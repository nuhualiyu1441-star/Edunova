<?php
// ============================================
// VERIFY PASSWORD RESET TOKEN API
// ============================================

require_once '../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

$token = isset($_GET['token']) ? sanitizeInput($_GET['token']) : null;

if (!$token) {
    http_response_code(400);
    echo json_encode(['error' => 'Reset token is required']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

$query = "SELECT id, email FROM users 
          WHERE reset_token = :token 
          AND reset_expires > NOW() 
          AND status = 'active'";
$stmt = $db->prepare($query);
$stmt->bindParam(':token', $token);
$stmt->execute();

if ($stmt->rowCount() > 0) {
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode([
        'success' => true,
        'valid' => true,
        'email' => $user['email'],
        'message' => 'Token is valid'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'valid' => false,
        'message' => 'Invalid or expired reset token'
    ]);
}
?>