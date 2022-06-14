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

<header class="header-color common_header">
    <div class="yagou title"><a href="menu.php"><?php echo $title;?></a></div></a></div>
</header>

<body class='common_body'>
    <div class="container-fluid">

<?php
    $array = [
         '売上金額ランキング'=>['analysis_uriagejisseki.php?sum_tani=4&csrf_token='.$token,'商品ごとの売上金額ランキングを表示。']
        ,'売上個数ランキング'=>['analysis_uriagejisseki.php?sum_tani=5&csrf_token='.$token,'商品ごとの売上個数ランキングを表示。']
        ,'客単価実績'=>['analysis_uriagejisseki.php?sum_tani=6&csrf_token='.$token,'日ごとの平均客単価を表示します。']
        ,'平均客単価ランキング'=>['analysis_uriagejisseki.php?sum_tani=7&csrf_token='.$token,'イベントごとの平均客単価を算出し、ランキングを表示。']
        ,'来客数実績'=>['analysis_uriagejisseki.php?sum_tani=8&csrf_token='.$token,'日ごとの来客数（会計数）を表示。']
        ,'平均来客数ランキング'=>['analysis_uriagejisseki.php?sum_tani=9&csrf_token='.$token,'イベントごとの平均来客数を算出し、ランキングを表示']
        ,'時間帯別売上実績'=>['analysis_uriagejisseki.php?sum_tani=10&csrf_token='.$token,'時間帯ごとに何がどれだけ売れているかを分析・グラフ化します。<br>グラフ化して見ることで商品の売れる勢いを確認出来ます。<br>例えば、開店と同時に売れる商品は人気商品なので多めに準備するといいでしょうし、地味に売れ続ける商品も根強い人気があると分析できます。']
        ,'時間帯別来客実績'=>['analysis_uriagejisseki.php?sum_tani=11&csrf_token='.$token,'１時間ごとの来客数を集計・グラフ化します。<br>グラフがグンと伸びたところが繁忙期。なだらかなとこは凪となります。']
        ,'期間毎売上集計'=>['analysis_uriagejisseki.php?sum_tani=2&csrf_token='.$token,'日ごと、月毎、年間の売上金額を確認<br>イベント名を指定することで過去の売上傾向を確認出来ます。']
        ,'ABC分析'=>['analysis_abc.php?sum_tani=2&csrf_token='.$token,'売上の8割は2割の製品で構成されている。という統計学的な話があります。<br>売上げの7割を支える商品群をAグループ、2割を支える商品群をBグループ、残り1割をCグループに分類してます。<br>Aグループは人気商品。Cグループはあまり売上げに貢献していない商品と位置づけられます。<br>取扱商品の検討材料等に利用できます。']
        //,'バスケット分析'=>['xxxx.php?mode=1&csrf_token='.$token]
        //,'契約・解除'=>['../../PAY/index.php?system='.$title.'&mode='.MODE_DIR]
        //,'お知らせ'=>['system_update_log.php']
    ];

    /*
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
    */
    
	foreach(array_merge($array) as $key=>$vals){
        echo "<div class='row' style='margin-top:20px;'>";
        echo "  <div class ='col-md-3 col-sm-6 col-6' >\n";
        echo "      <a href='".$vals[0]."' class='btn--topmenu btn-view' style='font-size:1.5rem;width:170px;'>".$key."\n";
        echo "      </a>\n";
        echo "  </div>\n";
        echo "</div>";
        echo "<div class='row'>";
        echo "  <div class ='col-12'  style='paddin-left:10px' >\n";
        echo "<div>".$vals[1]."</div>";
        echo "  </div>\n";
        echo "</div>";
	}
    
	
?> 
              
    </div>
</body>

</html>
<?php
    $pdo_h=null;
?>