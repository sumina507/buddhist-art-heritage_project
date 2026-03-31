<?php
require_once 'includes/config.php';

echo "<h1>Creating Rating Tables</h1>";

// Create artist_ratings table
$sql1 = "CREATE TABLE IF NOT EXISTS artist_ratings (
    rating_id INT AUTO_INCREMENT PRIMARY KEY,
    artist_id INT NOT NULL,
    user_id INT NOT NULL,
    rating INT CHECK (rating >= 1 AND rating <= 5),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_artist_user (artist_id, user_id),
    FOREIGN KEY (artist_id) REFERENCES artists(artist_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
)";

if (mysqli_query($conn, $sql1)) {
    echo "✅ Artist ratings table created successfully<br>";
} else {
    echo "❌ Error creating artist ratings table: " . mysqli_error($conn) . "<br>";
}

// Create artwork_ratings table
$sql2 = "CREATE TABLE IF NOT EXISTS artwork_ratings (
    rating_id INT AUTO_INCREMENT PRIMARY KEY,
    artwork_id INT NOT NULL,
    user_id INT NOT NULL,
    rating INT CHECK (rating >= 1 AND rating <= 5),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_artwork_user (artwork_id, user_id),
    FOREIGN KEY (artwork_id) REFERENCES artworks(artwork_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
)";

if (mysqli_query($conn, $sql2)) {
    echo "✅ Artwork ratings table created successfully<br>";
} else {
    echo "❌ Error creating artwork ratings table: " . mysqli_error($conn) . "<br>";
}

// Check if tables were created
$check1 = mysqli_query($conn, "SHOW TABLES LIKE 'artist_ratings'");
if (mysqli_num_rows($check1) > 0) {
    echo "<p style='color:green'>✓ artist_ratings table exists</p>";
}

$check2 = mysqli_query($conn, "SHOW TABLES LIKE 'artwork_ratings'");
if (mysqli_num_rows($check2) > 0) {
    echo "<p style='color:green'>✓ artwork_ratings table exists</p>";
}

echo "<hr>";
echo "<p><a href='artist-profile.php?id=1'>Go to Artist Profile</a></p>";
echo "<p><strong>Note:</strong> Delete this file after running!</p>";
?>