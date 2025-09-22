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
    
    // Get user_id from session with proper fallback
    $user_id = null;
    if (isset($_SESSION['id'])) {
        $user_id = $_SESSION['id'];
    } elseif (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
    } else {
        // For testing purposes, use a default user ID
        $user_id = 1;
    }
    
    // Validate required fields
    $required_fields = ['type', 'message', 'severity', 'timestamp', 'session_id'];
    
    foreach ($required_fields as $field) {
        if (!isset($input[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }
    
    // Create violation_logs table if it doesn't exist
    $create_table_sql = "CREATE TABLE IF NOT EXISTS `violation_logs` (
        `id` int NOT NULL AUTO_INCREMENT,
        `user_id` int DEFAULT NULL,
        `session_id` varchar(100) NOT NULL,
        `violation_type` varchar(50) NOT NULL,
        `violation_message` text NOT NULL,
        `severity` enum('info','warning','error') NOT NULL DEFAULT 'warning',
        `violation_timestamp` bigint NOT NULL,
        `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `user_id` (`user_id`),
        KEY `session_id` (`session_id`),
        KEY `violation_timestamp` (`violation_timestamp`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci";
    
    $pdo->exec($create_table_sql);
    
    // Prepare SQL statement
    $sql = "INSERT INTO violation_logs (
        user_id, 
        session_id,
        violation_type, 
        violation_message, 
        severity, 
        violation_timestamp
    ) VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = $pdo->prepare($sql);
    
    // Bind parameters
    $stmt->execute([
        $user_id,
        $input['session_id'],
        $input['type'],
        $input['message'],
        $input['severity'],
        intval($input['timestamp'])
    ]);
    
    $violation_id = $pdo->lastInsertId();
    
    // Return success response
    echo json_encode([
        'success' => true,
        'violation_id' => $violation_id,
        'message' => 'Violation logged successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>