<?php
// my-artworks.php
require_once 'includes/config.php';
require_once 'includes/navbar.php';

// Check if user is logged in and is an artist
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'artist') {
    header('Location: login.php');
    exit;
}

$page_title = "My Artworks";
$artist_id = $_SESSION['artist_id'] ?? 0;

// Handle artwork deletion
if (isset($_GET['delete_id'])) {
    $artwork_id = intval($_GET['delete_id']);
    
    // Get image path first
    $sql = "SELECT image_path FROM artworks WHERE artwork_id = ? AND artist_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $artwork_id, $artist_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $artwork = mysqli_fetch_assoc($result);
    
    // Delete from database
    $sql = "DELETE FROM artworks WHERE artwork_id = ? AND artist_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $artwork_id, $artist_id);
    
    if (mysqli_stmt_execute($stmt)) {
        // Delete image file if not default
        if ($artwork && $artwork['image_path'] != 'default.jpg' && file_exists(ARTWORK_UPLOAD_PATH . $artwork['image_path'])) {
            unlink(ARTWORK_UPLOAD_PATH . $artwork['image_path']);
        }
        
        $_SESSION['message'] = "Artwork deleted successfully!";
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = "Error deleting artwork.";
        $_SESSION['message_type'] = 'error';
    }
    
    header('Location: my-artworks.php');
    exit;
}

// Get all artworks for initial load
$sql = "SELECT a.* 
        FROM artworks a 
        WHERE a.artist_id = ? 
        ORDER BY a.created_at DESC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $artist_id);
mysqli_stmt_execute($stmt);
$artworks_result = mysqli_stmt_get_result($stmt);
$total_artworks = mysqli_num_rows($artworks_result);
?>

<div class="my-artworks-container">
    
    <?php if (isset($_SESSION['message'])): ?>
    <div class="toast-notification toast-<?php echo $_SESSION['message_type']; ?>">
        <i class="fas <?php echo $_SESSION['message_type'] == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
        <span><?php echo $_SESSION['message']; ?></span>
    </div>
    <?php 
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
    ?>
    <?php endif; ?>
    
    <!-- Artworks Table with Search -->
    <div class="data-table-container">
        <div class="table-header">
            <h1>My Artworks</h1>
            <div class="header-right">
                <!-- Search Bar -->
                <div class="search-wrapper">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="searchInput" placeholder="Search by title..." autocomplete="off">
                </div>
                <div class="table-info" id="tableInfo"><?php echo $total_artworks; ?> artwork<?php echo $total_artworks != 1 ? 's' : ''; ?></div>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="data-table" id="artworksTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Image</th>
                        <th>Title</th>
                        <th>Views</th>
                        <th>Likes</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </thead>
                <tbody id="tableBody">
                    <?php if ($total_artworks > 0): ?>
                        <?php while ($artwork = mysqli_fetch_assoc($artworks_result)): 
                            // Get comment count
                            $comment_sql = "SELECT COUNT(*) as count FROM artwork_comments WHERE artwork_id = ?";
                            $stmt = mysqli_prepare($conn, $comment_sql);
                            mysqli_stmt_bind_param($stmt, "i", $artwork['artwork_id']);
                            mysqli_stmt_execute($stmt);
                            $comment_result = mysqli_stmt_get_result($stmt);
                            $comment_data = mysqli_fetch_assoc($comment_result);
                            $comment_count = $comment_data['count'] ?? 0;
                        ?>
                            <tr data-title="<?php echo strtolower(htmlspecialchars($artwork['title'])); ?>">
                                <td><?php echo $artwork['artwork_id']; ?></td>
                                <td>
                                    <img src="uploads/artworks/<?php echo htmlspecialchars($artwork['image_path']); ?>" 
                                         alt="<?php echo htmlspecialchars($artwork['title']); ?>"
                                         style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px;">
                                </td>
                                <td><strong><?php echo htmlspecialchars($artwork['title']); ?></strong></td>
                                <td><?php echo $artwork['views']; ?></td>
                                <td><?php echo $artwork['likes']; ?></td>
                                <td><?php echo date('M d, Y', strtotime($artwork['created_at'])); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="artwork-detail.php?id=<?php echo $artwork['artwork_id']; ?>" 
                                           class="btn-action btn-view" title="View Public" target="_blank">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit-artwork.php?id=<?php echo $artwork['artwork_id']; ?>" 
                                           class="btn-action btn-edit" title="Edit Artwork">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="?delete_id=<?php echo $artwork['artwork_id']; ?>" 
                                           class="btn-action btn-delete" title="Delete Artwork">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr class="no-data-row">
                            <td colspan="8" class="no-data">
                                <i class="fas fa-palette"></i>
                                <h4>No Artworks Yet</h4>
                                <p>Start by uploading your first Buddhist artwork</p>
                                <a href="upload-artwork.php" class="btn-primary-small">Upload Artwork</a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Quick Tips -->
    <div class="artist-tips">
        <h3><i class="fas fa-lightbulb"></i> Tips for Better Artwork Presentation</h3>
        <div class="tips-grid">
            <div class="tip">
                <i class="fas fa-camera"></i>
                <h4>High-Quality Photos</h4>
                <p>Use good lighting and clear images to showcase details</p>
            </div>
            <div class="tip">
                <i class="fas fa-book"></i>
                <h4>Detailed Descriptions</h4>
                <p>Explain the cultural significance and symbolism</p>
            </div>
            <div class="tip">
                <i class="fas fa-tags"></i>
                <h4>Proper Categorization</h4>
                <p>Choose the right category for better discovery</p>
            </div>
            <div class="tip">
                <i class="fas fa-hashtag"></i>
                <h4>Update Regularly</h4>
                <p>Add new artworks to keep your portfolio fresh</p>
            </div>
        </div>
    </div>
</div>

<style>
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

.my-artworks-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem 1rem;
}

/* Table Header with Search */
.table-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    padding-bottom: 0.8rem;
    border-bottom: 1px solid #f0f0f0;
    flex-wrap: wrap;
    gap: 1rem;
}

.table-header h1 {
    margin: 0;
    font-size: 1.5rem;
    color: #2c3e50;
}

.header-right {
    display: flex;
    align-items: center;
    gap: 1rem;
}

/* Search Bar */
.search-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}

.search-icon {
    position: absolute;
    left: 12px;
    color: #95a5a6;
    font-size: 0.9rem;
}

#searchInput {
    padding: 0.5rem 0.8rem 0.5rem 2.2rem;
    border: 1px solid #e0e0e0;
    border-radius: 25px;
    font-size: 0.85rem;
    width: 220px;
    transition: all 0.3s;
    outline: none;
}

#searchInput:focus {
    border-color: #e74c3c;
    box-shadow: 0 0 0 2px rgba(231, 76, 60, 0.1);
    width: 260px;
}

.table-info {
    font-size: 0.8rem;
    color: #7f8c8d;
    background: #f8f9fa;
    padding: 0.3rem 0.8rem;
    border-radius: 20px;
    white-space: nowrap;
}

/* Table Styles */
.data-table-container {
    background: white;
    border-radius: 12px;
    padding: 1rem;
    margin-bottom: 2rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
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
}

.data-table tr:hover {
    background: #fef5f4;
}

.category-badge {
    background: #e8f4fc;
    color: #3498db;
    padding: 0.2rem 0.6rem;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 600;
}

/* Action Buttons */
.action-buttons {
    display: flex;
    gap: 0.5rem;
}

.btn-action {
    width: 32px;
    height: 32px;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    transition: all 0.2s;
}

.btn-view {
    background: #e8f4fc;
    color: #3498db;
}

.btn-view:hover {
    background: #3498db;
    color: white;
}

.btn-edit {
    background: #fff3cd;
    color: #f39c12;
}

.btn-edit:hover {
    background: #f39c12;
    color: white;
}

.btn-delete {
    background: #f8d7da;
    color: #e74c3c;
}

.btn-delete:hover {
    background: #e74c3c;
    color: white;
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
    margin-bottom: 1rem;
}

.btn-primary-small {
    display: inline-block;
    background: #27ae60;
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 5px;
    text-decoration: none;
    font-size: 0.85rem;
}

/* Tips Section */
.artist-tips {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 1.5rem;
    margin-top: 1rem;
}

.artist-tips h3 {
    color: #2c3e50;
    margin-bottom: 1rem;
    font-size: 1.1rem;
    display: flex;
    align-items: center;
    gap: 8px;
}

.tips-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
}

.tip {
    text-align: center;
    padding: 1rem;
    background: white;
    border-radius: 8px;
}

.tip i {
    font-size: 1.5rem;
    color: #f1c40f;
    margin-bottom: 0.5rem;
}

.tip h4 {
    font-size: 0.85rem;
    margin-bottom: 0.3rem;
    color: #2c3e50;
}

.tip p {
    font-size: 0.7rem;
    color: #7f8c8d;
}

/* Responsive */
@media (max-width: 768px) {
    .table-header {
        flex-direction: column;
        align-items: stretch;
    }
    
    .header-right {
        justify-content: space-between;
    }
    
    #searchInput {
        width: 180px;
    }
    
    #searchInput:focus {
        width: 200px;
    }
    
    .tips-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 480px) {
    .header-right {
        flex-direction: column;
        align-items: stretch;
        gap: 0.5rem;
    }
    
    .search-wrapper {
        width: 100%;
    }
    
    #searchInput {
        width: 100%;
    }
    
    #searchInput:focus {
        width: 100%;
    }
    
    .tips-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
// AJAX Search functionality
document.getElementById('searchInput').addEventListener('keyup', function() {
    const searchTerm = this.value.toLowerCase().trim();
    const rows = document.querySelectorAll('#tableBody tr');
    let visibleCount = 0;
    
    rows.forEach(row => {
        // Skip the "no data" row
        if (row.classList.contains('no-data-row')) return;
        
        const titleElement = row.querySelector('td:nth-child(3) strong');
        if (titleElement) {
            const title = titleElement.innerText.toLowerCase();
            if (searchTerm === '' || title.includes(searchTerm)) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        }
    });
    
    // Update the count display
    const tableInfo = document.getElementById('tableInfo');
    if (searchTerm === '') {
        tableInfo.innerHTML = '<?php echo $total_artworks; ?> artwork<?php echo $total_artworks != 1 ? "s" : ""; ?>';
    } else {
        tableInfo.innerHTML = visibleCount + ' result' + (visibleCount != 1 ? 's' : '') + ' found';
    }
    
    // Show "no results" message if needed
    const noDataRow = document.querySelector('.no-data-row');
    if (visibleCount === 0 && !noDataRow) {
        // Add a temporary no results row
        const tbody = document.getElementById('tableBody');
        const existingNoResult = document.getElementById('noResultRow');
        if (!existingNoResult) {
            const noResultRow = document.createElement('tr');
            noResultRow.id = 'noResultRow';
            noResultRow.innerHTML = '<td colspan="8" class="no-data" style="text-align: center; padding: 2rem;"><i class="fas fa-search"></i><h4>No artworks found</h4><p>Try a different search term</p></td>';
            tbody.appendChild(noResultRow);
        }
    } else {
        const noResultRow = document.getElementById('noResultRow');
        if (noResultRow) noResultRow.remove();
    }
});

// Delete confirmation
document.querySelectorAll('.btn-delete').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
        var row = this.closest('tr');
        var titleCell = row.querySelector('td:nth-child(3) strong');
        var title = titleCell ? titleCell.innerText : 'this artwork';
        
        if (!confirm('Delete "' + title + '"? This action cannot be undone.')) {
            e.preventDefault();
            return false;
        }
    });
});

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
</script>

<?php require_once 'includes/footer.php'; ?>