<template>
	<view class="page">
		<!-- 紫色渐变顶部 -->
		<view class="header-bg"></view>

		<!-- Tab切换 -->
		<view class="tab-bar">
			<view
				class="tab-item"
				:class="{ active: currentTab === 'up' }"
				@tap="switchTab('up')"
			>
				<text class="tab-icon">👍</text>
				<text class="tab-text">最多赞同</text>
			</view>
			<view
				class="tab-item"
				:class="{ active: currentTab === 'net' }"
				@tap="switchTab('net')"
			>
				<text class="tab-icon">📊</text>
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
			<view v-else>
				<view
					class="rank-card"
					v-for="item in list"
					:key="item.id"
					@tap="goDetail(item)"
				>
					<!-- 排名序号 -->
					<view class="rank-num" :class="'rank-' + (item.rank <= 3 ? item.rank : 'other')">
						<text v-if="item.rank === 1" class="rank-medal">🥇</text>
						<text v-else-if="item.rank === 2" class="rank-medal">🥈</text>
						<text v-else-if="item.rank === 3" class="rank-medal">🥉</text>
						<text v-else class="rank-number">{{ item.rank }}</text>
					</view>

					<!-- 封面缩略图 -->
					<view class="rank-cover-wrap">
						<image
							class="rank-cover"
							:src="getFullUrl(item.cover_url)"
							mode="aspectFill"
						></image>
					</view>

					<!-- 信息区 -->
					<view class="rank-info">
						<text class="rank-title">{{ item.title }}</text>
						<view class="rank-tags">
							<text class="tag" :style="tPri" v-if="item.category_name">{{ item.category_name }}</text>
						</view>
						<view class="rank-votes">
							<text class="vote-up" :style="'color:' + tc.primary + ';'">👍 {{ item.up_votes }}</text>
							<text class="vote-down">👎 {{ item.down_votes }}</text>
							<text
								class="vote-net"
								:class="item.net_votes >= 0 ? 'positive' : 'negative'"
								:style="item.net_votes >= 0 ? 'color:' + tc.primary + ';' : ''"
							>
								{{ item.net_votes >= 0 ? '+' : '' }}{{ item.net_votes }}
							</text>
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
	background: linear-gradient(180deg, #eef0f8 0%, #f3f4f8 8%, #f7f8fc 20%, #fafbfe 50%, #f8f9fc 100%);
}

/* ===== 渐变顶部背景 ===== */
.header-bg {
	height: 200rpx;
	background: linear-gradient(135deg, #2ed573 0%, #27ae60 40%, #1abc9c 100%);
	position: relative;
	overflow: hidden;
	box-shadow: 0 2rpx 8rpx rgba(0,0,0,0.04);
}
.header-bg::after {
	content: '';
	position: absolute;
	width: 300rpx;
	height: 300rpx;
	border-radius: 50%;
	background: rgba(255, 255, 255, 0.08);
	top: -100rpx;
	right: -60rpx;
}
.header-bg::before {
	content: '';
	position: absolute;
	width: 200rpx;
	height: 200rpx;
	border-radius: 50%;
	background: rgba(255, 255, 255, 0.05);
	bottom: -80rpx;
	left: -40rpx;
}

/* ===== Tab切换 ===== */
.tab-bar {
	display: flex;
	margin: -60rpx 28rpx 24rpx;
	background: rgba(255, 255, 255, 0.95);
	backdrop-filter: blur(24rpx);
	-webkit-backdrop-filter: blur(24rpx);
	border-radius: 24rpx;
	padding: 8rpx;
	box-shadow:
		0 2rpx 8rpx rgba(0,0,0,0.04),
		0 12rpx 40rpx rgba(0,0,0,0.08);
	border: 1rpx solid rgba(255,255,255,0.8);
	position: relative;
	z-index: 10;
}
/* Tab栏底部光晕 */
.tab-bar::after {
	content: '';
	position: absolute;
	bottom: -8rpx;
	left: 20%;
	right: 20%;
	height: 16rpx;
	background: linear-gradient(90deg, transparent, rgba(46,213,115, 0.15), transparent);
	border-radius: 50%;
	filter: blur(8rpx);
}
.tab-item {
	flex: 1;
	display: flex;
	align-items: center;
	justify-content: center;
	height: 84rpx;
	border-radius: 20rpx;
	transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
}
.tab-item.active {
	background: linear-gradient(135deg, rgba(0,0,0,0.06), rgba(0,0,0,0.03));
	box-shadow:
		0 2rpx 8rpx rgba(0,0,0,0.08),
		inset 0 1rpx 0 rgba(255,255,255,0.5);
	transform: scale(1.01);
}
.tab-icon {
	font-size: 32rpx;
	margin-right: 10rpx;
}
.tab-text {
	font-size: 28rpx;
	font-weight: 600;
	color: #888;
	transition: color 0.3s ease;
}
.tab-item.active .tab-text {
	color: #1a1a2e;
	font-weight: 700;
}

/* ===== 滚动区域 ===== */
.scroll-content {
	height: calc(100vh - 280rpx);
	padding: 0 28rpx;
}

/* ===== 排名卡片 ===== */
.rank-card {
	display: flex;
	align-items: center;
	background: #fff;
	border-radius: 24rpx;
	padding: 24rpx;
	margin-bottom: 20rpx;
	box-shadow:
		0 2rpx 8rpx rgba(0,0,0,0.03),
		0 8rpx 24rpx rgba(0,0,0,0.06),
		0 16rpx 40rpx rgba(0,0,0,0.04);
	border: 1rpx solid rgba(0,0,0,0.03);
	transition: transform 0.25s ease, box-shadow 0.25s ease;
	position: relative;
}
/* 顶部渐变装饰线 */
.rank-card::before {
	content: '';
	position: absolute;
	top: 0;
	left: 0;
	right: 0;
	height: 2rpx;
	background: linear-gradient(90deg, transparent 0%, rgba(0,0,0,0.06) 30%, rgba(0,0,0,0.08) 50%, rgba(0,0,0,0.06) 70%, transparent 100%);
	border-radius: 2rpx 2rpx 0 0;
	z-index: 2;
	box-shadow: 0 0 6rpx rgba(46,213,115, 0.25);
}
.rank-card:active {
	transform: scale(0.98);
	box-shadow: 0 4rpx 16rpx rgba(46,213,115, 0.1);
}

/* ===== 排名序号 - 渐变徽章 ===== */
.rank-num {
	width: 76rpx;
	height: 76rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	flex-shrink: 0;
	margin-right: 20rpx;
	border-radius: 20rpx;
	position: relative;
}
/* 第1名脉冲发光 */
.rank-1 {
	background: linear-gradient(135deg, #FFD700, #FFA500);
	box-shadow: 0 4rpx 8rpx rgba(255, 215, 0, 0.4);
	animation: pulse-gold 2s ease-in-out infinite;
}
.rank-1::after {
	content: '';
	position: absolute;
	top: 0;
	left: 0;
	right: 0;
	height: 50%;
	background: linear-gradient(180deg, rgba(255,255,255,0.35), transparent);
	border-radius: 20rpx 20rpx 0 0;
}
@keyframes pulse-gold {
	0%, 100% { box-shadow: 0 4rpx 8rpx rgba(255, 215, 0, 0.4); }
	50% { box-shadow: 0 4rpx 15rpx rgba(255, 215, 0, 0.7), 0 0 10rpx rgba(255, 165, 0, 0.3); }
}
/* 第2名脉冲发光 */
.rank-2 {
	background: linear-gradient(135deg, #C0C0C0, #A8A8A8);
	box-shadow: 0 4rpx 8rpx rgba(192, 192, 192, 0.4);
	animation: pulse-silver 2s ease-in-out infinite;
}
.rank-2::after {
	content: '';
	position: absolute;
	top: 0;
	left: 0;
	right: 0;
	height: 50%;
	background: linear-gradient(180deg, rgba(255,255,255,0.35), transparent);
	border-radius: 20rpx 20rpx 0 0;
}
@keyframes pulse-silver {
	0%, 100% { box-shadow: 0 4rpx 8rpx rgba(192, 192, 192, 0.4); }
	50% { box-shadow: 0 4rpx 15rpx rgba(192, 192, 192, 0.7), 0 0 10rpx rgba(169, 169, 169, 0.3); }
}
/* 第3名脉冲发光 */
.rank-3 {
	background: linear-gradient(135deg, #CD7F32, #B8860B);
	box-shadow: 0 4rpx 8rpx rgba(205, 127, 50, 0.4);
	animation: pulse-bronze 2s ease-in-out infinite;
}
.rank-3::after {
	content: '';
	position: absolute;
	top: 0;
	left: 0;
	right: 0;
	height: 50%;
	background: linear-gradient(180deg, rgba(255,255,255,0.3), transparent);
	border-radius: 20rpx 20rpx 0 0;
}
@keyframes pulse-bronze {
	0%, 100% { box-shadow: 0 4rpx 8rpx rgba(205, 127, 50, 0.4); }
	50% { box-shadow: 0 4rpx 15rpx rgba(205, 127, 50, 0.7), 0 0 10rpx rgba(184, 134, 11, 0.3); }
}
.rank-other {
	background: linear-gradient(135deg, #f0f1f8, #e8e9f2);
}
.rank-medal {
	font-size: 52rpx;
	position: relative;
	z-index: 1;
}
.rank-number {
	font-size: 32rpx;
	font-weight: 800;
	color: #aaa;
	position: relative;
	z-index: 1;
}

/* ===== 封面 ===== */
.rank-cover-wrap {
	width: 160rpx;
	height: 120rpx;
	border-radius: 18rpx;
	flex-shrink: 0;
	margin-right: 20rpx;
	overflow: hidden;
	background: #f0f1f8;
}
.rank-cover {
	width: 160rpx;
	height: 120rpx;
}
.rank-card:active .rank-cover-wrap {
	transform: scale(1.02);
	transition: transform 0.4s ease;
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
	color: #1a1a2e;
	font-weight: 700;
	overflow: hidden;
	text-overflow: ellipsis;
	display: -webkit-box;
	-webkit-line-clamp: 2;
	-webkit-box-orient: vertical;
	letter-spacing: 0.5rpx;
	line-height: 1.6;
	line-height: 1.5;
}
.rank-tags {
	display: flex;
	gap: 12rpx;
	flex-wrap: wrap;
}
.tag {
	font-size: 20rpx;
	background: rgba(0,0,0,0.04);
	padding: 5rpx 16rpx;
	border-radius: 16rpx;
	font-weight: 600;
	letter-spacing: 0.3rpx;
	border: 1rpx solid rgba(0,0,0,0.03);
}
.rank-votes {
	display: flex;
	align-items: center;
	gap: 16rpx;
}
.vote-up {
	font-size: 24rpx;
	color: #22c55e;
	font-weight: 600;
}
.vote-down {
	font-size: 24rpx;
	color: #ef4444;
	font-weight: 600;
}
.vote-net {
	font-size: 24rpx;
	font-weight: 800;
	padding: 4rpx 14rpx;
	border-radius: 12rpx;
}
.vote-net.positive {
	color: #16a34a;
	background: rgba(22, 163, 74, 0.08);
	box-shadow: 0 2rpx 8rpx rgba(22, 163, 74, 0.08);
}
.vote-net.negative {
	color: #dc2626;
	background: rgba(220, 38, 38, 0.08);
	box-shadow: 0 2rpx 8rpx rgba(220, 38, 38, 0.08);
}

/* ===== 分页栏 ===== */
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

/* ===== 空状态 ===== */
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
	width: 260rpx;
	height: 260rpx;
	border-radius: 50%;
	background: linear-gradient(135deg, rgba(0,0,0,0.02), rgba(0,0,0,0.01));
	top: 80rpx;
}
.empty-icon {
	font-size: 80rpx;
	margin-bottom: 20rpx;
	position: relative;
	z-index: 1;
	animation: breathe 3s ease-in-out infinite;
}
.empty-text {
	font-size: 28rpx;
	color: #aaa;
	position: relative;
	z-index: 1;
}
@keyframes breathe {
	0%, 100% { transform: scale(1); }
	50% { transform: scale(1.04); }
}

/* ===== 加载状态 ===== */
.load-status {
	text-align: center;
	padding: 30rpx 0;
	font-size: 24rpx;
	color: #c0c0c0;
	letter-spacing: 2rpx;
}

/* ===== 骨架屏 ===== */
.skeleton-card {
	background: #fff;
	border-radius: 24rpx;
	box-shadow:
		0 2rpx 8rpx rgba(0,0,0,0.03),
		0 8rpx 24rpx rgba(0,0,0,0.05);
}
.skeleton-num {
	width: 40rpx;
	height: 40rpx;
	border-radius: 10rpx;
	background: linear-gradient(90deg, #eef0f5 25%, #f5f6fa 50%, #eef0f5 75%);
	background-size: 200% 100%;
	animation: skeleton-shimmer 1.5s ease-in-out infinite;
}
.skeleton-cover {
	width: 160rpx;
	height: 120rpx;
	border-radius: 18rpx;
	flex-shrink: 0;
	margin-right: 20rpx;
	background: linear-gradient(90deg, #eef0f5 25%, #f5f6fa 50%, #eef0f5 75%);
	background-size: 200% 100%;
	animation: skeleton-shimmer 1.5s ease-in-out infinite;
}
.skeleton-text {
	height: 28rpx;
	border-radius: 10rpx;
	background: linear-gradient(90deg, #eef0f5 25%, #f5f6fa 50%, #eef0f5 75%);
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
