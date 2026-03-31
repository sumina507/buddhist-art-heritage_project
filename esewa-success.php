<?php
// esewa-success.php
require_once 'includes/config.php';

// Get callback data
$transaction_uuid = $_GET['transaction_uuid'] ?? '';
$ref_id = $_GET['ref_id'] ?? '';

if (!isset($_SESSION['pending_payment'])) {
    header('Location: commissions.php');
    exit;
}

$commission_id = $_SESSION['pending_payment']['commission_id'];

// Update commission as paid
$update = "UPDATE commissions SET payment_status = 'paid', paid_at = NOW(), transaction_id = ? WHERE commission_id = ?";
$stmt = mysqli_prepare($conn, $update);
mysqli_stmt_bind_param($stmt, "si", $ref_id, $commission_id);
mysqli_stmt_execute($stmt);

unset($_SESSION['pending_payment']);

$_SESSION['message'] = "Payment successful!";
$_SESSION['message_type'] = 'success';

header('Location: commission-details.php?id=' . $commission_id);
exit;
?>