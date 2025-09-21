<?php
session_start();

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php");
    exit;
}

require_once "../config.php";

// Handle AJAX requests for saving simulation results
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] == 'save_simulation_result') {
        $user_id = $_SESSION["id"];
        $total_scenarios = (int)$_POST['total_scenarios'];
        $correct_answers = (int)$_POST['correct_answers'];
        $wrong_answers = (int)$_POST['wrong_answers'];
        $completion_time = (int)$_POST['completion_time'];
        $score_percentage = ($total_scenarios > 0) ? round(($correct_answers / $total_scenarios) * 100, 2) : 0;
        $scenarios_data = json_encode($_POST['scenarios_data'] ?? []);
        $status = ($score_percentage >= 70) ? 'completed' : 'failed';
        
        $sql = "INSERT INTO simulation_results (user_id, total_scenarios, correct_answers, wrong_answers, score_percentage, completion_time_seconds, scenarios_data, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "iiidisss", $user_id, $total_scenarios, $correct_answers, $wrong_answers, $score_percentage, $completion_time, $scenarios_data, $status);
            
            if (mysqli_stmt_execute($stmt)) {
                echo json_encode(['success' => true, 'message' => 'Simulation result saved successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
            }
            
            mysqli_stmt_close($stmt);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database prepare error: ' . mysqli_error($conn)]);
        }
        
        exit;
    }
}

// Get simulation history for the user
$user_id = $_SESSION["id"];
$history_sql = "SELECT * FROM simulation_results WHERE user_id = ? ORDER BY created_at DESC LIMIT 10";
$simulation_history = [];

if ($stmt = mysqli_prepare($conn, $history_sql)) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($result)) {
        $simulation_history[] = $row;
    }
    
    mysqli_stmt_close($stmt);
}

// Page setup for layout
$page_title = "Advanced Driving Simulation";
$header_title = "Professional Driving Practice Simulator";

ob_start();
?>

<div class="fullscreen-simulation">
    <!-- Simulation Canvas -->
    <div class="simulation-canvas-container">
        <canvas id="simulationCanvas" class="simulation-canvas"></canvas>
        
        <!-- Loading Screen -->
        <div class="loading-screen" id="loadingScreen">
            <div class="loading-spinner"></div>
            <div class="loading-text">Loading Driving Environment...</div>
        </div>
        
        <!-- Status Message -->
        <div class="status-message" id="statusMessage" style="display: none;">
            Loading...
        </div>
        
        <!-- UI Overlay -->
        <div class="ui-overlay">
            <!-- Scenario Panel -->
            <div class="scenario-panel" id="scenarioPanel">
                <div class="scenario-header">
                    <h3>Scenario <span id="scenarioNumber">1</span>/5</h3>
                    <div class="scenario-timer" id="scenarioTimer">30s</div>
                </div>
                <div class="scenario-description" id="scenarioDescription">
                    <p>Loading scenario...</p>
                </div>
                <div class="scenario-question" id="scenarioQuestion">
                    <p>No active scenario</p>
                </div>
                <div class="scenario-options" id="scenarioOptions">
                    <!-- Options will be populated by JavaScript -->
                </div>
            </div>
            
            <!-- Control Panel -->
            <div class="control-panel">
                <div class="control-buttons">
                    <button class="control-btn accelerate-btn" id="accelerateBtn">⬆️ Accelerate</button>
                    <button class="control-btn brake-btn" id="brakeBtn">⬇️ Brake</button>
                    <button class="control-btn turn-left-btn" id="turnLeftBtn">⬅️ Turn Left</button>
                    <button class="control-btn turn-right-btn" id="turnRightBtn">➡️ Turn Right</button>
                </div>
            </div>
            
            <!-- Speed Panel -->
            <div class="speed-panel">
                <div class="speed-display" id="speedDisplay">0</div>
                <div class="speed-label">km/h</div>
            </div>


            
            <!-- Mini Map -->
            <div class="mini-map" id="miniMap">
                <canvas id="miniMapCanvas" width="150" height="150"></canvas>
            </div>
        </div>
    </div>

    <!-- Results Screen -->
    <div class="results-screen" id="resultsScreen">
        <div class="results-content">
            <div class="results-header">
                <div class="results-title" id="resultsTitle">Passed</div>
                <div class="results-score" id="resultsScore">85%</div>
            </div>
            
            <div class="results-stats">
                <div class="stat-item">
                    <div class="stat-circle">
                        <span class="stat-number" id="totalScenarios">5</span>
                    </div>
                    <div class="stat-label">Total Item of<br>scenario</div>
                </div>
                
                <div class="stat-item">
                    <div class="stat-circle">
                        <span class="stat-number" id="wrongAnswers">1</span>
                    </div>
                    <div class="stat-label">Wrong<br>Answer</div>
                </div>
            </div>
            
            <div class="results-actions">
                <button class="btn btn-primary" id="retryBtn">Try Again</button>
                <button class="btn btn-secondary" onclick="window.location.href='dashboard.php'">Back to Dashboard</button>
            </div>
        </div>
    </div>
</div>

<!-- Simulation History Section -->
<div class="history-section">
    <h3>Recent Simulation Results</h3>
    <div class="history-table-container">
        <?php if (!empty($simulation_history)): ?>
            <table class="history-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Score</th>
                        <th>Scenarios</th>
                        <th>Correct</th>
                        <th>Wrong</th>
                        <th>Time</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($simulation_history as $record): ?>
                        <tr>
                            <td><?php echo date('M j, Y g:i A', strtotime($record['created_at'])); ?></td>
                            <td class="score-cell"><?php echo $record['score_percentage']; ?>%</td>
                            <td><?php echo $record['total_scenarios']; ?></td>
                            <td class="correct-cell"><?php echo $record['correct_answers']; ?></td>
                            <td class="wrong-cell"><?php echo $record['wrong_answers']; ?></td>
                            <td><?php echo gmdate("i:s", $record['completion_time_seconds']); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $record['status']; ?>">
                                    <?php echo ucfirst($record['status']); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="no-history">
                <p>No simulation results yet. Complete your first simulation to see your progress!</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="../assets/js/simulation.js"></script>
<link rel="stylesheet" href="../assets/css/simulation.css">

<?php
$content = ob_get_clean();
include '../layouts/main_layout.php';
?>