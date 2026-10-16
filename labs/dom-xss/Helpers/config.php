<?php

namespace DOM\Helpers;

class config
{

    public static function db(): array
    {
        return [
            'dbname'   => 'xss_lab',
            'user'     => 'root',
            'password' => 'root',
            'host'     => 'db',
            'port'     => 3306,
            'driver'   => 'pdo_mysql',
            'charset'  => 'utf8mb4',

        ];
    }

}