<!DOCTYPE html>
<html lang="ja">
<?php
require "php_header.php";

?>
<head>
    <?php 
    //共通部分、bootstrap設定、フォントCND、ファビコン等
    include "head.html" 
    ?>
    <!--ページ専用CSS-->
    <link rel="stylesheet" href="css/style_menu.css" >
    <TITLE><?php echo $title;?></TITLE>
</head>

<script>
  
</script>

<form method = "post" action="menu2.php">
    
<header>
    <div class="yagou title"><a href=""><?php echo $title;?></a></div>
</header>

<body>
    <div class="container-fluid">

<?php
    $array = [
        'Eventレジ'=>['EVregi.php'],
        '個別売上'=>['Kouri.php'],
        '売上実績'=>['UriageData.php'],
        '商品登録'=>['shouhinMSedit.php'],
        '商品一覧'=>['shouhinMSList.php'],
        'ユーザ情報修正'=>['account_create.php?mode=1'],
        '改定履歴'=>['system_update_log.php']
    ];
    $i=0;
    echo "<div class='row'>";
	foreach($array as $key=>$vals){
        echo "  <div class ='col-md-3 col-sm-6 col-6' style='padding:5px;' >\n";
        echo "      <a href='".$vals[0]."' class='btn btn--orange'>".$key."\n";
        echo "      </a>\n";
        echo "  </div>\n";
        $i++;
	}
    echo "</div>";
	
?> 
              
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