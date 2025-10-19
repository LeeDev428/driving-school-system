<?php
// Check if vehicle_type column exists in simulation_results table
require_once 'config.php';

try {
    // Check table structure
    $stmt = $pdo->query("DESCRIBE simulation_results");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>simulation_results Table Structure:</h2>";
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    
    $hasVehicleType = false;
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . $column['Field'] . "</td>";
        echo "<td>" . $column['Type'] . "</td>";
        echo "<td>" . $column['Null'] . "</td>";
        echo "<td>" . $column['Key'] . "</td>";
        echo "<td>" . $column['Default'] . "</td>";
        echo "</tr>";
        
        if ($column['Field'] === 'vehicle_type') {
            $hasVehicleType = true;
        }
    }
    echo "</table>";
    
    if (!$hasVehicleType) {
        echo "<h3 style='color: red;'>❌ vehicle_type column NOT FOUND!</h3>";
        echo "<p>You need to run this SQL:</p>";
        echo "<pre>ALTER TABLE `simulation_results` ADD COLUMN `vehicle_type` VARCHAR(20) NOT NULL DEFAULT 'car' AFTER `simulation_type`;</pre>";
    } else {
        echo "<h3 style='color: green;'>✅ vehicle_type column EXISTS!</h3>";
    }
    
    // Show recent data
    echo "<h2>Recent simulation_results Data:</h2>";
    $stmt = $pdo->query("SELECT * FROM simulation_results ORDER BY id DESC LIMIT 5");
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($results) > 0) {
        echo "<table border='1' cellpadding='10'>";
        echo "<tr>";
        foreach (array_keys($results[0]) as $header) {
            echo "<th>$header</th>";
        }
        echo "</tr>";
        
        foreach ($results as $row) {
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . htmlspecialchars($value) . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No data found.</p>";
    }
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>Error: " . $e->getMessage() . "</h3>";
}
?>
