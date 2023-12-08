<?php
//売上修正画面の修正モードフラグ更新
date_default_timezone_set('Asia/Tokyo');
session_start();

$_SESSION["UriageData_Correct_mode"]=(empty($_POST["mode"])?"%":$_POST["mode"]);
$_SESSION["ColorCSS"]=(empty($_GET["ColorCSS"])?"":$_GET["ColorCSS"]);

?>