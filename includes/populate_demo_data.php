<?php
require 'db.php';

// 1. Create Announcement
$sql = "INSERT INTO announcements (title, content, target_role, created_by) VALUES ('Welcome!', 'Welcome to the new digital Sunday School platform.', 'all', 1)";
$conn->query($sql);

// 2. Create Event
$event_date = date('Y-m-d H:i:s', strtotime('+3 days'));
$sql = "INSERT INTO events (title, event_date, description, created_by) VALUES ('Sunday School Day', '$event_date', 'Annual gathering at the main hall.', 1)";
$conn->query($sql);

// 3. Create Assignment (By Teacher ID 1 for simplicity, assuming ID 1 exists/is admin acting as teacher)
$due_date = date('Y-m-d', strtotime('+1 week'));
$sql = "INSERT INTO assignments (title, description, due_date, assigned_by, target_grade) VALUES ('Memorize Psalm 23', 'Recite the full chapter.', '$due_date', 1, 'Grade 4')";
$conn->query($sql);

// 4. Create Leave Request (By Student ID 7)
$start = date('Y-m-d', strtotime('+2 days'));
$end = date('Y-m-d', strtotime('+3 days'));
$sql = "INSERT INTO leaves (user_id, reason, start_date, end_date, status) VALUES (7, 'Family function', '$start', '$end', 'pending')";
$conn->query($sql);

echo "Demo data populated successfully.";
?>
