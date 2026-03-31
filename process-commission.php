<?php
require_once 'includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $artist_id = intval($_POST['artist_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $budget = !empty($_POST['budget']) ? floatval($_POST['budget']) : NULL;
    $deadline = !empty($_POST['deadline']) ? $_POST['deadline'] : NULL;
    $payment_method = trim($_POST['payment_method'] ?? 'eSewa');
    
    // Validation
    $errors = [];
    
    if (!$artist_id) {
        $errors[] = "Please select an artist";
    }
    
    if (empty($title)) {
        $errors[] = "Title is required";
    }
    
    if (empty($description)) {
        $errors[] = "Description is required";
    }
    
    // Validate deadline is not in the past
    if ($deadline && strtotime($deadline) < strtotime(date('Y-m-d'))) {
        $errors[] = "Deadline cannot be in the past. Please select a future date.";
    }
    
    // Check if artist exists and is approved
    $check_artist = "SELECT artist_id, user_id FROM artists WHERE artist_id = ? AND status = 'approved'";
    $stmt = mysqli_prepare($conn, $check_artist);
    mysqli_stmt_bind_param($stmt, "i", $artist_id);
    mysqli_stmt_execute($stmt);
    $artist_result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($artist_result) == 0) {
        $errors[] = "Selected artist not found or not approved";
    }
    
    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        header('Location: commission-request.php' . ($artist_id ? "?artist_id=$artist_id" : ''));
        exit;
    }
    
    // Insert commission
    $sql = "INSERT INTO commissions (user_id, artist_id, title, description, budget, deadline, payment_method, status, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "iisssss", $user_id, $artist_id, $title, $description, $budget, $deadline, $payment_method);
    
    if (mysqli_stmt_execute($stmt)) {
        $commission_id = mysqli_insert_id($conn);
        
        // Add initial message about budget and negotiation
        $message = "Commission request created.\n\n";
        $message .= "Title: $title\n";
        $message .= "Description: $description\n";
        $message .= "Budget: " . ($budget ? "NRs " . number_format($budget, 2) : "To be discussed") . "\n";
        if ($deadline) {
            $message .= "Deadline: " . date('M d, Y', strtotime($deadline)) . "\n";
        }
        $message .= "Payment Method: $payment_method\n\n";
        $message .= "If the budget needs adjustment, please discuss through messages. Nothing is final until both parties agree.";
        
        $msg_sql = "INSERT INTO commission_messages (commission_id, user_id, message, created_at) VALUES (?, ?, ?, NOW())";
        $msg_stmt = mysqli_prepare($conn, $msg_sql);
        mysqli_stmt_bind_param($msg_stmt, "iis", $commission_id, $user_id, $message);
        mysqli_stmt_execute($msg_stmt);
        
        $_SESSION['message'] = "Commission request submitted successfully! The artist will review it and discuss details with you.";
        $_SESSION['message_type'] = 'success';
        header('Location: commissions.php');
        exit;
    } else {
        $_SESSION['message'] = "Error submitting request: " . mysqli_error($conn);
        $_SESSION['message_type'] = 'error';
        header('Location: commission-request.php' . ($artist_id ? "?artist_id=$artist_id" : ''));
        exit;
    }
} else {
    header('Location: commission-request.php');
    exit;
}
?>