-- Migration script to add payment_reference field to appointments table
-- Run this script if your database doesn't already have the payment_reference field

-- Add payment_reference field to appointments table
ALTER TABLE `appointments` 
ADD COLUMN `payment_reference` varchar(100) DEFAULT NULL AFTER `payment_method`;

-- Optionally update existing records with example reference numbers for testing
-- UPDATE `appointments` SET `payment_reference` = CONCAT('REF-', id, '-', YEAR(created_at)) WHERE `payment_method` IN ('card', 'bank_transfer', 'online') AND `payment_reference` IS NULL;