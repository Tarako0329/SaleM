<?php
require "php_header.php";
log_writer2("shouhinMSList_sql.php > \$_GET",$_GET,"lv3");
if(isset($_GET["csrf_token"]) || empty($_POST)){
    if(csrf_chk_nonsession_get($_GET["csrf_token"])==false){
        $_SESSION["EMSG"]="セッションが正しくありませんでした。";
        header("HTTP/1.1 301 Moved Permanently");
        header("Location: index.php");
        exit();
    }
}

if(!($_SESSION['user_id']<>"")){
    //セッションのIDがクリアされた場合の再取得処理。
    if(check_auto_login($_COOKIE['webrez_token'],$pdo_h)==false){
        $_SESSION["EMSG"]="ログイン有効期限が切れてます";
        header("HTTP/1.1 301 Moved Permanently");
        header("Location: index.php");
    }
}

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
    $stmt->bindValue(1, $_POST["cd"], PDO::PARAM_INT);
    $stmt->bindValue(2, $_SESSION['user_id'], PDO::PARAM_INT);
    //$stmt->execute();

    $status=$stmt->execute();
    if($status==true){
        $_SESSION["MSG"]= secho($_GET["nm"])." が削除されました。";
        $_SESSION["alert"] = "alert-success";
    }else{
        $_SESSION["MSG"] = "登録が失敗しました。";
        $_SESSION["alert"] = "alert-danger";
    }

    //echo $_POST["hyoujiKBN1"];
}
$stmt  = null;
$pdo_h = null;
header("HTTP/1.1 301 Moved Permanently");
header("Location: shouhinMSList.php?csrf_token=".$csrf_token);

exit();
