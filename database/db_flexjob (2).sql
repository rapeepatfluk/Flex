-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 25, 2026 at 07:04 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_flexjob`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_action_logs`
--

CREATE TABLE `admin_action_logs` (
  `admin_action_log_id` int(10) UNSIGNED NOT NULL,
  `admin_user_id` int(10) UNSIGNED NOT NULL,
  `target_type` varchar(50) NOT NULL,
  `target_record_id` int(10) UNSIGNED NOT NULL,
  `action_type` varchar(100) NOT NULL,
  `action_note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `applications`
--

CREATE TABLE `applications` (
  `application_id` int(10) UNSIGNED NOT NULL,
  `job_id` int(10) UNSIGNED NOT NULL,
  `worker_user_id` int(10) UNSIGNED NOT NULL,
  `resume_file_path` varchar(255) DEFAULT NULL,
  `cover_note` text DEFAULT NULL,
  `application_status` enum('submitted','eligible','not_selected','withdrawn') NOT NULL DEFAULT 'submitted',
  `withdrawn_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `applications`
--

INSERT INTO `applications` (`application_id`, `job_id`, `worker_user_id`, `resume_file_path`, `cover_note`, `application_status`, `withdrawn_at`, `created_at`) VALUES
(1, 31, 5, NULL, 'hello', 'submitted', NULL, '2026-08-20 11:33:49'),
(2, 31, 7, NULL, 'ffff', 'eligible', NULL, '2026-08-20 12:51:53'),
(3, 32, 7, 'uploads/resumes/ac47684d40eadb4a0d0b.pdf', 'สวัสดี', 'submitted', NULL, '2026-08-23 08:52:40'),
(4, 33, 7, 'uploads/resumes/ac47684d40eadb4a0d0b.pdf', '', 'withdrawn', '2026-08-25 14:04:44', '2026-08-25 07:04:35');

-- --------------------------------------------------------

--
-- Table structure for table `email_log`
--

CREATE TABLE `email_log` (
  `id` int(10) UNSIGNED NOT NULL,
  `to_email` varchar(190) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `status` enum('sent','failed') NOT NULL DEFAULT 'sent',
  `error_msg` text DEFAULT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `email_log`
--

INSERT INTO `email_log` (`id`, `to_email`, `subject`, `status`, `error_msg`, `sent_at`) VALUES
(1, 'rapeepat.wo02@gmail.com', 'ยืนยันอีเมลของคุณ — FLEXJOB', 'failed', 'SMTP Error: Could not authenticate.', '2026-08-20 12:19:04'),
(2, 'rapeepat.wo02@gmail.com', 'ลิงก์ยืนยันอีเมล (ใหม่) — FLEXJOB', 'failed', 'SMTP Error: Could not authenticate.', '2026-08-20 12:26:08'),
(3, 'wongsuwan.fluk@gmail.com', 'ยืนยันอีเมลของคุณ — FLEXJOB', 'sent', NULL, '2026-08-20 12:42:40'),
(4, 'rapeepat.wo02@gmail.com', 'ลิงก์ยืนยันอีเมล (ใหม่) — FLEXJOB', 'sent', NULL, '2026-08-20 12:44:21'),
(5, 'rapeepat.wo02@gmail.com', '???ͺ???????ʼ?ҹ???? ? FLEXJOB', 'sent', NULL, '2026-08-20 12:46:44'),
(6, 'rapeepat.wo02@gmail.com', 'ตั้งรหัสผ่านใหม่สำหรับบัญชี FLEXJOB', 'sent', NULL, '2026-08-20 12:51:04'),
(7, 'abc@gmail.com', 'มีผู้สมัครงานใหม่: ต้องการนักเขียนโปรแกรม', 'sent', NULL, '2026-08-20 12:51:57'),
(8, 'rapeepat.wo02@gmail.com', 'อัปเดตสถานะใบสมัคร: ต้องการนักเขียนโปรแกรม — abc company', 'sent', NULL, '2026-08-20 12:52:15'),
(9, 'rapeepat.wo02@gmail.com', '???ͺ???????ʼ?ҹ???? ? FLEXJOB', 'sent', NULL, '2026-08-20 13:32:36'),
(12, 'frk24072561@gmail.com', 'ลิงก์ยืนยันอีเมล (ใหม่) — FLEXJOB', 'sent', NULL, '2026-08-22 12:36:01'),
(13, 'frk24072561@gmail.com', 'ลิงก์ยืนยันอีเมล (ใหม่) — FLEXJOB', 'sent', NULL, '2026-08-22 12:36:33'),
(14, 'frk24072561@gmail.com', 'ลิงก์ยืนยันอีเมล (ใหม่) — FLEXJOB', 'sent', NULL, '2026-08-22 12:36:38'),
(15, 'frk24072561@gmail.com', 'ลิงก์ยืนยันอีเมล (ใหม่) — FLEXJOB', 'sent', NULL, '2026-08-22 12:41:41'),
(16, 'frk24072561@gmail.com', 'ลิงก์ยืนยันอีเมล (ใหม่) — FLEXJOB', 'sent', NULL, '2026-08-22 12:41:44'),
(17, 'frk24072561@gmail.com', 'ลิงก์ยืนยันอีเมล (ใหม่) — FLEXJOB', 'sent', NULL, '2026-08-22 12:44:12'),
(18, 'frk24072561@gmail.com', 'มีผู้สมัครงานใหม่: นักเขียนโปรแกรม', 'sent', NULL, '2026-08-23 08:52:44'),
(19, 'frk24072561@gmail.com', 'มีผู้สมัครงานใหม่: ออกแบบ UX / UI', 'sent', NULL, '2026-08-25 07:04:39');

-- --------------------------------------------------------

--
-- Table structure for table `email_verifications`
--

CREATE TABLE `email_verifications` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `token` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `email_verifications`
--

INSERT INTO `email_verifications` (`id`, `user_id`, `token`, `expires_at`, `used_at`, `created_at`) VALUES
(1, 7, '5e69bca75fdcd246f67812c00750876d91caea3327777f05db0d23857e91bebc', '2026-08-20 19:26:05', '2026-08-20 12:26:05', '2026-08-20 12:19:02'),
(2, 7, '9a275ee98773dc07a252827707088ea8360296517c2a2259c450f827fa13dcc2', '2026-08-20 19:44:17', '2026-08-20 12:44:17', '2026-08-20 12:26:05'),
(4, 7, 'e66373a024814d6dd809a485eafb40939058ed4bfd7d1cee4df8e07de5e5f1a7', '2026-08-20 19:44:27', '2026-08-20 12:44:27', '2026-08-20 12:44:17'),
(5, 12, 'b9265c2953dff9219b8ae428b7e557302214b769f459282e416ad48b3d00e12f', '2026-08-23 19:22:48', '2026-08-22 12:23:10', '2026-08-22 12:22:48'),
(6, 12, 'f225dc3b3e6140f31e31f083a584ad88dfdd2d705bb110db2919965c89a253dc', '2026-08-23 19:23:10', '2026-08-22 12:36:01', '2026-08-22 12:23:10'),
(7, 12, '241120187d6a5de89872d4dc523bd7fa9e3d2a31a73d1e515bb8da5bbf9d1f38', '2026-08-23 19:35:56', '2026-08-22 12:36:33', '2026-08-22 12:35:56'),
(8, 12, 'a2ae62d6204853e4ad6b9f1ac217853f43e567f4910f6fd0a65e851af4eb3620', '2026-08-23 19:36:28', '2026-08-22 12:36:38', '2026-08-22 12:36:28'),
(9, 12, 'b02669c7fdd1a75749dee4dce7789cd31acf053419fddbc444bcd9d03de57a3f', '2026-08-23 19:36:34', '2026-08-22 12:41:41', '2026-08-22 12:36:34'),
(10, 12, '9b088c426c9d4a434186f800157cfccdeb2babc03c295d3a77ede89d72069042', '2026-08-23 19:41:37', '2026-08-22 12:41:44', '2026-08-22 12:41:37'),
(11, 12, '06a75adffbf99bd879d97cafe08a52bd23ef5fcb9ad4d23d749b76a4a3aa5451', '2026-08-23 19:41:41', '2026-08-22 12:44:12', '2026-08-22 12:41:41'),
(12, 12, 'e0cccab308703f7332d5cb6a743102c5fbf3894a714fb64ab8e2aeb0a52c5758', '2026-08-23 19:44:08', '2026-08-22 12:44:23', '2026-08-22 12:44:08');

-- --------------------------------------------------------

--
-- Table structure for table `employer_documents`
--

CREATE TABLE `employer_documents` (
  `employer_document_id` int(10) UNSIGNED NOT NULL,
  `employer_user_id` int(10) UNSIGNED NOT NULL,
  `document_file_path` varchar(255) NOT NULL,
  `document_status` enum('pending','approved','rejected','resubmit') NOT NULL DEFAULT 'pending',
  `review_note` text DEFAULT NULL,
  `reviewed_by_user_id` int(10) UNSIGNED DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employer_documents`
--

INSERT INTO `employer_documents` (`employer_document_id`, `employer_user_id`, `document_file_path`, `document_status`, `review_note`, `reviewed_by_user_id`, `reviewed_at`, `submitted_at`) VALUES
(1, 6, 'uploads/verification/a4bee870bcd5407fff01.png', 'approved', NULL, 1, '2026-08-20 18:29:06', '2026-08-20 11:26:01'),
(2, 6, 'uploads/verification/0243568763edd4a29520.pdf', 'approved', NULL, 1, '2026-08-20 18:29:06', '2026-08-20 11:26:10'),
(3, 12, 'uploads/verification/272a4c56f1500f402fea.jpg', 'approved', NULL, 1, '2026-08-22 19:47:08', '2026-08-22 12:45:37');

-- --------------------------------------------------------

--
-- Table structure for table `employer_profiles`
--

CREATE TABLE `employer_profiles` (
  `employer_profile_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `company_name` varchar(180) NOT NULL,
  `company_description` text DEFAULT NULL,
  `company_address` text DEFAULT NULL,
  `company_logo_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employer_profiles`
--

INSERT INTO `employer_profiles` (`employer_profile_id`, `user_id`, `company_name`, `company_description`, `company_address`, `company_logo_path`) VALUES
(1, 2, 'Spark Event Studio', 'ทีมสร้างสรรค์งานอีเวนต์และแบรนด์แอคติเวชัน', 'กรุงเทพมหานคร', 'assets/images/spark-event-logo.svg'),
(2, 3, 'KIND Coffee', 'ร้านกาแฟสเปเชียลตี้สำหรับคนรักกาแฟ', 'กรุงเทพมหานคร', 'assets/images/kind-coffee-logo.svg'),
(3, 4, 'Morrow Creative', 'สตูดิโอครีเอทีฟและคอนเทนต์ดิจิทัล', 'กรุงเทพมหานคร', 'assets/images/morrow-creative-logo.svg'),
(4, 6, 'abc company', NULL, NULL, NULL),
(5, 12, 'fluk Software company', 'บริษัท Software เขียนเว็บไซต์', '111 ถนน AA ตำบล AB อำเภอ AC จังหวัด บุรีรัมย์ 31000', 'uploads/company-logos/2620be0d2178a715e28d.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `job_id` int(10) UNSIGNED NOT NULL,
  `employer_user_id` int(10) UNSIGNED NOT NULL,
  `job_category_id` int(10) UNSIGNED NOT NULL,
  `job_title` varchar(180) NOT NULL,
  `job_description` text NOT NULL,
  `work_location` varchar(180) NOT NULL,
  `work_province` varchar(100) DEFAULT NULL,
  `work_schedule` varchar(180) DEFAULT NULL,
  `work_mode` enum('onsite','remote','hybrid') NOT NULL DEFAULT 'onsite',
  `application_deadline` date DEFAULT NULL,
  `pay_amount` decimal(10,2) NOT NULL,
  `pay_unit` enum('hour','day','project') NOT NULL DEFAULT 'day',
  `open_positions` smallint(5) UNSIGNED NOT NULL DEFAULT 1,
  `job_status` enum('published','hidden','closed') NOT NULL DEFAULT 'published',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `jobs`
--

INSERT INTO `jobs` (`job_id`, `employer_user_id`, `job_category_id`, `job_title`, `job_description`, `work_location`, `work_province`, `work_schedule`, `work_mode`, `application_deadline`, `pay_amount`, `pay_unit`, `open_positions`, `job_status`, `created_at`, `updated_at`) VALUES
(21, 2, 2, 'Event Staff งานเปิดตัวสินค้า', 'ดูแลจุดลงทะเบียน ให้ข้อมูลผู้ร่วมงาน และช่วยประสานงานหน้างาน', 'ศูนย์นิทรรศการและการประชุมไบเทค บางนา, 88 ถนนเทพรัตน แขวงบางนาใต้ เขตบางนา กรุงเทพมหานคร 10260', 'กรุงเทพมหานคร', '23–24 ส.ค. 2026 เวลา 09:00–18:00', 'onsite', NULL, 900.00, 'day', 8, 'published', '2026-08-18 13:15:41', '2026-08-22 10:40:57'),
(22, 3, 1, 'พนักงานพาร์ทไทม์ ร้านกาแฟ', 'รับออเดอร์ เตรียมเครื่องดื่ม และดูแลความเรียบร้อยภายในร้าน', 'KIND Coffee, 51/2 ซอยอารีย์ 1 แขวงพญาไท เขตพญาไท กรุงเทพมหานคร 10400', 'กรุงเทพมหานคร', 'เลือกกะทำงานได้', 'onsite', NULL, 70.00, 'hour', 2, 'published', '2026-08-18 13:15:41', '2026-08-22 10:40:57'),
(23, 4, 3, 'Graphic Designer (Freelance)', 'ออกแบบสื่อ Social Media สำหรับแคมเปญ จำนวน 10 ชิ้นต่อโปรเจกต์', 'ทำงานออนไลน์ (Work from Home)', 'ออนไลน์', 'ปิดรับ 30 ส.ค. 2026', 'onsite', NULL, 3500.00, 'project', 1, 'published', '2026-08-18 13:15:41', '2026-08-22 10:40:57'),
(24, 2, 2, 'ทีมลงทะเบียนงานวิ่งการกุศล', 'ต้อนรับผู้ร่วมงาน แจกเบอร์วิ่ง และช่วยดูแลจุดลงทะเบียน', 'สวนลุมพินี ถนนพระราม 4 แขวงลุมพินี เขตปทุมวัน กรุงเทพมหานคร 10330', 'กรุงเทพมหานคร', '31 ส.ค. 2026 เวลา 04:30–10:00', 'onsite', NULL, 850.00, 'day', 12, 'published', '2026-08-18 13:15:41', '2026-08-22 10:40:57'),
(25, 3, 1, 'Barista พาร์ทไทม์ วันเสาร์-อาทิตย์', 'ชงกาแฟ รับออเดอร์ และดูแลความเรียบร้อยหน้าร้าน มีการสอนงาน', 'KIND Coffee, 89 ถนนสุขุมวิท 55 แขวงคลองตันเหนือ เขตวัฒนา กรุงเทพมหานคร 10110', 'กรุงเทพมหานคร', 'เสาร์–อาทิตย์ 08:00–17:00', 'onsite', NULL, 75.00, 'hour', 2, 'published', '2026-08-18 13:15:41', '2026-08-22 10:40:57'),
(26, 4, 3, 'ช่างภาพงานอีเวนต์', 'ถ่ายภาพบรรยากาศและกิจกรรมภายในงาน พร้อมคัดเลือกภาพส่งหลังจบงาน', 'ไอคอนสยาม, 299 ซอยเจริญนคร 5 แขวงคลองต้นไทร เขตคลองสาน กรุงเทพมหานคร 10600', 'กรุงเทพมหานคร', '6 ก.ย. 2026', 'onsite', NULL, 4500.00, 'project', 1, 'published', '2026-08-18 13:15:41', '2026-08-22 10:40:57'),
(27, 3, 1, 'แอดมินตอบแชต (Work from Home)', 'ตอบคำถามลูกค้าและประสานงานทีมขาย มีคู่มือข้อความให้', 'ทำงานออนไลน์ (Work from Home)', 'ออนไลน์', 'จ.–ศ. 10:00–18:00', 'onsite', NULL, 700.00, 'day', 3, 'published', '2026-08-18 13:15:41', '2026-08-22 10:40:57'),
(28, 2, 2, 'Staff แจกสินค้าตัวอย่าง', 'แจกสินค้าตัวอย่างและเชิญชวนผู้ร่วมงานเข้าร่วมกิจกรรมแบรนด์', 'เซ็นทรัลเวิลด์, 999/9 ถนนพระราม 1 แขวงปทุมวัน เขตปทุมวัน กรุงเทพมหานคร 10330', 'กรุงเทพมหานคร', '12–14 ก.ย. 2026', 'onsite', NULL, 950.00, 'day', 6, 'published', '2026-08-18 13:15:41', '2026-08-22 10:40:57'),
(29, 4, 3, 'Content Creator สำหรับ TikTok', 'คิดคอนเทนต์ ถ่าย และตัดต่อวิดีโอ TikTok จำนวน 5 คลิป', 'ทำงานออนไลน์ (Work from Home)', 'ออนไลน์', 'ส่งงานภายใน 14 วัน', 'onsite', NULL, 6000.00, 'project', 1, 'published', '2026-08-18 13:15:41', '2026-08-22 10:40:57'),
(30, 3, 1, 'พนักงานเสิร์ฟงานเลี้ยง', 'เสิร์ฟอาหารและเครื่องดื่ม ช่วยจัดโต๊ะ และดูแลความเรียบร้อยในงาน', 'โรงแรมริมปิง, 99 ถนนช้างคลาน ตำบลช้างคลาน อำเภอเมืองเชียงใหม่ เชียงใหม่ 50100', 'เชียงใหม่', '20 ก.ย. 2026 เวลา 16:00–23:00', 'onsite', NULL, 800.00, 'day', 5, 'published', '2026-08-18 13:15:41', '2026-08-22 10:40:57'),
(31, 6, 3, 'ต้องการนักเขียนโปรแกรม', 'โปรแกรมเว็บขายสินค้า', 'abc company', 'บุรีรัมย์', '10.00-20.00', 'onsite', NULL, 400.00, 'day', 1, 'published', '2026-08-20 11:33:21', '2026-08-22 10:40:57'),
(32, 12, 3, 'นักเขียนโปรแกรม', 'นักเขียนโปรแกรม เว็บไซต์บริษัท', '134 ต.ในเมือง อ.เมือง', 'บุรีรัมย์', '10:00 - 18:00', 'onsite', '2026-08-30', 350.00, 'day', 1, 'published', '2026-08-22 12:50:56', '2026-08-22 12:50:56'),
(33, 12, 3, 'ออกแบบ UX / UI', 'ออกแบบ UX / UI สำหรับเว็บไซต์ขายรถยนต์', '134 ต.ในเมือง อ.เมือง', 'บุรีรัมย์', '10:00 - 18:00', 'remote', '2026-08-31', 5000.00, 'project', 1, 'published', '2026-08-25 07:02:23', '2026-08-25 07:02:23'),
(34, 12, 2, 'คนยืนบูธงาน MotoGP', 'ยืนบูธงาน MotoGP รับลูกค้า', '13/4 ต.ในเมือง อ.เมือง', 'บุรีรัมย์', '10:00 - 18:00', 'onsite', '2026-08-31', 500.00, 'day', 3, 'published', '2026-08-25 08:28:47', '2026-08-25 08:28:47');

-- --------------------------------------------------------

--
-- Table structure for table `job_categories`
--

CREATE TABLE `job_categories` (
  `job_category_id` int(10) UNSIGNED NOT NULL,
  `category_slug` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `job_categories`
--

INSERT INTO `job_categories` (`job_category_id`, `category_slug`) VALUES
(2, 'event'),
(3, 'freelance'),
(1, 'part_time');

-- --------------------------------------------------------

--
-- Table structure for table `job_images`
--

CREATE TABLE `job_images` (
  `job_image_id` int(10) UNSIGNED NOT NULL,
  `job_id` int(10) UNSIGNED NOT NULL,
  `image_file_path` varchar(255) NOT NULL,
  `display_order` smallint(5) UNSIGNED NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `job_images`
--

INSERT INTO `job_images` (`job_image_id`, `job_id`, `image_file_path`, `display_order`) VALUES
(31, 21, 'assets/images/job-event-staff-v1.png', 1),
(32, 24, 'assets/images/job-event-staff-v1.png', 1),
(33, 28, 'assets/images/job-event-staff-v1.png', 1),
(34, 22, 'assets/images/job-barista-v1.png', 1),
(35, 25, 'assets/images/job-barista-v1.png', 1),
(36, 27, 'assets/images/job-barista-v1.png', 1),
(37, 30, 'assets/images/job-barista-v1.png', 1),
(38, 23, 'assets/images/job-creative-v1.png', 1),
(39, 26, 'assets/images/job-creative-v1.png', 1),
(40, 29, 'assets/images/job-creative-v1.png', 1),
(41, 31, 'uploads/jobs/9d84048958a5d54f354e.png', 1),
(42, 32, 'uploads/jobs/c849f78fc3757b7f45d4.png', 1),
(43, 34, 'uploads/jobs/4bfd90ef79d3c8eb5960.jpg', 1);

-- --------------------------------------------------------

--
-- Table structure for table `job_invitations`
--

CREATE TABLE `job_invitations` (
  `job_invitation_id` int(10) UNSIGNED NOT NULL,
  `job_id` int(10) UNSIGNED NOT NULL,
  `worker_user_id` int(10) UNSIGNED NOT NULL,
  `invitation_message` text DEFAULT NULL,
  `invitation_status` enum('sent','viewed','accepted','declined') NOT NULL DEFAULT 'sent',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `responded_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `job_invitations`
--

INSERT INTO `job_invitations` (`job_invitation_id`, `job_id`, `worker_user_id`, `invitation_message`, `invitation_status`, `created_at`, `responded_at`) VALUES
(1, 33, 7, 'เชิญมาสมัคร', 'accepted', '2026-08-25 07:03:12', '2026-08-25 07:03:29');

-- --------------------------------------------------------

--
-- Table structure for table `job_skills`
--

CREATE TABLE `job_skills` (
  `job_id` int(10) UNSIGNED NOT NULL,
  `skill_id` int(10) UNSIGNED NOT NULL,
  `importance` enum('required','preferred') NOT NULL DEFAULT 'required'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `job_skills`
--

INSERT INTO `job_skills` (`job_id`, `skill_id`, `importance`) VALUES
(33, 13, 'required'),
(32, 16, 'required'),
(32, 23, 'required'),
(32, 24, 'required'),
(32, 25, 'required'),
(33, 71, 'required'),
(33, 72, 'required'),
(34, 74, 'required'),
(34, 75, 'required');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `notification_title` varchar(180) NOT NULL,
  `notification_message` text NOT NULL,
  `notification_url` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notification_id`, `user_id`, `notification_title`, `notification_message`, `notification_url`, `is_read`, `created_at`) VALUES
(1, 6, 'ผลการตรวจเอกสารผู้ว่าจ้าง', 'เอกสารของคุณผ่านการตรวจสอบ', 'employer/dashboard.php', 0, '2026-08-20 11:29:06'),
(2, 6, 'ผลการตรวจเอกสารผู้ว่าจ้าง', 'เอกสารของคุณผ่านการตรวจสอบ', 'employer/dashboard.php', 0, '2026-08-20 11:29:06'),
(4, 7, 'คำเชิญสมัครงานใหม่', 'ผู้ว่าจ้างเชิญคุณสมัครงาน: ออกแบบ UX / UI', 'worker/invitations.php', 1, '2026-08-25 07:03:12'),
(5, 12, 'ผู้สมัครถอนใบสมัคร', 'Rapeepat Wongsuwan ถอนใบสมัครงาน: ออกแบบ UX / UI', 'employer/applicants.php?job=33', 1, '2026-08-25 07:04:44');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `token` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `user_id`, `token`, `expires_at`, `used_at`, `created_at`) VALUES
(1, 7, '92d4d177b9baca05fc66e6b10920f9296c612635abf238f4db69127c5d1cc664', '2026-08-20 19:51:01', '2026-08-20 12:51:01', '2026-08-20 12:46:40'),
(2, 7, '5225951b35cae6ba7a4128fc71c1a8f9fbc035cfbcbc22c75be3ce3f8dec9568', '2026-08-20 19:51:33', '2026-08-20 12:51:33', '2026-08-20 12:51:01'),
(3, 7, '1526e69850d9aeb20da266fb46bed98ce72d4f7d02eda80dbfc4bdcbcaba5aaa', '2026-08-20 21:32:32', NULL, '2026-08-20 13:32:32');

-- --------------------------------------------------------

--
-- Table structure for table `skills`
--

CREATE TABLE `skills` (
  `skill_id` int(10) UNSIGNED NOT NULL,
  `skill_name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `skills`
--

INSERT INTO `skills` (`skill_id`, `skill_name`, `created_at`) VALUES
(13, 'canva', '2026-08-22 12:21:12'),
(14, 'capcup', '2026-08-22 12:21:12'),
(15, 'microsoft', '2026-08-22 12:21:12'),
(16, 'vscode', '2026-08-22 12:21:12'),
(17, 'สื่อสารได้ดี', '2026-08-22 12:21:12'),
(18, 'ทำงานเป็นทีม', '2026-08-22 12:21:12'),
(23, 'php', '2026-08-22 12:50:56'),
(24, 'html', '2026-08-22 12:50:56'),
(25, 'javascript', '2026-08-22 12:50:56'),
(71, 'Figma', '2026-08-25 07:02:23'),
(72, 'Photoshop', '2026-08-25 07:02:23'),
(74, 'สื่อสารภาษาอังกฤษ', '2026-08-25 08:28:47'),
(75, 'การบริการ', '2026-08-25 08:28:47');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(190) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','employer','worker') NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `account_status` enum('active','suspended','pending') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `email_verified_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `first_name`, `last_name`, `email`, `password_hash`, `role`, `phone`, `account_status`, `created_at`, `email_verified_at`) VALUES
(1, 'Admin', 'FLEXJOB', 'admin@gmail.com', '$2y$10$C7Mda5k3E6wynuOjaodG3uuBCfrGpzsxt1.GXkpKP6nz2y69WIPKm', 'admin', NULL, 'active', '2026-08-18 12:56:05', NULL),
(2, 'Spark', 'Event Studio', 'employer@gmail.com', '$2y$10$4fWgNYHqUB7bhBw.DWLL8.aUAZw4N2bkYDkY.DP8VBwY.ejasyjFa', 'employer', NULL, 'active', '2026-08-18 12:56:05', NULL),
(3, 'KIND', 'Coffee', 'kind@flexjob.test', '$2y$10$NGlLFvgckOSFtrdSRQCVguQeIFa6KU7JHmx7uhyy8B0Sf3FsIB/kW', 'employer', NULL, 'active', '2026-08-18 13:01:04', NULL),
(4, 'Morrow', 'Creative', 'morrow@flexjob.test', '$2y$10$NGlLFvgckOSFtrdSRQCVguQeIFa6KU7JHmx7uhyy8B0Sf3FsIB/kW', 'employer', NULL, 'active', '2026-08-18 13:01:04', NULL),
(5, 'rapeepat', 'wongsuwan', 'rapeepat@gamail.com', '$2y$10$e2Kbr.SdS2Y1tTQHfuEF2uCE6JJobjLuwrbcapiEJq/q5tJafmXuq', 'worker', '0991028810', 'active', '2026-08-20 11:23:34', NULL),
(6, 'rape', 'wong', 'abc@gmail.com', '$2y$10$9dNPHSFBb4OcxeoyNrVnIenZXJU.KXNmYe9BHnzJ2zenkXVGruyT2', 'employer', '0981629810', 'active', '2026-08-20 11:24:59', NULL),
(7, 'Rapeepat', 'Wongsuwan', 'rapeepat.wo02@gmail.com', '$2y$10$p5nij3EhEFElT.GSFDCdNuJYp4BzG/dVdrRxFr4eNBRIa0G1CHEra', 'worker', '0919876782', 'active', '2026-08-20 12:19:02', '2026-08-20 12:44:27'),
(12, 'Rapeepat', 'Wongsuwan', 'frk24072561@gmail.com', '$2y$10$ozg5i9xj/ZfmKOCvh57Gx.qO.3/kXVj6kpyyhxxyh/zjJaQ0yOQk6', 'employer', '0981029910', 'active', '2026-08-22 12:22:48', '2026-08-22 12:44:23');

-- --------------------------------------------------------

--
-- Table structure for table `worker_job_preferences`
--

CREATE TABLE `worker_job_preferences` (
  `worker_user_id` int(10) UNSIGNED NOT NULL,
  `job_category_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `worker_job_preferences`
--

INSERT INTO `worker_job_preferences` (`worker_user_id`, `job_category_id`) VALUES
(7, 1),
(7, 2),
(7, 3);

-- --------------------------------------------------------

--
-- Table structure for table `worker_profiles`
--

CREATE TABLE `worker_profiles` (
  `worker_profile_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `professional_headline` varchar(180) DEFAULT NULL,
  `biography` text DEFAULT NULL,
  `profile_image_path` varchar(255) DEFAULT NULL,
  `skills` text DEFAULT NULL,
  `resume_file_path` varchar(255) DEFAULT NULL,
  `portfolio_file_path` varchar(255) DEFAULT NULL,
  `portfolio_url` varchar(500) DEFAULT NULL,
  `profile_visibility` enum('application_only','searchable') NOT NULL DEFAULT 'application_only',
  `work_province` varchar(100) DEFAULT NULL,
  `preferred_work_mode` enum('any','onsite','remote','hybrid') NOT NULL DEFAULT 'any',
  `available_from` date DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `worker_profiles`
--

INSERT INTO `worker_profiles` (`worker_profile_id`, `user_id`, `professional_headline`, `biography`, `profile_image_path`, `skills`, `resume_file_path`, `portfolio_file_path`, `portfolio_url`, `profile_visibility`, `work_province`, `preferred_work_mode`, `available_from`, `updated_at`) VALUES
(1, 5, '', '', NULL, '', 'uploads/resumes/a1b2bb781a0f5ad5a5d9.pdf', NULL, '', 'application_only', NULL, 'any', NULL, '2026-08-22 10:40:57'),
(3, 7, 'นักเขียนโปรแกรม Backend Frontend', 'สวัสดีครับ ผมมีประสบการณ์ทำงานด้าน IT support , network , web deverloper', 'uploads/profile-images/8038939095cc5e34bc5c.png', 'canva, capcup, microsoft, vscode, ทำงานเป็นทีม, สื่อสารได้ดี', 'uploads/resumes/ac47684d40eadb4a0d0b.pdf', 'uploads/portfolios/ba3a87990e8be774d8fb.pdf', 'https://canva.link/ft59mmx4fa895oj', 'searchable', 'บุรีรัมย์', 'any', '2026-08-22', '2026-08-25 06:58:30');

-- --------------------------------------------------------

--
-- Table structure for table `worker_skills`
--

CREATE TABLE `worker_skills` (
  `worker_user_id` int(10) UNSIGNED NOT NULL,
  `skill_id` int(10) UNSIGNED NOT NULL,
  `proficiency_level` enum('beginner','intermediate','advanced') NOT NULL DEFAULT 'intermediate'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `worker_skills`
--

INSERT INTO `worker_skills` (`worker_user_id`, `skill_id`, `proficiency_level`) VALUES
(7, 13, 'intermediate'),
(7, 14, 'intermediate'),
(7, 15, 'intermediate'),
(7, 16, 'intermediate'),
(7, 17, 'intermediate'),
(7, 18, 'intermediate');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_action_logs`
--
ALTER TABLE `admin_action_logs`
  ADD PRIMARY KEY (`admin_action_log_id`),
  ADD KEY `fk_admin_action_log_admin` (`admin_user_id`),
  ADD KEY `idx_admin_action_log_created` (`created_at`),
  ADD KEY `idx_admin_action_log_target` (`target_type`,`target_record_id`);

--
-- Indexes for table `applications`
--
ALTER TABLE `applications`
  ADD PRIMARY KEY (`application_id`),
  ADD UNIQUE KEY `uq_application_job_worker` (`job_id`,`worker_user_id`),
  ADD KEY `idx_application_worker` (`worker_user_id`,`application_status`);

--
-- Indexes for table `email_log`
--
ALTER TABLE `email_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_to` (`to_email`);

--
-- Indexes for table `email_verifications`
--
ALTER TABLE `email_verifications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_token` (`token`);

--
-- Indexes for table `employer_documents`
--
ALTER TABLE `employer_documents`
  ADD PRIMARY KEY (`employer_document_id`),
  ADD KEY `fk_employer_document_reviewer` (`reviewed_by_user_id`),
  ADD KEY `idx_employer_document_status` (`employer_user_id`,`document_status`);

--
-- Indexes for table `employer_profiles`
--
ALTER TABLE `employer_profiles`
  ADD PRIMARY KEY (`employer_profile_id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`job_id`),
  ADD KEY `fk_job_employer` (`employer_user_id`),
  ADD KEY `fk_job_category` (`job_category_id`),
  ADD KEY `idx_job_listing` (`job_status`,`job_category_id`,`created_at`);

--
-- Indexes for table `job_categories`
--
ALTER TABLE `job_categories`
  ADD PRIMARY KEY (`job_category_id`),
  ADD UNIQUE KEY `category_slug` (`category_slug`);

--
-- Indexes for table `job_images`
--
ALTER TABLE `job_images`
  ADD PRIMARY KEY (`job_image_id`),
  ADD KEY `idx_job_image_order` (`job_id`,`display_order`);

--
-- Indexes for table `job_invitations`
--
ALTER TABLE `job_invitations`
  ADD PRIMARY KEY (`job_invitation_id`),
  ADD UNIQUE KEY `uq_job_invitation_worker` (`job_id`,`worker_user_id`),
  ADD KEY `idx_worker_invitation_inbox` (`worker_user_id`,`invitation_status`,`created_at`);

--
-- Indexes for table `job_skills`
--
ALTER TABLE `job_skills`
  ADD PRIMARY KEY (`job_id`,`skill_id`),
  ADD KEY `idx_job_skill_lookup` (`skill_id`,`importance`,`job_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `idx_notification_inbox` (`user_id`,`is_read`,`created_at`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_token` (`token`);

--
-- Indexes for table `skills`
--
ALTER TABLE `skills`
  ADD PRIMARY KEY (`skill_id`),
  ADD UNIQUE KEY `uq_skill_name` (`skill_name`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `worker_job_preferences`
--
ALTER TABLE `worker_job_preferences`
  ADD PRIMARY KEY (`worker_user_id`,`job_category_id`),
  ADD KEY `idx_worker_preference_category` (`job_category_id`,`worker_user_id`);

--
-- Indexes for table `worker_profiles`
--
ALTER TABLE `worker_profiles`
  ADD PRIMARY KEY (`worker_profile_id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `worker_skills`
--
ALTER TABLE `worker_skills`
  ADD PRIMARY KEY (`worker_user_id`,`skill_id`),
  ADD KEY `idx_worker_skill_lookup` (`skill_id`,`worker_user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_action_logs`
--
ALTER TABLE `admin_action_logs`
  MODIFY `admin_action_log_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `applications`
--
ALTER TABLE `applications`
  MODIFY `application_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `email_log`
--
ALTER TABLE `email_log`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `email_verifications`
--
ALTER TABLE `email_verifications`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `employer_documents`
--
ALTER TABLE `employer_documents`
  MODIFY `employer_document_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `employer_profiles`
--
ALTER TABLE `employer_profiles`
  MODIFY `employer_profile_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `job_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `job_categories`
--
ALTER TABLE `job_categories`
  MODIFY `job_category_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `job_images`
--
ALTER TABLE `job_images`
  MODIFY `job_image_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `job_invitations`
--
ALTER TABLE `job_invitations`
  MODIFY `job_invitation_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `skills`
--
ALTER TABLE `skills`
  MODIFY `skill_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=76;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `worker_profiles`
--
ALTER TABLE `worker_profiles`
  MODIFY `worker_profile_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_action_logs`
--
ALTER TABLE `admin_action_logs`
  ADD CONSTRAINT `fk_admin_action_log_admin` FOREIGN KEY (`admin_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `applications`
--
ALTER TABLE `applications`
  ADD CONSTRAINT `fk_application_job` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`job_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_application_worker` FOREIGN KEY (`worker_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `email_verifications`
--
ALTER TABLE `email_verifications`
  ADD CONSTRAINT `email_verifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `employer_documents`
--
ALTER TABLE `employer_documents`
  ADD CONSTRAINT `fk_employer_document_employer` FOREIGN KEY (`employer_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_employer_document_reviewer` FOREIGN KEY (`reviewed_by_user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `employer_profiles`
--
ALTER TABLE `employer_profiles`
  ADD CONSTRAINT `fk_employer_profile_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `jobs`
--
ALTER TABLE `jobs`
  ADD CONSTRAINT `fk_job_category` FOREIGN KEY (`job_category_id`) REFERENCES `job_categories` (`job_category_id`),
  ADD CONSTRAINT `fk_job_employer` FOREIGN KEY (`employer_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `job_images`
--
ALTER TABLE `job_images`
  ADD CONSTRAINT `fk_job_image_job` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`job_id`) ON DELETE CASCADE;

--
-- Constraints for table `job_invitations`
--
ALTER TABLE `job_invitations`
  ADD CONSTRAINT `fk_job_invitation_job` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`job_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_job_invitation_worker` FOREIGN KEY (`worker_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `job_skills`
--
ALTER TABLE `job_skills`
  ADD CONSTRAINT `fk_job_skill_job` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`job_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_job_skill_skill` FOREIGN KEY (`skill_id`) REFERENCES `skills` (`skill_id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notification_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `worker_job_preferences`
--
ALTER TABLE `worker_job_preferences`
  ADD CONSTRAINT `fk_worker_preference_category` FOREIGN KEY (`job_category_id`) REFERENCES `job_categories` (`job_category_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_worker_preference_worker` FOREIGN KEY (`worker_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `worker_profiles`
--
ALTER TABLE `worker_profiles`
  ADD CONSTRAINT `fk_worker_profile_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `worker_skills`
--
ALTER TABLE `worker_skills`
  ADD CONSTRAINT `fk_worker_skill_skill` FOREIGN KEY (`skill_id`) REFERENCES `skills` (`skill_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_worker_skill_worker` FOREIGN KEY (`worker_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
