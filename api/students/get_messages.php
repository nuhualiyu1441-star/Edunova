<?php
// ============================================
// GET MESSAGES FOR STUDENT
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

$user = requireAuth();

$database = new Database();
$db = $database->getConnection();

// Get parameters
$type = isset($_GET['type']) ? $_GET['type'] : 'inbox'; // inbox, sent, archived
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

try {
    if ($type === 'inbox') {
        // Get received messages
        $query = "SELECT m.*, 
                  u.full_name as sender_name, 
                  u.email as sender_email,
                  u.role as sender_role,
                  (SELECT COUNT(*) FROM messages WHERE parent_message_id = m.id) as reply_count
                  FROM messages m
                  JOIN users u ON m.sender_id = u.id
                  WHERE m.receiver_id = :user_id AND m.is_archived = 0
                  ORDER BY m.created_at DESC
                  LIMIT :limit OFFSET :offset";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user['user_id']);
        
    } elseif ($type === 'sent') {
        // Get sent messages
        $query = "SELECT m.*, 
                  u.full_name as receiver_name, 
                  u.email as receiver_email,
                  u.role as receiver_role
                  FROM messages m
                  JOIN users u ON m.receiver_id = u.id
                  WHERE m.sender_id = :user_id AND m.is_archived = 0
                  ORDER BY m.created_at DESC
                  LIMIT :limit OFFSET :offset";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user['user_id']);
        
    } else {
        // Get archived messages
        $query = "SELECT m.*, 
                  u.full_name as other_name, 
                  u.email as other_email
                  FROM messages m
                  JOIN users u ON (u.id = CASE WHEN m.sender_id = :user_id THEN m.receiver_id ELSE m.sender_id END)
                  WHERE (m.sender_id = :user_id OR m.receiver_id = :user_id) AND m.is_archived = 1
                  ORDER BY m.created_at DESC
                  LIMIT :limit OFFSET :offset";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user['user_id']);
    }
    
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Mark unread messages as read when viewing inbox
    if ($type === 'inbox') {
        $updateQuery = "UPDATE messages SET is_read = 1 WHERE receiver_id = :user_id AND is_read = 0";
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->bindParam(':user_id', $user['user_id']);
        $updateStmt->execute();
    }
    
    // Get unread count
    $countQuery = "SELECT COUNT(*) as unread FROM messages WHERE receiver_id = :user_id AND is_read = 0 AND is_archived = 0";
    $countStmt = $db->prepare($countQuery);
    $countStmt->bindParam(':user_id', $user['user_id']);
    $countStmt->execute();
    $unreadCount = $countStmt->fetch(PDO::FETCH_ASSOC)['unread'];
    
    echo json_encode([
        'success' => true,
        'messages' => $messages,
        'unread_count' => (int)$unreadCount,
        'type' => $type
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch messages: ' . $e->getMessage()]);
}
?>