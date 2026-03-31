<?php
// process-payment.php
require_once 'includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_payment'])) {
    $commission_id = intval($_POST['commission_id']);
    $payment_method = $_POST['payment_method'];
    $transaction_id = $_POST['transaction_id'] ?? 'CASH-' . time();
    $user_id = $_SESSION['user_id'];
    
    // Verify this commission belongs to the user and is completed
    $check_sql = "SELECT * FROM commissions WHERE commission_id = ? AND user_id = ? AND status = 'completed'";
    $check_stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($check_stmt, "ii", $commission_id, $user_id);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    
    if (mysqli_num_rows($check_result) > 0) {
        // Update payment status
        $update_sql = "UPDATE commissions 
                       SET payment_status = 'paid', 
                           paid_at = NOW(),
                           transaction_id = ?,
                           payment_method = ?
                       WHERE commission_id = ?";
        
        $update_stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($update_stmt, "ssi", $transaction_id, $payment_method, $commission_id);
        
        if (mysqli_stmt_execute($update_stmt)) {
            // Add a message about payment
            $message = "Payment completed via " . $payment_method . ". Transaction ID: " . $transaction_id;
            $msg_sql = "INSERT INTO commission_messages (commission_id, user_id, message, created_at) 
                        VALUES (?, ?, ?, NOW())";
            $msg_stmt = mysqli_prepare($conn, $msg_sql);
            mysqli_stmt_bind_param($msg_stmt, "iis", $commission_id, $user_id, $message);
            mysqli_stmt_execute($msg_stmt);
            
            $_SESSION['message'] = "Payment confirmed successfully!";
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = "Error processing payment.";
            $_SESSION['message_type'] = 'error';
        }
    } else {
        $_SESSION['message'] = "Invalid commission or not completed yet.";
        $_SESSION['message_type'] = 'error';
    }
    
    header("Location: commission-details.php?id=$commission_id");
    exit;
} else {
    header('Location: commissions.php');
    exit;
}
?>