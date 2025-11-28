<?php
// Test registration directly
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session.php';

try {
    $db = Database::getInstance();
    echo "✓ Database connected\n";
    
    // Test insert
    $username = "testuser123";
    $email = "test123@example.com";
    $password = "password123";
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    
    echo "Inserting user...\n";
    $db->query(
        "INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)",
        [$username, $email, $passwordHash]
    );
    
    $userId = $db->lastInsertId();
    echo "✓ User created with ID: $userId\n";
    
    // Get user
    $user = $db->fetchOne(
        "SELECT id, username, email, created_at FROM users WHERE id = ?",
        [$userId]
    );
    
    print_r($user);
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
