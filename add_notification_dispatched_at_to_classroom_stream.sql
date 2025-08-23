ALTER TABLE `classroom_stream`
  ADD COLUMN `notification_dispatched_at` DATETIME NULL DEFAULT NULL AFTER `scheduled_at`;


