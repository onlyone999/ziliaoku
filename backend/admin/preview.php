<?php
/**
 * 文件预览页面
 * 支持 PDF / Office / TXT代码 / 图片 / 音视频 / 压缩包
 * 根据 resource_id 自动判断预览方案
 */
session_start();
if (empty($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../core/Database.php';
$db = Database::getInstance();

$resourceId = (int) ($_GET['id'] ?? 0);
if ($resourceId <= 0) {
    echo '<div style="text-align:center;padding:60px;color:#999;">缺少资源ID</div>';
    exit;
}

$resource = $db->fetch(
    'SELECT r.*, c.name as category_name FROM resources r LEFT JOIN categories c ON r.category_id=c.id WHERE r.id=:id LIMIT 1',
    [':id' => $resourceId]
);
if (empty($resource)) {
    echo '<div style="text-align:center;padding:60px;color:#999;">资源不存在</div>';
    exit;
}

$suffix = strtolower($resource['file_suffix'] ?? pathinfo($resource['file_url'], PATHINFO_EXTENSION));

// 预览类型判断
$officeExts = ['doc','docx','xls','xlsx','ppt','pptx'];
$textExts   = ['txt','md','csv','log','ini','conf','json','xml','yaml','yml','toml',
               'html','htm','css','js','ts','jsx','tsx','vue','php','py','java','c',
               'cpp','h','hpp','cs','go','rs','rb','swift','kt','sh','bash','bat',
               'ps1','sql','r','lua','perl','pl'];
$imageExts  = ['jpg','jpeg','png','gif','webp','bmp','svg','ico'];
$audioExts  = ['mp3','wav','ogg','flac','aac','wma','m4a'];
$videoExts  = ['mp4','avi','mov','wmv','flv','mkv','webm'];
$archiveExts= ['zip','rar','7z','tar','gz'];

if ($suffix === 'pdf')                        $previewType = 'pdf';
elseif (in_array($suffix, $officeExts))       $previewType = 'office';
elseif (in_array($suffix, $textExts))         $previewType = 'text';
elseif (in_array($suffix, $imageExts))        $previewType = 'image';
elseif (in_array($suffix, $audioExts))        $previewType = 'audio';
elseif (in_array($suffix, $videoExts))        $previewType = 'video';
elseif (in_array($suffix, $archiveExts))      $previewType = 'archive';
else                                          $previewType = 'download';

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host   = $_SERVER['HTTP_HOST'] ?? 'localhost:9901';
$baseUrl= $scheme . '://' . $host;
$fileUrl= $resource['file_url'];
$absUrl = (strpos($fileUrl, 'http') === 0) ? $fileUrl : $baseUrl . '/' . ltrim($fileUrl, '/');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>预览 - <?php echo htmlspecialchars($resource['title']); ?></title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:#0f172a;color:#e2e8f0;overflow:hidden;height:100vh}

/* 顶部工具栏 */
.preview-toolbar{
  height:52px;background:linear-gradient(135deg,#1e293b,#0f172a);
  display:flex;align-items:center;padding:0 20px;gap:16px;
  border-bottom:1px solid #334155;z-index:100;
}
.preview-toolbar .file-info{flex:1;display:flex;align-items:center;gap:12px;overflow:hidden}
.preview-toolbar .file-icon{
  width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;
  font-size:18px;font-weight:700;flex-shrink:0;
}
.preview-toolbar .file-name{font-size:15px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.preview-toolbar .file-meta{font-size:12px;color:#94a3b8;white-space:nowrap}
.preview-toolbar .toolbar-actions{display:flex;gap:8px}
.preview-toolbar .btn{
  height:34px;padding:0 14px;border:1px solid #475569;border-radius:8px;
  background:transparent;color:#e2e8f0;font-size:13px;cursor:pointer;
  display:flex;align-items:center;gap:6px;transition:all .2s;text-decoration:none;
}
.preview-toolbar .btn:hover{background:#334155;border-color:#64748b}
.preview-toolbar .btn-primary{background:linear-gradient(135deg,#6366f1,#8b5cf6);border-color:transparent}
.preview-toolbar .btn-primary:hover{opacity:.9}
.btn-close{width:34px;height:34px;padding:0;justify-content:center;font-size:18px}

/* 预览区域 */
.preview-container{height:calc(100vh - 52px);overflow:auto;position:relative}
.preview-container iframe{width:100%;height:100%;border:none}
.preview-container img{max-width:100%;max-height:100%;object-fit:contain;display:block;margin:auto;cursor:zoom-in}
.preview-container video,.preview-container audio{display:block;margin:40px auto;max-width:90%}

/* 文本/代码预览 */
.code-preview{
  padding:20px;overflow:auto;height:100%;font-size:14px;line-height:1.7;
}
.code-preview pre{
  background:#1e293b;border:1px solid #334155;border-radius:12px;padding:24px;
  overflow-x:auto;font-family:'JetBrains Mono','Fira Code','Cascadia Code',Consolas,monospace;
  font-size:13px;line-height:1.8;tab-size:4;white-space:pre-wrap;word-wrap:break-word;
}
.code-header{
  display:flex;align-items:center;justify-content:space-between;padding:12px 24px;
  background:#1e293b;border:1px solid #334155;border-bottom:none;border-radius:12px 12px 0 0;
}
.code-header .lang-badge{
  background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;
  padding:3px 10px;border-radius:6px;font-size:11px;font-weight:600;text-transform:uppercase;
}
.code-preview .code-with-header pre{border-radius:0 0 12px 12px;border-top:none}

/* 图片预览 */
.image-preview{
  display:flex;align-items:center;justify-content:center;height:100%;
  background:#0f172a;position:relative;
}
.image-preview img{
  max-width:95%;max-height:95%;object-fit:contain;border-radius:8px;
  box-shadow:0 8px 32px rgba(0,0,0,0.5);cursor:zoom-in;transition:transform .3s;
}
.image-preview img.zoomed{max-width:none;max-height:none;cursor:zoom-out}

/* PDF 预览 */
.pdf-preview{height:100%}
.pdf-preview iframe{width:100%;height:100%;border:none}

/* Office 预览 */
.office-preview{height:100%;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:20px}
.office-preview .loading{color:#94a3b8;font-size:15px}
.office-preview iframe{width:100%;height:100%;border:none}

/* 压缩包预览 */
.archive-preview{padding:20px;overflow:auto;height:100%}
.archive-header{
  display:flex;align-items:center;gap:20px;padding:16px 20px;
  background:linear-gradient(135deg,#1e293b,#0f172a);border:1px solid #334155;
  border-radius:12px;margin-bottom:16px;
}
.archive-header .stat{text-align:center}
.archive-header .stat .num{font-size:20px;font-weight:700;color:#8b5cf6}
.archive-header .stat .label{font-size:11px;color:#94a3b8;margin-top:2px}
.file-tree{
  background:#1e293b;border:1px solid #334155;border-radius:12px;overflow:hidden;
}
.file-tree-item{
  display:flex;align-items:center;gap:10px;padding:10px 16px;
  border-bottom:1px solid #1e293b;cursor:default;transition:background .15s;
}
.file-tree-item:last-child{border-bottom:none}
.file-tree-item:hover{background:#334155}
.file-tree-item .fi{font-size:16px;width:20px;text-align:center;flex-shrink:0}
.file-tree-item .fname{flex:1;font-size:13px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.file-tree-item .fsize{font-size:12px;color:#94a3b8;white-space:nowrap}
.file-tree-item .fpreview{
  font-size:11px;padding:3px 8px;border-radius:4px;border:1px solid #475569;
  background:transparent;color:#94a3b8;cursor:pointer;transition:all .2s;
}
.file-tree-item .fpreview:hover{background:#8b5cf6;color:#fff;border-color:#8b5cf6}
.file-tree-item .fpreview.disabled{opacity:.3;cursor:not-allowed}

/* 下载提示 */
.download-prompt{
  display:flex;flex-direction:column;align-items:center;justify-content:center;
  height:100%;gap:20px;color:#94a3b8;
}
.download-prompt .icon{font-size:64px;opacity:.5}
.download-prompt p{font-size:15px}

/* 加载动画 */
.spinner{
  width:40px;height:40px;border:3px solid #334155;border-top-color:#8b5cf6;
  border-radius:50%;animation:spin 1s linear infinite;
}
@keyframes spin{to{transform:rotate(360deg)}}

/* 内部文件预览弹窗 */
.inner-preview-modal{
  position:fixed;inset:0;background:rgba(0,0,0,0.8);z-index:200;
  display:none;align-items:center;justify-content:center;
}
.inner-preview-modal.active{display:flex}
.inner-preview-content{
  width:85%;height:85%;background:#1e293b;border:1px solid #334155;
  border-radius:16px;overflow:hidden;display:flex;flex-direction:column;
}
.inner-preview-header{
  height:44px;display:flex;align-items:center;padding:0 16px;
  border-bottom:1px solid #334155;gap:12px;
}
.inner-preview-header .title{flex:1;font-size:13px;color:#e2e8f0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.inner-preview-header .close-btn{
  width:28px;height:28px;border:none;background:transparent;color:#94a3b8;
  cursor:pointer;font-size:18px;border-radius:6px;
}
.inner-preview-header .close-btn:hover{background:#334155;color:#e2e8f0}
.inner-preview-body{flex:1;overflow:auto;padding:16px}
.inner-preview-body pre{
  font-family:'JetBrains Mono','Fira Code',Consolas,monospace;font-size:13px;
  line-height:1.7;white-space:pre-wrap;word-wrap:break-word;color:#e2e8f0;
}
.inner-preview-body img{max-width:100%;max-height:100%;object-fit:contain;border-radius:8px}
</style>
</head>
<body>

<!-- 顶部工具栏 -->
<div class="preview-toolbar">
  <div class="file-info">
    <?php
    $typeColors = [
        'pdf'=>'#ef4444','office'=>'#3b82f6','text'=>'#22c55e','image'=>'#f59e0b',
        'audio'=>'#8b5cf6','video'=>'#ec4899','archive'=>'#f97316','download'=>'#6b7280',
    ];
    $typeIcons = [
        'pdf'=>'📄','office'=>'📘','text'=>'📝','image'=>'🖼️',
        'audio'=>'🎵','video'=>'🎬','archive'=>'📦','download'=>'⬇️',
    ];
    $color = $typeColors[$previewType] ?? '#6b7280';
    ?>
    <div class="file-icon" style="background:linear-gradient(135deg,<?php echo $color; ?>,<?php echo $color; ?>88);">
      <?php echo $typeIcons[$previewType] ?? '📄'; ?>
    </div>
    <div>
      <div class="file-name"><?php echo htmlspecialchars($resource['title']); ?></div>
      <div class="file-meta">
        <?php echo strtoupper($suffix); ?> ·
        <?php echo $this->formatSize ?? number_format($resource['file_size']/1024/1024, 2) . ' MB'; ?>
        <?php if (!empty($resource['category_name'])): ?>
          · <?php echo htmlspecialchars($resource['category_name']); ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="toolbar-actions">
    <a href="javascript:history.back()" class="btn">← 返回</a>
    <?php if ($previewType !== 'archive'): ?>
    <a href="<?php echo htmlspecialchars($absUrl); ?>" download class="btn btn-primary">⬇ 下载</a>
    <?php endif; ?>
  </div>
</div>

<!-- 预览容器 -->
<div class="preview-container" id="previewContainer">

<?php if ($previewType === 'pdf'): ?>
  <!-- PDF 预览 -->
  <div class="pdf-preview">
    <iframe src="https://mozilla.github.io/pdf.js/web/viewer.html?file=<?php echo urlencode($absUrl); ?>" allowfullscreen></iframe>
  </div>

<?php elseif ($previewType === 'office'): ?>
  <!-- Office 文档预览 -->
  <div class="office-preview" id="officePreview">
    <div class="spinner"></div>
    <div class="loading">正在加载 Office 文档预览...</div>
    <iframe src="https://view.officeapps.live.com/op/embed.aspx?src=<?php echo urlencode($absUrl); ?>"
            style="display:none" onload="this.style.display='block';document.getElementById('officeLoader').style.display='none'"
            id="officeFrame"></iframe>
    <div id="officeLoader" style="text-align:center;">
      <div class="spinner"></div>
      <p style="color:#94a3b8;margin-top:12px;font-size:13px;">如长时间未加载，可能是文档过大或网络问题</p>
    </div>
  </div>

<?php elseif ($previewType === 'text'): ?>
  <!-- 文本/代码预览 -->
  <div class="code-preview" id="codePreview">
    <div style="text-align:center;padding:60px;"><div class="spinner" style="margin:0 auto"></div><p style="color:#94a3b8;margin-top:12px">正在加载文件内容...</p></div>
  </div>

<?php elseif ($previewType === 'image'): ?>
  <!-- 图片预览 -->
  <div class="image-preview">
    <img src="<?php echo htmlspecialchars($absUrl); ?>" alt="<?php echo htmlspecialchars($resource['title']); ?>"
         onclick="this.classList.toggle('zoomed')" loading="lazy">
  </div>

<?php elseif ($previewType === 'video'): ?>
  <!-- 视频预览 -->
  <video controls autoplay style="max-height:calc(100vh - 120px);margin:40px auto;display:block;max-width:90%;border-radius:12px;">
    <source src="<?php echo htmlspecialchars($absUrl); ?>">
    您的浏览器不支持视频播放
  </video>

<?php elseif ($previewType === 'audio'): ?>
  <!-- 音频预览 -->
  <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100%;gap:24px;">
    <div style="font-size:80px;">🎵</div>
    <div style="font-size:18px;font-weight:600;"><?php echo htmlspecialchars($resource['title']); ?></div>
    <audio controls autoplay style="width:80%;max-width:500px;">
      <source src="<?php echo htmlspecialchars($absUrl); ?>">
    </audio>
  </div>

<?php elseif ($previewType === 'archive'): ?>
  <!-- 压缩包预览 -->
  <div class="archive-preview" id="archivePreview">
    <div style="text-align:center;padding:60px;"><div class="spinner" style="margin:0 auto"></div><p style="color:#94a3b8;margin-top:12px">正在解析压缩包...</p></div>
  </div>

<?php else: ?>
  <!-- 不支持预览 - 直接下载 -->
  <div class="download-prompt">
    <div class="icon">⬇️</div>
    <p>此文件类型不支持在线预览</p>
    <a href="<?php echo htmlspecialchars($absUrl); ?>" download class="btn btn-primary" style="height:44px;padding:0 28px;font-size:15px;border-radius:10px;text-decoration:none;">下载文件</a>
  </div>

<?php endif; ?>
</div>

<!-- 内部文件预览弹窗（压缩包内文件） -->
<div class="inner-preview-modal" id="innerPreviewModal">
  <div class="inner-preview-content">
    <div class="inner-preview-header">
      <span class="title" id="innerPreviewTitle">预览</span>
      <button class="close-btn" onclick="closeInnerPreview()">&times;</button>
    </div>
    <div class="inner-preview-body" id="innerPreviewBody"></div>
  </div>
</div>

<script>
const API_BASE = '/api.php';
const RESOURCE_ID = <?php echo $resourceId; ?>;
const FILE_URL = '<?php echo addslashes($absUrl); ?>';
const SUFFIX = '<?php echo addslashes($suffix); ?>';

// ======== 文本/代码文件预览 ========
<?php if ($previewType === 'text'): ?>
(function() {
  const container = document.getElementById('codePreview');
  fetch(API_BASE + '/preview/textContent?resource_id=' + RESOURCE_ID)
    .then(r => r.json())
    .then(res => {
      if (res.code !== 0) { container.innerHTML = '<div class="download-prompt"><div class="icon">❌</div><p>' + res.message + '</p></div>'; return; }
      const d = res.data;
      const langMap = {
        'js':'javascript','ts':'typescript','py':'python','rb':'ruby','cs':'csharp',
        'cpp':'cpp','c':'c','h':'c','hpp':'cpp','java':'java','go':'go','rs':'rust',
        'swift':'swift','kt':'kotlin','php':'php','sh':'bash','bash':'bash',
        'bat':'batch','ps1':'powershell','sql':'sql','html':'html','htm':'html',
        'css':'css','json':'json','xml':'xml','yaml':'yaml','yml':'yaml',
        'md':'markdown','txt':'text','csv':'csv','log':'text','vue':'html',
        'jsx':'jsx','tsx':'tsx','conf':'ini','ini':'ini','toml':'toml',
        'lua':'lua','perl':'perl','pl':'perl','r':'r',
      };
      const lang = langMap[d.ext] || d.ext || 'text';
      const escaped = d.content.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
      container.innerHTML = '<div class="code-header"><span class="lang-badge">' + lang + '</span><span style="font-size:12px;color:#94a3b8">' + formatSize(d.size) + '</span></div><div class="code-with-header"><pre>' + escaped + '</pre></div>';
    })
    .catch(err => { container.innerHTML = '<div class="download-prompt"><div class="icon">❌</div><p>加载失败: ' + err.message + '</p></div>'; });
})();
<?php endif; ?>

// ======== 压缩包预览 ========
<?php if ($previewType === 'archive'): ?>
(function() {
  const container = document.getElementById('archivePreview');
  fetch(API_BASE + '/preview/archive?resource_id=' + RESOURCE_ID)
    .then(r => r.json())
    .then(res => {
      if (res.code !== 0) { container.innerHTML = '<div class="download-prompt"><div class="icon">❌</div><p>' + res.message + '</p></div>'; return; }
      const d = res.data;
      let html = '<div class="archive-header">';
      html += '<div class="stat"><div class="num">' + d.total_files + '</div><div class="label">文件数</div></div>';
      html += '<div class="stat"><div class="num">' + formatSize(d.total_size) + '</div><div class="label">总大小</div></div>';
      html += '<div class="stat"><div class="num">.' + d.suffix + '</div><div class="label">格式</div></div>';
      html += '</div>';
      html += '<div class="file-tree">';
      d.files.forEach(function(f) {
        const icon = getFileIcon(f.ext);
        const canPreview = f.previewable && (<?php echo $suffix === 'zip' ? 'true' : 'false'; ?>);
        html += '<div class="file-tree-item">';
        html += '<span class="fi">' + icon + '</span>';
        html += '<span class="fname" title="' + escHtml(f.name) + '">' + escHtml(f.name) + '</span>';
        html += '<span class="fsize">' + formatSize(f.size) + '</span>';
        if (canPreview) {
          html += '<button class="fpreview" onclick="previewInnerFile(\'' + escHtml(f.name).replace(/'/g,"\\'") + '\')">预览</button>';
        }
        html += '</div>';
      });
      html += '</div>';
      container.innerHTML = html;
    })
    .catch(err => { container.innerHTML = '<div class="download-prompt"><div class="icon">❌</div><p>解析失败: ' + err.message + '</p></div>'; });
})();
<?php endif; ?>

// ======== 内部文件预览 ========
function previewInnerFile(path) {
  const modal = document.getElementById('innerPreviewModal');
  const body  = document.getElementById('innerPreviewBody');
  const title = document.getElementById('innerPreviewTitle');
  title.textContent = path;
  body.innerHTML = '<div style="text-align:center;padding:40px"><div class="spinner" style="margin:0 auto"></div></div>';
  modal.classList.add('active');

  fetch(API_BASE + '/preview/archiveFile?resource_id=' + RESOURCE_ID + '&path=' + encodeURIComponent(path))
    .then(r => r.json())
    .then(res => {
      if (res.code !== 0) { body.innerHTML = '<p style="color:#ef4444;text-align:center;padding:40px;">' + res.message + '</p>'; return; }
      const d = res.data;
      if (d.type === 'image') {
        body.innerHTML = '<div style="text-align:center"><img src="data:' + d.mime + ';base64,' + d.base64 + '" style="max-width:100%;max-height:100%;border-radius:8px"></div>';
      } else {
        const escaped = d.content.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
        body.innerHTML = '<pre>' + escaped + '</pre>';
      }
    })
    .catch(err => { body.innerHTML = '<p style="color:#ef4444;text-align:center;padding:40px;">加载失败: ' + err.message + '</p>'; });
}

function closeInnerPreview() {
  document.getElementById('innerPreviewModal').classList.remove('active');
}

// ESC 关闭弹窗
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') closeInnerPreview();
});

// ======== 工具函数 ========
function formatSize(bytes) {
  if (!bytes || bytes === 0) return '0 B';
  var units = ['B','KB','MB','GB'];
  var i = 0;
  var size = bytes;
  while (size >= 1024 && i < units.length - 1) { size /= 1024; i++; }
  return (Math.round(size * 100) / 100) + ' ' + units[i];
}

function escHtml(s) {
  var div = document.createElement('div');
  div.appendChild(document.createTextNode(s));
  return div.innerHTML;
}

function getFileIcon(ext) {
  var icons = {
    'txt':'📄','md':'📝','csv':'📊','json':'📋','xml':'📋','yaml':'📋','yml':'📋',
    'html':'🌐','htm':'🌐','css':'🎨','js':'⚡','ts':'⚡','vue':'💚','jsx':'⚛️','tsx':'⚛️',
    'php':'🐘','py':'🐍','java':'☕','c':'🔧','cpp':'🔧','h':'🔧','cs':'🔷',
    'go':'🔵','rs':'🦀','rb':'💎','swift':'🍎','kt':'🟣',
    'sh':'💻','bash':'💻','bat':'💻','ps1':'💻',
    'sql':'🗄️','r':'📊','lua':'🌙','perl':'🐪',
    'jpg':'🖼️','jpeg':'🖼️','png':'🖼️','gif':'🖼️','webp':'🖼️','bmp':'🖼️','svg':'🖼️',
    'mp3':'🎵','wav':'🎵','ogg':'🎵','flac':'🎵','aac':'🎵',
    'mp4':'🎬','avi':'🎬','mov':'🎬','wmv':'🎬','flv':'🎬','mkv':'🎬',
    'pdf':'📕','doc':'📘','docx':'📘','xls':'📗','xlsx':'📗','ppt':'📙','pptx':'📙',
    'zip':'📦','rar':'📦','7z':'📦','tar':'📦','gz':'📦',
  };
  return icons[ext] || '📄';
}
</script>
</body>
</html>
