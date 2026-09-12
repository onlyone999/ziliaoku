import Vue from 'vue'
import App from './App'
import theme from '@/common/theme.js'
import { getThemeColors } from '@/common/theme-colors.js'

Vue.config.productionTip = false

// 全局混入：为所有页面提供 tc 主题色计算属性 + 事件同步
Vue.mixin({
  data () {
    return {
      activeTheme: uni.getStorageSync('theme') || 'green'
    }
  },
  computed: {
    tc () {
      return getThemeColors(this.activeTheme)
    },
    // 快捷样式字符串，减少模板中的重复拼接
    tPri () { return 'color:' + this.tc.primary + ';' },
    tPriDk () { return 'color:' + this.tc.primaryDark + ';' },
    tBgPri () { return 'background:' + this.tc.primary + ';' }
  },
  onLoad () {
    uni.$on('__themeChanged', (name) => {
      this.activeTheme = name
    })
  },
  onUnload () {
    uni.$off('__themeChanged')
  },
  onShow () {
    const current = uni.getStorageSync('theme') || 'green'
    if (this.activeTheme !== current) {
      this.activeTheme = current
    }
    const t = theme.getTheme()
    console.log('[Theme] onShow → navBg:', t.navBg, 'selected:', t.tabBarSelected)
    try {
      uni.setNavigationBarColor({
        frontColor: '#ffffff',
        backgroundColor: t.navBg,
        animation: { duration: 200, timingFunc: 'easeIn' }
      })
      uni.setTabBarStyle({ selectedColor: t.tabBarSelected })
    } catch (e) {
      console.error('[Theme] setNavigationBarColor error:', e)
    }
  }
})

App.mpType = 'app'

const app = new Vue({
  ...App
})
app.$mount()
