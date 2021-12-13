<!DOCTYPE html>
<?php

// 設定ファイルインクルード【開発中】
$pass=dirname(__FILE__);
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
    echo $_POST["shouhinNM"]."　が登録されました。";
}
?>
<head>
    <!--<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">-->
    <META http-equiv='Content-Type' content='text/html; charset=UTF-8'>
    <TITLE>Cafe Presents　取扱商品登録画面</TITLE>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
    <!-- オリジナル CSS -->
    <!--<link rel="stylesheet" href="css/style.css" >-->
</head>
 
<!-- Bootstrap Javascript(jQuery含む) -->
<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js" integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js" integrity="sha384-ChfqqxuZUCnJSK3+MXmPNIyE6ZbWh2IMqE241rYiqJxyMiZ6OW/JmZQ5stwEULTy" crossorigin="anonymous"></script>

<header>取扱商品登録画面</header>
<body>
    <form method="post" action="shouhinMSedit.php">
        <div>商品名   ：<input type="text" name="shouhinNM"></div>
        <div>単価     ：<input type="text" name="tanka"></div>
        <div>税率     ：<input type="text" name="zeiritu"></div>
        <div>税区分　 ：<input type="text" name="zeikbn"></div>
        <div>内容量   ：<input type="text" name="utisu"></div>
        <div>単位     ：<input type="text" name="tani"></div>
        <div>分類1    ：<input type="text" name="bunrui1"></div>
        <div>分類2    ：<input type="text" name="bunrui2"></div>
        <div>分類3    ：<input type="text" name="bunrui3"></div>
        <div>表示区分1：<input type="text" name="hyoujiKBN1"></div>
        <div>表示区分2：<input type="text" name="hyoujiKBN2"></div>
        <div>表示区分3：<input type="text" name="hyoujiKBN3"></div>
        <div>表示順   ：<input type="text" name="hyoujiNO"></div>
        <button type="submit" name="btn" value="登録">登録</button>
    </form>

</body>

<?php
    $mysqli->close();
?>
