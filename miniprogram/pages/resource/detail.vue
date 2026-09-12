<template>
	<view class="page">
		<scroll-view scroll-y class="main-scroll">
			<!-- 图片画廊 -->
			<view class="gallery-wrap">
				<swiper
					class="gallery-swiper"
					:indicator-dots="false"
					:autoplay="false"
					:circular="true"
					@change="onSwiperChange"
				>
					<swiper-item v-for="(img, idx) in images" :key="idx">
						<image class="gallery-img" :src="img" mode="aspectFill" @tap="previewImage(idx)"></image>
					</swiper-item>
				</swiper>
				<!-- 渐变遮罩 -->
				<view class="gallery-gradient-top"></view>
				<view class="gallery-gradient-bottom"></view>
				<!-- 底部分页指示器 -->
				<view class="gallery-indicator" v-if="images.length > 1">
					<view class="indicator-track">
						<view class="indicator-bar" :style="{ width: ((currentImageIdx + 1) / images.length * 100) + '%' }"></view>
					</view>
				</view>
				<!-- 悬浮标签 -->
				<view class="gallery-tags" v-if="resource.category_name || (resource.price !== undefined && resource.price <= 0)">
					<view class="gallery-tag" :style="'background:rgba(' + tc.rgbPrimary + ',0.55);color:#fff;'" v-if="resource.category_name">
						<text class="tag-dot"></text>{{ resource.category_name }}
					</view>
					<view class="gallery-tag free" :style="'background:' + tc.badgeBg + ';color:' + tc.primary + ';border-color:' + tc.badgeBorder + ';'" v-if="resource.price !== undefined && resource.price <= 0">
						<text class="tag-spark">✦</text> 免费
					</view>
				</view>
			</view>

			<!-- 标题信息卡片 -->
			<view class="hero-card">
				<!-- 装饰弧线 -->
				<view class="hero-deco"></view>
				<view class="hero-badges" v-if="resource.is_hot || resource.is_new || resource.is_recommended">
					<view class="hero-badge hot" :style="'background:' + tc.badgeBg + ';color:' + tc.primary + ';border:2rpx solid ' + tc.badgeBorder + ';'" v-if="resource.is_hot">
						<text class="badge-emoji">🔥</text> 热门
					</view>
					<view class="hero-badge new" :style="'background:' + tc.badgeBg + ';color:' + tc.primary + ';border:2rpx solid ' + tc.badgeBorder + ';'" v-if="resource.is_new">
						<text class="badge-emoji">🆕</text> 新品
					</view>
					<view class="hero-badge rec" :style="'background:' + tc.badgeBg + ';color:' + tc.primary + ';border:2rpx solid ' + tc.badgeBorder + ';'" v-if="resource.is_recommended">
						<text class="badge-emoji">⭐</text> 推荐
					</view>
				</view>
				<text class="hero-title">{{ resource.title }}</text>
				<view class="hero-meta">
					<view class="hero-stats">
						<view class="hero-stat">
							<view class="stat-icon-circle view">
								<text class="stat-icon">👁</text>
							</view>
							<text class="stat-num" :style="'color:' + tc.primary + ';'">{{ formatCount(resource.view_count || 0) }}</text>
						</view>
						<view class="stat-divider"></view>
						<view class="hero-stat">
							<view class="stat-icon-circle download" :style="'background:rgba(' + tc.rgbPrimary + ',0.08);'">
								<text class="stat-icon">↓</text>
							</view>
							<text class="stat-num" :style="'color:' + tc.primary + ';'">{{ formatCount(resource.download_count || 0) }}</text>
						</view>
						<view class="stat-divider"></view>
						<view class="hero-stat">
							<view class="stat-icon-circle like">
								<text class="stat-icon">❤</text>
							</view>
							<text class="stat-num" :style="'color:' + tc.primary + ';'">{{ formatCount(resource.like_count || 0) }}</text>
						</view>
					</view>
					<!-- 评分 -->
					<view class="hero-rating" v-if="resource.rating">
						<view class="rating-glow"></view>
						<text class="rating-star">★</text>
						<text class="rating-value">{{ resource.rating }}</text>
						<text class="rating-count">({{ comments.length }})</text>
					</view>
				</view>
			</view>

			<!-- 价格区块 -->
			<view class="price-card" v-if="resource.price > 0">
				<view class="price-left">
					<view class="price-row">
						<text class="price-symbol">¥</text>
						<text class="price-value">{{ resource.price }}</text>
						<text class="price-original" v-if="resource.original_price && resource.original_price > resource.price">
							原价 ¥{{ resource.original_price }}
						</text>
					</view>
					<view class="price-badges">
						<view class="badge badge-discount" :style="'background:' + tc.primary + ';'" v-if="resource.original_price && resource.original_price > resource.price">
							<text class="badge-flash">⚡</text> {{ Math.round((1 - resource.price / resource.original_price) * 100) }}% OFF
						</view>
						<view class="badge badge-member" :style="'background:' + tc.primary + ';'" v-if="resource.price_type === 'member_free'">
							<text class="badge-crown">👑</text> {{ vipFreeText }}
						</view>
						<view class="badge badge-vip" v-if="resource.vip_price !== undefined && resource.vip_price < resource.price">
							<text class="badge-star-icon">✦</text> VIP ¥{{ resource.vip_price }}
						</view>
					</view>
				</view>
				<view class="price-right">
					<view class="price-pulse"></view>
				</view>
				<!-- 装饰粒子 -->
				<view class="price-particle p1"></view>
				<view class="price-particle p2"></view>
			</view>
			<view class="price-card free-card" :style="'background:' + tc.tintLight + ';border-color:rgba(' + tc.rgbPrimary + ',0.15);'" v-else>
				<view class="free-card-bar" :style="'background:' + tc.primary + ';'"></view>
				<view class="free-icon-wrap" :style="'background:rgba(' + tc.rgbPrimary + ',0.12);'">
					<view class="free-icon-ring"></view>
					<text class="free-icon">🎉</text>
				</view>
				<view class="free-info">
					<text class="free-title" :style="'color:' + tc.primary + ';'">免费资源</text>
					<text class="free-desc" :style="'color:' + tc.primaryDark + ';'">无需付费，直接下载使用</text>
				</view>
				<view class="free-sparkles">
					<text class="sparkle s1">✦</text>
					<text class="sparkle s2">✧</text>
					<text class="sparkle s3">✦</text>
				</view>
			</view>

			<!-- 投票区块 -->
			<view class="vote-card">
				<view class="vote-btn" :class="{ active: userVote === 'up' }" :style="userVote === 'up' ? 'background:rgba(' + tc.rgbPrimary + ',0.08);border:2rpx solid rgba(' + tc.rgbPrimary + ',0.25);' : ''" @tap="toggleVote('up')">
					<view class="vote-icon-wrap up" :style="'background:rgba(' + tc.rgbPrimary + ',0.06);'">
						<text class="vote-icon">👍</text>
					</view>
					<text class="vote-count" :style="userVote === 'up' ? tPri : ''">{{ upVotes }}</text>
				</view>
				<view class="vote-center">
					<view class="vote-progress">
						<view class="vote-fill" :style="{ width: votePercent + '%', background: tc.primary }"></view>
					</view>
					<text class="vote-text">觉得有用？</text>
				</view>
				<view class="vote-btn" :class="{ active: userVote === 'down' }" @tap="toggleVote('down')">
					<view class="vote-icon-wrap down">
						<text class="vote-icon">👎</text>
					</view>
					<text class="vote-count" :style="userVote === 'down' ? tPri : ''">{{ downVotes }}</text>
				</view>
			</view>

			<!-- 文件信息 -->
			<view class="file-card" v-if="resource.file_type">
				<view class="file-bg-pattern"></view>
				<view class="file-icon-wrap" :style="'background:' + tc.primary + ';'">
					<view class="file-icon-glow"></view>
					<text class="file-type-icon">{{ fileTypeIcon }}</text>
				</view>
				<view class="file-info">
					<text class="file-name">{{ resource.file_name || resource.title }}</text>
					<view class="file-chips">
						<view class="file-chip type" :style="tPri" v-if="resource.file_type">
							<text class="chip-dot"></text>
							<text>{{ resource.file_type }}</text>
						</view>
						<view class="file-chip size" :style="tPri" v-if="resource.file_size">
							<text class="chip-dot"></text>
							<text>{{ formatSize(resource.file_size) }}</text>
						</view>
						<view class="file-chip format" :style="tPri" v-if="resource.file_format">
							<text class="chip-dot"></text>
							<text>{{ resource.file_format }}</text>
						</view>
					</view>
				</view>
			</view>

			<!-- 资源预览 -->
			<view class="section-card preview-card" v-if="hasPreview">
				<view class="section-header">
					<view class="section-header-left">
						<view class="section-bar" :style="'background:' + tc.primary + ';'"></view>
						<text class="section-title">资源预览</text>
					</view>
					<view class="section-hint-badge" :style="'background:' + tc.primary + ';'">
						<text class="hint-eye">👁</text>
						<text class="section-hint">下载前先预览</text>
					</view>
				</view>
				<!-- PDF预览 -->
				<view class="preview-pdf" v-if="previewType === 'pdf' && resource.preview_images">
					<image
						class="pdf-page-img"
						v-for="(pimg, pidx) in resource.preview_images"
						:key="pidx"
						:src="pimg"
						mode="widthFix"
						@tap="previewPreviewImage(pidx)"
					></image>
				</view>
				<!-- 图片预览 -->
				<view class="preview-images" v-else-if="previewType === 'image' && resource.preview_images">
					<scroll-view scroll-x class="preview-img-scroll">
						<view class="preview-img-list">
							<view class="preview-img-item" v-for="(pimg, pidx) in resource.preview_images" :key="pidx">
								<image
									class="preview-thumb"
									:src="pimg"
									mode="aspectFill"
									@tap="previewPreviewImage(pidx)"
								></image>
								<view class="preview-img-overlay">
									<text class="preview-zoom-icon">🔍</text>
								</view>
							</view>
						</view>
					</scroll-view>
				</view>
				<!-- 代码预览 -->
				<view class="preview-code" v-else-if="previewType === 'code' && resource.preview_code">
					<view class="code-header">
						<view class="code-dots">
							<view class="dot dot-r"></view>
							<view class="dot dot-y"></view>
							<view class="dot dot-g"></view>
						</view>
						<view class="code-header-right">
							<view class="code-lang-badge" :style="'background:' + tc.primary + ';'">
								<text>{{ resource.file_format || 'CODE' }}</text>
							</view>
							<view class="code-copy-btn" @tap="copyCode">
								<text class="copy-icon">📋</text>
							</view>
						</view>
					</view>
					<scroll-view scroll-x class="code-scroll">
						<text class="code-content">{{ resource.preview_code }}</text>
					</scroll-view>
				</view>
				<!-- 通用文本预览 -->
				<view class="preview-text" v-else-if="previewType === 'text' && resource.preview_text">
					<text class="preview-text-content">{{ resource.preview_text }}</text>
					<view class="preview-fade"></view>
				</view>
			</view>

			<!-- 描述 -->
			<view class="section-card">
				<view class="section-header">
					<view class="section-header-left">
						<view class="section-bar" :style="'background:' + tc.primary + ';'"></view>
						<text class="section-title">资源描述</text>
					</view>
				</view>
				<rich-text class="desc-content" :nodes="resource.description || '暂无描述'"></rich-text>
			</view>

			<!-- 标签 -->
			<view class="section-card" v-if="resource.tags && resource.tags.length > 0">
				<view class="section-header">
					<view class="section-header-left">
						<view class="section-bar" :style="'background:' + tc.primary + ';'"></view>
						<text class="section-title">标签</text>
					</view>
				</view>
				<view class="tags-wrap">
					<view class="tag-item" v-for="(tag, idx) in resource.tags" :key="idx" :class="'tag-color-' + (idx % 4)">
						<text class="tag-hash" :style="idx % 4 === 0 ? tPri : ''">#</text>
						<text class="tag-text" :style="idx % 4 === 0 ? tPri : ''">{{ tag.name || tag }}</text>
					</view>
				</view>
			</view>

			<!-- 评论区 -->
			<view class="section-card comment-card" v-if="commentEnabled">
				<view class="section-header">
					<view class="section-header-left">
						<view class="section-bar" :style="'background:' + tc.primary + ';'"></view>
						<text class="section-title">用户评价</text>
					</view>
					<view class="comment-avg" v-if="resource.rating">
						<text class="avg-star">★</text>
						<text class="avg-num">{{ resource.rating }}</text>
					</view>
				</view>
				<!-- 评分分布条 -->
				<view class="rating-bars" v-if="resource.rating">
					<view class="rating-bar-row" v-for="n in 5" :key="n">
						<text class="bar-label">{{ 6 - n }}星</text>
						<view class="bar-track">
							<view class="bar-fill" :style="{ width: getRatingPercent(6 - n) + '%' }"></view>
						</view>
						<text class="bar-pct">{{ getRatingPercent(6 - n) }}%</text>
					</view>
				</view>
				<view class="comment-list" v-if="comments.length > 0">
					<view class="comment-item" v-for="item in comments" :key="item.id">
						<view class="comment-header">
							<view class="avatar-ring">
								<image class="comment-avatar" :src="item.avatar || '/static/default-avatar.png'" mode="aspectFill"></image>
							</view>
							<view class="comment-user">
								<view class="comment-user-row">
									<text class="comment-name">{{ item.nickname }}</text>
									<view class="comment-stars">
										<text v-for="n in 5" :key="n" class="star" :class="{ active: n <= item.rating }">★</text>
									</view>
								</view>
								<text class="comment-time">{{ formatCommentTime(item.created_at) }}</text>
							</view>
						</view>
						<text class="comment-content">{{ item.content }}</text>
						<view class="comment-actions">
							<view class="comment-action-btn" @tap="likeComment(item)">
								<text class="action-icon">👍</text>
								<text class="action-count" v-if="item.like_count">{{ item.like_count }}</text>
							</view>
							<view class="comment-action-btn" @tap="replyComment(item)">
								<text class="action-icon">💬</text>
								<text class="action-label">回复</text>
							</view>
						</view>
					</view>
				</view>
				<view class="comment-empty" v-else>
					<view class="empty-bubble">
						<text class="empty-icon">💬</text>
					</view>
					<text class="empty-text">暂无评价，快来抢沙发~</text>
					<text class="empty-hint">成为第一个评价的人吧</text>
				</view>

				<!-- 发表评论 -->
				<view class="comment-form">
					<view class="form-divider"></view>
					<text class="form-title">发表评价</text>
					<view class="form-rating">
						<text class="form-rating-label">评分</text>
						<view class="form-stars">
							<text
								v-for="n in 5"
								:key="n"
								class="form-star"
								:class="{ active: n <= commentForm.rating }"
								@tap="commentForm.rating = n"
							>★</text>
						</view>
						<view class="form-rating-tag" :class="'rating-level-' + commentForm.rating" :style="commentForm.rating === 5 ? tPri : ''">
							<text>{{ ratingHintText }}</text>
						</view>
					</view>
					<view class="form-textarea-wrap">
						<textarea
							class="form-textarea"
							v-model="commentForm.content"
							placeholder="分享您对这个资源的看法..."
							maxlength="300"
							:auto-height="false"
						></textarea>
						<view class="form-textarea-footer">
							<text class="form-char-count" :class="{ warn: commentForm.content.length > 250 }">{{ commentForm.content.length }}/300</text>
						</view>
					</view>
					<view class="form-submit-btn" :class="{ disabled: !canSubmitComment }" :style="'background:' + tc.primary + ';'" @tap="submitComment">
						<text class="submit-icon">✉</text>
						<text>提交评价</text>
					</view>
				</view>
			</view>

			<!-- 相关资源 -->
			<view class="section-card" v-if="relatedResources.length > 0">
				<view class="section-header">
					<view class="section-header-left">
						<view class="section-bar" :style="'background:' + tc.primary + ';'"></view>
						<text class="section-title">相关资源</text>
					</view>
					<text class="section-more" :style="tPri" @tap="goMore">更多 ></text>
				</view>
				<scroll-view scroll-x class="related-scroll">
					<view class="related-list">
						<view
							class="related-card"
							v-for="item in relatedResources"
							:key="item.id"
							@tap="goRelated(item)"
						>
							<view class="related-cover-wrap">
								<image class="related-cover" :src="fixUrl(item.cover_url)" mode="aspectFill"></image>
								<view class="related-overlay">
									<text class="related-play">→</text>
								</view>
								<view class="related-badge" :style="'background:' + tc.primary + ';'" v-if="item.price <= 0">
									<text>免费</text>
								</view>
							</view>
							<view class="related-info">
								<text class="related-title">{{ item.title }}</text>
								<view class="related-bottom">
									<text class="related-price" v-if="item.price > 0">¥{{ item.price }}</text>
									<text class="related-price free" :style="'color:' + tc.primary + ';'" v-else>免费</text>
									<view class="related-stats">
										<text class="related-downloads">↓ {{ formatCount(item.download_count || 0) }}</text>
									</view>
								</view>
							</view>
						</view>
					</view>
				</scroll-view>
			</view>

			<view style="height: 180rpx;"></view>
		</scroll-view>

		<!-- 固定返回按钮 -->
		<view class="fixed-back" @tap="goBack">
			<text class="back-icon">‹</text>
		</view>

		<!-- 下载进度条浮层 -->
		<view class="download-progress-mask" v-if="downloading">
			<view class="progress-card">
				<view class="progress-icon-wrap">
					<view class="progress-ring-anim"></view>
					<text class="progress-download-icon">↓</text>
				</view>
				<text class="progress-title">正在下载...</text>
				<view class="progress-bar-bg">
					<view class="progress-bar-fill" :style="{ width: downloadPercent + '%' }"></view>
					<view class="progress-bar-glow" :style="{ left: (downloadPercent - 2) + '%' }"></view>
				</view>
				<view class="progress-info">
					<text class="progress-text" :style="tPri">{{ downloadPercent }}%</text>
					<text class="progress-speed" v-if="downloadPercent > 0 && downloadPercent < 100">下载中</text>
				</view>
			</view>
		</view>

		<!-- 底部操作栏 -->
		<view class="bottom-bar">
			<view class="bar-glass"></view>
			<view class="bar-left">
				<view class="bar-btn" @tap="toggleFavorite">
					<view class="bar-icon-circle" :class="{ active: isFavorited }">
						<text class="bar-icon" :class="{ active: isFavorited }">{{ isFavorited ? '❤' : '♡' }}</text>
					</view>
					<text class="bar-label">收藏</text>
				</view>
				<button class="bar-btn share-btn" open-type="share">
					<view class="bar-icon-circle">
						<text class="bar-icon">↗</text>
					</view>
					<text class="bar-label">分享</text>
				</button>
			</view>
			<view class="bar-right">
				<view class="action-btn free-btn" :style="'background:' + tc.primary + ';color:#fff;border-color:' + tc.primary + ';'" v-if="resource.price <= 0" @tap="handleDownload">
					<text class="action-btn-icon">↓</text>
					<text>免费下载</text>
				</view>
				<view class="action-btn buy-btn" v-else-if="!isPurchased && !isMemberFree" @tap="handleBuy">
					<text class="action-btn-icon">🛒</text>
					<text>¥{{ resource.price }} 立即购买</text>
				</view>
				<view class="action-btn member-btn" v-else-if="!isPurchased && isMemberFree && !isVip" @tap="goVip">
					<text class="action-btn-icon">👑</text>
					<text>开通会员免费下</text>
				</view>
				<view class="action-btn free-btn" :style="'background:' + tc.primary + ';color:#fff;border-color:' + tc.primary + ';'" v-else @tap="handleDownload">
					<text class="action-btn-icon">↓</text>
					<text>立即下载</text>
				</view>
			</view>
		</view>

		<!-- 加载中 -->
		<view class="loading-mask" v-if="pageLoading">
			<view class="loading-anim">
				<view class="loading-ring">
					<view class="ring-inner"></view>
				</view>
				<view class="loading-dots">
					<view class="ldot"></view>
					<view class="ldot"></view>
					<view class="ldot"></view>
				</view>
			</view>
			<text class="loading-text">加载中...</text>
		</view>

		<!-- 加载失败 -->
		<view class="error-mask" v-if="loadError && !pageLoading">
			<view class="error-card">
				<view class="error-icon-wrap">
					<view class="error-icon-bg"></view>
					<text class="error-icon">!</text>
				</view>
				<text class="error-title">加载失败</text>
				<text class="error-desc">{{ errorMsg }}</text>
				<view class="error-retry-btn" @tap="retryLoad">
					<text class="retry-icon">↻</text>
					<text>重新加载</text>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
import http, { BASE_URL } from '@/utils/http.js';

export default {
	data() {
		return {
			resourceId: 0,
			resource: {},
			images: [],
			comments: [],
			relatedResources: [],
			isFavorited: false,
			isPurchased: false,
			isVip: false,
			isMemberFree: false,
			pageLoading: true,
			loadError: false,
			errorMsg: '',
			currentImageIdx: 0,
			downloading: false,
			downloadPercent: 0,
			commentForm: {
				rating: 5,
				content: ''
			},
			submittingComment: false,
			commentEnabled: true,
			userVote: null,
			upVotes: 0,
			downVotes: 0
		};
	},
	computed: {
		fileTypeIcon() {
			const type = (this.resource.file_type || '').toLowerCase();
			if (type.includes('pdf')) return '📄';
			if (type.includes('doc') || type.includes('word')) return '📝';
			if (type.includes('xls') || type.includes('excel')) return '📊';
			if (type.includes('ppt')) return '📽';
			if (type.includes('zip') || type.includes('rar') || type.includes('7z')) return '📦';
			if (type.includes('psd') || type.includes('ai')) return '🎨';
			if (type.includes('mp4') || type.includes('mov') || type.includes('avi')) return '🎬';
			if (type.includes('mp3') || type.includes('wav') || type.includes('flac')) return '🎵';
			if (type.includes('jpg') || type.includes('png') || type.includes('svg')) return '🖼';
			return '📁';
		},
		vipFreeText() {
			const levels = this.resource.vip_free_levels;
			if (!levels) return 'VIP免费';
			const map = { 1: '月卡', 2: '季卡', 3: '年卡', 4: '终身' };
			const arr = levels.split(',').map(l => map[l.trim()] || l);
			return arr.join('/') + '免费';
		},
		previewType() {
			const type = (this.resource.file_type || '').toLowerCase();
			if (type.includes('pdf')) return 'pdf';
			if (type.includes('jpg') || type.includes('jpeg') || type.includes('png') || type.includes('gif') || type.includes('svg') || type.includes('psd') || type.includes('ai')) return 'image';
			if (type.includes('js') || type.includes('py') || type.includes('java') || type.includes('html') || type.includes('css') || type.includes('php') || type.includes('json') || type.includes('xml') || type.includes('sql') || type.includes('ts') || type.includes('vue') || type.includes('cpp') || type.includes('go') || type.includes('rust')) return 'code';
			return 'text';
		},
		hasPreview() {
			return !!(this.resource.preview_images && this.resource.preview_images.length > 0) ||
				!!this.resource.preview_code ||
				!!this.resource.preview_text;
		},
		canSubmitComment() {
			return this.commentForm.content.trim().length > 0 && !this.submittingComment;
		},
		ratingHintText() {
			const hints = ['', '很差', '较差', '一般', '不错', '很好'];
			return hints[this.commentForm.rating] || '';
		},
		votePercent() {
			const total = this.upVotes + this.downVotes;
			if (total === 0) return 50;
			return Math.round(this.upVotes / total * 100);
		}
	},
	onLoad(options) {
		if (options.id) {
			this.resourceId = parseInt(options.id);
			this.loadDetail();
			this.loadComments();
			this.loadRelated();
			this.checkFavorite();
			this.checkVip();
			this.loadVoteStatus();
		}
	},
	methods: {
		goBack() {
			uni.navigateBack({ delta: 1 });
		},
		retryLoad() {
			this.loadDetail();
			this.loadComments();
			this.loadRelated();
		},
		async loadDetail() {
			this.pageLoading = true;
			this.loadError = false;
			this.errorMsg = '';
			try {
				const res = await http.get('/api/resource/detail', {
					id: this.resourceId
				});
				if (res.code === 0) {
					this.resource = res.data || {};
					this.images = this.resource.images && this.resource.images.length > 0
						? this.resource.images
						: [this.resource.cover_url].filter(Boolean);
					this.isMemberFree = !!this.resource.is_member_free;
					this.isPurchased = !!this.resource.is_purchased;
					this.commentEnabled = this.resource.comment_enabled !== false;
					uni.setNavigationBarTitle({ title: this.resource.title || '资源详情' });
				} else {
					this.loadError = true;
					this.errorMsg = res.message || res.msg || '加载失败';
				}
			} catch (e) {
				console.error('加载资源详情失败', e);
				this.loadError = true;
				this.errorMsg = '网络异常，请检查网络后重试';
			} finally {
				this.pageLoading = false;
			}
		},
		async loadComments() {
			try {
				const res = await http.get('/api/resource/comments', {
					resource_id: this.resourceId,
					page: 1,
					page_size: 10
				});
				if (res.code === 0) {
					this.comments = res.data.list || res.data || [];
				}
			} catch (e) {
				console.error('加载评论失败', e);
			}
		},
		async loadRelated() {
			try {
				const res = await http.get('/api/resource/related', {
					id: this.resourceId
				});
				if (res.code === 0) {
					this.relatedResources = res.data || [];
				}
			} catch (e) {
				console.error('加载相关资源失败', e);
			}
		},
		async checkFavorite() {
			const token = uni.getStorageSync('token');
			if (!token) return;
			try {
				const res = await http.get('/api/favorite/check', {
					resource_id: this.resourceId
				});
				if (res.code === 0) {
					this.isFavorited = !!res.data.is_favorited;
				}
			} catch (e) {}
		},
		async checkVip() {
			const token = uni.getStorageSync('token');
			if (!token) return;
			try {
				const res = await http.get('/api/user/vipStatus');
				if (res.code === 0) {
					this.isVip = !!(res.data && res.data.is_vip);
				}
			} catch (e) {}
		},
		async loadVoteStatus() {
			const token = uni.getStorageSync('token');
			if (!token) return;
			try {
				const res = await http.get('/api/vote/status', {
					resource_id: this.resourceId
				}, { silent: true });
				if (res.code === 0) {
					this.userVote = res.data.user_vote;
					this.upVotes = res.data.up_votes || 0;
					this.downVotes = res.data.down_votes || 0;
				}
			} catch (e) {}
		},
		async toggleVote(type) {
			const token = uni.getStorageSync('token');
			if (!token) {
				uni.showModal({
					title: '提示',
					content: '请先登录后再投票',
					confirmText: '去登录',
					success: (res) => {
						if (res.confirm) {
							uni.switchTab({ url: '/pages/user/index' });
						}
					}
				});
				return;
			}
			try {
				const res = await http.post('/api/vote/toggle', {
					resource_id: this.resourceId,
					vote_type: type
				});
				if (res.code === 0) {
					this.userVote = res.data.user_vote;
					this.upVotes = res.data.up_votes || 0;
					this.downVotes = res.data.down_votes || 0;
					const action = res.data.action;
					if (action === 'removed') {
						uni.showToast({ title: '已取消投票', icon: 'none' });
					} else if (action === 'changed') {
						uni.showToast({ title: '已更改投票', icon: 'none' });
					}
				}
			} catch (e) {
				uni.showToast({ title: '投票失败', icon: 'none' });
			}
		},
		async toggleFavorite() {
			const token = uni.getStorageSync('token');
			if (!token) {
				uni.showModal({
					title: '提示',
					content: '请先登录',
					confirmText: '去登录',
					success: (res) => {
						if (res.confirm) {
							uni.switchTab({ url: '/pages/user/index' });
						}
					}
				});
				return;
			}
			try {
				const action = this.isFavorited ? 'cancel' : 'add';
				const res = await http.post('/api/favorite/toggle', {
					resource_id: this.resourceId,
					action: action
				});
				if (res.code === 0) {
					this.isFavorited = !this.isFavorited;
					uni.showToast({
						title: this.isFavorited ? '已收藏' : '已取消收藏',
						icon: 'none'
					});
				}
			} catch (e) {
				uni.showToast({ title: '操作失败', icon: 'none' });
			}
		},
		handleDownload() {
			const token = uni.getStorageSync('token');
			if (!token) {
				uni.showModal({
					title: '提示',
					content: '请先登录后再下载',
					confirmText: '去登录',
					success: (res) => {
						if (res.confirm) {
							uni.switchTab({ url: '/pages/user/index' });
						}
					}
				});
				return;
			}
			this.doDownload();
		},
		async doDownload() {
			uni.showLoading({ title: '获取下载链接...' });
			try {
				console.log('[DOWNLOAD] resourceId:', this.resourceId, 'resource:', this.resource?.title);
				const res = await http.post('/api/download/download', {
					resource_id: this.resourceId
				});
				if (res.code === 0 && res.data) {
					const fileUrl = (res.data.resource && res.data.resource.file_url) || res.data.file_url;
					if (!fileUrl) {
						uni.hideLoading();
						uni.showToast({ title: '获取下载链接失败', icon: 'none' });
						return;
					}
					uni.hideLoading();
					this.downloading = true;
					this.downloadPercent = 0;
					const downloadTask = uni.downloadFile({
						url: fileUrl,
						success: (downloadRes) => {
							this.downloading = false;
							if (downloadRes.statusCode === 200) {
								const tempPath = downloadRes.tempFilePath;
								uni.openDocument({
									filePath: tempPath,
									showMenu: true,
									success: () => {
										uni.showToast({ title: '下载成功，点右上角可保存转发', icon: 'success', duration: 3000 });
									},
									fail: (err) => {
										uni.showModal({
											title: '下载完成',
											content: '该文件无法在小程序内打开，请复制链接到浏览器下载。',
											confirmText: '复制链接',
											success: (mr) => {
												if (mr.confirm) uni.setClipboardData({ data: fileUrl });
											}
										});
									}
								});
							} else {
								uni.showToast({ title: '下载失败(HTTP ' + downloadRes.statusCode + ')', icon: 'none' });
							}
						},
						fail: (err) => {
							this.downloading = false;
							console.error('[DOWNLOAD]', fileUrl, err);
							uni.showToast({ title: '下载失败:' + (err.errMsg || '未知'), icon: 'none', duration: 5000 });
						}
					});
					if (downloadTask && downloadTask.onProgressUpdate) {
						downloadTask.onProgressUpdate((progressRes) => {
							this.downloadPercent = progressRes.progress || 0;
						});
					}
				} else {
					uni.hideLoading();
					uni.showToast({ title: res.message || res.msg || '获取下载链接失败', icon: 'none' });
				}
			} catch (e) {
				uni.hideLoading();
				this.downloading = false;
				console.error('[DOWNLOAD] 异常:', e);
				uni.showToast({ title: '下载异常:' + (e.message || '未知'), icon: 'none', duration: 5000 });
			}
		},
		async handleBuy() {
			const token = uni.getStorageSync('token');
			if (!token) {
				uni.showModal({
					title: '提示',
					content: '请先登录后再购买',
					confirmText: '去登录',
					success: (res) => {
						if (res.confirm) {
							uni.switchTab({ url: '/pages/user/index' });
						}
					}
				});
				return;
			}
			uni.showLoading({ title: '创建订单...' });
			try {
				const orderRes = await http.post('/api/order/create', {
					order_type: 'resource',
					resource_id: this.resourceId,
					pay_type: 'wechat'
				});
				if (orderRes.code === 0 && orderRes.data) {
					uni.hideLoading();
					const payParams = orderRes.data.pay_params || {};
					const orderId = orderRes.data.order_id;

					if (payParams.wxpay) {
						uni.requestPayment({
							provider: 'wxpay',
							...payParams.wxpay,
							success: async () => {
								await this.onPaySuccess(orderId);
							},
							fail: (err) => {
								if (err.errMsg && err.errMsg.includes('cancel')) {
									uni.showToast({ title: '已取消支付', icon: 'none' });
								} else {
									uni.showToast({ title: '支付失败', icon: 'none' });
								}
							}
						});
					} else if (payParams.virtual) {
						uni.requestVirtualPayment({
							...payParams.virtual,
							success: async () => {
								await this.onPaySuccess(orderId);
							},
							fail: (err) => {
								if (err.errMsg && err.errMsg.includes('cancel')) {
									uni.showToast({ title: '已取消支付', icon: 'none' });
								} else {
									uni.showToast({ title: '支付失败', icon: 'none' });
								}
							}
						});
					}
				} else {
					uni.hideLoading();
					uni.showToast({ title: orderRes.msg || '创建订单失败', icon: 'none' });
				}
			} catch (e) {
				uni.hideLoading();
				uni.showToast({ title: '支付异常', icon: 'none' });
			}
		},
		async onPaySuccess(orderId) {
			try {
				await http.post('/api/order/callback', { order_id: orderId });
			} catch (e) {}
			this.isPurchased = true;
			uni.showToast({ title: '购买成功', icon: 'success' });
			setTimeout(() => {
				this.doDownload();
			}, 1000);
		},
		goVip() {
			uni.navigateTo({ url: '/pages/user/vip' });
		},
		onSwiperChange(e) {
			this.currentImageIdx = e.detail.current;
		},
		previewImage(idx) {
			uni.previewImage({
				urls: this.images,
				current: idx
			});
		},
		previewPreviewImage(idx) {
			if (this.resource.preview_images) {
				uni.previewImage({
					urls: this.resource.preview_images,
					current: idx
				});
			}
		},
		renderStars(rating) {
			const r = Math.round(rating || 0);
			let stars = '';
			for (let i = 1; i <= 5; i++) {
				stars += i <= r ? '★' : '☆';
			}
			return stars;
		},
		async submitComment() {
			if (!this.canSubmitComment) return;
			const token = uni.getStorageSync('token');
			if (!token) {
				uni.showModal({
					title: '提示',
					content: '请先登录后再评价',
					confirmText: '去登录',
					success: (res) => {
						if (res.confirm) {
							uni.switchTab({ url: '/pages/user/index' });
						}
					}
				});
				return;
			}
			this.submittingComment = true;
			try {
				const res = await http.post('/api/resource/comment', {
					resource_id: this.resourceId,
					rating: this.commentForm.rating,
					content: this.commentForm.content.trim()
				});
				if (res.code === 0) {
					uni.showToast({ title: '评价成功', icon: 'success' });
					this.commentForm.content = '';
					this.commentForm.rating = 5;
					this.loadComments();
				} else {
					uni.showToast({ title: res.message || res.msg || '评价失败', icon: 'none' });
				}
			} catch (e) {
				uni.showToast({ title: '评价失败', icon: 'none' });
			} finally {
				this.submittingComment = false;
			}
		},
		goRelated(item) {
			uni.navigateTo({ url: '/pages/resource/detail?id=' + item.id });
		},
		formatSize(bytes) {
			if (!bytes) return '';
			if (bytes < 1024) return bytes + 'B';
			if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + 'KB';
			if (bytes < 1024 * 1024 * 1024) return (bytes / (1024 * 1024)).toFixed(1) + 'MB';
			return (bytes / (1024 * 1024 * 1024)).toFixed(1) + 'GB';
		},
		formatCount(num) {
			if (!num) return '0';
			if (num >= 10000) return (num / 10000).toFixed(1) + 'w';
			if (num >= 1000) return (num / 1000).toFixed(1) + 'k';
			return num + '';
		},
		fixUrl(url) {
			if (!url) return '';
			return url.startsWith('http') ? url : BASE_URL + url;
		},
		formatCommentTime(time) {
			if (!time) return '';
			const d = new Date(time.replace(/-/g, '/'));
			const now = new Date();
			const diff = (now - d) / 1000;
			if (diff < 60) return '刚刚';
			if (diff < 3600) return Math.floor(diff / 60) + '分钟前';
			if (diff < 86400) return Math.floor(diff / 3600) + '小时前';
			if (diff < 604800) return Math.floor(diff / 86400) + '天前';
			return time.slice(0, 10);
		},
		getRatingPercent(star) {
			// 模拟评分分布
			const dist = { 5: 62, 4: 24, 3: 9, 2: 3, 1: 2 };
			return dist[star] || 0;
		},
		copyCode() {
			if (this.resource.preview_code) {
				uni.setClipboardData({
					data: this.resource.preview_code,
					success: () => {
						uni.showToast({ title: '已复制代码', icon: 'success' });
					}
				});
			}
		},
		likeComment(item) {
			uni.showToast({ title: '已点赞', icon: 'none' });
		},
		replyComment(item) {
			this.commentForm.content = '@' + item.nickname + ' ';
		},
		goMore() {
			uni.switchTab({ url: '/pages/category/list' });
		}
	},
	onShareAppMessage() {
		return {
			title: this.resource.title || '发现优质资源',
			path: `/pages/resource/detail?id=${this.resourceId}`,
			imageUrl: this.resource.cover_url || ''
		};
	}
};
</script>

<style scoped>
/* ========== 基础 ========== */
.page {
	min-height: 100vh;
	background: linear-gradient(180deg, #f0f2ff 0%, #f5f6fa 300rpx, #f5f6fa 100%);
	position: relative;
}
.main-scroll {
	height: 100vh;
}

/* ========== 图片画廊 ========== */
.gallery-wrap {
	position: relative;
	overflow: hidden;
}
.gallery-swiper {
	height: 680rpx;
	background: #000;
}
.gallery-img {
	width: 100%;
	height: 100%;
	transition: transform 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}
.gallery-gradient-top {
	position: absolute;
	top: 0;
	left: 0;
	right: 0;
	height: 240rpx;
	background: linear-gradient(to bottom, rgba(0,0,0,0.5), rgba(0,0,0,0.1), transparent);
	z-index: 10;
	pointer-events: none;
}
.gallery-gradient-bottom {
	position: absolute;
	bottom: 0;
	left: 0;
	right: 0;
	height: 260rpx;
	background: linear-gradient(to top, rgba(0,0,0,0.45), rgba(0,0,0,0.1), transparent);
	z-index: 10;
	pointer-events: none;
}
.fixed-back {
	position: fixed;
	top: 80rpx;
	left: 28rpx;
	width: 72rpx;
	height: 72rpx;
	background: rgba(0,0,0,0.3);
	backdrop-filter: blur(16px);
	border-radius: 50%;
	display: flex;
	align-items: center;
	justify-content: center;
	z-index: 100;
	border: 2rpx solid rgba(255,255,255,0.2);
	box-shadow: 0 4rpx 20rpx rgba(0,0,0,0.2);
}
.back-icon {
	font-size: 48rpx;
	color: #fff;
	font-weight: 300;
	margin-top: -4rpx;
}
/* 进度条指示器 */
.gallery-indicator {
	position: absolute;
	bottom: 36rpx;
	left: 50%;
	transform: translateX(-50%);
	width: 200rpx;
	z-index: 20;
}
.indicator-track {
	height: 6rpx;
	background: rgba(255,255,255,0.25);
	border-radius: 3rpx;
	overflow: hidden;
}
.indicator-bar {
	height: 100%;
	background: linear-gradient(90deg, #2ed573, #1abc9c);
	border-radius: 3rpx;
	transition: width 0.35s cubic-bezier(0.4, 0, 0.2, 1);
	box-shadow: 0 0 10rpx rgba(46,213,115,0.5);
}
.gallery-tags {
	position: absolute;
	bottom: 60rpx;
	left: 28rpx;
	display: flex;
	gap: 12rpx;
	z-index: 20;
}
.gallery-tag {
	font-size: 22rpx;
	color: #fff;
	background: rgba(0,0,0,0.4);
	backdrop-filter: blur(12px);
	padding: 10rpx 24rpx;
	border-radius: 28rpx;
	font-weight: 600;
	border: 2rpx solid rgba(255,255,255,0.15);
	display: flex;
	align-items: center;
	gap: 8rpx;
}
.tag-dot {
	width: 10rpx;
	height: 10rpx;
	background: #2ed573;
	border-radius: 50%;
	display: inline-block;
}
.gallery-tag.free {
	background: rgba(46,213,115,0.10);
	color: #27ae60;
	border-color: rgba(46,213,115,0.18);
	box-shadow: 0 2rpx 8rpx rgba(46,213,115,0.08);
}
.tag-spark {
	font-size: 20rpx;
	animation: sparkle-rotate 2s ease-in-out infinite;
}
@keyframes sparkle-rotate {
	0%, 100% { transform: rotate(0deg) scale(1); }
	50% { transform: rotate(180deg) scale(1.2); }
}

/* ========== Hero 卡片 ========== */
.hero-card {
	background: #fff;
	margin: -60rpx 24rpx 0;
	padding: 36rpx;
	border-radius: 28rpx;
	position: relative;
	z-index: 15;
	box-shadow: 0 12rpx 48rpx rgba(46,213,115,0.08), 0 4rpx 12rpx rgba(0,0,0,0.04);
	overflow: hidden;
}
.hero-card::before {
	content: '';
	position: absolute;
	top: 0;
	left: 0;
	right: 0;
	height: 3rpx;
	background: linear-gradient(90deg, #2ed573, #1abc9c, #27ae60, #2ed573);
	border-radius: 0 0 2rpx 2rpx;
	box-shadow: 0 0 12rpx rgba(0,0,0,0.15), 0 0 24rpx rgba(0,0,0,0.06);
	filter: blur(0.5rpx);
}
.hero-deco {
	position: absolute;
	top: -40rpx;
	right: -40rpx;
	width: 200rpx;
	height: 200rpx;
	background: radial-gradient(circle, rgba(0,0,0,0.04), transparent 70%);
	border-radius: 50%;
	pointer-events: none;
}
.hero-badges {
	display: flex;
	gap: 10rpx;
	margin-bottom: 16rpx;
	flex-wrap: wrap;
}
.hero-badge {
	font-size: 20rpx;
	padding: 6rpx 16rpx;
	border-radius: 20rpx;
	font-weight: 700;
	display: flex;
	align-items: center;
	gap: 4rpx;
}
.hero-badge.hot {
	background: rgba(46,213,115,0.10);
	color: #27ae60;
	border: 2rpx solid rgba(46,213,115,0.18);
}
.hero-badge.new {
	background: rgba(46,213,115,0.10);
	color: #27ae60;
	border: 2rpx solid rgba(46,213,115,0.18);
}
.hero-badge.rec {
	background: rgba(46,213,115,0.10);
	color: #27ae60;
	border: 2rpx solid rgba(46,213,115,0.18);
}
.badge-emoji {
	font-size: 22rpx;
}
.hero-title {
	font-size: 38rpx;
	font-weight: 900;
	color: #1a1a2e;
	line-height: 1.5;
	display: block;
	letter-spacing: 0.5rpx;
}
.hero-meta {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-top: 24rpx;
}
.hero-stats {
	display: flex;
	align-items: center;
	gap: 0;
}
.stat-divider {
	width: 2rpx;
	height: 24rpx;
	background: #e8e8f0;
	margin: 0 20rpx;
}
.hero-stat {
	display: flex;
	align-items: center;
	gap: 8rpx;
}
.stat-icon-circle {
	width: 44rpx;
	height: 44rpx;
	border-radius: 50%;
	display: flex;
	align-items: center;
	justify-content: center;
}
.stat-icon-circle.view {
	background: rgba(59,130,246,0.08);
}
.stat-icon-circle.download {
	background: rgba(46,213,115,0.08);
}
.stat-icon-circle.like {
	background: rgba(239,68,68,0.08);
}
.stat-icon {
	font-size: 22rpx;
}
.stat-num {
	font-size: 24rpx;
	color: #888;
	font-weight: 700;
}
.hero-rating {
	display: flex;
	align-items: center;
	background: linear-gradient(135deg, #fff8e1, #fff3cd);
	padding: 10rpx 22rpx;
	border-radius: 24rpx;
	gap: 6rpx;
	position: relative;
	border: 2rpx solid rgba(245,158,11,0.15);
}
.rating-glow {
	position: absolute;
	top: -4rpx;
	left: -4rpx;
	right: -4rpx;
	bottom: -4rpx;
	border-radius: 28rpx;
	background: radial-gradient(ellipse at center, rgba(245,158,11,0.15), transparent);
	pointer-events: none;
}
.rating-star {
	font-size: 28rpx;
	color: #f59e0b;
	position: relative;
}
.rating-value {
	font-size: 28rpx;
	color: #d97706;
	font-weight: 900;
	position: relative;
}
.rating-count {
	font-size: 20rpx;
	color: #b8860b;
	position: relative;
}

/* ========== 价格卡片 ========== */
.price-card {
	background: #fff;
	margin: 20rpx 24rpx 0;
	padding: 32rpx;
	border-radius: 24rpx;
	display: flex;
	align-items: center;
	justify-content: space-between;
	box-shadow: 0 6rpx 28rpx rgba(255,71,87,0.08);
	border: 2rpx solid rgba(255,71,87,0.06);
	position: relative;
	overflow: hidden;
}
.price-card::before {
	content: '';
	position: absolute;
	left: 0;
	top: 0;
	bottom: 0;
	width: 8rpx;
	background: linear-gradient(180deg, #ff4757, #ff6b81, #ff4757);
	border-radius: 4rpx;
}
.price-left {
	flex: 1;
}
.price-row {
	display: flex;
	align-items: baseline;
}
.price-symbol {
	font-size: 30rpx;
	color: #ff4757;
	font-weight: 900;
}
.price-value {
	font-size: 56rpx;
	color: #ff4757;
	font-weight: 900;
	margin-left: 4rpx;
	line-height: 1;
	letter-spacing: -1rpx;
}
.price-original {
	font-size: 22rpx;
	color: #ccc;
	text-decoration: line-through;
	margin-left: 16rpx;
}
.price-badges {
	display: flex;
	gap: 10rpx;
	margin-top: 16rpx;
	flex-wrap: wrap;
}
.badge {
	font-size: 20rpx;
	padding: 8rpx 18rpx;
	border-radius: 18rpx;
	font-weight: 700;
	display: flex;
	align-items: center;
	gap: 4rpx;
}
.badge-flash {
	font-size: 18rpx;
}
.badge-crown, .badge-star-icon {
	font-size: 20rpx;
}
.badge-discount {
	color: #fff;
	background: linear-gradient(135deg, #ff4757, #ff6b81);
	box-shadow: 0 4rpx 12rpx rgba(255,71,87,0.25);
}
.badge-member {
	color: #fff;
	background: #f59e0b;
	border: none;
}
.badge-vip {
	color: #d97706;
	background: linear-gradient(135deg, rgba(217,119,6,0.08), rgba(245,158,11,0.08));
	border: 2rpx solid rgba(217,119,6,0.2);
}
.price-right {
	width: 80rpx;
	height: 80rpx;
	position: relative;
	display: flex;
	align-items: center;
	justify-content: center;
}
.price-pulse {
	width: 48rpx;
	height: 48rpx;
	border-radius: 50%;
	background: rgba(255,71,87,0.1);
	position: relative;
	animation: pulse-soft 2s ease-in-out infinite;
}
.price-pulse::after {
	content: '';
	position: absolute;
	top: 8rpx;
	left: 8rpx;
	width: 32rpx;
	height: 32rpx;
	border-radius: 50%;
	background: rgba(255,71,87,0.2);
}
@keyframes pulse-soft {
	0%, 100% { transform: scale(1); opacity: 1; }
	50% { transform: scale(1.1); opacity: 0.8; }
}
.price-particle {
	position: absolute;
	width: 8rpx;
	height: 8rpx;
	border-radius: 50%;
	background: rgba(255,71,87,0.2);
	pointer-events: none;
}
.price-particle.p1 {
	top: 16rpx;
	right: 120rpx;
	animation: float-particle 3s ease-in-out infinite;
}
.price-particle.p2 {
	bottom: 16rpx;
	right: 160rpx;
	width: 6rpx;
	height: 6rpx;
	animation: float-particle 3s ease-in-out 1s infinite;
}
@keyframes float-particle {
	0%, 100% { transform: translateY(0) scale(1); opacity: 0.6; }
	50% { transform: translateY(-8rpx) scale(1.3); opacity: 1; }
}

/* 免费卡片 */
.free-card {
	background: #f0fdf4;
	border: 2rpx solid rgba(0,0,0,0.08);
	box-shadow: 0 6rpx 28rpx rgba(0,0,0,0.06);
	position: relative;
}
.free-card::after {
	content: '';
	position: absolute;
	top: 10rpx;
	right: 30rpx;
	width: 6rpx;
	height: 6rpx;
	border-radius: 50%;
	background: transparent;
	box-shadow:
		12rpx 8rpx 0 2rpx rgba(46,213,115,0.15),
		32rpx 20rpx 0 1.5rpx rgba(245,158,11,0.12),
		8rpx 36rpx 0 2rpx rgba(255,215,0,0.1);
	animation: float-particle 3s ease-in-out infinite;
	pointer-events: none;
}
.free-card-bar {
	position: absolute;
	left: 0;
	top: 10%;
	height: 80%;
	width: 6rpx;
	border-radius: 0 4rpx 4rpx 0;
}
.free-icon-wrap {
	width: 80rpx;
	height: 80rpx;
	background: rgba(0,0,0,0.06);
	border-radius: 50%;
	display: flex;
	align-items: center;
	justify-content: center;
	margin-right: 24rpx;
	position: relative;
	flex-shrink: 0;
}
.free-icon-ring {
	position: absolute;
	top: -6rpx;
	left: -6rpx;
	right: -6rpx;
	bottom: -6rpx;
	border-radius: 50%;
	border: 3rpx dashed rgba(46,213,115,0.2);
	animation: ring-rotate 8s linear infinite;
}
@keyframes ring-rotate {
	to { transform: rotate(360deg); }
}
.free-icon {
	font-size: 40rpx;
}
.free-info {
	flex: 1;
}
.free-title {
	font-size: 34rpx;
	font-weight: 900;
	color: #166534;
	display: block;
}
.free-desc {
	font-size: 24rpx;
	color: #4ade80;
	margin-top: 6rpx;
	display: block;
}
.free-sparkles {
	position: relative;
	width: 60rpx;
	flex-shrink: 0;
}
.sparkle {
	position: absolute;
	font-size: 20rpx;
	color: rgba(46,213,115,0.4);
}
.sparkle.s1 { top: 0; right: 0; animation: sparkle-twinkle 2s ease-in-out infinite; }
.sparkle.s2 { top: 28rpx; right: 20rpx; animation: sparkle-twinkle 2s ease-in-out 0.5s infinite; }
.sparkle.s3 { top: 10rpx; right: 40rpx; animation: sparkle-twinkle 2s ease-in-out 1s infinite; }
@keyframes sparkle-twinkle {
	0%, 100% { opacity: 0.3; transform: scale(0.8); }
	50% { opacity: 1; transform: scale(1.2); }
}

/* ========== 投票卡片 ========== */
.vote-card {
	background: linear-gradient(135deg, #fff 0%, #f8f6ff 50%, #fff 100%);
	margin: 20rpx 24rpx 0;
	padding: 28rpx 32rpx;
	border-radius: 24rpx;
	display: flex;
	align-items: center;
	box-shadow: 0 6rpx 28rpx rgba(0,0,0,0.04);
	border: 2rpx solid rgba(46,213,115,0.04);
}
.vote-btn {
	display: flex;
	align-items: center;
	gap: 10rpx;
	padding: 18rpx 0;
	border-radius: 40rpx;
	background: #f8f8fc;
	transition: all 0.3s;
	flex: 1;
	justify-content: center;
}
.vote-btn.active {
	border: 2rpx solid rgba(0,0,0,0.1);
	box-shadow: 0 4rpx 16rpx rgba(0,0,0,0.08);
}
.vote-icon-wrap {
	width: 52rpx;
	height: 52rpx;
	border-radius: 50%;
	display: flex;
	align-items: center;
	justify-content: center;
	background: rgba(0,0,0,0.03);
}
.vote-icon-wrap.up {
	background: rgba(0,0,0,0.03);
}
.vote-icon-wrap.down {
	background: rgba(239,68,68,0.06);
}
.vote-icon {
	font-size: 28rpx;
}
.vote-count {
	font-size: 28rpx;
	color: #666;
	font-weight: 800;
}
.vote-btn.active .vote-count {
}
.vote-center {
	padding: 0 20rpx;
	flex: 0.6;
	display: flex;
	flex-direction: column;
	align-items: center;
	gap: 12rpx;
}
.vote-progress {
	width: 100%;
	height: 8rpx;
	background: #f0f0f5;
	border-radius: 4rpx;
	overflow: hidden;
}
.vote-fill {
	height: 100%;
	background: linear-gradient(90deg, #2ed573, #2ed573);
	border-radius: 4rpx;
	transition: width 0.5s ease;
	position: relative;
	overflow: hidden;
}
.vote-fill::after {
	content: '';
	position: absolute;
	top: 0;
	left: -100%;
	width: 20%;
	height: 100%;
	background: linear-gradient(90deg, transparent, rgba(255,255,255,0.35), transparent);
	animation: vote-shimmer 4s ease-in-out infinite;
}
@keyframes vote-shimmer {
	0% { left: -100%; }
	100% { left: 200%; }
}
.vote-text {
	font-size: 22rpx;
	color: #bbb;
	font-weight: 600;
}

/* ========== 文件信息卡片 ========== */
.file-card {
	background: #fff;
	margin: 20rpx 24rpx 0;
	padding: 32rpx;
	border-radius: 24rpx;
	display: flex;
	align-items: center;
	box-shadow: 0 6rpx 28rpx rgba(0,0,0,0.04);
	position: relative;
	overflow: hidden;
}
.file-bg-pattern {
	position: absolute;
	top: 0;
	right: 0;
	width: 200rpx;
	height: 200rpx;
	background: radial-gradient(circle at top right, rgba(46,213,115,0.04), transparent 70%);
	pointer-events: none;
}
.file-icon-wrap {
	width: 96rpx;
	height: 96rpx;
	border-radius: 24rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	flex-shrink: 0;
	position: relative;
	box-shadow: 0 8rpx 28rpx rgba(0,0,0,0.15);
	overflow: hidden;
}
.file-icon-wrap::after {
	content: '';
	position: absolute;
	top: -60%;
	left: -100%;
	width: 15%;
	height: 200%;
	background: linear-gradient(
		100deg,
		transparent 20%,
		rgba(255,255,255,0.1) 45%,
		rgba(255,255,255,0.3) 50%,
		rgba(255,255,255,0.1) 55%,
		transparent 80%
	);
	animation: icon-gloss-sweep 5s ease-in-out infinite;
	pointer-events: none;
}
@keyframes icon-gloss-sweep {
	0% { left: -100%; }
	50% { left: 150%; }
	100% { left: 150%; }
}
.file-icon-glow {
	position: absolute;
	top: -4rpx;
	left: -4rpx;
	right: -4rpx;
	bottom: -4rpx;
	border-radius: 28rpx;
	background: linear-gradient(135deg, rgba(0,0,0,0.08), rgba(0,0,0,0.08));
	z-index: -1;
}
.file-type-icon {
	font-size: 48rpx;
}
.file-info {
	flex: 1;
	margin-left: 28rpx;
}
.file-name {
	font-size: 30rpx;
	color: #1a1a2e;
	font-weight: 800;
	display: block;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
}
.file-chips {
	display: flex;
	gap: 10rpx;
	margin-top: 14rpx;
	flex-wrap: wrap;
}
.file-chip {
	font-size: 22rpx;
	background: rgba(46,213,115,0.08);
	padding: 8rpx 20rpx;
	border-radius: 16rpx;
	border: 2rpx solid rgba(46,213,115,0.15);
	display: flex;
	align-items: center;
	gap: 6rpx;
}
.file-chip.type {
	background: rgba(46,213,115,0.08);
	border-color: rgba(46,213,115,0.15);
}
.file-chip.size {
	color: #3b82f6;
	background: rgba(59,130,246,0.08);
	border-color: rgba(59,130,246,0.15);
}
.file-chip.format {
	font-weight: 600;
	background: rgba(46,213,115,0.08);
	border-color: rgba(46,213,115,0.15);
}
.chip-dot {
	width: 8rpx;
	height: 8rpx;
	border-radius: 50%;
	background: currentColor;
	flex-shrink: 0;
}

/* ========== 通用区块卡片 ========== */
.section-card {
	background: #fff;
	margin: 20rpx 24rpx 0;
	padding: 32rpx;
	border-radius: 24rpx;
	box-shadow: 0 6rpx 28rpx rgba(0,0,0,0.04);
}
.section-header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-bottom: 24rpx;
}
.section-header-left {
	display: flex;
	align-items: center;
	gap: 14rpx;
}
.section-bar {
	width: 8rpx;
	height: 36rpx;
	border-radius: 4rpx;
}
.section-title {
	font-size: 32rpx;
	font-weight: 900;
	color: #1a1a2e;
}
.section-hint-badge {
	display: flex;
	align-items: center;
	gap: 6rpx;
	padding: 8rpx 18rpx;
	border-radius: 20rpx;
}
.hint-eye {
	font-size: 22rpx;
}
.section-hint {
	font-size: 22rpx;
	color: #fff;
	font-weight: 500;
}
.section-more {
	font-size: 24rpx;
	font-weight: 600;
}

/* ========== 资源预览 ========== */
.preview-card {
	overflow: hidden;
}
.preview-pdf {
	margin-top: 8rpx;
}
.pdf-page-img {
	width: 100%;
	border-radius: 18rpx;
	margin-bottom: 14rpx;
	border: 2rpx solid #f0f0f5;
}
.preview-images {
	margin-top: 8rpx;
}
.preview-img-scroll {
	white-space: nowrap;
}
.preview-img-list {
	display: inline-flex;
	gap: 14rpx;
}
.preview-img-item {
	position: relative;
	border-radius: 18rpx;
	overflow: hidden;
	flex-shrink: 0;
}
.preview-thumb {
	width: 220rpx;
	height: 220rpx;
	display: block;
}
.preview-img-overlay {
	position: absolute;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	background: rgba(0,0,0,0.2);
	display: flex;
	align-items: center;
	justify-content: center;
	opacity: 0;
	transition: opacity 0.3s;
}
.preview-img-item:active .preview-img-overlay {
	opacity: 1;
}
.preview-zoom-icon {
	font-size: 40rpx;
}
.preview-code {
	margin-top: 8rpx;
	background: #1e1e2e;
	border-radius: 18rpx;
	overflow: hidden;
	border: 2rpx solid rgba(255,255,255,0.06);
	position: relative;
}
.preview-code::before {
	content: '';
	position: absolute;
	top: 0;
	left: 0;
	width: 6rpx;
	height: 100%;
	background: linear-gradient(180deg, #2ed573, #1abc9c, #27ae60, #2ed573);
	border-radius: 18rpx 0 0 18rpx;
	z-index: 2;
}
.code-header {
	display: flex;
	align-items: center;
	justify-content: space-between;
	padding: 18rpx 24rpx;
	background: rgba(255,255,255,0.04);
	border-bottom: 2rpx solid rgba(255,255,255,0.06);
}
.code-dots {
	display: flex;
	gap: 12rpx;
}
.dot {
	width: 18rpx;
	height: 18rpx;
	border-radius: 50%;
}
.dot-r { background: #ff5f57; box-shadow: 0 0 6rpx rgba(255,95,87,0.4); }
.dot-y { background: #ffbd2e; box-shadow: 0 0 6rpx rgba(255,189,46,0.4); }
.dot-g { background: #28c840; box-shadow: 0 0 6rpx rgba(40,200,64,0.4); }
.code-header-right {
	display: flex;
	align-items: center;
	gap: 12rpx;
}
.code-lang-badge {
	font-size: 20rpx;
	color: #fff;
	padding: 6rpx 18rpx;
	border-radius: 12rpx;
	font-weight: 600;
}
.code-copy-btn {
	width: 48rpx;
	height: 48rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	background: rgba(255,255,255,0.06);
	border-radius: 12rpx;
}
.copy-icon {
	font-size: 24rpx;
}
.code-scroll {
	max-height: 400rpx;
	padding: 24rpx;
}
.code-content {
	font-size: 24rpx;
	color: #cdd6f4;
	font-family: monospace;
	white-space: pre;
	line-height: 1.7;
}
.preview-text {
	margin-top: 8rpx;
	position: relative;
	max-height: 300rpx;
	overflow: hidden;
}
.preview-text-content {
	font-size: 26rpx;
	color: #666;
	line-height: 1.8;
}
.preview-fade {
	position: absolute;
	bottom: 0;
	left: 0;
	right: 0;
	height: 100rpx;
	background: linear-gradient(transparent, #fff);
}

/* ========== 描述 ========== */
.desc-content {
	font-size: 28rpx;
	color: #555;
	line-height: 1.9;
}

/* ========== 标签 ========== */
.tags-wrap {
	display: flex;
	flex-wrap: wrap;
	gap: 12rpx;
}
.tag-item {
	padding: 12rpx 26rpx;
	border-radius: 28rpx;
	display: flex;
	align-items: center;
	gap: 6rpx;
	transition: all 0.2s;
}
.tag-item.tag-color-0 {
	background: linear-gradient(135deg, rgba(46,213,115,0.06), rgba(46,213,115,0.08));
	border: 2rpx solid rgba(46,213,115,0.12);
}
.tag-item.tag-color-0 .tag-hash,
.tag-item.tag-color-0 .tag-text { }

.tag-item.tag-color-1 {
	background: linear-gradient(135deg, rgba(59,130,246,0.06), rgba(96,165,250,0.08));
	border: 2rpx solid rgba(59,130,246,0.12);
}
.tag-item.tag-color-1 .tag-hash,
.tag-item.tag-color-1 .tag-text { color: #3b82f6; }

.tag-item.tag-color-2 {
	background: linear-gradient(135deg, rgba(236,72,153,0.06), rgba(244,114,182,0.08));
	border: 2rpx solid rgba(236,72,153,0.12);
}
.tag-item.tag-color-2 .tag-hash,
.tag-item.tag-color-2 .tag-text { color: #ec4899; }

.tag-item.tag-color-3 {
	background: linear-gradient(135deg, rgba(245,158,11,0.06), rgba(251,191,36,0.08));
	border: 2rpx solid rgba(245,158,11,0.12);
}
.tag-item.tag-color-3 .tag-hash,
.tag-item.tag-color-3 .tag-text { color: #f59e0b; }

.tag-hash {
	font-size: 22rpx;
	font-weight: 900;
}
.tag-text {
	font-size: 24rpx;
	font-weight: 600;
}

/* ========== 评论区 ========== */
.comment-card {
	margin-bottom: 20rpx;
}
.comment-avg {
	display: flex;
	align-items: center;
	background: linear-gradient(135deg, #fff8e1, #fff3cd);
	padding: 8rpx 20rpx;
	border-radius: 20rpx;
	gap: 6rpx;
	border: 2rpx solid rgba(245,158,11,0.15);
}
.avg-star {
	font-size: 26rpx;
	color: #f59e0b;
}
.avg-num {
	font-size: 26rpx;
	color: #d97706;
	font-weight: 900;
}
/* 评分分布 */
.rating-bars {
	margin-bottom: 24rpx;
	padding: 20rpx 24rpx;
	background: #f8f8fc;
	border-radius: 18rpx;
}
.rating-bar-row {
	display: flex;
	align-items: center;
	gap: 12rpx;
	margin-bottom: 10rpx;
}
.rating-bar-row:last-child {
	margin-bottom: 0;
}
.bar-label {
	font-size: 22rpx;
	color: #999;
	width: 60rpx;
	text-align: right;
}
.bar-track {
	flex: 1;
	height: 10rpx;
	background: #e8e8f0;
	border-radius: 5rpx;
	overflow: hidden;
}
.bar-fill {
	height: 100%;
	background: linear-gradient(90deg, #f59e0b, #fbbf24);
	border-radius: 5rpx;
	transition: width 0.5s ease;
}
.bar-pct {
	font-size: 20rpx;
	color: #bbb;
	width: 64rpx;
	text-align: left;
}

.comment-list {
	margin: 0;
}
.comment-item {
	padding: 28rpx 0;
	border-bottom: none;
	position: relative;
}
.comment-item::after {
	content: '';
	position: absolute;
	bottom: 0;
	left: 0;
	right: 0;
	height: 2rpx;
	background: linear-gradient(to right, transparent, rgba(0,0,0,0.04), transparent);
}
.comment-item:last-child {
	border-bottom: none;
}
.comment-item:last-child::after {
	display: none;
}
.comment-header {
	display: flex;
	align-items: flex-start;
}
.avatar-ring {
	width: 76rpx;
	height: 76rpx;
	border-radius: 50%;
	padding: 3rpx;
	background: linear-gradient(135deg, #2ed573, #1abc9c);
	flex-shrink: 0;
}
.comment-avatar {
	width: 70rpx;
	height: 70rpx;
	border-radius: 50%;
	border: 3rpx solid #fff;
}
.comment-user {
	flex: 1;
	margin-left: 18rpx;
}
.comment-user-row {
	display: flex;
	align-items: center;
	justify-content: space-between;
}
.comment-name {
	font-size: 28rpx;
	color: #1a1a2e;
	font-weight: 800;
}
.comment-stars {
	display: flex;
	gap: 2rpx;
}
.star {
	font-size: 22rpx;
	color: #ddd;
}
.star.active {
	color: #f59e0b;
}
.comment-time {
	font-size: 22rpx;
	color: #c0c0c0;
	margin-top: 6rpx;
	display: block;
}
.comment-content {
	font-size: 28rpx;
	color: #555;
	line-height: 1.7;
	margin-top: 16rpx;
	display: block;
}
.comment-actions {
	display: flex;
	gap: 32rpx;
	margin-top: 16rpx;
}
.comment-action-btn {
	display: flex;
	align-items: center;
	gap: 6rpx;
	padding: 8rpx 16rpx;
	border-radius: 20rpx;
	background: #f8f8fc;
	transition: all 0.2s;
}
.comment-action-btn:active {
	transform: translateY(2rpx);
	background: #f0f0f5;
}
.action-icon {
	font-size: 24rpx;
}
.action-count, .action-label {
	font-size: 22rpx;
	color: #999;
}
.comment-empty {
	text-align: center;
	padding: 60rpx 0;
}
.empty-bubble {
	width: 100rpx;
	height: 100rpx;
	background: linear-gradient(135deg, rgba(46,213,115,0.06), rgba(46,213,115,0.1));
	border-radius: 50%;
	display: flex;
	align-items: center;
	justify-content: center;
	margin: 0 auto 20rpx;
}
.empty-icon {
	font-size: 52rpx;
	display: block;
}
.empty-text {
	font-size: 28rpx;
	color: #999;
	font-weight: 600;
	display: block;
}
.empty-hint {
	font-size: 24rpx;
	color: #ccc;
	display: block;
	margin-top: 8rpx;
}

/* ========== 评论表单 ========== */
.comment-form {
	margin-top: 24rpx;
}
.form-divider {
	height: 2rpx;
	background: linear-gradient(to right, transparent, #e0e0e8, transparent);
	margin-bottom: 32rpx;
}
.form-title {
	font-size: 30rpx;
	font-weight: 800;
	color: #1a1a2e;
	margin-bottom: 24rpx;
	display: block;
}
.form-rating {
	display: flex;
	align-items: center;
	margin-bottom: 24rpx;
	gap: 14rpx;
}
.form-rating-label {
	font-size: 28rpx;
	color: #888;
	font-weight: 600;
}
.form-stars {
	display: flex;
	gap: 10rpx;
}
.form-star {
	font-size: 44rpx;
	color: #e0e0e8;
	transition: all 0.25s;
}
.form-star.active {
	color: #f59e0b;
	transform: scale(1.05);
}
.form-rating-tag {
	padding: 6rpx 16rpx;
	border-radius: 16rpx;
	font-size: 22rpx;
	font-weight: 700;
	margin-left: 8rpx;
}
.form-rating-tag.rating-level-1 { color: #ef4444; background: rgba(239,68,68,0.08); }
.form-rating-tag.rating-level-2 { color: #f97316; background: rgba(249,115,22,0.08); }
.form-rating-tag.rating-level-3 { color: #f59e0b; background: rgba(245,158,11,0.08); }
.form-rating-tag.rating-level-4 { color: #22c55e; background: rgba(34,197,94,0.08); }
.form-rating-tag.rating-level-5 { background: rgba(46,213,115, 0.08); }
.form-textarea-wrap {
	position: relative;
	margin-bottom: 24rpx;
}
.form-textarea {
	width: 100%;
	height: 220rpx;
	font-size: 28rpx;
	color: #333;
	padding: 28rpx;
	background: #f8f8fc;
	border-radius: 22rpx;
	box-sizing: border-box;
	line-height: 1.6;
	border: 3rpx solid transparent;
	transition: all 0.3s;
}
.form-textarea:focus {
	border-color: rgba(46,213,115,0.2);
	background: #fff;
	box-shadow: 0 4rpx 20rpx rgba(46,213,115,0.06);
}
.form-textarea-footer {
	display: flex;
	justify-content: flex-end;
	margin-top: 10rpx;
}
.form-char-count {
	font-size: 22rpx;
	color: #ccc;
	transition: color 0.2s;
}
.form-char-count.warn {
	color: #ef4444;
}
.form-submit-btn {
	text-align: center;
	padding: 28rpx;
	border-radius: 48rpx;
	font-size: 30rpx;
	font-weight: 800;
	color: #fff;
	box-shadow: 0 10rpx 32rpx rgba(0,0,0,0.15);
	display: flex;
	align-items: center;
	justify-content: center;
	gap: 10rpx;
}
.form-submit-btn.disabled {
	opacity: 0.35;
	box-shadow: none;
}
.form-submit-btn:active {
	transform: scale(0.97) translateY(2rpx);
	box-shadow: 0 4rpx 12rpx rgba(46,213,115,0.18);
}
.submit-icon {
	font-size: 28rpx;
}

/* ========== 相关资源 ========== */
.related-scroll {
	white-space: nowrap;
}
.related-list {
	display: inline-flex;
	gap: 18rpx;
}
.related-card {
	width: 280rpx;
	background: #f8f8fc;
	border-radius: 22rpx;
	overflow: hidden;
	flex-shrink: 0;
	transition: transform 0.2s;
	box-shadow: 0 4rpx 16rpx rgba(0,0,0,0.04);
}
.related-card:active {
	transform: scale(0.97) translateY(2rpx);
	box-shadow: 0 2rpx 8rpx rgba(0,0,0,0.06);
}
.related-cover-wrap {
	position: relative;
	overflow: hidden;
}
.related-cover {
	width: 280rpx;
	height: 200rpx;
	display: block;
}
.related-overlay {
	position: absolute;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	background: linear-gradient(to top, rgba(0,0,0,0.3), transparent);
	display: flex;
	align-items: flex-end;
	justify-content: flex-end;
	padding: 12rpx;
}
.related-play {
	width: 44rpx;
	height: 44rpx;
	background: rgba(255,255,255,0.85);
	border-radius: 50%;
	display: flex;
	align-items: center;
	justify-content: center;
	font-size: 24rpx;
	color: #333;
	font-weight: 700;
}
.related-badge {
	position: absolute;
	top: 12rpx;
	left: 12rpx;
	color: #fff;
	font-size: 20rpx;
	padding: 4rpx 14rpx;
	border-radius: 14rpx;
	font-weight: 700;
	box-shadow: 0 4rpx 12rpx rgba(46,213,115,0.3);
}
.related-info {
	padding: 18rpx 20rpx 20rpx;
}
.related-title {
	font-size: 26rpx;
	color: #333;
	font-weight: 700;
	display: block;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
}
.related-bottom {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-top: 12rpx;
}
.related-price {
	font-size: 28rpx;
	color: #ff4757;
	font-weight: 800;
}
.related-price.free {
	font-size: 24rpx;
	font-weight: 700;
}
.related-stats {
	display: flex;
	align-items: center;
}
.related-downloads {
	font-size: 22rpx;
	color: #bbb;
}

/* ========== 下载进度 ========== */
.download-progress-mask {
	position: fixed;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	background: rgba(0,0,0,0.55);
	backdrop-filter: blur(12px);
	display: flex;
	align-items: center;
	justify-content: center;
	z-index: 300;
}
.progress-card {
	background: #fff;
	border-radius: 32rpx;
	padding: 56rpx 48rpx 48rpx;
	width: 500rpx;
	text-align: center;
	box-shadow: 0 24rpx 72rpx rgba(0,0,0,0.2);
	position: relative;
	overflow: hidden;
}
.progress-icon-wrap {
	width: 88rpx;
	height: 88rpx;
	background: linear-gradient(135deg, #2ed573, #27ae60);
	border-radius: 50%;
	display: flex;
	align-items: center;
	justify-content: center;
	margin: 0 auto 24rpx;
	position: relative;
}
.progress-ring-anim {
	position: absolute;
	top: -6rpx;
	left: -6rpx;
	right: -6rpx;
	bottom: -6rpx;
	border-radius: 50%;
	border: 3rpx solid transparent;
	border-top-color: rgba(46,213,115,0.3);
	animation: spin 1.2s linear infinite;
}
@keyframes spin {
	to { transform: rotate(360deg); }
}
.progress-download-icon {
	font-size: 40rpx;
	color: #fff;
	font-weight: 700;
}
.progress-title {
	font-size: 32rpx;
	font-weight: 800;
	color: #1a1a2e;
	margin-bottom: 32rpx;
	display: block;
}
.progress-bar-bg {
	height: 16rpx;
	background: #f0f0f5;
	border-radius: 8rpx;
	overflow: hidden;
	margin-bottom: 24rpx;
	position: relative;
}
.progress-bar-fill {
	height: 100%;
	background: linear-gradient(90deg, #2ed573, #1abc9c);
	border-radius: 8rpx;
	transition: width 0.3s ease;
	position: relative;
}
.progress-bar-glow {
	position: absolute;
	top: -2rpx;
	width: 40rpx;
	height: 20rpx;
	background: rgba(46,213,115,0.3);
	border-radius: 50%;
	filter: blur(8rpx);
	transition: left 0.3s ease;
}
.progress-info {
	display: flex;
	justify-content: center;
	align-items: center;
	gap: 16rpx;
}
.progress-text {
	font-size: 32rpx;
	font-weight: 900;
}
.progress-speed {
	font-size: 22rpx;
	color: #bbb;
}

/* ========== 底部栏 ========== */
.bottom-bar {
	position: fixed;
	bottom: 0;
	left: 0;
	right: 0;
	display: flex;
	align-items: center;
	padding: 16rpx 28rpx;
	padding-bottom: calc(16rpx + env(safe-area-inset-bottom));
	z-index: 100;
	background: transparent;
}
.bar-glass {
	position: absolute;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	background: rgba(255,255,255,0.92);
	backdrop-filter: blur(24px);
	box-shadow: 0 -4rpx 40rpx rgba(46,213,115,0.06);
	z-index: -1;
}
.bar-glass::after {
	content: '';
	position: absolute;
	top: 0;
	left: 10%;
	right: 10%;
	height: 1rpx;
	background: linear-gradient(90deg, transparent, rgba(255,255,255,0.6), rgba(46,213,115,0.08), rgba(255,255,255,0.6), transparent);
	pointer-events: none;
}
.bar-left {
	display: flex;
	align-items: center;
	gap: 28rpx;
	margin-right: 24rpx;
}
.bar-btn {
	display: flex;
	flex-direction: column;
	align-items: center;
	background: none;
	border: none;
	padding: 0;
	line-height: 1;
	margin: 0;
}
.bar-btn::after {
	display: none;
}
.share-btn {
	margin-left: 0;
	padding: 0;
}
.bar-icon-circle {
	width: 64rpx;
	height: 64rpx;
	border-radius: 50%;
	background: #f5f5fa;
	display: flex;
	align-items: center;
	justify-content: center;
	transition: all 0.3s;
}
.bar-icon-circle:active {
	transform: scale(0.9) translateY(2rpx);
	background: #ebebf0;
}
.bar-icon-circle.active {
	background: rgba(255,71,87,0.08);
}
.bar-icon {
	font-size: 36rpx;
	color: #888;
	transition: all 0.3s;
}
.bar-icon.active {
	color: #ff4757;
}
.bar-label {
	font-size: 20rpx;
	color: #aaa;
	margin-top: 6rpx;
	font-weight: 600;
}
.bar-right {
	flex: 1;
	display: flex;
	align-items: center;
}
.action-btn {
	flex: 1;
	height: 96rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	gap: 10rpx;
	border-radius: 48rpx;
	font-size: 30rpx;
	font-weight: 800;
	color: #fff;
	position: relative;
	overflow: hidden;
}
.action-btn::after {
	content: '';
	position: absolute;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	background: linear-gradient(to bottom, rgba(255,255,255,0.15), transparent);
	pointer-events: none;
}
.action-btn-icon {
	font-size: 30rpx;
}
.free-btn {
	box-shadow: 0 10rpx 32rpx rgba(0,0,0,0.15);
}
.free-btn:active {
	transform: scale(0.97) translateY(2rpx);
	box-shadow: 0 4rpx 12rpx rgba(0,0,0,0.1);
}
.buy-btn {
	background: linear-gradient(135deg, #ff4757, #ff6b81);
	box-shadow: 0 10rpx 32rpx rgba(255,71,87,0.35);
}
.buy-btn:active {
	transform: scale(0.97) translateY(2rpx);
	box-shadow: 0 4rpx 12rpx rgba(255,71,87,0.21);
}
.member-btn {
	background: linear-gradient(135deg, #f59e0b, #fbbf24);
	box-shadow: 0 10rpx 32rpx rgba(245,158,11,0.35);
}
.member-btn:active {
	transform: scale(0.97) translateY(2rpx);
	box-shadow: 0 4rpx 12rpx rgba(245,158,11,0.21);
}

/* ========== 加载 ========== */
.loading-mask {
	position: fixed;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	background: linear-gradient(180deg, #f0f2ff, #f5f6fa);
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	z-index: 200;
}
.loading-anim {
	display: flex;
	flex-direction: column;
	align-items: center;
	margin-bottom: 28rpx;
}
.loading-ring {
	width: 80rpx;
	height: 80rpx;
	border-radius: 50%;
	border: 5rpx solid #e8e8f0;
	animation: spin 0.8s linear infinite;
	position: relative;
}
.ring-inner {
	position: absolute;
	top: 8rpx;
	left: 8rpx;
	right: 8rpx;
	bottom: 8rpx;
	border-radius: 50%;
	border: 4rpx solid transparent;
	animation: spin 0.6s linear infinite reverse;
}
.loading-dots {
	display: flex;
	gap: 10rpx;
	margin-top: 24rpx;
}
.ldot {
	width: 12rpx;
	height: 12rpx;
	border-radius: 50%;
	background: #2ed573;
	animation: dot-bounce 1.4s ease-in-out infinite;
}
.ldot:nth-child(2) { animation-delay: 0.2s; }
.ldot:nth-child(3) { animation-delay: 0.4s; }
@keyframes dot-bounce {
	0%, 80%, 100% { transform: scale(0.6); opacity: 0.4; }
	40% { transform: scale(1); opacity: 1; }
}
.loading-text {
	font-size: 28rpx;
	color: #999;
	font-weight: 600;
}

/* ========== 加载失败 ========== */
.error-mask {
	position: fixed;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	background: linear-gradient(180deg, #f0f2ff, #f5f6fa);
	display: flex;
	align-items: center;
	justify-content: center;
	z-index: 200;
}
.error-card {
	background: #fff;
	border-radius: 32rpx;
	padding: 64rpx 48rpx;
	width: 580rpx;
	text-align: center;
	box-shadow: 0 16rpx 56rpx rgba(0,0,0,0.06);
}
.error-icon-wrap {
	width: 108rpx;
	height: 108rpx;
	background: linear-gradient(135deg, #fee2e2, #fecaca);
	border-radius: 50%;
	display: flex;
	align-items: center;
	justify-content: center;
	margin: 0 auto 32rpx;
	position: relative;
}
.error-icon-bg {
	position: absolute;
	top: -6rpx;
	left: -6rpx;
	right: -6rpx;
	bottom: -6rpx;
	border-radius: 50%;
	border: 3rpx dashed rgba(239,68,68,0.15);
	animation: ring-rotate 10s linear infinite;
}
.error-icon {
	font-size: 52rpx;
	color: #ef4444;
	font-weight: 900;
}
.error-title {
	font-size: 36rpx;
	font-weight: 800;
	color: #1a1a2e;
	display: block;
	margin-bottom: 14rpx;
}
.error-desc {
	font-size: 28rpx;
	color: #999;
	display: block;
	margin-bottom: 40rpx;
	line-height: 1.6;
}
.error-retry-btn {
	background: linear-gradient(135deg, #2ed573, #27ae60);
	text-align: center;
	padding: 28rpx;
	border-radius: 48rpx;
	font-size: 30rpx;
	font-weight: 800;
	color: #fff;
	box-shadow: 0 10rpx 32rpx rgba(46,213,115,0.3);
	display: flex;
	align-items: center;
	justify-content: center;
	gap: 10rpx;
}
.error-retry-btn:active {
	transform: scale(0.97) translateY(2rpx);
	box-shadow: 0 4rpx 12rpx rgba(46,213,115,0.18);
}
.retry-icon {
	font-size: 32rpx;
}
</style>
