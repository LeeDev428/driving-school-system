-- ============================================================================
-- PDC TIME SLOTS SYSTEM DATABASE MIGRATION
-- ============================================================================
-- This script creates the necessary tables for managing PDC appointment time slots
-- and adds required columns to track email reminders
-- ============================================================================

USE driving_school;

-- Add reminder tracking columns to appointments table (only if they don't exist)
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

-- Create PDC time slots table
CREATE TABLE IF NOT EXISTS pdc_time_slots (
    id INT PRIMARY KEY AUTO_INCREMENT,
    slot_date DATE NOT NULL COMMENT 'The date for this time slot',
    slot_time_start TIME NOT NULL COMMENT 'Start time (e.g., 08:00:00)',
    slot_time_end TIME NOT NULL COMMENT 'End time (e.g., 12:00:00)',
    slot_label VARCHAR(50) NOT NULL COMMENT 'Display label (e.g., "8:00 AM - 12:00 PM")',
    instructor_id INT NULL COMMENT 'Assigned instructor for this slot',
    max_bookings INT DEFAULT 1 COMMENT 'Maximum bookings allowed for this slot',
    current_bookings INT DEFAULT 0 COMMENT 'Current number of bookings',
    is_available TINYINT(1) DEFAULT 1 COMMENT 'Whether slot is available for booking',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (instructor_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_slot_date (slot_date),
    INDEX idx_instructor (instructor_id),
    INDEX idx_available (is_available),
    UNIQUE KEY unique_slot (slot_date, slot_time_start, instructor_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Available time slots for PDC appointments';

-- Add pdc_time_slot_id column to appointments table (MUST BE BEFORE TRIGGERS!)
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                   WHERE TABLE_SCHEMA = 'driving_school' 
                   AND TABLE_NAME = 'appointments' 
                   AND COLUMN_NAME = 'pdc_time_slot_id');

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE appointments ADD COLUMN pdc_time_slot_id INT NULL COMMENT "Link to pdc_time_slots table for PDC appointments"',
    'SELECT "Column pdc_time_slot_id already exists" AS status');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add foreign key constraint (only if column was just added)
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

-- Drop existing triggers if they exist (to recreate them)
DROP TRIGGER IF EXISTS update_pdc_slot_after_insert;
DROP TRIGGER IF EXISTS update_pdc_slot_after_delete;
DROP TRIGGER IF EXISTS update_pdc_slot_after_update;

-- Create trigger to update booking count when appointment is created
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

-- Create trigger to update booking count when appointment is deleted
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

-- Create trigger to update booking count when appointment is updated
DELIMITER $$
CREATE TRIGGER update_pdc_slot_after_update
AFTER UPDATE ON appointments
FOR EACH ROW
BEGIN
    -- If PDC slot changed, update both old and new slots
    IF NEW.course_selection = 'PDC' THEN
        IF OLD.pdc_time_slot_id != NEW.pdc_time_slot_id THEN
            -- Decrease old slot count
            IF OLD.pdc_time_slot_id IS NOT NULL THEN
                UPDATE pdc_time_slots 
                SET current_bookings = GREATEST(0, current_bookings - 1)
                WHERE id = OLD.pdc_time_slot_id;
            END IF;
            
            -- Increase new slot count
            IF NEW.pdc_time_slot_id IS NOT NULL THEN
                UPDATE pdc_time_slots 
                SET current_bookings = current_bookings + 1
                WHERE id = NEW.pdc_time_slot_id;
            END IF;
        END IF;
    END IF;
END$$
DELIMITER ;

-- Insert sample PDC time slots for October-November 2025
-- Common time slots: Morning (8am-12pm), Afternoon (2pm-6pm), Evening (6pm-9pm)
INSERT INTO pdc_time_slots (slot_date, slot_time_start, slot_time_end, slot_label, max_bookings, is_available) VALUES
-- Week 1 October 2025
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

-- Week 2
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

-- Week 3 November
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

-- Week 4
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

-- Verification queries
SELECT 'Checking appointments table columns...' as status;
SHOW COLUMNS FROM appointments LIKE 'reminder%';

SELECT 'Checking pdc_time_slots table...' as status;
DESCRIBE pdc_time_slots;

SELECT 'Sample PDC time slots created...' as status;
SELECT slot_date, slot_label, max_bookings, current_bookings, is_available 
FROM pdc_time_slots 
ORDER BY slot_date, slot_time_start 
LIMIT 10;

SELECT '✓ Migration completed successfully!' as status;
