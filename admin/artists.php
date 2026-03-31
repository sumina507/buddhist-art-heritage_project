<?php
require_once '../includes/config.php';

// Check admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit;
}

$page_title = "Manage Artists";

// Handle approve/reject
if (isset($_GET['action']) && isset($_GET['id'])) {
    $artist_id = intval($_GET['id']);
    $action = $_GET['action'];
    
    if ($action == 'approve') {
        // Update artist status
        $sql = "UPDATE artists SET status = 'approved' WHERE artist_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $artist_id);
        
        if (mysqli_stmt_execute($stmt)) {
            // Also update user role to 'artist' if not already
            $update_user = "UPDATE users u JOIN artists a ON u.user_id = a.user_id 
                           SET u.role = 'artist' WHERE a.artist_id = ?";
            $stmt2 = mysqli_prepare($conn, $update_user);
            mysqli_stmt_bind_param($stmt2, "i", $artist_id);
            mysqli_stmt_execute($stmt2);
            
            $_SESSION['message'] = "Artist approved successfully!";
            $_SESSION['message_type'] = 'success';
        }
    } elseif ($action == 'reject') {
        $sql = "UPDATE artists SET status = 'rejected' WHERE artist_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $artist_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['message'] = "Artist rejected.";
            $_SESSION['message_type'] = 'warning';
        }
    }
    
    header('Location: artists.php');
    exit;
}

// Get stats for artists
$stats_sql = "SELECT 
    COUNT(*) as total,
    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
    COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved,
    COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected
    FROM artists";
$stats_result = mysqli_query($conn, $stats_sql);
$stats = mysqli_fetch_assoc($stats_result);
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
        /* Search Bar Styles */
        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 0.8rem;
            border-bottom: 1px solid #f0f0f0;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .table-header h2 {
            margin: 0;
            font-size: 1.2rem;
            color: #2c3e50;
        }
        
        .header-right {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .search-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }
        
        .search-icon {
            position: absolute;
            left: 12px;
            color: #95a5a6;
            font-size: 0.85rem;
        }
        
        #searchInput {
            padding: 0.5rem 0.8rem 0.5rem 2rem;
            border: 1px solid #e0e0e0;
            border-radius: 25px;
            font-size: 0.85rem;
            width: 220px;
            transition: all 0.3s;
            outline: none;
        }
        
        #searchInput:focus {
            border-color: #e74c3c;
            box-shadow: 0 0 0 2px rgba(231, 76, 60, 0.1);
            width: 260px;
        }
        
        .table-info {
            font-size: 0.8rem;
            color: #7f8c8d;
            background: #f8f9fa;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            white-space: nowrap;
        }
        
        /* Toast Notification */
        .toast-notification {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 1rem 2rem;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            display: flex;
            align-items: center;
            gap: 12px;
            z-index: 9999;
            animation: slideIn 0.3s ease;
            font-size: 1rem;
            font-weight: 500;
            min-width: 280px;
            justify-content: center;
            border-left: 4px solid;
        }
        
        .toast-success {
            border-left-color: #27ae60;
        }
        
        .toast-success i {
            color: #27ae60;
        }
        
        .toast-warning {
            border-left-color: #f39c12;
        }
        
        .toast-warning i {
            color: #f39c12;
        }
        
        .toast-error {
            border-left-color: #e74c3c;
        }
        
        .toast-error i {
            color: #e74c3c;
        }
        
        .toast-notification span {
            color: #2c3e50;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translate(-50%, -50%) scale(0.9);
            }
            to {
                opacity: 1;
                transform: translate(-50%, -50%) scale(1);
            }
        }
        
        @keyframes fadeOut {
            from {
                opacity: 1;
                transform: translate(-50%, -50%) scale(1);
            }
            to {
                opacity: 0;
                transform: translate(-50%, -50%) scale(0.9);
                visibility: hidden;
            }
        }
        
        /* Filter Tabs */
        .filter-tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }
        
        .filter-tab {
            padding: 0.5rem 1rem;
            background: #f8f9fa;
            color: #333;
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.3s;
        }
        
        .filter-tab:hover {
            background: #e9ecef;
        }
        
        .filter-tab.active {
            background: #e74c3c;
            color: white;
        }
        
        .quick-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1rem;
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.3rem;
        }
        
        .stat-info h3 {
            font-size: 1.6rem;
            margin: 0;
            color: #2c3e50;
        }
        
        .stat-info p {
            margin: 0;
            font-size: 0.8rem;
            color: #7f8c8d;
        }
        
        .status-badge {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-block;
        }
        
        .status-approved {
            background: #d4edda;
            color: #155724;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-rejected {
            background: #f8d7da;
            color: #721c24;
        }
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .btn-small {
            padding: 0.3rem 0.8rem;
            border-radius: 5px;
            text-decoration: none;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.2s;
        }
        
        .btn-small:hover {
            transform: translateY(-2px);
            opacity: 0.9;
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
                <h2>Manage Artists</h2>
            </div>
        </div>
        
        <div class="admin-content">
            <!-- Toast Messages -->
            <?php if (isset($_SESSION['message'])): ?>
                <div class="toast-notification toast-<?php echo $_SESSION['message_type']; ?>">
                    <i class="fas <?php echo $_SESSION['message_type'] == 'success' ? 'fa-check-circle' : ($_SESSION['message_type'] == 'warning' ? 'fa-exclamation-triangle' : 'fa-exclamation-circle'); ?>"></i>
                    <span><?php echo $_SESSION['message']; ?></span>
                </div>
                <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
            <?php endif; ?>
            
            <!-- Quick Stats -->
            <div class="quick-stats">
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #667eea, #764ba2);">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['total']; ?></h3>
                        <p>Total Artists</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #f39c12, #e67e22);">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['pending']; ?></h3>
                        <p>Pending Approval</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #27ae60, #2ecc71);">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['approved']; ?></h3>
                        <p>Approved</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #e74c3c, #c0392b);">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['rejected']; ?></h3>
                        <p>Rejected</p>
                    </div>
                </div>
            </div>
            
            <!-- Filter Tabs -->
            <div class="filter-tabs">
                <a href="?status=all" class="filter-tab <?php echo (!isset($_GET['status']) || $_GET['status'] == 'all') ? 'active' : ''; ?>">All (<?php echo $stats['total']; ?>)</a>
                <a href="?status=pending" class="filter-tab <?php echo (isset($_GET['status']) && $_GET['status'] == 'pending') ? 'active' : ''; ?>">Pending (<?php echo $stats['pending']; ?>)</a>
                <a href="?status=approved" class="filter-tab <?php echo (isset($_GET['status']) && $_GET['status'] == 'approved') ? 'active' : ''; ?>">Approved (<?php echo $stats['approved']; ?>)</a>
                <a href="?status=rejected" class="filter-tab <?php echo (isset($_GET['status']) && $_GET['status'] == 'rejected') ? 'active' : ''; ?>">Rejected (<?php echo $stats['rejected']; ?>)</a>
            </div>
            
            <!-- Artists Table with Search -->
            <div class="data-table-container">
                <div class="table-header">
                    <h2>Artists List</h2>
                    <div class="header-right">
                        <div class="search-wrapper">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" id="searchInput" placeholder="Search by name or email..." autocomplete="off">
                        </div>
                        <div class="table-info" id="tableInfo"><?php echo $stats['total']; ?> artists</div>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="data-table" id="artistsTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Artist</th>
                                <th>Email</th>
                                <th>Specialization</th>
                                <th>Experience</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </thead>
                        <tbody id="tableBody">
                            <?php
                            // Build query based on filter
                            $where = "";
                            if (isset($_GET['status']) && $_GET['status'] != 'all') {
                                $status = mysqli_real_escape_string($conn, $_GET['status']);
                                $where = "WHERE a.status = '$status'";
                            }
                            
                            $sql = "SELECT a.*, u.username, u.email, u.full_name, u.profile_image, u.created_at 
                                    FROM artists a
                                    JOIN users u ON a.user_id = u.user_id
                                    $where
                                    ORDER BY 
                                        CASE a.status 
                                            WHEN 'pending' THEN 1 
                                            WHEN 'approved' THEN 2 
                                            ELSE 3 
                                        END,
                                        u.created_at ASC";
                            
                            $result = mysqli_query($conn, $sql);
                            
                            if ($result && mysqli_num_rows($result) > 0) {
                                while ($artist = mysqli_fetch_assoc($result)) {
                                    $status_text = $artist['status'] ?? 'pending';
                                    $artist_name = $artist['full_name'] ?? $artist['username'];
                                    ?>
                                    <tr data-name="<?php echo strtolower(htmlspecialchars($artist_name)); ?>" 
                                        data-email="<?php echo strtolower(htmlspecialchars($artist['email'])); ?>">
                                        <td>#<?php echo $artist['artist_id']; ?></td>
                                        <td>
                                            <div class="user-cell">
                                                <img src="../uploads/profiles/<?php echo $artist['profile_image'] ?? 'default.jpg'; ?>" 
                                                     alt="<?php echo htmlspecialchars($artist['username']); ?>" 
                                                     class="user-avatar">
                                                <div>
                                                    <strong><?php echo htmlspecialchars($artist_name); ?></strong>
                                                    <small>@<?php echo htmlspecialchars($artist['username']); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($artist['email']); ?></td>
                                        <td><?php echo htmlspecialchars($artist['specialization'] ?? 'Not specified'); ?></td>
                                        <td><?php echo $artist['experience_years'] ?? 0; ?> years</td>
                                        <td>
                                            <span class="status-badge status-<?php echo $status_text; ?>">
                                                <?php echo ucfirst($status_text); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <?php if ($status_text == 'pending'): ?>
                                                    <a href="?action=approve&id=<?php echo $artist['artist_id']; ?>" 
                                                       class="btn-small" style="background: #28a745; color: white;"
                                                       onclick="return confirm('Approve this artist?')">
                                                       <i class="fas fa-check"></i> Approve
                                                    </a>
                                                    <a href="?action=reject&id=<?php echo $artist['artist_id']; ?>" 
                                                       class="btn-small" style="background: #dc3545; color: white;"
                                                       onclick="return confirm('Reject this artist?')">
                                                       <i class="fas fa-times"></i> Reject
                                                    </a>
                                                <?php elseif ($status_text == 'approved'): ?>
                                                    <a href="?action=reject&id=<?php echo $artist['artist_id']; ?>" 
                                                       class="btn-small" style="background: #dc3545; color: white;"
                                                       onclick="return confirm('Reject this artist?')">
                                                       <i class="fas fa-times"></i> Reject
                                                    </a>
                                                <?php elseif ($status_text == 'rejected'): ?>
                                                    <a href="?action=approve&id=<?php echo $artist['artist_id']; ?>" 
                                                       class="btn-small" style="background: #28a745; color: white;"
                                                       onclick="return confirm('Approve this artist?')">
                                                       <i class="fas fa-check"></i> Approve
                                                    </a>
                                                <?php endif; ?>
                                                
                                                <a href="user-edit.php?id=<?php echo $artist['user_id']; ?>" 
                                                   class="btn-small" style="background: #17a2b8; color: white;">
                                                   <i class="fas fa-edit"></i> Edit
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php
                                }
                            } else {
                                echo '<tr class="no-data-row">
                                        <td colspan="7" class="no-data">
                                            <i class="fas fa-users-slash"></i>
                                            <h4>No artists found</h4>
                                            <p>Artists will appear here when they register</p>
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
    // Search functionality
    document.getElementById('searchInput').addEventListener('keyup', function() {
        const searchTerm = this.value.toLowerCase().trim();
        const rows = document.querySelectorAll('#tableBody tr');
        let visibleCount = 0;
        
        rows.forEach(row => {
            if (row.classList.contains('no-data-row')) return;
            
            const name = row.getAttribute('data-name') || '';
            const email = row.getAttribute('data-email') || '';
            
            if (searchTerm === '' || name.includes(searchTerm) || email.includes(searchTerm)) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });
        
        const tableInfo = document.getElementById('tableInfo');
        const totalArtists = <?php echo $stats['total']; ?>;
        
        if (searchTerm === '') {
            tableInfo.innerHTML = totalArtists + ' artists';
        } else {
            tableInfo.innerHTML = visibleCount + ' result' + (visibleCount != 1 ? 's' : '') + ' found';
        }
        
        // Show no results message
        const noDataRow = document.querySelector('.no-data-row');
        if (visibleCount === 0 && !noDataRow) {
            const tbody = document.getElementById('tableBody');
            const existingNoResult = document.getElementById('noResultRow');
            if (!existingNoResult) {
                const noResultRow = document.createElement('tr');
                noResultRow.id = 'noResultRow';
                noResultRow.innerHTML = '<td colspan="7" class="no-data" style="text-align: center; padding: 2rem;"><i class="fas fa-search"></i><h4>No artists found</h4><p>Try a different search term</p>'+'</td>';
                tbody.appendChild(noResultRow);
            }
        } else {
            const noResultRow = document.getElementById('noResultRow');
            if (noResultRow) noResultRow.remove();
        }
    });
    
    // Auto hide toast notification after 2 seconds
    document.addEventListener('DOMContentLoaded', function() {
        var toast = document.querySelector('.toast-notification');
        if (toast) {
            setTimeout(function() {
                toast.style.animation = 'fadeOut 0.3s ease forwards';
                setTimeout(function() {
                    toast.remove();
                }, 300);
            }, 2000);
        }
    });
    
    // Mobile menu toggle
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const sidebar = document.querySelector('.admin-sidebar');
    
    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });
    }
    
    document.addEventListener('click', function(e) {
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