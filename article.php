<?php
// article.php
require_once 'includes/config.php';
require_once 'includes/navbar.php';

$article_id = $_GET['id'] ?? 0;
$page_title = "Knowledge Article";

// Increment view count
if ($article_id) {
    $sql = "UPDATE knowledge_articles SET views = views + 1 WHERE article_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $article_id);
    mysqli_stmt_execute($stmt);
}

// Get article details
$sql = "SELECT ka.*, u.username, u.full_name as author_name 
        FROM knowledge_articles ka
        LEFT JOIN users u ON ka.user_id = u.user_id
        WHERE ka.article_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $article_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$article = mysqli_fetch_assoc($result);

if (!$article) {
    $_SESSION['message'] = "Article not found!";
    $_SESSION['message_type'] = 'error';
    header('Location: knowledge.php');
    exit;
}

$page_title = $article['title'] . " - Buddhist Art Knowledge";

// Get related articles
$related_sql = "SELECT * FROM knowledge_articles 
                WHERE category = ? AND article_id != ? 
                ORDER BY created_at DESC 
                LIMIT 3";
$stmt = mysqli_prepare($conn, $related_sql);
mysqli_stmt_bind_param($stmt, "si", $article['category'], $article_id);
mysqli_stmt_execute($stmt);
$related_result = mysqli_stmt_get_result($stmt);
?>

<div class="container article-container">
    <!-- Breadcrumb -->
    <nav class="breadcrumb">
        <a href="index.php">Home</a> &gt;
        <a href="knowledge.php">Knowledge Base</a> &gt;
        <span><?php echo htmlspecialchars($article['category']); ?></span> &gt;
        <span><?php echo htmlspecialchars($article['title']); ?></span>
    </nav>
    
    <!-- Article Header -->
    <header class="article-header">
        <div class="article-meta">
            <span class="category-badge"><?php echo $article['category']; ?></span>
            <span class="views"><i class="fas fa-eye"></i> <?php echo $article['views']; ?> views</span>
            <span class="date"><i class="fas fa-calendar"></i> <?php echo date('F j, Y', strtotime($article['created_at'])); ?></span>
            <?php if ($article['author_name']): ?>
            <span class="author"><i class="fas fa-user"></i> <?php echo htmlspecialchars($article['author_name']); ?></span>
            <?php endif; ?>
        </div>
        
        <h1><?php echo htmlspecialchars($article['title']); ?></h1>
        
        <?php if ($article['featured_image']): ?>
        <div class="featured-image">
            <img src="uploads/articles/<?php echo htmlspecialchars($article['featured_image']); ?>" 
                 alt="<?php echo htmlspecialchars($article['title']); ?>">
        </div>
        <?php endif; ?>
    </header>
    
    <!-- Article Content -->
    <article class="article-content">
        <?php echo nl2br(htmlspecialchars($article['content'])); ?>
    </article>
    
    <!-- Article Tags -->
    <div class="article-tags">
        <h3><i class="fas fa-tags"></i> Related Topics</h3>
        <div class="tags">
            <span class="tag">Buddhist Art</span>
            <span class="tag"><?php echo $article['category']; ?></span>
            <span class="tag">Cultural Heritage</span>
            <span class="tag">Traditional Art</span>
            <span class="tag">Art History</span>
        </div>
    </div>
    
    <!-- Share Article -->
    <div class="share-article">
        <h3><i class="fas fa-share-alt"></i> Share This Article</h3>
        <div class="share-buttons">
            <button class="share-btn facebook" onclick="shareOnFacebook()">
                <i class="fab fa-facebook-f"></i> Facebook
            </button>
            <button class="share-btn twitter" onclick="shareOnTwitter()">
                <i class="fab fa-twitter"></i> Twitter
            </button>
            <button class="share-btn whatsapp" onclick="shareOnWhatsApp()">
                <i class="fab fa-whatsapp"></i> WhatsApp
            </button>
            <button class="share-btn copy" onclick="copyArticleLink()">
                <i class="fas fa-copy"></i> Copy Link
            </button>
        </div>
    </div>
    
    <!-- Related Articles -->
    <div class="related-articles">
        <h2><i class="fas fa-book-open"></i> Related Articles</h2>
        
        <div class="related-grid">
            <?php if (mysqli_num_rows($related_result) > 0): ?>
                <?php while ($related = mysqli_fetch_assoc($related_result)): ?>
                <a href="article.php?id=<?php echo $related['article_id']; ?>" class="related-card">
                    <div class="related-image">
                        <?php if ($related['featured_image']): ?>
                            <img src="uploads/articles/<?php echo htmlspecialchars($related['featured_image']); ?>" 
                                 alt="<?php echo htmlspecialchars($related['title']); ?>">
                        <?php else: ?>
                            <div class="no-image">
                                <i class="fas fa-book"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="related-info">
                        <h4><?php echo htmlspecialchars($related['title']); ?></h4>
                        <p class="related-excerpt">
                            <?php echo substr(htmlspecialchars($related['content']), 0, 100); ?>...
                        </p>
                        <div class="related-meta">
                            <span class="category"><?php echo $related['category']; ?></span>
                            <span class="views"><i class="fas fa-eye"></i> <?php echo $related['views']; ?></span>
                        </div>
                    </div>
                </a>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="no-related">No related articles found. <a href="knowledge.php">Browse all articles</a></p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Back to Knowledge Base -->
    <div class="back-to-knowledge">
        <a href="knowledge.php" class="btn-primary">
            <i class="fas fa-arrow-left"></i> Back to Knowledge Base
        </a>
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
        <a href="admin/articles.php?edit=<?php echo $article['article_id']; ?>" class="btn-edit">
            <i class="fas fa-edit"></i> Edit Article
        </a>
        <?php endif; ?>
    </div>
</div>

<style>
.article-container {
    padding: 2rem 0;
    max-width: 900px;
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

/* Article Header */
.article-header {
    margin-bottom: 3rem;
    padding-bottom: 2rem;
    border-bottom: 2px solid #eee;
}

.article-meta {
    display: flex;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
    align-items: center;
}

.category-badge {
    background: var(--accent-color);
    color: var(--dark-color);
    padding: 0.5rem 1.2rem;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.9rem;
}

.views, .date, .author {
    color: #666;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 5px;
}

.article-header h1 {
    color: var(--primary-color);
    font-size: 2.5rem;
    line-height: 1.3;
    margin-bottom: 1.5rem;
}

.featured-image {
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
}

.featured-image img {
    width: 100%;
    height: auto;
    display: block;
}

/* Article Content */
.article-content {
    font-size: 1.1rem;
    line-height: 1.8;
    color: #333;
    margin-bottom: 3rem;
}

.article-content p {
    margin-bottom: 1.5rem;
}

.article-content h2, 
.article-content h3, 
.article-content h4 {
    color: var(--primary-color);
    margin: 2rem 0 1rem;
}

.article-content ul, 
.article-content ol {
    margin: 1rem 0 1.5rem 2rem;
}

.article-content li {
    margin-bottom: 0.8rem;
}

.article-content blockquote {
    border-left: 4px solid var(--accent-color);
    padding-left: 1.5rem;
    margin: 2rem 0;
    font-style: italic;
    color: #555;
}

/* Article Tags */
.article-tags {
    margin-bottom: 3rem;
    padding-bottom: 2rem;
    border-bottom: 1px solid #eee;
}

.article-tags h3 {
    color: var(--primary-color);
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 10px;
}

.tags {
    display: flex;
    gap: 0.8rem;
    flex-wrap: wrap;
}

.tag {
    background: #f8f9fa;
    color: #666;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.9rem;
    transition: all 0.3s;
}

.tag:hover {
    background: var(--accent-color);
    color: var(--dark-color);
    cursor: pointer;
}

/* Share Article */
.share-article {
    margin-bottom: 3rem;
    padding-bottom: 2rem;
    border-bottom: 1px solid #eee;
}

.share-article h3 {
    color: var(--primary-color);
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 10px;
}

.share-buttons {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.share-btn {
    padding: 0.8rem 1.5rem;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s;
}

.share-btn.facebook {
    background: #3b5998;
    color: white;
}

.share-btn.twitter {
    background: #1da1f2;
    color: white;
}

.share-btn.whatsapp {
    background: #25d366;
    color: white;
}

.share-btn.copy {
    background: #6c757d;
    color: white;
}

.share-btn:hover {
    transform: translateY(-2px);
    opacity: 0.9;
}

/* Related Articles */
.related-articles {
    margin-bottom: 3rem;
}

.related-articles h2 {
    color: var(--primary-color);
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 10px;
}

.related-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
}

.related-card {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    text-decoration: none;
    color: inherit;
    box-shadow: 0 3px 10px rgba(0,0,0,0.08);
    transition: transform 0.3s, box-shadow 0.3s;
}

.related-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 20px rgba(0,0,0,0.15);
}

.related-image {
    height: 150px;
    overflow: hidden;
}

.related-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.no-image {
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8f9fa;
    color: #ddd;
    font-size: 2rem;
}

.related-info {
    padding: 1.2rem;
}

.related-info h4 {
    color: var(--primary-color);
    margin-bottom: 0.8rem;
    font-size: 1rem;
}

.related-excerpt {
    color: #666;
    font-size: 0.9rem;
    line-height: 1.5;
    margin-bottom: 1rem;
}

.related-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.category {
    background: #e8f4fc;
    color: var(--info-color);
    padding: 0.3rem 0.8rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
}

.views {
    color: #888;
    font-size: 0.85rem;
    display: flex;
    align-items: center;
    gap: 5px;
}

.no-related {
    text-align: center;
    padding: 2rem;
    color: #666;
    grid-column: 1 / -1;
}

.no-related a {
    color: var(--secondary-color);
    text-decoration: none;
    font-weight: 600;
}

.no-related a:hover {
    text-decoration: underline;
}

/* Back to Knowledge Base */
.back-to-knowledge {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 2rem;
    border-top: 1px solid #eee;
}

.btn-primary, .btn-edit {
    padding: 0.8rem 2rem;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s;
}

.btn-primary {
    background: var(--secondary-color);
    color: white;
}

.btn-primary:hover {
    background: #c0392b;
    transform: translateY(-2px);
}

.btn-edit {
    background: var(--warning-color);
    color: white;
}

.btn-edit:hover {
    background: #e67e22;
    transform: translateY(-2px);
}

/* Responsive */
@media (max-width: 768px) {
    .article-header h1 {
        font-size: 2rem;
    }
    
    .article-meta {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.8rem;
    }
    
    .share-buttons {
        flex-direction: column;
    }
    
    .share-btn {
        justify-content: center;
    }
    
    .related-grid {
        grid-template-columns: 1fr;
    }
    
    .back-to-knowledge {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }
}
</style>

<script>
function shareOnFacebook() {
    const url = encodeURIComponent(window.location.href);
    const title = encodeURIComponent(document.title);
    window.open(`https://www.facebook.com/sharer/sharer.php?u=${url}&quote=${title}`, '_blank');
}

function shareOnTwitter() {
    const url = encodeURIComponent(window.location.href);
    const text = encodeURIComponent(document.title);
    window.open(`https://twitter.com/intent/tweet?url=${url}&text=${text}`, '_blank');
}

function shareOnWhatsApp() {
    const url = encodeURIComponent(window.location.href);
    const text = encodeURIComponent(document.title);
    window.open(`https://api.whatsapp.com/send?text=${text}%20${url}`, '_blank');
}

function copyArticleLink() {
    const url = window.location.href;
    navigator.clipboard.writeText(url).then(() => {
        alert('Article link copied to clipboard!');
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>