SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET NAMES utf8mb4;
/*
INSERT INTO `migrations` (`migration`, `batch`) VALUES
  ('0001_01_01_000000_create_users_table', 1),
  ('0001_01_01_000001_create_cache_table', 1),
  ('0001_01_01_000002_create_jobs_table', 1),
  ('2026_04_16_163235_create_permission_tables', 1),
  ('2026_04_16_163946_create_vehicles_table', 1),
  ('2026_04_16_163948_create_vehicle_images_table', 1),
  ('2026_04_17_120000_add_marketplace_fields_to_vehicles_table', 1),
  ('2026_04_17_140000_create_vehicle_favorites_table', 1),
  ('2026_04_17_140001_create_vehicle_inquiries_table', 1),
  ('2026_04_17_200000_create_site_settings_table', 1),
  ('2026_04_18_100000_create_cms_pages_table', 1);
*/

CREATE TABLE IF NOT EXISTS `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @demo_ts = '2026-04-20 02:37:47';

INSERT IGNORE INTO `roles` (`name`, `guard_name`, `created_at`, `updated_at`) VALUES
  ('admin', 'web', @demo_ts, @demo_ts),
  ('user', 'web', @demo_ts, @demo_ts);

INSERT INTO `users` (`name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`) VALUES
  ('Demo Admin', 'admin@example.com', @demo_ts, '$2y$10$pSP6FS1ZyY4rhex.nO85HePRoGFylbhNO6IsMw3UGDAIsgQmMogM2', '2BjTnBF1H7', @demo_ts, @demo_ts),
  ('Demo User', 'demo@example.com', @demo_ts, '$2y$10$pSP6FS1ZyY4rhex.nO85HePRoGFylbhNO6IsMw3UGDAIsgQmMogM2', '2BjTnBF1H7', @demo_ts, @demo_ts)
ON DUPLICATE KEY UPDATE
  `name` = VALUES(`name`),
  `email_verified_at` = VALUES(`email_verified_at`),
  `password` = VALUES(`password`),
  `updated_at` = VALUES(`updated_at`);

INSERT IGNORE INTO `model_has_roles` (`role_id`, `model_type`, `model_id`)
SELECT r.id, 'App\\Models\\User', u.id
FROM `roles` r
JOIN `users` u ON (r.name = 'admin' AND u.email = 'admin@example.com')
   OR (r.name = 'user' AND u.email = 'demo@example.com');

DELETE vi FROM `vehicle_images` vi
JOIN `vehicles` v ON v.id = vi.vehicle_id
WHERE v.slug IN (
  '2016-mercedes-benz-c-class-c300-4matic',
  '2019-nissan-altima-25-sv',
  '2021-tesla-roadster'
);
DELETE FROM `vehicles` WHERE `slug` IN (
  '2016-mercedes-benz-c-class-c300-4matic',
  '2019-nissan-altima-25-sv',
  '2021-tesla-roadster'
);
INSERT INTO `vehicles` (`user_id`, `title`, `slug`, `status`, `year`, `make`, `model`, `price`, `mileage`, `transmission`, `fuel_type`, `drive`, `body_type`, `condition`, `engine_size`, `location`, `features`, `exterior_color`, `interior_color`, `description`, `submitted_at`, `approved_at`, `approved_by`, `created_at`, `updated_at`)
SELECT u.id, '2021 BMW M4 Competition', '2021-bmw-m4-competition', 'approved', 2021, 'BMW', 'M4', 89900, 12500, 'Automatic', 'Petrol', 'RWD', 'Coupe', 'used', '3.0L I6 twin-turbo', 'Miami, FL', '["M Sport package","Carbon roof","Live Cockpit Professional","Harman Kardon","Head-up display"]', 'Isle of Man Green', 'Silverstone', 'Demo listing: high-performance M4 Competition with adaptive M suspension and M Sport brakes. Includes six local media images.', @demo_ts, @demo_ts, approver.id, @demo_ts, @demo_ts
FROM `users` u
JOIN `users` approver ON approver.email = 'admin@example.com'
WHERE u.email = 'demo@example.com'
ON DUPLICATE KEY UPDATE `user_id` = VALUES(`user_id`), `title` = VALUES(`title`), `status` = VALUES(`status`), `year` = VALUES(`year`), `make` = VALUES(`make`), `model` = VALUES(`model`), `price` = VALUES(`price`), `mileage` = VALUES(`mileage`), `transmission` = VALUES(`transmission`), `fuel_type` = VALUES(`fuel_type`), `drive` = VALUES(`drive`), `body_type` = VALUES(`body_type`), `condition` = VALUES(`condition`), `engine_size` = VALUES(`engine_size`), `location` = VALUES(`location`), `features` = VALUES(`features`), `exterior_color` = VALUES(`exterior_color`), `interior_color` = VALUES(`interior_color`), `description` = VALUES(`description`), `submitted_at` = VALUES(`submitted_at`), `approved_at` = VALUES(`approved_at`), `approved_by` = VALUES(`approved_by`), `rejection_reason` = NULL, `updated_at` = VALUES(`updated_at`);
DELETE vi FROM `vehicle_images` vi
JOIN `vehicles` v ON v.id = vi.vehicle_id
WHERE v.slug = '2021-bmw-m4-competition';
INSERT INTO `vehicle_images` (`vehicle_id`, `path`, `sort_order`, `created_at`, `updated_at`)
SELECT v.id, 'asset/images/media/01-6-1-1.jpg', 0, @demo_ts, @demo_ts
FROM `vehicles` v
WHERE v.slug = '2021-bmw-m4-competition';
INSERT INTO `vehicle_images` (`vehicle_id`, `path`, `sort_order`, `created_at`, `updated_at`)
SELECT v.id, 'asset/images/media/01-7-1-1.jpg', 1, @demo_ts, @demo_ts
FROM `vehicles` v
WHERE v.slug = '2021-bmw-m4-competition';
INSERT INTO `vehicle_images` (`vehicle_id`, `path`, `sort_order`, `created_at`, `updated_at`)
SELECT v.id, 'asset/images/media/01-10-1.jpg', 2, @demo_ts, @demo_ts
FROM `vehicles` v
WHERE v.slug = '2021-bmw-m4-competition';
INSERT INTO `vehicle_images` (`vehicle_id`, `path`, `sort_order`, `created_at`, `updated_at`)
SELECT v.id, 'asset/images/media/01-24-1.jpg', 3, @demo_ts, @demo_ts
FROM `vehicles` v
WHERE v.slug = '2021-bmw-m4-competition';
INSERT INTO `vehicle_images` (`vehicle_id`, `path`, `sort_order`, `created_at`, `updated_at`)
SELECT v.id, 'asset/images/media/01-6-1-1.jpg', 4, @demo_ts, @demo_ts
FROM `vehicles` v
WHERE v.slug = '2021-bmw-m4-competition';
INSERT INTO `vehicle_images` (`vehicle_id`, `path`, `sort_order`, `created_at`, `updated_at`)
SELECT v.id, 'asset/images/media/01-7-1-1.jpg', 5, @demo_ts, @demo_ts
FROM `vehicles` v
WHERE v.slug = '2021-bmw-m4-competition';

INSERT INTO `vehicles` (`user_id`, `title`, `slug`, `status`, `year`, `make`, `model`, `price`, `mileage`, `transmission`, `fuel_type`, `drive`, `body_type`, `condition`, `engine_size`, `location`, `features`, `exterior_color`, `interior_color`, `description`, `submitted_at`, `approved_at`, `approved_by`, `created_at`, `updated_at`)
SELECT u.id, '2022 Audi RS6 Avant', '2022-audi-rs6-avant', 'approved', 2022, 'Audi', 'RS6 Avant', 112000, 5200, 'Automatic', 'Petrol', 'AWD', 'Wagon', 'used', '4.0L V8 twin-turbo', 'Austin, TX', '["RS sport exhaust","Matrix LED","Bang & Olufsen","Adaptive air suspension","Tour assist"]', 'Nardo Grey', 'Black', 'Demo listing: RS6 Avant with quattro sport differential and dynamic ride control. Includes six local media images.', @demo_ts, @demo_ts, approver.id, @demo_ts, @demo_ts
FROM `users` u
JOIN `users` approver ON approver.email = 'admin@example.com'
WHERE u.email = 'demo@example.com'
ON DUPLICATE KEY UPDATE `user_id` = VALUES(`user_id`), `title` = VALUES(`title`), `status` = VALUES(`status`), `year` = VALUES(`year`), `make` = VALUES(`make`), `model` = VALUES(`model`), `price` = VALUES(`price`), `mileage` = VALUES(`mileage`), `transmission` = VALUES(`transmission`), `fuel_type` = VALUES(`fuel_type`), `drive` = VALUES(`drive`), `body_type` = VALUES(`body_type`), `condition` = VALUES(`condition`), `engine_size` = VALUES(`engine_size`), `location` = VALUES(`location`), `features` = VALUES(`features`), `exterior_color` = VALUES(`exterior_color`), `interior_color` = VALUES(`interior_color`), `description` = VALUES(`description`), `submitted_at` = VALUES(`submitted_at`), `approved_at` = VALUES(`approved_at`), `approved_by` = VALUES(`approved_by`), `rejection_reason` = NULL, `updated_at` = VALUES(`updated_at`);
DELETE vi FROM `vehicle_images` vi
JOIN `vehicles` v ON v.id = vi.vehicle_id
WHERE v.slug = '2022-audi-rs6-avant';
INSERT INTO `vehicle_images` (`vehicle_id`, `path`, `sort_order`, `created_at`, `updated_at`)
SELECT v.id, 'asset/images/media/01-7-1-1.jpg', 0, @demo_ts, @demo_ts
FROM `vehicles` v
WHERE v.slug = '2022-audi-rs6-avant';
INSERT INTO `vehicle_images` (`vehicle_id`, `path`, `sort_order`, `created_at`, `updated_at`)
SELECT v.id, 'asset/images/media/01-10-1.jpg', 1, @demo_ts, @demo_ts
FROM `vehicles` v
WHERE v.slug = '2022-audi-rs6-avant';
INSERT INTO `vehicle_images` (`vehicle_id`, `path`, `sort_order`, `created_at`, `updated_at`)
SELECT v.id, 'asset/images/media/01-24-1.jpg', 2, @demo_ts, @demo_ts
FROM `vehicles` v
WHERE v.slug = '2022-audi-rs6-avant';
INSERT INTO `vehicle_images` (`vehicle_id`, `path`, `sort_order`, `created_at`, `updated_at`)
SELECT v.id, 'asset/images/media/01-6-1-1.jpg', 3, @demo_ts, @demo_ts
FROM `vehicles` v
WHERE v.slug = '2022-audi-rs6-avant';
INSERT INTO `vehicle_images` (`vehicle_id`, `path`, `sort_order`, `created_at`, `updated_at`)
SELECT v.id, 'asset/images/media/01-7-1-1.jpg', 4, @demo_ts, @demo_ts
FROM `vehicles` v
WHERE v.slug = '2022-audi-rs6-avant';
INSERT INTO `vehicle_images` (`vehicle_id`, `path`, `sort_order`, `created_at`, `updated_at`)
SELECT v.id, 'asset/images/media/01-10-1.jpg', 5, @demo_ts, @demo_ts
FROM `vehicles` v
WHERE v.slug = '2022-audi-rs6-avant';

INSERT INTO `vehicles` (`user_id`, `title`, `slug`, `status`, `year`, `make`, `model`, `price`, `mileage`, `transmission`, `fuel_type`, `drive`, `body_type`, `condition`, `engine_size`, `location`, `features`, `exterior_color`, `interior_color`, `description`, `submitted_at`, `approved_at`, `approved_by`, `created_at`, `updated_at`)
SELECT u.id, '2023 Porsche 911 GT3', '2023-porsche-911-gt3', 'approved', 2023, 'Porsche', '911 GT3', 185500, 850, 'PDK', 'Petrol', 'RWD', 'Coupe', 'used', '4.0L flat-6', 'Scottsdale, AZ', '["Clubsport package","PCCB","Front axle lift","LED-Matrix headlights","Track Precision app"]', 'Shark Blue', 'Black', 'Demo listing: track-focused 911 GT3 with Clubsport package and PCCB. Includes six local media images.', @demo_ts, @demo_ts, approver.id, @demo_ts, @demo_ts
FROM `users` u
JOIN `users` approver ON approver.email = 'admin@example.com'
WHERE u.email = 'demo@example.com'
ON DUPLICATE KEY UPDATE `user_id` = VALUES(`user_id`), `title` = VALUES(`title`), `status` = VALUES(`status`), `year` = VALUES(`year`), `make` = VALUES(`make`), `model` = VALUES(`model`), `price` = VALUES(`price`), `mileage` = VALUES(`mileage`), `transmission` = VALUES(`transmission`), `fuel_type` = VALUES(`fuel_type`), `drive` = VALUES(`drive`), `body_type` = VALUES(`body_type`), `condition` = VALUES(`condition`), `engine_size` = VALUES(`engine_size`), `location` = VALUES(`location`), `features` = VALUES(`features`), `exterior_color` = VALUES(`exterior_color`), `interior_color` = VALUES(`interior_color`), `description` = VALUES(`description`), `submitted_at` = VALUES(`submitted_at`), `approved_at` = VALUES(`approved_at`), `approved_by` = VALUES(`approved_by`), `rejection_reason` = NULL, `updated_at` = VALUES(`updated_at`);
DELETE vi FROM `vehicle_images` vi
JOIN `vehicles` v ON v.id = vi.vehicle_id
WHERE v.slug = '2023-porsche-911-gt3';
INSERT INTO `vehicle_images` (`vehicle_id`, `path`, `sort_order`, `created_at`, `updated_at`)
SELECT v.id, 'asset/images/media/01-10-1.jpg', 0, @demo_ts, @demo_ts
FROM `vehicles` v
WHERE v.slug = '2023-porsche-911-gt3';
INSERT INTO `vehicle_images` (`vehicle_id`, `path`, `sort_order`, `created_at`, `updated_at`)
SELECT v.id, 'asset/images/media/01-24-1.jpg', 1, @demo_ts, @demo_ts
FROM `vehicles` v
WHERE v.slug = '2023-porsche-911-gt3';
INSERT INTO `vehicle_images` (`vehicle_id`, `path`, `sort_order`, `created_at`, `updated_at`)
SELECT v.id, 'asset/images/media/01-6-1-1.jpg', 2, @demo_ts, @demo_ts
FROM `vehicles` v
WHERE v.slug = '2023-porsche-911-gt3';
INSERT INTO `vehicle_images` (`vehicle_id`, `path`, `sort_order`, `created_at`, `updated_at`)
SELECT v.id, 'asset/images/media/01-7-1-1.jpg', 3, @demo_ts, @demo_ts
FROM `vehicles` v
WHERE v.slug = '2023-porsche-911-gt3';
INSERT INTO `vehicle_images` (`vehicle_id`, `path`, `sort_order`, `created_at`, `updated_at`)
SELECT v.id, 'asset/images/media/01-10-1.jpg', 4, @demo_ts, @demo_ts
FROM `vehicles` v
WHERE v.slug = '2023-porsche-911-gt3';
INSERT INTO `vehicle_images` (`vehicle_id`, `path`, `sort_order`, `created_at`, `updated_at`)
SELECT v.id, 'asset/images/media/01-24-1.jpg', 5, @demo_ts, @demo_ts
FROM `vehicles` v
WHERE v.slug = '2023-porsche-911-gt3';

INSERT INTO `vehicles` (`user_id`, `title`, `slug`, `status`, `year`, `make`, `model`, `price`, `mileage`, `transmission`, `fuel_type`, `drive`, `body_type`, `condition`, `engine_size`, `location`, `features`, `exterior_color`, `interior_color`, `description`, `submitted_at`, `approved_at`, `approved_by`, `created_at`, `updated_at`)
SELECT u.id, '2023 Lexus RX 350', '2023-lexus-rx-350', 'approved', 2023, 'Lexus', 'RX 350', 62000, 2000, 'Automatic', 'Petrol', 'AWD', 'SUV', 'used', '3.5L V6', 'Dallas, TX', '["Premium package","Panoramic roof","Mark Levinson","Hands-free liftgate","Safety System+ 3.0"]', 'Atomic Silver', 'Rioja Red', 'Demo listing: low-mile RX 350 AWD with Premium package and Safety System+ 3.0. Includes six local media images.', @demo_ts, @demo_ts, approver.id, @demo_ts, @demo_ts
FROM `users` u
JOIN `users` approver ON approver.email = 'admin@example.com'
WHERE u.email = 'demo@example.com'
ON DUPLICATE KEY UPDATE `user_id` = VALUES(`user_id`), `title` = VALUES(`title`), `status` = VALUES(`status`), `year` = VALUES(`year`), `make` = VALUES(`make`), `model` = VALUES(`model`), `price` = VALUES(`price`), `mileage` = VALUES(`mileage`), `transmission` = VALUES(`transmission`), `fuel_type` = VALUES(`fuel_type`), `drive` = VALUES(`drive`), `body_type` = VALUES(`body_type`), `condition` = VALUES(`condition`), `engine_size` = VALUES(`engine_size`), `location` = VALUES(`location`), `features` = VALUES(`features`), `exterior_color` = VALUES(`exterior_color`), `interior_color` = VALUES(`interior_color`), `description` = VALUES(`description`), `submitted_at` = VALUES(`submitted_at`), `approved_at` = VALUES(`approved_at`), `approved_by` = VALUES(`approved_by`), `rejection_reason` = NULL, `updated_at` = VALUES(`updated_at`);
DELETE vi FROM `vehicle_images` vi
JOIN `vehicles` v ON v.id = vi.vehicle_id
WHERE v.slug = '2023-lexus-rx-350';
INSERT INTO `vehicle_images` (`vehicle_id`, `path`, `sort_order`, `created_at`, `updated_at`)
SELECT v.id, 'asset/images/media/01-24-1.jpg', 0, @demo_ts, @demo_ts
FROM `vehicles` v
WHERE v.slug = '2023-lexus-rx-350';
INSERT INTO `vehicle_images` (`vehicle_id`, `path`, `sort_order`, `created_at`, `updated_at`)
SELECT v.id, 'asset/images/media/01-6-1-1.jpg', 1, @demo_ts, @demo_ts
FROM `vehicles` v
WHERE v.slug = '2023-lexus-rx-350';
INSERT INTO `vehicle_images` (`vehicle_id`, `path`, `sort_order`, `created_at`, `updated_at`)
SELECT v.id, 'asset/images/media/01-7-1-1.jpg', 2, @demo_ts, @demo_ts
FROM `vehicles` v
WHERE v.slug = '2023-lexus-rx-350';
INSERT INTO `vehicle_images` (`vehicle_id`, `path`, `sort_order`, `created_at`, `updated_at`)
SELECT v.id, 'asset/images/media/01-10-1.jpg', 3, @demo_ts, @demo_ts
FROM `vehicles` v
WHERE v.slug = '2023-lexus-rx-350';
INSERT INTO `vehicle_images` (`vehicle_id`, `path`, `sort_order`, `created_at`, `updated_at`)
SELECT v.id, 'asset/images/media/01-24-1.jpg', 4, @demo_ts, @demo_ts
FROM `vehicles` v
WHERE v.slug = '2023-lexus-rx-350';
INSERT INTO `vehicle_images` (`vehicle_id`, `path`, `sort_order`, `created_at`, `updated_at`)
SELECT v.id, 'asset/images/media/01-6-1-1.jpg', 5, @demo_ts, @demo_ts
FROM `vehicles` v
WHERE v.slug = '2023-lexus-rx-350';

INSERT INTO `vehicles` (`user_id`, `title`, `slug`, `status`, `year`, `make`, `model`, `price`, `mileage`, `transmission`, `fuel_type`, `drive`, `body_type`, `condition`, `engine_size`, `location`, `features`, `exterior_color`, `interior_color`, `description`, `submitted_at`, `approved_at`, `approved_by`, `created_at`, `updated_at`)
SELECT u.id, '2022 Lexus ES 350', '2022-lexus-es-350', 'approved', 2022, 'Lexus', 'ES 350', 45900, 15000, 'Automatic', 'Petrol', 'FWD', 'Sedan', 'used', '3.5L V6', 'San Diego, CA', '["Ultra Luxury","Adaptive variable suspension","Panoramic monitor","Heated/ventilated seats"]', 'Obsidian', 'Acorn', 'Demo listing: comfortable ES 350 with Ultra Luxury package and strong highway manners. Includes six local media images.', @demo_ts, @demo_ts, approver.id, @demo_ts, @demo_ts
FROM `users` u
JOIN `users` approver ON approver.email = 'admin@example.com'
WHERE u.email = 'demo@example.com'
ON DUPLICATE KEY UPDATE `user_id` = VALUES(`user_id`), `title` = VALUES(`title`), `status` = VALUES(`status`), `year` = VALUES(`year`), `make` = VALUES(`make`), `model` = VALUES(`model`), `price` = VALUES(`price`), `mileage` = VALUES(`mileage`), `transmission` = VALUES(`transmission`), `fuel_type` = VALUES(`fuel_type`), `drive` = VALUES(`drive`), `body_type` = VALUES(`body_type`), `condition` = VALUES(`condition`), `engine_size` = VALUES(`engine_size`), `location` = VALUES(`location`), `features` = VALUES(`features`), `exterior_color` = VALUES(`exterior_color`), `interior_color` = VALUES(`interior_color`), `description` = VALUES(`description`), `submitted_at` = VALUES(`submitted_at`), `approved_at` = VALUES(`approved_at`), `approved_by` = VALUES(`approved_by`), `rejection_reason` = NULL, `updated_at` = VALUES(`updated_at`);
DELETE vi FROM `vehicle_images` vi
JOIN `vehicles` v ON v.id = vi.vehicle_id
WHERE v.slug = '2022-lexus-es-350';
INSERT INTO `vehicle_images` (`vehicle_id`, `path`, `sort_order`, `created_at`, `updated_at`)
SELECT v.id, 'asset/images/media/01-6-1-1.jpg', 0, @demo_ts, @demo_ts
FROM `vehicles` v
WHERE v.slug = '2022-lexus-es-350';
INSERT INTO `vehicle_images` (`vehicle_id`, `path`, `sort_order`, `created_at`, `updated_at`)
SELECT v.id, 'asset/images/media/01-7-1-1.jpg', 1, @demo_ts, @demo_ts
FROM `vehicles` v
WHERE v.slug = '2022-lexus-es-350';
INSERT INTO `vehicle_images` (`vehicle_id`, `path`, `sort_order`, `created_at`, `updated_at`)
SELECT v.id, 'asset/images/media/01-10-1.jpg', 2, @demo_ts, @demo_ts
FROM `vehicles` v
WHERE v.slug = '2022-lexus-es-350';
INSERT INTO `vehicle_images` (`vehicle_id`, `path`, `sort_order`, `created_at`, `updated_at`)
SELECT v.id, 'asset/images/media/01-24-1.jpg', 3, @demo_ts, @demo_ts
FROM `vehicles` v
WHERE v.slug = '2022-lexus-es-350';
INSERT INTO `vehicle_images` (`vehicle_id`, `path`, `sort_order`, `created_at`, `updated_at`)
SELECT v.id, 'asset/images/media/01-6-1-1.jpg', 4, @demo_ts, @demo_ts
FROM `vehicles` v
WHERE v.slug = '2022-lexus-es-350';
INSERT INTO `vehicle_images` (`vehicle_id`, `path`, `sort_order`, `created_at`, `updated_at`)
SELECT v.id, 'asset/images/media/01-7-1-1.jpg', 5, @demo_ts, @demo_ts
FROM `vehicles` v
WHERE v.slug = '2022-lexus-es-350';

INSERT INTO `vehicles` (`user_id`, `title`, `slug`, `status`, `year`, `make`, `model`, `price`, `mileage`, `transmission`, `fuel_type`, `drive`, `body_type`, `condition`, `engine_size`, `location`, `features`, `exterior_color`, `interior_color`, `description`, `submitted_at`, `approved_at`, `approved_by`, `created_at`, `updated_at`)
SELECT u.id, '2023 Lamborghini Urus', '2023-lamborghini-urus', 'approved', 2023, 'Lamborghini', 'Urus', 305000, 1200, 'Automatic', 'Petrol', 'AWD', 'SUV', 'used', '4.0L V8 twin-turbo', 'Los Angeles, CA', '["ANIMA drive modes","Carbon ceramic brakes","Bang & Olufsen 3D","Night vision","Rear-wheel steering"]', 'Giallo Auge', 'Nero Ade', 'Demo listing: Urus with aggressive styling, ANIMA drive modes, and daily usability. Includes six local media images.', @demo_ts, @demo_ts, approver.id, @demo_ts, @demo_ts
FROM `users` u
JOIN `users` approver ON approver.email = 'admin@example.com'
WHERE u.email = 'demo@example.com'
ON DUPLICATE KEY UPDATE `user_id` = VALUES(`user_id`), `title` = VALUES(`title`), `status` = VALUES(`status`), `year` = VALUES(`year`), `make` = VALUES(`make`), `model` = VALUES(`model`), `price` = VALUES(`price`), `mileage` = VALUES(`mileage`), `transmission` = VALUES(`transmission`), `fuel_type` = VALUES(`fuel_type`), `drive` = VALUES(`drive`), `body_type` = VALUES(`body_type`), `condition` = VALUES(`condition`), `engine_size` = VALUES(`engine_size`), `location` = VALUES(`location`), `features` = VALUES(`features`), `exterior_color` = VALUES(`exterior_color`), `interior_color` = VALUES(`interior_color`), `description` = VALUES(`description`), `submitted_at` = VALUES(`submitted_at`), `approved_at` = VALUES(`approved_at`), `approved_by` = VALUES(`approved_by`), `rejection_reason` = NULL, `updated_at` = VALUES(`updated_at`);
DELETE vi FROM `vehicle_images` vi
JOIN `vehicles` v ON v.id = vi.vehicle_id
WHERE v.slug = '2023-lamborghini-urus';
INSERT INTO `vehicle_images` (`vehicle_id`, `path`, `sort_order`, `created_at`, `updated_at`)
SELECT v.id, 'asset/images/media/01-7-1-1.jpg', 0, @demo_ts, @demo_ts
FROM `vehicles` v
WHERE v.slug = '2023-lamborghini-urus';
INSERT INTO `vehicle_images` (`vehicle_id`, `path`, `sort_order`, `created_at`, `updated_at`)
SELECT v.id, 'asset/images/media/01-10-1.jpg', 1, @demo_ts, @demo_ts
FROM `vehicles` v
WHERE v.slug = '2023-lamborghini-urus';
INSERT INTO `vehicle_images` (`vehicle_id`, `path`, `sort_order`, `created_at`, `updated_at`)
SELECT v.id, 'asset/images/media/01-24-1.jpg', 2, @demo_ts, @demo_ts
FROM `vehicles` v
WHERE v.slug = '2023-lamborghini-urus';
INSERT INTO `vehicle_images` (`vehicle_id`, `path`, `sort_order`, `created_at`, `updated_at`)
SELECT v.id, 'asset/images/media/01-6-1-1.jpg', 3, @demo_ts, @demo_ts
FROM `vehicles` v
WHERE v.slug = '2023-lamborghini-urus';
INSERT INTO `vehicle_images` (`vehicle_id`, `path`, `sort_order`, `created_at`, `updated_at`)
SELECT v.id, 'asset/images/media/01-7-1-1.jpg', 4, @demo_ts, @demo_ts
FROM `vehicles` v
WHERE v.slug = '2023-lamborghini-urus';
INSERT INTO `vehicle_images` (`vehicle_id`, `path`, `sort_order`, `created_at`, `updated_at`)
SELECT v.id, 'asset/images/media/01-10-1.jpg', 5, @demo_ts, @demo_ts
FROM `vehicles` v
WHERE v.slug = '2023-lamborghini-urus';

UPDATE `vehicles` v
JOIN `users` a ON a.email = 'admin@example.com'
SET
  v.`status` = 'approved',
  v.`approved_at` = COALESCE(v.`approved_at`, @demo_ts),
  v.`approved_by` = a.id,
  v.`rejection_reason` = NULL,
  v.`updated_at` = NOW()
WHERE v.`slug` IN (
  '2021-bmw-m4-competition',
  '2022-audi-rs6-avant',
  '2023-porsche-911-gt3',
  '2023-lexus-rx-350',
  '2022-lexus-es-350',
  '2023-lamborghini-urus'
);

SET @has_is_active := (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'cms_pages'
    AND COLUMN_NAME = 'is_active'
);
SET @sql := IF(
  @has_is_active = 0,
  'ALTER TABLE `cms_pages` ADD COLUMN `is_active` TINYINT(1) NOT NULL DEFAULT 1 AFTER `content_html`;',
  'SELECT ''cms_pages.is_active already exists'';'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

CREATE TABLE IF NOT EXISTS `page_sections` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `page` VARCHAR(255) NOT NULL,
  `section_key` VARCHAR(255) NOT NULL,
  `content_type` VARCHAR(50) NOT NULL DEFAULT 'text',
  `content` TEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `page_sections_page_section_key_unique` (`page`, `section_key`),
  KEY `page_sections_page_index` (`page`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `media` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `filename` VARCHAR(255) NOT NULL,
  `original_name` VARCHAR(255) NULL,
  `file_path` VARCHAR(255) NOT NULL,
  `file_type` VARCHAR(100) NULL,
  `file_size` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `uploaded_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `media_created_at_index` (`created_at`),
  KEY `media_uploaded_by_foreign` (`uploaded_by`),
  CONSTRAINT `media_uploaded_by_foreign`
    FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

UPDATE `cms_pages`
SET `content_html` = ''
WHERE `slug` = 'home'
  AND (
    `content_html` LIKE '%elementor%'
    OR `content_html` LIKE '%__HOME_INVENTORY_SEARCH__%'
    OR `content_html` LIKE '%stm-%'
    OR `content_html` LIKE '%wp-%'
  );

INSERT INTO `cms_pages` (`slug`, `title`, `meta_description`, `content_html`, `is_active`, `created_at`, `updated_at`)
VALUES
('listing-detail', 'Product Detail', 'Product detail page heading and intro copy.', '', 1, NOW(), NOW()),
('inventory', 'Shop', 'Shop page heading and SEO. Product cards always load from active products.', '', 1, NOW(), NOW()),
('compare', 'Compare Products', 'Compare page heading and intro copy.', '', 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE
  `is_active` = 1,
  `updated_at` = VALUES(`updated_at`);

INSERT INTO `page_sections` (`page`, `section_key`, `content_type`, `content`, `created_at`, `updated_at`) VALUES
('home', 'hero_title', 'text', 'Artisanship Redefined', NOW(), NOW()),
('home', 'hero_subtitle', 'text', 'Luxury apparel and accessories cut, sewn, and finished in our Lagos atelier.', NOW(), NOW()),
('home', 'hero_cta_text', 'text', 'Explore Collection', NOW(), NOW()),
('home', 'hero_cta_href', 'text', '/shop', NOW(), NOW()),
('home', 'home_search_label', 'text', '', NOW(), NOW()),
('home', 'recent_title', 'text', '', NOW(), NOW()),
('home', 'recent_subtitle', 'textarea', '', NOW(), NOW()),
('home', 'hero_image', 'image', 'asset/images/media/home-hero-main.jpg', NOW(), NOW()),
('home', 'cta_left_image', 'image', 'asset/images/media/home-cta-left.jpg', NOW(), NOW()),
('home', 'cta_right_image', 'image', 'asset/images/media/home-cta-right.jpg', NOW(), NOW()),
('home', 'cta_left_title', 'text', '', NOW(), NOW()),
('home', 'cta_left_body', 'textarea', '', NOW(), NOW()),
('home', 'cta_right_title', 'text', '', NOW(), NOW()),
('home', 'cta_right_body', 'textarea', '', NOW(), NOW()),
('home', 'feat1_title', 'text', '', NOW(), NOW()),
('home', 'feat1_body', 'textarea', '', NOW(), NOW()),
('home', 'feat2_title', 'text', '', NOW(), NOW()),
('home', 'feat2_body', 'textarea', '', NOW(), NOW()),
('home', 'feat3_title', 'text', '', NOW(), NOW()),
('home', 'feat3_body', 'textarea', '', NOW(), NOW()),
('home', 'welcome_title', 'text', 'Crafting a New Legacy', NOW(), NOW()),
('home', 'welcome_body', 'textarea', 'We collaborate with master artisans to bring exceptional pieces to a global audience. Each product reflects quality, story, and craft.', NOW(), NOW()),
('home', 'testimonial_name', 'text', '', NOW(), NOW()),
('home', 'testimonial_role', 'text', '', NOW(), NOW()),
('home', 'testimonial_quote', 'textarea', '', NOW(), NOW()),
('inventory', 'heading', 'text', 'Shop the Collection', NOW(), NOW()),
('inventory', 'intro', 'textarea', '', NOW(), NOW()),
('inventory', 'fallback_image', 'image', 'asset/images/media/inventory-listing-fallback.jpg', NOW(), NOW()),
('contact', 'heading', 'text', 'Contact Us', NOW(), NOW()),
('contact', 'intro', 'textarea', '', NOW(), NOW()),
('contact', 'hero_image', 'image', 'asset/images/media/contact-hero-bg.jpg', NOW(), NOW()),
('contact', 'map_image', 'image', 'asset/images/media/contact-map.jpg', NOW(), NOW()),
('compare', 'heading', 'text', 'Compare Products', NOW(), NOW()),
('compare', 'intro', 'textarea', 'Compare list is dynamic and comes from visitor selections.', NOW(), NOW()),
('listing-detail', 'heading', 'text', 'Product Detail', NOW(), NOW()),
('listing-detail', 'intro', 'textarea', 'Product details and gallery are dynamic from the product record.', NOW(), NOW())
ON DUPLICATE KEY UPDATE
  `content_type` = VALUES(`content_type`),
  `content` = VALUES(`content`),
  `updated_at` = VALUES(`updated_at`);

CREATE TABLE IF NOT EXISTS `site_traffic_events` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `path` VARCHAR(1024) NOT NULL,
  `route_name` VARCHAR(255) NULL,
  `url` TEXT NULL,
  `method` VARCHAR(10) NOT NULL DEFAULT 'GET',
  `referrer_host` VARCHAR(255) NULL,
  `referrer_url` TEXT NULL,
  `user_agent` VARCHAR(255) NULL,
  `ip_hash` VARCHAR(64) NULL,
  `session_id` VARCHAR(120) NULL,
  `vehicle_id` BIGINT UNSIGNED NULL,
  `vehicle_slug` VARCHAR(255) NULL,
  `viewed_at` TIMESTAMP NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `site_traffic_events_viewed_at_path_index` (`viewed_at`, `path`(191)),
  KEY `site_traffic_events_viewed_at_route_name_index` (`viewed_at`, `route_name`),
  KEY `site_traffic_events_viewed_at_vehicle_id_index` (`viewed_at`, `vehicle_id`),
  KEY `site_traffic_events_viewed_at_session_id_index` (`viewed_at`, `session_id`),
  KEY `site_traffic_events_vehicle_id_foreign` (`vehicle_id`),
  CONSTRAINT `site_traffic_events_vehicle_id_foreign`
    FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
