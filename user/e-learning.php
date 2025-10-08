<?php
session_start();

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php");
    exit;
}

// Include database connection
require_once "../config.php";

// Include access control
require_once "access_control.php";

// CHECK ACCESS - Redirect to appointments if user hasn't paid or not approved
$access_check = checkUserAccess($_SESSION["id"], $conn);
if (!$access_check['has_access']) {
    redirectToAppointments($access_check['message']);
}

// Initialize variables
$page_title = "E-Learning";
$header_title = "E-Learning Portal";
$notification_count = 2;
$user_id = $_SESSION["id"];

// Get modules with user progress
$modules_sql = "SELECT m.*, 
                       COALESCE(p.progress_percentage, 0) as progress,
                       COALESCE(p.completed, 0) as completed,
                       (SELECT COUNT(*) FROM user_module_progress WHERE module_id = m.id) as enrolled_count
                FROM elearning_modules m 
                LEFT JOIN user_module_progress p ON m.id = p.module_id AND p.user_id = ?
                WHERE m.status = 'Active'
                ORDER BY m.created_at";

$modules = [];
if ($stmt = mysqli_prepare($conn, $modules_sql)) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $modules[] = $row;
    }
    mysqli_stmt_close($stmt);
}

// Get video tutorials
$videos_sql = "SELECT * FROM elearning_videos WHERE status = 'Active' ORDER BY created_at";
$videos = [];
if ($result = mysqli_query($conn, $videos_sql)) {
    while ($row = mysqli_fetch_assoc($result)) {
        $videos[] = $row;
    }
}

// Get quizzes
$quizzes_sql = "SELECT * FROM elearning_quizzes WHERE status = 'Active' ORDER BY created_at";
$quizzes = [];
if ($result = mysqli_query($conn, $quizzes_sql)) {
    while ($row = mysqli_fetch_assoc($result)) {
        $quizzes[] = $row;
    }
}

ob_start();
?>

<div class="elearning-container">
    <!-- Tab Navigation -->
    <div class="elearning-tabs">
        <button class="tab-btn active" onclick="switchTab('modules')">
            <i class="fas fa-book"></i> Modules
        </button>
        <button class="tab-btn" onclick="switchTab('videos')">
            <i class="fas fa-video"></i> Video Tutorials
        </button>
        <button class="tab-btn" onclick="switchTab('quizzes')">
            <i class="fas fa-question-circle"></i> Quizzes & Assessments
        </button>
    </div>

    <!-- Modules Tab -->
    <div id="modules-tab" class="tab-content active">
        <div class="modules-header">
            <h2>Road Safety Modules</h2>
        </div>
        
        <div class="modules-grid">
            <?php foreach ($modules as $module): ?>
                <div class="module-card">
                    <div class="module-icon">
                        <i class="<?php echo $module['icon']; ?>"></i>
                    </div>
                    <h3><?php echo htmlspecialchars($module['title']); ?></h3>
                    <div class="module-status">
                        <span class="status-badge active">Active</span>
                    </div>
                    <p class="module-description">
                        <?php echo htmlspecialchars($module['description']); ?>
                    </p>
                    
                    <div class="module-stats">
                        <div class="stat-item">
                            <i class="far fa-clock"></i>
                            <span>Duration: <?php echo $module['duration_minutes']; ?> minutes</span>
                        </div>
                        <div class="stat-item">
                            <i class="fas fa-users"></i>
                            <span>Enrolled: <?php echo $module['enrolled_count']; ?> students</span>
                        </div>
                        <div class="stat-item">
                            <i class="fas fa-star"></i>
                            <span>Rating: 4.8/5</span>
                        </div>
                    </div>
                    
                    <div class="progress-section">
                        <div class="progress-label">Progress</div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo $module['progress']; ?>%"></div>
                        </div>
                        <div class="progress-text"><?php echo $module['progress']; ?>%</div>
                    </div>
                    
                    <div class="module-actions">
                        <button class="btn-primary" onclick="startModule(<?php echo $module['id']; ?>)">
                            <?php echo $module['progress'] > 0 ? 'Continue' : 'Start'; ?>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Video Tutorials Tab -->
    <div id="videos-tab" class="tab-content">
        <div class="videos-header">
            <h2>Video Tutorials</h2>
        </div>
        
        <div class="videos-grid">
            <?php foreach ($videos as $video): ?>
                <div class="video-card">
                    <div class="video-thumbnail">
                        <i class="fas fa-play-circle play-icon"></i>
                        <div class="video-duration"><?php echo $video['duration_minutes']; ?> min</div>
                    </div>
                    <div class="video-content">
                        <h3><?php echo htmlspecialchars($video['title']); ?></h3>
                        <p><?php echo htmlspecialchars($video['description']); ?></p>
                        <button class="btn-primary" onclick="playVideo(<?php echo $video['id']; ?>)">
                            <i class="fas fa-play"></i> Watch Video
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Quizzes Tab -->
    <div id="quizzes-tab" class="tab-content">
        <div class="quizzes-header">
            <h2>Quizzes & Assessments</h2>
        </div>
        
        <div class="quizzes-grid">
            <?php foreach ($quizzes as $quiz): ?>
                <div class="quiz-card">
                    <div class="quiz-icon">
                        <i class="fas fa-clipboard-check"></i>
                    </div>
                    <h3><?php echo htmlspecialchars($quiz['title']); ?></h3>
                    <p><?php echo htmlspecialchars($quiz['description']); ?></p>
                    
                    <div class="quiz-info">
                        <div class="info-item">
                            <i class="far fa-clock"></i>
                            <span>Time Limit: <?php echo $quiz['time_limit_minutes']; ?> minutes</span>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-percentage"></i>
                            <span>Passing Score: <?php echo $quiz['passing_score']; ?>%</span>
                        </div>
                    </div>
                    
                    <div class="quiz-actions">
                        <button class="btn-primary" onclick="startQuiz(<?php echo $quiz['id']; ?>)">
                            <i class="fas fa-play"></i> Start Quiz
                        </button>
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
.elearning-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
}

.elearning-tabs {
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

.tab-btn:hover:not(.active) {
    background: #2a2f38;
    color: #ffffff;
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

.modules-header, .videos-header, .quizzes-header {
    text-align: center;
    margin-bottom: 30px;
}

.modules-header h2, .videos-header h2, .quizzes-header h2 {
    color: #ffffff;
    font-size: 28px;
    margin-bottom: 10px;
}

.modules-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 30px;
}

.module-card {
    background: #282c34;
    border: 1px solid #3a3f48;
    border-radius: 12px;
    padding: 25px;
    transition: all 0.3s;
}

.module-card:hover {
    border-color: #ffcc00;
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.3);
}

.module-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: rgba(255, 204, 0, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
    color: #ffcc00;
    font-size: 24px;
}

.module-card h3 {
    color: #ffffff;
    font-size: 20px;
    margin-bottom: 15px;
    text-align: center;
}

.module-status {
    text-align: center;
    margin-bottom: 15px;
}

.status-badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
}

.status-badge.active {
    background: rgba(76, 175, 80, 0.2);
    color: #4CAF50;
}

.module-description {
    color: #8b8d93;
    line-height: 1.6;
    margin-bottom: 20px;
    text-align: center;
    font-size: 14px;
}

.module-stats {
    margin-bottom: 20px;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
    color: #8b8d93;
    font-size: 13px;
}

.stat-item i {
    color: #ffcc00;
    width: 16px;
}

.progress-section {
    margin-bottom: 25px;
}

.progress-label {
    color: #8b8d93;
    font-size: 12px;
    margin-bottom: 8px;
    text-transform: uppercase;
    font-weight: 500;
}

.progress-bar {
    width: 100%;
    height: 8px;
    background: #1e2129;
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 5px;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #4CAF50, #8BC34A);
    border-radius: 4px;
    transition: width 0.3s;
}

.progress-text {
    text-align: right;
    color: #4CAF50;
    font-size: 12px;
    font-weight: 600;
}

.module-actions {
    text-align: center;
}

.btn-primary {
    background: #ffcc00;
    color: #1a1d24;
    border: none;
    padding: 12px 24px;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-primary:hover {
    background: #e6b800;
    transform: translateY(-2px);
}

/* Video Tutorials Styles */
.videos-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 25px;
}

.video-card {
    background: #282c34;
    border: 1px solid #3a3f48;
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.3s;
}

.video-card:hover {
    border-color: #ffcc00;
    transform: translateY(-3px);
}

.video-thumbnail {
    height: 200px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
}

.play-icon {
    font-size: 48px;
    color: rgba(255,255,255,0.9);
    cursor: pointer;
}

.video-duration {
    position: absolute;
    bottom: 10px;
    right: 10px;
    background: rgba(0,0,0,0.7);
    color: white;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
}

.video-content {
    padding: 20px;
}

.video-content h3 {
    color: #ffffff;
    margin-bottom: 10px;
}

.video-content p {
    color: #8b8d93;
    font-size: 14px;
    margin-bottom: 15px;
    line-height: 1.5;
}

/* Quiz Styles */
.quizzes-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 25px;
}

.quiz-card {
    background: #282c34;
    border: 1px solid #3a3f48;
    border-radius: 12px;
    padding: 25px;
    text-align: center;
    transition: all 0.3s;
}

.quiz-card:hover {
    border-color: #ffcc00;
    transform: translateY(-3px);
}

.quiz-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: rgba(33, 150, 243, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
    color: #2196F3;
    font-size: 24px;
}

.quiz-card h3 {
    color: #ffffff;
    margin-bottom: 15px;
}

.quiz-card p {
    color: #8b8d93;
    font-size: 14px;
    margin-bottom: 20px;
    line-height: 1.5;
}

.quiz-info {
    margin-bottom: 25px;
}

.info-item {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin-bottom: 8px;
    color: #8b8d93;
    font-size: 13px;
}

.info-item i {
    color: #2196F3;
    width: 16px;
}

/* Responsive */
@media (max-width: 768px) {
    .elearning-tabs {
        flex-direction: column;
    }
    
    .modules-grid, .videos-grid, .quizzes-grid {
        grid-template-columns: 1fr;
    }
    
    .module-card, .video-card, .quiz-card {
        margin-bottom: 20px;
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

function startModule(moduleId) {
    // For now, just show an alert - you can implement the actual module viewer later
    alert('Starting module ' + moduleId + '. Module viewer will be implemented next.');
    
    // You can redirect to a module viewer page:
    // window.location.href = 'module-viewer.php?id=' + moduleId;
}

function playVideo(videoId) {
    alert('Playing video ' + videoId + '. Video player will be implemented next.');
    
    // You can redirect to a video player page:
    // window.location.href = 'video-player.php?id=' + videoId;
}

function startQuiz(quizId) {
    alert('Starting quiz ' + quizId + '. Quiz system will be implemented next.');
    
    // You can redirect to a quiz page:
    // window.location.href = 'quiz.php?id=' + quizId;
}

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    console.log('E-learning portal loaded');
});
</script>
EOT;

// Include the main layout template
include "../layouts/main_layout.php";
?>