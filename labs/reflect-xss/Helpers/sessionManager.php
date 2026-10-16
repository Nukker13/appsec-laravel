<?php


namespace Reflect\Helpers;
use Doctrine\DBAL\DriverManager;

class sessionManager
{
    public function __construct()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    public function login(): void
    {
        if (!empty($_SESSION))
        {
            return;
        }
        $db = DriverManager::getConnection(Config::db());

        $row = $db->fetchAssociative("SELECT email, role FROM xss_lab.users WHERE id = 1;");

        $_SESSION['id'] = 1;
        $_SESSION['email'] = $row['email'];
        $_SESSION['role'] = $row['role'];
    }

    public function adminLogin(): void
    {
        $_SESSION['id'] = 2;
        $_SESSION['email'] = "admin@example.com";
        $_SESSION['role'] = "admin";
    }

    public function logout(): void
    {
        $_SESSION = [];
        session_destroy();
    }

    public function set(string $key, mixed $val): void
    {
        $_SESSION[$key] = $val;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public function requireRole(string $role): void
    {
        if ($this->get('role') !== $role) {
            http_response_code(403);
            exit('Forbidden');
        }
    }
}