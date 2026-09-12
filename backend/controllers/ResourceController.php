<?php
/**
 * 资源控制器
 * 处理资源列表、详情、搜索、收藏、评论、下载记录等
 */

require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/UrlHelper.php';

class ResourceController
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * 获取资源分页列表
     * 支持按分类、关键词、价格类型、标签筛选，支持排序
     */
    public function getList()
    {
        try {
            $page = max(1, intval($_GET['page'] ?? 1));
            $pageSize = min(50, max(1, intval($_GET['page_size'] ?? 10)));
            $offset = ($page - 1) * $pageSize;

            $categoryId = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;
            $keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
            $priceType = isset($_GET['price_type']) ? trim($_GET['price_type']) : '';
            $sort = isset($_GET['sort']) ? trim($_GET['sort']) : 'newest';
            $tagId = isset($_GET['tag']) ? intval($_GET['tag']) : 0;

            $where = ["`r`.`status` = 'approved'"];
            $params = [];

            // 分类筛选（递归包含子分类）
            if ($categoryId > 0) {
                $allCatIds = $this->getAllChildCategoryIds($categoryId);
                $allCatIds[] = $categoryId;
                $placeholders = implode(',', array_fill(0, count($allCatIds), '?'));
                $where[] = "`r`.`category_id` IN ($placeholders)";
                $params = array_merge($params, $allCatIds);
            }

            // 关键词搜索
            if (!empty($keyword)) {
                $where[] = "(`r`.`title` LIKE ? OR `r`.`description` LIKE ?)";
                $params[] = '%' . $keyword . '%';
                $params[] = '%' . $keyword . '%';
            }

            // 价格类型筛选
            if (!empty($priceType) && in_array($priceType, ['free', 'paid', 'member_free'])) {
                $where[] = "`r`.`price_type` = ?";
                $params[] = $priceType;
            }

            // 标签筛选
            if ($tagId > 0) {
                $where[] = "EXISTS (SELECT 1 FROM `resource_tags` `rt` WHERE `rt`.`resource_id` = `r`.`id` AND `rt`.`tag_id` = ?)";
                $params[] = $tagId;
            }

            $whereClause = implode(' AND ', $where);

            // 排序方式
            $orderBy = '`r`.`is_top` DESC, `r`.`created_at` DESC';
            switch ($sort) {
                case 'downloads':
                    $orderBy = '`r`.`is_top` DESC, `r`.`download_count` DESC';
                    break;
                case 'price_asc':
                    $orderBy = '`r`.`is_top` DESC, `r`.`price` ASC';
                    break;
                case 'price_desc':
                    $orderBy = '`r`.`is_top` DESC, `r`.`price` DESC';
                    break;
                case 'newest':
                default:
                    $orderBy = '`r`.`is_top` DESC, `r`.`created_at` DESC';
                    break;
            }

            // 查询总数
            $countSql = "SELECT COUNT(*) FROM `resources` `r` WHERE {$whereClause}";
            $countStmt = $this->db->prepare($countSql);
            $countStmt->execute($params);
            $total = intval($countStmt->fetchColumn());

            // 查询列表
            $sql = "SELECT `r`.*, `c`.`name` AS `category_name`
                    FROM `resources` `r`
                    LEFT JOIN `categories` `c` ON `c`.`id` = `r`.`category_id`
                    WHERE {$whereClause}
                    ORDER BY {$orderBy}
                    LIMIT ? OFFSET ?";
            $params[] = $pageSize;
            $params[] = $offset;
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $list = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // 为每个资源附加标签
            $list = $this->attachTags($list);

            // Fix image URLs
            $list = UrlHelper::fixResourceListUrls($list);

            Response::success([
                'list' => $list,
                'total' => $total,
                'page' => $page,
                'page_size' => $pageSize,
                'total_pages' => ceil($total / $pageSize),
            ], '获取成功');

        } catch (\Exception $e) {
            Response::error('获取资源列表异常: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 获取资源详情
     * 包含分类名、标签、文件列表、用户下载状态，增加浏览量
     */
    public function getDetail()
    {
        try {
            $resourceId = isset($GLOBALS['REQUEST_DATA']['id']) ? intval($GLOBALS['REQUEST_DATA']['id']) : 0;
            if ($resourceId <= 0) {
                $resourceId = isset($_GET['id']) ? intval($_GET['id']) : 0;
            }
            if ($resourceId <= 0) {
                Response::error('缺少资源ID', 400);
            }

            // 增加浏览量
            $this->db->prepare("UPDATE `resources` SET `view_count` = `view_count` + 1 WHERE `id` = ?")
                ->execute([$resourceId]);

            // 查询资源详情
            $sql = "SELECT `r`.*, `c`.`name` AS `category_name`
                    FROM `resources` `r`
                    LEFT JOIN `categories` `c` ON `c`.`id` = `r`.`category_id`
                    WHERE `r`.`id` = ? AND `r`.`status` = 'approved'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$resourceId]);
            $resource = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$resource) {
                Response::error('资源不存在', 404);
            }

            // 获取标签
            $tagStmt = $this->db->prepare(
                "SELECT `t`.`id`, `t`.`name` FROM `tags` `t`
                 INNER JOIN `resource_tags` `rt` ON `rt`.`tag_id` = `t`.`id`
                 WHERE `rt`.`resource_id` = ?"
            );
            $tagStmt->execute([$resourceId]);
            $resource['tags'] = $tagStmt->fetchAll(PDO::FETCH_ASSOC);

            // 获取文件列表
            $fileStmt = $this->db->prepare(
                "SELECT `id`, `file_name`, `file_url`, `file_size`, `file_type`, `sort_order` FROM `resource_files` WHERE `resource_id` = ? ORDER BY `sort_order` ASC"
            );
            $fileStmt->execute([$resourceId]);
            $resource['files'] = UrlHelper::fixFileListUrls($fileStmt->fetchAll(PDO::FETCH_ASSOC));

            // Fix image URLs
            $resource = UrlHelper::fixResourceUrls($resource);

            // 获取当前用户下载/收藏状态
            $resource['user_has_downloaded'] = false;
            $resource['is_favorite'] = false;
            $userId = Auth::optional();
            if ($userId) {
                $dlStmt = $this->db->prepare("SELECT COUNT(*) FROM `downloads` WHERE `user_id` = ? AND `resource_id` = ?");
                $dlStmt->execute([$userId, $resourceId]);
                $resource['user_has_downloaded'] = $dlStmt->fetchColumn() > 0;

                $favStmt = $this->db->prepare("SELECT COUNT(*) FROM `favorites` WHERE `user_id` = ? AND `resource_id` = ?");
                $favStmt->execute([$userId, $resourceId]);
                $resource['is_favorite'] = $favStmt->fetchColumn() > 0;
            }

            // 评论功能开关
            $resource['comment_enabled'] = $this->getSetting('comment_enabled') !== '0';

            Response::success($resource, '获取成功');

        } catch (\Exception $e) {
            Response::error('获取资源详情异常: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 全文搜索资源
     * 在标题和描述中搜索，返回分页结果
     */
    public function search()
    {
        try {
            $keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
            if (empty($keyword)) {
                Response::error('请输入搜索关键词', 400);
            }

            $page = max(1, intval($_GET['page'] ?? 1));
            $pageSize = min(50, max(1, intval($_GET['page_size'] ?? 10)));
            $offset = ($page - 1) * $pageSize;

            // 全文搜索（利用 FULLTEXT 索引，降级方案用 LIKE）
            $whereClause = "`r`.`status` = 'approved' AND (`r`.`title` LIKE ? OR `r`.`description` LIKE ?)";
            $params = ['%' . $keyword . '%', '%' . $keyword . '%'];

            // 统计总数
            $countSql = "SELECT COUNT(*) FROM `resources` `r` WHERE {$whereClause}";
            $countStmt = $this->db->prepare($countSql);
            $countStmt->execute($params);
            $total = intval($countStmt->fetchColumn());

            // 查询结果，按相关度排序（标题匹配优先）
            $sql = "SELECT `r`.*, `c`.`name` AS `category_name`,
                           (CASE WHEN `r`.`title` LIKE ? THEN 2 ELSE 1 END) AS `relevance`
                    FROM `resources` `r`
                    LEFT JOIN `categories` `c` ON `c`.`id` = `r`.`category_id`
                    WHERE {$whereClause}
                    ORDER BY `relevance` DESC, `r`.`download_count` DESC
                    LIMIT ? OFFSET ?";
            $likeParam = '%' . $keyword . '%';
            $params2 = array_merge([$likeParam], $params, [$pageSize, $offset]);
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params2);
            $list = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // 去掉 relevance 字段
            foreach ($list as &$item) {
                unset($item['relevance']);
            }

            $list = UrlHelper::fixResourceListUrls($list);

            Response::success([
                'list' => $list,
                'total' => $total,
                'page' => $page,
                'page_size' => $pageSize,
                'keyword' => $keyword,
            ], '搜索完成');

        } catch (\Exception $e) {
            Response::error('搜索异常: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 获取热门资源（按下载量排序，支持分页）
     */
    public function getHot()
    {
        try {
            $page = max(1, intval($GLOBALS['REQUEST_DATA']['page'] ?? 1));
            $pageSize = min(50, max(1, intval($GLOBALS['REQUEST_DATA']['page_size'] ?? $GLOBALS['REQUEST_DATA']['limit'] ?? 20)));
            $offset = ($page - 1) * $pageSize;

            $countStmt = $this->db->prepare("SELECT COUNT(*) FROM `resources` WHERE `status` = 'approved'");
            $countStmt->execute();
            $total = intval($countStmt->fetchColumn());

            $sql = "SELECT `r`.*, `c`.`name` AS `category_name`
                    FROM `resources` `r`
                    LEFT JOIN `categories` `c` ON `c`.`id` = `r`.`category_id`
                    WHERE `r`.`status` = 'approved'
                    ORDER BY `r`.`download_count` DESC
                    LIMIT ? OFFSET ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$pageSize, $offset]);
            $list = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $list = UrlHelper::fixResourceListUrls($list);

            Response::success([
                'list' => $list,
                'total' => $total,
                'page' => $page,
                'page_size' => $pageSize,
            ], '获取成功');

        } catch (\Exception $e) {
            Response::error('获取热门资源异常: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 获取推荐资源（is_recommended=1，支持分页）
     */
    public function getRecommended()
    {
        try {
            $page = max(1, intval($GLOBALS['REQUEST_DATA']['page'] ?? 1));
            $pageSize = min(50, max(1, intval($GLOBALS['REQUEST_DATA']['page_size'] ?? 20)));
            $offset = ($page - 1) * $pageSize;

            $countStmt = $this->db->prepare("SELECT COUNT(*) FROM `resources` WHERE `status` = 'approved' AND `is_recommended` = 1");
            $countStmt->execute();
            $total = intval($countStmt->fetchColumn());

            $sql = "SELECT `r`.*, `c`.`name` AS `category_name`
                    FROM `resources` `r`
                    LEFT JOIN `categories` `c` ON `c`.`id` = `r`.`category_id`
                    WHERE `r`.`status` = 'approved' AND `r`.`is_recommended` = 1
                    ORDER BY `r`.`sort_order` DESC, `r`.`created_at` DESC
                    LIMIT ? OFFSET ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$pageSize, $offset]);
            $list = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $list = UrlHelper::fixResourceListUrls($list);

            Response::success([
                'list' => $list,
                'total' => $total,
                'page' => $page,
                'page_size' => $pageSize,
            ], '获取成功');

        } catch (\Exception $e) {
            Response::error('获取推荐资源异常: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 获取相关资源（同分类，排除当前资源，取10条）
     */
    public function getRelated()
    {
        try {
            $resourceId = isset($GLOBALS['REQUEST_DATA']['id']) ? intval($GLOBALS['REQUEST_DATA']['id']) : 0;
            if ($resourceId <= 0) {
                $resourceId = isset($_GET['id']) ? intval($_GET['id']) : 0;
            }
            if ($resourceId <= 0) {
                Response::error('缺少资源ID', 400);
            }

            // 先获取当前资源的分类
            $catStmt = $this->db->prepare("SELECT `category_id` FROM `resources` WHERE `id` = ?");
            $catStmt->execute([$resourceId]);
            $categoryId = $catStmt->fetchColumn();

            if (!$categoryId) {
                Response::success([], '暂无相关资源');
            }

            $sql = "SELECT `r`.*, `c`.`name` AS `category_name`
                    FROM `resources` `r`
                    LEFT JOIN `categories` `c` ON `c`.`id` = `r`.`category_id`
                    WHERE `r`.`status` = 'approved' AND `r`.`category_id` = ? AND `r`.`id` != ?
                    ORDER BY `r`.`download_count` DESC
                    LIMIT 10";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$categoryId, $resourceId]);
            $list = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $list = UrlHelper::fixResourceListUrls($list);

            Response::success($list, '获取成功');

        } catch (\Exception $e) {
            Response::error('获取相关资源异常: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 收藏/取消收藏（切换操作）
     */
    public function like()
    {
        try {
            $userId = Auth::required();

            $resourceId = isset($_POST['resource_id']) ? intval($_POST['resource_id']) : 0;
            if ($resourceId <= 0) {
                $resourceId = isset($GLOBALS['REQUEST_DATA']['resource_id']) ? intval($GLOBALS['REQUEST_DATA']['resource_id']) : 0;
            }
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
     * 获取资源评论列表（分页）
     */
    public function getComments()
    {
        try {
            // 评论功能关闭时直接返回空
            if ($this->getSetting('comment_enabled') === '0') {
                Response::success(['list' => [], 'total' => 0, 'page' => 1, 'page_size' => 10], '评论功能已关闭');
            }

            $resourceId = isset($GLOBALS['REQUEST_DATA']['resource_id']) ? intval($GLOBALS['REQUEST_DATA']['resource_id']) : 0;
            if ($resourceId <= 0) {
                $resourceId = isset($_GET['resource_id']) ? intval($_GET['resource_id']) : 0;
            }
            if ($resourceId <= 0) {
                Response::error('缺少资源ID', 400);
            }

            $page = max(1, intval($_GET['page'] ?? 1));
            $pageSize = min(50, max(1, intval($_GET['page_size'] ?? 10)));
            $offset = ($page - 1) * $pageSize;

            // 统计总数
            $countStmt = $this->db->prepare("SELECT COUNT(*) FROM `comments` WHERE `resource_id` = ? AND `status` = 'approved'");
            $countStmt->execute([$resourceId]);
            $total = intval($countStmt->fetchColumn());

            // 查询评论，关联用户信息
            $sql = "SELECT `cm`.`id`, `cm`.`content`, `cm`.`rating`, `cm`.`parent_id`, `cm`.`created_at`,
                           `u`.`id` AS `user_id`, `u`.`nickname`, `u`.`avatar_url`
                    FROM `comments` `cm`
                    INNER JOIN `users` `u` ON `u`.`id` = `cm`.`user_id`
                    WHERE `cm`.`resource_id` = ? AND `cm`.`status` = 'approved'
                    ORDER BY `cm`.`created_at` DESC
                    LIMIT ? OFFSET ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$resourceId, $pageSize, $offset]);
            $list = $stmt->fetchAll(PDO::FETCH_ASSOC);

            Response::success([
                'list' => $list,
                'total' => $total,
                'page' => $page,
                'page_size' => $pageSize,
            ], '获取成功');

        } catch (\Exception $e) {
            Response::error('获取评论异常: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 添加评论（需要登录，支持1-5评分）
     */
    public function addComment()
    {
        try {
            // 评论功能关闭时拒绝
            if ($this->getSetting('comment_enabled') === '0') {
                Response::error('评论功能已关闭', 403);
            }

            $userId = Auth::required();

            $resourceId = isset($_POST['resource_id']) ? intval($_POST['resource_id']) : 0;
            if ($resourceId <= 0) {
                $resourceId = isset($GLOBALS['REQUEST_DATA']['resource_id']) ? intval($GLOBALS['REQUEST_DATA']['resource_id']) : 0;
            }
            $content = isset($_POST['content']) ? trim($_POST['content']) : '';
            if (empty($content)) {
                $content = isset($GLOBALS['REQUEST_DATA']['content']) ? trim($GLOBALS['REQUEST_DATA']['content']) : '';
            }
            $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
            if ($rating <= 0) {
                $rating = isset($GLOBALS['REQUEST_DATA']['rating']) ? intval($GLOBALS['REQUEST_DATA']['rating']) : 0;
            }

            if ($resourceId <= 0) {
                Response::error('缺少资源ID', 400);
            }
            if (empty($content)) {
                Response::error('评论内容不能为空', 400);
            }
            if (mb_strlen($content) > 500) {
                Response::error('评论内容不能超过500字', 400);
            }
            if ($rating < 1 || $rating > 5) {
                Response::error('评分应在1-5之间', 400);
            }

            // 验证资源存在
            $resStmt = $this->db->prepare("SELECT `id` FROM `resources` WHERE `id` = ? AND `status` = 'approved'");
            $resStmt->execute([$resourceId]);
            if (!$resStmt->fetch()) {
                Response::error('资源不存在', 404);
            }

            // 防重复评论：同一用户同一资源24小时内不能重复评论
            $dupStmt = $this->db->prepare(
                "SELECT COUNT(*) FROM `comments` WHERE `user_id` = ? AND `resource_id` = ? AND `created_at` > DATE_SUB(NOW(), INTERVAL 24 HOUR)"
            );
            $dupStmt->execute([$userId, $resourceId]);
            if ($dupStmt->fetchColumn() > 0) {
                Response::error('24小时内不能重复评论', 429);
            }

            // 插入评论
            $now = date('Y-m-d H:i:s');
            $insertStmt = $this->db->prepare(
                "INSERT INTO `comments` (`user_id`, `resource_id`, `content`, `rating`, `status`, `created_at`) VALUES (?, ?, ?, ?, 'approved', ?)"
            );
            $insertStmt->execute([$userId, $resourceId, $content, $rating, $now]);

            // 更新资源评论数
            $this->db->prepare("UPDATE `resources` SET `comment_count` = `comment_count` + 1 WHERE `id` = ?")
                ->execute([$resourceId]);

            Response::success([
                'id' => $this->db->lastInsertId(),
                'content' => $content,
                'rating' => $rating,
                'created_at' => $now,
            ], '评论成功');

        } catch (\Exception $e) {
            Response::error('添加评论异常: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 获取我的下载记录
     */
    public function getMyDownloads()
    {
        try {
            $userId = Auth::required();

            $page = max(1, intval($_GET['page'] ?? 1));
            $pageSize = min(50, max(1, intval($_GET['page_size'] ?? 10)));
            $offset = ($page - 1) * $pageSize;

            $countStmt = $this->db->prepare("SELECT COUNT(*) FROM `downloads` WHERE `user_id` = ?");
            $countStmt->execute([$userId]);
            $total = intval($countStmt->fetchColumn());

            $sql = "SELECT `d`.`id`, `d`.`created_at` AS `download_time`,
                           `r`.`id` AS `resource_id`, `r`.`title`, `r`.`cover_url`, `r`.`file_size`, `r`.`file_suffix`,
                           `c`.`name` AS `category_name`
                    FROM `downloads` `d`
                    INNER JOIN `resources` `r` ON `r`.`id` = `d`.`resource_id`
                    LEFT JOIN `categories` `c` ON `c`.`id` = `r`.`category_id`
                    WHERE `d`.`user_id` = ?
                    ORDER BY `d`.`created_at` DESC
                    LIMIT ? OFFSET ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $pageSize, $offset]);
            $list = $stmt->fetchAll(PDO::FETCH_ASSOC);

            Response::success([
                'list' => $list,
                'total' => $total,
                'page' => $page,
                'page_size' => $pageSize,
            ], '获取成功');

        } catch (\Exception $e) {
            Response::error('获取下载记录异常: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 获取我的收藏列表
     */
    public function getMyFavorites()
    {
        try {
            $userId = Auth::required();

            $page = max(1, intval($_GET['page'] ?? 1));
            $pageSize = min(50, max(1, intval($_GET['page_size'] ?? 10)));
            $offset = ($page - 1) * $pageSize;

            $countStmt = $this->db->prepare("SELECT COUNT(*) FROM `favorites` WHERE `user_id` = ?");
            $countStmt->execute([$userId]);
            $total = intval($countStmt->fetchColumn());

            $sql = "SELECT `f`.`id`, `f`.`created_at` AS `favorite_time`,
                           `r`.`id` AS `resource_id`, `r`.`title`, `r`.`cover_url`, `r`.`file_size`, `r`.`file_suffix`, `r`.`price_type`, `r`.`price`,
                           `c`.`name` AS `category_name`
                    FROM `favorites` `f`
                    INNER JOIN `resources` `r` ON `r`.`id` = `f`.`resource_id`
                    LEFT JOIN `categories` `c` ON `c`.`id` = `r`.`category_id`
                    WHERE `f`.`user_id` = ?
                    ORDER BY `f`.`created_at` DESC
                    LIMIT ? OFFSET ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $pageSize, $offset]);
            $list = $stmt->fetchAll(PDO::FETCH_ASSOC);

            Response::success([
                'list' => $list,
                'total' => $total,
                'page' => $page,
                'page_size' => $pageSize,
            ], '获取成功');

        } catch (\Exception $e) {
            Response::error('获取收藏列表异常: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 别名：获取资源列表
     */
    public function list()
    {
        $this->getList();
    }

    /**
     * 别名：获取资源详情
     */
    public function detail()
    {
        $this->getDetail();
    }

    /**
     * 别名：获取推荐资源
     */
    public function recommend()
    {
        $this->getRecommended();
    }

    /**
     * 别名：获取热门资源
     */
    public function hot()
    {
        $this->getHot();
    }

    /**
     * 别名：获取评论列表
     */
    public function comments()
    {
        $this->getComments();
    }

    /**
     * 别名：添加评论
     */
    public function comment()
    {
        $this->addComment();
    }

    /**
     * 别名：获取相关资源
     */
    public function related()
    {
        $this->getRelated();
    }

    /**
     * 别名：下载资源（委托给 DownloadController）
     */
    public function download()
    {
        require_once __DIR__ . '/DownloadController.php';
        $ctrl = new DownloadController();
        $ctrl->download();
    }

    /**
     * 创建资源（管理员）
     */
    public function create()
    {
        try {
            $data = $GLOBALS['REQUEST_DATA'] ?? [];
            $title = trim($data['title'] ?? '');
            $categoryId = intval($data['category_id'] ?? 0);
            $description = trim($data['description'] ?? '');
            $coverUrl = trim($data['cover_url'] ?? '');
            $fileUrl = trim($data['file_url'] ?? '');
            $fileSize = intval($data['file_size'] ?? 0);
            $fileType = trim($data['file_type'] ?? '');
            $fileSuffix = trim($data['file_suffix'] ?? '');
            $priceType = $data['price_type'] ?? 'free';
            $price = floatval($data['price'] ?? 0);
            $vipPrice = floatval($data['vip_price'] ?? 0);

            if (empty($title)) {
                Response::error('资源标题不能为空', 400);
            }
            if ($categoryId <= 0) {
                Response::error('请选择分类', 400);
            }

            $id = $this->db->insert('resources', [
                'category_id' => $categoryId,
                'title' => $title,
                'description' => $description,
                'cover_url' => $coverUrl,
                'file_url' => $fileUrl,
                'file_size' => $fileSize,
                'file_type' => $fileType,
                'file_suffix' => $fileSuffix,
                'price_type' => $priceType,
                'price' => $price,
                'vip_price' => $vipPrice,
                'status' => 'approved',
                'admin_id' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            Response::success(['id' => $id], '创建成功');

        } catch (\Exception $e) {
            Response::error('创建资源异常: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 更新资源（管理员）
     */
    public function update()
    {
        try {
            $data = $GLOBALS['REQUEST_DATA'] ?? [];
            $id = intval($data['id'] ?? 0);
            if ($id <= 0) {
                Response::error('缺少资源ID', 400);
            }

            $updateData = [];
            if (isset($data['title'])) $updateData['title'] = trim($data['title']);
            if (isset($data['category_id'])) $updateData['category_id'] = intval($data['category_id']);
            if (isset($data['description'])) $updateData['description'] = trim($data['description']);
            if (isset($data['cover_url'])) $updateData['cover_url'] = trim($data['cover_url']);
            if (isset($data['file_url'])) $updateData['file_url'] = trim($data['file_url']);
            if (isset($data['file_size'])) $updateData['file_size'] = intval($data['file_size']);
            if (isset($data['file_type'])) $updateData['file_type'] = trim($data['file_type']);
            if (isset($data['file_suffix'])) $updateData['file_suffix'] = trim($data['file_suffix']);
            if (isset($data['price_type'])) $updateData['price_type'] = $data['price_type'];
            if (isset($data['price'])) $updateData['price'] = floatval($data['price']);
            if (isset($data['vip_price'])) $updateData['vip_price'] = floatval($data['vip_price']);
            if (isset($data['status'])) $updateData['status'] = $data['status'];
            if (isset($data['is_top'])) $updateData['is_top'] = intval($data['is_top']);
            if (isset($data['is_recommended'])) $updateData['is_recommended'] = intval($data['is_recommended']);
            $updateData['updated_at'] = date('Y-m-d H:i:s');

            $this->db->update('resources', $updateData, 'id = :id', [':id' => $id]);

            Response::success([], '更新成功');

        } catch (\Exception $e) {
            Response::error('更新资源异常: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 删除资源（管理员）
     */
    public function delete()
    {
        try {
            $id = intval($GLOBALS['REQUEST_DATA']['id'] ?? 0);
            if ($id <= 0) {
                Response::error('缺少资源ID', 400);
            }

            $this->db->delete('resources', 'id = :id', [':id' => $id]);

            Response::success([], '删除成功');

        } catch (\Exception $e) {
            Response::error('删除资源异常: ' . $e->getMessage(), 500);
        }
    }

    // ============ 私有辅助方法 ============

    /**
     * 获取系统设置值
     */
    private function getSetting($key)
    {
        $stmt = $this->db->prepare("SELECT `setting_value` FROM `system_settings` WHERE `setting_key` = ?");
        $stmt->execute([$key]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['setting_value'] : null;
    }

    /**
     * 递归获取所有子分类ID
     * @param int $parentId 父分类ID
     * @return array 所有子孙分类ID
     */
    private function getAllChildCategoryIds($parentId)
    {
        $ids = [];
        $stmt = $this->db->prepare("SELECT `id` FROM `categories` WHERE `parent_id` = ? AND `status` = 1");
        $stmt->execute([$parentId]);
        $children = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($children as $child) {
            $ids[] = intval($child['id']);
            $ids = array_merge($ids, $this->getAllChildCategoryIds($child['id']));
        }
        return $ids;
    }

    /**
     * 批量为资源列表附加标签
     */
    private function attachTags($list)
    {
        if (empty($list)) {
            return $list;
        }

        $ids = array_column($list, 'id');
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $sql = "SELECT `rt`.`resource_id`, `t`.`id`, `t`.`name`
                FROM `resource_tags` `rt`
                INNER JOIN `tags` `t` ON `t`.`id` = `rt`.`tag_id`
                WHERE `rt`.`resource_id` IN ({$placeholders})";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($ids);
        $allTags = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 按 resource_id 分组
        $tagMap = [];
        foreach ($allTags as $tag) {
            $rid = $tag['resource_id'];
            if (!isset($tagMap[$rid])) {
                $tagMap[$rid] = [];
            }
            $tagMap[$rid][] = ['id' => $tag['id'], 'name' => $tag['name']];
        }

        foreach ($list as &$item) {
            $item['tags'] = isset($tagMap[$item['id']]) ? $tagMap[$item['id']] : [];
        }

        return $list;
    }
}
