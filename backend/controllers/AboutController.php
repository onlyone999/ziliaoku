<?php
/**
 * 关于我们 API
 * GET /api/about/info
 */

require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../core/Database.php';

class AboutController
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function info()
    {
        try {
            $keys = ['about_version', 'about_content', 'about_qrcode', 'about_qrcode_title', 'about_qrcode_desc', 'about_copyright', 'site_name'];
            $placeholders = implode(',', array_fill(0, count($keys), '?'));
            $rows = $this->db->fetchAll(
                "SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ({$placeholders})",
                $keys
            );

            $data = [];
            foreach ($rows as $row) {
                $data[$row['setting_key']] = $row['setting_value'];
            }

            Response::success([
                'version'      => $data['about_version'] ?? 'v1.0.0',
                'content'      => $data['about_content'] ?? '',
                'qrcode'       => $data['about_qrcode'] ?? '',
                'qrcode_title' => $data['about_qrcode_title'] ?? '扫码添加客服',
                'qrcode_desc'  => $data['about_qrcode_desc'] ?? '长按识别二维码，添加客服微信',
                'copyright'    => $data['about_copyright'] ?? '',
                'site_name'    => $data['site_name'] ?? '资料下载器',
            ]);
        } catch (\Exception $e) {
            Response::error('获取信息失败: ' . $e->getMessage(), 500);
        }
    }
}
