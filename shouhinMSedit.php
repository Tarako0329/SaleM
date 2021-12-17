<!DOCTYPE html>
<html lang="ja">
<?php

// 設定ファイルインクルード【開発中】
$pass=dirname(__FILE__);
require "version.php";
require "../SQ/functions.php";

if($_POST["btn"] == "登録"){
    $sqlstr="insert into ShouhinMS values(0,'".rot13encrypt($_POST["shouhinNM"]);
    $sqlstr=$sqlstr."','".$_POST["tanka"];
    $sqlstr=$sqlstr."','".$_POST["zeiritu"];
    $sqlstr=$sqlstr."','".$_POST["zeikbn"];
    $sqlstr=$sqlstr."','".$_POST["utisu"];
    $sqlstr=$sqlstr."','".$_POST["tani"];
    $sqlstr=$sqlstr."','".$_POST["bunrui1"];
    $sqlstr=$sqlstr."','".$_POST["bunrui2"];
    $sqlstr=$sqlstr."','".$_POST["bunrui3"];
    $sqlstr=$sqlstr."','".$_POST["hyoujiKBN1"];
    $sqlstr=$sqlstr."','".$_POST["hyoujiKBN2"];
    $sqlstr=$sqlstr."','".$_POST["hyoujiKBN3"];
    $sqlstr=$sqlstr."','".$_POST["hyoujiNO"]."');";


	$stmt = $mysqli->query("LOCK TABLES ShouhinMS WRITE");
	$stmt = $mysqli->prepare($sqlstr);
	$stmt->execute();
	$stmt = $mysqli->query("UNLOCK TABLES");
    echo $_POST["shouhinNM"]."　が登録されました。<br>";
    //echo $_POST["hyoujiKBN1"];
}
?>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <META http-equiv='Content-Type' content='text/html; charset=UTF-8'>
    <TITLE>Cafe Presents　取扱商品登録画面</TITLE>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
    <!-- オリジナル CSS -->
    <link rel="stylesheet" href="css/style_ShouhinMSedit.css" >
</head>
 
<!-- Bootstrap Javascript(jQuery含む) -->
<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js" integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js" integrity="sha384-ChfqqxuZUCnJSK3+MXmPNIyE6ZbWh2IMqE241rYiqJxyMiZ6OW/JmZQ5stwEULTy" crossorigin="anonymous"></script>

<header>取扱商品登録画面</header>
<body>
    <div class="container-fluid">
    <form method="post" class="form-horizontal" action="shouhinMSedit.php">
        <div class="form-group form-inline">
            <label for="shouhinNM" class="col-2 col-md-1 control-label">商品名</label>
            <div class=" col-10">
                <input type="text" class="form-control" id="shouhinNM" name="shouhinNM">
            </div>
        </div>
        <div class="form-group form-inline">
            <label for="tanka" class="col-2 col-md-1 control-label">単価</label>
            <div class=" col-10">
                <input type="text" class="form-control" id="tanka" name="tanka">
            </div>
        </div>
        <div class="form-group form-inline">
            <label for="zeiritu" class="col-2 col-md-1 control-label">税率</label>
            <div class=" col-10">
                <input type="text" class="form-control" id="zeiritu" name="zeiritu">
            </div>
        </div>
        <div class="form-group form-inline">
            <label for="zeikbn" class="col-2 col-md-1 control-label">税区分</label>
            <div class=" col-10">
                <input type="text" class="form-control" id="zeikbn" name="zeikbn">
            </div>
        </div>
        <div class="form-group form-inline">
            <label for="utisu" class="col-2 col-md-1 control-label">内容量</label>
            <div class=" col-10">
                <input type="text" class="form-control" id="utisu" name="utisu">
            </div>
        </div>
        <div class="form-group form-inline">
            <label for="tani" class="col-2 col-md-1 control-label">単位</label>
            <div class=" col-10">
                <input type="text" class="form-control" id="tani" name="tani">
            </div>
        </div>
        <div class="form-group form-inline">
            <label for="bunrui1" class="col-2 col-md-1 control-label">分類1</label>
            <div class=" col-10">
                <input type="text" class="form-control" id="bunrui1" name="bunrui1">
            </div>
        </div>
        <div class="form-group form-inline">
            <label for="bunrui2" class="col-2 col-md-1 control-label">分類2</label>
            <div class=" col-10">
                <input type="text" class="form-control" id="bunrui2" name="bunrui2">
            </div>
        </div>
        <div class="form-group form-inline">
            <label for="bunrui3" class="col-2 col-md-1 control-label">分類3</label>
            <div class=" col-10">
                <input type="text" class="form-control" id="bunrui3" name="bunrui3">
            </div>
        </div>
        <div class="form-group form-inline form-switch">
            <label for="hyoujiKBN1" class="col-2 col-md-1 control-label">レジ対象</label>
            <div class=" col-10">
                <input type="checkbox" class="form-check-input" id="hyoujiKBN1" name="hyoujiKBN1">
            </div>
        </div>
        <div class="form-group form-inline">
            <label for="hyoujiKBN2" class="col-2 col-md-1 control-label">表示区分2</label>
            <div class=" col-10">
                <input type="text" class="form-control" id="hyoujiKBN2" name="hyoujiKBN2">
            </div>
        </div>
        <div class="form-group form-inline">
            <label for="hyoujiKBN3" class="col-2 col-md-1 control-label">表示区分3</label>
            <div class=" col-10">
                <input type="text" class="form-control" id="hyoujiKBN3" name="hyoujiKBN3">
            </div>
        </div>
        <div class="form-group form-inline">
            <label for="hyoujiNO" class="col-2 col-md-1 control-label">表示順</label>
            <div class=" col-10">
                <input type="text" class="form-control" id="hyoujiNO" name="hyoujiNO">
            </div>
        </div>
        <button type="submit" class="btn btn-primary" name="btn" value="登録">登録</button>
    </form>
    </div>

</body>
</html>
<?php
    $mysqli->close();
?>




















