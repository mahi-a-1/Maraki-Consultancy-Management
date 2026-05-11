<?php 
// MNTHC-44: Update Appointment Status 
// Created by: Lydia Abebaw 
 
class AppointmentStatusManager { 
    private $db; 
    private $allowedStatuses = ['pending', 'approved', 'completed', 'cancelled', 'rescheduled']; 
 
    public function __construct($databaseConnection) { 
        $this- = $databaseConnection; 
    } 
 
    public function updateStatus($appointmentId, $newStatus, $updatedBy) { 
        if (!in_array($newStatus, $this- { 
            return ['success' => false, 'message' => 'Invalid status']; 
        } 
 
        $sql = "UPDATE appointments SET status = ?, updated_at = NOW() WHERE id = ?"; 
        $stmt = $this-
        $result = $stmt-, $appointmentId]); 
 
        return ['success' => $result, 'message' => 'Status updated to ' . $newStatus]; 
    } 
} 
