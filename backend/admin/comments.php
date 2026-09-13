<?php
/**
 * 管理后台 - 评论管理
 * 带筛选的列表、审核通过/拒绝、删除
 */
session_start();
if (empty($_SESSION['admin_id'])) { header('Location: login.php'); exit; }

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/helper.php';
$db = Database::getInstance();
$csrfToken = $_SESSION['csrf_token'] ?? '';

$msg = '';
$msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $token  = $_POST['_token'] ?? '';
    if ($token !== $csrfToken) { $msg = 'CSRF令牌无效'; $msgType = 'error'; }
    else {
        $cid = (int)($_POST['id'] ?? 0);

        // 批量操作
        if ($action === 'bulk_approve' || $action === 'bulk_reject' || $action === 'bulk_delete') {
            $ids = $_POST['ids'] ?? [];
            if (!empty($ids) && is_array($ids)) {
                $ids = array_map('intval', $ids);
                $ids = array_filter($ids, function($v) { return $v > 0; });
                if (!empty($ids)) {
                    $placeholders = implode(',', array_fill(0, count($ids), '?'));
                    if ($action === 'bulk_approve') {
                        $db->query("UPDATE comments SET status='approved' WHERE id IN ({$placeholders})", $ids);
                        $msg = '已批量通过 ' . count($ids) . ' 条评论';
                    } elseif ($action === 'bulk_reject') {
                        $db->query("UPDATE comments SET status='rejected' WHERE id IN ({$placeholders})", $ids);
                        $msg = '已批量拒绝 ' . count($ids) . ' 条评论';
                    } else {
                        $db->query("DELETE FROM comments WHERE id IN ({$placeholders})", $ids);
                        $msg = '已批量删除 ' . count($ids) . ' 条评论';
                    }
                    $msgType = 'success';
                }
            }
        }

        if ($action === 'approve' && $cid > 0) {
            $db->update('comments', ['status' => 'approved'], 'id = :id', [':id' => $cid]);
            $msg = '评论已通过'; $msgType = 'success';
        }
        elseif ($action === 'reject' && $cid > 0) {
            $db->update('comments', ['status' => 'rejected'], 'id = :id', [':id' => $cid]);
            $msg = '评论已拒绝'; $msgType = 'success';
        }
        elseif ($action === 'delete' && $cid > 0) {
            $db->delete('comments', 'id = :id', [':id' => $cid]);
            $msg = '评论已删除'; $msgType = 'success';
        }
    }
}

// 筛选条件
$search       = trim($_GET['search'] ?? '');
$filterStatus = $_GET['status'] ?? '';
$page         = max(1, (int)($_GET['page'] ?? 1));
$perPage      = getPerPage(20);
$offset       = ($page - 1) * $perPage;

$where = '1=1';
$params = [];
if ($search !== '') {
    $where .= ' AND (c.content LIKE :s OR u.nickname LIKE :s OR r.title LIKE :s)';
    $params[':s'] = '%' . $search . '%';
}
if ($filterStatus !== '') {
    $where .= ' AND c.status = :st';
    $params[':st'] = $filterStatus;
}

$total = $db->fetch(
    'SELECT COUNT(*) AS cnt FROM comments c
     LEFT JOIN users u ON c.user_id = u.id
     LEFT JOIN resources r ON c.resource_id = r.id
     WHERE ' . $where,
    $params
);
$totalRows = (int)($total['cnt'] ?? 0);
$totalPages = max(1, ceil($totalRows / $perPage));

$comments = $db->fetchAll(
    "SELECT c.*, u.nickname AS user_name, r.title AS resource_title
     FROM comments c
     LEFT JOIN users u ON c.user_id = u.id
     LEFT JOIN resources r ON c.resource_id = r.id
     WHERE {$where}
     ORDER BY c.created_at DESC
     LIMIT {$perPage} OFFSET {$offset}",
    $params
);

$statusLabels = [
    'pending'  => ['待审核',  'badge-warning'],
    'approved' => ['已通过',  'badge-success'],
    'rejected' => ['已拒绝',  'badge-danger'],
];

function buildUrl($p) {
    return 'comments.php?' . http_build_query(array_filter($p, function($v) { return $v !== '' && $v !== 0; }));
}

include __DIR__ . '/header.php';
?>

<?php if ($msg): ?>
<div class="alert auto-dismiss" style="padding:12px 16px;border-radius:var(--radius);margin-bottom:20px;font-size:14px;
  <?php echo $msgType==='success' ? 'background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0' : 'background:#fef2f2;color:#b91c1c;border:1px solid #fecaca'; ?>">
  <?php echo htmlspecialchars($msg); ?>
</div>
<?php endif; ?>

<!-- Toolbar -->
<div class="toolbar">
  <div class="toolbar-left">
    <form method="GET" class="d-flex align-center gap-10">
      <div class="search-box">
        <input type="text" name="search" placeholder="搜索内容 / 用户 / 资源..."
               value="<?php echo htmlspecialchars($search); ?>">
      </div>
      <select name="status" class="filter-select">
        <option value="">全部状态</option>
        <?php foreach ($statusLabels as $k => $v): ?>
        <option value="<?php echo $k; ?>" <?php echo $filterStatus===$k?'selected':''; ?>><?php echo $v[0]; ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn btn-primary btn-sm">筛选</button>
    </form>
  </div>
</div>

<!-- Comments Table -->
<div class="card">
  <div class="card-body no-padding">
    <?php if (empty($comments)): ?>
    <div class="empty-state">
      <div class="empty-icon">💬</div>
      <h4>暂无评论</h4>
    </div>
    <?php else: ?>
    <!-- Bulk Actions Bar -->
    <div id="bulkBar" style="display:none;padding:10px 16px;background:var(--primary-bg);border-bottom:1px solid var(--border);font-size:13px;">
      <span id="bulkCount">已选 0 条</span>
      <button class="btn btn-success btn-sm" onclick="bulkAction('bulk_approve')" style="margin-left:10px;">批量通过</button>
      <button class="btn btn-warning btn-sm" onclick="bulkAction('bulk_reject')">批量拒绝</button>
      <button class="btn btn-danger btn-sm" onclick="bulkAction('bulk_delete')">批量删除</button>
    </div>
    <div class="table-wrapper">
      <table>
        <thead>
          <tr>
            <th><input type="checkbox" id="checkAll" onclick="toggleCheckAll(this)"></th>
            <th>ID</th>
            <th>用户</th>
            <th>资源</th>
            <th>内容</th>
            <th>评分</th>
            <th>状态</th>
            <th>时间</th>
            <th>操作</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($comments as $c): ?>
          <tr>
            <td><input type="checkbox" name="ids[]" value="<?php echo $c['id']; ?>" class="row-check" onchange="updateBulkBar()"></td>
            <td><?php echo $c['id']; ?></td>
            <td><?php echo htmlspecialchars($c['user_name'] ?? '-'); ?></td>
            <td style="max-width:150px;white-space:normal;"><?php echo htmlspecialchars($c['resource_title'] ?? '-'); ?></td>
            <td style="max-width:280px;white-space:normal;font-size:13px;"><?php echo htmlspecialchars(mb_substr($c['content'], 0, 100)); ?></td>
            <td>
              <?php if ($c['rating']): ?>
              <?php for ($i = 0; $i < (int)$c['rating']; $i++): ?><span style="color:#f59e0b;">⭐</span><?php endfor; ?>
              <?php else: ?>
              <span class="text-muted">-</span>
              <?php endif; ?>
            </td>
            <td>
              <?php $sl = $statusLabels[$c['status']] ?? ['未知','badge-default']; ?>
              <span class="badge <?php echo $sl[1]; ?>"><?php echo $sl[0]; ?></span>
            </td>
            <td><?php echo substr($c['created_at'], 0, 16); ?></td>
            <td class="actions">
              <?php if ($c['status'] === 'pending'): ?>
              <form method="POST" style="display:inline">
                <input type="hidden" name="_token" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="action" value="approve">
                <input type="hidden" name="id" value="<?php echo $c['id']; ?>">
                <button class="btn btn-success btn-sm">通过</button>
              </form>
              <form method="POST" style="display:inline">
                <input type="hidden" name="_token" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="action" value="reject">
                <input type="hidden" name="id" value="<?php echo $c['id']; ?>">
                <button class="btn btn-warning btn-sm">拒绝</button>
              </form>
              <?php endif; ?>
              <form method="POST" style="display:inline" onsubmit="return confirm('确定删除此评论？')">
                <input type="hidden" name="_token" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?php echo $c['id']; ?>">
                <button class="btn btn-danger btn-sm">删除</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div class="pagination">
      <span class="page-info">共 <?php echo $totalRows; ?> 条评论，第 <?php echo $page; ?>/<?php echo $totalPages; ?> 页</span>
      <div class="page-links">
        <?php if ($page > 1): ?>
        <a href="<?php echo buildUrl(['search'=>$search,'status'=>$filterStatus,'page'=>$page-1]); ?>">&laquo; 上一页</a>
        <?php else: ?><span class="disabled">&laquo; 上一页</span><?php endif; ?>
        <?php $s=max(1,$page-3);$e=min($totalPages,$page+3); for($p=$s;$p<=$e;$p++): ?>
        <a href="<?php echo buildUrl(['search'=>$search,'status'=>$filterStatus,'page'=>$p]); ?>"
           class="<?php echo $p===$page?'active':''; ?>"><?php echo $p; ?></a>
        <?php endfor; ?>
        <?php if ($page < $totalPages): ?>
        <a href="<?php echo buildUrl(['search'=>$search,'status'=>$filterStatus,'page'=>$page+1]); ?>">下一页 &raquo;</a>
        <?php else: ?><span class="disabled">下一页 &raquo;</span><?php endif; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php
$extraJs = "
function toggleCheckAll(master) {
  var checks = document.querySelectorAll('.row-check');
  for (var i = 0; i < checks.length; i++) {
    checks[i].checked = master.checked;
  }
  updateBulkBar();
}
function updateBulkBar() {
  var checks = document.querySelectorAll('.row-check:checked');
  var bar = document.getElementById('bulkBar');
  var count = document.getElementById('bulkCount');
  if (bar) {
    bar.style.display = checks.length > 0 ? 'block' : 'none';
    count.textContent = '已选 ' + checks.length + ' 条';
  }
}
function bulkAction(action) {
  var checks = document.querySelectorAll('.row-check:checked');
  if (checks.length === 0) return;
  var msgMap = {bulk_approve:'批量通过', bulk_reject:'批量拒绝', bulk_delete:'批量删除'};
  Admin.confirm('确定' + msgMap[action] + '选中的 ' + checks.length + ' 条评论？', function() {
    var form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = '<input type=\"hidden\" name=\"_token\" value=\"' + Admin.getCsrfToken() + '\">' +
      '<input type=\"hidden\" name=\"action\" value=\"' + action + '\">';
    for (var i = 0; i < checks.length; i++) {
      var inp = document.createElement('input');
      inp.type = 'hidden';
      inp.name = 'ids[]';
      inp.value = checks[i].value;
      form.appendChild(inp);
    }
    document.body.appendChild(form);
    form.submit();
  });
}
";
?>

<?php include __DIR__ . '/footer.php'; ?>
