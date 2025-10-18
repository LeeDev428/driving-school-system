<!DOCTYPE html>
<html>
<head>
    <title>Vehicle Type Fix - Comprehensive Test</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .test-section { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .test-section h2 { color: #2196F3; margin-top: 0; }
        .success { color: #4CAF50; font-weight: bold; }
        .error { color: #f44336; font-weight: bold; }
        .warning { color: #FF9800; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #2196F3; color: white; }
        tr:hover { background-color: #f5f5f5; }
        .code { background: #f5f5f5; padding: 10px; border-radius: 4px; font-family: monospace; margin: 10px 0; }
        .btn { background: #2196F3; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; margin: 5px; }
        .btn:hover { background: #1976D2; }
    </style>
</head>
<body>
    <h1>🔧 Vehicle Type Fix - Comprehensive Test Results</h1>
    
    <?php
    require_once '../config.php';
    
    // Test 1: Check database column exists
    echo '<div class="test-section">';
    echo '<h2>Test 1: Database Column Structure</h2>';
    
    try {
        $stmt = $pdo->query("DESCRIBE simulation_results");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $hasVehicleType = false;
        $vehicleTypeInfo = null;
        
        foreach ($columns as $column) {
            if ($column['Field'] === 'vehicle_type') {
                $hasVehicleType = true;
                $vehicleTypeInfo = $column;
            }
        }
        
        if ($hasVehicleType) {
            echo '<p class="success">✅ vehicle_type column EXISTS!</p>';
            echo '<div class="code">';
            echo "Field: vehicle_type<br>";
            echo "Type: " . $vehicleTypeInfo['Type'] . "<br>";
            echo "Null: " . $vehicleTypeInfo['Null'] . "<br>";
            echo "Default: " . ($vehicleTypeInfo['Default'] ?: 'NULL') . "<br>";
            echo '</div>';
        } else {
            echo '<p class="error">❌ vehicle_type column NOT FOUND!</p>';
            echo '<p>Run this SQL in HeidiSQL:</p>';
            echo '<div class="code">ALTER TABLE `simulation_results` ADD COLUMN `vehicle_type` VARCHAR(20) NOT NULL DEFAULT \'car\' AFTER `simulation_type`;</div>';
        }
        
    } catch (Exception $e) {
        echo '<p class="error">❌ Error: ' . $e->getMessage() . '</p>';
    }
    
    echo '</div>';
    
    // Test 2: Check recent simulation results
    echo '<div class="test-section">';
    echo '<h2>Test 2: Recent Simulation Results (Last 10)</h2>';
    
    try {
        $stmt = $pdo->query("
            SELECT id, user_id, simulation_type, vehicle_type, total_scenarios, 
                   correct_answers, wrong_answers, score_percentage, created_at 
            FROM simulation_results 
            ORDER BY id DESC 
            LIMIT 10
        ");
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($results) > 0) {
            echo '<table>';
            echo '<tr><th>ID</th><th>User</th><th>Type</th><th>Vehicle</th><th>Correct</th><th>Wrong</th><th>Score %</th><th>Date</th></tr>';
            
            foreach ($results as $row) {
                $vehicleIcon = $row['vehicle_type'] === 'motorcycle' ? '🏍️' : '🚗';
                $vehicleClass = $row['vehicle_type'] === 'motorcycle' ? 'success' : '';
                
                echo '<tr>';
                echo '<td>' . $row['id'] . '</td>';
                echo '<td>' . $row['user_id'] . '</td>';
                echo '<td>' . $row['simulation_type'] . '</td>';
                echo '<td class="' . $vehicleClass . '">' . $vehicleIcon . ' ' . $row['vehicle_type'] . '</td>';
                echo '<td>' . $row['correct_answers'] . '/' . $row['total_scenarios'] . '</td>';
                echo '<td>' . $row['wrong_answers'] . '</td>';
                echo '<td>' . $row['score_percentage'] . '%</td>';
                echo '<td>' . date('Y-m-d H:i:s', strtotime($row['created_at'])) . '</td>';
                echo '</tr>';
            }
            
            echo '</table>';
            
            // Analysis
            $carCount = 0;
            $motorcycleCount = 0;
            foreach ($results as $row) {
                if ($row['vehicle_type'] === 'car') $carCount++;
                if ($row['vehicle_type'] === 'motorcycle') $motorcycleCount++;
            }
            
            echo '<p><strong>Summary:</strong></p>';
            echo '<ul>';
            echo '<li>🚗 Car results: ' . $carCount . '</li>';
            echo '<li>🏍️ Motorcycle results: ' . $motorcycleCount . '</li>';
            echo '</ul>';
            
        } else {
            echo '<p class="warning">⚠️ No simulation results found yet.</p>';
        }
        
    } catch (Exception $e) {
        echo '<p class="error">❌ Error: ' . $e->getMessage() . '</p>';
    }
    
    echo '</div>';
    
    // Test 3: Check quiz_responses table
    echo '<div class="test-section">';
    echo '<h2>Test 3: Recent Quiz Sessions</h2>';
    
    try {
        $stmt = $pdo->query("
            SELECT qr.session_id, qr.user_id, qr.status, qr.started_at, qr.completed_at,
                   COUNT(qrr.id) as total_responses,
                   SUM(CASE WHEN qrr.is_correct = 1 THEN 1 ELSE 0 END) as correct_count
            FROM quiz_responses qr
            LEFT JOIN quiz_response_records qrr ON qr.session_id = qrr.session_id
            GROUP BY qr.session_id
            ORDER BY qr.id DESC
            LIMIT 5
        ");
        $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($sessions) > 0) {
            echo '<table>';
            echo '<tr><th>Session ID</th><th>User</th><th>Status</th><th>Responses</th><th>Correct</th><th>Started</th><th>Completed</th></tr>';
            
            foreach ($sessions as $session) {
                echo '<tr>';
                echo '<td>' . substr($session['session_id'], 0, 20) . '...</td>';
                echo '<td>' . $session['user_id'] . '</td>';
                echo '<td>' . $session['status'] . '</td>';
                echo '<td>' . $session['total_responses'] . '</td>';
                echo '<td>' . $session['correct_count'] . '</td>';
                echo '<td>' . ($session['started_at'] ? date('Y-m-d H:i', strtotime($session['started_at'])) : 'N/A') . '</td>';
                echo '<td>' . ($session['completed_at'] ? date('Y-m-d H:i', strtotime($session['completed_at'])) : 'N/A') . '</td>';
                echo '</tr>';
            }
            
            echo '</table>';
        } else {
            echo '<p class="warning">⚠️ No quiz sessions found yet.</p>';
        }
        
    } catch (Exception $e) {
        echo '<p class="error">❌ Error: ' . $e->getMessage() . '</p>';
    }
    
    echo '</div>';
    
    // Test 4: Cleanup options
    echo '<div class="test-section">';
    echo '<h2>Test 4: Cleanup Options</h2>';
    echo '<p>If you want to clear test data and start fresh:</p>';
    
    echo '<form method="POST" onsubmit="return confirm(\'Are you sure you want to delete test data?\');">';
    echo '<button type="submit" name="cleanup" value="recent" class="btn">Delete Last 5 Records</button>';
    echo '<button type="submit" name="cleanup" value="all" class="btn" style="background: #f44336;">Delete ALL Records (DANGER)</button>';
    echo '</form>';
    
    if (isset($_POST['cleanup'])) {
        try {
            if ($_POST['cleanup'] === 'recent') {
                $stmt = $pdo->query("DELETE FROM simulation_results ORDER BY id DESC LIMIT 5");
                echo '<p class="success">✅ Deleted last 5 records!</p>';
            } elseif ($_POST['cleanup'] === 'all') {
                $pdo->query("TRUNCATE TABLE simulation_results");
                echo '<p class="success">✅ All records deleted!</p>';
            }
            echo '<meta http-equiv="refresh" content="2">';
        } catch (Exception $e) {
            echo '<p class="error">❌ Cleanup error: ' . $e->getMessage() . '</p>';
        }
    }
    
    echo '</div>';
    
    // Test 5: Manual test instructions
    echo '<div class="test-section">';
    echo '<h2>Test 5: Manual Testing Steps</h2>';
    echo '<ol>';
    echo '<li>Clear test data using buttons above (recommended)</li>';
    echo '<li>Open <a href="simulation.php" target="_blank">simulation.php</a> in a new tab</li>';
    echo '<li><strong>SELECT MOTORCYCLE</strong> 🏍️ (important!)</li>';
    echo '<li>Open browser console (F12) and verify:<br><code>✅ SimulationConfig.vehicleType set to: motorcycle</code></li>';
    echo '<li>Complete all 5 scenarios (they appear every 3 seconds)</li>';
    echo '<li>Click "Proceed" on completion screen</li>';
    echo '<li>Refresh this page and check if latest record shows motorcycle</li>';
    echo '<li>Repeat test with CAR selection to verify both work</li>';
    echo '</ol>';
    echo '</div>';
    
    // Test 6: Expected console logs
    echo '<div class="test-section">';
    echo '<h2>Test 6: Expected Browser Console Logs</h2>';
    echo '<p>When you run the simulation, you should see these logs in the console:</p>';
    echo '<div class="code">';
    echo '🔧 SimulationConfig initialized with vehicleType: NONE (awaiting user selection)<br>';
    echo '✅ Vehicle selected: motorcycle<br>';
    echo '✅ SimulationConfig.vehicleType set to: motorcycle<br>';
    echo '📋 SimulationConfig.vehicleType: motorcycle<br>';
    echo '✅ Vehicle type set to: motorcycle<br>';
    echo '🏍️ Motorcycle dimensions set: width=30, height=15<br>';
    echo '🚗 Vehicle type from SimulationConfig: motorcycle<br>';
    echo '🔍 Full SimulationConfig: {vehicleType: "motorcycle", ...}<br>';
    echo '🚗 Final vehicle type check before save:<br>';
    echo '  - SimulationConfig.vehicleType: motorcycle<br>';
    echo '  - CarModule.vehicleType: motorcycle<br>';
    echo '  - ✅ Using: motorcycle<br>';
    echo '✅ Simulation results saved with vehicle type!<br>';
    echo '</div>';
    echo '</div>';
    ?>
    
    <div class="test-section">
        <h2>✅ Fix Summary</h2>
        <ul>
            <li>✅ Default vehicleType changed from 'car' to 'NONE' (forces user selection)</li>
            <li>✅ Added validation in continueToGame() to prevent starting without selection</li>
            <li>✅ Added triple-check in gameStats.js before saving (SimulationConfig → CarModule → fallback)</li>
            <li>✅ Added debug logging to trace vehicle type through entire flow</li>
            <li>✅ Updated car.js to reject 'NONE' value</li>
        </ul>
    </div>
    
    <div style="text-align: center; padding: 20px;">
        <a href="simulation.php" class="btn" style="font-size: 18px;">🎮 Start Simulation Test</a>
        <button onclick="location.reload()" class="btn">🔄 Refresh Results</button>
    </div>
</body>
</html>
