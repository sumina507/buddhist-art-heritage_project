<?php
require_once 'includes/config.php';

// Unset all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Clear remember token cookie if exists
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

// Redirect to login page
header('Location: login.php?logged_out=true');
exit;
?>