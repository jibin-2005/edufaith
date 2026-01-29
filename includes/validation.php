<?php
/**
 * Server-side Form Validation Helper
 * Provides comprehensive validation for user forms
 */

class Validator {
    private $errors = [];
    
    /**
     * Validate full name
     */
    public function validateFullName($name, $fieldName = 'Full Name') {
        $name = trim($name);
        
        if (empty($name)) {
            $this->errors[$fieldName] = "$fieldName is required";
            return false;
        }
        
        if (strlen($name) < 2) {
            $this->errors[$fieldName] = "$fieldName must be at least 2 characters";
            return false;
        }
        
        if (strlen($name) > 100) {
            $this->errors[$fieldName] = "$fieldName must be less than 100 characters";
            return false;
        }
        
        // Only allow letters, spaces, dots, hyphens, and apostrophes
        if (!preg_match("/^[a-zA-Z\s\.\-']+$/", $name)) {
            $this->errors[$fieldName] = "$fieldName can only contain letters, spaces, dots, hyphens, and apostrophes";
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate email address
     */
    public function validateEmail($email, $fieldName = 'Email') {
        $email = trim($email);
        
        if (empty($email)) {
            $this->errors[$fieldName] = "$fieldName is required";
            return false;
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$fieldName] = "Please enter a valid email address";
            return false;
        }
        
        if (strlen($email) > 255) {
            $this->errors[$fieldName] = "$fieldName must be less than 255 characters";
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate password
     */
    public function validatePassword($password, $required = true, $fieldName = 'Password') {
        if (!$required && empty($password)) {
            return true;
        }
        
        if (empty($password)) {
            $this->errors[$fieldName] = "$fieldName is required";
            return false;
        }
        
        if (strlen($password) < 6) {
            $this->errors[$fieldName] = "$fieldName must be at least 6 characters";
            return false;
        }
        
        if (strlen($password) > 50) {
            $this->errors[$fieldName] = "$fieldName must be less than 50 characters";
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate phone number
     */
    public function validatePhone($phone, $required = false, $fieldName = 'Phone') {
        $phone = trim($phone);
        
        if (!$required && empty($phone)) {
            return true;
        }
        
        if ($required && empty($phone)) {
            $this->errors[$fieldName] = "$fieldName is required";
            return false;
        }
        
        // Remove common phone formatting characters for validation
        $digits = preg_replace('/[\s\-\+\(\)]/', '', $phone);
        
        if (!ctype_digit($digits) || strlen($digits) < 10 || strlen($digits) > 15) {
            $this->errors[$fieldName] = "Please enter a valid phone number (10-15 digits)";
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate role
     */
    public function validateRole($role, $allowedRoles = ['student', 'teacher', 'parent', 'admin'], $fieldName = 'Role') {
        if (empty($role)) {
            $this->errors[$fieldName] = "$fieldName is required";
            return false;
        }
        
        if (!in_array($role, $allowedRoles)) {
            $this->errors[$fieldName] = "Invalid $fieldName selected";
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate class ID (for students)
     */
    public function validateClassId($classId, $conn, $required = false, $fieldName = 'Class') {
        if (!$required && (empty($classId) || $classId === '')) {
            return true;
        }
        
        if ($required && (empty($classId) || $classId === '')) {
            $this->errors[$fieldName] = "$fieldName is required";
            return false;
        }
        
        // Check if class exists in database
        $stmt = $conn->prepare("SELECT id FROM classes WHERE id = ?");
        $stmt->bind_param("i", $classId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $this->errors[$fieldName] = "Invalid $fieldName selected";
            $stmt->close();
            return false;
        }
        
        $stmt->close();
        return true;
    }
    
    /**
     * Check if email already exists
     */
    public function checkEmailExists($email, $conn, $excludeUserId = null, $fieldName = 'Email') {
        if ($excludeUserId) {
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->bind_param("si", $email, $excludeUserId);
        } else {
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
        $stmt->close();
        
        if ($exists) {
            $this->errors[$fieldName] = "This email address is already in use";
            return true;
        }
        
        return false;
    }
    
    /**
     * Sanitize input string
     */
    public function sanitize($input) {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Get all validation errors
     */
    public function getErrors() {
        return $this->errors;
    }
    
    /**
     * Get first error message
     */
    public function getFirstError() {
        return reset($this->errors);
    }
    
    /**
     * Check if validation passed
     */
    public function isValid() {
        return empty($this->errors);
    }
    
    /**
     * Clear all errors
     */
    public function clearErrors() {
        $this->errors = [];
    }
    
    /**
     * Add custom error
     */
    public function addError($field, $message) {
        $this->errors[$field] = $message;
    }
}

// Helper function for quick validation
function validateUserInput($data, $conn, $isEdit = false, $userId = null) {
    $validator = new Validator();
    
    // Validate fullname
    if (isset($data['fullname'])) {
        $validator->validateFullName($data['fullname']);
    }
    
    // Validate email
    if (isset($data['email'])) {
        $validator->validateEmail($data['email']);
        if ($validator->isValid()) {
            $validator->checkEmailExists($data['email'], $conn, $isEdit ? $userId : null);
        }
    }
    
    // Validate password (required only for new users)
    if (isset($data['password'])) {
        $validator->validatePassword($data['password'], !$isEdit);
    }
    
    // Validate role
    if (isset($data['role'])) {
        $validator->validateRole($data['role']);
    }
    
    // Validate class_id (required for students)
    if (isset($data['class_id']) && isset($data['role']) && $data['role'] === 'student') {
        $validator->validateClassId($data['class_id'], $conn, true);
    }
    
    return $validator;
}
?>
