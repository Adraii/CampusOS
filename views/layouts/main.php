<?php
/**
 * views/layouts/main.php
 * Layout Utama Aplikasi CampusOS (Hybrid SSR)
 */

$currentPage = $_GET['page'] ?? 'dashboard';
$flash = getFlash();
$user = currentUser();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'CampusOS — Web-based Student Assistant') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/tokens.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    <link rel="stylesheet" href="assets/css/components.css">
</head>
<body>
    <div class="app-container">
        <!-- Sidebar Kiri -->
        <aside class="app-sidebar">
            <div class="sidebar-header">
                <a href="index.php?page=dashboard" class="brand-logo">
                    <span>🎓 CampusOS</span>
                    <span class="brand-badge">MVP</span>
                </a>
            </div>

            <nav class="sidebar-nav">
                <span class="nav-section-title">Menu Utama</span>
                
                <a href="index.php?page=dashboard" class="nav-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>">
                    <span class="nav-icon">📊</span>
                    <span>Dashboard</span>
                </a>

                <a href="index.php?page=academic" class="nav-link <?= $currentPage === 'academic' ? 'active' : '' ?>">
                    <span class="nav-icon">📚</span>
                    <span>Akademik & Jadwal</span>
                </a>

                <a href="index.php?page=calendar" class="nav-link <?= $currentPage === 'calendar' ? 'active' : '' ?>">
                    <span class="nav-icon">📅</span>
                    <span>Kalender Kegiatan</span>
                </a>

                <a href="index.php?page=tasks" class="nav-link <?= $currentPage === 'tasks' ? 'active' : '' ?>">
                    <span class="nav-icon">✅</span>
                    <span>Daftar Tugas</span>
                </a>

                <span class="nav-section-title">Sistem Cerdas</span>

                <a href="index.php?page=planner" class="nav-link <?= $currentPage === 'planner' ? 'active' : '' ?>">
                    <span class="nav-icon">⚡</span>
                    <span>Smart Planner</span>
                </a>

                <span class="nav-section-title">Finansial</span>

                <a href="index.php?page=finance" class="nav-link <?= $currentPage === 'finance' ? 'active' : '' ?>">
                    <span class="nav-icon">💰</span>
                    <span>Keuangan Pribadi</span>
                </a>

                <span class="nav-section-title">Akun</span>

                <a href="index.php?page=logout" class="nav-link" onclick="return confirm('Apakah Anda yakin ingin keluar?');">
                    <span class="nav-icon">🚪</span>
                    <span>Logout</span>
                </a>
            </nav>

            <div class="sidebar-footer">
                <p>CampusOS v1.0 Native</p>
                <p style="margin-top: 4px;">Pemrograman Web 2026</p>
            </div>
        </aside>

        <!-- Area Konten Utama -->
        <main class="app-main">
            <!-- Topbar Header -->
            <header class="app-header">
                <div>
                    <span style="font-size: var(--text-sm); color: var(--text-secondary);">
                        📅 <?= date('l, d F Y') ?>
                    </span>
                </div>

                <div class="header-user-info">
                    <div class="user-avatar">
                        <?= strtoupper(substr($user['name'] ?? 'M', 0, 1)) ?>
                    </div>
                    <div>
                        <div style="font-size: var(--text-sm); font-weight: var(--font-semibold);">
                            <?= e($user['name'] ?? 'Mahasiswa') ?>
                        </div>
                        <div style="font-size: var(--text-xs); color: var(--text-secondary);">
                            Semester <?= e($user['semester'] ?? 1) ?>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Body Konten -->
            <div class="content-body">
                <!-- Notifikasi Flash Alert jika ada -->
                <?php if ($flash): ?>
                    <div class="alert alert-<?= e($flash['type'] ?? 'info') ?>">
                        <span><?= $flash['type'] === 'success' ? '✓' : 'ℹ' ?></span>
                        <span><?= e($flash['message']) ?></span>
                    </div>
                <?php endif; ?>

                <!-- Konten Halaman Spesifik -->
                <?= $contentView ?? '' ?>
            </div>
        </main>
    </div>

    <script src="assets/js/ui.js"></script>
</body>
</html>

