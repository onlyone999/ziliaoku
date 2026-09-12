<?php
/**
 * 投票控制器
 * 处理资源投票的切换和状态查询
 */

require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';

class VoteController
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * 切换投票状态（POST /api/vote/toggle）
     * 已投票且类型相同 → 取消投票
     * 已投票且类型不同 → 更新投票
     * 未投票 → 新增投票
     */
    public function toggle()
    {
        try {
            $userId = Auth::required();

            $resourceId = isset($GLOBALS['REQUEST_DATA']['resource_id']) ? intval($GLOBALS['REQUEST_DATA']['resource_id']) : 0;
            $voteType = isset($GLOBALS['REQUEST_DATA']['vote_type']) ? trim($GLOBALS['REQUEST_DATA']['vote_type']) : '';

            if ($resourceId <= 0) {
                Response::error('缺少资源ID', 400);
            }
            if (!in_array($voteType, ['up', 'down'])) {
                Response::error('投票类型无效，只能是 up 或 down', 400);
            }

            // 验证资源存在
            $resource = $this->db->fetch(
                "SELECT id, up_votes, down_votes FROM resources WHERE id = :id LIMIT 1",
                [':id' => $resourceId]
            );
            if (empty($resource)) {
                Response::error('资源不存在', 404);
            }

            // 检查是否已投票
            $existing = $this->db->fetch(
                "SELECT id, vote_type FROM resource_votes WHERE resource_id = :rid AND user_id = :uid LIMIT 1",
                [':rid' => $resourceId, ':uid' => $userId]
            );

            if (!empty($existing)) {
                if ($existing['vote_type'] === $voteType) {
                    // 同类型 → 取消投票
                    $this->db->delete('resource_votes', 'id = :id', [':id' => $existing['id']]);
                    if ($voteType === 'up') {
                        $this->db->query(
                            "UPDATE resources SET up_votes = GREATEST(up_votes - 1, 0) WHERE id = :id",
                            [':id' => $resourceId]
                        );
                    } else {
                        $this->db->query(
                            "UPDATE resources SET down_votes = GREATEST(down_votes - 1, 0) WHERE id = :id",
                            [':id' => $resourceId]
                        );
                    }
                    $action = 'removed';
                } else {
                    // 不同类型 → 更新投票
                    $this->db->update('resource_votes', [
                        'vote_type' => $voteType,
                        'created_at' => date('Y-m-d H:i:s'),
                    ], 'id = :id', [':id' => $existing['id']]);

                    if ($voteType === 'up') {
                        $this->db->query(
                            "UPDATE resources SET up_votes = up_votes + 1, down_votes = GREATEST(down_votes - 1, 0) WHERE id = :id",
                            [':id' => $resourceId]
                        );
                    } else {
                        $this->db->query(
                            "UPDATE resources SET down_votes = down_votes + 1, up_votes = GREATEST(up_votes - 1, 0) WHERE id = :id",
                            [':id' => $resourceId]
                        );
                    }
                    $action = 'changed';
                }
            } else {
                // 未投票 → 新增
                $this->db->insert('resource_votes', [
                    'resource_id' => $resourceId,
                    'user_id'     => $userId,
                    'vote_type'   => $voteType,
                    'created_at'  => date('Y-m-d H:i:s'),
                ]);
                if ($voteType === 'up') {
                    $this->db->query(
                        "UPDATE resources SET up_votes = up_votes + 1 WHERE id = :id",
                        [':id' => $resourceId]
                    );
                } else {
                    $this->db->query(
                        "UPDATE resources SET down_votes = down_votes + 1 WHERE id = :id",
                        [':id' => $resourceId]
                    );
                }
                $action = 'added';
            }

            // 重新读取最新计数
            $updated = $this->db->fetch(
                "SELECT up_votes, down_votes FROM resources WHERE id = :id LIMIT 1",
                [':id' => $resourceId]
            );

            // 查询用户当前投票状态
            $currentVote = $this->db->fetch(
                "SELECT vote_type FROM resource_votes WHERE resource_id = :rid AND user_id = :uid LIMIT 1",
                [':rid' => $resourceId, ':uid' => $userId]
            );

            Response::success([
                'action'    => $action,
                'user_vote' => !empty($currentVote) ? $currentVote['vote_type'] : null,
                'up_votes'  => (int)($updated['up_votes'] ?? 0),
                'down_votes' => (int)($updated['down_votes'] ?? 0),
            ], $action === 'removed' ? '已取消投票' : ($action === 'changed' ? '已更改投票' : '投票成功'));

        } catch (\Exception $e) {
            Response::error('投票操作异常: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 资源排行（GET /api/vote/ranking）
     * type=up  按赞同数排
     * type=net 按净票数(up-down)排
     */
    public function ranking()
    {
        try {
            $page     = max(1, isset($GLOBALS['REQUEST_DATA']['page']) ? intval($GLOBALS['REQUEST_DATA']['page']) : 1);
            $pageSize = max(1, min(50, isset($GLOBALS['REQUEST_DATA']['page_size']) ? intval($GLOBALS['REQUEST_DATA']['page_size']) : 20));
            $type     = isset($GLOBALS['REQUEST_DATA']['type']) ? trim($GLOBALS['REQUEST_DATA']['type']) : 'net';
            if (!in_array($type, ['up', 'net'])) {
                $type = 'net';
            }
            $offset = ($page - 1) * $pageSize;

            $orderCol = $type === 'up' ? 'r.up_votes DESC' : '(r.up_votes - r.down_votes) DESC';
            $orderCol .= ', r.id ASC';

            $total = $this->db->fetch(
                "SELECT COUNT(*) AS cnt FROM resources r WHERE r.status = 'approved'"
            );
            $totalRows = (int)($total['cnt'] ?? 0);

            $rows = $this->db->fetchAll(
                "SELECT r.id, r.title, r.cover_url, r.up_votes, r.down_votes,
                        (r.up_votes - r.down_votes) AS net_votes,
                        c.name AS category_name
                 FROM resources r
                 LEFT JOIN categories c ON r.category_id = c.id
                 WHERE r.status = 'approved'
                 ORDER BY {$orderCol}
                 LIMIT :limit OFFSET :offset",
                [':limit' => $pageSize, ':offset' => $offset]
            );

            $list = [];
            $rankStart = $offset + 1;
            foreach ($rows as $i => $row) {
                $list[] = [
                    'id'            => (int)$row['id'],
                    'title'         => $row['title'],
                    'cover_url'     => $row['cover_url'],
                    'category_name' => $row['category_name'] ?? '',
                    'up_votes'      => (int)($row['up_votes'] ?? 0),
                    'down_votes'    => (int)($row['down_votes'] ?? 0),
                    'net_votes'     => (int)($row['net_votes'] ?? 0),
                    'rank'          => $rankStart + $i,
                ];
            }

            Response::success([
                'list'        => $list,
                'total'       => $totalRows,
                'page'        => $page,
                'page_size'   => $pageSize,
                'total_pages' => max(1, ceil($totalRows / $pageSize)),
            ], '获取成功');

        } catch (\Exception $e) {
            Response::error('获取排行异常: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 查询投票状态（GET /api/vote/status）
     * 返回用户投票类型和计数
     */
    public function status()
    {
        try {
            $userId = Auth::required();

            $resourceId = isset($GLOBALS['REQUEST_DATA']['resource_id']) ? intval($GLOBALS['REQUEST_DATA']['resource_id']) : 0;
            if ($resourceId <= 0) {
                Response::error('缺少资源ID', 400);
            }

            $resource = $this->db->fetch(
                "SELECT up_votes, down_votes FROM resources WHERE id = :id LIMIT 1",
                [':id' => $resourceId]
            );
            if (empty($resource)) {
                Response::error('资源不存在', 404);
            }

            $currentVote = $this->db->fetch(
                "SELECT vote_type FROM resource_votes WHERE resource_id = :rid AND user_id = :uid LIMIT 1",
                [':rid' => $resourceId, ':uid' => $userId]
            );

            Response::success([
                'user_vote'  => !empty($currentVote) ? $currentVote['vote_type'] : null,
                'up_votes'   => (int)($resource['up_votes'] ?? 0),
                'down_votes' => (int)($resource['down_votes'] ?? 0),
            ], '获取成功');

        } catch (\Exception $e) {
            Response::error('查询投票状态异常: ' . $e->getMessage(), 500);
        }
    }
}
