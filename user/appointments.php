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
    
    if ($_POST['action'] == 'get_calendar_events') {
        $year = isset($_POST['year']) ? (int)$_POST['year'] : date('Y');
        $month = isset($_POST['month']) ? (int)$_POST['month'] : date('n');
        
        $events = [];
        $sql = "SELECT a.*, at.name as type_name, at.color 
                FROM appointments a 
                LEFT JOIN appointment_types at ON a.appointment_type_id = at.id
                WHERE a.student_id = ? AND YEAR(a.appointment_date) = ? AND MONTH(a.appointment_date) = ?";
        
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "iii", $user_id, $year, $month);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            while ($row = mysqli_fetch_assoc($result)) {
                $events[] = [
                    'id' => $row['id'],
                    'date' => $row['appointment_date'], // This should be YYYY-MM-DD format
                    'time' => date('g:i A', strtotime($row['start_time'])),
                    'type' => $row['type_name'] ?: 'Appointment',
                    'course_type' => strtoupper($row['course_type'] ?? 'PDC'), // Add course type
                    'status' => $row['status'],
                    'color' => $row['color'] ?: '#4CAF50'
                ];
            }
            mysqli_stmt_close($stmt);
        }
        
        echo json_encode($events);
        exit;
    }
    
    if ($_POST['action'] == 'schedule_appointment') {
        $appointment_type_id = $_POST['appointment_type_id'];
        $course_type = $_POST['course_type']; // Add course type
        $appointment_date = $_POST['appointment_date'];
        $start_time = $_POST['start_time'];
        $preferred_instructor = !empty($_POST['preferred_instructor']) ? $_POST['preferred_instructor'] : null;
        $preferred_vehicle = !empty($_POST['preferred_vehicle']) ? $_POST['preferred_vehicle'] : null;
        $notes = $_POST['notes'] ?? '';
        
        // Get duration from appointment type
        $duration = 60; // default
        $duration_sql = "SELECT duration_minutes FROM appointment_types WHERE id = ?";
        if ($stmt = mysqli_prepare($conn, $duration_sql)) {
            mysqli_stmt_bind_param($stmt, "i", $appointment_type_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            if ($row = mysqli_fetch_assoc($result)) {
                $duration = $row['duration_minutes'];
            }
            mysqli_stmt_close($stmt);
        }
        
        $end_time = date('H:i:s', strtotime($start_time . " + $duration minutes"));
        
        // FIXED: Create variables for string literals
        $status = 'pending';
        
        // Insert appointment with course_type
        $sql = "INSERT INTO appointments (student_id, instructor_id, vehicle_id, appointment_type_id, course_type, appointment_date, start_time, end_time, status, student_notes) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        if ($stmt = mysqli_prepare($conn, $sql)) {
            // FIXED: Pass variables instead of string literals
            mysqli_stmt_bind_param($stmt, "iiiissssss", $user_id, $preferred_instructor, $preferred_vehicle, $appointment_type_id, $course_type, $appointment_date, $start_time, $end_time, $status, $notes);
            
            if (mysqli_stmt_execute($stmt)) {
                echo json_encode(['success' => true, 'message' => 'Appointment scheduled successfully!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error scheduling appointment: ' . mysqli_error($conn)]);
            }
            mysqli_stmt_close($stmt);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
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

// Get appointment types
$appointment_types = [];
$result = mysqli_query($conn, "SELECT * FROM appointment_types WHERE is_active = 1 ORDER BY name");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $appointment_types[] = $row;
    }
}

// Get instructors
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

// Get vehicles
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

// Get upcoming appointments - UPDATED SQL
$upcoming_appointments = [];
$sql = "SELECT a.*, at.name as type_name, u.full_name as instructor_name, v.make, v.model, v.license_plate
        FROM appointments a 
        LEFT JOIN appointment_types at ON a.appointment_type_id = at.id
        LEFT JOIN instructors i ON a.instructor_id = i.id
        LEFT JOIN users u ON i.user_id = u.id
        LEFT JOIN vehicles v ON a.vehicle_id = v.id
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

// Get appointment history - UPDATED SQL
$appointment_history = [];
$sql_history = "SELECT a.*, at.name as type_name, u.full_name as instructor_name, v.make, v.model, v.license_plate
                FROM appointments a 
                LEFT JOIN appointment_types at ON a.appointment_type_id = at.id
                LEFT JOIN instructors i ON a.instructor_id = i.id
                LEFT JOIN users u ON i.user_id = u.id
                LEFT JOIN vehicles v ON a.vehicle_id = v.id
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

ob_start();
?>

<div class="appointment-container">
    <!-- Tab Navigation -->
    <div class="appointment-tabs">
        <button class="tab-btn active" onclick="switchTab('calendar')">
            <i class="far fa-calendar-alt"></i> Calendar View
        </button>
        <button class="tab-btn" onclick="switchTab('upcoming')">
            <i class="far fa-clock"></i> Upcoming Appointments
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
                                        <?php echo htmlspecialchars($appointment['type_name']); ?>
                                        <?php if (!empty($appointment['course_type'])): ?>
                                            <span class="course-badge <?php echo $appointment['course_type']; ?>">
                                                <?php echo strtoupper($appointment['course_type']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="appointment-time">
                                        <i class="far fa-clock"></i>
                                        <?php echo date('g:i A', strtotime($appointment['start_time'])) . ' - ' . date('g:i A', strtotime($appointment['end_time'])); ?>
                                    </div>
                                    <?php if ($appointment['instructor_name']): ?>
                                        <div class="appointment-instructor">
                                            <i class="fas fa-user"></i>
                                            Instructor: <?php echo htmlspecialchars($appointment['instructor_name']); ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($appointment['make'] && $appointment['model']): ?>
                                        <div class="appointment-vehicle">
                                            <i class="fas fa-car"></i>
                                            <?php echo htmlspecialchars($appointment['make'] . ' ' . $appointment['model'] . ' (' . $appointment['license_plate'] . ')'); ?>
                                        </div>
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
                                        <?php echo htmlspecialchars($appointment['type_name']); ?>
                                        <?php if (!empty($appointment['course_type'])): ?>
                                            <span class="course-badge <?php echo $appointment['course_type']; ?>">
                                                <?php echo strtoupper($appointment['course_type']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="appointment-time">
                                        <i class="far fa-clock"></i>
                                        <?php echo date('M j, Y', strtotime($appointment['appointment_date'])) . ' at ' . date('g:i A', strtotime($appointment['start_time'])); ?>
                                    </div>
                                    <?php if ($appointment['instructor_name']): ?>
                                        <div class="appointment-instructor">
                                            <i class="fas fa-user"></i>
                                            Instructor: <?php echo htmlspecialchars($appointment['instructor_name']); ?>
                                        </div>
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
                            <td><?php echo htmlspecialchars($appointment['type_name']); ?></td>
                            <td>
                                <?php if (!empty($appointment['course_type'])): ?>
                                    <span class="course-badge <?php echo $appointment['course_type']; ?>">
                                        <?php echo strtoupper($appointment['course_type']); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="course-badge pdc">PDC</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($appointment['instructor_name'] ?? 'Not Assigned'); ?></td>
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
            <div class="form-group">
                <label for="appointment_type">Appointment Type</label>
                <select id="appointment_type" name="appointment_type_id" required>
                    <option value="">Select appointment type</option>
                    <?php foreach ($appointment_types as $type): ?>
                        <option value="<?php echo $type['id']; ?>" data-duration="<?php echo $type['duration_minutes']; ?>" data-price="<?php echo $type['price']; ?>">
                            <?php echo htmlspecialchars($type['name']); ?> - $<?php echo number_format($type['price'], 2); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- ADD COURSE TYPE SELECTION -->
            <div class="form-group">
                <label for="course_type">Course Type</label>
                <select id="course_type" name="course_type" required>
                    <option value="">Select Course Type</option>
                    <option value="tpc">TPC - Theoretical Driving Course</option>
                    <option value="pdc">PDC - Practical Driving Course</option>
                    <option value="both">Both TPC & PDC</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="appointment_date">Date</label>
                <input type="date" id="appointment_date" name="appointment_date" min="<?php echo date('Y-m-d'); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="start_time">Time</label>
                <select id="start_time" name="start_time" required>
                    <option value="">Select time</option>
                    <?php for ($hour = 8; $hour <= 17; $hour++): ?>
                        <?php for ($minute = 0; $minute < 60; $minute += 30): ?>
                            <?php 
                            $time = sprintf('%02d:%02d', $hour, $minute);
                            $display_time = date('g:i A', strtotime($time));
                            ?>
                            <option value="<?php echo $time; ?>"><?php echo $display_time; ?></option>
                        <?php endfor; ?>
                    <?php endfor; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="preferred_instructor">Preferred Instructor (Optional)</label>
                <select id="preferred_instructor" name="preferred_instructor">
                    <option value="">Any available instructor</option>
                    <?php foreach ($available_instructors as $instructor): ?>
                        <option value="<?php echo $instructor['id']; ?>" data-rate="<?php echo $instructor['hourly_rate']; ?>">
                            <?php echo htmlspecialchars($instructor['full_name']); ?>
                            <?php if ($instructor['specializations']): ?>
                                - <?php echo htmlspecialchars($instructor['specializations']); ?>
                            <?php endif; ?>
                            ($<?php echo number_format($instructor['hourly_rate'], 2); ?>/hr)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="preferred_vehicle">Preferred Vehicle (Optional)</label>
                <select id="preferred_vehicle" name="preferred_vehicle">
                    <option value="">Any available vehicle</option>
                    <?php foreach ($available_vehicles as $vehicle): ?>
                        <option value="<?php echo $vehicle['id']; ?>">
                            <?php echo htmlspecialchars($vehicle['make'] . ' ' . $vehicle['model'] . ' (' . $vehicle['license_plate'] . ')'); ?>
                            - <?php echo ucfirst($vehicle['transmission_type']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="notes">Notes (Optional)</label>
                <textarea id="notes" name="notes" rows="3" placeholder="Any special requests or notes..."></textarea>
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
$extra_styles = <<<EOT
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

.course-badge.tpc {
    background: rgba(156, 39, 176, 0.2);
    color: #9c27b0;
    border: 1px solid rgba(156, 39, 176, 0.3);
}

.course-badge.pdc {
    background: rgba(255, 152, 0, 0.2);
    color: #ff9800;
    border: 1px solid rgba(255, 152, 0, 0.3);
}

.course-badge.both {
    background: rgba(63, 81, 181, 0.2);
    color: #3f51b5;
    border: 1px solid rgba(63, 81, 181, 0.3);
}

.appointment-container {
    max-width: 1200px;
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