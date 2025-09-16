<?php

// Set timezone to Philippines (UTC+8)
date_default_timezone_set('Asia/Manila');

// Turn off error reporting for AJAX requests to prevent HTML output
error_reporting(0);
ob_start();

session_start();

// Check if user is logged in and is admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["user_type"] !== "admin") {
    header("location: ../login.php");
    exit;
}

// Include database connection
require_once "../config.php";

// Initialize variables
$page_title = "Appointments";
$header_title = "Appointment Management";
$notification_count = 3;

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    // Clean any output buffer and set JSON header
    ob_clean();
    header('Content-Type: application/json');
    
    // Suppress any further output
    while (ob_get_level()) {
        ob_end_clean();
    }
    ob_start();
    
    switch ($_POST['action']) {
        case 'get_calendar_events':
            $year = isset($_POST['year']) ? (int)$_POST['year'] : date('Y');
            $month = isset($_POST['month']) ? (int)$_POST['month'] : date('n');
            
            // Get all appointments for the month
            $sql = "SELECT a.*, at.name as type_name, at.color, 
                           u_student.full_name as student_name,
                           u_instructor.full_name as instructor_name 
                    FROM appointments a 
                    LEFT JOIN appointment_types at ON a.appointment_type_id = at.id
                    LEFT JOIN users u_student ON a.student_id = u_student.id
                    LEFT JOIN instructors i ON a.instructor_id = i.id
                    LEFT JOIN users u_instructor ON i.user_id = u_instructor.id
                    WHERE YEAR(a.appointment_date) = ? AND MONTH(a.appointment_date) = ?
                    ORDER BY a.appointment_date, a.start_time";
            
            $events = [];
            if ($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "ii", $year, $month);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                
                while ($row = mysqli_fetch_assoc($result)) {
                    $events[] = [
                        'id' => $row['id'],
                        'date' => $row['appointment_date'],
                        'time' => date('g:i A', strtotime($row['start_time'])),
                        'type' => $row['type_name'],
                        'course_type' => strtoupper($row['course_type']),
                        'student' => $row['student_name'],
                        'instructor' => $row['instructor_name'],
                        'status' => $row['status'],
                        'color' => $row['color']
                    ];
                }
                mysqli_stmt_close($stmt);
            }
            
            echo json_encode($events);
            exit;
            
        case 'update_appointment_status':
            $appointment_id = $_POST['appointment_id'];
            $new_status = $_POST['status'];
            $admin_id = $_SESSION['id'];
            
            // Update appointment status
            $sql = "UPDATE appointments SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            
            if ($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "si", $new_status, $appointment_id);
                
                if (mysqli_stmt_execute($stmt)) {
                    echo json_encode(['success' => true, 'message' => 'Status updated successfully!']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Error updating status.']);
                }
                mysqli_stmt_close($stmt);
            }
            exit;
            
        case 'assign_instructor':
            $appointment_id = $_POST['appointment_id'];
            $instructor_id = $_POST['instructor_id'];
            $vehicle_id = isset($_POST['vehicle_id']) ? $_POST['vehicle_id'] : null;
            
            // Update appointment with instructor and vehicle
            $sql = "UPDATE appointments SET instructor_id = ?, vehicle_id = ?, status = 'confirmed' WHERE id = ?";
            
            if ($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "iii", $instructor_id, $vehicle_id, $appointment_id);
                
                if (mysqli_stmt_execute($stmt)) {
                    echo json_encode(['success' => true, 'message' => 'Instructor assigned successfully!']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Error assigning instructor.']);
                }
                mysqli_stmt_close($stmt);
            }
            exit;
    }
}

// Get today's appointments
$today_sql = "SELECT a.*, at.name as type_name, 
                     u_student.full_name as student_name, u_student.contact_number,
                     u_instructor.full_name as instructor_name,
                     v.make, v.model, v.license_plate
              FROM appointments a 
              LEFT JOIN appointment_types at ON a.appointment_type_id = at.id
              LEFT JOIN users u_student ON a.student_id = u_student.id
              LEFT JOIN instructors i ON a.instructor_id = i.id
              LEFT JOIN users u_instructor ON i.user_id = u_instructor.id
              LEFT JOIN vehicles v ON a.vehicle_id = v.id
              WHERE a.appointment_date = CURDATE()
              ORDER BY a.start_time";

$today_appointments = [];
if ($result = mysqli_query($conn, $today_sql)) {
    while ($row = mysqli_fetch_assoc($result)) {
        $today_appointments[] = $row;
    }
}

// Get pending appointments
$pending_sql = "SELECT a.*, at.name as type_name, 
                       u_student.full_name as student_name, u_student.contact_number
                FROM appointments a 
                LEFT JOIN appointment_types at ON a.appointment_type_id = at.id
                LEFT JOIN users u_student ON a.student_id = u_student.id
                WHERE a.status = 'pending'
                ORDER BY a.appointment_date, a.start_time";

$pending_appointments = [];
if ($result = mysqli_query($conn, $pending_sql)) {
    while ($row = mysqli_fetch_assoc($result)) {
        $pending_appointments[] = $row;
    }
}

// Get appointment history for admin view
$history_sql = "SELECT a.*, at.name as type_name, 
                       u_student.full_name as student_name,
                       u_instructor.full_name as instructor_name
                FROM appointments a 
                LEFT JOIN appointment_types at ON a.appointment_type_id = at.id
                LEFT JOIN users u_student ON a.student_id = u_student.id
                LEFT JOIN instructors i ON a.instructor_id = i.id
                LEFT JOIN users u_instructor ON i.user_id = u_instructor.id
                WHERE a.status IN ('completed', 'cancelled', 'no_show')
                ORDER BY a.appointment_date DESC, a.start_time DESC
                LIMIT 50";

$appointment_history = [];
if ($result = mysqli_query($conn, $history_sql)) {
    while ($row = mysqli_fetch_assoc($result)) {
        $appointment_history[] = $row;
    }
}

// Get instructors for assignment
$instructors_sql = "SELECT i.id, u.full_name FROM instructors i 
                    JOIN users u ON i.user_id = u.id 
                    WHERE i.is_active = 1 
                    ORDER BY u.full_name";

$instructors = [];
if ($result = mysqli_query($conn, $instructors_sql)) {
    while ($row = mysqli_fetch_assoc($result)) {
        $instructors[] = $row;
    }
}

// Get available vehicles
$vehicles_sql = "SELECT id, make, model, license_plate FROM vehicles WHERE is_available = 1 ORDER BY make, model";

$vehicles = [];
if ($result = mysqli_query($conn, $vehicles_sql)) {
    while ($row = mysqli_fetch_assoc($result)) {
        $vehicles[] = $row;
    }
}

// Generate content
ob_start();
?>

<div class="appointment-container">
    <!-- Tab Navigation -->
    <div class="appointment-tabs">
        <button class="tab-btn active" onclick="switchTab('calendar')">
            <i class="far fa-calendar-alt"></i> Calendar View
        </button>
        <button class="tab-btn" onclick="switchTab('pending')">
            <i class="far fa-clock"></i> Pending Appointments
            <?php if (count($pending_appointments) > 0): ?>
                <span class="tab-badge"><?php echo count($pending_appointments); ?></span>
            <?php endif; ?>
        </button>
        <button class="tab-btn" onclick="switchTab('today')">
            <i class="fas fa-calendar-day"></i> Today's Schedule
        </button>
        <button class="tab-btn" onclick="switchTab('history')">
            <i class="fas fa-history"></i> Appointment History
        </button>
    </div>

    <!-- Calendar View Tab -->
    <div id="calendar-tab" class="tab-content active">
        <div class="calendar-header">
            <div class="calendar-nav">
                <button class="nav-btn" onclick="changeMonth(-1)">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <h3 id="calendar-title"><?php echo date('F Y'); ?></h3>
                <button class="nav-btn" onclick="changeMonth(1)">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
            <div class="calendar-stats">
                <div class="stat-item">
                    <span class="stat-number"><?php echo count($today_appointments); ?></span>
                    <span class="stat-label">Today</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?php echo count($pending_appointments); ?></span>
                    <span class="stat-label">Pending</span>
                </div>
            </div>
        </div>
        
        <div class="calendar-container">
            <div class="calendar-grid">
                <div class="calendar-header-days">
                    <div class="day-header">Sun</div>
                    <div class="day-header">Mon</div>
                    <div class="day-header">Tue</div>
                    <div class="day-header">Wed</div>
                    <div class="day-header">Thu</div>
                    <div class="day-header">Fri</div>
                    <div class="day-header">Sat</div>
                </div>
                <div id="calendar-days" class="calendar-days">
                    <!-- Calendar days will be generated by JavaScript -->
                </div>
            </div>
        </div>
    </div>

    <!-- Pending Appointments Tab -->
    <div id="pending-tab" class="tab-content">
        <div class="appointments-header">
            <h3>Pending Appointments</h3>
            <span class="badge badge-warning"><?php echo count($pending_appointments); ?> pending</span>
        </div>
        
        <div class="appointments-list">
            <?php if (empty($pending_appointments)): ?>
                <div class="no-appointments">
                    <i class="far fa-calendar-check"></i>
                    <h4>No Pending Appointments</h4>
                    <p>All appointments are up to date!</p>
                </div>
            <?php else: ?>
                <?php foreach ($pending_appointments as $appointment): ?>
                    <div class="appointment-card pending">
                        <div class="appointment-info">
                            <div class="appointment-type"><?php echo htmlspecialchars($appointment['type_name']); ?></div>
                            <div class="appointment-details">
                                <div class="detail-item">
                                    <i class="fas fa-user"></i>
                                    <strong>Student:</strong> <?php echo htmlspecialchars($appointment['student_name']); ?>
                                </div>
                                <div class="detail-item">
                                    <i class="fas fa-graduation-cap"></i>
                                    <strong>Course Type:</strong> <?php echo strtoupper(htmlspecialchars($appointment['course_type'])); ?>
                                </div>
                                <div class="detail-item">
                                    <i class="far fa-calendar"></i>
                                    <strong>Date:</strong> <?php echo date('M j, Y', strtotime($appointment['appointment_date'])); ?>
                                </div>
                                <div class="detail-item">
                                    <i class="far fa-clock"></i>
                                    <strong>Time:</strong> <?php echo date('g:i A', strtotime($appointment['start_time'])) . ' - ' . date('g:i A', strtotime($appointment['end_time'])); ?>
                                </div>
                                <?php if ($appointment['contact_number']): ?>
                                    <div class="detail-item">
                                        <i class="fas fa-phone"></i>
                                        <strong>Contact:</strong> <?php echo htmlspecialchars($appointment['contact_number']); ?>
                                    </div>
                                <?php endif; ?>
                                <?php if ($appointment['student_notes']): ?>
                                    <div class="detail-item">
                                        <i class="fas fa-sticky-note"></i>
                                        <strong>Notes:</strong> <?php echo htmlspecialchars($appointment['student_notes']); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Payment Information -->
                                <div class="detail-item payment-info">
                                    <i class="fas fa-money-bill-wave"></i>
                                    <strong>Payment:</strong> 
                                    <?php if ($appointment['payment_amount'] > 0): ?>
                                        $<?php echo number_format($appointment['payment_amount'], 2); ?>
                                        <?php if ($appointment['payment_method']): ?>
                                            (<?php echo ucfirst(str_replace('_', ' ', $appointment['payment_method'])); ?>)
                                        <?php endif; ?>
                                        - <span class="payment-status <?php echo $appointment['is_paid'] ? 'paid' : 'unpaid'; ?>">
                                            <?php echo $appointment['is_paid'] ? 'PAID' : 'UNPAID'; ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="payment-status unpaid">No payment recorded</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="appointment-actions">
                            <button class="action-btn approve" onclick="assignInstructor(<?php echo $appointment['id']; ?>)">
                                <i class="fas fa-user-plus"></i> Assign Instructor
                            </button>
                            <button class="action-btn confirm" onclick="updateStatus(<?php echo $appointment['id']; ?>, 'confirmed')">
                                <i class="fas fa-check"></i> Confirm
                            </button>
                            <button class="action-btn cancel" onclick="updateStatus(<?php echo $appointment['id']; ?>, 'cancelled')">
                                <i class="fas fa-times"></i> Cancel
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Today's Schedule Tab -->
    <div id="today-tab" class="tab-content">
        <div class="appointments-header">
            <h3>Today's Schedule - <?php echo date('F j, Y'); ?></h3>
            <span class="badge badge-primary"><?php echo count($today_appointments); ?> appointments</span>
        </div>
        
        <div class="today-schedule">
            <?php if (empty($today_appointments)): ?>
                <div class="no-appointments">
                    <i class="far fa-calendar"></i>
                    <h4>No Appointments Today</h4>
                    <p>Enjoy your day off!</p>
                </div>
            <?php else: ?>
                <div class="schedule-timeline">
                    <?php foreach ($today_appointments as $appointment): ?>
                        <div class="timeline-item">
                            <div class="timeline-time">
                                <?php echo date('g:i A', strtotime($appointment['start_time'])); ?>
                            </div>
                            <div class="timeline-content">
                                <div class="appointment-card-today status-<?php echo $appointment['status']; ?>">
                                    <div class="appointment-header">
                                        <h4><?php echo htmlspecialchars($appointment['type_name']); ?></h4>
                                        <span class="status-badge <?php echo $appointment['status']; ?>">
                                            <?php echo ucfirst($appointment['status']); ?>
                                        </span>
                                    </div>
                                    <div class="appointment-details">
                                        <div class="detail-row">
                                            <span><i class="fas fa-user"></i> Student:</span>
                                            <span><?php echo htmlspecialchars($appointment['student_name']); ?></span>
                                        </div>
                                        <div class="detail-row">
                                            <span><i class="fas fa-graduation-cap"></i> Course:</span>
                                            <span><?php echo strtoupper(htmlspecialchars($appointment['course_type'])); ?></span>
                                        </div>
                                        <?php if ($appointment['instructor_name']): ?>
                                            <div class="detail-row">
                                                <span><i class="fas fa-chalkboard-teacher"></i> Instructor:</span>
                                                <span><?php echo htmlspecialchars($appointment['instructor_name']); ?></span>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($appointment['make'] && $appointment['model']): ?>
                                            <div class="detail-row">
                                                <span><i class="fas fa-car"></i> Vehicle:</span>
                                                <span><?php echo htmlspecialchars($appointment['make'] . ' ' . $appointment['model'] . ' (' . $appointment['license_plate'] . ')'); ?></span>
                                            </div>
                                        <?php endif; ?>
                                        <div class="detail-row">
                                            <span><i class="far fa-clock"></i> Duration:</span>
                                            <span><?php echo date('g:i A', strtotime($appointment['start_time'])) . ' - ' . date('g:i A', strtotime($appointment['end_time'])); ?></span>
                                        </div>
                                        
                                        <!-- Payment Information -->
                                        <div class="detail-row payment-row">
                                            <span><i class="fas fa-money-bill-wave"></i> Payment:</span>
                                            <span>
                                                <?php if ($appointment['payment_amount'] > 0): ?>
                                                    $<?php echo number_format($appointment['payment_amount'], 2); ?>
                                                    <?php if ($appointment['payment_method']): ?>
                                                        (<?php echo ucfirst(str_replace('_', ' ', $appointment['payment_method'])); ?>)
                                                    <?php endif; ?>
                                                    - <span class="payment-status <?php echo $appointment['is_paid'] ? 'paid' : 'unpaid'; ?>">
                                                        <?php echo $appointment['is_paid'] ? 'PAID' : 'UNPAID'; ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="payment-status unpaid">No payment</span>
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                    </div>
                                    <?php if ($appointment['status'] === 'confirmed'): ?>
                                        <div class="appointment-quick-actions">
                                            <button class="quick-action-btn start" onclick="updateStatus(<?php echo $appointment['id']; ?>, 'in_progress')">
                                                <i class="fas fa-play"></i> Start
                                            </button>
                                            <button class="quick-action-btn complete" onclick="updateStatus(<?php echo $appointment['id']; ?>, 'completed')">
                                                <i class="fas fa-check"></i> Complete
                                            </button>
                                        </div>
                                    <?php elseif ($appointment['status'] === 'in_progress'): ?>
                                        <div class="appointment-quick-actions">
                                            <button class="quick-action-btn complete" onclick="updateStatus(<?php echo $appointment['id']; ?>, 'completed')">
                                                <i class="fas fa-check"></i> Complete
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Appointment History Tab -->
    <div id="history-tab" class="tab-content">
        <div class="appointments-header">
            <h3>Appointment History</h3>
        </div>
        
        <div class="history-table-container">
            <table class="history-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Student</th>
                        <th>Type</th>
                        <th>Course</th>
                        <th>Instructor</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($appointment_history as $appointment): ?>
                        <tr>
                            <td><?php echo date('M j, Y', strtotime($appointment['appointment_date'])); ?></td>
                            <td><?php echo htmlspecialchars($appointment['student_name']); ?></td>
                            <td><?php echo htmlspecialchars($appointment['type_name']); ?></td>
                            <td>
                                <span class="course-badge <?php echo $appointment['course_type']; ?>">
                                    <?php echo strtoupper($appointment['course_type']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($appointment['instructor_name'] ?? 'Not Assigned'); ?></td>
                            <td>
                                <span class="status-badge <?php echo $appointment['status']; ?>">
                                    <?php echo ucfirst($appointment['status']); ?>
                                </span>
                            </td>
                            <td>
                                <button class="details-btn" onclick="viewAppointmentDetails(<?php echo $appointment['id']; ?>)">
                                    <i class="fas fa-eye"></i> View
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Assign Instructor Modal -->
<div id="assign-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Assign Instructor & Vehicle</h3>
            <span class="close-btn" onclick="closeAssignModal()">&times;</span>
        </div>
        <form id="assign-form">
            <input type="hidden" id="assign-appointment-id" name="appointment_id">
            
            <div class="form-group">
                <label for="instructor_id">Select Instructor</label>
                <select id="instructor_id" name="instructor_id" required>
                    <option value="">Choose an instructor</option>
                    <?php foreach ($instructors as $instructor): ?>
                        <option value="<?php echo $instructor['id']; ?>">
                            <?php echo htmlspecialchars($instructor['full_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="vehicle_id">Select Vehicle (Optional)</label>
                <select id="vehicle_id" name="vehicle_id">
                    <option value="">Choose a vehicle</option>
                    <?php foreach ($vehicles as $vehicle): ?>
                        <option value="<?php echo $vehicle['id']; ?>">
                            <?php echo htmlspecialchars($vehicle['make'] . ' ' . $vehicle['model'] . ' (' . $vehicle['license_plate'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-actions">
                <button type="button" onclick="closeAssignModal()" class="cancel-btn">Cancel</button>
                <button type="submit" class="submit-btn">Assign & Confirm</button>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();

// Add additional styles
$extra_styles = <<<EOT
<style>
.appointment-container {
    max-width: 1400px;
    margin: 0 auto;
}

.appointment-tabs {
    display: flex;
    border-bottom: 2px solid #3a3f48;
    margin-bottom: 20px;
}

.tab-btn {
    background: none;
    border: none;
    color: #8b8d93;
    padding: 15px 20px;
    cursor: pointer;
    border-bottom: 2px solid transparent;
    transition: all 0.3s;
    font-size: 14px;
    font-weight: 500;
    position: relative;
    display: flex;
    align-items: center;
    gap: 8px;
}

.tab-btn.active,
.tab-btn:hover {
    color: #ffcc00;
    border-bottom-color: #ffcc00;
}

.tab-badge {
    background: #ff3333;
    color: white;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    font-weight: 600;
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

/* Course Type Badges */
.course-badge {
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.course-badge.tpc {
    background: rgba(156, 39, 176, 0.2);
    color: #9c27b0;
}

.course-badge.pdc {
    background: rgba(255, 152, 0, 0.2);
    color: #ff9800;
}

.course-badge.both {
    background: rgba(63, 81, 181, 0.2);
    color: #3f51b5;
}

/* Calendar Styles */
.calendar-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.calendar-nav {
    display: flex;
    align-items: center;
    gap: 20px;
}

.calendar-stats {
    display: flex;
    gap: 20px;
}

.stat-item {
    text-align: center;
}

.stat-number {
    display: block;
    font-size: 24px;
    font-weight: 600;
    color: #ffcc00;
}

.stat-label {
    font-size: 12px;
    color: #8b8d93;
    text-transform: uppercase;
}

.nav-btn {
    background: #282c34;
    border: 1px solid #3a3f48;
    color: #8b8d93;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s;
}

.nav-btn:hover {
    background: #3a3f48;
    color: white;
}

.calendar-container {
    background: #282c34;
    border-radius: 10px;
    padding: 20px;
    border: 1px solid #3a3f48;
}

.calendar-grid {
    width: 100%;
}

.calendar-header-days {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 1px;
    margin-bottom: 10px;
}

.day-header {
    text-align: center;
    padding: 10px;
    font-weight: 600;
    color: #8b8d93;
    text-transform: uppercase;
    font-size: 12px;
}

.calendar-days {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 1px;
    background: #3a3f48;
}

.calendar-day {
    background: #282c34;
    min-height: 120px;
    padding: 8px;
    display: flex;
    flex-direction: column;
    border: 1px solid #3a3f48;
    transition: all 0.3s;
}

.calendar-day:hover {
    background: #3a3f48;
}

.calendar-day.other-month {
    color: #5a5a5a;
    background: #1e2129;
}

.calendar-day.today {
    background: rgba(255, 204, 0, 0.1);
    border-color: #ffcc00;
}

.day-number {
    font-weight: 600;
    margin-bottom: 5px;
}

.appointment-indicator {
    background: #4CAF50;
    color: white;
    font-size: 9px;
    padding: 2px 4px;
    border-radius: 3px;
    margin-bottom: 2px;
    text-overflow: ellipsis;
    overflow: hidden;
    white-space: nowrap;
    cursor: pointer;
}

.appointment-indicator:hover {
    opacity: 0.8;
}

/* Pending Appointments */
.appointments-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.appointment-card {
    background: #282c34;
    border: 1px solid #3a3f48;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 15px;
    transition: all 0.3s;
}

.appointment-card.pending {
    border-left: 4px solid #ff9800;
}

.appointment-card:hover {
    border-color: #ffcc00;
    transform: translateY(-2px);
}

.appointment-info {
    margin-bottom: 20px;
}

.appointment-type {
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 15px;
    color: #ffcc00;
}

.appointment-details {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 10px;
}

.detail-item {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #8b8d93;
    font-size: 14px;
}

.detail-item i {
    width: 16px;
    color: #ffcc00;
}

.appointment-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.action-btn {
    padding: 8px 16px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    gap: 6px;
}

.action-btn.approve {
    background: #4CAF50;
    color: white;
}

.action-btn.confirm {
    background: #2196F3;
    color: white;
}

.action-btn.cancel {
    background: #f44336;
    color: white;
}

.action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.3);
}

/* Today's Schedule */
.today-schedule {
    max-width: 800px;
}

.schedule-timeline {
    position: relative;
}

.timeline-item {
    display: flex;
    margin-bottom: 20px;
    position: relative;
}

.timeline-time {
    width: 80px;
    flex-shrink: 0;
    font-weight: 600;
    color: #ffcc00;
    text-align: right;
    padding-right: 20px;
    padding-top: 5px;
}

.timeline-content {
    flex: 1;
    position: relative;
}

.timeline-content::before {
    content: '';
    position: absolute;
    left: -10px;
    top: 10px;
    width: 2px;
    height: calc(100% + 20px);
    background: #3a3f48;
}

.timeline-item::after {
    content: '';
    position: absolute;
    left: 70px;
    top: 10px;
    width: 8px;
    height: 8px;
    background: #ffcc00;
    border-radius: 50%;
    z-index: 1;
}

.appointment-card-today {
    background: #282c34;
    border: 1px solid #3a3f48;
    border-radius: 8px;
    padding: 20px;
    margin-left: 20px;
}

.appointment-card-today.status-confirmed {
    border-left: 4px solid #4CAF50;
}

.appointment-card-today.status-in_progress {
    border-left: 4px solid #2196F3;
    background: rgba(33, 150, 243, 0.05);
}

.appointment-card-today.status-completed {
    border-left: 4px solid #4CAF50;
    opacity: 0.7;
}

.appointment-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.appointment-header h4 {
    margin: 0;
    color: white;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
    font-size: 14px;
}

.detail-row span:first-child {
    color: #8b8d93;
    font-weight: 500;
}

.appointment-quick-actions {
    margin-top: 15px;
    display: flex;
    gap: 10px;
}

.quick-action-btn {
    padding: 6px 12px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 12px;
    font-weight: 500;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    gap: 4px;
}

.quick-action-btn.start {
    background: #2196F3;
    color: white;
}

.quick-action-btn.complete {
    background: #4CAF50;
    color: white;
}

.quick-action-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

.status-badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.status-badge.pending {
    background: rgba(255, 152, 0, 0.2);
    color: #ff9800;
}

.status-badge.confirmed {
    background: rgba(76, 175, 80, 0.2);
    color: #4CAF50;
}

.status-badge.in_progress {
    background: rgba(33, 150, 243, 0.2);
    color: #2196F3;
}

.status-badge.completed {
    background: rgba(76, 175, 80, 0.2);
    color: #4CAF50;
}

.status-badge.cancelled {
    background: rgba(244, 67, 54, 0.2);
    color: #f44336;
}

.status-badge.no_show {
    background: rgba(158, 158, 158, 0.2);
    color: #9e9e9e;
}

/* History Table */
.history-table-container {
    background: #282c34;
    border-radius: 10px;
    overflow: hidden;
    border: 1px solid #3a3f48;
}

.history-table {
    width: 100%;
    border-collapse: collapse;
}

.history-table th {
    background: #1e2129;
    color: #8b8d93;
    padding: 15px;
    text-align: left;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 12px;
}

.history-table td {
    padding: 15px;
    border-bottom: 1px solid #3a3f48;
}

.history-table tr:hover {
    background: #3a3f48;
}

.details-btn {
    background: none;
    border: 1px solid #3a3f48;
    color: #8b8d93;
    padding: 6px 12px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 12px;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    gap: 4px;
}

.details-btn:hover {
    border-color: #ffcc00;
    color: #ffcc00;
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.8);
}

.modal-content {
    background-color: #282c34;
    margin: 5% auto;
    padding: 0;
    border: 1px solid #3a3f48;
    border-radius: 10px;
    width: 90%;
    max-width: 500px;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #3a3f48;
}

.modal-header h3 {
    margin: 0;
    color: #ffcc00;
}

.close-btn {
    color: #8b8d93;
    font-size: 24px;
    font-weight: bold;
    cursor: pointer;
    border: none;
    background: none;
}

.close-btn:hover {
    color: white;
}

.modal form {
    padding: 20px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    color: #8b8d93;
    font-weight: 500;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #3a3f48;
    border-radius: 5px;
    background: #1e2129;
    color: white;
    font-size: 14px;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #ffcc00;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 30px;
}

.cancel-btn {
    background: none;
    border: 1px solid #3a3f48;
    color: #8b8d93;
    padding: 10px 20px;
    border-radius: 5px;
    cursor: pointer;
}

.cancel-btn:hover {
    border-color: #8b8d93;
    color: white;
}

.submit-btn {
    background: #ffcc00;
    border: none;
    color: #1a1d24;
    padding: 10px 20px;
    border-radius: 5px;
    font-weight: 600;
    cursor: pointer;
}

.submit-btn:hover {
    background: #e6b800;
}

.no-appointments {
    text-align: center;
    padding: 60px 20px;
    color: #8b8d93;
}

.no-appointments i {
    font-size: 48px;
    margin-bottom: 20px;
    opacity: 0.5;
}

/* Responsive */
@media (max-width: 768px) {
    .appointment-tabs {
        flex-wrap: wrap;
    }
    
    .tab-btn {
        flex: 1;
        min-width: 120px;
    }
    
    .calendar-header {
        flex-direction: column;
        gap: 15px;
    }
    
    .calendar-stats {
        justify-content: center;
    }
    
    .appointment-details {
        grid-template-columns: 1fr;
    }
    
    .appointment-actions {
        justify-content: flex-start;
    }
    
    .timeline-item {
        flex-direction: column;
    }
    
    .timeline-time {
        width: auto;
        text-align: left;
        padding-right: 0;
        margin-bottom: 10px;
    }
    
    .timeline-content {
        margin-left: 0;
    }
    
    .timeline-content::before {
        display: none;
    }
    
    .timeline-item::after {
        display: none;
    }
    
    .appointment-card-today {
        margin-left: 0;
    }
}
</style>
EOT;

// Add additional scripts
$extra_scripts = <<<EOT
<script>
let currentDate = new Date();
let currentView = 'calendar';

// Tab switching
function switchTab(tabName) {
    // Hide all tabs
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Remove active class from all buttons
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Show selected tab
    document.getElementById(tabName + '-tab').classList.add('active');
    
    // Add active class to clicked button
    event.target.classList.add('active');
    
    currentView = tabName;
    
    if (tabName === 'calendar') {
        loadCalendar();
    }
}

// Calendar functions
function changeMonth(delta) {
    currentDate.setMonth(currentDate.getMonth() + delta);
    loadCalendar();
}

function loadCalendar() {
    const year = currentDate.getFullYear();
    const month = currentDate.getMonth();
    
    // Update title
    document.getElementById('calendar-title').textContent = 
        currentDate.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
    
    // Get first day of month and number of days
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const daysInMonth = lastDay.getDate();
    const startDate = new Date(firstDay);
    startDate.setDate(startDate.getDate() - firstDay.getDay());
    
    // Generate calendar days
    const calendarDays = document.getElementById('calendar-days');
    calendarDays.innerHTML = '';
    
    // Load appointments for this month
    fetch('', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=get_calendar_events&year=' + year + '&month=' + (month + 1)
    })
    .then(response => response.json())
    .then(appointments => {
        const appointmentsByDate = {};
        appointments.forEach(apt => {
            if (!appointmentsByDate[apt.date]) {
                appointmentsByDate[apt.date] = [];
            }
            appointmentsByDate[apt.date].push(apt);
        });
        
        // Generate 42 days (6 weeks)
        for (let i = 0; i < 42; i++) {
            const date = new Date(startDate);
            date.setDate(startDate.getDate() + i);
            
            const dayElement = document.createElement('div');
            dayElement.className = 'calendar-day';
            
            const isCurrentMonth = date.getMonth() === month;
            const isToday = date.toDateString() === new Date().toDateString();
            
            if (!isCurrentMonth) {
                dayElement.classList.add('other-month');
            }
            
            if (isToday) {
                dayElement.classList.add('today');
            }
            
            const dateString = date.getFullYear() + '-' + 
                String(date.getMonth() + 1).padStart(2, '0') + '-' + 
                String(date.getDate()).padStart(2, '0');
            const dayAppointments = appointmentsByDate[dateString] || [];
            
            dayElement.innerHTML = 
                '<div class="day-number">' + date.getDate() + '</div>' +
                dayAppointments.map(apt => 
                    '<div class="appointment-indicator" style="background-color: ' + (apt.color || '#4CAF50') + '" title="' + apt.time + ' - ' + apt.student + ' - ' + apt.type + ' (' + apt.course_type + ')">' + apt.time + ' ' + apt.student + '</div>'
                ).join('');
            
            calendarDays.appendChild(dayElement);
        }
    });
}

// Status update function
function updateStatus(appointmentId, status) {
    if (confirm('Are you sure you want to update this appointment status to "' + status + '"?')) {
        fetch('', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=update_appointment_status&appointment_id=' + appointmentId + '&status=' + status
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        });
    }
}

// Assign instructor modal functions
function assignInstructor(appointmentId) {
    document.getElementById('assign-appointment-id').value = appointmentId;
    document.getElementById('assign-modal').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closeAssignModal() {
    document.getElementById('assign-modal').style.display = 'none';
    document.body.style.overflow = 'auto';
    document.getElementById('assign-form').reset();
}

// Assign form submission
document.getElementById('assign-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('action', 'assign_instructor');
    
    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            closeAssignModal();
            location.reload();
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    });
});

function viewAppointmentDetails(appointmentId) {
    // This would open a modal with full appointment details
    // For now, just show an alert
    alert('Appointment details for ID: ' + appointmentId);
}

// Close modal when clicking outside
window.onclick = function(event) {
    const assignModal = document.getElementById('assign-modal');
    if (event.target === assignModal) {
        closeAssignModal();
    }
}

// Initialize calendar on page load
document.addEventListener('DOMContentLoaded', function() {
    if (currentView === 'calendar') {
        loadCalendar();
    }
});
</script>
EOT;

// Include the main layout template
include "../layouts/main_layout.php";
?>