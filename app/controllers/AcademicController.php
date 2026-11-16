<?php
require_once __DIR__ . '/../models/CourseModel.php';
require_once __DIR__ . '/../models/ScheduleModel.php';

class AcademicController {
    private $courseModel;
    private $scheduleModel;

    public function __construct() {
        $this->courseModel = new CourseModel();
        $this->scheduleModel = new ScheduleModel();
    }

    // Menampilkan halaman utama Akademik & Jadwal
    public function index() {
        $userId = $_SESSION['user_id'];
        
        // Mengambil data mata kuliah dan jadwal mingguan untuk user yang sedang login
        $courses = $this->courseModel->getByUserId($userId);
        $weeklySchedules = $this->scheduleModel->getWeeklySchedules($userId);

        // Memuat file tampilan frontend yang sudah disiapkan rekanmu di folder views/
        require_once __DIR__ . '/../../views/academic/index.php';
    }
}