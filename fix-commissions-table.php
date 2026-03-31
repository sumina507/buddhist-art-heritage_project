<?php
require_once 'includes/config.php';

echo "<h2>Fixing Commissions Table</h2>";

// Check if table exists
$check = mysqli_query($conn, "SHOW TABLES LIKE 'commissions'");
if (mysqli_num_rows($check) == 0) {
    // Create table
    $sql = "CREATE TABLE commissions (
        commission_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        artist_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT NOT NULL,
        budget DECIMAL(10,2),
        deadline DATE,
        status ENUM('pending', 'accepted', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
        FOREIGN KEY (artist_id) REFERENCES artists(artist_id) ON DELETE CASCADE
    )";
    
    if (mysqli_query($conn, $sql)) {
        echo "✅ Commissions table created successfully<br>";
    } else {
        echo "❌ Error: " . mysqli_error($conn) . "<br>";
    }
} else {
    echo "✅ Commissions table exists<br>";
    
    // Check if all columns exist
    $columns = ['commission_id', 'user_id', 'artist_id', 'title', 'description', 'budget', 'deadline', 'status', 'created_at', 'updated_at'];
    $missing = [];
    
    foreach ($columns as $column) {
        $check_col = mysqli_query($conn, "SHOW COLUMNS FROM commissions LIKE '$column'");
        if (mysqli_num_rows($check_col) == 0) {
            $missing[] = $column;
        }
    }
    
    if (!empty($missing)) {
        echo "⚠️ Missing columns: " . implode(', ', $missing) . "<br>";
        
        // Add missing columns
        if (in_array('updated_at', $missing)) {
            mysqli_query($conn, "ALTER TABLE commissions ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
            echo "✅ Added updated_at column<br>";
        }
    } else {
        echo "✅ All columns exist<br>";
    }
}

// Check for test data
$count = mysqli_query($conn, "SELECT COUNT(*) as total FROM commissions");
$data = mysqli_fetch_assoc($count);
echo "<p>Total commissions: " . $data['total'] . "</p>";

echo "<hr>";
echo "<p><a href='commission-request.php'>Try creating a commission</a></p>";
echo "<p style='color:red;'><strong>Delete this file after running!</strong></p>";
?>