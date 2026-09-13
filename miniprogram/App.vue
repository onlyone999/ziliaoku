<script>
import theme from '@/common/theme.js'

export default {
  globalData: {
    userInfo: null,
    isLogin: false,
    baseUrl: 'http://192.168.0.104:9901',
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

    // 加载用户主题
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
@import './common/premium.css';

page {
  background: linear-gradient(180deg, #f0f2f8 0%, #f5f6fa 15%, #f8f9fc 40%, #fafbfe 100%);
  font-size: 28rpx;
  color: #1a1a2e;
  font-family: -apple-system, BlinkMacSystemFont, 'Helvetica Neue', Helvetica, 'PingFang SC', 'SF Pro Display', 'Microsoft YaHei', Arial, sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
  letter-spacing: 0.3rpx;
  line-height: 1.6;

  /* 主题变量（默认绿，运行时被 JS 覆盖） */
  --c-primary: #2ed573;
  --c-primary-dark: #27ae60;
  --c-primary-light: #3be88a;
  --c-accent: #1abc9c;
  --c-gradient-start: #26c67a;
  --c-gradient-end: #1abc9c;
  --c-rgb-primary: 46,213,115;
  --c-rgb-accent: 26,188,156;
  --c-tint-light: #f0fdf4;
  --c-tint-medium: #d1fae5;
  --c-tint-strong: #a7f3d0;
}
view, text, image, navigator, button, input, textarea {
  box-sizing: border-box;
}
button::after {
  border: none;
}
.container {
  padding: 20rpx;
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
 
