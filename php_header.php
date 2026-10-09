<?php
define("VERSION", "ver3.25.0");
define("RELEACE_DATE", "2026-09-14");
ini_set('error_log', __DIR__ . '/.error_log');
date_default_timezone_set('Asia/Tokyo');
require "./vendor/autoload.php";
require_once "functions.php";

$time=VERSION;

//.envの取得
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

define("EXEC_MODE",$_ENV["EXEC_MODE"]);
define("APP_NAME",EXEC_MODE.":WEBREZ");

define("MAIN_DOMAIN",$_ENV["MAIN_DOMAIN"]);
if(!empty($_SERVER['SCRIPT_URI'])){
    define("ROOT_URL",substr($_SERVER['SCRIPT_URI'],0,mb_strrpos($_SERVER['SCRIPT_URI'],"/")+1));
}else{
    define("ROOT_URL","https://".MAIN_DOMAIN."/");
}

//DB接続関連
define("DNS","mysql:host=".$_ENV["SV"].";dbname=".$_ENV["DBNAME"].";charset=utf8");
define("USER_NAME", $_ENV["DBUSER"]);
define("PASSWORD", $_ENV["PASS"]);

define("DB_HOST", $_ENV["SV"]);
define("DB_NAME", $_ENV["DBNAME"]);

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
/*暗号化*/
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

//GEMINI
define("GEMINI",$_ENV["GOOGLE_API"]);
define("GEMINI_URL",$_ENV["GEMINI_URL"]);
define("GEMINI_URL_TOKEN",$_ENV["GEMINI_URL_TOKEN"]);

if(EXEC_MODE=="Test" || EXEC_MODE=="Local" || EXEC_MODE=="TrialL"){
    //テスト環境はミリ秒単位
    //$time="8";
    $time=date('Ymd-His');
    error_reporting( E_ALL );
}else{
    //本番はリリースした日を指定
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

spl_autoload_register(function ($className) {
  // 1. 名前空間のバックスラッシュ '\' を、OS標準のパス区切り文字（通常は '/'）に置換
  $path = str_replace('\\', DIRECTORY_SEPARATOR, $className);
  // 2. クラスファイルを探すフルパスを組み立て
  $file = __DIR__.DIRECTORY_SEPARATOR.$path.'.php';
  //log_writer2("Autoloading class", $className . " (Path: " . $file . ")", "lv3");
  // 3. ファイルが存在すれば読み込む
  if (file_exists($file)) {
    require_once $file;
    //log_writer2("Autoloading success", "Class: " . $className . " (Expected Path: " . $file . ")", "lv3");
  }else{
    log_writer2("Autoloading failed", "Class: " . $className . " (Expected Path: " . $file . ")", "lv3");
  }
});

class_alias('classes\Utilities\Utilities','U');
use classes\Database\Database;

$db = new Database();

//log_writer("php_header.php _SERVER values ",$_SERVER);
//log_writer2("php_header.php end > \$_SESSION values ",$_SESSION,"lv3");

?>