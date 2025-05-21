const axios = require('axios');
const crypto = require('crypto');
const querystring = require('querystring');

class ApiClient {
  constructor(appId, appSecret, baseUrl) {
    this.appId = appId;
    this.appSecret = appSecret;
    this.baseUrl = baseUrl.replace(/\/+$/, ''); // 移除末尾斜杠
  }

  async make(url) {
    const endpoint = '/67d27c333e192';
    return await this.sendGetRequest(endpoint, { url });
  }

  getSign(data) {
    const sortedKeys = Object.keys(data).sort();
    const parts = sortedKeys.map(
      key => `${key}=${encodeURIComponent(data[key])}`
    );
    let preStr = parts.join('&') + this.appSecret;
    return crypto.createHash('md5').update(preStr).digest('hex');
  }

  async sendGetRequest(endpoint, params) {
    // 添加 app_id
    const data = { ...params, app_id: this.appId };
    // 获取签名
    const sign = this.getSign(data);

    // 重新排序参数
    const sortedData = Object.keys(data).sort().reduce((obj, key) => {
      obj[key] = data[key];
      return obj;
    }, {});
    const queryString = querystring.stringify(sortedData);

    const fullUrl = `${this.baseUrl}${endpoint}?${queryString}`;

    try {
      const response = await axios.get(fullUrl, {
        headers: {
          'Sign': sign
        },
        timeout: 60000
      });
      return response.data;
    } catch (err) {
      if (err.response) {
        throw new Error(`API 请求失败: ${err.response.status} - ${err.response.statusText}`);
      } else if (err.request) {
        throw new Error(`API 请求失败（无响应）: ${fullUrl}`);
      } else {
        throw new Error(`请求异常: ${err.message}`);
      }
    }
  }
}

// 示例调用
(async () => {
  const api = new ApiClient("18207536", "7QXmPfMDdhgOrjGDjuVYctEMFQSKWNXTw", "https://gateway.douyidou.cc/api");
  try {
    const result = await api.make("url");
    console.log(result);
  } catch (err) {
    console.error(err.message);
  }
})();
