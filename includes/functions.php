<?php
session_start();

// Clean input data
function clean_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Check if user is logged in
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Check user role
function has_role($role_id) {
    return is_logged_in() && $_SESSION['role_id'] == $role_id;
}

// Check if user is admin
function is_admin() {
    return has_role(1);
}

// Check if user is organizer
function is_organizer() {
    return has_role(2);
}

// Check if user is student
function is_student() {
    return has_role(3);
}

// Check if user is outsider
function is_outsider() {
    return has_role(4);
}

// Redirect to login if not logged in
function require_login() {
    if (!is_logged_in()) {
        header("Location: login.php");
        exit;
    }
}

// Redirect if not admin
function require_admin() {
    require_login();
    if (!is_admin()) {
        header("Location: index.php");
        exit;
    }
}

// Redirect if not admin or organizer
function require_admin_or_organizer() {
    require_login();
    if (!is_admin() && !is_organizer()) {
        header("Location: index.php");
        exit;
    }
}

// Display alert message
function display_message($message, $type = 'success') {
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $type;
}

// Generate a random string
function generate_random_string($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $random_string = '';
    for ($i = 0; $i < $length; $i++) {
        $random_string .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $random_string;
}
?> 