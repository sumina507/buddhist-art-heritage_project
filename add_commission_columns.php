<?php
require_once 'includes/config.php';

echo "<h2>Adding Missing Commission Columns</h2>";

$queries = [
    "ALTER TABLE commissions ADD COLUMN IF NOT EXISTS preview_file VARCHAR(255) NULL",
    "ALTER TABLE commissions ADD COLUMN IF NOT EXISTS preview_uploaded TINYINT DEFAULT 0",
    "ALTER TABLE commissions ADD COLUMN IF NOT EXISTS preview_approved TINYINT DEFAULT 0",
    "ALTER TABLE commissions ADD COLUMN IF NOT EXISTS final_file VARCHAR(255) NULL",
    "ALTER TABLE commissions ADD COLUMN IF NOT EXISTS final_uploaded TINYINT DEFAULT 0"
];

foreach ($queries as $sql) {
    if (mysqli_query($conn, $sql)) {
        echo "✅ Executed: " . substr($sql, 0, 50) . "...<br>";
    } else {
        echo "❌ Error: " . mysqli_error($conn) . "<br>";
    }
}

echo "<hr>";
echo "<p><a href='commission-details.php?id=1'>Go to Commission Details</a></p>";
echo "<p style='color:red;'><strong>Delete this file after running!</strong></p>";
?>