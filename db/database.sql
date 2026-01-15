-- /db/database.sql
-- This file contains the full schema for the application.

-- Drop tables if they exist to start from a clean state.
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `messages`;
DROP TABLE IF EXISTS `property_images`;
DROP TABLE IF EXISTS `properties`;
DROP TABLE IF EXISTS `otps`;
DROP TABLE IF EXISTS `kyc`;
DROP TABLE IF EXISTS `users`;


CREATE TABLE `users` (
  `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(255) UNIQUE NULL,
  `phone` VARCHAR(20) UNIQUE NULL,
  `google_id` VARCHAR(255) UNIQUE NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `first_name` VARCHAR(100) NULL,
  `last_name` VARCHAR(100) NULL,
  `bio` TEXT NULL,
  `address` TEXT NULL,
  `city` VARCHAR(100) NULL,
  `state` VARCHAR(100) NULL,
  `country` VARCHAR(100) NULL,
  `latitude` DECIMAL(10, 8) NULL,
  `longitude` DECIMAL(11, 8) NULL,
  `avatar` VARCHAR(255) NULL,
  `role` ENUM('guest','host','admin') NULL,
  `auth_provider` ENUM('email','phone','google') NOT NULL,
  `is_verified` BOOLEAN DEFAULT 0,
  `onboarding_step` ENUM('otp','password','profile','location','avatar','role','kyc','completed') DEFAULT 'otp',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE `kyc` (
  `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
  `user_id` BIGINT NOT NULL,
  `country` VARCHAR(100) NOT NULL,
  `identity_type` ENUM('passport','national_id','drivers_license') NOT NULL,
  `id_front` VARCHAR(255) NOT NULL,
  `id_back` VARCHAR(255) NOT NULL,
  `selfie` VARCHAR(255) NOT NULL,
  `status` ENUM('pending','approved','rejected') DEFAULT 'pending',
  `admin_note` TEXT NULL,
  `submitted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

CREATE TABLE `otps` (
  `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
  `user_id` BIGINT NOT NULL,
  `code` VARCHAR(6) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `used` BOOLEAN DEFAULT 0,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

CREATE TABLE `properties` (
  `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
  `host_id` BIGINT NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `type` ENUM('apartment', 'house', 'studio', 'duplex', 'hotel') NOT NULL,
  `price` DECIMAL(10, 2) NOT NULL,
  `price_type` ENUM('night', 'week', 'month') DEFAULT 'night',
  `bedrooms` INT NOT NULL,
  `bathrooms` INT NOT NULL,
  `area` INT NULL, -- in square feet
  `booking_type` ENUM('instant', 'request') DEFAULT 'request',
  `free_cancellation` BOOLEAN DEFAULT 0,
  `amenities` JSON NULL, -- e.g., ["wifi", "pool", "gym"]
  `address` VARCHAR(255) NOT NULL,
  `city` VARCHAR(100) NOT NULL,
  `state` VARCHAR(100) NOT NULL,
  `zip_code` VARCHAR(20) NOT NULL,
  `country` VARCHAR(100) NOT NULL,
  `latitude` DECIMAL(10, 8) NOT NULL,
  `longitude` DECIMAL(11, 8) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`host_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

CREATE TABLE `property_images` (
  `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
  `property_id` BIGINT NOT NULL,
  `image_url` VARCHAR(255) NOT NULL,
  `description` VARCHAR(255) NULL,
  `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`property_id`) REFERENCES `properties`(`id`) ON DELETE CASCADE
);

CREATE TABLE `notifications` (
  `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
  `user_id` BIGINT NOT NULL,
  `message` VARCHAR(255) NOT NULL,
  `is_read` BOOLEAN DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

CREATE TABLE `messages` (
  `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
  `sender_id` BIGINT NOT NULL,
  `receiver_id` BIGINT NOT NULL,
  `message` TEXT NOT NULL,
  `is_read` BOOLEAN DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`sender_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`receiver_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);
