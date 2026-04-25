<?php
// artist-profile.php - PUBLIC PROFILE VIEW (WITH COMMISSION COUNT)
require_once 'includes/config.php';

$artist_id = $_GET['id'] ?? 0;

if (!$artist_id) {
    $_SESSION['message'] = "No artist specified.";
    $_SESSION['message_type'] = 'error';
    header('Location: artists.php');
    exit;
}

// Get artist details - FIXED: bio comes from artists table (a.bio), not users table
$sql = "SELECT a.*, u.username, u.full_name, u.profile_image, u.email, u.created_at as user_since
        FROM artists a 
        JOIN users u ON a.user_id = u.user_id 
        WHERE a.artist_id = ? AND a.status = 'approved'";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $artist_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$artist = mysqli_fetch_assoc($result);

if (!$artist) {
    $_SESSION['message'] = "Artist not found or not approved yet.";
    $_SESSION['message_type'] = 'error';
    header('Location: artists.php');
    exit;
}

// Get artist's artworks with REAL likes count
$artworks_sql = "SELECT a.*, 
                        (SELECT COUNT(*) FROM artwork_likes WHERE artwork_id = a.artwork_id) as real_like_count
                 FROM artworks a 
                 WHERE a.artist_id = ? 
                 ORDER BY a.created_at DESC";
$stmt = mysqli_prepare($conn, $artworks_sql);
mysqli_stmt_bind_param($stmt, "i", $artist_id);
mysqli_stmt_execute($stmt);
$artworks_result = mysqli_stmt_get_result($stmt);
$total_artworks = mysqli_num_rows($artworks_result);

// Get total views and REAL likes from artwork_likes table
$stats_sql = "SELECT 
                SUM(views) as total_views,
                COUNT(*) as artwork_count
              FROM artworks 
              WHERE artist_id = ?";
$stmt = mysqli_prepare($conn, $stats_sql);
mysqli_stmt_bind_param($stmt, "i", $artist_id);
mysqli_stmt_execute($stmt);
$stats_result = mysqli_stmt_get_result($stmt);
$stats = mysqli_fetch_assoc($stats_result);

// Get REAL total likes from artwork_likes table
$likes_sql = "SELECT COUNT(*) as total_likes 
              FROM artwork_likes al
              JOIN artworks a ON al.artwork_id = a.artwork_id
              WHERE a.artist_id = ?";
$stmt = mysqli_prepare($conn, $likes_sql);
mysqli_stmt_bind_param($stmt, "i", $artist_id);
mysqli_stmt_execute($stmt);
$likes_result = mysqli_stmt_get_result($stmt);
$likes_data = mysqli_fetch_assoc($likes_result);
$total_real_likes = $likes_data['total_likes'] ?? 0;

// Get commission count
$commission_sql = "SELECT COUNT(*) as total_commissions 
                   FROM commissions 
                   WHERE artist_id = ? AND (status = 'completed' OR payment_status = 'completed')";
$stmt = mysqli_prepare($conn, $commission_sql);
mysqli_stmt_bind_param($stmt, "i", $artist_id);
mysqli_stmt_execute($stmt);
$commission_result = mysqli_stmt_get_result($stmt);
$commission_data = mysqli_fetch_assoc($commission_result);
$total_commissions = $commission_data['total_commissions'] ?? 0;

$page_title = htmlspecialchars($artist['full_name'] ?? $artist['username']) . " - Artist Profile";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Don't override body - only style the page container */
        .artist-profile-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 1rem;
            background: linear-gradient(135deg, #f9f7f1 0%, #f5f5f0 100%);
            border-radius: 0;
        }

        /* Breadcrumb */
        .artist-profile-container .breadcrumb {
            margin-bottom: 2rem;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .artist-profile-container .breadcrumb a {
            color: #666;
            text-decoration: none;
            transition: color 0.3s;
        }

        .artist-profile-container .breadcrumb a:hover {
            color: #ff8c00;
            text-decoration: underline;
        }

        .artist-profile-container .breadcrumb span {
            color: #333;
        }

        /* Artist Cover */
        .artist-profile-container .artist-cover {
            background: linear-gradient(135deg, #ff6b6b, #feca57);
            background-size: 400% 400%;
            animation: gradientShift 8s ease infinite;
            border-radius: 30px;
            position: relative;
            padding: 2.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
        }

        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .artist-profile-container .artist-header-content {
            position: relative;
            z-index: 2;
            display: flex;
            align-items: center;
            gap: 2rem;
            flex-wrap: wrap;
            color: white;
        }

        .artist-profile-container .artist-avatar-large {
            width: 130px;
            height: 130px;
            border-radius: 50%;
            border: 4px solid #ffd700;
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
            background: white;
            animation: borderPulse 2s infinite;
        }

        @keyframes borderPulse {
            0% { border-color: #ffd700; }
            50% { border-color: #ff6b6b; }
            100% { border-color: #ffd700; }
        }

        .artist-profile-container .artist-avatar-large img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .artist-profile-container .artist-header-info {
            flex: 1;
        }

        .artist-profile-container .artist-header-info h1 {
            font-size: 2.2rem;
            margin-bottom: 0.3rem;
            color: white;
            font-weight: 700;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }

        .artist-profile-container .artist-username {
            font-size: 1rem;
            opacity: 0.95;
            margin-bottom: 1rem;
            color: rgba(255,255,255,0.95);
        }

        .artist-profile-container .artist-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 0.8rem;
        }

        .artist-profile-container .badge {
            background: rgba(255,255,255,0.25);
            backdrop-filter: blur(8px);
            padding: 0.4rem 1.2rem;
            border-radius: 50px;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
            transition: all 0.3s;
        }

        .artist-profile-container .badge:nth-child(1) { background: rgba(255, 107, 107, 0.7); }
        .artist-profile-container .badge:nth-child(2) { background: rgba(72, 219, 251, 0.7); }
        .artist-profile-container .badge:nth-child(3) { background: rgba(255, 159, 243, 0.7); }

        .artist-profile-container .badge:hover {
            transform: translateY(-3px);
            background: rgba(255,255,255,0.4);
        }

        .artist-profile-container .badge i {
            color: #ffd700;
        }

        /* Commission Button */
        .artist-profile-container .btn-commission-header {
            background: linear-gradient(135deg, #ffd700, #ff8c00);
            color: #2c3e50;
            padding: 0.8rem 1.8rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .artist-profile-container .btn-commission-header:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
            background: linear-gradient(135deg, #ff8c00, #ffd700);
        }

        /* Stats Bar */
        .artist-profile-container .artist-stats-bar {
            background: white;
            border-radius: 25px;
            padding: 1.2rem;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-around;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            border: 1px solid #ffd700;
        }

        .artist-profile-container .stat-item {
            text-align: center;
            transition: all 0.3s;
            flex: 1;
        }

        .artist-profile-container .stat-item:hover {
            transform: translateY(-5px);
        }

        .artist-profile-container .stat-value {
            display: block;
            font-size: 1.8rem;
            font-weight: bold;
            background: linear-gradient(135deg, #ff6b6b, #feca57);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            line-height: 1.2;
        }

        .artist-profile-container .stat-label {
            color: #6c5ce7;
            font-size: 0.8rem;
            font-weight: 600;
        }

        /* Profile Grid */
        .artist-profile-container .profile-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 2rem;
        }

        /* Cards */
        .artist-profile-container .about-card,
        .artist-profile-container .specialization-card {
            background: white;
            border-radius: 25px;
            padding: 1.8rem;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            border: 1px solid #ffd700;
            transition: all 0.3s;
        }

        .artist-profile-container .about-card:hover,
        .artist-profile-container .specialization-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
        }

        .artist-profile-container .about-card h2,
        .artist-profile-container .specialization-card h3 {
            background: linear-gradient(135deg, #ff6b6b, #feca57);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 1.2rem;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.2rem;
            font-weight: 700;
        }

        .artist-profile-container .about-card h2 i,
        .artist-profile-container .specialization-card h3 i {
            color: #ff6b6b;
            font-size: 1.3rem;
        }

        .artist-profile-container .artist-bio {
            color: #2d3436;
            line-height: 1.7;
            margin-bottom: 1.5rem;
            font-size: 0.95rem;
        }

        .artist-profile-container .no-bio {
            color: #b2bec3;
            font-style: italic;
            margin-bottom: 1.5rem;
        }

        .artist-profile-container .artist-details {
            border-top: 1px solid #ffe0d0;
            padding-top: 1.2rem;
        }

        .artist-profile-container .detail-row {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 0.8rem;
            color: #636e72;
            font-size: 0.9rem;
        }

        .artist-profile-container .detail-row i {
            width: 22px;
            color: #ff6b6b;
            font-size: 1rem;
        }

        /* Specialization Tags */
        .artist-profile-container .specialization-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 0.6rem;
        }

        .artist-profile-container .spec-tag {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 0.4rem 1rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 500;
            transition: all 0.3s;
        }

        .artist-profile-container .spec-tag:nth-child(2) { background: linear-gradient(135deg, #f093fb, #f5576c); }
        .artist-profile-container .spec-tag:nth-child(3) { background: linear-gradient(135deg, #4facfe, #00f2fe); }
        .artist-profile-container .spec-tag:nth-child(4) { background: linear-gradient(135deg, #43e97b, #38f9d7); }

        .artist-profile-container .spec-tag:hover {
            transform: translateY(-3px);
            filter: brightness(1.1);
        }

        /* Right Column */
        .artist-profile-container .profile-right {
            background: white;
            border-radius: 25px;
            padding: 1.8rem;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            border: 1px solid #ffd700;
        }

        .artist-profile-container .artworks-header {
            margin-bottom: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }

        .artist-profile-container .artworks-header h2 {
            background: linear-gradient(135deg, #ff6b6b, #feca57);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.3rem;
            font-weight: 700;
        }

        .artist-profile-container .artworks-header h2 i {
            color: #ff6b6b;
        }

        .artist-profile-container .artwork-count {
            background: #ff6b6b;
            color: white;
            padding: 0.3rem 1rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        /* Artwork Grid */
        .artist-profile-container .artist-artworks-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(230px, 1fr));
            gap: 1.5rem;
        }

        .artist-profile-container .artwork-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.3s;
            border: 1px solid #f0f0f0;
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
        }

        .artist-profile-container .artwork-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
            border-color: #ffd700;
        }

        .artist-profile-container .artwork-image {
            position: relative;
            height: 180px;
            overflow: hidden;
        }

        .artist-profile-container .artwork-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s;
        }

        .artist-profile-container .artwork-card:hover .artwork-image img {
            transform: scale(1.05);
        }

        .artist-profile-container .artwork-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(44, 62, 80, 0.85);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s;
        }

        .artist-profile-container .artwork-card:hover .artwork-overlay {
            opacity: 1;
        }

        .artist-profile-container .view-btn {
            background: #f1c40f;
            color: black;
            padding: 0.6rem 1.3rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.85rem;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .artist-profile-container .view-btn:hover {
            color: white;
            background-color:#ff6b6b ;
            transform: scale(1.05);
        }

        .artist-profile-container .artwork-info {
            padding: 1rem;
        }

        .artist-profile-container .artwork-info h3 {
            color: #2c3e50;
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .artist-profile-container .artwork-stats {
            display: flex;
            gap: 1rem;
            font-size: 0.75rem;
            color: #636e72;
        }

        .artist-profile-container .artwork-stats i {
            color: #ff6b6b;
            margin-right: 4px;
        }

        /* No Artworks */
        .artist-profile-container .no-artworks {
            text-align: center;
            padding: 3rem;
            color: #b2bec3;
        }

        .artist-profile-container .no-artworks i {
            font-size: 3rem;
            background: linear-gradient(135deg, #ff6b6b, #feca57);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 1rem;
        }

        .artist-profile-container .no-artworks h3 {
            background: linear-gradient(135deg, #ff6b6b, #feca57);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 0.5rem;
            font-size: 1.2rem;
        }

        /* Specialization Card */
        .artist-profile-container .specialization-card {
            margin-top: 1.5rem;
        }

        /* Responsive */
        @media (max-width: 992px) {
            .artist-profile-container .profile-grid {
                grid-template-columns: 1fr;
            }
            
            .artist-profile-container .artist-header-content {
                flex-direction: column;
                text-align: center;
            }
            
            .artist-profile-container .artist-badges {
                justify-content: center;
            }
        }

        @media (max-width: 768px) {
            .artist-profile-container {
                padding: 1rem;
            }
            
            .artist-profile-container .artist-cover {
                padding: 1.5rem;
            }
            
            .artist-profile-container .artist-header-info h1 {
                font-size: 1.6rem;
            }
            
            .artist-profile-container .artist-stats-bar {
                flex-wrap: wrap;
                gap: 1rem;
            }
            
            .artist-profile-container .artist-artworks-grid {
                grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
                gap: 1rem;
            }
        }

        @media (max-width: 480px) {
            .artist-profile-container .artist-artworks-grid {
                grid-template-columns: 1fr;
            }
            
            .artist-profile-container .btn-commission-header {
                width: 100%;
                justify-content: center;
            }
            
            .artist-profile-container .artworks-header {
                flex-direction: column;
                gap: 0.5rem;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <?php require_once 'includes/navbar.php'; ?>

    <div class="artist-profile-container">
        <!-- Breadcrumb -->
        <nav class="breadcrumb">
            <a href="index.php"><i class="fas fa-home"></i> Home</a>
            <i class="fas fa-chevron-right"></i>
            <a href="artists.php"><i class="fas fa-paint-brush"></i> Artists</a>
            <i class="fas fa-chevron-right"></i>
            <span><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($artist['full_name'] ?? $artist['username']); ?></span>
        </nav>

        <!-- Artist Header with Animated Gradient -->
        <div class="artist-cover">
            <div class="artist-header-content">
                <div class="artist-avatar-large">
                    <img src="uploads/profiles/<?php echo htmlspecialchars($artist['profile_image'] ?? 'default.jpg'); ?>" 
                         alt="<?php echo htmlspecialchars($artist['full_name'] ?? $artist['username']); ?>">
                </div>
                <div class="artist-header-info">
                    <h1><?php echo htmlspecialchars($artist['full_name'] ?? $artist['username']); ?></h1>
                    <p class="artist-username">✨ @<?php echo htmlspecialchars($artist['username']); ?> ✨</p>
                    
                    <div class="artist-badges">
                        <?php if (!empty($artist['specialization'])): ?>
                            <span class="badge">
                                <i class="fas fa-paint-brush"></i> <?php echo htmlspecialchars($artist['specialization']); ?>
                            </span>
                        <?php endif; ?>
                        
                        <?php if ($artist['experience_years'] > 0): ?>
                            <span class="badge">
                                <i class="fas fa-calendar-alt"></i> <?php echo $artist['experience_years']; ?>+ years
                            </span>
                        <?php endif; ?>
                        
                        <span class="badge">
                            <i class="fas fa-heart"></i> Member since <?php echo date('M Y', strtotime($artist['user_since'])); ?>
                        </span>
                    </div>
                </div>
                
                <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] == 'user'): ?>
                    <a href="commission-request.php?artist_id=<?php echo $artist_id; ?>" class="btn-commission-header">
                        <i class="fas fa-handshake"></i> Request Custom Artworks ✨
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Stats Bar -->
        <div class="artist-stats-bar">
            <div class="stat-item">
                <span class="stat-value"><?php echo $total_artworks; ?></span>
                <span class="stat-label">Artworks</span>
            </div>
            <div class="stat-item">
                <span class="stat-value"><?php echo number_format($stats['total_views'] ?? 0); ?></span>
                <span class="stat-label">Views</span>
            </div>
            <div class="stat-item">
                <span class="stat-value"><?php echo number_format($total_real_likes); ?></span>
                <span class="stat-label">Likes</span>
            </div>
            <div class="stat-item">
                <span class="stat-value"><?php echo $total_commissions; ?></span>
                <span class="stat-label">Completed Projects</span>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="profile-grid">
            <!-- Left Column -->
            <div class="profile-left">
                <div class="about-card">
                    <h2><i class="fas fa-user-circle"></i> About the Artist</h2>
                    
                    <!-- FIXED: Using a.bio from artists table -->
                    <?php if (!empty($artist['bio'])): ?>
                        <div class="artist-bio">
                            <?php echo nl2br(htmlspecialchars($artist['bio'])); ?>
                        </div>
                    <?php else: ?>
                        <p class="no-bio">This artist hasn't added a biography yet. Stay tuned!</p>
                    <?php endif; ?>

                    <div class="artist-details">
                        <?php if (!empty($artist['contact_info'])): ?>
                            <div class="detail-row">
                                <i class="fas fa-phone-alt"></i>
                                <span><?php echo htmlspecialchars($artist['contact_info']); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($artist['email']) && isset($_SESSION['user_id'])): ?>
                            <div class="detail-row">
                                <i class="fas fa-envelope"></i>
                                <span><?php echo htmlspecialchars($artist['email']); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="specialization-card">
                    <h3><i class="fas fa-star"></i> Specialization</h3>
                    <div class="specialization-tags">
                        <?php
                        $specializations = explode(',', $artist['specialization'] ?? 'Traditional Art');
                        foreach ($specializations as $spec) {
                            echo '<span class="spec-tag">' . htmlspecialchars(trim($spec)) . '</span>';
                        }
                        ?>
                    </div>
                </div>
            </div>

            <!-- Right Column - Artworks Gallery -->
            <div class="profile-right">
                <div class="artworks-header">
                    <h2>🎨 Artworks Gallery</h2>
                    <?php if ($total_artworks > 0): ?>
                        <p class="artwork-count"><?php echo $total_artworks; ?> artwork<?php echo $total_artworks > 1 ? 's' : ''; ?></p>
                    <?php endif; ?>
                </div>

                <?php if ($total_artworks > 0): ?>
                    <div class="artist-artworks-grid">
                        <?php while ($artwork = mysqli_fetch_assoc($artworks_result)): ?>
                            <div class="artwork-card">
                                <div class="artwork-image">
                                    <img src="uploads/artworks/<?php echo htmlspecialchars($artwork['image_path']); ?>" 
                                         alt="<?php echo htmlspecialchars($artwork['title']); ?>">
                                    <div class="artwork-overlay">
                                        <a href="artwork-detail.php?id=<?php echo $artwork['artwork_id']; ?>" class="view-btn">
                                            </i> View
                                        </a>
                                    </div>
                                </div>
                                <div class="artwork-info">
                                    <h3><?php echo htmlspecialchars($artwork['title']); ?></h3>
                                    <div class="artwork-stats">
                                        <span title="Views">
                                            <i class="fas fa-eye"></i> <?php echo number_format($artwork['views']); ?>
                                        </span>
                                        <span title="Likes">
                                            <i class="fas fa-heart"></i> <?php echo number_format($artwork['real_like_count'] ?? $artwork['likes']); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="no-artworks">
                        <i class="fas fa-palette"></i>
                        <h3>No Artworks Yet</h3>
                        <p>This artist hasn't uploaded any artworks yet. Check back soon!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php require_once 'includes/footer.php'; ?>
</body>
</html>