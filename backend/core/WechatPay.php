<?php
/**
 * 微信小程序虚拟支付
 * PHP 7.4兼容 - 微信支付v3 API
 */

class WechatPay
{
    /** @var array 配置 */
    private array $config;

    /** @var string 商户ID */
    private string $mchId;

    /** @var string API v3密钥 */
    private string $apiKey;

    /** @var string 应用ID */
    private string $appId;

    /** @var string 回调通知URL */
    private string $notifyUrl;

    /** @var string 证书序列号 */
    private string $serialNumber;

    /** @var string 私钥内容 */
    private string $privateKey;

    public function __construct()
    {
        $this->config   = require __DIR__ . '/../config/app.php';
        $this->appId    = $this->config['wechat_mini_appid'];
        $this->mchId    = $this->config['wechat_mch_id'];
        $this->apiKey   = $this->config['wechat_api_key'];
        $this->notifyUrl = $this->config['wechat_notify_url'];

        $this->serialNumber = getenv('WECHAT_SERIAL_NUMBER') ?: '';
        $this->privateKey   = '';

        $keyPath = __DIR__ . '/../config/certs/apiclient_key.pem';
        if (file_exists($keyPath)) {
            $this->privateKey = file_get_contents($keyPath);
        }
    }

    /**
     * 创建虚拟支付订单（JSAPI）
     *
     * @param string $outTradeNo  商户订单号
     * @param int    $amount      金额（单位：分）
     * @param string $description 订单描述
     * @param string $openid      付款人openid
     * @return array  前端wx.requestVirtualPayment所需的支付参数
     */
    public function createOrder(string $outTradeNo, int $amount, string $description, string $openid): array
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException('支付金额必须大于0');
        }

        $url = 'https://api.mch.weixin.qq.com/v3/pay/transactions/jsapi';

        $body = [
            'appid'        => $this->appId,
            'mchid'        => $this->mchId,
            'description'  => mb_substr($description, 0, 127),
            'out_trade_no' => $outTradeNo,
            'notify_url'   => $this->notifyUrl,
            'amount'       => [
                'total'    => $amount,
                'currency' => 'CNY',
            ],
            'payer' => [
                'openid' => $openid,
            ],
        ];

        $response = $this->httpPostV3($url, $body);
        $result   = json_decode($response, true);

        if (!$result || isset($result['code'])) {
            $errMsg = $result['message'] ?? '创建订单失败';
            throw new RuntimeException('微信支付下单失败: ' . $errMsg);
        }

        $prepayId = $result['prepay_id'] ?? '';
        if (empty($prepayId)) {
            throw new RuntimeException('获取prepay_id失败');
        }

        // 生成小程序支付参数
        return $this->buildPayParams($prepayId);
    }

    /**
     * 根据商户订单号查询订单
     */
    public function queryOrder(string $outTradeNo): array
    {
        $url = sprintf(
            'https://api.mch.weixin.qq.com/v3/pay/transactions/out-trade-no/%s?mchid=%s',
            $outTradeNo,
            $this->mchId
        );

        $response = $this->httpGetV3($url);
        return json_decode($response, true) ?: [];
    }

    /**
     * 处理支付通知回调
     *
     * @param string $body     原始请求体
     * @param array  $headers  请求头
     * @return array  解密后的通知数据
     */
    public function handleNotify(string $body, array $headers = []): array
    {
        $notification = json_decode($body, true);
        if (!$notification) {
            throw new RuntimeException('通知数据格式错误');
        }

        // 验证签名
        $timestamp = $headers['HTTP_WECHATPAY_TIMESTAMP'] ?? $notification['summary'] ?? '';
        $nonce     = $headers['HTTP_WECHATPAY_NONCE'] ?? '';
        $signature = $headers['HTTP_WECHATPAY_SIGNATURE'] ?? '';
        $serial    = $headers['HTTP_WECHATPAY_SERIAL'] ?? '';

        // 解密资源数据
        $resource = $notification['resource'] ?? [];
        $ciphertext = $resource['ciphertext'] ?? '';
        $nonce2     = $resource['nonce'] ?? '';
        $associated = $resource['associated_data'] ?? '';

        if (empty($ciphertext)) {
            throw new RuntimeException('通知密文为空');
        }

        $decrypted = $this->decryptAEAD($ciphertext, $nonce2, $associated);
        $orderData = json_decode($decrypted, true);

        if (!$orderData) {
            throw new RuntimeException('解密通知数据失败');
        }

        return $orderData;
    }

    /**
     * 处理退款
     */
    public function refund(string $outTradeNo, string $outRefundNo, int $refundAmount, int $totalAmount): array
    {
        $url = 'https://api.mch.weixin.qq.com/v3/refund/domestic/refunds';

        $body = [
            'out_trade_no'  => $outTradeNo,
            'out_refund_no' => $outRefundNo,
            'amount'        => [
                'refund'   => $refundAmount,
                'total'    => $totalAmount,
                'currency' => 'CNY',
            ],
        ];

        $response = $this->httpPostV3($url, $body);
        $result   = json_decode($response, true);

        if (!$result || isset($result['code'])) {
            $errMsg = $result['message'] ?? '退款失败';
            throw new RuntimeException('微信退款失败: ' . $errMsg);
        }

        return $result;
    }

    /**
     * 构建小程序前端支付参数
     */
    private function buildPayParams(string $prepayId): array
    {
        $timestamp = (string) time();
        $nonceStr  = $this->generateNonceStr();
        $package   = 'prepay_id=' . $prepayId;

        $message = $this->appId . "\n"
                 . $timestamp . "\n"
                 . $nonceStr . "\n"
                 . $package . "\n";

        $signature = $this->rsaSign($message);

        return [
            'appId'     => $this->appId,
            'timeStamp' => $timestamp,
            'nonceStr'  => $nonceStr,
            'package'   => $package,
            'signType'  => 'RSA',
            'paySign'   => $signature,
        ];
    }

    /**
     * RSA-SHA256签名
     */
    private function rsaSign(string $message): string
    {
        if (empty($this->privateKey)) {
            throw new RuntimeException('商户私钥未配置');
        }

        $key = openssl_pkey_get_private($this->privateKey);
        if (!$key) {
            throw new RuntimeException('私钥格式错误');
        }

        $signature = '';
        $result    = openssl_sign($message, $signature, $key, OPENSSL_ALGO_SHA256);
        if (!$result) {
            throw new RuntimeException('签名失败');
        }

        return base64_encode($signature);
    }

    /**
     * AEAD AES-256-GCM解密
     */
    private function decryptAEAD(string $ciphertext, string $nonce, string $associatedData): string
    {
        $ciphertext   = base64_decode($ciphertext);
        $aesKey       = $this->apiKey;
        $aad          = $associatedData;
        $tag          = substr($ciphertext, -16);
        $ciphertext   = substr($ciphertext, 0, -16);

        $decrypted = openssl_decrypt(
            $ciphertext,
            'aes-256-gcm',
            $aesKey,
            OPENSSL_RAW_DATA,
            $nonce,
            $tag,
            $aad
        );

        if ($decrypted === false) {
            throw new RuntimeException('AEAD解密失败');
        }

        return $decrypted;
    }

    /**
     * 带微信支付v3认证的HTTP POST请求
     */
    private function httpPostV3(string $url, array $body): string
    {
        $bodyJson = json_encode($body, JSON_UNESCAPED_UNICODE);
        $timestamp = (string) time();
        $nonceStr  = $this->generateNonceStr();

        $parsedUrl = parse_url($url);
        $uri       = $parsedUrl['path'] ?? '';
        if (isset($parsedUrl['query'])) {
            $uri .= '?' . $parsedUrl['query'];
        }

        $message = "POST\n" . $uri . "\n" . $timestamp . "\n" . $nonceStr . "\n" . $bodyJson . "\n";
        $signature = $this->rsaSign($message);

        $authorization = sprintf(
            'WECHATPAY2-SHA256-RSA2048 mchid="%s",nonce_str="%s",timestamp="%s",serial_no="%s",signature="%s"',
            $this->mchId,
            $nonceStr,
            $timestamp,
            $this->serialNumber,
            $signature
        );

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $bodyJson,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: ' . $authorization,
            ],
        ]);

        $response = curl_exec($ch);
        $errno    = curl_errno($ch);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($errno) {
            throw new RuntimeException('HTTP请求失败: ' . $error);
        }

        return $response ?: '';
    }

    /**
     * 带微信支付v3认证的HTTP GET请求
     */
    private function httpGetV3(string $url): string
    {
        $timestamp = (string) time();
        $nonceStr  = $this->generateNonceStr();

        $parsedUrl = parse_url($url);
        $uri       = $parsedUrl['path'] ?? '';
        if (isset($parsedUrl['query'])) {
            $uri .= '?' . $parsedUrl['query'];
        }

        $message   = "GET\n" . $uri . "\n" . $timestamp . "\n" . $nonceStr . "\n\n";
        $signature = $this->rsaSign($message);

        $authorization = sprintf(
            'WECHATPAY2-SHA256-RSA2048 mchid="%s",nonce_str="%s",timestamp="%s",serial_no="%s",signature="%s"',
            $this->mchId,
            $nonceStr,
            $timestamp,
            $this->serialNumber,
            $signature
        );

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => [
                'Accept: application/json',
                'Authorization: ' . $authorization,
            ],
        ]);

        $response = curl_exec($ch);
        $errno    = curl_errno($ch);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($errno) {
            throw new RuntimeException('HTTP请求失败: ' . $error);
        }

        return $response ?: '';
    }

    /**
     * 生成随机字符串
     */
    private function generateNonceStr(int $length = 32): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $str   = '';
        for ($i = 0; $i < $length; $i++) {
            $str .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $str;
    }
}
