<?php
date_default_timezone_set('Asia/Tokyo');
require "./vendor/autoload.php";
require_once "functions.php";

$sql = "insert into hoge values(:hoge1,:hoge2,:hoge3)";
$params["hoge1"] = "1";
$params["hoge2"] = "2";
$params["hoge3"] = "3";

var_dump(array_keys($params));
sqllogger($sql,$params,"test","ng");
?>