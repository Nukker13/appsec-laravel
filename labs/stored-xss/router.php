<?php

declare(strict_types=1);

use Stored\Controllers\XssController;

require_once __DIR__  . "/vendor/autoload.php";

const LAB_PREFIX = 'stored-xss';

$routes = [
        'admin' => ['adminView', ['GET']],
        'student' => ['safeView', ['GET']],
        'flag' => ['flagView', ['GET']],
        'message' => ['storeMessage', ['POST']],
        'roleChange' => ['roleChange', ['POST']],
        'secret' => ['adminLogin', ['GET']],

];

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];


$parts = explode('/', $uri);
$action = $parts[1];

if ($uri === '/' || $action === null) {
return;
};

if ($action && isset($routes[$action])) {
    [$controllerMethod, $allowedMethods] = $routes[$action];

    if (in_array($method, $allowedMethods, true)) {
        $controller = new XssController();
        $controller->{$controllerMethod}();
        exit;
    }
}

http_response_code(404);
echo "Page not found";
