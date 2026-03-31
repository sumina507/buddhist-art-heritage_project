<?php
require_once 'includes/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

$username = trim($_POST['username']);
$password = $_POST['password'];
$remember = isset($_POST['remember']) ? true : false;

// Validate input
if (empty($username) || empty($password)) {
    $_SESSION['errors'] = ["Please enter both username and password"];
    header('Location: login.php');
    exit;
}

// Check if user exists (by username or email)
$sql = "SELECT u.*, a.status as artist_status, a.artist_id 
        FROM users u 
        LEFT JOIN artists a ON u.user_id = a.user_id 
        WHERE u.username = ? OR u.email = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ss", $username, $username);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    $_SESSION['errors'] = ["Invalid username or password"];
    header('Location: login.php');
    exit;
}

$user = mysqli_fetch_assoc($result);

// Verify password
if (!password_verify($password, $user['password_hash'])) {
    $_SESSION['errors'] = ["Invalid username or password"];
    header('Location: login.php');
    exit;
}

// Check if artist account is approved
if ($user['role'] == 'artist' && isset($user['artist_status']) && $user['artist_status'] != 'approved') {
    $_SESSION['errors'] = ["Your artist account is pending approval. Please wait for admin approval."];
    header('Location: login.php');
    exit;
}

// Check if account is active (only if column exists)
if (isset($user['is_active']) && $user['is_active'] == 0) {
    $_SESSION['errors'] = ["Your account has been deactivated. Please contact admin."];
    header('Location: login.php');
    exit;
}

// Set session variables
$_SESSION['user_id'] = $user['user_id'];
$_SESSION['username'] = $user['username'];
$_SESSION['email'] = $user['email'];
$_SESSION['role'] = $user['role'];
$_SESSION['full_name'] = $user['full_name'];
$_SESSION['artist_id'] = $user['artist_id'] ?? null;

// ADD THIS BLOCK - Fix for artists missing artist_id
if ($user['role'] == 'artist' && (!isset($_SESSION['artist_id']) || !$_SESSION['artist_id'])) {
    // Try to get artist_id from artists table
    $get_artist = "SELECT artist_id FROM artists WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $get_artist);
    mysqli_stmt_bind_param($stmt, "i", $user['user_id']);
    mysqli_stmt_execute($stmt);
    $artist_result = mysqli_stmt_get_result($stmt);
    $artist_data = mysqli_fetch_assoc($artist_result);
    
    if ($artist_data) {
        $_SESSION['artist_id'] = $artist_data['artist_id'];
    } else {
        // If no artist record exists, create one
        $insert_artist = "INSERT INTO artists (user_id, status) VALUES (?, 'approved')";
        $stmt = mysqli_prepare($conn, $insert_artist);
        mysqli_stmt_bind_param($stmt, "i", $user['user_id']);
        mysqli_stmt_execute($stmt);
        $_SESSION['artist_id'] = mysqli_insert_id($conn);
    }
}
// If "Remember me" is checked, set cookie
if ($remember) {
    $token = bin2hex(random_bytes(32));
    $expiry = time() + (30 * 24 * 60 * 60); // 30 days
    
    // Store token in database (only if table exists)
    $token_sql = "INSERT INTO remember_tokens (user_id, token, expires_at) VALUES (?, ?, ?)";
    $token_stmt = mysqli_prepare($conn, $token_sql);
    $expiry_date = date('Y-m-d H:i:s', $expiry);
    
    if ($token_stmt) {
        mysqli_stmt_bind_param($token_stmt, "iss", $user['user_id'], $token, $expiry_date);
        mysqli_stmt_execute($token_stmt);
        
        // Set cookie
        setcookie('remember_token', $token, $expiry, '/');
    }
}

// Update last login time (only if column exists)
if (isset($user['last_login'])) {
    $update_sql = "UPDATE users SET last_login = NOW() WHERE user_id = ?";
    $update_stmt = mysqli_prepare($conn, $update_sql);
    mysqli_stmt_bind_param($update_stmt, "i", $user['user_id']);
    mysqli_stmt_execute($update_stmt);
}
// Redirect based on role
switch ($user['role']) {
    case 'admin':
        error_log("Redirecting admin to admin/index.php");
        header('Location: admin/index.php');
        break;
    case 'artist':
        error_log("Redirecting artist to artist-dashboard.php");
        header('Location: artist-dashboard.php');     
        break;
    default:
        error_log("Redirecting user to dashboard.php");
        header('Location: dashboard.php');
        break;
}
exit;
?>