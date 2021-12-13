<!DOCTYPE html>
<?php

// 設定ファイルインクルード【開発中】

require "functions.php";

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
    <link href="https://fonts.googleapis.com/css2?family=Kosugi+Maru&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="css/style.css" >
</head>
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
	$sql = "select * from ShouhinMS order by hyoujiNO";
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
        /*echo "        items_".$row["shouhinCD"].".style.display = 'none';\n";*/
        echo "        kaikei_disp.innerHTML = total_pay;\n";
        echo "    };\n";
        echo "\n";
    }
?>
    //確認・確定ボタン
    var order_chk = document.getElementById('order_chk');
    var btn_commit = document.getElementById('btn_commit');
    order_chk.onclick = function(){
        <?php
        $result->data_seek(0);
        while($row = $result->fetch_assoc()){
            echo "if(cnt_suryou_".$row["shouhinCD"]."==0){items_".$row["shouhinCD"].".style.display = 'none';}";
        }
        ?>
        order_chk.style.display = 'none';
        btn_commit.style.display = 'block';
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

<form method = "post" action="menu2.php">
    
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
    <div class="right">
        <button type='submit' class='btn btn--commit' style='display:none;' id='btn_commit' name='commit_btn' value="commit">登　録</button>
        <button type='button' class='btn btn--chk' id='order_chk'>確　認</button>
    </div>
</footer>

</form>
