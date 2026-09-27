<?php
header('Content-Type: application/json');
require __DIR__.'/../config/auth.php'; require_login();
require __DIR__.'/../config/database.php';

$d=json_decode(file_get_contents('php://input'),true);
$items=$d['items']??[];
$type=$d['order_type']??'Dine-in';
$tableId=$type==='Dine-in'?(int)($d['table_id']??0):null;
$payment=$d['payment']??null;

if(!$items){ echo json_encode(['ok'=>false,'message'=>'Cart is empty.']); exit; }
if(!in_array($type,['Dine-in','Takeout'],true)){ echo json_encode(['ok'=>false,'message'=>'Invalid order type.']); exit; }
if($type==='Dine-in' && !$tableId){ echo json_encode(['ok'=>false,'message'=>'Please select a table.']); exit; }
if(!$payment){ echo json_encode(['ok'=>false,'message'=>'Payment is required.']); exit; }

try{
  $pdo->beginTransaction();

  if($tableId){
    $t=$pdo->prepare("SELECT table_number,status FROM restaurant_tables WHERE table_id=? FOR UPDATE");
    $t->execute([$tableId]); $table=$t->fetch();
    if(!$table) throw new Exception('Selected table does not exist.');
    if($table['status']!=='available') throw new Exception('Table '.$table['table_number'].' is already occupied.');
  }

  $subtotal=0; $verified=[];
  foreach($items as $i){
    $id=(int)($i['id']??0); $q=(int)($i['qty']??0);
    if($id<1||$q<1) throw new Exception('Invalid cart item.');
    $st=$pdo->prepare("SELECT product_id,product_name,price,stock,status FROM products WHERE product_id=? FOR UPDATE");
    $st->execute([$id]); $p=$st->fetch();
    if(!$p || $p['status']!=='Available') throw new Exception(($p['product_name']??'An item').' is unavailable.');
    if((int)$p['stock']<$q) throw new Exception('Only '.$p['stock'].' stock left for '.$p['product_name'].'.');
    $subtotal+=(float)$p['price']*$q; $verified[]=[$p,$q];
  }

  $method=$payment['method']??'Cash';
  if(!in_array($method,['Cash','GCash','Card'],true)) throw new Exception('Invalid payment method.');
  $received=$method==='Cash'?(float)($payment['received']??0):$subtotal;
  if($method==='Cash' && $received<$subtotal) throw new Exception('Amount received is less than the total.');
  $change=$method==='Cash'?max(0,$received-$subtotal):0;

  $orderNo='SY-'.date('Ymd-His').'-'.random_int(10,99);
  $st=$pdo->prepare("INSERT INTO orders(order_number,user_id,table_id,order_type,subtotal,total_amount,order_status,payment_status) VALUES(?,?,?,?,?,?,'Pending','Paid')");
  $st->execute([$orderNo,$_SESSION['user_id'],$tableId?:null,$type,$subtotal,$subtotal]);
  $oid=(int)$pdo->lastInsertId();

  foreach($verified as [$p,$q]){
    $line=(float)$p['price']*$q;
    $pdo->prepare("INSERT INTO order_items(order_id,product_id,quantity,price,subtotal) VALUES(?,?,?,?,?)")->execute([$oid,$p['product_id'],$q,$p['price'],$line]);
    $pdo->prepare("UPDATE products SET stock=stock-?,status=IF(stock-?<=0,'Unavailable',status) WHERE product_id=?")->execute([$q,$q,$p['product_id']]);
  }

  $pdo->prepare("INSERT INTO payments(order_id,amount,payment_method,amount_received,change_amount) VALUES(?,?,?,?,?)")->execute([$oid,$subtotal,$method,$received,$change]);
  if($tableId) $pdo->prepare("UPDATE restaurant_tables SET status='occupied' WHERE table_id=?")->execute([$tableId]);
  $pdo->prepare("INSERT INTO activity_logs(user_id,action) VALUES(?,?)")->execute([$_SESSION['user_id'],'Checked out and queued '.$orderNo]);

  $pdo->commit();
  echo json_encode(['ok'=>true,'order_id'=>$oid,'order_number'=>$orderNo,'total'=>$subtotal,'change'=>$change,'receipt_url'=>'receipt.php?id='.$oid]);
}catch(Throwable $e){
  if($pdo->inTransaction()) $pdo->rollBack();
  http_response_code(400);
  echo json_encode(['ok'=>false,'message'=>$e->getMessage()]);
}
?>