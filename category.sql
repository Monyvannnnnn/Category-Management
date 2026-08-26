-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3307
-- Generation Time: Aug 21, 2026 at 05:06 AM
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `category`
--

INSERT INTO `category` (`id`, `category_code`, `category_name`, `created_at`) VALUES
(7, 'ps_1', 'ម៉ាក់ប៊ុក ២០២០', '2026-08-20 09:26:05'),
(9, 'ps_2', 'អាយហ្វូន', '2026-08-20 09:49:48'),
(10, 'ps_3', 'សៀវភៅ', '2026-08-21 02:30:28'),
(11, 'CAT-01', 'អេឡិចត្រូនិក', '2026-08-21 02:49:39'),
(12, 'CAT-02', 'សៀវភៅ និងអក្សរសិល្ប៍', '2026-08-21 02:49:39'),
(13, 'CAT-03', 'សម្លៀកបំពាក់ និងសម្ភារៈ', '2026-08-21 02:49:39'),
(14, 'CAT-04', 'សម្រស់ និងការថែទាំខ្លួន', '2026-08-21 02:49:39'),
(15, 'CAT-05', 'របស់របរកម្សាន្ត និងល្បែង', '2026-08-21 02:49:39'),
(16, 'CAT-06', 'កីឡា និងការកម្សាន្តធម្មជាតិ', '2026-08-21 02:49:39'),
(17, 'CAT-07', 'គ្រឿងទេសន៍ និងអាហារ', '2026-08-21 02:49:39'),
(18, 'CAT-08', 'គ្រឿងសង្ហារឹម និងតុបតែបន់', '2026-08-21 02:49:39'),
(19, 'CAT-09', 'គ្រឿងបង្គុំយានយន្ត', '2026-08-21 02:49:39'),
(20, 'CAT-10', 'គំឿរអលង្ការ និងនាឡិកា', '2026-08-21 02:49:39'),
(21, 'CAT-11', 'ស្បែកជើង និងស្បែកជើង', '2026-08-21 02:49:39'),
(22, 'CAT-12', 'សួន និងថេរ៉ាស', '2026-08-21 02:49:39'),
(23, 'CAT-13', 'ឧបករណ៍តន្ត្រី', '2026-08-21 02:49:39'),
(24, 'CAT-14', 'សម្ភារៈការិយាល័យ', '2026-08-21 02:49:39'),
(25, 'CAT-15', 'សម្ភារៈសត្វចិញ្ចឹម', '2026-08-21 02:49:39'),
(26, 'CAT-16', 'ឧបករណ៍ និងការជួសជុលផ្ទះ', '2026-08-21 02:49:39'),
(27, 'CAT-17', 'ផលិតផលទារក', '2026-08-21 02:49:39'),
(28, 'CAT-18', 'សុខភាព និងសុខុមាលភាព', '2026-08-21 02:49:39'),
(29, 'CAT-19', 'ហ្គេមវីដេអូ និងកុងសូល', '2026-08-21 02:49:39'),
(30, 'CAT-20', 'កម្មវិធីកុំព្យូទ័រ និងកម្មវិធី', '2026-08-21 02:49:39'),
(31, 'CAT-21', 'គ្រឿងប្រើប្រាស់ក្នុងផ្ទះ', '2026-08-21 02:49:39'),
(32, 'CAT-22', 'គ្រឿងផ្ទះបង្ហូអាហារ និងការទទួលទាន', '2026-08-21 02:49:39'),
(33, 'CAT-23', 'ឧស្សាហកម្ម និងវិទ្យាសាស្ត្រ', '2026-08-21 02:49:39'),
(34, 'CAT-24', 'សិប្បកម្មដេរដៃ', '2026-08-21 02:49:39'),
(35, 'CAT-25', 'សម្ភារៈសិល្បៈ និងគំនូរ', '2026-08-21 02:49:39'),
(36, 'CAT-26', 'ភាពយន្ត និងទូរទស្សន៍', '2026-08-21 02:49:39'),
(37, 'CAT-27', 'តន្ត្រី និងអាល់ប៊ុម', '2026-08-21 02:49:39'),
(38, 'CAT-28', 'កាតកំណប់', '2026-08-21 02:49:39'),
(39, 'CAT-29', 'សម្ភារៈធ្វើដំណើរ', '2026-08-21 02:49:39'),
(40, 'CAT-30', 'កាបូប និងកង់ធុង', '2026-08-21 02:49:39');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `category`
--
ALTER TABLE `category`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `category_code` (`category_code`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `category`
--
ALTER TABLE `category`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
