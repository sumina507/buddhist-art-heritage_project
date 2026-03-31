<?php
require_once 'includes/config.php';

// Get the most recent artist registration
$sql = "SELECT a.*, u.username, u.email 
        FROM artists a 
        JOIN users u ON a.user_id = u.user_id 
        ORDER BY a.artist_id DESC 
        LIMIT 1";
$result = mysqli_query($conn, $sql);
$artist = mysqli_fetch_assoc($result);

echo "<h2>Most Recent Artist Registration</h2>";
echo "<pre>";
print_r($artist);
echo "</pre>";

// Check if specialization column exists and its type
$cols = mysqli_query($conn, "SHOW COLUMNS FROM artists LIKE 'specialization'");
$col_info = mysqli_fetch_assoc($cols);
echo "<h3>Specialization Column Info:</h3>";
echo "<pre>";
print_r($col_info);
echo "</pre>";
?>