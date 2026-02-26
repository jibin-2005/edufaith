-- Sunday School Sections Setup
-- 4 Sections: Little Flower, Dominic Savio, Alphonsa, St Thomas

-- Create sections table
CREATE TABLE IF NOT EXISTS sections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    section_name VARCHAR(100) NOT NULL UNIQUE,
    class_range VARCHAR(50) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert the 4 sections
INSERT INTO sections (section_name, class_range, description) VALUES
('Little Flower', 'Class 1 to 3', 'Primary section for younger students'),
('Dominic Savio', 'Class 4 to 6', 'Middle section for intermediate students'),
('Alphonsa', 'Class 7 to 9', 'Junior section for pre-high school students'),
('St Thomas', 'Class 10 to 12', 'Senior section for high school students')
ON DUPLICATE KEY UPDATE section_name=section_name;

-- Add section_id to classes table if not exists
ALTER TABLE classes 
ADD COLUMN IF NOT EXISTS section_id INT DEFAULT NULL,
ADD CONSTRAINT fk_classes_section FOREIGN KEY (section_id) REFERENCES sections(id) ON DELETE SET NULL;

-- Add section_id to events table if not exists
ALTER TABLE events 
ADD COLUMN IF NOT EXISTS section_id INT DEFAULT NULL,
ADD COLUMN IF NOT EXISTS published BOOLEAN DEFAULT FALSE,
ADD CONSTRAINT fk_events_section FOREIGN KEY (section_id) REFERENCES sections(id) ON DELETE SET NULL;

-- Add index for better performance
CREATE INDEX IF NOT EXISTS idx_classes_section ON classes(section_id);
CREATE INDEX IF NOT EXISTS idx_events_section ON events(section_id);
CREATE INDEX IF NOT EXISTS idx_events_published ON events(published);

-- Update existing classes to assign sections based on class names
-- This is a sample mapping - adjust based on your actual class names
UPDATE classes SET section_id = 1 WHERE class_name LIKE '%1%' OR class_name LIKE '%2%' OR class_name LIKE '%3%' OR class_name LIKE '%Grade 1%' OR class_name LIKE '%Grade 2%' OR class_name LIKE '%Grade 3%';
UPDATE classes SET section_id = 2 WHERE class_name LIKE '%4%' OR class_name LIKE '%5%' OR class_name LIKE '%6%' OR class_name LIKE '%Grade 4%' OR class_name LIKE '%Grade 5%' OR class_name LIKE '%Grade 6%';
UPDATE classes SET section_id = 3 WHERE class_name LIKE '%7%' OR class_name LIKE '%8%' OR class_name LIKE '%9%' OR class_name LIKE '%Grade 7%' OR class_name LIKE '%Grade 8%' OR class_name LIKE '%Grade 9%';
UPDATE classes SET section_id = 4 WHERE class_name LIKE '%10%' OR class_name LIKE '%11%' OR class_name LIKE '%12%' OR class_name LIKE '%Grade 10%' OR class_name LIKE '%Grade 11%' OR class_name LIKE '%Grade 12%';
