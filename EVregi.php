<!DOCTYPE html>
<html lang="ja">
<?php
require "php_header.php";
/*
if(isset($_GET["csrf_token"]) || empty($_POST)){
    if(csrf_chk_nonsession_get($_GET["csrf_token"])==false){
        $_SESSION["EMSG"]="セッションが正しくありませんでした。".$_GET["csrf_token"];
        header("HTTP/1.1 301 Moved Permanently");
        header("Location: index.php");
        exit();
    }
}
*/
$rtn=check_session_userid();
//有効期限チェック
$sql="select yuukoukigen from Users where uid=?";
$stmt = $pdo_h->prepare($sql);
$stmt->bindValue(1, $_SESSION['user_id'], PDO::PARAM_INT);
$stmt->execute();
$row = $stmt->fetchAll(PDO::FETCH_ASSOC);
if($row[0]["yuukoukigen"]==""){
    //本契約済み
}elseif($row[0]["yuukoukigen"] < date("Y-m-d")){
    //お試し期間終了
    echo row[0]["yuukoukigen"] ;    
    $emsg="お試し期間、もしくは解約後有効期間が終了しました。<br>継続してご利用頂ける場合は<a href='../../PAY/index.php?system=".$title."&mode=".MODE_DIR."'>こちらから本契約をお願い致します </a>";
}

//売上登録(F5・更新による2重登録を防ぐため、登録処理をEVregi_sql.phpに分離)
/*
if($_POST["commit_btn"] <> ""){
    if(csrf_chk_nonsession()==false){
        $_SESSION["EMSG"]="セッションが正しくありませんでした。";
        header("HTTP/1.1 301 Moved Permanently");
        header("Location: index.php");
        exit();
    }
    $array = $_POST["ORDERS"];
    $sqlstr = "";

    //売上番号の取得
    $sqlstr = "select max(UriageNO) as UriageNO from UriageData where uid=?";
    $stmt = $pdo_h->prepare($sqlstr);
    $stmt->bindValue(1, $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->execute();
    
    $row = $stmt->fetchAll(PDO::FETCH_ASSOC);
    

    if(is_null($row[0]["UriageNO"])){
        //初回売上時は売上NO[1]をセット
        $UriageNO = 1;
    }else{
        $UriageNO = $row[0]["UriageNO"]+1;
    }
    //echo (string)$UriageNO;
    
    foreach($array as $row){
        if($row["SU"]==0){
            continue;
        }
        $sqlstr = "insert into UriageData values(?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
        $stmt = $pdo_h->prepare($sqlstr);

        $stmt->bindValue(1,  $_SESSION['user_id'], PDO::PARAM_INT);
        $stmt->bindValue(2,  $UriageNO, PDO::PARAM_INT);
        $stmt->bindValue(3,  date("Y/m/d"), PDO::PARAM_STR);
        $stmt->bindValue(4,  date("Y/m/d H:i:s"), PDO::PARAM_STR);
        $stmt->bindValue(5,  $_POST["EV"], PDO::PARAM_INT);
        $stmt->bindValue(6,  '', PDO::PARAM_STR);
        $stmt->bindValue(7,  $row["CD"], PDO::PARAM_INT);
        $stmt->bindValue(8,  $row["NM"], PDO::PARAM_STR);
        $stmt->bindValue(9,  $row["SU"], PDO::PARAM_INT);
        $stmt->bindValue(10, $row["UTISU"], PDO::PARAM_INT);
        $stmt->bindValue(11, $row["TANKA"], PDO::PARAM_INT);
        $stmt->bindValue(12, ($row["SU"] * $row["TANKA"]), PDO::PARAM_INT);
        $stmt->bindValue(13, ($row["SU"] * $row["ZEI"]), PDO::PARAM_INT);
        $stmt->bindValue(14, $row["ZEIKBN"], PDO::PARAM_INT);
        
        
        $flg=$stmt->execute();
        
        if($flg){
        }else{
            echo "登録が失敗しました。<br>";
        }
    }

}
*/

//商品M取得
$sql = "select * from ShouhinMS where hyoujiKBN1='on' and uid = ? order by hyoujiNO,bunrui1,bunrui2,bunrui3,shouhinNM";
$stmt = $pdo_h->prepare($sql);
$stmt->bindValue(1, $_SESSION['user_id'], PDO::PARAM_INT);
$stmt->execute();
$shouhiMS = $stmt->fetchAll();

$token = csrf_create();

//イベント名の取得
if(isset($_POST["EV"])){
    $event = secho($_POST["EV"]);
    if($event<>$_COOKIE["EVENT"]){
        setCookie("EVENT", $event, time()+60*60*24*2, "/", null, TRUE, TRUE);   
    }
}elseif(isset($_COOKIE["EVENT"])){
    $event=$_COOKIE["EVENT"];
}else{
    $event="";
}


?>
<head>
    <?php 
    //共通部分、bootstrap設定、フォントCND、ファビコン等
    include "head.html" 
    ?>
    <!--ページ専用CSS-->
    <link rel="stylesheet" href="css/style_EVregi.css" >
    <TITLE><?php echo $title." レジ";?></TITLE>
</head>

<script>

window.onload = function() {

     // オブジェクトと変数の準備
     
     //合計金額を保持
     var kaikei_disp = document.getElementById("kaikei");
     var zei_disp = document.getElementById("utizei");
     var total_pay = 0;
     var total_zei = 0;
     
     //PHPで繰り返し表示。メニューボタン数に応じて準備する
<?php     
    foreach($shouhiMS as $row){
        echo "\n";
        echo "    var suryou_".$row["shouhinCD"]."  = document.getElementById('suryou_".$row["shouhinCD"]."');\n" ;         //ボタンの注文数
        echo "    var btn_menu_".$row["shouhinCD"]." = document.getElementById('btn_menu_".$row["shouhinCD"]."');\n";       //ボタンのオブジェクト
        echo "    var items_".$row["shouhinCD"]." = document.getElementById('items_".$row["shouhinCD"]."');\n";             //商品パネル
        echo "    var cnt_suryou_".$row["shouhinCD"]." = 0;\n";                                                             //ボタンのカウンタ
        echo "\n";
        //ボタンクリック時の動作関数
        echo "    //".rot13decrypt($row["shouhinNM"])."ボタンクリック時\n";
        echo "    btn_menu_".$row["shouhinCD"].".onclick = function (){\n";
        echo "        cnt_suryou_".$row["shouhinCD"]." += 1;\n";
        
        //小数の計算はBC関数を使用
        echo "        total_pay += ".bcadd($row["tanka"] , bcmul($row["tanka"], bcdiv($row["zeiritu"],100,2),0),0).";\n";
        echo "        total_zei += ".bcmul($row["tanka"], bcdiv($row["zeiritu"],100,2),0).";\n";
        echo "        suryou_".$row["shouhinCD"].".value = cnt_suryou_".$row["shouhinCD"].";\n";
        echo "        kaikei_disp.innerHTML = total_pay;\n";
        echo "        zei_disp.innerHTML = total_zei;\n";
        echo "    };\n";
        echo "\n";
    }
?>
    //確認・確定ボタン
    var order_chk = document.getElementById('order_chk');
    var btn_commit = document.getElementById('btn_commit');
    //電卓表示ボタン
    var dentaku = document.getElementById('dentaku');
    
    order_chk.onclick = function(){
        //注文確認ボタン。選択されてないメニューを消し、ボタンの表示を変更する。
        <?php
        //$result->data_seek(0);
        //while($row = $result->fetch_assoc()){
        foreach($shouhiMS as $row){
            echo "if(cnt_suryou_".$row["shouhinCD"]."==0){items_".$row["shouhinCD"].".style.display = 'none';}";
        }
        ?>
        order_chk.style.display = 'none';
        btn_commit.style.display = 'block';
        dentaku.style.display = 'block';
        order_return.style.display = 'block';
        order_clear.style.display = 'none';
    };
    
    dentaku.onclick = function(){
        //電卓モーダルに会計金額を表示
        seikyuu.innerHTML = total_pay;
    };

    //計算ボタン
    var keisan = document.getElementById('keisan');
    var azukari = document.getElementById('azukari');
    keisan.onclick = function(){
        //電卓モーダルに会計金額を表示
        var azukarikin = azukari.value;
        var oturikin = azukarikin - total_pay;
        oturi.innerHTML = oturikin;
    };
/*
    // メニューボタンクリック処理
    btn_menu_002.onclick = function (){
        cnt_suryou_2 += 1;
        total_pay += 230;
        suryou_2.value = cnt_suryou_2;
        kaikei_disp.innerHTML = total_pay;
    };
*/

     var su = document.getElementsByClassName("su");
     var items = document.getElementsByClassName("items");
     var reset_btn = document.getElementById("order_clear");
     var return_btn = document.getElementById("order_return");
     // リセットボタンのクリック処理
     reset_btn.onclick = function (){
        for (let i = 0; i < su.length; i++) {
            su.item(i).value = 0;
            items.item(i).style.display = 'block';
        }
        kaikei_disp.innerHTML = 0;
        total_pay = 0;
        zei_disp.innerHTML = 0;
        total_zei = 0;
        <?php
        foreach($shouhiMS as $row){
            echo "cnt_suryou_".$row["shouhinCD"]." = 0;\n";
        }
        ?>
        order_chk.style.display = 'block';
        btn_commit.style.display = 'none';
     };
     //戻るボタン
     return_btn.onclick = function(){
        for (let i = 0; i < su.length; i++) {
            items.item(i).style.display = 'block';
        }
        order_return.style.display = 'none';
        order_clear.style.display = 'block';
        btn_commit.style.display = 'none';
        order_chk.style.display = 'block';
     }
};    
</script>

<form method = "post" action="EVregi_sql.php">
    <input type="hidden" name="csrf_token" value='<?php echo $token;?>'>
    
<header>
    <div class="title yagou"><a href="menu.php"><?php echo $title;?></a></div>
    <div class="event" style="font-family:inherit;"><input type="text" class="ev" name="EV" value="<?php echo $event ?>" placeholder="イベント名等"></div>
</header>

<body>
<?php
if(isset($emsg)){
    echo $emsg;
    exit();
}
?>
    <div class="container-fluid">
        <div class="row">
<?php
    $i=0;

	foreach($shouhiMS as $row){
        echo "  <div class ='col-md-3 col-sm-6 col-6 items' id='items_".$row["shouhinCD"]."'>\n";
        echo "      <button type='button' class='btn btn--menu' id='btn_menu_".$row["shouhinCD"]."'>".rot13decrypt($row["shouhinNM"])."\n";
        echo "      <input type='hidden' name ='ORDERS[".$i."][CD]' value = '".$row["shouhinCD"]."'>\n";
        echo "      <input type='hidden' name ='ORDERS[".$i."][NM]' value = '".$row["shouhinNM"]."'>\n";
        echo "      <input type='hidden' name ='ORDERS[".$i."][UTISU]' value = '".$row["utisu"]."'>\n";
        echo "      <input type='hidden' name ='ORDERS[".$i."][ZEI]' value = '".(string)(($row["zeiritu"]/100)*$row["tanka"])."'>\n";
        echo "      <input type='hidden' name ='ORDERS[".$i."][ZEIKBN]' value = '".$row["zeiKBN"]."'>\n";
        echo "      <input type='hidden' name ='ORDERS[".$i."][TANKA]' value = '".$row["tanka"]."'>\n";
        echo "      </button>\n";
        echo "      <div class ='ordered'>\n";
//        echo "          ￥<input type='number' readonly='readonly' class='order tanka' name='ORDERS[".$i."][TANKA]' value=".$row["tanka"].">\n";
//        echo "          ￥<input type='number' readonly='readonly' class='order tanka' value=".$row["tanka"]*(($row["zeiritu"]/100)+1).">\n";

        echo "          ￥<input type='number' readonly='readonly' class='order tanka' value=".bcadd($row["tanka"] , bcmul($row["tanka"], bcdiv($row["zeiritu"],100,2),0),0).">\n";

        echo "× <input type='number' readonly='readonly' name ='ORDERS[".$i."][SU]' id='suryou_".$row["shouhinCD"]."' class='order su' value = 0 style='display: inline'>\n";
        echo "      </div>\n";
        echo "  </div>\n";
        $i = $i+1;
	}
?> 
        </div>
    </div>
</body>

<footer>
    <div class="kaikei">
        <span style="font-size:1.6rem;">お会計</span> ￥<span id="kaikei">0</span>- <span style="font-size:1.6rem;">内税</span>(<span id="utizei">0</span>)
    </div>
    <div class="right1">
        <button type='button' class='btn btn--chk' style="border-radius:0;" id='dentaku' data-toggle="modal" data-target="#FcModal">釣　銭</button>
    </div>
    <div class="right3">
        <button type='button' class='btn btn--chk' style="border:solid;border-top:none;border-bottom:none;border-color:#fff;border-radius: 0;" id='order_clear'>クリア</button>
        <button type='button' class='btn btn--chk' style="display:none;border:solid;border-top:none;border-bottom:none;border-color:#fff;border-radius: 0;" id='order_return'>戻　る</button>
    </div>
    <div class="right2">
        <button type='submit' class='btn btn--commit' style='display:none;border-radius:0;' id='btn_commit' name='commit_btn' value="commit">登　録</button>
        <button type='button' class='btn btn--chk' style="border-radius:0;" id='order_chk'>確　認</button>
    </div>
</footer>

<!--モーダル電卓-->
<div class="modal fade" id="FcModal" tabindex="-1" role="dialog" aria-labelledby="basicModal" aria-hidden="true">
    <div class="modal-dialog  modal-dialog-centered">
        <div class="modal-content" style="font-size: 3.0rem; font-weight: 800;">
            <div class="modal-header">
                <!--<div class="modal-title" id="myModalLabel">電　卓</div>-->
            </div>
            <div class="modal-body">
                <label>お預り</label><br>
                <input type="number" id="azukari"><br>
                <p>お会計：￥<span id="seikyuu">0</span></p>
                <button type="button" id="keisan">計　算</button>
                <p>お釣り：￥<span id="oturi">0</span></p>    
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">閉じる</button>
            </div>
        </div>
    </div>
</div>
</form>
</html>
<?php
$stmt = null;
$pdo_h = null;
?>


