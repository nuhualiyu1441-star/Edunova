<?php
// ============================================
// CONFIRM PASSWORD RESET API
// ============================================

require_once '../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->token) || empty($data->token)) {
    http_response_code(400);
    echo json_encode(['error' => 'Reset token is required']);
    exit();
}

if (!isset($data->password) || !isset($data->confirm_password)) {
    http_response_code(400);
    echo json_encode(['error' => 'Password and confirmation are required']);
    exit();
}

$token = sanitizeInput($data->token);
$password = $data->password;
$confirm_password = $data->confirm_password;

if ($password !== $confirm_password) {
    http_response_code(400);
    echo json_encode(['error' => 'Passwords do not match']);
    exit();
}

if (strlen($password) < 6) {
    http_response_code(400);
    echo json_encode(['error' => 'Password must be at least 6 characters']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Verify token
$query = "SELECT id, email, full_name FROM users 
          WHERE reset_token = :token 
          AND reset_expires > NOW() 
          AND status = 'active'";
$stmt = $db->prepare($query);
$stmt->bindParam(':token', $token);
$stmt->execute();

if ($stmt->rowCount() === 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid or expired reset token']);
    exit();
}

$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Hash new password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Update password and clear reset token
$updateQuery = "UPDATE users SET 
                password = :password, 
                reset_token = NULL, 
                reset_expires = NULL 
                WHERE id = :id";
$updateStmt = $db->prepare($updateQuery);
$updateStmt->bindParam(':password', $hashed_password);
$updateStmt->bindParam(':id', $user['id']);

if ($updateStmt->execute()) {
    logActivity($user['id'], 'password_reset_complete', 'Successfully reset password');
    
    echo json_encode([
        'success' => true,
        'message' => 'Password has been reset successfully. You can now login with your new password.'
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to reset password']);
}
?>