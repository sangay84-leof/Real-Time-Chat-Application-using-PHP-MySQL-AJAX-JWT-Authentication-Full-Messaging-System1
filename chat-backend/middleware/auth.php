<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../utils/Response.php';

/**
 * Authentication middleware
 * Requires user to be logged in
 */
function requireAuth() {
    if (!isAuthenticated()) {
        Response::unauthorized('Please log in to continue');
    }
}

/**
 * Get authenticated user ID or fail
 */
function getAuthUserId() {
    $userId = getCurrentUserId();
    if (!$userId) {
        Response::unauthorized('Authentication required');
    }
    return $userId;
}
