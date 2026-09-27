<?php
require __DIR__.'/../config/auth.php'; require_login();
if(!in_array($_SESSION['role'],['kitchen','admin'],true)){ http_response_code(403); die('Kitchen access only.'); }
require __DIR__.'/../config/database.php';

if($_SERVER['REQUEST_METHOD']==='POST'){
  $id=(int)($_POST['order_id']??0); $action=$_POST['action']??'';
  $map=['start'=>'Preparing','ready'=>'Ready','served'=>'Served'];
  if(isset($map[$action])){
    $pdo->beginTransaction();
    try{
      $st=$pdo->prepare("SELECT order_status,table_id FROM orders WHERE order_id=? FOR UPDATE"); $st->execute([$id]); $o=$st->fetch();
      if(!$o) throw new Exception('Order not found.');
      $allowed=['Pending'=>['start'],'Preparing'=>['ready'],'Ready'=>['served']];
      if(!in_array($action,$allowed[$o['order_status']]??[],true)) throw new Exception('Order status changed. Refresh the page.');
      $pdo->prepare("UPDATE orders SET order_status=? WHERE order_id=?")->execute([$map[$action],$id]);
      if($action==='served' && $o['table_id']){
        // Table intentionally remains occupied until restaurant completes/clears the dining table later.
      }
      $pdo->prepare("INSERT INTO activity_logs(user_id,action) VALUES(?,?)")->execute([$_SESSION['user_id'],'Kitchen order #'.$id.' -> '.$map[$action]]);
      $pdo->commit();
    }catch(Throwable $e){ if($pdo->inTransaction())$pdo->rollBack(); $_SESSION['kitchen_error']=$e->getMessage(); }
  }
  header('Location: index.php'); exit;
}

$orders=$pdo->query("SELECT o.*,t.table_number,TIMESTAMPDIFF(MINUTE,o.order_date,NOW()) wait_minutes FROM orders o LEFT JOIN restaurant_tables t USING(table_id) WHERE o.payment_status='Paid' AND o.order_status IN('Pending','Preparing','Ready') ORDER BY FIELD(o.order_status,'Pending','Preparing','Ready'),o.order_date")->fetchAll();
$itemSt=$pdo->prepare("SELECT oi.quantity,oi.special_request,p.product_name,c.category_name FROM order_items oi JOIN products p USING(product_id) JOIN categories c USING(category_id) WHERE oi.order_id=? ORDER BY c.sort_order,p.product_name");
$columns=['Pending'=>'NEW ORDERS','Preparing'=>'PREPARING','Ready'=>'READY'];
$error=$_SESSION['kitchen_error']??''; unset($_SESSION['kitchen_error']);
?><!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta http-equiv="refresh" content="15"><title>Sea You Kitchen</title><link rel="stylesheet" href="../assets/css/kitchen.css"></head>
<body><header class="k-header"><div class="k-brand"><img src="../assets/sea-you-logo.jpg" alt="Sea You logo"><div><b>SEA YOU</b><span>Kitchen Display</span></div></div><div><?=htmlspecialchars($_SESSION['full_name'])?> · Auto refresh 15s · <a href="../logout.php">Logout</a></div></header>
<?php if($error):?><div class="error"><?=htmlspecialchars($error)?></div><?php endif;?>
<main class="board"><?php foreach($columns as $status=>$label):?><section class="lane"><div class="lane-title"><h2><?=$label?></h2><span><?=count(array_filter($orders,fn($x)=>$x['order_status']===$status))?></span></div><div class="cards">
<?php foreach($orders as $o): if($o['order_status']!==$status)continue; $itemSt->execute([$o['order_id']]);$items=$itemSt->fetchAll();$food=array_filter($items,fn($x)=>$x['category_name']!=='Drinks');$drinks=array_filter($items,fn($x)=>$x['category_name']==='Drinks');?>
<article class="order-card status-<?=strtolower($status)?>">
 <div class="order-head"><div><b><?=htmlspecialchars($o['order_number'])?></b><small><?=htmlspecialchars(date('g:i A',strtotime($o['order_date'])))?></small></div><strong><?=$o['order_type']==='Dine-in'?'TABLE '.htmlspecialchars($o['table_number']):'TAKEOUT'?></strong></div>
 <div class="wait">Waiting <?=$o['wait_minutes']?> min</div>
 <?php if($food):?><h3>🍽 DISHES</h3><?php foreach($food as $i):?><div class="item"><b><?=$i['quantity']?>×</b><span><?=htmlspecialchars($i['product_name'])?></span></div><?php endforeach;?><?php endif;?>
 <?php if($drinks):?><h3>🥤 BEVERAGES</h3><?php foreach($drinks as $i):?><div class="item beverage"><b><?=$i['quantity']?>×</b><span><?=htmlspecialchars($i['product_name'])?></span></div><?php endforeach;?><?php endif;?>
 <form method="post"><input type="hidden" name="order_id" value="<?=$o['order_id']?>"><?php if($status==='Pending'):?><button name="action" value="start">START PREPARING</button><?php elseif($status==='Preparing'):?><button name="action" value="ready">MARK READY</button><?php else:?><button name="action" value="served">MARK SERVED</button><?php endif;?></form>
</article><?php endforeach;?>
<?php if(!array_filter($orders,fn($x)=>$x['order_status']===$status)):?><div class="empty">No <?=$label?>.</div><?php endif;?></div></section><?php endforeach;?></main>
</body></html>