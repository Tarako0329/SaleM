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
//セッションのIDがクリアされた場合の再取得処理。
$rtn=check_session_userid($pdo_h);

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

//商品M取得
$sql = "select * from ShouhinMS where hyoujiKBN1='on' and uid = ? order by hyoujiNO,bunrui1,bunrui2,bunrui3,shouhinNM";
$stmt = $pdo_h->prepare($sql);
$stmt->bindValue(1, $_SESSION['user_id'], PDO::PARAM_INT);
$stmt->execute();
$shouhiMS = $stmt->fetchAll();

$token = csrf_create();

//イベント名の取得
//セッション->クッキー->DB
if($_SESSION["EV"] != "" ){
    $event = $_SESSION["EV"];
    deb_echo("session");
}else{
    $sql = "select value from PageDefVal where uid=? and machin=? and page=? and item=?";
    $stmt = $pdo_h->prepare($sql);
    $stmt->bindValue(1, $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->bindValue(2, MACHIN_ID, PDO::PARAM_STR);
    $stmt->bindValue(3, "EVregi.php", PDO::PARAM_STR);
    $stmt->bindValue(4, "EV", PDO::PARAM_STR);//name属性を指定
    $stmt->execute();

    if($stmt->rowCount()==0){
        $event = "";
        deb_echo("NULL");
    }else{
        $buf = $stmt->fetch();
        $_SESSION["EV"] = $buf["value"];
        $event = $buf["value"];
        //setCookie("EV", $event, time()+60*60*24*2, "/", null, TRUE, TRUE); 
        deb_echo("DB".$event);
    } 
}


?>
<head>
    <?php 
    //共通部分、bootstrap設定、フォントCND、ファビコン等
    include "head.html" 
    ?>
    <!--ページ専用CSS-->
    <link rel="stylesheet" href="css/style_EVregi.css?<?php echo $time; ?>" >
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
     var total_pay_bk = 0;
     
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
        //税区分末尾１：外税　２：内税
        if(substr(strval($row["zeiKBN"]),3,1) =="1" || $row["zeiKBN"]==0){
            //外税もしくは非課税の場合
            echo "        total_pay += ".bcadd($row["tanka"] , bcmul($row["tanka"], bcdiv($row["zeiritu"],100,2),0),0).";\n";
            echo "        total_zei += ".bcmul($row["tanka"], bcdiv($row["zeiritu"],100,2),0).";\n";
        }else{
            //内税の場合
            echo "        total_pay += ".$row["tanka"].";\n";
            echo "        total_zei += ".bcsub($row["tanka"],bcdiv($row["tanka"], bcdiv(bcadd(100,$row["zeiritu"]),100,2),0),0).";\n";
        }
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
    //調整額入力エリア
    var CHOUSEI = document.getElementById("CHOUSEI");
    var CHOUSEI_GAKU = document.getElementById("CHOUSEI_GAKU");
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
        CHOUSEI.style.display = 'block';
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
        CHOUSEI.style.display = 'none';
        CHOUSEI_GAKU.value='';
        kaikei_disp.innerHTML = total_pay_bk;
        total_pay=total_pay_bk;
     };
    CHOUSEI_GAKU.onchange = function(){
        total_pay_bk = total_pay; //調整前金額を保存
        total_pay = CHOUSEI_GAKU.value;
        kaikei_disp.innerHTML = total_pay;
    }
};    
</script>

<form method = "post" action="EVregi_sql.php">
    <input type="hidden" name="csrf_token" value='<?php echo $token;?>'>
    <input type="hidden" name="mode" value='<?php echo $_GET["mode"];?>'>
    
<header>
    <div class="title yagou"><a href="menu.php"><?php echo $title;?></a></div>
    <?php
    if($_GET["mode"]=="kobetu"){
    ?>
        <div class="event" style="font-family:inherit;"><input type="text" class="ev" name="KOKYAKU" required="required" placeholder="(必須)顧客名"></div>
        <input type="hidden" name="EV" value="">
    <?php
    }else{
    ?>
        <div class="event" style="font-family:inherit;"><input type="text" class="ev" name="EV" value="<?php echo $event ?>" placeholder="イベント名等"></div>
        <input type="hidden" name="KOKYAKU" value="">
    <?php
    }
    ?>
</header>

<body>
<?php
if(isset($emsg)){//
    echo $emsg;
    exit();
}
?>
    <div class="container-fluid">
        <div class="row" style="display:none" id="CHOUSEI">
            <div class="col-1 col-md-0" ></div>
            <div class="col-10 col-md-7" style="font-size: 2.2rem;">
                割引／割増後　お会計額：
                <input type="number" class='order tanka' style=" width:100%;border:solid;border-top:none;border-right:none;border-left:none;" name="CHOUSEI_GAKU" id="CHOUSEI_GAKU">
                <br>
            </div>
            <div class="col-1" ></div>
        </div>
        <hr>
        <div class="row">
            
<?php
    $i=0;

	foreach($shouhiMS as $row){
        echo "  <div class ='col-md-3 col-sm-6 col-6 items' id='items_".$row["shouhinCD"]."'>\n";
        echo "      <button type='button' class='btn btn--menu' id='btn_menu_".$row["shouhinCD"]."'>".rot13decrypt($row["shouhinNM"])."\n";
        echo "      <input type='hidden' name ='ORDERS[".$i."][CD]' value = '".$row["shouhinCD"]."'>\n";
        echo "      <input type='hidden' name ='ORDERS[".$i."][NM]' value = '".$row["shouhinNM"]."'>\n";
        echo "      <input type='hidden' name ='ORDERS[".$i."][UTISU]' value = '".$row["utisu"]."'>\n";
        //echo "      <input type='hidden' name ='ORDERS[".$i."][ZEI]' value = '".(string)(($row["zeiritu"]/100)*$row["tanka"])."'>\n";
        echo "      <input type='hidden' name ='ORDERS[".$i."][ZEIKBN]' value = '".$row["zeiKBN"]."'>\n";
        echo "      <input type='hidden' name ='ORDERS[".$i."][TANKA]' value = '".$row["tanka"]."'>\n";
        echo "      </button>\n";
        echo "      <div class ='ordered'>\n";
        if(substr(strval($row["zeiKBN"]),3,1) =="1" || $row["zeiKBN"]==0){
            //外税（マスタは税抜単価）
            echo "          ￥<input type='number' readonly='readonly' class='order tanka' value=".bcadd($row["tanka"] , bcmul($row["tanka"], bcdiv($row["zeiritu"],100,2),0),0).">\n";
            echo "            <input type='hidden' name ='ORDERS[".$i."][ZEI]' value = '".(string)(bcmul($row["tanka"], bcdiv($row["zeiritu"],100,2),0))."'>\n";    //消費税：本体×税率
        }else{
            //内税（マスタは税込単価）
            echo "          ￥<input type='number' readonly='readonly' class='order tanka' value=".$row["tanka"].">\n";
            echo "            <input type='hidden' name ='ORDERS[".$i."][ZEI]' value = '".(string)(bcsub($row["tanka"],bcdiv($row["tanka"], bcdiv(bcadd(100,$row["zeiritu"]),100,2),0),0))."'>\n";  //税込価格-(税込価格÷1.1or1.08)
        }

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
                <!--<button type="button" class="btn btn-default" data-dismiss="modal">閉じる</button>-->
                <button type="button"  data-dismiss="modal">閉じる</button>
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


