-- Migration: Add atomic name fields to users table
-- Run this in DBeaver SQL Editor

-- Step 1: Add the columns (run these one by one or all together)
ALTER TABLE `users` ADD COLUMN `first_name` VARCHAR(100) NULL AFTER `full_name`;
ALTER TABLE `users` ADD COLUMN `middle_name` VARCHAR(100) NULL AFTER `first_name`;
ALTER TABLE `users` ADD COLUMN `last_name` VARCHAR(100) NULL AFTER `middle_name`;

-- Step 2: Add indexes for better performance
CREATE INDEX idx_users_first_name ON users(first_name);
CREATE INDEX idx_users_last_name ON users(last_name);


