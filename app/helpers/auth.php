<?php
// app/helpers/auth.php
// Helper autentikasi dan manajemen sesi pengguna

/**
 * Memulai session PHP secara aman jika belum berjalan
 */
function startSessionSafe(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        // Keamanan cookie session
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        session_start();
    }
}

/**
 * Memeriksa apakah pengguna saat ini sedang login
 * 
 * @return bool
 */
function isLoggedIn(): bool
{
    startSessionSafe();
    return !empty($_SESSION['user_id']);
}

/**
 * Mengharuskan pengguna login sebelum mengakses halaman
 * Melakukan redirect ke halaman login jika sesi belum aktif
 */
function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: index.php?page=login');
        exit;
    }
}

/**
 * Mendapatkan ID pengguna aktif saat ini
 * 
 * @return int|null
 */
function currentUserId(): ?int
{
    startSessionSafe();
    return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
}

/**
 * Mendapatkan data ringkas pengguna aktif dari sesi
 * 
 * @return array
 */
function currentUser(): array
{
    startSessionSafe();
    return $_SESSION['user'] ?? [
        'id'       => currentUserId(),
        'name'     => $_SESSION['user_name'] ?? 'Mahasiswa',
        'email'    => $_SESSION['user_email'] ?? '',
        'semester' => $_SESSION['user_semester'] ?? 1,
    ];
}

/**
 * Menghancurkan session dan logout pengguna
 */
function logoutUser(): void
{
    startSessionSafe();
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    session_destroy();
    header('Location: index.php?page=login');
    exit;
}
