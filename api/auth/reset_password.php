<?php
// ============================================
// PASSWORD RESET REQUEST API
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

if (!isset($data->email) || empty($data->email)) {
    http_response_code(400);
    echo json_encode(['error' => 'Email address is required']);
    exit();
}

$email = sanitizeInput($data->email);

if (!validateEmail($email)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid email format']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Check if user exists
$query = "SELECT id, full_name, email FROM users WHERE email = :email AND status = 'active'";
$stmt = $db->prepare($query);
$stmt->bindParam(':email', $email);
$stmt->execute();

if ($stmt->rowCount() === 0) {
    // For security, don't reveal that email doesn't exist
    echo json_encode([
        'success' => true,
        'message' => 'If your email is registered, you will receive a password reset link.'
    ]);
    exit();
}

$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Generate reset token
$reset_token = bin2hex(random_bytes(32));
$expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

// Save token to database
$updateQuery = "UPDATE users SET reset_token = :token, reset_expires = :expires WHERE id = :id";
$updateStmt = $db->prepare($updateQuery);
$updateStmt->bindParam(':token', $reset_token);
$updateStmt->bindParam(':expires', $expires_at);
$updateStmt->bindParam(':id', $user['id']);

if ($updateStmt->execute()) {
    // In production, send email here
    // For now, return the reset link for testing
    $reset_link = "http://localhost/edunova/pages/student/reset_password.html?token=" . $reset_token;
    
    logActivity($user['id'], 'password_reset_request', 'Requested password reset');
    
    echo json_encode([
        'success' => true,
        'message' => 'Password reset link sent to your email',
        'reset_link' => $reset_link, // Remove this in production
        'token' => $reset_token
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to process request']);
}
?>