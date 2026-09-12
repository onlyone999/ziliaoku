<?php
/**
 * 管理后台 - 仪表盘（增强版）
 * 含统计图表、快捷操作、最近活动
 */
session_start();
if (empty($_SESSION['admin_id'])) { header('Location: login.php'); exit; }

require_once __DIR__ . '/../core/Database.php';
$db = Database::getInstance();

// ======== 核心统计 ========
$totalUsers      = $db->count('users');
$totalResources  = $db->count('resources');
$totalDownloads  = (int) $db->fetch('SELECT COALESCE(SUM(download_count),0) AS v FROM resources')['v'];
$totalRevenue    = (float) $db->fetch("SELECT COALESCE(SUM(pay_amount),0) AS v FROM orders WHERE status='paid'")['v'];

$today = date('Y-m-d');
$monthStart = date('Y-m-01');
$todayOrders = $db->fetch("SELECT COUNT(*) AS cnt, COALESCE(SUM(pay_amount),0) AS rev FROM orders WHERE status='paid' AND DATE(created_at)=:d", [':d'=>$today]);
$monthOrders = $db->fetch("SELECT COUNT(*) AS cnt, COALESCE(SUM(pay_amount),0) AS rev FROM orders WHERE status='paid' AND created_at >= :d", [':d'=>$monthStart.' 00:00:00']);
$todayNewUsers   = $db->count('users', 'DATE(created_at) = :d', [':d'=>$today]);
$pendingResources= $db->count('resources', "status = 'pending'");
$pendingFeedback = $db->count('feedback', "status = 'pending'");

// ======== 近7天订单趋势 ========
$chartData = $db->fetchAll(
    "SELECT DATE(created_at) AS day, COUNT(*) AS cnt, COALESCE(SUM(pay_amount),0) AS rev
     FROM orders WHERE status='paid' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
     GROUP BY DATE(created_at) ORDER BY day"
);
$chartDays = []; $chartCounts = []; $chartRevs = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-{$i} days"));
    $chartDays[] = date('m/d', strtotime($d));
    $found = false;
    foreach ($chartData as $row) {
        if ($row['day'] === $d) { $chartCounts[] = (int)$row['cnt']; $chartRevs[] = (float)$row['rev']; $found = true; break; }
    }
    if (!$found) { $chartCounts[] = 0; $chartRevs[] = 0; }
}

// ======== 分类资源分布 ========
$catStats = $db->fetchAll(
    "SELECT c.name, COUNT(r.id) AS cnt FROM categories c LEFT JOIN resources r ON c.id=r.category_id AND r.status='approved'
     WHERE c.parent_id=0 GROUP BY c.id, c.name ORDER BY cnt DESC LIMIT 8"
);

// ======== 最近订单 ========
$recentOrders = $db->fetchAll(
    "SELECT o.*, u.nickname FROM orders o LEFT JOIN users u ON o.user_id=u.id ORDER BY o.created_at DESC LIMIT 8"
);
$orderStatusMap = ['pending'=>'待支付','paid'=>'已支付','refunded'=>'已退款','cancelled'=>'已取消'];
$orderBadgeMap  = ['pending'=>'badge-warning','paid'=>'badge-success','refunded'=>'badge-info','cancelled'=>'badge-default'];

// ======== 最近用户 ========
$recentUsers = $db->fetchAll("SELECT * FROM users ORDER BY created_at DESC LIMIT 8");
$vipLabels = [0=>'普通用户',1=>'月卡VIP',2=>'季卡VIP',3=>'年卡VIP',4=>'终身VIP'];

// ======== 最近活动 ========
$activities = [];
$recentRes = $db->fetchAll("SELECT title, created_at FROM resources ORDER BY created_at DESC LIMIT 5");
foreach ($recentRes as $r) $activities[] = ['icon'=>'📄','text'=>'新资源：'.mb_substr($r['title'],0,20),'time'=>$r['created_at']];
$recentOrd = $db->fetchAll("SELECT order_no, amount, created_at FROM orders ORDER BY created_at DESC LIMIT 5");
foreach ($recentOrd as $o) $activities[] = ['icon'=>'💰','text'=>'新订单：'.$o['order_no'],'time'=>$o['created_at']];
$recentUsr = $db->fetchAll("SELECT nickname, created_at FROM users ORDER BY created_at DESC LIMIT 3");
foreach ($recentUsr as $u) $activities[] = ['icon'=>'👤','text'=>'新用户：'.$u['nickname'],'time'=>$u['created_at']];
usort($activities, function($a,$b){ return strtotime($b['time']) - strtotime($a['time']); });
$activities = array_slice($activities, 0, 10);

include __DIR__ . '/header.php';
?>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<style>
.dashboard{padding:24px;animation:fadeSlideIn .5s ease}

/* 统计卡片 - 简洁干净 */
.stats-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:20px;margin-bottom:24px}
.stat-card{
  background:rgba(255,255,255,.75);backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);
  border-radius:16px;padding:24px;position:relative;overflow:hidden;
  border:1px solid rgba(255,255,255,.6);
  box-shadow:0 2px 12px rgba(0,0,0,.04);
  transition:all .3s cubic-bezier(.4,0,.2,1);
  animation:fadeSlideIn .5s ease both;
}
.stat-card:nth-child(2){animation-delay:.05s}
.stat-card:nth-child(3){animation-delay:.1s}
.stat-card:nth-child(4){animation-delay:.15s}
.stat-card:hover{transform:translateY(-4px);box-shadow:0 8px 24px rgba(0,0,0,.08)}
.stat-card .stat-icon{
  width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;
  font-size:20px;margin-bottom:16px;
}
.stat-card .stat-label{font-size:13px;color:#64748b;margin-bottom:4px;font-weight:500}
.stat-card .stat-value{font-size:26px;font-weight:800;color:#1e293b}
.stat-card .stat-sub{font-size:12px;color:#94a3b8;margin-top:8px}
.stat-card.blue   .stat-icon{background:#eef2ff;color:#6366f1}
.stat-card.green  .stat-icon{background:#ecfdf5;color:#10b981}
.stat-card.orange .stat-icon{background:#fffbeb;color:#f59e0b}
.stat-card.purple .stat-icon{background:#f5f3ff;color:#8b5cf6}
.stat-card.red    .stat-icon{background:#fef2f2;color:#ef4444}
.stat-card.pink   .stat-icon{background:#fdf2f8;color:#ec4899}

/* 快捷操作 - 简洁 */
.quick-actions{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:12px;margin-bottom:24px}
.quick-action{
  display:flex;align-items:center;gap:10px;padding:14px 16px;
  background:rgba(255,255,255,.7);backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px);
  border-radius:12px;border:1px solid rgba(255,255,255,.6);
  box-shadow:0 1px 4px rgba(0,0,0,.04);text-decoration:none;color:#334155;
  transition:all .25s cubic-bezier(.4,0,.2,1);cursor:pointer;
}
.quick-action:hover{transform:translateY(-2px);box-shadow:0 6px 16px rgba(0,0,0,.06)}
.quick-action .qa-icon{font-size:18px;width:34px;height:34px;display:flex;align-items:center;justify-content:center;border-radius:10px;background:#f1f5f9}
.quick-action .qa-label{font-size:13px;font-weight:600}

/* 图表区域 - 简洁 */
.chart-grid{display:grid;grid-template-columns:2fr 1fr;gap:20px;margin-bottom:24px}
.chart-card{
  background:rgba(255,255,255,.75);backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);
  border-radius:16px;padding:24px;border:1px solid rgba(255,255,255,.6);
  box-shadow:0 2px 12px rgba(0,0,0,.04);overflow:hidden;
  animation:fadeSlideIn .6s ease both;
}
.chart-card h3{font-size:16px;font-weight:700;color:#1e293b;margin-bottom:16px;display:flex;align-items:center;gap:8px}
.chart-card h3 span{font-size:18px}
.chart-card canvas{max-height:220px !important}

/* 表格区域 - 简洁 */
.tables-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px}
.table-card{
  background:rgba(255,255,255,.75);backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);
  border-radius:16px;padding:24px;border:1px solid rgba(255,255,255,.6);
  box-shadow:0 2px 12px rgba(0,0,0,.04);
  animation:fadeSlideIn .6s ease both;
}
.table-card h3{font-size:16px;font-weight:700;color:#1e293b;margin-bottom:16px;display:flex;align-items:center;gap:8px}
.table-card h3 span{font-size:18px}
.table-card .view-all{
  margin-left:auto;font-size:12px;color:#6366f1;text-decoration:none;font-weight:600;
  transition:color .2s;
}
.table-card .view-all:hover{color:#4f46e5;text-decoration:underline}

/* 活动时间线 */
.timeline{list-style:none;padding:0}
.timeline-item{
  display:flex;gap:12px;padding:10px 0;border-bottom:1px solid rgba(99,102,241,.06);
  transition:background .2s;
}
.timeline-item:hover{background:rgba(99,102,241,.03);border-radius:8px}
.timeline-item:last-child{border-bottom:none}
.timeline-item .t-icon{
  width:32px;height:32px;border-radius:8px;
  background:linear-gradient(135deg,rgba(99,102,241,.1),rgba(139,92,246,.1));
  display:flex;align-items:center;justify-content:center;font-size:14px;flex-shrink:0;
}
.timeline-item .t-text{font-size:13px;color:#334155;line-height:1.4}
.timeline-item .t-time{font-size:11px;color:#94a3b8;margin-top:2px}

@media(max-width:1024px){.chart-grid,.tables-grid{grid-template-columns:1fr}}
@media(max-width:768px){.stats-grid{grid-template-columns:repeat(2,1fr)}.quick-actions{grid-template-columns:repeat(2,1fr)}}
@media(max-width:480px){.stats-grid{grid-template-columns:1fr}}
</style>

<div class="dashboard" style="overflow:hidden;">
  <!-- 欢迎横幅 -->
  <div style="background:linear-gradient(135deg,#6366f1,#8b5cf6,#a855f7);border-radius:16px;padding:28px 32px;margin-bottom:24px;color:#fff;position:relative;overflow:hidden;animation:fadeSlideIn .4s ease">
    <div style="position:absolute;right:-20px;top:-20px;font-size:120px;opacity:0.1;">📊</div>
    <h2 style="font-size:22px;font-weight:800;margin-bottom:6px;">👋 欢迎回来，<?php echo htmlspecialchars($_SESSION['admin_realname'] ?: $_SESSION['admin_username']); ?></h2>
    <p style="font-size:14px;opacity:.85;">今天是 <?php echo date('Y年m月d日 l'); ?>，系统运行正常</p>
  </div>

  <!-- 核心统计卡片 -->
  <div class="stats-grid">
    <div class="stat-card blue">
      <div class="stat-icon">👥</div>
      <div class="stat-label">总用户数</div>
      <div class="stat-value"><?php echo number_format($totalUsers); ?></div>
      <div class="stat-sub">今日新增 +<?php echo $todayNewUsers; ?></div>
    </div>
    <div class="stat-card green">
      <div class="stat-icon">📄</div>
      <div class="stat-label">总资源数</div>
      <div class="stat-value"><?php echo number_format($totalResources); ?></div>
      <div class="stat-sub">待审核 <?php echo $pendingResources; ?> 个</div>
    </div>
    <div class="stat-card orange">
      <div class="stat-icon">⬇️</div>
      <div class="stat-label">总下载量</div>
      <div class="stat-value"><?php echo number_format($totalDownloads); ?></div>
    </div>
    <div class="stat-card purple">
      <div class="stat-icon">💰</div>
      <div class="stat-label">总收入</div>
      <div class="stat-value">¥<?php echo number_format($totalRevenue, 2); ?></div>
      <div class="stat-sub">今日 ¥<?php echo number_format($todayOrders['rev'] ?? 0, 2); ?> · 本月 ¥<?php echo number_format($monthOrders['rev'] ?? 0, 2); ?></div>
    </div>
    <div class="stat-card red">
      <div class="stat-icon">📝</div>
      <div class="stat-label">待处理反馈</div>
      <div class="stat-value"><?php echo $pendingFeedback; ?></div>
    </div>
    <div class="stat-card pink">
      <div class="stat-icon">📦</div>
      <div class="stat-label">本月订单</div>
      <div class="stat-value"><?php echo $monthOrders['cnt'] ?? 0; ?></div>
      <div class="stat-sub">今日 <?php echo $todayOrders['cnt'] ?? 0; ?> 单</div>
    </div>
  </div>

  <!-- 快捷操作 -->
  <div class="quick-actions">
    <a href="resources.php" class="quick-action"><div class="qa-icon">📄</div><div class="qa-label">资源管理</div></a>
    <a href="resources.php?status=pending" class="quick-action"><div class="qa-icon">✅</div><div class="qa-label">审核资源</div></a>
    <a href="categories.php" class="quick-action"><div class="qa-icon">📂</div><div class="qa-label">分类管理</div></a>
    <a href="orders.php" class="quick-action"><div class="qa-icon">💰</div><div class="qa-label">订单管理</div></a>
    <a href="users.php" class="quick-action"><div class="qa-icon">👥</div><div class="qa-label">用户管理</div></a>
    <a href="feedback.php" class="quick-action"><div class="qa-icon">📝</div><div class="qa-label">反馈处理</div></a>
    <a href="banners.php" class="quick-action"><div class="qa-icon">🖼️</div><div class="qa-label">轮播图管理</div></a>
    <a href="settings.php" class="quick-action"><div class="qa-icon">⚙️</div><div class="qa-label">系统设置</div></a>
  </div>

  <!-- 图表区域 -->
  <div class="chart-grid">
    <div class="chart-card">
      <h3><span>📈</span> 近7天订单趋势</h3>
      <div style="position:relative;height:220px;"><canvas id="orderChart"></canvas></div>
    </div>
    <div class="chart-card">
      <h3><span>📂</span> 分类资源分布</h3>
      <div style="position:relative;height:220px;"><canvas id="categoryChart"></canvas></div>
    </div>
  </div>

  <!-- 表格区域 -->
  <div class="tables-grid">
    <div class="table-card">
      <h3><span>💰</span> 最近订单 <a href="orders.php" class="view-all">查看全部 →</a></h3>
      <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;">
          <thead><tr style="border-bottom:2px solid #e2e8f0;">
            <th style="text-align:left;padding:10px 8px;font-size:12px;color:#64748b;font-weight:600;">订单号</th>
            <th style="text-align:left;padding:10px 8px;font-size:12px;color:#64748b;font-weight:600;">用户</th>
            <th style="text-align:right;padding:10px 8px;font-size:12px;color:#64748b;font-weight:600;">金额</th>
            <th style="text-align:center;padding:10px 8px;font-size:12px;color:#64748b;font-weight:600;">状态</th>
          </tr></thead>
          <tbody>
          <?php foreach ($recentOrders as $o): ?>
          <tr style="border-bottom:1px solid #f1f5f9;">
            <td style="padding:10px 8px;font-size:13px;font-family:monospace;"><?php echo htmlspecialchars($o['order_no']); ?></td>
            <td style="padding:10px 8px;font-size:13px;"><?php echo htmlspecialchars($o['nickname'] ?? '—'); ?></td>
            <td style="padding:10px 8px;font-size:13px;text-align:right;font-weight:600;">¥<?php echo number_format($o['pay_amount'], 2); ?></td>
            <td style="padding:10px 8px;text-align:center;"><span class="badge <?php echo $orderBadgeMap[$o['status']] ?? 'badge-default'; ?>"><?php echo $orderStatusMap[$o['status']] ?? $o['status']; ?></span></td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($recentOrders)): ?>
          <tr><td colspan="4" style="text-align:center;padding:24px;color:#94a3b8;">暂无订单</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="table-card">
      <h3><span>👥</span> 最近用户 <a href="users.php" class="view-all">查看全部 →</a></h3>
      <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;">
          <thead><tr style="border-bottom:2px solid #e2e8f0;">
            <th style="text-align:left;padding:10px 8px;font-size:12px;color:#64748b;font-weight:600;">昵称</th>
            <th style="text-align:center;padding:10px 8px;font-size:12px;color:#64748b;font-weight:600;">VIP</th>
            <th style="text-align:right;padding:10px 8px;font-size:12px;color:#64748b;font-weight:600;">下载</th>
            <th style="text-align:center;padding:10px 8px;font-size:12px;color:#64748b;font-weight:600;">注册时间</th>
          </tr></thead>
          <tbody>
          <?php foreach ($recentUsers as $u): ?>
          <tr style="border-bottom:1px solid #f1f5f9;">
            <td style="padding:10px 8px;font-size:13px;display:flex;align-items:center;gap:8px;">
              <div style="width:28px;height:28px;border-radius:8px;background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;flex-shrink:0;"><?php echo mb_substr($u['nickname'] ?? '?', 0, 1); ?></div>
              <?php echo htmlspecialchars($u['nickname'] ?? '未知'); ?>
            </td>
            <td style="padding:10px 8px;text-align:center;"><?php echo $u['vip_level'] > 0 ? '<span class="badge badge-warning">'.$vipLabels[$u['vip_level']].'</span>' : '<span style="color:#94a3b8;font-size:12px;">普通</span>'; ?></td>
            <td style="padding:10px 8px;font-size:13px;text-align:right;"><?php echo $u['total_downloads'] ?? 0; ?></td>
            <td style="padding:10px 8px;font-size:12px;color:#64748b;text-align:center;"><?php echo date('m-d H:i', strtotime($u['created_at'])); ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($recentUsers)): ?>
          <tr><td colspan="4" style="text-align:center;padding:24px;color:#94a3b8;">暂无用户</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- 最近活动 -->
  <div class="chart-card">
    <h3><span>🕐</span> 最近活动</h3>
    <ul class="timeline">
      <?php foreach ($activities as $act): ?>
      <li class="timeline-item">
        <div class="t-icon"><?php echo $act['icon']; ?></div>
        <div>
          <div class="t-text"><?php echo htmlspecialchars($act['text']); ?></div>
          <div class="t-time"><?php echo date('m-d H:i', strtotime($act['time'])); ?></div>
        </div>
      </li>
      <?php endforeach; ?>
      <?php if (empty($activities)): ?>
      <li class="timeline-item"><div class="t-icon">ℹ️</div><div class="t-text" style="color:#94a3b8;">暂无活动记录</div></li>
      <?php endif; ?>
    </ul>
  </div>
</div>

<script>
// ======== 订单趋势图 ========
new Chart(document.getElementById('orderChart').getContext('2d'), {
  type: 'line',
  data: {
    labels: <?php echo json_encode($chartDays); ?>,
    datasets: [{
      label: '订单数',
      data: <?php echo json_encode($chartCounts); ?>,
      borderColor: '#8b5cf6',
      backgroundColor: 'rgba(108,99,255,0.1)',
      fill: true, tension: 0.4, borderWidth: 2.5,
      pointBackgroundColor: '#8b5cf6', pointRadius: 5, pointHoverRadius: 7,
      yAxisID: 'y',
    }, {
      label: '收入(元)',
      data: <?php echo json_encode($chartRevs); ?>,
      borderColor: '#10b981',
      backgroundColor: 'rgba(16,185,129,0.08)',
      fill: true, tension: 0.4, borderWidth: 2.5,
      pointBackgroundColor: '#10b981', pointRadius: 5, pointHoverRadius: 7,
      yAxisID: 'y1',
    }]
  },
  options: {
    responsive: true, maintainAspectRatio: false,
    interaction: { mode: 'index', intersect: false },
    plugins: {
      legend: { position: 'bottom', labels: { usePointStyle: true, padding: 20, font: { size: 12 } } },
      tooltip: {
        backgroundColor: 'rgba(15,23,42,0.9)', padding: 12, cornerRadius: 8,
        callbacks: { label: function(ctx) { return ctx.dataset.label === '收入(元)' ? '收入: ¥' + ctx.parsed.y.toFixed(2) : '订单: ' + ctx.parsed.y + ' 单'; } }
      }
    },
    scales: {
      x: { grid: { display: false }, ticks: { font: { size: 11 } } },
      y: { position: 'left', beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { font: { size: 11 }, stepSize: 1 } },
      y1: { position: 'right', beginAtZero: true, grid: { display: false }, ticks: { font: { size: 11 }, callback: function(v) { return '¥' + v; } } }
    }
  }
});

// ======== 分类资源分布图 ========
var catColors = ['#8b5cf6','#3b82f6','#10b981','#f59e0b','#ef4444','#ec4899','#8b5cf6','#14b8a6'];
new Chart(document.getElementById('categoryChart').getContext('2d'), {
  type: 'doughnut',
  data: {
    labels: <?php echo json_encode(array_column($catStats, 'name')); ?>,
    datasets: [{ data: <?php echo json_encode(array_map('intval', array_column($catStats, 'cnt'))); ?>, backgroundColor: catColors.slice(0, <?php echo count($catStats); ?>), borderWidth: 0, hoverOffset: 6 }]
  },
  options: {
    responsive: true, maintainAspectRatio: false, cutout: '55%',
    plugins: {
      legend: { position: 'bottom', labels: { usePointStyle: true, padding: 12, font: { size: 11 } } },
      tooltip: { backgroundColor: 'rgba(15,23,42,0.9)', padding: 10, cornerRadius: 8, callbacks: { label: function(ctx) { return ctx.label + ': ' + ctx.parsed + ' 个资源'; } } }
    }
  }
});
</script>

<?php include __DIR__ . '/footer.php'; ?>
