<?php
require_once '../includes/config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit;
}

$page_title = "Admin Dashboard";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Buddhist Art Heritage</title>
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Modern Stats Cards */
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
        
        .stat-icon.users { background: #e8f4fc; color: #3498db; }
        .stat-icon.artists { background: #e8f4fc; color: #3498db; }
        .stat-icon.pending { background: #fff3cd; color: #f39c12; }
        .stat-icon.artworks { background: #e8f4fc; color: #3498db; }
        
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
        
        /* Data Tables */
        .data-table-container {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border: 1px solid #e9ecef;
        }
        
        .data-table-container h3 {
            color: #2c3e50;
            font-size: 1.1rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .data-table-container h3 i {
            color: #e74c3c;
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
        
        .user-cell {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .user-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            object-fit: cover;
        }
        
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
        
        .btn-small {
            background: #27ae60;
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 5px;
            text-decoration: none;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-block;
            transition: all 0.2s;
        }
        
        .btn-small:hover {
            background: #219653;
            transform: translateY(-2px);
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
        
        .topbar-right span {
            color: #6c757d;
            font-size: 0.9rem;
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
        }
    </style>
</head>
<body>
    <?php require_once 'sidebar.php'; ?>
    
    <main class="admin-main">
        <div class="admin-topbar">
            <div class="topbar-left">
                <button class="mobile-menu-btn" id="mobileMenuBtn">
                    <i class="fas fa-bars"></i>
                </button>
                <h2>Dashboard</h2>
            </div>
            <div class="topbar-right">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']); ?></span>
            </div>
        </div>
        
        <div class="admin-content">
            <!-- Quick Stats - Clean Design -->
            <div class="quick-stats">
                <?php
                $stats_sql = "SELECT 
                    (SELECT COUNT(*) FROM users) as total_users,
                    (SELECT COUNT(*) FROM users WHERE role = 'artist') as total_artists,
                    (SELECT COUNT(*) FROM artists WHERE status = 'pending') as pending_artists,
                    (SELECT COUNT(*) FROM artworks) as total_artworks";
                
                $stats_result = mysqli_query($conn, $stats_sql);
                $stats = mysqli_fetch_assoc($stats_result);
                ?>
                
                <div class="stat-card">
                    <div class="stat-icon users">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['total_users']; ?></h3>
                        <p>Total Users</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon artists">
                        <i class="fas fa-paint-brush"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['total_artists']; ?></h3>
                        <p>Artists</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon pending">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['pending_artists']; ?></h3>
                        <p>Pending Approval</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon artworks">
                        <i class="fas fa-palette"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['total_artworks']; ?></h3>
                        <p>Artworks</p>
                    </div>
                </div>
            </div>
            
            <!-- Recent Users -->
            <div class="data-table-container" style="margin-top: 2rem;">
                <h3><i class="fas fa-users"></i> Recent Users</h3>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Joined</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $recent_users = mysqli_query($conn, "SELECT * FROM users ORDER BY created_at DESC LIMIT 5");
                            while ($user = mysqli_fetch_assoc($recent_users)) {
                                ?>
                                <tr>
                                    <td>
                                        <div class="user-cell">
                                            <img src="../uploads/profiles/<?php echo $user['profile_image']; ?>" 
                                                 alt="<?php echo htmlspecialchars($user['username']); ?>" 
                                                 class="user-avatar">
                                            <span><?php echo htmlspecialchars($user['username']); ?></span>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <span class="role-badge role-<?php echo $user['role']; ?>">
                                            <?php echo ucfirst($user['role']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                </tr>
                                <?php
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Pending Artists -->
            <?php if ($stats['pending_artists'] > 0): ?>
            <div class="data-table-container" style="margin-top: 2rem;">
                <h3><i class="fas fa-clock"></i> Artists Pending Approval</h3>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Artist</th>
                                <th>Specialization</th>
                                <th>Experience</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $pending = mysqli_query($conn, "
                                SELECT a.*, u.username, u.full_name, u.email 
                                FROM artists a 
                                JOIN users u ON a.user_id = u.user_id 
                                WHERE a.status = 'pending' 
                                LIMIT 5
                            ");
                            while ($artist = mysqli_fetch_assoc($pending)) {
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($artist['full_name'] ?? $artist['username']); ?></td>
                                    <td><?php echo htmlspecialchars($artist['specialization'] ?? 'Not specified'); ?></td>
                                    <td><?php echo $artist['experience_years'] ?? 0; ?> years</div>
                                    <td>
                                        <a href="artists.php?approve=<?php echo $artist['artist_id']; ?>" class="btn-small">
                                            Approve
                                        </a>
                                    </div>
                                </tr>
                                <?php
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
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