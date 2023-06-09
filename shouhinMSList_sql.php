<?php
//POST ONLY
require "php_header.php";

$rtn = csrf_checker(["shouhinMSList.php"],["P","C","S"]);
if($rtn !== true){
    redirect_to_login($rtn);
}

check_session_userid($pdo_h);
$_SESSION["MSG"]= "更新対象がありませんでした。";
$_SESSION["alert"] = "alert-warning";

//log_writer2("shouhinMSList_sql.php > \$_POST",$_POST,"lv3");
$array = $_POST["ORDERS"];
$sqlstr = "";

$pdo_h->beginTransaction();
sqllogger("START TRANSACTION",[],basename(__FILE__),"ok");
$result='none';
foreach($array as $row){
    //var_dump($row);
    $sqlstr="select * from ZeiMS where zeiKBN=?;";
    $stmt = $pdo_h->prepare($sqlstr);
    $stmt->bindValue(1, $row["zeikbn"], PDO::PARAM_INT);
    $stmt->execute();
    $row3 = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $sqlstr = "update ShouhinMS set tanka=?,tanka_zei=?,zeiritu=?,zeikbn=?,tani=?,hyoujiKBN1=?,hyoujiNO=?,genka_tanka=? where shouhinCD=? and uid=?";
    $stmt = $pdo_h->prepare($sqlstr);
    $stmt->bindValue(1, $row["tanka"], PDO::PARAM_INT);
    $stmt->bindValue(2, $row["shouhizei"], PDO::PARAM_INT);
    $stmt->bindValue(3, $row3[0]["zeiritu"], PDO::PARAM_INT);
    $stmt->bindValue(4, $row["zeikbn"], PDO::PARAM_STR);
    $stmt->bindValue(5, $row["tani"], PDO::PARAM_STR);
    $stmt->bindValue(6, (empty($row["hyoujiKBN1"])?"":$row["hyoujiKBN1"]), PDO::PARAM_STR);
    $stmt->bindValue(7,0, PDO::PARAM_INT);
    $stmt->bindValue(8,$row["genka"], PDO::PARAM_INT);
    $stmt->bindValue(9,$row["shouhinCD"], PDO::PARAM_INT);
    $stmt->bindValue(10,$_SESSION['user_id'], PDO::PARAM_INT);
    
    $status=$stmt->execute();
    
    if($status==true){
        $result="success";
    }else{
        $result="failed";
        break;
        
    }
}
    
if($result==="none"){
}else if($result==="success"){
    $pdo_h->commit();
    sqllogger("commit",[],basename(__FILE__),"ok");
    $_SESSION["MSG"]= "更新されました。";
    $_SESSION["alert"] = "alert-success";
}else{
    //1件でも失敗したらロールバック
    $pdo_h->rollBack();
    sqllogger("rollback",[],basename(__FILE__),"ok");
    $_SESSION["MSG"]= "更新が失敗しました。";
    $_SESSION["alert"] = "alert-danger";
}
    
$csrf_create = csrf_create();

$stmt  = null;
$pdo_h = null;
//log_writer2("shouhinMSList_sql.php > \$_SESSION",$_SESSION,"lv3");
header("HTTP/1.1 301 Moved Permanently");
header("Location: shouhinMSList.php?csrf_token=".$csrf_create);

exit();

?>