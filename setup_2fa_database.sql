-- Setup 2FA Database Tables
-- Run this script to add Two-Factor Authentication support

-- Add 2FA fields to users table
ALTER TABLE `users` 
ADD COLUMN `two_factor_enabled` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Whether 2FA is enabled for this user',
ADD COLUMN `two_factor_secret` VARCHAR(64) NULL COMMENT 'Secret key for 2FA',
ADD COLUMN `two_factor_enabled_at` TIMESTAMP NULL COMMENT 'When 2FA was enabled',
ADD COLUMN `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update timestamp';

-- Create backup codes table for 2FA recovery
CREATE TABLE IF NOT EXISTS `backup_codes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(20) NOT NULL COMMENT 'Reference to users table',
  `codes` JSON NOT NULL COMMENT 'Hashed backup codes',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Backup codes for 2FA recovery';

-- Add indexes for better performance
ALTER TABLE `users` ADD INDEX `idx_two_factor_enabled` (`two_factor_enabled`);
ALTER TABLE `users` ADD INDEX `idx_two_factor_secret` (`two_factor_secret`);

-- Show the updated structure
DESCRIBE `users`;
DESCRIBE `backup_codes`;
