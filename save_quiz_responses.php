<?php
// Ensure no output before JSON
error_reporting(E_ALL);
ini_set('display_errors', 1); // Enable display_errors for debugging
ini_set('log_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    require_once 'config.php';
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        exit();
    }

    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON input']);
        exit();
    }

    // Start session to get user ID
    session_start();
    
    $user_id = null;
    if (isset($_SESSION['id'])) {
        $user_id = $_SESSION['id'];
    } elseif (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
    } else {
        throw new Exception("User not logged in - session: " . print_r($_SESSION, true));
    }
    
    $action = $input['action'] ?? '';
    
    switch ($action) {
        case 'save_single_response':
            echo json_encode(saveSingleResponse($pdo, $user_id, $input));
            break;
            
        case 'complete_quiz':
            echo json_encode(completeQuiz($pdo, $user_id, $input));
            break;
            
        case 'start_session':
            echo json_encode(startQuizSession($pdo, $user_id, $input));
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action: ' . $action]);
            break;
    }
    
} catch (Exception $e) {
    // Ensure JSON error response
    http_response_code(500);
    echo json_encode([
        'error' => 'Server error: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
} catch (Error $e) {
    // Catch fatal errors too
    http_response_code(500);
    echo json_encode([
        'error' => 'Fatal error: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
}

/**
 * Start a new quiz session
 */
function startQuizSession($pdo, $user_id, $input) {
    $session_id = 'quiz_' . $user_id . '_' . time() . '_' . uniqid();
    
    $sql = "INSERT INTO quiz_sessions (session_id, user_id, total_questions, started_at) 
            VALUES (?, ?, 5, NOW())";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$session_id, $user_id]);
    
    return [
        'success' => true,
        'session_id' => $session_id,
        'message' => 'Quiz session started'
    ];
}

/**
 * Save individual quiz response
 */
function saveSingleResponse($pdo, $user_id, $input) {
    $required_fields = ['session_id', 'scenario_id', 'question_text', 'selected_option', 'correct_option', 'is_correct', 'points_earned'];
    
    foreach ($required_fields as $field) {
        if (!isset($input[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }
    
    // Save individual response
    $sql = "INSERT INTO quiz_responses (
        user_id, session_id, scenario_id, question_text, selected_option, 
        correct_option, is_correct, points_earned, time_taken_seconds, answered_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $user_id,
        $input['session_id'],
        $input['scenario_id'],
        $input['question_text'],
        $input['selected_option'],
        $input['correct_option'],
        $input['is_correct'] ? 1 : 0,
        $input['points_earned'],
        $input['time_taken_seconds'] ?? null
    ]);
    
    // Update session progress
    updateSessionProgress($pdo, $input['session_id']);
    
    return [
        'success' => true,
        'response_id' => $pdo->lastInsertId(),
        'message' => 'Response saved successfully'
    ];
}

/**
 * Complete the quiz and save to simulation_results
 */
function completeQuiz($pdo, $user_id, $input) {
    $session_id = $input['session_id'] ?? '';
    
    if (empty($session_id)) {
        throw new Exception("Session ID required");
    }
    
    // Get all responses for this session
    $sql = "SELECT * FROM quiz_responses WHERE session_id = ? ORDER BY scenario_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$session_id]);
    $responses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($responses) < 5) {
        throw new Exception("All 5 questions must be answered before completing");
    }
    
    // Calculate totals
    $correct_answers = 0;
    $total_points = 0;
    $scenarios_data = [];
    
    foreach ($responses as $response) {
        if ($response['is_correct']) {
            $correct_answers++;
        }
        $total_points += $response['points_earned'];
        
        $scenarios_data[] = [
            'scenario_id' => $response['scenario_id'],
            'question' => $response['question_text'],
            'selected_option' => $response['selected_option'],
            'correct_option' => $response['correct_option'],
            'is_correct' => (bool)$response['is_correct'],
            'points_earned' => $response['points_earned'],
            'answered_at' => $response['answered_at']
        ];
    }
    
    $wrong_answers = 5 - $correct_answers;
    $score_percentage = ($correct_answers / 5) * 100;
    $status = $score_percentage >= 60 ? 'completed' : 'failed';
    
    // Save to simulation_results
    $sql = "INSERT INTO simulation_results (
        user_id, simulation_type, total_scenarios, correct_answers, wrong_answers,
        score_percentage, completion_time_seconds, scenarios_data, status
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $user_id,
        'driving_scenarios',
        5,
        $correct_answers,
        $wrong_answers,
        $score_percentage,
        $input['completion_time_seconds'] ?? 0,
        json_encode($scenarios_data),
        $status
    ]);
    
    $simulation_id = $pdo->lastInsertId();
    
    // Mark session as completed
    $sql = "UPDATE quiz_sessions SET 
            session_status = 'completed',
            questions_answered = 5,
            correct_answers = ?,
            total_points = ?,
            completion_percentage = ?,
            completed_at = NOW(),
            total_time_seconds = ?
            WHERE session_id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $correct_answers,
        $total_points,
        $score_percentage,
        $input['completion_time_seconds'] ?? 0,
        $session_id
    ]);
    
    return [
        'success' => true,
        'simulation_id' => $simulation_id,
        'session_id' => $session_id,
        'score_percentage' => round($score_percentage, 2),
        'correct_answers' => $correct_answers,
        'wrong_answers' => $wrong_answers,
        'total_points' => $total_points,
        'status' => $status,
        'message' => 'Quiz completed and saved successfully'
    ];
}

/**
 * Update session progress
 */
function updateSessionProgress($pdo, $session_id) {
    $sql = "SELECT COUNT(*) as answered, SUM(points_earned) as total_points, SUM(is_correct) as correct
            FROM quiz_responses WHERE session_id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$session_id]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $completion_percentage = ($stats['answered'] / 5) * 100;
    
    $sql = "UPDATE quiz_sessions SET 
            questions_answered = ?,
            correct_answers = ?,
            total_points = ?,
            completion_percentage = ?
            WHERE session_id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $stats['answered'],
        $stats['correct'],
        $stats['total_points'],
        $completion_percentage,
        $session_id
    ]);
}
?>