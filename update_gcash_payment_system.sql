-- ============================================================================
-- GCASH PAYMENT PROOF UPDATE
-- ============================================================================
-- This script updates the payment system to use only GCash with screenshot upload
-- instead of multiple payment methods with reference numbers
-- ============================================================================

USE driving_school;

-- Add payment_proof column to store screenshot filename
ALTER TABLE appointments 
ADD COLUMN IF NOT EXISTS payment_proof VARCHAR(255) NULL COMMENT 'Filename of uploaded payment proof screenshot'
AFTER payment_method;

-- Update existing records: migrate payment_reference to payment_proof if needed
-- (Optional - only if you want to preserve old reference numbers)
-- UPDATE appointments 
-- SET payment_proof = payment_reference 
-- WHERE payment_reference IS NOT NULL AND payment_proof IS NULL;

-- Set payment_method to 'gcash' for all existing records
UPDATE appointments 
SET payment_method = 'gcash' 
WHERE payment_method IS NULL OR payment_method IN ('online', 'bank_transfer', 'card', 'cash');

-- Create uploads directory structure (run this via PHP or manually create folders)
-- Directory: ../uploads/payment_proofs/

-- Verification queries
SELECT 'Checking payment_proof column...' as status;
SHOW COLUMNS FROM appointments LIKE 'payment_proof';

SELECT 'Sample appointments with payment info...' as status;
SELECT 
    id,
    student_id,
    course_selection,
    payment_amount,
    payment_method,
    payment_proof,
    payment_status,
    created_at
FROM appointments 
ORDER BY created_at DESC 
LIMIT 5;

SELECT '✓ Migration completed successfully!' as status;
SELECT 'Note: Make sure to create the uploads/payment_proofs/ directory' as reminder;
