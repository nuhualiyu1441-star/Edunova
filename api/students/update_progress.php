<?php
// ============================================
// UPDATE LESSON PROGRESS API
// ============================================

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
$token = preg_replace('/Bearer\s/', '', $authHeader);

if (!$token) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if (!$data) {
    echo json_encode(['success' => false, 'error' => 'No data received']);
    exit();
}

$lesson_id = isset($data['lesson_id']) ? (int)$data['lesson_id'] : 0;
$course_id = isset($data['course_id']) ? (int)$data['course_id'] : 0;
$completed = isset($data['completed']) ? (bool)$data['completed'] : false;

if (!$lesson_id || !$course_id) {
    echo json_encode(['success' => false, 'error' => 'Lesson ID and Course ID are required']);
    exit();
}

// Calculate new progress (for demo)
$currentProgress = 50;
$newProgress = $completed ? min(100, $currentProgress + 25) : max(0, $currentProgress - 10);

echo json_encode([
    'success' => true,
    'message' => $completed ? '✅ Lesson marked as complete!' : 'Lesson progress updated',
    'progress' => $newProgress,
    'completed_lessons' => 8,
    'total_lessons' => 12,
    'lesson_completed' => $completed
]);
?>