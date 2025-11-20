-- Makes password column nullable in users table
-- This allows users to register without a password (admin will send temporary password after approval)

ALTER TABLE `users` MODIFY COLUMN `password` VARCHAR(255) NULL;

