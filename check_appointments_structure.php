<?php
// Check appointments table structure
require_once "config.php";

echo "<h2>Checking Appointments Table Structure</h2>";

// Show columns in appointments table
$result = $conn->query("DESCRIBE appointments");
echo "<h3>Appointments Table Columns:</h3>";
echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$row['Field']}</td>";
    echo "<td>{$row['Type']}</td>";
    echo "<td>{$row['Null']}</td>";
    echo "<td>{$row['Key']}</td>";
    echo "<td>{$row['Default']}</td>";
    echo "<td>{$row['Extra']}</td>";
    echo "</tr>";
}
echo "</table>";

// Check if appointment_types table exists
$result = $conn->query("SHOW TABLES LIKE 'appointment_types'");
echo "<h3>Appointment Types Table:</h3>";
if ($result->num_rows > 0) {
    echo "✅ Table EXISTS<br>";
    
    // Show columns
    $result = $conn->query("DESCRIBE appointment_types");
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['Field']}</td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>{$row['Default']}</td>";
        echo "<td>{$row['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Show data
    $result = $conn->query("SELECT * FROM appointment_types");
    echo "<h4>Appointment Types Data:</h4>";
    echo "<pre>";
    while ($row = $result->fetch_assoc()) {
        print_r($row);
    }
    echo "</pre>";
} else {
    echo "❌ Table DOES NOT EXIST!<br>";
}

// Check sample appointment data
echo "<h3>Sample Appointments:</h3>";
$result = $conn->query("SELECT * FROM appointments LIMIT 3");
if ($result && $result->num_rows > 0) {
    echo "<pre>";
    while ($row = $result->fetch_assoc()) {
        print_r($row);
    }
    echo "</pre>";
} else {
    echo "No appointments found or query error: " . $conn->error;
}

// Test the actual query from dashboard
echo "<h3>Testing Dashboard Query:</h3>";
$user_id = 1; // Test with user ID 1
$query = "SELECT 
    a.id,
    a.appointment_date,
    a.start_time,
    a.status,
    a.course_selection,
    at.name as type_name,
    CONCAT(i.first_name, ' ', i.last_name) as instructor_name
    FROM appointments a
    LEFT JOIN appointment_types at ON a.appointment_type_id = at.id
    LEFT JOIN instructors i ON a.instructor_id = i.id
    WHERE a.student_id = ?
    ORDER BY a.appointment_date DESC, a.start_time DESC
    LIMIT 3";

$stmt = $conn->prepare($query);
if (!$stmt) {
    echo "❌ Query preparation failed: " . $conn->error . "<br>";
} else {
    $stmt->bind_param("i", $user_id);
    if (!$stmt->execute()) {
        echo "❌ Query execution failed: " . $stmt->error . "<br>";
    } else {
        $result = $stmt->get_result();
        echo "✅ Query executed successfully! Rows: " . $result->num_rows . "<br>";
        echo "<pre>";
        while ($row = $result->fetch_assoc()) {
            print_r($row);
        }
        echo "</pre>";
    }
}
?>
