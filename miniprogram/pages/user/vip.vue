<template>
	<view class="page">
		<scroll-view scroll-y class="scroll">
			<!-- 顶部 -->
			<view class="header">
				<view class="header-inner">
					<text class="h-crown">👑</text>
					<text class="h-title" v-if="isVip">尊享VIP会员</text>
					<text class="h-title" v-else>VIP会员</text>
					<text class="h-sub" v-if="isVip">{{ vipInfo.vip_level_name || 'VIP' }} · 有效期至 {{ formatExpire(vipInfo.vip_expire_at) }}</text>
					<text class="h-sub" v-else>全站资源免费下载 · 极速不限速</text>
					<view class="h-tag" v-if="isVip && expireCountdown">
						<text class="h-tag-t">{{ expireCountdown }}</text>
					</view>
				</view>
			</view>

			<!-- 权益 -->
			<view class="card">
				<text class="card-title">会员权益</text>
				<view class="ben-row">
					<view class="ben" v-for="(b,i) in benefits" :key="i">
						<text class="ben-ico">{{ b.icon }}</text>
						<text class="ben-t">{{ b.name }}</text>
					</view>
				</view>
			</view>

			<!-- 对比 -->
			<view class="card">
				<text class="card-title">权益对比</text>
				<view class="tbl">
					<view class="tbl-hd">
						<text class="tbl-c l">权益</text>
						<text class="tbl-c m">普通</text>
						<text class="tbl-c r">VIP</text>
					</view>
					<view class="tbl-row" v-for="(r,i) in rows" :key="i">
						<text class="tbl-c l">{{ r.label }}</text>
						<text class="tbl-c m">{{ r.normal }}</text>
						<text class="tbl-c r vip" :style="tPri">{{ r.vip }}</text>
					</view>
				</view>
			</view>

			<!-- 套餐 -->
			<view class="card plan-card">
				<text class="card-title">选择套餐</text>
				<view class="plans">
					<view
						class="p"
						:class="{ on: selectedPlan === idx, hot: item.recommended }"
						v-for="(item, idx) in plans"
						:key="idx"
						@tap="selectedPlan = idx"
					>
						<view class="p-left">
							<view class="p-radio" :class="{ sel: selectedPlan === idx }">
								<view class="p-dot" v-if="selectedPlan === idx"></view>
							</view>
							<view class="p-info">
								<view class="p-name-row">
									<text class="p-name">{{ item.name }}</text>
									<view class="p-badge" v-if="item.recommended">最受欢迎</view>
									<view class="p-save" :style="tPri" v-if="item.save_percent">省{{ item.save_percent }}%</view>
								</view>
								<text class="p-daily" v-if="item.unit">{{ item.unit }}</text>
							</view>
						</view>
						<view class="p-right">
							<view class="p-pr">
								<text class="p-sym">¥</text>
								<text class="p-val">{{ item.price }}</text>
							</view>
							<text class="p-ori" v-if="item.original_price">¥{{ item.original_price }}</text>
						</view>
					</view>
				</view>
			</view>

			<!-- 保障 -->
			<view class="trusts">
				<text class="tr" v-for="(t,i) in trusts" :key="i">{{ t.icon }} {{ t.text }}</text>
			</view>

			<!-- FAQ -->
			<view class="card">
				<text class="card-title">常见问题</text>
				<view class="faq" v-for="(f,i) in faqs" :key="i" @tap="toggleFaq(i)">
					<view class="faq-q">
						<text class="faq-qt">{{ f.q }}</text>
						<text class="faq-ar" :style="f.open ? tPri : ''" :class="{ open: f.open }">›</text>
					</view>
					<text class="faq-a" v-if="f.open">{{ f.a }}</text>
				</view>
			</view>

			<view style="height: 200rpx;"></view>
		</scroll-view>

		<!-- 底部 -->
		<view class="bar">
			<view class="bar-left">
				<text class="bar-lab">¥</text>
				<text class="bar-num">{{ selectedPlanData.price || 0 }}</text>
			</view>
			<view class="bar-btn" @tap="handleBuy" :style="'background:' + tc.primary + ';'">
				<text>{{ isVip ? '续费会员' : '立即开通' }}</text>
			</view>
		</view>
	</view>
</template>

<script>
import http from '@/utils/http.js';
export default {
	data(){return{
		isVip:false,vipInfo:{},selectedPlan:2,expireCountdown:'',countdownTimer:null,
		benefits:[
			{icon:'📥',name:'免费下载'},{icon:'⚡',name:'极速下载'},
			{icon:'🎯',name:'专属资源'},{icon:'💬',name:'优先客服'},
			{icon:'📦',name:'批量下载'},{icon:'🛡️',name:'无广告'}
		],
		rows:[
			{label:'每日下载',normal:'3次',vip:'无限'},
			{label:'付费资源',normal:'需购买',vip:'免费'},
			{label:'下载速度',normal:'普通',vip:'加速'},
			{label:'专属客服',normal:'—',vip:'✓'},
			{label:'批量下载',normal:'—',vip:'✓'}
		],
		plans:[
			{name:'月度',price:19.9,original_price:29.9,unit:'¥19.9/月',duration:30,recommended:false},
			{name:'季度',price:49.9,original_price:89.7,unit:'¥16.6/月',duration:90,recommended:false},
			{name:'半年',price:88,original_price:179.4,unit:'¥14.7/月',duration:180,recommended:true},
			{name:'年度',price:148,original_price:358.8,unit:'¥12.3/月',duration:365,recommended:false}
		],
		trusts:[
			{icon:'🔒',text:'安全支付'},{icon:'⚡',text:'即时生效'},
			{icon:'🔄',text:'随时续费'},{icon:'📞',text:'专属客服'}
		],
		faqs:[
			{q:'VIP到期后已下载资源还能用吗？',a:'已下载到本地的资源可正常使用，但无法再免费下载新资源。',open:false},
			{q:'支持多设备同时使用吗？',a:'支持2台设备同时登录，超出需先退出其他设备。',open:false},
			{q:'可以退款吗？',a:'开通7天内未使用VIP权益可申请全额退款，请联系客服。',open:false}
		]
	}},
	computed:{selectedPlanData(){return this.plans[this.selectedPlan]||{}}},
	onLoad(){this.loadVipStatus();this.loadPlans();},
	onUnload(){if(this.countdownTimer)clearInterval(this.countdownTimer);},
	methods:{
		formatExpire(d){return d?d.slice(0,10):'';},
		toggleFaq(i){this.$set(this.faqs[i],'open',!this.faqs[i].open);},
		async loadVipStatus(){try{const r=await http.get('/api/user/vipStatus');if(r.code===0&&r.data){this.isVip=!!r.data.is_vip;this.vipInfo=r.data;if(this.isVip&&this.vipInfo.vip_expire_at)this.startCountdown();}}catch(e){}},
		startCountdown(){this.updateCountdown();this.countdownTimer=setInterval(()=>this.updateCountdown(),60000);},
		updateCountdown(){if(!this.vipInfo.vip_expire_at)return;const diff=new Date(this.vipInfo.vip_expire_at).getTime()-Date.now();if(diff<=0){this.expireCountdown='已到期';clearInterval(this.countdownTimer);return;}const d=Math.floor(diff/86400000),h=Math.floor((diff%86400000)/3600000);this.expireCountdown=d>0?`剩余${d}天${h}小时`:`剩余${h}小时`;},
		async loadPlans(){try{const r=await http.get('/api/vip/plans');if(r.code===0&&r.data&&r.data.length>0){this.plans=r.data.map((item,idx)=>({...item,unit:item.daily_price?('¥'+item.daily_price+'/天'):'',recommended:idx===2}));}}catch(e){}},
		async handleBuy(){const t=uni.getStorageSync('token');if(!t){uni.showModal({title:'提示',content:'请先登录',confirmText:'去登录',success:r=>{if(r.confirm)uni.switchTab({url:'/pages/user/index'});}});return;}const p=this.selectedPlanData;if(!p||!p.price)return;uni.showLoading({title:'创建订单...'});try{const r=await http.post('/api/vip/createOrder',{plan_id:p.id||this.selectedPlan,duration:p.duration,pay_type:'wechat'});if(r.code===0&&r.data){uni.hideLoading();const pp=r.data.pay_params||{},oid=r.data.order_id;if(pp.wxpay){uni.requestPayment({provider:'wxpay',...pp.wxpay,success:async()=>{await this.onOk(oid);},fail:e=>{uni.showToast({title:e.errMsg?.includes('cancel')?'已取消':'支付失败',icon:'none'});}});}else if(pp.virtual){uni.requestVirtualPayment({...pp.virtual,success:async()=>{await this.onOk(oid);},fail:e=>{uni.showToast({title:e.errMsg?.includes('cancel')?'已取消':'支付失败',icon:'none'});}});}}else{uni.hideLoading();uni.showToast({title:r.msg||'创建失败',icon:'none'});}}catch(e){uni.hideLoading();uni.showToast({title:'支付异常',icon:'none'});}},
		async onOk(oid){try{await http.post('/api/vip/callback',{order_id:oid});}catch(e){}this.isVip=true;uni.showToast({title:'开通成功',icon:'success'});this.loadVipStatus();}
	},
	onShareAppMessage(){return{title:'VIP会员',path:'/pages/user/vip'};}
};
</script>

<style scoped>
.page{min-height:100vh;background:#f5f5f7;}
.scroll{height:100vh;}

/* 顶部 */
.header{background:#fff;padding:80rpx 40rpx 48rpx;}
.header-inner{display:flex;flex-direction:column;align-items:center;}
.h-crown{font-size:72rpx;margin-bottom:16rpx;}
.h-title{font-size:40rpx;font-weight:700;color:#1d1d1f;letter-spacing:2rpx;}
.h-sub{font-size:26rpx;color:#86868b;margin-top:10rpx;}
.h-tag{margin-top:16rpx;background:#f5f5f7;padding:8rpx 24rpx;border-radius:20rpx;}
.h-tag-t{font-size:24rpx;color:#1d1d1f;font-weight:500;}

/* 卡片 */
.card{background:#fff;margin:24rpx;border-radius:20rpx;padding:32rpx;}
.card-title{font-size:30rpx;font-weight:700;color:#1d1d1f;margin-bottom:24rpx;display:block;}

/* 权益 */
.ben-row{display:flex;flex-wrap:wrap;}
.ben{width:33.333%;display:flex;flex-direction:column;align-items:center;padding:20rpx 0;}
.ben-ico{font-size:48rpx;margin-bottom:10rpx;}
.ben-t{font-size:24rpx;color:#1d1d1f;font-weight:500;}

/* 对比表 */
.tbl{border:1rpx solid #e8e8e8;border-radius:12rpx;overflow:hidden;}
.tbl-hd{display:flex;background:#fafafa;padding:20rpx 0;}
.tbl-c{flex:1;text-align:center;font-size:24rpx;color:#86868b;}
.tbl-c.l{flex:1.2;text-align:left;padding-left:24rpx;font-weight:600;color:#1d1d1f;}
.tbl-hd .tbl-c{font-weight:600;color:#1d1d1f;font-size:26rpx;}
.tbl-row{display:flex;padding:18rpx 0;border-top:1rpx solid #f0f0f0;}
.tbl-c.r.vip{font-weight:700;}

/* 套餐 */
.plan-card{padding:32rpx 0;}
.plan-card .card-title{padding:0 32rpx;}
.plans{padding:0 24rpx;}
.p{
	display:flex;align-items:center;justify-content:space-between;
	background:#fff;
	border:2rpx solid #e8e8e8;
	border-radius:20rpx;
	padding:28rpx 24rpx;
	margin-bottom:16rpx;
	transition:all .2s;
	position:relative;
}
.p:last-child{margin-bottom:0;}
.p.on{
	background:#f8fef9;
	box-shadow:0 0 0 4rpx rgba(46,213,115,.08);
}
.p.hot::before{
	content:'';
	position:absolute;left:0;top:20%;bottom:20%;width:6rpx;
	border-radius:0 4rpx 4rpx 0;
}
.p-left{display:flex;align-items:center;gap:20rpx;flex:1;}
.p-radio{
	width:40rpx;height:40rpx;
	border-radius:50%;
	border:3rpx solid #d4d4d4;
	display:flex;align-items:center;justify-content:center;
	flex-shrink:0;
	transition:all .2s;
}
.p-radio.sel{}
.p-dot{width:16rpx;height:16rpx;border-radius:50%;background:#fff;}
.p-info{display:flex;flex-direction:column;gap:6rpx;}
.p-name-row{display:flex;align-items:center;gap:10rpx;}
.p-name{font-size:30rpx;font-weight:700;color:#1d1d1f;}
.p-badge{
	font-size:18rpx;font-weight:600;color:#fff;
	padding:4rpx 14rpx;
	border-radius:8rpx;
}
.p-save{
	font-size:18rpx;font-weight:600;
	background:rgba(46,213,115,.08);
	padding:4rpx 12rpx;
	border-radius:8rpx;
}
.p-daily{font-size:22rpx;color:#86868b;}
.p-right{display:flex;flex-direction:column;align-items:flex-end;gap:4rpx;}
.p-pr{display:flex;align-items:baseline;}
.p-sym{font-size:22rpx;color:#1d1d1f;font-weight:600;}
.p-val{font-size:44rpx;color:#1d1d1f;font-weight:700;line-height:1;}
.p-ori{font-size:20rpx;color:#ccc;text-decoration:line-through;}

/* 保障 */
.trusts{display:flex;justify-content:center;gap:32rpx;margin:24rpx;padding:20rpx 0;}
.tr{font-size:22rpx;color:#86868b;}

/* FAQ */
.faq{border-bottom:1rpx solid #f0f0f0;padding:24rpx 0;}
.faq:last-child{border-bottom:none;}
.faq-q{display:flex;justify-content:space-between;align-items:center;}
.faq-qt{font-size:28rpx;color:#1d1d1f;font-weight:500;flex:1;}
.faq-ar{font-size:32rpx;color:#ccc;transition:transform .2s;}
.faq-ar.open{transform:rotate(90deg);}
.faq-a{font-size:26rpx;color:#86868b;line-height:1.7;margin-top:16rpx;display:block;}

/* 底部 */
.bar{
	position:fixed;bottom:0;left:0;right:0;
	display:flex;align-items:center;justify-content:space-between;
	background:#fff;
	padding:20rpx 32rpx;
	padding-bottom:calc(20rpx + env(safe-area-inset-bottom));
	border-top:1rpx solid #e8e8e8;
	z-index:100;
}
.bar-left{display:flex;align-items:baseline;}
.bar-lab{font-size:24rpx;color:#1d1d1f;font-weight:600;}
.bar-num{font-size:48rpx;color:#1d1d1f;font-weight:700;line-height:1;margin-left:4rpx;}
.bar-btn{
	padding:24rpx 56rpx;
	border-radius:48rpx;
	font-size:30rpx;
	color:#fff;
	font-weight:600;
}
.bar-btn:active{opacity:.85;}
</style>
