<?php
// ============================================
// GET QUIZZES API (Admin)
// ============================================

require_once '../config/database.php';

$admin = requireAdmin();

$database = new Database();
$db = $database->getConnection();

// Get parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$status = isset($_GET['status']) ? $_GET['status'] : 'all';
$search = isset($_GET['search']) ? $_GET['search'] : '';

$offset = ($page - 1) * $limit;

// Build query
$where = [];
$params = [];

if ($status !== 'all') {
    $where[] = "q.status = :status";
    $params[':status'] = $status;
}

if (!empty($search)) {
    $where[] = "(q.title LIKE :search OR c.title LIKE :search)";
    $params[':search'] = "%$search%";
}

$whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// Get total count
$countQuery = "SELECT COUNT(*) as total FROM quizzes q $whereClause";
$countStmt = $db->prepare($countQuery);
foreach ($params as $key => $value) {
    $countStmt->bindValue($key, $value);
}
$countStmt->execute();
$total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

// Get quizzes
$query = "SELECT q.*, c.title as course_title,
          (SELECT COUNT(*) FROM quiz_questions WHERE quiz_id = q.id) as total_questions,
          (SELECT COUNT(*) FROM quiz_attempts WHERE quiz_id = q.id) as total_attempts
          FROM quizzes q
          LEFT JOIN courses c ON q.course_id = c.id
          $whereClause 
          ORDER BY q.created_at DESC 
          LIMIT :limit OFFSET :offset";
$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'quizzes' => $quizzes,
    'pagination' => [
        'current_page' => $page,
        'per_page' => $limit,
        'total' => $total,
        'total_pages' => ceil($total / $limit)
    ]
]);
?>