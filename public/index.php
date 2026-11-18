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

// Baca parameter rute (mendukung GET dan POST fallback)
$page   = $_GET['page'] ?? ($_POST['page'] ?? 'dashboard');
$action = $_GET['action'] ?? ($_POST['action'] ?? 'index');

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
    // Modul Dashboard Utama (DashboardController)
    case 'dashboard':
        require_once __DIR__ . '/../app/controllers/DashboardController.php';
        $dashboardController = new DashboardController();
        $dashboardController->index();
        break;

    // Modul Akademik & Jadwal Kuliah (AcademicController)
    case 'academic':
        require_once __DIR__ . '/../app/controllers/AcademicController.php';
        $academicController = new AcademicController();
        $academicController->handleRequest($action);
        break;

    // Modul Kalender Kegiatan & Deteksi Konflik (CalendarController)
    case 'calendar':
        require_once __DIR__ . '/../app/controllers/CalendarController.php';
        $calendarController = new CalendarController();
        $calendarController->handleRequest($action);
        break;

    // Modul Tugas & Prioritas (TaskController)
    case 'tasks':
        require_once __DIR__ . '/../app/controllers/TaskController.php';
        $taskController = new TaskController();
        $taskController->handleRequest($action);
        break;

    // Modul Smart Planner (Rekomendasi Belajar Mandiri Cerdas)
    case 'planner':
        require_once __DIR__ . '/../app/controllers/TaskController.php';
        $taskController = new TaskController();
        $taskController->planner();
        break;

    // Modul Pengelolaan Keuangan Mahasiswa (FinanceController)
    case 'finance':
        require_once __DIR__ . '/../app/controllers/FinanceController.php';
        $financeController = new FinanceController();
        $financeController->handleRequest($action);
        break;

    default:
        http_response_code(404);
        $placeholderContent = "
        <div class='canvas-top-bar'>
            <div class='canvas-header'>
                <h1 class='greeting-title'>404 — Halaman Tidak Ditemukan</h1>
                <p class='greeting-subtitle'>Halaman yang Anda tuju tidak tersedia di CampusOS.</p>
            </div>
        </div>
        <article class='bento-card' style='margin-top: 20px; padding: 48px 24px; text-align: center;'>
            <div style='font-size: 3rem; margin-bottom: 16px;'>🔍</div>
            <h2 class='card-heading-title' style='margin-bottom: 8px;'>Halaman Tidak Ditemukan</h2>
            <p style='color: var(--color-text-muted); font-size: var(--font-size-sm); max-width: 420px; margin: 0 auto 24px;'>
                Tautan yang Anda ikuti salah atau halaman telah dipindahkan.
            </p>
            <a href='index.php?page=dashboard' class='btn-lime' style='text-decoration: none;'>
                Kembali ke Dashboard
            </a>
        </article>";
        extract(['user' => currentUser(), 'pageTitle' => '404 Not Found — CampusOS']);
        $contentView = $placeholderContent;
        require dirname(__DIR__) . '/views/layouts/main.php';
        break;
}

