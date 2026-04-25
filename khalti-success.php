<?php
require_once 'includes/config.php';

$secret_key = "64ab09ee62944e24ad4e8e9520f13b9a";
$khalti_verify_url = "https://dev.khalti.com/api/v2/epayment/lookup/";

$pidx = $_GET['pidx'] ?? '';
$tidx = $_GET['tidx'] ?? '';

if (!isset($_SESSION['khalti_payment'])) {
    $_SESSION['message'] = "Invalid payment session.";
    header('Location: commissions.php');
    exit;
}

$commission_id = $_SESSION['khalti_payment']['commission_id'];
$payment_type = $_SESSION['khalti_payment']['payment_type'];
$expected_amount = $_SESSION['khalti_payment']['amount'];

// Verify payment with Khalti
$ch = curl_init($khalti_verify_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['pidx' => $pidx]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Key ' . $secret_key
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code != 200) {
    $_SESSION['message'] = "Payment verification failed.";
    $_SESSION['message_type'] = 'error';
    header('Location: commission-details.php?id=' . $commission_id);
    exit;
}

$verify_data = json_decode($response, true);

if ($verify_data['status'] !== 'Completed') {
    $_SESSION['message'] = "Payment not completed.";
    $_SESSION['message_type'] = 'error';
    header('Location: commission-details.php?id=' . $commission_id);
    exit;
}

// Update commission based on payment type
if ($payment_type == 'advance') {
    $update = "UPDATE commissions SET 
              advance_paid = 1, 
              advance_paid_at = NOW(), 
              advance_transaction_id = ?,
              status = 'in_progress'
              WHERE commission_id = ?";
} else {
    $update = "UPDATE commissions SET 
              remaining_paid = 1, 
              remaining_paid_at = NOW(), 
              remaining_transaction_id = ?,
              payment_status = 'completed',
              status = 'completed'
              WHERE commission_id = ?";
}

$stmt = mysqli_prepare($conn, $update);
mysqli_stmt_bind_param($stmt, "si", $pidx, $commission_id);
mysqli_stmt_execute($stmt);

unset($_SESSION['khalti_payment']);

$_SESSION['message'] = "Payment successful!";
$_SESSION['message_type'] = 'success';
header('Location: commission-details.php?id=' . $commission_id);
exit;
?>