<?php
declare(strict_types=1);

session_start();

// Auto-login as regular user on first visit
if (empty($_SESSION['role'])) {
    $_SESSION['username'] = 'student';
    $_SESSION['email']    = 'student@shrimpcorp.internal';
    $_SESSION['role']     = 'user';
}

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if ($uri === '/flag') {
    // VULNERABLE: role check is intentionally absent.
    // Any user who navigates here directly sees the flag.
    include __DIR__ . '/views/flag.php';
} else {
    include __DIR__ . '/views/dashboard.php';
}
