<?php
// upload-artwork.php
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
    $category = $_POST['category'];
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
        $sql = "INSERT INTO artworks (artist_id, title, description, category, image_path, materials, creation_time, symbolism) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "isssssss", $artist_id, $title, $description, $category, $image_path, $materials, $creation_time, $symbolism);
        
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

<div class="container upload-container">
    <h1><i class="fas fa-upload"></i> Upload Artwork</h1>
    
    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['message_type']; ?>">
            <?php 
            echo $_SESSION['message'];
            unset($_SESSION['message']);
            unset($_SESSION['message_type']);
            ?>
        </div>
    <?php endif; ?>
    
    <form action="upload-artwork.php" method="POST" enctype="multipart/form-data" class="upload-form">
        <div class="form-row">
            <div class="form-group">
                <label for="title"><i class="fas fa-heading"></i> Artwork Title</label>
                <input type="text" id="title" name="title" required placeholder="e.g., Buddha Mandala">
            </div>
        </div>
        
        <div class="form-group">
            <label for="description"><i class="fas fa-align-left"></i> Description</label>
            <textarea id="description" name="description" rows="4" required placeholder="Describe your artwork, its significance, and any background information..."></textarea>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="materials"><i class="fas fa-paint-brush"></i> Materials Used</label>
                <input type="text" id="materials" name="materials" placeholder="e.g., Natural pigments, gold leaf, silk canvas">
            </div>
            
            <div class="form-group">
                <label for="creation_time"><i class="fas fa-clock"></i> Creation Time</label>
                <input type="text" id="creation_time" name="creation_time" placeholder="e.g., 3 months, 40 hours">
            </div>
            
            <div class="form-group">
                <label for="symbolism"><i class="fas fa-yin-yang"></i> Symbolism</label>
                <input type="text" id="symbolism" name="symbolism" placeholder="e.g., Represents enlightenment, compassion">
            </div>
        </div>
        
        <div class="form-group file-upload">
            <label for="artwork_image"><i class="fas fa-image"></i> Artwork Image</label>
            <input type="file" id="artwork_image" name="artwork_image" accept="image/*" required onchange="previewImage(event)">
            
            <div class="image-preview" id="imagePreview">
                <div class="preview-text">No image selected</div>
            </div>
            <small>Max size: 5MB. Formats: JPG, PNG, GIF</small>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn-primary"><i class="fas fa-upload"></i> Upload Artwork</button>
            <a href="my-artworks.php" class="btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<style>
/* Container */
.upload-container {
    padding: 2rem 1rem;
    max-width: 850px;
    margin: 0 auto;
}

.upload-container h1 {
    color: #a78bfa;
    margin-bottom: 2rem;
    font-size: 1.8rem;
    display: flex;
    align-items: center;
    gap: 12px;
    justify-content: center;
}

/* Form Card */
.upload-form {
    background: #fff;
    padding: 2rem 2rem;
    border-radius: 15px;
    box-shadow: 0 6px 20px rgba(0,0,0,0.1);
    transition: transform 0.3s;
}

.upload-form:hover {
    transform: translateY(-2px);
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}

.form-group {
    margin-bottom: 1.5rem;
    position: relative;
}

.form-group label {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: black;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 2px solid #e9ecef;
    border-radius: 12px;
    font-size: 0.95rem;
    transition: all 0.3s ease;
    box-shadow: inset 0 2px 5px rgba(0,0,0,0.03);
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    border-color: #e74c3c;
    outline: none;
    box-shadow: 0 0 8px rgba(231,76,60,0.2);
}

textarea {
    min-height: 120px;
    resize: vertical;
}

/* File Upload */
.file-upload input[type="file"] {
    cursor: pointer;
}

.image-preview {
    margin-top: 1rem;
    width: 100%;
    max-width: 250px;
    height: 160px;
    border: 2px dashed #e9ecef;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    transition: border-color 0.3s, background 0.3s;
}

.image-preview:hover {
    border-color: #e74c3c;
    background: #fef6f6;
}

.image-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 10px;
}

.preview-text {
    color: #aaa;
    text-align: center;
    font-size: 0.85rem;
}

/* Buttons */
.form-actions {
    display: flex;
    gap: 1rem;
    margin-top: 2rem;
    justify-content: center;
}

.btn-primary, .btn-secondary {
    padding: 0.8rem 2rem;
    border-radius: 12px;
    text-decoration: none;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
}

.btn-primary {
    background: #a78bfa;
    color: white;
    border: none;
    cursor: pointer;
}

.btn-primary:hover {
    background: #a78bfa;
    transform: translateY(-2px);
}

.btn-secondary {
    background: red;
    color: white;
    border: 2px solid #ddd;
}

.btn-secondary:hover {
    background: #e9ecef;
}

/* Responsive */
@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .upload-form {
        padding: 1.5rem;
    }
}
</style>

<script>
function previewImage(event) {
    const preview = document.getElementById('imagePreview');
    const file = event.target.files[0];
    
    if (file) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            preview.innerHTML = `<img src="${e.target.result}" alt="Artwork Preview">`;
        }
        
        reader.readAsDataURL(file);
    } else {
        preview.innerHTML = '<div class="preview-text">No image selected</div>';
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>