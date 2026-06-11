<?php
// ============================================
// GET SINGLE COURSE DETAILS
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

$course_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$course_id) {
    echo json_encode(['success' => false, 'error' => 'Course ID required']);
    exit();
}

// Course data
$courses = [
    1 => [
        'id' => 1,
        'title' => 'Web Development Fundamentals',
        'description' => 'Learn HTML, CSS, JavaScript, React & Node.js. Build responsive websites from scratch.',
        'image' => 'https://images.unsplash.com/photo-1498050108023-c5249f4df085?w=400',
        'progress_percent' => 75,
        'enrollments_count' => 1245,
        'rating' => 4.8,
        'category' => 'Development',
        'level' => 'beginner',
        'duration_hours' => 40,
        'instructor_name' => 'Alex Johnson',
        'modules' => [
            [
                'id' => 1,
                'title' => 'Introduction to Web Development',
                'order_number' => 1,
                'lessons' => [
                    ['id' => 1, 'title' => 'What is Web Development?', 'duration_minutes' => 15, 'is_completed' => true, 'youtube_url' => 'https://www.youtube.com/watch?v=zJSY8tbf_ys', 'content' => 'Learn what web development is and how websites work.'],
                    ['id' => 2, 'title' => 'How the Web Works', 'duration_minutes' => 20, 'is_completed' => true, 'youtube_url' => 'https://www.youtube.com/watch?v=dh406O2v_1c', 'content' => 'Understanding HTTP, servers, and browsers.'],
                    ['id' => 3, 'title' => 'Setting Up Your Environment', 'duration_minutes' => 25, 'is_completed' => false, 'youtube_url' => 'https://www.youtube.com/watch?v=WPqXP_kLzpo', 'content' => 'Install VS Code and necessary tools.']
                ]
            ],
            [
                'id' => 2,
                'title' => 'HTML Basics',
                'order_number' => 2,
                'lessons' => [
                    ['id' => 4, 'title' => 'HTML Introduction', 'duration_minutes' => 18, 'is_completed' => false, 'youtube_url' => 'https://www.youtube.com/watch?v=qz0aGYrrlhU', 'content' => 'Basic HTML structure and tags.'],
                    ['id' => 5, 'title' => 'HTML Forms', 'duration_minutes' => 22, 'is_completed' => false, 'youtube_url' => 'https://www.youtube.com/watch?v=fNcJuPIZ2WE', 'content' => 'Creating forms in HTML.']
                ]
            ]
        ]
    ],
    2 => [
        'id' => 2,
        'title' => 'Python Programming Basics',
        'description' => 'Learn Python from scratch with real-world projects and exercises.',
        'image' => 'https://images.unsplash.com/photo-1526379095098-d400fd0bf935?w=400',
        'progress_percent' => 60,
        'enrollments_count' => 856,
        'rating' => 4.7,
        'category' => 'Programming',
        'level' => 'beginner',
        'duration_hours' => 35,
        'instructor_name' => 'Sarah Wilson',
        'modules' => [
            [
                'id' => 3,
                'title' => 'Python Introduction',
                'order_number' => 1,
                'lessons' => [
                    ['id' => 6, 'title' => 'What is Python?', 'duration_minutes' => 12, 'is_completed' => true, 'content' => 'Introduction to Python programming language.'],
                    ['id' => 7, 'title' => 'Installing Python', 'duration_minutes' => 15, 'is_completed' => false, 'content' => 'Installing Python and setting up environment.']
                ]
            ]
        ]
    ]
];

$course = isset($courses[$course_id]) ? $courses[$course_id] : null;

if (!$course) {
    echo json_encode(['success' => false, 'error' => 'Course not found']);
    exit();
}

echo json_encode([
    'success' => true,
    'course' => $course
]);
?>