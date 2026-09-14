<template>
	<view class="page" :class="'theme-' + activeTheme" :style="pageBgStyle">
		<view
			class="header-bg"
			:style="'background:' + tc.headerGradientLong + ';'"
		>
			<view class="soft-orb o1" :style="'background:' + tc.primary + '30;'"></view>
			<view class="soft-orb o2"></view>
			<view class="soft-orb o3"></view>
			<view class="header-fade" :style="'background:linear-gradient(180deg, rgba(240,247,230,0) 0%, ' + tc.pageBg + ' 100%);'"></view>
		</view>

		<scroll-view scroll-y class="main-scroll">
			<!-- 自定义顶栏 -->
			<view class="user-nav">
				<view class="status-bar"></view>
				<view class="nav-row">
					<view class="nav-spacer"></view>
					<text class="nav-title">我的</text>
					<view class="nav-right">
						<view class="nav-icon-btn" @tap="openThemePicker">
							<text class="nav-icon">▦</text>
						</view>
						<view class="nav-icon-btn" @tap="openThemePicker">
							<text class="nav-icon nav-dots">•••</text>
						</view>
					</view>
				</view>
			</view>

			<!-- 已登录 -->
			<view class="hero" v-if="isLoggedIn">
				<view class="hi-row">
					<view class="avatar-ring" :style="'background:linear-gradient(145deg,#ffffff 0%,' + tc.primaryLight + 'aa 100%);box-shadow:0 8rpx 24rpx ' + tc.primary + '2e, 0 2rpx 6rpx rgba(70,100,30,0.04);'" @tap="openEditProfile">
						<image class="avatar" :src="avatarSrc" mode="aspectFill" @error="onAvatarError"></image>
						<view class="avatar-badge" v-if="userInfo.is_vip">
							<text>V</text>
						</view>
					</view>
					<view class="hi-info">
						<text class="hi-title">Hi, {{ maskName }}</text>
						<text class="hi-sub">{{ hiTagline }}</text>
					</view>
					<view class="edit-btn" @tap="openEditProfile">
						<text>编辑</text>
					</view>
				</view>

				<view class="level-stage">
					<view class="level-main" @tap="goVip">
						<view class="level-row">
							<text class="level-name">{{ userInfo.is_vip ? '尊享会员' : '新鲜人' }}</text>
							<view class="info-dot" @tap.stop="goVip">
								<text>i</text>
							</view>
						</view>
						<text class="level-sub">{{ levelUpgradeText }}</text>
						<view class="level-progress" v-if="!userInfo.is_vip">
							<view
								class="level-progress-bar"
								:style="'width:' + vipProgress + '%;background:linear-gradient(90deg,' + tc.primaryLight + ',' + tc.primary + ');'"
							></view>
						</view>
						<view class="level-cta" @tap.stop="goVip">
							<text>{{ userInfo.is_vip ? '查看权益' : '去开通' }}</text>
							<text class="cta-arrow">›</text>
						</view>
					</view>
					<view class="hero-character" aria-hidden="true">
						<image class="hero-illu" src="/static/user-hero.png" mode="aspectFit"></image>
					</view>
				</view>
			</view>

			<!-- 未登录 -->
			<view class="hero" v-if="!isLoggedIn">
				<view class="hi-row">
					<view class="avatar-ring" :style="'background:linear-gradient(145deg,#ffffff 0%,' + tc.primaryLight + 'aa 100%);box-shadow:0 8rpx 24rpx ' + tc.primary + '2e, 0 2rpx 6rpx rgba(70,100,30,0.04);'">
						<image class="avatar" src="/static/default-avatar.png" mode="aspectFill"></image>
					</view>
					<view class="hi-info">
						<text class="hi-title">Hi, 你好</text>
						<text class="hi-sub">登录后同步下载、收藏与积分</text>
					</view>
					<view class="edit-btn edit-btn-login" @tap="quickLogin">
						<text>{{ loginLoading ? '登录中' : '登录' }}</text>
					</view>
				</view>

				<view class="level-stage">
					<view class="level-main">
						<view class="level-row">
							<text class="level-name">访客</text>
						</view>
						<text class="level-sub">登录后可查看等级与成长权益</text>
					</view>
					<view class="hero-character" aria-hidden="true">
						<image class="hero-illu" src="/static/user-hero.png" mode="aspectFit"></image>
					</view>
				</view>
			</view>

			<!-- 快捷入口 -->
			<view class="wrap quick-wrap">
				<view class="quick-bar">
					<view class="qk" @tap="goPage('/pages/user/downloads')">
						<view class="qk-ico" :style="'background:linear-gradient(160deg,' + tc.primaryLight + '2e 0%,' + tc.primary + '14 100%);color:' + tc.primary + ';'">
							<text class="qk-glyph">↓</text>
						</view>
						<text class="qk-t">下载</text>
					</view>
					<view class="qk" @tap="goPage('/pages/user/favorites')">
						<view class="qk-ico" :style="'background:linear-gradient(160deg,' + tc.primaryLight + '2e 0%,' + tc.primary + '14 100%);color:' + tc.primary + ';'">
							<text class="qk-glyph">♡</text>
						</view>
						<text class="qk-t">收藏</text>
					</view>
					<view class="qk" @tap="goPage('/pages/points/index')">
						<view class="qk-ico" :style="'background:linear-gradient(160deg,' + tc.primaryLight + '2e 0%,' + tc.primary + '14 100%);color:' + tc.primary + ';'">
							<text class="qk-glyph">◎</text>
						</view>
						<text class="qk-t">签到</text>
					</view>
					<view class="qk" @tap="goPage('/pages/user/orders')">
						<view class="qk-ico" :style="'background:linear-gradient(160deg,' + tc.primaryLight + '2e 0%,' + tc.primary + '14 100%);color:' + tc.primary + ';'">
							<text class="qk-glyph">≡</text>
						</view>
						<text class="qk-t">订单</text>
					</view>
					<view class="qk" @tap="openThemePicker">
						<view class="qk-ico qk-ico-more" :style="'background:linear-gradient(160deg,' + tc.primaryLight + '2e 0%,' + tc.primary + '14 100%);color:' + tc.primary + ';'">
							<text class="qk-glyph qk-dots">•••</text>
						</view>
						<text class="qk-t">更多</text>
					</view>
				</view>
			</view>

			<!-- 数据卡（已登录） -->
			<view class="wrap stats-wrap" v-if="isLoggedIn">
				<view class="stats">
					<view class="st" @tap="goPage('/pages/user/downloads')">
						<view class="st-num-row">
							<text class="st-num">{{ stats.download_count || 0 }}</text>
							<text class="st-unit">次</text>
						</view>
						<text class="st-lab">下载</text>
					</view>
					<view class="st" @tap="goPage('/pages/user/favorites')">
						<view class="st-num-row">
							<text class="st-num">{{ stats.favorite_count || 0 }}</text>
							<text class="st-unit">个</text>
						</view>
						<text class="st-lab">收藏</text>
					</view>
					<view class="st" @tap="goPage('/pages/user/orders')">
						<view class="st-num-row">
							<text class="st-num">{{ stats.order_count || 0 }}</text>
							<text class="st-unit">单</text>
						</view>
						<text class="st-lab">订单</text>
					</view>
					<view class="st" @tap="goPage('/pages/points/index')">
						<view class="st-num-row">
							<text class="st-num">{{ stats.points || 0 }}</text>
							<text class="st-unit">分</text>
						</view>
						<text class="st-lab">积分</text>
					</view>
				</view>
			</view>

			<!-- 未登录：登录引导 -->
			<view class="wrap stats-wrap" v-if="!isLoggedIn">
				<view class="login-panel" :style="'background:linear-gradient(180deg, rgba(255,255,255,0.72) 0%, rgba(255,255,255,0.55) 38%, ' + tc.tintMedium + 'aa 100%);backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);'">
					<view class="login-panel-orb login-panel-orb-a" :style="'background:' + tc.primaryLight + '30;'"></view>
					<view class="login-panel-orb login-panel-orb-b"></view>
					<view class="login-panel-head">
						<view class="login-panel-bar" :style="'background:linear-gradient(180deg,' + tc.primaryLight + ',' + tc.primary + ');'"></view>
						<view class="login-panel-copy">
							<text class="login-panel-t">登录后体验完整功能</text>
							<text class="login-panel-s">同步下载、收藏与积分，换设备也不丢</text>
						</view>
					</view>
					<view class="login-perks">
						<view class="login-perk">
							<text class="login-perk-t" :style="'color:' + tc.primaryDark + ';'">免费下载</text>
							<text class="login-perk-s">海量资源</text>
						</view>
						<view class="login-perk">
							<text class="login-perk-t" :style="'color:' + tc.primaryDark + ';'">收藏资源</text>
							<text class="login-perk-s">随时回看</text>
						</view>
						<view class="login-perk">
							<text class="login-perk-t" :style="'color:' + tc.primaryDark + ';'">积分成长</text>
							<text class="login-perk-s">等级权益</text>
						</view>
					</view>
					<view
						class="login-btn"
						:style="'background:linear-gradient(180deg,' + tc.primaryLight + ' 0%,' + tc.primary + ' 100%);box-shadow:0 12rpx 28rpx ' + tc.primary + '40, inset 0 1rpx 0 rgba(255,255,255,0.22);'"
						@tap="quickLogin"
					>
						<text v-if="!loginLoading" class="login-btn-icon">微</text>
						<text class="login-btn-t">{{ loginLoading ? '登录中...' : '微信一键登录' }}</text>
					</view>
					<text class="login-panel-tip">登录即代表同意用户协议与隐私政策</text>
				</view>
			</view>

			<!-- VIP -->
			<view class="wrap" v-if="isLoggedIn">
				<view class="vip-card" :style="'background:' + tc.vipBannerBg + ';'" @tap="goVip">
					<view class="vip-l">
						<text class="vip-t">{{ userInfo.is_vip ? 'VIP 会员' : '开通 VIP 会员' }}</text>
						<text class="vip-s">全站资源免费下载 · 极速不限速</text>
					</view>
					<text class="vip-go">{{ userInfo.is_vip ? '续费' : '开通' }}</text>
				</view>
			</view>

			<!-- 功能 -->
			<view class="wrap">
				<text class="sec-label">功能</text>
				<view class="menu">
					<view class="mi" @tap="goPage('/pages/user/downloads')">
						<view class="mi-ico" :style="'background:' + tc.tintMedium + ';color:' + tc.primary + ';'">↓</view>
						<text class="mi-t">我的下载</text>
						<text class="mi-a">›</text>
					</view>
					<view class="mi" @tap="goPage('/pages/user/favorites')">
						<view class="mi-ico" :style="'background:' + tc.tintMedium + ';color:' + tc.primary + ';'">♡</view>
						<text class="mi-t">我的收藏</text>
						<text class="mi-a">›</text>
					</view>
					<view class="mi" @tap="goPage('/pages/user/orders')">
						<view class="mi-ico" :style="'background:' + tc.tintMedium + ';color:' + tc.primary + ';'">≡</view>
						<text class="mi-t">我的订单</text>
						<text class="mi-a">›</text>
					</view>
					<view class="mi" @tap="goPage('/pages/user/vip')">
						<view class="mi-ico" :style="'background:' + tc.tintMedium + ';color:' + tc.primary + ';'">V</view>
						<text class="mi-t">VIP 中心</text>
						<text class="mi-a">›</text>
					</view>
					<view class="mi" @tap="goPage('/pages/points/index')">
						<view class="mi-ico" :style="'background:' + tc.tintMedium + ';color:' + tc.primary + ';'">◎</view>
						<text class="mi-t">积分中心</text>
						<text class="mi-a">›</text>
					</view>
				</view>
			</view>

			<!-- 设置 -->
			<view class="wrap">
				<text class="sec-label">设置</text>
				<view class="menu">
					<view class="mi" v-if="feedbackEnabled" @tap="goPage('/pages/user/feedback')">
						<view class="mi-ico" :style="'background:' + tc.tintMedium + ';color:' + tc.primary + ';'">✎</view>
						<text class="mi-t">意见反馈</text>
						<text class="mi-a">›</text>
					</view>
					<view class="mi" @tap="showAbout">
						<view class="mi-ico" :style="'background:' + tc.tintMedium + ';color:' + tc.primary + ';'">i</view>
						<text class="mi-t">关于我们</text>
						<text class="mi-a">›</text>
					</view>
					<view class="mi" @tap="openThemePicker">
						<view class="mi-ico" :style="'background:' + tc.tintMedium + ';color:' + tc.primary + ';'">◐</view>
						<text class="mi-t">主题风格</text>
						<view class="dot" :style="'background:' + tc.primary + ';'"></view>
						<text class="mi-a">›</text>
					</view>
				</view>
			</view>

			<view class="wrap" v-if="isLoggedIn">
				<view class="logout" @tap="handleLogout">退出登录</view>
			</view>

			<view class="foot">资料库</view>
		</scroll-view>

		<view class="mask" v-if="showAuthPopup" @tap="showAuthPopup = false">
			<view class="popup" @tap.stop>
				<view class="drag"></view>
				<text class="pop-title">完善个人信息</text>
				<view class="field">
					<text class="label">头像</text>
					<button v-if="!avatarChosen" class="av-btn" open-type="chooseAvatar" @chooseavatar="onChooseAvatar">
						<image class="av-img" :src="loginAvatar || '/static/default-avatar.png'" mode="aspectFill"></image>
						<text class="arrow">›</text>
					</button>
					<view v-else class="av-btn" @tap="resetAvatar">
						<image class="av-img" :src="loginAvatar || '/static/default-avatar.png'" mode="aspectFill"></image>
						<text class="arrow">›</text>
					</view>
				</view>
				<view class="field">
					<text class="label">昵称</text>
					<input type="nickname" class="input" v-model="loginNickname" placeholder="请输入昵称" placeholder-class="ph" maxlength="16" />
				</view>
				<view class="btn-main pop-btn" @tap="submitLogin">
					<text>{{ loginLoading ? '登录中...' : '保存并登录' }}</text>
				</view>
			</view>
		</view>

		<view class="mask" v-if="showEditPopup" @tap="showEditPopup = false">
			<view class="popup" @tap.stop>
				<view class="drag"></view>
				<text class="pop-title">编辑个人资料</text>
				<view class="field">
					<text class="label">头像</text>
					<button class="av-btn" open-type="chooseAvatar" @chooseavatar="onEditAvatar">
						<image class="av-img" :src="editAvatar || avatarSrc" mode="aspectFill"></image>
						<text class="arrow">›</text>
					</button>
				</view>
				<view class="field">
					<text class="label">昵称</text>
					<input type="nickname" class="input" v-model="editNickname" placeholder="请输入昵称" placeholder-class="ph" maxlength="16" />
				</view>
				<view class="btn-main pop-btn" @tap="saveProfile">
					<text>{{ saving ? '保存中...' : '保存' }}</text>
				</view>
			</view>
		</view>

		<view class="mask" v-if="showThemePopup" @tap="showThemePopup = false">
			<view class="popup" @tap.stop>
				<view class="drag"></view>
				<text class="pop-title">选择主题风格</text>
				<view class="theme-grid">
					<view
						class="th"
						v-for="item in themeList"
						:key="item.key"
						:class="{ active: activeTheme === item.key }"
						:style="activeTheme === item.key ? 'border-color:' + item.primary + ';background:' + item.tintLight + ';' : ''"
						@tap="switchTheme(item.key)"
					>
						<view class="th-ball" :style="'background:' + item.primary + ';'">
							<text v-if="activeTheme === item.key">✓</text>
						</view>
						<text class="th-name">{{ item.name }}</text>
					</view>
				</view>
			</view>
		</view>

		<lingxi-tabbar :current="2"></lingxi-tabbar>
	</view>
</template>

<script>
import http from '@/utils/http.js';
import theme from '@/common/theme.js';
import LingxiTabbar from '@/components/lingxi-tabbar/lingxi-tabbar.vue';

export default {
	components: { LingxiTabbar },
	data() {
		return {
			isLoggedIn: false,
			userInfo: {},
			stats: { download_count: 0, favorite_count: 0, order_count: 0, points: 0 },
			loginAvatar: '',
			loginNickname: '',
			loginLoading: false,
			showAuthPopup: false,
			avatarChosen: false,
			loginCode: '',
			showEditPopup: false,
			editNickname: '',
			editAvatar: '',
			saving: false,
			feedbackEnabled: true,
			showThemePopup: false,
			themeList: theme.THEME_LIST.map(k => ({ key: k, ...theme.THEMES[k] }))
		};
	},
	onShow() {
		this.checkLogin();
	},
	computed: {
		avatarSrc() {
			const url = this.userInfo.avatar_url;
			if (!url || url.indexOf('default_avatar') !== -1) return '/static/default-avatar.png';
			if (url.startsWith('/')) return http.getBaseUrl() + url;
			return url;
		},
		maskName() {
			const n = String(this.userInfo.nickname || '用户');
			if (/^1\d{10}$/.test(n)) return n.slice(0, 3) + '*******' + n.slice(7);
			if (n.length > 8) return n.slice(0, 8) + '**';
			return n;
		},
		hiTagline() {
			if (this.userInfo.is_vip) return '尊享会员权益已生效';
			return this.userInfo.id ? 'ID ' + this.userInfo.id + ' · 资料库会员' : '资料库会员';
		},
		levelUpgradeText() {
			if (this.userInfo.is_vip && this.userInfo.vip_expire_at) {
				return '有效期至 ' + this.userInfo.vip_expire_at.slice(0, 10);
			}
			const pts = Number(this.stats.points || 0);
			if (pts > 0) return '还差 ' + Math.max(1, 100 - (pts % 100)) + ' 成长值可享更多权益';
			return '还差一步升级，开通 VIP 全站免费下载';
		},
		vipProgress() {
			if (this.userInfo.is_vip) return 100;
			return Math.min(92, Math.max(18, Number(this.stats.points || 0) % 40 + 28));
		}
	},
	methods: {
		onAvatarError() {
			this.userInfo.avatar_url = '';
		},
		checkLogin() {
			const token = uni.getStorageSync('token');
			if (token) {
				this.isLoggedIn = true;
				this.loadUserInfo();
				this.loadStats();
			} else {
				this.isLoggedIn = false;
				this.userInfo = {};
				this.stats = { download_count: 0, favorite_count: 0, points: 0, order_count: 0 };
			}
		},
		async loadUserInfo() {
			try {
				const res = await http.get('/api/user/info', {}, { silent: true });
				if (res.code === 0) this.userInfo = res.data || {};
			} catch (e) {}
		},
		async loadStats() {
			try {
				const res = await http.get('/api/user/stats', {}, { silent: true });
				if (res.code === 0) {
					this.stats = res.data || {};
					if (res.data && res.data.feedback_enabled !== undefined) {
						this.feedbackEnabled = !!res.data.feedback_enabled;
					}
				}
			} catch (e) {}
		},
		async quickLogin() {
			if (this.loginLoading) return;
			this.loginLoading = true;
			try {
				const savedToken = uni.getStorageSync('token') || '';
				if (savedToken) {
					const checkRes = await http.get('/api/auth/silent-check');
					if (checkRes.code === 0 && checkRes.data && checkRes.data.logged_in && checkRes.data.user) {
						const user = checkRes.data.user;
						uni.setStorageSync('userInfo', JSON.stringify(user));
						if (user.openid) uni.setStorageSync('openid', user.openid);
						this.isLoggedIn = true;
						this.userInfo = user;
						this.loadStats();
						uni.showToast({ title: '欢迎回来', icon: 'success', duration: 1000 });
						this.loginLoading = false;
						return;
					}
					uni.removeStorageSync('token');
					uni.removeStorageSync('userInfo');
				}
				const loginRes = await new Promise((resolve, reject) => {
					uni.login({ provider: 'weixin', success: resolve, fail: reject });
				});
				const res = await http.post('/api/auth/wx-login', { code: loginRes.code, openid: '' });
				if (res.code === 0 && res.data && res.data.user) {
					const user = res.data.user;
					if (user.openid) uni.setStorageSync('openid', user.openid);
					if (!res.data.is_new_user && user.nickname && user.nickname !== '微信用户') {
						this.handleLoginSuccess(res.data);
						this.loginLoading = false;
						return;
					}
				}
				this.loginCode = loginRes.code;
				this.showAuthPopup = true;
			} catch (e) {
				console.error('登录失败', e);
				try {
					const loginRes = await new Promise((resolve, reject) => {
						uni.login({ provider: 'weixin', success: resolve, fail: reject });
					});
					this.loginCode = loginRes.code;
				} catch (e2) {}
				this.showAuthPopup = true;
			} finally {
				this.loginLoading = false;
			}
		},
		onChooseAvatar(e) {
			const url = e.detail.avatarUrl;
			if (url) {
				this.loginAvatar = url;
				this.avatarChosen = true;
			}
		},
		resetAvatar() {
			this.avatarChosen = false;
		},
		async submitLogin() {
			if (this.loginLoading) return;
			this.loginLoading = true;
			try {
				const code = this.loginCode;
				if (!code) {
					uni.showToast({ title: '请重新点击登录', icon: 'none' });
					return;
				}
				const nickname = this.loginNickname || '微信用户';
				const savedOpenid = uni.getStorageSync('openid') || '';
				if (this.loginAvatar) {
					const uploadRes = await new Promise((resolve, reject) => {
						uni.uploadFile({
							url: http.getBaseUrl() + '/api/auth/fill-login',
							filePath: this.loginAvatar,
							name: 'avatar',
							formData: { code, nickname, openid: savedOpenid },
							success: res => {
								try {
									resolve(JSON.parse(res.data));
								} catch (e) {
									reject(new Error('解析响应失败'));
								}
							},
							fail: reject
						});
					});
					if (uploadRes.code === 0) this.handleLoginSuccess(uploadRes.data);
					else uni.showToast({ title: uploadRes.message || '登录失败', icon: 'none' });
				} else {
					const res = await http.post('/api/auth/fill-login', { code, nickname, openid: savedOpenid });
					if (res.code === 0) this.handleLoginSuccess(res.data);
					else uni.showToast({ title: res.message || '登录失败', icon: 'none' });
				}
			} catch (e) {
				console.error('登录失败', e);
				uni.showToast({ title: '登录失败', icon: 'none' });
			} finally {
				this.loginLoading = false;
			}
		},
		handleLoginSuccess(data) {
			uni.setStorageSync('token', data.token);
			if (data.user) {
				uni.setStorageSync('userInfo', JSON.stringify(data.user));
				if (data.user.openid) uni.setStorageSync('openid', data.user.openid);
			}
			this.loginAvatar = '';
			this.loginNickname = '';
			this.loginCode = '';
			this.showAuthPopup = false;
			this.checkLogin();
			uni.showToast({ title: '登录成功', icon: 'success' });
		},
		goPage(url) {
			if (!this.isLoggedIn) {
				uni.showModal({
					title: '提示',
					content: '请先登录',
					confirmText: '去登录',
					success: res => {
						if (res.confirm) uni.pageScrollTo({ scrollTop: 0, duration: 300 });
					}
				});
				return;
			}
			uni.navigateTo({ url });
		},
		goVip() {
			uni.navigateTo({ url: '/pages/user/vip' });
		},
		async showAbout() {
			uni.navigateTo({ url: '/pages/about/index' });
		},
		openThemePicker() {
			this.showThemePopup = true;
		},
		switchTheme(name) {
			this.activeTheme = name;
			uni.setStorageSync('theme', name);
			theme.applyTheme(name);
			uni.$emit('__themeChanged', name);
			this.showThemePopup = false;
			const t = theme.getTheme();
			try {
				uni.setNavigationBarColor({ frontColor: '#ffffff', backgroundColor: t.navBg });
				uni.setBackgroundColor({ backgroundColor: t.pageBg, backgroundColorTop: t.pageBg, backgroundColorBottom: t.pageBg });
			} catch (e) {}
			uni.showToast({ title: '已切换', icon: 'success', duration: 800 });
		},
		openEditProfile() {
			this.editNickname = this.userInfo.nickname || '';
			this.editAvatar = '';
			this.showEditPopup = true;
		},
		onEditAvatar(e) {
			const url = e.detail.avatarUrl;
			if (url) this.editAvatar = url;
		},
		async saveProfile() {
			if (this.saving) return;
			if (!this.editNickname.trim()) {
				uni.showToast({ title: '请输入昵称', icon: 'none' });
				return;
			}
			this.saving = true;
			try {
				let res;
				if (this.editAvatar) {
					res = await new Promise((resolve, reject) => {
						const token = uni.getStorageSync('token') || '';
						uni.uploadFile({
							url: http.getBaseUrl() + '/api/auth/update-profile',
							filePath: this.editAvatar,
							name: 'avatar',
							formData: { nickname: this.editNickname.trim() },
							header: { Authorization: 'Bearer ' + token },
							success: r => {
								try {
									resolve(JSON.parse(r.data));
								} catch (e) {
									reject(e);
								}
							},
							fail: reject
						});
					});
				} else {
					res = await http.post('/api/auth/update-profile', { nickname: this.editNickname.trim() });
				}
				if (res.code === 0) {
					uni.showToast({ title: '保存成功', icon: 'success' });
					this.showEditPopup = false;
					this.loadUserInfo();
				} else {
					uni.showToast({ title: res.message || '保存失败', icon: 'none' });
				}
			} catch (e) {
				uni.showToast({ title: '保存失败', icon: 'none' });
			} finally {
				this.saving = false;
			}
		},
		handleLogout() {
			uni.showModal({
				title: '确认退出',
				content: '确定要退出登录吗？',
				success: res => {
					if (res.confirm) {
						uni.removeStorageSync('token');
						uni.removeStorageSync('userInfo');
						this.isLoggedIn = false;
						this.userInfo = {};
						this.stats = { download_count: 0, favorite_count: 0, points: 0, order_count: 0 };
						uni.showToast({ title: '已退出', icon: 'none' });
					}
				}
			});
		}
	},
	onShareAppMessage() {
		return { title: '海量资源免费下载', path: '/pages/index/index' };
	}
};
</script>

<style scoped>
.page {
	min-height: 100vh;
	background: transparent;
	padding-bottom: calc(140rpx + env(safe-area-inset-bottom));
}

.header-bg {
	position: absolute;
	left: 0;
	right: 0;
	top: 0;
	height: 820rpx;
	z-index: 0;
	pointer-events: none;
	overflow: hidden;
}
.soft-orb {
	position: absolute;
	border-radius: 50%;
	pointer-events: none;
}
.soft-orb.o1 {
	width: 260rpx;
	height: 260rpx;
	right: -60rpx;
	top: 140rpx;
	opacity: 0.28;
	filter: blur(6rpx);
}
.soft-orb.o2 {
	width: 140rpx;
	height: 140rpx;
	left: -36rpx;
	top: 320rpx;
	background: rgba(255, 255, 255, 0.22);
}
.soft-orb.o3 {
	width: 80rpx;
	height: 80rpx;
	right: 140rpx;
	top: 460rpx;
	background: rgba(255, 255, 255, 0.18);
}
.header-fade {
	position: absolute;
	left: 0;
	right: 0;
	bottom: 0;
	height: 260rpx;
	pointer-events: none;
}

.main-scroll {
	height: 100vh;
	position: relative;
	z-index: 1;
}

.user-nav {
	position: relative;
	z-index: 3;
}
.status-bar {
	height: 88rpx;
	height: calc(88rpx + constant(safe-area-inset-top));
	height: calc(88rpx + env(safe-area-inset-top));
}
.nav-row {
	display: flex;
	align-items: center;
	justify-content: space-between;
	height: 88rpx;
	padding: 0 28rpx;
}
.nav-spacer {
	width: 140rpx;
}
.nav-title {
	flex: 1;
	text-align: center;
	font-size: 34rpx;
	font-weight: 700;
	color: #1a1a1a;
	letter-spacing: 4rpx;
}
.nav-right {
	width: 140rpx;
	display: flex;
	align-items: center;
	justify-content: flex-end;
	gap: 14rpx;
}
.nav-icon-btn {
	width: 68rpx;
	height: 68rpx;
	border-radius: 50%;
	background: rgba(255, 255, 255, 0.72);
	display: flex;
	align-items: center;
	justify-content: center;
	border: 1rpx solid rgba(255, 255, 255, 0.85);
	box-shadow: 0 6rpx 18rpx rgba(70, 100, 30, 0.08);
	backdrop-filter: blur(8rpx);
}
.nav-icon-btn:active {
	transform: scale(0.94);
}
.nav-icon {
	font-size: 28rpx;
	color: #2f3a24;
	font-weight: 700;
	line-height: 1;
}
.nav-dots {
	font-size: 20rpx;
	letter-spacing: 2rpx;
	transform: translateY(-6rpx);
}

.hero {
	padding: 12rpx 40rpx 0;
	position: relative;
	z-index: 2;
}
.wrap.stats-wrap {
	margin-top: 8rpx;
}
.wrap.quick-wrap {
	margin-top: 36rpx;
	margin-bottom: 16rpx;
}
.hi-row {
	display: flex;
	align-items: center;
}
.avatar-ring {
	width: 108rpx;
	height: 108rpx;
	border-radius: 50%;
	padding: 5rpx;
	background: linear-gradient(145deg, rgba(255, 255, 255, 0.98), rgba(255, 255, 255, 0.4));
	box-shadow:
		0 8rpx 24rpx rgba(70, 100, 30, 0.1),
		0 2rpx 6rpx rgba(70, 100, 30, 0.04);
	flex-shrink: 0;
	position: relative;
}
.avatar {
	width: 100%;
	height: 100%;
	border-radius: 50%;
	background: #fff;
	overflow: hidden;
	display: block;
}
.avatar-badge {
	position: absolute;
	right: -2rpx;
	bottom: 2rpx;
	width: 32rpx;
	height: 32rpx;
	border-radius: 50%;
	background: linear-gradient(135deg, #ffd54f, #ffb300);
	border: 3rpx solid #fff;
	display: flex;
	align-items: center;
	justify-content: center;
	box-shadow: 0 4rpx 10rpx rgba(180, 120, 0, 0.25);
}
.avatar-badge text {
	font-size: 16rpx;
	font-weight: 800;
	color: #5c430c;
	line-height: 1;
}
.hi-info {
	flex: 1;
	min-width: 0;
	margin-left: 22rpx;
}
.hi-title {
	display: block;
	font-size: 36rpx;
	font-weight: 800;
	color: #161a12;
	letter-spacing: 0.5rpx;
	max-width: 340rpx;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
}
.hi-sub {
	display: block;
	margin-top: 8rpx;
	font-size: 20rpx;
	color: rgba(55, 75, 35, 0.42);
	font-weight: 500;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
}
.edit-btn {
	padding: 14rpx 28rpx;
	border-radius: 999rpx;
	background: rgba(255, 255, 255, 0.94);
	font-size: 22rpx;
	font-weight: 700;
	color: #558b2f;
	flex-shrink: 0;
	box-shadow: 0 6rpx 18rpx rgba(70, 100, 30, 0.08);
	letter-spacing: 1rpx;
	border: 1rpx solid rgba(255, 255, 255, 0.9);
}
.edit-btn:active {
	transform: scale(0.96);
}
.edit-btn-login {
	background: linear-gradient(180deg, #2b2b2b 0%, #1a1a1a 100%);
	color: #fff;
	border-color: transparent;
	box-shadow: 0 8rpx 20rpx rgba(0, 0, 0, 0.12);
}

.level-stage {
	margin-top: 28rpx;
	display: flex;
	align-items: flex-end;
	justify-content: space-between;
	min-height: 320rpx;
	position: relative;
}
.level-main {
	flex: 1;
	min-width: 0;
	padding-right: 8rpx;
	padding-bottom: 56rpx;
	position: relative;
	z-index: 2;
}
.level-row {
	display: flex;
	align-items: center;
	gap: 14rpx;
}
.level-name {
	font-size: 68rpx;
	font-weight: 800;
	color: #11150c;
	letter-spacing: 2rpx;
	line-height: 1.05;
	text-shadow: 0 2rpx 0 rgba(255, 255, 255, 0.28);
}
.info-dot {
	width: 32rpx;
	height: 32rpx;
	border-radius: 50%;
	border: 2rpx solid rgba(55, 75, 35, 0.3);
	display: flex;
	align-items: center;
	justify-content: center;
	font-size: 18rpx;
	font-weight: 700;
	color: rgba(55, 75, 35, 0.5);
	line-height: 1;
	margin-top: 14rpx;
	flex-shrink: 0;
	background: rgba(255, 255, 255, 0.45);
}
.info-dot:active {
	transform: scale(0.92);
}
.level-sub {
	display: block;
	margin-top: 18rpx;
	font-size: 22rpx;
	color: rgba(55, 75, 35, 0.48);
	font-weight: 500;
	letter-spacing: 0.4rpx;
	max-width: 360rpx;
	line-height: 1.5;
}
.level-progress {
	margin-top: 20rpx;
	width: 300rpx;
	height: 12rpx;
	border-radius: 999rpx;
	background: rgba(255, 255, 255, 0.6);
	overflow: hidden;
	box-shadow: inset 0 1rpx 3rpx rgba(70, 100, 30, 0.08);
}
.level-progress-bar {
	height: 100%;
	border-radius: 999rpx;
	box-shadow: 0 0 12rpx rgba(124, 179, 66, 0.35);
	transition: width 0.4s ease;
}
.level-cta {
	display: inline-flex;
	align-items: center;
	margin-top: 22rpx;
	padding: 12rpx 24rpx;
	border-radius: 999rpx;
	background: rgba(255, 255, 255, 0.78);
	border: 1rpx solid rgba(255, 255, 255, 0.95);
	font-size: 20rpx;
	font-weight: 700;
	color: #3d4a2e;
	box-shadow: 0 4rpx 14rpx rgba(70, 100, 30, 0.07);
}
.level-cta:active {
	transform: scale(0.96);
}
.cta-arrow {
	margin-left: 6rpx;
	font-size: 22rpx;
	opacity: 0.7;
}

/* ===== 人物插画 ===== */
.hero-character {
	position: relative;
	width: 300rpx;
	height: 340rpx;
	flex-shrink: 0;
	margin-right: -8rpx;
	margin-bottom: -8rpx;
	z-index: 1;
	display: flex;
	align-items: flex-end;
	justify-content: center;
}
.hero-illu {
	width: 300rpx;
	height: 340rpx;
	display: block;
}

.wrap {
	padding: 0 28rpx;
	margin-bottom: 18rpx;
	position: relative;
	z-index: 2;
}
.sec-label {
	display: block;
	margin: 20rpx 8rpx 12rpx;
	font-size: 22rpx;
	font-weight: 600;
	color: #a0ab92;
	letter-spacing: 3rpx;
}

/* ===== 快捷入口 ===== */
.quick-bar {
	display: flex;
	align-items: flex-start;
	justify-content: space-between;
	background: #ffffff;
	border-radius: 36rpx;
	padding: 28rpx 12rpx 22rpx;
	box-shadow:
		0 2rpx 8rpx rgba(70, 100, 30, 0.03),
		0 14rpx 36rpx rgba(70, 100, 30, 0.08);
}
.qk {
	flex: 1;
	display: flex;
	flex-direction: column;
	align-items: center;
	gap: 12rpx;
	min-width: 0;
}
.qk:active {
	transform: scale(0.94);
}
.qk-ico {
	width: 84rpx;
	height: 84rpx;
	border-radius: 50%;
	display: flex;
	align-items: center;
	justify-content: center;
	box-shadow: inset 0 -2rpx 6rpx rgba(255, 255, 255, 0.35);
}
.qk-ico-more {
	opacity: 0.92;
}
.qk-glyph {
	font-size: 34rpx;
	font-weight: 700;
	line-height: 1;
}
.qk-dots {
	font-size: 22rpx;
	letter-spacing: 2rpx;
	transform: translateY(-4rpx);
}
.qk-t {
	font-size: 20rpx;
	color: #5a6650;
	font-weight: 600;
	letter-spacing: 0.5rpx;
	line-height: 1.2;
}

.stats {
	display: flex;
	background: #fff;
	border-radius: 36rpx;
	padding: 38rpx 8rpx 34rpx;
	box-shadow:
		0 2rpx 8rpx rgba(70, 100, 30, 0.03),
		0 16rpx 40rpx rgba(70, 100, 30, 0.08);
}
.st {
	flex: 1;
	display: flex;
	flex-direction: column;
	align-items: center;
	gap: 12rpx;
	position: relative;
}
.st:active {
	transform: scale(0.96);
}
.st + .st::before {
	content: '';
	position: absolute;
	left: 0;
	top: 16%;
	height: 68%;
	width: 1rpx;
	background: linear-gradient(
		180deg,
		rgba(80, 110, 40, 0) 0%,
		rgba(80, 110, 40, 0.1) 50%,
		rgba(80, 110, 40, 0) 100%
	);
}
.st-num-row {
	display: flex;
	align-items: baseline;
	gap: 2rpx;
}
.st-num {
	font-size: 48rpx;
	font-weight: 800;
	color: #1a1a1a;
	line-height: 1;
	font-variant-numeric: tabular-nums;
	letter-spacing: -1.5rpx;
}
.st-unit {
	font-size: 18rpx;
	font-weight: 600;
	color: rgba(26, 26, 26, 0.32);
	margin-left: 2rpx;
}
.st-lab {
	font-size: 20rpx;
	color: #8f9c82;
	font-weight: 600;
	letter-spacing: 2rpx;
}

.login-panel {
	border-radius: 40rpx;
	padding: 44rpx 20rpx 30rpx;
	margin-top: 48rpx;
	box-shadow:
		0 2rpx 8rpx rgba(70, 100, 30, 0.04),
		0 18rpx 44rpx rgba(70, 100, 30, 0.1);
	position: relative;
	overflow: hidden;
	border: 1rpx solid rgba(255, 255, 255, 0.78);
}
.login-panel-orb {
	position: absolute;
	border-radius: 50%;
	pointer-events: none;
}
.login-panel-orb-a {
	width: 200rpx;
	height: 200rpx;
	right: -56rpx;
	top: -64rpx;
	filter: blur(2rpx);
}
.login-panel-orb-b {
	width: 110rpx;
	height: 110rpx;
	left: -32rpx;
	bottom: 48rpx;
	background: rgba(255, 255, 255, 0.4);
}
.login-panel-head {
	position: relative;
	z-index: 1;
	display: flex;
	align-items: flex-start;
	gap: 18rpx;
	padding: 0 8rpx;
}
.login-panel-bar {
	width: 8rpx;
	height: 68rpx;
	border-radius: 999rpx;
	flex-shrink: 0;
	margin-top: 4rpx;
	box-shadow: 0 4rpx 10rpx rgba(70, 100, 30, 0.12);
}
.login-panel-copy {
	flex: 1;
	min-width: 0;
}
.login-panel-t {
	display: block;
	font-size: 36rpx;
	font-weight: 800;
	color: #141810;
	letter-spacing: 0.5rpx;
	line-height: 1.25;
}
.login-panel-s {
	display: block;
	margin-top: 12rpx;
	font-size: 22rpx;
	color: rgba(55, 75, 35, 0.46);
	font-weight: 500;
	line-height: 1.5;
}
.login-perks {
	display: flex;
	margin-top: 34rpx;
	margin-bottom: 36rpx;
	position: relative;
	z-index: 1;
	background: rgba(255, 255, 255, 0.48);
	border-radius: 28rpx;
	border: 1rpx solid rgba(255, 255, 255, 0.7);
	padding: 22rpx 8rpx;
	backdrop-filter: blur(8rpx);
	-webkit-backdrop-filter: blur(8rpx);
}
.login-perk {
	flex: 1;
	display: flex;
	flex-direction: column;
	align-items: center;
	gap: 8rpx;
	position: relative;
}
.login-perk + .login-perk::before {
	content: '';
	position: absolute;
	left: 0;
	top: 18%;
	height: 64%;
	width: 1rpx;
	background: linear-gradient(
		180deg,
		rgba(80, 110, 40, 0) 0%,
		rgba(80, 110, 40, 0.12) 50%,
		rgba(80, 110, 40, 0) 100%
	);
}
.login-perk-t {
	font-size: 24rpx;
	font-weight: 800;
	letter-spacing: 1rpx;
}
.login-perk-s {
	font-size: 18rpx;
	color: rgba(55, 75, 35, 0.4);
	font-weight: 500;
	letter-spacing: 0.5rpx;
}
.login-btn {
	position: relative;
	z-index: 1;
	margin: 0 -4rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	gap: 12rpx;
	width: calc(100% + 8rpx);
	color: #fff;
	text-align: center;
	padding: 32rpx 0;
	border-radius: 999rpx;
}
.login-btn:active {
	opacity: 0.92;
	transform: scale(0.98);
}
.login-btn-icon {
	width: 40rpx;
	height: 40rpx;
	border-radius: 50%;
	background: rgba(255, 255, 255, 0.24);
	font-size: 20rpx;
	font-weight: 800;
	line-height: 40rpx;
	text-align: center;
	flex-shrink: 0;
}
.login-btn-t {
	font-size: 28rpx;
	font-weight: 700;
	letter-spacing: 3rpx;
}
.login-panel-tip {
	display: block;
	position: relative;
	z-index: 1;
	margin-top: 22rpx;
	text-align: center;
	font-size: 18rpx;
	color: rgba(55, 75, 35, 0.34);
	font-weight: 500;
	letter-spacing: 0.5rpx;
}
.btn-main {
	width: 100%;
	background: linear-gradient(180deg, #2b2b2b 0%, #1a1a1a 100%);
	color: #fff;
	text-align: center;
	padding: 30rpx 0;
	border-radius: 999rpx;
	font-size: 28rpx;
	font-weight: 700;
	letter-spacing: 3rpx;
	box-shadow: 0 10rpx 24rpx rgba(0, 0, 0, 0.14);
	border: none;
}
.btn-main:active {
	opacity: 0.9;
	transform: scale(0.98);
}

.vip-card {
	border-radius: 36rpx;
	padding: 30rpx 28rpx;
	display: flex;
	align-items: center;
	justify-content: space-between;
	box-shadow:
		0 2rpx 8rpx rgba(160, 120, 20, 0.04),
		0 12rpx 28rpx rgba(160, 120, 20, 0.09);
	border: 1rpx solid rgba(255, 255, 255, 0.55);
	position: relative;
	overflow: hidden;
}
.vip-card::after {
	content: '';
	position: absolute;
	right: -24rpx;
	top: -32rpx;
	width: 120rpx;
	height: 120rpx;
	border-radius: 50%;
	background: rgba(255, 255, 255, 0.32);
	pointer-events: none;
}
.vip-card::before {
	content: '';
	position: absolute;
	right: 70rpx;
	bottom: -40rpx;
	width: 80rpx;
	height: 80rpx;
	border-radius: 50%;
	background: rgba(255, 255, 255, 0.18);
	pointer-events: none;
}
.vip-l {
	flex: 1;
	min-width: 0;
	position: relative;
	z-index: 1;
}
.vip-t {
	display: block;
	font-size: 28rpx;
	font-weight: 800;
	color: #5c430c;
}
.vip-s {
	display: block;
	margin-top: 8rpx;
	font-size: 21rpx;
	color: #8a6a12;
	font-weight: 500;
}
.vip-go {
	position: relative;
	z-index: 1;
	flex-shrink: 0;
	padding: 14rpx 32rpx;
	border-radius: 999rpx;
	background: linear-gradient(180deg, #2b2b2b 0%, #1a1a1a 100%);
	color: #fff;
	font-size: 22rpx;
	font-weight: 700;
}

.menu {
	background: #fff;
	border-radius: 36rpx;
	overflow: hidden;
	box-shadow:
		0 2rpx 8rpx rgba(70, 100, 30, 0.04),
		0 14rpx 40rpx rgba(70, 100, 30, 0.08);
}
.mi {
	display: flex;
	align-items: center;
	padding: 32rpx 28rpx;
}
.mi:active {
	background: rgba(124, 179, 66, 0.05);
}
.mi + .mi {
	border-top: 1rpx solid rgba(80, 110, 40, 0.05);
}
.mi-ico {
	width: 64rpx;
	height: 64rpx;
	border-radius: 20rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	font-size: 28rpx;
	font-weight: 700;
	line-height: 1;
	flex-shrink: 0;
}
.mi-t {
	flex: 1;
	margin-left: 22rpx;
	font-size: 28rpx;
	color: #1a1a1a;
	font-weight: 500;
}
.mi-a {
	font-size: 34rpx;
	color: #c5cdb5;
	line-height: 1;
	font-weight: 300;
}
.dot {
	width: 20rpx;
	height: 20rpx;
	border-radius: 50%;
	margin-right: 10rpx;
}

.logout {
	padding: 28rpx;
	text-align: center;
	background: #fff;
	border-radius: 999rpx;
	font-size: 28rpx;
	font-weight: 600;
	color: #c45c5c;
	box-shadow:
		0 2rpx 6rpx rgba(70, 100, 30, 0.03),
		0 12rpx 36rpx rgba(70, 100, 30, 0.07);
	margin-top: 8rpx;
}
.logout:active {
	background: #fff5f5;
}
.foot {
	padding: 32rpx 0 16rpx;
	text-align: center;
	font-size: 20rpx;
	color: #c0cbb4;
	letter-spacing: 4rpx;
	font-weight: 500;
}

.mask {
	position: fixed;
	left: 0;
	right: 0;
	top: 0;
	bottom: 0;
	background: rgba(15, 23, 10, 0.45);
	z-index: 9999;
	display: flex;
	align-items: flex-end;
}
.popup {
	width: 100%;
	background: #fff;
	border-radius: 40rpx 40rpx 0 0;
	padding: 20rpx 36rpx 60rpx;
	padding-bottom: calc(60rpx + env(safe-area-inset-bottom));
	animation: slideUp 0.28s cubic-bezier(0.16, 1, 0.3, 1);
}
@keyframes slideUp {
	from {
		transform: translateY(100%);
	}
	to {
		transform: translateY(0);
	}
}
.drag {
	width: 48rpx;
	height: 6rpx;
	background: #d5dcc8;
	border-radius: 3rpx;
	margin: 0 auto 28rpx;
}
.pop-title {
	display: block;
	text-align: center;
	font-size: 32rpx;
	font-weight: 800;
	color: #1a1a1a;
	margin-bottom: 24rpx;
}
.field {
	display: flex;
	align-items: center;
	justify-content: space-between;
	padding: 24rpx 0;
	border-bottom: 1rpx solid rgba(80, 110, 40, 0.06);
}
.label {
	font-size: 28rpx;
	color: #1a1a1a;
	font-weight: 500;
}
.av-btn {
	display: flex;
	align-items: center;
	background: transparent;
	border: none;
	padding: 0;
	margin: 0;
	line-height: normal;
}
.av-btn::after {
	border: none;
}
.av-img {
	width: 96rpx;
	height: 96rpx;
	border-radius: 50%;
	background: #f3f5f0;
}
.arrow {
	font-size: 34rpx;
	color: #c5cdb5;
	margin-left: 12rpx;
}
.input {
	flex: 1;
	text-align: right;
	font-size: 30rpx;
	color: #1a1a1a;
	height: 48rpx;
}
.ph {
	color: #c5cdb5;
}
.pop-btn {
	margin-top: 48rpx;
}

.theme-grid {
	display: flex;
	flex-wrap: wrap;
	gap: 16rpx;
	padding: 8rpx 0 16rpx;
}
.th {
	width: calc((100% - 32rpx) / 3);
	display: flex;
	flex-direction: column;
	align-items: center;
	gap: 14rpx;
	padding: 28rpx 0;
	border-radius: 28rpx;
	border: 2rpx solid transparent;
	background: #f7f8f4;
}
.th.active {
	box-shadow: 0 6rpx 20rpx rgba(124, 179, 66, 0.12);
}
.th:active {
	transform: scale(0.96);
}
.th-ball {
	width: 72rpx;
	height: 72rpx;
	border-radius: 50%;
	display: flex;
	align-items: center;
	justify-content: center;
	color: #fff;
	font-size: 28rpx;
	font-weight: 800;
}
.th-name {
	font-size: 22rpx;
	font-weight: 600;
	color: #1a1a1a;
}
</style>
