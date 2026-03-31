<?php
// commissions.php - For USERS and ARTISTS to view their own commissions
require_once 'includes/config.php';
require_once 'includes/navbar.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$page_title = "My Commission Requests";
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Handle commission status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action_type'])) {
    $commission_id = intval($_POST['commission_id']);
    $action = $_POST['action_type']; // 'accept', 'reject', 'cancel', 'progress', 'complete'
    
    // Determine new status based on action
    $new_status = '';
    switch($action) {
        case 'accept':
            $new_status = 'accepted';
            break;
        case 'reject':
            $new_status = 'cancelled';
            break;
        case 'cancel':
            $new_status = 'cancelled';
            break;
        case 'progress':
            $new_status = 'in_progress';
            break;
        case 'complete':
            $new_status = 'completed';
            break;
        default:
            $_SESSION['message'] = "Invalid action.";
            $_SESSION['message_type'] = 'error';
            header('Location: commissions.php');
            exit;
    }
    
    // Verify ownership
    if ($role == 'artist') {
        $check_sql = "SELECT * FROM commissions WHERE commission_id = ? AND artist_id = ?";
        $check_stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($check_stmt, "ii", $commission_id, $_SESSION['artist_id']);
    } else {
        $check_sql = "SELECT * FROM commissions WHERE commission_id = ? AND user_id = ?";
        $check_stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($check_stmt, "ii", $commission_id, $user_id);
    }
    
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    
    if (mysqli_num_rows($check_result) > 0) {
        $update_sql = "UPDATE commissions SET status = ?, updated_at = NOW() WHERE commission_id = ?";
        $update_stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($update_stmt, "si", $new_status, $commission_id);
        
        if (mysqli_stmt_execute($update_stmt)) {
            $_SESSION['message'] = "Commission " . $new_status . " successfully!";
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = "Error updating status: " . mysqli_error($conn);
            $_SESSION['message_type'] = 'error';
        }
    } else {
        $_SESSION['message'] = "Commission not found or access denied.";
        $_SESSION['message_type'] = 'error';
    }
    
    header('Location: commissions.php');
    exit;
}

// Get commissions based on role
if ($role == 'artist') {
    $artist_id = $_SESSION['artist_id'] ?? 0;
    
    if (!$artist_id) {
        echo "<div class='alert alert-error'>Artist ID not found. Please contact admin.</div>";
        $commissions_result = false;
        $total_commissions = 0;
    } else {
        $sql = "SELECT c.*, u.username, u.full_name as client_name 
                FROM commissions c
                JOIN users u ON c.user_id = u.user_id
                WHERE c.artist_id = ?
                ORDER BY c.created_at DESC";
                
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $artist_id);
        mysqli_stmt_execute($stmt);
        $commissions_result = mysqli_stmt_get_result($stmt);
        $total_commissions = mysqli_num_rows($commissions_result);
    }
} else {
    $sql = "SELECT c.*, u.username as artist_name, u.full_name as artist_fullname 
            FROM commissions c
            JOIN artists a ON c.artist_id = a.artist_id
            JOIN users u ON a.user_id = u.user_id
            WHERE c.user_id = ?
            ORDER BY c.created_at DESC";
            
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $commissions_result = mysqli_stmt_get_result($stmt);
    $total_commissions = mysqli_num_rows($commissions_result);
}
?>

<div class="container" style="padding: 20px; max-width: 1200px; margin: 0 auto;">
    <h1><i class="fas fa-handshake"></i> Commission Requests</h1>
    
    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert" style="padding: 15px; margin: 20px 0; border-radius: 5px; background: <?php echo $_SESSION['message_type'] == 'success' ? '#d4edda' : '#f8d7da'; ?>; color: <?php echo $_SESSION['message_type'] == 'success' ? '#155724' : '#721c24'; ?>; display: flex; justify-content: space-between; align-items: center;">
            <span><?php echo $_SESSION['message']; ?></span>
            <button onclick="this.parentElement.style.display='none'" style="background: none; border: none; color: inherit; font-size: 1.2rem; cursor: pointer;">&times;</button>
            <?php 
            unset($_SESSION['message']);
            unset($_SESSION['message_type']);
            ?>
        </div>
    <?php endif; ?>
    
    <?php if ($role == 'user'): ?>
        <div style="margin-bottom: 20px;">
            <a href="commission-request.php" style="background: #e74c3c; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;">
                <i class="fas fa-plus"></i> New Commission Request
            </a>
        </div>
    <?php endif; ?>
    
    <?php if ($total_commissions > 0): ?>
        <div style="display: grid; gap: 20px;">
            <?php while ($commission = mysqli_fetch_assoc($commissions_result)): 
                $status = $commission['status'] ?? 'pending';
                
                // Set status colors
                $status_colors = [
                    'pending' => ['bg' => '#fff3cd', 'color' => '#856404'],
                    'accepted' => ['bg' => '#d4edda', 'color' => '#155724'],
                    'in_progress' => ['bg' => '#cce5ff', 'color' => '#004085'],
                    'completed' => ['bg' => '#d4edda', 'color' => '#155724'],
                    'cancelled' => ['bg' => '#f8d7da', 'color' => '#721c24']
                ];
                $status_bg = $status_colors[$status]['bg'] ?? '#fff3cd';
                $status_color = $status_colors[$status]['color'] ?? '#856404';
            ?>
                <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-wrap: wrap; gap: 10px;">
                        <h3 style="margin: 0;"><?php echo htmlspecialchars($commission['title']); ?></h3>
                        <span style="padding: 5px 15px; border-radius: 20px; font-size: 0.9rem; font-weight: 600; background: <?php echo $status_bg; ?>; color: <?php echo $status_color; ?>;">
                            <?php echo ucfirst(str_replace('_', ' ', $status)); ?>
                        </span>
                    </div>
                    
                    <p><strong>Description:</strong> <?php echo nl2br(htmlspecialchars($commission['description'])); ?></p>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; margin: 15px 0;">
                        <?php if ($role == 'artist'): ?>
                            <p><strong>Client:</strong> <?php echo htmlspecialchars($commission['client_name'] ?? $commission['username']); ?></p>
                        <?php else: ?>
                            <p><strong>Artist:</strong> <?php echo htmlspecialchars($commission['artist_fullname'] ?? $commission['artist_name'] ?? 'Not assigned'); ?></p>
                        <?php endif; ?>
                        
                        <?php if (!empty($commission['budget'])): ?>
                            <p><strong>Budget:</strong> NRs <?php echo number_format($commission['budget'], 2); ?></p>
                        <?php endif; ?>
                        
                        <?php if (!empty($commission['deadline'])): ?>
                            <p><strong>Deadline:</strong> <?php echo date('M d, Y', strtotime($commission['deadline'])); ?></p>
                        <?php endif; ?>
                        
                        <p><strong>Requested:</strong> <?php echo date('M d, Y', strtotime($commission['created_at'])); ?></p>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div style="margin-top: 20px; display: flex; gap: 10px; flex-wrap: wrap;">
                        <!-- Artist actions for pending commissions -->
                        <?php if ($role == 'artist' && $status == 'pending'): ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="commission_id" value="<?php echo $commission['commission_id']; ?>">
                                <input type="hidden" name="action_type" value="accept">
                                <button type="submit" style="background: #27ae60; color: white; border: none; padding: 8px 20px; border-radius: 5px; cursor: pointer;" onclick="return confirm('Accept this commission request?')">
                                    <i class="fas fa-check"></i> Accept
                                </button>
                            </form>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="commission_id" value="<?php echo $commission['commission_id']; ?>">
                                <input type="hidden" name="action_type" value="reject">
                                <button type="submit" style="background: #e74c3c; color: white; border: none; padding: 8px 20px; border-radius: 5px; cursor: pointer;" onclick="return confirm('Reject this commission request?')">
                                    <i class="fas fa-times"></i> Reject
                                </button>
                            </form>
                        <?php endif; ?>
                        
                        <!-- Artist actions for accepted/in_progress commissions -->
                        <?php if ($role == 'artist' && ($status == 'accepted' || $status == 'in_progress')): ?>
                            <?php if ($status == 'accepted'): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="commission_id" value="<?php echo $commission['commission_id']; ?>">
                                    <input type="hidden" name="action_type" value="progress">
                                    <button type="submit" style="background: #3498db; color: white; border: none; padding: 8px 20px; border-radius: 5px; cursor: pointer;">
                                        <i class="fas fa-spinner"></i> Start Working
                                    </button>
                                </form>
                            <?php endif; ?>
                            <?php if ($status == 'in_progress'): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="commission_id" value="<?php echo $commission['commission_id']; ?>">
                                    <input type="hidden" name="action_type" value="complete">
                                    <button type="submit" style="background: #27ae60; color: white; border: none; padding: 8px 20px; border-radius: 5px; cursor: pointer;" onclick="return confirm('Mark this commission as completed?')">
                                        <i class="fas fa-check-circle"></i> Mark Completed
                                    </button>
                                </form>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <!-- User cancel action for pending commissions -->
                        <?php if ($role == 'user' && $status == 'pending'): ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="commission_id" value="<?php echo $commission['commission_id']; ?>">
                                <input type="hidden" name="action_type" value="cancel">
                                <button type="submit" style="background: #e74c3c; color: white; border: none; padding: 8px 20px; border-radius: 5px; cursor: pointer;" onclick="return confirm('Cancel this commission request?')">
                                    <i class="fas fa-times"></i> Cancel Request
                                </button>
                            </form>
                        <?php endif; ?>
                        
                        <!-- View details button (always visible) -->
                        <a href="commission-details.php?id=<?php echo $commission['commission_id']; ?>" style="background: #f1c40f; color: white; padding: 8px 20px; border-radius: 5px; text-decoration: none; display: inline-flex; align-items: center; gap: 5px;">
                            <i class="fas fa-eye"></i> View Details
                        </a>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div style="text-align: center; padding: 50px; background: white; border-radius: 10px;">
            <i class="fas fa-handshake" style="font-size: 4rem; color: #ddd; margin-bottom: 20px;"></i>
            <h3>No commission requests yet</h3>
            <?php if ($role == 'user'): ?>
                <p>Ready to commission a custom artwork? <a href="commission-request.php" style="color: #e74c3c;">Start here</a></p>
                <div style="margin-top: 20px;">
                    <a href="artists.php" style="background: #3498db; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;">
                        <i class="fas fa-paint-brush"></i> Browse Artists
                    </a>
                </div>
            <?php else: ?>
                <p>When clients request commissions, they'll appear here</p>
                <p>Make sure your artist profile is complete to receive requests</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<style>
/* Add hover effects */
button:hover, a:hover {
    opacity: 0.9;
    transform: translateY(-2px);
    transition: all 0.3s;
}

/* Responsive design */
@media (max-width: 768px) {
    div[style*="grid-template-columns: repeat(auto-fit"] {
        grid-template-columns: 1fr !important;
    }
    
    div[style*="display: flex; gap: 10px;"] {
        flex-direction: column;
    }
    
    button, a[style*="padding: 8px 20px"] {
        width: 100%;
        text-align: center;
    }
}
</style>

<?php require_once 'includes/footer.php'; ?>