<?php
/**
 * 文件上传处理器
 * PHP 7.4兼容
 */

class Upload
{
    /** @var array 应用配置 */
    private array $config;

    /** @var string 基础上传目录 */
    private string $uploadDir;

    public function __construct()
    {
        $this->config    = require __DIR__ . '/../config/app.php';
        $this->uploadDir = $this->config['upload_path'];
        $this->ensureDirectory($this->uploadDir . 'resources');
    }

    /**
     * 处理文件上传
     *
     * @param array $file         $_FILES['xxx'] 条目
     * @param array $allowedTypes 例如 ['image'] 或 ['image','document']
     * @return array ['url' => ..., 'filename' => ..., 'size' => ..., 'type' => ...]
     */
    public function handle(array $file, array $allowedTypes = ['image']): array
    {
        // 检查上传错误
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException($this->getUploadErrorMessage($file['error']));
        }

        // 验证文件大小
        $this->validateFileSize($file['size']);

        // 获取扩展名
        $originalName = $file['name'];
        $extension    = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        // 验证文件类型
        $allowedExtensions = $this->getAllowedExtensions($allowedTypes);
        if (!in_array($extension, $allowedExtensions, true)) {
            throw new RuntimeException(
                '不允许的文件类型: .' . $extension . '。允许: ' . implode(', ', $allowedExtensions)
            );
        }

        // 验证图片的MIME类型
        if (in_array('image', $allowedTypes, true)) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime  = $finfo->file($file['tmp_name']);
            $allowedMimes = [
                'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/bmp',
            ];
            if (!in_array($mime, $allowedMimes, true)) {
                throw new RuntimeException('文件MIME类型不合法: ' . $mime);
            }
        }

        // 生成文件名和保存路径
        $newFilename = $this->generateFileName($extension);
        $subDir      = date('Ymd');
        $destDir     = $this->uploadDir . 'resources/' . $subDir;
        $this->ensureDirectory($destDir);

        $destPath = $destDir . '/' . $newFilename;

        // 移动上传文件
        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            throw new RuntimeException('文件保存失败');
        }

        // 构建URL
        $relativeUrl = 'uploads/resources/' . $subDir . '/' . $newFilename;

        return [
            'url'            => $this->config['upload_url'] . 'resources/' . $subDir . '/' . $newFilename,
            'relative_url'   => $relativeUrl,
            'filename'       => $newFilename,
            'original_name'  => $originalName,
            'size'           => $file['size'],
            'extension'      => $extension,
            'type'           => $this->detectCategory($extension),
        ];
    }

    /**
     * 验证文件大小是否超出配置限制
     */
    public function validateFileSize(int $size): void
    {
        $maxSize = $this->config['max_upload_size'] ?? (50 * 1024 * 1024);
        if ($size > $maxSize) {
            throw new RuntimeException(
                '文件大小超出限制: ' . $this->formatSize($size) . ' > ' . $this->formatSize($maxSize)
            );
        }
        if ($size <= 0) {
            throw new RuntimeException('文件为空');
        }
    }

    /**
     * 生成唯一文件名
     */
    public function generateFileName(string $extension): string
    {
        return date('His') . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
    }

    /**
     * 获取已存储文件的公开URL
     */
    public function getFileUrl(string $relativePath): string
    {
        return rtrim($this->config['upload_url'], '/') . '/' . ltrim($relativePath, '/');
    }

    /**
     * 获取指定分类的合并扩展名列表
     */
    private function getAllowedExtensions(array $categories): array
    {
        $allowed = [];
        $types   = $this->config['allowed_file_types'] ?? [];

        foreach ($categories as $cat) {
            if (isset($types[$cat])) {
                $allowed = array_merge($allowed, $types[$cat]);
            }
        }

        return array_unique($allowed);
    }

    /**
     * 根据扩展名检测文件分类
     */
    private function detectCategory(string $extension): string
    {
        $types = $this->config['allowed_file_types'] ?? [];
        foreach ($types as $category => $extensions) {
            if (in_array($extension, $extensions, true)) {
                return $category;
            }
        }
        return 'other';
    }

    /**
     * 确保目录存在
     */
    private function ensureDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    /**
     * 人类可读的文件大小
     */
    private function formatSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i     = 0;
        $size  = (float) $bytes;

        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }

        return round($size, 2) . ' ' . $units[$i];
    }

    /**
     * 上传错误信息映射
     */
    private function getUploadErrorMessage(int $code): string
    {
        $messages = [
            UPLOAD_ERR_INI_SIZE   => '文件超过服务器上传大小限制',
            UPLOAD_ERR_FORM_SIZE  => '文件超过表单指定大小限制',
            UPLOAD_ERR_PARTIAL    => '文件只有部分被上传',
            UPLOAD_ERR_NO_FILE    => '没有文件被上传',
            UPLOAD_ERR_NO_TMP_DIR => '服务器临时文件夹缺失',
            UPLOAD_ERR_CANT_WRITE => '文件写入失败',
            UPLOAD_ERR_EXTENSION  => 'PHP扩展停止了文件上传',
        ];

        return $messages[$code] ?? '未知上传错误 (code: ' . $code . ')';
    }
}
