<?php
/**
 * views/planner/index.php
 * Halaman Smart Planner (Rekomendasi Belajar Mandiri) — Technical High-Contrast Dark Mode
 * Terhubung penuh dengan TaskController::planner(), updatePreferences(), & storeEvent()
 */

$recommendations = $recommendations ?? [];
$plannerData = $plannerData ?? [];
$preferences = $preferences ?? [];
$rankedTasks = $rankedTasks ?? [];
$daysAhead = (int) ($daysAhead ?? 7);

$studyStart = substr($preferences['study_start_hour'] ?? '08:00:00', 0, 5);
$studyEnd   = substr($preferences['study_end_hour'] ?? '21:00:00', 0, 5);
$maxDaily   = (int) ($preferences['max_daily_study_hours'] ?? 4);
$idealSess  = (int) ($preferences['preferred_session_hours'] ?? 2);
?>

<!-- ========================================================================
     INFO GUARDRAIL BAR & RENTANG WAKTU
     ======================================================================== -->
<section class="card-header-row" aria-label="Kontrol Smart Planner">
  <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
    <span style="font-size: var(--font-size-xs); color: var(--text-secondary); margin-right: 4px;">Rentang Analisis:</span>
    <a href="index.php?page=planner&days=3" class="filter-pill btn-sm <?= $daysAhead === 3 ? 'is-active' : '' ?>">3 Hari</a>
    <a href="index.php?page=planner&days=7" class="filter-pill btn-sm <?= $daysAhead === 7 ? 'is-active' : '' ?>">7 Hari</a>
    <a href="index.php?page=planner&days=14" class="filter-pill btn-sm <?= $daysAhead === 14 ? 'is-active' : '' ?>">14 Hari</a>
  </div>

  <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
    <button type="button" class="btn-secondary" data-open-modal="prefsModal">
      <svg class="ui-icon ui-icon-xs" viewBox="0 0 24 24" aria-hidden="true">
        <circle cx="12" cy="12" r="3"></circle>
        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
      </svg>
      <span>Pengaturan Jam Belajar</span>
    </button>
    <a href="index.php?page=tasks" class="btn-secondary">Kelola Tugas</a>
  </div>
</section>

<!-- ========================================================================
     ROW 1: 4 PREFERENCE & ENGINE KPI CARDS
     ======================================================================== -->
<section class="kpi-grid-4" aria-label="Preferensi Jam Belajar Smart Planner">
  <article class="kpi-card">
    <div class="kpi-top">
      <span class="kpi-label">Rentang Jam Belajar</span>
      <span class="kpi-icon-dot" aria-hidden="true">
        <svg class="ui-icon ui-icon-xs" viewBox="0 0 24 24">
          <circle cx="12" cy="12" r="10"></circle>
          <polyline points="12 6 12 12 16 14"></polyline>
        </svg>
      </span>
    </div>
    <div class="kpi-value is-compact"><?= e($studyStart) ?> - <?= e($studyEnd) ?></div>
    <div class="kpi-footer">
      <span>Jendela aktif harian</span>
    </div>
  </article>

  <article class="kpi-card">
    <div class="kpi-top">
      <span class="kpi-label">Maksimal Harian</span>
      <span class="kpi-icon-dot is-lime" aria-hidden="true">
        <svg class="ui-icon ui-icon-xs" viewBox="0 0 24 24">
          <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
        </svg>
      </span>
    </div>
    <div class="kpi-value is-lime is-compact"><?= $maxDaily ?> Jam / Hari</div>
    <div class="kpi-footer">
      <span>Menjaga ritme anti-burnout</span>
    </div>
  </article>

  <article class="kpi-card">
    <div class="kpi-top">
      <span class="kpi-label">Durasi Sesi Ideal</span>
      <span class="kpi-icon-dot" aria-hidden="true">
        <svg class="ui-icon ui-icon-xs" viewBox="0 0 24 24">
          <rect x="3" y="4" width="18" height="18" rx="2"></rect>
          <line x1="16" y1="2" x2="16" y2="6"></line>
          <line x1="8" y1="2" x2="8" y2="6"></line>
          <line x1="3" y1="10" x2="21" y2="10"></line>
        </svg>
      </span>
    </div>
    <div class="kpi-value is-compact"><?= $idealSess ?> Jam / Sesi</div>
    <div class="kpi-footer">
      <span>Blok fokus mendalam</span>
    </div>
  </article>

  <article class="kpi-card">
    <div class="kpi-top">
      <span class="kpi-label">Tugas Dievaluasi</span>
      <span class="kpi-icon-dot is-orange" aria-hidden="true">
        <svg class="ui-icon ui-icon-xs" viewBox="0 0 24 24">
          <polyline points="9 11 12 14 22 4"></polyline>
          <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
        </svg>
      </span>
    </div>
    <div class="kpi-value is-orange is-compact"><?= count($rankedTasks) ?> Tugas Aktif</div>
    <div class="kpi-footer">
      <span class="pill pill-lime"><?= count($recommendations) ?> Sesi Disarankan</span>
    </div>
  </article>
</section>

<!-- ========================================================================
     ROW 2: REKOMENDASI SESI BELAJAR MANDIRI (DENGAN REASON & PERSETUJUAN)
     ======================================================================== -->
<article class="bento-card">
  <div class="card-header-row">
    <div class="card-heading-group">
      <h2 class="card-heading-title">Rekomendasi Sesi Belajar Mandiri (<?= count($recommendations) ?> Sesi)</h2>
      <span class="card-heading-sub">
        Seluruh rekomendasi bersifat saran — jadwal kuliah tetap tidak pernah diubah tanpa persetujuanmu.
      </span>
    </div>
    <span class="pill pill-lime">Rule-Based Deterministik</span>
  </div>

  <?php if (empty($recommendations)): ?>
    <div class="empty-state">
      <div class="empty-state-icon" aria-hidden="true">
        <svg class="ui-icon" viewBox="0 0 24 24">
          <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
        </svg>
      </div>
      <h3 class="empty-state-title">Belum Ada Rekomendasi Slot Belajar</h3>
      <p class="empty-state-desc">
        <?= e($plannerData['message'] ?? 'Semua tugas telah selesai, atau tidak ditemukan slot luang yang sesuai preferensi jam belajarmu.') ?>
      </p>
      <div style="display: flex; gap: 10px; flex-wrap: wrap; justify-content: center;">
        <button type="button" class="btn-lime" data-open-modal="quickTaskModal">+ Tambah Tugas Baru</button>
        <button type="button" class="btn-secondary" data-open-modal="prefsModal">Sesuaikan Jam Belajar</button>
      </div>
    </div>
  <?php else: ?>
    <div class="planner-rec-list">
      <?php foreach ($recommendations as $rec):
        $isHigh = (($rec['priority'] ?? '') === 'high');
        $prioBadgeClass = $isHigh ? 'pill-orange' : (($rec['priority'] ?? '') === 'medium' ? 'pill-lime' : 'pill-neutral');
      ?>
        <div class="planner-rec-card <?= $isHigh ? 'is-high' : '' ?>">
          <div class="planner-rec-top">
            <div class="planner-rec-badges">
              <span class="pill pill-neutral">
                <?= e($rec['day_name']) ?>, <?= date('d M Y', strtotime($rec['date'])) ?>
              </span>
              <span class="pill pill-lime">
                <?= e($rec['start_time']) ?> - <?= e($rec['end_time']) ?> WIB (<?= e($rec['session_duration_hours']) ?> Jam)
              </span>
              <span class="pill <?= $prioBadgeClass ?>">
                Prioritas <?= ucfirst(e($rec['priority'] ?? 'Medium')) ?>
              </span>
            </div>

            <span style="font-family: var(--font-family-mono); font-size: var(--font-size-xs); font-weight: var(--font-weight-bold); color: var(--accent-lime);">
              Skor Urgensi: <?= e($rec['urgency_score']) ?>
            </span>
          </div>

          <div class="planner-rec-title">
            <?= e($rec['task_title']) ?>
            <span class="planner-rec-course">• <?= e($rec['course_name'] ?? 'Umum') ?></span>
          </div>

          <div class="planner-rec-body">
            <!-- Kotak Alasan Transparan (Reason Box) -->
            <div class="planner-reason-box">
              <strong>Alasan Rekomendasi:</strong> <?= e($rec['reason']) ?>
            </div>

            <!-- Form Persetujuan Eksplisit ke Kalender (POST ke TaskController::storeEvent) -->
            <form method="POST" action="index.php?page=calendar&action=store_event" style="flex-shrink: 0;">
              <input type="hidden" name="title" value="Belajar: <?= e($rec['task_title']) ?>">
              <input type="hidden" name="category" value="study_session">
              <input type="hidden" name="event_date" value="<?= e($rec['date']) ?>">
              <input type="hidden" name="start_time" value="<?= e($rec['start_time']) ?>">
              <input type="hidden" name="end_time" value="<?= e($rec['end_time']) ?>">
              <input type="hidden" name="location" value="Sesi Belajar Mandiri (Smart Planner)">
              <input type="hidden" name="notes" value="<?= e($rec['reason']) ?>">
              <button
                type="submit"
                class="btn-lime"
                onclick="return confirm('Simpan sesi belajar <?= e($rec['day_name']) ?> <?= e($rec['start_time']) ?>-<?= e($rec['end_time']) ?> WIB ke Kalender Kegiatanmu?');"
              >
                <svg class="ui-icon ui-icon-xs" viewBox="0 0 24 24" aria-hidden="true">
                  <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
                <span>Setujui &amp; Simpan ke Kalender</span>
              </button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</article>

<!-- ========================================================================
     ROW 3: ANTREAN PRIORITAS TUGAS BERDASARKAN SKOR URGENSI (U)
     ======================================================================== -->
<article class="bento-card">
  <div class="card-header-row">
    <div class="card-heading-group">
      <h2 class="card-heading-title">Antrean Prioritas Tugas Berdasarkan Skor Urgensi (U)</h2>
      <span class="card-heading-sub">Rumus transparan: U = (Estimasi Jam / Sisa Jam Deadline) &times; Bobot Prioritas &times; 100</span>
    </div>
    <span class="pill pill-neutral"><?= count($rankedTasks) ?> Tugas Aktif</span>
  </div>

  <?php if (empty($rankedTasks)): ?>
    <div class="empty-state" style="padding: 28px 12px;">
      <p class="empty-state-desc" style="margin-bottom: 0;">Tidak ada tugas aktif dalam antrean evaluasi saat ini.</p>
    </div>
  <?php else: ?>
    <div class="data-table-wrap">
      <table class="data-table">
        <thead>
          <tr>
            <th style="width: 72px;">Rank</th>
            <th>Tugas Kuliah</th>
            <th>Mata Kuliah</th>
            <th>Sisa Waktu</th>
            <th>Estimasi</th>
            <th>Prioritas</th>
            <th style="text-align: right;">Skor Urgensi (U)</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rankedTasks as $idx => $rt):
            $rtPrioClass = match ($rt['priority'] ?? 'medium') {
                'high'   => 'pill-orange',
                'medium' => 'pill-lime',
                default  => 'pill-neutral',
            };
          ?>
            <tr>
              <td style="font-family: var(--font-family-mono); font-weight: var(--font-weight-bold); color: <?= $idx === 0 ? 'var(--accent-lime)' : 'var(--text-secondary)' ?>;">
                #<?= $idx + 1 ?>
              </td>
              <td>
                <div class="task-title-text"><?= e($rt['title']) ?></div>
              </td>
              <td>
                <span class="course-chip">
                  <span class="course-dot" style="background-color: <?= e($rt['color_hex'] ?? '#C8F538') ?>;"></span>
                  <span><?= e($rt['course_name'] ?? 'Umum') ?></span>
                </span>
              </td>
              <td>
                <?php if (!empty($rt['is_overdue'])): ?>
                  <span class="pill pill-danger">Lewat Deadline</span>
                <?php else: ?>
                  <span style="font-family: var(--font-family-mono); font-size: var(--font-size-xs);">
                    <?= number_format((float) ($rt['remaining_hours'] ?? 0), 1) ?> Jam lagi
                  </span>
                <?php endif; ?>
              </td>
              <td style="font-family: var(--font-family-mono); font-size: var(--font-size-xs);">
                <?= number_format((float) ($rt['estimated_hours'] ?? 2.0), 1) ?> Jam
              </td>
              <td>
                <span class="pill <?= $rtPrioClass ?>"><?= ucfirst(e($rt['priority'] ?? 'medium')) ?></span>
              </td>
              <td style="text-align: right; font-family: var(--font-family-mono); font-weight: var(--font-weight-bold); color: <?= $idx === 0 ? 'var(--accent-orange)' : 'var(--accent-lime)' ?>;">
                <?= e($rt['urgency_score']) ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</article>

<!-- ========================================================================
     MODAL PENGATURAN PREFERENSI JAM BELAJAR (POST index.php?page=planner)
     ======================================================================== -->
<div class="modal-backdrop" id="prefsModal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="prefsModalTitle">
  <div class="modal-card">
    <div class="modal-header">
      <div>
        <h2 class="modal-title" id="prefsModalTitle">Pengaturan Jam Belajar</h2>
        <p class="modal-subtitle">Sesuaikan rentang waktu belajar harian untuk perhitungan Smart Planner</p>
      </div>
      <button type="button" class="modal-close-btn" data-close-modal="prefsModal" aria-label="Tutup">&times;</button>
    </div>

    <form method="POST" action="index.php?page=planner">
      <div class="modal-body">
        <div class="form-grid-2">
          <div class="form-group">
            <label for="prefStartHour" class="form-label">Jam Mulai Belajar</label>
            <input type="time" id="prefStartHour" name="study_start_hour" class="form-input" value="<?= e($studyStart) ?>" required>
          </div>
          <div class="form-group">
            <label for="prefEndHour" class="form-label">Jam Selesai Belajar</label>
            <input type="time" id="prefEndHour" name="study_end_hour" class="form-input" value="<?= e($studyEnd) ?>" required>
          </div>
        </div>

        <div class="form-grid-2">
          <div class="form-group" style="margin-bottom: 0;">
            <label for="prefMaxDaily" class="form-label">Maksimal Belajar / Hari (Jam)</label>
            <input type="number" id="prefMaxDaily" name="max_daily_study_hours" class="form-input" min="1" max="16" value="<?= $maxDaily ?>" required>
          </div>
          <div class="form-group" style="margin-bottom: 0;">
            <label for="prefIdealSession" class="form-label">Durasi Ideal / Sesi (Jam)</label>
            <input type="number" id="prefIdealSession" name="preferred_session_hours" class="form-input" min="1" max="8" value="<?= $idealSess ?>" required>
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn-secondary" data-close-modal="prefsModal">Batal</button>
        <button type="submit" class="btn-lime">Simpan Preferensi</button>
      </div>
    </form>
  </div>
</div>
