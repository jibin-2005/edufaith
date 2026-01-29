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
        if (!preg_match('/[a-zA-Z]/', $text)) return "$fieldName must contain at least one letter.";
        return true;
    }

    /**
     * Validate Description / Content Fields.
     * Rule: Not empty, min 10 chars, contains meaningful text.
     */
    public static function validateDescription($desc, $fieldName, $min = 10) {
        $desc = trim($desc);
        if (empty($desc)) return "$fieldName cannot be empty.";
        if (strlen($desc) < $min) return "$fieldName must be at least $min characters.";
        if (!preg_match('/[a-zA-Z]/', $desc)) return "$fieldName must contain meaningful text.";
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
     * Validate Email.
     */
    public static function validateEmail($email) {
        if (empty($email)) return "Email cannot be empty.";
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
     */
    public static function validateDate($date, $fieldName) {
        if (empty($date)) return "$fieldName cannot be empty.";
        $d = DateTime::createFromFormat('Y-m-d', $date);
        if (!$d || $d->format('Y-m-d') !== $date) {
            // Check for datetime-local format
            $d = DateTime::createFromFormat('Y-m-d\TH:i', $date);
            if (!$d) return "Invalid $fieldName format.";
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
}
?>
