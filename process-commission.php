<?php
// process-commission.php - FIXED (removed can_cancel column)
require_once 'includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: commission-request.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$artist_id = intval($_POST['artist_id'] ?? 0);
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$size = trim($_POST['size'] ?? '');
$deadline = !empty($_POST['deadline']) ? $_POST['deadline'] : null;
$payment_method = trim($_POST['payment_method'] ?? 'eSewa');
$commission_type = trim($_POST['commission_type'] ?? 'personal');
$source_artwork_id = !empty($_POST['source_artwork_id']) ? intval($_POST['source_artwork_id']) : null;

// Handle budget based on size or custom input
$budget = null;
if (!empty($_POST['budget']) && floatval($_POST['budget']) > 0) {
    $budget = floatval($_POST['budget']);
} elseif (!empty($_POST['custom_budget']) && floatval($_POST['custom_budget']) > 0) {
    $budget = floatval($_POST['custom_budget']);
}

// Validation
$errors = [];
if (!$artist_id) $errors[] = "Please select an artist";
if (empty($title)) $errors[] = "Title is required";
if (empty($size)) $errors[] = "Please select a size";
if (!$budget || $budget < 500) $errors[] = "Budget must be at least NRs 500";
if (empty($description)) $errors[] = "Description is required";
if (strlen($description) < 20) $errors[] = "Description must be at least 20 characters";

if (!empty($errors)) {
    $_SESSION['errors'] = $errors;
    header("Location: commission-request.php" . ($source_artwork_id ? "?artwork_id=$source_artwork_id" : ""));
    exit;
}

// FIXED: Removed 'can_cancel' column from INSERT
$sql = "INSERT INTO commissions (
    user_id, artist_id, title, description, budget, size, deadline, 
    payment_method, source_artwork_id, commission_type,
    status, created_at
) VALUES (
    ?, ?, ?, ?, ?, ?, ?, 
    ?, ?, ?,
    'pending', NOW()
)";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param(
    $stmt, 
    "iissdssssi", 
    $user_id, $artist_id, $title, $description, $budget, $size, $deadline,
    $payment_method, $source_artwork_id, $commission_type
);

if (mysqli_stmt_execute($stmt)) {
    $commission_id = mysqli_insert_id($conn);
    
    // Add initial message
    $message = "New commission request #$commission_id\nTitle: $title\nSize: $size\nBudget: NRs " . number_format($budget, 2);
    $msg_sql = "INSERT INTO commission_messages (commission_id, user_id, message, created_at) VALUES (?, ?, ?, NOW())";
    $msg_stmt = mysqli_prepare($conn, $msg_sql);
    mysqli_stmt_bind_param($msg_stmt, "iis", $commission_id, $user_id, $message);
    mysqli_stmt_execute($msg_stmt);
    
    $_SESSION['message'] = "✓ Commission request submitted successfully!";
    $_SESSION['message_type'] = 'success';
    header('Location: commissions.php');
    exit;
} else {
    $_SESSION['message'] = "Database Error: " . mysqli_error($conn);
    $_SESSION['message_type'] = 'error';
    header("Location: commission-request.php" . ($source_artwork_id ? "?artwork_id=$source_artwork_id" : ""));
    exit;
}
?>