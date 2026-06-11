<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Check authentication
$headers = apache_request_headers();
$token = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : null;

if (!$token) {
    echo json_encode(['success' => false, 'error' => 'No token provided']);
    exit;
}

$userId = validateToken($token);
if (!$userId) {
    echo json_encode(['success' => false, 'error' => 'Invalid token']);
    exit;
}

// Check if user is admin
if (!isAdmin($userId)) {
    echo json_encode(['success' => false, 'error' => 'Admin access required']);
    exit;
}

$conversationId = isset($_GET['conversation_id']) ? (int)$_GET['conversation_id'] : 0;

if (!$conversationId) {
    echo json_encode(['success' => false, 'error' => 'Conversation ID required']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Verify admin has access to this conversation
$checkQuery = "SELECT id FROM conversations WHERE id = :conv_id AND (sender_id = :user_id OR receiver_id = :user_id)";
$checkStmt = $db->prepare($checkQuery);
$checkStmt->execute([':conv_id' => $conversationId, ':user_id' => $userId]);

if ($checkStmt->rowCount() == 0) {
    echo json_encode(['success' => false, 'error' => 'Access denied']);
    exit;
}

// Mark messages as read
$query = "UPDATE messages SET is_read = 1 
          WHERE conversation_id = :conv_id AND receiver_id = :user_id AND is_read = 0";
$stmt = $db->prepare($query);
$stmt->execute([
    ':conv_id' => $conversationId,
    ':user_id' => $userId
]);

$updatedCount = $stmt->rowCount();

echo json_encode([
    'success' => true,
    'message' => "Marked {$updatedCount} messages as read",
    'updated_count' => $updatedCount
]);
?>