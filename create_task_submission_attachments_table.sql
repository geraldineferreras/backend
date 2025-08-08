-- Create task_submission_attachments table for multiple file support
CREATE TABLE IF NOT EXISTS `task_submission_attachments` (
  `attachment_id` int(11) NOT NULL AUTO_INCREMENT,
  `submission_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `file_path` text NOT NULL,
  `file_size` int(11) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `attachment_type` enum('file','link','youtube','google_drive') DEFAULT 'file',
  `attachment_url` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`attachment_id`),
  KEY `idx_submission_id` (`submission_id`),
  KEY `idx_attachment_type` (`attachment_type`),
  CONSTRAINT `fk_task_submission_attachments_submission` FOREIGN KEY (`submission_id`) REFERENCES `task_submissions` (`submission_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add index for better performance
CREATE INDEX `idx_submission_attachments_composite` ON `task_submission_attachments` (`submission_id`, `attachment_type`);
