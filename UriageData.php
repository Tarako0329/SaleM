<!DOCTYPE html>
<?php

// 設定ファイルインクルード【開発中】

require "functions.php";

//売上実績の取得
if($_POST["UriDate"]<>""){
    $wheresql="where UriDate = '".$_POST["UriDate"]."'";
}else{
    $wheresql="where UriDate like '%'";
}
if($_POST["Event"]<>""){
    $wheresql = $wheresql." AND Event='".$_POST["Event"]."'";
}
if($_POST["Tokui"]<>""){
    $wheresql= $wheresql." AND TokuisakiNM='".$_POST["Tokui"]."'";
}

if($_POST["Type"]=="rireki" || $_POST["Type"]==""){
    $sql = "select * from UriageData ".$wheresql." order by UriageNO";
}elseif($_POST["Type"]=="shubetu"){
    $sql = "select UriDate,'-' as UriageNO,ShouhinNM,sum(su) as su,tanka,sum(UriageKin) as UriageKin from UriageData ".$wheresql." group by UriDate,ShouhinNM,tanka order by UriageNO";
}elseif($_POST["Type"]=="UriNO"){
    $sql = "select UriDate,UriageNO,'-' as ShouhinNM,sum(su) as su,tanka,sum(UriageKin) as UriageKin from UriageData ".$wheresql." group by UriDate,ShouhinNM,tanka order by UriageNO";
}else{
    echo "そんな！";
}
//echo $sql;
$result = $mysqli->query( $sql );
$row_cnt = $result->num_rows;


//Eventリスト
$EVsql = "select Event from UriageData group by Event order by Event";
$EVresult = $mysqli->query( $EVsql );
$EVrow_cnt = $result->num_rows;

//顧客リスト
$TKsql = "select TokuisakiNM from UriageData group by TokuisakiNM order by TokuisakiNM";
$TKresult = $mysqli->query( $TKsql );
$TKrow_cnt = $result->num_rows;

?>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <META http-equiv='Content-Type' content='text/html; charset=UTF-8'>
    <TITLE>Cafe Presents　売上実績</TITLE>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
    <!-- オリジナル CSS -->
    <link rel="stylesheet" href="css/style_UriageData.css" >
</head>
 
<!-- Bootstrap Javascript(jQuery含む) -->
<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js" integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js" integrity="sha384-ChfqqxuZUCnJSK3+MXmPNIyE6ZbWh2IMqE241rYiqJxyMiZ6OW/JmZQ5stwEULTy" crossorigin="anonymous"></script>

<header>売上実績画面

    <form method="post" action="UriageData.php">
    <div class="container-fluid" style="position: fixed;">
        <div>
        売上日：<input type="date" name="UriDate" maxlength="10" value="<?php echo (string)date("Y-m-d"); ?>">
        イベント名：
        <select name="Event">
            <option value=""></option>
            <?php
            while($row = $EVresult->fetch_assoc()){
                echo "<option value='".$row["Event"]."'>".$row["Event"]."</option>\n";
            }
            ?>
        </select>
        </div>
        <div>
        得意先：<select name="Tokui">
            <option value=""></option>
            <?php
            while($row = $TKresult->fetch_assoc()){
                echo "<option value='".$row["TokuisakiNM"]."'>".$row["TokuisakiNM"]."</option>\n";
            }
            ?>
        </select>
        表示：<select name="Type">
            <option value="rireki">履歴</option>
            <option value="shubetu">種類別</option>
            <option value="UriNO">顧客別</option>
        </select>
        <input type="submit" value="決定" class="btn btn-primary">
        </div>
    </div>
    </form>
</header>

<body>    
    <div class="container-fluid">
    <table class="table-striped">
        <tr><td>売上日</td><td>売上№</td><td>商品</td><td style="width:3rem;">個数</td><td style="width:3rem;">単価</td><td style="width:5rem;">売上</td></tr>
<?php    
$Goukei=0;
while($row = $result->fetch_assoc()){
    echo "<tr><td>".$row["UriDate"]."</td><td class='text-center'>".$row["UriageNO"]."</td><td>".rot13decrypt($row["ShouhinNM"])."</td><td class='text-right'>".$row["su"]."</td><td class='text-right'>".$row["tanka"]."</td><td class='text-right'>".$row["UriageKin"]."</td></tr>\n";
    $Goukei = $Goukei + $row["UriageKin"];
}
echo "<tr><td></td><td></td><td></td><td></td><td>合計</td><td>".$Goukei."-</td></tr>\n";
?>
    </table>
    </div>
</body>