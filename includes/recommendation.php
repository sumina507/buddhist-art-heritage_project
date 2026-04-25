<?php
// recommendation.php - Algorithm for artwork recommendations
require_once 'includes/navbar.php';

class ArtworkRecommender {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    /**
     * Algorithm: Popularity-based Recommendation
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
}
?>