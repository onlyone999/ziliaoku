<?php
/**
 * 活动报名控制器
 * 提供活动列表、详情、报名、取消报名 API
 */

require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';

class ActivityController
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * 获取活动列表
     * GET /api/activity/list
     * 参数：page, page_size, type(online/offline/both)
     * 只返回 status='published' 且 end_time > NOW() 的活动
     */
    public function list()
    {
        try {
            $data     = $GLOBALS['REQUEST_DATA'] ?? [];
            $page     = max(1, (int)($data['page'] ?? 1));
            $pageSize = max(1, min(50, (int)($data['page_size'] ?? 10)));
            $offset   = ($page - 1) * $pageSize;
            $type     = $data['type'] ?? '';

            $where  = "status = 'published' AND end_time > NOW()";
            $params = [];

            if ($type !== '' && in_array($type, ['online', 'offline', 'both'])) {
                $where .= ' AND activity_type = :type';
                $params[':type'] = $type;
            }

            $total = $this->db->count('activities', $where, $params);

            $sql = "SELECT id, title, description, cover_url, activity_type, start_time, end_time,
                           signup_deadline, location, max_participants, current_count, status,
                           resource_id, created_at
                    FROM activities
                    WHERE {$where}
                    ORDER BY start_time ASC
                    LIMIT {$pageSize} OFFSET {$offset}";
            $list = $this->db->fetchAll($sql, $params);

            // 如果用户已登录，标记是否已报名
            $userId = Auth::optional();
            if ($userId > 0 && !empty($list)) {
                $activityIds = array_column($list, 'id');
                $placeholders = implode(',', array_fill(0, count($activityIds), '?'));
                $signedRows = $this->db->fetchAll(
                    "SELECT activity_id FROM activity_signups WHERE user_id = ? AND activity_id IN ({$placeholders}) AND status != 'cancelled'",
                    array_merge([$userId], $activityIds)
                );
                $signedMap = [];
                foreach ($signedRows as $row) {
                    $signedMap[(int)$row['activity_id']] = true;
                }
                foreach ($list as &$item) {
                    $item['is_signed_up'] = isset($signedMap[(int)$item['id']]);
                }
                unset($item);
            } else {
                foreach ($list as &$item) {
                    $item['is_signed_up'] = false;
                }
                unset($item);
            }

            // 截断 description 用于列表展示
            foreach ($list as &$item) {
                if (strlen($item['description'] ?? '') > 200) {
                    $item['description'] = mb_substr($item['description'], 0, 200) . '...';
                }
            }
            unset($item);

            Response::paginate($list, $total, $page, $pageSize);

        } catch (\Exception $e) {
            Response::error('获取活动列表异常: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 获取活动详情
     * GET /api/activity/detail
     * 参数：id
     */
    public function detail()
    {
        try {
            $data = $GLOBALS['REQUEST_DATA'] ?? [];
            $id   = (int)($data['id'] ?? 0);

            if ($id <= 0) {
                Response::error('活动ID无效');
                return;
            }

            $activity = $this->db->fetch(
                "SELECT * FROM activities WHERE id = :id",
                [':id' => $id]
            );

            if (!$activity) {
                Response::error('活动不存在', 404);
                return;
            }

            // 获取当前用户报名状态
            $userId = Auth::optional();
            $signupStatus = null;
            if ($userId > 0) {
                $signup = $this->db->fetch(
                    "SELECT id, status, name, phone, created_at FROM activity_signups WHERE activity_id = :aid AND user_id = :uid",
                    [':aid' => $id, ':uid' => $userId]
                );
                if ($signup) {
                    $signupStatus = $signup;
                }
            }

            $activity['signup_status'] = $signupStatus;
            $activity['is_signed_up']  = $signupStatus !== null && $signupStatus['status'] !== 'cancelled';

            Response::success($activity, '获取成功');

        } catch (\Exception $e) {
            Response::error('获取活动详情异常: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 报名活动
     * POST /api/activity/signup
     * 参数：activity_id, name, phone
     */
    public function signup()
    {
        try {
            $userId = Auth::required();

            $data        = $GLOBALS['REQUEST_DATA'] ?? [];
            $activityId  = (int)($data['activity_id'] ?? 0);
            $name        = trim($data['name'] ?? '');
            $phone       = trim($data['phone'] ?? '');

            if ($activityId <= 0) {
                Response::error('活动ID无效');
                return;
            }
            if ($name === '') {
                Response::error('请输入姓名');
                return;
            }
            if (mb_strlen($name) > 100) {
                Response::error('姓名不能超过100个字符');
                return;
            }
            if ($phone !== '' && !preg_match('/^1[3-9]\d{9}$/', $phone)) {
                Response::error('手机号格式不正确');
                return;
            }

            // 查询活动
            $activity = $this->db->fetch(
                "SELECT * FROM activities WHERE id = :id",
                [':id' => $activityId]
            );

            if (!$activity) {
                Response::error('活动不存在');
                return;
            }
            if ($activity['status'] !== 'published') {
                Response::error('活动尚未发布');
                return;
            }

            // 检查报名截止时间
            if (!empty($activity['signup_deadline']) && $activity['signup_deadline'] !== '0000-00-00 00:00:00') {
                if (strtotime($activity['signup_deadline']) < time()) {
                    Response::error('报名已截止');
                    return;
                }
            }

            // 检查是否满员
            if ($activity['max_participants'] > 0 && $activity['current_count'] >= $activity['max_participants']) {
                Response::error('名额已满');
                return;
            }

            // 检查是否已报名（包括已取消的记录）
            $existing = $this->db->fetch(
                "SELECT id, status FROM activity_signups WHERE activity_id = :aid AND user_id = :uid",
                [':aid' => $activityId, ':uid' => $userId]
            );

            if ($existing) {
                if ($existing['status'] === 'cancelled') {
                    // 重新报名
                    $this->db->update('activity_signups', [
                        'status'     => 'pending',
                        'name'       => $name,
                        'phone'      => $phone,
                        'created_at' => date('Y-m-d H:i:s'),
                    ], 'id = :id', [':id' => $existing['id']]);
                    $this->db->query("UPDATE activities SET current_count = current_count + 1 WHERE id = :id", [':id' => $activityId]);
                    Response::success(['signup_id' => $existing['id']], '报名成功');
                    return;
                }
                Response::error('您已报名此活动');
                return;
            }

            // 新增报名
            $signupId = $this->db->insert('activity_signups', [
                'activity_id' => $activityId,
                'user_id'     => $userId,
                'name'        => $name,
                'phone'       => $phone,
                'status'      => 'pending',
                'created_at'  => date('Y-m-d H:i:s'),
            ]);

            // 更新报名人数
            $this->db->query("UPDATE activities SET current_count = current_count + 1 WHERE id = :id", [':id' => $activityId]);

            Response::success(['signup_id' => $signupId], '报名成功');

        } catch (\Exception $e) {
            Response::error('报名异常: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 取消报名
     * POST /api/activity/cancel
     * 参数：activity_id
     */
    public function cancel()
    {
        try {
            $userId = Auth::required();

            $data       = $GLOBALS['REQUEST_DATA'] ?? [];
            $activityId = (int)($data['activity_id'] ?? 0);

            if ($activityId <= 0) {
                Response::error('活动ID无效');
                return;
            }

            $signup = $this->db->fetch(
                "SELECT id, status FROM activity_signups WHERE activity_id = :aid AND user_id = :uid",
                [':aid' => $activityId, ':uid' => $userId]
            );

            if (!$signup || $signup['status'] === 'cancelled') {
                Response::error('您尚未报名此活动');
                return;
            }

            $this->db->update('activity_signups', [
                'status' => 'cancelled',
            ], 'id = :id', [':id' => $signup['id']]);

            $this->db->query("UPDATE activities SET current_count = GREATEST(current_count - 1, 0) WHERE id = :id", [':id' => $activityId]);

            Response::success(null, '取消报名成功');

        } catch (\Exception $e) {
            Response::error('取消报名异常: ' . $e->getMessage(), 500);
        }
    }
}
