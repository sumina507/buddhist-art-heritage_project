<?php
// config.php - Updated with upload paths
session_start();

// Database configuration
define('DB_HOST', 'localhost:3306');  // Add the port number
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sumina');

// Create connection
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

define('SITE_URL', 'http://localhost/buddhist-art-project/');// Upload paths - IMPORTANT!
define('UPLOAD_PATH', $_SERVER['DOCUMENT_ROOT'] . '/buddhist-art-project/uploads/');
define('PROFILE_UPLOAD_PATH', UPLOAD_PATH . 'profiles/');
define('ARTWORK_UPLOAD_PATH', UPLOAD_PATH . 'artworks/');
define('WEB_PROFILE_PATH', 'uploads/profiles/'); // For HTML img src

// Create folders if they don't exist
if (!file_exists(PROFILE_UPLOAD_PATH)) {
    mkdir(PROFILE_UPLOAD_PATH, 0777, true);
}
if (!file_exists(ARTWORK_UPLOAD_PATH)) {
    mkdir(ARTWORK_UPLOAD_PATH, 0777, true);
}
// ========== END OF ADDED LINES ==========

// Set timezone
date_default_timezone_set('Asia/Kathmandu');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>