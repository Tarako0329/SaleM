<?php
require "php_header.php";

if(isset($_GET["csrf_token"]) || empty($_POST)){
    if(csrf_chk_nonsession_get($_GET["csrf_token"])==false){
        $_SESSION["EMSG"]="セッションが正しくありませんでした。";
        header("HTTP/1.1 301 Moved Permanently");
        header("Location: index.php");
        exit();
    }
}
$_SESSION["MSG"]= "更新処理が実行されませんでした。";


if(csrf_chk()==false){
    $_SESSION["EMSG"]="セッションが正しくありませんでした";
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: index.php");
    exit();
}
$array = $_POST["ORDERS"];
$sqlstr = "";

$pdo_h->beginTransaction();
$E_Flg=0;
foreach($array as $row){
    //var_dump($row);
    $sqlstr="select * from ZeiMS where zeiKBN=?;";
    $stmt = $pdo_h->prepare($sqlstr);
    $stmt->bindValue(1, $row["zeikbn"], PDO::PARAM_INT);
    $stmt->execute();
    $row3 = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $sqlstr = "update ShouhinMS set tanka=?,tanka_zei=?,zeiritu=?,zeikbn=?,tani=?,bunrui1=?,bunrui2=?,bunrui3=?,hyoujiKBN1=?,hyoujiNO=?,genka_tanka=? where shouhinCD=? and uid=?";
    $stmt = $pdo_h->prepare($sqlstr);
    $stmt->bindValue(1, $row["tanka"], PDO::PARAM_INT);
    $stmt->bindValue(2, $row["shouhizei"], PDO::PARAM_INT);
    $stmt->bindValue(3, $row3[0]["zeiritu"], PDO::PARAM_INT);
    $stmt->bindValue(4, $row["zeikbn"], PDO::PARAM_STR);
    $stmt->bindValue(5, $row["tani"], PDO::PARAM_STR);
    $stmt->bindValue(6, $row["bunrui1"], PDO::PARAM_STR);
    $stmt->bindValue(7, $row["bunrui2"], PDO::PARAM_STR);
    $stmt->bindValue(8, $row["bunrui3"], PDO::PARAM_STR);
    //$stmt->bindValue(9, $row["hyoujiKBN1"], PDO::PARAM_STR);
    $stmt->bindValue(9, (empty($row["hyoujiKBN1"])?"":$row["hyoujiKBN1"]), PDO::PARAM_STR);
    //$stmt->bindValue(10,$row["hyoujiNO"], PDO::PARAM_INT); 表示順は不使用
    $stmt->bindValue(10,0, PDO::PARAM_INT);
    $stmt->bindValue(11,$row["genka"], PDO::PARAM_INT);
    $stmt->bindValue(12,$row["shouhinCD"], PDO::PARAM_INT);
    $stmt->bindValue(13,$_SESSION['user_id'], PDO::PARAM_INT);
    
    $status=$stmt->execute();
    
    if($status==true){
        
    }else{
        $E_Flg=1;
        break;
        
    }
}
    
if($E_Flg==0){
    $pdo_h->commit();
    $_SESSION["MSG"]= "更新されました。";
}else{
    //1件でも失敗したらロールバック
    $pdo_h->rollBack();
    $_SESSION["MSG"]= "更新が失敗しました。";
}
    
$csrf_create = csrf_create();

$stmt  = null;
$pdo_h = null;

header("HTTP/1.1 301 Moved Permanently");
header("Location: shouhinMSList.php?csrf_token=".$csrf_create);

exit();

?>