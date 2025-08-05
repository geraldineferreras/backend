-- Create class_tasks table
CREATE TABLE IF NOT EXISTS `class_tasks` (
  `task_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `type` enum('assignment','quiz','activity','project','exam') NOT NULL DEFAULT 'assignment',
  `points` int(11) NOT NULL DEFAULT 0,
  `instructions` text,
  `attachment_type` enum('file','link','youtube','google_drive') DEFAULT NULL,
  `attachment_url` text DEFAULT NULL,
  `allow_comments` tinyint(1) NOT NULL DEFAULT 1,
  `is_draft` tinyint(1) NOT NULL DEFAULT 0,
  `is_scheduled` tinyint(1) NOT NULL DEFAULT 0,
  `scheduled_at` datetime DEFAULT NULL,
  `teacher_id` varchar(50) NOT NULL,
  `class_codes` json NOT NULL COMMENT 'Array of class codes where task is posted',
  `status` enum('active','inactive','deleted') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`task_id`),
  KEY `idx_teacher_id` (`teacher_id`),
  KEY `idx_type` (`type`),
  KEY `idx_status` (`status`),
  KEY `idx_is_draft` (`is_draft`),
  KEY `idx_is_scheduled` (`is_scheduled`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create task_comments table for student comments on tasks
CREATE TABLE IF NOT EXISTS `task_comments` (
  `comment_id` int(11) NOT NULL AUTO_INCREMENT,
  `task_id` int(11) NOT NULL,
  `user_id` varchar(50) NOT NULL,
  `comment` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`comment_id`),
  KEY `idx_task_id` (`task_id`),
  KEY `idx_user_id` (`user_id`),
  CONSTRAINT `fk_task_comments_task` FOREIGN KEY (`task_id`) REFERENCES `class_tasks` (`task_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create task_submissions table for student submissions
CREATE TABLE IF NOT EXISTS `task_submissions` (
  `submission_id` int(11) NOT NULL AUTO_INCREMENT,
  `task_id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `class_code` varchar(20) NOT NULL,
  `submission_content` text,
  `attachment_type` enum('file','link','youtube','google_drive') DEFAULT NULL,
  `attachment_url` text DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `grade` decimal(5,2) DEFAULT NULL,
  `feedback` text DEFAULT NULL,
  `status` enum('submitted','graded','late') NOT NULL DEFAULT 'submitted',
  PRIMARY KEY (`submission_id`),
  UNIQUE KEY `unique_task_student_class` (`task_id`, `student_id`, `class_code`),
  KEY `idx_task_id` (`task_id`),
  KEY `idx_student_id` (`student_id`),
  KEY `idx_class_code` (`class_code`),
  CONSTRAINT `fk_task_submissions_task` FOREIGN KEY (`task_id`) REFERENCES `class_tasks` (`task_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci; 