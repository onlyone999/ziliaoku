<?php
/**
 * 管理后台 - 修改密码
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
    $currentPwd = trim($_POST['current_password'] ?? '');
    $newPwd     = trim($_POST['new_password'] ?? '');
    $confirmPwd = trim($_POST['confirm_password'] ?? '');

    if ($currentPwd === '' || $newPwd === '' || $confirmPwd === '') {
        $msg = '请填写所有字段';
        $msgType = 'error';
    } elseif (strlen($newPwd) < 6) {
        $msg = '新密码长度至少6位';
        $msgType = 'error';
    } elseif ($newPwd !== $confirmPwd) {
        $msg = '两次输入的新密码不一致';
        $msgType = 'error';
    } else {
        $adminId = (int)$_SESSION['admin_id'];
        $admin = $db->fetchOne('SELECT password FROM admin_users WHERE id = ?', [$adminId]);

        if (!$admin || !password_verify($currentPwd, $admin['password'])) {
            $msg = '当前密码错误';
            $msgType = 'error';
        } else {
            $newHash = password_hash($newPwd, PASSWORD_DEFAULT);
            $db->update('admin_users', ['password' => $newHash], 'id = ?', [$adminId]);

            AdminLog::log('change_password', 'admin', $adminId, '管理员修改了登录密码');

            $msg = '密码修改成功';
            $msgType = 'success';
        }
    }
}

require_once 'header.php';
?>

<div class="content-card" style="max-width:500px;margin:40px auto;">
    <h3 style="margin-bottom:24px;">修改密码</h3>

    <?php if ($msg): ?>
    <div class="alert alert-<?php echo $msgType; ?>" style="padding:12px 16px;border-radius:6px;margin-bottom:20px;
        <?php echo $msgType === 'success' ? 'background:#d4edda;color:#155724;border:1px solid #c3e6cb;' : 'background:#f8d7da;color:#721c24;border:1px solid #f5c6cb;'; ?>">
        <?php echo htmlspecialchars($msg); ?>
    </div>
    <?php endif; ?>

    <form method="post" action="password.php">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

        <div class="form-group" style="margin-bottom:18px;">
            <label style="display:block;margin-bottom:6px;font-weight:600;">当前密码</label>
            <input type="password" name="current_password" required
                   style="width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:6px;font-size:14px;box-sizing:border-box;">
        </div>

        <div class="form-group" style="margin-bottom:18px;">
            <label style="display:block;margin-bottom:6px;font-weight:600;">新密码</label>
            <input type="password" name="new_password" required minlength="6"
                   style="width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:6px;font-size:14px;box-sizing:border-box;">
            <small style="color:#888;">至少6位字符</small>
        </div>

        <div class="form-group" style="margin-bottom:24px;">
            <label style="display:block;margin-bottom:6px;font-weight:600;">确认新密码</label>
            <input type="password" name="confirm_password" required minlength="6"
                   style="width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:6px;font-size:14px;box-sizing:border-box;">
        </div>

        <button type="submit" class="btn btn-primary" style="width:100%;padding:12px;font-size:15px;border:none;border-radius:6px;background:#6366f1;color:#fff;cursor:pointer;">
            确认修改
        </button>
    </form>
</div>

<?php require_once 'footer.php'; ?>
