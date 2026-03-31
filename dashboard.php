<?php
require_once 'includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Redirect based on role
switch ($_SESSION['role']) {
    case 'admin':
        header('Location: admin/index.php');
        exit;
        
    case 'artist':
        header('Location: artist-dashboard.php');
        exit;
        
    default:
        $page_title = "My Dashboard";
        break;
}

require_once 'includes/navbar.php';
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Get user data
$user_sql = "SELECT * FROM users WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $user_sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$user_result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($user_result);

// Get stats
$stats_sql = "SELECT 
                (SELECT COUNT(*) FROM commissions WHERE user_id = ?) as total_commissions,
                (SELECT COUNT(*) FROM artwork_likes WHERE user_id = ?) as liked_artworks,
                (SELECT COUNT(*) FROM artwork_comments WHERE user_id = ?) as total_comments";
$stmt = mysqli_prepare($conn, $stats_sql);
mysqli_stmt_bind_param($stmt, "iii", $user_id, $user_id, $user_id);
mysqli_stmt_execute($stmt);
$stats_result = mysqli_stmt_get_result($stmt);
$stats = mysqli_fetch_assoc($stats_result);
?>

<div class="dashboard-container">
    <!-- Simple Welcome -->
    <div class="welcome-bar">
        <div class="welcome-text">
            <h1>My Dashboard </h1>
        </div>
        <div class="welcome-avatar">
            <img src="uploads/profiles/<?php echo htmlspecialchars($user['profile_image'] ?? 'default.jpg'); ?>" alt="Profile">
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon commissions">
                <i class="fas fa-handshake"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo $stats['total_commissions'] ?? 0; ?></h3>
                <p>Commissions</p>
                <a href="commissions.php">View →</a>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon likes">
                <i class="fas fa-heart"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo $stats['liked_artworks'] ?? 0; ?></h3>
                <p>Liked Artworks</p>
                <a href="favorites.php">View →</a>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon comments">
                <i class="fas fa-comment"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo $stats['total_comments'] ?? 0; ?></h3>
                <p>Comments</p>
                <a href="gallery.php">Browse →</a>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon member">
                <i class="fas fa-calendar-alt"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo date('M Y', strtotime($user['created_at'])); ?></h3>
                <p>Member Since</p>
                <a href="profile.php">Edit →</a>
            </div>
        </div>
    </div>

    <!-- Recent Artworks -->
    <div class="recent-section">
        <div class="section-header">
            <h2><i class="fas fa-clock"></i> Recent Artworks</h2>
            <a href="gallery.php" class="view-all">View All <i class="fas fa-arrow-right"></i></a>
        </div>
        
        <div class="artworks-grid">
            <?php
            $recent_sql = "SELECT a.*, u.username, u.full_name 
                           FROM artworks a 
                           JOIN artists ar ON a.artist_id = ar.artist_id
                           JOIN users u ON ar.user_id = u.user_id
                           WHERE ar.status = 'approved'
                           ORDER BY a.created_at DESC LIMIT 4";
            $recent = mysqli_query($conn, $recent_sql);
            
            if (mysqli_num_rows($recent) > 0) {
                while ($artwork = mysqli_fetch_assoc($recent)) {
                    $artist_name = !empty($artwork['full_name']) ? $artwork['full_name'] : $artwork['username'];
                    ?>
                    <div class="artwork-card">
                        <div class="artwork-image">
                            <img src="uploads/artworks/<?php echo htmlspecialchars($artwork['image_path']); ?>" 
                                 alt="<?php echo htmlspecialchars($artwork['title']); ?>">
                            <div class="artwork-overlay">
                                <a href="artwork-detail.php?id=<?php echo $artwork['artwork_id']; ?>" class="quick-view">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            </div>
                        </div>
                        <div class="artwork-info">
                            <h4><?php echo htmlspecialchars($artwork['title']); ?></h4>
                            <p class="artist">by <?php echo htmlspecialchars($artist_name); ?></p>
                            <div class="artwork-stats">
                                <span><i class="fas fa-eye"></i> <?php echo $artwork['views']; ?></span>
                                <span><i class="fas fa-heart"></i> <?php echo $artwork['likes']; ?></span>
                            </div>
                        </div>
                    </div>
                    <?php
                }
            } else {
                echo '<div class="no-artworks">No artworks available yet.</div>';
            }
            ?>
        </div>
    </div>
</div>

<style>
:root {
    --primary: #2c3e50;
    --accent: #e74c3c;
    --gold: #f1c40f;
    --gray-light: #f8f9fa;
    --text-light: #6c757d;
}

.dashboard-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem 1.5rem;
}

/* Welcome Bar */
.welcome-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 2px solid #e9ecef;
}

.welcome-text h1 {
    font-size: 1.8rem;
    color: var(--primary);
    margin-bottom: 0.3rem;
}

.welcome-text p {
    color: var(--text-light);
    font-size: 0.95rem;
}

.welcome-avatar {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    border: 3px solid var(--gold);
    overflow: hidden;
}

.welcome-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* Stats Cards */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2.5rem;
}

.stat-card {
    background: white;
    padding: 1.2rem;
    border-radius: 16px;
    display: flex;
    align-items: center;
    gap: 1rem;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    transition: all 0.3s;
}

.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.1);
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    color: white;
}

.stat-icon.commissions { background: linear-gradient(135deg, #667eea, #764ba2); }
.stat-icon.likes { background: linear-gradient(135deg, #fa709a, #fee140); }
.stat-icon.comments { background: linear-gradient(135deg, #4facfe, #00f2fe); }
.stat-icon.member { background: linear-gradient(135deg, #43e97b, #38f9d7); }

.stat-info h3 {
    font-size: 1.8rem;
    font-weight: 700;
    color: var(--primary);
    margin-bottom: 0.2rem;
}

.stat-info p {
    color: var(--text-light);
    font-size: 0.85rem;
    margin-bottom: 0.3rem;
}

.stat-info a {
    color: var(--accent);
    text-decoration: none;
    font-size: 0.8rem;
    font-weight: 500;
}

/* Recent Artworks */
.recent-section {
    margin-top: 1rem;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.section-header h2 {
    font-size: 1.3rem;
    color: var(--primary);
    display: flex;
    align-items: center;
    gap: 8px;
}

.view-all {
    color: var(--accent);
    text-decoration: none;
    font-size: 0.9rem;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 5px;
}

.view-all:hover {
    gap: 8px;
}

.artworks-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    gap: 1.5rem;
}

.artwork-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.3s;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.artwork-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
}

.artwork-image {
    position: relative;
    height: 160px;
    overflow: hidden;
}

.artwork-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s;
}

.artwork-card:hover .artwork-image img {
    transform: scale(1.05);
}

.artwork-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.6);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s;
}

.artwork-card:hover .artwork-overlay {
    opacity: 1;
}

.quick-view {
    background: var(--gold);
    color:white;
    padding: 0.5rem 1.2rem;
    border-radius: 30px;
    text-decoration: none;
    font-weight: 600;
    font-size: 0.85rem;
}

.artwork-info {
    padding: 0.8rem;
}

.artwork-info h4 {
    font-size: 0.95rem;
    color: var(--primary);
    margin-bottom: 0.2rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.artist {
    font-size: 0.75rem;
    color: var(--text-light);
    margin-bottom: 0.5rem;
}

.artwork-stats {
    display: flex;
    gap: 1rem;
    font-size: 0.75rem;
    color: var(--text-light);
}

.artwork-stats i {
    margin-right: 3px;
}

.no-artworks {
    text-align: center;
    padding: 2rem;
    background: white;
    border-radius: 12px;
    color: var(--text-light);
    grid-column: 1/-1;
}

/* Responsive */
@media (max-width: 768px) {
    .dashboard-container {
        padding: 1rem;
    }
    
    .welcome-bar {
        flex-direction: column;
        text-align: center;
        gap: 1rem;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .artworks-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
    }
}

@media (max-width: 480px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .artworks-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php require_once 'includes/footer.php'; ?>