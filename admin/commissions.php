<?php
require_once '../includes/config.php';  // Fixed path with ../

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit;
}

$page_title = "Manage Commissions";
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
                <h2>Manage Commissions</h2>
            </div>
            <div class="topbar-right">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']); ?></span>
            </div>
        </div>
        
        <div class="admin-content">
            <!-- Messages -->
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert" style="background: <?php echo $_SESSION['message_type'] == 'success' ? '#d4edda' : '#f8d7da'; ?>; color: <?php echo $_SESSION['message_type'] == 'success' ? '#155724' : '#721c24'; ?>;">
                    <?php 
                    echo $_SESSION['message'];
                    unset($_SESSION['message']);
                    unset($_SESSION['message_type']);
                    ?>
                </div>
            <?php endif; ?>
            
            <!-- Statistics -->
            <div class="quick-stats">
                <?php
                $stats_sql = "SELECT 
                    COUNT(*) as total,
                    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
                    COUNT(CASE WHEN status = 'accepted' THEN 1 END) as accepted,
                    COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as in_progress,
                    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed,
                    COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled
                    FROM commissions";
                $stats_result = mysqli_query($conn, $stats_sql);
                $stats = mysqli_fetch_assoc($stats_result);
                ?>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #667eea, #764ba2);">
                        <i class="fas fa-handshake"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['total']; ?></h3>
                        <p style="color: white;">Total Commissions</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #fa709a, #fee140);">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['pending']; ?></h3>
                        <p style="color: white;">Pending</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe, #00f2fe);">
                        <i class="fas fa-spinner"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['in_progress']; ?></h3>
                        <p style="color: white;">In Progress</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #43e97b, #38f9d7);">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['completed']; ?></h3>
                        <p style="color: white;">Completed</p>
                    </div>
                </div>
            </div>
            
            <!-- Filter Tabs -->
            <div class="filter-tabs">
                <a href="?status=all" class="filter-tab <?php echo (!isset($_GET['status']) || $_GET['status'] == 'all') ? 'active' : ''; ?>">All (<?php echo $stats['total']; ?>)</a>
                <a href="?status=pending" class="filter-tab <?php echo (isset($_GET['status']) && $_GET['status'] == 'pending') ? 'active' : ''; ?>">Pending (<?php echo $stats['pending']; ?>)</a>
                <a href="?status=in_progress" class="filter-tab <?php echo (isset($_GET['status']) && $_GET['status'] == 'in_progress') ? 'active' : ''; ?>">In Progress (<?php echo $stats['in_progress']; ?>)</a>
                <a href="?status=completed" class="filter-tab <?php echo (isset($_GET['status']) && $_GET['status'] == 'completed') ? 'active' : ''; ?>">Completed (<?php echo $stats['completed']; ?>)</a>
                <a href="?status=cancelled" class="filter-tab <?php echo (isset($_GET['status']) && $_GET['status'] == 'cancelled') ? 'active' : ''; ?>">Cancelled (<?php echo $stats['cancelled']; ?>)</a>
            </div>
            
            <!-- Commissions Table -->
           <!-- Commissions Table -->
<div class="data-table-container">
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Client</th>
                    <th>Artist</th>
                    <th>Title</th>
                    <th>Budget</th>
                    <th>Payment Method</th>  <!-- NEW COLUMN -->
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Build query with filter
                $where = "";
                if (isset($_GET['status']) && $_GET['status'] != 'all') {
                    $status = mysqli_real_escape_string($conn, $_GET['status']);
                    $where = "WHERE c.status = '$status'";
                }
                
                $sql = "SELECT c.*, 
                               u1.username as client_name, u1.full_name as client_fullname,
                               u2.username as artist_name, u2.full_name as artist_fullname
                        FROM commissions c
                        JOIN users u1 ON c.user_id = u1.user_id
                        JOIN artists a ON c.artist_id = a.artist_id
                        JOIN users u2 ON a.user_id = u2.user_id
                        $where
                        ORDER BY c.created_at ASC";
                
                $result = mysqli_query($conn, $sql);
                
                if ($result && mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        $status_class = '';
                        switch($row['status']) {
                            case 'pending': $status_class = 'status-pending'; break;
                            case 'accepted': $status_class = 'status-active'; break;
                            case 'in_progress': $status_class = 'status-pending'; break;
                            case 'completed': $status_class = 'status-active'; break;
                            case 'cancelled': $status_class = 'status-inactive'; break;
                        }
                        ?>
                        <tr>
                            <td>#<?php echo $row['commission_id']; ?></td>
                            <td><?php echo htmlspecialchars($row['client_fullname'] ?? $row['client_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['artist_fullname'] ?? $row['artist_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['title']); ?></td>
                            <td><?php echo $row['budget'] ? 'NRs ' . number_format($row['budget'], 2) : 'Not specified'; ?></td>
                            <td><?php echo htmlspecialchars($row['payment_method'] ?? 'To be discussed'); ?></td> <!-- NEW DATA -->
                            <td>
                                <span class="status-badge <?php echo $status_class; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $row['status'])); ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <a href="commission-details.php?id=<?php echo $row['commission_id']; ?>" 
                                       class="btn-small" style="background: var(--info-color); color: white;">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php
                    }
                } else {
                    echo '<tr>
                        <td colspan="9" class="no-data">  <!-- Changed from 8 to 9 columns -->
                            <i class="fas fa-handshake"></i>
                            <h4>No commissions found</h4>
                            <p>Commissions will appear here when clients request them</p>
                        </td>
                    </tr>';
                }
                ?>
            </tbody>
        </table>
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