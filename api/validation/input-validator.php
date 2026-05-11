<?php 
// MNTHC-33: Input Validation Module 
// Created by: Lydia Abebaw 
 
class InputValidator { 
 
    // Validate email address 
    public static function validateEmail($email) { 
        $email = trim($email); 
        if (empty($email)) { 
            return ['valid' => false, 'message' => 'Email is required']; 
        } 
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { 
            return ['valid' => false, 'message' => 'Invalid email format']; 
        } 
        return ['valid' => true, 'message' => 'Email is valid']; 
    } 
 
    // Validate name (only letters and spaces) 
    public static function validateName($name) { 
        $name = trim($name); 
        if (empty($name)) { 
            return ['valid' => false, 'message' => 'Name is required']; 
        } 
        if (strlen($name) < 2) { 
            return ['valid' => false, 'message' => 'Name must be at least 2 characters']; 
        } 
        if (strlen($name) > 100) { 
            return ['valid' => false, 'message' => 'Name cannot exceed 100 characters']; 
        } 
        if (!preg_match('/[a-zA-Z\s]+$/', $name)) { 
            return ['valid' => false, 'message' => 'Name can only contain letters and spaces']; 
        } 
        return ['valid' => true, 'message' => 'Name is valid']; 
    } 
 
    // Validate phone number 
    public static function validatePhone($phone) { 
        $phone = trim($phone); 
        if (empty($phone)) { 
            return ['valid' => false, 'message' => 'Phone number is required']; 
        } 
        // Ethiopian phone number format (09xxxxxxxx or 2519xxxxxxxx) 
            return ['valid' => false, 'message' => 'Invalid Ethiopian phone number']; 
        } 
        return ['valid' => true, 'message' => 'Phone number is valid']; 
    } 
 
    // Validate password strength 
    public static function validatePassword($password) { 
        if (empty($password)) { 
            return ['valid' => false, 'message' => 'Password is required']; 
        } 
        if (strlen($password) < 6) { 
            return ['valid' => false, 'message' => 'Password must be at least 6 characters']; 
        } 
        if (!preg_match('/[A-Z]/', $password)) { 
            return ['valid' => false, 'message' => 'Password must contain at least one uppercase letter']; 
        } 
        if (!preg_match('/[a-z]/', $password)) { 
            return ['valid' => false, 'message' => 'Password must contain at least one lowercase letter']; 
        } 
        if (!preg_match('/[0-9]/', $password)) { 
            return ['valid' => false, 'message' => 'Password must contain at least one number']; 
        } 
        return ['valid' => true, 'message' => 'Password is strong']; 
    } 
 
    // Validate date format (YYYY-MM-DD) 
    public static function validateDate($date) { 
        if (empty($date)) { 
            return ['valid' => false, 'message' => 'Date is required']; 
        } 
        $d = DateTime::createFromFormat('Y-m-d', $date); 
        if (!$d || $d- !== $date) { 
            return ['valid' => false, 'message' => 'Invalid date format. Use YYYY-MM-DD']; 
        } 
        return ['valid' => true, 'message' => 'Date is valid']; 
    } 
 
    // Validate appointment time 
    public static function validateTime($time) { 
        if (empty($time)) { 
            return ['valid' => false, 'message' => 'Time is required']; 
        } 
            return ['valid' => false, 'message' => 'Invalid time format. Use HH:MM in 24-hour format']; 
        } 
        return ['valid' => true, 'message' => 'Time is valid']; 
    } 
 
    // Sanitize input (remove dangerous characters) 
    public static function sanitize($input) { 
        if (is_array($input)) { 
            return array_map([self::class, 'sanitize'], $input); 
        } 
        $input = trim($input); 
        $input = stripslashes($input); 
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8'); 
        return $input; 
    } 
 
    // Validate registration form (all fields) 
    public static function validateRegistration($data) { 
        $errors = []; 
 
        $nameResult = self::validateName($data['name'] ?? ''); 
        if (!$nameResult['valid']) $errors['name'] = $nameResult['message']; 
 
        $emailResult = self::validateEmail($data['email'] ?? ''); 
        if (!$emailResult['valid']) $errors['email'] = $emailResult['message']; 
 
        $phoneResult = self::validatePhone($data['phone'] ?? ''); 
        if (!$phoneResult['valid']) $errors['phone'] = $phoneResult['message']; 
 
        $passResult = self::validatePassword($data['password'] ?? ''); 
        if (!$passResult['valid']) $errors['password'] = $passResult['message']; 
 
        // Check if passwords match 
        if (($data['password'] ?? '') !== ($data['confirm_password'] ?? '')) { 
            $errors['confirm_password'] = 'Passwords do not match'; 
        } 
 
        return [ 
            'valid' => empty($errors), 
            'errors' => $errors 
        ]; 
    } 
 
    // Validate appointment booking 
    public static function validateAppointment($data) { 
        $errors = []; 
 
        $dateResult = self::validateDate($data['date'] ?? ''); 
        if (!$dateResult['valid']) $errors['date'] = $dateResult['message']; 
 
        $timeResult = self::validateTime($data['time'] ?? ''); 
        if (!$timeResult['valid']) $errors['time'] = $timeResult['message']; 
 
        if (empty($data['doctor_id'])) { 
            $errors['doctor_id'] = 'Please select a doctor'; 
        } 
 
        if (empty($data['type'])) { 
            $errors['type'] = 'Please select consultation type'; 
        } 
 
        return [ 
            'valid' => empty($errors), 
            'errors' => $errors 
        ]; 
    } 
} 
