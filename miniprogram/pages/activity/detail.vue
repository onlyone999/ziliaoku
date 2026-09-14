<template>
	<view class="page" :class="'theme-' + activeTheme" :style="pageBgStyle + '--tc-primary:' + tc.primary + ';'">
		<scroll-view scroll-y class="detail-scroll">
			<!-- 加载骨架屏 -->
			<view v-if="loading && !activity" class="skeleton">
				<view class="skeleton-cover-lg"></view>
				<view class="skeleton-body">
					<view class="skeleton-line w80"></view>
					<view class="skeleton-line w60"></view>
					<view class="skeleton-line w100"></view>
					<view class="skeleton-line w100"></view>
				</view>
			</view>

			<view v-else-if="activity">
				<!-- 公告详情 -->
				<view v-if="loadType === 'announcement'" class="announcement-view">
					<view class="ann-header">
						<text class="ann-tag" :style="'color:' + tc.primary + ';background:' + tc.primary + '12;'">{{ typeMap[activity.type] || '通知' }}</text>
						<text class="ann-time">{{ formatDateTime(activity.created_at) }}</text>
					</view>
					<text class="ann-title">{{ activity.title }}</text>
					<view class="ann-divider"></view>
					<rich-text :nodes="formatDesc(activity.content || '')" class="ann-content"></rich-text>

					<!-- 公告图片 -->
					<view class="ann-images" v-if="activity.images && activity.images.length > 0">
						<image
							v-for="(img, idx) in activity.images"
							:key="idx"
							:src="fixUrl(img)"
							mode="widthFix"
							class="ann-image"
							@tap="previewImage(idx)"
						></image>
					</view>

					<!-- 公告附件 -->
					<view class="ann-attachments" v-if="activity.attachments && activity.attachments.length > 0">
						<view class="ann-attach-title" :style="'color:' + tc.primary + ';'">
							<text>📎 附件下载</text>
						</view>
						<view
							class="ann-attach-item"
							v-for="(file, idx) in activity.attachments"
							:key="idx"
							@tap="downloadFile(file)"
						>
							<text class="ann-attach-icon">📄</text>
							<text class="ann-attach-name">{{ file.name || getFileName(file.url || file) }}</text>
							<text class="ann-attach-dl" :style="'color:' + tc.primary + ';'">下载</text>
						</view>
					</view>
				</view>

				<!-- 活动详情 -->
				<template v-else>
					<!-- 封面大图 -->
					<view class="cover-section">
						<image
							v-if="activity.cover_url"
							:src="fixUrl(activity.cover_url)"
							mode="aspectFill"
							class="cover-image"
						></image>
						<view v-else class="cover-placeholder">
							<text class="cover-placeholder-icon">🎯</text>
						</view>
						<view class="cover-overlay">
							<view class="type-tag-lg" :style="'background:' + tc.primary + ';box-shadow:0 4rpx 20rpx ' + tc.primary + '70;'">
								<text>{{ typeLabels[activity.activity_type] || '线上' }}</text>
							</view>
						</view>
					</view>

					<!-- 活动信息 -->
					<view class="info-card">
						<text class="activity-title">{{ activity.title }}</text>

						<view class="info-row">
							<view class="info-icon-wrap">
								<text class="row-icon">&#128197;</text>
							</view>
							<view class="info-content">
								<text class="info-label">活动时间</text>
								<text class="info-value">{{ formatDateTime(activity.start_time) }} - {{ formatDateTime(activity.end_time) }}</text>
							</view>
						</view>

						<view class="info-row" v-if="activity.location">
							<view class="info-icon-wrap">
								<text class="row-icon">&#128205;</text>
							</view>
							<view class="info-content">
								<text class="info-label">活动地点</text>
								<text class="info-value">{{ activity.location }}</text>
							</view>
						</view>

						<view class="info-row" v-if="activity.signup_deadline && activity.signup_deadline !== '0000-00-00 00:00:00'">
							<view class="info-icon-wrap">
								<text class="row-icon">&#9200;</text>
							</view>
							<view class="info-content">
								<text class="info-label">报名截止</text>
								<text class="info-value">{{ formatDateTime(activity.signup_deadline) }}</text>
							</view>
						</view>

						<!-- 报名进度 -->
						<view class="progress-section">
							<view class="progress-header">
								<text class="progress-title">报名进度</text>
								<text class="progress-count">
									<text class="count-current" :style="tPri">{{ activity.current_count }}</text>
									<text class="count-sep"> / </text>
									<text class="count-total">{{ activity.max_participants > 0 ? activity.max_participants : '不限' }}</text>
									<text class="count-unit">人</text>
								</text>
							</view>
							<view class="progress-bar-wrap" v-if="activity.max_participants > 0">
								<view class="progress-bar-bg">
									<view
										class="progress-bar-fill"
										:style="{ width: progressPercent + '%' }"
									></view>
								</view>
								<text class="progress-percent" :style="tPri">{{ progressPercent }}%</text>
							</view>
						</view>
					</view>

					<!-- 活动描述 -->
					<view class="desc-card" v-if="activity.description">
						<view class="section-title-wrap">
							<view class="section-bar" :style="'background:' + tc.primary + ';'"></view>
							<text class="section-title">活动详情</text>
						</view>
						<rich-text :nodes="formatDesc(activity.description)" class="desc-content"></rich-text>
					</view>

					<!-- 已报名信息 -->
					<view class="signup-info-card" v-if="activity.is_signed_up && activity.signup_status">
						<view class="signup-info-header">
							<view class="section-title-wrap">
								<view class="section-bar" :style="'background:' + tc.primary + ';'"></view>
								<text class="section-title">我的报名</text>
							</view>
							<view class="signup-status-badge" :class="activity.signup_status.status" :style="activity.signup_status && activity.signup_status.status === 'confirmed' ? tPri : ''">
								<text>{{ signupStatusLabels[activity.signup_status.status] || '已报名' }}</text>
							</view>
						</view>
						<view class="signup-detail">
							<text class="signup-detail-item">姓名：{{ activity.signup_status.name }}</text>
							<text class="signup-detail-item" v-if="activity.signup_status.phone">手机：{{ activity.signup_status.phone }}</text>
							<text class="signup-detail-item">报名时间：{{ activity.signup_status.created_at }}</text>
						</view>
					</view>
				</template>

				<!-- 底部占位 -->
				<view style="height: 180rpx;"></view>
			</view>
		</scroll-view>

		<!-- 底部固定按钮 -->
		<view class="bottom-bar" v-if="activity && loadType !== 'announcement'">
			<!-- 已报名 -->
			<view v-if="activity.is_signed_up" class="bottom-btn-group">
				<view class="btn-cancel" @tap="cancelSignup">
					<text>取消报名</text>
				</view>
				<view class="btn-signed" :style="'background:' + tc.primary + ';box-shadow:0 6rpx 24rpx ' + tc.primary + '4c;'">
					<text>&#10003; 已报名</text>
				</view>
			</view>
			<!-- 名额已满 -->
			<view v-else-if="isFull" class="btn-disabled">
				<text>名额已满</text>
			</view>
			<!-- 报名已截止 -->
			<view v-else-if="isDeadlinePassed" class="btn-disabled">
				<text>报名已截止</text>
			</view>
			<!-- 立即报名 -->
			<view v-else class="btn-signup" :style="'background:' + tc.primary + ';'" @tap="showSignupForm">
				<text>立即报名</text>
			</view>
		</view>

		<!-- 报名弹窗 -->
		<view class="modal-mask" v-if="showModal" @tap="showModal = false">
			<view class="modal-content" @tap.stop>
				<view class="modal-header">
					<text class="modal-title">活动报名</text>
					<view class="modal-close" :style="tPri" @tap="showModal = false">
						<text>&#10005;</text>
					</view>
				</view>
				<view class="modal-body">
					<view class="form-group">
						<text class="form-label">姓名 <text class="required">*</text></text>
						<input
							class="form-input"
							v-model="signupForm.name"
							placeholder="请输入您的姓名"
							maxlength="100"
						/>
					</view>
					<view class="form-group">
						<text class="form-label">手机号</text>
						<input
							class="form-input"
							v-model="signupForm.phone"
							placeholder="请输入手机号（选填）"
							type="number"
							maxlength="11"
						/>
					</view>
				</view>
				<view class="modal-footer">
					<view class="modal-btn-cancel" @tap="showModal = false">
						<text>取消</text>
					</view>
					<view class="modal-btn-confirm" :class="{ disabled: !signupForm.name.trim() || submitting }" @tap="submitSignup">
						<text>{{ submitting ? '提交中...' : '确认报名' }}</text>
					</view>
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
			activityId: 0,
			loadType: 'activity',
			activity: null,
			loading: true,
			showModal: false,
			submitting: false,
			signupForm: {
				name: '',
				phone: ''
			},
			typeLabels: {
				online: '线上活动',
				offline: '线下活动',
				both: '线上+线下'
			},
			signupStatusLabels: {
				pending: '待确认',
				confirmed: '已确认',
				cancelled: '已取消'
			},
			typeMap: { info: '通知', warning: '警告', success: '喜讯' }
		};
	},
	computed: {
		progressPercent() {
			if (!this.activity || !this.activity.max_participants || this.activity.max_participants <= 0) return 0;
			return Math.min(100, Math.round((this.activity.current_count / this.activity.max_participants) * 100));
		},
		isFull() {
			if (!this.activity) return false;
			return this.activity.max_participants > 0 && this.activity.current_count >= this.activity.max_participants;
		},
		isDeadlinePassed() {
			if (!this.activity || !this.activity.signup_deadline || this.activity.signup_deadline === '0000-00-00 00:00:00') return false;
			return new Date(this.activity.signup_deadline.replace(/-/g, '/')) < new Date();
		}
	},
	onLoad(options) {
		this.activityId = parseInt(options.id) || 0;
		this.loadType = options.type || 'activity';
		if (this.loadType === 'announcement') {
			uni.setNavigationBarTitle({ title: '公告详情' });
		}
		if (this.activityId > 0) {
			this.loadDetail();
		}
	},
	methods: {
		async loadDetail() {
			this.loading = true;
			try {
				const apiUrl = this.loadType === 'announcement'
					? '/api/announcement/detail'
					: '/api/activity/detail';
				const res = await http.get(apiUrl, { id: this.activityId });
				if (res.code === 0) {
					this.activity = res.data;
				}
			} catch (e) {
				console.error('加载详情失败', e);
				uni.showToast({ title: '加载失败', icon: 'none' });
			} finally {
				this.loading = false;
			}
		},

		showSignupForm() {
			const token = uni.getStorageSync('token');
			if (!token) {
				uni.showModal({
					title: '提示',
					content: '请先登录后再报名',
					confirmText: '去登录',
					success: (res) => {
						if (res.confirm) {
							uni.switchTab({ url: '/pages/user/index' });
						}
					}
				});
				return;
			}
			// 预填用户信息
			try {
				const userInfo = uni.getStorageSync('userInfo');
				if (userInfo) {
					const info = typeof userInfo === 'string' ? JSON.parse(userInfo) : userInfo;
					if (info.nickname && !this.signupForm.name) this.signupForm.name = info.nickname;
					if (info.phone && !this.signupForm.phone) this.signupForm.phone = info.phone;
				}
			} catch (e) {}
			this.showModal = true;
		},

		async submitSignup() {
			if (!this.signupForm.name.trim()) {
				uni.showToast({ title: '请输入姓名', icon: 'none' });
				return;
			}
			if (this.signupForm.phone && !/^1[3-9]\d{9}$/.test(this.signupForm.phone)) {
				uni.showToast({ title: '手机号格式不正确', icon: 'none' });
				return;
			}
			if (this.submitting) return;

			this.submitting = true;
			try {
				const res = await http.post('/api/activity/signup', {
					activity_id: this.activityId,
					name: this.signupForm.name.trim(),
					phone: this.signupForm.phone.trim()
				});
				if (res.code === 0) {
					uni.showToast({ title: '报名成功', icon: 'success' });
					this.showModal = false;
					this.signupForm.name = '';
					this.signupForm.phone = '';
					this.loadDetail();
				}
			} catch (e) {
				// 错误已在 http.js 拦截器中处理
			} finally {
				this.submitting = false;
			}
		},

		cancelSignup() {
			uni.showModal({
				title: '确认取消',
				content: '确定要取消报名此活动吗？',
				confirmColor: '#ff6b6b',
				success: async (res) => {
					if (res.confirm) {
						try {
							const r = await http.post('/api/activity/cancel', {
								activity_id: this.activityId
							});
							if (r.code === 0) {
								uni.showToast({ title: '已取消报名', icon: 'success' });
								this.loadDetail();
							}
						} catch (e) {
							// 错误已在拦截器中处理
						}
					}
				}
			});
		},

		formatDateTime(dt) {
			if (!dt) return '';
			const d = new Date(dt.replace(/-/g, '/'));
			const y = d.getFullYear();
			const m = d.getMonth() + 1;
			const day = d.getDate();
			const h = String(d.getHours()).padStart(2, '0');
			const min = String(d.getMinutes()).padStart(2, '0');
			return y + '年' + m + '月' + day + '日 ' + h + ':' + min;
		},

		formatDesc(text) {
			if (!text) return '';
			// 简单换行转 <br>
			return text
				.replace(/&/g, '&amp;')
				.replace(/</g, '&lt;')
				.replace(/>/g, '&gt;')
				.replace(/\n/g, '<br>');
		},
		fixUrl(url) {
			if (!url) return '';
			if (url.startsWith('http')) return url;
			return http.getBaseUrl() + url;
		},
		previewImage(idx) {
			var images = (this.activity.images || []).map(function(u) {
				return u.startsWith('http') ? u : http.getBaseUrl() + u;
			});
			uni.previewImage({ urls: images, current: images[idx] || images[0] });
		},
		downloadFile(file) {
			var url = typeof file === 'string' ? file : (file.url || '');
			if (!url) return;
			var fullUrl = url.startsWith('http') ? url : http.getBaseUrl() + url;
			uni.showLoading({ title: '下载中...' });
			uni.downloadFile({
				url: fullUrl,
				success: function(res) {
					uni.hideLoading();
					if (res.statusCode === 200) {
						uni.openDocument({
							filePath: res.tempFilePath,
							showMenu: true,
							fail: function() {
								uni.showToast({ title: '无法打开此文件', icon: 'none' });
							}
						});
					} else {
						uni.showToast({ title: '下载失败', icon: 'none' });
					}
				},
				fail: function() {
					uni.hideLoading();
					uni.showToast({ title: '下载失败', icon: 'none' });
				}
			});
		},
		getFileName(path) {
			if (!path) return '附件';
			return path.split('/').pop();
		}
	},
	onShareAppMessage() {
		return {
			title: this.activity ? this.activity.title : '精彩活动',
			path: '/pages/activity/detail?id=' + this.activityId
		};
	}
};
</script>

<style scoped>
.page {
	min-height: 100vh;
	background: transparent;
	position: relative;
}
.detail-scroll {
	height: 100vh;
}

/* ===== 骨架屏 ===== */
.skeleton-cover-lg {
	height: 400rpx;
	background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
	background-size: 200% 100%;
	animation: shimmer 1.5s infinite;
}
.skeleton-body { padding: 30rpx; }
.skeleton-line {
	height: 30rpx;
	background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
	background-size: 200% 100%;
	animation: shimmer 1.5s infinite;
	border-radius: 10rpx;
	margin-bottom: 20rpx;
}
.skeleton-line.w80 { width: 80%; }
.skeleton-line.w60 { width: 60%; }
.skeleton-line.w100 { width: 100%; }
@keyframes shimmer {
	0% { background-position: 200% 0; }
	100% { background-position: -200% 0; }
}

/* ===== 封面 ===== */
.cover-section {
	position: relative;
	height: 400rpx;
	overflow: hidden;
}
.cover-image {
	width: 100%;
	height: 100%;
	transition: transform 0.4s ease;
}
.cover-placeholder {
	width: 100%;
	height: 100%;
	background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
	display: flex;
	align-items: center;
	justify-content: center;
}
.cover-placeholder-icon {
	font-size: 80rpx;
	opacity: 0.6;
}
.cover-section:active .cover-image {
	transform: scale(1.02);
}
.cover-overlay {
	position: absolute;
	bottom: 0;
	left: 0;
	right: 0;
	padding: 60rpx 24rpx 24rpx;
	background: linear-gradient(transparent, rgba(0, 0, 0, 0.5));
}
/* 类型标签发光 */
.type-tag-lg {
	display: inline-block;
	padding: 10rpx 28rpx;
	border-radius: 28rpx;
	font-size: 24rpx;
	font-weight: 600;
	color: #fff;
	animation: tagGlow 2s ease-in-out infinite alternate;
}
@keyframes tagGlow {
	0% { filter: brightness(1); }
	100% { filter: brightness(1.2); }
}

/* ===== 信息卡片 ===== */
.info-card {
	margin: -20rpx 24rpx 24rpx;
	background: #fff;
	border-radius: 28rpx;
	padding: 32rpx;
	position: relative;
	z-index: 2;
	box-shadow: 0 12rpx 40rpx var(--tc-primary, #7cb342)1a, 0 4rpx 12rpx rgba(0, 0, 0, 0.04);
}
/* 顶部装饰圆点 */
.info-card::before {
	content: '';
	position: absolute;
	top: 16rpx;
	left: 32rpx;
	width: 10rpx;
	height: 10rpx;
	border-radius: 50%;
	background: var(--tc-primary, #7cb342);
	box-shadow: 0 0 12rpx var(--tc-primary, #7cb342), 24rpx 0 0 var(--tc-primary, #7cb342), 48rpx 0 0 var(--tc-primary, #7cb342);
	opacity: 0.6;
}
.activity-title {
	font-size: 36rpx;
	font-weight: 700;
	color: #1c2333;
	display: block;
	margin-bottom: 24rpx;
	margin-top: 16rpx;
	line-height: 1.5;
}

.info-row {
	display: flex;
	margin-bottom: 20rpx;
}
.info-icon-wrap {
	width: 52rpx;
	height: 52rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	margin-right: 16rpx;
	flex-shrink: 0;
	background: rgba(124,179,66, 0.06);
	border-radius: 14rpx;
}
.row-icon {
	font-size: 30rpx;
}
.info-content {
	flex: 1;
}
.info-label {
	font-size: 22rpx;
	color: #aaa;
	display: block;
	margin-bottom: 4rpx;
}
.info-value {
	font-size: 28rpx;
	color: #333;
	line-height: 1.5;
}

/* ===== 报名进度 ===== */
.progress-section {
	margin-top: 24rpx;
	padding-top: 24rpx;
	border-top: 1rpx solid rgba(124,179,66, 0.06);
}
.progress-header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-bottom: 16rpx;
}
.progress-title {
	font-size: 28rpx;
	font-weight: 600;
	color: #333;
}
.progress-count {
	font-size: 26rpx;
}
.count-current {
	font-size: 36rpx;
	font-weight: 700;
}
.count-sep {
	color: #ddd;
}
.count-total {
	font-size: 28rpx;
	color: #888;
}
.count-unit {
	font-size: 24rpx;
	color: #aaa;
	margin-left: 4rpx;
}
.progress-bar-wrap {
	display: flex;
	align-items: center;
	gap: 16rpx;
}
.progress-bar-bg {
	flex: 1;
	height: 18rpx;
	background: rgba(124,179,66, 0.08);
	border-radius: 18rpx;
	overflow: hidden;
}
.progress-bar-fill {
	height: 100%;
	background: linear-gradient(90deg, #7cb342, #558b2f);
	border-radius: 18rpx;
	transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1);
	box-shadow: 0 2rpx 10rpx rgba(124,179,66, 0.3);
	position: relative;
}
.progress-bar-fill::after {
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
.progress-percent {
	font-size: 24rpx;
	font-weight: 700;
	width: 70rpx;
	text-align: right;
}

/* ===== 描述卡片 ===== */
.desc-card {
	margin: 0 24rpx 24rpx;
	background: #fff;
	border-radius: 28rpx;
	padding: 32rpx;
	box-shadow:
		0 2rpx 8rpx rgba(0,0,0,0.03),
		0 8rpx 24rpx rgba(0,0,0,0.06),
		0 16rpx 40rpx rgba(0,0,0,0.04);
	border: 1rpx solid rgba(0,0,0,0.03);
	position: relative;
}
/* 顶部渐变装饰线 */
.desc-card::before {
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
.section-title {
	font-size: 30rpx;
	font-weight: 700;
	color: #1c2333;
	display: block;
	margin-bottom: 20rpx;
	position: relative;
	padding-left: 20rpx;
}
.section-title-wrap {
	position: relative;
	display: inline-block;
}
.section-bar {
	width: 6rpx;
	height: 24rpx;
	border-radius: 6rpx;
	position: absolute;
	left: 0;
	top: 50%;
	transform: translateY(-50%);
}
.desc-content {
	font-size: 28rpx;
	color: #555;
	line-height: 1.8;
}

/* ===== 报名信息卡片 ===== */
.signup-info-card {
	margin: 0 24rpx 24rpx;
	background: #fff;
	border-radius: 28rpx;
	padding: 32rpx;
	box-shadow:
		0 2rpx 8rpx rgba(0,0,0,0.03),
		0 8rpx 24rpx rgba(0,0,0,0.06),
		0 16rpx 40rpx rgba(0,0,0,0.04);
	border: 1rpx solid rgba(0,0,0,0.03);
	position: relative;
}
/* 顶部渐变装饰线 */
.signup-info-card::before {
	content: '';
	position: absolute;
	top: 0;
	left: 0;
	right: 0;
	height: 2rpx;
	background: linear-gradient(90deg, #7cb342, #9ccc65, #7cb342);
	border-radius: 2rpx 2rpx 0 0;
	z-index: 2;
	box-shadow: 0 0 6rpx rgba(124,179,66, 0.25);
}
.signup-info-header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-bottom: 16rpx;
}
.signup-status-badge {
	padding: 6rpx 20rpx;
	border-radius: 28rpx;
	font-size: 22rpx;
	font-weight: 600;
}
.signup-status-badge.pending {
	color: #ff9f43;
	background: rgba(255, 159, 67, 0.1);
	box-shadow: 0 2rpx 8rpx rgba(255, 159, 67, 0.12), 0 0 14rpx rgba(255, 159, 67, 0.06);
}
.signup-status-badge.confirmed {
	background: rgba(124,179,66, 0.1);
	box-shadow: 0 2rpx 8rpx rgba(124,179,66, 0.12), 0 0 14rpx rgba(124,179,66, 0.06);
}
.signup-status-badge.cancelled {
	color: #999;
	background: rgba(153, 153, 153, 0.1);
	box-shadow: 0 2rpx 8rpx rgba(153, 153, 153, 0.08);
}
.signup-detail {
	padding: 20rpx 24rpx;
	background: linear-gradient(135deg, #f8f9fe, #f0f2ff);
	border-radius: 28rpx;
	border: 1rpx solid rgba(124,179,66, 0.06);
}
.signup-detail-item {
	font-size: 26rpx;
	color: #555;
	display: block;
	margin-bottom: 8rpx;
	line-height: 1.6;
}
.signup-detail-item:last-child {
	margin-bottom: 0;
}

/* ===== 底部固定栏 ===== */
.bottom-bar {
	position: fixed;
	bottom: 0;
	left: 0;
	right: 0;
	padding: 20rpx 30rpx;
	padding-bottom: calc(20rpx + env(safe-area-inset-bottom));
	background: rgba(255, 255, 255, 0.95);
	backdrop-filter: blur(24rpx);
	-webkit-backdrop-filter: blur(24rpx);
	box-shadow: 0 -2rpx 12rpx rgba(0,0,0,0.04);
	z-index: 100;
}
.btn-signup {
	text-align: center;
	padding: 28rpx;
	border-radius: 48rpx;
	font-size: 32rpx;
	font-weight: 700;
	color: #fff;
	box-shadow:
		0 4rpx 12rpx rgba(0,0,0,0.1),
		0 8rpx 24rpx rgba(0,0,0,0.06);
	transition: transform 0.2s ease, box-shadow 0.2s ease;
	position: relative;
	overflow: hidden;
}
/* CTA按钮shimmer扫光 */
.btn-signup::after {
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
.btn-signup:active {
	transform: scale(0.97);
	opacity: 0.85;
}
.btn-disabled {
	background: #e8e8f0;
	text-align: center;
	padding: 28rpx;
	border-radius: 48rpx;
	font-size: 32rpx;
	font-weight: 600;
	color: #aaa;
}
.bottom-btn-group {
	display: flex;
	gap: 20rpx;
}
.btn-cancel {
	flex: 1;
	text-align: center;
	padding: 26rpx;
	border-radius: 48rpx;
	font-size: 30rpx;
	font-weight: 600;
	color: #ff6b6b;
	border: 2rpx solid rgba(255, 107, 107, 0.3);
	background: rgba(255, 107, 107, 0.04);
	transition: all 0.2s ease;
}
.btn-cancel:active {
	background: rgba(255, 107, 107, 0.1);
}
.btn-signed {
	flex: 2;
	text-align: center;
	padding: 26rpx;
	border-radius: 48rpx;
	font-size: 30rpx;
	font-weight: 700;
	color: #fff;
}

/* ===== 弹窗 - 毛玻璃 ===== */
.modal-mask {
	position: fixed;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	background: rgba(20, 20, 40, 0.55);
	backdrop-filter: blur(8rpx);
	-webkit-backdrop-filter: blur(8rpx);
	display: flex;
	align-items: center;
	justify-content: center;
	z-index: 1000;
}
.modal-content {
	width: 620rpx;
	background: rgba(255, 255, 255, 0.92);
	backdrop-filter: blur(40rpx);
	-webkit-backdrop-filter: blur(40rpx);
	border-radius: 28rpx;
	overflow: hidden;
	box-shadow: 0 20rpx 60rpx rgba(0, 0, 0, 0.15), 0 0 0 1rpx rgba(255, 255, 255, 0.6) inset;
}
.modal-header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	padding: 30rpx;
	border-bottom: 1rpx solid rgba(124,179,66, 0.06);
}
.modal-title {
	font-size: 32rpx;
	font-weight: 700;
	color: #1c2333;
}
.modal-close {
	width: 52rpx;
	height: 52rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	font-size: 28rpx;
	color: #999;
	background: rgba(124,179,66, 0.06);
	border-radius: 50%;
	transition: all 0.2s ease;
}
.modal-close:active {
	background: rgba(124,179,66, 0.12);
}
.modal-body {
	padding: 30rpx;
}
.form-group {
	margin-bottom: 24rpx;
}
.form-group:last-child {
	margin-bottom: 0;
}
.form-label {
	font-size: 26rpx;
	font-weight: 600;
	color: #333;
	display: block;
	margin-bottom: 12rpx;
}
.required {
	color: #ff6b6b;
}
.form-input {
	height: 84rpx;
	font-size: 28rpx;
	color: #333;
	padding: 0 24rpx;
	background: rgba(124,179,66, 0.03);
	border-radius: 18rpx;
	border: 2rpx solid rgba(124,179,66, 0.1);
	transition: border-color 0.3s ease, box-shadow 0.3s ease, background 0.3s ease;
}
.form-input:focus {
	border-color: rgba(124,179,66, 0.4);
	background: rgba(124,179,66, 0.05);
	box-shadow: 0 0 0 6rpx rgba(124,179,66, 0.08), 0 0 12rpx rgba(124,179,66, 0.12);
}
.modal-footer {
	display: flex;
	padding: 20rpx 30rpx 30rpx;
	gap: 20rpx;
}
.modal-btn-cancel {
	flex: 1;
	text-align: center;
	padding: 24rpx;
	border-radius: 40rpx;
	font-size: 28rpx;
	color: #888;
	background: rgba(124,179,66, 0.06);
	transition: all 0.2s ease;
}
.modal-btn-cancel:active {
	background: rgba(124,179,66, 0.12);
}
.modal-btn-confirm {
	flex: 2;
	text-align: center;
	padding: 24rpx;
	border-radius: 40rpx;
	font-size: 28rpx;
	font-weight: 700;
	color: #fff;
	background: linear-gradient(135deg, #7cb342, #558b2f);
	box-shadow: 0 6rpx 24rpx rgba(124,179,66, 0.3);
	transition: all 0.2s ease;
	position: relative;
	overflow: hidden;
}
/* 确认按钮shimmer */
.modal-btn-confirm::after {
	content: '';
	position: absolute;
	top: 0;
	left: -100%;
	width: 25%;
	height: 100%;
	background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.25), transparent);
	animation: btnShimmer 5s ease-in-out infinite;
}
.modal-btn-confirm:active {
	transform: scale(0.97);
	box-shadow: 0 3rpx 12rpx rgba(124,179,66, 0.2);
}
.modal-btn-confirm.disabled {
	opacity: 0.45;
}

/* ===== 公告详情 ===== */
.announcement-view {
	padding: 32rpx;
	margin: 24rpx 24rpx 0;
	background: #fff;
	border-radius: 28rpx;
	box-shadow:
		0 2rpx 8rpx rgba(0,0,0,0.03),
		0 8rpx 24rpx rgba(0,0,0,0.06);
}
.ann-header {
	display: flex;
	align-items: center;
	justify-content: space-between;
	margin-bottom: 24rpx;
}
.ann-tag {
	font-size: 22rpx;
	padding: 4rpx 16rpx;
	border-radius: 8rpx;
	font-weight: 700;
	letter-spacing: 0.5rpx;
}
.ann-time {
	font-size: 24rpx;
	color: #aaa;
}
.ann-title {
	font-size: 36rpx;
	font-weight: 800;
	color: #1c2333;
	line-height: 1.5;
	letter-spacing: 1rpx;
	margin-bottom: 24rpx;
}
.ann-divider {
	height: 1rpx;
	background: linear-gradient(90deg, transparent 0%, #eee 50%, transparent 100%);
	margin-bottom: 24rpx;
}
.ann-content {
	font-size: 28rpx;
	color: #444;
	line-height: 1.8;
	letter-spacing: 0.3rpx;
}

/* 公告图片 */
.ann-images {
	margin-top: 28rpx;
	display: flex;
	flex-direction: column;
	gap: 16rpx;
}
.ann-image {
	width: 100%;
	border-radius: 28rpx;
}

/* 公告附件 */
.ann-attachments {
	margin-top: 28rpx;
	padding-top: 24rpx;
	border-top: 1rpx solid #f0f0f0;
}
.ann-attach-title {
	font-size: 26rpx;
	font-weight: 700;
	margin-bottom: 16rpx;
}
.ann-attach-item {
	display: flex;
	align-items: center;
	padding: 18rpx 20rpx;
	background: #f8f9fb;
	border-radius: 14rpx;
	margin-bottom: 12rpx;
	border: 1rpx solid #eef0f3;
}
.ann-attach-icon {
	font-size: 32rpx;
	margin-right: 14rpx;
}
.ann-attach-name {
	flex: 1;
	font-size: 26rpx;
	color: #333;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
}
.ann-attach-dl {
	flex-shrink: 0;
	font-size: 24rpx;
	font-weight: 600;
	margin-left: 16rpx;
}
</style>
