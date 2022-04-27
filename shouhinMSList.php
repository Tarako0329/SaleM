<!DOCTYPE html>
<html lang="ja">
<?php
require "php_header.php";
/*
if(isset($_GET["csrf_token"]) || empty($_POST)){
    if(csrf_chk_nonsession_get($_GET["csrf_token"])==false){
        $_SESSION["EMSG"]="セッションが正しくありませんでした。";
        header("HTTP/1.1 301 Moved Permanently");
        header("Location: index.php");
        exit();
    }
}
*/
if($_POST["commit_btn"] <> ""){
    if(csrf_chk()==false){
        $_SESSION["EMSG"]="セッションが正しくありませんでした";
        header("HTTP/1.1 301 Moved Permanently");
        header("Location: index.php");
    }
    $array = $_POST["ORDERS"];
    $sqlstr = "";

    $pdo_h->beginTransaction();
    $E_Flg=0;
    foreach($array as $row){
        $sqlstr="select * from ZeiMS where zeiKBN=?;";
        $stmt = $pdo_h->prepare($sqlstr);
        $stmt->bindValue(1, $row["zeikbn"], PDO::PARAM_INT);
        $stmt->execute();
        $row3 = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $sqlstr = "update ShouhinMS set tanka=?,tanka_zei=?,zeiritu=?,zeikbn=?,tani=?,bunrui1=?,bunrui2=?,bunrui3=?,hyoujiKBN1=?,hyoujiNO=?,genka_tanka=? where shouhinCD=? and uid=?";
        $stmt = $pdo_h->prepare($sqlstr);
        $stmt->bindValue(1, $row["tanka"], PDO::PARAM_INT);
        $stmt->bindValue(2, $row["shouhizei"], PDO::PARAM_INT);
        $stmt->bindValue(3, $row3[0]["zeiritu"], PDO::PARAM_INT);
        $stmt->bindValue(4, $row["zeikbn"], PDO::PARAM_STR);
        $stmt->bindValue(5, $row["tani"], PDO::PARAM_STR);
        $stmt->bindValue(6, $row["bunrui1"], PDO::PARAM_STR);
        $stmt->bindValue(7, $row["bunrui2"], PDO::PARAM_STR);
        $stmt->bindValue(8, $row["bunrui3"], PDO::PARAM_STR);
        $stmt->bindValue(9, $row["hyoujiKBN1"], PDO::PARAM_STR);
        $stmt->bindValue(10,$row["hyoujiNO"], PDO::PARAM_INT);
        $stmt->bindValue(11,$row["genka"], PDO::PARAM_INT);
        $stmt->bindValue(12,$row["shouhinCD"], PDO::PARAM_INT);
        $stmt->bindValue(13,$_SESSION['user_id'], PDO::PARAM_INT);
        
        $status=$stmt->execute();
        
        if($status==true){
            
        }else{
            $E_Flg=1;
            break;
            
        }
    }
    
    if($E_Flg==0){
        $pdo_h->commit();
        $_SESSION["MSG"]= "更新されました。";
    }else{
        //1件でも失敗したらロールバック
        $pdo_h->rollBack();
        $_SESSION["MSG"]= "更新が失敗しました。";
    }
    
}
$csrf_create = csrf_create();

//商品マスタの取得
$sql = "select * from ShouhinMS left join ZeiMS on ShouhinMS.zeiKBN=ZeiMS.zeiKBN where uid = ? order by shouhinCD";
$stmt = $pdo_h->prepare($sql);
$stmt->bindValue(1, $_SESSION['user_id'], PDO::PARAM_INT);
$stmt->execute();



//税区分MSリスト取得
$sqlstr="select * from ZeiMS order by zeiKBN;";
$stmt2 = $pdo_h->query($sqlstr);
$ZKMS = $stmt2->fetchAll();

?>
<head>
    <?php 
    //共通部分、bootstrap設定、フォントCND、ファビコン等
    include "head.html" 
    ?>
    <!--ページ専用CSS--><link rel='stylesheet' href='css/style_ShouhinMSL.css?<?php echo $time; ?>' >
    <TITLE><?php echo $title." 取扱商品 確認・編集";?></TITLE>
</head>
<script>
    window.onload = function() {
        // Enterキーが押された時にSubmitされるのを抑制する
        document.getElementById("form1").onkeypress = (e) => {
            // form1に入力されたキーを取得
            const key = e.keyCode || e.charCode || 0;
            // 13はEnterキーのキーコード
            if (key == 13) {
                // アクションを行わない
                e.preventDefault();
            }
        }    
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

</script>
<header class='header-color' style='flex-wrap:wrap'>
    <div class='title' style='width: 100%;'><a href='menu.php'><?php echo $title;?></a></div>
    <p style='font-size:1rem;'>  取扱商品 確認・編集 画面</p>
</header>

<div class='header2'>
    <div>
    画面を横にすると他の項目も表示されます。<br>
    <div class='btn-group btn-group-toggle' style='padding:0' data-toggle='buttons'>
        <label class='btn btn-outline-primary active' style='font-size:1.2rem'>
            <input type='radio' name='options' id='option1' value='zeikomi' onChange='zei_math_all()' autocomplete='off' checked> 税『込』で金額変更
        </label>
        <label class='btn btn-outline-primary' style='font-size:1.2rem'>
            <input type='radio' name='options' id='option2' value='zeinuki' onChange='zei_math_all()' autocomplete='off'> 税『抜』で金額変更
        </label>
    </div>
    </div>
    <div>
        <button id='hyouji' style='position:fixed;top:75px;right:0' >all⇒checked</button>
    </div>
</div>

<body>    
    <?php
        //echo $_SESSION["MSG"]."<br>";
        if($_SESSION["MSG"]!=""){
            echo "<div class='container'><div class='row'><div class='col-12'><div style='padding-top:5px;text-align:center;font-size:1.5rem;' id='alert-1' class='lead'></div></div></div></div>";
        }
        $_SESSION["MSG"]="";
    ?>
    <div class='container-fluid'>
    <form method='post' id='form1' action='shouhinMSList.php'>
    <input type='hidden' name='csrf_token' value='<?php echo $csrf_create; ?>'>

    
    <table class='table-striped'>
        <thead>
            <tr style='height:30px;'>
                <th class='th1' scope='col' colspan='12' style='width:auto;padding:0px 5px 0px 0px;'>ID:商品名</th><th scope='col'>
            </tr>
            <tr style='height:30px;'>
            <!--<th scope='col' style='width:2rem;padding:0;'>ID</th><th scope='col' style='width:auto;padding:0px 5px 0px 0px;'>商品名</th><th scope='col'>単価<br>変更</th><th scope='col' style='color:red;'>単価<br>(税抜)</th><th scope='col' >税区分</th>-->
            <th scope='col'>単価変更</th><th scope='col' style='color:red;'>単価(税抜)</th><th scope='col' >税区分</th>
            <th scope='col' style='color:red;'>消費税</th><th scope='col'>原価</th><th scope='col' class='d-none d-sm-table-cell'>内容量</th><th scope='col' class='d-none d-sm-table-cell'>単位</th><th scope='col' class='d-none d-sm-table-cell'>分類1</th>
            <th scope='col' class='d-none d-sm-table-cell'>分類2</th><th scope='col' class='d-none d-sm-table-cell'>分類3</th><th scope='col'>レジ</th><th scope='col' class='d-none d-sm-table-cell'>並順</th><th class='d-none d-sm-table-cell' style='width:4rem;'></th>
            </tr>
            
        </thead>
        <tbody>
<?php    
$i=0;
foreach($stmt as $row){
    $chk="";
    if($row["hyoujiKBN1"]=="on"){$chk="checked";}
    echo "<tr id='tr1_".$i."'>\n";
    echo "<td style='font-size:1.7rem;font-weight:700;' colspan='12'>".$row["shouhinCD"]."：".rot13decrypt($row["shouhinNM"])."</td>";    //商品名
    echo "</tr>\n";
    echo "<tr id='tr2_".$i."'>\n";
    echo "<td><input type='number' style='width:8rem;' id='new_tanka".$i."' onBlur='zei_math".$i."(this.value)' placeholder='新価格のみ' ></td>";   //単価修正欄
    echo "<td><input type='number' readonly='readonly' id ='ORDERS[".$i."][tanka]' name ='ORDERS[".$i."][tanka]' style='width:7rem;background-color:#a3a3a3;' value='".$row["tanka"]."'></td>"; //登録単価
    echo "<td><select id ='ORDERS[".$i."][zeikbn]' onchange='zei_math".$i."(new_tanka".$i.".value)' name ='ORDERS[".$i."][zeikbn]' style='width:8rem;height:30px;'>";     //税区分
        foreach($ZKMS as $row2){
            if($row["zeiKBN"]==$row2["zeiKBN"]){
                echo "<option value='".$row2["zeiKBN"]."' selected>".$row2["hyoujimei"]."</option>\n";
            }else{
                echo "<option value='".$row2["zeiKBN"]."'>".$row2["hyoujimei"]."</option>\n";
            }
        }
    echo "</select>";
    echo "<td><input type='number' readonly='readonly' id ='ORDERS[".$i."][shouhizei]' name ='ORDERS[".$i."][shouhizei]' style='width:6rem;background-color:#a3a3a3;' value='".$row["tanka_zei"]."'></td>";
    echo "<td><input type='number' name ='ORDERS[".$i."][genka]' style='width:6rem;' value='".$row["genka_tanka"]."'></td>";
    echo "<td class='d-none d-sm-table-cell'><input type='number' name ='ORDERS[".$i."][utisu]' style='width:6rem;' value='".$row["utisu"]."'></td>";
    echo "<td class='d-none d-sm-table-cell'><input type='text'   name ='ORDERS[".$i."][tani]' style='width:3rem;' value='".$row["tani"]."'></td>";
    echo "<td class='d-none d-sm-table-cell'><input type='text'   name ='ORDERS[".$i."][bunrui1]' style='width:6rem;' value='".$row["bunrui1"]."'></td>";
    echo "<td class='d-none d-sm-table-cell'><input type='text'   name ='ORDERS[".$i."][bunrui2]' style='width:6rem;' value='".$row["bunrui2"]."'></td>";
    echo "<td class='d-none d-sm-table-cell'><input type='text'   name ='ORDERS[".$i."][bunrui3]' style='width:6rem;' value='".$row["bunrui3"]."'></td>";
    echo "<td><input type='checkbox' id='chk_".$i."' name ='ORDERS[".$i."][hyoujiKBN1]' style='width:4rem;' ".$chk."></td>";
//    echo "<td class='d-none d-sm-table-cell'><input type='number'   name ='ORDERS[".$i."][hyoujiKBN2]' style='width:4rem;' value='".$row["hyoujiKBN2"]."'></td>";
//    echo "<td class='d-none d-sm-table-cell'><input type='number'   name ='ORDERS[".$i."][hyoujiKBN3]' style='width:4rem;' value='".$row["hyoujiKBN3"]."'></td>";
    echo "<td class='d-none d-sm-table-cell'><input type='number' name ='ORDERS[".$i."][hyoujiNO]' style='width:4rem;' value='".$row["hyoujiNO"]."'></td>"; //並び順
    echo "<td class='d-none d-sm-table-cell' style='text-align:center;'><a href='shouhinDEL.php?cd=".$row["shouhinCD"]."&csrf_token=".$csrf_create."'><i class='fa-regular fa-trash-can'></i></a></td>"; //削除アイコン
    echo "</tr>\n";
    echo "<input type='hidden' name ='ORDERS[".$i."][shouhinCD]' value='".$row["shouhinCD"]."'>";

    //JAVA SCRIPT
    echo "    <script type='text/javascript' language='javascript'>\n";
    echo "        var select".$i." = document.getElementById('ORDERS[".$i."][zeikbn]');\n";
    echo "        var tanka".$i." = document.getElementById('ORDERS[".$i."][tanka]');\n";
    echo "        var shouhizei".$i." = document.getElementById('ORDERS[".$i."][shouhizei]');\n";
    echo "        var kominuki".$i." = document.getElementsByName('options')\n";
    
    echo "        var zei_math".$i." = function(new_tanka){\n"; //税計算関数
    echo "            if(new_tanka==''){\n";
    echo "                switch(select".$i.".value){\n";
    echo "                    case '0':\n";
    echo "                        shouhizei".$i.".value=0;\n";
    echo "                        break;\n";
    echo "                    case '1001':\n";
    echo "                        shouhizei".$i.".value=Math.round(tanka".$i.".value * (8 / 100));\n";
    echo "                        break;\n";
    echo "                    case '1101':\n";
    echo "                        shouhizei".$i.".value=Math.round(tanka".$i.".value * (10 / 100));\n";
    echo "                        break;\n";
    echo "                }\n";
    echo "            }else if(select".$i.".value=='0'){\n";
    echo "                tanka".$i.".value=new_tanka;\n";
    echo "                shouhizei".$i.".value=0;\n";
    echo "            }else if(kominuki".$i."[0].checked){//税込\n";
    echo "                switch(select".$i.".value){\n";
    echo "                    case '1001':\n";
    echo "                        tanka".$i.".value=Math.round(new_tanka / (1 + 8 / 100));\n";
    echo "                        shouhizei".$i.".value=new_tanka - Math.round(new_tanka / (1 + 8 / 100));\n";
    echo "                        break;\n";
    echo "                    case '1101':\n";
    echo "                        tanka".$i.".value=Math.round(new_tanka / (1 + 10 / 100));\n";
    echo "                        shouhizei".$i.".value=new_tanka - Math.round(new_tanka / (1 + 10 / 100));\n";
    echo "                        break;\n";
    echo "                }\n";
    echo "            }else if(kominuki".$i."[1].checked){//税抜\n";
    echo "                switch(select".$i.".value){\n";
    echo "                    case '1001':\n";
    echo "                        tanka".$i.".value=new_tanka;\n";
    echo "                        shouhizei".$i.".value=Math.round(new_tanka * (8 / 100));\n";
    echo "                        break;\n";
    echo "                    case '1101':\n";
    echo "                        tanka".$i.".value=new_tanka;\n";
    echo "                        shouhizei".$i.".value=Math.round(new_tanka * (10 / 100));\n";
    echo "                        break;\n";
    echo "                }\n";
    echo "            }else{\n";
    echo "                //\n";
    echo "            }\n";
    echo "        }\n";
    echo "    </script>\n";

    $i = $i+1;
}
$i--;
$kensu=$i;

//JAVA SCRIPT
echo "<script type='text/javascript' language='javascript'>\n";
echo "  var zei_math_all=function(){\n";
while($i>=0){
    echo "      zei_math".$i."(new_tanka".$i.".value);\n";
    $i--;
}
echo "  }\n";

$i=$kensu;
echo "  var hyouji = document.getElementById('hyouji');\n";
echo "  var disp;\n";
echo "  var tr1;\n";
echo "  var tr2;\n";
echo "  hyouji.onclick=function(){\n";
echo "      switch(hyouji.innerHTML){\n";
echo "      case 'all⇒checked':\n"; //全件->チェックのみ
echo "          check='table-row';\n";
echo "          nocheck='none';\n";
echo "          hyouji.innerHTML='checked⇒no-checked';\n";
echo "          break;\n";
echo "      case 'checked⇒no-checked':\n";//チェックのみ->未チェックのみ
echo "          check='none';\n";
echo "          nocheck='table-row';\n";
echo "          hyouji.innerHTML='no-checked⇒all';\n";
echo "          break;\n";
echo "      case 'no-checked⇒all':\n";//未チェックのみ->全件
echo "          check='table-row';\n";
echo "          nocheck='table-row';\n";
echo "          hyouji.innerHTML='all⇒checked';\n";
echo "          break;\n";
echo "      }\n";


while($i>=0){
    echo "      disp".$i." = document.getElementById('chk_".$i."');\n";
    echo "          tr1".$i." = document.getElementById('tr1_".$i."');\n";
    echo "          tr2".$i." = document.getElementById('tr2_".$i."');\n";
    //echo "alert(disp".$i.".value + ':' + '".$i."');";
    echo "      if(disp".$i.".checked === true){\n";
    echo "          tr1".$i.".style.display=check;\n";
    echo "          tr2".$i.".style.display=check;\n";
    echo "      }else{\n";
    echo "          tr1".$i.".style.display=nocheck;\n";
    echo "          tr2".$i.".style.display=nocheck;\n";
    echo "      }\n";
    $i--;
}

echo "  };\n";

echo "</script>";
?>
        </tbody>
    </table>
    </div>
</body>

<footer>
    <dev class='left1'>
        <input type='submit' value='登　録' class='btn btn--chk' style='border-radius:0;' name='commit_btn'>
    </dev>
    </form>
</footer>

</html>
<?php
$stmt  = null;
$stmt2 = null;
$pdo_h = null;
?>