<?php
session_start();

$requestUri = $_SERVER['REQUEST_URI'];

if (strpos($requestUri, '/api/') === 0) {
    require __DIR__ . '/../api/index.php';
    exit;
}

$isLoggedIn = isset($_SESSION['user_id']);


$routes = [
    '/' => 'dashboard.php',
    '/login' => 'login.php',
    '/dashboard' => 'dashboard.php',
    '/users' => 'users.php',
    '/sessions' => 'sessions.php',
    '/stats' => 'stats.php',
    '/profile' => 'profile.php',
    '/requests' => 'requests.php',
];

$view = isset($routes[$requestUri]) ? $routes[$requestUri] : '404.php';

if (!$isLoggedIn && $view !== 'login.php') {
    header('Location: /login');
    exit;
}

if ($isLoggedIn && $view === 'login.php') {
    header('Location: /dashboard');
    exit;
}

require __DIR__ . '/../views/layout.php';
