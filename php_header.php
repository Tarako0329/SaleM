<?php
date_default_timezone_set('Asia/Tokyo');

session_start();
//session_regenerate_id(true);
require "./vendor/autoload.php";

$pass=dirname(__FILE__);
require "version.php";
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


//サイトタイトルの取得
$title = $_ENV["TITLE"];
//暗号化キー
$key = $_ENV["KEY"];
//PGバージョン差分補正
updatedb($_ENV["SV"], $_ENV["USER"], $_ENV["PASS"], $_ENV["DBNAME"] ,$version,$comment);

// DBとの接続
$pdo_h = new PDO(DNS, USER_NAME, PASSWORD, get_pdo_options());



?>