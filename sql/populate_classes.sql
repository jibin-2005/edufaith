-- Populate classes table with Class 1 to Class 12
USE sunday_school_db;

-- First, ensure the classes table exists
CREATE TABLE IF NOT EXISTS classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_name VARCHAR(100) NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Clear existing classes (optional - remove if you want to keep existing data)
-- DELETE FROM classes;

-- Insert Class 1 to Class 12
INSERT INTO classes (class_name, status) VALUES
('Class 1', 'active'),
('Class 2', 'active'),
('Class 3', 'active'),
('Class 4', 'active'),
('Class 5', 'active'),
('Class 6', 'active'),
('Class 7', 'active'),
('Class 8', 'active'),
('Class 9', 'active'),
('Class 10', 'active'),
('Class 11', 'active'),
('Class 12', 'active')
ON DUPLICATE KEY UPDATE status = 'active';
