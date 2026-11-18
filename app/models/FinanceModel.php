<?php
// app/models/FinanceModel.php
// Model manajemen transaksi keuangan (transactions) dan pengaturan anggaran (budget_settings)
// Mengikuti standar PDO Prepared Statements dan isolasi WHERE user_id = :user_id

require_once __DIR__ . '/../config/database.php';

class FinanceModel
{
    private ?PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getInstance();
    }

    /**
     * Mengambil daftar transaksi milik pengguna aktif dengan opsi filter
     *
     * @param int $userId
     * @param array $filters ['month' => 'YYYY-MM', 'type' => 'income|expense', 'category' => '...', 'search' => '...']
     * @return array
     */
    public function getTransactionsByUser(int $userId, array $filters = []): array
    {
        if (!$this->db) {
            return [];
        }

        try {
            $sql = "SELECT * FROM transactions WHERE user_id = :user_id";
            $params = [':user_id' => $userId];

            // Filter bulan transaksi (format: YYYY-MM)
            if (!empty($filters['month'])) {
                $monthWildcard = trim($filters['month']) . '%';
                $sql .= " AND transaction_date LIKE :month";
                $params[':month'] = $monthWildcard;
            }

            // Filter tipe transaksi: income atau expense
            if (!empty($filters['type']) && in_array($filters['type'], ['income', 'expense'])) {
                $sql .= " AND type = :type";
                $params[':type'] = $filters['type'];
            }

            // Filter kategori transaksi
            if (!empty($filters['category'])) {
                $sql .= " AND category = :category";
                $params[':category'] = trim($filters['category']);
            }

            // Filter pencarian deskripsi / keterangan
            if (!empty($filters['search'])) {
                $sql .= " AND description LIKE :search";
                $params[':search'] = '%' . trim($filters['search']) . '%';
            }

            // Diurutkan dari tanggal transaksi terbaru
            $sql .= " ORDER BY transaction_date DESC, id DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log("FinanceModel::getTransactionsByUser Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Mengambil satu transaksi berdasarkan ID dan user_id
     *
     * @param int $id
     * @param int $userId
     * @return array|null
     */
    public function getTransactionById(int $id, int $userId): ?array
    {
        if (!$this->db) {
            return null;
        }

        try {
            $stmt = $this->db->prepare("
                SELECT * FROM transactions 
                WHERE id = :id AND user_id = :user_id 
                LIMIT 1
            ");
            $stmt->execute([
                ':id'      => $id,
                ':user_id' => $userId,
            ]);

            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (\PDOException $e) {
            error_log("FinanceModel::getTransactionById Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Menghitung total pemasukan (income) dan pengeluaran (expense) untuk bulan tertentu
     *
     * @param int $userId
     * @param string $monthYear Format: 'YYYY-MM'
     * @return array ['income' => float, 'expense' => float]
     */
    public function getMonthlyTotals(int $userId, string $monthYear): array
    {
        $default = ['income' => 0.0, 'expense' => 0.0];

        if (!$this->db) {
            return $default;
        }

        try {
            $monthWildcard = trim($monthYear) . '%';

            $stmt = $this->db->prepare("
                SELECT 
                    SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as total_income,
                    SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as total_expense
                FROM transactions 
                WHERE user_id = :user_id AND transaction_date LIKE :month
            ");
            $stmt->execute([
                ':user_id' => $userId,
                ':month'   => $monthWildcard,
            ]);

            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                return [
                    'income'  => (float) ($row['total_income'] ?? 0.0),
                    'expense' => (float) ($row['total_expense'] ?? 0.0),
                ];
            }

            return $default;
        } catch (\PDOException $e) {
            error_log("FinanceModel::getMonthlyTotals Error: " . $e->getMessage());
            return $default;
        }
    }

    /**
     * Agregasi total pengeluaran per kategori dalam bulan tertentu
     *
     * @param int $userId
     * @param string $monthYear Format: 'YYYY-MM'
     * @return array
     */
    public function getExpenseByCategory(int $userId, string $monthYear): array
    {
        if (!$this->db) {
            return [];
        }

        try {
            $monthWildcard = trim($monthYear) . '%';

            $stmt = $this->db->prepare("
                SELECT 
                    category,
                    SUM(amount) as total_amount,
                    COUNT(*) as count
                FROM transactions
                WHERE user_id = :user_id 
                  AND type = 'expense' 
                  AND transaction_date LIKE :month
                GROUP BY category
                ORDER BY total_amount DESC
            ");
            $stmt->execute([
                ':user_id' => $userId,
                ':month'   => $monthWildcard,
            ]);

            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            $totalAll = array_sum(array_column($rows, 'total_amount'));

            $result = [];
            foreach ($rows as $row) {
                $amount = (float) $row['total_amount'];
                $pct = $totalAll > 0 ? round(($amount / $totalAll) * 100, 1) : 0.0;
                $result[] = [
                    'category'   => $row['category'],
                    'amount'     => $amount,
                    'percentage' => $pct,
                    'count'      => (int) $row['count'],
                ];
            }

            return $result;
        } catch (\PDOException $e) {
            error_log("FinanceModel::getExpenseByCategory Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Menambah transaksi baru
     *
     * @param int $userId
     * @param array $data ['type' => '...', 'category' => '...', 'amount' => ..., 'transaction_date' => '...', 'description' => '...']
     * @return int|false ID transaksi baru atau false jika gagal
     */
    public function createTransaction(int $userId, array $data): int|false
    {
        if (!$this->db) {
            return false;
        }

        try {
            $type = in_array($data['type'] ?? '', ['income', 'expense']) ? $data['type'] : 'expense';
            $category = trim($data['category'] ?? 'Lain-lain');
            $amount = max(0.01, (float) ($data['amount'] ?? 0));
            $transactionDate = !empty($data['transaction_date']) ? trim($data['transaction_date']) : date('Y-m-d');
            $description = !empty($data['description']) ? trim($data['description']) : null;

            $stmt = $this->db->prepare("
                INSERT INTO transactions (
                    user_id, 
                    type, 
                    category, 
                    amount, 
                    transaction_date, 
                    description, 
                    created_at
                ) VALUES (
                    :user_id, 
                    :type, 
                    :category, 
                    :amount, 
                    :transaction_date, 
                    :description, 
                    CURRENT_TIMESTAMP
                )
            ");

            $success = $stmt->execute([
                ':user_id'          => $userId,
                ':type'             => $type,
                ':category'         => $category,
                ':amount'           => $amount,
                ':transaction_date' => $transactionDate,
                ':description'      => $description,
            ]);

            return $success ? (int) $this->db->lastInsertId() : false;
        } catch (\PDOException $e) {
            error_log("FinanceModel::createTransaction Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Memperbarui transaksi milik pengguna aktif
     *
     * @param int $id
     * @param int $userId
     * @param array $data
     * @return bool
     */
    public function updateTransaction(int $id, int $userId, array $data): bool
    {
        if (!$this->db) {
            return false;
        }

        try {
            $existing = $this->getTransactionById($id, $userId);
            if (!$existing) {
                return false;
            }

            $type = in_array($data['type'] ?? '', ['income', 'expense']) ? $data['type'] : 'expense';
            $category = trim($data['category'] ?? 'Lain-lain');
            $amount = max(0.01, (float) ($data['amount'] ?? 0));
            $transactionDate = !empty($data['transaction_date']) ? trim($data['transaction_date']) : date('Y-m-d');
            $description = !empty($data['description']) ? trim($data['description']) : null;

            $stmt = $this->db->prepare("
                UPDATE transactions SET 
                    type             = :type,
                    category         = :category,
                    amount           = :amount,
                    transaction_date = :transaction_date,
                    description      = :description
                WHERE id = :id AND user_id = :user_id
            ");

            return $stmt->execute([
                ':id'               => $id,
                ':user_id'          => $userId,
                ':type'             => $type,
                ':category'         => $category,
                ':amount'           => $amount,
                ':transaction_date' => $transactionDate,
                ':description'      => $description,
            ]);
        } catch (\PDOException $e) {
            error_log("FinanceModel::updateTransaction Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Menghapus transaksi milik pengguna aktif
     *
     * @param int $id
     * @param int $userId
     * @return bool
     */
    public function deleteTransaction(int $id, int $userId): bool
    {
        if (!$this->db) {
            return false;
        }

        try {
            $existing = $this->getTransactionById($id, $userId);
            if (!$existing) {
                return false;
            }

            $stmt = $this->db->prepare("DELETE FROM transactions WHERE id = :id AND user_id = :user_id");
            return $stmt->execute([
                ':id'      => $id,
                ':user_id' => $userId,
            ]);
        } catch (\PDOException $e) {
            error_log("FinanceModel::deleteTransaction Error: " . $e->getMessage());
            return false;
        }
    }

    // ========================================================
    // Budget Settings Operations
    // ========================================================

    /**
     * Mengambil konfigurasi target batas anggaran bulanan
     *
     * @param int $userId
     * @param string $monthYear
     * @return array
     */
    public function getBudgetByMonth(int $userId, string $monthYear): array
    {
        $default = [
            'user_id'                   => $userId,
            'month'                     => $monthYear,
            'monthly_limit'             => 1500000.0,
            'monthly_budget'            => 1500000.0,
            'warning_threshold_percent' => 80,
            'period_start_day'          => 1,
        ];

        if (!$this->db) {
            return $default;
        }

        try {
            $stmt = $this->db->prepare("
                SELECT * FROM budget_settings 
                WHERE user_id = :user_id 
                LIMIT 1
            ");
            $stmt->execute([':user_id' => $userId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                $limit = (float) ($row['monthly_budget'] ?? 1500000.0);
                return [
                    'user_id'                   => (int) $row['user_id'],
                    'month'                     => $monthYear,
                    'monthly_limit'             => $limit,
                    'monthly_budget'            => $limit,
                    'warning_threshold_percent' => 80,
                    'period_start_day'          => (int) ($row['period_start_day'] ?? 1),
                ];
            }

            return $default;
        } catch (\PDOException $e) {
            error_log("FinanceModel::getBudgetByMonth Error: " . $e->getMessage());
            return $default;
        }
    }

    /**
     * Menyimpan atau memperbarui batas target anggaran bulanan (upsert)
     *
     * @param int $userId
     * @param string $monthYear
     * @param float $monthlyLimit
     * @param int $warningThreshold Default: 80
     * @return bool
     */
    public function upsertBudget(int $userId, string $monthYear, float $monthlyLimit, int $warningThreshold = 80): bool
    {
        if (!$this->db) {
            return false;
        }

        try {
            $monthlyLimit = max(0.0, $monthlyLimit);
            $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);

            if ($driver === 'sqlite') {
                $sql = "
                    INSERT INTO budget_settings (user_id, monthly_budget, period_start_day, updated_at)
                    VALUES (:user_id, :monthly_budget, 1, CURRENT_TIMESTAMP)
                    ON CONFLICT(user_id) DO UPDATE SET
                        monthly_budget = excluded.monthly_budget,
                        updated_at     = CURRENT_TIMESTAMP
                ";
            } else {
                $sql = "
                    INSERT INTO budget_settings (user_id, monthly_budget, period_start_day)
                    VALUES (:user_id, :monthly_budget, 1)
                    ON DUPLICATE KEY UPDATE
                        monthly_budget = VALUES(monthly_budget),
                        updated_at     = CURRENT_TIMESTAMP
                ";
            }

            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':user_id'        => $userId,
                ':monthly_budget' => $monthlyLimit,
            ]);
        } catch (\PDOException $e) {
            error_log("FinanceModel::upsertBudget Error: " . $e->getMessage());
            return false;
        }
    }
}
