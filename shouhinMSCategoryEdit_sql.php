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
    if($row["chk"]!="on"){
        continue;
    }
    
    if($_POST["categry"]=="cate1"){
        $col="bunrui1";
    }elseif($_POST["categry"]=="cate2"){
        $col="bunrui2";
    }elseif($_POST["categry"]=="cate3"){
        $col="bunrui3";
    }
    
    $sqlstr = "update ShouhinMS set ".$col."=? where shouhinCD=? and uid=?";
    $stmt = $pdo_h->prepare($sqlstr);
    $stmt->bindValue(1, $_POST["upd_bunrui"], PDO::PARAM_STR);
    $stmt->bindValue(2, $row["shouhinCD"], PDO::PARAM_INT);
    $stmt->bindValue(3, $_SESSION['user_id'], PDO::PARAM_INT);
    
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
header("Location: shouhinMSCategoryEdit.php?csrf_token=".$csrf_create);
exit();

?>