<?php
require_once 'includes/config.php';

echo "<h2>Adding Payment Status to Commissions</h2>";

// Add payment_status column
$sql1 = "ALTER TABLE commissions 
         ADD COLUMN IF NOT EXISTS payment_status 
         ENUM('pending', 'paid', 'failed') 
         DEFAULT 'pending'";

if (mysqli_query($conn, $sql1)) {
    echo "✅ Payment status column added successfully!<br>";
} else {
    echo "❌ Error: " . mysqli_error($conn) . "<br>";
}

// Add paid_at column
$sql2 = "ALTER TABLE commissions 
         ADD COLUMN IF NOT EXISTS paid_at TIMESTAMP NULL";

if (mysqli_query($conn, $sql2)) {
    echo "✅ Paid at column added successfully!<br>";
} else {
    echo "❌ Error: " . mysqli_error($conn) . "<br>";
}

$sql3 = "ALTER TABLE commissions 
         ADD COLUMN IF NOT EXISTS transaction_id VARCHAR(100) NULL";
mysqli_query($conn, $sql3);

echo "<p><a href='commissions.php'>Go to Commissions</a></p>";
echo "<p style='color:red;'><strong>Delete this file after running!</strong></p>";
?>