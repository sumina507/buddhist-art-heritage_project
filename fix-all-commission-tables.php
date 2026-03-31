<?php
require_once 'includes/config.php';

echo "<h2>🔧 Fixing All Commission Tables</h2>";

// ===== 1. CHECK IF COMMISSIONS TABLE HAS ALL COLUMNS =====
echo "<h3>1. Checking Commissions Table...</h3>";

// Add payment_status column if missing
$check = mysqli_query($conn, "SHOW COLUMNS FROM commissions LIKE 'payment_status'");
if (mysqli_num_rows($check) == 0) {
    $sql = "ALTER TABLE commissions ADD COLUMN payment_status ENUM('pending', 'paid', 'failed') DEFAULT 'pending'";
    if (mysqli_query($conn, $sql)) {
        echo "✅ Added payment_status column<br>";
    } else {
        echo "❌ Error adding payment_status: " . mysqli_error($conn) . "<br>";
    }
} else {
    echo "✅ payment_status column exists<br>";
}

// Add paid_at column if missing
$check = mysqli_query($conn, "SHOW COLUMNS FROM commissions LIKE 'paid_at'");
if (mysqli_num_rows($check) == 0) {
    $sql = "ALTER TABLE commissions ADD COLUMN paid_at TIMESTAMP NULL";
    if (mysqli_query($conn, $sql)) {
        echo "✅ Added paid_at column<br>";
    } else {
        echo "❌ Error adding paid_at: " . mysqli_error($conn) . "<br>";
    }
} else {
    echo "✅ paid_at column exists<br>";
}

// Add transaction_id column if missing
$check = mysqli_query($conn, "SHOW COLUMNS FROM commissions LIKE 'transaction_id'");
if (mysqli_num_rows($check) == 0) {
    $sql = "ALTER TABLE commissions ADD COLUMN transaction_id VARCHAR(100) NULL";
    if (mysqli_query($conn, $sql)) {
        echo "✅ Added transaction_id column<br>";
    } else {
        echo "❌ Error adding transaction_id: " . mysqli_error($conn) . "<br>";
    }
} else {
    echo "✅ transaction_id column exists<br>";
}

// ===== 2. CREATE COMMISSION MESSAGES TABLE =====
echo "<h3>2. Creating Commission Messages Table...</h3>";

$check = mysqli_query($conn, "SHOW TABLES LIKE 'commission_messages'");
if (mysqli_num_rows($check) == 0) {
    $sql = "CREATE TABLE commission_messages (
        message_id INT AUTO_INCREMENT PRIMARY KEY,
        commission_id INT NOT NULL,
        user_id INT NOT NULL,
        message TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (commission_id) REFERENCES commissions(commission_id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
    )";
    
    if (mysqli_query($conn, $sql)) {
        echo "✅ Commission messages table created successfully!<br>";
    } else {
        echo "❌ Error: " . mysqli_error($conn) . "<br>";
    }
} else {
    echo "✅ Commission messages table already exists<br>";
}

// ===== 3. ADD SAMPLE DATA FOR TESTING =====
echo "<h3>3. Adding Sample Data for Testing...</h3>";

// Check if we have any commissions
$check = mysqli_query($conn, "SELECT COUNT(*) as total FROM commissions");
$data = mysqli_fetch_assoc($check);

if ($data['total'] == 0) {
    // Get first artist and user for sample data
    $artist = mysqli_query($conn, "SELECT artist_id FROM artists LIMIT 1");
    $artist_data = mysqli_fetch_assoc($artist);
    
    $user = mysqli_query($conn, "SELECT user_id FROM users WHERE role = 'user' LIMIT 1");
    $user_data = mysqli_fetch_assoc($user);
    
    if ($artist_data && $user_data) {
        $sql = "INSERT INTO commissions (user_id, artist_id, title, description, budget, status, created_at) 
                VALUES (?, ?, 'Sample Commission', 'This is a test commission request', 5000, 'pending', NOW())";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $user_data['user_id'], $artist_data['artist_id']);
        
        if (mysqli_stmt_execute($stmt)) {
            echo "✅ Sample commission added for testing<br>";
        }
    }
}

// ===== 4. SUMMARY =====
echo "<hr>";
echo "<h3 style='color: green;'>✅ All fixes completed!</h3>";
echo "<p>Now you can:</p>";
echo "<ul>";
echo "<li><a href='commission-request.php'>Create a new commission request</a></li>";
echo "<li><a href='commissions.php'>View your commissions</a></li>";
echo "<li><a href='artist-dashboard.php'>Check artist dashboard</a></li>";
echo "</ul>";
echo "<p style='color: red;'><strong>⚠️ DELETE THIS FILE AFTER RUNNING!</strong></p>";
?>