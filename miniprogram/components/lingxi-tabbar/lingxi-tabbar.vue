<template>
	<view class="lingxi-tab-bar" :style="barStyle">
		<view class="lingxi-tab-inner" :style="innerStyle">
			<view
				class="lingxi-tab-item"
				v-for="(item, index) in list"
				:key="index"
				@tap="onTap(index)"
			>
				<image
					class="lingxi-tab-icon"
					:src="iconSrc(index)"
					mode="aspectFit"
				></image>
				<text
					class="lingxi-tab-label"
					:style="current === index ? activeLabelStyle : inactiveLabelStyle"
				>{{ item.text }}</text>
			</view>
		</view>
	</view>
</template>

<script>
import theme from '@/common/theme.js'
import { getThemeColors } from '@/common/theme-colors.js'

export default {
	name: 'LingxiTabbar',
	props: {
		current: {
			type: Number,
			default: 0
		}
	},
	data() {
		return {
			themeName: 'green',
			list: [
				{
					pagePath: '/pages/index/index',
					text: '首页',
					iconPath: '/static/icons/home.png',
					activeBase: '/static/icons/home-active'
				},
				{
					pagePath: '/pages/category/list',
					text: '分类',
					iconPath: '/static/icons/category.png',
					activeBase: '/static/icons/category-active'
				},
				{
					pagePath: '/pages/user/index',
					text: '我的',
					iconPath: '/static/icons/user.png',
					activeBase: '/static/icons/user-active'
				}
			]
		}
	},
	computed: {
		themeObj() {
			return getThemeColors(this.themeName)
		},
		pageBg() {
			return (this.themeObj && this.themeObj.pageBg) || '#f0f7e6'
		},
		rgbPrimary() {
			return (this.themeObj && this.themeObj.rgbPrimary) || '124,179,66'
		},
		// 外层垫底渐变（字符串样式，小程序更稳）
		barStyle() {
			const rgb = this.rgbPrimary
			const bg = this.pageBg
			return (
				'background:linear-gradient(180deg,rgba(' +
				rgb +
				',0) 0%,rgba(' +
				rgb +
				',0.06) 45%,' +
				bg +
				' 100%);'
			)
		},
		// 大胶囊：浅白通透毛玻璃，只带一点点主题色
		innerStyle() {
			const rgb = this.rgbPrimary
			return (
				'background:linear-gradient(135deg,rgba(255,255,255,0.92) 0%,rgba(255,255,255,0.78) 45%,rgba(' +
				rgb +
				',0.16) 100%);' +
				'backdrop-filter:blur(28px) saturate(1.2);' +
				'-webkit-backdrop-filter:blur(28px) saturate(1.2);' +
				'border:1rpx solid rgba(255,255,255,0.75);' +
				'box-shadow:0 8rpx 28rpx rgba(' +
				rgb +
				',0.1),0 2rpx 6rpx rgba(0,0,0,0.04),inset 0 1rpx 0 rgba(255,255,255,0.85);'
			)
		},
		activeLabelStyle() {
			const c =
				(this.themeObj && (this.themeObj.primaryDark || this.themeObj.primary)) ||
				'#558b2f'
			return 'color:' + c + ';font-weight:700;'
		},
		inactiveLabelStyle() {
			const c = (this.themeObj && this.themeObj.tabBarColor) || '#7a8a68'
			return 'color:' + c + ';font-weight:500;'
		}
	},
	mounted() {
		this.refreshTheme()
		uni.$on('__themeChanged', this.onThemeChanged)
	},
	beforeDestroy() {
		uni.$off('__themeChanged', this.onThemeChanged)
	},
	activated() {
		this.refreshTheme()
	},
	methods: {
		onThemeChanged(name) {
			this.themeName = name || theme.getThemeName()
		},
		refreshTheme() {
			this.themeName = theme.getThemeName()
		},
		activeIconPath(item) {
			if (!this.themeName || this.themeName === 'green') {
				return item.activeBase + '.png'
			}
			return item.activeBase + '-' + this.themeName + '.png'
		},
		iconSrc(index) {
			const item = this.list[index]
			if (this.current !== index) return item.iconPath
			return this.activeIconPath(item)
		},
		onTap(index) {
			if (this.current === index) return
			this.refreshTheme()
			uni.switchTab({ url: this.list[index].pagePath })
		}
	}
}
</script>

<style scoped>
.lingxi-tab-bar {
	position: fixed;
	left: 0;
	right: 0;
	bottom: 0;
	z-index: 9999;
	padding: 0 24rpx;
	padding-bottom: 5rpx;
	padding-bottom: calc(5rpx + constant(safe-area-inset-bottom));
	padding-bottom: calc(5rpx + env(safe-area-inset-bottom));
	pointer-events: none;
	box-sizing: border-box;
}

.lingxi-tab-inner {
	position: relative;
	pointer-events: auto;
	display: flex;
	align-items: center;
	justify-content: space-between;
	height: 112rpx;
	border-radius: 999rpx;
	padding: 0 12rpx;
	overflow: hidden;
	/* 背景/模糊由 innerStyle 驱动，不可用不透明实底挡住模糊 */
}

.lingxi-tab-item {
	position: relative;
	z-index: 1;
	flex: 1;
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	height: 100%;
}

.lingxi-tab-item:active {
	opacity: 0.85;
}

.lingxi-tab-icon {
	width: 40rpx;
	height: 40rpx;
	display: block;
}

.lingxi-tab-label {
	font-size: 20rpx;
	margin-top: 6rpx;
	line-height: 1.15;
	letter-spacing: 0.5rpx;
}
</style>
