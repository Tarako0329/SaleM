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
    //トップメニューからの遷移チェック。リンクから飛ぶのでPOSTなし
    if(csrf_chk_nonsession_get($_GET["csrf_token"])==false && csrf_chk_redirect($_GET["csrf_token"])==false){
        $_SESSION["EMSG"]="セッションが正しくありませんでした。";
        header("HTTP/1.1 301 Moved Permanently");
        header("Location: index.php");
        exit();
    }
}

//セッションのIDがクリアされた場合の再取得処理。
$rtn=check_session_userid($pdo_h);

//税区分MSリスト取得
$sqlstr="select * from ZeiMS order by zeiKBN;";
$stmt = $pdo_h->query($sqlstr);
$csrf_token=csrf_create();
?>
<head>
    <?php 
    //共通部分、bootstrap設定、フォントCND、ファビコン等
    include "head.html" 
    ?>
    <!--ページ専用CSS--><link rel="stylesheet" href="css/style_ShouhinMSedit.css?<?php echo $time; ?>" >
    <TITLE><?php echo secho($title)." 取扱商品登録画面";?></TITLE>
</head>
<script>
    window.onload = function() {
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
    };    
</script>

<header class="header-color" style="flex-wrap:wrap">
    <div class="title" style="width: 100%;"><a href="menu.php"><?php echo secho($title);?></a></div>
    <p style="font-size:1rem;">  取扱商品登録画面</p>
</header>

<body>
    <div class="container-fluid" style="padding-top:15px;">
    <?php
        //echo $_SESSION["MSG"]."<br>";
        if($_SESSION["MSG"]!=""){
            echo "<div class='container'><div class='row'><div class='col-12'><div style='padding-top:5px;text-align:center;font-size:1.5rem;' id='alert-1' class='lead'></div></div></div></div>";
        }
        $_SESSION["MSG"]="";
    ?>
    <form method="post" id="form1" class="form" action="shouhinMSedit_sql.php">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
        <table>
            <tr><td>商品名</td><td><input type="text" class="form-control" style="width:95%" id="shouhinNM" name="shouhinNM" required="required" placeholder="必須"></td></tr>
            <tr><td>単価</td><td><input type="number" onchange="zei_math()" class="form-control" style="width:95%" id="new_tanka" required="required" placeholder="必須"></td>
                <td>
                    <div class="btn-group btn-group-toggle" style="padding:0" data-toggle="buttons">
                        <label class="btn btn-outline-primary active" style="font-size:1.2rem">
                            <input type="radio" onchange="zei_math()" name="options" id="option1" value="zeikomi" autocomplete="off" checked> 税込み
                        </label>
                        <label class="btn btn-outline-primary" style="font-size:1.2rem">
                            <input type="radio" onchange="zei_math()" name="options" id="option2" value="zeinuki" autocomplete="off"> 税抜き
                        </label>
                    </div>                    
                </td></tr>
            <tr><td>税区分</td>
                <td>
                    <select class="form-control" onchange="zei_math()" style="width:80%;padding-top:0;" id="zeikbn" name="zeikbn" required="required" placeholder="必須" >
                    <option value=""></option>
                    <?php
                    foreach($stmt as $row){
                        echo "<option value=".secho($row["zeiKBN"]).">".secho($row["hyoujimei"])."</option>\n";
                    }
                    ?>
                    </select>
                </td></tr>
            <tr><td>税抜単価</td><td><input type="number" readonly='readonly' class="form-control" style="width:95%" id="tanka" name="tanka" ></td></tr>
            <tr><td>消費税</td><td><input type="number" readonly='readonly' class="form-control" style="width:95%" id="shouhizei" name="shouhizei" ></td></tr>
            <tr><td>税込単価</td>
                <td>
                    <input type="number" readonly='readonly' class="form-control" style="width:95%;border:none;" id="zkomitanka" aria-describedby="zkomitankaHelp">
                    <small id="zkomitankaHelp" class="form-text text-muted">レジ画面に表示される金額は税込価格です。</small>
                </td>
            </tr>
            <tr>
                <td>想定原価単価</td>
                <td>
                    <input type="number" class="form-control" style="width:95%" id="genka" name="genka" aria-describedby="genka">
                    <small id="genka" class="form-text text-muted">おおよその原材料費</small>
                </td>
            </tr>
            <tr><td>内容量</td><td><input type="number" class="form-control" style="width:95%" id="utisu" name="utisu" placeholder="1箱12個入りの場合「12」等"></td></tr>
            <tr><td>単位</td><td><input type="text" class="form-control" style="width:95%" id="tani" name="tani" placeholder="内容量の単位（g,個）等"></td></tr>
            <tr><td>大カテゴリー</td><td><input type="text" class="form-control" style="width:95%" id="bunrui1" name="bunrui1" placeholder="例：物販"></td></tr>
            <tr><td>中カテゴリー</td><td><input type="text" class="form-control" style="width:95%" id="bunrui2" name="bunrui2" placeholder="例：食品"></td></tr>
            <tr><td>小カテゴリー</td><td><input type="text" class="form-control" style="width:95%" id="bunrui3" name="bunrui3" placeholder="例：惣菜"></td></tr>
            <tr><td>レジ対象</td><td><label for="hyoujiKBN1" style="float:left;width:8rem;">
                     <input type="checkbox" style="vertical-align:middle;" id="hyoujiKBN1" name="hyoujiKBN1" checked="checked">表示する
                </label></td></tr>
            <tr><td>表示順</td><td><input type="text" class="form-control" style="width:50%" id="hyoujiNO" name="hyoujiNO" placeholder="レジ表示順。未指定の場合は「カテゴリー大>中>小>商品名」の五十音順" value=0></td></tr>
        </table>

        
        <input type="hidden" class="form-control" id="hyoujiKBN2" name="hyoujiKBN2" value="">
        <input type="hidden" class="form-control" id="hyoujiKBN3" name="hyoujiKBN3" value="">

        <!--用途が未定なので非表示
        <div class="form-group form-inline">
            <label for="hyoujiKBN2" class="col-2 col-md-1 control-label">表示区分2</label>
            <div class=" col-10">
                <input type="text" class="form-control" id="hyoujiKBN2" name="hyoujiKBN2">
            </div>
        </div>
        <div class="form-group form-inline">
            <label for="hyoujiKBN3" class="col-2 col-md-1 control-label">表示区分3</label>
            <div class=" col-10">
                <input type="text" class="form-control" id="hyoujiKBN3" name="hyoujiKBN3">
            </div>
        </div>
        -->
        <br>
        <div class="col-2 col-md-1" style=" padding:0; margin-top:10px;">
            <button type="submit" class="btn btn-primary" style="width:100%;" name="btn" value="登録">登  録</button>
        </div>
    </form>
    </div>

    <script type="text/javascript" language="javascript">
        var select = document.getElementById('zeikbn');
        var tanka = document.getElementById('tanka');
        var new_tanka = document.getElementById('new_tanka');
        var shouhizei = document.getElementById('shouhizei');
        var zkomitanka = document.getElementById('zkomitanka');
        var kominuki = document.getElementsByName('options')
        var zei_math = function(){
            if(select.value=='0'){//非課税
                zkomitanka.value=new_tanka.value;
                tanka.value = new_tanka.value;
                shouhizei.value=0;
            }else if(kominuki[0].checked){//税込
                switch(select.value){
                    case '1001':
                        zkomitanka.value=new_tanka.value;
                        shouhizei.value=new_tanka.value - Math.round(new_tanka.value / (1 + 8 / 100));
                        tanka.value = Math.round(new_tanka.value / (1 + 8 / 100));
                        break;
                    case '1101':
                        zkomitanka.value=new_tanka.value;
                        shouhizei.value=new_tanka.value - Math.round(new_tanka.value / (1 + 10 / 100));
                        tanka.value = Math.round(new_tanka.value / (1 + 10 / 100));
                        break;
                }
            }else if(kominuki[1].checked){//税抜
                switch(select.value){
                    case '1001':
                        zkomitanka.value=Math.round(new_tanka.value * (1 + 8 / 100));
                        tanka.value = new_tanka.value;
                        shouhizei.value=Math.round(new_tanka.value * (8 / 100));
                        break;
                    case '1101':
                        zkomitanka.value=Math.round(new_tanka.value * (1 + 10 / 100));
                        tanka.value = new_tanka.value;
                        shouhizei.value=Math.round(new_tanka.value * (10 / 100));
                        break;
                }
            }else{
                //
            }
        }
    </script>

</body>
</html>

<?php
$stmt  = null;
$pdo_h = null;
?>


















