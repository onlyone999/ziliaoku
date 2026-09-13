<?php
/**
 * 系统设置控制器（公开接口）
 * GET /api/settings/config
 */

require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../core/Database.php';

class SettingsController
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * 获取公开配置
     * GET /api/settings/config
     * 返回前端需要的功能开关等配置
     */
    public function config()
    {
        try {
            $keys = ['activity_enabled', 'comment_enabled', 'feedback_enabled', 'site_name', 'home_recommend_count', 'app_page_size'];
            $placeholders = implode(',', array_fill(0, count($keys), '?'));
            $rows = $this->db->fetchAll(
                "SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ({$placeholders})",
                $keys
            );

            $config = [];
            foreach ($rows as $row) {
                $config[$row['setting_key']] = $row['setting_value'];
            }

            // 默认值
            $config['activity_enabled']      = $config['activity_enabled'] ?? '1';
            $config['comment_enabled']       = $config['comment_enabled'] ?? '1';
            $config['feedback_enabled']      = $config['feedback_enabled'] ?? '1';
            $config['home_recommend_count']  = $config['home_recommend_count'] ?? '10';
            $config['app_page_size']         = $config['app_page_size'] ?? '10';

            Response::success($config);

        } catch (\Exception $e) {
            Response::error('获取配置异常: ' . $e->getMessage(), 500);
        }
    }
}
