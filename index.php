<!DOCTYPE html>
<html lang="ja">
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
<head>
    <?php 
    //共通部分、bootstrap設定、フォントCND、ファビコン等
    include "head.html" 
    ?>
    <!--ページ専用CSS--><link rel="stylesheet" href="css/style_index.css" >
    <TITLE><?php echo $title;?></TITLE>
</head>

<script>
  
</script>

<form method = "post" action="menu2.php">
    
<header>
    <div class="yagou"><a href=""><?php echo $title;?></a></div>
</header>

<body>
    <div class="main">
        <div class="contentA">
            <div class="menu">
                
<?php
    $array = [
        'Eventレジ'=>['EVregi.php'],
        '個別売上'=>['Kouri.php'],
        '売上実績'=>['UriageData.php'],
        '商品登録'=>['shouhinMSedit.php'],
        '商品一覧'=>['shouhinMSList.php']
    ];
 
	foreach($array as $key=>$vals){
        echo "  <div class ='items' >\n";
        echo "      <a href='".$vals[0]."' class='btn btn--orange'>".$key."\n";
        echo "      </a>\n";
        echo "  </div>\n";
        $i++;
	}
?> 
              
            </div>
        </div>
        <!--今のところサイドコンテンツ不要
        <div class="contentB">
            ORDER LIST
            
        </div>
        -->
    </div>
</body>

<!--
<footer>
</footer>
-->
</form>
</html>
<?php
    $mysqli->close();
?>