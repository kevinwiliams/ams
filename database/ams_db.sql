/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

CREATE DATABASE IF NOT EXISTS `ams` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */;
USE `ams`;

CREATE TABLE IF NOT EXISTS `assignment_list` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `drop_option` varchar(20) DEFAULT NULL,
  `team_members` text DEFAULT NULL,
  `assigned_by` varchar(255) DEFAULT NULL,
  `assignment_date` date DEFAULT NULL,
  `start_time` varchar(10) DEFAULT NULL,
  `is_prev_date` bit(1) DEFAULT NULL,
  `end_time` varchar(10) DEFAULT NULL,
  `depart_time` varchar(10) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `date_created` datetime DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `edited_by` int(11) DEFAULT NULL,
  `approval_date` datetime DEFAULT NULL,
  `is_deleted` bit(1) DEFAULT NULL,
  `transport_id` int(11) DEFAULT NULL,
  `transport_confirmed` bit(1) DEFAULT NULL,
  `deleted_by` int(11) DEFAULT NULL,
  `equipment_requested` bit(1) DEFAULT NULL,
  `equipment` varchar(200) DEFAULT NULL,
  `photo_requested` bit(1) DEFAULT NULL,
  `video_requested` bit(1) DEFAULT NULL,
  `social_requested` bit(1) DEFAULT NULL,
  `driver_requested` bit(1) DEFAULT NULL,
  `send_notification` bit(1) DEFAULT NULL,
  `uid` varchar(255) DEFAULT NULL,
  `is_cancelled` bit(1) DEFAULT b'0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uid` (`uid`)
) ENGINE=InnoDB AUTO_INCREMENT=71 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `confirmed_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `assignment_id` int(11) NOT NULL DEFAULT 0,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `empid` varchar(10) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=52 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `roles` (
  `role_id` int(11) NOT NULL AUTO_INCREMENT,
  `role_name` varchar(50) NOT NULL,
  PRIMARY KEY (`role_id`),
  UNIQUE KEY `role_name` (`role_name`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `sms_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `recipient` varchar(20) NOT NULL,
  `text` text NOT NULL,
  `status` enum('pending','sent','failed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=185 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `system_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(255) NOT NULL,
  `setting_value` text NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `transport_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `assignment_id` int(11) NOT NULL,
  `transport_id` int(11) NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `mileage` int(11) DEFAULT NULL,
  `gas_level` varchar(20) DEFAULT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `transport_vehicles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `plate_number` varchar(10) NOT NULL,
  `make_model` varchar(100) NOT NULL,
  `is_deleted` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `empid` varchar(20) NOT NULL,
  `firstname` varchar(50) NOT NULL,
  `lastname` varchar(50) NOT NULL,
  `address` text DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `reset_token` text DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL,
  `role_id` int(11) DEFAULT NULL,
  `preferred_channel` varchar(100) DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `empid` (`empid`),
  UNIQUE KEY `email` (`email`),
  KEY `fk_role` (`role_id`),
  CONSTRAINT `fk_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`)
) ENGINE=InnoDB AUTO_INCREMENT=180 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;

ALTER TABLE assignment_list
ADD COLUMN is_ob TINYINT(1) DEFAULT 0,
ADD COLUMN dj_requested BIT(1) DEFAULT 0,
ADD COLUMN show_id INT(10) DEFAULT NULL,
ADD COLUMN station_show VARCHAR(100) DEFAULT NULL;

ALTER TABLE users
ADD COLUMN sb_staff TINYINT(1) DEFAULT 0;

CREATE TABLE IF NOT EXISTS `station_shows` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `show_name` varchar(100) NOT NULL,
  `station` enum('EDGE','FYAH') NOT NULL,
  `is_exclusive` bit(1) NOT NULL DEFAULT b'0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE assignment_list
CHANGE COLUMN is_ob request_permit TINYINT(1) DEFAULT 0;

CREATE TABLE `ob_items` (
  `item_id` INT AUTO_INCREMENT PRIMARY KEY,
  `item_name` VARCHAR(50) NOT NULL,
  `description` TEXT
);
  
INSERT INTO `ob_items` (`item_name`, `description`) VALUES
('Speakers (LG)', 'Large speakers for broadcasting'),
('Microphone', 'Audio input device for broadcasting'),
('In-ear Monitor', 'Personal monitoring system for broadcasters'),
('Speakers (SM)', 'Small speakers for broadcasting'),
('Digital Mixer', 'Audio mixing console for broadcast equipment'),
('Headphones (Amp)', 'Amplified headphones for monitoring'),
('Headphones', 'Standard headphones for monitoring'),
('XLR Cables', 'Professional audio cables for connecting equipment'),
('Modem', 'Network device for broadcasting connectivity'),
('Tent', 'Event tent for outdoor activities'),
('Trestle Tables', 'Foldable tables for event setups'),
('Chairs', 'Portable chairs for seating arrangements'),
('Feather Banners', 'Tall, feather-shaped promotional banners'),
('Pull Up Banners', 'Retractable stand-up banners for displays'),
('Giveaways', 'Promotional items for event attendees'),
('Staff Bands', 'Identification bands for event staff');

CREATE TABLE `ob_inventory` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `assignment_id` INT NOT NULL,
  `item_id` INT NOT NULL,
  `status` TINYINT(1) DEFAULT 0,
  `quantity` INT DEFAULT 0,
  `notes` TEXT,
  FOREIGN KEY (`assignment_id`) REFERENCES `assignment_list`(`id`),
  FOREIGN KEY (`item_id`) REFERENCES `ob_items`(`item_id`)
);

-- Main inspection table
CREATE TABLE `venue_inspections` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `assignment_id` INT NOT NULL,
  `parking_available` TINYINT(1) DEFAULT 0,
  `bathrooms_available` TINYINT(1) DEFAULT 0,
  `layout_notes` TEXT,
  `tent_location` TEXT,
  `banner_location` TEXT,
  `general_notes` TEXT,
  `site_visit_date` DATE,
  `setup_time` VARCHAR(10),
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`assignment_id`) REFERENCES `assignment_list`(`id`) ON DELETE CASCADE
);

-- Permits table (supports multiple permits per inspection)
CREATE TABLE `venue_permits` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `inspection_id` INT NOT NULL,
  `permit_type` ENUM('temporary', 'full', 'other') NOT NULL,
  `notes` TEXT,
  FOREIGN KEY (`inspection_id`) REFERENCES `venue_inspections`(`id`) ON DELETE CASCADE
);

ALTER TABLE venue_inspections
ADD COLUMN updated_at DATETIME;

ALTER TABLE ob_inventory
ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN updated_at DATETIME;

ALTER TABLE `venue_inspections`
ADD COLUMN `updated_by` INT(10) NULL;

ALTER TABLE `venue_permits`
MODIFY COLUMN `permit_type` VARCHAR(20) NOT NULL;

ALTER TABLE venue_inspections
ADD COLUMN items_requested TINYINT(1) DEFAULT 0;

ALTER TABLE assignment_list
ADD COLUMN contact_information TEXT DEFAULT NULL;

ALTER TABLE assignment_list
ADD COLUMN is_exclusive TINYINT(1) DEFAULT 0,
ADD COLUMN toll_required TINYINT(1) DEFAULT 0;

ALTER TABLE venue_inspections
ADD COLUMN toll_required TINYINT(1) DEFAULT 0,
ADD COLUMN report_status VARCHAR(20) DEFAULT NULL,
ADD COLUMN nearest_power_source int(10) DEFAULT NULL,
ADD COLUMN bring_your_own TINYINT(1) DEFAULT 0,
ADD COLUMN network_available VARCHAR(20) DEFAULT NULL;

ALTER TABLE venue_inspections
ADD COLUMN approved_by INT(10) DEFAULT NULL,
ADD COLUMN approved_at DATETIME DEFAULT NULL,
ADD COLUMN confirmed_at DATETIME DEFAULT NULL;



