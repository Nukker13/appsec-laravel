<?php

declare(strict_types=1);

use OAUTH\Controllers\OAuthController;

require_once __DIR__ . "/vendor/autoload.php";

$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

$routes = [
    'GET'  => [
        '/oauth/provider'  => 'providerView',
        '/oauth/callback'  => 'callback',
        '/flag'            => 'flagView',
        '/form'            => 'reportView',
        '/next-report'     => 'nextReport',
        '/secret'          => 'adminLogin',
        '/logout'          => 'logout',
    ],
    'POST' => [
        '/oauth/start'         => 'start',
        '/oauth/provider'      => 'providerSubmit',
        '/oauth/social-start'  => 'socialStart',
        '/report'              => 'reportLink',
        '/reset'               => 'reset',
        '/logout'              => 'logout',
    ],
];

if ($uri === '/') {
    return;
}

$controller = new OAuthController();

if (isset($routes[$method][$uri])) {
    $action = $routes[$method][$uri];
    $controller->{$action}();
    exit;
}

http_response_code(404);
echo "Page not found";
