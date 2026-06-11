<?php
// ============================================
// REPLY TO MESSAGE
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

$user = requireAuth();

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->parent_message_id) || !isset($data->message)) {
    http_response_code(400);
    echo json_encode(['error' => 'Parent message ID and message are required']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

try {
    $db->beginTransaction();
    
    // Get original message details
    $parentQuery = "SELECT m.*, u.role as sender_role, u2.role as receiver_role 
                    FROM messages m
                    JOIN users u ON m.sender_id = u.id
                    JOIN users u2 ON m.receiver_id = u2.id
                    WHERE m.id = :id";
    $parentStmt = $db->prepare($parentQuery);
    $parentStmt->bindParam(':id', $data->parent_message_id);
    $parentStmt->execute();
    $parent = $parentStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$parent) {
        http_response_code(404);
        echo json_encode(['error' => 'Original message not found']);
        exit();
    }
    
    // Determine receiver (reply to the original sender)
    $receiver_id = ($parent['sender_id'] == $user['user_id']) ? $parent['receiver_id'] : $parent['sender_id'];
    $subject = "Re: " . $parent['subject'];
    
    // Insert reply
    $query = "INSERT INTO messages (sender_id, receiver_id, subject, message, parent_message_id, created_at) 
              VALUES (:sender_id, :receiver_id, :subject, :message, :parent_id, NOW())";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':sender_id', $user['user_id']);
    $stmt->bindParam(':receiver_id', $receiver_id);
    $stmt->bindParam(':subject', $subject);
    $stmt->bindParam(':message', $data->message);
    $stmt->bindParam(':parent_id', $data->parent_message_id);
    $stmt->execute();
    
    $message_id = $db->lastInsertId();
    
    // Create notification
    $notifQuery = "INSERT INTO notifications (user_id, type, title, content, link, created_at) 
                   VALUES (:user_id, 'message', :title, :content, :link, NOW())";
    $notifStmt = $db->prepare($notifQuery);
    $notifTitle = "New reply from " . ($user['role'] == 'admin' ? 'Admin' : 'Student');
    $notifContent = $data->message;
    $notifLink = $user['role'] == 'admin' ? "/pages/admin/messages.html" : "/pages/student/messages.html";
    $notifStmt->bindParam(':user_id', $receiver_id);
    $notifStmt->bindParam(':title', $notifTitle);
    $notifStmt->bindParam(':content', $notifContent);
    $notifStmt->bindParam(':link', $notifLink);
    $notifStmt->execute();
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Reply sent successfully',
        'message_id' => $message_id
    ]);
    
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error' => 'Failed to send reply: ' . $e->getMessage()]);
}
?>