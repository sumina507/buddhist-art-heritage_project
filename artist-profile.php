<?php
// artist-profile.php - PUBLIC PROFILE VIEW
require_once 'includes/config.php';
require_once 'includes/navbar.php';

$artist_id = $_GET['id'] ?? 0;

if (!$artist_id) {
    $_SESSION['message'] = "No artist specified.";
    $_SESSION['message_type'] = 'error';
    header('Location: artists.php');
    exit;
}

// Get artist details
$sql = "SELECT a.*, u.username, u.full_name, u.profile_image, u.bio, u.email, u.created_at as user_since
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

// Get artist average rating from completed commissions
$rating_sql = "SELECT AVG(satisfaction_rating) as avg_rating, COUNT(*) as total 
               FROM commissions 
               WHERE artist_id = ? AND payment_status = 'paid' AND satisfaction_rating IS NOT NULL";
$stmt = mysqli_prepare($conn, $rating_sql);
mysqli_stmt_bind_param($stmt, "i", $artist_id);
mysqli_stmt_execute($stmt);
$rating_result = mysqli_stmt_get_result($stmt);
$rating_data = mysqli_fetch_assoc($rating_result);
$avg_rating = round($rating_data['avg_rating'] ?? 0, 1);
$total_ratings = $rating_data['total'] ?? 0;

// Check if current user can rate (has completed commission with this artist)
$can_rate = false;
$user_rating = null;
if (isset($_SESSION['user_id'])) {
    // Check if user has completed a paid commission with this artist
    $check_sql = "SELECT c.commission_id, c.satisfaction_rating 
                  FROM commissions c
                  WHERE c.artist_id = ? AND c.user_id = ? 
                  AND c.payment_status = 'paid' AND c.delivery_status = 'delivered'";
    $stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($stmt, "ii", $artist_id, $_SESSION['user_id']);
    mysqli_stmt_execute($stmt);
    $check_result = mysqli_stmt_get_result($stmt);
    $commission_data = mysqli_fetch_assoc($check_result);
    
    if ($commission_data) {
        $can_rate = true;
        $user_rating = $commission_data['satisfaction_rating'];
    }
}

// Get artist's artworks
$artworks_sql = "SELECT a.*, 
                        (SELECT COUNT(*) FROM artwork_likes WHERE artwork_id = a.artwork_id) as like_count
                 FROM artworks a 
                 WHERE a.artist_id = ? 
                 ORDER BY a.created_at DESC";
$stmt = mysqli_prepare($conn, $artworks_sql);
mysqli_stmt_bind_param($stmt, "i", $artist_id);
mysqli_stmt_execute($stmt);
$artworks_result = mysqli_stmt_get_result($stmt);
$total_artworks = mysqli_num_rows($artworks_result);

// Get total views and likes
$stats_sql = "SELECT 
                SUM(views) as total_views,
                SUM(likes) as total_likes,
                COUNT(*) as artwork_count
              FROM artworks 
              WHERE artist_id = ?";
$stmt = mysqli_prepare($conn, $stats_sql);
mysqli_stmt_bind_param($stmt, "i", $artist_id);
mysqli_stmt_execute($stmt);
$stats_result = mysqli_stmt_get_result($stmt);
$stats = mysqli_fetch_assoc($stats_result);

$page_title = htmlspecialchars($artist['full_name'] ?? $artist['username']) . " - Artist Profile";
?>

<div class="artist-profile-container">
    <!-- Breadcrumb -->
    <nav class="breadcrumb">
        <a href="index.php">Home</a> &gt;
        <a href="artists.php">Artists</a> &gt;
        <span><?php echo htmlspecialchars($artist['full_name'] ?? $artist['username']); ?></span>
    </nav>

    <!-- Artist Header -->
    <div class="artist-cover">
        <div class="artist-cover-overlay"></div>
        <div class="artist-header-content">
            <div class="artist-avatar-large">
                <img src="uploads/profiles/<?php echo htmlspecialchars($artist['profile_image'] ?? 'default.jpg'); ?>" 
                     alt="<?php echo htmlspecialchars($artist['full_name'] ?? $artist['username']); ?>">
            </div>
            <div class="artist-header-info">
                <h1><?php echo htmlspecialchars($artist['full_name'] ?? $artist['username']); ?></h1>
                <p class="artist-username">@<?php echo htmlspecialchars($artist['username']); ?></p>
                
                <!-- ARTIST RATING (Only from completed commissions) -->
                <div class="artist-rating">
                    <div class="rating-stars">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="fas fa-star <?php echo $i <= $avg_rating ? 'active' : ''; ?>"></i>
                        <?php endfor; ?>
                        <span class="rating-text">(<?php echo $total_ratings; ?> <?php echo $total_ratings == 1 ? 'review' : 'reviews'; ?>)</span>
                    </div>
                    <?php if ($total_ratings > 0): ?>
                        <small class="rating-note">Based on completed commissions</small>
                    <?php endif; ?>
                </div>
                
                <div class="artist-badges">
                    <?php if (!empty($artist['specialization'])): ?>
                        <span class="badge specialization">
                            <i class="fas fa-paint-brush"></i> <?php echo htmlspecialchars($artist['specialization']); ?>
                        </span>
                    <?php endif; ?>
                    
                    <?php if ($artist['experience_years'] > 0): ?>
                        <span class="badge experience">
                            <i class="fas fa-calendar-alt"></i> <?php echo $artist['experience_years']; ?>+ years
                        </span>
                    <?php endif; ?>
                    
                    <span class="badge member-since">
                        <i class="fas fa-user-check"></i> Since <?php echo date('M Y', strtotime($artist['user_since'])); ?>
                    </span>
                </div>
            </div>
            
            <!-- Request Commission Button -->
            <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] == 'user'): ?>
                <a href="commission-request.php?artist_id=<?php echo $artist_id; ?>" class="btn-commission-header">
                    <i class="fas fa-handshake"></i> Request Commission
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Artist Stats Bar -->
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
            <span class="stat-value"><?php echo number_format($stats['total_likes'] ?? 0); ?></span>
            <span class="stat-label">Likes</span>
        </div>
        <div class="stat-item">
            <span class="stat-value"><?php echo $total_ratings; ?></span>
            <span class="stat-label">Ratings</span>
        </div>
    </div>

    <!-- Rate This Artist Section (Only for users who completed a commission) -->
    <?php if ($can_rate && !$user_rating): ?>
        <div class="rate-artist-section">
            <h3><i class="fas fa-star"></i> Rate Your Experience</h3>
            <p>You commissioned <?php echo htmlspecialchars($artist['full_name'] ?? $artist['username']); ?>. How was your experience?</p>
            <div class="rating-input">
                <span class="rating-label">Your rating:</span>
                <div class="rating-stars-input">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <i class="fas fa-star rate-star" data-rating="<?php echo $i; ?>"></i>
                    <?php endfor; ?>
                </div>
                <form id="rating-form" method="POST" action="ajax/rate-artist.php" style="display: none;">
                    <input type="hidden" name="artist_id" value="<?php echo $artist_id; ?>">
                    <input type="hidden" name="rating" id="selected-rating" value="">
                    <input type="hidden" name="commission_id" value="<?php echo $commission_data['commission_id'] ?? ''; ?>">
                </form>
            </div>
        </div>
    <?php elseif ($user_rating): ?>
        <div class="rate-artist-section rated">
            <h3><i class="fas fa-star"></i> Your Rating</h3>
            <div class="rating-stars">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <i class="fas fa-star <?php echo $i <= $user_rating ? 'active' : ''; ?>"></i>
                <?php endfor; ?>
                <span class="rating-text">Thank you for your feedback!</span>
            </div>
        </div>
    <?php endif; ?>

    <!-- Main Content Grid -->
    <div class="profile-grid">
        <!-- Left Column - About Section -->
        <div class="profile-left">
            <div class="about-card">
                <h2><i class="fas fa-user-circle"></i> About the Artist</h2>
                
                <?php if (!empty($artist['bio'])): ?>
                    <div class="artist-bio">
                        <?php echo nl2br(htmlspecialchars($artist['bio'])); ?>
                    </div>
                <?php else: ?>
                    <p class="no-bio">The artist hasn't added a biography yet.</p>
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

            <!-- Specialization Card -->
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
                <h2><i class="fas fa-palette"></i> Artworks</h2>
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
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                </div>
                            </div>
                            <div class="artwork-info">
                                <h3><?php echo htmlspecialchars($artwork['title']); ?></h3>
                                <div class="artwork-stats">
                                    <span class="stat" title="Views">
                                        <i class="fas fa-eye"></i> <?php echo number_format($artwork['views']); ?>
                                    </span>
                                    <span class="stat" title="Likes">
                                        <i class="fas fa-heart"></i> <?php echo number_format($artwork['like_count'] ?? $artwork['likes']); ?>
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

<script>
// Star rating functionality
document.querySelectorAll('.rate-star').forEach(star => {
    star.addEventListener('click', function() {
        const rating = this.dataset.rating;
        document.getElementById('selected-rating').value = rating;
        
        // Update stars visually
        document.querySelectorAll('.rate-star').forEach((s, index) => {
            if (index < rating) {
                s.classList.add('active');
            } else {
                s.classList.remove('active');
            }
        });
        
        // Submit the form
        document.getElementById('rating-form').submit();
    });
});
</script>

<style>
/* Rating Styles */
.artist-rating {
    margin: 0.5rem 0 1rem 0;
}

.rating-stars {
    display: flex;
    align-items: center;
    gap: 5px;
}

.rating-stars i {
    color: #ddd;
    font-size: 1.2rem;
}

.rating-stars i.active {
    color: #ffc107;
}

.rating-text {
    color: rgba(255,255,255,0.9);
    font-size: 0.9rem;
    margin-left: 10px;
}

.rating-note {
    display: block;
    font-size: 0.75rem;
    opacity: 0.7;
    margin-top: 4px;
}

.rate-artist-section {
    background: white;
    border-radius: 10px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    box-shadow: 0 3px 10px rgba(0,0,0,0.08);
}

.rate-artist-section.rated {
    background: #e8f5e9;
    border-left: 4px solid #4caf50;
}

.rate-artist-section h3 {
    color: var(--primary-color);
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 10px;
}

.rate-artist-section p {
    color: #666;
    margin-bottom: 1rem;
}

.rating-input {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
}

.rating-label {
    font-weight: 600;
    color: var(--primary-color);
}

.rating-stars-input {
    display: flex;
    gap: 10px;
}

.rate-star {
    font-size: 1.8rem;
    color: #ddd;
    cursor: pointer;
    transition: all 0.2s;
}

.rate-star:hover,
.rate-star.active {
    color: #ffc107;
    transform: scale(1.1);
}



.rating-stars {
    display: flex;
    align-items: center;
    gap: 5px;
}

.rating-stars i {
    color: #ddd;
    font-size: 1.2rem;
}

.rating-stars i.active {
    color: #ffc107;
}

.rating-text {
    color: rgba(255,255,255,0.9);
    font-size: 0.9rem;
    margin-left: 10px;
}

.rate-artist-section {
    background: white;
    border-radius: 10px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    box-shadow: 0 3px 10px rgba(0,0,0,0.08);
}

.rate-artist-section h3 {
    color: var(--primary-color);
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 10px;
}

.rating-input {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    flex-wrap: wrap;
}

.rating-label {
    font-weight: 600;
    color: var(--primary-color);
}

.rating-stars-input {
    display: flex;
    gap: 10px;
}

.rate-star {
    font-size: 1.8rem;
    color: #ddd;
    cursor: pointer;
    transition: all 0.2s;
}

.rate-star:hover,
.rate-star.active {
    color: #ffc107;
    transform: scale(1.1);
}

.your-rating {
    color: #666;
    font-style: italic;
}

.artwork-rating-small {
    display: flex;
    align-items: center;
    gap: 5px;
    margin: 0.5rem 0;
}

.artwork-rating-small i {
    color: #ddd;
    font-size: 0.9rem;
}

.artwork-rating-small i.active {
    color: #ffc107;
}

.artwork-rating-small span {
    color: #666;
    font-size: 0.8rem;
    margin-left: 5px;
}

.artist-profile-container {
    padding: 2rem 0;
    max-width: 1200px;
    margin: 0 auto;
}

.breadcrumb {
    margin-bottom: 2rem;
    color: #666;
    font-size: 0.9rem;
}

.breadcrumb a {
    color: var(--secondary-color);
    text-decoration: none;
}

.breadcrumb a:hover {
    text-decoration: underline;
}

/* Artist Cover */
.artist-cover {
    background: linear-gradient(135deg, var(--primary-color), #4a6491);
    border-radius: 15px 15px 0 0;
    position: relative;
    padding: 3rem 2rem;
    margin-bottom: 2rem;
    overflow: hidden;
}

.artist-cover-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" opacity="0.1"><path d="M50 15 L61 40 L88 44 L67 62 L72 90 L50 76 L28 90 L33 62 L12 44 L39 40 Z" fill="white"/></svg>');
    background-size: 100px;
    opacity: 0.1;
}

.artist-header-content {
    position: relative;
    z-index: 2;
    display: flex;
    align-items: center;
    gap: 2rem;
    flex-wrap: wrap;
    color: white;
}

.artist-avatar-large {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    border: 4px solid var(--accent-color);
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
}

.artist-avatar-large img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.artist-header-info {
    flex: 1;
}

.artist-header-info h1 {
    font-size: 2.5rem;
    margin-bottom: 0.5rem;
    color: white;
}

.artist-username {
    font-size: 1.1rem;
    opacity: 0.9;
    margin-bottom: 1rem;
    color: rgba(255,255,255,0.9);
}

.artist-badges {
    display: flex;
    flex-wrap: wrap;
    gap: 0.8rem;
}

.badge {
    background: rgba(255,255,255,0.2);
    padding: 0.4rem 1rem;
    border-radius: 20px;
    font-size: 0.9rem;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    backdrop-filter: blur(5px);
}

.badge i {
    color: var(--accent-color);
}

.btn-commission-header {
    background: var(--accent-color);
    color: var(--dark-color);
    padding: 1rem 2rem;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    transition: all 0.3s;
    box-shadow: 0 4px 10px rgba(0,0,0,0.2);
}

.btn-commission-header:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 15px rgba(0,0,0,0.3);
    background: #e6b800;
}

/* Stats Bar */
.artist-stats-bar {
    background: white;
    border-radius: 10px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    display: flex;
    justify-content: space-around;
    box-shadow: 0 3px 10px rgba(0,0,0,0.08);
}

.stat-item {
    text-align: center;
}

.stat-value {
    display: block;
    font-size: 2rem;
    font-weight: bold;
    color: var(--primary-color);
    line-height: 1.2;
}

.stat-label {
    color: #666;
    font-size: 0.9rem;
}

/* Profile Grid */
.profile-grid {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 2rem;
}

/* Left Column */
.profile-left {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.about-card, .specialization-card {
    background: white;
    border-radius: 10px;
    padding: 1.5rem;
    box-shadow: 0 3px 10px rgba(0,0,0,0.08);
}

.about-card h2, .specialization-card h3 {
    color: var(--primary-color);
    margin-bottom: 1.2rem;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 1.2rem;
}

.artist-bio {
    color: #444;
    line-height: 1.6;
    margin-bottom: 1.5rem;
}

.no-bio {
    color: #999;
    font-style: italic;
    margin-bottom: 1.5rem;
}

.artist-details {
    border-top: 1px solid #eee;
    padding-top: 1.5rem;
}

.detail-row {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 1rem;
    color: #666;
}

.detail-row i {
    width: 20px;
    color: var(--secondary-color);
}

.detail-row a {
    color: var(--info-color);
    text-decoration: none;
}

.detail-row a:hover {
    text-decoration: underline;
}

.specialization-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.spec-tag {
    background: #e8f4fc;
    color: var(--info-color);
    padding: 0.4rem 1rem;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: 500;
}

/* Right Column - Artworks */
.profile-right {
    background: white;
    border-radius: 10px;
    padding: 1.5rem;
    box-shadow: 0 3px 10px rgba(0,0,0,0.08);
}

.artworks-header {
    margin-bottom: 2rem;
}

.artworks-header h2 {
    color: var(--primary-color);
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 10px;
}

.artwork-count {
    color: #666;
    font-size: 0.95rem;
}

.artist-artworks-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 1.5rem;
}

.artwork-card {
    background: white;
    border: 1px solid #eee;
    border-radius: 10px;
    overflow: hidden;
    transition: all 0.3s;
}

.artwork-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.1);
}

.artwork-image {
    position: relative;
    height: 180px;
    overflow: hidden;
}

.artwork-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s;
}

.artwork-card:hover .artwork-image img {
    transform: scale(1.05);
}

.artwork-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s;
}

.artwork-card:hover .artwork-overlay {
    opacity: 1;
}
.artist-rating {
    margin: 0.5rem 0 1rem 0;
}
.view-btn {
    background: var(--accent-color);
    color: var(--dark-color);
    padding: 0.8rem 1.5rem;
    border-radius: 5px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s;
}

.view-btn:hover {
    background: #e6b800;
    transform: scale(1.05);
}

.artwork-category-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    background: rgba(44, 62, 80, 0.9);
    color: white;
    padding: 0.3rem 0.8rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
}

.artwork-info {
    padding: 1rem;
}

.artwork-info h3 {
    color: var(--primary-color);
    margin-bottom: 0.5rem;
    font-size: 1rem;
    line-height: 1.3;
}

.artwork-meta {
    margin-bottom: 0.5rem;
}

.artwork-date {
    color: #888;
    font-size: 0.8rem;
    display: flex;
    align-items: center;
    gap: 5px;
}

.artwork-stats {
    display: flex;
    gap: 1rem;
    margin-bottom: 0.5rem;
    font-size: 0.8rem;
    color: #888;
}

.artwork-stats i {
    color: var(--secondary-color);
    margin-right: 3px;
}

.artwork-materials {
    color: #666;
    font-size: 0.8rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.artwork-materials i {
    color: var(--info-color);
    margin-right: 5px;
}

/* No Artworks */
.no-artworks {
    text-align: center;
    padding: 3rem;
    color: #666;
}

.no-artworks i {
    font-size: 3rem;
    color: #ddd;
    margin-bottom: 1rem;
}

.no-artworks h3 {
    color: var(--primary-color);
    margin-bottom: 0.5rem;
}

.no-artworks p {
    margin-bottom: 1.5rem;
}

/* Responsive */
@media (max-width: 992px) {
    .profile-grid {
        grid-template-columns: 1fr;
    }
    
    .artist-header-content {
        flex-direction: column;
        text-align: center;
    }
    
    .artist-badges {
        justify-content: center;
    }
}

@media (max-width: 768px) {
    .artist-cover {
        padding: 2rem 1rem;
    }
    
    .artist-header-info h1 {
        font-size: 2rem;
    }
    
    .artist-stats-bar {
        flex-wrap: wrap;
        gap: 1rem;
    }
    
    .artist-artworks-grid {
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    }
}

@media (max-width: 480px) {
    .artist-artworks-grid {
        grid-template-columns: 1fr;
    }
    
    .btn-commission-header {
        width: 100%;
        justify-content: center;
    }
}


/* Keep all the existing CSS from your file */
</style>

<?php require_once 'includes/footer.php'; ?>
