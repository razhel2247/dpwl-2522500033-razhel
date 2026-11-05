<?php
session_start();
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);
define('APPPATH', FCPATH . 'application' . DIRECTORY_SEPARATOR);
define('SYSPATH', FCPATH . 'system' . DIRECTORY_SEPARATOR);

require_once APPPATH . 'config/config.php';
require_once APPPATH . 'config/database.php';
require_once APPPATH . 'helpers/url_helper.php';
require_once SYSPATH . 'core/Controller.php';
require_once SYSPATH . 'core/Router.php';

$route = [];
require APPPATH . 'config/routes.php';

// P6 menghilangkan penulisan index.php pada URL aplikasi

/* $uri = $_SERVER['PATH_INFO'] ?? '';

if ($uri === '') {
    $requestPath = parse_url(
            $_SERVER['REQUEST_URI'] ?? '/',
            PHP_URL_PATH
    ) ?? '/';

    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '/index.php';

        if (str_starts_with($requestPath, $scriptName)) {
            $uri = substr($requestPath, strlen($scriptName));
        }
} */
$uri = $_GET['route'] ?? ($_SERVER['PATH_INFO'] ?? '');

$router = new router($route);
$router->dispatch($uri);