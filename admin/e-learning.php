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

// Get ONLY STUDENTS with their Assessment & Quiz progress
$users_sql = "SELECT u.id, u.full_name, u.email, u.user_type,
                     COUNT(DISTINCT uas.id) as assessment_attempts,
                     MAX(CASE WHEN uas.passed = 1 THEN 1 ELSE 0 END) as assessment_passed,
                     MAX(uas.score_percentage) as assessment_best_score,
                     COUNT(DISTINCT uqs.id) as quiz_attempts,
                     MAX(CASE WHEN uqs.passed = 1 THEN 1 ELSE 0 END) as quiz_passed,
                     MAX(uqs.score_percentage) as quiz_best_score,
                     GREATEST(
                        COALESCE(MAX(uas.time_completed), '1970-01-01'),
                        COALESCE(MAX(uqs.time_completed), '1970-01-01')
                     ) as last_activity
              FROM users u
              LEFT JOIN user_assessment_sessions uas ON u.id = uas.user_id AND uas.status = 'completed'
              LEFT JOIN user_quiz_sessions uqs ON u.id = uqs.user_id AND uqs.status = 'completed'
              WHERE u.user_type = 'student'
              GROUP BY u.id, u.full_name, u.email, u.user_type
              ORDER BY u.full_name";

$users = [];
if ($result = mysqli_query($conn, $users_sql)) {
    while ($row = mysqli_fetch_assoc($result)) {
        // Determine status based on progress
        if ($row['assessment_attempts'] == 0 && $row['quiz_attempts'] == 0) {
            $row['status'] = 'Not Started';
            $row['status_class'] = 'not-started';
        } elseif ($row['assessment_passed'] == 1 && $row['quiz_passed'] == 1) {
            $row['status'] = 'Completed';
            $row['status_class'] = 'completed';
        } elseif ($row['assessment_attempts'] > 0 || $row['quiz_attempts'] > 0) {
            $row['status'] = 'In Progress';
            $row['status_class'] = 'in-progress';
        } else {
            $row['status'] = 'Enrolled';
            $row['status_class'] = 'enrolled';
        }
        
        $users[] = $row;
    }
}

// Get overall statistics (STUDENTS ONLY) - focus on assessments and quizzes
$stats_sql = "SELECT 
                (SELECT COUNT(*) FROM users WHERE user_type = 'student') as total_users,
                (SELECT COUNT(DISTINCT user_id) FROM user_assessment_sessions WHERE status = 'completed') as assessment_takers,
                (SELECT COUNT(DISTINCT user_id) FROM user_quiz_sessions WHERE status = 'completed') as quiz_takers,
                (SELECT COUNT(*) FROM user_assessment_sessions WHERE status = 'completed' AND passed = 1) as assessment_passes";

$stats = [];
if ($result = mysqli_query($conn, $stats_sql)) {
    $stats = mysqli_fetch_assoc($result);
}

// Get Assessment Results (All Students)
$assessment_results_sql = "SELECT u.id, u.full_name, u.email,
                                  COUNT(uas.id) as total_attempts,
                                  MAX(uas.score_percentage) as best_score,
                                  AVG(uas.score_percentage) as avg_score,
                                  SUM(CASE WHEN uas.passed = 1 THEN 1 ELSE 0 END) as passed_attempts,
                                  MAX(uas.time_completed) as last_attempt
                           FROM users u
                           LEFT JOIN user_assessment_sessions uas ON u.id = uas.user_id AND uas.status = 'completed'
                           WHERE u.user_type = 'student'
                           GROUP BY u.id, u.full_name, u.email
                           ORDER BY u.full_name";

$assessment_results = [];
if ($result = mysqli_query($conn, $assessment_results_sql)) {
    while ($row = mysqli_fetch_assoc($result)) {
        $assessment_results[] = $row;
    }
}

// Get Quiz Results (All Students)
$quiz_results_sql = "SELECT u.id, u.full_name, u.email,
                            COUNT(uqs.id) as total_attempts,
                            MAX(uqs.score_percentage) as best_score,
                            AVG(uqs.score_percentage) as avg_score,
                            SUM(CASE WHEN uqs.passed = 1 THEN 1 ELSE 0 END) as passed_attempts,
                            MAX(uqs.time_completed) as last_attempt
                     FROM users u
                     LEFT JOIN user_quiz_sessions uqs ON u.id = uqs.user_id AND uqs.status = 'completed'
                     WHERE u.user_type = 'student'
                     GROUP BY u.id, u.full_name, u.email
                     ORDER BY u.full_name";

$quiz_results = [];
if ($result = mysqli_query($conn, $quiz_results_sql)) {
    while ($row = mysqli_fetch_assoc($result)) {
        $quiz_results[] = $row;
    }
}

// Get Assessment & Quiz Statistics
$test_stats_sql = "SELECT 
                    (SELECT COUNT(DISTINCT user_id) FROM user_assessment_sessions WHERE status = 'completed') as assessment_takers,
                    (SELECT COUNT(DISTINCT user_id) FROM user_assessment_sessions WHERE status = 'completed' AND passed = 1) as assessment_passers,
                    (SELECT COUNT(DISTINCT user_id) FROM user_quiz_sessions WHERE status = 'completed') as quiz_takers,
                    (SELECT COUNT(DISTINCT user_id) FROM user_quiz_sessions WHERE status = 'completed' AND passed = 1) as quiz_passers,
                    (SELECT ROUND(AVG(score_percentage), 1) FROM user_assessment_sessions WHERE status = 'completed') as avg_assessment_score,
                    (SELECT ROUND(AVG(score_percentage), 1) FROM user_quiz_sessions WHERE status = 'completed') as avg_quiz_score";

$test_stats = [];
if ($result = mysqli_query($conn, $test_stats_sql)) {
    $test_stats = mysqli_fetch_assoc($result);
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
                <p>Total Students</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-clipboard-check"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $stats['assessment_takers'] ?? 0; ?></h3>
                <p>Assessment Takers</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-book-open"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $stats['quiz_takers'] ?? 0; ?></h3>
                <p>Quiz Takers</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-trophy"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $stats['assessment_passes'] ?? 0; ?></h3>
                <p>Assessment Passes</p>
            </div>
        </div>
    </div>

    <!-- Assessment & Quiz Statistics -->
    <div class="test-stats-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin: 30px 0;">
        <div class="test-stat-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 25px; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.1);">
            <h3 style="margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-clipboard-check" style="font-size: 30px;"></i>
                Assessment Results
            </h3>
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px;">
                <div>
                    <div style="font-size: 32px; font-weight: bold;"><?php echo $test_stats['assessment_takers'] ?? 0; ?></div>
                    <div style="opacity: 0.9;">Students Attempted</div>
                </div>
                <div>
                    <div style="font-size: 32px; font-weight: bold;"><?php echo $test_stats['assessment_passers'] ?? 0; ?></div>
                    <div style="opacity: 0.9;">Students Passed</div>
                </div>
                <div>
                    <div style="font-size: 32px; font-weight: bold;"><?php echo $test_stats['avg_assessment_score'] ?? 0; ?>%</div>
                    <div style="opacity: 0.9;">Average Score</div>
                </div>
                <div>
                    <div style="font-size: 32px; font-weight: bold;">
                        <?php 
                        $pass_rate = ($test_stats['assessment_takers'] ?? 0) > 0 ? 
                            round((($test_stats['assessment_passers'] ?? 0) / $test_stats['assessment_takers']) * 100, 1) : 0;
                        echo $pass_rate;
                        ?>%
                    </div>
                    <div style="opacity: 0.9;">Pass Rate</div>
                </div>
            </div>
        </div>

        <div class="test-stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; padding: 25px; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.1);">
            <h3 style="margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-book-open" style="font-size: 30px;"></i>
                Quiz Results
            </h3>
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px;">
                <div>
                    <div style="font-size: 32px; font-weight: bold;"><?php echo $test_stats['quiz_takers'] ?? 0; ?></div>
                    <div style="opacity: 0.9;">Students Attempted</div>
                </div>
                <div>
                    <div style="font-size: 32px; font-weight: bold;"><?php echo $test_stats['quiz_passers'] ?? 0; ?></div>
                    <div style="opacity: 0.9;">Students Passed</div>
                </div>
                <div>
                    <div style="font-size: 32px; font-weight: bold;"><?php echo $test_stats['avg_quiz_score'] ?? 0; ?>%</div>
                    <div style="opacity: 0.9;">Average Score</div>
                </div>
                <div>
                    <div style="font-size: 32px; font-weight: bold;">
                        <?php 
                        $quiz_pass_rate = ($test_stats['quiz_takers'] ?? 0) > 0 ? 
                            round((($test_stats['quiz_passers'] ?? 0) / $test_stats['quiz_takers']) * 100, 1) : 0;
                        echo $quiz_pass_rate;
                        ?>%
                    </div>
                    <div style="opacity: 0.9;">Pass Rate</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab Navigation -->
    <div class="admin-tabs">
        <button class="tab-btn active" onclick="switchTab('users')">
            <i class="fas fa-user-graduate"></i> Student Progress
        </button>
        <button class="tab-btn" onclick="switchTab('assessments')">
            <i class="fas fa-clipboard-check"></i> Assessment Results
        </button>
        <button class="tab-btn" onclick="switchTab('quizzes')">
            <i class="fas fa-book-open"></i> Quiz Results
        </button>
    </div>

    <!-- Users Progress Tab -->
    <div id="users-tab" class="tab-content active">
        <div class="section-header">
            <h2>Student Learning Progress</h2>
            <div class="header-actions">
                <input type="text" id="searchUsers" placeholder="Search students..." class="search-input">
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
                        <th>Assessment Status</th>
                        <th>Assessment Score</th>
                        <th>Quiz Status</th>
                        <th>Quiz Score</th>
                        <th>Overall Status</th>
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
                                <?php if ($user['assessment_attempts'] > 0): ?>
                                    <span class="status-badge <?php echo $user['assessment_passed'] ? 'completed' : 'in-progress'; ?>">
                                        <?php echo $user['assessment_passed'] ? '✓ Passed' : '✗ Not Passed'; ?>
                                    </span>
                                    <div style="font-size: 11px; color: #7f8c8d; margin-top: 3px;">
                                        <?php echo $user['assessment_attempts']; ?> attempt(s)
                                    </div>
                                <?php else: ?>
                                    <span class="status-badge not-started">Not Started</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($user['assessment_best_score']): ?>
                                    <span class="score-badge <?php echo $user['assessment_best_score'] >= 70 ? 'pass' : 'fail'; ?>">
                                        <?php echo number_format($user['assessment_best_score'], 1); ?>%
                                    </span>
                                <?php else: ?>
                                    <span style="color: #7f8c8d;">N/A</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($user['quiz_attempts'] > 0): ?>
                                    <span class="status-badge <?php echo $user['quiz_passed'] ? 'completed' : 'in-progress'; ?>">
                                        <?php echo $user['quiz_passed'] ? '✓ Passed' : '✗ Not Passed'; ?>
                                    </span>
                                    <div style="font-size: 11px; color: #7f8c8d; margin-top: 3px;">
                                        <?php echo $user['quiz_attempts']; ?> attempt(s)
                                    </div>
                                <?php else: ?>
                                    <span class="status-badge not-started">Not Started</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($user['quiz_best_score']): ?>
                                    <span class="score-badge <?php echo $user['quiz_best_score'] >= 70 ? 'pass' : 'fail'; ?>">
                                        <?php echo number_format($user['quiz_best_score'], 1); ?>%
                                    </span>
                                <?php else: ?>
                                    <span style="color: #7f8c8d;">N/A</span>
                                <?php endif; ?>
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

    <!-- Assessment Results Tab -->
    <div id="assessments-tab" class="tab-content">
        <h2 style="margin-bottom: 20px;">Assessment Results - All Students</h2>
        <div class="results-table-container">
            <table class="results-table">
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Email</th>
                        <th>Total Attempts</th>
                        <th>Passed Attempts</th>
                        <th>Best Score</th>
                        <th>Average Score</th>
                        <th>Last Attempt</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($assessment_results)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 40px; color: #7f8c8d;">
                                <i class="fas fa-clipboard" style="font-size: 48px; margin-bottom: 15px; display: block;"></i>
                                No assessment results yet
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($assessment_results as $result): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($result['full_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($result['email']); ?></td>
                                <td><?php echo $result['total_attempts'] ?? 0; ?></td>
                                <td><?php echo $result['passed_attempts'] ?? 0; ?></td>
                                <td>
                                    <?php if ($result['best_score']): ?>
                                        <span class="score-badge <?php echo $result['best_score'] >= 70 ? 'pass' : 'fail'; ?>">
                                            <?php echo number_format($result['best_score'], 1); ?>%
                                        </span>
                                    <?php else: ?>
                                        <span style="color: #7f8c8d;">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($result['avg_score']): ?>
                                        <?php echo number_format($result['avg_score'], 1); ?>%
                                    <?php else: ?>
                                        <span style="color: #7f8c8d;">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                    if ($result['last_attempt']) {
                                        echo date('M d, Y g:i A', strtotime($result['last_attempt']));
                                    } else {
                                        echo '<span style="color: #7f8c8d;">Not attempted</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    if ($result['passed_attempts'] > 0) {
                                        echo '<span class="status-badge passed">✓ Passed</span>';
                                    } elseif ($result['total_attempts'] > 0) {
                                        echo '<span class="status-badge failed">✗ Not Passed</span>';
                                    } else {
                                        echo '<span class="status-badge not-started">Not Started</span>';
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Quiz Results Tab -->
    <div id="quizzes-tab" class="tab-content">
        <h2 style="margin-bottom: 20px;">Quiz Results - All Students</h2>
        <div class="results-table-container">
            <table class="results-table">
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Email</th>
                        <th>Total Attempts</th>
                        <th>Passed Attempts</th>
                        <th>Best Score</th>
                        <th>Average Score</th>
                        <th>Last Attempt</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($quiz_results)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 40px; color: #7f8c8d;">
                                <i class="fas fa-book" style="font-size: 48px; margin-bottom: 15px; display: block;"></i>
                                No quiz results yet
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($quiz_results as $result): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($result['full_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($result['email']); ?></td>
                                <td><?php echo $result['total_attempts'] ?? 0; ?></td>
                                <td><?php echo $result['passed_attempts'] ?? 0; ?></td>
                                <td>
                                    <?php if ($result['best_score']): ?>
                                        <span class="score-badge <?php echo $result['best_score'] >= 70 ? 'pass' : 'fail'; ?>">
                                            <?php echo number_format($result['best_score'], 1); ?>%
                                        </span>
                                    <?php else: ?>
                                        <span style="color: #7f8c8d;">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($result['avg_score']): ?>
                                        <?php echo number_format($result['avg_score'], 1); ?>%
                                    <?php else: ?>
                                        <span style="color: #7f8c8d;">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                    if ($result['last_attempt']) {
                                        echo date('M d, Y g:i A', strtotime($result['last_attempt']));
                                    } else {
                                        echo '<span style="color: #7f8c8d;">Not attempted</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    if ($result['passed_attempts'] > 0) {
                                        echo '<span class="status-badge passed">✓ Passed</span>';
                                    } elseif ($result['total_attempts'] > 0) {
                                        echo '<span class="status-badge failed">✗ Not Passed</span>';
                                    } else {
                                        echo '<span class="status-badge not-started">Not Started</span>';
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
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

/* Assessment & Quiz Results Styles */
.results-table-container {
    background: #1a1d24;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
}

.results-table {
    width: 100%;
    border-collapse: collapse;
}

.results-table th {
    background: #1e2129;
    color: #ffffff;
    padding: 15px 12px;
    text-align: left;
    font-weight: 600;
    border-bottom: 1px solid #3a3f48;
    font-size: 14px;
}

.results-table td {
    padding: 15px 12px;
    border-bottom: 1px solid #3a3f48;
    vertical-align: middle;
    color: #e1e3e6;
}

.results-table tbody tr:hover {
    background: #23262e;
}

.score-badge {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 13px;
}

.score-badge.pass {
    background: #28a745;
    color: white;
}

.score-badge.fail {
    background: #dc3545;
    color: white;
}

.status-badge.passed {
    background: #28a745;
    color: white;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    display: inline-block;
}

.status-badge.failed {
    background: #dc3545;
    color: white;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    display: inline-block;
}

.status-badge.not-started {
    background: #6c757d;
    color: white;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    display: inline-block;
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