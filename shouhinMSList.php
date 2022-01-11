<!DOCTYPE html>
<html lang="ja">
<?php
require "php_header.php";

if($_POST["commit_btn"] <> ""){
    $array = $_POST["ORDERS"];
    $sqlstr = "";

    foreach($array as $row){
        $sqlstr="select * from ZeiMS where zeiKBN=".$row["zeikbn"].";";
        $result = $mysqli->query( $sqlstr );
        $row_cnt = $result->num_rows;
        $row3 = $result->fetch_assoc();

        $sqlstr = "update ShouhinMS set ";
        $sqlstr = $sqlstr."tanka = ".$row["tanka"].",";
        $sqlstr = $sqlstr."zeiritu = ".$row3["zeiritu"].",";
        $sqlstr = $sqlstr."zeikbn = ".$row["zeikbn"].",";
        $sqlstr = $sqlstr."tani = '".$row["tani"]."',";
        $sqlstr = $sqlstr."bunrui1 = ".$row["bunrui1"].",";
        $sqlstr = $sqlstr."bunrui2 = ".$row["bunrui2"].",";
        $sqlstr = $sqlstr."bunrui3 = ".$row["bunrui3"].",";
        $sqlstr = $sqlstr."hyoujiKBN1 = '".$row["hyoujiKBN1"]."',";
        $sqlstr = $sqlstr."hyoujiKBN2 = ".$row["hyoujiKBN2"].",";
        $sqlstr = $sqlstr."hyoujiKBN3 = ".$row["hyoujiKBN3"].",";
        $sqlstr = $sqlstr."hyoujiNO = ".$row["hyoujiNO"]." ";
        $sqlstr = $sqlstr."where shouhinCD = ".$row["shouhinCD"].";";

        echo $sqlstr."<br>";
	    $stmt = $mysqli->query("LOCK TABLES ShouhinMS WRITE");
	    $stmt = $mysqli->prepare($sqlstr);
	    $stmt->execute();
	    $stmt = $mysqli->query("UNLOCK TABLES");
    
    }
}


$sql = "select * from ShouhinMS left join ZeiMS on ShouhinMS.zeiKBN=ZeiMS.zeiKBN ".$wheresql." order by shouhinCD";
$result = $mysqli->query( $sql );
$row_cnt = $result->num_rows;

$sqlstr="select * from ZeiMS order by zeiKBN;";
$result2 = $mysqli->query( $sqlstr );

?>
<head>
    <?php 
    //共通部分、bootstrap設定、フォントCND、ファビコン等
    include "head.html" 
    ?>
    <!--ページ専用CSS--><link rel="stylesheet" href="css/style_ShouhinMSL.css" >
    <TITLE><?php echo $title." 取扱商品 確認・編集";?></TITLE>
</head>
<header style="flex-wrap:wrap">
    <div style="width: 100%;"><a href="index.php"><?php echo $title;?></a></div>
    <p style="font-size:1rem;">  取扱商品 確認・編集 画面</p>
</header>

<body>    
    <div class="container-fluid">
    <form method="post" action="shouhinMSList.php">
    
    <table class="table-striped">
        <thead><tr><th scope="col" style='width:3rem;'>ID</th><th scope="col">商品名</th><th scope="col">単価(税抜)</th><th scope="col" class='d-none d-sm-table-cell'>税区分</th><th scope="col">内容量</th><th scope="col" class="d-none d-sm-table-cell">単位</th><th scope="col" class="d-none d-sm-table-cell">分類1</th><th scope="col" class="d-none d-sm-table-cell">分類2</th><th scope="col" class="d-none d-sm-table-cell">分類3</th><th scope="col">レジ</th><th scope="col" class="d-none d-sm-table-cell">区分2</th><th scope="col" class="d-none d-sm-table-cell">区分3</th><th scope="col">並順</th></tr></thead>
        <tbody>
<?php    
$i=0;
while($row = $result->fetch_assoc()){
    $chk="";
    if($row["hyoujiKBN1"]=="on"){$chk="checked";}
    echo "<tr>\n";
    echo "<td style='width:3rem;'>".$row["shouhinCD"]."</td>";
    echo "<td style='width:auto;padding:0px 5px 0px 0px;'>".rot13decrypt($row["shouhinNM"])."</td>";
    echo "<td><input type='number'   name ='ORDERS[".$i."][tanka]' style='width:6rem;' value='".$row["tanka"]."'></td>";
    echo "<td><select name ='ORDERS[".$i."][zeikbn]' class='d-none d-sm-table-cell' style='width:10rem;height:30px;'>";
        $result2->data_seek(0);
        while($row2 = $result2->fetch_assoc()){
            if($row["zeiKBN"]==$row2["zeiKBN"]){
                echo "<option value='".$row2["zeiKBN"]."' selected>".$row2["hyoujimei"]."</option>\n";
            }else{
                echo "<option value='".$row2["zeiKBN"]."'>".$row2["hyoujimei"]."</option>\n";
            }
        }
    echo "</select>";
    
    echo "<td><input type='number'   name ='ORDERS[".$i."][utisu]' style='width:5rem;' value='".$row["utisu"]."'></td>";
    echo "<td class='d-none d-sm-table-cell'><input type='text'   name ='ORDERS[".$i."][tani]' style='width:3rem;' value='".$row["tani"]."'></td>";
    echo "<td class='d-none d-sm-table-cell'><input type='number'   name ='ORDERS[".$i."][bunrui1]' style='width:4rem;' value='".$row["bunrui1"]."'></td>";
    echo "<td class='d-none d-sm-table-cell'><input type='number'   name ='ORDERS[".$i."][bunrui2]' style='width:4rem;' value='".$row["bunrui2"]."'></td>";
    echo "<td class='d-none d-sm-table-cell'><input type='number'   name ='ORDERS[".$i."][bunrui3]' style='width:4rem;' value='".$row["bunrui3"]."'></td>";
    echo "<td><input type='checkbox' name ='ORDERS[".$i."][hyoujiKBN1]' style='width:4rem;' ".$chk."></td>";
    echo "<td class='d-none d-sm-table-cell'><input type='number'   name ='ORDERS[".$i."][hyoujiKBN2]' style='width:4rem;' value='".$row["hyoujiKBN2"]."'></td>";
    echo "<td class='d-none d-sm-table-cell'><input type='number'   name ='ORDERS[".$i."][hyoujiKBN3]' style='width:4rem;' value='".$row["hyoujiKBN3"]."'></td>";
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
    <dev class="col-1"></dev>
    <dev class="col-2" style="padding:0;">
        <input type="submit" value="登録" class="btn btn--chk" name="commit_btn">
    </dev>
    </form>
</footer>
</html>
<?php
    $mysqli->close();
?>
