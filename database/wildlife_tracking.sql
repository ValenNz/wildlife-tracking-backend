SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

CREATE TABLE `users` (
  `user_id` int UNSIGNED NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('konservasionis_lapangan','peneliti_ekologi','administrator_sistem','pengambil_kebijakan') COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` (`user_id`, `name`, `email`, `password`, `role`, `created_at`) VALUES
(1, 'Demo Konservasi', 'demo@wildlife.local', '$2y$12$OTdQxJZH5EIhFjv.XqM7le35DD0nsKXS/fbswQoezpdFCBbMXUw9q', 'konservasionis_lapangan', '2026-10-11 13:25:18');

CREATE TABLE `species` (
  `species_id` int UNSIGNED NOT NULL,
  `common_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `scientific_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `conservation_status` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `species` (`species_id`, `common_name`, `scientific_name`, `conservation_status`) VALUES
(1, 'Orangutan Sumatra', 'Pongo abelii', 'Critically Endangered'),
(2, 'Harimau Sumatra', 'Panthera tigris sumatrae', 'Critically Endangered'),
(3, 'Badak Sumatra', 'Dicerorhinus sumatrensis', 'Critically Endangered');

CREATE TABLE `animals` (
  `animal_id` int UNSIGNED NOT NULL,
  `species_id` int UNSIGNED NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `birth_date` date DEFAULT NULL,
  `gender` enum('male','female','unknown') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unknown',
  `is_active` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `animals` (`animal_id`, `species_id`, `name`, `birth_date`, `gender`, `is_active`) VALUES
(1, 1, 'DEMO-ORANG-001', NULL, 'unknown', 1),
(2, 2, 'DEMO-TIGER-001', NULL, 'unknown', 1),
(3, 3, 'DEMO-RHINO-001', NULL, 'unknown', 1);

CREATE TABLE `trackingdevices` (
  `device_id` int UNSIGNED NOT NULL,
  `animal_id` int UNSIGNED DEFAULT NULL,
  `model` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `installation_date` date DEFAULT NULL,
  `battery_life_months` smallint UNSIGNED DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `device_condition` enum('normal','damaged','lost') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normal',
  `battery_level` tinyint UNSIGNED DEFAULT NULL,
  `signal_strength` tinyint UNSIGNED DEFAULT NULL,
  `last_recorded_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `trackingdevices` (`device_id`, `animal_id`, `model`, `installation_date`, `battery_life_months`, `is_active`, `device_condition`, `battery_level`, `signal_strength`, `last_recorded_at`) VALUES
(1, 1, 'DEMO-GPS-COLLAR-A', NULL, 12, 1, 'normal', 92, 4, '2025-05-13 15:00:00'),
(2, 2, 'DEMO-GPS-COLLAR-B', NULL, 12, 1, 'normal', 78, 3, '2025-05-13 09:00:00'),
(3, 3, 'DEMO-GPS-COLLAR-C', NULL, 12, 1, 'normal', 85, 4, '2025-05-13 12:00:00');

CREATE TABLE `geozones` (
  `zone_id` int UNSIGNED NOT NULL,
  `zone_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `zone_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `center_lat` decimal(9,6) DEFAULT NULL,
  `center_lon` decimal(10,6) DEFAULT NULL,
  `radius_km` decimal(8,3) DEFAULT NULL,
  `polygon_geojson` json DEFAULT NULL,
  `created_by` int UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `geozones` (`zone_id`, `zone_name`, `zone_type`, `center_lat`, `center_lon`, `radius_km`, `polygon_geojson`, `created_by`, `created_at`) VALUES
(1, 'DEMO-Zona Utara', 'demo_simulation', 3.750000, 97.350000, 2.000, NULL, 1, '2026-10-11 13:25:18'),
(2, 'DEMO-Zona Tengah', 'demo_simulation', 3.600000, 97.550000, 2.000, NULL, 1, '2026-10-11 13:25:18'),
(3, 'DEMO-Zona Selatan', 'demo_simulation', 3.900000, 97.200000, 2.000, NULL, 1, '2026-10-11 13:25:18');

CREATE TABLE `gpsreadings` (
  `reading_id` bigint UNSIGNED NOT NULL,
  `device_id` int UNSIGNED NOT NULL,
  `latitude` decimal(9,6) NOT NULL,
  `longitude` decimal(10,6) NOT NULL,
  `recorded_at` datetime NOT NULL,
  `altitude_meters` decimal(8,2) DEFAULT NULL,
  `accuracy_meters` decimal(6,2) DEFAULT NULL,
  `checksum` char(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `gpsreadings` (`reading_id`, `device_id`, `latitude`, `longitude`, `recorded_at`, `altitude_meters`, `accuracy_meters`, `checksum`) VALUES
(1, 1, 3.749593, 97.349788, '2025-05-01 06:00:00', 323.15, 3.60, NULL),
(2, 2, 3.598997, 97.559471, '2025-05-01 09:00:00', 664.32, 14.43, NULL),
(3, 3, 3.892264, 97.222439, '2025-05-01 12:00:00', 583.89, 15.89, NULL);

CREATE TABLE `animalgeozones` (
  `visit_id` bigint UNSIGNED NOT NULL,
  `animal_id` int UNSIGNED NOT NULL,
  `zone_id` int UNSIGNED NOT NULL,
  `entry_reading_id` bigint UNSIGNED DEFAULT NULL,
  `entered_at` datetime NOT NULL,
  `exited_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `animalgeozones` (`visit_id`, `animal_id`, `zone_id`, `entry_reading_id`, `entered_at`, `exited_at`) VALUES
(1, 1, 1, 1, '2025-05-01 06:00:00', '2025-05-01 15:00:00'),
(2, 2, 2, 2, '2025-05-01 09:00:00', '2025-05-01 18:00:00'),
(3, 3, 3, 3, '2025-05-01 12:00:00', '2025-05-01 21:00:00');

CREATE TABLE `incidentreports` (
  `report_id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED DEFAULT NULL,
  `incident_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `animal_code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `location` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `timestamp` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `photo_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sync_status` enum('pending_sync','synced','failed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending_sync'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `incidentreports` (`report_id`, `user_id`, `incident_type`, `animal_code`, `location`, `description`, `timestamp`, `photo_url`, `sync_status`) VALUES
(1, 1, 'demo_observation', 'DEMO-ORANG-001', 'DEMO coordinate', 'SIMULATED demonstration report.', '2025-05-01 08:00:00', NULL, 'synced'),
(2, 1, 'demo_observation', 'DEMO-TIGER-001', 'DEMO coordinate', 'SIMULATED demonstration report.', '2025-05-02 08:00:00', NULL, 'synced');

CREATE TABLE `aimediauploads` (
  `media_id` bigint UNSIGNED NOT NULL,
  `uploaded_by` int UNSIGNED DEFAULT NULL,
  `report_id` int UNSIGNED DEFAULT NULL,
  `media_type` enum('image','video') COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_path` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `uploaded_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `aimediauploads` (`media_id`, `uploaded_by`, `report_id`, `media_type`, `file_path`, `uploaded_at`) VALUES
(1, 1, NULL, 'image', 'DEMO_PLACEHOLDER.jpg', '2026-10-11 13:25:18'),
(2, 1, NULL, 'video', 'DEMO_PLACEHOLDER.mp4', '2026-10-11 13:25:18');

CREATE TABLE `aipredictions` (
  `prediction_id` bigint UNSIGNED NOT NULL,
  `media_id` bigint UNSIGNED NOT NULL,
  `predicted_species_id` int UNSIGNED DEFAULT NULL,
  `confidence` decimal(5,4) DEFAULT NULL,
  `model_version` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `processing_status` enum('queued','processing','completed','failed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'queued',
  `processed_at` datetime DEFAULT NULL,
  `verification_status` enum('pending','confirmed','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `verified_species_id` int UNSIGNED DEFAULT NULL,
  `verified_by` int UNSIGNED DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `activitylog` (
  `log_id` bigint UNSIGNED NOT NULL,
  `user_id` int UNSIGNED DEFAULT NULL,
  `action` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `timestamp` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `details` text COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `activitylog` (`log_id`, `user_id`, `action`, `timestamp`, `details`) VALUES
(1, 1, 'DEMO_SEED_IMPORT', '2025-05-03 09:00:00', 'Synthetic demonstration event.'),
(2, 1, 'DEMO_GPS_VIEW', '2025-05-03 09:15:00', 'Synthetic demonstration event.'),
(3, 1, 'DEMO_AI_MODULE_VIEW', '2025-05-03 09:30:00', 'Synthetic demonstration event.');

CREATE TABLE `aggregatereports` (
  `report_id` int UNSIGNED NOT NULL,
  `generated_by` int UNSIGNED DEFAULT NULL,
  `report_type` enum('daily','weekly','monthly') COLLATE utf8mb4_unicode_ci NOT NULL,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `generated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `file_path` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `aggregatereports` (`report_id`, `generated_by`, `report_type`, `period_start`, `period_end`, `generated_at`, `file_path`) VALUES
(1, 1, 'daily', '2025-05-01', '2025-05-01', '2026-10-11 13:25:18', NULL),
(2, 1, 'weekly', '2025-05-01', '2025-05-07', '2026-10-11 13:25:18', NULL);

CREATE TABLE `notifications` (
  `notif_id` bigint UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `notification_type` enum('risk_zone','device_alert','system') COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `animal_id` int UNSIGNED DEFAULT NULL,
  `zone_id` int UNSIGNED DEFAULT NULL,
  `device_id` int UNSIGNED DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `notifications` (`notif_id`, `user_id`, `notification_type`, `message`, `animal_id`, `zone_id`, `device_id`, `is_read`, `created_at`) VALUES
(1, 1, 'system', 'DEMO: data seed berhasil disiapkan.', 1, 1, NULL, 0, '2026-10-11 13:25:18'),
(2, 1, 'risk_zone', 'DEMO: contoh notifikasi zona.', 2, 2, NULL, 0, '2026-10-11 13:25:18'),
(3, 1, 'device_alert', 'DEMO: contoh peringatan perangkat.', 3, 3, NULL, 0, '2026-10-11 13:25:18');

CREATE TABLE `weatherdata` (
  `weather_id` bigint UNSIGNED NOT NULL,
  `reading_id` bigint UNSIGNED DEFAULT NULL,
  `latitude` decimal(9,6) NOT NULL,
  `longitude` decimal(10,6) NOT NULL,
  `observed_at` datetime NOT NULL,
  `temperature_c` decimal(5,2) DEFAULT NULL,
  `rainfall_mm` decimal(7,2) DEFAULT NULL,
  `humidity_percent` decimal(5,2) DEFAULT NULL,
  `pressure_hpa` decimal(7,2) DEFAULT NULL,
  `source` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'WeatherAPI.com'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `weatherdata` (`weather_id`, `reading_id`, `latitude`, `longitude`, `observed_at`, `temperature_c`, `rainfall_mm`, `humidity_percent`, `pressure_hpa`, `source`) VALUES
(1, NULL, 3.750000, 97.350000, '2025-05-01 06:00:00', 25.00, 0.00, 85.00, 1010.00, 'SIMULATED_DEMO'),
(2, NULL, 3.600000, 97.550000, '2025-05-01 09:00:00', 26.20, 0.30, 82.00, 1009.50, 'SIMULATED_DEMO'),
(3, NULL, 3.900000, 97.200000, '2025-05-01 12:00:00', 27.00, 0.00, 78.00, 1009.00, 'SIMULATED_DEMO');

-- Indexes
ALTER TABLE `users` ADD PRIMARY KEY (`user_id`), ADD UNIQUE KEY `uq_users_email` (`email`);
ALTER TABLE `species` ADD PRIMARY KEY (`species_id`), ADD UNIQUE KEY `uq_species_scientific_name` (`scientific_name`);
ALTER TABLE `animals` ADD PRIMARY KEY (`animal_id`), ADD KEY `idx_animals_species` (`species_id`);
ALTER TABLE `trackingdevices` ADD PRIMARY KEY (`device_id`), ADD UNIQUE KEY `uq_devices_animal` (`animal_id`);
ALTER TABLE `geozones` ADD PRIMARY KEY (`zone_id`);
ALTER TABLE `gpsreadings` ADD PRIMARY KEY (`reading_id`), ADD UNIQUE KEY `uq_gps_device_time` (`device_id`,`recorded_at`);
ALTER TABLE `animalgeozones` ADD PRIMARY KEY (`visit_id`);
ALTER TABLE `incidentreports` ADD PRIMARY KEY (`report_id`);
ALTER TABLE `aimediauploads` ADD PRIMARY KEY (`media_id`);
ALTER TABLE `aipredictions` ADD PRIMARY KEY (`prediction_id`);
ALTER TABLE `activitylog` ADD PRIMARY KEY (`log_id`);
ALTER TABLE `aggregatereports` ADD PRIMARY KEY (`report_id`);
ALTER TABLE `notifications` ADD PRIMARY KEY (`notif_id`);
ALTER TABLE `weatherdata` ADD PRIMARY KEY (`weather_id`);

-- AUTO_INCREMENT
ALTER TABLE `users` MODIFY `user_id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
ALTER TABLE `species` MODIFY `species_id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
ALTER TABLE `animals` MODIFY `animal_id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
ALTER TABLE `trackingdevices` MODIFY `device_id` int UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `geozones` MODIFY `zone_id` int UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `gpsreadings` MODIFY `reading_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `animalgeozones` MODIFY `visit_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `incidentreports` MODIFY `report_id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
ALTER TABLE `aimediauploads` MODIFY `media_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
ALTER TABLE `aipredictions` MODIFY `prediction_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `activitylog` MODIFY `log_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
ALTER TABLE `aggregatereports` MODIFY `report_id` int UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `notifications` MODIFY `notif_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
ALTER TABLE `weatherdata` MODIFY `weather_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

-- Foreign Keys
ALTER TABLE `animals` ADD CONSTRAINT `fk_animals_species` FOREIGN KEY (`species_id`) REFERENCES `species` (`species_id`) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE `trackingdevices` ADD CONSTRAINT `fk_devices_animal` FOREIGN KEY (`animal_id`) REFERENCES `animals` (`animal_id`) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE `geozones` ADD CONSTRAINT `fk_zones_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE `gpsreadings` ADD CONSTRAINT `fk_gps_device` FOREIGN KEY (`device_id`) REFERENCES `trackingdevices` (`device_id`) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE `animalgeozones` ADD CONSTRAINT `fk_ag_animal` FOREIGN KEY (`animal_id`) REFERENCES `animals` (`animal_id`) ON DELETE CASCADE ON UPDATE CASCADE, ADD CONSTRAINT `fk_ag_zone` FOREIGN KEY (`zone_id`) REFERENCES `geozones` (`zone_id`) ON DELETE CASCADE ON UPDATE CASCADE, ADD CONSTRAINT `fk_ag_reading` FOREIGN KEY (`entry_reading_id`) REFERENCES `gpsreadings` (`reading_id`) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE `incidentreports` ADD CONSTRAINT `fk_ir_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE `aimediauploads` ADD CONSTRAINT `fk_media_user` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE, ADD CONSTRAINT `fk_media_report` FOREIGN KEY (`report_id`) REFERENCES `incidentreports` (`report_id`) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE `aipredictions` ADD CONSTRAINT `fk_pred_media` FOREIGN KEY (`media_id`) REFERENCES `aimediauploads` (`media_id`) ON DELETE CASCADE ON UPDATE CASCADE, ADD CONSTRAINT `fk_pred_species` FOREIGN KEY (`predicted_species_id`) REFERENCES `species` (`species_id`) ON DELETE SET NULL ON UPDATE CASCADE, ADD CONSTRAINT `fk_pred_verified_by` FOREIGN KEY (`verified_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE `activitylog` ADD CONSTRAINT `fk_log_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE `aggregatereports` ADD CONSTRAINT `fk_ar_user` FOREIGN KEY (`generated_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE `notifications` ADD CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE, ADD CONSTRAINT `fk_notif_animal` FOREIGN KEY (`animal_id`) REFERENCES `animals` (`animal_id`) ON DELETE SET NULL ON UPDATE CASCADE, ADD CONSTRAINT `fk_notif_zone` FOREIGN KEY (`zone_id`) REFERENCES `geozones` (`zone_id`) ON DELETE SET NULL ON UPDATE CASCADE, ADD CONSTRAINT `fk_notif_device` FOREIGN KEY (`device_id`) REFERENCES `trackingdevices` (`device_id`) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE `weatherdata` ADD CONSTRAINT `fk_weather_reading` FOREIGN KEY (`reading_id`) REFERENCES `gpsreadings` (`reading_id`) ON DELETE SET NULL ON UPDATE CASCADE;

COMMIT;