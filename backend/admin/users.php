<?php
/**
 * 管理后台 - 用户管理
 * 列表、搜索、查看详情、设置VIP、调整积分、启用/禁用
 */
session_start();
if (empty($_SESSION['admin_id'])) { header('Location: login.php'); exit; }

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/AdminLog.php';
$db = Database::getInstance();
$csrfToken = $_SESSION['csrf_token'] ?? '';

$msg = '';
$msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $token  = $_POST['_token'] ?? '';
    if ($token !== $csrfToken) { $msg = 'CSRF令牌无效'; $msgType = 'error'; }
    else {
        $uid = (int)($_POST['id'] ?? 0);

        // 编辑用户资料
        if ($action === 'edit' && $uid > 0) {
            $data = [];
            if (isset($_POST['nickname'])) {
                $nickname = trim($_POST['nickname']);
                if (mb_strlen($nickname) >= 2 && mb_strlen($nickname) <= 32) {
                    $data['nickname'] = $nickname;
                }
            }
            if (isset($_POST['phone'])) {
                $phone = trim($_POST['phone']);
                if ($phone === '' || preg_match('/^1[3-9]\d{9}$/', $phone)) {
                    $data['phone'] = $phone;
                }
            }
            if (isset($_POST['gender'])) {
                $gender = (int)$_POST['gender'];
                if ($gender >= 0 && $gender <= 2) {
                    $data['gender'] = $gender;
                }
            }
            // 处理头像上传
            if (!empty($_FILES['avatar']['name']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                $allowed = ['jpg','jpeg','png','gif','webp'];
                $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($_FILES['avatar']['tmp_name']);
                $allowedMimes = ['image/jpeg','image/png','image/gif','image/webp'];
                if (in_array($ext, $allowed) && in_array($mime, $allowedMimes)) {
                    $dir = __DIR__ . '/../uploads/avatars/';
                    if (!is_dir($dir)) mkdir($dir, 0755, true);
                    $fname = date('His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                    move_uploaded_file($_FILES['avatar']['tmp_name'], $dir . $fname);
                    $data['avatar_url'] = '/uploads/avatars/' . $fname;
                }
            }
            if (!empty($data)) {
                $db->update('users', $data, 'id = :id', [':id' => $uid]);
                $msg = '用户资料已更新'; $msgType = 'success';
            }
        }
        // 删除用户
        elseif ($action === 'delete' && $uid > 0) {
            $db->delete('user_points_log', 'user_id = :id', [':id' => $uid]);
            $db->delete('favorites', 'user_id = :id', [':id' => $uid]);
            $db->delete('downloads', 'user_id = :id', [':id' => $uid]);
            $db->delete('feedback', 'user_id = :id', [':id' => $uid]);
            $db->delete('comments', 'user_id = :id', [':id' => $uid]);
            $db->delete('orders', 'user_id = :id', [':id' => $uid]);
            $db->delete('users', 'id = :id', [':id' => $uid]);
            AdminLog::log('delete', 'user', $uid, '删除用户 #' . $uid);
            $msg = '用户及其所有关联数据已删除'; $msgType = 'success';
        }
        // 切换状态
        elseif ($action === 'toggle_status' && $uid > 0) {
            $cur = $db->fetch('SELECT status FROM users WHERE id=:id', [':id' => $uid]);
            $nv = ($cur['status'] ?? 1) ? 0 : 1;
            $db->update('users', ['status' => $nv], 'id = :id', [':id' => $uid]);
            AdminLog::log('toggle_status', 'user', $uid, ($nv ? '启用' : '禁用') . '用户 #' . $uid);
            $msg = $nv ? '用户已启用' : '用户已禁用'; $msgType = 'success';
        }
        // 设置VIP
        elseif ($action === 'set_vip' && $uid > 0) {
            $vipLevel = (int)($_POST['vip_level'] ?? 0);
            $expireAt = trim($_POST['vip_expire_at'] ?? '');
            $data = ['vip_level' => $vipLevel];
            if ($expireAt !== '') {
                $data['vip_expire_at'] = $expireAt;
            } elseif ($vipLevel === 0) {
                $data['vip_expire_at'] = null;
            }
            $db->update('users', $data, 'id = :id', [':id' => $uid]);
            AdminLog::log('set_vip', 'user', $uid, '设置用户 #' . $uid . ' VIP等级为 ' . $vipLevel . ($expireAt ? '，到期 ' . $expireAt : ''));
            $msg = 'VIP已更新'; $msgType = 'success';
        }
        // 调整积分
        elseif ($action === 'adjust_points' && $uid > 0) {
            $pointsDelta = (int)($_POST['points_delta'] ?? 0);
            $desc = trim($_POST['points_desc'] ?? '管理员调整');
            if ($pointsDelta !== 0) {
                $user = $db->fetch('SELECT points FROM users WHERE id=:id', [':id' => $uid]);
                $newPoints = max(0, (int)($user['points'] ?? 0) + $pointsDelta);
                $db->update('users', ['points' => $newPoints], 'id = :id', [':id' => $uid]);
                $db->insert('user_points_log', [
                    'user_id'     => $uid,
                    'points'      => abs($pointsDelta),
                    'type'        => $pointsDelta > 0 ? 'earn' : 'spend',
                    'description' => $desc,
                ]);
                $msg = '积分已调整'; $msgType = 'success';
            }
        }
        // 批量操作
        elseif ($action === 'batch_delete') {
            $ids = $_POST['ids'] ?? [];
            if (is_array($ids) && count($ids) > 0) {
                $ids = array_map('intval', array_filter($ids));
                if (!empty($ids)) {
                    $placeholders = implode(',', $ids);
                    $db->query("DELETE FROM user_points_log WHERE user_id IN ($placeholders)");
                    $db->query("DELETE FROM favorites WHERE user_id IN ($placeholders)");
                    $db->query("DELETE FROM downloads WHERE user_id IN ($placeholders)");
                    $db->query("DELETE FROM feedback WHERE user_id IN ($placeholders)");
                    $db->query("DELETE FROM comments WHERE user_id IN ($placeholders)");
                    $db->query("DELETE FROM orders WHERE user_id IN ($placeholders)");
                    $db->query("DELETE FROM users WHERE id IN ($placeholders)");
                    $msg = '已删除 ' . count($ids) . ' 个用户及其关联数据'; $msgType = 'success';
                }
            }
        }
        elseif ($action === 'batch_enable') {
            $ids = $_POST['ids'] ?? [];
            if (is_array($ids) && count($ids) > 0) {
                $ids = array_map('intval', array_filter($ids));
                if (!empty($ids)) {
                    $placeholders = implode(',', $ids);
                    $db->query("UPDATE users SET status=1 WHERE id IN ($placeholders)");
                    $msg = '已启用 ' . count($ids) . ' 个用户'; $msgType = 'success';
                }
            }
        }
        elseif ($action === 'batch_disable') {
            $ids = $_POST['ids'] ?? [];
            if (is_array($ids) && count($ids) > 0) {
                $ids = array_map('intval', array_filter($ids));
                if (!empty($ids)) {
                    $placeholders = implode(',', $ids);
                    $db->query("UPDATE users SET status=0 WHERE id IN ($placeholders)");
                    $msg = '已禁用 ' . count($ids) . ' 个用户'; $msgType = 'success';
                }
            }
        }
    }
}

// 筛选条件
$search = trim($_GET['search'] ?? '');
$filterVip    = $_GET['vip'] ?? '';
$filterStatus = $_GET['status'] ?? '';

// CSV导出
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $expWhere = '1=1';
    $expParams = [];
    if ($search !== '') {
        $expWhere .= ' AND (nickname LIKE :s OR phone LIKE :s OR id = :exact_id)';
        $expParams[':s'] = '%' . $search . '%';
        $expParams[':exact_id'] = (int)$search ?: 0;
    }
    if ($filterVip !== '') {
        $expWhere .= ' AND vip_level = :vip';
        $expParams[':vip'] = (int)$filterVip;
    }
    if ($filterStatus !== '') {
        $expWhere .= ' AND status = :st';
        $expParams[':st'] = (int)$filterStatus;
    }
    $expUsers = $db->fetchAll(
        "SELECT * FROM users WHERE {$expWhere} ORDER BY created_at DESC",
        $expParams
    );
    $expVipLabels = [0 => '普通', 1 => '包月VIP', 2 => '包季VIP', 3 => '包年VIP', 4 => '终身VIP'];
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=用户列表_' . date('Ymd') . '.csv');
    echo "\xEF\xBB\xBF"; // UTF-8 BOM
    $fp = fopen('php://output', 'w');
    fputcsv($fp, ['ID','昵称','手机号','性别','VIP等级','积分','下载次数','状态','注册时间']);
    foreach ($expUsers as $u) {
        fputcsv($fp, [
            $u['id'],
            $u['nickname'] ?? '',
            $u['phone'] ?? '',
            ['未知','男','女'][(int)$u['gender']] ?? '未知',
            $expVipLabels[$u['vip_level']] ?? '普通',
            (int)$u['points'],
            (int)$u['total_downloads'],
            $u['status'] ? '正常' : '禁用',
            $u['created_at'],
        ]);
    }
    fclose($fp);
    exit;
}

$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset  = ($page - 1) * $perPage;

$where = '1=1';
$params = [];
if ($search !== '') {
    $where .= ' AND (nickname LIKE :s OR phone LIKE :s OR id = :exact_id)';
    $params[':s'] = '%' . $search . '%';
    $params[':exact_id'] = (int)$search ?: 0;
}
if ($filterVip !== '') {
    $where .= ' AND vip_level = :vip';
    $params[':vip'] = (int)$filterVip;
}
if ($filterStatus !== '') {
    $where .= ' AND status = :st';
    $params[':st'] = (int)$filterStatus;
}

$total = $db->fetch('SELECT COUNT(*) AS cnt FROM users WHERE ' . $where, $params);
$totalRows = (int)($total['cnt'] ?? 0);
$totalPages = max(1, ceil($totalRows / $perPage));

$users = $db->fetchAll(
    "SELECT * FROM users WHERE {$where} ORDER BY created_at DESC LIMIT {$perPage} OFFSET {$offset}",
    $params
);

// 详情视图
$detailUser = null;
$editUser = null;
$pointsLog = [];
if (isset($_GET['view'])) {
    $viewId = (int)$_GET['view'];
    if ($viewId > 0) {
        $detailUser = $db->fetch('SELECT * FROM users WHERE id=:id', [':id' => $viewId]);
        if ($detailUser) {
            $pointsLog = $db->fetchAll(
                'SELECT * FROM user_points_log WHERE user_id=:id ORDER BY created_at DESC LIMIT 20',
                [':id' => $viewId]
            );
        }
    }
}
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    if ($editId > 0) {
        $editUser = $db->fetch('SELECT * FROM users WHERE id=:id', [':id' => $editId]);
    }
}

$vipLabels = [0 => '普通', 1 => '包月VIP', 2 => '包季VIP', 3 => '包年VIP', 4 => '终身VIP'];

function buildUrl($p) {
    return 'users.php?' . http_build_query(array_filter($p, function($v) { return $v !== '' && $v !== 0; }));
}

include __DIR__ . '/header.php';
?>

<?php if ($msg): ?>
<div class="alert auto-dismiss" style="padding:12px 16px;border-radius:var(--radius);margin-bottom:20px;font-size:14px;
  <?php echo $msgType==='success' ? 'background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0' : 'background:#fef2f2;color:#b91c1c;border:1px solid #fecaca'; ?>">
  <?php echo htmlspecialchars($msg); ?>
</div>
<?php endif; ?>

<?php if ($detailUser): ?>
<!-- User Detail -->
<div class="card mb-20">
  <div class="card-header">
    <h3>用户详情 #<?php echo $detailUser['id']; ?></h3>
    <a href="users.php" class="btn btn-outline btn-sm">返回列表</a>
  </div>
  <div class="card-body">
    <dl class="detail-grid">
      <dt>ID</dt><dd><?php echo $detailUser['id']; ?></dd>
      <dt>昵称</dt><dd><?php echo htmlspecialchars($detailUser['nickname'] ?? '-'); ?></dd>
      <dt>手机号</dt><dd><?php echo htmlspecialchars($detailUser['phone'] ?? '-'); ?></dd>
      <dt>性别</dt><dd><?php echo ['未知','男','女'][(int)$detailUser['gender']] ?? '-'; ?></dd>
      <dt>VIP等级</dt>
      <dd>
        <span class="badge <?php echo $detailUser['vip_level'] > 0 ? 'badge-info' : 'badge-default'; ?>">
          <?php echo $vipLabels[$detailUser['vip_level']] ?? '-'; ?>
        </span>
        <?php if ($detailUser['vip_expire_at']): ?>
        (到期: <?php echo $detailUser['vip_expire_at']; ?>)
        <?php endif; ?>
      </dd>
      <dt>积分</dt><dd><?php echo (int)$detailUser['points']; ?></dd>
      <dt>下载次数</dt><dd><?php echo (int)$detailUser['total_downloads']; ?></dd>
      <dt>状态</dt>
      <dd>
        <span class="badge <?php echo $detailUser['status'] ? 'badge-success' : 'badge-danger'; ?>">
          <?php echo $detailUser['status'] ? '正常' : '禁用'; ?>
        </span>
      </dd>
      <dt>注册时间</dt><dd><?php echo $detailUser['created_at']; ?></dd>
    </dl>
  </div>
</div>

<!-- Set VIP -->
<div class="card mb-20">
  <div class="card-header"><h3>设置VIP等级</h3></div>
  <div class="card-body">
    <form method="POST" class="d-flex align-center gap-10">
      <input type="hidden" name="_token" value="<?php echo $csrfToken; ?>">
      <input type="hidden" name="action" value="set_vip">
      <input type="hidden" name="id" value="<?php echo $detailUser['id']; ?>">
      <select name="vip_level" class="filter-select">
        <?php foreach ($vipLabels as $k => $v): ?>
        <option value="<?php echo $k; ?>" <?php echo $detailUser['vip_level'] == $k ? 'selected' : ''; ?>><?php echo $v; ?></option>
        <?php endforeach; ?>
      </select>
      <input type="date" name="vip_expire_at" class="form-control" style="width:180px"
             value="<?php echo $detailUser['vip_expire_at'] ? substr($detailUser['vip_expire_at'], 0, 10) : ''; ?>"
             placeholder="到期日期">
      <button type="submit" class="btn btn-primary btn-sm">更新VIP</button>
    </form>
  </div>
</div>

<!-- Adjust Points -->
<div class="card mb-20">
  <div class="card-header"><h3>调整积分</h3></div>
  <div class="card-body">
    <form method="POST" class="d-flex align-center gap-10">
      <input type="hidden" name="_token" value="<?php echo $csrfToken; ?>">
      <input type="hidden" name="action" value="adjust_points">
      <input type="hidden" name="id" value="<?php echo $detailUser['id']; ?>">
      <input type="number" name="points_delta" class="form-control" style="width:140px" placeholder="+/- 积分" required>
      <input type="text" name="points_desc" class="form-control" style="width:240px" placeholder="调整原因">
      <button type="submit" class="btn btn-primary btn-sm">调整</button>
    </form>
    <p class="form-hint mt-10">当前余额: <strong><?php echo (int)$detailUser['points']; ?></strong> 积分。正数增加，负数扣减。</p>
  </div>
</div>

<!-- Points History -->
<div class="card mb-20">
  <div class="card-header"><h3>积分记录</h3></div>
  <div class="card-body no-padding">
    <?php if (empty($pointsLog)): ?>
    <div class="empty-state"><p>暂无积分记录</p></div>
    <?php else: ?>
    <table>
      <thead>
        <tr><th>时间</th><th>类型</th><th>积分</th><th>描述</th></tr>
      </thead>
      <tbody>
        <?php foreach ($pointsLog as $pl): ?>
        <tr>
          <td><?php echo $pl['created_at']; ?></td>
          <td>
            <span class="badge <?php echo $pl['type'] === 'earn' ? 'badge-success' : 'badge-warning'; ?>">
              <?php echo $pl['type'] === 'earn' ? '收入' : '支出'; ?>
            </span>
          </td>
          <td><?php echo (int)$pl['points']; ?></td>
          <td><?php echo htmlspecialchars($pl['description'] ?? '-'); ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>

<?php elseif ($editUser): ?>
<!-- Edit User -->
<div class="card mb-20">
  <div class="card-header">
    <h3>编辑用户 #<?php echo $editUser['id']; ?></h3>
    <a href="users.php" class="btn btn-outline btn-sm">返回列表</a>
  </div>
  <div class="card-body">
    <form method="POST" enctype="multipart/form-data" class="form-grid">
      <input type="hidden" name="_token" value="<?php echo $csrfToken; ?>">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="id" value="<?php echo $editUser['id']; ?>">
      <div class="form-group" style="grid-column:1/-1;display:flex;align-items:center;gap:20px;">
        <div style="flex-shrink:0;">
          <?php if ($editUser['avatar_url']): ?>
          <img id="avatarPreview" src="<?php echo htmlspecialchars($editUser['avatar_url']); ?>" style="width:80px;height:80px;border-radius:50%;object-fit:cover;border:3px solid #e2e8f0;">
          <?php else: ?>
          <img id="avatarPreview" src="" style="width:80px;height:80px;border-radius:50%;object-fit:cover;border:3px solid #e2e8f0;display:none;">
          <div id="avatarPlaceholder" style="width:80px;height:80px;border-radius:50%;background:linear-gradient(135deg,#6366f1,#8b5cf6);display:flex;align-items:center;justify-content:center;color:#fff;font-size:32px;font-weight:800;">
            <?php echo mb_substr($editUser['nickname'] ?? '?', 0, 1); ?>
          </div>
          <?php endif; ?>
        </div>
        <div>
          <label style="font-weight:600;font-size:14px;display:block;margin-bottom:8px;">用户头像</label>
          <input type="file" name="avatar" accept="image/*" onchange="previewAvatar(this)" style="font-size:13px;">
          <p style="font-size:12px;color:#94a3b8;margin-top:4px;">支持 JPG/PNG/GIF/WebP，留空则不修改</p>
        </div>
      </div>
      <div class="form-group">
        <label>昵称</label>
        <input type="text" name="nickname" class="form-control" value="<?php echo htmlspecialchars($editUser['nickname'] ?? ''); ?>" required>
      </div>
      <div class="form-group">
        <label>手机号</label>
        <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($editUser['phone'] ?? ''); ?>">
      </div>
      <div class="form-group">
        <label>性别</label>
        <select name="gender" class="form-control">
          <option value="0" <?php echo ($editUser['gender'] ?? 0) == 0 ? 'selected' : ''; ?>>未知</option>
          <option value="1" <?php echo ($editUser['gender'] ?? 0) == 1 ? 'selected' : ''; ?>>男</option>
          <option value="2" <?php echo ($editUser['gender'] ?? 0) == 2 ? 'selected' : ''; ?>>女</option>
        </select>
      </div>
      <div class="form-group" style="grid-column:1/-1;">
        <button type="submit" class="btn btn-primary">保存修改</button>
        <a href="users.php" class="btn btn-outline" style="margin-left:10px;">取消</a>
      </div>
    </form>
  </div>
</div>

<?php else: ?>

<!-- Toolbar -->
<div class="toolbar">
  <div class="toolbar-left">
    <form method="GET" class="d-flex align-center gap-10">
      <div class="search-box">
        <input type="text" name="search" placeholder="搜索昵称或手机号..."
               value="<?php echo htmlspecialchars($search); ?>">
      </div>
      <select name="vip" class="filter-select">
        <option value="">全部VIP</option>
        <?php foreach ($vipLabels as $k => $v): ?>
        <option value="<?php echo $k; ?>" <?php echo $filterVip === (string)$k ? 'selected' : ''; ?>><?php echo $v; ?></option>
        <?php endforeach; ?>
      </select>
      <select name="status" class="filter-select">
        <option value="">全部状态</option>
        <option value="1" <?php echo $filterStatus === '1' ? 'selected' : ''; ?>>正常</option>
        <option value="0" <?php echo $filterStatus === '0' ? 'selected' : ''; ?>>禁用</option>
      </select>
      <button type="submit" class="btn btn-primary btn-sm">筛选</button>
    </form>
  </div>
  <div class="toolbar-right">
    <a href="users.php?export=csv&search=<?php echo urlencode($search); ?>&vip=<?php echo urlencode($filterVip); ?>&status=<?php echo urlencode($filterStatus); ?>" class="btn btn-outline btn-sm">📥 导出CSV</a>
  </div>
</div>

<!-- Users Table -->
<div class="card">
  <div class="card-body no-padding">
    <?php if (empty($users)): ?>
    <div class="empty-state">
      <div class="empty-icon">👥</div>
      <h4>未找到用户</h4>
    </div>
    <?php else: ?>
    <div class="table-wrapper">
      <table>
        <thead>
          <tr>
            <th style="width:40px;"><input type="checkbox" id="checkAll" onchange="toggleAll()"></th>
            <th>ID</th>
            <th>昵称</th>
            <th>手机号</th>
            <th>VIP</th>
            <th>积分</th>
            <th>下载次数</th>
            <th>状态</th>
            <th>注册时间</th>
            <th>操作</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $u): ?>
          <tr>
            <td><input type="checkbox" class="row-check" name="ids[]" value="<?php echo $u['id']; ?>" onchange="updateBatch()"></td>
            <td><?php echo $u['id']; ?></td>
            <td>
              <?php if ($u['avatar_url']): ?>
              <img src="<?php echo htmlspecialchars($u['avatar_url']); ?>" style="width:28px;height:28px;border-radius:50%;vertical-align:middle;margin-right:6px;">
              <?php endif; ?>
              <?php echo htmlspecialchars($u['nickname'] ?? '-'); ?>
            </td>
            <td><?php echo htmlspecialchars($u['phone'] ?? '-'); ?></td>
            <td>
              <span class="badge <?php echo $u['vip_level'] > 0 ? 'badge-info' : 'badge-default'; ?>">
                <?php echo $vipLabels[$u['vip_level']] ?? '-'; ?>
              </span>
              <?php if ($u['vip_level'] > 0 && $u['vip_expire_at']): ?>
              <br><span style="font-size:11px;color:var(--text-muted);">到期: <?php echo substr($u['vip_expire_at'], 0, 10); ?></span>
              <?php endif; ?>
            </td>
            <td><?php echo (int)$u['points']; ?></td>
            <td><?php echo (int)$u['total_downloads']; ?></td>
            <td>
              <span class="badge <?php echo $u['status'] ? 'badge-success' : 'badge-danger'; ?>">
                <?php echo $u['status'] ? '正常' : '禁用'; ?>
              </span>
            </td>
            <td><?php echo substr($u['created_at'], 0, 10); ?></td>
            <td class="actions">
              <a href="users.php?view=<?php echo $u['id']; ?>" class="btn btn-outline btn-sm">详情</a>
              <a href="users.php?edit=<?php echo $u['id']; ?>" class="btn btn-outline btn-sm">编辑</a>
              <form method="POST" style="display:inline">
                <input type="hidden" name="_token" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="action" value="toggle_status">
                <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                <button class="btn btn-sm <?php echo $u['status'] ? 'btn-warning' : 'btn-success'; ?>">
                  <?php echo $u['status'] ? '禁用' : '启用'; ?>
                </button>
              </form>
              <form method="POST" style="display:inline" onsubmit="return confirm('确定删除用户 #<?php echo $u['id']; ?>？此操作不可恢复！')">
                <input type="hidden" name="_token" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                <button class="btn btn-danger btn-sm">删除</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Batch Action Bar -->
    <div id="batchBar" style="display:none;position:sticky;bottom:0;background:rgba(255,255,255,.95);backdrop-filter:blur(12px);padding:12px 20px;border-top:1px solid #e2e8f0;border-radius:0 0 16px 16px;align-items:center;gap:12px;z-index:10;">
      <span id="batchCount" style="font-size:13px;color:#64748b;font-weight:600;">已选 0 项</span>
      <form method="POST" style="display:inline" id="batchEnableForm" onsubmit="return confirm('确定启用选中用户？')">
        <input type="hidden" name="_token" value="<?php echo $csrfToken; ?>">
        <input type="hidden" name="action" value="batch_enable">
        <div id="batchEnableIds"></div>
        <button class="btn btn-success btn-sm">批量启用</button>
      </form>
      <form method="POST" style="display:inline" id="batchDisableForm" onsubmit="return confirm('确定禁用选中用户？')">
        <input type="hidden" name="_token" value="<?php echo $csrfToken; ?>">
        <input type="hidden" name="action" value="batch_disable">
        <div id="batchDisableIds"></div>
        <button class="btn btn-warning btn-sm">批量禁用</button>
      </form>
      <form method="POST" style="display:inline" id="batchDeleteForm" onsubmit="return confirm('确定删除选中用户？此操作不可恢复！')">
        <input type="hidden" name="_token" value="<?php echo $csrfToken; ?>">
        <input type="hidden" name="action" value="batch_delete">
        <div id="batchDeleteIds"></div>
        <button class="btn btn-danger btn-sm">批量删除</button>
      </form>
    </div>

    <!-- Pagination -->
    <div class="pagination">
      <span class="page-info">共 <?php echo $totalRows; ?> 个用户，第 <?php echo $page; ?>/<?php echo $totalPages; ?> 页</span>
      <div class="page-links">
        <?php if ($page > 1): ?>
        <a href="<?php echo buildUrl(['search'=>$search,'vip'=>$filterVip,'status'=>$filterStatus,'page'=>$page-1]); ?>">&laquo; 上一页</a>
        <?php else: ?><span class="disabled">&laquo; 上一页</span><?php endif; ?>
        <?php
        $s = max(1, $page - 3); $e = min($totalPages, $page + 3);
        for ($p = $s; $p <= $e; $p++): ?>
        <a href="<?php echo buildUrl(['search'=>$search,'vip'=>$filterVip,'status'=>$filterStatus,'page'=>$p]); ?>"
           class="<?php echo $p===$page?'active':''; ?>"><?php echo $p; ?></a>
        <?php endfor; ?>
        <?php if ($page < $totalPages): ?>
        <a href="<?php echo buildUrl(['search'=>$search,'vip'=>$filterVip,'status'=>$filterStatus,'page'=>$page+1]); ?>">下一页 &raquo;</a>
        <?php else: ?><span class="disabled">下一页 &raquo;</span><?php endif; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php endif; ?>

<?php include __DIR__ . '/footer.php'; ?>

<script>
function previewAvatar(input) {
  if (input.files && input.files[0]) {
    var reader = new FileReader();
    reader.onload = function(e) {
      var preview = document.getElementById('avatarPreview');
      var placeholder = document.getElementById('avatarPlaceholder');
      if (preview) { preview.src = e.target.result; preview.style.display = 'block'; }
      if (placeholder) { placeholder.style.display = 'none'; }
    };
    reader.readAsDataURL(input.files[0]);
  }
}
function toggleAll() {
  var checked = document.getElementById('checkAll').checked;
  document.querySelectorAll('.row-check').forEach(function(cb) { cb.checked = checked; });
  updateBatch();
}
function updateBatch() {
  var checked = document.querySelectorAll('.row-check:checked');
  var bar = document.getElementById('batchBar');
  var count = document.getElementById('batchCount');
  if (checked.length > 0) {
    bar.style.display = 'flex';
    count.textContent = '已选 ' + checked.length + ' 项';
    // 同步选中ID到各表单
    var ids = [];
    checked.forEach(function(cb) { ids.push(cb.value); });
    ['batchEnableIds','batchDisableIds','batchDeleteIds'].forEach(function(divId) {
      var div = document.getElementById(divId);
      div.innerHTML = '';
      ids.forEach(function(id) {
        var input = document.createElement('input');
        input.type = 'hidden'; input.name = 'ids[]'; input.value = id;
        div.appendChild(input);
      });
    });
  } else {
    bar.style.display = 'none';
  }
}
</script>
