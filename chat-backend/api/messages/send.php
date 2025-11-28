<?php
require_once __DIR__ . '/../../middleware/cors.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../middleware/ratelimit.php';
require_once __DIR__ . '/../../utils/Response.php';
require_once __DIR__ . '/../../utils/Validator.php';
require_once __DIR__ . '/../../utils/CircularQueue.php';

// Require authentication
requireAuth();

// Rate limiting
rateLimit('send_message', 60, 60); // 60 messages per minute

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate input
if (empty($input['text'])) {
    Response::error('Message text is required');
}

$text = Validator::sanitizeString($input['text']);

if (strlen($text) > 5000) {
    Response::error('Message too long (max 5000 characters)');
}

try {
    $userId = getCurrentUserId();
    $queue = new CircularQueue();
    
    // Add message
    $messageId = $queue->addMessage($userId, $text, 'text');
    
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
        ]
    ];
    
    Response::success([
        'message' => $formattedMessage
    ], 'Message sent successfully', 201);
    
} catch (Exception $e) {
    error_log("Send message error: " . $e->getMessage());
    Response::serverError('Failed to send message');
}
