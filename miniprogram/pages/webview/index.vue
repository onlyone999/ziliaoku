<template>
	<view class="page" :class="'theme-' + activeTheme" :style="pageBgStyle">
		<web-view :src="url" v-if="url"></web-view>
		<view class="loading" v-else>
			<text>加载中...</text>
		</view>
	</view>
</template>

<script>
export default {
	data() {
		return {
			url: ''
		};
	},
	onLoad(options) {
		if (options.url) {
			this.url = decodeURIComponent(options.url);
		}
		if (options.title) {
			uni.setNavigationBarTitle({ title: decodeURIComponent(options.title) });
		}
	},
	methods: {},
	onShareAppMessage() {
		return {
			title: '分享链接',
			path: `/pages/webview/index?url=${encodeURIComponent(this.url)}`
		};
	}
};
</script>

<style scoped>
.page {
	min-height: 100vh;
	background: #fff;
}
.loading {
	display: flex;
	align-items: center;
	justify-content: center;
	height: 100vh;
	font-size: 28rpx;
	color: #999;
}
</style>
