-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th10 27, 2025 lúc 02:34 PM
-- Phiên bản máy phục vụ: 10.4.28-MariaDB
-- Phiên bản PHP: 8.1.17

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `quan_ly_sieu_thi`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) UNSIGNED DEFAULT NULL,
  `table_name` varchar(50) NOT NULL,
  `record_id` int(11) UNSIGNED NOT NULL,
  `action` varchar(50) NOT NULL,
  `field_name` varchar(255) DEFAULT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `timestamp` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `user_id`, `table_name`, `record_id`, `action`, `field_name`, `old_value`, `new_value`, `timestamp`) VALUES
(1, 4, 'products', 16, 'UPDATE', 'price', '10000', '200000', '2025-11-27 00:23:16'),
(2, 4, 'products', 15, 'UPDATE', 'name', 'cá viên chiên', 'cá viên chiên tàu khựa', '2025-11-27 00:23:52'),
(3, 4, 'products', 16, 'UPDATE', 'stock_quantity', '350', '351', '2025-11-27 14:01:07'),
(4, 4, 'products', 16, 'UPDATE', 'name', 'bò xào', 'bò xào lá lốt', '2025-11-27 14:01:14'),
(5, 4, 'transactions', 59, 'Update', 'Tổng tiền', '715,000', '780,000', '2025-11-27 15:14:50'),
(6, 4, 'transactions', 59, 'Update', 'Thuế (%)', '10', '20', '2025-11-27 15:14:50'),
(7, 4, 'transactions', 60, 'Update', 'Tổng tiền', '220,000', '440,000', '2025-11-27 15:15:02'),
(8, 4, 'transactions', 60, 'Update', 'DS Sản phẩm', 'bò xào lá lốt (x1)', 'bò xào lá lốt (x2)', '2025-11-27 15:15:02'),
(9, 4, 'transactions', 60, 'Update', 'Tổng tiền', '440,000', '560,000', '2025-11-27 15:19:21'),
(10, 4, 'transactions', 60, 'Update', 'Thuế (%)', '10', '40', '2025-11-27 15:19:21'),
(11, 4, 'transactions', 60, 'Update', 'Tổng tiền', '560,000', '630,000', '2025-11-27 15:19:26'),
(12, 4, 'transactions', 60, 'Update', 'DS Sản phẩm', 'bò xào lá lốt (x2)', 'bò xào lá lốt (x2), chó cảnh (x1)', '2025-11-27 15:19:26'),
(13, 4, 'transactions', 61, 'Update', 'Tổng tiền', '65,000', '75,000', '2025-11-27 15:34:10'),
(14, 4, 'transactions', 61, 'Update', 'Thuế (%)', '30', '50', '2025-11-27 15:34:10'),
(15, 4, 'transactions', 58, 'Update', 'Trạng thái', 'pending', 'cancelled', '2025-11-27 15:36:30'),
(16, 4, 'transactions', 61, 'Update', 'Tổng tiền', '75,000', '95,000', '2025-11-27 15:39:03'),
(17, 4, 'transactions', 61, 'Update', 'Thuế (%)', '50', '90', '2025-11-27 15:39:03'),
(18, 4, 'transactions', 61, 'Update', 'Tổng tiền', '95,000', '100,000', '2025-11-27 20:33:56'),
(19, 4, 'transactions', 61, 'Update', 'Thuế (%)', '90', '100', '2025-11-27 20:33:56');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `products`
--

CREATE TABLE `products` (
  `id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) UNSIGNED DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `sku` varchar(50) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock_quantity` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `products`
--

INSERT INTO `products` (`id`, `user_id`, `name`, `sku`, `price`, `stock_quantity`, `created_at`) VALUES
(2, 4, 'VỊT', NULL, 10000000.00, 99998, '2025-11-26 08:57:14'),
(4, 4, 'Thịt chó', NULL, 10000.00, 10000, '2025-11-26 09:50:02'),
(13, 4, 'hột vịt lộn', NULL, 123000.00, 123122, '2025-11-26 16:21:38'),
(15, 4, 'cá viên chiên tàu khựa', NULL, 1000000.00, 995, '2025-11-26 16:50:56'),
(16, 4, 'bò xào lá lốt', NULL, 200000.00, 338, '2025-11-26 16:51:08'),
(17, 4, 'gà ràn kfC', NULL, 123123.00, 123, '2025-11-26 17:20:42'),
(18, 4, 'chó cảnh', NULL, 50000.00, 112, '2025-11-26 17:20:55'),
(19, 4, 'rau má', NULL, 36000.00, 3636, '2025-11-27 08:35:38');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) UNSIGNED DEFAULT NULL,
  `customer_name` varchar(255) DEFAULT 'Khách lẻ',
  `total_amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'completed',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `transaction_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `tax_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tax_rate` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `transactions`
--

INSERT INTO `transactions` (`id`, `user_id`, `customer_name`, `total_amount`, `payment_method`, `status`, `created_at`, `transaction_date`, `tax_amount`, `tax_rate`) VALUES
(1, 4, 'Khách lẻ', 40000.00, NULL, 'paid', '2025-11-26 09:00:48', '2025-11-26 09:00:48', 0.00, 0),
(2, 4, 'Khách lẻ', 40000.00, NULL, 'paid', '2025-11-26 09:01:00', '2025-11-26 09:01:00', 0.00, 0),
(18, 4, 'Khách lẻ', 10000.00, NULL, 'paid', '2025-11-26 10:00:34', '2025-11-26 10:00:34', 0.00, 0),
(21, 4, 'Khách lẻ', 150000.00, NULL, 'paid', '2025-11-26 10:02:00', '2025-11-26 10:02:00', 0.00, 0),
(22, 4, 'Khách lẻ', 10000000.00, NULL, 'paid', '2025-11-26 10:02:05', '2025-11-26 10:02:05', 0.00, 0),
(23, 4, 'Khách lẻ', 10300000.00, NULL, 'paid', '2025-11-26 10:02:16', '2025-11-26 10:02:16', 0.00, 0),
(24, 4, 'Khách lẻ', 320000.00, NULL, 'paid', '2025-11-26 12:48:00', '2025-11-26 12:48:00', 0.00, 0),
(25, 4, 'Khách lẻ', 10000.00, NULL, 'paid', '2025-11-26 13:18:10', '2025-11-26 13:18:10', 0.00, 0),
(26, 4, 'Khách lẻ', 176000.00, NULL, 'paid', '2025-11-26 13:34:22', '2025-11-26 13:34:22', 0.00, 0),
(27, 4, 'Khách lẻ', 165000.00, NULL, 'paid', '2025-11-26 13:34:41', '2025-11-26 13:34:41', 0.00, 0),
(28, 4, 'Khách lẻ', 150000.00, NULL, 'paid', '2025-11-26 13:41:01', '2025-11-26 13:41:01', 0.00, 0),
(29, 4, 'Khách lẻ', 10000.00, NULL, 'paid', '2025-11-26 13:41:07', '2025-11-26 13:41:07', 0.00, 0),
(30, 4, 'Khách lẻ', 70000.00, NULL, 'paid', '2025-11-26 13:44:37', '2025-11-26 13:44:37', 0.00, 0),
(31, 4, 'Khách lẻ', 150000.00, NULL, 'paid', '2025-11-26 13:45:23', '2025-11-26 13:45:23', 0.00, 0),
(32, 4, 'Khách lẻ', 30000.00, NULL, 'paid', '2025-11-26 13:46:39', '2025-11-26 13:46:39', 0.00, 0),
(33, 4, 'Khách lẻ', 30000.00, NULL, 'paid', '2025-11-26 13:47:58', '2025-11-26 13:47:58', 0.00, 0),
(34, 4, 'Khách lẻ 2', 33000.00, NULL, 'paid', '2025-11-26 13:49:50', '2025-11-26 13:49:50', 0.00, 0),
(35, 4, 'Khách lẻ2', 506000.00, NULL, 'paid', '2025-11-26 13:53:23', '2025-11-26 13:53:23', 0.00, 0),
(36, 4, 'Khách lẻ', 22000.00, NULL, 'paid', '2025-11-26 14:00:26', '2025-11-26 14:00:26', 0.00, 0),
(37, 4, 'Khách l', 165000.00, NULL, 'paid', '2025-11-26 14:10:52', '2025-11-26 14:10:52', 0.00, 0),
(38, 4, 'Khách lẻ', 22000.00, NULL, 'paid', '2025-11-26 15:21:51', '2025-11-26 15:21:51', 0.00, 0),
(39, 4, 'Khách lẻ 2', 36000.00, NULL, 'paid', '2025-11-26 16:14:45', '2025-11-26 16:14:45', 0.00, 0),
(43, 4, 'Khách lẻ', 1500000.00, NULL, 'paid', '2025-11-27 05:42:42', '2025-11-27 05:42:42', 0.00, 0),
(44, 4, 'Khách lẻ', 560000.00, NULL, 'paid', '2025-11-27 05:52:24', '2025-11-27 05:52:24', 0.00, 0),
(45, 4, 'Khách lẻ', 1392000.00, NULL, 'paid', '2025-11-27 05:56:44', '2025-11-27 05:56:44', 0.00, 0),
(46, 4, 'Khách lẻ', 1150000.00, NULL, 'paid', '2025-11-27 05:59:51', '2025-11-27 05:59:51', 150000.00, 0),
(47, 4, 'Khách lẻ', 900000.00, NULL, 'paid', '2025-11-27 06:18:01', '2025-11-27 06:18:01', 0.00, 0),
(48, 4, 'Khách lẻ', 220000.00, NULL, 'paid', '2025-11-27 06:30:56', '2025-11-27 06:30:56', 20000.00, 0),
(49, 4, 'Khách lẻ', 224000.00, NULL, 'paid', '2025-11-27 06:40:02', '2025-11-27 06:40:02', 24000.00, 12),
(50, 4, 'Khách lẻ23', 684000.00, NULL, 'paid', '2025-11-27 06:43:40', '2025-11-27 06:43:40', 84000.00, 14),
(51, 4, 'Khách lẻ213', 240000.00, NULL, 'paid', '2025-11-27 06:51:08', '2025-11-27 06:51:08', 40000.00, 20),
(52, 4, 'Khách lẻ12345', 220000.00, NULL, 'paid', '2025-11-27 06:59:10', '2025-11-27 06:59:10', 20000.00, 10),
(53, 4, 'Khách lẻ', 678000.00, NULL, 'paid', '2025-11-27 07:01:55', '2025-11-27 07:01:55', 78000.00, 13),
(54, 4, 'Khách lẻ123', 448000.00, NULL, 'paid', '2025-11-27 07:19:20', '2025-11-27 07:19:20', 48000.00, 12),
(55, 4, 'Khách lẻ5', 230000.00, NULL, 'pending', '2025-11-27 07:23:46', '2025-11-27 07:23:46', 30000.00, 15),
(56, 4, 'Khách lẻ', 3510000.00, NULL, 'paid', '2025-11-27 07:25:34', '2025-11-27 07:25:34', 510000.00, 17),
(57, 4, 'Khách lẻ', 226000.00, NULL, 'paid', '2025-11-27 07:37:05', '2025-11-27 07:37:05', 26000.00, 13),
(58, 4, 'Khách lẻ 22', 1380000.00, NULL, 'cancelled', '2025-11-27 07:37:17', '2025-11-27 07:37:17', 180000.00, 15),
(59, 4, 'Khách lẻ123', 780000.00, NULL, 'paid', '2025-11-27 07:47:12', '2025-11-27 07:47:12', 130000.00, 20),
(60, 4, 'Khách lẻ', 630000.00, NULL, 'paid', '2025-11-27 08:14:57', '2025-11-27 08:14:57', 180000.00, 40),
(61, 4, 'Khách lẻ', 100000.00, NULL, 'paid', '2025-11-27 08:34:04', '2025-11-27 08:34:04', 50000.00, 100);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `transaction_details`
--

CREATE TABLE `transaction_details` (
  `id` int(11) UNSIGNED NOT NULL,
  `transaction_id` int(11) UNSIGNED NOT NULL,
  `product_id` int(11) UNSIGNED NOT NULL,
  `quantity` int(11) NOT NULL,
  `price_at_sale` decimal(10,2) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `unit` varchar(50) DEFAULT NULL,
  `line_total` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `transaction_details`
--

INSERT INTO `transaction_details` (`id`, `transaction_id`, `product_id`, `quantity`, `price_at_sale`, `price`, `unit`, `line_total`) VALUES
(3, 22, 2, 1, 10000000.00, 0.00, NULL, 0.00),
(5, 23, 2, 1, 10000000.00, 0.00, NULL, 0.00),
(29, 43, 15, 1, 1000000.00, 0.00, NULL, 0.00),
(30, 44, 18, 8, 50000.00, 0.00, NULL, 0.00),
(31, 45, 16, 6, 200000.00, 0.00, NULL, 0.00),
(32, 47, 16, 3, 200000.00, 0.00, NULL, 0.00),
(33, 48, 16, 1, 200000.00, 0.00, NULL, 0.00),
(34, 49, 16, 1, 200000.00, 0.00, NULL, 0.00),
(35, 50, 16, 3, 200000.00, 0.00, NULL, 0.00),
(36, 51, 16, 1, 200000.00, 0.00, NULL, 0.00),
(37, 52, 16, 1, 200000.00, 0.00, NULL, 0.00),
(38, 53, 16, 3, 200000.00, 0.00, NULL, 0.00),
(39, 54, 16, 2, 200000.00, 0.00, NULL, 0.00),
(40, 55, 16, 1, 200000.00, 0.00, NULL, 0.00),
(41, 56, 15, 3, 1000000.00, 0.00, NULL, 0.00),
(42, 57, 16, 1, 200000.00, 0.00, NULL, 0.00),
(54, 59, 16, 3, 200000.00, 0.00, NULL, 0.00),
(55, 59, 18, 1, 50000.00, 0.00, NULL, 0.00),
(59, 60, 16, 2, 200000.00, 0.00, NULL, 0.00),
(60, 60, 18, 1, 50000.00, 0.00, NULL, 0.00),
(63, 58, 15, 1, 1000000.00, 0.00, NULL, 0.00),
(64, 58, 16, 1, 200000.00, 0.00, NULL, 0.00),
(66, 61, 18, 1, 50000.00, 0.00, NULL, 0.00);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `users`
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
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `fullname`, `email`, `role`, `created_at`) VALUES
(1, 'admin', '123', 'Quản Trị Hệ Thống', 'admin@qlbh.com', 'admin', '2025-11-24 14:57:08'),
(2, 'thanh', '$2y$10$N/DeVkJIjzEkJLS3patPmOnmB.5IcUM7VYdOEirt8aW9SgMVYEaZe', 'Nguyễn Tuấn Thành', 'nguyentuanthanh@gmail.com', 'staff', '2025-11-24 15:04:02'),
(3, 'thanh1', '$2y$10$9FWQybapTyH8KwIHqSCNTeZAnCN/v3ZlTZzN2vgkXqA1tMvj9YzMW', 'Tuấn Thành DZ', 'nguyentuanthanh123@gmail.com', 'inventory', '2025-11-24 15:19:22'),
(4, 'minh', '$2y$10$EH7PBODEHUtuBjtL2duPJOYGL9KDZx0gQC/DI/kQ3IUqguP6i.4Qe', 'Nguyễn Công Minh', 'nguyencongminh028@gmail.com', 'sales', '2025-11-25 14:13:25');

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `table_name_record_id` (`table_name`,`record_id`);

--
-- Chỉ mục cho bảng `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sku` (`sku`),
  ADD KEY `products_ibfk_1` (`user_id`),
  ADD KEY `idx_product_name` (`name`);

--
-- Chỉ mục cho bảng `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Chỉ mục cho bảng `transaction_details`
--
ALTER TABLE `transaction_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `transaction_id` (`transaction_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Chỉ mục cho bảng `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT cho bảng `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT cho bảng `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;

--
-- AUTO_INCREMENT cho bảng `transaction_details`
--
ALTER TABLE `transaction_details`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- AUTO_INCREMENT cho bảng `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Các ràng buộc cho bảng `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Các ràng buộc cho bảng `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Các ràng buộc cho bảng `transaction_details`
--
ALTER TABLE `transaction_details`
  ADD CONSTRAINT `transaction_details_ibfk_1` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `transaction_details_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
