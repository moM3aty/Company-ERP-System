-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Sep 21, 2026 at 11:09 AM
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
-- Table structure for table `accounts`
--

CREATE TABLE `accounts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL DEFAULT 1,
  `branch_id` int(11) DEFAULT 0,
  `code` varchar(50) NOT NULL,
  `name_en` varchar(255) NOT NULL,
  `name_ar` varchar(255) NOT NULL,
  `type` enum('asset','liability','equity','revenue','expense') NOT NULL,
  `is_control_account` tinyint(1) NOT NULL DEFAULT 0,
  `parent_id` bigint(20) UNSIGNED DEFAULT NULL,
  `account_level` int(11) NOT NULL DEFAULT 1,
  `is_parent` tinyint(1) NOT NULL DEFAULT 0,
  `current_balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `opening_balance` decimal(15,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `accounts`
--

INSERT INTO `accounts` (`id`, `company_id`, `branch_id`, `code`, `name_en`, `name_ar`, `type`, `is_control_account`, `parent_id`, `account_level`, `is_parent`, `current_balance`, `is_active`, `created_at`, `opening_balance`) VALUES
(1, 1, 1, '1110', 'Main Bank Account', 'حساب البنك الرئيسي', 'asset', 0, NULL, 1, 0, 8000.00, 1, '2026-08-22 12:53:33', 0.00),
(2, 1, 1, '1120', 'Main Cashbox', 'الخزينة الرئيسية', 'asset', 0, NULL, 1, 0, 450000.00, 1, '2026-08-22 12:53:33', 0.00),
(3, 1, 2, '4100', 'Sales Revenue', 'إيرادات المبيعات', 'revenue', 0, NULL, 1, 0, 600.00, 1, '2026-08-22 12:53:33', 0.00),
(4, 1, 2, '2110', 'Accounts Payable', 'الدائنون (الموردين)', 'liability', 1, NULL, 1, 0, -600.00, 1, '2026-08-22 12:53:33', 0.00),
(6, 1, 3, '6100', 'Payroll Expense', 'مصروفات الرواتب', 'expense', 0, NULL, 1, 0, 0.00, 1, '2026-08-22 12:53:33', 0.00),
(7, 1, 3, '9517', '', 'محمد ابوالمعاطي', 'asset', 0, NULL, 1, 1, 20000.00, 1, '2026-08-23 19:41:10', 20000.00),
(8, 1, 1, '111002', 'Mohamed Abo-Elmaaty', 'محمد ابوالمعاطي', 'asset', 0, NULL, 1, 0, 8000.00, 1, '2026-08-25 03:12:48', 0.00),
(10, 1, 2, '111102', 'Jeddah Branch Safe', 'خزينة فرع جدة', 'asset', 0, NULL, 1, 0, 125000.00, 1, '2026-09-21 09:12:53', 0.00),
(11, 1, 2, '111202', 'NCB Bank - Jeddah', 'البنك الأهلي - فرع جدة', 'asset', 0, NULL, 1, 0, 380000.00, 1, '2026-09-21 09:12:53', 0.00),
(12, 1, 3, '111103', 'Muscat Branch Safe', 'خزينة فرع مسقط', 'asset', 0, NULL, 1, 0, 45000.00, 1, '2026-09-21 09:12:53', 0.00),
(13, 1, 3, '111203', 'Bank Muscat Account', 'بنك مسقط الرئيسي', 'asset', 0, NULL, 1, 0, 210000.00, 1, '2026-09-21 09:12:53', 0.00),
(14, 1, 4, '111104', 'Dubai Branch Safe', 'خزينة فرع دبي', 'asset', 0, NULL, 1, 0, 95000.00, 1, '2026-09-21 09:12:53', 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `asset_depreciations`
--

CREATE TABLE `asset_depreciations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `asset_id` bigint(20) UNSIGNED NOT NULL,
  `journal_entry_id` bigint(20) UNSIGNED DEFAULT NULL,
  `depreciation_date` date NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `asset_depreciations`
--

INSERT INTO `asset_depreciations` (`id`, `asset_id`, `journal_entry_id`, `depreciation_date`, `amount`, `notes`, `created_at`) VALUES
(1, 1, 3, '2026-08-23', 50.00, 'إهلاك شهري آلي', '2026-08-23 20:50:46');

-- --------------------------------------------------------

--
-- Table structure for table `bank_reconciliations`
--

CREATE TABLE `bank_reconciliations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED DEFAULT 1,
  `reconciliation_number` varchar(50) NOT NULL,
  `account_id` bigint(20) UNSIGNED NOT NULL,
  `reconciliation_date` date NOT NULL,
  `branch_id` int(11) DEFAULT 0,
  `statement_date` date NOT NULL,
  `statement_balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `system_balance` decimal(15,2) DEFAULT 0.00,
  `book_balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `cleared_debits` decimal(15,2) NOT NULL DEFAULT 0.00,
  `cleared_credits` decimal(15,2) NOT NULL DEFAULT 0.00,
  `outstanding_deposits` decimal(15,2) NOT NULL DEFAULT 0.00,
  `outstanding_payments` decimal(15,2) NOT NULL DEFAULT 0.00,
  `adjusted_bank_balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `adjusted_book_balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `difference` decimal(15,2) NOT NULL DEFAULT 0.00,
  `status` enum('draft','reconciled') NOT NULL DEFAULT 'draft',
  `notes` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `bank_reconciliations`
--

INSERT INTO `bank_reconciliations` (`id`, `company_id`, `reconciliation_number`, `account_id`, `reconciliation_date`, `branch_id`, `statement_date`, `statement_balance`, `system_balance`, `book_balance`, `cleared_debits`, `cleared_credits`, `outstanding_deposits`, `outstanding_payments`, `adjusted_bank_balance`, `adjusted_book_balance`, `difference`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(4, 1, 'BR-26080001', 4, '0000-00-00', 1, '2026-08-23', -600.00, 0.00, -600.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'reconciled', '', '2026-08-23 21:17:13', '2026-09-07 11:16:09');

-- --------------------------------------------------------

--
-- Table structure for table `bank_reconciliation_items`
--

CREATE TABLE `bank_reconciliation_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `reconciliation_id` bigint(20) UNSIGNED NOT NULL,
  `journal_entry_item_id` bigint(20) UNSIGNED NOT NULL,
  `is_cleared` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `branches`
--

CREATE TABLE `branches` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `branch_code` varchar(50) NOT NULL,
  `name_ar` varchar(255) NOT NULL,
  `name_en` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `budgets`
--

CREATE TABLE `budgets` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED DEFAULT 1,
  `branch_id` int(11) DEFAULT 0,
  `name_ar` varchar(255) NOT NULL,
  `name_en` varchar(255) DEFAULT NULL,
  `code` varchar(50) NOT NULL,
  `fiscal_year` int(11) NOT NULL,
  `total_amount` decimal(15,2) DEFAULT 0.00,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('draft','approved','closed') NOT NULL DEFAULT 'draft',
  `total_allocated` decimal(15,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `account_id` int(11) DEFAULT NULL,
  `cost_center_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `budgets`
--

INSERT INTO `budgets` (`id`, `company_id`, `branch_id`, `name_ar`, `name_en`, `code`, `fiscal_year`, `total_amount`, `start_date`, `end_date`, `status`, `total_allocated`, `notes`, `created_at`, `updated_at`, `account_id`, `cost_center_id`, `is_active`) VALUES
(1, 1, 1, 'اسم / عنوان الموازنة *', NULL, 'CUST-61551787404225', 2026, 0.00, '2026-01-01', '2026-12-31', 'approved', 4800.00, '', '2026-08-23 20:53:46', '2026-09-07 10:29:01', NULL, NULL, 1),
(2, 1, 1, 'مكتب خشبي إداري حديث', NULL, 'CC-200', 2026, 0.00, '2026-01-01', '2026-12-31', 'draft', 3800.00, '', '2026-08-23 20:55:49', '2026-09-07 10:29:04', NULL, NULL, 1),
(3, 1, 2, 'Mohamed Abo-Elmaaty', NULL, '+6502+84', 2026, 0.00, '2026-01-01', '2026-12-31', 'closed', 7200.00, '', '2026-08-23 20:56:16', '2026-09-07 10:29:08', NULL, NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `budget_items`
--

CREATE TABLE `budget_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `budget_id` bigint(20) UNSIGNED NOT NULL,
  `account_id` bigint(20) UNSIGNED NOT NULL,
  `allocated_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `notes` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `budget_items`
--

INSERT INTO `budget_items` (`id`, `budget_id`, `account_id`, `allocated_amount`, `notes`) VALUES
(4, 1, 3, 2000.00, ''),
(5, 1, 6, 1500.00, ''),
(6, 1, 3, 1300.00, ''),
(7, 2, 6, 1500.00, ''),
(8, 2, 3, 300.00, ''),
(9, 2, 6, 2000.00, ''),
(10, 3, 3, 2000.00, ''),
(11, 3, 6, 4200.00, ''),
(12, 3, 3, 1000.00, '');

-- --------------------------------------------------------

--
-- Table structure for table `companies`
--

CREATE TABLE `companies` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_code` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `legal_name` varchar(255) DEFAULT NULL,
  `tax_number` varchar(100) DEFAULT NULL,
  `commercial_register` varchar(100) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `timezone` varchar(100) DEFAULT 'Asia/Riyadh',
  `status` enum('active','suspended') DEFAULT 'active',
  `base_currency` varchar(10) DEFAULT 'SAR',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `companies`
--

INSERT INTO `companies` (`id`, `company_code`, `name`, `legal_name`, `tax_number`, `commercial_register`, `email`, `phone`, `address`, `timezone`, `status`, `base_currency`, `created_at`, `updated_at`) VALUES
(1, 'NT-001', 'Nour Trust Enterprise', NULL, NULL, NULL, NULL, NULL, NULL, 'Asia/Riyadh', 'active', 'SAR', '2026-08-22 12:53:33', '2026-08-22 12:53:33');

-- --------------------------------------------------------

--
-- Table structure for table `cost_centers`
--

CREATE TABLE `cost_centers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL DEFAULT 1,
  `branch_id` int(11) DEFAULT 0,
  `code` varchar(50) NOT NULL,
  `name_en` varchar(255) NOT NULL,
  `budget_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `parent_id` bigint(20) UNSIGNED DEFAULT NULL,
  `is_parent` tinyint(1) NOT NULL DEFAULT 0,
  `name_ar` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cost_centers`
--

INSERT INTO `cost_centers` (`id`, `company_id`, `branch_id`, `code`, `name_en`, `budget_amount`, `parent_id`, `is_parent`, `name_ar`, `is_active`, `created_at`) VALUES
(1, 1, 1, 'CC-100', 'Administration', 2000.00, NULL, 1, 'الإدارة العامة', 1, '2026-08-22 12:53:33'),
(2, 1, 1, 'CC-200', 'Sales Dept', 5000.00, 1, 0, 'قسم المبيعات', 1, '2026-08-22 12:53:33');

-- --------------------------------------------------------

--
-- Table structure for table `crm_leads`
--

CREATE TABLE `crm_leads` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL DEFAULT 1,
  `branch_id` int(11) DEFAULT NULL,
  `company_name` varchar(255) NOT NULL,
  `contact_person` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `source` varchar(100) DEFAULT 'Website',
  `status` varchar(50) DEFAULT 'new',
  `score` int(11) DEFAULT 10,
  `next_follow_up` date DEFAULT NULL,
  `owner_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `crm_leads`
--

INSERT INTO `crm_leads` (`id`, `company_id`, `branch_id`, `company_name`, `contact_person`, `email`, `phone`, `source`, `status`, `score`, `next_follow_up`, `owner_id`, `created_at`) VALUES
(7, 1, 1, 'Nour Trust', 'Mohamed Abo-Elmaaty', 'moie.m3aty@gmail.com', '01273844735', 'Referral', 'converted', 10, '2026-08-23', 1, '2026-08-22 15:26:32'),
(8, 1, 1, 'Nour Trust', 'Mohamed Abo-Elmaaty', 'mome.m3aty@gmail.com', '01897444735', 'Referral', 'contacted', 50, '2026-08-23', 1, '2026-08-22 18:50:10'),
(9, 1, 1, 'Ahmed Salim ', 'Ahmed Salim', 'workahmed372@gmail.com', '+966501234567', 'Website', 'new', 10, '2026-08-29', 1, '2026-08-22 23:58:35'),
(10, 1, 1, 'Huda Ali Khamis ', 'Huda Ali ', 'huda@gmail.com', '057823890890787', 'Social Media', 'new', 10, '2026-08-31', 1, '2026-08-22 23:59:55'),
(11, 1, 1, 'شركة الحلول المتقدمة', 'مهندس طارق السعيد', 'tareq@solutions.com', '0501122334', 'Website', 'new', 20, '2026-09-28', 1, '2026-09-21 11:09:41'),
(12, 1, 2, 'مؤسسة الرياض للتجارة', 'أستاذ فهد العتيبي', 'fahad@riyadh-trade.com', '0554433221', 'Referral', 'contacted', 60, '2026-09-25', 1, '2026-09-21 11:09:41');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL DEFAULT 1,
  `branch_id` int(11) DEFAULT NULL,
  `code` varchar(50) NOT NULL,
  `name_en` varchar(255) NOT NULL,
  `name_ar` varchar(255) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `type` enum('B2B','B2C') DEFAULT 'B2B',
  `credit_limit` decimal(15,2) DEFAULT 0.00,
  `balance` decimal(15,2) DEFAULT 0.00,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `address` text DEFAULT NULL,
  `tax_number` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `company_id`, `branch_id`, `code`, `name_en`, `name_ar`, `email`, `phone`, `type`, `credit_limit`, `balance`, `is_active`, `created_at`, `address`, `tax_number`) VALUES
(2, 1, 1, 'CUST-41291787403275', 'Mohamed Abo-Elmaaty', 'محمد ابوالمعاطي', 'momo.m3aty@gmail.com', '01124743148', 'B2B', 4000.00, 0.00, 1, '2026-08-22 12:54:35', NULL, NULL),
(8, 1, 1, 'CUST-61551787404225', 'Mohamed Abo-Elmaaty', 'علاء السيد', 'mo.m3aty@gmail.com', '01275844735', 'B2C', 5000.00, 0.00, 1, '2026-08-22 13:10:25', '', '976465461968456413'),
(11, 1, 1, 'CUST-1787424543', 'Nour Trust', 'Nour Trust', 'moie.m3aty@gmail.com', '01273844735', 'B2B', 0.00, 0.00, 1, '2026-08-22 18:49:03', NULL, NULL),
(12, 1, 2, 'Cust-0000211', 'Abdullah Ahmed Abdullraheem ', 'عبدالله أحمد عبدالرحيم ', 'workahmed372@gmail.com', '+966501234567', 'B2B', 1000.00, 0.00, 1, '2026-08-23 00:41:51', 'Makka', 'KSA0987888989'),
(13, 1, 2, 'CUS - 0009898', 'Khalied Salim Ali Al-barak ', 'خالد سالم علي البراك', 'Khalied@gmail.com', '0535488493', 'B2B', 30000.00, 0.00, 1, '2026-08-23 00:43:38', 'Makka ', 'KSA09090909888');

-- --------------------------------------------------------

--
-- Table structure for table `delivery_notes`
--

CREATE TABLE `delivery_notes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED DEFAULT 1,
  `branch_id` int(11) DEFAULT 0,
  `delivery_number` varchar(50) NOT NULL,
  `warehouse_id` bigint(20) UNSIGNED NOT NULL,
  `customer_name` varchar(255) NOT NULL,
  `delivery_date` date NOT NULL,
  `status` enum('draft','delivered','cancelled') NOT NULL DEFAULT 'draft',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `delivery_notes`
--

INSERT INTO `delivery_notes` (`id`, `company_id`, `branch_id`, `delivery_number`, `warehouse_id`, `customer_name`, `delivery_date`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'DN-26082284', 2, 'محمد ابوالمعاطي', '2026-08-22', 'draft', '', '2026-08-22 23:20:43', '2026-09-07 23:01:40'),
(2, 1, 2, 'DN-26082319', 2, 'khalid Abdullraheem', '2026-08-23', 'draft', 'as per the term and condition ', '2026-08-23 00:47:57', '2026-09-07 23:01:42');

-- --------------------------------------------------------

--
-- Table structure for table `delivery_note_items`
--

CREATE TABLE `delivery_note_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `delivery_note_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT 1.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `delivery_note_items`
--

INSERT INTO `delivery_note_items` (`id`, `delivery_note_id`, `product_id`, `quantity`) VALUES
(1, 1, 1, 80.00),
(2, 2, 6, 1000.00),
(3, 2, 2, 300.00);

-- --------------------------------------------------------

--
-- Table structure for table `fiscal_periods`
--

CREATE TABLE `fiscal_periods` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` int(11) DEFAULT 1,
  `branch_id` int(11) DEFAULT 0,
  `name_ar` varchar(255) NOT NULL,
  `name_en` varchar(255) DEFAULT NULL,
  `period_name` varchar(100) NOT NULL,
  `fiscal_year_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('open','locked','closed') NOT NULL DEFAULT 'open',
  `closed_at` timestamp NULL DEFAULT NULL,
  `closing_journal_id` bigint(20) UNSIGNED DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `fiscal_periods`
--

INSERT INTO `fiscal_periods` (`id`, `company_id`, `branch_id`, `name_ar`, `name_en`, `period_name`, `fiscal_year_id`, `name`, `start_date`, `end_date`, `status`, `closed_at`, `closing_journal_id`, `notes`) VALUES
(2, 1, 1, '', NULL, 'السنة المالية 2026', NULL, '', '2026-01-01', '2026-06-09', 'closed', '2026-08-23 21:46:04', NULL, ''),
(3, 1, 2, 'Period Name (AR) *', 'Period Name (EN)', 'السنة المالية 2026', NULL, '', '2026-06-10', '2026-12-31', 'open', NULL, NULL, ''),
(4, 1, 2, 'Period Name (AR) *', 'Period Name (EN)', '', NULL, '', '2026-01-01', '2026-12-31', 'open', NULL, NULL, '');

-- --------------------------------------------------------

--
-- Table structure for table `fiscal_sub_periods`
--

CREATE TABLE `fiscal_sub_periods` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `fiscal_period_id` bigint(20) UNSIGNED NOT NULL,
  `period_number` int(11) NOT NULL,
  `name_ar` varchar(100) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('open','soft_lock','closed') NOT NULL DEFAULT 'open',
  `closed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `fiscal_sub_periods`
--

INSERT INTO `fiscal_sub_periods` (`id`, `fiscal_period_id`, `period_number`, `name_ar`, `start_date`, `end_date`, `status`, `closed_at`) VALUES
(1, 2, 1, 'شهر يناير (2026-01)', '2026-01-01', '2026-01-31', 'open', NULL),
(2, 2, 2, 'شهر فبراير (2026-02)', '2026-02-01', '2026-02-28', 'open', NULL),
(3, 2, 3, 'شهر مارس (2026-03)', '2026-03-01', '2026-03-31', 'open', NULL),
(4, 2, 4, 'شهر أبريل (2026-04)', '2026-04-01', '2026-04-30', 'open', NULL),
(5, 2, 5, 'شهر مايو (2026-05)', '2026-05-01', '2026-05-31', 'open', NULL),
(6, 2, 6, 'شهر يونيو (2026-06)', '2026-06-01', '2026-06-30', 'open', NULL),
(7, 2, 7, 'شهر يوليو (2026-07)', '2026-07-01', '2026-07-31', 'open', NULL),
(8, 2, 8, 'شهر أغسطس (2026-08)', '2026-08-01', '2026-08-31', 'open', NULL),
(9, 2, 9, 'شهر سبتمبر (2026-09)', '2026-09-01', '2026-09-30', 'open', NULL),
(10, 2, 10, 'شهر أكتوبر (2026-10)', '2026-10-01', '2026-10-31', 'open', NULL),
(11, 2, 11, 'شهر نوفمبر (2026-11)', '2026-11-01', '2026-11-30', 'open', NULL),
(12, 2, 12, 'شهر ديسمبر (2026-12)', '2026-12-01', '2026-12-31', 'open', NULL),
(13, 3, 1, 'شهر يناير (2026-06)', '2026-06-10', '2026-06-30', 'soft_lock', NULL),
(14, 3, 2, 'شهر فبراير (2026-07)', '2026-07-10', '2026-07-31', 'open', NULL),
(15, 3, 3, 'شهر مارس (2026-08)', '2026-08-10', '2026-08-31', 'open', NULL),
(16, 3, 4, 'شهر أبريل (2026-09)', '2026-09-10', '2026-09-30', 'open', NULL),
(17, 3, 5, 'شهر مايو (2026-10)', '2026-10-10', '2026-10-31', 'open', NULL),
(18, 3, 6, 'شهر يونيو (2026-11)', '2026-11-10', '2026-11-30', 'open', NULL),
(19, 3, 7, 'شهر يوليو (2026-12)', '2026-12-10', '2026-12-31', 'open', NULL),
(20, 3, 8, 'شهر أغسطس (2027-01)', '2027-01-10', '2027-01-31', 'open', NULL),
(21, 3, 9, 'شهر سبتمبر (2027-02)', '2027-02-10', '2027-02-28', 'open', NULL),
(22, 3, 10, 'شهر أكتوبر (2027-03)', '2027-03-10', '2027-03-31', 'open', NULL),
(23, 3, 11, 'شهر نوفمبر (2027-04)', '2027-04-10', '2027-04-30', 'open', NULL),
(24, 3, 12, 'شهر ديسمبر (2027-05)', '2027-05-10', '2027-05-31', 'open', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `fiscal_years`
--

CREATE TABLE `fiscal_years` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('open','closed') NOT NULL DEFAULT 'open',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fixed_assets`
--

CREATE TABLE `fixed_assets` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED DEFAULT 1,
  `branch_id` int(11) DEFAULT 0,
  `code` varchar(50) NOT NULL,
  `name_ar` varchar(255) NOT NULL,
  `name_en` varchar(255) DEFAULT NULL,
  `category` varchar(100) DEFAULT 'general',
  `purchase_date` date NOT NULL,
  `purchase_cost` decimal(15,2) NOT NULL DEFAULT 0.00,
  `salvage_value` decimal(15,2) NOT NULL DEFAULT 0.00,
  `useful_life_years` int(11) NOT NULL DEFAULT 5,
  `depreciation_method` enum('straight_line','declining_balance') NOT NULL DEFAULT 'straight_line',
  `accumulated_depreciation` decimal(15,2) NOT NULL DEFAULT 0.00,
  `book_value` decimal(15,2) NOT NULL DEFAULT 0.00,
  `asset_account_id` bigint(20) UNSIGNED DEFAULT NULL,
  `dep_expense_account_id` bigint(20) UNSIGNED DEFAULT NULL,
  `acc_dep_account_id` bigint(20) UNSIGNED DEFAULT NULL,
  `cost_center_id` bigint(20) UNSIGNED DEFAULT NULL,
  `status` enum('active','fully_depreciated','disposed') NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `fixed_assets`
--

INSERT INTO `fixed_assets` (`id`, `company_id`, `branch_id`, `code`, `name_ar`, `name_en`, `category`, `purchase_date`, `purchase_cost`, `salvage_value`, `useful_life_years`, `depreciation_method`, `accumulated_depreciation`, `book_value`, `asset_account_id`, `dep_expense_account_id`, `acc_dep_account_id`, `cost_center_id`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 1, '07775000', 'محمد ابوالمعاطي', '', 'vehicles', '2026-08-23', 5000.00, 2000.00, 5, 'straight_line', 50.00, 4950.00, 1, 4, 2, 2, 'active', '2026-08-23 20:48:23', '2026-09-07 10:19:36'),
(2, 1, 1, 'CC-100', 'محمد ابوالمعاطي', '', 'buildings', '2026-08-23', 5000.00, 2000.00, 5, 'straight_line', 0.00, 5000.00, 3, 6, 4, 2, 'active', '2026-08-23 20:51:22', '2026-09-07 10:19:39');

-- --------------------------------------------------------

--
-- Table structure for table `goods_receipts`
--

CREATE TABLE `goods_receipts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED DEFAULT 1,
  `po_id` bigint(20) UNSIGNED DEFAULT NULL,
  `supplier_id` bigint(20) UNSIGNED NOT NULL,
  `receipt_number` varchar(50) NOT NULL,
  `delivery_note_number` varchar(100) DEFAULT NULL,
  `receipt_date` date NOT NULL,
  `received_by` varchar(255) DEFAULT NULL,
  `status` enum('draft','inspected','accepted','rejected') NOT NULL DEFAULT 'accepted',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `goods_receipts`
--

INSERT INTO `goods_receipts` (`id`, `company_id`, `po_id`, `supplier_id`, `receipt_number`, `delivery_note_number`, `receipt_date`, `received_by`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, 'GRN-260822-426', '64635165', '2026-08-22', 'محمد ابوالمعاطي ', 'accepted', '', '2026-08-22 21:18:04', '2026-08-22 21:18:04');

-- --------------------------------------------------------

--
-- Table structure for table `goods_receipt_items`
--

CREATE TABLE `goods_receipt_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `receipt_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED DEFAULT NULL,
  `description` varchar(255) NOT NULL,
  `quantity_received` decimal(10,2) NOT NULL DEFAULT 1.00,
  `quantity_accepted` decimal(10,2) NOT NULL DEFAULT 1.00,
  `quantity_rejected` decimal(10,2) NOT NULL DEFAULT 0.00,
  `unit_price` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_price` decimal(15,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `goods_receipt_items`
--

INSERT INTO `goods_receipt_items` (`id`, `receipt_id`, `product_id`, `description`, `quantity_received`, `quantity_accepted`, `quantity_rejected`, `unit_price`, `total_price`) VALUES
(1, 1, 3, 'حبر طابعه ', 4.00, 3.00, 1.00, 50.00, 150.00);

-- --------------------------------------------------------

--
-- Table structure for table `hr_appraisals`
--

CREATE TABLE `hr_appraisals` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED DEFAULT 1,
  `branch_id` int(11) DEFAULT 0,
  `appraisal_code` varchar(50) NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `evaluator_name` varchar(255) DEFAULT NULL,
  `appraisal_period` varchar(100) NOT NULL,
  `appraisal_date` date NOT NULL,
  `score` decimal(5,2) NOT NULL DEFAULT 0.00,
  `rating_grade` varchar(50) DEFAULT 'Good',
  `status` enum('draft','submitted','approved') NOT NULL DEFAULT 'draft',
  `remarks` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `hr_appraisals`
--

INSERT INTO `hr_appraisals` (`id`, `company_id`, `branch_id`, `appraisal_code`, `employee_id`, `evaluator_name`, `appraisal_period`, `appraisal_date`, `score`, `rating_grade`, `status`, `remarks`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'APR-2026-001', 1, 'المُقَيِّم المسؤول', 'Annual 2026', '2026-08-27', 100.00, 'Excellent', 'submitted', 'التوصيات وملاحظات الإدارة', 1, '2026-08-27 04:53:31', '2026-09-21 10:56:30');

-- --------------------------------------------------------

--
-- Table structure for table `hr_attendance`
--

CREATE TABLE `hr_attendance` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `date` date NOT NULL,
  `check_in` time DEFAULT NULL,
  `check_out` time DEFAULT NULL,
  `status` enum('present','absent','late','half_day','on_leave') NOT NULL DEFAULT 'present',
  `work_hours` decimal(5,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `hr_attendance`
--

INSERT INTO `hr_attendance` (`id`, `employee_id`, `date`, `check_in`, `check_out`, `status`, `work_hours`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 1, '2026-08-27', '21:13:00', '21:13:00', 'present', 0.00, '', 1, '2026-08-27 04:11:42', '2026-08-27 04:11:42');

-- --------------------------------------------------------

--
-- Table structure for table `hr_departments`
--

CREATE TABLE `hr_departments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(50) NOT NULL,
  `name_ar` varchar(255) NOT NULL,
  `name_en` varchar(255) DEFAULT NULL,
  `parent_id` bigint(20) UNSIGNED DEFAULT NULL,
  `manager_name` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `description` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `hr_departments`
--

INSERT INTO `hr_departments` (`id`, `code`, `name_ar`, `name_en`, `parent_id`, `manager_name`, `status`, `description`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'DEP-001', 'اسم الإدارة (عربي) *', 'اسم الإدارة (إنجليزي)', NULL, 'محمد أبوالمعاطي', 'active', 'الوصف والمهام الوظيفية للإدارة', 1, '2026-08-27 03:51:52', '2026-08-27 03:51:52');

-- --------------------------------------------------------

--
-- Table structure for table `hr_designations`
--

CREATE TABLE `hr_designations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(50) NOT NULL,
  `title_ar` varchar(255) NOT NULL,
  `title_en` varchar(255) DEFAULT NULL,
  `department_id` bigint(20) UNSIGNED DEFAULT NULL,
  `pay_grade` varchar(50) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `description` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `hr_designations`
--

INSERT INTO `hr_designations` (`id`, `code`, `title_ar`, `title_en`, `department_id`, `pay_grade`, `status`, `description`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'DSG-001', 'المسمى الوظيفي (عربي) *', 'المسمى الوظيفي (إنجليزي)', 1, 'الدرجة المالية (Pay Grade)', 'active', 'الوصف الوظيفي والمسؤوليات الرئيسية', 1, '2026-08-27 03:54:00', '2026-08-27 03:54:00');

-- --------------------------------------------------------

--
-- Table structure for table `hr_documents`
--

CREATE TABLE `hr_documents` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `document_code` varchar(50) NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `document_type` enum('passport','national_id','contract','certificate','visa','other') NOT NULL DEFAULT 'passport',
  `title_ar` varchar(255) NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `issue_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `status` enum('active','expired','pending_renewal') NOT NULL DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `hr_documents`
--

INSERT INTO `hr_documents` (`id`, `document_code`, `employee_id`, `document_type`, `title_ar`, `file_path`, `issue_date`, `expiry_date`, `status`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(2, 'DOC-2026-0001', 1, 'passport', 'عنوان / مسمى الوثيقة *', '/uploads/hr_documents/EMP-0001/1787818568_6a8ff2483db9d.pdf', '2026-08-27', '2026-08-31', 'active', 'ملاحظات وشروط إضافية', 1, '2026-08-27 05:11:42', '2026-08-27 08:16:08'),
(3, 'DOC-EXP-001', 1, 'passport', 'جواز سفر - محمد ابوالمعاطي', NULL, '2021-10-01', '2026-10-03', 'active', 'يتطلب تجديد عاجل', 1, '2026-09-21 11:09:41', '2026-09-21 11:09:41'),
(4, 'DOC-EXP-002', 1, 'visa', 'إقامة عمل - محمد ابوالمعاطي', NULL, '2023-01-01', '2026-10-16', 'active', 'تجديد تصريح العمل', 1, '2026-09-21 11:09:41', '2026-09-21 11:09:41');

-- --------------------------------------------------------

--
-- Table structure for table `hr_employees`
--

CREATE TABLE `hr_employees` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `emp_code` varchar(50) NOT NULL,
  `name_ar` varchar(255) NOT NULL,
  `name_en` varchar(255) DEFAULT NULL,
  `national_id` varchar(50) DEFAULT NULL,
  `passport_no` varchar(50) DEFAULT NULL,
  `gender` enum('male','female') NOT NULL DEFAULT 'male',
  `dob` date DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `department_id` bigint(20) UNSIGNED DEFAULT NULL,
  `designation_id` bigint(20) UNSIGNED DEFAULT NULL,
  `joining_date` date NOT NULL,
  `employment_type` enum('full_time','part_time','contract','probation') NOT NULL DEFAULT 'full_time',
  `basic_salary` decimal(15,2) NOT NULL DEFAULT 0.00,
  `status` enum('active','on_leave','resigned','terminated') NOT NULL DEFAULT 'active',
  `address` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `hr_employees`
--

INSERT INTO `hr_employees` (`id`, `emp_code`, `name_ar`, `name_en`, `national_id`, `passport_no`, `gender`, `dob`, `email`, `phone`, `department_id`, `designation_id`, `joining_date`, `employment_type`, `basic_salary`, `status`, `address`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'EMP-0001', 'Mohamed Abo-Elmaaty', 'Mohamed Abo-Elmaaty', '30003260300311', '', 'male', '2000-03-26', 'mo.m3aty@gmail.com', '01275844735', 1, 1, '2026-08-27', 'full_time', 15000.00, 'active', 'port-said Elzohor- building number8ِA -Apartment No. km', 1, '2026-08-27 03:54:39', '2026-08-27 04:07:07');

-- --------------------------------------------------------

--
-- Table structure for table `hr_employee_contracts`
--

CREATE TABLE `hr_employee_contracts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `contract_code` varchar(50) NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `basic_salary` decimal(15,2) NOT NULL DEFAULT 0.00,
  `housing_allowance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `transport_allowance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `status` enum('active','expired','terminated') NOT NULL DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `hr_employee_contracts`
--

INSERT INTO `hr_employee_contracts` (`id`, `contract_code`, `employee_id`, `start_date`, `end_date`, `basic_salary`, `housing_allowance`, `transport_allowance`, `status`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'CNT-0001', 1, '2026-08-27', '2027-08-27', 5000.00, 1000.00, 500.00, 'active', 'ملاحظات وشروط خاصة بالعقد', 1, '2026-08-27 04:07:28', '2026-08-27 04:07:28');

-- --------------------------------------------------------

--
-- Table structure for table `hr_leaves`
--

CREATE TABLE `hr_leaves` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED DEFAULT 1,
  `branch_id` int(11) DEFAULT 0,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `leave_type` enum('annual','sick','unpaid','maternity','other') NOT NULL DEFAULT 'annual',
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `days_count` decimal(5,2) NOT NULL DEFAULT 0.00,
  `reason` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `approved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `hr_leaves`
--

INSERT INTO `hr_leaves` (`id`, `company_id`, `branch_id`, `employee_id`, `leave_type`, `start_date`, `end_date`, `days_count`, `reason`, `status`, `approved_by`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, 'annual', '2026-08-27', '2026-08-28', 2.00, 'سبب الإجازة والتفاصيل', 'pending', NULL, 1, '2026-08-27 04:32:17', '2026-09-21 10:56:30'),
(2, 1, 1, 1, 'annual', '2026-10-01', '2026-10-05', 5.00, 'إجازة سنوية خطة الخريف', 'pending', NULL, 1, '2026-09-21 11:09:41', '2026-09-21 11:09:41'),
(3, 1, 2, 1, 'sick', '2026-09-22', '2026-09-24', 2.00, 'إجازة مرضية طارئة', 'pending', NULL, 1, '2026-09-21 11:09:41', '2026-09-21 11:09:41');

-- --------------------------------------------------------

--
-- Table structure for table `hr_payroll`
--

CREATE TABLE `hr_payroll` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `payroll_code` varchar(50) NOT NULL,
  `month` varchar(20) NOT NULL,
  `year` int(11) NOT NULL,
  `total_basic` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_allowances` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_deductions` decimal(15,2) NOT NULL DEFAULT 0.00,
  `net_pay` decimal(15,2) NOT NULL DEFAULT 0.00,
  `status` enum('draft','processed','paid') NOT NULL DEFAULT 'draft',
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `hr_payroll`
--

INSERT INTO `hr_payroll` (`id`, `payroll_code`, `month`, `year`, `total_basic`, `total_allowances`, `total_deductions`, `net_pay`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'PAY-202608-001', 'August', 2026, 15000.00, 1500.00, 0.00, 16500.00, 'processed', 1, '2026-08-27 04:36:18', '2026-08-27 04:36:18');

-- --------------------------------------------------------

--
-- Table structure for table `hr_positions`
--

CREATE TABLE `hr_positions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL DEFAULT 1,
  `title` varchar(255) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `hr_positions`
--

INSERT INTO `hr_positions` (`id`, `company_id`, `title`, `is_active`) VALUES
(1, 1, 'Sales Manager', 1),
(2, 1, 'Accountant', 1),
(3, 1, 'HR Specialist', 1);

-- --------------------------------------------------------

--
-- Table structure for table `hr_recruitment`
--

CREATE TABLE `hr_recruitment` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `applicant_code` varchar(50) NOT NULL,
  `candidate_name` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `department_id` bigint(20) UNSIGNED DEFAULT NULL,
  `designation_id` bigint(20) UNSIGNED DEFAULT NULL,
  `experience_years` int(11) DEFAULT 0,
  `expected_salary` decimal(15,2) DEFAULT 0.00,
  `status` enum('applied','interviewed','offered','hired','rejected') NOT NULL DEFAULT 'applied',
  `interview_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `hr_recruitment`
--

INSERT INTO `hr_recruitment` (`id`, `applicant_code`, `candidate_name`, `email`, `phone`, `department_id`, `designation_id`, `experience_years`, `expected_salary`, `status`, `interview_date`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'CAN-2026-001', 'اسم المرشح بالكامل *', 'mo.m3aty@gmail.com', '01124743148', 1, 1, 5, 20000.00, 'applied', '2026-08-29', 'ملاحظات وتقييم المقابلة', 1, '2026-08-27 04:57:52', '2026-08-27 04:57:52');

-- --------------------------------------------------------

--
-- Table structure for table `hr_salary_components`
--

CREATE TABLE `hr_salary_components` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `type` enum('allowance','deduction') NOT NULL DEFAULT 'allowance',
  `name_ar` varchar(255) NOT NULL,
  `amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `is_fixed` tinyint(1) DEFAULT 1,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `hr_salary_components`
--

INSERT INTO `hr_salary_components` (`id`, `employee_id`, `type`, `name_ar`, `amount`, `is_fixed`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 1, 'allowance', 'بدل سكن', 3000.00, 1, 1, '2026-08-27 04:44:58', '2026-08-27 04:44:58');

-- --------------------------------------------------------

--
-- Table structure for table `hr_shifts`
--

CREATE TABLE `hr_shifts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(50) NOT NULL,
  `name_ar` varchar(255) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `grace_period_mins` int(11) DEFAULT 15,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `hr_shifts`
--

INSERT INTO `hr_shifts` (`id`, `code`, `name_ar`, `start_time`, `end_time`, `grace_period_mins`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'SHF-001', 'الصباحيه', '08:00:00', '16:00:00', 15, 'active', 1, '2026-08-27 04:29:45', '2026-08-27 04:29:45');

-- --------------------------------------------------------

--
-- Table structure for table `inv_categories`
--

CREATE TABLE `inv_categories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL DEFAULT 1,
  `branch_id` int(11) DEFAULT 0,
  `name` varchar(255) NOT NULL,
  `parent_id` bigint(20) UNSIGNED DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `inv_categories`
--

INSERT INTO `inv_categories` (`id`, `company_id`, `branch_id`, `name`, `parent_id`, `is_active`, `created_at`) VALUES
(1, 1, 1, 'الإلكترونيات والأجهزة (Electronics)', NULL, 1, '2026-08-22 13:39:34'),
(2, 1, 2, 'الأثاث المكتبي (Office Furniture)', NULL, 1, '2026-08-22 13:39:34'),
(3, 1, 3, 'الخدمات (Services)', NULL, 1, '2026-08-22 13:39:34');

-- --------------------------------------------------------

--
-- Table structure for table `inv_delivery_notes`
--

CREATE TABLE `inv_delivery_notes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED DEFAULT NULL,
  `branch_id` int(11) DEFAULT 0,
  `note_no` varchar(50) NOT NULL,
  `warehouse_id` bigint(20) UNSIGNED DEFAULT NULL,
  `order_id` bigint(20) UNSIGNED DEFAULT NULL,
  `customer_id` bigint(20) UNSIGNED NOT NULL,
  `note_date` date NOT NULL,
  `status` enum('draft','dispatched','delivered','cancelled') DEFAULT 'draft',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `inv_delivery_notes`
--

INSERT INTO `inv_delivery_notes` (`id`, `company_id`, `branch_id`, `note_no`, `warehouse_id`, `order_id`, `customer_id`, `note_date`, `status`, `notes`, `created_at`) VALUES
(1, 1, 1, 'DN-2026081814', 1, NULL, 2, '2026-08-22', 'draft', 'اضافة راس مال', '2026-08-22 16:15:38');

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

-- --------------------------------------------------------

--
-- Table structure for table `inv_products`
--

CREATE TABLE `inv_products` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL DEFAULT 1,
  `branch_id` int(11) DEFAULT 0,
  `sku` varchar(100) NOT NULL,
  `barcode` varchar(100) DEFAULT NULL,
  `name_en` varchar(255) NOT NULL,
  `name_ar` varchar(255) NOT NULL,
  `category_id` bigint(20) UNSIGNED DEFAULT NULL,
  `type` enum('storable','service','consumable') DEFAULT 'storable',
  `unit` varchar(50) DEFAULT 'pcs',
  `cost_price` decimal(15,4) DEFAULT 0.0000,
  `sale_price` decimal(15,4) DEFAULT 0.0000,
  `tax_percent` decimal(5,2) DEFAULT 15.00,
  `track_batches` tinyint(1) DEFAULT 0,
  `track_serials` tinyint(1) DEFAULT 0,
  `reorder_level` decimal(15,2) DEFAULT 0.00,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `inv_products`
--

INSERT INTO `inv_products` (`id`, `company_id`, `branch_id`, `sku`, `barcode`, `name_en`, `name_ar`, `category_id`, `type`, `unit`, `cost_price`, `sale_price`, `tax_percent`, `track_batches`, `track_serials`, `reorder_level`, `is_active`, `created_at`) VALUES
(1, 1, 1, 'PRD-LPT-001', '1234567890123', 'Dell XPS 15 Laptop', 'لابتوب ديل XPS 15', 1, 'storable', 'pcs', 4500.0000, 5800.0000, 15.00, 0, 0, 15.00, 1, '2026-08-22 13:39:34'),
(2, 1, 2, 'PRD-LPT-002', '1234567890124', 'MacBook Pro 16', 'ماك بوك برو 16 إنش', 1, 'storable', 'pcs', 8500.0000, 10500.0000, 15.00, 0, 0, 15.00, 1, '2026-08-22 13:39:34'),
(3, 1, 3, 'PRD-FUR-001', '1234567890125', 'Ergonomic Office Chair', 'كرسي مكتب طبي مريح', 2, 'storable', 'pcs', 350.0000, 550.0000, 15.00, 0, 0, 15.00, 1, '2026-08-22 13:39:34'),
(4, 1, 4, 'PRD-FUR-002', '1234567890126', 'Executive Desk', 'مكتب إداري فاخر', 2, 'storable', 'pcs', 800.0000, 1200.0000, 15.00, 0, 0, 0.00, 1, '2026-08-22 13:39:34'),
(5, 1, 1, 'PRD-SRV-001', NULL, 'Annual Maintenance Contract', 'عقد صيانة سنوي للأجهزة', 3, 'service', 'contract', 0.0000, 2500.0000, 15.00, 0, 0, 0.00, 1, '2026-08-22 13:39:34'),
(6, 1, 2, 'PRD-SRV-002', NULL, 'Software Installation Support', 'خدمة تثبيت ودعم البرمجيات', 3, 'service', 'hour', 0.0000, 150.0000, 15.00, 0, 0, 0.00, 1, '2026-08-22 13:39:34');

-- --------------------------------------------------------

--
-- Table structure for table `inv_stock`
--

CREATE TABLE `inv_stock` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `branch_id` int(11) DEFAULT 0,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `warehouse_id` bigint(20) UNSIGNED NOT NULL,
  `quantity` decimal(15,4) DEFAULT 0.0000,
  `average_cost` decimal(15,4) DEFAULT 0.0000
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inv_stock_movements`
--

CREATE TABLE `inv_stock_movements` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `branch_id` int(11) DEFAULT 0,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `warehouse_id` bigint(20) UNSIGNED NOT NULL,
  `type` varchar(50) NOT NULL,
  `quantity` decimal(15,4) NOT NULL,
  `reference` varchar(100) DEFAULT NULL,
  `movement_date` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inv_stock_transfers`
--

CREATE TABLE `inv_stock_transfers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `branch_id` int(11) DEFAULT 0,
  `transfer_number` varchar(50) NOT NULL,
  `from_warehouse_id` bigint(20) UNSIGNED NOT NULL,
  `to_warehouse_id` bigint(20) UNSIGNED NOT NULL,
  `status` enum('draft','completed','cancelled') DEFAULT 'completed',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inv_stock_transfer_items`
--

CREATE TABLE `inv_stock_transfer_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `transfer_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `quantity` decimal(15,4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inv_warehouses`
--

CREATE TABLE `inv_warehouses` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL DEFAULT 1,
  `branch_id` int(11) DEFAULT 0,
  `code` varchar(50) NOT NULL,
  `name_ar` varchar(255) DEFAULT NULL,
  `name_en` varchar(255) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `manager_name` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `inv_warehouses`
--

INSERT INTO `inv_warehouses` (`id`, `company_id`, `branch_id`, `code`, `name_ar`, `name_en`, `name`, `location`, `is_active`, `created_at`, `manager_name`, `phone`) VALUES
(1, 1, 1, 'WH-JED-01', NULL, NULL, 'Jeddah Main Warehouse', 'Jeddah, Al-Nuzha', 1, '2026-08-22 12:53:33', NULL, NULL),
(2, 1, 2, 'WH-RUH-01', NULL, NULL, 'Riyadh Branch Warehouse', 'Riyadh, Olaya', 1, '2026-08-22 12:53:33', NULL, NULL),
(3, 1, 3, 'WH-001', NULL, NULL, 'المستودع الرئيسي', 'المبنى الرئيسي - المنطقة الصناعية', 1, '2026-08-22 16:10:18', NULL, NULL),
(4, 1, 4, 'WH-002', NULL, NULL, 'مستودع المعرض', 'فرع المعرض العام', 1, '2026-08-22 16:10:18', NULL, NULL),
(5, 1, 1, 'WH-003', NULL, NULL, 'مستودع المرتجعات', 'مبنى الخدمات الملحق', 1, '2026-08-22 16:10:18', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `journal_entries`
--

CREATE TABLE `journal_entries` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `entry_number` varchar(50) NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL DEFAULT 1,
  `branch_id` int(11) DEFAULT 0,
  `reference` varchar(100) DEFAULT NULL,
  `entry_date` date NOT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `description` text NOT NULL,
  `status` enum('draft','posted','voided') NOT NULL DEFAULT 'draft',
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `total_amount` decimal(15,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `journal_entries`
--

INSERT INTO `journal_entries` (`id`, `entry_number`, `company_id`, `branch_id`, `reference`, `entry_date`, `reference_number`, `description`, `status`, `created_by`, `created_at`, `total_amount`) VALUES
(1, 'JE-26080001', 1, 1, NULL, '2026-08-23', '87846541654165', 'بيان القيد (Description) *', 'draft', NULL, '2026-08-23 20:11:50', 4000.00),
(2, 'JE-26080002', 1, 2, NULL, '2026-08-23', '56541965165416', 'شرح القيد العام *', 'posted', NULL, '2026-08-23 20:13:14', 600.00),
(3, 'DEP-260823-811', 1, 1, NULL, '2026-08-23', NULL, 'قيد إهلاك شهري للأصل: محمد ابوالمعاطي (07775000)', 'posted', NULL, '2026-08-23 20:50:46', 50.00),
(4, 'BNK-FEE-260823-78', 1, 2, NULL, '2026-08-23', NULL, 'مصاريف وعمولات بنكية - كشف تسوية BR-26080001', 'posted', NULL, '2026-08-23 21:10:58', 5000.00);

-- --------------------------------------------------------

--
-- Table structure for table `journal_entry_items`
--

CREATE TABLE `journal_entry_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `journal_entry_id` bigint(20) UNSIGNED NOT NULL,
  `account_id` bigint(20) UNSIGNED NOT NULL,
  `cost_center_id` bigint(20) UNSIGNED DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `debit` decimal(15,2) NOT NULL DEFAULT 0.00,
  `credit` decimal(15,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `journal_entry_items`
--

INSERT INTO `journal_entry_items` (`id`, `journal_entry_id`, `account_id`, `cost_center_id`, `description`, `debit`, `credit`) VALUES
(3, 2, 4, NULL, 'جديد', 600.00, 0.00),
(4, 2, 3, NULL, 'جديد', 0.00, 600.00),
(7, 1, 1, 2, 'جديد', 4000.00, 0.00),
(8, 1, 2, 2, 'جديد', 0.00, 4000.00),
(9, 3, 4, 2, 'مصروف إهلاك - محمد ابوالمعاطي', 50.00, 0.00),
(10, 3, 2, 2, 'مجمع إهلاك - محمد ابوالمعاطي', 0.00, 50.00),
(11, 4, 6, NULL, 'مصاريف وعمولات بنكية - كشف تسوية BR-26080001', 5000.00, 0.00),
(12, 4, 1, NULL, 'مصاريف وعمولات بنكية - كشف تسوية BR-26080001', 0.00, 5000.00);

-- --------------------------------------------------------

--
-- Table structure for table `journal_entry_lines`
--

CREATE TABLE `journal_entry_lines` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `journal_entry_id` bigint(20) UNSIGNED NOT NULL,
  `account_id` bigint(20) UNSIGNED NOT NULL,
  `cost_center_id` bigint(20) UNSIGNED DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `debit` decimal(15,2) NOT NULL DEFAULT 0.00,
  `credit` decimal(15,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `landed_costs`
--

CREATE TABLE `landed_costs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED DEFAULT 1,
  `reference_number` varchar(50) NOT NULL,
  `po_id` bigint(20) UNSIGNED DEFAULT NULL,
  `supplier_id` bigint(20) UNSIGNED DEFAULT NULL,
  `cost_date` date NOT NULL,
  `allocation_method` enum('by_value','by_quantity') NOT NULL DEFAULT 'by_value',
  `total_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `status` enum('draft','allocated','posted') NOT NULL DEFAULT 'draft',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `landed_costs`
--

INSERT INTO `landed_costs` (`id`, `company_id`, `reference_number`, `po_id`, `supplier_id`, `cost_date`, `allocation_method`, `total_amount`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, 'LC-260822-235', 1, 1, '2026-08-22', 'by_value', 14000.00, 'allocated', 'ملاحظات والتفاصيل المالية', '2026-08-22 21:23:48', '2026-08-22 21:23:48');

-- --------------------------------------------------------

--
-- Table structure for table `landed_cost_items`
--

CREATE TABLE `landed_cost_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `landed_cost_id` bigint(20) UNSIGNED NOT NULL,
  `cost_type` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `landed_cost_items`
--

INSERT INTO `landed_cost_items` (`id`, `landed_cost_id`, `cost_type`, `description`, `amount`) VALUES
(1, 1, 'شحن دولي / بري', 'البيان', 8000.00),
(2, 1, 'رسوم جمركية', 'التفاصيل ', 6000.00);

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `module` varchar(100) NOT NULL,
  `resource` varchar(100) NOT NULL,
  `action` varchar(100) NOT NULL,
  `permission_key` varchar(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `category_id` bigint(20) UNSIGNED DEFAULT NULL,
  `company_id` bigint(20) UNSIGNED DEFAULT 1,
  `branch_id` int(11) DEFAULT 0,
  `item_code` varchar(50) NOT NULL,
  `barcode` varchar(100) DEFAULT NULL,
  `name_ar` varchar(255) NOT NULL,
  `name_en` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `purchase_price` decimal(15,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `unit` varchar(50) NOT NULL DEFAULT 'قطعة',
  `selling_price` decimal(15,2) NOT NULL DEFAULT 0.00,
  `reorder_level` decimal(10,2) NOT NULL DEFAULT 0.00,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `category_id`, `company_id`, `branch_id`, `item_code`, `barcode`, `name_ar`, `name_en`, `description`, `purchase_price`, `created_at`, `unit`, `selling_price`, `reorder_level`, `is_active`) VALUES
(1, 1, 1, 1, 'ITM-1001', '', 'لابتوب ديل انسبايرون (Core i7)', 'Dell Inspiron Laptop', '', 25000.00, '2026-08-22 19:59:53', 'قطعة', 0.00, 20.00, 1),
(2, NULL, 1, 2, 'ITM-1002', NULL, 'ورق طباعة A4 ممتاز 80 جرام', 'A4 Printer Paper 80g', NULL, 180.00, '2026-08-22 19:59:53', 'قطعة', 0.00, 20.00, 1),
(3, NULL, 1, 3, 'ITM-1003', NULL, 'حبر طابعة أسود HP', 'HP Black Ink Cartridge', NULL, 500.00, '2026-08-22 19:59:53', 'قطعة', 0.00, 20.00, 1),
(5, NULL, 1, 4, 'ITM-1005', '', 'مكتب خشبي إداري حديث', 'Modern Office Desk', 'وصف وتفاصيل الصنف\r\n', 3500.00, '2026-08-22 19:59:53', 'قطعة', 4000.00, 5.00, 1),
(6, 1, 1, 1, 'oo112', 'weffaf', 'برنامج سحابي للمحاماه ', 'ERP for  office ', 'wegavasv', 300.00, '2026-08-22 22:54:48', 'قطعة', 2500.00, 0.97, 1),
(7, 1, 1, 0, 'ITM-26110', 'الباركود الدولي (Barcode)', 'Product Name (Arabic) *', 'Product Name (English)', 'Product Description\r\n', 80.00, '2026-09-07 23:12:50', 'قطعة', 50.00, 70.00, 0);

-- --------------------------------------------------------

--
-- Table structure for table `product_categories`
--

CREATE TABLE `product_categories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED DEFAULT 1,
  `branch_id` int(11) DEFAULT 0,
  `name_ar` varchar(255) NOT NULL,
  `name_en` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `product_categories`
--

INSERT INTO `product_categories` (`id`, `company_id`, `branch_id`, `name_ar`, `name_en`, `description`) VALUES
(1, 1, 1, 'أجهزة الكترونيه ', 'Electronics', '');

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

CREATE TABLE `projects` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(100) NOT NULL,
  `name_ar` varchar(255) NOT NULL,
  `name_en` varchar(255) DEFAULT NULL,
  `customer_id` bigint(20) UNSIGNED DEFAULT NULL,
  `contract_value` decimal(15,2) NOT NULL DEFAULT 0.00,
  `estimated_budget` decimal(15,2) NOT NULL DEFAULT 0.00,
  `spent_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `progress_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('planning','in_progress','on_hold','completed','cancelled') NOT NULL DEFAULT 'in_progress',
  `description` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `company_id` int(11) DEFAULT 1,
  `branch_id` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `projects`
--

INSERT INTO `projects` (`id`, `code`, `name_ar`, `name_en`, `customer_id`, `contract_value`, `estimated_budget`, `spent_amount`, `progress_percent`, `start_date`, `end_date`, `status`, `description`, `created_by`, `created_at`, `updated_at`, `company_id`, `branch_id`) VALUES
(1, 'PRJ-2026-0001', 'اسم المشروع (عربي) *', 'اسم المشروع (إنجليزي)', 13, 50000.00, 15000.00, 8000.00, 20.00, '2026-08-26', '2026-09-30', 'in_progress', 'نطاق العمل والوصف التفصيلي', 1, '2026-08-26 19:36:46', '2026-09-03 10:54:02', 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `project_contracts`
--

CREATE TABLE `project_contracts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `contract_number` varchar(100) NOT NULL,
  `project_id` bigint(20) UNSIGNED NOT NULL,
  `customer_id` bigint(20) UNSIGNED DEFAULT NULL,
  `title_ar` varchar(255) NOT NULL,
  `title_en` varchar(255) DEFAULT NULL,
  `contract_type` enum('owner_contract','subcontractor_contract','consultant_contract','supply_contract') NOT NULL DEFAULT 'owner_contract',
  `contract_value` decimal(15,2) NOT NULL DEFAULT 0.00,
  `advance_payment_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `retention_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `sign_date` date DEFAULT NULL,
  `status` enum('draft','active','under_renewal','completed','terminated','suspended') NOT NULL DEFAULT 'active',
  `terms_and_conditions` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `company_id` int(11) DEFAULT 1,
  `branch_id` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `project_contracts`
--

INSERT INTO `project_contracts` (`id`, `contract_number`, `project_id`, `customer_id`, `title_ar`, `title_en`, `contract_type`, `contract_value`, `advance_payment_amount`, `retention_percent`, `start_date`, `end_date`, `sign_date`, `status`, `terms_and_conditions`, `notes`, `created_by`, `created_at`, `updated_at`, `company_id`, `branch_id`) VALUES
(1, 'CON-PRJ-2026-0001', 1, 13, 'عنوان العقد (عربي) *', 'عنوان العقد (إنجليزي)', 'owner_contract', 8000.00, 2000.00, 10.00, '2026-08-26', '2027-08-26', '2026-08-26', 'under_renewal', 'الشروط والبنود الالتزامية للعقد', 'ملاحظات إضافية', 1, '2026-08-26 20:06:43', '2026-09-03 10:54:41', 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `project_costs`
--

CREATE TABLE `project_costs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` int(11) DEFAULT 1,
  `branch_id` int(11) DEFAULT 0,
  `voucher_number` varchar(100) NOT NULL,
  `project_id` bigint(20) UNSIGNED NOT NULL,
  `cost_category` enum('materials','labor','equipment','subcontractor','overhead','other') NOT NULL DEFAULT 'materials',
  `cost_date` date NOT NULL,
  `amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `supplier_id` bigint(20) UNSIGNED DEFAULT NULL,
  `account_id` bigint(20) UNSIGNED DEFAULT NULL,
  `reference_no` varchar(100) DEFAULT NULL,
  `payment_status` enum('unpaid','partially_paid','paid') NOT NULL DEFAULT 'paid',
  `description` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `project_costs`
--

INSERT INTO `project_costs` (`id`, `company_id`, `branch_id`, `voucher_number`, `project_id`, `cost_category`, `cost_date`, `amount`, `supplier_id`, `account_id`, `reference_no`, `payment_status`, `description`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'COST-PRJ-2026-0001', 1, 'materials', '2026-08-26', 8000.00, 1, 2, '6453163164165', 'partially_paid', 'البيان والوصف التفصيلي', 'ملاحظات إضافية', 1, '2026-08-26 20:04:30', '2026-09-03 10:54:49');

-- --------------------------------------------------------

--
-- Table structure for table `project_invoices`
--

CREATE TABLE `project_invoices` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` int(11) DEFAULT 1,
  `branch_id` int(11) DEFAULT 0,
  `invoice_number` varchar(100) NOT NULL,
  `project_id` bigint(20) UNSIGNED NOT NULL,
  `customer_id` bigint(20) UNSIGNED DEFAULT NULL,
  `invoice_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `period_start` date DEFAULT NULL,
  `period_end` date DEFAULT NULL,
  `invoice_type` enum('advance_payment','progress_claim','final_claim','retention_release') NOT NULL DEFAULT 'progress_claim',
  `total_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `deductions_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `net_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `paid_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `status` enum('draft','submitted','approved','partially_paid','paid','rejected') NOT NULL DEFAULT 'draft',
  `description` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `project_invoices`
--

INSERT INTO `project_invoices` (`id`, `company_id`, `branch_id`, `invoice_number`, `project_id`, `customer_id`, `invoice_date`, `due_date`, `period_start`, `period_end`, `invoice_type`, `total_amount`, `deductions_amount`, `tax_amount`, `net_amount`, `paid_amount`, `status`, `description`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'INV-PRJ-2026-0001', 1, 12, '2026-08-26', '2026-09-10', '2026-08-01', '2026-08-31', 'advance_payment', 8000.00, 1500.00, 2500.00, 9000.00, 9000.00, 'paid', 'وصف ونطاق المستخلص', 'شروط وملاحظات السداد', 1, '2026-08-26 19:55:45', '2026-09-03 10:54:54');

-- --------------------------------------------------------

--
-- Table structure for table `project_milestones`
--

CREATE TABLE `project_milestones` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` int(11) DEFAULT 1,
  `branch_id` int(11) DEFAULT 0,
  `project_id` bigint(20) UNSIGNED NOT NULL,
  `milestone_code` varchar(100) NOT NULL,
  `title_ar` varchar(255) NOT NULL,
  `title_en` varchar(255) DEFAULT NULL,
  `assigned_to` varchar(255) DEFAULT NULL,
  `start_date` date NOT NULL,
  `due_date` date NOT NULL,
  `completion_date` date DEFAULT NULL,
  `progress_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `estimated_cost` decimal(15,2) NOT NULL DEFAULT 0.00,
  `actual_cost` decimal(15,2) NOT NULL DEFAULT 0.00,
  `status` enum('pending','in_progress','under_review','completed','delayed','cancelled') NOT NULL DEFAULT 'pending',
  `priority` enum('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
  `description` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `project_milestones`
--

INSERT INTO `project_milestones` (`id`, `company_id`, `branch_id`, `project_id`, `milestone_code`, `title_ar`, `title_en`, `assigned_to`, `start_date`, `due_date`, `completion_date`, `progress_percent`, `estimated_cost`, `actual_cost`, `status`, `priority`, `description`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, 'MS-2026-0001', 'عنوان المرحلة/المهمة (عربي) *', 'عنوان المرحلة/المهمة (إنجليزي)', 'المهندس / المسؤول الفني عن التنفيذ', '2026-08-26', '2026-09-25', '2026-08-28', 30.00, 5000.00, 3000.00, 'in_progress', 'high', 'تفاصيل والمواصفات الفنية للمرحلة', 1, '2026-08-26 19:45:19', '2026-09-03 10:55:01');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_contracts`
--

CREATE TABLE `purchase_contracts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED DEFAULT 1,
  `supplier_id` bigint(20) UNSIGNED NOT NULL,
  `contract_number` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `total_value` decimal(15,2) NOT NULL DEFAULT 0.00,
  `status` enum('draft','active','expired','terminated') NOT NULL DEFAULT 'active',
  `terms_conditions` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `purchase_contracts`
--

INSERT INTO `purchase_contracts` (`id`, `company_id`, `supplier_id`, `contract_number`, `title`, `start_date`, `end_date`, `total_value`, `status`, `terms_conditions`, `notes`, `created_at`, `updated_at`) VALUES
(2, 1, 1, 'PCNT-2608370', 'cabling and fitting in the site number (1919) Gala Oman ', '2026-08-26', '2027-12-03', 105000.00, 'active', 'Certainly. Below is a professional **Contract Terms & Conditions for Electrical Fitting and Cabling Works** between **Nour Makha International** and **Al-Hajri Electricity Company**. It is structured so it can be converted into a formal agreement and adapted to the specific project.\r\n\r\n# ELECTRICAL FITTING & CABLING WORKS AGREEMENT\r\n\r\n**Between**\r\n\r\n**Nour Makha International**\r\nHereinafter referred to as the **“Client”**\r\n\r\n**And**\r\n\r\n**Al-Hajri Electricity Company**\r\nHereinafter referred to as the **“Contractor”**\r\n\r\nCollectively referred to as the **“Parties.”**\r\n\r\n**Project:** [Project Name]\r\n**Project Location:** [Location]\r\n**Contract No.:** [●]\r\n**Contract Date:** [●]\r\n\r\n---\r\n\r\n## 1. Purpose of the Agreement\r\n\r\nThe Client appoints the Contractor to carry out **electrical fitting, installation, cabling, termination, testing, commissioning, and related electrical works** for the Project in accordance with the approved drawings, specifications, applicable standards, and instructions issued by the Client or its authorized representative.\r\n\r\nThe Contractor agrees to perform the Works professionally, safely, and within the agreed project schedule.\r\n\r\n---\r\n\r\n## 2. Scope of Work\r\n\r\nThe Contractor\'s scope shall include, where applicable:\r\n\r\n1. Electrical cable installation and routing.\r\n2. Cable pulling, laying, dressing, tagging, and identification.\r\n3. Installation of cable trays, trunking, conduits, supports, and accessories.\r\n4. Electrical fitting and installation of switches, sockets, lighting fixtures, distribution boards, panels, isolators, and related equipment.\r\n5. Cable termination and gland installation.\r\n6. Installation of cable lugs, ferrules, labels, and identification systems.\r\n7. Earthing and bonding works.\r\n8. Connection of electrical equipment and systems.\r\n9. Testing and inspection of installed cables and electrical equipment.\r\n10. Continuity, insulation resistance, polarity, earth continuity, and other applicable electrical tests.\r\n11. Rectification of defective or non-compliant work.\r\n12. Commissioning and functional testing.\r\n13. Submission of test reports and completion documentation.\r\n14. Removal of work-related waste and maintaining the work area in a clean and safe condition.\r\n15. Any other works specifically identified in the approved **Bill of Quantities (BOQ), drawings, specifications, or quotation**.\r\n\r\n---\r\n\r\n## 3. Approved Drawings and Specifications\r\n\r\nThe Contractor shall execute the Works strictly in accordance with:\r\n\r\n* Approved drawings.\r\n* Technical specifications.\r\n* BOQ.\r\n* Approved material submittals.\r\n* Applicable electrical codes and standards.\r\n* Manufacturer\'s installation instructions.\r\n* Written instructions issued by the Client or Project Consultant.\r\n\r\nNo material deviation from the approved drawings or specifications shall be permitted without prior written approval from the Client.\r\n\r\n---\r\n\r\n## 4. Materials and Equipment\r\n\r\nUnless otherwise agreed in writing:\r\n\r\n* All materials supplied by the Contractor shall be **new, unused, genuine, and of approved quality**.\r\n* Materials shall comply with the required technical specifications and applicable standards.\r\n* The Contractor shall submit material specifications, technical datasheets, certificates, and samples when requested.\r\n* The Client has the right to reject materials that do not comply with the approved specifications.\r\n* Any rejected materials shall be removed from the Project site at the Contractor\'s cost.\r\n\r\n---\r\n\r\n## 5. Workmanship\r\n\r\nThe Contractor shall ensure that all Works are carried out by **qualified, competent, and suitably experienced electricians and technicians**.\r\n\r\nAll installation works shall be neat, properly supported, correctly terminated, adequately labelled, and suitable for safe long-term operation.\r\n\r\nThe Contractor shall be responsible for correcting any defective workmanship identified during the Works or during the warranty period.\r\n\r\n---\r\n\r\n## 6. Health, Safety and Environment\r\n\r\nThe Contractor shall comply with all applicable health, safety, environmental, and site requirements.\r\n\r\nThe Contractor shall:\r\n\r\n* Provide appropriate PPE to all workers.\r\n* Maintain safe working practices.\r\n* Ensure electrical isolation and lock-out/tag-out procedures where applicable.\r\n* Prevent unauthorized access to work areas.\r\n* Maintain appropriate fire and emergency precautions.\r\n* Comply with site safety procedures.\r\n* Immediately report accidents, incidents, electrical hazards, or property damage to the Client.\r\n\r\nAny violation of safety requirements may result in suspension of the relevant Works until the issue is rectified.\r\n\r\n---\r\n\r\n## 7. Permits and Approvals\r\n\r\nThe Contractor shall obtain and maintain all permits, licenses, certifications, inspections, and approvals required for the execution of its scope, to the extent that such obligations are allocated to the Contractor under applicable law or the Project requirements.\r\n\r\nThe Contractor shall cooperate with the Client in obtaining any approvals that require the Client\'s participation.\r\n\r\n---\r\n\r\n## 8. Project Schedule\r\n\r\nThe Contractor shall commence the Works on:\r\n\r\n**Commencement Date:** [●]\r\n\r\nand shall substantially complete the Works by:\r\n\r\n**Completion Date:** [●]\r\n\r\nThe Contractor shall provide sufficient manpower, tools, equipment, and supervision to meet the agreed schedule.\r\n\r\nAny anticipated delay shall be notified to the Client in writing immediately, together with the reason and proposed recovery plan.\r\n\r\n---\r\n\r\n## 9. Contract Price\r\n\r\nThe total contract value shall be:\r\n\r\n**Contract Price: [Currency] [Amount]**\r\n\r\nThe price shall be based on the agreed quotation/BOQ attached to this Agreement.\r\n\r\nUnless specifically stated otherwise, the Contract Price shall include:\r\n\r\n* Labour.\r\n* Installation.\r\n* Tools and equipment.\r\n* Transportation related to the Contractor\'s scope.\r\n* Consumables.\r\n* Testing.\r\n* Commissioning.\r\n* Supervision.\r\n* Site housekeeping.\r\n* All other costs necessary to complete the Contractor\'s scope.\r\n\r\n**VAT or other applicable taxes:** [Included / Excluded / As applicable].\r\n\r\n---\r\n\r\n## 10. Payment Terms\r\n\r\nA suggested payment structure is:\r\n\r\n* **20% advance payment** upon signing of the Agreement and submission of the required documents.\r\n* **40%** upon substantial completion of installation works.\r\n* **30%** upon testing, commissioning, and successful inspection.\r\n* **10% retention** upon final handover and submission of all required documentation.\r\n\r\nThe Parties may agree to alternative payment milestones in the attached payment schedule.\r\n\r\nPayment shall be made within **[15/30] days** following receipt and approval of the Contractor\'s valid invoice and supporting documents.\r\n\r\n---\r\n\r\n## 11. Retention\r\n\r\nThe Client may retain **10% of the Contract Price** as retention until completion of the Works and satisfaction of the applicable warranty/defects obligations.\r\n\r\nThe retention shall be released according to the agreed payment schedule or after expiry of the defects liability period, subject to satisfactory completion of all outstanding obligations.\r\n\r\n---\r\n\r\n## 12. Variation / Additional Works\r\n\r\nNo additional work, change in specification, quantity increase, or variation shall be carried out on a chargeable basis without a **written Variation Order** approved by the Client.\r\n\r\nEach variation should clearly state:\r\n\r\n* Description of the additional/change work.\r\n* Additional or reduced cost.\r\n* Effect on completion date.\r\n* Required materials.\r\n* Approval by authorized representatives of both Parties.\r\n\r\nVerbal instructions shall not automatically constitute approval for additional payment.\r\n\r\n---\r\n\r\n## 13. Inspection and Testing\r\n\r\nThe Client or its authorized representative shall have the right to inspect the Works at any reasonable time.\r\n\r\nThe Contractor shall conduct all required testing, including where applicable:\r\n\r\n* Insulation resistance testing.\r\n* Continuity testing.\r\n* Earth resistance testing.\r\n* Polarity testing.\r\n* Phase sequence testing.\r\n* Cable testing.\r\n* Functional testing.\r\n* Load testing where required.\r\n* Panel and equipment testing.\r\n\r\nThe Contractor shall provide signed test reports upon request.\r\n\r\n---\r\n\r\n## 14. Defects and Rectification\r\n\r\nIf any defect, faulty installation, incorrect termination, damaged cable, or non-compliant work is identified, the Contractor shall rectify the defect at its own cost.\r\n\r\nWhere the defect results from the Contractor\'s workmanship or supplied materials, the Contractor shall bear all reasonable costs associated with rectification, including labour, replacement materials, testing, and re-commissioning.\r\n\r\n---\r\n\r\n## 15. Warranty / Defects Liability Period\r\n\r\nThe Contractor shall provide a **[12]-month defects liability/warranty period** commencing from the date of final acceptance of the Works.\r\n\r\nDuring this period, the Contractor shall correct defects attributable to its workmanship or supplied materials without additional cost to the Client.\r\n\r\nThe warranty shall not apply to defects resulting from misuse, unauthorized modification, normal wear and tear, or causes outside the Contractor\'s responsibility.\r\n\r\n---\r\n\r\n## 16. Protection of Existing Property\r\n\r\nThe Contractor shall take reasonable precautions to protect:\r\n\r\n* Existing electrical installations.\r\n* Buildings and structures.\r\n* Equipment.\r\n* Cables and utilities.\r\n* Client property.\r\n* Third-party property.\r\n\r\nAny damage caused by the Contractor, its employees, subcontractors, or representatives due to negligence or improper work shall be repaired or compensated by the Contractor.\r\n\r\n---\r\n\r\n## 17. Personnel and Subcontracting\r\n\r\nThe Contractor shall be responsible for the conduct and competence of its employees.\r\n\r\nThe Contractor shall not subcontract a substantial part of the Works without the Client\'s prior written approval.\r\n\r\nApproval of a subcontractor shall not relieve the Contractor of any responsibility under this Agreement.\r\n\r\n---\r\n\r\n## 18. Tools and Equipment\r\n\r\nUnless otherwise specified in the BOQ, the Contractor shall provide all tools, equipment, testing instruments, ladders, access equipment, PPE, and other resources required to perform its scope.\r\n\r\nAll testing equipment shall be properly calibrated where applicable.\r\n\r\n---\r\n\r\n## 19. Site Coordination\r\n\r\nThe Contractor shall coordinate its activities with the Client, other contractors, consultants, and site management to avoid interference with other works.\r\n\r\nThe Contractor shall attend coordination meetings when requested.\r\n\r\n---\r\n\r\n## 20. Confidentiality\r\n\r\nBoth Parties shall keep confidential all commercial, technical, financial, project, customer, and business information received from the other Party.\r\n\r\nNeither Party shall disclose confidential information to third parties except where required by law or necessary for execution of the Project.\r\n\r\n---\r\n\r\n## 21. Insurance\r\n\r\nThe Contractor shall maintain appropriate insurance coverage required by applicable law and the Project, including where applicable:\r\n\r\n* Workers\' compensation/employer liability.\r\n* Public liability.\r\n* Vehicle insurance.\r\n* Contractor\'s equipment insurance.\r\n* Professional liability where applicable.\r\n\r\nEvidence of insurance shall be provided upon request.\r\n\r\n---\r\n\r\n## 22. Indemnity\r\n\r\nThe Contractor shall be responsible for claims, losses, damages, or expenses arising from its negligence, misconduct, breach of contract, or failure to comply with applicable safety requirements, to the extent permitted by applicable law.\r\n\r\n---\r\n\r\n## 23. Delay and Liquidated Damages\r\n\r\nIf the Contractor fails to complete the Works within the agreed completion period due to causes attributable to the Contractor, the Parties may agree on liquidated damages of:\r\n\r\n**[●]% of the Contract Price per day/week**, subject to a maximum of **[●]%** of the Contract Price.\r\n\r\nLiquidated damages shall not apply where the delay results from approved variations, force majeure, Client-caused delays, or other circumstances agreed in writing.\r\n\r\n---\r\n\r\n## 24. Suspension of Works\r\n\r\nThe Client may instruct the Contractor to temporarily suspend the Works where necessary due to:\r\n\r\n* Safety concerns.\r\n* Non-compliance with specifications.\r\n* Quality problems.\r\n* Site conditions.\r\n* Coordination requirements.\r\n* Failure to follow contractual instructions.\r\n\r\nThe Contractor shall promptly remedy the reason for suspension.\r\n\r\n---\r\n\r\n## 25. Termination\r\n\r\nEither Party may terminate this Agreement in accordance with applicable law where the other Party materially breaches the Agreement and fails to remedy the breach within **[7/14] days** after receiving written notice.\r\n\r\nThe Client may also terminate the Agreement where the Contractor:\r\n\r\n* Abandons the Works.\r\n* Repeatedly fails to meet quality requirements.\r\n* Seriously breaches safety requirements.\r\n* Fails to maintain adequate manpower.\r\n* Becomes insolvent or ceases business operations.\r\n\r\nUpon termination, the Contractor shall hand over all completed Works, drawings, test reports, materials belonging to the Client, and relevant project documentation.\r\n\r\n---\r\n\r\n## 26. Force Majeure\r\n\r\nNeither Party shall be liable for failure or delay caused by circumstances beyond its reasonable control, including natural disasters, war, government restrictions, major public emergencies, or other events legally recognized as force majeure.\r\n\r\nThe affected Party shall notify the other Party as soon as reasonably possible.\r\n\r\n---\r\n\r\n## 27. Dispute Resolution\r\n\r\nThe Parties shall initially attempt to resolve any dispute through **good-faith negotiation between their authorized representatives**.\r\n\r\nIf the dispute cannot be resolved amicably within **30 days**, the matter may be referred to the competent courts or agreed arbitration mechanism in the jurisdiction specified in this Agreement.\r\n\r\n---\r\n\r\n## 28. Governing Law\r\n\r\nThis Agreement shall be governed by and interpreted in accordance with the **laws of the Sultanate of Oman**, unless the Parties expressly agree otherwise in writing.\r\n\r\n---\r\n\r\n## 29. Entire Agreement\r\n\r\nThis Agreement, together with its schedules, approved quotation, BOQ, drawings, specifications, and approved variation orders, constitutes the entire agreement between the Parties concerning the Works.\r\n\r\nAny amendment shall be valid only if made in writing and signed by authorized representatives of both Parties.\r\n\r\n---\r\n\r\n# 30. Documents Forming Part of the Contract\r\n\r\nThe following documents shall form an integral part of this Agreement:\r\n\r\n1. This Contract Agreement.\r\n2. Contractor\'s quotation.\r\n3. Bill of Quantities (BOQ).\r\n4. Scope of Work.\r\n5. Approved electrical drawings.\r\n6. Technical specifications.\r\n7. Project schedule.\r\n8. Payment schedule.\r\n9. Material approval documents.\r\n10. Approved Variation Orders.\r\n11. Testing and commissioning requirements.\r\n\r\n---\r\n\r\n# SIGNATURES\r\n\r\n### For Nour Makha International\r\n\r\n**Client**\r\n\r\nName: __________________________\r\nPosition: _______________________\r\nSignature: ______________________\r\nDate: __________________________\r\nCompany Stamp:\r\n\r\n### For Al-Hajri Electricity Company\r\n\r\n**Contractor**\r\n\r\nName: __________________________\r\nPosition: _______________________\r\nSignature: ______________________\r\nDate: __________________________\r\nCompany Stamp:\r\n\r\n---\r\n\r\n### Recommended additional clauses\r\n\r\nFor a **professional commercial contract**, I would particularly recommend adding a detailed **BOQ and payment milestone schedule**, and specifying exactly **who supplies the cables, panels, fittings, conduits, cable trays, glands and accessories**. This avoids one of the most common disputes in electrical contracts—whether a material or installation item is included in the quoted price.\r\n\r\n', '', '2026-08-26 08:39:37', '2026-08-26 08:39:37');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_invoices`
--

CREATE TABLE `purchase_invoices` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED DEFAULT 1,
  `supplier_id` bigint(20) UNSIGNED NOT NULL,
  `po_id` bigint(20) UNSIGNED DEFAULT NULL,
  `invoice_number` varchar(50) NOT NULL,
  `supplier_invoice_number` varchar(100) DEFAULT NULL,
  `invoice_date` date NOT NULL,
  `due_date` date NOT NULL,
  `status` enum('draft','unpaid','partially_paid','paid','cancelled') NOT NULL DEFAULT 'unpaid',
  `subtotal` decimal(15,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `paid_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `purchase_invoices`
--

INSERT INTO `purchase_invoices` (`id`, `company_id`, `supplier_id`, `po_id`, `invoice_number`, `supplier_invoice_number`, `invoice_date`, `due_date`, `status`, `subtotal`, `discount_amount`, `tax_amount`, `total_amount`, `paid_amount`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, 'INV-260822-961', '', '2026-08-22', '2026-09-21', 'partially_paid', 1500.00, 20.00, 30.00, 1510.00, 510.00, 'توريد نقدي ', '2026-08-22 21:27:03', '2026-08-22 21:27:03');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_invoice_items`
--

CREATE TABLE `purchase_invoice_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `invoice_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED DEFAULT NULL,
  `description` varchar(255) NOT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT 1.00,
  `unit_price` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_price` decimal(15,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `purchase_invoice_items`
--

INSERT INTO `purchase_invoice_items` (`id`, `invoice_id`, `product_id`, `description`, `quantity`, `unit_price`, `total_price`) VALUES
(1, 1, 3, 'حبر طابعه ', 30.00, 50.00, 1500.00);

-- --------------------------------------------------------

--
-- Table structure for table `purchase_orders`
--

CREATE TABLE `purchase_orders` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED DEFAULT 1,
  `branch_id` int(11) DEFAULT 0,
  `supplier_id` bigint(20) UNSIGNED NOT NULL,
  `po_number` varchar(50) NOT NULL,
  `order_number` varchar(50) NOT NULL,
  `order_date` date NOT NULL,
  `delivery_date` date NOT NULL,
  `expected_delivery_date` date DEFAULT NULL,
  `subtotal` decimal(15,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `status` enum('draft','sent','confirmed','processing','received','cancelled') NOT NULL DEFAULT 'draft',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `discount_amount` decimal(15,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `purchase_orders`
--

INSERT INTO `purchase_orders` (`id`, `company_id`, `branch_id`, `supplier_id`, `po_number`, `order_number`, `order_date`, `delivery_date`, `expected_delivery_date`, `subtotal`, `tax_amount`, `total_amount`, `status`, `notes`, `created_at`, `updated_at`, `discount_amount`) VALUES
(1, 1, 1, 1, 'PO-260822-940', '', '2026-08-22', '2026-08-24', NULL, 8000.00, 40.00, 8025.00, 'sent', 'ملاحظات للمورد وشروط الدفع\r\n', '2026-08-22 21:08:51', '2026-09-21 10:56:30', 15.00);

-- --------------------------------------------------------

--
-- Table structure for table `purchase_order_items`
--

CREATE TABLE `purchase_order_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `po_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED DEFAULT NULL,
  `description` varchar(255) NOT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT 1.00,
  `unit_price` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_price` decimal(15,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `purchase_order_items`
--

INSERT INTO `purchase_order_items` (`id`, `po_id`, `product_id`, `description`, `quantity`, `unit_price`, `total_price`) VALUES
(2, 1, 5, 'مكتب خشبي ', 40.00, 200.00, 8000.00);

-- --------------------------------------------------------

--
-- Table structure for table `purchase_requests`
--

CREATE TABLE `purchase_requests` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED DEFAULT 1,
  `branch_id` int(11) DEFAULT 0,
  `pr_number` varchar(50) NOT NULL,
  `request_date` date NOT NULL,
  `required_date` date NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `requested_by` varchar(100) DEFAULT NULL,
  `status` enum('draft','pending','approved','rejected','completed') NOT NULL DEFAULT 'pending',
  `total_estimated_value` decimal(15,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `purchase_requests`
--

INSERT INTO `purchase_requests` (`id`, `company_id`, `branch_id`, `pr_number`, `request_date`, `required_date`, `department`, `requested_by`, `status`, `total_estimated_value`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'PR-260822-54', '2026-08-22', '2026-08-25', 'IT', 'محمد أبوالمعاطي ', 'pending', 1350000.00, '', '2026-08-22 20:05:49', '2026-09-21 10:56:30');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_request_items`
--

CREATE TABLE `purchase_request_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `pr_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED DEFAULT NULL,
  `description` varchar(255) NOT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT 1.00,
  `estimated_price` decimal(15,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `purchase_request_items`
--

INSERT INTO `purchase_request_items` (`id`, `pr_id`, `product_id`, `description`, `quantity`, `estimated_price`) VALUES
(4, 1, 5, 'مكتب خشبي ', 50.00, 3000.00),
(5, 1, 1, 'لابتوب ', 40.00, 30000.00);

-- --------------------------------------------------------

--
-- Table structure for table `purchase_returns`
--

CREATE TABLE `purchase_returns` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED DEFAULT 1,
  `supplier_id` bigint(20) UNSIGNED NOT NULL,
  `invoice_id` bigint(20) UNSIGNED DEFAULT NULL,
  `return_number` varchar(50) NOT NULL,
  `return_date` date NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `status` enum('draft','approved','completed','cancelled') NOT NULL DEFAULT 'completed',
  `subtotal` decimal(15,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `purchase_returns`
--

INSERT INTO `purchase_returns` (`id`, `company_id`, `supplier_id`, `invoice_id`, `return_number`, `return_date`, `reason`, `status`, `subtotal`, `tax_amount`, `total_amount`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, 'PRT-260822-895', '2026-08-22', 'سبب الإرجاع', 'completed', 100.00, 15.00, 115.00, 'ملاحظات إضافية', '2026-08-22 21:53:21', '2026-08-22 21:53:21');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_return_items`
--

CREATE TABLE `purchase_return_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `return_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED DEFAULT NULL,
  `description` varchar(255) NOT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT 1.00,
  `unit_price` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_price` decimal(15,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `purchase_return_items`
--

INSERT INTO `purchase_return_items` (`id`, `return_id`, `product_id`, `description`, `quantity`, `unit_price`, `total_price`) VALUES
(1, 1, 3, 'حبر طابعه ', 2.00, 50.00, 100.00);

-- --------------------------------------------------------

--
-- Table structure for table `pur_orders`
--

CREATE TABLE `pur_orders` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL DEFAULT 1,
  `po_number` varchar(50) NOT NULL,
  `supplier_id` bigint(20) UNSIGNED NOT NULL,
  `order_date` date NOT NULL,
  `expected_delivery` date DEFAULT NULL,
  `status` enum('draft','sent','approved','received','cancelled') DEFAULT 'draft',
  `total_amount` decimal(15,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pur_order_lines`
--

CREATE TABLE `pur_order_lines` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `purchase_order_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `quantity` decimal(15,4) NOT NULL,
  `unit_price` decimal(15,4) NOT NULL,
  `total` decimal(15,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rfqs`
--

CREATE TABLE `rfqs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED DEFAULT 1,
  `rfq_number` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `request_date` date NOT NULL,
  `deadline_date` date NOT NULL,
  `status` enum('draft','published','closed','awarded') NOT NULL DEFAULT 'draft',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `rfqs`
--

INSERT INTO `rfqs` (`id`, `company_id`, `rfq_number`, `title`, `request_date`, `deadline_date`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, 'RFQ-260822-89', 'طلب تسعير أجهزة كمبيوتر ', '2026-08-22', '2026-08-29', 'closed', '', '2026-08-22 20:25:49', '2026-09-01 11:51:39');

-- --------------------------------------------------------

--
-- Table structure for table `rfq_items`
--

CREATE TABLE `rfq_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `rfq_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED DEFAULT NULL,
  `description` varchar(255) NOT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT 1.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `rfq_items`
--

INSERT INTO `rfq_items` (`id`, `rfq_id`, `product_id`, `description`, `quantity`) VALUES
(2, 1, 3, 'حبر طابعه ', 50.00);

-- --------------------------------------------------------

--
-- Table structure for table `rfq_suppliers`
--

CREATE TABLE `rfq_suppliers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `rfq_id` bigint(20) UNSIGNED NOT NULL,
  `supplier_id` bigint(20) UNSIGNED NOT NULL,
  `is_awarded` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `rfq_suppliers`
--

INSERT INTO `rfq_suppliers` (`id`, `rfq_id`, `supplier_id`, `is_awarded`) VALUES
(2, 1, 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `company_id`, `name`, `description`, `is_system`, `is_active`, `created_at`) VALUES
(1, 1, 'Super Administrator', NULL, 1, 1, '2026-08-22 12:53:33'),
(2, 1, 'Mohamed AboElmaaty', 'IT', 0, 1, '2026-08-25 02:03:29'),
(3, 1, 'Manager Human Resources (HR)', 'Head of HR', 0, 1, '2026-08-28 07:43:32'),
(4, 1, 'Finance & Accounting', 'Head OF Finance', 0, 1, '2026-08-28 07:46:00'),
(5, 1, 'Procurement & Purchasing', 'Head of Procurement & Purchasing', 0, 1, '2026-08-28 07:48:40'),
(6, 1, 'Customer Service', 'Customer Service ) staff)', 0, 1, '2026-08-28 07:51:08'),
(7, 1, 'Sales', 'Sales ) Staff )', 0, 1, '2026-08-28 07:52:51');

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

CREATE TABLE `role_permissions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `role_id` bigint(20) UNSIGNED NOT NULL,
  `permission_key` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `role_permissions`
--

INSERT INTO `role_permissions` (`id`, `role_id`, `permission_key`) VALUES
(10, 2, 'inventory.products.create'),
(12, 2, 'inventory.products.delete'),
(11, 2, 'inventory.products.edit'),
(9, 2, 'inventory.products.view'),
(6, 2, 'purchasing.suppliers.create'),
(8, 2, 'purchasing.suppliers.delete'),
(7, 2, 'purchasing.suppliers.edit'),
(5, 2, 'purchasing.suppliers.view'),
(2, 2, 'sales.customers.create'),
(4, 2, 'sales.customers.delete'),
(3, 2, 'sales.customers.edit'),
(1, 2, 'sales.customers.view'),
(36, 3, 'hr.attendance.create'),
(38, 3, 'hr.attendance.delete'),
(37, 3, 'hr.attendance.edit'),
(35, 3, 'hr.attendance.view'),
(32, 3, 'hr.contracts.create'),
(34, 3, 'hr.contracts.delete'),
(33, 3, 'hr.contracts.edit'),
(31, 3, 'hr.contracts.view'),
(28, 3, 'hr.employees.create'),
(30, 3, 'hr.employees.delete'),
(29, 3, 'hr.employees.edit'),
(27, 3, 'hr.employees.view'),
(40, 3, 'hr.payroll.create'),
(42, 3, 'hr.payroll.delete'),
(41, 3, 'hr.payroll.edit'),
(39, 3, 'hr.payroll.view'),
(24, 3, 'inventory.products.view'),
(26, 3, 'inventory.transfers.view'),
(25, 3, 'inventory.warehouses.view'),
(21, 3, 'purchasing.orders.create'),
(23, 3, 'purchasing.orders.delete'),
(22, 3, 'purchasing.orders.edit'),
(18, 3, 'purchasing.requisitions.create'),
(20, 3, 'purchasing.requisitions.delete'),
(19, 3, 'purchasing.requisitions.edit'),
(17, 3, 'sales.contracts.view'),
(13, 3, 'sales.customers.view'),
(16, 3, 'sales.invoices.view'),
(15, 3, 'sales.orders.view'),
(14, 3, 'sales.quotations.view'),
(44, 3, 'settings.roles.create'),
(46, 3, 'settings.roles.delete'),
(45, 3, 'settings.roles.edit'),
(43, 3, 'settings.roles.view'),
(48, 3, 'settings.users.create'),
(50, 3, 'settings.users.delete'),
(49, 3, 'settings.users.edit'),
(47, 3, 'settings.users.view'),
(85, 4, 'hr.attendance.view'),
(84, 4, 'hr.contracts.create'),
(83, 4, 'hr.contracts.view'),
(87, 4, 'hr.payroll.create'),
(88, 4, 'hr.payroll.edit'),
(86, 4, 'hr.payroll.view'),
(65, 4, 'inventory.products.view'),
(68, 4, 'inventory.transfers.create'),
(70, 4, 'inventory.transfers.delete'),
(69, 4, 'inventory.transfers.edit'),
(67, 4, 'inventory.transfers.view'),
(66, 4, 'inventory.warehouses.view'),
(63, 4, 'purchasing.invoices.create'),
(64, 4, 'purchasing.invoices.edit'),
(62, 4, 'purchasing.invoices.view'),
(61, 4, 'purchasing.orders.create'),
(60, 4, 'purchasing.orders.view'),
(59, 4, 'purchasing.requisitions.create'),
(58, 4, 'purchasing.requisitions.view'),
(57, 4, 'purchasing.suppliers.create'),
(56, 4, 'purchasing.suppliers.view'),
(55, 4, 'sales.contracts.view'),
(51, 4, 'sales.customers.view'),
(54, 4, 'sales.invoices.view'),
(53, 4, 'sales.orders.view'),
(52, 4, 'sales.quotations.view'),
(72, 4, 'treasury.accounts.create'),
(74, 4, 'treasury.accounts.delete'),
(73, 4, 'treasury.accounts.edit'),
(71, 4, 'treasury.accounts.view'),
(80, 4, 'treasury.payments.create'),
(82, 4, 'treasury.payments.delete'),
(81, 4, 'treasury.payments.edit'),
(79, 4, 'treasury.payments.view'),
(76, 4, 'treasury.receipts.create'),
(78, 4, 'treasury.receipts.delete'),
(77, 4, 'treasury.receipts.edit'),
(75, 4, 'treasury.receipts.view'),
(126, 5, 'inventory.products.create'),
(127, 5, 'inventory.products.edit'),
(125, 5, 'inventory.products.view'),
(129, 5, 'inventory.warehouses.create'),
(130, 5, 'inventory.warehouses.edit'),
(128, 5, 'inventory.warehouses.view'),
(122, 5, 'purchasing.invoices.create'),
(124, 5, 'purchasing.invoices.delete'),
(123, 5, 'purchasing.invoices.edit'),
(121, 5, 'purchasing.invoices.view'),
(118, 5, 'purchasing.orders.create'),
(120, 5, 'purchasing.orders.delete'),
(119, 5, 'purchasing.orders.edit'),
(117, 5, 'purchasing.orders.view'),
(114, 5, 'purchasing.requisitions.create'),
(116, 5, 'purchasing.requisitions.delete'),
(115, 5, 'purchasing.requisitions.edit'),
(113, 5, 'purchasing.requisitions.view'),
(110, 5, 'purchasing.suppliers.create'),
(112, 5, 'purchasing.suppliers.delete'),
(111, 5, 'purchasing.suppliers.edit'),
(109, 5, 'purchasing.suppliers.view'),
(106, 5, 'sales.contracts.create'),
(108, 5, 'sales.contracts.delete'),
(107, 5, 'sales.contracts.edit'),
(105, 5, 'sales.contracts.view'),
(90, 5, 'sales.customers.create'),
(92, 5, 'sales.customers.delete'),
(91, 5, 'sales.customers.edit'),
(89, 5, 'sales.customers.view'),
(102, 5, 'sales.invoices.create'),
(104, 5, 'sales.invoices.delete'),
(103, 5, 'sales.invoices.edit'),
(101, 5, 'sales.invoices.view'),
(98, 5, 'sales.orders.create'),
(100, 5, 'sales.orders.delete'),
(99, 5, 'sales.orders.edit'),
(97, 5, 'sales.orders.view'),
(94, 5, 'sales.quotations.create'),
(96, 5, 'sales.quotations.delete'),
(95, 5, 'sales.quotations.edit'),
(93, 5, 'sales.quotations.view'),
(132, 5, 'treasury.receipts.create'),
(133, 5, 'treasury.receipts.edit'),
(131, 5, 'treasury.receipts.view'),
(179, 6, 'hr.attendance.create'),
(178, 6, 'hr.attendance.view'),
(177, 6, 'hr.contracts.create'),
(176, 6, 'hr.contracts.view'),
(167, 6, 'inventory.products.create'),
(168, 6, 'inventory.products.edit'),
(166, 6, 'inventory.products.view'),
(170, 6, 'inventory.warehouses.create'),
(171, 6, 'inventory.warehouses.edit'),
(169, 6, 'inventory.warehouses.view'),
(163, 6, 'purchasing.invoices.create'),
(165, 6, 'purchasing.invoices.delete'),
(164, 6, 'purchasing.invoices.edit'),
(162, 6, 'purchasing.invoices.view'),
(159, 6, 'purchasing.orders.create'),
(161, 6, 'purchasing.orders.delete'),
(160, 6, 'purchasing.orders.edit'),
(158, 6, 'purchasing.orders.view'),
(155, 6, 'purchasing.requisitions.create'),
(157, 6, 'purchasing.requisitions.delete'),
(156, 6, 'purchasing.requisitions.edit'),
(154, 6, 'purchasing.requisitions.view'),
(151, 6, 'purchasing.suppliers.create'),
(153, 6, 'purchasing.suppliers.delete'),
(152, 6, 'purchasing.suppliers.edit'),
(150, 6, 'purchasing.suppliers.view'),
(148, 6, 'sales.contracts.create'),
(149, 6, 'sales.contracts.edit'),
(147, 6, 'sales.contracts.view'),
(135, 6, 'sales.customers.create'),
(137, 6, 'sales.customers.delete'),
(136, 6, 'sales.customers.edit'),
(134, 6, 'sales.customers.view'),
(145, 6, 'sales.invoices.create'),
(146, 6, 'sales.invoices.edit'),
(144, 6, 'sales.invoices.view'),
(142, 6, 'sales.orders.create'),
(143, 6, 'sales.orders.edit'),
(141, 6, 'sales.orders.view'),
(139, 6, 'sales.quotations.create'),
(140, 6, 'sales.quotations.edit'),
(138, 6, 'sales.quotations.view'),
(175, 6, 'treasury.payments.create'),
(174, 6, 'treasury.payments.view'),
(173, 6, 'treasury.receipts.create'),
(172, 6, 'treasury.receipts.view'),
(208, 7, 'inventory.products.create'),
(209, 7, 'inventory.products.edit'),
(207, 7, 'inventory.products.view'),
(211, 7, 'inventory.warehouses.create'),
(212, 7, 'inventory.warehouses.edit'),
(210, 7, 'inventory.warehouses.view'),
(205, 7, 'purchasing.invoices.create'),
(206, 7, 'purchasing.invoices.edit'),
(204, 7, 'purchasing.invoices.view'),
(202, 7, 'purchasing.orders.create'),
(203, 7, 'purchasing.orders.edit'),
(201, 7, 'purchasing.orders.view'),
(199, 7, 'purchasing.requisitions.create'),
(200, 7, 'purchasing.requisitions.edit'),
(198, 7, 'purchasing.requisitions.view'),
(196, 7, 'purchasing.suppliers.create'),
(197, 7, 'purchasing.suppliers.edit'),
(195, 7, 'purchasing.suppliers.view'),
(193, 7, 'sales.contracts.create'),
(194, 7, 'sales.contracts.edit'),
(192, 7, 'sales.contracts.view'),
(181, 7, 'sales.customers.create'),
(182, 7, 'sales.customers.edit'),
(180, 7, 'sales.customers.view'),
(190, 7, 'sales.invoices.create'),
(191, 7, 'sales.invoices.edit'),
(189, 7, 'sales.invoices.view'),
(187, 7, 'sales.orders.create'),
(188, 7, 'sales.orders.edit'),
(186, 7, 'sales.orders.view'),
(184, 7, 'sales.quotations.create'),
(185, 7, 'sales.quotations.edit'),
(183, 7, 'sales.quotations.view'),
(217, 7, 'treasury.payments.create'),
(218, 7, 'treasury.payments.edit'),
(216, 7, 'treasury.payments.view'),
(214, 7, 'treasury.receipts.create'),
(215, 7, 'treasury.receipts.edit'),
(213, 7, 'treasury.receipts.view');

-- --------------------------------------------------------

--
-- Table structure for table `sales_contracts`
--

CREATE TABLE `sales_contracts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED DEFAULT 1,
  `customer_id` bigint(20) UNSIGNED NOT NULL,
  `contract_number` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `billing_frequency` enum('one_time','monthly','quarterly','semi_annually','annually') NOT NULL DEFAULT 'monthly',
  `total_value` decimal(15,2) NOT NULL DEFAULT 0.00,
  `status` enum('draft','active','expired','terminated') NOT NULL DEFAULT 'draft',
  `terms_conditions` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `sales_contracts`
--

INSERT INTO `sales_contracts` (`id`, `company_id`, `customer_id`, `contract_number`, `title`, `start_date`, `end_date`, `billing_frequency`, `total_value`, `status`, `terms_conditions`, `created_at`, `updated_at`) VALUES
(1, 1, 2, 'CNT-2026085121', 'توريد بضائع ', '2026-08-22', '2027-08-22', 'monthly', 300000.00, 'active', 'الشروط والأحكام الخاصة\r\n', '2026-08-22 18:40:57', '2026-08-22 18:48:35'),
(2, 1, 11, 'Nour-  KSA contract 001', 'sales in KSA branch', '2026-08-22', '2027-08-22', 'monthly', 8000.00, 'active', 'test ', '2026-08-22 23:55:34', '2026-08-22 23:57:05');

-- --------------------------------------------------------

--
-- Table structure for table `sales_invoices`
--

CREATE TABLE `sales_invoices` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL DEFAULT 1,
  `branch_id` int(11) DEFAULT NULL,
  `invoice_number` varchar(50) NOT NULL,
  `customer_id` bigint(20) UNSIGNED NOT NULL,
  `issue_date` date NOT NULL,
  `due_date` date NOT NULL,
  `subtotal` decimal(15,2) DEFAULT 0.00,
  `tax_amount` decimal(15,2) DEFAULT 0.00,
  `total_amount` decimal(15,2) DEFAULT 0.00,
  `paid_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `status` enum('draft','unpaid','partially_paid','paid','cancelled') DEFAULT 'unpaid',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sales_invoices`
--

INSERT INTO `sales_invoices` (`id`, `company_id`, `branch_id`, `invoice_number`, `customer_id`, `issue_date`, `due_date`, `subtotal`, `tax_amount`, `total_amount`, `paid_amount`, `status`, `notes`, `created_at`) VALUES
(1, 1, NULL, 'INV-2026087928', 2, '2026-08-22', '2026-09-06', 21550.00, 3232.50, 24782.50, 24170.00, 'partially_paid', '', '2026-08-22 16:32:49'),
(2, 1, NULL, 'INV-2026087030', 12, '2026-08-23', '2026-09-07', 6100.00, 915.00, 7015.00, 0.00, 'partially_paid', 'as per the term and condition ', '2026-08-23 00:49:26'),
(3, 1, 1, 'INV-2026-OVERDU01', 2, '2026-07-01', '2026-08-01', 12000.00, 1800.00, 13800.00, 3800.00, 'partially_paid', 'فاتورة متأخرة السداد', '2026-09-21 11:09:41'),
(4, 1, 2, 'INV-2026-OVERDU02', 12, '2026-06-15', '2026-07-15', 25000.00, 3750.00, 28750.00, 0.00, 'unpaid', 'مستحقات متأخرة فرع جدة', '2026-09-21 11:09:41');

-- --------------------------------------------------------

--
-- Table structure for table `sales_invoice_lines`
--

CREATE TABLE `sales_invoice_lines` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `invoice_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED DEFAULT NULL,
  `description` varchar(255) NOT NULL,
  `quantity` decimal(15,2) NOT NULL DEFAULT 1.00,
  `unit_price` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total` decimal(15,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sales_invoice_lines`
--

INSERT INTO `sales_invoice_lines` (`id`, `invoice_id`, `product_id`, `description`, `quantity`, `unit_price`, `total`) VALUES
(3, 1, 2, 'ماك بوك برو 16 إنش', 1.00, 10500.00, 10500.00),
(4, 1, 2, 'ماك بوك برو 16 إنش', 1.00, 10500.00, 10500.00),
(5, 1, 3, 'كرسي مكتب طبي مريح', 1.00, 550.00, 550.00),
(6, 2, 6, 'خدمة تثبيت ودعم البرمجيات', 1.00, 150.00, 150.00),
(7, 2, 1, 'لابتوب ديل XPS 15', 1.00, 5800.00, 5800.00),
(8, 2, 6, 'خدمة تثبيت ودعم البرمجيات', 1.00, 150.00, 150.00);

-- --------------------------------------------------------

--
-- Table structure for table `sales_invoice_payments`
--

CREATE TABLE `sales_invoice_payments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `invoice_id` bigint(20) UNSIGNED NOT NULL,
  `amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `payment_method` varchar(50) DEFAULT 'cash',
  `payment_date` date NOT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `sales_invoice_payments`
--

INSERT INTO `sales_invoice_payments` (`id`, `invoice_id`, `amount`, `payment_method`, `payment_date`, `notes`, `created_at`) VALUES
(1, 1, 20000.00, 'bank_transfer', '2026-08-22', '4986434119841635', '2026-08-22 17:12:32'),
(2, 1, 4150.00, 'cheque', '2026-08-22', '498643411984166', '2026-08-22 17:20:31'),
(3, 1, 20.00, 'cash', '2026-08-22', '', '2026-08-22 17:45:28');

-- --------------------------------------------------------

--
-- Table structure for table `sales_orders`
--

CREATE TABLE `sales_orders` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL DEFAULT 1,
  `order_no` varchar(50) NOT NULL,
  `customer_id` bigint(20) UNSIGNED NOT NULL,
  `order_date` date NOT NULL,
  `expected_date` date DEFAULT NULL,
  `subtotal` decimal(15,2) DEFAULT 0.00,
  `tax_total` decimal(15,2) DEFAULT 0.00,
  `grand_total` decimal(15,2) DEFAULT 0.00,
  `status` enum('draft','confirmed','processing','shipped','delivered','cancelled') DEFAULT 'draft',
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sales_orders`
--

INSERT INTO `sales_orders` (`id`, `company_id`, `order_no`, `customer_id`, `order_date`, `expected_date`, `subtotal`, `tax_total`, `grand_total`, `status`, `notes`, `created_by`, `created_at`) VALUES
(1, 1, 'SO-2026089176', 2, '2026-08-22', '2026-08-25', 5800.00, 870.00, 6670.00, 'confirmed', '', NULL, '2026-08-22 13:42:34'),
(2, 1, 'SO-2026089697', 2, '2026-08-22', '2026-08-25', 10500.00, 1575.00, 12075.00, 'processing', '', NULL, '2026-08-22 13:44:28'),
(3, 1, 'SO-2026084977', 12, '2026-08-28', '2026-09-03', 10500.00, 1575.00, 12075.00, 'shipped', 'as per the term and condition ', NULL, '2026-08-28 07:59:13');

-- --------------------------------------------------------

--
-- Table structure for table `sales_order_lines`
--

CREATE TABLE `sales_order_lines` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED DEFAULT NULL,
  `description` varchar(255) NOT NULL,
  `quantity` decimal(15,2) NOT NULL DEFAULT 1.00,
  `unit_price` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total` decimal(15,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sales_order_lines`
--

INSERT INTO `sales_order_lines` (`id`, `order_id`, `product_id`, `description`, `quantity`, `unit_price`, `total`) VALUES
(1, 1, 1, 'لابتوب', 1.00, 5800.00, 5800.00),
(3, 2, 2, 'لابتوب', 1.00, 10500.00, 10500.00),
(4, 3, 2, 'computer ', 1.00, 10500.00, 10500.00);

-- --------------------------------------------------------

--
-- Table structure for table `sales_price_lists`
--

CREATE TABLE `sales_price_lists` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED DEFAULT 1,
  `branch_id` int(11) DEFAULT NULL,
  `code` varchar(50) NOT NULL,
  `name_ar` varchar(255) NOT NULL,
  `name_en` varchar(255) DEFAULT NULL,
  `currency` varchar(10) DEFAULT 'EGP',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `sales_price_lists`
--

INSERT INTO `sales_price_lists` (`id`, `company_id`, `branch_id`, `code`, `name_ar`, `name_en`, `currency`, `is_active`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, 'PL-147', 'اسم القائمة (عربي) *', 'اسم القائمة (إنجليزي)', 'EGP', 1, 'ملاحظات وشروط القائمة', '2026-08-22 17:57:25', '2026-08-22 17:57:25');

-- --------------------------------------------------------

--
-- Table structure for table `sales_price_list_items`
--

CREATE TABLE `sales_price_list_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `price_list_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `min_quantity` decimal(15,2) NOT NULL DEFAULT 1.00,
  `price` decimal(15,2) NOT NULL DEFAULT 0.00,
  `discount_percentage` decimal(5,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `sales_price_list_items`
--

INSERT INTO `sales_price_list_items` (`id`, `price_list_id`, `product_id`, `min_quantity`, `price`, `discount_percentage`) VALUES
(1, 1, 2, 1.00, 1100.00, 0.00),
(2, 1, 2, 1.00, 1000.00, 0.00),
(3, 1, 1, 1.00, 5800.00, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `sales_quotations`
--

CREATE TABLE `sales_quotations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED DEFAULT 1,
  `branch_id` int(11) DEFAULT NULL,
  `customer_id` bigint(20) UNSIGNED NOT NULL,
  `quote_number` varchar(50) NOT NULL,
  `issue_date` date NOT NULL,
  `expiry_date` date DEFAULT NULL,
  `status` enum('draft','sent','accepted','rejected') NOT NULL DEFAULT 'draft',
  `subtotal` decimal(15,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `sales_quotations`
--

INSERT INTO `sales_quotations` (`id`, `company_id`, `branch_id`, `customer_id`, `quote_number`, `issue_date`, `expiry_date`, `status`, `subtotal`, `tax_amount`, `total_amount`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 2, 'QT-2026083212', '2026-08-22', '2026-09-21', 'draft', 6350.00, 952.50, 7302.50, '', '2026-08-22 17:18:13', '2026-08-29 18:03:24'),
(2, 1, 1, 13, 'QT-2026086823', '2026-08-23', '2026-09-22', 'accepted', 59800.00, 8970.00, 68770.00, 'as per the term and condition ', '2026-08-23 00:45:33', '2026-08-29 18:03:24');

-- --------------------------------------------------------

--
-- Table structure for table `sales_quotation_lines`
--

CREATE TABLE `sales_quotation_lines` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `quotation_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED DEFAULT NULL,
  `description` text NOT NULL,
  `quantity` decimal(15,2) NOT NULL DEFAULT 1.00,
  `unit_price` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total` decimal(15,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `sales_quotation_lines`
--

INSERT INTO `sales_quotation_lines` (`id`, `quotation_id`, `product_id`, `description`, `quantity`, `unit_price`, `total`) VALUES
(1, 1, 1, 'لابتوب ديل XPS 15', 1.00, 5800.00, 5800.00),
(2, 1, 3, 'كرسي مكتب طبي مريح', 1.00, 550.00, 550.00),
(3, 2, 2, 'ماك بوك برو 16 إنش', 5.00, 10500.00, 52500.00),
(4, 2, 1, 'لابتوب ديل XPS 15', 1.00, 5800.00, 5800.00),
(5, 2, 6, 'خدمة تثبيت ودعم البرمجيات', 1.00, 150.00, 150.00),
(6, 2, 6, 'خدمة تثبيت ودعم البرمجيات', 1.00, 150.00, 150.00),
(7, 2, 4, 'مكتب إداري فاخر', 1.00, 1200.00, 1200.00);

-- --------------------------------------------------------

--
-- Table structure for table `sales_receipts`
--

CREATE TABLE `sales_receipts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED DEFAULT 1,
  `branch_id` int(11) DEFAULT NULL,
  `receipt_number` varchar(50) NOT NULL,
  `customer_id` bigint(20) UNSIGNED NOT NULL,
  `invoice_id` bigint(20) UNSIGNED DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `payment_method` enum('cash','bank_transfer','cheque') NOT NULL DEFAULT 'cash',
  `receipt_date` date NOT NULL,
  `reference_no` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `sales_receipts`
--

INSERT INTO `sales_receipts` (`id`, `company_id`, `branch_id`, `receipt_number`, `customer_id`, `invoice_id`, `amount`, `payment_method`, `receipt_date`, `reference_no`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, 'RCT-2026088906', 2, NULL, 2500.00, 'bank_transfer', '2026-08-22', '6453163164165', 'ملاحظات / البيان', '2026-08-22 17:43:52', '2026-08-22 17:43:52');

-- --------------------------------------------------------

--
-- Table structure for table `sales_representatives`
--

CREATE TABLE `sales_representatives` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED DEFAULT 1,
  `code` varchar(50) NOT NULL,
  `name_ar` varchar(255) NOT NULL,
  `name_en` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `commission_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `target_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `sales_representatives`
--

INSERT INTO `sales_representatives` (`id`, `company_id`, `code`, `name_ar`, `name_en`, `email`, `phone`, `commission_rate`, `target_amount`, `is_active`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, 'REP-583', 'محمد ابوالمعاطي ', 'Mohamed Abo-Elmaaty', 'mo.m3aty@gmail.com', '01275844735', 15.00, 3000.00, 1, '', '2026-08-22 18:04:03', '2026-08-22 18:32:48'),
(2, 1, 'Sales- nour-0001', 'خالد سالم علي البراك', 'Khalied Salim Ali Al-barak ', 'Khalied@gmail.com', '0539933993', 3.00, 10000.00, 1, 'KSA- sales team ', '2026-08-22 23:46:17', '2026-08-22 23:46:17'),
(3, 1, 'Sales - Nour -0002', 'عبدالله أحمد عبدالرحيم ', 'Abdullah Ahmed Abdullraheem ', 'Abdullah@gmail.com', '0566339983', 6.00, 2000.00, 1, 'KSA- sales team ', '2026-08-22 23:49:25', '2026-08-22 23:49:25'),
(4, 1, 'Sales - Nour -0003', 'علي سال الحسيني ', 'Ali Sal Alhusani ', 'Alisal@live.com', '056677990', 5.00, 8000.00, 1, 'KSA- sales team ', '2026-08-22 23:50:46', '2026-08-22 23:50:46'),
(5, 1, 'Sales - Nour -0004', 'مهند سالم الجوهري ', 'Muhanad Salim Al-jawhari ', 'Muhanad@gmail.com', '0585877338', 8.00, 100000.00, 1, 'UAE-sales team ', '2026-08-22 23:52:26', '2026-08-22 23:52:26');

-- --------------------------------------------------------

--
-- Table structure for table `sales_returns`
--

CREATE TABLE `sales_returns` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED DEFAULT 1,
  `branch_id` int(11) DEFAULT NULL,
  `customer_id` bigint(20) UNSIGNED NOT NULL,
  `invoice_id` bigint(20) UNSIGNED DEFAULT NULL,
  `return_number` varchar(50) NOT NULL,
  `return_date` date NOT NULL,
  `status` enum('draft','approved','completed','cancelled') NOT NULL DEFAULT 'draft',
  `subtotal` decimal(15,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `sales_returns`
--

INSERT INTO `sales_returns` (`id`, `company_id`, `branch_id`, `customer_id`, `invoice_id`, `return_number`, `return_date`, `status`, `subtotal`, `tax_amount`, `total_amount`, `reason`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, 8, NULL, 'SRN-2026089312', '2026-08-22', 'draft', 11050.00, 1657.50, 12707.50, '', '2026-08-22 17:51:07', '2026-08-22 17:51:07');

-- --------------------------------------------------------

--
-- Table structure for table `sales_return_lines`
--

CREATE TABLE `sales_return_lines` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `return_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED DEFAULT NULL,
  `description` text NOT NULL,
  `quantity` decimal(15,2) NOT NULL DEFAULT 1.00,
  `unit_price` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total` decimal(15,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `sales_return_lines`
--

INSERT INTO `sales_return_lines` (`id`, `return_id`, `product_id`, `description`, `quantity`, `unit_price`, `total`) VALUES
(1, 1, 2, 'ماك بوك برو 16 إنش', 1.00, 10500.00, 10500.00),
(2, 1, 3, 'كرسي مكتب طبي مريح', 1.00, 550.00, 550.00);

-- --------------------------------------------------------

--
-- Table structure for table `stock_adjustments`
--

CREATE TABLE `stock_adjustments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED DEFAULT 1,
  `branch_id` int(11) DEFAULT 0,
  `adjustment_number` varchar(50) NOT NULL,
  `warehouse_id` bigint(20) UNSIGNED NOT NULL,
  `adjustment_type` enum('addition','subtraction') NOT NULL DEFAULT 'addition',
  `adjustment_date` date NOT NULL,
  `status` enum('draft','approved','cancelled') NOT NULL DEFAULT 'draft',
  `reason` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `stock_adjustments`
--

INSERT INTO `stock_adjustments` (`id`, `company_id`, `branch_id`, `adjustment_number`, `warehouse_id`, `adjustment_type`, `adjustment_date`, `status`, `reason`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'ADJ-26082274', 2, 'addition', '2026-08-22', 'draft', 'جرد سنوي ', 'ملاحظات تفصيلية', '2026-08-22 23:41:16', '2026-09-07 23:00:09');

-- --------------------------------------------------------

--
-- Table structure for table `stock_adjustment_items`
--

CREATE TABLE `stock_adjustment_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `adjustment_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT 1.00,
  `unit_cost` decimal(15,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `stock_adjustment_items`
--

INSERT INTO `stock_adjustment_items` (`id`, `adjustment_id`, `product_id`, `quantity`, `unit_cost`) VALUES
(1, 1, 5, 8.00, 50.00),
(2, 1, 2, 20.00, 80.00);

-- --------------------------------------------------------

--
-- Table structure for table `stock_movements`
--

CREATE TABLE `stock_movements` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED DEFAULT 1,
  `branch_id` int(11) DEFAULT 0,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `warehouse_id` bigint(20) UNSIGNED NOT NULL,
  `movement_type` enum('in','out') NOT NULL,
  `reference_type` enum('purchase','sale','transfer_in','transfer_out','sales_return','purchase_return','adjustment_add','adjustment_sub') NOT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT 0.00,
  `balance_after` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `stock_movements`
--

INSERT INTO `stock_movements` (`id`, `company_id`, `branch_id`, `product_id`, `warehouse_id`, `movement_type`, `reference_type`, `reference_number`, `quantity`, `balance_after`, `created_at`) VALUES
(1, 1, 1, 5, 4, 'out', 'purchase_return', 'RET-26082225', 1.00, 0.00, '2026-08-22 23:58:15'),
(2, 1, 2, 5, 4, 'out', 'purchase_return', 'RET-26082225', 1.00, 0.00, '2026-08-23 00:01:37'),
(3, 1, 0, 1, 1, 'out', 'transfer_out', 'TRN-26082264', 50.00, 0.00, '2026-09-08 01:02:43'),
(4, 1, 0, 1, 2, 'in', 'transfer_in', 'TRN-26082264', 50.00, 0.00, '2026-09-08 01:02:43');

-- --------------------------------------------------------

--
-- Table structure for table `stock_returns`
--

CREATE TABLE `stock_returns` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED DEFAULT 1,
  `branch_id` int(11) DEFAULT 0,
  `return_number` varchar(50) NOT NULL,
  `return_type` enum('sales_return','purchase_return') NOT NULL DEFAULT 'sales_return',
  `warehouse_id` bigint(20) UNSIGNED NOT NULL,
  `party_name` varchar(255) NOT NULL,
  `return_date` date NOT NULL,
  `status` enum('draft','approved','cancelled') NOT NULL DEFAULT 'draft',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `stock_returns`
--

INSERT INTO `stock_returns` (`id`, `company_id`, `branch_id`, `return_number`, `return_type`, `warehouse_id`, `party_name`, `return_date`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'RET-26082225', 'purchase_return', 4, 'محمد أبو المعاطي', '2026-08-22', 'approved', '', '2026-08-22 23:26:03', '2026-09-07 23:00:01'),
(2, 1, 2, 'RET-26082226', 'purchase_return', 2, 'محمد أبو المعاطي', '2026-08-22', 'draft', '', '2026-08-22 23:30:08', '2026-09-07 23:00:03');

-- --------------------------------------------------------

--
-- Table structure for table `stock_return_items`
--

CREATE TABLE `stock_return_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `return_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT 1.00,
  `reason` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `stock_return_items`
--

INSERT INTO `stock_return_items` (`id`, `return_id`, `product_id`, `quantity`, `reason`) VALUES
(1, 1, 5, 1.00, 'تالف'),
(2, 2, 1, 1.00, 'تالف');

-- --------------------------------------------------------

--
-- Table structure for table `stock_transfers`
--

CREATE TABLE `stock_transfers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED DEFAULT 1,
  `branch_id` int(11) DEFAULT 0,
  `transfer_number` varchar(50) NOT NULL,
  `from_warehouse_id` bigint(20) UNSIGNED NOT NULL,
  `to_warehouse_id` bigint(20) UNSIGNED NOT NULL,
  `transfer_date` date NOT NULL,
  `status` enum('draft','in_transit','completed','cancelled') NOT NULL DEFAULT 'draft',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `stock_transfers`
--

INSERT INTO `stock_transfers` (`id`, `company_id`, `branch_id`, `transfer_number`, `from_warehouse_id`, `to_warehouse_id`, `transfer_date`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'TRN-26082264', 1, 2, '2026-08-22', 'completed', 'بيان وملاحظات النقل', '2026-08-22 22:57:52', '2026-09-08 01:02:43'),
(2, 1, 2, 'TRN-26082292', 5, 4, '2026-08-22', 'draft', '', '2026-08-22 23:00:40', '2026-09-07 23:00:25');

-- --------------------------------------------------------

--
-- Table structure for table `stock_transfer_items`
--

CREATE TABLE `stock_transfer_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `transfer_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT 1.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `stock_transfer_items`
--

INSERT INTO `stock_transfer_items` (`id`, `transfer_id`, `product_id`, `quantity`) VALUES
(3, 2, 5, 1.00),
(4, 1, 1, 50.00);

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL DEFAULT 1,
  `code` varchar(50) NOT NULL,
  `name_en` varchar(255) NOT NULL,
  `name_ar` varchar(255) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `tax_number` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `type` enum('Company','Individual') DEFAULT 'Company',
  `credit_limit` decimal(15,2) DEFAULT 0.00,
  `balance` decimal(15,2) DEFAULT 0.00,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`id`, `company_id`, `code`, `name_en`, `name_ar`, `email`, `phone`, `tax_number`, `address`, `type`, `credit_limit`, `balance`, `is_active`, `created_at`, `notes`) VALUES
(1, 1, 'SUP-1787426164', 'Mohamed Abo-Elmaaty', 'محمد ابوالمعاطي ', 'mo.m3aty@gmail.com', '01275844735', '976465461968456413', NULL, 'Company', 5000.00, 0.00, 1, '2026-08-22 19:16:04', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `supplier_evaluations`
--

CREATE TABLE `supplier_evaluations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED DEFAULT 1,
  `supplier_id` bigint(20) UNSIGNED NOT NULL,
  `eval_number` varchar(50) NOT NULL,
  `evaluation_date` date NOT NULL,
  `evaluator_name` varchar(255) DEFAULT NULL,
  `period_covered` varchar(100) DEFAULT NULL,
  `delivery_score` decimal(5,2) NOT NULL DEFAULT 0.00,
  `quality_score` decimal(5,2) NOT NULL DEFAULT 0.00,
  `price_score` decimal(5,2) NOT NULL DEFAULT 0.00,
  `service_score` decimal(5,2) NOT NULL DEFAULT 0.00,
  `overall_score` decimal(5,2) NOT NULL DEFAULT 0.00,
  `grade` varchar(10) DEFAULT 'B',
  `status` enum('draft','approved') NOT NULL DEFAULT 'approved',
  `recommendation` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `supplier_evaluations`
--

INSERT INTO `supplier_evaluations` (`id`, `company_id`, `supplier_id`, `eval_number`, `evaluation_date`, `evaluator_name`, `period_covered`, `delivery_score`, `quality_score`, `price_score`, `service_score`, `overall_score`, `grade`, `status`, `recommendation`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'EVL-202608907', '2026-08-22', 'علاء السيد', '2026', 85.00, 90.00, 80.00, 85.00, 85.00, 'A', 'approved', 'التوصيات والقرار\r\n', '2026-08-22 19:30:28', '2026-08-22 19:30:28');

-- --------------------------------------------------------

--
-- Table structure for table `supplier_price_lists`
--

CREATE TABLE `supplier_price_lists` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED DEFAULT 1,
  `supplier_id` bigint(20) UNSIGNED NOT NULL,
  `list_number` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `valid_from` date NOT NULL,
  `valid_to` date NOT NULL,
  `currency` varchar(10) DEFAULT 'EGP',
  `status` enum('draft','active','expired') NOT NULL DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `supplier_price_lists`
--

INSERT INTO `supplier_price_lists` (`id`, `company_id`, `supplier_id`, `list_number`, `title`, `valid_from`, `valid_to`, `currency`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'SPL-260863', 'اسعار توريد الصيف 2026', '2026-08-22', '2027-08-22', '3000 EGP', 'active', '', '2026-08-22 20:00:43', '2026-08-22 20:00:43');

-- --------------------------------------------------------

--
-- Table structure for table `supplier_price_list_items`
--

CREATE TABLE `supplier_price_list_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `price_list_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `unit_price` decimal(15,2) NOT NULL DEFAULT 0.00,
  `min_order_qty` decimal(10,2) NOT NULL DEFAULT 1.00,
  `discount_percent` decimal(5,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `supplier_price_list_items`
--

INSERT INTO `supplier_price_list_items` (`id`, `price_list_id`, `product_id`, `unit_price`, `min_order_qty`, `discount_percent`) VALUES
(1, 1, 4, 200.00, 400.00, 0.00),
(2, 1, 2, 400.00, 800.00, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `sys_audit_logs`
--

CREATE TABLE `sys_audit_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `user_name` varchar(255) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `module` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sys_backups`
--

CREATE TABLE `sys_backups` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `filename` varchar(255) NOT NULL,
  `file_size` varchar(50) NOT NULL,
  `created_by` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sys_branches`
--

CREATE TABLE `sys_branches` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(50) NOT NULL,
  `name_ar` varchar(255) NOT NULL,
  `city` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sys_branches`
--

INSERT INTO `sys_branches` (`id`, `company_id`, `code`, `name_ar`, `city`, `is_active`, `created_at`) VALUES
(1, 1, 'BRN-001', 'KSA mean branch', 'Al-Rayadh', 1, '2026-08-27 06:39:11'),
(2, 1, 'BRN-002', 'Jeddah branch', 'Jedah - Al-Nuzha', 1, '2026-08-28 07:36:26'),
(3, 1, 'BRN-003', 'Oman mean  branch', 'Muscat', 1, '2026-08-28 07:36:47'),
(4, 1, 'BRN-004', 'UAE mean  branch', 'Aboudhabi', 1, '2026-08-28 07:37:11');

-- --------------------------------------------------------

--
-- Table structure for table `sys_companies`
--

CREATE TABLE `sys_companies` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(50) NOT NULL,
  `name_ar` varchar(255) NOT NULL,
  `tax_number` varchar(100) DEFAULT NULL,
  `cr_number` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sys_companies`
--

INSERT INTO `sys_companies` (`id`, `code`, `name_ar`, `tax_number`, `cr_number`, `is_active`, `created_at`) VALUES
(1, 'CMP-001', 'اسم الشركة بالعربية *', '976465461968456413', '4654354684516', 1, '2026-08-27 06:15:18');

-- --------------------------------------------------------

--
-- Table structure for table `sys_notifications`
--

CREATE TABLE `sys_notifications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` varchar(50) DEFAULT 'info' COMMENT 'info, approval, alert, warning',
  `link` varchar(255) DEFAULT '#',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sys_roles`
--

CREATE TABLE `sys_roles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `role_code` varchar(50) NOT NULL,
  `title_ar` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sys_role_permissions`
--

CREATE TABLE `sys_role_permissions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `role_id` bigint(20) UNSIGNED NOT NULL,
  `module` varchar(100) NOT NULL,
  `permission_key` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sys_settings`
--

CREATE TABLE `sys_settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sys_users`
--

CREATE TABLE `sys_users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_code` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(191) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role_id` bigint(20) UNSIGNED DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `taxes`
--

CREATE TABLE `taxes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(50) NOT NULL,
  `name_ar` varchar(255) NOT NULL,
  `tax_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `name_en` varchar(255) NOT NULL,
  `type` enum('percentage','fixed') NOT NULL DEFAULT 'percentage',
  `rate` decimal(5,2) NOT NULL DEFAULT 15.00,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `company_id` bigint(20) UNSIGNED DEFAULT 1,
  `branch_id` int(11) DEFAULT 0,
  `tax_type` enum('vat','wht','sales','other') NOT NULL DEFAULT 'vat',
  `account_id` bigint(20) UNSIGNED DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `taxes`
--

INSERT INTO `taxes` (`id`, `code`, `name_ar`, `tax_rate`, `name_en`, `type`, `rate`, `is_active`, `company_id`, `branch_id`, `tax_type`, `account_id`, `notes`) VALUES
(1, 'CUST-61551787404225', 'محمد ابوالمعاطي', 14.00, 'Mohamed Abo-Elmaaty', 'percentage', 15.00, 1, 1, 1, 'vat', 1, 'اضافة راس مال'),
(2, '', 'Tax Name (AR) *', 0.00, 'Tax Name (EN)', 'percentage', 10.00, 1, 1, 1, 'vat', 1, 'Notes');

-- --------------------------------------------------------

--
-- Table structure for table `treasury_cheques`
--

CREATE TABLE `treasury_cheques` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED DEFAULT 1,
  `branch_id` int(11) DEFAULT 0,
  `cheque_number` varchar(100) NOT NULL,
  `type` enum('received','issued') NOT NULL DEFAULT 'received',
  `bank_name` varchar(255) NOT NULL,
  `treasury_account_id` bigint(20) UNSIGNED NOT NULL,
  `payee_payer_name` varchar(255) NOT NULL,
  `amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `issue_date` date NOT NULL,
  `due_date` date NOT NULL,
  `status` enum('pending','collected','bounced','cancelled') NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `treasury_cheques`
--

INSERT INTO `treasury_cheques` (`id`, `company_id`, `branch_id`, `cheque_number`, `type`, `bank_name`, `treasury_account_id`, `payee_payer_name`, `amount`, `issue_date`, `due_date`, `status`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 1, 0, '46465416', 'received', 'اسم البنك المسحوب عليه *', 8, 'اسم المستفيد / الساحب *', 5000.00, '2026-08-25', '2026-08-25', 'pending', 'ملاحظات وشروط الشيك', 1, '2026-08-25 03:38:23', '2026-08-25 03:38:23'),
(2, 1, 1, 'CHQ-99012', 'received', 'البنك الأهلي السعودي', 1, 'شركة الأمل للمقاولات', 32000.00, '2026-09-10', '2026-09-25', 'pending', 'شيك برسم التحصيل عن فاتورة 401', 1, '2026-09-21 09:12:53', '2026-09-21 09:12:53'),
(3, 1, 1, 'CHQ-99013', 'issued', 'بنك مصر', 1, 'شركة السلام للتوريدات', 15000.00, '2026-09-01', '2026-09-18', 'collected', 'شيك صادر تم تحصيله بنجاح', 1, '2026-09-21 09:12:53', '2026-09-21 09:12:53'),
(4, 1, 2, 'CHQ-JED-01', 'received', 'مصرف الراجحي', 11, 'خالد البراك', 28000.00, '2026-09-12', '2026-09-22', 'pending', 'شيك مسحوب لحساب فرع جدة', 1, '2026-09-21 09:12:53', '2026-09-21 09:12:53'),
(5, 1, 3, 'CHQ-OMN-01', 'received', 'بنك مسقط', 13, 'مؤسسة مسقط للتجارة', 14500.00, '2026-09-05', '2026-09-15', 'bounced', 'مرتد لعدم كفاية الرصيد', 1, '2026-09-21 09:12:53', '2026-09-21 09:12:53');

-- --------------------------------------------------------

--
-- Table structure for table `treasury_payments`
--

CREATE TABLE `treasury_payments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED DEFAULT 1,
  `branch_id` int(11) DEFAULT 0,
  `voucher_number` varchar(100) NOT NULL,
  `payment_date` date NOT NULL,
  `treasury_account_id` bigint(20) UNSIGNED NOT NULL,
  `supplier_id` bigint(20) UNSIGNED DEFAULT NULL,
  `payee_name` varchar(255) DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `payment_method` enum('cash','bank_transfer','cheque','pos') NOT NULL DEFAULT 'cash',
  `reference_no` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `treasury_payments`
--

INSERT INTO `treasury_payments` (`id`, `company_id`, `branch_id`, `voucher_number`, `payment_date`, `treasury_account_id`, `supplier_id`, `payee_name`, `amount`, `payment_method`, `reference_no`, `description`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 1, 0, 'PV-2026-00001', '2026-08-25', 8, 1, 'اسم المستفيد صراحة (اصرفوا إلى)', 8000.00, 'cheque', '6453163164165', 'البيان والوصف التفصيلي', 1, '2026-08-25 03:18:29', '2026-08-25 03:18:29'),
(2, 1, 1, 'PV-2026-00002', '2026-09-16', 2, 1, 'محمد ابوالمعاطي', 12500.00, 'cash', 'CSH-OUT-10', 'سداد مستحقات توريد قطع غيار', 1, '2026-09-21 09:12:53', '2026-09-21 09:12:53'),
(3, 1, 1, 'PV-2026-00003', '2026-09-17', 1, 1, 'شركة الخليج للتوريدات', 22000.00, 'bank_transfer', 'TRF-OUT-88', 'تحويل بنكي مقابل فاتورة مشتريات', 1, '2026-09-21 09:12:53', '2026-09-21 09:12:53'),
(4, 1, 2, 'PV-2026-00004', '2026-09-19', 10, NULL, 'شركة الكهرباء والمرافق', 3400.00, 'cash', 'UTIL-009', 'سداد فاتورة الكهرباء والصيانة', 1, '2026-09-21 09:12:53', '2026-09-21 09:12:53'),
(5, 1, 2, 'PV-2026-00005', '2026-09-20', 11, 1, 'محمد ابوالمعاطي', 18000.00, 'cheque', 'CHQ-ISS-01', 'شيك صادر للمورد مقابل بضاعة', 1, '2026-09-21 09:12:53', '2026-09-21 09:12:53'),
(6, 1, 3, 'PV-2026-00006', '2026-09-21', 12, NULL, 'مالك العقار - مسقط', 8500.00, 'cash', 'RENT-2026', 'سداد إيجار مكتب فرع عمان', 1, '2026-09-21 09:12:53', '2026-09-21 09:12:53');

-- --------------------------------------------------------

--
-- Table structure for table `treasury_petty_cash`
--

CREATE TABLE `treasury_petty_cash` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED DEFAULT 1,
  `branch_id` int(11) DEFAULT 0,
  `code` varchar(100) NOT NULL,
  `employee_name` varchar(255) NOT NULL,
  `treasury_account_id` bigint(20) UNSIGNED NOT NULL,
  `amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `spent_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `remaining_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `issue_date` date NOT NULL,
  `status` enum('active','partially_settled','closed') NOT NULL DEFAULT 'active',
  `description` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `treasury_petty_cash`
--

INSERT INTO `treasury_petty_cash` (`id`, `company_id`, `branch_id`, `code`, `employee_name`, `treasury_account_id`, `amount`, `spent_amount`, `remaining_amount`, `issue_date`, `status`, `description`, `created_by`, `created_at`, `updated_at`) VALUES
(2, 1, 1, 'PC-2026-00002', 'أحمد خليل - مسؤول الضيافة', 2, 5000.00, 1200.00, 3800.00, '2026-09-01', 'partially_settled', 'عهدة نثريات وضيافة المكاتب الرئيسية', 1, '2026-09-21 09:12:53', '2026-09-21 09:12:53'),
(3, 1, 2, 'PC-2026-00003', 'سارة السيد - المشتريات', 10, 10000.00, 4500.00, 5500.00, '2026-09-10', 'partially_settled', 'عهدة مستلزمات تشغيلية لفرع جدة', 1, '2026-09-21 09:12:53', '2026-09-21 09:12:53'),
(4, 1, 3, 'PC-2026-00004', 'مهند الجوهري - المبيعات', 12, 3000.00, 3000.00, 0.00, '2026-09-05', 'closed', 'عهدة مصاريف انتقال وسفريات', 1, '2026-09-21 09:12:53', '2026-09-21 09:12:53');

-- --------------------------------------------------------

--
-- Table structure for table `treasury_receipts`
--

CREATE TABLE `treasury_receipts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED DEFAULT 1,
  `branch_id` int(11) DEFAULT 0,
  `voucher_number` varchar(100) NOT NULL,
  `receipt_date` date NOT NULL,
  `treasury_account_id` bigint(20) UNSIGNED NOT NULL,
  `customer_id` bigint(20) UNSIGNED DEFAULT NULL,
  `payer_name` varchar(255) DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `payment_method` enum('cash','bank_transfer','cheque','pos') NOT NULL DEFAULT 'cash',
  `reference_no` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `treasury_receipts`
--

INSERT INTO `treasury_receipts` (`id`, `company_id`, `branch_id`, `voucher_number`, `receipt_date`, `treasury_account_id`, `customer_id`, `payer_name`, `amount`, `payment_method`, `reference_no`, `description`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 1, 0, 'RV-2026-00001', '2026-08-25', 1, 2, 'اسم الدافع / المسلم صراحة', 5000.00, 'bank_transfer', '6453163164165', 'البيان والوصف التفصيلي', 1, '2026-08-25 03:07:50', '2026-08-25 03:07:50'),
(2, 1, 1, 'RV-2026-00002', '2026-09-15', 2, 2, 'محمد ابوالمعاطي', 15000.00, 'cash', 'CSH-9921', 'دفعة من حساب فاتورة مبيعات', 1, '2026-09-21 09:12:53', '2026-09-21 09:12:53'),
(3, 1, 1, 'RV-2026-00003', '2026-09-18', 1, 8, 'علاء السيد', 28500.00, 'bank_transfer', 'TRF-88129', 'تحويل بنكي سداد مستحقات', 1, '2026-09-21 09:12:53', '2026-09-21 09:12:53'),
(4, 1, 2, 'RV-2026-00004', '2026-09-19', 10, 12, 'عبدالله أحمد عبدالرحيم', 12000.00, 'cash', 'CSH-5012', 'تحصيل نقدي مبيعات فرع جدة', 1, '2026-09-21 09:12:53', '2026-09-21 09:12:53'),
(5, 1, 2, 'RV-2026-00005', '2026-09-20', 11, 13, 'خالد سالم علي البراك', 45000.00, 'bank_transfer', 'NCB-44120', 'سداد دفعة العقد السنوي', 1, '2026-09-21 09:12:53', '2026-09-21 09:12:53'),
(6, 1, 3, 'RV-2026-00006', '2026-09-21', 13, NULL, 'شركة النور الدولية', 18500.00, 'cheque', 'CHQ-77120', 'شيك وارد برسم التحصيل', 1, '2026-09-21 09:12:53', '2026-09-21 09:12:53'),
(7, 1, 4, 'RV-2026-00007', '2026-09-21', 14, NULL, 'مستثمر خارجي', 35000.00, 'pos', 'POS-9982', 'مقبوضات شبكة مدى / نقطة بيع', 1, '2026-09-21 09:12:53', '2026-09-21 09:12:53');

-- --------------------------------------------------------

--
-- Table structure for table `treasury_transfers`
--

CREATE TABLE `treasury_transfers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED DEFAULT 1,
  `branch_id` int(11) DEFAULT 0,
  `transfer_number` varchar(100) NOT NULL,
  `transfer_date` date NOT NULL,
  `from_account_id` bigint(20) UNSIGNED NOT NULL,
  `to_account_id` bigint(20) UNSIGNED NOT NULL,
  `amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `reference_no` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `treasury_transfers`
--

INSERT INTO `treasury_transfers` (`id`, `company_id`, `branch_id`, `transfer_number`, `transfer_date`, `from_account_id`, `to_account_id`, `amount`, `reference_no`, `description`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 1, 0, 'TRF-2026-00001', '2026-08-25', 1, 8, 20000.00, '6453163164165', 'البيان والوصف التفصيلي', 1, '2026-08-25 03:21:48', '2026-08-25 03:21:48'),
(2, 1, 1, 'TRF-2026-00002', '2026-09-18', 2, 1, 50000.00, 'DEP-8812', 'إيداع نقدي من الخزينة الرئيسية إلى البنك', 1, '2026-09-21 09:12:53', '2026-09-21 09:12:53'),
(3, 1, 2, 'TRF-2026-00003', '2026-09-20', 10, 11, 25000.00, 'DEP-JED-01', 'تغذية حساب البنك الأهلي من خزينة جدة', 1, '2026-09-21 09:12:53', '2026-09-21 09:12:53'),
(4, 1, 3, 'TRF-2026-00004', '2026-09-21', 13, 12, 10000.00, 'WTH-OMN-02', 'سحب نقدي من بنك مسقط لتغذية الخزينة', 1, '2026-09-21 09:12:53', '2026-09-21 09:12:53');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `branch_id` bigint(20) UNSIGNED DEFAULT NULL,
  `role_id` bigint(20) UNSIGNED DEFAULT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `language` varchar(10) DEFAULT 'ar',
  `timezone` varchar(100) DEFAULT 'Asia/Riyadh',
  `status` varchar(20) DEFAULT 'active',
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `company_id`, `branch_id`, `role_id`, `username`, `email`, `password_hash`, `language`, `timezone`, `status`, `last_login`, `created_at`) VALUES
(1, 1, NULL, 1, 'superadmin', 'admin@nourtrust.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ar', 'Asia/Riyadh', 'active', NULL, '2026-08-22 12:53:33'),
(2, 1, NULL, 2, 'Mohamed Aboelmaaty', 'mo.m3aty@gmail.com', '$2y$10$q3IFZL4NLX1AMLXli8EqqOymktP6ckpmIrrNKKcD0mJAQ/PvLeJdW', 'ar', 'Asia/Riyadh', 'active', NULL, '2026-08-25 02:04:04'),
(3, 1, NULL, 3, 'Roqayah', 'R@nourtrust.com', '$2y$10$BfbZVKn.Vl7SFK36ZSbwHeTaMkjJskrS24Ixk0jALeNAxaNBlx5P.', 'en', 'Asia/Riyadh', 'active', NULL, '2026-08-28 07:54:23'),
(4, 1, NULL, 6, 'Abdullmakik', 'A@nourtrust.com', '$2y$10$8offGtaG8KeBJk4ZDiH1feOBc19hmqvrzGX9jDxJUcIB9SibF352W', 'en', 'Asia/Riyadh', 'active', NULL, '2026-08-28 07:55:10'),
(5, 1, NULL, 4, 'Sarah', 'S@nourtrust.com', '$2y$10$iyNBJtP96ZuG77yJZkqpCOd8Y97a1/YTtH6mjoS0LGu2.4513BQR6', 'en', 'Asia/Riyadh', 'active', NULL, '2026-08-28 07:56:04');

-- --------------------------------------------------------

--
-- Table structure for table `warehouses`
--

CREATE TABLE `warehouses` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED DEFAULT 1,
  `branch_id` int(11) DEFAULT 0,
  `code` varchar(50) NOT NULL,
  `name_ar` varchar(255) NOT NULL,
  `name_en` varchar(255) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `manager_name` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `warehouses`
--

INSERT INTO `warehouses` (`id`, `company_id`, `branch_id`, `code`, `name_ar`, `name_en`, `location`, `manager_name`, `phone`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'WH-246', 'المستودع الرئيسي', 'Main ', 'موقع المستودع / العنوان', 'محمد أبوالمعاطي', '01275844735', 1, '2026-08-22 22:50:42', '2026-09-08 00:56:03'),
(2, 1, 2, 'WH-RUH-01', 'Riyadh Branch Warehouse', NULL, 'Riyadh, Olaya', NULL, NULL, 1, '2026-08-22 22:53:30', '2026-09-08 00:56:08'),
(3, 1, 3, 'WH-001', 'المستودع الرئيسي', '', 'المبنى الرئيسي - المنطقة الصناعية', '', '', 0, '2026-08-22 22:53:30', '2026-09-08 00:56:11'),
(4, 1, 4, 'WH-002', 'مستودع المعرض', NULL, 'فرع المعرض العام', NULL, NULL, 1, '2026-08-22 22:53:30', '2026-09-08 00:56:13'),
(5, 1, 1, 'WH-003', 'Warehouse Name (Arabic) ', 'Warehouse Name (English)', 'مبنى الخدمات الملحق', '', '', 1, '2026-08-22 22:53:30', '2026-09-08 00:57:08');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `accounts`
--
ALTER TABLE `accounts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `parent_id` (`parent_id`),
  ADD KEY `company_id` (`company_id`);

--
-- Indexes for table `asset_depreciations`
--
ALTER TABLE `asset_depreciations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `asset_id` (`asset_id`);

--
-- Indexes for table `bank_reconciliations`
--
ALTER TABLE `bank_reconciliations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `reconciliation_number` (`reconciliation_number`),
  ADD KEY `account_id` (`account_id`);

--
-- Indexes for table `bank_reconciliation_items`
--
ALTER TABLE `bank_reconciliation_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reconciliation_id` (`reconciliation_id`),
  ADD KEY `journal_entry_item_id` (`journal_entry_item_id`);

--
-- Indexes for table `branches`
--
ALTER TABLE `branches`
  ADD PRIMARY KEY (`id`),
  ADD KEY `company_id` (`company_id`);

--
-- Indexes for table `budgets`
--
ALTER TABLE `budgets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `budget_items`
--
ALTER TABLE `budget_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `budget_id` (`budget_id`),
  ADD KEY `account_id` (`account_id`);

--
-- Indexes for table `companies`
--
ALTER TABLE `companies`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `company_code` (`company_code`);

--
-- Indexes for table `cost_centers`
--
ALTER TABLE `cost_centers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `company_id` (`company_id`);

--
-- Indexes for table `crm_leads`
--
ALTER TABLE `crm_leads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `company_id` (`company_id`),
  ADD KEY `owner_id` (`owner_id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `company_id` (`company_id`);

--
-- Indexes for table `delivery_notes`
--
ALTER TABLE `delivery_notes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `delivery_number_unique` (`delivery_number`,`company_id`),
  ADD KEY `fk_dn_wh` (`warehouse_id`);

--
-- Indexes for table `delivery_note_items`
--
ALTER TABLE `delivery_note_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_dni_dn` (`delivery_note_id`),
  ADD KEY `fk_dni_prod` (`product_id`);

--
-- Indexes for table `fiscal_periods`
--
ALTER TABLE `fiscal_periods`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fiscal_year_id` (`fiscal_year_id`);

--
-- Indexes for table `fiscal_sub_periods`
--
ALTER TABLE `fiscal_sub_periods`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fiscal_period_id` (`fiscal_period_id`);

--
-- Indexes for table `fiscal_years`
--
ALTER TABLE `fiscal_years`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `fixed_assets`
--
ALTER TABLE `fixed_assets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `goods_receipts`
--
ALTER TABLE `goods_receipts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `receipt_number_unique` (`receipt_number`,`company_id`),
  ADD KEY `fk_grn_supplier` (`supplier_id`),
  ADD KEY `fk_grn_po` (`po_id`);

--
-- Indexes for table `goods_receipt_items`
--
ALTER TABLE `goods_receipt_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_grn_items_grn` (`receipt_id`);

--
-- Indexes for table `hr_appraisals`
--
ALTER TABLE `hr_appraisals`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_appraisal_code` (`appraisal_code`),
  ADD KEY `idx_appraisal_emp` (`employee_id`),
  ADD KEY `idx_appraisal_status` (`status`);

--
-- Indexes for table `hr_attendance`
--
ALTER TABLE `hr_attendance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_att_emp` (`employee_id`);

--
-- Indexes for table `hr_departments`
--
ALTER TABLE `hr_departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_dept_code` (`code`);

--
-- Indexes for table `hr_designations`
--
ALTER TABLE `hr_designations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_desig_code` (`code`),
  ADD KEY `idx_desig_dept` (`department_id`);

--
-- Indexes for table `hr_documents`
--
ALTER TABLE `hr_documents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_doc_code` (`document_code`),
  ADD KEY `idx_doc_emp` (`employee_id`),
  ADD KEY `idx_doc_status` (`status`),
  ADD KEY `idx_doc_type` (`document_type`);

--
-- Indexes for table `hr_employees`
--
ALTER TABLE `hr_employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_emp_code` (`emp_code`),
  ADD KEY `idx_emp_dept` (`department_id`),
  ADD KEY `idx_emp_desig` (`designation_id`);

--
-- Indexes for table `hr_employee_contracts`
--
ALTER TABLE `hr_employee_contracts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_contract_code` (`contract_code`),
  ADD KEY `idx_contract_emp` (`employee_id`);

--
-- Indexes for table `hr_leaves`
--
ALTER TABLE `hr_leaves`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_leave_emp` (`employee_id`);

--
-- Indexes for table `hr_payroll`
--
ALTER TABLE `hr_payroll`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_payroll_code` (`payroll_code`);

--
-- Indexes for table `hr_positions`
--
ALTER TABLE `hr_positions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `company_id` (`company_id`);

--
-- Indexes for table `hr_recruitment`
--
ALTER TABLE `hr_recruitment`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_app_code` (`applicant_code`),
  ADD KEY `idx_rec_dept` (`department_id`),
  ADD KEY `idx_rec_status` (`status`);

--
-- Indexes for table `hr_salary_components`
--
ALTER TABLE `hr_salary_components`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sal_comp_emp` (`employee_id`);

--
-- Indexes for table `hr_shifts`
--
ALTER TABLE `hr_shifts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_shift_code` (`code`);

--
-- Indexes for table `inv_categories`
--
ALTER TABLE `inv_categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parent_id` (`parent_id`),
  ADD KEY `company_id` (`company_id`);

--
-- Indexes for table `inv_delivery_notes`
--
ALTER TABLE `inv_delivery_notes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inv_delivery_note_lines`
--
ALTER TABLE `inv_delivery_note_lines`
  ADD PRIMARY KEY (`id`),
  ADD KEY `note_id` (`note_id`);

--
-- Indexes for table `inv_products`
--
ALTER TABLE `inv_products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sku` (`sku`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `company_id` (`company_id`);

--
-- Indexes for table `inv_stock`
--
ALTER TABLE `inv_stock`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_stock` (`product_id`,`warehouse_id`),
  ADD KEY `warehouse_id` (`warehouse_id`);

--
-- Indexes for table `inv_stock_movements`
--
ALTER TABLE `inv_stock_movements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `warehouse_id` (`warehouse_id`);

--
-- Indexes for table `inv_stock_transfers`
--
ALTER TABLE `inv_stock_transfers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `transfer_number` (`transfer_number`),
  ADD KEY `from_warehouse_id` (`from_warehouse_id`),
  ADD KEY `to_warehouse_id` (`to_warehouse_id`);

--
-- Indexes for table `inv_stock_transfer_items`
--
ALTER TABLE `inv_stock_transfer_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `transfer_id` (`transfer_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `inv_warehouses`
--
ALTER TABLE `inv_warehouses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `company_id` (`company_id`);

--
-- Indexes for table `journal_entries`
--
ALTER TABLE `journal_entries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `company_id` (`company_id`);

--
-- Indexes for table `journal_entry_items`
--
ALTER TABLE `journal_entry_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `journal_entry_id` (`journal_entry_id`),
  ADD KEY `account_id` (`account_id`);

--
-- Indexes for table `journal_entry_lines`
--
ALTER TABLE `journal_entry_lines`
  ADD PRIMARY KEY (`id`),
  ADD KEY `journal_entry_id` (`journal_entry_id`),
  ADD KEY `account_id` (`account_id`),
  ADD KEY `cost_center_id` (`cost_center_id`);

--
-- Indexes for table `landed_costs`
--
ALTER TABLE `landed_costs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `reference_number_unique` (`reference_number`,`company_id`),
  ADD KEY `fk_lc_po` (`po_id`),
  ADD KEY `fk_lc_supplier` (`supplier_id`);

--
-- Indexes for table `landed_cost_items`
--
ALTER TABLE `landed_cost_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_lc_items_lc` (`landed_cost_id`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `item_code_unique` (`item_code`,`company_id`);

--
-- Indexes for table `product_categories`
--
ALTER TABLE `product_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_project_code` (`code`),
  ADD KEY `idx_project_customer` (`customer_id`),
  ADD KEY `idx_project_status` (`status`);

--
-- Indexes for table `project_contracts`
--
ALTER TABLE `project_contracts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_project_contract_number` (`contract_number`),
  ADD KEY `idx_proj_contract_project` (`project_id`),
  ADD KEY `idx_proj_contract_customer` (`customer_id`),
  ADD KEY `idx_proj_contract_status` (`status`);

--
-- Indexes for table `project_costs`
--
ALTER TABLE `project_costs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_project_cost_voucher` (`voucher_number`),
  ADD KEY `idx_proj_cost_project` (`project_id`),
  ADD KEY `idx_proj_cost_category` (`cost_category`),
  ADD KEY `idx_proj_cost_supplier` (`supplier_id`);

--
-- Indexes for table `project_invoices`
--
ALTER TABLE `project_invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_project_invoice_number` (`invoice_number`),
  ADD KEY `idx_proj_inv_project` (`project_id`),
  ADD KEY `idx_proj_inv_status` (`status`);

--
-- Indexes for table `project_milestones`
--
ALTER TABLE `project_milestones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_milestone_code` (`milestone_code`),
  ADD KEY `idx_milestone_project` (`project_id`),
  ADD KEY `idx_milestone_status` (`status`);

--
-- Indexes for table `purchase_contracts`
--
ALTER TABLE `purchase_contracts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `pur_contract_num_unique` (`contract_number`,`company_id`),
  ADD KEY `fk_pur_contract_supplier` (`supplier_id`);

--
-- Indexes for table `purchase_invoices`
--
ALTER TABLE `purchase_invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_number_unique` (`invoice_number`,`company_id`),
  ADD KEY `fk_inv_supplier` (`supplier_id`),
  ADD KEY `fk_inv_po` (`po_id`);

--
-- Indexes for table `purchase_invoice_items`
--
ALTER TABLE `purchase_invoice_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_inv_items_inv` (`invoice_id`);

--
-- Indexes for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `po_number_unique` (`order_number`,`company_id`),
  ADD KEY `fk_po_supplier` (`supplier_id`);

--
-- Indexes for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_po_items_po` (`po_id`);

--
-- Indexes for table `purchase_requests`
--
ALTER TABLE `purchase_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `pr_number_unique` (`pr_number`,`company_id`);

--
-- Indexes for table `purchase_request_items`
--
ALTER TABLE `purchase_request_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_pr_items_pr` (`pr_id`);

--
-- Indexes for table `purchase_returns`
--
ALTER TABLE `purchase_returns`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `return_number_unique` (`return_number`,`company_id`),
  ADD KEY `fk_prt_supplier` (`supplier_id`),
  ADD KEY `fk_prt_invoice` (`invoice_id`);

--
-- Indexes for table `purchase_return_items`
--
ALTER TABLE `purchase_return_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_prt_items_ret` (`return_id`);

--
-- Indexes for table `pur_orders`
--
ALTER TABLE `pur_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `po_number` (`po_number`),
  ADD KEY `company_id` (`company_id`),
  ADD KEY `supplier_id` (`supplier_id`);

--
-- Indexes for table `pur_order_lines`
--
ALTER TABLE `pur_order_lines`
  ADD PRIMARY KEY (`id`),
  ADD KEY `purchase_order_id` (`purchase_order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `rfqs`
--
ALTER TABLE `rfqs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `rfq_number_unique` (`rfq_number`,`company_id`);

--
-- Indexes for table `rfq_items`
--
ALTER TABLE `rfq_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_rfq_items_rfq` (`rfq_id`);

--
-- Indexes for table `rfq_suppliers`
--
ALTER TABLE `rfq_suppliers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_rfq_suppliers_rfq` (`rfq_id`),
  ADD KEY `fk_rfq_suppliers_sup` (`supplier_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `company_id` (`company_id`);

--
-- Indexes for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `role_perm_unique` (`role_id`,`permission_key`);

--
-- Indexes for table `sales_contracts`
--
ALTER TABLE `sales_contracts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_contracts_customer` (`customer_id`);

--
-- Indexes for table `sales_invoices`
--
ALTER TABLE `sales_invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_number` (`invoice_number`),
  ADD KEY `company_id` (`company_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `sales_invoice_lines`
--
ALTER TABLE `sales_invoice_lines`
  ADD PRIMARY KEY (`id`),
  ADD KEY `invoice_id` (`invoice_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `sales_invoice_payments`
--
ALTER TABLE `sales_invoice_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_inv_payments_invoice` (`invoice_id`);

--
-- Indexes for table `sales_orders`
--
ALTER TABLE `sales_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_no` (`order_no`),
  ADD KEY `company_id` (`company_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `sales_order_lines`
--
ALTER TABLE `sales_order_lines`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `sales_price_lists`
--
ALTER TABLE `sales_price_lists`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sales_price_list_items`
--
ALTER TABLE `sales_price_list_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_pl_items_list` (`price_list_id`),
  ADD KEY `fk_pl_items_product` (`product_id`);

--
-- Indexes for table `sales_quotations`
--
ALTER TABLE `sales_quotations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_quotations_customer` (`customer_id`);

--
-- Indexes for table `sales_quotation_lines`
--
ALTER TABLE `sales_quotation_lines`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_quote_lines_quotation` (`quotation_id`);

--
-- Indexes for table `sales_receipts`
--
ALTER TABLE `sales_receipts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_receipts_customer` (`customer_id`),
  ADD KEY `fk_receipts_invoice` (`invoice_id`);

--
-- Indexes for table `sales_representatives`
--
ALTER TABLE `sales_representatives`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sales_returns`
--
ALTER TABLE `sales_returns`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_returns_customer` (`customer_id`),
  ADD KEY `fk_returns_invoice` (`invoice_id`);

--
-- Indexes for table `sales_return_lines`
--
ALTER TABLE `sales_return_lines`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_return_lines_return` (`return_id`);

--
-- Indexes for table `stock_adjustments`
--
ALTER TABLE `stock_adjustments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `adj_number_unique` (`adjustment_number`,`company_id`),
  ADD KEY `fk_sa_wh` (`warehouse_id`);

--
-- Indexes for table `stock_adjustment_items`
--
ALTER TABLE `stock_adjustment_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_sai_adj` (`adjustment_id`),
  ADD KEY `fk_sai_prod` (`product_id`);

--
-- Indexes for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_sm_prod` (`product_id`),
  ADD KEY `fk_sm_wh` (`warehouse_id`);

--
-- Indexes for table `stock_returns`
--
ALTER TABLE `stock_returns`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `return_number_unique` (`return_number`,`company_id`),
  ADD KEY `fk_sr_wh` (`warehouse_id`);

--
-- Indexes for table `stock_return_items`
--
ALTER TABLE `stock_return_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_sri_ret` (`return_id`),
  ADD KEY `fk_sri_prod` (`product_id`);

--
-- Indexes for table `stock_transfers`
--
ALTER TABLE `stock_transfers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `transfer_number_unique` (`transfer_number`,`company_id`),
  ADD KEY `fk_st_from_wh` (`from_warehouse_id`),
  ADD KEY `fk_st_to_wh` (`to_warehouse_id`);

--
-- Indexes for table `stock_transfer_items`
--
ALTER TABLE `stock_transfer_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_st_items_st` (`transfer_id`),
  ADD KEY `fk_st_items_prod` (`product_id`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `company_id` (`company_id`);

--
-- Indexes for table `supplier_evaluations`
--
ALTER TABLE `supplier_evaluations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `eval_num_unique` (`eval_number`,`company_id`),
  ADD KEY `fk_eval_supplier` (`supplier_id`);

--
-- Indexes for table `supplier_price_lists`
--
ALTER TABLE `supplier_price_lists`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `spl_num_unique` (`list_number`,`company_id`),
  ADD KEY `fk_spl_supplier` (`supplier_id`);

--
-- Indexes for table `supplier_price_list_items`
--
ALTER TABLE `supplier_price_list_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_spl_items_list` (`price_list_id`);

--
-- Indexes for table `sys_audit_logs`
--
ALTER TABLE `sys_audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_log_user` (`user_id`),
  ADD KEY `idx_log_module` (`module`);

--
-- Indexes for table `sys_backups`
--
ALTER TABLE `sys_backups`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sys_branches`
--
ALTER TABLE `sys_branches`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_branch_code` (`code`),
  ADD KEY `idx_branch_comp` (`company_id`);

--
-- Indexes for table `sys_companies`
--
ALTER TABLE `sys_companies`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_comp_code` (`code`);

--
-- Indexes for table `sys_notifications`
--
ALTER TABLE `sys_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id_index` (`user_id`),
  ADD KEY `is_read_index` (`is_read`);

--
-- Indexes for table `sys_roles`
--
ALTER TABLE `sys_roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_role_code` (`role_code`);

--
-- Indexes for table `sys_role_permissions`
--
ALTER TABLE `sys_role_permissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_perm_role` (`role_id`);

--
-- Indexes for table `sys_settings`
--
ALTER TABLE `sys_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_setting_key` (`setting_key`);

--
-- Indexes for table `sys_users`
--
ALTER TABLE `sys_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_code` (`user_code`),
  ADD UNIQUE KEY `unique_user_email` (`email`);

--
-- Indexes for table `taxes`
--
ALTER TABLE `taxes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `treasury_cheques`
--
ALTER TABLE `treasury_cheques`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cheque_account` (`treasury_account_id`),
  ADD KEY `idx_cheque_status` (`status`);

--
-- Indexes for table `treasury_payments`
--
ALTER TABLE `treasury_payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_payment_voucher` (`voucher_number`),
  ADD KEY `idx_payment_treasury_account` (`treasury_account_id`),
  ADD KEY `idx_payment_supplier` (`supplier_id`);

--
-- Indexes for table `treasury_petty_cash`
--
ALTER TABLE `treasury_petty_cash`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_petty_cash_code` (`code`),
  ADD KEY `idx_petty_cash_account` (`treasury_account_id`);

--
-- Indexes for table `treasury_receipts`
--
ALTER TABLE `treasury_receipts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_voucher_number` (`voucher_number`),
  ADD KEY `idx_treasury_account` (`treasury_account_id`),
  ADD KEY `idx_customer` (`customer_id`);

--
-- Indexes for table `treasury_transfers`
--
ALTER TABLE `treasury_transfers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_transfer_number` (`transfer_number`),
  ADD KEY `idx_from_account` (`from_account_id`),
  ADD KEY `idx_to_account` (`to_account_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `company_id` (`company_id`),
  ADD KEY `branch_id` (`branch_id`),
  ADD KEY `role_id` (`role_id`);

--
-- Indexes for table `warehouses`
--
ALTER TABLE `warehouses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `warehouse_code_unique` (`code`,`company_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `accounts`
--
ALTER TABLE `accounts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `asset_depreciations`
--
ALTER TABLE `asset_depreciations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `bank_reconciliations`
--
ALTER TABLE `bank_reconciliations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `bank_reconciliation_items`
--
ALTER TABLE `bank_reconciliation_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `branches`
--
ALTER TABLE `branches`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `budgets`
--
ALTER TABLE `budgets`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `budget_items`
--
ALTER TABLE `budget_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `companies`
--
ALTER TABLE `companies`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `cost_centers`
--
ALTER TABLE `cost_centers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `crm_leads`
--
ALTER TABLE `crm_leads`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `delivery_notes`
--
ALTER TABLE `delivery_notes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `delivery_note_items`
--
ALTER TABLE `delivery_note_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `fiscal_periods`
--
ALTER TABLE `fiscal_periods`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `fiscal_sub_periods`
--
ALTER TABLE `fiscal_sub_periods`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `fiscal_years`
--
ALTER TABLE `fiscal_years`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fixed_assets`
--
ALTER TABLE `fixed_assets`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `goods_receipts`
--
ALTER TABLE `goods_receipts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `goods_receipt_items`
--
ALTER TABLE `goods_receipt_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `hr_appraisals`
--
ALTER TABLE `hr_appraisals`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `hr_attendance`
--
ALTER TABLE `hr_attendance`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `hr_departments`
--
ALTER TABLE `hr_departments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `hr_designations`
--
ALTER TABLE `hr_designations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `hr_documents`
--
ALTER TABLE `hr_documents`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `hr_employees`
--
ALTER TABLE `hr_employees`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `hr_employee_contracts`
--
ALTER TABLE `hr_employee_contracts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `hr_leaves`
--
ALTER TABLE `hr_leaves`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `hr_payroll`
--
ALTER TABLE `hr_payroll`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `hr_positions`
--
ALTER TABLE `hr_positions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `hr_recruitment`
--
ALTER TABLE `hr_recruitment`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `hr_salary_components`
--
ALTER TABLE `hr_salary_components`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `hr_shifts`
--
ALTER TABLE `hr_shifts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `inv_categories`
--
ALTER TABLE `inv_categories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `inv_delivery_notes`
--
ALTER TABLE `inv_delivery_notes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `inv_delivery_note_lines`
--
ALTER TABLE `inv_delivery_note_lines`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `inv_products`
--
ALTER TABLE `inv_products`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `inv_stock`
--
ALTER TABLE `inv_stock`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inv_stock_movements`
--
ALTER TABLE `inv_stock_movements`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inv_stock_transfers`
--
ALTER TABLE `inv_stock_transfers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inv_stock_transfer_items`
--
ALTER TABLE `inv_stock_transfer_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inv_warehouses`
--
ALTER TABLE `inv_warehouses`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `journal_entries`
--
ALTER TABLE `journal_entries`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `journal_entry_items`
--
ALTER TABLE `journal_entry_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `journal_entry_lines`
--
ALTER TABLE `journal_entry_lines`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `landed_costs`
--
ALTER TABLE `landed_costs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `landed_cost_items`
--
ALTER TABLE `landed_cost_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `product_categories`
--
ALTER TABLE `product_categories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `project_contracts`
--
ALTER TABLE `project_contracts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `project_costs`
--
ALTER TABLE `project_costs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `project_invoices`
--
ALTER TABLE `project_invoices`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `project_milestones`
--
ALTER TABLE `project_milestones`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `purchase_contracts`
--
ALTER TABLE `purchase_contracts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `purchase_invoices`
--
ALTER TABLE `purchase_invoices`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `purchase_invoice_items`
--
ALTER TABLE `purchase_invoice_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `purchase_requests`
--
ALTER TABLE `purchase_requests`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `purchase_request_items`
--
ALTER TABLE `purchase_request_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `purchase_returns`
--
ALTER TABLE `purchase_returns`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `purchase_return_items`
--
ALTER TABLE `purchase_return_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `pur_orders`
--
ALTER TABLE `pur_orders`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pur_order_lines`
--
ALTER TABLE `pur_order_lines`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rfqs`
--
ALTER TABLE `rfqs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `rfq_items`
--
ALTER TABLE `rfq_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `rfq_suppliers`
--
ALTER TABLE `rfq_suppliers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `role_permissions`
--
ALTER TABLE `role_permissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=219;

--
-- AUTO_INCREMENT for table `sales_contracts`
--
ALTER TABLE `sales_contracts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `sales_invoices`
--
ALTER TABLE `sales_invoices`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `sales_invoice_lines`
--
ALTER TABLE `sales_invoice_lines`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `sales_invoice_payments`
--
ALTER TABLE `sales_invoice_payments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `sales_orders`
--
ALTER TABLE `sales_orders`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `sales_order_lines`
--
ALTER TABLE `sales_order_lines`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `sales_price_lists`
--
ALTER TABLE `sales_price_lists`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `sales_price_list_items`
--
ALTER TABLE `sales_price_list_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `sales_quotations`
--
ALTER TABLE `sales_quotations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `sales_quotation_lines`
--
ALTER TABLE `sales_quotation_lines`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `sales_receipts`
--
ALTER TABLE `sales_receipts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `sales_representatives`
--
ALTER TABLE `sales_representatives`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `sales_returns`
--
ALTER TABLE `sales_returns`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `sales_return_lines`
--
ALTER TABLE `sales_return_lines`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `stock_adjustments`
--
ALTER TABLE `stock_adjustments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `stock_adjustment_items`
--
ALTER TABLE `stock_adjustment_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `stock_movements`
--
ALTER TABLE `stock_movements`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `stock_returns`
--
ALTER TABLE `stock_returns`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `stock_return_items`
--
ALTER TABLE `stock_return_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `stock_transfers`
--
ALTER TABLE `stock_transfers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `stock_transfer_items`
--
ALTER TABLE `stock_transfer_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `supplier_evaluations`
--
ALTER TABLE `supplier_evaluations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `supplier_price_lists`
--
ALTER TABLE `supplier_price_lists`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `supplier_price_list_items`
--
ALTER TABLE `supplier_price_list_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `sys_audit_logs`
--
ALTER TABLE `sys_audit_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sys_backups`
--
ALTER TABLE `sys_backups`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sys_branches`
--
ALTER TABLE `sys_branches`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `sys_companies`
--
ALTER TABLE `sys_companies`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `sys_notifications`
--
ALTER TABLE `sys_notifications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sys_roles`
--
ALTER TABLE `sys_roles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sys_role_permissions`
--
ALTER TABLE `sys_role_permissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sys_settings`
--
ALTER TABLE `sys_settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sys_users`
--
ALTER TABLE `sys_users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `taxes`
--
ALTER TABLE `taxes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `treasury_cheques`
--
ALTER TABLE `treasury_cheques`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `treasury_payments`
--
ALTER TABLE `treasury_payments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `treasury_petty_cash`
--
ALTER TABLE `treasury_petty_cash`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `treasury_receipts`
--
ALTER TABLE `treasury_receipts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `treasury_transfers`
--
ALTER TABLE `treasury_transfers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `warehouses`
--
ALTER TABLE `warehouses`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `accounts`
--
ALTER TABLE `accounts`
  ADD CONSTRAINT `accounts_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `accounts_ibfk_2` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `asset_depreciations`
--
ALTER TABLE `asset_depreciations`
  ADD CONSTRAINT `asset_depreciations_ibfk_1` FOREIGN KEY (`asset_id`) REFERENCES `fixed_assets` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `bank_reconciliations`
--
ALTER TABLE `bank_reconciliations`
  ADD CONSTRAINT `bank_reconciliations_ibfk_1` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`);

--
-- Constraints for table `bank_reconciliation_items`
--
ALTER TABLE `bank_reconciliation_items`
  ADD CONSTRAINT `bank_reconciliation_items_ibfk_1` FOREIGN KEY (`reconciliation_id`) REFERENCES `bank_reconciliations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bank_reconciliation_items_ibfk_2` FOREIGN KEY (`journal_entry_item_id`) REFERENCES `journal_entry_items` (`id`);

--
-- Constraints for table `branches`
--
ALTER TABLE `branches`
  ADD CONSTRAINT `branches_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `budget_items`
--
ALTER TABLE `budget_items`
  ADD CONSTRAINT `budget_items_ibfk_1` FOREIGN KEY (`budget_id`) REFERENCES `budgets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `budget_items_ibfk_2` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`);

--
-- Constraints for table `cost_centers`
--
ALTER TABLE `cost_centers`
  ADD CONSTRAINT `cost_centers_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `crm_leads`
--
ALTER TABLE `crm_leads`
  ADD CONSTRAINT `crm_leads_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `crm_leads_ibfk_2` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `customers`
--
ALTER TABLE `customers`
  ADD CONSTRAINT `customers_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `delivery_notes`
--
ALTER TABLE `delivery_notes`
  ADD CONSTRAINT `fk_dn_wh` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `delivery_note_items`
--
ALTER TABLE `delivery_note_items`
  ADD CONSTRAINT `fk_dni_dn` FOREIGN KEY (`delivery_note_id`) REFERENCES `delivery_notes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_dni_prod` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `fiscal_sub_periods`
--
ALTER TABLE `fiscal_sub_periods`
  ADD CONSTRAINT `fiscal_sub_periods_ibfk_1` FOREIGN KEY (`fiscal_period_id`) REFERENCES `fiscal_periods` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `goods_receipts`
--
ALTER TABLE `goods_receipts`
  ADD CONSTRAINT `fk_grn_po` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_grn_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `goods_receipt_items`
--
ALTER TABLE `goods_receipt_items`
  ADD CONSTRAINT `fk_grn_items_grn` FOREIGN KEY (`receipt_id`) REFERENCES `goods_receipts` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `hr_positions`
--
ALTER TABLE `hr_positions`
  ADD CONSTRAINT `hr_positions_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `inv_categories`
--
ALTER TABLE `inv_categories`
  ADD CONSTRAINT `inv_categories_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `inv_categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `inv_categories_ibfk_2` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `inv_delivery_note_lines`
--
ALTER TABLE `inv_delivery_note_lines`
  ADD CONSTRAINT `fk_dn_lines_note` FOREIGN KEY (`note_id`) REFERENCES `inv_delivery_notes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `inv_products`
--
ALTER TABLE `inv_products`
  ADD CONSTRAINT `inv_products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `inv_categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `inv_products_ibfk_2` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `inv_stock`
--
ALTER TABLE `inv_stock`
  ADD CONSTRAINT `inv_stock_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `inv_products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `inv_stock_ibfk_2` FOREIGN KEY (`warehouse_id`) REFERENCES `inv_warehouses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `inv_stock_movements`
--
ALTER TABLE `inv_stock_movements`
  ADD CONSTRAINT `inv_stock_movements_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `inv_products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `inv_stock_movements_ibfk_2` FOREIGN KEY (`warehouse_id`) REFERENCES `inv_warehouses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `inv_stock_transfers`
--
ALTER TABLE `inv_stock_transfers`
  ADD CONSTRAINT `inv_stock_transfers_ibfk_1` FOREIGN KEY (`from_warehouse_id`) REFERENCES `inv_warehouses` (`id`),
  ADD CONSTRAINT `inv_stock_transfers_ibfk_2` FOREIGN KEY (`to_warehouse_id`) REFERENCES `inv_warehouses` (`id`);

--
-- Constraints for table `inv_stock_transfer_items`
--
ALTER TABLE `inv_stock_transfer_items`
  ADD CONSTRAINT `inv_stock_transfer_items_ibfk_1` FOREIGN KEY (`transfer_id`) REFERENCES `inv_stock_transfers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `inv_stock_transfer_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `inv_products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `inv_warehouses`
--
ALTER TABLE `inv_warehouses`
  ADD CONSTRAINT `inv_warehouses_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `journal_entries`
--
ALTER TABLE `journal_entries`
  ADD CONSTRAINT `journal_entries_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `journal_entry_items`
--
ALTER TABLE `journal_entry_items`
  ADD CONSTRAINT `journal_entry_items_ibfk_1` FOREIGN KEY (`journal_entry_id`) REFERENCES `journal_entries` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `journal_entry_items_ibfk_2` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`);

--
-- Constraints for table `journal_entry_lines`
--
ALTER TABLE `journal_entry_lines`
  ADD CONSTRAINT `journal_entry_lines_ibfk_1` FOREIGN KEY (`journal_entry_id`) REFERENCES `journal_entries` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `journal_entry_lines_ibfk_2` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`),
  ADD CONSTRAINT `journal_entry_lines_ibfk_3` FOREIGN KEY (`cost_center_id`) REFERENCES `cost_centers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `landed_costs`
--
ALTER TABLE `landed_costs`
  ADD CONSTRAINT `fk_lc_po` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_lc_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `landed_cost_items`
--
ALTER TABLE `landed_cost_items`
  ADD CONSTRAINT `fk_lc_items_lc` FOREIGN KEY (`landed_cost_id`) REFERENCES `landed_costs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `purchase_contracts`
--
ALTER TABLE `purchase_contracts`
  ADD CONSTRAINT `fk_pur_contract_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `purchase_invoices`
--
ALTER TABLE `purchase_invoices`
  ADD CONSTRAINT `fk_inv_po` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_inv_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `purchase_invoice_items`
--
ALTER TABLE `purchase_invoice_items`
  ADD CONSTRAINT `fk_inv_items_inv` FOREIGN KEY (`invoice_id`) REFERENCES `purchase_invoices` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD CONSTRAINT `fk_po_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  ADD CONSTRAINT `fk_po_items_po` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `purchase_request_items`
--
ALTER TABLE `purchase_request_items`
  ADD CONSTRAINT `fk_pr_items_pr` FOREIGN KEY (`pr_id`) REFERENCES `purchase_requests` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `purchase_returns`
--
ALTER TABLE `purchase_returns`
  ADD CONSTRAINT `fk_prt_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `purchase_invoices` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_prt_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `purchase_return_items`
--
ALTER TABLE `purchase_return_items`
  ADD CONSTRAINT `fk_prt_items_ret` FOREIGN KEY (`return_id`) REFERENCES `purchase_returns` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pur_orders`
--
ALTER TABLE `pur_orders`
  ADD CONSTRAINT `pur_orders_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pur_orders_ibfk_2` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`);

--
-- Constraints for table `pur_order_lines`
--
ALTER TABLE `pur_order_lines`
  ADD CONSTRAINT `pur_order_lines_ibfk_1` FOREIGN KEY (`purchase_order_id`) REFERENCES `pur_orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pur_order_lines_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `inv_products` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `rfq_items`
--
ALTER TABLE `rfq_items`
  ADD CONSTRAINT `fk_rfq_items_rfq` FOREIGN KEY (`rfq_id`) REFERENCES `rfqs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `rfq_suppliers`
--
ALTER TABLE `rfq_suppliers`
  ADD CONSTRAINT `fk_rfq_suppliers_rfq` FOREIGN KEY (`rfq_id`) REFERENCES `rfqs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_rfq_suppliers_sup` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `roles`
--
ALTER TABLE `roles`
  ADD CONSTRAINT `roles_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `fk_role_permissions_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sales_contracts`
--
ALTER TABLE `sales_contracts`
  ADD CONSTRAINT `fk_contracts_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sales_invoices`
--
ALTER TABLE `sales_invoices`
  ADD CONSTRAINT `sales_invoices_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sales_invoices_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`);

--
-- Constraints for table `sales_invoice_lines`
--
ALTER TABLE `sales_invoice_lines`
  ADD CONSTRAINT `sales_invoice_lines_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `sales_invoices` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sales_invoice_lines_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `inv_products` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `sales_invoice_payments`
--
ALTER TABLE `sales_invoice_payments`
  ADD CONSTRAINT `fk_inv_payments_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `sales_invoices` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sales_orders`
--
ALTER TABLE `sales_orders`
  ADD CONSTRAINT `sales_orders_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sales_orders_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`);

--
-- Constraints for table `sales_order_lines`
--
ALTER TABLE `sales_order_lines`
  ADD CONSTRAINT `sales_order_lines_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `sales_orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sales_order_lines_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `inv_products` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `sales_price_list_items`
--
ALTER TABLE `sales_price_list_items`
  ADD CONSTRAINT `fk_pl_items_list` FOREIGN KEY (`price_list_id`) REFERENCES `sales_price_lists` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pl_items_product` FOREIGN KEY (`product_id`) REFERENCES `inv_products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sales_quotations`
--
ALTER TABLE `sales_quotations`
  ADD CONSTRAINT `fk_quotations_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sales_quotation_lines`
--
ALTER TABLE `sales_quotation_lines`
  ADD CONSTRAINT `fk_quote_lines_quotation` FOREIGN KEY (`quotation_id`) REFERENCES `sales_quotations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sales_receipts`
--
ALTER TABLE `sales_receipts`
  ADD CONSTRAINT `fk_receipts_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_receipts_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `sales_invoices` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `sales_returns`
--
ALTER TABLE `sales_returns`
  ADD CONSTRAINT `fk_returns_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_returns_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `sales_invoices` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `sales_return_lines`
--
ALTER TABLE `sales_return_lines`
  ADD CONSTRAINT `fk_return_lines_return` FOREIGN KEY (`return_id`) REFERENCES `sales_returns` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stock_adjustments`
--
ALTER TABLE `stock_adjustments`
  ADD CONSTRAINT `fk_sa_wh` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stock_adjustment_items`
--
ALTER TABLE `stock_adjustment_items`
  ADD CONSTRAINT `fk_sai_adj` FOREIGN KEY (`adjustment_id`) REFERENCES `stock_adjustments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_sai_prod` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD CONSTRAINT `fk_sm_prod` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_sm_wh` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stock_returns`
--
ALTER TABLE `stock_returns`
  ADD CONSTRAINT `fk_sr_wh` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stock_return_items`
--
ALTER TABLE `stock_return_items`
  ADD CONSTRAINT `fk_sri_prod` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_sri_ret` FOREIGN KEY (`return_id`) REFERENCES `stock_returns` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stock_transfers`
--
ALTER TABLE `stock_transfers`
  ADD CONSTRAINT `fk_st_from_wh` FOREIGN KEY (`from_warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_st_to_wh` FOREIGN KEY (`to_warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stock_transfer_items`
--
ALTER TABLE `stock_transfer_items`
  ADD CONSTRAINT `fk_st_items_prod` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_st_items_st` FOREIGN KEY (`transfer_id`) REFERENCES `stock_transfers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD CONSTRAINT `suppliers_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `supplier_evaluations`
--
ALTER TABLE `supplier_evaluations`
  ADD CONSTRAINT `fk_eval_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `supplier_price_lists`
--
ALTER TABLE `supplier_price_lists`
  ADD CONSTRAINT `fk_spl_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `supplier_price_list_items`
--
ALTER TABLE `supplier_price_list_items`
  ADD CONSTRAINT `fk_spl_items_list` FOREIGN KEY (`price_list_id`) REFERENCES `supplier_price_lists` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `users_ibfk_2` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `users_ibfk_3` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
