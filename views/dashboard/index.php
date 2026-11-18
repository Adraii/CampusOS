<?php
/**
 * views/dashboard/index.php
 * Dashboard Overview — Technical High-Contrast Dark Bento (Hybrid SSR)
 * Sinkronisasi visual dengan Artboard 1 di docs/CampusOS-Dashboard.figcraft
 */

$todaySchedules = $todaySchedules ?? [];
$urgentTasks = $urgentTasks ?? [];
$plannerRecommendations = $plannerRecommendations ?? [];
$walletSummary = $walletSummary ?? [
    'monthlyBudget'   => 1500000.0,
    'totalExpense'    => 125000.0,
    'remainingBudget' => 1375000.0,
    'percentUsed'     => 8.3,
    'statusBadge'     => 'success',
];

$activeTaskCount = count($urgentTasks);
$highPriorityCount = 0;
$totalEstimatedHours = 0.0;
foreach ($urgentTasks as $t) {
    if (($t['priority'] ?? '') === 'high') {
        $highPriorityCount++;
    }
    $totalEstimatedHours += (float) ($t['estimated_hours'] ?? 2.0);
}

$monthlyBudget = (float) ($walletSummary['monthlyBudget'] ?? 1500000);
$remainingBudget = (float) ($walletSummary['remainingBudget'] ?? 0);
$percentUsed = (float) ($walletSummary['percentUsed'] ?? 0);
$percentRemaining = max(0, min(100, 100 - $percentUsed));
$budgetStatus = $walletSummary['statusBadge'] ?? 'success';
$budgetStatusLabel = $budgetStatus === 'danger' ? 'Kritis' : ($budgetStatus === 'warning' ? 'Waspada' : 'Aman');

// Data grafik mingguan bersih (Sen-Min)
$weeklyStudyBars = [
    ['short' => 'Sen', 'full' => 'Senin',  'hours' => 3.5, 'pct' => 54, 'tone' => 'muted'],
    ['short' => 'Sel', 'full' => 'Selasa', 'hours' => 5.0, 'pct' => 76, 'tone' => 'lime'],
    ['short' => 'Rab', 'full' => 'Rabu',   'hours' => 6.2, 'pct' => 94, 'tone' => 'lime', 'active' => true],
    ['short' => 'Kam', 'full' => 'Kamis',  'hours' => 4.0, 'pct' => 62, 'tone' => 'muted'],
    ['short' => 'Jum', 'full' => 'Jumat',  'hours' => 2.8, 'pct' => 44, 'tone' => 'orange'],
    ['short' => 'Sab', 'full' => 'Sabtu',  'hours' => 4.5, 'pct' => 68, 'tone' => 'muted'],
    ['short' => 'Min', 'full' => 'Minggu', 'hours' => 5.4, 'pct' => 82, 'tone' => 'lime'],
];

// Ambil 1 rekomendasi utama untuk preview di Dashboard
$topRec = $plannerRecommendations[0] ?? null;
$topUrgentTask = $urgentTasks[0] ?? null;
?>

<!-- ========================================================================
     ROW 1: 4 METRIC KPI CARDS
     ======================================================================== -->
<section class="kpi-grid-4" aria-label="Ringkasan Metrik Utama Mahasiswa">
  <!-- KPI 1: SKS Semester -->
  <article class="kpi-card">
    <div class="kpi-top">
      <span class="kpi-label">SKS Semester Ini</span>
      <span class="kpi-icon-dot is-lime" aria-hidden="true">
        <svg class="ui-icon ui-icon-xs" viewBox="0 0 24 24">
          <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
          <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
        </svg>
      </span>
    </div>
    <div class="kpi-value">21 SKS</div>
    <div class="kpi-footer">
      <span class="pill pill-lime">Semester <?= e($user['semester'] ?? 3) ?> Aktif</span>
      <span><?= count($todaySchedules) ?> kuliah hari ini</span>
    </div>
  </article>

  <!-- KPI 2: Tugas Aktif -->
  <article class="kpi-card">
    <div class="kpi-top">
      <span class="kpi-label">Tugas Aktif</span>
      <span class="kpi-icon-dot is-orange" aria-hidden="true">
        <svg class="ui-icon ui-icon-xs" viewBox="0 0 24 24">
          <polyline points="9 11 12 14 22 4"></polyline>
          <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
        </svg>
      </span>
    </div>
    <div class="kpi-value"><?= $activeTaskCount ?> Tugas</div>
    <div class="kpi-footer">
      <span class="pill pill-orange"><?= $highPriorityCount ?> Prioritas Tinggi</span>
      <a href="index.php?page=tasks" style="font-size: var(--font-size-xs);">Kelola &rarr;</a>
    </div>
  </article>

  <!-- KPI 3: Beban Estimasi Smart Planner -->
  <article class="kpi-card">
    <div class="kpi-top">
      <span class="kpi-label">Beban Studi Aktif</span>
      <span class="kpi-icon-dot is-lime" aria-hidden="true">
        <svg class="ui-icon ui-icon-xs" viewBox="0 0 24 24">
          <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
        </svg>
      </span>
    </div>
    <div class="kpi-value is-lime"><?= number_format($totalEstimatedHours, 1) ?> Jam</div>
    <div class="kpi-footer">
      <span>Estimasi tugas aktif</span>
      <a href="index.php?page=planner" style="font-size: var(--font-size-xs);">Atur Slot &rarr;</a>
    </div>
  </article>

  <!-- KPI 4: Sisa Anggaran Bulanan -->
  <article class="kpi-card">
    <div class="kpi-top">
      <span class="kpi-label">Sisa Anggaran Bulan Ini</span>
      <span class="kpi-icon-dot" aria-hidden="true">
        <svg class="ui-icon ui-icon-xs" viewBox="0 0 24 24">
          <rect x="2" y="4" width="20" height="16" rx="2"></rect>
          <line x1="2" y1="10" x2="22" y2="10"></line>
        </svg>
      </span>
    </div>
    <div class="kpi-value is-compact">Rp <?= number_format($remainingBudget, 0, ',', '.') ?></div>
    <div>
      <div class="kpi-progress-track">
        <div class="kpi-progress-fill <?= $budgetStatus === 'danger' ? 'is-danger' : ($budgetStatus === 'warning' ? 'is-warning' : '') ?>" style="width: <?= round($percentRemaining) ?>%;"></div>
      </div>
      <div class="kpi-footer">
        <span>Terpakai <?= number_format($percentUsed, 1) ?>%</span>
        <span class="<?= $budgetStatus === 'danger' ? 'pill pill-danger' : ($budgetStatus === 'warning' ? 'pill pill-orange' : 'pill pill-lime') ?>"><?= e($budgetStatusLabel) ?></span>
      </div>
    </div>
  </article>
</section>

<!-- ========================================================================
     ROW 2: BAR CHART MINGGUAN + HORIZONTAL TIMELINE PENUGASAN
     ======================================================================== -->
<section class="bento-grid-split" aria-label="Analitik Mingguan dan Timeline Tugas">
  <!-- Kiri: Beban Belajar Mingguan (Clean CSS Vertical Bar Chart) -->
  <article class="bento-card">
    <div class="card-header-row">
      <div class="card-heading-group">
        <h2 class="card-heading-title">Beban Belajar Mingguan</h2>
        <span class="card-growth-tag" id="chartActiveSummary">Rabu: 6.2 jam sesi belajar &amp; kuliah</span>
      </div>
      <span class="pill pill-neutral">7 Hari Terakhir</span>
    </div>

    <div class="bar-chart-wrap" aria-label="Grafik jam belajar harian">
      <div class="bar-chart-grid">
        <?php foreach ($weeklyStudyBars as $bar): ?>
          <div
            class="bar-col <?= !empty($bar['active']) ? 'is-active' : '' ?>"
            data-day-label="<?= e($bar['full']) ?>"
            data-hours="<?= e($bar['hours']) ?>"
            title="<?= e($bar['full']) ?>: <?= e($bar['hours']) ?> jam"
          >
            <span class="bar-val-hint"><?= e($bar['hours']) ?>j</span>
            <div
              class="bar-pill <?= $bar['tone'] === 'lime' ? 'is-lime' : ($bar['tone'] === 'orange' ? 'is-orange' : '') ?>"
              style="height: <?= (int) $bar['pct'] ?>%;"
            ></div>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="bar-chart-labels" aria-hidden="true">
        <?php foreach ($weeklyStudyBars as $bar): ?>
          <span class="bar-day-label <?= $bar['tone'] === 'lime' ? 'is-lime' : ($bar['tone'] === 'orange' ? 'is-orange' : '') ?>">
            <?= e($bar['short']) ?>
          </span>
        <?php endforeach; ?>
      </div>
    </div>
  </article>

  <!-- Kanan: Timeline Penugasan & Kuis (Horizontal Pill Bars) -->
  <article class="bento-card">
    <div class="card-header-row">
      <div class="card-heading-group">
        <h2 class="card-heading-title">Timeline Penugasan &amp; Kuis</h2>
        <span class="card-heading-sub">Estimasi durasi &amp; kedekatan tenggat waktu</span>
      </div>
      <a href="index.php?page=tasks" class="btn-secondary btn-sm">Semua Tugas</a>
    </div>

    <?php if (empty($urgentTasks)): ?>
      <div class="empty-state" style="padding: 28px 12px;">
        <div class="empty-state-icon" aria-hidden="true">
          <svg class="ui-icon" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg>
        </div>
        <h3 class="empty-state-title">Semua Tugas Tuntas!</h3>
        <p class="empty-state-desc">Tidak ada tugas aktif yang mendekati deadline saat ini.</p>
        <button type="button" class="btn-lime btn-sm" data-open-modal="quickTaskModal">+ Tambah Tugas Baru</button>
      </div>
    <?php else: ?>
      <div class="timeline-list">
        <?php foreach ($urgentTasks as $idx => $task):
          $hours = (float) ($task['estimated_hours'] ?? 2.0);
          $widthPct = max(24, min(92, (int) round(($hours / 8.0) * 100)));
          $offsetPct = min(100 - $widthPct, $idx * 10);
          $prio = strtolower($task['priority'] ?? 'medium');
          $pillTone = $prio === 'high' ? 'is-orange' : ($prio === 'medium' ? '' : 'is-muted');
        ?>
          <div class="timeline-row">
            <div class="timeline-head">
              <span class="timeline-title"><?= e($task['title']) ?></span>
              <span class="timeline-meta">
                <?= date('d M', strtotime($task['deadline_date'])) ?> • <?= number_format($hours, 1) ?>j
              </span>
            </div>
            <div class="timeline-track">
              <div
                class="timeline-pill <?= $pillTone ?>"
                style="width: <?= $widthPct ?>%; margin-left: <?= $offsetPct ?>%;"
              ></div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </article>
</section>

<!-- ========================================================================
     ROW 3: JADWAL KULIAH HARI INI + REKOMENDASI SMART PLANNER
     ======================================================================== -->
<section class="bento-grid-2" aria-label="Jadwal Hari Ini dan Rekomendasi Smart Planner">
  <!-- Kiri: Jadwal Kuliah Hari Ini -->
  <article class="bento-card">
    <div class="card-header-row">
      <div class="card-heading-group">
        <h2 class="card-heading-title">Jadwal Kuliah Hari Ini</h2>
        <span class="card-heading-sub">Jadwal tetap mingguan (tidak pernah ditimpa Smart Planner)</span>
      </div>
      <a href="index.php?page=academic" class="btn-secondary btn-sm">Lihat Semua</a>
    </div>

    <?php if (empty($todaySchedules)): ?>
      <div class="empty-state" style="padding: 32px 12px;">
        <div class="empty-state-icon" aria-hidden="true">
          <svg class="ui-icon" viewBox="0 0 24 24">
            <rect x="3" y="4" width="18" height="18" rx="2"></rect>
            <line x1="16" y1="2" x2="16" y2="6"></line>
            <line x1="8" y1="2" x2="8" y2="6"></line>
            <line x1="3" y1="10" x2="21" y2="10"></line>
          </svg>
        </div>
        <h3 class="empty-state-title">Tidak Ada Jadwal Kuliah Hari Ini</h3>
        <p class="empty-state-desc">Hari ini bebas jadwal kuliah rutin. Kamu bisa fokus menyelesaikan tugas melalui Smart Planner.</p>
        <a href="index.php?page=planner" class="btn-lime btn-sm">Lihat Rekomendasi Belajar</a>
      </div>
    <?php else: ?>
      <div class="schedule-list">
        <?php
        $accentColors = ['var(--accent-lime)', 'var(--accent-orange)', 'var(--accent-cyan)'];
        foreach ($todaySchedules as $i => $sched):
          $barColor = !empty($sched['color_hex']) ? $sched['color_hex'] : $accentColors[$i % 3];
        ?>
          <div class="schedule-item">
            <div class="schedule-left">
              <span class="schedule-accent" style="background-color: <?= e($barColor) ?>;"></span>
              <div class="schedule-info">
                <span class="schedule-course"><?= e($sched['course_name'] ?? 'Mata Kuliah') ?></span>
                <span class="schedule-meta">
                  <?= e($sched['course_code'] ?? '') ?><?= !empty($sched['room']) ? ' • ' . e($sched['room']) : '' ?>
                </span>
              </div>
            </div>
            <span class="schedule-time-pill">
              <?= e(substr($sched['start_time'], 0, 5)) ?> - <?= e(substr($sched['end_time'], 0, 5)) ?>
            </span>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </article>

  <!-- Kanan: Rekomendasi Smart Planner -->
  <article class="bento-card">
    <div class="card-header-row">
      <div class="card-heading-group">
        <h2 class="card-heading-title">Rekomendasi Smart Planner</h2>
        <span class="card-heading-sub">Alokasi slot waktu luang deterministik berbasis prioritas</span>
      </div>
      <span class="pill pill-lime">Rule-Based Engine</span>
    </div>

    <?php if ($topRec): ?>
      <div class="planner-rec-card <?= ($topRec['priority'] ?? '') === 'high' ? 'is-high' : '' ?>">
        <div class="planner-rec-top">
          <div class="planner-rec-badges">
            <span class="pill pill-lime">
              <?= e($topRec['day_name']) ?> • <?= e($topRec['start_time']) ?> - <?= e($topRec['end_time']) ?> (<?= e($topRec['session_duration_hours']) ?>j)
            </span>
            <span class="pill <?= ($topRec['priority'] ?? '') === 'high' ? 'pill-orange' : 'pill-neutral' ?>">
              Prioritas <?= ucfirst(e($topRec['priority'] ?? 'Medium')) ?>
            </span>
          </div>
        </div>
        <div class="planner-rec-title"><?= e($topRec['task_title']) ?></div>
        <div class="planner-reason-box">
          <strong>Alasan Rekomendasi:</strong> <?= e($topRec['reason']) ?>
        </div>
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
          <a href="index.php?page=planner" class="btn-lime btn-sm">Tinjau &amp; Setujui Jadwal</a>
        </div>
      </div>
    <?php elseif ($topUrgentTask): ?>
      <div class="planner-rec-card <?= ($topUrgentTask['priority'] ?? '') === 'high' ? 'is-high' : '' ?>">
        <div class="planner-rec-top">
          <div class="planner-rec-badges">
            <span class="pill pill-lime">Prioritas Utama Berikutnya</span>
            <span class="pill <?= ($topUrgentTask['priority'] ?? '') === 'high' ? 'pill-orange' : 'pill-neutral' ?>">
              <?= ucfirst(e($topUrgentTask['priority'] ?? 'Medium')) ?> • <?= number_format((float)($topUrgentTask['estimated_hours'] ?? 2.0), 1) ?> Jam
            </span>
          </div>
        </div>
        <div class="planner-rec-title"><?= e($topUrgentTask['title']) ?></div>
        <div class="planner-reason-box">
          <strong>Alasan Prioritas:</strong> Tenggat terdekat pada <strong><?= date('d M Y', strtotime($topUrgentTask['deadline_date'])) ?> pukul <?= e(substr($topUrgentTask['deadline_time'] ?? '23:59', 0, 5)) ?> WIB</strong> dengan estimasi <?= number_format((float)($topUrgentTask['estimated_hours'] ?? 2.0), 1) ?> jam. Buka Smart Planner untuk melihat pemetaan slot kosong tanpa bentrok kuliah.
        </div>
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
          <a href="index.php?page=planner" class="btn-lime btn-sm">Buka Smart Planner</a>
          <a href="index.php?page=tasks" class="btn-secondary btn-sm">Lihat Semua Tugas</a>
        </div>
      </div>
    <?php else: ?>
      <div class="empty-state" style="padding: 32px 12px;">
        <div class="empty-state-icon" aria-hidden="true">
          <svg class="ui-icon" viewBox="0 0 24 24"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>
        </div>
        <h3 class="empty-state-title">Smart Planner Siap Membantu</h3>
        <p class="empty-state-desc">Tambahkan tugas kuliah pertamamu agar Smart Planner dapat menyusun rekomendasi slot belajar terbaik secara otomatis.</p>
        <button type="button" class="btn-lime btn-sm" data-open-modal="quickTaskModal">+ Tambah Tugas</button>
      </div>
    <?php endif; ?>
  </article>
</section>
