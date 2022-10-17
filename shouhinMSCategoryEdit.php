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
$sql = "select * from ShouhinMS left join ZeiMS on ShouhinMS.zeiKBN=ZeiMS.zeiKBN where uid = ? order by bunrui1 desc,bunrui2,bunrui3,shouhinNM";
$stmt = $pdo_h->prepare($sql);
$stmt->bindValue(1, $_SESSION['user_id'], PDO::PARAM_INT);
$stmt->execute();

?>
<head>
    <?php 
    //共通部分、bootstrap設定、フォントCND、ファビコン等
    include "head.html" 
    ?>
    <!--ページ専用CSS-->
    <link rel='stylesheet' href='css/style_ShouhinMSCategoryEdit.css?<?php echo $time; ?>' >
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

        function getCategory(get_list_type){
            //商品マスタのカテゴリーリストを取得
            let List = $('#over_cate');
            if($(get_list_type)[0].value=='cate1'){
                $(List).attr("disabled", true);
                $(List).children().remove();
                $('.MSLIST tbody tr').each(function(index,elm){
                    $(elm).css({'display':'table-row'});
                });
                $(List).append("<option value='*'>上位分類を選択</option>\n");
                getCategory_sujest('#categry','#over_cate');
                return 0;
            }
            
            $.ajax({
                // 通信先ファイル名
                type        : 'POST',
                url         : 'ajax_get_MSCategory_list.php',
                //dataType    : 'application/json',
                data        :{
                                user_id     :'<?php echo $_SESSION["user_id"];?>',
                                list_type   :$(get_list_type)[0].value //カテ１ or カテ１＞カテ２
                            }
                },
            ).done(
                // 通信が成功した時
                function(data) {
                    //selectの子要素をすべて削除
                    $(List).attr("disabled", false);
                    $(List).children().remove();
                    $(List).append("<option value=''>上位分類を選択</option>\n");
                    // 取得したレコードをeachで順次取り出す
                    $.each(data, function(key, value){
                        // appendで追記していく
                        if(value.LIST == '<?php echo (!empty($_SESSION["Event"])?$_SESSION["Event"]:""); ?>'){
                            $(List).append("<option value='" + value.LIST + "' selected>" + value.LIST + "</option>\n");
                        }else{
                            $(List).append("<option value='" + value.LIST + "'>" + value.LIST + "</option>\n");
                        }
                    });
                    console.log("getCategory_通信成功");
                }
            ).fail(
                // 通信が失敗した時
                function(XMLHttpRequest, textStatus, errorThrown){
                    console.log("getCategory_通信失敗2");
                    console.log("XMLHttpRequest : " + XMLHttpRequest.status);
                    console.log("textStatus     : " + textStatus);
                    console.log("errorThrown    : " + errorThrown.message);
                }
            )
        };
        //分類を選択したときにカテゴリーリストを更新
        $('#categry').change(function(){
            getCategory('#categry');
            //$('#categry_send').val($('#categry').val());
        });

        function getCategory_sujest(get_list_type,serch_word){
            $.ajax({
                // 通信先ファイル名
                type        : 'POST',
                url         : 'ajax_get_MSCategory_list.php',
                //dataType    : 'application/json',
                data        :{
                                user_id     :'<?php echo $_SESSION["user_id"];?>',
                                list_type   :$(get_list_type)[0].value, //カテ１ or カテ１＞カテ２
                                serch_word  :$(serch_word)[0].value
                            }
                },
            ).done(
                // 通信が成功した時
                function(data) {
                    let words=[];
                    $.each(data, function(key, value){
                        words.push(value.LIST);
                    });
                    
                    $('#upd_bunrui_write').autocomplete({
                        source: words,
                        minLength: 0
                    });
                    console.log("getCategory_sujest_通信成功");
                }
            ).fail(
                // 通信が失敗した時
                function(XMLHttpRequest, textStatus, errorThrown){
                    console.log("getCategory_sujest_通信失敗2");
                    console.log("XMLHttpRequest : " + XMLHttpRequest.status);
                    console.log("textStatus     : " + textStatus);
                    console.log("errorThrown    : " + errorThrown.message);
                }
            )
        };

        $('#over_cate').change(function(){
            getCategory_sujest('#categry','#over_cate');
            let i=0;
            $('.MSLIST tbody tr').each(function(index,elm){
                //table>tr 内のcheckboxにチェックが入ってる行のみ表示するサンプル
                /*
                if(i!=0){i--;}
                if($(elm).find('input:checkbox').length > 0){
                    if($(elm).find('input:checkbox').is(':checked')){
                        //$(elm).css({'display':'table-row'});
                    }else{
                        $(elm).hide();
                        i=2;
                    }
                }else if(i!=0){
                    $(elm).hide();
                }
                */
                if($(elm).children().children("td").prevObject[2].innerText.indexOf($('#over_cate').val())===0){
                    //console.log(elm.innerText);
                    $(elm).css({'display':'table-row'});
                }else{
                    $(elm).hide();
                }
                
            })
        });
                     $("#upd_bunrui_write").focusin(function(){
                        // 第1引数：searchを設定した場合、データを絞り込みを実行
                        // 第2引数：検索対象のキーワード。空文字を指定した場合、全ての入力候補を表示
                        $(this).autocomplete("search","");
                    });
       
        getCategory_sujest('#categry','#over_cate');
        /*
        $('#upd_bunrui_write').change(function(){
            $('#upd_bunrui_send').val($('#upd_bunrui_write').val());
        });
        */

    };    

</script>
<!--
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
<link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
-->
<script src="script/jquery-ui-1.12.1.min.js"></script>
<link rel="stylesheet" href="css/jQuery-UI-1.12.1.min.css">

<body class='common_body media_body'>
    <header class='header-color common_header' style='flex-wrap:wrap'>
        <div class='title' style='width: 100%;'><a href='menu.php'><?php echo $title;?></a></div>
        <p style='font-size:1rem;color:var(--user-disp-color);font-weight:400;'>  取扱商品 カテゴリー一括修正 画面</p>
        <?php 
        if(empty($_SESSION["tour"])){
            echo "<a href='#' style='color:inherit;position:fixed;top:5px;right:5px;' onclick='help()'><i class='fa-regular fa-circle-question fa-lg logoff-color'></i></a>";
        }
        ?>
    </header>
    <form method='post' id='form1' action='shouhinMSCategoryEdit_sql.php'>
    <div class='header2'>
        <div style='display:flex;height:25px;margin:5px;'>
            <select class='form-control' id='categry' name='categry' style='width:100px;' required='required'>
                <option value=''>項目選択</option>
                <option value='cate1' <?php echo "selected";?> >カテゴリー１</option>
                <option value='cate2' >カテゴリー２</option>
                <option value='cate3' <?php /*echo ($categry==3?"selected":"");*/?> >カテゴリー３</option>
            </select>
            <select class='form-control' id='over_cate' disabled style='width:200px;margin-left:5px' >
                <option value='*' selected>上位分類を選択</option>
                <!--ajaxでセット-->
            </select>
        </div>
        <div style='display:block;margin:5px;'>
            <input type='text' name='upd_bunrui' required='required' placeholder='カテゴリー名を入力' id='upd_bunrui_write' class='form-control' style='max-width:305px;'>
        </div>
        
    </div>

    <?php
        //echo $_SESSION["MSG"]."<br>";
        if($_SESSION["MSG"]!=""){
            echo "<div class='container'><div class='row'><div class='col-12'><div style='padding-top:5px;text-align:center;font-size:1.5rem;' id='alert-1' class='lead'></div></div></div></div>";
        }
        $_SESSION["MSG"]="";
    ?>
    <div class='container-fluid'>
    
    <input type='hidden' name='csrf_token' value='<?php echo $csrf_create; ?>'>
    <!--
    <input type='hidden' name='upd_bunrui' id='upd_bunrui_send' value=''>
    <input type='hidden' name='categry' id='categry_send' value=''>
    -->
    
    <table class='table-striped table-bordered item_1 MSLIST'>
        <thead>
            <tr style='height:30px;'>
                <th class='th1' scope='col' style='width:auto;padding:0px 5px 0px 0px;'>レ</th>
                <!--<th class='th1' scope='col' style='width:auto;padding:0px 5px 0px 0px;' colspan='4'> ID:商品名</th>-->
                <th class='th1' scope='col' style='width:auto;padding:0px 5px 0px 0px;' > ID:商品名</th>
                <th class='th1' scope='col' style='width:auto;padding:0px 5px 0px 0px;' > カテゴリー(1>2>3)</th>
            </tr>
        </thead>
        <tbody id='tbody1'>
<?php    
$i=0;

foreach($stmt as $row){
    $zeikomitanka=$row["tanka"]+$row["tanka_zei"];
    $category=(!empty($row["bunrui1"])?$row["bunrui1"].">":"");
    $category=$category.(!empty($row["bunrui2"])?$row["bunrui2"].">":"");
    $category=$category.(!empty($row["bunrui3"])?$row["bunrui3"]:"");
    
    echo "<tr id='tr1_".$i."' >\n";
    echo "<td><input type='checkbox' id='chk_".$i."' name ='ORDERS[".$i."][chk]' style='width:2rem;padding-left:10px;'></td>";
    echo "<td style=''>".$row["shouhinCD"].":".$row["shouhinNM"]."</td>";    //商品名
    echo "<td style='padding-left:5px;'>".$category."</td>";
    echo "</tr>\n";
    echo "<input type='hidden' name ='ORDERS[".$i."][shouhinCD]' value='".$row["shouhinCD"]."'>";



    $i = $i+1;
}

?>
        </tbody>
    </table>
    
    </div>


    <footer class='common_footer'>
        <!--
        <dev class='left1 item_3'>
            <button type='submit' class='btn--chk' style='border-radius:0;' name='commit_btn' >登　録</button>
        </dev>
        -->
        <button type='submit' class='btn--chk item_3' style='border-radius:0;' name='commit_btn' >登　録</button>
    </footer>
    </form>
</body>
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
        text: `<p class='tour_discription'> 商品の並び順はここで変更できます。
                <br>三角マークは昇順・降順の切り替えに使います。
                </p>`,
        attachTo: {
            element: '.item_01',
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
        text: `<p class='tour_discription'> 商品の並び順はここで変更できます。
                <br>三角マークは昇順・降順の切り替えに使います。
                </p>`,
        attachTo: {
            element: '.item_01',
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
    });    helpTour.addStep({
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
<script>
    function send(){
        const form2 = document.getElementById('form2');
        form2.submit();
    }
</script>
</html>
<?php
$stmt  = null;
$stmt2 = null;
$pdo_h = null;
?>