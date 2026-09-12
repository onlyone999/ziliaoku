<?php
/**
 * 管理后台 - VIP套餐管理
 * 套餐增删改查、设置价格、切换状态
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
        // 删除
        if ($action === 'delete') {
            $pid = (int)($_POST['id'] ?? 0);
            if ($pid > 0) {
                $orderCount = $db->count('orders', 'vip_plan_id = :p', [':p' => $pid]);
                if ($orderCount > 0) {
                    $msg = '无法删除：该套餐有关联订单'; $msgType = 'error';
                } else {
                    $db->delete('vip_plans', 'id = :id', [':id' => $pid]);
                    $msg = 'VIP套餐已删除'; $msgType = 'success';
                }
            }
        }
        // 切换状态
        elseif ($action === 'toggle_status') {
            $pid = (int)($_POST['id'] ?? 0);
            if ($pid > 0) {
                $cur = $db->fetch('SELECT status FROM vip_plans WHERE id=:id', [':id' => $pid]);
                $nv = ($cur['status'] ?? 1) ? 0 : 1;
                $db->update('vip_plans', ['status' => $nv], 'id = :id', [':id' => $pid]);
                $msg = $nv ? '套餐已启用' : '套餐已禁用'; $msgType = 'success';
            }
        }
        // 保存
        elseif ($action === 'save') {
            $editId       = (int)($_POST['edit_id'] ?? 0);
            $name         = trim($_POST['name'] ?? '');
            $durationDays = (int)($_POST['duration_days'] ?? 0);
            $originalPrice = (float)($_POST['original_price'] ?? 0);
            $price        = (float)($_POST['price'] ?? 0);
            $pointsPrice  = (int)($_POST['points_price'] ?? 0);
            $description  = trim($_POST['description'] ?? '');
            $sortOrder    = (int)($_POST['sort_order'] ?? 0);

            if ($name === '' || $durationDays <= 0) {
                $msg = '名称和时长为必填项'; $msgType = 'error';
            } else {
                $data = [
                    'name'           => $name,
                    'duration_days'  => $durationDays,
                    'original_price' => $originalPrice,
                    'price'          => $price,
                    'points_price'   => $pointsPrice,
                    'description'    => $description,
                    'sort_order'     => $sortOrder,
                ];
                if ($editId > 0) {
                    $db->update('vip_plans', $data, 'id = :id', [':id' => $editId]);
                    $msg = '套餐已更新';
                } else {
                    $db->insert('vip_plans', $data);
                    $msg = '套餐已添加';
                }
                $msgType = 'success';
            }
        }
    }
}

// 加载套餐
$plans = $db->fetchAll('SELECT * FROM vip_plans ORDER BY sort_order DESC, id ASC');

// 编辑表单
$editPlan = null;
if (isset($_GET['edit'])) {
    $eid = (int)$_GET['edit'];
    if ($eid > 0) {
        $editPlan = $db->fetch('SELECT * FROM vip_plans WHERE id=:id', [':id' => $eid]);
    }
}

include __DIR__ . '/header.php';
?>

<?php if ($msg): ?>
<div class="alert auto-dismiss" style="padding:12px 16px;border-radius:var(--radius);margin-bottom:20px;font-size:14px;
  <?php echo $msgType==='success' ? 'background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0' : 'background:#fef2f2;color:#b91c1c;border:1px solid #fecaca'; ?>">
  <?php echo htmlspecialchars($msg); ?>
</div>
<?php endif; ?>

<?php if ($editPlan || isset($_GET['add'])): ?>
<!-- Add/Edit Form -->
<div class="card mb-20">
  <div class="card-header">
    <h3><?php echo $editPlan ? '编辑VIP套餐' : '新增VIP套餐'; ?></h3>
    <a href="viplans.php" class="btn btn-outline btn-sm">返回列表</a>
  </div>
  <div class="card-body">
    <form method="POST" action="viplans.php">
      <input type="hidden" name="_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
      <input type="hidden" name="action" value="save">
      <?php if ($editPlan): ?>
      <input type="hidden" name="edit_id" value="<?php echo $editPlan['id']; ?>">
      <?php endif; ?>

      <div class="form-row">
        <div class="form-group">
          <label>套餐名称 <span class="required">*</span></label>
          <input type="text" name="name" class="form-control" required
                 value="<?php echo htmlspecialchars($editPlan['name'] ?? ''); ?>">
        </div>
        <div class="form-group">
          <label>时长（天） <span class="required">*</span></label>
          <input type="number" name="duration_days" class="form-control" min="1" required
                 value="<?php echo $editPlan['duration_days'] ?? ''; ?>">
        </div>
      </div>

      <div class="form-row-3">
        <div class="form-group">
          <label>原价 (&yen;)</label>
          <input type="number" name="original_price" class="form-control" step="0.01" min="0"
                 value="<?php echo $editPlan['original_price'] ?? '0.00'; ?>">
        </div>
        <div class="form-group">
          <label>售价 (&yen;)</label>
          <input type="number" name="price" class="form-control" step="0.01" min="0"
                 value="<?php echo $editPlan['price'] ?? '0.00'; ?>">
        </div>
        <div class="form-group">
          <label>积分价格</label>
          <input type="number" name="points_price" class="form-control" min="0"
                 value="<?php echo $editPlan['points_price'] ?? '0'; ?>">
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>描述</label>
          <input type="text" name="description" class="form-control"
                 value="<?php echo htmlspecialchars($editPlan['description'] ?? ''); ?>">
        </div>
        <div class="form-group">
          <label>排序</label>
          <input type="number" name="sort_order" class="form-control" min="0"
                 value="<?php echo $editPlan['sort_order'] ?? '0'; ?>">
          <span class="form-hint">数值越大越靠前</span>
        </div>
      </div>

      <div class="d-flex gap-10">
        <button type="submit" class="btn btn-primary btn-lg">保存</button>
        <a href="viplans.php" class="btn btn-outline btn-lg">取消</a>
      </div>
    </form>
  </div>
</div>

<?php else: ?>

<!-- Toolbar -->
<div class="toolbar">
  <div class="toolbar-left">
    <h3 style="font-size:16px;color:var(--text-secondary);">VIP套餐（共 <?php echo count($plans); ?> 个）</h3>
  </div>
  <div class="toolbar-right">
    <a href="viplans.php?add=1" class="btn btn-primary">+ 新增套餐</a>
  </div>
</div>

<!-- Plans Table -->
<div class="card">
  <div class="card-body no-padding">
    <?php if (empty($plans)): ?>
    <div class="empty-state">
      <div class="empty-icon">⭐</div>
      <h4>暂无VIP套餐</h4>
    </div>
    <?php else: ?>
    <div class="table-wrapper">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>名称</th>
            <th>时长</th>
            <th>原价</th>
            <th>售价</th>
            <th>积分价格</th>
            <th>描述</th>
            <th>排序</th>
            <th>状态</th>
            <th>操作</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($plans as $p): ?>
          <tr>
            <td><?php echo $p['id']; ?></td>
            <td><strong><?php echo htmlspecialchars($p['name']); ?></strong></td>
            <td><?php echo $p['duration_days']; ?> 天</td>
            <td><span style="text-decoration:line-through;color:var(--text-muted);">&yen;<?php echo number_format((float)$p['original_price'], 2); ?></span></td>
            <td><strong style="color:var(--danger);">&yen;<?php echo number_format((float)$p['price'], 2); ?></strong>
              <?php if ((float)$p['original_price'] > 0 && (float)$p['price'] > 0 && (float)$p['original_price'] > (float)$p['price']): ?>
              <br><span style="font-size:11px;color:var(--success);">省 <?php echo number_format((float)$p['original_price'] - (float)$p['price'], 2); ?> 元</span>
              <?php endif; ?>
            </td>
            <td><?php echo (int)$p['points_price']; ?></td>
            <td style="max-width:200px;white-space:normal;font-size:12px;"><?php echo htmlspecialchars($p['description'] ?? '-'); ?></td>
            <td><?php echo $p['sort_order']; ?></td>
            <td>
              <span class="badge <?php echo $p['status'] ? 'badge-success' : 'badge-danger'; ?>">
                <?php echo $p['status'] ? '上线' : '下线'; ?>
              </span>
            </td>
            <td class="actions">
              <a href="viplans.php?edit=<?php echo $p['id']; ?>" class="btn btn-outline btn-sm">编辑</a>
              <form method="POST" style="display:inline">
                <input type="hidden" name="_token" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="action" value="toggle_status">
                <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                <button class="btn btn-sm <?php echo $p['status'] ? 'btn-warning' : 'btn-success'; ?>">
                  <?php echo $p['status'] ? '禁用' : '启用'; ?>
                </button>
              </form>
              <form method="POST" style="display:inline" onsubmit="return confirm('确定删除此套餐？')">
                <input type="hidden" name="_token" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
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

<?php include __DIR__ . '/footer.php'; ?>
