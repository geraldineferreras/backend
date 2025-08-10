-- Create classroom_stream_comments table for comments on stream posts
CREATE TABLE IF NOT EXISTS `classroom_stream_comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `stream_id` int(11) NOT NULL,
  `user_id` varchar(50) NOT NULL,
  `comment` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_stream_id` (`stream_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_stream_comments_stream` FOREIGN KEY (`stream_id`) REFERENCES `classroom_stream` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_stream_comments_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add composite index for better performance
CREATE INDEX `idx_stream_comments_composite` ON `classroom_stream_comments` (`stream_id`, `created_at`);
