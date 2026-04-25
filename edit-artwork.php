<?php
// edit-artwork.php
require_once 'includes/config.php';
require_once 'includes/navbar.php';

// Check if user is logged in and is an artist
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'artist') {
    header('Location: login.php');
    exit;
}

$artwork_id = $_GET['id'] ?? 0;
$artist_id = $_SESSION['artist_id'] ?? 0;

// Get artwork details (verify ownership)
$sql = "SELECT * FROM artworks WHERE artwork_id = ? AND artist_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $artwork_id, $artist_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$artwork = mysqli_fetch_assoc($result);

if (!$artwork) {
    $_SESSION['message'] = "Artwork not found or access denied!";
    $_SESSION['message_type'] = 'error';
    header('Location: my-artworks.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = $_POST['category'] ?? 'Other';
    $materials = trim($_POST['materials'] ?? '');
    $creation_time = trim($_POST['creation_time'] ?? '');
    $symbolism = trim($_POST['symbolism'] ?? '');
    
    // Handle image update
    $image_path = $artwork['image_path'];
    if (isset($_FILES['artwork_image']) && $_FILES['artwork_image']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $file_type = $_FILES['artwork_image']['type'];
        $file_size = $_FILES['artwork_image']['size'];
        
        if (in_array($file_type, $allowed_types)) {
            if ($file_size <= 5 * 1024 * 1024) {
                if ($image_path != 'default.jpg' && file_exists(ARTWORK_UPLOAD_PATH . $image_path)) {
                    unlink(ARTWORK_UPLOAD_PATH . $image_path);
                }
                
                $file_extension = pathinfo($_FILES['artwork_image']['name'], PATHINFO_EXTENSION);
                $new_filename = uniqid() . '_' . time() . '.' . $file_extension;
                $upload_path = ARTWORK_UPLOAD_PATH . $new_filename;
                
                if (move_uploaded_file($_FILES['artwork_image']['tmp_name'], $upload_path)) {
                    $image_path = $new_filename;
                } else {
                    $_SESSION['message'] = "Error uploading new image.";
                    $_SESSION['message_type'] = 'error';
                }
            } else {
                $_SESSION['message'] = "File too large. Max 5MB.";
                $_SESSION['message_type'] = 'error';
            }
        } else {
            $_SESSION['message'] = "Invalid file type. Only JPG, PNG, GIF allowed.";
            $_SESSION['message_type'] = 'error';
        }
    }
    
    // Update artwork
    $sql = "UPDATE artworks SET 
            title = ?, 
            description = ?, 
            category = ?, 
            image_path = ?, 
            materials = ?, 
            creation_time = ?, 
            symbolism = ?, 
            updated_at = NOW() 
            WHERE artwork_id = ? AND artist_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sssssssii", $title, $description, $category, $image_path, 
                          $materials, $creation_time, $symbolism, $artwork_id, $artist_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['message'] = "Artwork updated successfully!";
        $_SESSION['message_type'] = 'success';
        header('Location: my-artworks.php');
        exit;
    } else {
        $_SESSION['message'] = "Error updating artwork: " . mysqli_error($conn);
        $_SESSION['message_type'] = 'error';
    }
}

// Helper function to safely escape values
function safeHtml($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

$page_title = "Edit Artwork: " . safeHtml($artwork['title']);
?>

<style>
body {
    background: linear-gradient(135deg, #f9f7f1 0%, #f5f5f0 100%);
    min-height: 100vh;
}

.auth-container {
    min-height: 85vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.auth-card {
    background: #ffffff;
    padding: 2.5rem;
    border-radius: 20px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    max-width: 800px;
    width: 100%;
    border: 1px solid #e9ecef;
}

.auth-header {
    text-align: center;
    margin-bottom: 2rem;
}

.auth-header h1 {
    color: #2c3e50;
    font-size: 2rem;
    margin-bottom: 0.5rem;
}

.auth-header h1 i {
    color: #e74c3c;
    margin-right: 10px;
}

.auth-header p {
    color: #6c757d;
    font-size: 0.9rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

label {
    display: block;
    margin-bottom: 8px;
    font-size: 0.9rem;
    color: #2c3e50;
    font-weight: 600;
}

label i {
    color: #e74c3c;
    margin-right: 5px;
}

input, textarea, select {
    width: 100%;
    padding: 0.85rem 1rem;
    border: 1.5px solid #e9ecef;
    border-radius: 12px;
    background: #ffffff;
    color: #2c3e50;
    font-size: 1rem;
    transition: all 0.3s;
    font-family: inherit;
}

textarea {
    resize: vertical;
    min-height: 100px;
}

input:focus, textarea:focus, select:focus {
    border-color: #27ae60;
    background: #ffffff;
    box-shadow: 0 0 0 3px rgba(39, 174, 96, 0.1);
    outline: none;
}

.current-image {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 12px;
    margin-bottom: 1.5rem;
    border: 1px solid #e9ecef;
}

.current-image p {
    color: #2c3e50;
    font-weight: 600;
    margin-bottom: 0.8rem;
}

.current-image p i {
    color: #e74c3c;
}

.current-image img {
    max-width: 200px;
    border-radius: 12px;
    border: 2px solid #e9ecef;
}

.file-hint {
    font-size: 0.7rem;
    color: #6c757d;
    margin-top: 5px;
    display: block;
}

.btn-primary {
    width: 100%;
    padding: 0.9rem;
    text-align: center;
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 8px;
    border: none;
    border-radius: 12px;
    background: linear-gradient(135deg, #e74c3c, #c0392b);
    color: white;
    font-weight: 600;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(39, 174, 96, 0.3);
}

.button-group {
    display: flex;
    gap: 1rem;
    margin-top: 1.5rem;
}

.toast-notification {
    position: fixed;
    bottom: 30px;
    right: 30px;
    background: white;
    padding: 12px 20px;
    border-radius: 12px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.15);
    display: flex;
    align-items: center;
    gap: 10px;
    z-index: 9999;
    font-size: 0.9rem;
    border-left: 4px solid;
    transition: opacity 0.3s ease, transform 0.3s ease;
}

.toast-success { 
    border-left-color: #27ae60; 
    color: #2c3e50;
}
.toast-success i { color: #27ae60; }
.toast-error { 
    border-left-color: #e74c3c; 
    color: #2c3e50;
}
.toast-error i { color: #e74c3c; }



@media (max-width: 600px) {
    .auth-card {
        padding: 1.5rem;
    }
    .button-group {
        flex-direction: column;
    }
    .current-image img {
        max-width: 150px;
    }
}
</style>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <h1><i class="fas fa-edit"></i> Edit Artwork</h1>
            <p>Update your sacred masterpiece details</p>
        </div>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="toast-notification toast-<?php echo safeHtml($_SESSION['message_type']); ?>">
                <i class="fas <?php echo ($_SESSION['message_type'] ?? '') == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                <span><?php echo safeHtml($_SESSION['message']); ?></span>
            </div>
            <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
        <?php endif; ?>

        <form action="edit-artwork.php?id=<?php echo $artwork_id; ?>" method="POST" enctype="multipart/form-data">
            
            <div class="form-group">
                <label><i class="fas fa-heading"></i> Artwork Title *</label>
                <input type="text" name="title" value="<?php echo safeHtml($artwork['title']); ?>" required>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-align-left"></i> Description</label>
                <textarea name="description" rows="5" placeholder="Describe the artwork, its meaning, and significance..."><?php echo safeHtml($artwork['description']); ?></textarea>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-tag"></i> Category *</label>
                <select name="category" required>
                    <option value="Thangka" <?php echo (($artwork['category'] ?? '') == 'Thangka') ? 'selected' : ''; ?>>Thangka</option>
                    <option value="Sculpture" <?php echo (($artwork['category'] ?? '') == 'Sculpture') ? 'selected' : ''; ?>>Sculpture</option>
                    <option value="Mandala" <?php echo (($artwork['category'] ?? '') == 'Mandala') ? 'selected' : ''; ?>>Mandala</option>
                    <option value="Painting" <?php echo (($artwork['category'] ?? '') == 'Painting') ? 'selected' : ''; ?>>Painting</option>
                    <option value="Other" <?php echo (($artwork['category'] ?? '') == 'Other') ? 'selected' : ''; ?>>Other</option>
                </select>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-paintbrush"></i> Materials Used</label>
                <input type="text" name="materials" value="<?php echo safeHtml($artwork['materials']); ?>" placeholder="e.g., Mineral pigments, Gold leaf, Cotton canvas">
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-hourglass-half"></i> Creation Time</label>
                <input type="text" name="creation_time" value="<?php echo safeHtml($artwork['creation_time']); ?>" 
                       placeholder="e.g., 3 months, 40 hours">
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-feather-alt"></i> Symbolism & Meaning</label>
                <textarea name="symbolism" rows="3" placeholder="Explain the spiritual meaning and symbolism..."><?php echo safeHtml($artwork['symbolism']); ?></textarea>
            </div>
            
            <div class="current-image">
                <p><i class="fas fa-image"></i> Current Image:</p>
                <img src="uploads/artworks/<?php echo safeHtml($artwork['image_path']); ?>" 
                     alt="<?php echo safeHtml($artwork['title']); ?>">
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-upload"></i> Change Image (Optional)</label>
                <input type="file" name="artwork_image" accept="image/*">
                <small class="file-hint"><i class="fas fa-info-circle"></i> Leave empty to keep current image. Max 5MB. JPG, PNG, GIF only.</small>
            </div>
            
            <div class="button-group">
                <button type="submit" class="btn-primary">
                     Update Artwork
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Auto hide toast notification after 3 seconds
document.addEventListener('DOMContentLoaded', function() {
    var toast = document.querySelector('.toast-notification');
    if (toast) {
        setTimeout(function() {
            toast.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(100%)';
            setTimeout(function() {
                if (toast && toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 300);
        }, 3000);
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>