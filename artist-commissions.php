<?php
// artist-commissions.php - For ARTISTS to view their received commissions
require_once 'includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'artist') {
    header('Location: login.php');
    exit;
}

$page_title = "Artist Commissions";
require_once 'includes/navbar.php';

$artist_id = $_SESSION['artist_id'] ?? 0;

// If artist_id is not set, try to get it
if (!$artist_id) {
    $get_artist = "SELECT artist_id FROM artists WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $get_artist);
    mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $artist = mysqli_fetch_assoc($result);
    if ($artist) {
        $artist_id = $artist['artist_id'];
        $_SESSION['artist_id'] = $artist_id;
    }
}

// Get filter from URL
$filter = $_GET['status'] ?? 'all';

// Build query for artist's received commissions
$sql = "SELECT c.*, 
        u.full_name as client_name, 
        u.username as client_username,
        u.email as client_email,
        CASE 
            WHEN c.status = 'pending' THEN 'warning'
            WHEN c.status = 'accepted' THEN 'info'
            WHEN c.status = 'in_progress' THEN 'primary'
            WHEN c.status = 'ready_for_payment' THEN 'warning'
            WHEN c.status = 'completed' THEN 'success'
            WHEN c.status = 'cancelled' THEN 'danger'
        END as status_color
        FROM commissions c
        JOIN users u ON c.user_id = u.user_id
        WHERE c.artist_id = ?";

// Add filter if not 'all'
if ($filter != 'all') {
    $sql .= " AND c.status = '" . mysqli_real_escape_string($conn, $filter) . "'";
}

$sql .= " ORDER BY 
            CASE c.status 
                WHEN 'pending' THEN 1 
                WHEN 'accepted' THEN 2 
                WHEN 'in_progress' THEN 3 
                WHEN 'ready_for_payment' THEN 4
                WHEN 'completed' THEN 5 
                ELSE 6 
            END,
            c.created_at DESC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $artist_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Get counts for tabs
$count_sql = "SELECT 
    COUNT(*) as total,
    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
    COUNT(CASE WHEN status = 'accepted' THEN 1 END) as accepted,
    COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as in_progress,
    COUNT(CASE WHEN status = 'ready_for_payment' THEN 1 END) as ready_for_payment,
    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed,
    COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled
    FROM commissions WHERE artist_id = ?";
$stmt2 = mysqli_prepare($conn, $count_sql);
mysqli_stmt_bind_param($stmt2, "i", $artist_id);
mysqli_stmt_execute($stmt2);
$count_result = mysqli_stmt_get_result($stmt2);
$counts = mysqli_fetch_assoc($count_result);
?>

<style>
body { background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); min-height: 100vh; }
.commissions-container { max-width: 1200px; margin: 0 auto; padding: 2rem 1rem; }
.page-header { text-align: center; margin-bottom: 2rem; }
.page-header h1 { color: #f1c40f; font-size: 2rem; }
.page-header p { color: #bdc3c7; }

/* Filter Tabs */
.filter-tabs { display: flex; gap: 0.5rem; margin-bottom: 1.5rem; flex-wrap: wrap; justify-content: center; }
.filter-tab { padding: 0.5rem 1rem; background: #2c3e50; color: #ecf0f1; text-decoration: none; border-radius: 8px; transition: all 0.3s; border: 1px solid rgba(241, 196, 15, 0.2); }
.filter-tab:hover { background: #3d566e; }
.filter-tab.active { background: #f1c40f; color: #1a1a2e; border-color: #f1c40f; }
.filter-tab .count { margin-left: 5px; opacity: 0.8; font-size: 0.8rem; }

/* Commission Card */
.commission-card { background: #2c3e50; border-radius: 15px; margin-bottom: 1.5rem; overflow: hidden; transition: transform 0.3s; border: 1px solid rgba(241, 196, 15, 0.2); }
.commission-card:hover { transform: translateY(-5px); }
.card-header { padding: 1rem 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; }
.commission-id { font-weight: bold; color: #f1c40f; }
.commission-title { font-size: 1.2rem; color: #ecf0f1; margin: 0; }
.card-body { padding: 1.5rem; }
.info-row { display: flex; margin-bottom: 0.8rem; flex-wrap: wrap; }
.info-label { width: 120px; font-weight: bold; color: #f1c40f; }
.info-value { flex: 1; color: #bdc3c7; }

/* Status Badges */
.status-badge { padding: 4px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: bold; display: inline-block; margin-right: 8px; }
.status-warning { background: #f39c12; color: #fff; }
.status-info { background: #3498db; color: #fff; }
.status-primary { background: #9b59b6; color: #fff; }
.status-success { background: #27ae60; color: #fff; }
.status-danger { background: #e74c3c; color: #fff; }

.payment-paid { background: #27ae60; color: #fff; }
.payment-pending { background: #e74c3c; color: #fff; }
.payment-advance { background: #f39c12; color: #fff; }

/* Buttons */
.btn-view { background: #3498db; color: white; padding: 8px 20px; border-radius: 8px; text-decoration: none; display: inline-block; transition: all 0.3s; }
.btn-view:hover { background: #2980b9; transform: translateY(-2px); }

.btn-accept { background: #27ae60; color: white; padding: 6px 15px; border-radius: 6px; text-decoration: none; display: inline-block; transition: all 0.3s; font-size: 0.8rem; margin-right: 10px; }
.btn-accept:hover { background: #219653; transform: translateY(-2px); }
.btn-reject { background: #e74c3c; color: white; padding: 6px 15px; border-radius: 6px; text-decoration: none; display: inline-block; transition: all 0.3s; font-size: 0.8rem; }
.btn-reject:hover { background: #c0392b; transform: translateY(-2px); }

.empty-state { text-align: center; padding: 4rem; background: #2c3e50; border-radius: 20px; }
.empty-state i { font-size: 4rem; color: #f1c40f; margin-bottom: 1rem; }
.empty-state p { color: #bdc3c7; font-size: 1.1rem; }

@media (max-width: 768px) {
    .filter-tabs { flex-direction: column; align-items: stretch; }
    .filter-tab { text-align: center; }
    .card-header { flex-direction: column; gap: 10px; text-align: center; }
}
</style>

<div class="commissions-container">
    <div class="page-header">
        <h1><i class="fas fa-handshake"></i> Commission Requests</h1>
        <p>Manage artwork requests from clients</p>
    </div>

    <!-- Filter Tabs -->
    <div class="filter-tabs">
        <a href="?status=all" class="filter-tab <?php echo $filter == 'all' ? 'active' : ''; ?>">
            All <span class="count">(<?php echo $counts['total'] ?? 0; ?>)</span>
        </a>
        <a href="?status=pending" class="filter-tab <?php echo $filter == 'pending' ? 'active' : ''; ?>">
            Pending <span class="count">(<?php echo $counts['pending'] ?? 0; ?>)</span>
        </a>
        <a href="?status=accepted" class="filter-tab <?php echo $filter == 'accepted' ? 'active' : ''; ?>">
            Accepted <span class="count">(<?php echo $counts['accepted'] ?? 0; ?>)</span>
        </a>
        <a href="?status=in_progress" class="filter-tab <?php echo $filter == 'in_progress' ? 'active' : ''; ?>">
            In Progress <span class="count">(<?php echo $counts['in_progress'] ?? 0; ?>)</span>
        </a>
        <a href="?status=ready_for_payment" class="filter-tab <?php echo $filter == 'ready_for_payment' ? 'active' : ''; ?>">
            Ready for Payment <span class="count">(<?php echo $counts['ready_for_payment'] ?? 0; ?>)</span>
        </a>
        <a href="?status=completed" class="filter-tab <?php echo $filter == 'completed' ? 'active' : ''; ?>">
            Completed <span class="count">(<?php echo $counts['completed'] ?? 0; ?>)</span>
        </a>
        <a href="?status=cancelled" class="filter-tab <?php echo $filter == 'cancelled' ? 'active' : ''; ?>">
            Cancelled <span class="count">(<?php echo $counts['cancelled'] ?? 0; ?>)</span>
        </a>
    </div>

    <?php if (mysqli_num_rows($result) > 0): ?>
        <?php while($row = mysqli_fetch_assoc($result)): ?>
        <div class="commission-card">
            <div class="card-header">
                <div>
                    <span class="commission-id">#<?php echo $row['commission_id']; ?></span>
                    <span class="status-badge status-<?php echo $row['status_color']; ?>">
                        <?php echo ucfirst(str_replace('_', ' ', $row['status'])); ?>
                    </span>
                    <?php 
                    $payment_class = 'pending';
                    if ($row['payment_status'] == 'completed') $payment_class = 'paid';
                    elseif ($row['payment_status'] == 'advance_paid') $payment_class = 'advance';
                    ?>
                    <span class="status-badge payment-<?php echo $payment_class; ?>">
                        <?php echo $row['payment_status'] == 'completed' ? 'Fully Paid' : ($row['payment_status'] == 'advance_paid' ? 'Advance Paid' : 'Pending'); ?>
                    </span>
                </div>
                <small style="color: #7f8c8d;"><?php echo date('M d, Y', strtotime($row['created_at'])); ?></small>
            </div>
            <div class="card-body">
                <h3 class="commission-title"><?php echo htmlspecialchars($row['title']); ?></h3>
                
                <div class="info-row">
                    <div class="info-label">Client:</div>
                    <div class="info-value"><?php echo htmlspecialchars($row['client_name'] ?? $row['client_username']); ?></div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Size:</div>
                    <div class="info-value"><?php echo htmlspecialchars($row['size'] ?? 'Standard'); ?></div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Budget:</div>
                    <div class="info-value">NRs <?php echo number_format($row['budget'] ?? 0, 2); ?></div>
                </div>
                
                <?php if ($row['deadline']): ?>
                <div class="info-row">
                    <div class="info-label">Deadline:</div>
                    <div class="info-value"><?php echo date('M d, Y', strtotime($row['deadline'])); ?></div>
                </div>
                <?php endif; ?>
                
                <div class="info-row">
                    <div class="info-label">Description:</div>
                    <div class="info-value"><?php echo nl2br(htmlspecialchars(substr($row['description'], 0, 150))) . (strlen($row['description']) > 150 ? '...' : ''); ?></div>
                </div>
                
                <div style="margin-top: 1rem; text-align: right;">
                    <a href="commission-details.php?id=<?php echo $row['commission_id']; ?>" class="btn-view">
                        <i class="fas fa-eye"></i> View Details
                    </a>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-inbox"></i>
            <p>No commission requests found.</p>
            <p style="font-size: 0.9rem;">When clients request your artwork, they'll appear here.</p>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>