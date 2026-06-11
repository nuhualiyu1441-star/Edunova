<?php
// ============================================
// ADD COURSE API
// ============================================

require_once '../config/database.php';

$admin = requireAdmin();

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->title) || empty($data->title)) {
    http_response_code(400);
    echo json_encode(['error' => 'Course title is required']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Generate slug
$slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $data->title)));

// Check if slug exists
$checkQuery = "SELECT id FROM courses WHERE slug = :slug";
$checkStmt = $db->prepare($checkQuery);
$checkStmt->bindParam(':slug', $slug);
$checkStmt->execute();

if ($checkStmt->rowCount() > 0) {
    $slug = $slug . '-' . time();
}

$query = "INSERT INTO courses (title, slug, description, category, level, price, duration_hours, status, created_at) 
          VALUES (:title, :slug, :description, :category, :level, :price, :duration, :status, NOW())";

$stmt = $db->prepare($query);
$stmt->bindParam(':title', $data->title);
$stmt->bindParam(':slug', $slug);
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
    $course_id = $db->lastInsertId();
    
    logActivity($admin['user_id'], 'course_add', "Added course: {$data->title}");
    
    echo json_encode([
        'success' => true,
        'message' => 'Course added successfully',
        'course_id' => $course_id
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to add course']);
}
?>