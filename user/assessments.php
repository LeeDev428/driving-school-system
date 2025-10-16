<?php
session_start();

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php");
    exit;
}

// Include database connection
require_once "../config.php";

$user_id = $_SESSION["id"];
$page_title = "Assessment";

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    // Start Assessment
    if ($_POST['action'] == 'start_assessment') {
        $session_id = 'ASSESS_' . $user_id . '_' . time();
        
        // Create assessment session
        $sql = "INSERT INTO user_assessment_sessions (user_id, session_id, total_questions, status) 
                VALUES (?, ?, 20, 'in_progress')";
        
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "is", $user_id, $session_id);
            
            if (mysqli_stmt_execute($stmt)) {
                echo json_encode(['success' => true, 'session_id' => $session_id]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to start assessment']);
            }
            mysqli_stmt_close($stmt);
        }
        exit;
    }
    
    // Get Assessment Questions
    if ($_POST['action'] == 'get_questions') {
        $sql = "SELECT id, question_number, question_text, category 
                FROM assessments 
                WHERE is_active = 1 
                ORDER BY question_number";
        
        $questions = [];
        if ($result = mysqli_query($conn, $sql)) {
            while ($row = mysqli_fetch_assoc($result)) {
                $questions[] = $row;
            }
            echo json_encode(['success' => true, 'questions' => $questions]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to fetch questions']);
        }
        exit;
    }
    
    // Submit Assessment
    if ($_POST['action'] == 'submit_assessment') {
        $session_id = $_POST['session_id'];
        $answers = json_decode($_POST['answers'], true);
        
        $correct_count = 0;
        $wrong_count = 0;
        
        // Process each answer
        foreach ($answers as $question_id => $user_answer) {
            // Get correct answer
            $sql = "SELECT correct_answer, question_number FROM assessments WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $question_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $question = mysqli_fetch_assoc($result);
            $correct_answer = $question['correct_answer'];
            $question_number = $question['question_number'];
            mysqli_stmt_close($stmt);
            
            $is_correct = ($user_answer == $correct_answer) ? 1 : 0;
            
            if ($is_correct) {
                $correct_count++;
            } else {
                $wrong_count++;
            }
            
            // Save response
            $sql = "INSERT INTO user_assessment_responses 
                    (session_id, user_id, question_id, question_number, user_answer, correct_answer, is_correct) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "siiissi", $session_id, $user_id, $question_id, 
                                   $question_number, $user_answer, $correct_answer, $is_correct);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
        
        // Calculate score
        $score_percentage = ($correct_count / 20) * 100;
        $passed = ($score_percentage >= 70) ? 1 : 0;
        
        // Update session
        $sql = "UPDATE user_assessment_sessions 
                SET correct_answers = ?, wrong_answers = ?, score_percentage = ?, passed = ?, 
                    time_completed = NOW(), status = 'completed',
                    duration_seconds = TIMESTAMPDIFF(SECOND, time_started, NOW())
                WHERE session_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "iidis", $correct_count, $wrong_count, $score_percentage, $passed, $session_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        echo json_encode([
            'success' => true,
            'correct' => $correct_count,
            'wrong' => $wrong_count,
            'score' => $score_percentage,
            'passed' => $passed
        ]);
        exit;
    }
}

// Get user's assessment history
$history_sql = "SELECT * FROM user_assessment_sessions 
                WHERE user_id = ? AND status = 'completed' 
                ORDER BY time_completed DESC LIMIT 10";
$history = [];
if ($stmt = mysqli_prepare($conn, $history_sql)) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $history[] = $row;
    }
    mysqli_stmt_close($stmt);
}

ob_start();
?>

<style>
    .assessment-container {
        max-width: 900px;
        margin: 0 auto;
        padding: 20px;
    }
    
    .assessment-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 30px;
        border-radius: 15px;
        text-align: center;
        margin-bottom: 30px;
        box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
    }
    
    .assessment-header h1 {
        margin: 0 0 10px 0;
        font-size: 36px;
    }
    
    .assessment-header p {
        margin: 0;
        opacity: 0.9;
        font-size: 16px;
    }
    
    .start-card {
        background: white;
        border-radius: 15px;
        padding: 40px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        text-align: center;
    }
    
    .assessment-info {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 20px;
        margin: 20px 0;
        text-align: left;
    }
    
    .info-item {
        display: flex;
        align-items: center;
        margin: 10px 0;
        font-size: 16px;
        color: #2c3e50;
    }
    
    .info-item i {
        color: #667eea;
        margin-right: 10px;
        font-size: 20px;
    }
    
    .info-item span {
        color: #2c3e50;
    }
    
    .start-btn {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        padding: 15px 40px;
        font-size: 18px;
        font-weight: bold;
        border-radius: 10px;
        cursor: pointer;
        margin-top: 20px;
        transition: all 0.3s ease;
    }
    
    .start-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
    }
    
    /* Quiz Container */
    #quizContainer {
        display: none;
    }
    
    .question-card {
        background: white;
        border-radius: 15px;
        padding: 30px;
        margin-bottom: 20px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    }
    
    .question-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 2px solid #f0f0f0;
    }
    
    .question-number {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 8px 15px;
        border-radius: 8px;
        font-weight: bold;
    }
    
    .question-category {
        background: #e3f2fd;
        color: #1976d2;
        padding: 8px 15px;
        border-radius: 8px;
        font-size: 14px;
    }
    
    .question-text {
        font-size: 18px;
        line-height: 1.6;
        margin-bottom: 25px;
        color: #2c3e50;
    }
    
    .answer-options {
        display: flex;
        gap: 15px;
    }
    
    .answer-btn {
        flex: 1;
        padding: 20px;
        border: 3px solid #e0e0e0;
        background: white;
        border-radius: 12px;
        cursor: pointer;
        transition: all 0.3s ease;
        font-size: 18px;
        font-weight: bold;
    }
    
    .answer-btn:hover {
        border-color: #667eea;
        transform: translateY(-2px);
    }
    
    .answer-btn.true-btn {
        color: #28a745;
    }
    
    .answer-btn.false-btn {
        color: #dc3545;
    }
    
    .answer-btn.selected {
        border-color: #667eea;
        background: #f0f4ff;
    }
    
    .answer-btn.true-btn.selected {
        border-color: #28a745;
        background: #d4edda;
    }
    
    .answer-btn.false-btn.selected {
        border-color: #dc3545;
        background: #f8d7da;
    }
    
    .progress-bar-container {
        background: #e0e0e0;
        border-radius: 10px;
        height: 10px;
        margin-bottom: 30px;
        overflow: hidden;
    }
    
    .progress-bar-fill {
        background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
        height: 100%;
        transition: width 0.3s ease;
    }
    
    .submit-section {
        text-align: center;
        margin-top: 30px;
    }
    
    .submit-btn {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        color: white;
        border: none;
        padding: 15px 50px;
        font-size: 18px;
        font-weight: bold;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .submit-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(40, 167, 69, 0.4);
    }
    
    .submit-btn:disabled {
        background: #6c757d;
        cursor: not-allowed;
        transform: none;
    }
    
    /* Results */
    #resultsContainer {
        display: none;
    }
    
    .results-card {
        background: white;
        border-radius: 15px;
        padding: 40px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        text-align: center;
    }
    
    .results-icon {
        font-size: 80px;
        margin-bottom: 20px;
    }
    
    .results-title {
        font-size: 32px;
        margin-bottom: 10px;
        font-weight: bold;
    }
    
    .results-title.passed {
        color: #28a745;
    }
    
    .results-title.failed {
        color: #dc3545;
    }
    
    .results-stats {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
        margin: 30px 0;
    }
    
    .stat-box {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 10px;
    }
    
    .stat-value {
        font-size: 36px;
        font-weight: bold;
        margin-bottom: 5px;
    }
    
    .stat-label {
        color: #6c757d;
        font-size: 14px;
    }
    
    .results-actions {
        display: flex;
        gap: 15px;
        justify-content: center;
        margin-top: 30px;
    }
    
    .results-btn {
        padding: 12px 30px;
        border: none;
        border-radius: 8px;
        font-size: 16px;
        font-weight: bold;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .results-btn.primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    
    .results-btn.secondary {
        background: #e9ecef;
        color: #495057;
    }
    
    .results-btn:hover {
        transform: translateY(-2px);
    }
    
    /* History */
    .history-section {
        margin-top: 40px;
    }
    
    .history-table {
        width: 100%;
        background: white;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    }
    
    .history-table table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .history-table th {
        background: #f8f9fa;
        padding: 15px;
        text-align: left;
        font-weight: bold;
        color: #495057;
    }
    
    .history-table td {
        padding: 15px;
        border-top: 1px solid #dee2e6;
    }
    
    .badge {
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: bold;
    }
    
    .badge.passed {
        background: #d4edda;
        color: #155724;
    }
    
    .badge.failed {
        background: #f8d7da;
        color: #721c24;
    }
</style>

<div class="assessment-container">
    <div class="assessment-header">
        <h1>📝 True or False Assessment</h1>
        <p>Test your knowledge of traffic rules, road signs, and safety procedures</p>
    </div>
    
    <!-- Start Screen -->
    <div id="startScreen">
        <div class="start-card">
            <h2>Ready to Start?</h2>
            <div class="assessment-info">
                <div class="info-item">
                    <i class="fas fa-question-circle"></i>
                    <span><strong>20 Questions</strong> - True or False format</span>
                </div>
                <div class="info-item">
                    <i class="fas fa-clock"></i>
                    <span><strong>No Time Limit</strong> - Take your time to think</span>
                </div>
                <div class="info-item">
                    <i class="fas fa-check-circle"></i>
                    <span><strong>Passing Score:</strong> 70% (14 out of 20 correct)</span>
                </div>
                <div class="info-item">
                    <i class="fas fa-clipboard-list"></i>
                    <span><strong>Categories:</strong> Traffic Signs, Road Markings, Rules, Emergency Response</span>
                </div>
            </div>
            <button class="start-btn" onclick="startAssessment()">
                <i class="fas fa-play"></i> Start Assessment
            </button>
        </div>
        
        <?php if (!empty($history)): ?>
        <div class="history-section">
            <h3>Your Previous Attempts</h3>
            <div class="history-table">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Score</th>
                            <th>Correct/Wrong</th>
                            <th>Status</th>
                            <th>Duration</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($history as $attempt): ?>
                        <tr>
                            <td style="color: #2c3e50;"><?php echo date('M j, Y g:i A', strtotime($attempt['time_completed'])); ?></td>
                            <td style="color: #2c3e50;"><strong><?php echo number_format($attempt['score_percentage'], 1); ?>%</strong></td>
                            <td style="color: #2c3e50;"><?php echo $attempt['correct_answers']; ?> / <?php echo $attempt['wrong_answers']; ?></td>
                            <td>
                                <span class="badge <?php echo $attempt['passed'] ? 'passed' : 'failed'; ?>">
                                    <?php echo $attempt['passed'] ? 'PASSED' : 'FAILED'; ?>
                                </span>
                            </td>
                            <td style="color: #2c3e50;"><?php echo gmdate('i:s', $attempt['duration_seconds']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Quiz Container -->
    <div id="quizContainer"></div>
    
    <!-- Results Container -->
    <div id="resultsContainer"></div>
</div>

<script>
let currentSessionId = null;
let questions = [];
let userAnswers = {};

async function startAssessment() {
    try {
        // Start session
        const response = await fetch('assessments.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=start_assessment'
        });
        
        const data = await response.json();
        
        if (data.success) {
            currentSessionId = data.session_id;
            await loadQuestions();
        }
    } catch (error) {
        console.error('Error starting assessment:', error);
        alert('Failed to start assessment. Please try again.');
    }
}

async function loadQuestions() {
    try {
        const response = await fetch('assessments.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=get_questions'
        });
        
        const data = await response.json();
        
        if (data.success) {
            questions = data.questions;
            displayQuestions();
            document.getElementById('startScreen').style.display = 'none';
            document.getElementById('quizContainer').style.display = 'block';
        }
    } catch (error) {
        console.error('Error loading questions:', error);
    }
}

function displayQuestions() {
    const container = document.getElementById('quizContainer');
    
    let html = `
        <div class="progress-bar-container">
            <div class="progress-bar-fill" id="progressBar" style="width: 0%"></div>
        </div>
    `;
    
    questions.forEach((q, index) => {
        html += `
            <div class="question-card">
                <div class="question-header">
                    <div class="question-number">Question ${q.question_number}/20</div>
                    <div class="question-category">${q.category}</div>
                </div>
                <div class="question-text">${q.question_text}</div>
                <div class="answer-options">
                    <button class="answer-btn true-btn" onclick="selectAnswer(${q.id}, 'True', this)">
                        <i class="fas fa-check-circle"></i> TRUE
                    </button>
                    <button class="answer-btn false-btn" onclick="selectAnswer(${q.id}, 'False', this)">
                        <i class="fas fa-times-circle"></i> FALSE
                    </button>
                </div>
            </div>
        `;
    });
    
    html += `
        <div class="submit-section">
            <button class="submit-btn" id="submitBtn" onclick="submitAssessment()" disabled>
                <i class="fas fa-paper-plane"></i> Submit Assessment
            </button>
        </div>
    `;
    
    container.innerHTML = html;
}

function selectAnswer(questionId, answer, button) {
    // Remove previous selection in this question
    const card = button.closest('.question-card');
    card.querySelectorAll('.answer-btn').forEach(btn => {
        btn.classList.remove('selected');
    });
    
    // Add selection to clicked button
    button.classList.add('selected');
    
    // Save answer
    userAnswers[questionId] = answer;
    
    // Update progress
    const progress = (Object.keys(userAnswers).length / questions.length) * 100;
    document.getElementById('progressBar').style.width = progress + '%';
    
    // Enable submit button if all answered
    document.getElementById('submitBtn').disabled = Object.keys(userAnswers).length !== questions.length;
}

async function submitAssessment() {
    if (!confirm('Are you sure you want to submit? You cannot change your answers after submission.')) {
        return;
    }
    
    try {
        const response = await fetch('assessments.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `action=submit_assessment&session_id=${currentSessionId}&answers=${JSON.stringify(userAnswers)}`
        });
        
        const data = await response.json();
        
        if (data.success) {
            showResults(data);
        }
    } catch (error) {
        console.error('Error submitting assessment:', error);
        alert('Failed to submit assessment. Please try again.');
    }
}

function showResults(data) {
    document.getElementById('quizContainer').style.display = 'none';
    
    const icon = data.passed ? '🎉' : '😢';
    const title = data.passed ? 'Congratulations! You Passed!' : 'Sorry, You Did Not Pass';
    const titleClass = data.passed ? 'passed' : 'failed';
    
    const html = `
        <div class="results-card">
            <div class="results-icon">${icon}</div>
            <div class="results-title ${titleClass}">${title}</div>
            <p>You scored ${data.score.toFixed(1)}% - ${data.passed ? 'Above' : 'Below'} the passing score of 70%</p>
            
            <div class="results-stats">
                <div class="stat-box">
                    <div class="stat-value" style="color: #28a745;">${data.correct}</div>
                    <div class="stat-label">Correct</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value" style="color: #dc3545;">${data.wrong}</div>
                    <div class="stat-label">Wrong</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value" style="color: #667eea;">${data.score.toFixed(1)}%</div>
                    <div class="stat-label">Score</div>
                </div>
            </div>
            
            <div class="results-actions">
                <button class="results-btn secondary" onclick="location.href='e-learning.php'">
                    <i class="fas fa-arrow-left"></i> Back to E-Learning
                </button>
                <button class="results-btn primary" onclick="location.reload()">
                    <i class="fas fa-redo"></i> Retake Assessment
                </button>
            </div>
        </div>
    `;
    
    document.getElementById('resultsContainer').innerHTML = html;
    document.getElementById('resultsContainer').style.display = 'block';
}
</script>

<?php
$content = ob_get_clean();
include '../layouts/main_layout.php';
?>
