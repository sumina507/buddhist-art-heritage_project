<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'Buddhist Art Heritage'; ?></title>
    
    <!-- Main CSS -->
    <link rel="stylesheet" href="css/style.css">
    
    <!-- Navbar & Footer CSS (Load after main CSS) -->
    <link rel="stylesheet" href="css/navbar-footer.css?v=<?php echo time(); ?>">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@300;400;700&family=Open+Sans:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <?php if (isset($additional_css)): ?>
        <link rel="stylesheet" href="<?php echo $additional_css; ?>">
    <?php endif; ?>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container nav-container">
            <a href="index.php" class="logo">
                <i class="fas fa-lotus"></i>
                <span>Buddhist Art Heritage</span>
            </a>
            
            <!-- Mobile Menu Toggle -->
            <button class="mobile-menu-btn" id="mobileMenuToggle">
                <i class="fas fa-bars"></i>
            </button>
            
            <ul class="nav-links" id="navLinks">
                <li class="<?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">
                    <a href="index.php"><i class="fas fa-home"></i> Home</a>
                </li>
                <li class="<?php echo ($current_page == 'gallery.php') ? 'active' : ''; ?>">
                    <a href="gallery.php"><i class="fas fa-images"></i> Gallery</a>
                </li>
                <li class="<?php echo ($current_page == 'artists.php') ? 'active' : ''; ?>">
                    <a href="artists.php"><i class="fas fa-paint-brush"></i> Artists</a>
                </li>
            <li class="<?php echo ($current_page == 'about.php') ? 'active' : ''; ?>">
    <a href="about.php"><i class="fas fa-book"></i> About Us</a>
</li>
                
                <?php if (isset($_SESSION['user_id'])): ?>
                    <!-- User is logged in -->
                    <li class="dropdown">
                        <a href="#" class="dropdown-toggle welcome-user">
                            <i class="fas fa-user-circle"></i>
                            <span class="welcome-text">Welcome, <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'User'); ?></span>
                            <i class="fas fa-caret-down"></i>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                            <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'artist'): ?>
                                <li><a href="upload-artwork.php"><i class="fas fa-upload"></i> Upload Artwork</a></li>
                                <li><a href="my-artworks.php"><i class="fas fa-palette"></i> My Artworks</a></li>
                            <?php endif; ?>
                            <li><a href="profile.php"><i class="fas fa-user-edit"></i> My Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <!-- User is not logged in -->
                    <li class="<?php echo ($current_page == 'login.php') ? 'active' : ''; ?>">
                        <a href="login.php" class="btn-login">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

     <!-- Alert Messages - Centered with Auto-dismiss -->
    <?php if (isset($_SESSION['message'])): ?>
    <div id="customAlert" class="custom-alert alert-<?php echo $_SESSION['message_type'] ?? 'info'; ?>">
        <div class="alert-content">
            <div class="alert-icon">
                <?php if ($_SESSION['message_type'] == 'success'): ?>
                    <i class="fas fa-check-circle"></i>
                <?php elseif ($_SESSION['message_type'] == 'error'): ?>
                    <i class="fas fa-exclamation-circle"></i>
                <?php elseif ($_SESSION['message_type'] == 'warning'): ?>
                    <i class="fas fa-exclamation-triangle"></i>
                <?php else: ?>
                    <i class="fas fa-info-circle"></i>
                <?php endif; ?>
            </div>
            <p class="alert-message"><?php echo $_SESSION['message']; ?></p>
                </div>
                </div>
<script>
// Auto-dismiss alert after 2 seconds
document.addEventListener('DOMContentLoaded', function() {
    const alert = document.getElementById('customAlert');
    if (alert) {
        setTimeout(function() {
            alert.style.animation = 'fadeOutAlert 0.3s ease-out forwards';
            setTimeout(function() {
                if (alert && alert.parentNode) {
                    alert.remove();
                }
            }, 300);
        }, 2000);
    }
});
</script>
    <?php 
        unset($_SESSION['message']);
        unset($_SESSION['message_type']);
    endif; 
    ?>