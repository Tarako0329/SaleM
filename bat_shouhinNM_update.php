<?php
require "php_header.php";
//商品マスタの取得
$sql = "select * from ShouhinMS";
$stmt = $pdo_h->prepare($sql);
$stmt->execute();


$sqlstr = "";

$pdo_h->beginTransaction();
$E_Flg=0;
foreach($stmt as $row){
    $sqlstr = "update ShouhinMS set ShouhinNM=? where shouhinCD=? and uid=?";
    $stmt = $pdo_h->prepare($sqlstr);
    $stmt->bindValue(1, rot13decrypt($row["shouhinNM"]), PDO::PARAM_STR);
    $stmt->bindValue(2, $row["shouhinCD"], PDO::PARAM_INT);
    $stmt->bindValue(3, $row["uid"], PDO::PARAM_INT);

    $status=$stmt->execute();
    
    if($status==true){
    }else{
        $E_Flg=1;
        break;
    }
    $sqlstr = "update UriageData set ShouhinNM=? where shouhinCD=? and uid=?";
    $stmt = $pdo_h->prepare($sqlstr);
    $stmt->bindValue(1, rot13decrypt($row["shouhinNM"]), PDO::PARAM_STR);
    $stmt->bindValue(2, $row["shouhinCD"], PDO::PARAM_INT);
    $stmt->bindValue(3, $row["uid"], PDO::PARAM_INT);

    $status=$stmt->execute();
    
    if($status==true){
    }else{
        $E_Flg=1;
        break;
    }
}
    
if($E_Flg==0){
    $pdo_h->commit();
    echo "更新されました。";
}else{
    //1件でも失敗したらロールバック
    $pdo_h->rollBack();
    echo "更新が失敗しました。";
}
    
$stmt  = null;
$pdo_h = null;
?>