<?php
/**
 * 反馈控制器
 * 处理用户反馈提交和反馈历史查询
 */

require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';

class FeedbackController
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * 提交反馈
     * 参数: type, content, contact（可选）
     */
    public function submit()
    {
        try {
            $userId = Auth::required();

            $type = isset($GLOBALS['REQUEST_DATA']['type']) ? trim($GLOBALS['REQUEST_DATA']['type']) : 'other';
            $content = isset($GLOBALS['REQUEST_DATA']['content']) ? trim($GLOBALS['REQUEST_DATA']['content']) : '';
            $contact = isset($GLOBALS['REQUEST_DATA']['contact']) ? trim($GLOBALS['REQUEST_DATA']['contact']) : '';

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
                "INSERT INTO `feedback` (`user_id`, `type`, `content`, `contact`, `created_at`) VALUES (:uid, :type, :content, :contact, :now)"
            );
            $stmt->execute([
                ':uid'     => $userId,
                ':type'    => $type,
                ':content' => $content,
                ':contact' => $contact,
                ':now'     => date('Y-m-d H:i:s'),
            ]);

            Response::success(['id' => $this->db->lastInsertId()], '反馈提交成功，感谢您的建议');

        } catch (\Exception $e) {
            Response::error('提交反馈异常: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 获取用户反馈历史（分页）
     */
    public function list()
    {
        try {
            $userId = Auth::required();

            $page = max(1, intval($GLOBALS['REQUEST_DATA']['page'] ?? 1));
            $pageSize = min(50, max(1, intval($GLOBALS['REQUEST_DATA']['page_size'] ?? 10)));
            $offset = ($page - 1) * $pageSize;

            $countRow = $this->db->fetch(
                'SELECT COUNT(*) AS cnt FROM feedback WHERE user_id = :uid',
                [':uid' => $userId]
            );
            $total = intval($countRow['cnt']);

            $list = $this->db->fetchAll(
                'SELECT id, type, content, contact, reply, status, created_at
                 FROM feedback
                 WHERE user_id = :uid
                 ORDER BY created_at DESC
                 LIMIT :limit OFFSET :offset',
                [':uid' => $userId, ':limit' => $pageSize, ':offset' => $offset]
            );

            Response::success([
                'list' => $list,
                'total' => $total,
                'page' => $page,
                'page_size' => $pageSize,
            ]);

        } catch (\Exception $e) {
            Response::error('获取反馈列表异常: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 回复反馈（管理员）
     */
    public function reply()
    {
        try {
            $data = $GLOBALS['REQUEST_DATA'] ?? [];
            $id = intval($data['id'] ?? 0);
            $reply = trim($data['reply'] ?? '');

            if ($id <= 0) {
                Response::error('缺少反馈ID', 400);
            }
            if (empty($reply)) {
                Response::error('回复内容不能为空', 400);
            }

            $this->db->update('feedback', [
                'reply' => $reply,
                'status' => 'replied',
            ], 'id = :id', [':id' => $id]);

            Response::success([], '回复成功');

        } catch (\Exception $e) {
            Response::error('回复反馈异常: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 获取所有反馈列表（管理员）
     */
    public function all()
    {
        try {
            $page = max(1, intval($GLOBALS['REQUEST_DATA']['page'] ?? 1));
            $pageSize = min(50, max(1, intval($GLOBALS['REQUEST_DATA']['page_size'] ?? 20)));
            $offset = ($page - 1) * $pageSize;

            $status = isset($GLOBALS['REQUEST_DATA']['status']) ? trim($GLOBALS['REQUEST_DATA']['status']) : '';
            $where = '1=1';
            $params = [];
            if (!empty($status) && in_array($status, ['pending', 'replied'])) {
                $where = 'f.status = :status';
                $params[':status'] = $status;
            }

            $countRow = $this->db->fetch(
                "SELECT COUNT(*) AS cnt FROM feedback f WHERE {$where}",
                $params
            );
            $total = intval($countRow['cnt']);

            $list = $this->db->fetchAll(
                "SELECT f.*, u.nickname, u.avatar_url
                 FROM feedback f
                 LEFT JOIN users u ON f.user_id = u.id
                 WHERE {$where}
                 ORDER BY f.created_at DESC
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
            Response::error('获取反馈列表异常: ' . $e->getMessage(), 500);
        }
    }
}
