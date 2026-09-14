<template>
	<view class="page" :class="'theme-' + activeTheme" :style="pageBgStyle">
		<!-- 自定义顶栏 -->
		<view class="header-wrap" :style="'background:' + tc.headerGradientLong + ';'">
			<view class="header-shade"></view>
			<view class="header-deco deco-1"></view>
			<view class="header-deco deco-2"></view>
			<view class="status-bar"></view>
			<view class="nav-row">
				<view class="back-btn" @tap="goBack">
					<text class="back-icon">‹</text>
				</view>
				<text class="nav-title">资源排行</text>
				<view class="nav-placeholder"></view>
			</view>
			<view class="header-body">
				<view class="header-eyebrow">
					<view class="eyebrow-dot"></view>
					<text class="eyebrow-text">LIVE RANKING</text>
				</view>
				<text class="header-heading">热门资源榜</text>
				<text class="header-sub">按赞与评分实时更新 · 发现优质内容</text>
			</view>
		</view>

		<!-- Tab切换 -->
		<view class="tab-bar">
			<view
				class="tab-item"
				:class="{ active: currentTab === 'up' }"
				@tap="switchTab('up')"
			>
				<text class="tab-icon">↑</text>
				<text class="tab-text">最多赞同</text>
			</view>
			<view
				class="tab-item"
				:class="{ active: currentTab === 'net' }"
				@tap="switchTab('net')"
			>
				<text class="tab-icon">★</text>
				<text class="tab-text">最高评分</text>
			</view>
		</view>

		<!-- 排名列表 -->
		<scroll-view
			scroll-y
			class="scroll-content"
			:refresher-enabled="true"
			:refresher-triggered="isRefreshing"
			@refresherrefresh="onRefresh"
		>
			<!-- 骨架屏 -->
			<view v-if="loading && list.length === 0">
				<view class="rank-card skeleton-card" v-for="n in 5" :key="n">
					<view class="rank-num skeleton-num"></view>
					<view class="skeleton-cover"></view>
					<view class="rank-info">
						<view class="skeleton-text"></view>
						<view class="skeleton-text short"></view>
					</view>
				</view>
			</view>

			<!-- 排名列表 -->
			<view class="rank-list" v-else>
				<view
					class="rank-card"
					:class="{ 'rank-card-top': item.rank <= 3 }"
					v-for="item in list"
					:key="item.id"
					@tap="goDetail(item)"
				>
					<view class="rank-num" :class="'rank-' + (item.rank <= 3 ? item.rank : 'other')">
						<text v-if="item.rank === 1" class="rank-medal">1</text>
						<text v-else-if="item.rank === 2" class="rank-medal">2</text>
						<text v-else-if="item.rank === 3" class="rank-medal">3</text>
						<text v-else class="rank-number">{{ item.rank }}</text>
					</view>

					<view class="rank-cover-wrap">
						<image
							class="rank-cover"
							:src="getFullUrl(item.cover_url)"
							mode="aspectFill"
						></image>
					</view>

					<view class="rank-info">
						<text class="rank-title" :class="{ top: item.rank === 1 }">{{ item.title }}</text>
						<view class="rank-meta">
							<text class="rank-category" v-if="item.category_name">{{ item.category_name }}</text>
							<text
								class="vote-net"
								:class="item.net_votes >= 0 ? 'positive' : 'negative'"
							>
								{{ item.net_votes >= 0 ? '+' : '' }}{{ item.net_votes }}
							</text>
						</view>
						<view class="rank-votes">
							<view class="vote-chip up">
								<text class="vote-mark">↑</text>
								<text class="vote-val">{{ item.up_votes }}</text>
							</view>
							<view class="vote-chip down">
								<text class="vote-mark">↓</text>
								<text class="vote-val">{{ item.down_votes }}</text>
							</view>
						</view>
					</view>
				</view>
			</view>

			<!-- 空状态 -->
			<view class="empty" v-if="!loading && list.length === 0">
				<text class="empty-icon">📦</text>
				<text class="empty-text">暂无排行数据</text>
			</view>

			<!-- 分页栏 -->
			<view class="pager-bar" v-if="list.length > 0">
				<view class="pager-btn" :class="{ disabled: page <= 1 }" @tap="goPage(page - 1)">
					<text>‹ 上一页</text>
				</view>
				<view class="pager-info">
					<text class="pager-current">{{ page }}/{{ totalPages || 1 }}</text>
					<text class="pager-total">共{{ total }}条</text>
				</view>
				<view class="pager-btn" :class="{ disabled: page >= totalPages }" @tap="goPage(page + 1)">
					<text>下一页 ›</text>
				</view>
			</view>

			<!-- 加载状态 -->
			<view class="load-status">
				<text v-if="loading && list.length > 0">加载中...</text>
			</view>
		</scroll-view>
	</view>
</template>

<script>
import http from '@/utils/http.js';

export default {
	data() {
		return {
			currentTab: 'net',
			list: [],
			page: 1,
			total: 0,
			totalPages: 0,
			loading: false,
			noMore: false,
			isRefreshing: false
		};
	},
	onLoad() {
		this.loadRanking();
	},
	methods: {
		goBack() {
			uni.navigateBack({
				delta: 1,
				fail: () => {
					uni.switchTab({ url: '/pages/index/index' });
				}
			});
		},
		switchTab(tab) {
			if (this.currentTab === tab) return;
			this.currentTab = tab;
			this.page = 1;
			this.noMore = false;
			this.list = [];
			this.loadRanking();
		},
		async loadRanking() {
			if (this.loading) return;
			this.loading = true;
			try {
				const res = await http.get('/api/vote/ranking', {
					type: this.currentTab,
					page: this.page,
					page_size: this.pageSize
				}, { silent: true });
				if (res.code === 0) {
					const data = res.data;
					const items = data.list || [];
					this.total = data.total || items.length;
					this.totalPages = data.total_pages || Math.ceil(this.total / this.pageSize) || 1;
					this.list = items;
					this.noMore = this.page >= this.totalPages;
				}
			} catch (e) {
				console.error('加载排行失败', e);
			} finally {
				this.loading = false;
			}
		},
		async onRefresh() {
			this.isRefreshing = true;
			this.page = 1;
			this.noMore = false;
			await this.loadRanking();
			this.isRefreshing = false;
		},
		goPage(p) {
			if (p < 1 || p > this.totalPages || this.loading) return;
			this.page = p;
			this.loadRanking();
			uni.pageScrollTo && uni.pageScrollTo({ scrollTop: 0, duration: 200 });
		},
		goDetail(item) {
			uni.navigateTo({ url: `/pages/resource/detail?id=${item.id}` });
		},
		getFullUrl(url) {
			if (!url) return '';
			if (url.startsWith('http')) return url;
			return http.getBaseUrl() + url;
		}
	},
	onShareAppMessage() {
		return {
			title: '资源投票排行榜 - 快来看看最受欢迎的资源',
			path: '/pages/rank/index'
		};
	}
};
</script>

<style scoped>
.page {
	min-height: 100vh;
	background: transparent;
}

/* ===== 自定义顶栏 ===== */
.header-wrap {
	position: relative;
	padding-bottom: 64rpx;
	overflow: hidden;
}
/* 保证任意主题下白字可读 */
.header-shade {
	position: absolute;
	left: 0;
	top: 0;
	right: 0;
	bottom: 0;
	background: linear-gradient(
		180deg,
		rgba(20, 40, 10, 0.38) 0%,
		rgba(20, 40, 10, 0.22) 45%,
		rgba(20, 40, 10, 0.08) 75%,
		rgba(20, 40, 10, 0) 100%
	);
	pointer-events: none;
	z-index: 0;
}
.header-deco {
	position: absolute;
	border-radius: 50%;
	pointer-events: none;
	z-index: 0;
}
.deco-1 {
	width: 320rpx;
	height: 320rpx;
	top: -120rpx;
	right: -80rpx;
	background: rgba(255, 255, 255, 0.12);
}
.deco-2 {
	width: 200rpx;
	height: 200rpx;
	bottom: 20rpx;
	left: -60rpx;
	background: rgba(255, 255, 255, 0.08);
}
.status-bar {
	height: 88rpx;
	height: calc(88rpx + constant(safe-area-inset-top));
	height: calc(88rpx + env(safe-area-inset-top));
	position: relative;
	z-index: 1;
}
.nav-row {
	display: flex;
	align-items: center;
	justify-content: space-between;
	height: 88rpx;
	padding: 0 24rpx;
	position: relative;
	z-index: 2;
}
.back-btn {
	width: 68rpx;
	height: 68rpx;
	border-radius: 22rpx;
	background: rgba(255, 255, 255, 0.18);
	display: flex;
	align-items: center;
	justify-content: center;
	border: 1rpx solid rgba(255, 255, 255, 0.25);
}
.back-icon {
	font-size: 48rpx;
	color: #ffffff;
	font-weight: 300;
	line-height: 1;
	margin-top: -6rpx;
}
.nav-title {
	position: absolute;
	left: 0;
	right: 0;
	text-align: center;
	font-size: 32rpx;
	font-weight: 700;
	color: #ffffff;
	letter-spacing: 3rpx;
	pointer-events: none;
	text-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.25);
}
.nav-placeholder {
	width: 68rpx;
	height: 68rpx;
}
.header-body {
	padding: 20rpx 40rpx 0;
	position: relative;
	z-index: 1;
}
.header-eyebrow {
	display: flex;
	align-items: center;
	gap: 10rpx;
	margin-bottom: 14rpx;
}
.eyebrow-dot {
	width: 12rpx;
	height: 12rpx;
	border-radius: 50%;
	background: #ffd54f;
	box-shadow: 0 0 12rpx rgba(255, 213, 79, 0.7);
}
.eyebrow-text {
	font-size: 20rpx;
	font-weight: 700;
	color: #ffffff;
	letter-spacing: 4rpx;
	text-shadow: 0 2rpx 6rpx rgba(0, 0, 0, 0.2);
}
.header-heading {
	display: block;
	font-size: 52rpx;
	font-weight: 800;
	color: #ffffff;
	letter-spacing: 3rpx;
	line-height: 1.2;
	text-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.28);
}
.header-sub {
	display: block;
	margin-top: 14rpx;
	font-size: 24rpx;
	color: rgba(255, 255, 255, 0.92);
	letter-spacing: 1.5rpx;
	line-height: 1.5;
	text-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.2);
}

/* ===== Tab分段控件 ===== */
.tab-bar {
	display: flex;
	margin: -48rpx 28rpx 28rpx;
	background: rgba(255, 255, 255, 0.96);
	border-radius: 999rpx;
	padding: 8rpx;
	box-shadow: 0 16rpx 40rpx rgba(70, 100, 30, 0.14);
	border: 1rpx solid rgba(255, 255, 255, 0.8);
	position: relative;
	z-index: 10;
}
.tab-item {
	flex: 1;
	display: flex;
	align-items: center;
	justify-content: center;
	height: 76rpx;
	border-radius: 999rpx;
	transition: all 0.2s ease;
	gap: 8rpx;
}
.tab-item.active {
	background: #1a1a1a;
}
.tab-icon {
	font-size: 24rpx;
	color: #9aa88a;
	font-weight: 700;
}
.tab-item.active .tab-icon {
	color: #ffd54f;
}
.tab-text {
	font-size: 26rpx;
	font-weight: 600;
	color: #8a9578;
}
.tab-item.active .tab-text {
	color: #ffffff;
	font-weight: 700;
}

/* ===== 滚动区域 ===== */
.scroll-content {
	height: calc(100vh - 420rpx);
	padding: 0 28rpx 20rpx;
}
.rank-list {
	padding-bottom: 20rpx;
}

/* ===== 排名卡片 ===== */
.rank-card {
	display: flex;
	align-items: center;
	background: #fff;
	border-radius: 32rpx;
	padding: 22rpx 24rpx;
	margin-bottom: 18rpx;
	box-shadow: 0 8rpx 28rpx rgba(90, 120, 50, 0.08);
	border: none;
	transition: transform 0.2s ease;
	position: relative;
}
.rank-card-top {
	padding: 26rpx 24rpx;
	box-shadow: 0 10rpx 32rpx rgba(90, 120, 50, 0.1);
}
.rank-card:active {
	transform: scale(0.985);
}

/* ===== 排名徽章 ===== */
.rank-num {
	width: 64rpx;
	height: 64rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	flex-shrink: 0;
	margin-right: 18rpx;
	border-radius: 50%;
	position: relative;
}
.rank-1 {
	background: linear-gradient(145deg, #f8d56a 0%, #e8a317 100%);
	box-shadow: 0 8rpx 18rpx rgba(232, 163, 23, 0.32);
	width: 72rpx;
	height: 72rpx;
}
.rank-2 {
	background: linear-gradient(145deg, #e2e8f0 0%, #aeb7c4 100%);
	box-shadow: 0 6rpx 14rpx rgba(174, 183, 196, 0.28);
	width: 68rpx;
	height: 68rpx;
}
.rank-3 {
	background: linear-gradient(145deg, #e8bc96 0%, #c68642 100%);
	box-shadow: 0 6rpx 14rpx rgba(198, 134, 66, 0.28);
	width: 68rpx;
	height: 68rpx;
}
.rank-other {
	background: #f3f5f0;
	border-radius: 20rpx;
}
.rank-medal {
	font-size: 32rpx;
	font-weight: 800;
	color: #ffffff;
	position: relative;
	z-index: 1;
	line-height: 1;
	text-shadow: 0 1rpx 2rpx rgba(0, 0, 0, 0.12);
}
.rank-1 .rank-medal {
	font-size: 36rpx;
}
.rank-number {
	font-size: 28rpx;
	font-weight: 700;
	color: #9aa88a;
	position: relative;
	z-index: 1;
}

/* ===== 封面 ===== */
.rank-cover-wrap {
	width: 168rpx;
	height: 126rpx;
	border-radius: 20rpx;
	flex-shrink: 0;
	margin-right: 20rpx;
	overflow: hidden;
	background: #f3f5f0;
}
.rank-cover {
	width: 100%;
	height: 100%;
}
.rank-card-top .rank-cover-wrap {
	width: 180rpx;
	height: 136rpx;
}

/* ===== 信息区 ===== */
.rank-info {
	flex: 1;
	min-width: 0;
	display: flex;
	flex-direction: column;
	gap: 10rpx;
}
.rank-title {
	font-size: 28rpx;
	color: #1a1a1a;
	font-weight: 700;
	overflow: hidden;
	text-overflow: ellipsis;
	display: -webkit-box;
	-webkit-line-clamp: 2;
	-webkit-box-orient: vertical;
	line-height: 1.45;
}
.rank-title.top {
	font-size: 30rpx;
}
.rank-meta {
	display: flex;
	align-items: center;
	justify-content: space-between;
	gap: 12rpx;
}
.rank-category {
	font-size: 20rpx;
	color: #8a9578;
	font-weight: 500;
	background: #f3f5f0;
	padding: 4rpx 14rpx;
	border-radius: 999rpx;
	max-width: 160rpx;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
}
.rank-votes {
	display: flex;
	align-items: center;
	gap: 12rpx;
}
.vote-chip {
	display: flex;
	align-items: center;
	gap: 4rpx;
	padding: 4rpx 12rpx;
	border-radius: 999rpx;
	font-size: 20rpx;
	font-weight: 600;
}
.vote-chip.up {
	background: rgba(124, 179, 66, 0.1);
	color: #558b2f;
}
.vote-chip.down {
	background: rgba(220, 38, 38, 0.08);
	color: #c45c5c;
}
.vote-mark {
	font-size: 18rpx;
	line-height: 1;
}
.vote-val {
	line-height: 1;
}
.vote-net {
	font-size: 20rpx;
	font-weight: 800;
	padding: 4rpx 12rpx;
	border-radius: 999rpx;
	flex-shrink: 0;
}
.vote-net.positive {
	color: #558b2f;
	background: rgba(124, 179, 66, 0.12);
}
.vote-net.negative {
	color: #dc2626;
	background: rgba(220, 38, 38, 0.08);
}

/* ===== 分页栏 ===== */
.pager-bar {
	display: flex;
	align-items: center;
	justify-content: space-between;
	padding: 20rpx 24rpx;
	margin: 8rpx 0 20rpx;
	background: #fff;
	border-radius: 28rpx;
	border: none;
	box-shadow: 0 8rpx 28rpx rgba(90, 120, 50, 0.08);
}
.pager-btn {
	padding: 14rpx 28rpx;
	border-radius: 999rpx;
	transition: all 0.2s ease;
}
.pager-btn.disabled {
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
	color: #1a1a1a;
	font-weight: 700;
}
.pager-total {
	font-size: 20rpx;
	color: #a3b08a;
	margin-top: 4rpx;
}

/* ===== 空状态 ===== */
.empty {
	display: flex;
	flex-direction: column;
	align-items: center;
	padding: 100rpx 0;
	position: relative;
}
.empty::before {
	content: '';
	position: absolute;
	width: 220rpx;
	height: 220rpx;
	border-radius: 50%;
	background: linear-gradient(160deg, #ffffff 0%, #e8f2dc 100%);
	top: 70rpx;
}
.empty-icon {
	font-size: 72rpx;
	margin-bottom: 20rpx;
	position: relative;
	z-index: 1;
	animation: none;
}
.empty-text {
	font-size: 28rpx;
	color: #a3b08a;
	position: relative;
	z-index: 1;
	font-weight: 500;
}

/* ===== 加载状态 ===== */
.load-status {
	text-align: center;
	padding: 30rpx 0;
	font-size: 24rpx;
	color: #a3b08a;
	letter-spacing: 2rpx;
}

/* ===== 骨架屏 ===== */
.skeleton-card {
	background: #fff;
	border-radius: 32rpx;
	box-shadow: 0 8rpx 28rpx rgba(90, 120, 50, 0.08);
}
.skeleton-num {
	width: 64rpx;
	height: 64rpx;
	border-radius: 20rpx;
	background: linear-gradient(90deg, #eef2e8 25%, #f5f7f1 50%, #eef2e8 75%);
	background-size: 200% 100%;
	animation: skeleton-shimmer 1.5s ease-in-out infinite;
}
.skeleton-cover {
	width: 168rpx;
	height: 126rpx;
	border-radius: 18rpx;
	flex-shrink: 0;
	margin-right: 20rpx;
	background: linear-gradient(90deg, #eef2e8 25%, #f5f7f1 50%, #eef2e8 75%);
	background-size: 200% 100%;
	animation: skeleton-shimmer 1.5s ease-in-out infinite;
}
.skeleton-text {
	height: 28rpx;
	border-radius: 10rpx;
	background: linear-gradient(90deg, #eef2e8 25%, #f5f7f1 50%, #eef2e8 75%);
	background-size: 200% 100%;
	animation: skeleton-shimmer 1.5s ease-in-out infinite;
	margin-bottom: 12rpx;
	width: 100%;
}
.skeleton-text.short {
	width: 60%;
}

@keyframes skeleton-shimmer {
	0% { background-position: 200% 0; }
	100% { background-position: -200% 0; }
}
</style>
