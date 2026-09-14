import Vue from 'vue'
import App from './App'
import theme from '@/common/theme.js'
import { getThemeColors } from '@/common/theme-colors.js'
import http from '@/utils/http.js'

Vue.config.productionTip = false

// 全局配置缓存
var _appConfig = null;
var _configLoading = false;
var _configCallbacks = [];

function loadAppConfig(cb) {
  if (_appConfig) { cb(_appConfig); return; }
  if (_configCallbacks.length > 0) { _configCallbacks.push(cb); return; }
  _configCallbacks.push(cb);
  if (_configLoading) return;
  _configLoading = true;
  http.get('/api/settings/config', {}, { silent: true }).then(function(res) {
    _appConfig = res.code === 0 ? res.data : {};
    _configCallbacks.forEach(function(c) { try { c(_appConfig); } catch(e) {} });
    _configCallbacks = [];
    _configLoading = false;
  }).catch(function() {
    _appConfig = {};
    _configCallbacks.forEach(function(c) { try { c(_appConfig); } catch(e) {} });
    _configCallbacks = [];
    _configLoading = false;
  });
}

// 全局混入：为所有页面提供 tc 主题色 + 全局 pageSize
Vue.mixin({
  data () {
    return {
      activeTheme: uni.getStorageSync('theme') || 'green',
      pageSize: 10
    }
  },
  computed: {
    tc () {
      return getThemeColors(this.activeTheme)
    },
    tPri () { return 'color:' + this.tc.primary + ';' },
    tPriDk () { return 'color:' + this.tc.primaryDark + ';' },
    tBgPri () { return 'background:' + this.tc.primary + ';' },
    pageBgStyle () {
      return 'background:' + (this.tc.pageBg || '#f0f7e6') + ';'
    }
  },
  onLoad () {
    var self = this;
    uni.$on('__themeChanged', function(name) { self.activeTheme = name; });
    // 加载全局 pageSize
    loadAppConfig(function(cfg) {
      var ps = parseInt(cfg.app_page_size) || 10;
      if (ps > 0 && ps <= 100) self.pageSize = ps;
    });
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
    try {
      uni.setNavigationBarColor({
        frontColor: '#ffffff',
        backgroundColor: t.navBg,
        animation: { duration: 200, timingFunc: 'easeIn' }
      })
      uni.setTabBarStyle({
        color: t.tabBarColor,
        selectedColor: t.tabBarSelected,
        backgroundColor: t.tabBarBg,
        borderStyle: 'white'
      })
      uni.setBackgroundColor({
        backgroundColor: t.pageBg,
        backgroundColorTop: t.pageBg,
        backgroundColorBottom: t.pageBg
      })
    } catch (e) {}
  }
})

App.mpType = 'app'

const app = new Vue({
  ...App
})
app.$mount()
