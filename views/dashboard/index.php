<?php
/**
 * views/dashboard/index.php
 * Tampilan Utama Bento Grid Dashboard CampusOS (Hybrid SSR)
 * Terintegrasi penuh dengan Design System FigCraft & data dinamis PHP
 */

$user = currentUser();
$scheduleCount = !empty($todaySchedules) ? count($todaySchedules) : 3;
$taskCount = !empty($urgentTasks) ? count($urgentTasks) : 4;
$remainingBudget = isset($walletSummary['remainingBudget']) ? (float)$walletSummary['remainingBudget'] : 450000;
$spentBudget = isset($walletSummary['totalExpense']) ? (float)$walletSummary['totalExpense'] : 150000;
$budgetStatus = isset($walletSummary['statusBadge']) 
    ? ($walletSummary['statusBadge'] === 'danger' ? 'Kritis' : ($walletSummary['statusBadge'] === 'warning' ? 'Waspada' : 'Aman')) 
    : 'Aman';

$earliestSession = '08.00 WIB';
if (!empty($todaySchedules) && isset($todaySchedules[0]['start_time'])) {
    $earliestSession = substr($todaySchedules[0]['start_time'], 0, 5) . ' WIB';
}
?>

<!-- Top Greeting Header & Action Group -->
<div class="canvas-top-bar">
  <div class="canvas-top-left">
    <button type="button" class="mobile-nav-toggle" id="mobileNavToggle" aria-label="Buka Menu Navigasi" title="Menu">
      <svg class="ui-icon" viewBox="0 0 24 24" aria-hidden="true">
        <line x1="3" y1="12" x2="21" y2="12"></line>
        <line x1="3" y1="6" x2="21" y2="6"></line>
        <line x1="3" y1="18" x2="21" y2="18"></line>
      </svg>
    </button>

    <header class="canvas-header">
      <h1 class="greeting-title" id="greetingTitle">Selamat datang kembali, <?= e($user['name'] ?? 'Mahasiswa') ?> 👋</h1>
      <p class="greeting-subtitle" id="greetingSubtitle"><?= date('l, d F Y') ?> • <?= $scheduleCount ?> Mata Kuliah &amp; <?= $taskCount ?> Deadline Menunggu</p>
    </header>
  </div>

  <div class="canvas-top-right">
    <button type="button" class="btn-reset-demo" id="resetDemoBtn" title="Kembalikan data demo awal">
      <svg class="ui-icon ui-icon-xs" viewBox="0 0 24 24" aria-hidden="true">
        <polyline points="23 4 23 10 17 10"></polyline>
        <polyline points="1 20 1 14 7 14"></polyline>
        <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
      </svg>
      <span>Reset Demo</span>
    </button>
  </div>
</div>

<!-- Baris 1: 3 Kartu Ringkasan Atas (Grid 3 Kolom Sejajar) -->
<section class="summary-cards-grid" aria-label="Ringkasan Status Perkuliahan">
  
  <!-- Kartu 1: Jadwal Kuliah Hari Ini (#FDE6D8 Peach) -->
  <article class="summary-card" data-module="next-class">
    <div class="summary-card-header">
      <div class="summary-icon-box peach" aria-hidden="true">
        <svg class="ui-icon" viewBox="0 0 24 24">
          <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
          <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
        </svg>
      </div>
      <div class="summary-title-group">
        <h2 class="summary-main-title" id="summaryNextClassTitle"><?= $scheduleCount ?> Mata Kuliah</h2>
        <span class="summary-subtitle" id="summaryNextClassSubtitle">Jadwal Kuliah Hari Ini</span>
      </div>
    </div>
    <div class="summary-card-meta">
      <div class="summary-meta-item">
        <span class="summary-meta-label">STATUS</span>
        <span class="summary-meta-val">Aktif Semester <?= e($user['semester'] ?? 5) ?></span>
      </div>
      <div class="summary-meta-item">
        <span class="summary-meta-label">SESI TERDEKAT</span>
        <span class="summary-meta-val" id="summaryNextClassSession"><?= e($earliestSession) ?></span>
      </div>
    </div>
  </article>

  <!-- Kartu 2: Tugas Aktif (#EBF9B8 Lime) -->
  <article class="summary-card" data-module="priority-tasks">
    <div class="summary-card-header">
      <div class="summary-icon-box lime" aria-hidden="true">
        <svg class="ui-icon" viewBox="0 0 24 24">
          <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path>
          <rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect>
        </svg>
      </div>
      <div class="summary-title-group">
        <h2 class="summary-main-title"><span id="summaryTasksCount"><?= $taskCount ?></span> Tugas Aktif</h2>
        <span class="summary-subtitle"><span id="summaryTasksWarning">2</span> Mendekati Deadline</span>
      </div>
    </div>
    <div class="summary-card-meta">
      <div class="summary-meta-item">
        <span class="summary-meta-label">SELESAI</span>
        <span class="summary-meta-val" id="summaryTasksDoneVal">12 Tugas</span>
      </div>
      <div class="summary-meta-item">
        <span class="summary-meta-label">PRIORITAS</span>
        <span class="summary-meta-val text-warning" id="summaryTasksPriorityVal">Tinggi (2)</span>
      </div>
    </div>
  </article>

  <!-- Kartu 3: Anggaran Kuliah (#E5DFFF Lavender) -->
  <article class="summary-card" data-module="expense-summary">
    <div class="summary-card-header">
      <div class="summary-icon-box lavender" aria-hidden="true">
        <svg class="ui-icon" viewBox="0 0 24 24">
          <rect x="2" y="4" width="20" height="16" rx="2"></rect>
          <line x1="2" y1="10" x2="22" y2="10"></line>
        </svg>
      </div>
      <div class="summary-title-group">
        <h2 class="summary-main-title" id="summaryRemainingBudget">Rp <?= number_format($remainingBudget, 0, ',', '.') ?></h2>
        <span class="summary-subtitle">Sisa Anggaran Minggu Ini</span>
      </div>
    </div>
    <div class="summary-card-meta">
      <div class="summary-meta-item">
        <span class="summary-meta-label">TERPAKAI</span>
        <span class="summary-meta-val" id="summarySpentBudget">Rp <?= number_format($spentBudget, 0, ',', '.') ?></span>
      </div>
      <div class="summary-meta-item">
        <span class="summary-meta-label">STATUS</span>
        <span class="summary-meta-val text-<?= $budgetStatus === 'Aman' ? 'success' : 'warning' ?>" id="summaryBudgetStatus"><?= e($budgetStatus) ?></span>
      </div>
    </div>
  </article>
</section>

<!-- Baris 2: Grid 2 Kolom Sejajar (Grafik Aktivitas & Jadwal Kuliah) -->
<section class="middle-bento-grid" aria-label="Aktivitas dan Jadwal">
  
  <!-- Kiri: Grafik Aktivitas Belajar Mingguan (7 Batang) -->
  <article class="bento-card" data-module="activity-chart">
    <div class="card-header-row">
      <div class="card-heading-group">
        <h2 class="card-heading-title">Aktivitas Mingguan</h2>
        <span class="card-growth-tag">↗ +18% lebih produktif dari minggu lalu</span>
      </div>
      <button type="button" class="filter-pill" id="chartFilterBtn" aria-label="Pilih Periode Grafik">
        <span>Mingguan</span>
        <svg class="ui-icon ui-icon-xs" viewBox="0 0 24 24" aria-hidden="true">
          <polyline points="6 9 12 15 18 9"></polyline>
        </svg>
      </button>
    </div>

    <div class="chart-container" aria-label="Grafik jam belajar dan pengeluaran harian">
      <!-- Grid Lines -->
      <div class="chart-grid-guides" aria-hidden="true">
        <div class="chart-guide-line"></div>
        <div class="chart-guide-line"></div>
        <div class="chart-guide-line"></div>
        <div class="chart-guide-line"></div>
        <div class="chart-guide-line"></div>
      </div>

      <!-- Y Axis Labels -->
      <div class="chart-y-axis" aria-hidden="true">
        <span>8j</span>
        <span>6j</span>
        <span>4j</span>
        <span>2j</span>
        <span>0j</span>
      </div>

      <!-- Vertical Bar Columns Track (Interaktif via ui.js) -->
      <div class="chart-bars-track" id="chartBarsTrack">
        <!-- Rendered via ui.js dynamically -->
      </div>

      <!-- X Axis Day Labels -->
      <div class="chart-x-labels" id="chartXLabels" aria-hidden="true">
        <span class="x-label" data-index="0">Min</span>
        <span class="x-label" data-index="1">Sen</span>
        <span class="x-label" data-index="2">Sel</span>
        <span class="x-label is-active" data-index="3">Rab</span>
        <span class="x-label" data-index="4">Kam</span>
        <span class="x-label" data-index="5">Jum</span>
        <span class="x-label" data-index="6">Sab</span>
      </div>
    </div>
  </article>

  <!-- Kanan: Jadwal Kuliah Hari Ini -->
  <article class="bento-card" data-module="daily-schedule">
    <div class="card-header-row">
      <h2 class="card-heading-title">Jadwal Kuliah Hari Ini</h2>
      <a href="index.php?page=academic" class="summary-subtitle" id="viewAllScheduleLink">Lihat Semua</a>
    </div>

    <div class="schedule-list" id="scheduleList">
      <!-- Rendered via ui.js dynamically -->
    </div>
  </article>
</section>

<!-- Baris 3: Progres Target Semester (Card Horizontal dengan Ring Chart) -->
<section class="semester-target-row" aria-label="Progres Target Semester">
  <article class="semester-card" data-module="semester-progress">
    <div class="card-header-row">
      <h2 class="card-heading-title">Target Semester &amp; Mata Kuliah</h2>
      <div class="semester-header-actions">
        <button type="button" class="filter-pill" id="semesterFilterBtn">
          <span>Semester <?= e($user['semester'] ?? 5) ?> (Ganjil)</span>
          <svg class="ui-icon ui-icon-xs" viewBox="0 0 24 24" aria-hidden="true">
            <polyline points="6 9 12 15 18 9"></polyline>
          </svg>
        </button>
        <a href="index.php?page=academic" class="btn-circle-add" id="addTargetBtn" aria-label="Tambah Target Mata Kuliah" title="Tambah Target">+</a>
      </div>
    </div>

    <div class="target-course-rows">
      <!-- Row 1: Pemrograman Web Modern (45% Emerald Ring) -->
      <div class="target-row">
        <div class="target-left-group">
          <div class="target-icon-box lavender" aria-hidden="true">
            <svg class="ui-icon" viewBox="0 0 24 24">
              <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
              <line x1="8" y1="21" x2="16" y2="21"></line>
              <line x1="12" y1="17" x2="12" y2="21"></line>
            </svg>
          </div>
          <div class="target-detail-group">
            <span class="target-course-name">Pemrograman Web Modern</span>
            <span class="target-lecturer">Vera Irma Delianti, M.Pd.T.</span>
          </div>
        </div>
        <div class="target-mid-meta">
          <span class="target-meta-label">SISA MATERI</span>
          <span class="target-meta-value">4 Modul • 8 Jam</span>
        </div>
        <div class="ring-progress-wrapper" aria-label="Progres 45%">
          <svg class="ring-progress-svg" viewBox="0 0 40 40">
            <circle class="ring-bg" cx="20" cy="20" r="16"></circle>
            <circle class="ring-val emerald pct-45" cx="20" cy="20" r="16"></circle>
          </svg>
          <span class="ring-pct-text">45%</span>
        </div>
      </div>

      <!-- Row 2: Sistem Basis Data Relasional (75% Indigo Ring) -->
      <div class="target-row">
        <div class="target-left-group">
          <div class="target-icon-box peach" aria-hidden="true">
            <svg class="ui-icon" viewBox="0 0 24 24">
              <ellipse cx="12" cy="5" rx="9" ry="3"></ellipse>
              <path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"></path>
              <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"></path>
            </svg>
          </div>
          <div class="target-detail-group">
            <span class="target-course-name">Sistem Basis Data Relasional</span>
            <span class="target-lecturer">Ahmad Fauzi, M.Kom.</span>
          </div>
        </div>
        <div class="target-mid-meta">
          <span class="target-meta-label">SISA MATERI</span>
          <span class="target-meta-value">2 Modul • 4 Jam</span>
        </div>
        <div class="ring-progress-wrapper" aria-label="Progres 75%">
          <svg class="ring-progress-svg" viewBox="0 0 40 40">
            <circle class="ring-bg" cx="20" cy="20" r="16"></circle>
            <circle class="ring-val indigo pct-75" cx="20" cy="20" r="16"></circle>
          </svg>
          <span class="ring-pct-text">75%</span>
        </div>
      </div>
    </div>

    <div class="semester-info-strip">
      🎓 Total 21 SKS terdaftar semester ini • Target Indeks Prestasi Akademik: 3.85
    </div>
  </article>
</section>

<!-- Injeksi Data Nyata dari Backend PHP ke Client Controller ui.js jika tersedia -->
<script>
  window.CAMPUSOS_INITIAL_DATA = {
    <?php if (!empty($urgentTasks)): ?>
    tasks: <?= json_encode(array_map(function($t) {
      return [
        'id'       => 'task-' . $t['id'],
        'title'    => $t['title'],
        'course'   => $t['course_name'] ?? 'Mata Kuliah',
        'deadline' => $t['deadline_date'] . ', ' . substr($t['deadline_time'] ?? '23:59:00', 0, 5) . ' WIB',
        'status'   => $t['status'] === 'done' ? 'Completed' : ($t['status'] === 'in_progress' ? 'In progress' : 'Upcoming'),
        'priority' => ucfirst($t['priority'] ?? 'Sedang')
      ];
    }, $urgentTasks)) ?>,
    <?php endif; ?>

    <?php if (!empty($todaySchedules)): ?>
    schedule: <?= json_encode(array_map(function($s, $idx) {
      $colorPalettes = ['peach', 'lavender', 'mint', 'lime'];
      $color = $colorPalettes[$idx % 4];
      return [
        'id'      => 'sch-' . $s['id'],
        'title'   => $s['course_name'] ?? 'Mata Kuliah',
        'meta'    => substr($s['start_time'], 0, 5) . ' - ' . substr($s['end_time'], 0, 5) . ' • ' . ($s['room'] ?? 'Ruang Teori'),
        'color'   => $color,
        'iconSvg' => '<polyline points="16 18 22 12 16 6"></polyline><polyline points="8 6 2 12 8 18"></polyline>'
      ];
    }, $todaySchedules, array_keys($todaySchedules))) ?>,
    <?php endif; ?>

    <?php if (!empty($walletSummary)): ?>
    budget: {
      totalWeekly: <?= (float)($walletSummary['monthlyBudget'] ?? 600000) ?>,
      remaining:   <?= (float)($walletSummary['remainingBudget'] ?? 450000) ?>,
      spent:       <?= (float)($walletSummary['totalExpense'] ?? 150000) ?>,
      status:      '<?= e($budgetStatus) ?>',
      dailyData: [
        { day: 'Min', label: 'Minggu', hours: 3.0, timeStr: '3j 00 mnt', dateStr: '15 Nov 2026', heightPct: 40, spending: 20000 },
        { day: 'Sen', label: 'Senin', hours: 5.25, timeStr: '5j 15 mnt', dateStr: '16 Nov 2026', heightPct: 65, spending: 35000 },
        { day: 'Sel', label: 'Selasa', hours: 2.65, timeStr: '2j 40 mnt', dateStr: '17 Nov 2026', heightPct: 35, spending: 25000 },
        { day: 'Rab', label: 'Rabu', hours: 6.75, timeStr: '6j 45 mnt', dateStr: '18 Nov 2026', heightPct: 85, spending: 45000 },
        { day: 'Kam', label: 'Kamis', hours: 4.0, timeStr: '4j 00 mnt', dateStr: '19 Nov 2026', heightPct: 50, spending: 15000 },
        { day: 'Jum', label: 'Jumat', hours: 2.15, timeStr: '2j 10 mnt', dateStr: '20 Nov 2026', heightPct: 25, spending: 10000 },
        { day: 'Sab', label: 'Sabtu', hours: 4.5, timeStr: '4j 30 mnt', dateStr: '21 Nov 2026', heightPct: 55, spending: 0 }
      ],
      activeDayIndex: 3
    }
    <?php endif; ?>
  };
</script>
