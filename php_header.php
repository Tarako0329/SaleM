<?php
date_default_timezone_set('Asia/Tokyo');
//ディレクトリ・テストモード本番モードのURL切り分け用
$s_name=$_SERVER['SCRIPT_NAME'];
$dir_a=explode("/",$s_name,-1);
define("MODE_DIR",$dir_a[2]);

//CSSスーパーリロード頻度
if(MODE_DIR=="TEST"){
    $time=date('Ymd-His');
    error_reporting( E_ALL );
}else{
    $time=date('Ymd');
    error_reporting( E_ALL & ~E_NOTICE );
}


session_start();
//session_regenerate_id(true);
require "./vendor/autoload.php";

$pass=dirname(__FILE__);
//require "version.php";
require "functions.php";


//.envの取得
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

define("DNS","mysql:host=".$_ENV["SV"].";dbname=".$_ENV["DBNAME"].";charset=utf8");
define("USER_NAME", $_ENV["USER"]);
define("PASSWORD", $_ENV["PASS"]);

define("HOST", $_ENV["HOST"]);
define("PORT", $_ENV["PORT"]);
define("FROM", $_ENV["FROM"]);
define("PROTOCOL", $_ENV["PROTOCOL"]);
define("POP_HOST", $_ENV["POP_HOST"]);
define("POP_USER", $_ENV["POP_USER"]);
define("POP_PASS", $_ENV["POP_PASS"]);

define("SKEY", $_ENV["SKey"]);
define("PKEY", $_ENV["PKey"]);
define("PLAN_M", $_ENV["PLAN_M"]);
define("PLAN_Y", $_ENV["PLAN_Y"]);

//サイトタイトルの取得
$title = $_ENV["TITLE"];


//暗号化キー
$key = $_ENV["KEY"];

// DBとの接続
$pdo_h = new PDO(DNS, USER_NAME, PASSWORD, get_pdo_options());


//端末IDを発行し、１年の有効期限でCookieにセット
if(!isset($_COOKIE['machin_id'])){
    $machin_id = getGUID();
    setCookie("machin_id", $machin_id, time()+60*60*24*365, "/", null, TRUE, TRUE); 
}else{
    $machin_id = $_COOKIE['machin_id'];
}
define("MACHIN_ID", $machin_id);

//deb_echo("端末ID：".MACHIN_ID);

$rtn=check_session_userid($pdo_h);

$sql = "select value from PageDefVal where uid=? and machin=? and page=? and item=?";
$stmt = $pdo_h->prepare($sql);
$stmt->bindValue(1, $_SESSION['user_id'], PDO::PARAM_INT);
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




