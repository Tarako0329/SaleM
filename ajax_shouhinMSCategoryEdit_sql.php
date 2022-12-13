<?php
require "php_header.php";
log_writer("logincheck.php POST ",$_POST);

$msg[0] = array(
    "EMSG" => "更新処理が実行されませんでした。"
    ,"status" => "alert-danger"
);

if(isset($_GET["csrf_token"]) || empty($_POST)){
    if(csrf_chk_nonsession_get($_GET["csrf_token"])==false){
        $msg[0] = array(
            "EMSG" => "セッションが正しくありませんでした。csrf_chk_nonsession_get"
            ,"status" => "alert-danger"
        );
        header('Content-type: application/json');
        echo json_encode($msg, JSON_UNESCAPED_UNICODE);
        exit();
    }
}

if(csrf_chk()==false){
    $msg[0] = array(
        "EMSG" => "セッションが正しくありませんでした。csrf_chk()"
        ,"status" => "alert-danger"
    );
    header('Content-type: application/json');
    echo json_encode($msg, JSON_UNESCAPED_UNICODE);
    exit();
}
$array = $_POST["ORDERS"];
$sqlstr = "";

$pdo_h->beginTransaction();
$E_Flg=0;

if($_POST["categry"]=="cate1"){
    $col="bunrui1";
}elseif($_POST["categry"]=="cate2"){
    $col="bunrui2";
}elseif($_POST["categry"]=="cate3"){
    $col="bunrui3";
}


foreach($array as $row){
    if($row["chk"]!="on"){
        continue;
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
    $msg[0] = array(
        "EMSG" => "更新されました。"
        ,"status" => "alert-danger"
        ,"csrf_create" => csrf_create()
    );
}else{
    //1件でも失敗したらロールバック
    $pdo_h->rollBack();
    $msg[0] = array(
        "EMSG" => "更新が失敗しました。"
        ,"status" => "alert-danger"
        ,"csrf_create" => csrf_create()
    );
}
    
$stmt  = null;
$pdo_h = null;

header('Content-type: application/json');
echo json_encode($msg, JSON_UNESCAPED_UNICODE);
exit();
?>