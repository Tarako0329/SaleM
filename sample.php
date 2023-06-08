<?php
date_default_timezone_set('Asia/Tokyo');
require "./vendor/autoload.php";
require_once "functions.php";
$params=[1,11,111];
echo sqllogger("insert into xxx values (?,?,?)",$params,basename(__FILE__));

?>