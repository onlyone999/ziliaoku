<template>
	<view class="page" :class="'theme-' + activeTheme" :style="pageBgStyle">
		<scroll-view scroll-y class="scroll" :show-scrollbar="false">
			<!-- Logo区 -->
			<view class="logo-section">
				<view class="logo-circle" :style="'background:' + tc.primary + ';'">
					<text class="logo-text">Z</text>
				</view>
				<text class="app-name">{{ info.site_name || '资料库' }}</text>
				<text class="app-ver">{{ info.version || 'v1.0.0' }}</text>
			</view>

			<!-- 关于内容 -->
			<view class="card" v-if="info.content">
				<text class="card-title">关于我们</text>
				<text class="card-body">{{ info.content }}</text>
			</view>

			<!-- 二维码 -->
			<view class="card qr-card" v-if="info.qrcode">
				<text class="card-title">{{ info.qrcode_title || '扫码联系我们' }}</text>
				<view class="qr-wrap" @tap="previewQr">
					<image class="qr-img" :src="fixUrl(info.qrcode)" mode="aspectFit"></image>
				</view>
				<text class="qr-desc">{{ info.qrcode_desc || '长按识别二维码' }}</text>
			</view>

			<!-- 无二维码时的提示 -->
			<view class="card" v-else>
				<view class="empty-qr">
					<text class="empty-qr-icon">📱</text>
					<text class="empty-qr-text">暂未配置客服二维码</text>
				</view>
			</view>

			<!-- 版权 -->
			<view class="copyright">
				<text>{{ info.copyright || '© 2026 资源下载平台' }}</text>
			</view>

			<view style="height: 60rpx;"></view>
		</scroll-view>
	</view>
</template>

<script>
import http, { BASE_URL } from '@/utils/http.js';

export default {
	data() {
		return {
			info: {}
		};
	},
	onLoad() {
		this.loadInfo();
	},
	methods: {
		async loadInfo() {
			try {
				const res = await http.get('/api/about/info', {}, { silent: true });
				if (res.code === 0) this.info = res.data;
			} catch (e) {}
		},
		fixUrl(url) {
			if (!url) return '';
			if (url.startsWith('http')) return url;
			return BASE_URL + url;
		},
		previewQr() {
			if (!this.info.qrcode) return;
			uni.previewImage({
				urls: [this.fixUrl(this.info.qrcode)],
				current: this.fixUrl(this.info.qrcode)
			});
		}
	}
};
</script>

<style scoped>
.page { height: 100vh; background: transparent; overflow: hidden; display: flex; flex-direction: column; }
page { overflow: hidden; height: 100vh; }
.scroll { flex: 1; height: 0; padding: 0 28rpx; box-sizing: border-box; }

.logo-section {
	display: flex;
	flex-direction: column;
	align-items: center;
	padding: 60rpx 0 40rpx;
}
.logo-circle {
	width: 120rpx;
	height: 120rpx;
	border-radius: 36rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	margin-bottom: 20rpx;
	box-shadow: 0 12rpx 32rpx rgba(124, 179, 66, 0.2);
}
.logo-text {
	font-size: 52rpx;
	font-weight: 800;
	color: #fff;
}
.app-name {
	font-size: 36rpx;
	font-weight: 800;
	color: #1a1a1a;
	margin-bottom: 6rpx;
	letter-spacing: 1rpx;
}
.app-ver {
	font-size: 24rpx;
	color: #9aa88a;
}

.card {
	background: #fff;
	border-radius: 36rpx;
	padding: 28rpx;
	margin-bottom: 20rpx;
	box-shadow: 0 2rpx 12rpx rgba(0,0,0,0.04);
	box-sizing: border-box;
}
.card-title {
	font-size: 30rpx;
	font-weight: 700;
	color: #1c2333;
	display: block;
	margin-bottom: 16rpx;
}
.card-body {
	font-size: 26rpx;
	color: #666;
	line-height: 1.8;
	display: block;
}

.qr-card {
	display: flex;
	flex-direction: column;
	align-items: center;
	padding: 36rpx 28rpx;
}
.qr-card .card-title {
	text-align: center;
	margin-bottom: 24rpx;
}
.qr-wrap {
	width: 360rpx;
	height: 360rpx;
	border-radius: 28rpx;
	overflow: hidden;
	background: #fff;
	border: 2rpx solid #f0f0f0;
	margin-bottom: 20rpx;
	display: flex;
	align-items: center;
	justify-content: center;
}
.qr-img {
	width: 320rpx;
	height: 320rpx;
}
.qr-desc {
	font-size: 24rpx;
	color: #999;
	text-align: center;
}

.empty-qr {
	display: flex;
	flex-direction: column;
	align-items: center;
	padding: 40rpx 0;
}
.empty-qr-icon {
	font-size: 60rpx;
	margin-bottom: 12rpx;
	opacity: 0.4;
}
.empty-qr-text {
	font-size: 24rpx;
	color: #ccc;
}

.copyright {
	text-align: center;
	padding: 32rpx 0 0;
	font-size: 22rpx;
	color: #ccc;
}
</style>
