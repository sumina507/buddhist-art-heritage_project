<?php
// ajax/like-artwork.php
require_once '../includes/config.php';

header('Content-Type: application/json');

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
    // Unlike
    $sql = "DELETE FROM artwork_likes WHERE user_id = ? AND artwork_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $artwork_id);
    $action = 'unliked';
    $change = -1;
} else {
    // Like
    $sql = "INSERT INTO artwork_likes (user_id, artwork_id) VALUES (?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $artwork_id);
    $action = 'liked';
    $change = 1;
}

if (mysqli_stmt_execute($stmt)) {
    // Update artwork likes count
    $update_sql = "UPDATE artworks SET likes = likes + ? WHERE artwork_id = ?";
    $update_stmt = mysqli_prepare($conn, $update_sql);
    mysqli_stmt_bind_param($update_stmt, "ii", $change, $artwork_id);
    mysqli_stmt_execute($update_stmt);
    
    // Get new like count
    $count_sql = "SELECT likes FROM artworks WHERE artwork_id = ?";
    $count_stmt = mysqli_prepare($conn, $count_sql);
    mysqli_stmt_bind_param($count_stmt, "i", $artwork_id);
    mysqli_stmt_execute($count_stmt);
    $count_result = mysqli_stmt_get_result($count_stmt);
    $count_data = mysqli_fetch_assoc($count_result);
    $new_count = $count_data['likes'];
    
    echo json_encode([
        'success' => true, 
        'liked' => !$liked,
        'new_count' => $new_count,
        'message' => $action == 'liked' ? 'Artwork liked!' : 'Like removed'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>