-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 16, 2025 at 05:21 PM
-- Server version: 8.0.42
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `employee_management`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `dob` varchar(15) DEFAULT NULL,
  `password` varchar(75) NOT NULL,
  `dp` varchar(255) NOT NULL,
  `dp_url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `name`, `email`, `gender`, `dob`, `password`, `dp`, `dp_url`) VALUES
(12, 'Mrinal Paul', 'admin1@admin.com', 'Male', '2004-12-03', '1234', '6850306c2ce1a.jpg', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `target_employee_id` int DEFAULT NULL,
  `admin_id` int NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`id`, `title`, `message`, `file_path`, `target_employee_id`, `admin_id`, `created_at`, `updated_at`) VALUES
(1, 'VP REFRACTORIES HIGH ALUMINA BRICKS - 50%', 'Demo', '../uploads/announcements/announcement_684ec3df3f3376.38172343.jpg', 13, 12, '2025-06-15 18:30:15', '2025-06-15 18:30:15');

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int NOT NULL,
  `employee_id` int NOT NULL,
  `date` date NOT NULL,
  `check_in` time DEFAULT NULL,
  `check_out` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `employee_id`, `date`, `check_in`, `check_out`) VALUES
(5, 13, '2025-06-13', '17:12:34', '17:12:36'),
(6, 13, '2025-06-15', '20:06:10', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `employee`
--

CREATE TABLE `employee` (
  `id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `dob` varchar(255) DEFAULT NULL,
  `password` varchar(75) NOT NULL,
  `salary` int NOT NULL,
  `dp` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `employee`
--

INSERT INTO `employee` (`id`, `name`, `email`, `gender`, `dob`, `password`, `salary`, `dp`) VALUES
(13, 'Sagun Kumar Behera', 'emp1@emp.com', 'Male', '1998-06-25', '1234', 121123, '68503448539977.62546641Sagun.jpg'),
(14, 'emp2', 'emp2@emp.com', 'Female', '1998-07-09', '1234', 3432423, '60df15c11c4505.59875888marco-mons-ROWNIiEV9iM-unsplash.png'),
(16, 'test', 'test@gmail.com', 'Male', '1998-06-19', 'asdf', 234, '60e047c12caa19.90869347profile.png');

-- --------------------------------------------------------

--
-- Table structure for table `employee_ratings`
--

CREATE TABLE `employee_ratings` (
  `id` int NOT NULL,
  `employee_id` int NOT NULL,
  `admin_id` int NOT NULL,
  `rating_score` int NOT NULL,
  `rating_comments` text,
  `rating_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ;

--
-- Dumping data for table `employee_ratings`
--

INSERT INTO `employee_ratings` (`id`, `employee_id`, `admin_id`, `rating_score`, `rating_comments`, `rating_date`) VALUES
(1, 13, 12, 4, 'Great work', '2025-06-15 13:18:30'),
(3, 14, 12, 2, 'hello', '2025-06-15 13:21:16');

-- --------------------------------------------------------

--
-- Table structure for table `employee_tasks`
--

CREATE TABLE `employee_tasks` (
  `id` int NOT NULL,
  `task_title` varchar(255) NOT NULL,
  `task_description` text,
  `employee_id` int NOT NULL,
  `admin_id` int NOT NULL,
  `deadline` datetime NOT NULL,
  `status` enum('Not Started','In Progress','Done') DEFAULT 'Not Started',
  `assigned_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `completed_date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `employee_tasks`
--

INSERT INTO `employee_tasks` (`id`, `task_title`, `task_description`, `employee_id`, `admin_id`, `deadline`, `status`, `assigned_date`, `completed_date`) VALUES
(1, 'Hello', 'Hi', 13, 12, '2025-06-15 19:30:00', 'Done', '2025-06-15 13:41:28', '2025-06-15 23:36:22');

-- --------------------------------------------------------

--
-- Table structure for table `emp_leave`
--

CREATE TABLE `emp_leave` (
  `id` int NOT NULL,
  `reason` varchar(500) NOT NULL,
  `start_date` varchar(24) NOT NULL,
  `last_date` varchar(20) NOT NULL,
  `email` varchar(30) NOT NULL,
  `status` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `emp_leave`
--

INSERT INTO `emp_leave` (`id`, `reason`, `start_date`, `last_date`, `email`, `status`) VALUES
(9, 'I got sick', '2021-07-28', '2021-07-30', 'test@gmail.com', 'Canceled'),
(15, ' drnrdng', '2021-07-09', '2021-07-11', 'emp1@emp.com', 'Accepted'),
(16, ' drnrdng', '2021-07-14', '2021-07-25', 'emp1@emp.com', 'Canceled'),
(17, ' drnrdng', '2021-07-16', '2021-07-25', 'emp1@emp.com', 'pending'),
(20, ' dcw', '2021-07-10', '2021-07-11', 'emp3@emp.com', 'Accepted'),
(21, ' efwe', '2021-07-23', '2021-07-25', 'emp3@emp.com', 'Canceled'),
(22, ' ewfew', '2021-07-24', '2021-07-18', 'emp3@emp.com', 'Accepted'),
(23, ' drnrdng', '2021-07-01', '2021-07-02', 'emp3@emp.com', 'Canceled'),
(24, ' i got sick', '2021-07-03', '2021-07-06', 'test@gmail.com', 'Accepted'),
(25, ' i got sick', '2021-07-04', '2021-07-07', 'test@gmail.com', 'Canceled'),
(26, ' drnrdng', '2021-07-04', '2021-07-07', 'test@gmail.com', 'Accepted'),
(27, ' sick', '2025-05-04', '2025-05-05', 'emp1@emp.com', 'pending');

-- --------------------------------------------------------

--
-- Table structure for table `pay_slips`
--

CREATE TABLE `pay_slips` (
  `id` int NOT NULL,
  `employee_id` int NOT NULL,
  `month` varchar(20) NOT NULL,
  `year` int NOT NULL,
  `basic_salary` decimal(10,2) NOT NULL,
  `allowances` decimal(10,2) DEFAULT '0.00',
  `deductions` decimal(10,2) DEFAULT '0.00',
  `net_salary` decimal(10,2) NOT NULL,
  `generation_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `pay_slips`
--

INSERT INTO `pay_slips` (`id`, `employee_id`, `month`, `year`, `basic_salary`, `allowances`, `deductions`, `net_salary`, `generation_date`) VALUES
(1, 13, 'June', 2025, 24000.00, 2500.00, 1000.00, 25500.00, '2025-06-13');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_target_employee` (`target_employee_id`),
  ADD KEY `fk_admin_creator` (`admin_id`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `employee`
--
ALTER TABLE `employee`
  ADD PRIMARY KEY (`id`),
  ADD KEY `email` (`email`);

--
-- Indexes for table `employee_ratings`
--
ALTER TABLE `employee_ratings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `employee_tasks`
--
ALTER TABLE `employee_tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `emp_leave`
--
ALTER TABLE `emp_leave`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pay_slips`
--
ALTER TABLE `pay_slips`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `employee`
--
ALTER TABLE `employee`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `employee_ratings`
--
ALTER TABLE `employee_ratings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employee_tasks`
--
ALTER TABLE `employee_tasks`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `emp_leave`
--
ALTER TABLE `emp_leave`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `pay_slips`
--
ALTER TABLE `pay_slips`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `fk_admin_creator` FOREIGN KEY (`admin_id`) REFERENCES `admin` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_target_employee` FOREIGN KEY (`target_employee_id`) REFERENCES `employee` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employee` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `employee_ratings`
--
ALTER TABLE `employee_ratings`
  ADD CONSTRAINT `employee_ratings_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employee` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `employee_ratings_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `admin` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `employee_tasks`
--
ALTER TABLE `employee_tasks`
  ADD CONSTRAINT `employee_tasks_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employee` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `employee_tasks_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `admin` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
