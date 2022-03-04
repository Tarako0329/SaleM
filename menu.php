<!DOCTYPE html>
<html lang="ja">
<?php
require "php_header.php";
$rtn=check_session_userid();
$token = csrf_create();
$logoff=false;
if($_GET["action"]=="logout"){
    setCookie("webrez_token", 'a', -1, "/", null, TRUE, TRUE); 
    session_destroy();
    session_start();
    $logoff=true;
}
$_SESSION["PK"]=PKEY;
$_SESSION["SK"]=SKEY;
$s_name=$_SERVER['SCRIPT_NAME'];
$dir_a=explode("/",$s_name,-1);
$_SESSION["URL"]="../SaleM/$dir_a[2]/subscription.php";

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
    <div class="yagou title"><a href=""><?php echo $title;?></a></div></a></div><span style="font-size:1.5rem;"><a href="menu.php?action=logout">LogOut</a></span>
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
        'レジ'=>['EVregi.php?csrf_token='.$token]
        //,'個別売上'=>['xxx.php?csrf_token='.$token]
        ,'売上実績'=>['UriageData.php?csrf_token='.$token]
        ,'商品登録'=>['shouhinMSedit.php?csrf_token='.$token]
        ,'商品一覧'=>['shouhinMSList.php?csrf_token='.$token]
        ,'ユーザ情報'=>['account_create.php?mode=1&csrf_token='.$token]
        ,'契約・解除'=>['../../PAY/index.php?system=webrez']
        //,'お知らせ'=>['system_update_log.php']
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
    $pdo_h=null;
?>