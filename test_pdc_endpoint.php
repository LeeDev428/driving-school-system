<?php
require_once 'config.php';

// Simulate the AJAX request
$_POST['action'] = 'get_pdc_time_slots';
$_POST['selected_date'] = '2025-10-20';

echo "Testing get_pdc_time_slots for date: 2025-10-20\n\n";

$selected_date = $_POST['selected_date'];
$slots = [];

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
    
    echo "Query executed. Rows returned: " . mysqli_num_rows($result) . "\n\n";
    
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
    
    echo "Slots found: " . count($slots) . "\n\n";
    echo "JSON output:\n";
    echo json_encode(['success' => true, 'slots' => $slots], JSON_PRETTY_PRINT);
} else {
    echo "ERROR: " . mysqli_error($conn);
}
