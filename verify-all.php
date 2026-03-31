<?php
// verify-all.php
require_once 'includes/config.php';

echo "<h2>System Verification</h2>";

// Check database tables
$tables = ['users', 'artists', 'artworks', 'commissions', 'artwork_likes', 'knowledge_articles'];
foreach ($tables as $table) {
    $result = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
    echo mysqli_num_rows($result) > 0 ? "✅ $table<br>" : "❌ $table MISSING<br>";
}

// Check upload folders
$folders = ['uploads/profiles/', 'uploads/artworks/', 'uploads/previews/', 'uploads/final/'];
foreach ($folders as $folder) {
    echo file_exists($folder) ? "✅ $folder<br>" : "❌ $folder MISSING<br>";
}

// Check admin exists
$admin = mysqli_query($conn, "SELECT * FROM users WHERE username='admin'");
echo mysqli_num_rows($admin) > 0 ? "✅ Admin user exists<br>" : "❌ Admin missing. Run make-admin.php<br>";

echo "<hr><a href='index.php'>Go to Homepage</a>";
?>