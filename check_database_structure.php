<?php
require_once 'config.php';

echo "=== CHECKING STATUS COLUMN ===\n\n";

// Check status column details
$result = $conn->query("SHOW COLUMNS FROM appointments WHERE Field = 'status'");
if ($row = $result->fetch_assoc()) {
    echo "Column: {$row['Field']}\n";
    echo "Type: {$row['Type']}\n";
    echo "Null: {$row['Null']}\n";
    echo "Default: {$row['Default']}\n";
}
