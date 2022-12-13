<?php
/*
*params:POST
*   user_id     ：ログインユーザID
*   output      ：[select:大＞中＞小形式でリストを出力][suggest:serche_wordで指定した上位分類配下のリストを出力]
*   list_type   ：[cate1:大分類のリスト][cate2:大＞中分類のリスト][cate3:大＞中＞小分類のリスト]
*   serch_word  ：cate2,cate3の場合の上位分類を指定する。NULL可。
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

// DBとの接続
$pdo_h = new PDO(DNS, USER_NAME, PASSWORD, get_pdo_options());

if($_POST["output"]==="select"){
    //selectリストの取得
    if($_POST["list_type"]=="cate2"){
        $items = "concat(bunrui1,'>')";
        $items_where = "bunrui1";
    }else if($_POST["list_type"]=="cate3"){
        $items = "concat(bunrui1,'>',bunrui2,'>')";
        $items_where = "bunrui2";
    }else{
        exit();
    }
    
    $sqlstr = "select ".$items." as bunrui from ShouhinMS where uid = ? and ".$items_where." not in ('') group by ".$items." order by ".$items;
    log_writer("ajax_get_MSCategory_list.php empty",$sqlstr);

    $stmt = $pdo_h->prepare($sqlstr);
    $stmt->bindValue(1, $_POST['user_id'], PDO::PARAM_INT);
}else if($_POST["output"]==="suggest"){
    //サジェストリストの取得
    if($_POST["list_type"]=="cate1"){
        $items = "bunrui1";
        $items_group = $items;
        $items_where = "bunrui1<>''";
    }else if($_POST["list_type"]=="cate2"){
        $items = "bunrui2";
        $items_group = "bunrui1,bunrui2";
        $items_where = "bunrui2<>'' and concat(bunrui1,'>') like '".$_POST["serch_word"]."'";
    }else if($_POST["list_type"]=="cate3"){
        $items = "bunrui3";
        $items_group = "bunrui1,bunrui2,bunrui3";
        $items_where = "bunrui3<>'' and concat(bunrui1,'>',bunrui2,'>') like '".$_POST["serch_word"]."'";
    }
    
    $sqlstr = "select ".$items." as bunrui from ShouhinMS where uid = ? and ".$items_where." group by ".$items." order by ".$items;
    log_writer("ajax_get_MSCategory_list.php !empty",$sqlstr);

    $stmt = $pdo_h->prepare($sqlstr);
    $stmt->bindValue(1, $_POST['user_id'], PDO::PARAM_INT);
}else{
    echo "不正アクセス";
    exit;
}

//log_writer("ajax_get_MSCategory_list.php",$sqlstr);

$stmt->execute();

$EVList = array();

while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
    $EVList[] = array(
        'LIST'  => $row['bunrui']
    );
}

// ヘッダーを指定することによりjsonの動作を安定させる
header('Content-type: application/json');
// htmlへ渡す配列$productListをjsonに変換する
echo json_encode($EVList, JSON_UNESCAPED_UNICODE);
?>


