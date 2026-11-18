<?php
/**
 * views/calendar/index.php
 * Kalender Kegiatan & Deteksi Bentrok Jadwal — Technical High-Contrast / Daylight Hybrid SSR
 * Terhubung 100% dengan CalendarController.php (Schedules, Events, Tasks, & ConflictService)
 */

$month = (int) ($month ?? date('m'));
$year  = (int) ($year ?? date('Y'));
$monthName = $monthName ?? 'Bulan';
$selectedDate = $selectedDate ?? date('Y-m-d');
$selectedDayName = $selectedDayName ?? 'Hari';
$calendarGrid = $calendarGrid ?? [];
$selectedDayData = $selectedDayData ?? [
    'date'      => $selectedDate,
    'dayName'   => $selectedDayName,
    'schedules' => [],
    'events'    => [],
    'tasks'     => [],
];

// Perhitungan navigasi bulan sebelumnya dan selanjutnya
$prevMonth = $month - 1;
$prevYear  = $year;
if ($prevMonth < 1) {
    $prevMonth = 12;
    $prevYear--;
}

$nextMonth = $month + 1;
$nextYear  = $year;
if ($nextMonth > 12) {
    $nextMonth = 1;
    $nextYear++;
}

// Tentukan offset hari pertama dalam kalender (1=Senin s/d 7=Minggu)
$firstDayOfWeek = (int) date('N', strtotime($startDate));
$blankDaysCount = $firstDayOfWeek - 1;

$categoryLabels = [
    'academic'     => 'Akademik',
    'organization' => 'Organisasi',
    'personal'     => 'Pribadi',
    'exam'         => 'Ujian',
];
?>

<!-- ========================================================================
     ROW 1: NAVIGASI BULAN & AKSI
     ======================================================================== -->
<section class="calendar-header-bar" aria-label="Kontrol Kalender">
  <div class="calendar-nav-group">
    <a href="index.php?page=calendar&month=<?= $prevMonth ?>&year=<?= $prevYear ?>" class="btn-secondary btn-sm" aria-label="Bulan Sebelumnya">
      &larr; <?= sprintf('%02d', $prevMonth) ?>
    </a>
    <span class="calendar-month-title">
      <?= e($monthName) ?> <?= $year ?>
    </span>
    <a href="index.php?page=calendar&month=<?= $nextMonth ?>&year=<?= $nextYear ?>" class="btn-secondary btn-sm" aria-label="Bulan Selanjutnya">
      <?= sprintf('%02d', $nextMonth) ?> &rarr;
    </a>
    <a href="index.php?page=calendar&month=<?= (int)date('m') ?>&year=<?= (int)date('Y') ?>&date=<?= date('Y-m-d') ?>" class="filter-pill btn-sm" style="margin-left: 8px;">
      Hari Ini
    </a>
  </div>

  <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
    <div style="display: flex; align-items: center; gap: 12px; font-size: var(--font-size-xs); color: var(--text-secondary); margin-right: 8px;">
      <span style="display: flex; align-items: center; gap: 4px;">
        <span class="cal-dot is-schedule" aria-hidden="true"></span> Kuliah Tetap
      </span>
      <span style="display: flex; align-items: center; gap: 4px;">
        <span class="cal-dot is-event" aria-hidden="true"></span> Agenda Kegiatan
      </span>
      <span style="display: flex; align-items: center; gap: 4px;">
        <span class="cal-dot is-task" aria-hidden="true"></span> Deadline Tugas
      </span>
    </div>

    <button type="button" class="btn-lime" data-open-modal="eventCreateModal">
      <svg class="ui-icon ui-icon-xs" viewBox="0 0 24 24" aria-hidden="true">
        <line x1="12" y1="5" x2="12" y2="19"></line>
        <line x1="5" y1="12" x2="19" y2="12"></line>
      </svg>
      <span>Tambah Agenda Baru</span>
    </button>
  </div>
</section>

<!-- ========================================================================
     ROW 2: DUAL-COLUMN CALENDAR & AGENDA PANEL
     ======================================================================== -->
<section class="calendar-layout" aria-label="Tampilan Kalender dan Agenda Terpilih">
  <!-- Kolom Kiri: Matriks Grid Kalender Bulanan -->
  <article class="bento-card" style="padding: var(--space-4);">
    <div class="calendar-grid-table">
      <!-- Header Nama Hari (Sen - Min) -->
      <div class="calendar-day-header">Sen</div>
      <div class="calendar-day-header">Sel</div>
      <div class="calendar-day-header">Rab</div>
      <div class="calendar-day-header">Kam</div>
      <div class="calendar-day-header">Jum</div>
      <div class="calendar-day-header">Sab</div>
      <div class="calendar-day-header">Min</div>

      <!-- Sel Kosong Sebelum Tanggal 1 -->
      <?php for ($b = 0; $b < $blankDaysCount; $b++): ?>
        <div style="aspect-ratio: 1 / 1; min-height: 80px; opacity: 0.2; background: transparent; border: 1px dashed var(--border-subtle); border-radius: var(--radius-md);"></div>
      <?php endfor; ?>

      <!-- Sel Hari Kalender -->
      <?php
      $todayStr = date('Y-m-d');
      foreach ($calendarGrid as $dateStr => $dayData):
        $isToday = ($dateStr === $todayStr);
        $isSelected = ($dateStr === $selectedDate);
        $cellClass = 'calendar-day-cell';
        if ($isToday) $cellClass .= ' is-today';
        if ($isSelected) $cellClass .= ' is-selected';

        $hasSchedules = !empty($dayData['schedules']);
        $hasEvents    = !empty($dayData['events']);
        $hasTasks     = !empty($dayData['tasks']);
      ?>
        <a
          href="index.php?page=calendar&month=<?= $month ?>&year=<?= $year ?>&date=<?= e($dateStr) ?>"
          class="<?= $cellClass ?>"
          title="<?= e($dateStr) ?>: <?= count($dayData['schedules']) ?> kuliah, <?= count($dayData['events']) ?> agenda, <?= count($dayData['tasks']) ?> tugas"
        >
          <div class="calendar-day-number">
            <span><?= (int) $dayData['day'] ?></span>
            <?php if ($isToday): ?>
              <span class="pill pill-lime" style="font-size: 0.625rem; padding: 1px 4px;">Kini</span>
            <?php endif; ?>
          </div>

          <div class="calendar-cell-dots" aria-hidden="true">
            <?php if ($hasSchedules): ?>
              <span class="cal-dot is-schedule" title="Jadwal Kuliah"></span>
            <?php endif; ?>
            <?php if ($hasEvents): ?>
              <span class="cal-dot is-event" title="Agenda Kegiatan"></span>
            <?php endif; ?>
            <?php if ($hasTasks): ?>
              <span class="cal-dot is-task" title="Deadline Tugas"></span>
            <?php endif; ?>
          </div>

          <div style="font-size: 0.6875rem; color: var(--text-muted); margin-top: auto; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
            <?php if ($dayData['count'] > 0): ?>
              <?= (int) $dayData['count'] ?> entitas
            <?php endif; ?>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  </article>

  <!-- Kolom Kanan: Rincian Agenda Hari Terpilih -->
  <aside class="agenda-day-panel" aria-label="Agenda Tanggal Terpilih">
    <div style="border-bottom: 1px solid var(--border-subtle); padding-bottom: var(--space-3);">
      <span class="pill pill-lime" style="margin-bottom: 6px; display: inline-block;">
        <?= e($selectedDayName) ?>
      </span>
      <h3 style="font-size: var(--font-size-md); font-weight: var(--font-weight-bold); color: var(--text-primary);">
        <?= date('d F Y', strtotime($selectedDate)) ?>
      </h3>
      <span style="font-size: var(--font-size-xs); color: var(--text-secondary);">
        Deteksi bentrok jadwal aktif
      </span>
    </div>

    <!-- 1. Sesi Kuliah Tetap Pada Hari Terpilih -->
    <div>
      <div style="font-size: var(--font-size-xs); font-weight: var(--font-weight-bold); text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); margin-bottom: 8px;">
        Jadwal Kuliah Rutin
      </div>
      <?php if (empty($selectedDayData['schedules'])): ?>
        <p style="font-size: var(--font-size-xs); color: var(--text-muted); margin: 0 0 12px 0;">Tidak ada jadwal kuliah rutin di hari ini.</p>
      <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 8px; margin-bottom: 16px;">
          <?php foreach ($selectedDayData['schedules'] as $sch): ?>
            <div style="background-color: var(--bg-sidebar); border: 1px solid var(--border-subtle); border-left: 3px solid <?= e($sch['color_hex'] ?? '#3B82F6') ?>; border-radius: var(--radius-sm); padding: 8px 10px;">
              <div style="display: flex; justify-content: space-between; align-items: center; font-size: var(--font-size-xs);">
                <span style="font-weight: var(--font-weight-bold); color: var(--accent-lime);">
                  <?= e(substr($sch['start_time'], 0, 5)) ?> - <?= e(substr($sch['end_time'], 0, 5)) ?>
                </span>
                <?php if (!empty($sch['room'])): ?>
                  <span class="pill pill-neutral" style="font-size: 0.6875rem;"><?= e($sch['room']) ?></span>
                <?php endif; ?>
              </div>
              <strong style="font-size: var(--font-size-sm); color: var(--text-primary); display: block; margin-top: 2px;">
                <?= e($sch['course_name'] ?? 'Mata Kuliah') ?>
              </strong>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- 2. Agenda Kegiatan & Acara Mahasiswa -->
    <div>
      <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
        <span style="font-size: var(--font-size-xs); font-weight: var(--font-weight-bold); text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted);">
          Agenda &amp; Acara
        </span>
        <button type="button" class="btn-ghost-icon" data-open-modal="eventCreateModal" title="Tambah Agenda" style="width: 20px; height: 20px; font-size: 0.8rem;">+</button>
      </div>

      <?php if (empty($selectedDayData['events'])): ?>
        <p style="font-size: var(--font-size-xs); color: var(--text-muted); margin: 0 0 12px 0;">Belum ada agenda kegiatan yang dicatat.</p>
      <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 16px;">
          <?php foreach ($selectedDayData['events'] as $ev):
            $catLabel = $categoryLabels[$ev['category'] ?? 'personal'] ?? ucfirst(e($ev['category']));
          ?>
            <div style="background-color: var(--bg-sidebar); border: 1px solid var(--border-subtle); border-radius: var(--radius-sm); padding: 10px;">
              <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 6px;">
                <span class="pill pill-orange" style="font-size: 0.6875rem;">
                  <?= e($catLabel) ?>
                </span>
                <span style="font-size: var(--font-size-xs); font-weight: var(--font-weight-bold); color: var(--accent-orange);">
                  <?= e(substr($ev['start_time'], 0, 5)) ?> - <?= e(substr($ev['end_time'], 0, 5)) ?>
                </span>
              </div>
              <h4 style="font-size: var(--font-size-sm); font-weight: var(--font-weight-bold); color: var(--text-primary); margin: 6px 0 2px 0;">
                <?= e($ev['title']) ?>
              </h4>
              <?php if (!empty($ev['location'])): ?>
                <div style="font-size: var(--font-size-xs); color: var(--text-secondary); margin-bottom: 4px;">
                  📍 <?= e($ev['location']) ?>
                </div>
              <?php endif; ?>
              <?php if (!empty($ev['notes'])): ?>
                <div style="font-size: var(--font-size-2xs); color: var(--text-muted); line-height: 1.4;">
                  <?= e($ev['notes']) ?>
                </div>
              <?php endif; ?>

              <div style="display: flex; align-items: center; justify-content: flex-end; gap: 6px; margin-top: 8px; border-top: 1px solid var(--border-subtle); padding-top: 6px;">
                <button
                  type="button"
                  class="btn-secondary btn-sm"
                  style="padding: 3px 8px; font-size: var(--font-size-2xs);"
                  data-edit-event='<?= json_encode($ev, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>'
                >
                  Edit
                </button>
                <form method="POST" action="index.php?page=calendar&action=delete_event&date=<?= e($selectedDate) ?>" onsubmit="return confirm('Apakah Anda yakin ingin menghapus agenda <?= e(addslashes($ev['title'])) ?>?');" style="margin:0;">
                  <input type="hidden" name="page" value="calendar">
                  <input type="hidden" name="action" value="delete_event">
                  <input type="hidden" name="id" value="<?= (int) $ev['id'] ?>">
                  <button type="submit" class="btn-danger-ghost" style="padding: 3px 8px; font-size: var(--font-size-2xs);">Hapus</button>
                </form>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- 3. Tenggat Tugas Kuliah Pada Hari Terpilih -->
    <div>
      <div style="font-size: var(--font-size-xs); font-weight: var(--font-weight-bold); text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); margin-bottom: 8px;">
        Deadline Tugas Hari Ini
      </div>
      <?php if (empty($selectedDayData['tasks'])): ?>
        <p style="font-size: var(--font-size-xs); color: var(--text-muted); margin: 0;">Tidak ada batas pengumpulan tugas hari ini.</p>
      <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 8px;">
          <?php foreach ($selectedDayData['tasks'] as $t): ?>
            <div style="background-color: var(--bg-sidebar); border: 1px solid var(--border-subtle); border-radius: var(--radius-sm); padding: 8px 10px;">
              <div style="display: flex; justify-content: space-between; align-items: center; font-size: var(--font-size-xs);">
                <span class="pill pill-cyan" style="font-size: 0.6875rem;">Deadline: <?= e(substr($t['deadline_time'] ?? '23:59', 0, 5)) ?></span>
                <span class="pill <?= ($t['priority'] ?? '') === 'high' ? 'pill-orange' : 'pill-neutral' ?>" style="font-size: 0.6875rem;">
                  <?= ucfirst(e($t['priority'] ?? 'Medium')) ?>
                </span>
              </div>
              <strong style="font-size: var(--font-size-sm); color: var(--text-primary); display: block; margin-top: 4px;">
                <?= e($t['title']) ?>
              </strong>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </aside>
</section>

<!-- ========================================================================
     MODAL 1: TAMBAH AGENDA KEGIATAN (CalendarController::storeEvent)
     ======================================================================== -->
<div class="modal-backdrop" id="eventCreateModal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="eventCreateModalTitle">
  <div class="modal-card">
    <div class="modal-header">
      <div>
        <h2 class="modal-title" id="eventCreateModalTitle">Tambah Agenda Kegiatan</h2>
        <p class="modal-subtitle">Tervalidasi anti-bentrok secara real-time dengan jadwal kuliah</p>
      </div>
      <button type="button" class="modal-close-btn" data-close-modal="eventCreateModal" aria-label="Tutup">&times;</button>
    </div>
    <form method="POST" action="index.php?page=calendar&action=store_event">
      <input type="hidden" name="page" value="calendar">
      <input type="hidden" name="action" value="store_event">
      <div class="modal-body">
        <div class="form-group">
          <label for="createEventTitle" class="form-label">Judul Agenda / Acara *</label>
          <input type="text" id="createEventTitle" name="title" class="form-input" placeholder="Contoh: Rapat Himpunan Mahasiswa" required>
        </div>

        <div class="form-grid-2">
          <div class="form-group">
            <label for="createEventCategory" class="form-label">Kategori *</label>
            <select id="createEventCategory" name="category" class="form-select">
              <option value="academic">Akademik</option>
              <option value="organization">Organisasi</option>
              <option value="personal" selected>Pribadi</option>
              <option value="exam">Ujian</option>
            </select>
          </div>
          <div class="form-group">
            <label for="createEventDate" class="form-label">Tanggal *</label>
            <input type="date" id="createEventDate" name="event_date" class="form-input" value="<?= e($selectedDate) ?>" required>
          </div>
        </div>

        <div class="form-grid-2">
          <div class="form-group">
            <label for="createEventStartTime" class="form-label">Jam Mulai *</label>
            <input type="time" id="createEventStartTime" name="start_time" class="form-input" value="13:00" required>
          </div>
          <div class="form-group">
            <label for="createEventEndTime" class="form-label">Jam Selesai *</label>
            <input type="time" id="createEventEndTime" name="end_time" class="form-input" value="15:00" required>
          </div>
        </div>

        <div class="form-group">
          <label for="createEventLocation" class="form-label">Lokasi / Tautan Pertemuan</label>
          <input type="text" id="createEventLocation" name="location" class="form-input" placeholder="Contoh: Sekretariat Himpunan / Zoom Meeting">
        </div>

        <div class="form-group" style="margin-bottom: 0;">
          <label for="createEventNotes" class="form-label">Catatan Agenda</label>
          <textarea id="createEventNotes" name="notes" class="form-textarea" placeholder="Detail agenda atau persiapan..."></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-secondary" data-close-modal="eventCreateModal">Batal</button>
        <button type="submit" class="btn-lime">Simpan Agenda</button>
      </div>
    </form>
  </div>
</div>

<!-- ========================================================================
     MODAL 2: EDIT AGENDA KEGIATAN (CalendarController::updateEvent)
     ======================================================================== -->
<div class="modal-backdrop" id="eventEditModal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="eventEditModalTitle">
  <div class="modal-card">
    <div class="modal-header">
      <div>
        <h2 class="modal-title" id="eventEditModalTitle">Perbarui Agenda Kegiatan</h2>
        <p class="modal-subtitle">Ubah informasi waktu atau rincian agenda</p>
      </div>
      <button type="button" class="modal-close-btn" data-close-modal="eventEditModal" aria-label="Tutup">&times;</button>
    </div>
    <form method="POST" action="index.php?page=calendar&action=update_event">
      <input type="hidden" name="page" value="calendar">
      <input type="hidden" name="action" value="update_event">
      <input type="hidden" name="id" id="editEventId">
      <div class="modal-body">
        <div class="form-group">
          <label for="editEventTitle" class="form-label">Judul Agenda / Acara *</label>
          <input type="text" id="editEventTitle" name="title" class="form-input" required>
        </div>

        <div class="form-grid-2">
          <div class="form-group">
            <label for="editEventCategory" class="form-label">Kategori *</label>
            <select id="editEventCategory" name="category" class="form-select">
              <option value="academic">Akademik</option>
              <option value="organization">Organisasi</option>
              <option value="personal">Pribadi</option>
              <option value="exam">Ujian</option>
            </select>
          </div>
          <div class="form-group">
            <label for="editEventDate" class="form-label">Tanggal *</label>
            <input type="date" id="editEventDate" name="event_date" class="form-input" required>
          </div>
        </div>

        <div class="form-grid-2">
          <div class="form-group">
            <label for="editEventStartTime" class="form-label">Jam Mulai *</label>
            <input type="time" id="editEventStartTime" name="start_time" class="form-input" required>
          </div>
          <div class="form-group">
            <label for="editEventEndTime" class="form-label">Jam Selesai *</label>
            <input type="time" id="editEventEndTime" name="end_time" class="form-input" required>
          </div>
        </div>

        <div class="form-group">
          <label for="editEventLocation" class="form-label">Lokasi / Tautan Pertemuan</label>
          <input type="text" id="editEventLocation" name="location" class="form-input">
        </div>

        <div class="form-group" style="margin-bottom: 0;">
          <label for="editEventNotes" class="form-label">Catatan Agenda</label>
          <textarea id="editEventNotes" name="notes" class="form-textarea"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-secondary" data-close-modal="eventEditModal">Batal</button>
        <button type="submit" class="btn-lime">Simpan Perubahan</button>
      </div>
    </form>
  </div>
</div>
