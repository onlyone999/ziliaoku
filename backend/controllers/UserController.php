<?php
/**
 * 用户控制器
 * 处理用户个人资料、积分、VIP、订单、反馈等功能
 */

require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';

class UserController
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * 获取用户完整个人资料（含统计数据）
     */
    public function getProfile()
    {
        try {
            $userId = Auth::required();

            // 查询用户基础信息
            $userStmt = $this->db->prepare(
                "SELECT `id`, `openid`, `nickname`, `avatar_url`, `phone`, `gender`, `vip_level`, `vip_expire_at`, `points`, `total_downloads`, `created_at`
                 FROM `users` WHERE `id` = ?"
            );
            $userStmt->execute([$userId]);
            $user = $userStmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                Response::error('用户不存在', 404);
            }

            // 统计下载次数
            $dlStmt = $this->db->prepare("SELECT COUNT(*) FROM `downloads` WHERE `user_id` = ?");
            $dlStmt->execute([$userId]);
            $user['download_count'] = intval($dlStmt->fetchColumn());

            // 统计收藏数
            $favStmt = $this->db->prepare("SELECT COUNT(*) FROM `favorites` WHERE `user_id` = ?");
            $favStmt->execute([$userId]);
            $user['favorite_count'] = intval($favStmt->fetchColumn());

            // 统计评论数
            $cmtStmt = $this->db->prepare("SELECT COUNT(*) FROM `comments` WHERE `user_id` = ?");
            $cmtStmt->execute([$userId]);
            $user['comment_count'] = intval($cmtStmt->fetchColumn());

            // VIP状态
            $user['is_vip'] = $this->isVipValid($user);

            Response::success($user, '获取成功');

        } catch (\Exception $e) {
            Response::error('获取个人资料异常: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 获取积分明细（分页）
     */
    public function getPointsLog()
    {
        try {
            $userId = Auth::required();

            $page = max(1, intval($_GET['page'] ?? 1));
            $pageSize = min(50, max(1, intval($_GET['page_size'] ?? 20)));
            $offset = ($page - 1) * $pageSize;

            $countStmt = $this->db->prepare("SELECT COUNT(*) FROM `user_points_log` WHERE `user_id` = ?");
            $countStmt->execute([$userId]);
            $total = intval($countStmt->fetchColumn());

            $sql = "SELECT `id`, `points`, `type`, `description`, `created_at`
                    FROM `user_points_log`
                    WHERE `user_id` = ?
                    ORDER BY `created_at` DESC
                    LIMIT ? OFFSET ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $pageSize, $offset]);
            $list = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // 获取当前积分余额
            $balStmt = $this->db->prepare("SELECT `points` FROM `users` WHERE `id` = ?");
            $balStmt->execute([$userId]);
            $balance = intval($balStmt->fetchColumn());

            Response::success([
                'balance' => $balance,
                'list' => $list,
                'total' => $total,
                'page' => $page,
                'page_size' => $pageSize,
            ], '获取成功');

        } catch (\Exception $e) {
            Response::error('获取积分记录异常: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 获取VIP信息（状态、到期时间、当前套餐详情）
     */
    public function getVipInfo()
    {
        try {
            $userId = Auth::required();

            $userStmt = $this->db->prepare(
                "SELECT `vip_level`, `vip_expire_at` FROM `users` WHERE `id` = ?"
            );
            $userStmt->execute([$userId]);
            $user = $userStmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                Response::error('用户不存在', 404);
            }

            $isVip = $this->isVipValid($user);

            // VIP等级名称映射
            $levelNames = [0 => '普通用户', 1 => '月度会员', 2 => '季度会员', 3 => '年度会员', 4 => '终身会员'];

            // 查询最近购买的VIP订单以获取套餐信息
            $planInfo = null;
            if ($user['vip_level'] > 0) {
                $planStmt = $this->db->prepare(
                    "SELECT `vp`.`name`, `vp`.`duration_days`, `vp`.`price`
                     FROM `orders` `o`
                     INNER JOIN `vip_plans` `vp` ON `vp`.`id` = `o`.`vip_plan_id`
                     WHERE `o`.`user_id` = ? AND `o`.`order_type` = 'vip' AND `o`.`status` = 'paid'
                     ORDER BY `o`.`paid_at` DESC
                     LIMIT 1"
                );
                $planStmt->execute([$userId]);
                $planInfo = $planStmt->fetch(PDO::FETCH_ASSOC);
            }

            // 计算剩余天数
            $remainDays = 0;
            if ($isVip && $user['vip_expire_at']) {
                $remainDays = max(0, floor((strtotime($user['vip_expire_at']) - time()) / 86400));
            }

            Response::success([
                'is_vip' => $isVip,
                'vip_level' => $user['vip_level'],
                'vip_level_name' => isset($levelNames[$user['vip_level']]) ? $levelNames[$user['vip_level']] : '普通用户',
                'vip_expire_at' => $user['vip_expire_at'],
                'remain_days' => $remainDays,
                'current_plan' => $planInfo,
            ], '获取成功');

        } catch (\Exception $e) {
            Response::error('获取VIP信息异常: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 购买VIP（创建订单，返回支付参数）
     * 实际支付下单逻辑委托给 PaymentController
     */
    public function buyVip()
    {
        try {
            $userId = Auth::required();

            $planId = isset($_POST['vip_plan_id']) ? intval($_POST['vip_plan_id']) : 0;
            if ($planId <= 0) {
                Response::error('请选择VIP套餐', 400);
            }

            // 验证套餐
            $planStmt = $this->db->prepare("SELECT * FROM `vip_plans` WHERE `id` = ? AND `status` = 1");
            $planStmt->execute([$planId]);
            $plan = $planStmt->fetch(PDO::FETCH_ASSOC);
            if (!$plan) {
                Response::error('套餐不存在或已下架', 404);
            }

            // 获取用户openid
            $userStmt = $this->db->prepare("SELECT `openid` FROM `users` WHERE `id` = ?");
            $userStmt->execute([$userId]);
            $user = $userStmt->fetch(PDO::FETCH_ASSOC);
            if (!$user || empty($user['openid'])) {
                Response::error('用户信息异常', 400);
            }

            // 创建订单
            $orderNo = date('YmdHis') . str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
            $now = date('Y-m-d H:i:s');

            $insertStmt = $this->db->prepare(
                "INSERT INTO `orders` (`order_no`, `user_id`, `order_type`, `vip_plan_id`, `amount`, `pay_amount`, `payment_method`, `status`, `created_at`, `updated_at`) VALUES (?, ?, 'vip', ?, ?, ?, 'wechat', 'pending', ?, ?)"
            );
            $insertStmt->execute([$orderNo, $userId, $planId, $plan['price'], $plan['price'], $now, $now]);

            // 调用微信支付统一下单（复用 PaymentController 的逻辑）
            // 这里直接内联支付参数生成
            $payParams = $this->createWechatPayParams($orderNo, '开通VIP: ' . $plan['name'], $plan['price'], $user['openid']);

            Response::success([
                'order_no' => $orderNo,
                'amount' => $plan['price'],
                'plan_name' => $plan['name'],
                'duration_days' => $plan['duration_days'],
                'pay_params' => $payParams,
            ], '订单创建成功');

        } catch (\Exception $e) {
            Response::error('购买VIP异常: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 获取用户订单历史（分页）
     */
    public function getOrders()
    {
        try {
            $userId = Auth::required();

            $page = max(1, intval($_GET['page'] ?? 1));
            $pageSize = min(50, max(1, intval($_GET['page_size'] ?? 10)));
            $offset = ($page - 1) * $pageSize;

            // 可选筛选订单类型
            $orderType = isset($_GET['order_type']) ? trim($_GET['order_type']) : '';
            $where = "`o`.`user_id` = ?";
            $params = [$userId];

            if (!empty($orderType) && in_array($orderType, ['resource', 'vip'])) {
                $where .= " AND `o`.`order_type` = ?";
                $params[] = $orderType;
            }

            $countStmt = $this->db->prepare("SELECT COUNT(*) FROM `orders` `o` WHERE {$where}");
            $countStmt->execute($params);
            $total = intval($countStmt->fetchColumn());

            $sql = "SELECT `o`.`id`, `o`.`order_no`, `o`.`order_type`, `o`.`amount`, `o`.`pay_amount`, `o`.`payment_method`, `o`.`status`, `o`.`paid_at`, `o`.`created_at`,
                           `r`.`title` AS `resource_title`, `r`.`cover_url` AS `resource_cover`,
                           `vp`.`name` AS `vip_plan_name`
                    FROM `orders` `o`
                    LEFT JOIN `resources` `r` ON `r`.`id` = `o`.`resource_id`
                    LEFT JOIN `vip_plans` `vp` ON `vp`.`id` = `o`.`vip_plan_id`
                    WHERE {$where}
                    ORDER BY `o`.`created_at` DESC
                    LIMIT ? OFFSET ?";
            $params[] = $pageSize;
            $params[] = $offset;
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $list = $stmt->fetchAll(PDO::FETCH_ASSOC);

            Response::success([
                'list' => $list,
                'total' => $total,
                'page' => $page,
                'page_size' => $pageSize,
            ], '获取成功');

        } catch (\Exception $e) {
            Response::error('获取订单列表异常: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 别名：获取用户个人资料
     */
    public function info()
    {
        $this->getProfile();
    }

    /**
     * 获取用户统计数据
     */
    public function stats()
    {
        try {
            $userId = Auth::required();

            // 下载次数
            $dlStmt = $this->db->prepare("SELECT COUNT(*) FROM `downloads` WHERE `user_id` = ?");
            $dlStmt->execute([$userId]);
            $downloadCount = intval($dlStmt->fetchColumn());

            // 收藏数
            $favStmt = $this->db->prepare("SELECT COUNT(*) FROM `favorites` WHERE `user_id` = ?");
            $favStmt->execute([$userId]);
            $favoriteCount = intval($favStmt->fetchColumn());

            // 订单数
            $orderStmt = $this->db->prepare("SELECT COUNT(*) FROM `orders` WHERE `user_id` = ?");
            $orderStmt->execute([$userId]);
            $orderCount = intval($orderStmt->fetchColumn());

            // 积分
            $pointsStmt = $this->db->prepare("SELECT `points` FROM `users` WHERE `id` = ?");
            $pointsStmt->execute([$userId]);
            $points = intval($pointsStmt->fetchColumn());

            // VIP状态
            $vipStmt = $this->db->prepare("SELECT `vip_level`, `vip_expire_at` FROM `users` WHERE `id` = ?");
            $vipStmt->execute([$userId]);
            $user = $vipStmt->fetch(PDO::FETCH_ASSOC);
            $isVip = $this->isVipValid($user);

            Response::success([
                'download_count' => $downloadCount,
                'favorite_count' => $favoriteCount,
                'order_count' => $orderCount,
                'points' => $points,
                'is_vip' => $isVip,
                'feedback_enabled' => $this->getSetting('feedback_enabled') !== '0',
            ], '获取成功');

        } catch (\Exception $e) {
            Response::error('获取统计数据异常: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 别名：获取VIP信息
     */
    public function vipStatus()
    {
        $this->getVipInfo();
    }

    /**
     * 获取用户下载记录（分页）
     */
    public function downloads()
    {
        $userId = Auth::required();
        $page = max(1, intval($GLOBALS['REQUEST_DATA']['page'] ?? 1));
        $pageSize = min(50, max(1, intval($GLOBALS['REQUEST_DATA']['page_size'] ?? 10)));
        $offset = ($page - 1) * $pageSize;

        $countRow = $this->db->fetch('SELECT COUNT(*) AS cnt FROM downloads WHERE user_id = :uid', [':uid' => $userId]);
        $total = intval($countRow['cnt']);

        $list = $this->db->fetchAll(
            'SELECT d.*, r.title, r.cover_url, r.file_type, r.file_size, c.name AS category_name
             FROM downloads d
             LEFT JOIN resources r ON d.resource_id = r.id
             LEFT JOIN categories c ON r.category_id = c.id
             WHERE d.user_id = :uid
             ORDER BY d.created_at DESC
             LIMIT :limit OFFSET :offset',
            [':uid' => $userId, ':limit' => $pageSize, ':offset' => $offset]
        );

        Response::success([
            'list' => $list,
            'total' => $total,
            'page' => $page,
            'page_size' => $pageSize,
        ]);
    }

    /**
     * 获取所有用户列表（管理员）
     */
    public function all()
    {
        try {
            $page = max(1, intval($GLOBALS['REQUEST_DATA']['page'] ?? 1));
            $pageSize = min(50, max(1, intval($GLOBALS['REQUEST_DATA']['page_size'] ?? 20)));
            $offset = ($page - 1) * $pageSize;

            $keyword = isset($GLOBALS['REQUEST_DATA']['keyword']) ? trim($GLOBALS['REQUEST_DATA']['keyword']) : '';
            $where = '1=1';
            $params = [];
            if (!empty($keyword)) {
                $where = '(nickname LIKE :kw OR phone LIKE :kw OR openid LIKE :kw)';
                $params[':kw'] = '%' . $keyword . '%';
            }

            $countRow = $this->db->fetch(
                "SELECT COUNT(*) AS cnt FROM users WHERE {$where}",
                $params
            );
            $total = intval($countRow['cnt']);

            $list = $this->db->fetchAll(
                "SELECT id, openid, nickname, avatar_url, phone, gender, vip_level, vip_expire_at, points, total_downloads, status, created_at
                 FROM users
                 WHERE {$where}
                 ORDER BY created_at DESC
                 LIMIT :limit OFFSET :offset",
                array_merge($params, [':limit' => $pageSize, ':offset' => $offset])
            );

            Response::success([
                'list' => $list,
                'total' => $total,
                'page' => $page,
                'page_size' => $pageSize,
            ]);

        } catch (\Exception $e) {
            Response::error('获取用户列表异常: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 更新用户状态（管理员）
     */
    public function updateStatus()
    {
        try {
            $data = $GLOBALS['REQUEST_DATA'] ?? [];
            $id = intval($data['id'] ?? 0);
            $status = intval($data['status'] ?? 1);

            if ($id <= 0) {
                Response::error('缺少用户ID', 400);
            }

            $this->db->update('users', [
                'status' => $status,
                'updated_at' => date('Y-m-d H:i:s'),
            ], 'id = :id', [':id' => $id]);

            Response::success([], '更新成功');

        } catch (\Exception $e) {
            Response::error('更新用户状态异常: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 设置用户VIP（管理员）
     */
    public function setVip()
    {
        try {
            $data = $GLOBALS['REQUEST_DATA'] ?? [];
            $id = intval($data['id'] ?? 0);
            $vipLevel = intval($data['vip_level'] ?? 0);
            $vipExpireAt = trim($data['vip_expire_at'] ?? '');

            if ($id <= 0) {
                Response::error('缺少用户ID', 400);
            }

            $updateData = [
                'vip_level' => $vipLevel,
                'vip_expire_at' => $vipExpireAt ?: null,
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            $this->db->update('users', $updateData, 'id = :id', [':id' => $id]);

            Response::success([], 'VIP设置成功');

        } catch (\Exception $e) {
            Response::error('设置VIP异常: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 提交反馈
     */
    public function feedback()
    {
        try {
            $userId = Auth::required();

            $type = isset($_POST['type']) ? trim($_POST['type']) : 'other';
            $content = isset($_POST['content']) ? trim($_POST['content']) : '';
            $contact = isset($_POST['contact']) ? trim($_POST['contact']) : '';

            if (empty($content)) {
                Response::error('反馈内容不能为空', 400);
            }
            if (mb_strlen($content) > 1000) {
                Response::error('反馈内容不能超过1000字', 400);
            }
            if (!in_array($type, ['bug', 'suggest', 'other'])) {
                $type = 'other';
            }

            $stmt = $this->db->prepare(
                "INSERT INTO `feedback` (`user_id`, `type`, `content`, `contact`, `created_at`) VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->execute([$userId, $type, $content, $contact, date('Y-m-d H:i:s')]);

            Response::success(['id' => $this->db->lastInsertId()], '反馈提交成功，感谢您的建议');

        } catch (\Exception $e) {
            Response::error('提交反馈异常: ' . $e->getMessage(), 500);
        }
    }

    // ============ 私有辅助方法 ============

    /**
     * 检查用户VIP是否有效
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
     * 创建微信支付参数（内联实现，避免循环引用 PaymentController）
     */
    private function createWechatPayParams($orderNo, $body, $amount, $openid)
    {
        $appid = $this->getSetting('wechat_appid');
        $mchId = $this->getSetting('wechat_mch_id');
        $apiKey = $this->getSetting('wechat_api_key');
        $notifyUrl = $this->getSetting('wechat_notify_url');

        if (empty($appid) || empty($mchId) || empty($apiKey)) {
            throw new \Exception('微信支付配置不完整');
        }

        if (empty($notifyUrl)) {
            $notifyUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/api/payment/notify';
        }

        $totalFee = intval(round($amount * 100));
        $nonceStr = $this->generateNonceStr(32);
        $ip = $this->getClientIp();

        $params = [
            'appid' => $appid,
            'mch_id' => $mchId,
            'nonce_str' => $nonceStr,
            'body' => mb_substr($body, 0, 128),
            'out_trade_no' => $orderNo,
            'total_fee' => $totalFee,
            'spbill_create_ip' => $ip,
            'notify_url' => $notifyUrl,
            'trade_type' => 'JSAPI',
            'openid' => $openid,
        ];
        $params['sign'] = $this->makeWechatSign($params, $apiKey);

        $xmlData = $this->arrayToXml($params);
        $result = $this->postWechatApi('https://api.mch.weixin.qq.com/pay/unifiedorder', $xmlData);
        $resultData = $this->parseXml($result);

        if (!$resultData || $resultData['return_code'] !== 'SUCCESS' || $resultData['result_code'] !== 'SUCCESS') {
            $errMsg = $resultData['err_code_des'] ?? $resultData['return_msg'] ?? '统一下单失败';
            throw new \Exception('微信下单失败: ' . $errMsg);
        }

        $prepayId = $resultData['prepay_id'];
        $timeStamp = (string)time();
        $payNonceStr = $this->generateNonceStr(32);
        $package = 'prepay_id=' . $prepayId;

        $paySignParams = [
            'appId' => $appid,
            'timeStamp' => $timeStamp,
            'nonceStr' => $payNonceStr,
            'package' => $package,
            'signType' => 'MD5',
        ];
        $paySign = $this->makeWechatSign($paySignParams, $apiKey);

        return [
            'timeStamp' => $timeStamp,
            'nonceStr' => $payNonceStr,
            'package' => $package,
            'signType' => 'MD5',
            'paySign' => $paySign,
        ];
    }

    /**
     * 获取关于我们信息
     * GET /api/user/about
     */
    public function about()
    {
        try {
            $stmt = $this->db->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_group = 'about'");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $data = [];
            foreach ($rows as $row) {
                $data[$row['setting_key']] = $row['setting_value'];
            }
            // 兜底默认值
            $data['about_version'] = $data['about_version'] ?? 'v1.0.0';
            $data['about_content'] = $data['about_content'] ?? '海量优质资源，助力高效工作';
            $data['about_copyright'] = $data['about_copyright'] ?? '© 2026 资源下载平台';

            Response::success($data);
        } catch (\Exception $e) {
            Response::error('获取关于我们信息失败', 500);
        }
    }

    private function getSetting($key)
    {
        $stmt = $this->db->prepare("SELECT `setting_value` FROM `system_settings` WHERE `setting_key` = ?");
        $stmt->execute([$key]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['setting_value'] : null;
    }

    private function makeWechatSign($params, $apiKey)
    {
        ksort($params);
        $stringA = '';
        foreach ($params as $k => $v) {
            if ($v !== '' && $v !== null) {
                $stringA .= $k . '=' . $v . '&';
            }
        }
        $stringA .= 'key=' . $apiKey;
        return strtoupper(md5($stringA));
    }

    private function arrayToXml($arr)
    {
        $xml = '<xml>';
        foreach ($arr as $k => $v) {
            if (is_numeric($v)) {
                $xml .= '<' . $k . '>' . $v . '</' . $k . '>';
            } else {
                $xml .= '<' . $k . '><![CDATA[' . $v . ']]></' . $k . '>';
            }
        }
        $xml .= '</xml>';
        return $xml;
    }

    private function parseXml($xml)
    {
        if (empty($xml)) return null;
        $backup = libxml_disable_entity_loader(true);
        $data = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        libxml_disable_entity_loader($backup);
        if (!$data) return null;
        return json_decode(json_encode($data), true);
    }

    private function postWechatApi($url, $data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            curl_close($ch);
            throw new \Exception('微信API请求失败: ' . curl_error($ch));
        }
        curl_close($ch);
        return $result;
    }

    private function generateNonceStr($length = 32)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
        $str = '';
        for ($i = 0; $i < $length; $i++) {
            $str .= $chars[mt_rand(0, strlen($chars) - 1)];
        }
        return $str;
    }

    private function getClientIp()
    {
        $keys = ['HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        foreach ($keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = trim(explode(',', $_SERVER[$key])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        return '127.0.0.1';
    }
}
