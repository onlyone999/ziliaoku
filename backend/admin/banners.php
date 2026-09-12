<?php
/**
 * 管理后台 - 轮播图管理
 * 列表、添加/编辑含图片上传、排序、切换状态
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
        // 删除
        if ($action === 'delete') {
            $bid = (int)($_POST['id'] ?? 0);
            if ($bid > 0) {
                $db->delete('banners', 'id = :id', [':id' => $bid]);
                AdminLog::log('delete', 'banner', $bid, '删除轮播图 #' . $bid);
                $msg = '轮播图已删除'; $msgType = 'success';
            }
        }
        // 切换状态
        elseif ($action === 'toggle_status') {
            $bid = (int)($_POST['id'] ?? 0);
            if ($bid > 0) {
                $cur = $db->fetch('SELECT status FROM banners WHERE id=:id', [':id' => $bid]);
                $nv = ($cur['status'] ?? 1) ? 0 : 1;
                $db->update('banners', ['status' => $nv], 'id = :id', [':id' => $bid]);
                $msg = $nv ? '轮播图已显示' : '轮播图已隐藏'; $msgType = 'success';
            }
        }
        // 批量启用
        elseif ($action === 'batch_enable') {
            $ids = $_POST['ids'] ?? [];
            if (!empty($ids) && is_array($ids)) {
                $ids = array_filter(array_map('intval', $ids), function($v) { return $v > 0; });
                if (!empty($ids)) {
                    $ph = implode(',', array_fill(0, count($ids), '?'));
                    $db->query("UPDATE banners SET status=1 WHERE id IN ({$ph})", array_values($ids));
                    $msg = '已批量启用 ' . count($ids) . ' 个轮播图'; $msgType = 'success';
                }
            }
        }
        // 批量禁用
        elseif ($action === 'batch_disable') {
            $ids = $_POST['ids'] ?? [];
            if (!empty($ids) && is_array($ids)) {
                $ids = array_filter(array_map('intval', $ids), function($v) { return $v > 0; });
                if (!empty($ids)) {
                    $ph = implode(',', array_fill(0, count($ids), '?'));
                    $db->query("UPDATE banners SET status=0 WHERE id IN ({$ph})", array_values($ids));
                    $msg = '已批量禁用 ' . count($ids) . ' 个轮播图'; $msgType = 'success';
                }
            }
        }
        // 批量删除
        elseif ($action === 'batch_delete') {
            $ids = $_POST['ids'] ?? [];
            if (!empty($ids) && is_array($ids)) {
                $ids = array_filter(array_map('intval', $ids), function($v) { return $v > 0; });
                if (!empty($ids)) {
                    $ph = implode(',', array_fill(0, count($ids), '?'));
                    $db->query("DELETE FROM banners WHERE id IN ({$ph})", array_values($ids));
                    $msg = '已批量删除 ' . count($ids) . ' 个轮播图'; $msgType = 'success';
                }
            }
        }
        // 保存
        elseif ($action === 'save') {
            $editId    = (int)($_POST['edit_id'] ?? 0);
            $title     = trim($_POST['title'] ?? '');
            $linkType  = $_POST['link_type'] ?? 'none';
            $linkValue = trim($_POST['link_value'] ?? '');
            $sortOrder = (int)($_POST['sort_order'] ?? 0);

            if ($title === '') {
                $msg = '标题为必填项'; $msgType = 'error';
            } else {
                // 处理图片上传
                $imageUrl = $_POST['existing_image'] ?? '';
                if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $allowed = ['jpg','jpeg','png','gif','webp'];
                    $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                    if (in_array($ext, $allowed)) {
                        $dir = __DIR__ . '/../uploads/banners/';
                        if (!is_dir($dir)) mkdir($dir, 0755, true);
                        $fname = date('His') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
                        move_uploaded_file($_FILES['image']['tmp_name'], $dir . $fname);
                        $imageUrl = '/uploads/banners/' . $fname;
                    }
                }

                if ($imageUrl === '' && $editId === 0) {
                    $msg = '轮播图图片为必填项'; $msgType = 'error';
                } else {
                    $data = [
                        'title'       => $title,
                        'image_url'   => $imageUrl,
                        'link_type'   => $linkType,
                        'link_value'  => $linkValue,
                        'sort_order'  => $sortOrder,
                    ];
                    if ($editId > 0) {
                        $db->update('banners', $data, 'id = :id', [':id' => $editId]);
                        $msg = '轮播图已更新';
                    } else {
                        $db->insert('banners', $data);
                        $msg = '轮播图已添加';
                    }
                    $msgType = 'success';
                }
            }
        }
    }
}

// 加载轮播图
$banners = $db->fetchAll('SELECT * FROM banners ORDER BY sort_order DESC, id DESC');

// 编辑表单
$editBanner = null;
if (isset($_GET['edit'])) {
    $eid = (int)$_GET['edit'];
    if ($eid > 0) {
        $editBanner = $db->fetch('SELECT * FROM banners WHERE id=:id', [':id' => $eid]);
    }
}

$linkTypeLabels = ['none' => '无链接', 'resource' => '资源', 'url' => '网址'];

include __DIR__ . '/header.php';
?>

<?php if ($msg): ?>
<div class="alert auto-dismiss" style="padding:12px 16px;border-radius:var(--radius);margin-bottom:20px;font-size:14px;
  <?php echo $msgType==='success' ? 'background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0' : 'background:#fef2f2;color:#b91c1c;border:1px solid #fecaca'; ?>">
  <?php echo htmlspecialchars($msg); ?>
</div>
<?php endif; ?>

<?php if ($editBanner || isset($_GET['add'])): ?>
<!-- Add/Edit Form -->
<div class="card mb-20">
  <div class="card-header">
    <h3><?php echo $editBanner ? '编辑轮播图' : '新增轮播图'; ?></h3>
    <a href="banners.php" class="btn btn-outline btn-sm">返回列表</a>
  </div>
  <div class="card-body">
    <form method="POST" enctype="multipart/form-data" action="banners.php">
      <input type="hidden" name="_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
      <input type="hidden" name="action" value="save">
      <?php if ($editBanner): ?>
      <input type="hidden" name="edit_id" value="<?php echo $editBanner['id']; ?>">
      <?php endif; ?>

      <div class="form-row">
        <div class="form-group">
          <label>标题 <span class="required">*</span></label>
          <input type="text" name="title" class="form-control" required
                 value="<?php echo htmlspecialchars($editBanner['title'] ?? ''); ?>">
        </div>
        <div class="form-group">
          <label>排序</label>
          <input type="number" name="sort_order" class="form-control" min="0"
                 value="<?php echo $editBanner['sort_order'] ?? '0'; ?>">
          <span class="form-hint">数值越大越靠前</span>
        </div>
      </div>

      <div class="form-group">
        <label>轮播图图片 <?php echo $editBanner ? '' : '<span class="required">*</span>'; ?></label>
        <input type="file" name="image" class="form-control" accept="image/*">
        <?php if (!empty($editBanner['image_url'])): ?>
        <div class="file-preview" id="banner-preview">
          <img src="<?php echo htmlspecialchars($editBanner['image_url']); ?>" alt="banner">
          <span class="file-name">当前轮播图图片</span>
          <input type="hidden" name="existing_image" value="<?php echo htmlspecialchars($editBanner['image_url']); ?>">
        </div>
        <?php endif; ?>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>链接类型</label>
          <select name="link_type" class="form-control">
            <?php foreach ($linkTypeLabels as $k => $v): ?>
            <option value="<?php echo $k; ?>" <?php echo (($editBanner['link_type'] ?? 'none') === $k) ? 'selected' : ''; ?>><?php echo $v; ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>链接值</label>
          <input type="text" name="link_value" class="form-control"
                 value="<?php echo htmlspecialchars($editBanner['link_value'] ?? ''); ?>"
                 placeholder="资源ID或网址">
          <span class="form-hint">资源类型填资源ID，网址类型填完整URL</span>
        </div>
      </div>

      <div class="d-flex gap-10">
        <button type="submit" class="btn btn-primary btn-lg">保存</button>
        <a href="banners.php" class="btn btn-outline btn-lg">取消</a>
      </div>
    </form>
  </div>
</div>

<?php else: ?>

<!-- Toolbar -->
<div class="toolbar">
  <div class="toolbar-left">
    <h3 style="font-size:16px;color:var(--text-secondary);">轮播图（共 <?php echo count($banners); ?> 个）</h3>
  </div>
  <div class="toolbar-right">
    <a href="banners.php?add=1" class="btn btn-primary">+ 新增轮播图</a>
  </div>
</div>

<!-- Banners Table -->
<div class="card">
  <div class="card-body no-padding">
    <?php if (empty($banners)): ?>
    <div class="empty-state">
      <div class="empty-icon">🖼️</div>
      <h4>暂无轮播图</h4>
    </div>
    <?php else: ?>
    <!-- Batch Actions Bar -->
    <div id="batchBar" style="display:none;padding:10px 16px;background:var(--primary-bg);border-bottom:1px solid var(--border);font-size:13px;align-items:center;gap:10px;">
      <span id="batchCount">已选 0 个</span>
      <button class="btn btn-success btn-sm" onclick="batchBannerAction('batch_enable')" style="margin-left:10px;">批量启用</button>
      <button class="btn btn-warning btn-sm" onclick="batchBannerAction('batch_disable')">批量禁用</button>
      <button class="btn btn-danger btn-sm" onclick="batchBannerAction('batch_delete')">批量删除</button>
    </div>
    <div class="table-wrapper">
      <table>
        <thead>
          <tr>
            <th style="width:36px;"><input type="checkbox" id="checkAll" onclick="toggleBannerCheckAll(this)"></th>
            <th>ID</th>
            <th>预览</th>
            <th>标题</th>
            <th>链接</th>
            <th>排序</th>
            <th>状态</th>
            <th>操作</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($banners as $b): ?>
          <tr>
            <td><input type="checkbox" class="row-check" value="<?php echo $b['id']; ?>" onchange="updateBannerBulkBar()"></td>
            <td><?php echo $b['id']; ?></td>
            <td>
              <?php if ($b['image_url']): ?>
              <img src="<?php echo htmlspecialchars($b['image_url']); ?>" style="width:120px;height:48px;object-fit:cover;border-radius:4px;">
              <?php endif; ?>
            </td>
            <td><?php echo htmlspecialchars($b['title']); ?></td>
            <td style="font-size:12px;">
              <span class="badge badge-default"><?php echo $linkTypeLabels[$b['link_type']] ?? '-'; ?></span>
              <?php if ($b['link_value']): ?>
              <br><?php echo htmlspecialchars($b['link_value']); ?>
              <?php endif; ?>
            </td>
            <td><?php echo $b['sort_order']; ?></td>
            <td>
              <span class="badge <?php echo $b['status'] ? 'badge-success' : 'badge-danger'; ?>">
                <?php echo $b['status'] ? '显示' : '隐藏'; ?>
              </span>
            </td>
            <td class="actions">
              <a href="banners.php?edit=<?php echo $b['id']; ?>" class="btn btn-outline btn-sm">编辑</a>
              <form method="POST" style="display:inline">
                <input type="hidden" name="_token" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="action" value="toggle_status">
                <input type="hidden" name="id" value="<?php echo $b['id']; ?>">
                <button class="btn btn-sm <?php echo $b['status'] ? 'btn-warning' : 'btn-success'; ?>">
                  <?php echo $b['status'] ? '隐藏' : '显示'; ?>
                </button>
              </form>
              <form method="POST" style="display:inline" onsubmit="return confirm('确定删除此轮播图？')">
                <input type="hidden" name="_token" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?php echo $b['id']; ?>">
                <button class="btn btn-danger btn-sm">删除</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php endif; ?>

<?php
$extraJs = "
document.addEventListener('DOMContentLoaded', function() {
  var imgInput = document.querySelector('input[name=\"image\"]');
  if (imgInput) {
    imgInput.addEventListener('change', function() {
      var file = this.files[0];
      if (!file || !file.type.startsWith('image/')) return;
      var reader = new FileReader();
      reader.onload = function(e) {
        var container = document.getElementById('banner-preview');
        if (!container) {
          container = document.createElement('div');
          container.id = 'banner-preview';
          container.className = 'file-preview';
          imgInput.parentElement.appendChild(container);
        }
        container.innerHTML = '<img src=\"' + e.target.result + '\" alt=\"banner\" style=\"width:120px;height:48px;object-fit:cover;border-radius:4px;\"><span class=\"file-name\">' + file.name + ' (' + Admin.formatSize(file.size) + ')</span>';
      };
      reader.readAsDataURL(file);
    });
  }
});

function toggleBannerCheckAll(master) {
  var checks = document.querySelectorAll('.row-check');
  for (var i = 0; i < checks.length; i++) { checks[i].checked = master.checked; }
  updateBannerBulkBar();
}
function updateBannerBulkBar() {
  var checks = document.querySelectorAll('.row-check:checked');
  var bar = document.getElementById('batchBar');
  var count = document.getElementById('batchCount');
  if (bar) {
    bar.style.display = checks.length > 0 ? 'flex' : 'none';
    count.textContent = '已选 ' + checks.length + ' 个';
  }
}
function batchBannerAction(action) {
  var checks = document.querySelectorAll('.row-check:checked');
  if (checks.length === 0) return;
  var msgMap = {batch_enable:'批量启用', batch_disable:'批量禁用', batch_delete:'批量删除'};
  Admin.confirm('确定' + msgMap[action] + '选中的 ' + checks.length + ' 个轮播图？', function() {
    var form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = '<input type=\"hidden\" name=\"_token\" value=\"' + Admin.getCsrfToken() + '\">' +
      '<input type=\"hidden\" name=\"action\" value=\"' + action + '\">';
    for (var i = 0; i < checks.length; i++) {
      var inp = document.createElement('input');
      inp.type = 'hidden'; inp.name = 'ids[]'; inp.value = checks[i].value;
      form.appendChild(inp);
    }
    document.body.appendChild(form);
    form.submit();
  });
}
";
?>

<?php include __DIR__ . '/footer.php'; ?>
