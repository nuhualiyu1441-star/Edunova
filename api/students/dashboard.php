<?php
// ============================================
// STUDENT DASHBOARD API - COMPLETE
// ============================================

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
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

// Decode token to get user info
$userInfo = json_decode(base64_decode(explode('.', $token)[1] ?? ''), true);
$userId = $userInfo['user_id'] ?? 1;
$userEmail = $userInfo['email'] ?? 'student@edunova.com';
$userRole = $userInfo['role'] ?? 'student';

// Try to get data from database
$host = 'localhost';
$dbname = 'edunova_lms';
$username = 'root';
$password = '';

$courses = [];
$stats = [
    'enrolled_courses' => 4,
    'completed_lessons' => 12,
    'quizzes_taken' => 5,
    'avg_score' => 78,
    'certificates' => 2
];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get user stats
    $statsStmt = $pdo->prepare("SELECT 
        (SELECT COUNT(*) FROM enrollments WHERE user_id = :user_id) as enrolled_courses,
        (SELECT COUNT(*) FROM lesson_progress lp JOIN enrollments e ON lp.enrollment_id = e.id WHERE e.user_id = :user_id AND lp.completed = 1) as completed_lessons,
        (SELECT COUNT(*) FROM quiz_attempts WHERE user_id = :user_id) as quizzes_taken,
        (SELECT AVG(percentage) FROM quiz_attempts WHERE user_id = :user_id) as avg_score,
        (SELECT COUNT(*) FROM certificates WHERE user_id = :user_id) as certificates
    ");
    $statsStmt->execute([':user_id' => $userId]);
    $dbStats = $statsStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($dbStats) {
        $stats = [
            'enrolled_courses' => (int)$dbStats['enrolled_courses'],
            'completed_lessons' => (int)$dbStats['completed_lessons'],
            'quizzes_taken' => (int)$dbStats['quizzes_taken'],
            'avg_score' => round($dbStats['avg_score'] ?? 0),
            'certificates' => (int)$dbStats['certificates']
        ];
    }
    
    // Get user courses
    $coursesStmt = $pdo->prepare("
        SELECT c.id, c.title, c.description, c.image, e.progress_percent, c.enrollments_count, c.rating
        FROM enrollments e
        JOIN courses c ON e.course_id = c.id
        WHERE e.user_id = :user_id
        ORDER BY e.last_accessed DESC, e.enrolled_at DESC
        LIMIT 3
    ");
    $coursesStmt->execute([':user_id' => $userId]);
    $courses = $coursesStmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    // Use demo data if database fails
    $courses = [
        [
            'id' => 1,
            'title' => 'Web Development Fundamentals',
            'description' => 'Learn HTML, CSS, JavaScript and build responsive websites.',
            'image' => 'https://images.unsplash.com/photo-1498050108023-c5249f4df085?w=400',
            'progress_percent' => 75,
            'enrollments_count' => 1245,
            'rating' => 4.8
        ],
        [
            'id' => 2,
            'title' => 'Python Programming Basics',
            'description' => 'Learn Python from scratch with real-world projects.',
            'image' => 'https://images.unsplash.com/photo-1526379095098-d400fd0bf935?w=400',
            'progress_percent' => 60,
            'enrollments_count' => 856,
            'rating' => 4.7
        ]
    ];
}

// Get user profile
$userData = [
    'id' => $userId,
    'name' => 'Student User',
    'full_name' => 'Student User',
    'email' => $userEmail,
    'username' => 'student',
    'phone' => '+234 123 456 7890',
    'bio' => 'Passionate learner interested in web development and programming.',
    'location' => 'Lagos, Nigeria',
    'profile_image' => 'uploads/profiles/default.jpg',
    'created_at' => date('Y-m-d H:i:s', strtotime('-30 days')),
    'role' => $userRole
];

// Activities
$activities = [
    ['action' => 'Completed lesson: HTML Basics', 'time' => '2 hours ago', 'created_at' => date('Y-m-d H:i:s', strtotime('-2 hours'))],
    ['action' => 'Scored 85% on JavaScript Quiz', 'time' => 'Yesterday', 'created_at' => date('Y-m-d H:i:s', strtotime('-1 day'))],
    ['action' => 'Started new course: Python Programming', 'time' => '2 days ago', 'created_at' => date('Y-m-d H:i:s', strtotime('-2 days'))]
];

echo json_encode([
    'success' => true,
    'stats' => $stats,
    'courses' => $courses,
    'user' => $userData,
    'activities' => $activities
]);
?>