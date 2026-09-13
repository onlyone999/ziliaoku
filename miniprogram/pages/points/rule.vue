<template>
	<view class="page">
		<scroll-view scroll-y class="scroll" :show-scrollbar="false">
			<!-- 获取积分 -->
			<view class="card">
				<text class="card-title" :style="'color:' + tc.primary + ';'">🎯 如何获取积分</text>
				<view class="rule-item" v-for="(item, key) in rules" :key="key" v-if="item.points > 0">
					<text class="rule-icon">{{ iconMap[key] || '⭐' }}</text>
					<view class="rule-info">
						<text class="rule-name">{{ item.name }}</text>
						<text class="rule-desc">{{ item.desc }}（{{ item.limit }}）</text>
					</view>
					<text class="rule-points" :style="'color:' + tc.primary + ';'">+{{ item.points }}</text>
				</view>
			</view>

			<!-- 积分用途 -->
			<view class="card">
				<text class="card-title" :style="'color:' + tc.primary + ';'">💎 积分用途</text>
				<view class="rule-item">
					<text class="rule-icon">📦</text>
					<view class="rule-info">
						<text class="rule-name">兑换资源</text>
						<text class="rule-desc">部分资源支持积分直接兑换，无需付费</text>
					</view>
				</view>
				<view class="rule-item">
					<text class="rule-icon">👑</text>
					<view class="rule-info">
						<text class="rule-name">兑换VIP</text>
						<text class="rule-desc">积分兑换VIP会员，享受全场资源免费下载</text>
					</view>
				</view>
			</view>

			<!-- 注意事项 -->
			<view class="card">
				<text class="card-title" :style="'color:' + tc.primary + ';'">⚠️ 注意事项</text>
				<text class="note">• 积分不可转让，不可提现</text>
				<text class="note">• 积分兑换的资源与付费购买享有同等权益</text>
				<text class="note">• 积分兑换VIP后，VIP期内下载不再获得积分</text>
				<text class="note">• 恶意刷积分将被清零并封号处理</text>
				<text class="note">• 每日签到连续7天可获得额外奖励</text>
			</view>

			<!-- 邀请码 -->
			<view class="card">
				<text class="card-title" :style="'color:' + tc.primary + ';'">🤝 邀请好友</text>
				<view class="invite-wrap">
					<text class="invite-desc">输入好友邀请码，双方均可获得积分奖励</text>
					<view class="invite-row">
						<input class="invite-input" v-model="inviteCode" placeholder="请输入邀请码" />
						<view class="invite-btn" :style="'background:' + tc.primary + ';'" @tap="submitInvite">
							<text>兑换</text>
						</view>
					</view>
				</view>
				<view class="my-code-wrap">
					<text class="my-code-label">我的邀请码：</text>
					<text class="my-code-value" :style="'color:' + tc.primary + ';'" @tap="copyCode">{{ myInviteCode || '登录后查看' }}</text>
					<text class="my-code-copy" v-if="myInviteCode" @tap="copyCode">复制</text>
				</view>
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
			rules: {},
			inviteCode: '',
			myInviteCode: '',
			iconMap: {
				signin: '📅', download: '↓', share: '↗', comment: '💬',
				favorite: '❤️', complete_profile: '✅', invite: '🤝', register: '🎉'
			}
		};
	},
	onLoad() {
		this.loadRules();
		this.loadMyCode();
	},
	methods: {
		async loadRules() {
			try {
				const res = await http.get('/api/points/rules', {}, { silent: true });
				if (res.code === 0) this.rules = res.data;
			} catch (e) {}
		},
		loadMyCode() {
			try {
				// 尝试从 userInfo 中获取用户ID
				let uid = 0;
				const raw = uni.getStorageSync('userInfo');
				if (raw) {
					const info = typeof raw === 'string' ? JSON.parse(raw) : raw;
					uid = info.id || info.user_id || 0;
				}
				if (!uid) uid = uni.getStorageSync('userId') || 0;
				if (uid > 0) {
					// 用36进制编码用户ID作为邀请码，6位补零
					this.myInviteCode = parseInt(uid).toString(36).toUpperCase().padStart(6, '0');
				}
			} catch (e) {}
		},
		copyCode() {
			if (!this.myInviteCode) return;
			uni.setClipboardData({ data: this.myInviteCode });
		},
		async submitInvite() {
			if (!this.inviteCode.trim()) {
				uni.showToast({ title: '请输入邀请码', icon: 'none' });
				return;
			}
			try {
				const res = await http.post('/api/points/invite', { invite_code: this.inviteCode.trim() }, { silent: true });
				if (res.code === 0) {
					uni.showModal({ title: '🎉 兑换成功', content: res.message || '积分已到账', showCancel: false });
					this.inviteCode = '';
				} else {
					uni.showModal({ title: '兑换失败', content: res.message || res.msg || '邀请码无效', showCancel: false });
				}
			} catch (e) {
				const msg = (e && e.message) ? e.message : '网络异常，请稍后重试';
				uni.showModal({ title: '兑换失败', content: msg, showCancel: false });
			}
		}
	}
};
</script>

<style scoped>
.page { height: 100vh; background: #f5f6fa; overflow: hidden; display: flex; flex-direction: column; }
page { overflow: hidden; height: 100vh; }
.scroll { flex: 1; height: 0; padding: 24rpx 28rpx; box-sizing: border-box; }
.card {
	background: #fff;
	border-radius: 20rpx;
	padding: 28rpx;
	margin-bottom: 20rpx;
	box-shadow: 0 4rpx 16rpx rgba(0,0,0,0.04);
	box-sizing: border-box;
	overflow: hidden;
}
.card-title { font-size: 30rpx; font-weight: 700; display: block; margin-bottom: 20rpx; }
.rule-item { display: flex; align-items: center; padding: 18rpx 0; border-bottom: 1rpx solid #f5f5f8; }
.rule-item:last-child { border-bottom: none; }
.rule-icon { font-size: 36rpx; width: 60rpx; text-align: center; flex-shrink: 0; }
.rule-info { flex: 1; margin: 0 16rpx; min-width: 0; }
.rule-name { font-size: 28rpx; color: #333; font-weight: 600; display: block; }
.rule-desc { font-size: 22rpx; color: #999; margin-top: 4rpx; }
.rule-points { font-size: 30rpx; font-weight: 700; flex-shrink: 0; }
.note { font-size: 24rpx; color: #888; line-height: 2; display: block; }
.invite-wrap { margin-bottom: 20rpx; }
.invite-desc { font-size: 24rpx; color: #888; margin-bottom: 16rpx; }
.invite-row { display: flex; gap: 16rpx; }
.invite-input {
	flex: 1;
	height: 72rpx;
	border: 1rpx solid #e5e7eb;
	border-radius: 12rpx;
	padding: 0 20rpx;
	font-size: 28rpx;
	box-sizing: border-box;
}
.invite-btn {
	padding: 0 32rpx;
	height: 72rpx;
	border-radius: 12rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	color: #fff;
	font-size: 28rpx;
	font-weight: 600;
	flex-shrink: 0;
}
.my-code-wrap {
	display: flex;
	align-items: center;
	background: #f8f9fb;
	border-radius: 12rpx;
	padding: 18rpx 20rpx;
}
.my-code-label { font-size: 24rpx; color: #888; flex-shrink: 0; }
.my-code-value { font-size: 30rpx; font-weight: 700; flex: 1; letter-spacing: 2rpx; margin: 0 12rpx; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.my-code-copy { font-size: 24rpx; color: #999; padding: 8rpx 16rpx; flex-shrink: 0; }
</style>
