<?php

declare(strict_types=1);

namespace OAUTH\Models;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use OAUTH\Helpers\Config;

class OAuthModel
{
    private Connection $db;

    public function __construct()
    {
        $this->db = DriverManager::getConnection(Config::db());
    }

    public function linkProvider(int $userId, string $provider, string $email): void
    {
        $column = match ($provider) {
            'google' => 'google_email',
            'apple'  => 'apple_email',
            default  => throw new \InvalidArgumentException("Unknown provider: $provider"),
        };

        $this->db->executeQuery(
            "UPDATE users SET {$column} = ? WHERE id = ?",
            [$email, $userId]
        );
    }

    public function findByProvider(string $provider, string $email): ?array
    {
        $column = match ($provider) {
            'google' => 'google_email',
            'apple'  => 'apple_email',
            default  => throw new \InvalidArgumentException("Unknown provider: $provider"),
        };

        $row = $this->db->fetchAssociative(
            "SELECT id, email, role FROM users WHERE {$column} = ?",
            [$email]
        );

        return $row ?: null;
    }

    public function resetProviders(int $userId): void
    {
        $this->db->executeQuery(
            "UPDATE users SET google_email = NULL, apple_email = NULL WHERE id = ?",
            [$userId]
        );
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

        $parts    = parse_url($raw);
        if ($parts === false) {
            return null;
        }
        $path     = $parts['path']  ?? '/';
        $query    = isset($parts['query'])    ? '?' . $parts['query']    : '';
        $fragment = isset($parts['fragment']) ? '#' . $parts['fragment'] : '';

        return $path . $query . $fragment;
    }
}
