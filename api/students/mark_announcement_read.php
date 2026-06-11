<?php
// ============================================
// MARK ANNOUNCEMENT AS READ
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

if (!isset($data->announcement_id)) {
    http_response_code(400);
    echo json_encode(['error' => 'Announcement ID is required']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

try {
    $query = "INSERT IGNORE INTO announcement_reads (announcement_id, user_id, read_at) 
              VALUES (:announcement_id, :user_id, NOW())";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':announcement_id', $data->announcement_id);
    $stmt->bindParam(':user_id', $user['user_id']);
    $stmt->execute();
    
    echo json_encode(['success' => true, 'message' => 'Marked as read']);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to mark as read: ' . $e->getMessage()]);
}
?>