<?php
/**
 * 管理后台 - 登录页面
 * 使用bcrypt验证admin_users表
 */
session_start();

// 已经登录？
if (!empty($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = '请输入用户名和密码';
    } else {
        require_once __DIR__ . '/../core/Database.php';
        $db = Database::getInstance();
        $admin = $db->fetch(
            'SELECT * FROM admin_users WHERE username = :u AND status = 1 LIMIT 1',
            [':u' => $username]
        );

        if (empty($admin)) {
            $error = '用户名或密码错误';
        } elseif (!password_verify($password, $admin['password'])) {
            $error = '用户名或密码错误';
        } else {
            // 更新最后登录时间
            $db->update('admin_users', [
                'last_login_at' => date('Y-m-d H:i:s'),
            ], 'id = :id', [':id' => $admin['id']]);

            // 设置会话
            session_regenerate_id(true);
            $_SESSION['admin_id']       = (int) $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_realname'] = $admin['realname'] ?? '';
            $_SESSION['admin_role']     = $admin['role'];
            $_SESSION['admin_avatar']   = $admin['avatar'] ?? '';
            $_SESSION['csrf_token']     = bin2hex(random_bytes(32));

            header('Location: dashboard.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>管理后台登录</title>
  <link rel="stylesheet" href="assets/css/admin.css">
  <style>
    /* 动态粒子背景 */
    body::before {
      content: '';
      position: fixed;
      top: 0; left: 0; right: 0; bottom: 0;
      background: linear-gradient(135deg, #0f0c29, #302b63, #24243e);
      z-index: -2;
    }
    body::after {
      content: '';
      position: fixed;
      top: 0; left: 0; right: 0; bottom: 0;
      background:
        radial-gradient(circle at 20% 80%, rgba(120,119,198,0.3) 0%, transparent 50%),
        radial-gradient(circle at 80% 20%, rgba(78,205,196,0.2) 0%, transparent 50%),
        radial-gradient(circle at 50% 50%, rgba(108,99,255,0.15) 0%, transparent 70%);
      z-index: -1;
      animation: bgPulse 8s ease-in-out infinite;
    }
    @keyframes bgPulse {
      0%, 100% { opacity: 1; }
      50% { opacity: 0.7; }
    }

    .login-wrapper {
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      padding: 20px;
    }

    .login-box {
      background: rgba(255,255,255,0.95);
      backdrop-filter: blur(20px);
      border-radius: 24px;
      padding: 48px 40px;
      width: 100%;
      max-width: 420px;
      box-shadow:
        0 20px 60px rgba(0,0,0,0.3),
        0 0 0 1px rgba(255,255,255,0.1) inset;
      animation: cardFloat 0.8s ease-out;
    }
    @keyframes cardFloat {
      from { opacity: 0; transform: translateY(40px) scale(0.95); }
      to { opacity: 1; transform: translateY(0) scale(1); }
    }

    .login-logo {
      text-align: center;
      margin-bottom: 32px;
    }
    .login-logo .logo-circle {
      width: 80px;
      height: 80px;
      margin: 0 auto 20px;
      background: linear-gradient(135deg, #6366f1, #8b5cf6);
      border-radius: 24px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 36px;
      color: #fff;
      font-weight: 800;
      box-shadow: 0 12px 32px rgba(108,99,255,0.4);
      animation: logoPulse 3s ease-in-out infinite;
    }
    @keyframes logoPulse {
      0%, 100% { transform: scale(1); box-shadow: 0 12px 32px rgba(108,99,255,0.4); }
      50% { transform: scale(1.08); box-shadow: 0 16px 40px rgba(108,99,255,0.5); }
    }
    .login-logo h1 {
      font-size: 28px;
      font-weight: 800;
      color: #1a1a2e;
      margin: 0 0 8px;
    }
    .login-logo .subtitle {
      font-size: 14px;
      color: #94a3b8;
      margin: 0;
    }

    .login-box .form-group {
      margin-bottom: 20px;
    }
    .login-box label {
      display: block;
      font-size: 14px;
      font-weight: 600;
      color: #374151;
      margin-bottom: 8px;
    }
    .login-box input[type="text"],
    .login-box input[type="password"] {
      width: 100%;
      height: 48px;
      background: #f8fafc;
      border: 2px solid #e5e7eb;
      border-radius: 12px;
      padding: 0 16px;
      font-size: 15px;
      color: #1f2937;
      transition: all 0.3s;
      box-sizing: border-box;
    }
    .login-box input[type="text"]:focus,
    .login-box input[type="password"]:focus {
      background: #fff;
      border-color: #8b5cf6;
      box-shadow: 0 0 0 4px rgba(108,99,255,0.1);
      outline: none;
    }

    .login-box .btn-primary {
      width: 100%;
      height: 52px;
      background: linear-gradient(135deg, #6366f1, #8b5cf6);
      color: #fff;
      border: none;
      border-radius: 14px;
      font-size: 16px;
      font-weight: 700;
      cursor: pointer;
      transition: all 0.3s;
      box-shadow: 0 8px 24px rgba(108,99,255,0.35);
      margin-top: 8px;
    }
    .login-box .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 12px 32px rgba(108,99,255,0.45);
    }
    .login-box .btn-primary:active {
      transform: translateY(0);
    }

    .login-error {
      background: #fef2f2;
      color: #dc2626;
      padding: 12px 16px;
      border-radius: 10px;
      font-size: 14px;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 8px;
      border: 1px solid #fecaca;
      animation: shake 0.5s ease-in-out;
    }
    @keyframes shake {
      0%, 100% { transform: translateX(0); }
      20%, 60% { transform: translateX(-5px); }
      40%, 80% { transform: translateX(5px); }
    }

    .login-footer {
      text-align: center;
      margin-top: 24px;
      font-size: 12px;
      color: #94a3b8;
    }
  </style>
</head>
<body>
<div class="login-wrapper">
  <div class="login-box">
    <div class="login-logo">
      <div class="logo-circle">Z</div>
      <h1>管理后台</h1>
      <p class="subtitle">资料库管理系统</p>
    </div>

    <?php if ($error): ?>
    <div class="login-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" action="login.php" autocomplete="off">
      <div class="form-group">
        <label for="username">用户名</label>
        <div class="input-icon">
          <input type="text" id="username" name="username" class="form-control"
                 placeholder="请输入管理员用户名"
                 value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                 autofocus required>
        </div>
      </div>

      <div class="form-group">
        <label for="password">密码</label>
        <div class="input-icon">
          <input type="password" id="password" name="password" class="form-control"
                 placeholder="请输入密码" required>
        </div>
      </div>

      <button type="submit" class="btn btn-primary btn-block btn-lg">登录</button>
    </form>

    <div class="login-footer">
      &copy; <?php echo date('Y'); ?> 资料库管理系统
    </div>
  </div>
</div>
</body>
</html>
