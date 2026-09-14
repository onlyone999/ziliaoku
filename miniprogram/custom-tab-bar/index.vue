<template>
	<view class="lingxi-tab-bar" v-if="show">
		<view class="lingxi-tab-inner">
			<view
				class="lingxi-tab-item"
				v-for="(item, index) in list"
				:key="index"
				@tap="onTap(index)"
			>
				<view class="lingxi-tab-icon-box" :class="{ active: selected === index }">
					<image
						class="lingxi-tab-icon"
						:src="selected === index ? item.selectedIconPath : item.iconPath"
						mode="aspectFit"
					></image>
				</view>
				<text class="lingxi-tab-label" :class="{ active: selected === index }">{{ item.text }}</text>
			</view>
		</view>
	</view>
</template>

<script>
export default {
	data() {
		return {
			show: true,
			selected: 0,
			list: [
				{
					pagePath: '/pages/index/index',
					text: '首页',
					iconPath: '/static/icons/home.png',
					selectedIconPath: '/static/icons/home-active.png'
				},
				{
					pagePath: '/pages/category/list',
					text: '分类',
					iconPath: '/static/icons/category.png',
					selectedIconPath: '/static/icons/category-active.png'
				},
				{
					pagePath: '/pages/user/index',
					text: '我的',
					iconPath: '/static/icons/user.png',
					selectedIconPath: '/static/icons/user-active.png'
				}
			]
		};
	},
	methods: {
		onTap(index) {
			if (this.selected === index) return;
			this.selected = index;
			uni.switchTab({ url: this.list[index].pagePath });
		}
	}
};
</script>

<style>
.lingxi-tab-bar {
	position: fixed;
	left: 0;
	right: 0;
	bottom: 0;
	z-index: 9999;
	padding: 0 24rpx;
	padding-bottom: 16rpx;
	padding-bottom: calc(16rpx + constant(safe-area-inset-bottom));
	padding-bottom: calc(16rpx + env(safe-area-inset-bottom));
	pointer-events: none;
	box-sizing: border-box;
	background: linear-gradient(180deg, rgba(240, 247, 230, 0) 0%, rgba(240, 247, 230, 0.9) 35%, #f0f7e6 100%);
}

.lingxi-tab-inner {
	pointer-events: auto;
	display: flex;
	align-items: center;
	justify-content: space-around;
	height: 110rpx;
	background: #ffffff;
	border-radius: 999rpx;
	box-shadow: 0 8rpx 32rpx rgba(90, 120, 50, 0.14), 0 2rpx 8rpx rgba(90, 120, 50, 0.06);
	padding: 0 8rpx;
}

.lingxi-tab-item {
	flex: 1;
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	height: 100%;
}

.lingxi-tab-icon-box {
	width: 56rpx;
	height: 56rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	border-radius: 18rpx;
}

.lingxi-tab-icon-box.active {
	background: rgba(124, 179, 66, 0.14);
}

.lingxi-tab-icon {
	width: 40rpx;
	height: 40rpx;
	display: block;
}

.lingxi-tab-label {
	font-size: 20rpx;
	margin-top: 2rpx;
	color: #6b7a5a;
	font-weight: 500;
	line-height: 1.2;
}

.lingxi-tab-label.active {
	color: #7cb342;
	font-weight: 700;
}
</style>
