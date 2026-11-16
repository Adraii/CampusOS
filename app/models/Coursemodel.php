<?php
require_once __DIR__ . '/../config/database.php';

class CourseModel {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // Mengambil semua mata kuliah berdasarkan user_id
    public function getByUserId($userId) {
        $stmt = $this->db->prepare("SELECT * FROM courses WHERE user_id = :user_id ORDER BY course_name ASC");
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }

    // Menambah mata kuliah baru
    public function create($userId, $data) {
        $stmt = $this->db->prepare("INSERT INTO courses (user_id, course_code, course_name, lecturer, credits, color_hex, created_at) VALUES (:user_id, :course_code, :course_name, :lecturer, :credits, :color_hex, NOW())");
        return $stmt->execute([
            'user_id' => $userId,
            'course_code' => $data['course_code'],
            'course_name' => $data['course_name'],
            'lecturer' => $data['lecturer'],
            'credits' => $data['credits'],
            'color_hex' => $data['color_hex'] ?? '#3b82f6'
        ]);
    }
}