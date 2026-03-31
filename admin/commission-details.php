<?php
require_once '../includes/config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit;
}

$commission_id = $_GET['id'] ?? 0;

// Get commission details with all delivery info
$sql = "SELECT c.*, 
               u1.username as client_name, u1.full_name as client_fullname, u1.email as client_email,
               u2.username as artist_name, u2.full_name as artist_fullname, u2.email as artist_email,
               a.specialization, a.experience_years
        FROM commissions c
        JOIN users u1 ON c.user_id = u1.user_id
        JOIN artists a ON c.artist_id = a.artist_id
        JOIN users u2 ON a.user_id = u2.user_id
        WHERE c.commission_id = ?";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $commission_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$commission = mysqli_fetch_assoc($result);

if (!$commission) {
    $_SESSION['message'] = "Commission not found!";
    $_SESSION['message_type'] = 'error';
    header('Location: commissions.php');
    exit;
}

$page_title = "Commission #" . $commission_id . " Details";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Admin Panel</title>
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <main class="admin-main">
        <div class="admin-topbar">
            <div class="topbar-left">
                <button class="mobile-menu-btn" id="mobileMenuBtn">
                    <i class="fas fa-bars"></i>
                </button>
                <h2>Commission Details #<?php echo $commission_id; ?></h2>
            </div>
            <div class="topbar-right">
                <a href="commissions.php" class="btn-small" style="background: var(--info-color); color: white; text-decoration: none; padding: 0.5rem 1rem; border-radius: 5px;">
                    <i class="fas fa-arrow-left"></i> Back to Commissions
                </a>
            </div>
        </div>
        
        <div class="admin-content">
            <!-- Status Summary Cards -->
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 2rem;">
                <!-- Commission Status -->
                <div style="background: white; padding: 1rem; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
                    <div style="font-size: 0.9rem; color: #666;">Commission Status</div>
                    <div style="font-size: 1.3rem; font-weight: bold; margin-top: 0.5rem;">
                        <span class="status-badge status-<?php echo $commission['status']; ?>" style="font-size: 1rem;">
                            <?php echo ucfirst(str_replace('_', ' ', $commission['status'])); ?>
                        </span>
                    </div>
                </div>
                
                <!-- Payment Status -->
                <div style="background: white; padding: 1rem; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
                    <div style="font-size: 0.9rem; color: #666;">Payment Status</div>
                    <div style="font-size: 1.3rem; font-weight: bold; margin-top: 0.5rem;">
                        <span style="padding: 5px 15px; border-radius: 20px; background: <?php echo ($commission['payment_status'] ?? 'pending') == 'paid' ? '#d4edda' : '#fff3cd'; ?>; color: <?php echo ($commission['payment_status'] ?? 'pending') == 'paid' ? '#155724' : '#856404'; ?>;">
                            <?php echo ucfirst($commission['payment_status'] ?? 'pending'); ?>
                        </span>
                    </div>
                </div>
                
                <!-- Delivery Status -->
                <div style="background: white; padding: 1rem; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
                    <div style="font-size: 0.9rem; color: #666;">Delivery Status</div>
                    <div style="font-size: 1.3rem; font-weight: bold; margin-top: 0.5rem;">
                        <?php 
                        $delivery_status = $commission['delivery_status'] ?? 'not_started';
                        $delivery_colors = [
                            'not_started' => ['bg' => '#f8f9fa', 'color' => '#6c757d'],
                            'pending_review' => ['bg' => '#fff3cd', 'color' => '#856404'],
                            'approved' => ['bg' => '#d4edda', 'color' => '#155724'],
                            'rejected' => ['bg' => '#f8d7da', 'color' => '#721c24'],
                            'revision_requested' => ['bg' => '#cce5ff', 'color' => '#004085'],
                            'delivered' => ['bg' => '#d4edda', 'color' => '#155724']
                        ];
                        $color = $delivery_colors[$delivery_status] ?? $delivery_colors['not_started'];
                        ?>
                        <span style="padding: 5px 15px; border-radius: 20px; background: <?php echo $color['bg']; ?>; color: <?php echo $color['color']; ?>;">
                            <?php echo ucfirst(str_replace('_', ' ', $delivery_status)); ?>
                        </span>
                    </div>
                </div>
                
                <!-- Timeline -->
                <div style="background: white; padding: 1rem; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
                    <div style="font-size: 0.9rem; color: #666;">Created</div>
                    <div style="font-size: 1rem; font-weight: bold; margin-top: 0.5rem;">
                        <?php echo date('M d, Y', strtotime($commission['created_at'])); ?>
                    </div>
                    <div style="font-size: 0.8rem; color: #999;">
                        <?php echo date('h:i A', strtotime($commission['created_at'])); ?>
                    </div>
                </div>
            </div>
            
            <!-- Quick Info Badges -->
            <div style="margin-bottom: 2rem; display: flex; gap: 1rem; flex-wrap: wrap;">
                <div style="background: white; padding: 0.5rem 1.5rem; border-radius: 30px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
                    <strong>Payment Method:</strong> 
                    <?php echo htmlspecialchars($commission['payment_method'] ?? 'To be discussed'); ?>
                </div>
                
                <?php if (!empty($commission['budget'])): ?>
                <div style="background: white; padding: 0.5rem 1.5rem; border-radius: 30px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
                    <strong>Budget:</strong> 
                    NRs <?php echo number_format($commission['budget'], 2); ?>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($commission['deadline'])): ?>
                <div style="background: white; padding: 0.5rem 1.5rem; border-radius: 30px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
                    <strong>Deadline:</strong> 
                    <?php echo date('M d, Y', strtotime($commission['deadline'])); ?>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($commission['delivered_at'])): ?>
                <div style="background: white; padding: 0.5rem 1.5rem; border-radius: 30px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
                    <strong>Delivered:</strong> 
                    <?php echo date('M d, Y', strtotime($commission['delivered_at'])); ?>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($commission['client_approved_at'])): ?>
                <div style="background: white; padding: 0.5rem 1.5rem; border-radius: 30px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
                    <strong>Approved:</strong> 
                    <?php echo date('M d, Y', strtotime($commission['client_approved_at'])); ?>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Client and Artist Info -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
                <!-- Client Info -->
                <div class="data-table-container">
                    <h3><i class="fas fa-user"></i> Client Information</h3>
                    <table style="width: 100%;">
                        <tr>
                            <th style="width: 120px;">Name:</th>
                            <td><?php echo htmlspecialchars($commission['client_fullname'] ?? $commission['client_name']); ?></td>
                        </tr>
                        <tr>
                            <th>Username:</th>
                            <td><?php echo htmlspecialchars($commission['client_name']); ?></td>
                        </tr>
                        <tr>
                            <th>Email:</th>
                            <td><?php echo htmlspecialchars($commission['client_email']); ?></td>
                        </tr>
                    </table>
                </div>
                
                <!-- Artist Info -->
                <div class="data-table-container">
                    <h3><i class="fas fa-paint-brush"></i> Artist Information</h3>
                    <table style="width: 100%;">
                        <tr>
                            <th style="width: 120px;">Name:</th>
                            <td><?php echo htmlspecialchars($commission['artist_fullname'] ?? $commission['artist_name']); ?></td>
                        </tr>
                        <tr>
                            <th>Username:</th>
                            <td><?php echo htmlspecialchars($commission['artist_name']); ?></td>
                        </tr>
                        <tr>
                            <th>Email:</th>
                            <td><?php echo htmlspecialchars($commission['artist_email']); ?></td>
                        </tr>
                        <tr>
                            <th>Specialization:</th>
                            <td><?php echo htmlspecialchars($commission['specialization'] ?? 'Not specified'); ?></td>
                        </tr>
                        <tr>
                            <th>Experience:</th>
                            <td><?php echo $commission['experience_years'] ?? 0; ?> years</td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <!-- Commission Details -->
            <div class="data-table-container" style="margin-bottom: 2rem;">
                <h3><i class="fas fa-handshake"></i> Commission Details</h3>
                <table style="width: 100%;">
                    <tr>
                        <th style="width: 150px;">Title:</th>
                        <td><?php echo htmlspecialchars($commission['title']); ?></td>
                    </tr>
                    <tr>
                        <th>Description:</th>
                        <td><?php echo nl2br(htmlspecialchars($commission['description'])); ?></td>
                    </tr>
                </table>
            </div>
            
            <!-- Delivery Information Section -->
            <div class="data-table-container" style="margin-bottom: 2rem;">
                <h3><i class="fas fa-truck"></i> Delivery Information</h3>
                
                <?php if (!empty($commission['delivery_file'])): ?>
                <div style="margin-bottom: 20px;">
                    <strong>Delivered Artwork:</strong><br>
                    <div style="margin-top: 10px; text-align: center; background: #f8f9fa; padding: 20px; border-radius: 5px;">
                        <img src="../uploads/artworks/<?php echo $commission['delivery_file']; ?>" 
                             style="max-width: 100%; max-height: 300px; border-radius: 5px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                        <br>
                        <a href="../uploads/artworks/<?php echo $commission['delivery_file']; ?>" download 
                           style="display: inline-block; margin-top: 10px; background: #3498db; color: white; padding: 5px 15px; border-radius: 5px; text-decoration: none;">
                            <i class="fas fa-download"></i> Download Artwork
                        </a>
                    </div>
                </div>
                <?php endif; ?>
                
                <table style="width: 100%;">
                    <tr>
                        <th style="width: 150px;">Delivery Status:</th>
                        <td>
                            <?php 
                            $delivery_status = $commission['delivery_status'] ?? 'not_started';
                            $status_colors = [
                                'not_started' => ['bg' => '#f8f9fa', 'color' => '#6c757d'],
                                'pending_review' => ['bg' => '#fff3cd', 'color' => '#856404'],
                                'approved' => ['bg' => '#d4edda', 'color' => '#155724'],
                                'rejected' => ['bg' => '#f8d7da', 'color' => '#721c24'],
                                'revision_requested' => ['bg' => '#cce5ff', 'color' => '#004085'],
                                'delivered' => ['bg' => '#d4edda', 'color' => '#155724']
                            ];
                            $color = $status_colors[$delivery_status] ?? $status_colors['not_started'];
                            ?>
                            <span style="padding: 5px 15px; border-radius: 20px; background: <?php echo $color['bg']; ?>; color: <?php echo $color['color']; ?>;">
                                <?php echo ucfirst(str_replace('_', ' ', $delivery_status)); ?>
                            </span>
                        </td>
                    </tr>
                    
                    <?php if (!empty($commission['delivered_at'])): ?>
                    <tr>
                        <th>Delivered On:</th>
                        <td><?php echo date('F j, Y g:i A', strtotime($commission['delivered_at'])); ?></td>
                    </tr>
                    <?php endif; ?>
                    
                    <?php if (!empty($commission['client_approved_at'])): ?>
                    <tr>
                        <th>Approved On:</th>
                        <td><?php echo date('F j, Y g:i A', strtotime($commission['client_approved_at'])); ?></td>
                    </tr>
                    <?php endif; ?>
                    
                    <?php if (!empty($commission['revision_notes'])): ?>
                    <tr>
                        <th>Revision Notes:</th>
                        <td>
                            <div style="background: #fff3cd; padding: 15px; border-radius: 5px;">
                                <?php echo nl2br(htmlspecialchars($commission['revision_notes'])); ?>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                    
                    <?php if (!empty($commission['revision_count']) && $commission['revision_count'] > 0): ?>
                    <tr>
                        <th>Revision Count:</th>
                        <td><?php echo $commission['revision_count']; ?></td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>
            
            <!-- Payment Information Section -->
            <div class="data-table-container" style="margin-bottom: 2rem;">
                <h3><i class="fas fa-credit-card"></i> Payment Information</h3>
                <table style="width: 100%;">
                    <tr>
                        <th style="width: 150px;">Payment Method:</th>
                        <td><?php echo htmlspecialchars($commission['payment_method'] ?? 'To be discussed'); ?></td>
                    </tr>
                    <tr>
                        <th>Payment Status:</th>
                        <td>
                            <?php 
                            $payment_status = $commission['payment_status'] ?? 'pending';
                            $payment_colors = [
                                'pending' => ['bg' => '#fff3cd', 'color' => '#856404'],
                                'paid' => ['bg' => '#d4edda', 'color' => '#155724'],
                                'failed' => ['bg' => '#f8d7da', 'color' => '#721c24']
                            ];
                            $color = $payment_colors[$payment_status] ?? $payment_colors['pending'];
                            ?>
                            <span style="padding: 5px 15px; border-radius: 20px; background: <?php echo $color['bg']; ?>; color: <?php echo $color['color']; ?>;">
                                <?php echo ucfirst($payment_status); ?>
                            </span>
                        </td>
                    </tr>
                    
                    <?php if (!empty($commission['paid_at'])): ?>
                    <tr>
                        <th>Paid On:</th>
                        <td><?php echo date('F j, Y g:i A', strtotime($commission['paid_at'])); ?></td>
                    </tr>
                    <?php endif; ?>
                    
                    <?php if (!empty($commission['transaction_id'])): ?>
                    <tr>
                        <th>Transaction ID:</th>
                        <td><code><?php echo htmlspecialchars($commission['transaction_id']); ?></code></td>
                    </tr>
                    <?php endif; ?>
                    
                    <tr>
                        <th>Budget:</th>
                        <td><strong style="font-size: 1.2rem; color: #e74c3c;">NRs <?php echo number_format($commission['budget'] ?? 0, 2); ?></strong></td>
                    </tr>
                </table>
            </div>
            
            <!-- Timeline Section -->
            <div class="data-table-container">
                <h3><i class="fas fa-clock"></i> Complete Timeline</h3>
                <div style="position: relative; padding: 20px 0;">
                    <?php
                    $timeline = [
                        'created_at' => 'Commission Requested',
                        'updated_at' => 'Last Updated',
                        'delivered_at' => 'Artwork Delivered',
                        'client_approved_at' => 'Client Approved',
                        'paid_at' => 'Payment Completed'
                    ];
                    
                    $count = 0;
                    foreach ($timeline as $field => $label):
                        if (!empty($commission[$field])):
                            $count++;
                    ?>
                    <div style="display: flex; margin-bottom: 15px; position: relative; padding-left: 30px;">
                        <div style="position: absolute; left: 0; top: 0; width: 20px; height: 20px; background: <?php echo $count == 1 ? '#3498db' : '#27ae60'; ?>; border-radius: 50%;"></div>
                        <?php if ($count < 5): ?>
                        <div style="position: absolute; left: 9px; top: 20px; width: 2px; height: 35px; background: #ddd;"></div>
                        <?php endif; ?>
                        <div style="flex: 1;">
                            <div style="font-weight: bold;"><?php echo $label; ?></div>
                            <div style="color: #666;"><?php echo date('F j, Y g:i A', strtotime($commission[$field])); ?></div>
                        </div>
                    </div>
                    <?php 
                        endif;
                    endforeach; 
                    
                    if ($count == 0):
                        echo "<p style='color: #999; text-align: center;'>No timeline events yet</p>";
                    endif;
                    ?>
                </div>
            </div>
        </div>
    </main>
    
    <script>
    // Mobile menu toggle
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const sidebar = document.querySelector('.admin-sidebar');
    
    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', () => {
            sidebar.classList.toggle('active');
        });
    }
    
    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', (e) => {
        if (window.innerWidth <= 992 && 
            sidebar && 
            !sidebar.contains(e.target) && 
            !mobileMenuBtn.contains(e.target)) {
            sidebar.classList.remove('active');
        }
    });
    </script>
</body>
</html>