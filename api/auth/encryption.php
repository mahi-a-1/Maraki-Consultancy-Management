<?php 
// MNTHC-37: Password Encryption Module 
// Created by: Lydia Abebaw 
 
class PasswordEncryption { 
    public static function hash($password) { 
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]); 
    } 
 
    public static function verify($password, $hash) { 
        return password_verify($password, $hash); 
    } 
 
    public static function generateToken($length = 32) { 
        return bin2hex(random_bytes($length)); 
    } 
 
    public static function validateStrength($password) { 
        if (strlen($password) < 8) { 
            return ['valid' => false, 'message' => 'Password must be at least 8 characters']; 
        } 
        if (!preg_match('/[A-Z]/', $password)) { 
            return ['valid' => false, 'message' => 'Password must contain an uppercase letter']; 
        } 
        if (!preg_match('/[0-9]/', $password)) { 
            return ['valid' => false, 'message' => 'Password must contain a number']; 
        } 
        return ['valid' => true, 'message' => 'Password is strong']; 
    } 
} 
