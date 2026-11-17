<?php
// app/models/TaskModel.php
// Model manajemen data tugas (tasks) dan preferensi belajar (planning_preferences)
// Mengikuti standar PDO Prepared Statements dan isolasi data user_id

require_once __DIR__ . '/../config/database.php';

class TaskModel
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
     * Mengambil semua daftar tugas milik user tertentu dengan opsi filter
     *
     * @param int $userId
     * @param array $filters ['status' => '...', 'priority' => '...', 'course_id' => ..., 'search' => '...']
     * @return array
     */
    public function getAllByUserId(int $userId, array $filters = []): array
    {
        if (!$this->db) {
            return [];
        }

        try {
            $sql = "
                SELECT 
                    t.*, 
                    c.course_name, 
                    c.course_code, 
                    c.color_hex,
                    c.lecturer
                FROM tasks t
                LEFT JOIN courses c ON t.course_id = c.id
                WHERE t.user_id = :user_id
            ";

            $params = [':user_id' => $userId];

            // Filter berdasarkan status
            if (!empty($filters['status'])) {
                if ($filters['status'] === 'active' || $filters['status'] === 'not_done') {
                    $sql .= " AND t.status != 'done'";
                } elseif (in_array($filters['status'], ['pending', 'in_progress', 'done'])) {
                    $sql .= " AND t.status = :status";
                    $params[':status'] = $filters['status'];
                }
            }

            // Filter berdasarkan prioritas
            if (!empty($filters['priority']) && in_array($filters['priority'], ['low', 'medium', 'high'])) {
                $sql .= " AND t.priority = :priority";
                $params[':priority'] = $filters['priority'];
            }

            // Filter berdasarkan mata kuliah
            if (!empty($filters['course_id'])) {
                $sql .= " AND t.course_id = :course_id";
                $params[':course_id'] = (int) $filters['course_id'];
            }

            // Filter pencarian teks judul atau deskripsi
            if (!empty($filters['search'])) {
                $sql .= " AND (t.title LIKE :search_title OR t.description LIKE :search_desc)";
                $searchWildcard = '%' . trim($filters['search']) . '%';
                $params[':search_title'] = $searchWildcard;
                $params[':search_desc'] = $searchWildcard;
            }

            // Urutan: tugas belum selesai didahulukan, diurutkan deadline terdekat
            $sql .= " ORDER BY (t.status = 'done') ASC, t.deadline_date ASC, t.deadline_time ASC, t.id DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log("TaskModel::getAllByUserId Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Mengambil detail tugas berdasarkan ID tugas dan kepemilikan user_id
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
                    t.*, 
                    c.course_name, 
                    c.course_code, 
                    c.color_hex,
                    c.lecturer
                FROM tasks t
                LEFT JOIN courses c ON t.course_id = c.id
                WHERE t.id = :id AND t.user_id = :user_id
                LIMIT 1
            ");
            $stmt->execute([
                ':id'      => $id,
                ':user_id' => $userId,
            ]);

            $task = $stmt->fetch(PDO::FETCH_ASSOC);
            return $task ?: null;
        } catch (\PDOException $e) {
            error_log("TaskModel::getById Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Mengambil tugas yang belum selesai (pending atau in_progress)
     * Digunakan oleh SmartPlannerService dan Dashboard
     *
     * @param int $userId
     * @return array
     */
    public function getPendingTasks(int $userId): array
    {
        if (!$this->db) {
            return [];
        }

        try {
            $stmt = $this->db->prepare("
                SELECT 
                    t.*, 
                    c.course_name, 
                    c.course_code, 
                    c.color_hex
                FROM tasks t
                LEFT JOIN courses c ON t.course_id = c.id
                WHERE t.user_id = :user_id AND t.status != 'done'
                ORDER BY t.deadline_date ASC, t.deadline_time ASC
            ");
            $stmt->execute([':user_id' => $userId]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log("TaskModel::getPendingTasks Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Mengambil tugas mendesak dengan limit tertentu
     *
     * @param int $userId
     * @param int $limit
     * @return array
     */
    public function getUrgentTasks(int $userId, int $limit = 5): array
    {
        if (!$this->db) {
            return [];
        }

        try {
            $limit = max(1, min(50, (int) $limit));
            $stmt = $this->db->prepare("
                SELECT 
                    t.*, 
                    c.course_name, 
                    c.course_code, 
                    c.color_hex
                FROM tasks t
                LEFT JOIN courses c ON t.course_id = c.id
                WHERE t.user_id = :user_id AND t.status != 'done'
                ORDER BY t.deadline_date ASC, t.deadline_time ASC
                LIMIT {$limit}
            ");
            $stmt->execute([':user_id' => $userId]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log("TaskModel::getUrgentTasks Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Menambahkan tugas baru ke basis data
     *
     * @param int $userId
     * @param array $data
     * @return int|false ID tugas yang baru dibuat atau false jika gagal
     */
    public function create(int $userId, array $data): int|false
    {
        if (!$this->db) {
            return false;
        }

        try {
            $stmt = $this->db->prepare("
                INSERT INTO tasks (
                    user_id, 
                    course_id, 
                    title, 
                    description, 
                    deadline_date, 
                    deadline_time, 
                    estimated_hours, 
                    priority, 
                    status, 
                    created_at
                ) VALUES (
                    :user_id, 
                    :course_id, 
                    :title, 
                    :description, 
                    :deadline_date, 
                    :deadline_time, 
                    :estimated_hours, 
                    :priority, 
                    :status, 
                    CURRENT_TIMESTAMP
                )
            ");

            $courseId = !empty($data['course_id']) ? (int) $data['course_id'] : null;
            $deadlineTime = !empty($data['deadline_time']) ? trim($data['deadline_time']) : '23:59:00';
            if (strlen($deadlineTime) === 5) {
                $deadlineTime .= ':00';
            }
            $estimatedHours = isset($data['estimated_hours']) ? (float) $data['estimated_hours'] : 2.0;
            $priority = in_array($data['priority'] ?? '', ['low', 'medium', 'high']) ? $data['priority'] : 'medium';
            $status = in_array($data['status'] ?? '', ['pending', 'in_progress', 'done']) ? $data['status'] : 'pending';

            $success = $stmt->execute([
                ':user_id'         => $userId,
                ':course_id'       => $courseId,
                ':title'           => trim($data['title'] ?? ''),
                ':description'     => !empty($data['description']) ? trim($data['description']) : null,
                ':deadline_date'   => $data['deadline_date'],
                ':deadline_time'   => $deadlineTime,
                ':estimated_hours' => $estimatedHours,
                ':priority'        => $priority,
                ':status'          => $status,
            ]);

            return $success ? (int) $this->db->lastInsertId() : false;
        } catch (\PDOException $e) {
            error_log("TaskModel::create Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Memperbarui data tugas milik pengguna aktif
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
            $stmt = $this->db->prepare("
                UPDATE tasks SET 
                    course_id       = :course_id,
                    title           = :title,
                    description     = :description,
                    deadline_date   = :deadline_date,
                    deadline_time   = :deadline_time,
                    estimated_hours = :estimated_hours,
                    priority        = :priority,
                    status          = :status
                WHERE id = :id AND user_id = :user_id
            ");

            $courseId = !empty($data['course_id']) ? (int) $data['course_id'] : null;
            $deadlineTime = !empty($data['deadline_time']) ? trim($data['deadline_time']) : '23:59:00';
            if (strlen($deadlineTime) === 5) {
                $deadlineTime .= ':00';
            }
            $estimatedHours = isset($data['estimated_hours']) ? (float) $data['estimated_hours'] : 2.0;
            $priority = in_array($data['priority'] ?? '', ['low', 'medium', 'high']) ? $data['priority'] : 'medium';
            $status = in_array($data['status'] ?? '', ['pending', 'in_progress', 'done']) ? $data['status'] : 'pending';

            return $stmt->execute([
                ':id'              => $id,
                ':user_id'         => $userId,
                ':course_id'       => $courseId,
                ':title'           => trim($data['title'] ?? ''),
                ':description'     => !empty($data['description']) ? trim($data['description']) : null,
                ':deadline_date'   => $data['deadline_date'],
                ':deadline_time'   => $deadlineTime,
                ':estimated_hours' => $estimatedHours,
                ':priority'        => $priority,
                ':status'          => $status,
            ]);
        } catch (\PDOException $e) {
            error_log("TaskModel::update Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mengubah status tugas secara cepat (pending, in_progress, done)
     *
     * @param int $id
     * @param int $userId
     * @param string $status
     * @return bool
     */
    public function updateStatus(int $id, int $userId, string $status): bool
    {
        if (!$this->db) {
            return false;
        }

        if (!in_array($status, ['pending', 'in_progress', 'done'])) {
            return false;
        }

        try {
            $stmt = $this->db->prepare("
                UPDATE tasks 
                SET status = :status 
                WHERE id = :id AND user_id = :user_id
            ");

            return $stmt->execute([
                ':id'      => $id,
                ':user_id' => $userId,
                ':status'  => $status,
            ]);
        } catch (\PDOException $e) {
            error_log("TaskModel::updateStatus Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Menghapus tugas milik pengguna aktif
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
            $stmt = $this->db->prepare("DELETE FROM tasks WHERE id = :id AND user_id = :user_id");
            return $stmt->execute([
                ':id'      => $id,
                ':user_id' => $userId,
            ]);
        } catch (\PDOException $e) {
            error_log("TaskModel::delete Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Menghitung statistik ringkas tugas pengguna aktif
     *
     * @param int $userId
     * @return array
     */
    public function getCountsByStatus(int $userId): array
    {
        $default = [
            'total'       => 0,
            'pending'     => 0,
            'in_progress' => 0,
            'done'        => 0,
            'overdue'     => 0,
        ];

        if (!$this->db) {
            return $default;
        }

        try {
            $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
            $overdueCondition = ($driver === 'sqlite')
                ? "(deadline_date || ' ' || deadline_time) < CURRENT_TIMESTAMP"
                : "CONCAT(deadline_date, ' ', deadline_time) < CURRENT_TIMESTAMP";

            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                    SUM(CASE WHEN status = 'done' THEN 1 ELSE 0 END) as done,
                    SUM(CASE WHEN status != 'done' AND {$overdueCondition} THEN 1 ELSE 0 END) as overdue
                FROM tasks 
                WHERE user_id = :user_id
            ");
            $stmt->execute([':user_id' => $userId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                return [
                    'total'       => (int) ($row['total'] ?? 0),
                    'pending'     => (int) ($row['pending'] ?? 0),
                    'in_progress' => (int) ($row['in_progress'] ?? 0),
                    'done'        => (int) ($row['done'] ?? 0),
                    'overdue'     => (int) ($row['overdue'] ?? 0),
                ];
            }

            return $default;
        } catch (\PDOException $e) {
            error_log("TaskModel::getCountsByStatus Error: " . $e->getMessage());
            return $default;
        }
    }

    // ========================================================
    // Planning Preferences Management
    // ========================================================

    /**
     * Mengambil preferensi jam belajar pengguna untuk Smart Planner
     * Jika belum diatur di database, kembalikan nilai default standar
     *
     * @param int $userId
     * @return array
     */
    public function getPlanningPreferences(int $userId): array
    {
        $default = [
            'user_id'                 => $userId,
            'study_start_hour'        => '08:00:00',
            'study_end_hour'          => '21:00:00',
            'max_daily_study_hours'   => 4,
            'preferred_session_hours' => 2,
        ];

        if (!$this->db) {
            return $default;
        }

        try {
            $stmt = $this->db->prepare("
                SELECT 
                    user_id,
                    study_start_hour,
                    study_end_hour,
                    max_daily_study_hours,
                    preferred_session_hours
                FROM planning_preferences
                WHERE user_id = :user_id
                LIMIT 1
            ");
            $stmt->execute([':user_id' => $userId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                return [
                    'user_id'                 => (int) $row['user_id'],
                    'study_start_hour'        => $row['study_start_hour'] ?? '08:00:00',
                    'study_end_hour'          => $row['study_end_hour'] ?? '21:00:00',
                    'max_daily_study_hours'   => (int) ($row['max_daily_study_hours'] ?? 4),
                    'preferred_session_hours' => (int) ($row['preferred_session_hours'] ?? 2),
                ];
            }

            return $default;
        } catch (\PDOException $e) {
            error_log("TaskModel::getPlanningPreferences Error: " . $e->getMessage());
            return $default;
        }
    }

    /**
     * Memperbarui atau menyimpan preferensi jam belajar (upsert)
     *
     * @param int $userId
     * @param array $preferences
     * @return bool
     */
    public function updatePlanningPreferences(int $userId, array $preferences): bool
    {
        if (!$this->db) {
            return false;
        }

        try {
            $startHour = !empty($preferences['study_start_hour']) ? trim($preferences['study_start_hour']) : '08:00:00';
            if (strlen($startHour) === 5) {
                $startHour .= ':00';
            }

            $endHour = !empty($preferences['study_end_hour']) ? trim($preferences['study_end_hour']) : '21:00:00';
            if (strlen($endHour) === 5) {
                $endHour .= ':00';
            }

            $maxDaily = isset($preferences['max_daily_study_hours']) ? max(1, min(16, (int) $preferences['max_daily_study_hours'])) : 4;
            $preferredSession = isset($preferences['preferred_session_hours']) ? max(1, min(8, (int) $preferences['preferred_session_hours'])) : 2;

            $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);

            if ($driver === 'sqlite') {
                $sql = "
                    INSERT INTO planning_preferences 
                        (user_id, study_start_hour, study_end_hour, max_daily_study_hours, preferred_session_hours)
                    VALUES 
                        (:user_id, :study_start_hour, :study_end_hour, :max_daily_study_hours, :preferred_session_hours)
                    ON CONFLICT(user_id) DO UPDATE SET 
                        study_start_hour        = excluded.study_start_hour,
                        study_end_hour          = excluded.study_end_hour,
                        max_daily_study_hours   = excluded.max_daily_study_hours,
                        preferred_session_hours = excluded.preferred_session_hours
                ";
            } else {
                $sql = "
                    INSERT INTO planning_preferences 
                        (user_id, study_start_hour, study_end_hour, max_daily_study_hours, preferred_session_hours)
                    VALUES 
                        (:user_id, :study_start_hour, :study_end_hour, :max_daily_study_hours, :preferred_session_hours)
                    ON DUPLICATE KEY UPDATE 
                        study_start_hour        = VALUES(study_start_hour),
                        study_end_hour          = VALUES(study_end_hour),
                        max_daily_study_hours   = VALUES(max_daily_study_hours),
                        preferred_session_hours = VALUES(preferred_session_hours)
                ";
            }

            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':user_id'                 => $userId,
                ':study_start_hour'        => $startHour,
                ':study_end_hour'          => $endHour,
                ':max_daily_study_hours'   => $maxDaily,
                ':preferred_session_hours' => $preferredSession,
            ]);
        } catch (\PDOException $e) {
            error_log("TaskModel::updatePlanningPreferences Error: " . $e->getMessage());
            return false;
        }
    }
}
