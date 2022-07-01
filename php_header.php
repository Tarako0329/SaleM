<?php
date_default_timezone_set('Asia/Tokyo');
require "./vendor/autoload.php";
//.envの取得
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

define("MAIN_DOMAIN",$_ENV["MAIN_DOMAIN"]);

$rtn=session_set_cookie_params(24*60*60*24*3,'/','.'.MAIN_DOMAIN,true);
if($rtn==false){
    echo "ERROR:session_set_cookie_params";
    exit();
}
session_start();

require "functions.php";


define("ROOT_URL",substr($_SERVER['SCRIPT_URI'],0,mb_strrpos($_SERVER['SCRIPT_URI'],"/")+1));
define("EXEC_MODE",$_ENV["EXEC_MODE"]);


if(EXEC_MODE=="Test"){
    //テスト環境はミリ秒単位
    $time="1";
    //$time=date('Ymd-His');
    error_reporting( E_ALL );
}else{
    //本番はリリースした日を指定
    $time="20220701";
    //$time=date('Ymd');
    error_reporting( E_ALL & ~E_NOTICE );
}


$pass=dirname(__FILE__);


//ツアーガイド実行中か否かを判断する
$_SESSION["tour"]=(empty($_SESSION["tour"])?"":$_SESSION["tour"]);
//deb_echo($_SESSION["tour"]);

//DB接続関連
define("DNS","mysql:host=".$_ENV["SV"].";dbname=".$_ENV["DBNAME"].";charset=utf8");
define("USER_NAME", $_ENV["DBUSER"]);
define("PASSWORD", $_ENV["PASS"]);

//メール送信関連
define("HOST", $_ENV["HOST"]);
define("PORT", $_ENV["PORT"]);
define("FROM", $_ENV["FROM"]);
define("PROTOCOL", $_ENV["PROTOCOL"]);
define("POP_HOST", $_ENV["POP_HOST"]);
define("POP_USER", $_ENV["POP_USER"]);
define("POP_PASS", $_ENV["POP_PASS"]);

//システム通知
define("SYSTEM_NOTICE_MAIL",$_ENV["SYSTEM_NOTICE_MAIL"]);

//契約・支払関連のキー情報
define("SKEY", $_ENV["SKey"]);
define("PKEY", $_ENV["PKey"]);
define("PLAN_M", $_ENV["PLAN_M"]);
define("PLAN_Y", $_ENV["PLAN_Y"]);
define("PAY_CONTRACT_URL", $_ENV["PAY_contract_url"]);
define("PAY_CANCEL_URL", $_ENV["PAY_cancel_url"]);

//サイトタイトルの取得
$title = $_ENV["TITLE"];


//暗号化キー
$key = $_ENV["KEY"];

// DBとの接続
$pdo_h = new PDO(DNS, USER_NAME, PASSWORD, get_pdo_options());


//端末IDを発行し、10年の有効期限でCookieにセット
if(!isset($_COOKIE['machin_id'])){
    $machin_id = getGUID();
    setCookie("machin_id", $machin_id, time()+60*60*24*365*10, "/", null, TRUE, TRUE); 
}else{
    $machin_id = $_COOKIE['machin_id'];
}
define("MACHIN_ID", $machin_id);

//deb_echo("端末ID：".MACHIN_ID);

//スキンの取得
$sql = "select value from PageDefVal where uid=? and machin=? and page=? and item=?";
$stmt = $pdo_h->prepare($sql);
$stmt->bindValue(1, (!empty($_SESSION['user_id'])?$_SESSION['user_id']:NULL), PDO::PARAM_INT);
$stmt->bindValue(2, MACHIN_ID, PDO::PARAM_STR);
$stmt->bindValue(3, "menu.php", PDO::PARAM_STR);
$stmt->bindValue(4, "COLOR", PDO::PARAM_STR);//name属性を指定
$stmt->execute();

if($stmt->rowCount()==0){
    $color_No = 0;
    //deb_echo("NULL");
}else{
    $buf = $stmt->fetch();
    $color_No = $buf["value"];
    //deb_echo("COLOR".$color_No);
} 

?>




