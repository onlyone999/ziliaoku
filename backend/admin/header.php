<?php
/**
 * 管理后台 - 通用头部
 */
if (session_status() === PHP_SESSION_NONE) session_start();

if (empty($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$adminName = $_SESSION['admin_realname'] ?: $_SESSION['admin_username'];
$adminRole = $_SESSION['admin_role'] ?? 'admin';
$csrfToken = $_SESSION['csrf_token'] ?? '';
if (empty($csrfToken)) {
    $csrfToken = bin2hex(random_bytes(32));
    $_SESSION['csrf_token'] = $csrfToken;
}

$navItems = [
    ['key' => 'bigscreen',  'label' => '数据大屏',   'icon' => '🖥️', 'url' => 'bigscreen.php'],
    ['key' => 'dashboard',  'label' => '仪表盘',     'icon' => '📊', 'url' => 'dashboard.php'],
    ['key' => 'resources',  'label' => '资源管理',   'icon' => '📄', 'url' => 'resources.php'],
    ['key' => 'categories', 'label' => '分类管理',   'icon' => '📂', 'url' => 'categories.php'],
    ['key' => 'users',      'label' => '用户管理',   'icon' => '👥', 'url' => 'users.php'],
    ['key' => 'orders',     'label' => '订单管理',   'icon' => '💰', 'url' => 'orders.php'],
    ['key' => 'viplans',    'label' => 'VIP管理',    'icon' => '⭐', 'url' => 'viplans.php'],
    ['key' => 'comments',   'label' => '评论管理',   'icon' => '💬', 'url' => 'comments.php'],
    ['key' => 'announcements', 'label' => '公告管理',   'icon' => '📢', 'url' => 'announcements.php'],
    ['key' => 'activities',    'label' => '活动管理',   'icon' => '🎯', 'url' => 'activities.php'],
    ['key' => 'banners',    'label' => '轮播图管理', 'icon' => '🖼️', 'url' => 'banners.php'],
    ['key' => 'logs',       'label' => '操作日志',   'icon' => '📋', 'url' => 'logs.php'],
    ['key' => 'password',   'label' => '修改密码',   'icon' => '🔑', 'url' => 'password.php'],
    ['key' => 'settings',   'label' => '系统设置',   'icon' => '⚙️', 'url' => 'settings.php'],
    ['key' => 'feedback',   'label' => '意见反馈',   'icon' => '📝', 'url' => 'feedback.php'],
];

$pageTitles = [
    'bigscreen' => '数据大屏',
    'dashboard' => '仪表盘',
    'resources' => '资源管理',
    'categories' => '分类管理',
    'users' => '用户管理',
    'orders' => '订单管理',
    'viplans' => 'VIP管理',
    'comments' => '评论管理',
    'announcements' => '公告管理',
    'activities' => '活动管理',
    'banners' => '轮播图管理',
    'logs' => '操作日志',
    'password' => '修改密码',
    'settings' => '系统设置',
    'feedback' => '意见反馈',
];
$pageTitle = $pageTitles[$currentPage] ?? '管理后台';

// Role labels
$roleLabels = [
    'super' => '超级管理员',
    'admin' => '管理员',
    'editor' => '编辑',
];
$roleLabel = $roleLabels[$adminRole] ?? $adminRole;
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?php echo htmlspecialchars($csrfToken); ?>">
  <title><?php echo htmlspecialchars($pageTitle); ?> - 资料库管理后台</title>
  <link rel="icon" type="image/x-icon" href="favicon.ico">
  <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body>
<div class="admin-wrapper">
  <!-- 侧边栏 -->
  <aside class="sidebar">
    <div class="sidebar-logo">
      <div class="logo-icon">Z</div>
      <div>
        <h2>资料库</h2>
        <small>管理后台</small>
      </div>
    </div>
    <nav class="sidebar-nav">
      <div class="nav-section">功能菜单</div>
      <?php foreach ($navItems as $nav): ?>
      <a href="<?php echo htmlspecialchars($nav['url']); ?>"
         class="<?php echo $currentPage === $nav['key'] ? 'active' : ''; ?>">
        <span class="nav-icon"><?php echo $nav['icon']; ?></span>
        <?php echo htmlspecialchars($nav['label']); ?>
      </a>
      <?php endforeach; ?>
    </nav>
    <div class="sidebar-footer">
      &copy; <?php echo date('Y'); ?> 资料库管理系统
    </div>
  </aside>

  <!-- 主内容区 -->
  <div class="main-content">
    <!-- 顶部栏 -->
    <header class="topbar">
      <div class="topbar-left">
        <button class="menu-toggle" onclick="Admin.toggleSidebar()">&#9776;</button>
        <span class="page-title"><?php echo htmlspecialchars($pageTitle); ?></span>
      </div>
      <div class="topbar-right">
        <div class="admin-info">
          <div class="admin-avatar"><?php echo mb_substr($adminName, 0, 1); ?></div>
          <span><?php echo htmlspecialchars($adminName); ?></span>
          <span class="badge badge-info"><?php echo htmlspecialchars($roleLabel); ?></span>
        </div>
        <a href="logout.php" class="btn-logout" title="退出登录">&#8594; 退出登录</a>
      </div>
    </header>

    <!-- 内容区 -->
    <div class="content-area">
