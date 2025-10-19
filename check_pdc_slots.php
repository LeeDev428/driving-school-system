<?php
require_once 'config.php';

echo "=== CHECKING PDC TIME SLOTS ===\n\n";

// Count total slots
$result = $conn->query("SELECT COUNT(*) as count FROM pdc_time_slots");
$row = $result->fetch_assoc();
echo "Total PDC time slots: {$row['count']}\n\n";

// Show slots by date
echo "Time slots by date:\n";
$result = $conn->query("SELECT slot_date, COUNT(*) as count FROM pdc_time_slots GROUP BY slot_date ORDER BY slot_date LIMIT 10");
while($row = $result->fetch_assoc()) {
    echo "  {$row['slot_date']}: {$row['count']} slots\n";
}

echo "\n";

// Show first few slots
echo "First 5 time slots:\n";
$result = $conn->query("SELECT id, slot_date, slot_time_start, slot_time_end, slot_label, current_bookings, max_bookings, is_available FROM pdc_time_slots ORDER BY slot_date, slot_time_start LIMIT 5");
while($row = $result->fetch_assoc()) {
    echo "  [{$row['id']}] {$row['slot_date']} {$row['slot_time_start']}-{$row['slot_time_end']} '{$row['slot_label']}' ({$row['current_bookings']}/{$row['max_bookings']}) Available: {$row['is_available']}\n";
}
