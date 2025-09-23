<?php
require_once 'config.php';

echo "<h2>Database Column Check</h2>";

try {
    // Check quiz_responses table structure
    echo "<h3>quiz_responses columns:</h3>";
    $stmt = $pdo->query("DESCRIBE quiz_responses");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<ul>";
    foreach ($columns as $column) {
        echo "<li><strong>{$column['Field']}</strong> - {$column['Type']}</li>";
    }
    echo "</ul>";
    
    // Check actual data in quiz_responses
    echo "<h3>Sample quiz_responses data:</h3>";
    $stmt = $pdo->query("SELECT * FROM quiz_responses ORDER BY answered_at DESC LIMIT 3");
    $responses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($responses) {
        echo "<pre>";
        print_r($responses);
        echo "</pre>";
    } else {
        echo "<p>No data found in quiz_responses table.</p>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>