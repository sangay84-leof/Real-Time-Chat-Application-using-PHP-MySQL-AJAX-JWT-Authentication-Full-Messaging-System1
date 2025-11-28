<?php
require_once __DIR__ . '/../../middleware/cors.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../utils/Response.php';
require_once __DIR__ . '/../../utils/CircularQueue.php';

// Require authentication
requireAuth();

// Get message ID from query string or JSON
$messageId = $_GET['id'] ?? null;

if (!$messageId) {
    $input = json_decode(file_get_contents('php://input'), true);
    $messageId = $input['id'] ?? null;
}

if (!$messageId) {
    Response::error('Message ID is required');
}

try {
    $userId = getCurrentUserId();
    $queue = new CircularQueue();
    
    // Delete message (checks ownership)
    $deleted = $queue->deleteMessage($messageId, $userId);
    
    if (!$deleted) {
        Response::error('Message not found or unauthorized', 404);
    }
    
    Response::success(null, 'Message deleted successfully');
    
} catch (Exception $e) {
    error_log("Delete message error: " . $e->getMessage());
    Response::serverError('Failed to delete message');
}
