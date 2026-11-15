<?php
/**
 * CampusOS — Front Controller & Application Entry Point
 * Routing berbasis query string: index.php?page=<modul>&action=<aksi>
 */

require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/helpers/view.php';

// Inisialisasi Sesi Pengguna
startSessionSafe();

// Baca parameter rute
$page   = $_GET['page'] ?? 'dashboard';
$action = $_GET['action'] ?? 'index';

// ========================================================
// 1. Rute Publik (Authentication Flow)
// ========================================================
if ($page === 'login') {
    if (isLoggedIn()) {
        header('Location: index.php?page=dashboard');
        exit;
    }

    $errorMessage = null;
    $submittedEmail = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email    = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $submittedEmail = $email;

        if (empty($email) || empty($password)) {
            $errorMessage = 'Silakan isi alamat email dan kata sandi.';
        } else {
            // Verifikasi ke basis data via PDO
            $pdo = getDBConnection();
            $authenticated = false;

            if ($pdo) {
                try {
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
                    $stmt->execute([':email' => $email]);
                    $user = $stmt->fetch();

                    if ($user && password_verify($password, $user['password_hash'])) {
                        $authenticated = true;
                        $_SESSION['user_id']       = (int) $user['id'];
                        $_SESSION['user_name']     = $user['name'];
                        $_SESSION['user_email']    = $user['email'];
                        $_SESSION['user_semester'] = (int) $user['semester'];
                    }
                } catch (\PDOException $e) {
                    error_log("Login Query Error: " . $e->getMessage());
                }
            }

            // Fallback akun demo jika database belum di-import
            if (!$authenticated && $email === 'mahasiswa@campusos.test' && $password === 'password123') {
                $authenticated = true;
                $_SESSION['user_id']       = 1;
                $_SESSION['user_name']     = 'Demo Mahasiswa';
                $_SESSION['user_email']    = 'mahasiswa@campusos.test';
                $_SESSION['user_semester'] = 3;
            }

            if ($authenticated) {
                session_regenerate_id(true);
                setFlash('success', 'Selamat datang kembali di CampusOS!');
                header('Location: index.php?page=dashboard');
                exit;
            } else {
                $errorMessage = 'Email atau kata sandi tidak sesuai.';
            }
        }
    }

    renderView('auth/login', [
        'errorMessage'   => $errorMessage,
        'submittedEmail' => $submittedEmail,
    ], null);
    exit;
}

if ($page === 'logout') {
    logoutUser();
    exit;
}

// ========================================================
// 2. Rute Terproteksi (Wajib Login)
// ========================================================
requireLogin();

switch ($page) {
    case 'dashboard':
        $userId = currentUserId();
        $pdo = getDBConnection();

        $todaySchedules = [];
        $urgentTasks = [];
        $walletSummary = [
            'monthlyBudget'   => 1500000.0,
            'totalExpense'    => 125000.0,
            'remainingBudget' => 1375000.0,
            'percentUsed'     => 8.3,
            'statusBadge'     => 'success',
        ];

        // Jika koneksi DB aktif, baca data nyata
        if ($pdo && $userId) {
            try {
                // Jadwal hari ini (hari 1-7, PHP date('N') return 1=Senin s/d 7=Minggu)
                $dayOfWeek = (int) date('N');
                $stmtSched = $pdo->prepare("
                    SELECT cs.*, c.course_code, c.course_name, c.color_hex
                    FROM class_schedules cs
                    JOIN courses c ON cs.course_id = c.id
                    WHERE cs.user_id = :user_id AND cs.day_of_week = :day_of_week
                    ORDER BY cs.start_time ASC
                ");
                $stmtSched->execute([':user_id' => $userId, ':day_of_week' => $dayOfWeek]);
                $todaySchedules = $stmtSched->fetchAll() ?: [];

                // Tugas mendesak (belum selesai, diurutkan deadline terdekat)
                $stmtTask = $pdo->prepare("
                    SELECT * FROM tasks
                    WHERE user_id = :user_id AND status != 'done'
                    ORDER BY deadline_date ASC, deadline_time ASC
                    LIMIT 3
                ");
                $stmtTask->execute([':user_id' => $userId]);
                $urgentTasks = $stmtTask->fetchAll() ?: [];

                // Pengaturan budget dan transaksi
                $stmtBudget = $pdo->prepare("SELECT * FROM budget_settings WHERE user_id = :user_id LIMIT 1");
                $stmtBudget->execute([':user_id' => $userId]);
                $budgetRow = $stmtBudget->fetch();
                $monthlyBudget = $budgetRow ? (float) $budgetRow['monthly_budget'] : 1500000.0;

                $stmtExpense = $pdo->prepare("
                    SELECT SUM(amount) as total 
                    FROM transactions 
                    WHERE user_id = :user_id AND type = 'expense'
                ");
                $stmtExpense->execute([':user_id' => $userId]);
                $expenseRow = $stmtExpense->fetch();
                $totalExpense = $expenseRow && $expenseRow['total'] ? (float) $expenseRow['total'] : 0.0;

                $remaining = max(0, $monthlyBudget - $totalExpense);
                $percent = $monthlyBudget > 0 ? ($totalExpense / $monthlyBudget) * 100 : 0;
                $statusBadge = $percent > 90 ? 'danger' : ($percent > 70 ? 'warning' : 'success');

                $walletSummary = [
                    'monthlyBudget'   => $monthlyBudget,
                    'totalExpense'    => $totalExpense,
                    'remainingBudget' => $remaining,
                    'percentUsed'     => $percent,
                    'statusBadge'     => $statusBadge,
                ];
            } catch (\PDOException $e) {
                error_log("Dashboard Data Fetch Error: " . $e->getMessage());
            }
        }

        renderView('dashboard/index', [
            'pageTitle'      => 'Dashboard Overview — CampusOS',
            'user'           => currentUser(),
            'todaySchedules' => $todaySchedules,
            'urgentTasks'    => $urgentTasks,
            'walletSummary'  => $walletSummary,
        ]);
        break;

    // Modul yang akan di-scaffold pada tahap berikutnya
    case 'academic':
    case 'calendar':
    case 'tasks':
    case 'planner':
    case 'finance':
        $titles = [
            'academic' => 'Manajemen Akademik & Jadwal Kuliah',
            'calendar' => 'Kalender Kegiatan & Deteksi Konflik',
            'tasks'    => 'Manajemen Tugas & Prioritas',
            'planner'  => 'Smart Planner (Rekomendasi Belajar)',
            'finance'  => 'Pengelolaan Keuangan Pribadi Mahasiswa',
        ];

        $moduleName = $titles[$page] ?? ucfirst($page);

        // Placeholder view untuk modul yang sedang dalam tahap pengembangan
        $placeholderContent = "
        <div class='page-header'>
            <h1 class='page-title'>" . e($moduleName) . "</h1>
            <p class='page-subtitle'>Modul ini siap dikembangkan pada tahapan berikutnya sesuai spesifikasi produk.</p>
        </div>
        <div class='card'>
            <div class='card-body'>
                <div class='empty-state'>
                    <div class='empty-state-icon'>🛠️</div>
                    <h3 class='empty-state-title'>Modul Segera Hadir</h3>
                    <p class='empty-state-desc'>Struktur file, model PDO, controller, dan antarmuka untuk modul <strong>" . e($moduleName) . "</strong> akan diimplementasikan pada langkah pengerjaan modul fitur.</p>
                    <a href='index.php?page=dashboard' class='btn btn-primary'>Kembali ke Dashboard</a>
                </div>
            </div>
        </div>
        ";

        extract(['user' => currentUser(), 'pageTitle' => $moduleName . ' — CampusOS']);
        $contentView = $placeholderContent;
        require dirname(__DIR__) . '/views/layouts/main.php';
        break;

    default:
        http_response_code(404);
        $placeholderContent = "
        <div class='card'>
            <div class='card-body'>
                <div class='empty-state'>
                    <div class='empty-state-icon'>🔍</div>
                    <h3 class='empty-state-title'>404 — Halaman Tidak Ditemukan</h3>
                    <p class='empty-state-desc'>Halaman yang Anda tuju tidak tersedia di CampusOS.</p>
                    <a href='index.php?page=dashboard' class='btn btn-primary'>Kembali ke Dashboard</a>
                </div>
            </div>
        </div>";
        extract(['user' => currentUser(), 'pageTitle' => '404 Not Found — CampusOS']);
        $contentView = $placeholderContent;
        require dirname(__DIR__) . '/views/layouts/main.php';
        break;
}
