<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

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

try {
    // Start session to check user authentication
    session_start();
    
    // For simulation, we'll use a default user_id if not logged in
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;
    
    // Validate required fields
    $required_fields = ['simulation_type', 'total_scenarios', 'correct_answers', 'wrong_answers', 'completion_time_seconds', 'scenarios_data'];
    
    foreach ($required_fields as $field) {
        if (!isset($input[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }
    
    // Calculate score percentage
    $total_scenarios = intval($input['total_scenarios']);
    $correct_answers = intval($input['correct_answers']);
    $score_percentage = $total_scenarios > 0 ? ($correct_answers / $total_scenarios) * 100 : 0;
    
    // Determine status based on score
    $status = 'completed';
    if ($score_percentage < 60) {
        $status = 'failed';
    }
    
    // Prepare SQL statement
    $sql = "INSERT INTO simulation_results (
        user_id, 
        simulation_type, 
        total_scenarios, 
        correct_answers, 
        wrong_answers, 
        score_percentage, 
        completion_time_seconds, 
        scenarios_data, 
        status
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $pdo->prepare($sql);
    
    // Bind parameters
    $stmt->execute([
        $user_id,
        $input['simulation_type'],
        $total_scenarios,
        $correct_answers,
        intval($input['wrong_answers']),
        $score_percentage,
        intval($input['completion_time_seconds']),
        json_encode($input['scenarios_data']),
        $status
    ]);
    
    $simulation_id = $pdo->lastInsertId();
    
    // Return success response
    echo json_encode([
        'success' => true,
        'simulation_id' => $simulation_id,
        'score_percentage' => round($score_percentage, 2),
        'status' => $status,
        'message' => 'Simulation results saved successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>