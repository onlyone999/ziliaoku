<?php
/**
 * 订单控制器
 * 处理用户订单列表、详情、创建、支付、取消、状态查询
 */

require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';

class OrderController
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * 获取用户订单列表（分页，可按状态筛选）
     */
    public function list()
    {
        try {
            $userId = Auth::required();

            $page = max(1, intval($GLOBALS['REQUEST_DATA']['page'] ?? 1));
            $pageSize = min(50, max(1, intval($GLOBALS['REQUEST_DATA']['page_size'] ?? 10)));
            $offset = ($page - 1) * $pageSize;

            $where = "`o`.`user_id` = :uid";
            $params = [':uid' => $userId];

            // 可选筛选订单状态
            $status = isset($GLOBALS['REQUEST_DATA']['status']) ? trim($GLOBALS['REQUEST_DATA']['status']) : '';
            if (!empty($status) && in_array($status, ['pending', 'paid', 'cancelled', 'refunded'])) {
                $where .= " AND `o`.`status` = :status";
                $params[':status'] = $status;
            }

            // 可选筛选订单类型
            $orderType = isset($GLOBALS['REQUEST_DATA']['order_type']) ? trim($GLOBALS['REQUEST_DATA']['order_type']) : '';
            if (!empty($orderType) && in_array($orderType, ['resource', 'vip'])) {
                $where .= " AND `o`.`order_type` = :otype";
                $params[':otype'] = $orderType;
            }

            $countRow = $this->db->fetch("SELECT COUNT(*) AS cnt FROM `orders` `o` WHERE {$where}", $params);
            $total = intval($countRow['cnt']);

            $sql = "SELECT `o`.`id`, `o`.`order_no`, `o`.`order_type`, `o`.`amount`, `o`.`pay_amount`,
                           `o`.`payment_method`, `o`.`status`, `o`.`paid_at`, `o`.`created_at`,
                           `r`.`title` AS `resource_title`, `r`.`cover_url` AS `resource_cover`,
                           `vp`.`name` AS `vip_plan_name`
                    FROM `orders` `o`
                    LEFT JOIN `resources` `r` ON `r`.`id` = `o`.`resource_id`
                    LEFT JOIN `vip_plans` `vp` ON `vp`.`id` = `o`.`vip_plan_id`
                    WHERE {$where}
                    ORDER BY `o`.`created_at` DESC
                    LIMIT :limit OFFSET :offset";
            $params[':limit'] = $pageSize;
            $params[':offset'] = $offset;
            $list = $this->db->fetchAll($sql, $params);

            Response::success([
                'list' => $list,
                'total' => $total,
                'page' => $page,
                'page_size' => $pageSize,
            ]);

        } catch (\Exception $e) {
            Response::error('获取订单列表异常: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 获取订单详情
     */
    public function detail()
    {
        try {
            $userId = Auth::required();

            $orderId = isset($GLOBALS['REQUEST_DATA']['id']) ? intval($GLOBALS['REQUEST_DATA']['id']) : 0;
            if ($orderId <= 0) {
                Response::error('缺少订单ID', 400);
            }

            $order = $this->db->fetch(
                "SELECT `o`.*, `r`.`title` AS `resource_title`, `r`.`cover_url` AS `resource_cover`,
                        `vp`.`name` AS `vip_plan_name`
                 FROM `orders` `o`
                 LEFT JOIN `resources` `r` ON `r`.`id` = `o`.`resource_id`
                 LEFT JOIN `vip_plans` `vp` ON `vp`.`id` = `o`.`vip_plan_id`
                 WHERE `o`.`id` = :id AND `o`.`user_id` = :uid",
                [':id' => $orderId, ':uid' => $userId]
            );

            if (empty($order)) {
                Response::error('订单不存在', 404);
            }

            Response::success($order);

        } catch (\Exception $e) {
            Response::error('获取订单详情异常: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 创建订单（委托给 PaymentController::create）
     */
    public function create()
    {
        require_once __DIR__ . '/PaymentController.php';
        $payment = new PaymentController();
        $payment->create();
    }

    /**
     * 重新支付待处理订单（查找已有订单并生成支付参数）
     */
    public function pay()
    {
        try {
            $userId = Auth::required();

            $orderId = isset($GLOBALS['REQUEST_DATA']['id']) ? intval($GLOBALS['REQUEST_DATA']['id']) : 0;
            if ($orderId <= 0) {
                $orderId = isset($GLOBALS['REQUEST_DATA']['order_id']) ? intval($GLOBALS['REQUEST_DATA']['order_id']) : 0;
            }
            if ($orderId <= 0) {
                Response::error('缺少订单ID', 400);
            }

            // 查找已有订单
            $order = $this->db->fetch(
                "SELECT * FROM `orders` WHERE `id` = :id AND `user_id` = :uid",
                [':id' => $orderId, ':uid' => $userId]
            );

            if (empty($order)) {
                Response::error('订单不存在', 404);
            }

            if ($order['status'] !== 'pending') {
                Response::error('该订单不是待支付状态', 400);
            }

            // 将订单信息写入POST供PaymentController使用
            $_POST['order_type'] = $order['order_type'];
            if ($order['order_type'] === 'resource' && $order['resource_id']) {
                $_POST['resource_id'] = $order['resource_id'];
            } elseif ($order['order_type'] === 'vip' && $order['vip_plan_id']) {
                $_POST['vip_plan_id'] = $order['vip_plan_id'];
            }

            // 先取消旧的pending订单，让PaymentController创建新的
            $this->db->update('orders', ['status' => 'cancelled', 'updated_at' => date('Y-m-d H:i:s')], '`id` = :id', [':id' => $orderId]);

            // 委托给PaymentController创建新订单
            require_once __DIR__ . '/PaymentController.php';
            $payment = new PaymentController();
            $payment->create();

        } catch (\Exception $e) {
            Response::error('获取支付参数异常: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 取消待处理订单
     */
    public function cancel()
    {
        try {
            $userId = Auth::required();

            $orderId = isset($GLOBALS['REQUEST_DATA']['id']) ? intval($GLOBALS['REQUEST_DATA']['id']) : 0;
            if ($orderId <= 0) {
                Response::error('缺少订单ID', 400);
            }

            $order = $this->db->fetch(
                "SELECT `id`, `status` FROM `orders` WHERE `id` = :id AND `user_id` = :uid",
                [':id' => $orderId, ':uid' => $userId]
            );

            if (empty($order)) {
                Response::error('订单不存在', 404);
            }

            if ($order['status'] !== 'pending') {
                Response::error('只能取消待支付的订单', 400);
            }

            $this->db->update('orders', ['status' => 'cancelled', 'updated_at' => date('Y-m-d H:i:s')], '`id` = :id', [':id' => $orderId]);

            Response::success([], '订单已取消');

        } catch (\Exception $e) {
            Response::error('取消订单异常: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 查询订单状态
     */
    public function status()
    {
        try {
            $userId = Auth::required();

            $orderId = isset($GLOBALS['REQUEST_DATA']['id']) ? intval($GLOBALS['REQUEST_DATA']['id']) : 0;
            $orderNo = isset($GLOBALS['REQUEST_DATA']['order_no']) ? trim($GLOBALS['REQUEST_DATA']['order_no']) : '';

            if ($orderId <= 0 && empty($orderNo)) {
                Response::error('缺少订单ID或订单号', 400);
            }

            if ($orderId > 0) {
                $order = $this->db->fetch(
                    "SELECT `id`, `order_no`, `status`, `paid_at`, `created_at` FROM `orders` WHERE `id` = :id AND `user_id` = :uid",
                    [':id' => $orderId, ':uid' => $userId]
                );
            } else {
                $order = $this->db->fetch(
                    "SELECT `id`, `order_no`, `status`, `paid_at`, `created_at` FROM `orders` WHERE `order_no` = :ono AND `user_id` = :uid",
                    [':ono' => $orderNo, ':uid' => $userId]
                );
            }

            if (empty($order)) {
                Response::error('订单不存在', 404);
            }

            Response::success($order);

        } catch (\Exception $e) {
            Response::error('查询订单状态异常: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 支付回调（委托给 PaymentController::notify）
     */
    public function callback()
    {
        require_once __DIR__ . '/PaymentController.php';
        $payment = new PaymentController();
        $payment->notify();
    }

    /**
     * 获取所有订单列表（管理员）
     */
    public function all()
    {
        try {
            $page = max(1, intval($GLOBALS['REQUEST_DATA']['page'] ?? 1));
            $pageSize = min(50, max(1, intval($GLOBALS['REQUEST_DATA']['page_size'] ?? 20)));
            $offset = ($page - 1) * $pageSize;

            $status = isset($GLOBALS['REQUEST_DATA']['status']) ? trim($GLOBALS['REQUEST_DATA']['status']) : '';
            $orderType = isset($GLOBALS['REQUEST_DATA']['order_type']) ? trim($GLOBALS['REQUEST_DATA']['order_type']) : '';

            $where = '1=1';
            $params = [];
            if (!empty($status) && in_array($status, ['pending', 'paid', 'refunded', 'cancelled'])) {
                $where .= ' AND o.status = :status';
                $params[':status'] = $status;
            }
            if (!empty($orderType) && in_array($orderType, ['resource', 'vip'])) {
                $where .= ' AND o.order_type = :otype';
                $params[':otype'] = $orderType;
            }

            $countRow = $this->db->fetch(
                "SELECT COUNT(*) AS cnt FROM orders o WHERE {$where}",
                $params
            );
            $total = intval($countRow['cnt']);

            $list = $this->db->fetchAll(
                "SELECT o.*, u.nickname AS user_name, r.title AS resource_title, vp.name AS vip_plan_name
                 FROM orders o
                 LEFT JOIN users u ON o.user_id = u.id
                 LEFT JOIN resources r ON o.resource_id = r.id
                 LEFT JOIN vip_plans vp ON o.vip_plan_id = vp.id
                 WHERE {$where}
                 ORDER BY o.created_at DESC
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
            Response::error('获取订单列表异常: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 更新订单状态（管理员）
     */
    public function updateStatus()
    {
        try {
            $data = $GLOBALS['REQUEST_DATA'] ?? [];
            $id = intval($data['id'] ?? 0);
            $status = trim($data['status'] ?? '');

            if ($id <= 0) {
                Response::error('缺少订单ID', 400);
            }
            if (!in_array($status, ['pending', 'paid', 'refunded', 'cancelled'])) {
                Response::error('无效的订单状态', 400);
            }

            $updateData = [
                'status' => $status,
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            if ($status === 'paid') {
                $updateData['paid_at'] = date('Y-m-d H:i:s');
            }
            if ($status === 'refunded') {
                $updateData['refund_at'] = date('Y-m-d H:i:s');
            }

            $this->db->update('orders', $updateData, 'id = :id', [':id' => $id]);

            Response::success([], '状态更新成功');

        } catch (\Exception $e) {
            Response::error('更新订单状态异常: ' . $e->getMessage(), 500);
        }
    }
}
