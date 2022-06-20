<!DOCTYPE html>
<html lang='ja'>
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
    $root_url = bin2hex(openssl_encrypt(ROOT_URL, 'AES-128-ECB', null));
    $dir_path =  bin2hex(openssl_encrypt(dirname(__FILE__)."/", 'AES-128-ECB', null));
    
    //echo row[0]["yuukoukigen"] ;
    $emsg="お試し期間、もしくは解約後有効期間が終了しました。<br>継続してご利用頂ける場合は<a href='".PAY_CONTRACT_URL."?system=".$title."&sysurl=".$root_url."&dirpath=".$dir_path."'>こちらから本契約をお願い致します </a>";
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
    //deb_echo("session");
}else{
    $sql = "select value,updatetime from PageDefVal where uid=? and machin=? and page=? and item=?";
    $stmt = $pdo_h->prepare($sql);
    $stmt->bindValue(1, $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->bindValue(2, MACHIN_ID, PDO::PARAM_STR);
    $stmt->bindValue(3, "EVregi.php", PDO::PARAM_STR);
    $stmt->bindValue(4, "EV", PDO::PARAM_STR);//name属性を指定
    $stmt->execute();

    
    if($stmt->rowCount()==0){
        $event = "";
        //deb_echo("NULL");
    }else{
        $buf = $stmt->fetch();
        $date = new DateTime($buf["updatetime"]);
    
        //指定した書式で日時を取得する
        //echo $date->format('Y-m-d');
        if($date->format('Y-m-d')!=date("Y-m-d")){
            //イベントの日付が前日以前の場合はクリア
            $event = "";
            //deb_echo($buf["updatetime"]."<br>");
        }else{
            //$buf = $stmt->fetch();
            $_SESSION["EV"] = $buf["value"];
            $event = $buf["value"];
            //deb_echo("DB".$event);
        }
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
        //deb_echo("NULL");
    }else{
        $buf = $stmt->fetch();
        $_SESSION["CTGL"] = $buf["value"];
        $categoly = $buf["value"];
        //deb_echo("DB".$event);
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
if($_GET["mode"]=="shuppin_zaiko"){
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
     var kaikei_disp = document.getElementById("kaikei");
     var zei_disp = document.getElementById("utizei");
     var plus_minus = document.getElementsByName('options');
     //フッター合計支払金額を保持
     var total_pay = 0;     //税込総支払額
     var total_zei = 0;     //総支払額の内税
     var total_pay_bk = 0;  //値引値増前の金額を保持し、会計確定せずに戻る際にtotal_payに返す
     
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
        echo "        if(plus_minus[0].checked){\n";//通常モード（プラス）
        echo "              cnt_suryou_".$row["shouhinCD"]." += 1;\n";
        echo "              total_pay += ".($row["tanka"] + $row["tanka_zei"]).";\n";

        echo "              total_pay_bk += ".($row["tanka"] + $row["tanka_zei"]).";\n";

        echo "              total_zei += ".$row["tanka_zei"].";\n";
        echo "              suryou_".$row["shouhinCD"].".value = cnt_suryou_".$row["shouhinCD"].";\n";
        echo "              kaikei_disp.innerHTML = total_pay;\n";
        echo "              zei_disp.innerHTML = total_zei;\n";
        echo "        }else if(plus_minus[1].checked){\n";//減らすモード（マイナス）
        echo "              if(cnt_suryou_".$row["shouhinCD"]."==0){\n";
        echo "                  window.alert('数量０以下には出来ません');\n";
        echo "                  exit;\n";
        echo "              }\n";
        echo "              cnt_suryou_".$row["shouhinCD"]." -= 1;\n";
        echo "              total_pay -= ".($row["tanka"] + $row["tanka_zei"]).";\n";

        echo "              total_pay_bk -= ".($row["tanka"] + $row["tanka_zei"]).";\n";

        echo "              total_zei -= ".$row["tanka_zei"].";\n";
        echo "              suryou_".$row["shouhinCD"].".value = cnt_suryou_".$row["shouhinCD"].";\n";
        echo "              kaikei_disp.innerHTML = total_pay;\n";
        echo "              zei_disp.innerHTML = total_zei;\n";
        echo "        }\n";
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
    
    function double(btn){
        //登録ボタンの２重クリック防止
        btn.innerHTML="登録中";
        btn.disabled = true;
    }
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
        <?php
            if($_GET["mode"]<>"shuppin_zaiko"){ echo "CHOUSEI_AREA.style.display = 'block';\n";} //在庫登録モードでは割引ボタンを表示しない
        ?>
        //total_pay_bk = total_pay; //調整前金額を保存
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
        total_pay_bk = 0;
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
        CHOUSEI_BTN.style.display = 'block';
        CHOUSEI.style.display = 'none';
        CHOUSEI_GAKU.value='';
        kaikei_disp.innerHTML = total_pay_bk;
        total_pay=total_pay_bk;
     };
    CHOUSEI_BTN.onclick = function(){
        CHOUSEI_BTN.style.display = 'none';
        CHOUSEI.style.display = 'block';
        //total_pay_bk = total_pay
        return_btn = document.getElementById("maekin");
        maekin.innerHTML = total_pay_bk;
    }
    CHOUSEI_GAKU.onchange = function(){
        total_pay = CHOUSEI_GAKU.value;
        kaikei_disp.innerHTML = total_pay;
    }

     var plus_mode = document.getElementById("plus_mode");
     var minus_mode = document.getElementById("minus_mode");
     //var plus_disp = document.getElementById("plus_disp");
     var minus_disp = document.getElementsByClassName('minus_disp');
     minus_mode.onclick = function(){
        for (let i = 0; i < minus_disp.length; i++) {
            minus_disp.item(i).style.display = 'block';
        }
        //plus_disp.style.display = 'none';
     }
     plus_mode.onclick = function(){
        for (let i = 0; i < minus_disp.length; i++) {
            minus_disp.item(i).style.display = 'none';
        }
        //plus_disp.style.display = 'block';
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
      /* エラーの場合はアラートを消さない
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
    
};//window.onload

</script>

<form method = "post" id="form1" action="EVregi_sql.php">
    <input type="hidden" name="csrf_token" value='<?php echo $token;?>'>
    <input type="hidden" name="mode" value='<?php echo $_GET["mode"];?>'> <!--レジor個別売上or在庫登録-->
    
<header class="header-color common_header" style='display:block'>
    <div class="title yagou"><a href="menu.php"><?php echo $title;?></a></div>
    <span class='item_1'>
    <span style='color:var(--user-disp-color);font-weight:400;'>
    <?php if($_GET["mode"]=="shuppin_zaiko"){echo "出店日：";}else{echo "売上日：";}?>
    </span><input type='date' class='date' style='height:20%' name='KEIJOUBI' required="required" value='<?php if($_GET["mode"]<>"shuppin_zaiko"){echo (string)date("Y-m-d");} ?>'>
    </span>
    <?php
    if($_GET["mode"]=="kobetu"){
    ?>
        <div class="event" style="font-family:inherit;"><input type="text" class="ev" name="KOKYAKU" required="required" placeholder="(必須)顧客名"></div>
        <input type="hidden" name="EV" value="">
    <?php
    }else{
    ?>
        <div class="event" style="font-family:inherit;"><input type="text" class="ev item_2" name="EV" value="<?php echo $event ?>" required="required" placeholder="(必須)イベント名等"></div>
        <input type="hidden" name="KOKYAKU" value="">
    <?php
    }
    ?>
</header>
<div class='header-select header-color' >
    <select class='form-control item_16' style='font-size:1.2rem;padding:0;'> <!--width:80%;-->
        <option>カテゴリートップへ移動できます</option>
    <?php
        $i=1;
        foreach($shouhiMS_bunrui as $row){
            echo "<option value='#jump_".$i."'>".$row["categoly"]."</option>";
            $i++;
        }
    ?>
    </select>
    <a href="#" style='color:inherit;margin-left:10px;margin-right:10px;margin-top:5px;' data-toggle='modal' data-target='#modal_help1'><i class="fa-regular fa-circle-question fa-lg logoff-color"></i></a>
    <a class='item_15' href='EVregi_sql.php?CTGL=<?php echo $next_categoly; ?>&mode=<?php echo $_GET["mode"]; ?>' style='color:inherit;margin-left:10px;margin-right:10px;margin-top:5px;'><i class="fa-solid fa-arrow-rotate-right fa-lg logoff-color"></i></a>
</div>
<div class='header-plus-minus text-center item_4' style='font-size:1.4rem;font-weight;700'>
    <i class="fa-regular fa-circle-question fa-lg logoff-color"></i><!--スペーシングのため白アイコンを表示-->
    <div class='btn-group btn-group-toggle' style='padding:0;' data-toggle='buttons'>
        <label class='btn btn-outline-primary active' id='plus_mode'>
            <input type='radio' name='options' value='plus' autocomplete='off' checked>　▲　
        </label>
        <label class='btn btn-outline-warning' id='minus_mode'>
            <input type='radio' name='options' value='minus' autocomplete='off'>　▼　
        </label>
    </div>
    <a href="#" style='color:inherit;margin-left:5px;margin-top:10px;' data-toggle='modal' data-target='#modal_help2'><i class="fa-regular fa-circle-question fa-lg awesome-color-panel-border-same"></i></a>
</div>
<body class='common_body'>
    
<?php
    if(isset($emsg)){//
        echo $emsg;
        echo "</body></html>";
        exit();
    }
    if($_GET["status"]=="success"){
        echo "<div class='container'><div class='row'><div class='col-12'><div style='padding-top:5px;text-align:center;font-size:1.5rem;' id='alert-s' class='lead'></div></div></div></div>";
    }elseif($_GET["status"]=="failed"){
        echo "<div class='container'><div class='row'><div class='col-12'><div style='padding-top:5px;text-align:center;font-size:1.5rem;' id='alert-e' class='lead'></div></div></div></div>";
    }
?>
    <div class="container-fluid">
        <div class='item_11 item_12'>
        <div class="row text-center" style='padding-top:5px;display:none;' id='CHOUSEI_AREA'>
            <button type='button' class='btn-view btn-changeVal ' style="padding:0.1rem;width:300px;font-size:2.2rem;" id="CHOUSEI_BTN" >割引・割増</button>
        </div>
        

        <div class="row " style="display:none" id="CHOUSEI">
            <div class="col-1 col-md-0" ></div>
            <div class="col-10 col-md-7" style="font-size: 2.2rem;">
                お会計額：￥<span id="maekin">0</span> ⇒
                <input type="number" placeholder="変更後の金額を入力。"　class='order tanka' style=" width:100%;border:solid;border-top:none;border-right:none;border-left:none;" name="CHOUSEI_GAKU" id="CHOUSEI_GAKU">
                <br>
            </div>
            <div class="col-1" ></div>
        </div>
        </div>
        <hr>

        <div class='row item_3' id='jump_0'>

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
	        echo "<div class='col-12' id='jump_".$now."' style='color:var(--categ-font-color);'><a href='#jump_".$befor."' class='btn-updown'><i class='fa-solid fa-angles-up'></i></a>\n";
	        echo $row["categoly"];
	        echo "<a href='#jump_".$next."'  class='btn-updown'><i class='fa-solid fa-angles-down'></i></a>\n";
	        echo "</div></div>\n";
	        echo "<div class='row'>";
	        $bunrui=$row["categoly"];
	        $now=$now+1;
	    }
        echo "  <div class ='col-md-3 col-sm-6 col-6 items' id='items_".$row["shouhinCD"]."'>\n";
        echo "      <button type='button' class='btn-view btn--rezi' id='btn_menu_".$row["shouhinCD"]."'>".rot13decrypt($row["shouhinNM"])."\n";
        echo "      </button>\n";
        echo "      <div class='btn-view btn--rezi-minus bg-warning minus_disp' style='display:none;'></div>n";
        echo "      <input type='hidden' name ='ORDERS[".$i."][CD]' value = '".$row["shouhinCD"]."'>\n";
        echo "      <input type='hidden' name ='ORDERS[".$i."][NM]' value = '".$row["shouhinNM"]."'>\n";
        echo "      <input type='hidden' name ='ORDERS[".$i."][UTISU]' value = '".$row["utisu"]."'>\n";
        echo "      <input type='hidden' name ='ORDERS[".$i."][ZEIKBN]' value = '".$row["zeiKBN"]."'>\n";
        echo "      <input type='hidden' name ='ORDERS[".$i."][TANKA]' value = '".$row["tanka"]."'>\n";
        echo "      <input type='hidden' name ='ORDERS[".$i."][GENKA_TANKA]' value = '".$row["genka_tanka"]."'>\n";
        echo "      <div class ='ordered'>\n";
        echo "          ￥<input type='number' readonly='readonly' class='order tanka' value=".($row["tanka"] + $row["tanka_zei"]).">\n";
        echo "            <input type='hidden' name ='ORDERS[".$i."][ZEI]' value = '".$row["tanka_zei"]."'>\n";  //税込価格-(税込価格÷1.1or1.08)
        echo "          × <input type='number' readonly='readonly' name ='ORDERS[".$i."][SU]' id='suryou_".$row["shouhinCD"]."' class='order su' value = 0 style='display: inline'>\n";
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
        	var pos = $(elmHash).offset().top-145;	//idの上部の距離を取得
        	$('body,html').animate({scrollTop: pos}, 500); //取得した位置にスクロール。500の数値が大きくなるほどゆっくりスクロール
        	return false;
        });
        
        $(function () {
          $('select').change(function () {
            var speed = 400;
            var href = $(this).val();
            var target = $(href == "#" || href == "" ? 'html' : href);
            //var position = target.offset().top-100;
            var position = target.offset().top-145;
            $('body,html').animate({scrollTop:position}, speed, 'swing');
            return false;
          });
        });        
        
    </script>
</body>

<footer class='rezfooter'>
    <div class='kaikei'>
        <span style='font-size:1.6rem;'>お会計</span> ￥<span id='kaikei'>0</span>- <span style='font-size:1.6rem;'>内税</span>(<span id='utizei'>0</span>)
    </div>
    <div class='right1'>
        <button type='button' class='btn--chk item_5' style='border-left:none;border-right:none;' id='dentaku' data-toggle='modal' data-target='#FcModal'><?php if($_GET["mode"]<>"shuppin_zaiko"){echo "釣　銭";} ?></button>
    </div>
    <div class='right3 item_10'>
        <button type='button' class='btn--chk item_8' style=';' id='order_clear'>クリア</button>
        <button type='button' class='btn--chk ' style='display:none;' id='order_return'>戻　る</button>
    </div>
    <div class='right2 item_9'>
        <button type='submit' class='btn--commit item_13' style='display:none;border-left:none;border-right:none;' id='btn_commit' name='commit_btn' value='uriage_commit' onClick='double(this)'>登　録</button>
        <button type='button' class='btn--chk ' style='border-left:none;border-right:none;' id='order_chk'>確　認</button>
    </div>
</footer>
</form>
<!--モーダル電卓(FcModal)-->
<div class='modal fade' id='FcModal' tabindex='-1' role='dialog' aria-labelledby='basicModal' aria-hidden='true'>
    <div class='modal-dialog  modal-dialog-centered'>
        <div class='modal-content item_6' style='font-size: 3.0rem; font-weight: 800;'>
            <div class='modal-header'>
                <!--<div class='modal-title' id='myModalLabel'>電　卓</div>-->
            </div>
            <div class='modal-body'>
                <label>お預り</label><br>
                <input type='number' id='azukari'><br>
                <p>お会計：￥<span id='seikyuu'>0</span></p>
                <button type='button' id='keisan'>計　算</button>
                <p>お釣り：￥<span id='oturi'>0</span></p>    
            </div>
            <div class='modal-footer'>
                <!--<button type='button' class='btn btn-default' data-dismiss='modal'>閉じる</button>-->
                <button type='button'  class='item_7' data-dismiss='modal'>閉じる</button>
            </div>
        </div>
    </div>
</div>

<!--分類表示切替のヘルプ(modal_help1)-->
<div class='modal fade' id='modal_help1' tabindex='-1' role='dialog' aria-labelledby='basicModal' aria-hidden='true'>
    <div class='modal-dialog  modal-dialog-centered'>
        <div class='modal-content' style='font-size: 1.5rem; font-weight: 600;'>
            <div class='modal-header'>
                <!--<div class='modal-title' id='myModalLabel'>電　卓</div>-->
            </div>
            <div class='modal-body text-center'>
                <i class="fa-solid fa-arrow-rotate-right fa-lg"></i> をタップすると、商品登録時に設定した分類ごとにパネルが表示されます。<br>
                タップするごとに（大分類⇒中分類⇒小分類⇒50音順）の順番でループします。<br>
            </div>
            <div class='modal-footer'>
                <!--<button type='button' class='btn btn-default' data-dismiss='modal'>閉じる</button>-->
                <button type='button'  data-dismiss='modal'>閉じる</button>
            </div>
        </div>
    </div>
</div>

<!--加算減算モードのヘルプ(modal_help2)-->
<div class='modal fade' id='modal_help2' tabindex='-1' role='dialog' aria-labelledby='basicModal' aria-hidden='true'>
    <div class='modal-dialog  modal-dialog-centered'>
        <div class='modal-content' style='font-size: 1.5rem; font-weight: 600;'>
            <div class='modal-header'>
                <!--<div class='modal-title' id='myModalLabel'>電　卓</div>-->
            </div>
            <div class='modal-body'>
                <div class='btn-group btn-group-toggle' data-toggle='buttons'>
                <label class='btn btn-outline-warning' style='padding:0;'>
                    <input type='radio' autocomplete='off'>　▼　
                </label>
                </div>
                をタップすると、注文数を減らせるようになります。<br>
                <div class='btn-group btn-group-toggle' data-toggle='buttons'>
                <label class='btn btn-outline-primary' style='padding:0;' >
                <input type='radio' autocomplete='off'>　▲　
                </label>
                </div>
                をタップすると元に戻ります。<br>
            </div>
            <div class='modal-footer'>
                <!--<button type='button' class='btn btn-default' data-dismiss='modal'>閉じる</button>-->
                <button type='button'  data-dismiss='modal'>閉じる</button>
            </div>
        </div>
    </div>
</div>


<!--シェパードナビ
<script src="https://cdn.jsdelivr.net/npm/shepherd.js@9.1.1/dist/js/shepherd.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/shepherd.js@9.1.1/dist/css/shepherd.css"/>
-->
<script src="shepherd/shepherd.min.js?<?php echo $time; ?>"></script>
<link rel="stylesheet" href="shepherd/shepherd.css?<?php echo $time; ?>"/>
<?php require "ajax_func_tourFinish.php";?>
<script>
    const TourMilestone = '<?php echo $_SESSION["tour"];?>';

    const tutorial_5 = new Shepherd.Tour({
        useModalOverlay: true,
        defaultStepOptions: {
            classes: 'tour_modal',
            scrollTo: false,
            cancelIcon:{
                enabled:true
            }
        },
        tourName:'tutorial_5'
    });
    tutorial_5.addStep({
        title: `<p class='tour_header'>チュートリアル</p>`,
        text: `<p class='tour_discription'>売上計上日はここで変更します。<br><br>過去の売上を入れ忘れた場合、ここの日付を変更して売上登録をして下さい。</p>`,
        attachTo: {
            element: '.item_1',
            on: 'auto'
        },
        buttons: [
            {
                text: 'Next',
                action: tutorial_5.next
            }
        ]
    });
    tutorial_5.addStep({
        title: `<p class='tour_header'>チュートリアル</p>`,
        text: `<p class='tour_discription'>出店しているイベント名を入力します。<br><br>今回は適当に入れてください。</p>`,
        attachTo: {
            element: '.item_2',
            on: 'bottom'
        },
        buttons: [
            {
                text: 'Back',
                action: tutorial_5.back
            },
            {
                text: 'Next',
                action: tutorial_5.next
            }
        ]
    });
    tutorial_5.addStep({
        title: `<p class='tour_header'>チュートリアル</p>`,
        text: `<p class='tour_discription'>お会計はメニューをタップした数だけカウントされます。<br><br>試しに何回かタップしてみてください。</p>`,
        attachTo: {
            element: '.item_3',
            on: 'top'
        },
        buttons: [
            {
                text: 'Back',
                action: tutorial_5.back
            },
            {
                text: 'Next',
                action: tutorial_5.next
            }
        ]
    });
    tutorial_5.addStep({
        title: `<p class='tour_header'>チュートリアル</p>`,
        text: `
                <div class='btn-group btn-group-toggle' data-toggle='buttons'>
                <label class='btn btn-outline-warning' style='padding:0;' >
                    <input type='radio' autocomplete='off'>　▼　
                </label>
                </div>
                <br><p class='tour_discription'>を選択すると、メニュータップ時にマイナスされるようになります。<br><br>オーダーを多く入れすぎたときに使ってください。</p>`,
        attachTo: {
            element: '.item_4',
            on: 'top'
        },
        buttons: [
            {
                text: 'Back',
                action: tutorial_5.back
            },
            {
                text: 'Next',
                action: tutorial_5.next
            }
        ]
    });
    tutorial_5.addStep({
        title: `<p class='tour_header'>チュートリアル</p>`,
        text: `
                <div class='btn-group btn-group-toggle' data-toggle='buttons'>
                <label class='btn btn-outline-primary  style='padding:0;'>
                    <input type='radio' name='options' value='plus' autocomplete='off' checked>　▲　
                </label>
                </div>
                <br><p class='tour_discription'>を選択すると、元に戻ります。</p>`,
        attachTo: {
            element: '.item_4',
            on: 'top'
        },
        buttons: [
            {
                text: 'Back',
                action: tutorial_5.back
            },
            {
                text: 'Next',
                action: tutorial_5.next
            }
        ]
    });
    tutorial_5.addStep({
        title: `<p class='tour_header'>チュートリアル</p>`,
        text: `<p class='tour_discription'>お釣り計算機が表示されます。<!--<br><br>釣銭ボタンを押して表示してみてください。--></p>`,
        attachTo: {
            element: '.item_5',
            on: 'top'
        },
        buttons: [
            {
                text: 'Back',
                action: tutorial_5.back
            },
            {
                text: 'Next',
                action: tutorial_5.next
            }
        ]
    });
    tutorial_5.addStep({
        title: `<p class='tour_header'>チュートリアル</p>`,
        text: `<p class='tour_discription'>受取金額を入力して「計算」ボタンを押すとお釣りが表示されます。<br><br>ここでは計算するだけで、何も登録されません。</p>`,
        buttons: [
            {
                text: 'Back',
                action: tutorial_5.back
            },
            {
                text: 'Next',
                action: tutorial_5.next
            }
        ]
    });
    tutorial_5.addStep({
        title: `<p class='tour_header'>チュートリアル</p>`,
        text: `<p class='tour_discription'>再度「釣銭」ボタンを押すと計算機が非表示になります。<!--<br><br>釣銭ボタンを何度か押してしてみてください。--></p>`,
        attachTo: {
            element: '.item_5',
            on: 'top'
        },
        buttons: [
            {
                text: 'Back',
                action: tutorial_5.back
            },
            {
                text: 'Next',
                action: tutorial_5.next
            }
        ]
    });
    /*
    tutorial_5.addStep({
        title: `<p class='tour_header'>チュートリアル</p>`,
        text: `<p class='tour_discription'>お釣り計算機です。<br><br>（表示されてない場合は[BACK]を押して釣銭ボタンを押してください。</p>`,
        buttons: [
            {
                text: 'back',
                action: tutorial_5.back
            },
            {
                text: 'Next',
                action: tutorial_5.next
            }
        ]
    });
    tutorial_5.addStep({
        title: `<p class='tour_header'>チュートリアル</p>`,
        text: `<p class='tour_discription'>受取金額を入力して「計算」ボタンを押すとお釣りが表示されます。<br><br>ここでは計算するだけで、何も登録されません。</p>`,
        attachTo: {
            element: '.item_6',
            on: 'auto'
        },
        buttons: [
            {
                text: 'Next',
                action: tutorial_5.next
            }
        ]
    });
    tutorial_5.addStep({
        title: `<p class='tour_header'>チュートリアル</p>`,
        text: `<p class='tour_discription'>「閉じる」ボタンを押すか、枠外をタップすると前の画面に戻ります。</p>`,
        attachTo: {
            element: '.item_7',
            on: 'top'
        },
        buttons: [
            {
                text: 'Next',
                action: tutorial_5.next
            }
        ]
    });
    */
    tutorial_5.addStep({
        title: `<p class='tour_header'>チュートリアル</p>`,
        text: `<p class='tour_discription'>「クリア」ボタンを押すと、全ての数量を０にクリアします。</p>`,
        attachTo: {
            element: '.item_8',
            on: 'top'
        },
        buttons: [
            {
                text: 'back',
                action: tutorial_5.back
            },
            {
                text: 'Next',
                action: tutorial_5.next
            }
        ]
    });
    tutorial_5.addStep({
        title: `<p class='tour_header'>チュートリアル</p>`,
        text: `<p class='tour_discription'>「確認」ボタンを押すと、注文したメニューのみが表示されます。<br><br>この状態で注文結果の最終確認を行います。<br><br>ボタン名が「確認」から「登録」に変更されます。</p>`,
        buttons: [
            {
                text: 'Back',
                action: tutorial_5.back
            },
            {
                text: 'Next',
                action: tutorial_5.next
            }
        ]
    });
    tutorial_5.addStep({
        title: `<p class='tour_header'>チュートリアル</p>`,
        text: `<p class='tour_discription'>「確認」ボタンを押して下さい。</p>`,
        attachTo: {
            element: '.item_9',
            on: 'top'
        },
        buttons: [
            {
                text: 'Back',
                action: tutorial_5.back
            },
            {
                text: 'Next',
                action: tutorial_5.next
            }
        ]
    });
    tutorial_5.addStep({
        title: `<p class='tour_header'>チュートリアル</p>`,
        text: `<p class='tour_discription'>注文内容を修正したい場合は「戻る」ボタンを押すと、ひとつ前の状態に戻ります。
                <br>表示が「クリア」のままの場合、「Back」をタップして「確認」を押してください。
                <br><br><span style='color:red;'>今回は「戻る」は押さずに「NEXT」をタップしてください。</span></p>`,
        attachTo: {
            element: '.item_10',
            on: 'top'
        },
        buttons: [
            {
                text: 'Back',
                action: tutorial_5.back
            },
            {
                text: 'Next',
                action: tutorial_5.next
            }
        ]
    });
    tutorial_5.addStep({
        title: `<p class='tour_header'>チュートリアル</p>`,
        text: `<p class='tour_discription'>次に、「割引・割増」について説明します。</p>`,
        buttons: [
            {
                text: 'Back',
                action: tutorial_5.back
            },
            {
                text: 'Next',
                action: tutorial_5.next
            }
        ]
    });
    /*
    tutorial_5.addStep({
        title: `<p class='tour_header'>チュートリアル</p>`,
        text: `<p class='tour_discription'>「割引・割増」ボタンが表示されてない場合、下の「確認」ボタンを押してください。</p>`,
        attachTo: {
            element: '.item_9',
            on: 'top'
        },
        buttons: [
            {
                text: 'Back',
                action: tutorial_5.back
            },
            {
                text: 'Next',
                action: tutorial_5.next
            }
        ]
    });
    */
    tutorial_5.addStep({
        title: `<p class='tour_header'>チュートリアル</p>`,
        text: `<p class='tour_discription'>「割引・割増」ボタンをタップしてください。
                <br>
                <br>ボタンが表示されていない場合、「Back」ボタンで前に戻り、「確認」ボタンを押してください。</p>`,
        attachTo: {
            element: '.item_11',
            on: 'bottom'
        },
        buttons: [
            {
                text: 'Back',
                action: tutorial_5.back
            },
            {
                text: 'Next',
                action: tutorial_5.next
            }
        ]
    });
    tutorial_5.addStep({
        title: `<p class='tour_header'>チュートリアル</p>`,
        text: `<p class='tour_discription'>まとめ買いに対する割引や、サービス時間超過に対する追加料金等で<span style='color:red;'>『支払総額』を変更したい場合</span>に使用します。</p>`,
        buttons: [
            {
                text: 'Back',
                action: tutorial_5.back
            },
            {
                text: 'Next',
                action: tutorial_5.next
            }
        ]
    });
    tutorial_5.addStep({
        title: `<p class='tour_header'>チュートリアル</p>`,
        text: `<p class='tour_discription'>割引割増を使用すると、売上げの実績は以下の表に登録されます。
                <br>
                <br>例：割引（3,300円を3,000円）<br>商品A：1,100円<br>商品B：1,100円<br>商品C：1,100円<br><span style='color:red;'>割引：-300円</span></p>`,
        buttons: [
            {
                text: 'Back',
                action: tutorial_5.back
            },
            {
                text: 'Next',
                action: tutorial_5.next
            }
        ]
    });
    tutorial_5.addStep({
        title: `<p class='tour_header'>チュートリアル</p>`,
        text: `<p class='tour_discription'>「変更後の金額を入力」をタップし、割引後・割増後の総額を入力すると、下のお会計額が変更されます。</p>`,
        attachTo: {
            element: '.item_12',
            on: 'bottom'
        },
        buttons: [
            {
                text: 'Back',
                action: tutorial_5.back
            },
            {
                text: 'Next',
                action: tutorial_5.nextAndSave
            }
        ]
    });
    tutorial_5.addStep({
        title: `<p class='tour_header'>チュートリアル</p>`,
        text: `<p class='tour_discription'>最後に「登録」ボタンを押すと、売上げの登録が完了します。</p>`,
        attachTo: {
            element: '.item_9',
            on: 'top'
        },
        buttons: [
            {
                text: 'Back',
                action: tutorial_5.back
            },
            {
                text: 'Next',
                action: tutorial_5.next
            }
        ]
    });
    tutorial_5.addStep({
        title: `<p class='tour_header'>チュートリアル</p>`,
        text: `<p class='tour_discription'>いろいろ操作してみて最後に「登録」をタップしてください。</p>`,
        buttons: [
            {
                text: 'Back',
                action: tutorial_5.back
            },
            {
                text: 'Next',
                action: tutorial_5.next
            }
        ]
    });





    const tutorial_6 = new Shepherd.Tour({
        useModalOverlay: true,
        defaultStepOptions: {
            classes: 'tour_modal',
            scrollTo: false,
            cancelIcon:{
                enabled:true
            }
        },
        tourName:'tutorial_6'
    });
    tutorial_6.addStep({
        title: `<p class='tour_header'>チュートリアル</p>`,
        text: `<p class='tour_discription'>売上が登録されると画面上部に緑色のメッセージバーが表示されます。
                <br>(しばらくすると自動で消えます。)</p>`,
        buttons: [
            {
                text: 'Next',
                action: tutorial_6.next
            }
        ]
    });
    tutorial_6.addStep({
        title: `<p class='tour_header'>チュートリアル</p>`,
        text: `<p class='tour_discription'>次に、レジ画面のカテゴリー別表示についてです。</p>`,
        buttons: [
            {
                text: 'Next',
                action: tutorial_6.next
            }
        ]
    });
    tutorial_6.addStep({
        title: `<p class='tour_header'>チュートリアル</p>`,
        text: `<p class='tour_discription'>商品登録時にカテゴリーを設定すると、設定したカテゴリーごとに商品を纏めて表示ます。</p>`,
        buttons: [
            {
                text: 'Next',
                action: tutorial_6.nextAndSave
            }
        ]
    });
    tutorial_6.addStep({
        title: `<p class='tour_header'>チュートリアル</p>`,
        text: `<p class='tour_discription'>ここをタップするとカテゴリー別表示に変更されます。<br>試しにタップしてください。</p>`,
        attachTo: {
            element: '.item_15',
            on: 'bottom'
        },
        buttons: [
            {
                text: 'Back',
                action: tutorial_6.back
            },
            {
                text: 'Next',
                action: tutorial_6.next
            }
        ]
    });

    const tutorial_7 = new Shepherd.Tour({
        useModalOverlay: true,
        defaultStepOptions: {
            classes: 'tour_modal',
            scrollTo: false,
            cancelIcon:{
                enabled:true
            }
        },
        tourName:'tutorial_7'
    });
    tutorial_7.addStep({
        title: `<p class='tour_header'>チュートリアル</p>`,
        text: `<p class='tour_discription'>カテゴリー別表示の場合、ここのリストにカテゴリーが表示されるようになります。<br>目的のカテゴリーを選択すると、その付近まで画面が自動でスライドするようになります</p>`,
        attachTo: {
            element: '.item_16',
            on: 'bottom'
        },
        buttons: [
            {
                text: 'Next',
                action: tutorial_7.next
            }
        ]
    });
    tutorial_7.addStep({
        title: `<p class='tour_header'>チュートリアル</p>`,
        text: `<p class='tour_discription'><i class="fa-solid fa-arrow-rotate-right fa-lg  awesome-color-panel-border-same"></i>をタップするごとにカテゴリーの粒度が「大→中→小→分別なし」の順で切り替わるので、ご自由に設定して下さい。</p>`,
        buttons: [
            {
                text: 'Back',
                action: tutorial_7.back
            },
            {
                text: 'Next',
                action: tutorial_7.next
            }
        ]
    });
    tutorial_7.addStep({
        title: `<p class='tour_header'>チュートリアル</p>`,
        text: `<p class='tour_discription'>「商品登録～レジの使い方」までの説明は以上となります。</p>`,
        buttons: [
            {
                text: 'Back',
                action: tutorial_7.back
            },
            {
                text: 'Next',
                action: tutorial_7.next
            }
        ]
    });
    tutorial_7.addStep({
        title: `<p class='tour_header'>チュートリアル</p>`,
        text: `<p class='tour_discription'>次はレジで登録した売上げの確認に移ります。</p>`,
        buttons: [
            {
                text: 'Back',
                action: tutorial_7.back
            },
            {
                text: 'Next',
                action: tutorial_7.next
            }
        ]
    });
    tutorial_7.addStep({
        title: `<p class='tour_header'>チュートリアル</p>`,
        text: `<p class='tour_discription'>画面上部の「WebRez＋」をタップしてメニュー画面に戻ってください。
                <br>
                <br><span style='font-size:1rem;color:green;'>※進捗を保存しました。</span>
                </p>`,
        buttons: [
            {
                text: 'Back',
                action: tutorial_7.back
            },
            {
                text: 'Next',
                action: tutorial_7.complete
            }
        ]
    });
    
    if(TourMilestone=="tutorial_4"){
        tutorial_5.start(tourFinish,'tutorial','');  
    }else if(TourMilestone=="tutorial_5"){
        tutorial_6.start(tourFinish,'tutorial','');
    }else if(TourMilestone=="tutorial_6"){
        tutorial_7.start(tourFinish,'tutorial','save');
    }
    

</script>
</html>
<?php
$stmt = null;
$pdo_h = null;
?>


