<?php
/**
 * views/finance/index.php
 * Pengelolaan Keuangan Pribadi Mahasiswa — Technical High-Contrast / Daylight Hybrid SSR
 * Terhubung 100% dengan FinanceController.php & FinanceService.php
 */

$summary = $summary ?? [
    'month'                     => date('Y-m'),
    'total_income'              => 0.0,
    'total_expense'             => 0.0,
    'net_balance'               => 0.0,
    'monthly_limit'             => 1500000.0,
    'remaining_budget'          => 1500000.0,
    'budget_usage_percent'      => 0.0,
    'is_over_budget'            => false,
    'is_near_limit'             => false,
    'projected_monthly_expense' => 0.0,
    'daily_average_expense'     => 0.0,
    'days_in_month'             => 30,
    'days_passed'               => (int) date('j'),
    'remaining_days'            => 10,
    'warning_threshold_percent' => 80,
];

$transactions = $transactions ?? [];
$categoryBreakdown = $categoryBreakdown ?? [];
$budgetSettings = $budgetSettings ?? null;
$filters = $filters ?? [];
$currentMonth = $currentMonth ?? date('Y-m');

$totalIncome  = (float) ($summary['total_income'] ?? 0);
$totalExpense = (float) ($summary['total_expense'] ?? 0);
$netBalance   = (float) ($summary['net_balance'] ?? 0);
$monthlyLimit = (float) ($summary['monthly_limit'] ?? 1500000);
$remainingBudget = (float) ($summary['remaining_budget'] ?? 0);
$budgetUsagePct  = (float) ($summary['budget_usage_percent'] ?? 0);
$projectedExpense = (float) ($summary['projected_monthly_expense'] ?? 0);
$dailyAverage = (float) ($summary['daily_average_expense'] ?? 0);

$isOverBudget = !empty($summary['is_over_budget']);
$isNearLimit  = !empty($summary['is_near_limit']);

$budgetBarClass = $isOverBudget ? 'is-danger' : ($isNearLimit ? 'is-warning' : '');
?>

<!-- ========================================================================
     ALERT STATUS BATAS ANGGARAN
     ======================================================================== -->
<?php if ($isOverBudget): ?>
  <div class="alert-banner alert-danger" role="alert">
    <div class="alert-banner-left">
      <svg class="ui-icon ui-icon-sm" viewBox="0 0 24 24" aria-hidden="true">
        <circle cx="12" cy="12" r="10"></circle>
        <line x1="12" y1="8" x2="12" y2="12"></line>
        <line x1="12" y1="16" x2="12.01" y2="16"></line>
      </svg>
      <span>
        <strong>Peringatan Defisit:</strong> Pengeluaran bulan ini (Rp <?= number_format($totalExpense, 0, ',', '.') ?>) telah melampaui batas anggaran (Rp <?= number_format($monthlyLimit, 0, ',', '.') ?>). Kurangi pengeluaran non-primer.
      </span>
    </div>
    <button type="button" class="btn-secondary btn-sm" data-open-modal="budgetSettingsModal">Ubah Limit</button>
  </div>
<?php elseif ($isNearLimit): ?>
  <div class="alert-banner alert-warning" role="alert">
    <div class="alert-banner-left">
      <svg class="ui-icon ui-icon-sm" viewBox="0 0 24 24" aria-hidden="true">
        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
        <line x1="12" y1="9" x2="12" y2="13"></line>
        <line x1="12" y1="17" x2="12.01" y2="17"></line>
      </svg>
      <span>
        <strong>Mendekati Batas:</strong> Anda telah menggunakan <?= number_format($budgetUsagePct, 1) ?>% dari anggaran bulanan. Sisa anggaran tersedia Rp <?= number_format($remainingBudget, 0, ',', '.') ?>.
      </span>
    </div>
    <button type="button" class="btn-secondary btn-sm" data-open-modal="budgetSettingsModal">Ubah Limit</button>
  </div>
<?php endif; ?>

<!-- ========================================================================
     ROW 1: 4 FINANCIAL METRIC KPI CARDS
     ======================================================================== -->
<section class="kpi-grid-4" aria-label="Ringkasan Arus Kas Mahasiswa">
  <article class="kpi-card">
    <div class="kpi-top">
      <span class="kpi-label">Pemasukan Bulan Ini</span>
      <span class="kpi-icon-dot is-lime" aria-hidden="true">
        <svg class="ui-icon ui-icon-xs" viewBox="0 0 24 24">
          <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline>
          <polyline points="17 6 23 6 23 12"></polyline>
        </svg>
      </span>
    </div>
    <div class="kpi-value is-lime is-compact">Rp <?= number_format($totalIncome, 0, ',', '.') ?></div>
    <div class="kpi-footer">
      <span>Arus masuk tercatat</span>
    </div>
  </article>

  <article class="kpi-card">
    <div class="kpi-top">
      <span class="kpi-label">Pengeluaran Bulan Ini</span>
      <span class="kpi-icon-dot is-orange" aria-hidden="true">
        <svg class="ui-icon ui-icon-xs" viewBox="0 0 24 24">
          <polyline points="23 18 13.5 8.5 8.5 13.5 1 6"></polyline>
          <polyline points="17 18 23 18 23 12"></polyline>
        </svg>
      </span>
    </div>
    <div class="kpi-value is-orange is-compact">Rp <?= number_format($totalExpense, 0, ',', '.') ?></div>
    <div class="kpi-footer">
      <span>Rata-rata Rp <?= number_format($dailyAverage, 0, ',', '.') ?>/hari</span>
    </div>
  </article>

  <article class="kpi-card">
    <div class="kpi-top">
      <span class="kpi-label">Sisa Anggaran Bulanan</span>
      <span class="kpi-icon-dot" aria-hidden="true">
        <svg class="ui-icon ui-icon-xs" viewBox="0 0 24 24">
          <rect x="2" y="4" width="20" height="16" rx="2"></rect>
          <line x1="2" y1="10" x2="22" y2="10"></line>
        </svg>
      </span>
    </div>
    <div class="kpi-value is-compact <?= $remainingBudget < 0 ? 'is-orange' : '' ?>">
      Rp <?= number_format($remainingBudget, 0, ',', '.') ?>
    </div>
    <div>
      <div class="budget-bar-track">
        <div class="budget-bar-fill <?= $budgetBarClass ?>" style="width: <?= min(100, round($budgetUsagePct)) ?>%;"></div>
      </div>
      <div class="kpi-footer">
        <span>Terpakai <?= number_format($budgetUsagePct, 1) ?>%</span>
        <span class="pill <?= $budgetBarClass === 'is-danger' ? 'pill-danger' : ($budgetBarClass === 'is-warning' ? 'pill-orange' : 'pill-lime') ?>">
          Limit Rp <?= number_format($monthlyLimit, 0, ',', '.') ?>
        </span>
      </div>
    </div>
  </article>

  <article class="kpi-card">
    <div class="kpi-top">
      <span class="kpi-label">Proyeksi Pengeluaran Akhir Bulan</span>
      <span class="kpi-icon-dot" aria-hidden="true">
        <svg class="ui-icon ui-icon-xs" viewBox="0 0 24 24">
          <path d="M12 20V10"></path>
          <path d="M18 20V4"></path>
          <path d="M6 20v-4"></path>
        </svg>
      </span>
    </div>
    <div class="kpi-value is-compact <?= $projectedExpense > $monthlyLimit ? 'is-orange' : '' ?>">
      Rp <?= number_format($projectedExpense, 0, ',', '.') ?>
    </div>
    <div class="kpi-footer">
      <span><?= $summary['remaining_days'] ?? 0 ?> hari tersisa</span>
      <span class="pill <?= $projectedExpense > $monthlyLimit ? 'pill-orange' : 'pill-lime' ?>">
        <?= $projectedExpense > $monthlyLimit ? 'Potensi Melebihi' : 'Aman Terkendali' ?>
      </span>
    </div>
  </article>
</section>

<!-- ========================================================================
     ROW 2: KONTROL BULAN, FILTER & AKSI TRANSAKSI
     ======================================================================== -->
<section class="card-header-row" aria-label="Kontrol Transaksi Keuangan">
  <form method="GET" action="index.php" style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
    <input type="hidden" name="page" value="finance">
    <label for="monthFilter" style="font-size: var(--font-size-xs); color: var(--text-secondary);">Periode Bulan:</label>
    <input
      type="month"
      id="monthFilter"
      name="month"
      value="<?= e($currentMonth) ?>"
      class="form-input"
      style="width: auto; padding: 6px 10px; font-size: var(--font-size-xs);"
      onchange="this.form.submit()"
    >
  </form>

  <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
    <button type="button" class="btn-secondary" data-open-modal="budgetSettingsModal">
      <svg class="ui-icon ui-icon-xs" viewBox="0 0 24 24" aria-hidden="true">
        <circle cx="12" cy="12" r="3"></circle>
        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
      </svg>
      <span>Atur Batas Anggaran</span>
    </button>
    <button type="button" class="btn-lime" data-open-modal="transactionCreateModal">
      <svg class="ui-icon ui-icon-xs" viewBox="0 0 24 24" aria-hidden="true">
        <line x1="12" y1="5" x2="12" y2="19"></line>
        <line x1="5" y1="12" x2="19" y2="12"></line>
      </svg>
      <span>Catat Transaksi Baru</span>
    </button>
  </div>
</section>

<!-- ========================================================================
     ROW 3: BREAKDOWN KATEGORI PENGELUARAN
     ======================================================================== -->
<?php if (!empty($categoryBreakdown)): ?>
  <article class="bento-card">
    <div class="card-header-row">
      <div class="card-heading-group">
        <h2 class="card-heading-title">Distribusi Pengeluaran per Kategori</h2>
        <span class="card-heading-sub">Persentase alokasi dana mahasiswa bulan ini</span>
      </div>
      <span class="pill pill-neutral"><?= count($categoryBreakdown) ?> Kategori Aktif</span>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: var(--space-3);">
      <?php foreach ($categoryBreakdown as $cat): ?>
        <div style="background-color: var(--bg-sidebar); border: 1px solid var(--border-subtle); border-radius: var(--radius-md); padding: var(--space-4);">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
            <strong style="font-size: var(--font-size-sm); color: var(--text-primary);"><?= e($cat['category']) ?></strong>
            <span class="pill pill-orange" style="font-size: 0.6875rem;"><?= number_format((float)($cat['percentage'] ?? 0), 1) ?>%</span>
          </div>
          <div style="font-size: var(--font-size-md); font-weight: var(--font-weight-bold); color: var(--text-primary);">
            Rp <?= number_format((float)($cat['total_amount'] ?? 0), 0, ',', '.') ?>
          </div>
          <div style="font-size: var(--font-size-2xs); color: var(--text-muted); margin-top: 4px;">
            <?= (int) ($cat['transaction_count'] ?? 1) ?> transaksi
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </article>
<?php endif; ?>

<!-- ========================================================================
     ROW 4: RIWAYAT & CATATAN TRANSAKSI (TABLE HYBRID SSR)
     ======================================================================== -->
<article class="bento-card">
  <div class="card-header-row">
    <div class="card-heading-group">
      <h2 class="card-heading-title">Riwayat Catatan Transaksi</h2>
      <span class="card-heading-sub">Catatan arus kas mahasiswa bulan <?= e($currentMonth) ?></span>
    </div>

    <!-- Filter Cepat Jenis Transaksi -->
    <div style="display: flex; gap: 6px; flex-wrap: wrap;">
      <a href="index.php?page=finance&month=<?= e($currentMonth) ?>" class="filter-pill btn-sm <?= empty($filters['type']) ? 'is-active' : '' ?>">Semua</a>
      <a href="index.php?page=finance&month=<?= e($currentMonth) ?>&type=expense" class="filter-pill btn-sm <?= ($filters['type'] ?? '') === 'expense' ? 'is-active' : '' ?>">Pengeluaran</a>
      <a href="index.php?page=finance&month=<?= e($currentMonth) ?>&type=income" class="filter-pill btn-sm <?= ($filters['type'] ?? '') === 'income' ? 'is-active' : '' ?>">Pemasukan</a>
    </div>
  </div>

  <?php if (empty($transactions)): ?>
    <div class="empty-state">
      <div class="empty-state-icon" aria-hidden="true">
        <svg class="ui-icon" viewBox="0 0 24 24">
          <rect x="2" y="4" width="20" height="16" rx="2"></rect>
          <line x1="2" y1="10" x2="22" y2="10"></line>
        </svg>
      </div>
      <h3 class="empty-state-title">Belum Ada Catatan Transaksi</h3>
      <p class="empty-state-desc">Belum ada catatan pemasukan atau pengeluaran di periode ini. Mulai catat transaksi untuk mengontrol keuangan harianmu.</p>
      <button type="button" class="btn-lime" data-open-modal="transactionCreateModal">+ Catat Transaksi Pertama</button>
    </div>
  <?php else: ?>
    <div class="finance-table-wrap">
      <table class="finance-table" aria-label="Tabel Catatan Transaksi">
        <thead>
          <tr>
            <th>Tanggal</th>
            <th>Tipe &amp; Kategori</th>
            <th>Keterangan</th>
            <th style="text-align: right;">Nominal</th>
            <th style="text-align: center; width: 110px;">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($transactions as $tx):
            $isIncome = (($tx['type'] ?? '') === 'income');
            $amountFormatted = number_format((float)($tx['amount'] ?? 0), 0, ',', '.');
          ?>
            <tr>
              <td style="white-space: nowrap; font-size: var(--font-size-xs); color: var(--text-secondary);">
                <?= date('d M Y', strtotime($tx['transaction_date'])) ?>
              </td>
              <td>
                <div style="display: flex; align-items: center; gap: 8px;">
                  <span class="pill <?= $isIncome ? 'pill-lime' : 'pill-orange' ?>" style="font-size: 0.6875rem;">
                    <?= $isIncome ? 'Pemasukan' : 'Pengeluaran' ?>
                  </span>
                  <strong style="color: var(--text-primary); font-size: var(--font-size-sm);"><?= e($tx['category']) ?></strong>
                </div>
              </td>
              <td style="color: var(--text-secondary);">
                <?= e(!empty($tx['description']) ? $tx['description'] : '—') ?>
              </td>
              <td style="text-align: right; white-space: nowrap;">
                <span class="<?= $isIncome ? 'amount-income' : 'amount-expense' ?>">
                  <?= $isIncome ? '+' : '-' ?> Rp <?= $amountFormatted ?>
                </span>
              </td>
              <td style="text-align: center; white-space: nowrap;">
                <div style="display: inline-flex; align-items: center; gap: 4px;">
                  <button
                    type="button"
                    class="btn-ghost-icon"
                    title="Edit Transaksi"
                    data-edit-transaction='<?= json_encode($tx, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>'
                    style="width: 26px; height: 26px; font-size: 0.75rem;"
                  >
                    ✏️
                  </button>
                  <form method="POST" action="index.php?page=finance&action=delete_transaction&month=<?= e($currentMonth) ?>" onsubmit="return confirm('Apakah Anda yakin ingin menghapus catatan transaksi ini?');" style="margin:0;">
                    <input type="hidden" name="id" value="<?= (int) $tx['id'] ?>">
                    <button type="submit" class="btn-ghost-icon" title="Hapus Transaksi" style="width: 26px; height: 26px; color: var(--accent-danger);">
                      &times;
                    </button>
                  </form>
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
     MODAL 1: CATAT TRANSAKSI BARU (FinanceController::storeTransaction)
     ======================================================================== -->
<div class="modal-backdrop" id="transactionCreateModal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="txCreateTitle">
  <div class="modal-card">
    <div class="modal-header">
      <div>
        <h2 class="modal-title" id="txCreateTitle">Catat Transaksi</h2>
        <p class="modal-subtitle">Tambah arus kas pemasukan atau pengeluaran harian</p>
      </div>
      <button type="button" class="modal-close-btn" data-close-modal="transactionCreateModal" aria-label="Tutup">&times;</button>
    </div>
    <form method="POST" action="index.php?page=finance&action=store_transaction">
      <div class="modal-body">
        <div class="form-grid-2">
          <div class="form-group">
            <label for="createTxType" class="form-label">Jenis Transaksi *</label>
            <select id="createTxType" name="type" class="form-select" required>
              <option value="expense" selected>Pengeluaran (-)</option>
              <option value="income">Pemasukan (+)</option>
            </select>
          </div>
          <div class="form-group">
            <label for="createTxDate" class="form-label">Tanggal Transaksi *</label>
            <input type="date" id="createTxDate" name="transaction_date" class="form-input" value="<?= date('Y-m-d') ?>" required>
          </div>
        </div>

        <div class="form-grid-2">
          <div class="form-group">
            <label for="createTxCategory" class="form-label">Kategori *</label>
            <input type="text" id="createTxCategory" name="category" class="form-input" list="categoryOptions" placeholder="Pilih atau ketik kategori..." required>
            <datalist id="categoryOptions">
              <option value="Makanan & Minuman">
              <option value="Transportasi">
              <option value="Kebutuhan Kuliah & Buku">
              <option value="Kost & Listrik">
              <option value="Uang Saku Bulanan">
              <option value="Gaji / Freelance">
              <option value="Beasiswa">
              <option value="Hiburan & Santai">
              <option value="Kesehatan">
              <option value="Lain-lain">
            </datalist>
          </div>
          <div class="form-group">
            <label for="createTxAmount" class="form-label">Nominal (Rp) *</label>
            <input type="number" id="createTxAmount" name="amount" class="form-input" min="100" step="500" placeholder="Contoh: 25000" required>
          </div>
        </div>

        <div class="form-group" style="margin-bottom: 0;">
          <label for="createTxDesc" class="form-label">Keterangan / Catatan</label>
          <input type="text" id="createTxDesc" name="description" class="form-input" placeholder="Contoh: Makan siang warteg dekat kampus">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-secondary" data-close-modal="transactionCreateModal">Batal</button>
        <button type="submit" class="btn-lime">Simpan Transaksi</button>
      </div>
    </form>
  </div>
</div>

<!-- ========================================================================
     MODAL 2: EDIT TRANSAKSI (FinanceController::updateTransaction)
     ======================================================================== -->
<div class="modal-backdrop" id="transactionEditModal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="txEditTitle">
  <div class="modal-card">
    <div class="modal-header">
      <div>
        <h2 class="modal-title" id="txEditTitle">Perbarui Transaksi</h2>
        <p class="modal-subtitle">Ubah rincian transaksi yang sudah tercatat</p>
      </div>
      <button type="button" class="modal-close-btn" data-close-modal="transactionEditModal" aria-label="Tutup">&times;</button>
    </div>
    <form method="POST" action="index.php?page=finance&action=update_transaction">
      <input type="hidden" name="id" id="editTxId">
      <div class="modal-body">
        <div class="form-grid-2">
          <div class="form-group">
            <label for="editTxType" class="form-label">Jenis Transaksi *</label>
            <select id="editTxType" name="type" class="form-select" required>
              <option value="expense">Pengeluaran (-)</option>
              <option value="income">Pemasukan (+)</option>
            </select>
          </div>
          <div class="form-group">
            <label for="editTxDate" class="form-label">Tanggal Transaksi *</label>
            <input type="date" id="editTxDate" name="transaction_date" class="form-input" required>
          </div>
        </div>

        <div class="form-grid-2">
          <div class="form-group">
            <label for="editTxCategory" class="form-label">Kategori *</label>
            <input type="text" id="editTxCategory" name="category" class="form-input" list="categoryOptions" required>
          </div>
          <div class="form-group">
            <label for="editTxAmount" class="form-label">Nominal (Rp) *</label>
            <input type="number" id="editTxAmount" name="amount" class="form-input" min="100" step="500" required>
          </div>
        </div>

        <div class="form-group" style="margin-bottom: 0;">
          <label for="editTxDescription" class="form-label">Keterangan / Catatan</label>
          <input type="text" id="editTxDescription" name="description" class="form-input">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-secondary" data-close-modal="transactionEditModal">Batal</button>
        <button type="submit" class="btn-lime">Simpan Perubahan</button>
      </div>
    </form>
  </div>
</div>

<!-- ========================================================================
     MODAL 3: PENGATURAN BATAS ANGGARAN (FinanceController::saveBudget)
     ======================================================================== -->
<div class="modal-backdrop" id="budgetSettingsModal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="budgetModalTitle">
  <div class="modal-card">
    <div class="modal-header">
      <div>
        <h2 class="modal-title" id="budgetModalTitle">Atur Batas Anggaran Bulanan</h2>
        <p class="modal-subtitle">Tetapkan pagu pengeluaran maksimal dan ambang peringatan</p>
      </div>
      <button type="button" class="modal-close-btn" data-close-modal="budgetSettingsModal" aria-label="Tutup">&times;</button>
    </div>
    <form method="POST" action="index.php?page=finance&action=save_budget">
      <input type="hidden" name="month" value="<?= e($currentMonth) ?>">
      <div class="modal-body">
        <div class="form-group">
          <label for="budgetLimitInput" class="form-label">Batas Pengeluaran Bulan <?= e($currentMonth) ?> (Rp) *</label>
          <input
            type="number"
            id="budgetLimitInput"
            name="monthly_limit"
            class="form-input"
            min="0"
            step="50000"
            value="<?= (int) $monthlyLimit ?>"
            required
          >
          <span style="font-size: var(--font-size-2xs); color: var(--text-muted); margin-top: 4px; display: block;">
            Jika pengeluaran melebihi angka ini, sistem akan memicu status Peringatan Defisit.
          </span>
        </div>

        <div class="form-group" style="margin-bottom: 0;">
          <label for="warningThresholdInput" class="form-label">Ambang Peringatan Waspada (% Penggunaan)</label>
          <input
            type="number"
            id="warningThresholdInput"
            name="warning_threshold_percent"
            class="form-input"
            min="50"
            max="95"
            value="<?= (int) ($summary['warning_threshold_percent'] ?? 80) ?>"
            required
          >
          <span style="font-size: var(--font-size-2xs); color: var(--text-muted); margin-top: 4px; display: block;">
            Contoh: 80% berarti peringatan waspada muncul saat pengeluaran mencapai 80% dari batas pagu.
          </span>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-secondary" data-close-modal="budgetSettingsModal">Batal</button>
        <button type="submit" class="btn-lime">Simpan Anggaran</button>
      </div>
    </form>
  </div>
</div>
