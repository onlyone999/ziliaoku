<template>
	<view class="page">
		<scroll-view
			scroll-y
			class="list-scroll"
			@scrolltolower="loadMore"
			:refresher-enabled="true"
			:refresher-triggered="isRefreshing"
			@refresherrefresh="onRefresh"
		>
			<view class="fav-list" v-if="favorites.length > 0">
				<view
					class="fav-card"
					v-for="item in favorites"
					:key="item.id"
					@tap="goDetail(item)"
					@longpress="onLongPress(item)"
				>
					<view class="card-main">
						<image class="card-cover" :src="fixUrl(item.cover_url)" mode="aspectFill"></image>
						<view class="card-info">
							<text class="card-title">{{ item.title }}</text>
							<view class="card-tags">
								<text class="card-badge" :style="tPri" v-if="item.category_name">{{ item.category_name }}</text>
							</view>
							<view class="card-bottom">
								<text class="card-count">{{ item.download_count }} 下载</text>
								<text class="card-price" v-if="item.price > 0">¥{{ item.price }}</text>
								<view class="card-free-tag" v-else :style="'background:' + tc.primary + ';'">
									<text>免费</text>
								</view>
							</view>
						</view>
						<view class="unfav-btn" @tap.stop="unfavorite(item)">
							<text class="unfav-icon">❤️</text>
						</view>
					</view>
				</view>
			</view>

			<view class="empty" v-if="!loading && favorites.length === 0">
				<text class="empty-icon">❤️</text>
				<text class="empty-title">暂无收藏</text>
				<text class="empty-desc">收藏喜欢的资源，方便下次查看</text>
				<view class="empty-btn" @tap="goHome">
					<text>去首页看看</text>
				</view>
			</view>

			<view class="load-status">
				<text v-if="loading">加载中...</text>
				<text v-else-if="noMore && favorites.length > 0">— 没有更多了 —</text>
			</view>
		</scroll-view>
	</view>
</template>

<script>
import http, { BASE_URL } from '@/utils/http.js';

export default {
	data() {
		return {
			favorites: [],
			page: 1,
			pageSize: 15,
			loading: false,
			noMore: false,
			isRefreshing: false
		};
	},
	onLoad() {
		this.loadFavorites();
	},
	methods: {
		async loadFavorites() {
			if (this.loading || this.noMore) return;
			this.loading = true;
			try {
				const res = await http.get('/api/favorite/list', {
					page: this.page,
					page_size: this.pageSize
				});
				if (res.code === 0) {
					const list = res.data.list || res.data || [];
					if (this.page === 1) {
						this.favorites = list;
					} else {
						this.favorites = [...this.favorites, ...list];
					}
					if (list.length < this.pageSize) {
						this.noMore = true;
					}
					this.page++;
				}
			} catch (e) {
				console.error('加载收藏失败', e);
			} finally {
				this.loading = false;
			}
		},
		async unfavorite(item) {
			uni.showModal({
				title: '确认取消',
				content: `确定取消收藏「${item.title}」吗？`,
				success: async (res) => {
					if (res.confirm) {
						try {
							const resp = await http.post('/api/favorite/toggle', {
								resource_id: item.resource_id || item.id,
								action: 'cancel'
							});
							if (resp.code === 0) {
								this.favorites = this.favorites.filter(f =>
									(f.resource_id || f.id) !== (item.resource_id || item.id)
								);
								uni.showToast({ title: '已取消收藏', icon: 'none' });
							}
						} catch (e) {
							uni.showToast({ title: '操作失败', icon: 'none' });
						}
					}
				}
			});
		},
		onLongPress(item) {
			uni.showActionSheet({
				itemList: ['取消收藏', '查看详情'],
				success: (res) => {
					if (res.tapIndex === 0) {
						this.unfavorite(item);
					} else if (res.tapIndex === 1) {
						this.goDetail(item);
					}
				}
			});
		},
		async onRefresh() {
			this.isRefreshing = true;
			this.page = 1;
			this.noMore = false;
			this.favorites = [];
			await this.loadFavorites();
			this.isRefreshing = false;
		},
		loadMore() {
			this.loadFavorites();
		},
		goDetail(item) {
			uni.navigateTo({ url: `/pages/resource/detail?id=${item.resource_id || item.id}` });
		},
		fixUrl(url) {
			if (!url) return '';
			return url.startsWith('http') ? url : BASE_URL + url;
		},
		goHome() {
			uni.switchTab({ url: '/pages/index/index' });
		}
	},
	onShareAppMessage() {
		return {
			title: '海量资源免费下载',
			path: '/pages/index/index'
		};
	}
};
</script>

<style scoped>
/* ===== 页面基础 ===== */
.page {
	min-height: 100vh;
	background: linear-gradient(180deg, #f0f2ff 0%, #f5f6fa 200rpx);
}
.list-scroll {
	height: 100vh;
}

/* ===== 收藏列表 ===== */
.fav-list {
	padding: 16rpx 24rpx;
}
.fav-card {
	background: #fff;
	border-radius: 24rpx;
	margin-bottom: 20rpx;
	overflow: hidden;
	box-shadow: 0 8rpx 32rpx rgba(46,213,115, 0.08), 0 2rpx 8rpx rgba(0, 0, 0, 0.04);
	transition: transform 0.2s ease, box-shadow 0.2s ease;
	position: relative;
}
/* 顶部渐变装饰线 */
.fav-card::before {
	content: '';
	position: absolute;
	top: 0;
	left: 30%;
	right: 30%;
	height: 2rpx;
	background: linear-gradient(90deg, transparent, rgba(46,213,115, 0.15), transparent);
	border-radius: 2rpx;
	z-index: 2;
}
.fav-card:active {
	transform: scale(0.985);
	box-shadow: 0 4rpx 16rpx rgba(46,213,115, 0.12);
}
.card-main {
	display: flex;
}
.card-cover {
	width: 220rpx;
	height: 180rpx;
	flex-shrink: 0;
	transition: transform 0.4s ease;
}
.fav-card:active .card-cover {
	transform: scale(1.02);
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
	line-height: 1.5;
}
.card-tags {
	margin-top: 10rpx;
}
.card-badge {
	display: inline-block;
	font-size: 20rpx;
	background: linear-gradient(135deg, rgba(46,213,115, 0.12), rgba(90, 82, 213, 0.06));
	padding: 6rpx 18rpx;
	border-radius: 20rpx;
	font-weight: 500;
	letter-spacing: 1rpx;
	box-shadow: 0 2rpx 8rpx rgba(46,213,115, 0.08);
}
.card-bottom {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-top: auto;
}
.card-count {
	font-size: 22rpx;
	color: #aaa;
}
.card-price {
	font-size: 28rpx;
	color: #ff4757;
	font-weight: 700;
}
.card-free-tag {
	padding: 4rpx 16rpx;
	border-radius: 8rpx;
	background: #2ed573;
}
.card-free-tag text {
	font-size: 20rpx;
	color: #fff;
	font-weight: 600;
}

/* ===== 取消收藏按钮 - 渐变边框 ===== */
.card-main {
	position: relative;
}
.unfav-btn {
	position: absolute;
	top: 8rpx;
	right: 8rpx;
	width: 56rpx;
	height: 56rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	background: rgba(255, 255, 255, 0.85);
	border-radius: 50%;
	z-index: 2;
	transition: all 0.2s;
}
.unfav-btn:active {
	transform: scale(0.85);
	background: rgba(255, 255, 255, 0.6);
}
.unfav-icon {
	font-size: 28rpx;
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
	top: 40rpx;
	width: 320rpx;
	height: 320rpx;
	border-radius: 50%;
	background: linear-gradient(135deg, rgba(46,213,115, 0.06), rgba(255, 107, 129, 0.06));
	filter: blur(2rpx);
}
.empty-icon {
	font-size: 120rpx;
	margin-bottom: 32rpx;
	position: relative;
	z-index: 1;
	animation: breathe 3s ease-in-out infinite;
}
@keyframes breathe {
	0%, 100% { transform: scale(1); }
	50% { transform: scale(1.04); }
}
.empty-title {
	font-size: 34rpx;
	color: #1a1a2e;
	font-weight: 700;
	margin-bottom: 12rpx;
	position: relative;
	z-index: 1;
}
.empty-desc {
	font-size: 26rpx;
	color: #aaa;
	margin-bottom: 40rpx;
	position: relative;
	z-index: 1;
}
.empty-btn {
	padding: 20rpx 56rpx;
	background: linear-gradient(135deg, #2ed573, #5A52D5);
	border-radius: 40rpx;
	font-size: 28rpx;
	color: #fff;
	font-weight: 600;
	box-shadow: 0 8rpx 24rpx rgba(46,213,115, 0.3);
	position: relative;
	z-index: 1;
	transition: all 0.3s ease;
	overflow: hidden;
}
/* 空状态按钮shimmer */
.empty-btn::after {
	content: '';
	position: absolute;
	top: 0;
	left: -100%;
	width: 25%;
	height: 100%;
	background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
	animation: btnShimmer 5s ease-in-out infinite;
}
@keyframes btnShimmer {
	0% { left: -100%; }
	50% { left: 100%; }
	100% { left: 100%; }
}
.empty-btn:active {
	transform: scale(0.96);
	box-shadow: 0 4rpx 12rpx rgba(46,213,115, 0.4);
}

/* ===== 加载状态 ===== */
.load-status {
	text-align: center;
	padding: 30rpx 0;
	font-size: 24rpx;
	color: #aaa;
}
.load-status text:first-child {
	display: inline-block;
	animation: pulse-text 1.4s ease-in-out infinite;
}
@keyframes pulse-text {
	0%, 100% { opacity: 0.4; }
	50% { opacity: 1; }
}
</style>
