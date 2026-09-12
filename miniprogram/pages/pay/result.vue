<template>
	<view class="page">
		<view class="result-container">
			<!-- 状态图标 -->
			<view class="status-icon" :class="status" :style="status === 'success' ? 'background:' + tc.primary + ';' : ''">
				<text class="icon-text" v-if="status === 'success'">✓</text>
				<text class="icon-text" v-else>✕</text>
			</view>

			<!-- 状态消息 -->
			<text class="status-title" v-if="status === 'success'">支付成功</text>
			<text class="status-title" v-else-if="status === 'fail'">支付失败</text>
			<text class="status-title" v-else>支付结果</text>

			<text class="status-desc" v-if="status === 'success'">恭喜您，订单支付成功！</text>
			<text class="status-desc" v-else-if="status === 'fail'">支付过程中出现问题，请重试</text>
			<text class="status-desc" v-else>正在确认支付结果...</text>

			<!-- 订单信息 -->
			<view class="order-card" v-if="orderInfo">
				<view class="card-row">
					<text class="row-label">订单号</text>
					<text class="row-value">{{ orderInfo.order_no }}</text>
				</view>
				<view class="card-row" v-if="orderInfo.title">
					<text class="row-label">商品名称</text>
					<text class="row-value">{{ orderInfo.title }}</text>
				</view>
				<view class="card-row" v-if="orderInfo.amount">
					<text class="row-label">支付金额</text>
					<text class="row-value price">¥{{ orderInfo.amount }}</text>
				</view>
				<view class="card-row" v-if="orderInfo.pay_time">
					<text class="row-label">支付时间</text>
					<text class="row-value">{{ orderInfo.pay_time }}</text>
				</view>
			</view>

			<!-- 操作按钮 -->
			<view class="action-area">
				<view class="action-btn primary" v-if="status === 'success'" @tap="viewOrder">
					<text>查看订单</text>
				</view>
				<view class="action-btn primary" v-if="status === 'fail'" @tap="retryPay">
					<text>重新支付</text>
				</view>
				<view class="action-btn secondary" :style="tPri" @tap="goHome">
					<text>继续浏览</text>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
import http from '@/utils/http.js';

export default {
	data() {
		return {
			status: 'pending',
			orderId: '',
			orderInfo: null
		};
	},
	onLoad(options) {
		this.status = options.status || 'pending';
		this.orderId = options.order_id || '';
		if (this.orderId) {
			this.loadOrderInfo();
		}
		if (this.status === 'pending' && this.orderId) {
			this.checkPayResult();
		}
	},
	methods: {
		async loadOrderInfo() {
			try {
				const res = await http.get('/api/order/detail', { order_id: this.orderId });
				if (res.code === 0) {
					this.orderInfo = res.data || {};
					if (this.orderInfo.status === 'paid') {
						this.status = 'success';
					}
				}
			} catch (e) {}
		},
		async checkPayResult() {
			uni.showLoading({ title: '确认支付结果...' });
			let retryCount = 0;
			const maxRetry = 5;
			const check = async () => {
				try {
					const res = await http.get('/api/order/status', { order_id: this.orderId });
					if (res.code === 0 && res.data && res.data.status === 'paid') {
						this.status = 'success';
						this.orderInfo = res.data;
						uni.hideLoading();
						return;
					}
				} catch (e) {}
				retryCount++;
				if (retryCount < maxRetry) {
					setTimeout(check, 2000);
				} else {
					uni.hideLoading();
					this.status = 'fail';
				}
			};
			check();
		},
		viewOrder() {
			uni.navigateTo({ url: '/pages/user/orders' });
		},
		retryPay() {
			if (this.orderId) {
				uni.navigateTo({ url: `/pages/user/orders` });
			}
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
.page {
	min-height: 100vh;
	background: linear-gradient(180deg, #f0f2ff 0%, #f5f6fa 200rpx);
	display: flex;
	align-items: center;
	justify-content: center;
}

.result-container {
	width: 100%;
	padding: 60rpx 40rpx;
	display: flex;
	flex-direction: column;
	align-items: center;
}

/* ===== 状态图标 + 动画 ===== */
.status-icon {
	width: 160rpx;
	height: 160rpx;
	border-radius: 50%;
	display: flex;
	align-items: center;
	justify-content: center;
	margin-bottom: 36rpx;
	position: relative;
}
/* 成功：呼吸灯 + 外环旋转 + 金色粒子散射 */
.status-icon.success {
	background: linear-gradient(135deg, #2ed573, #3be88a);
	box-shadow:
		0 8rpx 36rpx rgba(46,213,115, 0.35),
		0 0 60rpx rgba(255, 215, 0, 0.15),
		20rpx -30rpx 0 -14rpx rgba(255, 215, 0, 0.5),
		-25rpx -20rpx 0 -14rpx rgba(255, 200, 0, 0.4),
		30rpx 10rpx 0 -14rpx rgba(255, 180, 0, 0.35),
		-20rpx 25rpx 0 -14rpx rgba(255, 215, 0, 0.45);
	animation: breatheSuccess 2s ease-in-out infinite;
}
.status-icon.success::before {
	content: '';
	position: absolute;
	top: -12rpx;
	left: -12rpx;
	right: -12rpx;
	bottom: -12rpx;
	border-radius: 50%;
	border: 4rpx solid transparent;
	border-top-color: #2ed573;
	border-right-color: rgba(46,213,115, 0.4);
	animation: ringRotate 1.5s linear infinite;
}
.status-icon.success::after {
	content: '';
	position: absolute;
	top: -24rpx;
	left: -24rpx;
	right: -24rpx;
	bottom: -24rpx;
	border-radius: 50%;
	border: 2rpx solid transparent;
	border-bottom-color: rgba(123, 237, 159, 0.3);
	border-left-color: rgba(46,213,115, 0.15);
	animation: ringRotate 2.5s linear infinite reverse;
}

/* 失败：闪烁 + 外环旋转 */
.status-icon.fail {
	background: linear-gradient(135deg, #ff4757, #ff6b81);
	box-shadow: 0 8rpx 36rpx rgba(255, 71, 87, 0.35);
	animation: breatheFail 2.5s ease-in-out infinite;
}
.status-icon.fail::before {
	content: '';
	position: absolute;
	top: -12rpx;
	left: -12rpx;
	right: -12rpx;
	bottom: -12rpx;
	border-radius: 50%;
	border: 4rpx solid transparent;
	border-top-color: #ff4757;
	border-right-color: rgba(255, 107, 129, 0.4);
	animation: ringRotate 1.5s linear infinite;
}

/* 待定：脉冲旋转 */
.status-icon.pending {
	background: linear-gradient(135deg, #ff9f43, #feca57);
	box-shadow: 0 8rpx 36rpx rgba(255, 159, 67, 0.35);
	animation: breathePending 1.5s ease-in-out infinite;
}
.status-icon.pending::before {
	content: '';
	position: absolute;
	top: -12rpx;
	left: -12rpx;
	right: -12rpx;
	bottom: -12rpx;
	border-radius: 50%;
	border: 4rpx solid transparent;
	border-top-color: #ff9f43;
	border-right-color: #feca57;
	animation: ringRotate 1s linear infinite;
}

@keyframes breatheSuccess {
	0%, 100% { box-shadow: 0 8rpx 36rpx rgba(46,213,115, 0.3), 0 0 60rpx rgba(255, 215, 0, 0.1), 20rpx -30rpx 0 -14rpx rgba(255, 215, 0, 0.5), -25rpx -20rpx 0 -14rpx rgba(255, 200, 0, 0.4), 30rpx 10rpx 0 -14rpx rgba(255, 180, 0, 0.35), -20rpx 25rpx 0 -14rpx rgba(255, 215, 0, 0.45); transform: scale(1); }
	50% { box-shadow: 0 12rpx 50rpx rgba(46,213,115, 0.5), 0 0 80rpx rgba(255, 215, 0, 0.25), 25rpx -38rpx 0 -12rpx rgba(255, 215, 0, 0.7), -30rpx -25rpx 0 -12rpx rgba(255, 200, 0, 0.6), 38rpx 14rpx 0 -12rpx rgba(255, 180, 0, 0.5), -25rpx 32rpx 0 -12rpx rgba(255, 215, 0, 0.6); transform: scale(1.04); }
}
@keyframes breatheFail {
	0%, 100% { box-shadow: 0 8rpx 36rpx rgba(255, 71, 87, 0.3); opacity: 1; }
	50% { box-shadow: 0 12rpx 50rpx rgba(255, 71, 87, 0.5); opacity: 0.85; }
}
@keyframes breathePending {
	0%, 100% { box-shadow: 0 8rpx 36rpx rgba(255, 159, 67, 0.3); transform: scale(1); }
	50% { box-shadow: 0 12rpx 50rpx rgba(255, 159, 67, 0.5); transform: scale(1.06); }
}
@keyframes ringRotate {
	0% { transform: rotate(0deg); }
	100% { transform: rotate(360deg); }
}

.icon-text {
	font-size: 64rpx;
	color: #fff;
	font-weight: 700;
	text-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.15);
}

/* ===== 状态文字 ===== */
.status-title {
	font-size: 38rpx;
	font-weight: 700;
	color: #1a1a2e;
	margin-bottom: 12rpx;
}
.status-desc {
	font-size: 26rpx;
	color: #aaa;
	margin-bottom: 48rpx;
}

/* ===== 订单信息卡片 ===== */
.order-card {
	width: 100%;
	background: #fff;
	border-radius: 24rpx;
	padding: 32rpx;
	margin-bottom: 48rpx;
	box-shadow: 0 12rpx 40rpx rgba(46,213,115, 0.08), 0 2rpx 8rpx rgba(0, 0, 0, 0.03);
	position: relative;
	overflow: hidden;
}
/* 卡片顶部装饰线 */
.order-card::before {
	content: '';
	position: absolute;
	top: 0;
	left: 0;
	right: 0;
	height: 2rpx;
	background: linear-gradient(90deg, #2ed573, #27ae60, #2ed573);
	border-radius: 2rpx 2rpx 0 0;
	box-shadow: 0 0 6rpx rgba(46,213,115, 0.3);
}
.card-row {
	display: flex;
	justify-content: space-between;
	align-items: center;
	padding: 16rpx 0;
	border-bottom: 1rpx solid rgba(46,213,115, 0.04);
	transition: background 0.2s ease;
}
/* 交替背景色 */
.card-row:nth-child(odd) {
	background: rgba(46,213,115, 0.015);
	border-radius: 8rpx;
}
.card-row:last-child {
	border-bottom: none;
}
.row-label {
	font-size: 26rpx;
	color: #aaa;
}
.row-value {
	font-size: 26rpx;
	color: #333;
	font-weight: 500;
}
.row-value.price {
	color: #ff4757;
	font-weight: 800;
	font-size: 32rpx;
}

/* ===== 操作按钮 ===== */
.action-area {
	width: 100%;
	display: flex;
	flex-direction: column;
	gap: 24rpx;
}
.action-btn {
	text-align: center;
	padding: 28rpx;
	border-radius: 48rpx;
	font-size: 30rpx;
	font-weight: 700;
	transition: transform 0.2s ease, box-shadow 0.2s ease;
	position: relative;
	overflow: hidden;
}
.action-btn:active {
	transform: scale(0.97);
}
/* 主要CTA按钮shimmer */
.action-btn.primary {
	background: linear-gradient(135deg, #2ed573 0%, #27ae60 100%);
	color: #fff;
	box-shadow: 0 10rpx 36rpx rgba(46,213,115, 0.3);
}
.action-btn.primary::after {
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
.action-btn.primary:active {
	box-shadow: 0 4rpx 16rpx rgba(46,213,115, 0.2);
}
.action-btn.secondary {
	background: rgba(255, 255, 255, 0.85);
	backdrop-filter: blur(16rpx);
	-webkit-backdrop-filter: blur(16rpx);

	border: 2rpx solid rgba(46,213,115, 0.2);
	box-shadow: 0 4rpx 16rpx rgba(46,213,115, 0.06);
}
</style>
