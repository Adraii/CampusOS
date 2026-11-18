<?php
// app/controllers/FinanceController.php
// Controller Pengelolaan Keuangan Pribadi Mahasiswa (Hybrid SSR Bento)
// Menghubungkan FinanceModel dan FinanceService untuk transaksi, analitik, dan status anggaran

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/view.php';
require_once __DIR__ . '/../models/FinanceModel.php';
require_once __DIR__ . '/../services/FinanceService.php';

class FinanceController
{
    private ?PDO $db;
    private FinanceModel $financeModel;
    private FinanceService $financeService;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? getDBConnection();
        $this->financeModel = new FinanceModel($this->db);
        $this->financeService = new FinanceService($this->financeModel, $this->db);
    }

    /**
     * Dispatcher utama request keuangan
     *
     * @param string|null $action
     */
    public function handleRequest(?string $action = null): void
    {
        requireLogin();

        $action = $action ?? ($_GET['action'] ?? 'index');

        switch ($action) {
            case 'index':
                $this->index();
                break;
            case 'store_transaction':
            case 'create_transaction':
                $this->storeTransaction();
                break;
            case 'update_transaction':
            case 'edit_transaction':
                $this->updateTransaction();
                break;
            case 'delete_transaction':
                $this->deleteTransaction();
                break;
            case 'save_budget':
            case 'update_budget':
                $this->saveBudget();
                break;
            case 'api_summary':
                $this->apiSummary();
                break;
            default:
                $this->index();
                break;
        }
    }

    /**
     * Menampilkan ringkasan keuangan, analitik proyeksi, status limit, dan riwayat transaksi
     */
    public function index(): void
    {
        $userId = currentUserId();

        $currentMonth = trim($_GET['month'] ?? date('Y-m'));
        if (!preg_match('/^\d{4}-\d{2}$/', $currentMonth)) {
            $currentMonth = date('Y-m');
        }

        $filters = [
            'month'    => $currentMonth,
            'type'     => $_GET['type'] ?? '',
            'category' => trim($_GET['category'] ?? ''),
            'search'   => trim($_GET['search'] ?? ''),
        ];

        // 1. Ambil ringkasan analitik dan proyeksi dari FinanceService
        $summary = $this->financeService->getMonthlySummary($userId, $currentMonth);

        // 2. Ambil daftar transaksi terfilter
        $transactions = $this->financeModel->getTransactionsByUser($userId, $filters) ?: [];

        // 3. Ambil data breakdown pengeluaran per kategori
        $categoryBreakdown = $this->financeModel->getExpenseByCategory($userId, $currentMonth) ?: [];

        // 4. Pengaturan batas anggaran
        $budgetSettings = $this->financeModel->getBudgetByMonth($userId, $currentMonth);

        $viewData = [
            'pageTitle'         => 'Pengelolaan Keuangan Mahasiswa — CampusOS',
            'user'              => currentUser(),
            'summary'           => $summary,
            'transactions'      => $transactions,
            'categoryBreakdown' => $categoryBreakdown,
            'budgetSettings'    => $budgetSettings,
            'filters'           => $filters,
            'currentMonth'      => $currentMonth,
        ];

        $viewFile = dirname(__DIR__, 2) . '/views/finance/index.php';
        if (file_exists($viewFile)) {
            renderView('finance/index', $viewData);
        } else {
            $this->renderFinanceFallback($viewData);
        }
    }

    /**
     * Menyimpan transaksi baru (pemasukan atau pengeluaran)
     */
    public function storeTransaction(): void
    {
        $userId = currentUserId();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=finance');
            exit;
        }

        $type            = in_array($_POST['type'] ?? '', ['income', 'expense']) ? $_POST['type'] : 'expense';
        $category        = trim($_POST['category'] ?? '');
        $amount          = (float) ($_POST['amount'] ?? 0);
        $transactionDate = trim($_POST['transaction_date'] ?? date('Y-m-d'));
        $description     = trim($_POST['description'] ?? '');

        if ($amount <= 0 || empty($category) || empty($transactionDate)) {
            setFlash('danger', 'Nominal harus lebih dari Rp 0, kategori dan tanggal wajib diisi.');
            header('Location: index.php?page=finance');
            exit;
        }

        $data = [
            'type'             => $type,
            'category'         => $category,
            'amount'           => $amount,
            'transaction_date' => $transactionDate,
            'description'      => $description,
        ];

        $insertedId = $this->financeModel->createTransaction($userId, $data);

        if ($insertedId) {
            $label = ($type === 'income') ? 'Pemasukan' : 'Pengeluaran';
            setFlash('success', "{$label} sebesar Rp " . number_format($amount, 0, ',', '.') . " berhasil dicatat.");
        } else {
            setFlash('danger', 'Gagal menyimpan transaksi ke basis data.');
        }

        $monthStr = substr($transactionDate, 0, 7);
        header("Location: index.php?page=finance&month={$monthStr}");
        exit;
    }

    /**
     * Memperbarui transaksi yang sudah ada
     */
    public function updateTransaction(): void
    {
        $userId = currentUserId();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=finance');
            exit;
        }

        $id              = (int) ($_POST['id'] ?? 0);
        $type            = in_array($_POST['type'] ?? '', ['income', 'expense']) ? $_POST['type'] : 'expense';
        $category        = trim($_POST['category'] ?? '');
        $amount          = (float) ($_POST['amount'] ?? 0);
        $transactionDate = trim($_POST['transaction_date'] ?? date('Y-m-d'));
        $description     = trim($_POST['description'] ?? '');

        if ($id <= 0 || $amount <= 0 || empty($category) || empty($transactionDate)) {
            setFlash('danger', 'Data transaksi tidak lengkap atau tidak valid.');
            header('Location: index.php?page=finance');
            exit;
        }

        $data = [
            'type'             => $type,
            'category'         => $category,
            'amount'           => $amount,
            'transaction_date' => $transactionDate,
            'description'      => $description,
        ];

        $success = $this->financeModel->updateTransaction($id, $userId, $data);

        if ($success) {
            setFlash('success', 'Catatan transaksi berhasil diperbarui.');
        } else {
            setFlash('danger', 'Gagal memperbarui catatan transaksi.');
        }

        $monthStr = substr($transactionDate, 0, 7);
        header("Location: index.php?page=finance&month={$monthStr}");
        exit;
    }

    /**
     * Menghapus transaksi milik pengguna aktif
     */
    public function deleteTransaction(): void
    {
        $userId = currentUserId();

        $id = (int) ($_POST['id'] ?? ($_GET['id'] ?? 0));
        $month = trim($_GET['month'] ?? date('Y-m'));

        if ($id <= 0) {
            setFlash('danger', 'ID transaksi tidak valid.');
            header('Location: index.php?page=finance');
            exit;
        }

        $success = $this->financeModel->deleteTransaction($id, $userId);

        if ($success) {
            setFlash('success', 'Transaksi berhasil dihapus dari catatan.');
        } else {
            setFlash('danger', 'Gagal menghapus transaksi.');
        }

        header("Location: index.php?page=finance&month={$month}");
        exit;
    }

    /**
     * Menyimpan pengaturan batas anggaran bulanan
     */
    public function saveBudget(): void
    {
        $userId = currentUserId();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=finance');
            exit;
        }

        $monthlyLimit = (float) ($_POST['monthly_limit'] ?? 0);
        $month = trim($_POST['month'] ?? date('Y-m'));
        $warningThreshold = max(50, min(95, (int) ($_POST['warning_threshold_percent'] ?? 80)));

        if ($monthlyLimit < 0) {
            setFlash('danger', 'Batas anggaran tidak boleh bernilai negatif.');
            header('Location: index.php?page=finance');
            exit;
        }

        $success = $this->financeModel->upsertBudget($userId, $month, $monthlyLimit, $warningThreshold);

        if ($success) {
            setFlash('success', 'Target batas anggaran bulanan berhasil disimpan.');
        } else {
            setFlash('danger', 'Gagal menyimpan pengaturan anggaran.');
        }

        header("Location: index.php?page=finance&month={$month}");
        exit;
    }

    /**
     * API JSON Ringkasan Keuangan untuk update widget asynchronous
     */
    public function apiSummary(): void
    {
        $userId = currentUserId();
        $month = trim($_GET['month'] ?? date('Y-m'));

        $summary = $this->financeService->getMonthlySummary($userId, $month);

        header('Content-Type: application/json');
        echo json_encode($summary);
        exit;
    }

    /**
     * Render antarmuka Keuangan berbasis Bento Grid
     * Digunakan ketika berkas views/finance/index.php belum dibuat oleh tim frontend
     */
    private function renderFinanceFallback(array $data): void
    {
        extract($data, EXTR_SKIP);

        $monthNamesIndo = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        $curYear = (int) substr($currentMonth, 0, 4);
        $curMonthNum = (int) substr($currentMonth, 5, 2);
        $monthNameStr = ($monthNamesIndo[$curMonthNum] ?? 'Bulan') . ' ' . $curYear;

        // Navigasi bulan sebelumnya dan sesudahnya
        $prevTs = strtotime("{$currentMonth}-01 -1 month");
        $nextTs = strtotime("{$currentMonth}-01 +1 month");
        $prevMonth = date('Y-m', $prevTs);
        $nextMonth = date('Y-m', $nextTs);

        ob_start();
        ?>
        <!-- Header & Navigasi Periode -->
        <div class="canvas-top-bar">
            <div class="canvas-top-left">
                <header class="canvas-header">
                    <h1 class="greeting-title">Pengelolaan Keuangan Mahasiswa 💳</h1>
                    <p class="greeting-subtitle">Pantau arus kas harian, alokasi anggaran bulanan, dan proyeksi sisa uang saku.</p>
                </header>
            </div>
            <div class="canvas-top-right" style="display:flex; gap:8px;">
                <div style="display:flex; align-items:center; background:var(--color-bg-surface); border:1px solid var(--color-border); border-radius:var(--radius-sm); padding:2px 8px;">
                    <a href="index.php?page=finance&month=<?= $prevMonth ?>" class="cal-nav-btn" title="Bulan Sebelumnya" style="text-decoration:none; display:inline-flex; align-items:center;">&larr;</a>
                    <span style="font-size:var(--font-size-sm); font-weight:var(--font-weight-bold); padding:0 8px;">
                        <?= e($monthNameStr) ?>
                    </span>
                    <a href="index.php?page=finance&month=<?= $nextMonth ?>" class="cal-nav-btn" title="Bulan Berikutnya" style="text-decoration:none; display:inline-flex; align-items:center;">&rarr;</a>
                </div>
                <button type="button" class="filter-pill" onclick="document.getElementById('budgetModal').style.display='flex';" style="cursor:pointer;">
                    ⚙️ Atur Anggaran
                </button>
                <button type="button" class="btn-lime" onclick="openAddTransactionModal('expense')" style="cursor:pointer; display:inline-flex; align-items:center; gap:6px;">
                    <svg class="ui-icon ui-icon-xs" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                    <span>+ Catat Transaksi</span>
                </button>
            </div>
        </div>

        <!-- 4 KPI Cards -->
        <section class="summary-cards-grid" style="grid-template-columns: repeat(4, 1fr); margin-bottom: 20px;">
            <article class="summary-card">
                <div class="summary-card-header">
                    <div class="summary-icon-box lavender"><svg class="ui-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"></rect><line x1="2" y1="10" x2="22" y2="10"></line></svg></div>
                    <div class="summary-title-group">
                        <h2 class="summary-main-title">Rp <?= number_format($summary['monthly_limit'], 0, ',', '.') ?></h2>
                        <span class="summary-subtitle">Batas Anggaran Bulanan</span>
                    </div>
                </div>
            </article>

            <article class="summary-card">
                <div class="summary-card-header">
                    <div class="summary-icon-box lime"><svg class="ui-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="19" x2="12" y2="5"></line><polyline points="5 12 12 5 19 12"></polyline></svg></div>
                    <div class="summary-title-group">
                        <h2 class="summary-main-title" style="color:var(--color-pastel-lime-text);">Rp <?= number_format($summary['total_income'], 0, ',', '.') ?></h2>
                        <span class="summary-subtitle">Total Pemasukan</span>
                    </div>
                </div>
            </article>

            <article class="summary-card">
                <div class="summary-card-header">
                    <div class="summary-icon-box peach"><svg class="ui-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><polyline points="19 12 12 19 5 12"></polyline></svg></div>
                    <div class="summary-title-group">
                        <h2 class="summary-main-title" style="color:#ef4444;">Rp <?= number_format($summary['total_expense'], 0, ',', '.') ?></h2>
                        <span class="summary-subtitle">Total Pengeluaran</span>
                    </div>
                </div>
            </article>

            <article class="summary-card">
                <div class="summary-card-header">
                    <div class="summary-icon-box" style="background:<?= $summary['status_badge'] === 'danger' ? '#fee2e2' : ($summary['status_badge'] === 'warning' ? '#fef3c7' : '#dcfce7') ?>; color:<?= $summary['status_badge'] === 'danger' ? '#b91c1c' : ($summary['status_badge'] === 'warning' ? '#b45309' : '#15803d') ?>;">
                        <?= $summary['status_badge'] === 'danger' ? '⚠️' : ($summary['status_badge'] === 'warning' ? '⚡' : '🛡️') ?>
                    </div>
                    <div class="summary-title-group">
                        <h2 class="summary-main-title"><?= $summary['status_label'] ?></h2>
                        <span class="summary-subtitle"><?= $summary['usage_percentage'] ?>% Terpakai</span>
                    </div>
                </div>
            </article>
        </section>

        <!-- Banner Proyeksi & Wawasan Finansial Deterministik -->
        <article class="bento-card" style="padding: 20px 24px; margin-bottom: 24px; background: var(--color-bg-base); border-left: 4px solid <?= $summary['status_badge'] === 'danger' ? '#ef4444' : ($summary['status_badge'] === 'warning' ? '#f59e0b' : '#10b981') ?>;">
            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:10px;">
                <div style="display:flex; align-items:center; gap:8px;">
                    <span style="font-weight:var(--font-weight-bold); font-size:var(--font-size-sm);">
                        💡 Analisis &amp; Proyeksi Akhir Bulan (<?= e($monthNameStr) ?>)
                    </span>
                </div>
                <div style="display:flex; gap:16px; font-size:var(--font-size-xs);">
                    <span>🔥 Burn Rate: <strong>Rp <?= number_format($summary['daily_burn_rate'], 0, ',', '.') ?>/hari</strong></span>
                    <span>📊 Proyeksi Pengeluaran: <strong>Rp <?= number_format($summary['projected_total_expense'], 0, ',', '.') ?></strong></span>
                    <span>💰 Proyeksi Sisa Saldo: <strong>Rp <?= number_format(max(0, $summary['projected_end_balance']), 0, ',', '.') ?></strong></span>
                </div>
            </div>
            <p style="margin:0; font-size:var(--font-size-xs); color:var(--color-text-muted); line-height:1.6;">
                <?= e($summary['insight_message']) ?>
            </p>
        </article>

        <!-- Grid 2 Kolom: Kiri Pengeluaran Kategori, Kanan Riwayat Transaksi -->
        <div style="display:grid; grid-template-columns: 1fr 2fr; gap:20px; align-items:start;">
            
            <!-- Kolom Kiri: Breakdown Pengeluaran per Kategori -->
            <article class="bento-card" style="padding: 24px;">
                <div class="card-header-row" style="margin-bottom: 16px;">
                    <h2 class="card-heading-title">Kategori Pengeluaran</h2>
                </div>

                <?php if (empty($categoryBreakdown)): ?>
                    <p style="text-align:center; color:var(--color-text-muted); font-size:var(--font-size-xs); padding:20px 0;">Belum ada pengeluaran di bulan ini.</p>
                <?php else: ?>
                    <div style="display:flex; flex-direction:column; gap:12px;">
                        <?php foreach ($categoryBreakdown as $cat): ?>
                            <div>
                                <div style="display:flex; justify-content:space-between; font-size:var(--font-size-xs); margin-bottom:4px;">
                                    <span style="font-weight:var(--font-weight-medium);"><?= e($cat['category']) ?></span>
                                    <span>Rp <?= number_format($cat['amount'], 0, ',', '.') ?> (<?= $cat['percentage'] ?>%)</span>
                                </div>
                                <div style="width:100%; height:6px; background:var(--color-bg-base); border-radius:3px; overflow:hidden;">
                                    <div style="width:<?= min(100, $cat['percentage']) ?>%; height:100%; background:var(--color-pastel-lime-text); border-radius:3px;"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </article>

            <!-- Kolom Kanan: Tabel Riwayat Transaksi -->
            <article class="bento-card" style="padding: 24px;">
                <div class="card-header-row" style="margin-bottom: 16px;">
                    <h2 class="card-heading-title">Riwayat Transaksi (<?= count($transactions) ?>)</h2>
                    
                    <!-- Filter Tipe -->
                    <div style="display:flex; gap:6px;">
                        <a href="index.php?page=finance&month=<?= $currentMonth ?>" class="filter-pill <?= empty($filters['type']) ? 'is-active' : '' ?>" style="text-decoration:none; font-size:11px;">Semua</a>
                        <a href="index.php?page=finance&month=<?= $currentMonth ?>&type=expense" class="filter-pill <?= ($filters['type'] ?? '') === 'expense' ? 'is-active' : '' ?>" style="text-decoration:none; font-size:11px;">Pengeluaran</a>
                        <a href="index.php?page=finance&month=<?= $currentMonth ?>&type=income" class="filter-pill <?= ($filters['type'] ?? '') === 'income' ? 'is-active' : '' ?>" style="text-decoration:none; font-size:11px;">Pemasukan</a>
                    </div>
                </div>

                <?php if (empty($transactions)): ?>
                    <div style="text-align:center; padding: 36px 16px;">
                        <div style="font-size: 2rem; margin-bottom: 8px;">💸</div>
                        <p style="color: var(--color-text-muted); font-size: var(--font-size-sm); margin:0;">Belum ada catatan transaksi pada bulan ini.</p>
                    </div>
                <?php else: ?>
                    <div style="overflow-x:auto;">
                        <table style="width:100%; border-collapse:collapse; text-align:left; font-size:var(--font-size-sm);">
                            <thead>
                                <tr style="border-bottom:1px solid var(--color-border); color:var(--color-text-muted); font-size:var(--font-size-xs);">
                                    <th style="padding:8px;">TANGGAL</th>
                                    <th style="padding:8px;">KATEGORI</th>
                                    <th style="padding:8px;">KETERANGAN</th>
                                    <th style="padding:8px; text-align:right;">NOMINAL</th>
                                    <th style="padding:8px; text-align:right;">AKSI</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($transactions as $t): 
                                    $isExpense = ($t['type'] === 'expense');
                                ?>
                                    <tr style="border-bottom:1px solid var(--color-border);">
                                        <td style="padding:10px 8px; font-size:var(--font-size-xs); color:var(--color-text-muted);">
                                            <?= date('d M Y', strtotime($t['transaction_date'])) ?>
                                        </td>
                                        <td style="padding:10px 8px;">
                                            <span style="font-weight:var(--font-weight-medium);"><?= e($t['category']) ?></span>
                                        </td>
                                        <td style="padding:10px 8px; font-size:var(--font-size-xs); color:var(--color-text-muted);">
                                            <?= e($t['description'] ?? '-') ?>
                                        </td>
                                        <td style="padding:10px 8px; text-align:right; font-weight:var(--font-weight-bold); color:<?= $isExpense ? '#ef4444' : 'var(--color-pastel-lime-text)' ?>;">
                                            <?= $isExpense ? '- ' : '+ ' ?>Rp <?= number_format($t['amount'], 0, ',', '.') ?>
                                        </td>
                                        <td style="padding:10px 8px; text-align:right;">
                                            <button type="button" onclick="editTransaction(<?= htmlspecialchars(json_encode($t), ENT_QUOTES, 'UTF-8') ?>)" style="background:none; border:none; color:#4f46e5; cursor:pointer; font-size:11px; margin-right:6px;">Edit</button>
                                            <a href="index.php?page=finance&action=delete_transaction&id=<?= (int)$t['id'] ?>&month=<?= e($currentMonth) ?>" onclick="return confirm('Hapus transaksi ini?')" style="color:#ef4444; text-decoration:none; font-size:11px;">Hapus</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </article>
        </div>

        <!-- Modal Catat / Edit Transaksi -->
        <div id="transactionModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center; padding:16px;">
            <div class="bento-card" style="width:100%; max-width:480px; padding:28px; background:var(--color-bg-surface); position:relative;">
                <div class="card-header-row" style="margin-bottom: 20px;">
                    <h3 class="card-heading-title" id="transactionModalTitle">Catat Transaksi Baru</h3>
                    <button type="button" onclick="document.getElementById('transactionModal').style.display='none';" style="background:none; border:none; font-size:1.4rem; cursor:pointer;">&times;</button>
                </div>

                <form method="POST" id="transactionForm" action="index.php?page=finance&action=store_transaction">
                    <input type="hidden" name="id" id="transId" value="">

                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px; margin-bottom: 14px;">
                        <div>
                            <label style="display:block; font-size:var(--font-size-xs); font-weight:var(--font-weight-medium); margin-bottom:4px;">Tipe Transaksi *</label>
                            <select name="type" id="transType" required style="width:100%; padding:9px 12px; border-radius:var(--radius-sm); border:1px solid var(--color-border); font-size:var(--font-size-sm); background:var(--color-bg-base);">
                                <option value="expense">Pengeluaran</option>
                                <option value="income">Pemasukan</option>
                            </select>
                        </div>
                        <div>
                            <label style="display:block; font-size:var(--font-size-xs); font-weight:var(--font-weight-medium); margin-bottom:4px;">Kategori *</label>
                            <input type="text" name="category" id="transCategory" required list="categoryList" placeholder="Makanan / Kuliah / Uang Saku" style="width:100%; padding:9px 12px; border-radius:var(--radius-sm); border:1px solid var(--color-border); font-size:var(--font-size-sm); background:var(--color-bg-base);">
                            <datalist id="categoryList">
                                <option value="Makanan & Minuman">
                                <option value="Transportasi">
                                <option value="Alat Tulis & Kuliah">
                                <option value="Internet & Pulsa">
                                <option value="Uang Saku / Kiriman">
                                <option value="Gaji / Part-time">
                                <option value="Hiburan & Hobi">
                            </datalist>
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px; margin-bottom: 14px;">
                        <div>
                            <label style="display:block; font-size:var(--font-size-xs); font-weight:var(--font-weight-medium); margin-bottom:4px;">Nominal (Rp) *</label>
                            <input type="number" step="500" min="500" name="amount" id="transAmount" required placeholder="50000" style="width:100%; padding:9px 12px; border-radius:var(--radius-sm); border:1px solid var(--color-border); font-size:var(--font-size-sm); background:var(--color-bg-base);">
                        </div>
                        <div>
                            <label style="display:block; font-size:var(--font-size-xs); font-weight:var(--font-weight-medium); margin-bottom:4px;">Tanggal *</label>
                            <input type="date" name="transaction_date" id="transDate" required value="<?= date('Y-m-d') ?>" style="width:100%; padding:9px 12px; border-radius:var(--radius-sm); border:1px solid var(--color-border); font-size:var(--font-size-sm); background:var(--color-bg-base);">
                        </div>
                    </div>

                    <div style="margin-bottom: 20px;">
                        <label style="display:block; font-size:var(--font-size-xs); font-weight:var(--font-weight-medium); margin-bottom:4px;">Keterangan / Catatan</label>
                        <input type="text" name="description" id="transDesc" placeholder="Makan siang di kantin teknik" style="width:100%; padding:9px 12px; border-radius:var(--radius-sm); border:1px solid var(--color-border); font-size:var(--font-size-sm); background:var(--color-bg-base);">
                    </div>

                    <div style="display:flex; justify-content:flex-end; gap:8px;">
                        <button type="button" onclick="document.getElementById('transactionModal').style.display='none';" class="filter-pill" style="cursor:pointer;">Batal</button>
                        <button type="submit" class="btn-lime" id="transSubmitBtn" style="cursor:pointer;">Simpan Transaksi</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Atur Batas Anggaran -->
        <div id="budgetModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center; padding:16px;">
            <div class="bento-card" style="width:100%; max-width:440px; padding:28px; background:var(--color-bg-surface); position:relative;">
                <div class="card-header-row" style="margin-bottom: 20px;">
                    <h3 class="card-heading-title">Atur Batas Anggaran Bulanan</h3>
                    <button type="button" onclick="document.getElementById('budgetModal').style.display='none';" style="background:none; border:none; font-size:1.4rem; cursor:pointer;">&times;</button>
                </div>

                <form method="POST" action="index.php?page=finance&action=save_budget">
                    <input type="hidden" name="month" value="<?= e($currentMonth) ?>">

                    <div style="margin-bottom: 14px;">
                        <label style="display:block; font-size:var(--font-size-xs); font-weight:var(--font-weight-medium); margin-bottom:4px;">Batas Anggaran Bulanan (Rp) *</label>
                        <input type="number" step="50000" min="0" name="monthly_limit" required value="<?= (float)$summary['monthly_limit'] ?>" style="width:100%; padding:9px 12px; border-radius:var(--radius-sm); border:1px solid var(--color-border); font-size:var(--font-size-sm); background:var(--color-bg-base);">
                    </div>

                    <div style="margin-bottom: 20px;">
                        <label style="display:block; font-size:var(--font-size-xs); font-weight:var(--font-weight-medium); margin-bottom:4px;">Ambang Peringatan Waspada (%)</label>
                        <input type="number" min="50" max="95" name="warning_threshold_percent" value="<?= (int)$summary['warning_threshold_percent'] ?>" style="width:100%; padding:9px 12px; border-radius:var(--radius-sm); border:1px solid var(--color-border); font-size:var(--font-size-sm); background:var(--color-bg-base);">
                        <span style="font-size:11px; color:var(--color-text-muted);">Peringatan waspada akan dipicu saat pengeluaran melampaui persentase ini.</span>
                    </div>

                    <div style="display:flex; justify-content:flex-end; gap:8px;">
                        <button type="button" onclick="document.getElementById('budgetModal').style.display='none';" class="filter-pill" style="cursor:pointer;">Batal</button>
                        <button type="submit" class="btn-lime" style="cursor:pointer;">Simpan Target Anggaran</button>
                    </div>
                </form>
            </div>
        </div>

        <script>
        function openAddTransactionModal(defaultType) {
            document.getElementById('transactionModalTitle').innerText = 'Catat Transaksi Baru';
            document.getElementById('transactionForm').action = 'index.php?page=finance&action=store_transaction';
            document.getElementById('transId').value = '';
            document.getElementById('transType').value = defaultType || 'expense';
            document.getElementById('transCategory').value = '';
            document.getElementById('transAmount').value = '';
            document.getElementById('transDate').value = '<?= date('Y-m-d') ?>';
            document.getElementById('transDesc').value = '';
            document.getElementById('transSubmitBtn').innerText = 'Simpan Transaksi';
            document.getElementById('transactionModal').style.display = 'flex';
        }

        function editTransaction(trans) {
            document.getElementById('transactionModalTitle').innerText = 'Edit Transaksi';
            document.getElementById('transactionForm').action = 'index.php?page=finance&action=update_transaction';
            document.getElementById('transId').value = trans.id;
            document.getElementById('transType').value = trans.type;
            document.getElementById('transCategory').value = trans.category;
            document.getElementById('transAmount').value = trans.amount;
            document.getElementById('transDate').value = trans.transaction_date;
            document.getElementById('transDesc').value = trans.description || '';
            document.getElementById('transSubmitBtn').innerText = 'Perbarui Transaksi';
            document.getElementById('transactionModal').style.display = 'flex';
        }
        </script>
        <?php
        $contentView = ob_get_clean();
        require dirname(__DIR__, 2) . '/views/layouts/main.php';
    }
}
