<?php
// app/models/CourseModel.php
// Model data mata kuliah mahasiswa
// Mengikuti standar PDO Prepared Statements dan isolasi data WHERE user_id = :user_id

require_once __DIR__ . '/../config/database.php';

class CourseModel
{
    private ?PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getInstance();
    }

    /**
     * Mengambil semua mata kuliah milik pengguna aktif
     *
     * @param int $userId
     * @return array
     */
    public function getByUserId(int $userId): array
    {
        if (!$this->db) {
            return [];
        }

        try {
            $stmt = $this->db->prepare("
                SELECT * FROM courses 
                WHERE user_id = :user_id 
                ORDER BY course_name ASC
            ");
            $stmt->execute([':user_id' => $userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log("CourseModel::getByUserId Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Mengambil satu mata kuliah berdasarkan ID dan user_id
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
                SELECT * FROM courses 
                WHERE id = :id AND user_id = :user_id 
                LIMIT 1
            ");
            $stmt->execute([
                ':id'      => $id,
                ':user_id' => $userId,
            ]);
            $course = $stmt->fetch(PDO::FETCH_ASSOC);
            return $course ?: null;
        } catch (\PDOException $e) {
            error_log("CourseModel::getById Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Menambah mata kuliah baru
     *
     * @param int $userId
     * @param array $data
     * @return int|false ID yang baru dibuat atau false jika gagal
     */
    public function create(int $userId, array $data): int|false
    {
        if (!$this->db) {
            return false;
        }

        try {
            $stmt = $this->db->prepare("
                INSERT INTO courses (
                    user_id, 
                    course_code, 
                    course_name, 
                    lecturer, 
                    credits, 
                    color_hex, 
                    created_at
                ) VALUES (
                    :user_id, 
                    :course_code, 
                    :course_name, 
                    :lecturer, 
                    :credits, 
                    :color_hex, 
                    CURRENT_TIMESTAMP
                )
            ");

            $success = $stmt->execute([
                ':user_id'     => $userId,
                ':course_code' => trim($data['course_code'] ?? ''),
                ':course_name' => trim($data['course_name'] ?? ''),
                ':lecturer'    => !empty($data['lecturer']) ? trim($data['lecturer']) : null,
                ':credits'     => isset($data['credits']) ? (int) $data['credits'] : 3,
                ':color_hex'   => !empty($data['color_hex']) ? trim($data['color_hex']) : '#3B82F6',
            ]);

            return $success ? (int) $this->db->lastInsertId() : false;
        } catch (\PDOException $e) {
            error_log("CourseModel::create Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Memperbarui data mata kuliah
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
            $existing = $this->getById($id, $userId);
            if (!$existing) {
                return false;
            }

            $stmt = $this->db->prepare("
                UPDATE courses SET 
                    course_code = :course_code, 
                    course_name = :course_name, 
                    lecturer    = :lecturer, 
                    credits     = :credits, 
                    color_hex   = :color_hex 
                WHERE id = :id AND user_id = :user_id
            ");

            return $stmt->execute([
                ':id'          => $id,
                ':user_id'     => $userId,
                ':course_code' => trim($data['course_code'] ?? ''),
                ':course_name' => trim($data['course_name'] ?? ''),
                ':lecturer'    => !empty($data['lecturer']) ? trim($data['lecturer']) : null,
                ':credits'     => isset($data['credits']) ? (int) $data['credits'] : 3,
                ':color_hex'   => !empty($data['color_hex']) ? trim($data['color_hex']) : '#3B82F6',
            ]);
        } catch (\PDOException $e) {
            error_log("CourseModel::update Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Menghapus mata kuliah milik user aktif
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

            $stmt = $this->db->prepare("DELETE FROM courses WHERE id = :id AND user_id = :user_id");
            return $stmt->execute([
                ':id'      => $id,
                ':user_id' => $userId,
            ]);
        } catch (\PDOException $e) {
            error_log("CourseModel::delete Error: " . $e->getMessage());
            return false;
        }
    }
}
