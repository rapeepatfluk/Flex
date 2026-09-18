-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 18, 2026 at 06:37 AM
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
-- Table structure for table `applications`
--

CREATE TABLE `applications` (
  `application_id` int(10) UNSIGNED NOT NULL,
  `job_id` int(10) UNSIGNED NOT NULL,
  `worker_user_id` int(10) UNSIGNED NOT NULL,
  `resume_file_path` varchar(255) DEFAULT NULL,
  `cover_note` text DEFAULT NULL,
  `application_status` enum('submitted','eligible','interview_passed','completed','not_selected','withdrawn') NOT NULL DEFAULT 'submitted',
  `completed_at` datetime DEFAULT NULL,
  `withdrawn_at` datetime DEFAULT NULL,
  `rating_by_worker` tinyint(3) UNSIGNED DEFAULT NULL,
  `rated_by_worker_at` timestamp NULL DEFAULT NULL,
  `rating_by_employer` tinyint(3) UNSIGNED DEFAULT NULL,
  `rated_by_employer_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `applications`
--

INSERT INTO `applications` (`application_id`, `job_id`, `worker_user_id`, `resume_file_path`, `cover_note`, `application_status`, `completed_at`, `withdrawn_at`, `rating_by_worker`, `rated_by_worker_at`, `rating_by_employer`, `rated_by_employer_at`, `created_at`) VALUES
(3, 32, 7, 'uploads/resumes/ac47684d40eadb4a0d0b.pdf', 'สวัสดี', 'withdrawn', NULL, '2026-09-04 12:13:43', NULL, NULL, NULL, NULL, '2026-08-23 08:52:40'),
(4, 33, 7, 'uploads/resumes/ac47684d40eadb4a0d0b.pdf', '', 'withdrawn', NULL, '2026-08-25 14:04:44', NULL, NULL, NULL, NULL, '2026-08-25 07:04:35'),
(5, 34, 13, 'uploads/resumes/1cbe7d369672277b3be9.pdf', '', 'not_selected', NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-30 08:25:52'),
(6, 32, 13, 'uploads/resumes/1cbe7d369672277b3be9.pdf', '', 'submitted', NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-30 10:08:20'),
(7, 21, 13, 'uploads/resumes/1cbe7d369672277b3be9.pdf', '', 'completed', NULL, NULL, 5, '2026-08-30 10:11:15', 5, '2026-08-30 10:10:05', '2026-08-30 10:08:50'),
(8, 39, 7, 'uploads/resumes/ac47684d40eadb4a0d0b.pdf', '', 'eligible', NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-04 05:13:37'),
(9, 54, 7, 'uploads/resumes/ac47684d40eadb4a0d0b.pdf', '', 'completed', '2026-09-12 17:45:02', NULL, 5, '2026-09-12 10:46:34', 5, '2026-09-12 10:45:02', '2026-09-12 10:44:36');

-- --------------------------------------------------------

--
-- Table structure for table `auth_tokens`
--

CREATE TABLE `auth_tokens` (
  `auth_token_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `token` char(64) NOT NULL,
  `token_type` enum('email_verification','password_reset') NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `auth_tokens`
--

INSERT INTO `auth_tokens` (`auth_token_id`, `user_id`, `token`, `token_type`, `expires_at`, `used_at`, `created_at`) VALUES
(1, 7, '5e69bca75fdcd246f67812c00750876d91caea3327777f05db0d23857e91bebc', 'email_verification', '2026-08-20 19:26:05', '2026-08-20 12:26:05', '2026-08-20 12:19:02'),
(2, 7, '9a275ee98773dc07a252827707088ea8360296517c2a2259c450f827fa13dcc2', 'email_verification', '2026-08-20 19:44:17', '2026-08-20 12:44:17', '2026-08-20 12:26:05'),
(3, 7, 'e66373a024814d6dd809a485eafb40939058ed4bfd7d1cee4df8e07de5e5f1a7', 'email_verification', '2026-08-20 19:44:27', '2026-08-20 12:44:27', '2026-08-20 12:44:17'),
(4, 12, 'b9265c2953dff9219b8ae428b7e557302214b769f459282e416ad48b3d00e12f', 'email_verification', '2026-08-23 19:22:48', '2026-08-22 12:23:10', '2026-08-22 12:22:48'),
(5, 12, 'f225dc3b3e6140f31e31f083a584ad88dfdd2d705bb110db2919965c89a253dc', 'email_verification', '2026-08-23 19:23:10', '2026-08-22 12:36:01', '2026-08-22 12:23:10'),
(6, 12, '241120187d6a5de89872d4dc523bd7fa9e3d2a31a73d1e515bb8da5bbf9d1f38', 'email_verification', '2026-08-23 19:35:56', '2026-08-22 12:36:33', '2026-08-22 12:35:56'),
(7, 12, 'a2ae62d6204853e4ad6b9f1ac217853f43e567f4910f6fd0a65e851af4eb3620', 'email_verification', '2026-08-23 19:36:28', '2026-08-22 12:36:38', '2026-08-22 12:36:28'),
(8, 12, 'b02669c7fdd1a75749dee4dce7789cd31acf053419fddbc444bcd9d03de57a3f', 'email_verification', '2026-08-23 19:36:34', '2026-08-22 12:41:41', '2026-08-22 12:36:34'),
(9, 12, '9b088c426c9d4a434186f800157cfccdeb2babc03c295d3a77ede89d72069042', 'email_verification', '2026-08-23 19:41:37', '2026-08-22 12:41:44', '2026-08-22 12:41:37'),
(10, 12, '06a75adffbf99bd879d97cafe08a52bd23ef5fcb9ad4d23d749b76a4a3aa5451', 'email_verification', '2026-08-23 19:41:41', '2026-08-22 12:44:12', '2026-08-22 12:41:41'),
(11, 12, 'e0cccab308703f7332d5cb6a743102c5fbf3894a714fb64ab8e2aeb0a52c5758', 'email_verification', '2026-08-23 19:44:08', '2026-08-22 12:44:23', '2026-08-22 12:44:08'),
(12, 13, '7e6fc531acaedd2c35088fe437fc71187f3403d8829f72a0bbda91ff8d1322ad', 'email_verification', '2026-08-27 21:06:15', '2026-08-26 14:19:32', '2026-08-26 14:06:15'),
(13, 13, '8ac5a90c87e45e7f7032f16ca0b99e84f9c4ed087fd3fc0ea3b3a6a5124f007b', 'email_verification', '2026-08-27 21:19:27', '2026-08-26 14:19:44', '2026-08-26 14:19:27'),
(16, 7, '92d4d177b9baca05fc66e6b10920f9296c612635abf238f4db69127c5d1cc664', 'password_reset', '2026-08-20 19:51:01', '2026-08-20 12:51:01', '2026-08-20 12:46:40'),
(17, 7, '5225951b35cae6ba7a4128fc71c1a8f9fbc035cfbcbc22c75be3ce3f8dec9568', 'password_reset', '2026-08-20 19:51:33', '2026-08-20 12:51:33', '2026-08-20 12:51:01'),
(18, 7, '1526e69850d9aeb20da266fb46bed98ce72d4f7d02eda80dbfc4bdcbcaba5aaa', 'password_reset', '2026-08-20 21:32:32', NULL, '2026-08-20 13:32:32'),
(20, 13, 'c9d7520afb19b18d4ef18fb05d0c382da88a3fbac569b32d2f36e0f9c30ea710', 'password_reset', '2026-09-04 03:43:03', '2026-09-03 19:43:31', '2026-09-03 19:43:03'),
(21, 15, 'c78b34221fceaa3849eda1e71a91d755ad58280c74e868f336191feb7e92691a', 'email_verification', '2026-09-05 03:11:18', '2026-09-03 20:11:28', '2026-09-03 20:11:18');

-- --------------------------------------------------------

--
-- Table structure for table `email_log`
--

CREATE TABLE `email_log` (
  `id` int(10) UNSIGNED NOT NULL,
  `to_email` varchar(190) NOT NULL,
  `to_name` varchar(190) DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `html_body` longtext DEFAULT NULL,
  `reply_to_email` varchar(190) DEFAULT NULL,
  `reply_to_name` varchar(190) DEFAULT NULL,
  `status` enum('queued','processing','sent','failed') NOT NULL DEFAULT 'queued',
  `error_msg` text DEFAULT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `available_at` datetime NOT NULL DEFAULT current_timestamp(),
  `locked_at` datetime DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `email_log`
--

INSERT INTO `email_log` (`id`, `to_email`, `to_name`, `subject`, `html_body`, `reply_to_email`, `reply_to_name`, `status`, `error_msg`, `attempts`, `available_at`, `locked_at`, `sent_at`) VALUES
(1, 'rapeepat.wo02@gmail.com', NULL, 'ยืนยันอีเมลของคุณ — FLEXJOB', NULL, NULL, NULL, 'failed', 'SMTP Error: Could not authenticate.', 0, '2026-09-04 11:16:24', NULL, '2026-08-20 12:19:04'),
(2, 'rapeepat.wo02@gmail.com', NULL, 'ลิงก์ยืนยันอีเมล (ใหม่) — FLEXJOB', NULL, NULL, NULL, 'failed', 'SMTP Error: Could not authenticate.', 0, '2026-09-04 11:16:24', NULL, '2026-08-20 12:26:08'),
(3, 'wongsuwan.fluk@gmail.com', NULL, 'ยืนยันอีเมลของคุณ — FLEXJOB', NULL, NULL, NULL, 'sent', NULL, 0, '2026-09-04 11:16:24', NULL, '2026-08-20 12:42:40'),
(4, 'rapeepat.wo02@gmail.com', NULL, 'ลิงก์ยืนยันอีเมล (ใหม่) — FLEXJOB', NULL, NULL, NULL, 'sent', NULL, 0, '2026-09-04 11:16:24', NULL, '2026-08-20 12:44:21'),
(5, 'rapeepat.wo02@gmail.com', NULL, '???ͺ???????ʼ?ҹ???? ? FLEXJOB', NULL, NULL, NULL, 'sent', NULL, 0, '2026-09-04 11:16:24', NULL, '2026-08-20 12:46:44'),
(6, 'rapeepat.wo02@gmail.com', NULL, 'ตั้งรหัสผ่านใหม่สำหรับบัญชี FLEXJOB', NULL, NULL, NULL, 'sent', NULL, 0, '2026-09-04 11:16:24', NULL, '2026-08-20 12:51:04'),
(7, 'abc@gmail.com', NULL, 'มีผู้สมัครงานใหม่: ต้องการนักเขียนโปรแกรม', NULL, NULL, NULL, 'sent', NULL, 0, '2026-09-04 11:16:24', NULL, '2026-08-20 12:51:57'),
(8, 'rapeepat.wo02@gmail.com', NULL, 'อัปเดตสถานะใบสมัคร: ต้องการนักเขียนโปรแกรม — abc company', NULL, NULL, NULL, 'sent', NULL, 0, '2026-09-04 11:16:24', NULL, '2026-08-20 12:52:15'),
(9, 'rapeepat.wo02@gmail.com', NULL, '???ͺ???????ʼ?ҹ???? ? FLEXJOB', NULL, NULL, NULL, 'sent', NULL, 0, '2026-09-04 11:16:24', NULL, '2026-08-20 13:32:36'),
(12, 'frk24072561@gmail.com', NULL, 'ลิงก์ยืนยันอีเมล (ใหม่) — FLEXJOB', NULL, NULL, NULL, 'sent', NULL, 0, '2026-09-04 11:16:24', NULL, '2026-08-22 12:36:01'),
(13, 'frk24072561@gmail.com', NULL, 'ลิงก์ยืนยันอีเมล (ใหม่) — FLEXJOB', NULL, NULL, NULL, 'sent', NULL, 0, '2026-09-04 11:16:24', NULL, '2026-08-22 12:36:33'),
(14, 'frk24072561@gmail.com', NULL, 'ลิงก์ยืนยันอีเมล (ใหม่) — FLEXJOB', NULL, NULL, NULL, 'sent', NULL, 0, '2026-09-04 11:16:24', NULL, '2026-08-22 12:36:38'),
(15, 'frk24072561@gmail.com', NULL, 'ลิงก์ยืนยันอีเมล (ใหม่) — FLEXJOB', NULL, NULL, NULL, 'sent', NULL, 0, '2026-09-04 11:16:24', NULL, '2026-08-22 12:41:41'),
(16, 'frk24072561@gmail.com', NULL, 'ลิงก์ยืนยันอีเมล (ใหม่) — FLEXJOB', NULL, NULL, NULL, 'sent', NULL, 0, '2026-09-04 11:16:24', NULL, '2026-08-22 12:41:44'),
(17, 'frk24072561@gmail.com', NULL, 'ลิงก์ยืนยันอีเมล (ใหม่) — FLEXJOB', NULL, NULL, NULL, 'sent', NULL, 0, '2026-09-04 11:16:24', NULL, '2026-08-22 12:44:12'),
(18, 'frk24072561@gmail.com', NULL, 'มีผู้สมัครงานใหม่: นักเขียนโปรแกรม', NULL, NULL, NULL, 'sent', NULL, 0, '2026-09-04 11:16:24', NULL, '2026-08-23 08:52:44'),
(19, 'frk24072561@gmail.com', NULL, 'มีผู้สมัครงานใหม่: ออกแบบ UX / UI', NULL, NULL, NULL, 'sent', NULL, 0, '2026-09-04 11:16:24', NULL, '2026-08-25 07:04:39'),
(20, 'bankchannel010@gmail.com', NULL, 'ยืนยันอีเมลของคุณ — FLEXJOB', NULL, NULL, NULL, 'failed', 'SMTP credentials are not configured', 0, '2026-09-04 11:16:24', NULL, '2026-08-26 14:06:15'),
(21, 'bankchannel010@gmail.com', NULL, 'ลิงก์ยืนยันอีเมล (ใหม่) — FLEXJOB', NULL, NULL, NULL, 'sent', NULL, 0, '2026-09-04 11:16:24', NULL, '2026-08-26 14:19:32'),
(22, 'frk24072561@gmail.com', NULL, 'มีผู้สมัครงานใหม่: คนยืนบูธงาน MotoGP', NULL, NULL, NULL, 'sent', NULL, 0, '2026-09-04 11:16:24', NULL, '2026-08-30 08:19:38'),
(23, 'frk24072561@gmail.com', NULL, 'มีผู้สมัครงานใหม่: คนยืนบูธงาน MotoGP', NULL, NULL, NULL, 'sent', NULL, 0, '2026-09-04 11:16:24', NULL, '2026-08-30 08:25:57'),
(24, 'frk24072561@gmail.com', NULL, 'มีผู้สมัครงานใหม่: นักเขียนโปรแกรม', NULL, NULL, NULL, 'sent', NULL, 0, '2026-09-04 11:16:24', NULL, '2026-08-30 10:08:24'),
(25, 'employer@gmail.com', NULL, 'มีผู้สมัครงานใหม่: Event Staff งานเปิดตัวสินค้า', NULL, NULL, NULL, 'sent', NULL, 0, '2026-09-04 11:16:24', NULL, '2026-08-30 10:08:54'),
(26, 'bankchannel010@gmail.com', NULL, 'อัปเดตสถานะใบสมัคร: Event Staff งานเปิดตัวสินค้า — Spark Event Studio', NULL, NULL, NULL, 'sent', NULL, 0, '2026-09-04 11:16:24', NULL, '2026-08-30 10:09:45'),
(27, 'bankchannel010@gmail.com', NULL, 'อัปเดตสถานะใบสมัคร: Event Staff งานเปิดตัวสินค้า — Spark Event Studio', NULL, NULL, NULL, 'sent', NULL, 0, '2026-09-04 11:16:24', NULL, '2026-08-30 20:33:09'),
(28, 'bankchannel010@gmail.com', NULL, 'อัปเดตสถานะใบสมัคร: Event Staff งานเปิดตัวสินค้า — Spark Event Studio', NULL, NULL, NULL, 'sent', NULL, 0, '2026-09-04 11:16:24', NULL, '2026-08-30 20:33:18'),
(29, 'wasuphon1205@gmail.com', NULL, 'ยืนยันอีเมลของคุณ — FLEXJOB', NULL, NULL, NULL, 'sent', NULL, 0, '2026-09-04 11:16:24', NULL, '2026-09-03 19:05:47'),
(30, 'bankchannel010@gmail.com', NULL, 'ตั้งรหัสผ่านใหม่สำหรับบัญชี FLEXJOB', NULL, NULL, NULL, 'sent', NULL, 0, '2026-09-04 11:16:24', NULL, '2026-09-03 19:43:07'),
(31, 'wasuphon1205@gmail.com', NULL, 'ยืนยันอีเมลของคุณ — FLEXJOB', NULL, NULL, NULL, 'sent', NULL, 0, '2026-09-04 11:16:24', NULL, '2026-09-03 20:11:23'),
(32, 'bankchannel010@gmail.com', NULL, 'อัปเดตสถานะใบสมัคร: Event Staff งานเปิดตัวสินค้า — Spark Event Studio', NULL, NULL, NULL, 'sent', NULL, 0, '2026-09-04 11:16:24', NULL, '2026-09-04 04:10:32'),
(33, 'bankchannel010@gmail.com', NULL, 'อัปเดตสถานะใบสมัคร: Event Staff งานเปิดตัวสินค้า — Spark Event Studio', NULL, NULL, NULL, 'sent', NULL, 0, '2026-09-04 11:16:24', NULL, '2026-09-04 04:10:44'),
(35, 'bankchannel010@gmail.com', 'Wasuphon Mahawong', 'อัปเดตสถานะใบสมัคร: Event Staff งานเปิดตัวสินค้า — Spark Event Studio', '<h2 style=\"margin:0 0 8px;font-size:24px;color:#17231f;letter-spacing:-1px;\">สวัสดี, Wasuphon Mahawong</h2>\n<p style=\"margin:0 0 24px;color:#697671;font-size:15px;\">มีการอัปเดตสถานะใบสมัครของคุณ</p>\n\n<table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" style=\"background:#f7f8f4;border-radius:12px;overflow:hidden;margin-bottom:24px;\">\n  <tr>\n    <td style=\"padding:20px 24px;\">\n      <p style=\"margin:0 0 6px;font-size:12px;color:#697671;text-transform:uppercase;letter-spacing:1px;\">ตำแหน่งงาน</p>\n      <p style=\"margin:0;font-size:18px;font-weight:700;color:#17231f;\">Event Staff งานเปิดตัวสินค้า</p>\n      <p style=\"margin:4px 0 0;font-size:14px;color:#697671;\">Spark Event Studio</p>\n    </td>\n  </tr>\n  <tr>\n    <td style=\"padding:0 24px 20px;\">\n      <p style=\"margin:0 0 6px;font-size:12px;color:#697671;text-transform:uppercase;letter-spacing:1px;\">สถานะปัจจุบัน</p>\n      <span style=\"display:inline-block;padding:6px 14px;background:#edf0ef;border-radius:20px;font-size:14px;font-weight:600;color:#047857;\">ผ่านสัมภาษณ์แล้ว 🎉</span>\n    </td>\n  </tr>\n</table>\n\n<div style=\"text-align:center;margin:28px 0;\">\n  <a href=\"http://localhost/Flex/worker/application-detail.php?id=7\" style=\"display:inline-block;padding:14px 32px;background:#0052cc;color:#ffffff;text-decoration:none;border-radius:10px;font-size:15px;font-weight:600;letter-spacing:-0.3px;\">ดูรายละเอียดการสมัคร</a>\n</div>\n\n<p style=\"font-size:13px;color:#697671;margin-top:8px;\">หากมีข้อสงสัย สามารถติดต่อผู้ว่าจ้างได้โดยตรงผ่านข้อมูลติดต่อในหน้ารายละเอียดการสมัคร</p>', NULL, '', 'sent', NULL, 1, '2026-09-04 11:18:39', NULL, '2026-09-04 04:19:06'),
(36, 'bankchannel010@gmail.com', 'Wasuphon Mahawong', 'อัปเดตสถานะใบสมัคร: Event Staff งานเปิดตัวสินค้า — Spark Event Studio', '<h2 style=\"margin:0 0 8px;font-size:24px;color:#17231f;letter-spacing:-1px;\">สวัสดี, Wasuphon Mahawong</h2>\n<p style=\"margin:0 0 24px;color:#697671;font-size:15px;\">มีการอัปเดตสถานะใบสมัครของคุณ</p>\n\n<table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" style=\"background:#f7f8f4;border-radius:12px;overflow:hidden;margin-bottom:24px;\">\n  <tr>\n    <td style=\"padding:20px 24px;\">\n      <p style=\"margin:0 0 6px;font-size:12px;color:#697671;text-transform:uppercase;letter-spacing:1px;\">ตำแหน่งงาน</p>\n      <p style=\"margin:0;font-size:18px;font-weight:700;color:#17231f;\">Event Staff งานเปิดตัวสินค้า</p>\n      <p style=\"margin:4px 0 0;font-size:14px;color:#697671;\">Spark Event Studio</p>\n    </td>\n  </tr>\n  <tr>\n    <td style=\"padding:0 24px 20px;\">\n      <p style=\"margin:0 0 6px;font-size:12px;color:#697671;text-transform:uppercase;letter-spacing:1px;\">สถานะปัจจุบัน</p>\n      <span style=\"display:inline-block;padding:6px 14px;background:#edf0ef;border-radius:20px;font-size:14px;font-weight:600;color:#0052cc;\">งานเสร็จสิ้น — ให้คะแนนได้</span>\n    </td>\n  </tr>\n</table>\n\n<div style=\"text-align:center;margin:28px 0;\">\n  <a href=\"http://localhost/Flex/worker/application-detail.php?id=7\" style=\"display:inline-block;padding:14px 32px;background:#0052cc;color:#ffffff;text-decoration:none;border-radius:10px;font-size:15px;font-weight:600;letter-spacing:-0.3px;\">ดูรายละเอียดการสมัคร</a>\n</div>\n\n<p style=\"font-size:13px;color:#697671;margin-top:8px;\">หากมีข้อสงสัย สามารถติดต่อผู้ว่าจ้างได้โดยตรงผ่านข้อมูลติดต่อในหน้ารายละเอียดการสมัคร</p>', NULL, '', 'processing', NULL, 1, '2026-09-04 11:18:41', '2026-09-04 11:19:01', NULL),
(37, 'bankchannel010@gmail.com', 'Wasuphon Mahawong', 'อัปเดตสถานะใบสมัคร: คนยืนบูธงาน MotoGP — fluk Software company', '<h2 style=\"margin:0 0 8px;font-size:24px;color:#17231f;letter-spacing:-1px;\">สวัสดี, Wasuphon Mahawong</h2>\n<p style=\"margin:0 0 24px;color:#697671;font-size:15px;\">มีการอัปเดตสถานะใบสมัครของคุณ</p>\n\n<table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" style=\"background:#f7f8f4;border-radius:12px;overflow:hidden;margin-bottom:24px;\">\n  <tr>\n    <td style=\"padding:20px 24px;\">\n      <p style=\"margin:0 0 6px;font-size:12px;color:#697671;text-transform:uppercase;letter-spacing:1px;\">ตำแหน่งงาน</p>\n      <p style=\"margin:0;font-size:18px;font-weight:700;color:#17231f;\">คนยืนบูธงาน MotoGP</p>\n      <p style=\"margin:4px 0 0;font-size:14px;color:#697671;\">fluk Software company</p>\n    </td>\n  </tr>\n  <tr>\n    <td style=\"padding:0 24px 20px;\">\n      <p style=\"margin:0 0 6px;font-size:12px;color:#697671;text-transform:uppercase;letter-spacing:1px;\">สถานะปัจจุบัน</p>\n      <span style=\"display:inline-block;padding:6px 14px;background:#edf0ef;border-radius:20px;font-size:14px;font-weight:600;color:#19663f;\">มีสิทธิ์สัมภาษณ์ 🎉</span>\n    </td>\n  </tr>\n</table>\n\n<div style=\"text-align:center;margin:28px 0;\">\n  <a href=\"http://localhost/Flex/worker/application-detail.php?id=5\" style=\"display:inline-block;padding:14px 32px;background:#0052cc;color:#ffffff;text-decoration:none;border-radius:10px;font-size:15px;font-weight:600;letter-spacing:-0.3px;\">ดูรายละเอียดการสมัคร</a>\n</div>\n\n<p style=\"font-size:13px;color:#697671;margin-top:8px;\">หากมีข้อสงสัย สามารถติดต่อผู้ว่าจ้างได้โดยตรงผ่านข้อมูลติดต่อในหน้ารายละเอียดการสมัคร</p>', NULL, '', 'queued', NULL, 0, '2026-09-04 12:00:09', NULL, NULL),
(38, 'bankchannel010@gmail.com', 'Wasuphon Mahawong', 'อัปเดตสถานะใบสมัคร: คนยืนบูธงาน MotoGP — fluk Software company', '<h2 style=\"margin:0 0 8px;font-size:24px;color:#17231f;letter-spacing:-1px;\">สวัสดี, Wasuphon Mahawong</h2>\n<p style=\"margin:0 0 24px;color:#697671;font-size:15px;\">มีการอัปเดตสถานะใบสมัครของคุณ</p>\n\n<table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" style=\"background:#f7f8f4;border-radius:12px;overflow:hidden;margin-bottom:24px;\">\n  <tr>\n    <td style=\"padding:20px 24px;\">\n      <p style=\"margin:0 0 6px;font-size:12px;color:#697671;text-transform:uppercase;letter-spacing:1px;\">ตำแหน่งงาน</p>\n      <p style=\"margin:0;font-size:18px;font-weight:700;color:#17231f;\">คนยืนบูธงาน MotoGP</p>\n      <p style=\"margin:4px 0 0;font-size:14px;color:#697671;\">fluk Software company</p>\n    </td>\n  </tr>\n  <tr>\n    <td style=\"padding:0 24px 20px;\">\n      <p style=\"margin:0 0 6px;font-size:12px;color:#697671;text-transform:uppercase;letter-spacing:1px;\">สถานะปัจจุบัน</p>\n      <span style=\"display:inline-block;padding:6px 14px;background:#edf0ef;border-radius:20px;font-size:14px;font-weight:600;color:#bd4d3d;\">ไม่ผ่านการคัดเลือก</span>\n    </td>\n  </tr>\n</table>\n\n<div style=\"text-align:center;margin:28px 0;\">\n  <a href=\"http://localhost/Flex/worker/application-detail.php?id=5\" style=\"display:inline-block;padding:14px 32px;background:#0052cc;color:#ffffff;text-decoration:none;border-radius:10px;font-size:15px;font-weight:600;letter-spacing:-0.3px;\">ดูรายละเอียดการสมัคร</a>\n</div>\n\n<p style=\"font-size:13px;color:#697671;margin-top:8px;\">หากมีข้อสงสัย สามารถติดต่อผู้ว่าจ้างได้โดยตรงผ่านข้อมูลติดต่อในหน้ารายละเอียดการสมัคร</p>', NULL, '', 'queued', NULL, 0, '2026-09-04 12:00:28', NULL, NULL),
(39, 'bankchannel010@gmail.com', 'Wasuphon Mahawong', 'อัปเดตสถานะใบสมัคร: คนยืนบูธงาน MotoGP — fluk Software company', '<h2 style=\"margin:0 0 8px;font-size:24px;color:#17231f;letter-spacing:-1px;\">สวัสดี, Wasuphon Mahawong</h2>\n<p style=\"margin:0 0 24px;color:#697671;font-size:15px;\">มีการอัปเดตสถานะใบสมัครของคุณ</p>\n\n<table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" style=\"background:#f7f8f4;border-radius:12px;overflow:hidden;margin-bottom:24px;\">\n  <tr>\n    <td style=\"padding:20px 24px;\">\n      <p style=\"margin:0 0 6px;font-size:12px;color:#697671;text-transform:uppercase;letter-spacing:1px;\">ตำแหน่งงาน</p>\n      <p style=\"margin:0;font-size:18px;font-weight:700;color:#17231f;\">คนยืนบูธงาน MotoGP</p>\n      <p style=\"margin:4px 0 0;font-size:14px;color:#697671;\">fluk Software company</p>\n    </td>\n  </tr>\n  <tr>\n    <td style=\"padding:0 24px 20px;\">\n      <p style=\"margin:0 0 6px;font-size:12px;color:#697671;text-transform:uppercase;letter-spacing:1px;\">สถานะปัจจุบัน</p>\n      <span style=\"display:inline-block;padding:6px 14px;background:#edf0ef;border-radius:20px;font-size:14px;font-weight:600;color:#19663f;\">มีสิทธิ์สัมภาษณ์ 🎉</span>\n    </td>\n  </tr>\n</table>\n\n<div style=\"text-align:center;margin:28px 0;\">\n  <a href=\"http://localhost/Flex/worker/application-detail.php?id=5\" style=\"display:inline-block;padding:14px 32px;background:#0052cc;color:#ffffff;text-decoration:none;border-radius:10px;font-size:15px;font-weight:600;letter-spacing:-0.3px;\">ดูรายละเอียดการสมัคร</a>\n</div>\n\n<p style=\"font-size:13px;color:#697671;margin-top:8px;\">หากมีข้อสงสัย สามารถติดต่อผู้ว่าจ้างได้โดยตรงผ่านข้อมูลติดต่อในหน้ารายละเอียดการสมัคร</p>', NULL, '', 'queued', NULL, 0, '2026-09-04 12:00:53', NULL, NULL),
(40, 'frk24072561@gmail.com', 'Rapeepat Wongsuwan', 'มีผู้สมัครงานใหม่: พนังงานขายสินค้า Powerbuy', '<h2 style=\"margin:0 0 8px;font-size:24px;color:#17231f;letter-spacing:-1px;\">มีผู้สมัครงานใหม่! 🎯</h2>\n<p style=\"margin:0 0 24px;color:#697671;font-size:15px;\">สวัสดี Rapeepat Wongsuwan — มีคนสมัครงานของคุณ</p>\n\n<table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" style=\"background:#f7f8f4;border-radius:12px;margin-bottom:20px;\">\n  <tr><td style=\"padding:20px 24px;\">\n    <p style=\"margin:0 0 4px;font-size:12px;color:#697671;text-transform:uppercase;letter-spacing:1px;\">ตำแหน่งงาน</p>\n    <p style=\"margin:0;font-size:18px;font-weight:700;color:#17231f;\">พนังงานขายสินค้า Powerbuy</p>\n  </td></tr>\n</table>\n\n<table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" style=\"border:1px solid #e0e8e3;border-radius:12px;overflow:hidden;margin-bottom:20px;\">\n  <tr style=\"background:#166b54;\">\n    <td colspan=\"2\" style=\"padding:14px 20px;\">\n      <div style=\"display:inline-block;width:36px;height:36px;border-radius:50%;background:#d7f56d;text-align:center;line-height:36px;font-weight:700;font-size:16px;color:#166b54;float:left;margin-right:12px;\">\n        R\n      </div>\n      <span style=\"color:#ffffff;font-size:16px;font-weight:600;line-height:36px;\">Rapeepat Wongsuwan</span>\n    </td>\n  </tr>\n  <tr>\n    <td style=\"padding:12px 20px;font-size:13px;color:#697671;border-bottom:1px solid #e0e8e3;\">📧 อีเมล</td>\n    <td style=\"padding:12px 20px;font-size:13px;font-weight:600;border-bottom:1px solid #e0e8e3;\">rapeepat.wo02@gmail.com</td>\n  </tr>\n  <tr>\n    <td style=\"padding:12px 20px;font-size:13px;color:#697671;\">📞 โทรศัพท์</td>\n    <td style=\"padding:12px 20px;font-size:13px;font-weight:600;\">0919876782</td>\n  </tr>\n</table>\n\n<div style=\"text-align:center;margin:28px 0;\">\n  <a href=\"http://localhost/Flex/employer/applicants.php?job=39\" style=\"display:inline-block;padding:14px 32px;background:#0052cc;color:#ffffff;text-decoration:none;border-radius:10px;font-size:15px;font-weight:600;letter-spacing:-0.3px;\">ดูผู้สมัครทั้งหมด →</a>\n</div>', NULL, '', 'queued', NULL, 0, '2026-09-04 12:11:53', NULL, NULL),
(41, 'frk24072561@gmail.com', 'Rapeepat Wongsuwan', 'มีผู้สมัครงานใหม่: พนังงานขายสินค้า Powerbuy', '<h2 style=\"margin:0 0 8px;font-size:24px;color:#17231f;letter-spacing:-1px;\">มีผู้สมัครงานใหม่! 🎯</h2>\n<p style=\"margin:0 0 24px;color:#697671;font-size:15px;\">สวัสดี Rapeepat Wongsuwan — มีคนสมัครงานของคุณ</p>\n\n<table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" style=\"background:#f7f8f4;border-radius:12px;margin-bottom:20px;\">\n  <tr><td style=\"padding:20px 24px;\">\n    <p style=\"margin:0 0 4px;font-size:12px;color:#697671;text-transform:uppercase;letter-spacing:1px;\">ตำแหน่งงาน</p>\n    <p style=\"margin:0;font-size:18px;font-weight:700;color:#17231f;\">พนังงานขายสินค้า Powerbuy</p>\n  </td></tr>\n</table>\n\n<table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" style=\"border:1px solid #e0e8e3;border-radius:12px;overflow:hidden;margin-bottom:20px;\">\n  <tr style=\"background:#166b54;\">\n    <td colspan=\"2\" style=\"padding:14px 20px;\">\n      <div style=\"display:inline-block;width:36px;height:36px;border-radius:50%;background:#d7f56d;text-align:center;line-height:36px;font-weight:700;font-size:16px;color:#166b54;float:left;margin-right:12px;\">\n        R\n      </div>\n      <span style=\"color:#ffffff;font-size:16px;font-weight:600;line-height:36px;\">Rapeepat Wongsuwan</span>\n    </td>\n  </tr>\n  <tr>\n    <td style=\"padding:12px 20px;font-size:13px;color:#697671;border-bottom:1px solid #e0e8e3;\">📧 อีเมล</td>\n    <td style=\"padding:12px 20px;font-size:13px;font-weight:600;border-bottom:1px solid #e0e8e3;\">rapeepat.wo02@gmail.com</td>\n  </tr>\n  <tr>\n    <td style=\"padding:12px 20px;font-size:13px;color:#697671;\">📞 โทรศัพท์</td>\n    <td style=\"padding:12px 20px;font-size:13px;font-weight:600;\">0919876782</td>\n  </tr>\n</table>\n\n<div style=\"text-align:center;margin:28px 0;\">\n  <a href=\"http://localhost/Flex/employer/applicants.php?job=39\" style=\"display:inline-block;padding:14px 32px;background:#0052cc;color:#ffffff;text-decoration:none;border-radius:10px;font-size:15px;font-weight:600;letter-spacing:-0.3px;\">ดูผู้สมัครทั้งหมด →</a>\n</div>', NULL, '', 'queued', NULL, 0, '2026-09-04 12:13:37', NULL, NULL),
(42, 'rapeepat.wo02@gmail.com', 'Rapeepat Wongsuwan', 'อัปเดตสถานะใบสมัคร: พนังงานขายสินค้า Powerbuy — fluk Software company', '<h2 style=\"margin:0 0 8px;font-size:24px;color:#17231f;letter-spacing:-1px;\">สวัสดี, Rapeepat Wongsuwan</h2>\n<p style=\"margin:0 0 24px;color:#697671;font-size:15px;\">มีการอัปเดตสถานะใบสมัครของคุณ</p>\n\n<table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" style=\"background:#f7f8f4;border-radius:12px;overflow:hidden;margin-bottom:24px;\">\n  <tr>\n    <td style=\"padding:20px 24px;\">\n      <p style=\"margin:0 0 6px;font-size:12px;color:#697671;text-transform:uppercase;letter-spacing:1px;\">ตำแหน่งงาน</p>\n      <p style=\"margin:0;font-size:18px;font-weight:700;color:#17231f;\">พนังงานขายสินค้า Powerbuy</p>\n      <p style=\"margin:4px 0 0;font-size:14px;color:#697671;\">fluk Software company</p>\n    </td>\n  </tr>\n  <tr>\n    <td style=\"padding:0 24px 20px;\">\n      <p style=\"margin:0 0 6px;font-size:12px;color:#697671;text-transform:uppercase;letter-spacing:1px;\">สถานะปัจจุบัน</p>\n      <span style=\"display:inline-block;padding:6px 14px;background:#edf0ef;border-radius:20px;font-size:14px;font-weight:600;color:#19663f;\">มีสิทธิ์สัมภาษณ์ 🎉</span>\n    </td>\n  </tr>\n</table>\n\n<div style=\"text-align:center;margin:28px 0;\">\n  <a href=\"http://localhost/Flex/worker/application-detail.php?id=8\" style=\"display:inline-block;padding:14px 32px;background:#0052cc;color:#ffffff;text-decoration:none;border-radius:10px;font-size:15px;font-weight:600;letter-spacing:-0.3px;\">ดูรายละเอียดการสมัคร</a>\n</div>\n\n<p style=\"font-size:13px;color:#697671;margin-top:8px;\">หากมีข้อสงสัย สามารถติดต่อผู้ว่าจ้างได้โดยตรงผ่านข้อมูลติดต่อในหน้ารายละเอียดการสมัคร</p>', NULL, '', 'queued', NULL, 0, '2026-09-04 12:14:26', NULL, NULL),
(43, 'rapeepat.wo02@gmail.com', 'Rapeepat Wongsuwan', 'เรื่องใบสมัครงาน: พนังงานขายสินค้า Powerbuy', NULL, NULL, NULL, 'sent', NULL, 0, '2026-09-04 12:14:49', NULL, '2026-09-04 00:14:49'),
(44, 'rapeepat.wo02@gmail.com', 'Rapeepat Wongsuwan', 'อัปเดตสถานะใบสมัคร: พนังงานขายสินค้า Powerbuy — fluk Software company', '<h2 style=\"margin:0 0 8px;font-size:24px;color:#17231f;letter-spacing:-1px;\">สวัสดี, Rapeepat Wongsuwan</h2>\n<p style=\"margin:0 0 24px;color:#697671;font-size:15px;\">มีการอัปเดตสถานะใบสมัครของคุณ</p>\n\n<table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" style=\"background:#f7f8f4;border-radius:12px;overflow:hidden;margin-bottom:24px;\">\n  <tr>\n    <td style=\"padding:20px 24px;\">\n      <p style=\"margin:0 0 6px;font-size:12px;color:#697671;text-transform:uppercase;letter-spacing:1px;\">ตำแหน่งงาน</p>\n      <p style=\"margin:0;font-size:18px;font-weight:700;color:#17231f;\">พนังงานขายสินค้า Powerbuy</p>\n      <p style=\"margin:4px 0 0;font-size:14px;color:#697671;\">fluk Software company</p>\n    </td>\n  </tr>\n  <tr>\n    <td style=\"padding:0 24px 20px;\">\n      <p style=\"margin:0 0 6px;font-size:12px;color:#697671;text-transform:uppercase;letter-spacing:1px;\">สถานะปัจจุบัน</p>\n      <span style=\"display:inline-block;padding:6px 14px;background:#edf0ef;border-radius:20px;font-size:14px;font-weight:600;color:#047857;\">ผ่านสัมภาษณ์แล้ว 🎉</span>\n    </td>\n  </tr>\n</table>\n\n<div style=\"text-align:center;margin:28px 0;\">\n  <a href=\"http://localhost/Flex/worker/application-detail.php?id=8\" style=\"display:inline-block;padding:14px 32px;background:#0052cc;color:#ffffff;text-decoration:none;border-radius:10px;font-size:15px;font-weight:600;letter-spacing:-0.3px;\">ดูรายละเอียดการสมัคร</a>\n</div>\n\n<p style=\"font-size:13px;color:#697671;margin-top:8px;\">หากมีข้อสงสัย สามารถติดต่อผู้ว่าจ้างได้โดยตรงผ่านข้อมูลติดต่อในหน้ารายละเอียดการสมัคร</p>', NULL, '', 'queued', NULL, 0, '2026-09-04 12:15:58', NULL, NULL),
(45, 'rapeepat.wo02@gmail.com', 'Rapeepat Wongsuwan', 'อัปเดตสถานะใบสมัคร: พนังงานขายสินค้า Powerbuy — fluk Software company', '<h2 style=\"margin:0 0 8px;font-size:24px;color:#17231f;letter-spacing:-1px;\">สวัสดี, Rapeepat Wongsuwan</h2>\n<p style=\"margin:0 0 24px;color:#697671;font-size:15px;\">มีการอัปเดตสถานะใบสมัครของคุณ</p>\n\n<table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" style=\"background:#f7f8f4;border-radius:12px;overflow:hidden;margin-bottom:24px;\">\n  <tr>\n    <td style=\"padding:20px 24px;\">\n      <p style=\"margin:0 0 6px;font-size:12px;color:#697671;text-transform:uppercase;letter-spacing:1px;\">ตำแหน่งงาน</p>\n      <p style=\"margin:0;font-size:18px;font-weight:700;color:#17231f;\">พนังงานขายสินค้า Powerbuy</p>\n      <p style=\"margin:4px 0 0;font-size:14px;color:#697671;\">fluk Software company</p>\n    </td>\n  </tr>\n  <tr>\n    <td style=\"padding:0 24px 20px;\">\n      <p style=\"margin:0 0 6px;font-size:12px;color:#697671;text-transform:uppercase;letter-spacing:1px;\">สถานะปัจจุบัน</p>\n      <span style=\"display:inline-block;padding:6px 14px;background:#edf0ef;border-radius:20px;font-size:14px;font-weight:600;color:#19663f;\">มีสิทธิ์สัมภาษณ์ 🎉</span>\n    </td>\n  </tr>\n</table>\n\n<div style=\"text-align:center;margin:28px 0;\">\n  <a href=\"http://localhost/Flex/worker/application-detail.php?id=8\" style=\"display:inline-block;padding:14px 32px;background:#0052cc;color:#ffffff;text-decoration:none;border-radius:10px;font-size:15px;font-weight:600;letter-spacing:-0.3px;\">ดูรายละเอียดการสมัคร</a>\n</div>\n\n<p style=\"font-size:13px;color:#697671;margin-top:8px;\">หากมีข้อสงสัย สามารถติดต่อผู้ว่าจ้างได้โดยตรงผ่านข้อมูลติดต่อในหน้ารายละเอียดการสมัคร</p>', NULL, '', 'queued', NULL, 0, '2026-09-04 12:16:00', NULL, NULL),
(46, 'rapeepat.wo02@gmail.com', 'Rapeepat Wongsuwan', 'อัปเดตสถานะใบสมัคร: พนังงานขายสินค้า Powerbuy — fluk Software company', '<h2 style=\"margin:0 0 8px;font-size:24px;color:#17231f;letter-spacing:-1px;\">สวัสดี, Rapeepat Wongsuwan</h2>\n<p style=\"margin:0 0 24px;color:#697671;font-size:15px;\">มีการอัปเดตสถานะใบสมัครของคุณ</p>\n\n<table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" style=\"background:#f7f8f4;border-radius:12px;overflow:hidden;margin-bottom:24px;\">\n  <tr>\n    <td style=\"padding:20px 24px;\">\n      <p style=\"margin:0 0 6px;font-size:12px;color:#697671;text-transform:uppercase;letter-spacing:1px;\">ตำแหน่งงาน</p>\n      <p style=\"margin:0;font-size:18px;font-weight:700;color:#17231f;\">พนังงานขายสินค้า Powerbuy</p>\n      <p style=\"margin:4px 0 0;font-size:14px;color:#697671;\">fluk Software company</p>\n    </td>\n  </tr>\n  <tr>\n    <td style=\"padding:0 24px 20px;\">\n      <p style=\"margin:0 0 6px;font-size:12px;color:#697671;text-transform:uppercase;letter-spacing:1px;\">สถานะปัจจุบัน</p>\n      <span style=\"display:inline-block;padding:6px 14px;background:#edf0ef;border-radius:20px;font-size:14px;font-weight:600;color:#047857;\">ผ่านสัมภาษณ์แล้ว 🎉</span>\n    </td>\n  </tr>\n</table>\n\n<div style=\"text-align:center;margin:28px 0;\">\n  <a href=\"http://localhost/Flex/worker/application-detail.php?id=8\" style=\"display:inline-block;padding:14px 32px;background:#0052cc;color:#ffffff;text-decoration:none;border-radius:10px;font-size:15px;font-weight:600;letter-spacing:-0.3px;\">ดูรายละเอียดการสมัคร</a>\n</div>\n\n<p style=\"font-size:13px;color:#697671;margin-top:8px;\">หากมีข้อสงสัย สามารถติดต่อผู้ว่าจ้างได้โดยตรงผ่านข้อมูลติดต่อในหน้ารายละเอียดการสมัคร</p>', NULL, '', 'queued', NULL, 0, '2026-09-04 12:16:13', NULL, NULL),
(47, 'rapeepat.wo02@gmail.com', 'Rapeepat Wongsuwan', 'อัปเดตสถานะใบสมัคร: พนังงานขายสินค้า Powerbuy — fluk Software company', '<h2 style=\"margin:0 0 8px;font-size:24px;color:#17231f;letter-spacing:-1px;\">สวัสดี, Rapeepat Wongsuwan</h2>\n<p style=\"margin:0 0 24px;color:#697671;font-size:15px;\">มีการอัปเดตสถานะใบสมัครของคุณ</p>\n\n<table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" style=\"background:#f7f8f4;border-radius:12px;overflow:hidden;margin-bottom:24px;\">\n  <tr>\n    <td style=\"padding:20px 24px;\">\n      <p style=\"margin:0 0 6px;font-size:12px;color:#697671;text-transform:uppercase;letter-spacing:1px;\">ตำแหน่งงาน</p>\n      <p style=\"margin:0;font-size:18px;font-weight:700;color:#17231f;\">พนังงานขายสินค้า Powerbuy</p>\n      <p style=\"margin:4px 0 0;font-size:14px;color:#697671;\">fluk Software company</p>\n    </td>\n  </tr>\n  <tr>\n    <td style=\"padding:0 24px 20px;\">\n      <p style=\"margin:0 0 6px;font-size:12px;color:#697671;text-transform:uppercase;letter-spacing:1px;\">สถานะปัจจุบัน</p>\n      <span style=\"display:inline-block;padding:6px 14px;background:#edf0ef;border-radius:20px;font-size:14px;font-weight:600;color:#8a6100;\">รอพิจารณา</span>\n    </td>\n  </tr>\n</table>\n\n\n\n<p style=\"font-size:13px;color:#697671;margin-top:8px;\">หากมีข้อสงสัย สามารถติดต่อผู้ว่าจ้างได้โดยตรงผ่านข้อมูลติดต่อในหน้ารายละเอียดการสมัคร</p>', NULL, '', 'queued', NULL, 0, '2026-09-04 12:16:16', NULL, NULL),
(48, 'rapeepat.wo02@gmail.com', 'Rapeepat Wongsuwan', 'อัปเดตสถานะใบสมัคร: พนังงานขายสินค้า Powerbuy — fluk Software company', '<h2 style=\"margin:0 0 8px;font-size:24px;color:#17231f;letter-spacing:-1px;\">สวัสดี, Rapeepat Wongsuwan</h2>\n<p style=\"margin:0 0 24px;color:#697671;font-size:15px;\">มีการอัปเดตสถานะใบสมัครของคุณ</p>\n\n<table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" style=\"background:#f7f8f4;border-radius:12px;overflow:hidden;margin-bottom:24px;\">\n  <tr>\n    <td style=\"padding:20px 24px;\">\n      <p style=\"margin:0 0 6px;font-size:12px;color:#697671;text-transform:uppercase;letter-spacing:1px;\">ตำแหน่งงาน</p>\n      <p style=\"margin:0;font-size:18px;font-weight:700;color:#17231f;\">พนังงานขายสินค้า Powerbuy</p>\n      <p style=\"margin:4px 0 0;font-size:14px;color:#697671;\">fluk Software company</p>\n    </td>\n  </tr>\n  <tr>\n    <td style=\"padding:0 24px 20px;\">\n      <p style=\"margin:0 0 6px;font-size:12px;color:#697671;text-transform:uppercase;letter-spacing:1px;\">สถานะปัจจุบัน</p>\n      <span style=\"display:inline-block;padding:6px 14px;background:#edf0ef;border-radius:20px;font-size:14px;font-weight:600;color:#19663f;\">มีสิทธิ์สัมภาษณ์ 🎉</span>\n    </td>\n  </tr>\n</table>\n\n<div style=\"text-align:center;margin:28px 0;\">\n  <a href=\"http://localhost/Flex/worker/application-detail.php?id=8\" style=\"display:inline-block;padding:14px 32px;background:#0052cc;color:#ffffff;text-decoration:none;border-radius:10px;font-size:15px;font-weight:600;letter-spacing:-0.3px;\">ดูรายละเอียดการสมัคร</a>\n</div>\n\n<p style=\"font-size:13px;color:#697671;margin-top:8px;\">หากมีข้อสงสัย สามารถติดต่อผู้ว่าจ้างได้โดยตรงผ่านข้อมูลติดต่อในหน้ารายละเอียดการสมัคร</p>', NULL, '', 'queued', NULL, 0, '2026-09-04 12:19:07', NULL, NULL),
(49, 'rapeepat.wo02@gmail.com', 'Rapeepat Wongsuwan', 'อัปเดตสถานะใบสมัคร: พนังงานขายสินค้า Powerbuy — fluk Software company', '<h2 style=\"margin:0 0 8px;font-size:24px;color:#17231f;letter-spacing:-1px;\">สวัสดี, Rapeepat Wongsuwan</h2>\n<p style=\"margin:0 0 24px;color:#697671;font-size:15px;\">มีการอัปเดตสถานะใบสมัครของคุณ</p>\n\n<table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" style=\"background:#f7f8f4;border-radius:12px;overflow:hidden;margin-bottom:24px;\">\n  <tr>\n    <td style=\"padding:20px 24px;\">\n      <p style=\"margin:0 0 6px;font-size:12px;color:#697671;text-transform:uppercase;letter-spacing:1px;\">ตำแหน่งงาน</p>\n      <p style=\"margin:0;font-size:18px;font-weight:700;color:#17231f;\">พนังงานขายสินค้า Powerbuy</p>\n      <p style=\"margin:4px 0 0;font-size:14px;color:#697671;\">fluk Software company</p>\n    </td>\n  </tr>\n  <tr>\n    <td style=\"padding:0 24px 20px;\">\n      <p style=\"margin:0 0 6px;font-size:12px;color:#697671;text-transform:uppercase;letter-spacing:1px;\">สถานะปัจจุบัน</p>\n      <span style=\"display:inline-block;padding:6px 14px;background:#edf0ef;border-radius:20px;font-size:14px;font-weight:600;color:#047857;\">ผ่านสัมภาษณ์แล้ว 🎉</span>\n    </td>\n  </tr>\n</table>\n\n<div style=\"text-align:center;margin:28px 0;\">\n  <a href=\"http://localhost/Flex/worker/application-detail.php?id=8\" style=\"display:inline-block;padding:14px 32px;background:#0052cc;color:#ffffff;text-decoration:none;border-radius:10px;font-size:15px;font-weight:600;letter-spacing:-0.3px;\">ดูรายละเอียดการสมัคร</a>\n</div>\n\n<p style=\"font-size:13px;color:#697671;margin-top:8px;\">หากมีข้อสงสัย สามารถติดต่อผู้ว่าจ้างได้โดยตรงผ่านข้อมูลติดต่อในหน้ารายละเอียดการสมัคร</p>', NULL, '', 'queued', NULL, 0, '2026-09-04 12:33:24', NULL, NULL),
(50, 'rapeepat.wo02@gmail.com', 'Rapeepat Wongsuwan', 'อัปเดตสถานะใบสมัคร: พนังงานขายสินค้า Powerbuy — fluk Software company', '<h2 style=\"margin:0 0 8px;font-size:24px;color:#17231f;letter-spacing:-1px;\">สวัสดี, Rapeepat Wongsuwan</h2>\n<p style=\"margin:0 0 24px;color:#697671;font-size:15px;\">มีการอัปเดตสถานะใบสมัครของคุณ</p>\n\n<table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" style=\"background:#f7f8f4;border-radius:12px;overflow:hidden;margin-bottom:24px;\">\n  <tr>\n    <td style=\"padding:20px 24px;\">\n      <p style=\"margin:0 0 6px;font-size:12px;color:#697671;text-transform:uppercase;letter-spacing:1px;\">ตำแหน่งงาน</p>\n      <p style=\"margin:0;font-size:18px;font-weight:700;color:#17231f;\">พนังงานขายสินค้า Powerbuy</p>\n      <p style=\"margin:4px 0 0;font-size:14px;color:#697671;\">fluk Software company</p>\n    </td>\n  </tr>\n  <tr>\n    <td style=\"padding:0 24px 20px;\">\n      <p style=\"margin:0 0 6px;font-size:12px;color:#697671;text-transform:uppercase;letter-spacing:1px;\">สถานะปัจจุบัน</p>\n      <span style=\"display:inline-block;padding:6px 14px;background:#edf0ef;border-radius:20px;font-size:14px;font-weight:600;color:#8a6100;\">รอพิจารณา</span>\n    </td>\n  </tr>\n</table>\n\n\n\n<p style=\"font-size:13px;color:#697671;margin-top:8px;\">หากมีข้อสงสัย สามารถติดต่อผู้ว่าจ้างได้โดยตรงผ่านข้อมูลติดต่อในหน้ารายละเอียดการสมัคร</p>', NULL, '', 'queued', NULL, 0, '2026-09-04 12:33:51', NULL, NULL),
(51, 'rapeepat.wo02@gmail.com', 'Rapeepat Wongsuwan', 'อัปเดตสถานะใบสมัคร: พนังงานขายสินค้า Powerbuy — fluk Software company', '<h2 style=\"margin:0 0 8px;font-size:24px;color:#17231f;letter-spacing:-1px;\">สวัสดี, Rapeepat Wongsuwan</h2>\n<p style=\"margin:0 0 24px;color:#697671;font-size:15px;\">มีการอัปเดตสถานะใบสมัครของคุณ</p>\n\n<table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" style=\"background:#f7f8f4;border-radius:12px;overflow:hidden;margin-bottom:24px;\">\n  <tr>\n    <td style=\"padding:20px 24px;\">\n      <p style=\"margin:0 0 6px;font-size:12px;color:#697671;text-transform:uppercase;letter-spacing:1px;\">ตำแหน่งงาน</p>\n      <p style=\"margin:0;font-size:18px;font-weight:700;color:#17231f;\">พนังงานขายสินค้า Powerbuy</p>\n      <p style=\"margin:4px 0 0;font-size:14px;color:#697671;\">fluk Software company</p>\n    </td>\n  </tr>\n  <tr>\n    <td style=\"padding:0 24px 20px;\">\n      <p style=\"margin:0 0 6px;font-size:12px;color:#697671;text-transform:uppercase;letter-spacing:1px;\">สถานะปัจจุบัน</p>\n      <span style=\"display:inline-block;padding:6px 14px;background:#edf0ef;border-radius:20px;font-size:14px;font-weight:600;color:#19663f;\">มีสิทธิ์สัมภาษณ์ 🎉</span>\n    </td>\n  </tr>\n</table>\n\n<div style=\"text-align:center;margin:28px 0;\">\n  <a href=\"http://localhost/Flex/worker/application-detail.php?id=8\" style=\"display:inline-block;padding:14px 32px;background:#0052cc;color:#ffffff;text-decoration:none;border-radius:10px;font-size:15px;font-weight:600;letter-spacing:-0.3px;\">ดูรายละเอียดการสมัคร</a>\n</div>\n\n<p style=\"font-size:13px;color:#697671;margin-top:8px;\">หากมีข้อสงสัย สามารถติดต่อผู้ว่าจ้างได้โดยตรงผ่านข้อมูลติดต่อในหน้ารายละเอียดการสมัคร</p>', NULL, '', 'queued', NULL, 0, '2026-09-04 12:42:22', NULL, NULL),
(52, 'rapeepat.wo02@gmail.com', 'Rapeepat Wongsuwan', 'อัปเดตสถานะใบสมัคร: พนังงานขายสินค้า Powerbuy — fluk Software company', '<h2 style=\"margin:0 0 8px;font-size:24px;color:#17231f;letter-spacing:-1px;\">สวัสดี, Rapeepat Wongsuwan</h2>\n<p style=\"margin:0 0 24px;color:#697671;font-size:15px;\">มีการอัปเดตสถานะใบสมัครของคุณ</p>\n\n<table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" style=\"background:#f7f8f4;border-radius:12px;overflow:hidden;margin-bottom:24px;\">\n  <tr>\n    <td style=\"padding:20px 24px;\">\n      <p style=\"margin:0 0 6px;font-size:12px;color:#697671;text-transform:uppercase;letter-spacing:1px;\">ตำแหน่งงาน</p>\n      <p style=\"margin:0;font-size:18px;font-weight:700;color:#17231f;\">พนังงานขายสินค้า Powerbuy</p>\n      <p style=\"margin:4px 0 0;font-size:14px;color:#697671;\">fluk Software company</p>\n    </td>\n  </tr>\n  <tr>\n    <td style=\"padding:0 24px 20px;\">\n      <p style=\"margin:0 0 6px;font-size:12px;color:#697671;text-transform:uppercase;letter-spacing:1px;\">สถานะปัจจุบัน</p>\n      <span style=\"display:inline-block;padding:6px 14px;background:#edf0ef;border-radius:20px;font-size:14px;font-weight:600;color:#8a6100;\">รอพิจารณา</span>\n    </td>\n  </tr>\n</table>\n\n\n\n<p style=\"font-size:13px;color:#697671;margin-top:8px;\">หากมีข้อสงสัย สามารถติดต่อผู้ว่าจ้างได้โดยตรงผ่านข้อมูลติดต่อในหน้ารายละเอียดการสมัคร</p>', NULL, '', 'queued', NULL, 0, '2026-09-09 23:39:09', NULL, NULL),
(53, 'rapeepat.wo02@gmail.com', 'Rapeepat Wongsuwan', 'อัปเดตสถานะใบสมัคร: พนังงานขายสินค้า Powerbuy — fluk Software company', '<h2 style=\"margin:0 0 8px;font-size:24px;color:#17231f;letter-spacing:-1px;\">สวัสดี, Rapeepat Wongsuwan</h2>\n<p style=\"margin:0 0 24px;color:#697671;font-size:15px;\">มีการอัปเดตสถานะใบสมัครของคุณ</p>\n\n<table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" style=\"background:#f7f8f4;border-radius:12px;overflow:hidden;margin-bottom:24px;\">\n  <tr>\n    <td style=\"padding:20px 24px;\">\n      <p style=\"margin:0 0 6px;font-size:12px;color:#697671;text-transform:uppercase;letter-spacing:1px;\">ตำแหน่งงาน</p>\n      <p style=\"margin:0;font-size:18px;font-weight:700;color:#17231f;\">พนังงานขายสินค้า Powerbuy</p>\n      <p style=\"margin:4px 0 0;font-size:14px;color:#697671;\">fluk Software company</p>\n    </td>\n  </tr>\n  <tr>\n    <td style=\"padding:0 24px 20px;\">\n      <p style=\"margin:0 0 6px;font-size:12px;color:#697671;text-transform:uppercase;letter-spacing:1px;\">สถานะปัจจุบัน</p>\n      <span style=\"display:inline-block;padding:6px 14px;background:#edf0ef;border-radius:20px;font-size:14px;font-weight:600;color:#19663f;\">มีสิทธิ์สัมภาษณ์ 🎉</span>\n    </td>\n  </tr>\n</table>\n\n<div style=\"text-align:center;margin:28px 0;\">\n  <a href=\"http://localhost/Flex/worker/application-detail.php?id=8\" style=\"display:inline-block;padding:14px 32px;background:#0052cc;color:#ffffff;text-decoration:none;border-radius:10px;font-size:15px;font-weight:600;letter-spacing:-0.3px;\">ดูรายละเอียดการสมัคร</a>\n</div>\n\n<p style=\"font-size:13px;color:#697671;margin-top:8px;\">หากมีข้อสงสัย สามารถติดต่อผู้ว่าจ้างได้โดยตรงผ่านข้อมูลติดต่อในหน้ารายละเอียดการสมัคร</p>', NULL, '', 'queued', NULL, 0, '2026-09-10 10:43:05', NULL, NULL),
(54, 'rapeepat.wo02@gmail.com', 'Rapeepat Wongsuwan', 'อัปเดตสถานะใบสมัคร: พนังงานขายสินค้า Powerbuy — fluk Software company', '<h2 style=\"margin:0 0 8px;font-size:24px;color:#17231f;letter-spacing:-1px;\">สวัสดี, Rapeepat Wongsuwan</h2>\n<p style=\"margin:0 0 24px;color:#697671;font-size:15px;\">มีการอัปเดตสถานะใบสมัครของคุณ</p>\n\n<table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" style=\"background:#f7f8f4;border-radius:12px;overflow:hidden;margin-bottom:24px;\">\n  <tr>\n    <td style=\"padding:20px 24px;\">\n      <p style=\"margin:0 0 6px;font-size:12px;color:#697671;text-transform:uppercase;letter-spacing:1px;\">ตำแหน่งงาน</p>\n      <p style=\"margin:0;font-size:18px;font-weight:700;color:#17231f;\">พนังงานขายสินค้า Powerbuy</p>\n      <p style=\"margin:4px 0 0;font-size:14px;color:#697671;\">fluk Software company</p>\n    </td>\n  </tr>\n  <tr>\n    <td style=\"padding:0 24px 20px;\">\n      <p style=\"margin:0 0 6px;font-size:12px;color:#697671;text-transform:uppercase;letter-spacing:1px;\">สถานะปัจจุบัน</p>\n      <span style=\"display:inline-block;padding:6px 14px;background:#edf0ef;border-radius:20px;font-size:14px;font-weight:600;color:#8a6100;\">รอพิจารณา</span>\n    </td>\n  </tr>\n</table>\n\n\n\n<p style=\"font-size:13px;color:#697671;margin-top:8px;\">หากมีข้อสงสัย สามารถติดต่อผู้ว่าจ้างได้โดยตรงผ่านข้อมูลติดต่อในหน้ารายละเอียดการสมัคร</p>', NULL, '', 'queued', NULL, 0, '2026-09-10 10:43:13', NULL, NULL),
(55, 'rapeepat.wo02@gmail.com', 'Rapeepat Wongsuwan', 'อัปเดตสถานะใบสมัคร: พนังงานขายสินค้า Powerbuy — fluk Software company', '<h2 style=\"margin:0 0 8px;font-size:24px;color:#17231f;letter-spacing:-1px;\">สวัสดี, Rapeepat Wongsuwan</h2>\n<p style=\"margin:0 0 24px;color:#697671;font-size:15px;\">มีการอัปเดตสถานะใบสมัครของคุณ</p>\n\n<table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" style=\"background:#f7f8f4;border-radius:12px;overflow:hidden;margin-bottom:24px;\">\n  <tr>\n    <td style=\"padding:20px 24px;\">\n      <p style=\"margin:0 0 6px;font-size:12px;color:#697671;text-transform:uppercase;letter-spacing:1px;\">ตำแหน่งงาน</p>\n      <p style=\"margin:0;font-size:18px;font-weight:700;color:#17231f;\">พนังงานขายสินค้า Powerbuy</p>\n      <p style=\"margin:4px 0 0;font-size:14px;color:#697671;\">fluk Software company</p>\n    </td>\n  </tr>\n  <tr>\n    <td style=\"padding:0 24px 20px;\">\n      <p style=\"margin:0 0 6px;font-size:12px;color:#697671;text-transform:uppercase;letter-spacing:1px;\">สถานะปัจจุบัน</p>\n      <span style=\"display:inline-block;padding:6px 14px;background:#edf0ef;border-radius:20px;font-size:14px;font-weight:600;color:#19663f;\">มีสิทธิ์สัมภาษณ์ 🎉</span>\n    </td>\n  </tr>\n</table>\n\n<div style=\"text-align:center;margin:28px 0;\">\n  <a href=\"http://localhost/Flex/worker/application-detail.php?id=8\" style=\"display:inline-block;padding:14px 32px;background:#0052cc;color:#ffffff;text-decoration:none;border-radius:10px;font-size:15px;font-weight:600;letter-spacing:-0.3px;\">ดูรายละเอียดการสมัคร</a>\n</div>\n\n<p style=\"font-size:13px;color:#697671;margin-top:8px;\">หากมีข้อสงสัย สามารถติดต่อผู้ว่าจ้างได้โดยตรงผ่านข้อมูลติดต่อในหน้ารายละเอียดการสมัคร</p>', NULL, '', 'queued', NULL, 0, '2026-09-10 12:04:08', NULL, NULL),
(56, 'frk24072561@gmail.com', 'Rapeepat Wongsuwan', 'มีผู้สมัครงานใหม่: ช่างภาพ', '<h2 style=\"margin:0 0 8px;font-size:24px;color:#17231f;letter-spacing:-1px;\">มีผู้สมัครงานใหม่! 🎯</h2>\n<p style=\"margin:0 0 24px;color:#697671;font-size:15px;\">สวัสดี Rapeepat Wongsuwan — มีคนสมัครงานของคุณ</p>\n\n<table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" style=\"background:#f7f8f4;border-radius:12px;margin-bottom:20px;\">\n  <tr><td style=\"padding:20px 24px;\">\n    <p style=\"margin:0 0 4px;font-size:12px;color:#697671;text-transform:uppercase;letter-spacing:1px;\">ตำแหน่งงาน</p>\n    <p style=\"margin:0;font-size:18px;font-weight:700;color:#17231f;\">ช่างภาพ</p>\n  </td></tr>\n</table>\n\n<table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" style=\"border:1px solid #e0e8e3;border-radius:12px;overflow:hidden;margin-bottom:20px;\">\n  <tr style=\"background:#166b54;\">\n    <td colspan=\"2\" style=\"padding:14px 20px;\">\n      <div style=\"display:inline-block;width:36px;height:36px;border-radius:50%;background:#d7f56d;text-align:center;line-height:36px;font-weight:700;font-size:16px;color:#166b54;float:left;margin-right:12px;\">\n        R\n      </div>\n      <span style=\"color:#ffffff;font-size:16px;font-weight:600;line-height:36px;\">Rapeepat Wongsuwan</span>\n    </td>\n  </tr>\n  <tr>\n    <td style=\"padding:12px 20px;font-size:13px;color:#697671;border-bottom:1px solid #e0e8e3;\">📧 อีเมล</td>\n    <td style=\"padding:12px 20px;font-size:13px;font-weight:600;border-bottom:1px solid #e0e8e3;\">rapeepat.wo02@gmail.com</td>\n  </tr>\n  <tr>\n    <td style=\"padding:12px 20px;font-size:13px;color:#697671;\">📞 โทรศัพท์</td>\n    <td style=\"padding:12px 20px;font-size:13px;font-weight:600;\">0919876782</td>\n  </tr>\n</table>\n\n<div style=\"text-align:center;margin:28px 0;\">\n  <a href=\"http://localhost/Flex/employer/applicants.php?job=54\" style=\"display:inline-block;padding:14px 32px;background:#0052cc;color:#ffffff;text-decoration:none;border-radius:10px;font-size:15px;font-weight:600;letter-spacing:-0.3px;\">ดูผู้สมัครทั้งหมด →</a>\n</div>', NULL, '', 'queued', NULL, 0, '2026-09-12 17:44:36', NULL, NULL),
(57, 'rapeepat.wo02@gmail.com', 'Rapeepat Wongsuwan', 'อัปเดตสถานะใบสมัคร: ช่างภาพ — fluk Software company', '<h2 style=\"margin:0 0 8px;font-size:24px;color:#17231f;letter-spacing:-1px;\">สวัสดี, Rapeepat Wongsuwan</h2>\n<p style=\"margin:0 0 24px;color:#697671;font-size:15px;\">มีการอัปเดตสถานะใบสมัครของคุณ</p>\n\n<table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" style=\"background:#f7f8f4;border-radius:12px;overflow:hidden;margin-bottom:24px;\">\n  <tr>\n    <td style=\"padding:20px 24px;\">\n      <p style=\"margin:0 0 6px;font-size:12px;color:#697671;text-transform:uppercase;letter-spacing:1px;\">ตำแหน่งงาน</p>\n      <p style=\"margin:0;font-size:18px;font-weight:700;color:#17231f;\">ช่างภาพ</p>\n      <p style=\"margin:4px 0 0;font-size:14px;color:#697671;\">fluk Software company</p>\n    </td>\n  </tr>\n  <tr>\n    <td style=\"padding:0 24px 20px;\">\n      <p style=\"margin:0 0 6px;font-size:12px;color:#697671;text-transform:uppercase;letter-spacing:1px;\">สถานะปัจจุบัน</p>\n      <span style=\"display:inline-block;padding:6px 14px;background:#edf0ef;border-radius:20px;font-size:14px;font-weight:600;color:#19663f;\">มีสิทธิ์สัมภาษณ์ 🎉</span>\n    </td>\n  </tr>\n</table>\n\n<div style=\"text-align:center;margin:28px 0;\">\n  <a href=\"http://localhost/Flex/worker/application-detail.php?id=9\" style=\"display:inline-block;padding:14px 32px;background:#0052cc;color:#ffffff;text-decoration:none;border-radius:10px;font-size:15px;font-weight:600;letter-spacing:-0.3px;\">ดูรายละเอียดการสมัคร</a>\n</div>\n\n<p style=\"font-size:13px;color:#697671;margin-top:8px;\">หากมีข้อสงสัย สามารถติดต่อผู้ว่าจ้างได้โดยตรงผ่านข้อมูลติดต่อในหน้ารายละเอียดการสมัคร</p>', NULL, '', 'queued', NULL, 0, '2026-09-12 17:44:46', NULL, NULL),
(58, 'rapeepat.wo02@gmail.com', 'Rapeepat Wongsuwan', 'อัปเดตสถานะใบสมัคร: ช่างภาพ — fluk Software company', '<h2 style=\"margin:0 0 8px;font-size:24px;color:#17231f;letter-spacing:-1px;\">สวัสดี, Rapeepat Wongsuwan</h2>\n<p style=\"margin:0 0 24px;color:#697671;font-size:15px;\">มีการอัปเดตสถานะใบสมัครของคุณ</p>\n\n<table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" style=\"background:#f7f8f4;border-radius:12px;overflow:hidden;margin-bottom:24px;\">\n  <tr>\n    <td style=\"padding:20px 24px;\">\n      <p style=\"margin:0 0 6px;font-size:12px;color:#697671;text-transform:uppercase;letter-spacing:1px;\">ตำแหน่งงาน</p>\n      <p style=\"margin:0;font-size:18px;font-weight:700;color:#17231f;\">ช่างภาพ</p>\n      <p style=\"margin:4px 0 0;font-size:14px;color:#697671;\">fluk Software company</p>\n    </td>\n  </tr>\n  <tr>\n    <td style=\"padding:0 24px 20px;\">\n      <p style=\"margin:0 0 6px;font-size:12px;color:#697671;text-transform:uppercase;letter-spacing:1px;\">สถานะปัจจุบัน</p>\n      <span style=\"display:inline-block;padding:6px 14px;background:#edf0ef;border-radius:20px;font-size:14px;font-weight:600;color:#047857;\">ผ่านสัมภาษณ์แล้ว 🎉</span>\n    </td>\n  </tr>\n</table>\n\n<div style=\"text-align:center;margin:28px 0;\">\n  <a href=\"http://localhost/Flex/worker/application-detail.php?id=9\" style=\"display:inline-block;padding:14px 32px;background:#0052cc;color:#ffffff;text-decoration:none;border-radius:10px;font-size:15px;font-weight:600;letter-spacing:-0.3px;\">ดูรายละเอียดการสมัคร</a>\n</div>\n\n<p style=\"font-size:13px;color:#697671;margin-top:8px;\">หากมีข้อสงสัย สามารถติดต่อผู้ว่าจ้างได้โดยตรงผ่านข้อมูลติดต่อในหน้ารายละเอียดการสมัคร</p>', NULL, '', 'queued', NULL, 0, '2026-09-12 17:44:55', NULL, NULL),
(59, 'rapeepat.wo02@gmail.com', 'Rapeepat Wongsuwan', 'อัปเดตสถานะใบสมัคร: ช่างภาพ — fluk Software company', '<h2 style=\"margin:0 0 8px;font-size:24px;color:#17231f;letter-spacing:-1px;\">สวัสดี, Rapeepat Wongsuwan</h2>\n<p style=\"margin:0 0 24px;color:#697671;font-size:15px;\">มีการอัปเดตสถานะใบสมัครของคุณ</p>\n\n<table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" style=\"background:#f7f8f4;border-radius:12px;overflow:hidden;margin-bottom:24px;\">\n  <tr>\n    <td style=\"padding:20px 24px;\">\n      <p style=\"margin:0 0 6px;font-size:12px;color:#697671;text-transform:uppercase;letter-spacing:1px;\">ตำแหน่งงาน</p>\n      <p style=\"margin:0;font-size:18px;font-weight:700;color:#17231f;\">ช่างภาพ</p>\n      <p style=\"margin:4px 0 0;font-size:14px;color:#697671;\">fluk Software company</p>\n    </td>\n  </tr>\n  <tr>\n    <td style=\"padding:0 24px 20px;\">\n      <p style=\"margin:0 0 6px;font-size:12px;color:#697671;text-transform:uppercase;letter-spacing:1px;\">สถานะปัจจุบัน</p>\n      <span style=\"display:inline-block;padding:6px 14px;background:#edf0ef;border-radius:20px;font-size:14px;font-weight:600;color:#0052cc;\">งานเสร็จสิ้น — ให้คะแนนได้</span>\n    </td>\n  </tr>\n</table>\n\n<div style=\"text-align:center;margin:28px 0;\">\n  <a href=\"http://localhost/Flex/worker/application-detail.php?id=9\" style=\"display:inline-block;padding:14px 32px;background:#0052cc;color:#ffffff;text-decoration:none;border-radius:10px;font-size:15px;font-weight:600;letter-spacing:-0.3px;\">ดูรายละเอียดการสมัคร</a>\n</div>\n\n<p style=\"font-size:13px;color:#697671;margin-top:8px;\">หากมีข้อสงสัย สามารถติดต่อผู้ว่าจ้างได้โดยตรงผ่านข้อมูลติดต่อในหน้ารายละเอียดการสมัคร</p>', NULL, '', 'queued', NULL, 0, '2026-09-12 17:45:02', NULL, NULL);
INSERT INTO `email_log` (`id`, `to_email`, `to_name`, `subject`, `html_body`, `reply_to_email`, `reply_to_name`, `status`, `error_msg`, `attempts`, `available_at`, `locked_at`, `sent_at`) VALUES
(60, 'bankchannel010@gmail.com', 'Wasuphon Mahawong', 'อัปเดตสถานะใบสมัคร: คนยืนบูธงาน MotoGP — fluk Software company', '<h2 style=\"margin:0 0 8px;font-size:24px;color:#17231f;letter-spacing:-1px;\">สวัสดี, Wasuphon Mahawong</h2>\n<p style=\"margin:0 0 24px;color:#697671;font-size:15px;\">มีการอัปเดตสถานะใบสมัครของคุณ</p>\n\n<table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" style=\"background:#f7f8f4;border-radius:12px;overflow:hidden;margin-bottom:24px;\">\n  <tr>\n    <td style=\"padding:20px 24px;\">\n      <p style=\"margin:0 0 6px;font-size:12px;color:#697671;text-transform:uppercase;letter-spacing:1px;\">ตำแหน่งงาน</p>\n      <p style=\"margin:0;font-size:18px;font-weight:700;color:#17231f;\">คนยืนบูธงาน MotoGP</p>\n      <p style=\"margin:4px 0 0;font-size:14px;color:#697671;\">fluk Software company</p>\n    </td>\n  </tr>\n  <tr>\n    <td style=\"padding:0 24px 20px;\">\n      <p style=\"margin:0 0 6px;font-size:12px;color:#697671;text-transform:uppercase;letter-spacing:1px;\">สถานะปัจจุบัน</p>\n      <span style=\"display:inline-block;padding:6px 14px;background:#edf0ef;border-radius:20px;font-size:14px;font-weight:600;color:#bd4d3d;\">ไม่ผ่านการคัดเลือก</span>\n    </td>\n  </tr>\n</table>\n\n<div style=\"text-align:center;margin:28px 0;\">\n  <a href=\"http://localhost/Flex/worker/application-detail.php?id=5\" style=\"display:inline-block;padding:14px 32px;background:#0052cc;color:#ffffff;text-decoration:none;border-radius:10px;font-size:15px;font-weight:600;letter-spacing:-0.3px;\">ดูรายละเอียดการสมัคร</a>\n</div>\n\n<p style=\"font-size:13px;color:#697671;margin-top:8px;\">หากมีข้อสงสัย สามารถติดต่อผู้ว่าจ้างได้โดยตรงผ่านข้อมูลติดต่อในหน้ารายละเอียดการสมัคร</p>', NULL, '', 'queued', NULL, 0, '2026-09-12 17:53:40', NULL, NULL);

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
(3, 12, 'uploads/verification/272a4c56f1500f402fea.jpg', 'approved', NULL, 1, '2026-08-22 19:47:08', '2026-08-22 12:45:37'),
(4, 2, 'uploads/verification/58bf2beac292b91b95c5.pdf', 'approved', NULL, 1, '2026-08-26 21:26:46', '2026-08-26 14:26:27');

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
(1, 2, 'Spark Event Studio', 'ทีมสร้างสรรค์งานอีเวนต์และแบรนด์แอคติเวชัน', '444 หมู่ 15 ตำบลอิสาณ อำเภอเมืองบุรีรัมย์ จังหวัดบุรีรัมย์ 31000', 'assets/images/spark-event-logo.svg'),
(2, 3, 'KIND Coffee', 'ร้านกาแฟสเปเชียลตี้สำหรับคนรักกาแฟ', '99/9 ถนนจิระ ตำบลในเมือง อำเภอเมืองบุรีรัมย์ จังหวัดบุรีรัมย์ 31000', 'assets/images/kind-coffee-logo.svg'),
(3, 4, 'Morrow Creative', 'สตูดิโอครีเอทีฟและคอนเทนต์ดิจิทัล', '156 ถนนรมย์บุรี ตำบลในเมือง อำเภอเมืองบุรีรัมย์ จังหวัดบุรีรัมย์ 31000', 'assets/images/morrow-creative-logo.svg'),
(4, 6, 'abc company', NULL, NULL, NULL),
(5, 12, 'fluk Software company', 'บริษัท Software เขียนเว็บไซต์', '111 ถนน AA ตำบล AB อำเภอ AC จังหวัด บุรีรัมย์ 31000', 'uploads/company-logos/2620be0d2178a715e28d.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `employer_subscriptions`
--

CREATE TABLE `employer_subscriptions` (
  `subscription_id` int(10) UNSIGNED NOT NULL,
  `employer_user_id` int(10) UNSIGNED NOT NULL,
  `plan_id` int(10) UNSIGNED NOT NULL,
  `plan_name_snapshot` varchar(120) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `duration_days` smallint(5) UNSIGNED NOT NULL,
  `active_job_limit` smallint(5) UNSIGNED NOT NULL,
  `promotion_credits` smallint(5) UNSIGNED NOT NULL,
  `promotion_duration_days` smallint(5) UNSIGNED NOT NULL,
  `subscription_status` enum('pending_payment','pending_verification','active','rejected','expired','cancelled') NOT NULL DEFAULT 'pending_payment',
  `payment_slip_path` varchar(255) DEFAULT NULL,
  `payment_reference` varchar(120) DEFAULT NULL,
  `payment_submitted_at` datetime DEFAULT NULL,
  `reviewed_by_user_id` int(10) UNSIGNED DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `review_note` varchar(1000) DEFAULT NULL,
  `starts_at` datetime DEFAULT NULL,
  `ends_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employer_subscriptions`
--

INSERT INTO `employer_subscriptions` (`subscription_id`, `employer_user_id`, `plan_id`, `plan_name_snapshot`, `amount`, `duration_days`, `active_job_limit`, `promotion_credits`, `promotion_duration_days`, `subscription_status`, `payment_slip_path`, `payment_reference`, `payment_submitted_at`, `reviewed_by_user_id`, `reviewed_at`, `review_note`, `starts_at`, `ends_at`, `created_at`, `updated_at`) VALUES
(5, 12, 1, 'Pro 30 วัน', 239.00, 30, 6, 2, 7, 'cancelled', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-09 16:38:37', '2026-09-12 10:04:37'),
(9, 12, 1, 'Pro 30 วัน', 239.00, 30, 6, 2, 7, 'active', 'uploads/payment-slips/45deba661ff28a459c31.jpg', NULL, '2026-09-12 17:04:43', 1, '2026-09-12 17:04:58', NULL, '2026-09-12 12:04:58', '2026-10-12 12:04:58', '2026-09-12 10:04:37', '2026-09-12 10:04:58');

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `job_id` int(10) UNSIGNED NOT NULL,
  `employer_user_id` int(10) UNSIGNED NOT NULL,
  `job_category_id` int(10) UNSIGNED NOT NULL,
  `work_interest_id` int(10) UNSIGNED DEFAULT NULL,
  `job_title` varchar(180) NOT NULL,
  `job_description` text NOT NULL,
  `work_location` varchar(180) NOT NULL,
  `work_province` varchar(100) DEFAULT NULL,
  `work_schedule` varchar(180) DEFAULT NULL,
  `work_start_date` date DEFAULT NULL,
  `work_end_date` date DEFAULT NULL,
  `work_start_time` time DEFAULT NULL,
  `work_end_time` time DEFAULT NULL,
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

INSERT INTO `jobs` (`job_id`, `employer_user_id`, `job_category_id`, `work_interest_id`, `job_title`, `job_description`, `work_location`, `work_province`, `work_schedule`, `work_start_date`, `work_end_date`, `work_start_time`, `work_end_time`, `work_mode`, `application_deadline`, `pay_amount`, `pay_unit`, `open_positions`, `job_status`, `created_at`, `updated_at`) VALUES
(21, 2, 2, NULL, 'Event Staff งานเปิดตัวสินค้า', 'ดูแลจุดลงทะเบียน ให้ข้อมูลผู้ร่วมงาน และช่วยประสานงานหน้างาน', 'สนามช้าง อินเตอร์เนชั่นแนล เซอร์กิต, 444 หมู่ 15 ตำบลอิสาณ อำเภอเมืองบุรีรัมย์ จังหวัดบุรีรัมย์ 31000', 'บุรีรัมย์', '23–24 ส.ค. 2026 เวลา 09:00–18:00', NULL, NULL, NULL, NULL, 'onsite', '2026-10-09', 900.00, 'day', 8, 'published', '2026-08-18 13:15:41', '2026-09-09 12:31:08'),
(22, 3, 1, NULL, 'พนักงานพาร์ทไทม์ ร้านกาแฟ', 'รับออเดอร์ เตรียมเครื่องดื่ม และดูแลความเรียบร้อยภายในร้าน', 'KIND Coffee, 99/9 ถนนจิระ ตำบลในเมือง อำเภอเมืองบุรีรัมย์ จังหวัดบุรีรัมย์ 31000', 'บุรีรัมย์', 'เลือกกะทำงานได้', NULL, NULL, NULL, NULL, 'onsite', '2026-10-09', 70.00, 'hour', 2, 'hidden', '2026-08-18 13:15:41', '2026-09-10 05:04:02'),
(23, 4, 3, NULL, 'Graphic Designer (Freelance)', 'ออกแบบสื่อ Social Media สำหรับแคมเปญ จำนวน 10 ชิ้นต่อโปรเจกต์', 'ทำงานออนไลน์ (สำนักงาน Morrow Creative, ตำบลในเมือง อำเภอเมืองบุรีรัมย์ จังหวัดบุรีรัมย์ 31000)', 'บุรีรัมย์', 'ปิดรับ 30 ส.ค. 2026', NULL, NULL, NULL, NULL, 'remote', '2026-10-09', 3500.00, 'project', 1, 'published', '2026-08-18 13:15:41', '2026-09-09 12:31:08'),
(24, 2, 2, NULL, 'ทีมลงทะเบียนงานวิ่งการกุศล', 'ต้อนรับผู้ร่วมงาน แจกเบอร์วิ่ง และช่วยดูแลจุดลงทะเบียน', 'สวนรมย์บุรี ตำบลในเมือง อำเภอเมืองบุรีรัมย์ จังหวัดบุรีรัมย์ 31000', 'บุรีรัมย์', '31 ส.ค. 2026 เวลา 04:30–10:00', NULL, NULL, NULL, NULL, 'onsite', '2026-10-09', 850.00, 'day', 12, 'published', '2026-08-18 13:15:41', '2026-09-09 12:31:08'),
(25, 3, 1, NULL, 'Barista พาร์ทไทม์ วันเสาร์-อาทิตย์', 'ชงกาแฟ รับออเดอร์ และดูแลความเรียบร้อยหน้าร้าน มีการสอนงาน', 'KIND Coffee, 99/9 ถนนจิระ ตำบลในเมือง อำเภอเมืองบุรีรัมย์ จังหวัดบุรีรัมย์ 31000', 'บุรีรัมย์', 'เสาร์–อาทิตย์ 08:00–17:00', NULL, NULL, NULL, NULL, 'onsite', '2026-10-09', 75.00, 'hour', 2, 'published', '2026-08-18 13:15:41', '2026-09-09 12:31:08'),
(26, 4, 3, NULL, 'ช่างภาพงานอีเวนต์', 'ถ่ายภาพบรรยากาศและกิจกรรมภายในงาน พร้อมคัดเลือกภาพส่งหลังจบงาน', 'สนามช้าง อินเตอร์เนชั่นแนล เซอร์กิต, 444 หมู่ 15 ตำบลอิสาณ อำเภอเมืองบุรีรัมย์ จังหวัดบุรีรัมย์ 31000', 'บุรีรัมย์', '6 ก.ย. 2026', NULL, NULL, NULL, NULL, 'onsite', '2026-10-09', 4500.00, 'project', 1, 'published', '2026-08-18 13:15:41', '2026-09-09 12:31:08'),
(27, 3, 1, NULL, 'แอดมินตอบแชต (Work from Home)', 'ตอบคำถามลูกค้าและประสานงานทีมขาย มีคู่มือข้อความให้', 'ทำงานออนไลน์ (สำนักงานใหญ่ KIND Coffee, ตำบลในเมือง อำเภอเมืองบุรีรัมย์ จังหวัดบุรีรัมย์ 31000)', 'บุรีรัมย์', 'จ.–ศ. 10:00–18:00', NULL, NULL, NULL, NULL, 'remote', '2026-10-09', 700.00, 'day', 3, 'published', '2026-08-18 13:15:41', '2026-09-09 12:31:08'),
(28, 2, 2, NULL, 'Staff แจกสินค้าตัวอย่าง', 'แจกสินค้าตัวอย่างและเชิญชวนผู้ร่วมงานเข้าร่วมกิจกรรมแบรนด์', 'ทวีกิจซูเปอร์เซ็นเตอร์ ถนนจิระ ตำบลในเมือง อำเภอเมืองบุรีรัมย์ จังหวัดบุรีรัมย์ 31000', 'บุรีรัมย์', '12–14 ก.ย. 2026', NULL, NULL, NULL, NULL, 'onsite', '2026-10-09', 950.00, 'day', 6, 'published', '2026-08-18 13:15:41', '2026-09-09 12:31:08'),
(29, 4, 3, NULL, 'Content Creator สำหรับ TikTok', 'คิดคอนเทนต์ ถ่าย และตัดต่อวิดีโอ TikTok จำนวน 5 คลิป', 'ทำงานออนไลน์ (สำนักงาน Morrow Creative, ตำบลในเมือง อำเภอเมืองบุรีรัมย์ จังหวัดบุรีรัมย์ 31000)', 'บุรีรัมย์', 'ส่งงานภายใน 14 วัน', NULL, NULL, NULL, NULL, 'remote', '2026-10-09', 6000.00, 'project', 1, 'published', '2026-08-18 13:15:41', '2026-09-09 12:31:08'),
(30, 3, 1, NULL, 'พนักงานเสิร์ฟงานเลี้ยง', 'เสิร์ฟอาหารและเครื่องดื่ม ช่วยจัดโต๊ะ และดูแลความเรียบร้อยในงาน', 'โรงแรมเทพนคร ถนนจิระ ตำบลในเมือง อำเภอเมืองบุรีรัมย์ จังหวัดบุรีรัมย์ 31000', 'บุรีรัมย์', '20 ก.ย. 2026 เวลา 16:00–23:00', NULL, NULL, NULL, NULL, 'onsite', '2026-10-09', 800.00, 'day', 5, 'published', '2026-08-18 13:15:41', '2026-09-09 12:31:08'),
(32, 12, 3, NULL, 'นักเขียนโปรแกรม', 'นักเขียนโปรแกรม เว็บไซต์บริษัท', '134 ถนนจิระ ตำบลในเมือง อำเภอเมืองบุรีรัมย์ จังหวัดบุรีรัมย์ 31000', 'บุรีรัมย์', '10:00 - 18:00', NULL, NULL, NULL, NULL, 'onsite', '2026-08-30', 350.00, 'day', 1, 'published', '2026-08-22 12:50:56', '2026-09-03 20:09:24'),
(33, 12, 3, NULL, 'ออกแบบ UX / UI', 'ออกแบบ UX / UI สำหรับเว็บไซต์ขายรถยนต์', 'ทำงานออนไลน์ (สำนักงาน 134 ถนนจิระ ตำบลในเมือง อำเภอเมืองบุรีรัมย์ จังหวัดบุรีรัมย์ 31000)', 'บุรีรัมย์', '10:00 - 18:00', NULL, NULL, NULL, NULL, 'remote', '2026-08-31', 5000.00, 'project', 1, 'published', '2026-08-25 07:02:23', '2026-09-03 20:09:24'),
(34, 12, 2, NULL, 'คนยืนบูธงาน MotoGP', 'ยืนบูธงาน MotoGP รับลูกค้า', 'สนามช้าง อินเตอร์เนชั่นแนล เซอร์กิต, 444 หมู่ 15 ตำบลอิสาณ อำเภอเมืองบุรีรัมย์ จังหวัดบุรีรัมย์ 31000', 'บุรีรัมย์', '10:00 - 18:00', NULL, NULL, NULL, NULL, 'onsite', '2026-08-31', 500.00, 'day', 3, 'hidden', '2026-08-25 08:28:47', '2026-09-12 10:51:04'),
(37, 2, 2, 7, 'งานเปิดตัวสินค้า', 'ยืนบูสแนะนำสินค้า เชิญชวนลูกค้า', 'Robinson125 ม.6 ถนน บุรีรัมย์ - นางรอง ตำบล อิสาณ อำเภอเมืองบุรีรัมย์ บุรีรัมย์ 31000', 'บุรีรัมย์', '05/09/2026 – 06/09/2026 · 10:00–20:00 น.', '2026-09-05', '2026-09-06', '10:00:00', '20:00:00', 'onsite', '2026-09-05', 500.00, 'day', 1, 'published', '2026-09-04 03:42:29', '2026-09-04 03:42:29'),
(38, 12, 3, 1, 'เขียนโปรแกรม', 'เขียนโปรแกรม', 'ABC software', 'บุรีรัมย์', '04/09/2026 – 30/09/2026', '2026-09-04', '2026-09-30', NULL, NULL, 'remote', '2026-09-30', 5000.00, 'project', 1, 'published', '2026-09-04 05:02:48', '2026-09-04 05:02:48'),
(39, 12, 1, 8, 'พนังงานขายสินค้า Powerbuy', 'ขายสินค้า Powerbuy ทุกประเภท', 'Powerbuy buriram', 'บุรีรัมย์', '04/09/2026 · 10:00–20:00 น.', '2026-09-04', NULL, '10:00:00', '20:00:00', 'onsite', '2026-09-30', 375.00, 'day', 1, 'published', '2026-09-04 05:07:24', '2026-09-04 05:07:24'),
(54, 12, 2, 5, 'ช่างภาพ', 'ช่างภาพ', 'โรงแรมสโนว์', 'บุรีรัมย์', '12/09/2026 – 20/09/2026 · 10:00–20:00 น.', '2026-09-12', '2026-09-20', '10:00:00', '20:00:00', 'onsite', '2026-10-30', 6000.00, 'day', 3, 'published', '2026-09-12 10:06:59', '2026-09-12 10:52:51');

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
(42, 32, 'uploads/jobs/c849f78fc3757b7f45d4.png', 1),
(43, 34, 'uploads/jobs/4bfd90ef79d3c8eb5960.jpg', 1),
(44, 37, 'uploads/jobs/66e8c56a18323e2935a2.jpg', 1),
(45, 38, 'uploads/jobs/5ed6fc23331695689876.png', 1),
(46, 39, 'uploads/jobs/2ecc97b9f7952f43dd72.jpg', 1);

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
(1, 33, 7, 'เชิญมาสมัคร', 'accepted', '2026-08-25 07:03:12', '2026-08-25 07:03:29'),
(2, 21, 15, NULL, 'viewed', '2026-09-04 03:09:53', NULL),
(3, 37, 15, NULL, 'viewed', '2026-09-04 04:01:23', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `job_promotions`
--

CREATE TABLE `job_promotions` (
  `promotion_id` int(10) UNSIGNED NOT NULL,
  `job_id` int(10) UNSIGNED NOT NULL,
  `employer_user_id` int(10) UNSIGNED NOT NULL,
  `subscription_id` int(10) UNSIGNED NOT NULL,
  `duration_days` smallint(5) UNSIGNED NOT NULL,
  `promotion_status` enum('active','expired','cancelled') NOT NULL DEFAULT 'active',
  `starts_at` datetime NOT NULL,
  `ends_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(26, 98, 'required'),
(54, 98, 'required'),
(27, 158, 'required'),
(30, 158, 'required'),
(39, 158, 'required'),
(34, 159, 'required'),
(22, 160, 'required'),
(25, 160, 'required'),
(30, 160, 'required'),
(22, 161, 'required'),
(25, 161, 'required'),
(39, 162, 'required'),
(21, 164, 'required'),
(24, 164, 'required'),
(37, 164, 'required'),
(21, 165, 'required'),
(24, 166, 'required'),
(37, 166, 'required'),
(28, 167, 'required'),
(34, 167, 'required'),
(37, 167, 'required'),
(28, 170, 'required'),
(23, 176, 'required'),
(33, 177, 'required'),
(29, 178, 'required'),
(29, 179, 'required'),
(27, 185, 'required'),
(32, 188, 'required'),
(38, 188, 'required'),
(32, 189, 'required'),
(38, 191, 'required');

-- --------------------------------------------------------

--
-- Table structure for table `job_worker_matches`
--

CREATE TABLE `job_worker_matches` (
  `job_id` int(10) UNSIGNED NOT NULL,
  `worker_user_id` int(10) UNSIGNED NOT NULL,
  `match_score` tinyint(3) UNSIGNED DEFAULT NULL,
  `data_strength` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `match_reasons_json` text NOT NULL,
  `missing_required_json` text NOT NULL,
  `required_skills_json` text NOT NULL,
  `preferred_skills_json` text NOT NULL,
  `calculated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `job_worker_matches`
--

INSERT INTO `job_worker_matches` (`job_id`, `worker_user_id`, `match_score`, `data_strength`, `match_reasons_json`, `missing_required_json`, `required_skills_json`, `preferred_skills_json`, `calculated_at`) VALUES
(21, 5, NULL, 50, '[\"ตรงความสามารถที่จำเป็น 0\\/2\",\"รูปแบบงานตรงกับที่ต้องการ\"]', '[\"ต้อนรับและลงทะเบียน\",\"ประสานงานอีเวนต์\"]', '[\"ต้อนรับและลงทะเบียน\",\"ประสานงานอีเวนต์\"]', '[]', '2026-09-09 12:54:53'),
(21, 7, 0, 60, '[\"ตรงความสามารถที่จำเป็น 0\\/2\"]', '[\"ต้อนรับและลงทะเบียน\",\"ประสานงานอีเวนต์\"]', '[\"ต้อนรับและลงทะเบียน\",\"ประสานงานอีเวนต์\"]', '[]', '2026-09-18 03:56:30'),
(21, 13, 33, 60, '[\"ตรงความสามารถที่จำเป็น 0\\/2\",\"รูปแบบงานตรงกับที่ต้องการ\",\"ตรงรูปแบบการจ้างที่สนใจ\"]', '[\"ต้อนรับและลงทะเบียน\",\"ประสานงานอีเวนต์\"]', '[\"ต้อนรับและลงทะเบียน\",\"ประสานงานอีเวนต์\"]', '[]', '2026-09-04 03:28:31'),
(21, 15, 67, 60, '[\"ตรงความสามารถที่จำเป็น 1\\/2\",\"รูปแบบงานตรงกับที่ต้องการ\",\"ตรงรูปแบบการจ้างที่สนใจ\"]', '[\"ประสานงานอีเวนต์\"]', '[\"ต้อนรับและลงทะเบียน\",\"ประสานงานอีเวนต์\"]', '[]', '2026-09-03 20:12:42'),
(22, 5, NULL, 50, '[\"ตรงความสามารถที่จำเป็น 0\\/2\",\"รูปแบบงานตรงกับที่ต้องการ\"]', '[\"งานอาหารและเครื่องดื่ม\",\"รับออเดอร์และชำระเงิน\"]', '[\"งานอาหารและเครื่องดื่ม\",\"รับออเดอร์และชำระเงิน\"]', '[]', '2026-09-09 12:54:53'),
(22, 13, 33, 60, '[\"ตรงความสามารถที่จำเป็น 0\\/2\",\"รูปแบบงานตรงกับที่ต้องการ\",\"ตรงรูปแบบการจ้างที่สนใจ\"]', '[\"งานอาหารและเครื่องดื่ม\",\"รับออเดอร์และชำระเงิน\"]', '[\"งานอาหารและเครื่องดื่ม\",\"รับออเดอร์และชำระเงิน\"]', '[]', '2026-09-04 03:31:19'),
(22, 15, 33, 60, '[\"ตรงความสามารถที่จำเป็น 0\\/2\",\"รูปแบบงานตรงกับที่ต้องการ\",\"ตรงรูปแบบการจ้างที่สนใจ\"]', '[\"งานอาหารและเครื่องดื่ม\",\"รับออเดอร์และชำระเงิน\"]', '[\"งานอาหารและเครื่องดื่ม\",\"รับออเดอร์และชำระเงิน\"]', '[]', '2026-09-03 20:12:42'),
(23, 5, NULL, 50, '[\"ตรงความสามารถที่จำเป็น 0\\/1\",\"รูปแบบงานตรงกับที่ต้องการ\"]', '[\"ออกแบบกราฟิก\"]', '[\"ออกแบบกราฟิก\"]', '[]', '2026-09-09 12:54:53'),
(23, 13, 100, 60, '[\"ตรงความสามารถที่จำเป็น 1\\/1\",\"รูปแบบงานตรงกับที่ต้องการ\",\"ตรงรูปแบบการจ้างที่สนใจ\"]', '[]', '[\"ออกแบบกราฟิก\"]', '[]', '2026-09-04 03:31:19'),
(23, 15, 33, 60, '[\"ตรงความสามารถที่จำเป็น 0\\/1\",\"รูปแบบงานตรงกับที่ต้องการ\",\"ตรงรูปแบบการจ้างที่สนใจ\"]', '[\"ออกแบบกราฟิก\"]', '[\"ออกแบบกราฟิก\"]', '[]', '2026-09-03 20:12:42'),
(24, 5, NULL, 50, '[\"ตรงความสามารถที่จำเป็น 0\\/2\",\"รูปแบบงานตรงกับที่ต้องการ\"]', '[\"ต้อนรับและลงทะเบียน\",\"ดูแลคิวและกิจกรรม\"]', '[\"ต้อนรับและลงทะเบียน\",\"ดูแลคิวและกิจกรรม\"]', '[]', '2026-09-09 12:54:53'),
(24, 7, 0, 60, '[\"ตรงความสามารถที่จำเป็น 0\\/2\"]', '[\"ต้อนรับและลงทะเบียน\",\"ดูแลคิวและกิจกรรม\"]', '[\"ต้อนรับและลงทะเบียน\",\"ดูแลคิวและกิจกรรม\"]', '[]', '2026-09-18 03:56:30'),
(24, 13, 33, 60, '[\"ตรงความสามารถที่จำเป็น 0\\/2\",\"รูปแบบงานตรงกับที่ต้องการ\",\"ตรงรูปแบบการจ้างที่สนใจ\"]', '[\"ต้อนรับและลงทะเบียน\",\"ดูแลคิวและกิจกรรม\"]', '[\"ต้อนรับและลงทะเบียน\",\"ดูแลคิวและกิจกรรม\"]', '[]', '2026-09-04 03:26:57'),
(24, 15, 100, 60, '[\"ตรงความสามารถที่จำเป็น 2\\/2\",\"รูปแบบงานตรงกับที่ต้องการ\",\"ตรงรูปแบบการจ้างที่สนใจ\"]', '[]', '[\"ต้อนรับและลงทะเบียน\",\"ดูแลคิวและกิจกรรม\"]', '[]', '2026-09-03 20:12:42'),
(25, 5, NULL, 50, '[\"ตรงความสามารถที่จำเป็น 0\\/2\",\"รูปแบบงานตรงกับที่ต้องการ\"]', '[\"งานอาหารและเครื่องดื่ม\",\"รับออเดอร์และชำระเงิน\"]', '[\"งานอาหารและเครื่องดื่ม\",\"รับออเดอร์และชำระเงิน\"]', '[]', '2026-09-09 12:54:53'),
(25, 13, 33, 60, '[\"ตรงความสามารถที่จำเป็น 0\\/2\",\"รูปแบบงานตรงกับที่ต้องการ\",\"ตรงรูปแบบการจ้างที่สนใจ\"]', '[\"งานอาหารและเครื่องดื่ม\",\"รับออเดอร์และชำระเงิน\"]', '[\"งานอาหารและเครื่องดื่ม\",\"รับออเดอร์และชำระเงิน\"]', '[]', '2026-09-04 03:31:19'),
(25, 15, 33, 60, '[\"ตรงความสามารถที่จำเป็น 0\\/2\",\"รูปแบบงานตรงกับที่ต้องการ\",\"ตรงรูปแบบการจ้างที่สนใจ\"]', '[\"งานอาหารและเครื่องดื่ม\",\"รับออเดอร์และชำระเงิน\"]', '[\"งานอาหารและเครื่องดื่ม\",\"รับออเดอร์และชำระเงิน\"]', '[]', '2026-09-03 20:12:42'),
(26, 5, NULL, 50, '[\"ตรงความสามารถที่จำเป็น 0\\/1\",\"รูปแบบงานตรงกับที่ต้องการ\"]', '[\"ถ่ายภาพ\"]', '[\"ถ่ายภาพ\"]', '[]', '2026-09-09 12:54:53'),
(26, 13, 33, 60, '[\"ตรงความสามารถที่จำเป็น 0\\/1\",\"รูปแบบงานตรงกับที่ต้องการ\",\"ตรงรูปแบบการจ้างที่สนใจ\"]', '[\"ถ่ายภาพ\"]', '[\"ถ่ายภาพ\"]', '[]', '2026-09-04 03:31:19'),
(26, 15, 33, 60, '[\"ตรงความสามารถที่จำเป็น 0\\/1\",\"รูปแบบงานตรงกับที่ต้องการ\",\"ตรงรูปแบบการจ้างที่สนใจ\"]', '[\"ถ่ายภาพ\"]', '[\"ถ่ายภาพ\"]', '[]', '2026-09-03 20:12:42'),
(27, 5, NULL, 50, '[\"ตรงความสามารถที่จำเป็น 0\\/2\",\"รูปแบบงานตรงกับที่ต้องการ\"]', '[\"บริการและดูแลลูกค้า\",\"รับสายและประสานงาน\"]', '[\"บริการและดูแลลูกค้า\",\"รับสายและประสานงาน\"]', '[]', '2026-09-09 12:54:53'),
(27, 13, 33, 60, '[\"ตรงความสามารถที่จำเป็น 0\\/2\",\"รูปแบบงานตรงกับที่ต้องการ\",\"ตรงรูปแบบการจ้างที่สนใจ\"]', '[\"บริการและดูแลลูกค้า\",\"รับสายและประสานงาน\"]', '[\"บริการและดูแลลูกค้า\",\"รับสายและประสานงาน\"]', '[]', '2026-09-04 03:31:19'),
(27, 15, 33, 60, '[\"ตรงความสามารถที่จำเป็น 0\\/2\",\"รูปแบบงานตรงกับที่ต้องการ\",\"ตรงรูปแบบการจ้างที่สนใจ\"]', '[\"บริการและดูแลลูกค้า\",\"รับสายและประสานงาน\"]', '[\"บริการและดูแลลูกค้า\",\"รับสายและประสานงาน\"]', '[]', '2026-09-03 20:12:42'),
(28, 5, NULL, 50, '[\"ตรงความสามารถที่จำเป็น 0\\/2\",\"รูปแบบงานตรงกับที่ต้องการ\"]', '[\"ดูแลบูธและผู้ร่วมงาน\",\"ขายและแนะนำสินค้า\"]', '[\"ดูแลบูธและผู้ร่วมงาน\",\"ขายและแนะนำสินค้า\"]', '[]', '2026-09-09 12:54:53'),
(28, 7, 0, 60, '[\"ตรงความสามารถที่จำเป็น 0\\/2\"]', '[\"ดูแลบูธและผู้ร่วมงาน\",\"ขายและแนะนำสินค้า\"]', '[\"ดูแลบูธและผู้ร่วมงาน\",\"ขายและแนะนำสินค้า\"]', '[]', '2026-09-18 03:56:30'),
(28, 13, 33, 60, '[\"ตรงความสามารถที่จำเป็น 0\\/2\",\"รูปแบบงานตรงกับที่ต้องการ\",\"ตรงรูปแบบการจ้างที่สนใจ\"]', '[\"ดูแลบูธและผู้ร่วมงาน\",\"ขายและแนะนำสินค้า\"]', '[\"ดูแลบูธและผู้ร่วมงาน\",\"ขายและแนะนำสินค้า\"]', '[]', '2026-09-04 03:31:19'),
(28, 15, 67, 60, '[\"ตรงความสามารถที่จำเป็น 1\\/2\",\"รูปแบบงานตรงกับที่ต้องการ\",\"ตรงรูปแบบการจ้างที่สนใจ\"]', '[\"ขายและแนะนำสินค้า\"]', '[\"ดูแลบูธและผู้ร่วมงาน\",\"ขายและแนะนำสินค้า\"]', '[]', '2026-09-03 20:12:42'),
(29, 5, NULL, 50, '[\"ตรงความสามารถที่จำเป็น 0\\/2\",\"รูปแบบงานตรงกับที่ต้องการ\"]', '[\"ถ่ายและตัดต่อวิดีโอ\",\"สร้างคอนเทนต์และเขียนเนื้อหา\"]', '[\"ถ่ายและตัดต่อวิดีโอ\",\"สร้างคอนเทนต์และเขียนเนื้อหา\"]', '[]', '2026-09-09 12:54:53'),
(29, 13, 33, 60, '[\"ตรงความสามารถที่จำเป็น 0\\/2\",\"รูปแบบงานตรงกับที่ต้องการ\",\"ตรงรูปแบบการจ้างที่สนใจ\"]', '[\"ถ่ายและตัดต่อวิดีโอ\",\"สร้างคอนเทนต์และเขียนเนื้อหา\"]', '[\"ถ่ายและตัดต่อวิดีโอ\",\"สร้างคอนเทนต์และเขียนเนื้อหา\"]', '[]', '2026-09-04 03:31:19'),
(29, 15, 33, 60, '[\"ตรงความสามารถที่จำเป็น 0\\/2\",\"รูปแบบงานตรงกับที่ต้องการ\",\"ตรงรูปแบบการจ้างที่สนใจ\"]', '[\"ถ่ายและตัดต่อวิดีโอ\",\"สร้างคอนเทนต์และเขียนเนื้อหา\"]', '[\"ถ่ายและตัดต่อวิดีโอ\",\"สร้างคอนเทนต์และเขียนเนื้อหา\"]', '[]', '2026-09-03 20:12:42'),
(30, 5, NULL, 50, '[\"ตรงความสามารถที่จำเป็น 0\\/2\",\"รูปแบบงานตรงกับที่ต้องการ\"]', '[\"บริการและดูแลลูกค้า\",\"งานอาหารและเครื่องดื่ม\"]', '[\"บริการและดูแลลูกค้า\",\"งานอาหารและเครื่องดื่ม\"]', '[]', '2026-09-09 12:54:53'),
(30, 13, 33, 60, '[\"ตรงความสามารถที่จำเป็น 0\\/2\",\"รูปแบบงานตรงกับที่ต้องการ\",\"ตรงรูปแบบการจ้างที่สนใจ\"]', '[\"บริการและดูแลลูกค้า\",\"งานอาหารและเครื่องดื่ม\"]', '[\"บริการและดูแลลูกค้า\",\"งานอาหารและเครื่องดื่ม\"]', '[]', '2026-09-04 03:31:19'),
(30, 15, 33, 60, '[\"ตรงความสามารถที่จำเป็น 0\\/2\",\"รูปแบบงานตรงกับที่ต้องการ\",\"ตรงรูปแบบการจ้างที่สนใจ\"]', '[\"บริการและดูแลลูกค้า\",\"งานอาหารและเครื่องดื่ม\"]', '[\"บริการและดูแลลูกค้า\",\"งานอาหารและเครื่องดื่ม\"]', '[]', '2026-09-03 20:12:42'),
(37, 13, 24, 85, '[\"ตรงความสามารถที่จำเป็น 0\\/3\",\"รูปแบบงานตรงกับที่ต้องการ\",\"ตรงรูปแบบการจ้างที่สนใจ\"]', '[\"ต้อนรับและลงทะเบียน\",\"ดูแลคิวและกิจกรรม\",\"ดูแลบูธและผู้ร่วมงาน\"]', '[\"ต้อนรับและลงทะเบียน\",\"ดูแลคิวและกิจกรรม\",\"ดูแลบูธและผู้ร่วมงาน\"]', '[]', '2026-09-04 04:01:20'),
(37, 15, 100, 85, '[\"ตรงความสามารถที่จำเป็น 3\\/3\",\"สนใจงานด้าน: Staff และงานอีเวนต์\",\"รูปแบบงานตรงกับที่ต้องการ\",\"ตรงรูปแบบการจ้างที่สนใจ\"]', '[]', '[\"ต้อนรับและลงทะเบียน\",\"ดูแลคิวและกิจกรรม\",\"ดูแลบูธและผู้ร่วมงาน\"]', '[]', '2026-09-04 03:45:55'),
(38, 5, NULL, 50, '[\"ตรงความสามารถที่จำเป็น 0\\/2\",\"รูปแบบงานตรงกับที่ต้องการ\"]', '[\"พัฒนาเว็บไซต์\",\"ดูแลระบบและ IT Support\"]', '[\"พัฒนาเว็บไซต์\",\"ดูแลระบบและ IT Support\"]', '[]', '2026-09-09 12:54:53'),
(38, 7, 100, 85, '[\"ตรงความสามารถที่จำเป็น 2\\/2\",\"สนใจงานด้าน: เขียนโปรแกรมและพัฒนาเว็บไซต์\",\"รูปแบบงานตรงกับที่ต้องการ\",\"ตรงรูปแบบการจ้างที่สนใจ\"]', '[]', '[\"พัฒนาเว็บไซต์\",\"ดูแลระบบและ IT Support\"]', '[]', '2026-09-18 03:56:30'),
(38, 13, 76, 85, '[\"ตรงความสามารถที่จำเป็น 1\\/2\",\"สนใจงานด้าน: เขียนโปรแกรมและพัฒนาเว็บไซต์\",\"รูปแบบงานตรงกับที่ต้องการ\",\"ตรงรูปแบบการจ้างที่สนใจ\"]', '[\"ดูแลระบบและ IT Support\"]', '[\"พัฒนาเว็บไซต์\",\"ดูแลระบบและ IT Support\"]', '[]', '2026-09-04 05:15:35'),
(38, 15, 24, 85, '[\"ตรงความสามารถที่จำเป็น 0\\/2\",\"รูปแบบงานตรงกับที่ต้องการ\",\"ตรงรูปแบบการจ้างที่สนใจ\"]', '[\"พัฒนาเว็บไซต์\",\"ดูแลระบบและ IT Support\"]', '[\"พัฒนาเว็บไซต์\",\"ดูแลระบบและ IT Support\"]', '[]', '2026-09-04 05:15:35'),
(39, 5, NULL, 50, '[\"ตรงความสามารถที่จำเป็น 0\\/2\",\"รูปแบบงานตรงกับที่ต้องการ\"]', '[\"บริการและดูแลลูกค้า\",\"จัดการสินค้าในร้าน\"]', '[\"บริการและดูแลลูกค้า\",\"จัดการสินค้าในร้าน\"]', '[]', '2026-09-09 12:54:53'),
(39, 7, 12, 85, '[\"ตรงความสามารถที่จำเป็น 0\\/2\",\"ตรงรูปแบบการจ้างที่สนใจ\"]', '[\"บริการและดูแลลูกค้า\",\"จัดการสินค้าในร้าน\"]', '[\"บริการและดูแลลูกค้า\",\"จัดการสินค้าในร้าน\"]', '[]', '2026-09-18 03:56:30'),
(39, 13, 24, 85, '[\"ตรงความสามารถที่จำเป็น 0\\/2\",\"รูปแบบงานตรงกับที่ต้องการ\",\"ตรงรูปแบบการจ้างที่สนใจ\"]', '[\"บริการและดูแลลูกค้า\",\"จัดการสินค้าในร้าน\"]', '[\"บริการและดูแลลูกค้า\",\"จัดการสินค้าในร้าน\"]', '[]', '2026-09-04 05:15:34'),
(39, 15, 24, 85, '[\"ตรงความสามารถที่จำเป็น 0\\/2\",\"รูปแบบงานตรงกับที่ต้องการ\",\"ตรงรูปแบบการจ้างที่สนใจ\"]', '[\"บริการและดูแลลูกค้า\",\"จัดการสินค้าในร้าน\"]', '[\"บริการและดูแลลูกค้า\",\"จัดการสินค้าในร้าน\"]', '[]', '2026-09-04 05:15:34'),
(54, 5, NULL, 50, '[\"ตรงความสามารถที่จำเป็น 0\\/1\",\"รูปแบบงานตรงกับที่ต้องการ\"]', '[\"ถ่ายภาพ\"]', '[\"ถ่ายภาพ\"]', '[]', '2026-09-12 10:58:04'),
(54, 7, 0, 85, '[\"ตรงความสามารถที่จำเป็น 0\\/1\"]', '[\"ถ่ายภาพ\"]', '[\"ถ่ายภาพ\"]', '[]', '2026-09-18 03:56:30'),
(54, 13, 24, 85, '[\"ตรงความสามารถที่จำเป็น 0\\/1\",\"รูปแบบงานตรงกับที่ต้องการ\",\"ตรงรูปแบบการจ้างที่สนใจ\"]', '[\"ถ่ายภาพ\"]', '[\"ถ่ายภาพ\"]', '[]', '2026-09-12 11:14:26'),
(54, 15, 24, 85, '[\"ตรงความสามารถที่จำเป็น 0\\/1\",\"รูปแบบงานตรงกับที่ต้องการ\",\"ตรงรูปแบบการจ้างที่สนใจ\"]', '[\"ถ่ายภาพ\"]', '[\"ถ่ายภาพ\"]', '[]', '2026-09-12 11:14:26');

-- --------------------------------------------------------

--
-- Table structure for table `legacy_standalone_job_promotions`
--

CREATE TABLE `legacy_standalone_job_promotions` (
  `promotion_id` int(10) UNSIGNED NOT NULL,
  `job_id` int(10) UNSIGNED NOT NULL,
  `employer_user_id` int(10) UNSIGNED NOT NULL,
  `subscription_id` int(10) UNSIGNED DEFAULT NULL,
  `promotion_source` enum('standalone','subscription') NOT NULL DEFAULT 'standalone',
  `package_id` int(10) UNSIGNED NOT NULL,
  `package_name_snapshot` varchar(120) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `duration_days` smallint(5) UNSIGNED NOT NULL,
  `promotion_status` enum('pending_payment','pending_verification','active','rejected','expired','cancelled') NOT NULL DEFAULT 'pending_payment',
  `payment_slip_path` varchar(255) DEFAULT NULL,
  `payment_reference` varchar(120) DEFAULT NULL,
  `payment_submitted_at` datetime DEFAULT NULL,
  `reviewed_by_user_id` int(10) UNSIGNED DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `review_note` text DEFAULT NULL,
  `starts_at` datetime DEFAULT NULL,
  `ends_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `legacy_standalone_job_promotions`
--

INSERT INTO `legacy_standalone_job_promotions` (`promotion_id`, `job_id`, `employer_user_id`, `subscription_id`, `promotion_source`, `package_id`, `package_name_snapshot`, `amount`, `duration_days`, `promotion_status`, `payment_slip_path`, `payment_reference`, `payment_submitted_at`, `reviewed_by_user_id`, `reviewed_at`, `review_note`, `starts_at`, `ends_at`, `created_at`, `updated_at`) VALUES
(1, 38, 12, NULL, 'standalone', 1, 'ดันประกาศ 3 วัน', 99.00, 3, 'expired', 'uploads/payment-slips/8aaa6aa5b35b95ac5346.jpg', NULL, '2026-09-04 12:08:40', 1, '2026-09-04 12:09:01', NULL, '2026-09-04 12:09:01', '2026-09-07 12:09:01', '2026-09-04 05:08:32', '2026-09-09 12:57:54');

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
(8, 13, 'ได้รับคะแนนใหม่', 'Spark Event Studio ให้คะแนนคุณ 5 ดาว หลังจบงาน', 'worker/application-detail.php?id=7', 1, '2026-08-30 10:10:05'),
(9, 2, 'ได้รับคะแนนใหม่', 'Wasuphon Mahawong ให้คะแนนคุณ 5 ดาว หลังจบงาน', 'employer/applicant-detail.php?id=7&job=21', 1, '2026-08-30 10:11:15'),
(10, 15, 'คำเชิญสมัครงานใหม่', 'ผู้ว่าจ้างเชิญคุณสมัครงาน: Event Staff งานเปิดตัวสินค้า', 'worker/invitations.php', 1, '2026-09-04 03:09:53'),
(11, 15, 'คำเชิญสมัครงานใหม่', 'ผู้ว่าจ้างเชิญคุณสมัครงาน: งานเปิดตัวสินค้า', 'worker/invitations.php', 1, '2026-09-04 04:01:23'),
(12, 13, 'อัปเดตสถานะใบสมัคร', 'งาน “Event Staff งานเปิดตัวสินค้า” เปลี่ยนสถานะเป็น ผ่านสัมภาษณ์แล้ว 🎉', 'worker/application-detail.php?id=7', 0, '2026-09-04 04:10:27'),
(13, 13, 'อัปเดตสถานะใบสมัคร', 'งาน “Event Staff งานเปิดตัวสินค้า” เปลี่ยนสถานะเป็น งานเสร็จสิ้น — ให้คะแนนได้', 'worker/application-detail.php?id=7', 0, '2026-09-04 04:10:40'),
(14, 13, 'อัปเดตสถานะใบสมัคร', 'งาน “Event Staff งานเปิดตัวสินค้า” เปลี่ยนสถานะเป็น ผ่านสัมภาษณ์แล้ว 🎉', 'worker/application-detail.php?id=7', 0, '2026-09-04 04:18:39'),
(15, 13, 'อัปเดตสถานะใบสมัคร', 'งาน “Event Staff งานเปิดตัวสินค้า” เปลี่ยนสถานะเป็น งานเสร็จสิ้น — ให้คะแนนได้', 'worker/application-detail.php?id=7', 0, '2026-09-04 04:18:41'),
(16, 13, 'อัปเดตสถานะใบสมัคร', 'งาน “คนยืนบูธงาน MotoGP” เปลี่ยนสถานะเป็น มีสิทธิ์สัมภาษณ์ 🎉', 'worker/application-detail.php?id=5', 0, '2026-09-04 05:00:09'),
(17, 13, 'อัปเดตสถานะใบสมัคร', 'งาน “คนยืนบูธงาน MotoGP” เปลี่ยนสถานะเป็น ไม่ผ่านการคัดเลือก', 'worker/application-detail.php?id=5', 0, '2026-09-04 05:00:28'),
(18, 13, 'อัปเดตสถานะใบสมัคร', 'งาน “คนยืนบูธงาน MotoGP” เปลี่ยนสถานะเป็น มีสิทธิ์สัมภาษณ์ 🎉', 'worker/application-detail.php?id=5', 0, '2026-09-04 05:00:53'),
(19, 1, 'มีสลิปโปรโมตรอตรวจ', 'fluk Software company ส่งสลิปโปรโมตงาน: เขียนโปรแกรม', 'admin/promotions.php', 1, '2026-09-04 05:08:40'),
(22, 7, 'ส่งใบสมัครสำเร็จ', 'ใบสมัครงาน “พนังงานขายสินค้า Powerbuy” ถูกส่งให้ผู้ว่าจ้างแล้ว', 'worker/application-detail.php?id=8', 1, '2026-09-04 05:11:53'),
(24, 12, 'มีผู้สมัครส่งใบสมัครใหม่', 'Rapeepat Wongsuwan สมัครงาน: พนังงานขายสินค้า Powerbuy', 'employer/applicant-detail.php?id=8&job=39', 1, '2026-09-04 05:13:37'),
(25, 7, 'ส่งใบสมัครสำเร็จ', 'ใบสมัครงาน “พนังงานขายสินค้า Powerbuy” ถูกส่งให้ผู้ว่าจ้างแล้ว', 'worker/application-detail.php?id=8', 1, '2026-09-04 05:13:37'),
(26, 12, 'ผู้สมัครถอนใบสมัคร', 'Rapeepat Wongsuwan ถอนใบสมัครงาน: นักเขียนโปรแกรม', 'employer/applicants.php?job=32', 1, '2026-09-04 05:13:43'),
(27, 7, 'อัปเดตสถานะใบสมัคร', 'งาน “พนังงานขายสินค้า Powerbuy” เปลี่ยนสถานะเป็น มีสิทธิ์สัมภาษณ์ 🎉', 'worker/application-detail.php?id=8', 1, '2026-09-04 05:14:26'),
(28, 7, 'อัปเดตสถานะใบสมัคร', 'งาน “พนังงานขายสินค้า Powerbuy” เปลี่ยนสถานะเป็น ผ่านสัมภาษณ์แล้ว 🎉', 'worker/application-detail.php?id=8', 1, '2026-09-04 05:15:58'),
(29, 7, 'อัปเดตสถานะใบสมัคร', 'งาน “พนังงานขายสินค้า Powerbuy” เปลี่ยนสถานะเป็น มีสิทธิ์สัมภาษณ์ 🎉', 'worker/application-detail.php?id=8', 1, '2026-09-04 05:16:00'),
(30, 7, 'อัปเดตสถานะใบสมัคร', 'งาน “พนังงานขายสินค้า Powerbuy” เปลี่ยนสถานะเป็น ผ่านสัมภาษณ์แล้ว 🎉', 'worker/application-detail.php?id=8', 1, '2026-09-04 05:16:13'),
(31, 7, 'อัปเดตสถานะใบสมัคร', 'งาน “พนังงานขายสินค้า Powerbuy” เปลี่ยนสถานะเป็น รอพิจารณา', 'worker/application-detail.php?id=8', 1, '2026-09-04 05:16:16'),
(32, 7, 'อัปเดตสถานะใบสมัคร', 'งาน “พนังงานขายสินค้า Powerbuy” เปลี่ยนสถานะเป็น มีสิทธิ์สัมภาษณ์ 🎉', 'worker/application-detail.php?id=8', 1, '2026-09-04 05:19:07'),
(33, 7, 'อัปเดตสถานะใบสมัคร', 'งาน “พนังงานขายสินค้า Powerbuy” เปลี่ยนสถานะเป็น ผ่านสัมภาษณ์แล้ว 🎉', 'worker/application-detail.php?id=8', 1, '2026-09-04 05:33:24'),
(34, 7, 'อัปเดตสถานะใบสมัคร', 'งาน “พนังงานขายสินค้า Powerbuy” เปลี่ยนสถานะเป็น รอพิจารณา', 'worker/application-detail.php?id=8', 1, '2026-09-04 05:33:51'),
(35, 7, 'อัปเดตสถานะใบสมัคร', 'งาน “พนังงานขายสินค้า Powerbuy” เปลี่ยนสถานะเป็น มีสิทธิ์สัมภาษณ์ 🎉', 'worker/application-detail.php?id=8', 1, '2026-09-04 05:42:22'),
(64, 12, 'โปรโมชันหมดอายุแล้ว', 'การโปรโมตงาน “เขียนโปรแกรม” สิ้นสุดแล้ว', 'employer/promote.php?job=38&promotion=1', 1, '2026-09-09 12:57:54'),
(77, 7, 'อัปเดตสถานะใบสมัคร', 'งาน “พนังงานขายสินค้า Powerbuy” เปลี่ยนสถานะเป็น รอพิจารณา', 'worker/application-detail.php?id=8', 1, '2026-09-09 16:39:09'),
(89, 7, 'อัปเดตสถานะใบสมัคร', 'งาน “พนังงานขายสินค้า Powerbuy” เปลี่ยนสถานะเป็น มีสิทธิ์สัมภาษณ์ 🎉', 'worker/application-detail.php?id=8', 1, '2026-09-10 03:43:05'),
(90, 7, 'อัปเดตสถานะใบสมัคร', 'งาน “พนังงานขายสินค้า Powerbuy” เปลี่ยนสถานะเป็น รอพิจารณา', 'worker/application-detail.php?id=8', 1, '2026-09-10 03:43:13'),
(124, 3, 'ปรับประกาศตามสิทธิ์ Free', 'ระบบซ่อนประกาศส่วนเกินตามสิทธิ์ Free คุณสามารถเลือกเปิดประกาศได้ไม่เกิน 3 รายการ', 'employer/dashboard.php#all-jobs', 0, '2026-09-10 05:04:02'),
(125, 7, 'อัปเดตสถานะใบสมัคร', 'งาน “พนังงานขายสินค้า Powerbuy” เปลี่ยนสถานะเป็น มีสิทธิ์สัมภาษณ์ 🎉', 'worker/application-detail.php?id=8', 1, '2026-09-10 05:04:08'),
(126, 1, 'มีสลิปแพ็กเกจ Pro รอตรวจ', 'Rapeepat Wongsuwan ส่งสลิปแพ็กเกจ Pro', 'admin/subscriptions.php', 1, '2026-09-12 10:04:43'),
(127, 12, 'ผลตรวจสลิปแพ็กเกจ Pro', 'อนุมัติแพ็กเกจ Pro 30 วัน แล้ว ใช้งานตั้งแต่ 12/09/2026 12:04 ถึง 12/10/2026 12:04', 'employer/subscription.php?subscription=9', 1, '2026-09-12 10:04:58'),
(128, 12, 'มีผู้สมัครงานใหม่', 'Rapeepat Wongsuwan สมัครงาน: ช่างภาพ', 'employer/applicant-detail.php?id=9&job=54', 1, '2026-09-12 10:44:36'),
(129, 7, 'ส่งใบสมัครสำเร็จ', 'ใบสมัครงาน “ช่างภาพ” ถูกส่งให้ผู้ว่าจ้างแล้ว', 'worker/application-detail.php?id=9', 1, '2026-09-12 10:44:36'),
(130, 7, 'อัปเดตสถานะใบสมัคร', 'งาน “ช่างภาพ” เปลี่ยนสถานะเป็น มีสิทธิ์สัมภาษณ์ 🎉', 'worker/application-detail.php?id=9', 1, '2026-09-12 10:44:46'),
(131, 7, 'อัปเดตสถานะใบสมัคร', 'งาน “ช่างภาพ” เปลี่ยนสถานะเป็น ผ่านสัมภาษณ์แล้ว 🎉', 'worker/application-detail.php?id=9', 1, '2026-09-12 10:44:55'),
(132, 7, 'ได้รับรีวิวใหม่', 'Rapeepat Wongsuwan ให้คะแนนคุณ 5 ดาว พร้อมความคิดเห็นหลังจบงาน', 'worker/application-detail.php?id=9', 1, '2026-09-12 10:45:02'),
(133, 7, 'อัปเดตสถานะใบสมัคร', 'งาน “ช่างภาพ” เปลี่ยนสถานะเป็น งานเสร็จสิ้น — ให้คะแนนได้', 'worker/application-detail.php?id=9', 1, '2026-09-12 10:45:02'),
(136, 12, 'ได้รับรีวิวใหม่', 'Rapeepat Wongsuwan ให้คะแนนคุณ 5 ดาว พร้อมความคิดเห็นหลังจบงาน', 'employer/applicant-detail.php?id=9&job=54', 1, '2026-09-12 10:46:34'),
(138, 13, 'อัปเดตสถานะใบสมัคร', 'งาน “คนยืนบูธงาน MotoGP” เปลี่ยนสถานะเป็น ไม่ผ่านการคัดเลือก', 'worker/application-detail.php?id=5', 0, '2026-09-12 10:53:40');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `review_id` int(10) UNSIGNED NOT NULL,
  `application_id` int(10) UNSIGNED NOT NULL,
  `reviewer_user_id` int(10) UNSIGNED NOT NULL,
  `reviewee_user_id` int(10) UNSIGNED NOT NULL,
  `reviewer_role` enum('worker','employer') NOT NULL,
  `rating` tinyint(3) UNSIGNED NOT NULL,
  `review_comment` varchar(1000) DEFAULT NULL,
  `review_status` enum('visible','hidden') NOT NULL DEFAULT 'visible',
  `moderated_by_user_id` int(10) UNSIGNED DEFAULT NULL,
  `moderated_at` datetime DEFAULT NULL,
  `moderation_note` varchar(1000) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`review_id`, `application_id`, `reviewer_user_id`, `reviewee_user_id`, `reviewer_role`, `rating`, `review_comment`, `review_status`, `moderated_by_user_id`, `moderated_at`, `moderation_note`, `created_at`, `updated_at`) VALUES
(1, 7, 13, 2, 'worker', 5, NULL, 'visible', NULL, NULL, NULL, '2026-08-30 10:11:15', '2026-08-30 10:11:15'),
(2, 7, 2, 13, 'employer', 5, NULL, 'visible', 1, '2026-09-12 17:46:11', NULL, '2026-08-30 10:10:05', '2026-09-12 10:46:11'),
(21, 9, 12, 7, 'employer', 5, 'ดีๆๆๆ', 'visible', NULL, NULL, NULL, '2026-09-12 10:45:02', '2026-09-12 10:45:02'),
(24, 9, 7, 12, 'worker', 5, 'ดีๆๆ', 'visible', NULL, NULL, NULL, '2026-09-12 10:46:34', '2026-09-12 10:46:34');

-- --------------------------------------------------------

--
-- Table structure for table `review_reports`
--

CREATE TABLE `review_reports` (
  `review_report_id` int(10) UNSIGNED NOT NULL,
  `review_id` int(10) UNSIGNED NOT NULL,
  `reporter_user_id` int(10) UNSIGNED NOT NULL,
  `report_reason` varchar(500) NOT NULL,
  `report_status` enum('pending','resolved','dismissed') NOT NULL DEFAULT 'pending',
  `resolved_by_user_id` int(10) UNSIGNED DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `schema_migrations`
--

CREATE TABLE `schema_migrations` (
  `migration` varchar(255) NOT NULL,
  `checksum` char(64) NOT NULL,
  `applied_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `schema_migrations`
--

INSERT INTO `schema_migrations` (`migration`, `checksum`, `applied_at`) VALUES
('0001_initial_schema.sql', '2f51249b791e63cc5d30974b1fb32d8379e24a3e02d41b166d19a87e83676919', '2026-09-03 19:56:39'),
('0002_job_promotions.sql', 'e7bb7a63fc9204274eff3c4e0c1f2693adf05f4993fc6653641c6ba51e51523a', '2026-09-03 18:44:17'),
('0003_broad_skill_catalog.sql', '7e6158400208076a7ec309ae81d3225492b9e1bd3d575fb7f69765814d6e84bc', '2026-09-03 19:11:49'),
('0004_job_worker_matches.sql', '4668f330933b348fdf31787fb811539a5ffbed42375102b1c2c907af06b89457', '2026-09-03 18:37:24'),
('0005_worker_survey_onboarding.sql', '3ab0e85cadfcf8d02a61743516df58f1ec7cf30b57d5ef858d8c4989447cb6b2', '2026-09-03 19:56:49'),
('0006_refresh_sample_jobs_buriram.sql', '98007d682875f4f08c00e082564ed18741116234466cfc7a94987a96d6dd61cc', '2026-09-03 20:09:24'),
('0007_add_structured_work_schedule.sql', 'dbb057a03630acc063a3a53fc9639a02d96e4a3c6b3b9d756ed61016083bee86', '2026-09-04 03:38:39'),
('0008_email_delivery_queue.sql', 'efde1d0811fdfcab714bf7b5a1c1a600363611194263a33b254ae35ac737f9fc', '2026-09-04 04:16:24'),
('0009_usernames_and_reviews.sql', '6d81e6c4b9360d7f25638c6d6a55231d98c32a4e07192b73892c66e24846f340', '2026-09-09 12:31:08'),
('0010_employer_subscriptions.sql', '32e0cc169c690cb203c9f19338ee7cc8067be3e083cca0474b77fea86ace2172', '2026-09-09 12:31:08'),
('0011_application_completed_at.sql', '8ff13087d629de68d5b5dfd1ecde3f47783164ffb3c5326ae73dc16cedde1476', '2026-09-12 10:25:17'),
('0012_pro_only_job_promotions.sql', '531bf09dd7bab9b0cac26e7fd2380dd9f3ddfc49f52f69690299938eaa210dfd', '2026-09-12 10:43:08'),
('0013_job_promotion_active_index.sql', '704219839456984a15305b4950b28666113702d9d03bc6f3d8d42bcdb370f060', '2026-09-12 10:43:56');

-- --------------------------------------------------------

--
-- Table structure for table `skills`
--

CREATE TABLE `skills` (
  `skill_id` int(10) UNSIGNED NOT NULL,
  `skill_category_id` int(10) UNSIGNED DEFAULT NULL,
  `skill_name` varchar(100) NOT NULL,
  `is_custom` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `retired_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `skills`
--

INSERT INTO `skills` (`skill_id`, `skill_category_id`, `skill_name`, `is_custom`, `is_active`, `retired_at`, `created_at`) VALUES
(13, 3, 'canva', 0, 0, '2026-09-04 01:23:37', '2026-08-22 12:21:12'),
(14, NULL, 'capcup', 0, 0, '2026-09-04 01:23:37', '2026-08-22 12:21:12'),
(15, NULL, 'microsoft', 0, 0, '2026-09-04 01:23:37', '2026-08-22 12:21:12'),
(16, NULL, 'vscode', 0, 0, '2026-09-04 01:23:37', '2026-08-22 12:21:12'),
(17, NULL, 'สื่อสารได้ดี', 0, 0, '2026-09-04 01:23:37', '2026-08-22 12:21:12'),
(18, NULL, 'ทำงานเป็นทีม', 0, 0, '2026-09-04 01:23:37', '2026-08-22 12:21:12'),
(23, 6, 'php', 0, 0, '2026-09-04 01:23:37', '2026-08-22 12:50:56'),
(24, NULL, 'html', 0, 0, '2026-09-04 01:23:37', '2026-08-22 12:50:56'),
(25, 6, 'javascript', 0, 0, '2026-09-04 01:23:37', '2026-08-22 12:50:56'),
(71, 6, 'Figma', 0, 0, '2026-09-04 01:23:37', '2026-08-25 07:02:23'),
(72, 4, 'Photoshop', 0, 0, '2026-09-04 01:23:37', '2026-08-25 07:02:23'),
(74, NULL, 'สื่อสารภาษาอังกฤษ', 0, 0, '2026-09-04 01:23:37', '2026-08-25 08:28:47'),
(75, NULL, 'การบริการ', 0, 0, '2026-09-04 01:23:37', '2026-08-25 08:28:47'),
(76, NULL, 'ไม่มี', 0, 0, '2026-09-04 01:23:37', '2026-08-26 14:22:22'),
(78, NULL, 'UX/UI', 0, 0, '2026-09-04 01:23:37', '2026-08-26 14:28:17'),
(80, NULL, 'UXUI', 0, 0, '2026-09-04 01:23:37', '2026-08-26 14:29:33'),
(94, 4, 'CapCut', 0, 0, '2026-09-04 01:23:37', '2026-08-29 09:02:11'),
(95, 4, '3D Design', 0, 0, '2026-09-04 01:23:37', '2026-08-29 09:02:11'),
(96, 4, 'Motion Graphic', 0, 0, '2026-09-04 01:23:37', '2026-08-29 09:02:11'),
(97, 4, 'เขียนคอนเทนต์', 0, 0, '2026-09-04 01:23:37', '2026-08-29 09:02:11'),
(98, 4, 'ถ่ายภาพ', 0, 1, NULL, '2026-08-29 09:02:11'),
(99, 4, 'ตัดต่อวิดีโอ', 0, 0, '2026-09-04 01:23:37', '2026-08-29 09:02:11'),
(100, 4, 'Illustrator', 0, 0, '2026-09-04 01:23:37', '2026-08-29 09:02:11'),
(101, 2, 'ควบคุมคิว', 0, 0, '2026-09-04 01:23:37', '2026-08-29 09:02:11'),
(102, 2, 'ถ่ายภาพหน้างาน', 0, 0, '2026-09-04 01:23:37', '2026-08-29 09:02:11'),
(103, 2, 'ดูแลเครื่องเสียง', 0, 0, '2026-09-04 01:23:37', '2026-08-29 09:02:11'),
(104, 2, 'MC / พิธีกร', 0, 0, '2026-09-04 01:23:37', '2026-08-29 09:02:11'),
(105, 2, 'จัดสถานที่', 0, 0, '2026-09-04 01:23:37', '2026-08-29 09:02:11'),
(106, 2, 'ประสานงาน', 0, 0, '2026-09-04 01:23:37', '2026-08-29 09:02:11'),
(107, 2, 'ดูแลบูธ', 0, 0, '2026-09-04 01:23:37', '2026-08-29 09:02:11'),
(108, 2, 'ลงทะเบียนหน้างาน', 0, 0, '2026-09-04 01:23:37', '2026-08-29 09:02:11'),
(109, 7, 'จัดส่งสินค้า', 0, 1, NULL, '2026-08-29 09:02:11'),
(110, 7, 'ตรวจนับสินค้า', 0, 0, '2026-09-04 01:23:37', '2026-08-29 09:02:11'),
(111, 7, 'ติดตั้งอุปกรณ์', 0, 0, '2026-09-04 01:23:37', '2026-08-29 09:02:11'),
(112, 7, 'ใช้เครื่องมือช่าง', 0, 0, '2026-09-04 01:23:37', '2026-08-29 09:02:11'),
(113, 7, 'ยกของ', 0, 0, '2026-09-04 01:23:37', '2026-08-29 09:02:11'),
(114, 7, 'แพ็กสินค้า', 0, 0, '2026-09-04 01:23:37', '2026-08-29 09:02:11'),
(115, 7, 'ขับมอเตอร์ไซค์', 0, 0, '2026-09-04 01:23:37', '2026-08-29 09:02:11'),
(116, 7, 'ขับรถยนต์', 0, 0, '2026-09-04 01:23:37', '2026-08-29 09:02:11'),
(117, 5, 'ธุรการ', 0, 0, '2026-09-04 01:23:37', '2026-08-29 09:02:11'),
(118, 5, 'รับโทรศัพท์', 0, 0, '2026-09-04 01:23:37', '2026-08-29 09:02:11'),
(119, 5, 'จัดตารางนัดหมาย', 0, 0, '2026-09-04 01:23:37', '2026-08-29 09:02:11'),
(120, 5, 'พิมพ์เอกสาร', 0, 0, '2026-09-04 01:23:37', '2026-08-29 09:02:11'),
(121, 5, 'จัดเอกสาร', 0, 0, '2026-09-04 01:23:37', '2026-08-29 09:02:11'),
(122, 5, 'คีย์ข้อมูล', 0, 0, '2026-09-04 01:23:37', '2026-08-29 09:02:11'),
(123, 5, 'Google Workspace', 0, 0, '2026-09-04 01:23:37', '2026-08-29 09:02:11'),
(124, 5, 'Microsoft Excel', 0, 0, '2026-09-04 01:23:37', '2026-08-29 09:02:11'),
(125, 3, 'วิเคราะห์ลูกค้า', 0, 0, '2026-09-04 01:23:37', '2026-08-29 09:02:11'),
(126, 3, 'ทำคอนเทนต์', 0, 0, '2026-09-04 01:23:37', '2026-08-29 09:02:11'),
(127, 3, 'ยิงโฆษณาออนไลน์', 0, 0, '2026-09-04 01:23:37', '2026-08-29 09:02:11'),
(128, 3, 'ดูแลโซเชียลมีเดีย', 0, 1, NULL, '2026-08-29 09:02:11'),
(129, 3, 'Live ขายสินค้า', 0, 0, '2026-09-04 01:23:37', '2026-08-29 09:02:11'),
(130, 3, 'ปิดการขาย', 0, 0, '2026-09-04 01:23:37', '2026-08-29 09:02:11'),
(131, 3, 'การขาย', 0, 0, '2026-09-04 01:23:37', '2026-08-29 09:02:11'),
(132, 1, 'ตอบแชตลูกค้า', 0, 0, '2026-09-04 01:23:37', '2026-08-29 09:02:11'),
(133, 1, 'ทำอาหาร', 0, 0, '2026-09-04 01:23:37', '2026-08-29 09:02:11'),
(134, 1, 'ใช้ระบบ POS', 0, 0, '2026-09-04 01:23:37', '2026-08-29 09:02:11'),
(135, 1, 'จัดเรียงสินค้า', 0, 0, '2026-09-04 01:23:37', '2026-08-29 09:02:11'),
(136, 1, 'ชงกาแฟ', 0, 0, '2026-09-04 01:23:37', '2026-08-29 09:02:11'),
(137, 1, 'แคชเชียร์', 0, 0, '2026-09-04 01:23:37', '2026-08-29 09:02:11'),
(138, 1, 'รับออเดอร์', 0, 0, '2026-09-04 01:23:37', '2026-08-29 09:02:11'),
(139, 1, 'บริการลูกค้า', 0, 0, '2026-09-04 01:23:37', '2026-08-29 09:02:11'),
(140, 6, 'Graphic Design', 0, 0, '2026-09-04 01:23:37', '2026-08-29 09:02:11'),
(141, 6, 'Web Design', 0, 0, '2026-09-04 01:23:37', '2026-08-29 09:02:11'),
(142, 6, 'UI / UX Design', 0, 0, '2026-09-04 01:23:37', '2026-08-29 09:02:11'),
(143, 6, 'WordPress', 0, 0, '2026-09-04 01:23:37', '2026-08-29 09:02:11'),
(144, 6, 'Full-stack Development', 0, 0, '2026-09-04 01:23:37', '2026-08-29 09:02:11'),
(145, 6, 'Back-end Development', 0, 0, '2026-09-04 01:23:37', '2026-08-29 09:02:11'),
(146, 6, 'Front-end Development', 0, 0, '2026-09-04 01:23:37', '2026-08-29 09:02:11'),
(147, 6, 'MySQL / Database', 0, 0, '2026-09-04 01:23:37', '2026-08-29 09:02:11'),
(148, 6, 'HTML / CSS', 0, 0, '2026-09-04 01:23:37', '2026-08-29 09:02:11'),
(157, NULL, 'ฟหก', 1, 1, NULL, '2026-08-29 09:15:53'),
(158, 1, 'บริการและดูแลลูกค้า', 0, 1, NULL, '2026-09-03 18:23:36'),
(159, 1, 'ต้อนรับและให้ข้อมูล', 0, 1, NULL, '2026-09-03 18:23:36'),
(160, 1, 'งานอาหารและเครื่องดื่ม', 0, 1, NULL, '2026-09-03 18:23:36'),
(161, 1, 'รับออเดอร์และชำระเงิน', 0, 1, NULL, '2026-09-03 18:23:36'),
(162, 1, 'จัดการสินค้าในร้าน', 0, 1, NULL, '2026-09-03 18:23:36'),
(163, 2, 'จัดเตรียมสถานที่', 0, 1, NULL, '2026-09-03 18:23:36'),
(164, 2, 'ต้อนรับและลงทะเบียน', 0, 1, NULL, '2026-09-03 18:23:36'),
(165, 2, 'ประสานงานอีเวนต์', 0, 1, NULL, '2026-09-03 18:23:36'),
(166, 2, 'ดูแลคิวและกิจกรรม', 0, 1, NULL, '2026-09-03 18:23:36'),
(167, 2, 'ดูแลบูธและผู้ร่วมงาน', 0, 1, NULL, '2026-09-03 18:23:36'),
(168, 2, 'พิธีกรและการนำเสนอ', 0, 1, NULL, '2026-09-03 18:23:36'),
(169, 2, 'ดูแลสื่อและอุปกรณ์หน้างาน', 0, 1, NULL, '2026-09-03 18:23:36'),
(170, 3, 'ขายและแนะนำสินค้า', 0, 1, NULL, '2026-09-03 18:23:36'),
(171, 3, 'เจรจาและปิดการขาย', 0, 1, NULL, '2026-09-03 18:23:36'),
(172, 3, 'ขายสินค้าออนไลน์และไลฟ์', 0, 1, NULL, '2026-09-03 18:23:36'),
(173, 3, 'สร้างคอนเทนต์การตลาด', 0, 1, NULL, '2026-09-03 18:23:36'),
(174, 3, 'โฆษณาและประชาสัมพันธ์', 0, 1, NULL, '2026-09-03 18:23:36'),
(175, 3, 'วิเคราะห์ลูกค้าและตลาด', 0, 1, NULL, '2026-09-03 18:23:36'),
(176, 4, 'ออกแบบกราฟิก', 0, 1, NULL, '2026-09-03 18:23:36'),
(177, 4, 'ออกแบบเว็บไซต์และ UI/UX', 0, 1, NULL, '2026-09-03 18:23:36'),
(178, 4, 'ถ่ายและตัดต่อวิดีโอ', 0, 1, NULL, '2026-09-03 18:23:36'),
(179, 4, 'สร้างคอนเทนต์และเขียนเนื้อหา', 0, 1, NULL, '2026-09-03 18:23:36'),
(180, 4, 'ภาพประกอบและงาน 3D', 0, 1, NULL, '2026-09-03 18:23:36'),
(181, 4, 'ผลิตสื่อและงานนำเสนอ', 0, 1, NULL, '2026-09-03 18:23:36'),
(182, 5, 'จัดทำและจัดเก็บเอกสาร', 0, 1, NULL, '2026-09-03 18:23:36'),
(183, 5, 'คีย์และจัดการข้อมูล', 0, 1, NULL, '2026-09-03 18:23:36'),
(184, 5, 'จัดทำตารางและรายงาน', 0, 1, NULL, '2026-09-03 18:23:36'),
(185, 5, 'รับสายและประสานงาน', 0, 1, NULL, '2026-09-03 18:23:36'),
(186, 5, 'งานธุรการและสนับสนุน', 0, 1, NULL, '2026-09-03 18:23:36'),
(187, 5, 'บัญชีและการเงินเบื้องต้น', 0, 1, NULL, '2026-09-03 18:23:36'),
(188, 6, 'พัฒนาเว็บไซต์', 0, 1, NULL, '2026-09-03 18:23:36'),
(189, 6, 'พัฒนาแอปพลิเคชันและซอฟต์แวร์', 0, 1, NULL, '2026-09-03 18:23:36'),
(190, 6, 'จัดการข้อมูลและฐานข้อมูล', 0, 1, NULL, '2026-09-03 18:23:36'),
(191, 6, 'ดูแลระบบและ IT Support', 0, 1, NULL, '2026-09-03 18:23:36'),
(192, 6, 'ดูแลเครือข่ายและเซิร์ฟเวอร์', 0, 1, NULL, '2026-09-03 18:23:36'),
(193, 6, 'ทดสอบระบบและซอฟต์แวร์', 0, 1, NULL, '2026-09-03 18:23:36'),
(194, 7, 'งานคลังสินค้าและตรวจนับ', 0, 1, NULL, '2026-09-03 18:23:36'),
(195, 7, 'แพ็กและจัดเตรียมสินค้า', 0, 1, NULL, '2026-09-03 18:23:36'),
(196, 7, 'ขับรถและขนส่ง', 0, 1, NULL, '2026-09-03 18:23:36'),
(197, 7, 'ติดตั้งและเคลื่อนย้ายอุปกรณ์', 0, 1, NULL, '2026-09-03 18:23:36'),
(198, 7, 'งานช่างและซ่อมบำรุง', 0, 1, NULL, '2026-09-03 18:23:36'),
(199, 7, 'งานใช้แรงและงานทั่วไป', 0, 1, NULL, '2026-09-03 18:23:36'),
(200, 7, 'ดูแลสถานที่และความสะอาด', 0, 1, NULL, '2026-09-03 18:23:36');

-- --------------------------------------------------------

--
-- Table structure for table `skill_categories`
--

CREATE TABLE `skill_categories` (
  `skill_category_id` int(10) UNSIGNED NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `category_slug` varchar(100) NOT NULL,
  `sort_order` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `skill_categories`
--

INSERT INTO `skill_categories` (`skill_category_id`, `category_name`, `category_slug`, `sort_order`, `is_active`, `created_at`) VALUES
(1, 'งานบริการและร้านค้า', 'service-retail', 10, 1, '2026-08-29 09:02:11'),
(2, 'งานอีเวนต์', 'event', 20, 1, '2026-08-29 09:02:11'),
(3, 'ขายและการตลาด', 'sales-marketing', 30, 1, '2026-08-29 09:02:11'),
(4, 'ครีเอทีฟและดิจิทัล', 'creative-digital', 40, 1, '2026-08-29 09:02:11'),
(5, 'งานสำนักงาน', 'office', 50, 1, '2026-08-29 09:02:11'),
(6, 'เทคโนโลยีและไอที', 'technology-design', 60, 1, '2026-08-29 09:02:11'),
(7, 'ขนส่งและงานทั่วไป', 'logistics-general', 70, 1, '2026-08-29 09:02:11');

-- --------------------------------------------------------

--
-- Table structure for table `skill_consolidation_map`
--

CREATE TABLE `skill_consolidation_map` (
  `legacy_skill_id` int(10) UNSIGNED NOT NULL,
  `broad_skill_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `skill_consolidation_map`
--

INSERT INTO `skill_consolidation_map` (`legacy_skill_id`, `broad_skill_id`, `created_at`) VALUES
(13, 176, '2026-09-03 18:23:37'),
(14, 178, '2026-09-03 18:23:37'),
(15, 186, '2026-09-03 18:23:37'),
(16, 189, '2026-09-03 18:23:37'),
(23, 188, '2026-09-03 18:23:37'),
(24, 188, '2026-09-03 18:23:37'),
(25, 188, '2026-09-03 18:23:37'),
(71, 177, '2026-09-03 18:23:37'),
(72, 176, '2026-09-03 18:23:37'),
(75, 158, '2026-09-03 18:23:37'),
(78, 177, '2026-09-03 18:23:37'),
(80, 177, '2026-09-03 18:23:37'),
(94, 178, '2026-09-03 18:23:37'),
(95, 180, '2026-09-03 18:23:37'),
(96, 178, '2026-09-03 18:23:37'),
(97, 179, '2026-09-03 18:23:37'),
(99, 178, '2026-09-03 18:23:37'),
(100, 176, '2026-09-03 18:23:37'),
(101, 166, '2026-09-03 18:23:37'),
(102, 98, '2026-09-03 18:23:37'),
(103, 169, '2026-09-03 18:23:37'),
(104, 168, '2026-09-03 18:23:37'),
(105, 163, '2026-09-03 18:23:37'),
(106, 165, '2026-09-03 18:23:37'),
(107, 167, '2026-09-03 18:23:37'),
(108, 164, '2026-09-03 18:23:37'),
(110, 194, '2026-09-03 18:23:37'),
(111, 197, '2026-09-03 18:23:37'),
(112, 198, '2026-09-03 18:23:37'),
(113, 199, '2026-09-03 18:23:37'),
(114, 195, '2026-09-03 18:23:37'),
(115, 196, '2026-09-03 18:23:37'),
(116, 196, '2026-09-03 18:23:37'),
(117, 186, '2026-09-03 18:23:37'),
(118, 185, '2026-09-03 18:23:37'),
(119, 186, '2026-09-03 18:23:37'),
(120, 182, '2026-09-03 18:23:37'),
(121, 182, '2026-09-03 18:23:37'),
(122, 183, '2026-09-03 18:23:37'),
(123, 186, '2026-09-03 18:23:37'),
(124, 184, '2026-09-03 18:23:37'),
(125, 175, '2026-09-03 18:23:37'),
(126, 173, '2026-09-03 18:23:37'),
(127, 174, '2026-09-03 18:23:37'),
(129, 172, '2026-09-03 18:23:37'),
(130, 171, '2026-09-03 18:23:37'),
(131, 170, '2026-09-03 18:23:37'),
(132, 158, '2026-09-03 18:23:37'),
(133, 160, '2026-09-03 18:23:37'),
(134, 161, '2026-09-03 18:23:37'),
(135, 162, '2026-09-03 18:23:37'),
(136, 160, '2026-09-03 18:23:37'),
(137, 161, '2026-09-03 18:23:37'),
(138, 161, '2026-09-03 18:23:37'),
(139, 158, '2026-09-03 18:23:37'),
(140, 176, '2026-09-03 18:23:37'),
(141, 177, '2026-09-03 18:23:37'),
(142, 177, '2026-09-03 18:23:37'),
(143, 188, '2026-09-03 18:23:37'),
(144, 188, '2026-09-03 18:23:37'),
(145, 188, '2026-09-03 18:23:37'),
(146, 188, '2026-09-03 18:23:37'),
(147, 190, '2026-09-03 18:23:37'),
(148, 188, '2026-09-03 18:23:37');

-- --------------------------------------------------------

--
-- Table structure for table `subscription_plans`
--

CREATE TABLE `subscription_plans` (
  `plan_id` int(10) UNSIGNED NOT NULL,
  `plan_code` varchar(50) NOT NULL,
  `plan_name` varchar(120) NOT NULL,
  `plan_description` varchar(255) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `duration_days` smallint(5) UNSIGNED NOT NULL,
  `active_job_limit` smallint(5) UNSIGNED NOT NULL,
  `promotion_credits` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `promotion_duration_days` smallint(5) UNSIGNED NOT NULL DEFAULT 7,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` smallint(5) UNSIGNED NOT NULL DEFAULT 10,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `subscription_plans`
--

INSERT INTO `subscription_plans` (`plan_id`, `plan_code`, `plan_name`, `plan_description`, `price`, `duration_days`, `active_job_limit`, `promotion_credits`, `promotion_duration_days`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 'pro-30d', 'Pro 30 วัน', 'เปิดรับพร้อมกัน 6 ประกาศ และโปรโมตได้ 2 ครั้ง ครั้งละ 7 วัน', 239.00, 30, 6, 2, 7, 1, 10, '2026-09-09 12:31:08', '2026-09-09 12:31:08');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `username` varchar(30) DEFAULT NULL,
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

INSERT INTO `users` (`user_id`, `username`, `first_name`, `last_name`, `email`, `password_hash`, `role`, `phone`, `account_status`, `created_at`, `email_verified_at`) VALUES
(1, 'admin', 'Admin', 'FLEXJOB', 'admin@gmail.com', '$2y$10$C7Mda5k3E6wynuOjaodG3uuBCfrGpzsxt1.GXkpKP6nz2y69WIPKm', 'admin', NULL, 'active', '2026-08-18 12:56:05', NULL),
(2, NULL, 'Spark', 'Event Studio', 'employer@gmail.com', '$2y$10$4fWgNYHqUB7bhBw.DWLL8.aUAZw4N2bkYDkY.DP8VBwY.ejasyjFa', 'employer', NULL, 'active', '2026-08-18 12:56:05', NULL),
(3, NULL, 'KIND', 'Coffee', 'kind@flexjob.test', '$2y$10$NGlLFvgckOSFtrdSRQCVguQeIFa6KU7JHmx7uhyy8B0Sf3FsIB/kW', 'employer', NULL, 'active', '2026-08-18 13:01:04', NULL),
(4, NULL, 'Morrow', 'Creative', 'morrow@flexjob.test', '$2y$10$NGlLFvgckOSFtrdSRQCVguQeIFa6KU7JHmx7uhyy8B0Sf3FsIB/kW', 'employer', NULL, 'active', '2026-08-18 13:01:04', NULL),
(5, NULL, 'rapeepat', 'wongsuwan', 'rapeepat@gamail.com', '$2y$10$e2Kbr.SdS2Y1tTQHfuEF2uCE6JJobjLuwrbcapiEJq/q5tJafmXuq', 'worker', '0991028810', 'active', '2026-08-20 11:23:34', NULL),
(6, NULL, 'rape', 'wong', 'abc@gmail.com', '$2y$10$9dNPHSFBb4OcxeoyNrVnIenZXJU.KXNmYe9BHnzJ2zenkXVGruyT2', 'employer', '0981629810', 'active', '2026-08-20 11:24:59', NULL),
(7, 'rapeepat01', 'Rapeepat', 'Wongsuwan', 'rapeepat.wo02@gmail.com', '$2y$10$p5nij3EhEFElT.GSFDCdNuJYp4BzG/dVdrRxFr4eNBRIa0G1CHEra', 'worker', '0919876782', 'active', '2026-08-20 12:19:02', '2026-08-20 12:44:27'),
(12, 'rapeepat02', 'Rapeepat', 'Wongsuwan', 'frk24072561@gmail.com', '$2y$10$ozg5i9xj/ZfmKOCvh57Gx.qO.3/kXVj6kpyyhxxyh/zjJaQ0yOQk6', 'employer', '0981029910', 'active', '2026-08-22 12:22:48', '2026-08-22 12:44:23'),
(13, NULL, 'Wasuphon', 'Mahawong', 'bankchannel010@gmail.com', '$2y$10$t3MZXf0DzZdKtXNXPlVh/uidGTGIJ7bvXhFVUvQAedAAfitk/wl.2', 'worker', '0902858938', 'active', '2026-08-26 14:06:15', '2026-08-26 14:19:44'),
(15, NULL, 'Wasu', 'Mahawong', 'wasuphon1205@gmail.com', '$2y$10$9Dh4bhrOyGTBEi7D47s52OvSukQgB6B3SZpNpRjvk7AoGzWSpudT2', 'worker', '0902858938', 'active', '2026-09-03 20:11:18', '2026-09-03 20:11:28');

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
(7, 3),
(13, 1),
(13, 2),
(13, 3),
(15, 1),
(15, 2),
(15, 3);

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
  `resume_file_path` varchar(255) DEFAULT NULL,
  `portfolio_file_path` varchar(255) DEFAULT NULL,
  `portfolio_url` varchar(500) DEFAULT NULL,
  `profile_visibility` enum('application_only','searchable') NOT NULL DEFAULT 'application_only',
  `work_province` varchar(100) DEFAULT NULL,
  `preferred_work_mode` enum('any','onsite','remote','hybrid') NOT NULL DEFAULT 'any',
  `available_from` date DEFAULT NULL,
  `matching_survey_required_at` datetime DEFAULT NULL,
  `matching_survey_completed_at` datetime DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `worker_profiles`
--

INSERT INTO `worker_profiles` (`worker_profile_id`, `user_id`, `professional_headline`, `biography`, `profile_image_path`, `resume_file_path`, `portfolio_file_path`, `portfolio_url`, `profile_visibility`, `work_province`, `preferred_work_mode`, `available_from`, `matching_survey_required_at`, `matching_survey_completed_at`, `updated_at`) VALUES
(1, 5, '', '', NULL, 'uploads/resumes/a1b2bb781a0f5ad5a5d9.pdf', NULL, '', 'application_only', NULL, 'any', NULL, NULL, NULL, '2026-08-22 10:40:57'),
(3, 7, 'นักเขียนโปรแกรม Backend Frontend', 'สวัสดีครับ ผมมีประสบการณ์ทำงานด้าน IT support , network , web deverloper', 'uploads/profile-images/8038939095cc5e34bc5c.png', 'uploads/resumes/ac47684d40eadb4a0d0b.pdf', 'uploads/portfolios/ba3a87990e8be774d8fb.pdf', 'https://canva.link/ft59mmx4fa895oj', 'searchable', 'บุรีรัมย์', 'remote', '2026-08-22', NULL, '2026-09-04 12:03:59', '2026-09-18 03:56:22'),
(12, 13, 'it support', 'ไม่มี', 'uploads/profile-images/f329fb3bd3bbd3d15606.jpg', 'uploads/resumes/8e4e56a3ba19dcc70ad1.pdf', 'uploads/portfolios/7e6af709ac76e8f82495.pdf', '', 'searchable', 'บุรีรัมย์', 'any', '2026-08-27', NULL, NULL, '2026-09-04 03:26:52'),
(23, 15, NULL, NULL, NULL, NULL, NULL, NULL, 'searchable', 'บุรีรัมย์', 'any', '2026-09-05', '2026-09-04 03:11:18', '2026-09-04 03:12:42', '2026-09-03 20:12:42');

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
(7, 186, 'intermediate'),
(7, 188, 'intermediate'),
(7, 191, 'intermediate'),
(13, 176, 'intermediate'),
(13, 177, 'intermediate'),
(13, 188, 'intermediate'),
(13, 190, 'intermediate'),
(15, 163, 'intermediate'),
(15, 164, 'intermediate'),
(15, 166, 'intermediate'),
(15, 167, 'intermediate'),
(15, 169, 'intermediate');

-- --------------------------------------------------------

--
-- Table structure for table `worker_work_interests`
--

CREATE TABLE `worker_work_interests` (
  `worker_user_id` int(10) UNSIGNED NOT NULL,
  `work_interest_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `worker_work_interests`
--

INSERT INTO `worker_work_interests` (`worker_user_id`, `work_interest_id`) VALUES
(7, 1),
(7, 9),
(13, 1),
(13, 3),
(15, 7),
(15, 9);

-- --------------------------------------------------------

--
-- Table structure for table `work_interests`
--

CREATE TABLE `work_interests` (
  `work_interest_id` int(10) UNSIGNED NOT NULL,
  `interest_slug` varchar(100) NOT NULL,
  `interest_name` varchar(180) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` smallint(5) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `work_interests`
--

INSERT INTO `work_interests` (`work_interest_id`, `interest_slug`, `interest_name`, `is_active`, `sort_order`) VALUES
(1, 'web-development', 'เขียนโปรแกรมและพัฒนาเว็บไซต์', 1, 10),
(2, 'graphic-design', 'ออกแบบกราฟิกและโปสเตอร์', 1, 20),
(3, 'ux-ui-design', 'ออกแบบ UX/UI', 1, 30),
(4, 'video-editing', 'ตัดต่อวิดีโอ', 1, 40),
(5, 'photo-video', 'ถ่ายภาพและวิดีโอ', 1, 50),
(6, 'admin-document', 'งานเอกสารและธุรการ', 1, 60),
(7, 'event-staff', 'Staff และงานอีเวนต์', 1, 70),
(8, 'sales-promotion', 'งานขายและแนะนำสินค้า', 1, 80),
(9, 'food-service', 'งานบริการ ร้านอาหาร และเครื่องดื่ม', 1, 90),
(10, 'content-social', 'คอนเทนต์และดูแลโซเชียลมีเดีย', 1, 100);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `applications`
--
ALTER TABLE `applications`
  ADD PRIMARY KEY (`application_id`),
  ADD UNIQUE KEY `uq_application_job_worker` (`job_id`,`worker_user_id`),
  ADD KEY `idx_application_worker` (`worker_user_id`,`application_status`),
  ADD KEY `idx_application_completed_at` (`completed_at`);

--
-- Indexes for table `auth_tokens`
--
ALTER TABLE `auth_tokens`
  ADD PRIMARY KEY (`auth_token_id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `idx_auth_token_lookup` (`user_id`,`token_type`,`used_at`);

--
-- Indexes for table `email_log`
--
ALTER TABLE `email_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_to` (`to_email`),
  ADD KEY `idx_email_log_delivery_queue` (`status`,`available_at`);

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
-- Indexes for table `employer_subscriptions`
--
ALTER TABLE `employer_subscriptions`
  ADD PRIMARY KEY (`subscription_id`),
  ADD KEY `idx_subscription_employer_period` (`employer_user_id`,`subscription_status`,`starts_at`,`ends_at`),
  ADD KEY `idx_subscription_review_queue` (`subscription_status`,`payment_submitted_at`),
  ADD KEY `fk_subscription_plan` (`plan_id`),
  ADD KEY `fk_subscription_reviewer` (`reviewed_by_user_id`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`job_id`),
  ADD KEY `fk_job_employer` (`employer_user_id`),
  ADD KEY `fk_job_category` (`job_category_id`),
  ADD KEY `idx_job_listing` (`job_status`,`job_category_id`,`created_at`),
  ADD KEY `fk_job_work_interest` (`work_interest_id`);

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
-- Indexes for table `job_promotions`
--
ALTER TABLE `job_promotions`
  ADD PRIMARY KEY (`promotion_id`),
  ADD KEY `idx_promotion_job_status` (`job_id`,`promotion_status`,`ends_at`),
  ADD KEY `idx_promotion_employer` (`employer_user_id`,`created_at`),
  ADD KEY `idx_promotion_subscription` (`subscription_id`,`promotion_status`),
  ADD KEY `idx_promotion_active_period` (`promotion_status`,`starts_at`,`ends_at`);

--
-- Indexes for table `job_skills`
--
ALTER TABLE `job_skills`
  ADD PRIMARY KEY (`job_id`,`skill_id`),
  ADD KEY `idx_job_skill_lookup` (`skill_id`,`importance`,`job_id`);

--
-- Indexes for table `job_worker_matches`
--
ALTER TABLE `job_worker_matches`
  ADD PRIMARY KEY (`job_id`,`worker_user_id`),
  ADD KEY `idx_job_worker_matches_job_score` (`job_id`,`match_score`),
  ADD KEY `idx_job_worker_matches_worker_score` (`worker_user_id`,`match_score`);

--
-- Indexes for table `legacy_standalone_job_promotions`
--
ALTER TABLE `legacy_standalone_job_promotions`
  ADD PRIMARY KEY (`promotion_id`),
  ADD KEY `fk_job_promotion_package` (`package_id`),
  ADD KEY `fk_job_promotion_reviewer` (`reviewed_by_user_id`),
  ADD KEY `idx_promotion_job_status` (`job_id`,`promotion_status`,`ends_at`),
  ADD KEY `idx_promotion_review_queue` (`promotion_status`,`payment_submitted_at`),
  ADD KEY `idx_promotion_employer` (`employer_user_id`,`created_at`),
  ADD KEY `idx_promotion_subscription` (`subscription_id`,`promotion_status`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `idx_notification_inbox` (`user_id`,`is_read`,`created_at`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD UNIQUE KEY `uq_review_application_reviewer` (`application_id`,`reviewer_user_id`),
  ADD KEY `idx_reviewee_visible` (`reviewee_user_id`,`review_status`,`created_at`),
  ADD KEY `idx_review_reviewer` (`reviewer_user_id`,`created_at`),
  ADD KEY `fk_review_moderator` (`moderated_by_user_id`);

--
-- Indexes for table `review_reports`
--
ALTER TABLE `review_reports`
  ADD PRIMARY KEY (`review_report_id`),
  ADD UNIQUE KEY `uq_review_reporter` (`review_id`,`reporter_user_id`),
  ADD KEY `idx_review_report_queue` (`report_status`,`created_at`),
  ADD KEY `fk_review_report_reporter` (`reporter_user_id`),
  ADD KEY `fk_review_report_resolver` (`resolved_by_user_id`);

--
-- Indexes for table `schema_migrations`
--
ALTER TABLE `schema_migrations`
  ADD PRIMARY KEY (`migration`);

--
-- Indexes for table `skills`
--
ALTER TABLE `skills`
  ADD PRIMARY KEY (`skill_id`),
  ADD UNIQUE KEY `uq_skill_name` (`skill_name`),
  ADD KEY `idx_skill_catalog` (`skill_category_id`,`is_active`);

--
-- Indexes for table `skill_categories`
--
ALTER TABLE `skill_categories`
  ADD PRIMARY KEY (`skill_category_id`),
  ADD UNIQUE KEY `category_slug` (`category_slug`);

--
-- Indexes for table `skill_consolidation_map`
--
ALTER TABLE `skill_consolidation_map`
  ADD PRIMARY KEY (`legacy_skill_id`),
  ADD KEY `idx_skill_map_broad` (`broad_skill_id`);

--
-- Indexes for table `subscription_plans`
--
ALTER TABLE `subscription_plans`
  ADD PRIMARY KEY (`plan_id`),
  ADD UNIQUE KEY `uq_subscription_plan_code` (`plan_code`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `uq_users_username` (`username`);

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
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `idx_worker_profiles_matching_survey` (`matching_survey_required_at`,`matching_survey_completed_at`);

--
-- Indexes for table `worker_skills`
--
ALTER TABLE `worker_skills`
  ADD PRIMARY KEY (`worker_user_id`,`skill_id`),
  ADD KEY `idx_worker_skill_lookup` (`skill_id`,`worker_user_id`);

--
-- Indexes for table `worker_work_interests`
--
ALTER TABLE `worker_work_interests`
  ADD PRIMARY KEY (`worker_user_id`,`work_interest_id`),
  ADD KEY `idx_worker_work_interest_lookup` (`work_interest_id`,`worker_user_id`);

--
-- Indexes for table `work_interests`
--
ALTER TABLE `work_interests`
  ADD PRIMARY KEY (`work_interest_id`),
  ADD UNIQUE KEY `uq_work_interest_slug` (`interest_slug`),
  ADD KEY `idx_work_interest_active` (`is_active`,`sort_order`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `applications`
--
ALTER TABLE `applications`
  MODIFY `application_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `auth_tokens`
--
ALTER TABLE `auth_tokens`
  MODIFY `auth_token_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `email_log`
--
ALTER TABLE `email_log`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT for table `employer_documents`
--
ALTER TABLE `employer_documents`
  MODIFY `employer_document_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `employer_profiles`
--
ALTER TABLE `employer_profiles`
  MODIFY `employer_profile_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `employer_subscriptions`
--
ALTER TABLE `employer_subscriptions`
  MODIFY `subscription_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `job_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=99;

--
-- AUTO_INCREMENT for table `job_categories`
--
ALTER TABLE `job_categories`
  MODIFY `job_category_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `job_images`
--
ALTER TABLE `job_images`
  MODIFY `job_image_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT for table `job_invitations`
--
ALTER TABLE `job_invitations`
  MODIFY `job_invitation_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `job_promotions`
--
ALTER TABLE `job_promotions`
  MODIFY `promotion_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `legacy_standalone_job_promotions`
--
ALTER TABLE `legacy_standalone_job_promotions`
  MODIFY `promotion_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=190;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `review_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `review_reports`
--
ALTER TABLE `review_reports`
  MODIFY `review_report_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `skills`
--
ALTER TABLE `skills`
  MODIFY `skill_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=258;

--
-- AUTO_INCREMENT for table `skill_categories`
--
ALTER TABLE `skill_categories`
  MODIFY `skill_category_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `subscription_plans`
--
ALTER TABLE `subscription_plans`
  MODIFY `plan_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT for table `worker_profiles`
--
ALTER TABLE `worker_profiles`
  MODIFY `worker_profile_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `work_interests`
--
ALTER TABLE `work_interests`
  MODIFY `work_interest_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `applications`
--
ALTER TABLE `applications`
  ADD CONSTRAINT `fk_application_job` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`job_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_application_worker` FOREIGN KEY (`worker_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `auth_tokens`
--
ALTER TABLE `auth_tokens`
  ADD CONSTRAINT `fk_auth_token_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

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
-- Constraints for table `employer_subscriptions`
--
ALTER TABLE `employer_subscriptions`
  ADD CONSTRAINT `fk_subscription_employer` FOREIGN KEY (`employer_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_subscription_plan` FOREIGN KEY (`plan_id`) REFERENCES `subscription_plans` (`plan_id`),
  ADD CONSTRAINT `fk_subscription_reviewer` FOREIGN KEY (`reviewed_by_user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `jobs`
--
ALTER TABLE `jobs`
  ADD CONSTRAINT `fk_job_category` FOREIGN KEY (`job_category_id`) REFERENCES `job_categories` (`job_category_id`),
  ADD CONSTRAINT `fk_job_employer` FOREIGN KEY (`employer_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_job_work_interest` FOREIGN KEY (`work_interest_id`) REFERENCES `work_interests` (`work_interest_id`) ON DELETE SET NULL;

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
-- Constraints for table `job_promotions`
--
ALTER TABLE `job_promotions`
  ADD CONSTRAINT `fk_job_promotion_employer` FOREIGN KEY (`employer_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_job_promotion_job` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`job_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_job_promotion_subscription` FOREIGN KEY (`subscription_id`) REFERENCES `employer_subscriptions` (`subscription_id`) ON DELETE CASCADE;

--
-- Constraints for table `job_skills`
--
ALTER TABLE `job_skills`
  ADD CONSTRAINT `fk_job_skill_job` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`job_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_job_skill_skill` FOREIGN KEY (`skill_id`) REFERENCES `skills` (`skill_id`) ON DELETE CASCADE;

--
-- Constraints for table `job_worker_matches`
--
ALTER TABLE `job_worker_matches`
  ADD CONSTRAINT `fk_job_worker_matches_job` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`job_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_job_worker_matches_worker` FOREIGN KEY (`worker_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notification_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `fk_review_application` FOREIGN KEY (`application_id`) REFERENCES `applications` (`application_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_review_moderator` FOREIGN KEY (`moderated_by_user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_review_reviewee` FOREIGN KEY (`reviewee_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_review_reviewer` FOREIGN KEY (`reviewer_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `review_reports`
--
ALTER TABLE `review_reports`
  ADD CONSTRAINT `fk_review_report_reporter` FOREIGN KEY (`reporter_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_review_report_resolver` FOREIGN KEY (`resolved_by_user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_review_report_review` FOREIGN KEY (`review_id`) REFERENCES `reviews` (`review_id`) ON DELETE CASCADE;

--
-- Constraints for table `skills`
--
ALTER TABLE `skills`
  ADD CONSTRAINT `fk_skill_category` FOREIGN KEY (`skill_category_id`) REFERENCES `skill_categories` (`skill_category_id`) ON DELETE SET NULL;

--
-- Constraints for table `skill_consolidation_map`
--
ALTER TABLE `skill_consolidation_map`
  ADD CONSTRAINT `fk_skill_map_broad` FOREIGN KEY (`broad_skill_id`) REFERENCES `skills` (`skill_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_skill_map_legacy` FOREIGN KEY (`legacy_skill_id`) REFERENCES `skills` (`skill_id`) ON DELETE CASCADE;

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

--
-- Constraints for table `worker_work_interests`
--
ALTER TABLE `worker_work_interests`
  ADD CONSTRAINT `fk_worker_work_interest_interest` FOREIGN KEY (`work_interest_id`) REFERENCES `work_interests` (`work_interest_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_worker_work_interest_worker` FOREIGN KEY (`worker_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
