<?php
require "php_header.php";
log_writer2("shouhinMSList_sql.php > \$_GET",$_GET,"lv3");

$rtn = csrf_checker(["shouhinMSList.php"],["G","C","S"]);
if($rtn !== true){
    redirect_to_login($rtn);
}

check_session_userid($pdo_h);
$csrf_token = csrf_create();
$flg=true;
//実績有無の確認
$sqlstr="select count(*) as cnt from UriageData where ShouhinCD=? and uid=?";
$stmt = $pdo_h->prepare($sqlstr);
$stmt->bindValue(1, $_GET["cd"], PDO::PARAM_INT);
$stmt->bindValue(2, $_SESSION['user_id'], PDO::PARAM_INT);
$stmt->execute();
$row = $stmt->fetchAll(PDO::FETCH_ASSOC);
$uri = $row[0]["cnt"];
if($uri!=0){
    $_SESSION["MSG"]= "売上実績があるため、".secho($_GET["nm"])." を削除できません。";
    $_SESSION["alert"] = "alert-warning";
    $flg=false;
}

if($flg === true){    

    $sqlstr="delete from ShouhinMS where shouhinCD=? and uid=?";
    $stmt = $pdo_h->prepare($sqlstr);
    $stmt->bindValue(1, $_GET["cd"], PDO::PARAM_INT);
    $stmt->bindValue(2, $_SESSION['user_id'], PDO::PARAM_INT);
    $status=$stmt->execute();
    if($status==true){
        $_SESSION["MSG"]= secho($_GET["nm"])." が削除されました。";
        $_SESSION["alert"] = "alert-success";
    }else{
        $_SESSION["MSG"] = "登録が失敗しました。";
        $_SESSION["alert"] = "alert-danger";
    }
}
$stmt  = null;
$pdo_h = null;
header("HTTP/1.1 301 Moved Permanently");
header("Location: shouhinMSList.php?csrf_token=".$csrf_token);

exit();
