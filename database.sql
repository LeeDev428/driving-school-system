-- Success Driving School Database Structure
-- Run this SQL in your phpMyAdmin or MySQL client

-- Drop existing database and create fresh one (uncomment if needed)
-- DROP DATABASE IF EXISTS driving_school;
-- CREATE DATABASE driving_school;
-- USE driving_school;

-- Users table (this might already exist, if so just add the missing columns)
CREATE TABLE IF NOT EXISTS users (
    id int(11) NOT NULL AUTO_INCREMENT,
    full_name varchar(100) NOT NULL,
    email varchar(100) UNIQUE NOT NULL,
    password varchar(255) NOT NULL,
    contact_number varchar(20),
    license_type varchar(50),
    user_type enum('admin','student','instructor') DEFAULT 'student',
    profile_image varchar(255),
    date_of_birth date,
    address text,
    emergency_contact varchar(100),
    emergency_phone varchar(20),
    created_at timestamp DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
);

-- Instructors table
CREATE TABLE IF NOT EXISTS instructors (
    id int(11) NOT NULL AUTO_INCREMENT,
    user_id int(11) NOT NULL,
    license_number varchar(50),
    specializations text,
    years_experience int,
    hourly_rate decimal(10,2),
    is_active boolean DEFAULT true,
    created_at timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Vehicles table
CREATE TABLE IF NOT EXISTS vehicles (
    id int(11) NOT NULL AUTO_INCREMENT,
    make varchar(50) NOT NULL,
    model varchar(50) NOT NULL,
    year int NOT NULL,
    license_plate varchar(20) UNIQUE NOT NULL,
    transmission_type enum('manual','automatic') NOT NULL,
    vehicle_type enum('sedan','suv','truck','motorcycle') DEFAULT 'sedan',
    color varchar(30),
    is_available boolean DEFAULT true,
    last_maintenance date,
    next_maintenance date,
    notes text,
    created_at timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
);

-- Appointment types table
CREATE TABLE IF NOT EXISTS appointment_types (
    id int(11) NOT NULL AUTO_INCREMENT,
    name varchar(100) NOT NULL,
    description text,
    duration_minutes int NOT NULL DEFAULT 60,
    price decimal(10,2) NOT NULL DEFAULT 0.00,
    color varchar(7) DEFAULT '#007bff',
    is_active boolean DEFAULT true,
    PRIMARY KEY (id)
);

-- Appointments table
CREATE TABLE IF NOT EXISTS appointments (
    id int(11) NOT NULL AUTO_INCREMENT,
    student_id int(11) NOT NULL,
    instructor_id int(11),
    vehicle_id int(11),
    appointment_type_id int(11) NOT NULL,
    appointment_date date NOT NULL,
    start_time time NOT NULL,
    end_time time NOT NULL,
    status enum('pending','confirmed','in_progress','completed','cancelled','no_show') DEFAULT 'pending',
    notes text,
    student_notes text,
    instructor_notes text,
    payment_status enum('unpaid','paid','refunded') DEFAULT 'unpaid',
    created_at timestamp DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (instructor_id) REFERENCES instructors(id) ON DELETE SET NULL,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE SET NULL,
    FOREIGN KEY (appointment_type_id) REFERENCES appointment_types(id) ON DELETE CASCADE
);

-- Appointment history/logs table
CREATE TABLE IF NOT EXISTS appointment_logs (
    id int(11) NOT NULL AUTO_INCREMENT,
    appointment_id int(11) NOT NULL,
    action varchar(100) NOT NULL,
    old_status varchar(50),
    new_status varchar(50),
    changed_by int(11),
    notes text,
    created_at timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Insert default admin user (password: admin123)
INSERT IGNORE INTO users (id, full_name, email, password, user_type) VALUES 
(1, 'System Administrator', 'admin@successdriving.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Insert sample instructors
INSERT IGNORE INTO users (full_name, email, password, user_type, contact_number) VALUES 
('Robert Johnson', 'robert.johnson@successdriving.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'instructor', '555-0101'),
('Maria Garcia', 'maria.garcia@successdriving.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'instructor', '555-0102'),
('David Wilson', 'david.wilson@successdriving.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'instructor', '555-0103');

-- Insert instructor details
INSERT IGNORE INTO instructors (user_id, license_number, specializations, years_experience, hourly_rate) VALUES 
((SELECT id FROM users WHERE email = 'robert.johnson@successdriving.com'), 'INS001', 'Highway Driving, Parking', 8, 45.00),
((SELECT id FROM users WHERE email = 'maria.garcia@successdriving.com'), 'INS002', 'City Driving, Road Test Prep', 5, 40.00),
((SELECT id FROM users WHERE email = 'david.wilson@successdriving.com'), 'INS003', 'Manual Transmission, Defensive Driving', 12, 50.00);

-- Insert sample vehicles
INSERT IGNORE INTO vehicles (make, model, year, license_plate, transmission_type, vehicle_type, color) VALUES 
('Toyota', 'Corolla', 2022, 'ABC-123', 'automatic', 'sedan', 'White'),
('Honda', 'Civic', 2021, 'XYZ-789', 'manual', 'sedan', 'Blue'),
('Toyota', 'RAV4', 2023, 'DEF-456', 'automatic', 'suv', 'Silver'),
('Nissan', 'Sentra', 2022, 'GHI-789', 'automatic', 'sedan', 'Black');

-- Insert appointment types
INSERT IGNORE INTO appointment_types (name, description, duration_minutes, price, color) VALUES 
('Driving Lesson', 'Standard driving lesson with instructor', 60, 50.00, '#4CAF50'),
('Driving Test', 'Official driving test for license', 90, 75.00, '#2196F3'),
('Parking Practice', 'Focused parking skills training', 45, 35.00, '#FF9800'),
('Highway Driving', 'Highway and freeway driving training', 90, 65.00, '#9C27B0'),
('Night Driving', 'Night time driving practice', 60, 55.00, '#607D8B'),
('Road Test Prep', 'Preparation for road test', 75, 60.00, '#795548');

-- Insert sample student for testing
INSERT IGNORE INTO users (full_name, email, password, user_type, contact_number, license_type) VALUES 
('Michael Brown', 'michael.brown@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', '555-1234', 'Class B'),
('Sarah Wilson', 'sarah.wilson@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', '555-5678', 'Class B'),
('James Taylor', 'james.taylor@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', '555-9012', 'Class C'),
('Emily Davis', 'emily.davis@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', '555-3456', 'Class A'),
('Christopher Miller', 'christopher.miller@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', '555-7890', 'Class B');

-- Insert sample appointments for testing
INSERT IGNORE INTO appointments (student_id, instructor_id, vehicle_id, appointment_type_id, appointment_date, start_time, end_time, status, payment_status) VALUES 
((SELECT id FROM users WHERE email = 'michael.brown@email.com'), 1, 1, 1, CURDATE(), '10:00:00', '11:00:00', 'confirmed', 'paid'),
((SELECT id FROM users WHERE email = 'sarah.wilson@email.com'), 2, 2, 2, CURDATE(), '13:00:00', '14:30:00', 'confirmed', 'paid'),
((SELECT id FROM users WHERE email = 'michael.brown@email.com'), 1, 1, 1, '2023-07-10', '10:00:00', '11:00:00', 'completed', 'paid'),
((SELECT id FROM users WHERE email = 'sarah.wilson@email.com'), 2, 2, 2, '2023-07-08', '13:00:00', '14:30:00', 'completed', 'paid'),
((SELECT id FROM users WHERE email = 'james.taylor@email.com'), 1, 1, 3, '2023-07-05', '14:00:00', '14:45:00', 'cancelled', 'refunded'),
((SELECT id FROM users WHERE email = 'emily.davis@email.com'), 3, 3, 4, '2023-07-03', '16:00:00', '17:30:00', 'completed', 'paid'),
((SELECT id FROM users WHERE email = 'christopher.miller@email.com'), 2, 2, 1, '2023-06-30', '15:00:00', '16:00:00', 'completed', 'paid');

-- Add some future appointments
INSERT IGNORE INTO appointments (student_id, instructor_id, vehicle_id, appointment_type_id, appointment_date, start_time, end_time, status, payment_status) VALUES 
((SELECT id FROM users WHERE email = 'michael.brown@email.com'), 1, 1, 1, DATE_ADD(CURDATE(), INTERVAL 2 DAY), '10:00:00', '11:00:00', 'confirmed', 'paid'),
((SELECT id FROM users WHERE email = 'sarah.wilson@email.com'), 2, 2, 2, DATE_ADD(CURDATE(), INTERVAL 3 DAY), '14:00:00', '15:30:00', 'pending', 'unpaid');
