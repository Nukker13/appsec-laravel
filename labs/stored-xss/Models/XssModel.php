<?php

namespace Stored\Models;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Stored\Helpers\config;
use InvalidArgumentException;

class XssModel
{
    private Connection $db;

    public function __construct()
    {
        $this->db = DriverManager::getConnection(config::db());
    }

    public function saveMessage(?int $userId, string $email, string $message): void
    {
        $this->db->executeQuery(
            'INSERT INTO messages (user_id, email, content) VALUES (?, ?, ?)',
            [$userId, $email, $message]
        );
    }

    public function getMessages(): array
    {
        return $this->db->fetchAllAssociative(
            'SELECT user_id, content, created_at FROM messages ORDER BY id DESC'
        );
    }

    public function roleChange(int $userId, string $role): void
    {
        $roles = ['user', 'admin'];
        if (!in_array($role, $roles, true)) {
            throw new InvalidArgumentException("No such role" . $role);
        }

        $this->db->executeQuery(
            'UPDATE users SET role = ? WHERE id = ?',
            [$role, $userId]
        );
    }
}