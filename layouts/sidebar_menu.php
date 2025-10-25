<?php
// Get user type from session
$user_type = isset($_SESSION['user_type']) ? $_SESSION['user_type'] : '';

// Determine the correct base path based on current location
$current_script = $_SERVER['PHP_SELF'];
$is_in_subfolder = (strpos($current_script, '/certificates/') !== false || 
                    strpos($current_script, '/e-learning-module/') !== false);

// Set base paths
if ($user_type == 'admin') {
    $base_path = $is_in_subfolder ? '../../admin/' : '../admin/';
} else {
    $base_path = $is_in_subfolder ? '../../user/' : '../user/';
}

// Define menu items based on user type
if ($user_type == 'admin') {
    // Admin menu
    ?>
    <a href="<?php echo $base_path; ?>dashboard.php" <?php if($current_page == "dashboard.php") echo 'class="active"'; ?>>
        <i class="fas fa-home"></i>
        <span>Dashboard</span>
    </a>
    <a href="<?php echo $base_path; ?>e-learning.php" <?php if($current_page == "e-learning.php") echo 'class="active"'; ?>>
        <i class="fas fa-book-open"></i>
        <span>E-Learning</span>
    </a>
    <a href="<?php echo $base_path; ?>appointments.php" <?php if($current_page == "appointments.php") echo 'class="active"'; ?>>
        <i class="far fa-calendar-alt"></i>
        <span>Appointments</span>
    </a>
    <a href="<?php echo $base_path; ?>instructors.php" <?php if($current_page == "instructors.php") echo 'class="active"'; ?>>
        <i class="fas fa-chalkboard-teacher"></i>
        <span>Instructors</span>
    </a>
    <a href="<?php echo $base_path; ?>vehicles.php" <?php if($current_page == "vehicles.php") echo 'class="active"'; ?>>
        <i class="fas fa-car"></i>
        <span>Vehicles</span>
    </a>
    <a href="<?php echo $base_path; ?>students.php" <?php if($current_page == "students.php") echo 'class="active"'; ?>>
        <i class="fas fa-user-graduate"></i>
        <span>Students</span>
    </a>
    <!-- <a href="<?php echo $base_path; ?>payments.php" <?php if($current_page == "payments.php") echo 'class="active"'; ?>>
        <i class="fas fa-credit-card"></i>
        <span>Payments</span>
    </a> -->
       <a href="<?php echo $base_path; ?>simulation_result.php" <?php if($current_page == "simulation_result.php") echo 'class="active"'; ?>>
     <i class="fas fa-chart-bar"></i>
     <span>Simulation Result</span>
    </a>
    
    <a href="<?php echo $base_path; ?>certificates/index.php" <?php if($current_page == "index.php" && strpos($_SERVER['PHP_SELF'], 'certificates') !== false) echo 'class="active"'; ?>>
     <i class="fas fa-certificate"></i>
     <span>Certificates</span>
    </a>
    <?php
} else {
    // User/Student menu 
    ?>
    <a href="<?php echo $base_path; ?>dashboard.php" <?php if($current_page == "dashboard.php") echo 'class="active"'; ?>>
        <i class="fas fa-home"></i>
        <span>Dashboard</span>
    </a>
    <a href="<?php echo $base_path; ?>e-learning.php" <?php if($current_page == "e-learning.php") echo 'class="active"'; ?>>
        <i class="fas fa-book-open"></i>
        <span>E-Learning</span>
    </a>
    <a href="<?php echo $base_path; ?>appointments.php" <?php if($current_page == "appointments.php") echo 'class="active"'; ?>>
        <i class="far fa-calendar-alt"></i>
        <span>Appointments</span>
    </a>

    <a href="<?php echo $base_path; ?>simulation.php" <?php if($current_page == "simulation.php") echo 'class="active"'; ?>>
        <i class="fas fa-car"></i>
        <span>Simulation</span>
    </a>

    <a href="<?php echo $base_path; ?>simulation_result.php" <?php if($current_page == "simulation_result.php") echo 'class="active"'; ?>>
     <i class="fas fa-chart-bar"></i>
     <span>Simulation Result</span>
    </a>
    <?php
}
?>