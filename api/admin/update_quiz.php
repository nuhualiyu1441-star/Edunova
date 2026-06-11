<?php
// ============================================
// UPDATE QUIZ API
// ============================================

require_once '../config/database.php';

$admin = requireAdmin();

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->id)) {
    http_response_code(400);
    echo json_encode(['error' => 'Quiz ID is required']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Check if quiz exists
$checkQuery = "SELECT id, title FROM quizzes WHERE id = :id";
$checkStmt = $db->prepare($checkQuery);
$checkStmt->bindParam(':id', $data->id);
$checkStmt->execute();

if ($checkStmt->rowCount() == 0) {
    http_response_code(404);
    echo json_encode(['error' => 'Quiz not found']);
    exit();
}

$oldTitle = $checkStmt->fetch(PDO::FETCH_ASSOC)['title'];

$query = "UPDATE quizzes SET 
          title = :title,
          description = :description,
          time_limit_minutes = :time_limit,
          passing_score = :passing_score,
          status = :status,
          updated_at = NOW()
          WHERE id = :id";

$stmt = $db->prepare($query);
$stmt->bindParam(':id', $data->id);
$stmt->bindParam(':title', $data->title);
$description = $data->description ?? '';
$stmt->bindParam(':description', $description);
$time_limit = $data->time_limit_minutes ?? 30;
$stmt->bindParam(':time_limit', $time_limit);
$passing_score = $data->passing_score ?? 70;
$stmt->bindParam(':passing_score', $passing_score);
$status = $data->status ?? 'draft';
$stmt->bindParam(':status', $status);

if ($stmt->execute()) {
    logActivity($admin['user_id'], 'quiz_update', "Updated quiz: {$oldTitle} → {$data->title}");
    
    echo json_encode([
        'success' => true,
        'message' => 'Quiz updated successfully'
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to update quiz']);
}
?>