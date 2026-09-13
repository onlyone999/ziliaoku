<?php
/**
 * 后台公告文件上传接口
 * POST /admin/upload_api.php
 * 返回 JSON: { code:0, data:{ url, filename, size } }
 */
session_start();
header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['admin_id'])) {
    echo json_encode(['code' => 401, 'message' => '未登录']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['file'])) {
    echo json_encode(['code' => 400, 'message' => '请选择文件']);
    exit;
}

$file = $_FILES['file'];
$type = $_POST['type'] ?? 'image';

// 配置
$config = require __DIR__ . '/../config/app.php';
$uploadBase = rtrim($config['upload_path'], '/');

// 根据类型确定目录和允许扩展名
if ($type === 'image') {
    $subDir = 'announcements/images';
    $allowedExts = ['jpg','jpeg','png','gif','webp','bmp'];
    $maxSize = 5 * 1024 * 1024; // 5MB
} else {
    $subDir = 'announcements/files';
    $allowedExts = ['pdf','doc','docx','xls','xlsx','ppt','pptx','zip','rar','7z','txt','csv'];
    $maxSize = 20 * 1024 * 1024; // 20MB
}

$uploadDir = $uploadBase . '/' . $subDir;
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// 检查上传错误
if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['code' => 400, 'message' => '上传出错: ' . $file['error']]);
    exit;
}

// 检查大小
if ($file['size'] > $maxSize) {
    echo json_encode(['code' => 400, 'message' => '文件过大，最大 ' . round($maxSize / 1024 / 1024) . 'MB']);
    exit;
}

// 检查扩展名
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($ext, $allowedExts)) {
    echo json_encode(['code' => 400, 'message' => '不允许的文件类型: .' . $ext]);
    exit;
}

// 图片校验MIME
if ($type === 'image') {
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    if (!preg_match('#^image/#', $mime)) {
        echo json_encode(['code' => 400, 'message' => '文件不是图片类型']);
        exit;
    }
}

// 生成文件名
$filename = date('YmdHis') . '_' . mt_rand(1000, 9999) . '.' . $ext;
$destPath = $uploadDir . '/' . $filename;

if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    echo json_encode(['code' => 500, 'message' => '保存文件失败']);
    exit;
}

// 返回URL（相对路径）
$url = '/uploads/' . $subDir . '/' . $filename;

echo json_encode([
    'code' => 0,
    'message' => '上传成功',
    'data' => [
        'url'      => $url,
        'filename' => $file['name'],
        'size'     => $file['size'],
    ]
]);
