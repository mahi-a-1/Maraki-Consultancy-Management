<?php 
// MNTHC-51: Messaging Database by Lydia 
class MessagingDB { 
    private $db; 
    public function __construct($db) { 
        $this- = $db; 
    } 
    public function sendMessage($sender, $receiver, $message) { 
        $sql = "INSERT INTO messages (sender_id, receiver_id, message, created_at) VALUES (?, ?, ?, NOW())"; 
        $stmt = $this-
        return $stmt-, $receiver, $message]); 
    } 
    public function getMessages($user1, $user2) { 
        $sql = "SELECT * FROM messages WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?) ORDER BY created_at"; 
        $stmt = $this-
        $stmt-, $user2, $user2, $user1]); 
        return $stmt-
    } 
    public function markAsRead($receiver, $sender) { 
        $sql = "UPDATE messages SET is_read = 1, read_at = NOW() WHERE sender_id = ? AND receiver_id = ? AND is_read = 0"; 
        $stmt = $this-
        return $stmt-, $receiver]); 
    } 
} 
