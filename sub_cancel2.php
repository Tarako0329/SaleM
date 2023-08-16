<?php
require "php_header.php";
$rtn=check_session_userid($pdo_h);

$msg="";

if($_SESSION["flg"]=="succsess"){
    try{
        $pdo_h->beginTransaction();
        $sqllog .= rtn_sqllog("START TRANSACTION",[]);
    
        $sqlstr="update Users set yuukoukigen=?,keiyakudate=?,plan=?,kaiyakudate=? where uid=?";
        $stmt = $pdo_h->prepare($sqlstr);
        $stmt->bindValue(1, $_SESSION["yuukoukigen"], PDO::PARAM_STR);
        $stmt->bindValue(2, NULL, PDO::PARAM_STR);
        $stmt->bindValue(3, NULL, PDO::PARAM_STR);
        $stmt->bindValue(4, date("Y-m-d"), PDO::PARAM_STR);
        $stmt->bindValue(5, $_SESSION["user_id"], PDO::PARAM_INT);
    
        $sqllog .= rtn_sqllog($sqlstr,[NULL,$_GET["sid"],$keiyakudate,$_GET["M"],$_SESSION["user_id"]]);
        $stmt->execute();
        $pdo_h->commit();
        $sqllog .= rtn_sqllog("commit",[]);
        sqllogger($sqllog,0);
        $msg = "解約処理が完了しました。<br><br>ご利用いただきありがとうございました。";
        $mode=5;
        $_SESSION["yuukoukigen"]="";
    
    }catch(Exception $e){
        $pdo_h->rollBack();
        $sqllog .= rtn_sqllog("rollBack",[]);
        sqllogger($sqllog,$e);

        $msg = "解約の登録処理が失敗しました。";
        $mode=6;

    }

}else{
    //stripe側の解約処理が失敗
    $msg = $_SESSION["msg"]."<br>";
    send_mail(SYSTEM_NOTICE_MAIL,"Stripの解約処理が失敗しました",$_SESSION["msg"]);
}
$_SESSION["flg"]="";
$_SESSION["msg"]="";
$token=csrf_create();

?>
<DOCTYPE html>
<html lang="ja">
<head>
    <?php 
    //共通部分、bootstrap設定、フォントCND、ファビコン等
    include "head_bs5.html" 
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