-- =========================================
-- FIX DUPLICATE TDC TRIGGERS
-- Issue: Two sets of triggers incrementing the same counter
-- Solution: Drop duplicate triggers and recalculate counts
-- =========================================

USE driving_school;

SHOW TRIGGERS WHERE `Table` = 'appointments';

-- Drop the duplicate TDC triggers (keeping update_tdc_enrollment_* versions)
DROP TRIGGER IF EXISTS `update_tdc_session_after_insert`;
DROP TRIGGER IF EXISTS `update_tdc_session_after_update`;
DROP TRIGGER IF EXISTS `update_tdc_session_after_delete`;

-- Recalculate TDC enrollment counts to fix any incorrect values
UPDATE tdc_sessions ts
SET current_enrollments = (
    SELECT COUNT(*) 
    FROM appointments a 
    WHERE a.tdc_session_id = ts.id 
    AND a.course_selection = 'TDC'
    AND a.status NOT IN ('cancelled', 'no_show')
);

-- Update TDC session status based on correct enrollment counts
UPDATE tdc_sessions
SET status = CASE
    WHEN current_enrollments >= max_enrollments THEN 'full'
    WHEN current_enrollments > 0 THEN 'active'
    ELSE 'active'
END;

-- Recalculate PDC booking counts to be safe
UPDATE pdc_time_slots pts
SET current_bookings = (
    SELECT COUNT(*) 
    FROM appointments a 
    WHERE a.pdc_time_slot_id = pts.id 
    AND a.course_selection = 'PDC'
    AND a.status NOT IN ('cancelled', 'no_show')
);

-- Show final trigger list
SELECT 
    TRIGGER_NAME,
    EVENT_MANIPULATION,
    ACTION_TIMING
FROM information_schema.TRIGGERS 
WHERE TRIGGER_SCHEMA = 'driving_school'
AND EVENT_OBJECT_TABLE = 'appointments'
ORDER BY TRIGGER_NAME;

-- Show current TDC session counts
SELECT 
    id,
    session_date,
    session_day,
    current_enrollments,
    max_enrollments,
    status,
    CONCAT(current_enrollments, '/', max_enrollments) as 'Bookings'
FROM tdc_sessions
ORDER BY session_date;

-- Show current PDC slot counts
SELECT 
    slot_date,
    COUNT(*) as total_slots,
    SUM(current_bookings) as total_bookings
FROM pdc_time_slots
GROUP BY slot_date
ORDER BY slot_date
LIMIT 10;
