const fs = require('fs');
const BASE = 'E:/ziliaoku/miniprogram/unpackage/dist/dev/mp-weixin';

function enhanceFile(file, marker, css) {
    let content = fs.readFileSync(file, 'utf8');
    if (content.indexOf(marker) === -1) {
        content += '\n' + css;
        fs.writeFileSync(file, content);
        console.log('Enhanced: ' + file.split('/').pop());
    } else {
        console.log('Already enhanced: ' + file.split('/').pop());
    }
}

// Index page enhancements
enhanceFile(BASE + '/pages/index/index.wxss', 'advancedEffects', `
/* ========== Advanced Effects ========== */

/* Gradient text */
.site-name {
  background: linear-gradient(135deg, #fff, #e0e7ff);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

/* Search bar glow */
.search-bar {
  position: relative;
}
.search-bar::after {
  content: '';
  position: absolute;
  top: -4rpx; left: -4rpx; right: -4rpx; bottom: -4rpx;
  background: linear-gradient(135deg, rgba(46,213,115,0.2), rgba(74,144,217,0.2));
  border-radius: 44rpx;
  z-index: -1;
  animation: searchGlow 3s ease-in-out infinite;
}
@keyframes searchGlow {
  0%, 100% { opacity: 0.3; }
  50% { opacity: 0.8; }
}

/* Quick entry hover */
.quick-item { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
.quick-item:active { transform: translateY(-4rpx) scale(0.92); }
.quick-icon { transition: all 0.3s; position: relative; overflow: hidden; }
.quick-icon::after {
  content: '';
  position: absolute;
  top: -50%; left: -50%; width: 200%; height: 200%;
  background: linear-gradient(45deg, transparent, rgba(255,255,255,0.3), transparent);
  transform: rotate(45deg);
  opacity: 0;
  transition: all 0.5s;
}
.quick-item:active .quick-icon::after { opacity: 1; }

/* Category chip active pulse */
.cat-chip:active {
  animation: chipPulse 0.5s ease-out;
}
@keyframes chipPulse {
  0% { transform: scale(1); }
  50% { transform: scale(0.92); box-shadow: 0 4rpx 20rpx rgba(46,213,115,0.4); }
  100% { transform: scale(1); }
}

/* Hot card shine */
.hot-card { position: relative; overflow: hidden; }
.hot-card::before {
  content: '';
  position: absolute;
  top: 0; left: -100%; width: 100%; height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.15), transparent);
  transition: left 0.6s;
  z-index: 2;
}
.hot-card:active::before { left: 100%; }

/* Resource list stagger animation */
.resource-item { animation: resFadeIn 0.4s ease-out both; }
.resource-item:nth-child(1) { animation-delay: 0.05s; }
.resource-item:nth-child(2) { animation-delay: 0.1s; }
.resource-item:nth-child(3) { animation-delay: 0.15s; }
.resource-item:nth-child(4) { animation-delay: 0.2s; }
.resource-item:nth-child(5) { animation-delay: 0.25s; }
@keyframes resFadeIn {
  from { opacity: 0; transform: translateY(16rpx); }
  to { opacity: 1; transform: translateY(0); }
}

/* Price free shine */
.hot-free, .res-free {
  position: relative; overflow: hidden;
}
.hot-free::after, .res-free::after {
  content: '';
  position: absolute;
  top: 0; left: -100%; width: 50%; height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.5), transparent);
  animation: priceShine 3s ease-in-out infinite;
}
@keyframes priceShine {
  0%, 100% { left: -100%; }
  50% { left: 200%; }
}

/* Loading dots */
.dot { animation: dotElastic 1.4s infinite; }
.dot:nth-child(1) { animation-delay: 0s; }
.dot:nth-child(2) { animation-delay: 0.2s; }
.dot:nth-child(3) { animation-delay: 0.4s; }
@keyframes dotElastic {
  0%, 100% { transform: scale(0.5); opacity: 0.3; }
  50% { transform: scale(1.3); opacity: 1; }
}

/* Empty state breathing */
.empty-icon { animation: emptyBreath 3s ease-in-out infinite; }
@keyframes emptyBreath {
  0%, 100% { transform: scale(1); opacity: 0.7; }
  50% { transform: scale(1.15); opacity: 1; }
}
`);

// Detail page enhancements
enhanceFile(BASE + '/pages/resource/detail.wxss', 'detailEffects', `
/* ========== Detail Advanced Effects ========== */

/* Cover gradient overlay */
.cover { position: relative; }
.cover::after {
  content: '';
  position: absolute;
  bottom: 0; left: 0; right: 0; height: 100rpx;
  background: linear-gradient(transparent, rgba(0,0,0,0.2));
}

/* Title fade in */
.title { animation: titleIn 0.6s ease-out; }
@keyframes titleIn {
  from { opacity: 0; transform: translateY(10rpx); }
  to { opacity: 1; transform: translateY(0); }
}

/* Price pulse */
.price-paid { animation: pricePulse 2s ease-in-out infinite; }
@keyframes pricePulse {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.03); }
}

/* Download button gradient animation */
.download-btn {
  background-size: 200% 200%;
  animation: gradientFlow 3s ease infinite;
}
@keyframes gradientFlow {
  0% { background-position: 0% 50%; }
  50% { background-position: 100% 50%; }
  100% { background-position: 0% 50%; }
}

/* Tags pop in */
.tag { animation: tagPop 0.3s ease-out both; }
.tag:nth-child(1) { animation-delay: 0.1s; }
.tag:nth-child(2) { animation-delay: 0.2s; }
.tag:nth-child(3) { animation-delay: 0.3s; }
@keyframes tagPop {
  from { opacity: 0; transform: scale(0.7); }
  to { opacity: 1; transform: scale(1); }
}

/* Bottom bar blur */
.bottom-bar {
  backdrop-filter: blur(20px);
  background: rgba(255,255,255,0.9);
}
`);

// User page enhancements
enhanceFile(BASE + '/pages/user/index.wxss', 'userEffects', `
/* ========== User Advanced Effects ========== */

/* User card shine */
.user-card { position: relative; overflow: hidden; }
.user-card::before {
  content: '';
  position: absolute;
  top: -50%; left: -50%; width: 200%; height: 200%;
  background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 60%);
  animation: cardShine 8s linear infinite;
}
@keyframes cardShine {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

/* Avatar ring */
.avatar { position: relative; }
.avatar::before {
  content: '';
  position: absolute;
  top: -6rpx; left: -6rpx; right: -6rpx; bottom: -6rpx;
  background: linear-gradient(135deg, #4A90D9, #2ed573, #f59e0b);
  border-radius: 50%;
  z-index: -1;
  animation: ringRotate 4s linear infinite;
}
@keyframes ringRotate {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

/* Menu items slide in */
.menu-item { animation: menuSlide 0.4s ease-out both; }
.menu-item:nth-child(1) { animation-delay: 0.05s; }
.menu-item:nth-child(2) { animation-delay: 0.1s; }
.menu-item:nth-child(3) { animation-delay: 0.15s; }
.menu-item:nth-child(4) { animation-delay: 0.2s; }
.menu-item:nth-child(5) { animation-delay: 0.25s; }
@keyframes menuSlide {
  from { opacity: 0; transform: translateX(-20rpx); }
  to { opacity: 1; transform: translateX(0); }
}

/* Stats bounce */
.stat-num { animation: numBounce 0.6s ease-out; }
@keyframes numBounce {
  0% { transform: scale(0.5); opacity: 0; }
  60% { transform: scale(1.1); }
  100% { transform: scale(1); opacity: 1; }
}
`);

// Category list enhancements
enhanceFile(BASE + '/pages/category/list.wxss', 'catEffects', `
/* ========== Category Advanced Effects ========== */

/* Sidebar active glow */
.sidebar-item.active {
  position: relative;
  background: linear-gradient(90deg, rgba(46,213,115,0.1), transparent);
}
.sidebar-item.active::after {
  content: '';
  position: absolute;
  right: 0; top: 50%; transform: translateY(-50%);
  width: 6rpx; height: 60%;
  background: linear-gradient(180deg, #4A90D9, #2ed573);
  border-radius: 3rpx 0 0 3rpx;
  box-shadow: 0 0 12rpx rgba(46,213,115,0.5);
}

/* Resource cards stagger */
.resource-card { animation: catCardIn 0.4s ease-out both; }
.resource-card:nth-child(1) { animation-delay: 0.05s; }
.resource-card:nth-child(2) { animation-delay: 0.1s; }
.resource-card:nth-child(3) { animation-delay: 0.15s; }
@keyframes catCardIn {
  from { opacity: 0; transform: translateY(10rpx); }
  to { opacity: 1; transform: translateY(0); }
}

/* Sort item active */
.sort-item.active {
  position: relative;
}
.sort-item.active::after {
  content: '';
  position: absolute;
  bottom: -8rpx; left: 50%; transform: translateX(-50%);
  width: 32rpx; height: 4rpx;
  background: linear-gradient(90deg, #4A90D9, #2ed573);
  border-radius: 2rpx;
}
`);

// VIP page enhancements
enhanceFile(BASE + '/pages/user/vip.wxss', 'vipEffects', `
/* ========== VIP Advanced Effects ========== */

/* VIP header particles */
.vip-header { position: relative; overflow: hidden; }
.vip-header::before {
  content: '';
  position: absolute;
  top: -50%; left: -50%; width: 200%; height: 200%;
  background: radial-gradient(circle at 30% 50%, rgba(255,215,0,0.1) 0%, transparent 50%),
              radial-gradient(circle at 70% 50%, rgba(46,213,115,0.1) 0%, transparent 50%);
  animation: vipParticle 10s linear infinite;
}
@keyframes vipParticle {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

/* Plan card select animation */
.plan-card { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
.plan-card.active {
  transform: scale(1.02);
  box-shadow: 0 8rpx 32rpx rgba(46,213,115,0.2);
}
.plan-card:active { transform: scale(0.98); }

/* Buy button pulse */
.buy-btn {
  animation: buyPulse 2s ease-in-out infinite;
}
@keyframes buyPulse {
  0%, 100% { box-shadow: 0 8rpx 24rpx rgba(46,213,115,0.3); }
  50% { box-shadow: 0 12rpx 36rpx rgba(46,213,115,0.5); }
}
`);

// Search page enhancements
enhanceFile(BASE + '/pages/resource/search.wxss', 'searchEffects', `
/* ========== Search Advanced Effects ========== */

/* Search input focus glow */
.search-input { transition: all 0.3s; }
.search-input:focus {
  box-shadow: 0 0 0 4rpx rgba(46,213,115,0.15);
}

/* Hot keyword tags bounce */
.keyword { transition: all 0.2s; }
.keyword:active { transform: scale(0.9); }

/* Results stagger */
.result-card { animation: resultIn 0.3s ease-out both; }
.result-card:nth-child(1) { animation-delay: 0.05s; }
.result-card:nth-child(2) { animation-delay: 0.1s; }
.result-card:nth-child(3) { animation-delay: 0.15s; }
@keyframes resultIn {
  from { opacity: 0; transform: translateY(10rpx); }
  to { opacity: 1; transform: translateY(0); }
}
`);

// Orders page enhancements
enhanceFile(BASE + '/pages/user/orders.wxss', 'orderEffects', `
/* ========== Order Advanced Effects ========== */

/* Tab active indicator */
.tab.active {
  position: relative;
}
.tab.active::after {
  content: '';
  position: absolute;
  bottom: 0; left: 50%; transform: translateX(-50%);
  width: 40rpx; height: 4rpx;
  background: linear-gradient(90deg, #4A90D9, #2ed573);
  border-radius: 2rpx;
  animation: tabIndicator 0.3s ease-out;
}
@keyframes tabIndicator {
  from { width: 0; opacity: 0; }
  to { width: 40rpx; opacity: 1; }
}

/* Order card slide */
.order-card { animation: orderSlide 0.4s ease-out both; }
@keyframes orderSlide {
  from { opacity: 0; transform: translateX(-10rpx); }
  to { opacity: 1; transform: translateX(0); }
}

/* Status badge pulse */
.order-status { animation: statusPulse 2s ease-in-out infinite; }
@keyframes statusPulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.7; }
}
`);

console.log('All pages enhanced with advanced effects!');
