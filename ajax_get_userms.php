<?php
require "php_header.php";
$shoukai="";
$msg="";
$status="warning";

if(csrf_checker(["pre_account.php","shoukai.php","account_create.php"],["C","S"])!==true){
	$msg="セッションが正しくありませんでした";
	$status = "danger";
	$reseve_status = true;
}else{
	try{
        $rtn=check_session_userid_for_ajax($pdo_h);
        if($rtn===false){
            $reseve_status = true;
            $msg="長時間未操作のため、処理を中断しました。再度ログインし、もう一度操作して下さい。";
            $_SESSION["EMSG"]="長時間操作されていないため、自動ﾛｸﾞｱｳﾄしました。";
            $timeout=true;
        }else{
            $sql_us = "select * from Users where uid = :uid";
            $stmt = $pdo_h->prepare($sql_us);
            $stmt->bindValue(1, $_SESSION['user_id'], PDO::PARAM_INT);
            $stmt->execute();
            $Users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $sql_rz = "select * from Users_webrez where uid = :uid";
            $stmt = $pdo_h->prepare($sql_rz);
            $stmt->bindValue(1, $_SESSION['user_id'], PDO::PARAM_INT);
            $stmt->execute();
            $Users_webrez = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
            $status = "success";
        }
	}catch(Exception $e){
		log_writer2("Exception \$e",$e,"lv0");
		$status="danger";
	}
}
$token=csrf_create();

$result = array(
    "Users" => $Users
    ,"Users_webrez" => $Users_webrez
    ,"status" => $status
    ,"msg" => $msg
    ,"token" => $token
);

// ヘッダーを指定することによりjsonの動作を安定させる
header('Content-type: application/json');
// htmlへ渡す配列$productListをjsonに変換する
echo json_encode($result, JSON_UNESCAPED_UNICODE);
exit();

?>