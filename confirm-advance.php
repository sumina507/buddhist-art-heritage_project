<?php
// confirm-advance.php - Redirect to commission-details with action
require_once 'includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$commission_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($commission_id) {
    $user_id = $_SESSION['user_id'];
    $stmt = mysqli_prepare($conn, "UPDATE commissions SET advance_paid = 1, advance_paid_at = NOW(), status = 'in_progress' WHERE commission_id = ? AND user_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $commission_id, $user_id);
    mysqli_stmt_execute($stmt);
    $_SESSION['message'] = "Advance payment confirmed!";
    $_SESSION['message_type'] = 'success';
}

header("Location: commission-details.php?id=$commission_id");
exit;
?>