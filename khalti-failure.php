<?php
require_once 'includes/config.php';

$commission_id = $_SESSION['khalti_payment']['commission_id'] ?? 0;
unset($_SESSION['khalti_payment']);

$_SESSION['message'] = "Payment failed. Please try again.";
$_SESSION['message_type'] = 'error';
header('Location: commission-details.php?id=' . $commission_id);
exit;
?>