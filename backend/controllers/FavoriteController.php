<?php
/**
 * 收藏控制器
 * 处理资源收藏检查和收藏切换
 */

require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';

class FavoriteController
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * 检查用户是否已收藏指定资源
     */
    public function check()
    {
        try {
            $userId = Auth::required();

            $resourceId = isset($_GET['resource_id']) ? intval($_GET['resource_id']) : 0;
            if ($resourceId <= 0) {
                Response::error('缺少资源ID', 400);
            }

            $stmt = $this->db->prepare("SELECT COUNT(*) FROM `favorites` WHERE `user_id` = ? AND `resource_id` = ?");
            $stmt->execute([$userId, $resourceId]);
            $isFavorited = $stmt->fetchColumn() > 0;

            Response::success([
                'resource_id' => $resourceId,
                'is_favorited' => $isFavorited,
            ], '获取成功');

        } catch (\Exception $e) {
            Response::error('检查收藏状态异常: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 切换收藏状态（已收藏则取消，未收藏则添加）
     * 同步更新 resources 表的 like_count
     */
    public function toggle()
    {
        try {
            $userId = Auth::required();

            $resourceId = isset($_POST['resource_id']) ? intval($_POST['resource_id']) : 0;
            if ($resourceId <= 0) {
                Response::error('缺少资源ID', 400);
            }

            // 验证资源存在
            $resStmt = $this->db->prepare("SELECT `id`, `like_count` FROM `resources` WHERE `id` = ? AND `status` = 'approved'");
            $resStmt->execute([$resourceId]);
            $resource = $resStmt->fetch(PDO::FETCH_ASSOC);
            if (!$resource) {
                Response::error('资源不存在', 404);
            }

            // 检查是否已收藏
            $checkStmt = $this->db->prepare("SELECT `id` FROM `favorites` WHERE `user_id` = ? AND `resource_id` = ?");
            $checkStmt->execute([$userId, $resourceId]);
            $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                // 取消收藏
                $this->db->prepare("DELETE FROM `favorites` WHERE `id` = ?")->execute([$existing['id']]);
                $this->db->prepare("UPDATE `resources` SET `like_count` = GREATEST(`like_count` - 1, 0) WHERE `id` = ?")->execute([$resourceId]);
                Response::success(['is_favorited' => false], '已取消收藏');
            } else {
                // 添加收藏
                $this->db->prepare("INSERT INTO `favorites` (`user_id`, `resource_id`, `created_at`) VALUES (?, ?, ?)")
                    ->execute([$userId, $resourceId, date('Y-m-d H:i:s')]);
                $this->db->prepare("UPDATE `resources` SET `like_count` = `like_count` + 1 WHERE `id` = ?")->execute([$resourceId]);
                Response::success(['is_favorited' => true], '已收藏');
            }

        } catch (\Exception $e) {
            Response::error('收藏操作异常: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 获取用户收藏列表（分页）
     */
    public function list()
    {
        $userId = Auth::required();
        $page = max(1, intval($GLOBALS['REQUEST_DATA']['page'] ?? 1));
        $pageSize = min(50, max(1, intval($GLOBALS['REQUEST_DATA']['page_size'] ?? 10)));
        $offset = ($page - 1) * $pageSize;

        $countRow = $this->db->fetch('SELECT COUNT(*) AS cnt FROM favorites WHERE user_id = :uid', [':uid' => $userId]);
        $total = intval($countRow['cnt']);

        $list = $this->db->fetchAll(
            'SELECT f.id AS favorite_id, f.created_at AS favorite_time, r.*, c.name AS category_name
             FROM favorites f
             LEFT JOIN resources r ON f.resource_id = r.id
             LEFT JOIN categories c ON r.category_id = c.id
             WHERE f.user_id = :uid
             ORDER BY f.created_at DESC
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
     * 获取所有收藏记录（管理员）
     */
    public function all()
    {
        try {
            $page = max(1, intval($GLOBALS['REQUEST_DATA']['page'] ?? 1));
            $pageSize = min(50, max(1, intval($GLOBALS['REQUEST_DATA']['page_size'] ?? 20)));
            $offset = ($page - 1) * $pageSize;

            $countRow = $this->db->fetch('SELECT COUNT(*) AS cnt FROM favorites');
            $total = intval($countRow['cnt']);

            $list = $this->db->fetchAll(
                'SELECT f.*, u.nickname, r.title AS resource_title
                 FROM favorites f
                 LEFT JOIN users u ON f.user_id = u.id
                 LEFT JOIN resources r ON f.resource_id = r.id
                 ORDER BY f.created_at DESC
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
            Response::error('获取收藏记录异常: ' . $e->getMessage(), 500);
        }
    }
}
