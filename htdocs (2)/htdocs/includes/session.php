<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!function_exists('loginUser')) {
    function loginUser($id, $name, $email, $role) {
        $_SESSION['user_id'] = $id;
        $_SESSION['user_name'] = $name;
        $_SESSION['user_email'] = $email;
        $_SESSION['role'] = $role;
    }
}

if (!function_exists('logoutUser')) {
    function logoutUser() {
        $_SESSION = [];
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        session_destroy();
    }
}

if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
}

if (!function_exists('currentUserId')) {
    function currentUserId() {
        return $_SESSION['user_id'] ?? null;
    }
}

if (!function_exists('currentUserRole')) {
    function currentUserRole() {
        return $_SESSION['role'] ?? 'user';
    }
}

if (!function_exists('currentUserName')) {
    function currentUserName() {
        return $_SESSION['user_name'] ?? 'Guest';
    }
}

if (!function_exists('currentUserEmail')) {
    function currentUserEmail() {
        return $_SESSION['user_email'] ?? '';
    }
}