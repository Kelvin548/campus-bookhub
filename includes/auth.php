<?php
// includes/auth.php

if (!defined('BASE_URL')) {
    define('BASE_URL', '/campus_bookhub/');
}

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => false, // Set to true if using HTTPS
        'httponly' => true,  // Protects against XSS session theft
        'samesite' => 'Lax'  // Protects against CSRF
    ]);
    session_start();
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// CSRF Verification
function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Login Checks
function is_logged_in() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_role']);
}

function is_admin() {
    return is_logged_in() && $_SESSION['user_role'] === 'admin';
}

function is_student() {
    return is_logged_in() && $_SESSION['user_role'] === 'student';
}

// Route Protection Enforcers
function require_student() {
    if (!is_student()) {
        header("Location: " . BASE_URL . "login.php");
        exit();
    }
}

function require_admin() {
    if (!is_admin()) {
        header("Location: " . BASE_URL . "login.php");
        exit();
    }
}

// Helper Badge Utility
function get_status_badge_class(string $status): string {
    return match (strtolower($status)) {
        'pending'             => 'bg-warning-subtle text-warning-emphasis',
        'verified', 'approved', 
        'collected'           => 'bg-success-subtle text-success',
        'rejected', 'declined'=> 'bg-danger-subtle text-danger',
        default               => 'bg-secondary-subtle text-secondary',
    };
}
?>