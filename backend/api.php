<?php
/**
 * Main API Router
 * PHP 7.4 Compatible
 *
 * Route format: /api/{controller}/{action}
 * Example: /api/user/login -> controllers/UserController.php -> login()
 */

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
date_default_timezone_set('Asia/Shanghai');

// Load core classes
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/Response.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/Upload.php';
require_once __DIR__ . '/core/WechatPay.php';
require_once __DIR__ . '/core/Validator.php';
require_once __DIR__ . '/core/UrlHelper.php';

// Apply CORS headers
$corsConfig = require __DIR__ . '/config/cors.php';
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array('*', $corsConfig['allowed_origins'])) {
    header('Access-Control-Allow-Origin: *');
} elseif ($origin && in_array($origin, $corsConfig['allowed_origins'])) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Vary: Origin');
}
header('Access-Control-Allow-Methods: ' . implode(', ', $corsConfig['allowed_methods']));
header('Access-Control-Allow-Headers: ' . implode(', ', $corsConfig['allowed_headers']));
header('Access-Control-Max-Age: ' . (int) $corsConfig['max_age']);

// 临时调试：记录 POST 请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && strpos($_SERVER['REQUEST_URI'] ?? '', 'download') !== false) {
    $dbg = date('H:i:s') . ' | ' . ($_SERVER['REQUEST_URI'] ?? '') . ' | CT=' . ($_SERVER['CONTENT_TYPE'] ?? 'N/A') . ' | POST=' . json_encode($_POST) . ' | INPUT=' . substr(file_get_contents('php://input'), 0, 300) . "\n";
    file_put_contents('E:/ziliaoku/runtime/api_debug.log', $dbg, FILE_APPEND);
}

// Parse request URI from REQUEST_URI (works with both /api/xxx and /api.php/xxx)
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';

// Remove query string
$qpos = strpos($requestUri, '?');
if ($qpos !== false) {
    $requestUri = substr($requestUri, 0, $qpos);
}

// Clean and normalize
$requestUri = '/' . trim($requestUri, '/');

// Remove /api prefix (handles both /api/xxx and /api.php/xxx)
$requestUri = preg_replace('#^/?api(\.php)?/?#', '', $requestUri);
if (empty($requestUri)) {
    $requestUri = '/';
} else {
    $requestUri = '/' . trim($requestUri, '/');
}

// Parse controller and action
$segments = array_values(array_filter(explode('/', $requestUri)));
$controllerName = !empty($segments[0]) ? $segments[0] : 'index';
$actionName     = !empty($segments[1]) ? $segments[1] : 'index';

// Default route
if ($requestUri === '/' || $requestUri === '') {
    Response::success([
        'name'    => '资料库 API',
        'version' => '1.0.0',
        'time'    => date('Y-m-d H:i:s'),
    ], '资料库 API 运行正常');
}

// Extract extra path parameters (e.g., /user/profile/123 -> id=123)
$pathParams = [];
if (count($segments) > 2) {
    $extraSegments = array_slice($segments, 2);
    for ($i = 0; $i < count($extraSegments); $i += 2) {
        $key = $extraSegments[$i];
        $value = $extraSegments[$i + 1] ?? null;
        $pathParams[$key] = $value;
    }
}

// Convert hyphenated names to camelCase (e.g., wx-login -> wxLogin, my-controller -> myController)
$actionName = preg_replace_callback('/-([a-zA-Z])/', function($m) { return strtoupper($m[1]); }, $actionName);
$controllerName = preg_replace_callback('/-([a-zA-Z])/', function($m) { return strtoupper($m[1]); }, $controllerName);

// Sanitize controller/action names (prevent directory traversal)
$controllerName = preg_replace('/[^a-zA-Z0-9_]/', '', $controllerName);
$actionName     = preg_replace('/[^a-zA-Z0-9_]/', '', $actionName);

if (empty($controllerName)) {
    $controllerName = 'index';
}
if (empty($actionName)) {
    $actionName = 'index';
}

// Normalize controller name (e.g., auth -> Auth)
$controllerName = ucfirst($controllerName);

// Build controller file path
$controllerFile = __DIR__ . '/controllers/' . $controllerName . 'Controller.php';
$controllerClass = $controllerName . 'Controller';

// Check if controller exists
if (!file_exists($controllerFile)) {
    Response::notFound('接口不存在: /' . $controllerName . '/' . $actionName);
}

// Load controller
require_once $controllerFile;

// Verify class exists
if (!class_exists($controllerClass)) {
    Response::serverError('控制器类不存在: ' . $controllerClass);
}

// Instantiate controller
try {
    $controller = new $controllerClass();
} catch (Throwable $e) {
    Response::serverError('控制器实例化失败: ' . $e->getMessage());
}

// Check action method exists
if (!method_exists($controller, $actionName)) {
    Response::notFound('操作不存在: ' . $controllerName . '/' . $actionName);
}

// Merge request data
$_REQUEST_DATA = [];

// GET params
$_REQUEST_DATA = array_merge($_REQUEST_DATA, $_GET);

// POST params (JSON body or form data)
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (stripos($contentType, 'application/json') !== false) {
    $rawBody = file_get_contents('php://input');
    $jsonData = json_decode($rawBody, true);
    if (is_array($jsonData)) {
        $_REQUEST_DATA = array_merge($_REQUEST_DATA, $jsonData);
        // Also populate $_POST so controllers reading $_POST work with JSON requests
        $_POST = array_merge($_POST, $jsonData);
    }
} else {
    $_REQUEST_DATA = array_merge($_REQUEST_DATA, $_POST);
}

// Add path parameters
$_REQUEST_DATA = array_merge($_REQUEST_DATA, $pathParams);

// Store in global for controllers to access
$GLOBALS['REQUEST_DATA'] = $_REQUEST_DATA;

// Execute action
try {
    // 调试：记录请求路由
if (strpos($_SERVER['REQUEST_URI'] ?? '', 'download') !== false) {
    file_put_contents('E:/ziliaoku/runtime/api_debug.log', date('H:i:s') . ' | ROUTE: ' . $controllerClass . '->' . $actionName . ' | ID=' . ($GLOBALS['REQUEST_DATA']['resource_id'] ?? 'N/A') . '
', FILE_APPEND);
}
call_user_func([$controller, $actionName]);
} catch (Throwable $e) {
    $config = require __DIR__ . '/config/app.php';
    if ($config['debug']) {
        Response::error(
            $e->getMessage(),
            500,
            500,
            [
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]
        );
    } else {
        Response::serverError();
    }
}
