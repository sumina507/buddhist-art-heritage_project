<?php
// artist-dashboard.php - SOFT LIGHT VERSION (Same as Homepage UI)
require_once 'includes/config.php';

// Check if user is logged in and is artist
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'artist') {
    header('Location: login.php');
    exit;
}

require_once 'includes/navbar.php';

$artist_id = $_SESSION['artist_id'] ?? 0;
$user_id = $_SESSION['user_id'];

// Fix artist_id if missing
if (!$artist_id) {
    $alt_sql = "SELECT artist_id FROM artists WHERE user_id = ?";
    $alt_stmt = mysqli_prepare($conn, $alt_sql);
    mysqli_stmt_bind_param($alt_stmt, "i", $user_id);
    mysqli_stmt_execute($alt_stmt);
    $alt_result = mysqli_stmt_get_result($alt_stmt);
    $alt_artist = mysqli_fetch_assoc($alt_result);
    
    if ($alt_artist) {
        $_SESSION['artist_id'] = $alt_artist['artist_id'];
        $artist_id = $alt_artist['artist_id'];
    } else {
        $insert_artist = "INSERT INTO artists (user_id, status) VALUES (?, 'approved')";
        $stmt = mysqli_prepare($conn, $insert_artist);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $artist_id = mysqli_insert_id($conn);
        $_SESSION['artist_id'] = $artist_id;
    }
}

// Get artist details
$sql = "SELECT a.*, u.username, u.full_name, u.profile_image, u.bio, u.email 
        FROM artists a 
        JOIN users u ON a.user_id = u.user_id 
        WHERE a.artist_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $artist_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$artist = mysqli_fetch_assoc($result);

if (!$artist) {
    echo "<div class='container' style='padding: 50px; text-align: center;'>";
    echo "<h2>Artist Profile Not Found</h2>";
    echo "<p>Your artist profile is not set up correctly. Please contact admin.</p>";
    echo "<a href='logout.php' class='btn btn-primary'>Logout</a>";
    echo "</div>";
    require_once 'includes/footer.php';
    exit;
}

// Get artist's artworks
$artworks_sql = "SELECT * FROM artworks WHERE artist_id = ? ORDER BY created_at DESC LIMIT 6";
$stmt = mysqli_prepare($conn, $artworks_sql);
mysqli_stmt_bind_param($stmt, "i", $artist_id);
mysqli_stmt_execute($stmt);
$artworks_result = mysqli_stmt_get_result($stmt);

// Get artwork stats
$stats_sql = "SELECT 
                COUNT(*) as total_artworks,
                IFNULL(SUM(views), 0) as total_views,
                IFNULL(SUM(likes), 0) as total_likes
              FROM artworks 
              WHERE artist_id = ?";
$stmt = mysqli_prepare($conn, $stats_sql);
mysqli_stmt_bind_param($stmt, "i", $artist_id);
mysqli_stmt_execute($stmt);
$stats_result = mysqli_stmt_get_result($stmt);
$stats = mysqli_fetch_assoc($stats_result);

// Get custom request stats
$request_sql = "SELECT 
                    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_requests,
                    COUNT(CASE WHEN status = 'accepted' OR status = 'in_progress' THEN 1 END) as active_requests,
                    COUNT(CASE WHEN payment_status = 'completed' THEN 1 END) as completed_requests,
                    SUM(CASE WHEN payment_status = 'completed' THEN budget ELSE 0 END) as total_income
                FROM commissions 
                WHERE artist_id = ?";
$stmt = mysqli_prepare($conn, $request_sql);
mysqli_stmt_bind_param($stmt, "i", $artist_id);
mysqli_stmt_execute($stmt);
$request_result = mysqli_stmt_get_result($stmt);
$request_data = mysqli_fetch_assoc($request_result);

$pending_requests = $request_data['pending_requests'] ?? 0;
$active_requests = $request_data['active_requests'] ?? 0;
$completed_requests = $request_data['completed_requests'] ?? 0;
$total_income = $request_data['total_income'] ?? 0;

// Get recent requests
$recent_requests_sql = "SELECT c.*, u.username, u.full_name 
                        FROM commissions c
                        JOIN users u ON c.user_id = u.user_id
                        WHERE c.artist_id = ? 
                        ORDER BY c.created_at DESC 
                        LIMIT 5";
$stmt = mysqli_prepare($conn, $recent_requests_sql);
mysqli_stmt_bind_param($stmt, "i", $artist_id);
mysqli_stmt_execute($stmt);
$recent_requests = mysqli_stmt_get_result($stmt);
?>

<style>
:root {
    --primary-dark: #2c3e50;
    --primary-warm: #5c4b3a;
    --accent-gold: #f1c40f;
    --accent-orange: #d98324;
    --accent-red: #e74c3c;
    --accent-blue: #3498db;
    --accent-green: #27ae60;
    --bg-light: #fef9f0;
    --bg-white: #ffffff;
    --border-light: #ffe0b5;
    --text-dark: #5c4b3a;
    --text-muted: #a68a6e;
}

body {
    background: linear-gradient(135deg, #f9f7f1 0%, #f5f5f0 100%);
    font-family: 'Open Sans', sans-serif;
    color: var(--primary-dark);
}

.dashboard-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 2rem;
}

/* Dashboard Title */
.dashboard-title {
    margin-bottom: 2rem;
    text-align: center;
}

.dashboard-title h1 {
    font-size: 2rem;
    color: var(--primary-dark);
    font-weight: 700;
}

.dashboard-title h1 i {
    color: var(--accent-red);
    margin-right: 10px;
}

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: var(--bg-white);
    border-radius: 20px;
    padding: 1.2rem;
    text-align: center;
    transition: all 0.3s;
    border: 1px solid var(--border-light);
    box-shadow: 0 4px 12px rgba(0,0,0,0.03);
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 35px rgba(0,0,0,0.1);
    border-color: var(--accent-gold);
}

.stat-number {
    font-size: 2rem;
    font-weight: 700;
    color: var(--accent-orange);
    margin-bottom: 0.3rem;
}

.stat-label {
    color: var(--text-muted);
    font-size: 0.85rem;
    margin-bottom: 0.5rem;
}

.stat-link {
    color: var(--accent-red);
    font-size: 0.75rem;
    text-decoration: none;
    font-weight: 600;
}

.stat-link:hover {
    text-decoration: underline;
}

/* Quick Actions */
.actions-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.action-card {
    background: var(--bg-white);
    border-radius: 20px;
    padding: 1.5rem;
    text-align: center;
    text-decoration: none;
    transition: all 0.3s;
    border: 1px solid var(--border-light);
    position: relative;
    box-shadow: 0 4px 12px rgba(0,0,0,0.03);
}

.action-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 35px rgba(0,0,0,0.1);
    border-color: var(--accent-gold);
}

.action-icon {
    font-size: 2rem;
    display: block;
    margin-bottom: 0.8rem;
}

.action-icon i {
    font-size: 2rem;
}

.action-icon i.fa-cloud-upload-alt { color: var(--accent-orange); }
.action-icon i.fa-palette { color: var(--accent-gold); }
.action-icon i.fa-gem { color: #c4a35a; }

.action-title {
    font-weight: 600;
    color: var(--text-dark);
    margin-bottom: 0.3rem;
}

.action-desc {
    font-size: 0.7rem;
    color: var(--text-muted);
}

.action-badge {
    position: absolute;
    top: 12px;
    right: 15px;
    background: var(--accent-red);
    color: white;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    font-size: 0.7rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Section Cards */
.section-card {
    background: var(--bg-white);
    border-radius: 24px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    border: 1px solid var(--border-light);
    box-shadow: 0 4px 12px rgba(0,0,0,0.03);
}

.section-title {
    color: var(--primary-dark);
    margin-bottom: 1.2rem;
    padding-bottom: 0.8rem;
    border-bottom: 2px solid var(--accent-gold);
    display: inline-block;
    font-size: 1.2rem;
    font-weight: 700;
}

.section-title i {
    color: var(--accent-red);
    margin-right: 8px;
}

/* Request Item */
.request-item {
    background: var(--bg-light);
    border-radius: 16px;
    padding: 1rem;
    margin-bottom: 0.8rem;
    transition: all 0.3s;
    border-left: 4px solid;
}

.request-item:hover {
    background: #fff5e6;
    transform: translateX(5px);
}

.request-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 8px;
}

.request-id {
    color: var(--accent-orange);
    font-weight: 600;
}

.request-title {
    color: var(--text-dark);
    font-weight: 500;
}

.request-status {
    padding: 3px 12px;
    border-radius: 30px;
    font-size: 0.7rem;
    font-weight: 600;
}

.status-pending { background: #fff3cd; color: #856404; }
.status-accepted { background: #e8f5e9; color: #2e7d32; }
.status-in_progress { background: #e3f2fd; color: #1565c0; }
.status-completed { background: #e8f5e9; color: #2e7d32; }
.status-ready_for_payment { background: #fff3cd; color: #856404; }

.request-details {
    display: flex;
    gap: 1.5rem;
    flex-wrap: wrap;
    margin: 8px 0;
    font-size: 0.8rem;
    color: var(--text-muted);
}

.request-details i {
    margin-right: 4px;
    color: var(--accent-orange);
}

.view-link {
    display: inline-block;
    margin-top: 8px;
    background: var(--accent-blue);
    color: white;
    padding: 5px 18px;
    border-radius: 30px;
    text-decoration: none;
    font-size: 0.75rem;
    font-weight: 500;
    transition: all 0.3s;
}

.view-link:hover {
    background: #2980b9;
    transform: translateY(-2px);
}

.view-all-link {
    display: inline-block;
    margin-top: 1rem;
    color: var(--accent-red);
    text-decoration: none;
    font-weight: 600;
    font-size: 0.85rem;
}

.view-all-link:hover {
    text-decoration: underline;
}

/* Artworks Grid */
.artworks-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-top: 1rem;
}

.artwork-item {
    background: var(--bg-light);
    border-radius: 16px;
    overflow: hidden;
    transition: all 0.3s;
    border: 1px solid var(--border-light);
}

.artwork-item:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 35px rgba(0,0,0,0.1);
    border-color: var(--accent-gold);
}

.artwork-image {
    height: 160px;
    overflow: hidden;
    position: relative;
}

.artwork-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s;
}

.artwork-item:hover .artwork-image img {
    transform: scale(1.05);
}

.artwork-info {
    padding: 12px;
}

.artwork-title {
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--text-dark);
    margin-bottom: 5px;
}

.artwork-stats {
    display: flex;
    gap: 12px;
    font-size: 0.7rem;
    color: var(--text-muted);
    margin-bottom: 8px;
}

.artwork-stats i.fa-eye { color: var(--accent-blue); }
.artwork-stats i.fa-heart { color: var(--accent-red); }

.artwork-link {
    color: var(--accent-blue);
    font-size: 0.7rem;
    text-decoration: none;
    font-weight: 500;
}

.artwork-link:hover {
    text-decoration: underline;
    color: #2980b9;
}

.empty-state {
    text-align: center;
    padding: 3rem;
    background: var(--bg-light);
    border-radius: 20px;
    color: var(--text-muted);
}

.empty-state i {
    font-size: 2.5rem;
    color: var(--accent-gold);
    margin-bottom: 1rem;
    display: block;
}

.empty-state a {
    color: var(--accent-red);
    text-decoration: none;
}

.empty-state a:hover {
    text-decoration: underline;
}

/* Manage Button */
.manage-btn {
    text-align: center;
    margin-top: 1.5rem;
}

.manage-btn a {
    display: inline-block;
    background: var(--bg-light);
    color: var(--accent-red);
    padding: 8px 25px;
    border-radius: 40px;
    text-decoration: none;
    font-size: 0.85rem;
    font-weight: 600;
    transition: all 0.3s;
    border: 1px solid var(--border-light);
}

.manage-btn a:hover {
    background: var(--accent-red);
    color: white;
    border-color: var(--accent-red);
}

/* Responsive */
@media (max-width: 992px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    .actions-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .dashboard-container {
        padding: 1rem;
    }
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
    }
    .actions-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 480px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="dashboard-container">
    
    <div class="dashboard-title">
        <h1><i class="fas fa-chalkboard-user"></i> Artist Dashboard</h1>
    </div>
    
    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number"><?php echo $stats['total_artworks'] ?? 0; ?></div>
            <div class="stat-label">Total Artworks</div>
            <a href="my-artworks.php" class="stat-link">Manage →</a>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo number_format($stats['total_views'] ?? 0); ?></div>
            <div class="stat-label">Total Views</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo number_format($stats['total_likes'] ?? 0); ?></div>
            <div class="stat-label">Total Likes</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">NRs <?php echo number_format($total_income, 2); ?></div>
            <div class="stat-label">Total Income</div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="actions-grid">
        <a href="upload-artwork.php" class="action-card">
            <div class="action-icon"><i class="fas fa-cloud-upload-alt"></i></div>
            <div class="action-title">Upload Artwork</div>
            <div class="action-desc">Share your masterpiece</div>
        </a>
        <a href="my-artworks.php" class="action-card">
            <div class="action-icon"><i class="fas fa-palette"></i></div>
            <div class="action-title">My Gallery</div>
            <div class="action-desc">View and manage</div>
        </a>
        <a href="commissions.php" class="action-card">
            <div class="action-icon"><i class="fas fa-gem"></i></div>
            <div class="action-title">Custom Requests</div>
            <div class="action-desc">Client orders</div>
            <?php if ($pending_requests > 0): ?>
                <div class="action-badge"><?php echo $pending_requests; ?></div>
            <?php endif; ?>
        </a>
    </div>
    
    <!-- Recent Custom Requests -->
    <div class="section-card">
        <h3 class="section-title"><i class="fas fa-gem"></i> Recent Custom Requests</h3>
        
        <?php if (mysqli_num_rows($recent_requests) > 0): ?>
            <?php while($req = mysqli_fetch_assoc($recent_requests)): 
                $req_status = $req['status'];
                $status_text = $req_status == 'ready_for_payment' ? 'Awaiting Payment' : ucfirst($req_status);
            ?>
            <div class="request-item" style="border-left-color: <?php echo $req_status == 'pending' ? '#e8b86b' : ($req_status == 'accepted' ? '#80c4a8' : '#a0c4e8'); ?>">
                <div class="request-header">
                    <div>
                        <span class="request-id">#<?php echo $req['commission_id']; ?></span>
                        <span class="request-title"><?php echo htmlspecialchars($req['title']); ?></span>
                    </div>
                    <span class="request-status status-<?php echo $req_status; ?>">
                        <?php echo ucfirst(str_replace('_', ' ', $status_text)); ?>
                    </span>
                </div>
                <div class="request-details">
                    <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($req['full_name'] ?? $req['username']); ?></span>
                    <span><i class="fas fa-tag"></i> NRs <?php echo number_format($req['budget'], 2); ?></span>
                    <span><i class="fas fa-calendar"></i> <?php echo date('M d, Y', strtotime($req['created_at'])); ?></span>
                </div>
                <a href="commission-details.php?id=<?php echo $req['commission_id']; ?>" class="view-link">View Details →</a>
            </div>
            <?php endwhile; ?>
            <a href="commissions.php" class="view-all-link">View all requests →</a>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <p>No custom requests yet</p>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Recent Artworks -->
    <div class="section-card">
        <h3 class="section-title"><i class="fas fa-clock"></i> Recent Artworks</h3>
        
        <?php if (mysqli_num_rows($artworks_result) > 0): ?>
            <div class="artworks-grid">
                <?php while ($artwork = mysqli_fetch_assoc($artworks_result)): ?>
                    <div class="artwork-item">
                        <div class="artwork-image">
                            <img src="uploads/artworks/<?php echo $artwork['image_path']; ?>" alt="<?php echo htmlspecialchars($artwork['title']); ?>">
                        </div>
                        <div class="artwork-info">
                            <div class="artwork-title"><?php echo htmlspecialchars($artwork['title']); ?></div>
                            <div class="artwork-stats">
                                <span><i class="fas fa-eye"></i> <?php echo $artwork['views']; ?></span>
                                <span><i class="fas fa-heart"></i> <?php echo $artwork['likes']; ?></span>
                            </div>
                            <a href="artwork-detail.php?id=<?php echo $artwork['artwork_id']; ?>" class="artwork-link">View Details →</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
            <div class="manage-btn">
                <a href="my-artworks.php">Manage All Artworks →</a>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-palette"></i>
                <p>No artworks yet. <a href="upload-artwork.php">Upload your first artwork</a></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>