<?php
// app/services/ConflictService.php
// Layanan pendeteksi bentrok waktu (Time Overlap Detection)
// Menggunakan rumus matematis deterministik: (start_A < end_B) && (end_A > start_B)
// Memeriksa terhadap class_schedules (jadwal kuliah) dan events (agenda/kegiatan)

require_once __DIR__ . '/../config/database.php';

class ConflictService
{
    private ?PDO $db;

    /**
     * Inisialisasi ConflictService
     *
     * @param PDO|null $db
     */
    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? getDBConnection();
    }

    /**
     * Memeriksa irisan waktu matematis antara dua rentang waktu
     * Rumus baku: (start_A < end_B) && (end_A > start_B)
     *
     * @param string $startA Waktu mulai rentang A (format H:i atau H:i:s)
     * @param string $endA Waktu selesai rentang A (format H:i atau H:i:s)
     * @param string $startB Waktu mulai rentang B (format H:i atau H:i:s)
     * @param string $endB Waktu selesai rentang B (format H:i atau H:i:s)
     * @return bool True jika terdapat irisan waktu (bentrok)
     */
    public static function hasOverlap(string $startA, string $endA, string $startB, string $endB): bool
    {
        $sA = self::normalizeTime($startA);
        $eA = self::normalizeTime($endA);
        $sB = self::normalizeTime($startB);
        $eB = self::normalizeTime($endB);

        return ($sA < $eB) && ($eA > $sB);
    }

    /**
     * Normalisasi format waktu ke standar 'H:i:s'
     *
     * @param string $time
     * @return string
     */
    public static function normalizeTime(string $time): string
    {
        $trimmed = trim($time);
        $parts = explode(':', $trimmed);

        $hours = isset($parts[0]) ? str_pad($parts[0], 2, '0', STR_PAD_LEFT) : '00';
        $minutes = isset($parts[1]) ? str_pad($parts[1], 2, '0', STR_PAD_LEFT) : '00';
        $seconds = isset($parts[2]) ? str_pad($parts[2], 2, '0', STR_PAD_LEFT) : '00';

        return "{$hours}:{$minutes}:{$seconds}";
    }

    /**
     * Mendeteksi bentrok waktu untuk agenda / kegiatan pada tanggal dan rentang jam tertentu
     * Memeriksa irisan terhadap jadwal kuliah tetap (class_schedules) dan agenda lain (events)
     *
     * @param int $userId ID pengguna aktif
     * @param string $date Tanggal target (YYYY-MM-DD)
     * @param string $startTime Waktu mulai target (HH:MM atau HH:MM:SS)
     * @param string $endTime Waktu selesai target (HH:MM atau HH:MM:SS)
     * @param int|null $excludeEventId ID event yang diabaikan (saat edit event sendiri)
     * @return array Hasil evaluasi bentrok dengan rincian
     */
    public function checkEventConflict(
        int $userId,
        string $date,
        string $startTime,
        string $endTime,
        ?int $excludeEventId = null
    ): array {
        $conflicts = [];
        $startTimeNorm = self::normalizeTime($startTime);
        $endTimeNorm = self::normalizeTime($endTime);

        // Validasi urutan waktu: waktu selesai harus lebih besar dari waktu mulai
        if ($startTimeNorm >= $endTimeNorm) {
            return [
                'has_conflict'   => true,
                'conflict_count' => 1,
                'conflicts'      => [
                    [
                        'source'     => 'validation',
                        'title'      => 'Rentang Waktu Tidak Valid',
                        'start_time' => $startTimeNorm,
                        'end_time'   => $endTimeNorm,
                        'message'    => 'Waktu selesai harus lebih besar daripada waktu mulai.',
                    ]
                ],
                'target'         => [
                    'date'        => $date,
                    'start_time'  => $startTimeNorm,
                    'end_time'    => $endTimeNorm,
                ]
            ];
        }

        if (!$this->db) {
            return [
                'has_conflict'   => false,
                'conflict_count' => 0,
                'conflicts'      => [],
                'target'         => [
                    'date'       => $date,
                    'start_time' => $startTimeNorm,
                    'end_time'   => $endTimeNorm,
                ]
            ];
        }

        // Konversi tanggal ke hari dalam seminggu (1 = Senin, ..., 7 = Minggu)
        $timestamp = strtotime($date);
        $dayOfWeek = (int) date('N', $timestamp);

        try {
            // 1. Periksa bentrok terhadap jadwal kuliah tetap (class_schedules)
            $stmtSched = $this->db->prepare("
                SELECT 
                    cs.id,
                    cs.course_id,
                    cs.day_of_week,
                    cs.start_time,
                    cs.end_time,
                    cs.room,
                    c.course_code,
                    c.course_name,
                    c.color_hex,
                    c.lecturer
                FROM class_schedules cs
                JOIN courses c ON cs.course_id = c.id
                WHERE cs.user_id = :user_id AND cs.day_of_week = :day_of_week
                ORDER BY cs.start_time ASC
            ");
            $stmtSched->execute([
                ':user_id'     => $userId,
                ':day_of_week' => $dayOfWeek,
            ]);
            $schedules = $stmtSched->fetchAll(PDO::FETCH_ASSOC) ?: [];

            foreach ($schedules as $sched) {
                $schedStart = self::normalizeTime($sched['start_time']);
                $schedEnd   = self::normalizeTime($sched['end_time']);

                // Rumus baku: (start_A < end_B) && (end_A > start_B)
                if (self::hasOverlap($startTimeNorm, $endTimeNorm, $schedStart, $schedEnd)) {
                    $conflicts[] = [
                        'source'      => 'class_schedule',
                        'id'          => (int) $sched['id'],
                        'title'       => $sched['course_name'],
                        'course_code' => $sched['course_code'],
                        'room'        => $sched['room'] ?? 'Ruang Kuliah',
                        'day_of_week' => (int) $sched['day_of_week'],
                        'start_time'  => substr($schedStart, 0, 5),
                        'end_time'    => substr($schedEnd, 0, 5),
                        'color_hex'   => $sched['color_hex'] ?? '#3B82F6',
                        'message'     => "Bentrok dengan perkuliahan {$sched['course_name']} ({$sched['course_code']}) pukul " . substr($schedStart, 0, 5) . " - " . substr($schedEnd, 0, 5),
                    ];
                }
            }

            // 2. Periksa bentrok terhadap agenda kegiatan lain (events)
            $sqlEvents = "
                SELECT 
                    id,
                    title,
                    category,
                    event_date,
                    start_time,
                    end_time,
                    location,
                    notes
                FROM events
                WHERE user_id = :user_id AND event_date = :event_date
            ";
            $eventParams = [
                ':user_id'    => $userId,
                ':event_date' => $date,
            ];

            if ($excludeEventId !== null && $excludeEventId > 0) {
                $sqlEvents .= " AND id != :exclude_id";
                $eventParams[':exclude_id'] = $excludeEventId;
            }

            $sqlEvents .= " ORDER BY start_time ASC";

            $stmtEvent = $this->db->prepare($sqlEvents);
            $stmtEvent->execute($eventParams);
            $events = $stmtEvent->fetchAll(PDO::FETCH_ASSOC) ?: [];

            foreach ($events as $event) {
                $eventStart = self::normalizeTime($event['start_time']);
                $eventEnd   = self::normalizeTime($event['end_time']);

                // Rumus baku: (start_A < end_B) && (end_A > start_B)
                if (self::hasOverlap($startTimeNorm, $endTimeNorm, $eventStart, $eventEnd)) {
                    $conflicts[] = [
                        'source'      => 'event',
                        'id'          => (int) $event['id'],
                        'title'       => $event['title'],
                        'category'    => $event['category'],
                        'location'    => $event['location'] ?? null,
                        'event_date'  => $event['event_date'],
                        'start_time'  => substr($eventStart, 0, 5),
                        'end_time'    => substr($eventEnd, 0, 5),
                        'message'     => "Bentrok dengan agenda '{$event['title']}' pukul " . substr($eventStart, 0, 5) . " - " . substr($eventEnd, 0, 5),
                    ];
                }
            }
        } catch (\PDOException $e) {
            error_log("ConflictService::checkEventConflict Error: " . $e->getMessage());
        }

        return [
            'has_conflict'   => !empty($conflicts),
            'conflict_count' => count($conflicts),
            'conflicts'      => $conflicts,
            'target'         => [
                'date'        => $date,
                'day_of_week' => $dayOfWeek,
                'start_time'  => $startTimeNorm,
                'end_time'    => $endTimeNorm,
            ]
        ];
    }

    /**
     * Memeriksa bentrok saat menambahkan / mengedit jadwal kuliah mingguan
     *
     * @param int $userId ID pengguna aktif
     * @param int $dayOfWeek Hari (1-7)
     * @param string $startTime
     * @param string $endTime
     * @param int|null $excludeScheduleId
     * @return array
     */
    public function checkClassScheduleConflict(
        int $userId,
        int $dayOfWeek,
        string $startTime,
        string $endTime,
        ?int $excludeScheduleId = null
    ): array {
        $conflicts = [];
        $startTimeNorm = self::normalizeTime($startTime);
        $endTimeNorm = self::normalizeTime($endTime);

        if ($startTimeNorm >= $endTimeNorm) {
            return [
                'has_conflict'   => true,
                'conflict_count' => 1,
                'conflicts'      => [
                    [
                        'source'  => 'validation',
                        'title'   => 'Rentang Waktu Tidak Valid',
                        'message' => 'Waktu selesai perkuliahan harus lebih besar dari waktu mulai.',
                    ]
                ],
            ];
        }

        if (!$this->db) {
            return ['has_conflict' => false, 'conflict_count' => 0, 'conflicts' => []];
        }

        try {
            $sql = "
                SELECT 
                    cs.*, 
                    c.course_name, 
                    c.course_code 
                FROM class_schedules cs
                JOIN courses c ON cs.course_id = c.id
                WHERE cs.user_id = :user_id AND cs.day_of_week = :day_of_week
            ";
            $params = [
                ':user_id'     => $userId,
                ':day_of_week' => $dayOfWeek,
            ];

            if ($excludeScheduleId !== null && $excludeScheduleId > 0) {
                $sql .= " AND cs.id != :exclude_id";
                $params[':exclude_id'] = $excludeScheduleId;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $existing = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            foreach ($existing as $item) {
                $itemStart = self::normalizeTime($item['start_time']);
                $itemEnd   = self::normalizeTime($item['end_time']);

                if (self::hasOverlap($startTimeNorm, $endTimeNorm, $itemStart, $itemEnd)) {
                    $conflicts[] = [
                        'source'      => 'class_schedule',
                        'id'          => (int) $item['id'],
                        'title'       => $item['course_name'],
                        'course_code' => $item['course_code'],
                        'start_time'  => substr($itemStart, 0, 5),
                        'end_time'    => substr($itemEnd, 0, 5),
                        'message'     => "Bentrok dengan mata kuliah {$item['course_name']} ({$item['course_code']}) pukul " . substr($itemStart, 0, 5) . " - " . substr($itemEnd, 0, 5),
                    ];
                }
            }

            // 2. Periksa bentrok terhadap agenda (events) pada hari yang sama
            $stmtEvents = $this->db->prepare("
                SELECT id, title, category, event_date, start_time, end_time, location
                FROM events
                WHERE user_id = :user_id
                ORDER BY event_date ASC, start_time ASC
            ");
            $stmtEvents->execute([':user_id' => $userId]);
            $events = $stmtEvents->fetchAll(PDO::FETCH_ASSOC) ?: [];

            foreach ($events as $event) {
                $evDay = (int) date('N', strtotime($event['event_date']));
                if ($evDay === $dayOfWeek) {
                    $eventStart = self::normalizeTime($event['start_time']);
                    $eventEnd   = self::normalizeTime($event['end_time']);

                    if (self::hasOverlap($startTimeNorm, $endTimeNorm, $eventStart, $eventEnd)) {
                        $conflicts[] = [
                            'source'     => 'event',
                            'id'         => (int) $event['id'],
                            'title'      => $event['title'],
                            'category'   => $event['category'],
                            'event_date' => $event['event_date'],
                            'start_time' => substr($eventStart, 0, 5),
                            'end_time'   => substr($eventEnd, 0, 5),
                            'message'    => "Bentrok dengan agenda '{$event['title']}' pada tanggal {$event['event_date']} pukul " . substr($eventStart, 0, 5) . " - " . substr($eventEnd, 0, 5),
                        ];
                    }
                }
            }
        } catch (\PDOException $e) {
            error_log("ConflictService::checkClassScheduleConflict Error: " . $e->getMessage());
        }

        return [
            'has_conflict'   => !empty($conflicts),
            'conflict_count' => count($conflicts),
            'conflicts'      => $conflicts,
        ];
    }
}
