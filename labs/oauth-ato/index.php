<?php

declare(strict_types=1);

use OAUTH\Helpers\SessionManager;

require_once __DIR__ . "/router.php";

$session = new SessionManager();
$session->login();

include __DIR__ . '/Views/dashboard.php';
