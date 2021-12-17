<!DOCTYPE html>
<html lang="ja">
<?php

// 設定ファイルインクルード【開発中】
$pass=dirname(__FILE__);
require "version.php";
require "../SQ/functions.php";

?>
<head>
    <META http-equiv='Content-Type' content='text/html; charset=UTF-8'>
    <TITLE>Cafe Presents</TITLE>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kosugi+Maru&display=swap" rel="stylesheet">
    <!--ファビコンCDN-->
    <link rel="apple-touch-icon" href="../favicons/GIfavi.png">
    <link rel="icon" href="../favicons/GIfavi.png">
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
    <!-- オリジナル CSS -->
    <link rel="stylesheet" href="css/style_index.css" >
</head>
<!-- Bootstrap Javascript(jQuery含む) -->
<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js" integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js" integrity="sha384-ChfqqxuZUCnJSK3+MXmPNIyE6ZbWh2IMqE241rYiqJxyMiZ6OW/JmZQ5stwEULTy" crossorigin="anonymous"></script>

<script>
  
</script>

<form method = "post" action="menu2.php">
    
<header>
    <div class="yagou"><a href="">Cafe Presents</a></div>
    <div class="event"><input type="text" class="ev" name="EV" value="<?php echo $_POST["EV"] ?>"</div>
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