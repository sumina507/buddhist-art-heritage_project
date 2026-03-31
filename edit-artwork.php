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
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $category = $_POST['category'];
    $materials = trim($_POST['materials']);
    $creation_time = trim($_POST['creation_time']);
    $symbolism = trim($_POST['symbolism']);
    
    // Handle image update
    $image_path = $artwork['image_path'];
    if (isset($_FILES['artwork_image']) && $_FILES['artwork_image']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $file_type = $_FILES['artwork_image']['type'];
        $file_size = $_FILES['artwork_image']['size'];
        
        if (in_array($file_type, $allowed_types)) {
            if ($file_size <= 5 * 1024 * 1024) { // 5MB limit
                // Delete old image if not default
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

$page_title = "Edit Artwork: " . htmlspecialchars($artwork['title']);
?>

<div class="container edit-container" style="max-width: 800px; margin: 0 auto; padding: 2rem;">
    <h1 style="color: #2c3e50; margin-bottom: 2rem;">
        <i class="fas fa-edit"></i> Edit Artwork
    </h1>
    
    <?php if (isset($_SESSION['message'])): ?>
        <div class="toast-notification toast-<?php echo $_SESSION['message_type']; ?>">
            <i class="fas <?php echo $_SESSION['message_type'] == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
            <span><?php echo $_SESSION['message']; ?></span>
        </div>
        <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
    <?php endif; ?>
    
    <form action="edit-artwork.php?id=<?php echo $artwork_id; ?>" method="POST" enctype="multipart/form-data" class="edit-form" style="background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.08);">
        
        <!-- Artwork Title -->
        <div class="form-group" style="margin-bottom: 1.2rem;">
            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Artwork Title *</label>
            <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($artwork['title']); ?>" required
                   style="width: 100%; padding: 0.8rem; border: 2px solid #e9ecef; border-radius: 8px;">
        </div>
        
        <!-- Description -->
        <div class="form-group" style="margin-bottom: 1.2rem;">
            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Description</label>
            <textarea name="description" rows="5" style="width: 100%; padding: 0.8rem; border: 2px solid #e9ecef; border-radius: 8px;"><?php echo htmlspecialchars($artwork['description']); ?></textarea>
        </div>
        
        <!-- Category
        <div class="form-group" style="margin-bottom: 1.2rem;">
            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Category *</label>
            <select name="category" required style="width: 100%; padding: 0.8rem; border: 2px solid #e9ecef; border-radius: 8px;">
                <option value="Thangka" <?php echo $artwork['category'] == 'Thangka' ? 'selected' : ''; ?>>Thangka</option>
                <option value="Sculpture" <?php echo $artwork['category'] == 'Sculpture' ? 'selected' : ''; ?>>Sculpture</option>
                <option value="Mandala" <?php echo $artwork['category'] == 'Mandala' ? 'selected' : ''; ?>>Mandala</option>
                <option value="Painting" <?php echo $artwork['category'] == 'Painting' ? 'selected' : ''; ?>>Painting</option>
                <option value="Other" <?php echo $artwork['category'] == 'Other' ? 'selected' : ''; ?>>Other</option>
            </select>
        </div> -->
        
        <!-- Materials -->
        <div class="form-group" style="margin-bottom: 1.2rem;">
            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Materials Used</label>
            <input type="text" name="materials" value="<?php echo htmlspecialchars($artwork['materials']); ?>" 
                   style="width: 100%; padding: 0.8rem; border: 2px solid #e9ecef; border-radius: 8px;">
        </div>
        
        <!-- Creation Time -->
        <div class="form-group" style="margin-bottom: 1.2rem;">
            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Creation Time</label>
            <input type="text" name="creation_time" value="<?php echo htmlspecialchars($artwork['creation_time']); ?>" 
                   placeholder="e.g., 3 months, 40 hours" style="width: 100%; padding: 0.8rem; border: 2px solid #e9ecef; border-radius: 8px;">
        </div>
        
        <!-- Symbolism -->
        <div class="form-group" style="margin-bottom: 1.2rem;">
            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Symbolism & Meaning</label>
            <textarea name="symbolism" rows="3" style="width: 100%; padding: 0.8rem; border: 2px solid #e9ecef; border-radius: 8px;"><?php echo htmlspecialchars($artwork['symbolism']); ?></textarea>
        </div>
        
        <!-- Current Image -->
        <div class="current-image" style="margin-bottom: 1.2rem;">
            <p style="font-weight: 600; margin-bottom: 0.5rem;">Current Image:</p>
            <img src="uploads/artworks/<?php echo htmlspecialchars($artwork['image_path']); ?>" 
                 alt="<?php echo htmlspecialchars($artwork['title']); ?>" 
                 style="max-width: 200px; border-radius: 8px; border: 1px solid #eee;">
        </div>
        
        <!-- New Image Upload -->
        <div class="form-group" style="margin-bottom: 1.2rem;">
            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Change Image (Optional)</label>
            <input type="file" name="artwork_image" accept="image/*" 
                   style="width: 100%; padding: 0.8rem; border: 2px dashed #e9ecef; border-radius: 8px;">
            <small style="color: #666;">Leave empty to keep current image. Max 5MB.</small>
        </div>
        
        <!-- Form Actions -->
        <div class="form-actions" style="display: flex; gap: 1rem; margin-top: 2rem;">
            <button type="submit" class="btn-primary" style="background: #27ae60; color: white; padding: 0.8rem 2rem; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">
                <i class="fas fa-save"></i> Update Artwork
            </button>
            <a href="my-artworks.php" class="btn-secondary" style="background: #e74c3c; color: white; padding: 0.8rem 2rem; border-radius: 8px; text-decoration: none; font-weight: 600;">
                <i class="fas fa-times"></i> Cancel
            </a>
        </div>
    </form>
</div>

<style>
.edit-container input:focus, .edit-container textarea:focus, .edit-container select:focus {
    border-color: #27ae60;
    outline: none;
    box-shadow: 0 0 0 3px rgba(39, 174, 96, 0.1);
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
</script>

<?php require_once 'includes/footer.php'; ?>