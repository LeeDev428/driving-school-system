<?php
session_start();

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php");
    exit;
}

require_once "../config.php";

// Get the latest simulation result for the user
$user_id = $_SESSION["id"];
$sql = "SELECT * FROM simulation_results WHERE user_id = ? ORDER BY created_at DESC LIMIT 1";
$latest_result = null;

if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        $latest_result = $row;
    }
    
    mysqli_stmt_close($stmt);
}

// Page setup for layout
$page_title = "Simulation Results";
$header_title = "Your Driving Simulation Results";

ob_start();
?>

<div class="results-container">
    <?php if ($latest_result): ?>
        <div class="results-header">
            <h2>🏁 Simulation Complete!</h2>
            <div class="completion-date">
                Completed on: <?php echo date('F j, Y \a\t g:i A', strtotime($latest_result['created_at'])); ?>
            </div>
        </div>

        <div class="results-summary">
            <div class="score-card <?php echo $latest_result['status'] === 'completed' ? 'passed' : 'failed'; ?>">
                <div class="score-percentage">
                    <?php echo number_format($latest_result['score_percentage'], 1); ?>%
                </div>
                <div class="score-label">
                    <?php echo $latest_result['status'] === 'completed' ? 'PASSED' : 'NEEDS IMPROVEMENT'; ?>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-number"><?php echo $latest_result['total_scenarios']; ?></div>
                    <div class="stat-label">Total Scenarios</div>
                </div>
                <div class="stat-item correct">
                    <div class="stat-number"><?php echo $latest_result['correct_answers']; ?></div>
                    <div class="stat-label">Correct Answers</div>
                </div>
                <div class="stat-item wrong">
                    <div class="stat-number"><?php echo $latest_result['wrong_answers']; ?></div>
                    <div class="stat-label">Wrong Answers</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo gmdate("i:s", $latest_result['completion_time_seconds']); ?></div>
                    <div class="stat-label">Completion Time</div>
                </div>
            </div>
        </div>

        <?php if (!empty($latest_result['scenarios_data'])): ?>
            <?php $scenarios_data = json_decode($latest_result['scenarios_data'], true); ?>
            <?php if (is_array($scenarios_data) && count($scenarios_data) > 0): ?>
                <div class="detailed-results">
                    <h3>📋 Detailed Scenario Results</h3>
                    <div class="scenarios-list">
                        <?php foreach ($scenarios_data as $index => $scenario): ?>
                            <div class="scenario-result <?php echo $scenario['isCorrect'] ? 'correct' : 'incorrect'; ?>">
                                <div class="scenario-header">
                                    <span class="scenario-number">Scenario <?php echo $index + 1; ?></span>
                                    <span class="scenario-status">
                                        <?php echo $scenario['isCorrect'] ? '✅ Correct' : '❌ Incorrect'; ?>
                                    </span>
                                </div>
                                <div class="scenario-title"><?php echo htmlspecialchars($scenario['scenario']); ?></div>
                                <div class="scenario-details">
                                    <span>Your answer: Option <?php echo $scenario['userAnswer'] + 1; ?></span>
                                    <span>Correct answer: Option <?php echo $scenario['correctAnswer'] + 1; ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <div class="results-actions">
            <a href="simulation.php" class="btn btn-primary">
                🔄 Try Again
            </a>
            <a href="dashboard.php" class="btn btn-secondary">
                📊 Back to Dashboard
            </a>
        </div>

        <?php if ($latest_result['score_percentage'] >= 70): ?>
            <div class="congratulations">
                <h3>🎉 Congratulations!</h3>
                <p>You passed the driving simulation! You demonstrated good knowledge of safe driving practices.</p>
            </div>
        <?php else: ?>
            <div class="improvement-tips">
                <h3>💡 Areas for Improvement</h3>
                <p>Consider reviewing driving rules and safety practices before taking the simulation again.</p>
                <ul>
                    <li>Always stop for pedestrians at crossings</li>
                    <li>Follow traffic signals and signs</li>
                    <li>Maintain safe speeds in school zones</li>
                    <li>Be extra cautious at intersections</li>
                </ul>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <div class="no-results">
            <h2>📋 No Results Found</h2>
            <p>You haven't completed any simulation yet.</p>
            <a href="simulation.php" class="btn btn-primary">
                🚗 Start Simulation
            </a>
        </div>
    <?php endif; ?>
</div>

<style>
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
