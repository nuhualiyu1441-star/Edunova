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

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);
$recipientId = isset($input['recipient_id']) ? (int)$input['recipient_id'] : 0;
$subject = isset($input['subject']) ? trim($input['subject']) : '';
$message = isset($input['message']) ? trim($input['message']) : '';

if (!$recipientId || !$subject || !$message) {
    echo json_encode(['success' => false, 'error' => 'Recipient, subject, and message are required']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Check if recipient exists
$userQuery = "SELECT id, full_name, email FROM users WHERE id = :id";
$userStmt = $db->prepare($userQuery);
$userStmt->execute([':id' => $recipientId]);

if ($userStmt->rowCount() == 0) {
    echo json_encode(['success' => false, 'error' => 'Recipient not found']);
    exit;
}

$recipient = $userStmt->fetch(PDO::FETCH_ASSOC);

// Check if conversation exists between admin and recipient
$convQuery = "SELECT id FROM conversations 
              WHERE (sender_id = :admin_id AND receiver_id = :recipient_id) 
                 OR (sender_id = :recipient_id AND receiver_id = :admin_id)";
$convStmt = $db->prepare($convQuery);
$convStmt->execute([':admin_id' => $userId, ':recipient_id' => $recipientId]);

if ($convStmt->rowCount() > 0) {
    // Use existing conversation
    $conversation = $convStmt->fetch(PDO::FETCH_ASSOC);
    $conversationId = $conversation['id'];
    
    // Update conversation timestamp
    $updateConv = "UPDATE conversations SET updated_at = NOW() WHERE id = :id";
    $updateStmt = $db->prepare($updateConv);
    $updateStmt->execute([':id' => $conversationId]);
} else {
    // Create new conversation
    $insertConv = "INSERT INTO conversations (sender_id, receiver_id, subject, created_at, updated_at) 
                   VALUES (:sender_id, :receiver_id, :subject, NOW(), NOW())";
    $insertStmt = $db->prepare($insertConv);
    $insertStmt->execute([
        ':sender_id' => $userId,
        ':receiver_id' => $recipientId,
        ':subject' => $subject
    ]);
    $conversationId = $db->lastInsertId();
}

// Insert message
$insertMsg = "INSERT INTO messages (conversation_id, sender_id, receiver_id, message, is_read, created_at) 
               VALUES (:conv_id, :sender_id, :receiver_id, :message, 0, NOW())";
$msgStmt = $db->prepare($insertMsg);
$msgStmt->execute([
    ':conv_id' => $conversationId,
    ':sender_id' => $userId,
    ':receiver_id' => $recipientId,
    ':message' => $message
]);

// Get admin info for response
$adminQuery = "SELECT full_name FROM users WHERE id = :id";
$adminStmt = $db->prepare($adminQuery);
$adminStmt->execute([':id' => $userId]);
$admin = $adminStmt->fetch(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'message' => 'Message sent successfully',
    'data' => [
        'conversation_id' => $conversationId,
        'message_id' => $db->lastInsertId(),
        'recipient' => $recipient['full_name']
    ]
]);
?>