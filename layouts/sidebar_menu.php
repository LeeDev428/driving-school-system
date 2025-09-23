<?php
// Get user type from session
$user_type = isset($_SESSION['user_type']) ? $_SESSION['user_type'] : '';

// Define menu items based on user type
if ($user_type == 'admin') {
    // Admin menu
    ?>
    <a href="../admin/dashboard.php" <?php if($current_page == "dashboard.php") echo 'class="active"'; ?>>
        <i class="fas fa-home"></i>
        <span>Dashboard</span>
    </a>
    <a href="../admin/e-learning.php" <?php if($current_page == "e-learning.php") echo 'class="active"'; ?>>
        <i class="fas fa-book-open"></i>
        <span>E-Learning</span>
    </a>
    <a href="../admin/appointments.php" <?php if($current_page == "appointments.php") echo 'class="active"'; ?>>
        <i class="far fa-calendar-alt"></i>
        <span>Appointments</span>
    </a>
    <a href="../admin/instructors.php" <?php if($current_page == "instructors.php") echo 'class="active"'; ?>>
        <i class="fas fa-chalkboard-teacher"></i>
        <span>Instructors</span>
    </a>
    <a href="../admin/vehicles.php" <?php if($current_page == "vehicles.php") echo 'class="active"'; ?>>
        <i class="fas fa-car"></i>
        <span>Vehicles</span>
    </a>
    <a href="../admin/students.php" <?php if($current_page == "students.php") echo 'class="active"'; ?>>
        <i class="fas fa-user-graduate"></i>
        <span>Students</span>
    </a>
    <!-- <a href="../admin/payments.php" <?php if($current_page == "payments.php") echo 'class="active"'; ?>>
        <i class="fas fa-credit-card"></i>
        <span>Payments</span>
    </a> -->
       <a href="../admin/simulation_result.php" <?php if($current_page == "simulation_result.php") echo 'class="active"'; ?>>
     <i class="fas fa-chart-bar"></i>
     <span>Simulation Result</span>
    </a>
    <?php
} else {
    // User/Student menu 
    ?>
    <a href="../user/dashboard.php" <?php if($current_page == "dashboard.php") echo 'class="active"'; ?>>
        <i class="fas fa-home"></i>
        <span>Dashboard</span>
    </a>
    <a href="../user/e-learning.php" <?php if($current_page == "e-learning.php") echo 'class="active"'; ?>>
        <i class="fas fa-book-open"></i>
        <span>E-Learning</span>
    </a>
    <a href="../user/appointments.php" <?php if($current_page == "appointments.php") echo 'class="active"'; ?>>
        <i class="far fa-calendar-alt"></i>
        <span>Appointments</span>
    </a>

    <a href="../user/simulation.php" <?php if($current_page == "simulation.php") echo 'class="active"'; ?>>
        <i class="fas fa-car"></i>
        <span>Simulation</span>
    </a>

    <a href="../user/simulation_result.php" <?php if($current_page == "simulation_result.php") echo 'class="active"'; ?>>
     <i class="fas fa-chart-bar"></i>
     <span>Simulation Result</span>
    </a>
    <?php
}
?>