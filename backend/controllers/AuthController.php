<?php
/**
 * 认证控制器
 * 处理微信小程序登录、手机号获取、用户信息管理
 */

// 加载依赖
require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';

class AuthController
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * 微信小程序登录
     * 流程：接收code -> 调用微信code2session -> 创建或更新用户 -> 返回JWT和用户信息
     */
    public function login()
    {
        try {
            $code = isset($_POST['code']) ? trim($_POST['code']) : '';
            if (empty($code)) {
                $code = isset($GLOBALS['REQUEST_DATA']['code']) ? trim($GLOBALS['REQUEST_DATA']['code']) : '';
            }
            if (empty($code)) {
                Response::error('缺少code参数', 400);
            }

            // 前端传来的已保存openid（用于识别老用户）
            $savedOpenid = isset($_POST['openid']) ? trim($_POST['openid']) : '';
            if (empty($savedOpenid)) {
                $savedOpenid = isset($GLOBALS['REQUEST_DATA']['openid']) ? trim($GLOBALS['REQUEST_DATA']['openid']) : '';
            }

            // 获取微信配置
            $appid = $this->getSetting('wechat_appid');
            $secret = $this->getSetting('wechat_secret');

            // 开发模式：微信未配置或使用默认值时，用code作为openid创建mock用户
            $isDevMode = empty($appid) || empty($secret) || $appid === 'wx5be23d52a525cc71';

            if ($isDevMode) {
                // dev模式：使用固定openid，保证同一设备始终识别为同一用户
                // 正式环境由微信服务器保证openid唯一性
                $openid = 'dev_user_' . md5($appid . '_default');
                $unionid = null;
            } else {
                // 调用微信 code2session 接口
                $wxUrl = sprintf(
                    'https://api.weixin.qq.com/sns/jscode2session?appid=%s&secret=%s&js_code=%s&grant_type=authorization_code',
                    $appid,
                    $secret,
                    $code
                );

                $wxResult = $this->httpGet($wxUrl);
                $wxData = json_decode($wxResult, true);

                if (!$wxData || isset($wxData['errcode'])) {
                    $errMsg = isset($wxData['errmsg']) ? $wxData['errmsg'] : '微信登录失败';
                    $errCode = isset($wxData['errcode']) ? $wxData['errcode'] : 500;
                    Response::error('微信登录失败: ' . $errMsg, $errCode);
                }

                $openid = $wxData['openid'];
                $unionid = isset($wxData['unionid']) ? $wxData['unionid'] : null;
            }

            // 查询用户：优先用前端保存的openid识别老用户，再用本次code解析的openid
            $user = null;
            if (!empty($savedOpenid)) {
                $stmt = $this->db->prepare("SELECT * FROM `users` WHERE `openid` = ?");
                $stmt->execute([$savedOpenid]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
            }
            if (!$user) {
                $stmt = $this->db->prepare("SELECT * FROM `users` WHERE `openid` = ?");
                $stmt->execute([$openid]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
            }

            $now = date('Y-m-d H:i:s');
            $isNewUser = false;

            if ($user) {
                // 更新已有用户
                $updateStmt = $this->db->prepare(
                    "UPDATE `users` SET `unionid` = IFNULL(?, `unionid`), `updated_at` = ? WHERE `id` = ?"
                );
                $updateStmt->execute([$unionid, $now, $user['id']]);
                $userId = $user['id'];
            } else {
                $isNewUser = true;
                // 创建新用户（mock模式用随机昵称，正式模式用openid后6位）
                $insertStmt = $this->db->prepare(
                    "INSERT INTO `users` (`openid`, `unionid`, `nickname`, `avatar_url`, `created_at`, `updated_at`) VALUES (?, ?, ?, ?, ?, ?)"
                );
                if ($isDevMode) {
                    $nickname = '用户' . mt_rand(1000, 9999);
                    $avatarUrl = '';
                } else {
                    $nickname = '用户_' . substr($openid, -6);
                    $avatarUrl = '';
                }
                $insertStmt->execute([$openid, $unionid, $nickname, $avatarUrl, $now, $now]);
                $userId = $this->db->lastInsertId();
            }

            // 生成 JWT token（携带 openid 以便 token 过期后仍可恢复）
            $token = Auth::generateToken($userId, 'user', 0, $openid);

            // 获取最新用户信息
            $userStmt = $this->db->prepare(
                "SELECT `id`, `openid`, `nickname`, `avatar_url`, `phone`, `gender`, `vip_level`, `vip_expire_at`, `points`, `total_downloads`, `created_at` FROM `users` WHERE `id` = ?"
            );
            $userStmt->execute([$userId]);
            $userInfo = $userStmt->fetch(PDO::FETCH_ASSOC);

            // 在用户信息中标记 VIP 是否有效
            $userInfo['is_vip'] = $this->isVipValid($userInfo);

            Response::success([
                'token' => $token,
                'user' => $userInfo,
                'is_new_user' => $isNewUser,
                'is_dev_mode' => $isDevMode,
            ], '登录成功');

        } catch (\Exception $e) {
            Response::error('登录处理异常: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 获取手机号
     * 通过微信 getPhoneNumber 回调的加密数据解密获取
     */
    public function getPhone()
    {
        try {
            $userId = Auth::required();

            $encryptedData = isset($_POST['encrypted_data']) ? trim($_POST['encrypted_data']) : '';
            $iv = isset($_POST['iv']) ? trim($_POST['iv']) : '';

            if (empty($encryptedData) || empty($iv)) {
                Response::error('缺少加密数据参数', 400);
            }

            // 获取 session_key（实际项目中应缓存在Redis，此处从简查询数据库）
            // 注意：实际项目中 session_key 应存储在服务端缓存，这里仅为示例
            $sessionKey = isset($_POST['session_key']) ? trim($_POST['session_key']) : '';

            if (empty($sessionKey)) {
                Response::error('缺少session_key，请重新登录', 400);
            }

            // AES-128-CBC 解密
            $decrypted = $this->decryptWechatData($encryptedData, $iv, $sessionKey);

            if ($decrypted === false) {
                Response::error('数据解密失败', 400);
            }

            $phoneData = json_decode($decrypted, true);

            if (!$phoneData || !isset($phoneData['phoneNumber'])) {
                Response::error('手机号获取失败', 400);
            }

            $phone = $phoneData['phoneNumber'];

            // 更新用户手机号
            $stmt = $this->db->prepare("UPDATE `users` SET `phone` = ?, `updated_at` = ? WHERE `id` = ?");
            $stmt->execute([$phone, date('Y-m-d H:i:s'), $userId]);

            Response::success(['phone' => $phone], '手机号获取成功');

        } catch (\Exception $e) {
            Response::error('获取手机号异常: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 获取当前登录用户信息
     */
    public function getUserInfo()
    {
        try {
            $userId = Auth::required();

            $stmt = $this->db->prepare(
                "SELECT `id`, `openid`, `nickname`, `avatar_url`, `phone`, `gender`, `vip_level`, `vip_expire_at`, `points`, `total_downloads`, `created_at` FROM `users` WHERE `id` = ?"
            );
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                Response::error('用户不存在', 404);
            }

            $user['is_vip'] = $this->isVipValid($user);

            Response::success($user, '获取成功');

        } catch (\Exception $e) {
            Response::error('获取用户信息异常: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 更新用户资料（昵称、头像）
     * 支持 JSON 和 multipart/form-data（头像文件上传）
     */
    public function updateProfile()
    {
        try {
            $userId = Auth::required();

            $nickname = isset($_POST['nickname']) ? trim($_POST['nickname']) : null;
            $avatarUrl = isset($_POST['avatar_url']) ? trim($_POST['avatar_url']) : null;
            $gender = isset($_POST['gender']) ? intval($_POST['gender']) : null;

            // 处理头像文件上传
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                try {
                    $upload = new Upload();
                    $result = $upload->handle($_FILES['avatar'], ['image']);
                    $avatarUrl = $result['url'];
                } catch (\Exception $e) {
                    // 头像上传失败不阻断
                }
            }

            $fields = [];
            $params = [];

            if ($nickname !== null) {
                if (mb_strlen($nickname) < 1 || mb_strlen($nickname) > 50) {
                    Response::error('昵称长度应在1-50个字符之间', 400);
                }
                $fields[] = '`nickname` = ?';
                $params[] = $nickname;
            }

            if ($avatarUrl !== null) {
                $fields[] = '`avatar_url` = ?';
                $params[] = $avatarUrl;
            }

            if ($gender !== null && in_array($gender, [0, 1, 2])) {
                $fields[] = '`gender` = ?';
                $params[] = $gender;
            }

            if (empty($fields)) {
                Response::error('没有需要更新的字段', 400);
            }

            $fields[] = '`updated_at` = ?';
            $params[] = date('Y-m-d H:i:s');
            $params[] = $userId;

            $sql = "UPDATE `users` SET " . implode(', ', $fields) . " WHERE `id` = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            // 返回更新后的用户信息
            $userStmt = $this->db->prepare(
                "SELECT `id`, `nickname`, `avatar_url`, `phone`, `gender`, `vip_level`, `vip_expire_at`, `points`, `total_downloads` FROM `users` WHERE `id` = ?"
            );
            $userStmt->execute([$userId]);
            $user = $userStmt->fetch(PDO::FETCH_ASSOC);

            Response::success($user, '更新成功');

        } catch (\Exception $e) {
            Response::error('更新资料异常: ' . $e->getMessage(), 500);
        }
    }

    // ============ 私有辅助方法 ============

    /**
     * 获取系统设置值
     */
    private function getSetting($key)
    {
        $stmt = $this->db->prepare("SELECT `setting_value` FROM `system_settings` WHERE `setting_key` = ?");
        $stmt->execute([$key]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['setting_value'] : null;
    }

    /**
     * 发起HTTP GET请求
     */
    private function httpGet($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            curl_close($ch);
            throw new \Exception('HTTP请求失败: ' . curl_error($ch));
        }
        curl_close($ch);
        return $result;
    }

    /**
     * AES-128-CBC 解密微信加密数据
     */
    private function decryptWechatData($encryptedData, $iv, $sessionKey)
    {
        $aesKey = base64_decode($sessionKey);
        $aesIV = base64_decode($iv);
        $aesCipher = base64_decode($encryptedData);

        $result = openssl_decrypt($aesCipher, 'AES-128-CBC', $aesKey, OPENSSL_RAW_DATA, $aesIV);
        return $result;
    }

    /**
     * 检查用户 VIP 是否有效
     */
    private function isVipValid($user)
    {
        if (empty($user['vip_level']) || $user['vip_level'] <= 0) {
            return false;
        }
        if ($user['vip_expire_at'] === null) {
            return false;
        }
        return strtotime($user['vip_expire_at']) > time();
    }

    /**
     * 别名：微信登录
     */
    public function wxLogin()
    {
        $this->login();
    }

    /**
     * 新版登录：头像昵称填写能力
     * 前端 wx.login() 获取 code，chooseAvatar 获取头像，type=nickname 获取昵称
     * POST /api/auth/fill-login
     *   code     - wx.login() 返回的 code
     *   nickname - 用户填写的昵称
     *   avatar   - 头像文件（multipart/form-data）
     */
    public function fillLogin()
    {
        try {
            // 读取参数
            $code     = isset($_POST['code']) ? trim($_POST['code']) : '';
            $nickname = isset($_POST['nickname']) ? trim($_POST['nickname']) : '';

            // 前端传来的已保存openid（用于识别老用户，避免重复注册）
            $savedOpenid = isset($_POST['openid']) ? trim($_POST['openid']) : '';
            if (empty($savedOpenid)) {
                $savedOpenid = isset($GLOBALS['REQUEST_DATA']['openid']) ? trim($GLOBALS['REQUEST_DATA']['openid']) : '';
            }

            if (empty($code)) {
                Response::error('缺少code参数', 400);
            }
            if (empty($nickname)) {
                $nickname = '微信用户';
            }

            // 获取微信配置
            $appid  = $this->getSetting('wechat_appid');
            $secret = $this->getSetting('wechat_secret');

            $isDevMode = empty($appid) || empty($secret) || $appid === 'wx5be23d52a525cc71';

            if ($isDevMode) {
                $openid  = 'dev_user_' . md5($appid . '_default');
                $unionid = null;
            } else {
                $wxUrl = sprintf(
                    'https://api.weixin.qq.com/sns/jscode2session?appid=%s&secret=%s&js_code=%s&grant_type=authorization_code',
                    $appid, $secret, $code
                );
                $wxResult = $this->httpGet($wxUrl);
                $wxData   = json_decode($wxResult, true);

                if (!$wxData || isset($wxData['errcode'])) {
                    $errMsg  = isset($wxData['errmsg']) ? $wxData['errmsg'] : '微信登录失败';
                    $errCode = isset($wxData['errcode']) ? $wxData['errcode'] : 500;
                    Response::error('微信登录失败: ' . $errMsg, $errCode);
                }

                $openid  = $wxData['openid'];
                $unionid = isset($wxData['unionid']) ? $wxData['unionid'] : null;
            }

            // 处理头像上传
            $avatarUrl = '';
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                try {
                    $upload   = new Upload();
                    $result   = $upload->handle($_FILES['avatar'], ['image']);
                    $avatarUrl = $result['url'];
                } catch (\Exception $e) {
                    // 头像上传失败不阻断登录
                    $avatarUrl = '';
                }
            }

            // 查询用户：优先用前端保存的openid识别老用户，再用本次code解析的openid
            $user = null;
            if (!empty($savedOpenid)) {
                $stmt = $this->db->prepare("SELECT * FROM `users` WHERE `openid` = ?");
                $stmt->execute([$savedOpenid]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
            }
            if (!$user) {
                $stmt = $this->db->prepare("SELECT * FROM `users` WHERE `openid` = ?");
                $stmt->execute([$openid]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
            }

            $now = date('Y-m-d H:i:s');

            if ($user) {
                // 老用户：更新头像和昵称（只更新有值的字段）
                $sql = "UPDATE `users` SET `updated_at` = ?";
                $params = [$now];

                if ($nickname && $nickname !== '微信用户') {
                    $sql .= ", `nickname` = ?";
                    $params[] = $nickname;
                }
                if ($avatarUrl) {
                    $sql .= ", `avatar_url` = ?";
                    $params[] = $avatarUrl;
                }
                if ($unionid) {
                    $sql .= ", `unionid` = ?";
                    $params[] = $unionid;
                }

                $sql .= " WHERE `id` = ?";
                $params[] = $user['id'];

                $updateStmt = $this->db->prepare($sql);
                $updateStmt->execute($params);
                $userId = $user['id'];
            } else {
                // 新用户：创建
                $insertStmt = $this->db->prepare(
                    "INSERT INTO `users` (`openid`, `unionid`, `nickname`, `avatar_url`, `created_at`, `updated_at`) VALUES (?, ?, ?, ?, ?, ?)"
                );
                $insertStmt->execute([$openid, $unionid, $nickname, $avatarUrl, $now, $now]);
                $userId = $this->db->lastInsertId();
            }

            // 生成 JWT token（携带 openid 以便 token 过期后仍可恢复）
            $token = Auth::generateToken($userId, 'user', 0, $openid);

            // 获取最新用户信息
            $userStmt = $this->db->prepare(
                "SELECT `id`, `openid`, `nickname`, `avatar_url`, `phone`, `gender`, `vip_level`, `vip_expire_at`, `points`, `total_downloads`, `created_at` FROM `users` WHERE `id` = ?"
            );
            $userStmt->execute([$userId]);
            $userInfo = $userStmt->fetch(PDO::FETCH_ASSOC);
            $userInfo['is_vip'] = $this->isVipValid($userInfo);

            Response::success([
                'token' => $token,
                'user'  => $userInfo,
            ], '登录成功');

        } catch (\Exception $e) {
            Response::error('登录处理异常: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 静默登录检查（永远返回200，前端自行判断 code）
     * GET /api/auth/silent-check
     * 返回: code=0 + user（token有效）或 code=401（无效/过期）
     */
    public function silentCheck()
    {
        $token = Auth::getTokenFromRequest();
        if (empty($token)) {
            Response::success(['logged_in' => false, 'reason' => 'no_token']);
            return;
        }
        $payload = Auth::verifyToken($token);
        if ($payload === false) {
            // 过期 token 也能提取 openid
            $openid = Auth::decodeOpenid($token);
            Response::success(['logged_in' => false, 'reason' => 'token_expired', 'openid' => $openid]);
            return;
        }
        // token 有效，返回用户信息
        $userId = $payload['user_id'];
        $stmt = $this->db->prepare(
            "SELECT `id`, `openid`, `nickname`, `avatar_url`, `phone`, `gender`, `vip_level`, `vip_expire_at`, `points`, `total_downloads`, `created_at` FROM `users` WHERE `id` = ?"
        );
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            Response::success(['logged_in' => false, 'reason' => 'user_deleted']);
            return;
        }
        $user['is_vip'] = $this->isVipValid($user);
        Response::success(['logged_in' => true, 'user' => $user]);
    }

    /**
     * 从过期 token 中恢复 openid
     * POST /api/auth/recover-openid
     *   token - 任意 token（含过期），仅提取其中的 openid 字段
     */
    public function recoverOpenid()
    {
        try {
            $token = isset($_POST['token']) ? trim($_POST['token']) : '';
            if (empty($token)) {
                $token = isset($GLOBALS['REQUEST_DATA']['token']) ? trim($GLOBALS['REQUEST_DATA']['token']) : '';
            }
            if (empty($token)) {
                Response::error('缺少token参数', 400);
            }

            $openid = Auth::decodeOpenid($token);
            if (empty($openid)) {
                Response::success(['openid' => ''], 'token中无openid');
            }

            Response::success(['openid' => $openid], '恢复成功');
        } catch (\Exception $e) {
            Response::error('恢复openid失败: ' . $e->getMessage(), 500);
        }
    }
}
