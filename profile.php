<?php
require_once 'includes/config.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$page_title = "My Profile";
require_once 'includes/navbar.php';

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $bio = trim($_POST['bio']);
    
    // Update basic info
    $update_sql = "UPDATE users SET full_name = ?, email = ?, bio = ? WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $update_sql);
    mysqli_stmt_bind_param($stmt, "sssi", $full_name, $email, $bio, $user_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['full_name'] = $full_name;
        $success_message = "Profile updated successfully!";
    } else {
        $error_message = "Error updating profile.";
    }
    
    // Handle profile image upload
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $file_type = $_FILES['profile_image']['type'];
        
        if (in_array($file_type, $allowed_types)) {
            $file_extension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
            $new_filename = 'user_' . $user_id . '_' . time() . '.' . $file_extension;
            $upload_path = 'uploads/profiles/' . $new_filename;
            
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
                $image_sql = "UPDATE users SET profile_image = ? WHERE user_id = ?";
                $image_stmt = mysqli_prepare($conn, $image_sql);
                mysqli_stmt_bind_param($image_stmt, "si", $new_filename, $user_id);
                mysqli_stmt_execute($image_stmt);
                $success_message = "Profile and image updated successfully!";
            }
        } else {
            $error_message = "Invalid image format. Please use JPG, PNG, or GIF.";
        }
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current = $_POST['current_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];
    
    // Verify current password
    $check_sql = "SELECT password_hash FROM users WHERE user_id = ?";
    $check_stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($check_stmt, "i", $user_id);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    $user_data = mysqli_fetch_assoc($check_result);
    
    if (password_verify($current, $user_data['password_hash'])) {
        if ($new == $confirm) {
            if (strlen($new) >= 8) {
                $new_hash = password_hash($new, PASSWORD_DEFAULT);
                $update_sql = "UPDATE users SET password_hash = ? WHERE user_id = ?";
                $update_stmt = mysqli_prepare($conn, $update_sql);
                mysqli_stmt_bind_param($update_stmt, "si", $new_hash, $user_id);
                
                if (mysqli_stmt_execute($update_stmt)) {
                    $success_message = "Password changed successfully!";
                } else {
                    $error_message = "Error changing password.";
                }
            } else {
                $error_message = "Password must be at least 8 characters.";
            }
        } else {
            $error_message = "New passwords do not match.";
        }
    } else {
        $error_message = "Current password is incorrect.";
    }
}

// Get user data
$sql = "SELECT * FROM users WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

// Get user stats
$stats_sql = "SELECT 
                (SELECT COUNT(*) FROM commissions WHERE user_id = ?) as total_commissions,
                (SELECT COUNT(*) FROM artwork_likes WHERE user_id = ?) as liked_artworks,
                (SELECT COUNT(*) FROM artwork_comments WHERE user_id = ?) as total_comments
              FROM dual";
$stmt = mysqli_prepare($conn, $stats_sql);
mysqli_stmt_bind_param($stmt, "iii", $user_id, $user_id, $user_id);
mysqli_stmt_execute($stmt);
$stats_result = mysqli_stmt_get_result($stmt);
$stats = mysqli_fetch_assoc($stats_result);
?>

<div class="profile-container">
    <div class="profile-header">
        <h1><i class="fas fa-user-circle"></i> My Profile</h1>
        <p>View and manage your personal information</p>
    </div>

    <?php if ($success_message): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
            <button class="alert-close">&times;</button>
        </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
            <button class="alert-close">&times;</button>
        </div>
    <?php endif; ?>

    <!-- Profile Card - Centered -->
    <div class="profile-card-main">
        <div class="profile-avatar-section">
            <div class="profile-avatar-wrapper">
                <img src="uploads/profiles/<?php echo htmlspecialchars($user['profile_image'] ?? 'default.jpg'); ?>" 
                     alt="Profile Picture" 
                     id="profilePreview">
            </div>
            
            <div class="profile-stats">
                <div class="stat-item">
                    <span class="stat-value"><?php echo $stats['total_commissions'] ?? 0; ?></span>
                    <span class="stat-label">Commissions</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value"><?php echo $stats['liked_artworks'] ?? 0; ?></span>
                    <span class="stat-label">Liked</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value"><?php echo $stats['total_comments'] ?? 0; ?></span>
                    <span class="stat-label">Comments</span>
                </div>
            </div>
        </div>

        <div class="profile-info-section">
            <h2><?php echo htmlspecialchars($user['full_name'] ?? $user['username']); ?></h2>
            <p class="profile-username">@<?php echo htmlspecialchars($user['username']); ?></p>
            
            <div class="info-grid">
                <div class="info-item">
                    <i class="fas fa-envelope"></i>
                    <div>
                        <small>Email</small>
                        <p><?php echo htmlspecialchars($user['email']); ?></p>
                    </div>
                </div>
                
                <div class="info-item">
                    <i class="fas fa-calendar-alt"></i>
                    <div>
                        <small>Member Since</small>
                        <p><?php echo date('F j, Y', strtotime($user['created_at'])); ?></p>
                    </div>
                </div>
                
                <div class="info-item">
                    <i class="fas fa-tag"></i>
                    <div>
                        <small>Account Type</small>
                        <p><span class="role-badge role-<?php echo $user['role']; ?>"><?php echo ucfirst($user['role']); ?></span></p>
                    </div>
                </div>
                
                <?php if (!empty($user['bio'])): ?>
                <div class="info-item full-width">
                    <i class="fas fa-align-left"></i>
                    <div>
                        <small>Bio</small>
                        <p><?php echo nl2br(htmlspecialchars($user['bio'])); ?></p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Edit Profile Button -->
            <button id="showEditFormBtn" class="btn-edit-profile">
                <i class="fas fa-edit"></i> Edit Profile
            </button>
        </div>
    </div>

    <!-- Edit Form Section (Hidden by default) -->
    <div class="edit-form-container" id="editFormSection" style="display: none;">
        <div class="profile-card">
            <div class="section-header">
                <h2>Edit Profile Information</h2>
                <button id="closeEditForm" class="close-btn">&times;</button>
            </div>
            
            <form method="POST" enctype="multipart/form-data" class="profile-form">
                <input type="hidden" name="update_profile" value="1">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="full_name"><i class="fas fa-user"></i> Full Name</label>
                        <input type="text" id="full_name" name="full_name" 
                               value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" 
                               placeholder="Enter your full name">
                    </div>

                    <div class="form-group">
                        <label for="username"><i class="fas fa-at"></i> Username</label>
                        <input type="text" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" 
                               readonly disabled class="readonly-field">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="email"><i class="fas fa-envelope"></i> Email Address</label>
                        <input type="email" id="email" name="email" 
                               value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="profile_image"><i class="fas fa-camera"></i> Profile Picture</label>
                        <div class="file-input-wrapper">
                            <input type="file" id="profile_image" name="profile_image" accept="image/*" onchange="previewImage(this)">
                            <span class="file-input-button">Choose Image</span>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="bio"><i class="fas fa-align-left"></i> Bio</label>
                    <textarea id="bio" name="bio" rows="4" placeholder="Tell us a little about yourself..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                    <button type="button" id="cancelEdit" class="btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                </div>
            </form>
        </div>

        <!-- Change Password Section -->
        <div class="profile-card">
            <div class="section-header">
                <h2> Change Password</h2>
            </div>
            <form method="POST" class="profile-form">
                <input type="hidden" name="change_password" value="1">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="current_password"><i class="fas fa-lock"></i> Current Password</label>
                        <input type="password" id="current_password" name="current_password" required>
                    </div>

                    <div class="form-group">
                        <label for="new_password"><i class="fas fa-key"></i> New Password</label>
                        <input type="password" id="new_password" name="new_password" required>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password"><i class="fas fa-check"></i> Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                </div>

                <div class="form-actions" style="justify-content: center;">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-sync-alt"></i> Update Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.profile-container {
    max-width: 900px;
    margin: 2rem auto;
    padding: 0 2rem;
}

.profile-header {
    text-align: center;
    margin-bottom: 2rem;
}

.profile-header h1 {
    color: var(--primary-color);
    margin-bottom: 0.5rem;
    font-size: 2.2rem;
}

.profile-header p {
    color: #666;
    font-size: 1.1rem;
}

/* Alert Messages */
.alert {
    padding: 1rem;
    border-radius: 10px;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.alert i {
    margin-right: 10px;
    font-size: 1.2rem;
}

.alert-close {
    background: none;
    border: none;
    font-size: 1.2rem;
    cursor: pointer;
    color: inherit;
    opacity: 0.7;
}

.alert-close:hover {
    opacity: 1;
}

/* Main Profile Card */
.profile-card-main {
    background: white;
    border-radius: 20px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    overflow: hidden;
    margin-bottom: 2rem;
    display: grid;
    grid-template-columns: 300px 1fr;
}

.profile-avatar-section {
    background: linear-gradient(135deg, var(--primary-color), #4a6491);
    padding: 2rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: white;
}

.profile-avatar-wrapper {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    border: 4px solid var(--accent-color);
    overflow: hidden;
    margin-bottom: 1.5rem;
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
}

.profile-avatar-wrapper img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.profile-stats {
    display: flex;
    justify-content: space-around;
    width: 100%;
    text-align: center;
}

.stat-value {
    display: block;
    font-size: 1.8rem;
    font-weight: bold;
    line-height: 1.2;
}

.stat-label {
    font-size: 0.9rem;
    opacity: 0.9;
}

.profile-info-section {
    padding: 2rem;
}

.profile-info-section h2 {
    color: var(--primary-color);
    margin-bottom: 0.3rem;
    font-size: 1.8rem;
}

.profile-username {
    color: #666;
    margin-bottom: 1.5rem;
    font-size: 1rem;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.info-item {
    display: flex;
    gap: 1rem;
    align-items: flex-start;
}

.info-item i {
    font-size: 1.2rem;
    color: var(--secondary-color);
    margin-top: 0.2rem;
}

.info-item small {
    color: #888;
    font-size: 0.8rem;
    display: block;
    margin-bottom: 0.2rem;
}

.info-item p {
    color: #333;
    font-weight: 500;
    margin: 0;
}

.info-item.full-width {
    grid-column: span 2;
}

.role-badge {
    padding: 0.3rem 0.8rem;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
    display: inline-block;
}

.role-user { background: #dfe6e9; color: #636e72; }
.role-artist { background: #a29bfe; color: white; }
.role-admin { background: #ffeaa7; color: #e17055; }

.btn-edit-profile {
    background: #a78bfa;
    color: white;
    border: none;
    padding: 0.8rem 2rem;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s;
}

.btn-edit-profile:hover {
    background: #8b6df7;
    transform: translateY(-2px);
}

/* Edit Form Container */
.edit-form-container {
    max-width: 800px;
    margin: 0 auto;
}

.profile-card {
    background: white;
    border-radius: 15px;
    padding: 2rem;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
    margin-bottom: 2rem;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid #f0f0f0;
}

.section-header h2 {
    color:#a78bfa;
    font-size: 1.3rem;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
    justify-content: center;
    width: 100%;
    text-align: center;
}

.section-header h2 i {
    color: #a78bfa;
}

.close-btn {
    background: none;
    border: none;
    font-size: 2rem;
    cursor: pointer;
    color: #999;
    transition: color 0.3s;
    margin-left: auto;
}

.close-btn:hover {
    color: var(--danger-color);
}

/* Form Styles */
.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}

.profile-form .form-group {
    margin-bottom: 1.5rem;
}

.profile-form label {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: var(--primary-color);
}

.profile-form label i {
    color: #a78bfa;
}

.profile-form input,
.profile-form textarea {
    width: 100%;
    padding: 0.8rem 1rem;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    font-size: 1rem;
    transition: all 0.3s;
}

.profile-form input:focus,
.profile-form textarea:focus {
    border-color: #a78bfa;
    outline: none;
    box-shadow: 0 0 0 3px rgba(167, 139, 250, 0.1);
}

.profile-form input.readonly-field {
    background: #f8f9fa;
    cursor: not-allowed;
}

/* File Input */
.file-input-wrapper {
    position: relative;
    overflow: hidden;
    display: inline-block;
    width: 100%;
}

.file-input-wrapper input[type=file] {
    position: absolute;
    left: 0;
    top: 0;
    opacity: 0;
    cursor: pointer;
    width: 100%;
    height: 100%;
}

.file-input-button {
    display: block;
    padding: 0.8rem 1rem;
    background: #f8f9fa;
    border: 2px dashed #a78bfa;
    border-radius: 8px;
    text-align: center;
    color: #a78bfa;
    transition: all 0.3s;
}

.file-input-wrapper:hover .file-input-button {
    background: #f3f0ff;
    border-color: #8b6df7;
}

/* Form Actions */
.form-actions {
    display: flex;
    gap: 1rem;
    margin-top: 2rem;
}

.btn-primary, .btn-secondary {
    padding: 0.8rem 2rem;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s;
    border: none;
    cursor: pointer;
}

.btn-primary {
    background: #a78bfa;
    color: white;
}

.btn-primary:hover {
    background: #8b6df7;
    transform: translateY(-2px);
}

.btn-secondary {
    background: red;
    color: #666;
    border: 2px solid #e9ecef;
}

.btn-secondary:hover {
    background: #e9ecef;
    transform: translateY(-2px);
}

/* Responsive */
@media (max-width: 768px) {
    .profile-card-main {
        grid-template-columns: 1fr;
    }
    
    .info-grid {
        grid-template-columns: 1fr;
    }
    
    .info-item.full-width {
        grid-column: span 1;
    }
    
    .form-row {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .btn-primary, .btn-secondary {
        width: 100%;
        justify-content: center;
    }
    
    .section-header h2 {
        font-size: 1.1rem;
    }
}
</style>

<script>
function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            document.getElementById('profilePreview').src = e.target.result;
        }
        
        reader.readAsDataURL(input.files[0]);
    }
}

// Show edit form
document.getElementById('showEditFormBtn').addEventListener('click', function() {
    document.getElementById('editFormSection').style.display = 'block';
    this.style.display = 'none';
});

// Hide edit form
function hideEditForm() {
    document.getElementById('editFormSection').style.display = 'none';
    document.getElementById('showEditFormBtn').style.display = 'inline-flex';
}

document.getElementById('closeEditForm').addEventListener('click', hideEditForm);
document.getElementById('cancelEdit').addEventListener('click', hideEditForm);

// Auto-hide alerts after 5 seconds
setTimeout(function() {
    document.querySelectorAll('.alert').forEach(alert => {
        alert.style.transition = 'opacity 0.5s';
        alert.style.opacity = '0';
        setTimeout(() => alert.remove(), 500);
    });
}, 5000);

// Alert close buttons
document.querySelectorAll('.alert-close').forEach(btn => {
    btn.addEventListener('click', function() {
        this.closest('.alert').remove();
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>