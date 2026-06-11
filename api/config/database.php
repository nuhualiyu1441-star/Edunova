<?php
// ============================================
// DATABASE CONFIGURATION WITH ERROR LOGGING
// ============================================

// Enable error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't show errors to users
ini_set('log_errors', 1);     // Log errors to file
ini_set('error_log', dirname(__DIR__, 2) . '/logs/error.log');

// Set timezone
date_default_timezone_set('Africa/Lagos');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Custom error handler
function customErrorHandler($errno, $errstr, $errfile, $errline) {
    $error_message = date('Y-m-d H:i:s') . " - Error [$errno] $errstr in $errfile on line $errline\n";
    error_log($error_message, 3, dirname(__DIR__, 2) . '/logs/error.log');
    
    // Don't display errors to users in production
    if (ini_get('display_errors') == 0) {
        return true;
    }
    return false;
}

// Custom exception handler
function customExceptionHandler($exception) {
    $error_message = date('Y-m-d H:i:s') . " - Exception: " . $exception->getMessage() . 
                     " in " . $exception->getFile() . " on line " . $exception->getLine() . "\n";
    error_log($error_message, 3, dirname(__DIR__, 2) . '/logs/error.log');
    
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}

// Set custom handlers
set_error_handler('customErrorHandler');
set_exception_handler('customExceptionHandler');

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'edunova_lms');
define('DB_USER', 'root');
define('DB_PASS', '');
define('JWT_SECRET', 'edunova-secret-key-2024');
define('UPLOAD_PATH', dirname(__DIR__, 2) . '/uploads/');

// Database connection class
class Database {
    private static $instance = null;
    private $conn;
    
    public function __construct() {
        try {
            $this->conn = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->conn->exec("set names utf8mb4");
        } catch(PDOException $exception) {
            $error_message = date('Y-m-d H:i:s') . " - Database Error: " . $exception->getMessage() . "\n";
            error_log($error_message, 3, dirname(__DIR__, 2) . '/logs/error.log');
            throw $exception;
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->conn;
    }
}

// Global database connection function
function getDB() {
    return Database::getInstance()->getConnection();
}

// ============================================
// JWT FUNCTIONS
// ============================================
function generateJWT($user_id, $email, $role, $name) {
    $header = base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
    $payload = base64_encode(json_encode([
        'user_id' => $user_id,
        'email' => $email,
        'role' => $role,
        'name' => $name,
        'iat' => time(),
        'exp' => time() + (7 * 24 * 60 * 60)
    ]));
    $signature = hash_hmac('sha256', "$header.$payload", JWT_SECRET);
    return "$header.$payload.$signature";
}

function verifyJWT($token) {
    $parts = explode('.', $token);
    if (count($parts) != 3) return false;
    
    $signature = hash_hmac('sha256', "$parts[0].$parts[1]", JWT_SECRET);
    if (!hash_equals($signature, $parts[2])) return false;
    
    $payload = json_decode(base64_decode($parts[1]), true);
    if ($payload['exp'] < time()) return false;
    
    return $payload;
}

function getBearerToken() {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    
    if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        return $matches[1];
    }
    return null;
}

function isAuthenticated() {
    $token = getBearerToken();
    if (!$token) return false;
    return verifyJWT($token);
}

function requireAuth() {
    $user = isAuthenticated();
    if (!$user) {
        error_log(date('Y-m-d H:i:s') . " - Unauthorized access attempt\n", 3, dirname(__DIR__, 2) . '/logs/error.log');
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit();
    }
    return $user;
}

function requireAdmin() {
    $user = requireAuth();
    if ($user['role'] !== 'admin') {
        error_log(date('Y-m-d H:i:s') . " - Forbidden admin access attempt by user: {$user['user_id']}\n", 3, dirname(__DIR__, 2) . '/logs/error.log');
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden: Admin access required']);
        exit();
    }
    return $user;
}

// ============================================
// TOKEN VALIDATION FUNCTIONS (FIXED)
// ============================================

function validateToken($token) {
    if (empty($token)) {
        return false;
    }
    
    try {
        // Try JWT validation first
        $jwtPayload = verifyJWT($token);
        if ($jwtPayload) {
            return $jwtPayload['user_id'];
        }
        
        // Fallback: Check for demo/admin tokens
        if (strpos($token, 'admin-demo-token') !== false || strpos($token, 'demo-token') !== false) {
            return 1; // Return admin user ID for demo
        }
        
        // Check in database if you have a sessions table
        $db = getDB();
        $query = "SELECT user_id FROM user_sessions WHERE token = :token AND expires_at > NOW()";
        $stmt = $db->prepare($query);
        $stmt->execute([':token' => $token]);
        
        if ($stmt->rowCount() > 0) {
            $session = $stmt->fetch(PDO::FETCH_ASSOC);
            return $session['user_id'];
        }
        
        return false;
    } catch (Exception $e) {
        error_log("validateToken error: " . $e->getMessage());
        return false;
    }
}

function isAdmin($userId) {
    try {
        $db = getDB();
        
        $query = "SELECT role FROM users WHERE id = :id AND status = 'active'";
        $stmt = $db->prepare($query);
        $stmt->execute([':id' => $userId]);
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            return $user['role'] === 'admin';
        }
        
        // Fallback for demo mode
        if ($userId == 1) {
            return true;
        }
        
        return false;
    } catch (Exception $e) {
        error_log("isAdmin error: " . $e->getMessage());
        return false;
    }
}

function simpleValidateToken($token) {
    // For testing purposes
    if (empty($token)) {
        return false;
    }
    
    // Check for admin demo token
    if (strpos($token, 'admin-demo-token') !== false) {
        return 1; // Admin user ID
    }
    
    // Check for regular demo token
    if (strpos($token, 'demo-token') !== false) {
        return 2; // Regular user ID
    }
    
    return false;
}

// ============================================
// HELPER FUNCTIONS
// ============================================
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function logActivity($user_id, $action, $description) {
    try {
        $db = getDB();
        
        $query = "INSERT INTO activity_logs (user_id, action, description, ip_address, user_agent) 
                  VALUES (:user_id, :action, :description, :ip, :agent)";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':action', $action);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':ip', $_SERVER['REMOTE_ADDR']);
        $agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $stmt->bindParam(':agent', $agent);
        $stmt->execute();
    } catch (Exception $e) {
        error_log(date('Y-m-d H:i:s') . " - Failed to log activity: " . $e->getMessage() . "\n", 3, dirname(__DIR__, 2) . '/logs/error.log');
    }
}

function generateSlug($string) {
    $string = strtolower($string);
    $string = preg_replace('/[^a-z0-9-]/', '-', $string);
    $string = preg_replace('/-+/', '-', $string);
    return trim($string, '-');
}

// ============================================
// RESPONSE HELPERS
// ============================================

function sendSuccess($data = [], $message = 'Success') {
    echo json_encode([
        'success' => true,
        'message' => $message,
        'data' => $data
    ]);
    exit();
}

function sendError($message = 'Error occurred', $code = 400) {
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'error' => $message
    ]);
    exit();
}

function sendResponse($success, $data = [], $message = '') {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        ...$data
    ]);
    exit();
}

// Create logs directory if it doesn't exist
$logDir = dirname(__DIR__, 2) . '/logs';
if (!file_exists($logDir)) {
    mkdir($logDir, 0777, true);
}
?>