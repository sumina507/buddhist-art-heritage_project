<?php
require_once '../includes/config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to rate']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$type = $data['type'] ?? '';
$id = $data['id'] ?? 0;
$rating = $data['rating'] ?? 0;
$user_id = $_SESSION['user_id'];

if ($rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Invalid rating']);
    exit;
}

$table = ($type == 'artist') ? 'artist_ratings' : 'artwork_ratings';
$id_column = ($type == 'artist') ? 'artist_id' : 'artwork_id';

// Insert or update rating
$sql = "INSERT INTO $table ($id_column, user_id, rating) 
        VALUES (?, ?, ?) 
        ON DUPLICATE KEY UPDATE rating = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "iiii", $id, $user_id, $rating, $rating);

if (mysqli_stmt_execute($stmt)) {
    // Get updated average
    $avg_sql = "SELECT AVG(rating) as avg_rating, COUNT(*) as total 
                FROM $table WHERE $id_column = ?";
    $stmt = mysqli_prepare($conn, $avg_sql);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $data = mysqli_fetch_assoc($result);
    
    echo json_encode([
        'success' => true,
        'new_avg' => round($data['avg_rating'], 1),
        'total' => $data['total']
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
}
?>