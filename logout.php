<?php
require_once 'php/config.php';

// Log the logout event
if (isset($_SESSION['user_id'])) {
    error_log("User logged out: " . $_SESSION['user_email']);
}

// Destroy all session data
$_SESSION = array();

// Delete session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Delete remember me cookie
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

// Destroy the session
session_destroy();

// Redirect to login page with success message
header("Location: login.php?logout=success");
exit();
?>