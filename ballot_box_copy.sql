-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: May 20, 2025 at 01:56 PM
-- Server version: 9.1.0
-- PHP Version: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ballot_box copy`
--

-- --------------------------------------------------------

--
-- Table structure for table `candidates`
--

DROP TABLE IF EXISTS `candidates`;
CREATE TABLE IF NOT EXISTS `candidates` (
  `student_id` varchar(20) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `position_id` int DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `bio` text,
  `photo` varchar(255) DEFAULT NULL,
  `eligibility_document` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `voter_id` int DEFAULT NULL,
  `position_name` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`student_id`),
  UNIQUE KEY `student_id` (`student_id`),
  KEY `voter_id` (`voter_id`),
  KEY `position_id` (`position_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `candidates`
--

INSERT INTO `candidates` (`student_id`, `name`, `email`, `position_id`, `department`, `phone`, `bio`, `photo`, `eligibility_document`, `status`, `voter_id`, `position_name`) VALUES
('AIK22CS008', 'ALEENA ANTONY', 'aleenaantony151@gmail.com', 3, 'CSE', '7809564321', 'The ballot is stronger than the bullet', '1744262655_me.jpg', '1744262655_1743086991_Shadow Forest.pdf', 'approved', 0, NULL),
('AIK22CS005', 'AGNA JO AUGUSTIN', 'agnajoaugustin94@gmail.com', 3, 'CSE', '7809564321', 'believe you can and you are halfway there .', '1744257300_agna.jpg', '1744257300_1743221845_Shadows.pdf', 'approved', 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `election_status`
--

DROP TABLE IF EXISTS `election_status`;
CREATE TABLE IF NOT EXISTS `election_status` (
  `id` int NOT NULL AUTO_INCREMENT,
  `status` varchar(20) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `election_timeline`
--

DROP TABLE IF EXISTS `election_timeline`;
CREATE TABLE IF NOT EXISTS `election_timeline` (
  `election_id` int NOT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `previous_status` varchar(20) DEFAULT NULL,
  `status` enum('not_started','ongoing','ended') DEFAULT 'not_started',
  `start_message_sent` tinyint(1) DEFAULT '0',
  `end_message_sent` tinyint(1) DEFAULT '0',
  `start_notification_sent` tinyint(1) DEFAULT '0',
  `end_notification_sent` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`election_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `issues`
--

DROP TABLE IF EXISTS `issues`;
CREATE TABLE IF NOT EXISTS `issues` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` varchar(50) NOT NULL,
  `recipient` varchar(50) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `issue_text` text NOT NULL,
  `status` enum('new','in progress','resolved','closed') DEFAULT 'new',
  `admin_notes` text,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `issues`
--

INSERT INTO `issues` (`id`, `student_id`, `recipient`, `subject`, `issue_text`, `status`, `admin_notes`, `created_at`, `updated_at`) VALUES
(5, 'AIK22CS001', 'admin', 'login issue', 'issue', 'new', NULL, '2025-04-10 10:11:16', NULL),
(3, 'AIK22CS005', 'admin', 'vote', 'vote', 'new', NULL, '2025-03-31 20:50:20', NULL),
(4, 'AIK22CS001', 'admin', 'login issue', 'voting issue', 'new', '', '2025-04-01 11:28:57', '2025-04-01 12:45:55');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `message` text NOT NULL,
  `type` enum('manual','election_status') DEFAULT 'manual',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `is_marquee` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `message`, `type`, `created_at`, `is_marquee`) VALUES
(16, 'Election timeline has been reset.', 'election_status', '2025-03-27 16:55:01', 0),
(19, 'election started..', 'manual', '2025-03-28 02:39:02', 1),
(21, 'election started soon!!!!!!!!!!!!!!!!!', 'manual', '2025-03-29 02:40:46', 1),
(23, 'We have an important update for you from the Election Admin team.Election started..Please log in to the voting system to stay updated on the latest election details.', 'manual', '2025-03-29 03:59:31', 1),
(24, 'admin alert >>', 'manual', '2025-03-29 13:08:04', 1);

-- --------------------------------------------------------

--
-- Table structure for table `positions`
--

DROP TABLE IF EXISTS `positions`;
CREATE TABLE IF NOT EXISTS `positions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `position_name` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `position_name` (`position_name`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `positions`
--

INSERT INTO `positions` (`id`, `position_name`) VALUES
(4, 'Arts Representative'),
(1, 'Chairman'),
(3, 'Secretary'),
(5, 'Sports Representative'),
(6, 'Tech Coordinator'),
(2, 'Vice Chairman');

-- --------------------------------------------------------

--
-- Table structure for table `rejected_voters`
--

DROP TABLE IF EXISTS `rejected_voters`;
CREATE TABLE IF NOT EXISTS `rejected_voters` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `reason` text,
  `rejected_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_id` (`student_id`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `selected_candidate`
--

DROP TABLE IF EXISTS `selected_candidate`;
CREATE TABLE IF NOT EXISTS `selected_candidate` (
  `student_id` varchar(20) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `position_id` int DEFAULT NULL,
  `eligibility_document` varchar(255) DEFAULT NULL,
  `published` tinyint(1) DEFAULT '0',
  `bio` text,
  `position_name` varchar(50) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'approved',
  PRIMARY KEY (`student_id`),
  KEY `position_id` (`position_id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `selected_candidate`
--

INSERT INTO `selected_candidate` (`student_id`, `name`, `photo`, `department`, `position_id`, `eligibility_document`, `published`, `bio`, `position_name`, `status`) VALUES
('AIK22CS005', 'AGNA JO AUGUSTIN', '1744257300_agna.jpg', 'CSE', 3, '1744257300_1743221845_Shadows.pdf', 0, 'believe you can and you are halfway there .', NULL, 'approved'),
('AIK22CS008', 'ALEENA ANTONY', '1744262655_me.jpg', 'CSE', 3, '1744262655_1743086991_Shadow Forest.pdf', 0, 'The ballot is stronger than the bullet', NULL, 'approved');

-- --------------------------------------------------------

--
-- Table structure for table `staff_login`
--

DROP TABLE IF EXISTS `staff_login`;
CREATE TABLE IF NOT EXISTS `staff_login` (
  `staff_id` int NOT NULL AUTO_INCREMENT,
  `password` varchar(300) NOT NULL,
  PRIMARY KEY (`staff_id`)
) ENGINE=MyISAM AUTO_INCREMENT=221762 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `staff_login`
--

INSERT INTO `staff_login` (`staff_id`, `password`) VALUES
(446, 'admin');

-- --------------------------------------------------------

--
-- Table structure for table `tutors`
--

DROP TABLE IF EXISTS `tutors`;
CREATE TABLE IF NOT EXISTS `tutors` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tutor_id` varchar(191) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `batch` varchar(50) NOT NULL,
  `department` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tutor_id` (`tutor_id`)
) ENGINE=MyISAM AUTO_INCREMENT=49 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `tutors`
--

INSERT INTO `tutors` (`id`, `tutor_id`, `password`, `name`, `batch`, `department`) VALUES
(29, '401', 'divya', 'Divya Mohan ', '11', 'CSE'),
(30, '200', '$2y$10$WhWhT20Zxdn3fcpI08nahevuc/whC7Vv5MVfzz0kRB9ra1GZVnu4e', 'Dr Neenu ', '10', 'EC'),
(31, '402', '$2y$10$Ik6PnxjrgqwNb469e53To.8xs65ysd8o4ArNyBtDpXQwNpFJK77e6', 'Ms.Teenu Jose', '12', 'CSE'),
(32, '400', 'jeswin', 'Mr.Jeswin Roy ', '10', 'CSE'),
(33, '403', '$2y$10$EvY51U3yN3YH.7LZ.E1ygej.fZpqmXgR7o95m/aKj17Wmn2LK48Pu', 'Ms. Anna John', '13', 'CSE'),
(34, '100', '$2y$10$79DxyyN71mtzXO3OZM2/n.ZpLFJl1ID3cJPoWh9e8ORBDyywdGIgO', 'Dr Benny Mathews', '10', 'CE'),
(35, '101', '$2y$10$/UEG85Rpz53gDNETspCvuOGFgYpuS67mdkQuz32cB5Pd4JawCAMhS', 'Ms Rinku ', '11', 'CE'),
(36, '102', '$2y$10$xHRi9ZurpQLjaJ5ZHXY5C.BSmkZkk7D1MaywV9F2wovCqvFtbxeUi', 'Ms Nithiya', '12', 'CE'),
(37, '103', '$2y$10$.lm0yj3DEc64SNWN6ZnuPeAHnwGRpEgA0kgCe4zNxxSIC5OwwDqhy', 'Ms Feeba', '13', 'CE'),
(38, '201', '$2y$10$R0/NEjVx./6K5Uu7gkw/kuW2GM7K3ChDXXLxLVT46Q7tTa9JPo7uK', 'Dr saju', '11', 'EC'),
(39, '202', '$2y$10$LGcwjsX6CuxfZdVieBlFbefE..QGsDJ2E8kT/OSPy4OddgQYNl4z.', 'Ms. Pearl', '12', 'EC'),
(40, '203', '$2y$10$83NQElZ5ihVByT4mgG4avu8NdIElYq.JJpTWwI1dG7nYJAlzXAqtq', 'Ms.Neethu ', '13', 'EC'),
(41, '300', '$2y$10$RNMyiGJRemLEMsNzFuTduOrPrUxbM6Oq1H8Ew4J8/rDnPspr2UT5e', 'Mr.Linss', '10', 'EEE'),
(42, '301', '$2y$10$AzN84vfzrr62IVVn6Rs8fe5Y4FrtvC69jZ/13BVd7xWbPUGDui8ve', 'Ms.Annie', '11', 'EEE'),
(43, '302', '$2y$10$BOwBM97dnniyKYX/wjDBqu71yDEZRMwrTNYmJiJLN4Be0JVCuxyGa', 'Mr.Deepu ', '12', 'EEE'),
(44, '303', '$2y$10$TLYuuWS70ad33ZP4XwV8Me95k0HWUbPAHBbQ8zlS5b2eQGAvkqYUG', 'Ms.Merin', '13', 'EEE'),
(45, '500', '$2y$10$DMV2VL/oP7nvKB4SuwYHQ.q3MfwBeIMvzhxJvxj.aWckGpVXMJXN2', 'Dr. Ramadas ', '10', 'ME'),
(46, '501', '$2y$10$b1VBrG8QAXscxNbF6PXx1u.QEFML.fFPTY/ApK.ZTV83O83JcVBL6', 'Ms. Asha', '11', 'ME'),
(47, '502', '$2y$10$v3mhrb5od9t2L9ltyMv0mutTpYhQRITIheZ6SsQkeq.qi08UH9GvG', 'Dr.Manoj', '12', 'ME'),
(48, '503', '$2y$10$arHX72ZULTEIoemRsH6TAukRtluOC5nkxpFYhZGcs12KMq9Mo4qmy', 'Mr.Arun', '13', 'ME');

-- --------------------------------------------------------

--
-- Table structure for table `tutor_login`
--

DROP TABLE IF EXISTS `tutor_login`;
CREATE TABLE IF NOT EXISTS `tutor_login` (
  `tutor_id` int NOT NULL AUTO_INCREMENT,
  `password` varchar(300) NOT NULL,
  `name` varchar(100) NOT NULL,
  `department` varchar(100) NOT NULL,
  `batch` varchar(50) NOT NULL,
  PRIMARY KEY (`tutor_id`)
) ENGINE=MyISAM AUTO_INCREMENT=221762 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `tutor_login`
--

INSERT INTO `tutor_login` (`tutor_id`, `password`, `name`, `department`, `batch`) VALUES
(403, '$2y$10$m08czbNutnNei4RbvoP...//tCG6AmSDohBbiN6XCthPXedtD9eAG', 'Ms Anna John', 'CSE', '13'),
(401, '$2y$10$r4FxaoEBMiy1p7aVAdelW.yjmHLyUjcNTdKUB.1EYkaRB.PXF0gwa', 'Ms Divya Mohan ', 'CSE', '11'),
(402, '$2y$10$90OLorTZ12aWN6Ewbi7h7u7n3vMNUK8HqavCLicC4drbbi8LImHUa', 'Ms Teenu Jose', 'CSE', '12'),
(400, '$2y$10$OfOvlHP6BmAVSp8Og17wBOv09eG83bzaqqWz4CZgA3FBKjEE7Inb6', 'Mr Jeswin', 'CSE', '10'),
(503, '$2y$10$LCEY4CibKszktqiIfN7M2.XJSbz7GVI39VsmiDNwCpEWVkIr/Kheu', 'Mr Arun', 'ME', '13'),
(502, '$2y$10$WazvxmbJV10uP.l6m0sR.unLGxewWOYGjwCJxZO0a/cGd7ChC7AJq', 'Dr Manoj', 'ME', '12'),
(501, '$2y$10$Hl5xh4gHl4ACzuotkGiC8ONZqTX5zKmmioP2nyfzMlr270e6mQEmq', 'Ms Asha', 'ME', '11'),
(303, '$2y$10$SW9WE0jr0qOVcuVZRH11LusNf2hjOd4z.ZyRtTpcBmJIJGYc30oRK', 'Ms Merin', 'EEE', '13'),
(500, '$2y$10$HUWcd.RsCZL7OelBeTD6DunBXJBIvG2k.DnuuIk69U77VSNESkk/S', 'Dr Ramadas', 'ME', '10'),
(302, '$2y$10$9YH1b26f/3NMpMickaMe4euK/FFtNPuXHRB.H/tIe5Kb/KX/scyiO', 'Mr Deepu', 'EEE', '12'),
(301, '$2y$10$zyebOJFc/9EL1cCJa/5PLuNWvGXBgbdAHMPHSPVNYWCMnNrzHD3fq', 'Ms Annie', 'EEE', '11'),
(300, '$2y$10$dJlS221i1C5xwHkHBUbqVO0UNFfUc7EXjFWXj5.ifqprgO8Rz/0y.', 'Mr Linss', 'EEE', '10'),
(203, '$2y$10$d//6P/GhOIdpHj7rAZKj4uccgaNG0OSVqKDy/PJl.SrxFG1LfnUDq', 'Ms Neethu', 'EC', '13'),
(202, '$2y$10$gTF25uTf02JbHtS4yL1cG.d7kwtvokkUcbg4PcqdZGuFBfG/NSoiq', 'Ms Pearl ', 'EC', '12'),
(201, '$2y$10$aKwt4RxVUnT3xK2az7xiau0wR3yqImKELthRfeVbDkIuxdZINDk/.', 'Dr Saju', 'EC', '11'),
(200, '$2y$10$1C28dN9vULpQHRl9HRQqCO05fykTJ2CT6IHQr2L2ftlyPCb5PCwkG', 'Dr Neenu ', 'EC', '10'),
(103, '$2y$10$7wOM/X4Zbfe3DveQGXsl0eFjAIpIWtF7HOma1KzTsA0hgq.ix8jIm', 'Ms Feeba', 'CE', '13'),
(102, '$2y$10$izfu6ReLq0OGDtddcf8yvuMMjO9PYwQe3aoOSxRDY7mvgpSybZ5Ra', 'Ms Nithiya', 'CE', '12'),
(100, '$2y$10$Ceo46b3M1sohYDi2GA1MEurtZde6JVmZbA6GAnIFVmey9XnDL.g1W', 'Dr Benny Mathews', 'CE', '10'),
(101, '$2y$10$kAfYLSHEzeyZqWSWP2nG9uJ2pDhVW6L.kXh.FcK/mWpp3D71wwy6O', 'Ms Rinku ', 'CE', '11');

-- --------------------------------------------------------

--
-- Table structure for table `voter_reg`
--

DROP TABLE IF EXISTS `voter_reg`;
CREATE TABLE IF NOT EXISTS `voter_reg` (
  `student_id` varchar(20) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `batch` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `tutor_id` int DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `candidate_id` int DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `voter_code` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`student_id`),
  UNIQUE KEY `student_id` (`student_id`),
  KEY `candidate_id` (`candidate_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `voter_reg`
--

INSERT INTO `voter_reg` (`student_id`, `name`, `gender`, `department`, `batch`, `email`, `reset_token`, `phone`, `tutor_id`, `status`, `candidate_id`, `password`, `voter_code`) VALUES
('AIK22CS022', 'CECIL ROY', 'male', 'CSE', '11', 'menofculture917@gmail.com', NULL, '7839564321', 401, 'approved', NULL, '$2y$10$maH340oakmmwk4NyKU0ltu8nlhu6DSUeyO9hbaFYYrUD6lrZgiwLi', NULL),
('AIK22CS050', 'REJOY SANJOSE', 'male', 'CSE', '11', 'rejorock2003@gmail.com', NULL, '1234567892', 401, 'approved', NULL, '$2y$10$nQVrYkXu1cxNFey36CFnc.bGUhbyFduSNofX/S/ro3sSVas10KZHq', NULL),
('AIK22CS005', 'AGNA JO AUGUSTIN', 'female', 'CSE', '11', 'agnajoaugustin94@gmail.com', NULL, '7839564321', 401, 'approved', NULL, '$2y$10$pfYSxn/A2pWnh4Br6t68Xeg69oa94AvZvyiPu.5Xrbp6FTI2mHSIm', NULL),
('AIK22CS008', 'ALEENA ANTONY', 'female', 'CSE', '11', 'aleenaantony151@gmail.com', NULL, '7839564321', 401, 'approved', NULL, '$2y$10$jAwlc3wPhtcbApTf2bWeSO0EiInbCiod.sCFbdkeB2aBlui2l0R46', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `votes`
--

DROP TABLE IF EXISTS `votes`;
CREATE TABLE IF NOT EXISTS `votes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` varchar(20) DEFAULT NULL,
  `position_id` int DEFAULT NULL,
  `candidate_student_id` varchar(20) DEFAULT NULL,
  `vote_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `candidate_student_id` (`candidate_student_id`)
) ENGINE=MyISAM AUTO_INCREMENT=47 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
