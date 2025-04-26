<?php
require_once 'includes/functions.php';

// Initialize session
init_session();

// Unset all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to home page with message
redirect_with_message('/', 'Vous avez été déconnecté avec succès.', 'success');
