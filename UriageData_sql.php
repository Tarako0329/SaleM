<?php
require "php_header.php";
if(csrf_chk()==false){
    $_SESSION["EMSG"]="セッションが正しくありませんでした②";
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: index.php");
    exit();
}

$rtn=check_session_userid();
$csrf_create = csrf_create();

$msg = "";

if(isset($_POST["cd"]) && isset($_POST["urino"])){
    //削除モード(実行)
    $sql="delete from UriageData where uid = :user_id and UriageNO = :UriNO and ShouhinCD = :ShouhinCD";
    $stmt = $pdo_h->prepare( $sql );
    $stmt->bindValue("UriNO", $_POST["urino"], PDO::PARAM_INT);
    $stmt->bindValue("ShouhinCD", $_POST["cd"], PDO::PARAM_INT);
    $stmt->bindValue("user_id", $_SESSION["user_id"], PDO::PARAM_INT);
    $status = $stmt->execute();
    if($status){
        $_SESSION["MSG"] = "売上を削除しました<br>";
    }else{
        $_SESSION["MSG"] = "売り上げ削除失敗<br><br>";
    }
}

header("HTTP/1.1 301 Moved Permanently");
header("Location: UriageData.php?sts=redirect&csrf_token=".$csrf_create);
exit();

?>