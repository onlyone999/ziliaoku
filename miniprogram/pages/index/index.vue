<template>
	<view class="page" :class="'theme-' + activeTheme" :style="pageBgStyle">
		<!-- 渐变顶部背景 -->
		<view class="header-bg" :style="'background:' + tc.headerGradient + ';'"></view>

		<!-- 轮播图（与header-bg同层，z-index生效） -->
		<swiper
			v-if="banners.length > 0"
			class="banner-swiper"
			:indicator-dots="true"
			indicator-color="rgba(26,26,26,0.2)"
			indicator-active-color="#ffd54f"
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

		<!-- 公告栏 -->
		<view
			class="notice-bar"
			v-if="announcements.length > 0"
			:style="'color:' + tc.primary + ';'"
			@tap="goAnnouncement"
		>
			<text class="notice-dot" :style="'background:' + tc.primary + ';'"></text>
			<text class="notice-label">公告</text>
			<view class="notice-content">
				<swiper
					class="notice-swiper"
					:vertical="true"
					:autoplay="true"
					:interval="3500"
					:duration="400"
					:circular="true"
					:display-multiple-items="1"
				>
					<swiper-item v-for="item in announcements" :key="item.id" @tap.stop="goAnnouncementDetail(item)">
						<view class="notice-item">
							<text class="notice-text">{{ item.title }}</text>
						</view>
					</swiper-item>
				</swiper>
			</view>
			<text class="notice-arrow" :style="'color:' + tc.primary + ';'">›</text>
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
						<view class="section-bar" style="background:#ffd54f;"></view>
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
							<view class="category-icon" :style="'background:' + getGlowColor(item) + ';'">
								<image v-if="getCatIcon(item)" class="icon-img" :src="getCatIcon(item)" mode="aspectFit"></image>
								<text v-else class="icon-emoji">{{ item.icon }}</text>
							</view>
							<text class="category-name">{{ item.name }}</text>
						</view>
					</view>
				</scroll-view>
			</view>

			<!-- 活动入口 -->
			<view class="section" v-if="activityEnabled && (activities.length > 0 || activityLoading)">
				<view class="section-header">
					<view class="section-title-wrap">
						<view class="section-bar" style="background:#ffd54f;"></view>
						<text class="section-title">活动中心</text>
					</view>
					<view class="more-link" @tap="goActivityList">
						<text class="more-text" :style="'color:' + tc.primaryDark + ';'">全部活动</text>
						<text class="more-arrow">›</text>
					</view>
				</view>
				<scroll-view scroll-x class="hot-scroll">
					<view class="hot-list">
						<view
							class="activity-card-h"
							v-for="item in activities"
							:key="item.id"
							@tap="goActivityDetail(item)"
						>
							<image v-if="item.cover_url" class="activity-cover" :src="fixUrl(item.cover_url)" mode="aspectFill"></image>
							<view v-else class="activity-cover-ph">
								<text class="activity-cover-icon">🎯</text>
							</view>
							<view class="activity-info">
								<text class="activity-title">{{ item.title }}</text>
								<view class="activity-meta">
									<text class="activity-time">{{ formatActivityTime(item.start_time) }}</text>
									<text class="activity-count">{{ item.current_count || 0 }}人报名</text>
								</view>
								<view class="activity-status" :style="'color:' + tc.primary + ';border-color:' + tc.primary + '30;background:' + tc.primary + '0a;'">
									<text>报名中</text>
								</view>
							</view>
						</view>
					</view>
				</scroll-view>
			</view>

			<!-- 热门资源 -->
			<view class="section" v-if="hotResources.length > 0 || loading">
				<view class="section-header">
					<view class="section-title-wrap">
						<view class="section-bar" style="background:#ffd54f;"></view>
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
						<view class="section-bar" style="background:#ffd54f;"></view>
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
						<view class="section-bar" style="background:#ffd54f;"></view>
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

		<lingxi-tabbar :current="0"></lingxi-tabbar>
	</view>
</template>

<script>
import http, { BASE_URL } from '@/utils/http.js';
import LingxiTabbar from '@/components/lingxi-tabbar/lingxi-tabbar.vue';

export default {
	components: { LingxiTabbar },
	data() {
		return {
			banners: [
				{ id: 'local-1', title: '海量资源 免费下载', image_url: '/static/banners/banner1.png', link_type: 'url', link_value: '/pages/category/list' },
				{ id: 'local-2', title: '开通VIP 全站畅享', image_url: '/static/banners/banner2.png', link_type: 'url', link_value: '/pages/user/vip' },
				{ id: 'local-3', title: '热门精选 每日更新', image_url: '/static/banners/banner3.png', link_type: 'url', link_value: '/pages/rank/index' }
			],
			categories: [],
			hotResources: [],
			recommendList: [],
			rankList: [],
			announcements: [],
			activities: [],
			activityLoading: false,
			activityEnabled: true,
			recommendCount: 10,
			typeMap: { info: '通知', warning: '警告', success: '喜讯' },
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
		// 悬浮 tabbar 由组件 current 属性控制
	},
	methods: {
		async initData() {
			await this.loadSettings();
			await Promise.all([
				this.loadBanners(),
				this.loadCategories(),
				this.loadHotResources(),
				this.loadRecommend(),
				this.loadRankList(),
				this.loadAnnouncements()
			]);
			if (this.activityEnabled) {
				await this.loadActivities();
			}
		},
		async loadSettings() {
			try {
				const res = await http.get('/api/settings/config', {}, { silent: true });
				if (res.code === 0) {
					this.activityEnabled = res.data.activity_enabled !== '0';
					this.recommendCount = parseInt(res.data.home_recommend_count) || 10;
				}
			} catch (e) {}
		},
		async loadBanners() {
			// 使用本地灵溪风轮播图（static/banners）
			// 如需后台配置，把下面注释打开：
			// try {
			//   const res = await http.get('/api/banner/list', {}, { silent: true });
			//   if (res.code === 0 && Array.isArray(res.data) && res.data.length > 0) {
			//     this.banners = res.data;
			//   }
			// } catch (e) {}
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
		async loadAnnouncements() {
			try {
				const res = await http.get('/api/announcement/list', { page_size: 5 }, { silent: true });
				if (res.code === 0) {
					const raw = res.data;
					this.announcements = Array.isArray(raw) ? raw : (raw.list || []);
				}
			} catch (e) {
				console.error('加载公告失败', e);
			}
		},
		async loadActivities() {
			this.activityLoading = true;
			try {
				const res = await http.get('/api/activity/list', { page_size: 5 }, { silent: true });
				if (res.code === 0) {
					const raw = res.data;
					this.activities = Array.isArray(raw) ? raw : (raw.list || []);
				}
			} catch (e) {
				console.error('加载活动失败', e);
			} finally {
				this.activityLoading = false;
			}
		},
		async loadRecommend() {
			if (this.loading || this.noMore) return;
			this.loading = true;
			try {
				const res = await http.get('/api/resource/recommend', {
					page: this.page,
					page_size: this.recommendCount
				});
				if (res.code === 0) {
					const raw = res.data;
					const list = Array.isArray(raw) ? raw : (raw.list || []);
					if (this.page === 1) {
						this.recommendList = list;
					} else {
						this.recommendList = [...this.recommendList, ...list];
					}
					if (list.length < this.recommendCount) {
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
		goAnnouncement() {
			// 跳转到公告列表页（活动中心复用）
			uni.navigateTo({ url: '/pages/activity/list?type=announcement' });
		},
		goAnnouncementDetail(item) {
			uni.navigateTo({ url: '/pages/activity/detail?id=' + item.id + '&type=announcement' });
		},
		goActivityList() {
			uni.navigateTo({ url: '/pages/activity/list' });
		},
		goActivityDetail(item) {
			uni.navigateTo({ url: '/pages/activity/detail?id=' + item.id });
		},
		formatActivityTime(dt) {
			if (!dt) return '';
			return dt.substring(5, 16).replace('-', '/');
		},
		fixUrl(url) {
			if (!url) return '';
			if (url.startsWith('http')) return url;
			return http.getBaseUrl() + url;
		},
		getCatIcon(item) {
			const name = (item && item.name) || '';
			const map = {
				'小学': '/static/cats/xiaoxue.png',
				'初中': '/static/cats/chuzhong.png',
				'高中': '/static/cats/gaozhong.png',
				'大学': '/static/cats/daxue.png',
				'考公': '/static/cats/kaogong.png',
				'考研': '/static/cats/kaoyan.png',
				'职业': '/static/cats/zhiye.png',
				'设计': '/static/cats/sheji.png',
				'办公': '/static/cats/bangong.png',
				'其他': '/static/cats/qita.png'
			};
			for (const k in map) {
				if (name.indexOf(k) !== -1) return map[k];
			}
			return '';
		},
		getGlowColor(item) {
			const map = {
				'小学': 'linear-gradient(160deg,#fff5f5 0%,#ffe0e3 100%)',
				'初中': 'linear-gradient(160deg,#f5f3ff 0%,#e4dcff 100%)',
				'高中': 'linear-gradient(160deg,#fff0f6 0%,#ffd6e8 100%)',
				'大学': 'linear-gradient(160deg,#f0fdf4 0%,#d1fae5 100%)',
				'考公': 'linear-gradient(160deg,#f0f9ff 0%,#dbeafe 100%)',
				'考研': 'linear-gradient(160deg,#fffbeb 0%,#fde68a 100%)',
				'职业': 'linear-gradient(160deg,#fff7ed 0%,#fed7aa 100%)',
				'设计': 'linear-gradient(160deg,#fdf4ff 0%,#f5d0fe 100%)',
				'办公': 'linear-gradient(160deg,#f0f9ff 0%,#bae6fd 100%)',
				'其他': 'linear-gradient(160deg,#f7fee7 0%,#ecfccb 100%)'
			};
			const name = item.name || '';
			for (const k in map) {
				if (name.indexOf(k) !== -1) return map[k];
			}
			return 'linear-gradient(160deg,#ffffff 0%,#eef5df 100%)';
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
	background: transparent;
	padding-bottom: calc(140rpx + env(safe-area-inset-bottom));
}

.header-bg {
	height: 480rpx;
	position: relative;
}

/* 搜索栏：白胶囊 */
.search-bar {
	padding: 0 28rpx;
	margin-top: 16rpx;
	position: relative;
	z-index: 9999;
}
.search-input {
	display: flex;
	align-items: center;
	height: 92rpx;
	padding: 0 32rpx;
	background: #ffffff;
	border-radius: 999rpx;
	box-shadow: 0 10rpx 32rpx rgba(70, 100, 30, 0.1);
	border: none;
	transition: all 0.2s ease;
	position: relative;
}
.search-input:active {
	box-shadow: 0 6rpx 16rpx rgba(70, 100, 30, 0.08);
	transform: scale(0.99);
}
.placeholder {
	margin-left: 14rpx;
	font-size: 27rpx;
	color: #8a9a70;
	letter-spacing: 0.5rpx;
}

/* 公告栏 */
.notice-bar {
	display: flex;
	align-items: center;
	margin: 18rpx 28rpx 0;
	padding: 0;
	height: 56rpx;
	transition: opacity 0.2s ease;
}
.notice-bar:active {
	opacity: 0.7;
}
.notice-dot {
	width: 8rpx;
	height: 8rpx;
	border-radius: 50%;
	flex-shrink: 0;
	margin-right: 12rpx;
	animation: none;
}
@keyframes notice-pulse {
	0%, 100% { opacity: 1; }
	50% { opacity: 0.35; }
}
.notice-label {
	flex-shrink: 0;
	font-size: 20rpx;
	font-weight: 800;
	margin-right: 14rpx;
	letter-spacing: 1.5rpx;
	padding: 6rpx 14rpx;
	border-radius: 999rpx;
	background: #ffd54f;
	color: #1a1a1a;
	line-height: 1.2;
}
.notice-content {
	flex: 1;
	min-width: 0;
	height: 56rpx;
	overflow: hidden;
}
.notice-swiper {
	height: 56rpx;
	width: 100%;
}
.notice-item {
	display: flex;
	align-items: center;
	height: 56rpx;
}
.notice-text {
	flex: 1;
	font-size: 24rpx;
	color: #5a6650;
	font-weight: 500;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
	letter-spacing: 0.3rpx;
	line-height: 56rpx;
}
.notice-arrow {
	flex-shrink: 0;
	font-size: 28rpx;
	margin-left: 8rpx;
	font-weight: 300;
	color: #a3b08a;
	transition: transform 0.2s ease;
}
.notice-bar:active .notice-arrow {
	transform: translateX(4rpx);
}

/* 活动卡片横滑 */
.activity-card-h {
	width: 400rpx;
	margin-right: 20rpx;
	background: #fff;
	border-radius: 28rpx;
	overflow: hidden;
	box-shadow: 0 10rpx 32rpx rgba(90, 120, 50, 0.08);
	border: none;
	display: inline-block;
	transition: transform 0.2s ease;
}
.activity-card-h:active {
	transform: scale(0.98);
}
.activity-cover {
	width: 400rpx;
	height: 200rpx;
}
.activity-cover-ph {
	width: 400rpx;
	height: 200rpx;
	background: linear-gradient(145deg, #a8e063 0%, #7cb342 100%);
	display: flex;
	align-items: center;
	justify-content: center;
}
.activity-cover-icon {
	font-size: 64rpx;
	opacity: 0.7;
}
.activity-info {
	padding: 22rpx 24rpx 24rpx;
}
.activity-title {
	font-size: 28rpx;
	color: #1a1a1a;
	font-weight: 700;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
	display: block;
	width: 352rpx;
	line-height: 1.4;
}
.activity-meta {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-top: 12rpx;
}
.activity-time {
	font-size: 22rpx;
	color: #9aa88a;
}
.activity-count {
	font-size: 22rpx;
	color: #9aa88a;
}
.activity-status {
	display: inline-block;
	margin-top: 14rpx;
	font-size: 20rpx;
	font-weight: 700;
	padding: 6rpx 14rpx;
	border-radius: 999rpx;
	border: none;
	background: rgba(124, 179, 66, 0.12);
	color: #558b2f !important;
}

/* 轮播图 */
.banner-swiper {
	height: 380rpx;
	margin: -290rpx 28rpx 0;
	border-radius: 32rpx;
	overflow: hidden;
	box-shadow: 0 16rpx 48rpx rgba(70, 100, 30, 0.12);
	position: relative;
	z-index: 9999;
	border: none;
}
.banner-img {
	width: 100%;
	height: 100%;
}

/* 通用区块 */
.section {
	margin: 36rpx 0;
	padding: 0 28rpx;
}
.section-header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-bottom: 22rpx;
}
.section-title-wrap {
	position: relative;
	display: flex;
	align-items: center;
}
.section-title {
	font-size: 36rpx;
	font-weight: 800;
	letter-spacing: 0.5rpx;
	padding-left: 20rpx;
	color: #1a1a1a;
}
.section-bar {
	width: 10rpx;
	height: 36rpx;
	border-radius: 5rpx;
	position: absolute;
	left: 0;
	top: 50%;
	transform: translateY(-50%);
}
.section-more {
	font-size: 26rpx;
	color: #1a1a1a;
}

/* 查看更多链接 */
.more-link {
	display: flex;
	align-items: center;
	padding: 8rpx 18rpx;
	border-radius: 999rpx;
	transition: all 0.2s ease;
	background: rgba(124, 179, 66, 0.08);
}
.more-link:active {
	background: rgba(124, 179, 66, 0.14);
}
.more-text {
	font-size: 24rpx;
	font-weight: 600;
	letter-spacing: 0.2rpx;
	line-height: 1.7;
	color: #558b2f;
}
.more-arrow {
	font-size: 24rpx;
	color: #7cb342;
	margin-left: 4rpx;
	transition: transform 0.25s ease;
	display: inline-block;
}
.more-link:active .more-arrow {
	transform: translateX(3rpx);
}

/* 分类宫格 */
.category-scroll {
	white-space: nowrap;
}
.category-grid {
	display: inline-flex;
	flex-wrap: nowrap;
	background: #ffffff;
	border-radius: 32rpx;
	padding: 32rpx 8rpx 24rpx;
	box-shadow: 0 10rpx 32rpx rgba(90, 120, 50, 0.08);
	border: none;
}
.category-item {
	width: 160rpx;
	display: inline-flex;
	flex-direction: column;
	align-items: center;
	margin-bottom: 8rpx;
	margin-right: 4rpx;
	transition: transform 0.2s ease;
}
.category-item:active {
	transform: scale(0.95);
}
.category-icon {
	width: 100rpx;
	height: 100rpx;
	border-radius: 28rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	margin-bottom: 14rpx;
	position: relative;
	border: none;
	box-shadow: 0 6rpx 16rpx rgba(124, 179, 66, 0.1);
	overflow: hidden;
}
.icon-glow {
	display: none;
}
.icon-img {
	width: 68rpx;
	height: 68rpx;
	position: relative;
	z-index: 2;
}
.icon-emoji {
	font-size: 40rpx;
	position: relative;
	z-index: 2;
}
.category-name {
	font-size: 23rpx;
	color: #1a1a1a;
	text-align: center;
	font-weight: 500;
	letter-spacing: 0.2rpx;
}

/* 热门资源横滑 */
.hot-scroll {
	white-space: nowrap;
}
.hot-list {
	display: inline-flex;
	padding: 4rpx 0;
}
.hot-card {
	width: 300rpx;
	margin-right: 20rpx;
	background: #fff;
	border-radius: 32rpx;
	overflow: hidden;
	box-shadow: 0 10rpx 32rpx rgba(90, 120, 50, 0.08);
	border: none;
	display: inline-block;
	transition: transform 0.2s ease;
	position: relative;
}
.hot-card::after {
	display: none;
}
.hot-card:active {
	transform: scale(0.98);
}
.hot-cover {
	width: 300rpx;
	height: 210rpx;
}
.hot-info {
	padding: 20rpx 22rpx 24rpx;
}
.hot-title {
	font-size: 26rpx;
	color: #1a1a1a;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
	display: block;
	width: 256rpx;
	font-weight: 600;
	letter-spacing: 0.1rpx;
	line-height: 1.45;
}
.hot-bottom {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-top: 14rpx;
}
.hot-price {
	font-size: 28rpx;
	color: #1a1a1a;
	font-weight: 800;
	letter-spacing: -0.5rpx;
}
.hot-price.free {
	font-size: 22rpx;
	font-weight: 700;
	padding: 6rpx 14rpx;
	border-radius: 999rpx;
	background: rgba(124, 179, 66, 0.12);
	color: #558b2f;
}
.hot-count {
	font-size: 21rpx;
	color: #9aa88a;
	letter-spacing: 0.2rpx;
	line-height: 1.7;
}

/* 推荐资源列表 */
.recommend-card {
	display: flex;
	background: #fff;
	border-radius: 32rpx;
	margin-bottom: 20rpx;
	overflow: hidden;
	box-shadow: 0 10rpx 32rpx rgba(90, 120, 50, 0.08);
	border: none;
	transition: transform 0.2s ease;
	position: relative;
}
/* 推荐卡片右上角精致圆角装饰 */
.recommend-card::before {
	display: none;
}
.recommend-card:active {
	transform: scale(0.98);
}
.recommend-cover {
	width: 260rpx;
	height: 210rpx;
	flex-shrink: 0;
}
.recommend-info {
	flex: 1;
	padding: 24rpx 26rpx;
	display: flex;
	flex-direction: column;
	justify-content: space-between;
	min-width: 0;
}
.recommend-title {
	font-size: 28rpx;
	color: #1a1a1a;
	font-weight: 700;
	overflow: hidden;
	text-overflow: ellipsis;
	display: -webkit-box;
	-webkit-line-clamp: 2;
	-webkit-box-orient: vertical;
	line-height: 1.55;
	letter-spacing: 0.5rpx;
}
.recommend-tags {
	display: flex;
	gap: 12rpx;
	margin-top: 12rpx;
	flex-wrap: wrap;
}
.tag {
	font-size: 20rpx;
	background: #eef2e4;
	padding: 5rpx 16rpx;
	border-radius: 999rpx;
	font-weight: 600;
	letter-spacing: 0.3rpx;
	border: none;
	color: #6b7a5a;
}
.tag.type {
	color: #558b2f;
	background: rgba(124, 179, 66, 0.12);
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
	color: #9aa88a;
	letter-spacing: 0.3rpx;
	line-height: 1.7;
}
.recommend-price {
	font-size: 32rpx;
	color: #1a1a1a;
	font-weight: 800;
}
.recommend-price.free {
	font-size: 22rpx;
	font-weight: 700;
	padding: 4rpx 14rpx;
	border-radius: 999rpx;
	background: rgba(124, 179, 66, 0.12);
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
		0 2rpx 8rpx rgba(0,0,0,0.03),
		0 8rpx 24rpx rgba(0,0,0,0.06),
		0 16rpx 40rpx rgba(0,0,0,0.04);
	border: 1rpx solid rgba(0,0,0,0.03);
	display: inline-block;
	transition: transform 0.2s ease, box-shadow 0.25s ease;
	position: relative;
}
.rank-card-h:active {
	transform: scale(0.97);
	box-shadow:
		0 1rpx 4rpx rgba(0,0,0,0.04),
		0 4rpx 12rpx rgba(0,0,0,0.06);
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
	color: #1c2333;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
	display: block;
	width: 300rpx;
	font-weight: 600;
	letter-spacing: 0.5rpx;
	line-height: 1.6;
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

/* 空状态 */
.empty {
	display: flex;
	flex-direction: column;
	align-items: center;
	padding: 100rpx 0 120rpx;
}
.empty-icon {
	font-size: 80rpx;
	margin-bottom: 24rpx;
	width: 160rpx;
	height: 160rpx;
	line-height: 160rpx;
	text-align: center;
	background: linear-gradient(160deg, #ffffff 0%, #e8f2dc 100%);
	border-radius: 50%;
	box-shadow: 0 8rpx 24rpx rgba(124, 179, 66, 0.1);
	border: none;
}
.empty-text {
	font-size: 28rpx;
	color: #a3b08a;
	letter-spacing: 1rpx;
	font-weight: 500;
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
	background: linear-gradient(90deg, #eef0f5 25%, #f5f6fa 37%, #eef0f5 63%);
	background-size: 400% 100%;
	animation: skeleton-shimmer 1.6s ease-in-out infinite;
	border-radius: 28rpx;
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
	background: linear-gradient(135deg, rgba(0,0,0,0.04) 0%, rgba(0,0,0,0.02) 100%);
	border: 1rpx solid rgba(0,0,0,0.03);
	box-shadow:
		0 4rpx 16rpx rgba(0,0,0,0.04),
		inset 0 1rpx 0 rgba(255,255,255,0.4);
	margin-bottom: 18rpx;
}

.skeleton-text {
	height: 28rpx;
	border-radius: 8rpx;
	background: linear-gradient(90deg, #eef0f5 25%, #f5f6fa 37%, #eef0f5 63%);
	background-size: 400% 100%;
	animation: skeleton-shimmer 1.6s ease-in-out infinite;
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
	border-radius: 28rpx;
	box-shadow:
		0 2rpx 8rpx rgba(0,0,0,0.03),
		0 8rpx 24rpx rgba(0,0,0,0.05);
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
