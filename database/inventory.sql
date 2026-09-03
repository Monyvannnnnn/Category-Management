-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3307
-- Generation Time: Aug 31, 2026 at 09:42 AM
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
-- Table structure for table `category`
--

CREATE TABLE `category` (
  `id` int(11) NOT NULL,
  `category_code` varchar(50) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `lastupdate` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `category`
--

INSERT INTO `category` (`id`, `category_code`, `category_name`, `created_at`, `lastupdate`) VALUES
(90, 'CAT-001', 'Electronics', '2026-08-27 05:50:33', '2026-08-27 05:50:33'),
(91, 'CAT-002', 'Food & Beverage', '2026-08-27 05:50:33', '2026-08-27 05:50:33'),
(92, 'CAT-003', 'Clothing', '2026-08-27 05:50:33', '2026-08-27 05:50:33'),
(93, 'CAT-004', 'Health & Wellness', '2026-08-27 05:50:33', '2026-08-27 05:50:33'),
(94, 'CAT-005', 'Books & Stationery', '2026-08-27 05:50:33', '2026-08-27 05:50:33'),
(95, '302', 'Vann', '2026-08-27 06:03:48', '2026-08-27 06:03:48'),
(96, 'CAT-006', 'Sports & Fitness', '2026-08-27 06:05:45', '2026-08-27 06:05:45'),
(97, 'CAT-007', 'Home & Garden', '2026-08-27 06:05:45', '2026-08-27 06:05:45'),
(98, 'CAT-008', 'Automotive', '2026-08-27 06:05:45', '2026-08-27 06:05:45'),
(99, 'CAT-009', 'Mobile Phones', '2026-08-27 06:05:45', '2026-08-27 06:05:45'),
(100, 'CAT-010', 'Computers & Laptops', '2026-08-27 06:05:45', '2026-08-27 06:05:45'),
(101, 'CAT-011', 'Toys & Games', '2026-08-27 06:05:45', '2026-08-27 06:05:45'),
(102, 'CAT-012', 'Beauty & Cosmetics', '2026-08-27 06:05:45', '2026-08-27 06:05:45'),
(103, 'CAT-013', 'Baby & Kids', '2026-08-27 06:05:45', '2026-08-27 06:05:45'),
(104, 'CAT-014', 'Pets Supplies', '2026-08-27 06:05:45', '2026-08-27 06:05:45'),
(105, 'CAT-015', 'Books & Media', '2026-08-27 06:05:45', '2026-08-27 06:05:45'),
(106, 'CAT-016', 'Music & Instruments', '2026-08-27 06:05:45', '2026-08-27 06:05:45'),
(107, 'CAT-017', 'Movies & DVD', '2026-08-27 06:05:45', '2026-08-27 06:05:45'),
(108, 'CAT-018', 'Travel & Luggage', '2026-08-27 06:05:45', '2026-08-27 06:05:45'),
(109, 'CAT-019', 'Office Supplies', '2026-08-27 06:05:45', '2026-08-27 06:05:45'),
(110, 'CAT-020', 'Industrial & Tools', '2026-08-27 06:05:45', '2026-08-27 06:05:45'),
(111, 'CAT-021', 'Jewelry & Watches', '2026-08-27 06:05:45', '2026-08-27 06:05:45'),
(112, 'CAT-022', 'Arts & Crafts', '2026-08-27 06:05:45', '2026-08-27 06:05:45'),
(113, 'CAT-023', 'Grocery & Market', '2026-08-27 06:05:45', '2026-08-27 06:05:45'),
(114, 'CAT-024', 'Pharmacy', '2026-08-27 06:05:45', '2026-08-27 06:05:45'),
(115, 'CAT-025', 'Software & Apps', '2026-08-27 06:05:45', '2026-08-27 06:05:45'),
(116, 'CAT-026', 'Furniture', '2026-08-27 06:35:06', '2026-08-27 06:35:06'),
(117, 'CAT-027', 'Kitchen & Dining', '2026-08-27 06:35:06', '2026-08-27 06:35:06'),
(118, 'CAT-028', 'Lighting', '2026-08-27 06:35:06', '2026-08-27 06:35:06'),
(119, 'CAT-029', 'Bedding', '2026-08-27 06:35:06', '2026-08-27 06:35:06'),
(120, 'CAT-030', 'Cleaning Supplies', '2026-08-27 06:35:06', '2026-08-27 06:35:06'),
(121, 'CAT-031', 'Stationery & Office', '2026-08-27 06:35:06', '2026-08-27 06:35:06'),
(122, 'CAT-032', 'Shoes & Footwear', '2026-08-27 06:35:06', '2026-08-27 06:35:06'),
(123, 'CAT-033', 'Bags & Luggage', '2026-08-27 06:35:06', '2026-08-27 06:35:06'),
(124, 'CAT-034', 'Watches & Clocks', '2026-08-27 06:35:06', '2026-08-27 06:35:06'),
(125, 'CAT-035', 'Cameras & Photo', '2026-08-27 06:35:06', '2026-08-27 06:35:06'),
(126, 'CAT-036', 'Gaming Consoles', '2026-08-27 06:35:06', '2026-08-27 06:35:06'),
(127, 'CAT-037', 'Accessories', '2026-08-27 06:35:06', '2026-08-27 06:35:06'),
(128, 'CAT-038', 'Hardware & Tools', '2026-08-27 06:35:06', '2026-08-27 06:35:06'),
(129, 'CAT-039', 'Garden & Outdoor', '2026-08-27 06:35:06', '2026-08-27 06:35:06'),
(130, 'CAT-040', 'Automotive Parts', '2026-08-27 06:35:06', '2026-08-27 06:35:06'),
(131, 'CAT-041', 'Pet Food', '2026-08-27 06:35:06', '2026-08-27 06:35:06'),
(132, 'CAT-042', 'Baby Gear', '2026-08-27 06:35:06', '2026-08-27 06:35:06'),
(133, 'CAT-043', 'Sports Equipment', '2026-08-27 06:35:06', '2026-08-27 06:35:06'),
(134, 'CAT-044', 'Musical Gear', '2026-08-27 06:35:06', '2026-08-27 06:35:06'),
(135, 'CAT-045', 'Digital Services', '2026-08-27 06:35:06', '2026-08-27 06:35:06'),
(136, 'CAT-99', 'ខ្មែរ', '2026-08-27 09:23:53', '2026-08-27 09:23:53'),
(137, 'CAT-22', 'Water Bottle', '2026-08-31 04:08:06', '2026-08-31 04:08:06'),
(138, 'CAT-44', 'Door', '2026-08-31 04:19:50', '2026-08-31 04:19:50'),
(139, 'CAT-23', 'Bin', '2026-08-31 04:20:31', '2026-08-31 04:20:31');

-- --------------------------------------------------------

--
-- Table structure for table `grid_state`
--

CREATE TABLE `grid_state` (
  `id` int(11) NOT NULL,
  `grid_name` varchar(100) NOT NULL,
  `state_json` longtext DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `grid_state`
--

INSERT INTO `grid_state` (`id`, `grid_name`, `state_json`, `updated_at`) VALUES
(1, 'categoryGrid', '{\"columns\":[{\"visibleIndex\":0,\"dataField\":\"category_code\",\"name\":\"col_category_code\",\"dataType\":\"string\",\"visible\":true,\"fixed\":true,\"fixedPosition\":\"left\"},{\"visibleIndex\":1,\"dataField\":\"category_name\",\"name\":\"col_category_name\",\"dataType\":\"string\",\"visible\":true,\"fixed\":true,\"fixedPosition\":\"sticky\"},{\"visibleIndex\":2,\"dataField\":\"created_at\",\"name\":\"col_date_created\",\"dataType\":\"datetime\",\"visible\":true,\"fixed\":true,\"fixedPosition\":\"left\"},{\"visibleIndex\":3,\"dataField\":\"created_at\",\"name\":\"col_created_date\",\"dataType\":\"date\",\"visible\":true,\"sortOrder\":\"asc\",\"sortIndex\":0,\"fixed\":true,\"fixedPosition\":\"sticky\"},{\"visibleIndex\":4,\"dataField\":\"created_at\",\"name\":\"col_created_time\",\"dataType\":\"datetime\",\"visible\":true,\"fixed\":true,\"fixedPosition\":\"sticky\"},{\"visibleIndex\":5,\"dataField\":\"created_at\",\"name\":\"col_formatted_date\",\"dataType\":\"date\",\"visible\":true},{\"visibleIndex\":6,\"dataField\":\"created_at\",\"name\":\"col_formatted_time\",\"dataType\":\"datetime\",\"visible\":true},{\"visibleIndex\":7,\"dataField\":\"created_at\",\"name\":\"col_formatted_datetime\",\"dataType\":\"datetime\",\"visible\":true},{\"visibleIndex\":8,\"dataField\":\"lastupdate\",\"name\":\"col_last_updated\",\"dataType\":\"datetime\",\"visible\":true,\"fixed\":true,\"fixedPosition\":\"sticky\"},{\"visibleIndex\":9,\"dataField\":\"lastupdate\",\"name\":\"col_last_date\",\"dataType\":\"date\",\"visible\":true},{\"visibleIndex\":10,\"dataField\":\"lastupdate\",\"name\":\"col_last_time\",\"dataType\":\"datetime\",\"visible\":true},{\"visibleIndex\":11,\"dataField\":\"lastupdate\",\"name\":\"col_time_ago\",\"dataType\":\"datetime\",\"visible\":true},{\"visibleIndex\":12,\"name\":\"col_action\",\"width\":130,\"visible\":true,\"fixed\":true,\"fixedPosition\":\"right\"}],\"allowedPageSizes\":[5,10,20],\"filterPanel\":{\"filterEnabled\":true},\"filterValue\":null,\"searchText\":\"\",\"pageIndex\":0,\"pageSize\":10,\"selectedRowKeys\":[]}', '2026-08-24 08:53:27');

-- --------------------------------------------------------

--
-- Table structure for table `product`
--

CREATE TABLE `product` (
  `id` int(11) NOT NULL,
  `product_code` varchar(50) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `category_id` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `lastupdate` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product`
--

INSERT INTO `product` (`id`, `product_code`, `product_name`, `category_id`, `price`, `quantity`, `created_at`, `lastupdate`) VALUES
(1, 'CD-33', 'harry poter', 105, 40.00, 10, '2026-08-31 14:18:28', '2026-08-31 14:18:28');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `category`
--
ALTER TABLE `category`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `category_code` (`category_code`),
  ADD UNIQUE KEY `category_name` (`category_name`),
  ADD UNIQUE KEY `category_name_2` (`category_name`),
  ADD UNIQUE KEY `category_name_3` (`category_name`);

--
-- Indexes for table `grid_state`
--
ALTER TABLE `grid_state`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_grid_name` (`grid_name`);

--
-- Indexes for table `product`
--
ALTER TABLE `product`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `product_code` (`product_code`),
  ADD KEY `category_id` (`category_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `category`
--
ALTER TABLE `category`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=140;

--
-- AUTO_INCREMENT for table `grid_state`
--
ALTER TABLE `grid_state`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `product`
--
ALTER TABLE `product`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `product`
--
ALTER TABLE `product`
  ADD CONSTRAINT `fk_product_category` FOREIGN KEY (`category_id`) REFERENCES `category` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
