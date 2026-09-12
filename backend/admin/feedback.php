<?php
/**
 * 管理后台 - 反馈管理
 * 带筛选的列表、回复功能
 */
session_start();
if (empty($_SESSION['admin_id'])) { header('Location: login.php'); exit; }

require_once __DIR__ . '/../core/Database.php';
$db = Database::getInstance();
$csrfToken = $_SESSION['csrf_token'] ?? '';

$msg = '';
$msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $token  = $_POST['_token'] ?? '';
    if ($token !== $csrfToken) { $msg = 'CSRF令牌无效'; $msgType = 'error'; }
    else {
        $fid = (int)($_POST['id'] ?? 0);

        // 回复
        if ($action === 'reply' && $fid > 0) {
            $reply = trim($_POST['reply'] ?? '');
            if ($reply === '') {
                $msg = '回复内容不能为空'; $msgType = 'error';
            } else {
                $db->update('feedback', [
                    'reply'  => $reply,
                    'status' => 'replied',
                ], 'id = :id', [':id' => $fid]);
                $msg = '回复已发送'; $msgType = 'success';
            }
        }
        // 删除
        elseif ($action === 'delete' && $fid > 0) {
            $db->delete('feedback', 'id = :id', [':id' => $fid]);
            $msg = '反馈已删除'; $msgType = 'success';
        }
        // 切换状态
        elseif ($action === 'set_status' && $fid > 0) {
            $newStatus = $_POST['new_status'] ?? '';
            $allowed = ['pending','replied','resolved','closed'];
            if (in_array($newStatus, $allowed)) {
                $db->update('feedback', ['status' => $newStatus], 'id = :id', [':id' => $fid]);
                $statusNames = ['pending'=>'待处理','replied'=>'已回复','resolved'=>'已解决','closed'=>'已关闭'];
                $msg = '状态已更新为: ' . ($statusNames[$newStatus] ?? $newStatus);
                $msgType = 'success';
            }
        }
        // 批量删除
        elseif ($action === 'bulk_delete') {
            $ids = $_POST['ids'] ?? [];
            if (!empty($ids) && is_array($ids)) {
                $ids = array_filter(array_map('intval', $ids), function($v) { return $v > 0; });
                if (!empty($ids)) {
                    $placeholders = implode(',', array_fill(0, count($ids), '?'));
                    $db->query("DELETE FROM feedback WHERE id IN ({$placeholders})", array_values($ids));
                    $msg = '已批量删除 ' . count($ids) . ' 条反馈';
                    $msgType = 'success';
                }
            }
        }
        // 批量关闭
        elseif ($action === 'bulk_close') {
            $ids = $_POST['ids'] ?? [];
            if (!empty($ids) && is_array($ids)) {
                $ids = array_filter(array_map('intval', $ids), function($v) { return $v > 0; });
                if (!empty($ids)) {
                    $placeholders = implode(',', array_fill(0, count($ids), '?'));
                    $db->query("UPDATE feedback SET status='closed' WHERE id IN ({$placeholders})", array_values($ids));
                    $msg = '已批量关闭 ' . count($ids) . ' 条反馈';
                    $msgType = 'success';
                }
            }
        }
        // 批量标记已解决
        elseif ($action === 'bulk_resolve') {
            $ids = $_POST['ids'] ?? [];
            if (!empty($ids) && is_array($ids)) {
                $ids = array_filter(array_map('intval', $ids), function($v) { return $v > 0; });
                if (!empty($ids)) {
                    $placeholders = implode(',', array_fill(0, count($ids), '?'));
                    $db->query("UPDATE feedback SET status='resolved' WHERE id IN ({$placeholders})", array_values($ids));
                    $msg = '已批量标记 ' . count($ids) . ' 条反馈为已解决';
                    $msgType = 'success';
                }
            }
        }
    }
}

// 筛选条件
$search       = trim($_GET['search'] ?? '');
$filterStatus = $_GET['status'] ?? '';
$filterType   = $_GET['type'] ?? '';
$page         = max(1, (int)($_GET['page'] ?? 1));
$perPage      = 20;
$offset       = ($page - 1) * $perPage;

$where = '1=1';
$params = [];
if ($search !== '') {
    $where .= ' AND (f.content LIKE :s OR u.nickname LIKE :s OR f.contact LIKE :s)';
    $params[':s'] = '%' . $search . '%';
}
if ($filterStatus !== '') {
    $where .= ' AND f.status = :st';
    $params[':st'] = $filterStatus;
}
if ($filterType !== '') {
    $where .= ' AND f.type = :ft';
    $params[':ft'] = $filterType;
}

$total = $db->fetch(
    'SELECT COUNT(*) AS cnt FROM feedback f LEFT JOIN users u ON f.user_id=u.id WHERE ' . $where,
    $params
);
$totalRows = (int)($total['cnt'] ?? 0);
$totalPages = max(1, ceil($totalRows / $perPage));

$feedbackList = $db->fetchAll(
    "SELECT f.*, u.nickname AS user_name, u.phone AS user_phone
     FROM feedback f
     LEFT JOIN users u ON f.user_id = u.id
     WHERE {$where}
     ORDER BY f.status ASC, f.created_at DESC
     LIMIT {$perPage} OFFSET {$offset}",
    $params
);

$typeLabels = ['bug' => 'Bug报告', 'suggest' => '建议', 'other' => '其他'];
$statusLabels = [
    'pending'  => ['待处理', 'badge-warning'],
    'replied'  => ['已回复', 'badge-success'],
    'resolved' => ['已解决', 'badge-info'],
    'closed'   => ['已关闭', 'badge-default'],
];

function buildUrl($p) {
    return 'feedback.php?' . http_build_query(array_filter($p, function($v) { return $v !== '' && $v !== 0; }));
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
        <input type="text" name="search" placeholder="搜索内容 / 用户 / 联系方式..."
               value="<?php echo htmlspecialchars($search); ?>">
      </div>
      <select name="status" class="filter-select">
        <option value="">全部状态</option>
        <?php foreach ($statusLabels as $k => $v): ?>
        <option value="<?php echo $k; ?>" <?php echo $filterStatus===$k?'selected':''; ?>><?php echo $v[0]; ?></option>
        <?php endforeach; ?>
      </select>
      <select name="type" class="filter-select">
        <option value="">全部类型</option>
        <?php foreach ($typeLabels as $k => $v): ?>
        <option value="<?php echo $k; ?>" <?php echo $filterType===$k?'selected':''; ?>><?php echo $v; ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn btn-primary btn-sm">筛选</button>
    </form>
  </div>
</div>

<!-- Feedback List -->
<div class="card">
  <div class="card-body no-padding">
    <?php if (empty($feedbackList)): ?>
    <div class="empty-state">
      <div class="empty-icon">📝</div>
      <h4>暂无反馈</h4>
    </div>
    <?php else: ?>
    <!-- Bulk Actions Bar -->
    <div id="bulkBar" style="display:none;padding:10px 16px;background:var(--primary-bg);border-bottom:1px solid var(--border);font-size:13px;align-items:center;gap:10px;">
      <span id="bulkCount">已选 0 条</span>
      <button class="btn btn-info btn-sm" onclick="bulkFeedbackAction('bulk_resolve')" style="margin-left:10px;">批量已解决</button>
      <button class="btn btn-warning btn-sm" onclick="bulkFeedbackAction('bulk_close')">批量关闭</button>
      <button class="btn btn-danger btn-sm" onclick="bulkFeedbackAction('bulk_delete')">批量删除</button>
    </div>
    <div class="table-wrapper">
      <table>
        <thead>
          <tr>
            <th style="width:36px;"><input type="checkbox" id="checkAll" onclick="toggleCheckAll(this)"></th>
            <th>ID</th>
            <th>用户</th>
            <th>类型</th>
            <th>内容</th>
            <th>联系方式</th>
            <th>状态</th>
            <th>回复</th>
            <th>时间</th>
            <th>操作</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($feedbackList as $f): ?>
          <tr>
            <td><input type="checkbox" class="row-check" value="<?php echo $f['id']; ?>" onchange="updateBulkBar()"></td>
            <td><?php echo $f['id']; ?></td>
            <td>
              <?php echo htmlspecialchars($f['user_name'] ?? '-'); ?>
              <?php if ($f['user_phone']): ?><br><span class="text-muted" style="font-size:11px;"><?php echo htmlspecialchars($f['user_phone']); ?></span><?php endif; ?>
            </td>
            <td>
              <span class="badge <?php echo $f['type']==='bug'?'badge-danger':($f['type']==='suggest'?'badge-info':'badge-default'); ?>">
                <?php echo $typeLabels[$f['type']] ?? '-'; ?>
              </span>
            </td>
            <td style="max-width:260px;white-space:normal;font-size:13px;"><?php echo htmlspecialchars($f['content']); ?></td>
            <td><?php echo htmlspecialchars($f['contact'] ?? '-'); ?></td>
            <td>
              <?php $sl = $statusLabels[$f['status']] ?? ['未知','badge-default']; ?>
              <span class="badge <?php echo $sl[1]; ?>"><?php echo $sl[0]; ?></span>
            </td>
            <td style="max-width:200px;white-space:normal;font-size:12px;color:var(--text-secondary);">
              <?php echo $f['reply'] ? htmlspecialchars($f['reply']) : '<span class="text-muted">-</span>'; ?>
            </td>
            <td><?php echo substr($f['created_at'], 0, 16); ?></td>
            <td class="actions">
              <?php if ($f['status'] === 'pending'): ?>
              <button class="btn btn-primary btn-sm" onclick="showReplyForm(<?php echo $f['id']; ?>, '')">回复</button>
              <?php else: ?>
              <button class="btn btn-outline btn-sm" onclick="showReplyForm(<?php echo $f['id']; ?>, <?php echo json_encode($f['reply'] ?? '', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>)">修改回复</button>
              <?php endif; ?>
              <select onchange="setFeedbackStatus(<?php echo $f['id']; ?>, this.value)" class="filter-select" style="padding:5px 10px;font-size:11px;width:auto;">
                <?php foreach ($statusLabels as $sk => $sv): ?>
                <option value="<?php echo $sk; ?>" <?php echo $f['status']===$sk?'selected':''; ?>><?php echo $sv[0]; ?></option>
                <?php endforeach; ?>
              </select>
              <form method="POST" style="display:inline" onsubmit="return confirm('确定删除此反馈？')">
                <input type="hidden" name="_token" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?php echo $f['id']; ?>">
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
      <span class="page-info">共 <?php echo $totalRows; ?> 条反馈，第 <?php echo $page; ?>/<?php echo $totalPages; ?> 页</span>
      <div class="page-links">
        <?php if ($page > 1): ?>
        <a href="<?php echo buildUrl(['search'=>$search,'status'=>$filterStatus,'type'=>$filterType,'page'=>$page-1]); ?>">&laquo; 上一页</a>
        <?php else: ?><span class="disabled">&laquo; 上一页</span><?php endif; ?>
        <?php $s=max(1,$page-3);$e=min($totalPages,$page+3); for($p=$s;$p<=$e;$p++): ?>
        <a href="<?php echo buildUrl(['search'=>$search,'status'=>$filterStatus,'type'=>$filterType,'page'=>$p]); ?>"
           class="<?php echo $p===$page?'active':''; ?>"><?php echo $p; ?></a>
        <?php endfor; ?>
        <?php if ($page < $totalPages): ?>
        <a href="<?php echo buildUrl(['search'=>$search,'status'=>$filterStatus,'type'=>$filterType,'page'=>$page+1]); ?>">下一页 &raquo;</a>
        <?php else: ?><span class="disabled">下一页 &raquo;</span><?php endif; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Reply Modal -->
<div class="modal-overlay" id="replyModal">
  <div class="modal">
    <div class="modal-header">
      <h3>回复反馈</h3>
      <button class="modal-close" onclick="closeReplyModal()">&times;</button>
    </div>
    <form method="POST" action="feedback.php">
      <input type="hidden" name="_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
      <input type="hidden" name="action" value="reply">
      <input type="hidden" name="id" id="reply_id" value="">
      <div class="modal-body">
        <div class="form-group">
          <label>回复内容 <span class="required">*</span></label>
          <textarea name="reply" id="reply_content" class="form-control" rows="5" required
                    placeholder="请输入回复内容..."></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeReplyModal()">取消</button>
        <button type="submit" class="btn btn-primary">发送回复</button>
      </div>
    </form>
  </div>
</div>

<?php
$extraJs = "
function showReplyForm(id, existingReply) {
  document.getElementById('reply_id').value = id;
  document.getElementById('reply_content').value = existingReply || '';
  document.getElementById('replyModal').classList.add('active');
}
function closeReplyModal() {
  document.getElementById('replyModal').classList.remove('active');
}
document.getElementById('replyModal').addEventListener('click', function(e) {
  if (e.target === this) closeReplyModal();
});

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
    bar.style.display = checks.length > 0 ? 'flex' : 'none';
    count.textContent = '已选 ' + checks.length + ' 条';
  }
}
function bulkFeedbackAction(action) {
  var checks = document.querySelectorAll('.row-check:checked');
  if (checks.length === 0) return;
  var msgMap = {bulk_resolve:'批量标记已解决', bulk_close:'批量关闭', bulk_delete:'批量删除'};
  Admin.confirm('确定' + msgMap[action] + '选中的 ' + checks.length + ' 条反馈？', function() {
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
function setFeedbackStatus(id, newStatus) {
  var form = document.createElement('form');
  form.method = 'POST';
  form.innerHTML = '<input type=\"hidden\" name=\"_token\" value=\"' + Admin.getCsrfToken() + '\">' +
    '<input type=\"hidden\" name=\"action\" value=\"set_status\">' +
    '<input type=\"hidden\" name=\"id\" value=\"' + id + '\">' +
    '<input type=\"hidden\" name=\"new_status\" value=\"' + newStatus + '\">';
  document.body.appendChild(form);
  form.submit();
}
";
?>

<?php include __DIR__ . '/footer.php'; ?>
