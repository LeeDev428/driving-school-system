-- Add vehicle_type column to simulation_results table

ALTER TABLE `simulation_results` 
ADD COLUMN `vehicle_type` VARCHAR(20) NOT NULL DEFAULT 'car' AFTER `simulation_type`;

-- Update existing records to have 'car' as default
UPDATE `simulation_results` SET `vehicle_type` = 'car' WHERE `vehicle_type` IS NULL;

