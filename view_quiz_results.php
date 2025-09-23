<?php
session_start();

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

require_once "config.php";

$user_id = $_SESSION["id"];

// Get user's quiz sessions
$sql = "SELECT qs.*, 
        (SELECT COUNT(*) FROM quiz_responses qr WHERE qr.session_id = qs.session_id) as responses_count
        FROM quiz_sessions qs 
        WHERE qs.user_id = ? 
        ORDER BY qs.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$sessions = $stmt->fetchAll();

// Get detailed responses if session_id is provided
$detailed_responses = [];
if (isset($_GET['session_id'])) {
    $session_id = $_GET['session_id'];
    
    $sql = "SELECT qr.*, qs.session_status 
            FROM quiz_responses qr 
            JOIN quiz_sessions qs ON qr.session_id = qs.session_id 
            WHERE qr.session_id = ? AND qr.user_id = ?
            ORDER BY qr.scenario_id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$session_id, $user_id]);
    $detailed_responses = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Results - Driving School System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(45deg, #2c3e50, #34495e);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .content {
            padding: 30px;
        }
        
        .session-card {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            transition: transform 0.2s;
        }
        
        .session-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-completed { background: #d4edda; color: #155724; }
        .status-in_progress { background: #fff3cd; color: #856404; }
        .status-abandoned { background: #f8d7da; color: #721c24; }
        
        .responses-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .responses-table th,
        .responses-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .responses-table th {
            background: #f8f9fa;
            font-weight: bold;
        }
        
        .correct { color: #28a745; font-weight: bold; }
        .incorrect { color: #dc3545; font-weight: bold; }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 5px;
            transition: background 0.3s;
        }
        
        .btn:hover {
            background: #0056b3;
        }
        
        .btn-success { background: #28a745; }
        .btn-success:hover { background: #1e7e34; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎯 Driving Quiz Results</h1>
            <p>View your simulation quiz sessions and detailed responses</p>
        </div>
        
        <div class="content">
            <?php if (empty($sessions)): ?>
                <div style="text-align: center; padding: 50px;">
                    <h3>No Quiz Sessions Found</h3>
                    <p>You haven't completed any driving simulation quizzes yet.</p>
                    <a href="user/simulation.php" class="btn btn-success">Start Simulation</a>
                </div>
            <?php else: ?>
                <h2>Your Quiz Sessions</h2>
                
                <?php foreach ($sessions as $session): ?>
                    <div class="session-card">
                        <div style="display: flex; justify-content: between; align-items: center; margin-bottom: 15px;">
                            <h3>Session: <?= htmlspecialchars(substr($session['session_id'], -8)) ?></h3>
                            <span class="status-badge status-<?= $session['session_status'] ?>">
                                <?= ucfirst($session['session_status']) ?>
                            </span>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 15px;">
                            <div>
                                <strong>Questions Answered:</strong> <?= $session['questions_answered'] ?>/<?= $session['total_questions'] ?>
                            </div>
                            <div>
                                <strong>Correct Answers:</strong> <?= $session['correct_answers'] ?>
                            </div>
                            <div>
                                <strong>Total Points:</strong> <?= $session['total_points'] ?>/<?= $session['max_points'] ?>
                            </div>
                            <div>
                                <strong>Completion:</strong> <?= number_format($session['completion_percentage'], 1) ?>%
                            </div>
                            <div>
                                <strong>Started:</strong> <?= date('M j, Y H:i', strtotime($session['started_at'])) ?>
                            </div>
                            <?php if ($session['completed_at']): ?>
                            <div>
                                <strong>Completed:</strong> <?= date('M j, Y H:i', strtotime($session['completed_at'])) ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($session['responses_count'] > 0): ?>
                            <a href="?session_id=<?= urlencode($session['session_id']) ?>" class="btn">View Detailed Responses</a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                
                <?php if (!empty($detailed_responses)): ?>
                    <hr style="margin: 30px 0;">
                    <h2>Detailed Responses for Session: <?= htmlspecialchars(substr($_GET['session_id'], -8)) ?></h2>
                    
                    <table class="responses-table">
                        <thead>
                            <tr>
                                <th>Scenario</th>
                                <th>Question</th>
                                <th>Your Answer</th>
                                <th>Correct Answer</th>
                                <th>Result</th>
                                <th>Points</th>
                                <th>Answered At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($detailed_responses as $response): ?>
                                <tr>
                                    <td><?= $response['scenario_id'] ?></td>
                                    <td style="max-width: 300px; word-wrap: break-word;">
                                        <?= htmlspecialchars(substr($response['question_text'], 0, 100)) ?>...
                                    </td>
                                    <td>Option <?= chr(65 + $response['selected_option']) ?></td>
                                    <td>Option <?= chr(65 + $response['correct_option']) ?></td>
                                    <td class="<?= $response['is_correct'] ? 'correct' : 'incorrect' ?>">
                                        <?= $response['is_correct'] ? '✅ Correct' : '❌ Incorrect' ?>
                                    </td>
                                    <td><?= $response['points_earned'] ?></td>
                                    <td><?= date('H:i:s', strtotime($response['answered_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <div style="margin-top: 20px;">
                        <a href="view_quiz_results.php" class="btn">← Back to All Sessions</a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            
            <div style="margin-top: 30px; text-align: center;">
                <a href="user/dashboard.php" class="btn">← Back to Dashboard</a>
                <a href="user/simulation.php" class="btn btn-success">Take New Quiz</a>
            </div>
        </div>
    </div>
</body>
</html>