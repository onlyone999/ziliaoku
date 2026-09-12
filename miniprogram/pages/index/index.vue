<template>
	<view class="page">
		<!-- 紫色渐变顶部背景 -->
		<view class="header-bg" :style="'background:' + tc.headerGradient + ';'"></view>

		<!-- 轮播图（与header-bg同层，z-index生效） -->
		<swiper
			v-if="banners.length > 0"
			class="banner-swiper"
			:indicator-dots="true"
			indicator-color="rgba(255,255,255,0.4)"
			indicator-active-color="#ffffff"
			:autoplay="true"
			:interval="4000"
			:circular="true"
		>
			<swiper-item v-for="item in banners" :key="item.id" @tap="onBannerTap(item)">
				<image class="banner-img" :src="item.image_url" mode="aspectFill"></image>
			</swiper-item>
		</swiper>

		<!-- 骨架屏: 轮播图 -->
		<view class="skeleton-banner" v-if="loading && banners.length === 0">
			<view class="skeleton-block banner-block"></view>
		</view>

		<!-- 搜索栏 -->
		<view class="search-bar" @tap="goSearch">
			<view class="search-input">
				🔍
				<text class="placeholder">搜索资源、模板、素材...</text>
			</view>
		</view>

		<scroll-view
			scroll-y
			class="scroll-content"
			:refresher-enabled="true"
			:refresher-triggered="isRefreshing"
			@refresherrefresh="onRefresh"
			@scrolltolower="loadMore"
		>

			<!-- 分类宫格 -->
			<view class="section">
				<view class="section-header">
					<view class="section-title-wrap">
						<view class="section-bar" :style="'background:' + tc.primary + ';'"></view>
						<text class="section-title">资源分类</text>
					</view>
					<view class="more-link" @tap="goCategoryList">
						<text class="more-text" :style="'color:' + tc.primaryDark + ';'">全部分类</text>
						<text class="more-arrow">›</text>
					</view>
				</view>
				<!-- 骨架屏: 分类 -->
				<view class="category-grid" v-if="loading && categories.length === 0">
					<view class="category-item" v-for="n in 10" :key="n">
						<view class="skeleton-circle"></view>
						<view class="skeleton-text short"></view>
					</view>
				</view>
				<scroll-view scroll-x class="category-scroll" v-else>
					<view class="category-grid">
						<view
							class="category-item"
							v-for="item in categories"
							:key="item.id"
							@tap="goCategory(item)"
						>
							<view class="category-icon">
								<text class="icon-emoji">{{ item.icon }}</text>
								<view class="icon-glow" :style="'background:' + getGlowColor(item) + ';'"></view>
							</view>
							<text class="category-name">{{ item.name }}</text>
						</view>
					</view>
				</scroll-view>
			</view>

			<!-- 热门资源 -->
			<view class="section" v-if="hotResources.length > 0 || loading">
				<view class="section-header">
					<view class="section-title-wrap">
						<view class="section-bar" :style="'background:' + tc.primary + ';'"></view>
						<text class="section-title">热门资源</text>
					</view>
					<view class="more-link" @tap="goMore('hot')">
						<text class="more-text" :style="'color:' + tc.primaryDark + ';'">查看更多</text>
						<text class="more-arrow">></text>
					</view>
				</view>
				<!-- 骨架屏: 热门 -->
				<view class="hot-scroll" v-if="loading && hotResources.length === 0">
					<view class="hot-list">
						<view class="hot-card skeleton-card" v-for="n in 3" :key="n">
							<view class="skeleton-block hot-cover-sk"></view>
							<view class="skeleton-info">
								<view class="skeleton-text"></view>
								<view class="skeleton-text short"></view>
							</view>
						</view>
					</view>
				</view>
				<scroll-view scroll-x class="hot-scroll" v-else>
					<view class="hot-list">
						<view
							class="hot-card"
							v-for="item in hotResources"
							:key="item.id"
							@tap="goDetail(item)"
						>
							<image class="hot-cover" :src="fixUrl(item.cover_url)" mode="aspectFill"></image>
							<view class="hot-info">
								<text class="hot-title">{{ item.title }}</text>
								<view class="hot-bottom">
									<text class="hot-price" v-if="item.price > 0">¥{{ item.price }}</text>
									<text class="hot-price free" :style="'color:' + tc.primary + ';'" v-else>免费</text>
									<text class="hot-count">{{ item.download_count }}次下载</text>
								</view>
							</view>
						</view>
					</view>
				</scroll-view>
			</view>

			<!-- 推荐资源 -->
			<view class="section" v-if="recommendList.length > 0 || loading">
				<view class="section-header">
					<view class="section-title-wrap">
						<view class="section-bar" :style="'background:' + tc.primary + ';'"></view>
						<text class="section-title">推荐资源</text>
					</view>
					<view class="more-link" @tap="goMore('recommend')">
						<text class="more-text" :style="'color:' + tc.primaryDark + ';'">查看更多</text>
						<text class="more-arrow">></text>
					</view>
				</view>
				<!-- 骨架屏: 推荐 -->
				<view v-if="loading && recommendList.length === 0">
					<view class="recommend-card skeleton-card" v-for="n in 3" :key="n">
						<view class="skeleton-block recommend-cover-sk"></view>
						<view class="recommend-info">
							<view class="skeleton-text"></view>
							<view class="skeleton-text short"></view>
							<view class="skeleton-text shorter"></view>
						</view>
					</view>
				</view>
				<view class="recommend-list" v-else>
					<view
						class="recommend-card"
						v-for="item in recommendList"
						:key="item.id"
						@tap="goDetail(item)"
					>
						<image class="recommend-cover" :src="fixUrl(item.cover_url)" mode="aspectFill"></image>
						<view class="recommend-info">
							<text class="recommend-title">{{ item.title }}</text>
							<view class="recommend-tags">
								<text class="tag" :style="tPri" v-if="item.category_name">{{ item.category_name }}</text>
								<text class="tag type" :style="'color:' + tc.primary + ';'" v-if="item.file_type">{{ item.file_type }}</text>
							</view>
							<view class="recommend-bottom">
								<view class="recommend-stats">
									<text class="stat-item">{{ item.view_count }} 浏览</text>
									<text class="stat-item">{{ item.download_count }} 下载</text>
								</view>
								<text class="recommend-price" v-if="item.price > 0">¥{{ item.price }}</text>
								<text class="recommend-price free" :style="'color:' + tc.primary + ';'" v-else>免费</text>
							</view>
						</view>
					</view>
				</view>
			</view>

			<!-- 投票排行 -->
			<view class="section" v-if="rankList.length > 0">
				<view class="section-header">
					<view class="section-title-wrap">
						<view class="section-bar" :style="'background:' + tc.primary + ';'"></view>
						<text class="section-title">投票排行</text>
					</view>
					<view class="more-link" @tap="goRank()">
						<text class="more-text" :style="'color:' + tc.primaryDark + ';'">查看更多</text>
						<text class="more-arrow">></text>
					</view>
				</view>
				<scroll-view scroll-x class="rank-scroll">
					<view class="rank-list">
						<view
							class="rank-card-h"
							v-for="(item, idx) in rankList"
							:key="item.id"
							@tap="goDetail(item)"
						>
							<view class="rank-badge" :class="'rank-badge-' + (idx < 3 ? idx + 1 : 'other')">
								<text v-if="idx === 0">🥇</text>
								<text v-else-if="idx === 1">🥈</text>
								<text v-else-if="idx === 2">🥉</text>
								<text v-else class="rank-num-text">{{ idx + 1 }}</text>
							</view>
							<image class="rank-card-cover" :src="fixUrl(item.cover_url)" mode="aspectFill"></image>
							<view class="rank-card-info">
								<text class="rank-card-title">{{ item.title }}</text>
								<view class="rank-card-votes">
									<text class="vote-up-sm" :style="'color:' + tc.primary + ';'">👍{{ item.up_votes }}</text>
									<text
										class="vote-net-sm"
										:class="item.net_votes >= 0 ? 'positive' : 'negative'"
										:style="item.net_votes >= 0 ? 'color:' + tc.primary + ';' : ''"
									>{{ item.net_votes >= 0 ? '+' : '' }}{{ item.net_votes }}</text>
								</view>
							</view>
						</view>
					</view>
				</scroll-view>
			</view>

			<!-- 空状态 -->
			<view class="empty" v-if="!loading && banners.length === 0 && hotResources.length === 0 && recommendList.length === 0">
				<text class="empty-icon">📦</text>
				<text class="empty-text">暂无资源</text>
			</view>

			<!-- 加载状态 -->
			<view class="load-status">
				<text v-if="loading && recommendList.length > 0">加载中...</text>
				<text v-else-if="noMore">— 已经到底了 —</text>
			</view>
		</scroll-view>
	</view>
</template>

<script>
import http, { BASE_URL } from '@/utils/http.js';

export default {
	data() {
		return {
			banners: [],
			categories: [],
			hotResources: [],
			recommendList: [],
			rankList: [],
			page: 1,
			pageSize: 10,
			loading: false,
			noMore: false,
			isRefreshing: false
		};
	},
	computed: {
		freeTextStyle() {
			return 'color:' + this.tc.primary + ';';
		}
	},
	onLoad() {
		this.initData();
	},
	onShow() {
		// 主题由全局 mixin 自动同步
	},
	methods: {
		async initData() {
			await Promise.all([
				this.loadBanners(),
				this.loadCategories(),
				this.loadHotResources(),
				this.loadRecommend(),
				this.loadRankList()
			]);
		},
		async loadBanners() {
			try {
				const res = await http.get('/api/banner/list');
				if (res.code === 0) {
					this.banners = res.data || [];
				}
			} catch (e) {
				console.error('加载轮播图失败', e);
			}
		},
		async loadCategories() {
			try {
				const res = await http.get('/api/category/list');
				if (res.code === 0) {
					// API returns tree structure, flatten to top-level categories
					const data = res.data || [];
					this.categories = Array.isArray(data) ? data : [];
				}
			} catch (e) {
				console.error('加载分类失败', e);
			}
		},
		async loadHotResources() {
			try {
				const res = await http.get('/api/resource/hot', { page_size: 8 });
				if (res.code === 0) {
					const data = res.data;
					this.hotResources = Array.isArray(data) ? data : (data.list || []);
				}
			} catch (e) {
				console.error('加载热门资源失败', e);
			}
		},
		async loadRankList() {
			try {
				const res = await http.get('/api/vote/ranking', { type: 'net', page_size: 5 }, { silent: true });
				if (res.code === 0) {
					this.rankList = (res.data && res.data.list) ? res.data.list : [];
				}
			} catch (e) {
				console.error('加载排行失败', e);
			}
		},
		async loadRecommend() {
			if (this.loading || this.noMore) return;
			this.loading = true;
			try {
				const res = await http.get('/api/resource/recommend', {
					page: this.page,
					page_size: this.pageSize
				});
				if (res.code === 0) {
					const raw = res.data;
					const list = Array.isArray(raw) ? raw : (raw.list || []);
					if (this.page === 1) {
						this.recommendList = list;
					} else {
						this.recommendList = [...this.recommendList, ...list];
					}
					if (list.length < this.pageSize) {
						this.noMore = true;
					}
					this.page++;
				}
			} catch (e) {
				console.error('加载推荐资源失败', e);
			} finally {
				this.loading = false;
			}
		},
		async onRefresh() {
			this.isRefreshing = true;
			this.page = 1;
			this.noMore = false;
			await this.initData();
			this.isRefreshing = false;
		},
		loadMore() {
			this.loadRecommend();
		},
		goSearch() {
			uni.navigateTo({ url: '/pages/resource/search' });
		},
		onBannerTap(item) {
			if (item.link_type === 'resource' && item.link_value) {
				uni.navigateTo({ url: `/pages/resource/detail?id=${item.link_value}` });
			} else if (item.link_type === 'url' && item.link_value) {
				// Check if it's an internal page path
				if (item.link_value.startsWith('/pages/')) {
					// tabBar页面只能用switchTab，且不支持参数
					if (item.link_value === '/pages/index/index' || item.link_value === '/pages/category/list' || item.link_value === '/pages/user/index') {
						uni.switchTab({ url: item.link_value });
					} else {
						uni.navigateTo({ url: item.link_value });
					}
				} else {
					uni.navigateTo({ url: `/pages/webview/index?url=${encodeURIComponent(item.link_value)}` });
				}
			}
		},
		goCategory(item) {
			uni.navigateTo({ url: `/pages/category/detail?id=${item.id}` });
		},
		goCategoryList() {
			uni.switchTab({ url: '/pages/category/list' });
		},
		goDetail(item) {
			uni.navigateTo({ url: `/pages/resource/detail?id=${item.id}` });
		},
		goMore(type) {
			const app = getApp();
			if (!app.globalData) app.globalData = {};
			app.globalData.categoryFilter = type;
			uni.switchTab({ url: '/pages/category/list' });
		},
		goRank() {
			uni.navigateTo({ url: '/pages/rank/index' });
		},
		fixUrl(url) {
			if (!url) return '';
			if (url.startsWith('http')) return url;
			return http.getBaseUrl() + url;
		},
		getGlowColor(item) {
			const map = {
				'小学': '#FF9A9E', '初中': '#A18CD1', '高中': '#FBC2EB',
				'大学': '#84FAB0', '考公': '#FFD1FF', '考研': '#C2FFD8',
				'职业': '#F6D365', '设计': '#FDA085', '办公': '#A1C4FD',
				'其他': '#D4FC79'
			};
			const name = item.name || '';
			for (const k in map) {
				if (name.indexOf(k) !== -1) return map[k];
			}
			return '#C2B2F2';
		}
	},
	onShareAppMessage() {
		return {
			title: '海量资源免费下载，模板素材应有尽有',
			path: '/pages/index/index'
		};
	}
};
</script>

<style scoped>
.page {
	min-height: 100vh;
	background: linear-gradient(180deg, #f0f2ff 0%, #f5f6fa 200rpx);
}

/* 紫色渐变顶部背景 */
.header-bg {
	height: 450rpx;
	background: linear-gradient(180deg, #1abc9c 0%, #27ae60 30%, #2ed573 60%, #f0fdf4 85%, #ffffff 100%);
	position: relative;
}
.header-bg::before {
	content: '';
	position: absolute;
	top: 30rpx;
	left: 60rpx;
	width: 6rpx;
	height: 6rpx;
	border-radius: 50%;
	background: rgba(255, 255, 255, 0.9);
	box-shadow:
		80rpx 40rpx 0 4rpx rgba(255, 255, 255, 0.25),
		200rpx 100rpx 0 2rpx rgba(255, 255, 255, 0.35),
		320rpx 30rpx 0 6rpx rgba(255, 255, 255, 0.15),
		500rpx 80rpx 0 3rpx rgba(255, 255, 255, 0.3),
		140rpx 160rpx 0 2rpx rgba(255, 255, 255, 0.2),
		420rpx 140rpx 0 5rpx rgba(255, 255, 255, 0.12);
	animation: float-particle 6s ease-in-out infinite;
	pointer-events: none;
}
@keyframes float-particle {
	0%, 100% { transform: translateY(0); }
	25% { transform: translateY(-12rpx); }
	50% { transform: translateY(-6rpx); }
	75% { transform: translateY(-18rpx); }
}
.header-bg::after {
	content: '';
	position: absolute;
	bottom: -40rpx;
	left: 0;
	right: 0;
	height: 80rpx;
	background: linear-gradient(180deg, rgba(46,213,115,0.15) 0%, transparent 100%);
	border-radius: 50%;
	filter: blur(20rpx);
}

/* 搜索栏 - 玻璃态效果 */
.search-bar {
	padding: 0 28rpx;
	margin-top: 30rpx;
	position: relative;
	z-index: 9999;
}
.search-input {
	display: flex;
	align-items: center;
	height: 80rpx;
	padding: 0 32rpx;
	background: rgba(255, 255, 255, 0.72);
	backdrop-filter: blur(16px);
	-webkit-backdrop-filter: blur(16px);
	border-radius: 44rpx;
	box-shadow:
		0 8rpx 32rpx rgba(46,213,115, 0.12),
		0 2rpx 8rpx rgba(0, 0, 0, 0.04),
		inset 0 1rpx 0 rgba(255, 255, 255, 0.8);
	border: 1rpx solid rgba(255, 255, 255, 0.6);
	transition: box-shadow 0.25s ease;
	position: relative;
	overflow: hidden;
}
.search-input::after {
	content: '';
	position: absolute;
	top: 0;
	left: -100%;
	width: 30%;
	height: 100%;
	background: linear-gradient(90deg, transparent 0%, rgba(255,255,255,0.35) 50%, transparent 100%);
	animation: shimmer-sweep 12s ease-in-out infinite;
	pointer-events: none;
}
@keyframes shimmer-sweep {
	0% { left: -100%; }
	40% { left: 150%; }
	100% { left: 150%; }
}
.search-input:active {
	box-shadow:
		0 4rpx 16rpx rgba(46,213,115, 0.18),
		0 1rpx 4rpx rgba(0, 0, 0, 0.06),
		inset 0 1rpx 0 rgba(255, 255, 255, 0.8);
}
.placeholder {
	margin-left: 14rpx;
	font-size: 28rpx;
	color: #9a9ac0;
	letter-spacing: 0.5rpx;
}

/* 轮播图 - 毛玻璃边框 + 底部反射 */
.banner-swiper {
	height: 360rpx;
	margin: -270rpx 28rpx 0;
	border-radius: 28rpx;
	overflow: hidden;
	box-shadow:
		0 20rpx 60rpx rgba(46,213,115, 0.22),
		0 6rpx 16rpx rgba(0, 0, 0, 0.08),
		0 1rpx 3rpx rgba(0, 0, 0, 0.04);
	position: relative;
	z-index: 9999;
	border: 2rpx solid rgba(255, 255, 255, 0.35);
}
.banner-swiper::after {
	content: '';
	position: absolute;
	bottom: -60rpx;
	left: 20rpx;
	right: 20rpx;
	height: 60rpx;
	background: linear-gradient(180deg, rgba(26,188,156,0.12) 0%, rgba(26,188,156,0.03) 60%, transparent 100%);
	border-radius: 0 0 28rpx 28rpx;
	transform: scaleY(-1);
	filter: blur(6rpx);
	pointer-events: none;
	opacity: 0.25;
}
.banner-img {
	width: 100%;
	height: 100%;
	border-radius: 24rpx;
}

/* 通用区块 */
.section {
	margin: 28rpx 0;
	padding: 0 28rpx;
}
.section-header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-bottom: 22rpx;
}
/* 区块标题 - 左侧竖条装饰 + 渐变文字质感 */
.section-title-wrap {
	position: relative;
	display: flex;
	align-items: center;
}
.section-title {
	font-size: 36rpx;
	font-weight: 800;
	letter-spacing: 1rpx;
	padding-left: 22rpx;
	background: linear-gradient(135deg, #1a1a2e 0%, #4a4a6a 50%, #2d2d4a 100%);
	-webkit-background-clip: text;
	-webkit-text-fill-color: transparent;
	background-clip: text;
}
.section-bar {
	width: 5rpx;
	height: 28rpx;
	border-radius: 4rpx;
	position: absolute;
	left: 0;
	top: 50%;
	transform: translateY(-50%);
}
.section-more {
	font-size: 26rpx;
	color: #2ed573;
}

/* 查看更多链接 - 箭头动画 */
.more-link {
	display: flex;
	align-items: center;
	padding: 8rpx 0 8rpx 16rpx;
	border-radius: 20rpx;
	transition: background 0.2s ease;
}
.more-link:active {
	background: rgba(46,213,115, 0.06);
}
.more-text {
	font-size: 26rpx;
	font-weight: 500;
	letter-spacing: 0.3rpx;
	line-height: 1.7;
}
.more-arrow {
	font-size: 26rpx;
	color: #27ae60;
	margin-left: 6rpx;
	transition: transform 0.25s ease;
	display: inline-block;
}
.more-link:active .more-arrow {
	transform: translateX(6rpx);
}

/* 分类宫格 */
.category-scroll {
	white-space: nowrap;
}
.category-grid {
	display: inline-flex;
	flex-wrap: nowrap;
	background: linear-gradient(135deg, rgba(255,255,255,0.88) 0%, rgba(245,242,255,0.78) 100%);
	border-radius: 28rpx;
	padding: 32rpx 16rpx 24rpx;
	box-shadow:
		0 8rpx 32rpx rgba(46,213,115, 0.08),
		0 1rpx 3rpx rgba(0, 0, 0, 0.04),
		inset 0 1rpx 0 rgba(255,255,255,0.7);
	border: 1rpx solid rgba(255, 255, 255, 0.6);
}
.category-item {
	width: 160rpx;
	display: inline-flex;
	flex-direction: column;
	align-items: center;
	margin-bottom: 28rpx;
	margin-right: 10rpx;
	transition: transform 0.2s ease;
}
.category-item:active {
	transform: scale(0.95);
}
/* 毛玻璃风格分类图标 + 旋转光效 */
.category-icon {
	width: 108rpx;
	height: 108rpx;
	border-radius: 30rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	margin-bottom: 18rpx;
	position: relative;
	overflow: hidden;
	/* 多层毛玻璃背景 */
	background:
		linear-gradient(135deg,
			rgba(255,255,255,0.7) 0%,
			rgba(255,255,255,0.3) 40%,
			rgba(255,255,255,0.5) 100%);
	/* 玻璃边框 - 上左亮 下右暗 */
	border-top: 2rpx solid rgba(255,255,255,0.95);
	border-left: 2rpx solid rgba(255,255,255,0.8);
	border-right: 2rpx solid rgba(255,255,255,0.4);
	border-bottom: 2rpx solid rgba(255,255,255,0.3);
	/* 立体阴影 */
	box-shadow:
		0 12rpx 40rpx rgba(46,213,115, 0.18),
		0 4rpx 12rpx rgba(0, 0, 0, 0.06),
		0 1rpx 3rpx rgba(0, 0, 0, 0.04),
		inset 0 1rpx 0 rgba(255,255,255,0.6);
}
/* 静态柔和径向光晕 */
.category-icon::before {
	content: '';
	position: absolute;
	top: -20%;
	left: -20%;
	width: 140%;
	height: 140%;
	background: radial-gradient(ellipse at 30% 30%,
		rgba(255,255,255,0.35) 0%,
		rgba(200,180,255,0.15) 40%,
		transparent 70%);
	pointer-events: none;
	border-radius: 30rpx;
}
/* 底部环境光反射 */
.category-icon::after {
	content: '';
	position: absolute;
	bottom: -4rpx;
	left: 15%;
	width: 70%;
	height: 35%;
	background: radial-gradient(ellipse at center,
		rgba(255,255,255,0.25) 0%,
		transparent 70%);
	border-radius: 50%;
	pointer-events: none;
}
.icon-glow {
	position: absolute;
	top: -30rpx;
	right: -30rpx;
	width: 90rpx;
	height: 90rpx;
	border-radius: 50%;
	opacity: 0.35;
	filter: blur(18rpx);
}
.icon-emoji {
	font-size: 46rpx;
	position: relative;
	z-index: 2;
	text-shadow:
		0 2rpx 6rpx rgba(0, 0, 0, 0.08),
		0 0 20rpx rgba(255, 255, 255, 0.5);
}
.category-name {
	font-size: 24rpx;
	color: #2d2d3f;
	text-align: center;
	font-weight: 600;
	letter-spacing: 0.8rpx;
	text-shadow: 0 1rpx 2rpx rgba(255, 255, 255, 0.8);
}

/* 热门资源横滑 - 悬浮效果 + 底部渐变遮罩 */
.hot-scroll {
	white-space: nowrap;
}
.hot-list {
	display: inline-flex;
	padding: 4rpx 0;
}
.hot-card {
	width: 300rpx;
	margin-right: 22rpx;
	background: #fff;
	border-radius: 22rpx;
	overflow: hidden;
	box-shadow:
		0 8rpx 32rpx rgba(46,213,115, 0.1),
		0 2rpx 8rpx rgba(0, 0, 0, 0.04);
	display: inline-block;
	transition: transform 0.2s ease, box-shadow 0.25s ease;
	position: relative;
}
/* 热门卡片封面底部微弱渐变遮罩 */
.hot-card::after {
	content: '';
	position: absolute;
	top: 0;
	left: 0;
	width: 300rpx;
	height: 210rpx;
	background: linear-gradient(180deg,
		transparent 50%,
		rgba(0, 0, 0, 0.04) 75%,
		rgba(0, 0, 0, 0.08) 100%);
	pointer-events: none;
	border-radius: 22rpx 22rpx 0 0;
	z-index: 1;
}
.hot-card:active {
	transform: scale(0.97);
	box-shadow:
		0 4rpx 16rpx rgba(46,213,115, 0.15),
		0 1rpx 4rpx rgba(0, 0, 0, 0.06);
}
.hot-cover {
	width: 300rpx;
	height: 210rpx;
}
.hot-info {
	padding: 18rpx 20rpx;
}
.hot-title {
	font-size: 26rpx;
	color: #1a1a2e;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
	display: block;
	width: 260rpx;
	font-weight: 600;
	letter-spacing: 0.3rpx;
	line-height: 1.7;
}
.hot-bottom {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-top: 14rpx;
}
.hot-price {
	font-size: 30rpx;
	color: #ff4757;
	font-weight: 800;
}
.hot-price.free {
	font-size: 26rpx;
	font-weight: 700;
}
.hot-count {
	font-size: 22rpx;
	color: #aaa;
	letter-spacing: 0.3rpx;
	line-height: 1.7;
}

/* 推荐资源列表 */
.recommend-card {
	display: flex;
	background: #fff;
	border-radius: 22rpx;
	margin-bottom: 22rpx;
	overflow: hidden;
	box-shadow:
		0 6rpx 24rpx rgba(46,213,115, 0.08),
		0 1rpx 4rpx rgba(0, 0, 0, 0.04);
	transition: transform 0.2s ease, box-shadow 0.25s ease;
	position: relative;
}
/* 推荐卡片右上角精致圆角装饰 */
.recommend-card::before {
	content: '';
	position: absolute;
	top: 0;
	right: 0;
	width: 32rpx;
	height: 32rpx;
	background: linear-gradient(225deg, rgba(46,213,115,0.08) 0%, transparent 70%);
	border-radius: 0 22rpx 0 0;
	z-index: 2;
}
.recommend-card:active {
	transform: scale(0.97);
	box-shadow:
		0 3rpx 12rpx rgba(46,213,115, 0.12),
		0 1rpx 4rpx rgba(0, 0, 0, 0.06);
}
.recommend-cover {
	width: 260rpx;
	height: 210rpx;
	flex-shrink: 0;
}
.recommend-info {
	flex: 1;
	padding: 22rpx;
	display: flex;
	flex-direction: column;
	justify-content: space-between;
	min-width: 0;
}
.recommend-title {
	font-size: 28rpx;
	color: #1a1a2e;
	font-weight: 700;
	overflow: hidden;
	text-overflow: ellipsis;
	display: -webkit-box;
	-webkit-line-clamp: 2;
	-webkit-box-orient: vertical;
	line-height: 1.5;
	letter-spacing: 0.3rpx;
}
.recommend-tags {
	display: flex;
	gap: 12rpx;
	margin-top: 12rpx;
	flex-wrap: wrap;
}
.tag {
	font-size: 22rpx;
	background: rgba(46,213,115, 0.08);
	padding: 6rpx 18rpx;
	border-radius: 20rpx;
	font-weight: 500;
}
.tag.type {
	color: #00b894;
	background: rgba(0, 184, 148, 0.08);
}
.recommend-bottom {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-top: 14rpx;
}
.recommend-stats {
	display: flex;
	gap: 18rpx;
}
.stat-item {
	font-size: 22rpx;
	color: #aaa;
	letter-spacing: 0.3rpx;
	line-height: 1.7;
}
.recommend-price {
	font-size: 32rpx;
	color: #ff4757;
	font-weight: 800;
}
.recommend-price.free {
	font-size: 26rpx;
	font-weight: 700;
}

/* 投票排行横滑 - 精致样式 + 前三名发光边框 */
.rank-scroll {
	white-space: nowrap;
}
.rank-list {
	display: inline-flex;
	padding: 4rpx 0;
}
.rank-card-h {
	width: 340rpx;
	margin-right: 22rpx;
	background: #fff;
	border-radius: 22rpx;
	overflow: hidden;
	box-shadow:
		0 8rpx 32rpx rgba(46,213,115, 0.1),
		0 2rpx 8rpx rgba(0, 0, 0, 0.04);
	display: inline-block;
	transition: transform 0.2s ease, box-shadow 0.25s ease;
	position: relative;
}
.rank-card-h:active {
	transform: scale(0.97);
	box-shadow:
		0 4rpx 16rpx rgba(46,213,115, 0.15),
		0 1rpx 4rpx rgba(0, 0, 0, 0.06);
}
.rank-badge {
	position: absolute;
	top: 12rpx;
	left: 12rpx;
	width: 52rpx;
	height: 52rpx;
	border-radius: 50%;
	display: flex;
	align-items: center;
	justify-content: center;
	z-index: 5;
	font-size: 32rpx;
	background: rgba(255,255,255,0.92);
	box-shadow:
		0 4rpx 12rpx rgba(0,0,0,0.12),
		0 1rpx 3rpx rgba(0,0,0,0.06);
	backdrop-filter: blur(8px);
}
/* 金牌 - 金色发光脉冲（柔和版） */
.rank-badge-1 {
	background: linear-gradient(135deg, rgba(255,215,0,0.95), rgba(255,185,0,0.9));
	box-shadow:
		0 4rpx 12rpx rgba(255,215,0,0.15),
		0 0 12rpx rgba(255,215,0,0.1),
		0 0 24rpx rgba(255,185,0,0.05);
	animation: glow-gold 2.5s ease-in-out infinite;
}
@keyframes glow-gold {
	0%, 100% { box-shadow: 0 4rpx 12rpx rgba(255,215,0,0.15), 0 0 12rpx rgba(255,215,0,0.1), 0 0 24rpx rgba(255,185,0,0.05); }
	50% { box-shadow: 0 4rpx 16rpx rgba(255,215,0,0.25), 0 0 18rpx rgba(255,215,0,0.18), 0 0 32rpx rgba(255,185,0,0.09); }
}
/* 银牌 - 银色发光脉冲（柔和版） */
.rank-badge-2 {
	background: linear-gradient(135deg, rgba(210,210,220,0.95), rgba(180,180,195,0.9));
	box-shadow:
		0 4rpx 12rpx rgba(192,192,192,0.15),
		0 0 10rpx rgba(200,200,210,0.1),
		0 0 20rpx rgba(180,180,195,0.05);
	animation: glow-silver 2.5s ease-in-out infinite 0.3s;
}
@keyframes glow-silver {
	0%, 100% { box-shadow: 0 4rpx 12rpx rgba(192,192,192,0.15), 0 0 10rpx rgba(200,200,210,0.1), 0 0 20rpx rgba(180,180,195,0.05); }
	50% { box-shadow: 0 4rpx 16rpx rgba(192,192,192,0.25), 0 0 16rpx rgba(200,200,210,0.18), 0 0 28rpx rgba(180,180,195,0.08); }
}
/* 铜牌 - 铜色发光脉冲（柔和版） */
.rank-badge-3 {
	background: linear-gradient(135deg, rgba(215,150,70,0.95), rgba(190,120,40,0.9));
	box-shadow:
		0 4rpx 12rpx rgba(205,127,50,0.15),
		0 0 10rpx rgba(215,150,70,0.1),
		0 0 20rpx rgba(190,120,40,0.05);
	animation: glow-bronze 2.5s ease-in-out infinite 0.6s;
}
@keyframes glow-bronze {
	0%, 100% { box-shadow: 0 4rpx 12rpx rgba(205,127,50,0.15), 0 0 10rpx rgba(215,150,70,0.1), 0 0 20rpx rgba(190,120,40,0.05); }
	50% { box-shadow: 0 4rpx 16rpx rgba(205,127,50,0.25), 0 0 16rpx rgba(215,150,70,0.18), 0 0 28rpx rgba(190,120,40,0.08); }
}
.rank-badge-other { background: rgba(255,255,255,0.88); }
.rank-num-text {
	font-size: 22rpx;
	font-weight: 800;
	color: #666;
}
.rank-card-cover {
	width: 340rpx;
	height: 200rpx;
}
.rank-card-info {
	padding: 18rpx 20rpx;
}
.rank-card-title {
	font-size: 26rpx;
	color: #1a1a2e;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
	display: block;
	width: 300rpx;
	font-weight: 600;
	letter-spacing: 0.3rpx;
	line-height: 1.7;
}
.rank-card-votes {
	display: flex;
	align-items: center;
	gap: 14rpx;
	margin-top: 12rpx;
}
.vote-up-sm {
	font-size: 22rpx;
	color: #22c55e;
	font-weight: 600;
}
.vote-net-sm {
	font-size: 22rpx;
	font-weight: 700;
	padding: 4rpx 14rpx;
	border-radius: 10rpx;
}
.vote-net-sm.positive {
	color: #16a34a;
	background: rgba(22, 163, 74, 0.1);
}
.vote-net-sm.negative {
	color: #dc2626;
	background: rgba(220, 38, 38, 0.1);
}

/* 空状态 - 精致圆形背景 */
.empty {
	display: flex;
	flex-direction: column;
	align-items: center;
	padding: 120rpx 0;
}
.empty-icon {
	font-size: 80rpx;
	margin-bottom: 24rpx;
	width: 160rpx;
	height: 160rpx;
	line-height: 160rpx;
	text-align: center;
	background: linear-gradient(135deg, rgba(46,213,115,0.08) 0%, rgba(26,188,156,0.05) 100%);
	border-radius: 50%;
	box-shadow:
		0 8rpx 32rpx rgba(46,213,115, 0.06),
		inset 0 1rpx 0 rgba(255,255,255,0.8);
	border: 2rpx solid rgba(46,213,115, 0.08);
}
.empty-text {
	font-size: 28rpx;
	color: #999;
	letter-spacing: 1rpx;
}

/* 加载状态 - 动画点效果 */
.load-status {
	text-align: center;
	padding: 30rpx 0;
	font-size: 24rpx;
	color: #aaa;
}
@keyframes loading-dot {
	0%, 80%, 100% { opacity: 0.3; transform: scale(0.8); }
	40% { opacity: 1; transform: scale(1); }
}

/* ========== 骨架屏 - 优化shimmer ========== */
@keyframes skeleton-pulse {
	0% { opacity: 1; }
	50% { opacity: 0.4; }
	100% { opacity: 1; }
}

.skeleton-block {
	background: linear-gradient(90deg, #eaeaef 25%, #f2f2f6 37%, #eaeaef 63%);
	background-size: 400% 100%;
	animation: skeleton-shimmer 1.2s ease-in-out infinite;
	border-radius: 16rpx;
}

@keyframes skeleton-shimmer {
	0% { background-position: 100% 50%; }
	100% { background-position: 0% 50%; }
}

.skeleton-banner {
	margin: -180rpx 28rpx 0;
	position: relative;
	z-index: 9999;
}
.banner-block {
	height: 360rpx;
	border-radius: 28rpx;
}

.skeleton-circle {
	width: 108rpx;
	height: 108rpx;
	border-radius: 30rpx;
	background: linear-gradient(135deg,
		rgba(232,232,240,0.7) 0%,
		rgba(240,240,248,0.4) 40%,
		rgba(236,236,244,0.6) 100%);
	border: 2rpx solid rgba(255, 255, 255, 0.5);
	box-shadow:
		0 8rpx 24rpx rgba(46,213,115, 0.08),
		inset 0 1rpx 0 rgba(255,255,255,0.4);
	margin-bottom: 18rpx;
}

.skeleton-text {
	height: 28rpx;
	border-radius: 8rpx;
	background: linear-gradient(90deg, #eaeaef 25%, #f2f2f6 37%, #eaeaef 63%);
	background-size: 400% 100%;
	animation: skeleton-shimmer 1.2s ease-in-out infinite;
	margin-bottom: 12rpx;
	width: 100%;
}
.skeleton-text.short {
	width: 60%;
}
.skeleton-text.shorter {
	width: 40%;
}

.skeleton-card {
	background: #fff;
}

.hot-cover-sk {
	height: 210rpx;
	border-radius: 0;
}

.skeleton-info {
	padding: 18rpx 20rpx;
}

.recommend-cover-sk {
	width: 260rpx;
	height: 210rpx;
	flex-shrink: 0;
	border-radius: 0;
}
</style>
