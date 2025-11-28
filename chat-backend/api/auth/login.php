<?php
require_once __DIR__ . '/../../middleware/cors.php';
require_once __DIR__ . '/../../middleware/ratelimit.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../utils/Response.php';
require_once __DIR__ . '/../../utils/Validator.php';

// Rate limiting
rateLimit('login', 20, 900); // 20 requests per 15 minutes

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate input
$validation = Validator::validate($input, [
    'username' => ['required' => true],
    'password' => ['required' => true]
]);

if ($validation !== true) {
    Response::validationError($validation);
}

$username = Validator::sanitizeString($input['username']);
$password = $input['password'];

try {
    $db = Database::getInstance();
    
    // Get user by username or email
    $user = $db->fetchOne(
        "SELECT * FROM users WHERE username = ? OR email = ?",
        [$username, $username]
    );
    
    if (!$user) {
        Response::error('Invalid credentials', 401);
    }
    
    // Verify password
    if (!password_verify($password, $user['password_hash'])) {
        Response::error('Invalid credentials', 401);
    }
    
    // Set session
    setUserSession($user);
    
    // Return user data (without password hash)
    unset($user['password_hash']);
    
    Response::success([
        'user' => $user
    ], 'Login successful');
    
} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    Response::serverError('Login failed');
}
