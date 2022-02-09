<!DOCTYPE html>
<html lang="ja">
<?php
//ユーザ登録、登録情報の修正画面
//mode 0:新規　1:更新　3:確認(新規) 4:確認(更新)

require "php_header.php";

if($_GET["mode"]<>""){
    $mode=$_GET["mode"];
}elseif($_POST["MODE"]<>""){
    $mode=$_POST["MODE"];
}else{
    //必ずモード値が渡される
    echo "不正アクセス";
    exit;
}

if((isset($_GET["csrf_token"]) || empty($_POST)) && $mode<>0){
    if(csrf_chk_nonsession_get($_GET["csrf_token"])==false){
        $_SESSION["EMSG"]="セッションが正しくありませんでした。";
        header("HTTP/1.1 301 Moved Permanently");
        header("Location: index.php");
        exit();
    }
}

//unset($_SESSION["user_id"]);

if($mode==1 || $mode==4){
    //更新モードの場合、session[usr_id]のチェック
    $rtn=check_session_userid();
}

if(csrf_chk()==false && $mode>=3){
    $_SESSION["EMSG"]="セッションが正しくありませんでした";
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: index.php");
}



//新規登録時の重複フラグ
$choufuku_flg=0;

$btnname = "確　認";

if($_POST["BTN"] == "確　認"){
    //入力内容の確認モード
    if($mode==0){
        //新規の場合メールアドレスの重複チェック
        $sqlstr="select count(*) as kensu from Users where mail=?";
        $stmt = $pdo_h->prepare($sqlstr);
        $stmt->bindValue(1, $_POST["MAIL"], PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $col1 = $row[0]["kensu"];
        if($col1>0){
            $choufuku_flg=1;
        }
        $btnname = "登　録";
        $mode=3;
    }elseif($mode==1){
        $btnname = "更　新";
        $mode=4;
    }
    
    
    //POSTをSESSIONに格納
    $_SESSION["MAIL"] = $_POST["MAIL"];
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

}elseif($_POST["BTN"] == "登　録"){
    $sqlstr="insert into Users values(0,?,?,?,?,?,?,?,?,?,?,?,null,null,null,null,null,null,null,null,null,null)";
    $stmt = $pdo_h->prepare($sqlstr);
    $stmt->bindValue(1, $_SESSION["MAIL"], PDO::PARAM_STR);
    $stmt->bindValue(2, $_SESSION["PASS"], PDO::PARAM_STR);
    $stmt->bindValue(3, $_SESSION["QUESTION"], PDO::PARAM_STR);
    $stmt->bindValue(4, $_SESSION["ANSWER"], PDO::PARAM_STR);
    $stmt->bindValue(5, $_SESSION["LOGINREZ"], PDO::PARAM_STR);
    $stmt->bindValue(6, $_SESSION["NAME"], PDO::PARAM_STR);
    $stmt->bindValue(7, $_SESSION["YAGOU"], PDO::PARAM_STR);
    $stmt->bindValue(8, $_SESSION["zip11"], PDO::PARAM_STR);
    $stmt->bindValue(9, $_SESSION["addr11"], PDO::PARAM_STR);
    $stmt->bindValue(10, $_SESSION["ADD2"], PDO::PARAM_STR);
    $stmt->bindValue(11, $_SESSION["ADD3"], PDO::PARAM_STR);
    $flg=$stmt->execute();
    
    if($flg){
        $stmt2 = $pdo_h->prepare("select uid from Users where mail=?");
        $stmt2->bindValue(1, $_SESSION["MAIL"], PDO::PARAM_STR);
        $stmt2->execute();
        $tmp = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        $_SESSION["user_id"]=$tmp[0]["uid"];
        echo "<meta http-equiv='refresh' content=' 5; url=./menu.php'>";
        echo secho($_POST["MAIL"])."　が登録されました。<br>5秒後、メニュー画面に遷移します。";
        exit();
    }else{
        echo "登録が失敗しました。";
    }
    //echo $_POST["hyoujiKBN1"];
    //リダイレクトTOP画面へ
}elseif($_POST["BTN"] == "更　新"){
    $sqlstr="update Users set mail=?,password=?,question=?,answer=?,loginrez=?,name=?,yagou=?,yubin=?,address1=?,address2=?,address3=? where uid=?";
    $stmt = $pdo_h->prepare($sqlstr);
    $stmt->bindValue(1, $_SESSION["MAIL"], PDO::PARAM_STR);
    $stmt->bindValue(2, $_SESSION["PASS"], PDO::PARAM_STR);
    $stmt->bindValue(3, $_SESSION["QUESTION"], PDO::PARAM_STR);
    $stmt->bindValue(4, $_SESSION["ANSWER"], PDO::PARAM_STR);
    $stmt->bindValue(5, $_SESSION["LOGINREZ"], PDO::PARAM_STR);
    $stmt->bindValue(6, $_SESSION["NAME"], PDO::PARAM_STR);
    $stmt->bindValue(7, $_SESSION["YAGOU"], PDO::PARAM_STR);
    $stmt->bindValue(8, $_SESSION["zip11"], PDO::PARAM_STR);
    $stmt->bindValue(9, $_SESSION["addr11"], PDO::PARAM_STR);
    $stmt->bindValue(10, $_SESSION["ADD2"], PDO::PARAM_STR);
    $stmt->bindValue(11, $_SESSION["ADD3"], PDO::PARAM_STR);
    $stmt->bindValue(12, $_SESSION["user_id"], PDO::PARAM_INT);
    $flg=$stmt->execute();
    
    if($flg){
        echo "<meta http-equiv='refresh' content=' 5; url=./menu.php'>";
        echo "更新されました。";
        $mode=1;
        //exit();
    }else{
        echo "登録が失敗しました。";
    }
}

if($mode==1){
    $sqlstr="select * from Users where uid=?";
    $stmt = $pdo_h->prepare($sqlstr);
    $stmt->bindValue(1, $_SESSION["user_id"], PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $_SESSION["MAIL"] = $row[0]["mail"];
    //$_SESSION["PASS"] = passEX($_POST["PASS"],$_POST["MAIL"],$key);
    $_SESSION["QUESTION"] = $row[0]["question"];
    $_SESSION["ANSWER"] = $row[0]["answer"];
    $_SESSION["LOGINREZ"] = $row[0]["loginrez"];
    $_SESSION["NAME"] = $row[0]["name"];
    $_SESSION["YAGOU"] = $row[0]["yagou"];
    $_SESSION["zip11"] = $row[0]["yubin"];
    $_SESSION["addr11"] = $row[0]["address1"];
    $_SESSION["ADD2"] = $row[0]["address2"];
    $_SESSION["ADD3"] = $row[0]["address3"];
}
$token=csrf_create();

?>
<head>
    <?php 
    //共通部分、bootstrap設定、フォントCND、ファビコン等
    include "head.html" 
    ?>
    <!--ページ専用CSS-->
    <link rel="stylesheet" href="css/style_account_create.css" >
    <TITLE><?php echo secho($title)." ユーザー登録";?></TITLE>
    <!--郵便場号から住所取得-->
    <script src="https://ajaxzip3.github.io/ajaxzip3.js" charset="UTF-8"></script>
</head>
<header style="flex-wrap:wrap">
    <div class="title" style="width: 100%;"><a href="<?php if($mode==1 || $mode==4){echo "menu.php";}else{echo "index.php";}?>"><?php echo secho($title);?></a></div>
    <p style="font-size:1rem;">  ユーザー登録</p>
</header>

<body>
    <?php
    if($choufuku_flg==1){
        //メールアドレスの検索結果が１件以上の場合
        echo "このメールアドレスは登録済みです。<a href='index.php'>TOP画面</a>に戻ってログインして下さい。<br>パスワードを忘れた場合は再発行してログインして下さい。<br>";
        exit;
    }
    ?>
    <div class="container" style="padding-top:15px;">
    <div class="col-12 col-md-8">
    <form method="post" action="account_create.php" style="font-size:1.5rem">
        <input type="hidden" name="csrf_token" value="<?php echo $token; ?>">
        <input type="hidden" name="MODE" value=<?php echo $mode; ?>>
        <div class="form-group">
            <label for="mail" >メールアドレス</label>
            <input type="email" maxlength="40" class="form-control" id="mail" name="MAIL" required="required" placeholder="必須" <?php if($mode>=3){echo "readonly='readonly' ";} if($mode>=1){echo "value='".secho($_SESSION["MAIL"])."'";}elseif($mode==0){echo "value='".secho(rot13decrypt($_GET["acc"]))."'";}  ?>>
        </div>
        <div class="form-group">
            <label for="pass" >パスワード(6桁以上)</label>
            <input type="password" minlength="6" class="form-control" id="pass" required="required" placeholder="必須" <?php if($mode>=3){echo "readonly='readonly' ";} if($mode>=1){echo "value='".secho($_POST["PASS"])."'";}  ?>>
        </div>
        <div class="form-group">
            <label for="pass2">パスワード（確認）</label>
            <input type="password" minlength="6" class="form-control" id="pass2" oninput="Checkpass(this)" name="PASS" required="required" placeholder="必須" <?php if($mode>=3){echo "readonly='readonly' ";} if($mode>=1){echo "value='".secho($_POST["PASS"])."'";}  ?>>
        </div>
        <div class="form-group">
            <label for="question" >秘密の質問(パスワードを忘れたときに使用します)</label>
            <input type="text" maxlength="20" class="form-control" id="question" name="QUESTION" required="required" placeholder="例：初恋の人の名前" <?php if($mode>=3){echo "readonly='readonly' ";} if($mode>=1){echo "value='".secho($_SESSION["QUESTION"])."'";}  ?>>
        </div>
        <div class="form-group">
            <label for="answer" >答え</label>
            <input type="text" maxlength="20" class="form-control" id="answer" name="ANSWER" required="required" placeholder="例：ささき" <?php if($mode>=3){echo "readonly='readonly' ";} if($mode>=1){echo "value='".secho($_SESSION["ANSWER"])."'";}  ?>>
            <small id="answer" class="form-text text-muted">ひらがな・半角英数・スペース不使用を推奨</small>
        </div>
        <br>
        <div class="form-group form-check">
            <input type="checkbox" class="form-check-input" id="loginrez" name="LOGINREZ" <?php if($mode>=1){if($_SESSION["LOGINREZ"]=="on"){echo "checked";};} ?>>
            <label class="form-check-label" for="loginrez" style="padding-left:5px;padding-top:3px">ログイン後レジ画面表示</label>
        </div>
<?php
    if($mode==1 || $mode==4){
?>
        <br>
        <hr>
        <div>ここから下は請求書・納品書・自動送信メール等に使用します。<br>使用しない方は入力不要です。</div>
        <hr>
        <div class="form-group">
            <label for="name" >姓名</label>
            <input type="text" class="form-control" id="name" name="NAME" placeholder="納品書・請求書等に使用" <?php if($mode>=3){echo "readonly='readonly' ";} if($mode>=1){echo "value='".secho($_SESSION["NAME"])."'";}  ?>>
        </div>
        <div class="form-group">
            <label for="yagou" >屋号</label>
            <input type="text" class="form-control" id="yagou" name="YAGOU" placeholder="納品書・請求書等に使用" <?php if($mode>=3){echo "readonly='readonly' ";} if($mode>=1){echo "value='".secho($_SESSION["YAGOU"])."'";}  ?>>
        </div>
        <div class="form-group">
            <label for="yubin" >郵便番号('-'抜き)</label>
            <input type="text" class="form-control" id="yubin" name="zip11" onKeyUp="AjaxZip3.zip2addr(this,'','addr11','addr11');"　placeholder="納品書・請求書等に使用" <?php if($mode>=3){echo "readonly='readonly' ";} if($mode>=1){echo "value='".secho($_SESSION["zip11"])."'";}  ?>>
        </div>
        <div class="form-group">
            <label for="add1" >住所１</label>
            <input type="text" maxlength="20" class="form-control" id="add1" name="addr11" placeholder="納品書・請求書等に使用。住所1行目" <?php if($mode>=3){echo "readonly='readonly' ";} if($mode>=1){echo "value='".secho($_SESSION["addr11"])."'";}  ?>>
        </div>
        <div class="form-group">
            <label for="add2" >住所２</label>
            <input type="text" maxlength="20" class="form-control" id="add2" name="ADD2" placeholder="納品書・請求書等に使用。住所2行目" <?php if($mode>=3){echo "readonly='readonly' ";} if($mode>=1){echo "value='".secho($_SESSION["ADD2"])."'";}  ?>>
        </div>
        <div class="form-group">
            <label for="add3" >住所３</label>
            <input type="text" maxlength="20" class="form-control" id="add3" name="ADD3" placeholder="納品書・請求書等に使用。住所3行目" <?php if($mode>=3){echo "readonly='readonly' ";} if($mode>=1){echo "value='".secho($_SESSION["ADD3"])."'";}  ?>>
        </div>
<?php
    }
?>
        <div class="col-2 col-md-1" style=" padding:0; margin-top:10px;">
            <button type="submit" class="btn btn-primary" style="width:150%;hight:150%;font-size:1.5rem" name="BTN" value="<?php echo $btnname; ?>"><?php echo secho($btnname); ?></button>
        </div>
        <br>
    </form>
    </div>
    </div>
</body>
<script language="JavaScript" type="text/javascript">
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
// -->
</script>
</html>
<?php
    $stmt=null;
    $pdo_h=null;
?>




















