-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 31, 2026 at 10:14 AM
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
-- Database: `campus_bookhub`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `admin_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`admin_id`, `username`, `password`, `fullname`, `email`, `created_at`) VALUES
(1, 'admin_rep', '$2y$10$nWAWOQmGqLZSnW4RUXC0aOKsVq5CuWmqzQUijIt0Y5F9KddSwnmOe', 'Administrator', 'admin@campusbookhub.com', '2026-07-26 19:40:11');

-- --------------------------------------------------------

--
-- Table structure for table `books`
--

CREATE TABLE `books` (
  `book_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `course_code` varchar(20) NOT NULL,
  `lecturer` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `image` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `books`
--

INSERT INTO `books` (`book_id`, `title`, `course_code`, `lecturer`, `price`, `stock`, `image`, `description`, `created_at`) VALUES
(1, 'System Analysis & Design', 'ICT 244', 'Dr. Franco Osei-Wusu', 55.00, 97, 'uploads/books/book_1785097098_8185.jpeg', 'From problem to powerful solution', '2026-07-26 20:18:18'),
(2, 'Computer Networking', 'ICT 245', 'Dr. Eldad  Antwi-Bekoe', 60.00, 496, 'uploads/books/book_1785106734_6454.jpeg', 'Lecture Notes', '2026-07-26 22:58:54'),
(3, 'Database Development & Implementation', 'ICT 241', 'Dr. Oliver K. Boansi', 55.00, 148, 'uploads/books/book_1785172506_7330.jpeg', 'MySQL Book Guide', '2026-07-27 17:15:06');

-- --------------------------------------------------------

--
-- Table structure for table `collection`
--

CREATE TABLE `collection` (
  `collection_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `collection_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `collection`
--

INSERT INTO `collection` (`collection_id`, `order_id`, `admin_id`, `collection_date`, `notes`) VALUES
(1, 2, 1, '2026-07-26 23:01:12', 'Verified and handed over in-person'),
(2, 1, 1, '2026-07-26 23:59:10', 'Verified and handed over in-person'),
(3, 3, 1, '2026-07-27 00:12:46', 'Verified and handed over in-person'),
(4, 4, 1, '2026-07-27 10:27:29', 'Verified and handed over in-person'),
(5, 5, 1, '2026-07-27 10:54:28', 'Verified and handed over in-person'),
(6, 6, 1, '2026-07-28 01:48:55', 'Verified and handed over in-person'),
(7, 7, 1, '2026-07-28 13:19:04', 'Verified and handed over in-person');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `total_amount` decimal(10,2) NOT NULL,
  `payment_reference` varchar(100) NOT NULL,
  `payment_status` enum('Pending','Verified','Rejected') DEFAULT 'Pending',
  `collection_status` enum('Pending','Collected') DEFAULT 'Pending',
  `order_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `student_id`, `book_id`, `quantity`, `total_amount`, `payment_reference`, `payment_status`, `collection_status`, `order_date`) VALUES
(1, 1, 1, 1, 55.00, 'MOMO-20260726-673261', 'Verified', 'Collected', '2026-07-26 21:44:13'),
(2, 1, 1, 1, 55.00, 'MOMO-20260726-727113', 'Verified', 'Collected', '2026-07-26 21:45:58'),
(3, 1, 2, 1, 60.00, 'MOMO-20260726-100003', 'Verified', 'Collected', '2026-07-26 23:32:51'),
(4, 1, 2, 1, 60.00, '5240101305-9824', 'Verified', 'Collected', '2026-07-26 23:57:29'),
(5, 1, 1, 1, 55.00, '5240101305-1188', 'Verified', 'Collected', '2026-07-27 01:39:15'),
(6, 1, 2, 1, 60.00, '5240101305-7200', 'Verified', 'Collected', '2026-07-27 10:52:49'),
(7, 1, 2, 1, 60.00, '5240101305-9714', 'Verified', 'Collected', '2026-07-27 16:10:21'),
(8, 1, 3, 1, 55.00, '5240101305-8703', 'Verified', 'Pending', '2026-07-28 01:52:35'),
(9, 1, 3, 1, 55.00, '5240101305-9387', 'Verified', 'Pending', '2026-07-28 13:17:06');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `email`, `token`, `expires_at`, `created_at`) VALUES
(27, 'kelvinhonorajunior@gmail.com', '645503', '2026-07-31 07:28:40', '2026-07-31 05:18:40');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `transaction_id` varchar(100) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `proof_image` varchar(255) NOT NULL,
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `payment_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`payment_id`, `order_id`, `transaction_id`, `payment_method`, `amount`, `proof_image`, `status`, `payment_date`) VALUES
(1, 1, 'MOMO-20260726-673261', 'MTN Mobile Money', 55.00, 'IN_APP_MOMO_VERIFIED', 'Approved', '2026-07-26 21:44:13'),
(2, 2, 'MOMO-20260726-727113', 'MTN Mobile Money', 55.00, 'IN_APP_MOMO_VERIFIED', 'Approved', '2026-07-26 21:45:59');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `student_id` int(11) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `index_number` varchar(50) NOT NULL,
  `departmentlevel` varchar(100) NOT NULL,
  `class` varchar(50) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`student_id`, `fullname`, `index_number`, `departmentlevel`, `class`, `phone`, `email`, `password`, `created_at`) VALUES
(1, 'Kelvyn Honora', '5240101305', 'IT - Level 200', 'N class', '0544893582', 'kelvinhonorajunior@gmail.com', '$2y$10$GpidqzdFqcgV0l0J/ZcyQePw.krBbJX.HsMKDTPfsVRopCDmnLD8i', '2026-07-26 19:11:35'),
(2, 'Kelly Synergy', '5240101309', 'IT - Level 200', 'A class', '0544893582', '', '$2y$10$c9nAIKZbHGM78/NknC0TrOXoQBzKR7wM/jbgM3NHkUYnMr2ExZnma', '2026-07-27 00:18:11');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `books`
--
ALTER TABLE `books`
  ADD PRIMARY KEY (`book_id`);

--
-- Indexes for table `collection`
--
ALTER TABLE `collection`
  ADD PRIMARY KEY (`collection_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `book_id` (`book_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`student_id`),
  ADD UNIQUE KEY `index_number` (`index_number`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `books`
--
ALTER TABLE `books`
  MODIFY `book_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `collection`
--
ALTER TABLE `collection`
  MODIFY `collection_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `collection`
--
ALTER TABLE `collection`
  ADD CONSTRAINT `collection_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `collection_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `admin` (`admin_id`);

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`),
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`book_id`);

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
