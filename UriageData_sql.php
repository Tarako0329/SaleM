<?php
require "php_header.php";
if(csrf_chk()==false){
    $_SESSION["EMSG"]="セッションが正しくありませんでした②";
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: index.php");
    exit();
}

$rtn=check_session_userid($pdo_h);
$csrf_create = csrf_create();

$msg = "";

if($_POST["mode"] == "del"){
    //削除モード(実行)
    $sql="delete from UriageData where uid = :user_id and UriageNO = :UriNO and ShouhinCD = :ShouhinCD";
    $stmt = $pdo_h->prepare( $sql );
    $stmt->bindValue("UriNO", $_SESSION["urino"], PDO::PARAM_INT);
    $stmt->bindValue("ShouhinCD", $_SESSION["cd"], PDO::PARAM_INT);
    $stmt->bindValue("user_id", $_SESSION["user_id"], PDO::PARAM_INT);
    $status = $stmt->execute();
    $count = $stmt->rowCount();
    if($status && $count<>0){
        $_SESSION["MSG"] = "売上を削除しました。<br>";
    }else{
        $_SESSION["MSG"] = "売上削除失敗。<br><br>";
    }
}elseif(($_POST["mode"]=="UpdateTk" || $_POST["mode"]=="UpdateEv") && $_SESSION["wheresql"]<>""){
    //イベント名or顧客名の更新モード(実行)
    if($_POST["mode"]=="UpdateEv"){
        $sql="update UriageData set Event = :UpNM ".$_SESSION["wheresql"];
        $_SESSION["MSG"] = "イベント名を更新しました。<br>";
    }elseif($_POST["mode"]=="UpdateTk"){
        $sql="update UriageData set TokuisakiNM = :UpNM ".$_SESSION["wheresql"];
        $_SESSION["MSG"] = "顧客名を更新しました。<br>";
    }
    $stmt = $pdo_h->prepare( $sql );
    
    $stmt->bindValue("UpNM", $_SESSION["UpNM"], PDO::PARAM_STR);
    $stmt->bindValue("user_id", $_SESSION["user_id"], PDO::PARAM_INT);
    $status = $stmt->execute();
    $count = $stmt->rowCount();
    if($status && $count<>0){
        
    }else{
        $_SESSION["MSG"] = "更新失敗。<br><br>";
    }
    
}elseif($_POST["mode"]=="UpdateKin"){
    $sql="update UriageData set tanka = :tanka,UriageKin = :tanka2 * su,zei=:zei * su, zeiKBN=:zeikbn,updDatetime=now() ".$_SESSION["wheresql"];
    deb_echo($sql);
    $stmt = $pdo_h->prepare( $sql );
    
    $stmt->bindValue("tanka", $_SESSION["zeinukiTanka"], PDO::PARAM_INT);
    $stmt->bindValue("tanka2", $_SESSION["zeinukiTanka"], PDO::PARAM_INT);
    $stmt->bindValue("zei", $_SESSION["shouhizei"], PDO::PARAM_INT);
    $stmt->bindValue("zeikbn", $_SESSION["zeikbn"], PDO::PARAM_INT);
    $stmt->bindValue("user_id", $_SESSION["user_id"], PDO::PARAM_INT);
    $status = $stmt->execute();
    $count = $stmt->rowCount();
    if($status && $count<>0){
        $_SESSION["MSG"] = "金額を更新しました。<br>";
    }else{
        $_SESSION["MSG"] = "更新失敗。<br><br>";
    }
    
}

header("HTTP/1.1 301 Moved Permanently");
header("Location: UriageData.php?mode=redirect&csrf_token=".$csrf_create);
exit();

?>