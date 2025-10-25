<?php
session_start();

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../../login.php");
    exit;
}

// Include database connection
require_once "../../config.php";

// Initialize variables
$page_title = "My Certificates";
$header_title = "My Certificates";
$notification_count = 2;
$user_id = $_SESSION["id"];
$username = $_SESSION["username"];

// Get user's full name
$user_query = "SELECT full_name FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $user_query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$user_result = mysqli_stmt_get_result($stmt);
$user_data = mysqli_fetch_assoc($user_result);
$full_name = $user_data['full_name'] ?? $username;
mysqli_stmt_close($stmt);

// Check TDC completion (Assessment PASSED + Quiz PASSED)
$tdc_completed = false;
$tdc_completion_date = null;

// Check if assessment passed (70% or higher)
$assessment_query = "SELECT * FROM user_assessment_sessions 
                     WHERE user_id = ? AND status = 'completed' AND passed = 1 
                     ORDER BY time_completed DESC LIMIT 1";
$stmt = mysqli_prepare($conn, $assessment_query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$assessment_result = mysqli_stmt_get_result($stmt);
$assessment_passed = mysqli_num_rows($assessment_result) > 0;
$assessment_data = mysqli_fetch_assoc($assessment_result);
mysqli_stmt_close($stmt);

// Check if quiz passed (70% or higher) - using quiz_sessions table
$quiz_query = "SELECT * FROM quiz_sessions 
               WHERE user_id = ? AND session_status = 'completed' AND completion_percentage >= 70 
               ORDER BY completed_at DESC LIMIT 1";
$stmt = mysqli_prepare($conn, $quiz_query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$quiz_result = mysqli_stmt_get_result($stmt);
$quiz_passed = mysqli_num_rows($quiz_result) > 0;
$quiz_data = mysqli_fetch_assoc($quiz_result);
mysqli_stmt_close($stmt);

// TDC is completed if both assessment and quiz are passed
if ($assessment_passed && $quiz_passed) {
    $tdc_completed = true;
    // Use the latest completion date
    $assessment_date = strtotime($assessment_data['time_completed'] ?? '');
    $quiz_date = strtotime($quiz_data['completed_at'] ?? '');
    $tdc_completion_date = date('F d, Y', max($assessment_date, $quiz_date));
}

// Check PDC completion (Simulation PASSED - 60% or higher)
$pdc_completed = false;
$pdc_completion_date = null;

$simulation_query = "SELECT * FROM simulation_results 
                     WHERE user_id = ? AND status = 'completed' AND score_percentage >= 60 
                     ORDER BY created_at DESC LIMIT 1";
$stmt = mysqli_prepare($conn, $simulation_query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$simulation_result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($simulation_result) > 0) {
    $pdc_completed = true;
    $simulation_data = mysqli_fetch_assoc($simulation_result);
    $pdc_completion_date = date('F d, Y', strtotime($simulation_data['created_at']));
}
mysqli_stmt_close($stmt);

ob_start();
?>

<div class="certificates-container" style="max-width: 1200px; margin: 0 auto; padding: 20px;">
    <div style="text-align: center; margin-bottom: 40px;">
        <h1 style="color: #2c3e50; font-size: 36px; margin-bottom: 10px;">🏆 My Certificates</h1>
        <p style="color: #7f8c8d; font-size: 16px;">Your achievements and completed courses</p>
    </div>

    <?php if (!$tdc_completed && !$pdc_completed): ?>
        <div style="text-align: center; padding: 60px 20px; background: #f8f9fa; border-radius: 15px; margin: 40px 0;">
            <div style="font-size: 80px; margin-bottom: 20px;">📜</div>
            <h2 style="color: #2c3e50; margin-bottom: 15px;">No Certificates Yet</h2>
            <p style="color: #7f8c8d; font-size: 16px; margin-bottom: 25px;">Complete your courses to earn certificates!</p>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; max-width: 600px; margin: 0 auto;">
                <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    <h4 style="color: #3498db; margin-bottom: 10px;">📚 TDC Certificate</h4>
                    <p style="color: #555; font-size: 14px; margin-bottom: 15px;">Complete Assessment + Quiz</p>
                    <div style="display: flex; flex-direction: column; gap: 5px; text-align: left;">
                        <div style="display: flex; align-items: center; gap: 8px; font-size: 13px;">
                            <?php if ($assessment_passed): ?>
                                <span style="color: #27ae60;">✓</span> <span style="color: #27ae60;">Assessment Passed</span>
                            <?php else: ?>
                                <span style="color: #e74c3c;">✗</span> <span style="color: #95a5a6;">Assessment Pending</span>
                            <?php endif; ?>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px; font-size: 13px;">
                            <?php if ($quiz_passed): ?>
                                <span style="color: #27ae60;">✓</span> <span style="color: #27ae60;">Quiz Passed</span>
                            <?php else: ?>
                                <span style="color: #e74c3c;">✗</span> <span style="color: #95a5a6;">Quiz Pending</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    <h4 style="color: #e74c3c; margin-bottom: 10px;">🚗 PDC Certificate</h4>
                    <p style="color: #555; font-size: 14px; margin-bottom: 15px;">Complete Simulation (60%+)</p>
                    <div style="display: flex; flex-direction: column; gap: 5px; text-align: left;">
                        <div style="display: flex; align-items: center; gap: 8px; font-size: 13px;">
                            <?php if ($pdc_completed): ?>
                                <span style="color: #27ae60;">✓</span> <span style="color: #27ae60;">Simulation Passed</span>
                            <?php else: ?>
                                <span style="color: #e74c3c;">✗</span> <span style="color: #95a5a6;">Simulation Pending</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="certificates-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(500px, 1fr)); gap: 40px; margin-top: 40px;">
        
        <!-- TDC Certificate -->
        <?php if ($tdc_completed): ?>
        <div class="certificate-card" style="background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 5px 25px rgba(0,0,0,0.1); transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 10px 35px rgba(0,0,0,0.15)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 5px 25px rgba(0,0,0,0.1)';">
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; text-align: center;">
                <h3 style="color: white; font-size: 24px; margin: 0;">📚 TDC Certificate</h3>
                <p style="color: rgba(255,255,255,0.9); margin: 5px 0 0 0; font-size: 14px;">Theoretical Driving Course</p>
            </div>
            <div style="padding: 30px; position: relative;">
                <div class="certificate-image" style="position: relative; background: #f8f9fa; border-radius: 10px; overflow: hidden; border: 3px solid #e9ecef;">
                    <img src="../../assets/images/tdc-cert.png" alt="TDC Certificate" style="width: 100%; display: block;">
                    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; width: 80%;">
                        <h2 style="color: #2c3e50; font-size: 28px; font-weight: bold; margin: 0; text-shadow: 2px 2px 4px rgba(255,255,255,0.8);"><?php echo htmlspecialchars($full_name); ?></h2>
                    </div>
                </div>
                <div style="margin-top: 20px; text-align: center;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 15px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                        <div>
                            <small style="color: #7f8c8d; display: block; margin-bottom: 5px;">Completion Date</small>
                            <strong style="color: #2c3e50;"><?php echo $tdc_completion_date; ?></strong>
                        </div>
                        <div>
                            <small style="color: #7f8c8d; display: block; margin-bottom: 5px;">Status</small>
                            <span style="background: #27ae60; color: white; padding: 4px 12px; border-radius: 15px; font-size: 12px; font-weight: 600;">✓ COMPLETED</span>
                        </div>
                    </div>
                    <button onclick="downloadCertificate('tdc')" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 12px 30px; border: none; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer; width: 100%; transition: all 0.3s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                        <i class="fas fa-download"></i> Download Certificate
                    </button>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- PDC Certificate -->
        <?php if ($pdc_completed): ?>
        <div class="certificate-card" style="background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 5px 25px rgba(0,0,0,0.1); transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 10px 35px rgba(0,0,0,0.15)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 5px 25px rgba(0,0,0,0.1)';">
            <div style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); padding: 20px; text-align: center;">
                <h3 style="color: white; font-size: 24px; margin: 0;">🚗 PDC Certificate</h3>
                <p style="color: rgba(255,255,255,0.9); margin: 5px 0 0 0; font-size: 14px;">Practical Driving Course</p>
            </div>
            <div style="padding: 30px; position: relative;">
                <div class="certificate-image" style="position: relative; background: #f8f9fa; border-radius: 10px; overflow: hidden; border: 3px solid #e9ecef;">
                    <img src="../../assets/images/pdc-cert.png" alt="PDC Certificate" style="width: 100%; display: block;">
                    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; width: 80%;">
                        <h2 style="color: #2c3e50; font-size: 28px; font-weight: bold; margin: 0; text-shadow: 2px 2px 4px rgba(255,255,255,0.8);"><?php echo htmlspecialchars($full_name); ?></h2>
                    </div>
                </div>
                <div style="margin-top: 20px; text-align: center;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 15px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                        <div>
                            <small style="color: #7f8c8d; display: block; margin-bottom: 5px;">Completion Date</small>
                            <strong style="color: #2c3e50;"><?php echo $pdc_completion_date; ?></strong>
                        </div>
                        <div>
                            <small style="color: #7f8c8d; display: block; margin-bottom: 5px;">Status</small>
                            <span style="background: #27ae60; color: white; padding: 4px 12px; border-radius: 15px; font-size: 12px; font-weight: 600;">✓ COMPLETED</span>
                        </div>
                    </div>
                    <button onclick="downloadCertificate('pdc')" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; padding: 12px 30px; border: none; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer; width: 100%; transition: all 0.3s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                        <i class="fas fa-download"></i> Download Certificate
                    </button>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>

<?php
$content = ob_get_clean();

$extra_styles = <<<EOT
<style>
.certificates-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.certificates-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
    gap: 40px;
    margin-top: 40px;
}

.certificate-card {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 5px 25px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}

.certificate-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 35px rgba(0,0,0,0.15);
}

.certificate-image {
    position: relative;
    background: #f8f9fa;
    border-radius: 10px;
    overflow: hidden;
    border: 3px solid #e9ecef;
}

.certificate-image img {
    width: 100%;
    display: block;
}

@media (max-width: 768px) {
    .certificates-grid {
        grid-template-columns: 1fr;
    }
}
</style>
EOT;

$extra_scripts = <<<EOT
<script>
function downloadCertificate(type) {
    const userName = <?php echo json_encode($full_name); ?>;
    const certType = type.toUpperCase();
    
    // Create canvas to add user name to certificate
    const img = new Image();
    img.crossOrigin = 'anonymous';
    img.src = '../../assets/images/' + type + '-cert.png';
    
    img.onload = function() {
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        
        canvas.width = img.width;
        canvas.height = img.height;
        
        // Draw certificate image
        ctx.drawImage(img, 0, 0);
        
        // Add user name (adjust position as needed)
        ctx.font = 'bold 60px Arial';
        ctx.fillStyle = '#2c3e50';
        ctx.textAlign = 'center';
        ctx.fillText(userName, canvas.width / 2, canvas.height / 2);
        
        // Download
        const link = document.createElement('a');
        link.download = certType + '_Certificate_' + userName.replace(/\s+/g, '_') + '.png';
        link.href = canvas.toDataURL();
        link.click();
    };
}

document.addEventListener('DOMContentLoaded', function() {
    console.log('Certificates page loaded');
});
</script>
EOT;

// Include the main layout template
include "../../layouts/main_layout.php";
?>
