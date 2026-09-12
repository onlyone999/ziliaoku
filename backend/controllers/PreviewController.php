<?php
/**
 * 文件预览控制器
 * 支持 Office/PDF/TXT/代码/图片/音视频/压缩包 预览
 * PHP 7.4 兼容
 */

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Response.php';

class PreviewController
{
    private Database $db;

    /** Office 扩展名集合 */
    private static array $officeExts = ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'];

    /** 纯文本/代码扩展名 */
    private static array $textExts = [
        'txt', 'md', 'csv', 'log', 'ini', 'conf',
        'json', 'xml', 'yaml', 'yml', 'toml',
        'html', 'htm', 'css', 'js', 'ts', 'jsx', 'tsx', 'vue',
        'php', 'py', 'java', 'c', 'cpp', 'h', 'hpp', 'cs',
        'go', 'rs', 'rb', 'swift', 'kt', 'sh', 'bash', 'bat', 'ps1',
        'sql', 'r', 'lua', 'perl', 'pl',
    ];

    /** 图片扩展名 */
    private static array $imageExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg', 'ico'];

    /** 音频扩展名 */
    private static array $audioExts = ['mp3', 'wav', 'ogg', 'flac', 'aac', 'wma', 'm4a'];

    /** 视频扩展名 */
    private static array $videoExts = ['mp4', 'avi', 'mov', 'wmv', 'flv', 'mkv', 'webm'];

    /** 压缩包扩展名 */
    private static array $archiveExts = ['zip', 'rar', '7z', 'tar', 'gz', 'tar.gz'];

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // ============================================================
    //  GET /api/preview/info?resource_id=123
    //  获取预览信息（文件类型、预览方案、预览URL）
    // ============================================================
    public function info(): void
    {
        $resourceId = (int) ($GLOBALS['REQUEST_DATA']['resource_id'] ?? 0);
        if ($resourceId <= 0) {
            Response::error('缺少资源ID');
        }

        $resource = $this->db->fetch(
            'SELECT id, title, file_url, file_size, file_type, file_suffix FROM resources WHERE id = :id LIMIT 1',
            [':id' => $resourceId]
        );
        if (empty($resource)) {
            Response::notFound('资源不存在');
        }

        $suffix = strtolower($resource['file_suffix'] ?? '');
        if (empty($suffix)) {
            $suffix = strtolower(pathinfo($resource['file_url'], PATHINFO_EXTENSION));
        }

        $previewType = $this->detectPreviewType($suffix);
        $previewUrl  = $this->buildPreviewUrl($resource, $suffix, $previewType);

        // 查询附加文件
        $extraFiles = $this->db->fetchAll(
            'SELECT id, file_name, file_url, file_size FROM resource_files WHERE resource_id = :id ORDER BY id',
            [':id' => $resourceId]
        );

        Response::success([
            'resource'    => $resource,
            'suffix'      => $suffix,
            'preview_type'=> $previewType,
            'preview_url' => $previewUrl,
            'extra_files' => $extraFiles,
        ]);
    }

    // ============================================================
    //  GET /api/preview/office?url=xxx&ext=docx
    //  返回 Microsoft Office Online Viewer 预览地址
    // ============================================================
    public function office(): void
    {
        $url = $GLOBALS['REQUEST_DATA']['url'] ?? '';
        $ext = strtolower($GLOBALS['REQUEST_DATA']['ext'] ?? '');

        if (empty($url)) {
            Response::error('缺少文件URL');
        }

        // 转为绝对URL
        $absoluteUrl = $this->toAbsoluteUrl($url);
        $encodedUrl  = urlencode($absoluteUrl);

        $viewerUrl = "https://view.officeapps.live.com/op/embed.aspx?src={$encodedUrl}";

        Response::success([
            'viewer_url'  => $viewerUrl,
            'original_url'=> $absoluteUrl,
            'extension'   => $ext,
        ]);
    }

    // ============================================================
    //  GET /api/preview/archive?resource_id=123
    //  列出压缩包内文件（ZIP 用 PHP ZipArchive；RAR 需 unrar 命令行）
    // ============================================================
    public function archive(): void
    {
        $resourceId = (int) ($GLOBALS['REQUEST_DATA']['resource_id'] ?? 0);
        if ($resourceId <= 0) {
            Response::error('缺少资源ID');
        }

        $resource = $this->db->fetch(
            'SELECT id, title, file_url, file_suffix FROM resources WHERE id = :id LIMIT 1',
            [':id' => $resourceId]
        );
        if (empty($resource)) {
            Response::notFound('资源不存在');
        }

        $filePath = $this->toLocalPath($resource['file_url']);
        if (!file_exists($filePath)) {
            Response::notFound('文件不存在');
        }

        $suffix = strtolower($resource['file_suffix'] ?? pathinfo($filePath, PATHINFO_EXTENSION));

        $files = [];
        if ($suffix === 'zip') {
            $files = $this->listZipContents($filePath);
        } elseif ($suffix === 'rar') {
            $files = $this->listRarContents($filePath);
        } elseif ($suffix === '7z') {
            $files = $this->list7zContents($filePath);
        } else {
            Response::error('不支持的压缩格式: .' . $suffix);
        }

        if ($files === null) {
            Response::error('解压失败或格式不支持');
        }

        // 统计
        $totalFiles   = count($files);
        $totalSize    = array_sum(array_column($files, 'size'));

        Response::success([
            'archive_name'=> $resource['title'],
            'suffix'      => $suffix,
            'total_files' => $totalFiles,
            'total_size'  => $totalSize,
            'files'       => $files,
        ]);
    }

    // ============================================================
    //  GET /api/preview/archive-file?resource_id=123&path=xxx
    //  获取压缩包内单个文件内容（仅限文本/图片，ZIP格式）
    // ============================================================
    public function archiveFile(): void
    {
        $resourceId = (int) ($GLOBALS['REQUEST_DATA']['resource_id'] ?? 0);
        $innerPath  = $GLOBALS['REQUEST_DATA']['path'] ?? '';

        if ($resourceId <= 0 || empty($innerPath)) {
            Response::error('缺少参数');
        }

        $resource = $this->db->fetch(
            'SELECT id, file_url, file_suffix FROM resources WHERE id = :id LIMIT 1',
            [':id' => $resourceId]
        );
        if (empty($resource)) {
            Response::notFound('资源不存在');
        }

        $filePath = $this->toLocalPath($resource['file_url']);
        $suffix   = strtolower($resource['file_suffix'] ?? pathinfo($filePath, PATHINFO_EXTENSION));

        if ($suffix !== 'zip') {
            Response::error('仅支持ZIP格式内部文件预览');
        }

        if (!class_exists('ZipArchive')) {
            Response::error('服务器未安装ZipArchive扩展');
        }

        $zip = new \ZipArchive();
        if ($zip->open($filePath) !== true) {
            Response::error('无法打开压缩包');
        }

        $entryIndex = $zip->locateName($innerPath);
        if ($entryIndex === false) {
            $zip->close();
            Response::notFound('文件不存在于压缩包中');
        }

        $stat = $zip->statIndex($entryIndex);
        $maxPreviewSize = 5 * 1024 * 1024; // 5MB
        if ($stat['size'] > $maxPreviewSize) {
            $zip->close();
            Response::error('文件过大，无法预览（限5MB）');
        }

        $content = $zip->getFromIndex($entryIndex);
        $zip->close();

        if ($content === false) {
            Response::error('读取文件失败');
        }

        $ext = strtolower(pathinfo($innerPath, PATHINFO_EXTENSION));

        // 图片类：base64 返回
        if (in_array($ext, self::$imageExts, true)) {
            $mime = $this->extToMime($ext);
            Response::success([
                'type'    => 'image',
                'mime'    => $mime,
                'base64'  => base64_encode($content),
                'size'    => $stat['size'],
            ]);
        }

        // 文本类：直接返回内容
        Response::success([
            'type'    => 'text',
            'ext'     => $ext,
            'content' => $content,
            'size'    => $stat['size'],
        ]);
    }

    // ============================================================
    //  GET /api/preview/text-content?resource_id=123
    //  读取服务器上的文本/代码文件内容（用于语法高亮预览）
    // ============================================================
    public function textContent(): void
    {
        $resourceId = (int) ($GLOBALS['REQUEST_DATA']['resource_id'] ?? 0);
        if ($resourceId <= 0) {
            Response::error('缺少资源ID');
        }

        $resource = $this->db->fetch(
            'SELECT id, title, file_url, file_size, file_suffix FROM resources WHERE id = :id LIMIT 1',
            [':id' => $resourceId]
        );
        if (empty($resource)) {
            Response::notFound('资源不存在');
        }

        $filePath = $this->toLocalPath($resource['file_url']);
        if (!file_exists($filePath)) {
            Response::notFound('文件不存在');
        }

        $maxSize = 2 * 1024 * 1024; // 2MB
        if (filesize($filePath) > $maxSize) {
            Response::error('文件过大，无法预览（限2MB）');
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            Response::error('读取文件失败');
        }

        $suffix = strtolower($resource['file_suffix'] ?? pathinfo($filePath, PATHINFO_EXTENSION));

        Response::success([
            'resource_id' => $resourceId,
            'title'       => $resource['title'],
            'ext'         => $suffix,
            'content'     => $content,
            'size'        => $resource['file_size'],
        ]);
    }

    // ============================================================
    //  内部辅助方法
    // ============================================================

    /**
     * 根据扩展名判断预览类型
     */
    private function detectPreviewType(string $suffix): string
    {
        if ($suffix === 'pdf')                     return 'pdf';
        if (in_array($suffix, self::$officeExts))  return 'office';
        if (in_array($suffix, self::$textExts))    return 'text';
        if (in_array($suffix, self::$imageExts))   return 'image';
        if (in_array($suffix, self::$audioExts))   return 'audio';
        if (in_array($suffix, self::$videoExts))   return 'video';
        if (in_array($suffix, self::$archiveExts)) return 'archive';
        return 'download';
    }

    /**
     * 构建预览URL
     */
    private function buildPreviewUrl(array $resource, string $suffix, string $previewType): string
    {
        $fileUrl = $resource['file_url'];

        switch ($previewType) {
            case 'pdf':
                // 使用 PDF.js CDN 查看器
                $absUrl = $this->toAbsoluteUrl($fileUrl);
                return 'https://mozilla.github.io/pdf.js/web/viewer.html?file=' . urlencode($absUrl);

            case 'office':
                $absUrl = $this->toAbsoluteUrl($fileUrl);
                return 'https://view.officeapps.live.com/op/embed.aspx?src=' . urlencode($absUrl);

            case 'image':
            case 'audio':
            case 'video':
                return $fileUrl; // 直接URL，前端用 HTML5 标签

            case 'text':
            case 'archive':
                return ''; // 前端调用专用API

            default:
                return $fileUrl;
        }
    }

    /**
     * 将相对URL转为绝对URL
     */
    private function toAbsoluteUrl(string $url): string
    {
        if (strpos($url, 'http') === 0) return $url;

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost:9901';
        return $scheme . '://' . $host . '/' . ltrim($url, '/');
    }

    /**
     * 将上传URL转为本地文件路径
     */
    private function toLocalPath(string $url): string
    {
        $config = require __DIR__ . '/../config/app.php';
        $uploadBase = rtrim($config['upload_path'], '/\\');

        // 去掉URL前缀 /uploads/
        $relative = preg_replace('#^/?uploads/#', '', ltrim($url, '/'));
        return $uploadBase . '/' . $relative;
    }

    /**
     * 列出 ZIP 文件内容
     */
    private function listZipContents(string $filePath): ?array
    {
        if (!class_exists('ZipArchive')) return null;

        $zip = new \ZipArchive();
        if ($zip->open($filePath) !== true) return null;

        $files = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            $name = $stat['name'];

            // 跳过目录条目
            if (substr($name, -1) === '/') continue;

            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

            $files[] = [
                'name'      => $name,
                'size'      => $stat['size'],
                'compressed'=> $stat['comp_size'],
                'ext'       => $ext,
                'type'      => $this->detectPreviewType($ext),
                'previewable'=> in_array($ext, array_merge(self::$textExts, self::$imageExts), true),
            ];
        }

        $zip->close();
        return $files;
    }

    /**
     * 列出 RAR 文件内容（需要 rar 扩展或 unrar 命令行）
     */
    private function listRarContents(string $filePath): ?array
    {
        // 尝试 PHP rar 扩展
        if (class_exists('RarArchive')) {
            $rar = \RarArchive::open($filePath);
            if (!$rar) return null;

            $entries = $rar->getEntries();
            $files = [];
            foreach ($entries as $entry) {
                if ($entry->isDirectory()) continue;

                $name = $entry->getName();
                $ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));

                $files[] = [
                    'name'      => $name,
                    'size'      => $entry->getUnpackedSize(),
                    'compressed'=> $entry->getPackedSize(),
                    'ext'       => $ext,
                    'type'      => $this->detectPreviewType($ext),
                    'previewable'=> false,
                ];
            }
            $rar->close();
            return $files;
        }

        // 尝试 unrar 命令行
        $output = [];
        $cmd = 'unrar l "' . $filePath . '" 2>&1';
        exec($cmd, $output, $returnCode);
        if ($returnCode !== 0) return null;

        $files = [];
        $inList = false;
        foreach ($output as $line) {
            if (strpos($line, '--------') !== false) {
                $inList = !$inList;
                continue;
            }
            if (!$inList || empty(trim($line))) continue;

            // unrar l 输出格式：大小 日期 时间  文件名
            if (preg_match('/^\s*(\d+)\s+\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}\s+(.+)$/', $line, $m)) {
                $name = trim($m[2]);
                $ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));

                $files[] = [
                    'name'      => $name,
                    'size'      => (int) $m[1],
                    'compressed'=> 0,
                    'ext'       => $ext,
                    'type'      => $this->detectPreviewType($ext),
                    'previewable'=> false,
                ];
            }
        }
        return $files;
    }

    /**
     * 列出 7z 文件内容
     */
    private function list7zContents(string $filePath): ?array
    {
        $output = [];
        $cmd = '7z l "' . $filePath . '" 2>&1';
        exec($cmd, $output, $returnCode);
        if ($returnCode !== 0) return null;

        $files = [];
        $inList = false;
        foreach ($output as $line) {
            if (strpos($line, '--------') !== false) {
                $inList = !$inList;
                continue;
            }
            if (!$inList || empty(trim($line))) continue;

            // 7z l 输出格式：日期 时间 属性 大小 压缩后大小 文件名
            if (preg_match('/^\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2}\s+[\w\.]+\s+(\d+)\s+\d*\s+(.+)$/', $line, $m)) {
                $name = trim($m[2]);
                $ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));

                $files[] = [
                    'name'      => $name,
                    'size'      => (int) $m[1],
                    'compressed'=> 0,
                    'ext'       => $ext,
                    'type'      => $this->detectPreviewType($ext),
                    'previewable'=> false,
                ];
            }
        }
        return $files;
    }

    /**
     * 扩展名转 MIME
     */
    private function extToMime(string $ext): string
    {
        $map = [
            'jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png',
            'gif'=>'image/gif','webp'=>'image/webp','bmp'=>'image/bmp',
            'svg'=>'image/svg+xml','ico'=>'image/x-icon',
        ];
        return $map[$ext] ?? 'application/octet-stream';
    }
}
