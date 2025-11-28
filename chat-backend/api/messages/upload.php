<?php
require_once __DIR__ . '/../../middleware/cors.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../middleware/ratelimit.php';
require_once __DIR__ . '/../../utils/Response.php';
require_once __DIR__ . '/../../utils/FileHandler.php';
require_once __DIR__ . '/../../utils/CircularQueue.php';
require_once __DIR__ . '/../../config/database.php';

// Require authentication
requireAuth();

// Rate limiting
rateLimit('upload_file', 30, 60); // 30 uploads per minute

// Check if file was uploaded
if (!isset($_FILES['file'])) {
    Response::error('No file uploaded');
}

try {
    $userId = getCurrentUserId();
    
    // Handle file upload
    $fileData = FileHandler::upload($_FILES['file']);
    
    // Add message with file
    $queue = new CircularQueue();
    $messageId = $queue->addMessage($userId, null, $fileData['type'], $fileData);
    
    // Get created message
    $db = Database::getInstance();
    $message = $db->fetchOne(
        "SELECT m.*, u.username, u.email 
         FROM messages m 
         JOIN users u ON m.user_id = u.id 
         WHERE m.id = ?",
        [$messageId]
    );
    
    // Format message
    $formattedMessage = [
        'id' => $message['id'],
        'text' => $message['text'],
        'type' => $message['type'],
        'isUser' => true,
        'timestamp' => $message['created_at'],
        'user' => [
            'id' => $message['user_id'],
            'username' => $message['username'],
            'email' => $message['email']
        ],
        'fileData' => [
            'name' => $message['file_name'],
            'url' => $message['file_url'],
            'size' => $message['file_size'],
            'mimeType' => $message['mime_type'],
            'formattedSize' => FileHandler::formatFileSize($message['file_size'])
        ]
    ];
    
    Response::success([
        'message' => $formattedMessage
    ], 'File uploaded successfully', 201);
    
} catch (Exception $e) {
    error_log("Upload error: " . $e->getMessage());
    Response::error($e->getMessage(), 400);
}
