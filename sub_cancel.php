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
$flg="";

if($row[0]["keiyakudate"]>date("Y-m-d")){
    $day=date("Y-m-d", strtotime($row[0]["keiyakudate"]." -1 day"));
    $msg="トライアル期間中の解約となりますので<span style='color:red'>料金は発生致しません</span>。<br>引き続き、トライアル期間として「".$day."」までは利用できます。<br>";
    $flg="trial";
}else if($row[0]["plan"]==12){
    $msg="次回契約更新日は『<span style='color:red'>";
    if(date("Y").substr($row[0]["keiyakudate"],4)>date("Y-m-d")){
        $day=date("Y").substr($row[0]["keiyakudate"],4);
    }else{
        $day=date("Y", strtotime("1 year")).substr($row[0]["keiyakudate"],4);
    }
    $msg=$msg.$day."</span>』となってます。<br>";
    
}else if($row[0]["plan"]==1){
    $msg="次回契約更新日は『<span style='color:red'>";
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
    $msg=$msg.$day."</span>』となってます。<br>";
}else{
    echo "想定外エラー";
    exit();
}

$_SESSION["yuukoukigen"]=$day;
$_SESSION["redirect_url"]=ROOT_URL."sub_cancel2.php"; //解約処理終了後に飛ぶURL
$token=csrf_create();

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <?php 
    //共通部分、bootstrap設定、フォントCND、ファビコン等
    include "head_bs5.html" 
    ?>
    <!--ページ専用CSS-->
    <link rel="stylesheet" href="css/style_account_create.css?<?php echo $time; ?>" >
    <TITLE><?php echo secho($title)." サブスクリプションの解約";?></TITLE>
</head>
<header class="header-color common_header" style="flex-wrap:wrap">
    <div class="title" style="width: 100%;"><a href="menu.php"><?php echo $title;?></a></a></div>
    <p class='user_disp'style="font-size:1rem;">サブスクリプションの解約</p>
</header>

<body class='common_body' style='font-size:1.5rem;padding-left:10px;'>
    <?php echo $msg; ?>
    <?php
    if($flg!="trial"){
    ?>
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
    <?php
    }
    ?>
    <br><br>
    <a href='<?php echo PAY_CANCEL_URL; ?>?token=<?php echo $token; ?>' class='btn btn-primary' style='color:#fff;' >解 約（確定）</a>
    

</body>
    
</html>