<?php
// die("CONFIG LOADED"); // Disabled to allow config loading
function loadEnv($path) {
    if (!file_exists($path)) {
        return;
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // Parse KEY=VALUE
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        
        // Set environment variable
        if (!array_key_exists($key, $_ENV)) {
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
}

// Load environment variables
loadEnv(__DIR__ . '/../.env');

// Application Configuration
define('APP_ENV', $_ENV['APP_ENV'] ?? 'production');
define('APP_URL', $_ENV['APP_URL'] ?? 'http://localhost:8000');
define('FRONTEND_URL', $_ENV['FRONTEND_URL'] ?? 'http://localhost:8000');

// Database Configuration
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'chat_app');
define('DB_USER', 'root');
define('DB_PASS', 'Sangay@3571@');

error_log("DEBUG: DB_HOST is " . DB_HOST);

error_log("DEBUG: DB_HOST is " . DB_HOST);
error_log("DEBUG: DB_USER is " . DB_USER);

// Session Configuration
define('SESSION_LIFETIME', $_ENV['SESSION_LIFETIME'] ?? 86400);
define('SESSION_NAME', $_ENV['SESSION_NAME'] ?? 'chat_session');

// File Upload Configuration
define('UPLOAD_MAX_SIZE', $_ENV['UPLOAD_MAX_SIZE'] ?? 52428800); // 50MB
define('UPLOAD_PATH', __DIR__ . '/../uploads/');

// Rate Limiting
define('RATE_LIMIT_REQUESTS', $_ENV['RATE_LIMIT_REQUESTS'] ?? 100);
define('RATE_LIMIT_WINDOW', $_ENV['RATE_LIMIT_WINDOW'] ?? 60);

// Allowed file types
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('ALLOWED_VIDEO_TYPES', ['video/mp4', 'video/webm', 'video/ogg']);
define('ALLOWED_AUDIO_TYPES', ['audio/mpeg', 'audio/ogg', 'audio/wav', 'audio/webm']);
define('ALLOWED_FILE_TYPES', ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'text/plain']);

// Circular queue limit
define('MESSAGE_QUEUE_LIMIT', 5);

// Error reporting
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
