<!DOCTYPE html>
<html lang="ja">
<?php
require "php_header.php";
if(isset($_GET["csrf_token"]) || empty($_POST)){
    if(csrf_chk_nonsession_get($_GET["csrf_token"])==false){
        $_SESSION["EMSG"]="セッションが正しくありませんでした。";
        header("HTTP/1.1 301 Moved Permanently");
        header("Location: index.php");
        exit();
    }
}elseif(csrf_chk()==false){
    $_SESSION["EMSG"]="セッションが正しくありませんでした";
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: index.php");
}

$rtn=check_session_userid();
$csrf_create = csrf_create();

if(isset($_POST["cd"]) && isset($_POST["urino"])){
    //削除モード(実行)
    $mode="del";
    $sql="delete from UriageData where uid = :user_id and UriageNO = :UriNO and ShouhinCD = :ShouhinCD";
    $stmt = $pdo_h->prepare( $sql );
    $stmt->bindValue("UriNO", $_POST["urino"], PDO::PARAM_INT);
    $stmt->bindValue("ShouhinCD", $_POST["cd"], PDO::PARAM_INT);
    $stmt->bindValue("user_id", $_SESSION["user_id"], PDO::PARAM_INT);
    $status = $stmt->execute();
    if($status){
        $msg = "売上を削除しました<br><br>";
    }else{
        $msg = "売り上げ削除失敗<br><br>";
    }
}
if(isset($_GET["cd"]) && isset($_GET["urino"])){
    //削除モード(確認)
    $mode="del";
    $msg ="この売上を削除しますか？<br><br>";
    $sql = "select * from UriageData where uid = :user_id and UriageNO = :UriNO and ShouhinCD = :ShouhinCD order by UriageNO";
    $stmt = $pdo_h->prepare( $sql );
    $stmt->bindValue("UriNO", $_GET["urino"], PDO::PARAM_INT);
    $stmt->bindValue("ShouhinCD", $_GET["cd"], PDO::PARAM_INT);
}else{
    //売上実績の取得
    if($_POST["UriDate"]<>""){
    $wheresql="where UriDate >= :UriDate ";
    $UriFrom = (string)$_POST["UriDate"];
    }else{
    $wheresql="where UriDate >= '".(string)date("Y-m-d")."'";
    $UriFrom = (string)date("Y-m-d");
}
    
    $wheresql=$wheresql." AND UriDate <= :UriDateTo ";
    if($_POST["UriDateTo"]<>""){
    $UriTo = (string)$_POST["UriDateTo"];
    }else{
    $UriTo = $UriFrom;
}
    if($_POST["Event"]<>""){
        $wheresql = $wheresql." AND Event=:Event ";
    }
    if($_POST["Tokui"]<>""){
        $wheresql= $wheresql." AND TokuisakiNM=:Tokui ";
    }
    $wheresql= $wheresql." AND uid = :user_id ";
    if($_POST["Type"]=="rireki" || $_POST["Type"]==""){
        //履歴取得
        $sql = "select * from UriageData ".$wheresql." order by UriageNO";
    }elseif($_POST["Type"]=="shubetu"){
        //商品別<!-- 何が売れてるか知りたい -->
        $sql = "select '-' as UriDate,'-' as Event,'-' as TokuisakiNM,'-' as UriageNO,'-' as Event,ShouhinNM,sum(su) as su,tanka,sum(UriageKin) as UriageKin,sum(zei) as zei from UriageData ".$wheresql." group by ShouhinNM,tanka order by ShouhinNM";
    }elseif($_POST["Type"]=="UriNO"){
        //Event会計別<!-- イベントでの客単価を知りたい -->
        $sql = "select '-' as UriDate,UriageNO,Event,'-' as TokuisakiNM,'-' as ShouhinNM,sum(su) as su,0 as tanka,sum(UriageKin) as UriageKin,sum(zei) as zei from UriageData ".$wheresql." group by Event,UriageNO order by Event,UriageNO";
    }elseif($_POST["Type"]=="EVTKshubetu"){
        //顧客/Event別・種類別<!-- 顧客・イベントでの売れ筋を知りたい -->
        $sql = "select '-' as UriDate,'-' as UriageNO,Event,TokuisakiNM,ShouhinNM,sum(su) as su,tanka,sum(UriageKin) as UriageKin,sum(zei) as zei from UriageData ".$wheresql." group by Event,TokuisakiNM,ShouhinNM,tanka order by Event,TokuisakiNM,ShouhinNM";
    }else{
        echo "そんな！";
    }
    //echo $sql;
    $stmt = $pdo_h->prepare( $sql );
    if($_POST["UriDate"]<>""){
        $stmt->bindValue("UriDate", $_POST["UriDate"], PDO::PARAM_STR);
    }
    if($_POST["UriDateTo"]<>""){
        $stmt->bindValue("UriDateTo", $_POST["UriDateTo"], PDO::PARAM_STR);
    }else{
        $stmt->bindValue("UriDateTo", $UriFrom, PDO::PARAM_STR);
    }
    if($_POST["Event"]<>""){
        $stmt->bindValue("Event", $_POST["Event"], PDO::PARAM_STR);
    }
    if($_POST["Tokui"]<>""){
        $stmt->bindValue("Tokui", $_POST["Tokui"], PDO::PARAM_STR);
    }
}
$stmt->bindValue("user_id", $_SESSION["user_id"], PDO::PARAM_INT);
$stmt->execute();


//Eventリスト
$EVsql = "select Event from UriageData where uid =? group by Event order by Event";
$EVresult = $pdo_h->prepare($EVsql);
$EVresult->bindValue(1, $_SESSION['user_id'], PDO::PARAM_INT);
$EVresult->execute();

//顧客リスト
$TKsql = "select TokuisakiNM from UriageData where uid =? group by TokuisakiNM order by TokuisakiNM";
$TKresult = $pdo_h->prepare($TKsql);
$TKresult->bindValue(1, $_SESSION['user_id'], PDO::PARAM_INT);
$TKresult->execute();

?>
<head>
    <?php 
    //共通部分、bootstrap設定、フォントCND、ファビコン等
    include "head.html" 
    ?>
    <!--ページ専用CSS-->
    <link rel="stylesheet" href="css/style_UriageData.css" >
    <TITLE><?php echo $title." 売上実績";?></TITLE>
</head>
 
<header style="flex-wrap:wrap">
    <div class="title" style="width: 100%;"><a href="menu.php"><?php echo $title;?></a></div>
    <div style="font-size:1rem;"> 期間：<?php echo $UriFrom."～".$UriTo;?>　顧客：<?php echo $_POST["Tokui"];?>　EVENT：<?php echo $_POST["Event"];?></div>

</header>

<body>    
    <div class="container-fluid">
    <?php
    if($mode=="del"){echo secho($msg);}?>
    <table class="table-striped table-bordered">
        <thead><tr><th>売上日</th><th>Event名</th><th>顧客名</th><th>売上№</th><th>商品</th><th style="width:3rem;">個数</th><th style="width:3rem;">単価</th><th style="width:5rem;">売上</th><th style="width:4rem;">消費税</th></tr></thead>
<?php    
$Goukei=0;
$GoukeiZei=0;
foreach($stmt as $row){
    echo "<tr><td>".$row["UriDate"]."</td><td>".$row["Event"]."</td><td>".$row["TokuisakiNM"]."</td><td class='text-center'>".$row["UriageNO"]."</td><td>".rot13decrypt($row["ShouhinNM"])."</td><td class='text-right'>".$row["su"]."</td><td class='text-right'>".$row["tanka"]."</td><td class='text-right'>".$row["UriageKin"]."</td><td class='text-right'>".$row["zei"]."</td><td style='width:4rem;text-align:center;'><a href='UriageData.php?cd=".$row["ShouhinCD"]."&urino=".$row["UriageNO"]."&csrf_token=".$csrf_create."'><i class='fa-regular fa-trash-can'></i></a></td></tr>\n";
    $Goukei = $Goukei + $row["UriageKin"];
    $GoukeiZei = $GoukeiZei + $row["zei"];
}
?>
    </table>
<?php
if($mode=="del"){
?>
    <br>
    <form method="post" action="UriageData.php">
        <input type="hidden" name="mode" value="del">
        <input type="hidden" name="urino" value=<?php echo secho($_GET["urino"]);?>>
        <input type="hidden" name="cd" value=<?php echo secho($_GET["cd"]);?>>
        <input type="submit" class="btn btn-primary" style="width:5rem;padding:0;" value="削除">
    </form>
    </div>
    
<?php
}
?>
</body>

<footer>
    <div class='kaikei'>
        合計(税抜)：￥<?php echo $Goukei ?>-<br>
        税：￥<?php echo $GoukeiZei ?>-
    </div>
    <div class="right1">
        <button type='button' class='btn btn--chk' style="border-radius:0;" id='dentaku' data-toggle="modal" data-target="#UriModal">検　索</button>
    </div>

</footer>

<!--売上実績検索条件-->
<div class="modal fade" id="UriModal" tabindex="-1" role="dialog" aria-labelledby="basicModal" aria-hidden="true">
    <div class="modal-dialog  modal-dialog-centered">
        <div class="modal-content" style="font-size: 1.0rem; font-weight: 600;">
            
            <form class="form-horizontal" method="post" action="UriageData.php">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_create; ?>">
                <div class="modal-header">
                    <div class="modal-title" id="myModalLabel">表示条件変更</div>
                </div>
                <div class="modal-body">
                    <div>
                        <label for="uridate" class="control-label">売上日～：</label>
                        <input type="date" name="UriDate" maxlength="10" id="uridate" class="form-control" value="<?php echo $UriFrom; ?>">
                    </div>
                    <div>
                        <label for="uridateto" class="control-label">～売上日：</label>
                        <input type="date" name="UriDateTo" maxlength="10" id="uridateto" class="form-control" value="<?php echo $UriTo; ?>">
                    </div>
                    <div>
                        <label for="Event" class="control-label">イベント名：</label>
                        <select name="Event" id="Event" class="form-control">
                            <option value=""></option>
                            <?php
                            //while($row = $EVresult->fetch_assoc()){
                            foreach($EVresult as $row){
                                if($_POST["Event"]==$row["Event"]){
                                    echo "<option value='".$row["Event"]."' selected>".$row["Event"]."</option>\n";
                                }else{
                                    echo "<option value='".$row["Event"]."'>".$row["Event"]."</option>\n";
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div>
                        <label for="Tokui" class="control-label">得意先：</label>
                        <select name="Tokui" id="Tokui" class="form-control">
                            <option value=""></option>
                            <?php
                            //while($row = $TKresult->fetch_assoc()){
                            foreach($TKresult as $row){
                                 if($_POST["Tokui"]==$row["TokuisakiNM"]){
                                    echo "<option value='".$row["TokuisakiNM"]."' selected>".$row["TokuisakiNM"]."</option>\n";
                                }else{
                                    echo "<option value='".$row["TokuisakiNM"]."'>".$row["TokuisakiNM"]."</option>\n";
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div>
                        <label for="Type" class="control-label">表示：</label>
                        <select name="Type" id="Type" class="form-control">
                            <option value="rireki" <?php if($_POST["Type"]=="rireki"){echo "selected";}  ?> >履歴</option>
                            <option value="shubetu" <?php if($_POST["Type"]=="shubetu"){echo "selected";}  ?> >種類別</option>     <!-- 何が売れてるか知りたい -->
                            <option value="UriNO" <?php if($_POST["Type"]=="UriNO"){echo "selected";}  ?> >Event会計別</option>  <!-- イベントでの客単価を知りたい -->
                            <option value="EVTKshubetu" <?php if($_POST["Type"]=="EVTKshubetu"){echo "selected";}  ?> >顧客/Event別・種類別</option> <!-- 顧客・イベントでの売れ筋を知りたい -->
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary" >決定</button>
                </div>
            </form>
        </div>
    </div>
</div>



</html>
<?php
$EVresult  = null;
$TKresult = null;
$stmt = null;
$pdo_h = null;
?>
