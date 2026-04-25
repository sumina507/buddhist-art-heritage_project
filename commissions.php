<?php
require_once 'includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$page_title = $role == 'artist' ? "Custom Requests Received" : "My Custom Requests";
require_once 'includes/navbar.php';

// Different queries for user and artist
if ($role == 'artist') {
    $artist_id = $_SESSION['artist_id'] ?? 0;
    
    // Get artist_id if not set
    if (!$artist_id) {
        $get_artist = "SELECT artist_id FROM artists WHERE user_id = ?";
        $stmt = mysqli_prepare($conn, $get_artist);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $artist_data = mysqli_fetch_assoc($result);
        if ($artist_data) {
            $artist_id = $artist_data['artist_id'];
            $_SESSION['artist_id'] = $artist_id;
        }
    }
    
    // For ARTIST: show requests made to them
    $sql = "SELECT c.*, 
            u.full_name as client_name, 
            u.username as client_username,
            CASE 
                WHEN c.status = 'pending' THEN 'warning'
                WHEN c.status = 'accepted' THEN 'info'
                WHEN c.status = 'in_progress' THEN 'primary'
                WHEN c.status = 'completed' THEN 'success'
                WHEN c.status = 'cancelled' THEN 'danger'
                ELSE 'secondary'
            END as status_color
            FROM commissions c
            JOIN users u ON c.user_id = u.user_id
            WHERE c.artist_id = ?
            ORDER BY c.created_at DESC";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $artist_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
} else {
    // For USER: show requests made by them
    $sql = "SELECT c.*, 
            u.full_name as artist_name, 
            u.username as artist_username,
            CASE 
                WHEN c.status = 'pending' THEN 'warning'
                WHEN c.status = 'accepted' THEN 'info'
                WHEN c.status = 'in_progress' THEN 'primary'
                WHEN c.status = 'completed' THEN 'success'
                WHEN c.status = 'cancelled' THEN 'danger'
                ELSE 'secondary'
            END as status_color
            FROM commissions c
            JOIN artists a ON c.artist_id = a.artist_id
            JOIN users u ON a.user_id = u.user_id
            WHERE c.user_id = ?
            ORDER BY c.created_at DESC";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
}

$total_requests = mysqli_num_rows($result);
?>

<style>
body { background: #f5f5f0; font-family: 'Inter', sans-serif; }
.commissions-container { max-width: 1200px; margin: 0 auto; padding: 2rem 1rem; }
.page-header { text-align: center; margin-bottom: 2rem; }
.page-header h1 { color: #2c3e50; font-size: 2rem; }
.page-header h1 i { color: #e74c3c; margin-right: 10px; }
.page-header p { color: #6c757d; }
.request-card { background: white; border-radius: 16px; margin-bottom: 1.5rem; overflow: hidden; transition: all 0.3s; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border: 1px solid #e9ecef; }
.request-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
.card-header { padding: 1rem 1.5rem; border-bottom: 1px solid #e9ecef; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; }
.request-id { font-weight: bold; color: #e74c3c; }
.request-title { font-size: 1.1rem; color: #2c3e50; margin: 0; }
.card-body { padding: 1.5rem; }
.info-row { display: flex; margin-bottom: 0.8rem; flex-wrap: wrap; }
.info-label { width: 100px; font-weight: bold; color: #6c757d; }
.info-value { flex: 1; color: #2c3e50; }
.status-badge { padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; display: inline-block; }
.status-warning { background: #fff3cd; color: #856404; }
.status-info { background: #cce5ff; color: #004085; }
.status-primary { background: #cce5ff; color: #004085; }
.status-success { background: #d4edda; color: #155724; }
.status-danger { background: #f8d7da; color: #721c24; }
.status-secondary { background: #e9ecef; color: #6c757d; }
.payment-badge { padding: 4px 12px; border-radius: 20px; font-size: 0.7rem; font-weight: 600; display: inline-block; margin-left: 8px; }
.payment-paid { background: #d4edda; color: #155724; }
.payment-pending { background: #f8d7da; color: #721c24; }
.payment-advance { background: #fff3cd; color: #856404; }
.btn-view { background: #e74c3c; color: white; padding: 8px 20px; border-radius: 8px; text-decoration: none; display: inline-block; transition: all 0.3s; border: none; cursor: pointer; font-size: 0.85rem; }
.btn-view:hover { background: #c0392b; transform: translateY(-2px); }
.empty-state { text-align: center; padding: 4rem; background: white; border-radius: 20px; border: 1px solid #e9ecef; }
.empty-state i { font-size: 4rem; color: #e74c3c; margin-bottom: 1rem; }
.empty-state p { color: #6c757d; font-size: 1.1rem; }
.btn-request { background: #e74c3c; color: white; padding: 12px 30px; border-radius: 10px; text-decoration: none; display: inline-block; margin-top: 1rem; transition: all 0.3s; }
.btn-request:hover { background: #c0392b; transform: translateY(-2px); }

</style>
<div class="commissions-container">
    <div class="page-header">
        <h1><i class="fas fa-clipboard-list"></i> <?php echo $page_title; ?></h1>
        <p>Track and manage your custom artwork requests</p>
        <?php if ($role != 'artist'): ?>
        <a href="commission-request.php" class="btn-request">
            <i class="fas fa-plus"></i> New Custom Request
        </a>
        <?php endif; ?>
    </div>
    
    <?php if ($total_requests > 0): ?>
        <?php while($row = mysqli_fetch_assoc($result)): 
            // Determine payment status display
            $payment_display = 'pending';
            $payment_text = 'Payment Pending';
            if ($row['payment_status'] == 'completed' || $row['remaining_paid'] == 1) {
                $payment_display = 'paid';
                $payment_text = 'Payment Completed';
            } elseif ($row['advance_paid'] == 1) {
                $payment_display = 'advance';
                $payment_text = 'Advance Paid';
            }
        ?>
        <div class="request-card">
            <div class="card-header">
                <div>
                    <span class="request-id">#<?php echo $row['commission_id']; ?></span>
                    <span class="status-badge status-<?php echo $row['status_color']; ?>">
                        <?php echo ucfirst($row['status']); ?>
                    </span>
                    <span class="payment-badge payment-<?php echo $payment_display; ?>">
                        <?php echo $payment_text; ?>
                    </span>
                </div>
                <small style="color: #6c757d;"><?php echo date('M d, Y', strtotime($row['created_at'])); ?></small>
            </div>
            <div class="card-body">
                <h3 class="request-title"><?php echo htmlspecialchars($row['title']); ?></h3>
                
                <div class="info-row">
                    <div class="info-label"><?php echo $role == 'artist' ? 'Client:' : 'Artist:'; ?></div>
                    <div class="info-value">
                        <?php 
                        if ($role == 'artist') {
                            echo htmlspecialchars($row['client_name'] ?? $row['client_username']);
                        } else {
                            echo htmlspecialchars($row['artist_name'] ?? $row['artist_username']);
                        }
                        ?>
                    </div>
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
            <i class="fas fa-paint-brush"></i>
            <p><?php echo $role == 'artist' ? 'No custom artwork requests received yet.' : 'You haven\'t made any custom artwork requests yet.'; ?></p>
            <?php if ($role != 'artist'): ?>
            <a href="commission-request.php" class="btn-request">
                <i class="fas fa-handshake"></i> Request Custom Artwork
            </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>


<?php require_once 'includes/footer.php'; ?>