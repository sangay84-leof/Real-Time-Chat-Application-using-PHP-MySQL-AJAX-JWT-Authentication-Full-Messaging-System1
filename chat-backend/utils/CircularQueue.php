<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';

/**
 * Circular Queue implementation for message management
 */
class CircularQueue {
    private $db;
    private $limit;
    
    public function __construct($limit = MESSAGE_QUEUE_LIMIT) {
        $this->db = Database::getInstance();
        $this->limit = $limit;
    }
    
    /**
     * Get message count
     */
    public function getCount() {
        $result = $this->db->fetchOne("SELECT COUNT(*) as count FROM messages");
        return $result['count'] ?? 0;
    }
    
    /**
     * Enforce circular queue limit
     * Deletes oldest messages if count exceeds limit
     */
    public function enforceLimit() {
        $count = $this->getCount();
        
        if ($count >= $this->limit) {
            $deleteCount = $count - $this->limit + 1;
            
            // Get oldest messages to delete
            $oldMessages = $this->db->fetchAll(
                "SELECT id, file_name FROM messages ORDER BY created_at ASC LIMIT ?",
                [$deleteCount]
            );
            
            // Delete files and messages
            foreach ($oldMessages as $message) {
                if ($message['file_name']) {
                    require_once __DIR__ . '/FileHandler.php';
                    FileHandler::delete($message['file_name']);
                }
                
                $this->db->query("DELETE FROM messages WHERE id = ?", [$message['id']]);
            }
        }
    }
    
    /**
     * Add message (enforces limit)
     */
    public function addMessage($userId, $text, $type = 'text', $fileData = null) {
        // Enforce limit before adding
        $this->enforceLimit();
        
        // Insert new message
        $sql = "INSERT INTO messages (user_id, text, type, file_name, file_url, file_size, mime_type) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $userId,
            $text,
            $type,
            $fileData['filename'] ?? null,
            $fileData['url'] ?? null,
            $fileData['size'] ?? null,
            $fileData['mime_type'] ?? null
        ];
        
        $this->db->query($sql, $params);
        return $this->db->lastInsertId();
    }
    
    /**
     * Get all messages
     */
    public function getMessages() {
        $sql = "SELECT m.*, u.username, u.email 
                FROM messages m 
                JOIN users u ON m.user_id = u.id 
                ORDER BY m.created_at ASC 
                LIMIT ?";
        
        return $this->db->fetchAll($sql, [$this->limit]);
    }
    
    /**
     * Delete message by ID
     */
    public function deleteMessage($messageId, $userId) {
        // Get message to check ownership and file
        $message = $this->db->fetchOne(
            "SELECT * FROM messages WHERE id = ? AND user_id = ?",
            [$messageId, $userId]
        );
        
        if (!$message) {
            return false;
        }
        
        // Delete file if exists
        if ($message['file_name']) {
            require_once __DIR__ . '/FileHandler.php';
            FileHandler::delete($message['file_name']);
        }
        
        // Delete message
        $this->db->query("DELETE FROM messages WHERE id = ?", [$messageId]);
        return true;
    }
}
