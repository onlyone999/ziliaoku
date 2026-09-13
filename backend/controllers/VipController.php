<?php
/**
 * VIP控制器
 * 处理VIP套餐查询、VIP信息查询、VIP状态检查
 */

require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';

class VipController
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * 获取所有有效VIP套餐
     * 按 sort_order 降序排列，包含价格信息
     */
    public function getPlans()
    {
        try {
            $sql = "SELECT `id`, `name`, `duration_days`, `original_price`, `price`, `points_price`, `description`, `sort_order`
                    FROM `vip_plans`
                    WHERE `status` = 1
                    ORDER BY `sort_order` DESC, `id` ASC";
            $stmt = $this->db->query($sql);
            $list = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // 计算每日均价，方便前端展示性价比
            foreach ($list as &$plan) {
                $plan['daily_price'] = $plan['duration_days'] > 0
                    ? round($plan['price'] / $plan['duration_days'], 2)
                    : 0;
                $plan['discount_rate'] = $plan['original_price'] > 0
                    ? round($plan['price'] / $plan['original_price'] * 10, 1)
                    : 0;
            }

            Response::success($list, '获取成功');

        } catch (\Exception $e) {
            Response::error('获取VIP套餐异常: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 获取当前用户的VIP信息
     */
    public function getInfo()
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
            ], '获取成功');

        } catch (\Exception $e) {
            Response::error('获取VIP信息异常: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 检查当前用户VIP是否有效
     * 如果已过期则自动更新 vip_level 为 0
     */
    public function checkVip()
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

            // 如果VIP已过期，自动降级
            if ($user['vip_level'] > 0 && !$isVip) {
                $this->db->prepare(
                    "UPDATE `users` SET `vip_level` = 0, `updated_at` = ? WHERE `id` = ?"
                )->execute([date('Y-m-d H:i:s'), $userId]);

                Response::success([
                    'is_vip' => false,
                    'vip_level' => 0,
                    'expired' => true,
                    'message' => 'VIP已过期，已自动降级为普通用户',
                ], 'VIP已过期');
                return;
            }

            // 计算剩余天数
            $remainDays = 0;
            if ($isVip && $user['vip_expire_at']) {
                $remainDays = max(0, floor((strtotime($user['vip_expire_at']) - time()) / 86400));
            }

            Response::success([
                'is_vip' => $isVip,
                'vip_level' => $user['vip_level'],
                'vip_expire_at' => $user['vip_expire_at'],
                'remain_days' => $remainDays,
                'expired' => false,
            ], $isVip ? 'VIP有效' : '非VIP用户');

        } catch (\Exception $e) {
            Response::error('检查VIP状态异常: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 别名：获取VIP套餐列表
     */
    public function plans()
    {
        $this->getPlans();
    }

    /**
     * 创建VIP订单（委托给 PaymentController）
     */
    public function createOrder()
    {
        $data = $GLOBALS['REQUEST_DATA'] ?? [];
        $_POST['order_type'] = 'vip';
        $_POST['vip_plan_id'] = $data['plan_id'] ?? $data['vip_plan_id'] ?? 0;
        require_once __DIR__ . '/PaymentController.php';
        $ctrl = new PaymentController();
        $ctrl->create();
    }

    /**
     * 别名：支付回调（委托给 PaymentController）
     */
    public function callback()
    {
        require_once __DIR__ . '/PaymentController.php';
        $ctrl = new PaymentController();
        $ctrl->notify();
    }

    /**
     * 创建VIP套餐（管理员）
     */
    public function createPlan()
    {
        try {
            $data = $GLOBALS['REQUEST_DATA'] ?? [];
            $name = trim($data['name'] ?? '');
            $durationDays = intval($data['duration_days'] ?? 0);
            $originalPrice = floatval($data['original_price'] ?? 0);
            $price = floatval($data['price'] ?? 0);
            $pointsPrice = intval($data['points_price'] ?? 0);
            $description = trim($data['description'] ?? '');
            $sortOrder = intval($data['sort_order'] ?? 0);

            if (empty($name)) {
                Response::error('套餐名称不能为空', 400);
            }
            if ($durationDays <= 0) {
                Response::error('有效天数必须大于0', 400);
            }
            if ($price <= 0) {
                Response::error('售价必须大于0', 400);
            }

            $id = $this->db->insert('vip_plans', [
                'name' => $name,
                'duration_days' => $durationDays,
                'original_price' => $originalPrice,
                'price' => $price,
                'points_price' => $pointsPrice,
                'description' => $description,
                'sort_order' => $sortOrder,
                'status' => 1,
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            Response::success(['id' => $id], '创建成功');

        } catch (\Exception $e) {
            Response::error('创建VIP套餐异常: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 更新VIP套餐（管理员）
     */
    public function updatePlan()
    {
        try {
            $data = $GLOBALS['REQUEST_DATA'] ?? [];
            $id = intval($data['id'] ?? 0);
            if ($id <= 0) {
                Response::error('缺少套餐ID', 400);
            }

            $updateData = [];
            if (isset($data['name'])) $updateData['name'] = trim($data['name']);
            if (isset($data['duration_days'])) $updateData['duration_days'] = intval($data['duration_days']);
            if (isset($data['original_price'])) $updateData['original_price'] = floatval($data['original_price']);
            if (isset($data['price'])) $updateData['price'] = floatval($data['price']);
            if (isset($data['points_price'])) $updateData['points_price'] = intval($data['points_price']);
            if (isset($data['description'])) $updateData['description'] = trim($data['description']);
            if (isset($data['sort_order'])) $updateData['sort_order'] = intval($data['sort_order']);
            if (isset($data['status'])) $updateData['status'] = intval($data['status']);

            $this->db->update('vip_plans', $updateData, 'id = :id', [':id' => $id]);

            Response::success([], '更新成功');

        } catch (\Exception $e) {
            Response::error('更新VIP套餐异常: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 删除VIP套餐（管理员）
     */
    public function deletePlan()
    {
        try {
            $id = intval($GLOBALS['REQUEST_DATA']['id'] ?? 0);
            if ($id <= 0) {
                Response::error('缺少套餐ID', 400);
            }

            $this->db->delete('vip_plans', 'id = :id', [':id' => $id]);

            Response::success([], '删除成功');

        } catch (\Exception $e) {
            Response::error('删除VIP套餐异常: ' . $e->getMessage(), 500);
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
}
