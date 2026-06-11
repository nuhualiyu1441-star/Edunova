<?php
// ============================================
// LOGOUT API
// ============================================

require_once '../config/database.php';

// Get authorization header
$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

// Extract token
$token = null;
if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    $token = $matches[1];
}

// If token exists, verify and log logout
if ($token) {
    $user = verifyJWT($token);
    if ($user) {
        // Log logout activity
        logActivity($user['user_id'], 'logout', 'User logged out');
    }
}

// Clear any server-side sessions if needed
// For JWT, we just let the client delete the token

echo json_encode([
    'success' => true,
    'message' => 'Logged out successfully'
]);
?>