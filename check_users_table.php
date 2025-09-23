<?php
require_once 'config.php';

echo "<h2>Users Table Structure</h2>";
$result = mysqli_query($conn, 'DESCRIBE users');
while($row = mysqli_fetch_assoc($result)) {
    echo "<p><strong>" . $row['Field'] . "</strong> - " . $row['Type'] . "</p>";
}

echo "<h2>Sample Users Data</h2>";
$result = mysqli_query($conn, 'SELECT * FROM users LIMIT 3');
while($row = mysqli_fetch_assoc($result)) {
    echo "<pre>";
    print_r($row);
    echo "</pre>";
}
?>