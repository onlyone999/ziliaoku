<?php
/**
 * 支付控制器
 * 处理微信支付下单、回调通知、订单查询
 * 支持资源购买和VIP购买两种订单类型
 */

require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';

class PaymentController
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * 创建支付订单
     * 支持 resource（资源购买）和 vip（VIP购买）两种类型
     * 返回小程序支付参数
     */
    public function create()
    {
        try {
            $userId = Auth::required();

            $orderType = isset($_POST['order_type']) ? trim($_POST['order_type']) : '';
            if (!in_array($orderType, ['resource', 'vip'])) {
                Response::error('无效的订单类型', 400);
            }

            // 获取用户 openid
            $userStmt = $this->db->prepare("SELECT `openid` FROM `users` WHERE `id` = ?");
            $userStmt->execute([$userId]);
            $user = $userStmt->fetch(PDO::FETCH_ASSOC);
            if (!$user || empty($user['openid'])) {
                Response::error('用户信息异常', 400);
            }

            $orderNo = $this->generateOrderNo();
            $now = date('Y-m-d H:i:s');

            if ($orderType === 'resource') {
                // 资源购买订单
                $resourceId = isset($_POST['resource_id']) ? intval($_POST['resource_id']) : 0;
                if ($resourceId <= 0) {
                    Response::error('缺少资源ID', 400);
                }

                // 获取资源信息
                $resStmt = $this->db->prepare(
                    "SELECT `id`, `title`, `price_type`, `price`, `vip_price` FROM `resources` WHERE `id` = ? AND `status` = 'approved'"
                );
                $resStmt->execute([$resourceId]);
                $resource = $resStmt->fetch(PDO::FETCH_ASSOC);
                if (!$resource) {
                    Response::error('资源不存在', 404);
                }

                if ($resource['price_type'] === 'free') {
                    Response::error('免费资源无需购买', 400);
                }

                // 检查是否已购买
                $existStmt = $this->db->prepare(
                    "SELECT COUNT(*) FROM `orders` WHERE `user_id` = ? AND `resource_id` = ? AND `status` = 'paid' AND `order_type` = 'resource'"
                );
                $existStmt->execute([$userId, $resourceId]);
                if ($existStmt->fetchColumn() > 0) {
                    Response::error('您已购买过该资源', 400);
                }

                // 判断价格（VIP用户享受VIP价）
                $userVipStmt = $this->db->prepare("SELECT `vip_level`, `vip_expire_at` FROM `users` WHERE `id` = ?");
                $userVipStmt->execute([$userId]);
                $userVip = $userVipStmt->fetch(PDO::FETCH_ASSOC);
                $isVip = $this->isVipValid($userVip);
                $amount = $isVip ? $resource['vip_price'] : $resource['price'];

                // 积分为0时使用微信支付
                $payAmount = $amount;

                // 创建订单
                $insertStmt = $this->db->prepare(
                    "INSERT INTO `orders` (`order_no`, `user_id`, `order_type`, `resource_id`, `amount`, `pay_amount`, `payment_method`, `status`, `created_at`, `updated_at`) VALUES (?, ?, 'resource', ?, ?, ?, 'wechat', 'pending', ?, ?)"
                );
                $insertStmt->execute([$orderNo, $userId, $resourceId, $amount, $payAmount, $now, $now]);
                $orderId = $this->db->lastInsertId();

                $body = '购买资源: ' . $resource['title'];

            } else {
                // VIP购买订单
                $planId = isset($_POST['vip_plan_id']) ? intval($_POST['vip_plan_id']) : 0;
                if ($planId <= 0) {
                    Response::error('缺少VIP套餐ID', 400);
                }

                // 获取套餐信息
                $planStmt = $this->db->prepare(
                    "SELECT * FROM `vip_plans` WHERE `id` = ? AND `status` = 1"
                );
                $planStmt->execute([$planId]);
                $plan = $planStmt->fetch(PDO::FETCH_ASSOC);
                if (!$plan) {
                    Response::error('VIP套餐不存在或已下架', 404);
                }

                $amount = $plan['price'];

                // 创建订单
                $insertStmt = $this->db->prepare(
                    "INSERT INTO `orders` (`order_no`, `user_id`, `order_type`, `vip_plan_id`, `amount`, `pay_amount`, `payment_method`, `status`, `created_at`, `updated_at`) VALUES (?, ?, 'vip', ?, ?, ?, 'wechat', 'pending', ?, ?)"
                );
                $insertStmt->execute([$orderNo, $userId, $planId, $amount, $amount, $now, $now]);
                $orderId = $this->db->lastInsertId();

                $body = '开通VIP: ' . $plan['name'];
            }

            // 调用微信支付统一下单
            $payParams = $this->wechatPayUnifiedOrder($orderNo, $body, $payAmount, $user['openid']);

            Response::success([
                'order_no' => $orderNo,
                'order_id' => $orderId,
                'amount' => $amount,
                'pay_params' => $payParams,
            ], '订单创建成功');

        } catch (\Exception $e) {
            Response::error('创建订单异常: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 微信支付回调通知
     * 验签 -> 更新订单 -> 执行业务逻辑
     */
    public function notify()
    {
        try {
            $xmlData = file_get_contents('php://input');
            if (empty($xmlData)) {
                $this->xmlResponse('FAIL', '数据为空');
            }

            $notifyData = $this->parseXml($xmlData);

            // 验证签名
            if (!$this->verifyWechatSign($notifyData)) {
                $this->xmlResponse('FAIL', '签名验证失败');
            }

            if ($notifyData['return_code'] !== 'SUCCESS' || $notifyData['result_code'] !== 'SUCCESS') {
                $this->xmlResponse('FAIL', '支付失败');
            }

            $orderNo = $notifyData['out_trade_no'];
            $transactionId = $notifyData['transaction_id'];

            // 查询订单
            $orderStmt = $this->db->prepare("SELECT * FROM `orders` WHERE `order_no` = ?");
            $orderStmt->execute([$orderNo]);
            $order = $orderStmt->fetch(PDO::FETCH_ASSOC);

            if (!$order) {
                $this->xmlResponse('FAIL', '订单不存在');
            }

            if ($order['status'] === 'paid') {
                // 幂等处理：已支付直接返回成功
                $this->xmlResponse('SUCCESS', 'OK');
            }

            // 开启事务
            $this->db->beginTransaction();

            try {
                $now = date('Y-m-d H:i:s');

                // 更新订单状态
                $updateOrder = $this->db->prepare(
                    "UPDATE `orders` SET `status` = 'paid', `transaction_id` = ?, `paid_at` = ?, `updated_at` = ? WHERE `id` = ? AND `status` = 'pending'"
                );
                $updateOrder->execute([$transactionId, $now, $now, $order['id']]);

                if ($order['order_type'] === 'vip') {
                    // VIP订单：更新用户VIP状态
                    $this->activateVip($order['user_id'], $order['vip_plan_id']);
                }

                // 赠送积分（如有配置）
                $pointsConfig = $this->getSetting('points_per_download');
                if ($pointsConfig && intval($pointsConfig) > 0) {
                    $points = intval($pointsConfig);
                    $this->db->prepare("UPDATE `users` SET `points` = `points` + ? WHERE `id` = ?")
                        ->execute([$points, $order['user_id']]);
                    $this->db->prepare(
                        "INSERT INTO `user_points_log` (`user_id`, `points`, `type`, `description`, `created_at`) VALUES (?, ?, 'earn', ?, ?)"
                    )->execute([$order['user_id'], $points, '购买赠送积分', $now]);
                }

                $this->db->commit();
                $this->xmlResponse('SUCCESS', 'OK');

            } catch (\Exception $e) {
                $this->db->rollBack();
                $this->xmlResponse('FAIL', '处理失败: ' . $e->getMessage());
            }

        } catch (\Exception $e) {
            $this->xmlResponse('FAIL', '系统异常');
        }
    }

    /**
     * 查询订单状态
     */
    public function query()
    {
        try {
            $userId = Auth::required();

            $orderNo = isset($_GET['order_no']) ? trim($_GET['order_no']) : '';
            if (empty($orderNo)) {
                Response::error('缺少订单号', 400);
            }

            $stmt = $this->db->prepare(
                "SELECT `o`.`id`, `o`.`order_no`, `o`.`order_type`, `o`.`amount`, `o`.`pay_amount`, `o`.`payment_method`, `o`.`status`, `o`.`paid_at`, `o`.`created_at`,
                        `r`.`title` AS `resource_title`,
                        `vp`.`name` AS `vip_plan_name`
                 FROM `orders` `o`
                 LEFT JOIN `resources` `r` ON `r`.`id` = `o`.`resource_id`
                 LEFT JOIN `vip_plans` `vp` ON `vp`.`id` = `o`.`vip_plan_id`
                 WHERE `o`.`order_no` = ? AND `o`.`user_id` = ?"
            );
            $stmt->execute([$orderNo, $userId]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$order) {
                Response::error('订单不存在', 404);
            }

            Response::success($order, '查询成功');

        } catch (\Exception $e) {
            Response::error('查询订单异常: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 别名：支付回调通知
     */
    public function callback()
    {
        $this->notify();
    }

    // ============ 私有辅助方法 ============

    /**
     * 生成唯一订单号（年月日时分秒 + 6位随机数）
     */
    private function generateOrderNo()
    {
        return date('YmdHis') . str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
    }

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
     * 调用微信支付统一下单接口
     * 返回小程序调起支付所需参数
     */
    private function wechatPayUnifiedOrder($orderNo, $body, $amount, $openid)
    {
        $appid = $this->getSetting('wechat_appid');
        $mchId = $this->getSetting('wechat_mch_id');
        $apiKey = $this->getSetting('wechat_api_key');
        $notifyUrl = $this->getSetting('wechat_notify_url');

        if (empty($appid) || empty($mchId) || empty($apiKey)) {
            throw new \Exception('微信支付配置不完整');
        }

        if (empty($notifyUrl)) {
            // 默认回调地址
            $notifyUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/api/payment/notify';
        }

        // 金额转为分
        $totalFee = intval(round($amount * 100));
        if ($totalFee <= 0) {
            throw new \Exception('支付金额异常');
        }

        $nonceStr = $this->generateNonceStr(32);
        $ip = $this->getClientIp();

        // 组装请求参数
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

        // 生成签名
        $params['sign'] = $this->makeWechatSign($params, $apiKey);

        // 发送请求
        $xmlData = $this->arrayToXml($params);
        $result = $this->postWechatApi('https://api.mch.weixin.qq.com/pay/unifiedorder', $xmlData);
        $resultData = $this->parseXml($result);

        if (!$resultData || $resultData['return_code'] !== 'SUCCESS') {
            $errMsg = isset($resultData['return_msg']) ? $resultData['return_msg'] : '统一下单失败';
            throw new \Exception('微信下单失败: ' . $errMsg);
        }

        if ($resultData['result_code'] !== 'SUCCESS') {
            $errMsg = isset($resultData['err_code_des']) ? $resultData['err_code_des'] : '下单失败';
            throw new \Exception('微信下单失败: ' . $errMsg);
        }

        // 生成小程序支付参数
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
     * 激活用户VIP
     * 根据套餐天数延长VIP到期时间
     */
    private function activateVip($userId, $planId)
    {
        $planStmt = $this->db->prepare("SELECT `duration_days` FROM `vip_plans` WHERE `id` = ?");
        $planStmt->execute([$planId]);
        $plan = $planStmt->fetch(PDO::FETCH_ASSOC);

        if (!$plan) {
            throw new \Exception('VIP套餐不存在');
        }

        $userStmt = $this->db->prepare("SELECT `vip_expire_at` FROM `users` WHERE `id` = ?");
        $userStmt->execute([$userId]);
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);

        $durationDays = intval($plan['duration_days']);

        // 如果当前VIP未过期，在过期时间基础上叠加；否则从当前时间开始
        $baseTime = ($user['vip_expire_at'] && strtotime($user['vip_expire_at']) > time())
            ? $user['vip_expire_at']
            : date('Y-m-d H:i:s');

        $newExpireAt = date('Y-m-d H:i:s', strtotime($baseTime) + ($durationDays * 86400));

        // 根据天数判断VIP等级
        $vipLevel = 1; // 默认月度
        if ($durationDays >= 3650) {
            $vipLevel = 4; // 终身
        } elseif ($durationDays >= 365) {
            $vipLevel = 3; // 年度
        } elseif ($durationDays >= 90) {
            $vipLevel = 2; // 季度
        }

        // 终身会员设超远过期时间
        if ($durationDays >= 3650) {
            $newExpireAt = '2099-12-31 23:59:59';
        }

        $this->db->prepare("UPDATE `users` SET `vip_level` = ?, `vip_expire_at` = ?, `updated_at` = ? WHERE `id` = ?")
            ->execute([$vipLevel, $newExpireAt, date('Y-m-d H:i:s'), $userId]);
    }

    /**
     * 生成微信支付签名
     */
    private function makeWechatSign($params, $apiKey)
    {
        // 按key排序
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

    /**
     * 验证微信回调签名
     */
    private function verifyWechatSign($data)
    {
        if (!isset($data['sign'])) {
            return false;
        }
        $sign = $data['sign'];
        unset($data['sign']);

        $apiKey = $this->getSetting('wechat_api_key');
        if (empty($apiKey)) {
            return false;
        }

        return $this->makeWechatSign($data, $apiKey) === $sign;
    }

    /**
     * 数组转XML
     */
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

    /**
     * 解析XML为数组
     */
    private function parseXml($xml)
    {
        if (empty($xml)) {
            return null;
        }
        $backup = libxml_disable_entity_loader(true);
        $data = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        libxml_disable_entity_loader($backup);

        if (!$data) {
            return null;
        }
        return json_decode(json_encode($data), true);
    }

    /**
     * 返回XML格式响应（微信支付回调要求）
     */
    private function xmlResponse($code, $msg)
    {
        header('Content-Type: text/xml; charset=utf-8');
        echo '<xml><return_code><![CDATA[' . $code . ']]></return_code><return_msg><![CDATA[' . $msg . ']]></return_msg></xml>';
        exit;
    }

    /**
     * 向微信API发送POST请求
     */
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

    /**
     * 生成随机字符串
     */
    private function generateNonceStr($length = 32)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
        $str = '';
        for ($i = 0; $i < $length; $i++) {
            $str .= $chars[mt_rand(0, strlen($chars) - 1)];
        }
        return $str;
    }

    /**
     * 获取客户端IP
     */
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
