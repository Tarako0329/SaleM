<!DOCTYPE html>
<html lang="ja">
<?php
//ユーザ登録、登録情報の修正画面
//$mode 0:新規　1:更新　3:確認(新規) 4:確認(更新) 5:登録完了（更新・新規共通）6:登録失敗
require "php_header.php";

//GET or POST からモード値を取得。無い場合は不正アクセス
if($_GET["mode"]<>""){
    $mode=$_GET["mode"];
}elseif($_POST["MODE"]<>""){
    $mode=$_POST["MODE"];
}else{
    //必ずモード値が渡される
    echo "不正アクセス";
    exit();
}
$shoukai="";
if(!empty($_GET["shoukai"])){
    $shoukai=$_GET["shoukai"];
}elseif(!empty($_POST["shoukai"])){
    $shoukai=$_POST["shoukai"];
}

//GETのセッションチェック
if($mode==5 || $mode==6){
}elseif((isset($_GET["csrf_token"]) || empty($_POST)) && $mode<>0){
    if(csrf_chk_nonsession_get($_GET["csrf_token"])==false){
        $_SESSION["EMSG"]="セッションが正しくありませんでした。";
        header("HTTP/1.1 301 Moved Permanently");
        header("Location: index.php");
        exit();
    }
}elseif(csrf_chk()==false && $mode>=3){
    //POSTのセッションチェック
    $_SESSION["EMSG"]="セッションが正しくありませんでした";
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: index.php");
    exit();
}

if($mode==1 || $mode==4){
    //更新モードの場合、session[usr_id]のチェック
    $rtn=check_session_userid($pdo_h);
}


if($mode==0){
    //同一端末の前回ログイン情報をクリアする
    //Cookie のトークンを削除
    setCookie("webrez_token", '', -1, "/", null, TRUE, TRUE); // secure, httponly
    //古くなったトークンを削除
    delete_old_token($cookie_token, $pdo_h);
    //セッション変数のクリア
    $_SESSION = array();

    //GETからメールアドレスを復元
    $_SESSION["MAIL"]=rot13decrypt2($_GET["acc"]);
    $next_mode=3;
}

if($mode==1){
    //更新モード：ユーザ情報取得
    $sqlstr="select * from Users where uid=?";
    $stmt = $pdo_h->prepare($sqlstr);
    $stmt->bindValue(1, $_SESSION["user_id"], PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $_SESSION["MAIL"] = $row[0]["mail"];
    $_SESSION["moto_MAIL"] = $row[0]["mail"]; //メールアドレスを変更したかどうか比較するための元ｱﾄﾞﾚｽ
    $_SESSION["QUESTION"] = $row[0]["question"];
    $_SESSION["ANSWER"] = $row[0]["answer"];
    $_SESSION["LOGINREZ"] = $row[0]["loginrez"];
    $_SESSION["NAME"] = $row[0]["name"];
    $_SESSION["YAGOU"] = $row[0]["yagou"];
    $_SESSION["zip11"] = $row[0]["yubin"];
    $_SESSION["addr11"] = $row[0]["address1"];
    $_SESSION["ADD2"] = $row[0]["address2"];
    $_SESSION["ADD3"] = $row[0]["address3"];
    $_SESSION["chk_pass"]="";
    $next_mode=4;
}

if($mode==3 || $mode==4){
    //入力内容の確認モード
    if($mode==4 && $_SESSION["moto_MAIL"] <> $_POST["MAIL"]){
        //更新の場合メールアドレスの重複チェック
        $sqlstr="select count(*) as kensu from Users where mail=?";
        $stmt = $pdo_h->prepare($sqlstr);
        $stmt->bindValue(1, $_POST["MAIL"], PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $col1 = $row[0]["kensu"];
        
        //新規登録時の重複フラグ
        if($col1>0){
            $choufuku_flg=1;
            $mode=1;
            $next_mode=4;
        }else{
            $choufuku_flg=0;
        }
    }
    //POSTをSESSIONに格納
    $_SESSION["MAIL"] = $_POST["MAIL"];
    $_SESSION["chk_pass"] = $_POST["chk_pass"];
    $_SESSION["PASS"] = passEX($_POST["PASS"],$_POST["MAIL"],$key);
    $_SESSION["QUESTION"] = $_POST["QUESTION"];
    $_SESSION["ANSWER"] = $_POST["ANSWER"];
    $_SESSION["LOGINREZ"] = $_POST["LOGINREZ"];
    $_SESSION["NAME"] = $_POST["NAME"];
    $_SESSION["YAGOU"] = $_POST["YAGOU"];
    $_SESSION["zip11"] = $_POST["zip11"];
    $_SESSION["addr11"] = $_POST["addr11"];
    $_SESSION["ADD2"] = $_POST["ADD2"];
    $_SESSION["ADD3"] = $_POST["ADD3"];
    if(rot13decrypt2(secho($shoukai))!=""){
        $_SESSION["SHOUKAI"] = rot13decrypt2(secho($shoukai))-10000;
    }else{
        $_SESSION["SHOUKAI"] = NULL;
    }
    $_SESSION["MOTO_PASS"] = $_POST["PASS"];
}

$token=csrf_create();

//ボタン名のセット
if($mode==0 || $mode==1){
    $btnname = "確　認";
}elseif($mode==3){
    $btnname = "登　録";
}elseif($mode==4){
    $btnname = "更　新";
}elseif($mode==5 || $mode==6){
    //ボタン表示等なし。
}else{
    $_SESSION["EMSG"]="モード値が不正です。（mode=".$mode."）";
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: index.php");
    exit();
}
?>
<head>
    <?php 
    //共通部分、bootstrap設定、フォントCND、ファビコン等
    include "head.html" 
    ?>
    <!--ページ専用CSS-->
    <link rel='stylesheet' href='css/style_account_create.css?<?php echo $time; ?>' >
    <TITLE><?php echo secho($title)." ユーザー登録";?></TITLE>
    <!--郵便場号から住所取得-->
    <script src='https://ajaxzip3.github.io/ajaxzip3.js' charset='UTF-8'></script>
</head>
<header class='header-color common_header' style='flex-wrap:wrap'>
    <div class='title' style='width: 100%;'><a href='<?php if($mode==1 || $mode==4){echo "menu.php";}else{echo "index.php";}?>'><?php echo secho($title);?></a></div>
    <p style='font-size:1rem;color:var(--user-disp-color);font-weight:400;'>  ユーザー登録</p>
</header>

<body class='common_body'>
    <?php
    if($choufuku_flg==1){
        //メールアドレスの検索結果が１件以上の場合
        echo "このメールアドレスは登録済みです。<a href='index.php'>TOP画面</a>に戻ってログインして下さい。<br>パスワードを忘れた場合は<a href='forget_pass_sendurl.php'>コチラ</a>からパスワードの再設定をお願いします。<br>";
    }
    if($mode==5){
        echo "<meta http-equiv='refresh' content=' 5; url=./menu.php?csrf_token=".$token."'>";
        echo "情報が登録されました。<br>5秒後、メニュー画面に遷移します。";
        exit();
    }elseif($mode==6){
        echo "登録が失敗しました。<br>";
        echo "システム管理者へ";
        exit();
    }
    ?>
    <div class='container' style='padding-top:15px;'>
    <div class='col-12 col-md-8'>
    <?php   if($mode==0 || $mode==1){ ?>
        <form method='post' id='form1' action='account_create.php' style='font-size:1.5rem'>
    <?php   }else{ ?>
        <form method='post' id='form1' action='account_sql.php' style='font-size:1.5rem'>
    <?php   } ?>
        <input type='hidden' name='csrf_token' value='<?php echo $token; ?>'>
        <input type='hidden' name='MODE' value=<?php echo $next_mode; ?>>
        <input type='hidden' name='shoukai' value=<?php echo $shoukai; ?>>
        <div class='form-group'>
            <label for='mail' >メールアドレス</label>
            <input type='email' maxlength='40' class='form-control' id='mail' name='MAIL' required='required' placeholder='必須' <?php if($mode>=3){echo "readonly='readonly' ";} echo "value='".secho($_SESSION["MAIL"])."'"; ?>>
        </div>

        <hr>
    <?php
        if($mode==1 || $mode==4){
    ?>
        <div class='form-group'>
        <label for='chk_pass' >
        	<input type='checkbox'  id='chk_pass' name='chk_pass' <?php if($mode>=1){if($_SESSION["chk_pass"]=="on"){echo "checked";};} ?>>パスワードを変更する
        </label>
        </div>
    <?php
        }
    ?>
        <div class='form-group'>
            <label for='pass' >パスワード(6桁以上)</label>
            <input type='password' minlength='6' class='form-control' id='pass' 
            <?php if($mode==0){echo "required='required' placeholder='必須'";} if($mode>=1){echo "readonly='readonly' ";} if($mode>=2){echo "value='".secho($_POST["PASS"])."'";}  ?>>
        </div>
        <div class='form-group'>
            <label for='pass2'>パスワード（確認）</label>
            <input type='password' minlength='6' class='form-control' id='pass2' oninput='Checkpass(this)' name='PASS' 
            <?php if($mode==0){echo "required='required' placeholder='必須'";} if($mode>=1){echo "readonly='readonly' ";} if($mode>=2){echo "value='".secho($_POST["PASS"])."'";}  ?>>
        </div>
<hr>
        <div class='form-group'>
            <label for='question' >秘密の質問(パスワードを忘れたときに使用します)</label>
            <input type='text' maxlength='20' class='form-control' id='question' name='QUESTION' required='required' placeholder='例：初恋の人の名前' <?php if($mode>=3){echo "readonly='readonly' ";} if($mode>=1){echo "value='".secho($_SESSION["QUESTION"])."'";}  ?>>
        </div>
        <div class='form-group'>
            <label for='answer' >答え</label>
            <input type='text' maxlength='20' class='form-control' id='answer' name='ANSWER' required='required' placeholder='例：ささき' <?php if($mode>=3){echo "readonly='readonly' ";} if($mode>=1){echo "value='".secho($_SESSION["ANSWER"])."'";}  ?>>
            <small id='answer' class='form-text text-muted'>ひらがな・半角英数・スペース不使用を推奨</small>
        </div>
        <br>
        <!--
        <div class="form-group form-check">
            <input type="checkbox" class="form-check-input" id="loginrez" name="LOGINREZ" <?php if($mode>=1){if($_SESSION["LOGINREZ"]=="on"){echo "checked";};} ?>>
            <label class="form-check-label" for="loginrez" style="padding-left:5px;padding-top:3px">ログイン後レジ画面表示</label>
        </div>
        -->
        <div class='form-group'>
        <label for='loginrez' >
        	<input type='checkbox'  id='loginrez' name='LOGINREZ' <?php if($mode>=1){if($_SESSION["LOGINREZ"]=="on"){echo "checked";};} ?>>ログイン後レジ画面表示
        </label>
        </div>

<?php
    if($mode==1 || $mode==4){
?>
        <br>
        <hr>
        <div>ここから下は請求書・納品書・自動送信メール等に使用します。<br>使用しない方は入力不要です。</div>
        <hr>
        <div class='form-group'>
            <label for='name' >姓名</label>
            <input type='text' class='form-control' id='name' name='NAME' placeholder='納品書・請求書等に使用' <?php if($mode>=3){echo "readonly='readonly' ";} if($mode>=1){echo "value='".secho($_SESSION["NAME"])."'";}  ?>>
        </div>
        <div class='form-group'>
            <label for='yagou' >屋号</label>
            <input type='text' class='form-control' id='yagou' name='YAGOU' placeholder='納品書・請求書等に使用' <?php if($mode>=3){echo "readonly='readonly' ";} if($mode>=1){echo "value='".secho($_SESSION["YAGOU"])."'";}  ?>>
        </div>
        <div class='form-group'>
            <label for='yubin' >郵便番号('-'抜き)</label>
            <input type='text' class='form-control' id='yubin' name='zip11' onKeyUp='AjaxZip3.zip2addr(this,'','addr11','addr11');'　placeholder='納品書・請求書等に使用' <?php if($mode>=3){echo "readonly='readonly' ";} if($mode>=1){echo "value='".secho($_SESSION["zip11"])."'";}  ?>>
        </div>
        <div class='form-group'>
            <label for='add1' >住所１</label>
            <input type='text' maxlength='20' class='form-control' id='add1' name='addr11' placeholder='納品書・請求書等に使用。住所1行目' <?php if($mode>=3){echo "readonly='readonly' ";} if($mode>=1){echo "value='".secho($_SESSION["addr11"])."'";}  ?>>
        </div>
        <div class='form-group'>
            <label for='add2' >住所２</label>
            <input type='text' maxlength='20' class='form-control' id='add2' name='ADD2' placeholder='納品書・請求書等に使用。住所2行目' <?php if($mode>=3){echo "readonly='readonly' ";} if($mode>=1){echo "value='".secho($_SESSION["ADD2"])."'";}  ?>>
        </div>
        <div class='form-group'>
            <label for='add3' >住所３</label>
            <input type='text' maxlength='20' class='form-control' id='add3' name='ADD3' placeholder='納品書・請求書等に使用。住所3行目' <?php if($mode>=3){echo "readonly='readonly' ";} if($mode>=1){echo "value='".secho($_SESSION["ADD3"])."'";}  ?>>
        </div>
<?php
    }
?>
        <div class='col-2 col-md-1' style=' padding:0; margin-top:10px;'>
            <button type='submit' class='btn btn-primary' style='width:180%;hight:150%;font-size:1.5rem' name='BTN' value='<?php echo $btnname; ?>'><?php echo secho($btnname); ?></button>
        </div>
        <br>
    </form>
    </div>
    </div>
</body>
<script language='JavaScript' type='text/javascript'>
<!--
    function Checkpass(input){
        //IE対応の為変更
        
        var pass = document.getElementById("pass").value; //メールフォームの値を取得
        var passConfirm = input.value; //メール確認用フォームの値を取得(引数input)
        
        // パスワードの一致確認
        if(pass != passConfirm){
            input.setCustomValidity('パスワードが一致しません'); // エラーメッセージのセット
        }else{
            input.setCustomValidity(''); // エラーメッセージのクリア
        }
    }
    
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
    document.getElementById('chk_pass').onclick = function(){
        const a = document.getElementById('pass');
        const b = document.getElementById('pass2');
        if(a.required==true){
            a.required=false;
            b.required=false;
            a.placeholder='';
            b.placeholder='';
            a.readOnly='readonly';
            b.readOnly='readonly';
        }else{
            a.required=true;
            b.required=true;
            a.placeholder='必須';
            b.placeholder='必須';
            a.readOnly='';
            b.readOnly='';
        }
    }
    
// -->
</script>
</html>
<?php
$stmt=null;
$pdo_h=null;
?>





