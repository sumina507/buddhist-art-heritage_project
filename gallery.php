<?php
// gallery.php - With Weighted Popularity Algorithm (Real-time Score)
require_once 'includes/config.php';

$page_title = "Thangka Gallery - Buddhist Art Heritage";
require_once 'includes/navbar.php';

// Get filter parameters
$deity = isset($_GET['deity']) ? trim($_GET['deity']) : 'all';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

// Build WHERE clause safely
$where = "ar.status = 'approved'";
$deity_param = null;

if ($deity != 'all' && !empty($deity)) {
    $where .= " AND (a.title LIKE ? OR a.description LIKE ?)";
    $deity_param = '%' . $deity . '%';
}

// Get total count
if ($deity_param) {
    $count_sql = "SELECT COUNT(*) as total FROM artworks a 
                  JOIN artists ar ON a.artist_id = ar.artist_id 
                  WHERE $where";
    $count_stmt = mysqli_prepare($conn, $count_sql);
    mysqli_stmt_bind_param($count_stmt, "ss", $deity_param, $deity_param);
    mysqli_stmt_execute($count_stmt);
    $count_result = mysqli_stmt_get_result($count_stmt);
} else {
    $count_sql = "SELECT COUNT(*) as total FROM artworks a 
                  JOIN artists ar ON a.artist_id = ar.artist_id 
                  WHERE $where";
    $count_result = mysqli_query($conn, $count_sql);
}

$total_rows = mysqli_fetch_assoc($count_result)['total'] ?? 0;
$total_pages = ceil($total_rows / $limit);

$sql = "SELECT a.*, u.username, u.full_name,
        (SELECT COUNT(*) FROM artwork_likes WHERE artwork_id = a.artwork_id) as total_likes,
        (SELECT COUNT(*) FROM artwork_comments WHERE artwork_id = a.artwork_id AND sentiment = 'positive') as positive_comments,
        (SELECT COUNT(*) FROM artwork_comments WHERE artwork_id = a.artwork_id AND sentiment = 'negative') as negative_comments,
        ROUND(
            a.views * 0.3 + 
            (SELECT COUNT(*) FROM artwork_likes WHERE artwork_id = a.artwork_id) * 0.5 + 
            (SELECT COUNT(*) FROM artwork_comments WHERE artwork_id = a.artwork_id AND sentiment = 'positive') * 0.2 -
            (SELECT COUNT(*) FROM artwork_comments WHERE artwork_id = a.artwork_id AND sentiment = 'negative') * 0.1
        , 1) as popularity_score
        FROM artworks a
        JOIN artists ar ON a.artist_id = ar.artist_id
        JOIN users u ON ar.user_id = u.user_id
        WHERE $where
        ORDER BY popularity_score DESC
        LIMIT ? OFFSET ?";

if ($deity_param) {
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ssii", $deity_param, $deity_param, $limit, $offset);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $limit, $offset);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
}
?>

<style>
.gallery-container { padding: 2rem 1rem; }

.gallery-header { text-align: center; margin-bottom: 2rem; }
.gallery-header h1 { color: #2c3e50; font-size: 2.2rem; margin-bottom: 0.5rem; font-weight: 700; }
.gallery-header h1 i { color: #e74c3c; margin-right: 10px; }
.gallery-header p { color: #6c757d; font-size: 1rem; }

/* Algorithm Info Card */
.algo-info-card {
    background: linear-gradient(135deg, #ffffff, #fff8e7);
    padding: 1.2rem 1.8rem;
    border-radius: 16px;
    margin-bottom: 2rem;
    border-left: 4px solid #e74c3c;
    box-shadow: 0 4px 15px rgba(0,0,0,0.05);
}

.algo-info-card h3 { color: #2c3e50; margin-bottom: 0.5rem; font-size: 1.1rem; }
.algo-info-card h3 i { color: #e74c3c; margin-right: 8px; }
.algo-info-card p { color: #6c757d; font-size: 0.9rem; margin-bottom: 0.5rem; }

.algo-formula {
    background: #fef5f4;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    font-family: monospace;
    font-size: 0.85rem;
    color: #e74c3c;
    font-weight: 600;
}

/* Filter Bar */
.filter-bar {
    background: white;
    padding: 1rem 1.5rem;
    border-radius: 12px;
    margin-bottom: 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    border: 1px solid #e9ecef;
}

.filter-group { display: flex; align-items: center; gap: 0.8rem; flex-wrap: wrap; }
.filter-group label { font-weight: 600; color: #2c3e50; font-size: 0.9rem; }
.filter-group label i { color: #e74c3c; margin-right: 5px; }

.filter-select {
    padding: 0.6rem 1rem;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    font-size: 0.9rem;
    color: #2c3e50;
    background: white;
    min-width: 160px;
    cursor: pointer;
    transition: all 0.3s;
}

.filter-select:hover { border-color: #f1c40f; }
.filter-select:focus { outline: none; border-color: #e74c3c; }
.result-count { color: #6c757d; font-size: 0.85rem; }

/* Gallery Grid */
.gallery-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.8rem;
    margin-top: 1.5rem;
}

.artwork-card {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
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
    z-index: 1;
}

.artwork-card:hover::before { transform: scaleX(1); }

.artwork-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 15px 35px rgba(0,0,0,0.12);
    border-color: #f1c40f;
}

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
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s;
}

.view-btn:hover { background: #e74c3c; color: white; }

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
.artist i { color: #e74c3c; margin-right: 5px; }

.algo-score {
    background: #fef5f4;
    color: #e74c3c;
    padding: 0.2rem 0.6rem;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 600;
    display: inline-block;
    margin-bottom: 0.5rem;
}

.artwork-stats {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 0.5rem;
    border-top: 1px solid #e9ecef;
}

.stats-left { display: flex; gap: 12px; }

.stat-item {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 0.75rem;
    color: #6c757d;
}

.stat-item i.fa-eye { color: #3498db; }
.stat-item i.fa-comment { color: #27ae60; }

.like-btn {
    background: none;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.75rem;
    color: #6c757d;
    transition: all 0.2s;
    padding: 4px 8px;
    border-radius: 30px;
}

.like-btn:hover { background: #fef5f4; }
.like-btn i { font-size: 1rem; transition: all 0.2s; }
.like-btn:hover i { transform: scale(1.1); }
.like-btn.liked { color: #e74c3c; }
.like-btn.liked i { color: #e74c3c; }

.no-artworks {
    grid-column: 1/-1;
    text-align: center;
    padding: 3rem;
    background: white;
    border-radius: 16px;
}

.no-artworks i { font-size: 3rem; color: #e74c3c; display: block; margin-bottom: 1rem; }

/* Pagination */
.pagination {
    display: flex;
    justify-content: center;
    gap: 0.5rem;
    margin-top: 2rem;
    flex-wrap: wrap;
}

.page-btn {
    background: white;
    border: 1px solid #e9ecef;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    text-decoration: none;
    color: #2c3e50;
    transition: all 0.3s;
}

.page-btn:hover { background: #fef5f4; border-color: #e74c3c; color: #e74c3c; }
.page-btn.active { background: linear-gradient(135deg, #e74c3c, #c0392b); color: white; border-color: #e74c3c; }

@media (max-width: 992px) { .gallery-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 768px) {
    .filter-bar { flex-direction: column; align-items: stretch; }
    .filter-group { width: 100%; }
    .filter-select { width: 100%; }
    .gallery-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 576px) { .gallery-grid { grid-template-columns: 1fr; } }
</style>

<div class="container gallery-container">
    <div class="gallery-header">
        <h1><i class="fas fa-om"></i> Sacred Thangka Gallery</h1>
        <p>Explore traditional Buddhist Thangka paintings from master artists</p>
    </div>

    <!-- Algorithm Explanation Card -->
    <div class="algo-info-card">
        <h3><i class="fas fa-chart-line"></i> Weighted Popularity Algorithm</h3>
        <p>Artworks are ranked using a weighted formula that considers Views, Likes, and Comments:</p>
        <div class="algo-formula">
            Popularity Score = (Views × 0.3) + (Likes × 0.5) + (Comments × 0.2)
        </div>
        <p style="margin-top: 0.5rem; font-size: 0.8rem;">
            <i class="fas fa-info-circle"></i> Likes have the highest weight (50%) as they show active user appreciation,
            followed by Views (30%) and Comments (20%).
        </p>
    </div>

    <!-- Filter Bar -->
    <div class="filter-bar">
        <div class="filter-group">
            <label><i class="fas fa-om"></i> Filter by Deity:</label>
            <select id="deityFilter" class="filter-select" onchange="updateFilters()">
                <option value="all" <?php echo $deity == 'all' ? 'selected' : ''; ?>>All Artworks</option>
                <option value="Shakyamuni Buddha" <?php echo $deity == 'Shakyamuni Buddha' ? 'selected' : ''; ?>>Shakyamuni Buddha</option>
                <option value="Green Tara" <?php echo $deity == 'Green Tara' ? 'selected' : ''; ?>>Green Tara</option>
                <option value="White Tara" <?php echo $deity == 'White Tara' ? 'selected' : ''; ?>>White Tara</option>
                <option value="Guru Rinpoche" <?php echo $deity == 'Guru Rinpoche' ? 'selected' : ''; ?>>Guru Rinpoche</option>
                <option value="Medicine Buddha" <?php echo $deity == 'Medicine Buddha' ? 'selected' : ''; ?>>Medicine Buddha</option>
                <option value="Mandala" <?php echo $deity == 'Mandala' ? 'selected' : ''; ?>>Mandala</option>
            </select>
        </div>
        <div class="result-count">
            <i class="fas fa-image"></i> 
            Showing <?php echo mysqli_num_rows($result); ?> of <?php echo $total_rows; ?> artworks
        </div>
    </div>

    <!-- Gallery Grid -->
    <div class="gallery-grid" id="galleryGrid">
        <?php
        if ($result && mysqli_num_rows($result) > 0):
            while ($row = mysqli_fetch_assoc($result)):
                $views         = $row['views'] ?? 0;
                $total_likes   = $row['total_likes'] ?? 0;
                $total_comments = $row['total_comments'] ?? 0;
                $algo_score    = $row['popularity_score'] ?? round(($views * 0.3) + ($total_likes * 0.5) + ($total_comments * 0.2), 1);
                $artist_name   = !empty($row['full_name']) ? $row['full_name'] : $row['username'];

                $user_has_liked = false;
                if (isset($_SESSION['user_id'])) {
                    $check_stmt = mysqli_prepare($conn, "SELECT artwork_id FROM artwork_likes WHERE user_id = ? AND artwork_id = ?");
                    mysqli_stmt_bind_param($check_stmt, "ii", $_SESSION['user_id'], $row['artwork_id']);
                    mysqli_stmt_execute($check_stmt);
                    mysqli_stmt_store_result($check_stmt);
                    $user_has_liked = mysqli_stmt_num_rows($check_stmt) > 0;
                    mysqli_stmt_close($check_stmt);
                }
        ?>
        <div class="artwork-card" data-artwork-id="<?php echo $row['artwork_id']; ?>" data-current-score="<?php echo $algo_score; ?>">
            <div class="artwork-image">
                <img src="uploads/artworks/<?php echo htmlspecialchars($row['image_path']); ?>"
                     alt="<?php echo htmlspecialchars($row['title']); ?>"
                     onerror="this.src='images/placeholder.jpg'">
                <div class="artwork-overlay">
                    <a href="artwork-detail.php?id=<?php echo $row['artwork_id']; ?>" class="view-btn">
                        <i class="fas fa-eye"></i> View
                    </a>
                </div>
            </div>
            <div class="artwork-info">
                <div class="algo-score" id="score-<?php echo $row['artwork_id']; ?>">
                    <i class="fas fa-chart-line"></i> Score: <?php echo $algo_score; ?>
                </div>
                <h3><?php echo htmlspecialchars($row['title']); ?></h3>
                <p class="artist">
                    <i class="fas fa-user"></i>
                    <?php echo htmlspecialchars($artist_name); ?>
                </p>
                <div class="artwork-stats">
                    <div class="stats-left">
                        <div class="stat-item">
                            <i class="fas fa-eye"></i> <span class="view-count-<?php echo $row['artwork_id']; ?>"><?php echo number_format($views); ?></span>
                        </div>
                        <div class="stat-item">
                            <i class="fas fa-comment"></i> <span class="comment-count-<?php echo $row['artwork_id']; ?>"><?php echo number_format($total_comments); ?></span>
                        </div>
                    </div>
                    <button class="like-btn <?php echo $user_has_liked ? 'liked' : ''; ?>"
                            data-artwork-id="<?php echo $row['artwork_id']; ?>"
                            onclick="toggleLike(<?php echo $row['artwork_id']; ?>, this)">
                        <i class="<?php echo $user_has_liked ? 'fas' : 'far'; ?> fa-heart"></i>
                        <span class="like-count-<?php echo $row['artwork_id']; ?>"><?php echo $total_likes; ?></span>
                    </button>
                </div>
            </div>
        </div>
        <?php
            endwhile;
        else:
        ?>
        <div class="no-artworks">
            <i class="fas fa-palette"></i>
            <h3>No artworks found</h3>
            <p>Try selecting a different deity or check back later</p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="?deity=<?php echo urlencode($deity); ?>&page=<?php echo $page - 1; ?>" class="page-btn">
                <i class="fas fa-chevron-left"></i> Previous
            </a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <?php if ($i == $page): ?>
                <span class="page-btn active"><?php echo $i; ?></span>
            <?php else: ?>
                <a href="?deity=<?php echo urlencode($deity); ?>&page=<?php echo $i; ?>" class="page-btn">
                    <?php echo $i; ?>
                </a>
            <?php endif; ?>
        <?php endfor; ?>

        <?php if ($page < $total_pages): ?>
            <a href="?deity=<?php echo urlencode($deity); ?>&page=<?php echo $page + 1; ?>" class="page-btn">
                Next <i class="fas fa-chevron-right"></i>
            </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<script>
function updateFilters() {
    const deity = document.getElementById('deityFilter').value;
    window.location.href = '?deity=' + encodeURIComponent(deity) + '&page=1';
}
function toggleLike(artworkId, buttonElement) {
    <?php if (!isset($_SESSION['user_id'])): ?>
        window.location.href = 'login.php';
        return;
    <?php endif; ?>

    const heartIcon = buttonElement.querySelector('i');
    const likeCountSpan = buttonElement.querySelector('.like-count-' + artworkId);
    const artworkCard = buttonElement.closest('.artwork-card');
    const scoreSpan = document.getElementById('score-' + artworkId);
    
    // Store original values for rollback
    const wasLiked = buttonElement.classList.contains('liked');
    const oldLikeCount = parseInt(likeCountSpan.textContent);
    const oldScoreText = scoreSpan ? scoreSpan.textContent.replace('Score: ', '') : '0';
    const oldScore = parseFloat(oldScoreText);
    
    // Optimistic update (visual feedback only)
    if (wasLiked) {
        heartIcon.className = 'far fa-heart';
        buttonElement.classList.remove('liked');
        likeCountSpan.textContent = oldLikeCount - 1;
        if (scoreSpan) {
            let newScore = (oldScore - 0.5).toFixed(1);
            scoreSpan.innerHTML = '<i class="fas fa-chart-line"></i> Score: ' + newScore;
        }
    } else {
        heartIcon.className = 'fas fa-heart';
        buttonElement.classList.add('liked');
        likeCountSpan.textContent = oldLikeCount + 1;
        if (scoreSpan) {
            let newScore = (oldScore + 0.5).toFixed(1);
            scoreSpan.innerHTML = '<i class="fas fa-chart-line"></i> Score: ' + newScore;
        }
    }
    
    buttonElement.disabled = true;
    buttonElement.style.opacity = '0.6';

    fetch('ajax/like-artwork.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ artwork_id: artworkId })
    })
    .then(response => response.json())
    .then(data => {
        buttonElement.disabled = false;
        buttonElement.style.opacity = '1';
        
        if (data.success) {
            // Update with correct values from server
            likeCountSpan.textContent = data.new_count;
            if (scoreSpan) {
                scoreSpan.innerHTML = '<i class="fas fa-chart-line"></i> Score: ' + data.new_score;
            }
            
            // Ensure like button state matches server
            if (data.liked) {
                heartIcon.className = 'fas fa-heart';
                buttonElement.classList.add('liked');
            } else {
                heartIcon.className = 'far fa-heart';
                buttonElement.classList.remove('liked');
            }
            
            // Animation feedback
            heartIcon.style.transform = 'scale(1.3)';
            setTimeout(() => heartIcon.style.transform = 'scale(1)', 200);
        } else {
            // Rollback on error
            likeCountSpan.textContent = oldLikeCount;
            if (scoreSpan) {
                scoreSpan.innerHTML = '<i class="fas fa-chart-line"></i> Score: ' + oldScore.toFixed(1);
            }
            if (wasLiked) {
                heartIcon.className = 'fas fa-heart';
                buttonElement.classList.add('liked');
            } else {
                heartIcon.className = 'far fa-heart';
                buttonElement.classList.remove('liked');
            }
            alert(data.message || 'Error updating like');
            if (data.message === 'Please login to like') {
                window.location.href = 'login.php';
            }
        }
    })
    .catch(error => {
        buttonElement.disabled = false;
        buttonElement.style.opacity = '1';
        // Rollback on network error
        likeCountSpan.textContent = oldLikeCount;
        if (scoreSpan) {
            scoreSpan.innerHTML = '<i class="fas fa-chart-line"></i> Score: ' + oldScore.toFixed(1);
        }
        if (wasLiked) {
            heartIcon.className = 'fas fa-heart';
            buttonElement.classList.add('liked');
        } else {
            heartIcon.className = 'far fa-heart';
            buttonElement.classList.remove('liked');
        }
        alert('Network error. Please try again.');
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>