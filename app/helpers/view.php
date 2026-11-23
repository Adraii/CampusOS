<?php
// app/helpers/view.php
// Helper rendering template antarmuka dan sanitasi output

/**
 * Sanitasi string untuk mencegah kerentanan Cross-Site Scripting (XSS)
 * 
 * @param mixed $string
 * @return string
 */
function e(mixed $string): string
{
    return htmlspecialchars((string) ($string ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Render template view dengan layout modular (Hybrid SSR)
 * 
 * @param string $viewPath Path relatif terhadap direktori views/ (tanpa ekstensi .php)
 * @param array $data Data variabel yang diekstraksi ke view
 * @param string|null $layout Path layout pembungkus di views/ (default: 'layouts/main')
 */
function renderView(string $viewPath, array $data = [], ?string $layout = 'layouts/main'): void
{
    // Ekstraksi data agar dapat langsung dipanggil sebagai variabel di dalam view ($tasks, dsb)
    extract($data, EXTR_SKIP);

    $baseViewDir = dirname(__DIR__, 2) . '/views/';
    $viewFile = $baseViewDir . ltrim($viewPath, '/') . '.php';

    if (!file_exists($viewFile)) {
        http_response_code(500);
        echo "View Error: File view tidak ditemukan di: " . e($viewFile);
        return;
    }

    // Tangkap output view ke dalam buffer
    ob_start();
    require $viewFile;
    $contentView = ob_get_clean();

    // Jika tanpa layout, cetak langsung konten view
    if ($layout === null) {
        echo $contentView;
        return;
    }

    $layoutFile = $baseViewDir . ltrim($layout, '/') . '.php';
    if (!file_exists($layoutFile)) {
        // Fallback jika layout tidak ada
        echo $contentView;
        return;
    }

    // Render layout utama dengan menyuntikkan $contentView
    require $layoutFile;
}

/**
 * Set pesan flash notifikasi ke session
 * 
 * @param string $type Tipe status: 'success', 'warning', 'danger', 'info'
 * @param string $message Pesan notifikasi
 */
function setFlash(string $type, string $message): void
{
    startSessionSafe();
    $_SESSION['flash'] = [
        'type'    => $type,
        'message' => $message,
    ];
}

/**
 * Ambil dan bersihkan pesan flash dari session
 * 
 * @return array|null
 */
function getFlash(): ?array
{
    startSessionSafe();
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

