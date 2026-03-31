<?php
// commission-details.php - View single commission with full details
require_once 'includes/config.php';
require_once 'includes/navbar.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$commission_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Get commission details
if ($role == 'artist') {
    $artist_id = $_SESSION['artist_id'] ?? 0;
    $sql = "SELECT c.*, u.username as client_name, u.email as client_email
            FROM commissions c
            JOIN users u ON c.user_id = u.user_id
            WHERE c.commission_id = ? AND c.artist_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $commission_id, $artist_id);
} else {
    $sql = "SELECT c.*, u.username as artist_name, u.email as artist_email
            FROM commissions c
            JOIN artists a ON c.artist_id = a.artist_id
            JOIN users u ON a.user_id = u.user_id
            WHERE c.commission_id = ? AND c.user_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $commission_id, $user_id);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$commission = mysqli_fetch_assoc($result);

if (!$commission) {
    $_SESSION['message'] = "Commission not found.";
    header('Location: commissions.php');
    exit;
}

// Handle messages
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_message'])) {
    $message = trim($_POST['message']);
    if (!empty($message)) {
        $sql = "INSERT INTO commission_messages (commission_id, user_id, message, created_at) VALUES (?, ?, ?, NOW())";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "iis", $commission_id, $user_id, $message);
        mysqli_stmt_execute($stmt);
        $_SESSION['message'] = "Message sent!";
        header("Location: commission-details.php?id=$commission_id");
        exit;
    }
}

// ARTIST: Upload Preview
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_preview']) && $role == 'artist') {
    if (isset($_FILES['preview_image']) && $_FILES['preview_image']['error'] == 0) {
        $ext = pathinfo($_FILES['preview_image']['name'], PATHINFO_EXTENSION);
        $filename = 'preview_' . $commission_id . '_' . time() . '.' . $ext;
        $upload_path = 'uploads/previews/' . $filename;
        if (!file_exists('uploads/previews')) mkdir('uploads/previews', 0777, true);
        
        if (move_uploaded_file($_FILES['preview_image']['tmp_name'], $upload_path)) {
            $update = "UPDATE commissions SET preview_file = ?, preview_uploaded = 1 WHERE commission_id = ?";
            $stmt = mysqli_prepare($conn, $update);
            mysqli_stmt_bind_param($stmt, "si", $filename, $commission_id);
            mysqli_stmt_execute($stmt);
            $_SESSION['message'] = "Preview uploaded!";
            header("Location: commission-details.php?id=$commission_id");
            exit;
        }
    }
}

// CLIENT: Approve Preview
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['approve_preview']) && $role == 'user') {
    $update = "UPDATE commissions SET preview_approved = 1 WHERE commission_id = ?";
    $stmt = mysqli_prepare($conn, $update);
    mysqli_stmt_bind_param($stmt, "i", $commission_id);
    mysqli_stmt_execute($stmt);
    $_SESSION['message'] = "Preview approved! Artist can upload final file.";
    header("Location: commission-details.php?id=$commission_id");
    exit;
}

// ARTIST: Upload Final
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_final']) && $role == 'artist') {
    if (isset($_FILES['final_file']) && $_FILES['final_file']['error'] == 0) {
        $ext = pathinfo($_FILES['final_file']['name'], PATHINFO_EXTENSION);
        $filename = 'final_' . $commission_id . '_' . time() . '.' . $ext;
        $upload_path = 'uploads/final/' . $filename;
        if (!file_exists('uploads/final')) mkdir('uploads/final', 0777, true);
        
        if (move_uploaded_file($_FILES['final_file']['tmp_name'], $upload_path)) {
            $update = "UPDATE commissions SET final_file = ?, final_uploaded = 1 WHERE commission_id = ?";
            $stmt = mysqli_prepare($conn, $update);
            mysqli_stmt_bind_param($stmt, "si", $filename, $commission_id);
            mysqli_stmt_execute($stmt);
            $_SESSION['message'] = "Final file uploaded! Client can now pay.";
            header("Location: commission-details.php?id=$commission_id");
            exit;
        }
    }
}

// CLIENT: Pay
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['pay_now']) && $role == 'user') {
    $update = "UPDATE commissions SET payment_status = 'paid', paid_at = NOW() WHERE commission_id = ?";
    $stmt = mysqli_prepare($conn, $update);
    mysqli_stmt_bind_param($stmt, "i", $commission_id);
    mysqli_stmt_execute($stmt);
    $_SESSION['message'] = "Payment successful! Download your artwork.";
    header("Location: commission-details.php?id=$commission_id");
    exit;
}

// Get data
$has_preview = !empty($commission['preview_file']);
$preview_approved = $commission['preview_approved'] ?? 0;
$has_final = !empty($commission['final_file']);
$is_paid = ($commission['payment_status'] ?? 'pending') == 'paid';
$status = $commission['status'];

// Get messages
$msg_sql = "SELECT m.*, u.username FROM commission_messages m JOIN users u ON m.user_id = u.user_id WHERE m.commission_id = ? ORDER BY m.created_at ASC";
$msg_stmt = mysqli_prepare($conn, $msg_sql);
mysqli_stmt_bind_param($msg_stmt, "i", $commission_id);
mysqli_stmt_execute($msg_stmt);
$messages = mysqli_stmt_get_result($msg_stmt);
?>

<div class="container" style="padding: 20px; max-width: 900px; margin: 0 auto;">
    <h1>Commission #<?php echo $commission_id; ?></h1>
    <a href="commissions.php">← Back</a>
    
    <?php if (isset($_SESSION['message'])): ?>
        <div style="background: #d4edda; padding: 10px; margin: 10px 0; border-radius: 5px;">
            <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
        </div>
    <?php endif; ?>
    
    <!-- Status -->
    <div style="display: flex; gap: 10px; margin: 20px 0; flex-wrap: wrap;">
        <div style="background: white; padding: 8px 15px; border-radius: 5px;">📋 Status: <?php echo ucfirst($status); ?></div>
        <div style="background: white; padding: 8px 15px; border-radius: 5px;">🖼️ Preview: <?php echo $has_preview ? 'Uploaded' : 'Pending'; ?></div>
        <div style="background: white; padding: 8px 15px; border-radius: 5px;">✅ Approved: <?php echo $preview_approved ? 'Yes' : 'No'; ?></div>
        <div style="background: white; padding: 8px 15px; border-radius: 5px;">📁 Final: <?php echo $has_final ? 'Ready' : 'Pending'; ?></div>
        <div style="background: white; padding: 8px 15px; border-radius: 5px;">💰 Payment: <?php echo $is_paid ? 'Paid' : 'Pending'; ?></div>
    </div>
    
    <!-- Details -->
    <div style="background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px;">
        <h2><?php echo htmlspecialchars($commission['title']); ?></h2>
        <p><strong>Budget:</strong> NRs <?php echo number_format($commission['budget'] ?? 0, 2); ?></p>
        <p><strong>Description:</strong> <?php echo nl2br(htmlspecialchars($commission['description'])); ?></p>
    </div>
    
    <!-- Show Preview -->
    <?php if ($has_preview): ?>
    <div style="background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px;">
        <h3>Preview</h3>
        <img src="uploads/previews/<?php echo $commission['preview_file']; ?>" style="max-width: 100%; max-height: 300px;">
        <?php if ($role == 'user' && !$preview_approved): ?>
            <form method="POST" style="margin-top: 15px;">
                <button type="submit" name="approve_preview" style="background: #27ae60; color: white; padding: 10px 20px; border: none; border-radius: 5px;">✅ Approve This Artwork</button>
            </form>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <!-- Show Final File Preview -->
    <?php if ($has_final && $role == 'user' && !$is_paid): ?>
    <div style="background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px;">
        <h3>Final Artwork (Preview)</h3>
        <img src="uploads/final/<?php echo $commission['final_file']; ?>" style="max-width: 100%; max-height: 300px;">
    </div>
    <?php endif; ?>
    
    <!-- ARTIST ACTIONS -->
    <?php if ($role == 'artist'): ?>
        <?php if ($status == 'accepted' && !$has_preview): ?>
        <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 20px;">
            <h3>📸 Upload Preview</h3>
            <form method="POST" enctype="multipart/form-data">
                <input type="file" name="preview_image" accept="image/*" required style="margin-bottom: 10px;">
                <button type="submit" name="upload_preview" style="background: #3498db; color: white; border: none; padding: 10px 20px; border-radius: 5px;">Upload Preview</button>
            </form>
        </div>
        <?php endif; ?>
        
        <?php if ($preview_approved && !$has_final): ?>
        <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 20px;">
            <h3>📁 Upload Final File</h3>
            <form method="POST" enctype="multipart/form-data">
                <input type="file" name="final_file" accept="image/*,.pdf" required style="margin-bottom: 10px;">
                <button type="submit" name="upload_final" style="background: #27ae60; color: white; border: none; padding: 10px 20px; border-radius: 5px;">Upload Final File</button>
            </form>
        </div>
        <?php endif; ?>
        
        <?php if ($is_paid): ?>
        <div style="background: #d4edda; padding: 15px; border-radius: 10px; margin-bottom: 20px;">
            ✅ Payment received! You can download the file from your dashboard.
        </div>
        <?php endif; ?>
    <?php endif; ?>
    
    <!-- CLIENT ACTIONS -->
    <?php if ($role == 'user'): ?>
        <?php if ($has_final && !$is_paid): ?>
        <div style="background: white; padding: 20px; border-radius: 10px; text-align: center; border: 2px solid #27ae60; margin-bottom: 20px;">
            <h3>💰 Complete Payment</h3>
            <p style="font-size: 1.5rem; color: #e74c3c;">NRs <?php echo number_format($commission['budget'] ?? 0, 2); ?></p>
            <form method="POST">
                <button type="submit" name="pay_now" style="background: #27ae60; color: white; padding: 12px 30px; border: none; border-radius: 5px; font-size: 16px; cursor: pointer;">
                    💳 Complete Payment
                </button>
            </form>
            <p style="font-size: 12px; margin-top: 10px;">(Demo payment - completes instantly)</p>
        </div>
        <?php endif; ?>
        
        <?php if ($is_paid && $has_final): ?>
        <div style="background: #d4edda; padding: 20px; text-align: center; border-radius: 10px; margin-bottom: 20px;">
            <h3>🎉 Download Your Artwork</h3>
            <a href="uploads/final/<?php echo $commission['final_file']; ?>" download style="background: #27ae60; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; display: inline-block;">
                📥 Download Artwork
            </a>
        </div>
        <?php endif; ?>
    <?php endif; ?>
    
    <!-- Messages -->
    <div style="background: white; border-radius: 10px;">
        <div onclick="toggleMessages()" style="padding: 12px; background: #f8f9fa; cursor: pointer; border-radius: 10px;">
            💬 Messages (<?php echo mysqli_num_rows($messages); ?>)
        </div>
        <div id="messages" style="display: block; padding: 15px;">
            <form method="POST">
                <textarea name="message" rows="2" style="width: 100%; margin-bottom: 10px; padding: 8px;"></textarea>
                <button type="submit" name="send_message" style="background: #3498db; color: white; padding: 5px 15px; border: none;">Send</button>
            </form>
            <?php while($msg = mysqli_fetch_assoc($messages)): ?>
                <div style="margin: 10px 0; padding: 8px; background: <?php echo $msg['user_id'] == $user_id ? '#e3f2fd' : '#f5f5f5'; ?>; border-radius: 8px;">
                    <strong><?php echo htmlspecialchars($msg['username']); ?></strong> 
                    <small><?php echo date('M j, g:i A', strtotime($msg['created_at'])); ?></small>
                    <p style="margin: 5px 0 0;"><?php echo nl2br(htmlspecialchars($msg['message'])); ?></p>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
</div>

<script>
function toggleMessages() {
    var m = document.getElementById('messages');
    m.style.display = m.style.display === 'none' ? 'block' : 'none';
}
</script>

<?php require_once 'includes/footer.php'; ?>