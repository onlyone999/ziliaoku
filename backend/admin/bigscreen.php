<?php
session_start();
if (empty($_SESSION['admin_id'])) { header('Location: login.php'); exit; }
require_once __DIR__ . '/../core/Database.php';
$db = Database::getInstance();

$totalUsers=(int)$db->fetch("SELECT COUNT(*) v FROM users")['v'];
$totalResources=(int)$db->fetch("SELECT COUNT(*) v FROM resources")['v'];
$totalDownloads=(int)$db->fetch("SELECT COALESCE(SUM(download_count),0) v FROM resources")['v'];
$totalRevenue=(float)$db->fetch("SELECT COALESCE(SUM(pay_amount),0) v FROM orders WHERE status IN('paid','pending')")['v'];
$totalOrders=(int)$db->fetch("SELECT COUNT(*) v FROM orders")['v'];
$totalPoints=(int)$db->fetch("SELECT COALESCE(SUM(points),0) v FROM users")['v'];
$pendingRes=(int)$db->fetch("SELECT COUNT(*) v FROM resources WHERE status='pending'")['v'];
$vipCount=(int)$db->fetch("SELECT COUNT(*) v FROM users WHERE vip_level>0 AND vip_expire_at>NOW()")['v'];
$d=date('Y-m-d');$yd=date('Y-m-d',strtotime('-1 day'));
$tU=(int)$db->fetch("SELECT COUNT(*) v FROM users WHERE DATE(created_at)='$d'")['v'];$yU=(int)$db->fetch("SELECT COUNT(*) v FROM users WHERE DATE(created_at)='$yd'")['v'];
$tR=(int)$db->fetch("SELECT COUNT(*) v FROM resources WHERE DATE(created_at)='$d'")['v'];$yR=(int)$db->fetch("SELECT COUNT(*) v FROM resources WHERE DATE(created_at)='$yd'")['v'];
$tD=(int)$db->fetch("SELECT COALESCE(SUM(download_count),0) v FROM resources WHERE DATE(updated_at)='$d'")['v'];$yD=(int)$db->fetch("SELECT COALESCE(SUM(download_count),0) v FROM resources WHERE DATE(updated_at)='$yd'")['v'];
$tV=(float)$db->fetch("SELECT COALESCE(SUM(pay_amount),0) v FROM orders WHERE DATE(created_at)='$d'")['v'];$yV=(float)$db->fetch("SELECT COALESCE(SUM(pay_amount),0) v FROM orders WHERE DATE(created_at)='$yd'")['v'];
function pct($c,$p){if($p==0)return $c>0?'+100%':'0%';return ($c>=0&&$c>=$p?'+':'').number_format(($c-$p)/max(1,$p)*100,1).'%';}
function cls($c,$p){return $c>$p?'up':($c<$p?'dn':'eq');}

$oT=$db->fetchAll("SELECT DATE(created_at) d,COUNT(*) c,COALESCE(SUM(pay_amount),0) r FROM orders WHERE created_at>=DATE_SUB(CURDATE(),INTERVAL 30 DAY) GROUP BY DATE(created_at) ORDER BY d");
$td=[];$tc=[];$tr=[];
for($i=29;$i>=0;$i--){$dd=date('Y-m-d',strtotime("-{$i} days"));$td[]=$dd===$d?'今':date('j',strtotime($dd));$f=false;foreach($oT as $r){if($r['d']===$dd){$tc[]=(int)$r['c'];$tr[]=(float)$r['r'];$f=true;break;}}if(!$f){$tc[]=0;$tr[]=0;}}

$nU=$db->fetchAll("SELECT DATE(created_at) d,COUNT(*) c FROM users WHERE created_at>=DATE_SUB(CURDATE(),INTERVAL 7 DAY) GROUP BY DATE(created_at) ORDER BY d");
$nd=[];$nc=[];
for($i=6;$i>=0;$i--){$dd=date('Y-m-d',strtotime("-{$i} days"));$nd[]=$dd===$d?'今':date('D',strtotime($dd));$f=false;foreach($nU as $r){if($r['d']===$dd){$nc[]=(int)$r['c'];$f=true;break;}}if(!$f)$nc[]=0;}

$cS=$db->fetchAll("SELECT c.name,COUNT(r.id) cnt FROM categories c JOIN resources r ON c.id=r.category_id WHERE c.parent_id=0 GROUP BY c.id,c.name ORDER BY cnt DESC LIMIT 6");
$rsS=$db->fetchAll("SELECT status,COUNT(*) cnt FROM resources GROUP BY status");
$rsl=['approved'=>0,'pending'=>0,'rejected'=>0,'offline'=>0];foreach($rsS as $r)$rsl[$r['status']]=(int)$r['cnt'];
$mD=$db->fetchAll("SELECT DATE_FORMAT(created_at,'%m') m,COUNT(*) cnt FROM users WHERE created_at>=DATE_SUB(CURDATE(),INTERVAL 6 MONTH) GROUP BY m ORDER BY m");
$mL=[];$mV=[];
for($i=6;$i>=1;$i--){$m=date('m',strtotime("-{$i} months"));$mL[]=$m.'月';$f=false;foreach($mD as $r){if($r['m']===$m){$mV[]=(int)$r['cnt'];$f=true;break;}}if(!$f)$mV[]=0;}

$topRes=$db->fetchAll("SELECT title,download_count,view_count FROM resources WHERE status='approved' ORDER BY download_count DESC LIMIT 10");
$rO=$db->fetchAll("SELECT o.*,u.nickname FROM orders o LEFT JOIN users u ON o.user_id=u.id ORDER BY o.created_at DESC LIMIT 20");
if(empty($rO)){$rO=$db->fetchAll("SELECT id,order_no,pay_amount,status,created_at,'用户' nickname FROM orders ORDER BY created_at DESC LIMIT 20");}
$osm=['pending'=>['待支付','#eab308'],'paid'=>['已支付','#22c55e'],'refunded'=>['已退款','#60a5fa'],'cancelled'=>['已取消','#6b7280']];
$ptTop=$db->fetchAll("SELECT nickname,points FROM users WHERE status=1 ORDER BY points DESC LIMIT 8");
$dld=$db->fetchAll("SELECT DATE(updated_at) d,SUM(download_count) c FROM resources WHERE updated_at>=DATE_SUB(CURDATE(),INTERVAL 7 DAY) GROUP BY DATE(updated_at) ORDER BY d");
$dd7=[];$dc7=[];
for($i=6;$i>=0;$i--){$dd=date('Y-m-d',strtotime("-{$i} days"));$dd7[]=$dd===$d?'今':date('D',strtotime($dd));$f=false;foreach($dld as $r){if($r['d']===$dd){$dc7[]=(int)$r['c'];$f=true;break;}}if(!$f)$dc7[]=0;}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
<title>数据驾驶舱</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
html,body{width:100%;height:100%;overflow:hidden;background:#09090b}
body{font-family:'Inter','SF Pro Display','PingFang SC','Microsoft YaHei',system-ui,sans-serif;color:#a1a1aa;-webkit-font-smoothing:antialiased;-moz-osx-font-smoothing:grayscale}

.app{width:100%;height:100%;display:flex;flex-direction:column;padding:20px 28px 18px;background:radial-gradient(ellipse 80% 50% at 50% -20%,rgba(24,24,27,1),#09090b)}

/* 顶栏 */
.top{display:flex;align-items:center;justify-content:space-between;height:48px;flex-shrink:0;margin-bottom:20px}
.top-l{display:flex;align-items:baseline;gap:16px}
.logo{font-size:20px;font-weight:800;color:#fafafa;letter-spacing:-.5px}
.logo span{color:#71717a;font-weight:400;font-size:13px;margin-left:12px;letter-spacing:1px}
.top-r{display:flex;align-items:center;gap:16px}
.clk{font-size:12px;color:#52525b;font-variant-numeric:tabular-nums;font-weight:500}
.btn-bk{padding:6px 16px;border-radius:8px;font-size:12px;background:#18181b;border:1px solid #27272a;color:#a1a1aa;text-decoration:none;transition:.2s;font-weight:500}
.btn-bk:hover{background:#27272a;color:#fafafa}

/* KPI行 */
.kpis{display:grid;grid-template-columns:repeat(7,1fr);gap:12px;flex-shrink:0;margin-bottom:18px}
.k{background:#18181b;border:1px solid #27272a;border-radius:14px;padding:18px 16px 14px;transition:.3s;position:relative;overflow:hidden}
.k:hover{border-color:#3f3f46;background:#1c1c1f;transform:translateY(-1px)}
.k-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:10px}
.k-label{font-size:12px;color:#71717a;font-weight:500}
.k-badge{font-size:10px;font-weight:600;padding:2px 8px;border-radius:6px}
.k-badge.up{color:#4ade80;background:rgba(74,222,128,.1)}.k-badge.dn{color:#f87171;background:rgba(248,113,113,.1)}.k-badge.eq{color:#71717a;background:rgba(113,113,122,.1)}
.k-val{font-size:28px;font-weight:800;color:#fafafa;line-height:1;letter-spacing:-1px}
.k-sub{font-size:11px;color:#52525b;margin-top:6px;font-weight:400}

/* 三栏 */
.main{flex:1;display:grid;grid-template-columns:1fr 1.5fr 1fr;gap:14px;min-height:0}
.col{display:flex;flex-direction:column;gap:12px;min-height:0}

/* 面板 */
.card{background:#18181b;border:1px solid #27272a;border-radius:14px;display:flex;flex-direction:column;overflow:hidden;transition:.3s}
.card:hover{border-color:#3f3f46}
.card-h{height:40px;display:flex;align-items:center;padding:0 18px;font-size:13px;font-weight:600;color:#e4e4e7;border-bottom:1px solid #27272a;flex-shrink:0;letter-spacing:.2px}
.card-h .badge{margin-left:auto;font-size:10px;color:#52525b;font-weight:500;background:#09090b;padding:2px 10px;border-radius:6px;letter-spacing:1px}
.card-b{flex:1;padding:14px 16px;overflow:hidden;display:flex;flex-direction:column}
.ch{flex:1;min-height:0}

/* 列表 */
.it{display:flex;align-items:center;gap:10px;padding:7px 0;border-bottom:1px solid #27272a}
.it:last-child{border-bottom:none}
.it-rank{width:24px;height:24px;border-radius:7px;font-size:11px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;background:#27272a;color:#71717a}
.it-rank.top{background:rgba(234,179,8,.1);color:#facc15}
.it-info{flex:1;min-width:0}
.it-name{font-size:12px;color:#d4d4d8;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;font-weight:500}
.it-meta{font-size:10px;color:#52525b;margin-top:2px}

/* 订单滚动 */
.sw{overflow:hidden;flex:1}
.sl{animation:slideUp 30s linear infinite}
.sl:hover{animation-play-state:paused}
@keyframes slideUp{0%{transform:translateY(0)}100%{transform:translateY(-50%)}}
.od{display:flex;align-items:center;gap:10px;padding:7px 0;border-bottom:1px solid #27272a}
.od:last-child{border-bottom:none}
.od-icon{width:28px;height:28px;border-radius:8px;background:#27272a;display:flex;align-items:center;justify-content:center;font-size:12px;flex-shrink:0}
.od-info{flex:1;min-width:0}
.od-title{font-size:12px;color:#d4d4d8;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.od-sub{display:flex;gap:8px;margin-top:3px;font-size:10px;color:#52525b;align-items:center}
.tag{font-size:10px;font-weight:600;padding:2px 8px;border-radius:5px}

/* 今日4格 */
.tg{display:grid;grid-template-columns:1fr 1fr;gap:10px;flex:1}
.tgi{text-align:center;padding:14px 10px;background:#09090b;border-radius:10px;border:1px solid #27272a;transition:.3s}
.tgi:hover{border-color:#3f3f46;background:#18181b}
.tgn{font-size:26px;font-weight:800;color:#fafafa;line-height:1;margin-bottom:4px;letter-spacing:-1px}
.tgl{font-size:10px;color:#52525b;font-weight:500}
</style>
<script src="https://cdn.jsdelivr.net/npm/echarts@5.5.0/dist/echarts.min.js"></script>
</head>
<body>
<div class="app">

<div class="top">
  <div class="top-l"><span class="logo">数据中心<span>DATA CENTER</span></span></div>
  <div class="top-r"><span class="clk" id="clk"></span><a href="dashboard.php" class="btn-bk">← 返回后台</a></div>
</div>

<div class="kpis">
  <div class="k"><div class="k-head"><span class="k-label">总用户</span><span class="k-badge <?php echo cls($tU,$yU)?>"><?php echo pct($tU,$yU)?></span></div><div class="k-val"><?php echo number_format($totalUsers)?></div><div class="k-sub">今日新增 <?php echo $tU?></div></div>
  <div class="k"><div class="k-head"><span class="k-label">总资源</span><span class="k-badge <?php echo cls($tR,$yR)?>"><?php echo pct($tR,$yR)?></span></div><div class="k-val"><?php echo number_format($totalResources)?></div><div class="k-sub">今日新增 <?php echo $tR?></div></div>
  <div class="k"><div class="k-head"><span class="k-label">总下载</span><span class="k-badge <?php echo cls($tD,$yD)?>"><?php echo pct($tD,$yD)?></span></div><div class="k-val"><?php echo number_format($totalDownloads)?></div><div class="k-sub">今日 <?php echo number_format($tD)?></div></div>
  <div class="k"><div class="k-head"><span class="k-label">总营收</span><span class="k-badge <?php echo cls($tV,$yV)?>"><?php echo pct($tV,$yV)?></span></div><div class="k-val">¥<?php echo number_format($totalRevenue,0)?></div><div class="k-sub">今日 ¥<?php echo number_format($tV,2)?></div></div>
  <div class="k"><div class="k-head"><span class="k-label">积分总量</span><span class="k-badge eq"><?php echo $totalOrders?>单</span></div><div class="k-val"><?php echo number_format($totalPoints)?></div><div class="k-sub"><?php echo $vipCount?> 位VIP用户</div></div>
  <div class="k"><div class="k-head"><span class="k-label">今日活跃</span><span class="k-badge up">+<?php echo $tU?></span></div><div class="k-val"><?php echo $tU+$tR?></div><div class="k-sub"><?php echo $tU?>用户 · <?php echo $tR?>资源</div></div>
  <div class="k"><div class="k-head"><span class="k-label">待审核</span><span class="k-badge <?php echo $pendingRes>0?'dn':'eq'?>"><?php echo $pendingRes?></span></div><div class="k-val"><?php echo $vipCount?></div><div class="k-sub">VIP用户 · <?php echo $rsl['approved']?> 已发布</div></div>
</div>

<div class="main">

  <div class="col">
    <div class="card" style="flex:1.2"><div class="card-h">订单趋势<span class="badge">30D</span></div><div class="card-b"><div class="ch" id="c1"></div></div></div>
    <div class="card" style="flex:1"><div class="card-h">资源分类<span class="badge">TOP6</span></div><div class="card-b"><div class="ch" id="c2"></div></div></div>
    <div class="card" style="flex:.7"><div class="card-h">资源状态<span class="badge">ALL</span></div><div class="card-b"><div class="ch" id="c3"></div></div></div>
  </div>

  <div class="col">
    <div class="card" style="flex:1.4"><div class="card-h">用户增长<span class="badge">6M</span></div><div class="card-b"><div class="ch" id="c4"></div></div></div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;flex:1">
      <div class="card"><div class="card-h">7日下载<span class="badge">7D</span></div><div class="card-b"><div class="ch" id="c5"></div></div></div>
      <div class="card"><div class="card-h">今日概览<span class="badge">TODAY</span></div><div class="card-b"><div class="tg">
        <div class="tgi"><div class="tgn" style="color:#a78bfa"><?php echo $tU?></div><div class="tgl">新用户</div></div>
        <div class="tgi"><div class="tgn" style="color:#4ade80"><?php echo $tR?></div><div class="tgl">新资源</div></div>
        <div class="tgi"><div class="tgn" style="color:#facc15"><?php echo $tD?></div><div class="tgl">下载量</div></div>
        <div class="tgi"><div class="tgn" style="color:#f472b6">¥<?php echo number_format($tV,0)?></div><div class="tgl">收入</div></div>
      </div></div></div>
    </div>
  </div>

  <div class="col">
    <div class="card" style="flex:1"><div class="card-h">热门资源<span class="badge">TOP 10</span></div><div class="card-b" style="overflow-y:auto">
      <?php foreach($topRes as $i=>$r):?>
      <div class="it"><span class="it-rank <?php echo $i<3?'top':''?>"><?php echo $i<3?['🥇','🥈','🥉'][$i]:($i+1)?></span><div class="it-info"><div class="it-name"><?php echo htmlspecialchars($r['title'])?></div><div class="it-meta">↓<?php echo number_format($r['download_count'])?>　👁<?php echo number_format($r['view_count'])?></div></div></div>
      <?php endforeach;?>
    </div></div>
    <div class="card" style="flex:.7"><div class="card-h">积分排行<span class="badge">TOP</span></div><div class="card-b">
      <?php if(empty($ptTop)):?><div style="text-align:center;padding:24px 0;color:#3f3f46;font-size:12px">暂无积分数据</div>
      <?php else: foreach($ptTop as $i=>$r):?>
      <div class="it"><span class="it-rank <?php echo $i<3?'top':''?>"><?php echo $i<3?['🥇','🥈','🥉'][$i]:($i+1)?></span><div class="it-info"><div class="it-name"><?php echo htmlspecialchars($r['nickname']?:'匿名')?></div><div class="it-meta" style="color:#4ade80;font-weight:600"><?php echo number_format($r['points'])?> 积分</div></div></div>
      <?php endforeach; endif;?>
    </div></div>
    <div class="card" style="flex:1"><div class="card-h">最新订单<span class="badge">LIVE</span></div><div class="card-b">
      <?php if(empty($rO)):?><div style="text-align:center;padding:24px 0;color:#3f3f46;font-size:12px">暂无订单</div>
      <?php else:?>
      <div class="sw"><div class="sl"><?php for($z=0;$z<2;$z++):foreach($rO as $o):$s=$osm[$o['status']]??['未知','#6b7280'];?>
      <div class="od"><div class="od-icon">💰</div><div class="od-info"><div class="od-title"><?php echo htmlspecialchars($o['nickname']??'用户')?> · <?php echo substr($o['order_no']??'',-8)?></div><div class="od-sub"><span><?php echo date('m-d H:i',strtotime($o['created_at']))?></span><span style="color:#4ade80;font-weight:700">¥<?php echo number_format($o['pay_amount'],2)?></span><span class="tag" style="color:<?php echo $s[1]?>;background:<?php echo $s[1]?>15"><?php echo $s[0]?></span></div></div></div>
      <?php endforeach;endfor;?>
      </div></div>
      <?php endif;?>
    </div></div>
  </div>

</div>
</div>

<script>
!function(){var z=n=>String(n).padStart(2,'0');function t(){var d=new Date();document.getElementById('clk').textContent=d.getFullYear()+'-'+z(d.getMonth()+1)+'-'+z(d.getDate())+'  '+z(d.getHours())+':'+z(d.getMinutes())+':'+z(d.getSeconds())}t();setInterval(t,1000)}();

var bg='transparent';
var tp={backgroundColor:'#18181b',borderColor:'#27272a',borderRadius:10,padding:[10,14],textStyle:{color:'#a1a1aa',fontSize:12},extraCssText:'box-shadow:0 8px 30px rgba(0,0,0,.4)'};
var gd={top:18,right:16,bottom:22,left:38};
var xa={axisLine:{lineStyle:{color:'#27272a'}},axisTick:{show:false},axisLabel:{color:'#52525b',fontSize:10}};
var ya={splitLine:{lineStyle:{color:'#1f1f23'}},axisLabel:{color:'#52525b',fontSize:10}};
var cols=['#a78bfa','#4ade80','#facc15','#f472b6','#22d3ee','#fb923c'];

// 1 订单趋势
echarts.init(document.getElementById('c1'),{backgroundColor:bg}).setOption({
  tooltip:{...tp,trigger:'axis'},grid:{...gd,right:34},
  legend:{bottom:0,textStyle:{color:'#52525b',fontSize:11},itemWidth:12,itemHeight:4},
  xAxis:{type:'category',data:<?php echo json_encode($td)?>,...xa,axisLabel:{...xa.axisLabel,interval:4}},
  yAxis:[{type:'value',...ya},{type:'value',splitLine:{show:false},axisLabel:{color:'#52525b',fontSize:10,formatter:'¥{value}'}}],
  series:[
    {name:'订单',type:'line',smooth:.4,symbol:'none',lineStyle:{width:2,color:cols[0]},areaStyle:{color:{type:'linear',x:0,y:0,x2:0,y2:1,colorStops:[{offset:0,color:'rgba(167,139,250,.12)'},{offset:1,color:'transparent'}]}},data:<?php echo json_encode($tc)?>},
    {name:'收入',type:'line',smooth:.4,symbol:'none',lineStyle:{width:2,color:cols[1]},yAxisIndex:1,areaStyle:{color:{type:'linear',x:0,y:0,x2:0,y2:1,colorStops:[{offset:0,color:'rgba(74,222,128,.08)'},{offset:1,color:'transparent'}]}},data:<?php echo json_encode($tr)?>}
  ]});

// 2 分类
echarts.init(document.getElementById('c2'),{backgroundColor:bg}).setOption({
  tooltip:{...tp,trigger:'item',formatter:'{b}\n{c}个 · {d}%'},
  legend:{bottom:0,textStyle:{color:'#52525b',fontSize:10},itemWidth:8,itemHeight:4},
  series:[{type:'pie',radius:['32%','64%'],roseType:'area',center:['50%','44%'],
    itemStyle:{borderRadius:5,borderColor:'#18181b',borderWidth:3},
    label:{color:'#71717a',fontSize:10,formatter:'{b}\n{d}%'},labelLine:{lineStyle:{color:'#27272a'}},
    data:[<?php foreach($cS as $i=>$s)echo '{value:'.$s['cnt'].",name:'".addslashes($s['name'])."',itemStyle:{color:'".($cols[$i%6])."'},";?>]}]});

// 3 状态
echarts.init(document.getElementById('c3'),{backgroundColor:bg}).setOption({
  tooltip:{...tp,trigger:'item'},legend:{show:false},
  series:[{type:'pie',radius:['46%','70%'],center:['50%','48%'],
    itemStyle:{borderRadius:5,borderColor:'#18181b',borderWidth:3},
    label:{show:true,color:'#71717a',fontSize:9,formatter:'{b}\n{c}'},
    data:[
      {value:<?php echo $rsl['approved']?>,name:'已发布',itemStyle:{color:'#4ade80'}},
      {value:<?php echo $rsl['pending']?>,name:'待审',itemStyle:{color:'#facc15'}},
      {value:<?php echo $rsl['rejected']?>,name:'拒绝',itemStyle:{color:'#f87171'}},
      {value:<?php echo $rsl['offline']?>,name:'下线',itemStyle:{color:'#52525b'}}
    ]}]});

// 4 用户增长
echarts.init(document.getElementById('c4'),{backgroundColor:bg}).setOption({
  tooltip:{...tp,trigger:'axis'},grid:{...gd},
  xAxis:{type:'category',data:<?php echo json_encode($mL)?>,...xa},
  yAxis:{type:'value',...ya},
  series:[{type:'bar',data:<?php echo json_encode($mV)?>,barWidth:20,
    itemStyle:{borderRadius:[6,6,0,0],color:{type:'linear',x:0,y:0,x2:0,y2:1,colorStops:[{offset:0,color:'rgba(167,139,250,.6)'},{offset:1,color:'rgba(167,139,250,.06)'}]}},
    emphasis:{itemStyle:{color:{type:'linear',x:0,y:0,x2:0,y2:1,colorStops:[{offset:0,color:'rgba(167,139,250,.8)'},{offset:1,color:'rgba(167,139,250,.2)'}]}}}}]});

// 5 7日下载
echarts.init(document.getElementById('c5'),{backgroundColor:bg}).setOption({
  tooltip:{...tp,trigger:'axis'},grid:{top:14,right:12,bottom:20,left:28},
  xAxis:{type:'category',data:<?php echo json_encode($dd7)?>,...xa},
  yAxis:{type:'value',...ya},
  series:[{type:'line',smooth:.4,symbol:'circle',symbolSize:6,lineStyle:{width:2,color:cols[4]},itemStyle:{color:cols[4],borderWidth:2,borderColor:'#18181b'},
    areaStyle:{color:{type:'linear',x:0,y:0,x2:0,y2:1,colorStops:[{offset:0,color:'rgba(34,211,238,.12)'},{offset:1,color:'transparent'}]}},
    data:<?php echo json_encode($dc7)?>}]});

window.addEventListener('resize',function(){for(var i=1;i<=5;i++){var el=document.getElementById('c'+i);if(el&&echarts.getInstanceByDom(el))echarts.getInstanceByDom(el).resize();}});
setTimeout(function(){location.reload()},30000);
</script>
</body>
</html>
