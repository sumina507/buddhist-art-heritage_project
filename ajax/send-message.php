<?php
// ajax/send-message.php
require_once '../includes/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login']);
    exit;
}

$commission_id = isset($_POST['commission_id']) ? intval($_POST['commission_id']) : 0;
$user_id = $_SESSION['user_id'];
$message = trim($_POST['message'] ?? '');

if (empty($message)) {
    echo json_encode(['success' => false, 'message' => 'Message cannot be empty']);
    exit;
}

// Verify user has access to this commission
$check_sql = "SELECT commission_id FROM commissions WHERE commission_id = ? AND (user_id = ? OR artist_id = (SELECT artist_id FROM artists WHERE user_id = ?))";
$check_stmt = mysqli_prepare($conn, $check_sql);
mysqli_stmt_bind_param($check_stmt, "iii", $commission_id, $user_id, $user_id);
mysqli_stmt_execute($check_stmt);
$check_result = mysqli_stmt_get_result($check_stmt);

if (mysqli_num_rows($check_result) == 0) {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

// Insert message
$sql = "INSERT INTO commission_messages (commission_id, user_id, message, created_at) VALUES (?, ?, ?, NOW())";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "iis", $commission_id, $user_id, $message);

if (mysqli_stmt_execute($stmt)) {
    // Get user's profile image
    $img_sql = "SELECT profile_image FROM users WHERE user_id = ?";
    $img_stmt = mysqli_prepare($conn, $img_sql);
    mysqli_stmt_bind_param($img_stmt, "i", $user_id);
    mysqli_stmt_execute($img_stmt);
    $img_result = mysqli_stmt_get_result($img_stmt);
    $user_data = mysqli_fetch_assoc($img_result);
    $profile_image = $user_data['profile_image'] ?? 'default.jpg';
    
    echo json_encode([
        'success' => true, 
        'message' => 'Message sent',
        'profile_image' => $profile_image,
        'username' => $_SESSION['username']
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>