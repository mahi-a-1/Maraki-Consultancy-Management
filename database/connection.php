<?php 
// MNTHC-34: Database Connection by Lydia 
class DB { 
    private static $conn = null; 
    public static function connect() { 
        if (self::$conn === null) { 
            try { 
                self::$conn = new PDO("mysql:host=localhost;dbname=maraki_db", "root", ""); 
                self::$conn-, PDO::ERRMODE_EXCEPTION); 
            } catch(PDOException $e) { 
                die("Connection failed: " . $e-
            } 
        } 
        return self::$conn; 
    } 
} 
