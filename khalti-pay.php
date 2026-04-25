<?php
require_once 'includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$commission_id = isset($_GET['commission_id']) ? intval($_GET['commission_id']) : 0;
$payment_type = isset($_GET['type']) ? $_GET['type'] : 'advance';

// Get commission details
$sql = "SELECT * FROM commissions WHERE commission_id = ? AND user_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $commission_id, $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$commission = mysqli_fetch_assoc($result);

if (!$commission) {
    $_SESSION['message'] = "Commission not found.";
    header('Location: commissions.php');
    exit;
}

$total_amount = $commission['budget'] ?? 0;
$advance_amount = $total_amount / 2;
$remaining_amount = $total_amount / 2;

if ($payment_type == 'advance') {
    $amount = $advance_amount;
} else {
    $amount = $remaining_amount;
}

// Amount must be in paisa (multiply by 100)
$amount_paisa = $amount * 100;

// Generate unique purchase order ID
$purchase_order_id = 'COMM_' . $commission_id . '_' . $payment_type . '_' . time();

// Store in session for later verification
$_SESSION['khalti_payment'] = [
    'commission_id' => $commission_id,
    'amount' => $amount,
    'payment_type' => $payment_type,
    'purchase_order_id' => $purchase_order_id
];

// YOUR KHALTI KEYS (from your screenshot)
$public_key = "79cefc912b3546bb8e6a782eeb0fb991";
$secret_key = "64ab09ee62944e24ad4e8e9520f13b9a";
$khalti_api_url = "https://dev.khalti.com/api/v2/epayment/initiate/";

// Prepare data for Khalti API
$post_data = [
    'return_url' => SITE_URL . 'khalti-success.php',
    'website_url' => SITE_URL,
    'amount' => $amount_paisa,
    'purchase_order_id' => $purchase_order_id,
    'purchase_order_name' => $commission['title'],
    'customer_info' => [
        'name' => $_SESSION['full_name'] ?? $_SESSION['username'],
        'email' => $_SESSION['email']
    ]
];

// Initialize cURL
$ch = curl_init($khalti_api_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Key ' . $secret_key
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code != 200) {
    $_SESSION['message'] = "Payment initiation failed. Please use Demo mode.";
    $_SESSION['message_type'] = 'error';
    header('Location: commission-details.php?id=' . $commission_id);
    exit;
}

$response_data = json_decode($response, true);

if (!isset($response_data['payment_url'])) {
    $_SESSION['message'] = "Invalid response from Khalti. Please use Demo mode.";
    $_SESSION['message_type'] = 'error';
    header('Location: commission-details.php?id=' . $commission_id);
    exit;
}

// Redirect user to Khalti payment page
header('Location: ' . $response_data['payment_url']);
exit;
?>