<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in and is admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["user_type"] !== "admin") {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

require_once "../config.php";

if (!isset($_GET['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User ID not provided']);
    exit;
}

$user_id = intval($_GET['user_id']);

try {
    // Get user basic info
    $user_query = "SELECT id, full_name, email, user_type FROM users WHERE id = ?";
    $stmt = $conn->prepare($user_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user_result = $stmt->get_result();
    
    if ($user_result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    
    $user = $user_result->fetch_assoc();
    
    // Get assessment progress
    $assessment_query = "SELECT 
        COUNT(*) as attempts,
        MAX(score_percentage) as best_score,
        AVG(score_percentage) as avg_score,
        MAX(passed) as passed,
        MAX(time_completed) as last_attempt
        FROM user_assessment_sessions
        WHERE user_id = ? AND status = 'completed'";
    
    $stmt = $conn->prepare($assessment_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $assessment = $stmt->get_result()->fetch_assoc();
    
    // Get quiz progress
    $quiz_query = "SELECT 
        COUNT(*) as attempts,
        MAX(score_percentage) as best_score,
        AVG(score_percentage) as avg_score,
        MAX(passed) as passed,
        MAX(time_completed) as last_attempt
        FROM user_quiz_sessions
        WHERE user_id = ? AND status = 'completed'";
    
    $stmt = $conn->prepare($quiz_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $quiz = $stmt->get_result()->fetch_assoc();
    
    // Combine all data
    $user['assessment_attempts'] = $assessment['attempts'] ?? 0;
    $user['assessment_best_score'] = $assessment['best_score'] ? round($assessment['best_score'], 1) : null;
    $user['assessment_avg_score'] = $assessment['avg_score'] ? round($assessment['avg_score'], 1) : null;
    $user['assessment_passed'] = $assessment['passed'] ?? 0;
    
    $user['quiz_attempts'] = $quiz['attempts'] ?? 0;
    $user['quiz_best_score'] = $quiz['best_score'] ? round($quiz['best_score'], 1) : null;
    $user['quiz_avg_score'] = $quiz['avg_score'] ? round($quiz['avg_score'], 1) : null;
    $user['quiz_passed'] = $quiz['passed'] ?? 0;
    
    // Get last activity
    $last_activity = null;
    if ($assessment['last_attempt']) {
        $last_activity = $assessment['last_attempt'];
    }
    if ($quiz['last_attempt'] && (!$last_activity || $quiz['last_attempt'] > $last_activity)) {
        $last_activity = $quiz['last_attempt'];
    }
    $user['last_activity'] = $last_activity;
    
    echo json_encode(['success' => true, 'user' => $user]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

$conn->close();
?>
