<?php
// commission-request.php - User commission request form
require_once 'includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$page_title = "Request Commission";
require_once 'includes/navbar.php';

// Get artist_id from URL if passed
$pre_selected_artist = isset($_GET['artist_id']) ? intval($_GET['artist_id']) : 0;

// Get artist details if pre-selected
$artist_name = '';
if ($pre_selected_artist) {
    $artist_sql = "SELECT a.*, u.full_name, u.username 
                   FROM artists a 
                   JOIN users u ON a.user_id = u.user_id 
                   WHERE a.artist_id = ?";
    $stmt = mysqli_prepare($conn, $artist_sql);
    mysqli_stmt_bind_param($stmt, "i", $pre_selected_artist);
    mysqli_stmt_execute($stmt);
    $artist_result = mysqli_stmt_get_result($stmt);
    $artist_data = mysqli_fetch_assoc($artist_result);
    if ($artist_data) {
        $artist_name = $artist_data['full_name'] ?? $artist_data['username'];
    }
}

// Get today's date for min date
$today = date('Y-m-d');
?>

<div class="container" style="max-width: 600px; margin: 0 auto; padding: 20px;">
    <h1 style="color: #a78bfa; margin-bottom: 20px;">
        <i class="fas fa-handshake"></i> Request Custom Artwork
    </h1>
    
    <?php if (isset($_SESSION['errors'])): ?>
        <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
            <?php 
            foreach ($_SESSION['errors'] as $error) {
                echo "<p style='margin: 0;'>$error</p>";
            }
            unset($_SESSION['errors']);
            ?>
        </div>
    <?php endif; ?>
    
    <form action="process-commission.php" method="POST" style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.08);">
        
        <!-- Artist Selection -->
        <div class="form-group" style="margin-bottom: 18px;">
            <label style="display: block; margin-bottom: 6px; font-weight: 600;">Artist</label>
            <select name="artist_id" required style="width: 100%; padding: 10px; border: 2px solid #e9ecef; border-radius: 8px;" 
                    <?php echo $pre_selected_artist ? 'disabled' : ''; ?>>
                <option value="">-- Choose an Artist --</option>
                <?php
                $sql = "SELECT a.artist_id, u.username, u.full_name, a.specialization 
                        FROM artists a 
                        JOIN users u ON a.user_id = u.user_id 
                        WHERE a.status = 'approved'
                        ORDER BY u.full_name ASC";
                $result = mysqli_query($conn, $sql);
                
                if ($result && mysqli_num_rows($result) > 0) {
                    while($artist = mysqli_fetch_assoc($result)) {
                        $artist_name_display = $artist['full_name'] ?: $artist['username'];
                        $specialization = $artist['specialization'] ? " ({$artist['specialization']})" : '';
                        $selected = ($pre_selected_artist == $artist['artist_id']) ? 'selected' : '';
                        echo "<option value='{$artist['artist_id']}' $selected>" . 
                             htmlspecialchars($artist_name_display . $specialization) . 
                             "</option>";
                    }
                } else {
                    echo "<option value=''>No artists available</option>";
                }
                ?>
            </select>
            
            <?php if ($pre_selected_artist): ?>
                <input type="hidden" name="artist_id" value="<?php echo $pre_selected_artist; ?>">
                <small style="color: #27ae60; margin-top: 5px; display: block;">
                    <i class="fas fa-check-circle"></i> Artist: <strong><?php echo htmlspecialchars($artist_name); ?></strong>
                </small>
            <?php endif; ?>
        </div>
        
        <!-- Artwork Title -->
        <div class="form-group" style="margin-bottom: 18px;">
            <label style="display: block; margin-bottom: 6px; font-weight: 600;">Artwork Title </label>
            <input type="text" name="title" required placeholder="e.g., Buddha Mandala" 
                   style="width: 100%; padding: 10px; border: 2px solid #e9ecef; border-radius: 8px;">
        </div>
        
        <!-- Description -->
        <div class="form-group" style="margin-bottom: 18px;">
            <label style="display: block; margin-bottom: 6px; font-weight: 600;">Description </label>
            <textarea name="description" rows="4" required 
                      placeholder="Describe your custom artwork request (size, style, colors, etc.)" 
                      style="width: 100%; padding: 10px; border: 2px solid #e9ecef; border-radius: 8px;"></textarea>
        </div>
        
        <!-- Budget & Deadline Row -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 18px;">
            <div class="form-group">
                <label style="display: block; margin-bottom: 6px; font-weight: 600;">Budget (NRs)</label>
                <input type="number" name="budget" placeholder="Amount" 
                       style="width: 100%; padding: 10px; border: 2px solid #e9ecef; border-radius: 8px;">
            </div>
            
            <div class="form-group">
                <label style="display: block; margin-bottom: 6px; font-weight: 600;">Deadline</label>
                <input type="date" name="deadline" min="<?php echo $today; ?>" 
                       style="width: 100%; padding: 10px; border: 2px solid #e9ecef; border-radius: 8px;">
            </div>
        </div>
        
        <!-- Payment Method -->
        <div class="form-group" style="margin-bottom: 18px;">
            <label style="display: block; margin-bottom: 6px; font-weight: 600;">Payment Method</label>
            <select name="payment_method" style="width: 100%; padding: 10px; border: 2px solid #e9ecef; border-radius: 8px;">
                <option value="eSewa" selected>eSewa (Online)</option>
                <option value="To be discussed">Discuss with Artist</option>
            </select>
        </div>
        
        <!-- Note (compact) -->
        <div style="background: #f8f9fa; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 0.85rem;">
            <i class="fas fa-info-circle" style="color: #a78bfa;"></i>
            <span style="color: #555;"> Budget is negotiable. Artist will review and discuss details through messages.</span>
        </div>
        
        <!-- Submit Button -->
        <button type="submit" style="background: #a78bfa; color: white; border: none; padding: 12px; border-radius: 8px; cursor: pointer; font-size: 1rem; font-weight: 600; width: 100%;">
            <i class="fas fa-paper-plane"></i> Submit Request
        </button>
    </form>
</div>

<style>
.form-group input:focus, .form-group select:focus, .form-group textarea:focus {
    border-color: #a78bfa;
    outline: none;
    box-shadow: 0 0 0 3px rgba(167, 139, 250, 0.1);
}
</style>

<?php require_once 'includes/footer.php'; ?>