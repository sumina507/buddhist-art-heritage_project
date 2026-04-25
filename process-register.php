<?php
require_once 'includes/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: register.php');
    exit;
}

// Store form data in session for re-filling if needed
$_SESSION['form_data'] = $_POST;

// Get form data
$full_name = trim($_POST['full_name'] ?? '');
$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';
$role = $_POST['role'] ?? 'user';

// Validation rules
$errors = [];

// ===== FULL NAME VALIDATION =====
if (empty($full_name)) {
    $errors[] = "Full name is required";
} elseif (strlen($full_name) < 3) {
    $errors[] = "Full name must be at least 3 characters";
} elseif (strlen($full_name) > 100) {
    $errors[] = "Full name must be less than 100 characters";
}

// ===== USERNAME VALIDATION =====
if (empty($username)) {
    $errors[] = "Username is required";
} elseif (strlen($username) < 4) {
    $errors[] = "Username must be at least 4 characters long";
} elseif (strlen($username) > 30) {
    $errors[] = "Username must be less than 30 characters";
} elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
    $errors[] = "Username can only contain letters, numbers, and underscores";
}

// ===== EMAIL VALIDATION =====
if (empty($email)) {
    $errors[] = "Email is required";
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Please enter a valid email address";
} elseif (strlen($email) > 100) {
    $errors[] = "Email must be less than 100 characters";
}

// ===== PASSWORD VALIDATION =====
if (empty($password)) {
    $errors[] = "Password is required";
} else {
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    }
    if (strlen($password) > 50) {
        $errors[] = "Password must be less than 50 characters";
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter (A-Z)";
    }
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain at least one lowercase letter (a-z)";
    }
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number (0-9)";
    }
}

// ===== CONFIRM PASSWORD VALIDATION =====
if (empty($confirm_password)) {
    $errors[] = "Please confirm your password";
} elseif ($password !== $confirm_password) {
    $errors[] = "Passwords do not match";
}

// ===== ARTIST SPECIFIC VALIDATION =====
if ($role == 'artist') {
    $specialization = trim($_POST['specialization'] ?? '');
    if (empty($specialization)) {
        $errors[] = "Please select your specialization";
    }
    
    $experience_years = intval($_POST['experience_years'] ?? 0);
    if ($experience_years < 0 || $experience_years > 100) {
        $errors[] = "Experience years must be between 0 and 100";
    }
    
    $bio = trim($_POST['bio'] ?? '');
    if (strlen($bio) > 1000) {
        $errors[] = "Bio must be less than 1000 characters";
    }
}

// ===== CHECK IF USERNAME OR EMAIL ALREADY EXISTS =====
$check_sql = "SELECT user_id FROM users WHERE username = ? OR email = ?";
$check_stmt = mysqli_prepare($conn, $check_sql);
mysqli_stmt_bind_param($check_stmt, "ss", $username, $email);
mysqli_stmt_execute($check_stmt);
$check_result = mysqli_stmt_get_result($check_stmt);

if (mysqli_num_rows($check_result) > 0) {
    $errors[] = "Username or email already exists. Please choose another.";
}

// ===== IF THERE ARE ERRORS, REDIRECT BACK =====
if (!empty($errors)) {
    $_SESSION['errors'] = $errors;
    header('Location: register.php' . ($role == 'artist' ? '?role=artist' : ''));
    exit;
}

// ===== HANDLE PROFILE IMAGE UPLOAD =====
$profile_image = 'default.jpg';

if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $file_type = $_FILES['profile_image']['type'];
    $file_size = $_FILES['profile_image']['size'];
    $max_size = 2 * 1024 * 1024; // 2MB
    
    if (!in_array($file_type, $allowed_types)) {
        $_SESSION['errors'] = ["Invalid file type. Please upload JPG, PNG, or GIF images only."];
        header('Location: register.php' . ($role == 'artist' ? '?role=artist' : ''));
        exit;
    }
    
    if ($file_size > $max_size) {
        $_SESSION['errors'] = ["File too large. Maximum size is 2MB."];
        header('Location: register.php' . ($role == 'artist' ? '?role=artist' : ''));
        exit;
    }
    
    $file_extension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
    $new_filename = $username . '_' . time() . '.' . $file_extension;
    $upload_path = 'uploads/profiles/' . $new_filename;
    
    // Create directory if not exists
    if (!file_exists('uploads/profiles')) {
        mkdir('uploads/profiles', 0777, true);
    }
    
    if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
        $profile_image = $new_filename;
    }
}

// ===== HASH PASSWORD =====
$password_hash = password_hash($password, PASSWORD_DEFAULT);

// ===== START TRANSACTION =====
mysqli_begin_transaction($conn);

try {
    // Insert into users table
    $user_sql = "INSERT INTO users (username, email, password_hash, full_name, role, profile_image, created_at) 
                 VALUES (?, ?, ?, ?, ?, ?, NOW())";
    $user_stmt = mysqli_prepare($conn, $user_sql);
    mysqli_stmt_bind_param($user_stmt, "ssssss", $username, $email, $password_hash, $full_name, $role, $profile_image);
    
    if (!mysqli_stmt_execute($user_stmt)) {
        throw new Exception("Failed to create user account: " . mysqli_error($conn));
    }
    
    $user_id = mysqli_insert_id($conn);
    
    // If registering as artist, insert into artists table (ONLY columns that exist)
    if ($role == 'artist') {
        $specialization = trim($_POST['specialization'] ?? '');
        $experience_years = intval($_POST['experience_years'] ?? 0);
        $bio = trim($_POST['bio'] ?? '');
        
        // Match your exact table structure
        $artist_sql = "INSERT INTO artists (user_id, specialization, experience_years, bio, status) 
                       VALUES (?, ?, ?, ?, 'pending')";
        $artist_stmt = mysqli_prepare($conn, $artist_sql);
        mysqli_stmt_bind_param($artist_stmt, "isis", $user_id, $specialization, $experience_years, $bio);
        
        if (!mysqli_stmt_execute($artist_stmt)) {
            throw new Exception("Failed to create artist profile: " . mysqli_error($conn));
        }
    }
    
    // Commit transaction
    mysqli_commit($conn);
    
    // Clear form data from session
    unset($_SESSION['form_data']);
    
    // Set success message
    $_SESSION['message'] = $role == 'artist' 
        ? "Registration successful! Your artist account is pending approval. You'll be notified when approved."
        : "Registration successful! You can now login.";
    $_SESSION['message_type'] = 'success';
    
    // Redirect to login
    header('Location: login.php');
    exit;
    
} catch (Exception $e) {
    // Rollback transaction on error
    mysqli_rollback($conn);
    
    $_SESSION['errors'] = ["Registration failed: " . $e->getMessage()];
    header('Location: register.php' . ($role == 'artist' ? '?role=artist' : ''));
    exit;
}
?>