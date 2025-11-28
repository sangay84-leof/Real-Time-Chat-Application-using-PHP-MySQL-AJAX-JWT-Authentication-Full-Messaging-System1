<?php
require_once __DIR__ . '/../config/config.php';

/**
 * CORS middleware
 * Handles Cross-Origin Resource Sharing
 */
function handleCors() {
    // Allow requests from frontend
    header("Access-Control-Allow-Origin: " . FRONTEND_URL);
    // If origin not in allowed list, still allow for development
    // header("Access-Control-Allow-Origin: *");
    
    header("Access-Control-Allow-Credentials: true");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
    header("Access-Control-Max-Age: 3600");
    
    // Handle preflight requests
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
}

// Apply CORS headers
handleCors();
