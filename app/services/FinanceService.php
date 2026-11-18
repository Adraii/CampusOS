<?php
// app/services/FinanceService.php
// Layanan Analitik dan Proyeksi Keuangan Pribadi Mahasiswa (Deterministik)
// Menghitung arus kas, status anggaran, rasio pemakaian, dan proyeksi akhir periode

require_once __DIR__ . '/../models/FinanceModel.php';

class FinanceService
{
    private FinanceModel $financeModel;

    public function __construct(?FinanceModel $financeModel = null, ?PDO $db = null)
    {
        $this->financeModel = $financeModel ?? new FinanceModel($db);
    }

    /**
     * Menghasilkan ringkasan dan analisis keuangan lengkap untuk bulan tertentu
     *
     * @param int $userId
     * @param string|null $monthYear Format: 'YYYY-MM' (default bulan ini)
     * @param int|null $currentTimestamp Timestamp referensi (untuk pengujian)
     * @return array
     */
    public function getMonthlySummary(int $userId, ?string $monthYear = null, ?int $currentTimestamp = null): array
    {
        $now = $currentTimestamp ?? time();
        $monthYear = $monthYear ?? date('Y-m', $now);

        // 1. Ambil total pemasukan dan pengeluaran bulan berjalan
        $totals = $this->financeModel->getMonthlyTotals($userId, $monthYear);
        $totalIncome = (float) ($totals['income'] ?? 0.0);
        $totalExpense = (float) ($totals['expense'] ?? 0.0);
        $netBalance = $totalIncome - $totalExpense;

        // 2. Ambil pengaturan batas anggaran
        $budget = $this->financeModel->getBudgetByMonth($userId, $monthYear);
        $monthlyLimit = (float) ($budget['monthly_limit'] ?? 1500000.0);
        $warningThreshold = (int) ($budget['warning_threshold_percent'] ?? 80);

        $remainingBudget = max(0.0, $monthlyLimit - $totalExpense);
        $usagePercentage = $monthlyLimit > 0 ? round(($totalExpense / $monthlyLimit) * 100, 1) : 0.0;

        // 3. Tentukan status peringatan anggaran
        if ($monthlyLimit <= 0) {
            $status = 'safe';
            $statusBadge = 'success';
            $statusLabel = 'Aman';
        } elseif ($totalExpense > $monthlyLimit) {
            $status = 'exceeded';
            $statusBadge = 'danger';
            $statusLabel = 'Kritis (Defisit)';
        } elseif ($usagePercentage >= $warningThreshold) {
            $status = 'warning';
            $statusBadge = 'warning';
            $statusLabel = 'Waspada';
        } else {
            $status = 'safe';
            $statusBadge = 'success';
            $statusLabel = 'Aman';
        }

        // 4. Kalkulasi Proyeksi Saldo & Pengeluaran Akhir Bulan
        $monthStartTs = strtotime("{$monthYear}-01");
        $daysInMonth = (int) date('t', $monthStartTs);

        $currentMonthStr = date('Y-m', $now);
        $currentDay = (int) date('j', $now);

        if ($monthYear === $currentMonthStr) {
            $daysElapsed = max(1, $currentDay);
        } elseif ($monthYear < $currentMonthStr) {
            $daysElapsed = $daysInMonth; // Bulan lampau
        } else {
            $daysElapsed = 1; // Bulan masa depan
        }

        $daysRemaining = max(0, $daysInMonth - $daysElapsed);

        // Rata-rata pengeluaran harian (Daily Burn Rate)
        $rawBurnRate = $daysElapsed > 0 ? ($totalExpense / $daysElapsed) : 0.0;
        $dailyBurnRate = round($rawBurnRate, 2);

        // Proyeksi total pengeluaran hingga akhir bulan
        $projectedTotalExpense = round($totalExpense + ($rawBurnRate * $daysRemaining), 2);

        // Proyeksi sisa anggaran di akhir periode
        $projectedEndBalance = round($monthlyLimit - $projectedTotalExpense, 2);

        // Proyeksi selisih arus kas bersih (net cashflow)
        $projectedNetCashflow = round($totalIncome - $projectedTotalExpense, 2);

        // 5. Pesan Wawasan Deterministik (Bahasa Indonesia)
        $formattedExpense = 'Rp ' . number_format($totalExpense, 0, ',', '.');
        $formattedLimit = 'Rp ' . number_format($monthlyLimit, 0, ',', '.');
        $formattedBurn = 'Rp ' . number_format($dailyBurnRate, 0, ',', '.');
        $formattedProjected = 'Rp ' . number_format($projectedTotalExpense, 0, ',', '.');

        if ($status === 'exceeded') {
            $overAmount = 'Rp ' . number_format($totalExpense - $monthlyLimit, 0, ',', '.');
            $insightMessage = "Pengeluaran bulan ini ({$formattedExpense}) telah melampaui batas anggaran ({$formattedLimit}) sebesar {$overAmount} (Pemakaian: {$usagePercentage}%). Disarankan untuk menekan pengeluaran non-primer hingga akhir bulan.";
        } elseif ($status === 'warning') {
            $insightMessage = "Penggunaan anggaran telah mencapai {$usagePercentage}% (melewati ambang batas waspada {$warningThreshold}%). Dengan rata-rata pengeluaran {$formattedBurn}/hari, proyeksi total pengeluaran di akhir bulan mencapai {$formattedProjected}.";
        } else {
            $projectedRemainingSafe = max(0, $projectedEndBalance);
            $formattedProjRem = 'Rp ' . number_format($projectedRemainingSafe, 0, ',', '.');
            $insightMessage = "Kondisi anggaran terpantau sehat dengan pemakaian {$usagePercentage}% dari batas bulanan. Rata-rata pengeluaran terkendali di {$formattedBurn}/hari dengan proyeksi sisa anggaran akhir bulan {$formattedProjRem}.";
        }

        // 6. Pengeluaran per kategori
        $categoryBreakdown = $this->financeModel->getExpenseByCategory($userId, $monthYear);

        return [
            'month'                     => $monthYear,
            'days_in_month'             => $daysInMonth,
            'days_elapsed'              => $daysElapsed,
            'days_remaining'            => $daysRemaining,
            'total_income'              => $totalIncome,
            'total_expense'             => $totalExpense,
            'net_balance'               => $netBalance,
            'monthly_limit'             => $monthlyLimit,
            'remaining_budget'          => $remainingBudget,
            'usage_percentage'          => $usagePercentage,
            'warning_threshold_percent' => $warningThreshold,
            'status'                    => $status,          // 'safe', 'warning', 'exceeded'
            'status_badge'              => $statusBadge,    // 'success', 'warning', 'danger'
            'status_label'              => $statusLabel,    // 'Aman', 'Waspada', 'Kritis (Defisit)'
            'daily_burn_rate'           => $dailyBurnRate,
            'projected_total_expense'   => $projectedTotalExpense,
            'projected_end_balance'     => $projectedEndBalance,
            'projected_net_cashflow'    => $projectedNetCashflow,
            'insight_message'           => $insightMessage,
            'category_breakdown'        => $categoryBreakdown,
        ];
    }

    /**
     * Ringkasan dompet khusus untuk kebutuhan widget Dashboard Bento
     *
     * @param int $userId
     * @param string|null $monthYear
     * @return array
     */
    public function getWalletSummary(int $userId, ?string $monthYear = null): array
    {
        $summary = $this->getMonthlySummary($userId, $monthYear);

        return [
            'monthlyBudget'   => $summary['monthly_limit'],
            'totalExpense'    => $summary['total_expense'],
            'totalIncome'     => $summary['total_income'],
            'netBalance'      => $summary['net_balance'],
            'remainingBudget' => $summary['remaining_budget'],
            'percentUsed'     => $summary['usage_percentage'],
            'statusBadge'     => $summary['status_badge'],
            'statusLabel'     => $summary['status_label'],
            'dailyBurnRate'   => $summary['daily_burn_rate'],
            'projectedSpend'  => $summary['projected_total_expense'],
            'insight'         => $summary['insight_message'],
        ];
    }
}
