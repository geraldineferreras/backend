-- Create classroom_enrollments table (without foreign key constraints for now)
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
  KEY `idx_classroom_id` (`classroom_id`),
  KEY `idx_student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci; 