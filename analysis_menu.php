<!DOCTYPE html>
<html lang="ja">
<?php
/*関数メモ
check_session_userid：セッションのユーザIDが消えた場合、自動ログインがオフならログイン画面へ、オンなら自動ログインテーブルからユーザIDを取得

【想定して無いページからの遷移チェック】
csrf_create()：SESSIONとCOOKIEに同一トークンをセットし、同内容を返す。(POSTorGETで遷移先に渡す)
　　　　　　　 headerでリダイレクトされた場合、COOKIEにセットされないので注意。

遷移先のチェック
csrf_chk()                              ：COOKIE・SESSION・POSTのトークンチェック。
csrf_chk_nonsession()                   ：COOKIE・POSTのトークンチェック。
csrf_chk_nonsession_get($_GET[token])   ：COOKIE・GETのトークンチェック。
csrf_chk_redirect($_GET[token])         ：SESSSION・GETのトークンチェック
*/
require "php_header.php";
if(csrf_chk_nonsession_get($_GET["csrf_token"])==false){
    $_SESSION["EMSG"]="セッションが正しくありませんでした。①";
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: index.php");
    exit();
}



$rtn=check_session_userid($pdo_h);
$token = csrf_create();

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
    <div class="yagou title"><a href="menu.php"><?php echo $title;?></a></div></a></div>
</header>

<body>
    <div class="container-fluid">

<?php
    $array = [
         '実績集計'=>['analysis_uriagejisseki.php?csrf_token='.$token]
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