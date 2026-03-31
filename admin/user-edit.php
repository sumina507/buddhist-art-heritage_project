<?php
require_once '../includes/config.php';
// Check admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit;
}

$user_id = $_GET['id'] ?? 0;
$page_title = "Edit User";

// Get user data
$sql = "SELECT * FROM users WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

if (!$user) {
    $_SESSION['message'] = "User not found!";
    $_SESSION['message_type'] = 'error';
    header('Location: users.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    // Remove is_active since column doesn't exist
    
    // Check if username already exists (excluding current user)
    $check_sql = "SELECT user_id FROM users WHERE username = ? AND user_id != ?";
    $check_stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($check_stmt, "si", $username, $user_id);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    
    if (mysqli_num_rows($check_result) > 0) {
        $_SESSION['message'] = "Username already exists!";
        $_SESSION['message_type'] = 'error';
    } else {
        // Check if email already exists (excluding current user)
        $check_sql = "SELECT user_id FROM users WHERE email = ? AND user_id != ?";
        $check_stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($check_stmt, "si", $email, $user_id);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);
        
        if (mysqli_num_rows($check_result) > 0) {
            $_SESSION['message'] = "Email already exists!";
            $_SESSION['message_type'] = 'error';
        } else {
            // Handle profile image upload
            $profile_image = $user['profile_image'];
            if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
                $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                $file_type = $_FILES['profile_image']['type'];
                
                if (in_array($file_type, $allowed_types)) {
                    // Delete old image if not default
                    if ($profile_image != 'default.jpg' && file_exists(PROFILE_UPLOAD_PATH . $profile_image)) {
                        unlink(PROFILE_UPLOAD_PATH . $profile_image);
                    }
                    
                    $file_extension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
                    $new_filename = $username . '_' . time() . '.' . $file_extension;
                    $upload_path = PROFILE_UPLOAD_PATH . $new_filename;
                    
                    if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
                        $profile_image = $new_filename;
                    }
                }
            }
            
            // Handle password update
            $password_update = '';
            $params = [];
            $types = '';
            
            if (!empty($_POST['password'])) {
                $password = $_POST['password'];
                if (strlen($password) >= 8) {
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    $password_update = ", password_hash = ?";
                    $params[] = $password_hash;
                    $types .= 's';
                } else {
                    $_SESSION['message'] = "Password must be at least 8 characters!";
                    $_SESSION['message_type'] = 'error';
                    header("Location: user-edit.php?id=$user_id");
                    exit;
                }
            }
            
            // Update user (without is_active)
            $sql = "UPDATE users SET 
                    username = ?, 
                    email = ?, 
                    full_name = ?, 
                    role = ?, 
                    profile_image = ?
                    $password_update 
                    WHERE user_id = ?";
            
            $params = array_merge([$username, $email, $full_name, $role, $profile_image], $params);
            $params[] = $user_id;
            $types = 'sssss' . $types . 'i';
            
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, $types, ...$params);
            
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['message'] = "User updated successfully!";
                $_SESSION['message_type'] = 'success';
                header('Location: users.php');
                exit;
            } else {
                $_SESSION['message'] = "Error updating user: " . mysqli_error($conn);
                $_SESSION['message_type'] = 'error';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Admin Panel</title>
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <main class="admin-main">
        <div class="admin-topbar">
            <div class="topbar-left">
                <button class="mobile-menu-btn" id="mobileMenuBtn">
                    <i class="fas fa-bars"></i>
                </button>
                <h2>Edit User: <?php echo htmlspecialchars($user['username']); ?></h2>
            </div>
            <div class="topbar-right">
                <a href="users.php" class="btn-small" style="background: var(--info-color); color: white; padding: 0.5rem 1rem; border-radius: 5px; text-decoration: none;">
                    <i class="fas fa-arrow-left"></i> Back to Users
                </a>
            </div>
        </div>
        
        <div class="admin-content">
            <!-- Messages -->
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-<?php echo $_SESSION['message_type']; ?>">
                    <?php 
                    echo $_SESSION['message'];
                    unset($_SESSION['message']);
                    unset($_SESSION['message_type']);
                    ?>
                    <button class="alert-close">&times;</button>
                </div>
            <?php endif; ?>
            
            <!-- Edit Form -->
            <div class="form-container">
                <div class="user-preview">
                    <div class="profile-image-large">
                        <img src="../uploads/profiles/<?php echo htmlspecialchars($user['profile_image']); ?>" 
                             alt="<?php echo htmlspecialchars($user['username']); ?>">
                    </div>
                    <div class="preview-info">
                        <h4><?php echo htmlspecialchars($user['full_name'] ?? $user['username']); ?></h4>
                        <p><i class="fas fa-user"></i> <?php echo htmlspecialchars($user['username']); ?></p>
                        <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user['email']); ?></p>
                        <p><i class="fas fa-calendar"></i> Joined: <?php echo date('M d, Y', strtotime($user['created_at'])); ?></p>
                        <p><i class="fas fa-clock"></i> Last Login: <?php echo isset($user['last_login']) ? date('M d, Y g:i A', strtotime($user['last_login'])) : 'Never'; ?></p>
                    </div>
                </div>
                
                <form method="POST" enctype="multipart/form-data" class="admin-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="username"><i class="fas fa-user"></i> Username *</label>
                            <input type="text" id="username" name="username" required 
                                   value="<?php echo htmlspecialchars($user['username']); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="email"><i class="fas fa-envelope"></i> Email *</label>
                            <input type="email" id="email" name="email" required 
                                   value="<?php echo htmlspecialchars($user['email']); ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="full_name"><i class="fas fa-id-card"></i> Full Name</label>
                        <input type="text" id="full_name" name="full_name" 
                               value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="role"><i class="fas fa-user-tag"></i> Role *</label>
                        <select id="role" name="role" required>
                            <option value="user" <?php echo $user['role'] == 'user' ? 'selected' : ''; ?>>Regular User</option>
                            <option value="artist" <?php echo $user['role'] == 'artist' ? 'selected' : ''; ?>>Artist</option>
                            <option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="profile_image"><i class="fas fa-image"></i> Profile Picture</label>
                        <input type="file" id="profile_image" name="profile_image" accept="image/*" onchange="previewImage(event)">
                        <small>Leave empty to keep current image</small>
                        <div class="image-preview" id="imagePreview">
                            <div class="preview-text">No new image selected</div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="password"><i class="fas fa-lock"></i> New Password</label>
                        <div class="password-wrapper">
                            <input type="password" id="password" name="password" 
                                   placeholder="Leave empty to keep current password">
                            <button type="button" class="toggle-password" data-target="password">Show</button>
                        </div>
                        <small>Minimum 8 characters. Leave empty to keep current password.</small>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                        <a href="users.php" class="btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </main>
    
    <style>
    /* Form Container */
    .form-container {
        background: white;
        padding: 2rem;
        border-radius: 10px;
        box-shadow: 0 3px 15px rgba(0,0,0,0.08);
    }
    
    /* User Preview */
    .user-preview {
        display: flex;
        gap: 2rem;
        margin-bottom: 2rem;
        padding-bottom: 2rem;
        border-bottom: 1px solid #eee;
        align-items: center;
    }
    
    .profile-image-large {
        width: 120px;
        height: 120px;
    }
    
    .profile-image-large img {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid var(--accent-color);
    }
    
    .preview-info h4 {
        color: var(--primary-color);
        margin-bottom: 0.5rem;
        font-size: 1.3rem;
    }
    
    .preview-info p {
        color: #666;
        margin-bottom: 0.3rem;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .preview-info i {
        width: 20px;
        color: var(--secondary-color);
    }
    
    /* Form Styles */
    .admin-form {
        max-width: 600px;
        margin: 0 auto;
    }
    
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.5rem;
        margin-bottom: 1.5rem;
    }
    
    .form-group {
        margin-bottom: 1.5rem;
    }
    
    .form-group label {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 0.5rem;
        font-weight: 600;
        color: var(--primary-color);
    }
    
    .form-group input,
    .form-group select {
        width: 100%;
        padding: 0.8rem;
        border: 2px solid #e9ecef;
        border-radius: 8px;
        font-size: 1rem;
        transition: border-color 0.3s;
    }
    
    .form-group input:focus,
    .form-group select:focus {
        border-color: var(--secondary-color);
        outline: none;
    }
    
    /* Password Wrapper */
    .password-wrapper {
        position: relative;
    }
    
    .toggle-password {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        border: none;
        background: none;
        cursor: pointer;
        font-size: 0.8rem;
        color: #777;
    }
    
    /* Image Preview */
    .image-preview {
        margin-top: 1rem;
        width: 150px;
        height: 150px;
        border: 2px dashed #ddd;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }
    
    .image-preview img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .preview-text {
        color: #999;
        text-align: center;
        padding: 1rem;
    }
    
    small {
        color: #666;
        font-size: 0.85rem;
        display: block;
        margin-top: 0.5rem;
    }
    
    /* Form Actions */
    .form-actions {
        display: flex;
        gap: 1rem;
        margin-top: 2rem;
        padding-top: 2rem;
        border-top: 1px solid #eee;
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
    }
    
    .btn-primary {
        background: var(--success-color);
        color: white;
        border: none;
        cursor: pointer;
    }
    
    .btn-primary:hover {
        background: #219653;
        transform: translateY(-2px);
    }
    
    .btn-secondary {
        background: #f8f9fa;
        color: #333;
        border: 2px solid #ddd;
    }
    
    .btn-secondary:hover {
        background: #e9ecef;
        transform: translateY(-2px);
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .user-preview {
            flex-direction: column;
            text-align: center;
        }
        
        .form-row {
            grid-template-columns: 1fr;
            gap: 1rem;
        }
        
        .form-container {
            padding: 1.5rem;
        }
    }
    </style>
    
    <script>
    // Mobile menu toggle
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const sidebar = document.querySelector('.admin-sidebar');
    
    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', () => {
            sidebar.classList.toggle('active');
        });
    }
    
    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', (e) => {
        if (window.innerWidth <= 992 && 
            sidebar && 
            !sidebar.contains(e.target) && 
            !mobileMenuBtn.contains(e.target)) {
            sidebar.classList.remove('active');
        }
    });
    
    // Toggle password visibility
    document.querySelectorAll('.toggle-password').forEach(btn => {
        btn.addEventListener('click', function() {
            const input = document.getElementById(this.dataset.target);
            const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
            input.setAttribute('type', type);
            this.textContent = type === 'password' ? 'Show' : 'Hide';
        });
    });
    
    // Image preview
    function previewImage(event) {
        const preview = document.getElementById('imagePreview');
        const file = event.target.files[0];
        
        if (file) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                preview.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
            }
            
            reader.readAsDataURL(file);
        } else {
            preview.innerHTML = '<div class="preview-text">No new image selected</div>';
        }
    }
    
    // Alert close
    document.querySelectorAll('.alert-close').forEach(btn => {
        btn.addEventListener('click', function() {
            this.closest('.alert').style.display = 'none';
        });
    });
    </script>
</body>
</html>