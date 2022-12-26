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

	$sql = "select *,(UriageKin + zei) as ZeikomiUriage,max(UriageNO) OVER() as lastNo from UriageData where uid = ? and UriDate = ? order by insDatetime desc";
	log_writer("ajax_get_Uriage.php ",$sqlstr);
	$stmt = $pdo_h->prepare($sql);
	$stmt->bindValue(1, $_POST['user_id'], PDO::PARAM_INT);
	$stmt->bindValue(2, (string)date("Y-m-d"), PDO::PARAM_STR);
	$stmt->execute();
	$UriageList = $stmt->fetchAll();
}else{
	echo "不正アクセス";
	exit;
}

// ヘッダーを指定することによりjsonの動作を安定させる
header('Content-type: application/json');
// htmlへ渡す配列$productListをjsonに変換する
echo json_encode($UriageList, JSON_UNESCAPED_UNICODE);
?>


