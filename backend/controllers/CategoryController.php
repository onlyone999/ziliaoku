<?php
/**
 * 分类控制器
 * 处理分类列表和分类详情（含分页资源）
 */

require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/UrlHelper.php';

class CategoryController
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * 获取所有分类列表
     * 包含每个分类下的资源数量，按 sort_order 排序
     */
    public function getList()
    {
        try {
            $sql = "SELECT `c`.`id`, `c`.`parent_id`, `c`.`name`, `c`.`icon`, `c`.`sort_order`, `c`.`is_hot`,
                           (SELECT COUNT(*) FROM `resources` `r` WHERE `r`.`category_id` = `c`.`id` AND `r`.`status` = 'approved') AS `resource_count`
                    FROM `categories` `c`
                    WHERE `c`.`status` = 1
                    ORDER BY `c`.`sort_order` DESC, `c`.`id` ASC";
            $stmt = $this->db->query($sql);
            $list = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // 构建树形结构（parent_id=0 为顶级）
            $tree = $this->buildTree($list);

            Response::success($tree, '获取成功');

        } catch (\Exception $e) {
            Response::error('获取分类列表异常: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 获取分类详情
     * 包含分类信息和该分类下的分页资源列表
     */
    public function getDetail()
    {
        try {
            $categoryId = isset($GLOBALS['REQUEST_DATA']['id']) ? intval($GLOBALS['REQUEST_DATA']['id']) : 0;
            if ($categoryId <= 0) {
                $categoryId = isset($_GET['id']) ? intval($_GET['id']) : 0;
            }
            if ($categoryId <= 0) {
                Response::error('缺少分类ID', 400);
            }

            // 获取分类信息
            $catStmt = $this->db->prepare("SELECT * FROM `categories` WHERE `id` = ? AND `status` = 1");
            $catStmt->execute([$categoryId]);
            $category = $catStmt->fetch(PDO::FETCH_ASSOC);

            if (!$category) {
                Response::error('分类不存在', 404);
            }

            // 统计资源数量
            $countStmt = $this->db->prepare("SELECT COUNT(*) FROM `resources` WHERE `category_id` = ? AND `status` = 'approved'");
            $countStmt->execute([$categoryId]);
            $category['resource_count'] = intval($countStmt->fetchColumn());

            // 分页查询该分类下的资源
            $page = max(1, intval($_GET['page'] ?? 1));
            $pageSize = min(50, max(1, intval($_GET['page_size'] ?? 10)));
            $offset = ($page - 1) * $pageSize;

            $sort = isset($_GET['sort']) ? trim($_GET['sort']) : 'newest';
            $orderBy = '`r`.`created_at` DESC';
            switch ($sort) {
                case 'downloads':
                    $orderBy = '`r`.`download_count` DESC';
                    break;
                case 'price_asc':
                    $orderBy = '`r`.`price` ASC';
                    break;
                case 'price_desc':
                    $orderBy = '`r`.`price` DESC';
                    break;
                case 'newest':
                default:
                    $orderBy = '`r`.`created_at` DESC';
                    break;
            }

            $sql = "SELECT `r`.*, `c`.`name` AS `category_name`
                    FROM `resources` `r`
                    LEFT JOIN `categories` `c` ON `c`.`id` = `r`.`category_id`
                    WHERE `r`.`category_id` = ? AND `r`.`status` = 'approved'
                    ORDER BY {$orderBy}
                    LIMIT ? OFFSET ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$categoryId, $pageSize, $offset]);
            $resources = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $resources = UrlHelper::fixResourceListUrls($resources);

            Response::success([
                'category' => $category,
                'resources' => $resources,
                'total' => $category['resource_count'],
                'page' => $page,
                'page_size' => $pageSize,
                'total_pages' => ceil($category['resource_count'] / $pageSize),
            ], '获取成功');

        } catch (\Exception $e) {
            Response::error('获取分类详情异常: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 别名：获取所有分类列表
     */
    public function list()
    {
        $this->getList();
    }

    /**
     * 别名：获取分类详情（含该分类下的分页资源）
     */
    public function detail()
    {
        $this->getDetail();
    }

    /**
     * 获取指定分类的子分类
     * 接受 id 或 parent_id 参数
     */
    public function sub()
    {
        try {
            $parentId = isset($GLOBALS['REQUEST_DATA']['id']) ? intval($GLOBALS['REQUEST_DATA']['id']) : 0;
            if ($parentId <= 0) {
                $parentId = isset($GLOBALS['REQUEST_DATA']['parent_id']) ? intval($GLOBALS['REQUEST_DATA']['parent_id']) : 0;
            }
            if ($parentId <= 0) {
                $parentId = isset($_GET['id']) ? intval($_GET['id']) : 0;
            }
            if ($parentId <= 0) {
                $parentId = isset($_GET['parent_id']) ? intval($_GET['parent_id']) : 0;
            }
            if ($parentId <= 0) {
                Response::error('缺少父分类ID', 400);
            }

            $sql = "SELECT `id`, `parent_id`, `name`, `icon`, `sort_order`, `is_hot`,
                           (SELECT COUNT(*) FROM `resources` `r` WHERE `r`.`category_id` = `c`.`id` AND `r`.`status` = 'approved') AS `resource_count`
                    FROM `categories` `c`
                    WHERE `c`.`parent_id` = ? AND `c`.`status` = 1
                    ORDER BY `c`.`sort_order` DESC, `c`.`id` ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$parentId]);
            $list = $stmt->fetchAll(PDO::FETCH_ASSOC);

            Response::success($list, '获取成功');

        } catch (\Exception $e) {
            Response::error('获取子分类异常: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 创建分类（管理员）
     */
    public function create()
    {
        try {
            $data = $GLOBALS['REQUEST_DATA'] ?? [];
            $name = trim($data['name'] ?? '');
            $parentId = intval($data['parent_id'] ?? 0);
            $icon = trim($data['icon'] ?? '');
            $sortOrder = intval($data['sort_order'] ?? 0);
            $isHot = intval($data['is_hot'] ?? 0);

            if (empty($name)) {
                Response::error('分类名称不能为空', 400);
            }

            $id = $this->db->insert('categories', [
                'parent_id' => $parentId,
                'name' => $name,
                'icon' => $icon,
                'sort_order' => $sortOrder,
                'is_hot' => $isHot,
                'status' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            Response::success(['id' => $id], '创建成功');

        } catch (\Exception $e) {
            Response::error('创建分类异常: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 更新分类（管理员）
     */
    public function update()
    {
        try {
            $data = $GLOBALS['REQUEST_DATA'] ?? [];
            $id = intval($data['id'] ?? 0);
            if ($id <= 0) {
                Response::error('缺少分类ID', 400);
            }

            $updateData = [];
            if (isset($data['name'])) $updateData['name'] = trim($data['name']);
            if (isset($data['parent_id'])) $updateData['parent_id'] = intval($data['parent_id']);
            if (isset($data['icon'])) $updateData['icon'] = trim($data['icon']);
            if (isset($data['sort_order'])) $updateData['sort_order'] = intval($data['sort_order']);
            if (isset($data['is_hot'])) $updateData['is_hot'] = intval($data['is_hot']);
            if (isset($data['status'])) $updateData['status'] = intval($data['status']);
            $updateData['updated_at'] = date('Y-m-d H:i:s');

            $this->db->update('categories', $updateData, 'id = :id', [':id' => $id]);

            Response::success([], '更新成功');

        } catch (\Exception $e) {
            Response::error('更新分类异常: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 删除分类（管理员）
     */
    public function delete()
    {
        try {
            $id = intval($GLOBALS['REQUEST_DATA']['id'] ?? 0);
            if ($id <= 0) {
                Response::error('缺少分类ID', 400);
            }

            // Check if category has resources
            $count = $this->db->count('resources', 'category_id = :cid', [':cid' => $id]);
            if ($count > 0) {
                Response::error('该分类下还有资源，无法删除', 400);
            }

            // Check if category has children
            $childCount = $this->db->count('categories', 'parent_id = :pid', [':pid' => $id]);
            if ($childCount > 0) {
                Response::error('该分类下还有子分类，无法删除', 400);
            }

            $this->db->delete('categories', 'id = :id', [':id' => $id]);

            Response::success([], '删除成功');

        } catch (\Exception $e) {
            Response::error('删除分类异常: ' . $e->getMessage(), 500);
        }
    }

    // ============ 私有辅助方法 ============

    /**
     * 构建树形分类结构
     */
    private function buildTree($list, $parentId = 0)
    {
        $tree = [];
        foreach ($list as $item) {
            if (intval($item['parent_id']) === $parentId) {
                $children = $this->buildTree($list, intval($item['id']));
                if ($children) {
                    $item['children'] = $children;
                }
                $tree[] = $item;
            }
        }
        return $tree;
    }
}
