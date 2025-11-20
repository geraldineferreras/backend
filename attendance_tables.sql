-- Attendance Management System Database Tables
-- This script creates all necessary tables for the attendance management system

-- Create attendance table
CREATE TABLE IF NOT EXISTS `attendance` (
  `attendance_id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` varchar(50) NOT NULL,
  `class_id` varchar(50) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `section_name` varchar(100) NOT NULL,
  `date` date NOT NULL,
  `time_in` time DEFAULT NULL,
  `status` enum('present','late','absent','excused') NOT NULL DEFAULT 'present',
  `notes` text DEFAULT NULL,
  `teacher_id` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`attendance_id`),
  UNIQUE KEY `unique_attendance` (`student_id`,`class_id`,`date`),
  KEY `idx_student_id` (`student_id`),
  KEY `idx_class_id` (`class_id`),
  KEY `idx_date` (`date`),
  KEY `idx_teacher_id` (`teacher_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create excuse_letters table if it doesn't exist
CREATE TABLE IF NOT EXISTS `excuse_letters` (
  `letter_id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` varchar(50) NOT NULL,
  `class_id` varchar(50) NOT NULL,
  `teacher_id` varchar(50) NOT NULL,
  `date_absent` date NOT NULL,
  `reason` text NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `teacher_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`letter_id`),
  UNIQUE KEY `unique_excuse` (`student_id`,`class_id`,`date_absent`),
  KEY `idx_student_id` (`student_id`),
  KEY `idx_class_id` (`class_id`),
  KEY `idx_teacher_id` (`teacher_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create classes table if it doesn't exist
CREATE TABLE IF NOT EXISTS `classes` (
  `class_id` varchar(50) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `section_id` int(11) NOT NULL,
  `teacher_id` varchar(50) NOT NULL,
  `schedule` varchar(100) DEFAULT NULL,
  `room` varchar(50) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`class_id`),
  KEY `idx_subject_id` (`subject_id`),
  KEY `idx_section_id` (`section_id`),
  KEY `idx_teacher_id` (`teacher_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create subjects table if it doesn't exist
CREATE TABLE IF NOT EXISTS `subjects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `subject_name` varchar(100) NOT NULL,
  `subject_code` varchar(20) NOT NULL,
  `description` text DEFAULT NULL,
  `credits` int(11) DEFAULT 1,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_subject_code` (`subject_code`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create sections table if it doesn't exist
CREATE TABLE IF NOT EXISTS `sections` (
  `section_id` int(11) NOT NULL AUTO_INCREMENT,
  `section_name` varchar(50) NOT NULL,
  `grade_level` varchar(20) DEFAULT NULL,
  `capacity` int(11) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`section_id`),
  UNIQUE KEY `unique_section_name` (`section_name`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create users table if it doesn't exist
CREATE TABLE IF NOT EXISTS `users` (
  `user_id` varchar(50) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` enum('admin','teacher','student') NOT NULL,
  `student_num` varchar(20) DEFAULT NULL,
  `section_id` int(11) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `unique_email` (`email`),
  UNIQUE KEY `unique_student_num` (`student_num`),
  KEY `idx_role` (`role`),
  KEY `idx_section_id` (`section_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample data for testing (optional)
-- Insert sample subjects
INSERT IGNORE INTO `subjects` (`subject_name`, `subject_code`, `description`, `credits`) VALUES
('Mathematics', 'MATH101', 'Basic Mathematics', 3),
('English', 'ENG101', 'English Language', 3),
('Science', 'SCI101', 'General Science', 3),
('History', 'HIST101', 'World History', 3),
('Computer Science', 'CS101', 'Introduction to Programming', 3);

-- Insert sample sections
INSERT IGNORE INTO `sections` (`section_name`, `grade_level`, `capacity`) VALUES
('Section A', 'Grade 10', 30),
('Section B', 'Grade 10', 30),
('Section C', 'Grade 11', 30),
('Section D', 'Grade 11', 30),
('Section E', 'Grade 12', 30);

-- Insert sample users (admin, teachers, students)
INSERT IGNORE INTO `users` (`user_id`, `full_name`, `email`, `password`, `role`, `student_num`, `section_id`) VALUES
('ADMIN001', 'System Administrator', 'admin@school.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', NULL, NULL),
('TCH001', 'John Smith', 'john.smith@school.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', NULL, NULL),
('TCH002', 'Jane Doe', 'jane.doe@school.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', NULL, NULL),
('STU001', 'Alice Johnson', 'alice.johnson@school.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', '2024001', 1),
('STU002', 'Bob Wilson', 'bob.wilson@school.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', '2024002', 1),
('STU003', 'Carol Brown', 'carol.brown@school.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', '2024003', 2);

-- Insert sample classes
INSERT IGNORE INTO `classes` (`class_id`, `subject_id`, `section_id`, `teacher_id`, `schedule`, `room`) VALUES
('CLASS001', 1, 1, 'TCH001', 'Monday 8:00-9:00', 'Room 101'),
('CLASS002', 2, 1, 'TCH002', 'Tuesday 9:00-10:00', 'Room 102'),
('CLASS003', 3, 2, 'TCH001', 'Wednesday 10:00-11:00', 'Room 103'),
('CLASS004', 4, 2, 'TCH002', 'Thursday 11:00-12:00', 'Room 104'),
('CLASS005', 5, 1, 'TCH001', 'Friday 1:00-2:00', 'Computer Lab');

-- Add foreign key constraints if they don't exist
-- Note: These are commented out to avoid errors if tables don't exist
-- You can uncomment these after ensuring all tables exist

/*
ALTER TABLE `attendance` 
ADD CONSTRAINT `fk_attendance_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
ADD CONSTRAINT `fk_attendance_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`) ON DELETE CASCADE,
ADD CONSTRAINT `fk_attendance_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
ADD CONSTRAINT `fk_attendance_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

ALTER TABLE `excuse_letters` 
ADD CONSTRAINT `fk_excuse_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
ADD CONSTRAINT `fk_excuse_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`) ON DELETE CASCADE,
ADD CONSTRAINT `fk_excuse_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

ALTER TABLE `classes` 
ADD CONSTRAINT `fk_class_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
ADD CONSTRAINT `fk_class_section` FOREIGN KEY (`section_id`) REFERENCES `sections` (`section_id`) ON DELETE CASCADE,
ADD CONSTRAINT `fk_class_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

ALTER TABLE `users` 
ADD CONSTRAINT `fk_user_section` FOREIGN KEY (`section_id`) REFERENCES `sections` (`section_id`) ON DELETE SET NULL;
*/ 