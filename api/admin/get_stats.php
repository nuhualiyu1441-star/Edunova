<?php
// ============================================
// GET ADMIN STATS API
// ============================================

require_once '../config/database.php';

$admin = requireAdmin();

$database = new Database();
$db = $database->getConnection();

// Get daily active users
$dailyQuery = "SELECT COUNT(DISTINCT user_id) as count 
               FROM activity_logs 
               WHERE DATE(created_at) = CURDATE()";
$dailyStmt = $db->prepare($dailyQuery);
$dailyStmt->execute();
$dailyActive = $dailyStmt->fetch(PDO::FETCH_ASSOC)['count'];

// Get weekly active users
$weeklyQuery = "SELECT COUNT(DISTINCT user_id) as count 
                FROM activity_logs 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
$weeklyStmt = $db->prepare($weeklyQuery);
$weeklyStmt->execute();
$weeklyActive = $weeklyStmt->fetch(PDO::FETCH_ASSOC)['count'];

// Get monthly active users
$monthlyQuery = "SELECT COUNT(DISTINCT user_id) as count 
                 FROM activity_logs 
                 WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
$monthlyStmt = $db->prepare($monthlyQuery);
$monthlyStmt->execute();
$monthlyActive = $monthlyStmt->fetch(PDO::FETCH_ASSOC)['count'];

// Get system storage usage
$storageQuery = "SELECT 
                 (SELECT COUNT(*) FROM lessons WHERE video_url IS NOT NULL) as video_count,
                 (SELECT COUNT(*) FROM lessons WHERE file_path IS NOT NULL) as document_count,
                 (SELECT COUNT(*) FROM users WHERE profile_image IS NOT NULL) as profile_count";
$storageStmt = $db->prepare($storageQuery);
$storageStmt->execute();
$storage = $storageStmt->fetch(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'active_users' => [
        'daily' => (int)$dailyActive,
        'weekly' => (int)$weeklyActive,
        'monthly' => (int)$monthlyActive
    ],
    'storage' => $storage
]);
?>