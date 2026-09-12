<template>
	<view class="page">
		<!-- 加载中 -->
		<view class="loading-wrap" v-if="loading">
			<view class="loading-spinner"></view>
			<text class="loading-text">加载中...</text>
		</view>

		<view class="content" v-else-if="resource">
			<!-- 顶部预览区 -->
			<swiper
				class="preview-swiper"
				:indicator-dots="images.length > 1"
				indicator-color="rgba(255,255,255,0.4)"
				indicator-active-color="#fff"
				:circular="true"
			>
				<swiper-item v-for="(img, idx) in images" :key="idx">
					<image class="preview-img" :src="img" mode="aspectFit" @tap="previewImage(idx)"></image>
				</swiper-item>
			</swiper>

			<!-- 资源信息 -->
			<view class="info-card">
				<text class="title">{{ resource.title }}</text>
				<view class="meta-row">
					<text class="category-badge" :style="tPri" v-if="resource.category_name">{{ resource.category_name }}</text>
					<text class="file-type" v-if="resource.file_type" :style="'color:' + tc.primary + ';'">{{ resource.file_type }}</text>
				</view>
				<view class="desc" v-if="resource.description">
					<text>{{ resource.description }}</text>
				</view>
				<view class="stats-row">
					<text class="stat">👁 {{ resource.view_count || 0 }}</text>
					<text class="stat">↓ {{ resource.download_count || 0 }}</text>
					<text class="stat">❤ {{ resource.like_count || 0 }}</text>
				</view>
				<view class="price-row" v-if="resource.price > 0">
					<text class="price">¥{{ resource.price }}</text>
					<text class="original-price" v-if="resource.original_price > resource.price">¥{{ resource.original_price }}</text>
				</view>
				<view class="price-row" v-else>
					<text class="free-tag" :style="'color:' + tc.primary + ';border-color:' + tc.primary + ';'">免费</text>
				</view>
			</view>

			<!-- 文件信息 -->
			<view class="file-info-card" v-if="resource.file_size || resource.file_format">
				<text class="card-title">文件信息</text>
				<view class="info-item" v-if="resource.file_size">
					<text class="info-label">文件大小</text>
					<text class="info-value">{{ formatSize(resource.file_size) }}</text>
				</view>
				<view class="info-item" v-if="resource.file_format">
					<text class="info-label">文件格式</text>
					<text class="info-value">{{ resource.file_format }}</text>
				</view>
				<view class="info-item" v-if="resource.file_count">
					<text class="info-label">文件数量</text>
					<text class="info-value">{{ resource.file_count }} 个</text>
				</view>
			</view>

			<!-- 底部操作栏 -->
			<view class="bottom-bar">
				<view class="btn-fav" @tap="toggleFavorite">
					<text>{{ isFav ? '❤️ 已收藏' : '🤍 收藏' }}</text>
				</view>
				<view class="btn-download" @tap="goDownload" :style="resource.price <= 0 ? 'color:' + tc.primary + ';border-color:' + tc.primary + ';' : ''">
					<text>{{ resource.price > 0 ? '¥' + resource.price + ' 购买下载' : '免费下载' }}</text>
				</view>
			</view>
		</view>

		<!-- 空状态 -->
		<view class="empty-wrap" v-else>
			<text class="empty-icon">😕</text>
			<text class="empty-text">资源不存在或已下架</text>
		</view>
	</view>
</template>

<script>
import http from '@/utils/http.js';

export default {
	data() {
		return {
			resourceId: '',
			resource: null,
			images: [],
			loading: true,
			isFav: false
		};
	},
	onLoad(options) {
		if (options.id) {
			this.resourceId = options.id;
			this.loadResource();
		} else {
			this.loading = false;
		}
	},
	methods: {
		async loadResource() {
			this.loading = true;
			try {
				const res = await http.get('/api/resource/detail', { id: this.resourceId });
				if (res.code === 0 && res.data) {
					this.resource = res.data;
					// 收集预览图
					const imgs = [];
					if (res.data.cover_url) imgs.push(res.data.cover_url);
					if (res.data.preview_images && Array.isArray(res.data.preview_images)) {
						imgs.push(...res.data.preview_images);
					}
					this.images = imgs.length > 0 ? imgs : [];
				}
			} catch (e) {
				console.error('加载资源详情失败', e);
			} finally {
				this.loading = false;
			}
		},
		previewImage(idx) {
			uni.previewImage({
				current: idx,
				urls: this.images
			});
		},
		formatSize(bytes) {
			if (!bytes) return '未知';
			if (bytes < 1024) return bytes + ' B';
			if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
			return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
		},
		async toggleFavorite() {
			if (!uni.getStorageSync('token')) {
				uni.showToast({ title: '请先登录', icon: 'none' });
				return;
			}
			try {
				const res = await http.post('/api/favorite/toggle', { resource_id: this.resourceId });
				if (res.code === 0) {
					this.isFav = !this.isFav;
					uni.showToast({ title: this.isFav ? '已收藏' : '已取消', icon: 'none' });
				}
			} catch (e) {
				console.error('收藏操作失败', e);
			}
		},
		goDownload() {
			uni.navigateTo({ url: `/pages/resource/detail?id=${this.resourceId}` });
		}
	}
};
</script>

<style scoped>
.page {
	min-height: 100vh;
	background: linear-gradient(180deg, #f0f2ff 0%, #f5f6fa 200rpx);
}

/* ===== 加载 ===== */
.loading-wrap {
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	padding-top: 300rpx;
}
.loading-spinner {
	width: 64rpx;
	height: 64rpx;
	border: 4rpx solid rgba(46,213,115, 0.12);
	border-top-color: #2ed573;
	border-radius: 50%;
	animation: spin 0.8s linear infinite;
	box-shadow: 0 0 20rpx rgba(46,213,115, 0.15);
}
@keyframes spin {
	to { transform: rotate(360deg); }
}
.loading-text {
	margin-top: 20rpx;
	font-size: 26rpx;
	color: #aaa;
}

/* ===== 预览图 ===== */
.preview-swiper {
	width: 100%;
	height: 560rpx;
	background: #0a0a0a;
	position: relative;
}
/* 渐变边框装饰 */
.preview-swiper::after {
	content: '';
	position: absolute;
	bottom: 0;
	left: 0;
	right: 0;
	height: 1rpx;
	background: rgba(46,213,115,0.08);
	z-index: 5;
}
.preview-img {
	width: 100%;
	height: 100%;
}

/* ===== 信息卡 ===== */
.info-card {
	margin: 24rpx;
	padding: 30rpx;
	background: #fff;
	border-radius: 24rpx;
	box-shadow: 0 12rpx 40rpx rgba(46,213,115, 0.08), 0 2rpx 8rpx rgba(0, 0, 0, 0.03);
	position: relative;
	overflow: hidden;
}
/* 卡片顶部彩色装饰线 */
.info-card::before {
	content: '';
	position: absolute;
	top: 0;
	left: 0;
	right: 0;
	height: 6rpx;
	background: linear-gradient(90deg, #2ed573, #27ae60, #1abc9c);
	border-radius: 6rpx 6rpx 0 0;
	box-shadow: 0 0 16rpx rgba(46,213,115, 0.3);
}
.title {
	font-size: 34rpx;
	font-weight: 700;
	color: #1a1a2e;
	line-height: 1.5;
	display: block;
}
.meta-row {
	display: flex;
	gap: 16rpx;
	margin-top: 16rpx;
	flex-wrap: wrap;
}
.category-badge {
	font-size: 22rpx;

	background: rgba(46,213,115, 0.08);
	padding: 6rpx 20rpx;
	border-radius: 24rpx;
	font-weight: 500;
	box-shadow: 0 2rpx 8rpx rgba(46,213,115, 0.08);
}
.file-type {
	font-size: 22rpx;
	color: #00b894;
	background: rgba(0, 184, 148, 0.08);
	padding: 6rpx 20rpx;
	border-radius: 24rpx;
	font-weight: 500;
	box-shadow: 0 2rpx 8rpx rgba(0, 184, 148, 0.08);
}
.desc {
	margin-top: 20rpx;
	font-size: 26rpx;
	color: #777;
	line-height: 1.7;
}
.stats-row {
	display: flex;
	gap: 28rpx;
	margin-top: 20rpx;
	padding-top: 16rpx;
	border-top: 1rpx solid rgba(46,213,115, 0.04);
}
.stat {
	font-size: 24rpx;
	color: #b0b0b0;
}
.price-row {
	margin-top: 20rpx;
	display: flex;
	align-items: baseline;
	gap: 16rpx;
}
.price {
	font-size: 42rpx;
	color: #ff4757;
	font-weight: 800;
}
.original-price {
	font-size: 26rpx;
	color: #ccc;
	text-decoration: line-through;
}
.free-tag {
	font-size: 34rpx;

	font-weight: 700;
}

/* ===== 文件信息 ===== */
.file-info-card {
	margin: 0 24rpx 24rpx;
	padding: 30rpx;
	background: #fff;
	border-radius: 24rpx;
	box-shadow: 0 8rpx 32rpx rgba(46,213,115, 0.06), 0 2rpx 8rpx rgba(0, 0, 0, 0.03);
	position: relative;
}
/* 顶部渐变装饰线 */
.file-info-card::before {
	content: '';
	position: absolute;
	top: 0;
	left: 0;
	right: 0;
	height: 4rpx;
	background: linear-gradient(90deg, #2ed573, #27ae60, #1abc9c);
	border-radius: 4rpx 4rpx 0 0;
	z-index: 2;
	box-shadow: 0 0 12rpx rgba(46,213,115, 0.25);
}
.card-title {
	font-size: 30rpx;
	font-weight: 700;
	color: #1a1a2e;
	margin-bottom: 20rpx;
	display: block;
	position: relative;
	padding-left: 20rpx;
}
.card-title::before {
	content: '';
	position: absolute;
	left: 0;
	top: 4rpx;
	bottom: 4rpx;
	width: 6rpx;
	border-radius: 6rpx;
	background: linear-gradient(180deg, #2ed573, #27ae60);
}
.info-item {
	display: flex;
	justify-content: space-between;
	padding: 14rpx 0;
	border-bottom: 1rpx solid rgba(46,213,115, 0.04);
}
.info-item:last-child {
	border-bottom: none;
}
.info-label {
	font-size: 26rpx;
	color: #aaa;
}
.info-value {
	font-size: 26rpx;
	color: #333;
	font-weight: 500;
}

/* ===== 底部栏 - 玻璃态 ===== */
.bottom-bar {
	position: fixed;
	bottom: 0;
	left: 0;
	right: 0;
	display: flex;
	padding: 16rpx 24rpx;
	padding-bottom: calc(16rpx + env(safe-area-inset-bottom));
	background: rgba(255, 255, 255, 0.7);
	backdrop-filter: blur(40rpx) saturate(150%);
	-webkit-backdrop-filter: blur(40rpx) saturate(150%);
	box-shadow: 0 -4rpx 30rpx rgba(46,213,115, 0.06), 0 -1rpx 0 rgba(255, 255, 255, 0.5) inset;
	z-index: 100;
}
.btn-fav {
	flex: 1;
	display: flex;
	align-items: center;
	justify-content: center;
	height: 84rpx;
	border-radius: 42rpx;
	background: rgba(46,213,115, 0.06);
	margin-right: 16rpx;
	font-size: 26rpx;
	color: #666;
	border: 1rpx solid rgba(46,213,115, 0.08);
	transition: all 0.2s ease;
}
.btn-fav:active {
	background: rgba(46,213,115, 0.12);
	transform: translateY(2rpx);
}
.btn-download {
	flex: 2;
	display: flex;
	align-items: center;
	justify-content: center;
	height: 84rpx;
	border-radius: 42rpx;
	background: linear-gradient(135deg, #2ed573 0%, #27ae60 100%);
	font-size: 28rpx;
	color: #fff;
	font-weight: 700;
	box-shadow: 0 8rpx 28rpx rgba(46,213,115, 0.3);
	transition: all 0.2s ease;
	position: relative;
	overflow: hidden;
}
/* 下载按钮shimmer */
.btn-download::after {
	content: '';
	position: absolute;
	top: 0;
	left: -100%;
	width: 60%;
	height: 100%;
	background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
	animation: btnShimmer 5s ease-in-out infinite;
}
@keyframes btnShimmer {
	0% { left: -100%; }
	50% { left: 100%; }
	100% { left: 100%; }
}
.btn-download:active {
	transform: scale(0.97) translateY(2rpx);
	box-shadow: 0 4rpx 14rpx rgba(46,213,115, 0.2);
}

/* ===== 空状态 ===== */
.empty-wrap {
	display: flex;
	flex-direction: column;
	align-items: center;
	padding-top: 300rpx;
	position: relative;
}
.empty-wrap::before {
	content: '';
	position: absolute;
	width: 240rpx;
	height: 240rpx;
	border-radius: 50%;
	background: linear-gradient(135deg, rgba(46,213,115, 0.06), rgba(126, 217, 87, 0.03));
	top: 240rpx;
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
	50% { transform: scale(1.08); }
}
</style>
