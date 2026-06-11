<?php
// ============================================
// FACEBOOK LOGIN API
// ============================================

require_once '../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->facebook_id) || !isset($data->email) || !isset($data->name)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required Facebook user data']);
    exit();
}

$facebook_id = sanitizeInput($data->facebook_id);
$email = isset($data->email) ? sanitizeInput($data->email) : null;
$full_name = sanitizeInput($data->name);
$avatar = isset($data->avatar) ? sanitizeInput($data->avatar) : null;

$database = new Database();
$db = $database->getConnection();

// Check if user exists by facebook_id
$query = "SELECT id, full_name, email, role, status, profile_image FROM users WHERE facebook_id = :facebook_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':facebook_id', $facebook_id);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// If not found by facebook_id and email exists, check by email
if (!$user && $email) {
    $query = "SELECT id, full_name, email, role, status, profile_image FROM users WHERE email = :email";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Link Facebook ID to existing account
    if ($user) {
        $linkQuery = "UPDATE users SET facebook_id = :facebook_id WHERE id = :id";
        $linkStmt = $db->prepare($linkQuery);
        $linkStmt->bindParam(':facebook_id', $facebook_id);
        $linkStmt->bindParam(':id', $user['id']);
        $linkStmt->execute();
    }
}

if (!$user) {
    // Create new user
    $username = $email ? strtolower(explode('@', $email)[0]) : 'fb_user_' . substr($facebook_id, 0, 8);
    $base_username = $username;
    $counter = 1;
    
    // Ensure unique username
    while (true) {
        $checkQuery = "SELECT id FROM users WHERE username = :username";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->bindParam(':username', $username);
        $checkStmt->execute();
        if ($checkStmt->rowCount() === 0) break;
        $username = $base_username . $counter;
        $counter++;
    }
    
    $random_password = bin2hex(random_bytes(16));
    $hashed_password = password_hash($random_password, PASSWORD_DEFAULT);
    
    $insertQuery = "INSERT INTO users (full_name, username, email, password, facebook_id, profile_image, role, status, created_at) 
                    VALUES (:full_name, :username, :email, :password, :facebook_id, :profile_image, 'student', 'active', NOW())";
    $insertStmt = $db->prepare($insertQuery);
    $insertStmt->bindParam(':full_name', $full_name);
    $insertStmt->bindParam(':username', $username);
    $insertStmt->bindParam(':email', $email);
    $insertStmt->bindParam(':password', $hashed_password);
    $insertStmt->bindParam(':facebook_id', $facebook_id);
    $insertStmt->bindParam(':profile_image', $avatar);
    
    if ($insertStmt->execute()) {
        $user_id = $db->lastInsertId();
        $user = [
            'id' => $user_id,
            'full_name' => $full_name,
            'email' => $email,
            'role' => 'student',
            'status' => 'active',
            'profile_image' => $avatar
        ];
        logActivity($user_id, 'facebook_register', 'Registered via Facebook');
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create user account']);
        exit();
    }
}

// Check if user is active
if ($user['status'] !== 'active') {
    http_response_code(403);
    echo json_encode(['error' => 'Your account is not active. Please contact support.']);
    exit();
}

// Generate JWT token
$token = generateJWT($user['id'], $user['email'], $user['role'], $user['full_name']);

// Update last login
$updateQuery = "UPDATE users SET last_login = NOW() WHERE id = :id";
$updateStmt = $db->prepare($updateQuery);
$updateStmt->bindParam(':id', $user['id']);
$updateStmt->execute();

logActivity($user['id'], 'facebook_login', 'Logged in via Facebook');

echo json_encode([
    'success' => true,
    'message' => 'Facebook login successful',
    'token' => $token,
    'user' => [
        'id' => $user['id'],
        'name' => $user['full_name'],
        'email' => $user['email'],
        'role' => $user['role'],
        'profile_image' => $user['profile_image'] ?? null
    ]
]);
?>