<?php
/**
 * 下载控制器
 * 处理资源下载权限校验和下载执行
 * 支持免费、付费、会员免费三种下载模式
 */

require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/UrlHelper.php';

class DownloadController
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * 检查用户是否可以下载指定资源
     * 返回: can_download, reason, price, vip_price
     */
    public function check()
    {
        try {
            $userId = Auth::required();
            $resourceId = isset($_GET['resource_id']) ? intval($_GET['resource_id']) : 0;
            if ($resourceId <= 0) {
                $resourceId = isset($GLOBALS['REQUEST_DATA']['resource_id']) ? intval($GLOBALS['REQUEST_DATA']['resource_id']) : 0;
            }

            if ($resourceId <= 0) {
                Response::error('缺少资源ID', 400);
            }

            // 获取资源信息
            $resStmt = $this->db->prepare(
                "SELECT `id`, `title`, `price_type`, `price`, `vip_price`, `points_price` FROM `resources` WHERE `id` = ? AND `status` = 'approved'"
            );
            $resStmt->execute([$resourceId]);
            $resource = $resStmt->fetch(PDO::FETCH_ASSOC);

            if (!$resource) {
            file_put_contents('E:/ziliaoku/runtime/download_debug.log', date('H:i:s') . ' | ERROR: 资源不存在 id=' . $resourceId . '
', FILE_APPEND);
                Response::error('资源不存在', 404);
            }

            // 获取用户信息
            $userStmt = $this->db->prepare("SELECT `vip_level`, `vip_expire_at`, `points` FROM `users` WHERE `id` = ?");
            $userStmt->execute([$userId]);
            $user = $userStmt->fetch(PDO::FETCH_ASSOC);

            $isVip = $this->isVipValid($user);

            // 检查是否已购买过该资源
            $paidStmt = $this->db->prepare(
                "SELECT COUNT(*) FROM `orders` WHERE `user_id` = ? AND `resource_id` = ? AND `status` = 'paid' AND `order_type` = 'resource'"
            );
            $paidStmt->execute([$userId, $resourceId]);
            $hasPaid = $paidStmt->fetchColumn() > 0;

            if ($hasPaid) {
                // 已购买过，可以直接下载
                Response::success([
                    'can_download' => true,
                    'reason' => '已购买',
                    'price' => $resource['price'],
                    'vip_price' => $resource['vip_price'],
                    'is_paid' => true,
                ], '可以下载');
                return;
            }

            switch ($resource['price_type']) {
                case 'free':
                    // 免费资源：检查每日下载限制
                    $freeLimit = intval($this->getSetting('free_download_limit') ?: 3);
                    $todayCount = $this->getTodayDownloadCount($userId);
                    if ($todayCount >= $freeLimit) {
                        Response::success([
                            'can_download' => false,
                            'reason' => '今日免费下载次数已用完(' . $freeLimit . '次/天)',
                            'price' => 0,
                            'vip_price' => 0,
                        ], '已达今日限制');
                    } else {
                        Response::success([
                            'can_download' => true,
                            'reason' => '免费下载（今日剩余' . ($freeLimit - $todayCount) . '次）',
                            'price' => 0,
                            'vip_price' => 0,
                        ], '可以下载');
                    }
                    break;

                case 'member_free':
                    // 会员免费：检查是否指定了VIP等级
                    $vipFreeLevels = $resource['vip_free_levels'] ? explode(',', $resource['vip_free_levels']) : [];
                    if ($isVip && (empty($vipFreeLevels) || in_array((string)$user['vip_level'], $vipFreeLevels))) {
                        // VIP等级匹配或未指定等级（全部VIP免费）
                        Response::success([
                            'can_download' => true,
                            'reason' => 'VIP会员免费下载',
                            'price' => $resource['price'],
                            'vip_price' => 0,
                        ], '可以下载');
                    } else {
                        $levelNames = [1 => '月卡', 2 => '季卡', 3 => '年卡', 4 => '终身'];
                        $freeLevelStr = empty($vipFreeLevels) ? 'VIP' : implode('、', array_map(function($l) use ($levelNames) { return $levelNames[$l] ?? $l; }, $vipFreeLevels));
                        Response::success([
                            'can_download' => false,
                            'reason' => $freeLevelStr . '会员免费，普通用户需付费',
                            'price' => $resource['price'],
                            'vip_price' => $resource['vip_price'],
                            'need_vip' => true,
                            'vip_free_levels' => $vipFreeLevels,
                        ], '需要VIP或付费');
                    }
                    break;

                case 'paid':
                    // 付费资源
                    $actualPrice = $isVip ? $resource['vip_price'] : $resource['price'];
                    Response::success([
                        'can_download' => false,
                        'reason' => $isVip ? 'VIP特惠价' : '付费资源',
                        'price' => $resource['price'],
                        'vip_price' => $resource['vip_price'],
                        'actual_price' => $actualPrice,
                    ], '需要付费');
                    break;

                default:
                    Response::error('未知的价格类型', 400);
            }

        } catch (\Exception $e) {
            Response::error('检查下载权限异常: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 执行下载
     * 校验权限 -> 记录下载 -> 返回文件URL
     */
    public function download()
    {
        try {
            $userId = Auth::required();
            $resourceId = isset($_POST['resource_id']) ? intval($_POST['resource_id']) : 0;
            if ($resourceId <= 0) {
                $resourceId = isset($GLOBALS['REQUEST_DATA']['resource_id']) ? intval($GLOBALS['REQUEST_DATA']['resource_id']) : 0;
            }

            // 调试日志
            $debugLog = date('H:i:s') . ' | POST=' . json_encode($_POST) . ' | REQ=' . json_encode($GLOBALS['REQUEST_DATA'] ?? []) . ' | CT=' . ($_SERVER['CONTENT_TYPE'] ?? 'N/A') . ' | resource_id=' . $resourceId . "\n";
            file_put_contents('E:/ziliaoku/runtime/download_debug.log', $debugLog, FILE_APPEND);

            if ($resourceId <= 0) {
                Response::error('缺少资源ID', 400);
            }

            // 获取资源信息
            $resStmt = $this->db->prepare(
                "SELECT * FROM `resources` WHERE `id` = ? AND `status` = 'approved'"
            );
            $resStmt->execute([$resourceId]);
            $resource = $resStmt->fetch(PDO::FETCH_ASSOC);

            if (!$resource) {
            file_put_contents('E:/ziliaoku/runtime/download_debug.log', date('H:i:s') . ' | ERROR: 资源不存在 id=' . $resourceId . '
', FILE_APPEND);
                Response::error('资源不存在', 404);
            }

            // 获取用户信息
            $userStmt = $this->db->prepare("SELECT `vip_level`, `vip_expire_at` FROM `users` WHERE `id` = ?");
            $userStmt->execute([$userId]);
            $user = $userStmt->fetch(PDO::FETCH_ASSOC);

            $isVip = $this->isVipValid($user);

            // 检查是否已购买
            $paidStmt = $this->db->prepare(
                "SELECT `id` FROM `orders` WHERE `user_id` = ? AND `resource_id` = ? AND `status` = 'paid' AND `order_type` = 'resource'"
            );
            $paidStmt->execute([$userId, $resourceId]);
            $paidOrder = $paidStmt->fetch(PDO::FETCH_ASSOC);

            $canDownload = false;
            $orderId = null;

            switch ($resource['price_type']) {
                case 'free':
                    // 免费资源：检查每日限制
                    $freeLimit = intval($this->getSetting('free_download_limit') ?: 3);
                    $todayCount = $this->getTodayDownloadCount($userId);
                    if ($todayCount >= $freeLimit) {
            file_put_contents('E:/ziliaoku/runtime/download_debug.log', date('H:i:s') . ' | ERROR: 今日次数用完
', FILE_APPEND);
                        Response::error('今日免费下载次数已用完，请明天再试或升级VIP', 403);
                    }
                    $canDownload = true;
                    break;

                case 'member_free':
                    if ($paidOrder) {
                        $canDownload = true;
                        $orderId = $paidOrder['id'];
                    } elseif ($isVip) {
                        $canDownload = true;
                    } else {
            file_put_contents('E:/ziliaoku/runtime/download_debug.log', date('H:i:s') . ' | ERROR: VIP免费 非VIP
', FILE_APPEND);
                        Response::error('该资源VIP会员免费，普通用户需购买', 403);
                    }
                    break;

                case 'paid':
                    if ($paidOrder) {
                        $canDownload = true;
                        $orderId = $paidOrder['id'];
                    } else {
            file_put_contents('E:/ziliaoku/runtime/download_debug.log', date('H:i:s') . ' | ERROR: 需购买
', FILE_APPEND);
                        Response::error('请先购买该资源', 403);
                    }
                    break;
            }

            if (!$canDownload) {
            file_put_contents('E:/ziliaoku/runtime/download_debug.log', date('H:i:s') . ' | ERROR: 无下载权限
', FILE_APPEND);
                Response::error('无下载权限', 403);
            }

            // 记录下载
            $ip = $this->getClientIp();
            $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 500) : '';

            $insertStmt = $this->db->prepare(
                "INSERT INTO `downloads` (`user_id`, `resource_id`, `order_id`, `ip`, `user_agent`, `created_at`) VALUES (?, ?, ?, ?, ?, ?)"
            );
            $now = date('Y-m-d H:i:s');
            $insertStmt->execute([$userId, $resourceId, $orderId, $ip, $userAgent, $now]);

            // 更新资源下载量和用户累计下载量
            $this->db->prepare("UPDATE `resources` SET `download_count` = `download_count` + 1 WHERE `id` = ?")
                ->execute([$resourceId]);
            $this->db->prepare("UPDATE `users` SET `total_downloads` = `total_downloads` + 1 WHERE `id` = ?")
                ->execute([$userId]);

            // 获取文件信息
            $fileStmt = $this->db->prepare(
                "SELECT `file_name`, `file_url`, `file_size`, `file_type` FROM `resource_files` WHERE `resource_id` = ? ORDER BY `sort_order` ASC"
            );
            $fileStmt->execute([$resourceId]);
            $files = UrlHelper::fixFileListUrls($fileStmt->fetchAll(PDO::FETCH_ASSOC));

            Response::success([
                'resource' => [
                    'id' => $resource['id'],
                    'title' => $resource['title'],
                    'file_url' => UrlHelper::fullUrl($resource['file_url']),
                    'file_size' => $resource['file_size'],
                    'file_type' => $resource['file_type'],
                    'file_suffix' => $resource['file_suffix'],
                ],
                'files' => $files,
                'download_time' => $now,
            ], '下载成功');

        } catch (\Exception $e) {
            Response::error('下载异常: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 别名：执行下载记录
     */
    public function record()
    {
        $this->download();
    }

    /**
     * 获取所有下载记录（管理员）
     */
    public function all()
    {
        try {
            $page = max(1, intval($GLOBALS['REQUEST_DATA']['page'] ?? 1));
            $pageSize = min(50, max(1, intval($GLOBALS['REQUEST_DATA']['page_size'] ?? 20)));
            $offset = ($page - 1) * $pageSize;

            $countRow = $this->db->fetch('SELECT COUNT(*) AS cnt FROM downloads');
            $total = intval($countRow['cnt']);

            $list = $this->db->fetchAll(
                'SELECT d.*, u.nickname, r.title AS resource_title
                 FROM downloads d
                 LEFT JOIN users u ON d.user_id = u.id
                 LEFT JOIN resources r ON d.resource_id = r.id
                 ORDER BY d.created_at DESC
                 LIMIT :limit OFFSET :offset',
                [':limit' => $pageSize, ':offset' => $offset]
            );

            Response::success([
                'list' => $list,
                'total' => $total,
                'page' => $page,
                'page_size' => $pageSize,
            ]);

        } catch (\Exception $e) {
            Response::error('获取下载记录异常: ' . $e->getMessage(), 500);
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
     * 获取用户今日下载次数
     */
    private function getTodayDownloadCount($userId)
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM `downloads` WHERE `user_id` = ? AND DATE(`created_at`) = CURDATE()"
        );
        $stmt->execute([$userId]);
        return intval($stmt->fetchColumn());
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
     * 获取客户端真实IP
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
        return '0.0.0.0';
    }
}
