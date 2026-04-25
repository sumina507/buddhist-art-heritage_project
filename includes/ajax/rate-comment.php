<?php
// ajax/rate-comment.php
require_once '../includes/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to rate']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$comment_id = intval($data['comment_id'] ?? 0);
$sentiment  = $data['sentiment'] ?? '';
$user_id    = $_SESSION['user_id'];

if (!$comment_id || !in_array($sentiment, ['positive', 'negative'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

// Get artwork_id from this comment
$art_stmt = mysqli_prepare($conn, "SELECT artwork_id FROM artwork_comments WHERE comment_id = ?");
mysqli_stmt_bind_param($art_stmt, "i", $comment_id);
mysqli_stmt_execute($art_stmt);
$art_result = mysqli_stmt_get_result($art_stmt);
$art_row = mysqli_fetch_assoc($art_result);
$artwork_id = $art_row['artwork_id'] ?? 0;

if (!$artwork_id) {
    echo json_encode(['success' => false, 'message' => 'Comment not found']);
    exit;
}

// Check if user already rated this comment
$check_stmt = mysqli_prepare($conn, "SELECT rating_id, sentiment FROM comment_ratings WHERE comment_id = ? AND user_id = ?");
mysqli_stmt_bind_param($check_stmt, "ii", $comment_id, $user_id);
mysqli_stmt_execute($check_stmt);
$check_result = mysqli_stmt_get_result($check_stmt);
$existing = mysqli_fetch_assoc($check_result);

if ($existing) {
    if ($existing['sentiment'] == $sentiment) {
        // Same rating clicked again — remove it (toggle off)
        $del_stmt = mysqli_prepare($conn, "DELETE FROM comment_ratings WHERE comment_id = ? AND user_id = ?");
        mysqli_stmt_bind_param($del_stmt, "ii", $comment_id, $user_id);
        mysqli_stmt_execute($del_stmt);

        // Also revert the sentiment on the comment if needed
        // Set back to neutral since rating removed
        $upd_stmt = mysqli_prepare($conn, "UPDATE artwork_comments SET sentiment = 'neutral' WHERE comment_id = ?");
        mysqli_stmt_bind_param($upd_stmt, "i", $comment_id);
        mysqli_stmt_execute($upd_stmt);
    } else {
        // Different rating — update existing
        $upd_stmt = mysqli_prepare($conn, "UPDATE comment_ratings SET sentiment = ? WHERE comment_id = ? AND user_id = ?");
        mysqli_stmt_bind_param($upd_stmt, "sii", $sentiment, $comment_id, $user_id);
        mysqli_stmt_execute($upd_stmt);

        // Update comment sentiment
        $upd_comment = mysqli_prepare($conn, "UPDATE artwork_comments SET sentiment = ? WHERE comment_id = ?");
        mysqli_stmt_bind_param($upd_comment, "si", $sentiment, $comment_id);
        mysqli_stmt_execute($upd_comment);
    }
} else {
    // New rating — insert
    $ins_stmt = mysqli_prepare($conn, "INSERT INTO comment_ratings (comment_id, user_id, sentiment, created_at) VALUES (?, ?, ?, NOW())");
    mysqli_stmt_bind_param($ins_stmt, "iis", $comment_id, $user_id, $sentiment);
    mysqli_stmt_execute($ins_stmt);

    // Update comment sentiment based on this rating
    $upd_comment = mysqli_prepare($conn, "UPDATE artwork_comments SET sentiment = ? WHERE comment_id = ?");
    mysqli_stmt_bind_param($upd_comment, "si", $sentiment, $comment_id);
    mysqli_stmt_execute($upd_comment);
}

// Get updated comment rating counts
$count_stmt = mysqli_prepare($conn, "SELECT 
    (SELECT COUNT(*) FROM comment_ratings WHERE comment_id = ? AND sentiment = 'positive') as pos,
    (SELECT COUNT(*) FROM comment_ratings WHERE comment_id = ? AND sentiment = 'negative') as neg");
mysqli_stmt_bind_param($count_stmt, "ii", $comment_id, $comment_id);
mysqli_stmt_execute($count_stmt);
$count_result = mysqli_stmt_get_result($count_stmt);
$counts = mysqli_fetch_assoc($count_result);

// Recalculate artwork popularity score
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

// Popularity formula
$new_score = round(($views * 0.3) + ($likes * 0.5) + ($pos * 0.2) - ($neg * 0.1), 1);

echo json_encode([
    'success'        => true,
    'positive_count' => $counts['pos'] ?? 0,
    'negative_count' => $counts['neg'] ?? 0,
    'new_score'      => $new_score,
    'artwork_id'     => $artwork_id,
    'message'        => 'Rating saved!'
]);
?>