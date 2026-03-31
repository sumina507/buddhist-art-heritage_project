<?php
require_once 'includes/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: register.php');
    exit;
}

// Store form data in session for re-filling if needed
$_SESSION['form_data'] = $_POST;

// Get form data
$full_name = trim($_POST['full_name']);
$username = trim($_POST['username']);
$email = trim($_POST['email']);
$password = $_POST['password'];
$confirm_password = $_POST['confirm_password'];
$role = $_POST['role'];

// Basic validation
$errors = [];

// Check required fields
if (empty($full_name)) $errors[] = "Full name is required";
if (empty($username)) $errors[] = "Username is required";
if (empty($email)) $errors[] = "Email is required";
if (empty($password)) $errors[] = "Password is required";

// Check username format (alphanumeric and underscore only)
if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
    $errors[] = "Username can only contain letters, numbers, and underscores";
}

// Check email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Invalid email format";
}

// Check password strength
if (strlen($password) < 8) {
    $errors[] = "Password must be at least 8 characters long";
}
if (!preg_match('/[A-Z]/', $password)) {
    $errors[] = "Password must contain at least one uppercase letter";
}
if (!preg_match('/[a-z]/', $password)) {
    $errors[] = "Password must contain at least one lowercase letter";
}
if (!preg_match('/[0-9]/', $password)) {
    $errors[] = "Password must contain at least one number";
}

// Check password match
if ($password !== $confirm_password) {
    $errors[] = "Passwords do not match";
}

// Check if username already exists
$check_sql = "SELECT user_id FROM users WHERE username = ? OR email = ?";
$check_stmt = mysqli_prepare($conn, $check_sql);
mysqli_stmt_bind_param($check_stmt, "ss", $username, $email);
mysqli_stmt_execute($check_stmt);
$check_result = mysqli_stmt_get_result($check_stmt);

if (mysqli_num_rows($check_result) > 0) {
    $errors[] = "Username or email already exists";
}

// If there are errors, redirect back with errors
if (!empty($errors)) {
    $_SESSION['errors'] = $errors;
    header('Location: register.php' . ($role == 'artist' ? '?role=artist' : ''));
    exit;
}

// Handle profile image upload
$profile_image = 'default.jpg';
if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $file_type = $_FILES['profile_image']['type'];
    
    if (in_array($file_type, $allowed_types)) {
        $file_extension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
        $new_filename = $username . '_' . time() . '.' . $file_extension;
$upload_path = 'uploads/profiles/' . $new_filename;
        
        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
            $profile_image = $new_filename;
        }
    }
}

// Hash password
$password_hash = password_hash($password, PASSWORD_DEFAULT);

// Start transaction
mysqli_begin_transaction($conn);

try {
    // Insert into users table
    $user_sql = "INSERT INTO users (username, email, password_hash, full_name, role, profile_image) 
                 VALUES (?, ?, ?, ?, ?, ?)";
    $user_stmt = mysqli_prepare($conn, $user_sql);
    mysqli_stmt_bind_param($user_stmt, "ssssss", $username, $email, $password_hash, $full_name, $role, $profile_image);
    mysqli_stmt_execute($user_stmt);
    
    $user_id = mysqli_insert_id($conn);
    
    // If registering as artist, insert into artists table
    if ($role == 'artist') {
        $specialization = $_POST['specialization'] ?? '';
        $experience_years = $_POST['experience_years'] ?? 0;
        $bio = $_POST['bio'] ?? '';
        $contact_info = $_POST['contact_info'] ?? '';
        $website = $_POST['website'] ?? '';
        
        $artist_sql = "INSERT INTO artists (user_id, specialization, experience_years, bio, contact_info, website, status) 
                       VALUES (?, ?, ?, ?, ?, ?, 'pending')";
        $artist_stmt = mysqli_prepare($conn, $artist_sql);
        mysqli_stmt_bind_param($artist_stmt, "sissss", $user_id, $specialization, $experience_years, $bio, $contact_info, $website);
        mysqli_stmt_execute($artist_stmt);
    }
    
    // Commit transaction
    mysqli_commit($conn);
    
    // Clear form data from session
    unset($_SESSION['form_data']);
    
    // Set success message
    $_SESSION['message'] = $role == 'artist' 
        ? "Registration successful! Your artist account is pending approval. You'll receive an email when approved."
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