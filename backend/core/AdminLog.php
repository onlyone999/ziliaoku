<?php
/**
 * 操作日志工具类
 */
class AdminLog
{
    /**
     * 记录操作日志
     *
     * @param string      $action     操作类型，如 delete、approve、reject 等
     * @param string      $targetType 操作对象类型，如 resource、user、order 等
     * @param int         $targetId   操作对象ID
     * @param string      $detail     详细描述
     */
    public static function log(string $action, string $targetType, int $targetId, string $detail = ''): void
    {
        $adminId       = (int)($_SESSION['admin_id'] ?? 0);
        $adminUsername = $_SESSION['admin_realname'] ?: ($_SESSION['admin_username'] ?? '');
        $ip            = self::getClientIp();

        try {
            $db = Database::getInstance();
            $db->insert('admin_logs', [
                'admin_id'       => $adminId,
                'admin_username' => $adminUsername,
                'action'         => $action,
                'target_type'    => $targetType,
                'target_id'      => $targetId,
                'detail'         => $detail,
                'ip'             => $ip,
                'created_at'     => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            // 日志写入失败不应影响业务流程
            error_log('AdminLog::log error: ' . $e->getMessage());
        }
    }

    /**
     * 获取客户端IP
     */
    private static function getClientIp(): string
    {
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);
        }
        if (!empty($_SERVER['HTTP_X_REAL_IP'])) {
            return $_SERVER['HTTP_X_REAL_IP'];
        }
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}
