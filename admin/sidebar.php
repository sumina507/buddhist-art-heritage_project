<?php
?>
<aside class="admin-sidebar" id="sidebar" style="
    background: linear-gradient(135deg, #ffffff 0%, #fff8e7 100%) !important;
    color: #2c3e50 !important;
    box-shadow: 2px 0 20px rgba(0, 0, 0, 0.05) !important;
    border-right: 3px solid #f1c40f !important;
">
    <div class="admin-brand" style="border-bottom: 1px solid #e9ecef !important;">
        <h1 style="color: #2c3e50 !important;">
            <i class="fas fa-lotus" style="color: #e74c3c !important;"></i> 
            Admin Panel
        </h1>
    </div>
    
    <div class="admin-user" style="border-bottom: 1px solid #e9ecef !important;">
        <?php
        $admin_sql = "SELECT profile_image FROM users WHERE user_id = ?";
        $stmt = mysqli_prepare($conn, $admin_sql);
        mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $admin = mysqli_fetch_assoc($result);
        ?>
        <img src="../uploads/profiles/<?php echo $admin['profile_image']; ?>" alt="Admin" style="border-color: #f1c40f !important;">
        <h3 style="color: #2c3e50 !important;"><?php echo htmlspecialchars($_SESSION['full_name']); ?></h3>
        <p style="color: #6c757d !important;">Administrator</p>
    </div>
    
    <nav class="admin-nav">
        <ul style="list-style: none; padding: 1rem 0;">
            <li>
                <a href="index.php" <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'class="active"' : ''; ?> style="
                    display: flex;
                    align-items: center;
                    padding: 0.8rem 1.5rem;
                    color: #2c3e50;
                    text-decoration: none;
                    transition: all 0.3s;
                    gap: 10px;
                    border-radius: 0;
                    <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'background: linear-gradient(135deg, rgba(231, 76, 60, 0.15), rgba(241, 196, 15, 0.1)); border-left: 4px solid #f1c40f;' : ''; ?>
                ">
                    <i class="fas fa-tachometer-alt" style="color: #e74c3c; width: 20px;"></i> Dashboard
                </a>
            </li>
            <li>
                <a href="users.php" <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'class="active"' : ''; ?> style="
                    display: flex;
                    align-items: center;
                    padding: 0.8rem 1.5rem;
                    color: #2c3e50;
                    text-decoration: none;
                    transition: all 0.3s;
                    gap: 10px;
                    border-radius: 0;
                    <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'background: linear-gradient(135deg, rgba(231, 76, 60, 0.15), rgba(241, 196, 15, 0.1)); border-left: 4px solid #f1c40f;' : ''; ?>
                ">
                    <i class="fas fa-users" style="color: #e74c3c; width: 20px;"></i> Users
                </a>
            </li>
            <li>
                <a href="artists.php" <?php echo basename($_SERVER['PHP_SELF']) == 'artists.php' ? 'class="active"' : ''; ?> style="
                    display: flex;
                    align-items: center;
                    padding: 0.8rem 1.5rem;
                    color: #2c3e50;
                    text-decoration: none;
                    transition: all 0.3s;
                    gap: 10px;
                    border-radius: 0;
                    <?php echo basename($_SERVER['PHP_SELF']) == 'artists.php' ? 'background: linear-gradient(135deg, rgba(231, 76, 60, 0.15), rgba(241, 196, 15, 0.1)); border-left: 4px solid #f1c40f;' : ''; ?>
                ">
                    <i class="fas fa-paint-brush" style="color: #e74c3c; width: 20px;"></i> Artists
                </a>
            </li>
            <li>
                <a href="artworks.php" <?php echo basename($_SERVER['PHP_SELF']) == 'artworks.php' ? 'class="active"' : ''; ?> style="
                    display: flex;
                    align-items: center;
                    padding: 0.8rem 1.5rem;
                    color: #2c3e50;
                    text-decoration: none;
                    transition: all 0.3s;
                    gap: 10px;
                    border-radius: 0;
                    <?php echo basename($_SERVER['PHP_SELF']) == 'artworks.php' ? 'background: linear-gradient(135deg, rgba(231, 76, 60, 0.15), rgba(241, 196, 15, 0.1)); border-left: 4px solid #f1c40f;' : ''; ?>
                ">
                    <i class="fas fa-palette" style="color: #e74c3c; width: 20px;"></i> Artworks
                </a>
            </li>
            <li>
                <a href="commissions.php" <?php echo basename($_SERVER['PHP_SELF']) == 'commissions.php' ? 'class="active"' : ''; ?> style="
                    display: flex;
                    align-items: center;
                    padding: 0.8rem 1.5rem;
                    color: #2c3e50;
                    text-decoration: none;
                    transition: all 0.3s;
                    gap: 10px;
                    border-radius: 0;
                    <?php echo basename($_SERVER['PHP_SELF']) == 'commissions.php' ? 'background: linear-gradient(135deg, rgba(231, 76, 60, 0.15), rgba(241, 196, 15, 0.1)); border-left: 4px solid #f1c40f;' : ''; ?>
                ">
                    <i class="fas fa-handshake" style="color: #e74c3c; width: 20px;"></i> Custom Artworks Requests
                </a>
            </li>
            <li>
                <a href="../logout.php" style="
                    display: flex;
                    align-items: center;
                    padding: 0.8rem 1.5rem;
                    color: #2c3e50;
                    text-decoration: none;
                    transition: all 0.3s;
                    gap: 10px;
                    border-radius: 0;
                ">
                    <i class="fas fa-sign-out-alt" style="color: #e74c3c; width: 20px;"></i> Logout
                </a>
            </li>
        </ul>
    </nav>
</aside>

<style>
    /* Hover effects for sidebar links */
    .admin-nav a:hover {
        background: rgba(231, 76, 60, 0.08) !important;
        color: #e74c3c !important;
        padding-left: 1.8rem !important;
    }
    
    .admin-nav a:hover i {
        color: #f1c40f !important;
        transform: translateX(3px);
    }
</style>