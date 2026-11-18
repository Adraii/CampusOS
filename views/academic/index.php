<?php
/**
 * views/academic/index.php
 * Manajemen Akademik & Jadwal Kuliah Tetap — Technical High-Contrast / Daylight Hybrid SSR
 * Terhubung 100% dengan AcademicController.php (Courses & Class Schedules)
 */

$courses = $courses ?? [];
$weeklySchedules = $weeklySchedules ?? [];
$groupedSchedules = $groupedSchedules ?? [
    1 => [], 2 => [], 3 => [], 4 => [], 5 => [], 6 => [], 7 => []
];

// Perhitungan ringkasan akademik
$totalCourses = count($courses);
$totalCredits = 0;
foreach ($courses as $c) {
    $totalCredits += (int) ($c['credits'] ?? 0);
}
$totalScheduleSlots = count($weeklySchedules);
$todayDayOfWeek = (int) date('N');
$todaySchedulesCount = count($groupedSchedules[$todayDayOfWeek] ?? []);

$dayNames = [
    1 => 'Senin',
    2 => 'Selasa',
    3 => 'Rabu',
    4 => 'Kamis',
    5 => 'Jumat',
    6 => 'Sabtu',
    7 => 'Minggu',
];
?>

<!-- ========================================================================
     ROW 1: 4 METRIC KPI CARDS AKADEMIK
     ======================================================================== -->
<section class="kpi-grid-4" aria-label="Ringkasan Beban Studi Akademik">
  <article class="kpi-card">
    <div class="kpi-top">
      <span class="kpi-label">Mata Kuliah Aktif</span>
      <span class="kpi-icon-dot is-lime" aria-hidden="true">
        <svg class="ui-icon ui-icon-xs" viewBox="0 0 24 24">
          <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
          <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
        </svg>
      </span>
    </div>
    <div class="kpi-value is-lime"><?= $totalCourses ?> MK</div>
    <div class="kpi-footer">
      <span>Semester <?= e($user['semester'] ?? 1) ?> aktif</span>
    </div>
  </article>

  <article class="kpi-card">
    <div class="kpi-top">
      <span class="kpi-label">Total Beban SKS</span>
      <span class="kpi-icon-dot" aria-hidden="true">
        <svg class="ui-icon ui-icon-xs" viewBox="0 0 24 24">
          <circle cx="12" cy="12" r="10"></circle>
          <polyline points="12 6 12 12 16 14"></polyline>
        </svg>
      </span>
    </div>
    <div class="kpi-value"><?= $totalCredits ?> SKS</div>
    <div class="kpi-footer">
      <span class="pill <?= $totalCredits > 20 ? 'pill-orange' : 'pill-lime' ?>"><?= $totalCredits > 20 ? 'Beban Penuh' : 'Beban Standar' ?></span>
    </div>
  </article>

  <article class="kpi-card">
    <div class="kpi-top">
      <span class="kpi-label">Sesi Kuliah Mingguan</span>
      <span class="kpi-icon-dot" aria-hidden="true">
        <svg class="ui-icon ui-icon-xs" viewBox="0 0 24 24">
          <rect x="3" y="4" width="18" height="18" rx="2"></rect>
          <line x1="16" y1="2" x2="16" y2="6"></line>
          <line x1="8" y1="2" x2="8" y2="6"></line>
          <line x1="3" y1="10" x2="21" y2="10"></line>
        </svg>
      </span>
    </div>
    <div class="kpi-value"><?= $totalScheduleSlots ?> Sesi</div>
    <div class="kpi-footer">
      <span>Terjadwal rutin</span>
    </div>
  </article>

  <article class="kpi-card">
    <div class="kpi-top">
      <span class="kpi-label">Kuliah Hari Ini (<?= e($dayNames[$todayDayOfWeek] ?? '') ?>)</span>
      <span class="kpi-icon-dot is-orange" aria-hidden="true">
        <svg class="ui-icon ui-icon-xs" viewBox="0 0 24 24">
          <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
        </svg>
      </span>
    </div>
    <div class="kpi-value is-orange"><?= $todaySchedulesCount ?> Kelas</div>
    <div class="kpi-footer">
      <span class="pill <?= $todaySchedulesCount > 0 ? 'pill-orange' : 'pill-neutral' ?>"><?= $todaySchedulesCount > 0 ? 'Ada Perkuliahan' : 'Libur Kuliah' ?></span>
    </div>
  </article>
</section>

<!-- ========================================================================
     ROW 2: ACTION BAR
     ======================================================================== -->
<section class="card-header-row" aria-label="Aksi Manajemen Akademik" style="margin-top: 8px;">
  <div class="card-heading-group">
    <h2 class="card-heading-title">Daftar Mata Kuliah Aktif</h2>
    <span class="card-heading-sub">Kelola mata kuliah dan sesi perkuliahan tetap tanpa konflik</span>
  </div>
  <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
    <button type="button" class="btn-secondary" data-open-modal="scheduleCreateModal">
      <svg class="ui-icon ui-icon-xs" viewBox="0 0 24 24" aria-hidden="true">
        <line x1="12" y1="5" x2="12" y2="19"></line>
        <line x1="5" y1="12" x2="19" y2="12"></line>
      </svg>
      <span>Tambah Jadwal Kuliah</span>
    </button>
    <button type="button" class="btn-lime" data-open-modal="courseCreateModal">
      <svg class="ui-icon ui-icon-xs" viewBox="0 0 24 24" aria-hidden="true">
        <line x1="12" y1="5" x2="12" y2="19"></line>
        <line x1="5" y1="12" x2="19" y2="12"></line>
      </svg>
      <span>Tambah Mata Kuliah</span>
    </button>
  </div>
</section>

<!-- ========================================================================
     ROW 3: DAFTAR MATA KULIAH AKTIF (CARD GRID)
     ======================================================================== -->
<?php if (empty($courses)): ?>
  <article class="bento-card">
    <div class="empty-state">
      <div class="empty-state-icon" aria-hidden="true">
        <svg class="ui-icon" viewBox="0 0 24 24">
          <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
          <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
        </svg>
      </div>
      <h3 class="empty-state-title">Belum Ada Mata Kuliah</h3>
      <p class="empty-state-desc">Tambahkan mata kuliah semester ini untuk mulai menyusun jadwal dan menghubungkan tugas kuliah.</p>
      <button type="button" class="btn-lime" data-open-modal="courseCreateModal">+ Tambah Mata Kuliah Pertama</button>
    </div>
  </article>
<?php else: ?>
  <section class="course-grid" aria-label="Daftar Mata Kuliah">
    <?php foreach ($courses as $c): ?>
      <article class="course-card">
        <div class="course-card-header">
          <div style="display: flex; align-items: center; gap: 12px; min-width: 0;">
            <span class="course-color-bar" style="background-color: <?= e($c['color_hex'] ?? '#3B82F6') ?>;"></span>
            <div style="min-width: 0;">
              <span class="pill pill-neutral" style="font-size: var(--font-size-2xs); font-weight: var(--font-weight-bold); margin-bottom: 4px; display: inline-block;">
                <?= e($c['course_code']) ?>
              </span>
              <h3 style="font-size: var(--font-size-md); font-weight: var(--font-weight-bold); color: var(--text-primary); line-height: 1.3;">
                <?= e($c['course_name']) ?>
              </h3>
            </div>
          </div>
          <span class="pill pill-lime" style="font-weight: var(--font-weight-bold);">
            <?= (int) ($c['credits'] ?? 3) ?> SKS
          </span>
        </div>

        <div style="font-size: var(--font-size-xs); color: var(--text-secondary); display: flex; align-items: center; gap: 6px;">
          <svg class="ui-icon ui-icon-xs" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
            <circle cx="12" cy="7" r="4"></circle>
          </svg>
          <span><?= e(!empty($c['lecturer']) ? $c['lecturer'] : 'Dosen Pengampu Belum Diisi') ?></span>
        </div>

        <div style="display: flex; align-items: center; justify-content: flex-end; gap: 8px; border-top: 1px solid var(--border-subtle); padding-top: 10px; margin-top: auto;">
          <button
            type="button"
            class="btn-secondary btn-sm"
            data-edit-course='<?= json_encode($c, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>'
          >
            Edit
          </button>
          <form method="POST" action="index.php?page=academic&action=delete_course" onsubmit="return confirm('Apakah Anda yakin ingin menghapus mata kuliah <?= e(addslashes($c['course_name'])) ?>? Seluruh jadwal terkait juga akan dihapus.');" style="display:inline;">
            <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
            <button type="submit" class="btn-danger-ghost">Hapus</button>
          </form>
        </div>
      </article>
    <?php endforeach; ?>
  </section>
<?php endif; ?>

<!-- ========================================================================
     ROW 4: JADWAL KULIAH MINGGUAN (TIMETABLE GRID SENIN - MINGGU)
     ======================================================================== -->
<article class="bento-card" style="margin-top: var(--space-4);">
  <div class="card-header-row">
    <div class="card-heading-group">
      <h2 class="card-heading-title">Jadwal Perkuliahan Mingguan Tetap</h2>
      <span class="card-heading-sub">Deteksi otomatis bentrok mencegah jadwal tumpang tindih</span>
    </div>
    <span class="pill pill-lime">Conflict-Free Engine</span>
  </div>

  <?php if (empty($weeklySchedules)): ?>
    <div class="empty-state">
      <div class="empty-state-icon" aria-hidden="true">
        <svg class="ui-icon" viewBox="0 0 24 24">
          <rect x="3" y="4" width="18" height="18" rx="2"></rect>
          <line x1="16" y1="2" x2="16" y2="6"></line>
          <line x1="8" y1="2" x2="8" y2="6"></line>
          <line x1="3" y1="10" x2="21" y2="10"></line>
        </svg>
      </div>
      <h3 class="empty-state-title">Belum Ada Jadwal Kuliah</h3>
      <p class="empty-state-desc">Pilih mata kuliah dan daftarkan hari serta jam kuliah tetap untuk mengisi matriks mingguan.</p>
      <button type="button" class="btn-lime" data-open-modal="scheduleCreateModal">+ Tambah Jadwal Kuliah</button>
    </div>
  <?php else: ?>
    <div class="course-schedule-grid">
      <?php for ($d = 1; $d <= 7; $d++):
        $dayScheds = $groupedSchedules[$d] ?? [];
        $isToday = ($d === $todayDayOfWeek);
      ?>
        <div class="day-schedule-col" style="<?= $isToday ? 'border-color: var(--accent-lime);' : '' ?>">
          <div class="day-schedule-header">
            <span><?= e($dayNames[$d]) ?></span>
            <?php if ($isToday): ?>
              <span class="pill pill-lime" style="font-size: var(--font-size-2xs); padding: 2px 6px;">Hari Ini</span>
            <?php else: ?>
              <span style="font-size: var(--font-size-xs); color: var(--text-muted);"><?= count($dayScheds) ?> Sesi</span>
            <?php endif; ?>
          </div>

          <?php if (empty($dayScheds)): ?>
            <div style="font-size: var(--font-size-xs); color: var(--text-muted); text-align: center; padding: 24px 8px;">
              Tidak ada kuliah
            </div>
          <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 10px;">
              <?php foreach ($dayScheds as $s):
                $cColor = $s['color_hex'] ?? '#3B82F6';
              ?>
                <div class="day-schedule-item" style="border-left-color: <?= e($cColor) ?>;">
                  <div style="display: flex; align-items: center; justify-content: space-between; gap: 4px;">
                    <span style="font-size: var(--font-size-xs); font-weight: var(--font-weight-bold); color: var(--accent-lime);">
                      <?= e(substr($s['start_time'], 0, 5)) ?> - <?= e(substr($s['end_time'], 0, 5)) ?>
                    </span>
                    <form method="POST" action="index.php?page=academic&action=delete_schedule" onsubmit="return confirm('Hapus jadwal kuliah ini?');" style="margin:0;">
                      <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                      <button type="submit" class="btn-ghost-icon" title="Hapus Jadwal" style="width: 22px; height: 22px; font-size: 0.75rem;">&times;</button>
                    </form>
                  </div>
                  <strong style="font-size: var(--font-size-sm); color: var(--text-primary); line-height: 1.25;">
                    <?= e($s['course_name'] ?? 'Mata Kuliah') ?>
                  </strong>
                  <div style="font-size: var(--font-size-2xs); color: var(--text-secondary); display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                    <span><?= e($s['course_code'] ?? '') ?></span>
                    <?php if (!empty($s['room'])): ?>
                      <span>• Ruang <?= e($s['room']) ?></span>
                    <?php endif; ?>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      <?php endfor; ?>
    </div>
  <?php endif; ?>
</article>

<!-- ========================================================================
     MODAL 1: TAMBAH MATA KULIAH (AcademicController::storeCourse)
     ======================================================================== -->
<div class="modal-backdrop" id="courseCreateModal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="courseCreateModalTitle">
  <div class="modal-card">
    <div class="modal-header">
      <div>
        <h2 class="modal-title" id="courseCreateModalTitle">Tambah Mata Kuliah</h2>
        <p class="modal-subtitle">Daftarkan mata kuliah baru untuk semester aktif</p>
      </div>
      <button type="button" class="modal-close-btn" data-close-modal="courseCreateModal" aria-label="Tutup">&times;</button>
    </div>
    <form method="POST" action="index.php?page=academic&action=create_course">
      <div class="modal-body">
        <div class="form-grid-2">
          <div class="form-group">
            <label for="createCourseCode" class="form-label">Kode Mata Kuliah *</label>
            <input type="text" id="createCourseCode" name="course_code" class="form-input" placeholder="Contoh: IF2104" required>
          </div>
          <div class="form-group">
            <label for="createCourseCredits" class="form-label">Bobot SKS *</label>
            <input type="number" id="createCourseCredits" name="credits" class="form-input" min="1" max="6" value="3" required>
          </div>
        </div>

        <div class="form-group">
          <label for="createCourseName" class="form-label">Nama Mata Kuliah *</label>
          <input type="text" id="createCourseName" name="course_name" class="form-input" placeholder="Contoh: Rekayasa Perangkat Lunak" required>
        </div>

        <div class="form-group">
          <label for="createCourseLecturer" class="form-label">Dosen Pengampu</label>
          <input type="text" id="createCourseLecturer" name="lecturer" class="form-input" placeholder="Nama dosen atau asisten laboratorium">
        </div>

        <div class="form-group" style="margin-bottom: 0;">
          <label for="createCourseColor" class="form-label">Warna Label / Aksen</label>
          <input type="color" id="createCourseColor" name="color_hex" class="form-input" value="#3B82F6" style="height: 42px; padding: 4px; cursor: pointer;">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-secondary" data-close-modal="courseCreateModal">Batal</button>
        <button type="submit" class="btn-lime">Simpan Mata Kuliah</button>
      </div>
    </form>
  </div>
</div>

<!-- ========================================================================
     MODAL 2: EDIT MATA KULIAH (AcademicController::updateCourse)
     ======================================================================== -->
<div class="modal-backdrop" id="courseEditModal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="courseEditModalTitle">
  <div class="modal-card">
    <div class="modal-header">
      <div>
        <h2 class="modal-title" id="courseEditModalTitle">Perbarui Mata Kuliah</h2>
        <p class="modal-subtitle">Ubah kode, nama, SKS, atau dosen pengampu</p>
      </div>
      <button type="button" class="modal-close-btn" data-close-modal="courseEditModal" aria-label="Tutup">&times;</button>
    </div>
    <form method="POST" action="index.php?page=academic&action=edit_course">
      <input type="hidden" name="id" id="editCourseId">
      <div class="modal-body">
        <div class="form-grid-2">
          <div class="form-group">
            <label for="editCourseCode" class="form-label">Kode Mata Kuliah *</label>
            <input type="text" id="editCourseCode" name="course_code" class="form-input" required>
          </div>
          <div class="form-group">
            <label for="editCourseCredits" class="form-label">Bobot SKS *</label>
            <input type="number" id="editCourseCredits" name="credits" class="form-input" min="1" max="6" required>
          </div>
        </div>

        <div class="form-group">
          <label for="editCourseName" class="form-label">Nama Mata Kuliah *</label>
          <input type="text" id="editCourseName" name="course_name" class="form-input" required>
        </div>

        <div class="form-group">
          <label for="editCourseLecturer" class="form-label">Dosen Pengampu</label>
          <input type="text" id="editCourseLecturer" name="lecturer" class="form-input">
        </div>

        <div class="form-group" style="margin-bottom: 0;">
          <label for="editCourseColor" class="form-label">Warna Label / Aksen</label>
          <input type="color" id="editCourseColor" name="color_hex" class="form-input" style="height: 42px; padding: 4px; cursor: pointer;">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-secondary" data-close-modal="courseEditModal">Batal</button>
        <button type="submit" class="btn-lime">Simpan Perubahan</button>
      </div>
    </form>
  </div>
</div>

<!-- ========================================================================
     MODAL 3: TAMBAH JADWAL KULIAH (AcademicController::storeSchedule)
     ======================================================================== -->
<div class="modal-backdrop" id="scheduleCreateModal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="scheduleCreateModalTitle">
  <div class="modal-card">
    <div class="modal-header">
      <div>
        <h2 class="modal-title" id="scheduleCreateModalTitle">Tambah Jadwal Perkuliahan</h2>
        <p class="modal-subtitle">Tervalidasi anti-bentrok secara otomatis dengan jadwal lain</p>
      </div>
      <button type="button" class="modal-close-btn" data-close-modal="scheduleCreateModal" aria-label="Tutup">&times;</button>
    </div>
    <form method="POST" action="index.php?page=academic&action=create_schedule">
      <div class="modal-body">
        <div class="form-group">
          <label for="createSchedCourseId" class="form-label">Pilih Mata Kuliah *</label>
          <select id="createSchedCourseId" name="course_id" class="form-select" required>
            <option value="" disabled selected>-- Pilih Mata Kuliah --</option>
            <?php foreach ($courses as $c): ?>
              <option value="<?= (int) $c['id'] ?>">
                <?= e($c['course_code']) ?> — <?= e($c['course_name']) ?> (<?= (int)$c['credits'] ?> SKS)
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label for="createSchedDay" class="form-label">Hari Perkuliahan *</label>
          <select id="createSchedDay" name="day_of_week" class="form-select" required>
            <option value="1">Senin</option>
            <option value="2">Selasa</option>
            <option value="3">Rabu</option>
            <option value="4">Kamis</option>
            <option value="5">Jumat</option>
            <option value="6">Sabtu</option>
            <option value="7">Minggu</option>
          </select>
        </div>

        <div class="form-grid-2">
          <div class="form-group">
            <label for="createSchedStartTime" class="form-label">Jam Mulai *</label>
            <input type="time" id="createSchedStartTime" name="start_time" class="form-input" value="08:00" required>
          </div>
          <div class="form-group">
            <label for="createSchedEndTime" class="form-label">Jam Selesai *</label>
            <input type="time" id="createSchedEndTime" name="end_time" class="form-input" value="10:30" required>
          </div>
        </div>

        <div class="form-group" style="margin-bottom: 0;">
          <label for="createSchedRoom" class="form-label">Ruang Kelas / Laboratorium</label>
          <input type="text" id="createSchedRoom" name="room" class="form-input" placeholder="Contoh: Gedung A - R.302">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-secondary" data-close-modal="scheduleCreateModal">Batal</button>
        <button type="submit" class="btn-lime">Simpan Jadwal Kuliah</button>
      </div>
    </form>
  </div>
</div>
