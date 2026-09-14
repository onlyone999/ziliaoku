<template>
	<view class="page" :class="'theme-' + activeTheme" :style="pageBgStyle">
		<!-- 订单状态标签 -->
		<view class="tabs-bar">
			<view
				class="tab-item"
				:class="{ active: currentTab === 'all' }"
				:style="currentTab === 'all' ? 'color:' + tc.primary + ';' : ''"
				@tap="switchTab('all')"
			>全部</view>
			<view
				class="tab-item"
				:class="{ active: currentTab === 'pending' }"
				:style="currentTab === 'pending' ? 'color:' + tc.primary + ';' : ''"
				@tap="switchTab('pending')"
			>待支付</view>
			<view
				class="tab-item"
				:class="{ active: currentTab === 'paid' }"
				:style="currentTab === 'paid' ? 'color:' + tc.primary + ';' : ''"
				@tap="switchTab('paid')"
			>已完成</view>
			<view
				class="tab-item"
				:class="{ active: currentTab === 'cancelled' }"
				:style="currentTab === 'cancelled' ? 'color:' + tc.primary + ';' : ''"
				@tap="switchTab('cancelled')"
			>已取消</view>
		</view>

		<scroll-view
			scroll-y
			class="list-scroll"
			@scrolltolower="loadMore"
			:refresher-enabled="true"
			:refresher-triggered="isRefreshing"
			@refresherrefresh="onRefresh"
		>
			<view class="order-list" v-if="orders.length > 0">
				<view
					class="order-card"
					v-for="item in orders"
					:key="item.id"
				>
					<view class="order-header">
						<text class="order-no">订单号: {{ item.order_no }}</text>
						<view class="order-status-badge" :class="item.status" :style="item.status === 'paid' ? tPri : ''">
							<text>{{ statusText(item.status) }}</text>
						</view>
					</view>
					<view class="order-body" @tap="goDetail(item)">
						<image class="order-cover" :src="fixUrl(item.cover_url)" mode="aspectFill" v-if="item.cover_url"></image>
						<view class="order-info">
							<text class="order-title">{{ item.resource_title || item.title }}</text>
							<view class="order-meta">
								<text class="order-time">{{ item.created_at }}</text>
							</view>
						</view>
					</view>
					<view class="order-footer">
						<view class="amount-row">
							<text class="amount-label">实付:</text>
							<text class="order-amount">¥{{ item.amount || item.price }}</text>
						</view>
						<view class="order-actions">
							<view
								class="action-btn pay-btn"
								v-if="item.status === 'pending'"
								@tap="payOrder(item)"
							>
								<text>去支付</text>
							</view>
							<view
								class="action-btn cancel-btn"
								v-if="item.status === 'pending'"
								@tap="cancelOrder(item)"
							>
								<text>取消</text>
							</view>
							<view
								class="action-btn download-btn"
								v-if="item.status === 'paid'"
								@tap="downloadResource(item)"
							>
								<text>下载</text>
							</view>
							<view
								class="action-btn detail-btn"
								:style="tPri"
								@tap="goDetail(item)"
							>
								<text>详情</text>
							</view>
						</view>
					</view>
				</view>
			</view>

			<view class="empty" v-if="!loading && orders.length === 0">
				<text class="empty-icon">📋</text>
				<text class="empty-title">暂无订单</text>
				<text class="empty-desc">快去挑选心仪的资源吧</text>
			</view>

			<view class="load-status">
				<text v-if="loading">加载中...</text>
				<text v-else-if="noMore && orders.length > 0">— 没有更多了 —</text>
			</view>
		</scroll-view>
	</view>
</template>

<script>
import http, { BASE_URL } from '@/utils/http.js';

export default {
	data() {
		return {
			currentTab: 'all',
			orders: [],
			page: 1,
			loading: false,
			noMore: false,
			isRefreshing: false
		};
	},
	onLoad() {
		this.loadOrders();
	},
	methods: {
		async loadOrders() {
			if (this.loading || this.noMore) return;
			this.loading = true;
			try {
				const params = {
					page: this.page,
					page_size: this.pageSize
				};
				if (this.currentTab !== 'all') {
					params.status = this.currentTab;
				}
				const res = await http.get('/api/order/list', params);
				if (res.code === 0) {
					const list = res.data.list || res.data || [];
					if (this.page === 1) {
						this.orders = list;
					} else {
						this.orders = [...this.orders, ...list];
					}
					if (list.length < this.pageSize) {
						this.noMore = true;
					}
					this.page++;
				}
			} catch (e) {
				console.error('加载订单失败', e);
			} finally {
				this.loading = false;
			}
		},
		switchTab(tab) {
			if (this.currentTab === tab) return;
			this.currentTab = tab;
			this.page = 1;
			this.noMore = false;
			this.orders = [];
			this.loadOrders();
		},
		async onRefresh() {
			this.isRefreshing = true;
			this.page = 1;
			this.noMore = false;
			this.orders = [];
			await this.loadOrders();
			this.isRefreshing = false;
		},
		loadMore() {
			this.loadOrders();
		},
		statusText(status) {
			const map = {
				pending: '待支付',
				paid: '已完成',
				cancelled: '已取消',
				refunded: '已退款'
			};
			return map[status] || status;
		},
		async payOrder(item) {
			uni.showLoading({ title: '创建支付...' });
			try {
				const res = await http.post('/api/order/pay', { order_id: item.id });
				if (res.code === 0 && res.data) {
					uni.hideLoading();
					const payParams = res.data.pay_params || {};
					if (payParams.wxpay) {
						uni.requestPayment({
							provider: 'wxpay',
							...payParams.wxpay,
							success: async () => {
								await this.onPaySuccess(item.id);
							},
							fail: (err) => {
								if (err.errMsg && err.errMsg.includes('cancel')) {
									uni.showToast({ title: '已取消支付', icon: 'none' });
								} else {
									uni.showToast({ title: '支付失败', icon: 'none' });
								}
							}
						});
					} else if (payParams.virtual) {
						uni.requestVirtualPayment({
							...payParams.virtual,
							success: async () => {
								await this.onPaySuccess(item.id);
							},
							fail: (err) => {
								if (err.errMsg && err.errMsg.includes('cancel')) {
									uni.showToast({ title: '已取消支付', icon: 'none' });
								} else {
									uni.showToast({ title: '支付失败', icon: 'none' });
								}
							}
						});
					}
				} else {
					uni.hideLoading();
					uni.showToast({ title: res.msg || '创建支付失败', icon: 'none' });
				}
			} catch (e) {
				uni.hideLoading();
				uni.showToast({ title: '支付异常', icon: 'none' });
			}
		},
		async onPaySuccess(orderId) {
			try {
				await http.post('/api/order/callback', { order_id: orderId });
			} catch (e) {}
			uni.showToast({ title: '支付成功', icon: 'success' });
			this.page = 1;
			this.noMore = false;
			this.orders = [];
			this.loadOrders();
		},
		async cancelOrder(item) {
			uni.showModal({
				title: '确认取消',
				content: '确定取消该订单吗？',
				success: async (res) => {
					if (res.confirm) {
						try {
							const resp = await http.post('/api/order/cancel', { id: item.id });
							if (resp.code === 0) {
								uni.showToast({ title: '已取消', icon: 'success' });
								this.page = 1;
								this.noMore = false;
								this.orders = [];
								this.loadOrders();
							}
						} catch (e) {
							uni.showToast({ title: '操作失败', icon: 'none' });
						}
					}
				}
			});
		},
		async downloadResource(item) {
			uni.showLoading({ title: '获取下载链接...' });
			try {
				const res = await http.post('/api/download/download', {
					resource_id: item.resource_id
				});
				if (res.code === 0 && res.data) {
					const fileUrl = (res.data.resource && res.data.resource.file_url) || res.data.file_url;
					if (fileUrl) {
						uni.hideLoading();
						uni.showLoading({ title: '正在下载...' });
						const downloadRes = await new Promise((resolve, reject) => {
							uni.downloadFile({
								url: fileUrl,
								success: resolve,
								fail: reject
							});
						});
						uni.hideLoading();
						if (downloadRes.statusCode === 200) {
							uni.openDocument({
								filePath: downloadRes.tempFilePath,
								showMenu: true,
								success: () => {
									uni.showToast({ title: '下载成功', icon: 'success' });
								},
								fail: () => {
									uni.showToast({ title: '文件已保存', icon: 'success' });
								}
							});
						} else {
							uni.showToast({ title: '下载失败', icon: 'none' });
						}
					} else {
						uni.hideLoading();
						uni.showToast({ title: '获取链接失败', icon: 'none' });
					}
				} else {
					uni.hideLoading();
					uni.showToast({ title: res.msg || '获取链接失败', icon: 'none' });
				}
			} catch (e) {
				uni.hideLoading();
				uni.showToast({ title: '下载失败', icon: 'none' });
			}
		},
		goDetail(item) {
			if (item.resource_id) {
				uni.navigateTo({ url: `/pages/resource/detail?id=${item.resource_id}` });
			}
		},
		fixUrl(url) {
			if (!url) return '';
			return url.startsWith('http') ? url : BASE_URL + url;
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
	background: transparent;
	display: flex;
	flex-direction: column;
}

/* ===== 标签栏 ===== */
.tabs-bar {
	display: flex;
	background: #fff;
	padding: 0 12rpx;
	flex-shrink: 0;
	box-shadow: 0 4rpx 16rpx rgba(124,179,66, 0.06);
	position: relative;
}
/* Tab栏底部光晕 */
.tabs-bar::after {
	content: '';
	position: absolute;
	bottom: -6rpx;
	left: 15%;
	right: 15%;
	height: 12rpx;
	background: linear-gradient(90deg, transparent, rgba(124,179,66, 0.12), transparent);
	border-radius: 50%;
	filter: blur(6rpx);
}
.tab-item {
	flex: 1;
	text-align: center;
	padding: 28rpx 0;
	font-size: 26rpx;
	color: #888;
	position: relative;
	transition: color 0.3s ease;
}
.tab-item.active {
	font-weight: 700;
}
.tab-item.active::after {
	content: '';
	position: absolute;
	bottom: 0;
	left: 50%;
	transform: translateX(-50%);
	width: 56rpx;
	height: 6rpx;
	background: linear-gradient(135deg, #7cb342, #558b2f);
	border-radius: 3rpx;
	box-shadow: 0 2rpx 8rpx rgba(124,179,66, 0.3);
}

.list-scroll {
	flex: 1;
}

/* ===== 订单列表 ===== */
.order-list {
	padding: 16rpx 24rpx;
}
.order-card {
	background: #fff;
	border-radius: 36rpx;
	margin-bottom: 20rpx;
	overflow: hidden;
	box-shadow:
		0 2rpx 8rpx rgba(0,0,0,0.03),
		0 8rpx 24rpx rgba(0,0,0,0.06),
		0 16rpx 40rpx rgba(0,0,0,0.04);
	border: 1rpx solid rgba(0,0,0,0.03);
	transition: transform 0.2s ease;
	position: relative;
}
/* 顶部渐变装饰线 */
.order-card::before {
	content: '';
	position: absolute;
	top: 0;
	left: 0;
	right: 0;
	height: 2rpx;
	background: linear-gradient(90deg, transparent 0%, rgba(0,0,0,0.06) 30%, rgba(0,0,0,0.08) 50%, rgba(0,0,0,0.06) 70%, transparent 100%);
	border-radius: 2rpx 2rpx 0 0;
	z-index: 2;
	box-shadow: 0 0 6rpx rgba(124,179,66, 0.25);
}
.order-card:active {
	transform: scale(0.985);
}
.order-header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	padding: 20rpx 24rpx;
	border-bottom: 1rpx solid rgba(124,179,66, 0.06);
}
.order-no {
	font-size: 24rpx;
	color: #aaa;
}
.order-status-badge {
	padding: 6rpx 18rpx;
	border-radius: 28rpx;
	font-size: 22rpx;
	font-weight: 600;
}
/* 待支付脉冲 */
.order-status-badge.pending {
	color: #ff9f43;
	background: rgba(255, 159, 67, 0.10);
	box-shadow: 0 2rpx 4rpx rgba(0,0,0,0.04);
	animation: pulse-orange 2s ease-in-out infinite;
}
@keyframes pulse-orange {
	0%, 100% { box-shadow: 0 2rpx 4rpx rgba(0,0,0,0.04); }
	50% { box-shadow: 0 2rpx 8rpx rgba(0,0,0,0.08); }
}
/* 已完成脉冲 */
.order-status-badge.paid {
	background: rgba(124,179,66, 0.10);
	box-shadow: 0 2rpx 4rpx rgba(0,0,0,0.04);
	animation: pulse-green 2s ease-in-out infinite;
}
@keyframes pulse-green {
	0%, 100% { box-shadow: 0 2rpx 4rpx rgba(0,0,0,0.04); }
	50% { box-shadow: 0 2rpx 8rpx rgba(0,0,0,0.08); }
}
.order-status-badge.cancelled {
	color: #999;
	background: linear-gradient(135deg, rgba(153, 153, 153, 0.14), rgba(153, 153, 153, 0.06));
	box-shadow: 0 2rpx 8rpx rgba(153, 153, 153, 0.1);
}
.order-status-badge.refunded {
	color: #ff4757;
	background: linear-gradient(135deg, rgba(255, 71, 87, 0.14), rgba(255, 71, 87, 0.06));
	box-shadow: 0 2rpx 4rpx rgba(255, 71, 87, 0.15);
	animation: pulse-red 2s ease-in-out infinite;
}
@keyframes pulse-red {
	0%, 100% { box-shadow: 0 2rpx 4rpx rgba(255, 71, 87, 0.15); }
	50% { box-shadow: 0 2rpx 8rpx rgba(255, 71, 87, 0.35), 0 0 6rpx rgba(255, 71, 87, 0.15); }
}

.order-body {
	display: flex;
	padding: 20rpx 24rpx;
}
.order-cover {
	width: 160rpx;
	height: 120rpx;
	border-radius: 28rpx;
	flex-shrink: 0;
	transition: transform 0.4s ease;
}
.order-card:active .order-cover {
	transform: scale(1.02);
}
.order-info {
	flex: 1;
	margin-left: 20rpx;
	display: flex;
	flex-direction: column;
	justify-content: space-between;
}
.order-title {
	font-size: 28rpx;
	color: #1c2333;
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
.order-meta {
	margin-top: 8rpx;
}
.order-time {
	font-size: 22rpx;
	color: #bbb;
}

.order-footer {
	display: flex;
	justify-content: space-between;
	align-items: center;
	padding: 18rpx 24rpx;
	border-top: 1rpx solid rgba(124,179,66, 0.06);
}
.amount-row {
	display: flex;
	align-items: baseline;
	gap: 6rpx;
}
.amount-label {
	font-size: 24rpx;
	color: #aaa;
}
.order-amount {
	font-size: 34rpx;
	color: #ff4757;
	font-weight: 700;
}
.order-actions {
	display: flex;
	gap: 12rpx;
}
.action-btn {
	padding: 10rpx 28rpx;
	border-radius: 28rpx;
	font-size: 24rpx;
	font-weight: 500;
	transition: all 0.3s ease;
}
.action-btn:active {
	transform: scale(0.95);
}
.pay-btn {
	background: linear-gradient(135deg, #ff4757, #ff6b81);
	color: #fff;
	box-shadow: 0 4rpx 16rpx rgba(255, 71, 87, 0.3);
	position: relative;
	overflow: hidden;
}
/* 支付按钮shimmer */
.pay-btn::after {
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
.cancel-btn {
	background: linear-gradient(135deg, #f0f0f0, #e8e8e8);
	color: #888;
	box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.04);
}
.download-btn {
	background: linear-gradient(135deg, #7cb342, #558b2f);
	color: #fff;
	box-shadow: 0 4rpx 16rpx rgba(124,179,66, 0.3);
}
.detail-btn {
	background: linear-gradient(135deg, #f0eeff, #e8e5ff);
	box-shadow: 0 2rpx 8rpx rgba(124,179,66, 0.08);
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
	top: 50rpx;
	width: 240rpx;
	height: 240rpx;
	border-radius: 50%;
	background: linear-gradient(160deg, #ffffff 0%, #e8f2dc 100%);
	filter: none;
}
.empty-icon {
	font-size: 96rpx;
	margin-bottom: 28rpx;
	position: relative;
	z-index: 1;
	animation: none;
}
@keyframes breathe {
	0%, 100% { transform: scale(1); }
	50% { transform: scale(1.04); }
}
.empty-title {
	font-size: 32rpx;
	color: #1a1a1a;
	font-weight: 700;
	margin-bottom: 12rpx;
	position: relative;
	z-index: 1;
}
.empty-desc {
	font-size: 26rpx;
	color: #a3b08a;
	position: relative;
	z-index: 1;
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
