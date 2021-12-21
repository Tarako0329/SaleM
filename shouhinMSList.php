<!DOCTYPE html>
<html lang="ja">
<?php

// 設定ファイルインクルード【開発中】
$pass=dirname(__FILE__);
require "version.php";
require "../SQ/functions.php";

if($_POST["commit_btn"] <> ""){
    $array = $_POST["ORDERS"];
    $sqlstr = "";

    foreach($array as $row){
        $sqlstr = "update ShouhinMS set ";
        $sqlstr = $sqlstr."tanka = ".$row["tanka"].",";
        $sqlstr = $sqlstr."zeiritu = ".$row["zeiritu"].",";
        $sqlstr = $sqlstr."zeikbn = '".$row["zeikbn"]."',";
        $sqlstr = $sqlstr."tani = '".$row["tani"]."',";
        $sqlstr = $sqlstr."bunrui1 = ".$row["bunrui1"].",";
        $sqlstr = $sqlstr."bunrui2 = ".$row["bunrui2"].",";
        $sqlstr = $sqlstr."bunrui3 = ".$row["bunrui3"].",";
        $sqlstr = $sqlstr."hyoujiKBN1 = '".$row["hyoujiKBN1"]."',";
        $sqlstr = $sqlstr."hyoujiKBN2 = ".$row["hyoujiKBN2"].",";
        $sqlstr = $sqlstr."hyoujiKBN3 = ".$row["hyoujiKBN3"].",";
        $sqlstr = $sqlstr."hyoujiNO = ".$row["hyoujiNO"]." ";
        $sqlstr = $sqlstr."where shouhinCD = ".$row["shouhinCD"].";";

        //echo $sqlstr."<br>";
	    $stmt = $mysqli->query("LOCK TABLES ShouhinMS WRITE");
	    $stmt = $mysqli->prepare($sqlstr);
	    $stmt->execute();
	    $stmt = $mysqli->query("UNLOCK TABLES");
    
    }
}


$sql = "select * from ShouhinMS ".$wheresql." order by shouhinCD";
$result = $mysqli->query( $sql );
$row_cnt = $result->num_rows;

?>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <META http-equiv='Content-Type' content='text/html; charset=UTF-8'>
    <TITLE>Cafe Presents　取扱商品 確認・編集</TITLE>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <!--フォントCDN-->
    <link href="https://fonts.googleapis.com/css2?family=Kosugi+Maru&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
    <!-- オリジナル CSS -->
    <link rel="stylesheet" href="css/style_ShouhinMSL.css" >
</head>
 
<!-- Bootstrap Javascript(jQuery含む) -->
<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js" integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js" integrity="sha384-ChfqqxuZUCnJSK3+MXmPNIyE6ZbWh2IMqE241rYiqJxyMiZ6OW/JmZQ5stwEULTy" crossorigin="anonymous"></script>

<header>
    <div class="yagou"><a href="index.php">Cafe Presents</a></div>
    <p style="font-size:1rem;">  取扱商品 確認・編集 画面</p>
</header>

<body>    
    <div class="container-fluid">
    <form method="post" action="shouhinMSList.php">
    
    <table class="table-striped">
        <thead><tr><th scope="col">ID</th><th scope="col">商品名</th><th scope="col">単価</th><th scope="col">税率</th><th scope="col">税区分</th><th scope="col">内容量</th><th scope="col">単位</th><th scope="col">分類1</th><th scope="col">分類2</th><th scope="col">分類3</th><th scope="col">レジ対象</th><th scope="col">区分2</th><th scope="col">区分3</th><th scope="col">表示順</th></tr></thead>
        <tbody>
<?php    
$i=0;
while($row = $result->fetch_assoc()){
    $chk="";
    if($row["hyoujiKBN1"]=="on"){$chk="checked";}
    echo "<tr>\n";
    echo "<td style='width:2rem;'>".$row["shouhinCD"]."</td>";
    echo "<td style='width:auto;padding:0px 5px 0px 0px;'>".rot13decrypt($row["shouhinNM"])."</td>";
    echo "<td><input type='number'   name ='ORDERS[".$i."][tanka]' style='width:7rem;' value='".$row["tanka"]."'></td>";
    echo "<td><input type='number'   name ='ORDERS[".$i."][zeiritu]' style='width:3rem;' value='".$row["zeiritu"]."'></td>";
    echo "<td><input type='number'   name ='ORDERS[".$i."][zeikbn]' style='width:3rem;' value='".$row["zeikbn"]."'></td>";
    echo "<td><input type='number'   name ='ORDERS[".$i."][utisu]' style='width:3rem;' value='".$row["utisu"]."'></td>";
    echo "<td><input type='text'   name ='ORDERS[".$i."][tani]' style='width:3rem;' value='".$row["tani"]."'></td>";
    echo "<td><input type='number'   name ='ORDERS[".$i."][bunrui1]' style='width:4rem;' value='".$row["bunrui1"]."'></td>";
    echo "<td><input type='number'   name ='ORDERS[".$i."][bunrui2]' style='width:4rem;' value='".$row["bunrui2"]."'></td>";
    echo "<td><input type='number'   name ='ORDERS[".$i."][bunrui3]' style='width:4rem;' value='".$row["bunrui3"]."'></td>";
    echo "<td><input type='checkbox' name ='ORDERS[".$i."][hyoujiKBN1]' style='width:4rem;' ".$chk."></td>";
    echo "<td><input type='number'   name ='ORDERS[".$i."][hyoujiKBN2]' style='width:4rem;' value='".$row["hyoujiKBN2"]."'></td>";
    echo "<td><input type='number'   name ='ORDERS[".$i."][hyoujiKBN3]' style='width:4rem;' value='".$row["hyoujiKBN3"]."'></td>";
    echo "<td><input type='number'   name ='ORDERS[".$i."][hyoujiNO]' style='width:4rem;' value='".$row["hyoujiNO"]."'></td>";
    echo "</tr>\n";
    echo "<input type='hidden'   name ='ORDERS[".$i."][shouhinCD]' value='".$row["shouhinCD"]."'>";
    $i = $i+1;
}

?>
        </tbody>
    </table>
    
    
    </div>
</body>

<footer>
    <input type="submit" value="登録" class="btn btn-primary" name="commit_btn">
    </form>
</footer>
</html>
<?php
    $mysqli->close();
?>
