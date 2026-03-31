<?php
// setup-database.php - Run this ONCE to create all tables
echo "<h2>Setting up Buddhist Art Database</h2>";

// Database configuration
$host = 'localhost';
$user = 'root';
$pass = 'Sumina2005@@';
$dbname = 'buddhist_art_db';

// Create connection
$conn = new mysqli($host, $user, $pass);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "1. Connected to MySQL server successfully<br>";

// Create database if not exists
$sql = "CREATE DATABASE IF NOT EXISTS $dbname";
if ($conn->query($sql) === TRUE) {
    echo "2. Database '$dbname' created or already exists<br>";
} else {
    echo "Error creating database: " . $conn->error . "<br>";
}

// Select database
$conn->select_db($dbname);

// ========== CREATE TABLES ==========

// 1. Users Table
$sql = "CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    role ENUM('user', 'artist', 'admin') DEFAULT 'user',
    profile_image VARCHAR(255) DEFAULT 'default.jpg',
    bio TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "3. Users table created successfully<br>";
} else {
    echo "Error creating users table: " . $conn->error . "<br>";
}

// 2. Artists Table
$sql = "CREATE TABLE IF NOT EXISTS artists (
    artist_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE,
    specialization VARCHAR(100),
    experience_years INT DEFAULT 0,
    contact_info TEXT,
    website VARCHAR(255),
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    approved_by INT,
    approved_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "4. Artists table created successfully<br>";
} else {
    echo "Error creating artists table: " . $conn->error . "<br>";
}

// 3. Artworks Table
$sql = "CREATE TABLE IF NOT EXISTS artworks (
    artwork_id INT AUTO_INCREMENT PRIMARY KEY,
    artist_id INT,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    category ENUM('Thangka', 'Sculpture', 'Painting', 'Mandala', 'Other') DEFAULT 'Other',
    image_path VARCHAR(255) NOT NULL,
    materials TEXT,
    creation_time VARCHAR(50),
    symbolism TEXT,
    views INT DEFAULT 0,
    likes INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (artist_id) REFERENCES artists(artist_id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "5. Artworks table created successfully<br>";
} else {
    echo "Error creating artworks table: " . $conn->error . "<br>";
}

// 4. Knowledge Articles Table
$sql = "CREATE TABLE IF NOT EXISTS knowledge_articles (
    article_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    title VARCHAR(200) NOT NULL,
    content LONGTEXT NOT NULL,
    category ENUM('History', 'Symbolism', 'Techniques', 'Styles', 'Other') DEFAULT 'Other',
    featured_image VARCHAR(255),
    views INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
)";

if ($conn->query($sql) === TRUE) {
    echo "6. Knowledge articles table created successfully<br>";
} else {
    echo "Error creating knowledge articles table: " . $conn->error . "<br>";
}

// 5. Commission Requests Table
$sql = "CREATE TABLE IF NOT EXISTS commissions (
    commission_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    artist_id INT,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    budget DECIMAL(10,2),
    deadline DATE,
    status ENUM('pending', 'accepted', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (artist_id) REFERENCES artists(artist_id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "7. Commissions table created successfully<br>";
} else {
    echo "Error creating commissions table: " . $conn->error . "<br>";
}

// 6. Artwork Likes Table
$sql = "CREATE TABLE IF NOT EXISTS artwork_likes (
    like_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    artwork_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_like (user_id, artwork_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (artwork_id) REFERENCES artworks(artwork_id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "8. Artwork likes table created successfully<br>";
} else {
    echo "Error creating artwork likes table: " . $conn->error . "<br>";
}

// 7. Artwork Comments Table
$sql = "CREATE TABLE IF NOT EXISTS artwork_comments (
    comment_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    artwork_id INT,
    comment TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (artwork_id) REFERENCES artworks(artwork_id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "9. Artwork comments table created successfully<br>";
} else {
    echo "Error creating artwork comments table: " . $conn->error . "<br>";
}

// 8. Algorithm: Popularity Tracking Table
$sql = "CREATE TABLE IF NOT EXISTS artwork_popularity (
    track_id INT AUTO_INCREMENT PRIMARY KEY,
    artwork_id INT,
    date DATE,
    views INT DEFAULT 0,
    likes INT DEFAULT 0,
    comments INT DEFAULT 0,
    total_score FLOAT,
    FOREIGN KEY (artwork_id) REFERENCES artworks(artwork_id) ON DELETE CASCADE,
    UNIQUE KEY unique_tracking (artwork_id, date)
)";

if ($conn->query($sql) === TRUE) {
    echo "10. Popularity tracking table created successfully<br>";
} else {
    echo "Error creating popularity tracking table: " . $conn->error . "<br>";
}

// ========== INSERT SAMPLE DATA ==========
echo "<h3>Inserting Sample Data...</h3>";

// Insert sample admin user (password: admin123)
$hashed_password = password_hash('admin123', PASSWORD_DEFAULT);
$sql = "INSERT IGNORE INTO users (username, email, password_hash, full_name, role) VALUES
        ('admin', 'admin@buddhistart.com', '$hashed_password', 'System Administrator', 'admin'),
        ('artist1', 'artist1@example.com', '$hashed_password', 'Traditional Thangka Artist', 'artist'),
        ('user1', 'user1@example.com', '$hashed_password', 'Art Enthusiast', 'user')";

if ($conn->query($sql) === TRUE) {
    echo "Sample users inserted<br>";
} else {
    echo "Error inserting users: " . $conn->error . "<br>";
}

// Insert sample artist
$sql = "INSERT IGNORE INTO artists (user_id, specialization, experience_years, status) VALUES
        (2, 'Thangka Painting', 10, 'approved')";

if ($conn->query($sql) === TRUE) {
    echo "Sample artist inserted<br>";
} else {
    echo "Error inserting artist: " . $conn->error . "<br>";
}

// Insert sample artworks
$sql = "INSERT IGNORE INTO artworks (artist_id, title, description, category, image_path, materials, symbolism, views, likes) VALUES
        (1, 'Buddha Mandala', 'Traditional Tibetan mandala representing the universe', 'Mandala', 'mandala1.jpg', 'Natural pigments on cotton', 'Represents cosmic order and spiritual journey', 150, 25),
        (1, 'Green Tara Thangka', 'Sacred painting of Green Tara, goddess of compassion', 'Thangka', 'thangka1.jpg', 'Gold leaf, mineral pigments on silk', 'Symbolizes compassion and protection from fear', 230, 42),
        (1, 'Meditating Buddha', 'Bronze sculpture of Buddha in meditation pose', 'Sculpture', 'sculpture1.jpg', 'Bronze with gold plating', 'Represents enlightenment and inner peace', 180, 31)";

if ($conn->query($sql) === TRUE) {
    echo "Sample artworks inserted<br>";
} else {
    echo "Error inserting artworks: " . $conn->error . "<br>";
}

// Insert sample knowledge articles
$sql = "INSERT IGNORE INTO knowledge_articles (user_id, title, content, category) VALUES
        (1, 'History of Thangka Painting', 'Thangka painting originated in Nepal in the 7th century...', 'History'),
        (1, 'Meaning of Buddhist Symbols', 'The eight auspicious symbols represent different aspects of Buddhist teachings...', 'Symbolism')";

if ($conn->query($sql) === TRUE) {
    echo "Sample knowledge articles inserted<br>";
} else {
    echo "Error inserting articles: " . $conn->error . "<br>";
}

echo "<h3 style='color: green;'>✅ Database setup completed successfully!</h3>";
echo "<p>Now you can:</p>";
echo "<ol>
        <li>Delete or rename this file (setup-database.php) for security</li>
        <li>Visit your homepage: <a href='index.php'>index.php</a></li>
        <li>Login with: username: <strong>admin</strong>, password: <strong>admin123</strong></li>
      </ol>";

$conn->close();
?>