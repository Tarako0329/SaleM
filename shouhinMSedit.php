<!DOCTYPE html>
<html lang="ja">
<?php
require "php_header.php";

if($_POST["btn"] == "登録"){
    $sqlstr="select * from ZeiMS where zeiKBN='".$_POST["zeikbn"]."';";
    $result = $mysqli->query( $sqlstr );
    $row_cnt = $result->num_rows;
    $row = $result->fetch_assoc();

    $sqlstr="insert into ShouhinMS values(0,'".rot13encrypt($_POST["shouhinNM"]);
    $sqlstr=$sqlstr."','".$_POST["tanka"];
    $sqlstr=$sqlstr."',".$row["zeiritu"];
    $sqlstr=$sqlstr.",".$row["zeiKBN"];
    $sqlstr=$sqlstr.",'".$_POST["utisu"];
    $sqlstr=$sqlstr."','".$_POST["tani"];
    $sqlstr=$sqlstr."','".$_POST["bunrui1"];
    $sqlstr=$sqlstr."','".$_POST["bunrui2"];
    $sqlstr=$sqlstr."','".$_POST["bunrui3"];
    $sqlstr=$sqlstr."','".$_POST["hyoujiKBN1"];
    $sqlstr=$sqlstr."','".$_POST["hyoujiKBN2"];
    $sqlstr=$sqlstr."','".$_POST["hyoujiKBN3"];
    $sqlstr=$sqlstr."','".$_POST["hyoujiNO"]."');";
    
    //echo $sqlstr;

	$stmt = $mysqli->query("LOCK TABLES ShouhinMS WRITE");
	$stmt = $mysqli->prepare($sqlstr);
	$stmt->execute();
	$stmt = $mysqli->query("UNLOCK TABLES");
    echo $_POST["shouhinNM"]."　が登録されました。<br>";
    //echo $_POST["hyoujiKBN1"];
}

$sqlstr="select * from ZeiMS order by zeiKBN;";
$result = $mysqli->query( $sqlstr );
$row_cnt = $result->num_rows;

?>
<head>
    <?php 
    //共通部分、bootstrap設定、フォントCND、ファビコン等
    include "head.html" 
    ?>
    <!--ページ専用CSS--><link rel="stylesheet" href="css/style_ShouhinMSedit.css" >
    <TITLE><?php echo $title." 取扱商品登録画面";?></TITLE>
</head>
<header style="flex-wrap:wrap">
    <div style="width: 100%;"><a href="index.php"><?php echo $title;?></a></div>
    <p style="font-size:1rem;">  取扱商品登録画面</p>
</header>

<body>
    <div class="container-fluid" style="padding-top:15px;">
    <form method="post" class="form" action="shouhinMSedit.php">
        <div class="form-group form-inline">
            <label for="shouhinNM" class="col-2 col-md-1 control-label">商品名</label>
            <div class=" col-10">
                <input type="text" class="form-control" id="shouhinNM" name="shouhinNM" required="required">
            </div>
        </div>
        <div class="form-group form-inline">
            <label for="tanka" class="col-2 col-md-1 control-label">単価(税抜)</label>
            <div class=" col-10">
                <input type="text" class="form-control" id="tanka" name="tanka" required="required">
            </div>
        </div>
        <!--
        <div class="form-group form-inline">
            <label for="zeiritu" class="col-2 col-md-1 control-label">税率</label>
            <div class=" col-10">
                <input type="text" class="form-control" id="zeiritu" name="zeiritu">
            </div>
        </div>
        -->
        <div class="form-group form-inline">
            <label for="zeikbn" class="col-2 col-md-1 control-label">税区分</label>
            <div class=" col-10">
                <!--<input type="text" class="form-control" id="zeikbn" name="zeikbn">-->
                <select class="form-control" id="zeikbn" name="zeikbn" required="required">
                    <option value=""></option>
                    <?php
                    while($row = $result->fetch_assoc()){
                        echo "<option value='".$row["zeiKBN"]."'>".$row["hyoujimei"]."</option>\n";
                    }
                    ?>
                </select>
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
            <label class="col-2 col-md-1 control-label">レジ対象</label>
            <div class="col-10">
                <label for="hyoujiKBN1" style="float:left;">
                     <input type="checkbox" style="vertical-align:middle;" id="hyoujiKBN1" name="hyoujiKBN1">（表示する）
                </label>
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
        <div class="col-2 col-md-1" style=" padding:0; margin-top:10px;">
            <button type="submit" class="btn btn-primary" style="width:100%;" name="btn" value="登録">登録</button>
        </div>
    </form>
    </div>

</body>
</html>
<?php
    $mysqli->close();
?>




















