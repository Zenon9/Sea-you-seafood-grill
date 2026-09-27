<?php
require __DIR__.'/config/auth.php'; require_login(); require __DIR__.'/config/database.php';
$id=(int)($_GET['id']??0);
$st=$pdo->prepare("SELECT o.*,u.full_name,t.table_number,p.payment_method,p.amount_received,p.change_amount,p.payment_date FROM orders o JOIN users u ON u.user_id=o.user_id LEFT JOIN restaurant_tables t ON t.table_id=o.table_id LEFT JOIN payments p ON p.order_id=o.order_id WHERE o.order_id=?");
$st->execute([$id]); $o=$st->fetch(); if(!$o){http_response_code(404);die('Receipt not found.');}
$st=$pdo->prepare("SELECT oi.quantity,oi.price,oi.subtotal,pr.product_name FROM order_items oi JOIN products pr ON pr.product_id=oi.product_id WHERE oi.order_id=? ORDER BY oi.order_item_id");
$st->execute([$id]); $items=$st->fetchAll();
?><!doctype html><html><head><meta charset="utf-8"><title>Receipt <?=$o['order_number']?></title><meta name="viewport" content="width=device-width,initial-scale=1">
<style>*{box-sizing:border-box}body{margin:0;background:#e8edf3;color:#111;font:14px Consolas,monospace}.receipt{width:360px;max-width:94vw;margin:25px auto;background:white;padding:24px;box-shadow:0 8px 30px #0002}.center{text-align:center}.receipt-logo{width:74px;height:74px;object-fit:cover;border-radius:50%;margin-bottom:6px}.logo{font:800 24px Arial}.muted{color:#555}.line{border-top:1px dashed #555;margin:14px 0}.row{display:flex;justify-content:space-between;gap:12px;margin:7px 0}.item{display:grid;grid-template-columns:1fr auto;gap:10px;margin:8px 0}.item small{display:block;color:#555}.total{font-size:18px;font-weight:bold}.actions{display:flex;gap:8px;margin-top:20px}.actions button,.actions a{flex:1;text-align:center;border:0;padding:10px;background:#c91b0c;color:#fff;text-decoration:none;border-radius:5px}@media print{body{background:white}.receipt{box-shadow:none;margin:0;width:80mm;max-width:none}.actions{display:none}}</style></head><body><div class="receipt">
<div class="center"><img class="receipt-logo" src="assets/sea-you-logo.jpg" alt="Sea You"><div class="logo">SEA YOU</div><b>SEAFOOD & GRILL RESTAURANT</b><p class="muted">Official POS Receipt</p></div><div class="line"></div>
<div class="row"><span>Order</span><b><?=htmlspecialchars($o['order_number'])?></b></div>
<div class="row"><span>Date</span><span><?=htmlspecialchars($o['order_date'])?></span></div>
<div class="row"><span>Cashier</span><span><?=htmlspecialchars($o['full_name'])?></span></div>
<div class="row"><span>Type</span><span><?=htmlspecialchars($o['order_type'])?></span></div>
<?php if($o['table_number']):?><div class="row"><span>Table</span><span><?=htmlspecialchars($o['table_number'])?></span></div><?php endif;?>
<div class="line"></div><?php foreach($items as $x):?><div class="item"><div><?=htmlspecialchars($x['product_name'])?><small><?=$x['quantity']?> × ₱<?=number_format($x['price'],2)?></small></div><b>₱<?=number_format($x['subtotal'],2)?></b></div><?php endforeach;?>
<div class="line"></div><div class="row"><span>Subtotal</span><span>₱<?=number_format($o['subtotal'],2)?></span></div><div class="row total"><span>TOTAL</span><span>₱<?=number_format($o['total_amount'],2)?></span></div>
<div class="line"></div><div class="row"><span>Payment</span><span><?=htmlspecialchars($o['payment_method'])?></span></div><div class="row"><span>Received</span><span>₱<?=number_format($o['amount_received'],2)?></span></div><div class="row"><span>Change</span><span>₱<?=number_format($o['change_amount'],2)?></span></div>
<div class="line"></div><p class="center">Thank you for dining with us!</p><div class="actions"><a href="index.php">New Order</a><button onclick="window.print()">Print</button></div>
</div></body></html>