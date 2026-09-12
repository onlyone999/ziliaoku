<template>
	<view class="page">
		<!-- 左侧一级分类栏 -->
		<view class="sidebar">
			<scroll-view scroll-y class="sidebar-scroll">
				<view
					class="sidebar-item"
					:class="{ active: currentCatId === item.id }"
					:style="currentCatId === item.id ? 'color:' + tc.primaryDark + ';background:' + tc.tintLight + ';' : ''"
					v-for="item in categories"
					:key="item.id"
					@tap="selectCategory(item)"
				>
					<view class="sidebar-indicator" v-if="currentCatId === item.id" :style="'background:' + tc.primary + ';'"></view>
					<text>{{ item.name }}</text>
					<view class="sidebar-dot" v-if="currentCatId === item.id" :style="'background:' + tc.primary + ';box-shadow:0 0 8rpx rgba(' + tc.rgbPrimary + ',0.5);'"></view>
				</view>
			</scroll-view>
		</view>

		<!-- 右侧内容区 -->
		<view class="content">
			<!-- 二级分类标签 -->
			<scroll-view scroll-x class="sub-tabs" v-if="subCategories.length > 0">
				<view class="sub-tabs-inner">
					<view class="sub-tab" :class="{ active: currentSubId === 0 }" :style="currentSubId === 0 ? 'background:' + tc.primary + ';' : ''" @tap="selectSub(0)">全部</view>
					<view class="sub-tab" :class="{ active: currentSubId === item.id }" :style="currentSubId === item.id ? 'background:' + tc.primary + ';' : ''" v-for="item in subCategories" :key="item.id" @tap="selectSub(item.id)">
						{{ item.name }}
					</view>
				</view>
			</scroll-view>

			<!-- 三级分类标签 -->
			<scroll-view scroll-x class="sub-tabs sub-sub-tabs" v-if="subSubCategories.length > 0">
				<view class="sub-tabs-inner">
					<view class="sub-tab small" :class="{ active: currentSubSubId === 0 }" :style="currentSubSubId === 0 ? 'background:' + tc.primary + ';' : ''" @tap="selectSubSub(0)">全部</view>
					<view class="sub-tab small" :class="{ active: currentSubSubId === item.id }" :style="currentSubSubId === item.id ? 'background:' + tc.primary + ';' : ''" v-for="item in subSubCategories" :key="item.id" @tap="selectSubSub(item.id)">
						{{ item.name }}
					</view>
				</view>
			</scroll-view>

			<!-- 四级分类标签 -->
			<scroll-view scroll-x class="sub-tabs sub-sub-tabs" v-if="subSubSubCategories.length > 0">
				<view class="sub-tabs-inner">
					<view class="sub-tab tiny" :class="{ active: currentSubSubSubId === 0 }" :style="currentSubSubSubId === 0 ? 'background:' + tc.primary + ';' : ''" @tap="selectSubSubSub(0)">全部</view>
					<view class="sub-tab tiny" :class="{ active: currentSubSubSubId === item.id }" :style="currentSubSubSubId === item.id ? 'background:' + tc.primary + ';' : ''" v-for="item in subSubSubCategories" :key="item.id" @tap="selectSubSubSub(item.id)">
						{{ item.name }}
					</view>
				</view>
			</scroll-view>

			<!-- 排序栏 -->
			<view class="sort-bar">
				<view class="sort-left">
					<view class="sort-item" :class="{ active: sortBy === 'newest' }" :style="sortBy === 'newest' ? 'color:' + tc.primaryDark + ';' : ''" @tap="changeSort('newest')">最新<view class="sort-line" v-if="sortBy === 'newest'" :style="'background:' + tc.primary + ';'"></view></view>
					<view class="sort-item" :class="{ active: sortBy === 'downloads' }" :style="sortBy === 'downloads' ? 'color:' + tc.primaryDark + ';' : ''" @tap="changeSort('downloads')">最热<view class="sort-line" v-if="sortBy === 'downloads'" :style="'background:' + tc.primary + ';'"></view></view>
					<view class="sort-item" :class="{ active: sortBy === 'price' }" :style="sortBy === 'price' ? 'color:' + tc.primaryDark + ';' : ''" @tap="changeSort('price')">价格<view class="sort-line" v-if="sortBy === 'price'" :style="'background:' + tc.primary + ';'"></view></view>
				</view>
			</view>

			<!-- 资源列表 -->
			<scroll-view scroll-y class="resource-scroll">
				<view class="resource-list" v-if="resources.length > 0">
					<view class="resource-card" v-for="item in resources" :key="item.id" @tap="goDetail(item)">
						<image class="card-cover" :src="fixUrl(item.cover_url)" mode="aspectFill"></image>
						<view class="card-info">
							<text class="card-title">{{ item.title }}</text>
							<view class="card-tags">
								<text class="card-badge" :style="'background:' + tc.badgeBg + ';color:' + tc.primary + ';border:2rpx solid ' + tc.badgeBorder + ';'" v-if="item.category_name">{{ item.category_name }}</text>
							</view>
							<view class="card-bottom">
								<text class="download-count">↓ {{ item.download_count }}</text>
								<text class="card-price" v-if="item.price > 0">¥{{ item.price }}</text>
								<text class="card-price free" :style="'background:' + tc.badgeBg + ';color:' + tc.primary + ';border:2rpx solid ' + tc.badgeBorder + ';'" v-else>免费</text>
							</view>
						</view>
					</view>
				</view>

				<!-- 空状态 -->
				<view class="empty" v-if="!loading && resources.length === 0">
					<text class="empty-icon">📦</text>
					<text class="empty-text">暂无资源</text>
				</view>

				<!-- 分页栏 -->
				<view class="pager-bar" v-if="resources.length > 0">
					<view class="pager-btn" :class="{ disabled: page <= 1 }" :style="page > 1 ? 'background:' + tc.primary + ';' : ''" @tap="goPage(page - 1)">
						<text>‹ 上一页</text>
					</view>
					<view class="pager-info">
						<text class="pager-current">{{ page }}/{{ totalPages || 1 }}</text>
						<text class="pager-total">共{{ total }}条</text>
					</view>
					<view class="pager-btn" :class="{ disabled: page >= totalPages }" :style="page < totalPages ? 'background:' + tc.primary + ';' : ''" @tap="goPage(page + 1)">
						<text>下一页 ›</text>
					</view>
				</view>

				<!-- 加载状态 -->
				<view class="load-status">
					<text v-if="loading">加载中...</text>
				</view>
			</scroll-view>
		</view>
	</view>
</template>

<script>
import http, { BASE_URL } from '@/utils/http.js';

export default {
	data() {
		return {
			categories: [],
			subCategories: [],
			subSubCategories: [],
			subSubSubCategories: [],
			currentCatId: 0,
			currentSubId: 0,
			currentSubSubId: 0,
			currentSubSubSubId: 0,
			sortBy: 'newest',
			resources: [],
			page: 1,
			pageSize: 15,
			total: 0,
			totalPages: 0,
			loading: false,
			type: ''
		};
	},
	onLoad(options) {
		if (options && options.type) {
			this.type = options.type;
		}
		this.loadCategories();
	},
	onShow() {
		// 主题导航栏颜色
		const themeName = uni.getStorageSync('theme') || 'green';
		const themeColors = { green:'#2ed573', blue:'#3b82f6', purple:'#8b5cf6', orange:'#f59e0b', pink:'#ec4899', red:'#ef4444' };
		const navBg = themeColors[themeName] || '#2ed573';
		console.log('[Category] onShow theme:', themeName, 'navBg:', navBg);
		uni.setNavigationBarColor({ frontColor: '#ffffff', backgroundColor: navBg });
		uni.setTabBarStyle({ selectedColor: navBg });

		const app = getApp();
		if (app.globalData && app.globalData.categoryFilter) {
			this.type = app.globalData.categoryFilter;
			app.globalData.categoryFilter = '';
			this.resetAndLoad();
		}
	},
	methods: {
		// 加载一级分类
		async loadCategories() {
			try {
				const res = await http.get('/api/category/list');
				if (res.code === 0 && res.data && res.data.length > 0) {
					// 只取一级分类（parent_id=0）
					this.categories = res.data.filter(c => c.parent_id === 0);
					if (this.categories.length > 0) {
						this.currentCatId = this.categories[0].id;
						this.loadSubCategories(this.currentCatId);
						this.loadResources();
					}
				}
			} catch (e) {
				console.error('加载分类失败', e);
			}
		},

		// 加载二级分类
		async loadSubCategories(catId) {
			try {
				const res = await http.get('/api/category/sub', { parent_id: catId });
				if (res.code === 0) {
					this.subCategories = res.data || [];
				}
			} catch (e) {
				this.subCategories = [];
			}
			this.currentSubId = 0;
			this.subSubCategories = [];
			this.subSubSubCategories = [];
		},

		// 加载三级分类
		async loadSubSubCategories(parentId) {
			try {
				const res = await http.get('/api/category/sub', { parent_id: parentId });
				if (res.code === 0) {
					this.subSubCategories = res.data || [];
				}
			} catch (e) {
				this.subSubCategories = [];
			}
		},

		// 加载四级分类
		async loadSubSubSubCategories(parentId) {
			try {
				const res = await http.get('/api/category/sub', { parent_id: parentId });
				if (res.code === 0) {
					this.subSubSubCategories = res.data || [];
				}
			} catch (e) {
				this.subSubSubCategories = [];
			}
		},

		// 选择一级分类
		selectCategory(item) {
			if (this.currentCatId === item.id) return;
			this.currentCatId = item.id;
			this.currentSubId = 0;
			this.currentSubSubId = 0;
			this.currentSubSubSubId = 0;
			this.subSubCategories = [];
			this.subSubSubCategories = [];
			this.loadSubCategories(item.id);
			this.resetAndLoad();
		},

		// 选择二级分类
		selectSub(subId) {
			if (this.currentSubId === subId) return;
			this.currentSubId = subId;
			this.currentSubSubId = 0;
			this.currentSubSubSubId = 0;
			this.subSubCategories = [];
			this.subSubSubCategories = [];
			if (subId > 0) {
				this.loadSubSubCategories(subId);
			}
			this.resetAndLoad();
		},

		// 选择三级分类
		selectSubSub(subSubId) {
			if (this.currentSubSubId === subSubId) return;
			this.currentSubSubId = subSubId;
			this.currentSubSubSubId = 0;
			this.subSubSubCategories = [];
			if (subSubId > 0) {
				this.loadSubSubSubCategories(subSubId);
			}
			this.resetAndLoad();
		},

		// 选择四级分类
		selectSubSubSub(subSubSubId) {
			if (this.currentSubSubSubId === subSubSubId) return;
			this.currentSubSubSubId = subSubSubId;
			this.resetAndLoad();
		},

		// 加载资源列表
		async loadResources() {
			if (this.loading) return;
			this.loading = true;
			try {
				// 使用最深层级的分类ID
				let categoryId = this.currentCatId;
				if (this.currentSubSubSubId > 0) {
					categoryId = this.currentSubSubSubId;
				} else if (this.currentSubSubId > 0) {
					categoryId = this.currentSubSubId;
				} else if (this.currentSubId > 0) {
					categoryId = this.currentSubId;
				}
				const params = {
					category_id: categoryId || undefined,
					sort: this.sortBy,
					page: this.page,
					page_size: this.pageSize
				};
				const res = await http.get('/api/resource/list', params);
				if (res.code === 0) {
					this.resources = res.data.list || res.data || [];
					this.total = res.data.total || this.resources.length;
					this.totalPages = res.data.total_pages || Math.ceil(this.total / this.pageSize) || 1;
				}
			} catch (e) {
				console.error('加载资源失败', e);
			} finally {
				this.loading = false;
			}
		},

		changeSort(sort) {
			if (this.sortBy === sort) return;
			this.sortBy = sort;
			this.resetAndLoad();
		},

		resetAndLoad() {
			this.page = 1;
			this.total = 0;
			this.totalPages = 0;
			this.resources = [];
			this.loadResources();
		},

		goPage(p) {
			if (p < 1 || p > this.totalPages || this.loading) return;
			this.page = p;
			this.loadResources();
			// 滚动到顶部
			uni.pageScrollTo ? uni.pageScrollTo({ scrollTop: 0, duration: 200 }) : '';
		},

		goDetail(item) {
			uni.navigateTo({ url: '/pages/resource/detail?id=' + item.id });
		},
		fixUrl(url) {
			if (!url) return '';
			return url.startsWith('http') ? url : BASE_URL + url;
		}
	}
};
</script>

<style scoped>
.page {
	display: flex;
	height: 100vh;
	background: linear-gradient(180deg, #f0f2ff 0%, #f5f6fa 200rpx);
}

/* 左侧边栏 */
.sidebar {
	width: 180rpx;
	background: linear-gradient(180deg, #ffffff 0%, #fafafe 100%);
	flex-shrink: 0;
	border-right: 1rpx solid rgba(46,213,115, 0.08);
}
.sidebar-scroll {
	height: 100%;
}
.sidebar-item {
	padding: 30rpx 20rpx;
	text-align: center;
	font-size: 26rpx;
	color: #666;
	position: relative;
	transition: all 0.3s ease;
	border-left: 6rpx solid transparent;
}
.sidebar-item.active {
	font-weight: 700;
	border-left: 6rpx solid transparent;
	position: relative;
}
.sidebar-indicator {
	position: absolute;
	left: 0;
	top: 15%;
	height: 70%;
	width: 6rpx;
	border-radius: 0 4rpx 4rpx 0;
	box-shadow: 2rpx 0 8rpx rgba(0,0,0,0.15);
}
/* 侧边栏 active 项右侧装饰点 */
.sidebar-dot {
	width: 10rpx;
	height: 10rpx;
	border-radius: 50%;
	background: #2ed573;
	position: absolute;
	right: 10rpx;
	top: 50%;
	transform: translateY(-50%);
}

/* 右侧内容 */
.content {
	flex: 1;
	display: flex;
	flex-direction: column;
	min-width: 0;
	background: linear-gradient(180deg, rgba(236, 253, 245, 0.5) 0%, #f7f8fc 100%);
}

/* 子分类标签 */
.sub-tabs {
	white-space: nowrap;
	background: rgba(255, 255, 255, 0.9);
	backdrop-filter: blur(10px);
	padding: 16rpx 0;
	border-bottom: 1rpx solid rgba(46,213,115, 0.06);
	flex-shrink: 0;
}
.sub-tabs-inner {
	display: inline-flex;
	padding: 0 16rpx;
}
.sub-tab {
	padding: 10rpx 24rpx;
	margin: 0 8rpx;
	font-size: 24rpx;
	color: #666;
	background: #f0f1f5;
	border-radius: 24rpx;
	display: inline-flex;
	align-items: center;
	transition: all 0.3s ease;
	border: 1rpx solid transparent;
	background-size: 200% 100%;
	background-image: linear-gradient(135deg, #f0f1f5 0%, #f0f1f5 50%, #d1fae5 100%);
}
.sub-tab:active {
	background-position: 100% 0;
}
.sub-tab.active {
	color: #fff;
	background-size: 200% 100%;
	animation: tagGradientShift 5s ease infinite;
	box-shadow: 0 4rpx 16rpx rgba(0,0,0,0.15);
}
@keyframes tagGradientShift {
	0% { background-position: 0% 0; }
	50% { background-position: 100% 0; }
	100% { background-position: 0% 0; }
}

/* 三级分类 */
.sub-sub-tabs {
	background: rgba(248, 249, 252, 0.95);
}
.sub-tab.small {
	padding: 8rpx 18rpx;
	font-size: 22rpx;
	border-radius: 20rpx;
}
.sub-tab.small.active {
	box-shadow: 0 3rpx 12rpx rgba(46,213,115, 0.3);
}

/* 四级分类 */
.sub-tab.tiny {
	padding: 6rpx 14rpx;
	font-size: 20rpx;
	border-radius: 16rpx;
	margin: 0 4rpx;
}
.sub-tab.tiny.active {
	box-shadow: 0 2rpx 10rpx rgba(46,213,115, 0.25);
}

/* 排序栏 */
.sort-bar {
	display: flex;
	justify-content: space-between;
	align-items: center;
	padding: 16rpx 24rpx;
	background: rgba(255, 255, 255, 0.95);
	border-bottom: 1rpx solid rgba(46,213,115, 0.06);
	flex-shrink: 0;
}
.sort-left {
	display: flex;
}
.sort-item {
	margin-right: 40rpx;
	font-size: 26rpx;
	color: #666;
	position: relative;
	padding-bottom: 8rpx;
	transition: color 0.3s ease;
}
.sort-item.active {
	font-weight: 600;
}
.sort-line {
	position: absolute;
	bottom: 0;
	left: 50%;
	transform: translateX(-50%);
	width: 40rpx;
	height: 4rpx;
	border-radius: 2rpx;
}

/* 资源列表 */
.resource-scroll {
	flex: 1;
}
.resource-list {
	padding: 20rpx 20rpx;
}
.resource-card {
	display: flex;
	background: #fff;
	border-radius: 20rpx;
	margin-bottom: 20rpx;
	overflow: hidden;
	box-shadow: 0 4rpx 20rpx rgba(46,213,115, 0.06), 0 2rpx 8rpx rgba(0, 0, 0, 0.03);
	transition: all 0.3s ease;
	border: 1rpx solid rgba(46,213,115, 0.04);
	position: relative;
}
/* 卡片顶部精细装饰线 */
.resource-card::before {
	content: '';
	position: absolute;
	top: 0;
	left: 10%;
	right: 10%;
	height: 2rpx;
	background: linear-gradient(90deg, transparent 0%, #26c67a 30%, #2ed573 50%, #26c67a 70%, transparent 100%);
	border-radius: 1rpx;
	filter: blur(1rpx);
	box-shadow: 0 0 4rpx rgba(46,213,115, 0.25), 0 0 8rpx rgba(46,213,115, 0.08);
	opacity: 0.5;
	z-index: 1;
}
.resource-card:active {
	transform: scale(0.97);
	box-shadow: 0 2rpx 10rpx rgba(46,213,115, 0.1);
}
/* 封面图片 hover 缩放容器 */
.card-cover {
	width: 200rpx;
	height: 170rpx;
	flex-shrink: 0;
	border-radius: 12rpx;
	margin: 12rpx;
	box-shadow: 0 2rpx 10rpx rgba(0, 0, 0, 0.06);
	transition: transform 0.4s ease;
}
.resource-card:active .card-cover {
	transform: scale(1.02);
}
.card-info {
	flex: 1;
	padding: 18rpx 20rpx 18rpx 8rpx;
	display: flex;
	flex-direction: column;
	justify-content: space-between;
	min-width: 0;
}
.card-title {
	font-size: 28rpx;
	color: #333;
	font-weight: 600;
	overflow: hidden;
	text-overflow: ellipsis;
	display: -webkit-box;
	-webkit-line-clamp: 2;
	-webkit-box-orient: vertical;
	line-height: 1.5;
}
.card-tags {
	margin-top: 8rpx;
	display: flex;
	flex-wrap: wrap;
	gap: 8rpx;
}
.card-badge {
	display: inline-block;
	font-size: 20rpx;
	padding: 4rpx 14rpx;
	border-radius: 16rpx;
	font-weight: 500;
}
.card-badge:active {
	opacity: 0.85;
}
.card-bottom {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-top: auto;
}
.download-count {
	font-size: 22rpx;
	color: #aaa;
}
/* 价格区域微弱渐变背景 */
.card-price {
	font-size: 28rpx;
	font-weight: 700;
	background: linear-gradient(135deg, #ff6b6b, #ee5a24);
	padding: 4rpx 16rpx;
	border-radius: 12rpx;
	background-color: linear-gradient(135deg, rgba(255, 107, 107, 0.06), rgba(238, 90, 36, 0.04));
	position: relative;
}
.card-price::before {
	content: '';
	position: absolute;
	inset: 0;
	border-radius: 12rpx;
	background: linear-gradient(135deg, rgba(255, 107, 107, 0.06), rgba(238, 90, 36, 0.03));
	z-index: -1;
}
.card-price.free {
	font-size: 24rpx;
	font-weight: 600;
	border-radius: 8rpx;
	padding: 2rpx 12rpx;
}
.card-price.free::before {
	display: none;
}

/* 分页栏 */
.pager-bar {
	display: flex;
	align-items: center;
	justify-content: space-between;
	padding: 20rpx 24rpx;
	margin: 0 0 20rpx;
	background: #fff;
	border-radius: 16rpx;
	box-shadow: 0 2rpx 12rpx rgba(46,213,115, 0.06);
}
.pager-btn {
	padding: 12rpx 28rpx;
	border-radius: 24rpx;
	box-shadow: 0 4rpx 14rpx rgba(0,0,0,0.1);
	transition: all 0.2s ease;
}
.pager-btn text {
	font-size: 24rpx;
	color: #fff;
	font-weight: 600;
}
.pager-btn:active {
	transform: scale(0.95);
	box-shadow: 0 2rpx 8rpx rgba(0,0,0,0.1);
}
.pager-btn.disabled {
	background: #e8e8ef;
	box-shadow: none;
}
.pager-btn.disabled text {
	color: #b0b0c0;
}
.pager-info {
	display: flex;
	flex-direction: column;
	align-items: center;
}
.pager-current {
	font-size: 26rpx;
	color: #333;
	font-weight: 700;
}
.pager-total {
	font-size: 20rpx;
	color: #aaa;
	margin-top: 4rpx;
}

/* 空状态 */
.empty {
	display: flex;
	flex-direction: column;
	align-items: center;
	padding: 120rpx 0;
	position: relative;
}
.empty::before {
	content: '';
	position: absolute;
	top: 80rpx;
	width: 200rpx;
	height: 200rpx;
	border-radius: 50%;
	background: linear-gradient(135deg, rgba(46,213,115, 0.06) 0%, rgba(139, 124, 247, 0.1) 100%);
	border: 2rpx dashed rgba(46,213,115, 0.15);
	z-index: 0;
}
.empty-icon {
	font-size: 80rpx;
	margin-bottom: 20rpx;
	position: relative;
	z-index: 1;
	animation: breathe 3s ease-in-out infinite;
}
@keyframes breathe {
	0%, 100% { transform: scale(1); }
	50% { transform: scale(1.12); }
}
.empty-text {
	font-size: 28rpx;
	color: #999;
	position: relative;
	z-index: 1;
}

/* 加载状态 */
.load-status {
	text-align: center;
	padding: 30rpx 0;
	font-size: 24rpx;
	color: #aaa;
}
.load-status text {
	display: inline-block;
	background: linear-gradient(90deg, #aaa 0%, #2ed573 40%, #aaa 80%);
	background-size: 200% 100%;
	animation: loadShimmer 2s linear infinite;
}
@keyframes loadShimmer {
	0% { background-position: 200% 0; }
	100% { background-position: -200% 0; }
}
</style>
