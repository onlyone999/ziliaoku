<template>
	<view class="page">
		<!-- 顶部渐变区 -->
		<view class="header" :style="'background:' + tc.primary + ';'">
			<!-- 返回按钮 -->
			<view class="back-btn" @tap="goBack">
				<text class="back-icon">‹</text>
			</view>
			<!-- 积分数字 -->
			<view class="header-center">
				<text class="header-label">我的积分</text>
				<view class="points-row">
					<text class="points-num">{{ balance.points || 0 }}</text>
				</view>
			</view>
			<!-- 统计 -->
			<view class="header-stats">
				<view class="hs-item">
					<text class="hs-val">+{{ balance.total_earned || 0 }}</text>
					<text class="hs-lab">累计获得</text>
				</view>
				<view class="hs-sep"></view>
				<view class="hs-item">
					<text class="hs-val">-{{ balance.total_spent || 0 }}</text>
					<text class="hs-lab">累计消费</text>
				</view>
				<view class="hs-sep"></view>
				<view class="hs-item">
					<text class="hs-val">{{ Object.keys(rules).length }}</text>
					<text class="hs-lab">获取渠道</text>
				</view>
			</view>
		</view>

		<scroll-view scroll-y class="scroll" :show-scrollbar="false" @scrolltolower="loadMore">
			<!-- 签到卡 -->
			<view class="signin-card" :class="{ signed: balance.signed_today }">
				<view class="sc-left">
					<text class="sc-emoji">{{ balance.signed_today ? '✅' : '📅' }}</text>
					<view class="sc-info">
						<text class="sc-title">{{ balance.signed_today ? '今日已签到' : '每日签到' }}</text>
						<text class="sc-desc">连续签到可获得额外积分奖励</text>
					</view>
				</view>
				<view
					class="sc-btn"
					:style="balance.signed_today ? 'background:#e8fae8;color:#22c55e;' : 'background:' + tc.primary + ';color:#fff;'"
					@tap="doSignin"
				>
					<text>{{ balance.signed_today ? '已签到' : '签到 +' + signinPoints }}</text>
				</view>
			</view>

			<!-- 赚取积分 -->
			<view class="card">
				<view class="card-title-row">
					<text class="card-title">🎯 赚取积分</text>
					<text class="card-sub" :style="'color:' + tc.primary + ';'">共{{ Object.keys(rules).length }}种方式</text>
				</view>
				<view class="earn-row" v-for="(item, key) in rules" :key="key" v-if="item.points > 0">
					<view class="er-icon" :style="'background:' + tc.primary + '10;'">
						<text>{{ iconMap[key] || '⭐' }}</text>
					</view>
					<view class="er-mid">
						<text class="er-name">{{ item.name }}</text>
						<text class="er-sub">{{ item.limit }}</text>
					</view>
					<text class="er-pts" :style="'color:' + tc.primary + ';'">+{{ item.points }}</text>
				</view>
			</view>

			<!-- 积分用途 -->
			<view class="card">
				<text class="card-title">💎 积分用途</text>
				<view class="use-row">
					<view class="ur-item" @tap="goVip">
						<text class="ur-icon">👑</text>
						<text class="ur-name">兑换VIP</text>
					</view>
					<view class="ur-item">
						<text class="ur-icon">📦</text>
						<text class="ur-name">兑换资源</text>
					</view>
					<view class="ur-item" @tap="goRanking">
						<text class="ur-icon">🏆</text>
						<text class="ur-name">排行榜</text>
					</view>
					<view class="ur-item" @tap="goRule">
						<text class="ur-icon">📋</text>
						<text class="ur-name">积分规则</text>
					</view>
				</view>
			</view>

			<!-- 积分明细 -->
			<view class="card">
				<view class="card-title-row">
					<text class="card-title">📝 积分明细</text>
				</view>
				<view v-if="logs.length === 0 && !loading" class="empty">
					<text class="empty-icon">📭</text>
					<text class="empty-text">暂无积分记录</text>
				</view>
				<view v-else>
					<view class="log-row" v-for="item in logs" :key="item.id">
						<view class="lr-left">
							<text class="lr-desc">{{ item.description }}</text>
							<text class="lr-time">{{ formatTime(item.created_at) }}</text>
						</view>
						<view class="lr-right">
							<text class="lr-pts" :style="item.type === 'earn' ? 'color:' + tc.primary + ';' : 'color:#ff4757;'">
								{{ item.type === 'earn' ? '+' : '-' }}{{ item.points }}
							</text>
							<text class="lr-bal">余{{ item.balance_after }}</text>
						</view>
					</view>
				</view>
				<view class="load-tip" v-if="logs.length > 0">
					<text v-if="loading">加载中...</text>
					<text v-else-if="noMore">— 没有更多了 —</text>
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
			balance: { points: 0, total_earned: 0, total_spent: 0, signed_today: false },
			signinPoints: 5,
			rules: {},
			iconMap: {
				signin: '📅', register: '🎉', download: '📥', share: '🔗',
				comment: '💬', favorite: '❤️', complete_profile: '✅', invite: '🤝'
			},
			logs: [],
			page: 1,
			loading: false,
			noMore: false
		};
	},
	onLoad() {
		this.loadBalance();
		this.loadRules();
		this.loadLogs();
	},
	methods: {
		goBack() {
			uni.navigateBack({ fail: () => uni.switchTab({ url: '/pages/user/index' }) });
		},
		async loadBalance() {
			try {
				const res = await http.get('/api/points/balance', {}, { silent: true });
				if (res.code === 0) {
					this.balance = res.data;
					if (this.rules.signin) this.signinPoints = this.rules.signin.points;
				}
			} catch (e) {}
		},
		async loadRules() {
			try {
				const res = await http.get('/api/points/rules', {}, { silent: true });
				if (res.code === 0) {
					this.rules = res.data;
					if (this.rules.signin) this.signinPoints = this.rules.signin.points;
				}
			} catch (e) {}
		},
		async doSignin() {
			if (this.balance.signed_today) {
				uni.showToast({ title: '今日已签到', icon: 'none' });
				return;
			}
			try {
				const res = await http.post('/api/points/signin');
				if (res.code === 0) {
					this.balance.points = res.data.balance;
					this.balance.signed_today = true;
					this.balance.total_earned += res.data.points;
					this.logs.unshift({
						id: Date.now(), points: res.data.points, type: 'earn',
						description: '每日签到' + (res.data.bonus > 0 ? '（连续奖励+' + res.data.bonus + '）' : ''),
						balance_after: res.data.balance,
						created_at: new Date().toISOString().replace('T', ' ').substring(0, 19)
					});
					uni.showToast({ title: '签到成功，+' + res.data.points + '积分', icon: 'success' });
				}
			} catch (e) {}
		},
		async loadLogs() {
			if (this.loading || this.noMore) return;
			this.loading = true;
			try {
				const res = await http.get('/api/points/log', { page: this.page, page_size: 20 }, { silent: true });
				if (res.code === 0) {
					const list = res.data.list || res.data || [];
					this.logs = this.page === 1 ? list : [...this.logs, ...list];
					if (list.length < 20) this.noMore = true;
					this.page++;
				}
			} catch (e) {} finally { this.loading = false; }
		},
		loadMore() { this.loadLogs(); },
		goVip() { uni.navigateTo({ url: '/pages/user/vip' }); },
		goRanking() { uni.navigateTo({ url: '/pages/points/ranking' }); },
		goRule() { uni.navigateTo({ url: '/pages/points/rule' }); },
		formatTime(dt) { return dt ? dt.substring(5, 16) : ''; }
	}
};
</script>

<style scoped>
.page { width: 100%; height: 100vh; background: #f2f3f8; overflow: hidden; display: flex; flex-direction: column; }
page { overflow: hidden; height: 100vh; }

/* ===== 顶部 ===== */
.header {
	padding: 80rpx 32rpx 48rpx;
	position: relative;
	border-radius: 0 0 44rpx 44rpx;
}
.header::after {
	content: '';
	position: absolute;
	bottom: -30rpx;
	left: 0;
	right: 0;
	height: 60rpx;
	background: inherit;
	border-radius: 0 0 44rpx 44rpx;
	filter: blur(20rpx);
	opacity: 0.3;
	pointer-events: none;
}
.back-btn {
	position: absolute;
	top: 80rpx;
	left: 24rpx;
	width: 64rpx;
	height: 64rpx;
	border-radius: 50%;
	background: rgba(255,255,255,0.2);
	display: flex;
	align-items: center;
	justify-content: center;
	z-index: 10;
}
.back-btn:active { background: rgba(255,255,255,0.35); }
.back-icon { font-size: 44rpx; color: #fff; font-weight: 300; line-height: 1; margin-top: -4rpx; }
.header-center { text-align: center; margin-bottom: 32rpx; }
.header-label { font-size: 24rpx; color: rgba(255,255,255,0.75); letter-spacing: 3rpx; }
.points-row { margin-top: 8rpx; }
.points-num { font-size: 88rpx; font-weight: 800; color: #fff; letter-spacing: 2rpx; line-height: 1.1; }
.header-stats {
	display: flex;
	justify-content: center;
	align-items: center;
	gap: 0;
	background: rgba(255,255,255,0.12);
	border-radius: 20rpx;
	padding: 20rpx 0;
}
.hs-item { flex: 1; text-align: center; }
.hs-val { display: block; font-size: 28rpx; font-weight: 700; color: #fff; }
.hs-lab { display: block; font-size: 20rpx; color: rgba(255,255,255,0.65); margin-top: 4rpx; }
.hs-sep { width: 1rpx; height: 48rpx; background: rgba(255,255,255,0.2); }

.scroll { flex: 1; height: 0; padding: 0 28rpx; margin-top: -10rpx; position: relative; z-index: 5; box-sizing: border-box; }

/* ===== 签到卡 ===== */
.signin-card {
	display: flex;
	align-items: center;
	justify-content: space-between;
	background: #fff;
	border-radius: 24rpx;
	padding: 28rpx;
	margin-bottom: 20rpx;
	box-shadow: 0 2rpx 12rpx rgba(0,0,0,0.04);
	box-sizing: border-box;
	overflow: hidden;
}
.signin-card.signed { background: #fafffe; }
.sc-left { display: flex; align-items: center; gap: 18rpx; }
.sc-emoji { font-size: 40rpx; }
.sc-title { font-size: 28rpx; font-weight: 700; color: #1a1a2e; display: block; }
.sc-desc { font-size: 22rpx; color: #aaa; margin-top: 4rpx; }
.sc-btn { padding: 14rpx 32rpx; border-radius: 32rpx; font-size: 24rpx; font-weight: 700; }
.sc-btn:active { opacity: 0.8; }

/* ===== 卡片通用 ===== */
.card {
	background: #fff;
	border-radius: 24rpx;
	padding: 28rpx;
	margin-bottom: 20rpx;
	box-shadow: 0 2rpx 12rpx rgba(0,0,0,0.04);
	box-sizing: border-box;
	overflow: hidden;
}
.card-title-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20rpx; }
.card-title { font-size: 30rpx; font-weight: 700; color: #1a1a2e; }
.card-sub { font-size: 22rpx; font-weight: 500; }

/* ===== 赚取积分 ===== */
.earn-row {
	display: flex;
	align-items: center;
	padding: 16rpx 0;
	border-bottom: 1rpx solid #f6f6f9;
	min-width: 0;
}
.earn-row:last-child { border-bottom: none; }
.er-icon {
	width: 68rpx;
	height: 68rpx;
	border-radius: 16rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	flex-shrink: 0;
	font-size: 28rpx;
}
.er-mid { flex: 1; margin: 0 16rpx; min-width: 0; }
.er-name { font-size: 26rpx; color: #1a1a2e; font-weight: 600; display: block; }
.er-sub { font-size: 20rpx; color: #bbb; margin-top: 2rpx; }
.er-pts { font-size: 30rpx; font-weight: 800; flex-shrink: 0; }

/* ===== 积分用途 ===== */
.use-row { display: flex; margin-top: 8rpx; }
.ur-item { flex: 1; display: flex; flex-direction: column; align-items: center; padding: 16rpx 0; border-radius: 16rpx; }
.ur-item:active { background: #f6f6f9; }
.ur-icon { font-size: 44rpx; margin-bottom: 8rpx; }
.ur-name { font-size: 22rpx; color: #555; font-weight: 600; }

/* ===== 积分明细 ===== */
.log-row {
	display: flex;
	justify-content: space-between;
	align-items: center;
	padding: 16rpx 0;
	border-bottom: 1rpx solid #f6f6f9;
}
.log-row:last-child { border-bottom: none; }
.lr-desc { font-size: 26rpx; color: #333; display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 400rpx; }
.lr-time { font-size: 20rpx; color: #ccc; margin-top: 4rpx; }
.lr-right { text-align: right; flex-shrink: 0; }
.lr-pts { font-size: 30rpx; font-weight: 800; display: block; }
.lr-bal { font-size: 20rpx; color: #ccc; }

.empty { padding: 48rpx 0; text-align: center; }
.empty-icon { font-size: 48rpx; display: block; margin-bottom: 12rpx; }
.empty-text { font-size: 24rpx; color: #ccc; }
.load-tip { text-align: center; padding: 20rpx 0; font-size: 22rpx; color: #ccc; }
</style>
