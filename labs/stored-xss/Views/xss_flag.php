<?php

require_once __DIR__ . '/../vendor/autoload.php';


use Doctrine\DBAL\DriverManager;
use Stored\Helpers\Config;

$db = DriverManager::getConnection(Config::db());

$role = $db->fetchOne("SELECT role FROM users WHERE id = 1");

if ($role == 'admin') {
    echo file_get_contents(__DIR__ . '/../flag.txt');
}
else echo "Only admins allowed!";