<?php
// app/models/ScheduleModel.php
// Model data jadwal kuliah tetap (class_schedules)
// Mengikuti standar PDO Prepared Statements dan isolasi data WHERE user_id = :user_id

require_once __DIR__ . '/../config/database.php';

class ScheduleModel
{
    private ?PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getInstance();
    }

    /**
     * Mengambil seluruh jadwal kuliah mingguan lengkap beserta informasi mata kuliah
     *
     * @param int $userId
     * @return array
     */
    public function getWeeklySchedules(int $userId): array
    {
        if (!$this->db) {
            return [];
        }

        try {
            $stmt = $this->db->prepare("
                SELECT 
                    cs.*, 
                    c.course_name, 
                    c.course_code, 
                    c.color_hex, 
                    c.lecturer,
                    c.credits
                FROM class_schedules cs
                JOIN courses c ON cs.course_id = c.id
                WHERE cs.user_id = :user_id
                ORDER BY cs.day_of_week ASC, cs.start_time ASC
            ");
            $stmt->execute([':user_id' => $userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log("ScheduleModel::getWeeklySchedules Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Mengambil satu data jadwal kuliah berdasarkan ID dan user_id
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
                SELECT 
                    cs.*, 
                    c.course_name, 
                    c.course_code, 
                    c.color_hex, 
                    c.lecturer,
                    c.credits
                FROM class_schedules cs
                JOIN courses c ON cs.course_id = c.id
                WHERE cs.id = :id AND cs.user_id = :user_id
                LIMIT 1
            ");
            $stmt->execute([
                ':id'      => $id,
                ':user_id' => $userId,
            ]);
            $schedule = $stmt->fetch(PDO::FETCH_ASSOC);
            return $schedule ?: null;
        } catch (\PDOException $e) {
            error_log("ScheduleModel::getById Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Mengambil jadwal kuliah pada hari tertentu dalam seminggu
     *
     * @param int $userId
     * @param int $dayOfWeek 1 = Senin, ..., 7 = Minggu
     * @return array
     */
    public function getByDayOfWeek(int $userId, int $dayOfWeek): array
    {
        if (!$this->db) {
            return [];
        }

        try {
            $stmt = $this->db->prepare("
                SELECT 
                    cs.*, 
                    c.course_name, 
                    c.course_code, 
                    c.color_hex, 
                    c.lecturer 
                FROM class_schedules cs
                JOIN courses c ON cs.course_id = c.id
                WHERE cs.user_id = :user_id AND cs.day_of_week = :day_of_week
                ORDER BY cs.start_time ASC
            ");
            $stmt->execute([
                ':user_id'     => $userId,
                ':day_of_week' => $dayOfWeek,
            ]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log("ScheduleModel::getByDayOfWeek Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Menambah jadwal kuliah baru
     *
     * @param int $userId
     * @param array $data
     * @return int|false ID jadwal baru atau false jika gagal
     */
    public function create(int $userId, array $data): int|false
    {
        if (!$this->db) {
            return false;
        }

        try {
            $startTime = trim($data['start_time'] ?? '08:00:00');
            if (strlen($startTime) === 5) {
                $startTime .= ':00';
            }

            $endTime = trim($data['end_time'] ?? '10:00:00');
            if (strlen($endTime) === 5) {
                $endTime .= ':00';
            }

            $stmt = $this->db->prepare("
                INSERT INTO class_schedules (
                    user_id, 
                    course_id, 
                    day_of_week, 
                    start_time, 
                    end_time, 
                    room
                ) VALUES (
                    :user_id, 
                    :course_id, 
                    :day_of_week, 
                    :start_time, 
                    :end_time, 
                    :room
                )
            ");

            $success = $stmt->execute([
                ':user_id'     => $userId,
                ':course_id'   => (int) $data['course_id'],
                ':day_of_week' => (int) $data['day_of_week'],
                ':start_time'  => $startTime,
                ':end_time'    => $endTime,
                ':room'        => !empty($data['room']) ? trim($data['room']) : null,
            ]);

            return $success ? (int) $this->db->lastInsertId() : false;
        } catch (\PDOException $e) {
            error_log("ScheduleModel::create Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Menghapus jadwal perkuliahan
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
            $existing = $this->getById($id, $userId);
            if (!$existing) {
                return false;
            }

            $stmt = $this->db->prepare("DELETE FROM class_schedules WHERE id = :id AND user_id = :user_id");
            return $stmt->execute([
                ':id'      => $id,
                ':user_id' => $userId,
            ]);
        } catch (\PDOException $e) {
            error_log("ScheduleModel::delete Error: " . $e->getMessage());
            return false;
        }
    }
}
