<?php
// favorites.php - Show user's liked artworks
require_once 'includes/config.php';
require_once 'includes/navbar.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$page_title = "My Favorite Artworks";
$user_id = $_SESSION['user_id'];

// Handle unlike action
if (isset($_GET['unlike_id'])) {
    $artwork_id = intval($_GET['unlike_id']);
    
    $sql = "DELETE FROM artwork_likes WHERE user_id = ? AND artwork_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $artwork_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $update_sql = "UPDATE artworks SET likes = GREATEST(likes - 1, 0) WHERE artwork_id = ?";
        $update_stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($update_stmt, "i", $artwork_id);
        mysqli_stmt_execute($update_stmt);
        
        $_SESSION['message'] = "Artwork removed from favorites";
        $_SESSION['message_type'] = 'success';
    }
    
    header('Location: favorites.php');
    exit;
}

// Get user's liked artworks
$sql = "SELECT a.*, u.username, u.full_name as artist_name,
               al.created_at as liked_date
        FROM artwork_likes al
        JOIN artworks a ON al.artwork_id = a.artwork_id
        JOIN artists ar ON a.artist_id = ar.artist_id
        JOIN users u ON ar.user_id = u.user_id
        WHERE al.user_id = ?
        ORDER BY al.created_at DESC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$total_favorites = mysqli_num_rows($result);
?>

<div class="favorites-container">
    <!-- Page Header -->
    <div class="page-header">
        <h1><i class="fas fa-heart"></i> My Favorites</h1>
        <p><?php echo $total_favorites; ?> artwork<?php echo $total_favorites != 1 ? 's' : ''; ?></p>
    </div>
    
    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['message_type']; ?>">
            <?php 
            echo $_SESSION['message'];
            unset($_SESSION['message']);
            unset($_SESSION['message_type']);
            ?>
        </div>
    <?php endif; ?>
    
    <!-- Favorites Grid -->
    <?php if ($total_favorites > 0): ?>
        <div class="favorites-grid">
            <?php while ($artwork = mysqli_fetch_assoc($result)): 
                $artist_display = !empty($artwork['artist_name']) ? $artwork['artist_name'] : $artwork['username'];
                ?>
                <div class="favorite-card">
                    <div class="favorite-image">
                        <img src="uploads/artworks/<?php echo htmlspecialchars($artwork['image_path']); ?>" 
                             alt="<?php echo htmlspecialchars($artwork['title']); ?>">
                        <div class="favorite-overlay">
                            <a href="artwork-detail.php?id=<?php echo $artwork['artwork_id']; ?>" class="btn-view">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="?unlike_id=<?php echo $artwork['artwork_id']; ?>" class="btn-unlike" 
                               onclick="return confirm('Remove from favorites?')">
                                <i class="fas fa-trash-alt"></i>
                            </a>
                        </div>
                    </div>
                    <div class="favorite-info">
                        <h3><?php echo htmlspecialchars($artwork['title']); ?></h3>
                        <p class="artist"><?php echo htmlspecialchars($artist_display); ?></p>
                        <div class="favorite-meta">
                            <span class="category"><?php echo $artwork['category']; ?></span>
                            <div class="stats">
                                <span><i class="fas fa-eye"></i> <?php echo $artwork['views']; ?></span>
                                <span><i class="fas fa-heart"></i> <?php echo $artwork['likes']; ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="no-favorites">
            <i class="fas fa-heart-broken"></i>
            <h3>No Favorites Yet</h3>
            <p>Like artworks you enjoy and they'll appear here</p>
            <a href="gallery.php" class="btn-primary">Browse Gallery</a>
        </div>
    <?php endif; ?>
</div>

<style>
.favorites-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 1.5rem 1rem;
}

/* Page Header */
.page-header {
    text-align: center;
    margin-bottom: 1.5rem;
}

.page-header h1 {
    font-size: 1.5rem;
    color: #2c3e50;
    margin-bottom: 0.2rem;
}

.page-header h1 i {
    color: #e74c3c;
    margin-right: 8px;
}

.page-header p {
    color: #7f8c8d;
    font-size: 0.85rem;
}

/* Alert */
.alert {
    padding: 0.6rem 1rem;
    border-radius: 6px;
    margin-bottom: 1.5rem;
    text-align: center;
    font-size: 0.85rem;
}

.alert-success {
    background: #d4edda;
    color: #155724;
}

.alert-error {
    background: #f8d7da;
    color: #721c24;
}

/* Favorites Grid - 3 columns, very compact */
.favorites-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.favorite-card {
    background: white;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    transition: all 0.2s ease;
    border: 1px solid #f0f0f0;
}

.favorite-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    border-color: #f1c40f;
}

.favorite-image {
    position: relative;
    height: 140px;
    overflow: hidden;
    background: #f5f5f5;
}

.favorite-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.2s ease;
}

.favorite-card:hover .favorite-image img {
    transform: scale(1.02);
}

.favorite-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.6rem;
    opacity: 0;
    transition: opacity 0.2s;
}

.favorite-card:hover .favorite-overlay {
    opacity: 1;
}

.btn-view, .btn-unlike {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    transition: all 0.2s;
    font-size: 0.8rem;
}

.btn-view {
    background: white;
    color: #e74c3c;
}

.btn-unlike {
    background: rgba(0,0,0,0.7);
    color: white;
}

.btn-view:hover, .btn-unlike:hover {
    transform: scale(1.05);
}

.btn-view:hover {
    background: #f1c40f;
    color: #2c3e50;
}

.btn-unlike:hover {
    background: #e74c3c;
}

.favorite-info {
    padding: 0.6rem;
}

.favorite-info h3 {
    font-size: 0.85rem;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 0.2rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.artist {
    font-size: 0.65rem;
    color: #7f8c8d;
    margin-bottom: 0.4rem;
}

.favorite-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.65rem;
    gap: 0.5rem;
}

.category {
    background: #f8f9fa;
    padding: 0.15rem 0.5rem;
    border-radius: 10px;
    color: #e74c3c;
    font-size: 0.6rem;
    white-space: nowrap;
}

.stats {
    display: flex;
    gap: 0.5rem;
    color: #95a5a6;
}

.stats span {
    display: flex;
    align-items: center;
    gap: 3px;
}

.stats i {
    font-size: 0.6rem;
}

/* Empty State */
.no-favorites {
    text-align: center;
    padding: 2rem;
    background: white;
    border-radius: 12px;
    margin: 1rem 0;
}

.no-favorites i {
    font-size: 2.5rem;
    color: #ddd;
    margin-bottom: 0.8rem;
}

.no-favorites h3 {
    font-size: 1.2rem;
    color: #2c3e50;
    margin-bottom: 0.3rem;
}

.no-favorites p {
    color: #7f8c8d;
    margin-bottom: 1rem;
    font-size: 0.85rem;
}

.btn-primary {
    display: inline-block;
    background: #e74c3c;
    color: white;
    padding: 0.4rem 1rem;
    border-radius: 20px;
    text-decoration: none;
    font-size: 0.8rem;
    font-weight: 500;
    transition: all 0.2s;
}

.btn-primary:hover {
    background: #c0392b;
    transform: translateY(-1px);
}

/* Responsive */
@media (max-width: 900px) {
    .favorites-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.8rem;
    }
}

@media (max-width: 600px) {
    .favorites-grid {
        grid-template-columns: 1fr;
    }
    
    .favorite-image {
        height: 160px;
    }
}
</style>

<?php require_once 'includes/footer.php'; ?>