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



$commissions_query = "SELECT COUNT(*) as total FROM commissions WHERE status IN ('accepted', 'in_progress', 'completed')";
$commissions_result = mysqli_query($conn, $commissions_query);
$commissions_count = mysqli_fetch_assoc($commissions_result)['total'] ?? 0;

// Total users (art lovers)
$users_query = "SELECT COUNT(*) as total FROM users WHERE role = 'user'";
$users_result = mysqli_query($conn, $users_query);
$users_count = mysqli_fetch_assoc($users_result)['total'] ?? 0;

require_once 'includes/navbar.php';
?>

<style>
body {
    background: linear-gradient(135deg, #f9f7f1 0%, #f5f5f0 100%);
    font-family: 'Open Sans', sans-serif;
    color: #2c3e50;
}

/* ========== HERO ========== */
.hero {
    padding: 4rem 0;
    background: url('https://happylandtreks.com/_next/image?url=https%3A%2F%2Fmedia.app.happylandtreks.com%2Fuploads%2Ffullbanner%2Fthangka-mandala-art-shop-in-thamel-kathmandu.webp&w=1920&q=75&dpl=dpl_1P2zZgSGv6Cv4sWHnfgyFwyfFLQV') no-repeat center center/cover;
    color: white;
    position: relative;
    overflow: hidden;
}

.hero::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0, 0, 0, 0.55);
    pointer-events: none;
}

.hero-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 3rem;
    align-items: center;
    position: relative;
    z-index: 1;
}

.hero-content h1 {
    font-size: 2.8rem;
    font-weight: 700;
    margin-bottom: 1rem;
    background: linear-gradient(135deg, #f1c40f, #e74c3c);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.hero-content .subtitle {
    font-size: 1rem;
    margin-bottom: 2rem;
    color: #ecf0f1;
    line-height: 1.7;
}

.hero-buttons {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.btn-primary {
    background: transparent;
    color: #f1c40f;
    padding: 0.8rem 1.8rem;
    border-radius: 40px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    border: 2px solid #f1c40f;
}

.btn-primary:hover {
    background: #f1c40f;
    color: #2c3e50;
    transform: translateY(-3px);
    box-shadow: 0 10px 20px rgba(241, 196, 15, 0.3);
}

.btn-secondary {
    background: transparent;
    color: #e74c3c;
    padding: 0.8rem 1.8rem;
    border-radius: 40px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    border: 2px solid #e74c3c;
}

.btn-secondary:hover {
    background: #e74c3c;
    color: white;
    transform: translateY(-3px);
    box-shadow: 0 10px 20px rgba(231, 76, 60, 0.3);
}

/* Mandala */
.mandala-animation {
    position: relative;
    width: 300px;
    height: 300px;
    margin: 0 auto;
}

.mandala-center {
    position: absolute;
    top: 50%; left: 50%;
    width: 60px; height: 60px;
    background: #f1c40f;
    border-radius: 50%;
    transform: translate(-50%, -50%);
    animation: pulse 2s infinite;
    box-shadow: 0 0 20px rgba(241, 196, 15, 0.5);
}

.dot {
    position: absolute;
    width: 16px; height: 16px;
    border-radius: 50%;
    top: 50%; left: 50%;
}

.dot-1 { background: #e74c3c; animation: rotate1 8s linear infinite; }
.dot-2 { background: #e74c3c; animation: rotate2 10s linear infinite reverse; }
.dot-3 { background: #e74c3c; animation: rotate3 6s linear infinite; }
.dot-4 { background: #e74c3c; animation: rotate4 12s linear infinite reverse; }

@keyframes rotate1 {
    from { transform: translate(-50%,-50%) rotate(0deg) translateX(120px) rotate(0deg); }
    to   { transform: translate(-50%,-50%) rotate(360deg) translateX(120px) rotate(-360deg); }
}
@keyframes rotate2 {
    from { transform: translate(-50%,-50%) rotate(0deg) translateX(90px) rotate(0deg); }
    to   { transform: translate(-50%,-50%) rotate(360deg) translateX(90px) rotate(-360deg); }
}
@keyframes rotate3 {
    from { transform: translate(-50%,-50%) rotate(0deg) translateX(140px) rotate(0deg); }
    to   { transform: translate(-50%,-50%) rotate(360deg) translateX(140px) rotate(-360deg); }
}
@keyframes rotate4 {
    from { transform: translate(-50%,-50%) rotate(0deg) translateX(60px) rotate(0deg); }
    to   { transform: translate(-50%,-50%) rotate(360deg) translateX(60px) rotate(-360deg); }
}

@keyframes pulse {
    0%, 100% { transform: translate(-50%,-50%) scale(1); opacity: 1; }
    50%       { transform: translate(-50%,-50%) scale(1.15); opacity: 0.9; }
}

/* ========== RECENT ARTWORKS ========== */
.recent-artworks {
    padding: 4rem 0;
    background: #ffffff;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.section-title {
    text-align: center;
    font-size: 2rem;
    color: #2c3e50;
    margin-bottom: 0.5rem;
    font-weight: 700;
}

.section-header .section-title { margin-bottom: 0; }

.view-all {
    color: #e74c3c;
    text-decoration: none;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    transition: all 0.3s;
}

.view-all:hover { gap: 10px; color: #c0392b; }

.artworks-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 2rem;
}

.artwork-card {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    border: 1px solid #e9ecef;
    position: relative;
}

.artwork-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 4px;
    background: linear-gradient(90deg, #f1c40f, #e74c3c);
    transform: scaleX(0);
    transition: 0.3s ease;
}

.artwork-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 15px 35px rgba(0,0,0,0.12);
    border-color: #f1c40f;
}

.artwork-card:hover::before { transform: scaleX(1); }

.artwork-image {
    position: relative;
    height: 220px;
    overflow: hidden;
    background: #f8f9fa;
}

.artwork-image img {
    width: 100%; height: 100%;
    object-fit: cover;
    transition: transform 0.4s ease;
}

.artwork-card:hover .artwork-image img { transform: scale(1.05); }

.artwork-overlay {
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(44, 62, 80, 0.85);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.artwork-card:hover .artwork-overlay { opacity: 1; }

.view-btn {
    background: #f1c40f;
    color: #2c3e50;
    padding: 0.7rem 1.5rem;
    border-radius: 40px;
    text-decoration: none;
    font-size: 0.9rem;
    font-weight: 600;
    transition: all 0.2s;
}

.view-btn:hover { background: #e74c3c; color: white; transform: scale(1.05); }

.artwork-info { padding: 1rem; }

.artwork-info h3 {
    font-size: 1rem;
    font-weight: 700;
    color: #2c3e50;
    margin-bottom: 0.3rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.artist { font-size: 0.8rem; color: #6c757d; margin-bottom: 0.8rem; }

.social-stats {
    display: flex;
    align-items: center;
    gap: 0.8rem;
    padding-top: 0.5rem;
    border-top: 1px solid #e9ecef;
}

.stat-like { display: flex; align-items: center; gap: 4px; font-size: 0.75rem; color: #e74c3c; font-weight: 500; }
.stat-like i { font-size: 0.85rem; color: #e74c3c; }
.stat-comment { display: flex; align-items: center; gap: 4px; font-size: 0.75rem; color: #27ae60; font-weight: 500; }
.stat-comment i { font-size: 0.85rem; color: #27ae60; }
.stat-view { display: flex; align-items: center; gap: 4px; font-size: 0.75rem; color: #3498db; font-weight: 500; }
.stat-view i { font-size: 0.85rem; color: #3498db; }

.like-btn {
    background: none;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 0.75rem;
    color: #95a5a6;
    transition: all 0.2s;
    padding: 4px 8px;
    border-radius: 20px;
}

.like-btn:hover { background: #fef5f4; }
.like-btn.liked { color: #e74c3c; }
.like-btn.liked i { color: #e74c3c; }

.no-artworks {
    grid-column: 1/-1;
    text-align: center;
    padding: 3rem;
    background: #f8f9fa;
    border-radius: 20px;
    border: 1px solid #e9ecef;
}

.no-artworks i { font-size: 3rem; color: #e74c3c; margin-bottom: 1rem; display: block; }
.no-artworks h3 { color: #2c3e50; margin-bottom: 0.5rem; }
.no-artworks p { color: #6c757d; }

/* ========== STATS ========== */
.stats {
    padding: 4rem 0;
    background: #f8f9fa;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 1.5rem;
    text-align: center;
}

.stat-card {
    background: white;
    padding: 1.5rem 1rem;
    border-radius: 20px;
    transition: all 0.3s;
    border: 1px solid #e9ecef;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    border-color: #f1c40f;
}

.stat-card i { font-size: 2rem; color: #e74c3c; margin-bottom: 0.8rem; display: block; }
.stat-card h3 { font-size: 1.8rem; font-weight: 700; color: #2c3e50; margin-bottom: 0.3rem; }
.stat-card p { color: #6c757d; font-size: 0.85rem; }

/* ========== FEATURES ========== */
.features {
    padding: 4rem 0;
    background: #ffffff;
}

.section-subtitle {
    text-align: center;
    color: #6c757d;
    margin-bottom: 3rem;
}

.feature-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 2rem;
}

.feature-card {
    background: #f8f9fa;
    padding: 2rem;
    border-radius: 20px;
    text-align: center;
    transition: all 0.3s;
    border: 1px solid #e9ecef;
}

.feature-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    border-color: #f1c40f;
    background: white;
}

.feature-icon {
    width: 70px; height: 70px;
    background: #fef5f4;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
}

.feature-icon i { font-size: 2rem; color: #e74c3c; }
.feature-card h3 { color: #2c3e50; margin-bottom: 0.5rem; font-size: 1rem; }
.feature-card p { color: #6c757d; font-size: 0.85rem; line-height: 1.6; }

/* ========== RESPONSIVE ========== */
@media (max-width: 992px) {
    .hero-container { grid-template-columns: 1fr; text-align: center; }
    .hero-buttons { justify-content: center; }
    .stats-grid { grid-template-columns: repeat(3, 1fr); }
    .feature-grid { grid-template-columns: repeat(2, 1fr); }
    .artworks-grid { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 768px) {
    .hero-content h1 { font-size: 2rem; }
    .stats-grid { grid-template-columns: repeat(2, 1fr); }
    .feature-grid, .artworks-grid { grid-template-columns: 1fr; }
    .section-header { flex-direction: column; text-align: center; }
}

@media (max-width: 480px) {
    .hero-buttons { flex-direction: column; }
    .btn-primary, .btn-secondary { justify-content: center; }
    .stats-grid { grid-template-columns: repeat(2, 1fr); }
}
</style>

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
                    <i class="fas fa-paint-brush"></i> Become an Artist
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

<!-- Recent Artworks -->
<section class="recent-artworks">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Recent Artworks</h2>
            <a href="gallery.php" class="view-all">View All <i class="fas fa-arrow-right"></i></a>
        </div>
        
        <div class="artworks-grid">
            <?php
            $query = "SELECT a.*, u.username, u.full_name,
                (SELECT COUNT(*) FROM artwork_likes WHERE artwork_id = a.artwork_id) as like_count,
                (SELECT COUNT(*) FROM artwork_comments WHERE artwork_id = a.artwork_id) as comment_count
                FROM artworks a 
                JOIN artists ar ON a.artist_id = ar.artist_id
                JOIN users u ON ar.user_id = u.user_id
                WHERE ar.status = 'approved'
                ORDER BY a.created_at DESC LIMIT 6";
            
            $result = mysqli_query($conn, $query);
            
            if ($result && mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $total_likes = $row['like_count'];
                    $comment_count = $row['comment_count'];
                    
                    $user_liked = false;
                    if (isset($_SESSION['user_id'])) {
                        $check_sql = "SELECT * FROM artwork_likes WHERE user_id = " . intval($_SESSION['user_id']) . " AND artwork_id = " . intval($row['artwork_id']);
                        $check_result = mysqli_query($conn, $check_sql);
                        $user_liked = $check_result && mysqli_num_rows($check_result) > 0;
                    }
                    
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
                            <p class="artist"><i class="fas fa-paint-brush" style="color:#e74c3c; font-size:0.7rem;"></i> <?php echo htmlspecialchars($artist_display); ?></p>
                            <div class="social-stats">
                                <button class="like-btn <?php echo $user_liked ? 'liked' : ''; ?>" 
                                        onclick="likeArtwork(<?php echo $row['artwork_id']; ?>, this)">
                                    <i class="<?php echo $user_liked ? 'fas' : 'far'; ?> fa-heart"></i>
                                    <span class="like-count"><?php echo $total_likes; ?></span>
                                </button>
                                <span class="stat-comment">
                                    <i class="fas fa-comment"></i>
                                    <?php echo number_format($comment_count); ?>
                                </span>
                                <span class="stat-view">
                                    <i class="fas fa-eye"></i>
                                    <?php echo number_format($row['views'] ?? 0); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <?php
                }
            } else {
                echo '<div class="no-artworks">
                        <i class="fas fa-palette"></i>
                        <h3>No Artworks Yet</h3>
                        <p>Be the first to upload a Buddhist artwork!</p>
                      </div>';
            }
            ?>
        </div>
    </div>
</section>

<!-- Stats Counter -->
<section class="stats">
    <div class="container">
        <h2 class="section-title" style="margin-bottom: 2rem;">Platform at a Glance</h2>
        <div class="stats-grid">
            <div class="stat-card">
                <i class="fas fa-users"></i>
                <h3 class="counter" data-target="<?php echo $users_count; ?>">0</h3>
                <p>Art Lovers</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-paint-brush"></i>
                <h3 class="counter" data-target="<?php echo $artists_count; ?>">0</h3>
                <p>Artists</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-palette"></i>
                <h3 class="counter" data-target="<?php echo $artworks_count; ?>">0</h3>
                <p>Artworks</p>
            </div>
           
            <div class="stat-card">
                <i class="fas fa-handshake"></i>
                <h3 class="counter" data-target="<?php echo $commissions_count; ?>">0</h3>
                <p>Custom Requests</p>
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
                <div class="feature-icon"><i class="fas fa-user-plus"></i></div>
                <h3>Artist Profiles</h3>
                <p>Create detailed portfolios showcasing your Buddhist art expertise, experience, and specialization.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-upload"></i></div>
                <h3>Artwork Gallery</h3>
                <p>Upload and display your traditional artworks with cultural context, materials, and symbolism explained.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-graduation-cap"></i></div>
                <h3>Knowledge Base</h3>
                <p>Learn about different Buddhist art styles, symbolism, history, and traditional techniques.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-pencil-ruler"></i></div>
                <h3>Custom Artwork</h3>
                <p>Request personalized Buddhist artworks directly from traditional artists with transparent process and clear communication.</p>
            </div>
        </div>
    </div>
</section>

<script>
// Counter animation
function animateCounters() {
    const counters = document.querySelectorAll('.counter');
    counters.forEach(counter => {
        const target = parseInt(counter.getAttribute('data-target'));
        if (target === 0) { counter.innerText = 0; return; }
        let current = 0;
        const increment = target / 50;
        const updateCounter = () => {
            if (current < target) {
                current += increment;
                counter.innerText = Math.ceil(current);
                setTimeout(updateCounter, 30);
            } else {
                counter.innerText = target;
            }
        };
        updateCounter();
    });
}

const statsSection = document.querySelector('.stats');
const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            animateCounters();
            observer.disconnect();
        }
    });
});
observer.observe(statsSection);

// Like artwork
function likeArtwork(artworkId, buttonElement) {
    <?php if (!isset($_SESSION['user_id'])): ?>
        window.location.href = 'login.php';
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
            } else {
                heartIcon.className = 'far fa-heart';
                buttonElement.classList.remove('liked');
            }
            likeSpan.textContent = data.new_count;
        } else if (data.message === 'Please login to like') {
            window.location.href = 'login.php';
        }
    })
    .catch(error => { buttonElement.disabled = false; console.error('Error:', error); });
}
</script>

<?php require_once 'includes/footer.php'; ?>