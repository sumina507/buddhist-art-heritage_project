<?php
require_once 'includes/config.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$page_title = "My Profile";
require_once 'includes/navbar.php';

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$success_message = '';
$error_message = '';

// Get current user data with artist bio if artist
$sql = "SELECT u.*, a.bio as artist_bio, a.artist_id, a.specialization, a.experience_years 
        FROM users u 
        LEFT JOIN artists a ON u.user_id = a.user_id 
        WHERE u.user_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $bio = trim($_POST['bio']);
    
    // Update users table
    $update_sql = "UPDATE users SET full_name = ?, email = ? WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $update_sql);
    mysqli_stmt_bind_param($stmt, "ssi", $full_name, $email, $user_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['full_name'] = $full_name;
        $success_message = "Profile updated successfully!";
        
        // If user is artist, update artists table bio
        if ($role == 'artist') {
            $artist_bio = trim($_POST['artist_bio'] ?? '');
            $update_artist_sql = "UPDATE artists SET bio = ? WHERE user_id = ?";
            $update_artist_stmt = mysqli_prepare($conn, $update_artist_sql);
            mysqli_stmt_bind_param($update_artist_stmt, "si", $artist_bio, $user_id);
            mysqli_stmt_execute($update_artist_stmt);
        }
    } else {
        $error_message = "Error updating profile.";
    }
    
    // Handle profile image upload
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $file_type = $_FILES['profile_image']['type'];
        $file_size = $_FILES['profile_image']['size'];
        
        if (in_array($file_type, $allowed_types)) {
            if ($file_size <= 2 * 1024 * 1024) {
                $file_extension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
                $new_filename = 'user_' . $user_id . '_' . time() . '.' . $file_extension;
                $upload_path = 'uploads/profiles/' . $new_filename;
                
                if (!file_exists('uploads/profiles')) {
                    mkdir('uploads/profiles', 0777, true);
                }
                
                if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
                    // Delete old image if not default
                    $old_image = $user['profile_image'] ?? 'default.jpg';
                    if ($old_image != 'default.jpg' && file_exists('uploads/profiles/' . $old_image)) {
                        unlink('uploads/profiles/' . $old_image);
                    }
                    
                    $image_sql = "UPDATE users SET profile_image = ? WHERE user_id = ?";
                    $image_stmt = mysqli_prepare($conn, $image_sql);
                    mysqli_stmt_bind_param($image_stmt, "si", $new_filename, $user_id);
                    mysqli_stmt_execute($image_stmt);
                    $_SESSION['profile_image'] = $new_filename;
                    $success_message = "Profile and image updated successfully!";
                }
            } else {
                $error_message = "File too large. Maximum size is 2MB.";
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

// Get stats based on role
if ($role == 'artist') {
    $artist_id = $user['artist_id'] ?? 0;
    if (!$artist_id) {
        $get_artist = "SELECT artist_id FROM artists WHERE user_id = ?";
        $a_stmt = mysqli_prepare($conn, $get_artist);
        mysqli_stmt_bind_param($a_stmt, "i", $user_id);
        mysqli_stmt_execute($a_stmt);
        $a_result = mysqli_stmt_get_result($a_stmt);
        $a_data = mysqli_fetch_assoc($a_result);
        $artist_id = $a_data['artist_id'] ?? 0;
        $_SESSION['artist_id'] = $artist_id;
    }
    
    $stats_sql = "SELECT 
                (SELECT COUNT(*) FROM commissions WHERE artist_id = ?) as total_commissions,
                (SELECT COUNT(*) FROM artwork_likes al 
                 JOIN artworks a ON al.artwork_id = a.artwork_id 
                 WHERE a.artist_id = ?) as liked_artworks,
                (SELECT COUNT(*) FROM artworks WHERE artist_id = ?) as total_artworks";
    $stats_stmt = mysqli_prepare($conn, $stats_sql);
    mysqli_stmt_bind_param($stats_stmt, "iii", $artist_id, $artist_id, $artist_id);
} else {
    $stats_sql = "SELECT 
                (SELECT COUNT(*) FROM commissions WHERE user_id = ?) as total_commissions,
                (SELECT COUNT(*) FROM artwork_likes WHERE user_id = ?) as liked_artworks,
                (SELECT COUNT(*) FROM artwork_comments WHERE user_id = ?) as total_comments";
    $stats_stmt = mysqli_prepare($conn, $stats_sql);
    mysqli_stmt_bind_param($stats_stmt, "iii", $user_id, $user_id, $user_id);
}

mysqli_stmt_execute($stats_stmt);
$stats_result = mysqli_stmt_get_result($stats_stmt);
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

    <!-- Profile Card -->
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
                    <span class="stat-label">
                        <?php echo $role == 'artist' ? 'Requests Received' : 'Custom Requests'; ?>
                    </span>
                </div>
                <div class="stat-item">
                    <span class="stat-value"><?php echo $stats['liked_artworks'] ?? 0; ?></span>
                    <span class="stat-label">
                        <?php echo $role == 'artist' ? 'Artwork Likes' : 'Liked'; ?>
                    </span>
                </div>
                <div class="stat-item">
                    <span class="stat-value">
                        <?php echo $role == 'artist' ? ($stats['total_artworks'] ?? 0) : ($stats['total_comments'] ?? 0); ?>
                    </span>
                    <span class="stat-label">
                        <?php echo $role == 'artist' ? 'Artworks Posted' : 'Comments'; ?>
                    </span>
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
                
                <!-- FIXED: Show bio from artists table for artists, from users table for regular users -->
                <?php if ($role == 'artist'): ?>
                    <?php if (!empty($user['artist_bio'])): ?>
                    <div class="info-item full-width">
                        <i class="fas fa-align-left"></i>
                        <div>
                            <small>Bio</small>
                            <p><?php echo nl2br(htmlspecialchars($user['artist_bio'])); ?></p>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="info-item full-width">
                        <i class="fas fa-align-left"></i>
                        <div>
                            <small>Bio</small>
                            <p class="no-bio">No bio added yet.</p>
                        </div>
                    </div>
                    <?php endif; ?>
                <?php else: ?>
                    <?php if (!empty($user['bio'])): ?>
                    <div class="info-item full-width">
                        <i class="fas fa-align-left"></i>
                        <div>
                            <small>Bio</small>
                            <p><?php echo nl2br(htmlspecialchars($user['bio'])); ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            
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
                        <small>Max size: 2MB (JPG, PNG, GIF)</small>
                    </div>
                </div>

                <!-- Bio field - Different for artists and regular users -->
                <?php if ($role == 'artist'): ?>
                    <div class="form-group">
                        <label for="artist_bio"><i class="fas fa-align-left"></i> Biography (Artist)</label>
                        <textarea id="artist_bio" name="artist_bio" rows="5" 
                                  placeholder="Tell your artistic journey, specialization, experience..."><?php echo htmlspecialchars($user['artist_bio'] ?? ''); ?></textarea>
                        <small>Share your art background, style, and experience with clients.</small>
                    </div>
                <?php else: ?>
                    <div class="form-group">
                        <label for="bio"><i class="fas fa-align-left"></i> Bio</label>
                        <textarea id="bio" name="bio" rows="4" 
                                  placeholder="Tell us a little about yourself..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                    </div>
                <?php endif; ?>

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
                <h2>Change Password</h2>
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
/* Add secondary button style */
.btn-secondary {
    background: #6c757d;
    color: white;
    padding: 0.8rem 1.5rem;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.2s;
    border: none;
    cursor: pointer;
    font-size: 0.9rem;
    width: 100%;
}

.btn-secondary:hover {
    background: #5a6268;
    transform: translateY(-2px);
}

.no-bio {
    color: #adb5bd;
    font-style: italic;
}

/* Rest of your existing styles remain the same */
:root {
    --primary-color: #2c3e50;
    --secondary-color: #e74c3c;
    --accent-color: #f1c40f;
    --success-color: #27ae60;
    --info-color: #3498db;
}

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
    font-size: 2rem;
}

.profile-header h1 i { color: var(--secondary-color); }

.profile-header p {
    color: #6c757d;
    font-size: 0.9rem;
}

/* Alert Messages */
.alert {
    padding: 0.8rem 1rem;
    border-radius: 10px;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
.alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
.alert i { margin-right: 10px; }

.alert-close {
    background: none;
    border: none;
    font-size: 1.2rem;
    cursor: pointer;
    color: inherit;
    opacity: 0.6;
}
.alert-close:hover { opacity: 1; }

/* Main Profile Card */
.profile-card-main {
    background: white;
    border-radius: 20px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
    overflow: hidden;
    margin-bottom: 2rem;
    display: grid;
    grid-template-columns: 300px 1fr;
}

.profile-avatar-section {
    background: linear-gradient(135deg, #2c3e50, #4a6491);
    padding: 2rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: white;
}

.profile-avatar-wrapper {
    width: 130px;
    height: 130px;
    border-radius: 50%;
    border: 3px solid var(--secondary-color);
    overflow: hidden;
    margin-bottom: 1rem;
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
    gap: 0.5rem;
}

.stat-item { text-align: center; }

.stat-value {
    display: block;
    font-size: 1.3rem;
    font-weight: bold;
    line-height: 1.2;
}

.stat-label {
    font-size: 0.65rem;
    opacity: 0.8;
    display: block;
    margin-top: 2px;
}

.profile-info-section { padding: 1.5rem; }

.profile-info-section h2 {
    color: var(--primary-color);
    margin-bottom: 0.2rem;
    font-size: 1.5rem;
}

.profile-username {
    color: #6c757d;
    margin-bottom: 1rem;
    font-size: 0.85rem;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.info-item {
    display: flex;
    gap: 0.8rem;
    align-items: flex-start;
}

.info-item i {
    font-size: 1rem;
    color: var(--secondary-color);
    margin-top: 0.2rem;
}

.info-item small {
    color: #6c757d;
    font-size: 0.7rem;
    display: block;
    margin-bottom: 0.2rem;
}

.info-item p {
    color: #2c3e50;
    font-weight: 500;
    margin: 0;
    font-size: 0.85rem;
}

.info-item.full-width { grid-column: span 2; }

.role-badge {
    padding: 0.2rem 0.6rem;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 600;
    display: inline-block;
}

.role-user { background: #e9ecef; color: #495057; }
.role-artist { background: #d4edda; color: #155724; }
.role-admin { background: #cce5ff; color: #004085; }

.btn-edit-profile {
    background: linear-gradient(135deg, #e74c3c, #c0392b);
    color: white;
    border: none;
    padding: 0.8rem 1.5rem;
    border-radius: 8px;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.2s;
    width: 100%;
}

.btn-edit-profile:hover { transform: translateY(-2px); }

/* Edit Form */
.edit-form-container {
    max-width: 800px;
    margin: 0 auto;
}

.profile-card {
    background: white;
    border-radius: 16px;
    padding: 1.5rem;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    margin-bottom: 1.5rem;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    padding-bottom: 0.8rem;
    border-bottom: 1px solid #e9ecef;
}

.section-header h2 {
    color: var(--secondary-color);
    font-size: 1.2rem;
    margin: 0;
    text-align: center;
    width: 100%;
}

.close-btn {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: #adb5bd;
    transition: color 0.2s;
}

.close-btn:hover { color: var(--secondary-color); }

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1rem;
}

.profile-form .form-group { margin-bottom: 1rem; }

.profile-form label {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 0.4rem;
    font-weight: 600;
    color: var(--primary-color);
    font-size: 0.8rem;
}

.profile-form label i { color: var(--secondary-color); }

.profile-form input,
.profile-form textarea {
    width: 100%;
    padding: 0.7rem 0.8rem;
    border: 1.5px solid #e9ecef;
    border-radius: 10px;
    font-size: 0.85rem;
    transition: all 0.2s;
}

.profile-form input:focus,
.profile-form textarea:focus {
    border-color: var(--secondary-color);
    outline: none;
    box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.1);
}

.profile-form input.readonly-field {
    background: #f8f9fa;
    cursor: not-allowed;
}

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
    padding: 0.7rem 0.8rem;
    background: #f8f9fa;
    border: 1.5px dashed var(--secondary-color);
    border-radius: 10px;
    text-align: center;
    color: var(--secondary-color);
    font-size: 0.8rem;
    transition: all 0.2s;
}

.file-input-wrapper:hover .file-input-button {
    background: #fef5f4;
    border-color: #c0392b;
}

.form-actions {
    display: flex;
    gap: 1rem;
    margin-top: 1.5rem;
}

.btn-primary {
    background: linear-gradient(135deg, #e74c3c, #c0392b);
    color: white;
    padding: 0.8rem 1.5rem;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.2s;
    border: none;
    cursor: pointer;
    font-size: 0.9rem;
    width: 100%;
}

.btn-primary:hover { transform: translateY(-2px); }

/* Responsive */
@media (max-width: 768px) {
    .profile-container { padding: 0 1rem; }
    .profile-card-main { grid-template-columns: 1fr; }
    .profile-avatar-section { padding: 1.5rem; }
    .profile-avatar-wrapper { width: 100px; height: 100px; }
    .info-grid { grid-template-columns: 1fr; }
    .info-item.full-width { grid-column: span 1; }
    .form-row { grid-template-columns: 1fr; gap: 0.8rem; }
    .form-actions { flex-direction: column; }
    .btn-primary { width: 100%; justify-content: center; }
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

document.getElementById('showEditFormBtn').addEventListener('click', function() {
    document.getElementById('editFormSection').style.display = 'block';
    this.style.display = 'none';
});

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
        setTimeout(() => alert.remove(), 200);
    });
}, 2000);

// Alert close buttons
document.querySelectorAll('.alert-close').forEach(btn => {
    btn.addEventListener('click', function() {
        this.closest('.alert').remove();
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>