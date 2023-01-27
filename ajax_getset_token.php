<?php
/*
*params:none
*/
date_default_timezone_set('Asia/Tokyo');
session_start();
require "functions.php";
require "./vendor/autoload.php";

log_writer2("ajax_getset_token.php","Lost Token","lv1");
$token = csrf_create();
log_writer2("ajax_getset_token.php","Reset Token::".$token,"lv1");

// ヘッダーを指定することによりjsonの動作を安定させる
header('Content-type: application/json');
// htmlへ渡す配列$productListをjsonに変換する
echo json_encode($token, JSON_UNESCAPED_UNICODE);
?>


