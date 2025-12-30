-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 30 Des 2025 pada 09.19
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

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
-- Struktur dari tabel `activity_log`
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
-- Struktur dari tabel `employees`
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
-- Dumping data untuk tabel `employees`
--

INSERT INTO `employees` (`id`, `user_id`, `name`, `email`, `subject`, `role`, `phone`, `department`, `status`, `created_at`) VALUES
(5, '#STFFS20241115IJKL', 'Fizard Surya', 'jarot@gmail.com', 'Office Boy', 'staff', NULL, 'General Affairs', 'active', '2025-12-17 20:38:28'),
(6, '#STUAL20241115MNOP', 'Alexander Graham', 'alex@student.edu', 'Backend Engineer Part time', 'student', NULL, 'Information Technology', 'active', '2025-12-17 20:38:28'),
(12, '#STU004', 'Muhammad Azan', 'azan@student.edu', 'Teknologi Rekayasa Multimedia', 'student', '0812345678121123', 'Desain Grafis', 'active', '2025-12-17 20:54:08'),
(13, 'stu001', 'tes', 'john@student.edu', 'Information Technology', 'student', NULL, 'Computer Science', 'active', '2025-12-17 21:06:29'),
(14, 'stu002', 'Jane Smith', 'jane@student.edu', 'Civil Engineering', 'student', NULL, 'Engineering', 'active', '2025-12-17 21:06:29'),
(16, 'stf001', 'Sarah Johnson', 'staff@university.edu', 'Office Manager', 'staff', NULL, 'Administration', 'active', '2025-12-17 21:06:29'),
(21, '#LECDO251229JQCM', 'dosen1', 'dosen@ezin.com', 'Teknologi Rekayasa Multimedia', 'lecturer', '08123456789', 'Computer Science', 'active', '2025-12-29 15:08:05'),
(22, '#LECLE251230HPWB', 'Lecturer', 'lecturer@university.edu', 'Nirmana Dwimatra', 'lecturer', '08121382358764', 'Desain Grafis', 'active', '2025-12-30 07:12:10');

-- --------------------------------------------------------

--
-- Struktur dari tabel `permissions`
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
  `file_size` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `permissions`
--

INSERT INTO `permissions` (`id`, `user_id`, `lecturer_id`, `name`, `role`, `permission_type`, `detail_permission`, `status`, `created_at`, `start_date`, `end_date`, `attachment_file`, `file_type`, `file_size`) VALUES
(12, 'stu001', '#LECLE251230HPWB', 'tes', 'student', 'Izin', 'Saya ingin mengajukan izin untuk [sebutkan keperluan].\r\nTanggal: [sebutkan tanggal].\r\nLama izin: [sebutkan durasi].', 'approved', '2025-12-30 07:20:50', '2025-12-30', '2025-12-30', 'permission_stu001_1767079250.jpg', 'image/jpeg', 760374);

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
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
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `user_id`, `name`, `email`, `password`, `role`, `created_at`, `status`, `phone`) VALUES
(1, 'admin001', 'System Admin', 'admin@ezin.com', '0192023a7bbd73250516f069df18b500', 'admin', '2025-12-17 20:20:04', 'active', '081234567890'),
(5, '#STFFS20241115IJKL', 'Fizard Surya', 'jarot@gmail.com', '482c811da5d5b4bc6d497ffa98491e38', 'staff', '2025-12-17 20:38:28', 'active', NULL),
(6, '#STUAL20241115MNOP', 'Alexander Graham', 'alex@student.edu', '482c811da5d5b4bc6d497ffa98491e38', 'student', '2025-12-17 20:38:28', 'active', NULL),
(12, '#STU004', 'Muhammad Azan', 'azan@student.edu', 'ad6a280417a0f533d8b670c61667e1a0', 'student', '2025-12-17 20:54:08', 'active', NULL),
(13, 'stu001', 'tes', 'john@student.edu', 'ad6a280417a0f533d8b670c61667e1a0', 'student', '2025-12-17 21:06:29', 'active', ''),
(14, 'stu002', 'Jane Smith', 'jane@student.edu', 'ad6a280417a0f533d8b670c61667e1a0', 'student', '2025-12-17 21:06:29', 'active', NULL),
(16, 'stf001', 'Sarah Johnson', 'staff@university.edu', 'de9bf5643eabf80f4a56fda3bbb84483', 'staff', '2025-12-17 21:06:29', 'active', NULL),
(20, '#LECDO251229JQCM', 'dosen1', 'dosen@ezin.com', 'e9f37ab3a738c4704a5d6035166d75d3', 'lecturer', '2025-12-29 15:08:05', 'active', NULL),
(21, '#LECLE251230HPWB', 'Lecturer', 'lecturer@university.edu', 'e9f37ab3a738c4704a5d6035166d75d3', 'lecturer', '2025-12-30 07:12:10', 'active', NULL);

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_user_id` (`user_id`);

--
-- Indeks untuk tabel `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `permissions_ibfk_lecturer` (`lecturer_id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT untuk tabel `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `activity_log`
--
ALTER TABLE `activity_log`
  ADD CONSTRAINT `activity_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `employees`
--
ALTER TABLE `employees`
  ADD CONSTRAINT `fk_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `permissions`
--
ALTER TABLE `permissions`
  ADD CONSTRAINT `permissions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `permissions_ibfk_lecturer` FOREIGN KEY (`lecturer_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
