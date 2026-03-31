<?php
require_once 'includes/config.php';

echo "<h2>Checking Commissions Table Structure</h2>";

// Check all columns
$result = mysqli_query($conn, "DESCRIBE commissions");
echo "<table border='1' cellpadding='5' cellspacing='0'>";
echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";

while ($row = mysqli_fetch_assoc($result)) {
    echo "<tr>";
    echo "<td>" . $row['Field'] . "</td>";
    echo "<td>" . $row['Type'] . "</td>";
    echo "<td>" . $row['Null'] . "</td>";
    echo "<td>" . $row['Key'] . "</td>";
    echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
    echo "</tr>";
}
echo "</table>";

// Check delivery columns specifically
echo "<h3>Delivery Columns Check:</h3>";
$delivery_columns = ['delivery_status', 'delivery_file', 'final_file', 'delivered_at', 'client_approved_at', 'revision_notes', 'revision_count', 'client_feedback', 'satisfaction_rating'];

foreach ($delivery_columns as $col) {
    $check = mysqli_query($conn, "SHOW COLUMNS FROM commissions LIKE '$col'");
    if (mysqli_num_rows($check) > 0) {
        echo "✅ $col exists<br>";
    } else {
        echo "❌ $col MISSING!<br>";
    }
}
?>