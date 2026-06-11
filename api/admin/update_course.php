<?php
// ============================================
// UPDATE COURSE API
// ============================================

require_once '../config/database.php';

$admin = requireAdmin();

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->id) || !isset($data->title)) {
    http_response_code(400);
    echo json_encode(['error' => 'Course ID and title are required']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Check if course exists
$checkQuery = "SELECT id, title FROM courses WHERE id = :id";
$checkStmt = $db->prepare($checkQuery);
$checkStmt->bindParam(':id', $data->id);
$checkStmt->execute();

if ($checkStmt->rowCount() == 0) {
    http_response_code(404);
    echo json_encode(['error' => 'Course not found']);
    exit();
}

$oldTitle = $checkStmt->fetch(PDO::FETCH_ASSOC)['title'];

$query = "UPDATE courses SET 
          title = :title,
          description = :description,
          category = :category,
          level = :level,
          price = :price,
          duration_hours = :duration,
          status = :status,
          updated_at = NOW()
          WHERE id = :id";

$stmt = $db->prepare($query);
$stmt->bindParam(':id', $data->id);
$stmt->bindParam(':title', $data->title);
$description = $data->description ?? '';
$stmt->bindParam(':description', $description);
$category = $data->category ?? 'General';
$stmt->bindParam(':category', $category);
$level = $data->level ?? 'beginner';
$stmt->bindParam(':level', $level);
$price = $data->price ?? 0;
$stmt->bindParam(':price', $price);
$duration = $data->duration_hours ?? 0;
$stmt->bindParam(':duration', $duration);
$status = $data->status ?? 'draft';
$stmt->bindParam(':status', $status);

if ($stmt->execute()) {
    logActivity($admin['user_id'], 'course_update', "Updated course: {$oldTitle} → {$data->title}");
    
    echo json_encode([
        'success' => true,
        'message' => 'Course updated successfully'
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to update course']);
}
?>