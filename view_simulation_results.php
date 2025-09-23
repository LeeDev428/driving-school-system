<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

$user_id = $_SESSION["id"];

// Get user's simulation results
try {
    $sql = "SELECT sr.*, u.full_name 
            FROM simulation_results sr 
            JOIN users u ON sr.user_id = u.id 
            WHERE sr.user_id = ? 
            ORDER BY sr.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Simulation Results - Driving School System</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        h1 {
            color: #2c3e50;
            text-align: center;
            margin-bottom: 30px;
        }
        
        .results-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .results-table th, .results-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .results-table th {
            background: #3498db;
            color: white;
            font-weight: bold;
        }
        
        .results-table tr:hover {
            background: #f8f9fa;
        }
        
        .status-completed { color: #28a745; font-weight: bold; }
        .status-failed { color: #dc3545; font-weight: bold; }
        .status-abandoned { color: #6c757d; font-weight: bold; }
        
        .grade-A { background: #d4edda; color: #155724; padding: 4px 8px; border-radius: 4px; }
        .grade-B { background: #d1ecf1; color: #0c5460; padding: 4px 8px; border-radius: 4px; }
        .grade-C { background: #fff3cd; color: #856404; padding: 4px 8px; border-radius: 4px; }
        .grade-D { background: #f8d7da; color: #721c24; padding: 4px 8px; border-radius: 4px; }
        .grade-F { background: #f5c6cb; color: #721c24; padding: 4px 8px; border-radius: 4px; }
        
        .no-results {
            text-align: center;
            color: #6c757d;
            font-style: italic;
            padding: 40px;
        }
        
        .back-btn {
            display: inline-block;
            background: #3498db;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .back-btn:hover {
            background: #2980b9;
        }
        
        .details-btn {
            background: #17a2b8;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        }
        
        .details-btn:hover {
            background: #138496;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="user/dashboard.php" class="back-btn">← Back to Dashboard</a>
        
        <h1>🎮 My Simulation Results</h1>
        
        <?php if (empty($results)): ?>
            <div class="no-results">
                <h3>No simulation results yet</h3>
                <p>Complete the driving simulation to see your results here!</p>
                <a href="user/simulation.php" class="back-btn">Start Simulation</a>
            </div>
        <?php else: ?>
            <table class="results-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Scenarios</th>
                        <th>Correct</th>
                        <th>Wrong</th>
                        <th>Score %</th>
                        <th>Grade</th>
                        <th>Time</th>
                        <th>Status</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $result): ?>
                        <?php
                            $grade = '';
                            if ($result['score_percentage'] >= 90) $grade = 'A';
                            elseif ($result['score_percentage'] >= 80) $grade = 'B';
                            elseif ($result['score_percentage'] >= 70) $grade = 'C';
                            elseif ($result['score_percentage'] >= 60) $grade = 'D';
                            else $grade = 'F';
                            
                            $timeFormatted = gmdate("i:s", $result['completion_time_seconds']);
                        ?>
                        <tr>
                            <td><?php echo date('M j, Y g:i A', strtotime($result['created_at'])); ?></td>
                            <td><?php echo ucfirst(str_replace('_', ' ', $result['simulation_type'])); ?></td>
                            <td><?php echo $result['total_scenarios']; ?></td>
                            <td style="color: #28a745; font-weight: bold;"><?php echo $result['correct_answers']; ?></td>
                            <td style="color: #dc3545; font-weight: bold;"><?php echo $result['wrong_answers']; ?></td>
                            <td style="font-weight: bold;"><?php echo number_format($result['score_percentage'], 1); ?>%</td>
                            <td><span class="grade-<?php echo $grade; ?>"><?php echo $grade; ?></span></td>
                            <td><?php echo $timeFormatted; ?></td>
                            <td><span class="status-<?php echo $result['status']; ?>"><?php echo ucfirst($result['status']); ?></span></td>
                            <td>
                                <button class="details-btn" onclick="showDetails(<?php echo htmlspecialchars(json_encode($result['scenarios_data'])); ?>)">
                                    View Details
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    
    <!-- Details Modal -->
    <div id="detailsModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 30px; border-radius: 10px; max-width: 800px; width: 90%; max-height: 80%; overflow-y: auto;">
            <h3>Scenario Details</h3>
            <div id="detailsContent"></div>
            <button onclick="closeDetails()" style="background: #dc3545; color: white; border: none; padding: 10px 20px; border-radius: 5px; margin-top: 20px; cursor: pointer;">Close</button>
        </div>
    </div>
    
    <script>
        function showDetails(scenariosData) {
            const modal = document.getElementById('detailsModal');
            const content = document.getElementById('detailsContent');
            
            if (!scenariosData) {
                content.innerHTML = '<p>No detailed data available for this simulation.</p>';
            } else {
                let html = '<table style="width: 100%; border-collapse: collapse;">';
                html += '<tr style="background: #f8f9fa;"><th style="padding: 8px; border: 1px solid #ddd;">Scenario</th><th style="padding: 8px; border: 1px solid #ddd;">Your Answer</th><th style="padding: 8px; border: 1px solid #ddd;">Correct</th><th style="padding: 8px; border: 1px solid #ddd;">Points</th></tr>';
                
                scenariosData.forEach((scenario, index) => {
                    const correctIcon = scenario.is_correct ? '✅' : '❌';
                    const bgColor = scenario.is_correct ? '#d4edda' : '#f8d7da';
                    
                    html += `<tr style="background: ${bgColor};">`;
                    html += `<td style="padding: 8px; border: 1px solid #ddd;">Scenario ${scenario.scenario_id}</td>`;
                    html += `<td style="padding: 8px; border: 1px solid #ddd;">Option ${String.fromCharCode(65 + scenario.selected_option)}</td>`;
                    html += `<td style="padding: 8px; border: 1px solid #ddd;">${correctIcon}</td>`;
                    html += `<td style="padding: 8px; border: 1px solid #ddd;">${scenario.points_earned}</td>`;
                    html += '</tr>';
                });
                
                html += '</table>';
                content.innerHTML = html;
            }
            
            modal.style.display = 'block';
        }
        
        function closeDetails() {
            document.getElementById('detailsModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        document.getElementById('detailsModal').onclick = function(e) {
            if (e.target === this) {
                closeDetails();
            }
        }
    </script>
</body>
</html>