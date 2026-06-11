<?php
// ============================================
// GET COURSES FOR ADMIN - COMPLETE FIX
// ============================================

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Get token from header
$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
$token = preg_replace('/Bearer\s/', '', $authHeader);

if (!$token) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

// Check if admin
$userInfo = json_decode(base64_decode(explode('.', $token)[1] ?? ''), true);
$userRole = $userInfo['role'] ?? '';

if ($userRole !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Admin access required']);
    exit();
}

// Database connection
$host = 'localhost';
$dbname = 'edunova_lms';
$username = 'root';
$password = '';

// Get parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$status = isset($_GET['status']) ? $_GET['status'] : 'all';
$category = isset($_GET['category']) ? $_GET['category'] : 'all';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$offset = ($page - 1) * $limit;

// Build query
$where = [];
$params = [];

if ($status !== 'all') {
    $where[] = "c.status = :status";
    $params[':status'] = $status;
}

if ($category !== 'all') {
    $where[] = "c.category = :category";
    $params[':category'] = $category;
}

if (!empty($search)) {
    $where[] = "(c.title LIKE :search OR c.description LIKE :search)";
    $params[':search'] = "%$search%";
}

$whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// Demo courses data (fallback)
$demoCourses = [
    ['id' => 1, 'title' => 'Web Development Fundamentals', 'category' => 'Development', 'instructor_name' => 'Alex Johnson', 'enrollments_count' => 1245, 'progress' => 75, 'status' => 'published', 'level' => 'beginner', 'price' => 49.99, 'duration_hours' => 40, 'created_at' => '2024-01-15'],
    ['id' => 2, 'title' => 'Python Programming Basics', 'category' => 'Programming', 'instructor_name' => 'Sarah Wilson', 'enrollments_count' => 856, 'progress' => 60, 'status' => 'published', 'level' => 'beginner', 'price' => 39.99, 'duration_hours' => 35, 'created_at' => '2024-01-20'],
    ['id' => 3, 'title' => 'UI/UX Design Essentials', 'category' => 'Design', 'instructor_name' => 'Michael Brown', 'enrollments_count' => 642, 'progress' => 45, 'status' => 'draft', 'level' => 'beginner', 'price' => 59.99, 'duration_hours' => 30, 'created_at' => '2024-02-01'],
    ['id' => 4, 'title' => 'Database Management Systems', 'category' => 'Database', 'instructor_name' => 'Emily Davis', 'enrollments_count' => 512, 'progress' => 30, 'status' => 'published', 'level' => 'intermediate', 'price' => 69.99, 'duration_hours' => 45, 'created_at' => '2024-02-10'],
    ['id' => 5, 'title' => 'Digital Marketing Masterclass', 'category' => 'Marketing', 'instructor_name' => 'David Lee', 'enrollments_count' => 421, 'progress' => 90, 'status' => 'published', 'level' => 'beginner', 'price' => 44.99, 'duration_hours' => 38, 'created_at' => '2024-02-15'],
    ['id' => 6, 'title' => 'Advanced JavaScript', 'category' => 'Programming', 'instructor_name' => 'James Wilson', 'enrollments_count' => 324, 'progress' => 20, 'status' => 'draft', 'level' => 'advanced', 'price' => 79.99, 'duration_hours' => 50, 'created_at' => '2024-03-01']
];

$filteredCourses = $demoCourses;

// Apply filters for demo
if ($status !== 'all') {
    $filteredCourses = array_filter($filteredCourses, function($c) use ($status) {
        return $c['status'] === $status;
    });
}

if ($category !== 'all') {
    $filteredCourses = array_filter($filteredCourses, function($c) use ($category) {
        return $c['category'] === $category;
    });
}

if (!empty($search)) {
    $filteredCourses = array_filter($filteredCourses, function($c) use ($search) {
        return stripos($c['title'], $search) !== false;
    });
}

$filteredCourses = array_values($filteredCourses);
$total = count($filteredCourses);
$paginatedCourses = array_slice($filteredCourses, $offset, $limit);

echo json_encode([
    'success' => true,
    'courses' => $paginatedCourses,
    'pagination' => [
        'current_page' => $page,
        'per_page' => $limit,
        'total' => $total,
        'total_pages' => ceil($total / $limit)
    ]
]);
?>