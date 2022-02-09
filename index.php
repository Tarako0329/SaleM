<!DOCTYPE html>
<html lang="ja">
<?php

require "php_header.php";


$_SESSION['csrf_token'] = get_token(); // CSRFのトークンを取得する
//トークンがセットされていたらリダイレクト
if (isset($_COOKIE['webrez_token'])) {
    //exit();
    echo $_COOKIE['webrez_token'];

    header("HTTP/1.1 301 Moved Permanently");
    header("Location: logincheck.php");
}

$errmsg = "";
if(isset($_SESSION["EMSG"])){
    $errmsg="<div style='color:red'>".$_SESSION["EMSG"]."</div>";
    //一度エラーを表示したらクリアする
    $_SESSION["EMSG"]="";
}

?>
<head>
    <?php 
    //共通部分、bootstrap設定、フォントCND、ファビコン等
    include "head.html" 
    ?>
    <!--ページ専用CSS-->
    <link rel="stylesheet" href="css/style_index.css" >
    <script src="script/index.js"></script>
    <TITLE><?php echo secho($title)." ようこそ";?></TITLE>
</head>
 
<header style="flex-wrap:wrap">
    <div class="title" style="width: 100%;"><a href="index.php"><?php echo secho($title);?></a>
    <div style="font-size:1rem;"> ようこそWEBREZへ</div>
</header>

<body>
    <div class="container">
        <div class="card card-container">
            <!-- 
            <img class="profile-img-card" src="//lh3.googleusercontent.com/-6V8xOA6M7BA/AAAAAAAAAAI/AAAAAAAAAAA/rzlHcD0KYwo/photo.jpg?sz=120" alt="" />
            <img id="profile-img" class="profile-img-card" src="//ssl.gstatic.com/accounts/ui/avatar_2x.png" />　class="form-check-input"
            <p id="profile-name" class="profile-name-card"></p>
            -->
            <?php echo $errmsg; ?>
            <form class="form-signin" method="post" action="logincheck.php">
                <span id="reauth-email" class="reauth-email"></span>
                <input type="email" id="inputEmail" class="form-control" placeholder="Email address" name="LOGIN_EMAIL" required autofocus>
                <input type="password" id="inputPassword" class="form-control" name="LOGIN_PASS" placeholder="Password" required>
                <div id="remember" class="checkbox">
                    <label>
                        <input type="checkbox" name="AUTOLOGIN"> Remember 
                    </label>
                </div>

                <button class="btn btn-lg btn-primary btn-block btn-signin" type="submit">ロ グ イ ン</button>
                <input type="hidden" name="csrf_token" value="<?php echo secho($_SESSION['csrf_token']) ?>">
            </form><!-- /form -->
            <a href="#" class="forgot-password">
                Forgot the password?
            </a>
            <hr>
            <!--<a href="account_create.php?mode=0" class="btn btn-lg btn-primary btn-block btn-signin" style="padding-top:8px" >新 規 登 録</a>-->
            <a href="pre_account.php" class="btn btn-lg btn-primary btn-block btn-signin" style="padding-top:8px" >新 規 登 録</a>
        </div><!-- /card-container -->
    </div><!-- /container -->    
</body>
</html>