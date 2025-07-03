<?php
// Turn off error reporting for AJAX requests to prevent HTML output
error_reporting(0);
ini_set('display_errors', 0);

// Start output buffering immediately
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
$page_title = "Instructors";
$header_title = "Instructor Management";
$notification_count = 3;

// Function to generate random password
function generatePassword($length = 8) {
    $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $password;
}

// Function to send email (basic implementation - you can enhance this)
function sendWelcomeEmail($email, $full_name, $password) {
    $subject = "Welcome to Success Driving School - Instructor Account";
    $message = "
    Dear $full_name,
    
    Welcome to Success Driving School! Your instructor account has been created.
    
    Login Details:
    Email: $email
    Password: $password
    
    Please login and change your password after your first login.
    
    Best regards,
    Success Driving School Admin
    ";
    
    $headers = "From: admin@successdrivingschool.com\r\n";
    $headers .= "Reply-To: admin@successdrivingschool.com\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    
    return mail($email, $subject, $message, $headers);
}

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
        case 'add_instructor':
            $full_name = $_POST['full_name'];
            $email = $_POST['email'];
            $contact_number = $_POST['contact_number'];
            $license_number = $_POST['license_number'];
            $specializations = $_POST['specializations'];
            $years_experience = $_POST['years_experience'];
            $hourly_rate = $_POST['hourly_rate'];
            
            // Generate random password
            $generated_password = generatePassword(10);
            $hashed_password = password_hash($generated_password, PASSWORD_DEFAULT);
            
            // Start transaction
            mysqli_begin_transaction($conn);
            
            try {
                // Check if email already exists
                $check_email = "SELECT id FROM users WHERE email = ?";
                $stmt = mysqli_prepare($conn, $check_email);
                mysqli_stmt_bind_param($stmt, "s", $email);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                
                if (mysqli_num_rows($result) > 0) {
                    throw new Exception("Email already exists in the system");
                }
                mysqli_stmt_close($stmt);
                
                // Check if license number already exists
                $check_license = "SELECT id FROM instructors WHERE license_number = ?";
                $stmt = mysqli_prepare($conn, $check_license);
                mysqli_stmt_bind_param($stmt, "s", $license_number);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                
                if (mysqli_num_rows($result) > 0) {
                    throw new Exception("License number already exists");
                }
                mysqli_stmt_close($stmt);
                
                // Insert user
                $user_sql = "INSERT INTO users (full_name, email, password, user_type, contact_number) VALUES (?, ?, ?, 'instructor', ?)";
                $stmt = mysqli_prepare($conn, $user_sql);
                mysqli_stmt_bind_param($stmt, "ssss", $full_name, $email, $hashed_password, $contact_number);
                
                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception("Error creating user account");
                }
                
                $user_id = mysqli_insert_id($conn);
                mysqli_stmt_close($stmt);
                
                // Insert instructor details
                $instructor_sql = "INSERT INTO instructors (user_id, license_number, specializations, years_experience, hourly_rate) VALUES (?, ?, ?, ?, ?)";
                $stmt = mysqli_prepare($conn, $instructor_sql);
                mysqli_stmt_bind_param($stmt, "isiid", $user_id, $license_number, $specializations, $years_experience, $hourly_rate);
                
                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception("Error creating instructor profile");
                }
                
                mysqli_stmt_close($stmt);
                mysqli_commit($conn);
                
                // Try to send welcome email
                $email_sent = sendWelcomeEmail($email, $full_name, $generated_password);
                
                $message = 'Instructor added successfully!';
                if ($email_sent) {
                    $message .= ' Welcome email sent to instructor.';
                } else {
                    $message .= ' Note: Welcome email could not be sent. Password: ' . $generated_password;
                }
                
                echo json_encode(['success' => true, 'message' => $message]);
                
            } catch (Exception $e) {
                mysqli_rollback($conn);
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit;
            
        case 'update_instructor':
            $instructor_id = $_POST['instructor_id'];
            $user_id = $_POST['user_id'];
            $full_name = $_POST['full_name'];
            $email = $_POST['email'];
            $contact_number = $_POST['contact_number'];
            $license_number = $_POST['license_number'];
            $specializations = $_POST['specializations'];
            $years_experience = $_POST['years_experience'];
            $hourly_rate = $_POST['hourly_rate'];
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            
            mysqli_begin_transaction($conn);
            
            try {
                // Check if email exists for other users
                $check_email = "SELECT id FROM users WHERE email = ? AND id != ?";
                $stmt = mysqli_prepare($conn, $check_email);
                mysqli_stmt_bind_param($stmt, "si", $email, $user_id);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                
                if (mysqli_num_rows($result) > 0) {
                    throw new Exception("Email already exists for another user");
                }
                mysqli_stmt_close($stmt);
                
                // Check if license exists for other instructors
                $check_license = "SELECT id FROM instructors WHERE license_number = ? AND id != ?";
                $stmt = mysqli_prepare($conn, $check_license);
                mysqli_stmt_bind_param($stmt, "si", $license_number, $instructor_id);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                
                if (mysqli_num_rows($result) > 0) {
                    throw new Exception("License number already exists for another instructor");
                }
                mysqli_stmt_close($stmt);
                
                // Update user
                $user_sql = "UPDATE users SET full_name = ?, email = ?, contact_number = ? WHERE id = ?";
                $stmt = mysqli_prepare($conn, $user_sql);
                mysqli_stmt_bind_param($stmt, "sssi", $full_name, $email, $contact_number, $user_id);
                
                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception("Error updating user account");
                }
                mysqli_stmt_close($stmt);
                
                // Update instructor
                $instructor_sql = "UPDATE instructors SET license_number = ?, specializations = ?, years_experience = ?, hourly_rate = ?, is_active = ? WHERE id = ?";
                $stmt = mysqli_prepare($conn, $instructor_sql);
                mysqli_stmt_bind_param($stmt, "siidii", $license_number, $specializations, $years_experience, $hourly_rate, $is_active, $instructor_id);
                
                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception("Error updating instructor profile");
                }
                
                mysqli_stmt_close($stmt);
                mysqli_commit($conn);
                
                echo json_encode(['success' => true, 'message' => 'Instructor updated successfully!']);
                
            } catch (Exception $e) {
                mysqli_rollback($conn);
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit;
            
        case 'delete_instructor':
            $instructor_id = $_POST['instructor_id'];
            
            // Set instructor as inactive instead of deleting
            $sql = "UPDATE instructors SET is_active = 0 WHERE id = ?";
            if ($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "i", $instructor_id);
                
                if (mysqli_stmt_execute($stmt)) {
                    echo json_encode(['success' => true, 'message' => 'Instructor deactivated successfully!']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Error deactivating instructor.']);
                }
                mysqli_stmt_close($stmt);
            }
            exit;
    }
}

// Get all instructors
$instructors_sql = "SELECT i.*, u.full_name, u.email, u.contact_number, u.created_at as user_created,
                           COUNT(a.id) as total_appointments,
                           COUNT(CASE WHEN a.status = 'completed' THEN 1 END) as completed_appointments
                    FROM instructors i 
                    JOIN users u ON i.user_id = u.id 
                    LEFT JOIN appointments a ON i.id = a.instructor_id
                    GROUP BY i.id, u.id
                    ORDER BY u.full_name";

$instructors = [];
if ($result = mysqli_query($conn, $instructors_sql)) {
    while ($row = mysqli_fetch_assoc($result)) {
        $instructors[] = $row;
    }
}

// Generate content
ob_start();
?>

<div class="instructors-container">
    <div class="page-header">
        <div class="header-left">
            <h2>Instructor Management</h2>
            <p>Manage driving instructors and their profiles</p>
        </div>
        <button class="add-btn" onclick="openAddModal()">
            <i class="fas fa-user-plus"></i> Add New Instructor
        </button>
    </div>

    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-chalkboard-teacher"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo count($instructors); ?></h3>
                <p>Total Instructors</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-user-check"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo count(array_filter($instructors, function($i) { return $i['is_active']; })); ?></h3>
                <p>Active Instructors</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-calendar-check"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo array_sum(array_column($instructors, 'total_appointments')); ?></h3>
                <p>Total Appointments</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-star"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo array_sum(array_column($instructors, 'completed_appointments')); ?></h3>
                <p>Completed Sessions</p>
            </div>
        </div>
    </div>

    <!-- Instructors List -->
    <div class="instructors-grid">
        <?php foreach ($instructors as $instructor): ?>
            <div class="instructor-card <?php echo $instructor['is_active'] ? 'active' : 'inactive'; ?>">
                <div class="instructor-header">
                    <div class="instructor-avatar">
                        <?php echo strtoupper(substr($instructor['full_name'], 0, 1)); ?>
                    </div>
                    <div class="instructor-info">
                        <h3><?php echo htmlspecialchars($instructor['full_name']); ?></h3>
                        <p class="instructor-email"><?php echo htmlspecialchars($instructor['email']); ?></p>
                        <p class="instructor-license">License: <?php echo htmlspecialchars($instructor['license_number']); ?></p>
                    </div>
                    <div class="instructor-status">
                        <span class="status-badge <?php echo $instructor['is_active'] ? 'active' : 'inactive'; ?>">
                            <?php echo $instructor['is_active'] ? 'Active' : 'Inactive'; ?>
                        </span>
                    </div>
                </div>
                
                <div class="instructor-details">
                    <div class="detail-row">
                        <span class="detail-label">
                            <i class="fas fa-phone"></i> Contact:
                        </span>
                        <span><?php echo htmlspecialchars($instructor['contact_number'] ?: 'Not provided'); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">
                            <i class="fas fa-calendar"></i> Experience:
                        </span>
                        <span><?php echo $instructor['years_experience']; ?> years</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">
                            <i class="fas fa-peso-sign"></i> Rate:
                        </span>
                        <span>₱<?php echo number_format($instructor['hourly_rate'], 2); ?>/hour</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">
                            <i class="fas fa-star"></i> Specializations:
                        </span>
                        <span><?php echo htmlspecialchars($instructor['specializations'] ?: 'General'); ?></span>
                    </div>
                </div>
                
                <div class="instructor-stats">
                    <div class="stat-item">
                        <span class="stat-number"><?php echo $instructor['total_appointments']; ?></span>
                        <span class="stat-label">Total Appointments</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo $instructor['completed_appointments']; ?></span>
                        <span class="stat-label">Completed</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number">
                            <?php echo $instructor['total_appointments'] > 0 ? round(($instructor['completed_appointments'] / $instructor['total_appointments']) * 100) : 0; ?>%
                        </span>
                        <span class="stat-label">Success Rate</span>
                    </div>
                </div>
                
                <div class="instructor-actions">
                    <button class="action-btn edit" onclick="editInstructor(<?php echo htmlspecialchars(json_encode($instructor)); ?>)">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <button class="action-btn toggle" onclick="toggleInstructorStatus(<?php echo $instructor['id']; ?>, <?php echo $instructor['is_active'] ? 'false' : 'true'; ?>)">
                        <i class="fas fa-<?php echo $instructor['is_active'] ? 'pause' : 'play'; ?>"></i> 
                        <?php echo $instructor['is_active'] ? 'Deactivate' : 'Activate'; ?>
                    </button>
                    <button class="action-btn view" onclick="viewInstructorDetails(<?php echo $instructor['id']; ?>)">
                        <i class="fas fa-eye"></i> View
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Add/Edit Instructor Modal -->
<div id="instructor-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modal-title">Add New Instructor</h3>
            <span class="close-btn" onclick="closeInstructorModal()">&times;</span>
        </div>
        <form id="instructor-form">
            <input type="hidden" id="instructor_id" name="instructor_id">
            <input type="hidden" id="user_id" name="user_id">
            
            <div class="form-row">
                <div class="form-group">
                    <label for="full_name">Full Name *</label>
                    <input type="text" id="full_name" name="full_name" required>
                </div>
                <div class="form-group">
                    <label for="email">Email Address *</label>
                    <input type="email" id="email" name="email" required>
                    <small style="color: #8b8d93; font-size: 12px;">Login credentials will be sent to this email</small>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="contact_number">Contact Number</label>
                    <input type="tel" id="contact_number" name="contact_number">
                </div>
                <div class="form-group">
                    <label for="license_number">Driver's License Number *</label>
                    <input type="text" id="license_number" name="license_number" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="years_experience">Years of Experience *</label>
                    <input type="number" id="years_experience" name="years_experience" min="0" required>
                </div>
                <div class="form-group">
                    <label for="hourly_rate">Hourly Rate (₱) *</label>
                    <input type="number" id="hourly_rate" name="hourly_rate" step="0.01" min="0" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="specializations">Specializations</label>
                <input type="text" id="specializations" name="specializations" placeholder="e.g., Highway Driving, Parking, Manual Transmission">
                <small style="color: #8b8d93; font-size: 12px;">Optional: Areas of expertise or special skills</small>
            </div>
            
            <div class="form-group" id="active-checkbox" style="display: none;">
                <label class="checkbox-label">
                    <input type="checkbox" id="is_active" name="is_active" checked>
                    <span class="checkmark"></span>
                    Active Instructor
                </label>
            </div>
            
            <div class="form-actions">
                <button type="button" onclick="closeInstructorModal()" class="cancel-btn">Cancel</button>
                <button type="submit" id="submit-btn" class="submit-btn">Add Instructor</button>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();

// Add additional styles (same as before)
$extra_styles = <<<EOT
<style>
.instructors-container {
    max-width: 1400px;
    margin: 0 auto;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid #3a3f48;
}

.header-left h2 {
    margin: 0 0 5px 0;
    color: #ffcc00;
}

.header-left p {
    margin: 0;
    color: #8b8d93;
}

.add-btn {
    background: #ffcc00;
    color: #1a1d24;
    border: none;
    padding: 12px 20px;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    gap: 8px;
}

.add-btn:hover {
    background: #e6b800;
    transform: translateY(-2px);
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: #282c34;
    border: 1px solid #3a3f48;
    border-radius: 8px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    background: rgba(255, 204, 0, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #ffcc00;
    font-size: 20px;
}

.stat-info h3 {
    margin: 0 0 5px 0;
    font-size: 24px;
    font-weight: 600;
}

.stat-info p {
    margin: 0;
    color: #8b8d93;
    font-size: 14px;
}

.instructors-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 20px;
}

.instructor-card {
    background: #282c34;
    border: 1px solid #3a3f48;
    border-radius: 10px;
    padding: 20px;
    transition: all 0.3s;
}

.instructor-card:hover {
    border-color: #ffcc00;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.3);
}

.instructor-card.inactive {
    opacity: 0.7;
    border-color: #666;
}

.instructor-header {
    display: flex;
    align-items: center;
    margin-bottom: 20px;
}

.instructor-avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: #ffcc00;
    color: #1a1d24;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    font-weight: 600;
    margin-right: 15px;
}

.instructor-info {
    flex: 1;
}

.instructor-info h3 {
    margin: 0 0 5px 0;
    font-size: 18px;
}

.instructor-email {
    margin: 0 0 3px 0;
    color: #8b8d93;
    font-size: 14px;
}

.instructor-license {
    margin: 0;
    color: #8b8d93;
    font-size: 13px;
}

.instructor-status {
    margin-left: 10px;
}

.status-badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.status-badge.active {
    background: rgba(76, 175, 80, 0.2);
    color: #4CAF50;
}

.status-badge.inactive {
    background: rgba(158, 158, 158, 0.2);
    color: #9e9e9e;
}

.instructor-details {
    margin-bottom: 20px;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
    font-size: 14px;
}

.detail-label {
    color: #8b8d93;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 6px;
}

.detail-label i {
    width: 14px;
    color: #ffcc00;
}

.instructor-stats {
    display: flex;
    justify-content: space-between;
    margin-bottom: 20px;
    padding: 15px;
    background: #1e2129;
    border-radius: 6px;
}

.stat-item {
    text-align: center;
}

.stat-number {
    display: block;
    font-size: 18px;
    font-weight: 600;
    color: #ffcc00;
}

.stat-label {
    font-size: 11px;
    color: #8b8d93;
    text-transform: uppercase;
}

.instructor-actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.action-btn {
    padding: 8px 12px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 12px;
    font-weight: 500;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    gap: 4px;
    flex: 1;
    justify-content: center;
}

.action-btn.edit {
    background: #2196F3;
    color: white;
}

.action-btn.toggle {
    background: #ff9800;
    color: white;
}

.action-btn.view {
    background: #4CAF50;
    color: white;
}

.action-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.3);
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
    margin: 2% auto;
    padding: 0;
    border: 1px solid #3a3f48;
    border-radius: 10px;
    width: 90%;
    max-width: 600px;
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

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    margin-bottom: 15px;
}

.form-group {
    margin-bottom: 15px;
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

.form-group small {
    display: block;
    margin-top: 5px;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    color: white;
}

.checkbox-label input[type="checkbox"] {
    width: auto;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #3a3f48;
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
    .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .instructors-grid {
        grid-template-columns: 1fr;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .instructor-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .instructor-stats {
        flex-direction: column;
        gap: 10px;
    }
    
    .instructor-actions {
        flex-direction: column;
    }
}
</style>
EOT;

// Add JavaScript (updated to remove password field)
$extra_scripts = <<<EOT
<script>
let isEditing = false;

function openAddModal() {
    document.getElementById('modal-title').textContent = 'Add New Instructor';
    document.getElementById('submit-btn').textContent = 'Add Instructor';
    document.getElementById('instructor-form').reset();
    document.getElementById('instructor_id').value = '';
    document.getElementById('user_id').value = '';
    document.getElementById('active-checkbox').style.display = 'none';
    isEditing = false;
    
    document.getElementById('instructor-modal').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function editInstructor(instructor) {
    document.getElementById('modal-title').textContent = 'Edit Instructor';
    document.getElementById('submit-btn').textContent = 'Update Instructor';
    
    // Fill form with instructor data
    document.getElementById('instructor_id').value = instructor.id;
    document.getElementById('user_id').value = instructor.user_id;
    document.getElementById('full_name').value = instructor.full_name;
    document.getElementById('email').value = instructor.email;
    document.getElementById('contact_number').value = instructor.contact_number || '';
    document.getElementById('license_number').value = instructor.license_number;
    document.getElementById('specializations').value = instructor.specializations || '';
    document.getElementById('years_experience').value = instructor.years_experience;
    document.getElementById('hourly_rate').value = instructor.hourly_rate;
    document.getElementById('is_active').checked = instructor.is_active == 1;
    
    document.getElementById('active-checkbox').style.display = 'block';
    isEditing = true;
    
    document.getElementById('instructor-modal').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closeInstructorModal() {
    document.getElementById('instructor-modal').style.display = 'none';
    document.body.style.overflow = 'auto';
    document.getElementById('instructor-form').reset();
    isEditing = false;
}

function toggleInstructorStatus(instructorId, newStatus) {
    const action = newStatus === 'true' ? 'activate' : 'deactivate';
    if (confirm(`Are you sure you want to \${action} this instructor?`)) {
        fetch('', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=delete_instructor&instructor_id=\${instructorId}`
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

function viewInstructorDetails(instructorId) {
    // This would open a detailed view modal
    alert('View instructor details for ID: ' + instructorId);
}

// Form submission
document.getElementById('instructor-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const action = isEditing ? 'update_instructor' : 'add_instructor';
    formData.append('action', action);
    
    // Show loading state
    const submitBtn = document.getElementById('submit-btn');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Processing...';
    submitBtn.disabled = true;
    
    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            closeInstructorModal();
            location.reload();
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    })
    .finally(() => {
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    });
});

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('instructor-modal');
    if (event.target === modal) {
        closeInstructorModal();
    }
}
</script>
EOT;

// Include the main layout template
include "../layouts/main_layout.php";
?>