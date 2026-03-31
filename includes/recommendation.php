<?php
// recommendation.php - Algorithm for artwork recommendations
require_once 'includes/navbar.php';

class ArtworkRecommender {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    /**
     * ALGORITHM 1: Popularity-based Recommendation
     * Calculates popularity score using weighted formula
     * Score = (views * 0.3) + (likes * 0.5) + (comments * 0.2)
     */
    public function getPopularArtworks($limit = 10) {
        $sql = "SELECT a.*, 
                       u.username,
                       u.full_name as artist_name,
                       COUNT(DISTINCT al.like_id) as like_count,
                       COUNT(DISTINCT ac.comment_id) as comment_count,
                       -- Popularity Algorithm Formula
                       (a.views * 0.3 + 
                        COUNT(DISTINCT al.like_id) * 0.5 + 
                        COUNT(DISTINCT ac.comment_id) * 0.2) as popularity_score
                FROM artworks a
                JOIN artists ar ON a.artist_id = ar.artist_id
                JOIN users u ON ar.user_id = u.user_id
                LEFT JOIN artwork_likes al ON a.artwork_id = al.artwork_id
                LEFT JOIN artwork_comments ac ON a.artwork_id = ac.artwork_id
                WHERE ar.status = 'approved'
                GROUP BY a.artwork_id
                ORDER BY popularity_score DESC, a.created_at DESC
                LIMIT ?";
        
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $limit);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $artworks = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $artworks[] = $row;
        }
        
        return $artworks;
    }
    
    /**
     * ALGORITHM 2: Trending Artworks (Last 7 days)
     * Uses time decay factor for recent popularity
     */
    public function getTrendingArtworks($limit = 10) {
        $sql = "SELECT a.*, 
                       u.username,
                       -- Trending algorithm with time decay
                       ((a.views * 0.3 + 
                         COUNT(DISTINCT al.like_id) * 0.5 + 
                         COUNT(DISTINCT ac.comment_id) * 0.2) * 
                        CASE 
                            WHEN a.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1.5
                            WHEN a.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1.2
                            ELSE 1
                        END) as trending_score
                FROM artworks a
                JOIN artists ar ON a.artist_id = ar.artist_id
                JOIN users u ON ar.user_id = u.user_id
                LEFT JOIN artwork_likes al ON a.artwork_id = al.artwork_id
                LEFT JOIN artwork_comments ac ON a.artwork_id = ac.artwork_id
                WHERE ar.status = 'approved' 
                  AND a.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY a.artwork_id
                ORDER BY trending_score DESC
                LIMIT ?";
        
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $limit);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $artworks = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $artworks[] = $row;
        }
        
        return $artworks;
    }
    
    /**
     * ALGORITHM 3: Personalized Recommendations (Collaborative Filtering)
     * Recommends based on user's liked categories and similar users
     */
    public function getPersonalizedRecommendations($user_id, $limit = 5) {
        if (!$user_id) return $this->getPopularArtworks($limit);
        
        // Step 1: Get user's favorite categories
        $user_categories = $this->getUserFavoriteCategories($user_id);
        
        // Step 2: Get artworks from favorite categories
        $recommendations = [];
        
        if (!empty($user_categories)) {
            $placeholders = str_repeat('?,', count($user_categories) - 1) . '?';
            $sql = "SELECT a.*, u.username,
                           -- Category match bonus
                           (CASE WHEN a.category IN ($placeholders) THEN 1.0 ELSE 0 END) as category_match_score
                    FROM artworks a
                    JOIN artists ar ON a.artist_id = ar.artist_id
                    JOIN users u ON ar.user_id = u.user_id
                    WHERE ar.status = 'approved'
                      AND a.artwork_id NOT IN (
                          SELECT artwork_id FROM artwork_likes WHERE user_id = ?
                      )
                    ORDER BY category_match_score DESC, a.views DESC
                    LIMIT ?";
            
            $types = str_repeat('s', count($user_categories)) . 'ii';
            $params = array_merge($user_categories, [$user_id, $limit]);
            
            $stmt = mysqli_prepare($this->conn, $sql);
            mysqli_stmt_bind_param($stmt, $types, ...$params);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            while ($row = mysqli_fetch_assoc($result)) {
                $recommendations[] = $row;
            }
        }
        
        // If not enough recommendations, add popular ones
        if (count($recommendations) < $limit) {
            $needed = $limit - count($recommendations);
            $popular = $this->getPopularArtworks($needed);
            $recommendations = array_merge($recommendations, $popular);
        }
        
        // Remove duplicates
        $seen = [];
        $final = [];
        foreach ($recommendations as $artwork) {
            if (!in_array($artwork['artwork_id'], $seen)) {
                $seen[] = $artwork['artwork_id'];
                $final[] = $artwork;
            }
        }
        
        return array_slice($final, 0, $limit);
    }
    
    /**
     * ALGORITHM 4: Similar Artworks (Content-based Filtering)
     * Finds artworks similar to a given artwork
     */
    public function getSimilarArtworks($artwork_id, $limit = 5) {
        // Get the target artwork's details
        $sql = "SELECT category, title, materials FROM artworks WHERE artwork_id = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $artwork_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $target = mysqli_fetch_assoc($result);
        
        if (!$target) return [];
        
        // Find similar artworks
        $sql = "SELECT a.*, u.username,
                       -- Similarity algorithm
                       (CASE WHEN a.category = ? THEN 2 ELSE 0 END +
                        CASE WHEN a.title LIKE CONCAT('%', ?, '%') THEN 1 ELSE 0 END +
                        CASE WHEN a.materials LIKE CONCAT('%', ?, '%') THEN 0.5 ELSE 0 END) as similarity_score
                FROM artworks a
                JOIN artists ar ON a.artist_id = ar.artist_id
                JOIN users u ON ar.user_id = u.user_id
                WHERE a.artwork_id != ? 
                  AND ar.status = 'approved'
                ORDER BY similarity_score DESC, a.views DESC
                LIMIT ?";
        
        $stmt = mysqli_prepare($this->conn, $sql);
        $keyword = $this->extractKeyword($target['title']);
        mysqli_stmt_bind_param($stmt, "sssii", 
            $target['category'], 
            $keyword,
            $keyword,
            $artwork_id, 
            $limit
        );
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $similar = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $similar[] = $row;
        }
        
        return $similar;
    }
    
    /**
     * Helper: Get user's favorite categories
     */
    private function getUserFavoriteCategories($user_id) {
        $sql = "SELECT a.category, COUNT(*) as interaction_count
                FROM artwork_likes al
                JOIN artworks a ON al.artwork_id = a.artwork_id
                WHERE al.user_id = ?
                GROUP BY a.category
                ORDER BY interaction_count DESC
                LIMIT 3";
        
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $categories = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $categories[] = $row['category'];
        }
        
        return $categories;
    }
    
    /**
     * Helper: Extract keyword from title
     */
    private function extractKeyword($title) {
        $common_words = ['the', 'and', 'of', 'in', 'to', 'a', 'with', 'on', 'for', 'by'];
        $words = explode(' ', strtolower($title));
        
        foreach ($words as $word) {
            if (!in_array($word, $common_words) && strlen($word) > 3) {
                return $word;
            }
        }
        
        return $words[0] ?? '';
    }
    
    /**
     * Update popularity tracking (cron job)
     */
    public function updatePopularityTracking() {
        $sql = "INSERT INTO artwork_popularity (artwork_id, date, views, likes, comments, total_score)
                SELECT a.artwork_id, 
                       CURDATE(),
                       a.views,
                       (SELECT COUNT(*) FROM artwork_likes WHERE artwork_id = a.artwork_id),
                       (SELECT COUNT(*) FROM artwork_comments WHERE artwork_id = a.artwork_id),
                       (a.views * 0.3 + 
                        (SELECT COUNT(*) FROM artwork_likes WHERE artwork_id = a.artwork_id) * 0.5 +
                        (SELECT COUNT(*) FROM artwork_comments WHERE artwork_id = a.artwork_id) * 0.2)
                FROM artworks a
                ON DUPLICATE KEY UPDATE
                    views = VALUES(views),
                    likes = VALUES(likes),
                    comments = VALUES(comments),
                    total_score = VALUES(total_score)";
        
        return mysqli_query($this->conn, $sql);
    }
}
?>