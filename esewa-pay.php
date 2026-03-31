<?php
// esewa-pay.php - eSewa payment initiation for commissions
require_once 'includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$commission_id = isset($_GET['commission_id']) ? intval($_GET['commission_id']) : 0;
$user_id = $_SESSION['user_id'];

// Get commission details
$sql = "SELECT * FROM commissions WHERE commission_id = ? AND user_id = ? AND payment_status = 'pending'";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $commission_id, $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$commission = mysqli_fetch_assoc($result);

if (!$commission) {
    $_SESSION['message'] = "Commission not found or already paid.";
    $_SESSION['message_type'] = 'error';
    header('Location: commissions.php');
    exit;
}

$amount = $commission['budget'] ?? 0;
$tax_amount = 0;
$service_charge = 0;
$delivery_charge = 0;
$total_amount = $amount;

// Generate unique transaction ID
$transaction_uuid = 'COMM_' . $commission_id . '_' . time();

// Product code for test mode
$product_code = "EPAYTEST";

$signed_field_names = "total_amount,transaction_uuid,product_code";
$message = "total_amount=$total_amount,transaction_uuid=$transaction_uuid,product_code=$product_code";
$secret = "8gBm/:&EnhH.1/q";
$s = hash_hmac('sha256', $message, $secret, true);
$signature = base64_encode($s);

$_SESSION['pending_payment'] = [
    'commission_id' => $commission_id,
    'transaction_uuid' => $transaction_uuid,
    'amount' => $total_amount
];

$base_url = "http://localhost/buddhist-art-project/";
?>

<!DOCTYPE html>
<html>
<head>
    <title>eSewa Payment</title>
    <style>
        body { font-family: Arial; background: #f5f5f5; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        .container { background: white; padding: 30px; border-radius: 10px; text-align: center; max-width: 400px; }
        .amount { font-size: 24px; color: #e74c3c; margin: 20px 0; }
        button { background: #4CAF50; color: white; border: none; padding: 12px 25px; border-radius: 5px; cursor: pointer; width: 100%; font-size: 16px; }
        .test-info { background: #e8f4fc; padding: 10px; margin-top: 20px; border-radius: 5px; font-size: 12px; text-align: left; }
    </style>
</head>
<body>
    <div class="container">
        <h2>eSewa Payment</h2>
        <p>Commission #<?php echo $commission_id; ?></p>
        <div class="amount">NRs <?php echo number_format($total_amount, 2); ?></div>
        
        <form action="https://rc-epay.esewa.com.np/api/epay/main/v2/form" method="POST">
            <input type="hidden" name="amount" value="<?php echo $amount; ?>">
            <input type="hidden" name="tax_amount" value="<?php echo $tax_amount; ?>">
            <input type="hidden" name="total_amount" value="<?php echo $total_amount; ?>">
            <input type="hidden" name="transaction_uuid" value="<?php echo $transaction_uuid; ?>">
            <input type="hidden" name="product_code" value="<?php echo $product_code; ?>">
            <input type="hidden" name="product_service_charge" value="<?php echo $service_charge; ?>">
            <input type="hidden" name="product_delivery_charge" value="<?php echo $delivery_charge; ?>">
            <input type="hidden" name="success_url" value="<?php echo $base_url; ?>esewa-success.php">
            <input type="hidden" name="failure_url" value="<?php echo $base_url; ?>esewa-failure.php">
            <input type="hidden" name="signed_field_names" value="<?php echo $signed_field_names; ?>">
            <input type="hidden" name="signature" value="<?php echo $signature; ?>">
            <button type="submit">Pay with eSewa</button>
        </form>
        
        <div class="test-info">
            <strong>Test Credentials (exactly):</strong><br>
            Mobile: 9800000000<br>
            Password: 123456<br>
            MPIN: 1111
        </div>
    </div>
</body>
</html>