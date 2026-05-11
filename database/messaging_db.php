<?php 
// MNTHC-51: Connect Messaging Database 
// Created by: Lydia Abebaw 
 
class MessagingDatabase { 
    private $db; 
 
    public function __construct() { 
        $this- = new PDO('mysql:host=localhost;dbname=maraki_db', 'root', ''); 
        $this-, PDO::ERRMODE_EXCEPTION); 
    } 
 
    public function sendMessage($senderId, $receiverId, $message) { 
        $sql = "INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)"; 
        $stmt = $this-
        return $stmt-, $receiverId, $message]); 
    } 
 
    public function getMessages($user1, $user2) { 
        $sql = "SELECT * FROM messages WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?) ORDER BY created_at"; 
        $stmt = $this-
        $stmt-, $user2, $user2, $user1]); 
        return $stmt-
    } 
} 
