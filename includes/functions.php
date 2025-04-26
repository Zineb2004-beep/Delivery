<?php
// Start session if not already started
function init_session() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

// Sanitize input
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Validate email
function is_valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Check if user is logged in
function is_logged_in() {
    init_session();
    return isset($_SESSION['user_id']);
}

// Check user role
function has_role($required_role) {
    init_session();
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $required_role;
}

// Redirect with message
function redirect_with_message($url, $message, $type = 'success') {
    init_session();
    $_SESSION['flash'] = [
        'message' => $message,
        'type' => $type
    ];
    header("Location: $url");
    exit();
}

// Display flash message
function display_flash_message() {
    init_session();
    if (isset($_SESSION['flash'])) {
        $message = $_SESSION['flash']['message'];
        $type = $_SESSION['flash']['type'];
        unset($_SESSION['flash']);
        
        return "<div class='alert alert-{$type} alert-dismissible fade show' role='alert'>
                    {$message}
                    <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                </div>";
    }
    return '';
}

// Require authentication
function require_auth() {
    if (!is_logged_in()) {
        redirect_with_message('/login.php', 'Veuillez vous connecter pour accéder à cette page.', 'warning');
    }
}

// Require specific role
function require_role($role) {
    require_auth();
    if (!has_role($role)) {
        redirect_with_message('/', 'Accès non autorisé.', 'danger');
    }
}
