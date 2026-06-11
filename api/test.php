<?php
// ============================================
// ROOT API TEST - QUICK CHECK
// ============================================
// Access at: http://localhost/edunova/api/test.php
// ============================================

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

$response = [
    'success' => true,
    'message' => 'Edunova API is working!',
    'server_time' => date('Y-m-d H:i:s'),
    'php_version' => phpversion(),
    'api_endpoints' => [
        'auth' => [
            'login' => '/api/auth/login.php',
            'register' => '/api/auth/register.php',
            'logout' => '/api/auth/logout.php',
            'test' => '/api/auth/test.php'
        ],
        'students' => [
            'dashboard' => '/api/students/dashboard.php',
            'courses' => '/api/students/get_courses.php',
            'course' => '/api/students/get_course.php',
            'progress' => '/api/students/update_progress.php',
            'profile' => '/api/students/update_profile.php',
            'upload' => '/api/students/upload_profile.php'
        ],
        'admin' => [
            'dashboard' => '/api/admin/dashboard.php',
            'users' => '/api/admin/get_users.php',
            'courses' => '/api/admin/get_courses.php',
            'quizzes' => '/api/admin/get_quizzes.php'
        ]
    ]
];

echo json_encode($response, JSON_PRETTY_PRINT);
?>