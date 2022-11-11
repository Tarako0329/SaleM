<?php
{
//<!--Evregi.php-->    
//ヘッド処理
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
$log_time = date("Y/m/d H:i:s");
//セッションのIDがクリアされた場合の再取得処理。
$rtn=check_session_userid($pdo_h);
$logfilename="sid_".$_SESSION['user_id'].".log";
//file_put_contents("sql_log/".$logfilename, $log_time.",REFERER:".$_SERVER['HTTP_REFERER']."\n", FILE_APPEND);

$status=(!empty($_SESSION["status"])?$_SESSION["status"]:"");
//$event_aria=(empty($_COOKIE["address"])?"":urldecode($_COOKIE["address"]));
//$event_aria="住所サンプル";
$_SESSION["status"]="";
$HTTP_REFERER=(empty($_SERVER['HTTP_REFERER'])?"":$_SERVER['HTTP_REFERER']);

if(EXEC_MODE!=""){
//file_put_contents("sql_log/".$logfilename,$log_time.",cookie :".$_COOKIE['csrf_token']."\n",FILE_APPEND);
//file_put_contents("sql_log/".$logfilename,$log_time.",post   :".$_POST['csrf_token']."\n",FILE_APPEND);
//file_put_contents("sql_log/".$logfilename,$log_time.",session:".$_SESSION['csrf_token']."\n",FILE_APPEND);
//if(!empty($_POST)){
    if(csrf_chk_nonsession()==false){//POST:COOKIEチェック
        //if(!empty($status) && ROOT_URL."EVregi.php"==substr($_SERVER['HTTP_REFERER'],0,strlen(ROOT_URL."EvRegi.php"))){
        if(!empty($status) && ROOT_URL."EVregi.php"==substr($HTTP_REFERER,0,strlen(ROOT_URL."EvRegi.php"))){
            //リファイラが自身でかつstatusがセットされてる場合、問題なし
        }else if($status=="longin_redirect"){
            //$_SESSION["status"]="";
        }else{
            $_SESSION["EMSG"]="セッションが正しくありませんでした。".filter_input(INPUT_POST,"csrf_token");
            header("HTTP/1.1 301 Moved Permanently");
            header("Location: index.php");
            exit();
        }
    }
}


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
    $root_url = bin2hex(openssl_encrypt(ROOT_URL, 'AES-128-ECB', 1));
    $dir_path =  bin2hex(openssl_encrypt(dirname(__FILE__)."/", 'AES-128-ECB', 1));
    
    //echo row[0]["yuukoukigen"] ;
    $emsg="お試し期間、もしくは解約後有効期間が終了しました。<br>継続してご利用頂ける場合は<a href='".PAY_CONTRACT_URL."?system=".$title."&sysurl=".$root_url."&dirpath=".$dir_path."'>こちらから本契約をお願い致します </a>";
}


$token = csrf_create();

$alert_msg=(!empty($_SESSION["msg"])?$_SESSION["msg"]:"");
$RG_MODE=(!empty($_POST["mode"])?$_POST["mode"]:$_GET["mode"]);

if($RG_MODE==""){
    file_put_contents("sql_log/".$logfilename,$log_time.",post   :".var_dump($_POST)."\n",FILE_APPEND);
    echo "error rezi mode nothing!";
    exit();
}

//イベント名の取得
//セッション -> DB

$event = (!empty($_SESSION["EV"])?$_SESSION["EV"]:"");
if(empty($event)){
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
//セッション-> DB
$categoly=(!empty($_SESSION["CTGL"])?$_SESSION["CTGL"]:"");
if(empty($categoly)){
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
//echo $next_categoly;
$sqlorder="";
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
$sql = "select *,".$sql_select." from ShouhinMS where uid = ? ".$sqlorder;

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

//今日の売上
$sql = "select * from UriageData where uid = ? and UriDate = ? order by insDatetime desc";
$stmt = $pdo_h->prepare($sql);
$stmt->bindValue(1, $_SESSION['user_id'], PDO::PARAM_INT);
$stmt->bindValue(2, (string)date("Y-m-d"), PDO::PARAM_STR);
$stmt->execute();
$UriageList = $stmt->fetchAll();

}
?>
<!DOCTYPE html>
<html lang='ja'>
<head>
    <?php 
    //共通部分、bootstrap設定、フォントCND、ファビコン等
    include "head.html" 
    ?>
    <!--ページ専用CSS-->
    <link rel="stylesheet" href="css/style_EVregi.css?<?php echo $time; ?>" >
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-cookie/1.4.1/jquery.cookie.js"></script>
    <TITLE><?php echo $title." レジ";?></TITLE>
</head>

<script>
//フッター合計支払金額を保持
var total_pay = 0;     //税込総支払額
var total_zei = 0;     //総支払額の内税
var total_pay_bk = 0;  //値引値増前の金額を保持し、会計確定せずに戻る際にtotal_payに返す

window.onload = function() {

    // オブジェクトと変数の準備
    var kaikei_disp = document.getElementById("kaikei");
    var zei_disp = document.getElementById("utizei");

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
    var su = document.getElementsByClassName("su");
    var items = document.getElementsByClassName("items");   //全メニュー
    var show_items = document.getElementsByClassName("show_items"); //表示対象メニュー
    order_chk.onclick = function(){
        //注文確認ボタン。選択されてないメニューを消し、ボタンの表示を変更する。
        for (let i = 0; i < su.length; i++) {
            if(su.item(i).value == 0){
                items.item(i).style.display = 'none';
            }
        }
        order_chk.style.display = 'none';
        btn_commit.style.display = 'block';
        dentaku.style.display = 'block';
        order_return.style.display = 'block';
        order_clear.style.display = 'none';
        <?php
            if($RG_MODE<>"shuppin_zaiko"){ echo "CHOUSEI_AREA.style.display = 'block';\n";} //在庫登録モードでは割引ボタンを表示しない
        ?>
        //total_pay_bk = total_pay; //調整前金額を保存
        //位置情報取得
        get_gio();
    };
    
    dentaku.onclick = function(){
        //電卓モーダルに会計金額を表示
        seikyuu.innerHTML = total_pay.toLocaleString();
    };


    var reset_btn = document.getElementById("order_clear");
    var return_btn = document.getElementById("order_return");
    // リセットボタンのクリック処理
    reset_btn.onclick = function (){
        for (let i = 0; i < su.length; i++) {
            su.item(i).value = 0;
        }
        for (let i = 0; i < show_items.length; i++) {
            show_items.item(i).style.display = 'block';
        }
        kaikei_disp.innerHTML = 0;
        total_pay = 0;
        total_pay_bk = 0;
        zei_disp.innerHTML = 0;
        total_zei = 0;
        order_chk.style.display = 'block';
        btn_commit.style.display = 'none';
     };
    //戻るボタン
    return_btn.onclick = function(){
        for (let i = 0; i < show_items.length; i++) {
            show_items.item(i).style.display = 'block';
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
        maekin.innerHTML = total_pay_bk.toLocaleString();
    }
    CHOUSEI_GAKU.onchange = function(){
        total_pay = CHOUSEI_GAKU.value;
        kaikei_disp.innerHTML = Number(total_pay).toLocaleString();
    }

     var plus_mode = document.getElementById("plus_mode");
     var minus_mode = document.getElementById("minus_mode");
     var minus_disp = document.getElementsByClassName('minus_disp');
     minus_mode.onclick = function(){
        for (let i = 0; i < minus_disp.length; i++) {
            minus_disp.item(i).style.display = 'block';
        }
     }
     plus_mode.onclick = function(){
        for (let i = 0; i < minus_disp.length; i++) {
            minus_disp.item(i).style.display = 'none';
        }
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
    <input type="hidden" name="mode" value='<?php echo $RG_MODE;?>'> <!--レジor個別売上or在庫登録-->
    
<header class="header-color common_header" style='display:block'>
    <div class="title yagou"><a href="menu.php"><?php echo $title;?></a></div>
    <span class='item_1'>
    <span style='color:var(--user-disp-color);font-weight:400;'>
    <?php if($RG_MODE=="shuppin_zaiko"){echo "出店日：";}else{echo "売上日：";}?>
    </span><input type='date' class='date' style='height:20%' name='KEIJOUBI' required="required" value='<?php if($RG_MODE<>"shuppin_zaiko"){echo (string)date("Y-m-d");} ?>'>
    </span>
    <?php
    if($RG_MODE=="kobetu"){
        echo "<input type='text' class='ev' name='KOKYAKU' required='required' placeholder='顧客名'>\n";
        echo "<input type='hidden' name='EV' value=''>\n";
    }else{
        echo "<input type='text' class='ev item_2' name='EV' value='". $event."' required='required' placeholder='イベント名等'>\n";
        echo "<input type='hidden' name='KOKYAKU' value=''>\n";
    }
    
    $_SESSION["nonadd"]=(!empty($_SESSION["nonadd"])?$_SESSION["nonadd"]:"");
    if($RG_MODE!=="evrez"){
        $dispnone="display:none;";
        $checked="checked";
    }else{
        $dispnone="";
        $checked="";
        $_SESSION["nonadd"]="";
    }
    ?>
    <div class='address_disp' style='<?php echo $dispnone; ?> position:fixed;top:55px;right:5px;color:var(--user-disp-color);max-width:50%;height:15px;'>
        <input type='checkbox' name='nonadd' id='nonadd' onclick='gio_onoff()' <?php echo $_SESSION["nonadd"].$checked; ?>>
        <label for='nonadd' id='address_disp' class='item_101' title='' <?php if($_SESSION["nonadd"]=="checked"){echo "style='text-decoration:line-through;'";} ?> ><?php //echo $event_aria; ?></label>
    </div>
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
    <a class='item_15' href='javascript:void(0)' onClick="postFormRG('EVregi_sql.php','<?php echo $RG_MODE; ?>','<?php echo $next_categoly; ?>')" style='color:inherit;margin-left:10px;margin-right:10px;margin-top:5px;'>
        <i class="fa-solid fa-arrow-rotate-right fa-lg logoff-color"></i>
    </a>
</div>
<div class='header-plus-minus text-center item_4' style='font-size:1.4rem;font-weight;700'>
    <span style='position:fixed;top:95px;left:10px;' id='gio_exec'></span><!--開発モードでGET_GIO実行時の通知に使用-->
    <i class="fa-regular fa-circle-question fa-lg logoff-color"></i><!--スペーシングのため白アイコンを表示-->
    <div class='btn-group btn-group-toggle' style='padding:0;' data-toggle='buttons'>
        <label class='btn btn-outline-primary active' id='plus_mode'>
            <input type='radio' name='options' value='plus' autocomplete='off' checked>　▲　
        </label>
        <label class='btn btn-outline-warning' id='minus_mode'>
            <input type='radio' name='options' value='minus' autocomplete='off'>　▼　
        </label>
    </div>
    <a href="#" style='color:inherit;margin-left:5px;margin-top:10px;' data-toggle='modal' data-target='#modal_help2'>
        <i class="fa-regular fa-circle-question fa-lg awesome-color-panel-border-same"></i>
    </a>
    <a href="#" style='color:inherit;position:fixed;top:110px;right:10px;' data-toggle='modal' data-target='#modal_uriagelist'>
        <i class="fa-solid fa-cash-register fa-2x awesome-color-panel-border-same"></i>
    </a>
    
    
</div>
<body class='common_body'>
   
<?php
    if(isset($emsg)){//
        echo $emsg;
        echo "</body></html>";
        exit();
    }
    if($status=="success"){
        echo "<div class='container'><div class='row'><div class='col-12'><div style='padding-top:5px;text-align:center;font-size:1.5rem;' id='alert-s' class='lead'></div></div></div></div>";
    }elseif($status=="failed"){
        echo "<div class='container'><div class='row'><div class='col-12'><div style='padding-top:5px;text-align:center;font-size:1.5rem;' id='alert-e' class='lead'></div></div></div></div>";
    }
    //$_SESSION["status"]="";
?>
    <div class="container-fluid">
        <div class='item_11 item_12'>
            <div class="row text-center" style='padding-top:5px;display:none;' id='CHOUSEI_AREA'>
                <hr>
                <div>
                    <button type='button' class='btn-view btn-changeVal ' style="padding:0.1rem;width:300px;font-size:2.2rem;" id="CHOUSEI_BTN" >割引・割増</button>
                </div>
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
    $disp=""; //ボタンの表示非表示（showを表示。ブランクは非表示）
    $style="";
    
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
        if($RG_MODE=="shuppin_zaiko"){
            //在庫登録モードは全メニュー表示
	        $disp="show_items";
	        $style="";
        }else{
	        $disp=($row["hyoujiKBN1"]=="on"?"show_items":"");
	        $style=($row["hyoujiKBN1"]=="on"?"":" style='display:none' ");
        }
	    
        echo "  <div class ='col-md-3 col-sm-6 col-6 items ".$disp."' ".$style." id='items_".$row["shouhinCD"]."'>\n";
        echo "      <button type='button' class='btn-view btn--rezi' id='btn_menu_".$row["shouhinCD"]."' onclick='order_cnt(".$row["tanka"].",".$row["tanka_zei"].",getElementById(\"suryou_".$row["shouhinCD"]."\"))'>".($row["shouhinNM"])."\n";
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
        //注文ボタン
        function order_cnt(TANKA,TANKA_ZEI,obj_id_SHOUHIN_cnt){
            let plus_minus = document.getElementsByName('options');
            let kaikei_disp = document.getElementById("kaikei");
            let zei_disp = document.getElementById("utizei");
            
            if(plus_minus[0].checked){//通常モード（プラス）
                  total_pay += TANKA + TANKA_ZEI;
                  total_pay_bk += TANKA + TANKA_ZEI;
                  total_zei += TANKA_ZEI;
    
                  obj_id_SHOUHIN_cnt.value = parseInt(obj_id_SHOUHIN_cnt.value) + 1;
                  kaikei_disp.innerHTML = total_pay.toLocaleString();
                  zei_disp.innerHTML = total_zei.toLocaleString();
            }else if(plus_minus[1].checked){//減らすモード（マイナス）
                  if(parseInt(obj_id_SHOUHIN_cnt.value)==0){
                      window.alert('数量０以下には出来ません');
                      return;
                  }
                  total_pay -= TANKA + TANKA_ZEI;
                  total_pay_bk -= TANKA + TANKA_ZEI;
                  total_zei -= TANKA_ZEI;
    
                  obj_id_SHOUHIN_cnt.value = parseInt(obj_id_SHOUHIN_cnt.value) - 1;
                  kaikei_disp.innerHTML = total_pay.toLocaleString();
                  zei_disp.innerHTML = total_zei.toLocaleString();
            }
        }
    
        
    </script>
</body>

<footer class='rezfooter'>
    <div class='kaikei'>
        <span style='font-size:1.6rem;'>お会計</span> ￥<span id='kaikei'>0</span>- <span style='font-size:1.6rem;'>内税</span>(<span id='utizei'>0</span>)
    </div>
    <div class='right1'>
        <button type='button' class='btn--chk item_5' style='border-left:none;border-right:none;' id='dentaku' data-toggle='modal' data-target='#FcModal'><?php if($RG_MODE<>"shuppin_zaiko"){echo "釣　銭";} ?></button>
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
<input type='hidden' name='address' id='address' value='<?php echo (empty($_COOKIE["address"])?"":$_COOKIE["address"]); ?>'>
<input type='hidden' name='lat' id='lat' value='<?php echo (empty($_COOKIE["lat"])?"":$_COOKIE["lat"]); ?>'>
<input type='hidden' name='lon' id='lon' value='<?php echo (empty($_COOKIE["lon"])?"":$_COOKIE["lon"]); ?>'>

</form>
<!--モーダル電卓(FcModal)-->
<div class='modal fade' id='FcModal' tabindex='-1' role='dialog' aria-labelledby='basicModal' aria-hidden='true'>
    <div class='modal-dialog  modal-dialog-centered'>
        <div class='modal-content item_6' style='font-size: 3.0rem; font-weight: 800;'>
            <div class='modal-header'>
                <!--<div class='modal-title' id='myModalLabel'>電　卓</div>-->
            </div>
            <div class='modal-body'>
                
                <!--電卓-->
                <table style='margin:auto;width:86%'>
                    <tbody>
                    <tr><td colspan='3' style='text-align:center;background:lightgreen;color:#fff;font-size:2.0rem;font-weight:600;'>計　算</td></tr>
                    <tr><td class='dentaku--cellsize'><button class='btn btn-primary btn--dentaku' onclick='dentaku_btn(this)'>7</button></td><td class='dentaku--cellsize'><button class='btn btn-primary btn--dentaku' onclick='dentaku_btn(this)'>8</button></td><td class='dentaku--cellsize'><button class='btn btn-primary btn--dentaku' onclick='dentaku_btn(this)'>9</button></td></tr>
                    <tr><td class='dentaku--cellsize'><button class='btn btn-primary btn--dentaku' onclick='dentaku_btn(this)'>4</button></td><td class='dentaku--cellsize'><button class='btn btn-primary btn--dentaku' onclick='dentaku_btn(this)'>5</button></td><td class='dentaku--cellsize'><button class='btn btn-primary btn--dentaku' onclick='dentaku_btn(this)'>6</button></td></tr>
                    <tr><td class='dentaku--cellsize'><button class='btn btn-primary btn--dentaku' onclick='dentaku_btn(this)'>1</button></td><td class='dentaku--cellsize'><button class='btn btn-primary btn--dentaku' onclick='dentaku_btn(this)'>2</button></td><td class='dentaku--cellsize'><button class='btn btn-primary btn--dentaku' onclick='dentaku_btn(this)'>3</button></td></tr>
                    <tr><td class='dentaku--cellsize'><button class='btn btn-primary btn--dentaku' onclick='dentaku_btn(this)'>0</button></td><td class='dentaku--cellsize'><button class='btn btn-primary btn--dentaku' onclick='dentaku_btn(this)'>00</button></td><td class='dentaku--cellsize'><button class='btn btn-primary btn--dentaku' onclick='dentaku_btn(this)'>C</button></td></tr>
                    <tr><td colspan='3' class='dentaku--cellsize'><button class='btn btn-primary btn--dentaku' onclick='dentaku_btn(this)'>ちょうど</button></td></tr>
                    </tbody>
                </table>
                
                <div style='margin:0 7%'>
                <input type='hidden' id='azukari_val'>
                <input type='hidden' id='oturi_val'>

                <p>お預り：￥<span id='azukari'>0</span></p>
                <p>お会計：￥<span id='seikyuu'>0</span></p>
                <p>お釣り：￥<span id='oturi'>0</span></p>    
                </div>
            <div class='modal-footer'>
                <button type='button'  class='item_7 btn btn-primary' data-dismiss='modal' style='font-size: 2.0rem;width:100%;'>閉じる</button>
            </div>
            <script>
                function dentaku_btn(btn){
                    let azukarikin = azukari_val.value;
                    const seikyu = total_pay;
                    let oturikin
                    
                    if(btn.innerHTML =='C'){
                        azukari_val.value = 0;
                        azukari.innerHTML = '0';
                        azukarikin = '0';
                    }else if(btn.innerHTML =='ちょうど'){
                        azukarikin = seikyu;
                        azukari_val.value = Number(seikyu);
                        azukari.innerHTML = Number(seikyu).toLocaleString();
                    }else{
                        azukarikin = "" + azukarikin + btn.innerHTML;
                        azukari_val.value = Number(azukarikin);
                        azukari.innerHTML = Number(azukarikin).toLocaleString();
                    }
                    oturikin = azukarikin - seikyu;
                    
                    oturi_val.value = oturikin;
                    oturi.innerHTML = oturikin.toLocaleString();
                }
            </script>
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

<!--売上リスト-->
<div class='modal fade' id='modal_uriagelist' tabindex='-1' role='dialog' aria-labelledby='basicModal' aria-hidden='true'>
    <div class='modal-dialog  modal-dialog-centered' >
        <div class='modal-content' style='font-size:1.2rem; font-weight:400;'>
            <div class='modal-header'>
                <div class='container'><div class='row'><div class='col-12'><div style='padding-top:5px;text-align:center;font-size:1.5rem;font-weight:600;' class='lead alert-success'>本日の売上</div></div></div></div>
            </div>
            <div class='modal-body'>
                <div class='urilist'>
                <table class="table table-sm" style='font-family:"Meiryo UI";'>
                    <thead class='header-color' style='color:var(--title-color);'>
                        <tr>
                            <th>No</th>
                            <th>時刻</th>
                            <th>商品</th>
                            <th>数量</th>
                            <th>税込売上</th>
                        </tr>
                    </thead>
                    <tbody >
                        <?php
                        $No=0;
                        $color="";
                        $Goukei = 0;
                        foreach($UriageList as $row){
                            if($No == 0){
                                $color="class='table-success'";
                                $No=$row["UriageNO"];
                            }elseif($No != $row["UriageNO"]){
                                if(empty($color)){
                                    $color="class='table-active'";
                                }else{
                                    $color="";
                                }
                                $No=$row["UriageNO"];
                            }
                            $ZeikomiUri=$row["UriageKin"] + $row["zei"];
                            $Goukei = $Goukei + $ZeikomiUri;
                            echo "<tr ".$color."><td>".$row["UriageNO"]."</td>";
                            echo "<td>".substr($row["insDatetime"],11)."</td>";
                            echo "<td>".$row["ShouhinNM"]."</td>";
                            echo "<td>".$row["su"]."</td>";
                            echo "<td>".return_num_disp($ZeikomiUri)."</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
                </div>
            </div>
            <div class='modal-footer' style='font-size:2.5rem;font-weight:600;'>
                合計：<?php echo return_num_disp($Goukei); ?> 円
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
    //チュートリアル
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
        text: `<p class='tour_discription'>大まかな出店場所を表示してます。<br><br>端末のGPS機能を使用してます。<br>明らかに変な住所が表示されている場合はチェックを入れて無効にしてください。</p>`,
        attachTo: {
            element: '.item_101',
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
    

</script><!--チュートリアル-->
<script>
    //在庫機能のヘルプ
    const shuppin_zaiko_help2 = new Shepherd.Tour({
        useModalOverlay: true,
        defaultStepOptions: {
            classes: 'tour_modal',
            scrollTo: false,
            cancelIcon:{
                enabled:true
            }
        },
        tourName:'shuppin_zaiko_help2'
    });
    shuppin_zaiko_help2.addStep({
        title: `<p class='tour_header'>出品在庫機能</p>`,
        text: `<p class='tour_discription'>在庫の登録画面はレジ画面とほぼ同じです。</p>`,
        buttons: [
            {
                text: 'Next',
                action: shuppin_zaiko_help2.next
            }
        ]
    });
    shuppin_zaiko_help2.addStep({
        title: `<p class='tour_header'>出品在庫機能</p>`,
        text: `<p class='tour_discription'>イベント等の出店日を指定します。</p>`,
        attachTo: {
            element: '.item_1',
            on: 'auto'
        },
        buttons: [
            {
                text: 'Next',
                action: shuppin_zaiko_help2.next
            }
        ]
    });
    shuppin_zaiko_help2.addStep({
        title: `<p class='tour_header'>出品在庫機能</p>`,
        text: `<p class='tour_discription'>出店予定のイベント名を入力します。</p>`,
        attachTo: {
            element: '.item_2',
            on: 'bottom'
        },
        buttons: [
            {
                text: 'Back',
                action: shuppin_zaiko_help2.back
            },
            {
                text: 'Next',
                action: shuppin_zaiko_help2.next
            }
        ]
    });
    shuppin_zaiko_help2.addStep({
        title: `<p class='tour_header'>出品在庫機能</p>`,
        text: `<p class='tour_discription'>後は通常のレジと同じ要領で、商品名を出品予定の数だけタップして、「確認」⇒「登録」と進みます。</p>`,
        buttons: [
            {
                text: 'Back',
                action: shuppin_zaiko_help2.back
            },
            {
                text: 'Next',
                action: shuppin_zaiko_help2.next
            }
        ]
    });
    shuppin_zaiko_help2.addStep({
        title: `<p class='tour_header'>出品在庫機能（修正について）</p>`,
        text: `<p class='tour_discription'>登録した内容が間違えていた場合、同じ日付・同じイベント名を入力し、全て打ち直して登録して下さい。
                <br>すると、前回登録した内容は削除され、今回入力した内容が反映されます。</p>`,
        buttons: [
            {
                text: 'Back',
                action: shuppin_zaiko_help2.back
            },
            {
                text: 'Next',
                action: shuppin_zaiko_help2.next
            }
        ]
    });
    shuppin_zaiko_help2.addStep({
        title: `<p class='tour_header'>出品在庫機能（削除について）</p>`,
        text: `<p class='tour_discription'>登録した内容を削除したい場合、誤って登録した日付とイベント名を指定し、何も商品を選択せず、空で登録して下さい。</p>`,
        buttons: [
            {
                text: 'Back',
                action: shuppin_zaiko_help2.back
            },
            {
                text: 'Next',
                action: shuppin_zaiko_help2.next
            }
        ]
    });
    shuppin_zaiko_help2.addStep({
        title: `<p class='tour_header'>出品在庫機能（登録結果について）</p>`,
        text: `<p class='tour_discription'>登録した内容は「売上実績」画面を「商品集計」モードで表示すると、「出品数」という項目で確認することが出来ます。</p>`,
        buttons: [
            {
                text: 'Back',
                action: shuppin_zaiko_help2.back
            },
            {
                text: 'Next',
                action: shuppin_zaiko_help2.next
            }
        ]
    });
    shuppin_zaiko_help2.addStep({
        title: `<p class='tour_header'>出品在庫機能</p>`,
        text: `<p class='tour_discription'>出品在庫機能の説明は以上で終了です。</p>`,
        buttons: [
            {
                text: 'Back',
                action: shuppin_zaiko_help2.back
            },
            {
                text: 'complete',
                action: shuppin_zaiko_help2.complete
            }
        ]
    });
    
    function help(){
        shuppin_zaiko_help2.start(tourFinish,'','');
    }
</script><!--出品在庫機能help-->
<script>
    //天気機能のリリース
    const new_releace_002 = new Shepherd.Tour({
        useModalOverlay: true,
        defaultStepOptions: {
            classes: 'tour_modal',
            scrollTo: false,
            cancelIcon:{
                enabled:true
            }
        },
        tourName:'new_releace_002'
    });
    new_releace_002.addStep({
        title: `<p class='tour_header'>新規機能追加のお知らせ</p>`,
        text: `<p class='tour_discription'>大まかな出店場所を表示してます。<br><br>端末のGPS機能を使用してます。</p>`,
        attachTo: {
            element: '.item_101',
            on: 'bottom'
        },
        buttons: [
            {
                text: 'Back',
                action: new_releace_002.back
            },
            {
                text: 'Next',
                action: new_releace_002.next
            }
        ]
    });
    new_releace_002.addStep({
        title: `<p class='tour_header'>新規機能追加のお知らせ</p>`,
        text: `<p class='tour_discription'>ここに表示されている住所から天気・気温を取得します。<br><br>明らかに変な住所が表示されている場合はチェックを入れて無効にしてください。</p>`,
        attachTo: {
            element: '.item_101',
            on: 'bottom'
        },
        buttons: [
            {
                text: 'Back',
                action: new_releace_002.back
            },
            {
                text: 'Next',
                action: new_releace_002.next
            }
        ]
    });
    new_releace_002.addStep({
        title: `<p class='tour_header'>新規機能追加のお知らせ</p>`,
        text: `<p class='tour_discription'>次の売上から、『売上実績』の画面に売上時の天気、気温が表示されるようになります。</p>`,
        buttons: [
            {
                text: 'Back',
                action: new_releace_002.back
            },
            {
                text: 'Next',
                action: new_releace_002.next
            }
        ]
    });
    new_releace_002.addStep({
        title: `<p class='tour_header'>新規機能追加のお知らせ</p>`,
        text: `<p class='tour_discription'>今回の追加機能は以上となります。</p>`,
        buttons: [
            {
                text: 'Back',
                action: new_releace_002.back
            },
            {
                text: 'OK',
                action: new_releace_002.next
            }
        ]
    });
     if(TourMilestone=="new_releace_002"){
        new_releace_002.start(tourFinish,'new_releace_002','finish');
    }
   
</script><!--天気機能リリースヘルプ（次回機能リリース時は不要となる）-->
<script>
    function postFormRG(url,mode,CTGL) {
     
        var form = document.createElement('form');
        var request_mode = document.createElement('input');
        var request_CTGL = document.createElement('input');
     
        form.method = 'POST';
        form.action = url;
     
        request_mode.type = 'hidden'; //入力フォームが表示されないように
        request_mode.name = 'mode';
        request_mode.value = mode;
    
        request_CTGL.type = 'hidden'; //入力フォームが表示されないように
        request_CTGL.name = 'CTGL';
        request_CTGL.value = CTGL;
     
        form.appendChild(request_mode);
        form.appendChild(request_CTGL);
        document.body.appendChild(form);
     
        form.submit();
     
    }    
</script>
<script>
    /*ジオ・コーディング*/
    /** 変換表を入れる場所 */
    var GSI = {};
    let today = new Date();
    today.setTime(today.getTime() + 10*60*60*1000);
    let limit = today.toGMTString();
    let address = '';

    const latEle = document.querySelector('#lat');
    const lonEle = document.querySelector('#lon');
    const addressEle = document.querySelector('#address');
    const address_disp = document.querySelector('#address_disp');
    const gio_exec = document.querySelector('#gio_exec');
    
    let return_jusho = $.cookie('address',unescape);
    //console.log("起動時クッキー：" + return_jusho);
    /*
    * 緯度経度を画面表示
    */
    const setGeoLoc = (coords) => {
        latEle.value = `${coords.latitude}`;
        lonEle.value = `${coords.longitude}`;
        document.cookie = `lat=${coords.latitude};expires=${limit};Secure; `;
        document.cookie = `lon=${coords.longitude};expires=${limit};Secure; `;
        //alert(`緯度: ${coords.latitude}` + "/" + `経度: ${coords.longitude}`);
    }
    /*
    * 緯度経度から住所を取得して表示
    */
    const getAddress = async (coords) => {
        // 逆ジオコーディング API
        const url = new URL('https://mreversegeocoder.gsi.go.jp/reverse-geocoder/LonLatToAddress');
        url.searchParams.set('lat', coords.latitude);
        url.searchParams.set('lon', coords.longitude);
        const res = await fetch(url.toString());
        const json = await res.json();
        const data = json.results;  
        
        // 変換表から都道府県などを取得
        const muniData = GSI.MUNI_ARRAY[json.results.muniCd];
        // 都道府県コード,都道府県名,市区町村コード,市区町村名 に分割
        const [prefCode, pref, muniCode, city] = muniData.split(',');   
        //${pref}${city}${data.lv01Nm}->県・市区町村・番地
        // 画面に反映
        address_disp.textContent = `${city}${data.lv01Nm}`;
        address_disp.title = `${city}${data.lv01Nm}`;
        address.value = `${city}${data.lv01Nm}`;

        let jusho = escape(`${city}${data.lv01Nm}`);
        
        $.cookie('address',jusho,{secure: true,expires :0.5})
    };
    /*
    * 位置情報 API の実行(イベントリスナ)
    */
    let get_gio = function (){
        console.log('[EXEC Gio]')
        <?php
        if(EXEC_MODE=='Test'){ echo "gio_exec.textContent='[EXEC Gio]';";}
        ?>
        <?php
        if(EXEC_MODE<>'Trial'){
        ?>
        navigator.geolocation.getCurrentPosition(
            geoLoc => {
                setGeoLoc(geoLoc.coords);
                getAddress(geoLoc.coords);
            },
            err => console.error({err}),
        );
        <?php
        }else{
            echo "address_disp.textContent = '東京都中央区（仮）';";
            echo "latEle.value = 34.6816512;";
            echo "lonEle.value = 135.4792960;";
        }
        ?>
    }

    //if(address_disp.textContent==""){
    if(return_jusho==""){
        get_gio();
        address=address_disp.textContent;
    }else{
        address=return_jusho;
        address_disp.textContent=return_jusho;
    }
    
    let gio_onoff = function(){
        if(address_disp.style.textDecoration=='line-through'){
            address_disp.style.textDecoration='';
            gio_on.start(tourFinish,'','');
        }else{
            address_disp.style.textDecoration='line-through';
            gio_off.start(tourFinish,'','');
        }
    }
    
    
    const script = document.createElement('script');
    script.src = 'https://maps.gsi.go.jp/js/muni.js';
    document.body.insertAdjacentElement('afterEnd', script);    
    
    //GIO機能のオンオフ説明
    const gio_on = new Shepherd.Tour({
        useModalOverlay: true,
        defaultStepOptions: {
            classes: 'tour_modal',
            scrollTo: false,
            cancelIcon:{
                enabled:true
            }
        },
        tourName:'gio_on'
    });
    gio_on.addStep({
        title: `<p class='tour_header'>位置情報</p>`,
        text: `<p class='tour_discription'>位置情報は有効です。
            <br>
            <br>現在地が正しくない場合、同じ場所を再度タップして無効にしてください。
            <br>
            <br>現在地：${address}
            <br></p>`,
        buttons: [
            {
                text: 'OK',
                action: gio_on.next
            }
        ]
    });
    const gio_off = new Shepherd.Tour({
        useModalOverlay: true,
        defaultStepOptions: {
            classes: 'tour_modal',
            scrollTo: false,
            cancelIcon:{
                enabled:true
            }
        },
        tourName:'gio_off'
    });
    gio_off.addStep({
        title: `<p class='tour_header'>位置情報</p>`,
        text: `<p class='tour_discription'>位置情報は無効です。
            <br>
            <br>現在地に問題が無い場合、同じ場所を再度タップして有効にしてください。
            <br>
            <br>現在地：${address}
            <br></p>`,
        buttons: [
            {
                text: 'OK',
                action: gio_off.next
            }
        ]
    });
    
    
</script><!--ジオコーディング-->
</html>
<?php

$stmt = null;
$pdo_h = null;
?>


