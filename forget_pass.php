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
$token = csrf_create();
$msg="";

if($_GET["acc"]<>""){
    $mail=rot13decrypt2($_GET["acc"]);
}else if($_POST["mail"]<>""){
    $mail=$_POST["mail"];
}else{
    echo "不正アクセスです。";
    exit();
}


$sqlstr = "select * from Users where mail=?";
//deb_echo($sqlstr);
$stmt = $pdo_h->prepare( $sqlstr );
$stmt->bindValue(1, $mail, PDO::PARAM_STR);
$rtn=$stmt->execute();
$result=$stmt->fetchAll();

if($_POST["answer"]<>""){
    $sqlstr = "select * from Users where mail=? and answer=?";

    //deb_echo($sqlstr);

    $stmt = $pdo_h->prepare( $sqlstr );
    $stmt->bindValue(1, $mail, PDO::PARAM_STR);
    $stmt->bindValue(2, $_POST["answer"], PDO::PARAM_STR);
    $rtn=$stmt->execute();
    $cnt=$stmt->rowCount();
    $result2=$stmt->fetchAll();
    if($cnt==1){
        $_SESSION["user_id"]=$result2[0]["uid"];
        echo $_SESSION["user_id"]."<br>";
        header("HTTP/1.1 301 Moved Permanently");
        header("Location: account_create.php?mode=1&csrf_token=".$token);
        exit();
    }else{
        $msg="秘密の答えが間違ってます。<br>";
    }
}

?>
<head>
    <?php 
    //共通部分、bootstrap設定、フォントCND、ファビコン等
    include "head.html" 
    ?>
    <!--ページ専用CSS-->
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
    </script>

    
    <TITLE><?php echo $title." forget password";?></TITLE>
</head>
 
<header class="header-color common_header" style="flex-wrap:wrap;height:50px">
    <div class="title" style="width: 100%;"><a href="menu.php"><?php echo $title;?></a></div>
</header>

<body class='common_body' style='padding-top:55px'>
    <div class="container">
        <form method='post' action='forget_pass.php' style='font-size:1.6rem'>
        <p style='color:red'>
        <?php echo $msg;?>
        </p>
        秘密の質問：「<?php echo $result[0]["question"] ?>」
        <br>
        <br>
        <input type='text' class="form-control" style='font-size:1.6rem' name='answer' required="required">
        <br>
        <br>
        <input type='submit' class="btn btn-primary" style="width:150px;hight:30px;font-size:1.5rem" value='回 答'>
        <input type='hidden' name='mail' value='<?php echo $mail; ?>'>
        </form>
    </div>
</body>
<!--
<footer>
</footer>
-->

</html>
<?php

$stmt = null;
$pdo_h = null;
?>


