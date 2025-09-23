-- Add quiz_responses table for storing individual scenario answers
-- This table will store each user's response to each scenario question

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Add quiz_sessions table for tracking complete quiz sessions
CREATE TABLE IF NOT EXISTS `quiz_sessions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `session_id` varchar(100) NOT NULL UNIQUE COMMENT 'Links to quiz_responses',
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
  KEY `user_id` (`user_id`),
  CONSTRAINT `quiz_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;