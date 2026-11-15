-- ========================================================
-- CampusOS — Database Seed Data (Demo Environment)
-- Akun Mahasiswa Demo:
-- Email    : mahasiswa@campusos.test
-- Password : password123
-- ========================================================

SET FOREIGN_KEY_CHECKS = 0;

-- Bersihkan data lama jika ada
TRUNCATE TABLE `transactions`;
TRUNCATE TABLE `tasks`;
TRUNCATE TABLE `events`;
TRUNCATE TABLE `class_schedules`;
TRUNCATE TABLE `courses`;
TRUNCATE TABLE `budget_settings`;
TRUNCATE TABLE `planning_preferences`;
TRUNCATE TABLE `users`;

-- --------------------------------------------------------
-- 1. Seeding Data User Demo
-- Hash bcrypt di bawah ini untuk teks sandi: password123
-- --------------------------------------------------------
INSERT INTO `users` (`id`, `name`, `email`, `password_hash`, `semester`, `created_at`) 
VALUES (
    1, 
    'Demo Mahasiswa', 
    'mahasiswa@campusos.test', 
    '$2y$10$7q5Qe06Z3y0kE2l8fFhHleP3K3jT7/X8xG5oD6dE8jQ8bN4aB9Z1y', 
    3, 
    NOW()
);

-- --------------------------------------------------------
-- 2. Seeding Courses (Mata Kuliah)
-- --------------------------------------------------------
INSERT INTO `courses` (`id`, `user_id`, `course_code`, `course_name`, `lecturer`, `credits`, `color_hex`, `created_at`) VALUES
(1, 1, 'CS201', 'Pemrograman Web', 'Vera Irma Delianti, M.Pd.T.', 3, '#2563EB', NOW()),
(2, 1, 'CS202', 'Struktur Data & Algoritma', 'Siti Rahmawati, S.Kom., M.Cs.', 4, '#7C3AED', NOW()),
(3, 1, 'CS203', 'Sistem Basis Data', 'Ahmad Fauzi, M.Kom.', 3, '#059669', NOW());

-- --------------------------------------------------------
-- 3. Seeding Class Schedules (Jadwal Mingguan Tetap)
-- --------------------------------------------------------
INSERT INTO `class_schedules` (`id`, `user_id`, `course_id`, `day_of_week`, `start_time`, `end_time`, `room`) VALUES
(1, 1, 1, 1, '08:00:00', '10:30:00', 'Lab Komputer 2'),     -- Senin: Pemrograman Web
(2, 1, 2, 3, '13:00:00', '16:20:00', 'Ruang Teori 301'),   -- Rabu: Struktur Data
(3, 1, 3, 4, '10:00:00', '12:30:00', 'Lab Database 1');    -- Kamis: Basis Data

-- --------------------------------------------------------
-- 4. Seeding Events (Agenda Fleksibel / Non-Akademik)
-- --------------------------------------------------------
INSERT INTO `events` (`id`, `user_id`, `title`, `category`, `event_date`, `start_time`, `end_time`, `location`, `notes`, `created_at`) VALUES
(1, 1, 'Rapat Koordinasi BEM Fakultas', 'organization', '2026-11-18', '16:30:00', '18:30:00', 'Gedung Student Center Lt. 2', 'Pembahasan timeline kegiatan Dies Natalis dan rancangan anggaran.', NOW()),
(2, 1, 'Kelompok Belajar Struktur Data', 'study_session', '2026-11-20', '19:30:00', '21:30:00', 'Perpustakaan Pusat / Discord', 'Review implementasi Binary Search Tree dan Graph traversal.', NOW());

-- --------------------------------------------------------
-- 5. Seeding Tasks (Tugas Kuliah & Deadline)
-- --------------------------------------------------------
INSERT INTO `tasks` (`id`, `user_id`, `course_id`, `title`, `description`, `deadline_date`, `deadline_time`, `estimated_hours`, `priority`, `status`, `created_at`) VALUES
(1, 1, 1, 'Proyek Mini Web: MVC Native CampusOS', 'Implementasi routing query, Controller MVC, dan integrasi template UI layout.', '2026-11-22', '23:59:00', 6.0, 'high', 'in_progress', NOW()),
(2, 1, 3, 'Laporan Praktikum Normalisasi 3NF', 'Analisis anomali data relasional dan dokumentasi normalisasi sampai 3NF.', '2026-11-20', '23:59:00', 3.0, 'medium', 'pending', NOW()),
(3, 1, 2, 'Tugas Implementasi AVL Tree C++', 'Implementasi rotasi Left-Right dan Right-Left pada struktur data AVL Tree.', '2026-11-25', '23:59:00', 4.5, 'high', 'pending', NOW()),
(4, 1, NULL, 'Resume Bacaan Etika Profesi IT', 'Meringkas materi UU ITE dan tanggung jawab profesional insinyur software.', '2026-11-28', '23:59:00', 1.5, 'low', 'pending', NOW());

-- --------------------------------------------------------
-- 6. Seeding Budget Settings
-- --------------------------------------------------------
INSERT INTO `budget_settings` (`id`, `user_id`, `monthly_budget`, `period_start_day`, `updated_at`) 
VALUES (1, 1, 1500000.00, 1, NOW());

-- --------------------------------------------------------
-- 7. Seeding Planning Preferences (Smart Planner Settings)
-- --------------------------------------------------------
INSERT INTO `planning_preferences` (`id`, `user_id`, `study_start_hour`, `study_end_hour`, `max_daily_study_hours`, `preferred_session_hours`, `updated_at`) 
VALUES (1, 1, '08:00:00', '21:00:00', 4, 2, NOW());

-- --------------------------------------------------------
-- 8. Seeding Transactions (Arus Kas Demo)
-- --------------------------------------------------------
INSERT INTO `transactions` (`id`, `user_id`, `type`, `category`, `amount`, `transaction_date`, `description`, `created_at`) VALUES
(1, 1, 'income', 'Uang Saku', 1500000.00, '2026-11-01', 'Transfer uang saku bulanan dari orang tua', NOW()),
(2, 1, 'expense', 'Konsumsi', 25000.00, '2026-11-02', 'Makan siang kantin kampus nasi rames & es teh', NOW()),
(3, 1, 'expense', 'Pendidikan', 85000.00, '2026-11-05', 'Buku referensi Algoritma Pemrograman di Gramedia', NOW()),
(4, 1, 'expense', 'Pendidikan', 15000.00, '2026-11-08', 'Print dan jilid laporan pendahuluan praktikum', NOW());

SET FOREIGN_KEY_CHECKS = 1;

