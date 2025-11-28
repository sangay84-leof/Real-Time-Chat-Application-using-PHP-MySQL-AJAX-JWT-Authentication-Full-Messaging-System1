<?php
require_once __DIR__ . '/../../middleware/cors.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../utils/Response.php';

// Require authentication
requireAuth();

// Get current user
$user = getCurrentUser();

if (!$user) {
    Response::unauthorized();
}

Response::success([
    'user' => $user
], 'User retrieved successfully');
