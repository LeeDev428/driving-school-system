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
$page_title = "Quiz";

// Check if user has passed assessment
$assessment_passed = false;
$check_sql = "SELECT passed FROM user_assessment_sessions 
              WHERE user_id = ? AND status = 'completed' AND passed = 1 
              ORDER BY time_completed DESC LIMIT 1";
if ($stmt = mysqli_prepare($conn, $check_sql)) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($result)) {
        $assessment_passed = true;
    }
    mysqli_stmt_close($stmt);
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    // Start Quiz
    if ($_POST['action'] == 'start_quiz') {
        if (!$assessment_passed) {
            echo json_encode(['success' => false, 'message' => 'You must pass the Assessment first!']);
            exit;
        }
        
        $session_id = 'QUIZ_' . $user_id . '_' . time();
        
        $sql = "INSERT INTO user_quiz_sessions (user_id, session_id, total_questions, status) 
                VALUES (?, ?, 50, 'in_progress')";
        
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "is", $user_id, $session_id);
            
            if (mysqli_stmt_execute($stmt)) {
                echo json_encode(['success' => true, 'session_id' => $session_id]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to start quiz']);
            }
            mysqli_stmt_close($stmt);
        }
        exit;
    }
    
    // Get Quiz Questions
    if ($_POST['action'] == 'get_quiz_questions') {
        $sql = "SELECT id, question_number, question_text, option_a, option_b, option_c, option_d, category 
                FROM quizzes 
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
    
    // Submit Quiz
    if ($_POST['action'] == 'submit_quiz') {
        $session_id = $_POST['session_id'];
        $answers = json_decode($_POST['answers'], true);
        
        $correct_count = 0;
        $wrong_count = 0;
        
        foreach ($answers as $question_id => $user_answer) {
            $sql = "SELECT correct_answer, question_number FROM quizzes WHERE id = ?";
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
            
            $sql = "INSERT INTO user_quiz_responses 
                    (session_id, user_id, question_id, question_number, user_answer, correct_answer, is_correct) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "siiissi", $session_id, $user_id, $question_id, 
                                   $question_number, $user_answer, $correct_answer, $is_correct);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
        
        $score_percentage = ($correct_count / 50) * 100;
        $passed = ($score_percentage >= 70) ? 1 : 0;
        
        $sql = "UPDATE user_quiz_sessions 
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

// Get user's quiz history
$history_sql = "SELECT * FROM user_quiz_sessions 
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
    /* Copy all styles from assessments.php */
    .quiz-container {
        max-width: 900px;
        margin: 0 auto;
        padding: 20px;
    }
    
    .quiz-header {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        color: white;
        padding: 30px;
        border-radius: 15px;
        text-align: center;
        margin-bottom: 30px;
        box-shadow: 0 10px 30px rgba(240, 147, 251, 0.3);
    }
    
    .quiz-header h1 {
        margin: 0 0 10px 0;
        font-size: 36px;
    }
    
    .locked-card {
        background: white;
        border-radius: 15px;
        padding: 40px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        text-align: center;
    }
    
    .locked-icon {
        font-size: 80px;
        color: #dc3545;
        margin-bottom: 20px;
    }
    
    .locked-title {
        font-size: 28px;
        color: #dc3545;
        margin-bottom: 15px;
        font-weight: bold;
    }
    
    .locked-message {
        font-size: 18px;
        color: #6c757d;
        margin-bottom: 30px;
    }
    
    .goto-assessment-btn {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        padding: 15px 40px;
        font-size: 18px;
        font-weight: bold;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .goto-assessment-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
    }
    
    /* Reuse most styles from assessments.php for consistency */
    .start-card, .quiz-info, .info-item, .start-btn, #quizContainer,
    .question-card, .question-header, .question-number, .question-category,
    .question-text, .answer-options, .progress-bar-container, .progress-bar-fill,
    .submit-section, .submit-btn, #resultsContainer, .results-card, .results-icon,
    .results-title, .results-stats, .stat-box, .stat-value, .stat-label,
    .results-actions, .results-btn, .history-section, .history-table, .badge {
        /* Copy styles from assessments.php */
    }
    
    .start-card {
        background: white;
        border-radius: 15px;
        padding: 40px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        text-align: center;
    }
    
    .quiz-info {
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
        color: #f5576c;
        margin-right: 10px;
        font-size: 20px;
    }
    
    .info-item span {
        color: #2c3e50;
    }
    
    .start-btn {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
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
        box-shadow: 0 5px 15px rgba(245, 87, 108, 0.4);
    }
    
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
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        color: white;
        padding: 8px 15px;
        border-radius: 8px;
        font-weight: bold;
    }
    
    .question-category {
        background: #fff3cd;
        color: #856404;
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
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
    }
    
    .answer-btn {
        padding: 15px;
        border: 3px solid #e0e0e0;
        background: white;
        border-radius: 12px;
        cursor: pointer;
        transition: all 0.3s ease;
        font-size: 16px;
        text-align: left;
    }
    
    .answer-btn:hover {
        border-color: #f5576c;
        transform: translateY(-2px);
    }
    
    .answer-btn.selected {
        border-color: #f5576c;
        background: #fff0f3;
    }
    
    .progress-bar-container {
        background: #e0e0e0;
        border-radius: 10px;
        height: 10px;
        margin-bottom: 30px;
        overflow: hidden;
    }
    
    .progress-bar-fill {
        background: linear-gradient(90deg, #f093fb 0%, #f5576c 100%);
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
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        color: white;
    }
    
    .results-btn.secondary {
        background: #e9ecef;
        color: #495057;
    }
    
    .results-btn:hover {
        transform: translateY(-2px);
    }
    
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

<div class="quiz-container">
    <div class="quiz-header">
        <h1>📚 Multiple Choice Quiz</h1>
        <p>50 questions covering traffic rules, road signs, and safety procedures</p>
    </div>
    
    <?php if (!$assessment_passed): ?>
    <!-- Locked State -->
    <div class="locked-card">
        <div class="locked-icon">🔒</div>
        <div class="locked-title">Quiz Locked</div>
        <div class="locked-message">
            You must complete and pass the Assessment first before taking this Quiz.
        </div>
        <button class="goto-assessment-btn" onclick="window.location.href='assessments.php'">
            <i class="fas fa-arrow-right"></i> Go to Assessment
        </button>
    </div>
    
    <?php else: ?>
    <!-- Unlocked - Start Screen -->
    <div id="startScreen">
        <div class="start-card">
            <h2>Ready to Start?</h2>
            <div class="quiz-info">
                <div class="info-item">
                    <i class="fas fa-question-circle"></i>
                    <span><strong>50 Questions</strong> - Multiple choice (a, b, c, d)</span>
                </div>
                <div class="info-item">
                    <i class="fas fa-clock"></i>
                    <span><strong>10 Seconds Per Question</strong> - Timer auto-submits if no answer</span>
                </div>
                <div class="info-item">
                    <i class="fas fa-check-circle"></i>
                    <span><strong>Passing Score:</strong> 70% (35 out of 50 correct)</span>
                </div>
                <div class="info-item">
                    <i class="fas fa-clipboard-list"></i>
                    <span><strong>Categories:</strong> Traffic Lights, Road Signs, Markings, Emergency, Rules, Protocol Plates</span>
                </div>
            </div>
            <button class="start-btn" onclick="startQuiz()">
                <i class="fas fa-play"></i> Start Quiz
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
                            <td><?php echo date('M j, Y g:i A', strtotime($attempt['time_completed'])); ?></td>
                            <td><strong><?php echo number_format($attempt['score_percentage'], 1); ?>%</strong></td>
                            <td><?php echo $attempt['correct_answers']; ?> / <?php echo $attempt['wrong_answers']; ?></td>
                            <td>
                                <span class="badge <?php echo $attempt['passed'] ? 'passed' : 'failed'; ?>">
                                    <?php echo $attempt['passed'] ? 'PASSED' : 'FAILED'; ?>
                                </span>
                            </td>
                            <td><?php echo gmdate('i:s', $attempt['duration_seconds']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <div id="quizContainer"></div>
    <div id="resultsContainer"></div>
    <?php endif; ?>
</div>

<script>
let currentSessionId = null;
let questions = [];
let userAnswers = {};
let currentQuestionIndex = 0;
let timer = null;
let timeLeft = 10;

async function startQuiz() {
    try {
        const response = await fetch('quizzes.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=start_quiz'
        });
        
        const data = await response.json();
        
        if (data.success) {
            currentSessionId = data.session_id;
            await loadQuestions();
        } else {
            alert(data.message || 'Failed to start quiz');
        }
    } catch (error) {
        console.error('Error starting quiz:', error);
        alert('Failed to start quiz. Please try again.');
    }
}

async function loadQuestions() {
    try {
        const response = await fetch('quizzes.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=get_quiz_questions'
        });
        
        const data = await response.json();
        
        if (data.success) {
            questions = data.questions;
            currentQuestionIndex = 0;
            userAnswers = {};
            document.getElementById('startScreen').style.display = 'none';
            document.getElementById('quizContainer').style.display = 'block';
            showQuestion();
        }
    } catch (error) {
        console.error('Error loading questions:', error);
    }
}

function showQuestion() {
    if (currentQuestionIndex >= questions.length) {
        submitQuiz();
        return;
    }
    
    const q = questions[currentQuestionIndex];
    const container = document.getElementById('quizContainer');
    
    const html = `
        <div class="progress-bar-container">
            <div class="progress-bar-fill" id="progressBar" style="width: ${((currentQuestionIndex + 1) / questions.length) * 100}%"></div>
        </div>
        
        <div class="question-card">
            <div class="question-header">
                <div class="question-number">Question ${q.question_number}/50</div>
                <div class="question-category">${q.category}</div>
            </div>
            
            <div style="text-align: center; margin-bottom: 20px;">
                <div style="font-size: 48px; font-weight: bold; color: #f5576c;" id="timerDisplay">10</div>
                <div style="font-size: 14px; color: #6c757d;">seconds remaining</div>
            </div>
            
            <div class="question-text">${q.question_text}</div>
            <div class="answer-options">
                <button class="answer-btn" onclick="selectAnswer(${q.id}, 'a')">
                    <strong>A)</strong> ${q.option_a}
                </button>
                <button class="answer-btn" onclick="selectAnswer(${q.id}, 'b')">
                    <strong>B)</strong> ${q.option_b}
                </button>
                <button class="answer-btn" onclick="selectAnswer(${q.id}, 'c')">
                    <strong>C)</strong> ${q.option_c}
                </button>
                <button class="answer-btn" onclick="selectAnswer(${q.id}, 'd')">
                    <strong>D)</strong> ${q.option_d}
                </button>
            </div>
        </div>
    `;
    
    container.innerHTML = html;
    startTimer();
}

function startTimer() {
    timeLeft = 10;
    clearInterval(timer);
    
    timer = setInterval(() => {
        timeLeft--;
        document.getElementById('timerDisplay').textContent = timeLeft;
        
        if (timeLeft <= 3) {
            document.getElementById('timerDisplay').style.color = '#dc3545';
        }
        
        if (timeLeft <= 0) {
            clearInterval(timer);
            // Mark as wrong (no answer)
            const q = questions[currentQuestionIndex];
            userAnswers[q.id] = null; // No answer = wrong
            currentQuestionIndex++;
            showQuestion();
        }
    }, 1000);
}

function selectAnswer(questionId, answer) {
    clearInterval(timer);
    
    // Highlight selected answer
    const buttons = document.querySelectorAll('.answer-btn');
    buttons.forEach(btn => btn.classList.remove('selected'));
    event.target.closest('.answer-btn').classList.add('selected');
    
    userAnswers[questionId] = answer;
    
    // Wait 500ms then move to next question
    setTimeout(() => {
        currentQuestionIndex++;
        showQuestion();
    }, 500);
}

async function submitQuiz() {
    clearInterval(timer);
    
    try {
        const response = await fetch('quizzes.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `action=submit_quiz&session_id=${currentSessionId}&answers=${JSON.stringify(userAnswers)}`
        });
        
        const data = await response.json();
        
        if (data.success) {
            showResults(data);
        }
    } catch (error) {
        console.error('Error submitting quiz:', error);
        alert('Failed to submit quiz. Please try again.');
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
                    <div class="stat-value" style="color: #f5576c;">${data.score.toFixed(1)}%</div>
                    <div class="stat-label">Score</div>
                </div>
            </div>
            
            <div class="results-actions">
                <button class="results-btn secondary" onclick="location.href='e-learning.php'">
                    <i class="fas fa-arrow-left"></i> Back to E-Learning
                </button>
                <button class="results-btn primary" onclick="location.reload()">
                    <i class="fas fa-redo"></i> Retake Quiz
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
