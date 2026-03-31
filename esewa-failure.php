<?php
// esewa-failure.php - eSewa payment failure callback
require_once 'includes/config.php';

// Get commission_id from session or GET
$commission_id = $_SESSION['pending_payment']['commission_id'] ?? ($_GET['commission_id'] ?? 0);

// Clear pending payment
if (isset($_SESSION['pending_payment'])) {
    unset($_SESSION['pending_payment']);
}

$_SESSION['message'] = "Payment failed. Please try again or choose another payment method.";
$_SESSION['message_type'] = 'error';

header('Location: commission-details.php?id=' . $commission_id);
exit;
?>