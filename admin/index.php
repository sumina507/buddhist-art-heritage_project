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
            <!-- Quick Stats -->
            <div class="quick-stats">
                <?php
                // Get stats
                $stats_sql = "SELECT 
                    (SELECT COUNT(*) FROM users) as total_users,
                    (SELECT COUNT(*) FROM users WHERE role = 'artist') as total_artists,
                    (SELECT COUNT(*) FROM artists WHERE status = 'pending') as pending_artists,
                    (SELECT COUNT(*) FROM artworks) as total_artworks,
                    (SELECT COUNT(*) FROM commissions WHERE status = 'pending') as pending_commissions";
                
                $stats_result = mysqli_query($conn, $stats_sql);
                $stats = mysqli_fetch_assoc($stats_result);
                ?>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #667eea, #764ba2);">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['total_users']; ?></h3>
                        <p style="color:white">Total Users </p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe, #00f2fe);">
                        <i class="fas fa-paint-brush"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['total_artists']; ?></h3>
                        <p style="color: white;">Artists</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #fa709a, #fee140);">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['pending_artists']; ?></h3>
                        <p style="color: white;">Pending Approval</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #43e97b, #38f9d7);">
                        <i class="fas fa-palette"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['total_artworks']; ?></h3>
                        <p style="color: white;">Artworks</p>
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
                                    <td><?php echo $artist['experience_years'] ?? 0; ?> years</td>
                                    <td>
                                        <a href="artists.php?approve=<?php echo $artist['artist_id']; ?>" class="btn-small" style="background: var(--success-color); color: white;">
                                            Approve
                                        </a>
                                    </td>
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