<?php
// ============================================
// SEND ANNOUNCEMENT TO ALL STUDENTS
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

$admin = requireAdmin();

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->title) || !isset($data->content)) {
    http_response_code(400);
    echo json_encode(['error' => 'Title and content are required']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

try {
    $db->beginTransaction();
    
    // Insert announcement
    $target_role = isset($data->target_role) ? $data->target_role : 'all';
    $query = "INSERT INTO announcements (sender_id, title, content, target_role, is_published, created_at) 
              VALUES (:sender_id, :title, :content, :target_role, 1, NOW())";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':sender_id', $admin['user_id']);
    $stmt->bindParam(':title', $data->title);
    $stmt->bindParam(':content', $data->content);
    $stmt->bindParam(':target_role', $target_role);
    $stmt->execute();
    
    $announcement_id = $db->lastInsertId();
    
    // Get all students (or target users)
    if ($target_role == 'all' || $target_role == 'students') {
        $userQuery = "SELECT id FROM users WHERE role = 'student' AND status = 'active'";
    } elseif ($target_role == 'instructors') {
        $userQuery = "SELECT id FROM users WHERE role = 'instructor' AND status = 'active'";
    } else {
        $userQuery = "SELECT id FROM users WHERE role = 'admin' AND status = 'active'";
    }
    
    $userStmt = $db->prepare($userQuery);
    $userStmt->execute();
    $users = $userStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Create notifications for all target users
    $notifQuery = "INSERT INTO notifications (user_id, type, title, content, link, created_at) 
                   VALUES (:user_id, 'announcement', :title, :content, :link, NOW())";
    $notifStmt = $db->prepare($notifQuery);
    
    foreach ($users as $user) {
        $notifStmt->bindParam(':user_id', $user['id']);
        $notifStmt->bindParam(':title', $data->title);
        $notifStmt->bindParam(':content', $data->content);
        $link = "/pages/student/announcements.html";
        $notifStmt->bindParam(':link', $link);
        $notifStmt->execute();
    }
    
    $db->commit();
    
    logActivity($admin['user_id'], 'send_announcement', "Sent announcement: {$data->title} to {$target_role}");
    
    echo json_encode([
        'success' => true,
        'message' => 'Announcement sent successfully',
        'announcement_id' => $announcement_id,
        'recipients' => count($users)
    ]);
    
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error' => 'Failed to send announcement: ' . $e->getMessage()]);
}
?>