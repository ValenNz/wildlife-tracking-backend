-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Waktu pembuatan: 20 Okt 2026 pada 07.39
-- Versi server: 8.0.30
-- Versi PHP: 8.4.13

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Basis data: `wildlife_tracking`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `activity_log`
--

CREATE TABLE `activity_log` (
  `log_id` bigint UNSIGNED NOT NULL,
  `user_id` int UNSIGNED DEFAULT NULL,
  `action` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `timestamp` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `details` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `activity_log`
--

INSERT INTO `activity_log` (`log_id`, `user_id`, `action`, `timestamp`, `details`) VALUES
(1, 1, 'LOGIN', '2026-10-19 08:00:00', 'User login ke sistem'),
(2, 2, 'VIEW_GPS', '2026-10-19 09:30:00', 'Melihat data GPS SATWA-281'),
(3, 3, 'CREATE_REPORT', '2026-10-19 10:30:00', 'Membuat laporan insiden'),
(4, 1, 'VERIFY_PREDICTION', '2026-10-19 11:00:00', 'Memverifikasi prediksi AI'),
(5, 2, 'GENERATE_REPORT', '2026-10-19 12:00:00', 'Generate laporan mingguan');

-- --------------------------------------------------------

--
-- Struktur dari tabel `aggregate_reports`
--

CREATE TABLE `aggregate_reports` (
  `report_id` int UNSIGNED NOT NULL,
  `generated_by` int UNSIGNED DEFAULT NULL,
  `report_type` enum('daily','weekly','monthly') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `generated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `file_path` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `aggregate_reports`
--

INSERT INTO `aggregate_reports` (`report_id`, `generated_by`, `report_type`, `period_start`, `period_end`, `generated_at`, `file_path`) VALUES
(1, 1, 'daily', '2026-10-19', '2026-10-19', '2026-10-19 18:00:00', 'reports/daily_20261019.pdf'),
(2, 2, 'weekly', '2026-10-13', '2026-10-19', '2026-10-19 18:30:00', 'reports/weekly_20261019.pdf'),
(3, 1, 'monthly', '2026-10-01', '2026-10-31', '2026-10-19 19:00:00', 'reports/monthly_202610.pdf'),
(4, 3, 'daily', '2026-10-18', '2026-10-18', '2026-10-18 18:00:00', 'reports/daily_20261018.pdf'),
(5, 2, 'weekly', '2026-10-06', '2026-10-12', '2026-10-12 18:30:00', 'reports/weekly_20261012.pdf');

-- --------------------------------------------------------

--
-- Struktur dari tabel `ai_media_uploads`
--

CREATE TABLE `ai_media_uploads` (
  `media_id` bigint UNSIGNED NOT NULL,
  `uploaded_by` int UNSIGNED DEFAULT NULL,
  `report_id` int UNSIGNED DEFAULT NULL COMMENT 'opsional: laporan kejadian terkait',
  `media_type` enum('image','video') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_path` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `uploaded_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `ai_media_uploads`
--

INSERT INTO `ai_media_uploads` (`media_id`, `uploaded_by`, `report_id`, `media_type`, `file_path`, `uploaded_at`) VALUES
(1, 1, 1, 'image', 'uploads/media_001.jpg', '2026-10-19 10:30:00'),
(2, 2, 2, 'video', 'uploads/media_002.mp4', '2026-10-19 09:45:00'),
(3, 3, 3, 'image', 'uploads/media_003.jpg', '2026-10-19 11:20:00'),
(4, 1, 4, 'image', 'uploads/media_004.jpg', '2026-10-18 15:30:00'),
(5, 2, 5, 'video', 'uploads/media_005.mp4', '2026-10-15 09:00:00');

-- --------------------------------------------------------

--
-- Struktur dari tabel `ai_predictions`
--

CREATE TABLE `ai_predictions` (
  `prediction_id` bigint UNSIGNED NOT NULL,
  `media_id` bigint UNSIGNED NOT NULL,
  `predicted_species_id` int UNSIGNED DEFAULT NULL,
  `confidence` decimal(5,4) DEFAULT NULL COMMENT '0.0000 - 1.0000',
  `model_version` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `processing_status` enum('queued','processing','completed','failed') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'queued',
  `processed_at` datetime DEFAULT NULL,
  `verification_status` enum('pending','confirmed','rejected') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `verified_species_id` int UNSIGNED DEFAULT NULL COMMENT 'spesies benar menurut petugas',
  `verified_by` int UNSIGNED DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `ai_predictions`
--

INSERT INTO `ai_predictions` (`prediction_id`, `media_id`, `predicted_species_id`, `confidence`, `model_version`, `processing_status`, `processed_at`, `verification_status`, `verified_species_id`, `verified_by`, `verified_at`) VALUES
(1, 1, 1, 0.9829, 'YOLOv8-v1', 'completed', '2026-10-19 10:35:00', 'confirmed', 1, 2, '2026-10-19 11:00:00'),
(2, 2, 2, 0.9456, 'YOLOv8-v1', 'completed', '2026-10-19 09:50:00', 'confirmed', 2, 1, '2026-10-19 10:15:00'),
(3, 3, 3, 0.8734, 'YOLOv8-v1', 'completed', '2026-10-19 11:25:00', 'pending', NULL, NULL, NULL),
(4, 4, 4, 0.9123, 'YOLOv8-v1', 'completed', '2026-10-18 15:35:00', 'confirmed', 4, 2, '2026-10-18 16:00:00'),
(5, 5, 5, 0.7891, 'YOLOv8-v1', 'completed', '2026-10-15 09:10:00', 'rejected', 5, 1, '2026-10-15 10:30:00');

-- --------------------------------------------------------

--
-- Struktur dari tabel `animals`
--

CREATE TABLE `animals` (
  `animal_id` int UNSIGNED NOT NULL,
  `species_id` int UNSIGNED NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `birth_date` date DEFAULT NULL,
  `gender` enum('male','female','unknown') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unknown',
  `is_active` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `animals`
--

INSERT INTO `animals` (`animal_id`, `species_id`, `name`, `birth_date`, `gender`, `is_active`) VALUES
(1, 1, 'SATWA-742', '2023-08-04', 'male', 1),
(2, 2, 'SATWA-281', '2019-12-28', 'unknown', 1),
(3, 3, 'SATWA-803', '2019-11-21', 'female', 1),
(4, 4, 'SATWA-387', '2022-03-18', 'female', 1),
(5, 5, 'SATWA-968', '2014-02-14', 'female', 1);

-- --------------------------------------------------------

--
-- Struktur dari tabel `animal_geo_zones`
--

CREATE TABLE `animal_geo_zones` (
  `visit_id` bigint UNSIGNED NOT NULL,
  `animal_id` int UNSIGNED NOT NULL,
  `zone_id` int UNSIGNED NOT NULL,
  `entry_reading_id` bigint UNSIGNED DEFAULT NULL,
  `entered_at` datetime NOT NULL,
  `exited_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `animal_geo_zones`
--

INSERT INTO `animal_geo_zones` (`visit_id`, `animal_id`, `zone_id`, `entry_reading_id`, `entered_at`, `exited_at`) VALUES
(1, 1, 1, 1, '2026-10-19 08:00:00', '2026-10-19 12:00:00'),
(2, 2, 2, 2, '2026-10-19 09:00:00', '2026-10-19 14:00:00'),
(3, 3, 3, 3, '2026-10-19 10:00:00', NULL),
(4, 4, 4, 4, '2026-10-18 14:00:00', '2026-10-18 18:00:00'),
(5, 5, 5, 5, '2026-10-15 07:00:00', '2026-10-15 11:00:00');

-- --------------------------------------------------------

--
-- Struktur dari tabel `bookings`
--

CREATE TABLE `bookings` (
  `booking_id` bigint UNSIGNED NOT NULL,
  `booking_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'kode unik, mis. WLT-20261105-A7K2Q9',
  `visitor_id` int UNSIGNED NOT NULL,
  `visit_date` date NOT NULL,
  `total_amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT 'jumlah subtotal booking_items (IDR)',
  `booking_status` enum('pending','paid','cancelled','expired','completed') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `expires_at` datetime DEFAULT NULL COMMENT 'batas waktu pembayaran',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `bookings`
--

INSERT INTO `bookings` (`booking_id`, `booking_code`, `visitor_id`, `visit_date`, `total_amount`, `booking_status`, `expires_at`, `created_at`, `updated_at`) VALUES
(1, 'WLT-20261019-001', 1, '2026-10-25', 125000.00, 'paid', '2026-10-20 23:59:59', '2026-10-19 10:00:00', '2026-10-19 10:30:00'),
(2, 'WLT-20261019-002', 2, '2026-10-26', 50000.00, 'paid', '2026-10-20 23:59:59', '2026-10-19 10:05:00', '2026-10-19 10:35:00'),
(3, 'WLT-20261019-003', 3, '2026-10-27', 130000.00, 'pending', '2026-10-20 23:59:59', '2026-10-19 10:10:00', '2026-10-19 10:10:00'),
(4, 'WLT-20261019-004', 4, '2026-10-28', 200000.00, 'paid', '2026-10-20 23:59:59', '2026-10-19 10:15:00', '2026-10-19 10:45:00'),
(5, 'WLT-20261019-005', 5, '2026-10-29', 75000.00, 'cancelled', '2026-10-20 23:59:59', '2026-10-19 10:20:00', '2026-10-19 10:25:00');

--
-- Trigger `bookings`
--
DELIMITER $$
CREATE TRIGGER `trg_bookings_after_insert` AFTER INSERT ON `bookings` FOR EACH ROW BEGIN
  INSERT INTO `activity_log` (`user_id`, `action`, `details`)
  VALUES (NULL, 'TICKET_BOOKING_CREATED',
          CONCAT('Booking ', NEW.`booking_code`, ' untuk kunjungan ', NEW.`visit_date`,
                 ', total ', NEW.`total_amount`));
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Struktur dari tabel `booking_items`
--

CREATE TABLE `booking_items` (
  `item_id` bigint UNSIGNED NOT NULL,
  `booking_id` bigint UNSIGNED NOT NULL,
  `ticket_type_id` int UNSIGNED NOT NULL,
  `quantity` smallint UNSIGNED NOT NULL,
  `unit_price` decimal(12,2) NOT NULL COMMENT 'snapshot harga saat dipesan',
  `subtotal` decimal(12,2) GENERATED ALWAYS AS ((`quantity` * `unit_price`)) STORED
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `booking_items`
--

INSERT INTO `booking_items` (`item_id`, `booking_id`, `ticket_type_id`, `quantity`, `unit_price`) VALUES
(1, 1, 1, 2, 50000.00),
(2, 1, 2, 1, 25000.00),
(3, 2, 1, 1, 50000.00),
(4, 3, 1, 2, 50000.00),
(5, 3, 3, 1, 30000.00),
(6, 4, 5, 2, 100000.00),
(7, 5, 1, 1, 50000.00),
(8, 5, 2, 1, 25000.00);

-- --------------------------------------------------------

--
-- Struktur dari tabel `geo_zones`
--

CREATE TABLE `geo_zones` (
  `zone_id` int UNSIGNED NOT NULL,
  `zone_name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `zone_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'permukiman, perkebunan, jalan_raya, koridor_ekologis',
  `center_lat` decimal(9,6) DEFAULT NULL,
  `center_lon` decimal(10,6) DEFAULT NULL,
  `radius_km` decimal(8,3) DEFAULT NULL,
  `polygon_geojson` json DEFAULT NULL COMMENT 'GeoJSON Polygon WGS84',
  `created_by` int UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `geo_zones`
--

INSERT INTO `geo_zones` (`zone_id`, `zone_name`, `zone_type`, `center_lat`, `center_lon`, `radius_km`, `polygon_geojson`, `created_by`, `created_at`) VALUES
(1, 'Zona Konservasi Utara', 'koridor_ekologis', 3.671430, 97.878119, 2.313, NULL, 1, '2026-10-19 10:00:00'),
(2, 'Zona Perkebunan Tengah', 'perkebunan', 3.747409, 97.969478, 1.271, NULL, 2, '2026-10-19 10:00:00'),
(3, 'Zona Permukiman Selatan', 'permukiman', 3.674023, 97.433463, 1.363, NULL, 1, '2026-10-19 10:00:00'),
(4, 'Zona Jalan Raya Timur', 'jalan_raya', 3.918911, 97.535144, 1.514, NULL, 3, '2026-10-19 10:00:00'),
(5, 'Zona Hutan Lindung Barat', 'koridor_ekologis', 3.621963, 97.818243, 2.471, NULL, 2, '2026-10-19 10:00:00');

-- --------------------------------------------------------

--
-- Struktur dari tabel `gps_readings`
--

CREATE TABLE `gps_readings` (
  `reading_id` bigint UNSIGNED NOT NULL,
  `device_id` int UNSIGNED NOT NULL,
  `latitude` decimal(9,6) NOT NULL,
  `longitude` decimal(10,6) NOT NULL,
  `recorded_at` datetime NOT NULL,
  `altitude_meters` decimal(8,2) DEFAULT NULL,
  `accuracy_meters` decimal(6,2) DEFAULT NULL,
  `checksum` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'SHA-256 payload'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `gps_readings`
--

INSERT INTO `gps_readings` (`reading_id`, `device_id`, `latitude`, `longitude`, `recorded_at`, `altitude_meters`, `accuracy_meters`, `checksum`) VALUES
(1, 1, 3.658620, 97.731964, '2026-10-19 10:00:00', 489.16, 17.06, 'f468e4c385bf213285a191088c66bbe68652d3caa6f18f49dd80f77e36f6ba57'),
(2, 2, 3.967782, 97.381211, '2026-10-19 09:30:00', 379.69, 8.65, '4a33e9b80e758d0ee44c4cbbf53ea104f2485dda61ba0747b173c573f537b0f9'),
(3, 3, 3.730805, 97.613118, '2026-10-19 11:15:00', 517.81, 16.03, 'fdad1e5a093cfaa2f8d4f1db9be8ed9646c7e426f8d303753dc0ff995d027b9a'),
(4, 4, 3.756809, 97.450071, '2026-10-18 15:20:00', 205.69, 17.35, '3f25373bcb50467e3b29408a5d03eb790ab900e892a06bea001b69cef7936db7'),
(5, 5, 3.899724, 97.045250, '2026-10-15 08:45:00', 125.96, 13.73, '633b3eb374f92b1e3b49f2bbdd30a30601bc8bce1353f2e7f46d46af8c9232f8');

--
-- Trigger `gps_readings`
--
DELIMITER $$
CREATE TRIGGER `trg_gps_after_insert` AFTER INSERT ON `gps_readings` FOR EACH ROW UPDATE `tracking_devices`
SET `last_recorded_at` = GREATEST(IFNULL(`last_recorded_at`, NEW.`recorded_at`), NEW.`recorded_at`)
WHERE `device_id` = NEW.`device_id`
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Struktur dari tabel `incident_reports`
--

CREATE TABLE `incident_reports` (
  `report_id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED DEFAULT NULL COMMENT 'pelapor',
  `incident_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'konflik, perambahan, observasi',
  `animal_code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `location` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `timestamp` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `photo_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sync_status` enum('pending_sync','synced','failed') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending_sync'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `incident_reports`
--

INSERT INTO `incident_reports` (`report_id`, `user_id`, `incident_type`, `animal_code`, `location`, `description`, `timestamp`, `photo_url`, `sync_status`) VALUES
(1, 1, 'observasi', 'SATWA-742', 'Zona Konservasi Utara', 'Satwa terlihat sehat, bergerak normal', '2026-10-19 10:30:00', 'uploads/img_001.jpg', 'synced'),
(2, 2, 'konflik', 'SATWA-281', 'Zona Perkebunan Tengah', 'Satwa masuk area perkebunan warga', '2026-10-19 09:45:00', 'uploads/img_002.jpg', 'synced'),
(3, 3, 'device_alert', 'SATWA-803', 'Zona Permukiman Selatan', 'Perangkat GPS sinyal lemah', '2026-10-19 11:20:00', NULL, 'pending_sync'),
(4, 1, 'observasi', 'SATWA-387', 'Zona Jalan Raya Timur', 'Satwa melintasi jalan raya', '2026-10-18 15:30:00', 'uploads/img_003.jpg', 'synced'),
(5, 2, 'perambahan', 'SATWA-968', 'Zona Hutan Lindung Barat', 'Ditemukan aktivitas ilegal di zona lindung', '2026-10-15 09:00:00', 'uploads/img_004.jpg', 'failed');

-- --------------------------------------------------------

--
-- Struktur dari tabel `notifications`
--

CREATE TABLE `notifications` (
  `notif_id` bigint UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `notification_type` enum('risk_zone','device_alert','system') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `animal_id` int UNSIGNED DEFAULT NULL,
  `zone_id` int UNSIGNED DEFAULT NULL,
  `device_id` int UNSIGNED DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `notifications`
--

INSERT INTO `notifications` (`notif_id`, `user_id`, `notification_type`, `message`, `animal_id`, `zone_id`, `device_id`, `is_read`, `created_at`) VALUES
(1, 1, 'risk_zone', 'SATWA-281 memasuki zona perkebunan', 2, 2, 2, 0, '2026-10-19 09:45:00'),
(2, 2, 'device_alert', 'Baterai perangkat SATWA-387 lemah', 3, 3, 3, 1, '2026-10-19 11:20:00'),
(3, 3, 'system', 'Laporan harian siap diunduh', NULL, NULL, NULL, 1, '2026-10-19 08:00:00'),
(4, 1, 'risk_zone', 'SATWA-968 terdeteksi di zona lindung', 5, 5, 5, 0, '2026-10-15 09:00:00'),
(5, 2, 'device_alert', 'Perangkat SATWA-387 kehilangan sinyal', 3, 3, 3, 0, '2026-10-19 11:25:00');

-- --------------------------------------------------------

--
-- Struktur dari tabel `payments`
--

CREATE TABLE `payments` (
  `payment_id` bigint UNSIGNED NOT NULL,
  `booking_id` bigint UNSIGNED NOT NULL,
  `payment_method` enum('transfer_bank','qris','e_wallet','kartu_kredit','tunai') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `payment_status` enum('pending','success','failed','expired','refunded') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `transaction_ref` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'ID transaksi dari bank/gateway',
  `paid_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `is_success` tinyint GENERATED ALWAYS AS (if((`payment_status` = _utf8mb4'success'),1,NULL)) VIRTUAL COMMENT 'penanda pembayaran sukses'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `payments`
--

INSERT INTO `payments` (`payment_id`, `booking_id`, `payment_method`, `amount`, `payment_status`, `transaction_ref`, `paid_at`, `created_at`) VALUES
(1, 1, 'qris', 125000.00, 'success', 'QRIS-20261019-001', '2026-10-19 10:30:00', '2026-10-19 10:30:00'),
(2, 2, 'transfer_bank', 50000.00, 'success', 'TRF-20261019-002', '2026-10-19 10:35:00', '2026-10-19 10:35:00'),
(3, 3, 'e_wallet', 130000.00, 'pending', NULL, NULL, '2026-10-19 10:40:00'),
(4, 4, 'kartu_kredit', 200000.00, 'success', 'CC-20261019-004', '2026-10-19 10:45:00', '2026-10-19 10:45:00'),
(5, 5, 'qris', 75000.00, 'expired', 'QRIS-20261019-005', NULL, '2026-10-19 10:50:00');

--
-- Trigger `payments`
--
DELIMITER $$
CREATE TRIGGER `trg_payments_after_insert` AFTER INSERT ON `payments` FOR EACH ROW BEGIN
  IF NEW.`payment_status` = 'success' THEN
    UPDATE `bookings` SET `booking_status` = 'paid'
    WHERE `booking_id` = NEW.`booking_id` AND `booking_status` = 'pending';
    
    INSERT INTO `activity_log` (`user_id`, `action`, `details`)
    SELECT NULL, 'TICKET_PAYMENT_SUCCESS',
           CONCAT('Booking ', b.`booking_code`, ' dibayar via ', NEW.`payment_method`,
                  ', nominal ', NEW.`amount`, ', ref ', IFNULL(NEW.`transaction_ref`, '-'))
    FROM `bookings` b WHERE b.`booking_id` = NEW.`booking_id`;
  END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_payments_after_update` AFTER UPDATE ON `payments` FOR EACH ROW BEGIN
  IF NEW.`payment_status` = 'success' AND OLD.`payment_status` <> 'success' THEN
    UPDATE `bookings` SET `booking_status` = 'paid'
    WHERE `booking_id` = NEW.`booking_id` AND `booking_status` = 'pending';
    
    INSERT INTO `activity_log` (`user_id`, `action`, `details`)
    SELECT NULL, 'TICKET_PAYMENT_SUCCESS',
           CONCAT('Booking ', b.`booking_code`, ' dibayar via ', NEW.`payment_method`,
                  ', nominal ', NEW.`amount`, ', ref ', IFNULL(NEW.`transaction_ref`, '-'))
    FROM `bookings` b WHERE b.`booking_id` = NEW.`booking_id`;
  END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_payments_before_insert` BEFORE INSERT ON `payments` FOR EACH ROW BEGIN
  DECLARE v_total decimal(12,2);
  DECLARE v_status varchar(20);
  SELECT `total_amount`, `booking_status` INTO v_total, v_status
  FROM `bookings` WHERE `booking_id` = NEW.`booking_id`;
  
  IF NEW.`amount` <> v_total THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Nominal pembayaran harus sama dengan total pemesanan';
  END IF;
  IF NEW.`payment_status` = 'success' AND v_status <> 'pending' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Pembayaran sukses hanya boleh untuk pemesanan berstatus pending';
  END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_payments_before_update` BEFORE UPDATE ON `payments` FOR EACH ROW BEGIN
  DECLARE v_total decimal(12,2);
  DECLARE v_status varchar(20);
  SELECT `total_amount`, `booking_status` INTO v_total, v_status
  FROM `bookings` WHERE `booking_id` = NEW.`booking_id`;
  
  IF (NEW.`amount` <> OLD.`amount` OR NEW.`booking_id` <> OLD.`booking_id`)
     AND NEW.`amount` <> v_total THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Nominal pembayaran harus sama dengan total pemesanan';
  END IF;
  IF NEW.`payment_status` = 'success' AND OLD.`payment_status` <> 'success'
     AND v_status <> 'pending' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Pembayaran sukses hanya boleh untuk pemesanan berstatus pending';
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Struktur dari tabel `permissions`
--

CREATE TABLE `permissions` (
  `permission_id` int UNSIGNED NOT NULL,
  `permission_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `permissions`
--

INSERT INTO `permissions` (`permission_id`, `permission_name`, `description`, `created_at`) VALUES
(1, 'view_data', 'Melihat data satwa, pelacakan, dan konservasi', '2026-10-19 10:00:00'),
(2, 'create_data', 'Menambahkan data baru', '2026-10-19 10:00:00'),
(3, 'update_data', 'Mengubah data yang sudah ada', '2026-10-19 10:00:00'),
(4, 'delete_data', 'Menghapus data', '2026-10-19 10:00:00'),
(5, 'manage_users', 'Mengelola akun pengguna', '2026-10-19 10:00:00'),
(6, 'manage_roles', 'Mengelola role dan permission', '2026-10-19 10:00:00'),
(7, 'view_reports', 'Melihat laporan dan ringkasan', '2026-10-19 10:00:00');

-- --------------------------------------------------------

--
-- Struktur dari tabel `roles`
--

CREATE TABLE `roles` (
  `role_id` int UNSIGNED NOT NULL,
  `role_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `roles`
--

INSERT INTO `roles` (`role_id`, `role_name`, `description`, `created_at`) VALUES
(1, 'konservasionis_lapangan', 'Petugas konservasi di lapangan', '2026-10-19 10:00:00'),
(2, 'peneliti_ekologi', 'Peneliti yang menganalisis data ekologi dan satwa', '2026-10-19 10:00:00'),
(3, 'administrator_sistem', 'Administrator yang mengelola sistem dan pengguna', '2026-10-19 10:00:00'),
(4, 'pengambil_kebijakan', 'Pengambil kebijakan yang memantau laporan dan ringkasan', '2026-10-19 10:00:00'),
(5, 'visitor', 'Pengunjung umum dengan akses terbatas', '2026-10-19 10:00:00');

-- --------------------------------------------------------

--
-- Struktur dari tabel `role_permissions`
--

CREATE TABLE `role_permissions` (
  `role_id` int UNSIGNED NOT NULL,
  `permission_id` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `role_permissions`
--

INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(1, 1),
(2, 1),
(3, 1),
(4, 1),
(5, 1),
(1, 2),
(2, 2),
(3, 2),
(1, 3),
(2, 3),
(3, 3),
(3, 4),
(3, 5),
(3, 6),
(2, 7),
(3, 7),
(4, 7);

-- --------------------------------------------------------

--
-- Struktur dari tabel `species`
--

CREATE TABLE `species` (
  `species_id` int UNSIGNED NOT NULL,
  `common_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `scientific_name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `conservation_status` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `species`
--

INSERT INTO `species` (`species_id`, `common_name`, `scientific_name`, `conservation_status`) VALUES
(1, 'Orangutan Sumatra', 'Pongo abelii', 'Critically Endangered'),
(2, 'Harimau Sumatra', 'Panthera tigris sumatrae', 'Critically Endangered'),
(3, 'Badak Sumatra', 'Dicerorhinus sumatrensis', 'Critically Endangered'),
(4, 'Gajah Sumatra', 'Elephas maximus sumatranus', 'Critically Endangered'),
(5, 'Beruang Madu', 'Helarctos malayanus', 'Vulnerable');

-- --------------------------------------------------------

--
-- Struktur dari tabel `ticket_types`
--

CREATE TABLE `ticket_types` (
  `ticket_type_id` int UNSIGNED NOT NULL,
  `type_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `price` decimal(12,2) NOT NULL COMMENT 'harga per tiket (IDR)',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `ticket_types`
--

INSERT INTO `ticket_types` (`ticket_type_id`, `type_name`, `description`, `price`, `is_active`, `created_at`) VALUES
(1, 'Dewasa', 'Tiket masuk untuk usia 13 tahun ke atas', 50000.00, 1, '2026-10-19 10:00:00'),
(2, 'Anak-anak', 'Tiket masuk untuk usia 3-12 tahun', 25000.00, 1, '2026-10-19 10:00:00'),
(3, 'Lansia', 'Tiket masuk untuk usia di atas 60 tahun', 30000.00, 1, '2026-10-19 10:00:00'),
(4, 'Mahasiswa', 'Tiket masuk dengan menunjukkan KTM aktif', 35000.00, 1, '2026-10-19 10:00:00'),
(5, 'Turis Asing', 'Tiket masuk untuk wisatawan mancanegara', 100000.00, 1, '2026-10-19 10:00:00');

-- --------------------------------------------------------

--
-- Struktur dari tabel `tracking_devices`
--

CREATE TABLE `tracking_devices` (
  `device_id` int UNSIGNED NOT NULL,
  `animal_id` int UNSIGNED DEFAULT NULL,
  `model` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `installation_date` date DEFAULT NULL,
  `battery_life_months` smallint UNSIGNED DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `device_condition` enum('normal','damaged','lost') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normal',
  `battery_level` tinyint UNSIGNED DEFAULT NULL COMMENT 'persen 0-100',
  `signal_strength` tinyint UNSIGNED DEFAULT NULL,
  `last_recorded_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `tracking_devices`
--

INSERT INTO `tracking_devices` (`device_id`, `animal_id`, `model`, `installation_date`, `battery_life_months`, `is_active`, `device_condition`, `battery_level`, `signal_strength`, `last_recorded_at`) VALUES
(1, 1, 'Lotek Iridium TrackLite', '2026-01-21', 12, 1, 'normal', 87, 4, '2026-10-19 10:00:00'),
(2, 2, 'Lotek GPS-Plus', '2026-08-22', 12, 1, 'normal', 53, 3, '2026-10-19 09:30:00'),
(3, 3, 'Televilt Telemetry', '2026-02-12', 12, 1, 'normal', 98, 4, '2026-10-19 11:15:00'),
(4, 4, 'Lotek GPS-Plus', '2026-04-07', 12, 1, 'damaged', 25, 1, '2026-10-18 15:20:00'),
(5, 5, 'Lotek GPS-Plus', '2026-07-18', 12, 1, 'lost', 0, 0, '2026-10-15 08:45:00');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `user_id` int UNSIGNED NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'hash bcrypt/argon2',
  `role` enum('konservasionis_lapangan','peneliti_ekologi','administrator_sistem','pengambil_kebijakan') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`user_id`, `name`, `email`, `password`, `role`, `created_at`) VALUES
(1, 'Ir. Drajat Mustofa, S.Kom', 'user1@wildlife.id', '$2y$12$dummyhashpassword', 'administrator_sistem', '2026-10-19 14:43:26'),
(2, 'Adiarja Haryanto', 'user2@wildlife.id', '$2y$12$dummyhashpassword', 'peneliti_ekologi', '2026-10-19 14:43:26'),
(3, 'Mutia Purnawati', 'user3@wildlife.id', '$2y$12$dummyhashpassword', 'konservasionis_lapangan', '2026-10-19 14:43:26'),
(4, 'Tgk. Baktianto Pratama, S.Kom', 'user4@wildlife.id', '$2y$12$dummyhashpassword', 'pengambil_kebijakan', '2026-10-19 14:43:26'),
(5, 'Asmadi Waskita', 'user5@wildlife.id', '$2y$12$dummyhashpassword', 'peneliti_ekologi', '2026-10-19 14:43:26');

-- --------------------------------------------------------

--
-- Struktur dari tabel `visitors`
--

CREATE TABLE `visitors` (
  `visitor_id` int UNSIGNED NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `visitors`
--

INSERT INTO `visitors` (`visitor_id`, `name`, `email`, `phone`, `created_at`) VALUES
(1, 'Budi Santoso', 'budi.santoso@gmail.com', '081234567890', '2026-10-19 10:00:00'),
(2, 'Siti Aminah', 'siti.aminah@yahoo.com', '081298765432', '2026-10-19 10:05:00'),
(3, 'Rizky Pratama', 'rizky.p@outlook.com', '081311223344', '2026-10-19 10:10:00'),
(4, 'Dewi Lestari', 'dewi.lestari@gmail.com', '085677889900', '2026-10-19 10:15:00'),
(5, 'John Doe', 'john.doe@tourist.com', '081200001111', '2026-10-19 10:20:00');

-- --------------------------------------------------------

--
-- Struktur dari tabel `weather_data`
--

CREATE TABLE `weather_data` (
  `weather_id` bigint UNSIGNED NOT NULL,
  `reading_id` bigint UNSIGNED DEFAULT NULL,
  `latitude` decimal(9,6) NOT NULL,
  `longitude` decimal(10,6) NOT NULL,
  `observed_at` datetime NOT NULL,
  `temperature_c` decimal(5,2) DEFAULT NULL,
  `rainfall_mm` decimal(7,2) DEFAULT NULL,
  `humidity_percent` decimal(5,2) DEFAULT NULL,
  `pressure_hpa` decimal(7,2) DEFAULT NULL,
  `source` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'WeatherAPI.com'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `weather_data`
--

INSERT INTO `weather_data` (`weather_id`, `reading_id`, `latitude`, `longitude`, `observed_at`, `temperature_c`, `rainfall_mm`, `humidity_percent`, `pressure_hpa`, `source`) VALUES
(1, 1, 3.658620, 97.731964, '2026-10-19 10:00:00', 28.50, 0.00, 75.00, 1012.00, 'Open-Meteo'),
(2, 2, 3.967782, 97.381211, '2026-10-19 09:30:00', 27.80, 2.50, 82.00, 1011.50, 'Open-Meteo'),
(3, 3, 3.730805, 97.613118, '2026-10-19 11:15:00', 29.20, 0.00, 70.00, 1013.00, 'Open-Meteo'),
(4, 4, 3.756809, 97.450071, '2026-10-18 15:20:00', 26.90, 5.80, 88.00, 1010.80, 'Open-Meteo'),
(5, 5, 3.899724, 97.045250, '2026-10-15 08:45:00', 25.50, 12.30, 92.00, 1009.50, 'Open-Meteo');

--
-- Indeks untuk tabel yang dibuang
--

--
-- Indeks untuk tabel `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `idx_log_user_time` (`user_id`,`timestamp`),
  ADD KEY `idx_log_time` (`timestamp`);

--
-- Indeks untuk tabel `aggregate_reports`
--
ALTER TABLE `aggregate_reports`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `idx_ar_period` (`report_type`,`period_start`,`period_end`),
  ADD KEY `idx_ar_user` (`generated_by`);

--
-- Indeks untuk tabel `ai_media_uploads`
--
ALTER TABLE `ai_media_uploads`
  ADD PRIMARY KEY (`media_id`),
  ADD KEY `idx_media_user` (`uploaded_by`),
  ADD KEY `idx_media_report` (`report_id`),
  ADD KEY `idx_media_time` (`uploaded_at`);

--
-- Indeks untuk tabel `ai_predictions`
--
ALTER TABLE `ai_predictions`
  ADD PRIMARY KEY (`prediction_id`),
  ADD UNIQUE KEY `uq_pred_media_model` (`media_id`,`model_version`),
  ADD KEY `idx_pred_species` (`predicted_species_id`),
  ADD KEY `idx_pred_verif` (`verification_status`),
  ADD KEY `idx_pred_verified_species` (`verified_species_id`),
  ADD KEY `idx_pred_verified_by` (`verified_by`);

--
-- Indeks untuk tabel `animals`
--
ALTER TABLE `animals`
  ADD PRIMARY KEY (`animal_id`),
  ADD KEY `idx_animals_species` (`species_id`),
  ADD KEY `idx_animals_is_active` (`is_active`);

--
-- Indeks untuk tabel `animal_geo_zones`
--
ALTER TABLE `animal_geo_zones`
  ADD PRIMARY KEY (`visit_id`),
  ADD KEY `idx_ag_animal_time` (`animal_id`,`entered_at`),
  ADD KEY `idx_ag_zone_time` (`zone_id`,`entered_at`),
  ADD KEY `idx_ag_entry_reading` (`entry_reading_id`);

--
-- Indeks untuk tabel `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`booking_id`),
  ADD UNIQUE KEY `uq_bookings_code` (`booking_code`),
  ADD KEY `idx_bookings_visitor_time` (`visitor_id`,`created_at`),
  ADD KEY `idx_bookings_visit` (`visit_date`,`booking_status`);

--
-- Indeks untuk tabel `booking_items`
--
ALTER TABLE `booking_items`
  ADD PRIMARY KEY (`item_id`),
  ADD UNIQUE KEY `uq_bi_booking_type` (`booking_id`,`ticket_type_id`),
  ADD KEY `idx_bi_type` (`ticket_type_id`);

--
-- Indeks untuk tabel `geo_zones`
--
ALTER TABLE `geo_zones`
  ADD PRIMARY KEY (`zone_id`),
  ADD KEY `idx_zones_type` (`zone_type`),
  ADD KEY `idx_zones_created_by` (`created_by`);

--
-- Indeks untuk tabel `gps_readings`
--
ALTER TABLE `gps_readings`
  ADD PRIMARY KEY (`reading_id`),
  ADD UNIQUE KEY `uq_gps_device_time` (`device_id`,`recorded_at`),
  ADD KEY `idx_gps_recorded_at` (`recorded_at`);

--
-- Indeks untuk tabel `incident_reports`
--
ALTER TABLE `incident_reports`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `idx_ir_user` (`user_id`),
  ADD KEY `idx_ir_sync` (`sync_status`),
  ADD KEY `idx_ir_time` (`timestamp`);

--
-- Indeks untuk tabel `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notif_id`),
  ADD KEY `idx_notif_user_unread` (`user_id`,`is_read`,`created_at`),
  ADD KEY `idx_notif_animal` (`animal_id`),
  ADD KEY `idx_notif_zone` (`zone_id`),
  ADD KEY `idx_notif_device` (`device_id`);

--
-- Indeks untuk tabel `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD UNIQUE KEY `uq_pay_method_ref` (`payment_method`,`transaction_ref`),
  ADD UNIQUE KEY `uq_pay_one_success` (`booking_id`,`is_success`),
  ADD KEY `idx_pay_status_time` (`payment_status`,`created_at`);

--
-- Indeks untuk tabel `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`permission_id`),
  ADD UNIQUE KEY `uq_permissions_name` (`permission_name`);

--
-- Indeks untuk tabel `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`role_id`),
  ADD UNIQUE KEY `uq_roles_name` (`role_name`);

--
-- Indeks untuk tabel `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`role_id`,`permission_id`),
  ADD KEY `idx_rp_permission` (`permission_id`);

--
-- Indeks untuk tabel `species`
--
ALTER TABLE `species`
  ADD PRIMARY KEY (`species_id`),
  ADD UNIQUE KEY `uq_species_scientific_name` (`scientific_name`);

--
-- Indeks untuk tabel `ticket_types`
--
ALTER TABLE `ticket_types`
  ADD PRIMARY KEY (`ticket_type_id`),
  ADD UNIQUE KEY `uq_tickettypes_name` (`type_name`);

--
-- Indeks untuk tabel `tracking_devices`
--
ALTER TABLE `tracking_devices`
  ADD PRIMARY KEY (`device_id`),
  ADD UNIQUE KEY `uq_devices_animal` (`animal_id`),
  ADD KEY `idx_devices_monitor` (`is_active`,`last_recorded_at`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `uq_users_email` (`email`),
  ADD KEY `idx_users_role` (`role`);

--
-- Indeks untuk tabel `visitors`
--
ALTER TABLE `visitors`
  ADD PRIMARY KEY (`visitor_id`),
  ADD UNIQUE KEY `uq_visitors_email` (`email`);

--
-- Indeks untuk tabel `weather_data`
--
ALTER TABLE `weather_data`
  ADD PRIMARY KEY (`weather_id`),
  ADD KEY `idx_weather_time` (`observed_at`),
  ADD KEY `idx_weather_reading` (`reading_id`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `log_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `aggregate_reports`
--
ALTER TABLE `aggregate_reports`
  MODIFY `report_id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `ai_media_uploads`
--
ALTER TABLE `ai_media_uploads`
  MODIFY `media_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `ai_predictions`
--
ALTER TABLE `ai_predictions`
  MODIFY `prediction_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `animals`
--
ALTER TABLE `animals`
  MODIFY `animal_id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `animal_geo_zones`
--
ALTER TABLE `animal_geo_zones`
  MODIFY `visit_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `bookings`
--
ALTER TABLE `bookings`
  MODIFY `booking_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `booking_items`
--
ALTER TABLE `booking_items`
  MODIFY `item_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT untuk tabel `geo_zones`
--
ALTER TABLE `geo_zones`
  MODIFY `zone_id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `gps_readings`
--
ALTER TABLE `gps_readings`
  MODIFY `reading_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `incident_reports`
--
ALTER TABLE `incident_reports`
  MODIFY `report_id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notif_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `permissions`
--
ALTER TABLE `permissions`
  MODIFY `permission_id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT untuk tabel `roles`
--
ALTER TABLE `roles`
  MODIFY `role_id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `species`
--
ALTER TABLE `species`
  MODIFY `species_id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `ticket_types`
--
ALTER TABLE `ticket_types`
  MODIFY `ticket_type_id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `tracking_devices`
--
ALTER TABLE `tracking_devices`
  MODIFY `device_id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `visitors`
--
ALTER TABLE `visitors`
  MODIFY `visitor_id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `weather_data`
--
ALTER TABLE `weather_data`
  MODIFY `weather_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `activity_log`
--
ALTER TABLE `activity_log`
  ADD CONSTRAINT `fk_log_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `aggregate_reports`
--
ALTER TABLE `aggregate_reports`
  ADD CONSTRAINT `fk_ar_user` FOREIGN KEY (`generated_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `ai_media_uploads`
--
ALTER TABLE `ai_media_uploads`
  ADD CONSTRAINT `fk_media_report` FOREIGN KEY (`report_id`) REFERENCES `incident_reports` (`report_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_media_user` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `ai_predictions`
--
ALTER TABLE `ai_predictions`
  ADD CONSTRAINT `fk_pred_media` FOREIGN KEY (`media_id`) REFERENCES `ai_media_uploads` (`media_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pred_species` FOREIGN KEY (`predicted_species_id`) REFERENCES `species` (`species_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pred_verified_by` FOREIGN KEY (`verified_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pred_verified_species` FOREIGN KEY (`verified_species_id`) REFERENCES `species` (`species_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `animals`
--
ALTER TABLE `animals`
  ADD CONSTRAINT `fk_animals_species` FOREIGN KEY (`species_id`) REFERENCES `species` (`species_id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `animal_geo_zones`
--
ALTER TABLE `animal_geo_zones`
  ADD CONSTRAINT `fk_ag_animal` FOREIGN KEY (`animal_id`) REFERENCES `animals` (`animal_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ag_reading` FOREIGN KEY (`entry_reading_id`) REFERENCES `gps_readings` (`reading_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ag_zone` FOREIGN KEY (`zone_id`) REFERENCES `geo_zones` (`zone_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `fk_booking_visitor` FOREIGN KEY (`visitor_id`) REFERENCES `visitors` (`visitor_id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `booking_items`
--
ALTER TABLE `booking_items`
  ADD CONSTRAINT `fk_bi_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_bi_type` FOREIGN KEY (`ticket_type_id`) REFERENCES `ticket_types` (`ticket_type_id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `geo_zones`
--
ALTER TABLE `geo_zones`
  ADD CONSTRAINT `fk_zones_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `gps_readings`
--
ALTER TABLE `gps_readings`
  ADD CONSTRAINT `fk_gps_device` FOREIGN KEY (`device_id`) REFERENCES `tracking_devices` (`device_id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `incident_reports`
--
ALTER TABLE `incident_reports`
  ADD CONSTRAINT `fk_ir_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notif_animal` FOREIGN KEY (`animal_id`) REFERENCES `animals` (`animal_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_notif_device` FOREIGN KEY (`device_id`) REFERENCES `tracking_devices` (`device_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_notif_zone` FOREIGN KEY (`zone_id`) REFERENCES `geo_zones` (`zone_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_pay_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `fk_rp_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`permission_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rp_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `tracking_devices`
--
ALTER TABLE `tracking_devices`
  ADD CONSTRAINT `fk_devices_animal` FOREIGN KEY (`animal_id`) REFERENCES `animals` (`animal_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `weather_data`
--
ALTER TABLE `weather_data`
  ADD CONSTRAINT `fk_weather_reading` FOREIGN KEY (`reading_id`) REFERENCES `gps_readings` (`reading_id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
