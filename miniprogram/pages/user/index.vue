<template>
	<view class="page">
		<scroll-view scroll-y class="main-scroll">
			<!-- 用户信息卡 -->
			<view class="user-card" v-if="isLoggedIn" :style="'background:' + tc.headerGradientLong + ';'">
				<view class="user-bg-decor"></view>
				<view class="user-header">
					<view class="avatar-wrap">
						<image class="user-avatar" :src="avatarSrc" mode="aspectFill" @error="onAvatarError"></image>
						<view class="vip-ring" v-if="userInfo.is_vip"></view>
					</view>
					<view class="user-meta">
						<view class="name-row">
							<text class="user-name">{{ userInfo.nickname || '用户' }}</text>
							<view class="vip-badge" v-if="userInfo.is_vip">
								<text class="vip-icon">👑</text>
								<text class="vip-text">VIP</text>
							</view>
						</view>
						<text class="user-id">ID: {{ userInfo.id }}</text>
					</view>
					<view class="edit-profile-btn" :style="'border:1rpx solid rgba(255,255,255,0.4);'" @tap="openEditProfile">
						<text>编辑 ›</text>
					</view>
				</view>
			</view>

			<!-- 未登录 -->
			<view class="login-section" v-if="!isLoggedIn">
				<view class="login-header-bg" :style="'background:' + tc.headerGradientLong + ';'">
					<view class="lh-inner">
						<view class="lh-ring" :style="'border-color:' + tc.primary + ';'">
							<view class="lh-avatar-box" :style="'background:' + tc.primary + ';'">
								<text class="lh-avatar-icon">👤</text>
							</view>
						</view>
						<view class="lh-info">
							<text class="lh-name">未登录</text>
							<text class="lh-tip" :style="'color:' + tc.primary + ';'">登录后享受更多功能</text>
						</view>
					</view>
				</view>
				<view class="login-card">
					<view class="lc-row">
						<view class="lc-feat">
							<view class="lf-icon-wrap g1"><text class="lf-ico">📥</text></view>
							<text class="lf-name" :style="'color:' + tc.primary + ';'">免费下载</text>
						</view>
						<view class="lc-feat">
							<view class="lf-icon-wrap g2"><text class="lf-ico">⭐</text></view>
							<text class="lf-name" :style="'color:' + tc.primary + ';'">收藏资源</text>
						</view>
						<view class="lc-feat">
							<view class="lf-icon-wrap g3"><text class="lf-ico">📦</text></view>
							<text class="lf-name" :style="'color:' + tc.primary + ';'">下载记录</text>
						</view>
					</view>
					<view class="lc-btn-area">
						<view class="lc-btn" @tap="quickLogin" :style="'background:' + tc.btnGradient + ';'">
							<text class="lc-btn-t">{{ loginLoading ? '登录中...' : '微信登录' }}</text>
						</view>
					</view>
				</view>
			</view>

			<!-- 授权弹窗（底部弹出） -->
			<view class="auth-mask" v-if="showAuthPopup" @tap="showAuthPopup = false">
				<view class="auth-popup" @tap.stop>
					<!-- 顶部拖拽条 -->
					<view class="popup-drag"></view>
					<text class="popup-title">完善个人信息</text>
					<!-- 头像选择（官方chooseAvatar） -->
					<view class="popup-avatar-row">
						<text class="popup-label">头像</text>
						<view class="popup-avatar-btn-wrap">
							<button
								v-if="!avatarChosen"
								class="popup-avatar-btn"
								open-type="chooseAvatar"
								@chooseavatar="onChooseAvatar"
							>
								<image class="popup-avatar-img" :src="loginAvatar || '/static/default-avatar.png'" mode="aspectFill"></image>
								<text class="popup-arrow">›</text>
							</button>
							<view v-else class="popup-avatar-btn" @tap="resetAvatar">
								<image class="popup-avatar-img" :src="loginAvatar || '/static/default-avatar.png'" mode="aspectFill"></image>
								<text class="popup-arrow">›</text>
							</view>
						</view>
					</view>
					<!-- 昵称输入（官方type=nickname） -->
					<view class="popup-nickname-row">
						<text class="popup-label">昵称</text>
						<input
							type="nickname"
							class="popup-nickname-input"
							v-model="loginNickname"
							placeholder="请输入昵称"
							placeholder-class="popup-placeholder"
							maxlength="16"
						/>
					</view>
					<!-- 保存按钮 -->
					<view class="popup-save-btn" @tap="submitLogin" :style="'background:' + tc.btnGradient + ';box-shadow:0 6rpx 24rpx rgba(' + tc.rgbPrimary + ',0.35);'">
						<text>{{ loginLoading ? '登录中...' : '保存并登录' }}</text>
					</view>
				</view>
			</view>

			<!-- VIP横幅 -->
				<!-- 编辑资料弹窗 -->
				<view class="auth-mask" v-if="showEditPopup" @tap="showEditPopup = false">
					<view class="auth-popup" @tap.stop>
						<view class="popup-drag"></view>
						<text class="popup-title">编辑个人资料</text>
						<view class="popup-avatar-row">
							<text class="popup-label">头像</text>
							<view class="popup-avatar-btn-wrap">
								<button class="popup-avatar-btn" open-type="chooseAvatar" @chooseavatar="onEditAvatar">
									<image class="popup-avatar-img" :src="editAvatar || avatarSrc" mode="aspectFill"></image>
									<text class="popup-arrow">›</text>
								</button>
							</view>
						</view>
						<view class="popup-nickname-row">
							<text class="popup-label">昵称</text>
							<input type="nickname" class="popup-nickname-input" v-model="editNickname" placeholder="请输入昵称" placeholder-class="popup-placeholder" maxlength="16" />
						</view>
						<view class="popup-save-btn" @tap="saveProfile" :style="'background:' + tc.btnGradient + ';box-shadow:0 6rpx 24rpx rgba(' + tc.rgbPrimary + ',0.35);'">
							<text>{{ saving ? '保存中...' : '保存' }}</text>
						</view>
					</view>
				</view>

			<view class="vip-banner" v-if="isLoggedIn" @tap="goVip" :style="'background:' + tc.vipBannerBg + ';'">
				<view class="vip-content">
					<view class="vip-left">
						<view class="vip-title-row">
							<text class="vip-crown" v-if="userInfo.is_vip">👑</text>
							<text class="vip-crown" v-else>💎</text>
							<text class="vip-title" v-if="userInfo.is_vip" :style="'color:' + tc.vipTextColor + ';'">VIP会员</text>
							<text class="vip-title" v-else :style="'color:' + tc.vipTextColor + ';'">开通VIP会员</text>
						</view>
						<text class="vip-desc" v-if="userInfo.is_vip && userInfo.vip_expire_at" :style="'color:' + tc.vipDescColor + ';'">
							有效期至 {{ userInfo.vip_expire_at.slice(0,10) }}
						</text>
						<text class="vip-desc" v-else :style="'color:' + tc.vipDescColor + ';'">全站资源免费下载 · 极速不限速</text>
					</view>
					<view class="vip-action" :style="'background:' + tc.primary + ';box-shadow:0 6rpx 18rpx rgba(' + tc.rgbPrimary + ',0.25);'">
						<text class="vip-action-t">{{ userInfo.is_vip ? '续费' : '开通' }}</text>
					</view>
				</view>
				<view class="vip-features">
					<text class="vip-feat" :style="'color:' + tc.vipDescColor + ';background:rgba(255,255,255,0.45);border-color:rgba(' + tc.rgbPrimary + ',0.08);'">📥 免费下载</text>
					<text class="vip-feat" :style="'color:' + tc.vipDescColor + ';background:rgba(255,255,255,0.45);border-color:rgba(' + tc.rgbPrimary + ',0.08);'">⚡ 极速通道</text>
					<text class="vip-feat" :style="'color:' + tc.vipDescColor + ';background:rgba(255,255,255,0.45);border-color:rgba(' + tc.rgbPrimary + ',0.08);'">🎯 专属资源</text>
				</view>
			</view>

			<!-- 数据统计 -->
			<view class="stats-card" v-if="isLoggedIn">
				<view class="stat-item" @tap="goPage('/pages/user/downloads')">
					<view class="stat-icon-wrap download-bg" :style="'background:rgba(' + tc.rgbPrimary + ',0.15);'">
						<text class="stat-emoji">📥</text>
					</view>
					<text class="stat-num" :style="tPri">{{ stats.download_count || 0 }}</text>
					<text class="stat-label">下载</text>
				</view>
				<view class="stat-divider"></view>
				<view class="stat-item" @tap="goPage('/pages/user/favorites')">
					<view class="stat-icon-wrap fav-bg" :style="'background:rgba(' + tc.rgbPrimary + ',0.15);'">
						<text class="stat-emoji">❤️</text>
					</view>
					<text class="stat-num" :style="tPri">{{ stats.favorite_count || 0 }}</text>
					<text class="stat-label">收藏</text>
				</view>
				<view class="stat-divider"></view>
				<view class="stat-item" @tap="goPage('/pages/user/orders')">
					<view class="stat-icon-wrap orders-bg" :style="'background:rgba(' + tc.rgbPrimary + ',0.15);'">
						<text class="stat-emoji">📦</text>
					</view>
					<text class="stat-num" :style="tPri">{{ stats.order_count || 0 }}</text>
					<text class="stat-label">订单</text>
				</view>
			</view>

			<!-- 菜单列表 -->
			<view class="menu-card">
				<view class="menu-item" @tap="goPage('/pages/user/downloads')">
					<view class="menu-icon-wrap download-bg" :style="'background:rgba(' + tc.rgbPrimary + ',0.15);'">
						<text class="menu-emoji">📥</text>
					</view>
					<text class="menu-text">我的下载</text>
					<text class="menu-arrow">></text>
				</view>
				<view class="menu-item" @tap="goPage('/pages/user/favorites')">
					<view class="menu-icon-wrap fav-bg" :style="'background:rgba(' + tc.rgbPrimary + ',0.15);'">
						<text class="menu-emoji">❤️</text>
					</view>
					<text class="menu-text">我的收藏</text>
					<text class="menu-arrow">></text>
				</view>
				<view class="menu-item" @tap="goPage('/pages/user/orders')">
					<view class="menu-icon-wrap order-bg" :style="'background:rgba(' + tc.rgbPrimary + ',0.15);'">
						<text class="menu-emoji">📋</text>
					</view>
					<text class="menu-text">我的订单</text>
					<text class="menu-arrow">></text>
				</view>
				<view class="menu-item" @tap="goPage('/pages/user/vip')">
					<view class="menu-icon-wrap vip-bg">
						<text class="menu-emoji">👑</text>
					</view>
					<text class="menu-text">VIP中心</text>
					<text class="menu-arrow">></text>
				</view>
				<view class="menu-item" v-if="feedbackEnabled" @tap="goPage('/pages/user/feedback')">
					<view class="menu-icon-wrap fb-bg">
						<text class="menu-emoji">💬</text>
					</view>
					<text class="menu-text">意见反馈</text>
					<text class="menu-arrow">></text>
				</view>
				<view class="menu-item" @tap="showAbout">
					<view class="menu-icon-wrap about-bg">
						<text class="menu-emoji">ℹ️</text>
					</view>
					<text class="menu-text">关于我们</text>
					<text class="menu-arrow">></text>
				</view>
				<view class="menu-item" @tap="openThemePicker">
					<view class="menu-icon-wrap theme-bg">
						<text class="menu-emoji">🎨</text>
					</view>
					<text class="menu-text">主题风格</text>
					<view class="theme-dot" :style="'background:' + currentThemeColor + ';'"></view>
					<text class="menu-arrow">></text>
				</view>
			</view>

			<!-- 退出登录 -->
			<view class="logout-btn" v-if="isLoggedIn" @tap="handleLogout">
				<text>退出登录</text>
			</view>

			<view style="height: 60rpx;"></view>
		</scroll-view>

		<!-- 主题选择弹窗 -->
		<view class="auth-mask" v-if="showThemePopup" @tap="showThemePopup = false">
			<view class="theme-popup" @tap.stop>
				<view class="popup-drag"></view>
				<text class="popup-title">选择主题风格</text>
				<view class="theme-grid">
					<view
						class="theme-item"
						v-for="item in themeList"
						:key="item.key"
						:class="{ active: activeTheme === item.key }"
						:style="activeTheme === item.key ? 'border-color:' + item.primary + ';' : ''"
						@tap="switchTheme(item.key)"
					>
						<view class="theme-circle" :style="'background:' + item.primary + ';'">
							<text class="theme-check" v-if="activeTheme === item.key">✓</text>
						</view>
						<text class="theme-label">{{ item.emoji }} {{ item.name }}</text>
					</view>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
import http from '@/utils/http.js';
import theme from '@/common/theme.js';

export default {
	data() {
		return {
			isLoggedIn: false,
			userInfo: {},
			stats: {
				download_count: 0,
				favorite_count: 0,
				order_count: 0,
				points: 0
			},
			loginAvatar: '',
			loginNickname: '',
			loginLoading: false,
			loginFeatures: [
				{ icon: '📥', name: '免费下载' },
				{ icon: '⭐', name: '收藏资源' },
				{ icon: '📦', name: '下载记录' }
			],
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
			if (!url || url.indexOf('default_avatar') !== -1) {
				return '/static/default-avatar.png';
			}
			// 相对路径补全为完整URL
			if (url.startsWith('/')) {
				return http.getBaseUrl() + url;
			}
			return url;
		},
		currentThemeColor() {
			return this.tc.primary;
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
				const res = await http.get('/api/user/info');
				if (res.code === 0) {
					this.userInfo = res.data || {};
				}
			} catch (e) {
				console.error('加载用户信息失败', e);
			}
		},
		async loadStats() {
			try {
				const res = await http.get('/api/user/stats');
				if (res.code === 0) {
					this.stats = res.data || {};
					if (res.data && res.data.feedback_enabled !== undefined) {
						this.feedbackEnabled = !!res.data.feedback_enabled;
					}
				}
			} catch (e) {}
		},
		goLogin() {
			// 登录卡片默认展示，无需此方法
		},
		async quickLogin() {
			if (this.loginLoading) return;
			this.loginLoading = true;
			try {
				// 第一步：有 token 尝试静默登录
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
					// token 无效，清除
					uni.removeStorageSync('token');
					uni.removeStorageSync('userInfo');
				}

				// 第二步：wx.login 登录（后端用固定 openid 识别用户，不受 code 变化影响）
				const loginRes = await new Promise((resolve, reject) => {
					uni.login({ provider: 'weixin', success: resolve, fail: reject });
				});
				const res = await http.post('/api/auth/wx-login', { code: loginRes.code, openid: '' });
				if (res.code === 0 && res.data && res.data.user) {
					const user = res.data.user;
					if (user.openid) uni.setStorageSync('openid', user.openid);
					// 老用户（已自定义昵称）→ 直接登录
					if (!res.data.is_new_user && user.nickname && user.nickname !== '微信用户') {
						this.handleLoginSuccess(res.data);
						this.loginLoading = false;
						return;
					}
				}
				// 新用户 → 弹窗填写资料
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
			const avatarUrl = e.detail.avatarUrl;
			if (avatarUrl) {
				this.loginAvatar = avatarUrl;
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
				// 1. 使用已保存的 code（quickLogin 中获取的）
				const code = this.loginCode;
				if (!code) {
					uni.showToast({ title: '请重新点击登录', icon: 'none' });
					return;
				}

				// 2. 构造上传数据
				const nickname = this.loginNickname || '微信用户';
				const savedOpenid = uni.getStorageSync('openid') || '';

				// 3. 如果有头像，用 uploadFile 上传；否则用普通 POST
				if (this.loginAvatar) {
					const uploadRes = await new Promise((resolve, reject) => {
						uni.uploadFile({
							url: http.getBaseUrl() + '/api/auth/fill-login',
							filePath: this.loginAvatar,
							name: 'avatar',
							formData: { code: code, nickname: nickname, openid: savedOpenid },
							success: (res) => {
								try { resolve(JSON.parse(res.data)); }
								catch (e) { reject(new Error('解析响应失败')); }
							},
							fail: reject
						});
					});
					if (uploadRes.code === 0) {
						this.handleLoginSuccess(uploadRes.data);
					} else {
						uni.showToast({ title: uploadRes.message || '登录失败', icon: 'none' });
					}
				} else {
					// 无头像，普通 POST
					const res = await http.post('/api/auth/fill-login', {
						code: code,
						nickname: nickname,
						openid: savedOpenid
					});
					if (res.code === 0) {
						this.handleLoginSuccess(res.data);
					} else {
						uni.showToast({ title: res.message || '登录失败', icon: 'none' });
					}
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
				// 保存openid供后续登录识别老用户
				if (data.user.openid) {
					uni.setStorageSync('openid', data.user.openid);
				}
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
					success: (res) => {
						if (res.confirm) {
							uni.pageScrollTo({ scrollTop: 0, duration: 300 });
						}
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
			try {
				const res = await http.get('/api/user/about');
				if (res.code === 0 && res.data) {
					const d = res.data;
					uni.showModal({
						title: '关于我们',
						content: `版本: ${d.about_version || 'v1.0.0'}\n\n${d.about_content || ''}\n\n${d.about_copyright || ''}`,
						showCancel: false
					});
				} else {
					uni.showModal({
						title: '关于我们',
						content: '资源下载平台 v1.0.0\n海量优质资源，助力高效工作',
						showCancel: false
					});
				}
			} catch (e) {
				uni.showModal({
					title: '关于我们',
					content: '资源下载平台 v1.0.0\n海量优质资源，助力高效工作',
					showCancel: false
				});
			}
		},

		openThemePicker() {
			this.showThemePopup = true;
		},
		switchTheme(name) {
			this.activeTheme = name;
			uni.setStorageSync('theme', name);
			uni.$emit('__themeChanged', name);
			theme.applyTheme(name);
			this.showThemePopup = false;
			uni.showToast({ title: '已切换', icon: 'success', duration: 800 });
			setTimeout(() => {
				uni.switchTab({ url: '/pages/index/index' });
			}, 1000);
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
					// 有新头像，用 uploadFile
					res = await new Promise((resolve, reject) => {
						const token = uni.getStorageSync('token') || '';
						uni.uploadFile({
							url: http.getBaseUrl() + '/api/auth/update-profile',
							filePath: this.editAvatar,
							name: 'avatar',
							formData: { nickname: this.editNickname.trim() },
							header: { 'Authorization': 'Bearer ' + token },
							success: (r) => { try { resolve(JSON.parse(r.data)); } catch(e) { reject(e); } },
							fail: reject
						});
					});
				} else {
					res = await http.post('/api/auth/update-profile', {
						nickname: this.editNickname.trim()
					});
				}
				if (res.code === 0) {
					uni.showToast({ title: '保存成功', icon: 'success' });
					this.showEditPopup = false;
					// 刷新用户信息
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
				success: (res) => {
					if (res.confirm) {
						uni.removeStorageSync('token');
						uni.removeStorageSync('userInfo');
						// openid 是用户唯一标识，退出时保留，用于下次识别老用户
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
}
.main-scroll {
	height: 100vh;
}

/* ==================== 用户信息卡 ==================== */
.user-card {
	background: transparent;
	padding: 158rpx 32rpx 80rpx;
	min-height: 500rpx;
	margin-bottom: 24rpx;
	position: relative;
	overflow: hidden;
}
/* 装饰圆环粒子 - 大圆 */
.user-bg-decor {
	position: absolute;
	top: -80rpx;
	right: -80rpx;
	width: 360rpx;
	height: 360rpx;
	border-radius: 50%;
	background: radial-gradient(circle, rgba(255,255,255,0.12) 0%, rgba(255,255,255,0.03) 60%, transparent 70%);
	border: 2rpx solid rgba(255,255,255,0.08);
}
.user-card::before {
	content: '';
	position: absolute;
	top: 40rpx;
	left: -40rpx;
	width: 180rpx;
	height: 180rpx;
	border-radius: 50%;
	background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
	border: 1rpx solid rgba(255,255,255,0.06);
}
.user-card::after {
	content: '';
	position: absolute;
	bottom: 20rpx;
	right: 120rpx;
	width: 3rpx;
	height: 3rpx;
	border-radius: 50%;
	background: #fff;
	box-shadow:
		60rpx 30rpx 0 0 rgba(255,255,255,0.6),
		120rpx -10rpx 0 1rpx rgba(255,255,255,0.4),
		30rpx -40rpx 0 0 rgba(255,255,255,0.35),
		180rpx 20rpx 0 1rpx rgba(255,255,255,0.5),
		-20rpx 60rpx 0 1rpx rgba(255,255,255,0.4),
		150rpx 50rpx 0 0 rgba(255,255,255,0.35),
		100rpx 40rpx 0 1rpx rgba(255,255,255,0.25),
		220rpx 40rpx 0 1rpx rgba(255,255,255,0.4);
	animation: float-particle 4s ease-in-out infinite, twinkle 3s ease-in-out infinite;
}
@keyframes float-particle {
	0%, 100% { transform: translateY(0) scale(1); opacity: 0.6; }
	50% { transform: translateY(-16rpx) scale(1.1); opacity: 1; }
}
@keyframes twinkle {
	0%, 100% { opacity: 0.6; }
	25% { opacity: 1; }
	50% { opacity: 0.4; }
	75% { opacity: 0.9; }
}
@keyframes breathe-glow {
	0%, 100% { opacity: 1; transform: scale(0.95); }
	50% { opacity: 0.75; transform: scale(0.75); }
}

.user-header {
	display: flex;
	align-items: center;
	position: relative;
	z-index: 1;
}
.avatar-wrap {
	position: relative;
	flex-shrink: 0;
	width: 140rpx;
	height: 140rpx;
	display: flex;
	align-items: center;
	justify-content: center;
}
.user-avatar {
	width: 120rpx;
	height: 120rpx;
	border-radius: 50%;
	border: 5rpx solid #fff;
	flex-shrink: 0;
	position: relative;
	z-index: 1;
	box-shadow: 0 4rpx 20rpx rgba(0,0,0,0.15);
}
.avatar-wrap::before {
	content: '';
	position: absolute;
	inset: 0;
	border-radius: 50%;
	background: rgba(255,255,255,0.25);
	z-index: 0;
}
.avatar-wrap::after {
	content: '';
	position: absolute;
	inset: 4rpx;
	border-radius: 50%;
	background: transparent;
	border: 2rpx solid rgba(255,255,255,0.2);
	z-index: 0;
}

/* VIP 金色光环 */
.vip-ring {
	position: absolute;
	top: -10rpx;
	left: -10rpx;
	right: -10rpx;
	bottom: -10rpx;
	border-radius: 50%;
	border: 4rpx solid #f9ca24;
	animation: vip-glow 2s ease-in-out infinite;
	z-index: 2;
}
@keyframes vip-glow {
	0%, 100% {
		box-shadow: 0 0 8rpx rgba(249,202,36,0.3), 0 0 20rpx rgba(249,202,36,0.1);
		border-color: #f9ca24;
	}
	50% {
		box-shadow: 0 0 24rpx rgba(249,202,36,0.6), 0 0 48rpx rgba(249,202,36,0.2);
		border-color: #ffeaa7;
	}
}

.user-meta {
	margin-left: 28rpx;
	flex: 1;
}
.name-row {
	display: flex;
	align-items: center;
	gap: 14rpx;
}
.user-name {
	font-size: 38rpx;
	font-weight: 800;
	color: #fff;
	text-shadow: 0 2rpx 8rpx rgba(0,0,0,0.1);
}

/* VIP徽章发光效果 */
.vip-badge {
	background: linear-gradient(135deg, #f9ca24, #f0932b);
	padding: 6rpx 20rpx;
	border-radius: 20rpx;
	display: flex;
	align-items: center;
	gap: 6rpx;
	box-shadow: 0 4rpx 12rpx rgba(249,202,36,0.4), 0 0 24rpx rgba(249,202,36,0.2);
	animation: badge-glow 2.5s ease-in-out infinite;
}
@keyframes badge-glow {
	0%, 100% { box-shadow: 0 4rpx 12rpx rgba(249,202,36,0.4), 0 0 16rpx rgba(249,202,36,0.15); }
	50% { box-shadow: 0 4rpx 20rpx rgba(249,202,36,0.6), 0 0 36rpx rgba(249,202,36,0.3); }
}
.vip-icon {
	font-size: 22rpx;
}
.vip-text {
	font-size: 22rpx;
	color: #fff;
	font-weight: 800;
}
.user-id {
	font-size: 24rpx;
	color: rgba(255,255,255,0.7);
	margin-top: 10rpx;
	display: block;
}
.edit-profile-btn {
	margin-left: auto;
	background: rgba(255,255,255,0.2);
	padding: 8rpx 24rpx;
	border-radius: 24rpx;
	flex-shrink: 0;
	backdrop-filter: blur(8rpx);
	transition: all 0.2s;
}
.edit-profile-btn:active {
	background: rgba(255,255,255,0.35);
	transform: scale(0.96);
}
.edit-profile-btn text {
	font-size: 24rpx;
	color: rgba(255,255,255,0.9);
	font-weight: 500;
}

/* ==================== 未登录 ==================== */
.login-section {
	position: relative;
	margin-bottom: 24rpx;
}
.login-header-bg {
	height: 500rpx;
	background: transparent;
	display: flex;
	align-items: flex-start;
	padding: 140rpx 36rpx 0;
	position: relative;
}
.login-header-bg::before {
	content: '';
	position: absolute;
	top: 40rpx;
	right: 30rpx;
	width: 240rpx;
	height: 240rpx;
	border-radius: 50%;
	background: rgba(255,255,255,0.04);
	pointer-events: none;
}
.login-header-bg::after {
	content: '';
	position: absolute;
	bottom: 20rpx;
	left: 20rpx;
	width: 180rpx;
	height: 180rpx;
	border-radius: 50%;
	background: rgba(255,255,255,0.03);
	pointer-events: none;
}
.lh-inner {
	display: flex;
	align-items: center;
	position: relative;
	z-index: 1;
}
.lh-ring {
	width: 116rpx;
	height: 116rpx;
	border-radius: 50%;
	background: linear-gradient(135deg, rgba(255,255,255,0.95), rgba(255,255,255,0.8));
	display: flex;
	align-items: center;
	justify-content: center;
	flex-shrink: 0;
	box-shadow: 0 10rpx 32rpx rgba(0,0,0,0.15);
	border: 4rpx solid #2ed573;
	box-sizing: border-box;
	position: relative;
}
.lh-avatar-box {
	width: 100rpx;
	height: 100rpx;
	border-radius: 50%;
	overflow: hidden;
	position: absolute;
	top: 50%;
	left: 50%;
	transform: translate(-50%, -50%);
	background: #2ed573;
	display: flex;
	align-items: center;
	justify-content: center;
}
.lh-avatar-icon {
	font-size: 50rpx;
}
.lh-avatar {
	width: 100%;
	height: 100%;
	display: block;
}
.lh-info {
	margin-left: 36rpx;
}
.lh-name {
	font-size: 44rpx;
	font-weight: 900;
	color: #fff;
	display: block;
	text-shadow: 0 4rpx 14rpx rgba(0,0,0,0.12);
	letter-spacing: 5rpx;
}
.lh-tip {
	font-size: 26rpx;
	color: rgba(255,255,255,0.7);
	display: block;
	margin-top: 12rpx;
	font-weight: 500;
	letter-spacing: 1rpx;
}
.login-card {
	margin: -180rpx 28rpx 0;
	border-radius: 32rpx;
	background: #fff;
	box-shadow: 0 12rpx 48rpx rgba(0,0,0,0.09);
	position: relative;
	z-index: 1;
	overflow: hidden;
}
.lc-row {
	display: flex;
	padding: 48rpx 20rpx;
}
.lc-feat {
	flex: 1;
	display: flex;
	flex-direction: column;
	align-items: center;
	gap: 16rpx;
}
.lf-icon-wrap {
	width: 96rpx;
	height: 96rpx;
	border-radius: 50%;
	display: flex;
	align-items: center;
	justify-content: center;
	box-shadow: 0 6rpx 18rpx rgba(0,0,0,0.04);
}
.lf-icon-wrap.g1 { background: rgba(0,0,0,0.04); }
.lf-icon-wrap.g2 { background: linear-gradient(135deg, #fff7ed, #ffedd5); }
.lf-icon-wrap.g3 { background: linear-gradient(135deg, #f3e8ff, #e9d5ff); }
.lf-ico { font-size: 44rpx; }
.lf-name { font-size: 26rpx; color: #333; font-weight: 700; letter-spacing: 1rpx; }
.lc-btn-area {
	padding: 0 28rpx 48rpx;
}
.lc-btn {
	width: 100%;
	background: transparent;
	padding: 22rpx 0;
	border-radius: 56rpx;
	text-align: center;
	box-shadow: 0 16rpx 44rpx rgba(0,0,0,0.2), 0 6rpx 18rpx rgba(0,0,0,0.1), inset 0 1rpx 0 rgba(255,255,255,0.2);
	position: relative;
	overflow: hidden;
	animation: btn-gradient 4s ease infinite;
}
@keyframes btn-gradient {
	0% { background-position: 0% 50%; }
	50% { background-position: 100% 50%; }
	100% { background-position: 0% 50%; }
}
.lc-btn::before {
	content: '';
	position: absolute;
	top: -2rpx;
	left: 4%;
	right: 4%;
	height: 6rpx;
	background: linear-gradient(90deg, transparent, rgba(255,255,255,0.8), transparent);
	border-radius: 56rpx;
	pointer-events: none;
	animation: btn-shine 3s ease-in-out infinite;
}
@keyframes btn-shine {
	0%, 100% { opacity: 0.2; left: 4%; right: 4%; }
	50% { opacity: 1; left: 20%; right: 20%; }
}
.lc-btn::after {
	content: '';
	position: absolute;
	top: 0;
	left: 0;
	right: 0;
	height: 50%;
	background: linear-gradient(to bottom, rgba(255,255,255,0.32), transparent);
	border-radius: 56rpx 56rpx 0 0;
	pointer-events: none;
}
.lc-btn-t {
	font-size: 32rpx;
	color: #fff;
	font-weight: 800;
	letter-spacing: 4rpx;
	text-shadow: 0 3rpx 10rpx rgba(0,0,0,0.25), 0 1rpx 3rpx rgba(0,0,0,0.15);
	position: relative;
}
.lc-btn:active { opacity: 0.92; transform: scale(0.97); }

/* ==================== 授权弹窗（毛玻璃） ==================== */
.auth-mask {
	position: fixed;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	background: rgba(15,15,35,0.55);
	backdrop-filter: blur(12rpx);
	-webkit-backdrop-filter: blur(12rpx);
	z-index: 9999;
	display: flex;
	align-items: flex-end;
}
.auth-popup {
	width: 100%;
	background: rgba(255,255,255,0.92);
	backdrop-filter: blur(40rpx);
	-webkit-backdrop-filter: blur(40rpx);
	border-radius: 32rpx 32rpx 0 0;
	padding: 20rpx 40rpx 60rpx;
	padding-bottom: calc(60rpx + env(safe-area-inset-bottom));
	animation: slideUp 0.3s cubic-bezier(0.16,1,0.3,1);
	border-top: 1rpx solid rgba(255,255,255,0.6);
	box-shadow: inset 0 0 40rpx rgba(0,0,0,0.05);
}
@keyframes slideUp {
	from { transform: translateY(100%); opacity: 0.8; }
	to { transform: translateY(0); opacity: 1; }
}
.popup-drag {
	width: 64rpx;
	height: 8rpx;
	background: linear-gradient(90deg, #d0d0e0, #e8e8f0, #d0d0e0);
	border-radius: 4rpx;
	margin: 0 auto 28rpx;
}
.popup-title {
	font-size: 34rpx;
	font-weight: 700;
	color: #1a1a2e;
	display: block;
	text-align: center;
	margin-bottom: 40rpx;
}
/* 头像行 */
.popup-avatar-row {
	display: flex;
	align-items: center;
	justify-content: space-between;
	padding: 24rpx 0;
	border-bottom: 1rpx solid rgba(240,240,245,0.8);
}
.popup-label {
	font-size: 30rpx;
	color: #333;
	font-weight: 500;
}
.popup-avatar-btn-wrap {
	display: flex;
	align-items: center;
}
.popup-avatar-btn {
	display: flex;
	align-items: center;
	background: transparent;
	border: none;
	padding: 0;
	margin: 0;
	line-height: normal;
}
.popup-avatar-btn::after {
	border: none;
}
.popup-avatar-img {
	width: 96rpx;
	height: 96rpx;
	border-radius: 50%;
	background: linear-gradient(135deg, #f0f2ff, #f5f6fa);
	box-shadow: 0 4rpx 16rpx rgba(0,0,0,0.1);
}
.popup-arrow {
	font-size: 36rpx;
	color: #ccc;
	margin-left: 12rpx;
}
/* 昵称行 */
.popup-nickname-row {
	display: flex;
	align-items: center;
	justify-content: space-between;
	padding: 24rpx 0;
	border-bottom: 1rpx solid rgba(240,240,245,0.8);
}
.popup-nickname-input {
	flex: 1;
	text-align: right;
	font-size: 30rpx;
	color: #333;
	height: 48rpx;
}
.popup-placeholder {
	color: #ccc;
}
/* 保存按钮（背景由内联样式动态设置） */
.popup-save-btn {
	margin-top: 48rpx;
	width: 100%;
	background: transparent;
	text-align: center;
	padding: 28rpx;
	border-radius: 44rpx;
	color: #fff;
	font-size: 32rpx;
	font-weight: 700;
	transition: all 0.2s;
}
.popup-save-btn:active {
	transform: scale(0.98);
	box-shadow: 0 4rpx 16rpx rgba(0,0,0,0.4);
}

/* ==================== VIP横幅 ==================== */
.vip-banner {
	margin: -188rpx 24rpx 24rpx;
	border-radius: 32rpx;
	overflow: hidden;
	background: linear-gradient(150deg, #f0fdf4 0%, #d1fae5 35%, #a7f3d0 65%, #f0fdf4 100%);
	box-shadow: 0 12rpx 40rpx rgba(0,0,0,0.18), 0 4rpx 12rpx rgba(0,0,0,0.03);
	position: relative;
	border: 2rpx solid rgba(0,0,0,0.2);
}
.vip-banner::before {
	content: '';
	position: absolute;
	top: -100rpx;
	right: -80rpx;
	width: 320rpx;
	height: 320rpx;
	border-radius: 50%;
	background: radial-gradient(circle, rgba(0,0,0,0.07), transparent 70%);
	pointer-events: none;
}
.vip-banner::after {
	content: '';
	position: absolute;
	bottom: -80rpx;
	left: -60rpx;
	width: 260rpx;
	height: 260rpx;
	border-radius: 50%;
	background: radial-gradient(circle, rgba(26,188,156,0.05), transparent 70%);
	pointer-events: none;
}
.vip-content {
	display: flex;
	align-items: center;
	justify-content: space-between;
	padding: 44rpx 32rpx 16rpx;
	position: relative;
	z-index: 1;
}
.vip-left { flex: 1; }
.vip-title-row { display: flex; align-items: center; gap: 16rpx; }
.vip-crown {
	font-size: 48rpx;
	filter: drop-shadow(0 3rpx 8rpx rgba(0,0,0,0.1));
}
.vip-title {
	font-size: 40rpx;
	font-weight: 900;
	color: #14532d;
	letter-spacing: 4rpx;
}
.vip-desc {
	font-size: 26rpx;
	color: #4ade80;
	margin-top: 12rpx;
	display: block;
	font-weight: 500;
	letter-spacing: 1rpx;
}
.vip-action {
	background: #2ed573;
	padding: 20rpx 48rpx;
	border-radius: 40rpx;
	flex-shrink: 0;
	box-shadow: 0 6rpx 18rpx rgba(46,213,115,0.25);
	position: relative;
	overflow: hidden;
}
.vip-action::after {
	content: '';
	position: absolute;
	top: 0;
	left: 0;
	right: 0;
	height: 50%;
	background: linear-gradient(to bottom, rgba(255,255,255,0.15), transparent);
	border-radius: 40rpx 40rpx 0 0;
	pointer-events: none;
}
.vip-action-t {
	font-size: 28rpx;
	color: #fff;
	font-weight: 800;
	letter-spacing: 3rpx;
	text-shadow: 0 1rpx 4rpx rgba(0,0,0,0.15);
}
.vip-action:active { opacity: 0.85; transform: scale(0.96); }
.vip-features {
	display: flex;
	padding: 12rpx 32rpx 40rpx;
	gap: 16rpx;
	position: relative;
	z-index: 1;
}
.vip-feat {
	font-size: 22rpx;
	color: #4ade80;
	font-weight: 600;
	background: rgba(255,255,255,0.45);
	padding: 10rpx 22rpx;
	border-radius: 18rpx;
	border: 1rpx solid rgba(0,0,0,0.06);
	backdrop-filter: blur(6px);
	box-shadow: 0 2rpx 8rpx rgba(0,0,0,0.03);
}

/* ==================== 数据统计 ==================== */
.stats-card {
	display: flex;
	background: #fff;
	margin: 0 24rpx 24rpx;
	padding: 36rpx 0;
	border-radius: 24rpx;
	box-shadow: 0 4rpx 24rpx rgba(0,0,0,0.06);
	position: relative;
}
.stat-item {
	flex: 1;
	display: flex;
	flex-direction: column;
	align-items: center;
}
/* 图标圆形渐变背景 */
.stat-icon-wrap {
	width: 72rpx;
	height: 72rpx;
	border-radius: 50%;
	display: flex;
	align-items: center;
	justify-content: center;
	margin-bottom: 14rpx;
	position: relative;
}
.stat-icon-wrap::after {
	content: '';
	position: absolute;
	inset: 0;
	border-radius: 50%;
	opacity: 0.12;
}
.download-bg {
	background: linear-gradient(135deg, rgba(59,130,246,0.15), rgba(99,102,241,0.08));
}
.fav-bg {
	background: linear-gradient(135deg, rgba(255,71,87,0.15), rgba(255,107,129,0.08));
}
.points-bg {
	background: linear-gradient(135deg, rgba(255,159,67,0.15), rgba(255,183,77,0.08));
}
.orders-bg {
	background: linear-gradient(135deg, rgba(0,0,0,0.15), rgba(105,230,153,0.08));
}
.stat-emoji {
	font-size: 30rpx;
}
/* 渐变紫色数字 */
.stat-num {
	font-size: 44rpx;
	font-weight: 800;
	color: #2ed573;
	font-variant-numeric: tabular-nums;
}
.stat-label {
	font-size: 24rpx;
	color: #aaa;
	margin-top: 8rpx;
}
.stat-divider {
	width: 1rpx;
	background: linear-gradient(180deg, transparent, #e8e8f5, transparent);
	margin: 10rpx 0;
}

/* ==================== 菜单 ==================== */
.menu-card {
	background: #fff;
	margin: 0 24rpx 24rpx;
	border-radius: 24rpx;
	overflow: hidden;
	box-shadow: 0 4rpx 24rpx rgba(0,0,0,0.06);
}
.menu-item {
	display: flex;
	align-items: center;
	padding: 30rpx 32rpx;
	border-bottom: 1rpx solid rgba(245,245,248,0.8);
	transition: all 0.2s ease;
	position: relative;
}
.menu-item:active {
	background: rgba(0,0,0,0.04);
	transform: scale(0.99);
}
.menu-item:last-child {
	border-bottom: none;
}
/* 分组分隔线装饰 - 前3项后加分隔 */
.menu-item:nth-child(3) {
	border-bottom: 2rpx solid transparent;
	background-image: linear-gradient(#fff, #fff), linear-gradient(90deg, transparent, #e8e8f5, transparent);
	background-origin: border-box;
	background-clip: padding-box, border-box;
	border-bottom: 2rpx solid;
	border-image: linear-gradient(90deg, transparent, #e0e0f0, transparent) 1;
	padding-bottom: 32rpx;
	margin-bottom: 4rpx;
}

/* 菜单图标圆形容器+渐变背景 */
.menu-icon-wrap {
	width: 60rpx;
	height: 60rpx;
	border-radius: 50%;
	display: flex;
	align-items: center;
	justify-content: center;
	flex-shrink: 0;
	position: relative;
}
.menu-emoji {
	font-size: 28rpx;
}
.download-bg {
	background: linear-gradient(135deg, rgba(59,130,246,0.15), rgba(99,102,241,0.08));
}
.fav-bg {
	background: linear-gradient(135deg, rgba(255,71,87,0.15), rgba(255,107,129,0.08));
}
.order-bg {
	background: linear-gradient(135deg, rgba(0,184,148,0.15), rgba(72,219,185,0.08));
}
.vip-bg {
	background: linear-gradient(135deg, rgba(249,202,36,0.15), rgba(255,215,0,0.08));
}
.fb-bg {
	background: linear-gradient(135deg, rgba(99,102,241,0.15), rgba(0,0,0,0.08));
}
.about-bg {
	background: linear-gradient(135deg, rgba(148,163,184,0.15), rgba(176,190,210,0.08));
}
.theme-bg {
	background: linear-gradient(135deg, rgba(236,72,153,0.15), rgba(139,92,246,0.08));
}
.theme-dot {
	width: 24rpx;
	height: 24rpx;
	border-radius: 50%;
	margin-right: 12rpx;
	border: 3rpx solid rgba(255,255,255,0.8);
	box-shadow: 0 2rpx 8rpx rgba(0,0,0,0.1);
	flex-shrink: 0;
}
.menu-text {
	flex: 1;
	margin-left: 22rpx;
	font-size: 28rpx;
	color: #1a1a2e;
	font-weight: 600;
}
.menu-arrow {
	font-size: 28rpx;
	background: linear-gradient(180deg, #ccc, #999);
	-webkit-background-clip: text;
	background-clip: text;
	color: transparent;
	-webkit-text-fill-color: transparent;
	font-weight: 300;
	transition: transform 0.2s;
}
.menu-item:active .menu-arrow {
	transform: translateX(4rpx);
}

/* ==================== 退出登录（渐变边框） ==================== */
.logout-btn {
	margin: 40rpx 24rpx;
	text-align: center;
	padding: 28rpx;
	background: #fff;
	border-radius: 44rpx;
	font-size: 28rpx;
	color: #ff4757;
	font-weight: 600;
	position: relative;
	overflow: hidden;
	box-shadow: 0 4rpx 24rpx rgba(0,0,0,0.06);
	border: 2rpx solid transparent;
	background-clip: padding-box;
}
.logout-btn::before {
	content: '';
	position: absolute;
	top: -2rpx;
	left: -2rpx;
	right: -2rpx;
	bottom: -2rpx;
	border-radius: 46rpx;
	background: linear-gradient(135deg, #ff4757, #ff6b81, #ff4757);
	z-index: -1;
}
.logout-btn::after {
	content: '';
	position: absolute;
	inset: 2rpx;
	border-radius: 42rpx;
	background: #fff;
	z-index: -1;
}
.logout-btn:active {
	background: #fef2f2;
	border-color: transparent;
}
.logout-btn:active::after {
	background: #fef2f2;
}

/* ==================== 主题选择弹窗 ==================== */
.theme-popup {
	width: 100%;
	background: rgba(255,255,255,0.96);
	backdrop-filter: blur(40rpx);
	-webkit-backdrop-filter: blur(40rpx);
	border-radius: 32rpx 32rpx 0 0;
	padding: 20rpx 40rpx 60rpx;
	padding-bottom: calc(60rpx + env(safe-area-inset-bottom));
	animation: slideUp 0.3s cubic-bezier(0.16,1,0.3,1);
	border-top: 1rpx solid rgba(255,255,255,0.6);
}
.theme-grid {
	display: flex;
	flex-wrap: wrap;
	justify-content: space-between;
	padding: 20rpx 0 10rpx;
}
.theme-item {
	width: 30%;
	display: flex;
	flex-direction: column;
	align-items: center;
	padding: 28rpx 0 20rpx;
	margin-bottom: 16rpx;
	border-radius: 24rpx;
	background: #f7f8fc;
	border: 2rpx solid transparent;
	transition: all 0.25s ease;
}
.theme-item.active {
	background: rgba(0,0,0, 0.06);
	box-shadow: 0 4rpx 20rpx rgba(0,0,0, 0.12);
}
.theme-item:active {
	transform: scale(0.95);
}
.theme-circle {
	width: 80rpx;
	height: 80rpx;
	border-radius: 50%;
	display: flex;
	align-items: center;
	justify-content: center;
	margin-bottom: 16rpx;
	box-shadow: 0 6rpx 20rpx rgba(0,0,0,0.12);
	position: relative;
}
.theme-circle::after {
	content: '';
	position: absolute;
	top: 6rpx;
	left: 15%;
	right: 15%;
	height: 30%;
	background: linear-gradient(to bottom, rgba(255,255,255,0.45), transparent);
	border-radius: 50%;
}
.theme-check {
	font-size: 32rpx;
	color: #fff;
	font-weight: 800;
	text-shadow: 0 2rpx 6rpx rgba(0,0,0,0.2);
}
.theme-label {
	font-size: 24rpx;
	color: #444;
	font-weight: 600;
	letter-spacing: 0.5rpx;
}
</style>
