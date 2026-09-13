<?php
/**
 * 管理后台 - 系统设置
 * 分组设置表单：基础、下载、支付、积分
 */
session_start();
if (empty($_SESSION['admin_id'])) { header('Location: login.php'); exit; }

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/AdminLog.php';
$db = Database::getInstance();
$csrfToken = $_SESSION['csrf_token'] ?? '';

$msg = '';
$msgType = '';

// 默认设置定义
$settingDefs = [
    'basic' => [
        ['key' => 'site_name',         'label' => '站点名称',         'type' => 'text',   'default' => ''],
        ['key' => 'admin_per_page',    'label' => '后台每页显示条数', 'type' => 'number', 'default' => '15'],
        ['key' => 'app_page_size',     'label' => '小程序每页显示条数', 'type' => 'number', 'default' => '10'],
        ['key' => 'site_description',  'label' => '站点描述',         'type' => 'text',   'default' => ''],
        ['key' => 'site_keywords',     'label' => '站点关键词',       'type' => 'text',   'default' => ''],
        ['key' => 'site_logo',         'label' => '站点Logo地址',     'type' => 'text',   'default' => ''],
        ['key' => 'icp_beian',         'label' => 'ICP备案号',        'type' => 'text',   'default' => ''],
        ['key' => 'contact_email',     'label' => '联系邮箱',         'type' => 'text',   'default' => ''],
        ['key' => 'contact_phone',     'label' => '联系电话',         'type' => 'text',   'default' => ''],
        ['key' => 'comment_enabled',   'label' => '用户评论功能',      'type' => 'switch', 'default' => '1'],
        ['key' => 'feedback_enabled',  'label' => '意见反馈入口',      'type' => 'switch', 'default' => '1'],
        ['key' => 'activity_enabled',  'label' => '活动中心功能',      'type' => 'switch', 'default' => '1'],
    ],
    'download' => [
        ['key' => 'home_recommend_count', 'label' => '首页推荐资源条数', 'type' => 'number', 'default' => '10'],
        ['key' => 'free_download_limit', 'label' => '每日免费下载次数', 'type' => 'number', 'default' => '3'],
        ['key' => 'vip_download_limit',  'label' => 'VIP每日下载次数',  'type' => 'number', 'default' => '999'],
        ['key' => 'upload_max_size',     'label' => '最大上传大小 (MB)', 'type' => 'number', 'default' => '100'],
        ['key' => 'allowed_extensions',  'label' => '允许的扩展名',     'type' => 'text',   'default' => 'pdf,doc,docx,xls,xlsx,ppt,pptx,zip,rar,7z'],
    ],
    'payment' => [
        ['key' => 'wechat_appid',       'label' => '微信小程序AppID',  'type' => 'text', 'default' => ''],
        ['key' => 'wechat_secret',      'label' => '微信小程序Secret', 'type' => 'text', 'default' => ''],
        ['key' => 'wechat_mch_id',      'label' => '微信商户号',       'type' => 'text', 'default' => ''],
        ['key' => 'wechat_api_key',     'label' => '微信API密钥',      'type' => 'text', 'default' => ''],
        ['key' => 'wechat_notify_url',  'label' => '支付回调地址',     'type' => 'text', 'default' => ''],
        ['key' => 'min_pay_amount',     'label' => '最低支付金额 (&yen;)', 'type' => 'number', 'default' => '0.01'],
    ],
    'points' => [
        // ── 积分发放值 ──
        ['key' => 'register_gift_points',   'label' => '注册赠送积分',         'type' => 'number', 'default' => '50'],
        ['key' => 'daily_signin_points',    'label' => '每日签到积分',         'type' => 'number', 'default' => '5'],
        ['key' => 'signin_streak_bonus',    'label' => '连续签到每天额外奖励',  'type' => 'number', 'default' => '1'],
        ['key' => 'signin_streak_max',      'label' => '连续签到奖励上限天数',  'type' => 'number', 'default' => '7'],
        ['key' => 'points_per_download',    'label' => '每次下载获得积分',      'type' => 'number', 'default' => '10'],
        ['key' => 'points_per_share',       'label' => '每次分享获得积分',      'type' => 'number', 'default' => '2'],
        ['key' => 'points_per_comment',     'label' => '每次评论获得积分',      'type' => 'number', 'default' => '3'],
        ['key' => 'points_per_favorite',    'label' => '每次收藏获得积分',      'type' => 'number', 'default' => '1'],
        ['key' => 'points_complete_profile','label' => '完善资料奖励积分',      'type' => 'number', 'default' => '20'],
        ['key' => 'points_per_invite',      'label' => '被邀请者奖励积分',      'type' => 'number', 'default' => '30'],
        ['key' => 'points_invite_reward',   'label' => '邀请者奖励积分',        'type' => 'number', 'default' => '20'],
        ['key' => 'points_per_yuan',        'label' => '每元对应积分',          'type' => 'number', 'default' => '100'],
        // ── 限制次数 ──
        ['key' => 'limit_signin_daily',     'label' => '每日签到上限次数',      'type' => 'number', 'default' => '1'],
        ['key' => 'limit_download_daily',   'label' => '每日下载积分上限次数',   'type' => 'number', 'default' => '0'],
        ['key' => 'limit_share_daily',      'label' => '每日分享积分上限次数',   'type' => 'number', 'default' => '10'],
        ['key' => 'limit_share_per_resource','label' => '单资源分享积分上限',    'type' => 'number', 'default' => '1'],
        ['key' => 'limit_comment_per_resource','label' => '单资源评论积分上限',  'type' => 'number', 'default' => '1'],
        ['key' => 'limit_favorite_daily',   'label' => '每日收藏积分上限次数',   'type' => 'number', 'default' => '5'],
        ['key' => 'limit_invite_total',     'label' => '邀请码使用总上限',      'type' => 'number', 'default' => '3'],
        // ── 开关 ──
        ['key' => 'points_enabled',         'label' => '积分系统总开关',        'type' => 'switch', 'default' => '1'],
        ['key' => 'points_signin_enabled',  'label' => '签到积分开关',          'type' => 'switch', 'default' => '1'],
        ['key' => 'points_download_enabled','label' => '下载积分开关',          'type' => 'switch', 'default' => '1'],
        ['key' => 'points_share_enabled',   'label' => '分享积分开关',          'type' => 'switch', 'default' => '1'],
        ['key' => 'points_comment_enabled', 'label' => '评论积分开关',          'type' => 'switch', 'default' => '1'],
        ['key' => 'points_invite_enabled',  'label' => '邀请积分开关',          'type' => 'switch', 'default' => '1'],
        ['key' => 'points_redeem_enabled',  'label' => '积分兑换开关',          'type' => 'switch', 'default' => '1'],
    ],
    'about' => [
        ['key' => 'about_version',     'label' => '版本号',       'type' => 'text',     'default' => 'v1.0.0'],
        ['key' => 'about_content',     'label' => '关于我们内容', 'type' => 'textarea', 'default' => '海量优质资源，助力高效工作'],
        ['key' => 'about_qrcode',      'label' => '客服/群二维码图片地址', 'type' => 'text', 'default' => ''],
        ['key' => 'about_qrcode_title','label' => '二维码标题',   'type' => 'text',     'default' => '扫码添加客服'],
        ['key' => 'about_qrcode_desc', 'label' => '二维码描述',   'type' => 'text',     'default' => '长按识别二维码，添加客服微信'],
        ['key' => 'about_copyright',   'label' => '版权声明',     'type' => 'text',     'default' => '© 2026 资源下载平台'],
    ],
];

$groupLabels = [
    'basic'    => '基础设置',
    'download' => '下载设置',
    'payment'  => '支付设置',
    'points'   => '积分设置',
    'about'    => '关于我们',
];

// 加载所有当前设置
$allSettings = $db->fetchAll('SELECT setting_key, setting_value, setting_group FROM system_settings');
$settings = [];
foreach ($allSettings as $s) {
    $settings[$s['setting_key']] = $s['setting_value'];
}

// 保存
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['_token'] ?? '';
    if ($token !== $csrfToken) {
        $msg = 'CSRF令牌无效'; $msgType = 'error';
    } else {
        $saved = 0;
        foreach ($settingDefs as $group => $defs) {
            foreach ($defs as $def) {
                $key = $def['key'];
                $val = trim($_POST[$key] ?? $def['default']);
                $existing = $db->fetch(
                    'SELECT id FROM system_settings WHERE setting_key=:k LIMIT 1',
                    [':k' => $key]
                );
                if (empty($existing)) {
                    $db->insert('system_settings', [
                        'setting_key'   => $key,
                        'setting_value' => $val,
                        'setting_group' => $group,
                    ]);
                } else {
                    $db->update('system_settings', [
                        'setting_value' => $val,
                    ], 'setting_key = :k', [':k' => $key]);
                }
                $settings[$key] = $val;
                $saved++;
            }
        }
        $msg = "已保存 {$saved} 项设置";
        AdminLog::log('save_settings', 'setting', 0, '保存系统设置，共 ' . $saved . ' 项');
        $msgType = 'success';
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

<form method="POST" action="settings.php">
  <input type="hidden" name="_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

  <?php foreach ($settingDefs as $group => $defs): ?>
  <div class="settings-group">
    <h3><?php echo $groupLabels[$group] ?? $group; ?></h3>

    <div class="card">
      <div class="card-body">
        <?php foreach ($defs as $def): ?>
        <div class="form-group">
          <label><?php echo htmlspecialchars($def['label']); ?></label>
          <?php if ($def['type'] === 'number'): ?>
          <input type="number" name="<?php echo htmlspecialchars($def['key']); ?>"
                 class="form-control" step="any" min="0"
                 value="<?php echo htmlspecialchars($settings[$def['key']] ?? $def['default']); ?>">
          <?php elseif ($def['type'] === 'switch'): ?>
          <div style="display:inline-flex;align-items:center;gap:10px;">
            <input type="hidden" name="<?php echo htmlspecialchars($def['key']); ?>" id="input-<?php echo $def['key']; ?>" value="<?php echo htmlspecialchars($settings[$def['key']] ?? $def['default']); ?>">
            <div id="switch-<?php echo $def['key']; ?>"
                 onclick="toggleSwitch('<?php echo $def['key']; ?>')"
                 style="display:inline-block;width:44px;height:24px;border-radius:12px;position:relative;cursor:pointer;transition:background 0.3s;background:<?php echo ($settings[$def['key']] ?? $def['default']) === '1' ? '#6C63FF' : '#ccc'; ?>;">
              <div id="knob-<?php echo $def['key']; ?>" style="display:block;width:20px;height:20px;border-radius:50%;background:#fff;position:absolute;top:2px;left:<?php echo ($settings[$def['key']] ?? $def['default']) === '1' ? '22px' : '2px'; ?>;transition:left 0.3s;box-shadow:0 1px 3px rgba(0,0,0,0.2);"></div>
            </div>
            <span id="switch-text-<?php echo $def['key']; ?>" style="font-size:13px;color:#666;"><?php echo ($settings[$def['key']] ?? $def['default']) === '1' ? '已开启' : '已关闭'; ?></span>
          </div>
          <?php elseif ($def['type'] === 'textarea'): ?>
          <textarea name="<?php echo htmlspecialchars($def['key']); ?>"
                    class="form-control" rows="3"><?php echo htmlspecialchars($settings[$def['key']] ?? $def['default']); ?></textarea>
          <?php elseif ($def['key'] === 'contact_email'): ?>
          <input type="email" name="<?php echo htmlspecialchars($def['key']); ?>"
                 class="form-control" data-validate="email"
                 value="<?php echo htmlspecialchars($settings[$def['key']] ?? $def['default']); ?>"
                 placeholder="example@domain.com">
          <?php elseif (in_array($def['key'], ['site_logo', 'wechat_notify_url'])): ?>
          <input type="url" name="<?php echo htmlspecialchars($def['key']); ?>"
                 class="form-control" data-validate="url"
                 value="<?php echo htmlspecialchars($settings[$def['key']] ?? $def['default']); ?>"
                 placeholder="https://...">
          <?php elseif ($def['key'] === 'about_qrcode'): ?>
          <div style="display:flex;align-items:flex-start;gap:16px;">
            <div id="qrcode-preview" style="width:120px;height:120px;border:2px dashed #ddd;border-radius:8px;display:flex;align-items:center;justify-content:center;overflow:hidden;background:#fafafa;flex-shrink:0;">
              <?php $qv = $settings[$def['key']] ?? ''; if ($qv): ?>
              <img src="<?php echo htmlspecialchars($qv); ?>" style="width:100%;height:100%;object-fit:contain;" onerror="this.parentElement.innerHTML='<span style=color:#999>图片加载失败</span>'">
              <?php else: ?>
              <span style="color:#999;font-size:12px;text-align:center;">点击上传<br>二维码图片</span>
              <?php endif; ?>
            </div>
            <div style="flex:1;">
              <input type="file" id="qrcode-file" accept="image/*" style="display:none" onchange="uploadQrcode(this)">
              <button type="button" class="btn btn-outline btn-sm" onclick="document.getElementById('qrcode-file').click()">📷 上传二维码</button>
              <button type="button" class="btn btn-outline btn-sm" onclick="clearQrcode()" style="margin-left:8px;">清除</button>
              <input type="text" name="<?php echo htmlspecialchars($def['key']); ?>" id="qrcode-input"
                     class="form-control" style="margin-top:8px;"
                     value="<?php echo htmlspecialchars($settings[$def['key']] ?? $def['default']); ?>"
                     placeholder="或直接输入图片地址">
              <small style="color:#999;">支持 jpg/png/gif，建议 300×300 以上</small>
            </div>
          </div>
          <?php else: ?>
          <input type="text" name="<?php echo htmlspecialchars($def['key']); ?>"
                 class="form-control"
                 value="<?php echo htmlspecialchars($settings[$def['key']] ?? $def['default']); ?>">
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <?php endforeach; ?>

  <div style="padding-bottom:40px;">
    <button type="submit" class="btn btn-primary btn-lg">保存所有设置</button>
  </div>
</form>

<script>
function toggleSwitch(key) {
  var input = document.getElementById('input-' + key);
  var toggle = document.getElementById('switch-' + key);
  var knob = document.getElementById('knob-' + key);
  var text = document.getElementById('switch-text-' + key);
  if (!input || !toggle || !knob) return;
  var isOn = input.value === '1';
  if (isOn) {
    input.value = '0';
    toggle.style.background = '#ccc';
    knob.style.left = '2px';
    if (text) text.textContent = '已关闭';
  } else {
    input.value = '1';
    toggle.style.background = '#6C63FF';
    knob.style.left = '22px';
    if (text) text.textContent = '已开启';
  }
}
</script>

<?php
$extraJs = "
document.addEventListener('DOMContentLoaded', function() {
  var form = document.querySelector('form[action=\"settings.php\"]');
  if (!form) return;
  form.addEventListener('submit', function(e) {
    var errors = [];
    // Validate email fields
    var emailInputs = form.querySelectorAll('input[data-validate=\"email\"]');
    for (var i = 0; i < emailInputs.length; i++) {
      var val = emailInputs[i].value.trim();
      if (val && !/^[^\\s@]+@[^\\s@]+\\.[^\\s@]+$/.test(val)) {
        errors.push(emailInputs[i].closest('.form-group').querySelector('label').textContent.trim() + ' 格式不正确');
        emailInputs[i].classList.add('is-invalid');
      } else {
        emailInputs[i].classList.remove('is-invalid');
      }
    }
    // Validate URL fields
    var urlInputs = form.querySelectorAll('input[data-validate=\"url\"]');
    for (var j = 0; j < urlInputs.length; j++) {
      var val2 = urlInputs[j].value.trim();
      if (val2 && !/^https?:\\/\\/.+/.test(val2)) {
        errors.push(urlInputs[j].closest('.form-group').querySelector('label').textContent.trim() + ' 必须以 http:// 或 https:// 开头');
        urlInputs[j].classList.add('is-invalid');
      } else {
        urlInputs[j].classList.remove('is-invalid');
      }
    }
    if (errors.length > 0) {
      e.preventDefault();
      Admin.toast(errors.join('\\n'), 'error');
    }
  });
});
";
?>

<?php include __DIR__ . '/footer.php'; ?>

<script>
function uploadQrcode(input) {
  var file = input.files[0];
  if (!file) return;
  var fd = new FormData();
  fd.append('file', file);
  fd.append('type', 'image');
  var xhr = new XMLHttpRequest();
  xhr.open('POST', 'upload_api.php', true);
  xhr.onload = function() {
    if (xhr.status === 200) {
      try {
        var res = JSON.parse(xhr.responseText);
        if (res.code === 0 && res.data && res.data.url) {
          document.getElementById('qrcode-input').value = res.data.url;
          document.getElementById('qrcode-preview').innerHTML = '<img src="' + res.data.url + '" style="width:100%;height:100%;object-fit:contain;">';
        } else {
          alert('上传失败: ' + (res.message || '未知错误'));
        }
      } catch(e) { alert('上传失败'); }
    } else { alert('上传失败: HTTP ' + xhr.status); }
  };
  xhr.onerror = function() { alert('网络错误'); };
  xhr.send(fd);
  input.value = '';
}
function clearQrcode() {
  document.getElementById('qrcode-input').value = '';
  document.getElementById('qrcode-preview').innerHTML = '<span style="color:#999;font-size:12px;text-align:center;">点击上传<br>二维码图片</span>';
}
</script>
