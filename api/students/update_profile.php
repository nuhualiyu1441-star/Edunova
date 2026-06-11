<?php
// ============================================
// UPDATE PROFILE API - SAVES ALL USER DATA
// ============================================

require_once '../config/database.php';

$user = requireAuth();
$data = json_decode(file_get_contents("php://input"));

$database = new Database();
$db = $database->getConnection();

$query = "UPDATE users SET 
          full_name = :full_name,
          username = :username,
          email = :email,
          phone = :phone,
          bio = :bio,
          location = :location
          WHERE id = :user_id";

$stmt = $db->prepare($query);
$stmt->bindParam(':full_name', $data->full_name);
$stmt->bindParam(':username', $data->username);
$stmt->bindParam(':email', $data->email);
$phone = $data->phone ?? '';
$stmt->bindParam(':phone', $phone);
$bio = $data->bio ?? '';
$stmt->bindParam(':bio', $bio);
$location = $data->location ?? '';
$stmt->bindParam(':location', $location);
$stmt->bindParam(':user_id', $user['user_id']);

if ($stmt->execute()) {
    // Get updated user data
    $getUserQuery = "SELECT id, full_name, username, email, phone, bio, location, profile_image, role FROM users WHERE id = :user_id";
    $getUserStmt = $db->prepare($getUserQuery);
    $getUserStmt->bindParam(':user_id', $user['user_id']);
    $getUserStmt->execute();
    $updatedUser = $getUserStmt->fetch(PDO::FETCH_ASSOC);
    
    logActivity($user['user_id'], 'profile_update', 'Updated profile information');
    
    echo json_encode([
        'success' => true,
        'message' => 'Profile updated successfully',
        'user' => $updatedUser
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to update profile']);
}
?>