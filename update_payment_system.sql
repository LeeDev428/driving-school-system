-- ============================================================================
-- PAYMENT SYSTEM UPDATE - GCASH ONLY WITH SCREENSHOT UPLOAD
-- ============================================================================
-- This script adds the payment_proof column for storing screenshot filenames
-- ============================================================================

USE driving_school;

-- Add payment_proof column to appointments table
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                   WHERE TABLE_SCHEMA = 'driving_school' 
                   AND TABLE_NAME = 'appointments' 
                   AND COLUMN_NAME = 'payment_proof');

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE appointments ADD COLUMN payment_proof VARCHAR(255) NULL COMMENT "Filename of uploaded payment screenshot"',
    'SELECT "Column payment_proof already exists" AS status');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verification
SELECT 'Checking payment_proof column...' as status;
SHOW COLUMNS FROM appointments LIKE 'payment_proof';

SELECT '✓ Payment system update completed!' as status;
