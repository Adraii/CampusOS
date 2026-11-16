<?php
/**
 * views/layouts/main.php
 * Master Layout: 3-Column Bento Grid Shell (Hybrid SSR)
 * Terinspirasi dari Sandbox/index.html & FigCraft Canvas
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
if (empty($userInitials)) $userInitials = 'M';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="CampusOS — Web-based Student Assistant Dashboard berbasis Bento Grid modern.">
  <title><?= e($pageTitle ?? 'CampusOS — Web-based Student Assistant') ?></title>
  
  <!-- Google Fonts: Plus Jakarta Sans & Inter -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  
  <!-- Design Tokens, Layout, & Components -->
  <link rel="stylesheet" href="assets/css/tokens.css">
  <link rel="stylesheet" href="assets/css/layout.css">
  <link rel="stylesheet" href="assets/css/components.css">
</head>
<body>
  <!-- Overlay Backdrop for Mobile Sidebar Drawer -->
  <div class="sidebar-backdrop" id="sidebarBackdrop" aria-hidden="true"></div>

  <!-- Main Application Frame (Desktop 1440x900 Bento Shell) -->
  <div class="app-shell" id="appShell">
    
    <!-- ====================================================================
         1. KOLOM KIRI — SIDEBAR NAVIGASI (240px, #1A1B2E)
         ==================================================================== -->
    <aside class="sidebar" id="appSidebar" aria-label="Navigasi Utama">
      <!-- Brand Logo & App Title -->
      <div class="sidebar-header">
        <div class="sidebar-logo-ring" aria-hidden="true">
          <div class="sidebar-logo-dot"></div>
        </div>
        <span class="sidebar-brand-name">CampusOS</span>
        <button type="button" class="sidebar-collapse-btn" id="sidebarCollapseBtn" aria-label="Kecilkan/Perluas Sidebar" title="Kecilkan/Perluas Sidebar">
          <svg class="ui-icon ui-icon-xs" viewBox="0 0 24 24" aria-hidden="true">
            <polyline points="15 18 9 12 15 6"></polyline>
          </svg>
        </button>
      </div>

      <!-- Navigation Capsule Menu -->
      <nav class="sidebar-nav" aria-label="Menu Aplikasi">
        <a href="index.php?page=dashboard" class="nav-link <?= $currentPage === 'dashboard' ? 'is-active' : '' ?>" data-route="dashboard" data-label="Dashboard" <?= $currentPage === 'dashboard' ? 'aria-current="page"' : '' ?>>
          <svg class="ui-icon" viewBox="0 0 24 24" aria-hidden="true">
            <rect x="3" y="3" width="7" height="9" rx="1"></rect>
            <rect x="14" y="3" width="7" height="5" rx="1"></rect>
            <rect x="14" y="12" width="7" height="9" rx="1"></rect>
            <rect x="3" y="16" width="7" height="5" rx="1"></rect>
          </svg>
          <span>Dashboard</span>
        </a>

        <a href="index.php?page=academic" class="nav-link <?= $currentPage === 'academic' ? 'is-active' : '' ?>" data-route="academic" data-label="Jadwal Kuliah" <?= $currentPage === 'academic' ? 'aria-current="page"' : '' ?>>
          <svg class="ui-icon" viewBox="0 0 24 24" aria-hidden="true">
            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
            <line x1="16" y1="2" x2="16" y2="6"></line>
            <line x1="8" y1="2" x2="8" y2="6"></line>
            <line x1="3" y1="10" x2="21" y2="10"></line>
          </svg>
          <span>Jadwal Kuliah</span>
        </a>

        <a href="index.php?page=calendar" class="nav-link <?= $currentPage === 'calendar' ? 'is-active' : '' ?>" data-route="calendar" data-label="Kalender Kegiatan" <?= $currentPage === 'calendar' ? 'aria-current="page"' : '' ?>>
          <svg class="ui-icon" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M8 2v4"></path>
            <path d="M16 2v4"></path>
            <rect x="3" y="4" width="18" height="18" rx="2"></rect>
            <path d="M3 10h18"></path>
            <circle cx="8" cy="14" r="1"></circle>
            <circle cx="12" cy="14" r="1"></circle>
            <circle cx="16" cy="14" r="1"></circle>
            <circle cx="8" cy="18" r="1"></circle>
            <circle cx="12" cy="18" r="1"></circle>
          </svg>
          <span>Kalender</span>
        </a>

        <a href="index.php?page=tasks" class="nav-link <?= $currentPage === 'tasks' ? 'is-active' : '' ?>" data-route="tasks" data-label="Tugas &amp; Prioritas" <?= $currentPage === 'tasks' ? 'aria-current="page"' : '' ?>>
          <svg class="ui-icon" viewBox="0 0 24 24" aria-hidden="true">
            <polyline points="9 11 12 14 22 4"></polyline>
            <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
          </svg>
          <span>Tugas &amp; Prioritas</span>
        </a>

        <a href="index.php?page=planner" class="nav-link <?= $currentPage === 'planner' ? 'is-active' : '' ?>" data-route="planner" data-label="Smart Planner" <?= $currentPage === 'planner' ? 'aria-current="page"' : '' ?>>
          <svg class="ui-icon" viewBox="0 0 24 24" aria-hidden="true">
            <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
          </svg>
          <span>Smart Planner</span>
        </a>

        <a href="index.php?page=finance" class="nav-link <?= $currentPage === 'finance' ? 'is-active' : '' ?>" data-route="finance" data-label="Keuangan Mahasiswa" <?= $currentPage === 'finance' ? 'aria-current="page"' : '' ?>>
          <svg class="ui-icon" viewBox="0 0 24 24" aria-hidden="true">
            <rect x="2" y="4" width="20" height="16" rx="2"></rect>
            <line x1="2" y1="10" x2="22" y2="10"></line>
          </svg>
          <span>Keuangan</span>
        </a>

        <a href="index.php?page=logout" class="nav-link" data-route="logout" data-label="Keluar Akun" onclick="return confirm('Apakah Anda yakin ingin keluar?');">
          <svg class="ui-icon" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
            <polyline points="16 17 21 12 16 7"></polyline>
            <line x1="21" y1="12" x2="9" y2="12"></line>
          </svg>
          <span>Logout</span>
        </a>
      </nav>

      <!-- Bottom Card: Mode Fokus Belajar (#D4F26A) -->
      <div class="sidebar-focus-card">
        <div class="focus-card-top">
          <div class="focus-deco-lines" aria-hidden="true">
            <div class="focus-line focus-line-1"></div>
            <div class="focus-line focus-line-2"></div>
          </div>
          <button type="button" class="focus-circle-btn" id="focusModeBtn" aria-label="Aktifkan Mode Fokus Belajar" title="Buka Mode Fokus">
            <svg class="ui-icon ui-icon-sm" viewBox="0 0 24 24" aria-hidden="true">
              <line x1="7" y1="17" x2="17" y2="7"></line>
              <polyline points="7 7 17 7 17 17"></polyline>
            </svg>
          </button>
        </div>
        <div class="focus-card-pill">Mode Fokus Belajar</div>
      </div>
    </aside>

    <!-- ====================================================================
         2. KOLOM TENGAH — KANVAS UTAMA (#F6F6F8)
         ==================================================================== -->
    <main class="main-canvas" id="mainCanvas">
      <?php if ($flash): ?>
        <div class="toast-flash-notice" style="margin-bottom: 12px; padding: 12px 16px; border-radius: var(--radius-md); background: <?= $flash['type'] === 'success' ? 'var(--color-pastel-mint)' : 'var(--color-pastel-peach)' ?>; color: <?= $flash['type'] === 'success' ? 'var(--color-pastel-mint-text)' : 'var(--color-pastel-peach-text)' ?>; font-size: var(--font-size-sm); font-weight: var(--font-weight-medium); display: flex; align-items: center; gap: 8px;">
          <span><?= $flash['type'] === 'success' ? '✓' : 'ℹ' ?></span>
          <span><?= e($flash['message']) ?></span>
        </div>
      <?php endif; ?>

      <!-- Dynamic Page Content -->
      <?= $contentView ?? '' ?>
    </main>

    <!-- ====================================================================
         3. KOLOM KANAN — PANEL PENDUKUNG (320px)
         ==================================================================== -->
    <aside class="right-panel" id="rightPanel" aria-label="Panel Pendukung">
      
      <!-- Top Search & User Profile -->
      <div class="right-panel-top-row">
        <div class="search-bar-capsule">
          <svg class="ui-icon ui-icon-xs text-muted" viewBox="0 0 24 24" aria-hidden="true">
            <circle cx="11" cy="11" r="8"></circle>
            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
          </svg>
          <input type="search" class="search-input" id="globalSearchInput" placeholder="Cari matkul atau tugas..." aria-label="Pencarian Cepat">
        </div>
        <div class="user-avatar-circle" id="userProfileBtn" title="Profil <?= e($user['name'] ?? 'Mahasiswa') ?>" role="button" tabindex="0">
          <?= e($userInitials) ?>
        </div>
      </div>

      <!-- Dark Budget Card Banner (#1A1B2E with Lime Button) -->
      <section class="dark-budget-card" aria-label="Kartu Ringkasan Anggaran">
        <div class="budget-deco-circles" aria-hidden="true">
          <div class="budget-circle-1"></div>
          <div class="budget-circle-2"></div>
        </div>
        <span class="budget-tag">CampusOS Wallet</span>
        <h3 class="budget-title">Ringkasan Anggaran<br>Bulan Ini</h3>
        <p class="budget-subtitle">Kelola pengeluaran harian dan uang saku kuliah secara teratur.</p>
        <button type="button" class="budget-action-btn" id="recordExpenseBtn">+ Catat Pengeluaran</button>
      </section>

      <!-- Mini Calendar Card -->
      <section class="mini-calendar-card" aria-label="Kalender Akademik">
        <div class="calendar-header-nav">
          <button type="button" class="cal-nav-btn" id="prevMonthBtn" aria-label="Bulan Sebelumnya" title="Sebelumnya">
            <svg class="ui-icon ui-icon-xs" viewBox="0 0 24 24">
              <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
          </button>
          <span class="cal-month-title" id="calMonthTitle"><?= date('F Y') ?></span>
          <button type="button" class="cal-nav-btn" id="nextMonthBtn" aria-label="Bulan Berikutnya" title="Berikutnya">
            <svg class="ui-icon ui-icon-xs" viewBox="0 0 24 24">
              <polyline points="9 18 15 12 9 6"></polyline>
            </svg>
          </button>
        </div>

        <div class="calendar-weekdays-row" aria-hidden="true">
          <span>M</span><span>S</span><span>S</span><span>R</span><span>K</span><span>J</span><span>S</span>
        </div>

        <div class="calendar-dates-grid" id="calendarDatesGrid">
          <!-- Rendered dynamically via ui.js -->
        </div>

        <div class="calendar-footer-note" id="calendarFooterNote">
          ● Hari ini: <?= date('d M') ?> — Jadwal Perkuliahan &amp; Deadline
        </div>
      </section>

      <!-- Tasks & Deadlines Section -->
      <section class="tasks-section" aria-label="Daftar Tugas dan Deadline">
        <div class="tasks-header-row">
          <h3 class="tasks-header-title">Tugas &amp; Deadline</h3>
          <button type="button" class="btn-circle-add" id="addTaskBtn" aria-label="Tambah Tugas Baru" title="Tambah Tugas">+</button>
        </div>

        <div class="tasks-list" id="tasksList">
          <!-- Rendered dynamically via ui.js -->
        </div>
      </section>
    </aside>
  </div>

  <!-- ====================================================================
       4. MODAL POPUP DIALOGS & NOTIFICATION TOAST
       ==================================================================== -->

  <!-- Modal: Catat Pengeluaran -->
  <div class="modal-backdrop" id="expenseModal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="expenseModalTitle">
    <div class="modal-card">
      <div class="modal-header">
        <div class="modal-title-group">
          <h3 class="modal-title" id="expenseModalTitle">Catat Pengeluaran Baru</h3>
          <span class="modal-subtitle">Kurangi sisa anggaran &amp; perbarui grafik aktivitas</span>
        </div>
        <button type="button" class="modal-close-btn" id="closeExpenseModalBtn" aria-label="Tutup Modal">&times;</button>
      </div>
      <form id="expenseForm">
        <div class="modal-body">
          <div class="form-group">
            <label for="expenseName" class="form-label">Nama Pengeluaran</label>
            <input type="text" id="expenseName" name="name" class="form-input" placeholder="Contoh: Makan Siang Kantin" required autocomplete="off">
          </div>
          <div class="form-group">
            <label for="expenseAmount" class="form-label">Nominal (Rp)</label>
            <input type="number" id="expenseAmount" name="amount" class="form-input" placeholder="Contoh: 25000" min="500" step="500" required>
          </div>
          <div class="form-group">
            <label for="expenseCategory" class="form-label">Kategori</label>
            <select id="expenseCategory" name="category" class="form-select">
              <option value="Makanan &amp; Minuman">Makanan &amp; Minuman</option>
              <option value="Buku &amp; Perkuliahan">Buku &amp; Perkuliahan</option>
              <option value="Transportasi">Transportasi</option>
              <option value="Hiburan &amp; Lainnya">Hiburan &amp; Lainnya</option>
            </select>
          </div>
          <div class="form-group">
            <label for="expenseDay" class="form-label">Hari Pengeluaran</label>
            <select id="expenseDay" name="day" class="form-select">
              <option value="Sen">Senin</option>
              <option value="Sel">Selasa</option>
              <option value="Rab" selected>Rabu</option>
              <option value="Kam">Kamis</option>
              <option value="Jum">Jumat</option>
              <option value="Sab">Sabtu</option>
              <option value="Min">Minggu</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn-modal-cancel" id="cancelExpenseBtn">Batal</button>
          <button type="submit" class="btn-modal-submit">Simpan Pengeluaran</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal: Tambah Tugas Baru -->
  <div class="modal-backdrop" id="taskModal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="taskModalTitle">
    <div class="modal-card">
      <div class="modal-header">
        <div class="modal-title-group">
          <h3 class="modal-title" id="taskModalTitle">Tambah Tugas Baru</h3>
          <span class="modal-subtitle">Catat deadline dan prioritas tugas perkuliahan</span>
        </div>
        <button type="button" class="modal-close-btn" id="closeTaskModalBtn" aria-label="Tutup Modal">&times;</button>
      </div>
      <form id="taskForm">
        <div class="modal-body">
          <div class="form-group">
            <label for="taskName" class="form-label">Nama Tugas</label>
            <input type="text" id="taskName" name="title" class="form-input" placeholder="Contoh: Prototipe UI Portofolio" required autocomplete="off">
          </div>
          <div class="form-group">
            <label for="taskCourse" class="form-label">Mata Kuliah</label>
            <input type="text" id="taskCourse" name="course_name" class="form-input" placeholder="Contoh: Interaksi Manusia &amp; Komputer" required>
          </div>
          <div class="form-group">
            <label for="taskDeadline" class="form-label">Tenggat Waktu</label>
            <input type="text" id="taskDeadline" name="deadline" class="form-input" placeholder="Contoh: Besok, 23.59 WIB" required>
          </div>
          <div class="form-group">
            <label for="taskStatusSelect" class="form-label">Status Awal</label>
            <select id="taskStatusSelect" name="status" class="form-select">
              <option value="Upcoming">Upcoming (Akan Datang)</option>
              <option value="In progress" selected>In progress (Sedang Dikerjakan)</option>
              <option value="Completed">Completed (Selesai)</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn-modal-cancel" id="cancelTaskBtn">Batal</button>
          <button type="submit" class="btn-modal-submit">Tambah Tugas</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Toast Notifications Container -->
  <div class="toast-container" id="toastContainer" aria-live="polite" aria-atomic="true"></div>

  <!-- Vanilla JavaScript Interactive Controller -->
  <script src="assets/js/ui.js" defer></script>
</body>
</html>
