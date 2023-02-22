<?php
//POST ONLY
require "php_header.php";

if(csrf_chk()==false){
    redirect_to_login("セッションが正しくありませんでした");
}
$_SESSION["MSG"]= "更新対象がありませんでした。";
$_SESSION["alert"] = "alert-warning";

//log_writer2("shouhinMSList_sql.php > \$_POST",$_POST,"lv3");
$array = $_POST["ORDERS"];
$sqlstr = "";

$pdo_h->beginTransaction();
$result='none';
foreach($array as $row){
    //var_dump($row);
    $sqlstr="select * from ZeiMS where zeiKBN=?;";
    $stmt = $pdo_h->prepare($sqlstr);
    $stmt->bindValue(1, $row["zeikbn"], PDO::PARAM_INT);
    $stmt->execute();
    $row3 = $stmt->fetchAll(PDO::FETCH_ASSOC);

    //$sqlstr = "update ShouhinMS set tanka=?,tanka_zei=?,zeiritu=?,zeikbn=?,tani=?,bunrui1=?,bunrui2=?,bunrui3=?,hyoujiKBN1=?,hyoujiNO=?,genka_tanka=? where shouhinCD=? and uid=?";
    $sqlstr = "update ShouhinMS set tanka=?,tanka_zei=?,zeiritu=?,zeikbn=?,tani=?,hyoujiKBN1=?,hyoujiNO=?,genka_tanka=? where shouhinCD=? and uid=?";
    $stmt = $pdo_h->prepare($sqlstr);
    $stmt->bindValue(1, $row["tanka"], PDO::PARAM_INT);
    $stmt->bindValue(2, $row["shouhizei"], PDO::PARAM_INT);
    $stmt->bindValue(3, $row3[0]["zeiritu"], PDO::PARAM_INT);
    $stmt->bindValue(4, $row["zeikbn"], PDO::PARAM_STR);
    $stmt->bindValue(5, $row["tani"], PDO::PARAM_STR);
    //$stmt->bindValue(6, $row["bunrui1"], PDO::PARAM_STR);
    //$stmt->bindValue(7, $row["bunrui2"], PDO::PARAM_STR);
    //$stmt->bindValue(8, $row["bunrui3"], PDO::PARAM_STR);
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
    $_SESSION["MSG"]= "更新されました。";
    $_SESSION["alert"] = "alert-success";
}else{
    //1件でも失敗したらロールバック
    $pdo_h->rollBack();
    $_SESSION["MSG"]= "更新が失敗しました。";
    $_SESSION["alert"] = "alert-danger";
}
    
$csrf_create = csrf_create();

$stmt  = null;
$pdo_h = null;
log_writer2("shouhinMSList_sql.php > \$_SESSION",$_SESSION,"lv3");
header("HTTP/1.1 301 Moved Permanently");
header("Location: shouhinMSList.php?csrf_token=".$csrf_create);

exit();

?>