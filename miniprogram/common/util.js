/**
 * 通用工具函数模块
 * 提供文件大小格式化、日期格式化、价格处理、防抖、分享配置等常用功能
 */

/**
 * 格式化文件大小
 * @param {Number} bytes - 字节数
 * @param {Number} decimals - 小数位数，默认2
 * @returns {String} 格式化后的文件大小（如 "1.5 MB"）
 */
export function formatFileSize(bytes, decimals = 2) {
  if (bytes === 0) return '0 B'
  if (!bytes || isNaN(bytes)) return '未知大小'

  const k = 1024
  const sizes = ['B', 'KB', 'MB', 'GB', 'TB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))

  const size = (bytes / Math.pow(k, i)).toFixed(decimals)
  // 移除末尾的0，如 "1.50" -> "1.5"
  return parseFloat(size) + ' ' + sizes[i]
}

/**
 * 格式化日期时间
 * @param {Number|String|Date} timestamp - 时间戳（秒或毫秒）或日期字符串
 * @param {String} format - 格式模板，默认 'YYYY-MM-DD HH:mm:ss'
 *   支持: YYYY, MM, DD, HH, mm, ss
 * @returns {String} 格式化后的日期字符串
 */
export function formatDate(timestamp, format = 'YYYY-MM-DD HH:mm:ss') {
  if (!timestamp) return ''

  let date
  if (timestamp instanceof Date) {
    date = timestamp
  } else if (typeof timestamp === 'string') {
    // 兼容iOS的日期字符串格式
    timestamp = timestamp.replace(/-/g, '/')
    date = new Date(timestamp)
  } else if (typeof timestamp === 'number') {
    // 判断是秒还是毫秒（10位为秒，13位为毫秒）
    date = timestamp.toString().length === 10 ? new Date(timestamp * 1000) : new Date(timestamp)
  } else {
    return ''
  }

  if (isNaN(date.getTime())) return ''

  const year = date.getFullYear()
  const month = date.getMonth() + 1
  const day = date.getDate()
  const hours = date.getHours()
  const minutes = date.getMinutes()
  const seconds = date.getSeconds()

  const padZero = (num) => (num < 10 ? '0' + num : '' + num)

  return format
    .replace('YYYY', year)
    .replace('MM', padZero(month))
    .replace('DD', padZero(day))
    .replace('HH', padZero(hours))
    .replace('mm', padZero(minutes))
    .replace('ss', padZero(seconds))
}

/**
 * 格式化价格
 * @param {Number|String} price - 价格（单位：分 或 元，取决于isCent参数）
 * @param {Boolean} isCent - 是否为分单位，默认true
 * @returns {String} 格式化后的价格字符串，如 "9.90"
 */
export function formatPrice(price, isCent = true) {
  if (price === null || price === undefined || price === '') return '0.00'

  let num = parseFloat(price)
  if (isNaN(num)) return '0.00'

  // 如果是分，转换为元
  if (isCent) {
    num = num / 100
  }

  return num.toFixed(2)
}

/**
 * 格式化价格（带人民币符号）
 * @param {Number|String} price - 价格
 * @param {Boolean} isCent - 是否为分单位
 * @returns {String} 如 "¥9.90"
 */
export function formatPriceWithSymbol(price, isCent = true) {
  return '¥' + formatPrice(price, isCent)
}

/**
 * 防抖函数
 * @param {Function} fn - 需要防抖的函数
 * @param {Number} delay - 延迟时间（毫秒），默认300
 * @returns {Function} 防抖后的函数
 */
export function debounce(fn, delay = 300) {
  let timer = null
  return function (...args) {
    if (timer) clearTimeout(timer)
    timer = setTimeout(() => {
      fn.apply(this, args)
      timer = null
    }, delay)
  }
}

/**
 * 节流函数
 * @param {Function} fn - 需要节流的函数
 * @param {Number} interval - 间隔时间（毫秒），默认300
 * @returns {Function} 节流后的函数
 */
export function throttle(fn, interval = 300) {
  let lastTime = 0
  return function (...args) {
    const now = Date.now()
    if (now - lastTime >= interval) {
      fn.apply(this, args)
      lastTime = now
    }
  }
}

/**
 * 生成分享配置
 * @param {String} title - 分享标题
 * @param {String} path - 分享路径（小程序页面路径）
 * @param {String} imageUrl - 分享图片URL（可选）
 * @returns {Object} 分享配置对象，用于onShareAppMessage
 */
export function shareConfig(title, path, imageUrl) {
  const config = {
    title: title || '资料下载器 - 海量资源免费下载',
    path: path || '/pages/index/index'
  }

  if (imageUrl) {
    config.imageUrl = imageUrl
  }

  return config
}

/**
 * 生成分享到朋友圈的配置
 * @param {String} title - 分享标题
 * @param {String} query - 查询参数字符串
 * @returns {Object} 分享配置对象，用于onShareTimeline
 */
export function shareTimelineConfig(title, query) {
  return {
    title: title || '资料下载器 - 海量资源免费下载',
    query: query || ''
  }
}

/**
 * 生成随机字符串
 * @param {Number} length - 字符串长度，默认32
 * @returns {String} 随机字符串
 */
export function randomString(length = 32) {
  const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789'
  let result = ''
  for (let i = 0; i < length; i++) {
    result += chars.charAt(Math.floor(Math.random() * chars.length))
  }
  return result
}

/**
 * 深拷贝对象
 * @param {*} obj - 源对象
 * @returns {*} 拷贝后的新对象
 */
export function deepClone(obj) {
  if (obj === null || typeof obj !== 'object') return obj
  if (obj instanceof Date) return new Date(obj.getTime())
  if (obj instanceof Array) return obj.map(item => deepClone(item))

  const clonedObj = {}
  for (const key in obj) {
    if (obj.hasOwnProperty(key)) {
      clonedObj[key] = deepClone(obj[key])
    }
  }
  return clonedObj
}

/**
 * 复制文本到剪贴板
 * @param {String} text - 要复制的文本
 * @param {String} successMsg - 成功提示
 */
export function copyToClipboard(text, successMsg = '复制成功') {
  uni.setClipboardData({
    data: text,
    success() {
      uni.showToast({ title: successMsg, icon: 'success' })
    }
  })
}

/**
 * 拨打电话
 * @param {String} phoneNumber - 电话号码
 */
export function makePhoneCall(phoneNumber) {
  if (!phoneNumber) return
  uni.makePhoneCall({
    phoneNumber: phoneNumber.toString(),
    fail(err) {
      if (err.errMsg !== 'makePhoneCall:fail cancel') {
        uni.showToast({ title: '拨号失败', icon: 'none' })
      }
    }
  })
}

/**
 * 保存图片到相册
 * @param {String} imageUrl - 图片地址
 * @returns {Promise}
 */
export function saveImageToAlbum(imageUrl) {
  return new Promise((resolve, reject) => {
    uni.downloadFile({
      url: imageUrl,
      success(res) {
        if (res.statusCode === 200) {
          uni.saveImageToPhotosAlbum({
            filePath: res.tempFilePath,
            success() {
              uni.showToast({ title: '保存成功', icon: 'success' })
              resolve()
            },
            fail(err) {
              if (err.errMsg.includes('auth deny')) {
                uni.showModal({
                  title: '提示',
                  content: '需要您授权保存图片到相册',
                  success(modalRes) {
                    if (modalRes.confirm) {
                      uni.openSetting()
                    }
                  }
                })
              }
              reject(err)
            }
          })
        }
      },
      fail(err) {
        reject(err)
      }
  })
}

export default {
  formatFileSize,
  formatDate,
  formatPrice,
  formatPriceWithSymbol,
  debounce,
  throttle,
  shareConfig,
  shareTimelineConfig,
  randomString,
  deepClone,
  copyToClipboard,
  makePhoneCall,
  saveImageToAlbum
}
