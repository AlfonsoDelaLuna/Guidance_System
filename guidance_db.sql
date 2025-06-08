-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 08, 2025 at 07:09 AM
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
-- Database: `guidance_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `college_students`
--

CREATE TABLE `college_students` (
  `id` int(11) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `student_id_no` varchar(20) NOT NULL,
  `year_level` int(11) NOT NULL,
  `program` varchar(50) NOT NULL,
  `section` varchar(10) DEFAULT NULL,
  `academic_career` varchar(50) DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL,
  `father_name` varchar(50) DEFAULT NULL,
  `mother_name` varchar(50) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `email_addresses` varchar(100) DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `nationality` varchar(50) DEFAULT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `religion` varchar(50) DEFAULT NULL,
  `civil_status` varchar(20) DEFAULT NULL,
  `emergency_contact_name` varchar(50) DEFAULT NULL,
  `emergency_contact_number` varchar(20) DEFAULT NULL,
  `has_siblings` tinyint(1) DEFAULT NULL,
  `present_address` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `shs_students`
--

CREATE TABLE `shs_students` (
  `id` int(11) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `student_id_no` varchar(20) NOT NULL,
  `grade_level` int(11) DEFAULT NULL,
  `shs_program` varchar(50) DEFAULT NULL,
  `section` varchar(10) DEFAULT NULL,
  `academic_career` varchar(50) DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL,
  `father_name` varchar(50) DEFAULT NULL,
  `mother_name` varchar(50) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `email_addresses` varchar(100) DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `nationality` varchar(50) DEFAULT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `religion` varchar(50) DEFAULT NULL,
  `civil_status` varchar(20) DEFAULT NULL,
  `emergency_contact_name` varchar(50) DEFAULT NULL,
  `emergency_contact_number` varchar(20) DEFAULT NULL,
  `has_siblings` tinyint(1) DEFAULT NULL,
  `present_address` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password_hash`, `created_at`) VALUES
(1, 'guidance', '$2y$10$OZv7cleLga2qOfGCMTBPBOtlmg7miOwYqZom3qYsC8/sFFD1q3KDq', '2025-05-08 16:06:15'),
(2, 'guidance2', '$2y$10$ihC6bx9dh2ueL8Jf6H6skOfUlAAuQm/NGWboQH8h2.jPMuNHWlCf2', '2025-05-08 16:07:27');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `college_students`
--
ALTER TABLE `college_students`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `shs_students`
--
ALTER TABLE `shs_students`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `college_students`
--
ALTER TABLE `college_students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=357;

--
-- AUTO_INCREMENT for table `shs_students`
--
ALTER TABLE `shs_students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=95;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
