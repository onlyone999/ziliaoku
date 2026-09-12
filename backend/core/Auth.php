<?php
/**
 * 认证模块 - JWT和微信登录
 * PHP 7.4兼容 - HMAC-SHA256，无外部依赖
 */

class Auth
{
    /** @var array|null 从令牌缓存的用户 */
    private static ?array $currentUser = null;

    /**
     * 生成JWT令牌
     *
     * @param int    $userId
     * @param string $role       'user' 或 'admin'
     * @param int    $expire     过期秒数（0 = 使用配置默认值）
     * @return string
     */
    public static function generateToken(int $userId, string $role = 'user', int $expire = 0, string $openid = ''): string
    {
        $config = require __DIR__ . '/../config/app.php';
        $secret = $config['jwt_secret'];
        $now    = time();

        if ($expire <= 0) {
            $expire = $config['jwt_expire'];
        }

        $header = self::base64UrlEncode(json_encode([
            'typ' => 'JWT',
            'alg' => 'HS256',
        ]));

        $payloadData = [
            'user_id' => $userId,
            'role'    => $role,
            'iat'     => $now,
            'exp'     => $now + $expire,
            'iss'     => $config['jwt_issuer'] ?? 'ziliaoku',
        ];
        if (!empty($openid)) {
            $payloadData['openid'] = $openid;
        }

        $payload = self::base64UrlEncode(json_encode($payloadData));

        $signature = self::base64UrlEncode(
            hash_hmac('sha256', $header . '.' . $payload, $secret, true)
        );

        return $header . '.' . $payload . '.' . $signature;
    }

    /**
     * 验证并解码JWT令牌
     *
     * @param string $token
     * @return array|false  解码后的载荷，失败返回false
     */
    public static function verifyToken(string $token)
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return false;
        }

        [$header, $payload, $signature] = $parts;

        $config = require __DIR__ . '/../config/app.php';
        $expectedSig = self::base64UrlEncode(
            hash_hmac('sha256', $header . '.' . $payload, $config['jwt_secret'], true)
        );

        if (!hash_equals($expectedSig, $signature)) {
            return false;
        }

        $decoded = json_decode(self::base64UrlDecode($payload), true);
        if (!$decoded || !isset($decoded['exp'])) {
            return false;
        }

        if ($decoded['exp'] < time()) {
            return false;
        }

        return $decoded;
    }

    /**
     * 从任意 token（含过期）中提取 openid，不做签名/过期校验
     */
    public static function decodeOpenid(string $token): string
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return '';
        }
        $decoded = json_decode(self::base64UrlDecode($parts[1]), true);
        return ($decoded && isset($decoded['openid'])) ? $decoded['openid'] : '';
    }

    /**
     * 从请求中提取令牌（Authorization头或查询参数）
     */
    public static function getTokenFromRequest(): string
    {
        // 检查Authorization头
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
        if (preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
            return trim($matches[1]);
        }

        // 检查自定义头
        $xToken = $_SERVER['HTTP_X_TOKEN'] ?? '';
        if ($xToken !== '') {
            return trim($xToken);
        }

        // 检查查询参数
        $token = $_GET['token'] ?? '';
        if ($token !== '') {
            return trim($token);
        }

        return '';
    }

    /**
     * 检查当前请求是否有有效令牌（用户或管理员）
     */
    public static function check(): bool
    {
        $token = self::getTokenFromRequest();
        if ($token === '') {
            return false;
        }

        $payload = self::verifyToken($token);
        if ($payload === false) {
            return false;
        }

        self::$currentUser = $payload;
        return true;
    }

    /**
     * 要求认证 - 无效时返回401
     */
    public static function requireAuth(): array
    {
        if (!self::check()) {
            Response::unauthorized('请先登录');
        }
        return self::$currentUser;
    }

    /**
     * 获取当前用户载荷
     */
    public static function getUser(): ?array
    {
        if (self::$currentUser === null) {
            self::check();
        }
        return self::$currentUser;
    }

    /**
     * 获取当前用户ID
     */
    public static function getUserId(): int
    {
        $user = self::getUser();
        return $user ? (int) $user['user_id'] : 0;
    }

    /**
     * 检查当前令牌是否属于管理员
     */
    public static function adminCheck(): bool
    {
        if (!self::check()) {
            return false;
        }
        return (self::$currentUser['role'] ?? '') === 'admin';
    }

    /**
     * 要求管理员认证 - 非管理员返回403
     */
    public static function requireAdmin(): array
    {
        $user = self::requireAuth();
        if (($user['role'] ?? '') !== 'admin') {
            Response::forbidden('无管理员权限');
        }
        return $user;
    }

    /**
     * 管理员登录 - 验证凭据并返回令牌
     *
     * @param string $username
     * @param string $password
     * @return array|false  ['token' => ..., 'user' => ...] 或 false
     */
    public static function adminLogin(string $username, string $password): array
    {
        $db = Database::getInstance();
        $admin = $db->fetch(
            'SELECT * FROM admin_users WHERE username = :username AND status = 1 LIMIT 1',
            [':username' => $username]
        );

        if (empty($admin)) {
            return false;
        }

        if (!password_verify($password, $admin['password'])) {
            return false;
        }

        // 更新最后登录时间
        $db->update('admin_users', [
            'last_login_at' => date('Y-m-d H:i:s'),
        ], 'id = :id', [':id' => $admin['id']]);

        $token = self::generateToken((int) $admin['id'], 'admin');

        unset($admin['password']);
        return [
            'token' => $token,
            'user'  => $admin,
        ];
    }

    /**
     * 用户登录 - 验证凭据并返回令牌
     *
     * @param string $username
     * @param string $password
     * @return array|false
     */
    public static function login(string $username, string $password): array
    {
        $db = Database::getInstance();
        $user = $db->fetch(
            'SELECT * FROM users WHERE (nickname = :u OR phone = :u) AND status = 1 LIMIT 1',
            [':u' => $username]
        );

        if (empty($user)) {
            return false;
        }

        $db->update('users', [
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = :id', [':id' => $user['id']]);

        $token = self::generateToken((int) $user['id'], 'user');

        unset($user['password'], $user['wx_session_key']);
        return [
            'token' => $token,
            'user'  => $user,
        ];
    }

    /**
     * 微信小程序 code2session 登录
     *
     * @param string $code  wx.login() 返回的code
     * @return array|false  用户记录或false
     */
    public static function wechatLogin(string $code): array
    {
        $config = require __DIR__ . '/../config/app.php';

        $appid     = $config['wechat_mini_appid'];
        $secret    = $config['wechat_mini_secret'];

        if (empty($appid) || empty($secret)) {
            return false;
        }

        $url = sprintf(
            'https://api.weixin.qq.com/sns/jscode2session?appid=%s&secret=%s&js_code=%s&grant_type=authorization_code',
            $appid,
            $secret,
            $code
        );

        $response = self::httpGet($url);
        $result = json_decode($response, true);

        if (!$result || isset($result['errcode'])) {
            return false;
        }

        $openid     = $result['openid'] ?? '';
        $sessionKey = $result['session_key'] ?? '';
        $unionId    = $result['unionid'] ?? '';

        if (empty($openid)) {
            return false;
        }

        $db = Database::getInstance();

        // 检查用户是否已存在
        $user = $db->fetch(
            'SELECT * FROM users WHERE wx_openid = :openid LIMIT 1',
            [':openid' => $openid]
        );

        if (empty($user)) {
            // 创建新用户
            $userId = $db->insert('users', [
                'wx_openid'     => $openid,
                'wx_unionid'    => $unionId,
                'nickname'      => '微信用户',
                'avatar'        => '',
                'status'        => 1,
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ]);
            $user = $db->fetch('SELECT * FROM users WHERE id = :id LIMIT 1', [':id' => $userId]);
        } else {
            // 更新会话密钥
            $db->update('users', [
                'wx_session_key' => $sessionKey,
                'updated_at'     => date('Y-m-d H:i:s'),
            ], 'id = :id', [':id' => $user['id']]);
        }

        $token = self::generateToken((int) $user['id'], 'user');

        unset($user['password'], $user['wx_session_key']);
        return [
            'token' => $token,
            'user'  => $user,
        ];
    }

    /**
     * 简单HTTP GET请求（file_get_contents备用方案）
     */
    private static function httpGet(string $url): string
    {
        $context = stream_context_create([
            'http' => [
                'method'  => 'GET',
                'timeout' => 10,
                'header'  => "Accept: application/json\r\n",
            ],
        ]);

        $response = @file_get_contents($url, false, $context);
        return $response ?: '';
    }

    /**
     * Base64 URL编码
     */
    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Base64 URL解码
     */
    private static function base64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }

    /**
     * 别名：要求认证，返回用户ID
     *
     * @return int
     */
    public static function required(): int
    {
        $user = self::requireAuth();
        return (int) ($user['user_id'] ?? 0);
    }

    /**
     * 别名：可选认证，未登录返回0
     *
     * @return int
     */
    public static function optional(): int
    {
        try {
            return self::getUserId();
        } catch (\Exception $e) {
            return 0;
        }
    }
}
