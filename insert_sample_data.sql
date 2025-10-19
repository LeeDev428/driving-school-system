-- ============================================
-- SAMPLE DATA FOR DRIVING SCHOOL SYSTEM
-- Run this in HeidiSQL after creating database
-- ============================================

USE driving_school;

-- Clear existing data (optional - comment out if you want to keep existing data)
-- SET FOREIGN_KEY_CHECKS = 0;
-- TRUNCATE TABLE appointments;
-- TRUNCATE TABLE instructors;
-- TRUNCATE TABLE vehicles;
-- TRUNCATE TABLE appointment_types;
-- TRUNCATE TABLE users;
-- SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- 1. INSERT USERS (Admin, Instructors, Students)
-- ============================================

-- Admin user (password: admin123)
INSERT INTO `users` (`id`, `username`, `password`, `email`, `full_name`, `phone`, `address`, `user_type`, `status`, `created_at`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@successdriving.com', 'Admin User', '09171234567', 'Manila, Philippines', 'admin', 'active', NOW());

-- Instructor users (password: instructor123)
INSERT INTO `users` (`id`, `username`, `password`, `email`, `full_name`, `phone`, `address`, `user_type`, `status`, `created_at`) VALUES
(2, 'instructor1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'juan.santos@successdriving.com', 'Juan Santos', '09181234567', 'Quezon City, Philippines', 'instructor', 'active', '2025-10-10 08:00:00'),
(3, 'instructor2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'maria.reyes@successdriving.com', 'Maria Reyes', '09191234567', 'Makati City, Philippines', 'instructor', 'active', '2025-10-11 09:30:00'),
(4, 'instructor3', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'pedro.cruz@successdriving.com', 'Pedro Cruz', '09201234567', 'Pasig City, Philippines', 'instructor', 'active', '2025-10-12 10:00:00'),
(5, 'instructor4', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ana.lopez@successdriving.com', 'Ana Lopez', '09211234567', 'Mandaluyong, Philippines', 'instructor', 'active', '2025-10-13 11:00:00'),
(6, 'instructor5', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'jose.garcia@successdriving.com', 'Jose Garcia', '09221234567', 'Taguig City, Philippines', 'instructor', 'active', '2025-10-14 14:00:00');

-- Student users (password: student123)
INSERT INTO `users` (`id`, `username`, `password`, `email`, `full_name`, `phone`, `address`, `user_type`, `status`, `created_at`) VALUES
(7, 'student1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'john.doe@email.com', 'John Doe', '09231234567', 'Pasay City, Philippines', 'student', 'active', '2025-10-01 08:00:00'),
(8, 'student2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'jane.smith@email.com', 'Jane Smith', '09241234567', 'Caloocan City, Philippines', 'student', 'active', '2025-10-02 09:00:00'),
(9, 'student3', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'mike.johnson@email.com', 'Mike Johnson', '09251234567', 'Paranaque City, Philippines', 'student', 'active', '2025-10-05 10:00:00'),
(10, 'student4', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'sarah.wilson@email.com', 'Sarah Wilson', '09261234567', 'Las Pinas City, Philippines', 'student', 'active', '2025-10-08 11:00:00'),
(11, 'student5', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'david.brown@email.com', 'David Brown', '09271234567', 'Muntinlupa City, Philippines', 'student', 'active', '2025-10-12 12:00:00'),
(12, 'student6', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'emma.davis@email.com', 'Emma Davis', '09281234567', 'Marikina City, Philippines', 'student', 'active', '2025-10-15 13:00:00'),
(13, 'student7', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'chris.miller@email.com', 'Chris Miller', '09291234567', 'Valenzuela City, Philippines', 'student', 'active', NOW());

-- ============================================
-- 2. INSERT INSTRUCTORS
-- ============================================
INSERT INTO `instructors` (`id`, `user_id`, `license_number`, `specializations`, `years_experience`, `hourly_rate`, `is_active`, `date_hired`, `created_at`) VALUES
(1, 2, 'DL-2018-001234', 'Manual Transmission, Highway Driving', 7, 500.00, 1, '2018-06-15', '2025-10-10 08:00:00'),
(2, 3, 'DL-2019-005678', 'Automatic Transmission, Defensive Driving', 6, 450.00, 1, '2019-03-20', '2025-10-11 09:30:00'),
(3, 4, 'DL-2020-009012', 'Manual Transmission, Parking Skills', 5, 400.00, 1, '2020-01-10', '2025-10-12 10:00:00'),
(4, 5, 'DL-2021-003456', 'Automatic Transmission, City Driving', 4, 380.00, 1, '2021-05-15', '2025-10-13 11:00:00'),
(5, 6, 'DL-2022-007890', 'Manual & Automatic, Night Driving', 3, 350.00, 1, '2022-08-01', '2025-10-14 14:00:00');

-- ============================================
-- 3. INSERT VEHICLES
-- ============================================
INSERT INTO `vehicles` (`id`, `make`, `model`, `year`, `plate_number`, `transmission_type`, `fuel_type`, `status`, `color`, `capacity`, `created_at`) VALUES
(1, 'Toyota', 'Vios', 2023, 'ABC 1234', 'manual', 'gasoline', 'available', 'White', 5, '2025-10-01 08:00:00'),
(2, 'Honda', 'City', 2023, 'DEF 5678', 'automatic', 'gasoline', 'available', 'Silver', 5, '2025-10-02 09:00:00'),
(3, 'Mitsubishi', 'Mirage', 2022, 'GHI 9012', 'manual', 'gasoline', 'available', 'Red', 5, '2025-10-03 10:00:00'),
(4, 'Suzuki', 'Swift', 2023, 'JKL 3456', 'automatic', 'gasoline', 'in_use', 'Blue', 5, '2025-10-04 11:00:00'),
(5, 'Nissan', 'Almera', 2022, 'MNO 7890', 'manual', 'gasoline', 'available', 'Black', 5, '2025-10-05 12:00:00'),
(6, 'Hyundai', 'Accent', 2023, 'PQR 2345', 'automatic', 'gasoline', 'maintenance', 'Gray', 5, '2025-10-06 13:00:00'),
(7, 'Mazda', 'Mazda2', 2022, 'STU 6789', 'manual', 'gasoline', 'available', 'White', 5, '2025-10-07 14:00:00');

-- ============================================
-- 4. INSERT APPOINTMENT TYPES
-- ============================================
INSERT INTO `appointment_types` (`id`, `name`, `description`, `duration_minutes`, `price`, `color`, `is_active`) VALUES
(1, 'TDC - Theoretical Driving Course', 'Classroom-based theoretical instruction', 480, 899.00, '#667eea', 1),
(2, 'PDC - Practical Driving Course (Manual)', 'Hands-on driving practice with manual transmission', 240, 2000.00, '#f5576c', 1),
(3, 'PDC - Practical Driving Course (Automatic)', 'Hands-on driving practice with automatic transmission', 240, 4500.00, '#4bc0c0', 1),
(4, 'Refresher Course', 'Review and practice for experienced drivers', 120, 1200.00, '#ffcc00', 1),
(5, 'Highway Driving', 'Specialized highway and expressway training', 180, 1500.00, '#9966ff', 1),
(6, 'Parking Skills', 'Focused training on parking techniques', 90, 800.00, '#ff9800', 1);

-- ============================================
-- 5. INSERT APPOINTMENTS
-- ============================================
INSERT INTO `appointments` (`id`, `student_id`, `instructor_id`, `vehicle_id`, `appointment_type_id`, `course_selection`, `appointment_date`, `start_time`, `end_time`, `status`, `payment_status`, `payment_amount`, `created_at`) VALUES
-- Today's appointments
(1, 7, 1, 1, 2, 'PDC', CURDATE(), '08:00:00', '12:00:00', 'confirmed', 'paid', 2000.00, '2025-10-14 10:00:00'),
(2, 8, 2, 2, 3, 'PDC', CURDATE(), '13:00:00', '17:00:00', 'confirmed', 'paid', 4500.00, '2025-10-14 11:00:00'),
(3, 9, 3, 3, 4, 'PDC', CURDATE(), '09:00:00', '11:00:00', 'pending', 'unpaid', 1200.00, '2025-10-15 08:00:00'),

-- Yesterday's appointments
(4, 10, 4, 4, 2, 'PDC', DATE_SUB(CURDATE(), INTERVAL 1 DAY), '10:00:00', '14:00:00', 'completed', 'paid', 2000.00, '2025-10-13 09:00:00'),

-- Upcoming appointments
(5, 11, 5, 5, 5, 'PDC', DATE_ADD(CURDATE(), INTERVAL 1 DAY), '08:00:00', '11:00:00', 'confirmed', 'paid', 1500.00, '2025-10-14 12:00:00'),
(6, 12, 1, 1, 2, 'PDC', DATE_ADD(CURDATE(), INTERVAL 2 DAY), '14:00:00', '18:00:00', 'pending', 'unpaid', 2000.00, '2025-10-15 14:00:00'),
(7, 13, 2, 2, 1, 'TDC', DATE_ADD(CURDATE(), INTERVAL 3 DAY), '09:00:00', '17:00:00', 'pending', 'unpaid', 899.00, NOW()),

-- Past appointments
(8, 7, 3, 3, 6, 'PDC', DATE_SUB(CURDATE(), INTERVAL 3 DAY), '10:00:00', '11:30:00', 'completed', 'paid', 800.00, '2025-10-10 08:00:00'),
(9, 8, 4, 4, 2, 'PDC', DATE_SUB(CURDATE(), INTERVAL 5 DAY), '08:00:00', '12:00:00', 'completed', 'paid', 2000.00, '2025-10-08 09:00:00'),
(10, 9, 5, 5, 3, 'PDC', DATE_SUB(CURDATE(), INTERVAL 7 DAY), '13:00:00', '17:00:00', 'completed', 'paid', 4500.00, '2025-10-06 10:00:00');

-- ============================================
-- 6. INSERT SAMPLE ASSESSMENT SESSIONS
-- ============================================
INSERT INTO `user_assessment_sessions` (`user_id`, `score`, `total_questions`, `passed`, `status`, `started_at`, `completed_at`) VALUES
(7, 85.00, 20, 1, 'completed', '2025-10-01 10:00:00', '2025-10-01 10:15:00'),
(8, 90.00, 20, 1, 'completed', '2025-10-02 11:00:00', '2025-10-02 11:18:00'),
(9, 75.00, 20, 1, 'completed', '2025-10-05 14:00:00', '2025-10-05 14:20:00'),
(10, 65.00, 20, 0, 'completed', '2025-10-08 09:00:00', '2025-10-08 09:25:00'),
(11, 80.00, 20, 1, 'completed', '2025-10-12 15:00:00', '2025-10-12 15:17:00');

-- ============================================
-- 7. INSERT SAMPLE QUIZ SESSIONS
-- ============================================
INSERT INTO `user_quiz_sessions` (`user_id`, `score`, `total_questions`, `passed`, `status`, `started_at`, `completed_at`) VALUES
(7, 78.00, 50, 1, 'completed', '2025-10-02 10:00:00', '2025-10-02 10:45:00'),
(8, 88.00, 50, 1, 'completed', '2025-10-03 11:00:00', '2025-10-03 11:52:00'),
(9, 72.00, 50, 1, 'completed', '2025-10-06 14:00:00', '2025-10-06 14:48:00'),
(11, 82.00, 50, 1, 'completed', '2025-10-13 15:00:00', '2025-10-13 15:55:00');

-- ============================================
-- DONE! Sample data inserted successfully
-- ============================================

SELECT '✅ Sample data inserted successfully!' as Status;
SELECT 'Total Users:', COUNT(*) FROM users;
SELECT 'Total Instructors:', COUNT(*) FROM instructors;
SELECT 'Total Vehicles:', COUNT(*) FROM vehicles;
SELECT 'Total Appointments:', COUNT(*) FROM appointments;
SELECT 'Total Appointment Types:', COUNT(*) FROM appointment_types;
