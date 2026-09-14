<template>
	<view class="page" :class="'theme-' + activeTheme" :style="pageBgStyle">
		<scroll-view scroll-y class="scroll" :show-scrollbar="false">
			<view v-if="list.length === 0 && !loading" class="empty">
				<text class="empty-text">暂无排行数据</text>
			</view>
			<view class="list" v-else>
				<view class="rank-item" v-for="(item, idx) in list" :key="item.id">
					<view class="rank-num" :class="'rank-' + (idx < 3 ? idx + 1 : 'other')">
						<text v-if="idx === 0">🥇</text>
						<text v-else-if="idx === 1">🥈</text>
						<text v-else-if="idx === 2">🥉</text>
						<text v-else>{{ idx + 1 }}</text>
					</view>
					<image class="rank-avatar" :src="fixUrl(item.avatar_url)" mode="aspectFill"></image>
					<view class="rank-info">
						<text class="rank-name">{{ item.nickname || '用户' + item.id }}</text>
					</view>
					<text class="rank-points" :style="'color:' + tc.primary + ';'">{{ item.points }} 积分</text>
				</view>
			</view>
		</scroll-view>
	</view>
</template>

<script>
import http, { BASE_URL } from '@/utils/http.js';

export default {
	data() {
		return { list: [], loading: false };
	},
	onLoad() {
		this.loadData();
	},
	methods: {
		async loadData() {
			this.loading = true;
			try {
				const res = await http.get('/api/points/ranking', { page_size: 50 }, { silent: true });
				if (res.code === 0) this.list = res.data || [];
			} catch (e) {} finally {
				this.loading = false;
			}
		},
		fixUrl(url) {
			if (!url) return '/static/default-avatar.png';
			if (url.startsWith('http')) return url;
			return BASE_URL + url;
		}
	}
};
</script>

<style scoped>
.page { height: 100vh; background: transparent; overflow: hidden; display: flex; flex-direction: column; }
page { overflow: hidden; height: 100vh; }
.scroll { flex: 1; height: 0; padding: 24rpx 28rpx; box-sizing: border-box; }
.rank-item {
	display: flex;
	align-items: center;
	background: #fff;
	border-radius: 28rpx;
	padding: 20rpx 24rpx;
	margin-bottom: 12rpx;
	box-shadow: 0 2rpx 8rpx rgba(0,0,0,0.03);
	box-sizing: border-box;
}
.rank-num {
	width: 48rpx;
	text-align: center;
	font-size: 28rpx;
	font-weight: 700;
	color: #999;
}
.rank-1, .rank-2, .rank-3 { font-size: 36rpx; }
.rank-avatar {
	width: 72rpx;
	height: 72rpx;
	border-radius: 50%;
	margin: 0 16rpx;
	background: #eee;
}
.rank-info { flex: 1; }
.rank-name { font-size: 28rpx; color: #333; font-weight: 600; }
.rank-points { font-size: 28rpx; font-weight: 700; }
.empty { padding: 120rpx 0; text-align: center; }
.empty-text { color: #ccc; font-size: 28rpx; }
</style>
