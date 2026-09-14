/**
 * 主题管理 - 灵溪风多主题联动
 * 导航 / 页面底 / tabBar / 变量同步
 */

import tabbarIcons from './tabbar-icons.js'

const THEMES = {
  green: {
    name: '清新绿',
    emoji: '🌿',
    primary: '#7cb342',
    primaryDark: '#558b2f',
    primaryLight: '#9ccc65',
    accent: '#ffb300',
    gradientStart: '#9ccc65',
    gradientEnd: '#558b2f',
    navBg: '#7cb342',
    tabBarSelected: '#7cb342',
    tabBarColor: '#7a8a68',
    tabBarBg: '#ffffff',
    pageBg: '#f0f7e6',
    pageBgAlt: '#e8f2d8',
    rgbPrimary: '124,179,66',
    rgbAccent: '255,179,0',
    tintLight: '#f1f8e9',
    tintMedium: '#dcedc8',
    tintStrong: '#c5e1a5',
    tabFade: 'rgba(240,247,230,0)'
  },
  yellow: {
    name: '暖阳黄',
    emoji: '🌻',
    primary: '#f9a825',
    primaryDark: '#f57f17',
    primaryLight: '#ffca28',
    accent: '#ff8a65',
    gradientStart: '#ffca28',
    gradientEnd: '#f57f17',
    navBg: '#f9a825',
    tabBarSelected: '#f9a825',
    tabBarColor: '#9a8a5a',
    tabBarBg: '#ffffff',
    pageBg: '#fff8e1',
    pageBgAlt: '#fff3c4',
    rgbPrimary: '249,168,37',
    rgbAccent: '255,138,101',
    tintLight: '#fff8e1',
    tintMedium: '#ffecb3',
    tintStrong: '#ffe082',
    tabFade: 'rgba(255,248,225,0)'
  },
  coral: {
    name: '珊瑚橙',
    emoji: '🍊',
    primary: '#ff7043',
    primaryDark: '#e64a19',
    primaryLight: '#ff8a65',
    accent: '#ffd54f',
    gradientStart: '#ff8a65',
    gradientEnd: '#e64a19',
    navBg: '#ff7043',
    tabBarSelected: '#ff7043',
    tabBarColor: '#a87868',
    tabBarBg: '#ffffff',
    pageBg: '#fff3ee',
    pageBgAlt: '#ffe4dc',
    rgbPrimary: '255,112,67',
    rgbAccent: '255,213,79',
    tintLight: '#fbe9e7',
    tintMedium: '#ffccbc',
    tintStrong: '#ffab91',
    tabFade: 'rgba(255,243,238,0)'
  },
  blue: {
    name: '晴空蓝',
    emoji: '☁️',
    primary: '#42a5f5',
    primaryDark: '#1e88e5',
    primaryLight: '#64b5f6',
    accent: '#ffd54f',
    gradientStart: '#64b5f6',
    gradientEnd: '#1e88e5',
    navBg: '#42a5f5',
    tabBarSelected: '#42a5f5',
    tabBarColor: '#6a8aaa',
    tabBarBg: '#ffffff',
    pageBg: '#f0f7ff',
    pageBgAlt: '#e3f0fc',
    rgbPrimary: '66,165,245',
    rgbAccent: '255,213,79',
    tintLight: '#e3f2fd',
    tintMedium: '#bbdefb',
    tintStrong: '#90caf9',
    tabFade: 'rgba(240,247,255,0)'
  },
  pink: {
    name: '蜜桃粉',
    emoji: '🌸',
    primary: '#ec407a',
    primaryDark: '#d81b60',
    primaryLight: '#f48fb1',
    accent: '#ffd54f',
    gradientStart: '#f48fb1',
    gradientEnd: '#d81b60',
    navBg: '#ec407a',
    tabBarSelected: '#ec407a',
    tabBarColor: '#b07088',
    tabBarBg: '#ffffff',
    pageBg: '#fff0f5',
    pageBgAlt: '#fde2eb',
    rgbPrimary: '236,64,122',
    rgbAccent: '255,213,79',
    tintLight: '#fce4ec',
    tintMedium: '#f8bbd0',
    tintStrong: '#f48fb1',
    tabFade: 'rgba(255,240,245,0)'
  }
}

const THEME_LIST = Object.keys(THEMES)
const DEFAULT_THEME = 'green'

function loadTheme () {
  const name = uni.getStorageSync('theme') || DEFAULT_THEME
  applyTheme(name)
  return name
}

function applyTheme (name) {
  const theme = THEMES[name] || THEMES[DEFAULT_THEME]
  uni.setStorageSync('theme', name)

  const app = getApp()
  if (app) {
    app.globalData.currentTheme = name
  }

  _injectCssVars(theme, name)

  try {
    uni.setTabBarStyle({
      color: theme.tabBarColor,
      selectedColor: theme.tabBarSelected,
      backgroundColor: theme.tabBarBg,
      borderStyle: 'white'
    })
  } catch (e) {}

  _updateNavBar(theme)
  _updateWindowBg(theme)

  try {
    uni.$emit('__themeChanged', name)
  } catch (e) {}
}

function _injectCssVars (theme, name) {
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
    root.style.setProperty('--page-bg', theme.pageBg)
  }
  // #endif

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
    '--c-tint-strong': theme.tintStrong,
    '--page-bg': theme.pageBg
  })
}

function _updateNavBar (theme) {
  try {
    uni.setNavigationBarColor({
      frontColor: '#ffffff',
      backgroundColor: theme.navBg,
      animation: { duration: 300, timingFunc: 'easeIn' }
    })
  } catch (e) {}
}

function _updateWindowBg (theme) {
  try {
    uni.setBackgroundColor({
      backgroundColor: theme.pageBg,
      backgroundColorTop: theme.pageBg,
      backgroundColorBottom: theme.pageBg
    })
  } catch (e) {}
}

function getTheme () {
  const name = uni.getStorageSync('theme') || DEFAULT_THEME
  return THEMES[name] || THEMES[DEFAULT_THEME]
}

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
