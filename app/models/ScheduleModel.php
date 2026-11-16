<?php
require_once __DIR__ . '/../config/database.php';

class ScheduleModel {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // Mengambil jadwal kuliah lengkap dengan informasi nama mata kuliahnya
    public function getWeeklySchedules($userId) {
        $stmt = $this->db->prepare("
            SELECT cs.*, c.course_name, c.course_code, c.color_hex, c.lecturer 
            FROM class_schedules cs
            JOIN courses c ON cs.course_id = c.id
            WHERE cs.user_id = :user_id
            ORDER BY cs.day_of_week ASC, cs.start_time ASC
        ");
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }

    // Menambah jadwal kuliah baru
    public function create($userId, $data) {
        $stmt = $this->db->prepare("INSERT INTO class_schedules (user_id, course_id, day_of_week, start_time, end_time, room) VALUES (:user_id, :course_id, :day_of_week, :start_time, :end_time, :room)");
        return $stmt->execute([
            'user_id' => $userId,
            'course_id' => $data['course_id'],
            'day_of_week' => $data['day_of_week'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'room' => $data['room']
        ]);
    }
}