<?php
/**
 * Access Control Helper
 * Checks if user has completed the 20% down payment and received admin approval
 * Used to lock Dashboard and E-Learning pages until requirements are met
 */

/**
 * Check if user has access to dashboard and e-learning
 * Requirements:
 * 1. User must have at least one appointment
 * 2. Appointment must have 20% down payment (payment_status = 'paid')
 * 3. Appointment must be confirmed by admin (status = 'confirmed')
 * 
 * @param int $user_id The user's ID
 * @param mysqli $conn Database connection
 * @return array ['has_access' => bool, 'message' => string, 'details' => array]
 */
function checkUserAccess($user_id, $conn) {
    $result = [
        'has_access' => false,
        'message' => '',
        'details' => [
            'has_appointment' => false,
            'has_payment' => false,
            'is_confirmed' => false,
            'appointment_count' => 0,
            'pending_payment' => 0,
            'awaiting_approval' => 0
        ]
    ];
    
    // Check if user has any appointments
    $count_sql = "SELECT COUNT(*) as total FROM appointments WHERE student_id = ?";
    if ($stmt = mysqli_prepare($conn, $count_sql)) {
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $count_result = mysqli_stmt_get_result($stmt);
        $count_row = mysqli_fetch_assoc($count_result);
        $result['details']['appointment_count'] = $count_row['total'];
        mysqli_stmt_close($stmt);
        
        if ($count_row['total'] == 0) {
            $result['message'] = 'You need to schedule an appointment first.';
            return $result;
        }
        
        $result['details']['has_appointment'] = true;
    }
    
    // Check for appointments with payment but not confirmed
    $pending_sql = "SELECT COUNT(*) as pending 
                    FROM appointments 
                    WHERE student_id = ? 
                    AND payment_status = 'paid' 
                    AND status = 'pending'";
    if ($stmt = mysqli_prepare($conn, $pending_sql)) {
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $pending_result = mysqli_stmt_get_result($stmt);
        $pending_row = mysqli_fetch_assoc($pending_result);
        $result['details']['awaiting_approval'] = $pending_row['pending'];
        mysqli_stmt_close($stmt);
    }
    
    // Check for appointments without payment
    $unpaid_sql = "SELECT COUNT(*) as unpaid 
                   FROM appointments 
                   WHERE student_id = ? 
                   AND payment_status = 'unpaid'";
    if ($stmt = mysqli_prepare($conn, $unpaid_sql)) {
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $unpaid_result = mysqli_stmt_get_result($stmt);
        $unpaid_row = mysqli_fetch_assoc($unpaid_result);
        $result['details']['pending_payment'] = $unpaid_row['unpaid'];
        mysqli_stmt_close($stmt);
    }
    
    // Check if user has at least one appointment with payment submitted
    // UPDATED: Grant access if payment info is submitted (payment_amount > 0 and payment_method provided)
    // No need to wait for admin confirmation anymore
    $access_sql = "SELECT id, appointment_date, payment_amount, payment_method, payment_status, status 
                   FROM appointments 
                   WHERE student_id = ? 
                   AND payment_amount > 0 
                   AND payment_method IS NOT NULL
                   LIMIT 1";
    
    if ($stmt = mysqli_prepare($conn, $access_sql)) {
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $access_result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($access_result) > 0) {
            // User has submitted payment info - GRANT ACCESS IMMEDIATELY
            $result['has_access'] = true;
            $result['message'] = 'Access granted';
            $result['details']['has_payment'] = true;
            $result['details']['is_confirmed'] = true;
        } else {
            // No appointment with payment info found
            $result['message'] = 'Please schedule an appointment and complete the 20% down payment.';
        }
        
        mysqli_stmt_close($stmt);
    }
    
    return $result;
}

/**
 * Redirect user to appointments page with appropriate message
 * 
 * @param string $message The message to display
 */
function redirectToAppointments($message) {
    $_SESSION['access_denied'] = true;
    $_SESSION['access_message'] = $message;
    header("Location: appointments.php");
    exit;
}

/**
 * Display access denied message on appointments page
 * Call this function at the top of appointments.php
 */
function displayAccessMessage() {
    if (isset($_SESSION['access_denied']) && $_SESSION['access_denied']) {
        $message = isset($_SESSION['access_message']) ? $_SESSION['access_message'] : 'Access denied. Please complete the requirements.';
        
        echo '<div class="access-notice-banner">';
        echo '<div class="access-notice-content">';
        echo '<i class="fas fa-lock"></i>';
        echo '<div class="access-notice-text">';
        echo '<h4>Access Restricted</h4>';
        echo '<p>' . htmlspecialchars($message) . '</p>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        
        // Clear the session variables
        unset($_SESSION['access_denied']);
        unset($_SESSION['access_message']);
    }
}

/**
 * Get user access status for display
 * 
 * @param int $user_id The user's ID
 * @param mysqli $conn Database connection
 * @return string HTML formatted status message
 */
function getAccessStatusHTML($user_id, $conn) {
    $access = checkUserAccess($user_id, $conn);
    
    if ($access['has_access']) {
        return '<div class="access-status-granted">
                    <i class="fas fa-check-circle"></i>
                    <span>Full Access Granted</span>
                </div>';
    }
    
    $html = '<div class="access-status-locked">';
    $html .= '<i class="fas fa-lock"></i>';
    $html .= '<div class="access-status-info">';
    $html .= '<h4>Dashboard & E-Learning Locked</h4>';
    $html .= '<p>' . htmlspecialchars($access['message']) . '</p>';
    
    // Show checklist (updated - payment submission grants immediate access)
    $html .= '<div class="access-checklist">';
    
    $html .= '<div class="checklist-item ' . ($access['details']['has_appointment'] ? 'completed' : 'pending') . '">';
    $html .= '<i class="fas ' . ($access['details']['has_appointment'] ? 'fa-check-circle' : 'fa-circle') . '"></i>';
    $html .= '<span>Step 1: Schedule Appointment (' . $access['details']['appointment_count'] . ')</span>';
    $html .= '</div>';
    
    $html .= '<div class="checklist-item ' . ($access['details']['has_payment'] ? 'completed' : 'pending') . '">';
    $html .= '<i class="fas ' . ($access['details']['has_payment'] ? 'fa-check-circle' : 'fa-circle') . '"></i>';
    $html .= '<span>Step 2: Submit 20% Down Payment Info</span>';
    if ($access['details']['has_payment']) {
        $html .= '<small style="color: #4caf50; display: block; margin-left: 32px;">✓ Payment submitted - Access granted!</small>';
    }
    $html .= '</div>';
    
    $html .= '</div>'; // access-checklist
    
    // Add note about admin verification
    if ($access['details']['has_appointment'] && !$access['details']['has_payment']) {
        $html .= '<p style="margin-top: 15px; padding: 10px; background: rgba(255, 204, 0, 0.1); border-radius: 5px; font-size: 13px;">';
        $html .= '<i class="fas fa-info-circle" style="color: #ffcc00;"></i> ';
        $html .= '<strong>Note:</strong> Once you submit your payment information, you\'ll get instant access to Dashboard and E-Learning! Admin will verify your payment later.';
        $html .= '</p>';
    }
    
    $html .= '</div>'; // access-checklist
    
    $html .= '</div>'; // access-status-info
    $html .= '</div>'; // access-status-locked
    
    return $html;
}
?>
