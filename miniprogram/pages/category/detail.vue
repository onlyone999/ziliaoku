<template>
	<view class="page">
		<!-- 分类信息头部 -->
		<view class="cat-header" :style="'background:' + (categoryInfo.color || tc.primary) + ';'">
			<view class="cat-header-content">
				<view class="cat-icon" v-if="categoryInfo.icon && isIconUrl">
					<image :src="categoryInfo.icon" class="cat-icon-img"></image>
				</view>
				<view class="cat-icon" v-else-if="categoryInfo.icon">
					<text class="cat-emoji">{{ categoryInfo.icon }}</text>
				</view>
				<view class="cat-icon" v-else>
					<text class="cat-icon-text">{{ (categoryInfo.name || '').charAt(0) }}</text>
				</view>
				<view class="cat-meta">
					<text class="cat-name">{{ categoryInfo.name }}</text>
					<text class="cat-desc">{{ categoryInfo.description || '发现优质资源' }}</text>
					<text class="cat-count">{{ total || categoryInfo.resource_count || 0 }} 个资源</text>
				</view>
			</view>
		</view>

		<!-- 筛选排序 -->
		<view class="filter-bar">
			<scroll-view scroll-x class="sub-scroll" v-if="subCategories.length > 0">
				<view class="sub-inner">
					<view
						class="sub-tag"
						:class="{ active: currentSubId === 0 }"
						:style="currentSubId === 0 ? 'color:#fff;background:' + tc.primary + ';box-shadow:0 4rpx 12rpx rgba(' + tc.rgbPrimary + ',0.3);' : ''"
						@tap="selectSub(0)"
					>全部</view>
					<view
						class="sub-tag"
						:class="{ active: currentSubId === item.id }"
						:style="currentSubId === item.id ? 'color:#fff;background:' + tc.primary + ';box-shadow:0 4rpx 12rpx rgba(' + tc.rgbPrimary + ',0.3);' : ''"
						v-for="item in subCategories"
						:key="item.id"
						@tap="selectSub(item.id)"
					>{{ item.name }}</view>
				</view>
			</scroll-view>
			<view class="sort-row">
				<view
					class="sort-btn"
					:class="{ active: sortBy === 'newest' }"
					:style="sortBy === 'newest' ? 'color:#fff;background:' + tc.primary + ';box-shadow:0 4rpx 12rpx rgba(' + tc.rgbPrimary + ',0.3);' : ''"
					@tap="changeSort('newest')"
				>最新</view>
				<view
					class="sort-btn"
					:class="{ active: sortBy === 'downloads' }"
					:style="sortBy === 'downloads' ? 'color:#fff;background:' + tc.primary + ';box-shadow:0 4rpx 12rpx rgba(' + tc.rgbPrimary + ',0.3);' : ''"
					@tap="changeSort('downloads')"
				>最热</view>
				<view
					class="sort-btn"
					:class="{ active: sortBy === 'price_asc' }"
					:style="sortBy === 'price_asc' ? 'color:#fff;background:' + tc.primary + ';box-shadow:0 4rpx 12rpx rgba(' + tc.rgbPrimary + ',0.3);' : ''"
					@tap="changeSort('price_asc')"
				>价格↑</view>
				<view
					class="sort-btn"
					:class="{ active: sortBy === 'price_desc' }"
					:style="sortBy === 'price_desc' ? 'color:#fff;background:' + tc.primary + ';box-shadow:0 4rpx 12rpx rgba(' + tc.rgbPrimary + ',0.3);' : ''"
					@tap="changeSort('price_desc')"
				>价格↓</view>
			</view>
		</view>

		<!-- 资源列表 -->
		<scroll-view
			scroll-y
			class="resource-scroll"
			:refresher-enabled="true"
			:refresher-triggered="isRefreshing"
			@refresherrefresh="onRefresh"
		>
			<view class="resource-list" v-if="resources.length > 0">
				<view
					class="resource-card"
					v-for="item in resources"
					:key="item.id"
					@tap="goDetail(item)"
				>
					<image class="card-cover" :src="fixUrl(item.cover_url)" mode="aspectFill"></image>
					<view class="card-info">
						<text class="card-title">{{ item.title }}</text>
						<view class="card-tags">
							<text class="badge" :style="'background:' + tc.badgeBg + ';color:' + tc.primary + ';border:2rpx solid ' + tc.badgeBorder + ';'">{{ item.category_name }}</text>
							<text class="badge type" v-if="item.file_type" :style="'color:' + tc.primary + ';'">{{ item.file_type }}</text>
						</view>
						<view class="card-bottom">
							<view class="card-stats">
								<text class="stat">{{ item.download_count }} 下载</text>
								<text class="stat">{{ item.view_count }} 浏览</text>
							</view>
							<text class="card-price" v-if="item.price > 0">¥{{ item.price }}</text>
							<text class="card-price free" v-else :style="'background:' + tc.badgeBg + ';color:' + tc.primary + ';border:2rpx solid ' + tc.badgeBorder + ';'">免费</text>
						</view>
					</view>
				</view>
			</view>

			<!-- 分页栏 -->
			<view class="pager-bar" v-if="resources.length > 0">
				<view class="pager-btn" :class="{ disabled: page <= 1 }" :style="page > 1 ? 'background:' + tc.primary + ';color:#fff;' : ''" @tap="goPage(page - 1)">
					<text>‹ 上一页</text>
				</view>
				<view class="pager-info">
					<text class="pager-current">{{ page }}/{{ totalPages || 1 }}</text>
					<text class="pager-total">共{{ total }}条</text>
				</view>
				<view class="pager-btn" :class="{ disabled: page >= totalPages }" :style="page < totalPages ? 'background:' + tc.primary + ';color:#fff;' : ''" @tap="goPage(page + 1)">
					<text>下一页 ›</text>
				</view>
			</view>

			<view class="empty" v-if="!loading && resources.length === 0">
				<text class="empty-icon">📭</text>
				<text class="empty-text">该分类下暂无资源</text>
			</view>

			<view class="load-status">
				<text v-if="loading">加载中...</text>
			</view>
		</scroll-view>
	</view>
</template>

<script>
import http, { BASE_URL } from '@/utils/http.js';

export default {
	data() {
		return {
			catId: 0,
			categoryInfo: {},
			subCategories: [],
			currentSubId: 0,
			sortBy: 'newest',
			resources: [],
			page: 1,
			total: 0,
			totalPages: 0,
			loading: false,
			noMore: false,
			isRefreshing: false
		};
	},
	onLoad(options) {
		if (options.id) {
			this.catId = parseInt(options.id);
			this.loadCategoryInfo();
			this.loadSubCategories();
			this.loadResources();
		}
	},
	computed: {
		isIconUrl() {
			var icon = this.categoryInfo.icon || '';
			return icon.indexOf('http') === 0 || icon.indexOf('/') === 0;
		}
	},
	methods: {
		async loadCategoryInfo() {
			try {
				const res = await http.get('/api/category/detail', { id: this.catId });
				if (res.code === 0 && res.data) {
					this.categoryInfo = res.data.category || res.data || {};
					uni.setNavigationBarTitle({ title: this.categoryInfo.name || '分类详情' });
				}
			} catch (e) {
				console.error('加载分类信息失败', e);
			}
		},
		async loadSubCategories() {
			try {
				const res = await http.get('/api/category/sub', { parent_id: this.catId });
				if (res.code === 0) {
					this.subCategories = res.data || [];
				}
			} catch (e) {
				this.subCategories = [];
			}
		},
		async loadResources() {
			if (this.loading) return;
			this.loading = true;
			try {
				const res = await http.get('/api/resource/list', {
					category_id: this.currentSubId > 0 ? this.currentSubId : this.catId,
					sort: this.sortBy,
					page: this.page,
					page_size: this.pageSize
				});
				if (res.code === 0) {
					const list = res.data.list || res.data || [];
					this.total = res.data.total || list.length;
					this.totalPages = res.data.total_pages || Math.ceil(this.total / this.pageSize) || 1;
					this.resources = list;
					this.noMore = this.page >= this.totalPages;
				}
			} catch (e) {
				console.error('加载资源失败', e);
			} finally {
				this.loading = false;
			}
		},
		selectSub(subId) {
			if (this.currentSubId === subId) return;
			this.currentSubId = subId;
			this.resetAndLoad();
		},
		changeSort(sort) {
			if (this.sortBy === sort) return;
			this.sortBy = sort;
			this.resetAndLoad();
		},
		resetAndLoad() {
			this.page = 1;
			this.noMore = false;
			this.resources = [];
			this.loadResources();
		},
		async onRefresh() {
			this.isRefreshing = true;
			this.resetAndLoad();
			this.isRefreshing = false;
		},
		goPage(p) {
			if (p < 1 || p > this.totalPages || this.loading) return;
			this.page = p;
			this.loadResources();
			uni.pageScrollTo && uni.pageScrollTo({ scrollTop: 0, duration: 200 });
		},
		goDetail(item) {
			uni.navigateTo({ url: `/pages/resource/detail?id=${item.id}` });
		},
		fixUrl(url) {
			if (!url) return '';
			return url.startsWith('http') ? url : BASE_URL + url;
		}
	},
	onShareAppMessage() {
		return {
			title: `${this.categoryInfo.name || '分类'} - 优质资源推荐`,
			path: `/pages/category/detail?id=${this.catId}`
		};
	}
};
</script>

<style scoped>
.page {
	display: flex;
	flex-direction: column;
	height: 100vh;
	background: linear-gradient(180deg, #eef0f8 0%, #f3f4f8 8%, #f7f8fc 20%, #fafbfe 50%, #f8f9fc 100%);
	overflow: hidden;
}

/* 分类头部 */
.cat-header {
	padding: 40rpx 30rpx 30rpx;
	position: relative;
	overflow: hidden;
	flex-shrink: 0;
	box-shadow: 0 2rpx 8rpx rgba(0,0,0,0.03);
}
.cat-header::after {
	content: '';
	position: absolute;
	top: -60rpx;
	right: -60rpx;
	width: 200rpx;
	height: 200rpx;
	border-radius: 50%;
	background: rgba(255, 255, 255, 0.12);
}
/* 分类头部装饰粒子效果 */
.cat-header::before {
	content: '';
	position: absolute;
	top: 20rpx;
	right: 80rpx;
	width: 8rpx;
	height: 8rpx;
	border-radius: 50%;
	background: rgba(255, 255, 255, 0.6);
	box-shadow:
		40rpx -10rpx 0 2rpx rgba(255, 255, 255, 0.3),
		80rpx 30rpx 0 1rpx rgba(255, 255, 255, 0.5),
		120rpx -20rpx 0 3rpx rgba(255, 255, 255, 0.2),
		200rpx 50rpx 0 2rpx rgba(255, 255, 255, 0.4),
		160rpx 80rpx 0 1rpx rgba(255, 255, 255, 0.3),
		60rpx 70rpx 0 2rpx rgba(255, 255, 255, 0.25),
		260rpx 20rpx 0 1rpx rgba(255, 255, 255, 0.35);
	animation: particleFloat 4s ease-in-out infinite;
	z-index: 0;
}
@keyframes particleFloat {
	0%, 100% { transform: translateY(0) scale(1); opacity: 0.7; }
	25% { transform: translateY(-6rpx) scale(1.1); opacity: 1; }
	50% { transform: translateY(4rpx) scale(0.95); opacity: 0.8; }
	75% { transform: translateY(-3rpx) scale(1.05); opacity: 0.9; }
}
.cat-header-content {
	display: flex;
	align-items: center;
	position: relative;
	z-index: 1;
}
.cat-icon {
	width: 100rpx;
	height: 100rpx;
	border-radius: 24rpx;
	background: rgba(255, 255, 255, 0.2);
	display: flex;
	align-items: center;
	justify-content: center;
	flex-shrink: 0;
	backdrop-filter: blur(10px);
	border: 2rpx solid rgba(255, 255, 255, 0.3);
}
.cat-icon-img {
	width: 60rpx;
	height: 60rpx;
	border-radius: 12rpx;
}
.cat-emoji {
	font-size: 48rpx;
}
.cat-icon-text {
	font-size: 44rpx;
	color: #fff;
	font-weight: 700;
}
.cat-meta {
	margin-left: 24rpx;
	flex: 1;
}
.cat-name {
	font-size: 36rpx;
	font-weight: 700;
	color: #fff;
	display: block;
	text-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.1);
}
.cat-desc {
	font-size: 24rpx;
	color: rgba(255, 255, 255, 0.85);
	display: block;
	margin-top: 8rpx;
}
.cat-count {
	font-size: 22rpx;
	color: rgba(255, 255, 255, 0.7);
	display: block;
	margin-top: 6rpx;
	background: rgba(255, 255, 255, 0.15);
	display: inline-block;
	padding: 4rpx 16rpx;
	border-radius: 20rpx;
	margin-top: 12rpx;
}

/* 筛选栏 */
.filter-bar {
	background: #fff;
	flex-shrink: 0;
	border-bottom: 1rpx solid rgba(0,0,0,0.04);
	position: relative;
	z-index: 10;
	overflow: hidden;
	box-shadow: 0 2rpx 8rpx rgba(0,0,0,0.03);
}
.sub-scroll {
	white-space: nowrap;
	padding: 16rpx 0;
	border-bottom: 1rpx solid rgba(245, 245, 245, 0.6);
	width: 100%;
	max-height: 80rpx;
	overflow: hidden;
}
.sub-inner {
	display: inline-flex;
	padding: 0 20rpx;
	flex-wrap: nowrap;
}
.sub-tag {
	padding: 10rpx 28rpx;
	margin-right: 12rpx;
	font-size: 24rpx;
	color: #666;
	background: rgba(245, 245, 245, 0.8);
	border-radius: 24rpx;
	display: inline-block;
	transition: all 0.3s ease;
	background-size: 200% 100%;
	background-image: linear-gradient(135deg, rgba(245, 245, 245, 0.8) 0%, rgba(245, 245, 245, 0.8) 50%, rgba(232, 228, 255, 0.9) 100%);
}
.sub-tag:active {
	background-position: 100% 0;
}
.sub-tag.active {
	color: #fff;
	background: #2ed573;
	box-shadow: 0 4rpx 12rpx rgba(0, 0, 0, 0.15);
	transform: scale(0.97);
}
.sort-row {
	display: flex;
	padding: 16rpx 24rpx;
}
.sort-btn {
	margin-right: 16rpx;
	font-size: 24rpx;
	color: #999;
	position: relative;
	transition: all 0.3s ease;
	padding: 8rpx 24rpx;
	border-radius: 24rpx;
	background: #f5f5f5;
}
.sort-btn.active {
	font-weight: 600;
	color: #fff;
	background: #2ed573;
}

/* 资源列表 */
.resource-scroll {
	flex: 1;
	overflow: hidden;
}
.resource-list {
	padding: 20rpx 24rpx;
}
.resource-card {
	display: flex;
	background: #fff;
	border-radius: 24rpx;
	margin-bottom: 20rpx;
	overflow: hidden;
	box-shadow:
		0 2rpx 8rpx rgba(0,0,0,0.03),
		0 8rpx 24rpx rgba(0,0,0,0.06),
		0 16rpx 40rpx rgba(0,0,0,0.04);
	border: 1rpx solid rgba(0,0,0,0.03);
	transition: all 0.3s ease;
	position: relative;
}
/* 卡片顶部渐变装饰线 */
.resource-card::before {
	content: '';
	position: absolute;
	top: 0;
	left: 10%;
	right: 10%;
	height: 2rpx;
	background: linear-gradient(90deg, transparent 0%, rgba(0,0,0,0.06) 30%, rgba(0,0,0,0.08) 50%, rgba(0,0,0,0.06) 70%, transparent 100%);
	border-radius: 1rpx;
	filter: blur(1rpx);
	box-shadow: 0 0 6rpx rgba(46,213,115, 0.4), 0 0 12rpx rgba(46,213,115, 0.15);
	opacity: 0.8;
	z-index: 1;
}
.resource-card:active {
	transform: scale(0.97);
	box-shadow:
		0 1rpx 4rpx rgba(46,213,115, 0.04),
		0 4rpx 12rpx rgba(0, 0, 0, 0.04);
}
/* 封面图片 hover 缩放 */
.card-cover {
	width: 220rpx;
	height: 180rpx;
	flex-shrink: 0;
	border-radius: 24rpx 0 0 24rpx;
	border-right: 2rpx solid rgba(46,213,115, 0.06);
	overflow: hidden;
}
.card-cover image,
.resource-card image.card-cover {
	transition: transform 0.4s ease;
}
.resource-card:active .card-cover {
	transform: scale(1.05);
}
.card-info {
	flex: 1;
	padding: 20rpx 24rpx;
	display: flex;
	flex-direction: column;
	justify-content: space-between;
	min-width: 0;
}
.card-title {
	font-size: 28rpx;
	color: #1a1a2e;
	font-weight: 600;
	overflow: hidden;
	text-overflow: ellipsis;
	display: -webkit-box;
	-webkit-line-clamp: 2;
	-webkit-box-orient: vertical;
	letter-spacing: 0.5rpx;
	line-height: 1.6;
	line-height: 1.5;
}
.card-tags {
	display: flex;
	gap: 10rpx;
	margin-top: 12rpx;
	flex-wrap: wrap;
}
.badge {
	font-size: 20rpx;
	padding: 4rpx 16rpx;
	border-radius: 16rpx;
	font-weight: 500;
}
.badge:active {
	animation: badgeGradientShift 0.6s ease;
}
@keyframes badgeGradientShift {
	0% { background-position: 0% 0; }
	100% { background-position: 100% 0; }
}
.badge.type {
	color: #00b894;
	background: linear-gradient(135deg, rgba(0, 184, 148, 0.1), rgba(0, 210, 168, 0.08));
	background-size: 200% 100%;
	background-image: linear-gradient(135deg, rgba(0, 184, 148, 0.1) 0%, rgba(0, 184, 148, 0.1) 50%, rgba(0, 210, 168, 0.18) 100%);
}
.card-bottom {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-top: auto;
}
.card-stats {
	display: flex;
	gap: 16rpx;
}
.stat {
	font-size: 22rpx;
	color: #aaa;
}
.card-price {
	font-size: 28rpx;
	color: #ff6b6b;
	font-weight: 700;
	background: linear-gradient(135deg, #ff6b6b, #ff8e8e);
}
.card-price.free {
	font-size: 24rpx;
	font-weight: 600;
	border-radius: 8rpx;
	padding: 2rpx 12rpx;
}

/* 分页栏 */
.pager-bar {
	display: flex;
	align-items: center;
	justify-content: space-between;
	padding: 20rpx 24rpx;
	margin: 0 0 20rpx;
	background: #fff;
	border-radius: 20rpx;
	box-shadow:
		0 2rpx 8rpx rgba(0,0,0,0.03),
		0 8rpx 24rpx rgba(0,0,0,0.06);
	border: 1rpx solid rgba(0,0,0,0.03);
}
.pager-btn {
	padding: 12rpx 28rpx;
	border-radius: 24rpx;
	background: #e8e8e8;
	font-size: 24rpx;
	color: #333;
	font-weight: 600;
	transition: all 0.2s;
}
.pager-btn.disabled {
	opacity: 0.4;
	pointer-events: none;
}
.pager-btn:active {
	transform: scale(0.96);
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
	width: 240rpx;
	height: 240rpx;
	border-radius: 50%;
	background: linear-gradient(135deg, rgba(0,0,0,0.02), rgba(0,0,0,0.01));
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

.load-status {
	text-align: center;
	padding: 30rpx 0;
	font-size: 24rpx;
	color: #bbb;
}
.load-status text {
	display: inline-block;
	background: linear-gradient(90deg, #bbb 0%, #2ed573 40%, #bbb 80%);
	background-size: 200% 100%;
	animation: loadShimmer 2s linear infinite;
}
@keyframes loadShimmer {
	0% { background-position: 200% 0; }
	100% { background-position: -200% 0; }
}
@keyframes loadPulse {
	0%, 100% { opacity: 0.5; }
	50% { opacity: 1; }
}
</style>
