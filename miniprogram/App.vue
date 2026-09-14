<script>
import theme from '@/common/theme.js'

export default {
  globalData: {
    userInfo: null,
    isLogin: false,
    baseUrl: 'http://127.0.0.1:9901',
    token: '',
    systemInfo: null,
    currentTheme: 'green'
  },

  onLaunch() {
    console.log('[App] 应用启动')
    try {
      this.globalData.systemInfo = uni.getSystemInfoSync()
    } catch (e) {}

    const token = uni.getStorageSync('token')
    if (token) {
      this.globalData.token = token
      this.globalData.isLogin = true
      const userInfo = uni.getStorageSync('userInfo')
      if (userInfo) {
        this.globalData.userInfo = userInfo
      }
    }

    this.globalData.currentTheme = theme.loadTheme()
  },

  onShow() {
    console.log('[App] 应用显示')
  },

  onHide() {
    console.log('[App] 应用隐藏')
  }
}
</script>

<style>
@import './common/theme-overrides.wxss';
@import './common/professional.css';

page {
  background: #f0f7e6;
  font-size: 28rpx;
  color: #1a1a1a;
  font-family: -apple-system, BlinkMacSystemFont, 'Helvetica Neue', Helvetica, 'PingFang SC', 'SF Pro Text', 'Microsoft YaHei', Arial, sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
  letter-spacing: 0.3rpx;
  line-height: 1.55;

  --c-primary: #7cb342;
  --c-primary-dark: #558b2f;
  --c-primary-light: #9ccc65;
  --c-accent: #ffb300;
  --c-gradient-start: #9ccc65;
  --c-gradient-end: #558b2f;
  --c-rgb-primary: 124,179,66;
  --c-rgb-accent: 255,179,0;
  --c-tint-light: #f1f8e9;
  --c-tint-medium: #dcedc8;
  --c-tint-strong: #c5e1a5;
}
view, text, image, navigator, button, input, textarea, scroll-view {
  box-sizing: border-box;
}
button::after {
  border: none;
}
.container {
  padding: 24rpx;
}
.safe-area-bottom {
  padding-bottom: env(safe-area-inset-bottom);
}
.ellipsis {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
</style>
