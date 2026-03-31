<?php
require_once '../includes/config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit;
}

$page_title = "Manage Artworks";

// Handle artwork deletion
if (isset($_GET['delete_id'])) {
    $artwork_id = intval($_GET['delete_id']);
    
    // Get image path first to delete file
    $img_sql = "SELECT image_path FROM artworks WHERE artwork_id = ?";
    $img_stmt = mysqli_prepare($conn, $img_sql);
    mysqli_stmt_bind_param($img_stmt, "i", $artwork_id);
    mysqli_stmt_execute($img_stmt);
    $img_result = mysqli_stmt_get_result($img_stmt);
    $artwork = mysqli_fetch_assoc($img_result);
    
    // Delete from database
    $sql = "DELETE FROM artworks WHERE artwork_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $artwork_id);
    
    if (mysqli_stmt_execute($stmt)) {
        // Delete image file if not default
        if ($artwork && $artwork['image_path'] != 'default.jpg') {
            $file_path = '../uploads/artworks/' . $artwork['image_path'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }
        $_SESSION['message'] = "Artwork deleted successfully!";
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = "Error deleting artwork.";
        $_SESSION['message_type'] = 'error';
    }
    
    header('Location: artworks.php');
    exit;
}

// Get stats
$stats_sql = "SELECT 
    COUNT(*) as total,
    SUM(views) as total_views,
    SUM(likes) as total_likes,
    COUNT(DISTINCT artist_id) as total_artists
    FROM artworks";
$stats_result = mysqli_query($conn, $stats_sql);
$stats = mysqli_fetch_assoc($stats_result);

// Get today's uploads
$today_sql = "SELECT COUNT(*) as today FROM artworks WHERE DATE(created_at) = CURDATE()";
$today_result = mysqli_query($conn, $today_sql);
$today = mysqli_fetch_assoc($today_result);
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
        
        /* Quick Stats */
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
        
        /* Data Table */
        .data-table-container {
            background: white;
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
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
            font-size: 0.85rem;
            border-bottom: 2px solid #e9ecef;
        }
        
        .data-table td {
            padding: 0.8rem 0.5rem;
            border-bottom: 1px solid #f0f0f0;
            font-size: 0.85rem;
            color: #2c3e50;
        }
        
        .data-table tr:hover {
            background: #fef5f4;
        }
        
        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-icon {
            width: 32px;
            height: 32px;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: all 0.2s;
        }
        
        .btn-edit {
            background: #e8f4fc;
            color: #3498db;
        }
        
        .btn-edit:hover {
            background: #3498db;
            color: white;
        }
        
        .btn-delete {
            background: #f8d7da;
            color: #e74c3c;
        }
        
        .btn-delete:hover {
            background: #e74c3c;
            color: white;
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
        
        .no-data p {
            color: #7f8c8d;
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
                <h2>Manage Artworks</h2>
            </div>
            <div class="topbar-right">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']); ?></span>
            </div>
        </div>
        
        <div class="admin-content">
            <!-- Toast Messages -->
            <?php if (isset($_SESSION['message'])): ?>
                <div class="toast-notification toast-<?php echo $_SESSION['message_type']; ?>">
                    <i class="fas <?php echo $_SESSION['message_type'] == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                    <span><?php echo $_SESSION['message']; ?></span>
                </div>
                <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
            <?php endif; ?>
            
            <!-- Statistics -->
            <div class="quick-stats">
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #667eea, #764ba2);">
                        <i class="fas fa-palette"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['total']; ?></h3>
                        <p>Total Artworks</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe, #00f2fe);">
                        <i class="fas fa-eye"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($stats['total_views']); ?></h3>
                        <p>Total Views</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #fa709a, #fee140);">
                        <i class="fas fa-heart"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($stats['total_likes']); ?></h3>
                        <p>Total Likes</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #43e97b, #38f9d7);">
                        <i class="fas fa-calendar"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $today['today']; ?></h3>
                        <p>Uploaded Today</p>
                    </div>
                </div>
            </div>
            
            <!-- Artworks Table with Search -->
            <div class="data-table-container">
                <div class="table-header">
                    <h2>All Artworks</h2>
                    <div class="header-right">
                        <div class="search-wrapper">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" id="searchInput" placeholder="Search by title or artist..." autocomplete="off">
                        </div>
                        <div class="table-info" id="tableInfo"><?php echo $stats['total']; ?> artworks</div>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="data-table" id="artworksTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Image</th>
                                <th>Title</th>
                                <th>Artist</th>
                                <th>Views</th>
                                <th>Likes</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </thead>
                        <tbody id="tableBody">
                            <?php
                            $sql = "SELECT a.*, u.username, u.full_name as artist_name 
                                    FROM artworks a
                                    JOIN artists ar ON a.artist_id = ar.artist_id
                                    JOIN users u ON ar.user_id = u.user_id
                                    ORDER BY a.created_at ASC";
                            
                            $result = mysqli_query($conn, $sql);
                            
                            if ($result && mysqli_num_rows($result) > 0) {
                                while ($row = mysqli_fetch_assoc($result)) {
                                    $artist_name = $row['artist_name'] ?? $row['username'];
                                    ?>
                                    <tr data-title="<?php echo strtolower(htmlspecialchars($row['title'])); ?>" 
                                        data-artist="<?php echo strtolower(htmlspecialchars($artist_name)); ?>">
                                        <td>#<?php echo $row['artwork_id']; ?></td>
                                        <td>
                                            <img src="../uploads/artworks/<?php echo $row['image_path']; ?>" 
                                                 alt="<?php echo htmlspecialchars($row['title']); ?>"
                                                 style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px;">
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($row['title']); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($artist_name); ?></td>
                                        <td><?php echo $row['views']; ?></td>
                                        <td><?php echo $row['likes']; ?></td>
                                        <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="../artwork-detail.php?id=<?php echo $row['artwork_id']; ?>" 
                                                   class="btn-icon btn-edit" title="View" target="_blank">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="?delete_id=<?php echo $row['artwork_id']; ?>" 
                                                   class="btn-icon btn-delete" title="Delete"
                                                   onclick="return confirm('Delete this artwork? This cannot be undone.')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                         </td>
                                     </tr>
                                    <?php
                                }
                            } else {
                                echo '<tr class="no-data-row">
                                        <td colspan="8" class="no-data">
                                            <i class="fas fa-palette"></i>
                                            <h4>No artworks found</h4>
                                            <p>Artworks will appear here when artists upload them</p>
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
            
            const title = row.getAttribute('data-title') || '';
            const artist = row.getAttribute('data-artist') || '';
            
            if (searchTerm === '' || title.includes(searchTerm) || artist.includes(searchTerm)) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });
        
        const tableInfo = document.getElementById('tableInfo');
        const totalArtworks = <?php echo $stats['total']; ?>;
        
        if (searchTerm === '') {
            tableInfo.innerHTML = totalArtworks + ' artworks';
        } else {
            tableInfo.innerHTML = visibleCount + ' result' + (visibleCount != 1 ? 's' : '') + ' found';
        }
        
        // Show no results message
        if (visibleCount === 0 && !document.querySelector('.no-data-row')) {
            const tbody = document.getElementById('tableBody');
            const existingNoResult = document.getElementById('noResultRow');
            if (!existingNoResult) {
                const noResultRow = document.createElement('tr');
                noResultRow.id = 'noResultRow';
                noResultRow.innerHTML = '<td colspan="8" class="no-data" style="text-align: center; padding: 2rem;"><i class="fas fa-search"></i><h4>No artworks found</h4><p>Try a different search term</p></td>';
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