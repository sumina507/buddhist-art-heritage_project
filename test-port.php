<?php
echo "<h2>Testing MySQL Connection on Port 3307</h2>";

// Try connecting with port
$conn = @mysqli_connect('localhost:3307', 'root', '');

if ($conn) {
    echo "✅ Connected successfully on port 3307!<br>";
    
    // Check if database exists
    $db_selected = mysqli_select_db($conn, 'buddhist_art_db');
    if ($db_selected) {
        echo "✅ Database 'buddhist_art_db' found!<br>";
        
        // Show tables
        $tables = mysqli_query($conn, "SHOW TABLES");
        echo "📊 Tables in database:<br>";
        echo "<ul>";
        while ($table = mysqli_fetch_array($tables)) {
            echo "<li>" . $table[0] . "</li>";
        }
        echo "</ul>";
    } else {
        echo "❌ Database 'buddhist_art_db' not found: " . mysqli_error($conn) . "<br>";
    }
    
    mysqli_close($conn);
} else {
    echo "❌ Connection failed on port 3307: " . mysqli_connect_error() . "<br>";
    
    // Try without port as fallback
    echo "<h3>Trying without port...</h3>";
    $conn2 = @mysqli_connect('localhost', 'root', '');
    if ($conn2) {
        echo "✅ Connected on default port!<br>";
    } else {
        echo "❌ Also failed: " . mysqli_connect_error() . "<br>";
    }
}
?>