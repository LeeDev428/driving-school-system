-- =========================================
-- QUIZ SYSTEM TABLES
-- 50 Multiple Choice Questions (a, b, c, d)
-- =========================================

USE driving_school;

-- Table: quizzes (stores all quiz questions)
CREATE TABLE IF NOT EXISTS `quizzes` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `question_number` INT NOT NULL COMMENT 'Question number (1-50)',
  `question_text` TEXT NOT NULL COMMENT 'The question text',
  `option_a` VARCHAR(255) NOT NULL COMMENT 'Option A',
  `option_b` VARCHAR(255) NOT NULL COMMENT 'Option B',
  `option_c` VARCHAR(255) NOT NULL COMMENT 'Option C',
  `option_d` VARCHAR(255) NOT NULL COMMENT 'Option D',
  `correct_answer` ENUM('a', 'b', 'c', 'd') NOT NULL COMMENT 'Correct answer',
  `category` VARCHAR(100) DEFAULT NULL COMMENT 'Traffic Lights, Road Signs, Emergency, Road Markings, Driving Rules, Protocol Plates',
  `explanation` TEXT DEFAULT NULL COMMENT 'Explanation for the correct answer',
  `is_active` TINYINT(1) DEFAULT 1 COMMENT 'Is this question active?',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_question_number` (`question_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Quiz questions (Multiple Choice)';

-- Table: user_quiz_sessions (stores user quiz attempts)
CREATE TABLE IF NOT EXISTS `user_quiz_sessions` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL COMMENT 'User taking the quiz',
  `session_id` VARCHAR(100) NOT NULL COMMENT 'Unique session identifier',
  `total_questions` INT DEFAULT 50 COMMENT 'Total questions in quiz',
  `correct_answers` INT DEFAULT 0 COMMENT 'Number of correct answers',
  `wrong_answers` INT DEFAULT 0 COMMENT 'Number of wrong answers',
  `score_percentage` DECIMAL(5,2) DEFAULT 0.00 COMMENT 'Score in percentage',
  `passing_score` DECIMAL(5,2) DEFAULT 70.00 COMMENT 'Minimum passing score',
  `passed` TINYINT(1) DEFAULT 0 COMMENT 'Did user pass?',
  `time_started` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'When quiz started',
  `time_completed` TIMESTAMP NULL DEFAULT NULL COMMENT 'When quiz completed',
  `duration_seconds` INT DEFAULT NULL COMMENT 'Total time taken in seconds',
  `status` ENUM('in_progress', 'completed', 'abandoned') DEFAULT 'in_progress',
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_session_id` (`session_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_quiz_session_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='User quiz sessions/attempts';

-- Table: user_quiz_responses (stores individual question responses)
CREATE TABLE IF NOT EXISTS `user_quiz_responses` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `session_id` VARCHAR(100) NOT NULL COMMENT 'Links to user_quiz_sessions',
  `user_id` INT NOT NULL COMMENT 'User who answered',
  `question_id` INT NOT NULL COMMENT 'Question answered',
  `question_number` INT NOT NULL COMMENT 'Question number',
  `user_answer` ENUM('a', 'b', 'c', 'd') NOT NULL COMMENT 'User selected answer',
  `correct_answer` ENUM('a', 'b', 'c', 'd') NOT NULL COMMENT 'Correct answer',
  `is_correct` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Is answer correct?',
  `answered_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'When answered',
  PRIMARY KEY (`id`),
  KEY `idx_session_id` (`session_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_question_id` (`question_id`),
  CONSTRAINT `fk_quiz_response_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_quiz_response_question` FOREIGN KEY (`question_id`) REFERENCES `quizzes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='User answers to quiz questions';

-- Insert the 50 Multiple Choice Questions
INSERT INTO `quizzes` (`question_number`, `question_text`, `option_a`, `option_b`, `option_c`, `option_d`, `correct_answer`, `category`) VALUES
-- Traffic Lights (1-3)
(1, 'A red traffic light means:', 'Go', 'Stop', 'Slow down', 'Turn right', 'b', 'Traffic Lights'),
(2, 'A yellow (amber) light indicates:', 'Stop immediately no matter what', 'Prepare to stop', 'Go faster', 'Ignore', 'b', 'Traffic Lights'),
(3, 'A green light means:', 'Stop', 'Prepare to stop', 'Go', 'U-turn only', 'c', 'Traffic Lights'),

-- Road Signs (4-20)
(4, 'What does a Right Curve sign indicate?', 'The road bends left', 'The road bends right ahead', 'A sharp zigzag', 'Two-way traffic', 'b', 'Road Signs'),
(5, 'A Left Curve sign means:', 'Slow down, the road bends left', 'Speed up', 'Road narrows', 'Stop immediately', 'a', 'Road Signs'),
(6, 'A Right Zigzag Bend sign means:', 'Straight road', 'Multiple sharp right curves ahead', 'Left curve only', 'No U-turn', 'b', 'Road Signs'),
(7, 'A Narrow Road Left sign indicates:', 'The right side narrows', 'The left side narrows', 'Two-way traffic', 'Parking only', 'b', 'Road Signs'),
(8, 'A Two-Way Traffic sign means:', 'Road is one way', 'Road carries traffic in both directions', 'Stop immediately', 'Highway ahead', 'b', 'Road Signs'),
(9, 'A No Left Turn sign means:', 'Left turns allowed', 'Left turns not allowed', 'U-turn only', 'Pedestrian crossing', 'b', 'Road Signs'),
(10, 'A No Entry sign means:', 'Vehicles prohibited from entering', 'Parking allowed', 'One-way road', 'Cycle zone', 'a', 'Road Signs'),
(11, 'A No Right Turn sign means:', 'Right turns not allowed', 'Left turns not allowed', 'U-turns required', 'Road narrows', 'a', 'Road Signs'),
(12, 'A U-Turn sign indicates:', 'U-turn not allowed', 'U-turn allowed at that point', 'Stop immediately', 'Road closed', 'b', 'Road Signs'),
(13, 'A No U-Turn sign means:', 'U-turns are allowed', 'U-turns are not allowed', 'Only left turns allowed', 'Stop and yield', 'b', 'Road Signs'),
(14, 'A Narrow Bridge sign warns:', 'Bridge ahead is wide', 'Bridge ahead is narrow', 'Road closed', 'Highway intersection', 'b', 'Road Signs'),
(15, 'A Load Limit sign indicates:', 'Speed limit', 'Maximum vehicle weight allowed', 'Parking zone', 'No overtaking', 'b', 'Road Signs'),
(16, 'A Steep Hill sign means:', 'Flat road ahead', 'Sharp incline or decline ahead', 'No U-turns allowed', 'Road widening', 'b', 'Road Signs'),
(17, 'A Rough Road sign indicates:', 'Smooth pavement', 'Uneven or damaged road surface', 'Road construction only', 'Narrow bridge', 'b', 'Road Signs'),
(18, 'A One Way sign means:', 'Vehicles travel in both directions', 'Vehicles travel only in one direction', 'Road closed', 'No U-turns', 'b', 'Road Signs'),
(19, 'A No Parking sign means:', 'Parking allowed anytime', 'No parking in the marked area', 'Parking allowed but limited', 'No honking', 'b', 'Road Signs'),
(20, 'A Zebra Crossing sign means:', 'Pedestrian crosswalk ahead', 'Parking zone', 'No entry', 'Narrow road', 'a', 'Road Signs'),

-- Road Markings (21-24)
(21, 'A Broken Line means:', 'No overtaking allowed', 'Overtaking allowed if safe', 'Lane changing prohibited', 'Road closed', 'b', 'Road Markings'),
(22, 'Double Solid White Lines mean:', 'Overtaking allowed', 'Overtaking discouraged', 'Overtaking strictly prohibited', 'U-turn allowed', 'c', 'Road Markings'),
(23, 'Broken Yellow Line indicates:', 'Overtaking allowed with caution', 'No U-turns', 'Road closed', 'Parking only', 'a', 'Road Markings'),
(24, 'Double Solid Yellow Lines mean:', 'Passing allowed both sides', 'No overtaking allowed either direction', 'Parking allowed', 'U-turns allowed', 'b', 'Road Markings'),

-- Emergency Response (25-30)
(25, 'If your engine explodes while driving, the first rule is:', 'Panic and brake hard', 'Stay calm and avoid sudden braking', 'Restart the engine', 'Exit immediately', 'b', 'Emergency Response'),
(26, 'After an explosion, should you open the hood right away?', 'Yes', 'No, wait for heat/pressure to subside', 'Only if safe', 'Call towing service first', 'b', 'Emergency Response'),
(27, 'What should you place at least 4 meters behind the vehicle during breakdown?', 'Speed bump', 'Early Warning Device (EWD)', 'Stop sign', 'Fire extinguisher', 'b', 'Emergency Response'),
(28, 'What fire extinguisher should be used for fuel/electrical fires?', 'Class A', 'Class B or C', 'Water only', 'Sand', 'b', 'Emergency Response'),
(29, 'Restarting the engine after an explosion is:', 'Allowed if safe', 'Always prohibited', 'Required by LTO', 'Optional', 'b', 'Emergency Response'),
(30, 'After an accident, documentation is important for:', 'Insurance claims and investigation', 'Entertainment', 'Driving school', 'Exam review', 'a', 'Emergency Response'),

-- Driving Rules (31-40)
(31, 'Which of the following is a reality of driving?', 'All drivers follow the law', 'Always assume hazards exist', 'Right of way is absolute', 'Speeding at night is allowed', 'b', 'Driving Rules'),
(32, 'Seatbelts are required for:', 'Only front seat passengers', 'Only drivers', 'All passengers including back seat', 'Only children', 'c', 'Driving Rules'),
(33, 'Overtaking is not allowed on:', 'Bridges and curves', 'Open highways', 'Parking zones', 'One-way streets', 'a', 'Driving Rules'),
(34, 'Headlights must be on:', 'Only when pitch dark', 'Starting at sunset until sunrise', 'Only during rain', 'At all times', 'b', 'Driving Rules'),
(35, 'Pedestrians have right of way at pedestrian crossings:', 'Only if there\'s a green light', 'Always', 'Only at night', 'Never', 'b', 'Driving Rules'),
(36, 'You have the right to ask for:', 'Enforcer\'s ID and name', 'Enforcer\'s car keys', 'Free pass', 'License plate', 'a', 'Driving Rules'),
(37, 'Can you be forced to pay on the spot without an official receipt?', 'Yes', 'No', 'Sometimes', 'Only in emergencies', 'b', 'Driving Rules'),
(38, 'When issued a ticket, you have the right to:', 'Ignore it', 'Receive an official copy with control number', 'Refuse it always', 'Pay cash immediately', 'b', 'Driving Rules'),
(39, 'Your license can be confiscated:', 'Anytime', 'Only if violation requires it', 'Never', 'Only by MMDA', 'b', 'Driving Rules'),
(40, 'Are you allowed to record the encounter with a traffic officer?', 'No', 'Yes, if not obstructive', 'Only secretly', 'Only if officer agrees', 'b', 'Driving Rules'),

-- Protocol Plates (41-47)
(41, 'Which plate number belongs to the President of the Philippines?', '5', '1', '10', '14', 'b', 'Protocol Plates'),
(42, 'Which plate number belongs to the Vice President?', '2', '5', '7', '8', 'a', 'Protocol Plates'),
(43, 'Plate No. 7 is for:', 'Senators', 'Cabinet Secretaries', 'Supreme Court Justices', 'PNP Chief', 'a', 'Protocol Plates'),
(44, 'Plate No. 8 is for:', 'House of Representatives members', 'Supreme Court Chief Justice', 'Senators', 'President', 'a', 'Protocol Plates'),
(45, 'Protocol plates are valid:', 'Forever', 'Only during incumbency', '10 years', 'Transferable to family', 'b', 'Protocol Plates'),
(46, 'Misuse of protocol plates is:', 'Allowed if for family', 'Punishable', 'Encouraged', 'Optional', 'b', 'Protocol Plates'),
(47, 'How many pairs of plates are allowed for the President?', '1', '2', '3', '4', 'c', 'Protocol Plates'),

-- Safety Rules (48-50)
(48, 'Children below 12 or shorter than 4\'11" must not sit:', 'In the front seat', 'At the back seat', 'On the driver\'s lap', 'On booster seat', 'a', 'Safety Rules'),
(49, 'Using cellphones while driving is:', 'Allowed if on loudspeaker', 'Strictly prohibited', 'Only for emergency calls', 'Encouraged', 'b', 'Safety Rules'),
(50, 'Drinking and driving is:', 'Allowed if careful', 'Punishable under RA 10586', 'Legal at night', 'Optional rule', 'b', 'Safety Rules');

-- Verify the data
SELECT COUNT(*) as total_questions FROM quizzes;
SELECT question_number, LEFT(question_text, 50) as question, correct_answer, category FROM quizzes ORDER BY question_number;
