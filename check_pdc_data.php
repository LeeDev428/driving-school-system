<?php
require_once 'config.php';

echo "=== CHECKING PDC TIME SLOTS TABLE ===\n\n";

// Check total count
$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM pdc_time_slots");
$row = mysqli_fetch_assoc($result);
echo "Total PDC time slots: " . $row['total'] . "\n\n";

// Check for specific date
$test_date = '2025-10-20';
$sql = "SELECT * FROM pdc_time_slots WHERE slot_date = '$test_date' ORDER BY slot_time_start";
$result = mysqli_query($conn, $sql);

echo "Slots for $test_date:\n";
echo "-------------------\n";

if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        echo "ID: {$row['id']} | ";
        echo "Time: {$row['slot_time_start']} - {$row['slot_time_end']} | ";
        echo "Label: {$row['slot_label']} | ";
        echo "Bookings: {$row['current_bookings']}/{$row['max_bookings']} | ";
        echo "Available: " . ($row['is_available'] ? 'Yes' : 'No') . "\n";
    }
} else {
    echo "NO SLOTS FOUND FOR THIS DATE!\n";
}

echo "\n=== NOW TESTING THE ACTUAL AJAX QUERY ===\n\n";

// Test the exact query from appointments.php
$selected_date = '2025-10-20';
$sql = "SELECT pts.*, 
               u.full_name as instructor_name,
               (pts.max_bookings - pts.current_bookings) as available_slots
        FROM pdc_time_slots pts
        LEFT JOIN users u ON pts.instructor_id = u.id
        WHERE pts.slot_date = ? 
        AND pts.is_available = 1
        ORDER BY pts.slot_time_start";

if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "s", $selected_date);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    echo "Query executed successfully!\n";
    echo "Rows returned: " . mysqli_num_rows($result) . "\n\n";
    
    $slots = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $is_full = $row['current_bookings'] >= $row['max_bookings'];
        $slots[] = [
            'id' => $row['id'],
            'time_start' => $row['slot_time_start'],
            'time_end' => $row['slot_time_end'],
            'label' => $row['slot_label'],
            'instructor' => $row['instructor_name'] ?: 'Any Available',
            'current_bookings' => $row['current_bookings'],
            'max_bookings' => $row['max_bookings'],
            'available_slots' => $row['available_slots'],
            'is_full' => $is_full,
            'is_available' => !$is_full
        ];
    }
    mysqli_stmt_close($stmt);
    
    echo "JSON Output:\n";
    echo json_encode($slots, JSON_PRETTY_PRINT) . "\n";
} else {
    echo "ERROR: Failed to prepare statement: " . mysqli_error($conn) . "\n";
}

mysqli_close($conn);
?>
