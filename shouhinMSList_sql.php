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
$sqllog="";

//log_writer2("shouhinMSList_sql.php > \$_POST",$_POST,"lv3");
$array = $_POST["ORDERS"];
$sqlstr = "";
try{
    $pdo_h->beginTransaction();
    $sqllog .= rtn_sqllog("START TRANSACTION",[]);
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

        $params = [
            $row["tanka"],
            $row["shouhizei"],
            $row3[0]["zeiritu"],
            $row["zeikbn"],
            $row["tani"],
            (empty($row["hyoujiKBN1"])?"":$row["hyoujiKBN1"]),
            0,
            $row["genka"],
            $row["shouhinCD"],
            $_SESSION['user_id']
        ];

        $stmt->bindValue(1, $params[0], PDO::PARAM_INT);
        $stmt->bindValue(2, $params[1], PDO::PARAM_INT);
        $stmt->bindValue(3, $params[2], PDO::PARAM_INT);
        $stmt->bindValue(4, $params[3], PDO::PARAM_STR);
        $stmt->bindValue(5, $params[4], PDO::PARAM_STR);
        $stmt->bindValue(6, $params[5], PDO::PARAM_STR);
        $stmt->bindValue(7, $params[6], PDO::PARAM_INT);
        $stmt->bindValue(8, $params[7], PDO::PARAM_INT);
        $stmt->bindValue(9, $params[8], PDO::PARAM_INT);
        $stmt->bindValue(10,$params[9], PDO::PARAM_INT);

        $sqllog .= rtn_sqllog($sqlstr,$params);
        $status=$stmt->execute();
        $sqllog .= rtn_sqllog("--execute():正常終了",[]);
    }
    $result="success";
    $pdo_h->commit();
    $sqllog .= rtn_sqllog("commit",[]);
    sqllogger($sqllog,0);

    $_SESSION["MSG"]= "更新されました。";
    $_SESSION["alert"] = "alert-success";
    
}catch(Exception $e){
    $result="failed";
    $pdo_h->rollBack();
    $sqllog .= rtn_sqllog("rollBack",[]);
    sqllogger($sqllog,$e);
    
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