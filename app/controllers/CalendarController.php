<?php
// app/controllers/CalendarController.php
// Controller Kalender Kegiatan Mahasiswa & Deteksi Bentrok Jadwal
// Mengintegrasikan ScheduleModel, EventModel, TaskModel, dan ConflictService

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/view.php';
require_once __DIR__ . '/../models/ScheduleModel.php';
require_once __DIR__ . '/../models/EventModel.php';
require_once __DIR__ . '/../models/TaskModel.php';
require_once __DIR__ . '/../services/ConflictService.php';

class CalendarController
{
    private ?PDO $db;
    private ScheduleModel $scheduleModel;
    private EventModel $eventModel;
    private TaskModel $taskModel;
    private ConflictService $conflictService;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? getDBConnection();
        $this->scheduleModel = new ScheduleModel($this->db);
        $this->eventModel = new EventModel($this->db);
        $this->taskModel = new TaskModel($this->db);
        $this->conflictService = new ConflictService($this->db);
    }

    /**
     * Dispatcher utama request kalender
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
            case 'create_event':
            case 'store_event':
                $this->storeEvent();
                break;
            case 'update_event':
            case 'edit_event':
                $this->updateEvent();
                break;
            case 'delete_event':
                $this->deleteEvent();
                break;
            case 'check_conflict':
            case 'api_conflict':
                $this->checkConflict();
                break;
            case 'events_json':
                $this->eventsJson();
                break;
            default:
                $this->index();
                break;
        }
    }

    /**
     * Menampilkan halaman kalender dengan data jadwal kuliah, agenda, dan deadline tugas
     */
    public function index(): void
    {
        $userId = currentUserId();

        $month = isset($_GET['month']) ? max(1, min(12, (int) $_GET['month'])) : (int) date('m');
        $year  = isset($_GET['year']) ? max(2020, min(2040, (int) $_GET['year'])) : (int) date('Y');
        $selectedDate = trim($_GET['date'] ?? date('Y-m-d'));

        // Validasi format selectedDate
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate)) {
            $selectedDate = date('Y-m-d');
        }

        // Tentukan batas tanggal awal dan akhir bulan
        $daysInMonth = (int) date('t', strtotime(sprintf('%04d-%02d-01', $year, $month)));
        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate   = sprintf('%04d-%02d-%02d', $year, $month, $daysInMonth);

        // 1. Ambil seluruh agenda kegiatan (events) di bulan terkait
        $events = $this->eventModel->getByDateRange($userId, $startDate, $endDate) ?: [];

        // 2. Ambil seluruh deadline tugas (tasks) di bulan terkait
        $tasks = $this->taskModel->getAllByUserId($userId, [
            'start_date' => $startDate,
            'end_date'   => $endDate,
        ]) ?: [];

        // 3. Ambil jadwal kuliah mingguan tetap
        $weeklySchedules = $this->scheduleModel->getWeeklySchedules($userId) ?: [];

        // Pemetaan data kegiatan per tanggal untuk kalender grid
        $calendarGrid = [];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $d);
            $dayOfWeek = (int) date('N', strtotime($dateStr));

            $daySchedules = array_filter($weeklySchedules, function ($s) use ($dayOfWeek) {
                return (int) ($s['day_of_week'] ?? 0) === $dayOfWeek;
            });

            $dayEvents = array_filter($events, function ($e) use ($dateStr) {
                return ($e['event_date'] ?? '') === $dateStr;
            });

            $dayTasks = array_filter($tasks, function ($t) use ($dateStr) {
                return ($t['deadline_date'] ?? '') === $dateStr;
            });

            $calendarGrid[$dateStr] = [
                'day'       => $d,
                'dayOfWeek' => $dayOfWeek,
                'schedules' => array_values($daySchedules),
                'events'    => array_values($dayEvents),
                'tasks'     => array_values($dayTasks),
                'count'     => count($daySchedules) + count($dayEvents) + count($dayTasks),
            ];
        }

        // Data agenda khusus untuk tanggal yang sedang dipilih
        $selectedDayOfWeek = (int) date('N', strtotime($selectedDate));
        $selectedSchedules = array_values(array_filter($weeklySchedules, function ($s) use ($selectedDayOfWeek) {
            return (int) ($s['day_of_week'] ?? 0) === $selectedDayOfWeek;
        }));
        $selectedEvents = array_values(array_filter($events, function ($e) use ($selectedDate) {
            return ($e['event_date'] ?? '') === $selectedDate;
        }));
        $selectedTasks = array_values(array_filter($tasks, function ($t) use ($selectedDate) {
            return ($t['deadline_date'] ?? '') === $selectedDate;
        }));

        $monthNamesIndo = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        $dayNamesIndo = [
            1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis',
            5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'
        ];

        $viewData = [
            'pageTitle'         => 'Kalender Kegiatan & Deteksi Konflik — CampusOS',
            'user'              => currentUser(),
            'month'             => $month,
            'year'              => $year,
            'monthName'         => $monthNamesIndo[$month] ?? 'Bulan',
            'startDate'         => $startDate,
            'endDate'           => $endDate,
            'selectedDate'      => $selectedDate,
            'selectedDayName'   => $dayNamesIndo[$selectedDayOfWeek] ?? 'Hari',
            'events'            => $events,
            'tasks'             => $tasks,
            'weeklySchedules'   => $weeklySchedules,
            'calendarGrid'      => $calendarGrid,
            'selectedDayData'   => [
                'date'      => $selectedDate,
                'dayName'   => $dayNamesIndo[$selectedDayOfWeek] ?? 'Hari',
                'schedules' => $selectedSchedules,
                'events'    => $selectedEvents,
                'tasks'     => $selectedTasks,
            ],
        ];

        $viewFile = dirname(__DIR__, 2) . '/views/calendar/index.php';
        if (file_exists($viewFile)) {
            renderView('calendar/index', $viewData);
        } else {
            $this->renderCalendarFallback($viewData);
        }
    }

    /**
     * Menambah agenda baru dengan validasi bentrok (ConflictService)
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
        $startTime = trim($_POST['start_time'] ?? '');
        $endTime   = trim($_POST['end_time'] ?? '');
        $location  = trim($_POST['location'] ?? '');
        $notes     = trim($_POST['notes'] ?? '');

        if (empty($title) || empty($eventDate) || empty($startTime) || empty($endTime)) {
            setFlash('danger', 'Judul agenda, tanggal, jam mulai, dan jam selesai wajib diisi.');
            header("Location: index.php?page=calendar&date={$eventDate}");
            exit;
        }

        // DETEKSI BENTROK MENGGUNAKAN ConflictService
        $conflictCheck = $this->conflictService->checkEventConflict(
            $userId,
            $eventDate,
            $startTime,
            $endTime
        );

        if ($conflictCheck['has_conflict']) {
            $conflictDetails = [];
            foreach ($conflictCheck['conflicts'] as $c) {
                $conflictDetails[] = $c['message'];
            }
            $errorMessage = "Agenda BENTROK dan tidak dapat disimpan: " . implode(" | ", $conflictDetails);
            setFlash('danger', $errorMessage);
            header("Location: index.php?page=calendar&date={$eventDate}");
            exit;
        }

        $data = [
            'title'      => $title,
            'category'   => $category,
            'event_date' => $eventDate,
            'start_time' => $startTime,
            'end_time'   => $endTime,
            'location'   => $location,
            'notes'      => $notes,
        ];

        $createdId = $this->eventModel->create($userId, $data);

        if ($createdId) {
            setFlash('success', "Agenda '{$title}' berhasil ditambahkan ke kalender.");
        } else {
            setFlash('danger', 'Gagal menyimpan agenda ke basis data.');
        }

        header("Location: index.php?page=calendar&date={$eventDate}");
        exit;
    }

    /**
     * Memperbarui agenda yang sudah ada dengan validasi bentrok (ConflictService)
     */
    public function updateEvent(): void
    {
        $userId = currentUserId();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=calendar');
            exit;
        }

        $id        = (int) ($_POST['id'] ?? 0);
        $title     = trim($_POST['title'] ?? '');
        $category  = $_POST['category'] ?? 'personal';
        $eventDate = trim($_POST['event_date'] ?? date('Y-m-d'));
        $startTime = trim($_POST['start_time'] ?? '');
        $endTime   = trim($_POST['end_time'] ?? '');
        $location  = trim($_POST['location'] ?? '');
        $notes     = trim($_POST['notes'] ?? '');

        if ($id <= 0 || empty($title) || empty($eventDate) || empty($startTime) || empty($endTime)) {
            setFlash('danger', 'Data agenda tidak lengkap atau tidak valid.');
            header("Location: index.php?page=calendar&date={$eventDate}");
            exit;
        }

        // Verifikasi kepemilikan agenda oleh user aktif
        $existing = $this->eventModel->getById($id, $userId);
        if (!$existing) {
            setFlash('danger', 'Agenda tidak ditemukan atau Anda tidak memiliki hak akses.');
            header("Location: index.php?page=calendar&date={$eventDate}");
            exit;
        }

        // DETEKSI BENTROK MENGGUNAKAN ConflictService (abaikan event id sendiri)
        $conflictCheck = $this->conflictService->checkEventConflict(
            $userId,
            $eventDate,
            $startTime,
            $endTime,
            $id
        );

        if ($conflictCheck['has_conflict']) {
            $conflictDetails = [];
            foreach ($conflictCheck['conflicts'] as $c) {
                $conflictDetails[] = $c['message'];
            }
            $errorMessage = "Pembaruan agenda BENTROK: " . implode(" | ", $conflictDetails);
            setFlash('danger', $errorMessage);
            header("Location: index.php?page=calendar&date={$eventDate}");
            exit;
        }

        $data = [
            'title'      => $title,
            'category'   => $category,
            'event_date' => $eventDate,
            'start_time' => $startTime,
            'end_time'   => $endTime,
            'location'   => $location,
            'notes'      => $notes,
        ];

        $success = $this->eventModel->update($id, $userId, $data);

        if ($success) {
            setFlash('success', "Agenda '{$title}' berhasil diperbarui.");
        } else {
            setFlash('danger', 'Gagal memperbarui agenda.');
        }

        header("Location: index.php?page=calendar&date={$eventDate}");
        exit;
    }

    /**
     * Menghapus agenda milik pengguna aktif
     */
    public function deleteEvent(): void
    {
        $userId = currentUserId();

        $id = (int) ($_POST['id'] ?? ($_GET['id'] ?? 0));
        $date = trim($_GET['date'] ?? date('Y-m-d'));

        if ($id <= 0) {
            setFlash('danger', 'ID agenda tidak valid.');
            header('Location: index.php?page=calendar');
            exit;
        }

        $existing = $this->eventModel->getById($id, $userId);
        if (!$existing) {
            setFlash('danger', 'Agenda tidak ditemukan.');
            header('Location: index.php?page=calendar');
            exit;
        }

        $success = $this->eventModel->delete($id, $userId);

        if ($success) {
            setFlash('success', "Agenda '{$existing['title']}' berhasil dihapus.");
        } else {
            setFlash('danger', 'Gagal menghapus agenda.');
        }

        header("Location: index.php?page=calendar&date={$date}");
        exit;
    }

    /**
     * API JSON Deteksi Bentrok untuk pemeriksaan interaktif di frontend
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
     * API JSON Kegiatan & Tugas untuk kebutuhan kalender dinamis (ui.js / fetch)
     */
    public function eventsJson(): void
    {
        $userId = currentUserId();

        $start = $_GET['start'] ?? date('Y-m-01');
        $end   = $_GET['end'] ?? date('Y-m-t');

        $events = $this->eventModel->getByDateRange($userId, $start, $end) ?: [];
        $tasks  = $this->taskModel->getAllByUserId($userId, [
            'start_date' => $start,
            'end_date'   => $end,
        ]) ?: [];

        header('Content-Type: application/json');
        echo json_encode([
            'events' => $events,
            'tasks'  => $tasks,
        ]);
        exit;
    }

    /**
     * Render antarmuka Kalender berbasis Bento Grid
     * Digunakan ketika berkas views/calendar/index.php belum dibuat oleh tim frontend
     */
    private function renderCalendarFallback(array $data): void
    {
        extract($data, EXTR_SKIP);

        $prevMonth = $month - 1;
        $prevYear  = $year;
        if ($prevMonth < 1) {
            $prevMonth = 12;
            $prevYear--;
        }

        $nextMonth = $month + 1;
        $nextYear  = $year;
        if ($nextMonth > 12) {
            $nextMonth = 1;
            $nextYear++;
        }

        // Hitung hari pertama dalam seminggu untuk offset grid kalender (1=Senin, 7=Minggu)
        $firstDayTs = strtotime(sprintf('%04d-%02d-01', $year, $month));
        $firstDayOfWeek = (int) date('N', $firstDayTs); // 1 = Senin, ..., 7 = Minggu

        $categoryColors = [
            'personal'      => ['bg' => '#fef3c7', 'text' => '#b45309', 'label' => 'Pribadi'],
            'organization'  => ['bg' => 'var(--color-pastel-peach)', 'text' => 'var(--color-pastel-peach-text)', 'label' => 'Organisasi'],
            'study_session' => ['bg' => 'var(--color-pastel-lime)', 'text' => 'var(--color-pastel-lime-text)', 'label' => 'Belajar'],
            'exam'          => ['bg' => '#fee2e2', 'text' => '#b91c1c', 'label' => 'Ujian'],
        ];

        ob_start();
        ?>
        <!-- Header & Navigasi Bulan -->
        <div class="canvas-top-bar">
            <div class="canvas-top-left">
                <header class="canvas-header">
                    <h1 class="greeting-title">Kalender Kegiatan &amp; Deteksi Bentrok 🗓️</h1>
                    <p class="greeting-subtitle">Pantau seluruh agenda kuliah, kegiatan organisasi, dan deadline tugas dalam satu kalender terpadu.</p>
                </header>
            </div>
            <div class="canvas-top-right" style="display:flex; gap:8px;">
                <div style="display:flex; align-items:center; background:var(--color-bg-surface); border:1px solid var(--color-border); border-radius:var(--radius-sm); padding:2px 8px;">
                    <a href="index.php?page=calendar&month=<?= $prevMonth ?>&year=<?= $prevYear ?>" class="cal-nav-btn" title="Bulan Sebelumnya" style="text-decoration:none; display:inline-flex; align-items:center;">&larr;</a>
                    <span style="font-size:var(--font-size-sm); font-weight:var(--font-weight-bold); padding:0 8px;">
                        <?= e($monthName) ?> <?= $year ?>
                    </span>
                    <a href="index.php?page=calendar&month=<?= $nextMonth ?>&year=<?= $nextYear ?>" class="cal-nav-btn" title="Bulan Berikutnya" style="text-decoration:none; display:inline-flex; align-items:center;">&rarr;</a>
                </div>
                <button type="button" class="btn-lime" onclick="openAddEventModal('<?= e($selectedDate) ?>')" style="cursor:pointer; display:inline-flex; align-items:center; gap:6px;">
                    <svg class="ui-icon ui-icon-xs" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                    <span>+ Catat Agenda</span>
                </button>
            </div>
        </div>

        <!-- Layout 2 Kolom: Kiri Kalender Grid, Kanan Detail Hari Terpilih -->
        <div style="display:grid; grid-template-columns: 2fr 1fr; gap:20px; align-items:start;">
            
            <!-- Kolom Kiri: Kalender Grid Bulanan -->
            <article class="bento-card" style="padding: 24px;">
                <div class="card-header-row" style="margin-bottom: 16px;">
                    <h2 class="card-heading-title"><?= e($monthName) ?> <?= $year ?></h2>
                    <span class="card-growth-tag">🛡️ Deteksi bentrok waktu aktif</span>
                </div>

                <!-- Nama Hari Header -->
                <div style="display:grid; grid-template-columns: repeat(7, 1fr); text-align:center; font-size:var(--font-size-xs); font-weight:var(--font-weight-bold); color:var(--color-text-muted); margin-bottom:10px;">
                    <div>Sen</div><div>Sel</div><div>Rab</div><div>Kam</div><div>Jum</div><div>Sab</div><div>Min</div>
                </div>

                <!-- Grid Tanggal -->
                <div style="display:grid; grid-template-columns: repeat(7, 1fr); gap:6px;">
                    <?php 
                    // Blank offset cells sebelum tanggal 1
                    for ($b = 1; $b < $firstDayOfWeek; $b++): ?>
                        <div style="min-height:72px; background:transparent; border-radius:var(--radius-sm); opacity:0.3;"></div>
                    <?php endfor; ?>

                    <?php foreach ($calendarGrid as $dateStr => $dayInfo): 
                        $isSelected = ($dateStr === $selectedDate);
                        $isToday = ($dateStr === date('Y-m-d'));
                    ?>
                        <a href="index.php?page=calendar&month=<?= $month ?>&year=<?= $year ?>&date=<?= $dateStr ?>" style="min-height:72px; padding:6px; border-radius:var(--radius-sm); border:1px solid <?= $isSelected ? 'var(--color-pastel-lime-text)' : 'var(--color-border)' ?>; background:<?= $isSelected ? 'var(--color-pastel-lime)' : ($isToday ? 'var(--color-bg-base)' : 'var(--color-bg-surface)') ?>; text-decoration:none; color:inherit; display:flex; flex-direction:column; justify-content:space-between; transition:all 0.15s ease;">
                            <div style="display:flex; justify-content:space-between; align-items:center;">
                                <span style="font-size:var(--font-size-xs); font-weight:<?= ($isSelected || $isToday) ? 'var(--font-weight-bold)' : 'var(--font-weight-medium)' ?>; <?= $isToday ? 'color:#4f46e5;' : '' ?>">
                                    <?= $dayInfo['day'] ?>
                                </span>
                                <?php if ($isToday): ?>
                                    <span style="font-size:9px; background:#4f46e5; color:#fff; border-radius:3px; padding:1px 3px;">Kini</span>
                                <?php endif; ?>
                            </div>

                            <!-- Indikator Agenda/Kuliah/Tugas -->
                            <div style="display:flex; flex-wrap:wrap; gap:3px; margin-top:4px;">
                                <?php if (!empty($dayInfo['schedules'])): ?>
                                    <span title="<?= count($dayInfo['schedules']) ?> Jadwal Kuliah" style="width:6px; height:6px; border-radius:50%; background:#3b82f6;"></span>
                                <?php endif; ?>
                                <?php if (!empty($dayInfo['events'])): ?>
                                    <span title="<?= count($dayInfo['events']) ?> Agenda" style="width:6px; height:6px; border-radius:50%; background:#ec4899;"></span>
                                <?php endif; ?>
                                <?php if (!empty($dayInfo['tasks'])): ?>
                                    <span title="<?= count($dayInfo['tasks']) ?> Deadline Tugas" style="width:6px; height:6px; border-radius:50%; background:#f59e0b;"></span>
                                <?php endif; ?>
                            </div>

                            <div style="font-size:9px; color:var(--color-text-muted); text-align:right;">
                                <?= $dayInfo['count'] > 0 ? $dayInfo['count'] . ' item' : '' ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>

                <!-- Legenda Warna -->
                <div style="display:flex; gap:16px; margin-top:20px; font-size:var(--font-size-xs); color:var(--color-text-muted); padding-top:12px; border-top:1px solid var(--color-border);">
                    <div style="display:flex; align-items:center; gap:6px;">
                        <span style="width:8px; height:8px; border-radius:50%; background:#3b82f6;"></span>
                        <span>Jadwal Kuliah</span>
                    </div>
                    <div style="display:flex; align-items:center; gap:6px;">
                        <span style="width:8px; height:8px; border-radius:50%; background:#ec4899;"></span>
                        <span>Agenda / Kegiatan</span>
                    </div>
                    <div style="display:flex; align-items:center; gap:6px;">
                        <span style="width:8px; height:8px; border-radius:50%; background:#f59e0b;"></span>
                        <span>Deadline Tugas</span>
                    </div>
                </div>
            </article>

            <!-- Kolom Kanan: Rincian Hari Terpilih -->
            <article class="bento-card" style="padding: 24px;">
                <div class="card-header-row" style="margin-bottom: 16px;">
                    <div>
                        <h2 class="card-heading-title" style="margin-bottom:2px;"><?= e($selectedDayData['dayName']) ?></h2>
                        <span style="font-size:var(--font-size-xs); color:var(--color-text-muted);"><?= date('d F Y', strtotime($selectedDate)) ?></span>
                    </div>
                    <button type="button" class="btn-circle-add" onclick="openAddEventModal('<?= e($selectedDate) ?>')" title="Tambah Agenda">+</button>
                </div>

                <!-- 1. Jadwal Kuliah Hari Terpilih -->
                <div style="margin-bottom:20px;">
                    <h3 style="font-size:var(--font-size-xs); font-weight:var(--font-weight-bold); text-transform:uppercase; color:var(--color-text-muted); margin-bottom:8px;">
                        📚 Perkuliahan Tetap (<?= count($selectedDayData['schedules']) ?>)
                    </h3>
                    <?php if (empty($selectedDayData['schedules'])): ?>
                        <p style="font-size:var(--font-size-xs); color:var(--color-text-muted); font-style:italic; margin:0;">Tidak ada jadwal kuliah hari ini.</p>
                    <?php else: ?>
                        <div style="display:flex; flex-direction:column; gap:8px;">
                            <?php foreach ($selectedDayData['schedules'] as $sch): ?>
                                <div style="padding:8px 10px; border-radius:var(--radius-sm); background:var(--color-bg-base); border-left:3px solid <?= e($sch['color_hex'] ?? '#3B82F6') ?>; font-size:var(--font-size-xs);">
                                    <div style="font-weight:var(--font-weight-bold);"><?= e($sch['course_name']) ?></div>
                                    <div style="color:var(--color-text-muted); margin-top:2px;">
                                        ⏰ <?= substr($sch['start_time'], 0, 5) ?> - <?= substr($sch['end_time'], 0, 5) ?> WIB
                                        <?php if (!empty($sch['room'])): ?> • 📍 <?= e($sch['room']) ?><?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- 2. Agenda Kegiatan Hari Terpilih -->
                <div style="margin-bottom:20px;">
                    <h3 style="font-size:var(--font-size-xs); font-weight:var(--font-weight-bold); text-transform:uppercase; color:var(--color-text-muted); margin-bottom:8px;">
                        📌 Agenda Kegiatan (<?= count($selectedDayData['events']) ?>)
                    </h3>
                    <?php if (empty($selectedDayData['events'])): ?>
                        <p style="font-size:var(--font-size-xs); color:var(--color-text-muted); font-style:italic; margin:0;">Belum ada agenda pada tanggal ini.</p>
                    <?php else: ?>
                        <div style="display:flex; flex-direction:column; gap:8px;">
                            <?php foreach ($selectedDayData['events'] as $ev): 
                                $catInfo = $categoryColors[$ev['category']] ?? $categoryColors['personal'];
                            ?>
                                <div style="padding:10px 12px; border-radius:var(--radius-sm); background:var(--color-bg-base); border:1px solid var(--color-border); font-size:var(--font-size-xs);">
                                    <div style="display:flex; justify-content:space-between; align-items:start;">
                                        <div style="font-weight:var(--font-weight-bold);"><?= e($ev['title']) ?></div>
                                        <span style="padding:2px 6px; border-radius:3px; font-size:9px; background:<?= $catInfo['bg'] ?>; color:<?= $catInfo['text'] ?>;">
                                            <?= $catInfo['label'] ?>
                                        </span>
                                    </div>
                                    <div style="color:var(--color-text-muted); margin-top:3px;">
                                        ⏰ <?= substr($ev['start_time'], 0, 5) ?> - <?= substr($ev['end_time'], 0, 5) ?> WIB
                                        <?php if (!empty($ev['location'])): ?> • 📍 <?= e($ev['location']) ?><?php endif; ?>
                                    </div>
                                    <?php if (!empty($ev['notes'])): ?>
                                        <div style="margin-top:4px; font-style:italic; color:var(--color-text-muted); font-size:10px;">
                                            <?= e($ev['notes']) ?>
                                        </div>
                                    <?php endif; ?>
                                    <div style="margin-top:8px; display:flex; justify-content:flex-end; gap:8px;">
                                        <button type="button" onclick="editEvent(<?= htmlspecialchars(json_encode($ev), ENT_QUOTES, 'UTF-8') ?>)" style="background:none; border:none; color:#4f46e5; cursor:pointer; font-size:11px;">Edit</button>
                                        <a href="index.php?page=calendar&action=delete_event&id=<?= (int)$ev['id'] ?>&date=<?= e($selectedDate) ?>" onclick="return confirm('Hapus agenda ini?')" style="color:#ef4444; text-decoration:none; font-size:11px;">Hapus</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- 3. Deadline Tugas Hari Terpilih -->
                <div>
                    <h3 style="font-size:var(--font-size-xs); font-weight:var(--font-weight-bold); text-transform:uppercase; color:var(--color-text-muted); margin-bottom:8px;">
                        ⏳ Deadline Tugas (<?= count($selectedDayData['tasks']) ?>)
                    </h3>
                    <?php if (empty($selectedDayData['tasks'])): ?>
                        <p style="font-size:var(--font-size-xs); color:var(--color-text-muted); font-style:italic; margin:0;">Tidak ada deadline tugas hari ini.</p>
                    <?php else: ?>
                        <div style="display:flex; flex-direction:column; gap:8px;">
                            <?php foreach ($selectedDayData['tasks'] as $tk): ?>
                                <div style="padding:8px 10px; border-radius:var(--radius-sm); background:var(--color-bg-base); border-left:3px solid #f59e0b; font-size:var(--font-size-xs);">
                                    <div style="font-weight:var(--font-weight-bold);"><?= e($tk['title']) ?></div>
                                    <div style="color:var(--color-text-muted); margin-top:2px;">
                                        Jam <?= substr($tk['deadline_time'], 0, 5) ?> WIB • Prioritas: <?= ucfirst($tk['priority']) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </article>
        </div>

        <!-- Modal Tambah / Edit Agenda -->
        <div id="eventModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center; padding:16px;">
            <div class="bento-card" style="width:100%; max-width:480px; padding:28px; background:var(--color-bg-surface); position:relative;">
                <div class="card-header-row" style="margin-bottom: 20px;">
                    <h3 class="card-heading-title" id="eventModalTitle">Catat Agenda Baru</h3>
                    <button type="button" onclick="document.getElementById('eventModal').style.display='none';" style="background:none; border:none; font-size:1.4rem; cursor:pointer;">&times;</button>
                </div>

                <form method="POST" id="eventForm" action="index.php?page=calendar&action=store_event">
                    <input type="hidden" name="id" id="eventId" value="">

                    <div style="margin-bottom: 14px;">
                        <label style="display:block; font-size:var(--font-size-xs); font-weight:var(--font-weight-medium); margin-bottom:4px;">Judul Agenda *</label>
                        <input type="text" name="title" id="eventTitle" required placeholder="Contoh: Rapat Koordinasi Panitia" style="width:100%; padding:9px 12px; border-radius:var(--radius-sm); border:1px solid var(--color-border); font-size:var(--font-size-sm); background:var(--color-bg-base);">
                    </div>

                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px; margin-bottom: 14px;">
                        <div>
                            <label style="display:block; font-size:var(--font-size-xs); font-weight:var(--font-weight-medium); margin-bottom:4px;">Kategori *</label>
                            <select name="category" id="eventCategory" required style="width:100%; padding:9px 12px; border-radius:var(--radius-sm); border:1px solid var(--color-border); font-size:var(--font-size-sm); background:var(--color-bg-base);">
                                <option value="personal">Pribadi</option>
                                <option value="organization">Organisasi</option>
                                <option value="study_session">Sesi Belajar</option>
                                <option value="exam">Ujian</option>
                            </select>
                        </div>
                        <div>
                            <label style="display:block; font-size:var(--font-size-xs); font-weight:var(--font-weight-medium); margin-bottom:4px;">Tanggal Agenda *</label>
                            <input type="date" name="event_date" id="eventDate" required value="<?= e($selectedDate) ?>" style="width:100%; padding:9px 12px; border-radius:var(--radius-sm); border:1px solid var(--color-border); font-size:var(--font-size-sm); background:var(--color-bg-base);">
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px; margin-bottom: 14px;">
                        <div>
                            <label style="display:block; font-size:var(--font-size-xs); font-weight:var(--font-weight-medium); margin-bottom:4px;">Jam Mulai *</label>
                            <input type="time" name="start_time" id="eventStartTime" required value="13:00" style="width:100%; padding:9px 12px; border-radius:var(--radius-sm); border:1px solid var(--color-border); font-size:var(--font-size-sm); background:var(--color-bg-base);">
                        </div>
                        <div>
                            <label style="display:block; font-size:var(--font-size-xs); font-weight:var(--font-weight-medium); margin-bottom:4px;">Jam Selesai *</label>
                            <input type="time" name="end_time" id="eventEndTime" required value="15:00" style="width:100%; padding:9px 12px; border-radius:var(--radius-sm); border:1px solid var(--color-border); font-size:var(--font-size-sm); background:var(--color-bg-base);">
                        </div>
                    </div>

                    <div style="margin-bottom: 14px;">
                        <label style="display:block; font-size:var(--font-size-xs); font-weight:var(--font-weight-medium); margin-bottom:4px;">Lokasi</label>
                        <input type="text" name="location" id="eventLocation" placeholder="Contoh: Gedung PKM atau Zoom" style="width:100%; padding:9px 12px; border-radius:var(--radius-sm); border:1px solid var(--color-border); font-size:var(--font-size-sm); background:var(--color-bg-base);">
                    </div>

                    <div style="margin-bottom: 16px;">
                        <label style="display:block; font-size:var(--font-size-xs); font-weight:var(--font-weight-medium); margin-bottom:4px;">Catatan Tambahan</label>
                        <textarea name="notes" id="eventNotes" rows="2" placeholder="Catatan penting atau agenda rapat..." style="width:100%; padding:9px 12px; border-radius:var(--radius-sm); border:1px solid var(--color-border); font-size:var(--font-size-sm); background:var(--color-bg-base);"></textarea>
                    </div>

                    <div style="font-size:var(--font-size-xs); color:var(--color-text-muted); background:var(--color-bg-base); padding:10px 12px; border-radius:var(--radius-sm); margin-bottom:16px;">
                        🛡️ <strong>Deteksi Bentrok Otomatis:</strong> ConflictService akan memeriksa apakah waktu agenda menimpa jadwal perkuliahan atau agenda lain.
                    </div>

                    <div style="display:flex; justify-content:flex-end; gap:8px;">
                        <button type="button" onclick="document.getElementById('eventModal').style.display='none';" class="filter-pill" style="cursor:pointer;">Batal</button>
                        <button type="submit" class="btn-lime" id="eventSubmitBtn" style="cursor:pointer;">Simpan Agenda</button>
                    </div>
                </form>
            </div>
        </div>

        <script>
        function openAddEventModal(date) {
            document.getElementById('eventModalTitle').innerText = 'Catat Agenda Baru';
            document.getElementById('eventForm').action = 'index.php?page=calendar&action=store_event';
            document.getElementById('eventId').value = '';
            document.getElementById('eventTitle').value = '';
            document.getElementById('eventCategory').value = 'personal';
            document.getElementById('eventDate').value = date || '<?= e($selectedDate) ?>';
            document.getElementById('eventStartTime').value = '13:00';
            document.getElementById('eventEndTime').value = '15:00';
            document.getElementById('eventLocation').value = '';
            document.getElementById('eventNotes').value = '';
            document.getElementById('eventSubmitBtn').innerText = 'Simpan Agenda';
            document.getElementById('eventModal').style.display = 'flex';
        }

        function editEvent(ev) {
            document.getElementById('eventModalTitle').innerText = 'Edit Agenda Kegiatan';
            document.getElementById('eventForm').action = 'index.php?page=calendar&action=update_event';
            document.getElementById('eventId').value = ev.id;
            document.getElementById('eventTitle').value = ev.title;
            document.getElementById('eventCategory').value = ev.category || 'personal';
            document.getElementById('eventDate').value = ev.event_date;
            document.getElementById('eventStartTime').value = ev.start_time ? ev.start_time.substring(0, 5) : '13:00';
            document.getElementById('eventEndTime').value = ev.end_time ? ev.end_time.substring(0, 5) : '15:00';
            document.getElementById('eventLocation').value = ev.location || '';
            document.getElementById('eventNotes').value = ev.notes || '';
            document.getElementById('eventSubmitBtn').innerText = 'Perbarui Agenda';
            document.getElementById('eventModal').style.display = 'flex';
        }
        </script>
        <?php
        $contentView = ob_get_clean();
        require dirname(__DIR__, 2) . '/views/layouts/main.php';
    }
}
