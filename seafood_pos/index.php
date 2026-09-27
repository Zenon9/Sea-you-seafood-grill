<?php
require __DIR__.'/config/auth.php'; require_login();
if($_SESSION['role']==='kitchen'){ header('Location: kitchen/index.php'); exit; }
require __DIR__.'/config/database.php';
$cats=$pdo->query('SELECT * FROM categories ORDER BY sort_order,category_name')->fetchAll();
$products=$pdo->query("SELECT p.*,c.category_name FROM products p JOIN categories c USING(category_id) WHERE p.status='Available' ORDER BY c.sort_order,p.product_name")->fetchAll();
$tables=$pdo->query("SELECT * FROM restaurant_tables ORDER BY table_number")->fetchAll();
?><!doctype html>
<html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Sea You Cashier POS</title><link rel="stylesheet" href="assets/css/style.css"></head>
<body class="pos-body"><div class="pos-shell">
<header class="pos-header"><div class="brand"><img src="assets/sea-you-logo.jpg" alt="Sea You logo"><div><b>SEA YOU</b><span>Seafood & Grill Restaurant · Cashier POS</span></div></div><div><?=htmlspecialchars($_SESSION['full_name'])?> · <a href="tables.php">Table Checker</a> · <a href="logout.php">Logout</a></div></header>
<main class="pos-grid">
<section class="ticket">
  <div class="order-options">
    <button class="active" data-type="Dine-in">Dine In</button>
    <button data-type="Takeout">Takeout</button>
    <select id="table"><option value="">Select table</option><?php foreach($tables as $t):?><option value="<?=$t['table_id']?>" <?=$t['status']==='occupied'?'disabled':''?>><?=$t['table_number']?> (<?=$t['status']?>)</option><?php endforeach;?></select>
  </div>
  <div class="cart-title"><b>CURRENT ORDER</b><button id="cancel" type="button">Clear</button></div>
  <div class="cart-head"><span>Item</span><span>Qty</span><span>Amount</span></div>
  <div id="cart" class="cart"></div>
  <div class="totals">
    <div><span>SUBTOTAL</span><b id="subtotal">₱0.00</b></div>
    <div class="grand"><span>TOTAL</span><b id="total">₱0.00</b></div>
  </div>
  <button id="checkout" class="checkout-btn" type="button">CHECKOUT</button>
</section>
<section class="menu">
  <div class="menu-top"><input id="search" placeholder="Search menu..."></div>
  <div id="products" class="products"><?php foreach($products as $p):?><button class="product" data-id="<?=$p['product_id']?>" data-cat="<?=$p['category_id']?>" data-name="<?=htmlspecialchars($p['product_name'],ENT_QUOTES)?>" data-price="<?=$p['price']?>" data-stock="<?=$p['stock']?>"><div class="food-icon">🍽️</div><b><?=htmlspecialchars($p['product_name'])?></b><span>₱<?=number_format($p['price'],2)?></span><small>Stock <?=$p['stock']?></small></button><?php endforeach;?></div>
  <div class="categories"><button class="cat active" data-cat="all">All</button><?php foreach($cats as $c):?><button class="cat" data-cat="<?=$c['category_id']?>"><?=htmlspecialchars($c['category_name'])?></button><?php endforeach;?></div>
</section>
</main></div>
<div id="modal" class="modal" aria-hidden="true"><div class="modal-card">
 <div class="modal-head"><div><small>CHECKOUT</small><h2>Confirm Order</h2></div><button id="closeX" type="button">×</button></div>
 <div class="checkout-summary"><span>Order Type</span><b id="summaryType">Dine In</b><span>Table</span><b id="summaryTable">—</b><span>Total</span><b id="modalTotal">₱0.00</b></div>
 <label>Payment Method<select id="method"><option value="Cash">Cash</option><option value="GCash">GCash (record only)</option><option value="Card">Card (record only)</option></select></label>
 <div id="cashFields"><label>Amount Received<input type="number" id="received" min="0" step="0.01"></label><div class="change">Change <b id="change">₱0.00</b></div></div>
 <p class="checkout-note">After payment is confirmed, the order is saved and automatically queued for the Kitchen. There is no separate Send button.</p>
 <div class="modal-actions"><button id="closeModal" type="button">Back</button><button id="pay" type="button">Confirm Checkout</button></div>
</div></div>
<script src="assets/js.js"></script></body></html>