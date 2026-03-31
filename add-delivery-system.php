<?php
// add-delivery-system.php
require_once 'includes/config.php';

echo "<h2>Adding Delivery System Columns</h2>";

$columns = [
    "delivery_status ENUM('not_started', 'pending_review', 'approved', 'rejected', 'revision_requested', 'delivered') DEFAULT 'not_started'",
    "delivery_file VARCHAR(255) NULL",
    "final_file VARCHAR(255) NULL",
    "delivered_at TIMESTAMP NULL",
    "client_approved_at TIMESTAMP NULL",
    "revision_notes TEXT NULL",
    "revision_count INT DEFAULT 0",
    "client_feedback TEXT NULL",
    "satisfaction_rating INT NULL"
];

foreach ($columns as $column) {
    $column_name = explode(' ', $column)[0];
    
    // Check if column already exists
    $check = mysqli_query($conn, "SHOW COLUMNS FROM commissions LIKE '$column_name'");
    
    if (mysqli_num_rows($check) == 0) {
        $sql = "ALTER TABLE commissions ADD COLUMN $column";
        if (mysqli_query($conn, $sql)) {
            echo "✅ Added: $column_name<br>";
        } else {
            echo "❌ Error adding $column_name: " . mysqli_error($conn) . "<br>";
        }
    } else {
        echo "⏭️ Column already exists: $column_name<br>";
    }
}

// Create folders
$folders = ['uploads/artworks', 'uploads/previews', 'uploads/final'];
foreach ($folders as $folder) {
    if (!file_exists($folder)) {
        mkdir($folder, 0777, true);
        echo "✅ Created $folder folder<br>";
    } else {
        echo "✅ Folder already exists: $folder<br>";
    }
}

echo "<hr>";
echo "<p style='color:green;'>✅ Delivery system ready!</p>";
echo "<p><a href='commission-details.php?id=1'>Go to Commission Details</a></p>";
?>