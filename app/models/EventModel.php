<?php
// app/models/EventModel.php
// Model manajemen data agenda & kegiatan (events)
// Mengikuti standar PDO Prepared Statements dan isolasi data user_id

require_once __DIR__ . '/../config/database.php';

class EventModel
{
    private ?PDO $db;

    /**
     * Inisialisasi model dengan koneksi PDO
     *
     * @param PDO|null $db
     */
    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? getDBConnection();
    }

    /**
     * Mengambil seluruh agenda kegiatan milik pengguna aktif
     * Opsi filter rentang tanggal (startDate dan endDate)
     *
     * @param int $userId
     * @param string|null $startDate Format YYYY-MM-DD
     * @param string|null $endDate Format YYYY-MM-DD
     * @return array
     */
    public function getAllByUserId(int $userId, ?string $startDate = null, ?string $endDate = null): array
    {
        if (!$this->db) {
            return [];
        }

        try {
            $sql = "SELECT * FROM events WHERE user_id = :user_id";
            $params = [':user_id' => $userId];

            if (!empty($startDate) && !empty($endDate)) {
                $sql .= " AND event_date BETWEEN :start_date AND :end_date";
                $params[':start_date'] = $startDate;
                $params[':end_date']   = $endDate;
            } elseif (!empty($startDate)) {
                $sql .= " AND event_date >= :start_date";
                $params[':start_date'] = $startDate;
            } elseif (!empty($endDate)) {
                $sql .= " AND event_date <= :end_date";
                $params[':end_date']   = $endDate;
            }

            $sql .= " ORDER BY event_date ASC, start_time ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log("EventModel::getAllByUserId Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Mengambil detail satu event berdasarkan ID dan kepemilikan user_id
     *
     * @param int $id
     * @param int $userId
     * @return array|null
     */
    public function getById(int $id, int $userId): ?array
    {
        if (!$this->db) {
            return null;
        }

        try {
            $stmt = $this->db->prepare("
                SELECT * FROM events 
                WHERE id = :id AND user_id = :user_id 
                LIMIT 1
            ");
            $stmt->execute([
                ':id'      => $id,
                ':user_id' => $userId,
            ]);

            $event = $stmt->fetch(PDO::FETCH_ASSOC);
            return $event ?: null;
        } catch (\PDOException $e) {
            error_log("EventModel::getById Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Mengambil daftar kegiatan pengguna pada tanggal tertentu
     *
     * @param int $userId
     * @param string $date Format YYYY-MM-DD
     * @return array
     */
    public function getByDate(int $userId, string $date): array
    {
        if (!$this->db) {
            return [];
        }

        try {
            $stmt = $this->db->prepare("
                SELECT * FROM events 
                WHERE user_id = :user_id AND event_date = :event_date 
                ORDER BY start_time ASC
            ");
            $stmt->execute([
                ':user_id'    => $userId,
                ':event_date' => $date,
            ]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log("EventModel::getByDate Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Mengambil agenda kegiatan dalam rentang tanggal tertentu
     *
     * @param int $userId
     * @param string $startDate Format YYYY-MM-DD
     * @param string $endDate Format YYYY-MM-DD
     * @return array
     */
    public function getByDateRange(int $userId, string $startDate, string $endDate): array
    {
        return $this->getAllByUserId($userId, $startDate, $endDate);
    }

    /**
     * Menambahkan kegiatan / event baru ke basis data
     *
     * @param int $userId
     * @param array $data
     * @return int|false ID event baru atau false jika gagal
     */
    public function create(int $userId, array $data): int|false
    {
        if (!$this->db) {
            return false;
        }

        try {
            $validCategories = ['personal', 'organization', 'study_session', 'exam'];
            $category = in_array($data['category'] ?? '', $validCategories) ? $data['category'] : 'personal';

            $startTime = !empty($data['start_time']) ? trim($data['start_time']) : '08:00:00';
            if (strlen($startTime) === 5) {
                $startTime .= ':00';
            }

            $endTime = !empty($data['end_time']) ? trim($data['end_time']) : '09:00:00';
            if (strlen($endTime) === 5) {
                $endTime .= ':00';
            }

            $stmt = $this->db->prepare("
                INSERT INTO events (
                    user_id,
                    title,
                    category,
                    event_date,
                    start_time,
                    end_time,
                    location,
                    notes,
                    created_at
                ) VALUES (
                    :user_id,
                    :title,
                    :category,
                    :event_date,
                    :start_time,
                    :end_time,
                    :location,
                    :notes,
                    CURRENT_TIMESTAMP
                )
            ");

            $success = $stmt->execute([
                ':user_id'    => $userId,
                ':title'      => trim($data['title'] ?? ''),
                ':category'   => $category,
                ':event_date' => $data['event_date'],
                ':start_time' => $startTime,
                ':end_time'   => $endTime,
                ':location'   => !empty($data['location']) ? trim($data['location']) : null,
                ':notes'      => !empty($data['notes']) ? trim($data['notes']) : null,
            ]);

            return $success ? (int) $this->db->lastInsertId() : false;
        } catch (\PDOException $e) {
            error_log("EventModel::create Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Memperbarui kegiatan milik pengguna aktif
     *
     * @param int $id
     * @param int $userId
     * @param array $data
     * @return bool
     */
    public function update(int $id, int $userId, array $data): bool
    {
        if (!$this->db) {
            return false;
        }

        try {
            $validCategories = ['personal', 'organization', 'study_session', 'exam'];
            $category = in_array($data['category'] ?? '', $validCategories) ? $data['category'] : 'personal';

            $startTime = !empty($data['start_time']) ? trim($data['start_time']) : '08:00:00';
            if (strlen($startTime) === 5) {
                $startTime .= ':00';
            }

            $endTime = !empty($data['end_time']) ? trim($data['end_time']) : '09:00:00';
            if (strlen($endTime) === 5) {
                $endTime .= ':00';
            }

            $stmt = $this->db->prepare("
                UPDATE events SET
                    title      = :title,
                    category   = :category,
                    event_date = :event_date,
                    start_time = :start_time,
                    end_time   = :end_time,
                    location   = :location,
                    notes      = :notes
                WHERE id = :id AND user_id = :user_id
            ");

            return $stmt->execute([
                ':id'         => $id,
                ':user_id'    => $userId,
                ':title'      => trim($data['title'] ?? ''),
                ':category'   => $category,
                ':event_date' => $data['event_date'],
                ':start_time' => $startTime,
                ':end_time'   => $endTime,
                ':location'   => !empty($data['location']) ? trim($data['location']) : null,
                ':notes'      => !empty($data['notes']) ? trim($data['notes']) : null,
            ]);
        } catch (\PDOException $e) {
            error_log("EventModel::update Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Menghapus kegiatan milik pengguna aktif
     *
     * @param int $id
     * @param int $userId
     * @return bool
     */
    public function delete(int $id, int $userId): bool
    {
        if (!$this->db) {
            return false;
        }

        try {
            $stmt = $this->db->prepare("DELETE FROM events WHERE id = :id AND user_id = :user_id");
            return $stmt->execute([
                ':id'      => $id,
                ':user_id' => $userId,
            ]);
        } catch (\PDOException $e) {
            error_log("EventModel::delete Error: " . $e->getMessage());
            return false;
        }
    }
}
