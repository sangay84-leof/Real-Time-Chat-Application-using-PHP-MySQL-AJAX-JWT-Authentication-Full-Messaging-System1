<?php
require_once __DIR__ . '/../../middleware/cors.php';
require_once __DIR__ . '/../../middleware/ratelimit.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../utils/Response.php';
require_once __DIR__ . '/../../utils/Validator.php';

// Rate limiting
rateLimit('register', 10, 3600); // 10 requests per hour

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate input
$validation = Validator::validate($input, [
    'username' => ['required' => true, 'username' => true],
    'email' => ['required' => true, 'email' => true],
    'password' => ['required' => true, 'password' => true]
]);

if ($validation !== true) {
    Response::validationError($validation);
}

// Sanitize input
$username = Validator::sanitizeString($input['username']);
$email = Validator::sanitizeEmail($input['email']);
$password = $input['password'];

try {
    $db = Database::getInstance();
    
    // Check if username exists
    $existingUser = $db->fetchOne(
        "SELECT id FROM users WHERE username = ?",
        [$username]
    );
    
    if ($existingUser) {
        Response::error('Username already taken', 400);
    }
    
    // Check if email exists
    $existingEmail = $db->fetchOne(
        "SELECT id FROM users WHERE email = ?",
        [$email]
    );
    
    if ($existingEmail) {
        Response::error('Email already registered', 400);
    }
    
    // Hash password
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    
    // Create user
    $db->query(
        "INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)",
        [$username, $email, $passwordHash]
    );
    
    $userId = $db->lastInsertId();
    
    // Get created user
    $user = $db->fetchOne(
        "SELECT id, username, email, created_at FROM users WHERE id = ?",
        [$userId]
    );
    
    // Set session
    setUserSession($user);
    
    Response::success([
        'user' => $user
    ], 'Registration successful', 201);
    
} catch (Exception $e) {
    error_log("Registration error: " . $e->getMessage());
    Response::serverError('Registration failed');
}
