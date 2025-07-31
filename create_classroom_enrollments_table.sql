-- Create classroom_enrollments table
CREATE TABLE IF NOT EXISTS `classroom_enrollments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `classroom_id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `enrolled_at` datetime NOT NULL,
  `status` enum('active','inactive','dropped') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_enrollment` (`classroom_id`,`student_id`),
  KEY `fk_classroom_enrollments_classroom` (`classroom_id`),
  KEY `fk_classroom_enrollments_student` (`student_id`),
  CONSTRAINT `fk_classroom_enrollments_classroom` FOREIGN KEY (`classroom_id`) REFERENCES `classrooms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_classroom_enrollments_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci; 