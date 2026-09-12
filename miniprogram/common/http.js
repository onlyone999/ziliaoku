/**
 * HTTP请求封装
 * 基于uni.request封装，支持JWT认证、拦截器、加载状态管理
 */

import config from './config.js'

// 请求队列管理
let requestCount = 0
let loadingTimer = null

/**
 * 显示加载状态（防抖处理，避免闪烁）
 */
function showRequestLoading() {
  if (requestCount === 0) {
    loadingTimer = setTimeout(() => {
      uni.showLoading({ title: '加载中...', mask: false })
    }, 300)
  }
  requestCount++
}

/**
 * 隐藏加载状态
 */
function hideRequestLoading() {
  requestCount--
  if (requestCount <= 0) {
    requestCount = 0
    if (loadingTimer) {
      clearTimeout(loadingTimer)
      loadingTimer = null
    }
    uni.hideLoading()
  }
}

/**
 * 请求拦截器 - 添加认证头
 * @param {Object} options - 请求配置
 * @returns {Object} 处理后的配置
 */
function requestInterceptor(options) {
  const token = uni.getStorageSync('token')
  if (token) {
    options.header = options.header || {}
    options.header['Authorization'] = 'Bearer ' + token
  }

  // 添加通用请求头
  options.header = {
    ...options.header,
    'Content-Type': options.contentType || 'application/json',
    'Accept': 'application/json'
  }

  return options
}

/**
 * 响应拦截器 - 处理业务状态码
 * @param {Object} response - 响应对象
 * @param {String} url - 请求地址
 * @returns {Object} 处理后的响应数据
 */
function responseInterceptor(response, url) {
  const { statusCode, data } = response

  // HTTP状态码判断
  if (statusCode === 200) {
    // 业务状态码判断
    if (data.code === 0 || data.code === 200 || data.success) {
      return data
    } else {
      // 业务错误
      const errMsg = data.message || data.msg || '请求失败'
      uni.showToast({ title: errMsg, icon: 'none', duration: 2000 })
      return Promise.reject(data)
    }
  } else if (statusCode === 401) {
    // 未授权 - 清除登录信息并跳转
    handleUnauthorized()
    return Promise.reject({ code: 401, message: '登录已过期，请重新登录' })
  } else if (statusCode === 403) {
    uni.showToast({ title: '暂无权限访问', icon: 'none' })
    return Promise.reject({ code: 403, message: '暂无权限' })
  } else if (statusCode === 404) {
    uni.showToast({ title: '请求资源不存在', icon: 'none' })
    return Promise.reject({ code: 404, message: '资源不存在' })
  } else if (statusCode === 500) {
    uni.showToast({ title: '服务器错误，请稍后重试', icon: 'none' })
    return Promise.reject({ code: 500, message: '服务器错误' })
  } else {
    uni.showToast({ title: `请求失败(${statusCode})`, icon: 'none' })
    return Promise.reject({ code: statusCode, message: '请求失败' })
  }
}

/**
 * 处理未授权（401）情况
 */
function handleUnauthorized() {
  // 清除本地登录信息
  uni.removeStorageSync('token')
  uni.removeStorageSync('userInfo')

  const app = getApp()
  if (app) {
    app.globalData.token = ''
    app.globalData.isLogin = false
    app.globalData.userInfo = null
  }

  // 提示并跳转登录
  uni.showModal({
    title: '提示',
    content: '登录已过期，请重新登录',
    showCancel: false,
    confirmText: '我知道了',
    success() {
      uni.switchTab({ url: '/pages/user/index' })
    }
  })
}

/**
 * 核心请求方法
 * @param {Object} options - 请求配置
 * @param {String} options.url - 请求路径（相对路径）
 * @param {String} options.method - 请求方法
 * @param {Object} options.data - 请求数据
 * @param {Object} options.header - 自定义请求头
 * @param {Boolean} options.showLoading - 是否显示加载状态
 * @param {Boolean} options.showError - 是否显示错误提示
 * @returns {Promise} 请求Promise
 */
function request(options = {}) {
  const {
    url,
    method = 'GET',
    data = {},
    header = {},
    showLoading = true,
    showError = true,
    contentType = 'application/json'
  } = options

  // 拼接完整URL
  const fullUrl = url.startsWith('http') ? url : config.BASE_URL + url

  // 构建请求配置
  let requestOptions = {
    url: fullUrl,
    method: method.toUpperCase(),
    data,
    header,
    contentType,
    timeout: config.REQUEST_TIMEOUT
  }

  // 请求拦截
  requestOptions = requestInterceptor(requestOptions)

  // 显示加载
  if (showLoading) {
    showRequestLoading()
  }

  return new Promise((resolve, reject) => {
    uni.request({
      ...requestOptions,
      success(response) {
        try {
          const result = responseInterceptor(response, fullUrl)
          resolve(result)
        } catch (err) {
          if (showError && err.message) {
            // 错误已在拦截器中处理
          }
          reject(err)
        }
      },
      fail(error) {
        console.error('[HTTP] 请求失败:', fullUrl, error)
        if (showError) {
          uni.showToast({ title: '网络请求失败，请检查网络', icon: 'none' })
        }
        reject({ code: -1, message: '网络请求失败', detail: error })
      },
      complete() {
        if (showLoading) {
          hideRequestLoading()
        }
      }
    })
  })
}

/**
 * GET请求
 * @param {String} url - 请求路径
 * @param {Object} data - 查询参数
 * @param {Object} options - 额外配置
 * @returns {Promise}
 */
function get(url, data = {}, options = {}) {
  return request({ url, method: 'GET', data, ...options })
}

/**
 * POST请求
 * @param {String} url - 请求路径
 * @param {Object} data - 请求体数据
 * @param {Object} options - 额外配置
 * @returns {Promise}
 */
function post(url, data = {}, options = {}) {
  return request({ url, method: 'POST', data, ...options })
}

/**
 * PUT请求
 * @param {String} url - 请求路径
 * @param {Object} data - 请求体数据
 * @param {Object} options - 额外配置
 * @returns {Promise}
 */
function put(url, data = {}, options = {}) {
  return request({ url, method: 'PUT', data, ...options })
}

/**
 * DELETE请求
 * @param {String} url - 请求路径
 * @param {Object} data - 请求数据
 * @param {Object} options - 额外配置
 * @returns {Promise}
 */
function del(url, data = {}, options = {}) {
  return request({ url, method: 'DELETE', data, ...options })
}

/**
 * 上传文件
 * @param {String} url - 上传接口路径
 * @param {String} filePath - 本地文件路径
 * @param {String} name - 文件对应的key
 * @param {Object} formData - 额外表单数据
 * @returns {Promise}
 */
function upload(url, filePath, name = 'file', formData = {}) {
  const fullUrl = url.startsWith('http') ? url : config.FILE_UPLOAD_URL
  const token = uni.getStorageSync('token')

  return new Promise((resolve, reject) => {
    showRequestLoading()

    const uploadTask = uni.uploadFile({
      url: fullUrl,
      filePath,
      name,
      formData,
      header: {
        'Authorization': token ? 'Bearer ' + token : ''
      },
      success(response) {
        if (response.statusCode === 200) {
          const data = JSON.parse(response.data)
          resolve(data)
        } else {
          reject({ code: response.statusCode, message: '上传失败' })
        }
      },
      fail(error) {
        reject({ code: -1, message: '上传失败', detail: error })
      },
      complete() {
        hideRequestLoading()
      }
    })

    // 返回上传任务，支持进度监听
    return uploadTask
  })
}

/**
 * 下载文件
 * @param {String} url - 下载地址
 * @param {Object} options - 配置项
 * @returns {Promise}
 */
function download(url, options = {}) {
  const fullUrl = url.startsWith('http') ? url : config.FILE_DOWNLOAD_URL + url
  const token = uni.getStorageSync('token')

  return new Promise((resolve, reject) => {
    uni.showLoading({ title: '下载中...', mask: true })

    uni.downloadFile({
      url: fullUrl,
      header: {
        'Authorization': token ? 'Bearer ' + token : ''
      },
      success(response) {
        if (response.statusCode === 200) {
          resolve(response.tempFilePath)
        } else {
          reject({ code: response.statusCode, message: '下载失败' })
        }
      },
      fail(error) {
        reject({ code: -1, message: '下载失败', detail: error })
      },
      complete() {
        uni.hideLoading()
      }
    })
  })
}

export default {
  request,
  get,
  post,
  put,
  del,
  upload,
  download
}
