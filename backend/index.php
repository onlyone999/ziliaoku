<?php
/**
 * Entry Point - Routes to api.php
 * PHP 7.4 Compatible
 */

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Set timezone
date_default_timezone_set('Asia/Shanghai');

// Load autoloader
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/Response.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/Upload.php';
require_once __DIR__ . '/core/WechatPay.php';
require_once __DIR__ . '/core/Validator.php';

// CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    $corsConfig = require __DIR__ . '/config/cors.php';
    require __DIR__ . '/config/cors.php';

    http_response_code(204);
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-Token');
    header('Access-Control-Max-Age: 86400');
    exit;
}

// Global error handler
set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline) {
    $config = require __DIR__ . '/config/app.php';
    if ($config['debug']) {
        Response::error("[$errno] $errstr in $errfile:$errline", 500, 500);
    } else {
        Response::serverError();
    }
});

set_exception_handler(function (Throwable $e) {
    $config = require __DIR__ . '/config/app.php';
    if ($config['debug']) {
        Response::error($e->getMessage(), 500, 500, [
            'file'  => $e->getFile(),
            'line'  => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ]);
    } else {
        Response::serverError();
    }
});

// Route to API handler
require_once __DIR__ . '/api.php';
