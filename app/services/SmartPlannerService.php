<?php
// app/services/SmartPlannerService.php
// Mesin Rekomendasi Slot Belajar Mandiri Cerdas (Smart Planner)
// Bekerja secara deterministik (Rule-Based Engine) murni PHP tanpa API eksternal
// Menghitung skor urgensi tugas, memetakan celah waktu luang (free time gaps),
// dan menyajikan alasan rekomendasi yang transparan (Hard Guardrails #5 & #6)

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/TaskModel.php';
require_once __DIR__ . '/ConflictService.php';

class SmartPlannerService
{
    private ?PDO $db;
    private TaskModel $taskModel;

    /**
     * Inisialisasi SmartPlannerService
     *
     * @param PDO|null $db
     * @param TaskModel|null $taskModel
     */
    public function __construct(?PDO $db = null, ?TaskModel $taskModel = null)
    {
        $this->db = $db ?? getDBConnection();
        $this->taskModel = $taskModel ?? new TaskModel($this->db);
    }

    /**
     * Menghitung Skor Urgensi Tugas (U) secara deterministik
     * 
     * Rumus: U = (estimated_hours / max(T_remaining, 1)) * W_p * 100
     * Di mana:
     * - T_remaining: sisa waktu hingga deadline dalam jam
     * - W_p: bobot pengali prioritas (high=1.5, medium=1.2, low=1.0)
     *
     * @param array $task
     * @param int|null $currentTimestamp
     * @return float
     */
    public function calculateUrgencyScore(array $task, ?int $currentTimestamp = null): float
    {
        $now = $currentTimestamp ?? time();

        $deadlineDate = $task['deadline_date'] ?? date('Y-m-d');
        $deadlineTime = $task['deadline_time'] ?? '23:59:00';
        $deadlineTs = strtotime("{$deadlineDate} {$deadlineTime}");

        // Hitung sisa jam menuju deadline
        $remainingSeconds = $deadlineTs - $now;
        $remainingHours = $remainingSeconds / 3600.0;

        // Jika deadline sudah lewat atau sisa waktu < 1 jam, gunakan nilai minimum 1.0 jam
        $effectiveRemaining = max(1.0, $remainingHours);

        // Bobot pengali prioritas (W_p)
        $priority = strtolower($task['priority'] ?? 'medium');
        $weight = match ($priority) {
            'high'   => 1.5,
            'medium' => 1.2,
            'low'    => 1.0,
            default  => 1.0,
        };

        // Estimasi durasi pengerjaan dalam jam
        $estimatedHours = max(0.5, (float) ($task['estimated_hours'] ?? 2.0));

        // Perhitungan Skor Urgensi U
        $score = ($estimatedHours / $effectiveRemaining) * $weight * 100.0;

        // Jika sudah overdue (lewat deadline), berikan penalti urgensi ekstra (+100)
        if ($remainingHours <= 0) {
            $score += 100.0;
        }

        return round($score, 2);
    }

    /**
     * Mengambil dan mengurutkan tugas-tugas aktif berdasarkan Skor Urgensi tertinggi
     *
     * @param int $userId
     * @param int|null $currentTimestamp
     * @return array
     */
    public function getRankedTasks(int $userId, ?int $currentTimestamp = null): array
    {
        $now = $currentTimestamp ?? time();
        $tasks = $this->taskModel->getPendingTasks($userId);

        if (empty($tasks)) {
            return [];
        }

        $ranked = [];
        foreach ($tasks as $task) {
            $deadlineDate = $task['deadline_date'] ?? date('Y-m-d');
            $deadlineTime = $task['deadline_time'] ?? '23:59:00';
            $deadlineTs = strtotime("{$deadlineDate} {$deadlineTime}");
            $remainingHours = ($deadlineTs - $now) / 3600.0;

            $score = $this->calculateUrgencyScore($task, $now);

            $task['urgency_score']   = $score;
            $task['remaining_hours'] = round($remainingHours, 1);
            $task['is_overdue']      = ($remainingHours <= 0);
            $task['course_name']     = !empty($task['course_name']) ? $task['course_name'] : 'Umum';

            $ranked[] = $task;
        }

        // Urutkan secara menurun (descending) berdasarkan skor urgensi
        usort($ranked, function ($a, $b) {
            if ($b['urgency_score'] == $a['urgency_score']) {
                // Jika skor sama, dahulukan yang estimasi durasinya lebih singkat
                return (float) ($a['estimated_hours'] ?? 0) <=> (float) ($b['estimated_hours'] ?? 0);
            }
            return ($b['urgency_score'] > $a['urgency_score']) ? 1 : -1;
        });

        return $ranked;
    }

    /**
     * Mencari celah waktu luang (free time gaps) pada hari tertentu
     * Dibatasi oleh jam preferensi belajar (planning_preferences) serta
     * jadwal kuliah tetap (class_schedules) dan agenda (events)
     *
     * @param int $userId
     * @param string $date Format YYYY-MM-DD
     * @param array $preferences
     * @param int|null $currentTimestamp
     * @return array Daftar slot kosong: [['start' => 'HH:MM:SS', 'end' => 'HH:MM:SS', 'duration_hours' => float], ...]
     */
    public function findFreeSlotsForDay(
        int $userId,
        string $date,
        array $preferences,
        ?int $currentTimestamp = null
    ): array {
        if (!$this->db) {
            return [];
        }

        $now = $currentTimestamp ?? time();
        $isToday = ($date === date('Y-m-d', $now));

        $studyStart = ConflictService::normalizeTime($preferences['study_start_hour'] ?? '08:00:00');
        $studyEnd   = ConflictService::normalizeTime($preferences['study_end_hour'] ?? '21:00:00');

        // Jika hari ini, jangan jadwalkan waktu yang sudah lewat
        if ($isToday) {
            $currentHourMin = date('H:i:s', $now);
            // Bulatkan ke atas ke kelipatan 15 menit berikutnya
            $currentTs = strtotime($currentHourMin);
            $roundedTs = ceil($currentTs / (15 * 60)) * (15 * 60);
            $roundedTime = date('H:i:s', $roundedTs);

            if ($roundedTime > $studyStart) {
                $studyStart = $roundedTime;
            }
        }

        // Jika batas mulai sudah melewati batas selesai, tidak ada slot tersedia
        if ($studyStart >= $studyEnd) {
            return [];
        }

        $dayOfWeek = (int) date('N', strtotime($date));

        // 1. Ambil seluruh komitmen jadwal kuliah pada hari tersebut
        $commitments = [];
        try {
            $stmtSched = $this->db->prepare("
                SELECT start_time, end_time, 'Kuliah' as title
                FROM class_schedules
                WHERE user_id = :user_id AND day_of_week = :day_of_week
            ");
            $stmtSched->execute([
                ':user_id'     => $userId,
                ':day_of_week' => $dayOfWeek,
            ]);
            $schedules = $stmtSched->fetchAll(PDO::FETCH_ASSOC) ?: [];
            foreach ($schedules as $s) {
                $commitments[] = [
                    'start' => ConflictService::normalizeTime($s['start_time']),
                    'end'   => ConflictService::normalizeTime($s['end_time']),
                    'title' => $s['title'],
                ];
            }

            // 2. Ambil seluruh komitmen agenda/event pada tanggal tersebut
            $stmtEvent = $this->db->prepare("
                SELECT start_time, end_time, title
                FROM events
                WHERE user_id = :user_id AND event_date = :event_date
            ");
            $stmtEvent->execute([
                ':user_id'    => $userId,
                ':event_date' => $date,
            ]);
            $events = $stmtEvent->fetchAll(PDO::FETCH_ASSOC) ?: [];
            foreach ($events as $e) {
                $commitments[] = [
                    'start' => ConflictService::normalizeTime($e['start_time']),
                    'end'   => ConflictService::normalizeTime($e['end_time']),
                    'title' => $e['title'],
                ];
            }
        } catch (\PDOException $e) {
            error_log("SmartPlannerService::findFreeSlotsForDay Error: " . $e->getMessage());
        }

        // 3. Potong & saring komitmen hanya yang bersinggungan dengan rentang belajar preferensi
        $activeBlocks = [];
        foreach ($commitments as $c) {
            $tStart = max($studyStart, $c['start']);
            $tEnd   = min($studyEnd, $c['end']);

            if ($tStart < $tEnd) {
                $activeBlocks[] = ['start' => $tStart, 'end' => $tEnd];
            }
        }

        // 4. Urutkan komitmen berdasarkan waktu mulai
        usort($activeBlocks, function ($a, $b) {
            return strcmp($a['start'], $b['start']);
        });

        // 5. Gabungkan blok komitmen yang saling menimpa atau bersambung (Merge overlapping)
        $mergedBlocks = [];
        foreach ($activeBlocks as $block) {
            if (empty($mergedBlocks)) {
                $mergedBlocks[] = $block;
                continue;
            }

            $lastIdx = count($mergedBlocks) - 1;
            if ($block['start'] <= $mergedBlocks[$lastIdx]['end']) {
                // Tumpuk rentang selesai jika lebih panjang
                if ($block['end'] > $mergedBlocks[$lastIdx]['end']) {
                    $mergedBlocks[$lastIdx]['end'] = $block['end'];
                }
            } else {
                $mergedBlocks[] = $block;
            }
        }

        // 6. Buat celah kosong (inversi dari komitmen terikat)
        $freeSlots = [];
        $cursor = $studyStart;

        foreach ($mergedBlocks as $block) {
            if ($block['start'] > $cursor) {
                $durationSec = strtotime($block['start']) - strtotime($cursor);
                $durationHours = $durationSec / 3600.0;
                // Hanya pertimbangkan slot bebas minimal 30 menit (0.5 jam)
                if ($durationHours >= 0.5) {
                    $freeSlots[] = [
                        'start'          => $cursor,
                        'end'            => $block['start'],
                        'duration_hours' => round($durationHours, 2),
                    ];
                }
            }
            if ($block['end'] > $cursor) {
                $cursor = $block['end'];
            }
        }

        // Slot terakhir dari blok komitmen terakhir hingga akhir jam belajar
        if ($cursor < $studyEnd) {
            $durationSec = strtotime($studyEnd) - strtotime($cursor);
            $durationHours = $durationSec / 3600.0;
            if ($durationHours >= 0.5) {
                $freeSlots[] = [
                    'start'          => $cursor,
                    'end'            => $studyEnd,
                    'duration_hours' => round($durationHours, 2),
                ];
            }
        }

        return $freeSlots;
    }

    /**
     * Mesin Utama: Menghasilkan Rekomendasi Jadwal Belajar Cerdas (Deterministik)
     * Tidak pernah menimpa jadwal kuliah tetap (Hard Guardrail #5)
     *
     * @param int $userId ID pengguna aktif
     * @param int $daysAhead Jumlah hari ke depan yang dievaluasi (default: 7 hari)
     * @param int|null $currentTimestamp Timestamp titik mulai (opsional untuk testing)
     * @return array
     */
    public function generateRecommendations(
        int $userId,
        int $daysAhead = 7,
        ?int $currentTimestamp = null
    ): array {
        $now = $currentTimestamp ?? time();

        // 1. Ambil preferensi jam belajar user
        $preferences = $this->taskModel->getPlanningPreferences($userId);
        $maxDailyHours    = (float) ($preferences['max_daily_study_hours'] ?? 4);
        $preferredSession = (float) ($preferences['preferred_session_hours'] ?? 2);

        // 2. Ambil tugas-tugas aktif terurut berdasarkan Skor Urgensi
        $rankedTasks = $this->getRankedTasks($userId, $now);

        if (empty($rankedTasks)) {
            return [
                'success'               => true,
                'recommendations'       => [],
                'tasks_evaluated_count' => 0,
                'ranked_tasks'          => [],
                'preferences'           => $preferences,
                'generated_at'          => date('Y-m-d H:i:s', $now),
                'message'               => 'Semua tugas telah selesai atau belum ada tugas aktif yang didaftarkan. Tetap pertahankan prestasimu!',
            ];
        }

        // Clone struktur tugas untuk mencatat sisa durasi pengerjaan yang belum dialokasikan
        $taskPool = [];
        foreach ($rankedTasks as $task) {
            $taskPool[] = [
                'id'              => (int) $task['id'],
                'title'           => $task['title'],
                'course_name'     => $task['course_name'] ?? 'Mata Kuliah Umum',
                'course_code'     => $task['course_code'] ?? null,
                'color_hex'       => $task['color_hex'] ?? '#3B82F6',
                'priority'        => $task['priority'],
                'urgency_score'   => $task['urgency_score'],
                'deadline_date'   => $task['deadline_date'],
                'deadline_time'   => $task['deadline_time'] ?? '23:59:00',
                'remaining_hours' => $task['remaining_hours'],
                'needed_hours'    => max(0.5, (float) ($task['estimated_hours'] ?? 2.0)),
                'is_overdue'      => $task['is_overdue'],
            ];
        }

        $recommendations = [];
        $dayNamesIndo = [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
        ];

        // 3. Iterasi hari demi hari untuk mencocokkan celah waktu luang
        for ($dayOffset = 0; $dayOffset < $daysAhead; $dayOffset++) {
            $dayTs = $now + ($dayOffset * 86400);
            $evalDate = date('Y-m-d', $dayTs);
            $dayOfWeek = (int) date('N', $dayTs);
            $dayName = $dayNamesIndo[$dayOfWeek] ?? 'Hari';

            // Dapatkan celah waktu luang pada hari ini
            $freeSlots = $this->findFreeSlotsForDay($userId, $evalDate, $preferences, $now);
            if (empty($freeSlots)) {
                continue;
            }

            $dailyAllocatedHours = 0.0;

            foreach ($freeSlots as $slot) {
                $slotStart = $slot['start'];
                $slotEnd   = $slot['end'];

                while (true) {
                    // Cek kuota jam belajar harian
                    $remainingDailyQuota = $maxDailyHours - $dailyAllocatedHours;
                    if ($remainingDailyQuota <= 0.25) {
                        break 2; // Pindah ke hari berikutnya karena kuota harian terpenuhi
                    }

                    // Hitung durasi slot yang masih tersisa
                    $slotDuration = (strtotime($slotEnd) - strtotime($slotStart)) / 3600.0;
                    if ($slotDuration < 0.45) { // Kurang dari ~30 menit
                        break;
                    }

                    // Cari tugas aktif berikutnya yang masih butuh alokasi jam belajar
                    $activeTaskIndex = null;
                    foreach ($taskPool as $idx => $tp) {
                        if ($tp['needed_hours'] > 0.05) {
                            $activeTaskIndex = $idx;
                            break;
                        }
                    }

                    // Jika seluruh tugas sudah teralokasikan tuntas
                    if ($activeTaskIndex === null) {
                        break 3;
                    }

                    $currentTask = &$taskPool[$activeTaskIndex];

                    // Tentukan durasi sesi: min(sisa slot, sisa butuh tugas, preferred_session, sisa kuota harian)
                    $sessionHours = min(
                        $slotDuration,
                        $currentTask['needed_hours'],
                        $preferredSession,
                        $remainingDailyQuota
                    );

                    // Pastikan minimal sesi 30 menit
                    if ($sessionHours < 0.45 && $currentTask['needed_hours'] > 0.45) {
                        break;
                    }

                    $sessionSec = (int) round($sessionHours * 3600);
                    $sessionEndTs = strtotime($slotStart) + $sessionSec;
                    $sessionEnd = date('H:i:s', $sessionEndTs);

                    // Format tampilan waktu
                    $startDisplay = substr($slotStart, 0, 5);
                    $endDisplay   = substr($sessionEnd, 0, 5);
                    $prefStartDisplay = substr($preferences['study_start_hour'], 0, 5);
                    $prefEndDisplay   = substr($preferences['study_end_hour'], 0, 5);

                    // Bentuk teks alasan transparan (Transparent Reason)
                    $priorityLabel = ucfirst($currentTask['priority']);
                    $deadlineHuman = date('d M Y', strtotime($currentTask['deadline_date'])) . ' ' . substr($currentTask['deadline_time'], 0, 5) . ' WIB';
                    $remainingText = $currentTask['is_overdue']
                        ? "telah melewati deadline"
                        : "tersisa {$currentTask['remaining_hours']} jam lagi";

                    $reason = sprintf(
                        "Tugas '%s' (%s) diprioritaskan utama dengan Skor Urgensi %.1f karena deadline %s (%s) dan estimasi pengerjaan %.1fj (Prioritas: %s). Slot %s pukul %s - %s dipilih secara deterministik karena tidak ada bentrok kuliah/agenda tetap dan sesuai preferensi jam belajarmu (%s - %s).",
                        $currentTask['title'],
                        $currentTask['course_name'],
                        $currentTask['urgency_score'],
                        $remainingText,
                        $deadlineHuman,
                        $currentTask['needed_hours'],
                        $priorityLabel,
                        $dayName,
                        $startDisplay,
                        $endDisplay,
                        $prefStartDisplay,
                        $prefEndDisplay
                    );

                    // Masukkan ke array rekomendasi
                    $recommendations[] = [
                        'task_id'                => $currentTask['id'],
                        'task_title'             => $currentTask['title'],
                        'course_name'            => $currentTask['course_name'],
                        'course_code'            => $currentTask['course_code'],
                        'color_hex'              => $currentTask['color_hex'],
                        'priority'               => $currentTask['priority'],
                        'urgency_score'          => $currentTask['urgency_score'],
                        'date'                   => $evalDate,
                        'day_name'               => $dayName,
                        'start_time'             => $startDisplay,
                        'end_time'               => $endDisplay,
                        'session_duration_hours' => round($sessionHours, 2),
                        'remaining_task_hours'   => round(max(0, $currentTask['needed_hours'] - $sessionHours), 1),
                        'reason'                 => $reason,
                    ];

                    // Perbarui sisa jam tugas dan kuota
                    $currentTask['needed_hours'] -= $sessionHours;
                    $dailyAllocatedHours += $sessionHours;

                    // Geser waktu mulai slot
                    $slotStart = $sessionEnd;
                }
            }
        }

        return [
            'success'               => true,
            'recommendations'       => $recommendations,
            'tasks_evaluated_count' => count($rankedTasks),
            'ranked_tasks'          => $rankedTasks,
            'preferences'           => $preferences,
            'generated_at'          => date('Y-m-d H:i:s', $now),
            'message'               => count($recommendations) > 0 
                ? "Ditemukan " . count($recommendations) . " rekomendasi sesi belajar terstruktur untuk " . count($rankedTasks) . " tugas aktif."
                : "Tidak ditemukan slot waktu luang yang cukup dalam 7 hari ke depan dengan preferensi saat ini.",
        ];
    }
}
