<?php
// ============================================
// GET NOTIFICATIONS FOR USER
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

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

try {
    // Get notifications
    $query = "SELECT * FROM notifications 
              WHERE user_id = :user_id 
              ORDER BY created_at DESC 
              LIMIT :limit OFFSET :offset";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user['user_id']);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get unread count
    $countQuery = "SELECT COUNT(*) as unread FROM notifications WHERE user_id = :user_id AND is_read = 0";
    $countStmt = $db->prepare($countQuery);
    $countStmt->bindParam(':user_id', $user['user_id']);
    $countStmt->execute();
    $unreadCount = $countStmt->fetch(PDO::FETCH_ASSOC)['unread'];
    
    echo json_encode([
        'success' => true,
        'notifications' => $notifications,
        'unread_count' => (int)$unreadCount
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch notifications: ' . $e->getMessage()]);
}
?>