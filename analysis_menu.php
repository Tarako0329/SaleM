<?php
/*関数メモ
check_session_userid：セッションのユーザIDが消えた場合、自動ログインがオフならログイン画面へ、オンなら自動ログインテーブルからユーザIDを取得

【想定して無いページからの遷移チェック】
csrf_create()：SESSIONとCOOKIEに同一トークンをセットし、同内容を返す。(POSTorGETで遷移先に渡す)
　　　　　　　 headerでリダイレクトされた場合、COOKIEにセットされないので注意。

遷移先のチェック
*/
require "php_header.php";

$rtn = csrf_checker(["menu.php","analysis_uriagejisseki.php","analysis_abc.php"],["G","C","S"]);
if($rtn !== true){
    redirect_to_login($rtn);
}

$rtn=check_session_userid($pdo_h);
$token = csrf_create();

?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <?php 
    //共通部分、bootstrap設定、フォントCND、ファビコン等
    include "head_bs5.php" 
    ?>
    <!--ページ専用CSS-->
    <link rel="stylesheet" href="css/style_menu.css?<?php echo $time; ?>" >
    <TITLE><?php echo $title;?></TITLE>
</head>

<body class=''>
    <header class="header-color common_header" style='display:block'>
        <div class="yagou title">
            <a href="menu.php"><?php echo $title;?></a>
        </div>
        <div style='color:var(--user-disp-color);font-weight:400;'>データ分析メニュー</div>
    </header>
    <main class="common_body">
        <div class="container">
<?php
    $array = [
         '売上金額ランキング'=>['analysis_uriagejisseki.php?sum_tani=4&csrf_token='.$token,'商品ごとの売上金額ランキングを表示。']
        ,'売上個数ランキング'=>['analysis_uriagejisseki.php?sum_tani=5&csrf_token='.$token,'商品ごとの売上個数ランキングを表示。']
        ,'ジャンル別売上集計'=>['analysis_uriagejisseki.php?sum_tani=12&csrf_token='.$token,'ジャンルごとの売上を集計し、円グラフで表示。']
        ,'ｲﾍﾞﾝﾄ別客単価ﾗﾝｷﾝｸﾞ'=>['analysis_uriagejisseki.php?sum_tani=7&csrf_token='.$token,'イベントごとの客単価を算出し、ランキングを表示。']
        ,'ｲﾍﾞﾝﾄ別平均来客数ﾗﾝｷﾝｸﾞ'=>['analysis_uriagejisseki.php?sum_tani=9&csrf_token='.$token,'イベントごとの平均来客数を算出し、ランキングを表示。']
        ,'ｲﾍﾞﾝﾄ別平均総売上ﾗﾝｷﾝｸﾞ'=>['analysis_uriagejisseki.php?sum_tani=Ev_Avr_uri_rank&csrf_token='.$token,'イベントごとの平均総売上を算出し、ランキングを表示。']
        ,'エリア(市区)別客単価RANK'=>['analysis_uriagejisseki.php?sum_tani=Area_tanka_1&csrf_token='.$token,'市区町村エリアごとの客単価を算出し、ランキングを表示。']
        ,'エリア(市区町)別客単価RANK'=>['analysis_uriagejisseki.php?sum_tani=Area_tanka_2&csrf_token='.$token,'市区町村〇丁目エリアごとの客単価を算出し、ランキングを表示。']
        ,'客単価実績(ｲﾍﾞﾝﾄ開催ごと)'=>['analysis_uriagejisseki.php?sum_tani=6&csrf_token='.$token,'日ごとの客単価を表示します。']
        ,'来客数実績(ｲﾍﾞﾝﾄ開催ごと)'=>['analysis_uriagejisseki.php?sum_tani=8&csrf_token='.$token,'日ごとの来客数（会計数）を表示。']
        ,'時間帯別売上実績'=>['analysis_uriagejisseki.php?sum_tani=10&csrf_token='.$token,'時間帯ごとに何がどれだけ売れているかを分析・グラフ化します。<br>グラフ化して見ることで商品の売れる勢いを確認出来ます。<br>例えば、開店と同時に売れる商品は人気商品なので多めに準備するといいでしょうし、地味に売れ続ける商品も根強い人気があると分析できます。']
        ,'時間帯別来客実績'=>['analysis_uriagejisseki.php?sum_tani=11&csrf_token='.$token,'１時間ごとの来客数を集計・グラフ化します。<br>グラフがグンと伸びたところが繁忙期。なだらかなとこは凪となります。']
        ,'期間毎売上集計'=>['analysis_uriagejisseki.php?sum_tani=2&csrf_token='.$token,'日ごと、月毎、年間の売上金額を確認<br>イベント名を指定することで過去の売上傾向を確認出来ます。']
        ,'売切分析'=>['analysis_uriagejisseki.php?sum_tani=urikire&csrf_token='.$token,'過去イベントで売切れが発生した商品と売切れた時間をピックアップ<br>出品数を調整し、より売上を上げましょう！']
        ,'ABC分析'=>['analysis_abc.php?sum_tani=2&csrf_token='.$token,'売上げの7割を支える商品群をAグループ、2割を支える商品群をBグループ、残り1割をCグループに分類してます。<br>Aグループは人気商品。Cグループはあまり売上げに貢献していない商品と位置づけられます。<br>取扱商品の検討材料等に利用できます。']
        //,'バスケット分析'=>['xxxx.php?mode=1&csrf_token='.$token]
    ];
//<i class="fa-regular fa-circle-question fa-lg logoff-color"></i>
    echo "<div class='row' >";
	foreach(array_merge($array) as $key=>$vals){
        
        echo "  <div class ='col-md-4 col-sm-6 col-12 mb-3' >\n";
        echo "      <a href='".$vals[0]."' class='btn--topmenu btn-view' style='font-size:1.5rem;width:80%;height:50px;padding:12px 10px;'>".$key."\n";
        echo "      </a>\n";
        echo "      <i class='fa-regular fa-circle-question fa-2x' data-bs-placement='top' data-bs-trigger='click' data-bs-custom-class='custom-tooltip' data-bs-toggle='tooltip' data-bs-html='true' title='".$vals[1]."'></i>";
        echo "  </div>\n";
        //echo "</div>";
        //echo "<div class='row'>";
        //echo "  <div class ='col-12'  style='paddin-left:10px' >\n";
        //echo "<div>".$vals[1]."</div>";
        //echo "  </div>\n";
        
	}
    echo "</div>";
    
	
?> 
        </div>
    </main>
    <script>
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]')
        const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl))
    </script>
</body>

</html>
<?php
    $pdo_h=null;
?>