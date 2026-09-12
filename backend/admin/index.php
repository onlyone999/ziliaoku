<?php
/**
 * 管理后台 - 入口
 * 检查认证并重定向
 */
session_start();

if (!empty($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
} else {
    header('Location: login.php');
}
exit;
