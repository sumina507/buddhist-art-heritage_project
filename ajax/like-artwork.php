<?php
// ajax/like-artwork.php
require_once '../includes/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to like']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$artwork_id = intval($data['artwork_id'] ?? 0);
$user_id = $_SESSION['user_id'];

if (!$artwork_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid artwork']);
    exit;
}

// Check if already liked
$check_stmt = mysqli_prepare($conn, "SELECT artwork_id FROM artwork_likes WHERE user_id = ? AND artwork_id = ?");
mysqli_stmt_bind_param($check_stmt, "ii", $user_id, $artwork_id);
mysqli_stmt_execute($check_stmt);
mysqli_stmt_store_result($check_stmt);
$already_liked = mysqli_stmt_num_rows($check_stmt) > 0;
mysqli_stmt_close($check_stmt);

if ($already_liked) {
    // Unlike
    $stmt = mysqli_prepare($conn, "DELETE FROM artwork_likes WHERE user_id = ? AND artwork_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $artwork_id);
    $like_change = -1;
} else {
    // Like
    $stmt = mysqli_prepare($conn, "INSERT INTO artwork_likes (user_id, artwork_id) VALUES (?, ?)");
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $artwork_id);
    $like_change = 1;
}

if (mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);

    // Also keep artworks.likes column in sync
    $update_stmt = mysqli_prepare($conn, "UPDATE artworks SET likes = likes + ? WHERE artwork_id = ?");
    mysqli_stmt_bind_param($update_stmt, "ii", $like_change, $artwork_id);
    mysqli_stmt_execute($update_stmt);
    mysqli_stmt_close($update_stmt);

    // Get updated counts — always count from artwork_likes table for accuracy
    $score_sql = "SELECT 
                    a.views,
                    (SELECT COUNT(*) FROM artwork_likes WHERE artwork_id = a.artwork_id) as total_likes,
                    (SELECT COUNT(*) FROM artwork_comments WHERE artwork_id = a.artwork_id AND sentiment = 'positive') as pos_comments,
                    (SELECT COUNT(*) FROM artwork_comments WHERE artwork_id = a.artwork_id AND sentiment = 'negative') as neg_comments
                  FROM artworks a
                  WHERE a.artwork_id = ?";
    $score_stmt = mysqli_prepare($conn, $score_sql);
    mysqli_stmt_bind_param($score_stmt, "i", $artwork_id);
    mysqli_stmt_execute($score_stmt);
    $score_result = mysqli_stmt_get_result($score_stmt);
    $d = mysqli_fetch_assoc($score_result);

    $views  = $d['views'] ?? 0;
    $likes  = $d['total_likes'] ?? 0;
    $pos    = $d['pos_comments'] ?? 0;
    $neg    = $d['neg_comments'] ?? 0;

    // Popularity formula: views*0.3 + likes*0.5 + positive*0.2 - negative*0.1
    $new_score = round(($views * 0.3) + ($likes * 0.5) + ($pos * 0.2) - ($neg * 0.1), 1);

    echo json_encode([
        'success'   => true,
        'liked'     => !$already_liked,
        'new_count' => $likes,
        'new_score' => $new_score,
        'message'   => $already_liked ? 'Like removed' : 'Artwork liked!'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
}
?>