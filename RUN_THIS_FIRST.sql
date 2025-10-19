-- ============================================================================
-- PART 1: CREATE TABLES AND ADD COLUMNS
-- ============================================================================
-- Run this file FIRST in HeidiSQL
-- After successful execution, run RUN_THIS_SECOND.sql
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

SELECT '✓ Email reminder columns added' AS Status;

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

SELECT '✓ GCash payment proof column added' AS Status;

-- ============================================================================
-- STEP 3: ADD COURSE SELECTION COLUMN
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

SELECT '✓ Course selection column added' AS Status;

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

SELECT '✓ PDC time slots table created' AS Status;

-- ============================================================================
-- STEP 5: CREATE TDC SESSIONS TABLE
-- ============================================================================
CREATE TABLE IF NOT EXISTS `tdc_sessions` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `session_date` DATE NOT NULL COMMENT 'Friday or Saturday only',
    `session_label` VARCHAR(100) NOT NULL COMMENT 'Display label with date and time',
    `max_students` INT DEFAULT 30 COMMENT 'Maximum students per session',
    `enrolled_students` INT DEFAULT 0 COMMENT 'Current enrolled students',
    `instructor_id` INT NULL COMMENT 'Assigned instructor',
    `is_available` TINYINT(1) DEFAULT 1 COMMENT 'Available for booking',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_session_date` (`session_date`),
    INDEX `idx_instructor` (`instructor_id`),
    INDEX `idx_available` (`is_available`),
    UNIQUE KEY `unique_session` (`session_date`, `session_label`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

SELECT '✓ TDC sessions table created' AS Status;

-- ============================================================================
-- STEP 6: ADD PDC TIME SLOT COLUMN AND FOREIGN KEY
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

-- Add foreign key
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

SELECT '✓ PDC time slot column and FK added' AS Status;

-- ============================================================================
-- STEP 7: ADD TDC SESSION COLUMN AND FOREIGN KEY
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

-- Add foreign key
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

SELECT '✓ TDC session column and FK added' AS Status;

-- ============================================================================
SELECT '========================================' AS '';
SELECT '   PART 1 COMPLETE! ✓✓✓' AS '';
SELECT '========================================' AS '';
SELECT 'Tables created:' AS '';
SELECT '- pdc_time_slots' AS Tables;
SELECT '- tdc_sessions' AS Tables;
SELECT '' AS '';
SELECT 'Columns added to appointments:' AS '';
SELECT '- reminder_sent' AS Columns;
SELECT '- reminder_sent_at' AS Columns;
SELECT '- payment_proof' AS Columns;
SELECT '- course_selection' AS Columns;
SELECT '- pdc_time_slot_id' AS Columns;
SELECT '- tdc_session_id' AS Columns;
SELECT '' AS '';
SELECT '⚡ NOW RUN: RUN_THIS_SECOND.sql' AS NextStep;
