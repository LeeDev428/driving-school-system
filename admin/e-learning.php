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
$page_title = "E-Learning Management";
$header_title = "E-Learning Progress Tracking";
$notification_count = 5;

// Get users with their e-learning progress
$users_sql = "SELECT u.id, u.full_name, u.email, u.user_type,
                     COUNT(DISTINCT mp.module_id) as modules_enrolled,
                     COUNT(DISTINCT CASE WHEN mp.completed = 1 THEN mp.module_id END) as modules_completed,
                     ROUND(AVG(CASE WHEN mp.progress_percentage > 0 THEN mp.progress_percentage END), 1) as avg_progress,
                     (SELECT COUNT(*) FROM elearning_modules WHERE status = 'Active') as total_modules,
                     MAX(mp.started_at) as last_activity
              FROM users u
              LEFT JOIN user_module_progress mp ON u.id = mp.user_id
              WHERE u.user_type IN ('student', 'instructor')
              GROUP BY u.id, u.full_name, u.email, u.user_type
              ORDER BY u.full_name";

$users = [];
if ($result = mysqli_query($conn, $users_sql)) {
    while ($row = mysqli_fetch_assoc($result)) {
        // Calculate overall completion percentage
        $completion_rate = $row['total_modules'] > 0 ? 
            round(($row['modules_completed'] / $row['total_modules']) * 100, 1) : 0;
        
        $row['completion_rate'] = $completion_rate;
        
        // Determine status based on progress
        if ($row['modules_enrolled'] == 0) {
            $row['status'] = 'Not Started';
            $row['status_class'] = 'not-started';
        } elseif ($completion_rate >= 100) {
            $row['status'] = 'Completed';
            $row['status_class'] = 'completed';
        } elseif ($row['avg_progress'] > 0) {
            $row['status'] = 'In Progress';
            $row['status_class'] = 'in-progress';
        } else {
            $row['status'] = 'Enrolled';
            $row['status_class'] = 'enrolled';
        }
        
        $users[] = $row;
    }
}

// Get overall statistics
$stats_sql = "SELECT 
                (SELECT COUNT(*) FROM users WHERE user_type IN ('student', 'instructor')) as total_users,
                (SELECT COUNT(*) FROM elearning_modules WHERE status = 'Active') as total_modules,
                (SELECT COUNT(DISTINCT user_id) FROM user_module_progress) as active_learners,
                (SELECT COUNT(*) FROM user_module_progress WHERE completed = 1) as total_completions";

$stats = [];
if ($result = mysqli_query($conn, $stats_sql)) {
    $stats = mysqli_fetch_assoc($result);
}

// Get module-wise progress
$module_progress_sql = "SELECT m.title, m.id,
                               COUNT(DISTINCT mp.user_id) as enrolled_users,
                               COUNT(CASE WHEN mp.completed = 1 THEN 1 END) as completed_users,
                               ROUND(AVG(mp.progress_percentage), 1) as avg_progress
                        FROM elearning_modules m
                        LEFT JOIN user_module_progress mp ON m.id = mp.module_id
                        WHERE m.status = 'Active'
                        GROUP BY m.id, m.title
                        ORDER BY enrolled_users DESC";

$module_progress = [];
if ($result = mysqli_query($conn, $module_progress_sql)) {
    while ($row = mysqli_fetch_assoc($result)) {
        $module_progress[] = $row;
    }
}

ob_start();
?>

<div class="elearning-admin-container">
    <!-- Statistics Overview -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $stats['total_users'] ?? 0; ?></h3>
                <p>Total Users</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-book"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $stats['total_modules'] ?? 0; ?></h3>
                <p>Active Modules</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-user-graduate"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $stats['active_learners'] ?? 0; ?></h3>
                <p>Active Learners</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-trophy"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $stats['total_completions'] ?? 0; ?></h3>
                <p>Total Completions</p>
            </div>
        </div>
    </div>

    <!-- Tab Navigation -->
    <div class="admin-tabs">
        <button class="tab-btn active" onclick="switchTab('users')">
            <i class="fas fa-users"></i> User Progress
        </button>
        <button class="tab-btn" onclick="switchTab('modules')">
            <i class="fas fa-chart-bar"></i> Module Analytics
        </button>
    </div>

    <!-- Users Progress Tab -->
    <div id="users-tab" class="tab-content active">
        <div class="section-header">
            <h2>User Learning Progress</h2>
            <div class="header-actions">
                <input type="text" id="searchUsers" placeholder="Search users..." class="search-input">
                <select id="filterStatus" class="filter-select">
                    <option value="">All Status</option>
                    <option value="not-started">Not Started</option>
                    <option value="enrolled">Enrolled</option>
                    <option value="in-progress">In Progress</option>
                    <option value="completed">Completed</option>
                </select>
            </div>
        </div>

        <div class="users-table-container">
            <table class="users-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Role</th>
                        <th>Modules Enrolled</th>
                        <th>Modules Completed</th>
                        <th>Completion Rate</th>
                        <th>Avg Progress</th>
                        <th>Status</th>
                        <th>Last Activity</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr class="user-row" data-status="<?php echo $user['status_class']; ?>">
                            <td>
                                <div class="user-info">
                                    <div class="user-avatar">
                                        <?php 
                                        $name_parts = explode(' ', $user['full_name']);
                                        $initials = '';
                                        if (count($name_parts) >= 2) {
                                            $initials = strtoupper(substr($name_parts[0], 0, 1) . substr($name_parts[1], 0, 1));
                                        } else {
                                            $initials = strtoupper(substr($user['full_name'], 0, 2));
                                        }
                                        echo $initials;
                                        ?>
                                    </div>
                                    <div class="user-details">
                                        <div class="user-name"><?php echo htmlspecialchars($user['full_name']); ?></div>
                                        <div class="user-email"><?php echo htmlspecialchars($user['email']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="role-badge <?php echo $user['user_type']; ?>">
                                    <?php echo ucfirst($user['user_type']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="metric-value"><?php echo $user['modules_enrolled']; ?></span>
                                <span class="metric-total">/ <?php echo $user['total_modules']; ?></span>
                            </td>
                            <td>
                                <span class="metric-value completed"><?php echo $user['modules_completed']; ?></span>
                                <span class="metric-total">/ <?php echo $user['total_modules']; ?></span>
                            </td>
                            <td>
                                <div class="completion-cell">
                                    <div class="completion-bar">
                                        <div class="completion-fill" style="width: <?php echo $user['completion_rate']; ?>%"></div>
                                    </div>
                                    <span class="completion-text"><?php echo $user['completion_rate']; ?>%</span>
                                </div>
                            </td>
                            <td>
                                <span class="progress-value"><?php echo $user['avg_progress'] ?? 0; ?>%</span>
                            </td>
                            <td>
                                <span class="status-badge <?php echo $user['status_class']; ?>">
                                    <?php echo $user['status']; ?>
                                </span>
                            </td>
                            <td>
                                <span class="last-activity">
                                    <?php echo $user['last_activity'] ? date('M j, Y', strtotime($user['last_activity'])) : 'Never'; ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn-view" onclick="viewUserProgress(<?php echo $user['id']; ?>)" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn-reset" onclick="resetUserProgress(<?php echo $user['id']; ?>)" title="Reset Progress">
                                        <i class="fas fa-redo"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Module Analytics Tab -->
    <div id="modules-tab" class="tab-content">
        <div class="section-header">
            <h2>Module Performance Analytics</h2>
        </div>

        <div class="modules-analytics">
            <?php foreach ($module_progress as $module): ?>
                <div class="module-analytics-card">
                    <h3><?php echo htmlspecialchars($module['title']); ?></h3>
                    
                    <div class="module-stats-grid">
                        <div class="module-stat">
                            <div class="stat-label">Enrolled Users</div>
                            <div class="stat-value"><?php echo $module['enrolled_users']; ?></div>
                        </div>
                        <div class="module-stat">
                            <div class="stat-label">Completed</div>
                            <div class="stat-value completed"><?php echo $module['completed_users']; ?></div>
                        </div>
                        <div class="module-stat">
                            <div class="stat-label">Completion Rate</div>
                            <div class="stat-value">
                                <?php 
                                $completion_rate = $module['enrolled_users'] > 0 ? 
                                    round(($module['completed_users'] / $module['enrolled_users']) * 100, 1) : 0;
                                echo $completion_rate . '%';
                                ?>
                            </div>
                        </div>
                        <div class="module-stat">
                            <div class="stat-label">Avg Progress</div>
                            <div class="stat-value"><?php echo $module['avg_progress'] ?? 0; ?>%</div>
                        </div>
                    </div>
                    
                    <div class="module-progress-bar">
                        <div class="module-progress-fill" style="width: <?php echo $completion_rate; ?>%"></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

$extra_styles = <<<EOT
<style>
.elearning-admin-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: #282c34;
    border: 1px solid #3a3f48;
    border-radius: 12px;
    padding: 25px;
    display: flex;
    align-items: center;
    gap: 20px;
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: rgba(255, 204, 0, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #ffcc00;
    font-size: 24px;
}

.stat-content h3 {
    color: #ffffff;
    font-size: 32px;
    margin: 0 0 5px 0;
}

.stat-content p {
    color: #8b8d93;
    margin: 0;
    font-size: 14px;
}

.admin-tabs {
    display: flex;
    background: #1e2129;
    border-radius: 8px;
    padding: 5px;
    margin-bottom: 30px;
    border: 1px solid #3a3f48;
}

.tab-btn {
    flex: 1;
    background: transparent;
    border: none;
    color: #8b8d93;
    padding: 15px 20px;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    font-weight: 500;
}

.tab-btn.active {
    background: #ffcc00;
    color: #1a1d24;
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    flex-wrap: wrap;
    gap: 15px;
}

.section-header h2 {
    color: #ffffff;
    margin: 0;
}

.header-actions {
    display: flex;
    gap: 15px;
    align-items: center;
}

.search-input, .filter-select {
    background: #282c34;
    border: 1px solid #3a3f48;
    color: #ffffff;
    padding: 10px 15px;
    border-radius: 6px;
    outline: none;
}

.search-input:focus, .filter-select:focus {
    border-color: #ffcc00;
}

.users-table-container {
    background: #282c34;
    border: 1px solid #3a3f48;
    border-radius: 12px;
    overflow: hidden;
}

.users-table {
    width: 100%;
    border-collapse: collapse;
}

.users-table th {
    background: #1e2129;
    color: #ffffff;
    padding: 15px 12px;
    text-align: left;
    font-weight: 600;
    border-bottom: 1px solid #3a3f48;
    font-size: 14px;
}

.users-table td {
    padding: 15px 12px;
    border-bottom: 1px solid #3a3f48;
    vertical-align: middle;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #ffcc00;
    color: #1a1d24;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 14px;
}

.user-name {
    color: #ffffff;
    font-weight: 500;
}

.user-email {
    color: #8b8d93;
    font-size: 12px;
}

.role-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 500;
}

.role-badge.student {
    background: rgba(33, 150, 243, 0.2);
    color: #2196F3;
}

.role-badge.instructor {
    background: rgba(156, 39, 176, 0.2);
    color: #9C27B0;
}

.metric-value {
    color: #ffffff;
    font-weight: 600;
}

.metric-value.completed {
    color: #4CAF50;
}

.metric-total {
    color: #8b8d93;
    font-size: 12px;
}

.completion-cell {
    display: flex;
    align-items: center;
    gap: 10px;
}

.completion-bar {
    width: 60px;
    height: 6px;
    background: #1e2129;
    border-radius: 3px;
    overflow: hidden;
}

.completion-fill {
    height: 100%;
    background: linear-gradient(90deg, #4CAF50, #8BC34A);
    border-radius: 3px;
}

.completion-text {
    color: #4CAF50;
    font-size: 12px;
    font-weight: 600;
}

.progress-value {
    color: #2196F3;
    font-weight: 600;
}

.status-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 500;
}

.status-badge.not-started {
    background: rgba(158, 158, 158, 0.2);
    color: #9E9E9E;
}

.status-badge.enrolled {
    background: rgba(255, 193, 7, 0.2);
    color: #FFC107;
}

.status-badge.in-progress {
    background: rgba(33, 150, 243, 0.2);
    color: #2196F3;
}

.status-badge.completed {
    background: rgba(76, 175, 80, 0.2);
    color: #4CAF50;
}

.last-activity {
    color: #8b8d93;
    font-size: 12px;
}

.action-buttons {
    display: flex;
    gap: 8px;
}

.btn-view, .btn-reset {
    background: transparent;
    border: 1px solid #3a3f48;
    color: #8b8d93;
    padding: 6px 8px;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-view:hover {
    border-color: #2196F3;
    color: #2196F3;
}

.btn-reset:hover {
    border-color: #FF5722;
    color: #FF5722;
}

/* Module Analytics */
.modules-analytics {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 25px;
}

.module-analytics-card {
    background: #282c34;
    border: 1px solid #3a3f48;
    border-radius: 12px;
    padding: 25px;
}

.module-analytics-card h3 {
    color: #ffffff;
    margin-bottom: 20px;
}

.module-stats-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
    margin-bottom: 20px;
}

.module-stat {
    text-align: center;
}

.stat-label {
    color: #8b8d93;
    font-size: 12px;
    margin-bottom: 5px;
}

.stat-value {
    color: #ffffff;
    font-size: 18px;
    font-weight: 600;
}

.module-progress-bar {
    width: 100%;
    height: 8px;
    background: #1e2129;
    border-radius: 4px;
    overflow: hidden;
}

.module-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #4CAF50, #8BC34A);
    border-radius: 4px;
}

/* Responsive */
@media (max-width: 768px) {
    .users-table-container {
        overflow-x: auto;
    }
    
    .section-header {
        flex-direction: column;
        align-items: stretch;
    }
    
    .header-actions {
        flex-direction: column;
    }
}
</style>
EOT;

$extra_scripts = <<<EOT
<script>
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
}

function viewUserProgress(userId) {
    // Implement user progress detail view
    alert('Viewing detailed progress for user ' + userId);
    // You can redirect to a detailed view page:
    // window.location.href = 'user-progress-detail.php?id=' + userId;
}

function resetUserProgress(userId) {
    if (confirm('Are you sure you want to reset all progress for this user? This action cannot be undone.')) {
        // Implement progress reset logic
        alert('Resetting progress for user ' + userId);
        // You can make an AJAX call to reset progress:
        // resetProgressAjax(userId);
    }
}

// Search functionality
document.getElementById('searchUsers').addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    const rows = document.querySelectorAll('.user-row');
    
    rows.forEach(row => {
        const userName = row.querySelector('.user-name').textContent.toLowerCase();
        const userEmail = row.querySelector('.user-email').textContent.toLowerCase();
        
        if (userName.includes(searchTerm) || userEmail.includes(searchTerm)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});

// Filter functionality
document.getElementById('filterStatus').addEventListener('change', function() {
    const filterValue = this.value;
    const rows = document.querySelectorAll('.user-row');
    
    rows.forEach(row => {
        if (!filterValue || row.dataset.status === filterValue) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});

document.addEventListener('DOMContentLoaded', function() {
    console.log('E-learning admin panel loaded');
});
</script>
EOT;

// Include the main layout template
include "../layouts/main_layout.php";
?>