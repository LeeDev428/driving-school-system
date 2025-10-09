<?php
session_start();

// Set timezone to Philippines (UTC+8)
date_default_timezone_set('Asia/Manila');

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php");
    exit;
}

// Include database connection
require_once "../config.php";

// Handle AJAX requests FIRST - before any HTML output
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    $user_id = $_SESSION["id"];
    
    // Get calendar events - updated for new structure
    if ($_POST['action'] == 'get_calendar_events') {
        $year = isset($_POST['year']) ? (int)$_POST['year'] : date('Y');
        $month = isset($_POST['month']) ? (int)$_POST['month'] : date('n');
        
        $events = [];
        $sql = "SELECT a.*, a.course_selection, a.course_price, a.vehicle_type, a.vehicle_transmission,
                       ts.session_date, ts.current_enrollments, ts.max_enrollments
                FROM appointments a 
                LEFT JOIN tdc_sessions ts ON a.tdc_session_id = ts.id
                WHERE a.student_id = ? AND YEAR(a.appointment_date) = ? AND MONTH(a.appointment_date) = ?";
        
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "iii", $user_id, $year, $month);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            while ($row = mysqli_fetch_assoc($result)) {
                $eventLabel = $row['course_selection'];
                if ($row['course_selection'] == 'PDC' && $row['vehicle_type']) {
                    $eventLabel .= ' - ' . ucfirst($row['vehicle_type']);
                }
                
                $events[] = [
                    'id' => $row['id'],
                    'date' => $row['appointment_date'],
                    'time' => date('g:i A', strtotime($row['start_time'])),
                    'type' => $eventLabel,
                    'course_selection' => $row['course_selection'],
                    'status' => $row['status'],
                    'color' => $row['course_selection'] == 'TDC' ? '#9c27b0' : '#ff9800'
                ];
            }
            mysqli_stmt_close($stmt);
        }
        
        echo json_encode($events);
        exit;
    }
    
    // Get available TDC sessions
    if ($_POST['action'] == 'get_tdc_sessions') {
        $sessions = [];
        $sql = "SELECT ts.*, u.full_name as instructor_name
                FROM tdc_sessions ts
                LEFT JOIN instructors i ON ts.instructor_id = i.id
                LEFT JOIN users u ON i.user_id = u.id
                WHERE ts.session_date >= CURDATE() AND ts.status = 'active'
                ORDER BY ts.session_date, ts.start_time";
        
        $result = mysqli_query($conn, $sql);
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $available_slots = $row['max_enrollments'] - $row['current_enrollments'];
                $sessions[] = [
                    'id' => $row['id'],
                    'date' => $row['session_date'],
                    'day' => $row['session_day'],
                    'start_time' => date('g:i A', strtotime($row['start_time'])),
                    'end_time' => date('g:i A', strtotime($row['end_time'])),
                    'instructor' => $row['instructor_name'] ?: 'TBA',
                    'current_enrollments' => $row['current_enrollments'],
                    'max_enrollments' => $row['max_enrollments'],
                    'available_slots' => $available_slots,
                    'is_full' => $row['status'] == 'full'
                ];
            }
        }
        
        echo json_encode($sessions);
        exit;
    }
    
    // Schedule appointment - updated for TDC/PDC system
    if ($_POST['action'] == 'schedule_appointment') {
        $course_selection = $_POST['course_selection']; // TDC or PDC
        $notes = $_POST['notes'] ?? '';
        
        // Get payment fields
        $payment_amount = !empty($_POST['payment_amount']) ? floatval($_POST['payment_amount']) : 0.00;
        $payment_method = !empty($_POST['payment_method']) ? $_POST['payment_method'] : null;
        $payment_reference = !empty($_POST['payment_reference']) ? trim($_POST['payment_reference']) : null;
        $payment_status = 'unpaid'; // Always start as unpaid, admin will confirm
        
        $status = 'pending';
        
        if ($course_selection == 'TDC') {
            // TDC booking
            $tdc_session_id = $_POST['tdc_session_id'];
            $preferred_instructor = !empty($_POST['preferred_instructor']) ? $_POST['preferred_instructor'] : null;
            $preferred_vehicle = !empty($_POST['preferred_vehicle']) ? $_POST['preferred_vehicle'] : null;
            $course_price = 899.00;
            
            // Check if session is still available
            $check_sql = "SELECT current_enrollments, max_enrollments, session_date, start_time, end_time 
                         FROM tdc_sessions WHERE id = ? AND status = 'active'";
            if ($stmt = mysqli_prepare($conn, $check_sql)) {
                mysqli_stmt_bind_param($stmt, "i", $tdc_session_id);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $session = mysqli_fetch_assoc($result);
                mysqli_stmt_close($stmt);
                
                if (!$session) {
                    echo json_encode(['success' => false, 'message' => 'Session not available.']);
                    exit;
                }
                
                if ($session['current_enrollments'] >= $session['max_enrollments']) {
                    echo json_encode(['success' => false, 'message' => 'Session is full. Please select another date.']);
                    exit;
                }
                
                $appointment_date = $session['session_date'];
                $start_time = $session['start_time'];
                $end_time = $session['end_time'];
                
                // Insert TDC appointment
                $sql = "INSERT INTO appointments (student_id, instructor_id, vehicle_id, course_selection, tdc_session_id, 
                        appointment_date, start_time, end_time, course_price, status, student_notes, 
                        payment_amount, payment_method, payment_reference, payment_status) 
                        VALUES (?, ?, ?, 'TDC', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                if ($stmt = mysqli_prepare($conn, $sql)) {
                    // Type string: i=integer, s=string, d=double
                    // 4 integers (student_id, instructor_id, vehicle_id, tdc_session_id) + 7 strings + 1 double (course_price) + 2 strings
                    mysqli_stmt_bind_param($stmt, "iiiisssdssdsss", 
                        $user_id, $preferred_instructor, $preferred_vehicle, $tdc_session_id,
                        $appointment_date, $start_time, $end_time, $course_price, $status, 
                        $notes, $payment_amount, $payment_method, $payment_reference, $payment_status);
                    
                    if (mysqli_stmt_execute($stmt)) {
                        echo json_encode(['success' => true, 'message' => 'TDC session booked successfully! Awaiting admin confirmation.']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Error booking session: ' . mysqli_error($conn)]);
                    }
                    mysqli_stmt_close($stmt);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
                }
            }
            
        } else if ($course_selection == 'PDC') {
            // PDC booking
            $appointment_date = $_POST['appointment_date'];
            $start_time = $_POST['start_time'];
            $duration_days = (int)$_POST['duration_days']; // 2 or 4 days
            $vehicle_type = $_POST['vehicle_type']; // motorcycle or car
            $vehicle_transmission = $_POST['vehicle_transmission']; // automatic or manual
            
            // Set price based on vehicle type
            $course_price = ($vehicle_type == 'motorcycle') ? 2000.00 : 4500.00;
            
            // Calculate end time (3 hours per session)
            $end_time = date('H:i:s', strtotime($start_time . ' + 3 hours'));
            
            // Insert PDC appointment
            $sql = "INSERT INTO appointments (student_id, course_selection, duration_days, vehicle_type, vehicle_transmission,
                    appointment_date, start_time, end_time, course_price, status, student_notes, 
                    payment_amount, payment_method, payment_reference, payment_status) 
                    VALUES (?, 'PDC', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            if ($stmt = mysqli_prepare($conn, $sql)) {
                // Type string: 2 integers + 5 strings + 1 double + 1 string + 1 double + 3 strings = 14 params
                mysqli_stmt_bind_param($stmt, "iisssssdsdsss", 
                    $user_id, $duration_days, $vehicle_type, $vehicle_transmission,
                    $appointment_date, $start_time, $end_time, $course_price, $status, 
                    $notes, $payment_amount, $payment_method, $payment_reference, $payment_status);
                
                if (mysqli_stmt_execute($stmt)) {
                    echo json_encode(['success' => true, 'message' => 'PDC appointment scheduled successfully! Awaiting admin confirmation.']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Error scheduling appointment: ' . mysqli_error($conn)]);
                }
                mysqli_stmt_close($stmt);
            } else {
                echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
            }
        }
        
        exit;
    }
    
    echo json_encode(['success' => false, 'message' => 'Invalid action.']);
    exit;
}

// ...existing code...

// Initialize variables for HTML output
$page_title = "Appointments";
$header_title = "Appointment Scheduling";
$notification_count = 2;
$user_id = $_SESSION["id"];

// Get instructors (for TDC only)
$available_instructors = [];
$result = mysqli_query($conn, "SELECT i.id, u.full_name, i.specializations, i.hourly_rate 
                               FROM instructors i 
                               JOIN users u ON i.user_id = u.id 
                               WHERE i.is_active = 1 
                               ORDER BY u.full_name");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $available_instructors[] = $row;
    }
}

// Get vehicles (for TDC only)
$available_vehicles = [];
$result = mysqli_query($conn, "SELECT id, make, model, license_plate, transmission_type 
                               FROM vehicles 
                               WHERE is_available = 1 
                               ORDER BY make, model");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $available_vehicles[] = $row;
    }
}

// Get upcoming appointments - Updated for new structure
$upcoming_appointments = [];
$sql = "SELECT a.*, a.course_selection, a.course_price, a.vehicle_type, a.vehicle_transmission, a.duration_days,
               u.full_name as instructor_name, v.make, v.model, v.license_plate,
               ts.session_day, ts.current_enrollments, ts.max_enrollments
        FROM appointments a 
        LEFT JOIN instructors i ON a.instructor_id = i.id
        LEFT JOIN users u ON i.user_id = u.id
        LEFT JOIN vehicles v ON a.vehicle_id = v.id
        LEFT JOIN tdc_sessions ts ON a.tdc_session_id = ts.id
        WHERE a.student_id = ? AND a.appointment_date >= CURDATE() AND a.status IN ('pending', 'confirmed')
        ORDER BY a.appointment_date, a.start_time
        LIMIT 10";

if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $upcoming_appointments[] = $row;
    }
    mysqli_stmt_close($stmt);
}

// Get appointment history - Updated for new structure
$appointment_history = [];
$sql_history = "SELECT a.*, a.course_selection, a.course_price, a.vehicle_type, a.vehicle_transmission, a.duration_days,
                       u.full_name as instructor_name, v.make, v.model, v.license_plate,
                       ts.session_day
                FROM appointments a 
                LEFT JOIN instructors i ON a.instructor_id = i.id
                LEFT JOIN users u ON i.user_id = u.id
                LEFT JOIN vehicles v ON a.vehicle_id = v.id
                LEFT JOIN tdc_sessions ts ON a.tdc_session_id = ts.id
                WHERE a.student_id = ? AND a.appointment_date < CURDATE()
                ORDER BY a.appointment_date DESC, a.start_time DESC
                LIMIT 20";

if ($stmt = mysqli_prepare($conn, $sql_history)) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $appointment_history[] = $row;
    }
    mysqli_stmt_close($stmt);
}

// Include access control
require_once "access_control.php";

ob_start();
?>

<div class="appointment-container">
    <!-- Access Status Banner -->
    <?php 
    displayAccessMessage(); // Show redirect message if any
    echo getAccessStatusHTML($_SESSION["id"], $conn); // Show current access status
    ?>
    
    <!-- Tab Navigation -->
    <div class="appointment-tabs">
        <button class="tab-btn active" onclick="switchTab('calendar')">
            <i class="far fa-calendar-alt"></i> Calendar View
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
            <button class="schedule-btn" onclick="openScheduleModal()">
                <i class="fas fa-plus"></i> Schedule New
            </button>
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

    <!-- Upcoming Appointments Tab -->
    <div id="upcoming-tab" class="tab-content">
        <div class="appointments-header">
            <h3>Upcoming Appointments</h3>
            <button class="schedule-btn" onclick="openScheduleModal()">
                <i class="fas fa-plus"></i> Schedule New
            </button>
        </div>
        
        <div class="appointments-list">
            <?php if (empty($upcoming_appointments)): ?>
                <div class="no-appointments">
                    <i class="far fa-calendar-times"></i>
                    <h4>No Upcoming Appointments</h4>
                    <p>Schedule your first appointment to get started!</p>
                    <button class="schedule-btn" onclick="openScheduleModal()">Schedule Now</button>
                </div>
            <?php else: ?>
                <div class="appointments-section">
                    <h4>Today</h4>
                    <?php 
                    $today = date('Y-m-d');
                    $today_appointments = array_filter($upcoming_appointments, function($apt) use ($today) {
                        return $apt['appointment_date'] == $today;
                    });
                    ?>
                    
                    <?php if (empty($today_appointments)): ?>
                        <p class="no-appointments-text">No appointments scheduled for today</p>
                    <?php else: ?>
                        <?php foreach ($today_appointments as $appointment): ?>
                            <div class="appointment-card today">
                                <div class="appointment-info">
                                    <div class="appointment-type">
                                        <?php 
                                        $courseLabel = $appointment['course_selection'];
                                        if ($appointment['course_selection'] == 'PDC' && $appointment['vehicle_type']) {
                                            $courseLabel .= ' - ' . ucfirst($appointment['vehicle_type']);
                                            if ($appointment['vehicle_transmission']) {
                                                $courseLabel .= ' (' . ucfirst($appointment['vehicle_transmission']) . ')';
                                            }
                                        }
                                        echo htmlspecialchars($courseLabel);
                                        ?>
                                        <span class="course-badge <?php echo strtolower($appointment['course_selection']); ?>">
                                            <?php echo $appointment['course_selection']; ?>
                                        </span>
                                        <?php if ($appointment['course_selection'] == 'PDC' && $appointment['duration_days']): ?>
                                            <span class="duration-badge"><?php echo $appointment['duration_days']; ?> Days</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="appointment-time">
                                        <i class="far fa-clock"></i>
                                        <?php echo date('g:i A', strtotime($appointment['start_time'])) . ' - ' . date('g:i A', strtotime($appointment['end_time'])); ?>
                                    </div>
                                    <div class="appointment-price">
                                        <i class="fas fa-tag"></i>
                                        ₱<?php echo number_format($appointment['course_price'], 2); ?>
                                    </div>
                                    <?php if ($appointment['course_selection'] == 'TDC'): ?>
                                        <?php if ($appointment['instructor_name']): ?>
                                            <div class="appointment-instructor">
                                                <i class="fas fa-user"></i>
                                                Instructor: <?php echo htmlspecialchars($appointment['instructor_name']); ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($appointment['session_day']): ?>
                                            <div class="appointment-day">
                                                <i class="fas fa-calendar-day"></i>
                                                <?php echo $appointment['session_day']; ?> Session
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                                <div class="appointment-status">
                                    <span class="status-badge <?php echo $appointment['status']; ?>">
                                        <?php echo ucfirst($appointment['status']); ?>
                                    </span>
                                    <button class="details-btn" onclick="viewAppointmentDetails(<?php echo $appointment['id']; ?>)">
                                        View Details
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <?php 
                $future_appointments = array_filter($upcoming_appointments, function($apt) use ($today) {
                    return $apt['appointment_date'] > $today;
                });
                ?>
                
                <?php if (!empty($future_appointments)): ?>
                    <div class="appointments-section">
                        <h4>Upcoming</h4>
                        <?php foreach ($future_appointments as $appointment): ?>
                            <div class="appointment-card">
                                <div class="appointment-info">
                                    <div class="appointment-type">
                                        <?php 
                                        $courseLabel = $appointment['course_selection'];
                                        if ($appointment['course_selection'] == 'PDC' && $appointment['vehicle_type']) {
                                            $courseLabel .= ' - ' . ucfirst($appointment['vehicle_type']);
                                            if ($appointment['vehicle_transmission']) {
                                                $courseLabel .= ' (' . ucfirst($appointment['vehicle_transmission']) . ')';
                                            }
                                        }
                                        echo htmlspecialchars($courseLabel);
                                        ?>
                                        <span class="course-badge <?php echo strtolower($appointment['course_selection']); ?>">
                                            <?php echo $appointment['course_selection']; ?>
                                        </span>
                                    </div>
                                    <div class="appointment-time">
                                        <i class="far fa-clock"></i>
                                        <?php echo date('M j, Y', strtotime($appointment['appointment_date'])) . ' at ' . date('g:i A', strtotime($appointment['start_time'])); ?>
                                    </div>
                                    <div class="appointment-price">
                                        <i class="fas fa-tag"></i>
                                        ₱<?php echo number_format($appointment['course_price'], 2); ?>
                                    </div>
                                </div>
                                <div class="appointment-status">
                                    <span class="status-badge <?php echo $appointment['status']; ?>">
                                        <?php echo ucfirst($appointment['status']); ?>
                                    </span>
                                    <button class="details-btn" onclick="viewAppointmentDetails(<?php echo $appointment['id']; ?>)">
                                        View Details
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
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
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($appointment_history as $appointment): ?>
                        <tr onclick="viewAppointmentDetails(<?php echo $appointment['id']; ?>)" style="cursor: pointer;">
                            <td><?php echo date('M j, Y', strtotime($appointment['appointment_date'])); ?></td>
                            <td><?php echo htmlspecialchars($_SESSION['full_name']); ?></td>
                            <td>
                                <?php 
                                $courseLabel = $appointment['course_selection'];
                                if ($appointment['course_selection'] == 'PDC' && $appointment['vehicle_type']) {
                                    $courseLabel .= ' - ' . ucfirst($appointment['vehicle_type']);
                                }
                                echo htmlspecialchars($courseLabel);
                                ?>
                            </td>
                            <td>
                                <span class="course-badge <?php echo strtolower($appointment['course_selection']); ?>">
                                    <?php echo $appointment['course_selection']; ?>
                                </span>
                                <br>
                                <small style="color: #8b8d93;">₱<?php echo number_format($appointment['course_price'], 2); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($appointment['instructor_name'] ?? ($appointment['course_selection'] == 'PDC' ? 'N/A' : 'Not Assigned')); ?></td>
                            <td>
                                <span class="status-badge <?php echo $appointment['status']; ?>">
                                    <?php echo ucfirst($appointment['status']); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Schedule New Appointment Modal -->
<div id="schedule-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Schedule New Appointment</h3>
            <span class="close-btn" onclick="closeScheduleModal()">&times;</span>
        </div>
        <form id="schedule-form">
            <!-- Course Selection -->
            <div class="form-group">
                <label for="course_selection">Select Course</label>
                <select id="course_selection" name="course_selection" required onchange="toggleCourseFields()">
                    <option value="">Choose a course</option>
                    <option value="TDC" data-price="899">TDC - Theoretical Driving Course (₱899)</option>
                    <option value="PDC">PDC - Practical Driving Course (₱2,000 - ₱4,500)</option>
                </select>
            </div>
            
            <!-- TDC Fields (Hidden by default) -->
            <div id="tdc-fields" style="display: none;">
                <h4 class="section-title">📚 TDC Session Details</h4>
                <div class="info-banner">
                    <i class="fas fa-info-circle"></i>
                    <p>TDC sessions are available every <strong>Friday and Saturday</strong>. Maximum <strong>10 students</strong> per session.</p>
                </div>
                
                <div class="form-group">
                    <label for="tdc_session">Select TDC Session <span class="required">*</span></label>
                    <select id="tdc_session" name="tdc_session_id">
                        <option value="">Loading sessions...</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="tdc_instructor">Preferred Instructor</label>
                    <select id="tdc_instructor" name="preferred_instructor">
                        <option value="">Any available instructor</option>
                        <?php foreach ($available_instructors as $instructor): ?>
                            <option value="<?php echo $instructor['id']; ?>">
                                <?php echo htmlspecialchars($instructor['full_name']); ?>
                                <?php if ($instructor['specializations']): ?>
                                    - <?php echo htmlspecialchars($instructor['specializations']); ?>
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="tdc_vehicle">Preferred Vehicle</label>
                    <select id="tdc_vehicle" name="preferred_vehicle">
                        <option value="">Any available vehicle</option>
                        <?php foreach ($available_vehicles as $vehicle): ?>
                            <option value="<?php echo $vehicle['id']; ?>">
                                <?php echo htmlspecialchars($vehicle['make'] . ' ' . $vehicle['model'] . ' (' . $vehicle['license_plate'] . ')'); ?>
                                - <?php echo ucfirst($vehicle['transmission_type']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <!-- PDC Fields (Hidden by default) -->
            <div id="pdc-fields" style="display: none;">
                <h4 class="section-title">🚗 PDC Session Details</h4>
                
                <div class="form-group">
                    <label for="pdc_vehicle_type">Vehicle Type <span class="required">*</span></label>
                    <div class="vehicle-options">
                        <label class="radio-card">
                            <input type="radio" name="vehicle_type" value="motorcycle" onchange="updatePDCPrice()">
                            <div class="radio-content">
                                <i class="fas fa-motorcycle"></i>
                                <span class="radio-title">Motorcycle</span>
                                <span class="radio-price">₱2,000</span>
                            </div>
                        </label>
                        <label class="radio-card">
                            <input type="radio" name="vehicle_type" value="car" onchange="updatePDCPrice()">
                            <div class="radio-content">
                                <i class="fas fa-car"></i>
                                <span class="radio-title">Car</span>
                                <span class="radio-price">₱4,500</span>
                            </div>
                        </label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="pdc_transmission">Transmission Type <span class="required">*</span></label>
                    <select id="pdc_transmission" name="vehicle_transmission">
                        <option value="">Select transmission</option>
                        <option value="automatic">Automatic</option>
                        <option value="manual">Manual</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="pdc_duration">Course Duration <span class="required">*</span></label>
                    <select id="pdc_duration" name="duration_days">
                        <option value="">Select duration</option>
                        <option value="2">2 Days</option>
                        <option value="4">4 Days</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="pdc_date">Start Date <span class="required">*</span></label>
                    <input type="date" id="pdc_date" name="appointment_date" min="<?php echo date('Y-m-d'); ?>">
                    <small class="form-help">Choose your preferred start date from available schedules</small>
                </div>
                
                <div class="form-group">
                    <label for="pdc_time">Preferred Time <span class="required">*</span></label>
                    <select id="pdc_time" name="start_time">
                        <option value="">Select time</option>
                        <option value="08:00">8:00 AM - 11:00 AM</option>
                        <option value="11:00">11:00 AM - 2:00 PM</option>
                        <option value="14:00">2:00 PM - 5:00 PM</option>
                    </select>
                </div>
            </div>
            
            <!-- Notes for both courses -->
            <div class="form-group">
                <label for="notes">Notes (Optional)</label>
                <textarea id="notes" name="notes" rows="3" placeholder="Any special requests or notes..."></textarea>
            </div>
            
            <!-- Payment Information -->
            <div class="payment-section">
                <h4 class="payment-title">💰 Payment Information</h4>
                <div class="payment-notice">
                    <i class="fas fa-info-circle"></i>
                    <p><strong>Required:</strong> 20% down payment to secure your appointment. Full access to Dashboard and E-Learning will be granted after admin approval.</p>
                </div>
                
                <!-- GCash QR Code -->
                <div class="gcash-section">
                    <h5>Pay via GCash</h5>
                    <div class="gcash-qr-container">
                        <img src="../assets/images/dss_gcash.png" alt="GCash QR Code" class="gcash-qr">
                        <p class="gcash-instructions">Scan the QR code with your GCash app to pay the 20% down payment</p>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="payment_amount">20% Down Payment Amount <span class="required">*</span></label>
                    <input type="number" id="payment_amount" name="payment_amount" step="0.01" min="0" placeholder="0.00" required readonly>
                    <small class="form-help" id="payment_calculation_info">Select a course to see payment amount</small>
                </div>
                
                <div class="form-group">
                    <label for="payment_method">Payment Method <span class="required">*</span></label>
                    <select id="payment_method" name="payment_method" onchange="togglePaymentReference()" required>
                        <option value="">Select payment method</option>
                        <option value="online">GCash / Online Payment</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="card">Credit/Debit Card</option>
                        <option value="cash">Cash</option>
                    </select>
                </div>
                
                <div class="form-group" id="payment_reference_group" style="display: none;">
                    <label for="payment_reference">Payment Reference Number <span class="required">*</span></label>
                    <input type="text" id="payment_reference" name="payment_reference" placeholder="Enter GCash reference number" required>
                    <small class="form-help">Enter the 13-digit GCash reference number from your transaction receipt.</small>
                </div>
            </div>
            
          
            
            <div class="form-actions">
                <button type="button" onclick="closeScheduleModal()" class="cancel-btn">Cancel</button>
                <button type="submit" class="submit-btn">Schedule Appointment</button>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();

// Add additional styles - ADD COURSE BADGE STYLES
$extra_styles = <<<'EOT'
<style>
/* Course Type Badges */
.course-badge {
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
    display: inline-block;
    margin-left: 8px;
}

.course-badge.tdc {
    background: rgba(156, 39, 176, 0.2);
    color: #9c27b0;
    border: 1px solid rgba(156, 39, 176, 0.3);
}

.course-badge.pdc {
    background: rgba(255, 152, 0, 0.2);
    color: #ff9800;
    border: 1px solid rgba(255, 152, 0, 0.3);
}

.duration-badge {
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 10px;
    font-weight: 600;
    display: inline-block;
    margin-left: 5px;
    background: rgba(33, 150, 243, 0.2);
    color: #2196f3;
    border: 1px solid rgba(33, 150, 243, 0.3);
}

.appointment-price,
.appointment-day {
    color: #8b8d93;
    font-size: 13px;
    margin-bottom: 4px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.appointment-container {
    max-width: 1200px;
    margin: 0 auto;
}

/* Access Status Styles */
.access-notice-banner {
    background: linear-gradient(135deg, rgba(255, 77, 77, 0.2), rgba(255, 204, 0, 0.2));
    border: 2px solid #ffcc00;
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 30px;
    animation: pulse 2s infinite;
}

.access-notice-content {
    display: flex;
    align-items: flex-start;
    gap: 15px;
}

.access-notice-content i {
    font-size: 32px;
    color: #ffcc00;
    margin-top: 5px;
}

.access-notice-text h4 {
    margin: 0 0 10px 0;
    color: #ffcc00;
    font-size: 20px;
}

.access-notice-text p {
    margin: 0;
    color: #fff;
    font-size: 16px;
    line-height: 1.5;
}

.access-status-granted {
    background: linear-gradient(135deg, rgba(76, 175, 80, 0.2), rgba(129, 199, 132, 0.2));
    border: 2px solid #4caf50;
    border-radius: 10px;
    padding: 15px 20px;
    margin-bottom: 30px;
    display: flex;
    align-items: center;
    gap: 15px;
}

.access-status-granted i {
    font-size: 28px;
    color: #4caf50;
}

.access-status-granted span {
    color: #4caf50;
    font-size: 18px;
    font-weight: 600;
}

.access-status-locked {
    background: linear-gradient(135deg, rgba(255, 152, 0, 0.1), rgba(255, 193, 7, 0.1));
    border: 2px solid #ff9800;
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 30px;
    display: flex;
    gap: 20px;
}

.access-status-locked > i {
    font-size: 48px;
    color: #ffcc00;
    margin-top: 5px;
}

.access-status-info h4 {
    margin: 0 0 10px 0;
    color: #ffcc00;
    font-size: 20px;
}

.access-status-info p {
    margin: 0 0 20px 0;
    color: #fff;
    font-size: 15px;
    line-height: 1.6;
}

.access-checklist {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.checklist-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px;
    background: rgba(0, 0, 0, 0.3);
    border-radius: 8px;
}

.checklist-item i {
    font-size: 20px;
}

.checklist-item.completed i {
    color: #4caf50;
}

.checklist-item.pending i {
    color: #666;
}

.checklist-item span {
    color: #fff;
    font-size: 14px;
    font-weight: 500;
}

@keyframes pulse {
    0%, 100% {
        box-shadow: 0 0 0 0 rgba(255, 204, 0, 0.4);
    }
    50% {
        box-shadow: 0 0 0 10px rgba(255, 204, 0, 0);
    }
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
}

.tab-btn.active,
.tab-btn:hover {
    color: #ffcc00;
    border-bottom-color: #ffcc00;
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
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

.schedule-btn {
    background: #ffcc00;
    color: #1a1d24;
    border: none;
    padding: 10px 20px;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}

.schedule-btn:hover {
    background: #e6b800;
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
    min-height: 100px;
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
    font-size: 10px;
    padding: 2px 6px;
    border-radius: 3px;
    margin-bottom: 2px;
    text-overflow: ellipsis;
    overflow: hidden;
    white-space: nowrap;
}

/* Appointments List Styles */
.appointments-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.appointments-list {
    max-width: 100%;
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

.appointments-section {
    margin-bottom: 30px;
}

.appointments-section h4 {
    color: #ffcc00;
    margin-bottom: 15px;
    font-size: 18px;
    font-weight: 600;
}

.appointment-card {
    background: #282c34;
    border: 1px solid #3a3f48;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 15px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: all 0.3s;
}

.appointment-card:hover {
    border-color: #ffcc00;
    transform: translateY(-2px);
}

.appointment-card.today {
    border-color: #4CAF50;
    background: rgba(76, 175, 80, 0.05);
}

.appointment-info {
    flex: 1;
}

.appointment-type {
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 8px;
}

.appointment-time,
.appointment-instructor,
.appointment-vehicle {
    color: #8b8d93;
    font-size: 14px;
    margin-bottom: 4px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.appointment-status {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 10px;
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

.status-badge.completed {
    background: rgba(76, 175, 80, 0.2);
    color: #4CAF50;
}

.status-badge.cancelled {
    background: rgba(244, 67, 54, 0.2);
    color: #f44336;
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
}

.details-btn:hover {
    border-color: #ffcc00;
    color: #ffcc00;
}

/* History Table Styles */
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

/* Checkbox styling */
.checkbox-label {
    display: flex !important;
    align-items: center;
    cursor: pointer;
    user-select: none;
    position: relative;
}

.checkbox-label input[type="checkbox"] {
    width: auto !important;
    margin-right: 10px;
    transform: scale(1.2);
}

.form-help {
    color: #8b8d93;
    font-size: 11px;
    margin-top: 5px;
    display: block;
}

/* Section Styles */
.section-title {
    color: #ffcc00;
    font-size: 18px;
    margin: 20px 0 15px 0;
    padding-bottom: 10px;
    border-bottom: 2px solid #3a3f48;
}

.info-banner {
    background: rgba(255, 204, 0, 0.1);
    border-left: 4px solid #ffcc00;
    padding: 12px;
    margin-bottom: 20px;
    display: flex;
    align-items: flex-start;
    gap: 10px;
    border-radius: 4px;
}

.info-banner i {
    color: #ffcc00;
    font-size: 18px;
    margin-top: 2px;
}

.info-banner p {
    color: #fff;
    font-size: 14px;
    margin: 0;
    line-height: 1.5;
}

.info-banner strong {
    color: #ffcc00;
}

/* Radio Card Styles for Vehicle Selection */
.vehicle-options {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    margin-top: 10px;
}

.radio-card {
    position: relative;
    cursor: pointer;
    display: block;
}

.radio-card input[type="radio"] {
    position: absolute;
    opacity: 0;
    cursor: pointer;
}

.radio-content {
    background: #1e2129;
    border: 2px solid #3a3f48;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
    transition: all 0.3s;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
}

.radio-card:hover .radio-content {
    border-color: #ffcc00;
    background: rgba(255, 204, 0, 0.05);
}

.radio-card input[type="radio"]:checked + .radio-content {
    border-color: #ffcc00;
    background: rgba(255, 204, 0, 0.1);
    box-shadow: 0 0 0 3px rgba(255, 204, 0, 0.1);
}

.radio-content i {
    font-size: 32px;
    color: #8b8d93;
    transition: color 0.3s;
}

.radio-card input[type="radio"]:checked + .radio-content i {
    color: #ffcc00;
}

.radio-title {
    font-weight: 600;
    color: #fff;
    font-size: 16px;
}

.radio-price {
    font-weight: 700;
    color: #ffcc00;
    font-size: 18px;
}

/* Payment Section Styles */
.payment-section {
    background: #1e2129;
    border: 2px solid #3a3f48;
    border-radius: 8px;
    padding: 20px;
    margin: 20px 0;
}

.payment-title {
    color: #ffcc00;
    margin-bottom: 15px;
    font-size: 18px;
}

.payment-notice {
    background: rgba(255, 204, 0, 0.1);
    border-left: 4px solid #ffcc00;
    padding: 12px;
    margin-bottom: 20px;
    display: flex;
    align-items: flex-start;
    gap: 10px;
}

.payment-notice i {
    color: #ffcc00;
    margin-top: 2px;
    font-size: 18px;
}

.payment-notice p {
    margin: 0;
    color: #fff;
    font-size: 14px;
    line-height: 1.5;
}

.payment-notice strong {
    color: #ffcc00;
}

.gcash-section {
    background: #282c34;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 20px;
    text-align: center;
}

.gcash-section h5 {
    color: #fff;
    margin-bottom: 15px;
    font-size: 16px;
}

.gcash-qr-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
}

.gcash-qr {
    max-width: 250px;
    width: 100%;
    height: auto;
    border: 3px solid #ffcc00;
    border-radius: 8px;
    padding: 10px;
    background: white;
}

.gcash-instructions {
    color: #8b8d93;
    font-size: 13px;
    margin: 0;
}

.required {
    color: #ff4444;
    margin-left: 3px;
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

/* Responsive */
@media (max-width: 768px) {
    .appointment-card {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
    
    .appointment-status {
        align-items: flex-start;
        flex-direction: row;
        width: 100%;
        justify-content: space-between;
    }
    
    .calendar-day {
        min-height: 80px;
        padding: 4px;
    }
    
    .appointment-tabs {
        flex-direction: column;
    }
    
    .tab-btn {
        text-align: left;
    }
}
</style>
EOT;

// Add additional scripts - UPDATE CALENDAR TO SHOW COURSE TYPE
$extra_scripts = <<<'EOT'
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
    const selectedTab = document.getElementById(tabName + '-tab');
    if (selectedTab) {
        selectedTab.classList.add('active');
    }
    
    // Add active class to clicked button
    const clickedBtn = document.querySelector("[onclick=\"switchTab('" + tabName + "')\"]");
    if (clickedBtn) {
        clickedBtn.classList.add('active');
    }
    
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
    .then(response => {
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);
        return response.text();
    })
    .then(text => {
        console.log('Raw response:', text);
        try {
            const appointments = JSON.parse(text);
            console.log('Parsed appointments:', appointments);
            
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
                        '<div class="appointment-indicator" style="background-color: ' + (apt.color || '#4CAF50') + '" title="' + apt.time + ' - ' + apt.type + ' (' + apt.course_type + ')">' + 
                        apt.time + ' ' + apt.type + ' (' + apt.course_type + ')' +
                        '</div>'
                    ).join('');
                
                calendarDays.appendChild(dayElement);
            }
        } catch (e) {
            console.error('JSON parsing error:', e);
            console.error('Response text:', text);
            
            // Still generate calendar even if no appointments
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
                
                dayElement.innerHTML = '<div class="day-number">' + date.getDate() + '</div>';
                
                calendarDays.appendChild(dayElement);
            }
        }
    })
    .catch(error => {
        console.error('Network error:', error);
        
        // Still generate calendar even if network error
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
            
            dayElement.innerHTML = '<div class="day-number">' + date.getDate() + '</div>';
            
            calendarDays.appendChild(dayElement);
        }
    });
}

// Modal functions
function openScheduleModal() {
    document.getElementById('schedule-modal').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closeScheduleModal() {
    document.getElementById('schedule-modal').style.display = 'none';
    document.body.style.overflow = 'auto';
    document.getElementById('schedule-form').reset();
    // Hide payment reference field when modal is closed
    document.getElementById('payment_reference_group').style.display = 'none';
}

// Toggle TDC/PDC fields based on course selection
function toggleCourseFields() {
    const courseSelection = document.getElementById('course_selection').value;
    const tdcFields = document.getElementById('tdc-fields');
    const pdcFields = document.getElementById('pdc-fields');
    
    // Hide both sections
    tdcFields.style.display = 'none';
    pdcFields.style.display = 'none';
    
    // Clear required attributes
    document.querySelectorAll('#tdc-fields select, #tdc-fields input').forEach(el => {
        el.removeAttribute('required');
    });
    document.querySelectorAll('#pdc-fields select, #pdc-fields input').forEach(el => {
        el.removeAttribute('required');
    });
    
    if (courseSelection === 'TDC') {
        tdcFields.style.display = 'block';
        document.getElementById('tdc_session').setAttribute('required', 'required');
        
        // Load TDC sessions
        loadTDCSessions();
        
        // Calculate payment: TDC is ₱899
        const price = 899;
        const downPayment = (price * 0.20).toFixed(2);
        document.getElementById('payment_amount').value = downPayment;
        document.getElementById('payment_calculation_info').textContent = `20% of ₱${price} = ₱${downPayment}`;
        
    } else if (courseSelection === 'PDC') {
        pdcFields.style.display = 'block';
        document.querySelectorAll('input[name="vehicle_type"]').forEach(el => {
            el.setAttribute('required', 'required');
        });
        document.getElementById('pdc_transmission').setAttribute('required', 'required');
        document.getElementById('pdc_duration').setAttribute('required', 'required');
        document.getElementById('pdc_date').setAttribute('required', 'required');
        document.getElementById('pdc_time').setAttribute('required', 'required');
        
        // Payment will be calculated after vehicle type selection
        document.getElementById('payment_amount').value = '';
        document.getElementById('payment_calculation_info').textContent = 'Select vehicle type to calculate payment';
    }
}

// Load available TDC sessions
function loadTDCSessions() {
    fetch('', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=get_tdc_sessions'
    })
    .then(response => response.json())
    .then(sessions => {
        const select = document.getElementById('tdc_session');
        select.innerHTML = '<option value="">Select a session</option>';
        
        sessions.forEach(session => {
            const option = document.createElement('option');
            option.value = session.id;
            option.textContent = `${session.day}, ${new Date(session.date).toLocaleDateString('en-US', {month: 'short', day: 'numeric', year: 'numeric'})} - ${session.start_time} to ${session.end_time} (${session.available_slots}/${session.max_enrollments} slots available)`;
            option.disabled = session.is_full;
            if (session.is_full) {
                option.textContent += ' - FULL';
            }
            select.appendChild(option);
        });
    })
    .catch(error => {
        console.error('Error loading TDC sessions:', error);
        alert('Failed to load TDC sessions. Please refresh and try again.');
    });
}

// Update PDC price based on vehicle selection
function updatePDCPrice() {
    const vehicleType = document.querySelector('input[name="vehicle_type"]:checked');
    if (vehicleType) {
        const price = vehicleType.value === 'motorcycle' ? 2000 : 4500;
        const downPayment = (price * 0.20).toFixed(2);
        document.getElementById('payment_amount').value = downPayment;
        document.getElementById('payment_calculation_info').textContent = `20% of ₱${price} = ₱${downPayment}`;
    }
}

// Toggle payment reference field based on payment method
function togglePaymentReference() {
    const paymentMethod = document.getElementById('payment_method').value;
    const referenceGroup = document.getElementById('payment_reference_group');
    const referenceInput = document.getElementById('payment_reference');
    
    // Show reference field for card, bank_transfer, and online payments
    if (paymentMethod === 'card' || paymentMethod === 'bank_transfer' || paymentMethod === 'online') {
        referenceGroup.style.display = 'block';
        referenceInput.required = true;
        
        // Update placeholder based on payment method
        if (paymentMethod === 'online') {
            referenceInput.placeholder = 'Enter GCash reference number (13 digits)';
        } else if (paymentMethod === 'bank_transfer') {
            referenceInput.placeholder = 'Enter bank transaction reference';
        } else {
            referenceInput.placeholder = 'Enter transaction/reference number';
        }
    } else {
        referenceGroup.style.display = 'none';
        referenceInput.required = false;
        referenceInput.value = ''; // Clear the field when hidden
    }
}

// Form submission
document.getElementById('schedule-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('action', 'schedule_appointment');
    
    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Form response status:', response.status);
        return response.text();
    })
    .then(text => {
        console.log('Form raw response:', text);
        try {
            const data = JSON.parse(text);
            if (data.success) {
                alert(data.message);
                closeScheduleModal();
                location.reload(); // Refresh to show new appointment
            } else {
                alert(data.message);
            }
        } catch (e) {
            console.error('JSON parsing error:', e);
            console.error('Response text:', text);
            alert('An error occurred processing the response. Please check the console for details.');
        }
    })
    .catch(error => {
        console.error('Network error:', error);
        alert('A network error occurred. Please try again.');
    });
});

function viewAppointmentDetails(appointmentId) {
    // This would open a modal with appointment details
    // For now, just show an alert
    alert('Appointment details for ID: ' + appointmentId);
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('schedule-modal');
    if (event.target === modal) {
        closeScheduleModal();
    }
}

// Initialize calendar on page load
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM Content Loaded');
    console.log('Current view:', currentView);
    
    // Check if required elements exist
    const calendarDays = document.getElementById('calendar-days');
    const calendarTitle = document.getElementById('calendar-title');
    const scheduleForm = document.getElementById('schedule-form');
    
    if (!calendarDays) {
        console.error('calendar-days element not found!');
        return;
    }
    
    if (!calendarTitle) {
        console.error('calendar-title element not found!');
        return;
    }
    
    if (!scheduleForm) {
        console.error('schedule-form element not found!');
        return;
    }
    
    console.log('All required elements found');
    
    if (currentView === 'calendar') {
        console.log('Loading calendar...');
        setTimeout(function() {
            loadCalendar();
        }, 100);
    }
});
</script>
EOT;

// Include the main layout template
include "../layouts/main_layout.php";
?>