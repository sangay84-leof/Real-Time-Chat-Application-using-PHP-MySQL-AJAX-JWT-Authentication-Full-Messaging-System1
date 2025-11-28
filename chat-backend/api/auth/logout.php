<?php
require_once __DIR__ . '/../../middleware/cors.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../utils/Response.php';

// Destroy session
destroySession();

Response::success(null, 'Logged out successfully');
