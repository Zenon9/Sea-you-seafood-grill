<?php
require __DIR__.'/config/auth.php'; require_role(['cashier','admin']);
require __DIR__.'/config/database.php';
$message=''; $error='';

if($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='clear'){
  $tableId=(int)($_POST['table_id']??0);
  try{
    $pdo->beginTransaction();
    $st=$pdo->prepare("SELECT table_number,status FROM restaurant_tables WHERE table_id=? FOR UPDATE"); $st->execute([$tableId]); $table=$st->fetch();
    if(!$table) throw new Exception('Table not found.');
    if($table['status']!=='occupied') throw new Exception('Table '.$table['table_number'].' is already available.');

    $st=$pdo->prepare("SELECT order_id,order_number,order_status FROM orders WHERE table_id=? AND order_status NOT IN('Completed','Cancelled') ORDER BY order_date DESC LIMIT 1 FOR UPDATE");
    $st->execute([$tableId]); $order=$st->fetch();
    if($order && !in_array($order['order_status'],['Served'],true))
      throw new Exception('Cannot clear '.$table['table_number'].' yet. Current order is '.$order['order_status'].'. Kitchen must mark it Served first.');

    if($order){
      $pdo->prepare("UPDATE orders SET order_status='Completed' WHERE order_id=?")->execute([$order['order_id']]);
    }
    $pdo->prepare("UPDATE restaurant_tables SET status='available' WHERE table_id=?")->execute([$tableId]);
    $pdo->prepare("INSERT INTO activity_logs(user_id,action) VALUES(?,?)")->execute([$_SESSION['user_id'],'Cleared table '.$table['table_number'].($order?' / '.$order['order_number']:'')]);
    $pdo->commit(); $message='Table '.$table['table_number'].' is now available.';
  }catch(Throwable $e){ if($pdo->inTransaction())$pdo->rollBack(); $error=$e->getMessage(); }
}

$sql="SELECT t.*,o.order_id,o.order_number,o.order_status,o.order_type,o.total_amount,o.order_date,
TIMESTAMPDIFF(MINUTE,o.order_date,NOW()) AS minutes_open
FROM restaurant_tables t
LEFT JOIN orders o ON o.order_id=(
 SELECT x.order_id FROM orders x WHERE x.table_id=t.table_id AND x.order_status NOT IN('Completed','Cancelled') ORDER BY x.order_date DESC LIMIT 1
)
ORDER BY CAST(REPLACE(LOWER(t.table_number),'table','') AS UNSIGNED),t.table_number";
$tables=$pdo->query($sql)->fetchAll();
?><!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Table Checker</title><link rel="stylesheet" href="assets/css/tables.css"></head>
<body><header><div class="table-brand"><img src="assets/sea-you-logo.jpg" alt="Sea You logo"><div><b>SEA YOU</b><span>Table Checker</span></div></div><nav><a href="index.php">← Cashier POS</a><a href="logout.php">Logout</a></nav></header>
<main><div class="title"><div><h1>Restaurant Tables</h1><p>Check dine-in tables and release them after the kitchen marks the order served.</p></div><div class="legend"><span class="dot available"></span>Available <span class="dot occupied"></span>Occupied</div></div>
<?php if($message):?><div class="notice ok"><?=htmlspecialchars($message)?></div><?php endif;?><?php if($error):?><div class="notice bad"><?=htmlspecialchars($error)?></div><?php endif;?>
<div class="grid"><?php foreach($tables as $t): $occupied=$t['status']==='occupied';?>
<article class="table-card <?=$occupied?'is-occupied':'is-available'?>">
 <div class="table-top"><div class="table-icon">🍽</div><div><h2><?=htmlspecialchars($t['table_number'])?></h2><small>Capacity <?=$t['capacity']?> guests</small></div><span class="badge"><?=$occupied?'OCCUPIED':'AVAILABLE'?></span></div>
 <?php if($occupied && $t['order_id']):?>
 <div class="order-info"><div><span>Order</span><b><?=htmlspecialchars($t['order_number'])?></b></div><div><span>Status</span><b class="status"><?=htmlspecialchars($t['order_status'])?></b></div><div><span>Total</span><b>₱<?=number_format($t['total_amount'],2)?></b></div><div><span>Open</span><b><?=$t['minutes_open']?> min</b></div></div>
 <?php if($t['order_status']==='Served'):?><form method="post" onsubmit="return confirm('Clear <?=htmlspecialchars($t['table_number'],ENT_QUOTES)?> and complete this order?')"><input type="hidden" name="action" value="clear"><input type="hidden" name="table_id" value="<?=$t['table_id']?>"><button>✓ CLEAR TABLE</button></form>
 <?php else:?><button class="disabled" disabled>WAITING FOR <?=$t['order_status']==='Ready'?'SERVICE':strtoupper($t['order_status'])?></button><?php endif;?>
 <?php elseif($occupied):?><div class="warning">Occupied table has no active order.</div><form method="post" onsubmit="return confirm('Release this table?')"><input type="hidden" name="action" value="clear"><input type="hidden" name="table_id" value="<?=$t['table_id']?>"><button>RELEASE TABLE</button></form>
 <?php else:?><div class="free">Ready for a new dine-in order.</div><?php endif;?>
</article><?php endforeach;?></div></main></body></html>