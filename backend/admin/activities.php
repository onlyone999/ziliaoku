<?php
/**
 * 管理后台 - 活动管理
 * 完整CRUD、状态管理、报名名单、导出CSV、操作日志
 */
session_start();
if (empty($_SESSION['admin_id'])) { header('Location: login.php'); exit; }

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/AdminLog.php';
$db = Database::getInstance();
$csrfToken = $_SESSION['csrf_token'] ?? '';

// 自动建表
$db->query("CREATE TABLE IF NOT EXISTS activities (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    cover_url VARCHAR(500),
    activity_type ENUM('online','offline','both') NOT NULL DEFAULT 'online',
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    signup_deadline DATETIME,
    location VARCHAR(300),
    max_participants INT NOT NULL DEFAULT 0,
    current_count INT NOT NULL DEFAULT 0,
    status ENUM('draft','published','closed','ended') NOT NULL DEFAULT 'draft',
    resource_id INT UNSIGNED DEFAULT NULL,
    admin_id INT UNSIGNED DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_status (status),
    KEY idx_start (start_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->query("CREATE TABLE IF NOT EXISTS activity_signups (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    activity_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    extra_info JSON,
    status ENUM('pending','confirmed','cancelled') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_activity_user (activity_id, user_id),
    KEY idx_activity (activity_id),
    KEY idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$msg = '';
$msgType = '';

// 处理 POST 请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $token  = $_POST['_token'] ?? '';
    if ($token !== $csrfToken) {
        $msg = 'CSRF令牌无效'; $msgType = 'error';
    } else {
        // 删除活动
        if ($action === 'delete') {
            $aid = (int)($_POST['id'] ?? 0);
            if ($aid > 0) {
                $db->delete('activity_signups', 'activity_id = :aid', [':aid' => $aid]);
                $db->delete('activities', 'id = :id', [':id' => $aid]);
                AdminLog::log('delete', 'activity', $aid, '删除活动 #' . $aid);
                $msg = '活动已删除'; $msgType = 'success';
            }
        }
        // 切换状态
        elseif ($action === 'toggle_status') {
            $aid = (int)($_POST['id'] ?? 0);
            $newStatus = $_POST['new_status'] ?? '';
            if ($aid > 0 && in_array($newStatus, ['draft','published','closed','ended'])) {
                $db->update('activities', ['status' => $newStatus], 'id = :id', [':id' => $aid]);
                $statusLabels = ['draft'=>'草稿','published'=>'已发布','closed'=>'已关闭','ended'=>'已结束'];
                $msg = '状态已切换为：' . ($statusLabels[$newStatus] ?? $newStatus);
                $msgType = 'success';
                AdminLog::log('update', 'activity', $aid, '切换活动状态为 ' . $newStatus);
            }
        }
        // 更新报名状态
        elseif ($action === 'update_signup_status') {
            $signupId = (int)($_POST['signup_id'] ?? 0);
            $newStatus = $_POST['signup_status'] ?? '';
            if ($signupId > 0 && in_array($newStatus, ['pending','confirmed','cancelled'])) {
                $oldSignup = $db->fetch('SELECT * FROM activity_signups WHERE id=:id', [':id' => $signupId]);
                $db->update('activity_signups', ['status' => $newStatus], 'id = :id', [':id' => $signupId]);
                // 如果从非取消变为取消，人数-1；从取消变为非取消，人数+1
                if ($oldSignup) {
                    if ($oldSignup['status'] !== 'cancelled' && $newStatus === 'cancelled') {
                        $db->query("UPDATE activities SET current_count = GREATEST(current_count - 1, 0) WHERE id = :id", [':id' => $oldSignup['activity_id']]);
                    } elseif ($oldSignup['status'] === 'cancelled' && $newStatus !== 'cancelled') {
                        $db->query("UPDATE activities SET current_count = current_count + 1 WHERE id = :id", [':id' => $oldSignup['activity_id']]);
                    }
                }
                $msg = '报名状态已更新'; $msgType = 'success';
                AdminLog::log('update', 'activity_signup', $signupId, '更新报名状态为 ' . $newStatus);
            }
        }
        // 保存（新增/编辑）
        elseif ($action === 'save') {
            $editId           = (int)($_POST['edit_id'] ?? 0);
            $title            = trim($_POST['title'] ?? '');
            $description      = trim($_POST['description'] ?? '');
            $coverUrl         = trim($_POST['cover_url'] ?? '');
            $activityType     = $_POST['activity_type'] ?? 'online';
            $startTime        = trim($_POST['start_time'] ?? '');
            $endTime          = trim($_POST['end_time'] ?? '');
            $signupDeadline   = trim($_POST['signup_deadline'] ?? '');
            $location         = trim($_POST['location'] ?? '');
            $maxParticipants  = max(0, (int)($_POST['max_participants'] ?? 0));
            $status           = $_POST['status'] ?? 'draft';
            $resourceId       = ($_POST['resource_id'] ?? '') !== '' ? (int)$_POST['resource_id'] : null;

            if ($title === '') {
                $msg = '活动标题为必填项'; $msgType = 'error';
            } elseif ($startTime === '' || $endTime === '') {
                $msg = '活动开始和结束时间为必填项'; $msgType = 'error';
            } elseif ($startTime >= $endTime) {
                $msg = '结束时间必须晚于开始时间'; $msgType = 'error';
            } elseif (!in_array($activityType, ['online','offline','both'])) {
                $msg = '活动类型无效'; $msgType = 'error';
            } elseif (!in_array($status, ['draft','published','closed','ended'])) {
                $msg = '状态无效'; $msgType = 'error';
            } else {
                $data = [
                    'title'            => $title,
                    'description'      => $description,
                    'cover_url'        => $coverUrl,
                    'activity_type'    => $activityType,
                    'start_time'       => $startTime,
                    'end_time'         => $endTime,
                    'signup_deadline'  => $signupDeadline ?: null,
                    'location'         => $location,
                    'max_participants' => $maxParticipants,
                    'status'           => $status,
                    'resource_id'      => $resourceId,
                    'admin_id'         => (int)$_SESSION['admin_id'],
                ];
                if ($editId > 0) {
                    $db->update('activities', $data, 'id = :id', [':id' => $editId]);
                    AdminLog::log('update', 'activity', $editId, '编辑活动：' . $title);
                    $msg = '活动已更新';
                } else {
                    $data['current_count'] = 0;
                    $data['created_at'] = date('Y-m-d H:i:s');
                    $db->insert('activities', $data);
                    AdminLog::log('create', 'activity', 0, '新增活动：' . $title);
                    $msg = '活动已创建';
                }
                $msgType = 'success';
            }
        }
    }
}

// 导出 CSV
if (isset($_GET['export'])) {
    $exportId = (int)$_GET['export'];
    if ($exportId > 0) {
        $activity = $db->fetch('SELECT title FROM activities WHERE id=:id', [':id' => $exportId]);
        $signups = $db->fetchAll(
            "SELECT s.id, s.name, s.phone, s.status, s.created_at, u.username
             FROM activity_signups s
             LEFT JOIN users u ON s.user_id = u.id
             WHERE s.activity_id = :aid
             ORDER BY s.created_at ASC",
            [':aid' => $exportId]
        );
        $statusLabels = ['pending'=>'待确认','confirmed'=>'已确认','cancelled'=>'已取消'];
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=activity_' . $exportId . '_signups_' . date('YmdHis') . '.csv');
        echo "\xEF\xBB\xBF"; // UTF-8 BOM
        echo "序号,姓名,手机号,账号,状态,报名时间\n";
        $i = 1;
        foreach ($signups as $s) {
            echo $i++ . ',';
            echo '"' . str_replace('"', '""', $s['name']) . '",';
            echo '"' . str_replace('"', '""', $s['phone'] ?? '') . '",';
            echo '"' . str_replace('"', '""', $s['username'] ?? '') . '",';
            echo '"' . ($statusLabels[$s['status']] ?? $s['status']) . '",';
            echo '"' . $s['created_at'] . "\"\n";
        }
        exit;
    }
}

// 分页与筛选
$search       = trim($_GET['search'] ?? '');
$filterStatus = $_GET['status'] ?? '';
$filterType   = $_GET['type'] ?? '';
$page         = max(1, (int)($_GET['page'] ?? 1));
$perPage      = 15;
$offset       = ($page - 1) * $perPage;

$where = '1=1';
$params = [];
if ($search !== '') {
    $where .= ' AND title LIKE :s';
    $params[':s'] = '%' . $search . '%';
}
if ($filterStatus !== '' && in_array($filterStatus, ['draft','published','closed','ended'])) {
    $where .= ' AND status = :st';
    $params[':st'] = $filterStatus;
}
if ($filterType !== '' && in_array($filterType, ['online','offline','both'])) {
    $where .= ' AND activity_type = :tp';
    $params[':tp'] = $filterType;
}

$totalRow = $db->fetch('SELECT COUNT(*) AS cnt FROM activities WHERE ' . $where, $params);
$totalRows = (int)($totalRow['cnt'] ?? 0);
$totalPages = max(1, ceil($totalRows / $perPage));

$activities = $db->fetchAll(
    "SELECT * FROM activities WHERE {$where} ORDER BY id DESC LIMIT {$perPage} OFFSET {$offset}",
    $params
);

// 编辑表单
$editAct = null;
if (isset($_GET['edit'])) {
    $eid = (int)$_GET['edit'];
    if ($eid > 0) {
        $editAct = $db->fetch('SELECT * FROM activities WHERE id=:id', [':id' => $eid]);
    }
}

// 查看报名名单
$viewSignups = null;
$viewActivity = null;
if (isset($_GET['signups'])) {
    $viewId = (int)$_GET['signups'];
    if ($viewId > 0) {
        $viewActivity = $db->fetch('SELECT * FROM activities WHERE id=:id', [':id' => $viewId]);
        $viewSignups = $db->fetchAll(
            "SELECT s.*, u.username FROM activity_signups s LEFT JOIN users u ON s.user_id = u.id WHERE s.activity_id = :aid ORDER BY s.created_at DESC",
            [':aid' => $viewId]
        );
    }
}

$typeLabels   = ['online' => ['线上', 'badge-info'], 'offline' => ['线下', 'badge-warning'], 'both' => ['线上+线下', 'badge-success']];
$statusLabels = ['draft' => ['草稿', 'badge-default'], 'published' => ['已发布', 'badge-success'], 'closed' => ['已关闭', 'badge-danger'], 'ended' => ['已结束', 'badge-info']];
$signupStatusLabels = ['pending' => ['待确认', 'badge-warning'], 'confirmed' => ['已确认', 'badge-success'], 'cancelled' => ['已取消', 'badge-default']];

function buildUrl($p) {
    return 'activities.php?' . http_build_query(array_filter($p, function($v) { return $v !== '' && $v !== 0; }));
}

include __DIR__ . '/header.php';
?>

<?php if ($msg): ?>
<div class="alert auto-dismiss" style="padding:12px 16px;border-radius:var(--radius);margin-bottom:20px;font-size:14px;
  <?php echo $msgType==='success' ? 'background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0' : 'background:#fef2f2;color:#b91c1c;border:1px solid #fecaca'; ?>">
  <?php echo htmlspecialchars($msg); ?>
</div>
<?php endif; ?>

<?php if ($viewSignups !== null): ?>
<!-- 报名名单弹出 -->
<div class="card mb-20">
  <div class="card-header">
    <h3>&#127919; <?php echo htmlspecialchars($viewActivity['title'] ?? ''); ?> - 报名名单</h3>
    <div class="d-flex gap-10">
      <a href="activities.php?export=<?php echo $viewActivity['id']; ?>" class="btn btn-primary btn-sm">&#128229; 导出CSV</a>
      <a href="activities.php" class="btn btn-outline btn-sm">返回列表</a>
    </div>
  </div>
  <div class="card-body no-padding">
    <?php if (empty($viewSignups)): ?>
    <div class="empty-state">
      <div class="empty-icon">&#128100;</div>
      <h4>暂无报名</h4>
    </div>
    <?php else: ?>
    <div class="table-wrapper">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>姓名</th>
            <th>手机号</th>
            <th>账号</th>
            <th>状态</th>
            <th>报名时间</th>
            <th>操作</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($viewSignups as $idx => $sv): ?>
          <tr>
            <td><?php echo $idx + 1; ?></td>
            <td><?php echo htmlspecialchars($sv['name']); ?></td>
            <td><?php echo htmlspecialchars($sv['phone'] ?? '-'); ?></td>
            <td><?php echo htmlspecialchars($sv['username'] ?? '-'); ?></td>
            <td>
              <?php $sl = $signupStatusLabels[$sv['status']] ?? ['未知','badge-default']; ?>
              <span class="badge <?php echo $sl[1]; ?>"><?php echo $sl[0]; ?></span>
            </td>
            <td><?php echo substr($sv['created_at'], 0, 16); ?></td>
            <td class="actions">
              <?php if ($sv['status'] !== 'confirmed'): ?>
              <form method="POST" style="display:inline">
                <input type="hidden" name="_token" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="action" value="update_signup_status">
                <input type="hidden" name="signup_id" value="<?php echo $sv['id']; ?>">
                <input type="hidden" name="signup_status" value="confirmed">
                <button class="btn btn-success btn-sm">确认</button>
              </form>
              <?php endif; ?>
              <?php if ($sv['status'] !== 'cancelled'): ?>
              <form method="POST" style="display:inline">
                <input type="hidden" name="_token" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="action" value="update_signup_status">
                <input type="hidden" name="signup_id" value="<?php echo $sv['id']; ?>">
                <input type="hidden" name="signup_status" value="cancelled">
                <button class="btn btn-danger btn-sm">取消</button>
              </form>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div style="padding:12px 16px;font-size:13px;color:#666;">
      共 <?php echo count($viewSignups); ?> 条报名记录
      （待确认: <?php echo count(array_filter($viewSignups, function($s){return $s['status']==='pending';})); ?>，
      已确认: <?php echo count(array_filter($viewSignups, function($s){return $s['status']==='confirmed';})); ?>，
      已取消: <?php echo count(array_filter($viewSignups, function($s){return $s['status']==='cancelled';})); ?>）
    </div>
    <?php endif; ?>
  </div>
</div>

<?php elseif ($editAct || isset($_GET['add'])): ?>
<!-- 新增/编辑表单 -->
<div class="card mb-20">
  <div class="card-header">
    <h3><?php echo $editAct ? '编辑活动' : '新增活动'; ?></h3>
    <a href="activities.php" class="btn btn-outline btn-sm">返回列表</a>
  </div>
  <div class="card-body">
    <form method="POST" action="activities.php">
      <input type="hidden" name="_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
      <input type="hidden" name="action" value="save">
      <?php if ($editAct): ?>
      <input type="hidden" name="edit_id" value="<?php echo $editAct['id']; ?>">
      <?php endif; ?>

      <div class="form-row">
        <div class="form-group" style="flex:2;">
          <label>活动标题 <span class="required">*</span></label>
          <input type="text" name="title" class="form-control" required maxlength="200"
                 value="<?php echo htmlspecialchars($editAct['title'] ?? ''); ?>"
                 placeholder="请输入活动标题">
        </div>
        <div class="form-group">
          <label>活动类型</label>
          <select name="activity_type" class="form-control">
            <option value="online" <?php echo (($editAct['activity_type'] ?? 'online') === 'online') ? 'selected' : ''; ?>>线上</option>
            <option value="offline" <?php echo (($editAct['activity_type'] ?? '') === 'offline') ? 'selected' : ''; ?>>线下</option>
            <option value="both" <?php echo (($editAct['activity_type'] ?? '') === 'both') ? 'selected' : ''; ?>>线上+线下</option>
          </select>
        </div>
      </div>

      <div class="form-group">
        <label>封面图片URL</label>
        <input type="text" name="cover_url" class="form-control"
               value="<?php echo htmlspecialchars($editAct['cover_url'] ?? ''); ?>"
               placeholder="请输入封面图片地址">
      </div>

      <div class="form-group">
        <label>活动描述</label>
        <textarea name="description" class="form-control" rows="6"
                  placeholder="请输入活动详细描述"><?php echo htmlspecialchars($editAct['description'] ?? ''); ?></textarea>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>开始时间 <span class="required">*</span></label>
          <input type="datetime-local" name="start_time" class="form-control" required
                 value="<?php echo $editAct ? date('Y-m-d\TH:i', strtotime($editAct['start_time'])) : ''; ?>">
        </div>
        <div class="form-group">
          <label>结束时间 <span class="required">*</span></label>
          <input type="datetime-local" name="end_time" class="form-control" required
                 value="<?php echo $editAct ? date('Y-m-d\TH:i', strtotime($editAct['end_time'])) : ''; ?>">
        </div>
        <div class="form-group">
          <label>报名截止时间</label>
          <input type="datetime-local" name="signup_deadline" class="form-control"
                 value="<?php echo ($editAct && !empty($editAct['signup_deadline']) && $editAct['signup_deadline'] !== '0000-00-00 00:00:00') ? date('Y-m-d\TH:i', strtotime($editAct['signup_deadline'])) : ''; ?>">
        </div>
      </div>

      <div class="form-row">
        <div class="form-group" style="flex:2;">
          <label>活动地点</label>
          <input type="text" name="location" class="form-control"
                 value="<?php echo htmlspecialchars($editAct['location'] ?? ''); ?>"
                 placeholder="线下活动请输入地点">
        </div>
        <div class="form-group">
          <label>最大报名人数 (0=不限)</label>
          <input type="number" name="max_participants" class="form-control" min="0"
                 value="<?php echo (int)($editAct['max_participants'] ?? 0); ?>">
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>状态</label>
          <select name="status" class="form-control">
            <option value="draft" <?php echo (($editAct['status'] ?? 'draft') === 'draft') ? 'selected' : ''; ?>>草稿</option>
            <option value="published" <?php echo (($editAct['status'] ?? '') === 'published') ? 'selected' : ''; ?>>已发布</option>
            <option value="closed" <?php echo (($editAct['status'] ?? '') === 'closed') ? 'selected' : ''; ?>>已关闭</option>
            <option value="ended" <?php echo (($editAct['status'] ?? '') === 'ended') ? 'selected' : ''; ?>>已结束</option>
          </select>
        </div>
        <div class="form-group">
          <label>关联资源ID (选填)</label>
          <input type="number" name="resource_id" class="form-control" min="0"
                 value="<?php echo $editAct['resource_id'] ?? ''; ?>"
                 placeholder="关联的资源ID">
        </div>
      </div>

      <div class="d-flex gap-10">
        <button type="submit" class="btn btn-primary btn-lg">保存</button>
        <a href="activities.php" class="btn btn-outline btn-lg">取消</a>
      </div>
    </form>
  </div>
</div>

<?php else: ?>

<!-- 工具栏 -->
<div class="toolbar">
  <div class="toolbar-left">
    <form method="GET" class="d-flex align-center gap-10">
      <div class="search-box">
        <input type="text" name="search" placeholder="搜索活动标题..."
               value="<?php echo htmlspecialchars($search); ?>">
      </div>
      <select name="type" class="filter-select">
        <option value="">全部类型</option>
        <?php foreach ($typeLabels as $k => $v): ?>
        <option value="<?php echo $k; ?>" <?php echo $filterType===$k?'selected':''; ?>><?php echo $v[0]; ?></option>
        <?php endforeach; ?>
      </select>
      <select name="status" class="filter-select">
        <option value="">全部状态</option>
        <?php foreach ($statusLabels as $k => $v): ?>
        <option value="<?php echo $k; ?>" <?php echo $filterStatus===$k?'selected':''; ?>><?php echo $v[0]; ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn btn-primary btn-sm">筛选</button>
    </form>
  </div>
  <div class="toolbar-right">
    <a href="activities.php?add=1" class="btn btn-primary">+ 新增活动</a>
  </div>
</div>

<!-- 活动列表 -->
<div class="card">
  <div class="card-body no-padding">
    <?php if (empty($activities)): ?>
    <div class="empty-state">
      <div class="empty-icon">&#127891;</div>
      <h4>暂无活动</h4>
    </div>
    <?php else: ?>
    <div class="table-wrapper">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>活动标题</th>
            <th>类型</th>
            <th>时间</th>
            <th>报名</th>
            <th>状态</th>
            <th>操作</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($activities as $a): ?>
          <tr>
            <td><?php echo $a['id']; ?></td>
            <td style="max-width:220px;">
              <?php echo htmlspecialchars($a['title']); ?>
              <?php if ($a['location']): ?>
              <br><small class="text-muted">&#128205; <?php echo htmlspecialchars(mb_substr($a['location'], 0, 30)); ?></small>
              <?php endif; ?>
            </td>
            <td>
              <?php $tl = $typeLabels[$a['activity_type']] ?? ['未知','badge-default']; ?>
              <span class="badge <?php echo $tl[1]; ?>"><?php echo $tl[0]; ?></span>
            </td>
            <td style="font-size:12px;white-space:nowrap;">
              <?php echo date('m/d H:i', strtotime($a['start_time'])); ?><br>
              <span class="text-muted">~ <?php echo date('m/d H:i', strtotime($a['end_time'])); ?></span>
            </td>
            <td>
              <strong><?php echo $a['current_count']; ?></strong>
              / <?php echo $a['max_participants'] > 0 ? $a['max_participants'] : '不限'; ?>
            </td>
            <td>
              <?php $sl = $statusLabels[$a['status']] ?? ['未知','badge-default']; ?>
              <span class="badge <?php echo $sl[1]; ?>"><?php echo $sl[0]; ?></span>
            </td>
            <td class="actions">
              <a href="activities.php?signups=<?php echo $a['id']; ?>" class="btn btn-outline btn-sm">&#128100; 报名名单</a>
              <a href="activities.php?edit=<?php echo $a['id']; ?>" class="btn btn-outline btn-sm">编辑</a>
              <a href="activities.php?export=<?php echo $a['id']; ?>" class="btn btn-outline btn-sm" title="导出CSV">&#128229;</a>
              <!-- 状态切换 -->
              <?php if ($a['status'] === 'draft'): ?>
              <form method="POST" style="display:inline">
                <input type="hidden" name="_token" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="action" value="toggle_status">
                <input type="hidden" name="id" value="<?php echo $a['id']; ?>">
                <input type="hidden" name="new_status" value="published">
                <button class="btn btn-success btn-sm">发布</button>
              </form>
              <?php elseif ($a['status'] === 'published'): ?>
              <form method="POST" style="display:inline">
                <input type="hidden" name="_token" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="action" value="toggle_status">
                <input type="hidden" name="id" value="<?php echo $a['id']; ?>">
                <input type="hidden" name="new_status" value="closed">
                <button class="btn btn-warning btn-sm">关闭</button>
              </form>
              <form method="POST" style="display:inline">
                <input type="hidden" name="_token" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="action" value="toggle_status">
                <input type="hidden" name="id" value="<?php echo $a['id']; ?>">
                <input type="hidden" name="new_status" value="ended">
                <button class="btn btn-info btn-sm">结束</button>
              </form>
              <?php elseif ($a['status'] === 'closed'): ?>
              <form method="POST" style="display:inline">
                <input type="hidden" name="_token" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="action" value="toggle_status">
                <input type="hidden" name="id" value="<?php echo $a['id']; ?>">
                <input type="hidden" name="new_status" value="published">
                <button class="btn btn-success btn-sm">重新发布</button>
              </form>
              <?php elseif ($a['status'] === 'ended'): ?>
              <form method="POST" style="display:inline">
                <input type="hidden" name="_token" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="action" value="toggle_status">
                <input type="hidden" name="id" value="<?php echo $a['id']; ?>">
                <input type="hidden" name="new_status" value="published">
                <button class="btn btn-success btn-sm">重新开放</button>
              </form>
              <?php endif; ?>
              <!-- 删除 -->
              <form method="POST" style="display:inline" onsubmit="return confirm('确定删除此活动？所有报名记录也将一并删除！')">
                <input type="hidden" name="_token" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?php echo $a['id']; ?>">
                <button class="btn btn-danger btn-sm">删除</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- 分页 -->
    <div class="pagination">
      <span class="page-info">共 <?php echo $totalRows; ?> 条活动，第 <?php echo $page; ?>/<?php echo $totalPages; ?> 页</span>
      <div class="page-links">
        <?php if ($page > 1): ?>
        <a href="<?php echo buildUrl(['search'=>$search,'type'=>$filterType,'status'=>$filterStatus,'page'=>$page-1]); ?>">&laquo; 上一页</a>
        <?php else: ?><span class="disabled">&laquo; 上一页</span><?php endif; ?>
        <?php $s=max(1,$page-3);$e=min($totalPages,$page+3); for($p=$s;$p<=$e;$p++): ?>
        <a href="<?php echo buildUrl(['search'=>$search,'type'=>$filterType,'status'=>$filterStatus,'page'=>$p]); ?>"
           class="<?php echo $p===$page?'active':''; ?>"><?php echo $p; ?></a>
        <?php endfor; ?>
        <?php if ($page < $totalPages): ?>
        <a href="<?php echo buildUrl(['search'=>$search,'type'=>$filterType,'status'=>$filterStatus,'page'=>$page+1]); ?>">下一页 &raquo;</a>
        <?php else: ?><span class="disabled">下一页 &raquo;</span><?php endif; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php endif; ?>

<?php include __DIR__ . '/footer.php'; ?>
