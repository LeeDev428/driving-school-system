<?php
require_once 'config.php';

echo "<h2>Debug Quiz Database</h2>";

try {
    // Get latest quiz_responses
    echo "<h3>Latest Quiz Responses:</h3>";
    $stmt = $pdo->query("SELECT * FROM quiz_responses ORDER BY answered_at DESC LIMIT 10");
    $responses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Session ID</th><th>Scenario</th><th>Selected</th><th>Correct</th><th>Is Correct</th><th>Points</th><th>Answered At</th></tr>";
    foreach ($responses as $response) {
        echo "<tr>";
        echo "<td>{$response['id']}</td>";
        echo "<td>{$response['session_id']}</td>";
        echo "<td>{$response['scenario_id']}</td>";
        echo "<td>{$response['selected_option']}</td>";
        echo "<td>{$response['correct_option']}</td>";
        echo "<td style='color: " . ($response['is_correct'] ? 'green' : 'red') . ";'><strong>{$response['is_correct']}</strong></td>";
        echo "<td>{$response['points_earned']}</td>";
        echo "<td>{$response['answered_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Get latest quiz_sessions
    echo "<h3>Latest Quiz Sessions:</h3>";
    $stmt = $pdo->query("SELECT * FROM quiz_sessions ORDER BY created_at DESC LIMIT 5");
    $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Session ID</th><th>User ID</th><th>Answered</th><th>Correct</th><th>Status</th><th>Created</th></tr>";
    foreach ($sessions as $session) {
        echo "<tr>";
        echo "<td>{$session['id']}</td>";
        echo "<td>{$session['session_id']}</td>";
        echo "<td>{$session['user_id']}</td>";
        echo "<td>{$session['questions_answered']}</td>";
        echo "<td style='color: " . ($session['correct_answers'] > 0 ? 'green' : 'red') . ";'><strong>{$session['correct_answers']}</strong></td>";
        echo "<td>{$session['session_status']}</td>";
        echo "<td>{$session['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Manual calculation check
    if (!empty($sessions)) {
        $latest_session = $sessions[0]['session_id'];
        echo "<h3>Manual Check for Session: {$latest_session}</h3>";
        
        $stmt = $pdo->prepare("SELECT SUM(is_correct) as correct_count, COUNT(*) as total_count FROM quiz_responses WHERE session_id = ?");
        $stmt->execute([$latest_session]);
        $manual_check = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<p><strong>Manual calculation:</strong> {$manual_check['correct_count']} correct out of {$manual_check['total_count']} total</p>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>