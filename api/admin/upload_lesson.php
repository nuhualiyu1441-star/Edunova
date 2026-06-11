<?php
// ============================================
// UPLOAD LESSON API (with YouTube support)
// ============================================

require_once '../config/database.php';
require_once '../helpers/youtube_helper.php';

$admin = requireAdmin();

$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"));
    
    if (!isset($data->module_id) || !isset($data->title)) {
        http_response_code(400);
        echo json_encode(['error' => 'Module ID and title are required']);
        exit();
    }
    
    // Validate YouTube URL if provided
    $youtube_url = null;
    if (isset($data->youtube_url) && !empty($data->youtube_url)) {
        $youtube_url = $data->youtube_url;
        $videoId = extractYouTubeId($youtube_url);
        if (!$videoId) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid YouTube URL']);
            exit();
        }
        $lesson_type = 'youtube';
    } else {
        $lesson_type = $data->lesson_type ?? 'text';
    }
    
    $query = "INSERT INTO lessons (module_id, title, content, lesson_type, duration_minutes, order_number, youtube_url, status, created_at) 
              VALUES (:module_id, :title, :content, :lesson_type, :duration, :order_number, :youtube_url, :status, NOW())";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':module_id', $data->module_id);
    $stmt->bindParam(':title', $data->title);
    $content = $data->content ?? '';
    $stmt->bindParam(':content', $content);
    $stmt->bindParam(':lesson_type', $lesson_type);
    $duration = $data->duration_minutes ?? 0;
    $stmt->bindParam(':duration', $duration);
    $order_number = $data->order_number ?? 0;
    $stmt->bindParam(':order_number', $order_number);
    $stmt->bindParam(':youtube_url', $youtube_url);
    $status = $data->status ?? 'draft';
    $stmt->bindParam(':status', $status);
    
    if ($stmt->execute()) {
        $lesson_id = $db->lastInsertId();
        
        logActivity($admin['user_id'], 'lesson_add', "Added lesson: {$data->title}");
        
        echo json_encode([
            'success' => true,
            'message' => 'Lesson uploaded successfully',
            'lesson_id' => $lesson_id
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to upload lesson']);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get modules for dropdown
    $course_id = isset($_GET['course_id']) ? $_GET['course_id'] : null;
    
    if ($course_id) {
        $query = "SELECT id, title FROM modules WHERE course_id = :course_id ORDER BY order_number";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':course_id', $course_id);
        $stmt->execute();
        $modules = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'modules' => $modules
        ]);
    } else {
        $query = "SELECT id, title FROM courses WHERE status = 'published' ORDER BY title";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'courses' => $courses
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>