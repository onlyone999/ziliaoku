const fs = require('fs');
const path = require('path');

const BASE = 'E:/ziliaoku/miniprogram/unpackage/dist/dev/mp-weixin';

function createPage(dir, name, title, wxml, wxss, js) {
    const pageDir = path.join(BASE, dir);
    if (!fs.existsSync(pageDir)) fs.mkdirSync(pageDir, { recursive: true });

    fs.writeFileSync(path.join(pageDir, name + '.json'), JSON.stringify({ navigationBarTitleText: title }, null, 2));
    fs.writeFileSync(path.join(pageDir, name + '.wxml'), wxml);
    fs.writeFileSync(path.join(pageDir, name + '.wxss'), wxss);
    fs.writeFileSync(path.join(pageDir, name + '.js'), js);
}

// Category list - full implementation
createPage('pages/category', 'list', '资源分类',
`<view class="page">
  <view class="sidebar">
    <scroll-view scroll-y class="sidebar-scroll">
      <view class="sidebar-item {{currentCatId === item.id ? 'active' : ''}}" wx:for="{{categories}}" wx:key="id" bindtap="selectCategory" data-id="{{item.id}}">
        <text>{{item.name}}</text>
      </view>
    </scroll-view>
  </view>
  <view class="content">
    <scroll-view scroll-x class="sub-tabs" wx:if="{{subCategories.length > 0}}">
      <view class="sub-tabs-inner">
        <view class="sub-tab {{currentSubId === 0 ? 'active' : ''}}" bindtap="selectSub" data-id="0">全部</view>
        <view class="sub-tab {{currentSubId === item.id ? 'active' : ''}}" wx:for="{{subCategories}}" wx:key="id" bindtap="selectSub" data-id="{{item.id}}">{{item.name}}</view>
      </view>
    </scroll-view>
    <scroll-view scroll-x class="sub-tabs sub-sub-tabs" wx:if="{{subSubCategories.length > 0}}">
      <view class="sub-tabs-inner">
        <view class="sub-tab small {{currentSubSubId === 0 ? 'active' : ''}}" bindtap="selectSubSub" data-id="0">全部</view>
        <view class="sub-tab small {{currentSubSubId === item.id ? 'active' : ''}}" wx:for="{{subSubCategories}}" wx:key="id" bindtap="selectSubSub" data-id="{{item.id}}">{{item.name}}</view>
      </view>
    </scroll-view>
    <scroll-view scroll-x class="sub-tabs sub-sub-tabs" wx:if="{{subSubSubCategories.length > 0}}">
      <view class="sub-tabs-inner">
        <view class="sub-tab tiny {{currentSubSubSubId === 0 ? 'active' : ''}}" bindtap="selectSubSubSub" data-id="0">全部</view>
        <view class="sub-tab tiny {{currentSubSubSubId === item.id ? 'active' : ''}}" wx:for="{{subSubSubCategories}}" wx:key="id" bindtap="selectSubSubSub" data-id="{{item.id}}">{{item.name}}</view>
      </view>
    </scroll-view>
    <scroll-view scroll-y class="resource-scroll" bindscrolltolower="loadMore">
      <view class="resource-card" wx:for="{{resources}}" wx:key="id" bindtap="goDetail" data-id="{{item.id}}">
        <image class="card-cover" src="{{item.cover_url}}" mode="aspectFill"></image>
        <view class="card-info">
          <text class="card-title">{{item.title}}</text>
          <view class="card-bottom">
            <text class="card-price" wx:if="{{item.price > 0}}">¥{{item.price}}</text>
            <text class="card-price free" wx:else>免费</text>
            <text class="card-downloads">↓{{item.download_count}}</text>
          </view>
        </view>
      </view>
      <view class="empty" wx:if="{{!loading && resources.length === 0}}"><text>📦 暂无资源</text></view>
      <view class="load-status" wx:if="{{loading}}"><text>加载中...</text></view>
    </scroll-view>
  </view>
</view>`,
`.page { display: flex; height: 100vh; background: #f5f7fa; }
.sidebar { width: 180rpx; background: #fff; flex-shrink: 0; border-right: 1rpx solid #eee; }
.sidebar-scroll { height: 100%; }
.sidebar-item { padding: 30rpx 20rpx; text-align: center; font-size: 26rpx; color: #666; }
.sidebar-item.active { color: #2ed573; font-weight: 700; background: #f0eeff; }
.content { flex: 1; display: flex; flex-direction: column; }
.sub-tabs { white-space: nowrap; background: #fff; padding: 16rpx 0; border-bottom: 1rpx solid #f0f0f0; }
.sub-tabs-inner { display: inline-flex; padding: 0 16rpx; }
.sub-tab { padding: 10rpx 24rpx; margin: 0 8rpx; font-size: 24rpx; color: #666; background: #f5f5f5; border-radius: 24rpx; display: inline-block; }
.sub-tab.active { color: #fff; background: #2ed573; }
.sub-sub-tabs { background: #f8f9fc; }
.sub-tab.small { padding: 8rpx 18rpx; font-size: 22rpx; border-radius: 20rpx; }
.sub-tab.tiny { padding: 6rpx 14rpx; font-size: 20rpx; border-radius: 16rpx; }
.resource-scroll { flex: 1; padding: 16rpx; }
.resource-card { display: flex; background: #fff; border-radius: 12rpx; margin-bottom: 16rpx; overflow: hidden; }
.card-cover { width: 200rpx; height: 160rpx; flex-shrink: 0; }
.card-info { flex: 1; padding: 16rpx; display: flex; flex-direction: column; justify-content: space-between; }
.card-title { font-size: 28rpx; color: #333; font-weight: 600; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.card-bottom { display: flex; justify-content: space-between; }
.card-price { font-size: 28rpx; color: #ff6b6b; font-weight: 700; }
.card-price.free { color: #2ed573; font-size: 22rpx; }
.card-downloads { font-size: 20rpx; color: #999; }
.empty { text-align: center; padding: 100rpx 0; color: #999; }
.load-status { text-align: center; padding: 30rpx 0; color: #999; }`,
`var app = getApp();
var BASE_URL = app.globalData.baseUrl;
Page({
  data: {
    categories: [], subCategories: [], subSubCategories: [], subSubSubCategories: [],
    currentCatId: 0, currentSubId: 0, currentSubSubId: 0, currentSubSubSubId: 0,
    resources: [], loading: false, page: 1, noMore: false
  },
  onLoad: function() { this.loadCategories(); },
  loadCategories: function() {
    var that = this;
    wx.request({
      url: BASE_URL + '/api/category/list',
      success: function(res) {
        if (res.data.code === 0 && res.data.data) {
          var cats = res.data.data.filter(function(c) { return c.parent_id === 0; });
          that.setData({ categories: cats });
          if (cats.length > 0) {
            that.setData({ currentCatId: cats[0].id });
            that.loadSub(cats[0].id);
            that.loadResources();
          }
        }
      }
    });
  },
  loadSub: function(pid) {
    var that = this;
    wx.request({
      url: BASE_URL + '/api/category/sub?parent_id=' + pid,
      success: function(res) {
        that.setData({ subCategories: res.data.code === 0 ? (res.data.data || []) : [], currentSubId: 0, subSubCategories: [], subSubSubCategories: [] });
      }
    });
  },
  loadSubSub: function(pid) {
    var that = this;
    wx.request({
      url: BASE_URL + '/api/category/sub?parent_id=' + pid,
      success: function(res) {
        that.setData({ subSubCategories: res.data.code === 0 ? (res.data.data || []) : [], currentSubSubId: 0, subSubSubCategories: [] });
      }
    });
  },
  loadSubSubSub: function(pid) {
    var that = this;
    wx.request({
      url: BASE_URL + '/api/category/sub?parent_id=' + pid,
      success: function(res) {
        that.setData({ subSubSubCategories: res.data.code === 0 ? (res.data.data || []) : [], currentSubSubSubId: 0 });
      }
    });
  },
  selectCategory: function(e) {
    var id = e.currentTarget.dataset.id;
    this.setData({ currentCatId: id, currentSubId: 0, currentSubSubId: 0, currentSubSubSubId: 0, resources: [], page: 1, noMore: false });
    this.loadSub(id);
    this.loadResources();
  },
  selectSub: function(e) {
    var id = e.currentTarget.dataset.id;
    this.setData({ currentSubId: id, currentSubSubId: 0, currentSubSubSubId: 0, subSubCategories: [], subSubSubCategories: [], resources: [], page: 1, noMore: false });
    if (id > 0) this.loadSubSub(id);
    this.loadResources();
  },
  selectSubSub: function(e) {
    var id = e.currentTarget.dataset.id;
    this.setData({ currentSubSubId: id, currentSubSubSubId: 0, subSubSubCategories: [], resources: [], page: 1, noMore: false });
    if (id > 0) this.loadSubSubSub(id);
    this.loadResources();
  },
  selectSubSubSub: function(e) {
    var id = e.currentTarget.dataset.id;
    this.setData({ currentSubSubSubId: id, resources: [], page: 1, noMore: false });
    this.loadResources();
  },
  loadResources: function() {
    if (this.data.loading || this.data.noMore) return;
    var that = this;
    that.setData({ loading: true });
    var cid = this.data.currentCatId;
    if (this.data.currentSubSubSubId > 0) cid = this.data.currentSubSubSubId;
    else if (this.data.currentSubSubId > 0) cid = this.data.currentSubSubId;
    else if (this.data.currentSubId > 0) cid = this.data.currentSubId;
    wx.request({
      url: BASE_URL + '/api/resource/list?category_id=' + cid + '&page=' + this.data.page + '&page_size=15',
      success: function(res) {
        if (res.data.code === 0) {
          var list = res.data.data.list || res.data.data || [];
          that.setData({
            resources: that.data.page === 1 ? list : that.data.resources.concat(list),
            page: that.data.page + 1,
            noMore: list.length < 15
          });
        }
        that.setData({ loading: false });
      },
      fail: function() { that.setData({ loading: false }); }
    });
  },
  loadMore: function() { this.loadResources(); },
  goDetail: function(e) { wx.navigateTo({ url: '/pages/resource/detail?id=' + e.currentTarget.dataset.id }); }
});`
);

// Resource detail - full implementation
createPage('pages/resource', 'detail', '资源详情',
`<view class="page">
  <view wx:if="{{pageLoading}}" class="loading"><text>加载中...</text></view>
  <view wx:elif="{{loadError}}" class="error"><text>{{errorMsg}}</text><button bindtap="retry">重试</button></view>
  <block wx:else>
    <image class="cover" src="{{resource.cover_url}}" mode="aspectFill"></image>
    <view class="info">
      <text class="title">{{resource.title}}</text>
      <view class="stats">
        <text>👁 {{resource.view_count}}</text>
        <text>↓ {{resource.download_count}}</text>
        <text>❤ {{resource.like_count}}</text>
      </view>
      <view class="price-box">
        <text wx:if="{{resource.price_type === 'free'}}" class="free">免费</text>
        <text wx:elif="{{resource.price_type === 'paid'}}" class="paid">¥{{resource.price}}</text>
        <text wx:else class="member">会员免费</text>
      </view>
      <view class="file-box"><text>📁 {{resource.file_type}} · {{fileSizeText}}</text></view>
      <view class="tags" wx:if="{{resource.tags && resource.tags.length > 0}}">
        <text class="tag" wx:for="{{resource.tags}}" wx:key="id">{{item.name}}</text>
      </view>
      <view class="desc"><text>{{resource.description}}</text></view>
    </view>
    <view class="bottom-bar">
      <button class="fav-btn" bindtap="toggleFav">{{isFav ? '❤️' : '🤍'}}</button>
      <button class="share-btn" open-type="share">分享</button>
      <button class="dl-btn" bindtap="download">下载资源</button>
    </view>
  </block>
</view>`,
`.page { background: #f5f7fa; min-height: 100vh; padding-bottom: 120rpx; }
.loading, .error { text-align: center; padding: 100rpx 0; color: #999; }
.cover { width: 100%; height: 400rpx; }
.info { padding: 30rpx; background: #fff; }
.title { font-size: 34rpx; font-weight: 700; color: #333; display: block; margin-bottom: 16rpx; }
.stats { display: flex; gap: 30rpx; color: #999; font-size: 24rpx; margin-bottom: 20rpx; }
.price-box { margin-bottom: 20rpx; }
.free { font-size: 36rpx; font-weight: 700; color: #2ed573; }
.paid { font-size: 36rpx; font-weight: 700; color: #ff6b6b; }
.member { font-size: 36rpx; font-weight: 700; color: #2ed573; }
.file-box { background: #f0f2f5; padding: 16rpx; border-radius: 8rpx; margin-bottom: 20rpx; font-size: 24rpx; color: #666; }
.tags { display: flex; flex-wrap: wrap; gap: 10rpx; margin-bottom: 20rpx; }
.tag { background: #2ed57320; color: #2ed573; padding: 6rpx 16rpx; border-radius: 16rpx; font-size: 22rpx; }
.desc { font-size: 28rpx; color: #666; line-height: 1.6; }
.bottom-bar { position: fixed; bottom: 0; left: 0; right: 0; display: flex; align-items: center; padding: 16rpx 30rpx; background: #fff; border-top: 1rpx solid #eee; }
.fav-btn, .share-btn { flex: 0 0 80rpx; font-size: 36rpx; text-align: center; background: none; border: none; padding: 0; margin: 0; line-height: 1; }
.dl-btn { flex: 1; background: #2ed573; color: #fff; border-radius: 40rpx; font-size: 28rpx; margin-left: 16rpx; }
.dl-btn::after { border: none; }`,
`var app = getApp();
var BASE_URL = app.globalData.baseUrl;
Page({
  data: { resource: {}, pageLoading: true, loadError: false, errorMsg: '', isFav: false, fileSizeText: '' },
  onLoad: function(options) {
    this.id = options.id;
    this.loadDetail();
  },
  loadDetail: function() {
    var that = this;
    that.setData({ pageLoading: true, loadError: false });
    wx.request({
      url: BASE_URL + '/api/resource/detail?id=' + this.id,
      success: function(res) {
        if (res.data.code === 0) {
          var r = res.data.data;
          r.file_size_text = that.formatSize(r.file_size);
          that.setData({ resource: r, isFav: !!r.is_favorite, fileSizeText: that.formatSize(r.file_size), pageLoading: false });
          wx.setNavigationBarTitle({ title: r.title || '资源详情' });
        } else {
          that.setData({ loadError: true, errorMsg: res.data.message || '加载失败', pageLoading: false });
        }
      },
      fail: function() { that.setData({ loadError: true, errorMsg: '网络异常', pageLoading: false }); }
    });
  },
  retry: function() { this.loadDetail(); },
  formatSize: function(bytes) {
    if (!bytes) return '0B';
    var units = ['B', 'KB', 'MB', 'GB'];
    var i = 0;
    while (bytes >= 1024 && i < 3) { bytes /= 1024; i++; }
    return bytes.toFixed(1) + units[i];
  },
  download: function() {
    var token = wx.getStorageSync('token');
    if (!token) { wx.showToast({ title: '请先登录', icon: 'none' }); return; }
    var that = this;
    wx.showLoading({ title: '处理中...' });
    wx.request({
      url: BASE_URL + '/api/download/download',
      method: 'POST',
      data: { resource_id: parseInt(this.id) },
      header: { 'Authorization': 'Bearer ' + token, 'Content-Type': 'application/json' },
      success: function(res) {
        wx.hideLoading();
        if (res.data.code === 0 && res.data.data && res.data.data.file_url) {
          var url = res.data.data.file_url;
          if (url.indexOf('http') !== 0) url = BASE_URL + url;
          wx.downloadFile({
            url: url,
            success: function(r) {
              wx.openDocument({ filePath: r.tempFilePath, showMenu: true });
            },
            fail: function() { wx.showToast({ title: '下载失败', icon: 'none' }); }
          });
        } else {
          wx.showToast({ title: res.data.message || '下载失败', icon: 'none' });
        }
      },
      fail: function() { wx.hideLoading(); wx.showToast({ title: '网络异常', icon: 'none' }); }
    });
  },
  toggleFav: function() {
    var token = wx.getStorageSync('token');
    if (!token) { wx.showToast({ title: '请先登录', icon: 'none' }); return; }
    var that = this;
    wx.request({
      url: BASE_URL + '/api/favorite/toggle',
      method: 'POST',
      data: { resource_id: parseInt(this.id) },
      header: { 'Authorization': 'Bearer ' + token, 'Content-Type': 'application/json' },
      success: function(res) {
        if (res.data.code === 0) {
          that.setData({ isFav: res.data.data.is_favorite });
          wx.showToast({ title: res.data.data.is_favorite ? '已收藏' : '已取消收藏', icon: 'none' });
        }
      }
    });
  },
  onShareAppMessage: function() {
    return { title: this.data.resource.title, path: '/pages/resource/detail?id=' + this.id };
  }
});`
);

// Search page
createPage('pages/resource', 'search', '搜索资源',
`<view class="page">
  <view class="search-bar">
    <input class="search-input" placeholder="搜索资源" value="{{keyword}}" bindinput="onInput" bindconfirm="doSearch" focus/>
    <text class="search-btn" bindtap="doSearch">搜索</text>
  </view>
  <view wx:if="{{results.length === 0}}" class="hot-section">
    <text class="section-title">热门搜索</text>
    <view class="keyword-list">
      <text class="keyword" wx:for="{{hotKeywords}}" wx:key="*this" bindtap="tapKw" data-kw="{{item}}">{{item}}</text>
    </view>
  </view>
  <view wx:if="{{results.length > 0}}" class="results">
    <view class="result-card" wx:for="{{results}}" wx:key="id" bindtap="goDetail" data-id="{{item.id}}">
      <image class="result-cover" src="{{item.cover_url}}" mode="aspectFill"></image>
      <view class="result-info">
        <text class="result-title">{{item.title}}</text>
        <view class="result-bottom">
          <text wx:if="{{item.price > 0}}" class="result-price">¥{{item.price}}</text>
          <text wx:else class="result-price free">免费</text>
          <text class="result-downloads">↓{{item.download_count}}</text>
        </view>
      </view>
    </view>
  </view>
  <view wx:if="{{searched && results.length === 0}}" class="empty"><text>🔍 未找到相关资源</text></view>
</view>`,
`.page { background: #f5f7fa; min-height: 100vh; }
.search-bar { display: flex; align-items: center; padding: 20rpx; background: #fff; }
.search-input { flex: 1; background: #f5f5f5; border-radius: 30rpx; padding: 16rpx 24rpx; font-size: 28rpx; }
.search-btn { margin-left: 16rpx; color: #2ed573; font-size: 28rpx; }
.hot-section { padding: 30rpx; }
.section-title { font-size: 28rpx; font-weight: 700; color: #333; margin-bottom: 16rpx; display: block; }
.keyword-list { display: flex; flex-wrap: wrap; gap: 16rpx; }
.keyword { background: #f5f5f5; padding: 10rpx 24rpx; border-radius: 20rpx; font-size: 24rpx; color: #666; }
.results { padding: 20rpx; }
.result-card { display: flex; background: #fff; border-radius: 12rpx; margin-bottom: 16rpx; overflow: hidden; }
.result-cover { width: 180rpx; height: 140rpx; flex-shrink: 0; }
.result-info { flex: 1; padding: 16rpx; display: flex; flex-direction: column; justify-content: space-between; }
.result-title { font-size: 28rpx; color: #333; font-weight: 600; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.result-bottom { display: flex; justify-content: space-between; }
.result-price { font-size: 26rpx; color: #ff6b6b; font-weight: 700; }
.result-price.free { color: #2ed573; font-size: 22rpx; }
.result-downloads { font-size: 20rpx; color: #999; }
.empty { text-align: center; padding: 100rpx 0; color: #999; }`,
`var app = getApp();
var BASE_URL = app.globalData.baseUrl;
Page({
  data: { keyword: '', results: [], hotKeywords: ['模板', '源码', '教程', 'PPT', '设计', 'Vue', '小程序', 'Python'], searched: false },
  onInput: function(e) { this.setData({ keyword: e.detail.value }); },
  doSearch: function() {
    if (!this.data.keyword) return;
    var that = this;
    wx.request({
      url: BASE_URL + '/api/resource/search?keyword=' + encodeURIComponent(this.data.keyword),
      success: function(res) {
        if (res.data.code === 0) {
          that.setData({ results: res.data.data.list || [], searched: true });
        }
      }
    });
  },
  tapKw: function(e) {
    this.setData({ keyword: e.currentTarget.dataset.kw });
    this.doSearch();
  },
  goDetail: function(e) {
    wx.navigateTo({ url: '/pages/resource/detail?id=' + e.currentTarget.dataset.id });
  }
});`
);

// User page - full implementation
createPage('pages/user', 'index', '我的',
`<view class="page">
  <view wx:if="{{isLoggedIn}}" class="user-card">
    <view class="user-bg"></view>
    <view class="user-info">
      <image class="avatar" src="{{userInfo.avatar_url || '/static/default-avatar.png'}}"></image>
      <view class="user-meta">
        <text class="nickname">{{userInfo.nickname || '用户'}}</text>
        <text class="uid">ID: {{userInfo.id}}</text>
      </view>
    </view>
  </view>
  <view wx:else class="login-card">
    <text class="login-title">登录后享受更多功能</text>
    <button class="login-btn" bindtap="goLogin">立即登录</button>
  </view>
  <view class="menu-list">
    <view class="menu-item" bindtap="goPage" data-url="/pages/user/downloads"><text>📥 我的下载</text><text class="arrow">›</text></view>
    <view class="menu-item" bindtap="goPage" data-url="/pages/user/favorites"><text>❤️ 我的收藏</text><text class="arrow">›</text></view>
    <view class="menu-item" bindtap="goPage" data-url="/pages/user/orders"><text>📋 我的订单</text><text class="arrow">›</text></view>
    <view class="menu-item" bindtap="goPage" data-url="/pages/user/vip"><text>⭐ VIP会员</text><text class="arrow">›</text></view>
    <view class="menu-item" bindtap="goPage" data-url="/pages/user/feedback"><text>💬 意见反馈</text><text class="arrow">›</text></view>
  </view>
</view>`,
`.page { background: #f5f7fa; min-height: 100vh; }
.user-card { position: relative; padding: 60rpx 30rpx 30rpx; }
.user-bg { position: absolute; top: 0; left: 0; right: 0; height: 200rpx; background: linear-gradient(135deg, #4A90D9, #2ed573); }
.user-info { position: relative; display: flex; align-items: center; }
.avatar { width: 120rpx; height: 120rpx; border-radius: 50%; border: 4rpx solid #fff; margin-right: 24rpx; }
.user-meta { color: #fff; }
.nickname { font-size: 34rpx; font-weight: 700; display: block; }
.uid { font-size: 24rpx; opacity: 0.8; }
.login-card { padding: 60rpx 30rpx; text-align: center; }
.login-title { font-size: 28rpx; color: #666; margin-bottom: 30rpx; display: block; }
.login-btn { background: #2ed573; color: #fff; border-radius: 40rpx; font-size: 28rpx; }
.login-btn::after { border: none; }
.menu-list { margin: 20rpx; background: #fff; border-radius: 16rpx; overflow: hidden; }
.menu-item { display: flex; justify-content: space-between; align-items: center; padding: 30rpx; border-bottom: 1rpx solid #f5f5f5; font-size: 28rpx; }
.arrow { color: #ccc; font-size: 32rpx; }`,
`var app = getApp();
var BASE_URL = app.globalData.baseUrl;
Page({
  data: { isLoggedIn: false, userInfo: {} },
  onShow: function() { this.checkLogin(); },
  checkLogin: function() {
    var token = wx.getStorageSync('token');
    if (token) {
      this.setData({ isLoggedIn: true });
      this.loadUserInfo();
    } else {
      this.setData({ isLoggedIn: false, userInfo: {} });
    }
  },
  loadUserInfo: function() {
    var that = this;
    var token = wx.getStorageSync('token');
    wx.request({
      url: BASE_URL + '/api/user/info',
      header: { 'Authorization': 'Bearer ' + token },
      success: function(res) {
        if (res.data.code === 0) that.setData({ userInfo: res.data.data || {} });
      }
    });
  },
  goLogin: function() {
    var that = this;
    wx.login({
      success: function(loginRes) {
        wx.request({
          url: BASE_URL + '/api/auth/wx-login',
          method: 'POST',
          data: { code: loginRes.code },
          header: { 'Content-Type': 'application/json' },
          success: function(res) {
            if (res.data.code === 0) {
              wx.setStorageSync('token', res.data.data.token);
              if (res.data.data.user) wx.setStorageSync('userInfo', JSON.stringify(res.data.data.user));
              that.checkLogin();
              wx.showToast({ title: '登录成功', icon: 'success' });
            } else {
              wx.showToast({ title: res.data.message || '登录失败', icon: 'none' });
            }
          },
          fail: function() { wx.showToast({ title: '网络异常', icon: 'none' }); }
        });
      }
    });
  },
  goPage: function(e) {
    if (!this.data.isLoggedIn) {
      var that = this;
      wx.showModal({ title: '提示', content: '请先登录', success: function(r) { if (r.confirm) that.goLogin(); } });
      return;
    }
    wx.navigateTo({ url: e.currentTarget.dataset.url });
  }
});`
);

// Simple placeholder pages
var simplePages = [
    { dir: 'pages/category', name: 'detail', title: '分类详情' },
    { dir: 'pages/user', name: 'orders', title: '我的订单' },
    { dir: 'pages/user', name: 'downloads', title: '下载记录' },
    { dir: 'pages/user', name: 'favorites', title: '我的收藏' },
    { dir: 'pages/user', name: 'vip', title: 'VIP会员' },
    { dir: 'pages/user', name: 'feedback', title: '意见反馈' },
    { dir: 'pages/pay', name: 'result', title: '支付结果' },
    { dir: 'pages/webview', name: 'index', title: '网页浏览' }
];

simplePages.forEach(function(p) {
    createPage(p.dir, p.name, p.title,
        '<view class="page"><view class="coming"><text class="icon">🚧</text><text class="text">' + p.title + '开发中...</text></view></view>',
        '.page { display: flex; align-items: center; justify-content: center; height: 100vh; background: #f5f7fa; }\n.coming { text-align: center; }\n.icon { font-size: 80rpx; display: block; margin-bottom: 20rpx; }\n.text { font-size: 28rpx; color: #999; }',
        'Page({})'
    );
});

console.log('All pages created!');
