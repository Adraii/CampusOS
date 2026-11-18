<?php
// app/controllers/AcademicController.php
// Controller manajemen mata kuliah (courses) dan jadwal kuliah tetap (class_schedules)
// Terintegrasi dengan ConflictService untuk validasi bentrok jadwal

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/view.php';
require_once __DIR__ . '/../models/CourseModel.php';
require_once __DIR__ . '/../models/ScheduleModel.php';
require_once __DIR__ . '/../services/ConflictService.php';

class AcademicController
{
    private ?PDO $db;
    private CourseModel $courseModel;
    private ScheduleModel $scheduleModel;
    private ConflictService $conflictService;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? getDBConnection();
        $this->courseModel = new CourseModel($this->db);
        $this->scheduleModel = new ScheduleModel($this->db);
        $this->conflictService = new ConflictService($this->db);
    }

    /**
     * Dispatcher aksi request akademis
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
            case 'create_course':
            case 'store_course':
                $this->storeCourse();
                break;
            case 'edit_course':
            case 'update_course':
                $this->updateCourse();
                break;
            case 'delete_course':
                $this->deleteCourse();
                break;
            case 'create_schedule':
            case 'store_schedule':
                $this->storeSchedule();
                break;
            case 'delete_schedule':
                $this->deleteSchedule();
                break;
            default:
                $this->index();
                break;
        }
    }

    /**
     * Menampilkan daftar mata kuliah dan jadwal perkuliahan mingguan
     */
    public function index(): void
    {
        $userId = currentUserId();

        $courses = $this->courseModel->getByUserId($userId) ?: [];
        $weeklySchedules = $this->scheduleModel->getWeeklySchedules($userId) ?: [];

        // Kelompokkan jadwal perkuliahan per hari (1 = Senin s/d 7 = Minggu)
        $groupedSchedules = [
            1 => [],
            2 => [],
            3 => [],
            4 => [],
            5 => [],
            6 => [],
            7 => [],
        ];

        foreach ($weeklySchedules as $sched) {
            $day = (int) ($sched['day_of_week'] ?? 1);
            if (isset($groupedSchedules[$day])) {
                $groupedSchedules[$day][] = $sched;
            }
        }

        $viewData = [
            'pageTitle'        => 'Manajemen Akademik & Jadwal Kuliah — CampusOS',
            'user'             => currentUser(),
            'courses'          => $courses,
            'weeklySchedules'  => $weeklySchedules,
            'groupedSchedules' => $groupedSchedules,
        ];

        $viewFile = dirname(__DIR__, 2) . '/views/academic/index.php';
        if (file_exists($viewFile)) {
            renderView('academic/index', $viewData);
        } else {
            $this->renderAcademicFallback($viewData);
        }
    }

    /**
     * Menambah mata kuliah baru
     */
    public function storeCourse(): void
    {
        $userId = currentUserId();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=academic');
            exit;
        }

        $courseCode = trim($_POST['course_code'] ?? '');
        $courseName = trim($_POST['course_name'] ?? '');

        if (empty($courseCode) || empty($courseName)) {
            setFlash('danger', 'Kode mata kuliah dan nama mata kuliah wajib diisi.');
            header('Location: index.php?page=academic');
            exit;
        }

        $data = [
            'course_code' => $courseCode,
            'course_name' => $courseName,
            'lecturer'    => trim($_POST['lecturer'] ?? ''),
            'credits'     => max(1, min(6, (int) ($_POST['credits'] ?? 3))),
            'color_hex'   => trim($_POST['color_hex'] ?? '#3B82F6'),
        ];

        $createdId = $this->courseModel->create($userId, $data);

        if ($createdId) {
            setFlash('success', "Mata kuliah {$courseName} ({$courseCode}) berhasil ditambahkan.");
        } else {
            setFlash('danger', 'Gagal menyimpan mata kuliah baru.');
        }

        header('Location: index.php?page=academic');
        exit;
    }

    /**
     * Memperbarui data mata kuliah
     */
    public function updateCourse(): void
    {
        $userId = currentUserId();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=academic');
            exit;
        }

        $id = (int) ($_POST['id'] ?? 0);
        $courseCode = trim($_POST['course_code'] ?? '');
        $courseName = trim($_POST['course_name'] ?? '');

        if ($id <= 0 || empty($courseCode) || empty($courseName)) {
            setFlash('danger', 'Data mata kuliah tidak valid atau tidak lengkap.');
            header('Location: index.php?page=academic');
            exit;
        }

        // Verifikasi kepemilikan data (user_id = :user_id)
        $existing = $this->courseModel->getById($id, $userId);
        if (!$existing) {
            setFlash('danger', 'Mata kuliah tidak ditemukan atau Anda tidak memiliki akses.');
            header('Location: index.php?page=academic');
            exit;
        }

        $data = [
            'course_code' => $courseCode,
            'course_name' => $courseName,
            'lecturer'    => trim($_POST['lecturer'] ?? ''),
            'credits'     => max(1, min(6, (int) ($_POST['credits'] ?? 3))),
            'color_hex'   => trim($_POST['color_hex'] ?? '#3B82F6'),
        ];

        $success = $this->courseModel->update($id, $userId, $data);

        if ($success) {
            setFlash('success', "Mata kuliah {$courseName} berhasil diperbarui.");
        } else {
            setFlash('danger', 'Gagal memperbarui data mata kuliah.');
        }

        header('Location: index.php?page=academic');
        exit;
    }

    /**
     * Menghapus mata kuliah milik pengguna aktif
     */
    public function deleteCourse(): void
    {
        $userId = currentUserId();

        $id = (int) ($_POST['id'] ?? ($_GET['id'] ?? 0));

        if ($id <= 0) {
            setFlash('danger', 'ID mata kuliah tidak valid.');
            header('Location: index.php?page=academic');
            exit;
        }

        // Validasi kepemilikan
        $existing = $this->courseModel->getById($id, $userId);
        if (!$existing) {
            setFlash('danger', 'Mata kuliah tidak ditemukan.');
            header('Location: index.php?page=academic');
            exit;
        }

        $success = $this->courseModel->delete($id, $userId);

        if ($success) {
            setFlash('success', "Mata kuliah '{$existing['course_name']}' dan jadwal terkait berhasil dihapus.");
        } else {
            setFlash('danger', 'Gagal menghapus mata kuliah.');
        }

        header('Location: index.php?page=academic');
        exit;
    }

    /**
     * Menambah jadwal kuliah baru dengan validasi bentrok (ConflictService)
     */
    public function storeSchedule(): void
    {
        $userId = currentUserId();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=academic');
            exit;
        }

        $courseId  = (int) ($_POST['course_id'] ?? 0);
        $dayOfWeek = (int) ($_POST['day_of_week'] ?? 1);
        $startTime = trim($_POST['start_time'] ?? '');
        $endTime   = trim($_POST['end_time'] ?? '');
        $room      = trim($_POST['room'] ?? '');

        if ($courseId <= 0 || $dayOfWeek < 1 || $dayOfWeek > 7 || empty($startTime) || empty($endTime)) {
            setFlash('danger', 'Data jadwal perkuliahan belum lengkap atau hari tidak valid.');
            header('Location: index.php?page=academic');
            exit;
        }

        // Verifikasi kepemilikan mata kuliah oleh user aktif
        $course = $this->courseModel->getById($courseId, $userId);
        if (!$course) {
            setFlash('danger', 'Mata kuliah yang dipilih tidak valid.');
            header('Location: index.php?page=academic');
            exit;
        }

        // DETEKSI BENTROK WAKTU MENGGUNAKAN ConflictService
        $conflictCheck = $this->conflictService->checkClassScheduleConflict(
            $userId,
            $dayOfWeek,
            $startTime,
            $endTime
        );

        if ($conflictCheck['has_conflict']) {
            $conflictDetails = [];
            foreach ($conflictCheck['conflicts'] as $c) {
                $conflictDetails[] = $c['message'];
            }
            $errorMessage = "Jadwal kuliah tidak dapat disimpan karena BENTROK: " . implode(" | ", $conflictDetails);
            setFlash('danger', $errorMessage);
            header('Location: index.php?page=academic');
            exit;
        }

        // Jika tidak ada bentrok, simpan jadwal kuliah tetap baru
        $data = [
            'course_id'   => $courseId,
            'day_of_week' => $dayOfWeek,
            'start_time'  => $startTime,
            'end_time'    => $endTime,
            'room'        => $room,
        ];

        $createdId = $this->scheduleModel->create($userId, $data);

        if ($createdId) {
            setFlash('success', "Jadwal kuliah '{$course['course_name']}' berhasil ditambahkan tanpa konflik.");
        } else {
            setFlash('danger', 'Gagal menyimpan jadwal kuliah ke basis data.');
        }

        header('Location: index.php?page=academic');
        exit;
    }

    /**
     * Menghapus jadwal perkuliahan
     */
    public function deleteSchedule(): void
    {
        $userId = currentUserId();

        $id = (int) ($_POST['id'] ?? ($_GET['id'] ?? 0));

        if ($id <= 0) {
            setFlash('danger', 'ID jadwal perkuliahan tidak valid.');
            header('Location: index.php?page=academic');
            exit;
        }

        $existing = $this->scheduleModel->getById($id, $userId);
        if (!$existing) {
            setFlash('danger', 'Jadwal perkuliahan tidak ditemukan.');
            header('Location: index.php?page=academic');
            exit;
        }

        $success = $this->scheduleModel->delete($id, $userId);

        if ($success) {
            setFlash('success', 'Jadwal perkuliahan berhasil dihapus.');
        } else {
            setFlash('danger', 'Gagal menghapus jadwal perkuliahan.');
        }

        header('Location: index.php?page=academic');
        exit;
    }

    /**
     * Render antarmuka Academic & Jadwal berbasis Bento Grid
     * Digunakan ketika berkas views/academic/index.php belum dibuat oleh tim frontend
     */
    private function renderAcademicFallback(array $data): void
    {
        extract($data, EXTR_SKIP);

        $dayNames = [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
        ];

        $totalSKS = array_sum(array_column($courses, 'credits'));

        ob_start();
        ?>
        <!-- Header & Action Row -->
        <div class="canvas-top-bar">
            <div class="canvas-top-left">
                <header class="canvas-header">
                    <h1 class="greeting-title">Manajemen Akademik &amp; Jadwal Kuliah 🎓</h1>
                    <p class="greeting-subtitle">Kelola katalog mata kuliah aktif, jadwal mingguan tetap, dan ruang perkuliahan.</p>
                </header>
            </div>
            <div class="canvas-top-right" style="display:flex; gap:8px;">
                <button type="button" class="filter-pill" onclick="document.getElementById('courseModal').style.display='flex';" style="cursor:pointer;">
                    + Tambah Mata Kuliah
                </button>
                <button type="button" class="btn-lime" onclick="document.getElementById('scheduleModal').style.display='flex';" style="cursor:pointer; display:inline-flex; align-items:center; gap:6px;">
                    <svg class="ui-icon ui-icon-xs" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                    <span>+ Tambah Jadwal Kuliah</span>
                </button>
            </div>
        </div>

        <!-- 3 Summary Counter Cards -->
        <section class="summary-cards-grid" style="grid-template-columns: repeat(3, 1fr); margin-bottom: 24px;">
            <article class="summary-card">
                <div class="summary-card-header">
                    <div class="summary-icon-box lavender">📚</div>
                    <div class="summary-title-group">
                        <h2 class="summary-main-title"><?= count($courses) ?> Mata Kuliah</h2>
                        <span class="summary-subtitle">Terdaftar Semester Ini</span>
                    </div>
                </div>
            </article>

            <article class="summary-card">
                <div class="summary-card-header">
                    <div class="summary-icon-box lime">🎯</div>
                    <div class="summary-title-group">
                        <h2 class="summary-main-title"><?= (int)$totalSKS ?> SKS</h2>
                        <span class="summary-subtitle">Beban Kredit Semester</span>
                    </div>
                </div>
            </article>

            <article class="summary-card">
                <div class="summary-card-header">
                    <div class="summary-icon-box peach">🗓️</div>
                    <div class="summary-title-group">
                        <h2 class="summary-main-title"><?= count($weeklySchedules) ?> Sesi Kuliah</h2>
                        <span class="summary-subtitle">Jadwal Mingguan Tetap</span>
                    </div>
                </div>
            </article>
        </section>

        <!-- Jadwal Kuliah Mingguan (Per Hari) -->
        <article class="bento-card" style="padding: 24px; margin-bottom: 24px;">
            <div class="card-header-row" style="margin-bottom: 16px;">
                <h2 class="card-heading-title">Jadwal Perkuliahan Mingguan</h2>
                <span class="card-growth-tag">Deteksi bentrok waktu aktif via ConflictService</span>
            </div>

            <?php if (empty($weeklySchedules)): ?>
                <div style="text-align:center; padding: 40px 16px;">
                    <div style="font-size: 2.5rem; margin-bottom: 12px;">📅</div>
                    <h3 class="card-heading-title" style="margin-bottom: 6px;">Belum Ada Jadwal Kuliah</h3>
                    <p style="color: var(--color-text-muted); font-size: var(--font-size-sm); max-width: 420px; margin: 0 auto 16px;">
                        Tambahkan mata kuliah terlebih dahulu, lalu jadwalkan hari dan jam perkuliahanmu.
                    </p>
                    <button type="button" class="btn-lime" onclick="document.getElementById('scheduleModal').style.display='flex';" style="cursor:pointer;">
                        + Tambah Jadwal Sekarang
                    </button>
                </div>
            <?php else: ?>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 16px;">
                    <?php for ($d = 1; $d <= 7; $d++): 
                        $schedulesOfDay = $groupedSchedules[$d] ?? [];
                        if (empty($schedulesOfDay) && $d > 5) continue; // Lewati sabtu/minggu jika kosong
                    ?>
                        <div style="background: var(--color-bg-base); border-radius: var(--radius-md); padding: 16px; border: 1px solid var(--color-border);">
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; padding-bottom:8px; border-bottom:1px solid var(--color-border);">
                                <h3 style="font-size:var(--font-size-sm); font-weight:var(--font-weight-bold); margin:0;">
                                    <?= $dayNames[$d] ?>
                                </h3>
                                <span style="font-size:var(--font-size-xs); color:var(--color-text-muted);">
                                    <?= count($schedulesOfDay) ?> Sesi
                                </span>
                            </div>

                            <?php if (empty($schedulesOfDay)): ?>
                                <p style="font-size:var(--font-size-xs); color:var(--color-text-muted); margin:0; font-style:italic;">Tidak ada perkuliahan</p>
                            <?php else: ?>
                                <div style="display:flex; flex-direction:column; gap:10px;">
                                    <?php foreach ($schedulesOfDay as $s): ?>
                                        <div style="padding:10px 12px; border-radius:var(--radius-sm); background:var(--color-bg-surface); border-left:4px solid <?= e($s['color_hex'] ?? '#3B82F6') ?>; display:flex; justify-content:space-between; align-items:center;">
                                            <div>
                                                <div style="font-weight:var(--font-weight-bold); font-size:var(--font-size-xs);">
                                                    <?= e($s['course_name']) ?> (<?= e($s['course_code']) ?>)
                                                </div>
                                                <div style="font-size:var(--font-size-xs); color:var(--color-text-muted); margin-top:2px;">
                                                    ⏰ <?= substr($s['start_time'], 0, 5) ?> - <?= substr($s['end_time'], 0, 5) ?> WIB
                                                    <?php if (!empty($s['room'])): ?>
                                                        • 📍 <?= e($s['room']) ?>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <a href="index.php?page=academic&action=delete_schedule&id=<?= (int)$s['id'] ?>" onclick="return confirm('Hapus sesi jadwal kuliah ini?')" style="color:#ef4444; text-decoration:none; font-size:1.1rem; padding:4px;" title="Hapus Sesi">&times;</a>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </article>

        <!-- Katalog Mata Kuliah -->
        <article class="bento-card" style="padding: 24px;">
            <div class="card-header-row" style="margin-bottom: 16px;">
                <h2 class="card-heading-title">Daftar Mata Kuliah Terdaftar (<?= count($courses) ?>)</h2>
                <button type="button" class="btn-lime" onclick="document.getElementById('courseModal').style.display='flex';" style="cursor:pointer; font-size:var(--font-size-xs); padding:6px 14px;">
                    + Mata Kuliah
                </button>
            </div>

            <?php if (empty($courses)): ?>
                <p style="text-align:center; color:var(--color-text-muted); font-size:var(--font-size-sm); padding:20px;">Belum ada mata kuliah yang didaftarkan.</p>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: var(--font-size-sm);">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--color-border); color: var(--color-text-muted);">
                                <th style="padding: 10px 8px;">KODE</th>
                                <th style="padding: 10px 8px;">NAMA MATA KULIAH</th>
                                <th style="padding: 10px 8px;">DOSEN PENGAMPU</th>
                                <th style="padding: 10px 8px;">SKS</th>
                                <th style="padding: 10px 8px;">WARNA</th>
                                <th style="padding: 10px 8px; text-align: right;">AKSI</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($courses as $c): ?>
                                <tr style="border-bottom: 1px solid var(--color-border);">
                                    <td style="padding: 12px 8px; font-weight: var(--font-weight-bold);"><?= e($c['course_code']) ?></td>
                                    <td style="padding: 12px 8px;"><?= e($c['course_name']) ?></td>
                                    <td style="padding: 12px 8px; color: var(--color-text-muted);"><?= e($c['lecturer'] ?? '-') ?></td>
                                    <td style="padding: 12px 8px;"><?= (int)$c['credits'] ?> SKS</td>
                                    <td style="padding: 12px 8px;">
                                        <span style="display:inline-block; width:14px; height:14px; border-radius:50%; background:<?= e($c['color_hex'] ?? '#3B82F6') ?>; vertical-align:middle;"></span>
                                    </td>
                                    <td style="padding: 12px 8px; text-align: right;">
                                        <button type="button" onclick="editCourse(<?= htmlspecialchars(json_encode($c), ENT_QUOTES, 'UTF-8') ?>)" style="background:none; border:none; color:#4f46e5; cursor:pointer; font-size:var(--font-size-xs); font-weight:var(--font-weight-medium); margin-right:8px;">Edit</button>
                                        <a href="index.php?page=academic&action=delete_course&id=<?= (int)$c['id'] ?>" onclick="return confirm('Hapus mata kuliah ini? Seluruh jadwal terkait juga akan terhapus.')" style="color:#ef4444; text-decoration:none; font-size:var(--font-size-xs); font-weight:var(--font-weight-medium);">Hapus</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </article>

        <!-- Modal: Tambah / Edit Mata Kuliah -->
        <div id="courseModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center; padding:16px;">
            <div class="bento-card" style="width:100%; max-width:480px; padding:28px; background:var(--color-bg-surface); position:relative;">
                <div class="card-header-row" style="margin-bottom: 20px;">
                    <h3 class="card-heading-title" id="courseModalTitle">Tambah Mata Kuliah</h3>
                    <button type="button" onclick="document.getElementById('courseModal').style.display='none';" style="background:none; border:none; font-size:1.4rem; cursor:pointer;">&times;</button>
                </div>

                <form method="POST" id="courseForm" action="index.php?page=academic&action=store_course">
                    <input type="hidden" name="id" id="courseId" value="">

                    <div style="display:grid; grid-template-columns: 1fr 2fr; gap:12px; margin-bottom: 14px;">
                        <div>
                            <label style="display:block; font-size:var(--font-size-xs); font-weight:var(--font-weight-medium); margin-bottom:4px;">Kode Matkul *</label>
                            <input type="text" name="course_code" id="courseCode" required placeholder="IF2101" style="width:100%; padding:9px 12px; border-radius:var(--radius-sm); border:1px solid var(--color-border); font-size:var(--font-size-sm); background:var(--color-bg-base);">
                        </div>
                        <div>
                            <label style="display:block; font-size:var(--font-size-xs); font-weight:var(--font-weight-medium); margin-bottom:4px;">Nama Mata Kuliah *</label>
                            <input type="text" name="course_name" id="courseName" required placeholder="Pemrograman Web Modern" style="width:100%; padding:9px 12px; border-radius:var(--radius-sm); border:1px solid var(--color-border); font-size:var(--font-size-sm); background:var(--color-bg-base);">
                        </div>
                    </div>

                    <div style="margin-bottom: 14px;">
                        <label style="display:block; font-size:var(--font-size-xs); font-weight:var(--font-weight-medium); margin-bottom:4px;">Dosen Pengampu</label>
                        <input type="text" name="lecturer" id="courseLecturer" placeholder="Vera Irma Delianti, M.Pd.T." style="width:100%; padding:9px 12px; border-radius:var(--radius-sm); border:1px solid var(--color-border); font-size:var(--font-size-sm); background:var(--color-bg-base);">
                    </div>

                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px; margin-bottom: 20px;">
                        <div>
                            <label style="display:block; font-size:var(--font-size-xs); font-weight:var(--font-weight-medium); margin-bottom:4px;">Beban SKS</label>
                            <input type="number" name="credits" id="courseCredits" min="1" max="6" value="3" style="width:100%; padding:9px 12px; border-radius:var(--radius-sm); border:1px solid var(--color-border); font-size:var(--font-size-sm); background:var(--color-bg-base);">
                        </div>
                        <div>
                            <label style="display:block; font-size:var(--font-size-xs); font-weight:var(--font-weight-medium); margin-bottom:4px;">Warna Label</label>
                            <input type="color" name="color_hex" id="courseColor" value="#3B82F6" style="width:100%; height:40px; padding:2px; border-radius:var(--radius-sm); border:1px solid var(--color-border); cursor:pointer;">
                        </div>
                    </div>

                    <div style="display:flex; justify-content:flex-end; gap:8px;">
                        <button type="button" onclick="document.getElementById('courseModal').style.display='none';" class="filter-pill" style="cursor:pointer;">Batal</button>
                        <button type="submit" class="btn-lime" id="courseSubmitBtn" style="cursor:pointer;">Simpan Mata Kuliah</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal: Tambah Jadwal Perkuliahan -->
        <div id="scheduleModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center; padding:16px;">
            <div class="bento-card" style="width:100%; max-width:480px; padding:28px; background:var(--color-bg-surface); position:relative;">
                <div class="card-header-row" style="margin-bottom: 20px;">
                    <h3 class="card-heading-title">Tambah Jadwal Perkuliahan</h3>
                    <button type="button" onclick="document.getElementById('scheduleModal').style.display='none';" style="background:none; border:none; font-size:1.4rem; cursor:pointer;">&times;</button>
                </div>

                <form method="POST" action="index.php?page=academic&action=store_schedule">
                    <div style="margin-bottom: 14px;">
                        <label style="display:block; font-size:var(--font-size-xs); font-weight:var(--font-weight-medium); margin-bottom:4px;">Pilih Mata Kuliah *</label>
                        <select name="course_id" required style="width:100%; padding:9px 12px; border-radius:var(--radius-sm); border:1px solid var(--color-border); font-size:var(--font-size-sm); background:var(--color-bg-base);">
                            <option value="">-- Pilih Mata Kuliah --</option>
                            <?php foreach ($courses as $c): ?>
                                <option value="<?= (int)$c['id'] ?>"><?= e($c['course_code']) ?> - <?= e($c['course_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px; margin-bottom: 14px;">
                        <div>
                            <label style="display:block; font-size:var(--font-size-xs); font-weight:var(--font-weight-medium); margin-bottom:4px;">Hari Perkuliahan *</label>
                            <select name="day_of_week" required style="width:100%; padding:9px 12px; border-radius:var(--radius-sm); border:1px solid var(--color-border); font-size:var(--font-size-sm); background:var(--color-bg-base);">
                                <option value="1">Senin</option>
                                <option value="2">Selasa</option>
                                <option value="3">Rabu</option>
                                <option value="4">Kamis</option>
                                <option value="5">Jumat</option>
                                <option value="6">Sabtu</option>
                                <option value="7">Minggu</option>
                            </select>
                        </div>
                        <div>
                            <label style="display:block; font-size:var(--font-size-xs); font-weight:var(--font-weight-medium); margin-bottom:4px;">Ruangan / Lab</label>
                            <input type="text" name="room" placeholder="Lab Komputer 3" style="width:100%; padding:9px 12px; border-radius:var(--radius-sm); border:1px solid var(--color-border); font-size:var(--font-size-sm); background:var(--color-bg-base);">
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px; margin-bottom: 20px;">
                        <div>
                            <label style="display:block; font-size:var(--font-size-xs); font-weight:var(--font-weight-medium); margin-bottom:4px;">Jam Mulai *</label>
                            <input type="time" name="start_time" required value="08:00" style="width:100%; padding:9px 12px; border-radius:var(--radius-sm); border:1px solid var(--color-border); font-size:var(--font-size-sm); background:var(--color-bg-base);">
                        </div>
                        <div>
                            <label style="display:block; font-size:var(--font-size-xs); font-weight:var(--font-weight-medium); margin-bottom:4px;">Jam Selesai *</label>
                            <input type="time" name="end_time" required value="10:00" style="width:100%; padding:9px 12px; border-radius:var(--radius-sm); border:1px solid var(--color-border); font-size:var(--font-size-sm); background:var(--color-bg-base);">
                        </div>
                    </div>

                    <div style="font-size:var(--font-size-xs); color:var(--color-text-muted); background:var(--color-bg-base); padding:10px 12px; border-radius:var(--radius-sm); margin-bottom:16px;">
                        🛡️ <strong>Pendeteksi Bentrok Otomatis:</strong> Jadwal akan diperiksa terhadap jadwal kuliah lain dan agenda kegiatan sebelum disimpan.
                    </div>

                    <div style="display:flex; justify-content:flex-end; gap:8px;">
                        <button type="button" onclick="document.getElementById('scheduleModal').style.display='none';" class="filter-pill" style="cursor:pointer;">Batal</button>
                        <button type="submit" class="btn-lime" style="cursor:pointer;">Simpan Jadwal Kuliah</button>
                    </div>
                </form>
            </div>
        </div>

        <script>
        function editCourse(course) {
            document.getElementById('courseModalTitle').innerText = 'Edit Mata Kuliah';
            document.getElementById('courseForm').action = 'index.php?page=academic&action=update_course';
            document.getElementById('courseId').value = course.id;
            document.getElementById('courseCode').value = course.course_code;
            document.getElementById('courseName').value = course.course_name;
            document.getElementById('courseLecturer').value = course.lecturer || '';
            document.getElementById('courseCredits').value = course.credits || 3;
            document.getElementById('courseColor').value = course.color_hex || '#3B82F6';
            document.getElementById('courseSubmitBtn').innerText = 'Perbarui Mata Kuliah';
            document.getElementById('courseModal').style.display = 'flex';
        }
        </script>
        <?php
        $contentView = ob_get_clean();
        require dirname(__DIR__, 2) . '/views/layouts/main.php';
    }
}
