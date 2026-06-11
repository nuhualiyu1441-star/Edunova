<?php
// ============================================
// DELETE COURSE API
// ============================================

require_once '../config/database.php';

$admin = requireAdmin();

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Course ID is required']);
    exit();
}

$course_id = $_GET['id'];

$database = new Database();
$db = $database->getConnection();

// Get course title for logging
$titleQuery = "SELECT title FROM courses WHERE id = :id";
$titleStmt = $db->prepare($titleQuery);
$titleStmt->bindParam(':id', $course_id);
$titleStmt->execute();
$courseTitle = $titleStmt->fetch(PDO::FETCH_ASSOC)['title'] ?? 'Unknown';

// Delete course (cascade will delete modules, lessons, enrollments)
$query = "DELETE FROM courses WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $course_id);

if ($stmt->execute()) {
    logActivity($admin['user_id'], 'course_delete', "Deleted course: {$courseTitle}");
    
    echo json_encode([
        'success' => true,
        'message' => 'Course deleted successfully'
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to delete course']);
}
?>