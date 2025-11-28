<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils/Response.php';

/**
 * Rate limiting middleware
 */
class RateLimiter {
    private $db;
    private $requests;
    private $window;
    
    public function __construct($requests = null, $window = null) {
        $this->db = Database::getInstance();
        $this->requests = $requests ?? RATE_LIMIT_REQUESTS;
        $this->window = $window ?? RATE_LIMIT_WINDOW;
    }
    
    /**
     * Get identifier (IP or user ID)
     */
    private function getIdentifier() {
        if (isAuthenticated()) {
            return 'user_' . getCurrentUserId();
        }
        return 'ip_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    }
    
    /**
     * Check rate limit
     */
    public function check($endpoint = 'global') {
        $identifier = $this->getIdentifier();
        
        // Clean old entries
        $this->db->query(
            "DELETE FROM rate_limits WHERE window_start < DATE_SUB(NOW(), INTERVAL ? SECOND)",
            [$this->window]
        );
        
        // Get current limit
        $limit = $this->db->fetchOne(
            "SELECT * FROM rate_limits WHERE identifier = ? AND endpoint = ?",
            [$identifier, $endpoint]
        );
        
        if ($limit) {
            // Check if within window
            $windowStart = strtotime($limit['window_start']);
            $now = time();
            
            if ($now - $windowStart < $this->window) {
                // Within window, check request count
                if ($limit['requests'] >= $this->requests) {
                    Response::error('Too many requests. Please try again later.', 429);
                }
                
                // Increment request count
                $this->db->query(
                    "UPDATE rate_limits SET requests = requests + 1 WHERE id = ?",
                    [$limit['id']]
                );
            } else {
                // Window expired, reset
                $this->db->query(
                    "UPDATE rate_limits SET requests = 1, window_start = NOW() WHERE id = ?",
                    [$limit['id']]
                );
            }
        } else {
            // First request, create entry
            $this->db->query(
                "INSERT INTO rate_limits (identifier, endpoint, requests, window_start) VALUES (?, ?, 1, NOW())",
                [$identifier, $endpoint]
            );
        }
    }
}

/**
 * Apply rate limiting
 */
function rateLimit($endpoint = 'global', $requests = null, $window = null) {
    $limiter = new RateLimiter($requests, $window);
    $limiter->check($endpoint);
}
