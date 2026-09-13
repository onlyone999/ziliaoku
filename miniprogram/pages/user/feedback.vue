<template>
	<view class="page">
		<scroll-view scroll-y class="main-scroll">
			<!-- 反馈类型 -->
			<view class="form-section">
				<text class="form-label">反馈类型</text>
				<view class="type-grid">
					<view
						class="type-item"
						:class="{ active: form.type === item.value }"
						v-for="item in typeOptions"
						:key="item.value"
						@tap="form.type = item.value"
					>
						<text class="type-icon">{{ item.icon }}</text>
						<text class="type-name">{{ item.label }}</text>
					</view>
				</view>
			</view>

			<!-- 反馈内容 -->
			<view class="form-section">
				<text class="form-label">反馈内容</text>
				<view class="textarea-wrap">
					<textarea
						class="form-textarea"
						v-model="form.content"
						placeholder="请详细描述您的问题或建议..."
						maxlength="500"
						:auto-height="false"
					></textarea>
					<text class="char-count">{{ form.content.length }}/500</text>
				</view>
			</view>

			<!-- 联系方式 -->
			<view class="form-section">
				<text class="form-label">联系方式 (选填)</text>
				<input
					class="form-input"
					v-model="form.contact"
					placeholder="手机号/微信/邮箱，方便我们联系您"
				/>
			</view>

			<!-- 提交按钮 -->
			<view class="submit-area">
				<view
					class="submit-btn"
					:class="{ disabled: !canSubmit }"
					:style="'background:' + tc.primary + ';'"
					@tap="submitFeedback"
				>
					<text>提交反馈</text>
				</view>
			</view>

			<!-- 我的反馈历史 -->
			<view class="history-section" v-if="feedbackHistory.length > 0">
				<view class="history-header">
					<text class="history-title">我的反馈</text>
					<text class="history-count">共{{ feedbackHistory.length }}条</text>
				</view>
				<view class="history-list">
					<view class="history-item" v-for="item in feedbackHistory" :key="item.id">
						<view class="history-top">
							<view class="history-type-badge" :style="tPri">
								<text>{{ getTypeIcon(item.type) }} {{ getTypeLabel(item.type) }}</text>
							</view>
							<view class="history-status-badge" :class="item.status" :style="item.status === 'processing' || item.status === 'resolved' ? tPri : ''">
								<text>{{ getStatusText(item.status) }}</text>
							</view>
						</view>
						<text class="history-content">{{ item.content }}</text>
						<view class="history-reply" v-if="item.reply">
							<text class="reply-label">官方回复:</text>
							<text class="reply-content">{{ item.reply }}</text>
						</view>
						<text class="history-time">{{ item.created_at }}</text>
					</view>
				</view>
			</view>

			<view style="height: 60rpx;"></view>
		</scroll-view>
	</view>
</template>

<script>
import http from '@/utils/http.js';

export default {
	data() {
		return {
			form: {
				type: 'bug',
				content: '',
				contact: ''
			},
			typeOptions: [
				{ value: 'bug', label: '问题反馈', icon: '🐛' },
				{ value: 'suggest', label: '功能建议', icon: '💡' },
				{ value: 'other', label: '其他', icon: '💬' }
			],
			submitting: false,
			feedbackHistory: []
		};
	},
	computed: {
		canSubmit() {
			return this.form.content.trim().length > 0 && !this.submitting;
		}
	},
	onLoad() {
		this.loadHistory();
	},
	methods: {
		async loadHistory() {
			const token = uni.getStorageSync('token');
			if (!token) return;
			try {
				const res = await http.get('/api/feedback/list');
				if (res.code === 0) {
					this.feedbackHistory = res.data.list || res.data || [];
				}
			} catch (e) {}
		},
		getTypeIcon(type) {
			const map = { bug: '🐛', suggest: '💡', other: '💬' };
			return map[type] || '💬';
		},
		getTypeLabel(type) {
			const map = { bug: '问题反馈', suggest: '功能建议', other: '其他' };
			return map[type] || '其他';
		},
		getStatusText(status) {
			const map = { pending: '待处理', processing: '处理中', resolved: '已解决', closed: '已关闭' };
			return map[status] || '待处理';
		},
		async submitFeedback() {
			if (!this.canSubmit) return;

			const token = uni.getStorageSync('token');
			if (!token) {
				uni.showModal({
					title: '提示',
					content: '请先登录后再提交反馈',
					confirmText: '去登录',
					success: (res) => {
						if (res.confirm) {
							uni.switchTab({ url: '/pages/user/index' });
						}
					}
				});
				return;
			}

			this.submitting = true;
			try {
				const res = await http.post('/api/feedback/submit', {
					type: this.form.type,
					content: this.form.content.trim(),
					contact: this.form.contact.trim()
				});
				if (res.code === 0) {
					uni.showToast({ title: '提交成功', icon: 'success' });
					this.form.content = '';
					this.form.contact = '';
					this.loadHistory();
				} else {
					uni.showToast({ title: res.msg || '提交失败', icon: 'none' });
				}
			} catch (e) {
				uni.showToast({ title: '提交失败', icon: 'none' });
			} finally {
				this.submitting = false;
			}
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
	background: linear-gradient(180deg, #eef0f8 0%, #f3f4f8 8%, #f7f8fc 20%, #fafbfe 50%, #f8f9fc 100%);
}
.main-scroll {
	height: 100vh;
}

/* ===== 表单区块 ===== */
.form-section {
	margin: 24rpx;
	background: #fff;
	border-radius: 28rpx;
	padding: 28rpx 30rpx;
	box-shadow:
		0 2rpx 8rpx rgba(0,0,0,0.03),
		0 8rpx 24rpx rgba(0,0,0,0.06),
		0 16rpx 40rpx rgba(0,0,0,0.04);
	border: 1rpx solid rgba(0,0,0,0.03);
	position: relative;
}
/* 顶部渐变装饰线 */
.form-section::before {
	content: '';
	position: absolute;
	top: 0;
	left: 0;
	right: 0;
	height: 2rpx;
	background: linear-gradient(90deg, transparent 0%, rgba(0,0,0,0.06) 30%, rgba(0,0,0,0.08) 50%, rgba(0,0,0,0.06) 70%, transparent 100%);
	border-radius: 2rpx 2rpx 0 0;
	z-index: 2;
	opacity: 0.4;
}
.form-label {
	font-size: 28rpx;
	font-weight: 600;
	color: #1a1a2e;
	margin-bottom: 20rpx;
	display: block;
}

/* ===== 类型选择 ===== */
.type-grid {
	display: flex;
	gap: 16rpx;
}
.type-item {
	flex: 1;
	display: flex;
	flex-direction: column;
	align-items: center;
	padding: 24rpx 0;
	background: linear-gradient(135deg, #f8f9fe, #f0f2ff);
	border-radius: 20rpx;
	border: 2rpx solid transparent;
	transition: all 0.3s ease;
}
.type-item.active {
	background: linear-gradient(135deg, rgba(46,213,115, 0.1), rgba(145, 228, 50, 0.06));
	box-shadow: 0 4rpx 16rpx rgba(46,213,115, 0.15);
}
.type-icon {
	font-size: 44rpx;
	margin-bottom: 8rpx;
}
.type-name {
	font-size: 24rpx;
	color: #666;
}
.type-item.active .type-name {
	background: linear-gradient(135deg, #2ed573, #1abc9c);
	font-weight: 600;
}

/* ===== 文本域 - focus态 ===== */
.textarea-wrap {
	position: relative;
}
.form-textarea {
	width: 100%;
	height: 300rpx;
	font-size: 28rpx;
	color: #1a1a2e;
	padding: 20rpx;
	background: rgba(0,0,0,0.02);
	border-radius: 20rpx;
	border: 1rpx solid rgba(0,0,0,0.04);
	box-sizing: border-box;
	line-height: 1.6;
	border: 2rpx solid transparent;
	transition: all 0.3s ease;
}
.form-textarea:focus {
	background: #fff;
	border-color: rgba(46,213,115, 0.3);
	box-shadow: 0 0 0 4rpx rgba(46,213,115, 0.08), 0 4rpx 16rpx rgba(46,213,115, 0.06);
}
.char-count {
	position: absolute;
	bottom: 16rpx;
	right: 20rpx;
	font-size: 22rpx;
	color: #bbb;
}

/* ===== 输入框 - focus态 ===== */
.form-input {
	height: 80rpx;
	font-size: 28rpx;
	color: #1a1a2e;
	padding: 0 24rpx;
	background: #f8f9fe;
	border-radius: 20rpx;
	border: 2rpx solid transparent;
	transition: all 0.3s ease;
}
.form-input:focus {
	background: #fff;
	border-color: rgba(46,213,115, 0.3);
	box-shadow: 0 0 0 4rpx rgba(46,213,115, 0.08), 0 4rpx 16rpx rgba(46,213,115, 0.06);
}

/* ===== 提交按钮 ===== */
.submit-area {
	margin: 40rpx 24rpx;
}
.submit-btn {
	background: #2ed573;
	text-align: center;
	padding: 26rpx;
	border-radius: 44rpx;
	font-size: 30rpx;
	font-weight: 600;
	color: #fff;
	box-shadow:
		0 4rpx 12rpx rgba(0,0,0,0.1),
		0 8rpx 24rpx rgba(0,0,0,0.06);
	transition: all 0.3s ease;
	letter-spacing: 2rpx;
	position: relative;
	overflow: hidden;
}
/* shimmer扫光 */
.submit-btn::after {
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
.submit-btn:active {
	transform: scale(0.97);
	box-shadow: 0 4rpx 16rpx rgba(46,213,115, 0.4);
}
.submit-btn.disabled {
	opacity: 0.5;
	box-shadow: none;
}

/* ===== 反馈历史 ===== */
.history-section {
	margin: 24rpx;
}
.history-header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-bottom: 16rpx;
}
.history-title {
	font-size: 32rpx;
	font-weight: 700;
	color: #1a1a2e;
}
.history-count {
	font-size: 24rpx;
	color: #aaa;
}
.history-list {
	display: flex;
	flex-direction: column;
	gap: 16rpx;
}
.history-item {
	background: #fff;
	border-radius: 28rpx;
	padding: 24rpx;
	box-shadow:
		0 2rpx 8rpx rgba(0,0,0,0.03),
		0 8rpx 24rpx rgba(0,0,0,0.06),
		0 16rpx 40rpx rgba(0,0,0,0.04);
	border: 1rpx solid rgba(0,0,0,0.03);
	transition: transform 0.2s ease;
	position: relative;
}
/* 顶部渐变装饰线 */
.history-item::before {
	content: '';
	position: absolute;
	top: 0;
	left: 0;
	right: 0;
	height: 2rpx;
	background: linear-gradient(90deg, transparent 0%, rgba(0,0,0,0.06) 30%, rgba(0,0,0,0.08) 50%, rgba(0,0,0,0.06) 70%, transparent 100%);
	border-radius: 2rpx 2rpx 0 0;
	z-index: 2;
	opacity: 0.4;
}
.history-item:active {
	transform: scale(0.985);
}
.history-top {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-bottom: 12rpx;
}
.history-type-badge {
	font-size: 22rpx;
	background: linear-gradient(135deg, rgba(46,213,115, 0.12), rgba(145, 228, 50, 0.06));
	padding: 6rpx 18rpx;
	border-radius: 20rpx;
	font-weight: 500;
	box-shadow: 0 2rpx 8rpx rgba(46,213,115, 0.08);
}
.history-status-badge {
	font-size: 22rpx;
	padding: 6rpx 18rpx;
	border-radius: 20rpx;
	font-weight: 500;
}
.history-status-badge.pending {
	color: #ff9f43;
	background: linear-gradient(135deg, rgba(255, 159, 67, 0.14), rgba(255, 159, 67, 0.06));
	box-shadow: 0 2rpx 8rpx rgba(255, 159, 67, 0.12);
}
.history-status-badge.processing {

	background: linear-gradient(135deg, rgba(46,213,115, 0.14), rgba(46,213,115, 0.06));
	box-shadow: 0 2rpx 8rpx rgba(46,213,115, 0.12);
}
.history-status-badge.resolved {

	background: linear-gradient(135deg, rgba(46,213,115, 0.14), rgba(46,213,115, 0.06));
	box-shadow: 0 2rpx 8rpx rgba(46,213,115, 0.12);
}
.history-status-badge.closed {
	color: #999;
	background: linear-gradient(135deg, rgba(153, 153, 153, 0.14), rgba(153, 153, 153, 0.06));
	box-shadow: 0 2rpx 8rpx rgba(153, 153, 153, 0.08);
}
.history-content {
	font-size: 26rpx;
	color: #555;
	line-height: 1.6;
	display: block;
}
.history-reply {
	margin-top: 14rpx;
	padding: 14rpx 18rpx;
	background: linear-gradient(135deg, #f8f9fe, #f0f2ff);
	border-radius: 16rpx;
	border-left: 4rpx solid #2ed573;
	position: relative;
}
/* 回复区域左侧装饰条发光 */
.history-reply::before {
	content: '';
	position: absolute;
	left: -4rpx;
	top: 0;
	bottom: 0;
	width: 4rpx;
	background: linear-gradient(180deg, #2ed573, #27ae60, #1abc9c);
	border-radius: 4rpx;
	box-shadow: 0 0 6rpx rgba(46,213,115, 0.4), 0 0 12rpx rgba(46,213,115, 0.15);
}
.reply-label {
	font-size: 22rpx;
	background: linear-gradient(135deg, #2ed573, #1abc9c);
	font-weight: 600;
	margin-right: 8rpx;
}
.reply-content {
	font-size: 24rpx;
	color: #555;
	line-height: 1.6;
}
.history-time {
	font-size: 22rpx;
	color: #bbb;
	margin-top: 10rpx;
	display: block;
}
</style>
