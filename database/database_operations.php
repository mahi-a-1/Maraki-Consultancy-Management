<?php 
// MNTHC-58: Create Database Operations 
// Created by: Lydia Abebaw 
 
class DatabaseOperations { 
    private $db; 
 
    public function __construct($host = 'localhost', $dbname = 'maraki_db', $user = 'root', $pass = '') { 
        $this- = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass); 
        $this-, PDO::ERRMODE_EXCEPTION); 
    } 
 
    public function createUser($name, $email, $password, $role) { 
        $hashed = password_hash($password, PASSWORD_BCRYPT); 
        $sql = "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)"; 
        $stmt = $this-
        return $stmt-, $email, $hashed, $role]); 
    } 
 
    public function getUserByEmail($email) { 
        $sql = "SELECT * FROM users WHERE email = ?"; 
        $stmt = $this-
        $stmt-
        return $stmt-
    } 
 
    public function createAppointment($patientId, $doctorId, $date, $time, $type) { 
        $sql = "INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, type, status) VALUES (?, ?, ?, ?, ?, 'pending')"; 
        $stmt = $this-
        return $stmt-, $doctorId, $date, $time, $type]); 
    } 
 
    public function updateAppointmentStatus($appointmentId, $status) { 
        $sql = "UPDATE appointments SET status = ? WHERE id = ?"; 
        $stmt = $this-
        return $stmt-, $appointmentId]); 
    } 
} 
