<?php
/**
 * 管理后台 - 分类管理（支持无限层级）
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
        if ($action === 'delete') {
            $cid = (int)($_POST['id'] ?? 0);
            if ($cid > 0) {
                $children = $db->count('categories', 'parent_id = :pid', [':pid' => $cid]);
                $resCount = $db->count('resources', 'category_id = :cid', [':cid' => $cid]);
                if ($children > 0) {
                    $msg = '无法删除：该分类下有子分类'; $msgType = 'error';
                } elseif ($resCount > 0) {
                    $msg = '无法删除：该分类下有资源'; $msgType = 'error';
                } else {
                    $db->delete('categories', 'id = :id', [':id' => $cid]);
                    $msg = '分类已删除'; $msgType = 'success';
                }
            }
        }
        elseif ($action === 'toggle_status') {
            $cid = (int)($_POST['id'] ?? 0);
            if ($cid > 0) {
                $cur = $db->fetch('SELECT status FROM categories WHERE id=:id', [':id' => $cid]);
                $nv = ($cur['status'] ?? 1) ? 0 : 1;
                $db->update('categories', ['status' => $nv], 'id = :id', [':id' => $cid]);
                $msg = $nv ? '分类已启用' : '分类已禁用'; $msgType = 'success';
            }
        }
        elseif ($action === 'save') {
            $editId    = (int)($_POST['edit_id'] ?? 0);
            $name      = trim($_POST['name'] ?? '');
            $icon      = trim($_POST['icon'] ?? '');
            $parentId  = (int)($_POST['parent_id'] ?? 0);
            $sortOrder = (int)($_POST['sort_order'] ?? 0);
            $isHot     = isset($_POST['is_hot']) ? 1 : 0;

            if ($name === '') {
                $msg = '分类名称为必填项'; $msgType = 'error';
            } else {
                $dupWhere = 'name = :n AND parent_id = :p';
                $dupParams = [':n' => $name, ':p' => $parentId];
                if ($editId > 0) {
                    $dupWhere .= ' AND id != :eid';
                    $dupParams[':eid'] = $editId;
                }
                $dup = $db->fetch("SELECT id FROM categories WHERE {$dupWhere} LIMIT 1", $dupParams);
                if (!empty($dup)) {
                    $msg = '同一父级下已存在同名分类'; $msgType = 'error';
                } else {
                    $data = [
                        'name'       => $name,
                        'icon'       => $icon,
                        'parent_id'  => $parentId,
                        'sort_order' => $sortOrder,
                        'is_hot'     => $isHot,
                    ];
                    if ($editId > 0) {
                        $db->update('categories', $data, 'id = :id', [':id' => $editId]);
                        $msg = '分类已更新';
                    } else {
                        $db->insert('categories', $data);
                        $msg = '分类已添加';
                    }
                    $msgType = 'success';
                }
            }
        }
    }
}

// 加载所有分类
$allCategories = $db->fetchAll('SELECT * FROM categories ORDER BY sort_order DESC, id ASC');

// 构建子分类映射
$childrenMap = [];
foreach ($allCategories as $cat) {
    $childrenMap[(int)$cat['parent_id']][] = $cat;
}

// 获取顶级分类
$topLevel = $childrenMap[0] ?? [];

// 编辑表单
$editCat = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    if ($editId > 0) {
        $editCat = $db->fetch('SELECT * FROM categories WHERE id=:id', [':id' => $editId]);
    }
}
if (!$editCat && isset($_GET['add']) && isset($_GET['parent'])) {
    $editCat = ['parent_id' => (int)$_GET['parent']];
}

// 递归渲染分类树
function renderTree($parentId, $childrenMap, $db, $csrfToken, $level = 0) {
    if (empty($childrenMap[$parentId])) return;
    $indent = str_repeat('—', $level);
    echo '<ul class="tree-children" style="' . ($level > 0 ? 'margin-left:30px;' : '') . '">';
    foreach ($childrenMap[$parentId] as $cat) {
        $resCount = $db->count('resources', 'category_id = :c', [':c' => $cat['id']]);
        $hasChildren = !empty($childrenMap[(int)$cat['id']]);
        $levelColors = ['#3b82f6', '#22c55e', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#06b6d4'];
        $color = $levelColors[$level % count($levelColors)];
        echo '<li class="tree-item">';
        echo '<div class="tree-item-header">';
        echo '<div class="tree-icon" style="background:' . $color . '20;color:' . $color . ';">' . htmlspecialchars($cat['icon'] ?: '📁') . '</div>';
        echo '<span class="tree-name">';
        if ($level > 0) echo '<span style="color:#ccc;margin-right:4px;">' . $indent . '</span>';
        echo htmlspecialchars($cat['name']);
        if ($cat['is_hot']) echo ' <span class="badge badge-warning">热门</span>';
        if (!$cat['status']) echo ' <span class="badge badge-danger">已禁用</span>';
        echo '</span>';
        echo '<span class="text-muted" style="font-size:12px">排序:' . $cat['sort_order'] . '</span>';
        echo '<span class="text-muted" style="font-size:12px">(' . $resCount . '个资源)</span>';
        echo '<div class="tree-actions">';
        echo '<a href="categories.php?add=1&parent=' . $cat['id'] . '" class="btn btn-outline btn-sm" style="color:#22c55e;border-color:#22c55e;" title="添加子分类">+子</a>';
        echo '<a href="categories.php?edit=' . $cat['id'] . '" class="btn btn-outline btn-sm">编辑</a>';
        echo '<form method="POST" style="display:inline">';
        echo '<input type="hidden" name="_token" value="' . htmlspecialchars($csrfToken) . '">';
        echo '<input type="hidden" name="action" value="toggle_status">';
        echo '<input type="hidden" name="id" value="' . $cat['id'] . '">';
        echo '<button class="btn btn-sm btn-outline">' . ($cat['status'] ? '禁用' : '启用') . '</button>';
        echo '</form>';
        echo '<form method="POST" style="display:inline" onsubmit="return confirm(\'确定删除？\')">';
        echo '<input type="hidden" name="_token" value="' . htmlspecialchars($csrfToken) . '">';
        echo '<input type="hidden" name="action" value="delete">';
        echo '<input type="hidden" name="id" value="' . $cat['id'] . '">';
        echo '<button class="btn btn-danger btn-sm">删除</button>';
        echo '</form>';
        echo '</div>';
        echo '</div>';
        // 递归渲染子分类
        renderTree((int)$cat['id'], $childrenMap, $db, $csrfToken, $level + 1);
        echo '</li>';
    }
    echo '</ul>';
}

// 构建父分类下拉选项（带缩进）
function buildParentOptions($allCategories, $excludeId = 0, $selectedId = 0) {
    $tree = [];
    foreach ($allCategories as $cat) {
        $tree[(int)$cat['parent_id']][] = $cat;
    }
    $options = [];
    function flattenTree($parentId, $tree, $level, $excludeId, $selectedId, &$options) {
        if (empty($tree[$parentId])) return;
        foreach ($tree[$parentId] as $cat) {
            if ((int)$cat['id'] === $excludeId) continue;
            $indent = str_repeat('— ', $level);
            $selected = ((int)$cat['id'] === $selectedId) ? 'selected' : '';
            $options[] = '<option value="' . $cat['id'] . '" ' . $selected . '>' . $indent . htmlspecialchars($cat['name']) . '</option>';
            flattenTree((int)$cat['id'], $tree, $level + 1, $excludeId, $selectedId, $options);
        }
    }
    flattenTree(0, $tree, 0, $excludeId, $selectedId, $options);
    return implode("\n", $options);
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

<?php if ($editCat || isset($_GET['add'])): ?>
<!-- 添加/编辑表单 -->
<div class="card mb-20">
  <div class="card-header">
    <h3><?php echo ($editCat && isset($editCat['id'])) ? '编辑分类' : '新增分类'; ?></h3>
    <a href="categories.php" class="btn btn-outline btn-sm">返回列表</a>
  </div>
  <div class="card-body">
    <form method="POST" action="categories.php">
      <input type="hidden" name="_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
      <input type="hidden" name="action" value="save">
      <?php if ($editCat && isset($editCat['id'])): ?>
      <input type="hidden" name="edit_id" value="<?php echo $editCat['id']; ?>">
      <?php endif; ?>

      <div class="form-row">
        <div class="form-group">
          <label>分类名称 <span class="required">*</span></label>
          <input type="text" name="name" class="form-control" required
                 value="<?php echo htmlspecialchars($editCat['name'] ?? ''); ?>">
        </div>
        <div class="form-group">
          <label>上级分类</label>
          <select name="parent_id" class="form-control">
            <option value="0">-- 顶级分类 --</option>
            <?php echo buildParentOptions($allCategories, $editCat['id'] ?? 0, $editCat['parent_id'] ?? 0); ?>
          </select>
          <span class="form-hint">支持无限层级嵌套</span>
        </div>
      </div>

      <div class="form-row-3">
        <div class="form-group">
          <label>图标（Emoji）</label>
          <input type="text" name="icon" class="form-control" placeholder="例如: 📄 💻 🎨"
                 value="<?php echo htmlspecialchars($editCat['icon'] ?? ''); ?>">
          <span class="form-hint">输入Emoji表情符号</span>
        </div>
        <div class="form-group">
          <label>排序</label>
          <input type="number" name="sort_order" class="form-control" min="0"
                 value="<?php echo $editCat['sort_order'] ?? '0'; ?>">
          <span class="form-hint">数值越大越靠前</span>
        </div>
        <div class="form-group">
          <label>&nbsp;</label>
          <div class="form-check">
            <input type="checkbox" name="is_hot" id="is_hot" value="1"
              <?php echo ($editCat['is_hot'] ?? 0) ? 'checked' : ''; ?>>
            <label for="is_hot">热门分类</label>
          </div>
        </div>
      </div>

      <div class="d-flex gap-10">
        <button type="submit" class="btn btn-primary btn-lg">保存</button>
        <a href="categories.php" class="btn btn-outline btn-lg">取消</a>
      </div>
    </form>
  </div>
</div>
<?php else: ?>

<!-- 工具栏 -->
<div class="toolbar">
  <div class="toolbar-left">
    <h3 style="font-size:16px;color:var(--text-secondary);">分类树（共 <?php echo count($allCategories); ?> 个，支持无限层级）</h3>
  </div>
  <div class="toolbar-right">
    <a href="categories.php?add=1" class="btn btn-primary">+ 新增顶级分类</a>
  </div>
</div>

<!-- 分类树 -->
<div class="card">
  <div class="card-body">
    <?php if (empty($topLevel)): ?>
    <div class="empty-state">
      <div class="empty-icon">&#x1F4C1;</div>
      <h4>暂无分类</h4>
      <p>点击上方按钮添加第一个分类</p>
    </div>
    <?php else: ?>
    <ul class="tree-list">
      <?php foreach ($topLevel as $cat): ?>
      <li class="tree-item">
        <div class="tree-item-header">
          <div class="tree-icon"><?php echo htmlspecialchars($cat['icon'] ?: '📂'); ?></div>
          <span class="tree-name">
            <?php echo htmlspecialchars($cat['name']); ?>
            <?php if ($cat['is_hot']): ?><span class="badge badge-warning" style="margin-left:6px">热门</span><?php endif; ?>
            <?php if (!$cat['status']): ?><span class="badge badge-danger" style="margin-left:6px">已禁用</span><?php endif; ?>
          </span>
          <span class="text-muted" style="font-size:12px">排序: <?php echo $cat['sort_order']; ?></span>
          <span class="text-muted" style="font-size:12px">
            (<?php echo $db->count('resources', 'category_id = :c', [':c' => $cat['id']]); ?> 个资源)
          </span>
          <div class="tree-actions">
            <a href="categories.php?add=1&parent=<?php echo $cat['id']; ?>" class="btn btn-outline btn-sm" style="color:#22c55e;border-color:#22c55e;">+子</a>
            <a href="categories.php?edit=<?php echo $cat['id']; ?>" class="btn btn-outline btn-sm">编辑</a>
            <form method="POST" style="display:inline">
              <input type="hidden" name="_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
              <input type="hidden" name="action" value="toggle_status">
              <input type="hidden" name="id" value="<?php echo $cat['id']; ?>">
              <button class="btn btn-sm btn-outline"><?php echo $cat['status'] ? '禁用' : '启用'; ?></button>
            </form>
            <form method="POST" style="display:inline" onsubmit="return confirm('确定删除此分类？')">
              <input type="hidden" name="_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?php echo $cat['id']; ?>">
              <button class="btn btn-danger btn-sm">删除</button>
            </form>
          </div>
        </div>
        <?php renderTree((int)$cat['id'], $childrenMap, $db, $csrfToken, 1); ?>
      </li>
      <?php endforeach; ?>
    </ul>
    <?php endif; ?>
  </div>
</div>

<?php endif; ?>

<style>
.tree-list { list-style: none; padding: 0; margin: 0; }
.tree-children { list-style: none; padding: 0; margin: 0 0 0 18px; border-left: 2px solid #e5e7eb; }
.tree-children .tree-children { border-left-color: #d1d5db; }
.tree-children .tree-children .tree-children { border-left-color: #c1c9d2; }
.tree-item { border-bottom: 1px solid #f3f4f6; border-radius: 6px; margin-bottom: 4px; transition: all .2s ease; }
.tree-item:last-child { border-bottom: none; }
.tree-item:hover { background: #f8fafc; }
.tree-item-header { display: flex; align-items: center; gap: 12px; padding: 12px 16px; flex-wrap: wrap; border-radius: 6px; transition: background .2s ease; }
.tree-item-header:hover { background: rgba(59,130,246,.04); }
.tree-icon { width: 38px; height: 38px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0; transition: transform .2s ease, box-shadow .2s ease; }
.tree-item-header:hover .tree-icon { transform: scale(1.1); box-shadow: 0 2px 8px rgba(59,130,246,.15); }
.tree-name { font-size: 14px; font-weight: 600; min-width: 120px; }
.tree-actions { display: flex; gap: 6px; margin-left: auto; flex-wrap: wrap; }
</style>

<?php include __DIR__ . '/footer.php'; ?>
