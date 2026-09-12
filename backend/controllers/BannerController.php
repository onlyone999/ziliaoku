<?php
/**
 * 轮播图控制器
 * 处理首页轮播图数据
 */

require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/UrlHelper.php';

class BannerController
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * 获取所有有效轮播图
     * 按 sort_order 降序排列，只返回状态为显示的
     */
    public function getList()
    {
        try {
            $sql = "SELECT `id`, `title`, `image_url`, `link_type`, `link_value`, `sort_order`
                    FROM `banners`
                    WHERE `status` = 1
                    ORDER BY `sort_order` DESC, `id` DESC";
            $stmt = $this->db->query($sql);
            $list = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Fix image URLs to full URLs
            $list = UrlHelper::fixBannerListUrls($list);

            Response::success($list, '获取成功');

        } catch (\Exception $e) {
            Response::error('获取轮播图异常: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 别名：获取所有有效轮播图
     */
    public function list()
    {
        $this->getList();
    }

    /**
     * 创建轮播图（管理员）
     */
    public function create()
    {
        try {
            $data = $GLOBALS['REQUEST_DATA'] ?? [];
            $title = trim($data['title'] ?? '');
            $imageUrl = trim($data['image_url'] ?? '');
            $linkType = $data['link_type'] ?? 'none';
            $linkValue = trim($data['link_value'] ?? '');
            $sortOrder = intval($data['sort_order'] ?? 0);

            if (empty($title)) {
                Response::error('标题不能为空', 400);
            }
            if (empty($imageUrl)) {
                Response::error('图片URL不能为空', 400);
            }

            $id = $this->db->insert('banners', [
                'title' => $title,
                'image_url' => $imageUrl,
                'link_type' => $linkType,
                'link_value' => $linkValue,
                'sort_order' => $sortOrder,
                'status' => 1,
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            Response::success(['id' => $id], '创建成功');

        } catch (\Exception $e) {
            Response::error('创建轮播图异常: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 更新轮播图（管理员）
     */
    public function update()
    {
        try {
            $data = $GLOBALS['REQUEST_DATA'] ?? [];
            $id = intval($data['id'] ?? 0);
            if ($id <= 0) {
                Response::error('缺少轮播图ID', 400);
            }

            $updateData = [];
            if (isset($data['title'])) $updateData['title'] = trim($data['title']);
            if (isset($data['image_url'])) $updateData['image_url'] = trim($data['image_url']);
            if (isset($data['link_type'])) $updateData['link_type'] = $data['link_type'];
            if (isset($data['link_value'])) $updateData['link_value'] = trim($data['link_value']);
            if (isset($data['sort_order'])) $updateData['sort_order'] = intval($data['sort_order']);
            if (isset($data['status'])) $updateData['status'] = intval($data['status']);

            if (empty($updateData)) {
                Response::error('没有需要更新的字段', 400);
            }

            $this->db->update('banners', $updateData, 'id = :id', [':id' => $id]);

            Response::success([], '更新成功');

        } catch (\Exception $e) {
            Response::error('更新轮播图异常: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 删除轮播图（管理员）
     */
    public function delete()
    {
        try {
            $id = intval($GLOBALS['REQUEST_DATA']['id'] ?? 0);
            if ($id <= 0) {
                Response::error('缺少轮播图ID', 400);
            }

            $this->db->delete('banners', 'id = :id', [':id' => $id]);

            Response::success([], '删除成功');

        } catch (\Exception $e) {
            Response::error('删除轮播图异常: ' . $e->getMessage(), 500);
        }
    }
}
