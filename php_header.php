<?php
session_start();
require "./vendor/autoload.php";

$pass=dirname(__FILE__);
require "version.php";
require "functions.php";

//.envの取得
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

//サイトタイトルの取得
$title = $_ENV["TITLE"];
//暗号化キー
$key = $_ENV["KEY"];
//PGバージョン差分補正
updatedb($_ENV["SV"], $_ENV["USER"], $_ENV["PASS"], $_ENV["DBNAME"] ,$version);
//DB接続
$mysqli = new mysqli($_ENV["SV"], $_ENV["USER"], $_ENV["PASS"], $_ENV["DBNAME"]);
//MySQLエラーレポート用共通宣言
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

?>