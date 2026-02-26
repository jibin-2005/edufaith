-- Create Event Registration and Results Tables

-- Event Registrations Table
CREATE TABLE IF NOT EXISTS event_registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    student_id INT NOT NULL,
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(20) DEFAULT 'registered',
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_registration (event_id, student_id),
    INDEX idx_event_registrations_event (event_id),
    INDEX idx_event_registrations_student (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Event Results Table
CREATE TABLE IF NOT EXISTS event_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    student_id INT NOT NULL,
    marks INT DEFAULT NULL,
    remarks TEXT,
    evaluated_by INT,
    evaluated_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (evaluated_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_result (event_id, student_id),
    INDEX idx_event_results_event (event_id),
    INDEX idx_event_results_student (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
