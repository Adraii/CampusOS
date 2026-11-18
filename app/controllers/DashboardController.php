<?php
// app/controllers/DashboardController.php
// Controller Dashboard Utama CampusOS (Bento Grid Hybrid SSR)
// Mengagregasi data lintas modul: ScheduleModel, EventModel, TaskModel, SmartPlannerService, FinanceService

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/view.php';
require_once __DIR__ . '/../models/ScheduleModel.php';
require_once __DIR__ . '/../models/EventModel.php';
require_once __DIR__ . '/../models/TaskModel.php';
require_once __DIR__ . '/../services/SmartPlannerService.php';
require_once __DIR__ . '/../models/FinanceModel.php';
require_once __DIR__ . '/../services/FinanceService.php';

class DashboardController
{
    private ?PDO $db;
    private ScheduleModel $scheduleModel;
    private EventModel $eventModel;
    private TaskModel $taskModel;
    private SmartPlannerService $smartPlannerService;
    private FinanceModel $financeModel;
    private FinanceService $financeService;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? getDBConnection();
        $this->scheduleModel = new ScheduleModel($this->db);
        $this->eventModel = new EventModel($this->db);
        $this->taskModel = new TaskModel($this->db);
        $this->smartPlannerService = new SmartPlannerService($this->db, $this->taskModel);
        $this->financeModel = new FinanceModel($this->db);
        $this->financeService = new FinanceService($this->financeModel, $this->db);
    }

    /**
     * Menampilkan Dashboard Utama Bento Grid
     */
    public function index(): void
    {
        requireLogin();
        $userId = currentUserId();

        // 1. Jadwal Kuliah Hari Ini (PHP date('N'): 1=Senin s/d 7=Minggu)
        $dayOfWeek = (int) date('N');
        $allWeeklySchedules = $this->scheduleModel->getWeeklySchedules($userId) ?: [];
        $todaySchedules = array_values(array_filter($allWeeklySchedules, function ($s) use ($dayOfWeek) {
            return (int) ($s['day_of_week'] ?? 0) === $dayOfWeek;
        }));

        // 2. Agenda Kegiatan Hari Ini
        $todayDate = date('Y-m-d');
        $todayEvents = $this->eventModel->getByDate($userId, $todayDate) ?: [];

        // 3. Statistik Tugas & 5 Tugas Paling Mendesak (Urgent Tasks)
        $urgentTasks = $this->taskModel->getUrgentTasks($userId, 5) ?: [];
        $taskCounts = $this->taskModel->getCountsByStatus($userId);

        // 4. Rekomendasi Jadwal Belajar Smart Planner
        $plannerData = $this->smartPlannerService->generateRecommendations($userId, 7);
        $plannerRecommendations = $plannerData['recommendations'] ?? [];

        // 5. Ringkasan Dompet & Proyeksi Akhir Bulan dari FinanceService
        $currentMonth = date('Y-m');
        $financeSummary = $this->financeService->getMonthlySummary($userId, $currentMonth);
        $walletSummary = $this->financeService->getWalletSummary($userId, $currentMonth);

        // Kontrak Data Lengkap untuk views/dashboard/index.php
        $viewData = [
            'pageTitle'              => 'Dashboard Overview — CampusOS',
            'user'                   => currentUser(),
            'todaySchedules'         => $todaySchedules,
            'todayEvents'            => $todayEvents,
            'urgentTasks'            => $urgentTasks,
            'taskCounts'             => $taskCounts,
            'plannerRecommendations' => $plannerRecommendations,
            'walletSummary'          => $walletSummary,
            'financeSummary'         => $financeSummary,
        ];

        renderView('dashboard/index', $viewData);
    }
}
