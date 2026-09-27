<?php
if(session_status()===PHP_SESSION_NONE) session_start();
function require_login(){ if(empty($_SESSION['user_id'])){ header('Location: /seafood_pos/login.php'); exit; } }
function require_role($roles){ require_login(); if(!in_array($_SESSION['role'],(array)$roles,true)){ http_response_code(403); die('Access denied'); } }
?>
