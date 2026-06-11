<?php
// ============================================
// GET USERS API (with pagination and filters)
// ============================================

require_once '../config/database.php';

$admin = requireAdmin();

$database = new Database();
$db = $database->getConnection();

// Get parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$role = isset($_GET['role']) ? $_GET['role'] : 'all';
$status = isset($_GET['status']) ? $_GET['status'] : 'all';
$search = isset($_GET['search']) ? $_GET['search'] : '';

$offset = ($page - 1) * $limit;

// Build query
$where = [];
$params = [];

if ($role !== 'all') {
    $where[] = "role = :role";
    $params[':role'] = $role;
}

if ($status !== 'all') {
    $where[] = "status = :status";
    $params[':status'] = $status;
}

if (!empty($search)) {
    $where[] = "(full_name LIKE :search OR email LIKE :search OR username LIKE :search)";
    $params[':search'] = "%$search%";
}

$whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// Get total count
$countQuery = "SELECT COUNT(*) as total FROM users $whereClause";
$countStmt = $db->prepare($countQuery);
foreach ($params as $key => $value) {
    $countStmt->bindValue($key, $value);
}
$countStmt->execute();
$total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

// Get users
$query = "SELECT id, full_name, username, email, phone, role, status, profile_image, created_at, last_login 
          FROM users $whereClause 
          ORDER BY created_at DESC 
          LIMIT :limit OFFSET :offset";
$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'users' => $users,
    'pagination' => [
        'current_page' => $page,
        'per_page' => $limit,
        'total' => $total,
        'total_pages' => ceil($total / $limit)
    ]
]);
?>