<?php
// artwork-detail.php
require_once 'includes/config.php';
require_once 'includes/navbar.php';

$artwork_id = intval($_GET['id'] ?? 0);
$page_title = "Artwork Details";

// Smart view counting - count once per session per artwork
if ($artwork_id) {
    if (!isset($_SESSION['viewed_artworks'])) {
        $_SESSION['viewed_artworks'] = [];
    }
    if (!in_array($artwork_id, $_SESSION['viewed_artworks'])) {
        $view_stmt = mysqli_prepare($conn, "UPDATE artworks SET views = views + 1 WHERE artwork_id = ?");
        mysqli_stmt_bind_param($view_stmt, "i", $artwork_id);
        mysqli_stmt_execute($view_stmt);
        $_SESSION['viewed_artworks'][] = $artwork_id;
    }
}

// Get artwork details
$sql = "SELECT a.*, u.username, u.full_name as artist_name, u.profile_image, 
        ar.artist_id, ar.specialization, ar.experience_years
        FROM artworks a
        JOIN artists ar ON a.artist_id = ar.artist_id
        JOIN users u ON ar.user_id = u.user_id
        WHERE a.artwork_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $artwork_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$artwork = mysqli_fetch_assoc($result);

if (!$artwork) {
    $_SESSION['message'] = "Artwork not found!";
    $_SESSION['message_type'] = 'error';
    header('Location: gallery.php');
    exit;
}

$page_title = $artwork['title'] . " - Buddhist Art Heritage";

// Get like count from artwork_likes table (accurate)
$like_stmt = mysqli_prepare($conn, "SELECT COUNT(*) as total FROM artwork_likes WHERE artwork_id = ?");
mysqli_stmt_bind_param($like_stmt, "i", $artwork_id);
mysqli_stmt_execute($like_stmt);
$like_result = mysqli_stmt_get_result($like_stmt);
$like_count = mysqli_fetch_assoc($like_result)['total'] ?? 0;

// Check if current user liked
$user_liked = false;
if (isset($_SESSION['user_id'])) {
    $check_stmt = mysqli_prepare($conn, "SELECT artwork_id FROM artwork_likes WHERE artwork_id = ? AND user_id = ?");
    mysqli_stmt_bind_param($check_stmt, "ii", $artwork_id, $_SESSION['user_id']);
    mysqli_stmt_execute($check_stmt);
    mysqli_stmt_store_result($check_stmt);
    $user_liked = mysqli_stmt_num_rows($check_stmt) > 0;
    mysqli_stmt_close($check_stmt);
}

// Get current popularity score
$pos_stmt = mysqli_prepare($conn, "SELECT COUNT(*) as total FROM artwork_comments WHERE artwork_id = ? AND sentiment = 'positive'");
mysqli_stmt_bind_param($pos_stmt, "i", $artwork_id);
mysqli_stmt_execute($pos_stmt);
$pos_result = mysqli_stmt_get_result($pos_stmt);
$pos_comments = mysqli_fetch_assoc($pos_result)['total'] ?? 0;

$neg_stmt = mysqli_prepare($conn, "SELECT COUNT(*) as total FROM artwork_comments WHERE artwork_id = ? AND sentiment = 'negative'");
mysqli_stmt_bind_param($neg_stmt, "i", $artwork_id);
mysqli_stmt_execute($neg_stmt);
$neg_result = mysqli_stmt_get_result($neg_stmt);
$neg_comments = mysqli_fetch_assoc($neg_result)['total'] ?? 0;

$current_score = round(($artwork['views'] * 0.3) + ($like_count * 0.5) + ($pos_comments * 0.2) - ($neg_comments * 0.1), 1);

// ===== HANDLE COMMENT SUBMISSION WITH SENTIMENT DETECTION =====
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['comment'])) {
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['message'] = "Please login to comment";
        $_SESSION['message_type'] = 'error';
        header("Location: artwork-detail.php?id=$artwork_id");
        exit;
    }

    $comment = trim($_POST['comment']);
    if (!empty($comment)) {
        // Simple sentiment detection
        $positive_words = ['beautiful', 'amazing', 'excellent', 'wonderful', 'love', 'great', 'stunning', 
                           'gorgeous', 'perfect', 'fantastic', 'awesome', 'nice', 'good', 'impressive',
                           'masterpiece', 'incredible', 'breathtaking', 'divine', 'peaceful', 'calming'];
        
        $negative_words = ['bad', 'poor', 'terrible', 'awful', 'disappointing', 'ugly', 'hate', 'worst',
                           'horrible', 'cheap', 'low quality', 'worst', 'unhappy', 'dissatisfied',
                           'regret', 'waste', 'not good', 'below expectations'];
        
        $comment_lower = strtolower($comment);
        $sentiment = 'neutral';
        
        // Check for positive words
        foreach ($positive_words as $word) {
            if (strpos($comment_lower, $word) !== false) {
                $sentiment = 'positive';
                break;
            }
        }
        
        // Check for negative words (overrides positive if both present)
        foreach ($negative_words as $word) {
            if (strpos($comment_lower, $word) !== false) {
                $sentiment = 'negative';
                break;
            }
        }
        
        // Insert comment with detected sentiment
        $ins_stmt = mysqli_prepare($conn, "INSERT INTO artwork_comments (user_id, artwork_id, comment, sentiment, created_at) VALUES (?, ?, ?, ?, NOW())");
        mysqli_stmt_bind_param($ins_stmt, "iiss", $_SESSION['user_id'], $artwork_id, $comment, $sentiment);
        
        if (mysqli_stmt_execute($ins_stmt)) {
            // Recalculate artwork score
            $score_sql = "SELECT 
                            a.views,
                            (SELECT COUNT(*) FROM artwork_likes WHERE artwork_id = a.artwork_id) as total_likes,
                            (SELECT COUNT(*) FROM artwork_comments WHERE artwork_id = a.artwork_id AND sentiment = 'positive') as pos_comments,
                            (SELECT COUNT(*) FROM artwork_comments WHERE artwork_id = a.artwork_id AND sentiment = 'negative') as neg_comments
                          FROM artworks a WHERE a.artwork_id = ?";
            $score_stmt = mysqli_prepare($conn, $score_sql);
            mysqli_stmt_bind_param($score_stmt, "i", $artwork_id);
            mysqli_stmt_execute($score_stmt);
            $score_result = mysqli_stmt_get_result($score_stmt);
            $score_data = mysqli_fetch_assoc($score_result);
            
            $views = $score_data['views'] ?? 0;
            $likes = $score_data['total_likes'] ?? 0;
            $pos = $score_data['pos_comments'] ?? 0;
            $neg = $score_data['neg_comments'] ?? 0;
            
            // Positive comments: +0.2 each, Negative comments: -0.1 each
            $new_score = round(($views * 0.3) + ($likes * 0.5) + ($pos * 0.2) - ($neg * 0.1), 1);
            
            // Update artwork popularity score
            $update_score = mysqli_prepare($conn, "UPDATE artworks SET popularity_score = ? WHERE artwork_id = ?");
            mysqli_stmt_bind_param($update_score, "di", $new_score, $artwork_id);
            mysqli_stmt_execute($update_score);
            
            $sentiment_emoji = $sentiment == 'positive' ? '👍' : ($sentiment == 'negative' ? '👎' : '💬');
            $_SESSION['message'] = "Comment added! $sentiment_emoji Score updated to " . $new_score;
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = "Error adding comment.";
            $_SESSION['message_type'] = 'error';
        }
        
        header("Location: artwork-detail.php?id=$artwork_id");
        exit;
    }
}

// Get comments
$comment_sql = "SELECT ac.*, u.username, u.profile_image,
                (SELECT COUNT(*) FROM comment_ratings WHERE comment_id = ac.comment_id AND sentiment = 'positive') as helpful_count,
                (SELECT COUNT(*) FROM comment_ratings WHERE comment_id = ac.comment_id AND sentiment = 'negative') as unhelpful_count
                FROM artwork_comments ac
                JOIN users u ON ac.user_id = u.user_id
                WHERE ac.artwork_id = ?
                ORDER BY ac.created_at DESC";
$com_stmt = mysqli_prepare($conn, $comment_sql);
mysqli_stmt_bind_param($com_stmt, "i", $artwork_id);
mysqli_stmt_execute($com_stmt);
$comments_result = mysqli_stmt_get_result($com_stmt);
$total_comments = mysqli_num_rows($comments_result);
?>

<div class="container artwork-detail-container">
    <!-- Breadcrumb -->
    <nav class="breadcrumb">
        <a href="index.php">Home</a> &gt;
        <a href="gallery.php">Gallery</a> &gt;
        <span><?php echo htmlspecialchars($artwork['title'] ?? ''); ?></span>
    </nav>

    <!-- Popularity Score Badge -->
    <div class="score-badge-detail">
        <i class="fas fa-chart-line"></i> 
        Popularity Score: <strong id="detail-score"><?php echo $current_score; ?></strong>
        <span class="score-formula">= (Views×0.3) + (Likes×0.5) + (👍Comments×0.2) - (👎Comments×0.1)</span>
    </div>

    <!-- Main Artwork -->
    <div class="artwork-main">
        <div class="artwork-image-large">
            <img src="uploads/artworks/<?php echo htmlspecialchars($artwork['image_path'] ?? ''); ?>"
                 alt="<?php echo htmlspecialchars($artwork['title'] ?? 'Artwork'); ?>">

            <!-- Like Button -->
            <button class="like-btn <?php echo $user_liked ? 'liked' : ''; ?>"
                    onclick="toggleLike(<?php echo $artwork_id; ?>, this)">
                <i class="<?php echo $user_liked ? 'fas' : 'far'; ?> fa-heart"></i>
                <span class="like-count"><?php echo $like_count; ?></span>
            </button>
        </div>

        <div class="artwork-info-detail">
            <h1><?php echo htmlspecialchars($artwork['title'] ?? ''); ?></h1>

            <div class="artist-info">
                <div class="artist-avatar-small">
                    <img src="uploads/profiles/<?php echo htmlspecialchars(!empty($artwork['profile_image']) ? $artwork['profile_image'] : 'default.jpg'); ?>" alt="Artist">
                </div>
                <div>
                    <h3><?php echo htmlspecialchars($artwork['artist_name'] ?? 'Artist'); ?></h3>
                    <p class="specialization"><?php echo htmlspecialchars($artwork['specialization'] ?? 'Artist'); ?></p>
                </div>
                <a href="artist-profile.php?id=<?php echo $artwork['artist_id'] ?? 0; ?>" class="btn-view-artist">
                    View Profile
                </a>
            </div>

            <div class="artwork-meta-detail">
                <div class="meta-item">
                    <i class="fas fa-tag"></i>
                    <span><?php echo htmlspecialchars($artwork['category'] ?? 'Other'); ?></span>
                </div>
                <div class="meta-item">
                    <i class="fas fa-eye"></i>
                    <span><?php echo number_format($artwork['views'] ?? 0); ?> views</span>
                </div>
                <div class="meta-item">
                    <i class="fas fa-heart" style="color:#e74c3c;"></i>
                    <span><?php echo number_format($like_count); ?> likes</span>
                </div>
                <div class="meta-item">
                    <i class="fas fa-calendar"></i>
                    <span><?php echo isset($artwork['created_at']) ? date('M d, Y', strtotime($artwork['created_at'])) : 'Unknown'; ?></span>
                </div>
            </div>

            <div class="artwork-description">
                <h3>Description</h3>
                <p><?php echo nl2br(htmlspecialchars($artwork['description'] ?? '')); ?></p>
            </div>

            <?php if (!empty($artwork['materials'])): ?>
            <div class="artwork-materials">
                <h3>Materials</h3>
                <p><?php echo htmlspecialchars($artwork['materials']); ?></p>
            </div>
            <?php endif; ?>

            <?php if (!empty($artwork['symbolism'])): ?>
            <div class="artwork-symbolism">
                <h3>Symbolism</h3>
                <p><?php echo nl2br(htmlspecialchars($artwork['symbolism'])); ?></p>
            </div>
            <?php endif; ?>

            <!-- Commission CTA -->
            <?php
            $prefilled_title = urlencode($artwork['title']);
            $prefilled_description = urlencode("I would like a custom artwork similar to '{$artwork['title']}' by {$artwork['artist_name']}.");
            ?>
            <div class="commission-cta">
                <p>Want a custom piece like this?</p>
                <a href="commission-request.php?artist_id=<?php echo $artwork['artist_id']; ?>&title=<?php echo $prefilled_title; ?>&description=<?php echo $prefilled_description; ?>" class="btn-commission">
                    <i class="fas fa-handshake"></i> Request Similar Artwork
                </a>
            </div>
        </div>
    </div>

    <!-- Comments Section -->
    <div class="comments-section">
        <div class="comments-header">
            <h3><i class="fas fa-comments"></i> Discussion <span>(<?php echo $total_comments; ?>)</span></h3>
            <p style="font-size:0.8rem; color:#6c757d; margin-top:0.3rem;">
                <i class="fas fa-info-circle" style="color:#e74c3c;"></i>
                Thumbs up increases artwork score (+0.2), thumbs down decreases it (-0.1)
            </p>
        </div>

        <?php if (isset($_SESSION['user_id'])): ?>
        <form method="POST" class="comment-form">
            <div class="comment-input-wrapper">
                <img src="uploads/profiles/<?php echo !empty($_SESSION['profile_image']) ? $_SESSION['profile_image'] : 'default.jpg'; ?>"
                     alt="Your avatar" class="comment-avatar">
                <div class="comment-input-group">
                    <textarea name="comment" placeholder="Share your thoughts about this artwork..." required></textarea>
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-paper-plane"></i> Post
                    </button>
                </div>
            </div>
        </form>
        <?php else: ?>
        <div class="login-prompt">
            <i class="fas fa-lock"></i>
            <p><a href="login.php">Login</a> to join the discussion</p>
        </div>
        <?php endif; ?>

        <div class="comments-list">
            <?php if ($total_comments > 0): ?>
                <?php while ($comment = mysqli_fetch_assoc($comments_result)):
                    $helpful_count   = $comment['helpful_count'] ?? 0;
                    $unhelpful_count = $comment['unhelpful_count'] ?? 0;
                    $sentiment_class = '';
                    if ($comment['sentiment'] == 'positive') $sentiment_class = 'comment-positive';
                    if ($comment['sentiment'] == 'negative') $sentiment_class = 'comment-negative';
                ?>
                <div class="comment-item <?php echo $sentiment_class; ?>" data-comment-id="<?php echo $comment['comment_id']; ?>">
                    <img src="uploads/profiles/<?php echo htmlspecialchars($comment['profile_image'] ?? 'default.jpg'); ?>"
                         alt="<?php echo htmlspecialchars($comment['username'] ?? 'User'); ?>"
                         class="comment-avatar">
                    <div class="comment-content">
                        <div class="comment-meta">
                            <strong><?php echo htmlspecialchars($comment['username'] ?? 'Anonymous'); ?></strong>
                            <?php if ($comment['sentiment'] == 'positive'): ?>
                                <span class="sentiment-tag positive"><i class="fas fa-thumbs-up"></i> Helpful</span>
                            <?php elseif ($comment['sentiment'] == 'negative'): ?>
                                <span class="sentiment-tag negative"><i class="fas fa-thumbs-down"></i> Critical</span>
                            <?php endif; ?>
                            <span class="comment-time">
                                <i class="fas fa-clock"></i>
                                <?php echo isset($comment['created_at']) ? date('M j, Y • g:i A', strtotime($comment['created_at'])) : 'Unknown'; ?>
                            </span>
                        </div>
                        <p><?php echo nl2br(htmlspecialchars($comment['comment'] ?? '')); ?></p>

                        <!-- Rating Buttons -->
                        <div class="comment-actions">
                            <button class="comment-rate helpful"
                                    data-comment-id="<?php echo $comment['comment_id']; ?>"
                                    data-sentiment="positive"
                                    title="This comment is helpful — increases artwork score">
                                <i class="fas fa-thumbs-up"></i>
                                <span class="helpful-count-<?php echo $comment['comment_id']; ?>"><?php echo $helpful_count; ?></span>
                                <small>+score</small>
                            </button>
                            <button class="comment-rate not-helpful"
                                    data-comment-id="<?php echo $comment['comment_id']; ?>"
                                    data-sentiment="negative"
                                    title="This comment is unhelpful — decreases artwork score">
                                <i class="fas fa-thumbs-down"></i>
                                <span class="unhelpful-count-<?php echo $comment['comment_id']; ?>"><?php echo $unhelpful_count; ?></span>
                                <small>-score</small>
                            </button>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-comments">
                    <i class="fas fa-comment-dots"></i>
                    <p>No comments yet. Be the first to share your thoughts!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.artwork-detail-container { max-width: 1200px; margin: 0 auto; padding: 2rem 1rem; }

.breadcrumb { margin-bottom: 1rem; font-size: 0.9rem; color: #666; }
.breadcrumb a { color: #e74c3c; text-decoration: none; }
.breadcrumb a:hover { text-decoration: underline; }

/* Score Badge */
.score-badge-detail {
    background: linear-gradient(135deg, #fff8e7, #fef5f4);
    border: 1px solid #f1c40f;
    border-left: 4px solid #e74c3c;
    padding: 0.8rem 1.2rem;
    border-radius: 10px;
    margin-bottom: 1.5rem;
    font-size: 0.9rem;
    color: #2c3e50;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.score-badge-detail i { color: #e74c3c; }
.score-badge-detail strong { color: #e74c3c; font-size: 1.1rem; }
.score-formula { font-size: 0.75rem; color: #6c757d; margin-left: 0.5rem; }

/* Main Layout */
.artwork-main { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem; }

.artwork-image-large { position: relative; border-radius: 16px; overflow: hidden; box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
.artwork-image-large img { width: 100%; height: auto; display: block; }

/* Like Button */
.like-btn {
    position: absolute;
    bottom: 20px; right: 20px;
    background: white;
    border: none;
    padding: 0.6rem 1.2rem;
    border-radius: 40px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 6px;
    font-weight: 600;
    font-size: 0.9rem;
    box-shadow: 0 3px 10px rgba(0,0,0,0.2);
    transition: all 0.2s;
    color: #6c757d;
}
.like-btn i { color: #e74c3c; }
.like-btn:hover { transform: scale(1.05); }
.like-btn.liked { background: #e74c3c; color: white; }
.like-btn.liked i { color: white; }

/* Artwork Info */
.artwork-info-detail h1 { font-size: 1.8rem; color: #2c3e50; margin-bottom: 1rem; }

.artist-info {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #eee;
}

.artist-avatar-small { width: 50px; height: 50px; border-radius: 50%; overflow: hidden; }
.artist-avatar-small img { width: 100%; height: 100%; object-fit: cover; border: 2px solid #f1c40f; }
.artist-info h3 { font-size: 1.1rem; margin-bottom: 0.2rem; color: #2c3e50; }
.specialization { font-size: 0.8rem; color: #6c757d; }

.btn-view-artist {
    margin-left: auto;
    padding: 0.4rem 1rem;
    background: #3498db;
    color: white;
    text-decoration: none;
    border-radius: 5px;
    font-size: 0.8rem;
    transition: all 0.2s;
}
.btn-view-artist:hover { background: #2980b9; transform: translateY(-2px); }

.artwork-meta-detail {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    margin-bottom: 1.5rem;
    padding: 0.8rem;
    background: #f8f9fa;
    border-radius: 8px;
}

.meta-item { display: flex; align-items: center; gap: 6px; font-size: 0.85rem; color: #666; }
.meta-item i { color: #e74c3c; width: 18px; }

.artwork-description h3, .artwork-materials h3, .artwork-symbolism h3 {
    font-size: 1rem; color: #2c3e50; margin-bottom: 0.5rem; margin-top: 1rem;
}
.artwork-description p, .artwork-materials p, .artwork-symbolism p {
    font-size: 0.9rem; line-height: 1.6; color: #555;
}

/* Commission CTA */
.commission-cta {
    background: linear-gradient(135deg, #e74c3c, #c0392b);
    color: white;
    padding: 1.5rem;
    border-radius: 16px;
    text-align: center;
    margin-top: 1.5rem;
    box-shadow: 0 5px 20px rgba(231,76,60,0.3);
}
.commission-cta p { margin-bottom: 1rem; font-size: 1rem; font-weight: 500; }
.btn-commission {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 0.8rem 2rem;
    background: #f1c40f;
    color: #2c3e50;
    text-decoration: none;
    border-radius: 40px;
    font-weight: 600;
    font-size: 1rem;
    transition: all 0.3s;
}
.btn-commission:hover { transform: translateY(-3px); background: #e6b800; }

/* Comments Section */
.comments-section {
    background: white;
    border-radius: 20px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    box-shadow: 0 5px 20px rgba(0,0,0,0.05);
    border: 1px solid #e9ecef;
}

.comments-header h3 { font-size: 1.2rem; margin-bottom: 0.5rem; color: #2c3e50; display: flex; align-items: center; gap: 8px; }
.comments-header h3 i { color: #e74c3c; }
.comments-header h3 span { color: #999; font-weight: normal; font-size: 0.9rem; }

.comment-form { margin-bottom: 2rem; }
.comment-input-wrapper { display: flex; gap: 1rem; align-items: flex-start; }
.comment-avatar { width: 45px; height: 45px; border-radius: 50%; object-fit: cover; border: 2px solid #f1c40f; }
.comment-input-group { flex: 1; display: flex; flex-direction: column; gap: 0.8rem; }
.comment-input-group textarea {
    width: 100%;
    padding: 1rem;
    border: 2px solid #e9ecef;
    border-radius: 12px;
    resize: vertical;
    font-size: 0.9rem;
    font-family: inherit;
    transition: all 0.3s;
    min-height: 80px;
}
.comment-input-group textarea:focus { outline: none; border-color: #e74c3c; box-shadow: 0 0 0 3px rgba(231,76,60,0.1); }

.btn-submit {
    align-self: flex-end;
    background: linear-gradient(135deg, #e74c3c, #c0392b);
    color: white;
    border: none;
    padding: 0.6rem 1.5rem;
    border-radius: 40px;
    cursor: pointer;
    font-weight: 600;
    font-size: 0.85rem;
    transition: all 0.3s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}
.btn-submit:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(231,76,60,0.3); }

.comments-list { max-height: 500px; overflow-y: auto; padding-right: 0.5rem; }
.comments-list::-webkit-scrollbar { width: 5px; }
.comments-list::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
.comments-list::-webkit-scrollbar-thumb { background: #e74c3c; border-radius: 10px; }

.comment-item {
    display: flex;
    gap: 1rem;
    padding: 1rem 0;
    border-bottom: 1px solid #f0f0f0;
}
.comment-item:last-child { border-bottom: none; }
.comment-item .comment-avatar { width: 40px; height: 40px; }

/* Comment sentiment border */
.comment-positive { border-left: 3px solid #27ae60; padding-left: 0.8rem; }
.comment-negative { border-left: 3px solid #e74c3c; padding-left: 0.8rem; }

.comment-content { flex: 1; }
.comment-meta { display: flex; align-items: center; gap: 0.8rem; margin-bottom: 0.5rem; flex-wrap: wrap; }
.comment-meta strong { font-size: 0.9rem; color: #2c3e50; }
.comment-time { font-size: 0.7rem; color: #999; display: flex; align-items: center; gap: 4px; }
.comment-content p { font-size: 0.9rem; color: #555; line-height: 1.5; margin: 0 0 0.5rem 0; }

/* Sentiment tag */
.sentiment-tag {
    font-size: 0.65rem;
    padding: 0.15rem 0.5rem;
    border-radius: 20px;
    font-weight: 600;
}
.sentiment-tag.positive { background: #d4edda; color: #155724; }
.sentiment-tag.negative { background: #f8d7da; color: #721c24; }

/* Comment Actions */
.comment-actions { display: flex; gap: 1rem; margin-top: 0.5rem; }
.comment-rate {
    background: none;
    border: 1px solid #e9ecef;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 0.72rem;
    color: #6c757d;
    padding: 0.25rem 0.6rem;
    border-radius: 20px;
    transition: all 0.2s;
}
.comment-rate small { font-size: 0.6rem; opacity: 0.7; }
.comment-rate:hover { background: #fef5f4; color: #e74c3c; border-color: #e74c3c; }
.comment-rate.helpful:hover { background: #d4edda; color: #155724; border-color: #27ae60; }
.comment-rate.helpful.active { background: #d4edda; color: #155724; border-color: #27ae60; }
.comment-rate.not-helpful.active { background: #f8d7da; color: #721c24; border-color: #e74c3c; }

.login-prompt { text-align: center; padding: 2rem; background: #f8f9fa; border-radius: 12px; }
.login-prompt i { font-size: 2rem; color: #e74c3c; margin-bottom: 0.5rem; display: block; }
.login-prompt p { font-size: 0.9rem; color: #666; }
.login-prompt a { color: #e74c3c; font-weight: 600; text-decoration: none; }

.no-comments { text-align: center; padding: 2rem; color: #999; }
.no-comments i { font-size: 2.5rem; margin-bottom: 0.5rem; color: #e74c3c; opacity: 0.5; display: block; }
.no-comments p { font-size: 0.9rem; }

@media (max-width: 768px) {
    .artwork-main { grid-template-columns: 1fr; }
    .artist-info { flex-wrap: wrap; }
    .btn-view-artist { margin-left: 0; }
    .comment-input-wrapper { flex-direction: column; }
    .score-formula { display: none; }
}
</style>

<script>
// Like toggle with score update
function toggleLike(artworkId, buttonElement) {
    <?php if (!isset($_SESSION['user_id'])): ?>
        window.location.href = 'login.php';
        return;
    <?php endif; ?>

    const heartIcon = buttonElement.querySelector('i');
    const likeSpan  = buttonElement.querySelector('.like-count');

    buttonElement.disabled = true;
    buttonElement.style.opacity = '0.7';

    fetch('ajax/like-artwork.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ artwork_id: artworkId })
    })
    .then(r => r.json())
    .then(data => {
        buttonElement.disabled = false;
        buttonElement.style.opacity = '1';

        if (data.success) {
            if (data.liked) {
                heartIcon.className = 'fas fa-heart';
                buttonElement.classList.add('liked');
            } else {
                heartIcon.className = 'far fa-heart';
                buttonElement.classList.remove('liked');
            }
            likeSpan.textContent = data.new_count;

            // Update score display
            const scoreEl = document.getElementById('detail-score');
            if (scoreEl && data.new_score !== undefined) {
                scoreEl.textContent = data.new_score;
                scoreEl.style.transform = 'scale(1.3)';
                setTimeout(() => scoreEl.style.transform = 'scale(1)', 300);
            }

            // Heart animation
            heartIcon.style.transform = 'scale(1.3)';
            setTimeout(() => heartIcon.style.transform = 'scale(1)', 200);
        }
    })
    .catch(() => {
        buttonElement.disabled = false;
        buttonElement.style.opacity = '1';
    });
}

// Comment rating with score update
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.comment-rate').forEach(btn => {
        btn.addEventListener('click', function() {
            <?php if (!isset($_SESSION['user_id'])): ?>
                window.location.href = 'login.php';
                return;
            <?php endif; ?>

            const commentId = this.dataset.commentId;
            const sentiment = this.dataset.sentiment;

            this.disabled = true;
            this.style.opacity = '0.6';

            fetch('ajax/rate-comment.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ comment_id: commentId, sentiment: sentiment })
            })
            .then(r => r.json())
            .then(data => {
                this.disabled = false;
                this.style.opacity = '1';

                if (data.success) {
                    // Update thumbs up/down counts
                    const helpfulSpan   = document.querySelector('.helpful-count-' + commentId);
                    const unhelpfulSpan = document.querySelector('.unhelpful-count-' + commentId);
                    if (helpfulSpan)   helpfulSpan.textContent   = data.positive_count;
                    if (unhelpfulSpan) unhelpfulSpan.textContent = data.negative_count;

                    // Toggle active state
                    const commentEl = document.querySelector('[data-comment-id="' + commentId + '"]').closest('.comment-item');
                    const allBtns = commentEl.querySelectorAll('.comment-rate');
                    allBtns.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');

                    // Update artwork score display
                    const scoreEl = document.getElementById('detail-score');
                    if (scoreEl && data.new_score !== undefined) {
                        const oldScore = parseFloat(scoreEl.textContent);
                        scoreEl.textContent = data.new_score;

                        // Flash green if increased, red if decreased
                        if (data.new_score > oldScore) {
                            scoreEl.style.color = '#27ae60';
                        } else if (data.new_score < oldScore) {
                            scoreEl.style.color = '#e74c3c';
                        }
                        setTimeout(() => scoreEl.style.color = '#e74c3c', 1500);
                    }

                    // Toast
                    const msg = sentiment === 'positive' ? '👍 Score increased!' : '👎 Score decreased!';
                    showToast(msg, sentiment === 'positive' ? 'success' : 'info');
                }
            })
            .catch(() => {
                this.disabled = false;
                this.style.opacity = '1';
            });
        });
    });
});

function showToast(message, type) {
    const toast = document.createElement('div');
    toast.style.cssText = `
        position:fixed; bottom:20px; right:20px;
        background:${type === 'success' ? '#27ae60' : '#3498db'};
        color:white; padding:10px 20px; border-radius:8px;
        z-index:9999; font-size:0.85rem; font-weight:600;
        box-shadow:0 4px 12px rgba(0,0,0,0.15);
        animation: slideIn 0.3s ease;
    `;
    toast.textContent = message;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 2500);
}
</script>

<?php require_once 'includes/footer.php'; ?>