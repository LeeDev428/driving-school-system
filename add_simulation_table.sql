-- Add simulation_results table to existing database
-- Run this SQL script in your database to add the simulation functionality

CREATE TABLE IF NOT EXISTS `simulation_results` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `simulation_type` varchar(50) NOT NULL DEFAULT 'basic_driving',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;