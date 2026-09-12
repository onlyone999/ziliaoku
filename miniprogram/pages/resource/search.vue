<template>
	<view class="page">
		<!-- 搜索头部 -->
		<view class="search-header">
			<view class="search-box">
				🔍
				<input
					class="search-input"
					v-model="keyword"
					placeholder="搜索资源、模板、素材..."
					:focus="true"
					confirm-type="search"
					@confirm="doSearch"
					@input="onInput"
				/>
				<text class="clear-btn" v-if="keyword" @tap="clearKeyword">✕</text>
			</view>
			<text class="cancel-btn" :style="tPri" @tap="goBack">取消</text>
		</view>

		<scroll-view scroll-y class="content-scroll">
			<!-- 搜索建议 (输入时显示) -->
			<view class="suggest-list" v-if="keyword.trim() && !hasSearched && suggestions.length > 0">
				<view
					class="suggest-item"
					v-for="(item, idx) in suggestions"
					:key="idx"
					@tap="tapSuggestion(item)"
				>
					🔍
					<rich-text class="suggest-text" :nodes="highlightSuggestion(item)"></rich-text>
				</view>
			</view>

			<!-- 搜索历史 + 热搜 (未搜索时显示) -->
			<view class="suggest-panel" v-if="!hasSearched && !(keyword.trim() && suggestions.length > 0)">
				<!-- 搜索历史 -->
				<view class="panel-section" v-if="historyList.length > 0">
					<view class="panel-header">
						<text class="panel-title">搜索历史</text>
						<text class="clear-all" @tap="clearHistory">清空</text>
					</view>
					<view class="tag-list">
						<view
							class="history-tag"
							v-for="(item, idx) in historyList"
							:key="idx"
							@tap="tapHistory(item)"
						>
							<text>{{ item }}</text>
						</view>
					</view>
				</view>

				<!-- 热门搜索 -->
				<view class="panel-section">
					<view class="panel-header">
						<text class="panel-title">热门搜索</text>
					</view>
					<view class="tag-list">
						<view
							class="hot-tag"
							v-for="(item, idx) in hotKeywords"
							:key="idx"
							@tap="tapHot(item)"
						>
							<text class="hot-rank" :class="{ top: idx < 3 }">{{ idx + 1 }}</text>
							<text>{{ item }}</text>
						</view>
					</view>
				</view>
			</view>

			<!-- 搜索结果 -->
			<view class="result-panel" v-if="hasSearched">
				<!-- 排序标签 -->
				<view class="sort-tabs">
					<view
						class="sort-tab"
						:class="{ active: sortBy === 'default' }"
						:style="sortBy === 'default' ? 'background:' + tc.primary + ';color:#fff;' : ''"
						@tap="changeSort('default')"
					>综合</view>
					<view
						class="sort-tab"
						:class="{ active: sortBy === 'newest' }"
						:style="sortBy === 'newest' ? 'background:' + tc.primary + ';color:#fff;' : ''"
						@tap="changeSort('newest')"
					>最新</view>
					<view
						class="sort-tab"
						:class="{ active: sortBy === 'hot' }"
						:style="sortBy === 'hot' ? 'background:' + tc.primary + ';color:#fff;' : ''"
						@tap="changeSort('hot')"
					>最热</view>
					<view
						class="sort-tab"
						:class="{ active: sortBy === 'price' }"
						:style="sortBy === 'price' ? 'background:' + tc.primary + ';color:#fff;' : ''"
						@tap="changeSort('price')"
					>价格</view>
				</view>

				<view class="result-header">
					<text class="result-count" :style="tPri">找到 {{ total }} 个相关资源</text>
				</view>

				<view class="result-list" v-if="results.length > 0">
					<view
						class="result-card"
						v-for="item in results"
						:key="item.id"
						@tap="goDetail(item)"
					>
						<image class="result-cover" :src="fixUrl(item.cover_url)" mode="aspectFill"></image>
						<view class="result-info">
							<rich-text class="result-title" :nodes="highlightText(item.title)"></rich-text>
							<view class="result-tags">
								<text class="result-badge" :style="tPri" v-if="item.category_name">{{ item.category_name }}</text>
							</view>
							<view class="result-bottom">
								<view class="result-stats">
									<text class="stat">{{ item.download_count }} 下载</text>
								</view>
								<text class="result-price" v-if="item.price > 0">¥{{ item.price }}</text>
								<text class="result-price free" :style="'background:' + tc.primary + ';color:#fff;'" v-else>免费</text>
							</view>
						</view>
					</view>
				</view>

				<!-- 分页栏 -->
				<view class="pager-bar" v-if="results.length > 0">
					<view class="pager-btn" :class="{ disabled: page <= 1 }" :style="page > 1 ? 'background:' + tc.primary + ';color:#fff;' : ''" @tap="goPage(page - 1)">
						<text>‹ 上一页</text>
					</view>
					<view class="pager-info">
						<text class="pager-current">{{ page }}/{{ totalPages || 1 }}</text>
						<text class="pager-total">共{{ total }}条</text>
					</view>
					<view class="pager-btn" :class="{ disabled: page >= totalPages }" :style="page < totalPages ? 'background:' + tc.primary + ';color:#fff;' : ''" @tap="goPage(page + 1)">
						<text>下一页 ›</text>
					</view>
				</view>

				<!-- 空结果 -->
				<view class="empty-result" v-if="!loading && results.length === 0">
					<text class="empty-icon">🔍</text>
					<text class="empty-title">未找到相关资源</text>
					<text class="empty-desc">换个关键词试试吧</text>
				</view>

				<!-- 加载状态 -->
				<view class="load-status">
					<text v-if="loading">搜索中...</text>
				</view>
			</view>
		</scroll-view>
	</view>
</template>

<script>
import http, { BASE_URL } from '@/utils/http.js';

const HISTORY_KEY = 'search_history';
const MAX_HISTORY = 20;

export default {
	data() {
		return {
			keyword: '',
			hasSearched: false,
			results: [],
			historyList: [],
			hotKeywords: [],
			suggestions: [],
			sortBy: 'default',
			page: 1,
			pageSize: 10,
			total: 0,
			totalPages: 0,
			loading: false,
			noMore: false,
			suggestTimer: null
		};
	},
	onLoad() {
		this.loadHistory();
		this.loadHotKeywords();
	},
	onUnload() {
		if (this.suggestTimer) {
			clearTimeout(this.suggestTimer);
		}
	},
	methods: {
		loadHistory() {
			try {
				const data = uni.getStorageSync(HISTORY_KEY);
				this.historyList = Array.isArray(data) ? data : [];
			} catch (e) {
				this.historyList = [];
			}
		},
		saveHistory(kw) {
			if (!kw || !kw.trim()) return;
			const list = this.historyList.filter(item => item !== kw);
			list.unshift(kw);
			this.historyList = list.slice(0, MAX_HISTORY);
			try {
				uni.setStorageSync(HISTORY_KEY, this.historyList);
			} catch (e) {}
		},
		clearHistory() {
			this.historyList = [];
			try {
				uni.removeStorageSync(HISTORY_KEY);
			} catch (e) {}
		},
		async loadHotKeywords() {
			try {
				const res = await http.get('/api/search/hot');
				if (res.code === 0) {
					const data = res.data || [];
					// Backend returns objects {keyword, search_count}, extract keyword strings
					this.hotKeywords = Array.isArray(data)
						? data.map(item => typeof item === 'string' ? item : (item.keyword || item.title || String(item)))
						: [];
				}
			} catch (e) {
				this.hotKeywords = ['PPT模板', '简历', '设计素材', 'Excel', 'Word文档', 'PDF工具', 'UI图标', '海报'];
			}
		},
		onInput() {
			if (!this.keyword.trim()) {
				this.hasSearched = false;
				this.results = [];
				this.suggestions = [];
				return;
			}
			// Debounce search suggestions 300ms
			if (this.suggestTimer) {
				clearTimeout(this.suggestTimer);
			}
			this.suggestTimer = setTimeout(() => {
				this.loadSuggestions();
			}, 300);
		},
		async loadSuggestions() {
			const kw = this.keyword.trim();
			if (!kw) {
				this.suggestions = [];
				return;
			}
			try {
				const res = await http.get('/api/search/suggest', { keyword: kw });
				if (res.code === 0) {
					this.suggestions = res.data || [];
				}
			} catch (e) {
				this.suggestions = [];
			}
		},
		clearKeyword() {
			this.keyword = '';
			this.hasSearched = false;
			this.results = [];
			this.suggestions = [];
		},
		doSearch() {
			const kw = this.keyword.trim();
			if (!kw) return;
			this.saveHistory(kw);
			this.page = 1;
			this.noMore = false;
			this.results = [];
			this.hasSearched = true;
			this.suggestions = [];
			this.searchRequest();
		},
		changeSort(sort) {
			if (this.sortBy === sort) return;
			this.sortBy = sort;
			this.page = 1;
			this.noMore = false;
			this.results = [];
			this.searchRequest();
		},
		async searchRequest() {
			if (this.loading) return;
			this.loading = true;
			try {
				const res = await http.get('/api/resource/search', {
					keyword: this.keyword.trim(),
					sort: this.sortBy,
					page: this.page,
					page_size: this.pageSize
				});
				if (res.code === 0) {
					const list = res.data.list || res.data || [];
					this.total = res.data.total || list.length;
					this.totalPages = res.data.total_pages || Math.ceil(this.total / this.pageSize) || 1;
					this.results = list;
					this.noMore = this.page >= this.totalPages;
				}
			} catch (e) {
				console.error('搜索失败', e);
			} finally {
				this.loading = false;
			}
		},
		goPage(p) {
			if (p < 1 || p > this.totalPages || this.loading) return;
			this.page = p;
			this.searchRequest();
			uni.pageScrollTo && uni.pageScrollTo({ scrollTop: 0, duration: 200 });
		},
		tapHistory(item) {
			this.keyword = item;
			this.doSearch();
		},
		tapHot(item) {
			this.keyword = item;
			this.doSearch();
		},
		tapSuggestion(item) {
			this.keyword = typeof item === 'string' ? item : (item.keyword || item.title || item);
			this.doSearch();
		},
		highlightText(text) {
			if (!text || !this.keyword.trim()) return text;
			const kw = this.keyword.trim().replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
			const regex = new RegExp(`(${kw})`, 'gi');
			return text.replace(regex, '<span style="color:' + this.tc.primary + ';font-weight:700;">$1</span>');
		},
		highlightSuggestion(text) {
			const str = typeof text === 'string' ? text : (text.keyword || text.title || String(text));
			return this.highlightText(str);
		},
		goDetail(item) {
			uni.navigateTo({ url: `/pages/resource/detail?id=${item.id}` });
		},
		fixUrl(url) {
			if (!url) return '';
			return url.startsWith('http') ? url : BASE_URL + url;
		},
		goBack() {
			uni.navigateBack();
		}
	},
	onShareAppMessage() {
		return {
			title: '海量资源搜索',
			path: '/pages/resource/search'
		};
	}
};
</script>

<style scoped>
.page {
	min-height: 100vh;
	background: linear-gradient(180deg, #f0f2ff 0%, #f5f6fa 200rpx);
}

/* 搜索头部 */
.search-header {
	display: flex;
	align-items: center;
	padding: 16rpx 24rpx;
	background: rgba(255, 255, 255, 0.75);
	backdrop-filter: blur(24px);
	-webkit-backdrop-filter: blur(24px);
	border-bottom: 1rpx solid rgba(240, 240, 240, 0.5);
	position: sticky;
	top: 0;
	z-index: 100;
}
.search-box {
	flex: 1;
	display: flex;
	align-items: center;
	height: 72rpx;
	padding: 0 24rpx;
	background: rgba(245, 247, 250, 0.8);
	border-radius: 36rpx;
	border: 2rpx solid rgba(46,213,115, 0.08);
	transition: all 0.3s ease;
}
.search-box:focus-within {
	border-color: rgba(46,213,115, 0.2);
	box-shadow: 0 0 0 4rpx rgba(46,213,115, 0.06);
}
.search-input {
	flex: 1;
	margin-left: 12rpx;
	font-size: 28rpx;
	color: #333;
}
.clear-btn {
	font-size: 28rpx;
	color: #bbb;
	padding: 10rpx;
	transition: all 0.2s ease;
}
.clear-btn:active {
	transform: scale(0.9);
}
.cancel-btn {
	margin-left: 20rpx;
	font-size: 28rpx;
	flex-shrink: 0;
	font-weight: 500;
}

/* 内容区 */
.content-scroll {
	height: calc(100vh - 104rpx);
}

/* 搜索建议 */
.suggest-list {
	background: #fff;
	margin: 12rpx 24rpx;
	border-radius: 24rpx;
	box-shadow:
		0 2rpx 8rpx rgba(46,213,115, 0.04),
		0 8rpx 24rpx rgba(0, 0, 0, 0.06);
	overflow: hidden;
}
.suggest-item {
	display: flex;
	align-items: center;
	padding: 20rpx 24rpx;
	border-bottom: 1rpx solid rgba(245, 245, 245, 0.8);
	gap: 16rpx;
	transition: all 0.2s ease;
	position: relative;
}
.suggest-item:last-child {
	border-bottom: none;
}
/* 搜索建议项 active 增加左侧紫色边条 */
.suggest-item:active {
	background: rgba(46,213,115, 0.03);
	transform: scale(0.97);
}
.suggest-item:active::before {
	content: '';
	position: absolute;
	left: 0;
	top: 20%;
	height: 60%;
	width: 3rpx;
	background: linear-gradient(180deg, #26c67a, #2ed573);
	border-radius: 0 2rpx 2rpx 0;
	box-shadow: 0 0 6rpx rgba(46,213,115, 0.4);
	animation: slideIn 0.2s ease-out;
}
@keyframes slideIn {
	from { height: 0; top: 50%; opacity: 0; }
	to { height: 60%; top: 20%; opacity: 1; }
}
.suggest-text {
	font-size: 28rpx;
	color: #333;
	flex: 1;
}

/* 建议面板 */
.suggest-panel {
	padding: 24rpx;
}
.panel-section {
	margin-bottom: 40rpx;
}
.panel-header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-bottom: 20rpx;
}
.panel-title {
	font-size: 30rpx;
	font-weight: 700;
	color: #1a1a2e;
}
.clear-all {
	font-size: 24rpx;
	color: #bbb;
	transition: color 0.2s ease;
}
.clear-all:active {
}
.tag-list {
	display: flex;
	flex-wrap: wrap;
	gap: 16rpx;
}
.history-tag {
	padding: 12rpx 28rpx;
	background: #fff;
	border-radius: 30rpx;
	font-size: 26rpx;
	color: #666;
	box-shadow:
		0 2rpx 6rpx rgba(46,213,115, 0.04),
		0 4rpx 12rpx rgba(0, 0, 0, 0.04);
	transition: all 0.2s ease;
}
.history-tag:active {
	transform: scale(0.97);
	box-shadow:
		0 1rpx 3rpx rgba(46,213,115, 0.04),
		0 2rpx 6rpx rgba(0, 0, 0, 0.03);
}
.hot-tag {
	display: flex;
	align-items: center;
	width: 100%;
	padding: 16rpx 24rpx;
	background: #fff;
	border-radius: 24rpx;
	margin-bottom: 12rpx;
	font-size: 28rpx;
	color: #333;
	box-shadow:
		0 2rpx 6rpx rgba(46,213,115, 0.03),
		0 4rpx 12rpx rgba(0, 0, 0, 0.04);
	transition: all 0.2s ease;
}
.hot-tag:active {
	transform: scale(0.97);
}
.hot-rank {
	font-size: 24rpx;
	color: #bbb;
	margin-right: 20rpx;
	width: 40rpx;
	text-align: center;
	font-weight: 700;
}
/* 热搜排名前3渐变文字效果 */
.hot-rank.top {
	background: linear-gradient(135deg, #ff4757, #ff6b81);
	background-size: 200% 100%;
	animation: hotRankShimmer 3s ease-in-out infinite;
	text-shadow: none;
}
@keyframes hotRankShimmer {
	0% { background-position: 0% 0; }
	50% { background-position: 100% 0; }
	100% { background-position: 0% 0; }
}

/* 搜索结果 */
.result-panel {
	padding: 16rpx 24rpx;
}

/* 排序标签 */
.sort-tabs {
	display: flex;
	background: #fff;
	border-radius: 24rpx;
	padding: 8rpx;
	margin-bottom: 16rpx;
	box-shadow:
		0 2rpx 8rpx rgba(46,213,115, 0.04),
		0 4rpx 16rpx rgba(0, 0, 0, 0.04);
}
.sort-tab {
	flex: 1;
	text-align: center;
	padding: 16rpx 0;
	font-size: 26rpx;
	color: #999;
	border-radius: 18rpx;
	transition: all 0.3s ease;
	background-size: 200% 100%;
	background-image: linear-gradient(135deg, transparent 0%, transparent 50%, rgba(46,213,115, 0.05) 100%);
}
.sort-tab:active {
	background-position: 100% 0;
}
.sort-tab.active {
	color: #fff;
	background: #2ed573;
	font-weight: 700;
	box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.15);
	transform: scale(0.97);
}
@keyframes tagGradientShift {
	0% { background-position: 0% 0; }
	50% { background-position: 100% 0; }
	100% { background-position: 0% 0; }
}

.result-header {
	padding: 12rpx 0;
}
/* 搜索结果数量标签胶囊样式 */
.result-count {
	font-size: 24rpx;
	background: rgba(46,213,115, 0.08);
	padding: 6rpx 24rpx;
	border-radius: 30rpx;
	display: inline-block;
	font-weight: 500;
	color: #27ae60;
	border: 1rpx solid rgba(46,213,115, 0.15);
}
.result-card {
	display: flex;
	background: #fff;
	border-radius: 24rpx;
	margin-bottom: 20rpx;
	overflow: hidden;
	box-shadow:
		0 2rpx 8rpx rgba(46,213,115, 0.04),
		0 8rpx 24rpx rgba(0, 0, 0, 0.06);
	transition: all 0.3s ease;
	position: relative;
}
/* 卡片顶部渐变装饰线 */
.result-card::before {
	content: '';
	position: absolute;
	top: 0;
	left: 10%;
	right: 10%;
	height: 2rpx;
	background: linear-gradient(90deg, transparent 0%, #26c67a 30%, #2ed573 50%, #26c67a 70%, transparent 100%);
	border-radius: 1rpx;
	filter: blur(1rpx);
	box-shadow: 0 0 6rpx rgba(46,213,115, 0.4), 0 0 6rpx rgba(46,213,115, 0.15);
	opacity: 0.8;
	z-index: 1;
}
.result-card:active {
	transform: scale(0.97);
	box-shadow:
		0 1rpx 4rpx rgba(46,213,115, 0.04),
		0 4rpx 12rpx rgba(0, 0, 0, 0.04);
}
/* 封面图片 hover 缩放 */
.result-cover {
	width: 220rpx;
	height: 180rpx;
	flex-shrink: 0;
	border-radius: 24rpx 0 0 24rpx;
	border-right: 2rpx solid rgba(46,213,115, 0.06);
	transition: transform 0.4s ease;
}
.result-card:active .result-cover {
	transform: scale(1.02);
}
.result-info {
	flex: 1;
	padding: 20rpx 24rpx;
	display: flex;
	flex-direction: column;
	justify-content: space-between;
	min-width: 0;
}
.result-title {
	font-size: 28rpx;
	color: #1a1a2e;
	font-weight: 600;
	overflow: hidden;
	text-overflow: ellipsis;
	display: -webkit-box;
	-webkit-line-clamp: 2;
	-webkit-box-orient: vertical;
	line-height: 1.5;
}
.result-tags {
	margin-top: 8rpx;
}
.result-badge {
	display: inline-block;
	font-size: 20rpx;
	background: rgba(46,213,115, 0.08);
	padding: 4rpx 16rpx;
	border-radius: 16rpx;
	font-weight: 500;
	color: #27ae60;
	border: 1rpx solid rgba(46,213,115, 0.12);
}
.result-badge:active {
	animation: badgeGradientShift 0.6s ease;
}
@keyframes badgeGradientShift {
	0% { background-position: 0% 0; }
	100% { background-position: 100% 0; }
}
.result-bottom {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-top: auto;
}
.result-stats {
	display: flex;
}
.stat {
	font-size: 22rpx;
	color: #aaa;
}
.result-price {
	font-size: 28rpx;
	color: #ff6b6b;
	font-weight: 700;
	background: linear-gradient(135deg, #ff6b6b, #ff8e8e);
}
.result-price.free {
	font-size: 24rpx;
	background: #2ed573;
	color: #fff;
}

/* 分页栏 */
.pager-bar {
	display: flex;
	align-items: center;
	justify-content: space-between;
	padding: 20rpx 24rpx;
	margin: 0 0 20rpx;
	background: #fff;
	border-radius: 16rpx;
	box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.06);
}
.pager-btn {
	padding: 12rpx 28rpx;
	border-radius: 24rpx;
	background: #e8e8e8;
	font-size: 24rpx;
	color: #333;
	font-weight: 600;
	transition: all 0.2s;
}
.pager-btn.disabled {
	opacity: 0.4;
	pointer-events: none;
}
.pager-btn:active {
	transform: scale(0.96);
}
.pager-info {
	display: flex;
	flex-direction: column;
	align-items: center;
}
.pager-current {
	font-size: 26rpx;
	color: #333;
	font-weight: 700;
}
.pager-total {
	font-size: 20rpx;
	color: #aaa;
	margin-top: 4rpx;
}

/* 空结果 */
.empty-result {
	display: flex;
	flex-direction: column;
	align-items: center;
	padding: 120rpx 0;
	position: relative;
}
.empty-result::before {
	content: '';
	position: absolute;
	top: 60rpx;
	width: 280rpx;
	height: 280rpx;
	border-radius: 50%;
	background: linear-gradient(135deg, rgba(46,213,115, 0.06), rgba(139, 127, 255, 0.1));
}
.empty-icon {
	font-size: 100rpx;
	margin-bottom: 24rpx;
	position: relative;
	z-index: 1;
	animation: breathe 3s ease-in-out infinite;
}
@keyframes breathe {
	0%, 100% { transform: scale(1); }
	50% { transform: scale(1.04); }
}
.empty-title {
	font-size: 32rpx;
	color: #333;
	font-weight: 600;
	margin-bottom: 12rpx;
	position: relative;
	z-index: 1;
}
.empty-desc {
	font-size: 26rpx;
	color: #bbb;
	position: relative;
	z-index: 1;
}

.load-status {
	text-align: center;
	padding: 30rpx 0;
	font-size: 24rpx;
	color: #bbb;
}
.load-status text {
	display: inline-block;
	background: linear-gradient(90deg, #bbb 0%, #2ed573 40%, #bbb 80%);
	background-size: 200% 100%;
	animation: loadShimmer 2s linear infinite;
}
@keyframes loadShimmer {
	0% { background-position: 200% 0; }
	100% { background-position: -200% 0; }
}
@keyframes loadPulse {
	0%, 100% { opacity: 0.5; }
	50% { opacity: 1; }
}
</style>
 
