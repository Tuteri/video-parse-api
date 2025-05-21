import hashlib
import json
import urllib.parse
import urllib.request
import ssl


class ApiClient:
    def __init__(self, app_id: str, app_secret: str, base_url: str):
        self.app_id = app_id
        self.app_secret = app_secret
        self.base_url = base_url.rstrip('/')

    def make(self, url: str) -> dict:
        endpoint = '/parse'
        return self.send_get_request(endpoint, {'url': url})

    def send_get_request(self, endpoint: str, params: dict) -> dict:
        # 添加 app_id 参数
        data = params.copy()
        data['app_id'] = self.app_id

        # 签名
        sign = self.get_sign(data)

        # 排序后构建 query string
        sorted_data = dict(sorted(data.items()))
        query_string = urllib.parse.urlencode(sorted_data)

        # 构建完整 URL
        full_url = f"{self.base_url}{endpoint}?{query_string}"
        # 构建请求对象，带 Sign 头
        req = urllib.request.Request(full_url)
        req.add_header('Sign', sign)

        # 忽略 SSL 验证
        context = ssl._create_unverified_context()
      
        try:
            with urllib.request.urlopen(req, timeout=60, context=context) as response:
                body = response.read().decode('utf-8')
                result = json.loads(body)
                return result
        except Exception as e:
            print(f"请求失败: {e}")
            return {}

    def get_sign(self, data: dict) -> str:
        # 1. 按 key 升序排序
        sorted_items = sorted(data.items())

        # 2. 拼接 key=value（urlencode value）字符串
        parts = [f"{key}={urllib.parse.quote_plus(str(value))}" for key, value in sorted_items]
        pre_str = '&'.join(parts)

        # 3. 拼接 appSecret
        pre_str += self.app_secret

        # 4. 返回 MD5 签名
        return hashlib.md5(pre_str.encode('utf-8')).hexdigest()


# 使用示例
api = ApiClient("", "", "https://gateway.diadi.cn/api")
print(api.make("url"))
