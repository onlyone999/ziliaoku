<?php
/**
 * 管理后台 - 积分管理
 * 总览/用户积分/手动调整/积分记录
 */
session_start();
if (empty($_SESSION['admin_id'])) { header('Location: login.php'); exit; }

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/helper.php';
require_once __DIR__ . '/../core/AdminLog.php';
$db = Database::getInstance();
$csrfToken = $_SESSION['csrf_token'] ?? '';

$msg = '';
$msgType = '';

// ── 处理操作 ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $token  = $_POST['_token'] ?? '';
    if ($token !== $csrfToken) { $msg = 'CSRF令牌无效'; $msgType = 'error'; }
    else {
        // 手动调整积分
        if ($action === 'adjust') {
            $userId   = (int)($_POST['user_id'] ?? 0);
            $points   = (int)($_POST['points'] ?? 0);
            $reason   = trim($_POST['reason'] ?? '');
            $adjType  = $_POST['adj_type'] ?? 'earn';

            if ($userId <= 0) { $msg = '用户ID无效'; $msgType = 'error'; }
            elseif ($points <= 0) { $msg = '积分数必须大于0'; $msgType = 'error'; }
            elseif ($reason === '') { $msg = '请填写调整原因'; $msgType = 'error'; }
            elseif (!in_array($adjType, ['earn', 'spend'])) { $msg = '类型无效'; $msgType = 'error'; }
            else {
                $user = $db->fetch('SELECT id, nickname, points FROM users WHERE id = :id', [':id' => $userId]);
                if (!$user) { $msg = '用户不存在'; $msgType = 'error'; }
                else {
                    $oldPoints = (int)$user['points'];
                    if ($adjType === 'earn') {
                        $newBalance = $oldPoints + $points;
                        $desc = '管理员手动增加：' . $reason;
                    } else {
                        if ($oldPoints < $points) {
                            $msg = '用户积分不足，当前' . $oldPoints;
                            $msgType = 'error';
                        } else {
                            $newBalance = $oldPoints - $points;
                            $desc = '管理员手动扣除：' . $reason;
                        }
                    }
                    if ($msg === '') {
                        $db->update('users', ['points' => $newBalance], 'id = :id', [':id' => $userId]);
                        $db->insert('user_points_log', [
                            'user_id'       => $userId,
                            'points'        => $points,
                            'type'          => $adjType,
                            'description'   => $desc,
                            'balance_after' => $newBalance,
                            'created_at'    => date('Y-m-d H:i:s'),
                        ]);
                        AdminLog::log('update', 'points', $userId, $desc . ' (' . ($adjType === 'earn' ? '+' : '-') . $points . ')');
                        $msg = '调整成功：' . ($user['nickname'] ?: '用户' . $userId) . ' ' . ($adjType === 'earn' ? '+' : '-') . $points . ' 积分';
                        $msgType = 'success';
                    }
                }
            }
        }
        // 批量发放
        elseif ($action === 'batch') {
            $points   = (int)($_POST['batch_points'] ?? 0);
            $reason   = trim($_POST['batch_reason'] ?? '');
            $target   = $_POST['batch_target'] ?? 'all';

            if ($points <= 0) { $msg = '积分数必须大于0'; $msgType = 'error'; }
            elseif ($reason === '') { $msg = '请填写发放原因'; $msgType = 'error'; }
            else {
                $where = 'status = 1';
                if ($target === 'vip') $where .= ' AND vip_level > 0 AND vip_expire_at > NOW()';
                elseif ($target === 'new') $where .= ' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)';

                $users = $db->fetchAll("SELECT id, points FROM users WHERE {$where}");
                $count = 0;
                foreach ($users as $u) {
                    $newBalance = (int)$u['points'] + $points;
                    $db->update('users', ['points' => $newBalance], 'id = :id', [':id' => $u['id']]);
                    $db->insert('user_points_log', [
                        'user_id'       => $u['id'],
                        'points'        => $points,
                        'type'          => 'earn',
                        'description'   => '管理员批量发放：' . $reason,
                        'balance_after' => $newBalance,
                        'created_at'    => date('Y-m-d H:i:s'),
                    ]);
                    $count++;
                }
                AdminLog::log('batch', 'points', 0, "批量发放 {$points} 积分给 {$count} 名用户：{$reason}");
                $msg = "已向 {$count} 名用户发放 {$points} 积分";
                $msgType = 'success';
            }
        }
    }
}

// ── 统计数据 ──
$totalUsers     = $db->fetch("SELECT COUNT(*) AS cnt FROM users WHERE status = 1")['cnt'] ?? 0;
$totalPoints    = $db->fetch("SELECT COALESCE(SUM(points),0) AS s FROM users WHERE status = 1")['s'] ?? 0;
$todayEarn      = $db->fetch("SELECT COALESCE(SUM(points),0) AS s FROM user_points_log WHERE type='earn' AND DATE(created_at)=CURDATE()")['s'] ?? 0;
$todaySpend     = $db->fetch("SELECT COALESCE(SUM(points),0) AS s FROM user_points_log WHERE type='spend' AND DATE(created_at)=CURDATE()")['s'] ?? 0;
$totalEarn      = $db->fetch("SELECT COALESCE(SUM(points),0) AS s FROM user_points_log WHERE type='earn'")['s'] ?? 0;
$totalSpend     = $db->fetch("SELECT COALESCE(SUM(points),0) AS s FROM user_points_log WHERE type='spend'")['s'] ?? 0;
$todaySignins   = $db->fetch("SELECT COUNT(*) AS cnt FROM user_points_log WHERE description LIKE '%签到%' AND DATE(created_at)=CURDATE()")['cnt'] ?? 0;
$topUsers       = $db->fetchAll("SELECT id, nickname, avatar_url, points FROM users WHERE status = 1 ORDER BY points DESC LIMIT 10");

// ── 用户列表 ──
$search     = trim($_GET['search'] ?? '');
$filterType = $_GET['log_type'] ?? '';
$page       = max(1, (int)($_GET['page'] ?? 1));
$perPage    = getPerPage(20);
$offset     = ($page - 1) * $perPage;

// 用户积分列表
$uWhere = 'u.status = 1';
$uParams = [];
if ($search !== '') {
    $uWhere .= ' AND (u.nickname LIKE :s OR u.id = :sid)';
    $uParams[':s'] = '%' . $search . '%';
    $uParams[':sid'] = (int)$search;
}
$totalRows = (int)($db->fetch("SELECT COUNT(*) AS cnt FROM users u WHERE {$uWhere}", $uParams)['cnt'] ?? 0);
$totalPages = max(1, ceil($totalRows / $perPage));
$users = $db->fetchAll(
    "SELECT u.id, u.nickname, u.avatar_url, u.points, u.vip_level, u.vip_expire_at, u.total_downloads, u.created_at
     FROM users u WHERE {$uWhere} ORDER BY u.points DESC LIMIT {$perPage} OFFSET {$offset}",
    $uParams
);

// ── 积分日志 ──
$logPage = max(1, (int)($_GET['log_page'] ?? 1));
$logPerPage = 30;
$logOffset = ($logPage - 1) * $logPerPage;
$lWhere = '1=1';
$lParams = [];
if ($search !== '') {
    $lWhere .= ' AND (pl.description LIKE :ls OR pl.user_id = :luid)';
    $lParams[':ls'] = '%' . $search . '%';
    $lParams[':luid'] = (int)$search;
}
if ($filterType !== '' && in_array($filterType, ['earn', 'spend'])) {
    $lWhere .= ' AND pl.type = :lt';
    $lParams[':lt'] = $filterType;
}
$logTotal = (int)($db->fetch("SELECT COUNT(*) AS cnt FROM user_points_log pl WHERE {$lWhere}", $lParams)['cnt'] ?? 0);
$logTotalPages = max(1, ceil($logTotal / $logPerPage));
$logs = $db->fetchAll(
    "SELECT pl.*, u.nickname FROM user_points_log pl LEFT JOIN users u ON pl.user_id = u.id
     WHERE {$lWhere} ORDER BY pl.id DESC LIMIT {$logPerPage} OFFSET {$logOffset}",
    $lParams
);

$tab = $_GET['tab'] ?? 'dashboard';

include __DIR__ . '/header.php';
?>

<?php if ($msg): ?>
<div class="alert auto-dismiss" style="padding:12px 16px;border-radius:var(--radius);margin-bottom:20px;font-size:14px;
  <?php echo $msgType==='success' ? 'background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0' : 'background:#fef2f2;color:#b91c1c;border:1px solid #fecaca'; ?>">
  <?php echo htmlspecialchars($msg); ?>
</div>
<?php endif; ?>

<!-- 标签页导航 -->
<div class="tabs" style="display:flex;gap:8px;margin-bottom:20px;">
  <a href="?tab=dashboard" class="btn <?php echo $tab==='dashboard'?'btn-primary':'btn-outline'; ?> btn-sm">📊 数据总览</a>
  <a href="?tab=users" class="btn <?php echo $tab==='users'?'btn-primary':'btn-outline'; ?> btn-sm">👥 用户积分</a>
  <a href="?tab=logs" class="btn <?php echo $tab==='logs'?'btn-primary':'btn-outline'; ?> btn-sm">📋 积分记录</a>
  <a href="?tab=adjust" class="btn <?php echo $tab==='adjust'?'btn-primary':'btn-outline'; ?> btn-sm">✏️ 手动调整</a>
  <a href="?tab=batch" class="btn <?php echo $tab==='batch'?'btn-primary':'btn-outline'; ?> btn-sm">🎁 批量发放</a>
  <a href="settings.php" class="btn btn-outline btn-sm">⚙️ 积分设置</a>
</div>

<?php if ($tab === 'dashboard'): ?>
<!-- ========== 数据总览 ========== -->
<div class="stat-cards" style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px;">
  <div class="card" style="padding:20px;text-align:center;">
    <div style="font-size:28px;font-weight:800;color:#2ed573;"><?php echo number_format($totalPoints); ?></div>
    <div style="font-size:13px;color:#888;margin-top:4px;">平台积分总量</div>
  </div>
  <div class="card" style="padding:20px;text-align:center;">
    <div style="font-size:28px;font-weight:800;color:#3b82f6;"><?php echo number_format($todayEarn); ?></div>
    <div style="font-size:13px;color:#888;margin-top:4px;">今日发放</div>
  </div>
  <div class="card" style="padding:20px;text-align:center;">
    <div style="font-size:28px;font-weight:800;color:#f59e0b;"><?php echo number_format($todaySpend); ?></div>
    <div style="font-size:13px;color:#888;margin-top:4px;">今日消耗</div>
  </div>
  <div class="card" style="padding:20px;text-align:center;">
    <div style="font-size:28px;font-weight:800;color:#8b5cf6;"><?php echo number_format($todaySignins); ?></div>
    <div style="font-size:13px;color:#888;margin-top:4px;">今日签到人数</div>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
  <!-- 积分流水统计 -->
  <div class="card" style="padding:20px;">
    <h4 style="margin-bottom:16px;">📈 积分流水统计</h4>
    <table style="width:100%;">
      <tr><td style="padding:8px 0;color:#888;">累计发放</td><td style="text-align:right;font-weight:700;color:#22c55e;">+<?php echo number_format($totalEarn); ?></td></tr>
      <tr><td style="padding:8px 0;color:#888;">累计消耗</td><td style="text-align:right;font-weight:700;color:#ef4444;">-<?php echo number_format($totalSpend); ?></td></tr>
      <tr><td style="padding:8px 0;color:#888;">净流入</td><td style="text-align:right;font-weight:700;color:#3b82f6;"><?php echo number_format($totalEarn - $totalSpend); ?></td></tr>
      <tr><td style="padding:8px 0;color:#888;">人均积分</td><td style="text-align:right;font-weight:700;"><?php echo $totalUsers > 0 ? round($totalPoints / $totalUsers) : 0; ?></td></tr>
    </table>
  </div>

  <!-- 积分排行榜 -->
  <div class="card" style="padding:20px;">
    <h4 style="margin-bottom:16px;">🏆 积分排行榜 TOP10</h4>
    <table style="width:100%;">
      <?php foreach ($topUsers as $i => $u): ?>
      <tr>
        <td style="padding:6px 0;width:30px;font-weight:700;color:<?php echo $i < 3 ? '#f59e0b' : '#888'; ?>">
          <?php echo $i < 3 ? ['🥇','🥈','🥉'][$i] : ($i+1); ?>
        </td>
        <td style="padding:6px 0;"><?php echo htmlspecialchars($u['nickname'] ?: '用户' . $u['id']); ?></td>
        <td style="padding:6px 0;text-align:right;font-weight:700;color:#2ed573;"><?php echo number_format($u['points']); ?></td>
      </tr>
      <?php endforeach; ?>
    </table>
  </div>
</div>

<?php elseif ($tab === 'users'): ?>
<!-- ========== 用户积分列表 ========== -->
<div class="toolbar">
  <div class="toolbar-left">
    <form method="GET" class="d-flex align-center gap-10">
      <input type="hidden" name="tab" value="users">
      <div class="search-box">
        <input type="text" name="search" placeholder="搜索用户ID/昵称..." value="<?php echo htmlspecialchars($search); ?>">
      </div>
      <button type="submit" class="btn btn-primary btn-sm">搜索</button>
      <?php if ($search): ?>
      <a href="?tab=users" class="btn btn-outline btn-sm">清除</a>
      <?php endif; ?>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-body no-padding">
    <div class="table-wrapper">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>用户</th>
            <th>积分余额</th>
            <th>下载次数</th>
            <th>VIP状态</th>
            <th>注册时间</th>
            <th>操作</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $u): ?>
          <tr>
            <td><?php echo $u['id']; ?></td>
            <td>
              <div style="display:flex;align-items:center;gap:8px;">
                <?php if ($u['avatar_url']): ?>
                <img src="<?php echo htmlspecialchars($u['avatar_url']); ?>" style="width:32px;height:32px;border-radius:50%;object-fit:cover;" onerror="this.style.display='none'">
                <?php endif; ?>
                <span><?php echo htmlspecialchars($u['nickname'] ?: '未设置'); ?></span>
              </div>
            </td>
            <td><strong style="color:#2ed573;"><?php echo number_format($u['points']); ?></strong></td>
            <td><?php echo $u['total_downloads']; ?></td>
            <td>
              <?php if ($u['vip_level'] > 0 && $u['vip_expire_at'] > date('Y-m-d H:i:s')): ?>
              <span class="badge badge-success">VIP</span>
              <?php else: ?>
              <span class="text-muted">普通</span>
              <?php endif; ?>
            </td>
            <td><?php echo substr($u['created_at'], 0, 10); ?></td>
            <td>
              <a href="?tab=adjust&uid=<?php echo $u['id']; ?>&name=<?php echo urlencode($u['nickname']); ?>" class="btn btn-outline btn-sm">调整积分</a>
              <a href="?tab=logs&search=<?php echo $u['id']; ?>" class="btn btn-outline btn-sm">查看记录</a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <!-- 分页 -->
    <div class="pagination">
      <span class="page-info">共 <?php echo $totalRows; ?> 用户，第 <?php echo $page; ?>/<?php echo $totalPages; ?> 页</span>
      <div class="page-links">
        <?php if ($page > 1): ?>
        <a href="?tab=users&search=<?php echo urlencode($search); ?>&page=<?php echo $page-1; ?>">&laquo; 上一页</a>
        <?php else: ?><span class="disabled">&laquo; 上一页</span><?php endif; ?>
        <?php $s=max(1,$page-3);$e=min($totalPages,$page+3); for($p=$s;$p<=$e;$p++): ?>
        <a href="?tab=users&search=<?php echo urlencode($search); ?>&page=<?php echo $p; ?>" class="<?php echo $p===$page?'active':''; ?>"><?php echo $p; ?></a>
        <?php endfor; ?>
        <?php if ($page < $totalPages): ?>
        <a href="?tab=users&search=<?php echo urlencode($search); ?>&page=<?php echo $page+1; ?>">下一页 &raquo;</a>
        <?php else: ?><span class="disabled">下一页 &raquo;</span><?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php elseif ($tab === 'logs'): ?>
<!-- ========== 积分记录 ========== -->
<div class="toolbar">
  <div class="toolbar-left">
    <form method="GET" class="d-flex align-center gap-10">
      <input type="hidden" name="tab" value="logs">
      <div class="search-box">
        <input type="text" name="search" placeholder="搜索用户ID/描述..." value="<?php echo htmlspecialchars($search); ?>">
      </div>
      <select name="log_type" class="filter-select">
        <option value="">全部类型</option>
        <option value="earn" <?php echo $filterType==='earn'?'selected':''; ?>>收入</option>
        <option value="spend" <?php echo $filterType==='spend'?'selected':''; ?>>支出</option>
      </select>
      <button type="submit" class="btn btn-primary btn-sm">筛选</button>
      <?php if ($search || $filterType): ?>
      <a href="?tab=logs" class="btn btn-outline btn-sm">清除</a>
      <?php endif; ?>
    </form>
  </div>
  <div class="toolbar-right">
    <span style="font-size:13px;color:#888;">共 <?php echo number_format($logTotal); ?> 条记录</span>
  </div>
</div>

<div class="card">
  <div class="card-body no-padding">
    <div class="table-wrapper">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>用户</th>
            <th>类型</th>
            <th>积分</th>
            <th>余额</th>
            <th>描述</th>
            <th>时间</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($logs as $l): ?>
          <tr>
            <td><?php echo $l['id']; ?></td>
            <td><?php echo htmlspecialchars($l['nickname'] ?: '用户' . $l['user_id']); ?> (<?php echo $l['user_id']; ?>)</td>
            <td>
              <span class="badge <?php echo $l['type']==='earn' ? 'badge-success' : 'badge-danger'; ?>">
                <?php echo $l['type']==='earn' ? '收入' : '支出'; ?>
              </span>
            </td>
            <td style="font-weight:700;color:<?php echo $l['type']==='earn' ? '#22c55e' : '#ef4444'; ?>">
              <?php echo ($l['type']==='earn' ? '+' : '-') . $l['points']; ?>
            </td>
            <td><?php echo number_format($l['balance_after']); ?></td>
            <td style="max-width:300px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?php echo htmlspecialchars($l['description']); ?></td>
            <td><?php echo substr($l['created_at'], 0, 16); ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="pagination">
      <span class="page-info">第 <?php echo $logPage; ?>/<?php echo $logTotalPages; ?> 页</span>
      <div class="page-links">
        <?php if ($logPage > 1): ?>
        <a href="?tab=logs&search=<?php echo urlencode($search); ?>&log_type=<?php echo $filterType; ?>&log_page=<?php echo $logPage-1; ?>">&laquo; 上一页</a>
        <?php else: ?><span class="disabled">&laquo; 上一页</span><?php endif; ?>
        <?php $ls=max(1,$logPage-3);$le=min($logTotalPages,$logPage+3); for($p=$ls;$p<=$le;$p++): ?>
        <a href="?tab=logs&search=<?php echo urlencode($search); ?>&log_type=<?php echo $filterType; ?>&log_page=<?php echo $p; ?>" class="<?php echo $p===$logPage?'active':''; ?>"><?php echo $p; ?></a>
        <?php endfor; ?>
        <?php if ($logPage < $logTotalPages): ?>
        <a href="?tab=logs&search=<?php echo urlencode($search); ?>&log_type=<?php echo $filterType; ?>&log_page=<?php echo $logPage+1; ?>">下一页 &raquo;</a>
        <?php else: ?><span class="disabled">下一页 &raquo;</span><?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php elseif ($tab === 'adjust'): ?>
<!-- ========== 手动调整 ========== -->
<div class="card mb-20">
  <div class="card-header"><h3>✏️ 手动调整用户积分</h3></div>
  <div class="card-body">
    <form method="POST">
      <input type="hidden" name="_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
      <input type="hidden" name="action" value="adjust">
      <div class="form-row">
        <div class="form-group" style="flex:1;">
          <label>用户ID <span class="required">*</span></label>
          <input type="number" name="user_id" class="form-control" required min="1"
                 value="<?php echo htmlspecialchars($_GET['uid'] ?? ''); ?>"
                 placeholder="输入用户ID">
        </div>
        <div class="form-group" style="flex:1;">
          <label>用户名</label>
          <input type="text" class="form-control" disabled
                 value="<?php echo htmlspecialchars($_GET['name'] ?? ''); ?>">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group" style="flex:1;">
          <label>操作类型 <span class="required">*</span></label>
          <select name="adj_type" class="form-control">
            <option value="earn">➕ 增加积分</option>
            <option value="spend">➖ 扣除积分</option>
          </select>
        </div>
        <div class="form-group" style="flex:1;">
          <label>积分数量 <span class="required">*</span></label>
          <input type="number" name="points" class="form-control" required min="1" placeholder="输入积分数量">
        </div>
      </div>
      <div class="form-group">
        <label>调整原因 <span class="required">*</span></label>
        <input type="text" name="reason" class="form-control" required placeholder="如：活动奖励、违规扣除、补偿发放等">
      </div>
      <div class="d-flex gap-10">
        <button type="submit" class="btn btn-primary btn-lg">确认调整</button>
      </div>
    </form>
  </div>
</div>

<?php elseif ($tab === 'batch'): ?>
<!-- ========== 批量发放 ========== -->
<div class="card mb-20">
  <div class="card-header"><h3>🎁 批量发放积分</h3></div>
  <div class="card-body">
    <form method="POST" onsubmit="return confirm('确认批量发放积分？此操作不可撤销。')">
      <input type="hidden" name="_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
      <input type="hidden" name="action" value="batch">
      <div class="form-row">
        <div class="form-group" style="flex:1;">
          <label>发放对象 <span class="required">*</span></label>
          <select name="batch_target" class="form-control">
            <option value="all">全部用户</option>
            <option value="vip">仅VIP用户</option>
            <option value="new">近7天新用户</option>
          </select>
        </div>
        <div class="form-group" style="flex:1;">
          <label>每人积分 <span class="required">*</span></label>
          <input type="number" name="batch_points" class="form-control" required min="1" placeholder="输入每人发放积分">
        </div>
      </div>
      <div class="form-group">
        <label>发放原因 <span class="required">*</span></label>
        <input type="text" name="batch_reason" class="form-control" required placeholder="如：节日活动奖励、系统补偿等">
      </div>
      <div class="d-flex gap-10">
        <button type="submit" class="btn btn-primary btn-lg">确认批量发放</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/footer.php'; ?>
