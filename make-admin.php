<?php
require_once 'includes/config.php';

echo "<h1>Quick Admin Creator</h1>";

// Check connection
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Create admin user
$username = 'admin';
$password = 'admin123';
$email = 'admin@buddhistart.com';
$full_name = 'Administrator';
$hash = password_hash($password, PASSWORD_DEFAULT);

// Check if admin exists
$check = mysqli_query($conn, "SELECT user_id FROM users WHERE username = 'admin'");

if (mysqli_num_rows($check) == 0) {
    $sql = "INSERT INTO users (username, email, password_hash, full_name, role) 
            VALUES ('$username', '$email', '$hash', '$full_name', 'admin')";
    
    if (mysqli_query($conn, $sql)) {
        echo "<p style='color:green'>✅ Admin created successfully!</p>";
    } else {
        echo "<p style='color:red'>❌ Error: " . mysqli_error($conn) . "</p>";
    }
} else {
    echo "<p style='color:orange'>Admin already exists. Resetting password...</p>";
    
    // Reset password
    $sql = "UPDATE users SET password_hash = '$hash' WHERE username = 'admin'";
    mysqli_query($conn, $sql);
    echo "<p style='color:green'>✅ Password reset to 'admin123'</p>";
}

// Approve all artists
$approve = "UPDATE artists SET status = 'approved' WHERE status = 'pending' OR status IS NULL";
if (mysqli_query($conn, $approve)) {
    echo "<p style='color:green'>✅ Approved all pending artists</p>";
}

echo "<hr>";
echo "<h3>Login Credentials:</h3>";
echo "Admin: admin / admin123<br>";
echo "<br>";
echo "<a href='login.php'>Go to Login Page</a>";
?>