<!DOCTYPE html>
<html lang="ja">
<?php
require "php_header.php";

if(isset($_GET["csrf_token"]) || empty($_POST)){
    if(csrf_chk_redirect($_GET["csrf_token"])==false){
        $_SESSION["EMSG"]="セッションが正しくありませんでした。";
        header("HTTP/1.1 301 Moved Permanently");
        header("Location: index.php");
        exit();
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
    <!--ページ専用CSS-->
    <link rel='stylesheet' href='css/style_ShouhinMSL.css?<?php echo $time; ?>' >
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
<header class='header-color common_header' style='flex-wrap:wrap'>
    <div class='title' style='width: 100%;'><a href='menu.php'><?php echo $title;?></a></div>
    <p style='font-size:1rem;color:var(--user-disp-color);font-weight:400;'>  取扱商品 確認・編集 画面</p>
</header>

<div class='header2'>
    <div>
    画面を横にすると他の項目も表示されます。<br>
    <div class='btn-group btn-group-toggle item_2' style='padding:0' data-toggle='buttons'>
        <label class='btn btn-outline-primary active' style='font-size:1.2rem'>
            <input type='radio' name='options' id='option1' value='zeikomi' onChange='zei_math_all()' autocomplete='off' checked> 税 込 入 力
        </label>
        <label class='btn btn-outline-primary' style='font-size:1.2rem'>
            <input type='radio' name='options' id='option2' value='zeinuki' onChange='zei_math_all()' autocomplete='off'> 税 抜 入 力
        </label>
    </div>
    </div>
    <div>
        <select id='hyouji' class='hyouji item_0'>
            <option value='0' selected>全て表示</option>
            <option value='1'>チェック</option>
            <option value='2'>未チェック</option>
        </select>
    </div>
    <?php if(empty($_SESSION["tour"])){?>
    <a href="#" style='color:inherit;position:fixed;top:72px;right:5px;' onclick='help()'><i class="fa-regular fa-circle-question fa-lg awesome-color-panel-border-same"></i></a>
    <?php }?>
</div>

<body class='common_body media_body'>    
    <?php
        //echo $_SESSION["MSG"]."<br>";
        if($_SESSION["MSG"]!=""){
            echo "<div class='container'><div class='row'><div class='col-12'><div style='padding-top:5px;text-align:center;font-size:1.5rem;' id='alert-1' class='lead'></div></div></div></div>";
        }
        $_SESSION["MSG"]="";
    ?>
    <div class='container-fluid'>
    <form method='post' id='form1' action='shouhinMSList_sql.php'>
    <input type='hidden' name='csrf_token' value='<?php echo $csrf_create; ?>'>

    
    <table class='table-striped item_1'>
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
    echo "<td><input type='number' style='width:8rem;' id='new_tanka".$i."' onBlur='zei_math".$i."(this.value)' placeholder='新価格' ></td>";   //単価修正欄
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
    echo "        var kominuki".$i." = document.getElementsByName('options');\n";
    
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
echo "  hyouji.onchange=function(){\n";
echo "      switch(hyouji.value){\n";
echo "      case '1':\n"; //全件->チェックのみ
echo "          check='table-row';\n";
echo "          nocheck='none';\n";
//echo "          hyouji.innerHTML='checked⇒no-checked';\n";
echo "          break;\n";
echo "      case '2':\n";//チェックのみ->未チェックのみ
echo "          check='none';\n";
echo "          nocheck='table-row';\n";
//echo "          hyouji.innerHTML='no-checked⇒all';\n";
echo "          break;\n";
echo "      case '0':\n";//未チェックのみ->全件
echo "          check='table-row';\n";
echo "          nocheck='table-row';\n";
//echo "          hyouji.innerHTML='all⇒checked';\n";
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

<footer class='common_footer'>
    <dev class='left1 item_3'>
        <button type='submit' class='btn--chk' style='border-radius:0;' name='commit_btn' >登　録</button>
    </dev>
</footer>
</form>
<!--シェパードナビshepherd
<script src="https://cdn.jsdelivr.net/npm/shepherd.js@9.1.1/dist/js/shepherd.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/shepherd.js@9.1.1/dist/css/shepherd.css"/>
-->
<script src="shepherd/shepherd.min.js?<?php echo $time; ?>"></script>
<link rel="stylesheet" href="shepherd/shepherd.css?<?php echo $time; ?>"/>
<?php require "ajax_func_tourFinish.php";?>
<script>
    const TourMilestone = '<?php echo $_SESSION["tour"];?>';
    
    const tutorial_12 = new Shepherd.Tour({
        useModalOverlay: true,
        defaultStepOptions: {
            classes: 'tour_modal',
            scrollTo: false,
            cancelIcon:{
                enabled:true
            }
        },
        tourName:'tutorial_12'
    });
    tutorial_12.addStep({
        title: `<p class='tour_header'>チュートリアル</p>`,
        text: `<p class='tour_discription'> 商品一覧の修正画面になります。
              </p>`,
        buttons: [
            {
                text: 'Next',
                action: tutorial_12.next
            }
        ]
    });
    tutorial_12.addStep({
        title: `<p class='tour_header'>チュートリアル</p>`,
        text: `<p class='tour_discription'> 登録した商品の「価格変更」やレジへの「表示/非表示」の切替はこの状態(縦画面表示)で行えます。
              </p>`,
        buttons: [
            {
                text: 'Back',
                action: tutorial_12.back
            },
            {
                text: 'Next',
                action: tutorial_12.next
            }
        ]
    });
    tutorial_12.addStep({
        title: `<p class='tour_header'>チュートリアル</p>`,
        text: `<p class='tour_discription'> その他の項目については画面を横にすると表示され、修正可能な状態となります。
              </p>`,
        buttons: [
            {
                text: 'Back',
                action: tutorial_12.back
            },
            {
                text: 'Next',
                action: tutorial_12.next
            }
        ]
    });
    tutorial_12.addStep({
        title: `<p class='tour_header'>チュートリアル</p>`,
        text: `<p class='tour_discription'> 画面を横にしてみてください。
                <br>PCの場合、ブラウザの幅を拡大縮小すると表示が切り替わります。
                <br>タブレットの場合は最初から全て表示されているかと思います。
              </p>`,
        buttons: [
            {
                text: 'Back',
                action: tutorial_12.back
            },
            {
                text: 'Next',
                action: tutorial_12.next
            }
        ]
    });
    tutorial_12.addStep({
        title: `<p class='tour_header'>チュートリアル</p>`,
        text: `<p class='tour_discription'> 右上のリストボックスをタップすると、レジ画面の表示対象チェックが入っているもの、いないもの、全件表示と切り替える事が可能です。
              </p>`,
        attachTo: {
            element: '.item_0',
            on: 'bottom'
        },
        buttons: [
            {
                text: 'Back',
                action: tutorial_12.back
            },
            {
                text: 'Next',
                action: tutorial_12.next
            }
        ]
    });
    tutorial_12.addStep({
        title: `<p class='tour_header'>チュートリアル</p>`,
        text: `<p class='tour_discription'>試しにタップして変更してみてください。
              </p>`,
        attachTo: {
            element: '.item_0',
            on: 'auto'
        },
        buttons: [
            {
                text: 'Back',
                action: tutorial_12.back
            },
            {
                text: 'Next',
                action: tutorial_12.next
            }
        ]
    });
    tutorial_12.addStep({
        title: `<p class='tour_header'>チュートリアル</p>`,
        text: `<p class='tour_discription'>商品の価格を変更する際は「新価格」欄をタップして変更後の価格を入力して下さい。
              </p>`,
        attachTo: {
            element: '.item_1',
            on: 'auto'
        },
        buttons: [
            {
                text: 'Back',
                action: tutorial_12.back
            },
            {
                text: 'Next',
                action: tutorial_12.next
            }
        ]
    });
    tutorial_12.addStep({
        title: `<p class='tour_header'>チュートリアル</p>`,
        text: `<p class='tour_discription'>入力した「新価格」が「税込か税抜」かは、こちらで選択して下さい。
              </p>`,
        attachTo: {
            element: '.item_2',
            on: 'auto'
        },
        buttons: [
            {
                text: 'Back',
                action: tutorial_12.back
            },
            {
                text: 'Next',
                action: tutorial_12.next
            }
        ]
    });
    tutorial_12.addStep({
        title: `<p class='tour_header'>チュートリアル</p>`,
        text: `<p class='tour_discription'>レジへの「表示/非表示」の切替は「レジ」行のチェック有無で切り替えます。
              </p>`,
        attachTo: {
            element: '.item_1',
            on: 'auto'
        },
        buttons: [
            {
                text: 'Back',
                action: tutorial_12.back
            },
            {
                text: 'Next',
                action: tutorial_12.next
            }
        ]
    });
    tutorial_12.addStep({
        title: `<p class='tour_header'>チュートリアル</p>`,
        text: `<p class='tour_discription'>その他の項目についても、コチラの画面で修正したい部分をタップして打ち変えることで修正が可能です。
              </p>`,
        attachTo: {
            element: '.item_1',
            on: 'auto'
        },
        buttons: [
            {
                text: 'Back',
                action: tutorial_12.back
            },
            {
                text: 'Next',
                action: tutorial_12.next
            }
        ]
    });
    tutorial_12.addStep({
        title: `<p class='tour_header'>チュートリアル</p>`,
        text: `<p class='tour_discription'>修正が完了したら「登録」ボタンをタップすると、変更内容が登録されます。
              </p>`,
        attachTo: {
            element: '.item_3',
            on: 'auto'
        },
        buttons: [
            {
                text: 'Back',
                action: tutorial_12.back
            },
            {
                text: 'Next',
                action: tutorial_12.nextAndSave
            }
        ]
    });
    tutorial_12.addStep({
        title: `<p class='tour_header'>チュートリアル</p>`,
        text: `<p class='tour_discription'>なお、こちらで商品の価格等を修正しても過去の売上が変更されることはありません。
              </p>`,
        buttons: [
            {
                text: 'Back',
                action: tutorial_12.back
            },
            {
                text: 'Next',
                action: tutorial_12.next
            }
        ]
    });
    tutorial_12.addStep({
        title: `<p class='tour_header'>チュートリアル</p>`,
        text: `<p class='tour_discription'>試しに項目を修正し、「登録」してみてください。
              </p>`,
        buttons: [
            {
                text: 'Back',
                action: tutorial_12.back
            },
            {
                text: 'Next',
                action: tutorial_12.complete
            }
        ]
    });

    const tutorial_13 = new Shepherd.Tour({
        useModalOverlay: true,
        defaultStepOptions: {
            classes: 'tour_modal',
            scrollTo: true,
            cancelIcon:{
                enabled:true
            }
        },
        tourName:'tutorial_13'
    });
    tutorial_13.addStep({
        title: `<p class='tour_header'>チュートリアル</p>`,
        text: `<p class='tour_discription'>最後に、登録した商品情報の削除について説明します。
              </p>`,
        buttons: [
            {
                text: 'Back',
                action: tutorial_13.back
            },
            {
                text: 'Next',
                action: tutorial_13.next
            }
        ]
    });
    tutorial_13.addStep({
        title: `<p class='tour_header'>チュートリアル</p>`,
        text: `<p class='tour_discription'><span style='color:red;'>ちなみに、削除しようとしている商品の「売上」が１件でも登録されていると削除する事は出来ません。</span>
              </p>`,
        buttons: [
            {
                text: 'Back',
                action: tutorial_13.back
            },
            {
                text: 'Next',
                action: tutorial_13.next
            }
        ]
    });
    tutorial_13.addStep({
        title: `<p class='tour_header'>チュートリアル</p>`,
        text: `<p class='tour_discription'>画面を横にして頂くと右端に<i class='fa-regular fa-trash-can'></i>　マークが表示されます。
              </p>`,
        buttons: [
            {
                text: 'Back',
                action: tutorial_13.back
            },
            {
                text: 'Next',
                action: tutorial_13.next
            }
        ]
    });
    tutorial_13.addStep({
        title: `<p class='tour_header'>チュートリアル</p>`,
        text: `<p class='tour_discription'><i class='fa-regular fa-trash-can'></i>　マークをタップすると削除を確認する画面に移動します。
              </p>`,
        buttons: [
            {
                text: 'Back',
                action: tutorial_13.back
            },
            {
                text: 'Next',
                action: tutorial_13.next
            }
        ]
    });
    tutorial_13.addStep({
        title: `<p class='tour_header'>チュートリアル</p>`,
        text: `<p class='tour_discription'>今回登録した商品が不要な商品でしたら<i class='fa-regular fa-trash-can'></i>　マークをタップして削除して下さい。
              </p>`,
        buttons: [
            {
                text: 'Back',
                action: tutorial_13.back
            },
            {
                text: 'Next',
                action: tutorial_13.next
            }
        ]
    });
    tutorial_13.addStep({
        title: `<p class='tour_header'>チュートリアル</p>`,
        text: `<p class='tour_discription'>以上でチュートリアルは終了となります。
                <br>お疲れ様でした。
              </p>`,
        buttons: [
            {
                text: 'Back',
                action: tutorial_13.back
            },
            {
                text: 'Next',
                action: tutorial_13.complete
            }
        ]
    });

    const helpTour = new Shepherd.Tour({
        useModalOverlay: true,
        defaultStepOptions: {
            classes: 'tour_modal',
            scrollTo: false,
            cancelIcon:{
                enabled:true
            }
        },
        tourName:'helpTour'
    });
    helpTour.addStep({
        title: `<p class='tour_header'>チュートリアル</p>`,
        text: `<p class='tour_discription'> 登録した商品の「価格変更」やレジへの「表示/非表示」の切替はこの状態(縦画面表示)で行えます。
              </p>`,
        buttons: [
            {
                text: 'Back',
                action: helpTour.back
            },
            {
                text: 'Next',
                action: helpTour.next
            }
        ]
    });
    helpTour.addStep({
        title: `<p class='tour_header'>チュートリアル</p>`,
        text: `<p class='tour_discription'> 画面を横にすると他の項目も表示され、修正可能な状態となります。
              </p>`,
        buttons: [
            {
                text: 'Back',
                action: helpTour.back
            },
            {
                text: 'Next',
                action: helpTour.next
            }
        ]
    });
    helpTour.addStep({
        title: `<p class='tour_header'>チュートリアル</p>`,
        text: `<p class='tour_discription'> 画面を横にしてみてください。
                <br>PCの場合、ブラウザの幅を拡大縮小すると表示が切り替わります。
                <br>タブレットの場合は最初から全て表示されているかと思います。
              </p>`,
        buttons: [
            {
                text: 'Back',
                action: helpTour.back
            },
            {
                text: 'Next',
                action: helpTour.next
            }
        ]
    });
    helpTour.addStep({
        title: `<p class='tour_header'>チュートリアル</p>`,
        text: `<p class='tour_discription'><i class='fa-regular fa-trash-can'></i>　マークをタップすると削除を確認する画面に移動します。
              </p>`,
        buttons: [
            {
                text: 'Back',
                action: helpTour.back
            },
            {
                text: 'Next',
                action: helpTour.next
            }
        ]
    });
    helpTour.addStep({
        title: `<p class='tour_header'>チュートリアル</p>`,
        text: `<p class='tour_discription'> 右上のリストボックスをタップすると、レジ画面の表示対象チェックが入っているもの、いないもの、全件表示と切り替える事が可能です。
              </p>`,
        attachTo: {
            element: '.item_0',
            on: 'bottom'
        },
        buttons: [
            {
                text: 'Back',
                action: helpTour.back
            },
            {
                text: 'Next',
                action: helpTour.next
            }
        ]
    });
    helpTour.addStep({
        title: `<p class='tour_header'>チュートリアル</p>`,
        text: `<p class='tour_discription'>商品の価格を変更する際は「新価格」欄をタップして変更後の価格を入力して下さい。
              </p>`,
        attachTo: {
            element: '.item_1',
            on: 'auto'
        },
        buttons: [
            {
                text: 'Back',
                action: helpTour.back
            },
            {
                text: 'Next',
                action: helpTour.next
            }
        ]
    });
    helpTour.addStep({
        title: `<p class='tour_header'>チュートリアル</p>`,
        text: `<p class='tour_discription'>入力した「新価格」が「税込か税抜」かは、こちらで選択して下さい。
              </p>`,
        attachTo: {
            element: '.item_2',
            on: 'auto'
        },
        buttons: [
            {
                text: 'Back',
                action: helpTour.back
            },
            {
                text: 'Next',
                action: helpTour.next
            }
        ]
    });
    helpTour.addStep({
        title: `<p class='tour_header'>チュートリアル</p>`,
        text: `<p class='tour_discription'>レジへの「表示/非表示」の切替は「レジ」行のチェック有無で切り替えます。
              </p>`,
        attachTo: {
            element: '.item_1',
            on: 'auto'
        },
        buttons: [
            {
                text: 'Back',
                action: helpTour.back
            },
            {
                text: 'Next',
                action: helpTour.next
            }
        ]
    });
    helpTour.addStep({
        title: `<p class='tour_header'>チュートリアル</p>`,
        text: `<p class='tour_discription'>その他の項目についても、コチラの画面で修正したい部分をタップして打ち変えることで修正が可能です。
              </p>`,
        attachTo: {
            element: '.item_1',
            on: 'auto'
        },
        buttons: [
            {
                text: 'Back',
                action: helpTour.back
            },
            {
                text: 'Next',
                action: helpTour.next
            }
        ]
    });
    helpTour.addStep({
        title: `<p class='tour_header'>チュートリアル</p>`,
        text: `<p class='tour_discription'>修正が完了したら「登録」ボタンをタップすると、変更内容が登録されます。
              </p>`,
        attachTo: {
            element: '.item_3',
            on: 'auto'
        },
        buttons: [
            {
                text: 'Back',
                action: helpTour.back
            },
            {
                text: 'Next',
                action: helpTour.next
            }
        ]
    });
    helpTour.addStep({
        title: `<p class='tour_header'>チュートリアル</p>`,
        text: `<p class='tour_discription'>なお、こちらで商品の価格等を修正しても過去の売上が変更されることはありません。
              </p>`,
        buttons: [
            {
                text: 'Back',
                action: helpTour.back
            },
            {
                text: 'finish',
                action: helpTour.complete
            }
        ]
    });
    
    if(TourMilestone=="tutorial_11"){
        tutorial_12.start(tourFinish,'tutorial','');
    }else if(TourMilestone=="tutorial_12"){
        tutorial_13.start(tourFinish,'tutorial','finish');
    }

    function help(){
        helpTour.start(tourFinish,'help','');
    }

</script>
</html>
<?php
$stmt  = null;
$stmt2 = null;
$pdo_h = null;
?>