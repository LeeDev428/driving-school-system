-- ============================================================================
-- STEP-BY-STEP DATABASE SETUP (Run in Order!)
-- ============================================================================
-- Use this if the main script gives errors
-- Run each section ONE AT A TIME in HeidiSQL
-- ============================================================================

USE driving_school;

-- ============================================================================
-- STEP 1: Add reminder columns
-- ============================================================================
-- Check and add reminder_sent column
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                   WHERE TABLE_SCHEMA = 'driving_school' 
                   AND TABLE_NAME = 'appointments' 
                   AND COLUMN_NAME = 'reminder_sent');

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE appointments ADD COLUMN reminder_sent TINYINT(1) DEFAULT 0 COMMENT "Whether reminder email has been sent"',
    'SELECT "Column reminder_sent already exists" AS status');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add reminder_sent_at column
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                   WHERE TABLE_SCHEMA = 'driving_school' 
                   AND TABLE_NAME = 'appointments' 
                   AND COLUMN_NAME = 'reminder_sent_at');

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE appointments ADD COLUMN reminder_sent_at DATETIME NULL COMMENT "When reminder email was sent"',
    'SELECT "Column reminder_sent_at already exists" AS status');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT '✓ Step 1 Complete: Reminder columns added' AS Status;

-- ============================================================================
-- STEP 2: Create pdc_time_slots table
-- ============================================================================
CREATE TABLE IF NOT EXISTS pdc_time_slots (
    id INT PRIMARY KEY AUTO_INCREMENT,
    slot_date DATE NOT NULL COMMENT 'The date for this time slot',
    slot_time_start TIME NOT NULL COMMENT 'Start time',
    slot_time_end TIME NOT NULL COMMENT 'End time',
    slot_label VARCHAR(50) NOT NULL COMMENT 'Display label',
    instructor_id INT NULL COMMENT 'Assigned instructor',
    max_bookings INT DEFAULT 1 COMMENT 'Max bookings allowed',
    current_bookings INT DEFAULT 0 COMMENT 'Current bookings',
    is_available TINYINT(1) DEFAULT 1 COMMENT 'Available for booking',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (instructor_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_slot_date (slot_date),
    INDEX idx_instructor (instructor_id),
    INDEX idx_available (is_available),
    UNIQUE KEY unique_slot (slot_date, slot_time_start, instructor_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SELECT '✓ Step 2 Complete: Time slots table created' AS Status;

-- ============================================================================
-- STEP 3: Add pdc_time_slot_id column to appointments
-- ============================================================================
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                   WHERE TABLE_SCHEMA = 'driving_school' 
                   AND TABLE_NAME = 'appointments' 
                   AND COLUMN_NAME = 'pdc_time_slot_id');

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE appointments ADD COLUMN pdc_time_slot_id INT NULL COMMENT "Link to pdc_time_slots"',
    'SELECT "Column pdc_time_slot_id already exists" AS status');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT '✓ Step 3 Complete: Column added to appointments' AS Status;

-- ============================================================================
-- STEP 4: Add foreign key
-- ============================================================================
SET @fk_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                  WHERE TABLE_SCHEMA = 'driving_school' 
                  AND TABLE_NAME = 'appointments' 
                  AND COLUMN_NAME = 'pdc_time_slot_id'
                  AND REFERENCED_TABLE_NAME = 'pdc_time_slots');

SET @sql = IF(@fk_exists = 0, 
    'ALTER TABLE appointments ADD FOREIGN KEY (pdc_time_slot_id) REFERENCES pdc_time_slots(id) ON DELETE SET NULL',
    'SELECT "Foreign key already exists" AS status');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT '✓ Step 4 Complete: Foreign key added' AS Status;

-- ============================================================================
-- STEP 5: Drop old triggers (if they exist)
-- ============================================================================
DROP TRIGGER IF EXISTS update_pdc_slot_after_insert;
DROP TRIGGER IF EXISTS update_pdc_slot_after_delete;
DROP TRIGGER IF EXISTS update_pdc_slot_after_update;

SELECT '✓ Step 5 Complete: Old triggers dropped' AS Status;

-- ============================================================================
-- STEP 6: Create INSERT trigger
-- ============================================================================
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

SELECT '✓ Step 6 Complete: INSERT trigger created' AS Status;

-- ============================================================================
-- STEP 7: Create DELETE trigger
-- ============================================================================
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

SELECT '✓ Step 7 Complete: DELETE trigger created' AS Status;

-- ============================================================================
-- STEP 8: Create UPDATE trigger
-- ============================================================================
DELIMITER $$
CREATE TRIGGER update_pdc_slot_after_update
AFTER UPDATE ON appointments
FOR EACH ROW
BEGIN
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
END$$
DELIMITER ;

SELECT '✓ Step 8 Complete: UPDATE trigger created' AS Status;

-- ============================================================================
-- STEP 9: Insert sample time slots
-- ============================================================================
INSERT INTO pdc_time_slots (slot_date, slot_time_start, slot_time_end, slot_label, max_bookings, is_available) VALUES
-- October 2025
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
-- November 2025
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

SELECT '✓ Step 9 Complete: 40 time slots inserted' AS Status;

-- ============================================================================
-- STEP 10: Verification
-- ============================================================================
SELECT '=== VERIFICATION ===' AS '';
SELECT 'Reminder columns:' AS '';
SHOW COLUMNS FROM appointments LIKE 'reminder%';

SELECT 'PDC Time Slot column:' AS '';
SHOW COLUMNS FROM appointments LIKE 'pdc_time_slot_id';

SELECT 'Time slots count:' AS '';
SELECT COUNT(*) as total_slots FROM pdc_time_slots;

SELECT 'Triggers:' AS '';
SHOW TRIGGERS LIKE 'appointments';

SELECT '✓✓✓ ALL STEPS COMPLETE! ✓✓✓' AS Status;
