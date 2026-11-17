<?php
// app/controllers/TaskController.php
// Controller manajemen tugas (tasks), agenda (events), deteksi bentrok, dan Smart Planner
// Menghubungkan TaskModel, EventModel, CourseModel, ScheduleModel, ConflictService, dan SmartPlannerService

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/view.php';
require_once __DIR__ . '/../models/TaskModel.php';
require_once __DIR__ . '/../models/EventModel.php';
require_once __DIR__ . '/../models/CourseModel.php';
require_once __DIR__ . '/../models/ScheduleModel.php';
require_once __DIR__ . '/../services/ConflictService.php';
require_once __DIR__ . '/../services/SmartPlannerService.php';

class TaskController
{
    private ?PDO $db;
    private TaskModel $taskModel;
    private EventModel $eventModel;
    private CourseModel $courseModel;
    private ScheduleModel $scheduleModel;
    private ConflictService $conflictService;
    private SmartPlannerService $smartPlannerService;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? getDBConnection();
        $this->taskModel = new TaskModel($this->db);
        $this->eventModel = new EventModel($this->db);
        $this->courseModel = new CourseModel();
        $this->scheduleModel = new ScheduleModel();
        $this->conflictService = new ConflictService($this->db);
        $this->smartPlannerService = new SmartPlannerService($this->db, $this->taskModel);
    }

    /**
     * Dispatcher utama request berdasarkan parameter aksi
     *
     * @param string|null $action
     */
    public function handleRequest(?string $action = null): void
    {
        requireLogin();

        $action = $action ?? ($_GET['action'] ?? 'index');

        switch ($action) {
            case 'index':
                $this->index();
                break;
            case 'create':
            case 'store':
                $this->store();
                break;
            case 'update':
                $this->update();
                break;
            case 'update_status':
            case 'toggle_status':
                $this->updateStatus();
                break;
            case 'delete':
                $this->delete();
                break;
            case 'planner':
                $this->planner();
                break;
            case 'update_preferences':
                $this->updatePreferences();
                break;
            case 'check_conflict':
            case 'api_conflict':
                $this->checkConflict();
                break;
            case 'store_event':
                $this->storeEvent();
                break;
            case 'api_recommendations':
                $this->apiRecommendations();
                break;
            default:
                $this->index();
                break;
        }
    }

    /**
     * Menampilkan daftar tugas mahasiswa dengan filter & statistik
     */
    public function index(): void
    {
        $userId = currentUserId();

        $filters = [
            'status'    => $_GET['status'] ?? '',
            'priority'  => $_GET['priority'] ?? '',
            'course_id' => !empty($_GET['course_id']) ? (int) $_GET['course_id'] : null,
            'search'    => trim($_GET['search'] ?? ''),
        ];

        $tasks = $this->taskModel->getAllByUserId($userId, $filters);
        $courses = $this->courseModel->getByUserId($userId);
        $counts = $this->taskModel->getCountsByStatus($userId);
        $urgentTasks = $this->taskModel->getUrgentTasks($userId, 4);

        // Preview rekomendasi Smart Planner
        $plannerData = $this->smartPlannerService->generateRecommendations($userId, 3);
        $previewRecommendations = array_slice($plannerData['recommendations'] ?? [], 0, 3);

        $viewData = [
            'pageTitle'              => 'Manajemen Tugas & Prioritas — CampusOS',
            'user'                   => currentUser(),
            'tasks'                  => $tasks ?: [],
            'courses'                => $courses ?: [],
            'counts'                 => $counts ?: [],
            'filters'                => $filters,
            'urgentTasks'            => $urgentTasks ?: [],
            'previewRecommendations' => $previewRecommendations ?: [],
        ];

        $viewFile = dirname(__DIR__, 2) . '/views/tasks/index.php';
        if (file_exists($viewFile)) {
            renderView('tasks/index', $viewData);
        } else {
            $this->renderTasksFallback($viewData);
        }
    }

    /**
     * Menyimpan tugas baru
     */
    public function store(): void
    {
        $userId = currentUserId();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=tasks');
            exit;
        }

        $title = trim($_POST['title'] ?? '');
        $deadlineDate = trim($_POST['deadline_date'] ?? '');

        if (empty($title) || empty($deadlineDate)) {
            setFlash('danger', 'Judul tugas dan tanggal deadline wajib diisi.');
            header('Location: index.php?page=tasks');
            exit;
        }

        $data = [
            'course_id'       => !empty($_POST['course_id']) ? (int) $_POST['course_id'] : null,
            'title'           => $title,
            'description'     => trim($_POST['description'] ?? ''),
            'deadline_date'   => $deadlineDate,
            'deadline_time'   => !empty($_POST['deadline_time']) ? trim($_POST['deadline_time']) : '23:59:00',
            'estimated_hours' => !empty($_POST['estimated_hours']) ? (float) $_POST['estimated_hours'] : 2.0,
            'priority'        => $_POST['priority'] ?? 'medium',
            'status'          => $_POST['status'] ?? 'pending',
        ];

        $insertedId = $this->taskModel->create($userId, $data);

        if ($insertedId) {
            setFlash('success', 'Tugas baru berhasil ditambahkan ke daftar.');
        } else {
            setFlash('danger', 'Gagal menyimpan tugas baru ke basis data.');
        }

        header('Location: index.php?page=tasks');
        exit;
    }

    /**
     * Memperbarui tugas yang sudah ada
     */
    public function update(): void
    {
        $userId = currentUserId();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=tasks');
            exit;
        }

        $id = (int) ($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $deadlineDate = trim($_POST['deadline_date'] ?? '');

        if ($id <= 0 || empty($title) || empty($deadlineDate)) {
            setFlash('danger', 'Data tugas tidak lengkap atau tidak valid.');
            header('Location: index.php?page=tasks');
            exit;
        }

        $data = [
            'course_id'       => !empty($_POST['course_id']) ? (int) $_POST['course_id'] : null,
            'title'           => $title,
            'description'     => trim($_POST['description'] ?? ''),
            'deadline_date'   => $deadlineDate,
            'deadline_time'   => !empty($_POST['deadline_time']) ? trim($_POST['deadline_time']) : '23:59:00',
            'estimated_hours' => !empty($_POST['estimated_hours']) ? (float) $_POST['estimated_hours'] : 2.0,
            'priority'        => $_POST['priority'] ?? 'medium',
            'status'          => $_POST['status'] ?? 'pending',
        ];

        $success = $this->taskModel->update($id, $userId, $data);

        if ($success) {
            setFlash('success', 'Tugas berhasil diperbarui.');
        } else {
            setFlash('danger', 'Gagal memperbarui data tugas.');
        }

        header('Location: index.php?page=tasks');
        exit;
    }

    /**
     * Memperbarui status tugas (pending, in_progress, done)
     */
    public function updateStatus(): void
    {
        $userId = currentUserId();

        $id = (int) ($_POST['id'] ?? ($_GET['id'] ?? 0));
        $status = $_POST['status'] ?? ($_GET['status'] ?? '');

        if ($id <= 0 || !in_array($status, ['pending', 'in_progress', 'done'])) {
            if ($this->isAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Parameter tidak valid.']);
                exit;
            }
            setFlash('danger', 'Parameter status tugas tidak valid.');
            header('Location: index.php?page=tasks');
            exit;
        }

        $success = $this->taskModel->updateStatus($id, $userId, $status);

        if ($this->isAjax()) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => $success,
                'id'      => $id,
                'status'  => $status,
                'message' => $success ? 'Status tugas berhasil diperbarui.' : 'Gagal memperbarui status tugas.',
            ]);
            exit;
        }

        if ($success) {
            $label = match ($status) {
                'done'        => 'diselesaikan',
                'in_progress' => 'sedang dikerjakan',
                default       => 'diubah menjadi pending',
            };
            setFlash('success', "Status tugas berhasil {$label}.");
        } else {
            setFlash('danger', 'Gagal memperbarui status tugas.');
        }

        $redirect = $_SERVER['HTTP_REFERER'] ?? 'index.php?page=tasks';
        header('Location: ' . $redirect);
        exit;
    }

    /**
     * Menghapus tugas milik pengguna aktif
     */
    public function delete(): void
    {
        $userId = currentUserId();

        $id = (int) ($_POST['id'] ?? ($_GET['id'] ?? 0));

        if ($id <= 0) {
            setFlash('danger', 'ID tugas tidak valid.');
            header('Location: index.php?page=tasks');
            exit;
        }

        $success = $this->taskModel->delete($id, $userId);

        if ($success) {
            setFlash('success', 'Tugas berhasil dihapus.');
        } else {
            setFlash('danger', 'Gagal menghapus tugas dari basis data.');
        }

        header('Location: index.php?page=tasks');
        exit;
    }

    /**
     * Halaman Smart Planner: Rekomendasi Alokasi Slot Belajar Mandiri
     */
    public function planner(): void
    {
        $userId = currentUserId();

        // Jika request POST, proses pembaruan preferensi jam belajar
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->updatePreferences();
            return;
        }

        $daysAhead = isset($_GET['days']) ? max(1, min(14, (int) $_GET['days'])) : 7;
        $recommendationData = $this->smartPlannerService->generateRecommendations($userId, $daysAhead);
        $preferences = $this->taskModel->getPlanningPreferences($userId);
        $rankedTasks = $this->smartPlannerService->getRankedTasks($userId);
        $courses = $this->courseModel->getByUserId($userId);

        $viewData = [
            'pageTitle'              => 'Smart Planner (Rekomendasi Belajar) — CampusOS',
            'user'                   => currentUser(),
            'recommendations'        => $recommendationData['recommendations'] ?? [],
            'plannerData'            => $recommendationData,
            'preferences'            => $preferences,
            'rankedTasks'            => $rankedTasks ?: [],
            'courses'                => $courses ?: [],
            'daysAhead'              => $daysAhead,
        ];

        $viewFile = dirname(__DIR__, 2) . '/views/planner/index.php';
        if (file_exists($viewFile)) {
            renderView('planner/index', $viewData);
        } else {
            $this->renderPlannerFallback($viewData);
        }
    }

    /**
     * Memperbarui preferensi jam belajar untuk Smart Planner
     */
    public function updatePreferences(): void
    {
        $userId = currentUserId();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=planner');
            exit;
        }

        $data = [
            'study_start_hour'        => $_POST['study_start_hour'] ?? '08:00:00',
            'study_end_hour'          => $_POST['study_end_hour'] ?? '21:00:00',
            'max_daily_study_hours'   => (int) ($_POST['max_daily_study_hours'] ?? 4),
            'preferred_session_hours' => (int) ($_POST['preferred_session_hours'] ?? 2),
        ];

        $success = $this->taskModel->updatePlanningPreferences($userId, $data);

        if ($success) {
            setFlash('success', 'Preferensi jam belajar Smart Planner berhasil disimpan.');
        } else {
            setFlash('danger', 'Gagal memperbarui preferensi jam belajar.');
        }

        header('Location: index.php?page=planner');
        exit;
    }

    /**
     * API Deteksi Bentrok Jadwal / Waktu (ConflictService)
     */
    public function checkConflict(): void
    {
        $userId = currentUserId();

        $date      = $_GET['date'] ?? ($_POST['date'] ?? date('Y-m-d'));
        $startTime = $_GET['start_time'] ?? ($_POST['start_time'] ?? '08:00:00');
        $endTime   = $_GET['end_time'] ?? ($_POST['end_time'] ?? '10:00:00');
        $excludeId = !empty($_GET['exclude_id']) ? (int) $_GET['exclude_id'] : null;

        $result = $this->conflictService->checkEventConflict($userId, $date, $startTime, $endTime, $excludeId);

        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }

    /**
     * Menyimpan agenda baru dengan pengecekan bentrok via ConflictService
     */
    public function storeEvent(): void
    {
        $userId = currentUserId();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=calendar');
            exit;
        }

        $title     = trim($_POST['title'] ?? '');
        $category  = $_POST['category'] ?? 'personal';
        $eventDate = trim($_POST['event_date'] ?? date('Y-m-d'));
        $startTime = trim($_POST['start_time'] ?? '08:00');
        $endTime   = trim($_POST['end_time'] ?? '09:00');
        $forceSave = !empty($_POST['force_save']);

        if (empty($title) || empty($eventDate) || empty($startTime) || empty($endTime)) {
            setFlash('danger', 'Data agenda tidak lengkap.');
            header('Location: index.php?page=calendar');
            exit;
        }

        // Jalankan deteksi bentrok jika tidak dipaksa simpan
        if (!$forceSave) {
            $conflictCheck = $this->conflictService->checkEventConflict($userId, $eventDate, $startTime, $endTime);
            if ($conflictCheck['has_conflict']) {
                $conflictTitles = array_map(function ($c) {
                    return $c['title'] . ' (' . $c['start_time'] . '-' . $c['end_time'] . ')';
                }, $conflictCheck['conflicts']);

                setFlash('warning', 'Terdeteksi bentrok dengan: ' . implode(', ', $conflictTitles));
                header('Location: index.php?page=calendar&conflict=1');
                exit;
            }
        }

        $data = [
            'title'      => $title,
            'category'   => $category,
            'event_date' => $eventDate,
            'start_time' => $startTime,
            'end_time'   => $endTime,
            'location'   => trim($_POST['location'] ?? ''),
            'notes'      => trim($_POST['notes'] ?? ''),
        ];

        $insertedId = $this->eventModel->create($userId, $data);

        if ($insertedId) {
            setFlash('success', 'Agenda baru berhasil disimpan ke kalender.');
        } else {
            setFlash('danger', 'Gagal menyimpan agenda ke basis data.');
        }

        header('Location: index.php?page=calendar');
        exit;
    }

    /**
     * API JSON Rekomendasi Smart Planner untuk interaksi dinamis UI
     */
    public function apiRecommendations(): void
    {
        $userId = currentUserId();
        $daysAhead = isset($_GET['days']) ? (int) $_GET['days'] : 7;

        $data = $this->smartPlannerService->generateRecommendations($userId, $daysAhead);

        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * Helper cek apakah request bertipe AJAX
     */
    private function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Render antarmuka Tasks berbasis Bento Grid
     * Digunakan ketika berkas views/tasks/index.php belum dibuat oleh tim frontend
     */
    private function renderTasksFallback(array $data): void
    {
        extract($data, EXTR_SKIP);

        $totalTasks = $counts['total'] ?? 0;
        $pendingTasks = $counts['pending'] ?? 0;
        $inProgressTasks = $counts['in_progress'] ?? 0;
        $doneTasks = $counts['done'] ?? 0;
        $overdueTasks = $counts['overdue'] ?? 0;

        ob_start();
        ?>
        <!-- Header & Action Row -->
        <div class="canvas-top-bar">
            <div class="canvas-top-left">
                <header class="canvas-header">
                    <h1 class="greeting-title">Manajemen Tugas &amp; Prioritas 📋</h1>
                    <p class="greeting-subtitle">Kelola deadline akademik, atur estimasi pengerjaan, dan optimalkan produktivitas harianmu.</p>
                </header>
            </div>
            <div class="canvas-top-right">
                <button type="button" class="btn-lime" onclick="document.getElementById('taskCreateModal').style.display='flex';" style="display:inline-flex; align-items:center; gap:6px; cursor:pointer;">
                    <svg class="ui-icon ui-icon-xs" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                    <span>Tambah Tugas</span>
                </button>
            </div>
        </div>

        <!-- 4 Summary Counter Cards -->
        <section class="summary-cards-grid" style="grid-template-columns: repeat(4, 1fr); margin-bottom: 24px;">
            <article class="summary-card">
                <div class="summary-card-header">
                    <div class="summary-icon-box lavender"><svg class="ui-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path><rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect></svg></div>
                    <div class="summary-title-group">
                        <h2 class="summary-main-title"><?= $totalTasks ?> Tugas</h2>
                        <span class="summary-subtitle">Total Keseluruhan</span>
                    </div>
                </div>
            </article>

            <article class="summary-card">
                <div class="summary-card-header">
                    <div class="summary-icon-box peach"><svg class="ui-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg></div>
                    <div class="summary-title-group">
                        <h2 class="summary-main-title"><?= $pendingTasks ?> Pending</h2>
                        <span class="summary-subtitle">Menunggu Pengerjaan</span>
                    </div>
                </div>
            </article>

            <article class="summary-card">
                <div class="summary-card-header">
                    <div class="summary-icon-box" style="background:#dbeafe; color:#1e40af;"><svg class="ui-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"></polyline><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path></svg></div>
                    <div class="summary-title-group">
                        <h2 class="summary-main-title"><?= $inProgressTasks ?> Berjalan</h2>
                        <span class="summary-subtitle">Sedang Dikerjakan</span>
                    </div>
                </div>
            </article>

            <article class="summary-card">
                <div class="summary-card-header">
                    <div class="summary-icon-box lime"><svg class="ui-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg></div>
                    <div class="summary-title-group">
                        <h2 class="summary-main-title"><?= $doneTasks ?> Selesai</h2>
                        <span class="summary-subtitle">Tuntas Dikerjakan</span>
                    </div>
                </div>
            </article>
        </section>

        <!-- Filter & Search Bar -->
        <section class="bento-card" style="padding: 16px 20px; margin-bottom: 24px;">
            <form method="GET" action="index.php" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
                <input type="hidden" name="page" value="tasks">
                
                <div style="flex: 1; min-width: 220px; position: relative;">
                    <input type="text" name="search" value="<?= e($filters['search'] ?? '') ?>" placeholder="Cari judul atau mata kuliah..." style="width: 100%; padding: 8px 12px; border-radius: var(--radius-sm); border: 1px solid var(--color-border); font-size: var(--font-size-sm); background: var(--color-bg-surface);">
                </div>

                <div>
                    <select name="status" style="padding: 8px 12px; border-radius: var(--radius-sm); border: 1px solid var(--color-border); font-size: var(--font-size-sm); background: var(--color-bg-surface);">
                        <option value="">Semua Status</option>
                        <option value="pending" <?= ($filters['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="in_progress" <?= ($filters['status'] ?? '') === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                        <option value="done" <?= ($filters['status'] ?? '') === 'done' ? 'selected' : '' ?>>Done</option>
                    </select>
                </div>

                <div>
                    <select name="priority" style="padding: 8px 12px; border-radius: var(--radius-sm); border: 1px solid var(--color-border); font-size: var(--font-size-sm); background: var(--color-bg-surface);">
                        <option value="">Semua Prioritas</option>
                        <option value="high" <?= ($filters['priority'] ?? '') === 'high' ? 'selected' : '' ?>>High</option>
                        <option value="medium" <?= ($filters['priority'] ?? '') === 'medium' ? 'selected' : '' ?>>Medium</option>
                        <option value="low" <?= ($filters['priority'] ?? '') === 'low' ? 'selected' : '' ?>>Low</option>
                    </select>
                </div>

                <div>
                    <select name="course_id" style="padding: 8px 12px; border-radius: var(--radius-sm); border: 1px solid var(--color-border); font-size: var(--font-size-sm); background: var(--color-bg-surface);">
                        <option value="">Semua Mata Kuliah</option>
                        <?php foreach ($courses as $c): ?>
                            <option value="<?= (int)$c['id'] ?>" <?= ($filters['course_id'] ?? 0) == $c['id'] ? 'selected' : '' ?>>
                                <?= e($c['course_code']) ?> - <?= e($c['course_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="filter-pill" style="cursor: pointer;">Filter</button>
                <a href="index.php?page=tasks" class="filter-pill" style="text-decoration:none; display:inline-flex; align-items:center;">Reset</a>
            </form>
        </section>

        <!-- Task List Table / Grid -->
        <article class="bento-card" style="padding: 24px;">
            <div class="card-header-row" style="margin-bottom: 16px;">
                <h2 class="card-heading-title">Daftar Tugas Kuliah (<?= count($tasks) ?>)</h2>
                <a href="index.php?page=planner" class="btn-lime" style="text-decoration:none; padding:6px 14px; font-size:var(--font-size-xs);">Buka Smart Planner ⚡</a>
            </div>

            <?php if (empty($tasks)): ?>
                <div style="text-align:center; padding: 48px 16px;">
                    <div style="font-size: 2.5rem; margin-bottom: 12px;">🎉</div>
                    <h3 class="card-heading-title" style="margin-bottom: 6px;">Tidak Ada Tugas</h3>
                    <p style="color: var(--color-text-muted); font-size: var(--font-size-sm);">Tidak ada tugas yang sesuai dengan kriteria filter saat ini.</p>
                </div>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: var(--font-size-sm);">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--color-border); color: var(--color-text-muted);">
                                <th style="padding: 12px 8px;">STATUS</th>
                                <th style="padding: 12px 8px;">JUDUL TUGAS</th>
                                <th style="padding: 12px 8px;">MATA KULIAH</th>
                                <th style="padding: 12px 8px;">DEADLINE</th>
                                <th style="padding: 12px 8px;">ESTIMASI</th>
                                <th style="padding: 12px 8px;">PRIORITAS</th>
                                <th style="padding: 12px 8px; text-align: right;">AKSI</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tasks as $task): 
                                $isDone = ($task['status'] === 'done');
                                $pColor = match($task['priority']) {
                                    'high' => 'var(--color-pastel-peach-text)',
                                    'medium' => '#b45309',
                                    default => 'var(--color-pastel-mint-text)'
                                };
                                $pBg = match($task['priority']) {
                                    'high' => 'var(--color-pastel-peach)',
                                    'medium' => '#fef3c7',
                                    default => 'var(--color-pastel-mint)'
                                };
                            ?>
                                <tr style="border-bottom: 1px solid var(--color-border); opacity: <?= $isDone ? '0.6' : '1' ?>;">
                                    <td style="padding: 14px 8px;">
                                        <form method="POST" action="index.php?page=tasks&action=update_status" style="display:inline;">
                                            <input type="hidden" name="id" value="<?= (int)$task['id'] ?>">
                                            <input type="hidden" name="status" value="<?= $isDone ? 'pending' : 'done' ?>">
                                            <button type="submit" title="<?= $isDone ? 'Tandai Belum Selesai' : 'Tandai Selesai' ?>" style="background:none; border:none; cursor:pointer; font-size:1.1rem; line-height:1;">
                                                <?= $isDone ? '☑️' : '⬜' ?>
                                            </button>
                                        </form>
                                    </td>
                                    <td style="padding: 14px 8px; font-weight: var(--font-weight-medium);">
                                        <span style="<?= $isDone ? 'text-decoration: line-through;' : '' ?>"><?= e($task['title']) ?></span>
                                        <?php if (!empty($task['description'])): ?>
                                            <div style="font-size: var(--font-size-xs); color: var(--color-text-muted); font-weight: normal; margin-top: 2px;">
                                                <?= e($task['description']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 14px 8px;">
                                        <span style="display: inline-flex; align-items: center; gap: 6px;">
                                            <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: <?= e($task['color_hex'] ?? '#3b82f6') ?>;"></span>
                                            <?= e($task['course_name'] ?? 'Umum') ?>
                                        </span>
                                    </td>
                                    <td style="padding: 14px 8px;">
                                        <?= date('d M Y', strtotime($task['deadline_date'])) ?>, <?= substr($task['deadline_time'], 0, 5) ?> WIB
                                    </td>
                                    <td style="padding: 14px 8px;">
                                        <?= (float)$task['estimated_hours'] ?> Jam
                                    </td>
                                    <td style="padding: 14px 8px;">
                                        <span style="padding: 3px 8px; border-radius: var(--radius-xs); font-size: var(--font-size-xs); font-weight: var(--font-weight-medium); background: <?= $pBg ?>; color: <?= $pColor ?>;">
                                            <?= ucfirst($task['priority']) ?>
                                        </span>
                                    </td>
                                    <td style="padding: 14px 8px; text-align: right;">
                                        <a href="index.php?page=tasks&action=delete&id=<?= (int)$task['id'] ?>" onclick="return confirm('Hapus tugas ini?')" style="color: #ef4444; text-decoration: none; font-size: var(--font-size-xs); font-weight: var(--font-weight-medium);">
                                            Hapus
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </article>

        <!-- Modal Tambah Tugas -->
        <div id="taskCreateModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center; padding:16px;">
            <div class="bento-card" style="width:100%; max-width:520px; padding:28px; background:var(--color-bg-surface); position:relative;">
                <div class="card-header-row" style="margin-bottom: 20px;">
                    <h3 class="card-heading-title">Tambah Tugas Baru</h3>
                    <button type="button" onclick="document.getElementById('taskCreateModal').style.display='none';" style="background:none; border:none; font-size:1.4rem; cursor:pointer;">&times;</button>
                </div>

                <form method="POST" action="index.php?page=tasks&action=store">
                    <div style="margin-bottom: 14px;">
                        <label style="display:block; font-size:var(--font-size-xs); font-weight:var(--font-weight-medium); margin-bottom:4px;">Judul Tugas *</label>
                        <input type="text" name="title" required placeholder="Contoh: Laporan Praktikum Jaringan" style="width:100%; padding:9px 12px; border-radius:var(--radius-sm); border:1px solid var(--color-border); font-size:var(--font-size-sm); background:var(--color-bg-base);">
                    </div>

                    <div style="margin-bottom: 14px;">
                        <label style="display:block; font-size:var(--font-size-xs); font-weight:var(--font-weight-medium); margin-bottom:4px;">Mata Kuliah</label>
                        <select name="course_id" style="width:100%; padding:9px 12px; border-radius:var(--radius-sm); border:1px solid var(--color-border); font-size:var(--font-size-sm); background:var(--color-bg-base);">
                            <option value="">-- Pilih Mata Kuliah (Opsional) --</option>
                            <?php foreach ($courses as $c): ?>
                                <option value="<?= (int)$c['id'] ?>"><?= e($c['course_code']) ?> - <?= e($c['course_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px; margin-bottom: 14px;">
                        <div>
                            <label style="display:block; font-size:var(--font-size-xs); font-weight:var(--font-weight-medium); margin-bottom:4px;">Tanggal Deadline *</label>
                            <input type="date" name="deadline_date" required value="<?= date('Y-m-d') ?>" style="width:100%; padding:9px 12px; border-radius:var(--radius-sm); border:1px solid var(--color-border); font-size:var(--font-size-sm); background:var(--color-bg-base);">
                        </div>
                        <div>
                            <label style="display:block; font-size:var(--font-size-xs); font-weight:var(--font-weight-medium); margin-bottom:4px;">Jam Deadline</label>
                            <input type="time" name="deadline_time" value="23:59" style="width:100%; padding:9px 12px; border-radius:var(--radius-sm); border:1px solid var(--color-border); font-size:var(--font-size-sm); background:var(--color-bg-base);">
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px; margin-bottom: 14px;">
                        <div>
                            <label style="display:block; font-size:var(--font-size-xs); font-weight:var(--font-weight-medium); margin-bottom:4px;">Estimasi Durasi (Jam)</label>
                            <input type="number" step="0.5" min="0.5" max="40" name="estimated_hours" value="2.0" style="width:100%; padding:9px 12px; border-radius:var(--radius-sm); border:1px solid var(--color-border); font-size:var(--font-size-sm); background:var(--color-bg-base);">
                        </div>
                        <div>
                            <label style="display:block; font-size:var(--font-size-xs); font-weight:var(--font-weight-medium); margin-bottom:4px;">Prioritas</label>
                            <select name="priority" style="width:100%; padding:9px 12px; border-radius:var(--radius-sm); border:1px solid var(--color-border); font-size:var(--font-size-sm); background:var(--color-bg-base);">
                                <option value="low">Low (Santai)</option>
                                <option value="medium" selected>Medium (Normal)</option>
                                <option value="high">High (Mendesak)</option>
                            </select>
                        </div>
                    </div>

                    <div style="margin-bottom: 20px;">
                        <label style="display:block; font-size:var(--font-size-xs); font-weight:var(--font-weight-medium); margin-bottom:4px;">Catatan Tambahan</label>
                        <textarea name="description" rows="2" placeholder="Detail instruksi pengerjaan..." style="width:100%; padding:9px 12px; border-radius:var(--radius-sm); border:1px solid var(--color-border); font-size:var(--font-size-sm); background:var(--color-bg-base);"></textarea>
                    </div>

                    <div style="display:flex; justify-content:flex-end; gap:8px;">
                        <button type="button" onclick="document.getElementById('taskCreateModal').style.display='none';" class="filter-pill" style="cursor:pointer;">Batal</button>
                        <button type="submit" class="btn-lime" style="cursor:pointer;">Simpan Tugas</button>
                    </div>
                </form>
            </div>
        </div>
        <?php
        $contentView = ob_get_clean();
        require dirname(__DIR__, 2) . '/views/layouts/main.php';
    }

    /**
     * Render antarmuka Smart Planner berbasis Bento Grid
     * Digunakan ketika berkas views/planner/index.php belum dibuat oleh tim frontend
     */
    private function renderPlannerFallback(array $data): void
    {
        extract($data, EXTR_SKIP);

        $recs = $recommendations ?? [];
        $prefs = $preferences ?? [];
        $tasksRanked = $rankedTasks ?? [];

        ob_start();
        ?>
        <div class="canvas-top-bar">
            <div class="canvas-top-left">
                <header class="canvas-header">
                    <h1 class="greeting-title">Smart Planner (Rekomendasi Belajar) ⚡</h1>
                    <p class="greeting-subtitle">Mesin rekomendasi slot belajar mandiri deterministik. Tidak pernah menimpa jadwal kuliah tetap.</p>
                </header>
            </div>
            <div class="canvas-top-right">
                <button type="button" class="btn-lime" onclick="document.getElementById('prefsModal').style.display='flex';" style="display:inline-flex; align-items:center; gap:6px; cursor:pointer;">
                    <svg class="ui-icon ui-icon-xs" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                    <span>Pengaturan Preferensi</span>
                </button>
            </div>
        </div>

        <!-- Preferences Bar -->
        <section class="summary-cards-grid" style="grid-template-columns: repeat(4, 1fr); margin-bottom: 24px;">
            <article class="summary-card">
                <div class="summary-card-header">
                    <div class="summary-icon-box lavender">⏱️</div>
                    <div class="summary-title-group">
                        <h2 class="summary-main-title"><?= substr($prefs['study_start_hour'] ?? '08:00', 0, 5) ?> - <?= substr($prefs['study_end_hour'] ?? '21:00', 0, 5) ?></h2>
                        <span class="summary-subtitle">Rentang Jam Belajar</span>
                    </div>
                </div>
            </article>

            <article class="summary-card">
                <div class="summary-card-header">
                    <div class="summary-icon-box lime">🎯</div>
                    <div class="summary-title-group">
                        <h2 class="summary-main-title"><?= (int)($prefs['max_daily_study_hours'] ?? 4) ?> Jam / Hari</h2>
                        <span class="summary-subtitle">Maksimal Belajar Harian</span>
                    </div>
                </div>
            </article>

            <article class="summary-card">
                <div class="summary-card-header">
                    <div class="summary-icon-box peach">⏳</div>
                    <div class="summary-title-group">
                        <h2 class="summary-main-title"><?= (int)($prefs['preferred_session_hours'] ?? 2) ?> Jam / Sesi</h2>
                        <span class="summary-subtitle">Durasi Ideal Per Sesi</span>
                    </div>
                </div>
            </article>

            <article class="summary-card">
                <div class="summary-card-header">
                    <div class="summary-icon-box" style="background:#dbeafe; color:#1e40af;">📌</div>
                    <div class="summary-title-group">
                        <h2 class="summary-main-title"><?= count($tasksRanked) ?> Tugas Aktif</h2>
                        <span class="summary-subtitle">Dievaluasi Mesin</span>
                    </div>
                </div>
            </article>
        </section>

        <!-- Rekomendasi Alokasi Belajar -->
        <article class="bento-card" style="padding: 24px; margin-bottom: 24px;">
            <div class="card-header-row" style="margin-bottom: 16px;">
                <h2 class="card-heading-title">Rekomendasi Slot Belajar Mandiri (<?= count($recs) ?> Sesi Ditemukan)</h2>
                <span class="card-growth-tag">Berdasarkan Formula Urgensi &amp; Celah Waktu Luang</span>
            </div>

            <?php if (empty($recs)): ?>
                <div style="text-align:center; padding: 48px 16px;">
                    <div style="font-size: 2.5rem; margin-bottom: 12px;">☕</div>
                    <h3 class="card-heading-title" style="margin-bottom: 6px;">Tidak Ada Rekomendasi Sesi</h3>
                    <p style="color: var(--color-text-muted); font-size: var(--font-size-sm); max-width: 480px; margin: 0 auto;">
                        <?= e($plannerData['message'] ?? 'Tidak ada slot waktu luang yang cukup atau semua tugas telah diselesaikan.') ?>
                    </p>
                </div>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 14px;">
                    <?php foreach ($recs as $rec): ?>
                        <div style="padding: 16px 20px; border-radius: var(--radius-md); background: var(--color-bg-base); border-left: 4px solid <?= e($rec['color_hex'] ?? '#3B82F6') ?>; display: flex; flex-direction: column; gap: 8px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <span style="font-size: var(--font-size-xs); font-weight: var(--font-weight-bold); background: var(--color-bg-surface); padding: 4px 10px; border-radius: var(--radius-sm); border: 1px solid var(--color-border);">
                                        📅 <?= e($rec['day_name']) ?>, <?= date('d M Y', strtotime($rec['date'])) ?>
                                    </span>
                                    <span style="font-size: var(--font-size-xs); font-weight: var(--font-weight-bold); background: var(--color-pastel-lime); color: var(--color-pastel-lime-text); padding: 4px 10px; border-radius: var(--radius-sm);">
                                        ⏰ <?= e($rec['start_time']) ?> - <?= e($rec['end_time']) ?> WIB (<?= $rec['session_duration_hours'] ?> Jam)
                                    </span>
                                    <span style="font-size: var(--font-size-xs); font-weight: var(--font-weight-medium); color: var(--color-text-muted);">
                                        Skor Urgensi: <?= $rec['urgency_score'] ?>
                                    </span>
                                </div>
                                <span style="font-size: var(--font-size-xs); font-weight: var(--font-weight-medium); padding: 2px 8px; border-radius: var(--radius-xs); background: <?= $rec['priority'] === 'high' ? 'var(--color-pastel-peach)' : 'var(--color-pastel-mint)' ?>; color: <?= $rec['priority'] === 'high' ? 'var(--color-pastel-peach-text)' : 'var(--color-pastel-mint-text)' ?>;">
                                    Prioritas: <?= ucfirst($rec['priority']) ?>
                                </span>
                            </div>

                            <div style="font-size: var(--font-size-md); font-weight: var(--font-weight-bold);">
                                <?= e($rec['task_title']) ?>
                                <span style="font-size: var(--font-size-xs); font-weight: var(--font-weight-normal); color: var(--color-text-muted); margin-left: 6px;">
                                    • <?= e($rec['course_name']) ?>
                                </span>
                            </div>

                            <div style="font-size: var(--font-size-xs); color: var(--color-text-muted); line-height: 1.5; background: var(--color-bg-surface); padding: 10px 14px; border-radius: var(--radius-sm);">
                                💡 <strong>Alasan Rekomendasi:</strong> <?= e($rec['reason']) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </article>

        <!-- Ranked Tasks Overview -->
        <article class="bento-card" style="padding: 24px;">
            <h2 class="card-heading-title" style="margin-bottom: 14px;">Urutan Antrean Pengerjaan Tugas Berdasarkan Urgensi</h2>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: var(--font-size-sm);">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--color-border); color: var(--color-text-muted);">
                            <th style="padding: 10px 8px;">PERINGKAT</th>
                            <th style="padding: 10px 8px;">TUGAS</th>
                            <th style="padding: 10px 8px;">MATA KULIAH</th>
                            <th style="padding: 10px 8px;">SISA WAKTU</th>
                            <th style="padding: 10px 8px;">ESTIMASI</th>
                            <th style="padding: 10px 8px;">PRIORITAS</th>
                            <th style="padding: 10px 8px; text-align: right;">SKOR URGENSI (U)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($tasksRanked)): ?>
                            <tr><td colspan="7" style="padding:20px; text-align:center; color:var(--color-text-muted);">Tidak ada tugas aktif.</td></tr>
                        <?php else: ?>
                            <?php foreach ($tasksRanked as $idx => $t): ?>
                                <tr style="border-bottom: 1px solid var(--color-border);">
                                    <td style="padding: 12px 8px; font-weight: var(--font-weight-bold); color: var(--color-text-muted);">#<?= $idx + 1 ?></td>
                                    <td style="padding: 12px 8px; font-weight: var(--font-weight-medium);"><?= e($t['title']) ?></td>
                                    <td style="padding: 12px 8px;"><?= e($t['course_name'] ?? 'Umum') ?></td>
                                    <td style="padding: 12px 8px; color: <?= $t['is_overdue'] ? '#ef4444' : 'inherit' ?>;">
                                        <?= $t['is_overdue'] ? 'Lewat Deadline' : $t['remaining_hours'] . ' Jam' ?>
                                    </td>
                                    <td style="padding: 12px 8px;"><?= (float)$t['estimated_hours'] ?> Jam</td>
                                    <td style="padding: 12px 8px;"><?= ucfirst($t['priority']) ?></td>
                                    <td style="padding: 12px 8px; text-align: right; font-weight: var(--font-weight-bold); color: #4338ca;">
                                        <?= $t['urgency_score'] ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </article>

        <!-- Modal Pengaturan Preferensi Jam Belajar -->
        <div id="prefsModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center; padding:16px;">
            <div class="bento-card" style="width:100%; max-width:480px; padding:28px; background:var(--color-bg-surface); position:relative;">
                <div class="card-header-row" style="margin-bottom: 20px;">
                    <h3 class="card-heading-title">Pengaturan Jam Belajar</h3>
                    <button type="button" onclick="document.getElementById('prefsModal').style.display='none';" style="background:none; border:none; font-size:1.4rem; cursor:pointer;">&times;</button>
                </div>

                <form method="POST" action="index.php?page=planner">
                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px; margin-bottom: 14px;">
                        <div>
                            <label style="display:block; font-size:var(--font-size-xs); font-weight:var(--font-weight-medium); margin-bottom:4px;">Jam Mulai Belajar</label>
                            <input type="time" name="study_start_hour" value="<?= substr($prefs['study_start_hour'] ?? '08:00', 0, 5) ?>" style="width:100%; padding:9px 12px; border-radius:var(--radius-sm); border:1px solid var(--color-border); font-size:var(--font-size-sm); background:var(--color-bg-base);">
                        </div>
                        <div>
                            <label style="display:block; font-size:var(--font-size-xs); font-weight:var(--font-weight-medium); margin-bottom:4px;">Jam Selesai Belajar</label>
                            <input type="time" name="study_end_hour" value="<?= substr($prefs['study_end_hour'] ?? '21:00', 0, 5) ?>" style="width:100%; padding:9px 12px; border-radius:var(--radius-sm); border:1px solid var(--color-border); font-size:var(--font-size-sm); background:var(--color-bg-base);">
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px; margin-bottom: 20px;">
                        <div>
                            <label style="display:block; font-size:var(--font-size-xs); font-weight:var(--font-weight-medium); margin-bottom:4px;">Maksimal Belajar / Hari (Jam)</label>
                            <input type="number" min="1" max="16" name="max_daily_study_hours" value="<?= (int)($prefs['max_daily_study_hours'] ?? 4) ?>" style="width:100%; padding:9px 12px; border-radius:var(--radius-sm); border:1px solid var(--color-border); font-size:var(--font-size-sm); background:var(--color-bg-base);">
                        </div>
                        <div>
                            <label style="display:block; font-size:var(--font-size-xs); font-weight:var(--font-weight-medium); margin-bottom:4px;">Durasi Ideal / Sesi (Jam)</label>
                            <input type="number" min="1" max="6" name="preferred_session_hours" value="<?= (int)($prefs['preferred_session_hours'] ?? 2) ?>" style="width:100%; padding:9px 12px; border-radius:var(--radius-sm); border:1px solid var(--color-border); font-size:var(--font-size-sm); background:var(--color-bg-base);">
                        </div>
                    </div>

                    <div style="display:flex; justify-content:flex-end; gap:8px;">
                        <button type="button" onclick="document.getElementById('prefsModal').style.display='none';" class="filter-pill" style="cursor:pointer;">Batal</button>
                        <button type="submit" class="btn-lime" style="cursor:pointer;">Simpan Pengaturan</button>
                    </div>
                </form>
            </div>
        </div>
        <?php
        $contentView = ob_get_clean();
        require dirname(__DIR__, 2) . '/views/layouts/main.php';
    }
}
