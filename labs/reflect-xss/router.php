<?php

declare(strict_types=1);

use Reflect\Controllers\XssController;

require_once __DIR__  . "/vendor/autoload.php";

const LAB_PREFIX = 'reflect-xss';

$routes = [
        'facts/dog' => ['showView', ['GET']],
        'facts/cat' => ['showView', ['GET']],
        'form' => ['reportView', ['GET']],
        'flag' => ['flagView', ['GET']],
        'secret' => ['adminLogin', ['GET']],
        'next-report' => ['nextReport', ['GET']],
        'report' => ['reportLink', ['POST']],
        'roleChange' => ['roleChange', ['POST']],

];

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

$parts = explode('/', $uri);

$action = $parts[1] ?? null;

if ($uri === '/' || $action === null) {
    return;
};

if ($action === "facts") {

    $param = $parts[2] ?? null;

        $controller = new XssController();

    if ($param === "404") {
        $controller->errorView();
        exit;
    }
    $controller->showView($param);
    exit;
}

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
