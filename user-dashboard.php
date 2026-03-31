<?php
require_once 'includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// If user is admin, redirect to admin panel
if ($_SESSION['role'] == 'admin') {
    header('Location: admin/index.php');
    exit;
}

$page_title = "My Dashboard";
require_once 'includes/navbar.php';

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
?>

<div class="container dashboard-container">
    <!-- Welcome Header -->
    <div class="dashboard-header">
        <div class="welcome-message">
            <h1><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
            <p>Welcome back, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</p>
            <p class="user-role">
                <i class="fas fa-user-tag"></i> 
                <?php echo ucfirst($role); ?>
            </p>
        </div>
        <div class="quick-actions">
            <?php if ($role == 'artist'): ?>
                <a href="upload-artwork.php" class="btn-primary">
                    <i class="fas fa-upload"></i> Upload Artwork
                </a>
            <?php else: ?>
                <a href="artists.php" class="btn-primary">
                    <i class="fas fa-paint-brush"></i> Find Artists
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- User Statistics -->
    <div class="stats-grid">
        <?php
        // Get user-specific stats
        if ($role == 'artist') {
            // Artist stats
            $artist_id = $_SESSION['artist_id'] ?? 0;
            
            // Try to get artist stats
            if ($artist_id) {
                $stats_sql = "SELECT 
                                (SELECT COUNT(*) FROM artworks WHERE artist_id = ?) as total_artworks,
                                (SELECT COUNT(*) FROM commissions WHERE artist_id = ?) as total_commissions,
                                (SELECT SUM(views) FROM artworks WHERE artist_id = ?) as total_views";
                $stmt = mysqli_prepare($conn, $stats_sql);
                mysqli_stmt_bind_param($stmt, "iii", $artist_id, $artist_id, $artist_id);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $stats = mysqli_fetch_assoc($result);
            } else {
                $stats = ['total_artworks' => 0, 'total_commissions' => 0, 'total_views' => 0];
            }
            
            echo '
            <div class="stat-card">
                <div class="stat-icon success">
                    <i class="fas fa-palette"></i>
                </div>
                <div class="stat-info">
                    <h3>' . ($stats['total_artworks'] ?? 0) . '</h3>
                    <p>Your Artworks</p>
                    <a href="my-artworks.php">View</a>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon primary">
                    <i class="fas fa-handshake"></i>
                </div>
                <div class="stat-info">
                    <h3>' . ($stats['total_commissions'] ?? 0) . '</h3>
                    <p>Commissions</p>
                    <a href="commissions.php">View</a>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon info">
                    <i class="fas fa-eye"></i>
                </div>
                <div class="stat-info">
                    <h3>' . ($stats['total_views'] ?? 0) . '</h3>
                    <p>Total Views</p>
                </div>
            </div>';
            
            // Add fourth stat for artists
            $popular_sql = "SELECT title, views FROM artworks WHERE artist_id = ? ORDER BY views DESC LIMIT 1";
            $stmt = mysqli_prepare($conn, $popular_sql);
            mysqli_stmt_bind_param($stmt, "i", $artist_id);
            mysqli_stmt_execute($stmt);
            $popular_result = mysqli_stmt_get_result($stmt);
            $popular = mysqli_fetch_assoc($result);
            
            echo '
            <div class="stat-card">
                <div class="stat-icon warning">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-info">
                    <h3>' . ($popular['views'] ?? 0) . '</h3>
                    <p>Most Viewed</p>
                    <small>' . htmlspecialchars(substr($popular['title'] ?? 'N/A', 0, 20)) . '...</small>
                </div>
            </div>';
            
        } else {
            // Regular user stats
            $stats_sql = "SELECT 
                            (SELECT COUNT(*) FROM commissions WHERE user_id = ?) as total_commissions,
                            (SELECT COUNT(*) FROM artwork_likes WHERE user_id = ?) as liked_artworks";
            $stmt = mysqli_prepare($conn, $stats_sql);
            mysqli_stmt_bind_param($stmt, "ii", $user_id, $user_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $stats = mysqli_fetch_assoc($result);
            
            echo '
            <div class="stat-card">
                <div class="stat-icon primary">
                    <i class="fas fa-handshake"></i>
                </div>
                <div class="stat-info">
                    <h3>' . ($stats['total_commissions'] ?? 0) . '</h3>
                    <p>Your Commissions</p>
                    <a href="commissions.php">View All</a>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon success">
                    <i class="fas fa-heart"></i>
                </div>
                <div class="stat-info">
                    <h3>' . ($stats['liked_artworks'] ?? 0) . '</h3>
                    <p>Liked Artworks</p>
                    <a href="favorites.php">View Favorites</a>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon info">
                    <i class="fas fa-images"></i>
                </div>
                <div class="stat-info">
                    <h3>';
            
            // Get viewed artworks count
            $views_sql = "SELECT COUNT(DISTINCT artwork_id) as viewed_artworks FROM artwork_views WHERE user_id = ?";
            $stmt = mysqli_prepare($conn, $views_sql);
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            mysqli_stmt_execute($stmt);
            $views_result = mysqli_stmt_get_result($stmt);
            $views_data = mysqli_fetch_assoc($result);
            echo $views_data['viewed_artworks'] ?? 0;
            
            echo '</h3>
                    <p>Viewed Artworks</p>
                    <a href="gallery.php">Browse More</a>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon warning">
                    <i class="fas fa-book"></i>
                </div>
                <div class="stat-info">
                    <h3>';
            
            // Get knowledge articles read
            $articles_sql = "SELECT COUNT(*) as articles_read FROM article_views WHERE user_id = ?";
            $stmt = mysqli_prepare($conn, $articles_sql);
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            mysqli_stmt_execute($stmt);
            $articles_result = mysqli_stmt_get_result($stmt);
            $articles_data = mysqli_fetch_assoc($result);
            echo $articles_data['articles_read'] ?? 0;
            
            echo '</h3>
                    <p>Articles Read</p>
                    <a href="knowledge.php">Read More</a>
                </div>
            </div>';
        }
        ?>
    </div>

    <!-- Quick Links -->
    <div class="dashboard-section">
        <h2><i class="fas fa-bolt"></i> Quick Actions</h2>
        <div class="quick-links">
            <?php if ($role == 'artist'): ?>
                <a href="upload-artwork.php" class="quick-link">
                    <i class="fas fa-upload"></i>
                    <span>Upload Artwork</span>
                </a>
                <a href="my-artworks.php" class="quick-link">
                    <i class="fas fa-palette"></i>
                    <span>Manage Artworks</span>
                </a>
                <a href="commissions.php" class="quick-link">
                    <i class="fas fa-handshake"></i>
                    <span>View Commissions</span>
                </a>
                <a href="artist-profile.php?id=<?php echo $_SESSION['artist_id'] ?? ''; ?>" class="quick-link">
                    <i class="fas fa-eye"></i>
                    <span>View Public Profile</span>
                </a>
            <?php else: ?>
                <a href="gallery.php" class="quick-link">
                    <i class="fas fa-images"></i>
                    <span>Browse Gallery</span>
                </a>
                <a href="artists.php" class="quick-link">
                    <i class="fas fa-paint-brush"></i>
                    <span>Find Artists</span>
                </a>
                <a href="commission-request.php" class="quick-link">
                    <i class="fas fa-handshake"></i>
                    <span>Request Commission</span>
                </a>
                <a href="knowledge.php" class="quick-link">
                    <i class="fas fa-book"></i>
                    <span>Learn About Art</span>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="dashboard-section">
        <h2><i class="fas fa-history"></i> <?php echo $role == 'artist' ? 'Your Recent Artworks' : 'Recently Added Artworks'; ?></h2>
        <div class="recent-activity">
            <?php
            if ($role == 'artist') {
                // Show artist's own recent artworks
                $artist_id = $_SESSION['artist_id'] ?? 0;
                $artworks_sql = "SELECT a.* FROM artworks a 
                                 WHERE a.artist_id = ? 
                                 ORDER BY a.created_at DESC 
                                 LIMIT 4";
                $stmt = mysqli_prepare($conn, $artworks_sql);
                mysqli_stmt_bind_param($stmt, "i", $artist_id);
                mysqli_stmt_execute($stmt);
                $artworks_result = mysqli_stmt_get_result($stmt);
            } else {
                // Show general recent artworks for regular users
                $artworks_sql = "SELECT a.*, u.username 
                                 FROM artworks a 
                                 JOIN artists ar ON a.artist_id = ar.artist_id 
                                 JOIN users u ON ar.user_id = u.user_id 
                                 ORDER BY a.created_at DESC 
                                 LIMIT 4";
                $artworks_result = mysqli_query($conn, $artworks_sql);
            }
            
            if (mysqli_num_rows($artworks_result) > 0) {
                echo '<div class="artworks-grid">';
                while ($artwork = mysqli_fetch_assoc($artworks_result)) {
                    $artist_name = '';
                    if ($role != 'artist') {
                        $artist_name = $artwork['username'];
                    }
                    
                    echo '<div class="artwork-card-small">
                            <div class="artwork-image">
                                <img src="uploads/artworks/' . htmlspecialchars($artwork['image_path']) . '" 
                                     alt="' . htmlspecialchars($artwork['title']) . '">
                            </div>
                            <div class="artwork-info">
                                <h4>' . htmlspecialchars($artwork['title']) . '</h4>';
                    
                    if ($role != 'artist' && !empty($artist_name)) {
                        echo '<p><i class="fas fa-paint-brush"></i> ' . htmlspecialchars($artist_name) . '</p>';
                    }
                    
                    echo '<div class="artwork-stats">
                                    <span><i class="fas fa-eye"></i> ' . $artwork['views'] . '</span>
                                    <span><i class="fas fa-heart"></i> ' . $artwork['likes'] . '</span>
                                </div>
                                <a href="artwork-detail.php?id=' . $artwork['artwork_id'] . '" class="view-link">
                                    View Details <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                          </div>';
                }
                echo '</div>';
                echo '<div class="view-all-link">
                        <a href="' . ($role == 'artist' ? 'my-artworks.php' : 'gallery.php') . '">View All <i class="fas fa-arrow-right"></i></a>
                      </div>';
            } else {
                if ($role == 'artist') {
                    echo '<div class="no-activity">
                            <i class="fas fa-palette"></i>
                            <h4>No Artworks Yet</h4>
                            <p>Start by uploading your first Buddhist artwork</p>
                            <a href="upload-artwork.php" class="btn-primary">
                                <i class="fas fa-upload"></i> Upload Artwork
                            </a>
                          </div>';
                } else {
                    echo '<p class="no-activity">No recent artworks found. Start exploring the gallery!</p>';
                }
            }
            ?>
        </div>
    </div>
    
    <!-- Quick Tips -->
    <div class="dashboard-section">
        <h2><i class="fas fa-lightbulb"></i> Quick Tips</h2>
        <div class="tips-grid">
            <?php if ($role == 'artist'): ?>
                <div class="tip">
                    <i class="fas fa-camera"></i>
                    <h4>High-Quality Photos</h4>
                    <p>Use good lighting and clear images to showcase artwork details</p>
                </div>
                <div class="tip">
                    <i class="fas fa-book"></i>
                    <h4>Detailed Descriptions</h4>
                    <p>Explain the cultural significance and symbolism of your artwork</p>
                </div>
                <div class="tip">
                    <i class="fas fa-tags"></i>
                    <h4>Proper Categorization</h4>
                    <p>Choose the right category for better discovery by art enthusiasts</p>
                </div>
                <div class="tip">
                    <i class="fas fa-hashtag"></i>
                    <h4>Update Regularly</h4>
                    <p>Add new artworks to keep your portfolio fresh and engaging</p>
                </div>
            <?php else: ?>
                <div class="tip">
                    <i class="fas fa-heart"></i>
                    <h4>Like Artworks</h4>
                    <p>Like artworks you enjoy to help our algorithm recommend similar ones</p>
                </div>
                <div class="tip">
                    <i class="fas fa-comment"></i>
                    <h4>Leave Comments</h4>
                    <p>Share your thoughts and appreciation for artists' work</p>
                </div>
                <div class="tip">
                    <i class="fas fa-handshake"></i>
                    <h4>Request Commissions</h4>
                    <p>Commission custom artworks directly from traditional artists</p>
                </div>
                <div class="tip">
                    <i class="fas fa-book-open"></i>
                    <h4>Learn About Art</h4>
                    <p>Explore our knowledge base to understand Buddhist art better</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
/* Dashboard Styles */
.dashboard-container {
    padding: 2rem 0;
}

.dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.welcome-message h1 {
    color: var(--primary-color);
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 10px;
}

.welcome-message p {
    color: #666;
    margin-bottom: 0.3rem;
}

.user-role {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: #e8f4fc;
    color: var(--info-color);
    padding: 0.3rem 0.8rem;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: 600;
}

.quick-actions {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.btn-primary {
    padding: 0.8rem 1.5rem;
    background: var(--secondary-color);
    color: white;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s;
}

.btn-primary:hover {
    background: #c0392b;
    transform: translateY(-2px);
}

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: white;
    padding: 1.5rem;
    border-radius: 10px;
    box-shadow: 0 3px 10px rgba(0,0,0,0.08);
    display: flex;
    align-items: center;
    gap: 1.5rem;
    transition: transform 0.3s;
}

.stat-card:hover {
    transform: translateY(-5px);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: white;
}

.stat-icon.primary { background: linear-gradient(135deg, #667eea, #764ba2); }
.stat-icon.success { background: linear-gradient(135deg, #4facfe, #00f2fe); }
.stat-icon.info { background: linear-gradient(135deg, #43e97b, #38f9d7); }
.stat-icon.warning { background: linear-gradient(135deg, #fa709a, #fee140); }

.stat-info h3 {
    font-size: 2rem;
    color: var(--primary-color);
    margin-bottom: 0.3rem;
}

.stat-info p {
    color: #666;
    margin-bottom: 0.5rem;
}

.stat-info a {
    color: var(--secondary-color);
    text-decoration: none;
    font-size: 0.9rem;
    font-weight: 600;
}

.stat-info a:hover {
    text-decoration: underline;
}

.stat-info small {
    color: #888;
    font-size: 0.8rem;
    display: block;
    margin-top: 0.3rem;
}

/* Dashboard Sections */
.dashboard-section {
    background: white;
    padding: 1.5rem;
    border-radius: 10px;
    box-shadow: 0 3px 10px rgba(0,0,0,0.08);
    margin-bottom: 2rem;
}

.dashboard-section h2 {
    color: var(--primary-color);
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 10px;
}

/* Quick Links */
.quick-links {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.quick-link {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    padding: 1.5rem;
    background: #f8f9fa;
    border-radius: 8px;
    text-decoration: none;
    color: #333;
    transition: all 0.3s;
}

.quick-link:hover {
    background: #e9ecef;
    transform: translateY(-3px);
}

.quick-link i {
    font-size: 2rem;
    color: var(--secondary-color);
    margin-bottom: 1rem;
}

.quick-link span {
    font-weight: 600;
}

/* Recent Activity */
.artworks-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 1rem;
}

.artwork-card-small {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    transition: transform 0.3s;
}

.artwork-card-small:hover {
    transform: translateY(-3px);
}

.artwork-card-small .artwork-image {
    height: 150px;
    overflow: hidden;
}

.artwork-card-small img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s;
}

.artwork-card-small:hover img {
    transform: scale(1.05);
}

.artwork-card-small .artwork-info {
    padding: 1rem;
}

.artwork-card-small h4 {
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
    color: var(--primary-color);
}

.artwork-card-small p {
    font-size: 0.8rem;
    color: #666;
    margin-bottom: 0.8rem;
    display: flex;
    align-items: center;
    gap: 5px;
}

.artwork-card-small p i {
    color: var(--secondary-color);
}

.artwork-stats {
    display: flex;
    gap: 1rem;
    margin-bottom: 0.8rem;
    font-size: 0.8rem;
    color: #888;
}

.artwork-stats i {
    margin-right: 3px;
}

.view-link {
    color: var(--secondary-color);
    text-decoration: none;
    font-size: 0.85rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 5px;
}

.view-link:hover {
    gap: 8px;
}

.view-all-link {
    text-align: center;
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid #eee;
}

.view-all-link a {
    color: var(--secondary-color);
    text-decoration: none;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.view-all-link a:hover {
    gap: 12px;
}

.no-activity {
    text-align: center;
    padding: 3rem;
    color: #666;
}

.no-activity i {
    font-size: 3rem;
    color: #ddd;
    margin-bottom: 1rem;
}

.no-activity h4 {
    color: var(--primary-color);
    margin-bottom: 0.5rem;
}

.no-activity p {
    margin-bottom: 1.5rem;
}

/* Tips Grid */
.tips-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
}

.tip {
    text-align: center;
    padding: 1.5rem;
    background: #f8f9fa;
    border-radius: 8px;
}

.tip i {
    font-size: 2rem;
    color: var(--accent-color);
    margin-bottom: 1rem;
}

.tip h4 {
    color: var(--primary-color);
    margin-bottom: 0.5rem;
}

.tip p {
    color: #666;
    font-size: 0.9rem;
    line-height: 1.5;
}

/* Responsive */
@media (max-width: 768px) {
    .dashboard-header {
        flex-direction: column;
        text-align: center;
    }
    
    .stats-grid {
        grid-template-columns: 1fr 1fr;
    }
    
    .quick-links {
        grid-template-columns: 1fr 1fr;
    }
    
    .artworks-grid {
        grid-template-columns: 1fr 1fr;
    }
    
    .tips-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 480px) {
    .stats-grid,
    .quick-links,
    .artworks-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php require_once 'includes/footer.php'; ?>