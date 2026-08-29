-- Run this SQL manually on the Neweasylogics database
-- Creates the user_permissions table for reseller user module-wise permissions

CREATE TABLE IF NOT EXISTS `user_permissions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `module` VARCHAR(50) NOT NULL,
  `can_add` TINYINT NOT NULL DEFAULT 0,
  `can_edit` TINYINT NOT NULL DEFAULT 0,
  `can_delete` TINYINT NOT NULL DEFAULT 0,
  `can_generate` TINYINT NOT NULL DEFAULT 0,
  `can_view` TINYINT NOT NULL DEFAULT 0,
  `cdate` DATETIME NULL,
  `udate` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_module_unique` (`user_id`, `module`),
  KEY `user_id_index` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Also add name, email, mobile columns to users table if they don't exist
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `name` VARCHAR(100) NULL AFTER `username`;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `email` VARCHAR(150) NULL AFTER `name`;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `mobile` VARCHAR(20) NULL AFTER `email`;

-- Fix role column: char(10) is too small for 'ResellerUser' (12 chars)
ALTER TABLE `users` MODIFY COLUMN `role` VARCHAR(20) NOT NULL;
UPDATE `users` SET `role` = 'ResellerUser' WHERE `role` = 'ResellerUs';
