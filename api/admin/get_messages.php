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
    echo json_encode(['success' false, 'error' => 'Invalid token']);
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

// Get messages
$query = "SELECT 
    m.id,
    m.message,
    m.created_at,
    m.is_read,
    u.id as sender_id,
    u.full_name as sender_name,
    u.role as sender_role
FROM messages m
INNER JOIN users u ON m.sender_id = u.id
WHERE m.conversation_id = :conv_id
ORDER BY m.created_at ASC";

$stmt = $db->prepare($query);
$stmt->execute([':conv_id' => $conversationId]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'messages' => $messages
]);
?>