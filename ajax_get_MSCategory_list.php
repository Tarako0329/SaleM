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
define("USER_NAME", $_ENV["DBUSER"]);
define("PASSWORD", $_ENV["PASS"]);

// DBとの接続
$pdo_h = new PDO(DNS, USER_NAME, PASSWORD, get_pdo_options());

$output = print_r($_POST, true);
//log_writer("ajax_get_MSCategory_list.php",$output);

if(empty($_POST["serch_word"])){
    if($_POST["list_type"]=="cate2"){
        $items = "concat(bunrui1,'>')";
        $items_where = "bunrui1";
    }else if($_POST["list_type"]=="cate3"){
        $items = "concat(bunrui1,'>',bunrui2,'>')";
        $items_where = "bunrui2";
    }
    
    $sqlstr = "select ".$items." as bunrui from ShouhinMS where uid = ? and ".$items_where." not in ('') group by ".$items." order by ".$items;
    
    $stmt = $pdo_h->prepare($sqlstr);
    $stmt->bindValue(1, $_POST['user_id'], PDO::PARAM_INT);
}else{
    if($_POST["list_type"]=="cate1"){
        $items = "bunrui1";
        $items_group = $items;
        $items_where = "bunrui1<>''";
    }else if($_POST["list_type"]=="cate2"){
        $items = "bunrui2";
        $items_group = "bunrui1,bunrui2";
        $items_where = "concat(bunrui1,'>')='".$_POST["serch_word"]."'";
    }else if($_POST["list_type"]=="cate3"){
        $items = "bunrui3";
        $items_group = "bunrui1,bunrui2,bunrui3";
        $items_where = "concat(bunrui1,'>',bunrui2,'>')='".$_POST["serch_word"]."'";
    }
    
    $sqlstr = "select ".$items." as bunrui from ShouhinMS where uid = ? and ".$items_where." group by ".$items." order by ".$items;
    
    $stmt = $pdo_h->prepare($sqlstr);
    $stmt->bindValue(1, $_POST['user_id'], PDO::PARAM_INT);
}

//log_writer("ajax_get_MSCategory_list.php",$sqlstr);

$stmt->execute();

$EVList = array();


while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
    $EVList[] = array(
        'LIST'    => $row['bunrui']
    );
}

// ヘッダーを指定することによりjsonの動作を安定させる
header('Content-type: application/json');
// htmlへ渡す配列$productListをjsonに変換する
echo json_encode($EVList, JSON_UNESCAPED_UNICODE);
?>


