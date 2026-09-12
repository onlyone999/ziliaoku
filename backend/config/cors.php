<?php
/**
 * CORS配置
 * PHP 7.4兼容
 */

return [
    // 允许的来源 - 开发环境设为 ['*']，生产环境需限制
    'allowed_origins'   => ['*'],
    'allowed_methods'   => ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS'],
    'allowed_headers'   => ['Content-Type', 'Authorization', 'X-Requested-With', 'X-Token', 'Accept', 'Origin'],
    'exposed_headers'   => ['Content-Length', 'X-Request-Id'],
    'max_age'           => 86400, // 预检请求缓存24小时
    'allow_credentials' => false,
];

/**
 * 应用CORS头
 */
function applyCorsHeaders(array $config): void
{
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

    if (in_array('*', $config['allowed_origins'])) {
        header('Access-Control-Allow-Origin: *');
    } elseif ($origin && in_array($origin, $config['allowed_origins'])) {
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Vary: Origin');
    }

    header('Access-Control-Allow-Methods: ' . implode(', ', $config['allowed_methods']));
    header('Access-Control-Allow-Headers: ' . implode(', ', $config['allowed_headers']));

    if (!empty($config['exposed_headers'])) {
        header('Access-Control-Expose-Headers: ' . implode(', ', $config['exposed_headers']));
    }

    header('Access-Control-Max-Age: ' . (int)$config['max_age']);

    if ($config['allow_credentials']) {
        header('Access-Control-Allow-Credentials: true');
    }
}
