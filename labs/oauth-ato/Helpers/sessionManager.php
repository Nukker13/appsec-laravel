<?php

namespace OAUTH\Helpers;

use Doctrine\DBAL\DriverManager;

class SessionManager
{
    public function __construct()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    public function login(): void
    {
        if (!empty($_SESSION['id'])) {
            return;
        }

        if (!empty($_COOKIE['_oauth_logged_out'])) {
            setcookie('_oauth_logged_out', '', time() - 3600, '/');
            return;
        }

        $db  = DriverManager::getConnection(Config::db());
        $row = $db->fetchAssociative("SELECT id, email, role FROM oauth_lab.users WHERE id = 1");

        $_SESSION['id']    = $row['id'];
        $_SESSION['email'] = $row['email'];
        $_SESSION['role']  = $row['role'];
    }

    public function loginUser(array $row): void
    {
        $_SESSION['id']    = $row['id'];
        $_SESSION['email'] = $row['email'];
        $_SESSION['role']  = $row['role'];
    }

    public function adminLogin(): void
    {
        $_SESSION['id']    = 2;
        $_SESSION['email'] = 'admin@example.com';
        $_SESSION['role']  = 'admin';
    }

    public function logout(): void
    {
        $_SESSION = [];
        session_destroy();
        setcookie('_oauth_logged_out', '1', time() + 300, '/');
    }

    public function requireRole(string $role): void
    {
        if (($_SESSION['role'] ?? null) !== $role) {
            http_response_code(403);
            exit('Forbidden');
        }
    }
}
