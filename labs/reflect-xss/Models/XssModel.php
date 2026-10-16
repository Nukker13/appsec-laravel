<?php

namespace Reflect\Models;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Reflect\Helpers\config;
use InvalidArgumentException;

class XssModel
{
    private Connection $db;

    public function __construct()
    {
        $this->db = DriverManager::getConnection(config::db());
    }

    public function saveLink(string $url): void
    {
        $this->db->executeQuery(
            'INSERT INTO reports (url, created_at) VALUES (?, NOW())',
            [$url]
        );
    }

    public function getLink(): ?string
    {
        $row = $this->db->fetchAssociative("SELECT id, url FROM reports ORDER BY id LIMIT 1");

        if (!$row || empty($row['url'])) {
            return null;
        }

        $this->db->delete('reports', ['id' => $row['id']]);

        $raw = $row['url'];
        if (str_starts_with($raw, '/')) {
            return $raw;
        }

        $parts = parse_url($raw);
        if ($parts === false) {
            return null;
        }
        $path  = $parts['path']  ?? '/';
        $query = isset($parts['query']) ? '?' . $parts['query'] : '';

        return $path . $query;

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