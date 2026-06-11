<?php
// ============================================
// TEST API ENDPOINT - AUTHENTICATION TEST
// ============================================
// This file tests if the authentication system is working
// Access at: http://localhost/edunova/api/auth/test.php
// ============================================

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Include database configuration
require_once '../config/database.php';

// Get request information
$request_method = $_SERVER['REQUEST_METHOD'];
$request_time = date('Y-m-d H:i:s');
$client_ip = $_SERVER['REMOTE_ADDR'];

// Check authentication if token is provided
$auth_status = 'not_authenticated';
$user_info = null;

$headers = getallheaders();
$auth_header = $headers['Authorization'] ?? $headers['authorization'] ?? '';

if (!empty($auth_header)) {
    $token = str_replace('Bearer ', '', $auth_header);
    $user = verifyJWT($token);
    
    if ($user) {
        $auth_status = 'authenticated';
        $user_info = [
            'user_id' => $user['user_id'],
            'email' => $user['email'],
            'role' => $user['role'],
            'name' => $user['name']
        ];
    } else {
        $auth_status = 'invalid_token';
    }
}

// Database connection test
$db_status = 'unknown';
$db_message = '';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db) {
        $db_status = 'connected';
        $db_message = 'Successfully connected to database';
        
        // Test query to check if tables exist
        $tables = [];
        $query = "SHOW TABLES";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $tables = $result;
        
        // Check if users table has data
        $userCount = 0;
        $userQuery = "SELECT COUNT(*) as count FROM users";
        $userStmt = $db->prepare($userQuery);
        $userStmt->execute();
        $userCount = $userStmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        $db_message = "Database connected. Found " . count($tables) . " tables. Users: " . $userCount;
    }
} catch (Exception $e) {
    $db_status = 'error';
    $db_message = $e->getMessage();
}

// PHP configuration info
$php_info = [
    'version' => phpversion(),
    'max_execution_time' => ini_get('max_execution_time'),
    'memory_limit' => ini_get('memory_limit'),
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size')
];

// Response data
$response = [
    'success' => true,
    'message' => 'API Test Endpoint - Authentication Test',
    'server_info' => [
        'request_method' => $request_method,
        'request_time' => $request_time,
        'client_ip' => $client_ip,
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
        'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown',
        'script_filename' => __FILE__
    ],
    'authentication' => [
        'status' => $auth_status,
        'has_token' => !empty($auth_header),
        'user' => $user_info
    ],
    'database' => [
        'status' => $db_status,
        'message' => $db_message
    ],
    'php_config' => $php_info,
    'instructions' => [
        'test_without_token' => 'Visit this URL without a token to see basic info',
        'test_with_token' => 'Add Authorization header: Bearer YOUR_TOKEN_HERE',
        'get_token' => 'Login at: http://localhost/edunova/pages/student/login.html',
        'test_endpoints' => [
            'login' => 'POST http://localhost/edunova/api/auth/login.php',
            'register' => 'POST http://localhost/edunova/api/auth/register.php',
            'dashboard' => 'GET http://localhost/edunova/api/students/dashboard.php',
            'courses' => 'GET http://localhost/edunova/api/students/get_courses.php'
        ]
    ]
];

// If there's an error, change status code
if ($db_status === 'error') {
    http_response_code(500);
    $response['success'] = false;
    $response['message'] = 'Database connection error';
}

echo json_encode($response, JSON_PRETTY_PRINT);
?>