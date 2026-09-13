<?php
/**
 * 公告控制器
 * 提供已发布公告列表 API
 */

require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../core/Database.php';

class AnnouncementController
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * 获取已发布公告列表
     * GET /api/announcement/list
     * 支持分页参数：page, page_size
     */
    public function list()
    {
        try {
            $data     = $GLOBALS['REQUEST_DATA'] ?? [];
            $page     = max(1, (int)($data['page'] ?? 1));
            $pageSize = max(1, min(50, (int)($data['page_size'] ?? 20)));
            $offset   = ($page - 1) * $pageSize;

            // 自动建表（防御性）
            $this->db->query("CREATE TABLE IF NOT EXISTS `announcements` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `title` VARCHAR(255) NOT NULL DEFAULT '',
                `content` TEXT,
                `images` JSON DEFAULT NULL,
                `attachments` JSON DEFAULT NULL,
                `type` ENUM('info','warning','success') NOT NULL DEFAULT 'info',
                `is_top` TINYINT(1) NOT NULL DEFAULT 0,
                `status` ENUM('draft','published','closed') NOT NULL DEFAULT 'draft',
                `admin_id` INT UNSIGNED NOT NULL DEFAULT 0,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            $where  = "status = 'published' AND is_visible = 1";
            $total  = $this->db->count('announcements', $where);

            $sql = "SELECT `id`, `title`, `content`, `images`, `attachments`, `type`, `is_top`, `created_at`, `updated_at`
                    FROM `announcements`
                    WHERE {$where}
                    ORDER BY `is_top` DESC, `id` DESC
                    LIMIT {$pageSize} OFFSET {$offset}";
            $list = $this->db->fetchAll($sql);

            // 解码JSON字段
            foreach ($list as &$row) {
                $row['images']      = !empty($row['images']) ? json_decode($row['images'], true) : [];
                $row['attachments'] = !empty($row['attachments']) ? json_decode($row['attachments'], true) : [];
            }

            Response::paginate($list, $total, $page, $pageSize);

        } catch (\Exception $e) {
            Response::error('获取公告列表异常: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 获取单条公告详情
     * GET /api/announcement/detail?id=1
     */
    public function detail()
    {
        try {
            $data = $GLOBALS['REQUEST_DATA'] ?? [];
            $id   = (int)($data['id'] ?? 0);

            if ($id <= 0) {
                Response::error('参数错误：缺少公告ID', 400);
            }

            $row = $this->db->fetch(
                "SELECT `id`, `title`, `content`, `images`, `attachments`, `type`, `is_top`, `created_at`, `updated_at`
                 FROM `announcements`
                 WHERE `id` = :id AND `status` = 'published' AND `is_visible` = 1
                 LIMIT 1",
                [':id' => $id]
            );

            if (!$row) {
                Response::notFound('公告不存在或已关闭');
            }

            // 解码JSON字段
            $row['images']      = !empty($row['images']) ? json_decode($row['images'], true) : [];
            $row['attachments'] = !empty($row['attachments']) ? json_decode($row['attachments'], true) : [];

            Response::success($row);

        } catch (\Exception $e) {
            Response::error('获取公告详情异常: ' . $e->getMessage(), 500);
        }
    }
}
