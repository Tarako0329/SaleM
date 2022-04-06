<!DOCTYPE html>
<html lang="ja">
<?php
require "php_header.php";
$rtn=check_session_userid($pdo_h);
$token = csrf_create();
$logoff=false;
if($_GET["action"]=="logout"){
    setCookie("webrez_token", 'a', -1, "/", null, TRUE, TRUE); 
    session_destroy();
    session_start();
    $logoff=true;
}


?>
<head>
    <?php 
    //共通部分、bootstrap設定、フォントCND、ファビコン等
    include "head.html" 
    ?>
    <!--ページ専用CSS-->
    <link rel="stylesheet" href="css/style_menu.css?<?php echo $time; ?>" >
    <TITLE><?php echo $title;?></TITLE>
</head>

<script>
  
</script>

<header class="header-color">
    
    <?php
    if($logoff==false){
    ?>
        <div class="yagou title"><a href="menu.php"><?php echo $title;?></a></div></a></div>
        <span style="font-size:1.5rem;"><a href="menu.php?action=logout"><i class="fa-solid fa-right-from-bracket"></i></a></span>
    <?php
    }else{
    ?>
        <div class="yagou title"><a href="index.php"><?php echo $title;?></a></div></a></div>
    <?php
    }
    ?>
</header>

<body>
<?php
    if($logoff){
        echo "ログオフしました。<br>";
        echo "<a href='index.php'>再ログインする</a>";
        //echo $_COOKIE["webrez_token"];
        exit;
    }
?>
    <div class="container-fluid">

<?php
    $array = [
        '売上実績集計'=>['analysis_uriagejisseki.php?csrf_token='.$token]
        ,'ABC分析'=>['analysis_abc.php?csrf_token='.$token]
        ,'予備5'=>['xxxx.php?csrf_token='.$token]
        ,'予備4'=>['xxxx.php?csrf_token='.$token]
        ,'予備3'=>['xxxx.php?mode=select&csrf_token='.$token]
        ,'予備2'=>['xxxx.php?mode=select&csrf_token='.$token]
        ,'予備1'=>['xxxx.php?mode=1&csrf_token='.$token]
        //,'契約・解除'=>['../../PAY/index.php?system='.$title.'&mode='.MODE_DIR]
        //,'お知らせ'=>['system_update_log.php']
    ];

    $i=0;
    echo "<div class='row'>";
	foreach(array_merge($array) as $key=>$vals){
        echo "  <div class ='col-md-3 col-sm-6 col-6' style='padding:5px;' >\n";
        echo "      <a href='".$vals[0]."' class='btn--topmenu btn-view'>".$key."\n";
        echo "      </a>\n";
        echo "  </div>\n";
        $i++;
	}
    echo "</div>";
	
?> 
              
    </div>
</body>

</html>
<?php
    $pdo_h=null;
?>