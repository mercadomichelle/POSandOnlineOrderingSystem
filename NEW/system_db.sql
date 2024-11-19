-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Host: sql311.infinityfree.com
-- Generation Time: Nov 19, 2024 at 08:57 AM
-- Server version: 10.6.19-MariaDB
-- PHP Version: 7.2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `if0_37718710_system_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `alternative_varieties`
--

CREATE TABLE `alternative_varieties` (
  `id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `alternative_product_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `alternative_varieties`
--

INSERT INTO `alternative_varieties` (`id`, `product_id`, `alternative_product_id`) VALUES
(7, 1, 2),
(8, 1, 5),
(9, 2, 3),
(10, 2, 6),
(11, 3, 2);

-- --------------------------------------------------------

--
-- Table structure for table `branches`
--

CREATE TABLE `branches` (
  `branch_id` int(11) NOT NULL,
  `branch_name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `branches`
--

INSERT INTO `branches` (`branch_id`, `branch_name`) VALUES
(1, 'Calero'),
(2, 'Bauan'),
(3, 'San Pascual');

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `cart_id` int(11) NOT NULL,
  `login_id` int(11) NOT NULL,
  `prod_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `user_type` enum('customer','staff') DEFAULT 'customer',
  `price_type` enum('wholesale','retail') NOT NULL DEFAULT 'retail',
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`cart_id`, `login_id`, `prod_id`, `quantity`, `created_at`, `user_type`, `price_type`, `price`) VALUES
(72, 3, 2, 5, '2024-09-19 11:55:13', 'customer', 'wholesale', '0.00'),
(83, 3, 3, 4, '2024-09-30 02:41:59', 'customer', 'wholesale', '0.00'),
(86, 3, 6, 1, '2024-09-30 03:19:58', '', 'wholesale', '1110.00'),
(93, 11, 1, 9, '2024-10-06 11:19:19', '', 'wholesale', '1240.00');

-- --------------------------------------------------------

--
-- Table structure for table `delivery_fees`
--

CREATE TABLE `delivery_fees` (
  `id` int(11) NOT NULL,
  `city` varchar(255) NOT NULL,
  `fee` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `delivery_fees`
--

INSERT INTO `delivery_fees` (`id`, `city`, `fee`) VALUES
(1, 'Batangas City', '100.00'),
(2, 'San Jose', '120.00'),
(3, 'San Pascual', '120.00'),
(4, 'Ibaan', '150.00'),
(5, 'Taysan', '150.00'),
(6, 'Lobo', '150.00'),
(7, 'Balete', '150.00'),
(8, 'Mabini', '150.00'),
(9, 'Bauan', '150.00'),
(10, 'San Luis', '150.00'),
(11, 'Alitagtag', '150.00'),
(12, 'Cuenca', '150.00'),
(13, 'Lipa', '180.00'),
(14, 'Rosario', '180.00'),
(15, 'Padre Garcia', '180.00'),
(16, 'Taal', '180.00'),
(17, 'San Nicolas', '180.00'),
(18, 'Batangas', '120.00'),
(19, 'San Jose', '150.00'),
(20, 'San Pascual', '150.00'),
(21, 'Ibaan', '150.00'),
(22, 'Taysan', '150.00'),
(23, 'Lobo', '150.00'),
(24, 'San Pascual', '150.00'),
(25, 'Mabini', '150.00'),
(26, 'Bauan', '150.00'),
(27, 'San Luis', '150.00'),
(28, 'Alitagtag', '150.00'),
(29, 'Cuenca', '150.00'),
(30, 'Lipa', '180.00'),
(31, 'Rosario', '180.00'),
(32, 'Padre Garcia', '180.00'),
(33, 'Taal', '180.00'),
(34, 'San Nicolas', '180.00');

-- --------------------------------------------------------

--
-- Table structure for table `login`
--

CREATE TABLE `login` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `usertype` enum('admin','staff','customer','delivery') NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `login`
--

INSERT INTO `login` (`id`, `username`, `password`, `usertype`, `first_name`, `last_name`) VALUES
(13, 'cust', '$2y$10$G5Er0TZoljIYDSJfWGWrKuwB7qBsaprMjhlR8SdAF6mmsJKL7cgZm', 'customer', 'qfasf', 'asfaf'),
(15, 'admin', '$2y$10$inI9f3qenc215e3gqjjxC.UiZAOX0EgqmDojP0YAf.faUCwZRzCU6', 'admin', 'Escalona', 'Delen'),
(16, 'staff', '$2y$10$PALiQGEwfgLE4nONAEEyk.EaA85w8PKvBOAA8.6aqKX9ig/cWU9AG', 'staff', 'Staff', 'Acc'),
(17, 'del', '$2y$10$mH7Ov4mnJrtvp4TBcIV/D.87mKtq7M3YRr8EWIy1VIMviM5XWcxMW', 'delivery', 'Delivery', 'Acc'),
(18, 'Feya', '$2y$10$dYWme9Zi4zseV/NymXhxgu4yGxoZSgaD6ziKu8duHb6MA3wp/WyXe', 'customer', 'Alfea', 'Sanchez');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `login_id` int(11) NOT NULL,
  `order_date` datetime DEFAULT current_timestamp(),
  `total_amount` decimal(10,2) NOT NULL,
  `order_source` enum('online','in-store') NOT NULL DEFAULT 'online',
  `order_type` enum('wholesale','retail') NOT NULL,
  `order_status` enum('Pending','Being Packed','For Delivery','Delivery Complete','Cancelled','Paid') NOT NULL DEFAULT 'Pending',
  `status_processed_at` datetime DEFAULT NULL,
  `status_packed_at` datetime DEFAULT NULL,
  `status_shipped_at` datetime DEFAULT NULL,
  `status_delivered_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `login_id`, `order_date`, `total_amount`, `order_source`, `order_type`, `order_status`, `status_processed_at`, `status_packed_at`, `status_shipped_at`, `status_delivered_at`) VALUES
(1, 3, '2024-08-26 15:33:46', '11230.00', 'in-store', 'wholesale', 'Delivery Complete', NULL, '2024-09-07 06:17:36', '2024-09-07 06:35:47', '2024-09-07 11:27:19'),
(2, 3, '2024-08-26 15:54:14', '11230.00', 'online', 'wholesale', 'Delivery Complete', NULL, '2024-09-07 06:17:36', '2024-09-07 06:35:28', '2024-09-07 17:17:16'),
(3, 3, '2024-08-26 16:37:31', '11230.00', 'online', 'wholesale', 'Pending', NULL, '2024-09-07 06:17:36', NULL, NULL),
(5, 3, '2024-08-26 16:39:19', '11230.00', 'online', 'wholesale', 'Pending', NULL, '2024-09-07 06:35:28', '2024-09-07 06:35:28', '2024-09-07 17:59:36'),
(6, 3, '2024-08-26 17:05:45', '11230.00', 'online', 'wholesale', 'Delivery Complete', NULL, '2024-09-07 06:17:36', NULL, NULL),
(7, 3, '2024-08-26 17:19:46', '11230.00', 'online', 'wholesale', 'For Delivery', NULL, '2024-09-07 06:17:36', '2024-09-07 06:19:47', '2024-09-07 14:05:11'),
(8, 3, '2024-08-27 09:01:13', '11230.00', 'online', 'wholesale', 'For Delivery', NULL, '2024-09-07 06:17:36', '2024-09-07 06:17:36', '2024-09-07 14:30:42'),
(9, 3, '2024-08-27 09:31:27', '11450.00', 'online', 'wholesale', 'Being Packed', NULL, '2024-09-07 06:17:36', NULL, NULL),
(10, 3, '2024-08-27 18:43:33', '16292.00', 'online', 'wholesale', 'For Delivery', NULL, NULL, NULL, NULL),
(11, 3, '2024-08-27 18:45:26', '11650.00', 'online', 'wholesale', 'Being Packed', NULL, NULL, NULL, NULL),
(12, 3, '2024-08-27 18:46:04', '11650.00', 'online', 'wholesale', 'Being Packed', NULL, NULL, NULL, NULL),
(13, 3, '2024-08-27 18:47:07', '11550.00', 'online', 'wholesale', 'For Delivery', NULL, '2024-09-07 06:17:36', '2024-09-07 06:17:36', '2024-09-07 17:50:41'),
(14, 3, '2024-08-27 19:11:23', '15100.00', 'online', 'wholesale', 'For Delivery', NULL, NULL, NULL, NULL),
(18, 3, '2024-08-29 21:50:30', '13172.00', 'online', 'wholesale', 'For Delivery', NULL, '2024-09-07 06:17:06', '2024-09-07 11:49:30', NULL),
(19, 3, '2024-08-29 22:05:39', '10650.00', 'online', 'wholesale', 'Cancelled', NULL, NULL, NULL, NULL),
(20, 3, '2024-08-29 22:06:51', '10650.00', 'online', 'wholesale', 'Cancelled', NULL, NULL, NULL, NULL),
(22, 3, '2024-09-13 10:43:31', '11450.00', 'in-store', 'wholesale', 'Pending', NULL, '2024-09-30 04:08:24', '2024-09-30 04:08:27', NULL),
(40, 11, '2024-10-02 23:18:59', '11450.00', 'online', 'wholesale', 'Pending', NULL, NULL, NULL, NULL),
(42, 2, '2024-10-06 22:20:49', '14880.00', 'in-store', 'wholesale', 'Paid', NULL, NULL, NULL, NULL),
(43, 2, '2024-10-07 14:25:59', '9040.00', 'in-store', 'wholesale', 'Paid', NULL, NULL, NULL, NULL),
(44, 2, '2024-10-08 14:26:30', '5550.00', 'in-store', 'wholesale', 'Paid', NULL, NULL, NULL, NULL),
(45, 2, '2024-10-09 22:35:13', '2400.00', 'in-store', 'wholesale', 'Paid', NULL, NULL, NULL, NULL),
(46, 2, '2024-10-09 22:36:00', '10350.00', 'in-store', 'wholesale', 'Paid', NULL, NULL, NULL, NULL),
(47, 2, '2024-10-11 22:36:02', '10350.00', 'in-store', 'wholesale', 'Paid', NULL, NULL, NULL, NULL),
(48, 2, '2024-10-09 00:00:00', '102.00', 'in-store', 'retail', 'Paid', NULL, NULL, NULL, NULL),
(49, 2, '2024-10-10 00:21:50', '300.00', 'in-store', 'retail', 'Paid', NULL, NULL, NULL, NULL),
(50, 2, '2024-10-11 00:37:28', '300.00', 'in-store', 'retail', 'Paid', NULL, NULL, NULL, NULL),
(51, 2, '2024-10-15 21:00:55', '300.00', 'in-store', 'retail', 'Paid', NULL, NULL, NULL, NULL),
(52, 2, '2024-10-15 21:02:27', '203.00', 'in-store', 'retail', 'Paid', NULL, NULL, NULL, NULL),
(53, 2, '2024-10-17 21:18:26', '4440.00', 'in-store', 'wholesale', 'Paid', NULL, NULL, NULL, NULL),
(54, 2, '2024-10-17 21:19:15', '315.00', 'in-store', 'retail', 'Paid', NULL, NULL, NULL, NULL),
(55, 2, '2024-10-17 23:15:50', '1200.00', 'in-store', 'wholesale', 'Paid', NULL, NULL, NULL, NULL),
(56, 2, '2024-10-17 23:22:17', '1240.00', 'in-store', 'wholesale', 'Paid', NULL, NULL, NULL, NULL),
(57, 2, '2024-10-17 23:45:58', '1050.00', 'in-store', 'wholesale', 'Paid', NULL, NULL, NULL, NULL),
(58, 2, '2024-10-17 23:49:06', '1240.00', 'in-store', 'wholesale', 'Paid', NULL, NULL, NULL, NULL),
(59, 2, '2024-10-18 00:00:57', '1130.00', 'in-store', 'wholesale', 'Paid', NULL, NULL, NULL, NULL),
(60, 2, '2024-10-18 00:07:43', '1200.00', 'in-store', 'wholesale', 'Paid', NULL, NULL, NULL, NULL),
(61, 2, '2024-10-18 21:42:00', '2480.00', 'in-store', 'wholesale', 'Paid', NULL, NULL, NULL, NULL),
(62, 2, '2024-10-18 22:00:58', '1130.00', 'in-store', 'wholesale', 'Paid', NULL, NULL, NULL, NULL),
(63, 2, '2024-10-18 22:02:26', '180.00', 'in-store', 'wholesale', 'Paid', NULL, NULL, NULL, NULL),
(64, 2, '2024-10-18 22:08:33', '1130.00', 'in-store', 'wholesale', 'Paid', NULL, NULL, NULL, NULL),
(65, 2, '2024-10-18 22:17:40', '56.00', 'in-store', 'retail', 'Paid', NULL, NULL, NULL, NULL),
(66, 2, '2024-10-18 22:18:52', '1050.00', 'in-store', 'wholesale', 'Paid', NULL, NULL, NULL, NULL),
(67, 2, '2024-10-18 22:30:45', '1240.00', 'in-store', 'wholesale', 'Paid', NULL, NULL, NULL, NULL),
(68, 2, '2024-10-21 21:43:11', '1240.00', 'in-store', 'wholesale', 'Paid', NULL, NULL, NULL, NULL),
(69, 2, '2024-10-23 00:00:00', '1240.00', 'in-store', 'wholesale', 'Paid', NULL, NULL, NULL, NULL),
(70, 2, '2024-10-24 22:30:47', '1240.00', 'in-store', 'wholesale', 'Paid', NULL, NULL, NULL, NULL),
(71, 18, '2024-11-18 07:23:06', '12500.00', 'online', 'wholesale', 'Delivery Complete', NULL, '2024-11-18 10:54:54', '2024-11-18 10:55:00', '2024-11-18 10:55:05'),
(72, 13, '2024-11-18 21:06:49', '11620.00', 'online', 'wholesale', 'Being Packed', NULL, '2024-11-19 00:32:44', NULL, NULL),
(73, 16, '2024-11-18 21:38:49', '120.00', 'in-store', 'retail', 'Paid', NULL, NULL, NULL, NULL),
(75, 16, '2024-11-18 22:04:41', '74.00', 'in-store', 'retail', 'Paid', NULL, NULL, NULL, NULL),
(77, 16, '2024-11-18 22:07:30', '1150.00', 'in-store', 'wholesale', 'Paid', NULL, NULL, NULL, NULL),
(80, 16, '2024-11-18 22:12:31', '1050.00', 'in-store', 'wholesale', 'Paid', NULL, NULL, NULL, NULL),
(81, 16, '2024-11-18 22:30:34', '40.00', 'in-store', 'retail', 'Paid', NULL, NULL, NULL, NULL),
(83, 16, '2024-11-18 23:13:37', '56.00', 'in-store', 'retail', 'Paid', NULL, NULL, NULL, NULL),
(87, 16, '2024-11-18 23:20:27', '1250.00', 'in-store', 'wholesale', 'Paid', NULL, NULL, NULL, NULL),
(91, 16, '2024-11-18 23:26:33', '1130.00', 'in-store', 'wholesale', 'Paid', NULL, NULL, NULL, NULL),
(92, 16, '2024-11-18 23:33:05', '1250.00', 'in-store', 'wholesale', 'Paid', NULL, NULL, NULL, NULL),
(93, 16, '2024-11-18 23:34:23', '34.00', 'in-store', 'retail', 'Paid', NULL, NULL, NULL, NULL),
(94, 16, '2024-11-18 23:34:51', '1050.00', 'in-store', 'wholesale', 'Paid', NULL, NULL, NULL, NULL),
(95, 16, '2024-11-18 23:37:04', '112.00', 'in-store', 'retail', 'Paid', NULL, NULL, NULL, NULL),
(96, 16, '2024-11-18 23:39:51', '1250.00', 'in-store', 'wholesale', 'Paid', NULL, NULL, NULL, NULL),
(97, 16, '2024-11-18 23:40:13', '34.00', 'in-store', 'retail', 'Paid', NULL, NULL, NULL, NULL),
(98, 16, '2024-11-18 23:52:41', '102.00', 'in-store', 'retail', 'Paid', NULL, NULL, NULL, NULL),
(99, 16, '2024-11-18 23:53:20', '1150.00', 'in-store', 'wholesale', 'Paid', NULL, NULL, NULL, NULL),
(100, 16, '2024-11-18 23:59:49', '1250.00', 'in-store', 'wholesale', 'Paid', NULL, NULL, NULL, NULL),
(101, 16, '2024-11-19 00:04:54', '1110.00', 'in-store', 'wholesale', 'Paid', NULL, NULL, NULL, NULL),
(102, 16, '2024-11-19 00:25:50', '1150.00', 'in-store', 'wholesale', 'Paid', NULL, NULL, NULL, NULL),
(103, 16, '2024-11-19 00:32:53', '1250.00', 'in-store', 'wholesale', 'Paid', NULL, NULL, NULL, NULL),
(104, 16, '2024-11-19 00:33:45', '56.00', 'in-store', 'retail', 'Paid', NULL, NULL, NULL, NULL),
(105, 16, '2024-11-19 01:02:05', '56.00', 'in-store', 'retail', 'Paid', NULL, NULL, NULL, NULL),
(106, 16, '2024-11-19 01:05:28', '120.00', 'in-store', 'retail', 'Paid', NULL, NULL, NULL, NULL),
(107, 16, '2024-11-19 03:27:01', '1150.00', 'in-store', 'wholesale', 'Paid', NULL, NULL, NULL, NULL),
(108, 16, '2024-11-19 03:29:42', '120.00', 'in-store', 'retail', 'Paid', '2024-11-19 03:29:42', NULL, NULL, NULL),
(109, 16, '2024-11-19 03:41:23', '34.00', 'in-store', 'retail', 'Paid', '2024-11-19 03:41:23', NULL, NULL, NULL),
(110, 16, '2024-11-19 03:44:14', '56.00', 'in-store', 'retail', 'Paid', '2024-11-19 03:44:14', NULL, NULL, NULL),
(111, 16, '2024-11-19 03:49:16', '34.00', 'in-store', 'retail', 'Paid', '2024-11-19 03:49:16', NULL, NULL, NULL),
(112, 16, '2024-11-19 03:49:28', '34.00', 'in-store', 'retail', 'Paid', '2024-11-19 03:49:28', NULL, NULL, NULL),
(113, 16, '2024-11-19 20:03:03', '55.00', 'in-store', 'retail', 'Paid', '2024-11-19 20:03:03', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `order_item_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `prod_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`order_item_id`, `order_id`, `prod_id`, `quantity`) VALUES
(1, 1, 1, 2),
(2, 1, 3, 7),
(3, 1, 6, 1),
(4, 2, 5, 4),
(5, 2, 17, 6),
(6, 3, 4, 10),
(7, 5, 2, 11),
(8, 6, 4, 12),
(9, 7, 5, 10),
(10, 8, 2, 1),
(11, 8, 6, 9),
(12, 9, 2, 10),
(13, 10, 4, 3),
(14, 10, 1, 1),
(15, 10, 6, 4),
(16, 10, 3, 4),
(17, 10, 2, 2),
(18, 11, 3, 10),
(19, 12, 3, 10),
(20, 13, 5, 4),
(21, 13, 4, 6),
(22, 14, 3, 13),
(23, 15, 1, 10),
(24, 16, 4, 3),
(25, 16, 5, 5),
(26, 16, 3, 2),
(27, 18, 1, 6),
(28, 18, 3, 2),
(29, 18, 6, 2),
(30, 18, 5, 1),
(31, 19, 5, 10),
(32, 20, 5, 10),
(35, 22, 2, 10),
(36, 40, 2, 10),
(37, 42, 1, 12),
(38, 43, 2, 8),
(39, 44, 6, 5),
(40, 45, 4, 2),
(41, 46, 3, 9),
(42, 48, 1, 3),
(43, 49, 5, 5),
(44, 50, 5, 5),
(45, 51, 5, 5),
(46, 52, 17, 3),
(47, 52, 1, 1),
(48, 53, 6, 4),
(49, 54, 5, 3),
(50, 54, 17, 3),
(51, 55, 17, 1),
(52, 56, 1, 1),
(53, 57, 5, 1),
(54, 58, 1, 1),
(55, 59, 2, 1),
(56, 60, 4, 1),
(57, 61, 1, 2),
(58, 62, 2, 1),
(59, 63, 5, 3),
(60, 64, 2, 1),
(61, 65, 2, 1),
(62, 66, 5, 1),
(63, 68, 1, 1),
(64, 71, 1, 10),
(65, 72, 1, 2),
(66, 72, 3, 2),
(67, 72, 2, 3),
(68, 72, 6, 3),
(69, 74, 5, 2),
(70, 76, 3, 1),
(71, 76, 1, 1),
(72, 78, 3, 1),
(73, 80, 5, 1),
(74, 82, 3, 1),
(75, 84, 2, 1),
(76, 88, 1, 1),
(77, 91, 2, 1),
(78, 92, 1, 1),
(79, 93, 1, 1),
(80, 94, 5, 1),
(81, 95, 2, 2),
(82, 96, 1, 1),
(83, 97, 1, 1),
(84, 98, 1, 2),
(85, 99, 3, 1),
(86, 100, 1, 1),
(87, 101, 6, 1),
(88, 102, 3, 1),
(89, 103, 1, 1),
(90, 104, 2, 1),
(91, 105, 2, 1),
(92, 106, 3, 3),
(93, 107, 3, 1),
(94, 108, 5, 2),
(95, 109, 1, 1),
(96, 110, 2, 1),
(97, 111, 1, 1),
(98, 113, 6, 1);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `prod_id` int(11) NOT NULL,
  `prod_brand` varchar(100) NOT NULL,
  `prod_name` varchar(100) NOT NULL,
  `prod_price_wholesale` decimal(10,2) NOT NULL,
  `prod_price_retail` decimal(10,2) NOT NULL,
  `prod_image_path` varchar(255) DEFAULT NULL,
  `prod_created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`prod_id`, `prod_brand`, `prod_name`, `prod_price_wholesale`, `prod_price_retail`, `prod_image_path`, `prod_created_at`) VALUES
(1, 'A. M. Delen', 'Malagkit Mindoro', '1250.00', '34.00', '../../images/sacks/f_b_jasmine.png', '2024-08-15 10:32:17'),
(2, 'Farmers Best', 'C-4 Dinorado', '1130.00', '56.00', '../../images/sacks/f_b_c4_dinorado.png', '2024-08-15 10:32:17'),
(3, 'Farmers Best', 'Dinorado', '1150.00', '40.00', '../../images/sacks/f_b_dinorado.png', '2024-08-15 10:32:17'),
(5, 'N. H. Escalona', 'Malagkit Mindoro', '1050.00', '60.00', '../../images/sacks/n_h_malagkit.png', '2024-08-15 10:32:19'),
(6, 'Farmers Best', 'Maharlika', '1110.00', '55.00', '../../images/sacks/f_b_maharlika.png', '2024-08-16 09:02:48');

-- --------------------------------------------------------

--
-- Table structure for table `profile`
--

CREATE TABLE `profile` (
  `username` varchar(50) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(11) NOT NULL,
  `address` varchar(255) NOT NULL,
  `city` varchar(100) NOT NULL,
  `zip_code` varchar(4) NOT NULL,
  `latitude` float DEFAULT NULL,
  `longitude` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `profile`
--

INSERT INTO `profile` (`username`, `email`, `phone`, `address`, `city`, `zip_code`, `latitude`, `longitude`) VALUES
('cust', 'asdsad@gmail.com', '09837474473', 'San Jose Bayan, Chief Justice Q. C. Makalintal Avenue, Poblacion II, Poblacion, Taysan, San Jose, Batangas, Calabarzon, 4227, Philippines', 'San Jose', '4227', NULL, NULL),
('custo', 'asdsad@gmail.com', '42143424363', 'San Jose, Batangas, Calabarzon, 4227, Philippines', 'San Jose', '4224', 13.8929, 121.132),
('Feya', 'alfeasanchez7702@gmail.com', '09770821313', 'Calansayan Barangay Hall, Flamingo Street, Greenmeadows Residences (Phase 1), Calansayan, Dagatan, San Jose, Batangas, Calabarzon, 4227, Philippines', 'San Jose', '4227', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `staff_id` int(11) NOT NULL,
  `login_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `phone_number` varchar(11) NOT NULL,
  `email_address` varchar(255) NOT NULL,
  `usertype` varchar(25) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`staff_id`, `login_id`, `name`, `phone_number`, `email_address`, `usertype`) VALUES
(1, 2, 'Staff Acc', '12475475687', 'ASDasd@gmail.com', 'staff'),
(2, 4, 'sfaf asdasd', '24124124974', 'asda@gmail.com', 'delivery'),
(5, 9, 'ASDASD ', '09837474473', 'mmercado@gmail.com', 'delivery'),
(7, 16, 'Staff Acc', '09877654432', 'staff@gmail.com', 'staff'),
(8, 17, 'Delivery Acc', '09836451831', 'del@gmail.com', 'delivery');

-- --------------------------------------------------------

--
-- Table structure for table `stocks`
--

CREATE TABLE `stocks` (
  `stock_id` int(11) NOT NULL,
  `prod_id` int(11) NOT NULL,
  `stock_quantity` int(11) NOT NULL,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `branch_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stocks`
--

INSERT INTO `stocks` (`stock_id`, `prod_id`, `stock_quantity`, `last_updated`, `branch_id`) VALUES
(1, 1, 31, '2024-11-19 12:02:43', 1),
(2, 2, 4, '2024-11-19 12:02:51', 2),
(6, 3, 14, '2024-11-19 11:27:01', 2),
(10, 5, 16, '2024-11-19 11:29:42', 3),
(13, 6, 27, '2024-11-19 12:03:03', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `alternative_varieties`
--
ALTER TABLE `alternative_varieties`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `alternative_product_id` (`alternative_product_id`);

--
-- Indexes for table `branches`
--
ALTER TABLE `branches`
  ADD PRIMARY KEY (`branch_id`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`cart_id`),
  ADD KEY `login_id` (`login_id`),
  ADD KEY `prod_id` (`prod_id`);

--
-- Indexes for table `delivery_fees`
--
ALTER TABLE `delivery_fees`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `login`
--
ALTER TABLE `login`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `login_id` (`login_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`order_item_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `prod_id` (`prod_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`prod_id`);

--
-- Indexes for table `profile`
--
ALTER TABLE `profile`
  ADD PRIMARY KEY (`username`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`staff_id`),
  ADD KEY `login_id` (`login_id`);

--
-- Indexes for table `stocks`
--
ALTER TABLE `stocks`
  ADD PRIMARY KEY (`stock_id`),
  ADD UNIQUE KEY `prod_id` (`prod_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `alternative_varieties`
--
ALTER TABLE `alternative_varieties`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `cart_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=168;

--
-- AUTO_INCREMENT for table `delivery_fees`
--
ALTER TABLE `delivery_fees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `login`
--
ALTER TABLE `login`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=114;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `order_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=99;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `prod_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `staff_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `stocks`
--
ALTER TABLE `stocks`
  MODIFY `stock_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=75;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `alternative_varieties`
--
ALTER TABLE `alternative_varieties`
  ADD CONSTRAINT `alternative_varieties_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`prod_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `alternative_varieties_ibfk_2` FOREIGN KEY (`alternative_product_id`) REFERENCES `products` (`prod_id`) ON DELETE CASCADE;

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`login_id`) REFERENCES `login` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`prod_id`) REFERENCES `products` (`prod_id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`login_id`) REFERENCES `login` (`id`);

--
-- Constraints for table `profile`
--
ALTER TABLE `profile`
  ADD CONSTRAINT `profile_ibfk_1` FOREIGN KEY (`username`) REFERENCES `login` (`username`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
