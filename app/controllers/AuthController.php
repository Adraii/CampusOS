<?php
// app/controllers/AuthController.php
// Controller Otentikasi dan Manajemen Sesi Pengguna CampusOS
// Mengikuti standar PDO Prepared Statements dan keamanan sesi

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/view.php';

class AuthController
{
    private ?PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? getDBConnection();
    }

    /**
     * Menangani alur masuk akun (login)
     */
    public function login(): void
    {
        startSessionSafe();

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
                $authenticated = false;
                $userRecord = null;

                // 1. Verifikasi kredensial ke database via PDO Prepared Statements
                if ($this->db) {
                    try {
                        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
                        $stmt->execute([':email' => $email]);
                        $user = $stmt->fetch(PDO::FETCH_ASSOC);

                        if ($user && password_verify($password, $user['password_hash'])) {
                            $authenticated = true;
                            $userRecord = [
                                'id'       => (int) $user['id'],
                                'name'     => $user['name'],
                                'email'    => $user['email'],
                                'semester' => (int) ($user['semester'] ?? 1),
                            ];
                        }
                    } catch (\PDOException $e) {
                        error_log("AuthController::login Error: " . $e->getMessage());
                    }
                }

                // 2. Fallback kredensial demo mahasiswa jika basis data belum terisi
                if (!$authenticated && $email === 'mahasiswa@campusos.test' && $password === 'password123') {
                    $authenticated = true;
                    $userRecord = [
                        'id'       => 1,
                        'name'     => 'Demo Mahasiswa',
                        'email'    => 'mahasiswa@campusos.test',
                        'semester' => 3,
                    ];
                }

                // 3. Jika otentikasi berhasil, inisialisasi sesi pengguna
                if ($authenticated && $userRecord) {
                    session_regenerate_id(true);
                    $_SESSION['user_id']       = $userRecord['id'];
                    $_SESSION['user_name']     = $userRecord['name'];
                    $_SESSION['user_email']    = $userRecord['email'];
                    $_SESSION['user_semester'] = $userRecord['semester'];
                    $_SESSION['user']          = $userRecord;

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

    /**
     * Menangani proses keluar akun (logout)
     */
    public function logout(): void
    {
        logoutUser();
        exit;
    }
}
