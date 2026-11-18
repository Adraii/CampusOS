<?php
/**
 * views/tasks/index.php
 * Halaman Manajemen Tugas & Prioritas — Technical High-Contrast Dark Mode
 * Terhubung penuh dengan kontrak variabel & aksi POST di app/controllers/TaskController.php
 */

$tasks = $tasks ?? [];
$courses = $courses ?? [];
$counts = $counts ?? [];
$filters = $filters ?? [];
$urgentTasks = $urgentTasks ?? [];
$previewRecommendations = $previewRecommendations ?? [];

$totalTasks      = (int) ($counts['total'] ?? 0);
$pendingTasks    = (int) ($counts['pending'] ?? 0);
$inProgressTasks = (int) ($counts['in_progress'] ?? 0);
$doneTasks       = (int) ($counts['done'] ?? 0);
$overdueTasks    = (int) ($counts['overdue'] ?? 0);
?>

<?php if ($overdueTasks > 0): ?>
  <div class="alert-banner alert-warning" role="alert">
    <div class="alert-banner-left">
      <svg class="ui-icon ui-icon-sm" viewBox="0 0 24 24" aria-hidden="true">
        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
        <line x1="12" y1="9" x2="12" y2="13"></line>
        <line x1="12" y1="17" x2="12.01" y2="17"></line>
      </svg>
      <span>Perhatian: Ada <strong><?= $overdueTasks ?> tugas</strong> yang telah melewati tenggat waktu. Buka Smart Planner untuk menjadwalkan penyelesaian segera.</span>
    </div>
    <a href="index.php?page=planner" class="btn-orange btn-sm">Buka Smart Planner</a>
  </div>
<?php endif; ?>

<!-- ========================================================================
     ROW 1: 4 METRIC KPI CARDS TUGAS
     ======================================================================== -->
<section class="kpi-grid-4" aria-label="Ringkasan Status Tugas">
  <article class="kpi-card">
    <div class="kpi-top">
      <span class="kpi-label">Total Tugas</span>
      <span class="kpi-icon-dot" aria-hidden="true">
        <svg class="ui-icon ui-icon-xs" viewBox="0 0 24 24">
          <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path>
          <rect x="8" y="2" width="8" height="4" rx="1"></rect>
        </svg>
      </span>
    </div>
    <div class="kpi-value"><?= $totalTasks ?> Tugas</div>
    <div class="kpi-footer">
      <span>Semua status tercatat</span>
    </div>
  </article>

  <article class="kpi-card">
    <div class="kpi-top">
      <span class="kpi-label">Menunggu (Pending)</span>
      <span class="kpi-icon-dot is-orange" aria-hidden="true">
        <svg class="ui-icon ui-icon-xs" viewBox="0 0 24 24">
          <circle cx="12" cy="12" r="10"></circle>
          <polyline points="12 6 12 12 16 14"></polyline>
        </svg>
      </span>
    </div>
    <div class="kpi-value is-orange"><?= $pendingTasks ?> Pending</div>
    <div class="kpi-footer">
      <span class="pill pill-orange"><?= $overdueTasks > 0 ? $overdueTasks . ' Terlambat' : 'Belum dimulai' ?></span>
    </div>
  </article>

  <article class="kpi-card">
    <div class="kpi-top">
      <span class="kpi-label">Sedang Dikerjakan</span>
      <span class="kpi-icon-dot" aria-hidden="true">
        <svg class="ui-icon ui-icon-xs" viewBox="0 0 24 24">
          <polyline points="23 4 23 10 17 10"></polyline>
          <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path>
        </svg>
      </span>
    </div>
    <div class="kpi-value"><?= $inProgressTasks ?> Berjalan</div>
    <div class="kpi-footer">
      <span class="pill pill-cyan">In Progress</span>
    </div>
  </article>

  <article class="kpi-card">
    <div class="kpi-top">
      <span class="kpi-label">Tugas Selesai</span>
      <span class="kpi-icon-dot is-lime" aria-hidden="true">
        <svg class="ui-icon ui-icon-xs" viewBox="0 0 24 24">
          <polyline points="20 6 9 17 4 12"></polyline>
        </svg>
      </span>
    </div>
    <div class="kpi-value is-lime"><?= $doneTasks ?> Selesai</div>
    <div class="kpi-footer">
      <span class="pill pill-lime">Tuntas</span>
      <span><?= $totalTasks > 0 ? round(($doneTasks / $totalTasks) * 100) : 0 ?>% selesai</span>
    </div>
  </article>
</section>

<!-- ========================================================================
     ROW 2: TIMELINE PENUGASAN & PREVIEW REKOMENDASI SMART PLANNER
     ======================================================================== -->
<?php if (!empty($urgentTasks) || !empty($previewRecommendations)): ?>
<section class="bento-grid-2" aria-label="Timeline Tugas dan Preview Smart Planner">
  <!-- Horizontal Timeline Pill Bars -->
  <article class="bento-card">
    <div class="card-header-row">
      <div class="card-heading-group">
        <h2 class="card-heading-title">Timeline Penugasan Mendesak</h2>
        <span class="card-heading-sub">Visualisasi durasi estimasi &amp; tingkat prioritas</span>
      </div>
      <span class="pill pill-orange">Prioritas Aktif</span>
    </div>

    <?php if (empty($urgentTasks)): ?>
      <div class="empty-state" style="padding: 20px 12px;">
        <p class="empty-state-desc" style="margin-bottom: 0;">Tidak ada tugas mendesak saat ini.</p>
      </div>
    <?php else: ?>
      <div class="timeline-list">
        <?php foreach ($urgentTasks as $idx => $ut):
          $hrs = (float) ($ut['estimated_hours'] ?? 2.0);
          $wPct = max(22, min(92, (int) round(($hrs / 8.0) * 100)));
          $offPct = min(100 - $wPct, $idx * 8);
          $utTone = ($ut['priority'] ?? '') === 'high' ? 'is-orange' : (($ut['priority'] ?? '') === 'medium' ? '' : 'is-muted');
        ?>
          <div class="timeline-row">
            <div class="timeline-head">
              <span class="timeline-title"><?= e($ut['title']) ?></span>
              <span class="timeline-meta"><?= date('d M', strtotime($ut['deadline_date'])) ?> • <?= number_format($hrs, 1) ?> Jam</span>
            </div>
            <div class="timeline-track">
              <div class="timeline-pill <?= $utTone ?>" style="width: <?= $wPct ?>%; margin-left: <?= $offPct ?>%;"></div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </article>

  <!-- Preview Rekomendasi Smart Planner -->
  <article class="bento-card">
    <div class="card-header-row">
      <div class="card-heading-group">
        <h2 class="card-heading-title">Saran Slot Belajar Terdekat</h2>
        <span class="card-heading-sub">Dihitung deterministik oleh SmartPlannerService</span>
      </div>
      <a href="index.php?page=planner" class="btn-lime btn-sm">Buka Smart Planner</a>
    </div>

    <?php if (empty($previewRecommendations)): ?>
      <div class="empty-state" style="padding: 20px 12px;">
        <p class="empty-state-desc" style="margin-bottom: 0;">Belum ada rekomendasi sesi belajar. Tambahkan tugas aktif atau sesuaikan jam belajar di Smart Planner.</p>
      </div>
    <?php else: ?>
      <div class="schedule-list">
        <?php foreach ($previewRecommendations as $rec): ?>
          <div class="schedule-item">
            <div class="schedule-left">
              <span class="schedule-accent" style="background-color: <?= ($rec['priority'] ?? '') === 'high' ? 'var(--accent-orange)' : 'var(--accent-lime)' ?>;"></span>
              <div class="schedule-info">
                <span class="schedule-course"><?= e($rec['task_title']) ?></span>
                <span class="schedule-meta"><?= e($rec['day_name']) ?>, <?= date('d M', strtotime($rec['date'])) ?> • Skor <?= e($rec['urgency_score']) ?></span>
              </div>
            </div>
            <span class="schedule-time-pill"><?= e($rec['start_time']) ?> - <?= e($rec['end_time']) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </article>
</section>
<?php endif; ?>

<!-- ========================================================================
     ROW 3: FILTER & PENCARIAN TUGAS
     ======================================================================== -->
<section class="bento-card" style="padding: 18px 22px;" aria-label="Filter Daftar Tugas">
  <form method="GET" action="index.php" class="filter-bar">
    <input type="hidden" name="page" value="tasks">

    <div class="filter-search">
      <input
        type="text"
        name="search"
        class="form-input"
        value="<?= e($filters['search'] ?? '') ?>"
        placeholder="Cari judul atau deskripsi tugas..."
        aria-label="Cari tugas"
      >
    </div>

    <div>
      <select name="status" class="form-select" aria-label="Filter Status">
        <option value="">Semua Status</option>
        <option value="pending" <?= ($filters['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
        <option value="in_progress" <?= ($filters['status'] ?? '') === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
        <option value="done" <?= ($filters['status'] ?? '') === 'done' ? 'selected' : '' ?>>Done (Selesai)</option>
      </select>
    </div>

    <div>
      <select name="priority" class="form-select" aria-label="Filter Prioritas">
        <option value="">Semua Prioritas</option>
        <option value="high" <?= ($filters['priority'] ?? '') === 'high' ? 'selected' : '' ?>>High (Mendesak)</option>
        <option value="medium" <?= ($filters['priority'] ?? '') === 'medium' ? 'selected' : '' ?>>Medium (Normal)</option>
        <option value="low" <?= ($filters['priority'] ?? '') === 'low' ? 'selected' : '' ?>>Low (Santai)</option>
      </select>
    </div>

    <div>
      <select name="course_id" class="form-select" aria-label="Filter Mata Kuliah">
        <option value="">Semua Mata Kuliah</option>
        <?php foreach ($courses as $c): ?>
          <option value="<?= (int) $c['id'] ?>" <?= ((int) ($filters['course_id'] ?? 0)) === (int) $c['id'] ? 'selected' : '' ?>>
            <?= e($c['course_code']) ?> - <?= e($c['course_name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <button type="submit" class="btn-lime">Terapkan</button>
    <a href="index.php?page=tasks" class="btn-secondary">Reset</a>
  </form>
</section>

<!-- ========================================================================
     ROW 4: DAFTAR TUGAS KULIAH (DATABASE NYATA)
     ======================================================================== -->
<article class="bento-card">
  <div class="card-header-row">
    <div class="card-heading-group">
      <h2 class="card-heading-title">Daftar Tugas Kuliah (<?= count($tasks) ?>)</h2>
      <span class="card-heading-sub">Klik kotak centang untuk menandai selesai secara langsung</span>
    </div>
    <button type="button" class="btn-lime btn-sm" data-open-modal="taskCreateModal">+ Tambah Tugas Baru</button>
  </div>

  <?php if (empty($tasks)): ?>
    <div class="empty-state">
      <div class="empty-state-icon" aria-hidden="true">
        <svg class="ui-icon" viewBox="0 0 24 24">
          <polyline points="9 11 12 14 22 4"></polyline>
          <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
        </svg>
      </div>
      <h3 class="empty-state-title">Belum Ada Tugas Ditemukan</h3>
      <p class="empty-state-desc">Tidak ada tugas yang cocok dengan filter aktif, atau semua tugasmu sudah berhasil diselesaikan.</p>
      <div style="display: flex; gap: 10px; flex-wrap: wrap; justify-content: center;">
        <button type="button" class="btn-lime" data-open-modal="taskCreateModal">+ Tambah Tugas Pertama</button>
        <?php if (!empty($filters['search']) || !empty($filters['status']) || !empty($filters['priority']) || !empty($filters['course_id'])): ?>
          <a href="index.php?page=tasks" class="btn-secondary">Hapus Filter</a>
        <?php endif; ?>
      </div>
    </div>
  <?php else: ?>
    <div class="data-table-wrap">
      <table class="data-table">
        <thead>
          <tr>
            <th style="width: 52px;">Selesai</th>
            <th>Judul Tugas</th>
            <th>Mata Kuliah</th>
            <th>Deadline</th>
            <th>Estimasi</th>
            <th>Prioritas</th>
            <th>Status</th>
            <th style="text-align: right;">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($tasks as $task):
            $isDone = ($task['status'] === 'done');
            $deadlineTs = strtotime(($task['deadline_date'] ?? '') . ' ' . ($task['deadline_time'] ?? '23:59:00'));
            $isOverdue = !$isDone && $deadlineTs < time();

            $prioPillClass = match ($task['priority'] ?? 'medium') {
                'high'   => 'pill-orange',
                'medium' => 'pill-lime',
                default  => 'pill-neutral',
            };

            $statusLabel = match ($task['status'] ?? 'pending') {
                'done'        => 'Selesai',
                'in_progress' => 'Dikerjakan',
                default       => 'Pending',
            };
            $statusPillClass = match ($task['status'] ?? 'pending') {
                'done'        => 'pill-success',
                'in_progress' => 'pill-cyan',
                default       => 'pill-neutral',
            };

            $editPayload = json_encode([
                'id'              => (int) $task['id'],
                'course_id'       => $task['course_id'],
                'title'           => $task['title'],
                'description'     => $task['description'] ?? '',
                'deadline_date'   => $task['deadline_date'],
                'deadline_time'   => $task['deadline_time'] ?? '23:59:00',
                'estimated_hours' => (float) ($task['estimated_hours'] ?? 2.0),
                'priority'        => $task['priority'] ?? 'medium',
                'status'          => $task['status'] ?? 'pending',
            ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
          ?>
            <tr class="<?= $isDone ? 'is-done' : '' ?>">
              <td>
                <form method="POST" action="index.php?page=tasks&action=update_status" style="display: inline;">
                  <input type="hidden" name="id" value="<?= (int) $task['id'] ?>">
                  <input type="hidden" name="status" value="<?= $isDone ? 'pending' : 'done' ?>">
                  <button
                    type="submit"
                    class="status-toggle-btn <?= $isDone ? 'is-checked' : '' ?>"
                    title="<?= $isDone ? 'Tandai Belum Selesai' : 'Tandai Selesai' ?>"
                    aria-label="<?= $isDone ? 'Tandai Belum Selesai' : 'Tandai Selesai' ?>"
                  >
                    <?php if ($isDone): ?>
                      <svg class="ui-icon ui-icon-xs" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    <?php endif; ?>
                  </button>
                </form>
              </td>

              <td>
                <div class="task-title-text"><?= e($task['title']) ?></div>
                <?php if (!empty($task['description'])): ?>
                  <div class="task-desc-sub"><?= e($task['description']) ?></div>
                <?php endif; ?>
              </td>

              <td>
                <span class="course-chip">
                  <span class="course-dot" style="background-color: <?= e($task['color_hex'] ?? '#C8F538') ?>;"></span>
                  <span><?= e($task['course_name'] ?? 'Umum') ?></span>
                </span>
              </td>

              <td>
                <div style="font-weight: var(--font-weight-medium); color: <?= $isOverdue ? 'var(--accent-danger)' : 'var(--text-primary)' ?>;">
                  <?= date('d M Y', strtotime($task['deadline_date'])) ?>
                </div>
                <div style="font-size: var(--font-size-2xs); color: <?= $isOverdue ? 'var(--accent-danger)' : 'var(--text-secondary)' ?>;">
                  <?= e(substr($task['deadline_time'] ?? '23:59', 0, 5)) ?> WIB<?= $isOverdue ? ' • Terlambat' : '' ?>
                </div>
              </td>

              <td>
                <span style="font-family: var(--font-family-mono); font-size: var(--font-size-xs);">
                  <?= number_format((float) ($task['estimated_hours'] ?? 2.0), 1) ?> Jam
                </span>
              </td>

              <td>
                <span class="pill <?= $prioPillClass ?>"><?= ucfirst(e($task['priority'] ?? 'medium')) ?></span>
              </td>

              <td>
                <span class="pill <?= $statusPillClass ?>"><?= e($statusLabel) ?></span>
              </td>

              <td style="text-align: right;">
                <div class="table-actions-cell">
                  <button
                    type="button"
                    class="btn-ghost-icon"
                    data-edit-task="<?= e($editPayload) ?>"
                    title="Edit Tugas"
                    aria-label="Edit Tugas"
                  >
                    <svg class="ui-icon ui-icon-xs" viewBox="0 0 24 24">
                      <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                      <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                    </svg>
                  </button>

                  <a
                    href="index.php?page=tasks&action=delete&id=<?= (int) $task['id'] ?>"
                    class="btn-danger-ghost"
                    onclick="return confirm('Hapus tugas \'<?= e($task['title']) ?>\' secara permanen?');"
                  >
                    Hapus
                  </a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</article>

<!-- ========================================================================
     MODAL 1: TAMBAH TUGAS BARU (POST index.php?page=tasks&action=store)
     ======================================================================== -->
<div class="modal-backdrop" id="taskCreateModal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="taskCreateTitle">
  <div class="modal-card">
    <div class="modal-header">
      <div>
        <h2 class="modal-title" id="taskCreateTitle">Tambah Tugas Baru</h2>
        <p class="modal-subtitle">Isi detail deadline &amp; estimasi jam untuk prioritas Smart Planner</p>
      </div>
      <button type="button" class="modal-close-btn" data-close-modal="taskCreateModal" aria-label="Tutup">&times;</button>
    </div>

    <form method="POST" action="index.php?page=tasks&action=store">
      <div class="modal-body">
        <div class="form-group">
          <label for="createTaskTitle" class="form-label">Judul Tugas *</label>
          <input type="text" id="createTaskTitle" name="title" class="form-input" placeholder="Contoh: Laporan Praktikum Normalisasi 3NF" required>
        </div>

        <div class="form-group">
          <label for="createTaskCourse" class="form-label">Mata Kuliah</label>
          <select id="createTaskCourse" name="course_id" class="form-select">
            <option value="">-- Umum / Tanpa Mata Kuliah --</option>
            <?php foreach ($courses as $c): ?>
              <option value="<?= (int) $c['id'] ?>"><?= e($c['course_code']) ?> - <?= e($c['course_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-grid-2">
          <div class="form-group">
            <label for="createTaskDate" class="form-label">Tanggal Deadline *</label>
            <input type="date" id="createTaskDate" name="deadline_date" class="form-input" value="<?= date('Y-m-d', strtotime('+2 days')) ?>" required>
          </div>
          <div class="form-group">
            <label for="createTaskTime" class="form-label">Jam Deadline</label>
            <input type="time" id="createTaskTime" name="deadline_time" class="form-input" value="23:59">
          </div>
        </div>

        <div class="form-grid-2">
          <div class="form-group">
            <label for="createTaskHours" class="form-label">Estimasi Durasi (Jam)</label>
            <input type="number" id="createTaskHours" name="estimated_hours" class="form-input" step="0.5" min="0.5" max="40" value="2.0">
          </div>
          <div class="form-group">
            <label for="createTaskPriority" class="form-label">Prioritas</label>
            <select id="createTaskPriority" name="priority" class="form-select">
              <option value="low">Low (Santai)</option>
              <option value="medium" selected>Medium (Normal)</option>
              <option value="high">High (Mendesak)</option>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label for="createTaskStatus" class="form-label">Status Awal</label>
          <select id="createTaskStatus" name="status" class="form-select">
            <option value="pending" selected>Pending (Menunggu)</option>
            <option value="in_progress">In Progress (Sedang Dikerjakan)</option>
            <option value="done">Done (Selesai)</option>
          </select>
        </div>

        <div class="form-group" style="margin-bottom: 0;">
          <label for="createTaskDesc" class="form-label">Catatan / Instruksi</label>
          <textarea id="createTaskDesc" name="description" class="form-textarea" placeholder="Detail instruksi pengerjaan, link referensi, atau anggota kelompok..."></textarea>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn-secondary" data-close-modal="taskCreateModal">Batal</button>
        <button type="submit" class="btn-lime">Simpan Tugas</button>
      </div>
    </form>
  </div>
</div>

<!-- ========================================================================
     MODAL 2: EDIT TUGAS (POST index.php?page=tasks&action=update)
     ======================================================================== -->
<div class="modal-backdrop" id="taskEditModal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="taskEditTitle">
  <div class="modal-card">
    <div class="modal-header">
      <div>
        <h2 class="modal-title" id="taskEditTitle">Edit Detail Tugas</h2>
        <p class="modal-subtitle">Perbarui informasi deadline, estimasi durasi, atau status pengerjaan</p>
      </div>
      <button type="button" class="modal-close-btn" data-close-modal="taskEditModal" aria-label="Tutup">&times;</button>
    </div>

    <form method="POST" action="index.php?page=tasks&action=update" id="taskEditForm">
      <input type="hidden" name="id" id="editTaskId" value="">
      <div class="modal-body">
        <div class="form-group">
          <label for="editTaskTitle" class="form-label">Judul Tugas *</label>
          <input type="text" id="editTaskTitle" name="title" class="form-input" required>
        </div>

        <div class="form-group">
          <label for="editTaskCourseId" class="form-label">Mata Kuliah</label>
          <select id="editTaskCourseId" name="course_id" class="form-select">
            <option value="">-- Umum / Tanpa Mata Kuliah --</option>
            <?php foreach ($courses as $c): ?>
              <option value="<?= (int) $c['id'] ?>"><?= e($c['course_code']) ?> - <?= e($c['course_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-grid-2">
          <div class="form-group">
            <label for="editTaskDeadlineDate" class="form-label">Tanggal Deadline *</label>
            <input type="date" id="editTaskDeadlineDate" name="deadline_date" class="form-input" required>
          </div>
          <div class="form-group">
            <label for="editTaskDeadlineTime" class="form-label">Jam Deadline</label>
            <input type="time" id="editTaskDeadlineTime" name="deadline_time" class="form-input">
          </div>
        </div>

        <div class="form-grid-2">
          <div class="form-group">
            <label for="editTaskEstimatedHours" class="form-label">Estimasi Durasi (Jam)</label>
            <input type="number" id="editTaskEstimatedHours" name="estimated_hours" class="form-input" step="0.5" min="0.5" max="40">
          </div>
          <div class="form-group">
            <label for="editTaskPriority" class="form-label">Prioritas</label>
            <select id="editTaskPriority" name="priority" class="form-select">
              <option value="low">Low (Santai)</option>
              <option value="medium">Medium (Normal)</option>
              <option value="high">High (Mendesak)</option>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label for="editTaskStatus" class="form-label">Status</label>
          <select id="editTaskStatus" name="status" class="form-select">
            <option value="pending">Pending (Menunggu)</option>
            <option value="in_progress">In Progress (Sedang Dikerjakan)</option>
            <option value="done">Done (Selesai)</option>
          </select>
        </div>

        <div class="form-group" style="margin-bottom: 0;">
          <label for="editTaskDescription" class="form-label">Catatan / Instruksi</label>
          <textarea id="editTaskDescription" name="description" class="form-textarea"></textarea>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn-secondary" data-close-modal="taskEditModal">Batal</button>
        <button type="submit" class="btn-lime">Simpan Perubahan</button>
      </div>
    </form>
  </div>
</div>
