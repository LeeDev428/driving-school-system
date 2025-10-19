-- ============================================================================
-- COMPLETE DATABASE UPDATE FOR DRIVING SCHOOL SYSTEM
-- ============================================================================
-- This script updates your existing database with:
-- 1. Email reminder system (reminder_sent, reminder_sent_at)
-- 2. PDC time slot management (pdc_time_slots table + column)
-- 3. GCash payment system (payment_proof for screenshot uploads)
-- 4. TDC sessions table (for Friday/Saturday scheduling)
-- 5. Database triggers for automatic booking count updates
-- ============================================================================
-- Compatible with: MySQL 8.4.3 / HeidiSQL / Laragon
-- Run this entire file in HeidiSQL
-- ============================================================================

USE driving_school;

-- ============================================================================
-- STEP 1: ADD EMAIL REMINDER COLUMNS TO APPOINTMENTS
-- ============================================================================
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                   WHERE TABLE_SCHEMA = 'driving_school' 
                   AND TABLE_NAME = 'appointments' 
                   AND COLUMN_NAME = 'reminder_sent');

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE appointments ADD COLUMN reminder_sent TINYINT(1) DEFAULT 0 COMMENT "Whether reminder email has been sent"',
    'SELECT "✓ Column reminder_sent already exists" AS status');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                   WHERE TABLE_SCHEMA = 'driving_school' 
                   AND TABLE_NAME = 'appointments' 
                   AND COLUMN_NAME = 'reminder_sent_at');

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE appointments ADD COLUMN reminder_sent_at DATETIME NULL COMMENT "When reminder email was sent"',
    'SELECT "✓ Column reminder_sent_at already exists" AS status');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT '✓ STEP 1 COMPLETE: Email reminder columns added' AS Status;

-- ============================================================================
-- STEP 2: ADD GCASH PAYMENT PROOF COLUMN
-- ============================================================================
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                   WHERE TABLE_SCHEMA = 'driving_school' 
                   AND TABLE_NAME = 'appointments' 
                   AND COLUMN_NAME = 'payment_proof');

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE appointments ADD COLUMN payment_proof VARCHAR(255) NULL COMMENT "Filename of uploaded payment screenshot"',
    'SELECT "✓ Column payment_proof already exists" AS status');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT '✓ STEP 2 COMPLETE: GCash payment proof column added' AS Status;

-- ============================================================================
-- STEP 3: ADD COURSE SELECTION COLUMN (for PDC/TDC selection)
-- ============================================================================
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                   WHERE TABLE_SCHEMA = 'driving_school' 
                   AND TABLE_NAME = 'appointments' 
                   AND COLUMN_NAME = 'course_selection');

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE appointments ADD COLUMN course_selection VARCHAR(10) DEFAULT NULL COMMENT "PDC or TDC selection"',
    'SELECT "✓ Column course_selection already exists" AS status');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT '✓ STEP 3 COMPLETE: Course selection column added' AS Status;

-- ============================================================================
-- STEP 4: CREATE PDC TIME SLOTS TABLE
-- ============================================================================
CREATE TABLE IF NOT EXISTS `pdc_time_slots` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `slot_date` DATE NOT NULL COMMENT 'The date for this time slot',
    `slot_time_start` TIME NOT NULL COMMENT 'Start time',
    `slot_time_end` TIME NOT NULL COMMENT 'End time',
    `slot_label` VARCHAR(50) NOT NULL COMMENT 'Display label like "8:00 AM - 12:00 PM"',
    `instructor_id` INT NULL COMMENT 'Assigned instructor',
    `max_bookings` INT DEFAULT 3 COMMENT 'Maximum bookings allowed',
    `current_bookings` INT DEFAULT 0 COMMENT 'Current number of bookings',
    `is_available` TINYINT(1) DEFAULT 1 COMMENT 'Available for booking',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_slot_date` (`slot_date`),
    INDEX `idx_instructor` (`instructor_id`),
    INDEX `idx_available` (`is_available`),
    UNIQUE KEY `unique_slot` (`slot_date`, `slot_time_start`, `instructor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

SELECT '✓ STEP 4 COMPLETE: PDC time slots table created' AS Status;

-- ============================================================================
-- STEP 5: CREATE TDC SESSIONS TABLE
-- ============================================================================
CREATE TABLE IF NOT EXISTS `tdc_sessions` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `session_date` DATE NOT NULL COMMENT 'Friday or Saturday only',
    `session_time` TIME NOT NULL COMMENT 'Session start time',
    `session_label` VARCHAR(100) NOT NULL COMMENT 'Display label',
    `max_students` INT DEFAULT 30 COMMENT 'Maximum students per session',
    `enrolled_students` INT DEFAULT 0 COMMENT 'Current enrolled students',
    `instructor_id` INT NULL COMMENT 'Assigned instructor',
    `is_available` TINYINT(1) DEFAULT 1 COMMENT 'Available for booking',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_session_date` (`session_date`),
    INDEX `idx_instructor` (`instructor_id`),
    INDEX `idx_available` (`is_available`),
    UNIQUE KEY `unique_session` (`session_date`, `session_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

SELECT '✓ STEP 5 COMPLETE: TDC sessions table created' AS Status;

-- ============================================================================
-- STEP 6: ADD PDC TIME SLOT FOREIGN KEY TO APPOINTMENTS
-- ============================================================================
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                   WHERE TABLE_SCHEMA = 'driving_school' 
                   AND TABLE_NAME = 'appointments' 
                   AND COLUMN_NAME = 'pdc_time_slot_id');

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE appointments ADD COLUMN pdc_time_slot_id INT NULL COMMENT "Link to pdc_time_slots table"',
    'SELECT "✓ Column pdc_time_slot_id already exists" AS status');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add foreign key constraint
SET @fk_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                  WHERE TABLE_SCHEMA = 'driving_school' 
                  AND TABLE_NAME = 'appointments' 
                  AND COLUMN_NAME = 'pdc_time_slot_id'
                  AND REFERENCED_TABLE_NAME = 'pdc_time_slots');

SET @sql = IF(@fk_exists = 0, 
    'ALTER TABLE appointments ADD CONSTRAINT fk_pdc_time_slot FOREIGN KEY (pdc_time_slot_id) REFERENCES pdc_time_slots(id) ON DELETE SET NULL',
    'SELECT "✓ Foreign key already exists" AS status');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT '✓ STEP 6 COMPLETE: PDC time slot column and foreign key added' AS Status;

-- ============================================================================
-- STEP 7: ADD TDC SESSION FOREIGN KEY TO APPOINTMENTS
-- ============================================================================
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                   WHERE TABLE_SCHEMA = 'driving_school' 
                   AND TABLE_NAME = 'appointments' 
                   AND COLUMN_NAME = 'tdc_session_id');

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE appointments ADD COLUMN tdc_session_id INT NULL COMMENT "Link to tdc_sessions table"',
    'SELECT "✓ Column tdc_session_id already exists" AS status');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add foreign key constraint
SET @fk_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                  WHERE TABLE_SCHEMA = 'driving_school' 
                  AND TABLE_NAME = 'appointments' 
                  AND COLUMN_NAME = 'tdc_session_id'
                  AND REFERENCED_TABLE_NAME = 'tdc_sessions');

SET @sql = IF(@fk_exists = 0, 
    'ALTER TABLE appointments ADD CONSTRAINT fk_tdc_session FOREIGN KEY (tdc_session_id) REFERENCES tdc_sessions(id) ON DELETE SET NULL',
    'SELECT "✓ Foreign key already exists" AS status');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT '✓ STEP 7 COMPLETE: TDC session column and foreign key added' AS Status;

-- ============================================================================
-- STEP 8: DROP OLD TRIGGERS (if they exist)
-- ============================================================================
DROP TRIGGER IF EXISTS update_pdc_slot_after_insert;
DROP TRIGGER IF EXISTS update_pdc_slot_after_delete;
DROP TRIGGER IF EXISTS update_pdc_slot_after_update;
DROP TRIGGER IF EXISTS update_tdc_session_after_insert;
DROP TRIGGER IF EXISTS update_tdc_session_after_delete;
DROP TRIGGER IF EXISTS update_tdc_session_after_update;

SELECT '✓ STEP 8 COMPLETE: Old triggers dropped' AS Status;

-- ============================================================================
-- STEP 9: CREATE PDC TRIGGERS FOR AUTOMATIC BOOKING COUNT
-- ============================================================================

-- Trigger: After INSERT - Increment PDC slot bookings
DELIMITER $$
CREATE TRIGGER update_pdc_slot_after_insert
AFTER INSERT ON appointments
FOR EACH ROW
BEGIN
    IF NEW.course_selection = 'PDC' AND NEW.pdc_time_slot_id IS NOT NULL THEN
        UPDATE pdc_time_slots 
        SET current_bookings = current_bookings + 1
        WHERE id = NEW.pdc_time_slot_id;
    END IF;
END$$
DELIMITER ;

-- Trigger: After DELETE - Decrement PDC slot bookings
DELIMITER $$
CREATE TRIGGER update_pdc_slot_after_delete
AFTER DELETE ON appointments
FOR EACH ROW
BEGIN
    IF OLD.course_selection = 'PDC' AND OLD.pdc_time_slot_id IS NOT NULL THEN
        UPDATE pdc_time_slots 
        SET current_bookings = GREATEST(0, current_bookings - 1)
        WHERE id = OLD.pdc_time_slot_id;
    END IF;
END$$
DELIMITER ;

-- Trigger: After UPDATE - Handle slot changes
DELIMITER $$
CREATE TRIGGER update_pdc_slot_after_update
AFTER UPDATE ON appointments
FOR EACH ROW
BEGIN
    IF NEW.course_selection = 'PDC' THEN
        -- If slot changed, update both old and new slots
        IF OLD.pdc_time_slot_id != NEW.pdc_time_slot_id THEN
            -- Decrement old slot
            IF OLD.pdc_time_slot_id IS NOT NULL THEN
                UPDATE pdc_time_slots 
                SET current_bookings = GREATEST(0, current_bookings - 1)
                WHERE id = OLD.pdc_time_slot_id;
            END IF;
            
            -- Increment new slot
            IF NEW.pdc_time_slot_id IS NOT NULL THEN
                UPDATE pdc_time_slots 
                SET current_bookings = current_bookings + 1
                WHERE id = NEW.pdc_time_slot_id;
            END IF;
        END IF;
    END IF;
END$$
DELIMITER ;

SELECT '✓ STEP 9 COMPLETE: PDC triggers created' AS Status;

-- ============================================================================
-- STEP 10: CREATE TDC TRIGGERS FOR AUTOMATIC ENROLLMENT COUNT
-- ============================================================================

-- Trigger: After INSERT - Increment TDC session enrollment
DELIMITER $$
CREATE TRIGGER update_tdc_session_after_insert
AFTER INSERT ON appointments
FOR EACH ROW
BEGIN
    IF NEW.course_selection = 'TDC' AND NEW.tdc_session_id IS NOT NULL THEN
        UPDATE tdc_sessions 
        SET enrolled_students = enrolled_students + 1
        WHERE id = NEW.tdc_session_id;
    END IF;
END$$
DELIMITER ;

-- Trigger: After DELETE - Decrement TDC session enrollment
DELIMITER $$
CREATE TRIGGER update_tdc_session_after_delete
AFTER DELETE ON appointments
FOR EACH ROW
BEGIN
    IF OLD.course_selection = 'TDC' AND OLD.tdc_session_id IS NOT NULL THEN
        UPDATE tdc_sessions 
        SET enrolled_students = GREATEST(0, enrolled_students - 1)
        WHERE id = OLD.tdc_session_id;
    END IF;
END$$
DELIMITER ;

-- Trigger: After UPDATE - Handle session changes
DELIMITER $$
CREATE TRIGGER update_tdc_session_after_update
AFTER UPDATE ON appointments
FOR EACH ROW
BEGIN
    IF NEW.course_selection = 'TDC' THEN
        -- If session changed, update both old and new sessions
        IF OLD.tdc_session_id != NEW.tdc_session_id THEN
            -- Decrement old session
            IF OLD.tdc_session_id IS NOT NULL THEN
                UPDATE tdc_sessions 
                SET enrolled_students = GREATEST(0, enrolled_students - 1)
                WHERE id = OLD.tdc_session_id;
            END IF;
            
            -- Increment new session
            IF NEW.tdc_session_id IS NOT NULL THEN
                UPDATE tdc_sessions 
                SET enrolled_students = enrolled_students + 1
                WHERE id = NEW.tdc_session_id;
            END IF;
        END IF;
    END IF;
END$$
DELIMITER ;

SELECT '✓ STEP 10 COMPLETE: TDC triggers created' AS Status;

-- ============================================================================
-- STEP 11: INSERT SAMPLE PDC TIME SLOTS (October-November 2025)
-- ============================================================================
INSERT IGNORE INTO pdc_time_slots (slot_date, slot_time_start, slot_time_end, slot_label, max_bookings, is_available) VALUES
-- October 2025 (Weekdays only)
('2025-10-20', '08:00:00', '12:00:00', '8:00 AM - 12:00 PM', 3, 1),
('2025-10-20', '14:00:00', '18:00:00', '2:00 PM - 6:00 PM', 3, 1),
('2025-10-21', '08:00:00', '12:00:00', '8:00 AM - 12:00 PM', 3, 1),
('2025-10-21', '14:00:00', '18:00:00', '2:00 PM - 6:00 PM', 3, 1),
('2025-10-22', '08:00:00', '12:00:00', '8:00 AM - 12:00 PM', 3, 1),
('2025-10-22', '14:00:00', '18:00:00', '2:00 PM - 6:00 PM', 3, 1),
('2025-10-23', '08:00:00', '12:00:00', '8:00 AM - 12:00 PM', 3, 1),
('2025-10-23', '14:00:00', '18:00:00', '2:00 PM - 6:00 PM', 3, 1),
('2025-10-24', '08:00:00', '12:00:00', '8:00 AM - 12:00 PM', 3, 1),
('2025-10-24', '14:00:00', '18:00:00', '2:00 PM - 6:00 PM', 3, 1),
('2025-10-27', '08:00:00', '12:00:00', '8:00 AM - 12:00 PM', 3, 1),
('2025-10-27', '14:00:00', '18:00:00', '2:00 PM - 6:00 PM', 3, 1),
('2025-10-28', '08:00:00', '12:00:00', '8:00 AM - 12:00 PM', 3, 1),
('2025-10-28', '14:00:00', '18:00:00', '2:00 PM - 6:00 PM', 3, 1),
('2025-10-29', '08:00:00', '12:00:00', '8:00 AM - 12:00 PM', 3, 1),
('2025-10-29', '14:00:00', '18:00:00', '2:00 PM - 6:00 PM', 3, 1),
('2025-10-30', '08:00:00', '12:00:00', '8:00 AM - 12:00 PM', 3, 1),
('2025-10-30', '14:00:00', '18:00:00', '2:00 PM - 6:00 PM', 3, 1),
('2025-10-31', '08:00:00', '12:00:00', '8:00 AM - 12:00 PM', 3, 1),
('2025-10-31', '14:00:00', '18:00:00', '2:00 PM - 6:00 PM', 3, 1),
-- November 2025 (Weekdays only)
('2025-11-03', '08:00:00', '12:00:00', '8:00 AM - 12:00 PM', 3, 1),
('2025-11-03', '14:00:00', '18:00:00', '2:00 PM - 6:00 PM', 3, 1),
('2025-11-04', '08:00:00', '12:00:00', '8:00 AM - 12:00 PM', 3, 1),
('2025-11-04', '14:00:00', '18:00:00', '2:00 PM - 6:00 PM', 3, 1),
('2025-11-05', '08:00:00', '12:00:00', '8:00 AM - 12:00 PM', 3, 1),
('2025-11-05', '14:00:00', '18:00:00', '2:00 PM - 6:00 PM', 3, 1),
('2025-11-06', '08:00:00', '12:00:00', '8:00 AM - 12:00 PM', 3, 1),
('2025-11-06', '14:00:00', '18:00:00', '2:00 PM - 6:00 PM', 3, 1),
('2025-11-07', '08:00:00', '12:00:00', '8:00 AM - 12:00 PM', 3, 1),
('2025-11-07', '14:00:00', '18:00:00', '2:00 PM - 6:00 PM', 3, 1),
('2025-11-10', '08:00:00', '12:00:00', '8:00 AM - 12:00 PM', 3, 1),
('2025-11-10', '14:00:00', '18:00:00', '2:00 PM - 6:00 PM', 3, 1),
('2025-11-11', '08:00:00', '12:00:00', '8:00 AM - 12:00 PM', 3, 1),
('2025-11-11', '14:00:00', '18:00:00', '2:00 PM - 6:00 PM', 3, 1),
('2025-11-12', '08:00:00', '12:00:00', '8:00 AM - 12:00 PM', 3, 1),
('2025-11-12', '14:00:00', '18:00:00', '2:00 PM - 6:00 PM', 3, 1),
('2025-11-13', '08:00:00', '12:00:00', '8:00 AM - 12:00 PM', 3, 1),
('2025-11-13', '14:00:00', '18:00:00', '2:00 PM - 6:00 PM', 3, 1),
('2025-11-14', '08:00:00', '12:00:00', '8:00 AM - 12:00 PM', 3, 1),
('2025-11-14', '14:00:00', '18:00:00', '2:00 PM - 6:00 PM', 3, 1);

SELECT '✓ STEP 11 COMPLETE: 40 PDC time slots inserted' AS Status;

-- ============================================================================
-- STEP 12: INSERT SAMPLE TDC SESSIONS (Fridays and Saturdays only)
-- ============================================================================
INSERT IGNORE INTO tdc_sessions (session_date, session_time, session_label, max_students, is_available) VALUES
-- October 2025 (Fridays)
('2025-10-17', '09:00:00', 'Friday Morning Session - 9:00 AM', 30, 1),
('2025-10-17', '14:00:00', 'Friday Afternoon Session - 2:00 PM', 30, 1),
('2025-10-24', '09:00:00', 'Friday Morning Session - 9:00 AM', 30, 1),
('2025-10-24', '14:00:00', 'Friday Afternoon Session - 2:00 PM', 30, 1),
('2025-10-31', '09:00:00', 'Friday Morning Session - 9:00 AM', 30, 1),
('2025-10-31', '14:00:00', 'Friday Afternoon Session - 2:00 PM', 30, 1),
-- October 2025 (Saturdays)
('2025-10-18', '09:00:00', 'Saturday Morning Session - 9:00 AM', 30, 1),
('2025-10-18', '14:00:00', 'Saturday Afternoon Session - 2:00 PM', 30, 1),
('2025-10-25', '09:00:00', 'Saturday Morning Session - 9:00 AM', 30, 1),
('2025-10-25', '14:00:00', 'Saturday Afternoon Session - 2:00 PM', 30, 1),
-- November 2025 (Fridays)
('2025-11-07', '09:00:00', 'Friday Morning Session - 9:00 AM', 30, 1),
('2025-11-07', '14:00:00', 'Friday Afternoon Session - 2:00 PM', 30, 1),
('2025-11-14', '09:00:00', 'Friday Morning Session - 9:00 AM', 30, 1),
('2025-11-14', '14:00:00', 'Friday Afternoon Session - 2:00 PM', 30, 1),
('2025-11-21', '09:00:00', 'Friday Morning Session - 9:00 AM', 30, 1),
('2025-11-21', '14:00:00', 'Friday Afternoon Session - 2:00 PM', 30, 1),
('2025-11-28', '09:00:00', 'Friday Morning Session - 9:00 AM', 30, 1),
('2025-11-28', '14:00:00', 'Friday Afternoon Session - 2:00 PM', 30, 1),
-- November 2025 (Saturdays)
('2025-11-01', '09:00:00', 'Saturday Morning Session - 9:00 AM', 30, 1),
('2025-11-01', '14:00:00', 'Saturday Afternoon Session - 2:00 PM', 30, 1),
('2025-11-08', '09:00:00', 'Saturday Morning Session - 9:00 AM', 30, 1),
('2025-11-08', '14:00:00', 'Saturday Afternoon Session - 2:00 PM', 30, 1),
('2025-11-15', '09:00:00', 'Saturday Morning Session - 9:00 AM', 30, 1),
('2025-11-15', '14:00:00', 'Saturday Afternoon Session - 2:00 PM', 30, 1),
('2025-11-22', '09:00:00', 'Saturday Morning Session - 9:00 AM', 30, 1),
('2025-11-22', '14:00:00', 'Saturday Afternoon Session - 2:00 PM', 30, 1),
('2025-11-29', '09:00:00', 'Saturday Morning Session - 9:00 AM', 30, 1),
('2025-11-29', '14:00:00', 'Saturday Afternoon Session - 2:00 PM', 30, 1);

SELECT '✓ STEP 12 COMPLETE: 28 TDC sessions inserted' AS Status;

-- ============================================================================
-- STEP 13: VERIFICATION QUERIES
-- ============================================================================
SELECT '========================================' AS '';
SELECT '       DATABASE UPDATE COMPLETE!       ' AS '';
SELECT '========================================' AS '';

SELECT 'Appointments Table Columns:' AS '';
SHOW COLUMNS FROM appointments;

SELECT '' AS '';
SELECT 'PDC Time Slots Table:' AS '';
SELECT COUNT(*) as total_pdc_slots, 
       SUM(current_bookings) as total_bookings,
       SUM(max_bookings - current_bookings) as available_slots
FROM pdc_time_slots;

SELECT '' AS '';
SELECT 'TDC Sessions Table:' AS '';
SELECT COUNT(*) as total_tdc_sessions,
       SUM(enrolled_students) as total_enrolled,
       SUM(max_students - enrolled_students) as available_slots
FROM tdc_sessions;

SELECT '' AS '';
SELECT 'Database Triggers:' AS '';
SHOW TRIGGERS WHERE `Table` = 'appointments';

SELECT '' AS '';
SELECT '✓✓✓ ALL UPDATES COMPLETED SUCCESSFULLY! ✓✓✓' AS Status;
SELECT 'Your database now has:' AS '';
SELECT '- Email reminder system (reminder_sent, reminder_sent_at)' AS Features;
SELECT '- PDC time slot management with 40 slots' AS Features;
SELECT '- TDC session scheduling with 28 sessions' AS Features;
SELECT '- GCash payment proof upload (payment_proof column)' AS Features;
SELECT '- Automatic booking count triggers' AS Features;
SELECT '' AS '';
SELECT 'Next Steps:' AS '';
SELECT '1. Create folder: uploads/payment_proofs/' AS Step;
SELECT '2. Setup Windows Task Scheduler for send_appointment_reminder.php' AS Step;
SELECT '3. Test booking appointments with GCash payment upload' AS Step;
