<?php

namespace OAUTH\Helpers;

class Config
{
    public static function db(): array
    {
        return [
            'dbname'   => 'oauth_lab',
            'user'     => 'root',
            'password' => 'root',
            'host'     => 'db',
            'port'     => 3306,
            'driver'   => 'pdo_mysql',
            'charset'  => 'utf8mb4',
        ];
    }
}
