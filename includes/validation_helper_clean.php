<?php
/**
 * validation_helper.php
 * Centralized validation logic for the Sunday School Management System.
 */

class Validator {
    
    /**
     * Sanitize input to prevent XSS.
     */
    public static function sanitize($data) {
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Validate Text Fields (titles, names, subjects).
     * Rule: Not empty, >= 3 chars, <= 255 chars, contains at least one alphabet.
     */
    public static function validateText($text, $fieldName, $min = 3, $max = 255) {
        $text = trim($text);
        if (empty($text)) return "$fieldName cannot be empty.";
        if (strlen($text) < $min) return "$fieldName must be at least $min characters.";
        if (strlen($text) > $max) return "$fieldName cannot exceed $max characters.";
        
        // At least one alphabet
        if (!preg_match('/[a-zA-Z]/', $text)) return "$fieldName must contain at least one letter.";
        
        // Should not be only numbers
        if (ctype_digit($text)) return "$fieldName cannot consist of only numbers.";
        
        return true;
    }
    
    /**
     * Validate Title / Heading.
     * Rule: Not empty, No only special chars, No only numbers.
     */
    public static function validateTitle($text, $fieldName) {
        $text = trim($text);
        if (empty($text)) return "$fieldName cannot be empty.";
        
        // At least one alphabet
        if (!preg_match('/[a-zA-Z]/', $text)) return "$fieldName must contain at least one alphabet.";
        
        // Should not be only numbers
        if (ctype_digit($text)) return "$fieldName cannot consist of only numbers.";
        
        // Should not be only special chars (roughly checks if there's no alphanum)
        if (!preg_match('/[a-zA-Z0-9]/', $text)) return "$fieldName cannot consist of only special characters.";
        
        return true;
    }

    /**
     * Validate Description / Content Fields.
     * Rule: Not empty, min 10 chars, contains meaningful text (at least one alpha).
     */
    public static function validateDescription($desc, $fieldName, $min = 10) {
        $desc = trim($desc);
        if (empty($desc)) return "$fieldName cannot be empty.";
        if (strlen($desc) < $min) return "$fieldName must be at least $min characters.";
        if (!preg_match('/[a-zA-Z]/', $desc)) return "$fieldName must contain at least one alphabet.";
        return true;
    }

    /**
     * Validate Numeric Fields.
     * Rule: Only numbers, disallow negative, check range.
     */
    public static function validateNumeric($value, $fieldName, $min = 0, $max = null) {
        if (!is_numeric($value)) return "$fieldName must be a number.";
        if ($value < 0) return "$fieldName cannot be negative.";
        if ($min !== null && $value < $min) return "$fieldName must be at least $min.";
        if ($max !== null && $value > $max) return "$fieldName cannot exceed $max.";
        return true;
    }

    /**
     * Validate Email (Strict).
     */
    public static function validateEmail($email) {
        if (empty($email)) return "Email cannot be empty.";
        $email = trim($email);
        
        // Strict regex match before filter_var
        if (!preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $email)) {
            return "Invalid email format.";
        }
        
        // No spaces allowed
        if (strpos($email, ' ') !== false) return "Email cannot contain spaces.";
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return "Invalid email format.";
        return true;
    }

    /**
     * Validate Password.
     * Rule: Min length 6, not empty.
     */
    public static function validatePassword($password, $min = 6) {
        if (empty($password)) return "Password cannot be empty.";
        if (strlen($password) < $min) return "Password must be at least $min characters.";
        return true;
    }

    /**
     * Validate Phone.
     * Rule: Numeric, correct length (e.g. 10).
     */
    public static function validatePhone($phone) {
        if (empty($phone)) return "Phone number cannot be empty.";
        if (!preg_match('/^[0-9]{10}$/', $phone)) return "Phone number must be exactly 10 digits.";
        return true;
    }

    /**
     * Validate Date.
     * $type: 'future_only' (>= today), 'past_only' (<= today), 'any'
     */
    public static function validateDate($date, $fieldName, $type = 'any') {
        if (empty($date)) return "$fieldName cannot be empty.";
        
        // Helper to parse date
        $d = DateTime::createFromFormat('Y-m-d', $date);
        if (!$d || $d->format('Y-m-d') !== $date) {
            // Check for datetime-local format if strictly Y-m-d fails
            $d = DateTime::createFromFormat('Y-m-d\TH:i', $date);
            if (!$d) return "Invalid $fieldName format.";
        }

        $today = new DateTime();
        $today->setTime(0,0,0); // Midnight
        $d->setTime(0,0,0);

        if ($type === 'future_only') {
            if ($d < $today) return "$fieldName cannot be in the past.";
        } elseif ($type === 'past_only') {
            if ($d > $today) return "$fieldName cannot be in the future.";
        }

        return true;
    }

    /**
     * Validate Select/Dropdown.
     */
    public static function validateDropdown($value, $allowedValues, $fieldName) {
        if (empty($value) && $value !== '0') return "Please select a valid $fieldName.";
        if (!in_array($value, $allowedValues)) return "Invalid selection for $fieldName.";
        return true;
    }

    /**
     * Validate File Upload.
     */
    public static function validateFile($file, $fieldName, $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf'], $maxSize = 2097152) { // Default 2MB
        if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) return "Please choose a $fieldName.";
        if ($file['error'] !== UPLOAD_ERR_OK) return "Error uploading $fieldName.";
        
        $fileName = $file['name'];
        $fileSize = $file['size'];
        $fileTmp = $file['tmp_name'];
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (!in_array($ext, $allowedExtensions)) return "Invalid file type. Allowed: " . implode(', ', $allowedExtensions);
        if ($fileSize > $maxSize) return "File size exceeds limit (" . ($maxSize/1048576) . "MB).";
        
        // Basic MIME check
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $fileTmp);
        finfo_close($finfo);
        
        $allowedMimes = [
            'image/jpeg', 'image/png', 'application/pdf', 
            'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];
        if (!in_array($mime, $allowedMimes)) return "Invalid file content.";

        return true;
    }

    /**
     * Check if username is unique
     */
    public static function isUsernameUnique($conn, $username, $excludeUserId = null) {
        $sql = "SELECT id FROM users WHERE username = ?";
        if ($excludeUserId) {
            $sql .= " AND id != ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $username, $excludeUserId);
        } else {
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $username);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
        $stmt->close();
        return !$exists;
    }

    /**
     * Check if phone number is unique
     */
    public static function isPhoneUnique($conn, $phone, $excludeUserId = null) {
        if (empty($phone)) return true; // Phone is optional
        
        $sql = "SELECT id FROM users WHERE phone = ?";
        if ($excludeUserId) {
            $sql .= " AND id != ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $phone, $excludeUserId);
        } else {
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $phone);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
        $stmt->close();
        return !$exists;
    }

    /**
     * Check if class name is unique
     */
    public static function isClassNameUnique($conn, $className, $excludeClassId = null) {
        $sql = "SELECT id FROM classes WHERE class_name = ?";
        if ($excludeClassId) {
            $sql .= " AND id != ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $className, $excludeClassId);
        } else {
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $className);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
        $stmt->close();
        return !$exists;
    }

    /**
     * Check if date is Sunday
     */
    public static function isSunday($date) {
        $weekday = date('w', strtotime($date));
        return $weekday == 0;
    }

    /**
     * Get student attendance percentage
     */
    public static function getAttendancePercentage($conn, $studentId) {
        $stmt = $conn->prepare("SELECT 
            COUNT(*) as total_days,
            SUM(CASE WHEN status IN ('Present', 'Leave Approved') THEN 1 ELSE 0 END) as present_days
            FROM attendance WHERE student_id = ?");
        $stmt->bind_param("i", $studentId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        $total = $result['total_days'] ?? 0;
        $present = $result['present_days'] ?? 0;
        
        if ($total == 0) return 0;
        return round(($present / $total) * 100, 2);
    }

    /**
     * Count pending leave requests for student
     */
    public static function countPendingLeaves($conn, $studentId) {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM leave_requests WHERE student_id = ? AND status = 'pending'");
        $stmt->bind_param("i", $studentId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result['count'] ?? 0;
    }

    /**
     * Count approved leaves in current term (last 3 months)
     */
    public static function countApprovedLeavesInTerm($conn, $studentId) {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM leave_requests 
                                WHERE student_id = ? AND status = 'approved' 
                                AND leave_date >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)");
        $stmt->bind_param("i", $studentId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result['count'] ?? 0;
    }

    /**
     * Check if teacher already has a class assigned
     */
    public static function teacherHasClass($conn, $teacherId, $excludeClassId = null) {
        $sql = "SELECT id FROM classes WHERE teacher_id = ?";
        if ($excludeClassId) {
            $sql .= " AND id != ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $teacherId, $excludeClassId);
        } else {
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $teacherId);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $hasClass = $result->num_rows > 0;
        $stmt->close();
        return $hasClass;
    }

    /**
     * Check if class has enrolled students
     */
    public static function classHasStudents($conn, $classId) {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE class_id = ? AND role = 'student'");
        $stmt->bind_param("i", $classId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return ($result['count'] ?? 0) > 0;
    }

    /**
     * Check if teacher has pending results to grade
     */
    public static function teacherHasPendingResults($conn, $teacherId) {
        // Check if there are students in teacher's class without results
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM users u
                                JOIN classes c ON u.class_id = c.id
                                LEFT JOIN results r ON u.id = r.student_id
                                WHERE c.teacher_id = ? AND u.role = 'student' AND r.id IS NULL");
        $stmt->bind_param("i", $teacherId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return ($result['count'] ?? 0) > 0;
    }

    /**
     * Count messages sent by user in last hour
     */
    public static function countRecentMessages($conn, $userId) {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM messages 
                                WHERE sender_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result['count'] ?? 0;
    }

    /**
     * Check if parent-student link already exists
     */
    public static function parentStudentLinkExists($conn, $parentId, $studentId) {
        $stmt = $conn->prepare("SELECT id FROM parent_student WHERE parent_id = ? AND student_id = ?");
        $stmt->bind_param("ii", $parentId, $studentId);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
        $stmt->close();
        return $exists;
    }

    /**
     * Count children linked to parent
     */
    public static function countParentChildren($conn, $parentId) {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM parent_student WHERE parent_id = ?");
        $stmt->bind_param("i", $parentId);
        $stmt->execute();
