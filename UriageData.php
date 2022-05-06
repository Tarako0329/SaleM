<!DOCTYPE html>
<html lang="ja">
<?php
require "php_header.php";
if($_GET["mode"]=="redirect"){
    if(csrf_chk_redirect($_GET["csrf_token"])==false){
        $_SESSION["EMSG"]="セッションが正しくありませんでした。③";
        header("HTTP/1.1 301 Moved Permanently");
        header("Location: index.php");
        exit();
    }

}elseif(isset($_GET["csrf_token"]) || empty($_POST)){
    if(csrf_chk_nonsession_get($_GET["csrf_token"])==false){
        $_SESSION["EMSG"]="セッションが正しくありませんでした。①";
        header("HTTP/1.1 301 Moved Permanently");
        header("Location: index.php");
        exit();
    }
}elseif(csrf_chk()==false){
    $_SESSION["EMSG"]="セッションが正しくありませんでした②";
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: index.php");
    exit();
}

$rtn=check_session_userid($pdo_h);
$csrf_create = csrf_create();

//deb_echo("UID：".$_SESSION["user_id"]);

$msg = "";

if($_GET["mode"]<>""){
    $mode=$_GET["mode"];
}elseif($_POST["mode"]<>""){
    $mode=$_POST["mode"];
}else{
    $_SESSION["EMSG"]="セッションが正しくありませんでした：モード指定なし";
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: index.php");
    exit();
}


if($mode=="del"){
    //削除モード(確認)
    $btnm = "削　除";
    $msg ="この売上を削除しますか？<br>";
    $_SESSION["cd"]=$_GET["cd"];
    $_SESSION["urino"]=$_GET["urino"];
    $sql = "select * from UriageData where uid = :user_id and UriageNO = :UriNO and ShouhinCD = :ShouhinCD order by UriageNO";
    $stmt = $pdo_h->prepare( $sql );
    $stmt->bindValue("UriNO", $_GET["urino"], PDO::PARAM_INT);
    $stmt->bindValue("ShouhinCD", $_GET["cd"], PDO::PARAM_INT);
    deb_echo($_SESSION["wheresql"]);
    
}elseif($mode=="UpdateEv" || $mode=="UpdateTk"){
    $btnm = "更　新";
    if($mode=="UpdateEv"){
        $msg = "この売上のイベント名を『".$_POST["UpNM"]."』に変更しますか？<br>";
    }else{
        $msg = "この売上の顧客名を『".$_POST["UpNM"]."』に変更しますか？<br>";
    }
    $_SESSION["UpNM"] = $_POST["UpNM"];
    if($_POST["UpUriDateFrom"]<>""){
        if($_POST["UpUriDateTo"]<>""){
            $UpUriDateTo=$_POST["UpUriDateTo"];
        }else{
            $UpUriDateTo=$_POST["UpUriDateFrom"];
        }
        $sql = "select * from UriageData where uid = :user_id and UriDate between :From and :To order by UriageNO";
        $_SESSION["wheresql"] = " where uid = :user_id and UriDate between '".$_POST["UpUriDateFrom"]."' and '".$UpUriDateTo."' ";
        
        $stmt = $pdo_h->prepare( $sql );
        $stmt->bindValue("From", $_POST["UpUriDateFrom"], PDO::PARAM_STR);
        $stmt->bindValue("To", $UpUriDateTo, PDO::PARAM_STR);
    }elseif($_POST["UpUriNoFrom"]<>""){
        $UpUriNoFrom=$_POST["UpUriNoFrom"];
        if($_POST["UpUriNoTo"]<>""){
            $UpUriNoTo=$_POST["UpUriNoTo"];
        }else{
            $UpUriNoTo=$_POST["UpUriNoFrom"];
        }
        $sql = "select * from UriageData where uid = :user_id and UriageNo between :From and :To order by UriageNO";
        $_SESSION["wheresql"] = " where uid = :user_id and UriageNo between '".$UpUriNoFrom."' and '".$UpUriNoTo."' ";

        $stmt = $pdo_h->prepare( $sql );
        $stmt->bindValue("From", $UpUriNoFrom, PDO::PARAM_INT);
        $stmt->bindValue("To", $UpUriNoTo, PDO::PARAM_INT);
    }elseif($_POST["UpUriNo01"]<>""){
        if($_POST["UpUriNo01"]<>""){$UpUriNo01=$_POST["UpUriNo01"];}else{$UpUriNo01=0;}
        if($_POST["UpUriNo02"]<>""){$UpUriNo02=$_POST["UpUriNo02"];}else{$UpUriNo02=0;}
        if($_POST["UpUriNo03"]<>""){$UpUriNo03=$_POST["UpUriNo03"];}else{$UpUriNo03=0;}
        if($_POST["UpUriNo04"]<>""){$UpUriNo04=$_POST["UpUriNo04"];}else{$UpUriNo04=0;}
        if($_POST["UpUriNo05"]<>""){$UpUriNo05=$_POST["UpUriNo05"];}else{$UpUriNo05=0;}
        if($_POST["UpUriNo06"]<>""){$UpUriNo06=$_POST["UpUriNo06"];}else{$UpUriNo06=0;}
        if($_POST["UpUriNo07"]<>""){$UpUriNo07=$_POST["UpUriNo07"];}else{$UpUriNo07=0;}
        if($_POST["UpUriNo08"]<>""){$UpUriNo08=$_POST["UpUriNo08"];}else{$UpUriNo08=0;}
        if($_POST["UpUriNo09"]<>""){$UpUriNo09=$_POST["UpUriNo09"];}else{$UpUriNo09=0;}
        if($_POST["UpUriNo10"]<>""){$UpUriNo10=$_POST["UpUriNo10"];}else{$UpUriNo10=0;}
        $sql = "select * from UriageData where uid = :user_id and UriageNo in (:uri01,:uri02,:uri03,:uri04,:uri05,:uri06,:uri07,:uri08,:uri09,:uri10) order by UriageNO";
        $_SESSION["wheresql"] = " where uid = :user_id and UriageNo in (".$UpUriNo01.",".$UpUriNo02.",".$UpUriNo03.",".$UpUriNo04.",".$UpUriNo05.",".$UpUriNo06.",".$UpUriNo07.",".$UpUriNo08.",".$UpUriNo09.",".$UpUriNo10.") ";

        $stmt = $pdo_h->prepare( $sql );
        $stmt->bindValue("uri01", $UpUriNo01, PDO::PARAM_INT);
        $stmt->bindValue("uri02", $UpUriNo02, PDO::PARAM_INT);
        $stmt->bindValue("uri03", $UpUriNo03, PDO::PARAM_INT);
        $stmt->bindValue("uri04", $UpUriNo04, PDO::PARAM_INT);
        $stmt->bindValue("uri05", $UpUriNo05, PDO::PARAM_INT);
        $stmt->bindValue("uri06", $UpUriNo06, PDO::PARAM_INT);
        $stmt->bindValue("uri07", $UpUriNo07, PDO::PARAM_INT);
        $stmt->bindValue("uri08", $UpUriNo08, PDO::PARAM_INT);
        $stmt->bindValue("uri09", $UpUriNo09, PDO::PARAM_INT);
        $stmt->bindValue("uri10", $UpUriNo10, PDO::PARAM_INT);
    }

}elseif($mode=="UpdateKin"){
    $btnm = "更　新";
    $msg = "この商品の売上単価を以下に変更しますか？<br>税抜単価：".$_POST["UpTanka"]." 消費税：".$_POST["UpZei"]." 税込単価：".$_POST["UpUriZei"]." <br>";
    //売上実績の取得
    
    $_SESSION["zeinukiTanka"] = $_POST["UpTanka"];
    $_SESSION["shouhizei"] = $_POST["UpZei"];
    $_SESSION["zeikbn"] = $_POST["Upzeikbn"];

    $wheresql="where ShouhinCD = :ShouhinCD ";
    $_SESSION["wheresql"]="where ShouhinCD = ".$_POST["UpShouhinCD"];

    if($_POST["UpUriDateFrom"]<>""){
        $wheresql= $wheresql." AND UriDate >= :UriDate ";
        $UriFrom = (string)$_POST["UpUriDateFrom"];
        $_SESSION["wheresql"]=$_SESSION["wheresql"]." AND UriDate >= '".$UriFrom."' ";
    }

    if($_POST["UpUriDateTo"]<>""){
        $UriTo = (string)$_POST["UpUriDateTo"];
        $wheresql=$wheresql." AND UriDate <= :UriDateTo ";
        $_SESSION["wheresql"]=$_SESSION["wheresql"]." AND UriDate <= '".$UriTo."' ";
    }

    if($_POST["UpEvent"]<>""){
        $wheresql = $wheresql." AND Event=:Event ";
        $_SESSION["wheresql"]=$_SESSION["wheresql"]." AND Event='".$_POST["UpEvent"]."' ";
    }
    if($_POST["UpTokui"]<>""){
        $wheresql= $wheresql." AND TokuisakiNM=:Tokui ";
        $_SESSION["wheresql"]=$_SESSION["wheresql"]." AND TokuisakiNM='".$_POST["UpTokui"]."' ";
    }
    $wheresql= $wheresql." AND uid = :user_id ";
    $_SESSION["wheresql"]=$_SESSION["wheresql"]." AND uid = :user_id ";
    
    $sql = "select * from UriageData ".$wheresql." order by UriageNO";

    $stmt = $pdo_h->prepare( $sql );
    $stmt->bindValue("ShouhinCD", $_POST["UpShouhinCD"], PDO::PARAM_INT);
    
    if($_POST["UpUriDateFrom"]<>""){
        $stmt->bindValue("UriDate", $_POST["UpUriDateFrom"], PDO::PARAM_STR);
    }
    if($_POST["UpUriDateTo"]<>""){
        $stmt->bindValue("UriDateTo", $_POST["UpUriDateTo"], PDO::PARAM_STR);
    }
    if($_POST["UpEvent"]<>""){
        $stmt->bindValue("Event", $_POST["UpEvent"], PDO::PARAM_STR);
    }
    if($_POST["UpTokui"]<>""){
        $stmt->bindValue("Tokui", $_POST["UpTokui"], PDO::PARAM_STR);
    }
}elseif($mode=="select"){
    //売上実績の取得
    if($_POST["UriDate"]<>""){
        $wheresql="where UriDate >= :UriDate ";
        $UriFrom = (string)$_POST["UriDate"];
        $_SESSION["wheresql"]="where UriDate >= '".$UriFrom."' ";
    }else{
        $wheresql="where UriDate >= '".(string)date("Y-m-d")."'";
        $UriFrom = (string)date("Y-m-d");
        $_SESSION["wheresql"]=$wheresql;
    }
    
    $wheresql=$wheresql." AND UriDate <= :UriDateTo ";
    if($_POST["UriDateTo"]<>""){
        $UriTo = (string)$_POST["UriDateTo"];
    }else{
        $UriTo = $UriFrom;
    }
    $_SESSION["wheresql"]=$_SESSION["wheresql"]." AND UriDate <= '".$UriTo."' ";
    
    if($_POST["Event"]<>""){
        $wheresql = $wheresql." AND Event=:Event ";
        $_SESSION["wheresql"]=$_SESSION["wheresql"]." AND Event='".$_POST["Event"]."' ";
    }
    if($_POST["Tokui"]<>""){
        $wheresql= $wheresql." AND TokuisakiNM=:Tokui ";
        $_SESSION["wheresql"]=$_SESSION["wheresql"]." AND TokuisakiNM='".$_POST["Tokui"]."' ";
    }
    $wheresql= $wheresql." AND uid = :user_id ";
    $_SESSION["wheresql"]=$_SESSION["wheresql"]." AND uid = :user_id ";
    
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
}elseif($mode=="redirect"){
    //更新結果の表示
    //$msg = $_SESSION["MSG"];
    //$_SESSION["MSG"]="";
    deb_echo($_SESSION["wheresql"]);
    $sql = "select * from UriageData ".$_SESSION["wheresql"]." order by UriageNO";
    $stmt = $pdo_h->prepare( $sql );
    //更新後は破棄
    //$_SESSION["wheresql"]="";
}else{
    echo "想定外エラー";
    exit();
}
$stmt->bindValue("user_id", $_SESSION["user_id"], PDO::PARAM_INT);
$rtn=$stmt->execute();
if($rtn==false){
    deb_echo("失敗した場合は不正値が渡されたとみなし、wheresqlを破棄<br>");
    $_SESSION["wheresql"]="";
}
$result=$stmt->fetchAll();

//Eventリスト（検索モーダル用）
$EVsql = "select Event from UriageData where uid =? group by Event order by Event";
$stmt = $pdo_h->prepare($EVsql);
$stmt->bindValue(1, $_SESSION['user_id'], PDO::PARAM_INT);
$stmt->execute();
$EVresult = $stmt->fetchAll();

//顧客リスト（検索モーダル用）
$TKsql = "select TokuisakiNM from UriageData where uid =? group by TokuisakiNM order by TokuisakiNM";
$stmt = $pdo_h->prepare($TKsql);
$stmt->bindValue(1, $_SESSION['user_id'], PDO::PARAM_INT);
$stmt->execute();
$TKresult = $stmt->fetchAll();

//売上実績商品リスト（修正モーダル用）
$SHsql = "select ShouhinCD,ShouhinNM from UriageData where uid =? group by ShouhinCD,ShouhinNM order by ShouhinCD,ShouhinNM";
$stmt = $pdo_h->prepare($SHsql);
$stmt->bindValue(1, $_SESSION['user_id'], PDO::PARAM_INT);
$stmt->execute();
$SHresult = $stmt->fetchAll();

//税区分M
$ZEIsql="select * from ZeiMS order by zeiKBN;";
$ZEIresult = $pdo_h->query($ZEIsql);















?>
<head>
    <?php 
    //共通部分、bootstrap設定、フォントCND、ファビコン等
    include "head.html" 
    ?>
    <!--ページ専用CSS-->
    <link rel="stylesheet" href="css/style_UriageData.css?<?php echo $time; ?>" >
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
    <script>
    window.onload = function() {
        //アラート用
        function alert(msg) {
          return $('<div class="alert" role="alert"></div>')
            .text(msg);
        }
        (function($){
          const e = alert('<?php echo $_SESSION["MSG"]; ?>').addClass('alert-success');
          // アラートを表示する
          $('#alert-1').append(e);
          /* 2秒後にアラートを消す
          setTimeout(() => {
            e.alert('close');
          }, 3000);
          */
        })(jQuery);
    };
    $(function() {
        $('.hamburger').click(function() {
            $(this).toggleClass('active');
     
            if ($(this).hasClass('active')) {
                $('.globalMenuSp').addClass('active');
            } else {
                $('.globalMenuSp').removeClass('active');
            }
        });
        $('.globalMenuSp').click(function() {
            $('.hamburger').toggleClass('active');
            $('.globalMenuSp').removeClass('active');
        });
    });
    </script>    
    <TITLE><?php echo $title." 売上実績";?></TITLE>
</head>
 
<header class="header-color" style="flex-wrap:wrap">
    <div class="title" style="width: 100%;"><a href="menu.php"><?php echo $title;?></a></div>
    <div style="font-size:1rem;color:var(--user-disp-color);font-weight:400;"> 期間：<?php echo $UriFrom."～".$UriTo;?>　顧客：<?php echo $_POST["Tokui"];?>　EVENT：<?php echo $_POST["Event"];?></div>

    <div class="hamburger">
        <span></span>
        <span></span>
        <span></span>
    </div>
    
    <nav class="globalMenuSp" style="font-size:2rem;">
        <ul>
            <li><a style="font-weight:900">≪一括修正ﾒﾆｭｰ≫</a></li>
            <li><a data-toggle="modal" data-target="#UriUpEvModal" onclick="modechange_EV()">イベント名</a></li>
            <li><a data-toggle="modal" data-target="#UriUpEvModal" onclick="modechange_TK()">顧客名</a></li>
            <li><a data-toggle="modal" data-target="#UriUpKinModal">金額</a></li>
        </ul>
    </nav>

</header>

<body>
    <div class="container-fluid">
    <?php
        if($_SESSION["MSG"]!=""){
            echo "<div class='container'><div class='row'><div class='col-12'><div style='padding-top:5px;text-align:center;font-size:1.5rem;' id='alert-1' class='lead'></div></div></div></div>";
        }
        $_SESSION["MSG"]="";
        echo "<p style='font-size:1.5rem'>".$msg."</p>\n";
        
    ?>
    <table class="table-striped table-bordered" style='margin-top:10px'>
        <thead><tr><th scope='col'>売上日</th><th scope='col'>Event/顧客</th><th scope='col' class='d-none d-sm-table-cell'>売上№</th><th>商品</th><th scope='col' style="width:3rem;">個数</th><th scope='col' style="width:3rem;" class='d-none d-sm-table-cell'>単価</th><th scope='col' style="width:5rem;">売上</th><th scope='col' style="width:4rem;">消費税</th><th scope='col' style="width:auto;">削除</th></tr></thead>
<?php    
$Goukei=0;
$GoukeiZei=0;
$GoukeiZeikomi=0;
foreach($result as $row){
    echo "<tr><td>".$row["UriDate"]."</td><td>".$row["Event"].$row["TokuisakiNM"]."</td><td class='text-center d-none d-sm-table-cell'>".$row["UriageNO"]."</td><td>".rot13decrypt($row["ShouhinNM"])."</td><td class='text-right'>".$row["su"]."</td><td class='text-right d-none d-sm-table-cell'>".$row["tanka"]."</td><td class='text-right'>".$row["UriageKin"]."</td><td class='text-right'>".$row["zei"]."</td><td style='width:4rem;text-align:center;'>";
    if(($_POST["Type"]=="rireki" || $_POST["Type"]=="") && ($mode == "select" || $mode=="redirect")){
        //履歴表示の時だけ削除可能
        echo "<a href='UriageData.php?cd=".$row["ShouhinCD"]."&urino=".$row["UriageNO"]."&csrf_token=".$csrf_create."&mode=del'><i class='fa-regular fa-trash-can'></i></a>";
    }
    echo "</td></tr>\n";
    $Goukei = $Goukei + $row["UriageKin"];
    $GoukeiZei = $GoukeiZei + $row["zei"];
    $GoukeiZeikomi = $GoukeiZeikomi + $row["UriageKin"] + $row["zei"];
}
?>
    </table>
<?php
if($mode<>"select" && $mode<>"redirect"){
?>
    <br>
    <form method="post" action="UriageData_sql.php">
        <input type="hidden" name="mode" value="<?php echo $mode;?>">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf_create; ?>">
        <button type="submit" class="btn btn-primary"><?php echo $btnm;?></button>
    </form>
    </div>
    
<?php
}
?>
<br>
</body>

<footer>
    <div class='kaikei'>
        合計(税込)：￥<?php echo $GoukeiZeikomi ?>-<br>
        <span style="font-size:1.3rem;">内訳(本体+税)：￥<?php echo $Goukei." + ".$GoukeiZei ?></span>
    </div>
    <div class="right1">
        <button type='button' class='btn--chk' style="border-radius:0;" data-toggle="modal" data-target="#UriModal">検　索</button>
    </div>

</footer>

<!--売上実績検索条件-->
<div class="modal fade" id="UriModal" tabindex="-1" role="dialog" aria-labelledby="basicModal" aria-hidden="true">
    <div class="modal-dialog  modal-dialog-centered">
        <div class="modal-content" style="font-size:1.5rem; font-weight: 600;background-color:rgba(255,255,255,0.55);">
            
            <form class="form-horizontal" method="post" action="UriageData.php">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_create; ?>">
                <input type="hidden" name="mode" value="select">
                <div class="modal-header">
                    <div class="modal-title" id="myModalLabel">表示条件変更</div>
                </div>
                <div class="modal-body">
                    <div>
                        <label for="uridate" class="control-label">売上日～：</label>
                        <input type="date" style="font-size:1.5rem;" name="UriDate" maxlength="10" id="uridate" class="form-control" value="<?php echo $UriFrom; ?>">
                    </div>
                    <div>
                        <label for="uridateto" class="control-label">～売上日：</label>
                        <input type="date" style="font-size:1.5rem;" name="UriDateTo" maxlength="10" id="uridateto" class="form-control" value="<?php echo $UriTo; ?>">
                    </div>
                    <div>
                        <label for="Event" class="control-label">イベント名：</label>
                        <select name="Event" style="font-size:1.5rem;padding-top:0;" id="Event" class="form-control">
                            <option value=""></option>
                            <?php
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
                        <select name="Tokui" style="font-size:1.5rem;padding-top:0;" id="Tokui" class="form-control">
                            <option value=""></option>
                            <?php
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
                        <select name="Type" style="font-size:1.5rem;padding-top:0;" id="Type" class="form-control">
                            <option value="rireki" <?php if($_POST["Type"]=="rireki"){echo "selected";}  ?> >履歴</option>
                            <option value="shubetu" <?php if($_POST["Type"]=="shubetu"){echo "selected";}  ?> >種類別</option>     <!-- 何が売れてるか知りたい -->
                            <option value="UriNO" <?php if($_POST["Type"]=="UriNO"){echo "selected";}  ?> >Event会計別</option>  <!-- イベントでの客単価を知りたい -->
                            <option value="EVTKshubetu" <?php if($_POST["Type"]=="EVTKshubetu"){echo "selected";}  ?> >顧客/Event別・種類別</option> <!-- 顧客・イベントでの売れ筋を知りたい -->
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" style="font-size:1.5rem;color:#fff" class="btn btn-primary" >決　定</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!--売上修正・イベント名・顧客名共通・Javascriptでmodeと表示のみ変更-->
<!--<div class="modal fade" id="UriUpEvModal" tabindex="-1" role="dialog" aria-labelledby="basicModal" aria-hidden="true"> modal-dialog-centered-->
<div class="modal fade" id="UriUpEvModal" tabindex="-1" role="dialog" aria-labelledby="basicModal" aria-hidden="true">
    <div class="modal-dialog  ">
        <div class="modal-content" style="font-size:1.5rem; font-weight: 600;background-color:rgba(255,255,255,0.55);">
            
            <form class="form-horizontal" method="post" action="UriageData.php">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_create; ?>">
                <input type="hidden" name="mode" value="UpdateEv" id="mode">
                <div class="modal-header">
                    <div class="modal-title" id="myModalLabel">修正対象指定</div>
                </div>
                <div class="modal-body">
                    <div>
                        <table>
                        <tr><td>売上日範囲指定</td></tr>
                        <tr>
                            <td><input type="date" style="font-size:1.5rem;width:90%;" name="UpUriDateFrom" maxlength="10" class="form-control" id="M_EV01" value="<?php echo $UriFrom; ?>" onchange="del1()"></td><td>～</td>
                            <td><input type="date" style="font-size:1.5rem;width:90%;" name="UpUriDateTo" maxlength="10" class="form-control" id="M_EV02" value="<?php echo $UriTo; ?>" onchange="del1()"></td>
                        </tr>
                        </table>
                    <div>
                        <table>
                        <tr><td>売上No範囲指定</td></tr>
                        <tr><td><input type="number" style="font-size:1.5rem;width:90%;" name="UpUriNoFrom" maxlength="10" class="form-control" id="M_EV11" onchange="del2()"></td><td>～</td>
                        <td><input type="number" style="font-size:1.5rem;width:90%;" name="UpUriNoTo" maxlength="10" class="form-control" id="M_EV12" onchange="del2()"></td></tr>
                        </table>
                    </div>
                    <div>
                        <table>
                        <tr><td colspan="4">売上No複数指定</td></tr>
                        <tr>
                            <td><input type="number" style="font-size:1.5rem;width:90%;" name="UpUriNo01" maxlength="10" class="form-control" id="M_EV20" onchange="del3()"></td>
                            <td><input type="number" style="font-size:1.5rem;width:90%;" name="UpUriNo02" maxlength="10" class="form-control" id="M_EV21" onchange="del3()"></td>
                            <td><input type="number" style="font-size:1.5rem;width:90%;" name="UpUriNo03" maxlength="10" class="form-control" id="M_EV22" onchange="del3()"></td>
                            <td><input type="number" style="font-size:1.5rem;width:90%;" name="UpUriNo04" maxlength="10" class="form-control" id="M_EV23" onchange="del3()"></td>
                            <td><input type="number" style="font-size:1.5rem;width:90%;" name="UpUriNo05" maxlength="10" class="form-control" id="M_EV24" onchange="del3()"></td>
                        </tr>
                        <tr>
                            <td><input type="number" style="font-size:1.5rem;width:90%;" name="UpUriNo06" maxlength="10" class="form-control" id="M_EV25" onchange="del3()"></td>
                            <td><input type="number" style="font-size:1.5rem;width:90%;" name="UpUriNo07" maxlength="10" class="form-control" id="M_EV26" onchange="del3()"></td>
                            <td><input type="number" style="font-size:1.5rem;width:90%;" name="UpUriNo08" maxlength="10" class="form-control" id="M_EV27" onchange="del3()"></td>
                            <td><input type="number" style="font-size:1.5rem;width:90%;" name="UpUriNo09" maxlength="10" class="form-control" id="M_EV28" onchange="del3()"></td>
                            <td><input type="number" style="font-size:1.5rem;width:90%;" name="UpUriNo10" maxlength="10" class="form-control" id="M_EV29" onchange="del3()"></td>
                        </tr>
                        </table>
                    </div>
                    <hr>
                    <div>
                        <label for="upevent" class="control-label" id="lbl">修正後イベント名：</label>
                        <input type="text" style="font-size:1.5rem;" name="UpNM" maxlength="10" id="upevent" class="form-control" required="required">
                    </div>
                </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" style="font-size:1.5rem;color:#fff" class="btn btn-primary" >確　認</button>
                </div>
            </form>
        </div>
    </div>
</div>


<!--売上修正・金額-->
<!--<div class="modal fade" id="UriUpEvModal" tabindex="-1" role="dialog" aria-labelledby="basicModal" aria-hidden="true"> modal-dialog-centered-->
<div class="modal fade" id="UriUpKinModal" tabindex="-1" role="dialog" aria-labelledby="basicModal" aria-hidden="true">
    <div class="modal-dialog  ">
        <div class="modal-content" style="font-size:1.5rem; font-weight: 600;background-color:rgba(255,255,255,0.55);">
            
            <form class="form-horizontal" method="post" action="UriageData.php">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_create; ?>">
                <input type="hidden" name="mode" value="UpdateKin">
                <div class="modal-header">
                    <div class="modal-title" id="myModalLabel">修正対象指定</div>
                </div>
                <div class="modal-body">
                    <div>
                        <label for="ShouhinCD" class="control-label">修正対象商品名：</label>
                        <select name="UpShouhinCD" style="font-size:1.5rem;padding-top:0;" id="ShouhinCD" class="form-control" required="required" placeholder="必須">
                            <option value=""></option>
                            <?php
                            foreach($SHresult as $row){
                                echo "<option value='".$row["ShouhinCD"]."'>商品ID(".$row["ShouhinCD"].")：".rot13decrypt($row["ShouhinNM"])."</option>\n";
                            }
                            ?>
                        </select>
                    </div>
                    <div>
                        <table>
                        <tr><td>売上日範囲指定</td></tr>
                        <tr>
                            <td><input type="date" style="font-size:1.5rem;width:90%;" name="UpUriDateFrom" maxlength="10" class="form-control" value="<?php echo $UriFrom; ?>"></td><td>～</td>
                            <td><input type="date" style="font-size:1.5rem;width:90%;" name="UpUriDateTo" maxlength="10" class="form-control" value="<?php echo $UriTo; ?>"></td>
                        </tr>
                        </table>
                    </div>
                    <div>
                        <label for="Event" class="control-label">イベント名：</label>
                        <select name="UpEvent" style="font-size:1.5rem;padding-top:0;" id="Event" class="form-control">
                            <option value=""></option>
                            <?php
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
                        <select name="UpTokui" style="font-size:1.5rem;padding-top:0;" id="Tokui" class="form-control">
                            <option value=""></option>
                            <?php
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
                    <hr>
                    <div>
                        <table>
                        <tr><td>修正後金額</td>
                        <td colspan="4">
                                <div class="btn-group btn-group-toggle" style="font-size:1rem;" data-toggle="buttons">
                                    <label class="btn btn-primary active" style="padding:1px;" onchange="zei_math()" >
                                        <input type="radio" name="options" id="option1" value="zeikomi" autocomplete="off" checked> 税込み
                                    </label>
                                    <label class="btn btn-primary" style="padding:1px;" onchange="zei_math()" >
                                        <input type="radio" name="options" id="option2" value="zeinuki" autocomplete="off"> 税抜き
                                    </label>
                                    
                                </div>
                                <div style="font-size:0.75rem;">※単価変更欄に入力する金額</div>
                        </td></tr>
                        <tr><td style="width:9rem">単価変更</td><td style="width:9.5rem" >税区分</td><td style="width:9rem">税抜単価</td><td style="width:7rem">消費税</td><td style="width:9rem">税込単価</td></tr>
                        <tr>
                            <td><input type="number" style="font-size:1.5rem;width:90%;" name="UpUriTanka" id="UpUriTanka" maxlength="10" class="form-control" required="required" placeholder="必須" onchange="zei_math()" ></td>
                            <td>
                                <select class="form-control" style="padding-top:0;" id="zeikbn" name="Upzeikbn" required="required" placeholder="必須" onchange="zei_math()" >
                                    <option value=""></option>
                                    <?php
                                    foreach($ZEIresult as $row){
                                        echo "<option value=".secho($row["zeiKBN"]).">".secho($row["hyoujimei"])."</option>\n";
                                    }
                                    ?>
                                </select>
                            </td>
                            <td><input type="number" required="required" style="font-size:1.5rem;width:90%;" name="UpTanka" id="UpTanka" maxlength="10" class="form-control" ></td>
                            <td><input type="number" required="required" style="font-size:1.5rem;width:90%;" name="UpZei" id="UpZei" maxlength="10" class="form-control" ></td>
                            <td><input type="number" required="required" style="font-size:1.5rem;width:90%;" name="UpUriZei" id="UpUriZei" maxlength="10" class="form-control" ></td>
                        </tr>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" style="font-size:1.5rem;color:#fff" class="btn btn-primary" >確　認</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script type="text/javascript" language="javascript">
    var select = document.getElementById('zeikbn');
    var tanka = document.getElementById('UpUriTanka');
    var UpTanka = document.getElementById('UpTanka');
    var shouhizei = document.getElementById('UpZei');
    var zkomitanka = document.getElementById('UpUriZei');
    var kominuki = document.getElementsByName('options')
    var zei_math = function(){
        if(select.value=='0'){
            zkomitanka.value=tanka.value;
            shouhizei.value=0;
            UpTanka.value = tanka.value;
        }else if(kominuki[0].checked){//税込
            switch(select.value){
                case '1001':
                    zkomitanka.value=tanka.value * 1;
                    shouhizei.value=tanka.value - Math.round(tanka.value / (1 + 8 / 100));
                    UpTanka.value = Math.round(tanka.value / (1 + 8 / 100));
                    break;
                case '1101':
                    zkomitanka.value=tanka.value * 1;
                    shouhizei.value=tanka.value - Math.round(tanka.value / (1 + 10 / 100));
                    UpTanka.value = Math.round(tanka.value / (1 + 10 / 100));
                    break;
            }
        }else if(kominuki[1].checked){//税抜
            switch(select.value){
                case '1001':
                    zkomitanka.value=Math.round(tanka.value * (1 + 8 / 100));
                    shouhizei.value=Math.round(tanka.value * (8 / 100));
                    UpTanka.value = tanka.value;
                    break;
                case '1101':
                    zkomitanka.value=Math.round(tanka.value * (1 + 10 / 100));
                    shouhizei.value=Math.round(tanka.value * (10 / 100));
                    UpTanka.value = tanka.value;
                    break;
            }
        }else{
            //
        }
    }



    var M_EV29 = document.getElementById('M_EV29');
    var M_EV28 = document.getElementById('M_EV28');
    var M_EV27 = document.getElementById('M_EV27');
    var M_EV26 = document.getElementById('M_EV26');
    var M_EV25 = document.getElementById('M_EV25');
    var M_EV24 = document.getElementById('M_EV24');
    var M_EV23 = document.getElementById('M_EV23');
    var M_EV22 = document.getElementById('M_EV22');
    var M_EV21 = document.getElementById('M_EV21');
    var M_EV20 = document.getElementById('M_EV20');

    var M_EV11 = document.getElementById('M_EV11');
    var M_EV12 = document.getElementById('M_EV12');

    var M_EV01 = document.getElementById('M_EV01');
    var M_EV02 = document.getElementById('M_EV02');
    function del1(){//日付指定時
        M_EV11.value='';
        M_EV12.value='';
        M_EV29.value='';
        M_EV28.value='';
        M_EV27.value='';
        M_EV26.value='';
        M_EV25.value='';
        M_EV24.value='';
        M_EV23.value='';
        M_EV22.value='';
        M_EV21.value='';
        M_EV20.value='';
    }
    function del2(){//売上NO範囲指定
        M_EV01.value='';
        M_EV02.value='';
        M_EV29.value='';
        M_EV28.value='';
        M_EV27.value='';
        M_EV26.value='';
        M_EV25.value='';
        M_EV24.value='';
        M_EV23.value='';
        M_EV22.value='';
        M_EV21.value='';
        M_EV20.value='';
    }
    function del3(){//売上No複数指定
        M_EV01.value='';
        M_EV02.value='';
        M_EV11.value='';
        M_EV12.value='';
    }
    var mode=document.getElementById('mode');
    var lbl=document.getElementById('lbl');
    function modechange_TK(){
        lbl.innerText='修正後顧客名'
        mode.value='UpdateTk'
    }
    function modechange_EV(){
        lbl.innerText='修正後イベント名';
        mode.value='UpdateEv';
    }
</script>

</html>
<?php
$EVresult  = null;
$TKresult = null;
$stmt = null;
$pdo_h = null;
?>


