/**
 * 认证工具模块
 * 处理微信登录、Token管理、用户信息存储
 */

import http from '../utils/http.js'

/**
 * 微信登录 - 获取code并换取Token
 * 流程: wx.login → 获取code → 发送至后端 → 后端返回token
 * @returns {Promise<Object>} 登录结果，包含token和用户信息
 */
export function login() {
  return new Promise((resolve, reject) => {
    // 步骤1: 调用微信登录获取code
    uni.login({
      provider: 'weixin',
      success(loginRes) {
        if (!loginRes.code) {
          reject({ code: -1, message: '微信登录失败：未获取到code' })
          return
        }

        console.log('[Auth] 获取到微信code:', loginRes.code)

        // 步骤2: 将code发送至后端换取token
        http.post('/api/auth/wx-login', { code: loginRes.code }, { showLoading: true })
          .then(res => {
            const { token, userInfo } = res.data || res

            if (!token) {
              reject({ code: -1, message: '登录失败：服务器未返回token' })
              return
            }

            // 步骤3: 存储token和用户信息
            uni.setStorageSync('token', token)
            if (userInfo) {
              uni.setStorageSync('userInfo', JSON.stringify(userInfo))
            }

            // 更新全局状态
            const app = getApp()
            if (app) {
              app.globalData.token = token
              app.globalData.isLogin = true
              app.globalData.userInfo = userInfo || null
            }

            console.log('[Auth] 登录成功')
            resolve({ token, userInfo })
          })
          .catch(err => {
            console.error('[Auth] 登录接口请求失败:', err)
            reject(err)
          })
      },
      fail(err) {
        console.error('[Auth] wx.login调用失败:', err)
        reject({ code: -1, message: '微信登录调用失败', detail: err })
      }
    })
  })
}

/**
 * 获取用户微信信息（头像、昵称）
 * 需要用户主动触发（如点击按钮）
 * @returns {Promise<Object>} 用户信息
 */
export function getUserProfile() {
  return new Promise((resolve, reject) => {
    uni.getUserProfile({
      desc: '用于完善用户资料',
      success(res) {
        const userInfo = res.userInfo
        console.log('[Auth] 获取用户信息成功:', userInfo.nickName)
        resolve(userInfo)
      },
      fail(err) {
        console.error('[Auth] 获取用户信息失败:', err)
        reject({ code: -1, message: '获取用户信息失败', detail: err })
      }
    })
  })
}

/**
 * 检查登录状态是否有效
 * 验证本地token是否存在且未过期
 * @returns {Boolean} 是否已登录
 */
export function checkLogin() {
  const token = uni.getStorageSync('token')
  if (!token) {
    return false
  }

  // 可选: 向后端验证token有效性
  // 这里简单判断token是否存在
  const app = getApp()
  return !!(app && app.globalData.isLogin)
}

/**
 * 获取存储的用户信息
 * @returns {Object|null} 用户信息对象，未登录返回null
 */
export function getUserInfo() {
  try {
    const userInfoStr = uni.getStorageSync('userInfo')
    if (userInfoStr) {
      return typeof userInfoStr === 'string' ? JSON.parse(userInfoStr) : userInfoStr
    }
    return null
  } catch (e) {
    console.error('[Auth] 解析用户信息失败:', e)
    return null
  }
}

/**
 * 获取当前Token
 * @returns {String} Token字符串，未登录返回空字符串
 */
export function getToken() {
  return uni.getStorageSync('token') || ''
}

/**
 * 退出登录
 * 清除本地存储的登录信息并更新全局状态
 */
export function logout() {
  console.log('[Auth] 执行退出登录')

  // 清除本地存储
  uni.removeStorageSync('token')
  uni.removeStorageSync('userInfo')

  // 更新全局状态
  const app = getApp()
  if (app) {
    app.globalData.token = ''
    app.globalData.isLogin = false
    app.globalData.userInfo = null
  }

  // 提示用户
  uni.showToast({ title: '已退出登录', icon: 'success', duration: 1500 })
}

/**
 * 确保已登录，未登录则执行登录流程
 * @returns {Promise<Object>} 登录结果
 */
export function ensureLogin() {
  if (checkLogin()) {
    return Promise.resolve({
      token: getToken(),
      userInfo: getUserInfo()
    })
  }
  return login()
}

export default {
  login,
  getUserProfile,
  checkLogin,
  getUserInfo,
  getToken,
  logout,
  ensureLogin
}
