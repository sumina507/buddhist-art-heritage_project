<?php
// artists.php - PUBLIC artist listing for all users
require_once 'includes/config.php';
require_once 'includes/navbar.php';

$page_title = "Buddhist Artists";

// Get all approved artists
$sql = "SELECT a.*, u.username, u.full_name, u.profile_image, u.bio,
               (SELECT COUNT(*) FROM artworks WHERE artist_id = a.artist_id) as artwork_count,
               (SELECT COUNT(*) FROM commissions WHERE artist_id = a.artist_id) as commission_count
        FROM artists a
        JOIN users u ON a.user_id = u.user_id
        WHERE a.status = 'approved'
        ORDER BY a.created_at ASC";

$result = mysqli_query($conn, $sql);
$total_artists = mysqli_num_rows($result);
?>

<div class="artists-container">
    <!-- Page Header -->
    <div class="page-header">
        <h1><i class="fas fa-paint-brush"></i> Our Artists</h1>
        <p>Meet our talented Buddhist artists</p>
    </div>
    
    <?php if (isset($_SESSION['message'])): ?>
        <div class="toast-notification toast-<?php echo $_SESSION['message_type']; ?>">
            <i class="fas <?php echo $_SESSION['message_type'] == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
            <span><?php echo $_SESSION['message']; ?></span>
        </div>
        <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
    <?php endif; ?>
    
        
       <!-- Artists Table -->
<div class="data-table-container">
    <div class="table-header">
        <h2>All Artists</h2>
        <div class="table-info"><?php echo $total_artists; ?> artist<?php echo $total_artists != 1 ? 's' : ''; ?></div>
    </div>
    
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Artist</th>
                    <th>Specialization</th>
                    <th>Experience</th>
                    <th>Artworks</th>
                    <th>Commissions</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </thead>
            <tbody>
                <?php if ($total_artists > 0): ?>
                    <?php while ($artist = mysqli_fetch_assoc($result)): 
                        $artist_name = $artist['full_name'] ?? $artist['username'];
                        $specialization = $artist['specialization'] ?? 'Artist';
                        $experience = $artist['experience_years'] ?? 0;
                        $joined_date = date('M Y', strtotime($artist['created_at']));
                    ?>
                        <tr>
                            <td style="font-weight: 500;"><?php echo $artist['artist_id']; ?></td>
                            <td>
                                <div class="artist-cell">
                                    <img src="uploads/profiles/<?php echo htmlspecialchars($artist['profile_image'] ?? 'default.jpg'); ?>" 
                                         alt="<?php echo htmlspecialchars($artist_name); ?>"
                                         class="artist-avatar">
                                    <div>
                                        <strong><?php echo htmlspecialchars($artist_name); ?></strong>
                                        <small>@<?php echo htmlspecialchars($artist['username']); ?></small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="specialization-badge"><?php echo htmlspecialchars($specialization); ?></span>
                            </td>
                            <td>
                                <?php echo $experience; ?> <?php echo $experience == 1 ? 'year' : 'years'; ?>
                            </td>
                            <td>
                                <span class="stat-badge artworks">
                                     <?php echo $artist['artwork_count']; ?>
                                </span>
                            </td>
                            <td>
                                <span class="stat-badge commissions">
                                    <i class="fas fa-handshake"></i> <?php echo $artist['commission_count'] ?? 0; ?>
                                </span>
                            </td>
                            <td>
                                <i class="fas fa-calendar-alt"></i> <?php echo $joined_date; ?>
                            </td>
                            <td>
                                <a href="artist-profile.php?id=<?php echo $artist['artist_id']; ?>" 
                                   class="btn-action btn-view-profile" title="View Profile">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="no-data">
                            <i class="fas fa-users-slash"></i>
                            <h4>No Artists Found</h4>
                            <p>Artists will appear here when they register and get approved</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>


<style>
.artists-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem 1rem;
}

/* Page Header */
.page-header {
    text-align: center;
    margin-bottom: 2rem;
}

.page-header h1 {
    font-size: 2rem;
    color: #2c3e50;
    margin-bottom: 0.5rem;
}

.page-header h1 i {
    color: #e74c3c;
    margin-right: 10px;
}

.page-header p {
    color: #7f8c8d;
    font-size: 1rem;
}

/* Toast Notification */
.toast-notification {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: white;
    padding: 1rem 2rem;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    display: flex;
    align-items: center;
    gap: 12px;
    z-index: 9999;
    animation: slideIn 0.3s ease;
    font-size: 1rem;
    font-weight: 500;
    min-width: 280px;
    justify-content: center;
    border-left: 4px solid;
}

.toast-success {
    border-left-color: #27ae60;
}

.toast-success i {
    color: #27ae60;
}

.toast-error {
    border-left-color: #e74c3c;
}

.toast-error i {
    color: #e74c3c;
}

.toast-notification span {
    color: #2c3e50;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translate(-50%, -50%) scale(0.9);
    }
    to {
        opacity: 1;
        transform: translate(-50%, -50%) scale(1);
    }
}

/* Data Table */
.data-table-container {
    background: white;
    border-radius: 16px;
    padding: 1rem;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.table-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    padding-bottom: 0.8rem;
    border-bottom: 1px solid #f0f0f0;
}

.table-header h2 {
    margin: 0;
    font-size: 1.2rem;
    color: #2c3e50;
}

.table-info {
    font-size: 0.8rem;
    color: #7f8c8d;
    background: #f8f9fa;
    padding: 0.3rem 0.8rem;
    border-radius: 20px;
}

.table-responsive {
    overflow-x: auto;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table th {
    text-align: left;
    padding: 0.8rem 0.5rem;
    background: #f8f9fa;
    color: #2c3e50;
    font-weight: 600;
    font-size: 0.85rem;
    border-bottom: 2px solid #e9ecef;
}

.data-table td {
    padding: 0.8rem 0.5rem;
    border-bottom: 1px solid #f0f0f0;
    font-size: 0.85rem;
    color: #2c3e50;
    vertical-align: middle;
}

.data-table tr:hover {
    background: #fef5f4;
}

/* Artist Cell */
.artist-cell {
    display: flex;
    align-items: center;
    gap: 10px;
}

.artist-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #f1c40f;
}

.artist-cell div {
    display: flex;
    flex-direction: column;
}

.artist-cell strong {
    font-size: 0.9rem;
    color: #2c3e50;
}

.artist-cell small {
    font-size: 0.7rem;
    color: #7f8c8d;
}

/* Badges */
.specialization-badge {
    background: #e8f4fc;
    color: #3498db;
    padding: 0.2rem 0.6rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 500;
    display: inline-block;
}

.stat-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 0.2rem 0.6rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 500;
}

.stat-badge.artworks {
    background: #fff3cd;
    color: #f39c12;
}

.stat-badge.commissions {
    background: #e8f4fc;
    color: #3498db;
}

/* Action Button */
.btn-action {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #f1c40f;
    color: #2c3e50;
    padding: 0.4rem 1rem;
    border-radius: 20px;
    text-decoration: none;
    font-size: 0.75rem;
    font-weight: 500;
    transition: all 0.2s;
}

.btn-action:hover {
    background: #e6b800;
    transform: translateY(-2px);
}

/* No Data */
.no-data {
    text-align: center;
    padding: 3rem !important;
}

.no-data i {
    font-size: 3rem;
    color: #ddd;
    margin-bottom: 1rem;
}

.no-data h4 {
    color: #2c3e50;
    margin-bottom: 0.5rem;
}

.no-data p {
    color: #7f8c8d;
    margin-bottom: 0;
}

/* Responsive */
@media (max-width: 900px) {
    .data-table th:nth-child(3),
    .data-table td:nth-child(3) {
        display: none;
    }
}

@media (max-width: 768px) {
    .artists-container {
        padding: 1rem;
    }
    
    .data-table th:nth-child(4),
    .data-table td:nth-child(4),
    .data-table th:nth-child(5),
    .data-table td:nth-child(5) {
        display: none;
    }
}
</style>

<script>
// Auto hide toast notification after 2 seconds
document.addEventListener('DOMContentLoaded', function() {
    var toast = document.querySelector('.toast-notification');
    if (toast) {
        setTimeout(function() {
            toast.style.animation = 'fadeOut 0.3s ease forwards';
            setTimeout(function() {
                toast.remove();
            }, 300);
        }, 2000);
    }
});

// Add fadeOut animation
var style = document.createElement('style');
style.textContent = `
    @keyframes fadeOut {
        from {
            opacity: 1;
            transform: translate(-50%, -50%) scale(1);
        }
        to {
            opacity: 0;
            transform: translate(-50%, -50%) scale(0.9);
            visibility: hidden;
        }
    }
`;
document.head.appendChild(style);
</script>

<?php require_once 'includes/footer.php'; ?>