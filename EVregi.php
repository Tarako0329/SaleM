<!DOCTYPE html>
<html lang="ja">
<?php
/*関数メモ
check_session_userid：セッションのユーザIDが消えた場合、自動ログインがオフならログイン画面へ、オンなら自動ログインテーブルからユーザIDを取得

【想定して無いページからの遷移チェック】
csrf_create()：SESSIONとCOOKIEに同一トークンをセットし、同内容を返す。(POSTorGETで遷移先に渡す)
　　　　　　　 headerでリダイレクトされた場合、COOKIEにセットされないので注意。

遷移先のチェック
csrf_chk()                              ：COOKIE・SESSION・POSTのトークンチェック。
csrf_chk_nonsession()                   ：COOKIE・POSTのトークンチェック。
csrf_chk_nonsession_get($_GET[token])   ：COOKIE・GETのトークンチェック。
csrf_chk_redirect($_GET[token])         ：SESSSION・GETのトークンチェック
*/
require "php_header.php";

if(isset($_GET["csrf_token"]) || empty($_POST)){
    if(csrf_chk_redirect($_GET["csrf_token"])==false){
        $_SESSION["EMSG"]="セッションが正しくありませんでした。".$_GET["csrf_token"];
        header("HTTP/1.1 301 Moved Permanently");
        header("Location: index.php");
        exit();
    }
}

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


$token = csrf_create();
$alert_msg="";
if(!empty($_SESSION["msg"])){
    $alert_msg=$_SESSION["msg"];
}


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
        //deb_echo("DB".$event);
    } 
}
//メニューカテゴリー粒度(0:なし>1:大>2:中>3:小)
//セッション->クッキー->DB
if($_SESSION["CTGL"] != "" ){
    $categoly = $_SESSION["CTGL"];
    //deb_echo("session");
}else{
    $sql = "select value from PageDefVal where uid=? and machin=? and page=? and item=?";
    $stmt = $pdo_h->prepare($sql);
    $stmt->bindValue(1, $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->bindValue(2, MACHIN_ID, PDO::PARAM_STR);
    $stmt->bindValue(3, "EVregi.php", PDO::PARAM_STR);
    $stmt->bindValue(4, "CTGL", PDO::PARAM_STR);//name属性を指定
    $stmt->execute();

    if($stmt->rowCount()==0){
        $categoly = 0;
        deb_echo("NULL");
    }else{
        $buf = $stmt->fetch();
        $_SESSION["CTGL"] = $buf["value"];
        $categoly = $buf["value"];
        deb_echo("DB".$event);
    } 
}
$next_categoly=$categoly+1;

if($categoly==0){
    $sql_order="order by hyoujiNO,shouhinNM";
    $sql_group="group by categoly";
    $sql_select="'' as categoly";
}else if($categoly==1){
    $sqlorder="order by bunrui1,hyoujiNO,shouhinNM";
    $sql_group="group by bunrui1";
    $sql_select="if(bunrui1<>'',bunrui1,'未分類') as categoly";
}else if($categoly==2){
    $sqlorder="order by bunrui1,bunrui2,hyoujiNO,shouhinNM";
    $sql_group="group by bunrui1,bunrui2";
    $sql_select="concat(if(bunrui1<>'',bunrui1,'未分類'),'>',if(bunrui2<>'',bunrui2,'未分類')) as categoly";
}else if($categoly==3){
    $sqlorder="order by bunrui1,bunrui2,bunrui3,hyoujiNO,shouhinNM";
    $sql_group="group by bunrui1,bunrui2,bunrui3";
    $sql_select="concat(if(bunrui1<>'',bunrui1,'未分類'),'>',if(bunrui2<>'',bunrui2,'未分類'),'>',if(bunrui3<>'',bunrui3,'未分類')) as categoly";
    $next_categoly=0;
}
    
//商品M取得
if(!empty($_GET["mode"])){
    //イベントレジモード以外はすべて表示する
    $sql = "select *,".$sql_select." from ShouhinMS where uid = ? ".$sqlorder;
}else{
    $sql = "select *,".$sql_select." from ShouhinMS where hyoujiKBN1='on' and uid = ? ".$sqlorder;
}
$stmt = $pdo_h->prepare($sql);
$stmt->bindValue(1, $_SESSION['user_id'], PDO::PARAM_INT);
$stmt->execute();
$shouhiMS = $stmt->fetchAll();

//商品M分類取得
$sql = "select ".$sql_select." from ShouhinMS where hyoujiKBN1='on' and uid = ? ".$sql_group." ".$sqlorder;
$stmt = $pdo_h->prepare($sql);
$stmt->bindValue(1, $_SESSION['user_id'], PDO::PARAM_INT);
$stmt->execute();
$shouhiMS_bunrui = $stmt->fetchAll();
//deb_echo($next_categoly);

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
        echo "        total_pay += ".($row["tanka"] + $row["tanka_zei"]).";\n";
        echo "        total_zei += ".$row["tanka_zei"].";\n";
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
        foreach($shouhiMS as $row){
            echo "if(cnt_suryou_".$row["shouhinCD"]."==0){items_".$row["shouhinCD"].".style.display = 'none';}";
        }
        ?>
        order_chk.style.display = 'none';
        btn_commit.style.display = 'block';
        dentaku.style.display = 'block';
        order_return.style.display = 'block';
        order_clear.style.display = 'none';
        CHOUSEI_AREA.style.display = 'block';
        total_pay_bk = total_pay; //調整前金額を保存
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
        CHOUSEI_AREA.style.display = 'none';
        CHOUSEI.style.display = 'none';
        CHOUSEI_GAKU.value='';
        kaikei_disp.innerHTML = total_pay_bk;
        total_pay=total_pay_bk;
     };
    CHOUSEI_AREA.onclick = function(){
        CHOUSEI_AREA.style.display = 'none';
        CHOUSEI.style.display = 'block';
        return_btn = document.getElementById("maekin");
        maekin.innerHTML = total_pay_bk;
    }
    CHOUSEI_GAKU.onchange = function(){
        total_pay = CHOUSEI_GAKU.value;
        kaikei_disp.innerHTML = total_pay;
    }
    
    //アラート用
    function alert(msg) {
      return $('<div class="alert" role="alert"></div>')
        .text(msg);
    }
    (function($){
      const s = alert('<?php echo $alert_msg; ?> ').addClass('alert-success');
      const e = alert('<?php echo $alert_msg; ?> ').addClass('alert-danger');
      // アラートを表示する
      $('#alert-s').append(s);
      // 5秒後にアラートを消す
      setTimeout(() => {
        s.alert('close');
      }, 5000);
      $('#alert-e').append(e);
      /* アラートを消さない
      setTimeout(() => {
        e.alert('close');
      }, 5000);
      */
    })(jQuery);
    
    // Enterキーが押された時にSubmitされるのを抑制する
    document.getElementById("form1").onkeypress = (e) => {
        // form1に入力されたキーを取得
        const key = e.keyCode || e.charCode || 0;
        // 13はEnterキーのキーコード
        if (key == 13) {
            // アクションを行わない
            //alert('test');
            e.preventDefault();
        }
    }
    
};    

</script>

<form method = "post" id="form1" action="EVregi_sql.php">
    <input type="hidden" name="csrf_token" value='<?php echo $token;?>'>
    <input type="hidden" name="mode" value='<?php echo $_GET["mode"];?>'> <!--レジor個別売上or在庫登録-->
    
<header class="header-color" style='display:block'>
    <div class="title yagou"><a href="menu.php"><?php echo $title;?></a></div>
    売上日：<input type='date' class='date' style='height:20%' name='KEIJOUBI' value='<?php echo (string)date("Y-m-d") ?>'>
    <?php
    if($_GET["mode"]=="kobetu"){
    ?>
        <div class="event" style="font-family:inherit;"><input type="text" class="ev" name="KOKYAKU" required="required" placeholder="(必須)顧客名"></div>
        <input type="hidden" name="EV" value="">
    <?php
    }else{
    ?>
        <div class="event" style="font-family:inherit;"><input type="text" class="ev" name="EV" value="<?php echo $event ?>" required="required" placeholder="(必須)EVENT名/店舗名等"></div>
        <input type="hidden" name="KOKYAKU" value="">
    <?php
    }
    ?>
</header>
<div class='header-select header-color' >
    <select class='form-control' style='font-size:1.2rem;padding:0;'> <!--width:80%;-->
        <option>カテゴリートップへ移動できます</option>
    <?php
        $i=1;
        foreach($shouhiMS_bunrui as $row){
            echo "<option value='#jump_".$i."'>".$row["categoly"]."</option>";
            $i++;
        }
    ?>
    </select>
    <a href="#" title="商品の並びを「カテゴリー（大⇒中⇒小⇒なし）」とローテーションで変更します。" style='color:inherit;margin-left:10px;margin-right:10px;margin-top:5px;'><i class="fa-regular fa-circle-question fa-lg"></i></a><a href='EVregi_sql.php?CTGL=<?php echo $next_categoly; ?>&mode=<?php echo $_GET["mode"]; ?>' style='color:inherit;margin-left:10px;margin-right:10px;margin-top:5px;'><i class="fa-solid fa-arrow-rotate-right fa-lg"></i></a>
</div>
<body>

<?php
    if(isset($emsg)){//
        echo $emsg;
        exit();
    }
    if($_GET["status"]=="success"){
        echo "<div class='container'><div class='row'><div class='col-12'><div style='padding-top:5px;text-align:center;font-size:1.5rem;' id='alert-s' class='lead'></div></div></div></div>";
    }elseif($_GET["status"]=="failed"){
        echo "<div class='container'><div class='row'><div class='col-12'><div style='padding-top:5px;text-align:center;font-size:1.5rem;' id='alert-e' class='lead'></div></div></div></div>";
    }
?>
    <div class="container-fluid">
        <div class="row" style='padding-top:5px;'>
            <div class="col-1 col-lg-0" ></div>
            <div class="col-10 col-lg-3" style="font-size:2.2rem;padding-top:10px;">
                <button type='button' class='btn-view btn-changeVal' style="display:none;padding:0.1rem;" id="CHOUSEI_AREA" >割引・割増</button>
            </div>
            <div class="col-1" ></div>
        </div>

        <div class="row" style="display:none" id="CHOUSEI">
            <div class="col-1 col-md-0" ></div>
            <div class="col-10 col-md-7" style="font-size: 2.2rem;">
                お会計額：￥<span id="maekin">0</span> ⇒
                <input type="number" placeholder="変更後の金額を入力。"　class='order tanka' style=" width:100%;border:solid;border-top:none;border-right:none;border-left:none;" name="CHOUSEI_GAKU" id="CHOUSEI_GAKU">
                <br>
            </div>
            <div class="col-1" ></div>
        </div>
        <hr>

        <div class='row' id='jump_0'>

<?php
    $i=0;
    $now=1;
    $bunrui="";
    
	foreach($shouhiMS as $row){
	    if($bunrui<>$row["categoly"]){
	        //ジャンルを区切るバーの表示
	        $next=$now+1;
	        $befor=$now-1;
	        echo "</div>";
	        echo "<div class='row' style='background:var(--jumpbar-color);margin-top:5px;' >\n"; //height:30px;
	        echo "<div class='col-12' id='jump_".$now."'><a href='#jump_".$befor."' class='btn-updown'><i class='fa-solid fa-angles-up'></i></a>\n";
	        echo $row["categoly"];
	        echo "<a href='#jump_".$next."'  class='btn-updown'><i class='fa-solid fa-angles-down'></i></a>\n";
	        echo "</div></div>\n";
	        echo "<div class='row'>";
	        $bunrui=$row["categoly"];
	        $now=$now+1;
	    }
        echo "  <div class ='col-md-3 col-sm-6 col-6 items' id='items_".$row["shouhinCD"]."'>\n";
        echo "      <button type='button' class='btn-view btn--rezi' id='btn_menu_".$row["shouhinCD"]."'>".rot13decrypt($row["shouhinNM"])."\n";
        echo "      <input type='hidden' name ='ORDERS[".$i."][CD]' value = '".$row["shouhinCD"]."'>\n";
        echo "      <input type='hidden' name ='ORDERS[".$i."][NM]' value = '".$row["shouhinNM"]."'>\n";
        echo "      <input type='hidden' name ='ORDERS[".$i."][UTISU]' value = '".$row["utisu"]."'>\n";
        echo "      <input type='hidden' name ='ORDERS[".$i."][ZEIKBN]' value = '".$row["zeiKBN"]."'>\n";
        echo "      <input type='hidden' name ='ORDERS[".$i."][TANKA]' value = '".$row["tanka"]."'>\n";
        echo "      <input type='hidden' name ='ORDERS[".$i."][GENKA_TANKA]' value = '".$row["genka_tanka"]."'>\n";
        echo "      </button>\n";
        echo "      <div class ='ordered'>\n";
        echo "          ￥<input type='number' readonly='readonly' class='order tanka' value=".($row["tanka"] + $row["tanka_zei"]).">\n";
        echo "            <input type='hidden' name ='ORDERS[".$i."][ZEI]' value = '".$row["tanka_zei"]."'>\n";  //税込価格-(税込価格÷1.1or1.08)
        echo "× <input type='number' readonly='readonly' name ='ORDERS[".$i."][SU]' id='suryou_".$row["shouhinCD"]."' class='order su' value = 0 style='display: inline'>\n";
        echo "      </div>\n";
        echo "  </div>\n";
        
        $i = $i+1;
	}
?> 
        </div>

    </div>
    <script>
        $('a[href*="#"]').click(function () {//全てのページ内リンクに適用させたい場合はa[href*="#"]のみでもOK
        	var elmHash = $(this).attr('href'); //ページ内リンクのHTMLタグhrefから、リンクされているエリアidの値を取得
        	var pos = $(elmHash).offset().top-100;	//idの上部の距離を取得
        	$('body,html').animate({scrollTop: pos}, 500); //取得した位置にスクロール。500の数値が大きくなるほどゆっくりスクロール
        	return false;
        });
        
        $(function () {
          $('select').change(function () {
            var speed = 400;
            var href = $(this).val();
            var target = $(href == "#" || href == "" ? 'html' : href);
            var position = target.offset().top-100;
            $('body,html').animate({scrollTop:position}, speed, 'swing');
            return false;
          });
        });        
        
    </script>
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
        <button type='submit' class='btn btn--commit' style='display:none;border-radius:0;' id='btn_commit' name='commit_btn' value="uriage_commit">登　録</button>
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


