<?php

class ApiClient
{
    private $appId;
    private $appSecret;
    private $baseUrl;

    public function __construct(string $appId, string $appSecret, string $baseUrl)
    {
        $this->appId = $appId;
        $this->appSecret = $appSecret;
        $this->baseUrl = rtrim($baseUrl, '/'); // 移除多余斜杠
    }

    public function make(string $url): array
    {
        $endpoint = '/67d27c333e192';
        return $this->sendGetRequest($endpoint, ['url' => $url]);
    }

    private function sendGetRequest(string $endpoint, array $params): array
    {
        // 添加 app_id 参数
        $data = $params;
        $data['app_id'] = $this->appId;

        // 签名
        $sign = $this->getSign($data);
        ksort($data);
        // 构建查询参数
        $queryString = http_build_query($data);
        // 构建完整 URL
        $url = $this->baseUrl . $endpoint . '?' . $queryString;
        // 创建请求上下文（带上 Sign 头）
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "Sign: {$sign}\r\n",
                'timeout' => 60,
            ]
        ]);

        // 发送请求
        $response = @file_get_contents($url, false, $context);
        if ($response === false) {
            throw new RuntimeException("API 请求失败: $url");
        }

        // 解析 JSON
        $result = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException("JSON 解析失败: " . json_last_error_msg());
        }

        return $result;
    }

    private function getSign(array $data): string
    {
        // 1. 按 key 升序排序
        ksort($data);

        // 2. 构造 key=value& 拼接字符串（仅对 value 进行 urlencode）
        $parts = [];
        foreach ($data as $key => $value) {
            $encodedValue = urlencode($value);
            $parts[] = "{$key}={$encodedValue}";
        }

        $preStr = implode('&', $parts);

        // 3. 拼接 appSecret
        $preStr .= $this->appSecret;
        // 4. 返回 MD5 签名
        return md5($preStr);
    }
}
$api = new ApiClient("appId", "appSecret", "https://gateway.diadi.cn/api");
print($api->make("url"));
