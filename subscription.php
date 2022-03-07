<!DOCTYPE html>
<html lang="ja">
<?php
require "php_header.php";
$rtn=check_session_userid();

if($_GET["sid"]<>""){
}else{
    echo "不正アクセス！！";
    exit();
}

$sqlstr="update Users set yuukoukigen=?,stripe_id=?,keiyakudate=?,plan=? where uid=?";
$stmt = $pdo_h->prepare($sqlstr);
$stmt->bindValue(1, NULL, PDO::PARAM_STR);
$stmt->bindValue(2, $_GET["sid"], PDO::PARAM_STR);
$stmt->bindValue(3, date("Y-m-d"), PDO::PARAM_STR);
$stmt->bindValue(4, $_GET["M"], PDO::PARAM_INT);
$stmt->bindValue(5, $_SESSION["user_id"], PDO::PARAM_INT);
$flg=$stmt->execute();

if($flg){
    echo "登録が成功しました。";
    $mode=5;
}else{
    echo "登録が失敗しました。";
    $mode=6;
}

$token=csrf_create();

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
    <div class="title" style="width: 100%;"><a href="menu.php"><?php echo $title;?></a></a></div>
    <p style="font-size:1rem;">  ユーザー登録</p>
</header>

<body>