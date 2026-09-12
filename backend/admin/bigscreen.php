<?php
session_start();
if (empty($_SESSION['admin_id'])) { header('Location: login.php'); exit; }
require_once __DIR__ . '/../core/Database.php';
$db = Database::getInstance();

$totalUsers=$db->count('users');$totalResources=$db->count('resources');
$totalDownloads=(int)$db->fetch('SELECT COALESCE(SUM(download_count),0) AS v FROM resources')['v'];
$totalRevenue=(float)$db->fetch("SELECT COALESCE(SUM(pay_amount),0) AS v FROM orders WHERE status='paid'")['v'];
$today=date('Y-m-d');$yesterday=date('Y-m-d',strtotime('-1 day'));
$tU=$db->count('users','DATE(created_at)=:d',[':d'=>$today]);$yU=$db->count('users','DATE(created_at)=:d',[':d'=>$yesterday]);
$tR=$db->count('resources','DATE(created_at)=:d',[':d'=>$today]);$yR=$db->count('resources','DATE(created_at)=:d',[':d'=>$yesterday]);
$tD=(int)$db->fetch('SELECT COALESCE(SUM(download_count),0) AS v FROM resources WHERE DATE(updated_at)=:d',[':d'=>$today])['v'];
$yD=(int)$db->fetch('SELECT COALESCE(SUM(download_count),0) AS v FROM resources WHERE DATE(updated_at)=:d',[':d'=>$yesterday])['v'];
$tV=(float)$db->fetch("SELECT COALESCE(SUM(pay_amount),0) AS v FROM orders WHERE status='paid' AND DATE(created_at)=:d",[':d'=>$today])['v'];
$yV=(float)$db->fetch("SELECT COALESCE(SUM(pay_amount),0) AS v FROM orders WHERE status='paid' AND DATE(created_at)=:d",[':d'=>$yesterday])['v'];
function pct($c,$p){if($p==0)return $c>0?'100':'0';return number_format(abs((($c-$p)/$p)*100),1);}
function cls($c,$p){return $c>$p?'up':($c<$p?'down':'flat');}

$oT=$db->fetchAll("SELECT DATE(created_at) AS day,COUNT(*) AS cnt,COALESCE(SUM(pay_amount),0) AS rev FROM orders WHERE status='paid' AND created_at>=DATE_SUB(CURDATE(),INTERVAL 30 DAY) GROUP BY DATE(created_at) ORDER BY day");
$td=[];$tc=[];$tr=[];
for($i=29;$i>=0;$i--){$d=date('Y-m-d',strtotime("-{$i} days"));$td[]=date('m/d',strtotime($d));$f=false;foreach($oT as $r){if($r['day']===$d){$tc[]=(int)$r['cnt'];$tr[]=(float)$r['rev'];$f=true;break;}}if(!$f){$tc[]=0;$tr[]=0;}}
$nU=$db->fetchAll("SELECT DATE(created_at) AS day,COUNT(*) AS cnt FROM users WHERE created_at>=DATE_SUB(CURDATE(),INTERVAL 7 DAY) GROUP BY DATE(created_at) ORDER BY day");
$nd=[];$nc=[];
for($i=6;$i>=0;$i--){$d=date('Y-m-d',strtotime("-{$i} days"));$nd[]=date('m/d',strtotime($d));$f=false;foreach($nU as $r){if($r['day']===$d){$nc[]=(int)$r['cnt'];$f=true;break;}}if(!$f){$nc[]=0;}}
$cS=$db->fetchAll("SELECT c.name,COUNT(r.id) AS cnt FROM categories c LEFT JOIN resources r ON c.id=r.category_id AND r.status='approved' WHERE c.parent_id=0 GROUP BY c.id,c.name ORDER BY cnt DESC LIMIT 8");
$rO=$db->fetchAll("SELECT o.*,u.nickname FROM orders o LEFT JOIN users u ON o.user_id=u.id ORDER BY o.created_at DESC LIMIT 15");
$osm=['pending'=>'待支付','paid'=>'已支付','refunded'=>'已退款','cancelled'=>'已取消'];
$topRes=$db->fetchAll("SELECT r.title,r.download_count,r.view_count,c.name as cat FROM resources r LEFT JOIN categories c ON r.category_id=c.id WHERE r.status='approved' ORDER BY r.download_count DESC LIMIT 10");
$rsS=$db->fetchAll("SELECT status,COUNT(*) AS cnt FROM resources GROUP BY status");
$rsl=['approved'=>0,'pending'=>0,'rejected'=>0,'offline'=>0];foreach($rsS as $r)$rsl[$r['status']]=(int)$r['cnt'];
$mData=$db->fetchAll("SELECT DATE_FORMAT(created_at,'%m') AS m,COUNT(*) AS cnt FROM users WHERE created_at>=DATE_SUB(CURDATE(),INTERVAL 6 MONTH) GROUP BY m ORDER BY m");
$mL=[];$mV=[];for($i=6;$i>=1;$i--){$m=date('m',strtotime("-{$i} months"));$mL[]=$m.'月';$f=false;foreach($mData as $r){if($r['m']==$m){$mV[]=(int)$r['cnt'];$f=true;break;}}if(!$f)$mV[]=0;}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<title>数据驾驶舱</title>
<script src="https://cdn.jsdelivr.net/npm/echarts@5.5.0/dist/echarts.min.js"></script>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{background:#0c111b;color:#8b9bb4;font-family:-apple-system,BlinkMacSystemFont,'SF Pro Display','PingFang SC','Microsoft YaHei',sans-serif;height:100vh;overflow:hidden;-webkit-font-smoothing:antialiased;-moz-osx-font-smoothing:grayscale}

.wrap{position:relative;z-index:1;height:100vh;display:flex;flex-direction:column;padding:0 32px 16px}

/* ===== 标题栏 ===== */
.hdr{height:64px;display:flex;align-items:center;justify-content:space-between;flex-shrink:0;
  padding:0 4px;border-bottom:1px solid rgba(255,255,255,.04);margin-bottom:20px}
.hdr-l{display:flex;align-items:baseline;gap:16px}
.hdr-t{font-size:18px;font-weight:700;color:#e2e8f0;letter-spacing:2px}
.hdr-s{font-size:11px;color:#4a5568;letter-spacing:1px;font-weight:400}
.hdr-r{display:flex;align-items:center;gap:20px}
.clk{font-size:12px;color:#4a5568;font-variant-numeric:tabular-nums}
.btn-bk{padding:6px 16px;border-radius:6px;font-size:12px;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.06);color:#6b7fa3;text-decoration:none;transition:.2s}
.btn-bk:hover{background:rgba(255,255,255,.08);color:#a0aec0}

/* ===== 网格 ===== */
.g{flex:1;display:flex;flex-direction:column;gap:16px;min-height:0}
.g-row{flex:1;display:grid;grid-template-columns:1fr 1.6fr 1fr;gap:16px;min-height:0}

/* ===== 面板 ===== */
.p{background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.05);border-radius:12px;
  display:flex;flex-direction:column;overflow:hidden;transition:all .3s}
.p:hover{border-color:rgba(255,255,255,.08);background:rgba(255,255,255,.04)}

.pt{height:40px;display:flex;align-items:center;gap:8px;padding:0 18px;
  font-size:13px;font-weight:600;color:#8b9bb4;letter-spacing:.5px;
  border-bottom:1px solid rgba(255,255,255,.03);flex-shrink:0}
.pt em{font-style:normal;color:#3a4a5e;font-size:10px;margin-left:auto;letter-spacing:1.5px;font-weight:500}
.pb{flex:1;padding:16px;overflow:hidden;display:flex;flex-direction:column}
.ec{flex:1;min-height:0}

/* ===== KPI ===== */
.kpi-bar{display:grid;grid-template-columns:repeat(6,1fr);gap:12px;flex-shrink:0}
.kb{background:rgba(255,255,255,.025);border:1px solid rgba(255,255,255,.04);border-radius:10px;
  padding:18px 14px;transition:all .3s}
.kb:hover{background:rgba(255,255,255,.04);border-color:rgba(255,255,255,.07)}
.kb-v{font-size:26px;font-weight:800;line-height:1;margin-bottom:6px;letter-spacing:-.5px}
.kb-v.v1{color:#818cf8}.kb-v.v2{color:#34d399}.kb-v.v3{color:#fbbf24}.kb-v.v4{color:#f472b6}.kb-v.v5{color:#7c4dff}.kb-v.v6{color:#38bdf8}
.kb-l{font-size:11px;color:#4a5568;margin-bottom:4px}
.kb-t{font-size:10px;font-weight:600}
.kb-t.up{color:#34d399}.kb-t.down{color:#f87171}.kb-t.flat{color:#4a5568}

/* ===== 列表 ===== */
.lst{overflow:hidden;flex:1}
.lt{animation:scrl 22s linear infinite}.lt:hover{animation-play-state:paused}
.li{display:flex;align-items:center;gap:10px;padding:9px 4px;border-bottom:1px solid rgba(255,255,255,.02);border-radius:6px;transition:background .2s}
.li:hover{background:rgba(255,255,255,.02)}
.li:last-child{border-bottom:none}
.li-i{width:28px;height:28px;border-radius:7px;background:rgba(255,255,255,.04);display:flex;align-items:center;justify-content:center;font-size:12px;flex-shrink:0}
.li-b{flex:1;min-width:0}
.li-t{font-size:12px;color:#8b9bb4;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.li-m{display:flex;align-items:center;gap:8px;margin-top:3px}
.li-tm{font-size:10px;color:#4a5568}
.li-am{font-size:11px;font-weight:700;color:#34d399}
.li-tg{font-size:10px;font-weight:600;padding:2px 8px;border-radius:4px}

/* ===== 排行 ===== */
.rk{display:flex;align-items:center;gap:10px;padding:9px 4px;border-bottom:1px solid rgba(255,255,255,.02);border-radius:6px;transition:background .2s}
.rk:hover{background:rgba(255,255,255,.02)}
.rk:last-child{border-bottom:none}
.rk-n{width:22px;height:22px;border-radius:6px;font-size:11px;font-weight:700;
  display:flex;align-items:center;justify-content:center;flex-shrink:0;
  background:rgba(255,255,255,.04);color:#4a5568}
.rk-n.t{background:rgba(129,140,248,.15);color:#818cf8}
.rk-b{flex:1;min-width:0}
.rk-t{font-size:12px;color:#8b9bb4;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.rk-m{display:flex;gap:8px;margin-top:2px;font-size:10px;color:#4a5568}

/* 今日概览格 */
.tg{display:grid;grid-template-columns:1fr 1fr;gap:10px;flex:1}
.tg-i{text-align:center;padding:14px 8px;background:rgba(255,255,255,.02);border-radius:8px;border:1px solid rgba(255,255,255,.03)}
.tg-n{font-size:22px;font-weight:800;line-height:1;margin-bottom:4px}
.tg-l{font-size:10px;color:#4a5568}

@keyframes scrl{0%{transform:translateY(0)}100%{transform:translateY(-50%)}}
</style>
</head>
<body>
<div class="wrap">
  <div class="hdr">
    <div class="hdr-l"><span class="hdr-t">数据中心</span><span class="hdr-s">DATA CENTER</span></div>
    <div class="hdr-r"><span class="clk" id="clk"></span><a href="dashboard.php" class="btn-bk">← 返回后台</a></div>
  </div>
  <div class="g">
    <div class="kpi-bar">
      <div class="kb"><div class="kb-v v1"><?php echo number_format($totalUsers);?></div><div class="kb-l">总用户</div><div class="kb-t <?php echo cls($tU,$yU);?>"><?php echo $tU>=$yU?'↑':'↓';?><?php echo pct($tU,$yU);?>%</div></div>
      <div class="kb"><div class="kb-v v2"><?php echo number_format($totalResources);?></div><div class="kb-l">总资源</div><div class="kb-t <?php echo cls($tR,$yR);?>"><?php echo $tR>=$yR?'↑':'↓';?><?php echo pct($tR,$yR);?>%</div></div>
      <div class="kb"><div class="kb-v v3"><?php echo number_format($totalDownloads);?></div><div class="kb-l">总下载</div><div class="kb-t <?php echo cls($tD,$yD);?>"><?php echo $tD>=$yD?'↑':'↓';?><?php echo pct($tD,$yD);?>%</div></div>
      <div class="kb"><div class="kb-v v4">¥<?php echo number_format($totalRevenue,0);?></div><div class="kb-l">总收入</div><div class="kb-t <?php echo cls($tV,$yV);?>"><?php echo $tV>=$yV?'↑':'↓';?><?php echo pct($tV,$yV);?>%</div></div>
      <div class="kb"><div class="kb-v v5"><?php echo $tU+$tR;?></div><div class="kb-l">今日活跃</div><div class="kb-t up">+<?php echo pct($tU+$tR,max(1,$yU+$yR));?>%</div></div>
      <div class="kb"><div class="kb-v v6"><?php echo $rsl['approved'];?></div><div class="kb-l">已发布</div><div class="kb-t flat"><?php echo $rsl['pending'];?> 待审</div></div>
    </div>
    <div class="g-row">
      <div style="display:flex;flex-direction:column;gap:16px">
        <div class="p" style="flex:1.3"><div class="pt">订单趋势 <em>30D</em></div><div class="pb"><div class="ec" id="ecLine"></div></div></div>
        <div class="p" style="flex:1"><div class="pt">资源分类 <em>SHARE</em></div><div class="pb"><div class="ec" id="ecPie"></div></div></div>
        <div class="p" style="flex:.8"><div class="pt">资源状态 <em>STATUS</em></div><div class="pb"><div class="ec" id="ecDonut"></div></div></div>
      </div>
      <div style="display:flex;flex-direction:column;gap:16px">
        <div class="p" style="flex:2"><div class="pt">用户增长 <em>6M</em></div><div class="pb"><div class="ec" id="ecBar"></div></div></div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;flex:1">
          <div class="p"><div class="pt">7日趋势 <em>7D</em></div><div class="pb"><div class="ec" id="ecArea"></div></div></div>
          <div class="p"><div class="pt">今日概览 <em>TODAY</em></div><div class="pb"><div class="tg">
            <div class="tg-i"><div class="tg-n" style="color:#818cf8"><?php echo $tU;?></div><div class="tg-l">新增用户</div></div>
            <div class="tg-i"><div class="tg-n" style="color:#34d399"><?php echo $tR;?></div><div class="tg-l">新增资源</div></div>
            <div class="tg-i"><div class="tg-n" style="color:#fbbf24"><?php echo $tD;?></div><div class="tg-l">下载量</div></div>
            <div class="tg-i"><div class="tg-n" style="color:#f472b6">¥<?php echo number_format($tV,2);?></div><div class="tg-l">收入</div></div>
          </div></div></div>
        </div>
      </div>
      <div style="display:flex;flex-direction:column;gap:16px">
        <div class="p" style="flex:1"><div class="pt">热门资源 <em>TOP10</em></div><div class="pb">
          <?php foreach($topRes as $i=>$r):?><div class="rk"><span class="rk-n <?php echo $i<3?'t':'';?>"><?php echo $i+1;?></span><div class="rk-b"><div class="rk-t"><?php echo htmlspecialchars($r['title']);?></div><div class="rk-m"><span><?php echo htmlspecialchars($r['cat']??'');?></span><span>↓<?php echo number_format($r['download_count']);?></span></div></div></div><?php endforeach;?>
        </div></div>
        <div class="p" style="flex:1"><div class="pt">实时订单 <em>LIVE</em></div><div class="pb">
          <div class="lst"><div class="lt"><?php for($d=0;$d<2;$d++):foreach($rO as $o):?><div class="li"><div class="li-i">💰</div><div class="li-b"><div class="li-t"><?php echo htmlspecialchars($o['nickname']??'匿名');?> · <?php echo htmlspecialchars($o['order_no']);?></div><div class="li-m"><span class="li-tm"><?php echo date('m-d H:i',strtotime($o['created_at']));?></span><span class="li-am">¥<?php echo number_format($o['pay_amount'],2);?></span><span class="li-tg" style="color:<?php echo['#f59e0b','#34d399','#60a5fa','#64748b'][array_search($o['status'],['pending','paid','refunded','cancelled'])]??'#4a5568';?>;background:rgba(255,255,255,.04)"><?php echo $osm[$o['status']]??$o['status'];?></span></div></div></div><?php endforeach;endfor;?></div></div>
        </div></div>
      </div>
    </div>
  </div>
</div>
<script>
!function(){function t(){var n=new Date();document.getElementById('clk').textContent=n.getFullYear()+'-'+String(n.getMonth()+1).padStart(2,'0')+'-'+String(n.getDate()).padStart(2,'0')+' '+String(n.getHours()).padStart(2,'0')+':'+String(n.getMinutes()).padStart(2,'0')+':'+String(n.getSeconds()).padStart(2,'0')}t();setInterval(t,1000)}();

var th={backgroundColor:'transparent',textStyle:{color:'#4a5568',fontSize:11}};
var tip={backgroundColor:'rgba(12,17,27,.95)',borderColor:'rgba(255,255,255,.06)',borderRadius:8,padding:[10,14],textStyle:{color:'#8b9bb4',fontSize:12}};
var g={top:20,right:16,bottom:30,left:44};
var xA={axisLine:{lineStyle:{color:'rgba(255,255,255,.04)'}},axisTick:{show:false},axisLabel:{color:'#4a5568',fontSize:10}};
var yA={splitLine:{lineStyle:{color:'rgba(255,255,255,.03)'}},axisLabel:{color:'#4a5568',fontSize:10}};

echarts.init(document.getElementById('ecLine'),th).setOption({
  tooltip:{...tip,trigger:'axis'},grid:{...g,right:40},
  legend:{bottom:0,textStyle:{color:'#4a5568',fontSize:11},itemWidth:12,itemHeight:6},
  xAxis:{type:'category',data:<?php echo json_encode($td);?>,...xA},
  yAxis:[{type:'value',...yA},{type:'value',splitLine:{show:false},axisLabel:{color:'#4a5568',fontSize:10,formatter:'¥{value}'}}],
  series:[
    {name:'订单',type:'line',smooth:true,symbol:'none',lineStyle:{width:2,color:'#818cf8'},areaStyle:{color:{type:'linear',x:0,y:0,x2:0,y2:1,colorStops:[{offset:0,color:'rgba(129,140,248,.12)'},{offset:1,color:'transparent'}]}},data:<?php echo json_encode($tc);?>},
    {name:'收入',type:'line',smooth:true,symbol:'none',lineStyle:{width:2,color:'#34d399'},yAxisIndex:1,areaStyle:{color:{type:'linear',x:0,y:0,x2:0,y2:1,colorStops:[{offset:0,color:'rgba(52,211,153,.08)'},{offset:1,color:'transparent'}]}},data:<?php echo json_encode($tr);?>}
  ]});

echarts.init(document.getElementById('ecPie'),th).setOption({
  tooltip:{...tip,trigger:'item'},
  legend:{bottom:0,textStyle:{color:'#4a5568',fontSize:10},itemWidth:10,itemHeight:4},
  series:[{type:'pie',radius:['30%','68%'],roseType:'area',center:['50%','48%'],
    itemStyle:{borderRadius:5,borderColor:'rgba(12,17,23,.8)',borderWidth:2},
    label:{color:'#4a5568',fontSize:10},labelLine:{lineStyle:{color:'rgba(255,255,255,.06)'}},
    data:[<?php $pc=['#818cf8','#34d399','#fbbf24','#f472b6','#38bdf8','#fb923c','#7c4dff','#2dd4bf'];foreach($cS as $i=>$c)echo "{value:".$c['cnt'].",name:'".addslashes($c['name'])."',itemStyle:{color:'".$pc[$i%8]."'}},";?>]}]});

echarts.init(document.getElementById('ecDonut'),th).setOption({
  tooltip:{...tip,trigger:'item'},
  series:[{type:'pie',radius:['44%','70%'],center:['50%','48%'],
    itemStyle:{borderRadius:4,borderColor:'rgba(12,17,23,.8)',borderWidth:2},label:{show:false},
    data:[{value:<?php echo $rsl['approved'];?>,name:'已发布',itemStyle:{color:'#34d399'}},{value:<?php echo $rsl['pending'];?>,name:'待审核',itemStyle:{color:'#fbbf24'}},{value:<?php echo $rsl['rejected'];?>,name:'已拒绝',itemStyle:{color:'#f87171'}},{value:<?php echo $rsl['offline'];?>,name:'已下线',itemStyle:{color:'#64748b'}}]}]});

echarts.init(document.getElementById('ecBar'),th).setOption({
  tooltip:{...tip,trigger:'axis'},grid:{...g},
  xAxis:{type:'category',data:<?php echo json_encode($mL);?>,...xA},
  yAxis:{type:'value',...yA},
  series:[{type:'bar',data:<?php echo json_encode($mV);?>,barWidth:24,
    itemStyle:{borderRadius:[4,4,0,0],color:{type:'linear',x:0,y:0,x2:0,y2:1,colorStops:[{offset:0,color:'rgba(129,140,248,.7)'},{offset:1,color:'rgba(129,140,248,.15)'}]}}}]});

echarts.init(document.getElementById('ecArea'),th).setOption({
  tooltip:{...tip,trigger:'axis'},grid:{top:16,right:12,bottom:24,left:32},
  xAxis:{type:'category',data:<?php echo json_encode($nd);?>,...xA},
  yAxis:{type:'value',...yA},
  series:[{type:'line',smooth:true,symbol:'none',lineStyle:{width:2,color:'#7c4dff'},
    areaStyle:{color:{type:'linear',x:0,y:0,x2:0,y2:1,colorStops:[{offset:0,color:'rgba(124,77,255,.12)'},{offset:1,color:'transparent'}]}},
    data:<?php echo json_encode($nc);?>}]});

setTimeout(function(){location.reload()},2e4);
</script>
</body>
</html>
