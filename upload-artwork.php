<?php
// upload-artwork.php - CLEAN VERSION (no cancel button)
require_once 'includes/config.php';
require_once 'includes/navbar.php';

// Check if user is logged in and is an artist
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'artist') {
    header('Location: login.php');
    exit;
}

$page_title = "Upload Artwork";
$artist_id = $_SESSION['artist_id'] ?? 0;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $materials = trim($_POST['materials']);
    $creation_time = trim($_POST['creation_time']);
    $symbolism = trim($_POST['symbolism']);
    
    $image_path = 'default.jpg';
    if (isset($_FILES['artwork_image']) && $_FILES['artwork_image']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $file_type = $_FILES['artwork_image']['type'];
        
        if (in_array($file_type, $allowed_types)) {
            $file_extension = pathinfo($_FILES['artwork_image']['name'], PATHINFO_EXTENSION);
            $new_filename = uniqid() . '_' . time() . '.' . $file_extension;
            $upload_path = ARTWORK_UPLOAD_PATH . $new_filename;
            
            if (move_uploaded_file($_FILES['artwork_image']['tmp_name'], $upload_path)) {
                $image_path = $new_filename;
            } else {
                $_SESSION['message'] = "Error uploading image.";
                $_SESSION['message_type'] = 'error';
            }
        } else {
            $_SESSION['message'] = "Invalid file type. Only JPG, PNG, GIF allowed.";
            $_SESSION['message_type'] = 'error';
        }
    }
    
    if (!isset($_SESSION['message'])) {
        $sql = "INSERT INTO artworks (artist_id, title, description, image_path, materials, creation_time, symbolism, category, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'Thangka', NOW())";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "issssss", $artist_id, $title, $description, $image_path, $materials, $creation_time, $symbolism);
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['message'] = "Artwork uploaded successfully!";
            $_SESSION['message_type'] = 'success';
            header('Location: my-artworks.php');
            exit;
        } else {
            $_SESSION['message'] = "Error uploading artwork: " . mysqli_error($conn);
            $_SESSION['message_type'] = 'error';
        }
    }
}
?>

<style>
body {
    background: #f5f5f0;
    font-family: 'Inter', sans-serif;
}

/* Container */
.auth-container {
    min-height: 85vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem;
}

/* Card */
.auth-card {
    background: white;
    padding: 2.5rem;
    border-radius: 24px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.08);
    max-width: 750px;
    width: 100%;
    border: 1px solid #e9ecef;
}

/* Header */
.auth-header {
    text-align: center;
    margin-bottom: 2rem;
}

.auth-header h1 {
    color: #2c3e50;
    font-size: 1.8rem;
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

/* Form */
.form-group {
    margin-bottom: 1.2rem;
}

label {
    display: block;
    margin-bottom: 6px;
    font-size: 0.85rem;
    color: #2c3e50;
    font-weight: 600;
}

label i {
    color: #e74c3c;
    margin-right: 6px;
}

/* Inputs */
input, textarea, select {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1.5px solid #e9ecef;
    border-radius: 12px;
    background: white;
    color: #2c3e50;
    font-size: 0.9rem;
    transition: all 0.3s;
    font-family: inherit;
}

textarea {
    resize: vertical;
    min-height: 100px;
}

input:focus, textarea:focus, select:focus {
    border-color: #e74c3c;
    background: white;
    box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.1);
    outline: none;
}

input::placeholder, textarea::placeholder {
    color: #adb5bd;
}

/* Two columns layout */
.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1.2rem;
}

/* File upload area */
.file-upload-area {
    border: 2px dashed #e9ecef;
    border-radius: 12px;
    padding: 1rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s;
    background: #f8f9fa;
}

.file-upload-area:hover {
    border-color: #e74c3c;
    background: #fef5f4;
}

.file-upload-area i {
    color: #e74c3c;
    font-size: 1.8rem;
}

.file-upload-area p {
    color: #6c757d;
    margin-top: 5px;
    font-size: 0.8rem;
}

.preview-box {
    margin-top: 1rem;
    width: 120px;
    height: 120px;
    border-radius: 12px;
    overflow: hidden;
    background: #f8f9fa;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid #e9ecef;
}

.preview-box img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.file-hint {
    font-size: 0.7rem;
    color: #6c757d;
    margin-top: 5px;
}

/* Error messages */
.error-message {
    color: #e74c3c;
    font-size: 0.7rem;
    margin-top: 5px;
    display: none;
}

/* Button */
.btn-primary {
    width: 100%;
    padding: 0.85rem;
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
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-primary:hover {

    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(231, 76, 60, 0.3);
}

/* Toast notifications */
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
    animation: slideInRight 0.3s ease;
    border-left: 4px solid;
    color: #2c3e50;
}

.toast-success { border-left-color: #27ae60; }
.toast-success i { color: #27ae60; }
.toast-error { border-left-color: #e74c3c; }
.toast-error i { color: #e74c3c; }

@keyframes slideInRight {
    from { opacity: 0; transform: translateX(100%); }
    to { opacity: 1; transform: translateX(0); }
}

/* Responsive */
@media (max-width: 600px) {
    .auth-card {
        padding: 1.5rem;
    }
    .form-row {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
}
</style>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <h1><i class="fas fa-upload"></i> Upload Artwork</h1>
            <p>Share your sacred Thangka masterpiece</p>
        </div>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="toast-notification toast-<?php echo $_SESSION['message_type']; ?>">
                <i class="fas <?php echo $_SESSION['message_type'] == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                <span><?php echo $_SESSION['message']; ?></span>
            </div>
            <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
        <?php endif; ?>

        <form id="uploadForm" action="upload-artwork.php" method="POST" enctype="multipart/form-data">
            
            <!-- Title -->
            <div class="form-group">
                <label><i class="fas fa-heading"></i> Title *</label>
                <input type="text" id="title" name="title" placeholder="e.g., Buddha Mandala">
                <div id="titleError" class="error-message">Title is required</div>
            </div>
            
            <!-- Description -->
            <div class="form-group">
                <label><i class="fas fa-align-left"></i> Description *</label>
                <textarea id="description" name="description" placeholder="Describe your artwork..."></textarea>
                <div id="descError" class="error-message">Description is required (minimum 20 characters)</div>
            </div>
            
            <!-- Two columns for materials & creation time -->
            <div class="form-row">
                <div class="form-group">
                    <label><i class="fas fa-paintbrush"></i> Materials</label>
                    <input type="text" name="materials" placeholder="e.g., Natural pigments, gold leaf">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-hourglass-half"></i> Creation Time</label>
                    <input type="text" name="creation_time" placeholder="e.g., 3 months">
                </div>
            </div>
            
            <!-- Symbolism -->
            <div class="form-group">
                <label><i class="fas fa-feather-alt"></i> Symbolism</label>
                <input type="text" name="symbolism" placeholder="e.g., Represents enlightenment">
            </div>
            
            <!-- Image Upload -->
            <div class="form-group">
                <label><i class="fas fa-image"></i> Artwork Image *</label>
                <div class="file-upload-area" onclick="document.getElementById('artwork_image').click()">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <p>Click to select image</p>
                    <input type="file" id="artwork_image" name="artwork_image" accept="image/*" style="display: none;" onchange="previewImage(event)">
                </div>
                <div id="imagePreview" class="preview-box">
                    <span style="color: #adb5bd; font-size: 0.7rem;">Preview</span>
                </div>
                <div class="file-hint">
                    <i class="fas fa-info-circle"></i> Max 5MB. JPG, PNG, GIF only
                </div>
                <div id="imageError" class="error-message">Please select an image</div>
            </div>
            
            <!-- Submit Button -->
            <div class="button-group">
                <button type="submit" class="btn-primary">
                     Upload Artwork
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Image preview
function previewImage(event) {
    const preview = document.getElementById('imagePreview');
    const file = event.target.files[0];
    
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
        }
        reader.readAsDataURL(file);
        document.getElementById('imageError').style.display = 'none';
    } else {
        preview.innerHTML = '<span style="color: #adb5bd; font-size: 0.7rem;">Preview</span>';
    }
}

// Form validation
document.getElementById('uploadForm').addEventListener('submit', function(e) {
    let isValid = true;
    
    // Title validation
    const title = document.getElementById('title').value.trim();
    if (title === "") {
        document.getElementById('titleError').style.display = 'block';
        isValid = false;
    } else {
        document.getElementById('titleError').style.display = 'none';
    }
    
    // Description validation
    const description = document.getElementById('description').value.trim();
    if (description === "") {
        document.getElementById('descError').innerHTML = "Description is required";
        document.getElementById('descError').style.display = 'block';
        isValid = false;
    } else if (description.length < 20) {
        document.getElementById('descError').innerHTML = "Description must be at least 20 characters";
        document.getElementById('descError').style.display = 'block';
        isValid = false;
    } else {
        document.getElementById('descError').style.display = 'none';
    }
    
    // Image validation
    const image = document.getElementById('artwork_image').files[0];
    if (!image) {
        document.getElementById('imageError').style.display = 'block';
        isValid = false;
    } else {
        document.getElementById('imageError').style.display = 'none';
    }
    
    if (!isValid) {
        e.preventDefault();
    }
});

// Clear errors on typing
document.getElementById('title').addEventListener('input', function() {
    if (this.value.trim() !== "") {
        document.getElementById('titleError').style.display = 'none';
    }
});

document.getElementById('description').addEventListener('input', function() {
    if (this.value.trim().length >= 20) {
        document.getElementById('descError').style.display = 'none';
    }
});

// Auto hide toast
setTimeout(function() {
    var toast = document.querySelector('.toast-notification');
    if (toast) {
        toast.style.opacity = '0';
        toast.style.transition = 'opacity 0.3s';
        setTimeout(function() { toast.remove(); }, 300);
    }
}, 3000);
</script>

<?php require_once 'includes/footer.php'; ?>