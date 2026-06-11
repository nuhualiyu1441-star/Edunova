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

// Validate token (you should implement proper JWT validation)
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

$database = new Database();
$db = $database->getConnection();

// Get conversations for admin
$query = "SELECT 
    c.id,
    c.subject,
    c.created_at,
    c.updated_at,
    CASE 
        WHEN c.sender_id = :admin_id THEN c.receiver_id
        ELSE c.sender_id
    END as other_party_id,
    CASE 
        WHEN c.sender_id = :admin_id THEN 
            (SELECT full_name FROM users WHERE id = c.receiver_id)
        ELSE 
            (SELECT full_name FROM users WHERE id = c.sender_id)
    END as other_party_name,
    CASE 
        WHEN c.sender_id = :admin_id THEN 
            (SELECT role FROM users WHERE id = c.receiver_id)
        ELSE 
            (SELECT role FROM users WHERE id = c.sender_id)
    END as other_party_role,
    (SELECT message FROM messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) as last_message,
    (SELECT created_at FROM messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) as last_message_time,
    (SELECT COUNT(*) FROM messages WHERE conversation_id = c.id AND receiver_id = :admin_id AND is_read = 0) as unread_count,
    (SELECT COUNT(*) FROM messages WHERE conversation_id = c.id) as total_messages
FROM conversations c
WHERE c.sender_id = :admin_id OR c.receiver_id = :admin_id
ORDER BY updated_at DESC";

$stmt = $db->prepare($query);
$stmt->execute([':admin_id' => $userId]);
$conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'conversations' => $conversations
]);
?>