<!DOCTYPE html>
<html lang="ja">
<?php
require "php_header.php";
if(isset($_GET["csrf_token"]) || empty($_POST)){
    if(csrf_chk_nonsession_get($_GET["csrf_token"])==false){
        $_SESSION["EMSG"]="セッションが正しくありませんでした。";
        header("HTTP/1.1 301 Moved Permanently");
        header("Location: index.php");
        exit();
    }
}

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

        $sqlstr = "update ShouhinMS set tanka=?,tanka_zei=?,zeiritu=?,zeikbn=?,tani=?,bunrui1=?,bunrui2=?,bunrui3=?,hyoujiKBN1=?,hyoujiNO=? where shouhinCD=? and uid=?";
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
        $stmt->bindValue(11,$row["shouhinCD"], PDO::PARAM_INT);
        $stmt->bindValue(12,$_SESSION['user_id'], PDO::PARAM_INT);
        
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
    <!--ページ専用CSS--><link rel="stylesheet" href="css/style_ShouhinMSL.css?<?php echo $time; ?>" >
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
<header class="header-color" style="flex-wrap:wrap">
    <div class="title" style="width: 100%;"><a href="menu.php"><?php echo $title;?></a></div>
    <p style="font-size:1rem;">  取扱商品 確認・編集 画面</p>
</header>

<body>    
    <?php
        //echo $_SESSION["MSG"]."<br>";
        if($_SESSION["MSG"]!=""){
            echo "<div class='container'><div class='row'><div class='col-12'><div style='text-align:center;font-size:1.5rem;' id='alert-1' class='lead'></div></div></div></div>";
        }
        $_SESSION["MSG"]="";
    ?>
    <div class="container-fluid">
    画面を横にすると他の項目も表示されます。<br>
    <form method="post" id="form1" action="shouhinMSList.php">
    <input type="hidden" name="csrf_token" value="<?php echo $csrf_create; ?>">

    <div class="btn-group btn-group-toggle" style="font-size:1rem;padding:0" data-toggle="buttons">
        <label class="btn btn-primary active">
            <input type="radio" name="options" id="option1" value="zeikomi" autocomplete="off" checked> 税込み
        </label>
        <label class="btn btn-primary">
            <input type="radio" name="options" id="option2" value="zeinuki" autocomplete="off"> 税抜き
        </label>
        <span style="font-size:1.5rem;">※単価変更欄に入力する金額</span>
    </div>

    
    <table class="table-striped">
        <thead>
            <tr>
            <th scope="col" style='width:2rem;padding:0;'>ID</th><th scope="col" style='width:auto;padding:0px 5px 0px 0px;'>商品名</th><th scope="col">単価<br>変更</th><th scope="col">単価<br>(税抜)</th><th scope="col" >税区分</th>
            <th scope="col">消費税</th><th scope="col" class='d-none d-sm-table-cell'>内容量</th><th scope="col" class="d-none d-sm-table-cell">単位</th><th scope="col" class="d-none d-sm-table-cell">分類1</th>
            <th scope="col" class="d-none d-sm-table-cell">分類2</th><th scope="col" class="d-none d-sm-table-cell">分類3</th><th scope="col">レジ</th><th scope="col" class='d-none d-sm-table-cell'>並順</th><th class='d-none d-sm-table-cell' style='width:4rem;'></th>
            </tr>
        </thead>
        <tbody>
<?php    
$i=0;
foreach($stmt as $row){
    $chk="";
    if($row["hyoujiKBN1"]=="on"){$chk="checked";}
    echo "<tr>\n";
    echo "<td>".$row["shouhinCD"]."</td>";  //商品ＣＤ
    echo "<td>".rot13decrypt($row["shouhinNM"])."</td>";    //商品名
    echo "<td><input type='number' style='width:6rem;' id='new_tanka".$i."' onBlur='zei_math".$i."(this.value)' ></td>";   //単価修正欄
    echo "<td><input type='number' readonly='readonly' id ='ORDERS[".$i."][tanka]' name ='ORDERS[".$i."][tanka]' style='width:6rem;background-color:#a3a3a3;' value='".$row["tanka"]."'></td>"; //登録単価
    echo "<td><select id ='ORDERS[".$i."][zeikbn]' onchange='zei_math".$i."(new_tanka".$i.".value)' name ='ORDERS[".$i."][zeikbn]' style='width:7rem;height:30px;'>";     //税区分
        foreach($ZKMS as $row2){
            if($row["zeiKBN"]==$row2["zeiKBN"]){
                echo "<option value='".$row2["zeiKBN"]."' selected>".$row2["hyoujimei"]."</option>\n";
            }else{
                echo "<option value='".$row2["zeiKBN"]."'>".$row2["hyoujimei"]."</option>\n";
            }
        }
    echo "</select>";
    echo "<td><input type='number' readonly='readonly'  id ='ORDERS[".$i."][shouhizei]' name ='ORDERS[".$i."][shouhizei]' style='width:6rem;background-color:#a3a3a3;' value='".$row["tanka_zei"]."'></td>";
    echo "<td class='d-none d-sm-table-cell'><input type='number' name ='ORDERS[".$i."][utisu]' style='width:6rem;' value='".$row["utisu"]."'></td>";
    echo "<td class='d-none d-sm-table-cell'><input type='text'   name ='ORDERS[".$i."][tani]' style='width:3rem;' value='".$row["tani"]."'></td>";
    echo "<td class='d-none d-sm-table-cell'><input type='text'   name ='ORDERS[".$i."][bunrui1]' style='width:6rem;' value='".$row["bunrui1"]."'></td>";
    echo "<td class='d-none d-sm-table-cell'><input type='text'   name ='ORDERS[".$i."][bunrui2]' style='width:6rem;' value='".$row["bunrui2"]."'></td>";
    echo "<td class='d-none d-sm-table-cell'><input type='text'   name ='ORDERS[".$i."][bunrui3]' style='width:6rem;' value='".$row["bunrui3"]."'></td>";
    echo "<td><input type='checkbox' name ='ORDERS[".$i."][hyoujiKBN1]' style='width:4rem;' ".$chk."></td>";
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

?>
        </tbody>
    </table>
    
    
    </div>
</body>

<footer>
    <dev class="col-1"></dev>
    <dev class="col-2" style="padding:0;">
        <input type="submit" value="登録" class="btn btn--chk" name="commit_btn">
    </dev>
    </form>
</footer>

</html>
<?php
$stmt  = null;
$stmt2 = null;
$pdo_h = null;
?>