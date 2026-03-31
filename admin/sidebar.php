<?php
?>
<aside class="admin-sidebar" id="sidebar">
    <div class="admin-brand">
        <h1><i class="fas fa-lotus"></i> Admin Panel</h1>
    </div>
    
    <div class="admin-user">
        <?php
        $admin_sql = "SELECT profile_image FROM users WHERE user_id = ?";
        $stmt = mysqli_prepare($conn, $admin_sql);
        mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $admin = mysqli_fetch_assoc($result);
        ?>
        <img src="../uploads/profiles/<?php echo $admin['profile_image']; ?>" alt="Admin">
        <h3><?php echo htmlspecialchars($_SESSION['full_name']); ?></h3>
        <p>Administrator</p>
    </div>
    
    <nav class="admin-nav">
        <ul>
            <li><a href="index.php" <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'class="active"' : ''; ?>>
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a></li>
            <li><a href="users.php" <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'class="active"' : ''; ?>>
                <i class="fas fa-users"></i> Users
            </a></li>
            <li><a href="artists.php" <?php echo basename($_SERVER['PHP_SELF']) == 'artists.php' ? 'class="active"' : ''; ?>>
                <i class="fas fa-paint-brush"></i> Artists
            </a></li>
            <li><a href="artworks.php" <?php echo basename($_SERVER['PHP_SELF']) == 'artworks.php' ? 'class="active"' : ''; ?>>
                <i class="fas fa-palette"></i> Artworks
            </a></li>
            <li><a href="commissions.php" <?php echo basename($_SERVER['PHP_SELF']) == 'commissions.php' ? 'class="active"' : ''; ?>>
                <i class="fas fa-handshake"></i> Commissions
            </a></li>
            <li><a href="articles.php" <?php echo basename($_SERVER['PHP_SELF']) == 'articles.php' ? 'class="active"' : ''; ?>>
                <i class="fas fa-book"></i> Knowledge Base
            </a></li>
            <li><a href="settings.php" <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'class="active"' : ''; ?>>
                <i class="fas fa-cog"></i> Settings
            </a></li>
            <li><a href="../logout.php">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a></li>
        </ul>
    </nav>
</aside>