<?php
require_once __DIR__ . '/../../middleware/cors.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../utils/Response.php';
require_once __DIR__ . '/../../utils/CircularQueue.php';

// Require authentication
requireAuth();

try {
    $queue = new CircularQueue();
    $messages = $queue->getMessages();
    
    // Format messages for frontend
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
    
} catch (Exception $e) {
    error_log("Get messages error: " . $e->getMessage());
    Response::serverError('Failed to fetch messages');
}
