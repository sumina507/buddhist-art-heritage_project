<?php
require_once 'includes/config.php';

echo "<h2>Fixing Database Tables</h2>";

// 1. Add artist_id to users if missing (for session)
$sql = "ALTER TABLE users ADD COLUMN IF NOT EXISTS artist_id INT NULL";
mysqli_query($conn, $sql);

// 2. Create artwork_views table if missing
$sql = "CREATE TABLE IF NOT EXISTS artwork_views (
    view_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    artwork_id INT,
    viewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (artwork_id) REFERENCES artworks(artwork_id) ON DELETE CASCADE
)";
mysqli_query($conn, $sql) ? print("✅ artwork_views table created<br>") : print("❌ Error: " . mysqli_error($conn) . "<br>");

// 3. Create article_views table if missing
$sql = "CREATE TABLE IF NOT EXISTS article_views (
    view_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    article_id INT,
    viewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (article_id) REFERENCES knowledge_articles(article_id) ON DELETE CASCADE
)";
mysqli_query($conn, $sql) ? print("✅ article_views table created<br>") : print("❌ Error: " . mysqli_error($conn) . "<br>");

echo "<p style='color:green; font-weight:bold;'>✅ Database fixes completed! <a href='login.php'>Go to Login</a></p>";
echo "<p style='color:red;'><strong>IMPORTANT:</strong> Delete this file after running!</p>";
?>