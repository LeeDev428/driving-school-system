<?php
session_start();

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php");
    exit;
}

require_once "../config.php";

// Get the latest simulation result for the user
$user_id = $_SESSION["id"];

// First try to get from quiz_sessions (new table)
$latest_quiz_session = null;
$sql = "SELECT qs.*, 
        (SELECT COUNT(*) FROM quiz_responses qr WHERE qr.session_id = qs.session_id AND qr.is_correct = 1) as correct_answers_count
        FROM quiz_sessions qs 
        WHERE qs.user_id = ? 
        ORDER BY qs.completed_at DESC, qs.created_at DESC 
        LIMIT 1";

if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        $latest_quiz_session = $row;
    }
    
    mysqli_stmt_close($stmt);
}

// Get detailed quiz responses if available
$quiz_responses = [];
if ($latest_quiz_session && $latest_quiz_session['session_id']) {
    $sql = "SELECT * FROM quiz_responses 
            WHERE session_id = ? AND user_id = ? 
            ORDER BY scenario_id";
    
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "si", $latest_quiz_session['session_id'], $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        while ($row = mysqli_fetch_assoc($result)) {
            $quiz_responses[] = $row;
        }
        
        mysqli_stmt_close($stmt);
    }
}

// Fallback: Get from old simulation_results table if no quiz session found
$legacy_result = null;
if (!$latest_quiz_session) {
    $sql = "SELECT * FROM simulation_results WHERE user_id = ? ORDER BY created_at DESC LIMIT 1";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($row = mysqli_fetch_assoc($result)) {
            $legacy_result = $row;
        }
        
        mysqli_stmt_close($stmt);
    }
}

// Page setup for layout
$page_title = "Simulation Results";
$header_title = "Your Driving Simulation Results";

ob_start();
?>

<div class="results-container">
    <?php if ($latest_quiz_session): ?>
        <!-- NEW QUIZ RESULTS FROM quiz_sessions TABLE -->
        <div class="results-header">
            <h2>🏁 Quiz Simulation Complete!</h2>
            <div class="completion-date">
                Completed on: <?php echo date('F j, Y \a\t g:i A', strtotime($latest_quiz_session['completed_at'] ?: $latest_quiz_session['created_at'])); ?>
            </div>
        </div>

        <div class="results-summary">
            <div class="score-card <?php echo $latest_quiz_session['session_status'] === 'completed' ? 'passed' : 'failed'; ?>">
                <div class="score-percentage">
                    <?php echo number_format($latest_quiz_session['completion_percentage'], 1); ?>%
                </div>
                <div class="score-label">
                    <?php echo $latest_quiz_session['session_status'] === 'completed' ? 'COMPLETED' : 'IN PROGRESS'; ?>
                </div>
            </div>

            <div class="results-stats">
                <div class="stat-item">
                    <div class="stat-value"><?php echo $latest_quiz_session['correct_answers']; ?>/<?php echo $latest_quiz_session['total_questions']; ?></div>
                    <div class="stat-label">Correct Answers</div>
                </div>
                
                <div class="stat-item">
                    <div class="stat-value"><?php echo $latest_quiz_session['total_points']; ?></div>
                    <div class="stat-label">Total Points</div>
                </div>
                
                <div class="stat-item">
                    <div class="stat-value"><?php echo $latest_quiz_session['questions_answered']; ?>/5</div>
                    <div class="stat-label">Scenarios Completed</div>
                </div>
                
                <?php if ($latest_quiz_session['total_time_seconds']): ?>
                <div class="stat-item">
                    <div class="stat-value"><?php echo gmdate('i:s', $latest_quiz_session['total_time_seconds']); ?></div>
                    <div class="stat-label">Time Taken</div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($quiz_responses)): ?>
        <!-- DETAILED QUIZ RESPONSES -->
        <div class="detailed-results">
            <h3>📋 Detailed Quiz Responses</h3>
            <div class="responses-container">
                <?php foreach ($quiz_responses as $response): ?>
                    <div class="response-card">
                        <div class="response-header">
                            <h4>Scenario <?php echo $response['scenario_id']; ?></h4>
                            <span class="response-result <?php echo $response['is_correct'] ? 'correct' : 'incorrect'; ?>">
                                <?php echo $response['is_correct'] ? '✅ Correct' : '❌ Incorrect'; ?>
                            </span>
                        </div>
                        
                        <div class="question-text">
                            <?php echo htmlspecialchars($response['question_text']); ?>
                        </div>
                        
                        <div class="answer-details">
                            <div class="answer-row">
                                <strong>Your Answer:</strong> Option <?php echo chr(65 + $response['selected_option']); ?>
                            </div>
                            <div class="answer-row">
                                <strong>Correct Answer:</strong> Option <?php echo chr(65 + $response['correct_option']); ?>
                            </div>
                            <div class="answer-row">
                                <strong>Points Earned:</strong> <?php echo $response['points_earned']; ?>/20
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

    <?php elseif ($legacy_result): ?>
        <!-- FALLBACK: OLD SIMULATION RESULTS -->
        <div class="results-header">
            <h2>🏁 Simulation Complete!</h2>
            <div class="completion-date">
                Completed on: <?php echo date('F j, Y \a\t g:i A', strtotime($legacy_result['created_at'])); ?>
            </div>
        </div>

        <div class="results-summary">
            <div class="score-card <?php echo $legacy_result['status'] === 'completed' ? 'passed' : 'failed'; ?>">
                <div class="score-percentage">
                    <?php echo number_format($legacy_result['score_percentage'], 1); ?>%
                </div>
                <div class="score-label">
                    <?php echo $legacy_result['status'] === 'completed' ? 'PASSED' : 'NEEDS IMPROVEMENT'; ?>
                </div>
            </div>

            <div class="results-stats">
                <div class="stat-item">
                    <div class="stat-value"><?php echo $legacy_result['correct_answers']; ?>/<?php echo $legacy_result['total_questions']; ?></div>
                    <div class="stat-label">Correct Answers</div>
                </div>
                
                <div class="stat-item">
                    <div class="stat-value"><?php echo $legacy_result['total_score']; ?></div>
                    <div class="stat-label">Total Score</div>
                </div>
                
                <div class="stat-item">
                    <div class="stat-value"><?php echo gmdate('i:s', $legacy_result['completion_time_seconds']); ?></div>
                    <div class="stat-label">Time Taken</div>
                </div>
            </div>
        </div>

    <?php else: ?>
        <!-- NO RESULTS FOUND -->
        <div class="no-results">
            <div style="text-align: center; padding: 50px;">
                <h3>No Simulation Results Found</h3>
                <p>You haven't completed any driving simulation quizzes yet.</p>
                <a href="simulation.php" class="btn btn-primary">Start Your First Simulation</a>
            </div>
        </div>
    <?php endif; ?>

    <!-- ACTION BUTTONS -->
    <div class="results-actions">
        <a href="simulation.php" class="btn btn-primary">
            🔄 Take New Quiz
        </a>
        <a href="dashboard.php" class="btn btn-secondary">
            📊 Back to Dashboard
        </a>
        <a href="../view_quiz_results.php" class="btn btn-info">
            � View All Results
        </a>
    </div>
            </div>
        </div>

    <?php else: ?>
        <!-- NO RESULTS FOUND -->
        <div class="no-results">
            <div style="text-align: center; padding: 50px;">
                <h3>No Simulation Results Found</h3>
                <p>You haven't completed any driving simulation quizzes yet.</p>
                <a href="simulation.php" class="btn btn-primary">Start Your First Simulation</a>
            </div>
        </div>
    <?php endif; ?>

    <!-- ACTION BUTTONS -->
    <div class="results-actions">
        <a href="simulation.php" class="btn btn-primary">
            🔄 Take New Quiz
        </a>
        <a href="dashboard.php" class="btn btn-secondary">
            � Back to Dashboard
        </a>
        <a href="../view_quiz_results.php" class="btn btn-info">
            � View All Results
        </a>
    </div>
</div><style>
.results-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.results-header {
    text-align: center;
    margin-bottom: 30px;
}

.results-header h2 {
    color: #2c3e50;
    margin-bottom: 10px;
}

.completion-date {
    color: #7f8c8d;
    font-size: 14px;
}

.results-summary {
    background: white;
    border-radius: 10px;
    padding: 30px;
    margin-bottom: 30px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.score-card {
    text-align: center;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 30px;
}

.score-card.passed {
    background: linear-gradient(135deg, #27ae60, #2ecc71);
    color: white;
}

.score-card.failed {
    background: linear-gradient(135deg, #e74c3c, #c0392b);
    color: white;
}

.score-percentage {
    font-size: 48px;
    font-weight: bold;
    margin-bottom: 10px;
}

.score-label {
    font-size: 18px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.results-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 20px;
}

.stat-item {
    text-align: center;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
}

.stat-value {
    font-size: 24px;
    font-weight: bold;
    color: #2c3e50;
    margin-bottom: 5px;
}

.stat-label {
    font-size: 14px;
    color: #7f8c8d;
    text-transform: uppercase;
}

/* NEW: Detailed Quiz Response Styles */
.detailed-results {
    background: white;
    border-radius: 10px;
    padding: 30px;
    margin-bottom: 30px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.detailed-results h3 {
    color: #2c3e50;
    margin-bottom: 20px;
    text-align: center;
}

.responses-container {
    display: grid;
    gap: 15px;
}

.response-card {
    border: 1px solid #dee2e6;
    border-radius: 10px;
    padding: 20px;
    background: #f8f9fa;
    transition: transform 0.2s;
}

.response-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.response-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.response-header h4 {
    margin: 0;
    color: #2c3e50;
    font-size: 18px;
}

.response-result {
    padding: 5px 15px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: bold;
}

.response-result.correct {
    background: #d4edda;
    color: #155724;
}

.response-result.incorrect {
    background: #f8d7da;
    color: #721c24;
}

.question-text {
    background: white;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 15px;
    border-left: 4px solid #007bff;
    font-style: italic;
}

.answer-details {
    display: grid;
    gap: 8px;
}

.answer-row {
    display: flex;
    justify-content: space-between;
    padding: 8px 12px;
    background: white;
    border-radius: 5px;
    border-left: 3px solid #6c757d;
}

.no-results {
    text-align: center;
    padding: 50px;
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.results-actions {
    text-align: center;
    margin-top: 30px;
}

.btn {
    display: inline-block;
    padding: 12px 24px;
    margin: 0 10px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    transition: transform 0.2s;
}

.btn:hover {
    transform: translateY(-2px);
}

.btn-primary {
    background: linear-gradient(135deg, #007bff, #0056b3);
    color: white;
}

.btn-secondary {
    background: linear-gradient(135deg, #6c757d, #545b62);
    color: white;
}

.btn-info {
    background: linear-gradient(135deg, #17a2b8, #138496);
    color: white;
}
    font-size: 14px;
}

.results-summary {
    background: white;
    border-radius: 10px;
    padding: 30px;
    margin-bottom: 30px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.score-card {
    text-align: center;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 30px;
}

.score-card.passed {
    background: linear-gradient(135deg, #27ae60, #2ecc71);
    color: white;
}

.score-card.failed {
    background: linear-gradient(135deg, #e74c3c, #c0392b);
    color: white;
}

.score-percentage {
    font-size: 48px;
    font-weight: bold;
    margin-bottom: 10px;
}

.score-label {
    font-size: 18px;
    font-weight: 600;
    letter-spacing: 2px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 20px;
}

.stat-item {
    text-align: center;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
    border: 2px solid #e9ecef;
}

.stat-item.correct {
    border-color: #27ae60;
    background: #d5f4e6;
}

.stat-item.wrong {
    border-color: #e74c3c;
    background: #f8d7da;
}

.stat-number {
    font-size: 24px;
    font-weight: bold;
    color: #2c3e50;
    margin-bottom: 5px;
}

.stat-label {
    font-size: 14px;
    color: #7f8c8d;
    font-weight: 500;
}

.detailed-results {
    background: white;
    border-radius: 10px;
    padding: 30px;
    margin-bottom: 30px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.detailed-results h3 {
    color: #2c3e50;
    margin-bottom: 20px;
}

.scenario-result {
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 15px;
    border-left: 4px solid;
}

.scenario-result.correct {
    background: #d5f4e6;
    border-left-color: #27ae60;
}

.scenario-result.incorrect {
    background: #f8d7da;
    border-left-color: #e74c3c;
}

.scenario-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.scenario-number {
    font-weight: bold;
    color: #2c3e50;
}

.scenario-status {
    font-size: 14px;
    font-weight: 600;
}

.scenario-title {
    font-weight: 600;
    margin-bottom: 8px;
    color: #2c3e50;
}

.scenario-details {
    display: flex;
    gap: 20px;
    font-size: 14px;
    color: #7f8c8d;
}

.results-actions {
    display: flex;
    justify-content: center;
    gap: 20px;
    margin-bottom: 30px;
}

.btn {
    padding: 12px 24px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 600;
    font-size: 16px;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
}

.btn-primary {
    background: #3498db;
    color: white;
}

.btn-primary:hover {
    background: #2980b9;
    transform: translateY(-2px);
}

.btn-secondary {
    background: #95a5a6;
    color: white;
}

.btn-secondary:hover {
    background: #7f8c8d;
    transform: translateY(-2px);
}

.congratulations {
    background: #d5f4e6;
    border: 2px solid #27ae60;
    border-radius: 10px;
    padding: 20px;
    text-align: center;
}

.congratulations h3 {
    color: #27ae60;
    margin-bottom: 10px;
}

.improvement-tips {
    background: #fff3cd;
    border: 2px solid #ffc107;
    border-radius: 10px;
    padding: 20px;
}

.improvement-tips h3 {
    color: #856404;
    margin-bottom: 15px;
}

.improvement-tips ul {
    margin-top: 15px;
    padding-left: 20px;
}

.improvement-tips li {
    margin-bottom: 8px;
    color: #856404;
}

.no-results {
    text-align: center;
    padding: 40px;
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.no-results h2 {
    color: #2c3e50;
    margin-bottom: 15px;
}

.no-results p {
    color: #7f8c8d;
    margin-bottom: 20px;
}

@media (max-width: 768px) {
    .results-container {
        padding: 15px;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .results-actions {
        flex-direction: column;
        align-items: center;
    }
    
    .scenario-details {
        flex-direction: column;
        gap: 5px;
    }
    
    .scenario-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 5px;
    }
}
</style>

<?php
$content = ob_get_clean();
include '../layouts/main_layout.php';
?>
