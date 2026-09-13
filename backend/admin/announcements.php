<?php
/**
 * 管理后台 - 公告管理
 * CRUD、置顶切换、状态切换、分页、自动建表
 */
session_start();
if (empty($_SESSION['admin_id'])) { header('Location: login.php'); exit; }

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/helper.php';
require_once __DIR__ . '/../core/AdminLog.php';
$db = Database::getInstance();
$csrfToken = $_SESSION['csrf_token'] ?? '';

// 自动创建 announcements 表
$db->query("CREATE TABLE IF NOT EXISTS `announcements` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL DEFAULT '',
    `content` TEXT,
    `type` ENUM('info','warning','success') NOT NULL DEFAULT 'info',
    `is_top` TINYINT(1) NOT NULL DEFAULT 0,
    `status` ENUM('draft','published','closed') NOT NULL DEFAULT 'draft',
    `admin_id` INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$msg = '';
$msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $token  = $_POST['_token'] ?? '';
    if ($token !== $csrfToken) { $msg = 'CSRF令牌无效'; $msgType = 'error'; }
    else {
        // 删除
        if ($action === 'delete') {
            $aid = (int)($_POST['id'] ?? 0);
            if ($aid > 0) {
                $db->delete('announcements', 'id = :id', [':id' => $aid]);
                AdminLog::log('delete', 'announcement', $aid, '删除公告 #' . $aid);
                $msg = '公告已删除'; $msgType = 'success';
            }
        }
        // 切换置顶
        elseif ($action === 'toggle_top') {
            $aid = (int)($_POST['id'] ?? 0);
            if ($aid > 0) {
                $cur = $db->fetch('SELECT is_top FROM announcements WHERE id=:id', [':id' => $aid]);
                $nv = ($cur['is_top'] ?? 0) ? 0 : 1;
                $db->update('announcements', ['is_top' => $nv], 'id = :id', [':id' => $aid]);
                $msg = $nv ? '已置顶' : '已取消置顶'; $msgType = 'success';
            }
        }
        // 切换状态
        elseif ($action === 'toggle_status') {
            $aid = (int)($_POST['id'] ?? 0);
            $newStatus = $_POST['new_status'] ?? '';
            if ($aid > 0 && in_array($newStatus, ['draft','published','closed'])) {
                $db->update('announcements', ['status' => $newStatus], 'id = :id', [':id' => $aid]);
                $statusLabels = ['draft'=>'草稿','published'=>'已发布','closed'=>'已关闭'];
                $msg = '状态已切换为：' . ($statusLabels[$newStatus] ?? $newStatus);
                $msgType = 'success';
                AdminLog::log('update', 'announcement', $aid, '切换公告状态为 ' . $newStatus);
            }
        }
        // 保存（新增/编辑）
        elseif ($action === 'save') {
            $editId   = (int)($_POST['edit_id'] ?? 0);
            $title    = trim($_POST['title'] ?? '');
            $content  = trim($_POST['content'] ?? '');
            $type     = $_POST['type'] ?? 'info';
            $isTop    = isset($_POST['is_top']) ? 1 : 0;
            $isVisible = isset($_POST['is_visible']) ? 1 : 0;
            $status   = $_POST['status'] ?? 'draft';
            $images   = trim($_POST['images_json'] ?? '[]');
            $attachments = trim($_POST['attachments_json'] ?? '[]');

            if ($title === '') {
                $msg = '标题为必填项'; $msgType = 'error';
            } elseif (!in_array($type, ['info','warning','success'])) {
                $msg = '类型无效'; $msgType = 'error';
            } elseif (!in_array($status, ['draft','published','closed'])) {
                $msg = '状态无效'; $msgType = 'error';
            } else {
                $data = [
                    'title'       => $title,
                    'content'     => $content,
                    'images'      => $images,
                    'attachments' => $attachments,
                    'type'        => $type,
                    'is_top'      => $isTop,
                    'is_visible'  => $isVisible,
                    'status'      => $status,
                    'admin_id'    => (int)$_SESSION['admin_id'],
                ];
                if ($editId > 0) {
                    $db->update('announcements', $data, 'id = :id', [':id' => $editId]);
                    AdminLog::log('update', 'announcement', $editId, '编辑公告：' . $title);
                    $msg = '公告已更新';
                } else {
                    $data['created_at'] = date('Y-m-d H:i:s');
                    $db->insert('announcements', $data);
                    AdminLog::log('create', 'announcement', 0, '新增公告：' . $title);
                    $msg = '公告已添加';
                }
                $msgType = 'success';
            }
        }
    }
}

// 分页与筛选
$search      = trim($_GET['search'] ?? '');
$filterType  = $_GET['type'] ?? '';
$filterStatus= $_GET['status'] ?? '';
$page        = max(1, (int)($_GET['page'] ?? 1));
$perPage     = getPerPage(15);
$offset      = ($page - 1) * $perPage;

$where = '1=1';
$params = [];
if ($search !== '') {
    $where .= ' AND (title LIKE :s OR content LIKE :s)';
    $params[':s'] = '%' . $search . '%';
}
if ($filterType !== '' && in_array($filterType, ['info','warning','success'])) {
    $where .= ' AND type = :tp';
    $params[':tp'] = $filterType;
}
if ($filterStatus !== '' && in_array($filterStatus, ['draft','published','closed'])) {
    $where .= ' AND status = :st';
    $params[':st'] = $filterStatus;
}

$totalRow = $db->fetch('SELECT COUNT(*) AS cnt FROM announcements WHERE ' . $where, $params);
$totalRows = (int)($totalRow['cnt'] ?? 0);
$totalPages = max(1, ceil($totalRows / $perPage));

$announcements = $db->fetchAll(
    "SELECT * FROM announcements WHERE {$where} ORDER BY is_top DESC, id DESC LIMIT {$perPage} OFFSET {$offset}",
    $params
);

// 编辑表单
$editAnn = null;
if (isset($_GET['edit'])) {
    $eid = (int)$_GET['edit'];
    if ($eid > 0) {
        $editAnn = $db->fetch('SELECT * FROM announcements WHERE id=:id', [':id' => $eid]);
    }
}

$typeLabels   = ['info' => ['通知', 'badge-info'], 'warning' => ['警告', 'badge-warning'], 'success' => ['成功', 'badge-success']];
$statusLabels = ['draft' => ['草稿', 'badge-default'], 'published' => ['已发布', 'badge-success'], 'closed' => ['已关闭', 'badge-danger']];

function buildUrl($p) {
    return 'announcements.php?' . http_build_query(array_filter($p, function($v) { return $v !== '' && $v !== 0; }));
}

include __DIR__ . '/header.php';
?>

<?php if ($msg): ?>
<div class="alert auto-dismiss" style="padding:12px 16px;border-radius:var(--radius);margin-bottom:20px;font-size:14px;
  <?php echo $msgType==='success' ? 'background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0' : 'background:#fef2f2;color:#b91c1c;border:1px solid #fecaca'; ?>">
  <?php echo htmlspecialchars($msg); ?>
</div>
<?php endif; ?>

<?php if ($editAnn || isset($_GET['add'])): ?>
<!-- Add/Edit Form -->
<div class="card mb-20">
  <div class="card-header">
    <h3><?php echo $editAnn ? '编辑公告' : '新增公告'; ?></h3>
    <a href="announcements.php" class="btn btn-outline btn-sm">返回列表</a>
  </div>
  <div class="card-body">
    <form method="POST" action="announcements.php">
      <input type="hidden" name="_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
      <input type="hidden" name="action" value="save">
      <?php if ($editAnn): ?>
      <input type="hidden" name="edit_id" value="<?php echo $editAnn['id']; ?>">
      <?php endif; ?>

      <div class="form-row">
        <div class="form-group" style="flex:2;">
          <label>标题 <span class="required">*</span></label>
          <input type="text" name="title" class="form-control" required maxlength="255"
                 value="<?php echo htmlspecialchars($editAnn['title'] ?? ''); ?>"
                 placeholder="请输入公告标题">
        </div>
        <div class="form-group">
          <label>类型</label>
          <select name="type" class="form-control">
            <option value="info" <?php echo (($editAnn['type'] ?? 'info') === 'info') ? 'selected' : ''; ?>>通知</option>
            <option value="warning" <?php echo (($editAnn['type'] ?? '') === 'warning') ? 'selected' : ''; ?>>警告</option>
            <option value="success" <?php echo (($editAnn['type'] ?? '') === 'success') ? 'selected' : ''; ?>>成功</option>
          </select>
        </div>
      </div>

      <div class="form-group">
        <label>内容</label>
        <textarea name="content" class="form-control" rows="8"
                  placeholder="请输入公告内容"><?php echo htmlspecialchars($editAnn['content'] ?? ''); ?></textarea>
      </div>

      <!-- 图片上传 -->
      <div class="form-group">
        <label>公告图片 <small class="text-muted">（可多张，支持 jpg/png/gif）</small></label>
        <div id="image-preview" class="upload-preview">
          <?php
          $existingImages = [];
          if ($editAnn && !empty($editAnn['images'])) {
              $existingImages = json_decode($editAnn['images'], true) ?: [];
          }
          foreach ($existingImages as $img): ?>
          <div class="upload-item">
            <img src="<?php echo htmlspecialchars($img); ?>" onerror="this.src='/static/placeholder.png'">
            <button type="button" class="upload-remove" onclick="removeUploadItem(this)">&times;</button>
          </div>
          <?php endforeach; ?>
        </div>
        <div class="upload-btn-wrap">
          <input type="file" id="image-input" accept="image/*" multiple style="display:none">
          <button type="button" class="btn btn-outline btn-sm" onclick="document.getElementById('image-input').click()">+ 添加图片</button>
        </div>
        <input type="hidden" name="images_json" id="images_json" value="<?php echo htmlspecialchars($editAnn['images'] ?? '[]'); ?>">
      </div>

      <!-- 附件上传 -->
      <div class="form-group">
        <label>附件 <small class="text-muted">（支持 pdf/doc/xls/zip/rar 等）</small></label>
        <div id="attachment-preview" class="upload-preview upload-attach-list">
          <?php
          $existingAttach = [];
          if ($editAnn && !empty($editAnn['attachments'])) {
              $existingAttach = json_decode($editAnn['attachments'], true) ?: [];
          }
          foreach ($existingAttach as $at): ?>
          <div class="attach-item">
            <span class="attach-icon">📎</span>
            <span class="attach-name"><?php echo htmlspecialchars(basename($at)); ?></span>
            <button type="button" class="upload-remove" onclick="removeAttachItem(this)">&times;</button>
          </div>
          <?php endforeach; ?>
        </div>
        <div class="upload-btn-wrap">
          <input type="file" id="attachment-input" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip,.rar,.7z,.txt,.csv" multiple style="display:none">
          <button type="button" class="btn btn-outline btn-sm" onclick="document.getElementById('attachment-input').click()">+ 添加附件</button>
        </div>
        <input type="hidden" name="attachments_json" id="attachments_json" value="<?php echo htmlspecialchars($editAnn['attachments'] ?? '[]'); ?>">
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>状态</label>
          <select name="status" class="form-control">
            <option value="draft" <?php echo (($editAnn['status'] ?? 'draft') === 'draft') ? 'selected' : ''; ?>>草稿</option>
            <option value="published" <?php echo (($editAnn['status'] ?? '') === 'published') ? 'selected' : ''; ?>>已发布</option>
            <option value="closed" <?php echo (($editAnn['status'] ?? '') === 'closed') ? 'selected' : ''; ?>>已关闭</option>
          </select>
        </div>
        <div class="form-group">
          <label>&nbsp;</label>
          <label class="checkbox-label" style="display:flex;align-items:center;gap:8px;height:38px;">
            <input type="checkbox" name="is_top" value="1"
                   <?php echo (!empty($editAnn['is_top'])) ? 'checked' : ''; ?>>
            置顶显示
          </label>
        </div>
        <div class="form-group">
          <label>&nbsp;</label>
          <label class="checkbox-label" style="display:flex;align-items:center;gap:8px;height:38px;">
            <input type="checkbox" name="is_visible" value="1"
                   <?php echo (($editAnn['is_visible'] ?? 1) == 1) ? 'checked' : ''; ?>>
            前端显示
          </label>
        </div>
      </div>

      <div class="d-flex gap-10">
        <button type="submit" class="btn btn-primary btn-lg">保存</button>
        <a href="announcements.php" class="btn btn-outline btn-lg">取消</a>
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
        <input type="text" name="search" placeholder="搜索标题/内容..."
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
    <a href="announcements.php?add=1" class="btn btn-primary">+ 新增公告</a>
  </div>
</div>

<!-- Announcements Table -->
<div class="card">
  <div class="card-body no-padding">
    <?php if (empty($announcements)): ?>
    <div class="empty-state">
      <div class="empty-icon">📢</div>
      <h4>暂无公告</h4>
    </div>
    <?php else: ?>
    <div class="table-wrapper">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>标题</th>
            <th>类型</th>
            <th>状态</th>
            <th>置顶</th>
            <th>显示</th>
            <th>创建时间</th>
            <th>操作</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($announcements as $a): ?>
          <tr>
            <td><?php echo $a['id']; ?></td>
            <td style="max-width:280px;">
              <?php echo htmlspecialchars($a['title']); ?>
              <?php if ($a['content']): ?>
              <br><small class="text-muted"><?php echo htmlspecialchars(mb_substr($a['content'], 0, 60)); ?>...</small>
              <?php endif; ?>
            </td>
            <td>
              <?php $tl = $typeLabels[$a['type']] ?? ['未知','badge-default']; ?>
              <span class="badge <?php echo $tl[1]; ?>"><?php echo $tl[0]; ?></span>
            </td>
            <td>
              <?php $sl = $statusLabels[$a['status']] ?? ['未知','badge-default']; ?>
              <span class="badge <?php echo $sl[1]; ?>"><?php echo $sl[0]; ?></span>
            </td>
            <td>
              <?php if ($a['is_top']): ?>
              <span class="badge badge-warning">📌 已置顶</span>
              <?php else: ?>
              <span class="text-muted">-</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if (($a['is_visible'] ?? 1) == 1): ?>
              <span class="badge badge-success">显示</span>
              <?php else: ?>
              <span class="badge badge-default">隐藏</span>
              <?php endif; ?>
            </td>
            <td><?php echo substr($a['created_at'], 0, 16); ?></td>
            <td class="actions">
              <a href="announcements.php?edit=<?php echo $a['id']; ?>" class="btn btn-outline btn-sm">编辑</a>
              <!-- 置顶切换 -->
              <form method="POST" style="display:inline">
                <input type="hidden" name="_token" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="action" value="toggle_top">
                <input type="hidden" name="id" value="<?php echo $a['id']; ?>">
                <button class="btn btn-sm <?php echo $a['is_top'] ? 'btn-warning' : 'btn-outline'; ?>">
                  <?php echo $a['is_top'] ? '取消置顶' : '置顶'; ?>
                </button>
              </form>
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
              <?php elseif ($a['status'] === 'closed'): ?>
              <form method="POST" style="display:inline">
                <input type="hidden" name="_token" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="action" value="toggle_status">
                <input type="hidden" name="id" value="<?php echo $a['id']; ?>">
                <input type="hidden" name="new_status" value="published">
                <button class="btn btn-success btn-sm">重新发布</button>
              </form>
              <?php endif; ?>
              <!-- 删除 -->
              <form method="POST" style="display:inline" onsubmit="return confirm('确定删除此公告？')">
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

    <!-- Pagination -->
    <div class="pagination">
      <span class="page-info">共 <?php echo $totalRows; ?> 条公告，第 <?php echo $page; ?>/<?php echo $totalPages; ?> 页</span>
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

<style>
.upload-preview { display:flex; flex-wrap:wrap; gap:10px; margin-bottom:8px; min-height:20px; }
.upload-item { position:relative; width:100px; height:100px; border-radius:8px; overflow:hidden; border:1px solid #e5e7eb; }
.upload-item img { width:100%; height:100%; object-fit:cover; }
.upload-remove { position:absolute; top:2px; right:2px; width:22px; height:22px; border-radius:50%; background:rgba(0,0,0,0.6); color:#fff; border:none; cursor:pointer; font-size:14px; line-height:1; display:flex; align-items:center; justify-content:center; }
.upload-remove:hover { background:#ef4444; }
.upload-btn-wrap { margin-bottom:10px; }
.upload-attach-list { flex-direction:column; }
.attach-item { display:flex; align-items:center; gap:8px; padding:8px 12px; background:#f9fafb; border:1px solid #e5e7eb; border-radius:6px; font-size:13px; }
.attach-icon { font-size:16px; }
.attach-name { flex:1; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; color:#374151; }
.upload-item.uploading { opacity:0.5; }
.upload-item.uploading::after { content:'上传中'; position:absolute; inset:0; display:flex; align-items:center; justify-content:center; background:rgba(0,0,0,0.3); color:#fff; font-size:12px; }
.attach-item.uploading { opacity:0.5; }
</style>

<script>
// ============ 图片上传 ============
var imageList = <?php echo $editAnn['images'] ?? '[]'; ?>;

document.getElementById('image-input').addEventListener('change', function(e) {
    var files = e.target.files;
    for (var i = 0; i < files.length; i++) {
        uploadFile(files[i], 'image');
    }
    e.target.value = '';
});

function uploadFile(file, fileType) {
    var formData = new FormData();
    formData.append('file', file);
    formData.append('type', fileType);

    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'upload_api.php', true);
    xhr.onload = function() {
        if (xhr.status === 200) {
            try {
                var res = JSON.parse(xhr.responseText);
                if (res.code === 0 && res.data && res.data.url) {
                    if (fileType === 'image') {
                        imageList.push(res.data.url);
                        document.getElementById('images_json').value = JSON.stringify(imageList);
                        addImagePreview(res.data.url);
                    } else {
                        var attachList = JSON.parse(document.getElementById('attachments_json').value || '[]');
                        attachList.push({ url: res.data.url, name: file.name });
                        document.getElementById('attachments_json').value = JSON.stringify(attachList);
                        addAttachPreview(res.data.url, file.name);
                    }
                } else {
                    alert('上传失败: ' + (res.message || '未知错误'));
                }
            } catch(e) {
                alert('上传失败: 响应解析错误');
            }
        } else {
            alert('上传失败: HTTP ' + xhr.status);
        }
    };
    xhr.onerror = function() { alert('上传失败: 网络错误'); };
    xhr.send(formData);
}

function addImagePreview(url) {
    var div = document.createElement('div');
    div.className = 'upload-item';
    div.innerHTML = '<img src="' + url + '" onerror="this.src=\'/static/placeholder.png\'">' +
        '<button type="button" class="upload-remove" onclick="removeUploadItem(this)">&times;</button>';
    document.getElementById('image-preview').appendChild(div);
}

function removeUploadItem(btn) {
    var item = btn.parentElement;
    var img = item.querySelector('img');
    var src = img ? img.getAttribute('src') : '';
    imageList = imageList.filter(function(u) { return u !== src; });
    document.getElementById('images_json').value = JSON.stringify(imageList);
    item.remove();
}

// ============ 附件上传 ============
document.getElementById('attachment-input').addEventListener('change', function(e) {
    var files = e.target.files;
    for (var i = 0; i < files.length; i++) {
        uploadFile(files[i], 'attachment');
    }
    e.target.value = '';
});

function addAttachPreview(url, name) {
    var div = document.createElement('div');
    div.className = 'attach-item';
    div.innerHTML = '<span class="attach-icon">📎</span>' +
        '<span class="attach-name">' + name + '</span>' +
        '<button type="button" class="upload-remove" onclick="removeAttachItem(this)">&times;</button>';
    document.getElementById('attachment-preview').appendChild(div);
}

function removeAttachItem(btn) {
    var item = btn.parentElement;
    var nameEl = item.querySelector('.attach-name');
    var name = nameEl ? nameEl.textContent : '';
    var attachList = JSON.parse(document.getElementById('attachments_json').value || '[]');
    attachList = attachList.filter(function(a) { return a.name !== name; });
    document.getElementById('attachments_json').value = JSON.stringify(attachList);
    item.remove();
}
</script>

<?php include __DIR__ . '/footer.php'; ?>
