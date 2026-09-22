-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Sep 22, 2026 at 01:06 PM
-- Server version: 11.8.9-MariaDB-log
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u582652079_erp`
--

-- --------------------------------------------------------

--
-- Table structure for table `inv_delivery_note_lines`
--

CREATE TABLE `inv_delivery_note_lines` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `note_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED DEFAULT NULL,
  `description` varchar(255) NOT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT 1.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `inv_delivery_note_lines`
--

INSERT INTO `inv_delivery_note_lines` (`id`, `note_id`, `product_id`, `description`, `quantity`) VALUES
(9, 1, 1, '[PRD-LPT-001] لابتوب ديل XPS 15', 5.00),
(10, 1, 3, '[PRD-FUR-001] كرسي مكتب طبي مريح', 1.00),
(11, 1, 5, '[PRD-SRV-001] عقد صيانة سنوي للأجهزة', 1.00);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `inv_delivery_note_lines`
--
ALTER TABLE `inv_delivery_note_lines`
  ADD PRIMARY KEY (`id`),
  ADD KEY `note_id` (`note_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `inv_delivery_note_lines`
--
ALTER TABLE `inv_delivery_note_lines`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `inv_delivery_note_lines`
--
ALTER TABLE `inv_delivery_note_lines`
  ADD CONSTRAINT `fk_dn_lines_note` FOREIGN KEY (`note_id`) REFERENCES `inv_delivery_notes` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
