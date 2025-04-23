<?php
function logged_in_user_id() {
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
}

function require_login() {
    if (!logged_in_user_id()) {
        header("Location: index.php");
        exit;
    }
}

function set_message($msg) {
    $_SESSION['flash_msg'] = $msg;
}

function get_message() {
    if (!empty($_SESSION['flash_msg'])) {
        $msg = $_SESSION['flash_msg'];
        unset($_SESSION['flash_msg']);
        return $msg;
    }
    return "";
}

function redirect($url) {
    header("Location: $url");
    exit;
}