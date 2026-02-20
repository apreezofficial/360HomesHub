-- Migration: Add status and last_login to users table
-- Run this once against your 360HomesHub database

ALTER TABLE `users`
    ADD COLUMN IF NOT EXISTS `status` ENUM('active','suspended') NOT NULL DEFAULT 'active' AFTER `is_verified`,
    ADD COLUMN IF NOT EXISTS `last_login` TIMESTAMP NULL DEFAULT NULL AFTER `status`;

-- Ensure all existing rows default to active
UPDATE `users` SET `status` = 'active' WHERE `status` IS NULL;
