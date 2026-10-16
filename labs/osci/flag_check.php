<?php

require_once __DIR__ . "/../../vendor/autoload.php";

use Doctrine\DBAL\DriverManager;
use Stored\Helpers\Config;

$conn = DriverManager::getConnection(Config::db());

if ($_POST['flag'] === file_get_contents('flag.txt')) {
    echo 'Success!';

    $conn->executeQuery("UPDATE labs SET is_completed = 1 WHERE name = 'osci';");
}
else echo 'Try again';