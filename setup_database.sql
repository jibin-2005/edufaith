-- Run this SQL to update your database table
-- Database: sunday_school_db

-- Use the database
USE sunday_school_db;

-- Alter users table to add reset token columns if they don't exist
-- We are assuming a 'users' table exists. If your table is named differently (e.g., 'admins', 'members'), please rename it.

ALTER TABLE users 
ADD COLUMN IF NOT EXISTS reset_token_hash VARCHAR(64) NULL,
ADD COLUMN IF NOT EXISTS reset_token_expires_at DATETIME NULL;

-- If you don't have a users table yet, here is a basic endpoint:
/*
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) DEFAULT 'user',
    reset_token_hash VARCHAR(64) NULL,
    reset_token_expires_at DATETIME NULL
);
*/
