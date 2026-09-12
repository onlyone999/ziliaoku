/**
 * 主题管理模块
 * 6套预设风格 + CSS变量注入 + 原生栏动态切换 + 图标着色
 */

import tabbarIcons from './tabbar-icons.js'

const THEMES = {
  green: {
    name: '默认绿',
    emoji: '🌿',
    primary: '#2ed573',
    primaryDark: '#27ae60',
    primaryLight: '#3be88a',
    accent: '#1abc9c',
    gradientStart: '#26c67a',
    gradientEnd: '#1abc9c',
    navBg: '#2ed573',
    tabBarSelected: '#2ed573',
    rgbPrimary: '46,213,115',
    rgbAccent: '26,188,156',
    tintLight: '#f0fdf4',
    tintMedium: '#d1fae5',
    tintStrong: '#a7f3d0'
  },
  blue: {
    name: '海洋蓝',
    emoji: '🔵',
    primary: '#3b82f6',
    primaryDark: '#2563eb',
    primaryLight: '#60a5fa',
    accent: '#06b6d4',
    gradientStart: '#60a5fa',
    gradientEnd: '#3b82f6',
    navBg: '#3b82f6',
    tabBarSelected: '#3b82f6',
    rgbPrimary: '59,130,246',
    rgbAccent: '6,182,212',
    tintLight: '#eff6ff',
    tintMedium: '#dbeafe',
    tintStrong: '#bfdbfe'
  },
  purple: {
    name: '星空紫',
    emoji: '🟣',
    primary: '#8b5cf6',
    primaryDark: '#7c3aed',
    primaryLight: '#a78bfa',
    accent: '#c084fc',
    gradientStart: '#a78bfa',
    gradientEnd: '#7c3aed',
    navBg: '#8b5cf6',
    tabBarSelected: '#8b5cf6',
    rgbPrimary: '139,92,246',
    rgbAccent: '192,132,252',
    tintLight: '#f5f3ff',
    tintMedium: '#ede9fe',
    tintStrong: '#ddd6fe'
  },
  orange: {
    name: '暖阳橙',
    emoji: '🟠',
    primary: '#f59e0b',
    primaryDark: '#d97706',
    primaryLight: '#fbbf24',
    accent: '#f97316',
    gradientStart: '#fbbf24',
    gradientEnd: '#f59e0b',
    navBg: '#f59e0b',
    tabBarSelected: '#f59e0b',
    rgbPrimary: '245,158,11',
    rgbAccent: '249,115,22',
    tintLight: '#fffbeb',
    tintMedium: '#fef3c7',
    tintStrong: '#fde68a'
  },
  pink: {
    name: '樱花粉',
    emoji: '🌸',
    primary: '#ec4899',
    primaryDark: '#db2777',
    primaryLight: '#f472b6',
    accent: '#f9a8d4',
    gradientStart: '#f472b6',
    gradientEnd: '#db2777',
    navBg: '#ec4899',
    tabBarSelected: '#ec4899',
    rgbPrimary: '236,72,153',
    rgbAccent: '249,168,212',
    tintLight: '#fdf2f8',
    tintMedium: '#fce7f3',
    tintStrong: '#fbcfe8'
  },
  red: {
    name: '中国红',
    emoji: '🔴',
    primary: '#ef4444',
    primaryDark: '#dc2626',
    primaryLight: '#f87171',
    accent: '#fca5a5',
    gradientStart: '#f87171',
    gradientEnd: '#dc2626',
    navBg: '#ef4444',
    tabBarSelected: '#ef4444',
    rgbPrimary: '239,68,68',
    rgbAccent: '252,165,165',
    tintLight: '#fef2f2',
    tintMedium: '#fee2e2',
    tintStrong: '#fecaca'
  }
}

const THEME_LIST = Object.keys(THEMES)
const DEFAULT_THEME = 'green'

/**
 * 从缓存加载主题并应用
 * @returns {string} 当前主题名
 */
function loadTheme () {
  const name = uni.getStorageSync('theme') || DEFAULT_THEME
  applyTheme(name)
  return name
}

/**
 * 应用主题到全局
 * @param {string} name 主题名
 */
function applyTheme (name) {
  const theme = THEMES[name] || THEMES[DEFAULT_THEME]

  // 1. 持久化
  uni.setStorageSync('theme', name)

  // 2. 更新 globalData
  const app = getApp()
  if (app) {
    app.globalData.currentTheme = name
  }

  // 3. 注入 CSS 变量到页面根元素
  _injectCssVars(theme)

  // 4. 更新 tabBar 颜色 + 图标着色
  try {
    uni.setTabBarStyle({
      color: '#999999',
      selectedColor: theme.tabBarSelected,
      backgroundColor: '#ffffff',
      borderStyle: 'white'
    })
    // 动态着色选中图标
    tabbarIcons.updateTabBarIcons(name, theme.tabBarSelected)
  } catch (e) {}

  // 5. 更新当前页面导航栏颜色
  _updateNavBar(theme)
}

/**
 * 注入 CSS 变量到 page 元素
 */
function _injectCssVars (theme) {
  // #ifdef H5
  const root = document.documentElement
  if (root) {
    root.style.setProperty('--c-primary', theme.primary)
    root.style.setProperty('--c-primary-dark', theme.primaryDark)
    root.style.setProperty('--c-primary-light', theme.primaryLight)
    root.style.setProperty('--c-accent', theme.accent)
    root.style.setProperty('--c-gradient-start', theme.gradientStart)
    root.style.setProperty('--c-gradient-end', theme.gradientEnd)
    root.style.setProperty('--c-rgb-primary', theme.rgbPrimary)
    root.style.setProperty('--c-rgb-accent', theme.rgbAccent)
    root.style.setProperty('--c-tint-light', theme.tintLight)
    root.style.setProperty('--c-tint-medium', theme.tintMedium)
    root.style.setProperty('--c-tint-strong', theme.tintStrong)
  }
  // #endif

  // 小程序：写入 globalData，各页面 onShow 中读取并注入到自身 page 节点
  // 同时存一份到 storage 以便跨页面读取
  uni.setStorageSync('themeVars', {
    '--c-primary': theme.primary,
    '--c-primary-dark': theme.primaryDark,
    '--c-primary-light': theme.primaryLight,
    '--c-accent': theme.accent,
    '--c-gradient-start': theme.gradientStart,
    '--c-gradient-end': theme.gradientEnd,
    '--c-rgb-primary': theme.rgbPrimary,
    '--c-rgb-accent': theme.rgbAccent,
    '--c-tint-light': theme.tintLight,
    '--c-tint-medium': theme.tintMedium,
    '--c-tint-strong': theme.tintStrong
  })
}

/**
 * 更新当前页面导航栏颜色
 */
function _updateNavBar (theme) {
  try {
    uni.setNavigationBarColor({
      frontColor: '#ffffff',
      backgroundColor: theme.navBg,
      animation: { duration: 300, timingFunc: 'easeIn' }
    })
  } catch (e) {}
}

/**
 * 获取当前主题配置对象
 */
function getTheme () {
  const name = uni.getStorageSync('theme') || DEFAULT_THEME
  return THEMES[name] || THEMES[DEFAULT_THEME]
}

/**
 * 获取当前主题名
 */
function getThemeName () {
  return uni.getStorageSync('theme') || DEFAULT_THEME
}

export default {
  THEMES,
  THEME_LIST,
  DEFAULT_THEME,
  loadTheme,
  applyTheme,
  getTheme,
  getThemeName
}
