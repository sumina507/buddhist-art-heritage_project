<?php
// artwork-detail.php
require_once 'includes/config.php';
require_once 'includes/recommendation.php';
require_once 'includes/navbar.php';

$artwork_id = $_GET['id'] ?? 0;
$page_title = "Artwork Details";

// SMARTER VIEW COUNTING
if ($artwork_id) {
    if (!isset($_SESSION['viewed_artworks'])) {
        $_SESSION['viewed_artworks'] = [];
    }
    
    if (!in_array($artwork_id, $_SESSION['viewed_artworks'])) {
        $sql = "UPDATE artworks SET views = views + 1 WHERE artwork_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $artwork_id);
        mysqli_stmt_execute($stmt);
        $_SESSION['viewed_artworks'][] = $artwork_id;
    }
}

$sql = "SELECT a.*, u.username, u.full_name as artist_name, u.profile_image, ar.artist_id, ar.specialization, ar.experience_years
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

// Get like count
$like_sql = "SELECT COUNT(*) as like_count FROM artwork_likes WHERE artwork_id = ?";
$stmt = mysqli_prepare($conn, $like_sql);
mysqli_stmt_bind_param($stmt, "i", $artwork_id);
mysqli_stmt_execute($stmt);
$like_result = mysqli_stmt_get_result($stmt);
$like_data = mysqli_fetch_assoc($like_result);
$like_count = $like_data['like_count'] ?? 0;

// Check if user liked
$user_liked = false;
if (isset($_SESSION['user_id'])) {
    $check_sql = "SELECT * FROM artwork_likes WHERE artwork_id = ? AND user_id = ?";
    $stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($stmt, "ii", $artwork_id, $_SESSION['user_id']);
    mysqli_stmt_execute($stmt);
    $check_result = mysqli_stmt_get_result($stmt);
    $user_liked = mysqli_num_rows($check_result) > 0;
}

// Handle like action
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['like_action'])) {
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['message'] = "Please login to like artwork";
        $_SESSION['message_type'] = 'error';
        header("Location: artwork-detail.php?id=$artwork_id");
        exit;
    }
    
    if ($_POST['like_action'] == 'like') {
        $sql = "INSERT IGNORE INTO artwork_likes (user_id, artwork_id) VALUES (?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $_SESSION['user_id'], $artwork_id);
        mysqli_stmt_execute($stmt);
        $update_sql = "UPDATE artworks SET likes = likes + 1 WHERE artwork_id = ?";
        $update_stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($update_stmt, "i", $artwork_id);
        mysqli_stmt_execute($update_stmt);
        $user_liked = true;
        $like_count++;
    } else {
        $sql = "DELETE FROM artwork_likes WHERE user_id = ? AND artwork_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $_SESSION['user_id'], $artwork_id);
        mysqli_stmt_execute($stmt);
        $update_sql = "UPDATE artworks SET likes = GREATEST(likes - 1, 0) WHERE artwork_id = ?";
        $update_stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($update_stmt, "i", $artwork_id);
        mysqli_stmt_execute($update_stmt);
        $user_liked = false;
        $like_count = max(0, $like_count - 1);
    }
    
    header("Location: artwork-detail.php?id=$artwork_id");
    exit;
}

// Handle comment submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['comment'])) {
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['message'] = "Please login to comment";
        $_SESSION['message_type'] = 'error';
        header("Location: artwork-detail.php?id=$artwork_id");
        exit;
    }
    
    $comment = trim($_POST['comment']);
    if (!empty($comment)) {
        $sql = "INSERT INTO artwork_comments (user_id, artwork_id, comment) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "iis", $_SESSION['user_id'], $artwork_id, $comment);
        mysqli_stmt_execute($stmt);
        $_SESSION['message'] = "Comment added successfully!";
        $_SESSION['message_type'] = 'success';
        header("Location: artwork-detail.php?id=$artwork_id");
        exit;
    }
}

// Get comments
$comment_sql = "SELECT ac.*, u.username, u.profile_image 
                FROM artwork_comments ac
                JOIN users u ON ac.user_id = u.user_id
                WHERE ac.artwork_id = ?
                ORDER BY ac.created_at DESC";
$stmt = mysqli_prepare($conn, $comment_sql);
mysqli_stmt_bind_param($stmt, "i", $artwork_id);
mysqli_stmt_execute($stmt);
$comments_result = mysqli_stmt_get_result($stmt);

// Get similar artworks
$recommender = new ArtworkRecommender($conn);
$similar_artworks = $recommender->getSimilarArtworks($artwork_id, 4);
?>

<div class="container artwork-detail-container">
    <!-- Breadcrumb -->
    <nav class="breadcrumb">
        <a href="index.php">Home</a> &gt;
        <a href="gallery.php">Gallery</a> &gt;
        <span><?php echo htmlspecialchars($artwork['title'] ?? ''); ?></span>
    </nav>
    
    <!-- Main Artwork -->
    <div class="artwork-main">
        <div class="artwork-image-large">
            <img src="uploads/artworks/<?php echo htmlspecialchars($artwork['image_path'] ?? ''); ?>" 
                 alt="<?php echo htmlspecialchars($artwork['title'] ?? 'Artwork'); ?>">
            <form method="POST" class="like-form">
                <input type="hidden" name="like_action" value="<?php echo $user_liked ? 'unlike' : 'like'; ?>">
                <button type="submit" class="like-btn <?php echo $user_liked ? 'liked' : ''; ?>">
                    <i class="<?php echo $user_liked ? 'fas' : 'far'; ?> fa-heart"></i>
                    <span><?php echo $like_count; ?></span>
                </button>
            </form>
        </div>
        
        <div class="artwork-info-detail">
            <h1><?php echo htmlspecialchars($artwork['title'] ?? ''); ?></h1>
            
            <div class="artist-info">
                <div class="artist-avatar-small">
                    <img src="uploads/profiles/<?php 
                        $profile_image = isset($artwork['profile_image']) && !empty($artwork['profile_image']) 
                            ? $artwork['profile_image'] 
                            : 'default.jpg';
                        echo htmlspecialchars($profile_image); 
                    ?>" alt="Artist">
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
                    <span><?php echo $artwork['views'] ?? 0; ?> views</span>
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
            
            <div class="commission-cta">
                <p>Want something similar?</p>
                <a href="commission-request.php?artist_id=<?php echo $artwork['artist_id'] ?? 0; ?>" class="btn-commission">
                    <i class="fas fa-handshake"></i> Request Custom Commission
                </a>
            </div>
        </div>
    </div>
    
    <!-- Comments Section - Compact -->
    <div class="comments-section">
        <div class="comments-header">
            <h3><i class="fas fa-comments"></i> Comments <span>(<?php echo mysqli_num_rows($comments_result); ?>)</span></h3>
        </div>
        
        <?php if (isset($_SESSION['user_id'])): ?>
        <form method="POST" class="comment-form">
            <div class="comment-input">
                <textarea name="comment" placeholder="Add a comment..." required></textarea>
                <button type="submit" class="btn-submit">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </form>
        <?php else: ?>
        <p class="login-prompt"><a href="login.php">Login</a> to comment</p>
        <?php endif; ?>
        
        <div class="comments-list">
            <?php if (mysqli_num_rows($comments_result) > 0): ?>
                <?php while ($comment = mysqli_fetch_assoc($comments_result)): ?>
                <div class="comment-item">
                    <img src="uploads/profiles/<?php echo htmlspecialchars($comment['profile_image'] ?? 'default.jpg'); ?>" 
                         alt="<?php echo htmlspecialchars($comment['username'] ?? 'User'); ?>">
                    <div class="comment-content">
                        <div class="comment-meta">
                            <strong><?php echo htmlspecialchars($comment['username'] ?? 'Anonymous'); ?></strong>
                            <span><?php echo isset($comment['created_at']) ? date('M j, Y', strtotime($comment['created_at'])) : 'Unknown'; ?></span>
                        </div>
                        <p><?php echo nl2br(htmlspecialchars($comment['comment'] ?? '')); ?></p>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="no-comments">No comments yet. Be the first!</p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Similar Artworks - Simple -->
    <?php if (!empty($similar_artworks)): ?>
    <div class="similar-artworks">
        <h3>You May Also Like</h3>
        <div class="similar-grid">
            <?php foreach ($similar_artworks as $similar): ?>
            <a href="artwork-detail.php?id=<?php echo $similar['artwork_id'] ?? 0; ?>" class="similar-card">
                <img src="uploads/artworks/<?php echo htmlspecialchars($similar['image_path'] ?? 'default.jpg'); ?>" 
                     alt="<?php echo htmlspecialchars($similar['title'] ?? 'Artwork'); ?>">
                <div class="similar-info">
                    <h4><?php echo htmlspecialchars($similar['title'] ?? 'Untitled'); ?></h4>
                    <p><?php echo htmlspecialchars($similar['category'] ?? 'Other'); ?></p>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
/* Your existing CSS stays the same */
.artwork-detail-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem 1rem;
}

.breadcrumb {
    margin-bottom: 2rem;
    font-size: 0.9rem;
    color: #666;
}

.breadcrumb a {
    color: #e74c3c;
    text-decoration: none;
}

.breadcrumb a:hover {
    text-decoration: underline;
}

/* Main Layout */
.artwork-main {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
    margin-bottom: 2rem;
}

.artwork-image-large {
    position: relative;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
}

.artwork-image-large img {
    width: 100%;
    height: auto;
    display: block;
}

.like-form {
    position: absolute;
    bottom: 20px;
    right: 20px;
}

.like-btn {
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
}

.like-btn:hover {
    transform: scale(1.05);
}

.like-btn.liked {
    background: #e74c3c;
    color: white;
}

/* Artwork Info */
.artwork-info-detail h1 {
    font-size: 1.8rem;
    color: #2c3e50;
    margin-bottom: 1rem;
}

.artist-info {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #eee;
}

.artist-avatar-small {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    overflow: hidden;
}

.artist-avatar-small img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border: 2px solid #f1c40f;
}

.artist-info h3 {
    font-size: 1.1rem;
    margin-bottom: 0.2rem;
    color: #2c3e50;
}

.specialization {
    font-size: 0.8rem;
    color: #e74c3c;
}

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

.btn-view-artist:hover {
    background: #2980b9;
    transform: translateY(-2px);
}

.artwork-meta-detail {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    margin-bottom: 1.5rem;
    padding: 0.8rem;
    background: #f8f9fa;
    border-radius: 8px;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.85rem;
    color: #666;
}

.meta-item i {
    color: #e74c3c;
    width: 18px;
}

.artwork-description h3,
.artwork-materials h3,
.artwork-symbolism h3 {
    font-size: 1rem;
    color: #2c3e50;
    margin-bottom: 0.5rem;
    margin-top: 1rem;
}

.artwork-description p,
.artwork-materials p,
.artwork-symbolism p {
    font-size: 0.9rem;
    line-height: 1.6;
    color: #555;
}

.commission-cta {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    padding: 1.2rem;
    border-radius: 10px;
    text-align: center;
    margin-top: 1.5rem;
}

.commission-cta p {
    margin-bottom: 0.8rem;
    font-size: 0.9rem;
}

.btn-commission {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 0.6rem 1.5rem;
    background: white;
    color: #667eea;
    text-decoration: none;
    border-radius: 40px;
    font-weight: 600;
    font-size: 0.9rem;
    transition: all 0.2s;
}

.btn-commission:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

/* Comments Section - Compact */
.comments-section {
    background: white;
    border-radius: 12px;
    padding: 1rem;
    margin-bottom: 2rem;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.comments-header h3 {
    font-size: 1rem;
    margin-bottom: 1rem;
    color: #2c3e50;
}

.comments-header h3 span {
    color: #999;
    font-weight: normal;
}

.comment-form {
    margin-bottom: 1.5rem;
}

.comment-input {
    display: flex;
    gap: 0.5rem;
}

.comment-input textarea {
    flex: 1;
    padding: 0.6rem;
    border: 1px solid #e9ecef;
    border-radius: 20px;
    resize: none;
    font-size: 0.85rem;
    min-height: 40px;
}

.comment-input textarea:focus {
    outline: none;
    border-color: #e74c3c;
}

.btn-submit {
    background: #e74c3c;
    color: white;
    border: none;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-submit:hover {
    background: #c0392b;
    transform: scale(1.05);
}

.comments-list {
    max-height: 300px;
    overflow-y: auto;
}

.comment-item {
    display: flex;
    gap: 0.8rem;
    padding: 0.8rem 0;
    border-bottom: 1px solid #f0f0f0;
}

.comment-item img {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    object-fit: cover;
}

.comment-content {
    flex: 1;
}

.comment-meta {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.3rem;
}

.comment-meta strong {
    font-size: 0.8rem;
    color: #2c3e50;
}

.comment-meta span {
    font-size: 0.7rem;
    color: #999;
}

.comment-content p {
    font-size: 0.8rem;
    color: #555;
    line-height: 1.4;
    margin: 0;
}

.login-prompt {
    text-align: center;
    padding: 1rem;
    font-size: 0.85rem;
    background: #f8f9fa;
    border-radius: 8px;
}

.login-prompt a {
    color: #e74c3c;
    font-weight: 600;
    text-decoration: none;
}

.no-comments {
    text-align: center;
    padding: 1rem;
    color: #999;
    font-size: 0.8rem;
}

/* Similar Artworks - Simple */
.similar-artworks {
    margin-top: 1rem;
}

.similar-artworks h3 {
    font-size: 1rem;
    color: #2c3e50;
    margin-bottom: 1rem;
}

.similar-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    gap: 1rem;
}

.similar-card {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    text-decoration: none;
    transition: all 0.2s;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.similar-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.similar-card img {
    width: 100%;
    height: 100px;
    object-fit: cover;
}

.similar-info {
    padding: 0.5rem;
}

.similar-info h4 {
    font-size: 0.75rem;
    color: #2c3e50;
    margin-bottom: 0.2rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.similar-info p {
    font-size: 0.7rem;
    color: #999;
    margin: 0;
}

/* Responsive */
@media (max-width: 768px) {
    .artwork-main {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .artist-info {
        flex-wrap: wrap;
    }
    
    .btn-view-artist {
        margin-left: 0;
    }
    
    .similar-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 480px) {
    .artwork-meta-detail {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .similar-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php require_once 'includes/footer.php'; ?> 