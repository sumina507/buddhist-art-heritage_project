<?php
// test-algorithm.php
require_once 'includes/config.php';
require_once 'includes/recommendation.php';

echo "<h2>Testing Recommendation Algorithms</h2>";

$recommender = new ArtworkRecommender($conn);

// Test Popular
echo "<h3>1. Popular Artworks:</h3>";
$popular = $recommender->getPopularArtworks(3);
if (count($popular) > 0) {
    echo "✅ Found " . count($popular) . " popular artworks<br>";
    foreach ($popular as $a) {
        echo " - " . $a['title'] . " (Score: " . round($a['popularity_score'], 2) . ")<br>";
    }
} else {
    echo "⚠️ No popular artworks found. Upload some artworks first!<br>";
}

// Test Trending
echo "<h3>2. Trending Artworks:</h3>";
$trending = $recommender->getTrendingArtworks(3);
echo "✅ Found " . count($trending) . " trending artworks<br>";

// Test Similar (if any artwork exists)
$artwork_sql = "SELECT artwork_id FROM artworks LIMIT 1";
$result = mysqli_query($conn, $artwork_sql);
if (mysqli_num_rows($result) > 0) {
    $artwork = mysqli_fetch_assoc($result);
    $similar = $recommender->getSimilarArtworks($artwork['artwork_id'], 3);
    echo "<h3>3. Similar Artworks:</h3>";
    echo "✅ Found " . count($similar) . " similar artworks<br>";
}
?>