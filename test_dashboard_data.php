<?php
// Test if dashboard queries are working
require_once "config.php";

echo "<h2>Testing Dashboard Queries</h2>";
echo "<style>body { font-family: Arial; padding: 20px; } .success { color: green; } .error { color: red; } table { border-collapse: collapse; margin: 10px 0; } th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }</style>";

// Test 1: Recent Appointments
echo "<h3>1. Recent Appointments</h3>";
$appointments_query = "SELECT a.*, u.full_name as student_name, i.full_name as instructor_name, at.name as type_name
                      FROM appointments a 
                      LEFT JOIN users u ON a.student_id = u.id 
                      LEFT JOIN instructors inst ON a.instructor_id = inst.id
                      LEFT JOIN users i ON inst.user_id = i.id
                      LEFT JOIN appointment_types at ON a.appointment_type_id = at.id
                      ORDER BY a.created_at DESC LIMIT 10";
$appointments_result = $conn->query($appointments_query);
if ($appointments_result) {
    echo "<p class='success'>✅ Query successful! Found " . $appointments_result->num_rows . " appointments</p>";
    if ($appointments_result->num_rows > 0) {
        echo "<table><tr><th>ID</th><th>Student</th><th>Instructor</th><th>Type</th><th>Date</th></tr>";
        while ($row = $appointments_result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$row['id']}</td>";
            echo "<td>" . htmlspecialchars($row['student_name'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($row['instructor_name'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($row['type_name'] ?? 'N/A') . "</td>";
            echo "<td>{$row['appointment_date']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} else {
    echo "<p class='error'>❌ Query failed: " . $conn->error . "</p>";
}

// Test 2: Recent Instructors
echo "<h3>2. Recent Instructors</h3>";
$instructors_query = "SELECT inst.id, inst.license_number, inst.years_experience, inst.is_active, 
                     inst.created_at, u.full_name, u.email, u.phone
                     FROM instructors inst
                     LEFT JOIN users u ON inst.user_id = u.id
                     ORDER BY inst.created_at DESC LIMIT 5";
$instructors_result = $conn->query($instructors_query);
if ($instructors_result) {
    echo "<p class='success'>✅ Query successful! Found " . $instructors_result->num_rows . " instructors</p>";
    if ($instructors_result->num_rows > 0) {
        echo "<table><tr><th>ID</th><th>Name</th><th>Email</th><th>License</th><th>Experience</th><th>Status</th></tr>";
        while ($row = $instructors_result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$row['id']}</td>";
            echo "<td>" . htmlspecialchars($row['full_name'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($row['email'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($row['license_number'] ?? 'N/A') . "</td>";
            echo "<td>{$row['years_experience']} years</td>";
            echo "<td>" . ($row['is_active'] ? 'Active' : 'Inactive') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} else {
    echo "<p class='error'>❌ Query failed: " . $conn->error . "</p>";
}

// Test 3: Recent Vehicles
echo "<h3>3. Recent Vehicles</h3>";
$vehicles_query = "SELECT id, make, model, year, plate_number, transmission_type, 
                  fuel_type, status, created_at
                  FROM vehicles 
                  ORDER BY created_at DESC LIMIT 5";
$vehicles_result = $conn->query($vehicles_query);
if ($vehicles_result) {
    echo "<p class='success'>✅ Query successful! Found " . $vehicles_result->num_rows . " vehicles</p>";
    if ($vehicles_result->num_rows > 0) {
        echo "<table><tr><th>ID</th><th>Make</th><th>Model</th><th>Year</th><th>Plate</th><th>Transmission</th><th>Status</th></tr>";
        while ($row = $vehicles_result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$row['id']}</td>";
            echo "<td>" . htmlspecialchars($row['make']) . "</td>";
            echo "<td>" . htmlspecialchars($row['model']) . "</td>";
            echo "<td>{$row['year']}</td>";
            echo "<td>" . htmlspecialchars($row['plate_number'] ?? 'N/A') . "</td>";
            echo "<td>" . ucfirst($row['transmission_type'] ?? 'N/A') . "</td>";
            echo "<td>" . ucfirst($row['status']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} else {
    echo "<p class='error'>❌ Query failed: " . $conn->error . "</p>";
}

// Test 4: Stats
echo "<h3>4. Dashboard Stats</h3>";
$stats_query = "SELECT 
                  (SELECT COUNT(*) FROM appointments WHERE status = 'pending') as pending_appointments,
                  (SELECT COUNT(*) FROM appointments WHERE status = 'confirmed') as confirmed_appointments,
                  (SELECT COUNT(*) FROM users WHERE user_type = 'student' AND DATE(created_at) = CURDATE()) as new_students_today,
                  (SELECT COUNT(*) FROM users WHERE user_type = 'student') as total_students,
                  (SELECT COUNT(DISTINCT session_id) FROM quiz_responses) as total_simulation_sessions,
                  (SELECT COUNT(DISTINCT user_id) FROM user_assessment_sessions WHERE status = 'completed' AND passed = 1) as assessment_passes,
                  (SELECT COUNT(DISTINCT user_id) FROM user_quiz_sessions WHERE status = 'completed' AND passed = 1) as quiz_passes,
                  (SELECT COUNT(*) FROM appointments WHERE DATE(appointment_date) = CURDATE()) as appointments_today";
$stats_result = $conn->query($stats_query);
if ($stats_result) {
    echo "<p class='success'>✅ Query successful!</p>";
    $stats = $stats_result->fetch_assoc();
    echo "<table>";
    foreach ($stats as $key => $value) {
        echo "<tr><th>" . ucfirst(str_replace('_', ' ', $key)) . "</th><td>{$value}</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p class='error'>❌ Query failed: " . $conn->error . "</p>";
}

echo "<hr>";
echo "<h3>✅ All Tests Complete!</h3>";
echo "<p>If you see data above, refresh your admin dashboard at: <a href='admin/dashboard.php'>admin/dashboard.php</a></p>";
?>
