<?php
define("VERSION", "ver3.05.0-000");

date_default_timezone_set('Asia/Tokyo');
require "./vendor/autoload.php";
require_once "functions.php";
//本番はリリースした日を指定
$time="20240604-01";

//.envの取得
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

define("EXEC_MODE",$_ENV["EXEC_MODE"]);
if(EXEC_MODE==="Local" || EXEC_MODE==="TrialL"){
    //ini_set('error_log', 'C:\xampp\htdocs\SaleM\php_error.log');
    //define("HTTP","http://");
}else{
    //define("HTTP","https://");
}

define("MAIN_DOMAIN",$_ENV["MAIN_DOMAIN"]);
if(!empty($_SERVER['SCRIPT_URI'])){
    define("ROOT_URL",substr($_SERVER['SCRIPT_URI'],0,mb_strrpos($_SERVER['SCRIPT_URI'],"/")+1));
}else{
    define("ROOT_URL","http://".MAIN_DOMAIN."/");
}

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
/*暗号化
define("SKEY", $_ENV["SKey"]);
define("PKEY", $_ENV["PKey"]);
define("PLAN_M", $_ENV["PLAN_M"]);
define("PLAN_Y", $_ENV["PLAN_Y"]);
define("PAY_CONTRACT_URL", $_ENV["PAY_contract_url"]);
define("PAY_CANCEL_URL", $_ENV["PAY_cancel_url"]);
*/
define("SKEY", rot13encrypt2($_ENV["SKey"]));
define("PKEY", rot13encrypt2($_ENV["PKey"]));
define("PLAN_M", rot13encrypt2($_ENV["PLAN_M"]));
define("PLAN_Y", rot13encrypt2($_ENV["PLAN_Y"]));
define("PAY_CONTRACT_URL", rot13encrypt2($_ENV["PAY_contract_url"]));
define("PAY_CANCEL_URL", rot13encrypt2($_ENV["PAY_cancel_url"]));

//WEATHER_ID
define("WEATHER_ID", $_ENV["WEATHER_ID"]);

//サイトタイトルの取得
define("TITLE", $_ENV["TITLE"]);
$title = $_ENV["TITLE"];

//暗号化キー
define("KEY", $_ENV["KEY"]);
$key = $_ENV["KEY"];

if(EXEC_MODE=="Test" || EXEC_MODE=="Local"){
    //テスト環境はミリ秒単位
    //$time="8";
    $time=date('Ymd-His');
    error_reporting( E_ALL );
}else{
    //本番はリリースした日を指定
    $time="20240401-01";
    //$time=date('Ymd-His');
    error_reporting( E_ALL & ~E_NOTICE );
}

$pass=dirname(__FILE__);

ini_set('session.cookie_domain', '.'.MAIN_DOMAIN);
$rtn=session_set_cookie_params(24*60*60*24*3,'/','.'.MAIN_DOMAIN,true,true);
if($rtn==false){
    //echo "ERROR:session_set_cookie_params";
    log_writer2("php_header.php","ERROR:[session_set_cookie_params] が FALSE を返しました。","lv0");
    echo "システムエラー発生。システム管理者へ通知しました。";
    //共通ヘッダーでのエラーのため、リダイレクトTOPは実行できない。
    exit();
}
session_start();
//ツアーガイド実行中か否かを判断する
$_SESSION["tour"]=(empty($_SESSION["tour"])?"":$_SESSION["tour"]);

// DBとの接続
$pdo_h = new PDO(DNS, USER_NAME, PASSWORD, get_pdo_options());


//log_writer("php_header.php _SERVER values ",$_SERVER);
//log_writer("php_header.php end > \$_SESSION values ",$_SESSION);

?>