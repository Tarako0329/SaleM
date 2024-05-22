<?php
/*
*params:url
*/
date_default_timezone_set('Asia/Tokyo');
session_start();
require "functions.php";
require "./vendor/autoload.php";
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

define("EXEC_MODE",$_ENV["EXEC_MODE"]);

log_writer2("\$GET[url]",($_GET["url"]),"lv3");
log_writer2("\$GET[url] decode",rawurldecode($_GET["url"]),"lv3");

if(!empty($_GET["url"])){
  $url = urlencode(rawurldecode($_GET["url"]));
}else{
  $url = "NULL";
}

// ヘッダーを指定することによりjsonの動作を安定させる
header('Content-type: application/json');
// htmlへ渡す配列$productListをjsonに変換する
echo json_encode($url, JSON_UNESCAPED_UNICODE);
?>