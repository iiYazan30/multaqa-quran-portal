-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 18, 2026 at 10:48 PM
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
-- Database: `multaqadb`
--

-- --------------------------------------------------------

--
-- Table structure for table `colleges`
--

CREATE TABLE `colleges` (
  `college_id` int(11) NOT NULL,
  `college_name` varchar(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `colleges`
--

INSERT INTO `colleges` (`college_id`, `college_name`) VALUES
(1, 'كلية الهندسة وتكنولوجيا المعلومات');

-- --------------------------------------------------------

--
-- Table structure for table `exams`
--

CREATE TABLE `exams` (
  `exam_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `part_name` varchar(100) NOT NULL,
  `score` decimal(5,2) NOT NULL,
  `grade` varchar(50) DEFAULT NULL,
  `exam_date` date NOT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `exams`
--

INSERT INTO `exams` (`exam_id`, `student_id`, `part_name`, `score`, `grade`, `exam_date`, `notes`) VALUES
(1, 1, 'الجزء الأول', 92.50, 'ممتاز', '2026-05-01', 'أداء ممتاز'),
(2, 2, 'الجزء الثاني', 85.00, 'جيد جداً', '2026-05-03', 'جيد'),
(3, 3, 'الجزء الأول', 96.00, 'ممتاز', '2026-05-05', 'متميز'),
(4, 1, 'الجزء الثاني', 88.00, 'جيد جداً', '2026-05-15', 'أداء جيد مع بعض الملاحظات'),
(5, 1, 'الجزء الثالث', 95.00, 'ممتاز', '2026-05-28', 'إتقان واضح'),
(6, 1, 'الجزء الرابع', 90.00, 'ممتاز', '2026-06-10', 'تحسن في ضبط الأحكام'),
(7, 1, 'الجزء الخامس', 84.00, 'جيد جداً', '2026-06-22', 'بحاجة لمراجعة إضافية'),
(8, 2, 'الجزء الأول', 78.00, 'جيد', '2026-05-18', 'يحتاج مراجعة'),
(9, 2, 'الجزء الثاني', 82.00, 'جيد جداً', '2026-06-02', 'تحسن ملحوظ'),
(10, 3, 'الجزء الأول', 61.00, 'مقبول', '2026-05-20', 'بحاجة متابعة'),
(11, 4, 'الجزء الأول', 91.00, 'ممتاز', '2026-05-25', 'أداء قوي'),
(12, 4, 'الجزء الثاني', 87.00, 'جيد جداً', '2026-06-12', 'جيد جداً'),
(13, 5, 'الجزء الأول', 79.00, 'جيد', '2026-05-30', 'مستوى متوسط'),
(14, 6, 'الجزء الأول', 65.00, 'مقبول', '2026-06-05', 'بحاجة لمراجعة'),
(15, 7, 'الجزء السادس', 94.00, 'ممتاز', '2026-06-20', 'أداء عال'),
(16, 8, 'الجزء الرابع', 82.00, 'جيد جداً', '2026-06-21', 'جيد'),
(17, 11, 'الجزء السابع', 97.00, 'ممتاز', '2026-06-22', 'متميز'),
(18, 12, 'الجزء الخامس', 91.00, 'ممتاز', '2026-06-23', 'إتقان واضح'),
(19, 13, 'الجزء الثالث', 73.00, 'جيد', '2026-06-24', 'يحتاج مراجعة'),
(20, 14, 'الجزء الرابع', 86.00, 'جيد جداً', '2026-06-25', 'جيد جداً'),
(21, 16, 'الجزء الثامن', 89.00, 'جيد جداً', '2026-06-26', 'ثابت'),
(22, 17, 'الجزء الثالث', 84.00, 'جيد جداً', '2026-06-27', 'تحسن واضح'),
(23, 18, 'الجزء الأول', 58.00, 'مقبول', '2026-06-28', 'بحاجة متابعة'),
(24, 22, 'الجزء التاسع', 96.00, 'ممتاز', '2026-06-29', 'أداء قوي'),
(25, 24, 'الجزء الرابع', 88.00, 'جيد جداً', '2026-06-30', 'جيد'),
(26, 26, 'الجزء السادس', 92.00, 'ممتاز', '2026-07-01', 'ممتاز'),
(27, 27, 'الجزء الثاني', 81.00, 'جيد جداً', '2026-07-02', 'جيد'),
(28, 29, 'الجزء الخامس', 95.00, 'ممتاز', '2026-07-03', 'متميز'),
(29, 30, 'الجزء الأول', 60.00, 'مقبول', '2026-07-04', 'ضعيف');

-- --------------------------------------------------------

--
-- Table structure for table `exam_requests`
--

CREATE TABLE `exam_requests` (
  `request_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `requested_part` varchar(100) NOT NULL,
  `status` enum('pending','approved','rejected','completed') DEFAULT 'pending',
  `request_date` date NOT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `exam_requests`
--

INSERT INTO `exam_requests` (`request_id`, `student_id`, `requested_part`, `status`, `request_date`, `notes`) VALUES
(1, 1, 'الجزء الثاني', 'pending', '2026-05-10', 'جاهز للاختبار'),
(2, 2, 'الجزء الثالث', 'approved', '2026-05-11', 'تمت الموافقة'),
(3, 3, 'الجزء الثاني', 'completed', '2026-05-12', 'تم الاختبار'),
(4, 2, 'الجزء الثالث', 'pending', '2026-06-10', 'بانتظار الموافقة'),
(5, 3, 'الجزء الثاني', 'rejected', '2026-06-01', 'يحتاج تحسين المستوى'),
(6, 4, 'الجزء الثالث', 'pending', '2026-06-18', 'جاهز للاختبار'),
(7, 5, 'الجزء الثاني', 'approved', '2026-06-17', 'تمت الموافقة'),
(8, 6, 'الجزء الثاني', 'rejected', '2026-06-16', 'يحتاج تحسين قبل الاختبار'),
(9, 7, 'الجزء السابع', 'pending', '2026-07-05', 'جاهز للاختبار'),
(10, 8, 'الجزء الخامس', 'approved', '2026-07-05', 'تمت الموافقة'),
(11, 10, 'الجزء الثاني', 'rejected', '2026-07-06', 'بحاجة لمزيد من المراجعة'),
(12, 12, 'الجزء السادس', 'completed', '2026-07-06', 'تم الاختبار'),
(13, 13, 'الجزء الرابع', 'pending', '2026-07-07', 'بانتظار تحديد الموعد'),
(14, 15, 'الجزء الثاني', 'rejected', '2026-07-07', 'المستوى غير كافٍ'),
(15, 17, 'الجزء الرابع', 'approved', '2026-07-08', 'تمت الموافقة'),
(16, 18, 'الجزء الثاني', 'rejected', '2026-07-08', 'يحتاج متابعة'),
(17, 21, 'الجزء الأول', 'pending', '2026-07-09', 'طلب جديد'),
(18, 22, 'الجزء العاشر', 'completed', '2026-07-09', 'تم الاختبار'),
(19, 25, 'الجزء الثالث', 'pending', '2026-07-10', 'بانتظار المراجعة'),
(20, 29, 'الجزء السادس', 'approved', '2026-07-10', 'جاهز');

-- --------------------------------------------------------

--
-- Table structure for table `halqas`
--

CREATE TABLE `halqas` (
  `halqa_id` int(11) NOT NULL,
  `college_id` int(11) NOT NULL,
  `supervisor_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `halqas`
--

INSERT INTO `halqas` (`halqa_id`, `college_id`, `supervisor_id`, `name`) VALUES
(1, 1, 1, 'حلقة النور'),
(2, 1, 2, 'حلقة الفجر'),
(3, 1, 3, 'حلقة الهدى'),
(4, 1, 4, 'حلقة الفرقان'),
(5, 1, 5, 'حلقة الإحسان'),
(6, 1, 6, 'حلقة الإتقان');

-- --------------------------------------------------------

--
-- Table structure for table `managers`
--

CREATE TABLE `managers` (
  `manager_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `college_id` int(11) NOT NULL,
  `full_name` varchar(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `managers`
--

INSERT INTO `managers` (`manager_id`, `user_id`, `college_id`, `full_name`) VALUES
(1, 1, 1, 'أحمد مسؤول الكلية');

-- --------------------------------------------------------

--
-- Table structure for table `recitations`
--

CREATE TABLE `recitations` (
  `recitation_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `week_id` int(11) NOT NULL,
  `type` enum('حفظ','مراجعة') NOT NULL,
  `from_page` varchar(100) NOT NULL,
  `to_page` varchar(100) NOT NULL,
  `pages_count` int(11) NOT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `recitations`
--

INSERT INTO `recitations` (`recitation_id`, `student_id`, `week_id`, `type`, `from_page`, `to_page`, `pages_count`, `notes`) VALUES
(1, 1, 1, 'حفظ', '1', '5', 5, 'تسميع جيد'),
(2, 1, 2, 'مراجعة', '6', '10', 5, 'بحاجة لمراجعة بسيطة'),
(3, 2, 1, 'مراجعة', '11', '15', 5, 'ممتاز'),
(4, 3, 3, 'حفظ', '16', '22', 7, 'أداء قوي'),
(5, 1, 3, 'حفظ', '11', '16', 6, 'تحسن واضح في الحفظ'),
(6, 1, 3, 'مراجعة', '1', '6', 6, 'مراجعة جيدة'),
(7, 1, 4, 'حفظ', '17', '23', 7, 'أداء ممتاز'),
(8, 1, 4, 'مراجعة', '7', '12', 6, 'ثبات جيد'),
(9, 1, 5, 'حفظ', '24', '30', 7, 'تسميع متقن'),
(10, 1, 5, 'مراجعة', '13', '20', 8, 'مراجعة قوية'),
(11, 1, 6, 'حفظ', '31', '36', 6, 'بحاجة لتركيز بسيط'),
(12, 1, 6, 'مراجعة', '21', '28', 8, 'جيد جداً'),
(13, 1, 7, 'حفظ', '37', '44', 8, 'ممتاز'),
(14, 1, 7, 'مراجعة', '29', '36', 8, 'متقن'),
(15, 1, 8, 'حفظ', '45', '52', 8, 'أداء قوي'),
(16, 1, 8, 'مراجعة', '37', '45', 9, 'مراجعة ممتازة'),
(17, 2, 2, 'مراجعة', '16', '20', 5, 'مراجعة جيدة'),
(18, 2, 3, 'حفظ', '21', '24', 4, 'أداء متوسط'),
(19, 2, 4, 'مراجعة', '25', '30', 6, 'بحاجة لتركيز'),
(20, 2, 5, 'حفظ', '31', '34', 4, 'تحسن بسيط'),
(21, 3, 1, 'مراجعة', '1', '3', 3, 'ضعيف'),
(27, 4, 1, 'حفظ', '1', '6', 6, 'بداية ممتازة'),
(28, 4, 2, 'حفظ', '7', '13', 7, 'تسميع متقن'),
(29, 4, 3, 'مراجعة', '1', '10', 10, 'مراجعة قوية'),
(30, 4, 4, 'حفظ', '14', '20', 7, 'تقدم واضح'),
(31, 5, 1, 'مراجعة', '1', '5', 5, 'جيد'),
(32, 5, 2, 'حفظ', '6', '9', 4, 'بحاجة لتركيز'),
(33, 5, 4, 'مراجعة', '10', '15', 6, 'تحسن بسيط'),
(34, 6, 1, 'حفظ', '1', '2', 2, 'تسميع قليل'),
(35, 6, 2, 'مراجعة', '1', '3', 3, 'يحتاج متابعة'),
(36, 2, 8, 'حفظ', '5', '15', 11, ''),
(37, 7, 6, 'حفظ', '53', '60', 8, 'ممتاز'),
(38, 7, 7, 'مراجعة', '40', '50', 11, 'مراجعة قوية'),
(39, 7, 8, 'حفظ', '61', '68', 8, 'متقن'),
(40, 8, 5, 'مراجعة', '26', '32', 7, 'جيد'),
(41, 8, 7, 'مراجعة', '33', '40', 8, 'ثابت'),
(42, 9, 6, 'حفظ', '15', '20', 6, 'تحسن واضح'),
(43, 9, 8, 'حفظ', '21', '27', 7, 'جيد جداً'),
(44, 10, 5, 'مراجعة', '1', '4', 4, 'بحاجة متابعة'),
(45, 11, 7, 'حفظ', '70', '78', 9, 'أداء قوي'),
(46, 11, 8, 'مراجعة', '55', '65', 11, 'ممتاز'),
(47, 12, 6, 'حفظ', '44', '50', 7, 'ممتاز'),
(48, 12, 8, 'حفظ', '51', '58', 8, 'إتقان واضح'),
(49, 13, 4, 'مراجعة', '10', '14', 5, 'متوسط'),
(50, 13, 6, 'مراجعة', '15', '19', 5, 'يحتاج تثبيت'),
(51, 14, 7, 'حفظ', '30', '36', 7, 'جيد'),
(52, 14, 8, 'مراجعة', '20', '28', 9, 'مراجعة ممتازة'),
(53, 15, 5, 'مراجعة', '5', '8', 4, 'ضعيف'),
(54, 16, 6, 'مراجعة', '80', '88', 9, 'ثابت'),
(55, 16, 8, 'مراجعة', '89', '96', 8, 'جيد جداً'),
(56, 17, 7, 'حفظ', '25', '31', 7, 'جيد'),
(57, 17, 8, 'حفظ', '32', '38', 7, 'تحسن'),
(58, 18, 3, 'مراجعة', '1', '3', 3, 'يحتاج متابعة'),
(59, 19, 6, 'حفظ', '10', '15', 6, 'جيد'),
(60, 19, 8, 'مراجعة', '1', '8', 8, 'ممتاز'),
(61, 20, 5, 'حفظ', '18', '22', 5, 'متوسط'),
(62, 21, 2, 'مراجعة', '1', '2', 2, 'ضعيف'),
(63, 22, 6, 'حفظ', '90', '98', 9, 'ممتاز'),
(64, 22, 8, 'حفظ', '99', '108', 10, 'قوي جداً'),
(65, 23, 5, 'مراجعة', '45', '50', 6, 'جيد'),
(66, 23, 7, 'مراجعة', '51', '57', 7, 'ثابت'),
(67, 24, 6, 'حفظ', '35', '42', 8, 'جيد جداً'),
(68, 24, 8, 'مراجعة', '22', '30', 9, 'مراجعة جيدة'),
(69, 25, 4, 'مراجعة', '9', '13', 5, 'بحاجة تركيز'),
(70, 26, 7, 'حفظ', '60', '66', 7, 'متقن'),
(71, 26, 8, 'حفظ', '67', '74', 8, 'ممتاز'),
(72, 27, 6, 'حفظ', '12', '18', 7, 'جيد'),
(73, 27, 8, 'مراجعة', '1', '10', 10, 'مراجعة قوية'),
(74, 28, 5, 'مراجعة', '20', '25', 6, 'متوسط'),
(75, 29, 7, 'حفظ', '40', '47', 8, 'أداء ممتاز'),
(76, 29, 8, 'حفظ', '48', '55', 8, 'ممتاز'),
(77, 30, 3, 'مراجعة', '1', '2', 2, 'ضعيف جداً'),
(78, 31, 6, 'حفظ', '75', '82', 8, 'قوي'),
(79, 31, 8, 'مراجعة', '60', '70', 11, 'متقن');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `role_id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`role_id`, `role_name`) VALUES
(1, 'manager'),
(3, 'student'),
(2, 'supervisor');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `student_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `halqa_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `student_type` enum('حفظ','تثبيت') NOT NULL,
  `points` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`student_id`, `user_id`, `halqa_id`, `name`, `phone`, `student_type`, `points`) VALUES
(1, 3, 1, 'عمر خالد', '0599111111', 'حفظ', 120),
(2, 4, 1, 'عبدالله سمير', '0599222222', 'تثبيت', 90),
(3, 5, 1, 'يوسف أحمد', '0599333333', 'حفظ', 150),
(4, 6, 1, 'محمود علي', '0599444444', 'حفظ', 110),
(5, 7, 1, 'أنس وليد', '0599555555', 'تثبيت', 75),
(6, 8, 1, 'مالك طه', '0599666666', 'حفظ', 45),
(7, 14, 2, 'عبد الله نزار', '0599110001', 'حفظ', 185),
(8, 15, 2, 'يوسف زاهد', '0599110002', 'تثبيت', 144),
(9, 16, 2, 'رامي حسن', '0599110003', 'حفظ', 132),
(10, 17, 2, 'باسل محمود', '0599110004', 'تثبيت', 98),
(11, 18, 2, 'حسام طاهر', '0599110005', 'حفظ', 156),
(12, 19, 3, 'أنس خليل', '0599220001', 'حفظ', 172),
(13, 20, 3, 'عمر سامر', '0599220002', 'تثبيت', 88),
(14, 21, 3, 'ليث أحمد', '0599220003', 'حفظ', 146),
(15, 22, 3, 'مؤمن زياد', '0599220004', 'تثبيت', 121),
(16, 23, 4, 'محمد أسامة', '0599330001', 'تثبيت', 168),
(17, 24, 4, 'إبراهيم فهد', '0599330002', 'حفظ', 139),
(18, 25, 4, 'سليم مازن', '0599330003', 'تثبيت', 74),
(19, 26, 4, 'يزن عادل', '0599330004', 'حفظ', 111),
(20, 27, 4, 'طارق أمين', '0599330005', 'حفظ', 93),
(21, 28, 4, 'عبد الله رائد', '0599330006', 'تثبيت', 64),
(22, 29, 5, 'سيف خالد', '0599440001', 'حفظ', 160),
(23, 30, 5, 'آدم ناصر', '0599440002', 'تثبيت', 107),
(24, 31, 5, 'مراد علاء', '0599440003', 'حفظ', 126),
(25, 32, 5, 'قصي وليد', '0599440004', 'تثبيت', 81),
(26, 33, 5, 'بلال حمدان', '0599440005', 'حفظ', 142),
(27, 34, 6, 'كريم شادي', '0599550001', 'حفظ', 118),
(28, 35, 6, 'أمير نضال', '0599550002', 'تثبيت', 96),
(29, 36, 6, 'صهيب مراد', '0599550003', 'حفظ', 133),
(30, 37, 6, 'مالك زين', '0599550004', 'تثبيت', 57),
(31, 38, 6, 'أوس جهاد', '0599550005', 'حفظ', 149);

-- --------------------------------------------------------

--
-- Table structure for table `supervisors`
--

CREATE TABLE `supervisors` (
  `supervisor_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `phone` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `supervisors`
--

INSERT INTO `supervisors` (`supervisor_id`, `user_id`, `name`, `phone`) VALUES
(1, 2, 'محمد مشرف الحلقة', '0599000000'),
(2, 9, 'أحمد يوسف', '0599123456'),
(3, 10, 'محمد صالح', '0599345678'),
(4, 11, 'عبد الرحمن علي', '0599567890'),
(5, 12, 'خالد مراد', '0599789012'),
(6, 13, 'سامر ناصر', '0599901234');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `role_id`) VALUES
(1, 'manager1', '123456', 1),
(2, 'supervisor1', '123456', 2),
(3, 'student1', '123456', 3),
(4, 'student2', '123456', 3),
(5, 'student3', '123456', 3),
(6, 'student4', '123456', 3),
(7, 'student5', '123456', 3),
(8, 'student6', '123456', 3),
(9, 'supervisor2', '123456', 2),
(10, 'supervisor3', '123456', 2),
(11, 'supervisor4', '123456', 2),
(12, 'supervisor5', '123456', 2),
(13, 'supervisor6', '123456', 2),
(14, 'student7', '123456', 3),
(15, 'student8', '123456', 3),
(16, 'student9', '123456', 3),
(17, 'student10', '123456', 3),
(18, 'student11', '123456', 3),
(19, 'student12', '123456', 3),
(20, 'student13', '123456', 3),
(21, 'student14', '123456', 3),
(22, 'student15', '123456', 3),
(23, 'student16', '123456', 3),
(24, 'student17', '123456', 3),
(25, 'student18', '123456', 3),
(26, 'student19', '123456', 3),
(27, 'student20', '123456', 3),
(28, 'student21', '123456', 3),
(29, 'student22', '123456', 3),
(30, 'student23', '123456', 3),
(31, 'student24', '123456', 3),
(32, 'student25', '123456', 3),
(33, 'student26', '123456', 3),
(34, 'student27', '123456', 3),
(35, 'student28', '123456', 3),
(36, 'student29', '123456', 3),
(37, 'student30', '123456', 3),
(38, 'student31', '123456', 3);

-- --------------------------------------------------------

--
-- Table structure for table `weeks`
--

CREATE TABLE `weeks` (
  `week_id` int(11) NOT NULL,
  `week_number` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `weeks`
--

INSERT INTO `weeks` (`week_id`, `week_number`) VALUES
(1, 1),
(2, 2),
(3, 3),
(4, 4),
(5, 5),
(6, 6),
(7, 7),
(8, 8);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `colleges`
--
ALTER TABLE `colleges`
  ADD PRIMARY KEY (`college_id`);

--
-- Indexes for table `exams`
--
ALTER TABLE `exams`
  ADD PRIMARY KEY (`exam_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `exam_requests`
--
ALTER TABLE `exam_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `halqas`
--
ALTER TABLE `halqas`
  ADD PRIMARY KEY (`halqa_id`),
  ADD UNIQUE KEY `supervisor_id` (`supervisor_id`),
  ADD KEY `college_id` (`college_id`);

--
-- Indexes for table `managers`
--
ALTER TABLE `managers`
  ADD PRIMARY KEY (`manager_id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `college_id` (`college_id`);

--
-- Indexes for table `recitations`
--
ALTER TABLE `recitations`
  ADD PRIMARY KEY (`recitation_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `week_id` (`week_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`role_id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`student_id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `halqa_id` (`halqa_id`);

--
-- Indexes for table `supervisors`
--
ALTER TABLE `supervisors`
  ADD PRIMARY KEY (`supervisor_id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `fk_users_roles` (`role_id`);

--
-- Indexes for table `weeks`
--
ALTER TABLE `weeks`
  ADD PRIMARY KEY (`week_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `colleges`
--
ALTER TABLE `colleges`
  MODIFY `college_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `exams`
--
ALTER TABLE `exams`
  MODIFY `exam_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `exam_requests`
--
ALTER TABLE `exam_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `halqas`
--
ALTER TABLE `halqas`
  MODIFY `halqa_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `managers`
--
ALTER TABLE `managers`
  MODIFY `manager_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `recitations`
--
ALTER TABLE `recitations`
  MODIFY `recitation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=80;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `supervisors`
--
ALTER TABLE `supervisors`
  MODIFY `supervisor_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `weeks`
--
ALTER TABLE `weeks`
  MODIFY `week_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `exams`
--
ALTER TABLE `exams`
  ADD CONSTRAINT `exams_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `exam_requests`
--
ALTER TABLE `exam_requests`
  ADD CONSTRAINT `exam_requests_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `halqas`
--
ALTER TABLE `halqas`
  ADD CONSTRAINT `halqas_ibfk_1` FOREIGN KEY (`college_id`) REFERENCES `colleges` (`college_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `halqas_ibfk_2` FOREIGN KEY (`supervisor_id`) REFERENCES `supervisors` (`supervisor_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `managers`
--
ALTER TABLE `managers`
  ADD CONSTRAINT `managers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `managers_ibfk_2` FOREIGN KEY (`college_id`) REFERENCES `colleges` (`college_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `recitations`
--
ALTER TABLE `recitations`
  ADD CONSTRAINT `recitations_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `recitations_ibfk_2` FOREIGN KEY (`week_id`) REFERENCES `weeks` (`week_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `students_ibfk_2` FOREIGN KEY (`halqa_id`) REFERENCES `halqas` (`halqa_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `supervisors`
--
ALTER TABLE `supervisors`
  ADD CONSTRAINT `supervisors_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_roles` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
