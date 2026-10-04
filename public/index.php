<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\ImportController;
use App\Controllers\RecordController;

require dirname(__DIR__) . '/vendor/autoload.php';

$root = dirname(__DIR__);
if (is_file($root . '/.env')) {
    Dotenv\Dotenv::createImmutable($root)->safeLoad();
}

$https = (($_SERVER['HTTPS'] ?? '') === 'on') || (($_SERVER['SERVER_PORT'] ?? '') === '443');
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'httponly' => true,
    'secure'   => $https,
    'samesite' => 'Lax',
]);
session_name('DVDDEMO');
session_start();

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if ($uri !== '/' && is_file(__DIR__ . $uri)) {
    return false; // let the built-in server serve static assets
}
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

$routes = [
    'GET /'                 => fn() => print DashboardController::index(),
    'GET /login'            => fn() => print AuthController::showLogin(),
    'POST /login'           => fn() => AuthController::login(),
    'POST /logout'          => fn() => AuthController::logout(),
    'GET /records'          => fn() => print RecordController::index(),
    'GET /records/show'     => fn() => print RecordController::show(),
    'POST /records/verify'  => fn() => RecordController::verify(),
    'POST /records/reject'  => fn() => RecordController::reject(),
    'GET /records/export'   => fn() => RecordController::export(),
    'GET /import'           => fn() => print ImportController::form(),
    'POST /import'          => fn() => print ImportController::upload(),
];

$key = $method . ' ' . ($uri === '/' ? '/' : rtrim($uri, '/'));

try {
    if (isset($routes[$key])) {
        $routes[$key]();
    } else {
        http_response_code(404);
        echo view('error', ['title' => 'Not found', 'message' => 'Page not found.']);
    }
} catch (Throwable $e) {
    $debug = env('APP_DEBUG', 'false') === 'true';
    $code = http_response_code();
    if (!is_int($code) || $code < 400) {
        http_response_code(500);
    }
    echo view('error', [
        'title'   => 'Error',
        'message' => $debug ? $e->getMessage() : 'Something went wrong.',
    ]);
}
