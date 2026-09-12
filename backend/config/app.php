<?php
/**
 * 应用配置
 * PHP 7.4兼容
 */

return [
    // 应用信息
    'app_name'  => '资料库',
    'app_key'   => getenv('APP_KEY') ?: 'ziliaoku_secret_key_2026',
    'debug'     => filter_var(getenv('APP_DEBUG') ?: true, FILTER_VALIDATE_BOOLEAN),
    'timezone'  => 'Asia/Shanghai',
    'version'   => '1.0.0',

    // 微信小程序
    'wechat_mini_appid'  => getenv('WECHAT_MINI_APPID')  ?: '',
    'wechat_mini_secret' => getenv('WECHAT_MINI_SECRET') ?: '',
    'wechat_mch_id'      => getenv('WECHAT_MCH_ID')      ?: '',
    'wechat_api_key'     => getenv('WECHAT_API_KEY')     ?: '',
    'wechat_notify_url'  => getenv('WECHAT_NOTIFY_URL')  ?: 'https://yourdomain.com/api/pay/notify',

    // 上传
    'upload_path'        => __DIR__ . '/../uploads/',
    'upload_url'         => '/uploads/',
    'allowed_file_types' => [
        'image'    => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg', 'ico'],
        'document' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'md', 'csv', 'rtf', 'odt', 'ods', 'odp'],
        'archive'  => ['zip', 'rar', '7z', 'tar', 'gz', 'bz2'],
        'video'    => ['mp4', 'avi', 'mov', 'wmv', 'flv', 'mkv', 'webm'],
        'audio'    => ['mp3', 'wav', 'ogg', 'flac', 'aac', 'wma', 'm4a'],
        'code'     => ['html', 'htm', 'css', 'js', 'ts', 'jsx', 'tsx', 'vue', 'php', 'py', 'java', 'c', 'cpp', 'h', 'hpp', 'cs', 'go', 'rs', 'rb', 'swift', 'kt', 'sh', 'bat', 'sql', 'json', 'xml', 'yaml', 'yml', 'toml', 'ini', 'conf', 'log', 'r', 'lua', 'perl', 'pl'],
    ],
    'max_upload_size'    => 100 * 1024 * 1024, // 100MB

    // JWT
    'jwt_secret'  => getenv('JWT_SECRET') ?: 'ziliaoku_jwt_hmac_sha256_2026',
    'jwt_expire'  => 7200, // 2小时（秒）
    'jwt_issuer'  => 'ziliaoku',

    // 分页
    'default_page_size' => 20,
    'max_page_size'     => 100,

    // 速率限制
    'rate_limit'     => 60,    // 每个窗口的请求数
    'rate_window'    => 60,    // 窗口时间（秒）

    // 管理员
    'admin_default_user'     => 'admin',
    'admin_default_password' => 'admin123',
];
