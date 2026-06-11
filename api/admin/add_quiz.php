<?php
// ============================================
// ADD QUIZ API
// ============================================

require_once '../config/database.php';

$admin = requireAdmin();

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->title) || empty($data->title)) {
    http_response_code(400);
    echo json_encode(['error' => 'Quiz title is required']);
    exit();
}

if (!isset($data->course_id) || empty($data->course_id)) {
    http_response_code(400);
    echo json_encode(['error' => 'Course ID is required']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

$query = "INSERT INTO quizzes (course_id, module_id, title, description, time_limit_minutes, passing_score, status, created_at) 
          VALUES (:course_id, :module_id, :title, :description, :time_limit, :passing_score, :status, NOW())";

$stmt = $db->prepare($query);
$stmt->bindParam(':course_id', $data->course_id);
$module_id = $data->module_id ?? null;
$stmt->bindParam(':module_id', $module_id);
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
    $quiz_id = $db->lastInsertId();
    
    logActivity($admin['user_id'], 'quiz_add', "Added quiz: {$data->title}");
    
    echo json_encode([
        'success' => true,
        'message' => 'Quiz added successfully',
        'quiz_id' => $quiz_id
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to add quiz']);
}
?>