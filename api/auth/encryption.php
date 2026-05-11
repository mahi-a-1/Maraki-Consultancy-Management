<?php 
// MNTHC-37: Password Encryption by Lydia 
class PasswordEncryption { 
    public static function hash($password) { 
        return password_hash($password, PASSWORD_BCRYPT); 
    } 
    public static function verify($password, $hash) { 
        return password_verify($password, $hash); 
    } 
    public static function generateToken($length = 32) { 
        return bin2hex(random_bytes($length)); 
    } 
    public static function validateStrength($password) { 
        if (strlen($password) < 6) return false; 
        if (!preg_match('/[A-Z]/', $password)) return false; 
        if (!preg_match('/[0-9]/', $password)) return false; 
        return true; 
    } 
} 
