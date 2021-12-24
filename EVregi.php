<!DOCTYPE html>
<html lang="ja">
<?php
session_start();
require "./vendor/autoload.php";

$pass=dirname(__FILE__);
require "version.php";
require "functions.php";

//.envの取得
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

//サイトタイトルの取得
$title = $_ENV["TITLE"];
//暗号化キー
$key = $_ENV["KEY"];
//PGバージョン差分補正
updatedb($_ENV["SV"], $_ENV["USER"], $_ENV["PASS"], $_ENV["DBNAME"] ,$version);
//DB接続
$mysqli = new mysqli($_ENV["SV"], $_ENV["USER"], $_ENV["PASS"], $_ENV["DBNAME"]);
//MySQLエラーレポート用共通宣言
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

//売上登録
if($_POST["commit_btn"] <> ""){
    $array = $_POST["ORDERS"];
    $sqlstr = "";

    //売上番号の取得
    $sqlstr = "select max(UriageNO) as UriageNO from UriageData;";
    $result = $mysqli->query( $sqlstr );
    $row_cnt = $result->num_rows;
    $row = $result->fetch_assoc(); 
    
    if(is_null($row["UriageNO"])){
        //初回売上時は売上NO[1]をセット
        $UriageNO = 1;
    }else{
        $UriageNO = $row["UriageNO"]+1;
    }
//    echo (string)$UriageNO;
    
    foreach($array as $row){
        if($row["su"]==0){
            continue;
        }
        $sqlstr = "insert into UriageData values(";
        $sqlstr = $sqlstr.(string)$UriageNO.",";
        $sqlstr = $sqlstr."'".date("Y/m/d")."',";
        $sqlstr = $sqlstr."'".date("Y/m/d H:i:s")."',";
        $sqlstr = $sqlstr."'".$_POST["EV"]."',";
        $sqlstr = $sqlstr."'',";
        $sqlstr = $sqlstr."'".$row["CD"]."',";
        $sqlstr = $sqlstr."'".$row["NM"]."',";
        $sqlstr = $sqlstr.(string)$row["su"].",";
        $sqlstr = $sqlstr.(string)$row["UTISU"].",";
        $sqlstr = $sqlstr.(string)$row["tanka"].",";
        $sqlstr = $sqlstr.(string)($row["su"] * $row["tanka"]).",";
        $sqlstr = $sqlstr.(string)$row["ZEI"].",";
        $sqlstr = $sqlstr.(string)$row["ZEIKBN"].");";
        
  //      echo $sqlstr."<br>";
	    $stmt = $mysqli->query("LOCK TABLES UriageData WRITE");
	    $stmt = $mysqli->prepare($sqlstr);
	    $stmt->execute();
	    $stmt = $mysqli->query("UNLOCK TABLES");
    
    }

}

?>
<head>
    <?php 
    //共通部分、bootstrap設定、フォントCND、ファビコン等
    include "head.html" 
    ?>
    <!--ページ専用CSS--><link rel="stylesheet" href="css/style_EVregi.css" >
    <TITLE><?php echo $title." レジ";?></TITLE>
</head>

<script>

window.onload = function() {

     // オブジェクトと変数の準備
     
     //合計金額を保持
     var kaikei_disp = document.getElementById("kaikei");
     var total_pay = 0;
     
     //PHPで繰り返し表示。メニューボタン数に応じて準備する
<?php     
	//$mysqli = new mysqli(sv, user, pass, dbname);
	//商品M取得
	$sql = "select * from ShouhinMS where hyoujiKBN1='on' order by hyoujiNO";
	$result = $mysqli->query( $sql );
	$row_cnt = $result->num_rows;
    while($row = $result->fetch_assoc()){
        echo "\n";
        echo "    var suryou_".$row["shouhinCD"]."  = document.getElementById('suryou_".$row["shouhinCD"]."');\n" ;         //ボタンの注文数
        echo "    var btn_menu_".$row["shouhinCD"]." = document.getElementById('btn_menu_".$row["shouhinCD"]."');\n";       //ボタンのオブジェクト
        echo "    var items_".$row["shouhinCD"]." = document.getElementById('items_".$row["shouhinCD"]."');\n";             //商品パネル
        echo "    var cnt_suryou_".$row["shouhinCD"]." = 0;\n";                                                             //ボタンのカウンタ
        echo "\n";
        //ボタンクリック時の動作関数
        echo "    //".$row["shouhinNM"]."ボタンクリック時\n";
        echo "    btn_menu_".$row["shouhinCD"].".onclick = function (){\n";
        echo "        cnt_suryou_".$row["shouhinCD"]." += 1;\n";
        echo "        total_pay += ".$row["tanka"].";\n";
        echo "        suryou_".$row["shouhinCD"].".value = cnt_suryou_".$row["shouhinCD"].";\n";
        echo "        kaikei_disp.innerHTML = total_pay;\n";
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
        $result->data_seek(0);
        while($row = $result->fetch_assoc()){
            echo "if(cnt_suryou_".$row["shouhinCD"]."==0){items_".$row["shouhinCD"].".style.display = 'none';}";
        }
        ?>
        order_chk.style.display = 'none';
        btn_commit.style.display = 'block';
        dentaku.style.display = 'block';
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
     var reset_btn = document.getElementById("order_clear");
     // リセットボタンのクリック処理
     reset_btn.onclick = function (){
        for (let i = 0; i < su.length; i++) {
            su.item(i).value = 0;
        }
        kaikei_disp.innerHTML = 0;
        total_pay = 0;
     };
};    
</script>

<form method = "post" action="EVregi.php">
    
<header>
    <div class="yagou"><a href="index.php"><?php echo $title;?></a></div>
    <div class="event"><input type="text" class="ev" name="EV" value="<?php echo $_POST["EV"] ?>"</div>
</header>

<body>
    <div class="main">
        <div class="contentA">
            <div class="menu">
                
<?php
    $result->data_seek(0);
    $i=0;

	while($row = $result->fetch_assoc()){
        echo "  <div class ='items' id='items_".$row["shouhinCD"]."'>\n";
        echo "      <button type='button' class='btn btn--menu' id='btn_menu_".$row["shouhinCD"]."'>".rot13decrypt($row["shouhinNM"])."\n";
        echo "      <input type='hidden' name ='ORDERS[".$i."][CD]' value = '".$row["shouhinCD"]."'>\n";
        echo "      <input type='hidden' name ='ORDERS[".$i."][NM]' value = '".$row["shouhinNM"]."'>\n";
        echo "      <input type='hidden' name ='ORDERS[".$i."][UTISU]' value = '".$row["utisu"]."'>\n";
        echo "      <input type='hidden' name ='ORDERS[".$i."][ZEI]' value = '".(string)($row["zeiritu"]*$row["tanka"])."'>\n";
        echo "      <input type='hidden' name ='ORDERS[".$i."][ZEIKBN]' value = '".$row["zeiKBN"]."'>\n";
        echo "      </button>\n";
        echo "      <div class ='ordered'>\n";
        echo "          ￥<input type='number' readonly='readonly' class='order tanka' name='ORDERS[".$i."][tanka]' value=".$row["tanka"]."> \n";
        echo "          × <input type='number' readonly='readonly' name ='ORDERS[".$i."][su]' id='suryou_".$row["shouhinCD"]."' class='order su' value = 0 style='display: inline'>\n";
        echo "      </div>\n";
        echo "  </div>\n";
        $i = $i+1;
	}
?> 
              
            </div>
        </div>
        <!--今のところサイドコンテンツ不要
        <div class="contentB">
            ORDER LIST
            
        </div>
        -->
    </div>
</body>

<footer>
    <div class="kaikei">お会計 ￥<span id="kaikei">0</span>-</div>
    <div class="right1">
        <button type='button' class='btn btn--chk' style="border-radius:0;" id='dentaku' data-toggle="modal" data-target="#FcModal">釣　銭</button>
    </div>
    <div class="right3">
        <button type='button' class='btn btn--chk' style="border:solid;border-top:none;border-bottom:none;border-color:#fff;border-radius: 0;" id='order_clear'>クリア</button>
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
    $mysqli->close();
?>


