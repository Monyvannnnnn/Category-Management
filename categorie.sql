-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3307
-- Generation Time: Aug 21, 2026 at 05:01 AM
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
-- Database: `inventory`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `category_code` varchar(50) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `category_code`, `category_name`, `created_at`) VALUES
(7, 'ps_1', 'Macbook 2020', '2026-08-20 09:26:05'),
(9, 'ps_2', 'Iphone', '2026-08-20 09:49:48'),
(10, 'ps_3', 'book', '2026-08-21 02:30:28'),
(11, 'CAT-01', 'Electronics', '2026-08-21 02:49:39'),
(12, 'CAT-02', 'Books & Literature', '2026-08-21 02:49:39'),
(13, 'CAT-03', 'Clothing & Apparel', '2026-08-21 02:49:39'),
(14, 'CAT-04', 'Beauty & Personal Care', '2026-08-21 02:49:39'),
(15, 'CAT-05', 'Toys & Games', '2026-08-21 02:49:39'),
(16, 'CAT-06', 'Sports & Outdoors', '2026-08-21 02:49:39'),
(17, 'CAT-07', 'Groceries & Food', '2026-08-21 02:49:39'),
(18, 'CAT-08', 'Furniture & Decor', '2026-08-21 02:49:39'),
(19, 'CAT-09', 'Automotive Parts', '2026-08-21 02:49:39'),
(20, 'CAT-10', 'Jewelry & Watches', '2026-08-21 02:49:39'),
(21, 'CAT-11', 'Shoes & Footwear', '2026-08-21 02:49:39'),
(22, 'CAT-12', 'Garden & Patio', '2026-08-21 02:49:39'),
(23, 'CAT-13', 'Musical Instruments', '2026-08-21 02:49:39'),
(24, 'CAT-14', 'Office Supplies', '2026-08-21 02:49:39'),
(25, 'CAT-15', 'Pet Supplies', '2026-08-21 02:49:39'),
(26, 'CAT-16', 'Tools & Home Improvement', '2026-08-21 02:49:39'),
(27, 'CAT-17', 'Baby Products', '2026-08-21 02:49:39'),
(28, 'CAT-18', 'Health & Wellness', '2026-08-21 02:49:39'),
(29, 'CAT-19', 'Video Games & Consoles', '2026-08-21 02:49:39'),
(30, 'CAT-20', 'Software & Applications', '2026-08-21 02:49:39'),
(31, 'CAT-21', 'Home Appliances', '2026-08-21 02:49:39'),
(32, 'CAT-22', 'Kitchenware & Dining', '2026-08-21 02:49:39'),
(33, 'CAT-23', 'Industrial & Scientific', '2026-08-21 02:49:39'),
(34, 'CAT-24', 'Handmade Crafts', '2026-08-21 02:49:39'),
(35, 'CAT-25', 'Art & Painting Supplies', '2026-08-21 02:49:39'),
(36, 'CAT-26', 'Movies & Television', '2026-08-21 02:49:39'),
(37, 'CAT-27', 'Music & Albums', '2026-08-21 02:49:39'),
(38, 'CAT-28', 'Gift Cards', '2026-08-21 02:49:39'),
(39, 'CAT-29', 'Travel Accessories', '2026-08-21 02:49:39'),
(40, 'CAT-30', 'Bags & Luggage', '2026-08-21 02:49:39');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `category_code` (`category_code`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
