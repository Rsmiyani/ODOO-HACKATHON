-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 27, 2025 at 11:32 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `gearguard_db`
--

-- --------------------------------------------------------

--
-- Stand-in structure for view `active_requests_view`
-- (See below for the actual view)
--
CREATE TABLE `active_requests_view` (
`request_id` int(11)
,`subject` varchar(255)
,`stage` enum('new','in_progress','repaired','scrap')
,`priority` enum('low','medium','high','urgent')
,`request_type` enum('corrective','preventive')
,`scheduled_date` datetime
,`equipment_name` varchar(100)
,`serial_number` varchar(100)
,`location` varchar(255)
,`assigned_to_name` varchar(100)
,`created_by_name` varchar(100)
,`created_at` timestamp
);

-- --------------------------------------------------------

--
-- Table structure for table `equipment`
--

CREATE TABLE `equipment` (
  `id` int(11) NOT NULL,
  `equipment_name` varchar(100) NOT NULL,
  `serial_number` varchar(100) NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `assigned_to_employee` varchar(100) DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `warranty_expiry` date DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `maintenance_team_id` int(11) DEFAULT NULL,
  `default_technician_id` int(11) DEFAULT NULL,
  `status` enum('active','under_maintenance','scrapped') DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `equipment`
--

INSERT INTO `equipment` (`id`, `equipment_name`, `serial_number`, `category`, `department`, `assigned_to_employee`, `purchase_date`, `warranty_expiry`, `location`, `maintenance_team_id`, `default_technician_id`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'CNC Machine Model X1', 'CNC-2024-001', 'Manufacturing', 'Production', 'Amit Patel', '2024-01-15', '2026-01-15', 'Factory Floor - Bay 3', 1, 2, 'scrapped', NULL, '2025-12-27 03:50:28', '2025-12-27 05:12:34'),
(2, 'Laptop Dell Latitude 5420', 'DELL-LAP-001', 'IT Equipment', 'IT Department', 'Priya Shah', '2024-06-20', '2027-06-20', 'Office - Desk 15', 3, 2, 'scrapped', NULL, '2025-12-27 03:50:28', '2025-12-27 05:12:32'),
(3, 'Industrial Printer HP M507', 'HP-PRINT-2024-001', 'Office Equipment', 'Administration', NULL, '2024-03-10', '2025-03-10', 'Admin Office', 2, 3, 'scrapped', NULL, '2025-12-27 03:50:28', '2025-12-27 04:23:58'),
(4, 'Forklift Toyota 8FD25', 'FORK-2023-045', 'Heavy Equipment', 'Warehouse', 'Ramesh Kumar', '2023-11-01', '2025-11-01', 'Warehouse Zone A', 1, 2, 'scrapped', NULL, '2025-12-27 03:50:28', '2025-12-27 05:12:35'),
(5, 'Jumbo Machine', 'SN-00299', 'IT Equipment', 'IT', 'Rudra', '2025-12-25', '2027-11-30', 'Rajkot', 2, 2, 'scrapped', NULL, '2025-12-27 05:54:46', '2025-12-27 09:34:04'),
(6, 'Jumbo Machine  1', 'SN-00298', 'IT Equipment', 'Production', '', '2026-01-05', '2028-09-27', 'Rajkot', 3, 7, 'under_maintenance', NULL, '2025-12-27 10:09:53', '2025-12-27 10:10:44'),
(7, 'cNC machine model2200', 'SN-00293', 'Office Equipment', 'Production', 'Rudra', '2025-12-26', '2026-01-28', 'Rajkot', 4, 4, 'active', NULL, '2025-12-27 10:20:06', '2025-12-27 10:20:06');

-- --------------------------------------------------------

--
-- Stand-in structure for view `equipment_summary_view`
-- (See below for the actual view)
--
CREATE TABLE `equipment_summary_view` (
`id` int(11)
,`equipment_name` varchar(100)
,`serial_number` varchar(100)
,`category` varchar(50)
,`department` varchar(100)
,`status` enum('active','under_maintenance','scrapped')
,`maintenance_team` varchar(100)
,`default_technician` varchar(100)
,`total_requests` bigint(21)
,`open_requests` decimal(22,0)
);

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `success` tinyint(1) DEFAULT 0,
  `attempted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `login_attempts`
--

INSERT INTO `login_attempts` (`id`, `user_id`, `email`, `ip_address`, `user_agent`, `success`, `attempted_at`) VALUES
(1, NULL, 'admin2002@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 0, '2025-12-27 03:57:11'),
(2, NULL, 'admin2002@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 0, '2025-12-27 04:01:47'),
(3, 5, 'rudramiyani2008@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 1, '2025-12-27 04:11:56'),
(4, 6, 'rudramiyani2006@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 1, '2025-12-27 04:12:34'),
(5, 7, 'rudramiyani2007@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 1, '2025-12-27 04:13:15'),
(6, 5, 'rudramiyani2008@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 1, '2025-12-27 04:18:39'),
(7, 5, 'rudramiyani2008@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 1, '2025-12-27 04:18:57'),
(8, 6, 'rudramiyani2006@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 1, '2025-12-27 04:19:05'),
(9, 7, 'rudramiyani2007@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 1, '2025-12-27 04:19:19'),
(10, 5, 'rudramiyani2008@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 1, '2025-12-27 04:19:31'),
(11, 5, 'rudramiyani2008@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 1, '2025-12-27 04:23:32'),
(12, 7, 'rudramiyani2007@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 1, '2025-12-27 04:28:56'),
(13, 5, 'rudramiyani2008@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 1, '2025-12-27 05:33:15'),
(14, 6, 'rudramiyani2006@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 1, '2025-12-27 05:33:52'),
(15, 5, 'rudramiyani2008@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 1, '2025-12-27 05:34:28'),
(16, 5, 'rudramiyani2008@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 1, '2025-12-27 05:45:55'),
(17, 6, 'rudramiyani2006@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 1, '2025-12-27 05:46:11'),
(18, 5, 'rudramiyani2008@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 1, '2025-12-27 05:47:36'),
(19, 5, 'rudramiyani2008@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 1, '2025-12-27 06:12:31'),
(20, 5, 'rudramiyani2008@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 1, '2025-12-27 06:12:38'),
(21, 5, 'rudramiyani2008@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 1, '2025-12-27 06:13:27'),
(22, 5, 'rudramiyani2008@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 1, '2025-12-27 06:15:09'),
(23, 5, 'rudramiyani2008@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 1, '2025-12-27 06:19:10'),
(24, 6, 'rudramiyani2006@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 1, '2025-12-27 06:19:19'),
(25, 5, 'rudramiyani2008@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 1, '2025-12-27 06:20:43'),
(26, 5, 'rudramiyani2008@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 1, '2025-12-27 06:29:10'),
(27, 5, 'rudramiyani2008@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 1, '2025-12-27 06:36:35'),
(28, 5, 'rudramiyani2008@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 1, '2025-12-27 06:41:38'),
(29, 5, 'rudramiyani2008@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 1, '2025-12-27 06:52:18'),
(30, 5, 'rudramiyani2008@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 1, '2025-12-27 07:47:28'),
(31, 5, 'rudramiyani2008@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 1, '2025-12-27 08:05:01'),
(32, 5, 'rudramiyani2008@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 1, '2025-12-27 08:20:25'),
(33, 5, 'rudramiyani2008@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 1, '2025-12-27 08:24:29'),
(34, 5, 'rudramiyani2008@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 1, '2025-12-27 09:11:00'),
(35, 6, 'rudramiyani2006@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 1, '2025-12-27 09:11:23'),
(36, 6, 'rudramiyani2006@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 1, '2025-12-27 09:12:11'),
(37, 5, 'rudramiyani2008@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 1, '2025-12-27 09:12:26'),
(38, 8, 'rudramiyani2009@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 1, '2025-12-27 09:30:10'),
(39, 5, 'rudramiyani2008@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 1, '2025-12-27 09:32:26'),
(40, 8, 'rudramiyani2009@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 1, '2025-12-27 09:32:55'),
(41, 8, 'rudramiyani2009@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 1, '2025-12-27 09:57:49'),
(42, 8, 'rudramiyani2009@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 1, '2025-12-27 10:08:27'),
(43, 5, 'rudramiyani2008@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 1, '2025-12-27 10:08:51'),
(44, 8, 'rudramiyani2009@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 1, '2025-12-27 10:10:02'),
(45, 7, 'rudramiyani2007@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 1, '2025-12-27 10:12:37'),
(46, 6, 'rudramiyani2006@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 1, '2025-12-27 10:15:32'),
(47, 5, 'rudramiyani2008@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 1, '2025-12-27 10:16:07'),
(48, 8, 'rudramiyani2009@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 1, '2025-12-27 10:23:14'),
(49, 8, 'rudramiyani2009@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 1, '2025-12-27 10:23:25'),
(50, 7, 'rudramiyani2007@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 1, '2025-12-27 10:25:02'),
(51, 7, 'rudramiyani2007@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 1, '2025-12-27 10:29:07'),
(52, 6, 'rudramiyani2006@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 1, '2025-12-27 10:29:14'),
(53, 5, 'rudramiyani2008@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 1, '2025-12-27 10:30:11');

-- --------------------------------------------------------

--
-- Table structure for table `maintenance_requests`
--

CREATE TABLE `maintenance_requests` (
  `id` int(11) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `equipment_id` int(11) NOT NULL,
  `request_type` enum('corrective','preventive') NOT NULL,
  `stage` enum('new','in_progress','repaired','scrap') DEFAULT 'new',
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `scheduled_date` datetime DEFAULT NULL,
  `completed_date` datetime DEFAULT NULL,
  `duration_hours` decimal(5,2) DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `is_overdue` tinyint(1) DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `maintenance_requests`
--

INSERT INTO `maintenance_requests` (`id`, `subject`, `description`, `equipment_id`, `request_type`, `stage`, `priority`, `scheduled_date`, `completed_date`, `duration_hours`, `assigned_to`, `created_by`, `is_overdue`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'Oil Leakage Issue', 'CNC machine is leaking oil from the hydraulic system. Needs immediate attention.', 1, 'corrective', 'repaired', 'high', '2025-12-27 09:20:28', NULL, NULL, 2, 1, 0, NULL, '2025-12-27 03:50:28', '2025-12-27 05:30:29'),
(2, 'Routine Laptop Checkup', 'Quarterly maintenance check for laptop - clean internals, update software, check battery health.', 2, 'preventive', 'repaired', 'low', '2025-01-15 10:00:00', NULL, NULL, 2, 1, 0, NULL, '2025-12-27 03:50:28', '2025-12-27 10:20:24'),
(3, 'Printer Paper Jam', 'Printer frequently jamming. Needs inspection and cleaning.', 3, 'corrective', 'repaired', 'medium', '2025-12-27 09:20:28', NULL, NULL, 3, 4, 0, NULL, '2025-12-27 03:50:28', '2025-12-27 06:44:26'),
(4, 'Forklift Annual Service', 'Annual preventive maintenance service for forklift as per manufacturer guidelines.', 4, 'preventive', 'repaired', 'medium', '2025-01-20 09:00:00', NULL, NULL, 2, 4, 0, NULL, '2025-12-27 03:50:28', '2025-12-27 05:52:11'),
(5, 'leaking oil', 'oil is leaking in factory', 2, 'corrective', 'in_progress', 'high', '2025-12-28 09:58:00', NULL, NULL, 3, 5, 0, NULL, '2025-12-27 04:28:43', '2025-12-27 06:46:38'),
(6, 'bunker leaking', 'bunkr', 1, 'corrective', 'new', 'low', '2025-12-31 10:03:00', NULL, NULL, 6, 7, 0, NULL, '2025-12-27 04:33:54', '2025-12-27 10:29:44'),
(7, 'stucking', 'maching is not working properly', 5, 'preventive', 'in_progress', 'low', '2026-01-10 11:50:00', NULL, NULL, 6, 6, 0, NULL, '2025-12-27 06:20:12', '2025-12-27 10:29:49'),
(8, 'leaking water', '', 5, 'corrective', 'new', 'medium', '2025-12-27 21:02:00', NULL, NULL, NULL, 8, 0, NULL, '2025-12-27 09:32:10', '2025-12-27 10:25:59'),
(9, 'water lakage', 'cfsf', 6, 'corrective', 'new', 'low', '2025-12-28 15:40:00', NULL, NULL, NULL, 8, 0, NULL, '2025-12-27 10:10:44', '2025-12-27 10:26:22'),
(10, 'oil lekage', 'sfgasgfiu', 7, 'preventive', 'in_progress', 'low', '2025-12-31 15:54:00', NULL, NULL, NULL, 8, 0, NULL, '2025-12-27 10:24:15', '2025-12-27 10:26:29');

-- --------------------------------------------------------

--
-- Table structure for table `maintenance_teams`
--

CREATE TABLE `maintenance_teams` (
  `id` int(11) NOT NULL,
  `team_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `maintenance_teams`
--

INSERT INTO `maintenance_teams` (`id`, `team_name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'Mechanical Team', 'Handles all mechanical equipment maintenance', '2025-12-27 03:50:28', '2025-12-27 03:50:28'),
(2, 'Electrical Team', 'Manages electrical systems and repairs', '2025-12-27 03:50:28', '2025-12-27 03:50:28'),
(3, 'IT Support', 'Takes care of computers and IT infrastructure', '2025-12-27 03:50:28', '2025-12-27 03:50:28'),
(4, 'HVAC Team', 'Heating, ventilation, and air conditioning specialists', '2025-12-27 03:50:28', '2025-12-27 03:50:28');

-- --------------------------------------------------------

--
-- Table structure for table `request_history`
--

CREATE TABLE `request_history` (
  `id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `request_history`
--

INSERT INTO `request_history` (`id`, `request_id`, `user_id`, `action`, `old_value`, `new_value`, `comments`, `created_at`) VALUES
(1, 2, 5, 'stage_changed', NULL, 'in_progress', NULL, '2025-12-27 04:23:49'),
(2, 2, 5, 'stage_changed', NULL, 'new', NULL, '2025-12-27 04:23:50'),
(3, 3, 5, 'stage_changed', NULL, 'repaired', NULL, '2025-12-27 04:23:57'),
(4, 3, 5, 'stage_changed', NULL, 'scrap', NULL, '2025-12-27 04:23:58'),
(5, 3, 5, 'stage_changed', NULL, 'repaired', NULL, '2025-12-27 04:23:59'),
(6, 3, 5, 'stage_changed', NULL, 'new', NULL, '2025-12-27 04:23:59'),
(7, 2, 5, 'stage_changed', NULL, 'in_progress', NULL, '2025-12-27 04:24:04'),
(8, 3, 5, 'stage_changed', NULL, 'repaired', NULL, '2025-12-27 04:24:06'),
(9, 5, 5, 'created', NULL, 'Request created', NULL, '2025-12-27 04:28:43'),
(10, 6, 7, 'created', NULL, 'Request created', NULL, '2025-12-27 04:33:54'),
(11, 3, 7, 'stage_changed', NULL, 'scrap', NULL, '2025-12-27 05:01:31'),
(12, 3, 7, 'Stage changed', NULL, 'In progress', NULL, '2025-12-27 05:12:01'),
(13, 4, 7, 'Stage changed', NULL, 'Repaired', NULL, '2025-12-27 05:12:03'),
(14, 4, 7, 'Stage changed', NULL, 'In progress', NULL, '2025-12-27 05:12:04'),
(15, 6, 7, 'Stage changed', NULL, 'In progress', NULL, '2025-12-27 05:12:11'),
(16, 1, 7, 'Stage changed', NULL, 'In progress', NULL, '2025-12-27 05:12:12'),
(17, 2, 7, 'Equipment marked as scrap', NULL, 'Scrapped', NULL, '2025-12-27 05:12:32'),
(18, 2, 7, 'Stage changed', NULL, 'Scrap', NULL, '2025-12-27 05:12:32'),
(19, 6, 7, 'Equipment marked as scrap', NULL, 'Scrapped', NULL, '2025-12-27 05:12:34'),
(20, 6, 7, 'Stage changed', NULL, 'Scrap', NULL, '2025-12-27 05:12:34'),
(21, 4, 7, 'Equipment marked as scrap', NULL, 'Scrapped', NULL, '2025-12-27 05:12:35'),
(22, 4, 7, 'Stage changed', NULL, 'Scrap', NULL, '2025-12-27 05:12:35'),
(23, 1, 7, 'Stage changed', NULL, 'Repaired', NULL, '2025-12-27 05:30:29'),
(24, 4, 5, 'Stage changed', NULL, 'Repaired', NULL, '2025-12-27 05:33:32'),
(25, 2, 5, 'Stage changed', NULL, 'Repaired', NULL, '2025-12-27 05:33:32'),
(26, 2, 5, 'Equipment marked as scrap', NULL, 'Scrapped', NULL, '2025-12-27 05:33:34'),
(27, 2, 5, 'Stage changed', NULL, 'Scrap', NULL, '2025-12-27 05:33:34'),
(28, 4, 5, 'Equipment marked as scrap', NULL, 'Scrapped', NULL, '2025-12-27 05:33:34'),
(29, 4, 5, 'Stage changed', NULL, 'Scrap', NULL, '2025-12-27 05:33:34'),
(30, 6, 6, 'Stage changed', NULL, 'New', NULL, '2025-12-27 05:34:03'),
(31, 6, 6, 'Stage changed', NULL, 'In progress', NULL, '2025-12-27 05:34:04'),
(32, 6, 6, 'Stage changed', NULL, 'New', NULL, '2025-12-27 05:34:05'),
(33, 6, 6, 'Stage changed', NULL, 'In progress', NULL, '2025-12-27 05:34:06'),
(34, 6, 6, 'Stage changed', NULL, 'Repaired', NULL, '2025-12-27 05:34:09'),
(35, 6, 6, 'Equipment marked as scrap', NULL, 'Scrapped', NULL, '2025-12-27 05:34:10'),
(36, 6, 6, 'Stage changed', NULL, 'Scrap', NULL, '2025-12-27 05:34:10'),
(37, 6, 6, 'Stage changed', NULL, 'In progress', NULL, '2025-12-27 05:34:11'),
(38, 4, 5, 'Stage changed', NULL, 'Repaired', NULL, '2025-12-27 05:52:11'),
(39, 2, 5, 'Stage changed', NULL, 'In progress', NULL, '2025-12-27 05:52:13'),
(40, 3, 5, 'Stage changed', NULL, 'Repaired', NULL, '2025-12-27 05:52:30'),
(41, 2, 5, 'Stage changed', NULL, 'Repaired', NULL, '2025-12-27 05:53:19'),
(42, 6, 5, 'Stage changed', NULL, 'Repaired', NULL, '2025-12-27 05:56:07'),
(43, 2, 5, 'Equipment marked as scrap', NULL, 'Scrapped', NULL, '2025-12-27 06:17:39'),
(44, 2, 5, 'Stage changed', NULL, 'Scrap', NULL, '2025-12-27 06:17:39'),
(45, 3, 5, 'Equipment marked as scrap', NULL, 'Scrapped', NULL, '2025-12-27 06:17:41'),
(46, 3, 5, 'Stage changed', NULL, 'Scrap', NULL, '2025-12-27 06:17:41'),
(47, 7, 6, 'created', NULL, 'Request created', NULL, '2025-12-27 06:20:12'),
(48, 7, 6, 'Stage changed', NULL, 'In progress', NULL, '2025-12-27 06:20:18'),
(49, 3, 5, 'Stage changed', NULL, 'Repaired', NULL, '2025-12-27 06:44:26'),
(50, 7, 5, 'Stage changed', NULL, 'Repaired', NULL, '2025-12-27 06:45:12'),
(51, 5, 5, 'Stage changed', NULL, 'In progress', NULL, '2025-12-27 06:46:38'),
(52, 7, 6, 'Stage changed', NULL, 'New', NULL, '2025-12-27 09:12:15'),
(53, 8, 8, 'created', NULL, 'Request created', NULL, '2025-12-27 09:32:10'),
(54, 7, 5, 'Stage changed', NULL, 'In progress', NULL, '2025-12-27 09:32:48'),
(55, 8, 8, 'Stage changed', NULL, 'In progress', NULL, '2025-12-27 09:34:01'),
(56, 8, 8, 'Stage changed', NULL, 'New', NULL, '2025-12-27 09:34:02'),
(57, 8, 8, 'Stage changed', NULL, 'In progress', NULL, '2025-12-27 09:34:03'),
(58, 8, 8, 'Stage changed', NULL, 'Repaired', NULL, '2025-12-27 09:34:04'),
(59, 8, 8, 'Equipment marked as scrap', NULL, 'Scrapped', NULL, '2025-12-27 09:34:04'),
(60, 8, 8, 'Stage changed', NULL, 'Scrap', NULL, '2025-12-27 09:34:04'),
(61, 8, 8, 'Stage changed', NULL, 'New', NULL, '2025-12-27 09:34:05'),
(62, 8, 8, 'Stage changed', NULL, 'In progress', NULL, '2025-12-27 09:39:34'),
(63, 8, 8, 'Stage changed', NULL, 'New', NULL, '2025-12-27 09:39:35'),
(64, 9, 8, 'created', NULL, 'Request created', NULL, '2025-12-27 10:10:44'),
(65, 9, 8, 'Stage changed', NULL, 'In progress', NULL, '2025-12-27 10:10:53'),
(66, 8, 8, 'Stage changed', NULL, 'In progress', NULL, '2025-12-27 10:11:04'),
(67, 8, 8, 'Stage changed', NULL, 'New', NULL, '2025-12-27 10:11:08'),
(68, 9, 8, 'Stage changed', NULL, 'New', NULL, '2025-12-27 10:11:10'),
(69, 9, 7, 'Stage changed', NULL, 'In progress', NULL, '2025-12-27 10:13:38'),
(70, 7, 6, 'Stage changed', NULL, 'Repaired', NULL, '2025-12-27 10:15:53'),
(71, 2, 5, 'Stage changed', NULL, 'Repaired', NULL, '2025-12-27 10:20:24'),
(72, 10, 8, 'created', NULL, 'Request created', NULL, '2025-12-27 10:24:15'),
(73, 8, 7, 'Stage changed', NULL, 'In progress', NULL, '2025-12-27 10:25:55'),
(74, 8, 7, 'Stage changed', NULL, 'New', NULL, '2025-12-27 10:25:56'),
(75, 8, 7, 'Stage changed', NULL, 'In progress', NULL, '2025-12-27 10:25:57'),
(76, 10, 7, 'Stage changed', NULL, 'In progress', NULL, '2025-12-27 10:25:58'),
(77, 8, 7, 'Stage changed', NULL, 'New', NULL, '2025-12-27 10:25:59'),
(78, 10, 7, 'Stage changed', NULL, 'New', NULL, '2025-12-27 10:26:21'),
(79, 9, 7, 'Stage changed', NULL, 'New', NULL, '2025-12-27 10:26:22'),
(80, 10, 7, 'Stage changed', NULL, 'In progress', NULL, '2025-12-27 10:26:29'),
(81, 7, 6, 'Stage changed', NULL, 'In progress', NULL, '2025-12-27 10:29:42'),
(82, 6, 6, 'Stage changed', NULL, 'New', NULL, '2025-12-27 10:29:44'),
(83, 7, 6, 'Stage changed', NULL, 'New', NULL, '2025-12-27 10:29:45'),
(84, 7, 6, 'Stage changed', NULL, 'In progress', NULL, '2025-12-27 10:29:49');

-- --------------------------------------------------------

--
-- Table structure for table `team_members`
--

CREATE TABLE `team_members` (
  `id` int(11) NOT NULL,
  `team_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `team_members`
--

INSERT INTO `team_members` (`id`, `team_id`, `user_id`, `joined_at`) VALUES
(1, 1, 2, '2025-12-27 03:50:28'),
(2, 2, 3, '2025-12-27 03:50:28'),
(3, 3, 2, '2025-12-27 03:50:28'),
(4, 4, 3, '2025-12-27 06:47:08'),
(5, 2, 2, '2025-12-27 10:13:10'),
(6, 2, 6, '2025-12-27 10:27:18'),
(7, 2, 4, '2025-12-27 10:27:38');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','manager','technician','user') NOT NULL DEFAULT 'user',
  `avatar` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `failed_attempts` int(11) DEFAULT 0,
  `locked_until` datetime DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `email_verified` tinyint(1) DEFAULT 0,
  `verification_token` varchar(100) DEFAULT NULL,
  `reset_token` varchar(100) DEFAULT NULL,
  `reset_token_expiry` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `avatar`, `is_active`, `failed_attempts`, `locked_until`, `last_login`, `email_verified`, `verification_token`, `reset_token`, `reset_token_expiry`, `created_at`, `updated_at`) VALUES
(1, 'Admin User', 'admin@gearguard.com', '$argon2id$v=19$m=65536,t=4,p=1$VGhpc0lzQVNhbHRWYWx1ZQ$xqr8dqJqL+8KqH1pZqL+8KqH1pZqL+8KqH1pZqL+8', 'admin', NULL, 1, 0, NULL, NULL, 1, NULL, NULL, NULL, '2025-12-27 03:50:28', '2025-12-27 03:50:28'),
(2, 'Rudra Miyani', 'rudra@gearguard.com', '$argon2id$v=19$m=65536,t=4,p=1$VGhpc0lzQVNhbHRWYWx1ZQ$xqr8dqJqL+8KqH1pZqL+8KqH1pZqL+8KqH1pZqL+8', 'technician', NULL, 1, 0, NULL, NULL, 1, NULL, NULL, NULL, '2025-12-27 03:50:28', '2025-12-27 03:50:28'),
(3, 'Rajdeepsinh Jadeja', 'rajdeep@gearguard.com', '$argon2id$v=19$m=65536,t=4,p=1$VGhpc0lzQVNhbHRWYWx1ZQ$xqr8dqJqL+8KqH1pZqL+8KqH1pZqL+8KqH1pZqL+8', 'technician', NULL, 1, 0, NULL, NULL, 1, NULL, NULL, NULL, '2025-12-27 03:50:28', '2025-12-27 03:50:28'),
(4, 'John Manager', 'manager@gearguard.com', '$argon2id$v=19$m=65536,t=4,p=1$VGhpc0lzQVNhbHRWYWx1ZQ$xqr8dqJqL+8KqH1pZqL+8KqH1pZqL+8KqH1pZqL+8', 'manager', NULL, 1, 0, NULL, NULL, 1, NULL, NULL, NULL, '2025-12-27 03:50:28', '2025-12-27 03:50:28'),
(5, 'Rudra Miyani', 'rudramiyani2008@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$N2daS25wdjJJSHpFUHZ1ag$I7ckB0OPsTIX+5eYMrFwRlRYezTNF+PGAV2EE5XooI0', 'admin', NULL, 1, 0, NULL, '2025-12-27 16:00:11', 0, 'ac6021e4e5a9f7b15bf2fd6931e052b336256ad324e4471cee5394877911ccfa', NULL, NULL, '2025-12-27 04:11:48', '2025-12-27 10:30:11'),
(6, 'Rudra Miyani', 'rudramiyani2006@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$dmdJemhGNWg5RnJBMXQ2Vg$GZ08QTb8aMwKu+vbenVqOG9P8Zt/UbbGSIUNn/dGrps', 'technician', NULL, 1, 0, NULL, '2025-12-27 15:59:14', 0, 'd182ee2af4dab8c4cf4ab7fa5ff25727d20f605f30cf0003bcfd9d2bdf55887d', NULL, NULL, '2025-12-27 04:12:29', '2025-12-27 10:29:14'),
(7, 'Rudra Miyani', 'rudramiyani2007@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$RnlNM3d6TWh2ck5lRXNvUQ$AZ7/1pnlxXoDd3Vmdg0hbmD1HDJjCmwbDt+NSz6NMfM', 'manager', NULL, 0, 0, NULL, '2025-12-27 15:59:07', 0, 'f8006812ac3486c5807b267d5180049784dd82f3a0916a9c3000ce2b584466a2', NULL, NULL, '2025-12-27 04:13:01', '2025-12-27 10:30:42'),
(8, 'Rudra Miyani', 'rudramiyani2009@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$dE5jMWlPQTJTU1k3Yk1PZA$6DtYbZVffSqULzalZjS0PKSRLC3+sN8duUh9bmknjP8', 'user', NULL, 1, 0, NULL, '2025-12-27 15:53:25', 0, '7ad064efd01db21077800e3d8e00f13199a6976fc73fd13ced098d60e7b05885', NULL, NULL, '2025-12-27 09:30:01', '2025-12-27 10:23:25');

-- --------------------------------------------------------

--
-- Structure for view `active_requests_view`
--
DROP TABLE IF EXISTS `active_requests_view`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `active_requests_view`  AS SELECT `mr`.`id` AS `request_id`, `mr`.`subject` AS `subject`, `mr`.`stage` AS `stage`, `mr`.`priority` AS `priority`, `mr`.`request_type` AS `request_type`, `mr`.`scheduled_date` AS `scheduled_date`, `e`.`equipment_name` AS `equipment_name`, `e`.`serial_number` AS `serial_number`, `e`.`location` AS `location`, `u_assigned`.`name` AS `assigned_to_name`, `u_created`.`name` AS `created_by_name`, `mr`.`created_at` AS `created_at` FROM (((`maintenance_requests` `mr` join `equipment` `e` on(`mr`.`equipment_id` = `e`.`id`)) left join `users` `u_assigned` on(`mr`.`assigned_to` = `u_assigned`.`id`)) join `users` `u_created` on(`mr`.`created_by` = `u_created`.`id`)) WHERE `mr`.`stage` in ('new','in_progress') ORDER BY `mr`.`priority` DESC, `mr`.`created_at` ASC ;

-- --------------------------------------------------------

--
-- Structure for view `equipment_summary_view`
--
DROP TABLE IF EXISTS `equipment_summary_view`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `equipment_summary_view`  AS SELECT `e`.`id` AS `id`, `e`.`equipment_name` AS `equipment_name`, `e`.`serial_number` AS `serial_number`, `e`.`category` AS `category`, `e`.`department` AS `department`, `e`.`status` AS `status`, `mt`.`team_name` AS `maintenance_team`, `u`.`name` AS `default_technician`, count(`mr`.`id`) AS `total_requests`, sum(case when `mr`.`stage` in ('new','in_progress') then 1 else 0 end) AS `open_requests` FROM (((`equipment` `e` left join `maintenance_teams` `mt` on(`e`.`maintenance_team_id` = `mt`.`id`)) left join `users` `u` on(`e`.`default_technician_id` = `u`.`id`)) left join `maintenance_requests` `mr` on(`e`.`id` = `mr`.`equipment_id`)) GROUP BY `e`.`id` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `equipment`
--
ALTER TABLE `equipment`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `serial_number` (`serial_number`),
  ADD KEY `maintenance_team_id` (`maintenance_team_id`),
  ADD KEY `default_technician_id` (`default_technician_id`),
  ADD KEY `idx_serial_number` (`serial_number`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_department` (`department`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_ip` (`ip_address`),
  ADD KEY `idx_attempted_at` (`attempted_at`);

--
-- Indexes for table `maintenance_requests`
--
ALTER TABLE `maintenance_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_equipment_id` (`equipment_id`),
  ADD KEY `idx_request_type` (`request_type`),
  ADD KEY `idx_stage` (`stage`),
  ADD KEY `idx_scheduled_date` (`scheduled_date`),
  ADD KEY `idx_assigned_to` (`assigned_to`),
  ADD KEY `idx_created_by` (`created_by`);

--
-- Indexes for table `maintenance_teams`
--
ALTER TABLE `maintenance_teams`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_team_name` (`team_name`);

--
-- Indexes for table `request_history`
--
ALTER TABLE `request_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_request_id` (`request_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `team_members`
--
ALTER TABLE `team_members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_team_member` (`team_id`,`user_id`),
  ADD KEY `idx_team_id` (`team_id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_verification_token` (`verification_token`),
  ADD KEY `idx_reset_token` (`reset_token`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `equipment`
--
ALTER TABLE `equipment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT for table `maintenance_requests`
--
ALTER TABLE `maintenance_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `maintenance_teams`
--
ALTER TABLE `maintenance_teams`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `request_history`
--
ALTER TABLE `request_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=85;

--
-- AUTO_INCREMENT for table `team_members`
--
ALTER TABLE `team_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `equipment`
--
ALTER TABLE `equipment`
  ADD CONSTRAINT `equipment_ibfk_1` FOREIGN KEY (`maintenance_team_id`) REFERENCES `maintenance_teams` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `equipment_ibfk_2` FOREIGN KEY (`default_technician_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD CONSTRAINT `login_attempts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `maintenance_requests`
--
ALTER TABLE `maintenance_requests`
  ADD CONSTRAINT `maintenance_requests_ibfk_1` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `maintenance_requests_ibfk_2` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `maintenance_requests_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `request_history`
--
ALTER TABLE `request_history`
  ADD CONSTRAINT `request_history_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `maintenance_requests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `request_history_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `team_members`
--
ALTER TABLE `team_members`
  ADD CONSTRAINT `team_members_ibfk_1` FOREIGN KEY (`team_id`) REFERENCES `maintenance_teams` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `team_members_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
