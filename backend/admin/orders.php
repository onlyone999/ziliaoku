<?php
/**
 * 管理后台 - 订单管理
 * 带筛选的列表、查看详情、手动退款
 */
session_start();
if (empty($_SESSION['admin_id'])) { header('Location: login.php'); exit; }

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/AdminLog.php';
$db = Database::getInstance();
$csrfToken = $_SESSION['csrf_token'] ?? '';

$msg = '';
$msgType = '';

$statusLabels = [
    'pending'   => ['待处理',   'badge-warning'],
    'paid'      => ['已支付',   'badge-success'],
    'refunded'  => ['已退款',   'badge-danger'],
    'cancelled' => ['已取消',   'badge-default'],
];

$typeLabels = ['resource' => '资源', 'vip' => 'VIP套餐'];

// 筛选条件（提前初始化供CSV导出使用）
$search       = trim($_GET['search'] ?? '');
$filterStatus = $_GET['status'] ?? '';
$filterType   = $_GET['type'] ?? '';
$dateFrom     = $_GET['date_from'] ?? '';
$dateTo       = $_GET['date_to'] ?? '';

// CSV导出
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $expWhere = '1=1';
    $expParams = [];
    if ($search !== '') {
        $expWhere .= ' AND (o.order_no LIKE :s OR u.nickname LIKE :s OR u.phone LIKE :s)';
        $expParams[':s'] = '%' . $search . '%';
    }
    if ($filterStatus !== '') {
        $expWhere .= ' AND o.status = :st';
        $expParams[':st'] = $filterStatus;
    }
    if ($filterType !== '') {
        $expWhere .= ' AND o.order_type = :ot';
        $expParams[':ot'] = $filterType;
    }
    if ($dateFrom !== '') {
        $expWhere .= ' AND o.created_at >= :df';
        $expParams[':df'] = $dateFrom . ' 00:00:00';
    }
    if ($dateTo !== '') {
        $expWhere .= ' AND o.created_at <= :dt';
        $expParams[':dt'] = $dateTo . ' 23:59:59';
    }
    $expOrders = $db->fetchAll(
        "SELECT o.*, u.nickname AS user_name, u.phone AS user_phone,
                r.title AS resource_title, vp.name AS vip_plan_name
         FROM orders o
         LEFT JOIN users u ON o.user_id = u.id
         LEFT JOIN resources r ON o.resource_id = r.id
         LEFT JOIN vip_plans vp ON o.vip_plan_id = vp.id
         WHERE {$expWhere}
         ORDER BY o.created_at DESC",
        $expParams
    );
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=orders_' . date('YmdHis') . '.csv');
    echo "\xEF\xBB\xBF"; // UTF-8 BOM
    $fp = fopen('php://output', 'w');
    fputcsv($fp, ['订单号','类型','用户','手机号','项目','金额','支付金额','支付方式','状态','交易号','创建时间']);
    foreach ($expOrders as $o) {
        fputcsv($fp, [
            $o['order_no'],
            $typeLabels[$o['order_type']] ?? $o['order_type'],
            $o['user_name'] ?? '',
            $o['user_phone'] ?? '',
            $o['resource_title'] ?? $o['vip_plan_name'] ?? '',
            $o['amount'],
            $o['pay_amount'],
            $o['payment_method'] === 'wechat' ? '微信' : '积分',
            ($statusLabels[$o['status']] ?? ['未知'])[0],
            $o['transaction_id'] ?? '',
            $o['created_at'],
        ]);
    }
    fclose($fp);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $token  = $_POST['_token'] ?? '';
    if ($token !== $csrfToken) { $msg = 'CSRF令牌无效'; $msgType = 'error'; }
    else {
        $oid = (int)($_POST['id'] ?? 0);

        // 退款
        if ($action === 'refund' && $oid > 0) {
            $refundAmount = (float)($_POST['refund_amount'] ?? 0);
            $order = $db->fetch('SELECT * FROM orders WHERE id=:id', [':id' => $oid]);
            if (empty($order)) {
                $msg = '订单未找到'; $msgType = 'error';
            } elseif ($order['status'] !== 'paid') {
                $msg = '只有已支付的订单才能退款'; $msgType = 'error';
            } elseif ($refundAmount <= 0 || $refundAmount > (float)$order['pay_amount']) {
                $msg = '退款金额无效'; $msgType = 'error';
            } else {
                $db->update('orders', [
                    'status'        => 'refunded',
                    'refund_at'     => date('Y-m-d H:i:s'),
                    'refund_amount' => $refundAmount,
                ], 'id = :id', [':id' => $oid]);

                // 如果是VIP订单，撤销VIP
                if ($order['order_type'] === 'vip' && $order['user_id']) {
                    $db->update('users', [
                        'vip_level' => 0,
                        'vip_expire_at' => null,
                    ], 'id = :id', [':id' => $order['user_id']]);
                }

                $msg = '订单已退款: ¥' . number_format($refundAmount, 2);
                AdminLog::log('refund', 'order', $oid, '退款订单 #' . $order['order_no'] . '，金额 ¥' . number_format($refundAmount, 2));
                $msgType = 'success';
            }
        }
        // 取消
        elseif ($action === 'cancel' && $oid > 0) {
            $order = $db->fetch('SELECT status FROM orders WHERE id=:id', [':id' => $oid]);
            if ($order && $order['status'] === 'pending') {
                $db->update('orders', ['status' => 'cancelled'], 'id = :id', [':id' => $oid]);
                $msg = '订单已取消'; $msgType = 'success';
            } else {
                $msg = '只有待处理的订单才能取消'; $msgType = 'error';
            }
        }
    }
}

// 分页
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset  = ($page - 1) * $perPage;

$where = '1=1';
$params = [];
if ($search !== '') {
    $where .= ' AND (o.order_no LIKE :s OR u.nickname LIKE :s OR u.phone LIKE :s)';
    $params[':s'] = '%' . $search . '%';
}
if ($filterStatus !== '') {
    $where .= ' AND o.status = :st';
    $params[':st'] = $filterStatus;
}
if ($filterType !== '') {
    $where .= ' AND o.order_type = :ot';
    $params[':ot'] = $filterType;
}
if ($dateFrom !== '') {
    $where .= ' AND o.created_at >= :df';
    $params[':df'] = $dateFrom . ' 00:00:00';
}
if ($dateTo !== '') {
    $where .= ' AND o.created_at <= :dt';
    $params[':dt'] = $dateTo . ' 23:59:59';
}

$total = $db->fetch(
    'SELECT COUNT(*) AS cnt FROM orders o LEFT JOIN users u ON o.user_id=u.id WHERE ' . $where,
    $params
);
$totalRows = (int)($total['cnt'] ?? 0);
$totalPages = max(1, ceil($totalRows / $perPage));

$orders = $db->fetchAll(
    "SELECT o.*, u.nickname AS user_name, u.phone AS user_phone,
            r.title AS resource_title, vp.name AS vip_plan_name
     FROM orders o
     LEFT JOIN users u ON o.user_id = u.id
     LEFT JOIN resources r ON o.resource_id = r.id
     LEFT JOIN vip_plans vp ON o.vip_plan_id = vp.id
     WHERE {$where}
     ORDER BY o.created_at DESC
     LIMIT {$perPage} OFFSET {$offset}",
    $params
);

// 详情视图
$detailOrder = null;
if (isset($_GET['view'])) {
    $vid = (int)$_GET['view'];
    if ($vid > 0) {
        $detailOrder = $db->fetch(
            'SELECT o.*, u.nickname AS user_name, u.phone AS user_phone,
                    r.title AS resource_title, vp.name AS vip_plan_name
             FROM orders o
             LEFT JOIN users u ON o.user_id = u.id
             LEFT JOIN resources r ON o.resource_id = r.id
             LEFT JOIN vip_plans vp ON o.vip_plan_id = vp.id
             WHERE o.id=:id',
            [':id' => $vid]
        );
    }
}

function buildUrl($p) {
    return 'orders.php?' . http_build_query(array_filter($p, function($v) { return $v !== '' && $v !== 0; }));
}

include __DIR__ . '/header.php';
?>

<?php if ($msg): ?>
<div class="alert auto-dismiss" style="padding:12px 16px;border-radius:var(--radius);margin-bottom:20px;font-size:14px;
  <?php echo $msgType==='success' ? 'background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0' : 'background:#fef2f2;color:#b91c1c;border:1px solid #fecaca'; ?>">
  <?php echo htmlspecialchars($msg); ?>
</div>
<?php endif; ?>

<?php if ($detailOrder): ?>
<!-- Order Detail -->
<div class="card mb-20">
  <div class="card-header">
    <h3>订单详情 #<?php echo htmlspecialchars($detailOrder['order_no']); ?></h3>
    <a href="orders.php" class="btn btn-outline btn-sm">返回列表</a>
  </div>
  <div class="card-body">
    <dl class="detail-grid">
      <dt>订单号</dt><dd><?php echo htmlspecialchars($detailOrder['order_no']); ?></dd>
      <dt>类型</dt><dd><?php echo $typeLabels[$detailOrder['order_type']] ?? '-'; ?></dd>
      <dt>用户</dt><dd><?php echo htmlspecialchars($detailOrder['user_name'] ?? '-'); ?> (<?php echo htmlspecialchars($detailOrder['user_phone'] ?? '-'); ?>)</dd>
      <dt>资源</dt><dd><?php echo htmlspecialchars($detailOrder['resource_title'] ?? '-'); ?></dd>
      <dt>VIP套餐</dt><dd><?php echo htmlspecialchars($detailOrder['vip_plan_name'] ?? '-'); ?></dd>
      <dt>金额</dt><dd>&yen;<?php echo number_format((float)$detailOrder['amount'], 2); ?></dd>
      <dt>支付金额</dt><dd>&yen;<?php echo number_format((float)$detailOrder['pay_amount'], 2); ?></dd>
      <dt>支付方式</dt><dd><?php echo $detailOrder['payment_method'] === 'wechat' ? '微信支付' : '积分'; ?></dd>
      <dt>状态</dt>
      <dd>
        <?php $sl = $statusLabels[$detailOrder['status']] ?? ['未知','badge-default']; ?>
        <span class="badge <?php echo $sl[1]; ?>"><?php echo $sl[0]; ?></span>
      </dd>
      <dt>交易号</dt><dd><?php echo htmlspecialchars($detailOrder['transaction_id'] ?? '-'); ?></dd>
      <dt>支付时间</dt><dd><?php echo $detailOrder['paid_at'] ?? '-'; ?></dd>
      <dt>退款时间</dt><dd><?php echo $detailOrder['refund_at'] ?? '-'; ?></dd>
      <dt>退款金额</dt><dd><?php echo $detailOrder['refund_amount'] ? '&yen;' . number_format((float)$detailOrder['refund_amount'], 2) : '-'; ?></dd>
      <dt>创建时间</dt><dd><?php echo $detailOrder['created_at']; ?></dd>
    </dl>

    <?php if ($detailOrder['status'] === 'paid'): ?>
    <div style="margin-top:20px;padding-top:20px;border-top:1px solid var(--border)">
      <h4 style="margin-bottom:12px">手动退款</h4>
      <form method="POST" class="d-flex align-center gap-10">
        <input type="hidden" name="_token" value="<?php echo $csrfToken; ?>">
        <input type="hidden" name="action" value="refund">
        <input type="hidden" name="id" value="<?php echo $detailOrder['id']; ?>">
        <input type="number" name="refund_amount" class="form-control" style="width:160px" step="0.01" min="0.01"
               max="<?php echo $detailOrder['pay_amount']; ?>"
               value="<?php echo $detailOrder['pay_amount']; ?>" required>
        <button type="submit" class="btn btn-danger btn-sm"
                onclick="return confirm('确定退款？')">退款</button>
      </form>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php else: ?>

<!-- Toolbar -->
<div class="toolbar">
  <div class="toolbar-left">
    <form method="GET" class="d-flex align-center gap-10" style="flex-wrap:wrap">
      <div class="search-box">
        <input type="text" name="search" placeholder="订单号 / 用户..."
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
      <input type="date" name="date_from" class="filter-select" value="<?php echo htmlspecialchars($dateFrom); ?>" placeholder="From">
      <input type="date" name="date_to" class="filter-select" value="<?php echo htmlspecialchars($dateTo); ?>" placeholder="To">
      <button type="submit" class="btn btn-primary btn-sm">筛选</button>
    </form>
  </div>
  <div class="toolbar-right">
    <a href="orders.php?export=csv&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($filterStatus); ?>&type=<?php echo urlencode($filterType); ?>&date_from=<?php echo urlencode($dateFrom); ?>&date_to=<?php echo urlencode($dateTo); ?>" class="btn btn-outline btn-sm">📥 导出CSV</a>
  </div>
</div>

<!-- Orders Table -->
<div class="card">
  <div class="card-body no-padding">
    <?php if (empty($orders)): ?>
    <div class="empty-state">
      <div class="empty-icon">💰</div>
      <h4>暂无订单</h4>
    </div>
    <?php else: ?>
    <div class="table-wrapper">
      <table>
        <thead>
          <tr>
            <th>订单号</th>
            <th>类型</th>
            <th>用户</th>
            <th>项目</th>
            <th>金额</th>
            <th>支付方式</th>
            <th>状态</th>
            <th>创建时间</th>
            <th>操作</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($orders as $o): ?>
          <tr>
            <td style="font-size:12px;"><?php echo htmlspecialchars($o['order_no']); ?></td>
            <td><span class="badge <?php echo $o['order_type']==='vip'?'badge-info':'badge-default'; ?>">
              <?php echo $typeLabels[$o['order_type']] ?? '-'; ?></span></td>
            <td><?php echo htmlspecialchars($o['user_name'] ?? '-'); ?></td>
            <td style="max-width:180px;white-space:normal;">
              <?php echo htmlspecialchars($o['resource_title'] ?? $o['vip_plan_name'] ?? '-'); ?>
            </td>
            <td>&yen;<?php echo number_format((float)$o['pay_amount'], 2); ?></td>
            <td><?php echo $o['payment_method']==='wechat'?'微信':'积分'; ?></td>
            <td>
              <?php $sl = $statusLabels[$o['status']] ?? ['未知','badge-default']; ?>
              <span class="badge <?php echo $sl[1]; ?>"><?php echo $sl[0]; ?></span>
            </td>
            <td><?php echo substr($o['created_at'], 0, 16); ?></td>
            <td class="actions">
              <a href="orders.php?view=<?php echo $o['id']; ?>" class="btn btn-outline btn-sm">详情</a>
              <?php if ($o['status'] === 'paid'): ?>
              <form method="POST" style="display:inline" onsubmit="return confirm('确定对此订单退款？')">
                <input type="hidden" name="_token" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="action" value="refund">
                <input type="hidden" name="id" value="<?php echo $o['id']; ?>">
                <input type="hidden" name="refund_amount" value="<?php echo $o['pay_amount']; ?>">
                <button class="btn btn-danger btn-sm">退款</button>
              </form>
              <?php endif; ?>
              <?php if ($o['status'] === 'pending'): ?>
              <form method="POST" style="display:inline" onsubmit="return confirm('确定取消此订单？')">
                <input type="hidden" name="_token" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="action" value="cancel">
                <input type="hidden" name="id" value="<?php echo $o['id']; ?>">
                <button class="btn btn-warning btn-sm">取消</button>
              </form>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div class="pagination">
      <span class="page-info">共 <?php echo $totalRows; ?> 个订单，第 <?php echo $page; ?>/<?php echo $totalPages; ?> 页</span>
      <div class="page-links">
        <?php if ($page > 1): ?>
        <a href="<?php echo buildUrl(['search'=>$search,'status'=>$filterStatus,'type'=>$filterType,'date_from'=>$dateFrom,'date_to'=>$dateTo,'page'=>$page-1]); ?>">&laquo; 上一页</a>
        <?php else: ?><span class="disabled">&laquo; 上一页</span><?php endif; ?>
        <?php $s=max(1,$page-3);$e=min($totalPages,$page+3); for($p=$s;$p<=$e;$p++): ?>
        <a href="<?php echo buildUrl(['search'=>$search,'status'=>$filterStatus,'type'=>$filterType,'date_from'=>$dateFrom,'date_to'=>$dateTo,'page'=>$p]); ?>"
           class="<?php echo $p===$page?'active':''; ?>"><?php echo $p; ?></a>
        <?php endfor; ?>
        <?php if ($page < $totalPages): ?>
        <a href="<?php echo buildUrl(['search'=>$search,'status'=>$filterStatus,'type'=>$filterType,'date_from'=>$dateFrom,'date_to'=>$dateTo,'page'=>$page+1]); ?>">下一页 &raquo;</a>
        <?php else: ?><span class="disabled">下一页 &raquo;</span><?php endif; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php endif; ?>

<?php include __DIR__ . '/footer.php'; ?>
