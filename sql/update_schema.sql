USE sunday_school_db;

-- 1. Update Users Table
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS firebase_uid VARCHAR(128) NULL,
ADD COLUMN IF NOT EXISTS status ENUM('active', 'inactive') DEFAULT 'active';

-- Ensure role column is big enough and supports new roles (usually VARCHAR, but just in case)
ALTER TABLE users MODIFY COLUMN role VARCHAR(50) NOT NULL DEFAULT 'student';

-- 2. Create Parent-Student Link Table
CREATE TABLE IF NOT EXISTS parent_student (
    id INT AUTO_INCREMENT PRIMARY KEY,
    parent_id INT NOT NULL,
    student_id INT NOT NULL,
    FOREIGN KEY (parent_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_link (parent_id, student_id)
);

-- 3. Create Attendance Table
CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    class_id INT NOT NULL, -- Assuming class_id relates to a classes table or is just an ID. If 'classes' table exists, we should FK it.
    date DATE NOT NULL,
    status ENUM('Present', 'Absent') NOT NULL,
    marked_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (marked_by) REFERENCES users(id),
    UNIQUE KEY unique_attendance (student_id, date, class_id)
);
