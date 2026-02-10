-- Expand user profile fields
-- Add personal information, religious milestones, and contact details

USE sunday_school_db;

-- Add new columns to users table
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS dob DATE NULL COMMENT 'Date of birth',
ADD COLUMN IF NOT EXISTS phone VARCHAR(20) NULL COMMENT 'Phone number',
ADD COLUMN IF NOT EXISTS baptism_date DATE NULL COMMENT 'Baptism date',
ADD COLUMN IF NOT EXISTS holy_communion_date DATE NULL COMMENT 'Holy Communion date',
ADD COLUMN IF NOT EXISTS address TEXT NULL COMMENT 'Full address';

-- Note: class_id already exists for students
-- Teachers' teaching assignments are managed through the existing system
