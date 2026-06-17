-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 16, 2026 at 07:49 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


;
;
;
;

--
-- Database: `smartwrite_stationery_store_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `is_active`) VALUES
(1, 'Pens', 'High-quality writing instruments including gel pens, fountain pens, and markers', 1),
(2, 'Notebooks', 'Journals, spiral notebooks, and notepads perfect for students and professionals', 1),
(3, 'Art Supplies', 'Drawing materials, coloring pencils, sketchbooks, and watercolors for creative minds', 1),
(4, 'Office Supplies', 'Essential office supplies including staplers, folders, rulers, and more', 1),
(5, 'School Supplies', NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `contacts`
--

CREATE TABLE `contacts` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `subject` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `status` enum('new','read','replied','closed') DEFAULT 'new',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `replied_at` timestamp NULL DEFAULT NULL,
  `closed_at` timestamp NULL DEFAULT NULL,
  `admin_notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contacts`
--

INSERT INTO `contacts` (`id`, `name`, `email`, `phone`, `subject`, `message`, `status`, `created_at`, `updated_at`, `replied_at`, `closed_at`, `admin_notes`) VALUES
(4, 'Ang', 'ang@gmail.com', '1', 'Other', '1', 'read', '2026-02-03 08:18:10', '2026-02-03 09:11:59', NULL, NULL, '');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `subject` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `order_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `total_price` decimal(10,2) NOT NULL,
  `status` enum('pending','processing','completed','cancelled') DEFAULT 'pending',
  `delivery_address` text DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `customer_name` varchar(100) DEFAULT NULL,
  `customer_email` varchar(100) DEFAULT NULL,
  `customer_phone` varchar(20) DEFAULT NULL,
  `delivery_method` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `order_date`, `total_price`, `status`, `delivery_address`, `payment_method`, `customer_name`, `customer_email`, `customer_phone`, `delivery_method`) VALUES
(11, NULL, '2026-05-27 19:57:25', 22.40, 'pending', '57,Jalan Durian', 'online', NULL, NULL, NULL, 'delivery'),
(12, NULL, '2026-05-27 20:26:13', 34.40, 'pending', '57,jalan1,muar', 'Online - Tng', NULL, NULL, NULL, 'delivery'),
(13, 16, '2026-06-04 15:39:35', 22.40, 'pending', '57,Jalan Durian', 'Cash', 'tzuhong', 'tzuhong@gmail.com', '0183656110', 'delivery'),
(14, 16, '2026-06-04 15:41:41', 29.80, 'pending', '57,Jalan Durian', 'Online - Tng', 'tzuhong', 'tzuhong@gmail.com', '0126623068', 'delivery'),
(15, 16, '2026-06-04 15:48:14', 31.70, 'pending', '57,Jalan Durian', 'Cash', 'tzuhong', 'tzuhong@gmail.com', '0126623068', 'delivery'),
(16, 18, '2026-06-08 09:46:12', 26.80, 'pending', '123,jalan durain,melaka', 'Online - Tng', 'wenliang', 'wenliang1@gmail.com', '011-1187-1193', 'delivery'),
(17, 18, '2026-06-08 14:08:29', 26.80, 'pending', '123,jalan durain,melaka', 'Cash', 'wenliang', 'wenliang1@gmail.com', '011-1187-1193', 'delivery'),
(18, 18, '2026-06-08 14:21:10', 13.90, 'pending', '123,jalan durain,melaka', 'Online - Tng', 'wenliang', 'wenliang1@gmail.com', '011-1187-1193', 'delivery'),
(19, 18, '2026-06-09 09:15:27', 26.80, 'pending', '123,jalan durain,melaka', 'Cash', 'wenliang', 'wenliang1@gmail.com', '011-1187-1193', 'delivery'),
(20, NULL, '2026-06-09 18:55:32', 4.50, 'pending', '', 'Cash', 'aaa', 'admin@apt.com', '', 'pickup'),
(21, NULL, '2026-06-09 18:56:32', 3.90, 'pending', '', 'Online - Tng', 'aaa', 'admin@apt.com', '', 'pickup'),
(22, NULL, '2026-06-09 21:19:50', 3.90, 'pending', '1', 'eWallet - Touch n Go eWallet - 01158606387', 'aaa', 'admin@apt.com', '', 'delivery'),
(23, 18, '2026-06-10 05:59:08', 13.90, 'pending', '123,jalan durain,melaka', 'Cash', 'wenliang', 'wenliang1@gmail.com', '011-1187-1193', 'delivery'),
(24, 18, '2026-06-10 06:37:08', 68.60, 'pending', '123,jalan durain,melaka', 'eWallet - Touch n Go eWallet - 01111871193', 'wenliang', 'wenliang1@gmail.com', '011-1187-1193', 'delivery'),
(25, NULL, '2026-06-10 17:31:25', 29.80, 'pending', '22.jalan ngonglin,taman ngonglin,84330,Melaka,melaka', 'Cash', 'KANG ZHAO WAN', 'kang.zhao.wan@student.mmu.edu.my', '0183656110', 'delivery'),
(26, 20, '2026-06-10 18:16:55', 22.80, 'pending', '22.jalan ngonglin,taman ngonglin,84330,Melaka,melaka', 'Card - CIMB Bank - **** **** **** 7654', 'zhaowan', 'zhaowank@gmail.com', '0183656110', 'delivery'),
(27, 20, '2026-06-10 18:41:42', 18.40, 'pending', 'zhaowan, 0183656110, 22.jalan ngonglin,taman ngonglin,84330,Melaka,melaka, ,', 'Wallet Payment - Touch n Go eWallet - Ref: 12345678900', 'zhaowan', 'zhaowank@gmail.com', '0183656110', 'delivery'),
(28, 20, '2026-06-10 18:47:21', 25.90, 'pending', 'zhaowan, 0183656110, 22.jalan ngonglin,taman ngonglin,84330,Melaka,melaka, ,', 'Online Banking - FPX Maybank2u - ****', 'zhaowan', 'zhaowank@gmail.com', '', 'delivery'),
(29, 20, '2026-06-10 18:49:32', 25.90, 'pending', 'zhaowan, 0183656110, 22.jalan ngonglin,taman ngonglin,84330,Melaka,melaka, ,', 'Online Banking - FPX RHB Now - ****', 'zhaowan', 'zhaowank@gmail.com', '', 'delivery'),
(30, 20, '2026-06-10 18:52:46', 27.40, 'pending', 'zhaowan, 0183656110, 22.jalan ngonglin,taman ngonglin,84330,Melaka,melaka, ,', 'Wallet Payment - DuitNow QR - Ref: 2345678974567890', 'zhaowan', 'zhaowank@gmail.com', '', 'delivery'),
(31, 20, '2026-06-10 18:53:32', 24.80, 'pending', 'zhaowan, 0183656110, 22.jalan ngonglin,taman ngonglin,84330,Melaka,melaka, ,', 'Cash', 'zhaowan', 'zhaowank@gmail.com', '', 'delivery'),
(32, 20, '2026-06-10 18:59:37', 28.80, 'pending', 'zhaowan, 0183656110, 22.jalan ngonglin,taman ngonglin,84330,Melaka,melaka, ,', 'Wallet Payment - DuitNow QR - Ref: 54345678987654', 'zhaowan', 'zhaowank@gmail.com', '', 'delivery'),
(33, 20, '2026-06-10 19:00:10', 23.00, 'pending', 'zhaowan, 0183656110, 22.jalan ngonglin,taman ngonglin,84330,Melaka,melaka, ,', 'Online Banking - FPX Public Bank - ****', 'zhaowan', 'zhaowank@gmail.com', '', 'delivery'),
(34, 20, '2026-06-10 19:04:10', 54.60, 'pending', 'zhaowan, 0183656110, 22.jalan ngonglin,taman ngonglin,84330,Melaka,melaka, ,', 'Wallet Payment - GrabPay - Ref: 976546789-9876', 'zhaowan', 'zhaowank@gmail.com', '', 'delivery'),
(35, 20, '2026-06-10 19:16:19', 29.80, 'pending', 'zhaowan, 0183656110, 22.jalan ngonglin,taman ngonglin,84330,Melaka,melaka, ,', 'Online Banking - FPX RHB Now - ****', 'zhaowan', 'zhaowank@gmail.com', '', 'delivery'),
(36, 20, '2026-06-11 13:51:11', 24.80, 'cancelled', 'zhaowan, 0183656110, 22.jalan ngonglin,taman ngonglin,84330,Melaka,melaka, ,', 'Wallet Payment - Touch n Go eWallet - Ref: 54345678987', 'zhaowan', 'zhaowank@gmail.com', '', 'delivery'),
(37, 20, '2026-06-11 17:44:59', 38.80, 'completed', 'zhaowan, 0183656110, 22.jalan ngonglin,taman ngonglin,84330,Melaka,melaka, ,', 'Wallet Payment - Touch n Go eWallet - Ref: ghhjbjbjbj8', 'zhaowan', 'zhaowank@gmail.com', '', 'delivery'),
(38, 20, '2026-06-12 08:00:13', 28.80, 'processing', 'zhaowan, 0183656110, 22.jalan ngonglin,taman ngonglin,84330,Melaka,melaka, ,', 'Wallet Payment - Touch n Go eWallet - Ref: joiiiiii', 'zhaowan', 'zhaowank@gmail.com', '', 'delivery'),
(39, 20, '2026-06-12 08:45:03', 13.90, 'pending', 'zhaowan, 0183656110, 22.jalan ngonglin,taman ngonglin,84330,Melaka,melaka, ,', 'Cash', 'zhaowan', 'zhaowank@gmail.com', '', 'delivery'),
(40, 20, '2026-06-12 09:36:23', 24.80, 'pending', 'zhaowan, 0183656110, 22.jalan ngonglin,taman ngonglin,84330,Melaka,melaka, ,', 'Online Banking - FPX RHB Now - ****', 'zhaowan', 'zhaowank@gmail.com', '', 'delivery'),
(41, 20, '2026-06-14 13:22:50', 28.80, 'pending', 'zhaowan, 0183656110, 22.jalan ngonglin,taman ngonglin,84330,Melaka,melaka, ,', 'Online Banking - FPX CIMB Clicks - ****', 'zhaowan', 'zhaowank@gmail.com', '', 'delivery'),
(42, 20, '2026-06-14 13:27:26', 25.90, 'pending', 'KANG ZHAO WAN, 0183656110, 12,jalan duku, taman 2,tangkak,johor., 84800 Tangkak, Johor', 'Online Banking - FPX Maybank2u - ****', 'zhaowan', 'zhaowank@gmail.com', '', 'delivery'),
(43, 20, '2026-06-14 13:37:10', 13.90, 'pending', 'KANG ZHAO WAN, 0183656110, 12,jalan duku, taman 2,tangkak,johor., 84800 Tangkak, Johor', 'Wallet Payment - DuitNow QR - Ref: DN20260614213216656', 'zhaowan', 'zhaowank@gmail.com', '', 'delivery'),
(44, 20, '2026-06-14 13:37:32', 19.80, 'pending', 'KANG ZHAO WAN, 0183656110, 12,jalan duku, taman 2,tangkak,johor., 84800 Tangkak, Johor', 'Cash', 'zhaowan', 'zhaowank@gmail.com', '', 'delivery'),
(45, 20, '2026-06-14 13:41:18', 34.80, 'pending', 'KANG ZHAO WAN, 0183656110, 12,jalan duku, taman 2,tangkak,johor., 84800 Tangkak, Johor', 'Cash', 'zhaowan', 'zhaowank@gmail.com', '', 'delivery'),
(46, 20, '2026-06-14 13:42:16', 25.90, 'pending', 'KANG ZHAO WAN, 0183656110, 12,jalan duku, taman 2,tangkak,johor., 84800 Tangkak, Johor', 'Cash', 'zhaowan', 'zhaowank@gmail.com', '', 'delivery'),
(47, 20, '2026-06-14 13:47:40', 27.40, 'pending', 'KANG ZHAO WAN, 0183656110, 12,jalan duku, taman 2,tangkak,johor., 84800 Tangkak, Johor', 'Wallet Payment - Touch n Go eWallet - Ref: TNG20260614', 'zhaowan', 'zhaowank@gmail.com', '', 'delivery'),
(48, 20, '2026-06-14 13:50:22', 13.90, 'pending', 'KANG ZHAO WAN, 0183656110, 12,jalan duku, taman 2,tangkak,johor., 84800 Tangkak, Johor', 'Online Banking - FPX CIMB Clicks - ****', 'zhaowan', 'zhaowank@gmail.com', '', 'delivery'),
(49, 20, '2026-06-14 13:51:54', 38.80, 'pending', 'KANG ZHAO WAN, 0183656110, 12,jalan duku, taman 2,tangkak,johor., 84800 Tangkak, Johor', 'Online Banking - FPX Public Bank - ****', 'zhaowan', 'zhaowank@gmail.com', '', 'delivery'),
(50, 20, '2026-06-14 13:52:45', 18.90, 'pending', 'KANG ZHAO WAN, 0183656110, 12,jalan duku, taman 2,tangkak,johor., 84800 Tangkak, Johor', 'Cash', 'zhaowan', 'zhaowank@gmail.com', '', 'delivery'),
(51, 20, '2026-06-14 13:53:17', 20.90, 'pending', 'KANG ZHAO WAN, 0183656110, 12,jalan duku, taman 2,tangkak,johor., 84800 Tangkak, Johor', 'Online Banking - FPX Public Bank - ****', 'zhaowan', 'zhaowank@gmail.com', '', 'delivery'),
(52, 20, '2026-06-15 04:50:48', 38.80, 'pending', 'KANG ZHAO WAN, 0183656110, 12,jalan duku, taman 2,tangkak,johor., 84800 Tangkak, Johor', 'Wallet Payment - Touch n Go eWallet - Ref: TNG20260615', 'zhaowan', 'zhaowank@gmail.com', '', 'delivery'),
(53, 20, '2026-06-15 04:52:58', 27.40, 'pending', 'KANG ZHAO WAN, 0183656110, 12,jalan duku, taman 2,tangkak,johor., 84800 Tangkak, Johor', 'Online Banking - FPX RHB Now - ****', 'zhaowan', 'zhaowank@gmail.com', '', 'delivery'),
(54, 20, '2026-06-15 05:20:26', 22.80, 'pending', 'KANG ZHAO WAN, 0183656110, 12,jalan duku, taman 2,tangkak,johor., 84800 Tangkak, Johor', 'Card - CIMB Bank - **** **** **** 7654', 'zhaowan', 'zhaowank@gmail.com', '', 'delivery'),
(55, 20, '2026-06-15 05:29:39', 25.90, 'pending', 'KANG ZHAO WAN, 0183656110, 12,jalan duku, taman 2,tangkak,johor., 84800 Tangkak, Johor', 'Wallet Payment - GrabPay - Ref: GRAB20260615132933108', 'zhaowan', 'zhaowank@gmail.com', '', 'delivery'),
(56, 20, '2026-06-15 07:16:37', 25.90, 'pending', 'KANG ZHAO WAN, 0183656110, 12,jalan duku, taman 2,tangkak,johor., 84800 Tangkak, Johor', 'Wallet Payment - Touch n Go eWallet - Ref: TNG20260615', 'zhaowan', 'zhaowank@gmail.com', '', 'delivery'),
(57, 20, '2026-06-15 13:58:07', 18.80, 'pending', 'KANG ZHAO WAN, 0183656110, 12,jalan duku, taman 2,tangkak,johor., 84800 Tangkak, Johor', 'Wallet Payment - Touch n Go eWallet - Ref: TNG20260615', 'zhaowan', 'zhaowank@gmail.com', '', 'delivery');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`) VALUES
(21, 11, 2, 1, 8.50),
(22, 11, 4, 1, 3.90),
(23, 12, 2, 1, 8.50),
(24, 12, 3, 1, 15.90),
(25, 13, 4, 1, 3.90),
(26, 13, 2, 1, 8.50),
(27, 14, 3, 1, 15.90),
(28, 14, 4, 1, 3.90),
(29, 15, 1, 2, 5.90),
(30, 15, 5, 1, 9.90),
(31, 16, 4, 1, 3.90),
(32, 16, 11, 1, 12.90),
(33, 17, 11, 1, 12.90),
(34, 17, 4, 1, 3.90),
(35, 18, 4, 1, 3.90),
(36, 19, 15, 1, 8.90),
(37, 19, 13, 1, 7.90),
(38, 20, 2, 1, 4.50),
(39, 21, 4, 1, 3.90),
(40, 22, 4, 1, 3.90),
(41, 23, 4, 1, 3.90),
(42, 24, 9, 1, 10.90),
(43, 24, 46, 3, 15.90),
(44, 25, 3, 1, 15.90),
(45, 25, 4, 1, 3.90),
(46, 26, 47, 1, 8.90),
(47, 26, 4, 1, 3.90),
(48, 27, 2, 1, 4.50),
(49, 27, 4, 1, 3.90),
(50, 28, 46, 1, 15.90),
(51, 29, 3, 1, 15.90),
(52, 30, 11, 1, 12.90),
(53, 30, 2, 1, 4.50),
(54, 31, 51, 1, 3.90),
(55, 31, 50, 1, 10.90),
(56, 32, 52, 1, 5.90),
(57, 32, 11, 1, 12.90),
(58, 33, 2, 1, 4.50),
(59, 33, 6, 1, 8.50),
(60, 34, 46, 1, 15.90),
(61, 34, 3, 1, 15.90),
(62, 34, 4, 1, 3.90),
(63, 34, 47, 1, 8.90),
(64, 35, 3, 1, 15.90),
(65, 35, 4, 1, 3.90),
(66, 36, 51, 1, 3.90),
(67, 36, 50, 1, 10.90),
(68, 37, 11, 1, 12.90),
(69, 37, 3, 1, 15.90),
(70, 38, 50, 1, 10.90),
(71, 38, 34, 1, 7.90),
(72, 39, 4, 1, 3.90),
(73, 40, 50, 1, 10.90),
(74, 40, 51, 1, 3.90),
(75, 41, 34, 1, 7.90),
(76, 41, 50, 1, 10.90),
(77, 42, 3, 1, 15.90),
(78, 43, 4, 1, 3.90),
(79, 44, 51, 1, 3.90),
(80, 44, 10, 1, 5.90),
(81, 45, 47, 1, 8.90),
(82, 45, 3, 1, 15.90),
(83, 46, 3, 1, 15.90),
(84, 47, 2, 1, 4.50),
(85, 47, 11, 1, 12.90),
(86, 48, 4, 1, 3.90),
(87, 49, 44, 1, 12.90),
(88, 49, 46, 1, 15.90),
(89, 50, 47, 1, 8.90),
(90, 51, 50, 1, 10.90),
(91, 52, 44, 1, 12.90),
(92, 52, 3, 1, 15.90),
(93, 53, 2, 1, 4.50),
(94, 53, 11, 1, 12.90),
(95, 54, 47, 1, 8.90),
(96, 54, 4, 1, 3.90),
(97, 55, 3, 1, 15.90),
(98, 56, 3, 1, 15.90),
(99, 57, 27, 1, 4.90),
(100, 57, 53, 1, 3.90);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `image` varchar(255) DEFAULT 'default.jpg',
  `available` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `price`, `category_id`, `image`, `available`) VALUES
(1, 'Pilot G2 Pen', 'Smooth writing gel pen, 0.7mm', 2.50, 1, 'pilot-g2-pen.jpg', 1),
(2, 'A5 Notebook', '100 pages lined notebook, soft cover', 4.50, 2, 'a5-notebook.jpg', 1),
(3, 'Coloring Pencils Set', '24 colors premium coloring pencils', 15.90, 3, 'color-pencil-set.jpg', 1),
(4, 'Eraser Set', 'Pack of 5 soft erasers', 3.90, 3, 'eraser-set.jpg', 1),
(5, 'Highlighter Pen Set', '6 neon colors, chisel tip', 9.90, 1, 'highlighter-set.jpg', 1),
(6, 'Memo Pad Set', '5 pack, 50 sheets each, self-adhesive', 8.50, 2, 'memo-pad.jpg', 1),
(7, 'Glue Stick', 'Washable, 40g, acid-free', 3.50, 4, 'glue-stick.jpg', 1),
(8, 'Mini Stapler', 'Compact stapler with 100 staples', 6.90, 4, 'mini-stapler.jpg', 1),
(9, 'Ballpoint Pen Set', 'Pack of 10 blue ballpoint pens', 10.90, 1, 'ballpoint-pen-set.jpg', 1),
(10, 'Spiral Notebook', '200 pages spiral notebook', 5.90, 2, 'spiral-notebook.jpg', 1),
(11, 'Watercolor Paint Set', '12 colors watercolor paint set', 12.90, 3, 'watercolor-set.jpg', 1),
(12, 'Paper Clips Box', '100 paper clips per box', 2.90, 4, 'paper-clips.jpg', 1),
(13, 'Geometry Set', 'Ruler, compass and protractor set', 7.90, 5, 'geometry-set.jpg', 1),
(14, 'School Backpack', 'Lightweight school backpack', 29.90, 5, 'school-backpack.jpg', 1),
(15, 'Pencil Case', 'Large capacity pencil case', 8.90, 5, 'pencil-case.jpg', 1),
(27, 'Correction Tape', 'Smooth correction tape for clean and neat writing.', 4.90, 4, 'sw_correction_tape.png', 1),
(28, 'Sticky Notes Cube', 'Colourful sticky notes for quick reminders.', 6.90, 4, 'sw_sticky_notes_cube.png', 1),
(29, 'Index Flags Set', 'Bright index flags for marking important pages.', 3.50, 4, 'sw_index_flags_set.png', 1),
(30, 'Transparent Tape Roll', 'Clear tape for packing, fixing and school projects.', 2.90, 4, 'sw_transparent_tape_roll.png', 1),
(31, 'Double Sided Tape', 'Strong double sided tape for crafts and display work.', 4.50, 4, 'sw_double_sided_tape.png', 1),
(32, 'Binder Clips Pack', 'Useful binder clips for organising notes and papers.', 5.50, 4, 'sw_binder_clips_pack.png', 1),
(33, 'Clear Folder Set', 'Transparent folders for keeping documents tidy.', 6.90, 5, 'sw_clear_folder_set.png', 1),
(34, 'A4 Ring File', 'Durable ring file for reports and office documents.', 7.90, 4, 'sw_a4_ring_file.png', 1),
(35, 'Clipboard', 'Strong clipboard for writing without a desk.', 8.90, 4, 'sw_clipboard.png', 1),
(36, 'Metal Scissors', 'Sharp scissors for paper, craft and office use.', 5.90, 4, 'sw_metal_scissors.png', 1),
(37, '30cm Ruler', 'Clear ruler for drawing straight lines and measurement.', 2.20, 5, 'sw_30cm_ruler.png', 1),
(38, 'Pencil Sharpener', 'Compact sharpener for pencils and colouring pencils.', 2.50, 5, 'sw_pencil_sharpener.png', 1),
(39, 'HB Pencil Set', 'Pack of smooth HB pencils for writing and sketching.', 4.90, 1, 'sw_hb_pencil_set.png', 1),
(40, 'Mechanical Pencil Set', 'Comfortable mechanical pencils for neat writing.', 6.90, 1, 'sw_mechanical_pencil_set.png', 1),
(41, 'Gel Ink Pen Set', 'Smooth gel ink pens for notes and journaling.', 8.90, 1, 'sw_gel_ink_pen_set.png', 1),
(42, 'Whiteboard Marker Set', 'Markers with clear ink for classroom and office boards.', 7.90, 1, 'sw_whiteboard_marker_set.png', 1),
(43, 'Permanent Marker Set', 'Bold permanent markers for labels and packaging.', 6.90, 1, 'sw_permanent_marker_set.png', 1),
(44, 'Brush Pen Set', 'Flexible brush pens for lettering and creative drawing.', 12.90, 3, 'sw_brush_pen_set.png', 1),
(45, 'Sketch Book', 'A4 sketch book for drawing, ideas and art practice.', 9.90, 3, 'sw_sketch_book.png', 1),
(46, 'Acrylic Paint Set', 'Colourful acrylic paints for school and art projects.', 15.90, 3, 'sw_acrylic_paint_set.png', 1),
(47, 'Oil Pastel Set', 'Soft oil pastels for colouring and blending artwork.', 8.90, 3, 'sw_oil_pastel_set.png', 1),
(48, 'Drawing Compass', 'Compass tool for accurate circles and geometry work.', 4.90, 5, 'sw_drawing_compass.png', 1),
(49, 'Scientific Calculator', 'Calculator for mathematics, science and exam practice.', 29.90, 5, 'sw_scientific_calculator.png', 1),
(50, 'A4 Copy Paper Pack', 'Clean white A4 paper for printing and copying.', 10.90, 4, 'sw_a4_copy_paper_pack.png', 1),
(51, 'Sticky Bookmarks', 'Small sticky bookmarks for notes and revision.', 3.90, 2, 'sw_sticky_bookmarks.png', 1),
(52, 'Washi Tape Set', 'Decorative tape for journals, cards and craft work.', 5.90, 3, 'sw_washi_tape_set.png', 1),
(53, 'Correction Pen', 'Fast drying correction pen for small writing mistakes.', 3.90, 4, 'sw_correction_pen.png', 1),
(54, 'Notebook Divider Tabs', 'Divider tabs to organise notebooks and study notes.', 4.50, 2, 'sw_notebook_divider_tabs.png', 1);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `user_type` enum('superadmin','admin','user') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_expiry` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `phone`, `password`, `user_type`, `created_at`, `last_login`, `profile_image`, `reset_token`, `reset_expiry`) VALUES
(1, 'superadmin', 'admin@quickbite.com', '+06 11 3347 3876', '$2y$12$dVvkzTijcK2RqR2P36iaiu4QA16lyAG0YvTovaWr8Pr2ezFxCpOGK', 'superadmin', '2026-01-24 14:34:40', '2026-06-15 04:53:57', 'uploads/profile_images/profile_1_698351a9cd5e81.90973226.png', NULL, NULL),
(12, 'Wen Liang', 'wenliang@gmail.com', NULL, '$2y$10$.1IQzkCF1foXpIHDkHUtGOeUdMWkaYMKUJKYD.W5cOdbDnSq9uErO', 'user', '2026-02-05 02:02:39', NULL, NULL, NULL, NULL),
(16, 'tzuhong', 'tzuhong@gmail.com', '0126623068', '$2y$10$fpYQaA94ka1bBRfAEc4avOxU1n9i.wsiUhHoetD61dymkfK3JAoFi', 'user', '2026-06-04 15:11:53', NULL, NULL, NULL, NULL),
(18, 'wenliang', 'wenliang1@gmail.com', '01111871193', '$2y$10$CZNBLHhoxJ.qwY1qSnIKWOsCTGvCmrJamv2bWud/Ai3f19hv0i.1G', 'user', '2026-06-08 09:44:04', '2026-06-10 06:29:42', NULL, '9692488cac6e1e3518f75e57b71fac827e7709f2328c1824795a1cf3c3e2ec35', NULL),
(20, 'zhaowan', 'zhaowank@gmail.com', '0183656110', '$2y$10$UPalrZ1aJipKf21HhsYMxeuIA68GJKjxmN8UX.6XSxJ2yRqbgMLou', 'user', '2026-06-10 17:14:04', '2026-06-15 13:48:31', NULL, '576750', '2026-06-15 13:31:41'),
(21, 'admin1', 'admin1@gmail.com', NULL, '$2y$12$KZJH0YweMdKxkIqo3Gpn9ebfrKFmSgXDK2fSC9QShahkqV4sYqJqq', 'admin', '2026-06-13 00:00:00', '2026-06-15 04:55:06', NULL, '253802', '2026-06-15 11:50:58'),
(22, 'admin', 'chinwenliang@gmail.com', NULL, '$2y$10$SafLFPw8p94f9frzZojbDe8XDZklAeJyeMPVvoK4xWEwTrpeyY8Zu', 'admin', '2026-06-15 03:37:45', '2026-06-15 03:38:07', NULL, '674357', '2026-06-15 11:55:23');

-- --------------------------------------------------------

--
-- Table structure for table `user_addresses`
--

CREATE TABLE `user_addresses` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `label` varchar(50) DEFAULT 'Address',
  `receiver_name` varchar(100) DEFAULT '',
  `phone` varchar(30) DEFAULT '',
  `address_line` text NOT NULL,
  `city` varchar(80) DEFAULT '',
  `postcode` varchar(20) DEFAULT '',
  `state` varchar(80) DEFAULT '',
  `is_default` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_addresses`
--

INSERT INTO `user_addresses` (`id`, `user_id`, `label`, `receiver_name`, `phone`, `address_line`, `city`, `postcode`, `state`, `is_default`, `created_at`, `updated_at`) VALUES
(1, 19, 'Address', 'aaa', '', '1', '', '', '', 0, '2026-06-10 05:19:50', '2026-06-10 05:19:50'),
(2, 18, 'Address', 'wenliang', '011-1187-1193', '123,jalan durain,melaka', '', '', '', 0, '2026-06-10 13:59:08', '2026-06-10 13:59:08'),
(3, 18, 'Address', 'wenliang', '011-1187-1193', '123,jalan durain,melaka', '', '', '', 0, '2026-06-10 14:37:08', '2026-06-10 14:37:08'),
(4, 20, 'Home', 'zhaowan', '0183656110', '22.jalan ngonglin,taman ngonglin,84330,Melaka,melaka', '', '', '', 0, '2026-06-11 02:16:55', '2026-06-14 21:15:20'),
(5, 20, 'Office', 'KANG ZHAO WAN', '0183656110', '12,jalan duku, taman 2,tangkak,johor.', 'Tangkak', '84800', 'Johor', 1, '2026-06-14 21:15:20', '2026-06-14 21:15:20');

-- --------------------------------------------------------

--
-- Table structure for table `user_saved_cards`
--

CREATE TABLE `user_saved_cards` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `cardholder_name` varchar(100) NOT NULL,
  `bank_name` varchar(100) NOT NULL,
  `last4` varchar(4) NOT NULL,
  `expiry_date` varchar(5) NOT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_saved_cards`
--

INSERT INTO `user_saved_cards` (`id`, `user_id`, `cardholder_name`, `bank_name`, `last4`, `expiry_date`, `is_default`, `created_at`, `updated_at`) VALUES
(1, 20, 'ZHAO WAN', 'CIMB Bank', '7654', '05/30', 0, '2026-06-10 18:16:55', '2026-06-10 18:16:55');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `contacts`
--
ALTER TABLE `contacts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `product_name_unique` (`name`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `phone` (`phone`) USING BTREE;

--
-- Indexes for table `user_addresses`
--
ALTER TABLE `user_addresses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_addresses_user_id` (`user_id`);

--
-- Indexes for table `user_saved_cards`
--
ALTER TABLE `user_saved_cards`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_user_saved_card` (`user_id`,`bank_name`,`last4`,`expiry_date`),
  ADD KEY `idx_user_saved_cards_user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `contacts`
--
ALTER TABLE `contacts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=101;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `user_addresses`
--
ALTER TABLE `user_addresses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `user_saved_cards`
--
ALTER TABLE `user_saved_cards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;
COMMIT;

;
;
;
