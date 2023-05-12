<?php
/*
*params:POST
*   user_id     ：ログインユーザID
*   orderby     ：
*   list_type   ：
*   serch_word  ：
*/
date_default_timezone_set('Asia/Tokyo');
session_start();
require "functions.php";
require "./vendor/autoload.php";

$pass=dirname(__FILE__);

//.envの取得
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

define("DNS","mysql:host=".$_ENV["SV"].";dbname=".$_ENV["DBNAME"].";charset=utf8");
define("USER_NAME", $_ENV["DBUSER"]);
define("PASSWORD", $_ENV["PASS"]);

if(!empty($_POST)){
	// DBとの接続
	$pdo_h = new PDO(DNS, USER_NAME, PASSWORD, get_pdo_options());
	$sqlstr = "select *,tanka+tanka_zei as moto_kin from vw_shouhinms where uid = ? order by shouhinNM";
	//log_writer2("ajax_get_MSCategory_list.php ",$sqlstr,"lv3");

	$stmt = $pdo_h->prepare($sqlstr);
	$stmt->bindValue(1, $_POST['user_id'], PDO::PARAM_INT);
	$stmt->execute();
	$shouhihMS = $stmt->fetchAll();
}else{
	echo "不正アクセス";
	exit;
}

// ヘッダーを指定することによりjsonの動作を安定させる
header('Content-type: application/json');
// htmlへ渡す配列$productListをjsonに変換する
echo json_encode($shouhihMS, JSON_UNESCAPED_UNICODE);
?>


