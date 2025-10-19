-- ============================================================================
-- PART 2: CREATE TRIGGERS AND INSERT DATA
-- ============================================================================
-- Run this file SECOND (after RUN_THIS_FIRST.sql completes successfully)
-- ============================================================================

USE driving_school;

-- ============================================================================
-- STEP 1: DROP OLD TRIGGERS (if they exist)
-- ============================================================================
DROP TRIGGER IF EXISTS update_pdc_slot_after_insert;
DROP TRIGGER IF EXISTS update_pdc_slot_after_delete;
DROP TRIGGER IF EXISTS update_pdc_slot_after_update;
DROP TRIGGER IF EXISTS update_tdc_session_after_insert;
DROP TRIGGER IF EXISTS update_tdc_session_after_delete;
DROP TRIGGER IF EXISTS update_tdc_session_after_update;

SELECT '✓ Old triggers dropped' AS Status;

-- ============================================================================
-- STEP 2: CREATE PDC TRIGGERS
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

SELECT '✓ PDC triggers created' AS Status;

-- ============================================================================
-- STEP 3: CREATE TDC TRIGGERS
-- ============================================================================

DELIMITER $$
CREATE TRIGGER update_tdc_session_after_insert
AFTER INSERT ON appointments
FOR EACH ROW
BEGIN
    IF NEW.course_selection = 'TDC' AND NEW.tdc_session_id IS NOT NULL THEN
        UPDATE tdc_sessions 
        SET current_enrollments = current_enrollments + 1
        WHERE id = NEW.tdc_session_id;
    END IF;
END$$
DELIMITER ;

DELIMITER $$
CREATE TRIGGER update_tdc_session_after_delete
AFTER DELETE ON appointments
FOR EACH ROW
BEGIN
    IF OLD.course_selection = 'TDC' AND OLD.tdc_session_id IS NOT NULL THEN
        UPDATE tdc_sessions 
        SET current_enrollments = GREATEST(0, current_enrollments - 1)
        WHERE id = OLD.tdc_session_id;
    END IF;
END$$
DELIMITER ;

DELIMITER $$
CREATE TRIGGER update_tdc_session_after_update
AFTER UPDATE ON appointments
FOR EACH ROW
BEGIN
    IF NEW.course_selection = 'TDC' THEN
        IF OLD.tdc_session_id != NEW.tdc_session_id THEN
            IF OLD.tdc_session_id IS NOT NULL THEN
                UPDATE tdc_sessions 
                SET current_enrollments = GREATEST(0, current_enrollments - 1)
                WHERE id = OLD.tdc_session_id;
            END IF;
            
            IF NEW.tdc_session_id IS NOT NULL THEN
                UPDATE tdc_sessions 
                SET current_enrollments = current_enrollments + 1
                WHERE id = NEW.tdc_session_id;
            END IF;
        END IF;
    END IF;
END$$
DELIMITER ;

SELECT '✓ TDC triggers created' AS Status;

-- ============================================================================
-- STEP 4: INSERT PDC TIME SLOTS (40 slots)
-- ============================================================================
INSERT IGNORE INTO pdc_time_slots (slot_date, slot_time_start, slot_time_end, slot_label, max_bookings, is_available) VALUES
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

SELECT '✓ 40 PDC time slots inserted' AS Status;

-- ============================================================================
-- STEP 5: INSERT TDC SESSIONS (28 sessions - Fridays & Saturdays)
-- ============================================================================
INSERT IGNORE INTO tdc_sessions (session_date, session_day, start_time, end_time, max_enrollments, status) VALUES
-- October 2025 (Fridays)
('2025-10-17', 'Friday', '09:00:00', '12:00:00', 30, 'active'),
('2025-10-17', 'Friday', '14:00:00', '17:00:00', 30, 'active'),
('2025-10-24', 'Friday', '09:00:00', '12:00:00', 30, 'active'),
('2025-10-24', 'Friday', '14:00:00', '17:00:00', 30, 'active'),
('2025-10-31', 'Friday', '09:00:00', '12:00:00', 30, 'active'),
('2025-10-31', 'Friday', '14:00:00', '17:00:00', 30, 'active'),
-- October 2025 (Saturdays)
('2025-10-18', 'Saturday', '09:00:00', '12:00:00', 30, 'active'),
('2025-10-18', 'Saturday', '14:00:00', '17:00:00', 30, 'active'),
('2025-10-25', 'Saturday', '09:00:00', '12:00:00', 30, 'active'),
('2025-10-25', 'Saturday', '14:00:00', '17:00:00', 30, 'active'),
-- November 2025 (Fridays)
('2025-11-07', 'Friday', '09:00:00', '12:00:00', 30, 'active'),
('2025-11-07', 'Friday', '14:00:00', '17:00:00', 30, 'active'),
('2025-11-14', 'Friday', '09:00:00', '12:00:00', 30, 'active'),
('2025-11-14', 'Friday', '14:00:00', '17:00:00', 30, 'active'),
('2025-11-21', 'Friday', '09:00:00', '12:00:00', 30, 'active'),
('2025-11-21', 'Friday', '14:00:00', '17:00:00', 30, 'active'),
('2025-11-28', 'Friday', '09:00:00', '12:00:00', 30, 'active'),
('2025-11-28', 'Friday', '14:00:00', '17:00:00', 30, 'active'),
-- November 2025 (Saturdays)
('2025-11-01', 'Saturday', '09:00:00', '12:00:00', 30, 'active'),
('2025-11-01', 'Saturday', '14:00:00', '17:00:00', 30, 'active'),
('2025-11-08', 'Saturday', '09:00:00', '12:00:00', 30, 'active'),
('2025-11-08', 'Saturday', '14:00:00', '17:00:00', 30, 'active'),
('2025-11-15', 'Saturday', '09:00:00', '12:00:00', 30, 'active'),
('2025-11-15', 'Saturday', '14:00:00', '17:00:00', 30, 'active'),
('2025-11-22', 'Saturday', '09:00:00', '12:00:00', 30, 'active'),
('2025-11-22', 'Saturday', '14:00:00', '17:00:00', 30, 'active'),
('2025-11-29', 'Saturday', '09:00:00', '12:00:00', 30, 'active'),
('2025-11-29', 'Saturday', '14:00:00', '17:00:00', 30, 'active');

SELECT '✓ 28 TDC sessions inserted' AS Status;

-- ============================================================================
-- FINAL VERIFICATION
-- ============================================================================
SELECT '========================================' AS '';
SELECT '   ALL UPDATES COMPLETE! ✓✓✓' AS '';
SELECT '========================================' AS '';

SELECT 'PDC Time Slots:' AS '';
SELECT COUNT(*) as total_slots FROM pdc_time_slots;

SELECT 'TDC Sessions:' AS '';
SELECT COUNT(*) as total_sessions FROM tdc_sessions;

SELECT 'Database Triggers:' AS '';
SHOW TRIGGERS WHERE `Table` = 'appointments';

SELECT '' AS '';
SELECT '✓✓✓ DATABASE IS READY! ✓✓✓' AS Status;
SELECT '' AS '';
SELECT 'Next Steps:' AS '';
SELECT '1. Create folder: uploads/payment_proofs/' AS Step;
SELECT '2. Setup Windows Task Scheduler' AS Step;
SELECT '3. Test booking with GCash payment' AS Step;
