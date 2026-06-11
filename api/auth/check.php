<?php
// ============================================
// CHECK AUTHENTICATION STATUS
// ============================================

require_once '../config/database.php';

$user = isAuthenticated();

if ($user) {
    echo json_encode([
        'authenticated' => true,
        'user' => [
            'id' => $user['user_id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role']
        ]
    ]);
} else {
    echo json_encode([
        'authenticated' => false
    ]);
}
?>