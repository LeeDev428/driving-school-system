<?php
session_start();

// Check if user is logged in and is admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["user_type"] !== "admin") {
    header("location: ../login.php");
    exit;
}

// Include database connection
require_once "../config.php";

// Initialize variables
$page_title = "Generate Certificates";
$header_title = "Certificate Management";
$notification_count = 2;

// Get all students with their completion status
$students_query = "SELECT 
    u.id,
    u.full_name,
    u.email,
    u.contact_number,
    
    -- TDC: Check Assessment + Quiz completion
    (SELECT COUNT(*) FROM user_assessment_sessions 
     WHERE user_id = u.id AND status = 'completed' AND passed = 1) as assessment_passed,
    (SELECT MAX(time_completed) FROM user_assessment_sessions 
     WHERE user_id = u.id AND status = 'completed' AND passed = 1) as assessment_date,
     
    (SELECT COUNT(*) FROM user_quiz_sessions 
     WHERE user_id = u.id AND status = 'completed' AND passed = 1) as quiz_passed,
    (SELECT MAX(time_completed) FROM user_quiz_sessions 
     WHERE user_id = u.id AND status = 'completed' AND passed = 1) as quiz_date,
    
    -- PDC: Check Simulation completion
    (SELECT COUNT(*) FROM simulation_results 
     WHERE user_id = u.id AND status = 'completed' AND score_percentage >= 60) as simulation_passed,
    (SELECT MAX(created_at) FROM simulation_results 
     WHERE user_id = u.id AND status = 'completed' AND score_percentage >= 60) as simulation_date
     
FROM users u
WHERE u.user_type = 'student'
ORDER BY u.full_name ASC";

$students = [];
if ($result = mysqli_query($conn, $students_query)) {
    while ($row = mysqli_fetch_assoc($result)) {
        $row['tdc_eligible'] = ($row['assessment_passed'] > 0 && $row['quiz_passed'] > 0);
        $row['pdc_eligible'] = ($row['simulation_passed'] > 0);
        
        // Determine latest TDC completion date
        if ($row['tdc_eligible']) {
            $dates = array_filter([$row['assessment_date'], $row['quiz_date']]);
            $row['tdc_completion_date'] = $dates ? max($dates) : null;
        } else {
            $row['tdc_completion_date'] = null;
        }
        
        // PDC completion date
        $row['pdc_completion_date'] = $row['pdc_eligible'] ? $row['simulation_date'] : null;
        
        $students[] = $row;
    }
}

ob_start();
?>

<div class="certificates-container">
    <div class="certificates-header">
        <h2>📜 Certificate Management</h2>
        <p>Generate and download certificates for students who completed TDC or PDC courses</p>
    </div>

    <div class="certificates-table-container">
        <table class="certificates-table">
            <thead>
                <tr>
                    <th>Student Name</th>
                    <th>Email</th>
                    <th>Contact</th>
                    <th>TDC Status</th>
                    <th>PDC Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($students)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 40px; color: #8b8d93;">
                            <i class="fas fa-users" style="font-size: 48px; opacity: 0.5; margin-bottom: 10px; display: block;"></i>
                            No students found
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($students as $student): ?>
                        <tr>
                            <td>
                                <div class="student-info">
                                    <strong><?php echo htmlspecialchars($student['full_name']); ?></strong>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($student['email']); ?></td>
                            <td><?php echo htmlspecialchars($student['contact_number'] ?? 'N/A'); ?></td>
                            <td>
                                <?php if ($student['tdc_eligible']): ?>
                                    <span class="status-badge completed">
                                        <i class="fas fa-check-circle"></i> Completed
                                    </span>
                                    <br>
                                    <small style="color: #8b8d93;">
                                        <?php echo date('M j, Y', strtotime($student['tdc_completion_date'])); ?>
                                    </small>
                                <?php else: ?>
                                    <span class="status-badge pending">
                                        <i class="fas fa-clock"></i> Not Completed
                                    </span>
                                    <br>
                                    <small style="color: #8b8d93;">
                                        <?php if ($student['assessment_passed'] == 0): ?>
                                            Assessment pending
                                        <?php elseif ($student['quiz_passed'] == 0): ?>
                                            Quiz pending
                                        <?php endif; ?>
                                    </small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($student['pdc_eligible']): ?>
                                    <span class="status-badge completed">
                                        <i class="fas fa-check-circle"></i> Completed
                                    </span>
                                    <br>
                                    <small style="color: #8b8d93;">
                                        <?php echo date('M j, Y', strtotime($student['pdc_completion_date'])); ?>
                                    </small>
                                <?php else: ?>
                                    <span class="status-badge pending">
                                        <i class="fas fa-clock"></i> Not Completed
                                    </span>
                                    <br>
                                    <small style="color: #8b8d93;">Simulation pending</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <?php if ($student['tdc_eligible']): ?>
                                        <button class="cert-btn tdc-btn" onclick="generateCertificate(<?php echo $student['id']; ?>, 'TDC', '<?php echo htmlspecialchars($student['full_name']); ?>')">
                                            <i class="fas fa-file-download"></i> TDC
                                        </button>
                                    <?php else: ?>
                                        <button class="cert-btn disabled" disabled title="Student must complete Assessment and Quiz">
                                            <i class="fas fa-lock"></i> TDC
                                        </button>
                                    <?php endif; ?>
                                    
                                    <?php if ($student['pdc_eligible']): ?>
                                        <button class="cert-btn pdc-btn" onclick="generateCertificate(<?php echo $student['id']; ?>, 'PDC', '<?php echo htmlspecialchars($student['full_name']); ?>')">
                                            <i class="fas fa-file-download"></i> PDC
                                        </button>
                                    <?php else: ?>
                                        <button class="cert-btn disabled" disabled title="Student must complete Simulation with 60%+">
                                            <i class="fas fa-lock"></i> PDC
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$content = ob_get_clean();

$extra_styles = <<<EOT
<style>
.certificates-container {
    max-width: 1400px;
    margin: 0 auto;
}

.certificates-header {
    margin-bottom: 30px;
}

.certificates-header h2 {
    color: #ffcc00;
    font-size: 28px;
    margin-bottom: 10px;
}

.certificates-header p {
    color: #8b8d93;
    font-size: 14px;
}

.certificates-table-container {
    background: #282c34;
    border-radius: 10px;
    overflow: hidden;
    border: 1px solid #3a3f48;
}

.certificates-table {
    width: 100%;
    border-collapse: collapse;
}

.certificates-table thead {
    background: #1e2129;
}

.certificates-table th {
    padding: 15px;
    text-align: left;
    color: #8b8d93;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 12px;
    border-bottom: 2px solid #3a3f48;
}

.certificates-table tbody tr {
    border-bottom: 1px solid #3a3f48;
    transition: all 0.3s;
}

.certificates-table tbody tr:hover {
    background: #3a3f48;
}

.certificates-table td {
    padding: 15px;
    color: #fff;
    font-size: 14px;
    vertical-align: middle;
}

.student-info strong {
    color: #ffcc00;
    font-size: 15px;
}

.status-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 15px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.status-badge.completed {
    background: rgba(76, 175, 80, 0.2);
    color: #4CAF50;
}

.status-badge.pending {
    background: rgba(255, 152, 0, 0.2);
    color: #ff9800;
}

.status-badge i {
    margin-right: 4px;
}

.action-buttons {
    display: flex;
    gap: 8px;
}

.cert-btn {
    padding: 8px 16px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 12px;
    font-weight: 600;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    gap: 6px;
}

.cert-btn.tdc-btn {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.cert-btn.pdc-btn {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
}

.cert-btn:hover:not(.disabled) {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.3);
}

.cert-btn.disabled {
    background: #3a3f48;
    color: #8b8d93;
    cursor: not-allowed;
    opacity: 0.5;
}

@media (max-width: 768px) {
    .certificates-table {
        font-size: 12px;
    }
    
    .certificates-table th,
    .certificates-table td {
        padding: 10px;
    }
    
    .action-buttons {
        flex-direction: column;
    }
}
</style>
EOT;

$extra_scripts = <<<EOT
<script>
function generateCertificate(studentId, type, studentName) {
    if (confirm('Generate ' + type + ' certificate for ' + studentName + '?')) {
        // For now, just download a certificate with student name
        // You can enhance this to save to database or send email
        
        const certType = type.toLowerCase();
        const img = new Image();
        img.crossOrigin = 'anonymous';
        img.src = '../assets/images/' + certType + '-cert.png';
        
        img.onload = function() {
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            
            canvas.width = img.width || 1200;
            canvas.height = img.height || 800;
            
            // Draw certificate background (if image exists)
            if (img.complete && img.width > 0) {
                ctx.drawImage(img, 0, 0);
            } else {
                // Fallback: Create simple certificate
                ctx.fillStyle = '#ffffff';
                ctx.fillRect(0, 0, canvas.width, canvas.height);
                
                // Border
                ctx.strokeStyle = '#ffcc00';
                ctx.lineWidth = 10;
                ctx.strokeRect(20, 20, canvas.width - 40, canvas.height - 40);
                
                // Title
                ctx.fillStyle = '#2c3e50';
                ctx.font = 'bold 48px Arial';
                ctx.textAlign = 'center';
                ctx.fillText('CERTIFICATE OF COMPLETION', canvas.width / 2, 150);
                
                // Type
                ctx.font = 'bold 36px Arial';
                ctx.fillStyle = '#ffcc00';
                ctx.fillText(type + ' COURSE', canvas.width / 2, 250);
            }
            
            // Add student name
            ctx.font = 'bold 60px Arial';
            ctx.fillStyle = '#2c3e50';
            ctx.textAlign = 'center';
            ctx.fillText(studentName, canvas.width / 2, canvas.height / 2);
            
            // Download
            const link = document.createElement('a');
            link.download = type + '_Certificate_' + studentName.replace(/\s+/g, '_') + '.png';
            link.href = canvas.toDataURL();
            link.click();
            
            alert('Certificate generated successfully!');
        };
        
        img.onerror = function() {
            alert('Error loading certificate template. Please check if certificate images exist in assets/images/');
        };
    }
}

document.addEventListener('DOMContentLoaded', function() {
    console.log('Certificate management page loaded');
});
</script>
EOT;

// Include the main layout template
include "../layouts/main_layout.php";
?>
