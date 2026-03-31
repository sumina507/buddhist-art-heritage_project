<?php
// knowledge.php - Display Buddhist art educational content
require_once 'includes/config.php';
$page_title = "Buddhist Art Knowledge";
require_once 'includes/navbar.php';

$sql = "SELECT * FROM knowledge_articles ORDER BY created_at DESC";
$result = mysqli_query($conn, $sql);
?>

<div class="container">
    <h1>Buddhist Art Knowledge Base</h1>
    
    <div class="knowledge-grid">
        <?php while($article = mysqli_fetch_assoc($result)): ?>
        <article class="knowledge-card">
            <h3><?php echo htmlspecialchars($article['title']); ?></h3>
            <p><?php echo substr(htmlspecialchars($article['content']), 0, 200); ?>...</p>
            <a href="article.php?id=<?php echo $article['article_id']; ?>">Read More</a>
        </article>
        <?php endwhile; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>