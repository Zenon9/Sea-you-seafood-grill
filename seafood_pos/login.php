<?php
session_start(); require __DIR__.'/config/database.php';
if(!empty($_SESSION['user_id'])){ header('Location: index.php'); exit; }
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 $st=$pdo->prepare("SELECT * FROM users WHERE username=? AND status='active' LIMIT 1"); $st->execute([trim($_POST['username']??'')]); $u=$st->fetch();
 if($u && password_verify($_POST['password']??'',$u['password'])){ $_SESSION=['user_id'=>$u['user_id'],'full_name'=>$u['full_name'],'role'=>$u['role']]; header('Location: index.php'); exit; }
 $error='Invalid username or password.';
}
?><!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Sea You POS Login</title><link rel="stylesheet" href="assets/css/style.css"></head><body class="login-body"><div class="login-wrap"><div class="login-brand"><img src="assets/sea-you-logo.jpg" alt="Sea You Seafood & Grill"><h1>SEA YOU</h1><p>Seafood & Grill Restaurant</p><small>FOOD IS ALWAYS A GOOD IDEA</small></div><form class="login-card" method="post"><h2>Welcome Back</h2><p>Sign in to the restaurant POS</p><?php if($error):?><div class="error"><?=htmlspecialchars($error)?></div><?php endif;?><label>Username<input name="username" placeholder="Enter username" required autofocus></label><label>Password<input type="password" name="password" placeholder="Enter password" required></label><button>LOGIN</button><small class="demo">Demo: admin / password · cashier / password · kitchen / password</small></form></div></body></html>