<?php
require_once 'includes/config.php';

echo "<h2>Testing Rating System</h2>";

// Check if artwork_ratings table exists
$result = mysqli_query($conn, "SHOW TABLES LIKE 'artwork_ratings'");
if (mysqli_num_rows($result) > 0) {
    echo "✅ artwork_ratings table exists<br>";
} else {
    echo "❌ artwork_ratings table MISSING!<br>";
}

// Check if artist_ratings table exists
$result = mysqli_query($conn, "SHOW TABLES LIKE 'artist_ratings'");
if (mysqli_num_rows($result) > 0) {
    echo "✅ artist_ratings table exists<br>";
} else {
    echo "❌ artist_ratings table MISSING!<br>";
}

// Show what's in the artwork_ratings table if it exists
$result = mysqli_query($conn, "SELECT * FROM artwork_ratings");
if ($result && mysqli_num_rows($result) > 0) {
    echo "<h3>Current Ratings:</h3>";
    while ($row = mysqli_fetch_assoc($result)) {
        echo "Artwork ID: " . $row['artwork_id'] . " - Rating: " . $row['rating'] . " - User: " . $row['user_id'] . "<br>";
    }
} else {
    echo "<p>No ratings yet in artwork_ratings table</p>";
}
?>