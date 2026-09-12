<?php
/**
 * 搜索控制器
 * 处理热门关键词和资源全文搜索
 */

require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/UrlHelper.php';

class SearchController
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * 获取热门搜索关键词
     * 按搜索次数降序返回
     */
    public function hot()
    {
        try {
            $limit = isset($GLOBALS['REQUEST_DATA']['limit']) ? intval($GLOBALS['REQUEST_DATA']['limit']) : 10;

            // 优先从 search_keywords 表读取（如有）
            $tableExists = false;
            try {
                $this->db->query("SELECT 1 FROM `search_keywords` LIMIT 0");
                $tableExists = true;
            } catch (\Exception $e) {
                $tableExists = false;
            }

            if ($tableExists) {
                $stmt = $this->db->prepare(
                    "SELECT `keyword` FROM `search_keywords` WHERE `status` = 1 ORDER BY `search_count` DESC LIMIT ?"
                );
                $stmt->execute([$limit]);
                $list = $stmt->fetchAll(PDO::FETCH_COLUMN);
                Response::success($list, '获取成功');
            } else {
                // 降级：从资源标题提取热门关键词（按下载量取 Top 标题词）
                $stmt = $this->db->prepare(
                    "SELECT `title` AS `keyword`, `download_count` AS `search_count` FROM `resources` WHERE `status` = 'approved' ORDER BY `download_count` DESC LIMIT ?"
                );
                $stmt->execute([$limit]);
                $list = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if (empty($list)) {
                    // Return default hot keywords
                    $defaults = [
                        ['keyword' => 'PPT模板', 'search_count' => 999],
                        ['keyword' => '简历', 'search_count' => 888],
                        ['keyword' => '设计素材', 'search_count' => 777],
                        ['keyword' => 'Excel', 'search_count' => 666],
                        ['keyword' => 'Word文档', 'search_count' => 555],
                        ['keyword' => 'PDF', 'search_count' => 444],
                        ['keyword' => '源码', 'search_count' => 333],
                        ['keyword' => '教程', 'search_count' => 222],
                    ];
                    Response::success($defaults, '获取成功');
                } else {
                    Response::success($list, '获取成功');
                }
            }

        } catch (\Exception $e) {
            Response::error('获取热门关键词异常: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 全文搜索资源
     * 在标题和描述中搜索，按相关度排序，支持分页
     */
    public function resource()
    {
        try {
            $keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
            if (empty($keyword)) {
                Response::error('请输入搜索关键词', 400);
            }

            $page = max(1, intval($_GET['page'] ?? 1));
            $pageSize = min(50, max(1, intval($_GET['page_size'] ?? 10)));
            $offset = ($page - 1) * $pageSize;

            $categoryId = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;

            // 构建搜索条件
            $where = "`r`.`status` = 'approved' AND (`r`.`title` LIKE ? OR `r`.`description` LIKE ?)";
            $params = ['%' . $keyword . '%', '%' . $keyword . '%'];

            if ($categoryId > 0) {
                $where .= " AND `r`.`category_id` = ?";
                $params[] = $categoryId;
            }

            // 统计总数
            $countSql = "SELECT COUNT(*) FROM `resources` `r` WHERE {$where}";
            $countStmt = $this->db->prepare($countSql);
            $countStmt->execute($params);
            $total = intval($countStmt->fetchColumn());

            // 查询结果，标题匹配优先排序
            $likeParam = '%' . $keyword . '%';
            $sql = "SELECT `r`.*, `c`.`name` AS `category_name`,
                           (CASE WHEN `r`.`title` LIKE ? THEN 2 ELSE 1 END) AS `relevance`
                    FROM `resources` `r`
                    LEFT JOIN `categories` `c` ON `c`.`id` = `r`.`category_id`
                    WHERE {$where}
                    ORDER BY `relevance` DESC, `r`.`download_count` DESC
                    LIMIT ? OFFSET ?";
            $queryParams = array_merge([$likeParam], $params, [$pageSize, $offset]);
            $stmt = $this->db->prepare($sql);
            $stmt->execute($queryParams);
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
     * 搜索建议（自动补全）
     */
    public function suggest()
    {
        $keyword = trim($GLOBALS['REQUEST_DATA']['keyword'] ?? '');
        if (empty($keyword) || mb_strlen($keyword) < 1) {
            Response::success([]);
            return;
        }

        try {
            $like = '%' . $keyword . '%';
            $list = $this->db->fetchAll(
                'SELECT DISTINCT title FROM resources WHERE status = :s AND title LIKE :kw ORDER BY download_count DESC LIMIT 8',
                [':s' => 'approved', ':kw' => $like]
            );
            $suggestions = array_map(function($r) { return $r['title']; }, $list);
            Response::success($suggestions);
        } catch (\Exception $e) {
            Response::success([]);
        }
    }
}
