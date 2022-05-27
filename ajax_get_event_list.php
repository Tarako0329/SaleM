<?php
date_default_timezone_set('Asia/Tokyo');
session_start();
require "functions.php";
require "./vendor/autoload.php";

$pass=dirname(__FILE__);

//.envの取得
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

define("DNS","mysql:host=".$_ENV["SV"].";dbname=".$_ENV["DBNAME"].";charset=utf8");
define("USER_NAME", $_ENV["USER"]);
define("PASSWORD", $_ENV["PASS"]);
// DBとの接続
$pdo_h = new PDO(DNS, USER_NAME, PASSWORD, get_pdo_options());

if($_POST["list_type"]=="Event"){
    $sqlstr = "select '' as CODE, LIST from (select Event as LIST,UriDate from UriageData where uid =? and Event <> '' group by UriDate,Event ";
    $sqlstr = $sqlstr."union select TokuisakiNM as LIST,UriDate from UriageData where uid =? and TokuisakiNM<>'' group by UriDate,TokuisakiNM) as tmp ";
    $sqlstr = $sqlstr."where tmp.UriDate >= ? and tmp.UriDate <= ? group by LIST order by LIST";
    $stmt = $pdo_h->prepare($sqlstr);
    $stmt->bindValue(1, $_POST['user_id'], PDO::PARAM_INT);
    $stmt->bindValue(2, $_POST['user_id'], PDO::PARAM_INT);
    $stmt->bindValue(3, $_POST['date_from'], PDO::PARAM_STR);
    $stmt->bindValue(4, $_POST['date_to'], PDO::PARAM_STR);

    
}else if($_POST["list_type"]=="Shouhin"){
    $sqlstr = "select shouhinCD as CODE,shouhinNM as LIST from UriageData ";
    $sqlstr = $sqlstr."where uid =? and UriDate >= ? and UriDate <= ? and shouhinCD <= 9900 group by shouhinCD,shouhinNM order by shouhinCD";
    $stmt = $pdo_h->prepare($sqlstr);
    $stmt->bindValue(1, $_POST['user_id'], PDO::PARAM_INT);
    $stmt->bindValue(2, $_POST['date_from'], PDO::PARAM_STR);
    $stmt->bindValue(3, $_POST['date_to'], PDO::PARAM_STR);
}


$stmt->execute();

$EVList = array();

while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
    $EVList[] = array(
        'CODE'    => $row['CODE'],
        'LIST'    => $row['LIST']
    );
}

// ヘッダーを指定することによりjsonの動作を安定させる
header('Content-type: application/json');
// htmlへ渡す配列$productListをjsonに変換する
echo json_encode($EVList, JSON_UNESCAPED_UNICODE);
?>