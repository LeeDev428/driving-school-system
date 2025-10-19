-- =========================================
-- ASSESSMENT SYSTEM TABLES
-- True/False Assessment Questions and User Responses
-- =========================================

USE driving_school;

-- Table: assessments (stores all assessment questions)
CREATE TABLE IF NOT EXISTS `assessments` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `question_number` INT NOT NULL COMMENT 'Question number (1-20)',
  `question_text` TEXT NOT NULL COMMENT 'The question text',
  `correct_answer` ENUM('True', 'False') NOT NULL COMMENT 'Correct answer',
  `category` VARCHAR(100) DEFAULT NULL COMMENT 'Traffic Signs, Road Markings, Traffic Rules, Emergency Response',
  `explanation` TEXT DEFAULT NULL COMMENT 'Explanation for the correct answer',
  `is_active` TINYINT(1) DEFAULT 1 COMMENT 'Is this question active?',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_question_number` (`question_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Assessment questions (True/False)';

-- Table: user_assessment_sessions (stores user assessment attempts)
CREATE TABLE IF NOT EXISTS `user_assessment_sessions` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL COMMENT 'User taking the assessment',
  `session_id` VARCHAR(100) NOT NULL COMMENT 'Unique session identifier',
  `total_questions` INT DEFAULT 20 COMMENT 'Total questions in assessment',
  `correct_answers` INT DEFAULT 0 COMMENT 'Number of correct answers',
  `wrong_answers` INT DEFAULT 0 COMMENT 'Number of wrong answers',
  `score_percentage` DECIMAL(5,2) DEFAULT 0.00 COMMENT 'Score in percentage',
  `passing_score` DECIMAL(5,2) DEFAULT 70.00 COMMENT 'Minimum passing score',
  `passed` TINYINT(1) DEFAULT 0 COMMENT 'Did user pass?',
  `time_started` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'When assessment started',
  `time_completed` TIMESTAMP NULL DEFAULT NULL COMMENT 'When assessment completed',
  `duration_seconds` INT DEFAULT NULL COMMENT 'Total time taken in seconds',
  `status` ENUM('in_progress', 'completed', 'abandoned') DEFAULT 'in_progress',
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_session_id` (`session_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_assessment_session_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='User assessment sessions/attempts';

-- Table: user_assessment_responses (stores individual question responses)
CREATE TABLE IF NOT EXISTS `user_assessment_responses` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `session_id` VARCHAR(100) NOT NULL COMMENT 'Links to user_assessment_sessions',
  `user_id` INT NOT NULL COMMENT 'User who answered',
  `question_id` INT NOT NULL COMMENT 'Question answered',
  `question_number` INT NOT NULL COMMENT 'Question number',
  `user_answer` ENUM('True', 'False') NOT NULL COMMENT 'User selected answer',
  `correct_answer` ENUM('True', 'False') NOT NULL COMMENT 'Correct answer',
  `is_correct` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Is answer correct?',
  `answered_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'When answered',
  PRIMARY KEY (`id`),
  KEY `idx_session_id` (`session_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_question_id` (`question_id`),
  CONSTRAINT `fk_assessment_response_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_assessment_response_question` FOREIGN KEY (`question_id`) REFERENCES `assessments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='User answers to assessment questions';

-- Insert the 20 TRUE/FALSE assessment questions
INSERT INTO `assessments` (`question_number`, `question_text`, `correct_answer`, `category`, `explanation`) VALUES
-- Traffic Signs (Questions 1-5)
(1, 'A Red Traffic Light means vehicles must completely stop.', 'True', 'Traffic Signs', 'Red traffic lights indicate that vehicles must come to a complete stop and wait for the green light.'),
(2, 'A Right Curve sign means the road will bend to the left ahead.', 'False', 'Traffic Signs', 'A Right Curve sign indicates the road will bend to the RIGHT, not left.'),
(3, 'A No Entry sign allows vehicles to pass only if the road is clear.', 'False', 'Traffic Signs', 'No Entry signs strictly prohibit vehicle entry regardless of road conditions.'),
(4, 'A Two-Way Traffic sign means vehicles are allowed to drive in both directions on the road ahead.', 'True', 'Traffic Signs', 'Two-Way Traffic signs warn drivers that traffic flows in both directions ahead.'),
(5, 'A Zebra Crossing sign reminds drivers to yield and give way to pedestrians.', 'True', 'Traffic Signs', 'Zebra crossings are designated pedestrian crossing areas where drivers must yield to pedestrians.'),

-- Road Markings (Questions 6-10)
(6, 'A Broken Yellow Line means overtaking is allowed if the opposite lane is clear and safe.', 'True', 'Road Markings', 'Broken yellow lines permit overtaking when it is safe to do so.'),
(7, 'Double Solid Yellow Lines mean overtaking is allowed only if no vehicles are coming.', 'False', 'Road Markings', 'Double solid yellow lines strictly prohibit overtaking in either direction.'),
(8, 'A Single Solid White Line means lane changing is completely prohibited at all times.', 'False', 'Road Markings', 'Single solid white lines discourage lane changing but do not completely prohibit it in all situations.'),
(9, 'A Broken and Solid Yellow Line means the side with the broken line may overtake, while the side with the solid line cannot.', 'True', 'Road Markings', 'The broken line side may overtake safely, while the solid line side must not overtake.'),
(10, 'Double Solid White Lines mean lane changing is strictly prohibited in either direction.', 'True', 'Road Markings', 'Double solid white lines indicate that lane changing is not allowed from either side.'),

-- Traffic Rules & Rights (Questions 11-15)
(11, 'A driver has the right to ask for the traffic enforcer\'s ID and name before presenting documents.', 'True', 'Traffic Rules', 'Drivers have the right to verify the identity of traffic enforcers.'),
(12, 'It is legal for an enforcer to ask for "on-the-spot" cash payment without issuing an official receipt.', 'False', 'Traffic Rules', 'On-the-spot cash payments without official receipts are illegal and considered corruption.'),
(13, 'A driver can politely request to see the mission order of the apprehending officer.', 'True', 'Traffic Rules', 'Drivers have the right to verify that enforcers are on official duty by requesting their mission order.'),
(14, 'Every traffic violation automatically requires confiscation of the driver\'s license.', 'False', 'Traffic Rules', 'Not all violations require license confiscation. Minor violations may only result in a citation ticket.'),
(15, 'Drivers have the right to contest or appeal a ticket if they believe the apprehension was incorrect.', 'True', 'Traffic Rules', 'Drivers have legal rights to contest or appeal traffic violations they believe are unjust.'),

-- Emergency Response (Questions 16-20)
(16, 'You should panic and immediately brake hard if an engine explosion occurs.', 'False', 'Emergency Response', 'Panicking and hard braking can cause accidents. Stay calm and safely pull over.'),
(17, 'Activating hazard lights is a required safety step during a mechanical failure.', 'True', 'Emergency Response', 'Hazard lights alert other drivers to your vehicle\'s problem and help prevent collisions.'),
(18, 'It is safe to open the hood right away after an engine explosion.', 'False', 'Emergency Response', 'Opening the hood immediately can be dangerous. Wait for smoke/flames to subside and engine to cool.'),
(19, 'An Early Warning Device (EWD) must be placed at least 4 meters behind the vehicle on open roads.', 'True', 'Emergency Response', 'EWDs must be placed at a safe distance (at least 4 meters) to warn approaching traffic.'),
(20, 'You should never attempt to restart the engine after an explosion or fire.', 'True', 'Emergency Response', 'Attempting to restart after an explosion/fire can cause further damage or injury. Seek professional help.');

-- Verify the data
SELECT COUNT(*) as total_questions FROM assessments;
SELECT question_number, question_text, correct_answer, category FROM assessments ORDER BY question_number;
