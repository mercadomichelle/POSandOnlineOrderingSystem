-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Sep 08, 2024 at 03:21 PM
-- Server version: 10.4.27-MariaDB
-- PHP Version: 8.2.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `system_db`
--

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
  `price_type` enum('wholesale','retail') NOT NULL DEFAULT 'retail'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`cart_id`, `login_id`, `prod_id`, `quantity`, `created_at`, `user_type`, `price_type`) VALUES
(65, 3, 2, 10, '2024-08-29 15:08:41', 'customer', 'retail'),
(67, 2, 1, 9, '2024-08-30 06:58:09', 'staff', 'retail');

-- --------------------------------------------------------

--
-- Table structure for table `login`
--

CREATE TABLE `login` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `usertype` enum('admin','staff','customer') NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `login`
--

INSERT INTO `login` (`id`, `username`, `password`, `usertype`, `first_name`, `last_name`) VALUES
(1, 'admin', '1234', 'admin', 'Admin', 'Account'),
(2, 'staff', '1234', 'staff', 'Staff', 'Acc'),
(3, 'custo', '1234', 'customer', 'cus', 'acc'),
(4, 'asdasd', '235asd', 'staff', 'sfaf', 'asdasd');

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
  `order_status` enum('Pending','Being Packed','For Delivery','Delivery Complete','Cancelled') NOT NULL DEFAULT 'Pending',
  `status_processed_at` datetime DEFAULT NULL,
  `status_packed_at` datetime DEFAULT NULL,
  `status_shipped_at` datetime DEFAULT NULL,
  `status_delivered_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `login_id`, `order_date`, `total_amount`, `order_source`, `order_status`, `status_processed_at`, `status_packed_at`, `status_shipped_at`, `status_delivered_at`) VALUES
(1, 3, '2024-08-26 15:33:46', '10.00', 'online', 'Delivery Complete', NULL, '2024-09-07 06:17:36', '2024-09-07 06:35:47', '2024-09-07 11:27:19'),
(2, 3, '2024-08-26 15:54:14', '10.00', 'online', 'Delivery Complete', NULL, '2024-09-07 06:17:36', '2024-09-07 06:35:28', '2024-09-07 17:17:16'),
(3, 3, '2024-08-26 16:37:31', '10.00', 'online', 'Pending', NULL, '2024-09-07 06:17:36', NULL, NULL),
(4, 3, '2024-08-26 16:38:53', '0.00', 'online', 'For Delivery', NULL, '2024-09-07 06:17:36', NULL, NULL),
(5, 3, '2024-08-26 16:39:19', '11.00', 'online', 'Pending', NULL, '2024-09-07 06:35:28', '2024-09-07 06:35:28', '2024-09-07 17:59:36'),
(6, 3, '2024-08-26 17:05:45', '12.00', 'online', 'Delivery Complete', NULL, '2024-09-07 06:17:36', NULL, NULL),
(7, 3, '2024-08-26 17:19:46', '10.00', 'online', 'For Delivery', NULL, '2024-09-07 06:17:36', '2024-09-07 06:19:47', '2024-09-07 14:05:11'),
(8, 3, '2024-08-27 09:01:13', '10.00', 'online', 'For Delivery', NULL, '2024-09-07 06:17:36', '2024-09-07 06:17:36', '2024-09-07 14:30:42'),
(9, 3, '2024-08-27 09:31:27', '11450.00', 'online', 'Being Packed', NULL, '2024-09-07 06:17:36', NULL, NULL),
(10, 3, '2024-08-27 18:43:33', '16292.00', 'online', 'For Delivery', NULL, NULL, NULL, NULL),
(11, 3, '2024-08-27 18:45:26', '11650.00', 'online', 'Being Packed', NULL, NULL, NULL, NULL),
(12, 3, '2024-08-27 18:46:04', '11650.00', 'online', 'Being Packed', NULL, NULL, NULL, NULL),
(13, 3, '2024-08-27 18:47:07', '11550.00', 'online', 'For Delivery', NULL, '2024-09-07 06:17:36', '2024-09-07 06:17:36', '2024-09-07 17:50:41'),
(14, 3, '2024-08-27 19:11:23', '15100.00', 'online', 'For Delivery', NULL, NULL, NULL, NULL),
(15, 2, '2024-08-28 20:22:06', '12570.00', 'in-store', 'Pending', NULL, NULL, NULL, NULL),
(16, 2, '2024-08-28 21:24:33', '11300.00', 'in-store', 'Pending', NULL, NULL, NULL, NULL),
(17, 2, '2024-08-28 21:24:35', '150.00', 'in-store', 'Pending', NULL, NULL, NULL, NULL),
(18, 3, '2024-08-29 21:50:30', '13172.00', 'online', 'For Delivery', NULL, '2024-09-07 06:17:06', '2024-09-07 11:49:30', NULL),
(19, 3, '2024-08-29 22:05:39', '10650.00', 'online', 'Cancelled', NULL, NULL, NULL, NULL),
(20, 3, '2024-08-29 22:06:51', '10650.00', 'online', 'Cancelled', NULL, NULL, NULL, NULL),
(21, 2, '2024-08-29 22:13:35', '13260.00', 'in-store', 'Pending', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `order_item_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `prod_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(33, 21, 1, 5),
(34, 21, 3, 6);

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`prod_id`, `prod_brand`, `prod_name`, `prod_price_wholesale`, `prod_price_retail`, `prod_image_path`, `prod_created_at`) VALUES
(1, 'A. M. Delen', 'Malagkit Mindoro', '1242.00', '34.00', '../../images/sacks/a_m_malagkit.png', '2024-08-15 10:32:17'),
(2, 'Farmers Best', 'C-4 Dinorado', '1130.00', '56.00', '../../images/sacks/f_b_c4_dinorado.png', '2024-08-15 10:32:17'),
(3, 'Farmers Best', 'Dinorado', '1150.00', '40.00', '../../images/sacks/f_b_dinorado.png', '2024-08-15 10:32:17'),
(4, 'Farmers Best', 'Jasmine', '1200.00', '65.00', '../../images/sacks/f_b_jasmine.png', '2024-08-15 10:32:17'),
(5, 'N. H. Escalona', 'Malagkit Mindoro', '1050.00', '60.00', '../../images/sacks/n_h_malagkit.png', '2024-08-15 10:32:19'),
(6, 'Farmers Best', 'Maharlika', '1110.00', '55.00', '../../images/sacks/f_b_maharlika.png', '2024-08-16 09:02:48'),
(17, 'Farmers Best', 'Sinandomeng', '1200.00', '45.00', '../../images/sacks/f_b_sinandomeng.png', '2024-08-20 09:31:14'),
(23, 'Farmers Best', 'Milagrosa', '1050.00', '51.00', '../../images/sacks/f_b_milagrosa.png', '2024-08-30 08:53:47');

-- --------------------------------------------------------

--
-- Table structure for table `profile`
--

CREATE TABLE `profile` (
  `username` varchar(50) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(11) NOT NULL,
  `address` varchar(255) NOT NULL,
  `barangay` varchar(100) NOT NULL,
  `city` varchar(100) NOT NULL,
  `province` varchar(100) NOT NULL,
  `zip_code` varchar(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `profile`
--

INSERT INTO `profile` (`username`, `email`, `phone`, `address`, `barangay`, `city`, `province`, `zip_code`) VALUES
('custo', 'asdsad@gmail.com', '42143424363', 'Banay-Banay 1st, San Jose, Batangas', 'Banay-banay 1st', 'San Jose', 'Batangas', '4224');

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `staff_id` int(11) NOT NULL,
  `login_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `phone_number` varchar(11) NOT NULL,
  `email_address` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`staff_id`, `login_id`, `name`, `phone_number`, `email_address`) VALUES
(1, 2, 'Staff Acc', '12475475687', 'ASDasd@gmail.com'),
(2, 4, 'sfaf asdasd', '24124124974', 'asda@gmail.com');

-- --------------------------------------------------------

--
-- Table structure for table `stocks`
--

CREATE TABLE `stocks` (
  `stock_id` int(11) NOT NULL,
  `prod_id` int(11) NOT NULL,
  `stock_quantity` int(11) NOT NULL,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stocks`
--

INSERT INTO `stocks` (`stock_id`, `prod_id`, `stock_quantity`, `last_updated`) VALUES
(1, 1, 40, '2024-08-30 13:29:13'),
(2, 2, 1, '2024-09-04 09:28:11'),
(6, 3, 43, '2024-08-31 09:27:21'),
(10, 5, 35, '2024-08-29 14:06:51'),
(12, 4, 4, '2024-08-31 09:59:04'),
(13, 6, 38, '2024-08-29 13:50:30'),
(45, 17, 39, '2024-09-05 08:43:28'),
(51, 23, 40, '2024-08-30 08:54:04');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`cart_id`),
  ADD KEY `login_id` (`login_id`),
  ADD KEY `prod_id` (`prod_id`);

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
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `cart_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=70;

--
-- AUTO_INCREMENT for table `login`
--
ALTER TABLE `login`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `order_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `prod_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `staff_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `stocks`
--
ALTER TABLE `stocks`
  MODIFY `stock_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- Constraints for dumped tables
--

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
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`),
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`prod_id`) REFERENCES `products` (`prod_id`);

--
-- Constraints for table `profile`
--
ALTER TABLE `profile`
  ADD CONSTRAINT `profile_ibfk_1` FOREIGN KEY (`username`) REFERENCES `login` (`username`);

--
-- Constraints for table `staff`
--
ALTER TABLE `staff`
  ADD CONSTRAINT `staff_ibfk_1` FOREIGN KEY (`login_id`) REFERENCES `login` (`id`);

--
-- Constraints for table `stocks`
--
ALTER TABLE `stocks`
  ADD CONSTRAINT `stocks_ibfk_1` FOREIGN KEY (`prod_id`) REFERENCES `products` (`prod_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
