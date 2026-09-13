<?php
/**
 * 积分系统控制器
 * 签到、余额、记录、积分兑换资源/会员
 */

require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';

class PointsController
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * 获取积分余额和概况
     * GET /api/points/balance
     */
    public function balance()
    {
        try {
            $userId = Auth::required();
            $user = $this->db->fetch('SELECT points FROM users WHERE id = :id', [':id' => $userId]);
            $totalEarned = $this->db->fetch(
                "SELECT COALESCE(SUM(points),0) AS total FROM user_points_log WHERE user_id = :uid AND type = 'earn'",
                [':uid' => $userId]
            );
            $totalSpent = $this->db->fetch(
                "SELECT COALESCE(SUM(points),0) AS total FROM user_points_log WHERE user_id = :uid AND type = 'spend'",
                [':uid' => $userId]
            );
            $today = $this->db->fetch(
                "SELECT COUNT(*) AS cnt FROM user_points_log WHERE user_id = :uid AND type = 'earn' AND description LIKE '%签到%' AND DATE(created_at) = CURDATE()",
                [':uid' => $userId]
            );

            Response::success([
                'points'        => (int)($user['points'] ?? 0),
                'total_earned'  => (int)($totalEarned['total'] ?? 0),
                'total_spent'   => (int)($totalSpent['total'] ?? 0),
                'signed_today'  => (int)($today['cnt'] ?? 0) > 0,
            ]);
        } catch (\Exception $e) {
            Response::error('获取积分信息失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 每日签到
     * POST /api/points/signin
     */
    public function signin()
    {
        try {
            $userId = Auth::required();
            $user = $this->db->fetch('SELECT points FROM users WHERE id = :id', [':id' => $userId]);

            $today = $this->db->fetch(
                "SELECT COUNT(*) AS cnt FROM user_points_log WHERE user_id = :uid AND type = 'earn' AND description LIKE '%签到%' AND DATE(created_at) = CURDATE()",
                [':uid' => $userId]
            );
            if ((int)($today['cnt'] ?? 0) > 0) {
                Response::error('今日已签到，请明天再来', 400);
            }

            $settings = $this->getSettings();
            $points = (int)($settings['daily_signin_points'] ?? 5);

            // 连续签到奖励：连续N天多给N积分，最高7天
            $streak = $this->db->fetch(
                "SELECT COUNT(*) AS cnt FROM user_points_log WHERE user_id = :uid AND type = 'earn' AND description LIKE '%签到%' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)",
                [':uid' => $userId]
            );
            $streakDays = min((int)($streak['cnt'] ?? 0) + 1, 7);
            $bonus = $streakDays >= 3 ? $streakDays : 0;
            $totalPoints = $points + $bonus;

            $newBalance = (int)($user['points'] ?? 0) + $totalPoints;
            $this->db->update('users', ['points' => $newBalance], 'id = :id', [':id' => $userId]);

            $desc = '每日签到 +' . $points;
            if ($bonus > 0) $desc .= '（连续' . $streakDays . '天奖励+' . $bonus . '）';

            $this->db->insert('user_points_log', [
                'user_id'       => $userId,
                'points'        => $totalPoints,
                'type'          => 'earn',
                'description'   => $desc,
                'balance_after' => $newBalance,
                'created_at'    => date('Y-m-d H:i:s'),
            ]);

            Response::success([
                'points'      => $totalPoints,
                'balance'     => $newBalance,
                'streak_days' => $streakDays,
                'bonus'       => $bonus,
            ], '签到成功，+' . $totalPoints . '积分');
        } catch (\Exception $e) {
            Response::error('签到失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 积分记录
     * GET /api/points/log?page=1&page_size=20
     */
    public function log()
    {
        try {
            $userId = Auth::required();
            $data = $GLOBALS['REQUEST_DATA'] ?? [];
            $page = max(1, (int)($data['page'] ?? 1));
            $pageSize = max(1, min(50, (int)($data['page_size'] ?? 20)));
            $offset = ($page - 1) * $pageSize;

            $total = $this->db->count('user_points_log', 'user_id = :uid', [':uid' => $userId]);
            $list = $this->db->fetchAll(
                "SELECT id, points, type, description, balance_after, created_at FROM user_points_log WHERE user_id = :uid ORDER BY id DESC LIMIT {$pageSize} OFFSET {$offset}",
                [':uid' => $userId]
            );

            Response::paginate($list, $total, $page, $pageSize);
        } catch (\Exception $e) {
            Response::error('获取积分记录失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 积分兑换资源
     * POST /api/points/redeem_resource
     * 参数：resource_id
     */
    public function redeem_resource()
    {
        try {
            $userId = Auth::required();
            $data = $GLOBALS['REQUEST_DATA'] ?? [];
            $resourceId = (int)($data['resource_id'] ?? 0);

            if ($resourceId <= 0) {
                Response::error('资源ID无效', 400);
            }

            $resource = $this->db->fetch('SELECT * FROM resources WHERE id = :id AND status = 1', [':id' => $resourceId]);
            if (!$resource) {
                Response::error('资源不存在', 404);
            }
            if ((int)$resource['points_price'] <= 0) {
                Response::error('该资源不支持积分兑换', 400);
            }

            // 检查是否已兑换（已购买）
            $existing = $this->db->fetch(
                "SELECT id FROM orders WHERE user_id = :uid AND resource_id = :rid AND status = 'paid' LIMIT 1",
                [':uid' => $userId, ':rid' => $resourceId]
            );
            if ($existing) {
                Response::error('您已拥有该资源', 400);
            }

            $user = $this->db->fetch('SELECT points FROM users WHERE id = :id', [':id' => $userId]);
            $pointsPrice = (int)$resource['points_price'];
            $userPoints = (int)($user['points'] ?? 0);

            if ($userPoints < $pointsPrice) {
                Response::error('积分不足，需要' . $pointsPrice . '积分，当前' . $userPoints . '积分', 400);
            }

            // 扣除积分
            $newBalance = $userPoints - $pointsPrice;
            $this->db->update('users', ['points' => $newBalance], 'id = :id', [':id' => $userId]);

            // 记录日志
            $this->db->insert('user_points_log', [
                'user_id'       => $userId,
                'points'        => $pointsPrice,
                'type'          => 'spend',
                'description'   => '积分兑换资源：' . $resource['title'],
                'balance_after' => $newBalance,
                'created_at'    => date('Y-m-d H:i:s'),
            ]);

            // 创建已支付订单
            $this->db->insert('orders', [
                'user_id'       => $userId,
                'resource_id'   => $resourceId,
                'order_no'      => 'PTS' . date('YmdHis') . mt_rand(1000, 9999),
                'amount'        => 0,
                'pay_method'    => 'points',
                'status'        => 'paid',
                'paid_at'       => date('Y-m-d H:i:s'),
                'created_at'    => date('Y-m-d H:i:s'),
            ]);

            Response::success([
                'balance' => $newBalance,
            ], '兑换成功，-' . $pointsPrice . '积分');
        } catch (\Exception $e) {
            Response::error('兑换失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 积分兑换VIP
     * POST /api/points/redeem_vip
     * 参数：plan_id
     */
    public function redeem_vip()
    {
        try {
            $userId = Auth::required();
            $data = $GLOBALS['REQUEST_DATA'] ?? [];
            $planId = (int)($data['plan_id'] ?? 0);

            if ($planId <= 0) {
                Response::error('套餐ID无效', 400);
            }

            $plan = $this->db->fetch('SELECT * FROM vip_plans WHERE id = :id AND status = 1', [':id' => $planId]);
            if (!$plan) {
                Response::error('套餐不存在', 404);
            }
            if ((int)$plan['points_price'] <= 0) {
                Response::error('该套餐不支持积分兑换', 400);
            }

            $user = $this->db->fetch('SELECT points, vip_level, vip_expire_at FROM users WHERE id = :id', [':id' => $userId]);
            $pointsPrice = (int)$plan['points_price'];
            $userPoints = (int)($user['points'] ?? 0);

            if ($userPoints < $pointsPrice) {
                Response::error('积分不足，需要' . $pointsPrice . '积分，当前' . $userPoints . '积分', 400);
            }

            // 计算VIP到期时间
            $now = time();
            $currentExpire = strtotime($user['vip_expire_at'] ?? '2000-01-01');
            $baseTime = max($now, $currentExpire > $now ? $currentExpire : $now);
            $newExpire = date('Y-m-d H:i:s', $baseTime + (int)$plan['duration_days'] * 86400);

            // 扣除积分
            $newBalance = $userPoints - $pointsPrice;
            $this->db->update('users', [
                'points'        => $newBalance,
                'vip_level'     => 1,
                'vip_expire_at' => $newExpire,
            ], 'id = :id', [':id' => $userId]);

            // 记录日志
            $this->db->insert('user_points_log', [
                'user_id'       => $userId,
                'points'        => $pointsPrice,
                'type'          => 'spend',
                'description'   => '积分兑换VIP：' . $plan['name'],
                'balance_after' => $newBalance,
                'created_at'    => date('Y-m-d H:i:s'),
            ]);

            Response::success([
                'balance'     => $newBalance,
                'vip_expire'  => $newExpire,
                'plan_name'   => $plan['name'],
            ], 'VIP开通成功');
        } catch (\Exception $e) {
            Response::error('兑换失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 积分明细排行
     * GET /api/points/ranking?page_size=10
     */
    public function ranking()
    {
        try {
            $data = $GLOBALS['REQUEST_DATA'] ?? [];
            $pageSize = max(1, min(50, (int)($data['page_size'] ?? 10)));

            $list = $this->db->fetchAll(
                "SELECT id, nickname, avatar_url, points FROM users WHERE status = 1 ORDER BY points DESC LIMIT {$pageSize}"
            );
            Response::success($list);
        } catch (\Exception $e) {
            Response::error('获取排行失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 分享资源获得积分
     * POST /api/points/share
     * 参数：resource_id
     */
    public function share()
    {
        try {
            $userId = Auth::required();
            $data = $GLOBALS['REQUEST_DATA'] ?? [];
            $resourceId = (int)($data['resource_id'] ?? 0);

            if ($resourceId <= 0) {
                Response::error('参数错误', 400);
            }

            // 每个资源每天只能通过分享获得1次积分
            $today = $this->db->fetch(
                "SELECT COUNT(*) AS cnt FROM user_points_log WHERE user_id = :uid AND type = 'earn' AND description LIKE '%分享%' AND description LIKE '%#{$resourceId}%' AND DATE(created_at) = CURDATE()",
                [':uid' => $userId]
            );
            if ((int)($today['cnt'] ?? 0) > 0) {
                Response::error('该资源今日已通过分享获得积分', 400);
            }

            $settings = $this->getSettings();
            $points = (int)($settings['points_per_share'] ?? 2);
            $result = $this->awardPoints($userId, $points, '分享资源 #' . $resourceId);
            Response::success($result, '分享成功，+' . $points . '积分');
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * 评论资源获得积分
     * POST /api/points/comment
     * 参数：resource_id
     */
    public function comment()
    {
        try {
            $userId = Auth::required();
            $data = $GLOBALS['REQUEST_DATA'] ?? [];
            $resourceId = (int)($data['resource_id'] ?? 0);

            if ($resourceId <= 0) {
                Response::error('参数错误', 400);
            }

            // 每个资源只能通过评论获得1次积分
            $existing = $this->db->fetch(
                "SELECT COUNT(*) AS cnt FROM user_points_log WHERE user_id = :uid AND type = 'earn' AND description LIKE '%评论%' AND description LIKE '%#{$resourceId}%'",
                [':uid' => $userId]
            );
            if ((int)($existing['cnt'] ?? 0) > 0) {
                Response::error('该资源已通过评论获得过积分', 400);
            }

            $settings = $this->getSettings();
            $points = (int)($settings['points_per_comment'] ?? 3);
            $result = $this->awardPoints($userId, $points, '评论资源 #' . $resourceId);
            Response::success($result, '评论成功，+' . $points . '积分');
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * 收藏资源获得积分
     * POST /api/points/favorite
     * 参数：resource_id
     */
    public function favorite()
    {
        try {
            $userId = Auth::required();
            $data = $GLOBALS['REQUEST_DATA'] ?? [];
            $resourceId = (int)($data['resource_id'] ?? 0);

            if ($resourceId <= 0) {
                Response::error('参数错误', 400);
            }

            // 每日收藏积分上限5次
            $today = $this->db->fetch(
                "SELECT COUNT(*) AS cnt FROM user_points_log WHERE user_id = :uid AND type = 'earn' AND description LIKE '%收藏%' AND DATE(created_at) = CURDATE()",
                [':uid' => $userId]
            );
            if ((int)($today['cnt'] ?? 0) >= 5) {
                Response::error('今日收藏积分已达上限', 400);
            }

            $settings = $this->getSettings();
            $points = (int)($settings['points_per_favorite'] ?? 1);
            $result = $this->awardPoints($userId, $points, '收藏资源 #' . $resourceId);
            Response::success($result, '收藏成功，+' . $points . '积分');
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * 完善资料获得积分（一次性）
     * POST /api/points/complete_profile
     */
    public function complete_profile()
    {
        try {
            $userId = Auth::required();

            $existing = $this->db->fetch(
                "SELECT COUNT(*) AS cnt FROM user_points_log WHERE user_id = :uid AND type = 'earn' AND description LIKE '%完善资料%'",
                [':uid' => $userId]
            );
            if ((int)($existing['cnt'] ?? 0) > 0) {
                Response::error('已领取过完善资料奖励', 400);
            }

            $settings = $this->getSettings();
            $points = (int)($settings['points_complete_profile'] ?? 20);
            $result = $this->awardPoints($userId, $points, '完善个人资料');
            Response::success($result, '领取成功，+' . $points . '积分');
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * 邀请好友获得积分
     * POST /api/points/invite
     * 参数：invite_code（被邀请用户的注册邀请码）
     */
    public function invite()
    {
        try {
            $userId = Auth::required();
            $data = $GLOBALS['REQUEST_DATA'] ?? [];
            $code = trim($data['invite_code'] ?? '');

            if ($code === '') {
                Response::error('请输入邀请码', 400);
            }

            // 验证邀请码（使用用户ID作为简单邀请码）
            $inviterId = (int)base_convert(strtoupper($code), 36, 10);
            if ($inviterId <= 0) {
                Response::error('邀请码格式无效', 400);
            }
            if ($inviterId === $userId) {
                Response::error('不能使用自己的邀请码', 400);
            }

            $inviter = $this->db->fetch('SELECT id, nickname FROM users WHERE id = :id AND status = 1', [':id' => $inviterId]);
            if (!$inviter) {
                Response::error('邀请码对应的用户不存在', 400);
            }

            // 检查是否已使用过该邀请码
            $existing = $this->db->fetch(
                "SELECT COUNT(*) AS cnt FROM user_points_log WHERE user_id = :uid AND description LIKE '%邀请%' AND description LIKE '%#{$inviterId}%'",
                [':uid' => $userId]
            );
            if ((int)($existing['cnt'] ?? 0) > 0) {
                Response::error('已使用过邀请码', 400);
            }

            // 每人最多通过邀请码获得3次积分
            $total = $this->db->fetch(
                "SELECT COUNT(*) AS cnt FROM user_points_log WHERE user_id = :uid AND description LIKE '%邀请%'",
                [':uid' => $userId]
            );
            if ((int)($total['cnt'] ?? 0) >= 3) {
                Response::error('邀请码使用次数已达上限', 400);
            }

            $settings = $this->getSettings();
            $points = (int)($settings['points_per_invite'] ?? 30);

            // 被邀请者获得积分
            $result = $this->awardPoints($userId, $points, '使用邀请码 #' . $inviterId);

            // 邀请者也获得积分
            $inviterPoints = (int)($settings['points_invite_reward'] ?? 20);
            $this->awardPoints($inviterId, $inviterPoints, '邀请好友 #' . $userId);

            Response::success($result, '邀请成功，+' . $points . '积分');
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * 获取积分规则（前端展示用）
     * GET /api/points/rules
     */
    public function rules()
    {
        try {
            $settings = $this->getSettings();
            Response::success([
                'signin'             => ['name' => '每日签到', 'points' => (int)($settings['daily_signin_points'] ?? 5), 'desc' => '连续签到可获得额外奖励', 'limit' => '每日1次'],
                'download'           => ['name' => '下载资源', 'points' => (int)($settings['points_per_download'] ?? 10), 'desc' => '每次下载资源获得积分', 'limit' => '无限制'],
                'share'              => ['name' => '分享资源', 'points' => (int)($settings['points_per_share'] ?? 2), 'desc' => '分享资源给好友', 'limit' => '每资源每日1次'],
                'comment'            => ['name' => '评论资源', 'points' => (int)($settings['points_per_comment'] ?? 3), 'desc' => '对资源发表评论', 'limit' => '每资源1次'],
                'favorite'           => ['name' => '收藏资源', 'points' => (int)($settings['points_per_favorite'] ?? 1), 'desc' => '收藏喜欢的资源', 'limit' => '每日5次'],
                'complete_profile'   => ['name' => '完善资料', 'points' => (int)($settings['points_complete_profile'] ?? 20), 'desc' => '完善个人资料信息', 'limit' => '仅限1次'],
                'invite'             => ['name' => '邀请好友', 'points' => (int)($settings['points_per_invite'] ?? 30), 'desc' => '输入好友邀请码双方均获奖', 'limit' => '最多3次'],
                'register'           => ['name' => '注册奖励', 'points' => (int)($settings['register_gift_points'] ?? 50), 'desc' => '新用户注册即送', 'limit' => '仅限1次'],
            ]);
        } catch (\Exception $e) {
            Response::error('获取规则失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 通用积分发放方法（供其他控制器调用）
     * @param int $userId 用户ID
     * @param int $points 积分数
     * @param string $desc 描述
     * @return array
     */
    public function awardPoints($userId, $points, $desc)
    {
        if ($points <= 0) return ['points' => 0, 'balance' => 0];

        $user = $this->db->fetch('SELECT points FROM users WHERE id = :id', [':id' => $userId]);
        $newBalance = (int)($user['points'] ?? 0) + $points;

        $this->db->update('users', ['points' => $newBalance], 'id = :id', [':id' => $userId]);
        $this->db->insert('user_points_log', [
            'user_id'       => $userId,
            'points'        => $points,
            'type'          => 'earn',
            'description'   => $desc,
            'balance_after' => $newBalance,
            'created_at'    => date('Y-m-d H:i:s'),
        ]);

        return ['points' => $points, 'balance' => $newBalance];
    }

    private function getSettings()
    {
        $rows = $this->db->fetchAll("SELECT setting_key, setting_value FROM system_settings WHERE setting_group = 'points'");
        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        return $settings;
    }
}
