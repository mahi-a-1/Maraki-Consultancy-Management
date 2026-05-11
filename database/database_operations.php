<?php 
// MNTHC-58: Database Operations by Lydia 
class DatabaseOps { 
    private $db; 
    public function __construct($db) { $this- = $db; } 
    public function createUser($name, $email, $password, $role) { 
        $sql = "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)"; 
        $stmt = $this-
        return $stmt-, $email, password_hash($password, PASSWORD_BCRYPT), $role]); 
    } 
    public function getUser($id) { 
        $stmt = $this-
        $stmt-; return $stmt-
    } 
} 
