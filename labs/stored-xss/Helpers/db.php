<?php

declare(strict_types=1);

namespace Helpers;
require_once __DIR__ . '/../vendor/autoload.php';

use Doctrine\DBAL\DriverManager;

class db
{
    public const LAB_USER_ID = 1;
    public const LAB_USER_EMAIL = 'student@example.com';

    function connect($config)
    {
        $db = DriverManager::getConnection($config);
    }
}
