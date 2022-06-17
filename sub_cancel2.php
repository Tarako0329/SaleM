<DOCTYPE html>
<html lang="ja">
<?php
require "php_header.php";
$rtn=check_session_userid($pdo_h);

$msg="";

if($_SESSION["flg"]=="succsess"){

    $sqlstr="update Users set yuukoukigen=?,keiyakudate=?,plan=?,kaiyakudate=? where uid=?";
    $stmt = $pdo_h->prepare($sqlstr);
    $stmt->bindValue(1, $_SESSION["yuukoukigen"], PDO::PARAM_STR);
    $stmt->bindValue(2, NULL, PDO::PARAM_STR);
    $stmt->bindValue(3, NULL, PDO::PARAM_STR);
    $stmt->bindValue(4, date("Y-m-d"), PDO::PARAM_STR);
    $stmt->bindValue(5, $_SESSION["user_id"], PDO::PARAM_INT);
    $flg=$stmt->execute();
    
    if($flg){
        $msg = "解約処理が完了しました。<br><br>ご利用いただきありがとうございました。";
        $mode=5;
    }else{
        $msg = "解約の登録処理が失敗しました。";
        $mode=6;
    }
    $_SESSION["yuukoukigen"]="";

}else{
    //stripe側の解約処理が失敗
    $msg = $_SESSION["msg"]."<br>";
    send_mail(SYSTEM_NOTICE_MAIL,"Stripの解約処理が失敗しました",$_SESSION["msg"]);
}
$_SESSION["flg"]="";
$_SESSION["msg"]="";
$token=csrf_create();

?>
<head>
    <?php 
    //共通部分、bootstrap設定、フォントCND、ファビコン等
    include "head.html" 
    ?>
    <!--ページ専用CSS-->
    <link rel="stylesheet" href="css/style_account_create.css?<?php echo $time; ?>" >
    <TITLE><?php echo secho($title)." サブスクリプション解約";?></TITLE>
</head>
<header class="header-color common_header" style="flex-wrap:wrap">
    <div class="title" style="width: 100%;"><a href="menu.php"><?php echo $title;?></a></a></div>
    <p class='user_disp' style="font-size:1rem;">サブスクリプション解約</p>
</header>

<body class='common_body' style='font-size:1.5rem;'>
    <?php
    echo $msg."<br>";
    ?>
    
    引き続き、契約満了日までご利用いただけます。
</body>
</html>