const fs = require('fs');
const BASE = 'E:/ziliaoku/miniprogram/unpackage/dist/dev/mp-weixin';

function w(f, content) {
    const dir = require('path').dirname(f);
    if (!fs.existsSync(dir)) fs.mkdirSync(dir, { recursive: true });
    fs.writeFileSync(f, content);
}

const reqFn = `var app = getApp();
var BASE_URL = app.globalData.baseUrl;
function req(url, method, data) {
  return new Promise(function(resolve, reject) {
    var token = wx.getStorageSync('token');
    wx.request({
      url: BASE_URL + url, method: method || 'GET', data: data || {},
      header: token ? { 'Authorization': 'Bearer ' + token } : {},
      success: function(r) { resolve(r.data); }, fail: reject
    });
  });
}
`;

// Orders
w(BASE + '/pages/user/orders.wxss', `.page{background:#f5f7fa;min-height:100vh}.tabs{display:flex;background:#fff;border-bottom:1rpx solid #eee}.tab{flex:1;text-align:center;padding:24rpx 0;font-size:26rpx;color:#666}.tab.active{color:#2ed573;font-weight:700;border-bottom:4rpx solid #2ed573}.list{padding:20rpx}.order-card{background:#fff;border-radius:12rpx;margin-bottom:16rpx;overflow:hidden}.order-header{display:flex;justify-content:space-between;padding:16rpx 20rpx;border-bottom:1rpx solid #f5f5f5;font-size:22rpx}.order-no{color:#999}.order-status{color:#2ed573;font-weight:600}.order-body{display:flex;justify-content:space-between;align-items:center;padding:20rpx}.order-title{font-size:28rpx;color:#333;flex:1}.order-amount{font-size:28rpx;color:#ff6b6b;font-weight:700}.empty{text-align:center;padding:100rpx 0;color:#999}`);

w(BASE + '/pages/user/orders.js', reqFn + `
Page({
  data: { tab: 'all', orders: [] },
  onLoad: function() { this.loadOrders(); },
  switchTab: function(e) {
    this.setData({ tab: e.currentTarget.dataset.tab });
    this.loadOrders();
  },
  loadOrders: function() {
    var that = this;
    var url = '/api/order/list';
    if (that.data.tab !== 'all') url += '?status=' + that.data.tab;
    req(url).then(function(res) {
      if (res.code === 0) {
        var list = res.data.list || res.data || [];
        var statusMap = { pending: '待支付', paid: '已完成', cancelled: '已取消', refunded: '已退款' };
        list.forEach(function(item) { item.status_text = statusMap[item.status] || item.status; });
        that.setData({ orders: list });
      }
    }).catch(function(){});
  }
});`);

// Downloads
w(BASE + '/pages/user/downloads.wxss', `.page{background:#f5f7fa;min-height:100vh}.list{padding:20rpx}.item{display:flex;align-items:center;background:#fff;border-radius:12rpx;padding:20rpx;margin-bottom:16rpx}.item-cover{width:120rpx;height:100rpx;border-radius:8rpx;margin-right:16rpx;flex-shrink:0}.item-info{flex:1}.item-title{font-size:28rpx;color:#333;font-weight:600;display:block;margin-bottom:8rpx}.item-time{font-size:22rpx;color:#999}.empty{text-align:center;padding:100rpx 0;color:#999}`);

w(BASE + '/pages/user/downloads.js', reqFn + `
Page({
  data: { downloads: [] },
  onLoad: function() { this.loadDownloads(); },
  loadDownloads: function() {
    var that = this;
    req('/api/user/downloads').then(function(res) {
      if (res.code === 0) that.setData({ downloads: res.data.list || res.data || [] });
    }).catch(function(){});
  },
  goDetail: function(e) { wx.navigateTo({ url: '/pages/resource/detail?id=' + e.currentTarget.dataset.id }); }
});`);

// Favorites
w(BASE + '/pages/user/favorites.wxss', `.page{background:#f5f7fa;min-height:100vh}.list{padding:20rpx}.item{display:flex;align-items:center;background:#fff;border-radius:12rpx;padding:20rpx;margin-bottom:16rpx}.item-cover{width:120rpx;height:100rpx;border-radius:8rpx;margin-right:16rpx;flex-shrink:0}.item-info{flex:1}.item-title{font-size:28rpx;color:#333;font-weight:600;display:block;margin-bottom:8rpx}.item-price{font-size:26rpx;color:#ff6b6b;font-weight:700}.item-price.free{color:#2ed573}.empty{text-align:center;padding:100rpx 0;color:#999}`);

w(BASE + '/pages/user/favorites.js', reqFn + `
Page({
  data: { favorites: [] },
  onLoad: function() { this.loadFavorites(); },
  loadFavorites: function() {
    var that = this;
    req('/api/favorite/list').then(function(res) {
      if (res.code === 0) that.setData({ favorites: res.data.list || res.data || [] });
    }).catch(function(){});
  },
  goDetail: function(e) { wx.navigateTo({ url: '/pages/resource/detail?id=' + e.currentTarget.dataset.id }); }
});`);

// Feedback
w(BASE + '/pages/user/feedback.wxss', `.page{background:#f5f7fa;min-height:100vh}.form-card{margin:20rpx;background:#fff;border-radius:16rpx;padding:30rpx}.form-title{font-size:32rpx;font-weight:700;color:#333;display:block;margin-bottom:30rpx}.form-group{margin-bottom:24rpx}.label{font-size:26rpx;color:#666;display:block;margin-bottom:12rpx}.type-list{display:flex;gap:16rpx}.type-item{padding:12rpx 24rpx;background:#f5f5f5;border-radius:24rpx;font-size:24rpx}.type-item.active{background:#2ed573;color:#fff}.textarea{width:100%;height:200rpx;background:#f5f5f5;border-radius:12rpx;padding:16rpx;font-size:28rpx;box-sizing:border-box}.input{width:100%;height:80rpx;background:#f5f5f5;border-radius:12rpx;padding:0 16rpx;font-size:28rpx;box-sizing:border-box}.submit-btn{background:#2ed573;color:#fff;border-radius:40rpx;margin-top:30rpx}.submit-btn::after{border:none}`);

w(BASE + '/pages/user/feedback.js', reqFn + `
Page({
  data: { type: 'bug', content: '', contact: '' },
  setType: function(e) { this.setData({ type: e.currentTarget.dataset.type }); },
  onContent: function(e) { this.setData({ content: e.detail.value }); },
  onContact: function(e) { this.setData({ contact: e.detail.value }); },
  submit: function() {
    if (!this.data.content) { wx.showToast({ title: '请填写内容', icon: 'none' }); return; }
    req('/api/feedback/submit', 'POST', { type: this.data.type, content: this.data.content, contact: this.data.contact }).then(function(res) {
      if (res.code === 0) { wx.showToast({ title: '提交成功', icon: 'success' }); setTimeout(function(){ wx.navigateBack(); }, 1500); }
      else { wx.showToast({ title: res.message || '提交失败', icon: 'none' }); }
    });
  }
});`);

// VIP
w(BASE + '/pages/user/vip.wxss', `.page{background:#f5f7fa;min-height:100vh;padding-bottom:120rpx}.vip-header{background:linear-gradient(135deg,#1a1a2e,#16213e);padding:60rpx 30rpx 40rpx;text-align:center;color:#fff}.vip-icon{font-size:60rpx;display:block;margin-bottom:16rpx}.vip-title{font-size:36rpx;font-weight:700;display:block;margin-bottom:8rpx}.vip-desc{font-size:24rpx;opacity:0.8}.plans{padding:30rpx}.section-title{font-size:28rpx;font-weight:700;color:#333;margin-bottom:16rpx;display:block}.plan-card{display:flex;justify-content:space-between;align-items:center;background:#fff;border-radius:12rpx;padding:24rpx;margin-bottom:16rpx;border:2rpx solid transparent}.plan-card.active{border-color:#2ed573;background:#f0eeff}.plan-name{font-size:28rpx;color:#333;font-weight:600;display:block}.plan-days{font-size:22rpx;color:#999}.plan-price{font-size:32rpx;color:#ff6b6b;font-weight:700}.buy-btn{position:fixed;bottom:30rpx;left:30rpx;right:30rpx;background:linear-gradient(135deg,#2ed573,#4A90D9);color:#fff;border-radius:40rpx;font-size:30rpx}.buy-btn::after{border:none}`);

w(BASE + '/pages/user/vip.js', reqFn + `
Page({
  data: { isVip: false, vipExpire: '', plans: [], selectedPlan: 0 },
  onLoad: function() { this.loadPlans(); this.loadVipInfo(); },
  loadPlans: function() {
    var that = this;
    req('/api/vip/plans').then(function(res) {
      if (res.code === 0) that.setData({ plans: res.data || [] });
    }).catch(function(){});
  },
  loadVipInfo: function() {
    var that = this;
    req('/api/user/vipStatus').then(function(res) {
      if (res.code === 0 && res.data) that.setData({ isVip: !!res.data.is_vip, vipExpire: res.data.vip_expire_at || '' });
    }).catch(function(){});
  },
  selectPlan: function(e) { this.setData({ selectedPlan: e.currentTarget.dataset.id }); },
  buyVip: function() {
    if (!this.data.selectedPlan) return;
    var token = wx.getStorageSync('token');
    if (!token) { wx.showToast({ title: '请先登录', icon: 'none' }); return; }
    wx.showLoading({ title: '创建订单...' });
    req('/api/vip/createOrder', 'POST', { plan_id: this.data.selectedPlan }).then(function(res) {
      wx.hideLoading();
      if (res.code === 0) wx.showToast({ title: '订单已创建', icon: 'success' });
      else wx.showToast({ title: res.message || '创建失败', icon: 'none' });
    }).catch(function(){ wx.hideLoading(); });
  }
});`);

// Pay result
w(BASE + '/pages/pay/result.wxss', `.page{display:flex;align-items:center;justify-content:center;height:100vh;background:#f5f7fa}.result-card{text-align:center;padding:60rpx}.result-icon{font-size:80rpx;display:block;margin-bottom:20rpx}.result-title{font-size:36rpx;font-weight:700;color:#333;display:block;margin-bottom:12rpx}.result-actions{margin-top:40rpx}.btn-primary{background:#2ed573;color:#fff;border-radius:40rpx;margin-bottom:16rpx}.btn-primary::after{border:none}.btn-outline{background:#fff;color:#666;border-radius:40rpx;border:1rpx solid #ddd}.btn-outline::after{border:none}`);

w(BASE + '/pages/pay/result.js', `Page({
  data: { success: false },
  onLoad: function(options) { this.setData({ success: options.status === 'success' }); },
  goDownload: function() { wx.navigateBack(); },
  goBack: function() { wx.navigateBack(); }
});`);

// Category detail placeholder
w(BASE + '/pages/category/detail.wxml', '<view class="page"><view class="coming"><text>🚧 分类详情</text></view></view>');
w(BASE + '/pages/category/detail.wxss', '.page{display:flex;align-items:center;justify-content:center;height:100vh;background:#f5f7fa}.coming{text-align:center;color:#999;font-size:28rpx}');
w(BASE + '/pages/category/detail.js', 'Page({})');

// Webview
w(BASE + '/pages/webview/index.js', `Page({
  data: { url: '' },
  onLoad: function(options) { this.setData({ url: decodeURIComponent(options.url || '') }); }
});`);
w(BASE + '/pages/webview/index.wxss', '');

console.log('All pages fixed!');
