<!DOCTYPE html>
<html lang="ja">
<?php
//ユーザ登録、登録情報の修正画面
//mode 0:新規　1:更新　3:確認(新規) 4:確認(更新)

require "php_header.php";

if($_POST["BTN"] == "send"){
    //入力内容の確認モード
    //新規の場合メールアドレスの重複チェック
    $sqlstr="select count(*) as kensu from Users where mail=?";
    $stmt = $pdo_h->prepare($sqlstr);
    $stmt->bindValue(1, $_POST["MAIL"], PDO::PARAM_STR);
    $stmt->execute();
    $row = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $col1 = $row[0]["kensu"];

    if($col1>0){
        $choufuku_flg=1;
    }else{
        //登録用メール送信
        $to = $_POST["MAIL"];
        $subject = "WEBREZ登録のご案内";
        $mail2=rot13encrypt($to);
        
        $s_name=$_SERVER['SCRIPT_NAME'];
        $dir_a=explode("/",$s_name,-1);

        $body = <<< "EOM"
            WEBREZ+（ウェブレジプラス）にご興味をもっていただきありがとうございます。
            こちらのURLから登録をお願いいたします。
            
            https://green-island.mixh.jp/SaleM/$dir_a[2]/account_create.php?mode=0&acc=$mail2
            EOM;
        if(FROM==""){
            //.env にメールアカウント情報が設定されてない場合、phpのsendmailで送付
            define("FROM", "information@WEBREZ.jp");
            $okflg=touroku_mail($to,$subject,$body);
        }else{
            //qdmailでメール送付
            $okflg = send_mail($to,$subject,$body);
        }
    }
}else{
    //echo "登録が失敗しました。";
}

?>
<head>
    <?php 
    //共通部分、bootstrap設定、フォントCND、ファビコン等
    include "head.html" 
    ?>
    <!--ページ専用CSS-->
    <link rel="stylesheet" href="css/style_account_create.css" >
    <TITLE><?php echo secho($title)." ユーザー登録";?></TITLE>
</head>
<header style="flex-wrap:wrap">
    <div class="title" style="width: 100%;"><a href="<?php echo "index.php";?>"><?php echo secho($title);?></a></div>
    <p style="font-size:1rem;">  ユーザー登録</p>
</header>

<body>
    <?php
    if($choufuku_flg==1){
        //メールアドレスの検索結果が１件以上の場合
        echo "このメールアドレスは登録済みです。<a href='index.php'>TOP画面</a>に戻ってログインして下さい。<br>パスワードを忘れた場合は再発行してログインして下さい。<br>";
    }elseif($okflg==1){
        echo $_POST['MAIL']." へ登録用のURLを記載したメールを送信いたしました。メールが届いてない場合、メールアドレスが間違えているか、迷惑メールになっている可能性があります。<br>送信元アドレスは ".FROM. "となります。";
    }
    ?>
    <div class="container" style="padding-top:15px;">
    <div class="col-12 col-md-8">
    <form method="post" action="pre_account.php" style="font-size:1.5rem">
        <input type="hidden" name="csrf_token" value="<?php echo $token; ?>">
        <input type="hidden" name="MODE" value=<?php echo $mode; ?>>
        <div class="form-group">
            <label for="mail" >メールアドレス</label>
            <input type="email" maxlength="40" class="form-control" id="mail" name="MAIL" required="required" placeholder="必須" <?php if($mode>=3){echo "readonly='readonly' ";} if($mode>=1){echo "value='".secho($_SESSION["MAIL"])."'";}  ?>>
        </div>
        <div class="col-2 col-md-1" style=" padding:0; margin-top:10px;">
            <button type="submit" class="btn btn-primary" style="width:150%;hight:150%;font-size:1.5rem" name="BTN" value="send">送 信</button>
        </div>
        <br>
    </form>
    </div>
    </div>
</body>

</html>
<?php
    $stmt=null;
    $pdo_h=null;
?>




















