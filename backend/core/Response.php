<?php
/**
 * JSON响应助手
 * PHP 7.4兼容
 */

class Response
{
    /**
     * 发送成功JSON响应
     *
     * @param mixed       $data
     * @param string      $message
     * @param int         $code     业务码（默认0 = 成功）
     * @param int         $httpCode HTTP状态码
     */
    public static function success($data = null, string $message = 'success', int $code = 0, int $httpCode = 200): void
    {
        self::json([
            'code'    => $code,
            'message' => $message,
            'data'    => $data,
        ], $httpCode);
    }

    /**
     * 发送错误JSON响应
     *
     * @param string $message
     * @param int    $code     业务错误码
     * @param int    $httpCode HTTP状态码
     * @param mixed  $data     可选的错误详情
     */
    public static function error(string $message = 'error', int $code = 1, int $httpCode = 400, $data = null): void
    {
        self::json([
            'code'    => $code,
            'message' => $message,
            'data'    => $data,
        ], $httpCode);
    }

    /**
     * 发送分页JSON响应
     *
     * @param array $data
     * @param int   $total
     * @param int   $page
     * @param int   $pageSize
     */
    public static function paginate(array $data, int $total, int $page, int $pageSize): void
    {
        $lastPage = (int) ceil($total / $pageSize);
        if ($lastPage < 1) {
            $lastPage = 1;
        }

        self::json([
            'code'    => 0,
            'message' => 'success',
            'data'    => [
                'list'         => $data,
                'total'        => $total,
                'page'         => $page,
                'page_size'    => $pageSize,
                'last_page'    => $lastPage,
                'has_more'     => $page < $lastPage,
            ],
        ]);
    }

    /**
     * 发送401未授权响应
     */
    public static function unauthorized(string $message = 'Unauthorized'): void
    {
        self::error($message, 401, 401);
    }

    /**
     * 发送403禁止访问响应
     */
    public static function forbidden(string $message = 'Forbidden'): void
    {
        self::error($message, 403, 403);
    }

    /**
     * 发送404未找到响应
     */
    public static function notFound(string $message = 'Not Found'): void
    {
        self::error($message, 404, 404);
    }

    /**
     * 发送500服务器内部错误响应
     */
    public static function serverError(string $message = 'Internal Server Error'): void
    {
        self::error($message, 500, 500);
    }

    /**
     * 发送原始JSON响应
     *
     * @param array $payload
     * @param int   $httpCode
     */
    public static function json(array $payload, int $httpCode = 200): void
    {
        http_response_code($httpCode);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * 发送纯文本响应
     */
    public static function text(string $text, int $httpCode = 200): void
    {
        http_response_code($httpCode);
        header('Content-Type: text/plain; charset=utf-8');
        echo $text;
        exit;
    }
}
