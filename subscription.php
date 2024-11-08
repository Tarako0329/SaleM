<?php
require "php_header.php";
$rtn=check_session_userid($pdo_h);

if(!empty($_GET)){
}else{
    echo "不正アクセス！！";
    exit();
}

$paid_status=(!empty($_GET["status"])?$_GET["status"]:"");

$msg="";
$sqllog="";

try{
    if($paid_status=="paid"){
        $pdo_h->beginTransaction();
        $sqllog .= rtn_sqllog("START TRANSACTION",[]);
    
        //$sql="select DATE_ADD(yuukoukigen, INTERVAL 1 DAY) as keiyakudate from Users where uid=?";
        $sql="select DATE_ADD(yuukoukigen, INTERVAL 1 DAY) as keiyakudate from Users_webrez where uid=?";
        $stmt = $pdo_h->prepare($sql);
        $stmt->bindValue(1, $_SESSION["user_id"], PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if(date("Y-m-d")<$row[0]["keiyakudate"]){
            $keiyakudate=$row[0]["keiyakudate"];
        }else{
            $keiyakudate=date("Y-m-d");
        }
        
         
        //$sqlstr="update Users set yuukoukigen=?,stripe_id=?,keiyakudate=?,plan=? where uid=?";
        $sqlstr="update Users_webrez set yuukoukigen=?,stripe_id=?,keiyakudate=?,plan=? where uid=?";
        $stmt = $pdo_h->prepare($sqlstr);
        $stmt->bindValue(1, NULL, PDO::PARAM_STR);
        $stmt->bindValue(2, $_GET["sid"], PDO::PARAM_STR);
        $stmt->bindValue(3, $keiyakudate, PDO::PARAM_STR);//課金契約の始まる日。トライアル期間中に申し込んだ場合は未来日付が入る。
        $stmt->bindValue(4, $_GET["M"], PDO::PARAM_INT);
        $stmt->bindValue(5, $_SESSION["user_id"], PDO::PARAM_INT);

        $sqllog .= rtn_sqllog($sqlstr,[NULL,$_GET["sid"],$keiyakudate,$_GET["M"],$_SESSION["user_id"]]);
        $stmt->execute();
        $pdo_h->commit();
        $sqllog .= rtn_sqllog("commit",[]);
        sqllogger($sqllog,0);
        $msg= "ご契約が成立しました。<br>本登録頂き、ありがとうございます。<br>引き続き、『WebRez+』をよろしくお願いします。";

        send_mail(SYSTEM_NOTICE_MAIL,"【売上通知】レジ売れたよ","お買い上げUID：".$_SESSION["user_id"]."\r\nおめでとう！");
        /*
        if($flg){
            $msg= "ご契約が成立しました。<br>本登録頂き、ありがとうございます。<br>引き続き、『WebRez+』をよろしくお願いします。";
        }else{
            $msg= "登録が失敗しました。";
        }
        */
    }elseif($paid_status=="unpaid"){
        $msg= "ご契約処理をキャンセルいたしました。";
    }else{
        echo "不明なエラーが発生しました。処理を中止します";
        exit();
    }
    
}catch(Exception $e){
    $pdo_h->rollBack();
    $sqllog .= rtn_sqllog("rollBack",[]);
    sqllogger($sqllog,$e);
    echo "登録が失敗しました。";
}

$token=csrf_create();

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <?php 
    //共通部分、bootstrap設定、フォントCND、ファビコン等
    include "head_bs5.php" 
    ?>
    <!--ページ専用CSS-->
    <link rel="stylesheet" href="css/style_account_create.css?<?php echo $time; ?>" >
    <TITLE><?php echo secho($title)." 契約関連";?></TITLE>
</head>
<header class="header-color common_header" style="flex-wrap:wrap">
    <div class="title" style="width: 100%;"><a href="menu.php"><?php echo $title;?></a></a></div>
    <p style="font-size:1rem;color:var(--user-disp-color);font-weight:400;">  契約関連</p>
</header>

<body class='common_body' style='font-size:1.5rem;'>
    <?php
    echo $msg;
    ?>
    
</body>
</html>