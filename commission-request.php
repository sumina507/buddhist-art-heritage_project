<?php
// commission-request.php - CLEAN & ATTRACTIVE VERSION (with dropdown size)
require_once 'includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$page_title = "Request Custom Artwork";
require_once 'includes/navbar.php';

// Get parameters
$artwork_id = isset($_GET['artwork_id']) ? intval($_GET['artwork_id']) : 0;
$pre_selected_artist = isset($_GET['artist_id']) ? intval($_GET['artist_id']) : 0;
$pre_filled_title = isset($_GET['title']) ? urldecode($_GET['title']) : '';

// Get artwork details for reference
$artwork_details = null;
if ($artwork_id > 0) {
    $art_sql = "SELECT a.*, u.username, u.full_name as artist_name 
                FROM artworks a
                JOIN artists ar ON a.artist_id = ar.artist_id
                JOIN users u ON ar.user_id = u.user_id
                WHERE a.artwork_id = ?";
    $stmt = mysqli_prepare($conn, $art_sql);
    mysqli_stmt_bind_param($stmt, "i", $artwork_id);
    mysqli_stmt_execute($stmt);
    $art_result = mysqli_stmt_get_result($stmt);
    $artwork_details = mysqli_fetch_assoc($art_result);
}

// Get artist name if pre-selected
$artist_name = '';
if ($pre_selected_artist) {
    $artist_sql = "SELECT a.*, u.full_name, u.username 
                   FROM artists a 
                   JOIN users u ON a.user_id = u.user_id 
                   WHERE a.artist_id = ? AND a.status = 'approved'";
    $stmt = mysqli_prepare($conn, $artist_sql);
    mysqli_stmt_bind_param($stmt, "i", $pre_selected_artist);
    mysqli_stmt_execute($stmt);
    $artist_result = mysqli_stmt_get_result($stmt);
    $artist_data = mysqli_fetch_assoc($artist_result);
    if ($artist_data) {
        $artist_name = $artist_data['full_name'] ?? $artist_data['username'];
    }
}

$today = date('Y-m-d');
$min_deadline = date('Y-m-d', strtotime('+7 days'));

$size_prices = [
    'Small (A4 - 8x12")' => 5000,
    'Medium (A3 - 12x18")' => 10000,
    'Large (18x24")' => 20000,
    'Extra Large (24x36")' => 35000,
    'Custom Size' => 0
];
?>

<style>
body {
    background: #f5f5f0;
    font-family: 'Inter', sans-serif;
}

.commission-container {
    max-width: 650px;
    margin: 0 auto;
    padding: 2rem 1rem;
}

.back-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: #e74c3c;
    text-decoration: none;
    margin-bottom: 1.5rem;
    font-size: 0.9rem;
}

.back-link:hover {
    text-decoration: underline;
}

/* Main Card */
.commission-card {
    background: white;
    border-radius: 20px;
    padding: 1.8rem;
    box-shadow: 0 5px 20px rgba(0,0,0,0.05);
    border: 1px solid #e9ecef;
}

.commission-header {
    text-align: center;
    margin-bottom: 1.5rem;
}

.commission-header h1 {
    color: #2c3e50;
    font-size: 1.6rem;
    margin-bottom: 0.3rem;
}

.commission-header h1 i {
    color: #e74c3c;
    margin-right: 8px;
}

.commission-header p {
    color: #6c757d;
    font-size: 0.85rem;
}

/* Reference Artwork */
.reference-artwork {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 0.8rem;
    margin-bottom: 1.2rem;
    display: flex;
    gap: 0.8rem;
    align-items: center;
    border-left: 3px solid #e74c3c;
}

.reference-artwork img {
    width: 50px;
    height: 50px;
    border-radius: 8px;
    object-fit: cover;
}

.reference-artwork h4 {
    color: #2c3e50;
    margin-bottom: 0.2rem;
    font-size: 0.85rem;
}

.reference-artwork p {
    color: #6c757d;
    font-size: 0.75rem;
}

/* Form */
.form-group {
    margin-bottom: 1rem;
}

label {
    display: block;
    margin-bottom: 5px;
    font-size: 0.85rem;
    color: #2c3e50;
    font-weight: 600;
}

label i {
    color: #e74c3c;
    margin-right: 6px;
}

input, select, textarea {
    width: 100%;
    padding: 0.7rem 1rem;
    border: 1.5px solid #e9ecef;
    border-radius: 10px;
    background: white;
    color: #2c3e50;
    font-size: 0.9rem;
    transition: all 0.3s;
    font-family: inherit;
}

input:focus, select:focus, textarea:focus {
    border-color: #e74c3c;
    outline: none;
    box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.1);
}

input[readonly] {
    background: #f8f9fa;
    cursor: not-allowed;
}

/* Price Display */
.price-display {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 0.8rem;
    text-align: center;
    margin-top: 0.5rem;
    border: 1px solid #e9ecef;
}

.price {
    font-size: 1.3rem;
    font-weight: bold;
    color: #e74c3c;
}

.price-note {
    font-size: 0.65rem;
    color: #6c757d;
    margin-top: 3px;
}

/* Custom Budget Input */
.custom-budget-input {
    width: 100%;
    padding: 0.7rem;
    border: 1.5px solid #e74c3c;
    border-radius: 10px;
    background: white;
    font-size: 0.9rem;
    text-align: center;
    margin-top: 0.5rem;
}

/* Info Note */
.info-note {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 0.7rem;
    margin-top: 1rem;
    font-size: 0.75rem;
    color: #6c757d;
    text-align: center;
    border: 1px solid #e9ecef;
}

.info-note i {
    color: #e74c3c;
    margin-right: 5px;
}

/* Submit Button */
.btn-submit {
    width: 100%;
    padding: 0.8rem;
    background: linear-gradient(135deg, #e74c3c, #c0392b);
    color: white;
    border: none;
    border-radius: 10px;
    font-size: 0.95rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin-top: 0.5rem;
}

.btn-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(39, 174, 96, 0.3);
}

.btn-submit:disabled {
    background: #adb5bd;
    cursor: not-allowed;
    transform: none;
}

/* Error & Success Messages */
.error-message {
    background: #fef5f4;
    border: 1px solid #e74c3c;
    border-radius: 10px;
    padding: 0.8rem;
    margin-bottom: 1rem;
    color: #e74c3c;
    font-size: 0.85rem;
}

.success-message {
    background: #d4edda;
    border: 1px solid #27ae60;
    border-radius: 10px;
    padding: 0.8rem;
    margin-bottom: 1rem;
    color: #155724;
    font-size: 0.85rem;
}

/* Character Counter */
.char-counter {
    font-size: 0.65rem;
    color: #6c757d;
    margin-top: 3px;
    text-align: right;
}

/* Calendar Icon */
input[type="date"] {
    cursor: pointer;
}

input[type="date"]::-webkit-calendar-picker-indicator {
    cursor: pointer;
    filter: invert(0.4);
}

/* Responsive */
@media (max-width: 600px) {
    .commission-card {
        padding: 1.2rem;
    }
}
</style>

<div class="commission-container">
    <a href="javascript:history.back()" class="back-link">
        <i class="fas fa-arrow-left"></i> Back
    </a>
    
    <div class="commission-card">
        <div class="commission-header">
            <h1><i class="fas fa-handshake"></i> Request Custom Artwork</h1>
            <p>Fill in the details below</p>
        </div>
        
        <!-- Reference Artwork -->
        <?php if ($artwork_details): ?>
        <div class="reference-artwork">
            <img src="uploads/artworks/<?php echo htmlspecialchars($artwork_details['image_path']); ?>" alt="Reference">
            <div>
                <h4>Reference: <?php echo htmlspecialchars($artwork_details['title']); ?></h4>
                <p>by <?php echo htmlspecialchars($artwork_details['artist_name'] ?? $artwork_details['username']); ?></p>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Error/Success Messages -->
        <?php if (isset($_SESSION['errors'])): ?>
            <div class="error-message">
                <?php foreach ($_SESSION['errors'] as $error): ?>
                    <p style="margin: 0;">⚠️ <?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
                <?php unset($_SESSION['errors']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['message'])): ?>
            <div class="<?php echo $_SESSION['message_type'] == 'success' ? 'success-message' : 'error-message'; ?>">
                <p style="margin: 0;"><?php echo htmlspecialchars($_SESSION['message']); ?></p>
            </div>
            <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
        <?php endif; ?>
        
        <form action="process-commission.php" method="POST" id="commissionForm">
            <!-- Hidden fields -->
            <input type="hidden" name="source_artwork_id" value="<?php echo $artwork_id; ?>">
            
            <!-- Artist Selection -->
            <div class="form-group">
                <label><i class="fas fa-paint-brush"></i> Artist *</label>
                <select name="artist_id" id="artist_id" required <?php echo $pre_selected_artist ? 'disabled' : ''; ?>>
                    <option value="">-- Select Artist --</option>
                    <?php
                    $sql = "SELECT a.artist_id, u.full_name, u.username, a.specialization 
                            FROM artists a 
                            JOIN users u ON a.user_id = u.user_id 
                            WHERE a.status = 'approved'
                            ORDER BY u.full_name ASC";
                    $result = mysqli_query($conn, $sql);
                    while($artist = mysqli_fetch_assoc($result)) {
                        $display = ($artist['full_name'] ?: $artist['username']);
                        $selected = ($pre_selected_artist == $artist['artist_id']) ? 'selected' : '';
                        echo "<option value='{$artist['artist_id']}' $selected>" . htmlspecialchars($display) . " - {$artist['specialization']}</option>";
                    }
                    ?>
                </select>
                <?php if ($pre_selected_artist): ?>
                    <input type="hidden" name="artist_id" value="<?php echo $pre_selected_artist; ?>">
                    <small style="color: #27ae60;">✓ Artist: <strong><?php echo htmlspecialchars($artist_name); ?></strong></small>
                <?php endif; ?>
            </div>
            
            <!-- Artwork Title -->
            <div class="form-group">
                <label><i class="fas fa-heading"></i> Artwork Title *</label>
                <input type="text" name="title" id="title" required 
                       value="<?php echo htmlspecialchars($pre_filled_title ?: ($artwork_details['title'] ?? '')); ?>"
                       placeholder="e.g., Green Tara Thangka">
            </div>
            
            <!-- Size Selection - Dropdown -->
            <div class="form-group">
                <label><i class="fas fa-arrows-alt"></i> Size *</label>
                <select name="size" id="sizeSelect" required>
                    <option value="">-- Select Size --</option>
                    <?php foreach ($size_prices as $size => $price): ?>
                        <option value="<?php echo htmlspecialchars($size); ?>" data-price="<?php echo $price; ?>">
                            <?php echo htmlspecialchars($size); ?> <?php echo $price > 0 ? "- NRs " . number_format($price, 2) : "- Enter budget"; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
           <!-- Price Display -->
<div id="priceDisplay" class="price-display" style="display: none;">
    <div>Artwork Price</div>
    <div class="price" id="estimatedPrice">NRs 0</div>
    <div class="price-note">*Price is fixed based on selected size</div>
</div>
            
            <!-- Hidden budget field -->
            <input type="hidden" name="budget" id="budgetField" value="">
            
            <!-- Description -->
            <div class="form-group">
                <label><i class="fas fa-align-left"></i> Description / Requirements *</label>
                <textarea name="description" id="description" rows="5" required 
                          placeholder="Describe your vision: colors, style, specific deities, symbols, references, etc."></textarea>
                <div class="char-counter"><span id="charCount">0</span> characters</div>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-calendar-alt"></i> Preferred Deadline</label>
                <input type="date" name="deadline" id="deadline" min="<?php echo $min_deadline; ?>">
                <small>Minimum 7 days from today</small>
            </div>
            
            <!-- Payment Method - Only eSewa -->
            <div class="form-group">
                <label><i class="fas fa-credit-card"></i> Payment Method *</label>
                <select name="payment_method" required>
                    <option value="eSewa">Khalti</option>
                </select>
            </div>
            
            <!-- How it works -->
            <div class="info-note">
                <i class="fas fa-info-circle"></i> 
                Artist accepts → Pay 50% advance → Artist creates → Pay remaining 50% → Download artwork
            </div>
            
            <button type="submit" class="btn-submit" id="submitBtn">
                 Submit Request
            </button>
        </form>
    </div>
</div>

<script>
// Size selection and price calculation
const sizeSelect = document.getElementById('sizeSelect');
const priceDisplay = document.getElementById('priceDisplay');
const estimatedPrice = document.getElementById('estimatedPrice');
const budgetField = document.getElementById('budgetField');
let customBudgetInput = null;

sizeSelect.addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const price = parseInt(selectedOption.dataset.price);
    const sizeName = selectedOption.value;
    
    // Remove existing custom budget input if any
    if (customBudgetInput) {
        customBudgetInput.remove();
        customBudgetInput = null;
    }
    
    if (price > 0) {
        // Standard size
        budgetField.value = price;
        estimatedPrice.innerHTML = 'NRs ' + price.toLocaleString('en-IN');
        priceDisplay.style.display = 'block';
    } else if (sizeName === 'Custom Size') {
        // Custom size
        priceDisplay.style.display = 'block';
        estimatedPrice.innerHTML = 'Enter your budget';
        
        const customDiv = document.createElement('div');
        customDiv.id = 'customBudgetContainer';
        customDiv.innerHTML = `
            <input type="number" id="customBudget" name="custom_budget" 
                   placeholder="Enter your budget (NRs)" 
                   class="custom-budget-input"
                   min="500" max="500000" step="100">
            <div class="price-note" style="margin-top: 5px;">💡 Budget range: NRs 500 - 500,000</div>
        `;
        priceDisplay.appendChild(customDiv);
        customBudgetInput = customDiv;
        
        const customBudget = document.getElementById('customBudget');
        if (customBudget) {
            customBudget.addEventListener('input', function() {
                if (this.value) {
                    budgetField.value = this.value;
                    estimatedPrice.innerHTML = 'NRs ' + parseInt(this.value).toLocaleString('en-IN');
                } else {
                    budgetField.value = '';
                    estimatedPrice.innerHTML = 'Enter budget above';
                }
            });
        }
    } else {
        priceDisplay.style.display = 'none';
        budgetField.value = '';
    }
});

// Character counter
const description = document.getElementById('description');
const charCount = document.getElementById('charCount');

description.addEventListener('input', function() {
    charCount.textContent = this.value.length;
});

// Form validation
const form = document.getElementById('commissionForm');
const submitBtn = document.getElementById('submitBtn');

form.addEventListener('submit', function(e) {
    const artistId = document.getElementById('artist_id').value;
    const title = document.getElementById('title').value.trim();
    const size = sizeSelect.value;
    const budget = budgetField.value;
    const description = document.getElementById('description').value.trim();
    const deadline = document.getElementById('deadline').value;
    
    let errors = [];
    
    if (!artistId) errors.push('Please select an artist');
    if (!title) errors.push('Please enter an artwork title');
    if (!size) errors.push('Please select a size');
    if (!budget || parseFloat(budget) < 500) errors.push('Please set a valid budget (minimum NRs 500)');
    if (!description) errors.push('Please provide a description');
    if (description.length < 20) errors.push('Please provide more details (minimum 20 characters)');
    
    if (deadline) {
        const minDate = new Date();
        minDate.setDate(minDate.getDate() + 7);
        const selectedDate = new Date(deadline);
        if (selectedDate < minDate) errors.push('Deadline must be at least 7 days from today');
    }
    
    if (errors.length > 0) {
        e.preventDefault();
        alert(errors.join('\n'));
        return false;
    }
    
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
});
</script>

<?php require_once 'includes/footer.php'; ?>