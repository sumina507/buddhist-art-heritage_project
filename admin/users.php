<?php
require_once '../includes/config.php';

// Check admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit;
}

$page_title = "Manage Users";

// Handle delete
if (isset($_GET['delete_id'])) {
    $user_id = intval($_GET['delete_id']);
    
    // Prevent deleting yourself
    if ($user_id == $_SESSION['user_id']) {
        $_SESSION['message'] = "You cannot delete your own account!";
        $_SESSION['message_type'] = 'error';
    } else {
        $sql = "DELETE FROM users WHERE user_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['message'] = "User deleted successfully!";
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = "Error deleting user.";
            $_SESSION['message_type'] = 'error';
        }
    }
    header('Location: users.php');
    exit;
}

// Get all users for stats
$stats_sql = "SELECT 
    COUNT(*) as total,
    COUNT(CASE WHEN role = 'admin' THEN 1 END) as admins,
    COUNT(CASE WHEN role = 'artist' THEN 1 END) as artists,
    COUNT(CASE WHEN role = 'user' THEN 1 END) as users
    FROM users";
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
        /* Modern Stats Cards - Matching Dashboard */
        .quick-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 1.2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.3s;
            border: 1px solid #e9ecef;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        
        .stat-icon {
            width: 55px;
            height: 55px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        
        .stat-icon.total { background: #e8f4fc; color: #3498db; }
        .stat-icon.admins { background: #ffeaa7; color: #f39c12; }
        .stat-icon.artists { background: #a29bfe; color: #6c5ce7; }
        .stat-icon.users { background: #dfe6e9; color: #636e72; }
        
        .stat-info h3 {
            font-size: 1.8rem;
            font-weight: 700;
            color: #2c3e50;
            margin: 0;
        }
        
        .stat-info p {
            color: #6c757d;
            font-size: 0.85rem;
            margin: 0;
        }
        
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
        
        /* Data Table */
        .data-table-container {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border: 1px solid #e9ecef;
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
            vertical-align: middle;
        }
        
        .data-table tr:hover {
            background: #fef5f4;
        }
        
        /* User Cell */
        .user-cell {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .user-cell div {
            display: flex;
            flex-direction: column;
        }
        
        .user-cell strong {
            font-size: 0.9rem;
            color: #2c3e50;
        }
        
        .user-cell small {
            font-size: 0.7rem;
            color: #7f8c8d;
        }
        
        /* Role Badges */
        .role-badge {
            padding: 0.2rem 0.6rem;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
            display: inline-block;
        }
        
        .role-admin { background: #ffeaa7; color: #e17055; }
        .role-artist { background: #a29bfe; color: white; }
        .role-user { background: #dfe6e9; color: #636e72; }
        
        /* Status Badges */
        .status-badge {
            padding: 0.2rem 0.6rem;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
            display: inline-block;
            background: #d4edda;
            color: #155724;
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
        
        .btn-disabled {
            background: #f8f9fa;
            color: #adb5bd;
            cursor: not-allowed;
        }
        
        /* No Data */
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
            from { opacity: 0; transform: translate(-50%, -50%) scale(0.9); }
            to { opacity: 1; transform: translate(-50%, -50%) scale(1); }
        }
        
        @keyframes fadeOut {
            from { opacity: 1; transform: translate(-50%, -50%) scale(1); }
            to { opacity: 0; transform: translate(-50%, -50%) scale(0.9); visibility: hidden; }
        }
        
        /* Admin Topbar */
        .admin-topbar {
            background: white;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            border: 1px solid #e9ecef;
        }
        
        .topbar-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .topbar-left h2 {
            margin: 0;
            color: #2c3e50;
            font-size: 1.3rem;
        }
        
        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            font-size: 1.2rem;
            cursor: pointer;
            color: #2c3e50;
        }
        
        @media (max-width: 992px) {
            .quick-stats {
                grid-template-columns: repeat(2, 1fr);
            }
            .mobile-menu-btn {
                display: block;
            }
        }
        
        @media (max-width: 768px) {
            .quick-stats {
                grid-template-columns: 1fr;
            }
            .table-header {
                flex-direction: column;
                align-items: stretch;
            }
            .header-right {
                justify-content: space-between;
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
                <h2>Manage Users</h2>
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
            
            <!-- Quick Stats - Clean Design -->
            <div class="quick-stats">
                <div class="stat-card">
                    <div class="stat-icon total">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['total']; ?></h3>
                        <p>Total Users</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon admins">
                        <i class="fas fa-crown"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['admins']; ?></h3>
                        <p>Admins</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon artists">
                        <i class="fas fa-paint-brush"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['artists']; ?></h3>
                        <p>Artists</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon users">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['users']; ?></h3>
                        <p>Regular Users</p>
                    </div>
                </div>
            </div>
            
            <!-- Users Table with Search -->
            <div class="data-table-container">
                <div class="table-header">
                    <h2>All Users</h2>
                    <div class="header-right">
                        <div class="search-wrapper">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" id="searchInput" placeholder="Search by name or email..." autocomplete="off">
                        </div>
                        <div class="table-info" id="tableInfo"><?php echo $stats['total']; ?> users</div>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="data-table" id="usersTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </thead>
                        <tbody id="tableBody">
                            <?php
$sql = "SELECT * FROM users WHERE role = 'user' ORDER BY created_at ASC";                            $result = mysqli_query($conn, $sql);
                            
                            if (mysqli_num_rows($result) > 0) {
                                while ($user = mysqli_fetch_assoc($result)) {
                                    $is_current_user = ($user['user_id'] == $_SESSION['user_id']);
                                    ?>
                                    <tr data-name="<?php echo strtolower(htmlspecialchars($user['full_name'] ?? $user['username'])); ?>" 
                                        data-email="<?php echo strtolower(htmlspecialchars($user['email'])); ?>">
                                        <td><?php echo $user['user_id']; ?></td>
                                        <td>
                                            <div class="user-cell">
                                                <img src="../uploads/profiles/<?php echo $user['profile_image']; ?>" 
                                                     alt="<?php echo htmlspecialchars($user['username']); ?>" 
                                                     class="user-avatar">
                                                <div>
                                                    <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                                    <small><?php echo htmlspecialchars($user['full_name'] ?? 'No name'); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td>
                                            <span class="role-badge role-<?php echo $user['role']; ?>">
                                                <?php echo ucfirst($user['role']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="status-badge">Active</span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="user-edit.php?id=<?php echo $user['user_id']; ?>" 
                                                   class="btn-icon btn-edit" title="Edit User">
                                                   <i class="fas fa-edit"></i>
                                                </a>
                                                
                                                <?php if (!$is_current_user): ?>
                                                <a href="?delete_id=<?php echo $user['user_id']; ?>" 
                                                   class="btn-icon btn-delete" title="Delete User"
                                                   onclick="return confirm('Delete <?php echo htmlspecialchars($user['username']); ?>? This cannot be undone.')">
                                                   <i class="fas fa-trash"></i>
                                                </a>
                                                <?php else: ?>
                                                <span class="btn-icon btn-disabled" title="Your account">
                                                    <i class="fas fa-user-shield"></i>
                                                </span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php
                                }
                            } else {
                                echo '<tr class="no-data-row">
                                        <td colspan="7" class="no-data">
                                            <i class="fas fa-users-slash"></i>
                                            <h4>No users found</h4>
                                            <p>Add your first user to get started</p>
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
        if (searchTerm === '') {
            tableInfo.innerHTML = '<?php echo $stats['total']; ?> users';
        } else {
            tableInfo.innerHTML = visibleCount + ' result' + (visibleCount != 1 ? 's' : '') + ' found';
        }
        
        const noDataRow = document.querySelector('.no-data-row');
        if (visibleCount === 0 && !noDataRow) {
            const tbody = document.getElementById('tableBody');
            const existingNoResult = document.getElementById('noResultRow');
            if (!existingNoResult) {
                const noResultRow = document.createElement('tr');
                noResultRow.id = 'noResultRow';
                noResultRow.innerHTML = '<td colspan="7" class="no-data" style="text-align: center; padding: 2rem;"><i class="fas fa-search"></i><h4>No users found</h4><p>Try a different search term</p></td>';
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