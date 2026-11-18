<?php
/**
 * views/layouts/main.php
 * Master Layout: Technical High-Contrast Dark Mode Bento Shell (Hybrid SSR)
 * Sinkronisasi penuh dengan FigCraft Master (docs/CampusOS-Dashboard.figcraft)
 */

$currentPage = $_GET['page'] ?? 'dashboard';
$flash = getFlash();
$user = currentUser();

$userInitials = '';
$nameParts = explode(' ', trim($user['name'] ?? 'Mahasiswa'));
foreach ($nameParts as $part) {
    if (!empty($part)) {
        $userInitials .= strtoupper(substr($part, 0, 1));
    }
    if (strlen($userInitials) >= 2) break;
}
if (empty($userInitials)) {
    $userInitials = 'M';
}

$pageHeadings = [
    'dashboard' => [
        'title'    => 'Selamat datang kembali, ' . ($user['name'] ?? 'Mahasiswa'),
        'subtitle' => date('l, d F Y') . ' • Ringkasan perkuliahan, tugas, dan keuanganmu hari ini',
    ],
    'academic'  => [
        'title'    => 'Jadwal Kuliah & Mata Kuliah',
        'subtitle' => 'Kelola daftar mata kuliah aktif dan jadwal rutin mingguanmu',
    ],
    'calendar'  => [
        'title'    => 'Kalender Kegiatan & Deteksi Bentrok',
        'subtitle' => 'Pantau agenda organisasi dan cegah jadwal bertabrakan secara otomatis',
    ],
    'tasks'     => [
        'title'    => 'Tugas & Prioritas Kuliah',
        'subtitle' => 'Pantau deadline, atur estimasi durasi, dan selesaikan tugas lebih terstruktur',
    ],
    'planner'   => [
        'title'    => 'Smart Planner (Rekomendasi Belajar)',
        'subtitle' => 'Mesin rekomendasi deterministik berbasis skor urgensi tanpa menimpa jadwal kuliah tetap',
    ],
    'finance'   => [
        'title'    => 'Keuangan Pribadi Mahasiswa',
        'subtitle' => 'Catat arus kas harian dan jaga batas anggaran bulanan tetap aman',
    ],
];

$headerInfo = $pageHeadings[$currentPage] ?? [
    'title'    => 'CampusOS',
    'subtitle' => 'Web-based Student Assistant',
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="CampusOS — Asisten Mahasiswa Berbasis Web dengan Manajemen Akademik, Smart Planner, dan Keuangan Pribadi.">
  <title><?= e($pageTitle ?? 'CampusOS — Web-based Student Assistant') ?></title>

  <!-- Google Fonts: Plus Jakarta Sans & Inter -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">

  <!-- Modular High-Contrast Dark & Light Mode Stylesheets -->
  <link rel="stylesheet" href="assets/css/tokens.css">
  <link rel="stylesheet" href="assets/css/layout.css">
  <link rel="stylesheet" href="assets/css/components.css">

  <!-- Anti-flicker Theme Initialization -->
  <script>
    (function() {
      try {
        var savedTheme = localStorage.getItem('campusos-theme');
        var theme = savedTheme ? savedTheme : 'dark';
        document.documentElement.setAttribute('data-theme', theme);
      } catch (e) {
        document.documentElement.setAttribute('data-theme', 'dark');
      }
    })();
  </script>
</head>
<body>
  <!-- Mobile Drawer Overlay -->
  <div class="sidebar-backdrop" id="sidebarBackdrop" aria-hidden="true"></div>

  <div class="app-shell">
    <!-- ====================================================================
         1. SIDEBAR NAVIGASI (#111317)
         ==================================================================== -->
    <aside class="sidebar" id="appSidebar" aria-label="Navigasi Utama">
      <div class="sidebar-header">
        <div class="sidebar-logo-mark" aria-hidden="true">C</div>
        <div class="sidebar-brand-group">
          <span class="sidebar-brand-name">CampusOS</span>
          <span class="sidebar-brand-sub">Student Assistant</span>
        </div>
      </div>

      <nav class="sidebar-nav" aria-label="Menu Mahasiswa">
        <span class="nav-section-label">Menu Utama</span>

        <a href="index.php?page=dashboard" class="nav-link <?= $currentPage === 'dashboard' ? 'is-active' : '' ?>" <?= $currentPage === 'dashboard' ? 'aria-current="page"' : '' ?>>
          <svg class="ui-icon" viewBox="0 0 24 24" aria-hidden="true">
            <rect x="3" y="3" width="7" height="9" rx="1"></rect>
            <rect x="14" y="3" width="7" height="5" rx="1"></rect>
            <rect x="14" y="12" width="7" height="9" rx="1"></rect>
            <rect x="3" y="16" width="7" height="5" rx="1"></rect>
          </svg>
          <span>Dashboard</span>
        </a>

        <a href="index.php?page=academic" class="nav-link <?= $currentPage === 'academic' ? 'is-active' : '' ?>" <?= $currentPage === 'academic' ? 'aria-current="page"' : '' ?>>
          <svg class="ui-icon" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
          </svg>
          <span>Jadwal Kuliah</span>
        </a>

        <a href="index.php?page=calendar" class="nav-link <?= $currentPage === 'calendar' ? 'is-active' : '' ?>" <?= $currentPage === 'calendar' ? 'aria-current="page"' : '' ?>>
          <svg class="ui-icon" viewBox="0 0 24 24" aria-hidden="true">
            <rect x="3" y="4" width="18" height="18" rx="2"></rect>
            <line x1="16" y1="2" x2="16" y2="6"></line>
            <line x1="8" y1="2" x2="8" y2="6"></line>
            <line x1="3" y1="10" x2="21" y2="10"></line>
          </svg>
          <span>Kalender Kegiatan</span>
        </a>

        <span class="nav-section-label">Produktivitas</span>

        <a href="index.php?page=tasks" class="nav-link <?= $currentPage === 'tasks' ? 'is-active' : '' ?>" <?= $currentPage === 'tasks' ? 'aria-current="page"' : '' ?>>
          <svg class="ui-icon" viewBox="0 0 24 24" aria-hidden="true">
            <polyline points="9 11 12 14 22 4"></polyline>
            <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
          </svg>
          <span>Tugas &amp; Prioritas</span>
        </a>

        <a href="index.php?page=planner" class="nav-link <?= $currentPage === 'planner' ? 'is-active' : '' ?>" <?= $currentPage === 'planner' ? 'aria-current="page"' : '' ?>>
          <svg class="ui-icon" viewBox="0 0 24 24" aria-hidden="true">
            <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
          </svg>
          <span>Smart Planner</span>
        </a>

        <span class="nav-section-label">Finansial</span>

        <a href="index.php?page=finance" class="nav-link <?= $currentPage === 'finance' ? 'is-active' : '' ?>" <?= $currentPage === 'finance' ? 'aria-current="page"' : '' ?>>
          <svg class="ui-icon" viewBox="0 0 24 24" aria-hidden="true">
            <rect x="2" y="4" width="20" height="16" rx="2"></rect>
            <line x1="2" y1="10" x2="22" y2="10"></line>
          </svg>
          <span>Keuangan Pribadi</span>
        </a>
      </nav>

      <!-- Footer Profil Mahasiswa & Tombol Logout -->
      <div class="sidebar-footer">
        <div class="sidebar-user-card">
          <div class="user-avatar-badge" aria-hidden="true"><?= e($userInitials) ?></div>
          <div class="sidebar-user-meta">
            <span class="sidebar-user-name"><?= e($user['name'] ?? 'Mahasiswa') ?></span>
            <span class="sidebar-user-sem">Semester <?= e($user['semester'] ?? 1) ?> • Aktif</span>
          </div>
        </div>

        <a href="index.php?page=logout" class="btn-lime btn-block" onclick="return confirm('Apakah Anda yakin ingin keluar dari CampusOS?');">
          <svg class="ui-icon ui-icon-xs" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
            <polyline points="16 17 21 12 16 7"></polyline>
            <line x1="21" y1="12" x2="9" y2="12"></line>
          </svg>
          <span>Keluar Akun</span>
        </a>
      </div>
    </aside>

    <!-- ====================================================================
         2. MAIN WORKSPACE (#0B0D0E)
         ==================================================================== -->
    <div class="main-workspace">
      <!-- Sticky Top Header Bar dengan Aksi Cepat -->
      <header class="app-topbar">
        <div class="topbar-left">
          <button type="button" class="mobile-nav-toggle" id="mobileNavToggle" aria-label="Buka Menu Navigasi">
            <svg class="ui-icon" viewBox="0 0 24 24" aria-hidden="true">
              <line x1="3" y1="12" x2="21" y2="12"></line>
              <line x1="3" y1="6" x2="21" y2="6"></line>
              <line x1="3" y1="18" x2="21" y2="18"></line>
            </svg>
          </button>

          <div class="topbar-heading">
            <h1 class="topbar-title"><?= e($headerInfo['title']) ?></h1>
            <p class="topbar-subtitle"><?= e($headerInfo['subtitle']) ?></p>
          </div>
        </div>

        <div class="topbar-actions">
          <button type="button" class="theme-toggle-btn" id="themeToggleBtn" aria-label="Ganti Tema (Gelap/Terang)" title="Ganti Tema">
            <svg class="ui-icon theme-icon-sun" viewBox="0 0 24 24" aria-hidden="true">
              <circle cx="12" cy="12" r="5"></circle>
              <line x1="12" y1="1" x2="12" y2="3"></line>
              <line x1="12" y1="21" x2="12" y2="23"></line>
              <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
              <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
              <line x1="1" y1="12" x2="3" y2="12"></line>
              <line x1="21" y1="12" x2="23" y2="12"></line>
              <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
              <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
            </svg>
            <svg class="ui-icon theme-icon-moon" viewBox="0 0 24 24" aria-hidden="true">
              <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
            </svg>
            <span class="theme-label-text">Tema</span>
          </button>

          <a href="index.php?page=finance" class="btn-secondary">
            <svg class="ui-icon ui-icon-xs" viewBox="0 0 24 24" aria-hidden="true">
              <line x1="12" y1="5" x2="12" y2="19"></line>
              <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            <span>Catat Transaksi</span>
          </a>

          <button type="button" class="btn-lime" data-open-modal="<?= $currentPage === 'tasks' ? 'taskCreateModal' : 'quickTaskModal' ?>">
            <svg class="ui-icon ui-icon-xs" viewBox="0 0 24 24" aria-hidden="true">
              <line x1="12" y1="5" x2="12" y2="19"></line>
              <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            <span>Tambah Tugas</span>
          </button>
        </div>
      </header>

      <!-- Main Bento Canvas -->
      <main class="main-canvas" id="mainCanvas">
        <?php if ($flash): ?>
          <div class="alert-banner alert-<?= e($flash['type'] ?? 'info') ?>" id="flashAlertBanner" role="alert">
            <div class="alert-banner-left">
              <svg class="ui-icon ui-icon-sm" viewBox="0 0 24 24" aria-hidden="true">
                <?php if (($flash['type'] ?? '') === 'success'): ?>
                  <polyline points="20 6 9 17 4 12"></polyline>
                <?php elseif (in_array($flash['type'] ?? '', ['warning', 'danger'])): ?>
                  <circle cx="12" cy="12" r="10"></circle>
                  <line x1="12" y1="8" x2="12" y2="12"></line>
                  <line x1="12" y1="16" x2="12.01" y2="16"></line>
                <?php else: ?>
                  <circle cx="12" cy="12" r="10"></circle>
                  <line x1="12" y1="16" x2="12" y2="12"></line>
                  <line x1="12" y1="8" x2="12.01" y2="8"></line>
                <?php endif; ?>
              </svg>
              <span><?= e($flash['message']) ?></span>
            </div>
            <button type="button" class="modal-close-btn" data-dismiss-alert aria-label="Tutup Notifikasi" style="width:26px; height:26px; font-size:1rem;">&times;</button>
          </div>
        <?php endif; ?>

        <!-- Konten Halaman Aktif -->
        <?= $contentView ?? '' ?>
      </main>
    </div>
  </div>

  <!-- ====================================================================
       3. QUICK ADD TASK MODAL GLOBAL (POST langsung ke TaskController::store)
       ==================================================================== -->
  <div class="modal-backdrop" id="quickTaskModal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="quickTaskModalTitle">
    <div class="modal-card">
      <div class="modal-header">
        <div>
          <h2 class="modal-title" id="quickTaskModalTitle">Tambah Tugas Kuliah</h2>
          <p class="modal-subtitle">Tugas baru otomatis masuk ke antrean Smart Planner</p>
        </div>
        <button type="button" class="modal-close-btn" data-close-modal="quickTaskModal" aria-label="Tutup">&times;</button>
      </div>
      <form method="POST" action="index.php?page=tasks&action=store">
        <input type="hidden" name="page" value="tasks">
        <input type="hidden" name="action" value="store">
        <div class="modal-body">
          <div class="form-group">
            <label for="quickTaskTitle" class="form-label">Judul Tugas *</label>
            <input type="text" id="quickTaskTitle" name="title" class="form-input" placeholder="Contoh: Laporan Praktikum Basis Data" required>
          </div>

          <div class="form-grid-2">
            <div class="form-group">
              <label for="quickTaskDate" class="form-label">Tanggal Deadline *</label>
              <input type="date" id="quickTaskDate" name="deadline_date" class="form-input" value="<?= date('Y-m-d', strtotime('+2 days')) ?>" required>
            </div>
            <div class="form-group">
              <label for="quickTaskTime" class="form-label">Jam Deadline</label>
              <input type="time" id="quickTaskTime" name="deadline_time" class="form-input" value="23:59">
            </div>
          </div>

          <div class="form-grid-2">
            <div class="form-group">
              <label for="quickTaskHours" class="form-label">Estimasi Durasi (Jam)</label>
              <input type="number" id="quickTaskHours" name="estimated_hours" class="form-input" step="0.5" min="0.5" max="40" value="2.0">
            </div>
            <div class="form-group">
              <label for="quickTaskPriority" class="form-label">Prioritas</label>
              <select id="quickTaskPriority" name="priority" class="form-select">
                <option value="low">Low (Santai)</option>
                <option value="medium" selected>Medium (Normal)</option>
                <option value="high">High (Mendesak)</option>
              </select>
            </div>
          </div>

          <div class="form-group" style="margin-bottom: 0;">
            <label for="quickTaskDesc" class="form-label">Catatan Singkat</label>
            <textarea id="quickTaskDesc" name="description" class="form-textarea" placeholder="Catatan instruksi tugas atau referensi..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn-secondary" data-close-modal="quickTaskModal">Batal</button>
          <button type="submit" class="btn-lime">Simpan Tugas</button>
        </div>
      </form>
    </div>
  </div>

  <div class="toast-container" id="toastContainer" aria-live="polite" aria-atomic="true"></div>
  <script src="assets/js/ui.js" defer></script>
</body>
</html>
