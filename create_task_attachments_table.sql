-- Create task_attachments table for multiple file support in teacher-created tasks
CREATE TABLE IF NOT EXISTS `task_attachments` (
  `attachment_id` int(11) NOT NULL AUTO_INCREMENT,
  `task_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `file_path` text NOT NULL,
  `file_size` int(11) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `attachment_type` enum('file','link','youtube','google_drive') DEFAULT 'file',
  `attachment_url` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`attachment_id`),
  KEY `idx_task_id` (`task_id`),
  KEY `idx_attachment_type` (`attachment_type`),
  CONSTRAINT `fk_task_attachments_task` FOREIGN KEY (`task_id`) REFERENCES `class_tasks` (`task_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add index for better performance
CREATE INDEX `idx_task_attachments_composite` ON `task_attachments` (`task_id`, `attachment_type`);
