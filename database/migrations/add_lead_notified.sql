-- Migration: add `is_notified` column to `leads` table and add `organization_id` index if possible

ALTER TABLE `leads`
ADD COLUMN IF NOT EXISTS `is_notified` tinyint(1) DEFAULT 0 AFTER `status`;
