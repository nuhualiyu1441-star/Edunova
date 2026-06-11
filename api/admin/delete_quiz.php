<?php
// ============================================
// DELETE QUIZ API
// ============================================

require_once '../config/database.php';

$admin = requireAdmin();

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Quiz ID is required']);
    exit();
}

$quiz_id = $_GET['id'];

$database = new Database();
$db = $database->getConnection();

// Get quiz title for logging
$titleQuery = "SELECT title FROM quizzes WHERE id = :id";
$titleStmt = $db->prepare($titleQuery);
$titleStmt->bindParam(':id', $quiz_id);
$titleStmt->execute();
$quizTitle = $titleStmt->fetch(PDO::FETCH_ASSOC)['title'] ?? 'Unknown';

// Delete quiz (cascade will delete questions and attempts)
$query = "DELETE FROM quizzes WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $quiz_id);

if ($stmt->execute()) {
    logActivity($admin['user_id'], 'quiz_delete', "Deleted quiz: {$quizTitle}");
    
    echo json_encode([
        'success' => true,
        'message' => 'Quiz deleted successfully'
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to delete quiz']);
}
?>