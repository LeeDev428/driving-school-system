<?php
// Database setup check
session_start();

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

require_once "config.php";

echo "<h2>Database Setup Check</h2>";

// Check if tables exist
$tables = ['users', 'appointment_types', 'instructors', 'vehicles', 'appointments'];
$missing_tables = [];

foreach ($tables as $table) {
    $result = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
    if (mysqli_num_rows($result) == 0) {
        $missing_tables[] = $table;
    }
}

if (!empty($missing_tables)) {
    echo "<p style='color: red;'>Missing tables: " . implode(', ', $missing_tables) . "</p>";
    echo "<p>Please run the database.sql file to create the required tables.</p>";
} else {
    echo "<p style='color: green;'>All required tables exist.</p>";
    
    // Check if appointment_types has data
    $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM appointment_types");
    $row = mysqli_fetch_assoc($result);
    echo "<p>Appointment types count: " . $row['count'] . "</p>";
    
    // Check if user exists
    $user_id = $_SESSION["id"];
    $result = mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id");
    if (mysqli_num_rows($result) > 0) {
        echo "<p style='color: green;'>User found in database.</p>";
    } else {
        echo "<p style='color: red;'>User not found in database.</p>";
    }
}

echo "<br><a href='user/appointments.php'>Back to Appointments</a>";
?>
