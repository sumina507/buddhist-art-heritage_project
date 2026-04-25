<?php
require_once '../includes/config.php';

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
    <style>
        /* Cute Stats Cards */
        .quick-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.2rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 1.2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }
        
        .stat-icon {
            width: 55px;
            height: 55px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
        }
        
        .stat-info h3 {
            font-size: 1.8rem;
            margin: 0;
            color: #2c3e50;
        }
        
        .stat-info p {
            margin: 0;
            font-size: 0.8rem;
            color: #7f8c8d;
        }
        
        /* Status Badges */
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
            display: inline-block;
        }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-active { background: #d4edda; color: #155724; }
        .status-inactive { background: #f8d7da; color: #721c24; }
        
        /* Filter Tabs */
        .filter-tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }
        
        .filter-tab {
            padding: 0.5rem 1.2rem;
            background: #f8f9fa;
            color: #2c3e50;
            text-decoration: none;
            border-radius: 25px;
            font-size: 0.85rem;
            transition: all 0.2s;
        }
        
        .filter-tab:hover {
            background: #e9ecef;
        }
        
        .filter-tab.active {
            background: #e74c3c;
            color: white;
        }
        
        /* Data Table */
        .data-table-container {
            background: white;
            border-radius: 20px;
            padding: 1.2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 0.8rem;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .table-header h2 {
            font-size: 1.1rem;
            color: #2c3e50;
            margin: 0;
        }
        
        .table-header h2 i {
            color: #e74c3c;
            margin-right: 8px;
        }
        
        .table-info {
            background: #f8f9fa;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.75rem;
            color: #7f8c8d;
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table th {
            text-align: left;
            padding: 0.8rem 0.5rem;
            background: #f8f9fa;
            color: #2c3e50;
            font-weight: 600;
            font-size: 0.8rem;
            border-bottom: 2px solid #ecf0f1;
        }
        
        .data-table td {
            padding: 0.8rem 0.5rem;
            border-bottom: 1px solid #ecf0f1;
            font-size: 0.85rem;
            color: #2c3e50;
        }
        
        .data-table tr:hover {
            background: #fef5f4;
        }
        
        .payment-badge {
            background: #e8f4fc;
            color: #3498db;
            padding: 3px 8px;
            border-radius: 15px;
            font-size: 0.7rem;
            font-weight: 500;
            display: inline-block;
        }
        
        .btn-view {
            background: #e74c3c;
            color: white;
            padding: 5px 12px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.7rem;
            font-weight: 600;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn-view:hover {
            background: #c0392b;
            transform: translateY(-2px);
        }
        
        .no-data {
            text-align: center;
            padding: 3rem !important;
        }
        
        .no-data i {
            font-size: 3rem;
            color: #ddd;
            margin-bottom: 1rem;
        }
        
        .no-data h4 {
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .quick-stats {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .quick-stats {
                grid-template-columns: 1fr;
            }
            .filter-tabs {
                justify-content: center;
            }
        }
    </style>
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
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert" style="background: <?php echo $_SESSION['message_type'] == 'success' ? '#d4edda' : '#f8d7da'; ?>; color: <?php echo $_SESSION['message_type'] == 'success' ? '#155724' : '#721c24'; ?>; padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem;">
                    <?php 
                    echo $_SESSION['message'];
                    unset($_SESSION['message']);
                    unset($_SESSION['message_type']);
                    ?>
                </div>
            <?php endif; ?>
            
            <!-- Cute Statistics Cards -->
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
                    <div class="stat-icon" style="background: linear-gradient(135deg, #e74c3c, #c0392b);">
                        <i class="fas fa-handshake"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['total']; ?></h3>
                        <p>Total Commissions</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #f39c12, #e67e22);">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['pending']; ?></h3>
                        <p>Pending</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #3498db, #2980b9);">
                        <i class="fas fa-spinner"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['in_progress']; ?></h3>
                        <p>In Progress</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #27ae60, #219653);">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['completed']; ?></h3>
                        <p>Completed</p>
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
            <div class="data-table-container">
                <div class="table-header">
                    <h2><i class="fas fa-list"></i> All Commissions</h2>
                    <div class="table-info"><?php echo $stats['total']; ?> total</div>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Client</th>
                                <th>Artist</th>
                                <th>Title</th>
                                <th>Budget</th>
                                <th>Payment Method</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
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
                                    ORDER BY c.created_at DESC";
                            
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
                                        <td style="font-weight: 600;"><?php echo $row['commission_id']; ?></td>
                                        <td><?php echo htmlspecialchars($row['client_fullname'] ?? $row['client_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['artist_fullname'] ?? $row['artist_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['title']); ?></td>
                                        <td style="font-weight: 600; color: #27ae60;">NRs <?php echo number_format($row['budget'] ?? 0, 2); ?></td>
                                        <td><span class="payment-badge"><?php echo htmlspecialchars($row['payment_method'] ?? 'To be discussed'); ?></span></td>
                                        <td><span class="status-badge <?php echo $status_class; ?>"><?php echo ucfirst(str_replace('_', ' ', $row['status'])); ?></span></td>
                                        <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                                        <td><a href="commission-details.php?id=<?php echo $row['commission_id']; ?>" class="btn-view"><i class="fas fa-eye"></i> View</a></td>
                                    </tr>
                                    <?php
                                }
                            } else {
                                echo '<tr>
                                    <td colspan="9" class="no-data">
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
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const sidebar = document.querySelector('.admin-sidebar');
    
    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', () => {
            sidebar.classList.toggle('active');
        });
    }
    
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