/**
 * HTTP请求封装 - 统一请求/响应拦截
 * 用于资源下载小程序所有API调用
 */

const BASE_URL = 'http://192.168.0.106:9901';

// ============ Token 管理 ============

function getToken() {
  return uni.getStorageSync('token') || '';
}

function setToken(token) {
  uni.setStorageSync('token', token);
}

function clearToken() {
  uni.removeStorageSync('token');
  uni.removeStorageSync('userInfo');
}

// ============ Loading 管理 ============

let loadingCount = 0;

function showLoading(title) {
  loadingCount++;
  uni.showLoading({ title: title || '加载中...', mask: false });
}

function hideLoading() {
  loadingCount--;
  if (loadingCount <= 0) {
    loadingCount = 0;
    uni.hideLoading();
  }
}

// ============ 核心请求 ============

const request = (options) => {
  return new Promise((resolve, reject) => {
    const token = getToken();
    const header = {
      'Content-Type': 'application/json',
      ...options.header
    };
    if (token) {
      header['Authorization'] = `Bearer ${token}`;
    }

    if (options.showLoading) {
      showLoading(options.loadingText);
    }

    uni.request({
      url: options.url.startsWith('http') ? options.url : BASE_URL + options.url,
      method: options.method || 'GET',
      data: options.data || {},
      header: header,
      timeout: options.timeout || 30000,
      success: (res) => {
        if (options.showLoading) hideLoading();

        if (res.statusCode === 200) {
          const data = res.data;
          if (data.code === 0) {
            resolve(data);
          } else if (data.code === 401) {
            if (!options.silent) {
              clearToken();
              uni.showToast({ title: '请重新登录', icon: 'none' });
            }
            reject(new Error('未授权'));
          } else {
            if (!options.silent) {
              uni.showToast({ title: data.message || data.msg || '请求失败', icon: 'none' });
            }
            reject(new Error(data.message || data.msg || '请求失败'));
          }
        } else if (res.statusCode === 401) {
          if (!options.silent) {
            clearToken();
            uni.showToast({ title: '请重新登录', icon: 'none' });
          }
          reject(new Error('未授权'));
        } else {
          // 尝试从响应体获取错误信息
          var errMsg = '服务器错误(' + res.statusCode + ')';
          try {
            var body = typeof res.data === 'string' ? JSON.parse(res.data) : res.data;
            if (body && (body.message || body.msg)) {
              errMsg = body.message || body.msg;
            }
          } catch(e) {}
          if (!options.silent) {
            uni.showToast({ title: errMsg, icon: 'none' });
          }
          reject(new Error(errMsg));
        }
      },
      fail: (err) => {
        if (options.showLoading) hideLoading();
        if (!options.silent) {
          uni.showToast({ title: '网络连接失败', icon: 'none' });
        }
        reject(err);
      }
    });
  });
};

// ============ HTTP 方法 ============

const http = {
  get(url, data, options = {}) {
    return request({ url, method: 'GET', data, ...options });
  },
  post(url, data, options = {}) {
    return request({ url, method: 'POST', data, ...options });
  },
  put(url, data, options = {}) {
    return request({ url, method: 'PUT', data, ...options });
  },
  del(url, data, options = {}) {
    return request({ url, method: 'DELETE', data, ...options });
  },
  delete(url, data, options = {}) {
    return request({ url, method: 'DELETE', data, ...options });
  },
  upload(url, filePath, name, formData, options = {}) {
    return new Promise((resolve, reject) => {
      const token = getToken();
      const header = {};
      if (token) {
        header['Authorization'] = `Bearer ${token}`;
      }
      uni.uploadFile({
        url: url.startsWith('http') ? url : BASE_URL + url,
        filePath: filePath,
        name: name || 'file',
        formData: formData || {},
        header: header,
        success: (res) => {
          if (res.statusCode === 200) {
            const data = typeof res.data === 'string' ? JSON.parse(res.data) : res.data;
            if (data.code === 0) {
              resolve(data);
            } else {
              reject(new Error(data.message || data.msg || '上传失败'));
            }
          } else {
            reject(new Error(`HTTP ${res.statusCode}`));
          }
        },
        fail: (err) => {
          reject(err);
        }
      });
    });
  },
  download(url, options = {}) {
    return new Promise((resolve, reject) => {
      const token = getToken();
      const header = {};
      if (token) {
        header['Authorization'] = `Bearer ${token}`;
      }
      uni.downloadFile({
        url: url.startsWith('http') ? url : BASE_URL + url,
        header: header,
        success: (res) => {
          if (res.statusCode === 200) {
            resolve(res);
          } else {
            reject(new Error(`HTTP ${res.statusCode}`));
          }
        },
        fail: (err) => {
          reject(err);
        }
      });
    });
  }
};

http.getBaseUrl = function() {
  return BASE_URL;
};

export default http;
export { BASE_URL, getToken, setToken, clearToken, showLoading, hideLoading };
