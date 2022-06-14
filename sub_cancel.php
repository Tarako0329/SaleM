<!DOCTYPE html>
<html lang="ja">
<?php
require "php_header.php";
$rtn=check_session_userid($pdo_h);

//有効期限の取得
$sql="select *,DATE_ADD(keiyakudate, INTERVAL -1 DAY) as syuryoudate from Users where uid=?";
$stmt = $pdo_h->prepare($sql);
$stmt->bindValue(1, $_SESSION["user_id"], PDO::PARAM_INT);
$stmt->execute();
$row = $stmt->fetchAll(PDO::FETCH_ASSOC);

$_SESSION["SUBID"]=$row[0]["stripe_id"];

if($row[0]["keiyakudate"]>date("Y-m-d")){
    $msg="トライアル期間中の解約となりますので料金は発生致しません。";
}else if($row[0]["plan"]==12){
    $msg="次回契約更新日は『";
    if(date("Y").substr($row[0]["keiyakudate"],4)>date("Y-m-d")){
        $day=date("Y").substr($row[0]["keiyakudate"],4);
    }else{
        $day=date("Y", strtotime("1 year")).substr($row[0]["keiyakudate"],4);
    }
    $msg=$msg.$day."』となってます。<br>";
    
}else if($row[0]["plan"]==1){
    $msg="次回契約更新日は『";
    if(date("Y-m").substr($row[0]["keiyakudate"],7)>date("Y-m-d")){
        $day=date("Y-m").substr($row[0]["keiyakudate"],7);
    }else{
        $day=date("Y-m", strtotime("1 month")).substr($row[0]["keiyakudate"],7);
    }
    //[4/31]など、存在しない日付となった場合、該当月の月末をセット
    list($Y, $m, $d) = explode('-', $day);
    if (checkdate($m, $d, $Y) === true) {
        //echo $date;
    } else {
        $day = (new DateTimeImmutable)->modify('last day of '.substr($day,0,7))->format('Y-m-d');
        
    }
    $msg=$msg.$day."』となってます。<br>";
}else{
    echo "想定外エラー";
    exit();
}

$_SESSION["yuukoukigen"]=$day;
$_SESSION["mode"]=MODE_DIR;
$token=csrf_create();

?>
<head>
    <?php 
    //共通部分、bootstrap設定、フォントCND、ファビコン等
    include "head.html" 
    ?>
    <!--ページ専用CSS-->
    <link rel="stylesheet" href="css/style_account_create.css?<?php echo $time; ?>" >
    <TITLE><?php echo secho($title)." サブスクリプションの解約";?></TITLE>
</head>
<header class="header-color common_header" style="flex-wrap:wrap">
    <div class="title" style="width: 100%;"><a href="menu.php"><?php echo $title;?></a></a></div>
    <p style="font-size:1rem;">サブスクリプションの解約</p>
</header>

<body class='common_body'>
    <form methon="post" action='../../PAY/cancel.php' style='font-size:1.5rem'>
    <?php echo $msg; ?>
    <br>
    次回以降の契約更新を停止する場合は、「解約」ボタンを選択して下さい。
    <br><br>
    解約後も契約更新日の前日までWEBREZ+はご利用いただけます。
    <br><br>
    それ以降、新規の売上登録は出来ませんが、今まで入力した売上の閲覧・更新は可能となってます。
    <br><br>
    なお、登録データは削除されませんので、解約後、再契約いただくことで引き続き使用する事も可能です。
    <br><br>
    以上の内容をご理解頂いたうえで、解約をお願い致します。
    <br><br>
    <input type='submit' value='解 約（確定）' class='btn btn-primary' style='font-size:1.5rem'>
    </form>
    
</body>
    