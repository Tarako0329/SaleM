<!DOCTYPE html>
<html lang="ja">
<?php

// 設定ファイルインクルード【開発中】
$pass=dirname(__FILE__);
require "version.php";
require "../SQ/functions.php";

//売上登録
if($_POST["commit_btn"] <> ""){
/*
    print_r($_POST["ORDERS"]);
    echo "<br>deta?<br>";
    echo $_POST["ORDERS"][0]["CD"]."<br>";
*/    
    $array = $_POST["ORDERS"];
    $sqlstr = "";
/*    
    echo "<br>";
    print_r($array);
    echo "<br>";
*/    
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
    <META http-equiv='Content-Type' content='text/html; charset=UTF-8'>
    <TITLE>Cafe Presentsメニュー</TITLE>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <!--フォントCDN-->
    <link href="https://fonts.googleapis.com/css2?family=Kosugi+Maru&display=swap" rel="stylesheet">
    <!--ファビコンCDN-->
    <link rel="apple-touch-icon" href="../favicons/GIfavi.png">
    <link rel="icon" href="../favicons/GIfavi.png">
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
    <!-- オリジナル CSS -->
    <link rel="stylesheet" href="css/style_EVregi.css" >
</head>
<!-- Bootstrap Javascript(jQuery含む) -->
<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js" integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js" integrity="sha384-ChfqqxuZUCnJSK3+MXmPNIyE6ZbWh2IMqE241rYiqJxyMiZ6OW/JmZQ5stwEULTy" crossorigin="anonymous"></script>

<script>

window.onload = function() {

     // オブジェクトと変数の準備
     
     //合計金額を保持
     var kaikei_disp = document.getElementById("kaikei");
     var total_pay = 0;
     
     //PHPで繰り返し表示。メニューボタン数に応じて準備する
<?php     
	$mysqli = new mysqli(sv, user, pass, dbname);
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
    }
    
    dentaku.onclick = function(){
        //電卓モーダルに会計金額を表示
        seikyuu.innerHTML = total_pay;
    }

    //計算ボタン
    var keisan = document.getElementById('keisan');
    var azukari = document.getElementById('azukari');
    keisan.onclick = function(){
        //電卓モーダルに会計金額を表示
        var azukarikin = azukari.value;
        var oturikin = azukarikin - total_pay;
        oturi.innerHTML = oturikin;
    }

    // メニューボタンクリック処理
    btn_menu_002.onclick = function (){
        cnt_suryou_2 += 1;
        total_pay += 230;
        suryou_2.value = cnt_suryou_2;
        kaikei_disp.innerHTML = total_pay;
    };


     var reset_btn = document.getElementById("btn_reset");
     // リセットボタンのクリック処理
     reset_btn.onclick = function (){
          cnt_suryou_001 = 0; count_disp.innerHTML = cnt_suryou_001;
     }
};    
</script>

<form method = "post" action="EVregi.php">
    
<header>
    <div class="yagou"><a href="">Cafe Presents</a></div>
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
        echo "      <button type='button' class='btn btn--orange' id='btn_menu_".$row["shouhinCD"]."'>".rot13decrypt($row["shouhinNM"])."\n";
        echo "      <input type='hidden' name ='ORDERS[".$i."][CD]' value = '".$row["shouhinCD"]."'>\n";
        echo "      <input type='hidden' name ='ORDERS[".$i."][NM]' value = '".$row["shouhinNM"]."'>\n";
        echo "      <input type='hidden' name ='ORDERS[".$i."][UTISU]' value = '".$row["utisu"]."'>\n";
        echo "      <input type='hidden' name ='ORDERS[".$i."][ZEI]' value = '".(string)($row["zeiritu"]*$row["tanka"])."'>\n";
        echo "      <input type='hidden' name ='ORDERS[".$i."][ZEIKBN]' value = '".$row["zeiKBN"]."'>\n";
        echo "      </button>\n";
        echo "      <div class ='ordered'>\n";
        echo "          ￥<input type='number' readonly='readonly' class='order tanka' name='ORDERS[".$i."][tanka]' value=".$row["tanka"]."> \n";
        echo "          × <input type = 'number' name ='ORDERS[".$i."][su]' id='suryou_".$row["shouhinCD"]."' class='order su' value = 0 style='display: inline'>\n";
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
    <div class="kaikei">お会計　￥<span id="kaikei">0</span>円</div>
    <div class="right1">
        <button type='button' class='btn btn--chk' style='display:none;' id='dentaku' data-toggle="modal" data-target="#testModal">電　卓</button>
    </div>
    <div class="right2">
        <button type='submit' class='btn btn--commit' style='display:none;' id='btn_commit' name='commit_btn' value="commit">登　録</button>
        <button type='button' class='btn btn--chk' id='order_chk'>確　認</button>
    </div>
</footer>

<!--モーダル電卓-->
<div class="modal fade" id="testModal" tabindex="-1" role="dialog" aria-labelledby="basicModal" aria-hidden="true">
    <div class="modal-dialog  modal-dialog-centered">
        <div class="modal-content" style="font-size: 5.0rem; font-weight: 800;">
            <div class="modal-header">
                <div class="modal-title" id="myModalLabel">電　卓</div>
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


