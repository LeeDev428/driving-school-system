<?php
// Simple test page to debug AJAX issues
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    echo "Not logged in";
    exit;
}

// Include database connection
require_once "config.php";

// Test basic JSON response
if (isset($_GET['test']) && $_GET['test'] == 'json') {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success', 'message' => 'JSON test successful']);
    exit;
}

// Test database connection
if (isset($_GET['test']) && $_GET['test'] == 'db') {
    header('Content-Type: application/json');
    
    $sql = "SELECT COUNT(*) as count FROM users WHERE id = ?";
    $user_id = $_SESSION["id"];
    
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        echo json_encode(['status' => 'success', 'user_count' => $row['count']]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error']);
    }
    exit;
}

// Test appointments query
if (isset($_GET['test']) && $_GET['test'] == 'appointments') {
    header('Content-Type: application/json');
    
    $user_id = $_SESSION["id"];
    $year = date('Y');
    $month = date('n');
    
    $sql = "SELECT a.*, at.name as type_name, at.color, u.full_name as instructor_name 
            FROM appointments a 
            LEFT JOIN appointment_types at ON a.appointment_type_id = at.id
            LEFT JOIN instructors i ON a.instructor_id = i.id
            LEFT JOIN users u ON i.user_id = u.id
            WHERE a.student_id = ? AND YEAR(a.appointment_date) = ? AND MONTH(a.appointment_date) = ?
            ORDER BY a.appointment_date, a.start_time
            LIMIT 5";
    
    $events = [];
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "iii", $user_id, $year, $month);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        while ($row = mysqli_fetch_assoc($result)) {
            $events[] = [
                'id' => $row['id'],
                'date' => $row['appointment_date'],
                'time' => date('g:i A', strtotime($row['start_time'])),
                'type' => $row['type_name'],
                'instructor' => $row['instructor_name'],
                'status' => $row['status'],
                'color' => $row['color']
            ];
        }
        mysqli_stmt_close($stmt);
        
        echo json_encode(['status' => 'success', 'events' => $events, 'count' => count($events)]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Query failed']);
    }
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>AJAX Test</title>
</head>
<body>
    <h1>AJAX Testing Page</h1>
    
    <button onclick="testJson()">Test JSON Response</button>
    <button onclick="testDatabase()">Test Database</button>
    <button onclick="testAppointments()">Test Appointments Query</button>
    
    <div id="results" style="margin-top: 20px; padding: 10px; border: 1px solid #ccc; background: #f9f9f9;"></div>
    
    <script>
    function testJson() {
        fetch('?test=json')
            .then(response => response.text())
            .then(text => {
                document.getElementById('results').innerHTML = '<h3>JSON Test Raw Response:</h3><pre>' + text + '</pre>';
                try {
                    const data = JSON.parse(text);
                    document.getElementById('results').innerHTML += '<h3>Parsed JSON:</h3><pre>' + JSON.stringify(data, null, 2) + '</pre>';
                } catch (e) {
                    document.getElementById('results').innerHTML += '<h3>JSON Parse Error:</h3><pre>' + e.message + '</pre>';
                }
            });
    }
    
    function testDatabase() {
        fetch('?test=db')
            .then(response => response.text())
            .then(text => {
                document.getElementById('results').innerHTML = '<h3>Database Test Raw Response:</h3><pre>' + text + '</pre>';
                try {
                    const data = JSON.parse(text);
                    document.getElementById('results').innerHTML += '<h3>Parsed JSON:</h3><pre>' + JSON.stringify(data, null, 2) + '</pre>';
                } catch (e) {
                    document.getElementById('results').innerHTML += '<h3>JSON Parse Error:</h3><pre>' + e.message + '</pre>';
                }
            });
    }
    
    function testAppointments() {
        fetch('?test=appointments')
            .then(response => response.text())
            .then(text => {
                document.getElementById('results').innerHTML = '<h3>Appointments Test Raw Response:</h3><pre>' + text + '</pre>';
                try {
                    const data = JSON.parse(text);
                    document.getElementById('results').innerHTML += '<h3>Parsed JSON:</h3><pre>' + JSON.stringify(data, null, 2) + '</pre>';
                } catch (e) {
                    document.getElementById('results').innerHTML += '<h3>JSON Parse Error:</h3><pre>' + e.message + '</pre>';
                }
            });
    }
    </script>
</body>
</html>
