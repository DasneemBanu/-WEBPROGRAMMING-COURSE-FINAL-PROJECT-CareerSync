<?php
require_once __DIR__ . '/session.php';

if (!isLoggedIn() || currentUserRole() !== 'admin') {
    header('Location: /login.php');
    exit;
}
