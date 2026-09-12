<template>
	<view class="page">
		<!-- 搜索筛选 -->
		<view class="filter-bar">
			<view class="search-box">
				🔍
				<input
					class="filter-input"
					v-model="keyword"
					placeholder="搜索已下载资源..."
					@confirm="onSearch"
				/>
				<text class="clear-btn" :style="tPri" v-if="keyword" @tap="clearSearch">✕</text>
			</view>
		</view>

		<scroll-view
			scroll-y
			class="list-scroll"
			:refresher-enabled="true"
			:refresher-triggered="isRefreshing"
			@refresherrefresh="onRefresh"
		>
			<!-- 下载列表 -->
			<view class="download-list" v-if="downloads.length > 0">
				<view
					class="download-card"
					v-for="item in downloads"
					:key="item.id"
					@tap="goDetail(item)"
				>
					<view class="card-cover-wrap">
						<image class="card-cover" :src="fixUrl(item.cover_url)" mode="aspectFill"></image>
						<view class="file-type-badge">
							<text>{{ getFileTypeBadge(item.file_type) }}</text>
						</view>
					</view>
					<view class="card-info">
						<text class="card-title">{{ item.resource_title || item.title }}</text>
						<view class="card-meta">
							<text class="card-category" :style="tPri" v-if="item.category_name">{{ item.category_name }}</text>
							<text class="card-time">{{ item.created_at }}</text>
						</view>
						<view class="card-bottom">
							<view class="card-file-info">
								<text class="card-size" v-if="item.file_size">{{ formatSize(item.file_size) }}</text>
								<text class="card-file-type" :style="tPri" v-if="item.file_type">{{ item.file_type }}</text>
							</view>
							<view class="redownload-btn" :style="'background:' + tc.primary + ';'" @tap.stop="reDownload(item)">
								<text>重新下载</text>
							</view>
						</view>
					</view>
				</view>
			</view>

			<!-- 空状态 -->
			<view class="empty" v-if="!loading && downloads.length === 0">
				<text class="empty-icon">📥</text>
				<text class="empty-title">暂无下载记录</text>
				<text class="empty-desc">去发现优质资源吧</text>
				<view class="empty-btn" @tap="goHome">
					<text>去首页看看</text>
				</view>
			</view>

			<!-- 分页栏 -->
			<view class="pager-bar" v-if="downloads.length > 0">
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
			keyword: '',
			downloads: [],
			page: 1,
			pageSize: 10,
			total: 0,
			totalPages: 0,
			loading: false,
			noMore: false,
			isRefreshing: false
		};
	},
	onLoad() {
		this.loadDownloads();
	},
	methods: {
		fixUrl(url) {
			if (!url) return '';
			return url.startsWith('http') ? url : BASE_URL + url;
		},
		async loadDownloads() {
			if (this.loading) return;
			this.loading = true;
			try {
				const res = await http.get('/api/user/downloads', {
					keyword: this.keyword || undefined,
					page: this.page,
					page_size: this.pageSize
				});
				if (res.code === 0) {
					const list = res.data.list || res.data || [];
					this.total = res.data.total || list.length;
					this.totalPages = res.data.total_pages || Math.ceil(this.total / this.pageSize) || 1;
					this.downloads = list;
					this.noMore = this.page >= this.totalPages;
				}
			} catch (e) {
				console.error('加载下载记录失败', e);
			} finally {
				this.loading = false;
			}
		},
		onSearch() {
			this.resetAndLoad();
		},
		clearSearch() {
			this.keyword = '';
			this.resetAndLoad();
		},
		async onRefresh() {
			this.isRefreshing = true;
			this.resetAndLoad();
			this.isRefreshing = false;
		},
		resetAndLoad() {
			this.page = 1;
			this.noMore = false;
			this.downloads = [];
			this.loadDownloads();
		},
		goPage(p) {
			if (p < 1 || p > this.totalPages || this.loading) return;
			this.page = p;
			this.loadDownloads();
			uni.pageScrollTo && uni.pageScrollTo({ scrollTop: 0, duration: 200 });
		},
		getFileTypeBadge(fileType) {
			if (!fileType) return 'FILE';
			const type = fileType.toLowerCase();
			if (type.includes('pdf')) return 'PDF';
			if (type.includes('doc') || type.includes('word')) return 'DOC';
			if (type.includes('xls') || type.includes('excel')) return 'XLS';
			if (type.includes('ppt')) return 'PPT';
			if (type.includes('zip') || type.includes('rar') || type.includes('7z')) return 'ZIP';
			if (type.includes('psd')) return 'PSD';
			if (type.includes('ai')) return 'AI';
			if (type.includes('mp4') || type.includes('mov')) return 'MP4';
			if (type.includes('mp3') || type.includes('wav')) return 'MP3';
			if (type.includes('jpg') || type.includes('jpeg')) return 'JPG';
			if (type.includes('png')) return 'PNG';
			if (type.includes('svg')) return 'SVG';
			if (type.includes('js')) return 'JS';
			if (type.includes('py')) return 'PY';
			return type.substring(0, 4).toUpperCase();
		},
		async reDownload(item) {
			uni.showLoading({ title: '获取下载链接...' });
			try {
				const res = await http.post('/api/download/download', {
					resource_id: item.resource_id
				});
				if (res.code === 0 && res.data) {
					const fileUrl = (res.data.resource && res.data.resource.file_url) || res.data.file_url;
					if (!fileUrl) {
						uni.hideLoading();
						uni.showToast({ title: '获取链接失败', icon: 'none' });
						return;
					}
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
					uni.showToast({ title: res.msg || '获取链接失败', icon: 'none' });
				}
			} catch (e) {
				uni.hideLoading();
				uni.showToast({ title: '下载失败', icon: 'none' });
			}
		},
		goDetail(item) {
			uni.navigateTo({ url: `/pages/resource/detail?id=${item.resource_id}` });
		},
		goHome() {
			uni.switchTab({ url: '/pages/index/index' });
		},
		formatSize(bytes) {
			if (!bytes) return '';
			if (bytes < 1024) return bytes + 'B';
			if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + 'KB';
			if (bytes < 1024 * 1024 * 1024) return (bytes / (1024 * 1024)).toFixed(1) + 'MB';
			return (bytes / (1024 * 1024 * 1024)).toFixed(1) + 'GB';
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
	flex-direction: column;
}

.filter-bar {
	padding: 16rpx 24rpx;
	background: rgba(255, 255, 255, 0.75);
	backdrop-filter: blur(24px);
	-webkit-backdrop-filter: blur(24px);
	flex-shrink: 0;
	border-bottom: 1rpx solid rgba(240, 240, 240, 0.5);
}
.search-box {
	display: flex;
	align-items: center;
	height: 72rpx;
	padding: 0 24rpx;
	background: rgba(245, 247, 250, 0.8);
	border-radius: 36rpx;
	border: 2rpx solid rgba(46,213,115, 0.08);
	transition: all 0.3s ease;
}
.search-box:focus-within {
	border-color: rgba(46,213,115, 0.2);
	box-shadow: 0 0 0 4rpx rgba(46,213,115, 0.06);
}
.filter-input {
	flex: 1;
	margin-left: 12rpx;
	font-size: 28rpx;
	color: #333;
}
.clear-btn {
	font-size: 28rpx;
	color: #bbb;
	padding: 10rpx;
	transition: all 0.2s ease;
}
.clear-btn:active {
	transform: scale(0.9);
}

.list-scroll {
	flex: 1;
}

.download-list {
	padding: 20rpx 24rpx;
}
.download-card {
	display: flex;
	background: #fff;
	border-radius: 24rpx;
	margin-bottom: 20rpx;
	overflow: hidden;
	box-shadow:
		0 2rpx 8rpx rgba(46,213,115, 0.04),
		0 8rpx 24rpx rgba(0, 0, 0, 0.06);
	transition: all 0.3s ease;
	position: relative;
}
/* 卡片顶部渐变装饰线 */
.download-card::before {
	content: '';
	position: absolute;
	top: 0;
	left: 10%;
	right: 10%;
	height: 2rpx;
	background: linear-gradient(90deg, transparent 0%, #26c67a 30%, #2ed573 50%, #26c67a 70%, transparent 100%);
	border-radius: 1rpx;
	filter: blur(1rpx);
	box-shadow: 0 0 6rpx rgba(46,213,115, 0.4), 0 0 6rpx rgba(46,213,115, 0.15);
	opacity: 0.8;
	z-index: 1;
}
.download-card:active {
	transform: scale(0.97);
	box-shadow:
		0 1rpx 4rpx rgba(46,213,115, 0.04),
		0 4rpx 12rpx rgba(0, 0, 0, 0.04);
}
.card-cover-wrap {
	position: relative;
	flex-shrink: 0;
	overflow: hidden;
}
/* 封面图片 hover 缩放 */
.card-cover {
	width: 200rpx;
	height: 170rpx;
	border-radius: 24rpx 0 0 24rpx;
	border-right: 2rpx solid rgba(46,213,115, 0.06);
	transition: transform 0.4s ease;
}
.download-card:active .card-cover {
	transform: scale(1.02);
}
/* 文件类型徽章 shimmer 光效 */
.file-type-badge {
	position: absolute;
	top: 12rpx;
	left: 12rpx;
	background: rgba(46,213,115,0.10);
	color: #27ae60;
	font-size: 18rpx;
	font-weight: 700;
	padding: 4rpx 12rpx;
	border-radius: 10rpx;
	backdrop-filter: blur(8px);
	box-shadow: 0 2rpx 8rpx rgba(46,213,115,0.08);
	border: 2rpx solid rgba(46,213,115,0.18);
	overflow: hidden;
}
.file-type-badge::after {
	content: '';
	position: absolute;
	top: 0;
	left: -100%;
	width: 25%;
	height: 100%;
	background: linear-gradient(90deg, transparent 0%, rgba(255, 255, 255, 0.3) 50%, transparent 100%);
	animation: badgeShimmer 5s ease-in-out infinite;
}
@keyframes badgeShimmer {
	0% { left: -100%; }
	50% { left: 100%; }
	100% { left: 100%; }
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
.card-meta {
	display: flex;
	align-items: center;
	gap: 12rpx;
	margin-top: 10rpx;
}
.card-category {
	font-size: 20rpx;
	background: linear-gradient(135deg, rgba(46,213,115, 0.1), rgba(139, 127, 255, 0.08));
	padding: 4rpx 16rpx;
	border-radius: 16rpx;
	font-weight: 500;
	background-size: 200% 100%;
	background-image: linear-gradient(135deg, rgba(46,213,115, 0.1) 0%, rgba(46,213,115, 0.1) 50%, rgba(139, 127, 255, 0.18) 100%);
}
.card-category:active {
	animation: badgeGradientShift 0.6s ease;
}
@keyframes badgeGradientShift {
	0% { background-position: 0% 0; }
	100% { background-position: 100% 0; }
}
.card-time {
	font-size: 22rpx;
	color: #bbb;
}
.card-bottom {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-top: auto;
}
.card-file-info {
	display: flex;
	align-items: center;
	gap: 12rpx;
}
.card-size {
	font-size: 22rpx;
	color: #aaa;
}
.card-file-type {
	font-size: 20rpx;
	background: rgba(46,213,115, 0.08);
	padding: 2rpx 10rpx;
	border-radius: 8rpx;
	font-weight: 600;
}
/* 重新下载按钮 shimmer 扫光效果 */
.redownload-btn {
	padding: 10rpx 28rpx;
	background: #2ed573;
	border-radius: 24rpx;
	font-size: 22rpx;
	color: #fff;
	box-shadow: 0 4rpx 12rpx rgba(46,213,115, 0.25);
	transition: all 0.2s ease;
	position: relative;
	overflow: hidden;
}
.redownload-btn::after {
	content: '';
	position: absolute;
	top: 0;
	left: -100%;
	width: 25%;
	height: 100%;
	background: linear-gradient(90deg, transparent 0%, rgba(255, 255, 255, 0.25) 50%, transparent 100%);
	animation: btnShimmer 5s ease-in-out infinite;
}
@keyframes btnShimmer {
	0% { left: -100%; }
	50% { left: 100%; }
	100% { left: 100%; }
}
.redownload-btn:active {
	transform: scale(0.97);
	box-shadow: 0 2rpx 6rpx rgba(0, 0, 0, 0.15);
}

/* ===== 分页栏 ===== */
.pager-bar {
	display: flex;
	align-items: center;
	justify-content: space-between;
	padding: 20rpx 24rpx;
	margin: 0 24rpx 20rpx;
	background: #fff;
	border-radius: 16rpx;
	box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.06);
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
	top: 60rpx;
	width: 280rpx;
	height: 280rpx;
	border-radius: 50%;
	background: linear-gradient(135deg, rgba(46,213,115, 0.06), rgba(139, 127, 255, 0.1));
}
.empty-icon {
	font-size: 100rpx;
	margin-bottom: 24rpx;
	position: relative;
	z-index: 1;
	animation: breathe 3s ease-in-out infinite;
}
@keyframes breathe {
	0%, 100% { transform: scale(1); }
	50% { transform: scale(1.04); }
}
.empty-title {
	font-size: 32rpx;
	color: #333;
	font-weight: 600;
	margin-bottom: 12rpx;
	position: relative;
	z-index: 1;
}
.empty-desc {
	font-size: 26rpx;
	color: #bbb;
	margin-bottom: 30rpx;
	position: relative;
	z-index: 1;
}
.empty-btn {
	padding: 18rpx 56rpx;
	background: linear-gradient(135deg, #2ed573, #1abc9c);
	border-radius: 36rpx;
	font-size: 28rpx;
	color: #fff;
	box-shadow: 0 6rpx 20rpx rgba(46,213,115, 0.3);
	position: relative;
	z-index: 1;
	transition: all 0.2s ease;
}
.empty-btn:active {
	transform: scale(0.97);
	box-shadow: 0 3rpx 10rpx rgba(46,213,115, 0.25);
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
