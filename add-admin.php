<?php
require_once 'includes/config.php';

// Hash the password 'admin123'
$password = 'admin123';
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Check if admin already exists
$check = "SELECT user_id FROM users WHERE username = 'admin'";
$result = mysqli_query($conn, $check);

if (mysqli_num_rows($result) > 0) {
    // Update existing admin password
    $sql = "UPDATE users SET password_hash = ? WHERE username = 'admin'";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $hashed_password);
    
    if (mysqli_stmt_execute($stmt)) {
        echo "✅ Admin password updated!<br>";
        echo "Username: admin<br>";
        echo "Password: admin123<br>";
    } else {
        echo "❌ Error updating: " . mysqli_error($conn) . "<br>";
    }
} else {
    // Insert new admin
    $sql = "INSERT INTO users (username, email, password_hash, full_name, role) 
            VALUES ('admin', 'admin@buddhistart.com', ?, 'Administrator', 'admin')";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $hashed_password);
    
    if (mysqli_stmt_execute($stmt)) {
        echo "✅ Admin user created!<br>";
        echo "Username: admin<br>";
        echo "Password: admin123<br>";
    } else {
        echo "❌ Error creating: " . mysqli_error($conn) . "<br>";
    }
}

echo "<hr>";
echo "<a href='login.php'>Go to Login</a>";
?>