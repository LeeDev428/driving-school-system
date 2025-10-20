-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               8.4.3 - MySQL Community Server - GPL
-- Server OS:                    Win64
-- HeidiSQL Version:             12.8.0.6908
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Dumping database structure for driving_school
CREATE DATABASE IF NOT EXISTS `driving_school` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `driving_school`;

-- Dumping structure for table driving_school.appointments
CREATE TABLE IF NOT EXISTS `appointments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `instructor_id` int DEFAULT NULL,
  `vehicle_id` int DEFAULT NULL,
  `vehicle_type` enum('motorcycle','car') DEFAULT NULL COMMENT 'For PDC: motorcycle (₱2,000) or car (₱4,500)',
  `vehicle_transmission` enum('automatic','manual') DEFAULT NULL COMMENT 'Transmission type for selected vehicle',
  `course_price` decimal(10,2) DEFAULT '0.00' COMMENT 'TDC: 899, PDC Motorcycle: 2000, PDC Car: 4500',
  `appointment_type_id` int DEFAULT NULL,
  `course_selection` enum('TDC','PDC') NOT NULL DEFAULT 'PDC' COMMENT 'TDC: Theoretical Driving Course (₱899), PDC: Practical Driving Course (₱2,000-4,500)',
  `tdc_session_id` int DEFAULT NULL COMMENT 'Links to tdc_sessions table for TDC appointments only',
  `duration_days` int DEFAULT NULL COMMENT 'For PDC only: 2 or 4 days',
  `course_type` enum('tpc','pdc','both') NOT NULL DEFAULT 'pdc',
  `appointment_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `status` enum('pending','confirmed','in_progress','completed','cancelled','no_show') DEFAULT 'pending',
  `notes` text,
  `student_notes` text,
  `instructor_notes` text,
  `payment_status` enum('unpaid','paid','refunded') DEFAULT 'unpaid',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `payment_amount` decimal(10,2) DEFAULT '0.00',
  `payment_method` enum('cash','card','bank_transfer','online') DEFAULT NULL,
  `payment_reference` varchar(100) DEFAULT NULL,
  `reminder_sent` tinyint(1) DEFAULT '0' COMMENT 'Whether reminder email has been sent',
  `reminder_sent_at` datetime DEFAULT NULL COMMENT 'When reminder email was sent',
  `payment_proof` varchar(255) DEFAULT NULL COMMENT 'Filename of uploaded payment screenshot',
  `pdc_time_slot_id` int DEFAULT NULL COMMENT 'Link to pdc_time_slots table',
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `instructor_id` (`instructor_id`),
  KEY `vehicle_id` (`vehicle_id`),
  KEY `appointment_type_id` (`appointment_type_id`),
  KEY `idx_course_selection` (`course_selection`),
  KEY `idx_tdc_session` (`tdc_session_id`),
  KEY `idx_vehicle_type` (`vehicle_type`),
  KEY `fk_pdc_time_slot` (`pdc_time_slot_id`),
  CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`instructor_id`) REFERENCES `instructors` (`id`) ON DELETE SET NULL,
  CONSTRAINT `appointments_ibfk_3` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE SET NULL,
  CONSTRAINT `appointments_ibfk_4` FOREIGN KEY (`appointment_type_id`) REFERENCES `appointment_types` (`id`) ON DELETE CASCADE,
  CONSTRAINT `appointments_ibfk_5` FOREIGN KEY (`tdc_session_id`) REFERENCES `tdc_sessions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_pdc_time_slot` FOREIGN KEY (`pdc_time_slot_id`) REFERENCES `pdc_time_slots` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table driving_school.appointment_logs
CREATE TABLE IF NOT EXISTS `appointment_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `appointment_id` int NOT NULL,
  `action` varchar(100) NOT NULL,
  `old_status` varchar(50) DEFAULT NULL,
  `new_status` varchar(50) DEFAULT NULL,
  `changed_by` int DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `appointment_id` (`appointment_id`),
  KEY `changed_by` (`changed_by`),
  CONSTRAINT `appointment_logs_ibfk_1` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `appointment_logs_ibfk_2` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table driving_school.appointment_types
CREATE TABLE IF NOT EXISTS `appointment_types` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text,
  `duration_minutes` int NOT NULL DEFAULT '60',
  `price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `color` varchar(7) DEFAULT '#007bff',
  `is_active` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table driving_school.assessments
CREATE TABLE IF NOT EXISTS `assessments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `question_number` int NOT NULL COMMENT 'Question number (1-20)',
  `question_text` text NOT NULL COMMENT 'The question text',
  `correct_answer` enum('True','False') NOT NULL COMMENT 'Correct answer',
  `category` varchar(100) DEFAULT NULL COMMENT 'Traffic Signs, Road Markings, Traffic Rules, Emergency Response',
  `explanation` text COMMENT 'Explanation for the correct answer',
  `is_active` tinyint(1) DEFAULT '1' COMMENT 'Is this question active?',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_question_number` (`question_number`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Assessment questions (True/False)';

-- Data exporting was unselected.

-- Dumping structure for table driving_school.elearning_modules
CREATE TABLE IF NOT EXISTS `elearning_modules` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `icon` varchar(100) DEFAULT 'fas fa-book',
  `status` enum('Active','Draft','Archived') DEFAULT 'Draft',
  `duration_minutes` int NOT NULL DEFAULT '60',
  `content` longtext,
  `created_by` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table driving_school.elearning_quizzes
CREATE TABLE IF NOT EXISTS `elearning_quizzes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `time_limit_minutes` int DEFAULT '30',
  `passing_score` int DEFAULT '70',
  `status` enum('Active','Draft','Archived') DEFAULT 'Draft',
  `created_by` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table driving_school.elearning_videos
CREATE TABLE IF NOT EXISTS `elearning_videos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `video_url` varchar(500) NOT NULL,
  `thumbnail` varchar(500) DEFAULT NULL,
  `duration_minutes` int NOT NULL DEFAULT '10',
  `status` enum('Active','Draft','Archived') DEFAULT 'Draft',
  `created_by` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table driving_school.instructors
CREATE TABLE IF NOT EXISTS `instructors` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `license_number` varchar(50) NOT NULL,
  `specializations` text,
  `years_experience` int DEFAULT '0',
  `hourly_rate` decimal(8,2) DEFAULT '0.00',
  `is_active` tinyint(1) DEFAULT '1',
  `date_hired` date DEFAULT (curdate()),
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_license` (`license_number`),
  UNIQUE KEY `unique_user` (`user_id`),
  CONSTRAINT `instructors_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table driving_school.password_resets
CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` timestamp NOT NULL,
  `used` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `email` (`email`),
  KEY `token` (`token`),
  KEY `expires_at` (`expires_at`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table driving_school.pdc_time_slots
CREATE TABLE IF NOT EXISTS `pdc_time_slots` (
  `id` int NOT NULL AUTO_INCREMENT,
  `slot_date` date NOT NULL COMMENT 'The date for this time slot',
  `slot_time_start` time NOT NULL COMMENT 'Start time',
  `slot_time_end` time NOT NULL COMMENT 'End time',
  `slot_label` varchar(50) NOT NULL COMMENT 'Display label like "8:00 AM - 12:00 PM"',
  `instructor_id` int DEFAULT NULL COMMENT 'Assigned instructor',
  `max_bookings` int DEFAULT '3' COMMENT 'Maximum bookings allowed',
  `current_bookings` int DEFAULT '0' COMMENT 'Current number of bookings',
  `is_available` tinyint(1) DEFAULT '1' COMMENT 'Available for booking',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_slot` (`slot_date`,`slot_time_start`,`instructor_id`),
  KEY `idx_slot_date` (`slot_date`),
  KEY `idx_instructor` (`instructor_id`),
  KEY `idx_available` (`is_available`)
) ENGINE=InnoDB AUTO_INCREMENT=241 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table driving_school.quizzes
CREATE TABLE IF NOT EXISTS `quizzes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `question_number` int NOT NULL COMMENT 'Question number (1-50)',
  `question_text` text NOT NULL COMMENT 'The question text',
  `option_a` varchar(255) NOT NULL COMMENT 'Option A',
  `option_b` varchar(255) NOT NULL COMMENT 'Option B',
  `option_c` varchar(255) NOT NULL COMMENT 'Option C',
  `option_d` varchar(255) NOT NULL COMMENT 'Option D',
  `correct_answer` enum('a','b','c','d') NOT NULL COMMENT 'Correct answer',
  `category` varchar(100) DEFAULT NULL COMMENT 'Traffic Lights, Road Signs, Emergency, Road Markings, Driving Rules, Protocol Plates',
  `explanation` text COMMENT 'Explanation for the correct answer',
  `is_active` tinyint(1) DEFAULT '1' COMMENT 'Is this question active?',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_question_number` (`question_number`)
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Quiz questions (Multiple Choice)';

-- Data exporting was unselected.

-- Dumping structure for table driving_school.quiz_responses
CREATE TABLE IF NOT EXISTS `quiz_responses` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `session_id` varchar(100) NOT NULL COMMENT 'Unique session identifier for grouping responses',
  `scenario_id` int NOT NULL COMMENT 'Which scenario (1-5)',
  `question_text` text NOT NULL COMMENT 'The actual question asked',
  `selected_option` int NOT NULL COMMENT 'User selected option (0-3)',
  `correct_option` int NOT NULL COMMENT 'Correct option (0-3)',
  `is_correct` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Whether answer was correct',
  `points_earned` int NOT NULL DEFAULT '0' COMMENT 'Points earned for this question',
  `time_taken_seconds` int DEFAULT NULL COMMENT 'Time taken to answer',
  `answered_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `session_id` (`session_id`),
  KEY `scenario_id` (`scenario_id`),
  CONSTRAINT `quiz_responses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=65 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table driving_school.quiz_sessions
CREATE TABLE IF NOT EXISTS `quiz_sessions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `session_id` varchar(100) NOT NULL COMMENT 'Links to quiz_responses',
  `user_id` int NOT NULL,
  `total_questions` int NOT NULL DEFAULT '5',
  `questions_answered` int NOT NULL DEFAULT '0',
  `correct_answers` int NOT NULL DEFAULT '0',
  `total_points` int NOT NULL DEFAULT '0',
  `max_points` int NOT NULL DEFAULT '100',
  `completion_percentage` decimal(5,2) NOT NULL DEFAULT '0.00',
  `session_status` enum('in_progress','completed','abandoned') NOT NULL DEFAULT 'in_progress',
  `started_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `completed_at` timestamp NULL DEFAULT NULL,
  `total_time_seconds` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `session_id` (`session_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `quiz_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table driving_school.simulation_results
CREATE TABLE IF NOT EXISTS `simulation_results` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `simulation_type` varchar(50) NOT NULL DEFAULT 'basic_driving',
  `vehicle_type` varchar(20) NOT NULL DEFAULT 'car',
  `total_scenarios` int NOT NULL DEFAULT '0',
  `correct_answers` int NOT NULL DEFAULT '0',
  `wrong_answers` int NOT NULL DEFAULT '0',
  `score_percentage` decimal(5,2) NOT NULL DEFAULT '0.00',
  `completion_time_seconds` int NOT NULL DEFAULT '0',
  `scenarios_data` json DEFAULT NULL,
  `status` enum('completed','failed','abandoned') NOT NULL DEFAULT 'completed',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `simulation_results_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table driving_school.tdc_sessions
CREATE TABLE IF NOT EXISTS `tdc_sessions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `session_date` date NOT NULL,
  `session_day` enum('Friday','Saturday') COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `max_enrollments` int DEFAULT '10',
  `current_enrollments` int DEFAULT '0',
  `instructor_id` int DEFAULT NULL,
  `status` enum('active','full','cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_session_date` (`session_date`),
  KEY `idx_status` (`status`),
  KEY `instructor_id` (`instructor_id`),
  CONSTRAINT `tdc_sessions_ibfk_1` FOREIGN KEY (`instructor_id`) REFERENCES `instructors` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=45 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table driving_school.users
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `user_type` varchar(20) NOT NULL DEFAULT 'student',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `profile_image` varchar(255) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `license_type` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table driving_school.user_assessment_responses
CREATE TABLE IF NOT EXISTS `user_assessment_responses` (
  `id` int NOT NULL AUTO_INCREMENT,
  `session_id` varchar(100) NOT NULL COMMENT 'Links to user_assessment_sessions',
  `user_id` int NOT NULL COMMENT 'User who answered',
  `question_id` int NOT NULL COMMENT 'Question answered',
  `question_number` int NOT NULL COMMENT 'Question number',
  `user_answer` enum('True','False') NOT NULL COMMENT 'User selected answer',
  `correct_answer` enum('True','False') NOT NULL COMMENT 'Correct answer',
  `is_correct` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Is answer correct?',
  `answered_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'When answered',
  PRIMARY KEY (`id`),
  KEY `idx_session_id` (`session_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_question_id` (`question_id`),
  CONSTRAINT `fk_assessment_response_question` FOREIGN KEY (`question_id`) REFERENCES `assessments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_assessment_response_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='User answers to assessment questions';

-- Data exporting was unselected.

-- Dumping structure for table driving_school.user_assessment_sessions
CREATE TABLE IF NOT EXISTS `user_assessment_sessions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL COMMENT 'User taking the assessment',
  `session_id` varchar(100) NOT NULL COMMENT 'Unique session identifier',
  `total_questions` int DEFAULT '20' COMMENT 'Total questions in assessment',
  `correct_answers` int DEFAULT '0' COMMENT 'Number of correct answers',
  `wrong_answers` int DEFAULT '0' COMMENT 'Number of wrong answers',
  `score_percentage` decimal(5,2) DEFAULT '0.00' COMMENT 'Score in percentage',
  `passing_score` decimal(5,2) DEFAULT '70.00' COMMENT 'Minimum passing score',
  `passed` tinyint(1) DEFAULT '0' COMMENT 'Did user pass?',
  `time_started` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'When assessment started',
  `time_completed` timestamp NULL DEFAULT NULL COMMENT 'When assessment completed',
  `duration_seconds` int DEFAULT NULL COMMENT 'Total time taken in seconds',
  `status` enum('in_progress','completed','abandoned') DEFAULT 'in_progress',
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_session_id` (`session_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_assessment_session_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='User assessment sessions/attempts';

-- Data exporting was unselected.

-- Dumping structure for table driving_school.user_module_progress
CREATE TABLE IF NOT EXISTS `user_module_progress` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `module_id` int NOT NULL,
  `progress_percentage` int DEFAULT '0',
  `completed` tinyint(1) DEFAULT '0',
  `started_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `completed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_module` (`user_id`,`module_id`),
  KEY `module_id` (`module_id`),
  CONSTRAINT `user_module_progress_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_module_progress_ibfk_2` FOREIGN KEY (`module_id`) REFERENCES `elearning_modules` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table driving_school.user_quiz_responses
CREATE TABLE IF NOT EXISTS `user_quiz_responses` (
  `id` int NOT NULL AUTO_INCREMENT,
  `session_id` varchar(100) NOT NULL COMMENT 'Links to user_quiz_sessions',
  `user_id` int NOT NULL COMMENT 'User who answered',
  `question_id` int NOT NULL COMMENT 'Question answered',
  `question_number` int NOT NULL COMMENT 'Question number',
  `user_answer` enum('a','b','c','d') NOT NULL COMMENT 'User selected answer',
  `correct_answer` enum('a','b','c','d') NOT NULL COMMENT 'Correct answer',
  `is_correct` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Is answer correct?',
  `answered_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'When answered',
  PRIMARY KEY (`id`),
  KEY `idx_session_id` (`session_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_question_id` (`question_id`),
  CONSTRAINT `fk_quiz_response_question` FOREIGN KEY (`question_id`) REFERENCES `quizzes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_quiz_response_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='User answers to quiz questions';

-- Data exporting was unselected.

-- Dumping structure for table driving_school.user_quiz_sessions
CREATE TABLE IF NOT EXISTS `user_quiz_sessions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL COMMENT 'User taking the quiz',
  `session_id` varchar(100) NOT NULL COMMENT 'Unique session identifier',
  `total_questions` int DEFAULT '50' COMMENT 'Total questions in quiz',
  `correct_answers` int DEFAULT '0' COMMENT 'Number of correct answers',
  `wrong_answers` int DEFAULT '0' COMMENT 'Number of wrong answers',
  `score_percentage` decimal(5,2) DEFAULT '0.00' COMMENT 'Score in percentage',
  `passing_score` decimal(5,2) DEFAULT '70.00' COMMENT 'Minimum passing score',
  `passed` tinyint(1) DEFAULT '0' COMMENT 'Did user pass?',
  `time_started` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'When quiz started',
  `time_completed` timestamp NULL DEFAULT NULL COMMENT 'When quiz completed',
  `duration_seconds` int DEFAULT NULL COMMENT 'Total time taken in seconds',
  `status` enum('in_progress','completed','abandoned') DEFAULT 'in_progress',
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_session_id` (`session_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_quiz_session_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='User quiz sessions/attempts';

-- Data exporting was unselected.

-- Dumping structure for table driving_school.vehicles
CREATE TABLE IF NOT EXISTS `vehicles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `make` varchar(50) NOT NULL,
  `model` varchar(50) NOT NULL,
  `year` int NOT NULL,
  `license_plate` varchar(20) NOT NULL,
  `transmission_type` enum('manual','automatic') NOT NULL,
  `vehicle_type` enum('sedan','suv','truck','motorcycle') DEFAULT 'sedan',
  `color` varchar(30) DEFAULT NULL,
  `is_available` tinyint(1) DEFAULT '1',
  `last_maintenance` date DEFAULT NULL,
  `next_maintenance` date DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `license_plate` (`license_plate`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table driving_school.violation_logs
CREATE TABLE IF NOT EXISTS `violation_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `session_id` varchar(100) NOT NULL,
  `violation_type` varchar(50) NOT NULL,
  `violation_message` text NOT NULL,
  `severity` enum('info','warning','error') NOT NULL DEFAULT 'warning',
  `violation_timestamp` bigint NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `session_id` (`session_id`),
  KEY `violation_timestamp` (`violation_timestamp`)
) ENGINE=InnoDB AUTO_INCREMENT=199 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for trigger driving_school.update_pdc_slot_after_delete
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `update_pdc_slot_after_delete` AFTER DELETE ON `appointments` FOR EACH ROW BEGIN
    IF OLD.course_selection = 'PDC' AND OLD.pdc_time_slot_id IS NOT NULL THEN
        UPDATE pdc_time_slots 
        SET current_bookings = GREATEST(0, current_bookings - 1)
        WHERE id = OLD.pdc_time_slot_id;
    END IF;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger driving_school.update_pdc_slot_after_insert
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `update_pdc_slot_after_insert` AFTER INSERT ON `appointments` FOR EACH ROW BEGIN
    IF NEW.course_selection = 'PDC' AND NEW.pdc_time_slot_id IS NOT NULL THEN
        UPDATE pdc_time_slots 
        SET current_bookings = current_bookings + 1
        WHERE id = NEW.pdc_time_slot_id;
    END IF;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger driving_school.update_pdc_slot_after_update
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `update_pdc_slot_after_update` AFTER UPDATE ON `appointments` FOR EACH ROW BEGIN
    IF NEW.course_selection = 'PDC' THEN
        IF OLD.pdc_time_slot_id != NEW.pdc_time_slot_id THEN
            IF OLD.pdc_time_slot_id IS NOT NULL THEN
                UPDATE pdc_time_slots 
                SET current_bookings = GREATEST(0, current_bookings - 1)
                WHERE id = OLD.pdc_time_slot_id;
            END IF;
            
            IF NEW.pdc_time_slot_id IS NOT NULL THEN
                UPDATE pdc_time_slots 
                SET current_bookings = current_bookings + 1
                WHERE id = NEW.pdc_time_slot_id;
            END IF;
        END IF;
    END IF;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger driving_school.update_tdc_enrollment_after_delete
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `update_tdc_enrollment_after_delete` AFTER DELETE ON `appointments` FOR EACH ROW BEGIN
  IF OLD.tdc_session_id IS NOT NULL AND OLD.course_selection = 'TDC' THEN
    UPDATE `tdc_sessions` 
    SET `current_enrollments` = GREATEST(0, `current_enrollments` - 1),
        `status` = CASE 
          WHEN `current_enrollments` - 1 < `max_enrollments` THEN 'active'
          ELSE `status`
        END
    WHERE `id` = OLD.tdc_session_id;
  END IF;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger driving_school.update_tdc_enrollment_after_insert
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `update_tdc_enrollment_after_insert` AFTER INSERT ON `appointments` FOR EACH ROW BEGIN
  IF NEW.tdc_session_id IS NOT NULL AND NEW.course_selection = 'TDC' THEN
    UPDATE `tdc_sessions` 
    SET `current_enrollments` = `current_enrollments` + 1,
        `status` = CASE 
          WHEN `current_enrollments` + 1 >= `max_enrollments` THEN 'full'
          ELSE 'active'
        END
    WHERE `id` = NEW.tdc_session_id;
  END IF;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger driving_school.update_tdc_enrollment_after_update
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `update_tdc_enrollment_after_update` AFTER UPDATE ON `appointments` FOR EACH ROW BEGIN
  -- If session changed, update both old and new sessions
  IF OLD.tdc_session_id != NEW.tdc_session_id THEN
    -- Decrease old session
    IF OLD.tdc_session_id IS NOT NULL THEN
      UPDATE `tdc_sessions` 
      SET `current_enrollments` = GREATEST(0, `current_enrollments` - 1),
          `status` = CASE 
            WHEN `current_enrollments` - 1 < `max_enrollments` THEN 'active'
            ELSE `status`
          END
      WHERE `id` = OLD.tdc_session_id;
    END IF;
    
    -- Increase new session
    IF NEW.tdc_session_id IS NOT NULL THEN
      UPDATE `tdc_sessions` 
      SET `current_enrollments` = `current_enrollments` + 1,
          `status` = CASE 
            WHEN `current_enrollments` + 1 >= `max_enrollments` THEN 'full'
            ELSE 'active'
          END
      WHERE `id` = NEW.tdc_session_id;
    END IF;
  END IF;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
