<?php
// ajax/like-artwork.php
require_once '../includes/config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to like']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$artwork_id = $data['artwork_id'] ?? 0;
$user_id = $_SESSION['user_id'];

if (!$artwork_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid artwork']);
    exit;
}

// Check if already liked
$check_sql = "SELECT * FROM artwork_likes WHERE user_id = ? AND artwork_id = ?";
$check_stmt = mysqli_prepare($conn, $check_sql);
mysqli_stmt_bind_param($check_stmt, "ii", $user_id, $artwork_id);
mysqli_stmt_execute($check_stmt);
$check_result = mysqli_stmt_get_result($check_stmt);
$liked = mysqli_num_rows($check_result) > 0;

if ($liked) {
    // UNLIKE - Remove the like
    $sql = "DELETE FROM artwork_likes WHERE user_id = ? AND artwork_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $artwork_id);
    mysqli_stmt_execute($stmt);
    $liked_status = false;
} else {
    // LIKE - Add the like
    $sql = "INSERT INTO artwork_likes (user_id, artwork_id) VALUES (?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $artwork_id);
    mysqli_stmt_execute($stmt);
    $liked_status = true;
}

// Get the NEW like count from database
$count_sql = "SELECT COUNT(*) as new_count FROM artwork_likes WHERE artwork_id = ?";
$count_stmt = mysqli_prepare($conn, $count_sql);
mysqli_stmt_bind_param($count_stmt, "i", $artwork_id);
mysqli_stmt_execute($count_stmt);
$count_result = mysqli_stmt_get_result($count_stmt);
$count_data = mysqli_fetch_assoc($count_result);
$new_count = $count_data['new_count'];

// Return JSON response
echo json_encode([
    'success' => true,
    'liked' => $liked_status,
    'new_count' => $new_count
]);
?>