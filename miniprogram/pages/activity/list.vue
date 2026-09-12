<template>
	<view class="page">
		<!-- 顶部分类标签 -->
		<view class="tabs-bar">
			<view
				class="tab-item"
				:class="{ active: currentType === '' }"
				@tap="switchType('')"
			>
				<text>全部</text>
			</view>
			<view
				class="tab-item"
				:class="{ active: currentType === 'online' }"
				@tap="switchType('online')"
			>
				<text>线上</text>
			</view>
			<view
				class="tab-item"
				:class="{ active: currentType === 'offline' }"
				@tap="switchType('offline')"
			>
				<text>线下</text>
			</view>
			<view
				class="tab-item"
				:class="{ active: currentType === 'both' }"
				@tap="switchType('both')"
			>
				<text>线上线下</text>
			</view>
		</view>

		<!-- 活动列表 -->
		<scroll-view
			scroll-y
			class="list-scroll"
			:refresher-enabled="true"
			:refresher-triggered="isRefreshing"
			@refresherrefresh="onRefresh"
			@scrolltolower="loadMore"
		>
			<!-- 骨架屏 -->
			<view v-if="loading && list.length === 0" class="skeleton-list">
				<view class="skeleton-card" v-for="i in 3" :key="i">
					<view class="skeleton-cover"></view>
					<view class="skeleton-body">
						<view class="skeleton-line w80"></view>
						<view class="skeleton-line w60"></view>
						<view class="skeleton-line w40"></view>
					</view>
				</view>
			</view>

			<!-- 活动卡片 -->
			<view v-else-if="list.length > 0" class="activity-list">
				<view
					class="activity-card"
					v-for="item in list"
					:key="item.id"
					@tap="goDetail(item.id)"
				>
					<view class="card-cover">
						<image
							:src="item.cover_url || '/static/default-cover.png'"
							mode="aspectFill"
							class="cover-img"
						></image>
						<view class="type-tag" :class="item.activity_type">
							<text>{{ typeLabels[item.activity_type] || '线上' }}</text>
						</view>
						<view v-if="item.is_signed_up" class="signed-tag" :style="'background:' + tc.primary + ';'">
							<text>已报名</text>
						</view>
					</view>
					<view class="card-body">
						<text class="card-title">{{ item.title }}</text>
						<view class="card-info">
							<text class="info-icon">&#128197;</text>
							<text class="info-text">{{ formatTime(item.start_time) }} - {{ formatTime(item.end_time) }}</text>
						</view>
						<view class="card-info" v-if="item.location">
							<text class="info-icon">&#128205;</text>
							<text class="info-text">{{ item.location }}</text>
						</view>
						<view class="card-footer">
							<view class="progress-info">
								<text class="count-text">
									{{ item.current_count }}/{{ item.max_participants > 0 ? item.max_participants : '不限' }}人
								</text>
								<view v-if="item.max_participants > 0" class="mini-progress">
									<view
										class="mini-progress-bar"
										:style="{ width: Math.min(100, (item.current_count / item.max_participants) * 100) + '%' }"
									></view>
								</view>
							</view>
							<view class="status-badge" :class="getStatusClass(item)" :style="getStatusClass(item) === 'open' ? tPri : ''">
								<text>{{ getStatusText(item) }}</text>
							</view>
						</view>
					</view>
				</view>
			</view>

			<!-- 空状态 -->
			<view v-else class="empty-state">
				<text class="empty-icon">&#127891;</text>
				<text class="empty-text">暂无活动</text>
			</view>

			<!-- 加载更多 -->
			<view class="load-more" v-if="list.length > 0">
				<text v-if="loading">加载中...</text>
				<text v-else-if="noMore">- 已加载全部 -</text>
			</view>

			<view style="height: 40rpx;"></view>
		</scroll-view>
	</view>
</template>

<script>
import http from '@/utils/http.js';

export default {
	data() {
		return {
			currentType: '',
			list: [],
			page: 1,
			pageSize: 10,
			loading: false,
			noMore: false,
			isRefreshing: false,
			typeLabels: {
				online: '线上',
				offline: '线下',
				both: '线上+线下'
			}
		};
	},
	onLoad() {
		this.loadData(true);
	},
	methods: {
		switchType(type) {
			if (this.currentType === type) return;
			this.currentType = type;
			this.loadData(true);
		},

		async loadData(reset = false) {
			if (this.loading) return;
			if (reset) {
				this.page = 1;
				this.noMore = false;
				this.list = [];
			}
			if (this.noMore) return;

			this.loading = true;
			try {
				const params = {
					page: this.page,
					page_size: this.pageSize
				};
				if (this.currentType) {
					params.type = this.currentType;
				}
				const res = await http.get('/api/activity/list', params, { showLoading: this.page === 1 });
				if (res.code === 0) {
					const newList = res.data.list || res.data || [];
					if (reset) {
						this.list = newList;
					} else {
						this.list = this.list.concat(newList);
					}
					if (newList.length < this.pageSize) {
						this.noMore = true;
					}
					this.page++;
				}
			} catch (e) {
				console.error('加载活动列表失败', e);
			} finally {
				this.loading = false;
				this.isRefreshing = false;
			}
		},

		onRefresh() {
			this.isRefreshing = true;
			this.loadData(true);
		},

		loadMore() {
			if (!this.noMore && !this.loading) {
				this.loadData(false);
			}
		},

		goDetail(id) {
			uni.navigateTo({ url: '/pages/activity/detail?id=' + id });
		},

		formatTime(dt) {
			if (!dt) return '';
			const d = new Date(dt.replace(/-/g, '/'));
			const m = d.getMonth() + 1;
			const day = d.getDate();
			const h = String(d.getHours()).padStart(2, '0');
			const min = String(d.getMinutes()).padStart(2, '0');
			return m + '月' + day + '日 ' + h + ':' + min;
		},

		getStatusClass(item) {
			if (item.max_participants > 0 && item.current_count >= item.max_participants) return 'full';
			if (item.signup_deadline && new Date(item.signup_deadline.replace(/-/g, '/')) < new Date()) return 'deadline';
			return 'open';
		},

		getStatusText(item) {
			if (item.max_participants > 0 && item.current_count >= item.max_participants) return '名额已满';
			if (item.signup_deadline && new Date(item.signup_deadline.replace(/-/g, '/')) < new Date()) return '报名截止';
			return '报名中';
		}
	},
	onShareAppMessage() {
		return {
			title: '精彩活动等你来参加',
			path: '/pages/activity/list'
		};
	}
};
</script>

<style scoped>
.page {
	min-height: 100vh;
	background: linear-gradient(180deg, #f0f2ff 0%, #f5f6fa 200rpx);
}

/* ===== 分类标签 ===== */
.tabs-bar {
	display: flex;
	background: rgba(255, 255, 255, 0.85);
	backdrop-filter: blur(20rpx);
	-webkit-backdrop-filter: blur(20rpx);
	padding: 20rpx 24rpx;
	gap: 16rpx;
	position: sticky;
	top: 0;
	z-index: 10;
	box-shadow: 0 4rpx 20rpx rgba(46,213,115, 0.06);
}
.tab-item {
	flex: 1;
	text-align: center;
	padding: 16rpx 0;
	border-radius: 36rpx;
	font-size: 26rpx;
	color: #888;
	background: #f0f1f8;
	transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
	position: relative;
}
.tab-item.active {
	background: linear-gradient(135deg, #2ed573 0%, #27ae60 100%);
	color: #fff;
	font-weight: 700;
	box-shadow: 0 6rpx 24rpx rgba(46,213,115, 0.35);
	transform: scale(1.02);
}

/* ===== 列表滚动区 ===== */
.list-scroll {
	height: calc(100vh - 100rpx);
}

/* ===== 骨架屏 ===== */
.skeleton-list {
	padding: 24rpx;
}
.skeleton-card {
	background: #fff;
	border-radius: 24rpx;
	overflow: hidden;
	margin-bottom: 24rpx;
	box-shadow: 0 4rpx 24rpx rgba(0, 0, 0, 0.05);
}
.skeleton-cover {
	height: 300rpx;
	background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
	background-size: 200% 100%;
	animation: shimmer 1.5s infinite;
}
.skeleton-body {
	padding: 24rpx;
}
.skeleton-line {
	height: 28rpx;
	background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
	background-size: 200% 100%;
	animation: shimmer 1.5s infinite;
	border-radius: 10rpx;
	margin-bottom: 16rpx;
}
.skeleton-line.w80 { width: 80%; }
.skeleton-line.w60 { width: 60%; }
.skeleton-line.w40 { width: 40%; }
@keyframes shimmer {
	0% { background-position: 200% 0; }
	100% { background-position: -200% 0; }
}

/* ===== 活动卡片 ===== */
.activity-list {
	padding: 24rpx;
}
.activity-card {
	background: #fff;
	border-radius: 24rpx;
	overflow: hidden;
	margin-bottom: 28rpx;
	box-shadow: 0 8rpx 32rpx rgba(46,213,115, 0.08), 0 2rpx 8rpx rgba(0, 0, 0, 0.04);
	transition: transform 0.25s ease, box-shadow 0.25s ease;
	position: relative;
}
/* 顶部渐变装饰线 */
.activity-card::before {
	content: '';
	position: absolute;
	top: 0;
	left: 0;
	right: 0;
	height: 2rpx;
	background: linear-gradient(90deg, #2ed573, #27ae60, #1abc9c);
	border-radius: 2rpx 2rpx 0 0;
	z-index: 5;
	box-shadow: 0 0 6rpx rgba(46,213,115, 0.25);
}
.activity-card:active {
	transform: scale(0.985);
	box-shadow: 0 4rpx 16rpx rgba(46,213,115, 0.12);
}

/* 封面区域 */
.card-cover {
	position: relative;
	height: 320rpx;
	overflow: hidden;
}
.cover-img {
	width: 100%;
	height: 100%;
	transition: transform 0.4s ease;
}
.activity-card:active .cover-img {
	transform: scale(1.02);
}
/* 封面渐变遮罩 - 底部向上 */
.card-cover::after {
	content: '';
	position: absolute;
	left: 0;
	right: 0;
	bottom: 0;
	height: 60%;
	background: linear-gradient(transparent, rgba(0, 0, 0, 0.15));
	pointer-events: none;
}

/* 类型标签 + 发光 */
.type-tag {
	position: absolute;
	top: 20rpx;
	left: 20rpx;
	padding: 8rpx 22rpx;
	border-radius: 24rpx;
	font-size: 22rpx;
	color: #fff;
	background: linear-gradient(135deg, #2ed573, #27ae60);
	box-shadow: 0 4rpx 16rpx rgba(46,213,115, 0.4), 0 0 20rpx rgba(46,213,115, 0.2);
	animation: tagGlow 2s ease-in-out infinite alternate;
}
.type-tag.offline {
	background: linear-gradient(135deg, #ff6b6b, #ff8e8e);
	box-shadow: 0 4rpx 16rpx rgba(255, 107, 107, 0.4), 0 0 20rpx rgba(255, 107, 107, 0.2);
}
.type-tag.both {
	background: linear-gradient(135deg, #2ed573, #3be88a);
	box-shadow: 0 4rpx 16rpx rgba(46,213,115, 0.4), 0 0 20rpx rgba(46,213,115, 0.2);
}
@keyframes tagGlow {
	0% { filter: brightness(1); }
	100% { filter: brightness(1.15); }
}

.signed-tag {
	position: absolute;
	top: 20rpx;
	right: 20rpx;
	padding: 8rpx 22rpx;
	border-radius: 24rpx;
	font-size: 22rpx;
	color: #fff;
	background: linear-gradient(135deg, #2ed573, #3be88a);
	box-shadow: 0 4rpx 14rpx rgba(46,213,115, 0.35), 0 0 16rpx rgba(46,213,115, 0.15);
}

/* ===== 卡片内容 ===== */
.card-body {
	padding: 26rpx;
}
.card-title {
	font-size: 32rpx;
	font-weight: 700;
	color: #1a1a2e;
	display: block;
	margin-bottom: 16rpx;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
}
.card-info {
	display: flex;
	align-items: center;
	margin-bottom: 10rpx;
}
.info-icon {
	font-size: 26rpx;
	margin-right: 10rpx;
}
.info-text {
	font-size: 24rpx;
	color: #999;
}

/* ===== 卡片底部 ===== */
.card-footer {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-top: 16rpx;
	padding-top: 16rpx;
	border-top: 1rpx solid rgba(46,213,115, 0.06);
}
.progress-info {
	flex: 1;
}
.count-text {
	font-size: 24rpx;
	color: #888;
	margin-bottom: 6rpx;
	display: block;
}
.mini-progress {
	width: 160rpx;
	height: 10rpx;
	background: rgba(46,213,115, 0.08);
	border-radius: 10rpx;
	overflow: hidden;
	position: relative;
}
.mini-progress-bar {
	height: 100%;
	background: linear-gradient(90deg, #2ed573, #27ae60);
	border-radius: 10rpx;
	transition: width 0.5s ease;
	box-shadow: 0 2rpx 8rpx rgba(46,213,115, 0.3);
	position: relative;
}
/* 进度条光泽扫过效果 */
.mini-progress-bar::after {
	content: '';
	position: absolute;
	top: 0;
	left: -100%;
	width: 25%;
	height: 100%;
	background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
	animation: progressShine 5s ease-in-out infinite;
}
@keyframes progressShine {
	0% { left: -100%; }
	100% { left: 100%; }
}

.status-badge {
	padding: 6rpx 20rpx;
	border-radius: 24rpx;
	font-size: 22rpx;
	font-weight: 600;
}
.status-badge.open {
	background: rgba(46,213,115, 0.1);
	box-shadow: 0 2rpx 8rpx rgba(46,213,115, 0.12), 0 0 12rpx rgba(46,213,115, 0.06);
}
.status-badge.full {
	color: #ff6b6b;
	background: rgba(255, 107, 107, 0.1);
	box-shadow: 0 2rpx 8rpx rgba(255, 107, 107, 0.12), 0 0 12rpx rgba(255, 107, 107, 0.06);
}
.status-badge.deadline {
	color: #999;
	background: rgba(153, 153, 153, 0.1);
	box-shadow: 0 2rpx 8rpx rgba(153, 153, 153, 0.08);
}

/* ===== 空状态 ===== */
.empty-state {
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	padding: 120rpx 0;
	position: relative;
}
.empty-state::before {
	content: '';
	position: absolute;
	width: 240rpx;
	height: 240rpx;
	border-radius: 50%;
	background: linear-gradient(135deg, rgba(46,213,115, 0.08), rgba(126, 217, 87, 0.04));
	top: 80rpx;
}
.empty-icon {
	font-size: 100rpx;
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

/* ===== 加载更多 ===== */
.load-more {
	text-align: center;
	padding: 30rpx 0;
	font-size: 24rpx;
	color: #c0c0c0;
	letter-spacing: 2rpx;
}
</style>
