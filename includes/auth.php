<?php
// Session Authentication Middleware
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function checkLoggedIn() {
    if (!isset($_SESSION['user_id'])) {
        $redirectUrl = (defined('SITE_URL') && SITE_URL !== '') ? SITE_URL . "/index.php" : "/index.php";
        header("Location: " . $redirectUrl);
        exit();
    }
}

function checkAlreadyLoggedIn() {
    if (isset($_SESSION['user_id'])) {
        header("Location: dashboard.php");
        exit();
    }
}

function getCurrentUser() {
    if (isset($_SESSION['user_id'])) {
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'] ?? 'Admin',
            'full_name' => $_SESSION['full_name'] ?? 'Business Owner',
            'role' => $_SESSION['role'] ?? 'admin',
            'email' => $_SESSION['email'] ?? 'admin@agritrak.com'
        ];
    }
    return null;
}
