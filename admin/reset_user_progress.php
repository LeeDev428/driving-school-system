<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in and is admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["user_type"] !== "admin") {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

require_once "../config.php";

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User ID not provided']);
    exit;
}

$user_id = intval($input['user_id']);

try {
    // Start transaction
    $conn->begin_transaction();
    
    // Delete all assessment sessions for this user
    $delete_assessment = "DELETE FROM user_assessment_sessions WHERE user_id = ?";
    $stmt = $conn->prepare($delete_assessment);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $assessment_deleted = $stmt->affected_rows;
    
    // Delete all quiz sessions for this user
    $delete_quiz = "DELETE FROM user_quiz_sessions WHERE user_id = ?";
    $stmt = $conn->prepare($delete_quiz);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $quiz_deleted = $stmt->affected_rows;
    
    // Delete all simulation results for this user
    $delete_simulation = "DELETE FROM simulation_results WHERE user_id = ?";
    $stmt = $conn->prepare($delete_simulation);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $simulation_deleted = $stmt->affected_rows;
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'User progress reset successfully',
        'details' => [
            'assessment_sessions_deleted' => $assessment_deleted,
            'quiz_sessions_deleted' => $quiz_deleted,
            'simulation_results_deleted' => $simulation_deleted
        ]
    ]);
    
} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

$conn->close();
?>
