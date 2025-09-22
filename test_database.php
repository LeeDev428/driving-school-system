<?php
// Test database connection and check if simulation_results table exists
require_once 'config.php';

try {
    // Test basic connection
    echo "📡 Testing database connection...\n";
    $pdo->query("SELECT 1");
    echo "✅ Database connection successful!\n\n";
    
    // Check if simulation_results table exists
    echo "🔍 Checking simulation_results table...\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'simulation_results'");
    $table_exists = $stmt->rowCount() > 0;
    
    if ($table_exists) {
        echo "✅ simulation_results table exists!\n";
        
        // Show table structure
        echo "\n📋 Table structure:\n";
        $stmt = $pdo->query("DESCRIBE simulation_results");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "- {$row['Field']}: {$row['Type']}\n";
        }
        
        // Show recent records
        echo "\n📊 Recent records:\n";
        $stmt = $pdo->query("SELECT * FROM simulation_results ORDER BY created_at DESC LIMIT 5");
        $count = 0;
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $count++;
            echo "Record {$count}: User ID {$row['user_id']}, Score: {$row['score_percentage']}%, Status: {$row['status']}, Created: {$row['created_at']}\n";
        }
        
        if ($count === 0) {
            echo "No records found in simulation_results table.\n";
        }
        
    } else {
        echo "❌ simulation_results table does not exist!\n";
        echo "💡 Running table creation script...\n";
        
        // Create the table
        $sql = file_get_contents('add_simulation_table.sql');
        if ($sql) {
            $pdo->exec($sql);
            echo "✅ simulation_results table created successfully!\n";
        } else {
            echo "❌ Could not read add_simulation_table.sql file!\n";
        }
    }
    
    // Check if violation_logs table exists
    echo "\n🔍 Checking violation_logs table...\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'violation_logs'");
    $violation_table_exists = $stmt->rowCount() > 0;
    
    if ($violation_table_exists) {
        echo "✅ violation_logs table exists!\n";
    } else {
        echo "❌ violation_logs table does not exist (will be created automatically)!\n";
    }
    
    // Test save_simulation.php functionality
    echo "\n🧪 Testing save_simulation.php functionality...\n";
    
    $test_data = [
        'simulation_type' => 'test_driving',
        'total_scenarios' => 5,
        'correct_answers' => 4,
        'wrong_answers' => 1,
        'completion_time_seconds' => 300,
        'scenarios_data' => [
            ['scenario' => 1, 'answer' => 'correct'],
            ['scenario' => 2, 'answer' => 'correct'],
            ['scenario' => 3, 'answer' => 'wrong'],
            ['scenario' => 4, 'answer' => 'correct'],
            ['scenario' => 5, 'answer' => 'correct']
        ]
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost/driving-school-system/save_simulation.php');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($test_data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code === 200) {
        $result = json_decode($response, true);
        if ($result && isset($result['success']) && $result['success']) {
            echo "✅ save_simulation.php test successful!\n";
            echo "📝 Response: " . $response . "\n";
        } else {
            echo "❌ save_simulation.php test failed!\n";
            echo "📝 Response: " . $response . "\n";
        }
    } else {
        echo "❌ save_simulation.php HTTP error: {$http_code}\n";
        echo "📝 Response: " . $response . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Database test failed: " . $e->getMessage() . "\n";
}

echo "\n🏁 Database test completed!\n";
?>