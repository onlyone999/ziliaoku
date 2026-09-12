<?php
/**
 * URL助手 - 将相对路径转为完整URL
 * PHP 7.4兼容
 */

class UrlHelper
{
    /**
     * 获取应用基础URL
     */
    public static function getBaseUrl(): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $scheme . '://' . $host;
    }

    /**
     * 将相对URL转为完整URL
     * 如果已经是完整URL则直接返回
     */
    public static function fullUrl(string $url): string
    {
        if (empty($url)) {
            return '';
        }
        // 已经是完整URL
        if (preg_match('#^https?://#i', $url)) {
            return $url;
        }
        return self::getBaseUrl() . ($url[0] === '/' ? '' : '/') . $url;
    }

    /**
     * 批量转换资源记录中的图片URL字段
     */
    public static function fixResourceUrls(array $resource): array
    {
        $urlFields = ['cover_url', 'file_url'];
        foreach ($urlFields as $field) {
            if (!empty($resource[$field])) {
                $resource[$field] = self::fullUrl($resource[$field]);
            }
        }
        return $resource;
    }

    /**
     * 批量转换资源列表中的图片URL
     */
    public static function fixResourceListUrls(array $list): array
    {
        return array_map([self::class, 'fixResourceUrls'], $list);
    }

    /**
     * 转换banner记录中的image_url
     */
    public static function fixBannerUrls(array $banner): array
    {
        if (!empty($banner['image_url'])) {
            $banner['image_url'] = self::fullUrl($banner['image_url']);
        }
        return $banner;
    }

    /**
     * 批量转换banner列表
     */
    public static function fixBannerListUrls(array $list): array
    {
        return array_map([self::class, 'fixBannerUrls'], $list);
    }

    /**
     * 转换resource_files中的file_url
     */
    public static function fixFileUrls(array $file): array
    {
        if (!empty($file['file_url'])) {
            $file['file_url'] = self::fullUrl($file['file_url']);
        }
        return $file;
    }

    /**
     * 批量转换文件列表
     */
    public static function fixFileListUrls(array $list): array
    {
        return array_map([self::class, 'fixFileUrls'], $list);
    }
}
