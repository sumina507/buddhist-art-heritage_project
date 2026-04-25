<?php
// commission-details.php - WITH FIXED NAVBAR
require_once 'includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$commission_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Get artist_id for artists
if ($role == 'artist' && !isset($_SESSION['artist_id'])) {
    $get_artist = "SELECT artist_id FROM artists WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $get_artist);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $artist = mysqli_fetch_assoc($result);
    if ($artist) {
        $_SESSION['artist_id'] = $artist['artist_id'];
    }
}

$artist_id = $_SESSION['artist_id'] ?? 0;

// Get commission details
if ($role == 'artist') {
    $sql = "SELECT c.*, u.username as client_name, u.full_name as client_fullname, u.email as client_email, u.profile_image as client_image
            FROM commissions c JOIN users u ON c.user_id = u.user_id 
            WHERE c.commission_id = ? AND c.artist_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $commission_id, $artist_id);
} else {
    $sql = "SELECT c.*, u.username as artist_name, u.full_name as artist_fullname, u.email as artist_email, u.profile_image as artist_image
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
    $_SESSION['message'] = "Request not found.";
    header('Location: commissions.php');
    exit;
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Accept commission
    if (isset($_POST['accept_commission']) && $role == 'artist' && $commission['status'] == 'pending') {
        $stmt = mysqli_prepare($conn, "UPDATE commissions SET status = 'accepted' WHERE commission_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $commission_id);
        mysqli_stmt_execute($stmt);
        $_SESSION['message'] = "Request accepted! Client can now pay advance.";
        $_SESSION['message_type'] = 'success';
        header("Location: commission-details.php?id=$commission_id");
        exit;
    }
    
    // MARK ADVANCE AS PAID
    if (isset($_POST['mark_advance_paid']) && $role == 'user') {
        $stmt = mysqli_prepare($conn, "UPDATE commissions SET advance_paid = 1, advance_paid_at = NOW(), status = 'in_progress' WHERE commission_id = ? AND user_id = ?");
        mysqli_stmt_bind_param($stmt, "ii", $commission_id, $user_id);
        mysqli_stmt_execute($stmt);
        $_SESSION['message'] = "Advance payment confirmed! Artist will now start working.";
        $_SESSION['message_type'] = 'success';
        header("Location: commission-details.php?id=$commission_id");
        exit;
    }
    
    // Upload final artwork
    if (isset($_POST['upload_final']) && $role == 'artist' && isset($_FILES['final_file']) && $_FILES['final_file']['error'] == 0) {
        $ext = pathinfo($_FILES['final_file']['name'], PATHINFO_EXTENSION);
        $filename = 'final_' . $commission_id . '_' . time() . '.' . $ext;
        $upload_path = 'uploads/final/';
        if (!file_exists($upload_path)) mkdir($upload_path, 0777, true);
        
        if (move_uploaded_file($_FILES['final_file']['tmp_name'], $upload_path . $filename)) {
            $stmt = mysqli_prepare($conn, "UPDATE commissions SET final_file = ?, final_uploaded = 1, status = 'ready_for_payment' WHERE commission_id = ?");
            mysqli_stmt_bind_param($stmt, "si", $filename, $commission_id);
            mysqli_stmt_execute($stmt);
            $_SESSION['message'] = "Artwork uploaded! Client can now pay remaining amount.";
            $_SESSION['message_type'] = 'success';
        }
        header("Location: commission-details.php?id=$commission_id");
        exit;
    }
    
    // MARK REMAINING AS PAID
    if (isset($_POST['mark_remaining_paid']) && $role == 'user') {
        $stmt = mysqli_prepare($conn, "UPDATE commissions SET remaining_paid = 1, remaining_paid_at = NOW(), payment_status = 'completed', status = 'completed' WHERE commission_id = ? AND user_id = ?");
        mysqli_stmt_bind_param($stmt, "ii", $commission_id, $user_id);
        mysqli_stmt_execute($stmt);
        $_SESSION['message'] = "Payment completed! You can now download your artwork.";
        $_SESSION['message_type'] = 'success';
        header("Location: commission-details.php?id=$commission_id");
        exit;
    }
}

// Variables for display
$status = $commission['status'];
$advance_paid = $commission['advance_paid'] ?? 0;
$final_uploaded = $commission['final_uploaded'] ?? 0;
$remaining_paid = $commission['remaining_paid'] ?? 0;
$payment_status = $commission['payment_status'] ?? 'pending';
$is_completed = ($payment_status == 'completed' || $remaining_paid == 1);
$has_final = !empty($commission['final_file']);
$budget = $commission['budget'] ?? 0;
$advance_amount = $budget / 2;
$remaining_amount = $budget / 2;

// Get messages for chat
$msg_sql = "SELECT m.*, u.username, u.profile_image FROM commission_messages m JOIN users u ON m.user_id = u.user_id WHERE m.commission_id = ? ORDER BY m.created_at ASC";
$msg_stmt = mysqli_prepare($conn, $msg_sql);
mysqli_stmt_bind_param($msg_stmt, "i", $commission_id);
mysqli_stmt_execute($msg_stmt);
$messages = mysqli_stmt_get_result($msg_stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Custom Request #<?php echo $commission_id; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Only page-specific styles - NO navbar overrides */
        .commission-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }
        
        .back-link {
            color: #e74c3c;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 1.5rem;
        }
        .back-link:hover { text-decoration: underline; }

        /* Main Card */
        .main-card {
            background: white;
            border-radius: 20px;
            margin-bottom: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        .card-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .card-header h1 { color: #2c3e50; font-size: 1.5rem; margin: 0; }
        .status-badge { padding: 0.3rem 1rem; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
        .status-pending { background: #f39c12; color: #fff; }
        .status-accepted { background: #3498db; color: #fff; }
        .status-in_progress { background: #9b59b6; color: #fff; }
        .status-ready_for_payment { background: #f39c12; color: #fff; }
        .status-completed { background: #27ae60; color: #fff; }
        
        /* Two Columns */
        .two-columns {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            padding: 1.5rem;
        }
        @media (max-width: 768px) { .two-columns { grid-template-columns: 1fr; } }
        
        /* Info Box */
        .info-box {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 1.2rem;
            margin-bottom: 1rem;
        }
        .info-box h3 { color: #2c3e50; font-size: 1rem; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem; }
        .info-box h3 i { color: #e74c3c; }
        .info-row { display: flex; margin-bottom: 0.8rem; color: #2c3e50; font-size: 0.9rem; }
        .info-label { width: 100px; font-weight: 600; color: #6c757d; }
        .info-value { flex: 1; }
        
        /* Progress Steps */
        .progress-steps {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 1.5rem;
        }
        .progress-steps h3 { color: #2c3e50; font-size: 1rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem; }
        .step { display: flex; margin-bottom: 1.5rem; position: relative; }
        .step:not(:last-child)::after { content: ''; position: absolute; left: 15px; top: 40px; width: 2px; height: 30px; background: #e9ecef; }
        .step-icon { width: 32px; height: 32px; border-radius: 50%; background: white; border: 2px solid #e9ecef; display: flex; align-items: center; justify-content: center; margin-right: 1rem; font-size: 0.8rem; font-weight: bold; color: #6c757d; flex-shrink: 0; z-index: 1; }
        .step-icon.completed { background: #27ae60; border-color: #27ae60; color: white; }
        .step-icon.active { border-color: #e74c3c; color: #e74c3c; }
        .step-content { flex: 1; }
        .step-title { font-weight: 600; color: #2c3e50; margin-bottom: 0.2rem; }
        .step-desc { font-size: 0.75rem; color: #6c757d; margin-bottom: 0.5rem; }
        
        /* Payment Box */
        .payment-box {
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 12px;
            padding: 1rem;
            text-align: center;
            margin-top: 0.5rem;
        }
        .payment-amount { font-size: 1.3rem; font-weight: bold; color: #e74c3c; margin: 0.5rem 0; }
        
        /* Buttons */
        .btn-accept, .btn-upload, .btn-manual {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.8rem;
            cursor: pointer;
            border: none;
            text-align: center;
            width: 100%;
        }
        .btn-accept { background: #27ae60; color: white; }
        .btn-upload { background: #3498db; color: white; margin-top: 0.5rem; }
        .btn-manual { background: #f1c40f; color: #2c3e50; margin-top: 0.5rem; }
        .download-link {
            display: inline-block;
            background: #27ae60;
            color: white;
            padding: 0.6rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            margin-top: 0.5rem;
            width: 100%;
            text-align: center;
        }
        
        /* Artwork Preview */
        .artwork-preview {
            text-align: center;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 12px;
            margin-top: 0.5rem;
        }
        .artwork-preview img { max-width: 100%; max-height: 150px; border-radius: 8px; }
        .artwork-preview small { color: #e74c3c; display: block; margin-top: 0.5rem; }
        
        /* Description */
        .description-box {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 1.2rem;
            margin: 0 1.5rem 1.5rem 1.5rem;
        }
        .description-box h3 { color: #2c3e50; font-size: 1rem; margin-bottom: 0.8rem; display: flex; align-items: center; gap: 0.5rem; }
        .description-box h3 i { color: #e74c3c; }
        .description-box p { color: #2c3e50; font-size: 0.9rem; line-height: 1.5; }
        
              /* Chat Section - Messenger Style with WIDER bubbles */
        .chat-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            overflow: hidden;
            margin-top: 1.5rem;
            border: 1px solid #e9ecef;
        }
        
        .chat-header {
            background: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
        }
        
        .chat-toggle {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            padding: 0.8rem 1.2rem;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .chat-toggle:hover {
            background: #f0f0f0;
        }
        
        .chat-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .chat-header-info {
            flex: 1;
        }
        
        .chat-header-info h4 {
            margin: 0;
            font-size: 0.95rem;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .chat-header-info p {
            margin: 0;
            font-size: 0.7rem;
            color: #6c757d;
        }
        
        #chatToggleIcon {
            color: #6c757d;
            font-size: 0.9rem;
            transition: transform 0.2s;
        }
        
        .chat-body {
            max-height: 400px;
            overflow-y: auto;
    background: #f4f1ea;  /* Warm, calm, like traditional paper */
  position:relative;
    transition: max-height 0.3s ease;
        }
        
        
        .chat-body.collapsed {
            max-height: 0;
            padding: 0;
        }
        
        .chat-messages {
            padding: 1rem;
            display: flex;
            flex-direction: column;
            gap: 0.8rem;
        }
        
        /* Message Row - WIDER like Messenger */
        .message-row {
            display: flex;
            align-items: flex-end;
            gap: 0.5rem;
            width: 100%;
        }
        
        .sent-row {
            justify-content: flex-end;
        }
        
        .received-row {
            justify-content: flex-start;
        }
        
        /* Message Bubbles - WIDE */
        .message-bubble {
            max-width: 75%;
            padding: 0.6rem 1rem;
            border-radius: 18px;
            position: relative;
            word-wrap: break-word;
        }
        
        .message-bubble.sent {
            background: #0084ff;
            color: white;
            border-bottom-right-radius: 5px;
        }
        
        .message-bubble.received {
            background: white;
            color: #2c3e50;
            border-bottom-left-radius: 5px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
            border: 1px solid #e9ecef;
        }
        
        .message-text {
            font-size: 0.9rem;
            line-height: 1.4;
        }
        
        .message-time {
            font-size: 0.6rem;
            margin-top: 0.3rem;
            opacity: 0.6;
        }
        
        .sent .message-time {
            text-align: right;
        }
        
        .message-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
            flex-shrink: 0;
            align-self: flex-end;
        }
        
        /* For sent messages - no avatar shown */
        .sent-row .message-avatar {
            display: none;
        }
        
        .chat-footer {
            padding: 0.8rem 1rem;
            border-top: 1px solid #e9ecef;
            background: white;
        }
        
        .emoji-picker {
            display: flex;
            gap: 0.3rem;
            margin-bottom: 0.5rem;
            justify-content: flex-start;
        }
        
        .emoji-btn {
            background: none;
            border: none;
            font-size: 1rem;
            cursor: pointer;
            padding: 0.2rem;
            transition: transform 0.1s;
            opacity: 0.6;
        }
        
        .emoji-btn:hover {
            transform: scale(1.2);
            opacity: 1;
        }
        
        .chat-input-wrapper {
            display: flex;
            gap: 0.6rem;
            align-items: flex-end;
        }
        
        .chat-input-wrapper textarea {
            flex: 1;
            padding: 0.6rem 1rem;
            border: 1px solid #e9ecef;
            border-radius: 24px;
            resize: none;
            font-family: inherit;
            font-size: 0.85rem;
            max-height: 80px;
            background: #f8f9fa;
            transition: all 0.2s;
        }
        
        .chat-input-wrapper textarea:focus {
            outline: none;
            border-color: #0084ff;
            background: white;
        }
        
        .chat-send-btn {
            background: #0084ff;
            color: white;
            border: none;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .chat-send-btn:hover {
            background: #0066cc;
            transform: scale(1.02);
        }
        
        .no-messages {
            text-align: center;
            padding: 2rem;
            color: #6c757d;
        }
        
        .no-messages i {
            font-size: 2rem;
            color: #ddd;
            margin-bottom: 0.5rem;
        }
        
        /* Emoji picker */
        .emoji-picker {
            display: flex;
            gap: 0.3rem;
            margin-bottom: 0.3rem;
            justify-content: flex-start;
        }
        
        .emoji-btn {
            background: none;
            border: none;
            font-size: 1rem;
            cursor: pointer;
            padding: 0.2rem;
            transition: transform 0.1s;
            opacity: 0.6;
        }
        
        .emoji-btn:hover {
            transform: scale(1.2);
            opacity: 1;
        }
      
        
        .completed-message { background: #d4edda; color: #155724; padding: 1rem; border-radius: 10px; text-align: center; margin: 1rem 0; }
        input[type="file"] { padding: 0.5rem; border: 1px solid #e9ecef; border-radius: 8px; width: 100%; margin-bottom: 0.5rem; }
    </style>
</head>
<body>
    <?php require_once 'includes/navbar.php'; ?>
    
    <div class="commission-container">
                <a href="commissions.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to My Requests</a>

        
        <div class="main-card">
            <div class="card-header">
                <h1><i class="fas fa-handshake"></i> Custom Request #<?php echo $commission_id; ?></h1>
                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                    <span class="status-badge status-<?php echo $status; ?>">
                        <?php echo ucfirst(str_replace('_', ' ', $status)); ?>
                    </span>
                </div>
            </div>
            
            <?php if ($is_completed): ?>
            <div class="completed-message">
                <i class="fas fa-check-circle"></i> Your custom artwork request is complete. Thank you for ordering.
            </div>
            <?php endif; ?>
            
                     <div class="two-columns">
                <!-- LEFT COLUMN -->
                <div>
                    <div class="info-box">
                        <h3><i class="fas fa-info-circle"></i> Request Details</h3>
                        <div class="info-row"><div class="info-label">Title:</div><div class="info-value"><?php echo htmlspecialchars($commission['title']); ?></div></div>
                        <div class="info-row"><div class="info-label">Budget:</div><div class="info-value">NRs <?php echo number_format($budget, 2); ?></div></div>
                        <?php if ($commission['deadline']): ?>
                        <div class="info-row"><div class="info-label">Deadline:</div><div class="info-value"><?php echo date('M d, Y', strtotime($commission['deadline'])); ?></div></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="info-box">
                        <h3><i class="fas fa-user"></i> <?php echo $role == 'artist' ? 'Client' : 'Artist'; ?></h3>
                        <div class="info-row"><div class="info-label">Name:</div><div class="info-value"><?php echo htmlspecialchars($role == 'artist' ? ($commission['client_fullname'] ?? $commission['client_name']) : ($commission['artist_fullname'] ?? $commission['artist_name'])); ?></div></div>
                        <div class="info-row"><div class="info-label">Email:</div><div class="info-value"><?php echo htmlspecialchars($role == 'artist' ? $commission['client_email'] : $commission['artist_email']); ?></div></div>
                    </div>
                    
                    <!-- Description moved here - right after client info -->
                    <div class="info-box">
                        <h3><i class="fas fa-align-left"></i> Description</h3>
                        <p style="margin: 0; color: #2c3e50; font-size: 0.9rem; line-height: 1.5;"><?php echo nl2br(htmlspecialchars($commission['description'])); ?></p>
                    </div>
                </div>
                
                <!-- RIGHT COLUMN - Progress Steps -->
                <div class="progress-steps">
                    <h3><i class="fas fa-tasks"></i> Request Progress</h3>
                    
                    <div class="step">
                        <div class="step-icon <?php echo $status != 'pending' ? 'completed' : 'active'; ?>"><?php echo $status != 'pending' ? '✓' : '1'; ?></div>
                        <div class="step-content">
                            <div class="step-title">Step 1: Request Submitted</div>
                            <div class="step-desc">Your request has been sent to the artist</div>
                        </div>
                    </div>
                    
                    <div class="step">
                        <div class="step-icon <?php echo ($status == 'accepted' || $status == 'in_progress' || $status == 'ready_for_payment' || $status == 'completed') ? 'completed' : ($status == 'pending' ? '' : 'active'); ?>">
                            <?php echo ($status == 'accepted' || $status == 'in_progress' || $status == 'ready_for_payment' || $status == 'completed') ? '✓' : '2'; ?>
                        </div>
                        <div class="step-content">
                            <div class="step-title">Step 2: Artist Accepts</div>
                            <div class="step-desc">Artist reviews your request</div>
                            <?php if ($role == 'artist' && $status == 'pending'): ?>
                                <form method="POST"><button type="submit" name="accept_commission" class="btn-accept">Accept Request</button></form>
                            <?php endif; ?>
                        </div>
                    </div>
     <!-- STEP 3: Pay Advance -->
<div class="step">
    <div class="step-icon <?php echo $advance_paid ? 'completed' : ($status == 'accepted' ? 'active' : ''); ?>"><?php echo $advance_paid ? '✓' : '3'; ?></div>
    <div class="step-content">
        <div class="step-title">Step 3: Pay Advance (50%)</div>
        <div class="step-desc">Pay 50% advance to start the artwork</div>
        
        <?php if ($role == 'user' && $status == 'accepted' && !$advance_paid && !$is_completed): ?>
            <div class="payment-box">
                <div class="payment-amount">NRs <?php echo number_format($advance_amount, 2); ?></div>
                
                <!-- Khalti Payment -->
                <a href="khalti-pay.php?commission_id=<?php echo $commission_id; ?>&type=advance" 
                   class="btn-manual" style="display: block; text-align: center; text-decoration: none; background: #5C2D91; color: white; margin-bottom: 10px;">
                Pay with Khalti
                </a>
                
                <!-- Demo Payment (Backup) -->
                <form method="POST">
                    <button type="submit" name="mark_advance_paid" class="btn-manual" style="background: #f1c40f; width: 100%;">
                         Demo: Mark as Paid
                    </button>
                </form>
                <small style="color: #6c757d; display: block; margin-top: 0.5rem;">
                    ⚡ Use Demo mode if Khalti test server has issues.
                </small>
            </div>
        <?php endif; ?>
        
        <?php if ($advance_paid): ?>
            <div style="color: #27ae60; margin-top: 0.3rem;"><i class="fas fa-check-circle"></i> Advance payment received</div>
        <?php endif; ?>
    </div>
</div>
                    <div class="step">
                        <div class="step-icon <?php echo $final_uploaded ? 'completed' : ($advance_paid ? 'active' : ''); ?>"><?php echo $final_uploaded ? '✓' : '4'; ?></div>
                        <div class="step-content">
                            <div class="step-title">Step 4: Artist Creates Artwork</div>
                            <div class="step-desc">Artist creates and uploads the artwork</div>
                            
                            <?php if ($role == 'artist' && $advance_paid && !$final_uploaded && !$is_completed): ?>
                                <form method="POST" enctype="multipart/form-data">
                                    <input type="file" name="final_file" accept="image/*" required>
                                    <button type="submit" name="upload_final" class="btn-upload">Upload Artwork</button>
                                </form>
                            <?php endif; ?>
                            
                            <?php if ($final_uploaded && $has_final && !$is_completed): ?>
                                <div class="artwork-preview">
                                    <img src="uploads/final/<?php echo $commission['final_file']; ?>" alt="Preview">
                                    <small>Preview - Pay remaining to download</small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                   <!-- STEP 5: Pay Remaining -->
<div class="step">
    <div class="step-icon <?php echo $is_completed ? 'completed' : ($final_uploaded ? 'active' : ''); ?>"><?php echo $is_completed ? '✓' : '5'; ?></div>
    <div class="step-content">
        <div class="step-title">Step 5: Pay Remaining & Download</div>
        <div class="step-desc">Pay remaining 50% and download your artwork</div>
        
        <?php if ($role == 'user' && $final_uploaded && !$is_completed): ?>
            <div class="payment-box">
                <div class="payment-amount">NRs <?php echo number_format($remaining_amount, 2); ?></div>
                
                <!-- Khalti Payment -->
                <a href="khalti-pay.php?commission_id=<?php echo $commission_id; ?>&type=remaining" 
                   class="btn-manual" style="display: block; text-align: center; text-decoration: none; background: #5C2D91; color: white; margin-bottom: 10px;">
                     Pay with Khalti
                </a>
                
                <!-- Demo Payment (Backup) -->
                <form method="POST">
                    <button type="submit" name="mark_remaining_paid" class="btn-manual" style="background: #f1c40f; width: 100%;">
                        </i> Demo: Mark as Paid
                    </button>
                </form>
                <small style="color: #6c757d; display: block; margin-top: 0.5rem;">
                    ⚡ Use Demo mode if Khalti test server has issues.
                </small>
            </div>
        <?php endif; ?>
        
        <?php if ($role == 'user' && $is_completed && $has_final): ?>
            <a href="uploads/final/<?php echo $commission['final_file']; ?>" download class="download-link">
                <i class="fas fa-download"></i> Download Artwork
            </a>
        <?php endif; ?>
    </div>
</div>
                </div>
            </div>
            
           
        </div>
        
               
        <div class="chat-card">
            <div class="chat-header">
                <?php 
                $other_person_image = ($role == 'artist') ? ($commission['client_image'] ?? 'default.jpg') : ($commission['artist_image'] ?? 'default.jpg');
                $other_person_name = ($role == 'artist') ? ($commission['client_fullname'] ?? $commission['client_name']) : ($commission['artist_fullname'] ?? $commission['artist_name']);
                ?>
                <div class="chat-toggle" onclick="toggleChat()">
                    <img src="uploads/profiles/<?php echo htmlspecialchars($other_person_image); ?>" class="chat-avatar" alt="Avatar">
                    <div class="chat-header-info">
                        <h4><?php echo htmlspecialchars($other_person_name); ?></h4>
                        <p><?php echo $role == 'artist' ? 'Client' : 'Artist'; ?></p>
                    </div>
                    <i class="fas fa-chevron-down" id="chatToggleIcon"></i>
                </div>
            </div>
            
            <div class="chat-body" id="chatBody">
                <div class="chat-messages" id="chatMessages">
                    <?php if (mysqli_num_rows($messages) > 0): ?>
                        <?php while ($msg = mysqli_fetch_assoc($messages)): 
                            $is_sent = ($msg['user_id'] == $user_id);
                        ?>
                            <div class="message-row <?php echo $is_sent ? 'sent-row' : 'received-row'; ?>">
                                <?php if (!$is_sent): ?>
                                    <img src="uploads/profiles/<?php echo htmlspecialchars($msg['profile_image'] ?? 'default.jpg'); ?>" class="message-avatar" alt="Avatar">
                                <?php endif; ?>
                                <div class="message-bubble <?php echo $is_sent ? 'sent' : 'received'; ?>">
                                    <div class="message-text">
                                        <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                                    </div>
                                    <div class="message-time"><?php echo date('g:i A', strtotime($msg['created_at'])); ?></div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="no-messages">
                            <i class="fas fa-comments"></i>
                            <p>No messages yet. Start the conversation!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="chat-footer" id="chatFooter">
                <div class="emoji-picker">
                    <button type="button" class="emoji-btn" onclick="addEmoji('😊')">😊</button>
                    <button type="button" class="emoji-btn" onclick="addEmoji('❤️')">❤️</button>
                    <button type="button" class="emoji-btn" onclick="addEmoji('👍')">👍</button>
                    <button type="button" class="emoji-btn" onclick="addEmoji('🙏')">🙏</button>
                </div>
                <div class="chat-input-wrapper">
                    <textarea id="messageInput" placeholder="Type a message..." rows="1"></textarea>
                    <button class="chat-send-btn" onclick="sendMessage()">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </div>
        </div>
        
    </div>
    
    <script>

             // Auto-scroll to bottom
        const chatBody = document.getElementById('chatBody');
        const chatMessages = document.getElementById('chatMessages');
        if (chatMessages) chatMessages.scrollTop = chatMessages.scrollHeight;
        
        // Toggle chat collapse
        function toggleChat() {
            const chatBody = document.getElementById('chatBody');
            const icon = document.getElementById('chatToggleIcon');
            chatBody.classList.toggle('collapsed');
            if (chatBody.classList.contains('collapsed')) {
                icon.className = 'fas fa-chevron-up';
            } else {
                icon.className = 'fas fa-chevron-down';
                // Scroll to bottom when expanded
                if (chatMessages) chatMessages.scrollTop = chatMessages.scrollHeight;
            }
        }
        
        function addEmoji(emoji) {
            const textarea = document.getElementById('messageInput');
            textarea.value += emoji;
            textarea.focus();
        }
        
        const textarea = document.getElementById('messageInput');
        if (textarea) {
            textarea.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = Math.min(this.scrollHeight, 80) + 'px';
            });
            textarea.addEventListener('keypress', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    sendMessage();
                }
            });
        }
        
        function showToast(message, type) {
            // Simple alert for now - can be replaced with nice toast
            if (type === 'error') alert('Error: ' + message);
        }
        
               function sendMessage() {
            const messageInput = document.getElementById('messageInput');
            const message = messageInput.value.trim();
            if (message === '') return;
            
            const sendBtn = document.querySelector('.chat-send-btn');
            sendBtn.disabled = true;
            
            fetch('ajax/send-message.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'commission_id=<?php echo $commission_id; ?>&message=' + encodeURIComponent(message)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    messageInput.value = '';
                    messageInput.style.height = 'auto';
                    const chatMessages = document.getElementById('chatMessages');
                    const noMessages = chatMessages.querySelector('.no-messages');
                    if (noMessages) noMessages.remove();
                    
                    // Create new message row
                    const messageRow = document.createElement('div');
                    messageRow.className = 'message-row sent-row';
                    messageRow.innerHTML = `
                        <div class="message-bubble sent">
                            <div class="message-text">${escapeHtml(message).replace(/\n/g, '<br>')}</div>
                            <div class="message-time">Just now</div>
                        </div>
                    `;
                    chatMessages.appendChild(messageRow);
                    chatMessages.scrollTop = chatMessages.scrollHeight;
                } else {
                    alert(data.message || 'Error sending message');
                }
            })
            .catch(error => {
                alert('Network error. Please try again.');
            })
            .finally(() => { 
                sendBtn.disabled = false; 
                messageInput.focus();
            });
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
    
    <?php require_once 'includes/footer.php'; ?>
</body>
</html>