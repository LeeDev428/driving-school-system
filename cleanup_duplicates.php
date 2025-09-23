<?php
require_once 'config.php';

echo "<h2>Clean Duplicate Quiz Data</h2>";

try {
    // Start transaction
    $pdo->beginTransaction();
    
    // Find and remove duplicate quiz_responses (keep the earliest one for each session_id + scenario_id combination)
    echo "<h3>Cleaning quiz_responses duplicates...</h3>";
    
    $cleanup_sql = "
        DELETE qr1 FROM quiz_responses qr1
        INNER JOIN quiz_responses qr2 
        WHERE qr1.session_id = qr2.session_id 
        AND qr1.scenario_id = qr2.scenario_id 
        AND qr1.id > qr2.id
    ";
    
    $stmt = $pdo->prepare($cleanup_sql);
    $deleted = $stmt->execute();
    $deleted_count = $stmt->rowCount();
    
    echo "<p>Deleted {$deleted_count} duplicate quiz_responses entries</p>";
    
    // Recalculate quiz_sessions data based on remaining quiz_responses
    echo "<h3>Recalculating quiz_sessions data...</h3>";
    
    $sessions_sql = "SELECT DISTINCT session_id FROM quiz_responses";
    $sessions_stmt = $pdo->query($sessions_sql);
    $sessions = $sessions_stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $updated_sessions = 0;
    foreach ($sessions as $session_id) {
        // Get stats for this session
        $stats_sql = "
            SELECT 
                COUNT(*) as answered,
                SUM(points_earned) as total_points,
                SUM(is_correct) as correct_count
            FROM quiz_responses 
            WHERE session_id = ?
        ";
        
        $stats_stmt = $pdo->prepare($stats_sql);
        $stats_stmt->execute([$session_id]);
        $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
        
        $completion_percentage = ($stats['answered'] / 5) * 100;
        $session_status = $stats['answered'] >= 5 ? 'completed' : 'in_progress';
        
        // Update quiz_sessions
        $update_sql = "
            UPDATE quiz_sessions SET 
                questions_answered = ?,
                correct_answers = ?,
                total_points = ?,
                completion_percentage = ?,
                session_status = ?,
                completed_at = CASE WHEN ? >= 5 THEN NOW() ELSE completed_at END
            WHERE session_id = ?
        ";
        
        $update_stmt = $pdo->prepare($update_sql);
        $update_stmt->execute([
            $stats['answered'],
            $stats['correct_count'],
            $stats['total_points'],
            $completion_percentage,
            $session_status,
            $stats['answered'],
            $session_id
        ]);
        
        $updated_sessions++;
    }
    
    echo "<p>Updated {$updated_sessions} quiz_sessions entries</p>";
    
    // Remove any duplicate quiz_sessions (same user_id, keep the latest)
    echo "<h3>Cleaning quiz_sessions duplicates...</h3>";
    
    $sessions_cleanup_sql = "
        DELETE qs1 FROM quiz_sessions qs1
        INNER JOIN quiz_sessions qs2 
        WHERE qs1.user_id = qs2.user_id 
        AND qs1.created_at < qs2.created_at
        AND qs1.session_status = 'in_progress'
    ";
    
    $sessions_stmt = $pdo->prepare($sessions_cleanup_sql);
    $sessions_stmt->execute();
    $deleted_sessions = $sessions_stmt->rowCount();
    
    echo "<p>Deleted {$deleted_sessions} duplicate quiz_sessions entries</p>";
    
    // Commit transaction
    $pdo->commit();
    
    echo "<h3 style='color: green;'>✅ Cleanup completed successfully!</h3>";
    
    // Show final stats
    echo "<h3>Final Statistics:</h3>";
    
    $final_responses = $pdo->query("SELECT COUNT(*) FROM quiz_responses")->fetchColumn();
    $final_sessions = $pdo->query("SELECT COUNT(*) FROM quiz_sessions")->fetchColumn();
    $completed_sessions = $pdo->query("SELECT COUNT(*) FROM quiz_sessions WHERE session_status = 'completed'")->fetchColumn();
    
    echo "<ul>";
    echo "<li>Total quiz_responses: {$final_responses}</li>";
    echo "<li>Total quiz_sessions: {$final_sessions}</li>";
    echo "<li>Completed sessions: {$completed_sessions}</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>