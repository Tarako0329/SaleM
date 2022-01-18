<!DOCTYPE html>
<html lang="ja">
<?php
require "php_header.php";

if($_POST["LOGIN_EMAIL"]<>""){
    $sqlstr = $mysqli->prepare("select count(*) as kensu from Users where mail=? and password=?");
    $sqlstr->bind_param("ss",$_POST["LOGIN_EMAIL"],passEX($_POST["LOGIN_PASS"],$_POST["LOGIN_EMAIL"],$key));
    $sqlstr->execute();
    $sqlstr->store_result();

    $sqlstr->bind_result($col1);
    $sqlstr->fetch();
    if($col1==0){
        $errmsg="<div style='color:red'>メールアドレスもしくはパスワードが違います。</div>";
    }else{
        //リダイレクト
        header("HTTP/1.1 301 Moved Permanently");
        header("Location: menu.php");
 
    }
}
?>
<head>
    <?php 
    //共通部分、bootstrap設定、フォントCND、ファビコン等
    include "head.html" 
    ?>
    <!--ページ専用CSS-->
    <link rel="stylesheet" href="css/style_index.css" >
    <script src="/script/index.js"></script>
    <TITLE><?php echo $title." ようこそ";?></TITLE>
</head>
 
<header style="flex-wrap:wrap">
    <div class="title" style="width: 100%;"><a href="index.php"><?php echo $title;?></a></div>
    <div style="font-size:1rem;"> ようこそWEBREZへ</div>

</header>
<body>
    <div class="container">
        <div class="card card-container">
            <!-- 
            <img class="profile-img-card" src="//lh3.googleusercontent.com/-6V8xOA6M7BA/AAAAAAAAAAI/AAAAAAAAAAA/rzlHcD0KYwo/photo.jpg?sz=120" alt="" />
            <img id="profile-img" class="profile-img-card" src="//ssl.gstatic.com/accounts/ui/avatar_2x.png" />
            <p id="profile-name" class="profile-name-card"></p>
            -->
            <?php echo $errmsg; ?>
            <form class="form-signin" method="post" action="index.php">
                <span id="reauth-email" class="reauth-email"></span>
                <input type="email" id="inputEmail" class="form-control" placeholder="Email address" name="LOGIN_EMAIL" required autofocus>
                <input type="password" id="inputPassword" class="form-control" name="LOGIN_PASS" placeholder="Password" required>
                <div id="remember" class="checkbox">
                    <label>
                        <input type="checkbox" value="remember-me"> Remember me
                    </label>
                </div>
                <button class="btn btn-lg btn-primary btn-block btn-signin" type="submit">ロ グ イ ン</button>
            </form><!-- /form -->
            <a href="#" class="forgot-password">
                Forgot the password?
            </a>
            <hr>
            <a href="account_create.php?mode=0" class="btn btn-lg btn-primary btn-block btn-signin" style="padding-top:8px" >新 規 登 録</a>
        </div><!-- /card-container -->
    </div><!-- /container -->    
</body>