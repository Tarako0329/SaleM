<!DOCTYPE html>
<html lang="ja">
<?php
//ユーザ登録、登録情報の修正画面
require "php_header.php";
//0:新規　1:修正　3:確認

$btnname = "確　認";
if($_GET["mode"]<>""){
    $mode=$_GET["mode"];
}elseif($_POST["MODE"]<>""){
    $mode=$_POST["MODE"];
}

if($_POST["BTN"] == "確　認"){

    if($mode==0){
        //echo $_POST["MAIL"]."<br>";
        //新規の場合メールアドレスの重複チェック
        $sqlstr = $mysqli->prepare("select count(*) as kensu from Users where mail=?");
        $sqlstr->bind_param("s",$_POST["MAIL"]);
        $sqlstr->execute();
        $sqlstr->store_result();

        $sqlstr->bind_result($col1);
        $sqlstr->fetch();
        //echo $col1."---";

        $btnname = "登　録";
    }elseif($mode==1){
        $btnname = "更　新";
    }
    
    $mode=3;

    
}elseif($_POST["BTN"] == "登　録"){
    
    $sqlstr=$mysqli->prepare("insert into Users values(0,?,?,?,?,?,?,?,?,?,?,?,?,null,null,null,null,null,null,null,null,null,null);");
    $sqlstr->bind_param('ssssssssssss',$_POST["MAIL"],passEX($_POST["PASS"],$_POST["MAIL"],$key),$_POST["QUESTION"],$_POST["ANSWER"],$_POST["AUTOLOGIN"],$_POST["LOGINREZ"],$_POST["NAME"],$_POST["YAGOU"],$_POST["zip11"],$_POST["addr11"],$_POST["ADD2"],$_POST["ADD3"]);

	$stmt = $mysqli->query("LOCK TABLES Users WRITE");
    $sqlstr->execute();
	$stmt = $mysqli->query("UNLOCK TABLES");

    echo $_POST["MAIL"]."　が登録されました。<br>";
    //echo $_POST["hyoujiKBN1"];
    //リダイレクトTOP画面へ
}elseif($_POST["BTN"] == "更　新"){
    echo "未実装";
    exit;
}

?>
<head>
    <?php 
    //共通部分、bootstrap設定、フォントCND、ファビコン等
    include "head.html" 
    ?>
    <!--ページ専用CSS-->
    <link rel="stylesheet" href="css/style_account_create.css" >
    <TITLE><?php echo $title." ユーザー登録";?></TITLE>
    <!--郵便場号から住所取得-->
    <script src="https://ajaxzip3.github.io/ajaxzip3.js" charset="UTF-8"></script>
</head>
<header style="flex-wrap:wrap">
    <div class="title" style="width: 100%;"><a href="index.php"><?php echo $title;?></a></div>
    <p style="font-size:1rem;">  ユーザー登録</p>
</header>

<body>
    <?php
    if($col1>0){
        //メールアドレスの検索結果が１件以上の場合
        echo "このメールアドレスは登録済みです。<a href='index.php'>TOP画面</a>に戻ってログインして下さい。<br>パスワードを忘れた場合は再発行してログインして下さい。<br>";
        exit;
    }
    ?>
    <div class="container" style="padding-top:15px;">
    <div class="col-12 col-md-8">
    <form method="post" action="account_create.php">
        <div class="form-group">
            <label for="mail" >メールアドレス</label>
            <input type="email" maxlength="40" class="form-control" id="mail" name="MAIL" required="required" placeholder="必須" <?php if($mode==3){echo "readonly='readonly' value='".$_POST["MAIL"]."'";}  ?>>
        </div>
        <div class="form-group">
            <label for="pass" >パスワード</label>
            <input type="password" minlength="8" class="form-control" id="pass" required="required" placeholder="必須" <?php if($mode==3){echo "readonly='readonly' value='".$_POST["PASS"]."'";}  ?>>
        </div>
        <div class="form-group">
            <label for="pass2">パスワード（確認）</label>
            <input type="password" minlength="8" class="form-control" id="pass2" name="PASS" required="required" placeholder="必須" <?php if($mode==3){echo "readonly='readonly' value='".$_POST["PASS"]."'";}  ?>>
        </div>
        <div class="form-group">
            <label for="question" >秘密の質問(パスワードを忘れたときに使用します)</label>
            <input type="text" maxlength="20" class="form-control" id="question" name="QUESTION" required="required" placeholder="例：初恋の人の名前" <?php if($mode==3){echo "readonly='readonly' value='".$_POST["QUESTION"]."'";}  ?>>
        </div>
        <div class="form-group">
            <label for="answer" >答え</label>
            <input type="text" maxlength="20" class="form-control" id="answer" name="ANSWER" required="required" placeholder="例：ささき" <?php if($mode==3){echo "readonly='readonly' value='".$_POST["ANSWER"]."'";}  ?>>
            <small id="answer" class="form-text text-muted">ひらがな・半角英数・スペース不使用を推奨</small>
        </div>
        <br>
        <div class="form-group form-check">
            <input type="checkbox" class="form-check-input" id="autologin" name="AUTOLOGIN" <?php if($mode==3){if($_POST["AUTOLOGIN"]=="on"){echo "checked";};} ?>>
            <label class="form-check-label" for="autologin"> 自動ログイン有効</label>
        </div>
        <br>
        <div class="form-group form-check">
            <input type="checkbox" class="form-check-input" id="loginrez" name="LOGINREZ" <?php if($mode==3){if($_POST["LOGINREZ"]=="on"){echo "checked";};} ?>>
            <label class="form-check-label" for="loginrez"> ログイン後レジ画面表示</label>
        </div>
        <br>
        <hr>
        <div>ここから下は請求書・納品書・自動送信メール等に使用します。<br>使用しない方は入力不要です。</div>
        <hr>
        <div class="form-group">
            <label for="name" >姓名</label>
            <input type="text" class="form-control" id="name" name="NAME" placeholder="納品書・請求書等に使用" <?php if($mode==3){echo "readonly='readonly' value='".$_POST["NAME"]."'";}  ?>>
        </div>
        <div class="form-group">
            <label for="yagou" >屋号</label>
            <input type="text" class="form-control" id="yagou" name="YAGOU" placeholder="納品書・請求書等に使用" <?php if($mode==3){echo "readonly='readonly' value='".$_POST["YAGOU"]."'";}  ?>>
        </div>
        <div class="form-group">
            <label for="yubin" >郵便番号('-'抜き)</label>
            <input type="text" class="form-control" id="yubin" name="zip11" onKeyUp="AjaxZip3.zip2addr(this,'','addr11','addr11');"　placeholder="納品書・請求書等に使用" <?php if($mode==3){echo "readonly='readonly' value='".$_POST["zip11"]."'";}  ?>>
        </div>
        <div class="form-group">
            <label for="add1" >住所１</label>
            <input type="text" maxlength="20" class="form-control" id="add1" name="addr11" placeholder="納品書・請求書等に使用。住所1行目" <?php if($mode==3){echo "readonly='readonly' value='".$_POST["addr11"]."'";}  ?>>
        </div>
        <div class="form-group">
            <label for="add2" >住所２</label>
            <input type="text" maxlength="20" class="form-control" id="add2" name="ADD2" placeholder="納品書・請求書等に使用。住所2行目" <?php if($mode==3){echo "readonly='readonly' value='".$_POST["ADD2"]."'";}  ?>>
        </div>
        <div class="form-group">
            <label for="add3" >住所３</label>
            <input type="text" maxlength="20" class="form-control" id="add3" name="ADD3" placeholder="納品書・請求書等に使用。住所3行目" <?php if($mode==3){echo "readonly='readonly' value='".$_POST["ADD3"]."'";}  ?>>
        </div>
        <div class="col-2 col-md-1" style=" padding:0; margin-top:10px;">
            <button type="submit" class="btn btn-primary" style="width:100%;" name="BTN" value="<?php echo $btnname; ?>"><?php echo $btnname; ?></button>
        </div>
        <input type="hidden" name="MODE" value=<?php echo $mode; ?>>
    </form>
    </div>
    </div>
</body>
</html>
<?php
    $mysqli->close();
?>




















