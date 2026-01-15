-- /db/seed.sql
-- This file contains seed data to populate the database for development and testing.

-- Clear existing data
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE `notifications`;
TRUNCATE TABLE `messages`;
TRUNCATE TABLE `property_images`;
TRUNCATE TABLE `properties`;
TRUNCATE TABLE `otps`;
TRUNCATE TABLE `kyc`;
TRUNCATE TABLE `users`;
SET FOREIGN_KEY_CHECKS = 1;

-- Seed Users
-- Password for all users is 'password123'
INSERT INTO `users` (`id`, `email`, `password_hash`, `first_name`, `last_name`, `role`, `is_verified`, `onboarding_step`, `avatar`) VALUES
(1, 'john.doe@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John', 'Doe', 'guest', 1, 'completed', 'http://example.com/avatars/john.jpg'),
(2, 'jane.smith@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Jane', 'Smith', 'host', 1, 'completed', 'http://example.com/avatars/jane.jpg'),
(3, 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'User', 'admin', 1, 'completed', 'http://example.com/avatars/admin.jpg'),
(4, 'host.extra@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Peter', 'Jones', 'host', 1, 'completed', 'http://example.com/avatars/peter.jpg');

-- Seed Properties
INSERT INTO `properties` (`id`, `host_id`, `name`, `description`, `type`, `price`, `bedrooms`, `bathrooms`, `amenities`, `address`, `city`, `state`, `zip_code`, `country`, `latitude`, `longitude`, `free_cancellation`, `booking_type`) VALUES
(1, 2, 'Luxury Downtown Apartment', 'A beautiful apartment in the heart of the city, offering stunning views and modern amenities.', 'apartment', 250.00, 2, 2, '["wifi", "pool", "gym", "ac"]', '123 Main St', 'New York', 'NY', '10001', 'USA', 40.7138, -74.0070, 1, 'instant'),
(2, 2, 'Cozy Suburban House', 'A charming house in a quiet suburban neighborhood, perfect for families.', 'house', 350.00, 4, 3, '["wifi", "kitchen", "parking", "garden"]', '456 Oak Ave', 'White Plains', 'NY', '10601', 'USA', 41.0339, -73.7629, 1, 'request'),
(3, 4, 'Modern Studio Loft', 'A stylish and compact studio in a trendy arts district.', 'studio', 150.00, 1, 1, '["wifi", "ac", "kitchenette"]', '789 Art St', 'Brooklyn', 'NY', '11201', 'USA', 40.6925, -73.9905, 0, 'instant'),
(4, 4, 'Spacious Duplex with Rooftop', 'A large duplex with a private rooftop terrace, great for entertaining.', 'duplex', 450.00, 3, 2, '["wifi", "pool", "rooftop", "ac"]', '101 Sky High Rd', 'New York', 'NY', '10002', 'USA', 40.7145, -73.9985, 1, 'request'),
(5, 2, 'Boutique Hotel Room', 'A private room in a centrally located boutique hotel.', 'hotel', 180.00, 1, 1, '["wifi", "room_service", "gym"]', '212 Central Ave', 'New York', 'NY', '10003', 'USA', 40.7308, -73.9973, 0, 'instant');

-- Seed Property Images
INSERT INTO `property_images` (`property_id`, `image_url`) VALUES
(1, 'https://source.unsplash.com/random/800x600?apartment,interior'),
(1, 'https://source.unsplash.com/random/800x600?apartment,livingroom'),
(2, 'https://source.unsplash.com/random/800x600?house,exterior'),
(2, 'https://source.unsplash.com/random/800x600?house,kitchen'),
(3, 'https://source.unsplash.com/random/800x600?studio,loft'),
(4, 'https://source.unsplash.com/random/800x600?duplex,rooftop'),
(5, 'https://source.unsplash.com/random/800x600?hotel,room');

-- Seed Notifications (for John Doe, user_id = 1)
INSERT INTO `notifications` (`user_id`, `message`, `is_read`) VALUES
(1, 'Your booking for "Luxury Downtown Apartment" has been confirmed.', 0),
(1, 'A new property "Modern Studio Loft" is available near you.', 0),
(1, 'Welcome to 360 HomeHub! Complete your profile to get started.', 1),
(1, 'Your password was changed successfully.', 1),
(1, 'You have a new message from Jane Smith.', 0);

-- Seed Messages (between John Doe and Jane Smith)
INSERT INTO `messages` (`sender_id`, `receiver_id`, `message`, `is_read`) VALUES
(2, 1, 'Hi John, welcome! Let me know if you have any questions about the apartment.', 0),
(1, 2, 'Thanks Jane! It looks great. I might have a question about parking later.', 1),
(2, 1, 'No problem, parking is included. Enjoy your stay!', 0),
(2, 1, 'Just wanted to check if you have settled in well.', 0);
