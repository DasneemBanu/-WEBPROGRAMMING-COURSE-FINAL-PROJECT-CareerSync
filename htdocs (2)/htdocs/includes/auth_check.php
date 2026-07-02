<?php
require_once __DIR__ . '/session.php';

function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: /login.php');
        exit;
    }
}

function requireAdmin(): void
{
    requireLogin();

    if (currentUserRole() !== 'admin') {
        header('Location: /index.php');
        exit;
    }
}