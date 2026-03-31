<?php
// index.php - Homepage
require_once 'includes/config.php';
$page_title = "Buddhist Art Heritage - Home";

// Get real stats from database
$artists_query = "SELECT COUNT(DISTINCT u.user_id) as total 
                  FROM users u 
                  JOIN artists a ON u.user_id = a.user_id 
                  WHERE u.role = 'artist' AND a.status = 'approved'";
$artists_result = mysqli_query($conn, $artists_query);
$artists_count = mysqli_fetch_assoc($artists_result)['total'] ?? 0;

$artworks_query = "SELECT COUNT(*) as total FROM artworks";
$artworks_result = mysqli_query($conn, $artworks_query);
$artworks_count = mysqli_fetch_assoc($artworks_result)['total'] ?? 0;

$articles_query = "SELECT COUNT(*) as total FROM knowledge_articles";
$articles_result = mysqli_query($conn, $articles_query);
$articles_count = mysqli_fetch_assoc($articles_result)['total'] ?? 0;

$commissions_query = "SELECT COUNT(*) as total FROM commissions WHERE status IN ('accepted', 'in_progress', 'completed')";
$commissions_result = mysqli_query($conn, $commissions_query);
$commissions_count = mysqli_fetch_assoc($commissions_result)['total'] ?? 0;

require_once 'includes/navbar.php';
?>

<!-- Hero Section -->
<section class="hero">
    <div class="container hero-container">
        <div class="hero-content">
            <h1>Preserving Buddhist Art Heritage</h1>
<p class="subtitle">
Empowering local Buddhist artists to share their sacred craftsmanship with the world. 
Discover authentic artworks, learn their meaning, and connect directly with artists for custom creations.
</p>
            <div class="hero-buttons">
                <a href="gallery.php" class="btn-primary">
                    <i class="fas fa-images"></i> Explore Artworks
                </a>
                <a href="register.php?role=artist" class="btn-secondary">
                    <i class="fas fa-paint-brush"></i> Join as Artist
                </a>
            </div>
        </div>
        <div class="hero-image">
            <div class="mandala-animation">
                <div class="dot dot-1"></div>
                <div class="dot dot-2"></div>
                <div class="dot dot-3"></div>
                <div class="dot dot-4"></div>
                <div class="mandala-center"></div>
            </div>
        </div>
    </div>
</section>

<!-- Stats Counter -->
<section class="stats">
    <div class="container">
        <div class="stats-grid">
            <div class="stat-card">
                <i class="fas fa-users"></i>
                <h3 class="counter" data-target="<?php echo $artists_count; ?>">0</h3>
                <p>Artists</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-palette"></i>
                <h3 class="counter" data-target="<?php echo $artworks_count; ?>">0</h3>
                <p>Artworks</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-book-open"></i>
                <h3 class="counter" data-target="<?php echo $articles_count; ?>">0</h3>
                <p>Articles</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-handshake"></i>
                <h3 class="counter" data-target="<?php echo $commissions_count; ?>">0</h3>
                <p>Commissions</p>
            </div>
        </div>
    </div>
</section>

<!-- Features -->
<section class="features">
    <div class="container">
        <h2 class="section-title">Platform Features</h2>
        <p class="section-subtitle">Everything you need to explore, learn, and contribute to Buddhist art preservation</p>
        
        <div class="feature-grid">
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-user-plus"></i>
                </div>
                <h3>Artist Profiles</h3>
                <p>Create detailed portfolios showcasing your Buddhist art expertise, experience, and specialization.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-upload"></i>
                </div>
                <h3>Artwork Gallery</h3>
                <p>Upload and display your traditional artworks with cultural context, materials, and symbolism explained.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <h3>Knowledge Base</h3>
                <p>Learn about different Buddhist art styles, symbolism, history, and traditional techniques.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-handshake"></i>
                </div>
                <h3>Commission System</h3>
                <p>Request custom artworks directly from traditional artists with transparent workflow.</p>
            </div>
        </div>
    </div>
</section>

<!-- Recent Artworks Preview -->
<section class="recent-artworks">
    <div class="container">
        <div class="section-header">
<h2 class="section-title text-center">Recent Artworks</h2>
            <a href="gallery.php" class="view-all">View All <i class="fas fa-arrow-right"></i></a>
        </div>
        
        <div class="artworks-grid">
            <?php
            // Fetch 6 recent artworks (3 per row)
            $query = "SELECT a.*, u.username, u.full_name 
                      FROM artworks a 
                      JOIN artists ar ON a.artist_id = ar.artist_id
                      JOIN users u ON ar.user_id = u.user_id
                      WHERE ar.status = 'approved'
                      ORDER BY a.created_at DESC LIMIT 6";
            
            $result = mysqli_query($conn, $query);
            
            if (mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
                    // Check if user liked this artwork
                    $user_liked = false;
                    if (isset($_SESSION['user_id'])) {
                        $check_sql = "SELECT * FROM artwork_likes WHERE user_id = ? AND artwork_id = ?";
                        $check_stmt = mysqli_prepare($conn, $check_sql);
                        mysqli_stmt_bind_param($check_stmt, "ii", $_SESSION['user_id'], $row['artwork_id']);
                        mysqli_stmt_execute($check_stmt);
                        $check_result = mysqli_stmt_get_result($check_stmt);
                        $user_liked = mysqli_num_rows($check_result) > 0;
                    }
                    
                    // Get like count
                    $like_sql = "SELECT COUNT(*) as like_count FROM artwork_likes WHERE artwork_id = " . $row['artwork_id'];
                    $like_result = mysqli_query($conn, $like_sql);
                    $like_data = mysqli_fetch_assoc($like_result);
                    $like_count = $like_data['like_count'] ?? 0;
                    
                    // Artist name (full name or username)
                    $artist_display = !empty($row['full_name']) ? $row['full_name'] : $row['username'];
                    ?>
                    <div class="artwork-card">
                        <div class="artwork-image">
                            <img src="uploads/artworks/<?php echo htmlspecialchars($row['image_path']); ?>" 
                                 alt="<?php echo htmlspecialchars($row['title']); ?>">
                            <div class="artwork-overlay">
                                <a href="artwork-detail.php?id=<?php echo $row['artwork_id']; ?>" class="view-btn">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            </div>
                        </div>
                        <div class="artwork-info">
                            <h3><?php echo htmlspecialchars($row['title']); ?></h3>
                            <p class="artist"><?php echo htmlspecialchars($artist_display); ?></p>
                            <div class="artwork-stats">
                                <span class="views">
                                    <i class="fas fa-eye"></i> <?php echo $row['views']; ?>
                                </span>
                                <button class="like-btn <?php echo $user_liked ? 'liked' : ''; ?>" 
                                        onclick="likeArtwork(<?php echo $row['artwork_id']; ?>, this)">
                                    <i class="<?php echo $user_liked ? 'fas' : 'far'; ?> fa-heart"></i>
                                    <span class="like-count"><?php echo $like_count; ?></span>
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php
                }
            } else {
                echo '<div class="no-artworks" style="grid-column: 1/-1; text-align: center; padding: 3rem;">
                        <i class="fas fa-palette" style="font-size: 3rem; color: #ddd; margin-bottom: 1rem;"></i>
                        <h3>No Artworks Yet</h3>
                        <p>Be the first to upload a Buddhist artwork!</p>
                        <a href="upload-artwork.php" class="btn-primary" style="display: inline-block; margin-top: 1rem;">Upload Artwork</a>
                      </div>';
            }
            ?>
        </div>
    </div>
</section>

<style>
    /* FIX HEADER ALIGNMENT */
.section-header {
    position: relative;
    text-align: center;
    margin-bottom: 1.5rem;
}

.section-header .view-all {
    position: absolute;
    right: 0;
    top: 50%;
    transform: translateY(-50%);
}
    .text-center {
    text-align: center !important;
    width: 100%;
}
    .section-title {
    font-weight: 700;
    letter-spacing: 0.5px;
    text-align: center;
}
/* ===== ARTWORK GRID (STRICT 3 PER ROW) ===== */
.artworks-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 2rem;
    margin-top: 2rem;
}

/* ===== CARD ===== */
.artwork-card {
    background: #ffffff;
    border-radius: 18px;
    overflow: hidden;
    box-shadow: 0 6px 18px rgba(0,0,0,0.08);
    transition: all 0.35s ease;
    position: relative;
}

/* soft glowing top line */
.artwork-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, #6a11cb, #2575fc);
    transform: scaleX(0);
    transition: 0.3s ease;
}

.artwork-card:hover::before {
    transform: scaleX(1);
}

/* hover effect */
.artwork-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 18px 35px rgba(0,0,0,0.15);
}

/* ===== IMAGE ===== */
.artwork-image {
    position: relative;
    height: 210px;
    overflow: hidden;
}

.artwork-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.4s ease;
}

.artwork-card:hover .artwork-image img {
    transform: scale(1.08);
}

/* ===== OVERLAY ===== */
.artwork-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, rgba(106,17,203,0.85), rgba(37,117,252,0.85));
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: 0.3s ease;
}

.artwork-card:hover .artwork-overlay {
    opacity: 1;
}

.view-btn {
    background: #f1c40f;
    color: white;
    padding: 8px 18px;
    border-radius: 30px;
    font-size: 0.85rem;
    font-weight: 600;
    text-decoration: none;
    transition: 0.25s;
}

.view-btn:hover {
    background: #f1c40f;
    color: white;
}

/* ===== INFO ===== */
.artwork-info {
    padding: 1rem 1.1rem;
}

.artwork-info h3 {
    font-size: 1.05rem;
    font-weight: 700;
    color: #2c3e50;
    margin-bottom: 4px;
}

.artist {
    font-size: 0.8rem;
    color: #888;
    margin-bottom: 0.8rem;
}

/* ===== STATS ===== */
.artwork-stats {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-top: 1px solid #eee;
    padding-top: 8px;
}

.views {
    font-size: 0.75rem;
    color: #666;
}

/* ===== LIKE BUTTON ===== */
.like-btn {
    border: none;
    background: #f4f6f8;
    padding: 5px 10px;
    border-radius: 20px;
    cursor: pointer;
    font-size: 0.75rem;
    color: #888;
    transition: 0.25s;
}

.like-btn:hover {
    background: #ffeaea;
    color: #e74c3c;
}

.like-btn.liked {
    color: #e74c3c;
    background: #ffeaea;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 992px) {
    .artworks-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 600px) {
    .artworks-grid {
        grid-template-columns: 1fr;
    }
}

</style>

<script>
function likeArtwork(artworkId, buttonElement) {
    <?php if (!isset($_SESSION['user_id'])): ?>
        if (confirm('Please login to like artworks. Go to login page?')) {
            window.location.href = 'login.php';
        }
        return;
    <?php endif; ?>
    
    const heartIcon = buttonElement.querySelector('i');
    const likeSpan = buttonElement.querySelector('.like-count');
    
    buttonElement.disabled = true;
    
    fetch('ajax/like-artwork.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ artwork_id: artworkId })
    })
    .then(response => response.json())
    .then(data => {
        buttonElement.disabled = false;
        
        if (data.success) {
            if (data.liked) {
                heartIcon.className = 'fas fa-heart';
                buttonElement.classList.add('liked');
                likeSpan.textContent = data.new_count;
                buttonElement.style.transform = 'scale(1.1)';
                setTimeout(() => buttonElement.style.transform = 'scale(1)', 200);
            } else {
                heartIcon.className = 'far fa-heart';
                buttonElement.classList.remove('liked');
                likeSpan.textContent = data.new_count;
            }
        } else {
            alert(data.message);
            if (data.message === 'Please login to like') {
                window.location.href = 'login.php';
            }
        }
    })
    .catch(error => {
        buttonElement.disabled = false;
        alert('Network error. Please try again.');
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>