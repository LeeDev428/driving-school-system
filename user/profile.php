<?php
session_start();

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php");
    exit;
}

// Include database connection
require_once "../config.php";

// Initialize variables
$page_title = "My Profile";
$header_title = "Edit Profile";
$notification_count = 0;

$user_id = $_SESSION["id"];
$success_message = "";
$error_message = "";

// Fetch current user data
$user_data = [];
$sql = "SELECT full_name, email, contact_number, license_type, profile_image FROM users WHERE id = ?";
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user_data = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        
        // Update Profile Information
        if ($_POST['action'] == 'update_profile') {
            $full_name = trim($_POST['full_name']);
            $email = trim($_POST['email']);
            $contact_number = trim($_POST['contact_number']);
            $license_type = trim($_POST['license_type']);
            
            // Validate inputs
            if (empty($full_name) || empty($email)) {
                $error_message = "Full name and email are required.";
            } else {
                // Check if email is already taken by another user
                $check_sql = "SELECT id FROM users WHERE email = ? AND id != ?";
                if ($stmt = mysqli_prepare($conn, $check_sql)) {
                    mysqli_stmt_bind_param($stmt, "si", $email, $user_id);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_store_result($stmt);
                    
                    if (mysqli_stmt_num_rows($stmt) > 0) {
                        $error_message = "This email is already taken by another user.";
                    } else {
                        // Update profile
                        $update_sql = "UPDATE users SET full_name = ?, email = ?, contact_number = ?, license_type = ? WHERE id = ?";
                        if ($update_stmt = mysqli_prepare($conn, $update_sql)) {
                            mysqli_stmt_bind_param($update_stmt, "ssssi", $full_name, $email, $contact_number, $license_type, $user_id);
                            
                            if (mysqli_stmt_execute($update_stmt)) {
                                $_SESSION['full_name'] = $full_name;
                                $_SESSION['email'] = $email;
                                $user_data['full_name'] = $full_name;
                                $user_data['email'] = $email;
                                $user_data['contact_number'] = $contact_number;
                                $user_data['license_type'] = $license_type;
                                $success_message = "Profile updated successfully!";
                            } else {
                                $error_message = "Error updating profile. Please try again.";
                            }
                            mysqli_stmt_close($update_stmt);
                        }
                    }
                    mysqli_stmt_close($stmt);
                }
            }
        }
        
        // Update Password
        if ($_POST['action'] == 'update_password') {
            $current_password = $_POST['current_password'];
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];
            
            // Validate inputs
            if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                $error_message = "All password fields are required.";
            } elseif ($new_password !== $confirm_password) {
                $error_message = "New passwords do not match.";
            } elseif (strlen($new_password) < 6) {
                $error_message = "New password must be at least 6 characters long.";
            } else {
                // Verify current password
                $verify_sql = "SELECT password FROM users WHERE id = ?";
                if ($stmt = mysqli_prepare($conn, $verify_sql)) {
                    mysqli_stmt_bind_param($stmt, "i", $user_id);
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);
                    $row = mysqli_fetch_assoc($result);
                    
                    if (password_verify($current_password, $row['password'])) {
                        // Update password
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $update_sql = "UPDATE users SET password = ? WHERE id = ?";
                        if ($update_stmt = mysqli_prepare($conn, $update_sql)) {
                            mysqli_stmt_bind_param($update_stmt, "si", $hashed_password, $user_id);
                            
                            if (mysqli_stmt_execute($update_stmt)) {
                                $success_message = "Password updated successfully!";
                            } else {
                                $error_message = "Error updating password. Please try again.";
                            }
                            mysqli_stmt_close($update_stmt);
                        }
                    } else {
                        $error_message = "Current password is incorrect.";
                    }
                    mysqli_stmt_close($stmt);
                }
            }
        }
        
        // Upload Profile Image
        if ($_POST['action'] == 'upload_image') {
            if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
                $file = $_FILES['profile_image'];
                $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                $max_size = 5 * 1024 * 1024; // 5MB
                
                // Validate file type
                if (!in_array($file['type'], $allowed_types)) {
                    $error_message = 'Invalid file type. Only JPG, PNG, and GIF are allowed.';
                } elseif ($file['size'] > $max_size) {
                    $error_message = 'File size too large. Maximum size is 5MB.';
                } else {
                    // Create uploads directory if it doesn't exist
                    $upload_dir = '../uploads/profiles/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    // Delete old profile image if exists
                    if (!empty($user_data['profile_image']) && file_exists($upload_dir . $user_data['profile_image'])) {
                        unlink($upload_dir . $user_data['profile_image']);
                    }
                    
                    // Generate unique filename
                    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $unique_filename = 'profile_' . $user_id . '_' . time() . '.' . $file_extension;
                    $upload_path = $upload_dir . $unique_filename;
                    
                    // Move uploaded file
                    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                        // Update database
                        $update_sql = "UPDATE users SET profile_image = ? WHERE id = ?";
                        if ($stmt = mysqli_prepare($conn, $update_sql)) {
                            mysqli_stmt_bind_param($stmt, "si", $unique_filename, $user_id);
                            
                            if (mysqli_stmt_execute($stmt)) {
                                $user_data['profile_image'] = $unique_filename;
                                $success_message = "Profile image uploaded successfully!";
                            } else {
                                $error_message = "Error updating profile image in database.";
                            }
                            mysqli_stmt_close($stmt);
                        }
                    } else {
                        $error_message = "Failed to upload image. Please try again.";
                    }
                }
            } else {
                $error_message = "Please select an image to upload.";
            }
        }
        
        // Delete Profile Image
        if ($_POST['action'] == 'delete_image') {
            if (!empty($user_data['profile_image'])) {
                $upload_dir = '../uploads/profiles/';
                $image_path = $upload_dir . $user_data['profile_image'];
                
                // Delete file if exists
                if (file_exists($image_path)) {
                    unlink($image_path);
                }
                
                // Update database
                $update_sql = "UPDATE users SET profile_image = NULL WHERE id = ?";
                if ($stmt = mysqli_prepare($conn, $update_sql)) {
                    mysqli_stmt_bind_param($stmt, "i", $user_id);
                    
                    if (mysqli_stmt_execute($stmt)) {
                        $user_data['profile_image'] = null;
                        $success_message = "Profile image removed successfully!";
                    }
                    mysqli_stmt_close($stmt);
                }
            }
        }
    }
}

// Generate content
ob_start();
?>

<style>
    .profile-container {
        max-width: 1000px;
        margin: 0 auto;
    }
    
    .profile-grid {
        display: grid;
        grid-template-columns: 300px 1fr;
        gap: 20px;
        margin-bottom: 20px;
    }
    
    .profile-sidebar {
        background-color: #282c34;
        border-radius: 10px;
        padding: 30px;
        text-align: center;
        border: 1px solid #3a3f48;
        height: fit-content;
    }
    
    .profile-avatar {
        width: 150px;
        height: 150px;
        border-radius: 50%;
        margin: 0 auto 20px;
        background-color: #ffcc00;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 60px;
        font-weight: bold;
        color: #1a1d24;
        overflow: hidden;
        border: 4px solid #3a3f48;
    }
    
    .profile-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .profile-name {
        font-size: 22px;
        font-weight: 600;
        margin-bottom: 5px;
    }
    
    .profile-role {
        color: #8b8d93;
        font-size: 14px;
        margin-bottom: 20px;
    }
    
    .upload-btn, .delete-btn {
        width: 100%;
        padding: 10px;
        margin: 5px 0;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 500;
        transition: all 0.3s;
    }
    
    .upload-btn {
        background-color: #ffcc00;
        color: #1a1d24;
    }
    
    .upload-btn:hover {
        background-color: #ffd700;
    }
    
    .delete-btn {
        background-color: #ff4444;
        color: white;
    }
    
    .delete-btn:hover {
        background-color: #cc0000;
    }
    
    .form-section {
        background-color: #282c34;
        border-radius: 10px;
        padding: 25px;
        margin-bottom: 20px;
        border: 1px solid #3a3f48;
    }
    
    .section-title {
        font-size: 18px;
        font-weight: 600;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #3a3f48;
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 8px;
        color: #8b8d93;
        font-size: 14px;
        font-weight: 500;
    }
    
    .form-control {
        width: 100%;
        padding: 12px;
        border-radius: 5px;
        border: 1px solid #3a3f48;
        background-color: #1e2129;
        color: white;
        font-size: 14px;
    }
    
    .form-control:focus {
        outline: none;
        border-color: #ffcc00;
    }
    
    .submit-btn {
        background-color: #ffcc00;
        color: #1a1d24;
        padding: 12px 30px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 600;
        transition: all 0.3s;
    }
    
    .submit-btn:hover {
        background-color: #ffd700;
        transform: translateY(-2px);
    }
    
    .alert {
        padding: 15px 20px;
        border-radius: 5px;
        margin-bottom: 20px;
        font-size: 14px;
    }
    
    .alert-success {
        background-color: rgba(76, 175, 80, 0.2);
        border: 1px solid #4caf50;
        color: #4caf50;
    }
    
    .alert-error {
        background-color: rgba(244, 67, 54, 0.2);
        border: 1px solid #f44336;
        color: #f44336;
    }
    
    input[type="file"] {
        display: none;
    }
    
    @media (max-width: 768px) {
        .profile-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="profile-container">
    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($error_message)): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
        </div>
    <?php endif; ?>
    
    <div class="profile-grid">
        <!-- Profile Sidebar -->
        <div class="profile-sidebar">
            <div class="profile-avatar">
                <?php if (!empty($user_data['profile_image'])): ?>
                    <img src="../uploads/profiles/<?php echo htmlspecialchars($user_data['profile_image']); ?>" alt="Profile Picture">
                <?php else: ?>
                    <?php echo strtoupper(substr($user_data['full_name'], 0, 1)); ?>
                <?php endif; ?>
            </div>
            
            <div class="profile-name"><?php echo htmlspecialchars($user_data['full_name']); ?></div>
            <div class="profile-role">Student</div>
            
            <form method="POST" enctype="multipart/form-data" id="imageUploadForm">
                <input type="hidden" name="action" value="upload_image">
                <input type="file" name="profile_image" id="profileImageInput" accept="image/*" onchange="document.getElementById('imageUploadForm').submit();">
                <label for="profileImageInput" class="upload-btn">
                    <i class="fas fa-camera"></i> Upload Photo
                </label>
            </form>
            <br>
            
            <?php if (!empty($user_data['profile_image'])): ?>
                <form method="POST" onsubmit="return confirm('Are you sure you want to remove your profile picture?');">
                    <input type="hidden" name="action" value="delete_image">
                    <button type="submit" class="delete-btn">
                        <i class="fas fa-trash"></i> Remove Photo
                    </button>
                </form>
            <?php endif; ?>
        </div>
        
        <!-- Profile Forms -->
        <div>
            <!-- Profile Information -->
            <div class="form-section">
                <div class="section-title">
                    <i class="fas fa-user"></i> Profile Information
                </div>
                
                <form method="POST">
                    <input type="hidden" name="action" value="update_profile">
                    
                    <div class="form-group">
                        <label for="full_name">Full Name *</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" 
                               value="<?php echo htmlspecialchars($user_data['full_name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="contact_number">Contact Number</label>
                        <input type="text" class="form-control" id="contact_number" name="contact_number" 
                               value="<?php echo htmlspecialchars($user_data['contact_number'] ?? ''); ?>" 
                               placeholder="e.g., 09123456789">
                    </div>
                    
                    <div class="form-group">
                        <label for="license_type">License Type</label>
                        <select class="form-control" id="license_type" name="license_type">
                            <option value="">Select License Type</option>
                            <option value="Student Permit" <?php echo ($user_data['license_type'] ?? '') == 'Student Permit' ? 'selected' : ''; ?>>Student Permit</option>
                            <option value="Non-Professional" <?php echo ($user_data['license_type'] ?? '') == 'Non-Professional' ? 'selected' : ''; ?>>Non-Professional</option>
                            <option value="Professional" <?php echo ($user_data['license_type'] ?? '') == 'Professional' ? 'selected' : ''; ?>>Professional</option>
                            <option value="Motorcycle" <?php echo ($user_data['license_type'] ?? '') == 'Motorcycle' ? 'selected' : ''; ?>>Motorcycle</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="submit-btn">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </form>
            </div>
            
            <!-- Change Password -->
            <div class="form-section">
                <div class="section-title">
                    <i class="fas fa-lock"></i> Change Password
                </div>
                
                <form method="POST">
                    <input type="hidden" name="action" value="update_password">
                    
                    <div class="form-group">
                        <label for="current_password">Current Password *</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">New Password * (min. 6 characters)</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password *</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <button type="submit" class="submit-btn">
                        <i class="fas fa-key"></i> Update Password
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include(__DIR__ . '/../layouts/main_layout.php');
?>
