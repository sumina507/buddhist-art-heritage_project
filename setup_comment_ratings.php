<?php
// setup_comment_ratings.php - Run this once from your browser
require_once 'includes/config.php';

echo "<h2>Setting up Comment Rating System</h2>";

// Check if column exists and add if not
$check_column = "SHOW COLUMNS FROM artwork_comments LIKE 'sentiment'";
$column_result = mysqli_query($conn, $check_column);

if (mysqli_num_rows($column_result) == 0) {
    $add_column = "ALTER TABLE artwork_comments ADD COLUMN sentiment ENUM('positive', 'negative', 'neutral') DEFAULT 'neutral'";
    if (mysqli_query($conn, $add_column)) {
        echo "✅ Added 'sentiment' column to artwork_comments<br>";
    } else {
        echo "❌ Error adding column: " . mysqli_error($conn) . "<br>";
    }
} else {
    echo "✅ 'sentiment' column already exists<br>";
}

// Check if table exists and create if not
$check_table = "SHOW TABLES LIKE 'comment_ratings'";
$table_result = mysqli_query($conn, $check_table);

if (mysqli_num_rows($table_result) == 0) {
    $create_table = "CREATE TABLE comment_ratings (
        rating_id INT AUTO_INCREMENT PRIMARY KEY,
        comment_id INT NOT NULL,
        user_id INT NOT NULL,
        sentiment ENUM('positive', 'negative') NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_rating (comment_id, user_id)
    )";
    
    if (mysqli_query($conn, $create_table)) {
        echo "✅ Created 'comment_ratings' table<br>";
        
        // Add foreign keys after table creation
        $add_fk1 = "ALTER TABLE comment_ratings ADD FOREIGN KEY (comment_id) REFERENCES artwork_comments(comment_id) ON DELETE CASCADE";
        mysqli_query($conn, $add_fk1);
        
        $add_fk2 = "ALTER TABLE comment_ratings ADD FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE";
        mysqli_query($conn, $add_fk2);
        
        echo "✅ Added foreign key constraints<br>";
    } else {
        echo "❌ Error creating table: " . mysqli_error($conn) . "<br>";
    }
} else {
    echo "✅ 'comment_ratings' table already exists<br>";
}

echo "<hr>";
echo "<p><strong>Setup complete!</strong></p>";
echo "<p><a href='artwork-detail.php?id=1'>Go to artwork detail page and test comment ratings</a></p>";
echo "<p style='color:red;'><strong>⚠️ Delete this file after running!</strong></p>";
?>