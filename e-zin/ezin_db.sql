-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 24, 2025 at 03:50 AM
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
-- Database: `ezin_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `id` int(11) NOT NULL,
  `user_id` varchar(50) DEFAULT NULL,
  `activity` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` int(11) NOT NULL,
  `user_id` varchar(50) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `role` enum('lecturer','staff','student') DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `user_id`, `name`, `email`, `subject`, `role`, `phone`, `department`, `status`, `created_at`) VALUES
(3, '#LECJL20241115ABCD', 'Julio Lekardianist', 'lekardianist@gmail.com', 'PBO Teknologi Rekayasa Multimedia', 'lecturer', NULL, 'Computer Science', 'active', '2025-12-17 20:38:28'),
(4, '#LECMH20241115EFGH', 'Muhammad Hanif', 'hanif@gmail.com', 'Teknik Penulisan Ilmiah Desain Grafis', 'lecturer', NULL, 'Communication', 'active', '2025-12-17 20:38:28'),
(5, '#STFFS20241115IJKL', 'Fizard Surya', 'jarot@gmail.com', 'Office Boy', 'staff', NULL, 'General Affairs', 'active', '2025-12-17 20:38:28'),
(6, '#STUAL20241115MNOP', 'Alexander Graham', 'alex@student.edu', 'Backend Engineer Part time', 'student', NULL, 'Information Technology', 'active', '2025-12-17 20:38:28'),
(7, '#LECJLASJB4KDA', 'Julio Lekardianist', 'lekardianist@ezin.com', 'Rekayasa Perangkat Lunak', 'lecturer', '0812345678', 'Computer Science', 'active', '2025-12-17 20:38:32'),
(8, '#LECAA251218USTS', 'Alyafii Adnanda Prames', 'alyafi@ezin.com', 'Rekayasa Perangkat Lunak', 'lecturer', '0812345678', 'Computer Science', 'active', '2025-12-17 20:42:37'),
(12, '#STU004', 'Muhammad Azan', 'azan@student.edu', NULL, 'student', NULL, 'Business', 'active', '2025-12-17 20:54:08'),
(13, 'stu001', 'John Doe', 'john@student.edu', 'Information Technology', 'student', NULL, 'Computer Science', 'active', '2025-12-17 21:06:29'),
(14, 'stu002', 'Jane Smith', 'jane@student.edu', 'Civil Engineering', 'student', NULL, 'Engineering', 'active', '2025-12-17 21:06:29'),
(15, 'lec001', 'tes', 'lecturer@university.edu', 'Data Structures', 'lecturer', '', 'Computer Science', 'active', '2025-12-17 21:06:29'),
(16, 'stf001', 'Sarah Johnson', 'staff@university.edu', 'Office Manager', 'staff', NULL, 'Administration', 'active', '2025-12-17 21:06:29');

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` int(11) NOT NULL,
  `user_id` varchar(50) DEFAULT NULL,
  `lecturer_id` varchar(50) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `role` enum('lecturer','staff','student') DEFAULT NULL,
  `permission_type` varchar(100) DEFAULT NULL,
  `detail_permission` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `attachment_file` varchar(255) DEFAULT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `user_id`, `name`, `role`, `permission_type`, `detail_permission`, `status`, `created_at`, `start_date`, `end_date`, `attachment_file`, `file_type`, `file_size`) VALUES
(1, 'stu001', 'John Doe', 'student', 'Sakit', 'Saya ingin mengajukan izin sakit karena [sebutkan penyakit].\r\nLama izin: [sebutkan durasi].\r\nDokumen pendukung: [sebutkan jika ada].', 'rejected', '2025-12-17 21:10:20', '2025-12-18', '2025-12-19', NULL, NULL, NULL),
(2, 'stu001', 'John Doe', 'student', 'Izin', 'Saya ingin mengajukan izin untuk [sebutkan keperluan].\r\nTanggal: [sebutkan tanggal].\r\nLama izin: [sebutkan durasi].', 'approved', '2025-12-17 21:11:45', '2025-12-18', '2025-12-19', NULL, NULL, NULL),
(3, 'stu001', 'John Doe', 'student', 'Cuti', 'Saya ingin mengajukan cuti untuk [sebutkan alasan].\r\nPeriode: [sebutkan periode].\r\nJumlah hari: [sebutkan jumlah hari].', 'approved', '2025-12-17 21:30:41', '2025-12-18', '2025-12-27', NULL, NULL, NULL),
(5, 'stu001', 'John Doe', 'student', 'Sakit', 'Saya ingin mengajukan izin sakit karena [sebutkan penyakit].\r\nLama izin: [sebutkan durasi].\r\nDokumen pendukung: [sebutkan jika ada].', 'pending', '2025-12-17 22:00:11', '2025-12-17', '2025-12-17', 'permission_stu001_1766008811.png', 'image/png', 72109);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `user_id` varchar(50) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` enum('admin','lecturer','staff','student') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('active','inactive') DEFAULT 'active',
  `phone` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `user_id`, `name`, `email`, `password`, `role`, `created_at`, `status`, `phone`) VALUES
(1, 'admin001', 'System Admin', 'admin@ezin.com', '0192023a7bbd73250516f069df18b500', 'admin', '2025-12-17 20:20:04', 'active', '081234567890'),
(3, '#LECJL20241115ABCD', 'Julio Lekardianist', 'lekardianist@gmail.com', '482c811da5d5b4bc6d497ffa98491e38', 'lecturer', '2025-12-17 20:38:28', 'active', NULL),
(4, '#LECMH20241115EFGH', 'Muhammad Hanif', 'hanif@gmail.com', '482c811da5d5b4bc6d497ffa98491e38', 'lecturer', '2025-12-17 20:38:28', 'active', NULL),
(5, '#STFFS20241115IJKL', 'Fizard Surya', 'jarot@gmail.com', '482c811da5d5b4bc6d497ffa98491e38', 'staff', '2025-12-17 20:38:28', 'active', NULL),
(6, '#STUAL20241115MNOP', 'Alexander Graham', 'alex@student.edu', '482c811da5d5b4bc6d497ffa98491e38', 'student', '2025-12-17 20:38:28', 'active', NULL),
(7, '#LECJLASJB4KDA', 'Julio Lekardianist', 'lekardianist@ezin.com', '482c811da5d5b4bc6d497ffa98491e38', 'lecturer', '2025-12-17 20:38:32', 'active', NULL),
(8, '#LECAA251218USTS', 'Alyafii Adnanda Prames', 'alyafi@ezin.com', '482c811da5d5b4bc6d497ffa98491e38', 'lecturer', '2025-12-17 20:42:37', 'active', NULL),
(12, '#STU004', 'Muhammad Azan', 'azan@student.edu', 'ad6a280417a0f533d8b670c61667e1a0', 'student', '2025-12-17 20:54:08', 'active', NULL),
(13, 'stu001', 'John Doe', 'john@student.edu', 'ad6a280417a0f533d8b670c61667e1a0', 'student', '2025-12-17 21:06:29', 'active', NULL),
(14, 'stu002', 'Jane Smith', 'jane@student.edu', 'ad6a280417a0f533d8b670c61667e1a0', 'student', '2025-12-17 21:06:29', 'active', NULL),
(15, 'lec001', 'tes', 'lecturer@university.edu', 'e9f37ab3a738c4704a5d6035166d75d3', 'lecturer', '2025-12-17 21:06:29', 'active', NULL),
(16, 'stf001', 'Sarah Johnson', 'staff@university.edu', 'de9bf5643eabf80f4a56fda3bbb84483', 'staff', '2025-12-17 21:06:29', 'active', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_user_id` (`user_id`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD CONSTRAINT `activity_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `employees`
--
ALTER TABLE `employees`
  ADD CONSTRAINT `fk_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `permissions`
--
ALTER TABLE `permissions`
  ADD CONSTRAINT `permissions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
