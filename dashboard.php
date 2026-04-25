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

<style>
/* Soft Light Theme - Matching Artist Dashboard */
:root {
    --soft-bg: #fef9f0;
    --soft-card: #ffffff;
    --soft-border: #ffe0b5;
    --soft-primary: #e8b86b;
    --soft-text: #5c4b3a;
    --soft-accent: #d98324;
    --soft-hover: #f5e6d3;
}

body {
    background: linear-gradient(135deg, #f9f7f1 0%, #f5f5f0 100%);
    font-family: 'Inter', 'Poppins', sans-serif;
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
    border-bottom: 2px solid #ffe0b5;
}

.welcome-text h1 {
    font-size: 1.8rem;
    color: #5c4b3a;
    margin-bottom: 0.3rem;
}



.welcome-text p {
    color: #a68a6e;

    font-size: 0.95rem;
}

.welcome-avatar {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    border: 3px solid #e8b86b;
    overflow: hidden;
}

.welcome-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* Stats Cards - Soft Theme */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2.5rem;
}

.stat-card {
    background: white;
    padding: 1.2rem;
    border-radius: 20px;
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: all 0.3s;
    border: 1px solid #ffe0b5;
    box-shadow: 0 4px 12px rgba(0,0,0,0.03);
}

.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 25px rgba(216, 131, 36, 0.12);
    border-color: #e8b86b;
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    color: white;
}

.stat-icon.commissions { background: linear-gradient(135deg, #d98324, #e8b86b); }
.stat-icon.likes { background: linear-gradient(135deg, #e8b86b, #d98324); }
.stat-icon.comments { background: linear-gradient(135deg, #c4a35a, #b8924a); }
.stat-icon.member { background: linear-gradient(135deg, #a68a6e, #8c7259); }

.stat-info h3 {
    font-size: 1.8rem;
    font-weight: 700;
    color: #d98324;
    margin-bottom: 0.2rem;
}

.stat-info p {
    color:black;
    font-size: 0.85rem;
    margin-bottom: 0.3rem;
}

.stat-info a {
    color: red;
    text-decoration: none;
    font-size: 0.8rem;
    font-weight: 500;
    transition: color 0.2s;
}

.stat-info a:hover {
    color: #d98324;
    text-decoration: underline;
}

/* Recent Artworks Section */
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
    color: #5c4b3a;
    display: flex;
    align-items: center;
    gap: 8px;
}

.section-header h2 i {
    color: #d98324;
}

.view-all {
    color: red;
    text-decoration: none;
    font-size: 0.9rem;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 5px;
    transition: all 0.2s;
}

.view-all:hover {
    color: #d98324;
    gap: 8px;
}

/* Artworks Grid - Soft Cards */
.artworks-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    gap: 1.5rem;
}

.artwork-card {
    background: white;
    border-radius: 20px;
    overflow: hidden;
    transition: all 0.3s;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
    border: 1px solid #ffe0b5;
}

.artwork-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 25px rgba(216, 131, 36, 0.12);
    border-color: #e8b86b;
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

    background:0 5px 20px rgba(0,0,0,0.08);
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
    background: #f1c40f;
    color:black;
    padding: 0.5rem 1.2rem;
    border-radius: 30px;
    text-decoration: none;
    font-weight: 600;
    font-size: 0.85rem;
    transition: all 0.2s;
}

.quick-view:hover {
    background: #e74c3c;
    color: white;
}

.artwork-info {
    padding: 0.8rem;
}

.artwork-info h4 {
    font-size: 0.95rem;
    color: #5c4b3a;
    margin-bottom: 0.2rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.artist {
    font-size: 0.75rem;
    color: #a68a6e;
    margin-bottom: 0.5rem;
}

.artwork-stats {
    display: flex;
    gap: 1rem;
    font-size: 0.75rem;
    color: #a68a6e;
}

.artwork-stats i {
    margin-right: 3px;
    color: #e8b86b;
}

.no-artworks {
    text-align: center;
    padding: 2rem;
    background: white;
    border-radius: 20px;
    color: #a68a6e;
    grid-column: 1/-1;
    border: 1px solid #ffe0b5;
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

<div class="dashboard-container">
    <!-- Simple Welcome -->
    <div class="welcome-bar">
        <div class="welcome-text">
            <h1> My Dashboard</h1>
        </div>
        <div class="welcome-avatar">
            <img src="uploads/profiles/<?php echo htmlspecialchars($user['profile_image'] ?? 'default.jpg'); ?>" alt="Profile">
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon commissions">
                <i class="fas fa-pencil-ruler"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo $stats['total_commissions'] ?? 0; ?></h3>
                <p>Custom Orders</p>
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

<?php require_once 'includes/footer.php'; ?>