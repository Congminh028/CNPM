-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 02, 2025 at 03:45 PM
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
-- Database: `quan_ly_sieu_thi`
--

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `product_code` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock_quantity` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `product_code`, `name`, `description`, `price`, `stock_quantity`, `created_at`) VALUES
(1, 'SP001', 'Bánh Quy Ngọt Hải Châu', 'Bánh quy đóng gói 400g', 35000.00, 171, '2025-11-26 16:15:16'),
(2, 'SP002', 'Sữa Tươi Vinamilk Không Đường 1L', 'Hộp sữa tươi tiệt trùng 1 lít', 32500.00, 500, '2025-11-26 16:15:16'),
(3, 'SP003', 'Nước Ngọt Coca-Cola Lon', 'Lốc 6 lon nước ngọt 330ml', 45000.00, 320, '2025-11-26 16:15:16'),
(4, 'SP004', 'Mì Ăn Liền Hảo Hảo Tôm Chua', 'Thùng 30 gói mì ăn liền', 120000.00, 322, '2025-11-26 16:15:16');

-- --------------------------------------------------------

--
-- Table structure for table `receipts`
--

CREATE TABLE `receipts` (
  `id` int(11) NOT NULL,
  `receipt_code` varchar(50) NOT NULL,
  `receipt_date` date NOT NULL,
  `supplier_name` varchar(255) DEFAULT NULL,
  `total_amount` decimal(15,2) DEFAULT 0.00,
  `user_id` int(11) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Completed',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `receipts`
--

INSERT INTO `receipts` (`id`, `receipt_code`, `receipt_date`, `supplier_name`, `total_amount`, `user_id`, `status`, `created_at`) VALUES
(1, 'PN-20251126-768', '2025-11-26', '', 700000.00, 4, 'Completed', '2025-11-27 00:08:12'),
(2, 'PN-20251126-391', '2025-11-26', '', 100000.00, 4, 'Completed', '2025-11-27 00:10:57'),
(3, 'PN-20251126-493', '2025-11-26', '', 22520000.00, 4, 'Completed', '2025-11-27 00:14:25');

-- --------------------------------------------------------

--
-- Table structure for table `receipt_details`
--

CREATE TABLE `receipt_details` (
  `id` int(11) NOT NULL,
  `receipt_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(15,2) NOT NULL,
  `sub_total` decimal(15,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `receipt_details`
--

INSERT INTO `receipt_details` (`id`, `receipt_id`, `product_id`, `quantity`, `unit_price`, `sub_total`) VALUES
(1, 1, 3, 20, 35000.00, 700000.00),
(2, 2, 4, 1, 100000.00, 100000.00),
(3, 3, 4, 221, 100000.00, 22100000.00),
(4, 3, 1, 21, 20000.00, 420000.00);

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) UNSIGNED DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'completed',
  `transaction_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `fullname` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `role` varchar(50) NOT NULL DEFAULT 'staff',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `fullname`, `email`, `role`, `created_at`) VALUES
(1, 'admin', '123', 'Quản Trị Hệ Thống', 'admin@qlbh.com', 'admin', '2025-11-24 14:57:08'),
(2, 'thanh', '$2y$10$N/DeVkJIjzEkJLS3patPmOnmB.5IcUM7VYdOEirt8aW9SgMVYEaZe', 'Nguyễn Tuấn Thành', 'nguyentuanthanh@gmail.com', 'staff', '2025-11-24 15:04:02'),
(3, 'thanh1', '$2y$10$9FWQybapTyH8KwIHqSCNTeZAnCN/v3ZlTZzN2vgkXqA1tMvj9YzMW', 'Tuấn Thành DZ', 'nguyentuanthanh123@gmail.com', 'inventory', '2025-11-24 15:19:22'),
(4, 'thanhcon', '$2y$10$aA4knydBQYVMt3znRSlCT./zy0xMLeeegNFOrfjDZg8XGX3k1Q7ZG', 'Nguyễn Tuấn Thành', 'nguyenttuanthanh211314@gmail.com', 'inventory', '2025-11-26 15:25:48');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `product_code` (`product_code`);

--
-- Indexes for table `receipts`
--
ALTER TABLE `receipts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `receipt_code` (`receipt_code`);

--
-- Indexes for table `receipt_details`
--
ALTER TABLE `receipt_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `receipt_id` (`receipt_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `receipts`
--
ALTER TABLE `receipts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `receipt_details`
--
ALTER TABLE `receipt_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `receipt_details`
--
ALTER TABLE `receipt_details`
  ADD CONSTRAINT `receipt_details_ibfk_1` FOREIGN KEY (`receipt_id`) REFERENCES `receipts` (`id`),
  ADD CONSTRAINT `receipt_details_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
