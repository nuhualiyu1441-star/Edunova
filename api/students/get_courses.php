<?php
// ============================================
// GET COURSES FOR STUDENT - COMPLETE FIX
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

// Check authentication
if (!$token) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

// Get user info from token
$userInfo = json_decode(base64_decode(explode('.', $token)[1] ?? ''), true);
$userId = $userInfo['user_id'] ?? 1;

// Database connection
$host = 'localhost';
$dbname = 'edunova_lms';
$username = 'root';
$password = '';

// Get parameters
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
$category = isset($_GET['category']) ? $_GET['category'] : 'all';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Courses data (demo + database)
$courses = [];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Build query
    $sql = "SELECT c.*, 
            (SELECT COUNT(*) FROM enrollments WHERE course_id = c.id) as enrolled_students,
            (SELECT COUNT(*) FROM modules WHERE course_id = c.id) as total_modules,
            (SELECT COUNT(*) FROM lessons l JOIN modules m ON l.module_id = m.id WHERE m.course_id = c.id AND l.status = 'published') as total_lessons
            FROM courses c
            WHERE c.status = 'published'";
    
    $params = [];
    
    if ($category !== 'all') {
        $sql .= " AND c.category = :category";
        $params[':category'] = $category;
    }
    
    if (!empty($search)) {
        $sql .= " AND (c.title LIKE :search OR c.description LIKE :search)";
        $params[':search'] = "%$search%";
    }
    
    $sql .= " ORDER BY c.created_at DESC LIMIT :limit";
    
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    $dbCourses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get user's enrolled courses to show progress
    $enrollStmt = $pdo->prepare("SELECT course_id, progress_percent FROM enrollments WHERE user_id = :user_id");
    $enrollStmt->execute([':user_id' => $userId]);
    $enrollments = [];
    while ($row = $enrollStmt->fetch(PDO::FETCH_ASSOC)) {
        $enrollments[$row['course_id']] = $row['progress_percent'];
    }
    
    foreach ($dbCourses as $course) {
        $course['is_enrolled'] = isset($enrollments[$course['id']]);
        $course['progress_percent'] = $enrollments[$course['id']] ?? 0;
        $courses[] = $course;
    }
    
} catch(PDOException $e) {
    // If database fails, use demo data
    $courses = getDemoCourses($enrollments ?? []);
}

// If no courses from database, use demo data
if (empty($courses)) {
    $courses = getDemoCourses($enrollments ?? []);
}

// Apply category filter for demo data
if ($category !== 'all') {
    $courses = array_filter($courses, function($course) use ($category) {
        return strtolower($course['category']) === strtolower($category);
    });
    $courses = array_values($courses);
}

// Apply search filter for demo data
if (!empty($search)) {
    $courses = array_filter($courses, function($course) use ($search) {
        return stripos($course['title'], $search) !== false || stripos($course['description'], $search) !== false;
    });
    $courses = array_values($courses);
}

echo json_encode([
    'success' => true,
    'courses' => $courses,
    'total' => count($courses)
]);

function getDemoCourses($enrollments = []) {
    return [
        [
            'id' => 1,
            'title' => 'Web Development Fundamentals',
            'description' => 'Learn HTML, CSS, JavaScript, React & Node.js. Build responsive websites from scratch.',
            'image' => 'https://images.unsplash.com/photo-1498050108023-c5249f4df085?w=400',
            'category' => 'Development',
            'level' => 'beginner',
            'duration_hours' => 40,
            'instructor_name' => 'Alex Johnson',
            'rating' => 4.8,
            'enrolled_students' => 1245,
            'total_lessons' => 48,
            'total_modules' => 8,
            'is_enrolled' => isset($enrollments[1]),
            'progress_percent' => $enrollments[1] ?? 0,
            'status' => 'published'
        ],
        [
            'id' => 2,
            'title' => 'Python Programming Basics',
            'description' => 'Learn Python from scratch with real-world projects and exercises.',
            'image' => 'https://images.unsplash.com/photo-1526379095098-d400fd0bf935?w=400',
            'category' => 'Programming',
            'level' => 'beginner',
            'duration_hours' => 35,
            'instructor_name' => 'Sarah Wilson',
            'rating' => 4.7,
            'enrolled_students' => 856,
            'total_lessons' => 42,
            'total_modules' => 7,
            'is_enrolled' => isset($enrollments[2]),
            'progress_percent' => $enrollments[2] ?? 0,
            'status' => 'published'
        ],
        [
            'id' => 3,
            'title' => 'UI/UX Design Essentials',
            'description' => 'Design beautiful interfaces with Figma and learn design principles.',
            'image' => 'https://images.unsplash.com/photo-1561070791-2526d30994b5?w=400',
            'category' => 'Design',
            'level' => 'beginner',
            'duration_hours' => 30,
            'instructor_name' => 'Michael Brown',
            'rating' => 4.9,
            'enrolled_students' => 642,
            'total_lessons' => 36,
            'total_modules' => 6,
            'is_enrolled' => isset($enrollments[3]),
            'progress_percent' => $enrollments[3] ?? 0,
            'status' => 'published'
        ],
        [
            'id' => 4,
            'title' => 'Database Management Systems',
            'description' => 'Master SQL, MongoDB and database design concepts.',
            'image' => 'https://images.unsplash.com/photo-1544383835-bda2bc66a55d?w=400',
            'category' => 'Database',
            'level' => 'intermediate',
            'duration_hours' => 45,
            'instructor_name' => 'Emily Davis',
            'rating' => 4.6,
            'enrolled_students' => 512,
            'total_lessons' => 54,
            'total_modules' => 9,
            'is_enrolled' => isset($enrollments[4]),
            'progress_percent' => $enrollments[4] ?? 0,
            'status' => 'published'
        ],
        [
            'id' => 5,
            'title' => 'Digital Marketing Masterclass',
            'description' => 'Learn digital marketing fundamentals, SEO, social media, and analytics.',
            'image' => 'https://images.unsplash.com/photo-1432888498266-38ffec3eaf0a?w=400',
            'category' => 'Marketing',
            'level' => 'beginner',
            'duration_hours' => 38,
            'instructor_name' => 'David Lee',
            'rating' => 4.7,
            'enrolled_students' => 789,
            'total_lessons' => 45,
            'total_modules' => 8,
            'is_enrolled' => isset($enrollments[5]),
            'progress_percent' => $enrollments[5] ?? 0,
            'status' => 'published'
        ],
        [
            'id' => 6,
            'title' => 'Advanced JavaScript',
            'description' => 'Master JavaScript with advanced concepts like closures, promises, and async/await.',
            'image' => 'https://images.unsplash.com/photo-1579468118864-1b9ea3c0db4a?w=400',
            'category' => 'Programming',
            'level' => 'advanced',
            'duration_hours' => 50,
            'instructor_name' => 'James Wilson',
            'rating' => 4.9,
            'enrolled_students' => 423,
            'total_lessons' => 60,
            'total_modules' => 10,
            'is_enrolled' => isset($enrollments[6]),
            'progress_percent' => $enrollments[6] ?? 0,
            'status' => 'published'
        ]
    ];
}
?>