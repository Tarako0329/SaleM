<?php
require "php_header.php";

log_writer2("ajax_get_event_list_for_regi.php",$_POST,"lv3");

// DBとの接続
//$pdo_h = new PDO(DNS, USER_NAME, PASSWORD, get_pdo_options());

//$rtn = csrf_checker(["Evregi.php"],["C","S"]);
if(empty($_SESSION['user_id'])){
	$msg=$rtn;
	$alert_status = "alert-warning";
	$reseve_status = true;
}else{
    $sqlstr = "SELECT max(UriDate),Event FROM `UriageData` WHERE uid=:uid  and LENGTH(Event)<>0 group by Event order by max(UriDate) desc ";
    $stmt = $pdo_h->prepare($sqlstr);
    $stmt->bindValue("uid", $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetchall(PDO::FETCH_ASSOC);

}


// ヘッダーを指定することによりjsonの動作を安定させる
header('Content-type: application/json');
// htmlへ渡す配列$productListをjsonに変換する
echo json_encode($result, JSON_UNESCAPED_UNICODE);
?>