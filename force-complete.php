<?php
// force-complete.php - Emergency payment completion
require_once 'includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$commission_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$update = "UPDATE commissions SET 
            remaining_paid = 1, 
            remaining_paid_at = NOW(), 
            remaining_transaction_id = 'FORCED',
            payment_status = 'completed',
            paid_at = NOW(),
            status = 'completed'
            WHERE commission_id = ? AND user_id = ?";

$stmt = mysqli_prepare($conn, $update);
mysqli_stmt_bind_param($stmt, "ii", $commission_id, $_SESSION['user_id']);

if (mysqli_stmt_execute($stmt)) {
    $_SESSION['message'] = "Payment completed! You can now download.";
    $_SESSION['message_type'] = 'success';
} else {
    $_SESSION['message'] = "Error: " . mysqli_error($conn);
    $_SESSION['message_type'] = 'error';
}

header("Location: commission-details.php?id=$commission_id");
exit;
?>