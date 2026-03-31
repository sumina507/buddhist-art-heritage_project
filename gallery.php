<?php
require_once 'includes/config.php';

$page_title = "Thangka Gallery - Buddhist Art Heritage";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/gallery.css">
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@300;400;700&family=Open+Sans:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php require_once 'includes/navbar.php'; ?>

<?php
// Get filter parameters
$deity = isset($_GET['deity']) ? mysqli_real_escape_string($conn, $_GET['deity']) : 'all';
$sort = isset($_GET['sort']) ? mysqli_real_escape_string($conn, $_GET['sort']) : 'popular';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 12;
$offset = ($page - 1) * $limit;
?>

<div class="container gallery-container">
    <div class="gallery-header">
        <h1><i class="fas fa-palette"></i> Sacred Thangka Gallery</h1>
        <p>Explore traditional Buddhist Thangka paintings</p>
    </div>
    
    <!-- Algorithm Notice -->
    <div style="background: #f0f4f8; padding: 1rem 1.5rem; border-radius: 10px; margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; border-left: 4px solid #e74c3c;">
        <div>
            <span style="font-weight: 600; color: #2c3e50;"><i class="fas fa-robot" style="color: #e74c3c; margin-right: 8px;"></i> Algorithm:</span>
            <span style="color: #555;"> Popularity = Views(0.3) + Likes(0.5) + Comments(0.2)</span>
        </div>
        <a href="algorithm-demo.php" style="background: #f1c40f; color: white; padding: 0.4rem 1rem; border-radius: 5px; text-decoration: none; font-size: 0.9rem;">View Demo</a>
    </div>
    
    <!-- Filter Bar -->
    <div class="filter-bar">
        <div class="filter-group">
            <label for="deityFilter"><i class="fas fa-om"></i> Deity:</label>
            <select id="deityFilter" class="filter-select" onchange="updateFilters()">
                <option value="all" <?php echo $deity == 'all' ? 'selected' : ''; ?>>All Artworks</option>
                <option value="Shakyamuni Buddha">Shakyamuni Buddha</option>
                <option value="Green Tara">Green Tara</option>
                <option value="White Tara">White Tara</option>
                <option value="Guru Rinpoche">Guru Rinpoche</option>
                <option value="Medicine Buddha">Medicine Buddha</option>
                <option value="Mandala">Mandala</option>
            </select>
        </div>
        
        <div class="filter-group">
            <label for="sortFilter"><i class="fas fa-sort-amount-down"></i> Sort:</label>
            <select id="sortFilter" class="filter-select" onchange="updateFilters()">
                <option value="popular" <?php echo $sort == 'popular' ? 'selected' : ''; ?>>Most Popular</option>
                <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Newest</option>
                <option value="most_liked" <?php echo $sort == 'most_liked' ? 'selected' : ''; ?>>Most Liked</option>
            </select>
        </div>
    </div>
    
    <div class="gallery-grid">
        <?php
        $order_by = 'a.views DESC';
        if ($sort == 'newest') $order_by = 'a.created_at DESC';
        if ($sort == 'most_liked') $order_by = 'a.likes DESC';

        // REMOVED category filter - show ALL approved artworks
        $where = "";

        if ($deity != 'all') {
            $where .= " AND a.title LIKE '%$deity%'";
        }

        $sql = "SELECT a.*, u.username, u.full_name,
               (SELECT COUNT(*) FROM artwork_likes WHERE artwork_id = a.artwork_id) as like_count
        FROM artworks a
        JOIN artists ar ON a.artist_id = ar.artist_id
        JOIN users u ON ar.user_id = u.user_id
        WHERE ar.status = 'approved' $where
        ORDER BY $order_by
        LIMIT $limit OFFSET $offset";
        
        $result = mysqli_query($conn, $sql);
        
        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                // Check if user liked
                $user_liked = false;
                if (isset($_SESSION['user_id'])) {
                    $check_sql = "SELECT * FROM artwork_likes WHERE user_id = ? AND artwork_id = ?";
                    $check_stmt = mysqli_prepare($conn, $check_sql);
                    mysqli_stmt_bind_param($check_stmt, "ii", $_SESSION['user_id'], $row['artwork_id']);
                    mysqli_stmt_execute($check_stmt);
                    $check_result = mysqli_stmt_get_result($check_stmt);
                    $user_liked = mysqli_num_rows($check_result) > 0;
                }
                
                $like_count = $row['like_count'] ?? $row['likes'];
                ?>
                <div class="artwork-card" data-id="<?php echo $row['artwork_id']; ?>">
                    <div class="artwork-image">
                        <img src="uploads/artworks/<?php echo htmlspecialchars($row['image_path']); ?>" 
                             alt="<?php echo htmlspecialchars($row['title']); ?>"
                             loading="lazy">
                        <div class="artwork-overlay">
                            <a href="artwork-detail.php?id=<?php echo $row['artwork_id']; ?>" class="view-btn">
                                <i class="fas fa-eye"></i> View
                            </a>
                        </div>
                    </div>
                    <div class="artwork-info">
                        <h3><?php echo htmlspecialchars($row['title']); ?></h3>
                        <p class="artist">
                            <i class="fas fa-user"></i> 
                            <?php 
                            $artist_name = !empty($row['full_name']) ? $row['full_name'] : $row['username'];
                            echo htmlspecialchars($artist_name); 
                            ?>
                        </p>
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
            echo '<div class="no-artworks">
                    <i class="fas fa-palette"></i>
                    <h3>No artworks found</h3>
                    <p>Check back later</p>
                  </div>';
        }
        ?>
    </div>
</div>

<style>
/* Modern Card Styles */
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
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
}

.artwork-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 15px 35px rgba(0,0,0,0.12);
}

.artwork-image {
    position: relative;
    height: 240px;
    overflow: hidden;
    background: #f5f5f5;
}

.artwork-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.4s ease;
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
    background: rgba(0,0,0,0.6);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.artwork-card:hover .artwork-overlay {
    opacity: 1;
}

.view-btn {
    background: #f1c40f;
    color: white;
    padding: 0.7rem 1.5rem;
    border-radius: 40px;
    text-decoration: none;
    font-size: 0.9rem;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: transform 0.2s;
}

.view-btn:hover {
    transform: scale(1.05);
    background: #f1c40f;
}

.artwork-info {
    padding: 1.2rem;
}

.artwork-info h3 {
    font-size: 1.1rem;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 0.3rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.artist {
    font-size: 0.85rem;
    color: #7f8c8d;
    margin-bottom: 1rem;
}

.artwork-stats {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 0.8rem;
    border-top: 1px solid #ecf0f1;
}

.views {
    font-size: 0.85rem;
    color: #7f8c8d;
    display: flex;
    align-items: center;
    gap: 5px;
}

.views i {
    color: #3498db;
}

.like-btn {
    background: none;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.9rem;
    color: #95a5a6;
    transition: all 0.2s;
    padding: 5px 10px;
    border-radius: 30px;
}

.like-btn:hover {
    background: #fef5f4;
    color: #e74c3c;
}

.like-btn i {
    font-size: 1rem;
    transition: transform 0.2s;
}

.like-btn:hover i {
    transform: scale(1.15);
}

.like-btn.liked {
    color: #e74c3c;
}

.like-btn.liked i {
    color: #e74c3c;
}

/* Filter Bar */
.filter-bar {
    background: white;
    padding: 1rem 1.5rem;
    border-radius: 12px;
    margin-bottom: 2rem;
    display: flex;
    gap: 2rem;
    align-items: center;
    flex-wrap: wrap;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.filter-group {
    display: flex;
    align-items: center;
    gap: 0.8rem;
}

.filter-group label {
    font-weight: 600;
    color: #2c3e50;
    font-size: 0.9rem;
}

.filter-select {
    padding: 0.5rem 1rem;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    font-size: 0.9rem;
    color: #2c3e50;
    background: white;
    min-width: 180px;
    cursor: pointer;
}

.filter-select:focus {
    outline: none;
    border-color: #f1c40f;
}

.no-artworks {
    grid-column: 1/-1;
    text-align: center;
    padding: 4rem;
    background: white;
    border-radius: 16px;
}

@media (max-width: 992px) {
    .gallery-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 576px) {
    .gallery-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .filter-bar {
        flex-direction: column;
        align-items: stretch;
        gap: 1rem;
    }
    
    .filter-group {
        width: 100%;
    }
    
    .filter-select {
        width: 100%;
    }
    
    .gallery-grid {
        grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
        gap: 1.2rem;
    }
}
</style>

<script>
function updateFilters() {
    const deity = document.getElementById('deityFilter').value;
    const sort = document.getElementById('sortFilter').value;
    window.location.href = `?deity=${encodeURIComponent(deity)}&sort=${sort}&page=1`;
}

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