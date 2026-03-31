<?php
require_once 'includes/config.php';
require_once 'includes/recommendation.php';

$page_title = "Algorithm Demo - Recommendation System";
require_once 'includes/navbar.php';

$recommender = new ArtworkRecommender($conn);
?>

<div class="container" style="padding: 2rem 0;">
    <h1 class="page-title">🎯 Algorithm Implementation Demo</h1>
    <p class="subtitle">This demonstrates the artwork recommendation algorithm for your college project</p>
    
    <!-- Algorithm Explanation -->
    <div class="algorithm-explanation" style="background: #f8f9fa; padding: 2rem; border-radius: 10px; margin-bottom: 2rem;">
        <h2><i class="fas fa-calculator"></i> Algorithm Formula</h2>
        <p>The recommendation algorithm uses this weighted formula:</p>
        
        <div style="background: white; padding: 1.5rem; border-radius: 8px; margin: 1rem 0;">
            <h3 style="color: var(--primary-color);">Popularity Score = (Views × 0.3) + (Likes × 0.5) + (Comments × 0.2)</h3>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-top: 1rem;">
                <div style="text-align: center;">
                    <div style="width: 80px; height: 80px; background: #3498db; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; font-size: 1.5rem;">
                        0.3
                    </div>
                    <h4>Views Weight</h4>
                    <p>30% importance</p>
                </div>
                <div style="text-align: center;">
                    <div style="width: 80px; height: 80px; background: #e74c3c; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; font-size: 1.5rem;">
                        0.5
                    </div>
                    <h4>Likes Weight</h4>
                    <p>50% importance</p>
                </div>
                <div style="text-align: center;">
                    <div style="width: 80px; height: 80px; background: #2ecc71; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; font-size: 1.5rem;">
                        0.2
                    </div>
                    <h4>Comments Weight</h4>
                    <p>20% importance</p>
                </div>
            </div>
        </div>
        
        <h3 style="margin-top: 2rem;">Algorithm Types Implemented:</h3>
        <ol style="margin-left: 1.5rem;">
            <li><strong>Popularity-based:</strong> Most viewed/liked artworks</li>
            <li><strong>Trending:</strong> Recently popular with time decay</li>
            <li><strong>Personalized:</strong> Based on user preferences</li>
            <li><strong>Similar items:</strong> Content-based filtering</li>
        </ol>
    </div>
    
    <!-- Algorithm Results -->
    <div class="algorithm-results">
        <h2><i class="fas fa-chart-line"></i> Algorithm in Action</h2>
        
        <!-- Most Popular Artworks -->
        <div class="algorithm-section" style="margin: 2rem 0;">
            <h3><i class="fas fa-fire"></i> Most Popular Artworks (Algorithm 1)</h3>
            <p>Sorted by popularity score: (views×0.3 + likes×0.5 + comments×0.2)</p>
            
            <div class="artworks-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 1.5rem; margin-top: 1rem;">
                <?php
                $popular_artworks = $recommender->getPopularArtworks(6);
                
                if (count($popular_artworks) > 0) {
                    foreach ($popular_artworks as $artwork) {
                        echo '<div class="artwork-card" style="background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 3px 10px rgba(0,0,0,0.1);">
                                <div style="height: 150px; overflow: hidden;">
                                    <img src="uploads/artworks/' . $artwork['image_path'] . '" 
                                         alt="' . htmlspecialchars($artwork['title']) . '"
                                         style="width: 100%; height: 100%; object-fit: cover;">
                                </div>
                                <div style="padding: 1rem;">
                                    <h4 style="margin-bottom: 0.5rem;">' . htmlspecialchars($artwork['title']) . '</h4>
                                    <p style="color: #666; font-size: 0.9rem; margin-bottom: 0.5rem;">
                                        By: ' . htmlspecialchars($artwork['artist_name'] ?? $artwork['username']) . '
                                    </p>
                                    <div style="display: flex; justify-content: space-between; font-size: 0.9rem; color: #888;">
                                        <span><i class="fas fa-eye"></i> ' . $artwork['views'] . '</span>
                                        <span><i class="fas fa-heart"></i> ' . $artwork['like_count'] . '</span>
                                        <span><i class="fas fa-comment"></i> ' . $artwork['comment_count'] . '</span>
                                    </div>
                                    <div style="margin-top: 0.5rem; padding: 0.5rem; background: #e8f4fc; border-radius: 5px;">
                                        <small><strong>Popularity Score:</strong> ' . round($artwork['popularity_score'], 2) . '</small>
                                    </div>
                                </div>
                              </div>';
                    }
                } else {
                    echo '<p>No artworks found.</p>';
                }
                ?>
            </div>
        </div>
        
        <!-- Trending Artworks -->
        <div class="algorithm-section" style="margin: 2rem 0;">
            <h3><i class="fas fa-bolt"></i> Trending Artworks (Algorithm 2)</h3>
            <p>Recent popularity with time decay factor (last 30 days × 1.5 bonus)</p>
            
            <div class="artworks-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 1.5rem; margin-top: 1rem;">
                <?php
                $trending_artworks = $recommender->getTrendingArtworks(6);
                
                if (count($trending_artworks) > 0) {
                    foreach ($trending_artworks as $artwork) {
                        echo '<div class="artwork-card" style="background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 3px 10px rgba(0,0,0,0.1);">
                                <div style="height: 150px; overflow: hidden;">
                                    <img src="uploads/artworks/' . $artwork['image_path'] . '" 
                                         alt="' . htmlspecialchars($artwork['title']) . '"
                                         style="width: 100%; height: 100%; object-fit: cover;">
                                </div>
                                <div style="padding: 1rem;">
                                    <h4 style="margin-bottom: 0.5rem;">' . htmlspecialchars($artwork['title']) . '</h4>
                                    <p style="color: #666; font-size: 0.9rem; margin-bottom: 0.5rem;">
                                        By: ' . htmlspecialchars($artwork['artist_name'] ?? $artwork['username']) . '
                                    </p>
                                    <div style="margin-top: 0.5rem; padding: 0.5rem; background: #fff3cd; border-radius: 5px;">
                                        <small><strong>Trending Score:</strong> ' . round($artwork['trending_score'], 2) . '</small>
                                    </div>
                                </div>
                              </div>';
                    }
                }
                ?>
            </div>
        </div>
        
        <!-- Personalized Recommendations -->
        <?php if (isset($_SESSION['user_id'])): ?>
        <div class="algorithm-section" style="margin: 2rem 0;">
            <h3><i class="fas fa-user-check"></i> Personalized Recommendations (Algorithm 3)</h3>
            <p>Based on your liked categories and similar users</p>
            
            <div class="artworks-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 1.5rem; margin-top: 1rem;">
                <?php
                $user_id = $_SESSION['user_id'];
                $personalized = $recommender->getPersonalizedRecommendations($user_id, 6);
                
                if (count($personalized) > 0) {
                    foreach ($personalized as $artwork) {
                        echo '<div class="artwork-card" style="background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 3px 10px rgba(0,0,0,0.1);">
                                <div style="height: 150px; overflow: hidden;">
                                    <img src="uploads/artworks/' . $artwork['image_path'] . '" 
                                         alt="' . htmlspecialchars($artwork['title']) . '"
                                         style="width: 100%; height: 100%; object-fit: cover;">
                                </div>
                                <div style="padding: 1rem;">
                                    <h4 style="margin-bottom: 0.5rem;">' . htmlspecialchars($artwork['title']) . '</h4>
                                    <p style="color: #666; font-size: 0.9rem;">
                                        By: ' . htmlspecialchars($artwork['artist_name'] ?? $artwork['username']) . '
                                    </p>
                                </div>
                              </div>';
                    }
                }
                ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Algorithm Visualization -->
        <div class="visualization" style="background: white; padding: 2rem; border-radius: 10px; margin: 2rem 0;">
            <h3><i class="fas fa-chart-bar"></i> Algorithm Visualization</h3>
            <div id="algorithmChart" style="height: 300px; margin-top: 1rem;"></div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

<!-- Chart.js for visualization -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Algorithm Visualization Chart
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.createElement('canvas');
    document.getElementById('algorithmChart').appendChild(ctx);
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Views Weight', 'Likes Weight', 'Comments Weight'],
            datasets: [{
                label: 'Algorithm Weights',
                data: [0.3, 0.5, 0.2],
                backgroundColor: [
                    'rgba(52, 152, 219, 0.8)',
                    'rgba(231, 76, 60, 0.8)',
                    'rgba(46, 204, 113, 0.8)'
                ],
                borderColor: [
                    'rgb(52, 152, 219)',
                    'rgb(231, 76, 60)',
                    'rgb(46, 204, 113)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 1,
                    title: {
                        display: true,
                        text: 'Weight Value'
                    }
                }
            },
            plugins: {
                title: {
                    display: true,
                    text: 'Recommendation Algorithm Weights',
                    font: { size: 16 }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.label + ': ' + context.parsed.y * 100 + '%';
                        }
                    }
                }
            }
        }
    });
});
</script>
