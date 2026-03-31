<?php
// artist-dashboard.php - PRIVATE DASHBOARD FOR LOGGED-IN ARTISTS
require_once 'includes/config.php';

// Check if user is logged in and is artist
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'artist') {
    header('Location: login.php');
    exit;
}

require_once 'includes/navbar.php';

$artist_id = $_SESSION['artist_id'] ?? 0;
$user_id = $_SESSION['user_id'];

// FIX 1: Add this debug check
if (!$artist_id) {
    // Try to get artist_id from users table
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
        // If no artist record exists, create one
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
$artworks_sql = "SELECT * FROM artworks WHERE artist_id = ? ORDER BY created_at DESC";
$stmt = mysqli_prepare($conn, $artworks_sql);
mysqli_stmt_bind_param($stmt, "i", $artist_id);
mysqli_stmt_execute($stmt);
$artworks_result = mysqli_stmt_get_result($stmt);

// Get stats
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

// ===== FIXED: Commission counts with column check =====
$pending_commissions = 0;
$pending_payments = 0;
$completed_payments = 0;
$total_earnings = 0;

// Check if payment_status column exists
$column_check = mysqli_query($conn, "SHOW COLUMNS FROM commissions LIKE 'payment_status'");
$has_payment_columns = mysqli_num_rows($column_check) > 0;

if ($has_payment_columns) {
    // Full query with payment tracking - FIXED
    $commission_sql = "SELECT 
                        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_commissions,
                        COUNT(CASE WHEN status = 'completed' AND payment_status = 'pending' THEN 1 END) as pending_payments,
                        COUNT(CASE WHEN payment_status = 'paid' THEN 1 END) as completed_payments
                       FROM commissions 
                       WHERE artist_id = ?";
    $stmt = mysqli_prepare($conn, $commission_sql);
    mysqli_stmt_bind_param($stmt, "i", $artist_id);
    mysqli_stmt_execute($stmt);
    $commission_result = mysqli_stmt_get_result($stmt);
    $commission_data = mysqli_fetch_assoc($commission_result);
    
    $pending_commissions = $commission_data['pending_commissions'] ?? 0;
    $pending_payments = $commission_data['pending_payments'] ?? 0;
    $completed_payments = $commission_data['completed_payments'] ?? 0;
    
    // Get total earnings
    $earnings_sql = "SELECT SUM(budget) as total FROM commissions 
                     WHERE artist_id = ? AND payment_status = 'paid'";
    $stmt = mysqli_prepare($conn, $earnings_sql);
    mysqli_stmt_bind_param($stmt, "i", $artist_id);
    mysqli_stmt_execute($stmt);
    $earnings_result = mysqli_stmt_get_result($stmt);
    $earnings = mysqli_fetch_assoc($earnings_result);
    $total_earnings = $earnings['total'] ?? 0;
} else {
    // Simple query without payment tracking
    $commission_sql = "SELECT COUNT(*) as pending_commissions 
                       FROM commissions 
                       WHERE artist_id = ? AND status = 'pending'";
    $stmt = mysqli_prepare($conn, $commission_sql);
    mysqli_stmt_bind_param($stmt, "i", $artist_id);
    mysqli_stmt_execute($stmt);
    $commission_result = mysqli_stmt_get_result($stmt);
    $commission_data = mysqli_fetch_assoc($commission_result);
    $pending_commissions = $commission_data['pending_commissions'] ?? 0;
}
// ===== END FIX =====
?>

<div class="container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin: 20px 0; flex-wrap: wrap; gap: 10px;">
        <h1>Artist Dashboard</h1>
        <div style="display: flex; gap: 10px;">
            <?php if ($pending_commissions > 0): ?>
                <span style="background: #e74c3c; color: white; padding: 5px 15px; border-radius: 20px; font-size: 0.9rem;">
                    <i class="fas fa-bell"></i> <?php echo $pending_commissions; ?> pending
                </span>
            <?php endif; ?>
            <?php if ($has_payment_columns && $pending_payments > 0): ?>
                <span style="background: #f39c12; color: white; padding: 5px 15px; border-radius: 20px; font-size: 0.9rem;">
                    <i class="fas fa-credit-card"></i> <?php echo $pending_payments; ?> payment pending
                </span>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Quick Stats -->
    <div style="display: grid; grid-template-columns: repeat(<?php echo $has_payment_columns ? '5' : '4'; ?>, 1fr); gap: 20px; margin: 30px 0;">
        <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
            <h3 style="font-size: 2rem; margin: 0;"><?php echo $stats['total_artworks'] ?? 0; ?></h3>
            <p>Total Artworks</p>
            <a href="my-artworks.php" style="color: #3498db; text-decoration: none;">Manage →</a>
        </div>
        <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
            <h3 style="font-size: 2rem; margin: 0;"><?php echo $stats['total_views'] ?? 0; ?></h3>
            <p>Total Views</p>
        </div>
        <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
            <h3 style="font-size: 2rem; margin: 0;"><?php echo $stats['total_likes'] ?? 0; ?></h3>
            <p>Total Likes</p>
        </div>
        <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
            <h3 style="font-size: 2rem; margin: 0;"><?php echo $pending_commissions; ?></h3>
            <p>Pending Commissions</p>
            <a href="commissions.php?status=pending" style="color: #27ae60; text-decoration: none;">View →</a>
        </div>
        
        <?php if ($has_payment_columns): ?>
        <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
            <h3 style="font-size: 2rem; margin: 0; color: <?php echo $pending_payments > 0 ? '#e74c3c' : '#27ae60'; ?>;">
                <?php echo $pending_payments; ?>
            </h3>
            <p>Pending Payments</p>
            <a href="commissions.php?status=completed" style="color: #f39c12; text-decoration: none;">View →</a>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Payment Summary Card - Only show if payment columns exist -->
    <?php if ($has_payment_columns && ($pending_payments > 0 || $completed_payments > 0)): ?>
    <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin: 30px 0;">
        <h3 style="margin: 0 0 15px 0;"><i class="fas fa-credit-card"></i> Payment Overview</h3>
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
            <div>
                <div style="font-size: 0.9rem; color: #666;">Pending Payments</div>
                <div style="font-size: 2rem; font-weight: bold; color: #e74c3c;"><?php echo $pending_payments; ?></div>
                <small>Awaiting client payment</small>
            </div>
            <div>
                <div style="font-size: 0.9rem; color: #666;">Completed Payments</div>
                <div style="font-size: 2rem; font-weight: bold; color: #27ae60;"><?php echo $completed_payments; ?></div>
                <small>Successfully received</small>
            </div>
            <div>
                <div style="font-size: 0.9rem; color: #666;">Total Earnings</div>
                <div style="font-size: 2rem; font-weight: bold; color: #2c3e50;">NRs <?php echo number_format($total_earnings, 2); ?></div>
                <small>Total received</small>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Quick Actions -->
    <div style="display: grid; grid-template-columns: repeat(<?php echo $has_payment_columns ? '4' : '3'; ?>, 1fr); gap: 20px; margin: 30px 0;">
        <a href="upload-artwork.php" style="background: #e74c3c; color: white; padding: 20px; text-align: center; text-decoration: none; border-radius: 10px; transition: transform 0.3s;">
            <i class="fas fa-upload" style="font-size: 2rem; display: block; margin-bottom: 10px;"></i>
            Upload Artwork
        </a>
        <a href="my-artworks.php" style="background: #3498db; color: white; padding: 20px; text-align: center; text-decoration: none; border-radius: 10px; transition: transform 0.3s;">
            <i class="fas fa-palette" style="font-size: 2rem; display: block; margin-bottom: 10px;"></i>
            My Artworks
        </a>
        <a href="commissions.php" style="background: #27ae60; color: white; padding: 20px; text-align: center; text-decoration: none; border-radius: 10px; transition: transform 0.3s; position: relative;">
            <i class="fas fa-handshake" style="font-size: 2rem; display: block; margin-bottom: 10px;"></i>
            Commissions
            <?php if ($pending_commissions > 0): ?>
                <span style="position: absolute; top: 10px; right: 10px; background: #e74c3c; color: white; width: 25px; height: 25px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; font-weight: bold;">
                    <?php echo $pending_commissions; ?>
                </span>
            <?php endif; ?>
        </a>
        
        <?php if ($has_payment_columns): ?>
        <a href="commissions.php?status=completed" style="background: #f39c12; color: white; padding: 20px; text-align: center; text-decoration: none; border-radius: 10px; transition: transform 0.3s; position: relative;">
            <i class="fas fa-credit-card" style="font-size: 2rem; display: block; margin-bottom: 10px;"></i>
            Payments
            <?php if ($pending_payments > 0): ?>
                <span style="position: absolute; top: 10px; right: 10px; background: #e74c3c; color: white; width: 25px; height: 25px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; font-weight: bold;">
                    <?php echo $pending_payments; ?>
                </span>
            <?php endif; ?>
        </a>
        <?php endif; ?>
    </div>
    
    <!-- Recent Artworks - SIMPLE PREVIEW ONLY -->
<h2 style="margin: 30px 0 20px 0;">Recent Artworks</h2>
<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px;">
    <?php if (mysqli_num_rows($artworks_result) > 0): ?>
        <?php while ($artwork = mysqli_fetch_assoc($artworks_result)): ?>
            <div style="background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.08); transition: transform 0.2s;">
                <div style="position: relative; height: 160px; overflow: hidden;">
                    <img src="uploads/artworks/<?php echo $artwork['image_path']; ?>" 
                         style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s;">
                    <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; opacity: 0; transition: opacity 0.2s;">
                        <a href="artwork-detail.php?id=<?php echo $artwork['artwork_id']; ?>" 
                           style="background: white; color: #e74c3c; padding: 6px 12px; border-radius: 20px; text-decoration: none; font-size: 0.8rem; font-weight: 500;">
                            <i class="fas fa-eye"></i> Quick View
                        </a>
                    </div>
                </div>
                <div style="padding: 12px;">
                    <h3 style="margin: 0 0 5px 0; font-size: 0.9rem; font-weight: 600; color: #2c3e50;"><?php echo htmlspecialchars($artwork['title']); ?></h3>
                    <div style="display: flex; gap: 12px; color: #7f8c8d; font-size: 0.75rem;">
                        <span><i class="fas fa-eye"></i> <?php echo $artwork['views']; ?></span>
                        <span><i class="fas fa-heart"></i> <?php echo $artwork['likes']; ?></span>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div style="grid-column: 1/-1; text-align: center; padding: 40px; background: white; border-radius: 12px;">
            <i class="fas fa-palette" style="font-size: 2.5rem; color: #ddd; margin-bottom: 10px;"></i>
            <p style="color: #666;">No artworks yet. <a href="upload-artwork.php" style="color: #e74c3c;">Upload your first artwork</a></p>
        </div>
    <?php endif; ?>
</div>

<!-- Link to Manage All Artworks -->
<div style="text-align: center; margin-top: 20px;">
    <a href="my-artworks.php" style="display: inline-block; background: #f8f9fa; color: #e74c3c; padding: 8px 20px; border-radius: 25px; text-decoration: none; font-size: 0.85rem;">
        <i class="fas fa-th-large"></i> Manage All Artworks
    </a>
</div>

<style>
/* Add hover effects */
a[style*="background:"]:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

.artwork-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.15);
}

/* Responsive design */
@media (max-width: 992px) {
    div[style*="grid-template-columns: repeat(5, 1fr)"] {
        grid-template-columns: repeat(3, 1fr) !important;
    }
    div[style*="grid-template-columns: repeat(4, 1fr)"] {
        grid-template-columns: repeat(2, 1fr) !important;
    }
}

@media (max-width: 768px) {
    div[style*="grid-template-columns: repeat(5, 1fr)"] {
        grid-template-columns: repeat(2, 1fr) !important;
    }
    div[style*="grid-template-columns: repeat(4, 1fr)"] {
        grid-template-columns: 1fr !important;
    }
    div[style*="grid-template-columns: repeat(3, 1fr)"] {
        grid-template-columns: 1fr !important;
    }
}

@media (max-width: 480px) {
    div[style*="grid-template-columns: repeat(5, 1fr)"] {
        grid-template-columns: 1fr !important;
    }
}
</style>

<?php require_once 'includes/footer.php'; ?>