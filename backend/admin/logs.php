<?php
/**
 * 管理后台 - 操作日志
 * 列表展示、筛选、搜索、分页
 */
session_start();
if (empty($_SESSION['admin_id'])) { header('Location: login.php'); exit; }

require_once __DIR__ . '/../core/Database.php';
$db = Database::getInstance();

// 自动创建 admin_logs 表（如果不存在）
$db->query("CREATE TABLE IF NOT EXISTS `admin_logs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `admin_id` INT UNSIGNED NOT NULL DEFAULT 0,
    `admin_username` VARCHAR(64) NOT NULL DEFAULT '',
    `action` VARCHAR(32) NOT NULL DEFAULT '',
    `target_type` VARCHAR(32) NOT NULL DEFAULT '',
    `target_id` INT UNSIGNED NOT NULL DEFAULT 0,
    `detail` TEXT,
    `ip` VARCHAR(64) NOT NULL DEFAULT '',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_admin_id` (`admin_id`),
    KEY `idx_action` (`action`),
    KEY `idx_target` (`target_type`, `target_id`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='后台操作日志'");

// 筛选条件
$search        = trim($_GET['search'] ?? '');
$filterAdmin   = trim($_GET['admin'] ?? '');
$filterAction  = trim($_GET['action'] ?? '');
$filterTarget  = trim($_GET['target_type'] ?? '');
$dateFrom      = $_GET['date_from'] ?? '';
$dateTo        = $_GET['date_to'] ?? '';
$page          = max(1, (int)($_GET['page'] ?? 1));
$perPage       = 20;
$offset        = ($page - 1) * $perPage;

$where = '1=1';
$params = [];
if ($search !== '') {
    $where .= ' AND (detail LIKE :search OR admin_username LIKE :search)';
    $params[':search'] = '%' . $search . '%';
}
if ($filterAdmin !== '') {
    $where .= ' AND admin_username LIKE :admin';
    $params[':admin'] = '%' . $filterAdmin . '%';
}
if ($filterAction !== '') {
    $where .= ' AND action = :action';
    $params[':action'] = $filterAction;
}
if ($filterTarget !== '') {
    $where .= ' AND target_type = :target';
    $params[':target'] = $filterTarget;
}
if ($dateFrom !== '') {
    $where .= ' AND created_at >= :date_from';
    $params[':date_from'] = $dateFrom . ' 00:00:00';
}
if ($dateTo !== '') {
    $where .= ' AND created_at <= :date_to';
    $params[':date_to'] = $dateTo . ' 23:59:59';
}

$total = $db->fetch('SELECT COUNT(*) AS cnt FROM admin_logs WHERE ' . $where, $params);
$totalRows = (int)($total['cnt'] ?? 0);
$totalPages = max(1, ceil($totalRows / $perPage));

$logs = $db->fetchAll(
    "SELECT * FROM admin_logs WHERE {$where} ORDER BY created_at DESC LIMIT {$perPage} OFFSET {$offset}",
    $params
);

// 操作类型映射
$actionLabels = [
    'delete'        => ['删除', 'badge-danger'],
    'approve'       => ['审核通过', 'badge-success'],
    'reject'        => ['审核拒绝', 'badge-warning'],
    'toggle_status' => ['切换状态', 'badge-info'],
    'set_vip'       => ['设置VIP', 'badge-info'],
    'adjust_points' => ['调整积分', 'badge-info'],
    'refund'        => ['退款', 'badge-danger'],
    'save_settings' => ['保存设置', 'badge-success'],
];

$targetLabels = [
    'resource' => '资源',
    'user'     => '用户',
    'order'    => '订单',
    'banner'   => '轮播图',
    'setting'  => '设置',
];

function buildUrl($p) {
    return 'logs.php?' . http_build_query(array_filter($p, function($v) { return $v !== '' && $v !== 0; }));
}

include __DIR__ . '/header.php';
?>

<!-- Toolbar -->
<div class="toolbar">
  <div class="toolbar-left">
    <form method="GET" class="d-flex align-center gap-10" style="flex-wrap:wrap">
      <div class="search-box">
        <input type="text" name="search" placeholder="搜索详情或管理员..."
               value="<?php echo htmlspecialchars($search); ?>">
      </div>
      <input type="text" name="admin" class="filter-select" placeholder="管理员用户名"
             value="<?php echo htmlspecialchars($filterAdmin); ?>" style="width:120px;">
      <select name="action" class="filter-select">
        <option value="">全部操作</option>
        <?php foreach ($actionLabels as $k => $v): ?>
        <option value="<?php echo $k; ?>" <?php echo $filterAction === $k ? 'selected' : ''; ?>><?php echo $v[0]; ?></option>
        <?php endforeach; ?>
      </select>
      <select name="target_type" class="filter-select">
        <option value="">全部对象</option>
        <?php foreach ($targetLabels as $k => $v): ?>
        <option value="<?php echo $k; ?>" <?php echo $filterTarget === $k ? 'selected' : ''; ?>><?php echo $v; ?></option>
        <?php endforeach; ?>
      </select>
      <input type="date" name="date_from" class="filter-select" value="<?php echo htmlspecialchars($dateFrom); ?>" placeholder="起始日期">
      <input type="date" name="date_to" class="filter-select" value="<?php echo htmlspecialchars($dateTo); ?>" placeholder="结束日期">
      <button type="submit" class="btn btn-primary btn-sm">筛选</button>
      <?php if ($search || $filterAdmin || $filterAction || $filterTarget || $dateFrom || $dateTo): ?>
      <a href="logs.php" class="btn btn-outline btn-sm">清除</a>
      <?php endif; ?>
    </form>
  </div>
</div>

<!-- Logs Table -->
<div class="card">
  <div class="card-body no-padding">
    <?php if (empty($logs)): ?>
    <div class="empty-state">
      <div class="empty-icon">&#x1F4CB;</div>
      <h4>暂无操作日志</h4>
      <p>当管理员进行关键操作时，系统会自动记录日志</p>
    </div>
    <?php else: ?>
    <div class="table-wrapper">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>管理员</th>
            <th>操作</th>
            <th>对象类型</th>
            <th>对象ID</th>
            <th>详情</th>
            <th>IP</th>
            <th>时间</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($logs as $log): ?>
          <tr>
            <td><?php echo $log['id']; ?></td>
            <td>
              <span style="font-weight:600;"><?php echo htmlspecialchars($log['admin_username']); ?></span>
              <br><span style="font-size:11px;color:var(--text-muted);">#<?php echo $log['admin_id']; ?></span>
            </td>
            <td>
              <?php
                $al = $actionLabels[$log['action']] ?? [$log['action'], 'badge-default'];
              ?>
              <span class="badge <?php echo $al[1]; ?>"><?php echo $al[0]; ?></span>
            </td>
            <td>
              <?php echo $targetLabels[$log['target_type']] ?? $log['target_type']; ?>
            </td>
            <td><?php echo $log['target_id'] ?: '-'; ?></td>
            <td style="max-width:300px;white-space:normal;font-size:13px;">
              <?php echo htmlspecialchars($log['detail']); ?>
            </td>
            <td style="font-size:12px;font-family:monospace;"><?php echo htmlspecialchars($log['ip']); ?></td>
            <td style="font-size:12px;white-space:nowrap;"><?php echo $log['created_at']; ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div class="pagination">
      <span class="page-info">共 <?php echo $totalRows; ?> 条日志，第 <?php echo $page; ?>/<?php echo $totalPages; ?> 页</span>
      <div class="page-links">
        <?php if ($page > 1): ?>
        <a href="<?php echo buildUrl(['search'=>$search,'admin'=>$filterAdmin,'action'=>$filterAction,'target_type'=>$filterTarget,'date_from'=>$dateFrom,'date_to'=>$dateTo,'page'=>$page-1]); ?>">&laquo; 上一页</a>
        <?php else: ?><span class="disabled">&laquo; 上一页</span><?php endif; ?>
        <?php $s=max(1,$page-3); $e=min($totalPages,$page+3); for($p=$s;$p<=$e;$p++): ?>
        <a href="<?php echo buildUrl(['search'=>$search,'admin'=>$filterAdmin,'action'=>$filterAction,'target_type'=>$filterTarget,'date_from'=>$dateFrom,'date_to'=>$dateTo,'page'=>$p]); ?>"
           class="<?php echo $p===$page?'active':''; ?>"><?php echo $p; ?></a>
        <?php endfor; ?>
        <?php if ($page < $totalPages): ?>
        <a href="<?php echo buildUrl(['search'=>$search,'admin'=>$filterAdmin,'action'=>$filterAction,'target_type'=>$filterTarget,'date_from'=>$dateFrom,'date_to'=>$dateTo,'page'=>$page+1]); ?>">下一页 &raquo;</a>
        <?php else: ?><span class="disabled">下一页 &raquo;</span><?php endif; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>
