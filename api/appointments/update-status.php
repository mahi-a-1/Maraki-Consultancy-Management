<?php 
// MNTHC-44: Update Appointment Status by Lydia 
class AppointmentStatus { 
    private $db; 
    public function __construct($db) { 
        $this- = $db; 
    } 
    public function update($id, $status) { 
        $allowed = ['pending', 'approved', 'completed', 'cancelled']; 
        if (!in_array($status, $allowed)) { 
            return ['success' =, 'message' = status']; 
        } 
        $sql = "UPDATE appointments SET status = ?, updated_at = NOW() WHERE id = ?"; 
        $stmt = $this-
        $result = $stmt-, $id]); 
        return ['success' =, 'message' = updated to ' . $status]; 
    } 
} 
