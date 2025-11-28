<?php
require_once __DIR__ . '/../../middleware/cors.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/Response.php';
require_once __DIR__ . '/../../utils/FileHandler.php';

// Require authentication
requireAuth();

// Get last message ID from client
$lastMessageId = $_GET['lastId'] ?? 0;
$timeout = 30; // 30 seconds timeout
$checkInterval = 1; // Check every 1 second
$elapsed = 0;

try {
    $db = Database::getInstance();
    
    while ($elapsed < $timeout) {
        // Check for new messages
        $messages = $db->fetchAll(
            "SELECT m.*, u.username, u.email 
             FROM messages m 
             JOIN users u ON m.user_id = u.id 
             WHERE m.id > ? 
             ORDER BY m.created_at ASC",
            [$lastMessageId]
        );
        
        if (!empty($messages)) {
            // Format messages
            $formattedMessages = array_map(function($msg) {
                return [
                    'id' => $msg['id'],
                    'text' => $msg['text'],
                    'type' => $msg['type'],
                    'isUser' => $msg['user_id'] == getCurrentUserId(),
                    'timestamp' => $msg['created_at'],
                    'user' => [
                        'id' => $msg['user_id'],
                        'username' => $msg['username'],
                        'email' => $msg['email']
                    ],
                    'fileData' => $msg['file_name'] ? [
                        'name' => $msg['file_name'],
                        'url' => $msg['file_url'],
                        'size' => $msg['file_size'],
                        'mimeType' => $msg['mime_type'],
                        'formattedSize' => FileHandler::formatFileSize($msg['file_size'])
                    ] : null
                ];
            }, $messages);
            
            Response::success([
                'messages' => $formattedMessages,
                'count' => count($formattedMessages)
            ]);
        }
        
        // Wait before checking again
        sleep($checkInterval);
        $elapsed += $checkInterval;
    }
    
    // Timeout reached, no new messages
    Response::success([
        'messages' => [],
        'count' => 0
    ]);
    
} catch (Exception $e) {
    error_log("Poll error: " . $e->getMessage());
    Response::serverError('Polling failed');
}
