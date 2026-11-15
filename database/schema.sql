-- ========================================================
-- CampusOS — Database Schema DDL
-- Web-based Student Assistant
-- Engine: InnoDB | Charset: utf8mb4 | Collation: utf8mb4_unicode_ci
-- ========================================================

SET FOREIGN_KEY_CHECKS = 0;

-- --------------------------------------------------------
-- 1. Table: users
-- Menyimpan identitas akun mahasiswa
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(120) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `semester` TINYINT NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 2. Table: courses
-- Menyimpan daftar mata kuliah per semester
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `courses` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `course_code` VARCHAR(20) NOT NULL,
    `course_name` VARCHAR(120) NOT NULL,
    `lecturer` VARCHAR(100) NULL,
    `credits` TINYINT NOT NULL DEFAULT 3,
    `color_hex` VARCHAR(7) NOT NULL DEFAULT '#3B82F6',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_courses_user` FOREIGN KEY (`user_id`) 
        REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 3. Table: class_schedules
-- Menyimpan jadwal mingguan tetap untuk setiap mata kuliah
-- day_of_week: 1 = Senin, 2 = Selasa, ..., 7 = Minggu
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `class_schedules` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `course_id` INT NOT NULL,
    `day_of_week` TINYINT NOT NULL COMMENT '1=Senin, 2=Selasa, ..., 7=Minggu',
    `start_time` TIME NOT NULL,
    `end_time` TIME NOT NULL,
    `room` VARCHAR(50) NULL,
    INDEX `idx_schedules_user_day` (`user_id`, `day_of_week`),
    CONSTRAINT `fk_schedules_user` FOREIGN KEY (`user_id`) 
        REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_schedules_course` FOREIGN KEY (`course_id`) 
        REFERENCES `courses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 4. Table: events
-- Menyimpan agenda fleksibel / non-akademik pada tanggal tertentu
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `events` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `title` VARCHAR(150) NOT NULL,
    `category` ENUM('personal', 'organization', 'study_session', 'exam') NOT NULL,
    `event_date` DATE NOT NULL,
    `start_time` TIME NOT NULL,
    `end_time` TIME NOT NULL,
    `location` VARCHAR(100) NULL,
    `notes` TEXT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_events_user_date` (`user_id`, `event_date`),
    CONSTRAINT `fk_events_user` FOREIGN KEY (`user_id`) 
        REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 5. Table: tasks
-- Menyimpan daftar tugas kuliah dengan prioritas & deadline
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tasks` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `course_id` INT NULL,
    `title` VARCHAR(150) NOT NULL,
    `description` TEXT NULL,
    `deadline_date` DATE NOT NULL,
    `deadline_time` TIME NOT NULL DEFAULT '23:59:00',
    `estimated_hours` DECIMAL(4,1) NOT NULL DEFAULT 2.0,
    `priority` ENUM('low', 'medium', 'high') NOT NULL,
    `status` ENUM('pending', 'in_progress', 'done') NOT NULL DEFAULT 'pending',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_tasks_user_status_deadline` (`user_id`, `status`, `deadline_date`),
    CONSTRAINT `fk_tasks_user` FOREIGN KEY (`user_id`) 
        REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_tasks_course` FOREIGN KEY (`course_id`) 
        REFERENCES `courses` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 6. Table: transactions
-- Menyimpan catatan arus kas keuangan mahasiswa
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `transactions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `type` ENUM('income', 'expense') NOT NULL,
    `category` VARCHAR(50) NOT NULL,
    `amount` DECIMAL(12,2) NOT NULL,
    `transaction_date` DATE NOT NULL,
    `description` VARCHAR(200) NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_transactions_user_date` (`user_id`, `transaction_date`),
    CONSTRAINT `fk_transactions_user` FOREIGN KEY (`user_id`) 
        REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 7. Table: budget_settings
-- Pengaturan limit anggaran periodik per pengguna
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `budget_settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL UNIQUE,
    `monthly_budget` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `period_start_day` TINYINT NOT NULL DEFAULT 1,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_budget_user` FOREIGN KEY (`user_id`) 
        REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 8. Table: planning_preferences
-- Preferensi jam belajar untuk perhitungan Smart Planner
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `planning_preferences` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL UNIQUE,
    `study_start_hour` TIME NOT NULL DEFAULT '08:00:00',
    `study_end_hour` TIME NOT NULL DEFAULT '21:00:00',
    `max_daily_study_hours` TINYINT NOT NULL DEFAULT 4,
    `preferred_session_hours` TINYINT NOT NULL DEFAULT 2,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_planning_user` FOREIGN KEY (`user_id`) 
        REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
