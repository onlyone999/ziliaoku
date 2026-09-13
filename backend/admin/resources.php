<?php
/**
 * 管理后台 - 资源管理
 * 列表、添加、编辑、删除、审核通过/拒绝、切换推荐/置顶
 */
session_start();
if (empty($_SESSION['admin_id'])) { header('Location: login.php'); exit; }

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/helper.php';
require_once __DIR__ . '/../core/AdminLog.php';
$db = Database::getInstance();
$adminId = $_SESSION['admin_id'];
$csrfToken = $_SESSION['csrf_token'] ?? '';

$msg = '';
$msgType = '';

// ---------- 处理POST操作 ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $token = $_POST['_token'] ?? '';
    if ($token !== $csrfToken) { $msg = 'CSRF令牌无效'; $msgType = 'error'; }
    else {
        // 审核通过 / 拒绝
        if ($action === 'approve' || $action === 'reject') {
            $rid = (int)($_POST['id'] ?? 0);
            if ($rid > 0) {
                $newStatus = $action === 'approve' ? 'approved' : 'rejected';
                $db->update('resources', ['status' => $newStatus], 'id = :id', [':id' => $rid]);
                AdminLog::log($action, 'resource', $rid, ($action === 'approve' ? '审核通过' : '审核拒绝') . '资源 #' . $rid);
                $msg = $action === 'approve' ? '资源已审核通过' : '资源已拒绝';
                $msgType = 'success';
            }
        }
        // 切换推荐状态
        elseif ($action === 'toggle_recommend') {
            $rid = (int)($_POST['id'] ?? 0);
            if ($rid > 0) {
                $cur = $db->fetch('SELECT is_recommended FROM resources WHERE id=:id', [':id' => $rid]);
                $nv = ($cur['is_recommended'] ?? 0) ? 0 : 1;
                $db->update('resources', ['is_recommended' => $nv], 'id = :id', [':id' => $rid]);
                $msg = $nv ? '已设为推荐' : '已取消推荐';
                $msgType = 'success';
            }
        }
        // 切换置顶状态
        elseif ($action === 'toggle_top') {
            $rid = (int)($_POST['id'] ?? 0);
            if ($rid > 0) {
                $cur = $db->fetch('SELECT is_top FROM resources WHERE id=:id', [':id' => $rid]);
                $nv = ($cur['is_top'] ?? 0) ? 0 : 1;
                $db->update('resources', ['is_top' => $nv], 'id = :id', [':id' => $rid]);
                $msg = $nv ? '已设为置顶' : '已取消置顶';
                $msgType = 'success';
            }
        }
        // 删除
        elseif ($action === 'delete') {
            $rid = (int)($_POST['id'] ?? 0);
            if ($rid > 0) {
                $db->delete('resource_tags', 'resource_id = :id', [':id' => $rid]);
                $db->delete('resource_files', 'resource_id = :id', [':id' => $rid]);
                $db->delete('resources', 'id = :id', [':id' => $rid]);
                AdminLog::log('delete', 'resource', $rid, '删除资源 #' . $rid);
                $msg = '资源已删除'; $msgType = 'success';
            }
        }
        // 批量操作
        elseif ($action === 'batch_approve') {
            $ids = array_filter(array_map('intval', explode(',', $_POST['ids'] ?? '')));
            if (!empty($ids)) {
                $placeholders = implode(',', $ids);
                $db->query("UPDATE resources SET status='approved' WHERE id IN ($placeholders)");
                $msg = '已批量通过 ' . count($ids) . ' 个资源'; $msgType = 'success';
            }
        }
        elseif ($action === 'batch_delete') {
            $ids = array_filter(array_map('intval', explode(',', $_POST['ids'] ?? '')));
            if (!empty($ids)) {
                $placeholders = implode(',', $ids);
                $db->query("DELETE FROM resource_tags WHERE resource_id IN ($placeholders)");
                $db->query("DELETE FROM resource_files WHERE resource_id IN ($placeholders)");
                $db->query("DELETE FROM resources WHERE id IN ($placeholders)");
                $msg = '已批量删除 ' . count($ids) . ' 个资源'; $msgType = 'success';
            }
        }
        // 保存（添加/编辑）
        elseif ($action === 'save') {
            $editId     = (int)($_POST['edit_id'] ?? 0);
            $title      = trim($_POST['title'] ?? '');
            $categoryId = (int)($_POST['category_id'] ?? 0);
            $description = trim($_POST['description'] ?? '');
            $priceType  = $_POST['price_type'] ?? 'free';
            $price      = (float)($_POST['price'] ?? 0);
            $vipPrice   = (float)($_POST['vip_price'] ?? 0);
            $pointsPrice = (int)($_POST['points_price'] ?? 0);
            $vipFreeLevels = $priceType === 'member_free' ? implode(',', $_POST['vip_free_levels'] ?? []) : null;
            $status     = $_POST['status'] ?? 'pending';
            $isTop      = isset($_POST['is_top']) ? 1 : 0;
            $isRec      = isset($_POST['is_recommended']) ? 1 : 0;
            $tagStr     = trim($_POST['tags'] ?? '');

            if ($title === '' || $categoryId <= 0) {
                $msg = '标题和分类为必填项'; $msgType = 'error';
            } else {
                // 处理封面上传
                $coverUrl = $_POST['existing_cover'] ?? '';
                if (!empty($_FILES['cover']['name']) && $_FILES['cover']['error'] === UPLOAD_ERR_OK) {
                    $allowed = ['jpg','jpeg','png','gif','webp'];
                    $ext = strtolower(pathinfo($_FILES['cover']['name'], PATHINFO_EXTENSION));
                    if (in_array($ext, $allowed)) {
                        $dir = __DIR__ . '/../uploads/covers/';
                        if (!is_dir($dir)) mkdir($dir, 0755, true);
                        $fname = date('His') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
                        if (move_uploaded_file($_FILES['cover']['tmp_name'], $dir . $fname)) {
                            $coverUrl = '/uploads/covers/' . $fname;
                        }
                    }
                }

                // 处理主文件上传
                $fileUrl = $_POST['existing_file'] ?? '';
                $fileSize = (int)($_POST['existing_file_size'] ?? 0);
                $fileType = $_POST['existing_file_type'] ?? '';
                $fileSuffix = $_POST['existing_file_suffix'] ?? '';
                if (!empty($_FILES['file']['name']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
                    $dir = __DIR__ . '/../uploads/resources/' . date('Ymd') . '/';
                    if (!is_dir($dir)) mkdir($dir, 0755, true);
                    $fname = date('His') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
                    if (move_uploaded_file($_FILES['file']['tmp_name'], $dir . $fname)) {
                        $fileUrl = '/uploads/resources/' . date('Ymd') . '/' . $fname;
                        $fileSize = $_FILES['file']['size'];
                        $fileSuffix = $ext;
                        $finfo = new finfo(FILEINFO_MIME_TYPE);
                        $fileType = $finfo->file($dir . $fname);
                    }
                }

                $data = [
                    'title'         => $title,
                    'category_id'   => $categoryId,
                    'description'   => $description,
                    'cover_url'     => $coverUrl,
                    'file_url'      => $fileUrl,
                    'file_size'     => $fileSize,
                    'file_suffix'   => $fileSuffix,
                    'price_type'    => $priceType,
                    'price'         => $price,
                    'vip_price'     => $vipPrice,
                    'points_price'  => $pointsPrice,
                    'vip_free_levels' => $vipFreeLevels,
                    'status'        => $status,
                    'is_top'        => $isTop,
                    'is_recommended'=> $isRec,
                ];

                if ($editId > 0) {
                    $db->update('resources', $data, 'id = :id', [':id' => $editId]);
                    $rid = $editId;
                } else {
                    $data['admin_id'] = $adminId;
                    $rid = $db->insert('resources', $data);
                }

                // 更新标签
                $db->delete('resource_tags', 'resource_id = :id', [':id' => $rid]);
                if ($tagStr !== '') {
                    $tags = array_unique(array_filter(array_map('trim', explode(',', $tagStr))));
                    foreach ($tags as $tagName) {
                        $existing = $db->fetch('SELECT id FROM tags WHERE name=:n LIMIT 1', [':n' => $tagName]);
                        if (empty($existing)) {
                            $tagId = $db->insert('tags', ['name' => $tagName]);
                        } else {
                            $tagId = $existing['id'];
                        }
                        $db->query('INSERT IGNORE INTO resource_tags (resource_id, tag_id) VALUES (:r,:t)', [':r'=>$rid, ':t'=>$tagId]);
                    }
                }

                // 处理附加文件
                if (!empty($_FILES['extra_files']['name'][0])) {
                    $extraDir = __DIR__ . '/../uploads/resources/' . date('Ymd') . '/';
                    if (!is_dir($extraDir)) mkdir($extraDir, 0755, true);
                    $fileCount = count($_FILES['extra_files']['name']);
                    $maxSort = $db->fetch('SELECT COALESCE(MAX(sort_order),0) AS m FROM resource_files WHERE resource_id=:id', [':id'=>$rid]);
                    $sortStart = (int)($maxSort['m'] ?? 0) + 1;
                    $allowedExts = ['pdf','doc','docx','xls','xlsx','ppt','pptx','txt','md','csv','zip','rar','7z','mp4','mp3','jpg','jpeg','png','gif','webp','json','xml','html','css','js','py','java','sql'];
                    $allowedMimes = ['application/pdf','application/msword','application/vnd.openxmlformats-officedocument.wordprocessingml.document','application/vnd.ms-excel','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet','application/vnd.ms-powerpoint','application/vnd.openxmlformats-officedocument.presentationml.presentation','text/plain','text/markdown','text/csv','application/zip','application/x-rar-compressed','application/x-7z-compressed','video/mp4','audio/mpeg','image/jpeg','image/png','image/gif','image/webp','application/json','text/xml','text/html','text/css','application/javascript','text/x-python','text/x-java','application/sql','application/octet-stream'];
                    $finfo = new finfo(FILEINFO_MIME_TYPE);
                    $sortIdx = 0;
                    for ($i = 0; $i < $fileCount; $i++) {
                        if ($_FILES['extra_files']['error'][$i] === UPLOAD_ERR_OK) {
                            $ext = strtolower(pathinfo($_FILES['extra_files']['name'][$i], PATHINFO_EXTENSION));
                            $mime = $finfo->file($_FILES['extra_files']['tmp_name'][$i]);
                            if (!in_array($ext, $allowedExts)) continue;
                            if (!in_array($mime, $allowedMimes)) continue;
                            $fname = date('His') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
                            move_uploaded_file($_FILES['extra_files']['tmp_name'][$i], $extraDir . $fname);
                            $db->insert('resource_files', [
                                'resource_id' => $rid,
                                'file_name'   => $_FILES['extra_files']['name'][$i],
                                'file_url'    => '/uploads/resources/' . date('Ymd') . '/' . $fname,
                                'file_size'   => $_FILES['extra_files']['size'][$i],
                                'file_type'   => $mime,
                                'sort_order'  => $sortStart + $sortIdx,
                            ]);
                            $sortIdx++;
                        }
                    }
                }

                $msg = $editId > 0 ? '资源已更新' : '资源已添加';
                $msgType = 'success';
            }
        }
    }
}

// ---------- 筛选条件 ----------
$search     = trim($_GET['search'] ?? '');
$filterStatus   = $_GET['status'] ?? '';
$filterCategory = (int)($_GET['category'] ?? 0);
$filterPriceType = $_GET['price_type'] ?? '';
$sortBy = $_GET['sort_by'] ?? '';
$rankView = isset($_GET['rank_view']) && $_GET['rank_view'] === '1';
$page       = max(1, (int)($_GET['page'] ?? 1));
$perPage    = getPerPage(20);
$offset     = ($page - 1) * $perPage;

// 构建WHERE
$where = '1=1';
$params = [];
if ($search !== '') {
    $where .= ' AND (r.title LIKE :search OR r.description LIKE :search)';
    $params[':search'] = '%' . $search . '%';
}
if ($filterStatus !== '') {
    $where .= ' AND r.status = :status';
    $params[':status'] = $filterStatus;
}
if ($filterCategory > 0) {
    $where .= ' AND r.category_id = :cat';
    $params[':cat'] = $filterCategory;
}
if ($filterPriceType !== '') {
    $where .= ' AND r.price_type = :pt';
    $params[':pt'] = $filterPriceType;
}

// CSV导出
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $expWhere = '1=1';
    $expParams = [];
    if ($search !== '') {
        $expWhere .= ' AND (r.title LIKE :search OR r.description LIKE :search)';
        $expParams[':search'] = '%' . $search . '%';
    }
    if ($filterStatus !== '') {
        $expWhere .= ' AND r.status = :status';
        $expParams[':status'] = $filterStatus;
    }
    if ($filterCategory > 0) {
        $expWhere .= ' AND r.category_id = :cat';
        $expParams[':cat'] = $filterCategory;
    }
    if ($filterPriceType !== '') {
        $expWhere .= ' AND r.price_type = :pt';
        $expParams[':pt'] = $filterPriceType;
    }
    $expResources = $db->fetchAll(
        "SELECT r.*, c.name AS category_name
         FROM resources r
         LEFT JOIN categories c ON r.category_id = c.id
         WHERE {$expWhere}
         ORDER BY r.created_at DESC",
        $expParams
    );
    $expStatusLabels = ['pending'=>'待审核','approved'=>'已通过','rejected'=>'已拒绝','offline'=>'已下线'];
    $expPriceTypeLabels = ['free'=>'免费','paid'=>'付费','member_free'=>'VIP等级免费'];
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=资源列表_' . date('Ymd') . '.csv');
    echo "\xEF\xBB\xBF"; // UTF-8 BOM
    $fp = fopen('php://output', 'w');
    fputcsv($fp, ['ID','标题','分类','价格类型','价格','状态','下载次数','浏览次数','创建时间']);
    foreach ($expResources as $r) {
        fputcsv($fp, [
            $r['id'],
            $r['title'],
            $r['category_name'] ?? '',
            $expPriceTypeLabels[$r['price_type']] ?? $r['price_type'],
            number_format((float)$r['price'], 2),
            $expStatusLabels[$r['status']] ?? $r['status'],
            (int)$r['download_count'],
            (int)$r['view_count'],
            $r['created_at'],
        ]);
    }
    fclose($fp);
    exit;
}

$total = $db->fetch('SELECT COUNT(*) AS cnt FROM resources r WHERE ' . $where, $params);
$totalRows = (int)($total['cnt'] ?? 0);
$totalPages = max(1, ceil($totalRows / $perPage));

$orderClause = 'r.is_top DESC, r.sort_order DESC, r.created_at DESC';
if ($rankView) {
    $orderClause = '(r.up_votes - r.down_votes) DESC, r.up_votes DESC, r.created_at DESC';
} elseif ($sortBy === 'up_votes_desc') {
    $orderClause = 'r.up_votes DESC, r.created_at DESC';
} elseif ($sortBy === 'up_votes_asc') {
    $orderClause = 'r.up_votes ASC, r.created_at DESC';
} elseif ($sortBy === 'down_votes_desc') {
    $orderClause = 'r.down_votes DESC, r.created_at DESC';
} elseif ($sortBy === 'net_votes_desc') {
    $orderClause = '(r.up_votes - r.down_votes) DESC, r.created_at DESC';
}

$resources = $db->fetchAll(
    "SELECT r.*, c.name AS category_name
     FROM resources r
     LEFT JOIN categories c ON r.category_id = c.id
     WHERE {$where}
     ORDER BY {$orderClause}
     LIMIT {$perPage} OFFSET {$offset}",
    $params
);

// 用于筛选和表单的分类列表（构建层级结构）
$allCats = $db->fetchAll('SELECT id, name, parent_id FROM categories WHERE status=1 ORDER BY sort_order DESC, id ASC');
$catChildren = [];
foreach ($allCats as $c) {
    $catChildren[(int)$c['parent_id']][] = $c;
}
// 递归构建带缩进的分类列表
$categories = [];
function buildCatList($parentId, $catChildren, $level, &$categories) {
    if (empty($catChildren[$parentId])) return;
    foreach ($catChildren[$parentId] as $cat) {
        $cat['level'] = $level;
        $categories[] = $cat;
        buildCatList((int)$cat['id'], $catChildren, $level + 1, $categories);
    }
}
buildCatList(0, $catChildren, 0, $categories);

// 编辑表单：加载资源（如果GET中有edit_id）
$editResource = null;
$editTags = [];
$editFiles = [];
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    if ($editId > 0) {
        $editResource = $db->fetch('SELECT * FROM resources WHERE id=:id', [':id' => $editId]);
        if ($editResource) {
            $tagRows = $db->fetchAll(
                'SELECT t.name FROM resource_tags rt JOIN tags t ON rt.tag_id=t.id WHERE rt.resource_id=:id',
                [':id' => $editId]
            );
            $editTags = array_column($tagRows, 'name');
            $editFiles = $db->fetchAll('SELECT * FROM resource_files WHERE resource_id=:id ORDER BY sort_order', [':id' => $editId]);
        }
    }
}

$statusLabels = [
    'pending'  => ['待审核',  'badge-warning'],
    'approved' => ['已通过',  'badge-success'],
    'rejected' => ['已拒绝',  'badge-danger'],
    'offline'  => ['已下线',  'badge-default'],
];

$priceTypeLabels = ['free' => '免费', 'paid' => '付费', 'member_free' => 'VIP等级免费'];

// 构建分页URL
function buildUrl($params) {
    return 'resources.php?' . http_build_query(array_filter($params, function($v) { return $v !== '' && $v !== 0; }));
}

include __DIR__ . '/header.php';
?>

<?php if ($msg): ?>
<div class="alert auto-dismiss" style="padding:14px 18px;border-radius:10px;margin-bottom:20px;font-size:14px;display:flex;align-items:center;gap:10px;
  <?php echo $msgType==='success' ? 'background:linear-gradient(135deg,#f0fdf4,#dcfce7);color:#15803d;border:1px solid #bbf7d0;box-shadow:0 2px 8px rgba(34,197,94,.1)' : 'background:linear-gradient(135deg,#fef2f2,#fee2e2);color:#b91c1c;border:1px solid #fecaca;box-shadow:0 2px 8px rgba(239,68,68,.1)'; ?>">
  <span style="font-size:18px"><?php echo $msgType==='success' ? '&#10003;' : '&#10007;'; ?></span>
  <?php echo htmlspecialchars($msg); ?>
</div>
<?php endif; ?>

<!-- Add/Edit Form -->
<?php if ($editResource || isset($_GET['add'])): ?>
<div class="card mb-20">
  <div class="card-header">
    <h3><?php echo $editResource ? '编辑资源' : '新增资源'; ?></h3>
    <a href="resources.php" class="btn btn-outline btn-sm">返回列表</a>
  </div>
  <div class="card-body">
    <form method="POST" enctype="multipart/form-data" action="resources.php">
      <input type="hidden" name="_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
      <input type="hidden" name="action" value="save">
      <?php if ($editResource): ?>
      <input type="hidden" name="edit_id" value="<?php echo $editResource['id']; ?>">
      <?php endif; ?>

      <div class="form-row">
        <div class="form-group">
          <label>标题 <span class="required">*</span></label>
          <input type="text" name="title" class="form-control" required
                 value="<?php echo htmlspecialchars($editResource['title'] ?? ''); ?>">
        </div>
        <div class="form-group">
          <label>分类 <span class="required">*</span></label>
          <select name="category_id" class="form-control" required>
            <option value="">-- 请选择分类 --</option>
            <?php foreach ($categories as $cat): ?>
            <option value="<?php echo $cat['id']; ?>"
              <?php echo (($editResource['category_id'] ?? 0) == $cat['id']) ? 'selected' : ''; ?>>
              <?php echo str_repeat('— ', $cat['level'] ?? 0) . htmlspecialchars($cat['name']); ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="form-group">
        <label>描述</label>
        <textarea name="description" class="form-control" rows="4"><?php echo htmlspecialchars($editResource['description'] ?? ''); ?></textarea>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>封面图片</label>
          <input type="file" name="cover" class="form-control" accept="image/*">
          <?php if (!empty($editResource['cover_url'])): ?>
          <div class="file-preview" id="cover-preview">
            <img src="<?php echo htmlspecialchars($editResource['cover_url']); ?>" alt="cover">
            <span class="file-name">当前封面</span>
            <input type="hidden" name="existing_cover" value="<?php echo htmlspecialchars($editResource['cover_url']); ?>">
          </div>
          <?php endif; ?>
        </div>
        <div class="form-group">
          <label>主文件</label>
          <input type="file" name="file" class="form-control" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.md,.csv,.zip,.rar,.7z,.mp4,.mp3,.jpg,.jpeg,.png,.gif,.webp,.json,.xml,.html,.css,.js,.py,.java,.sql,.zip">
          <small style="color:#94a3b8;font-size:12px;margin-top:4px;display:block;">支持：文档(PDF/Word/Excel/PPT/TXT)、压缩包(ZIP/RAR/7Z)、图片、音视频、代码文件</small>
          <?php if (!empty($editResource['file_url'])): ?>
          <div class="file-preview">
            <span class="file-name"><?php echo htmlspecialchars(basename($editResource['file_url'])); ?>
              (<?php echo round($editResource['file_size']/1024); ?>KB)</span>
            <input type="hidden" name="existing_file" value="<?php echo htmlspecialchars($editResource['file_url']); ?>">
            <input type="hidden" name="existing_file_size" value="<?php echo $editResource['file_size']; ?>">
            <input type="hidden" name="existing_file_suffix" value="<?php echo htmlspecialchars($editResource['file_suffix'] ?? ''); ?>">
          </div>
          <?php endif; ?>
        </div>
      </div>

      <div class="form-group">
        <label>附加文件（可多选）</label>
        <input type="file" name="extra_files[]" class="form-control" multiple>
        <?php if (!empty($editFiles)): ?>
        <div style="margin-top:8px;font-size:13px;color:var(--text-secondary);">
          当前附加文件：
          <?php foreach ($editFiles as $ef): ?>
          <div class="file-preview">
            <span class="file-name"><?php echo htmlspecialchars($ef['file_name']); ?>
              (<?php echo round($ef['file_size']/1024); ?>KB)</span>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>

      <div class="form-row-3">
        <div class="form-group">
          <label>价格类型</label>
          <select name="price_type" class="form-control">
            <?php foreach ($priceTypeLabels as $k => $v): ?>
            <option value="<?php echo $k; ?>" <?php echo (($editResource['price_type'] ?? 'free') === $k) ? 'selected' : ''; ?>>
              <?php echo $v; ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>价格 (&yen;)</label>
          <input type="number" name="price" class="form-control" step="0.01" min="0"
                 value="<?php echo $editResource['price'] ?? '0.00'; ?>">
        </div>
        <div class="form-group">
          <label>VIP价格 (&yen;)</label>
          <input type="number" name="vip_price" class="form-control" step="0.01" min="0"
                 value="<?php echo $editResource['vip_price'] ?? '0.00'; ?>">
        </div>
      </div>

      <div class="form-row-3">
        <div class="form-group">
          <label>积分价格</label>
          <input type="number" name="points_price" class="form-control" min="0"
                 value="<?php echo $editResource['points_price'] ?? '0'; ?>">
        </div>
        <div class="form-group" id="vip-levels-group" style="display:<?php echo (($editResource['price_type'] ?? 'free') === 'member_free') ? 'block' : 'none'; ?>;">
          <label>VIP免费等级（可多选）</label>
          <?php
            $vipLevelOptions = [1 => '月卡会员', 2 => '季卡会员', 3 => '年卡会员', 4 => '终身会员'];
            $selectedLevels = $editResource['vip_free_levels'] ? explode(',', $editResource['vip_free_levels']) : [];
          ?>
          <div style="display:flex;gap:16px;flex-wrap:wrap;padding-top:6px;">
            <?php foreach ($vipLevelOptions as $lv => $lvName): ?>
            <label style="display:flex;align-items:center;gap:4px;cursor:pointer;font-weight:normal;">
              <input type="checkbox" name="vip_free_levels[]" value="<?php echo $lv; ?>"
                     <?php echo in_array((string)$lv, $selectedLevels) ? 'checked' : ''; ?>>
              <?php echo $lvName; ?>
            </label>
            <?php endforeach; ?>
          </div>
          <small style="color:#888;">选择哪些等级的VIP可以免费下载此资源</small>
        </div>
        <div class="form-group">
          <label>状态</label>
          <select name="status" class="form-control">
            <?php foreach ($statusLabels as $k => $v): ?>
            <option value="<?php echo $k; ?>" <?php echo (($editResource['status'] ?? 'pending') === $k) ? 'selected' : ''; ?>>
              <?php echo $v[0]; ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>标签（逗号分隔）</label>
          <input type="text" name="tags" class="form-control"
                 value="<?php echo htmlspecialchars(implode(', ', $editTags)); ?>"
                 placeholder="例如: 模板, 设计, PPT">
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <div class="form-check">
            <input type="checkbox" name="is_top" id="is_top" value="1"
              <?php echo ($editResource['is_top'] ?? 0) ? 'checked' : ''; ?>>
            <label for="is_top">置顶</label>
          </div>
        </div>
        <div class="form-group">
          <div class="form-check">
            <input type="checkbox" name="is_recommended" id="is_recommended" value="1"
              <?php echo ($editResource['is_recommended'] ?? 0) ? 'checked' : ''; ?>>
            <label for="is_recommended">推荐</label>
          </div>
        </div>
      </div>

      <div class="d-flex gap-10">
        <button type="submit" class="btn btn-primary btn-lg">保存</button>
        <a href="resources.php" class="btn btn-outline btn-lg">取消</a>
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
        <input type="text" name="search" placeholder="搜索资源..."
               value="<?php echo htmlspecialchars($search); ?>">
      </div>
      <select name="status" class="filter-select">
        <option value="">全部状态</option>
        <?php foreach ($statusLabels as $k => $v): ?>
        <option value="<?php echo $k; ?>" <?php echo $filterStatus === $k ? 'selected' : ''; ?>><?php echo $v[0]; ?></option>
        <?php endforeach; ?>
      </select>
      <select name="category" class="filter-select">
        <option value="">全部分类</option>
        <?php foreach ($categories as $cat): ?>
        <option value="<?php echo $cat['id']; ?>" <?php echo $filterCategory == $cat['id'] ? 'selected' : ''; ?>>
          <?php echo str_repeat('— ', $cat['level'] ?? 0) . htmlspecialchars($cat['name']); ?>
        </option>
        <?php endforeach; ?>
      </select>
      <select name="price_type" class="filter-select">
        <option value="">全部价格</option>
        <?php foreach ($priceTypeLabels as $k => $v): ?>
        <option value="<?php echo $k; ?>" <?php echo $filterPriceType === $k ? 'selected' : ''; ?>><?php echo $v; ?></option>
        <?php endforeach; ?>
      </select>
      <select name="sort_by" class="filter-select">
        <option value="">默认排序</option>
        <option value="up_votes_desc" <?php echo $sortBy === 'up_votes_desc' ? 'selected' : ''; ?>>👍 赞同最多</option>
        <option value="up_votes_asc" <?php echo $sortBy === 'up_votes_asc' ? 'selected' : ''; ?>>👍 赞同最少</option>
        <option value="down_votes_desc" <?php echo $sortBy === 'down_votes_desc' ? 'selected' : ''; ?>>👎 反对最多</option>
        <option value="net_votes_desc" <?php echo $sortBy === 'net_votes_desc' ? 'selected' : ''; ?>>📊 净票数最高</option>
      </select>
      <button type="submit" class="btn btn-primary btn-sm">筛选</button>
    </form>
  </div>
  <div class="toolbar-right">
    <a href="resources.php?export=csv&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($filterStatus); ?>&category=<?php echo urlencode($filterCategory); ?>&price_type=<?php echo urlencode($filterPriceType); ?>" class="btn btn-outline btn-sm" style="margin-right:8px;">📥 导出CSV</a>
    <?php if ($rankView): ?>
    <a href="resources.php" class="btn btn-outline btn-sm" style="margin-right:8px;background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;border-color:#6366f1;">🏆 排行视图</a>
    <?php else: ?>
    <a href="resources.php?rank_view=1&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($filterStatus); ?>&category=<?php echo urlencode($filterCategory); ?>&price_type=<?php echo urlencode($filterPriceType); ?>" class="btn btn-outline btn-sm" style="margin-right:8px;">🏆 排行视图</a>
    <?php endif; ?>
    <a href="resources.php?add=1" class="btn btn-primary">+ 新增资源</a>
  </div>
</div>

<!-- Resources Table -->
<div class="card">
  <div class="card-body no-padding">
    <?php if (empty($resources)): ?>
    <div class="empty-state">
      <div class="empty-icon">&#x1F4C4;</div>
      <h4>未找到资源</h4>
      <p>请尝试调整筛选条件或点击上方按钮新增资源</p>
    </div>
    <?php else: ?>
    <!-- 批量操作栏 -->
    <div id="batchBar" style="display:none;background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;padding:10px 16px;border-radius:10px;margin-bottom:12px;align-items:center;gap:12px;">
      <span id="batchCount" style="font-size:13px;font-weight:600;">已选 0 项</span>
      <button onclick="batchAction('approve')" style="height:30px;padding:0 12px;border:1px solid rgba(255,255,255,0.3);border-radius:6px;background:transparent;color:#fff;font-size:12px;cursor:pointer;">批量通过</button>
      <button onclick="batchAction('delete')" style="height:30px;padding:0 12px;border:1px solid rgba(255,255,255,0.3);border-radius:6px;background:rgba(239,68,68,0.6);color:#fff;font-size:12px;cursor:pointer;">批量删除</button>
      <button onclick="clearSelection()" style="height:30px;padding:0 12px;border:1px solid rgba(255,255,255,0.3);border-radius:6px;background:transparent;color:#fff;font-size:12px;cursor:pointer;margin-left:auto;">取消选择</button>
    </div>

    <div class="table-wrapper">
      <table>
        <thead>
          <tr>
            <th style="width:36px;"><input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)"></th>
            <?php if ($rankView): ?>
            <th style="width:50px;">排名</th>
            <?php endif; ?>
            <th>ID</th>
            <th>封面</th>
            <th>标题</th>
            <th>文件类型</th>
            <th>价格</th>
            <th>浏览</th>
            <th>下载</th>
            <th>投票</th>
            <th>状态</th>
            <th>标记</th>
            <th>创建时间</th>
            <th>操作</th>
          </tr>
        </thead>
        <tbody>
          <?php $rankCounter = $offset; foreach ($resources as $r): $rankCounter++; ?>
          <tr>
            <td><input type="checkbox" class="row-check" value="<?php echo $r['id']; ?>" onchange="updateBatchBar()"></td>
            <?php if ($rankView): ?>
            <td>
              <?php
              $rankStyle = '';
              $rankIcon = '#' . $rankCounter;
              if ($rankCounter === 1) { $rankStyle = 'background:#FFD700;color:#fff;font-weight:800;'; $rankIcon = '🥇'; }
              elseif ($rankCounter === 2) { $rankStyle = 'background:#C0C0C0;color:#fff;font-weight:800;'; $rankIcon = '🥈'; }
              elseif ($rankCounter === 3) { $rankStyle = 'background:#CD7F32;color:#fff;font-weight:800;'; $rankIcon = '🥉'; }
              ?>
              <span style="display:inline-block;min-width:32px;text-align:center;padding:3px 6px;border-radius:6px;font-size:13px;<?php echo $rankStyle; ?>"><?php echo $rankIcon; ?></span>
            </td>
            <?php endif; ?>
            <td><?php echo $r['id']; ?></td>
            <td>
              <?php if ($r['cover_url']): ?>
              <img src="<?php echo htmlspecialchars($r['cover_url']); ?>" style="width:48px;height:36px;object-fit:cover;border-radius:4px;">
              <?php else: ?>
              <span class="text-muted">-</span>
              <?php endif; ?>
            </td>
            <td style="max-width:220px;white-space:normal;">
              <div style="font-weight:600;"><?php echo htmlspecialchars($r['title']); ?></div>
              <?php if (!empty($r['category_name'])): ?>
              <span class="badge badge-default" style="font-size:11px;margin-top:3px;display:inline-block;"><?php echo htmlspecialchars($r['category_name']); ?></span>
              <?php endif; ?>
            </td>
            <td>
              <?php if (!empty($r['file_suffix'])):
                $suffix = strtolower($r['file_suffix']);
                $typeColors = [
                    'pdf'=>'#ef4444','doc'=>'#3b82f6','docx'=>'#3b82f6','xls'=>'#10b981','xlsx'=>'#10b981',
                    'ppt'=>'#f59e0b','pptx'=>'#f59e0b','txt'=>'#6b7280','md'=>'#6b7280','csv'=>'#10b981',
                    'zip'=>'#f97316','rar'=>'#f97316','7z'=>'#f97316',
                    'mp4'=>'#ec4899','mp3'=>'#8b5cf6','jpg'=>'#14b8a6','png'=>'#14b8a6',
                ];
                $color = $typeColors[$suffix] ?? '#8b5cf6';
              ?>
              <span class="badge" style="background:<?php echo $color; ?>;color:#fff;padding:3px 8px;border-radius:6px;font-size:11px;font-weight:600;"><?php echo strtoupper($suffix); ?></span>
              <?php else: ?>
              <span class="text-muted">-</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($r['price_type'] === 'free'): ?>
              <span class="badge badge-success">免费</span>
              <?php elseif ($r['price_type'] === 'member_free'): ?>
              <span class="badge badge-warning">👑 <?php
                $levelNames = [1 => '月卡', 2 => '季卡', 3 => '年卡', 4 => '终身'];
                $lvls = $r['vip_free_levels'] ? explode(',', $r['vip_free_levels']) : [];
                echo empty($lvls) ? 'VIP免费' : implode('/', array_map(function($l) use ($levelNames) { return $levelNames[$l] ?? $l; }, $lvls)) . '免费';
              ?></span>
              <?php else: ?>
              &yen;<?php echo number_format((float)$r['price'], 2); ?>
              <?php endif; ?>
            </td>
            <td><?php echo (int)$r['view_count']; ?></td>
            <td><?php echo (int)$r['download_count']; ?></td>
            <td>
              <span style="color:#22c55e;font-weight:600;" title="赞同">👍 <?php echo (int)($r['up_votes'] ?? 0); ?></span>
              <span style="color:#ef4444;font-weight:600;margin-left:6px;" title="反对">👎 <?php echo (int)($r['down_votes'] ?? 0); ?></span>
            </td>
            <td>
              <?php $sl = $statusLabels[$r['status']] ?? ['未知','badge-default']; ?>
              <span class="badge <?php echo $sl[1]; ?>"><?php echo $sl[0]; ?></span>
            </td>
            <td>
              <?php if ($r['is_top']): ?><span class="badge badge-warning">置顶</span><?php endif; ?>
              <?php if ($r['is_recommended']): ?><span class="badge badge-info">推荐</span><?php endif; ?>
            </td>
            <td><?php echo substr($r['created_at'], 0, 16); ?></td>
            <td class="actions">
              <a href="resources.php?edit=<?php echo $r['id']; ?>" class="btn btn-outline btn-sm" title="编辑">✏️ 编辑</a>
              <a href="preview.php?id=<?php echo $r['id']; ?>" target="_blank" class="btn btn-outline btn-sm" title="预览" style="color:#8b5cf6;border-color:#8b5cf6;">👁️ 预览</a>

              <?php if ($r['status'] === 'pending'): ?>
              <form method="POST" style="display:inline" onsubmit="return confirm('确定审核通过此资源？')">
                <input type="hidden" name="_token" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="action" value="approve">
                <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                <button class="btn btn-success btn-sm">通过</button>
              </form>
              <form method="POST" style="display:inline" onsubmit="return confirm('确定拒绝此资源？')">
                <input type="hidden" name="_token" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="action" value="reject">
                <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                <button class="btn btn-warning btn-sm">拒绝</button>
              </form>
              <?php endif; ?>

              <form method="POST" style="display:inline">
                <input type="hidden" name="_token" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="action" value="toggle_recommend">
                <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                <button class="btn btn-sm btn-outline" title="切换推荐"><?php echo $r['is_recommended'] ? '取消推荐' : '推荐'; ?></button>
              </form>

              <form method="POST" style="display:inline">
                <input type="hidden" name="_token" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="action" value="toggle_top">
                <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                <button class="btn btn-sm btn-outline" title="切换置顶"><?php echo $r['is_top'] ? '取消置顶' : '置顶'; ?></button>
              </form>

              <form method="POST" style="display:inline" onsubmit="return confirm('确定永久删除此资源？')">
                <input type="hidden" name="_token" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
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
      <span class="page-info">共 <?php echo $totalRows; ?> 条记录，第 <?php echo $page; ?>/<?php echo $totalPages; ?> 页</span>
      <div class="page-links">
        <?php if ($page > 1): ?>
        <a href="<?php echo buildUrl(['search'=>$search,'status'=>$filterStatus,'category'=>$filterCategory,'price_type'=>$filterPriceType,'sort_by'=>$sortBy,'rank_view'=>$rankView?'1':'','page'=>$page-1]); ?>">&laquo; 上一页</a>
        <?php else: ?>
        <span class="disabled">&laquo; 上一页</span>
        <?php endif; ?>

        <?php
        $startP = max(1, $page - 3);
        $endP = min($totalPages, $page + 3);
        for ($p = $startP; $p <= $endP; $p++):
        ?>
        <a href="<?php echo buildUrl(['search'=>$search,'status'=>$filterStatus,'category'=>$filterCategory,'price_type'=>$filterPriceType,'sort_by'=>$sortBy,'rank_view'=>$rankView?'1':'','page'=>$p]); ?>"
           class="<?php echo $p === $page ? 'active' : ''; ?>"><?php echo $p; ?></a>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
        <a href="<?php echo buildUrl(['search'=>$search,'status'=>$filterStatus,'category'=>$filterCategory,'price_type'=>$filterPriceType,'sort_by'=>$sortBy,'rank_view'=>$rankView?'1':'','page'=>$page+1]); ?>">下一页 &raquo;</a>
        <?php else: ?>
        <span class="disabled">下一页 &raquo;</span>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php endif; // end else (not add/edit form) ?>

<?php
// JS for form enhancements (cover preview + price toggle)
$extraJs = "
document.addEventListener('DOMContentLoaded', function() {
  // Cover image preview
  var coverInput = document.querySelector('input[name=\"cover\"]');
  if (coverInput) {
    coverInput.addEventListener('change', function() {
      var file = this.files[0];
      if (!file) return;
      if (!file.type.startsWith('image/')) return;
      var reader = new FileReader();
      reader.onload = function(e) {
        var container = document.getElementById('cover-preview');
        if (!container) {
          container = document.createElement('div');
          container.id = 'cover-preview';
          container.className = 'file-preview';
          coverInput.parentElement.appendChild(container);
        }
        container.innerHTML = '<img src=\"' + e.target.result + '\" alt=\"cover\"><span class=\"file-name\">' + file.name + ' (' + Admin.formatSize(file.size) + ')</span>';
      };
      reader.readAsDataURL(file);
    });
  }

  // Price type toggle - show/hide price fields
  var priceTypeSelect = document.querySelector('select[name=\"price_type\"]');
  var priceField = document.querySelector('input[name=\"price\"]');
  var vipPriceField = document.querySelector('input[name=\"vip_price\"]');
  var pointsField = document.querySelector('input[name=\"points_price\"]');
  function togglePriceFields() {
    if (!priceTypeSelect) return;
    var row3 = priceField ? priceField.closest('.form-row-3') || priceField.closest('.form-group').parentElement : null;
    if (priceTypeSelect.value === 'free') {
      if (priceField) priceField.value = '0';
      if (vipPriceField) vipPriceField.value = '0';
      if (pointsField) pointsField.value = '0';
    }
    // 显示/隐藏VIP等级选择
    var vipGroup = document.getElementById('vip-levels-group');
    if (vipGroup) {
      vipGroup.style.display = (priceTypeSelect.value === 'member_free') ? 'block' : 'none';
    }
  }
  if (priceTypeSelect) {
    priceTypeSelect.addEventListener('change', togglePriceFields);
    togglePriceFields();
  }

  // Main file preview
  var fileInput = document.querySelector('input[name=\"file\"]');
  if (fileInput) {
    fileInput.addEventListener('change', function() {
      var file = this.files[0];
      if (!file) return;
      var container = this.parentElement.querySelector('.file-preview');
      if (!container) {
        container = document.createElement('div');
        container.className = 'file-preview';
        this.parentElement.appendChild(container);
      }
      container.innerHTML = '<span class=\"file-name\">' + file.name + ' (' + Admin.formatSize(file.size) + ')</span>';
    });
  }
});
";
?>

<!-- 批量操作 JS -->
<script>
function toggleSelectAll(el) {
  document.querySelectorAll('.row-check').forEach(function(cb) { cb.checked = el.checked; });
  updateBatchBar();
}
function updateBatchBar() {
  var checked = document.querySelectorAll('.row-check:checked');
  var bar = document.getElementById('batchBar');
  if (checked.length > 0) {
    bar.style.display = 'flex';
    document.getElementById('batchCount').textContent = '已选 ' + checked.length + ' 项';
  } else {
    bar.style.display = 'none';
  }
}
function clearSelection() {
  document.querySelectorAll('.row-check').forEach(function(cb) { cb.checked = false; });
  document.getElementById('selectAll').checked = false;
  updateBatchBar();
}
function batchAction(action) {
  var checked = document.querySelectorAll('.row-check:checked');
  if (checked.length === 0) return;
  if (action === 'delete' && !confirm('确定要删除选中的 ' + checked.length + ' 个资源吗？此操作不可恢复！')) return;
  if (action === 'approve' && !confirm('确定要批量通过选中的 ' + checked.length + ' 个资源吗？')) return;

  var ids = [];
  checked.forEach(function(cb) { ids.push(cb.value); });

  var token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
  var form = document.createElement('form');
  form.method = 'POST';
  form.innerHTML = '<input type="hidden" name="_token" value="' + token + '">' +
    '<input type="hidden" name="action" value="batch_' + action + '">' +
    '<input type="hidden" name="ids" value="' + ids.join(',') + '">';
  document.body.appendChild(form);
  form.submit();
}
</script>

<?php include __DIR__ . '/footer.php'; ?>
