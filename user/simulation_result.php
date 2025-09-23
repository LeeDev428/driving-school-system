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

// Get the latest quiz session result for this user
try {
    $stmt = $pdo->prepare("
        SELECT qs.*, 
               (5 - qs.correct_answers) as wrong_answers,
               qs.completion_percentage as score_percentage,
               CASE 
                   WHEN qs.completion_percentage >= 60 THEN 'completed' 
                   ELSE 'failed' 
               END as status,
               qs.total_time_seconds as completion_time_seconds
        FROM quiz_sessions qs 
        WHERE qs.user_id = ? AND (qs.session_status = 'completed' OR qs.questions_answered = 5)
        ORDER BY qs.completed_at DESC, qs.updated_at DESC 
        LIMIT 1
    ");
    $stmt->execute([$user_id]);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($result)) {
        $simulation_result = null;
    } else {
        $simulation_result = $result[0];
        $simulation_result['total_scenarios'] = $simulation_result['total_questions'];
        
        // Get individual responses for this session
        $stmt = $pdo->prepare("
            SELECT scenario_id, question_text, selected_option, correct_option, 
                   is_correct, points_earned, answered_at
            FROM quiz_responses 
            WHERE session_id = ? 
            ORDER BY scenario_id
        ");
        $stmt->execute([$simulation_result['session_id']]);
        $simulation_result['scenarios'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // If no completed_at, use updated_at for display
        if (!$simulation_result['completed_at']) {
            $simulation_result['completed_at'] = $simulation_result['updated_at'];
        }
    }
} catch (Exception $e) {
    $error_message = "Error loading results: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simulation Results - Driving School System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .result-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        
        .result-header {
            background: linear-gradient(45deg, #4CAF50, #45a049);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .result-header.failed {
            background: linear-gradient(45deg, #f44336, #da190b);
        }
        
        .score-circle {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 2rem;
            font-weight: bold;
        }
        
        .scenario-card {
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            margin-bottom: 15px;
            overflow: hidden;
        }
        
        .scenario-header {
            padding: 15px;
            font-weight: bold;
            color: white;
        }
        
        .scenario-correct {
            background: #4CAF50;
        }
        
        .scenario-wrong {
            background: #f44336;
        }
        
        .scenario-body {
            padding: 15px;
            background: #f8f9fa;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        
        .stat-item {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #333;
        }
        
        .stat-label {
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> <?php echo $error_message; ?>
                    </div>
                <?php elseif (!$simulation_result): ?>
                    <div class="result-card">
                        <div class="result-header">
                            <h2><i class="fas fa-info-circle"></i> No Results Found</h2>
                            <p>You haven't completed any driving simulations yet.</p>
                        </div>
                        <div class="p-4 text-center">
                            <a href="simulation.php" class="btn btn-primary btn-lg">
                                <i class="fas fa-car"></i> Start Simulation
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="result-card">
                        <!-- Header -->
                        <div class="result-header <?php echo $simulation_result['status'] == 'failed' ? 'failed' : ''; ?>">
                            <div class="score-circle">
                                <?php echo round($simulation_result['score_percentage']); ?>%
                            </div>
                            <h2>
                                <i class="fas fa-<?php echo $simulation_result['status'] == 'completed' ? 'check-circle' : 'times-circle'; ?>"></i>
                                Simulation <?php echo ucfirst($simulation_result['status']); ?>
                            </h2>
                            <p>
                                Completed on <?php echo date('F j, Y \a\t g:i A', strtotime($simulation_result['completed_at'])); ?>
                            </p>
                        </div>
                        
                        <!-- Stats -->
                        <div class="p-4">
                            <div class="stats-grid">
                                <div class="stat-item">
                                    <div class="stat-number text-success"><?php echo $simulation_result['correct_answers']; ?></div>
                                    <div class="stat-label">Correct Answers</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-number text-danger"><?php echo $simulation_result['wrong_answers']; ?></div>
                                    <div class="stat-label">Wrong Answers</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-number text-primary"><?php echo $simulation_result['total_scenarios']; ?></div>
                                    <div class="stat-label">Total Scenarios</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-number text-info"><?php echo gmdate("i:s", $simulation_result['total_time_seconds'] ?? 0); ?></div>
                                    <div class="stat-label">Time Taken</div>
                                </div>
                            </div>
                            
                            <!-- Detailed Results -->
                            <?php if (isset($simulation_result['scenarios']) && is_array($simulation_result['scenarios'])): ?>
                                <h4 class="mt-4 mb-3"><i class="fas fa-list"></i> Detailed Results</h4>
                                
                                <?php foreach ($simulation_result['scenarios'] as $index => $scenario): ?>
                                    <div class="scenario-card">
                                        <div class="scenario-header <?php echo $scenario['is_correct'] ? 'scenario-correct' : 'scenario-wrong'; ?>">
                                            <i class="fas fa-<?php echo $scenario['is_correct'] ? 'check' : 'times'; ?>"></i>
                                            Scenario <?php echo $index + 1; ?>
                                            <span class="float-end">
                                                <?php echo $scenario['points_earned']; ?> points
                                            </span>
                                        </div>
                                        <div class="scenario-body">
                                            <h6><strong>Question:</strong></h6>
                                            <p><?php echo htmlspecialchars($scenario['question_text'] ?? 'Question not available'); ?></p>
                                            
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <h6><strong>Your Answer:</strong></h6>
                                                    <p class="<?php echo $scenario['is_correct'] ? 'text-success' : 'text-danger'; ?>">
                                                        <i class="fas fa-<?php echo $scenario['is_correct'] ? 'check' : 'times'; ?>"></i>
                                                        Option <?php echo $scenario['selected_option'] + 1; ?>
                                                    </p>
                                                </div>
                                                <?php if (!$scenario['is_correct']): ?>
                                                    <div class="col-md-6">
                                                        <h6><strong>Correct Answer:</strong></h6>
                                                        <p class="text-success">
                                                            <i class="fas fa-check"></i>
                                                            Option <?php echo $scenario['correct_option'] + 1; ?>
                                                        </p>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            
                            <!-- Action Buttons -->
                            <div class="text-center mt-4">
                                <a href="simulation.php" class="btn btn-primary btn-lg me-3">
                                    <i class="fas fa-redo"></i> Try Again
                                </a>
                                <a href="dashboard.php" class="btn btn-secondary btn-lg">
                                    <i class="fas fa-home"></i> Dashboard
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
