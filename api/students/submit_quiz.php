<?php
// ============================================
// SUBMIT QUIZ API - COMPLETE FIX
// ============================================

require_once '../config/database.php';

// Set headers for JSON response
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow POST method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed. Use POST.']);
    exit();
}

// Authenticate user
$user = requireAuth();

// Get input data
$input_data = file_get_contents("php://input");
$data = json_decode($input_data);

// Check if data is valid JSON
if ($data === null) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON data']);
    exit();
}

// Validate required fields
if (!isset($data->quiz_id)) {
    http_response_code(400);
    echo json_encode(['error' => 'Quiz ID is required']);
    exit();
}

if (!isset($data->answers) || !is_object($data->answers)) {
    http_response_code(400);
    echo json_encode(['error' => 'Answers are required and must be an object']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

try {
    // Start transaction
    $db->beginTransaction();
    
    // Get quiz details
    $quizQuery = "SELECT q.*, 
                  (SELECT COUNT(*) FROM quiz_questions WHERE quiz_id = q.id) as total_questions,
                  (SELECT SUM(points) FROM quiz_questions WHERE quiz_id = q.id) as total_points
                  FROM quizzes q 
                  WHERE q.id = :quiz_id AND q.status = 'published'";
    $quizStmt = $db->prepare($quizQuery);
    $quizStmt->bindParam(':quiz_id', $data->quiz_id);
    $quizStmt->execute();
    $quiz = $quizStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$quiz) {
        http_response_code(404);
        echo json_encode(['error' => 'Quiz not found or not published']);
        exit();
    }
    
    // Check if user has already taken this quiz
    $checkAttemptQuery = "SELECT id, score, percentage, passed FROM quiz_attempts 
                          WHERE user_id = :user_id AND quiz_id = :quiz_id 
                          ORDER BY id DESC LIMIT 1";
    $checkAttemptStmt = $db->prepare($checkAttemptQuery);
    $checkAttemptStmt->bindParam(':user_id', $user['user_id']);
    $checkAttemptStmt->bindParam(':quiz_id', $data->quiz_id);
    $checkAttemptStmt->execute();
    $previousAttempt = $checkAttemptStmt->fetch(PDO::FETCH_ASSOC);
    
    // Calculate score
    $score = 0;
    $max_possible_score = $quiz['total_points'];
    $answers_evaluated = 0;
    $correct_answers = 0;
    
    foreach ($data->answers as $question_id => $answer) {
        // Get question details
        $questionQuery = "SELECT id, correct_answer, points FROM quiz_questions WHERE id = :id AND quiz_id = :quiz_id";
        $questionStmt = $db->prepare($questionQuery);
        $questionStmt->bindParam(':id', $question_id);
        $questionStmt->bindParam(':quiz_id', $data->quiz_id);
        $questionStmt->execute();
        $question = $questionStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($question) {
            $answers_evaluated++;
            $is_correct = ($question['correct_answer'] === $answer);
            
            if ($is_correct) {
                $score += $question['points'];
                $correct_answers++;
            }
        }
    }
    
    // Calculate percentage
    $percentage = $max_possible_score > 0 ? ($score / $max_possible_score) * 100 : 0;
    $passed = $percentage >= ($quiz['passing_score'] ?? 70);
    
    // Save attempt
    $attemptQuery = "INSERT INTO quiz_attempts (user_id, quiz_id, score, total_points, percentage, passed, completed_at) 
                     VALUES (:user_id, :quiz_id, :score, :total_points, :percentage, :passed, NOW())";
    $attemptStmt = $db->prepare($attemptQuery);
    $attemptStmt->bindParam(':user_id', $user['user_id']);
    $attemptStmt->bindParam(':quiz_id', $data->quiz_id);
    $attemptStmt->bindParam(':score', $score);
    $attemptStmt->bindParam(':total_points', $max_possible_score);
    $attemptStmt->bindParam(':percentage', $percentage);
    $attemptStmt->bindParam(':passed', $passed);
    $attemptStmt->execute();
    
    $attempt_id = $db->lastInsertId();
    
    // Save individual answers
    foreach ($data->answers as $question_id => $answer) {
        // Verify question belongs to this quiz
        $verifyQuery = "SELECT id, correct_answer FROM quiz_questions WHERE id = :id AND quiz_id = :quiz_id";
        $verifyStmt = $db->prepare($verifyQuery);
        $verifyStmt->bindParam(':id', $question_id);
        $verifyStmt->bindParam(':quiz_id', $data->quiz_id);
        $verifyStmt->execute();
        $question = $verifyStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($question) {
            $is_correct = ($question['correct_answer'] === $answer) ? 1 : 0;
            
            $answerQuery = "INSERT INTO quiz_answers (attempt_id, question_id, selected_answer, is_correct) 
                            VALUES (:attempt_id, :question_id, :answer, :is_correct)";
            $answerStmt = $db->prepare($answerQuery);
            $answerStmt->bindParam(':attempt_id', $attempt_id);
            $answerStmt->bindParam(':question_id', $question_id);
            $answerStmt->bindParam(':answer', $answer);
            $answerStmt->bindParam(':is_correct', $is_correct);
            $answerStmt->execute();
        }
    }
    
    // If passed, check if certificate should be awarded
    if ($passed) {
        // Check if certificate already exists
        $certCheckQuery = "SELECT id FROM certificates WHERE user_id = :user_id AND course_id = :course_id";
        $certCheckStmt = $db->prepare($certCheckQuery);
        $certCheckStmt->bindParam(':user_id', $user['user_id']);
        $certCheckStmt->bindParam(':course_id', $quiz['course_id']);
        $certCheckStmt->execute();
        
        if ($certCheckStmt->rowCount() == 0) {
            // Generate certificate code
            $certificate_code = 'CERT-' . strtoupper(uniqid()) . '-' . $user['user_id'];
            
            $certQuery = "INSERT INTO certificates (user_id, course_id, certificate_code, issued_at) 
                          VALUES (:user_id, :course_id, :code, NOW())";
            $certStmt = $db->prepare($certQuery);
            $certStmt->bindParam(':user_id', $user['user_id']);
            $certStmt->bindParam(':course_id', $quiz['course_id']);
            $certStmt->bindParam(':code', $certificate_code);
            $certStmt->execute();
        }
    }
    
    // Commit transaction
    $db->commit();
    
    // Log activity
    logActivity($user['user_id'], 'quiz_submit', "Submitted quiz ID: {$data->quiz_id}, Score: {$score}/{$max_possible_score}, Passed: " . ($passed ? 'Yes' : 'No'));
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => $passed ? 'Congratulations! You passed the quiz!' : 'Quiz submitted. Keep practicing to improve your score!',
        'score' => $score,
        'total_points' => $max_possible_score,
        'percentage' => round($percentage, 2),
        'passed' => $passed,
        'correct_answers' => $correct_answers,
        'total_questions' => $quiz['total_questions'],
        'previous_attempt' => $previousAttempt ? [
            'score' => $previousAttempt['score'],
            'percentage' => round($previousAttempt['percentage'], 2),
            'passed' => $previousAttempt['passed'] == 1
        ] : null
    ]);
    
} catch (PDOException $e) {
    // Rollback transaction on error
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    
    error_log("Submit Quiz Error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error: ' . $e->getMessage(),
        'success' => false
    ]);
} catch (Exception $e) {
    // Rollback transaction on error
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    
    error_log("Submit Quiz Error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'error' => 'Server error: ' . $e->getMessage(),
        'success' => false
    ]);
}
?>