<?php
// test-commission.php - Run this once to test
require_once 'includes/config.php';

echo "<h2>Testing Commission System</h2>";

// Check if tables exist
$tables = ['commissions', 'artists', 'users'];
foreach ($tables as $table) {
    $result = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
    if (mysqli_num_rows($result) > 0) {
        echo "✅ Table '$table' exists<br>";
    } else {
        echo "❌ Table '$table' missing! Run setup-database.php<br>";
    }
}

// Check if there's at least one approved artist
$sql = "SELECT COUNT(*) as count FROM artists WHERE status = 'approved'";
$result = mysqli_query($conn, $sql);
$data = mysqli_fetch_assoc($result);
if ($data['count'] > 0) {
    echo "✅ Found {$data['count']} approved artists<br>";
} else {
    echo "⚠️ No approved artists yet. Create one in admin panel.<br>";
}

// Check if commission form exists
if (file_exists('commission-request.php')) {
    echo "✅ commission-request.php exists<br>";
} else {
    echo "❌ commission-request.php missing!<br>";
}

echo "<hr>";
echo "<h3>Next Steps:</h3>";
echo "<ol>";
echo "<li><a href='commission-request.php'>Try creating a commission request</a></li>";
echo "<li>Login as artist to accept it</li>";
echo "<li>Check <a href='commissions.php'>commissions.php</a> to see status</li>";
echo "</ol>";
?>