<?php
// fix-database.php - Add missing columns
require_once 'includes/config.php';


echo "<h2>Fixing Database Columns</h2>";

// Add is_active column to users table
$sql1 = "ALTER TABLE users 
         ADD COLUMN is_active BOOLEAN DEFAULT TRUE";

if (mysqli_query($conn, $sql1)) {
    echo "✅ Added is_active column to users table<br>";
} else {
    echo "⚠️ Column already exists or error: " . mysqli_error($conn) . "<br>";
}

// Add last_login column to users table
$sql2 = "ALTER TABLE users 
         ADD COLUMN last_login TIMESTAMP NULL";

if (mysqli_query($conn, $sql2)) {
    echo "✅ Added last_login column to users table<br>";
} else {
    echo "⚠️ Column already exists or error: " . mysqli_error($conn) . "<br>";
}

// Update sample users to be active
$sql3 = "UPDATE users SET is_active = TRUE";
if (mysqli_query($conn, $sql3)) {
    echo "✅ Updated all users to active<br>";
}

// Create remember_tokens table for "Remember Me" feature
$sql4 = "CREATE TABLE IF NOT EXISTS remember_tokens (
    token_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    token VARCHAR(64) UNIQUE,
    expires_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
)";

if (mysqli_query($conn, $sql4)) {
    echo "✅ Created remember_tokens table<br>";
} else {
    echo "⚠️ Table already exists or error: " . mysqli_error($conn) . "<br>";
}

echo "<h3 style='color: green;'>✅ Database fixes completed!</h3>";
echo "<p><a href='login.php'>Try logging in again</a></p>";

// Delete this file after running
echo "<p style='color: red;'><strong>IMPORTANT:</strong> Delete this file (fix-database.php) after running!</p>";
?>