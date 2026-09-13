<?php
/**
 * 后台分页条数公共函数
 * 在 header.php 之前 include 即可使用
 */
if (!function_exists('getPerPage')) {
    function getPerPage($default = 15) {
        static $perPage = null;
        if ($perPage === null) {
            try {
                $db = \Database::getInstance();
                $row = $db->fetch("SELECT setting_value FROM system_settings WHERE setting_key = 'admin_per_page' LIMIT 1");
                $perPage = max(5, min(100, (int)($row['setting_value'] ?? $default)));
            } catch (\Exception $e) {
                $perPage = $default;
            }
        }
        return $perPage;
    }
}
