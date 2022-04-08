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
/*
if(isset($_GET["csrf_token"]) || empty($_POST)){
    if(csrf_chk_nonsession_get($_GET["csrf_token"])==false){
        $_SESSION["EMSG"]="セッションが正しくありませんでした。①";
        header("HTTP/1.1 301 Moved Permanently");
        header("Location: index.php");
        exit();
    }
}
*/
$rtn=check_session_userid($pdo_h);
$csrf_create = csrf_create();

deb_echo("UID：".$_SESSION["user_id"]);

if(!empty($_POST)){
    $ymfrom = $_POST["ymfrom"];
    $ymto = $_POST["ymto"];
    $list = $_POST["list"];
}else{
    $ymfrom = (int)((string)date('Y')."01");
    $ymto = (string)date('Y')."12";
    $list = "%";
}
//deb_echo($list);
$cols=0;
if($_POST["sum_tani"]==1 || empty($_POST)){//全商品（金額）
    $sqlstr = "select tmp.* ,sum(税抜売上) over() as 総売上 from (select ShouhinNM as ShouhinNM ,sum(UriageKin) as 税抜売上 from UriageData ";
    $gp_sqlstr = "group by ShouhinNM) tmp order by 税抜売上 desc";
    $aryColumn = ["商品名","税抜売上"];
    $cols=2;
}elseif($_POST["sum_tani"]==2 || empty($_POST)){//月毎
    $sqlstr = "select tmp.* ,sum(税抜売上) over(PARTITION BY Event) as 総売上 from (select Event,ShouhinNM as ShouhinNM ,sum(UriageKin) as 税抜売上 from UriageData ";
    $gp_sqlstr = "group by Event,ShouhinNM) tmp order by Event,税抜売上 desc";
    $aryColumn = ["商品名","税抜売上"];
    $cols=3;
}/*elseif($_POST["sum_tani"]==3){//年ごと
    $sqlstr = "select DATE_FORMAT(UriDate, '%Y') as 計上年月 ,sum(UriageKin) as 税抜売上,sum(zei) as 税,sum(UriageKin+zei) as 税込売上 from UriageData ";
    $gp_sqlstr = "group by DATE_FORMAT(UriDate, '%Y') order by DATE_FORMAT(UriDate, '%Y')";
    $aryColumn = ["計上年度","税抜売上","消費税","税込売上"];
}elseif($_POST["sum_tani"]==4){//製品名ごと売上金額ランキング
}elseif($_POST["sum_tani"]==5){//製品名ごと売上数量ランキング
    $sqlstr = "select ShouhinNM as ShouhinNM ,sum(Su) as 売上数 from UriageData ";
    $gp_sqlstr = "group by ShouhinNM order by sum(Su) desc";
    $aryColumn = ["商品名","売上数"];
}elseif($_POST["sum_tani"]==7){//イベント・店舗別客単価ランキング
    $sqlstr = "select A,ROUND(avg(客単価)) as 平均客単価 from ";
    $sqlstr = $sqlstr." (select UriDate as 計上日 ,concat(Event,TokuisakiNM) as A ,UriageNO ,sum(UriageKin) as 客単価 from UriageData ";
    $gp_sqlstr = "group by UriDate,concat(Event,TokuisakiNM),UriageNO ) as UriSum group by A order by avg(客単価) desc";
    $aryColumn = ["Event/店舗","客単価"];
}elseif($_POST["sum_tani"]==6){//客単価推移
    //客単価一覧
    $sqlstr = "select 計上日,ROUND(avg(税抜売上)) as 客単価,Event from ";
    $sqlstr = $sqlstr." (select UriDate as 計上日 ,Event ,UriageNO ,sum(UriageKin) as 税抜売上 from UriageData ";
    $gp_sqlstr = "group by UriDate,UriageNO ) as UriSum group by 計上日 order by 計上日";
    $aryColumn = ["計上日","客単価","Event/店舗"];
}   
*/
$sqlstr = $sqlstr." where ShouhinCD<>9999 and DATE_FORMAT(UriDate, '%Y%m') between :ymfrom and :ymto AND uid = :user_id ";
$sqlstr = $sqlstr." AND (Event like :event OR TokuisakiNM like :tokui )";
$sqlstr = $sqlstr." ".$gp_sqlstr;

//deb_echo($sqlstr);

$stmt = $pdo_h->prepare( $sqlstr );
$stmt->bindValue("ymfrom", $ymfrom, PDO::PARAM_INT);
$stmt->bindValue("ymto", $ymto, PDO::PARAM_INT);
$stmt->bindValue("user_id", $_SESSION["user_id"], PDO::PARAM_INT);
$stmt->bindValue("event", $list, PDO::PARAM_STR);
$stmt->bindValue("tokui", $list, PDO::PARAM_STR);
$rtn=$stmt->execute();
if($rtn==false){
    deb_echo("失敗<br>");
}
$result=$stmt->fetchAll();

/*

//売上実績商品リスト（修正モーダル用）
$SHsql = "select ShouhinCD,ShouhinNM from UriageData where uid =? group by ShouhinCD,ShouhinNM order by ShouhinCD,ShouhinNM";
$stmt = $pdo_h->prepare($SHsql);
$stmt->bindValue(1, $_SESSION['user_id'], PDO::PARAM_INT);
$stmt->execute();
$SHresult = $stmt->fetchAll();
*/
//検索年月リスト
$SLVsql = "select * from SerchValMS where type='yyyymm' order by Value";
$stmt = $pdo_h->prepare($SLVsql);
$stmt->execute();
$SLVresult = $stmt->fetchAll();

$EVsql = "select Event as LIST from UriageData where uid =? and Event <> '' group by Event ";
$EVsql = $EVsql."union select TokuisakiNM as LIST from UriageData where uid =? and TokuisakiNM<>'' group by TokuisakiNM ";
$stmt = $pdo_h->prepare($EVsql);
$stmt->bindValue(1, $_SESSION['user_id'], PDO::PARAM_INT);
$stmt->bindValue(2, $_SESSION['user_id'], PDO::PARAM_INT);
$stmt->execute();
$EVresult = $stmt->fetchAll();

?>
<head>
    <?php 
    //共通部分、bootstrap設定、フォントCND、ファビコン等
    include "head.html" 
    ?>
    <!--ページ専用CSS-->
    <link rel="stylesheet" href="css/style_UriageData.css?<?php echo $time; ?>" >
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.1/chart.min.js" integrity="sha512-QSkVNOCYLtj73J4hbmVoOV6KVZuMluZlioC+trLpewV8qMjsWqlIQvkn1KGX2StWvPMdWGBqim1xlC8krl1EKQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>    
    
    <script>
    window.onload = function() {
        //アラート用
        function alert(msg) {
          return $('<div class="alert" role="alert"></div>')
            .text(msg);
        }
        (function($){
          const e = alert('<?php echo $_SESSION["MSG"]; ?>').addClass('alert-success');
          // アラートを表示する
          $('#alert-1').append(e);
          /* 2秒後にアラートを消す
          setTimeout(() => {
            e.alert('close');
          }, 3000);
          */
        })(jQuery);
        /*
        const ctx = document.getElementById('myChart').getContext('2d');
        const myChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: [
                    <?php
                    $i=0;
                    foreach($result as $row){
                        if($i!=0){
                            echo ",";
                        }
                        if($row[0]===$row["ShouhinNM"]){
                            echo "'".rot13decrypt($row["ShouhinNM"])."'";
                            if($i==14){
                                break;
                            }
                        }else{
                            echo "'".$row[0]."'";
                        }
                        $i++;
                    }
                    ?>
                    ],
                datasets: [{
                    label: '売上金額(税抜)<?php if($row[0]===$row["ShouhinNM"]){
                            echo "TOP15";
                        }?>',
                    data: [
                        <?php
                        $i=0;
                        foreach($result as $row){
                            if($i!=0){
                                echo ",";
                            }
                            echo "'".$row[1]."'";
                            if($row[0]===$row["ShouhinNM"] && $i==14){
                                break;
                            }
                            $i++;
                        }
                        ?>
                        ],
                    backgroundColor:[
                        <?php
                        $i=0;
                        foreach($result as $row){
                            if($i!=0){
                                echo ",";
                            }
                            echo "'rgba('+(~~(256 * Math.random()))+','+(~~(256 * Math.random()))+','+ (~~(256 * Math.random()))+', 0.5)'\n";
                            if($row[0]===$row["ShouhinNM"] && $i==14){
                                break;
                            }
                            $i++;
                        }
                        ?>
                        ],
                    //borderWidth: 1,
                    maxBarThickness:20,
                    barPercentage:0.9
                }]
            },
            options: {
                scales: {
                    x: {
                        //beginAtZero: true
                    }
                },
                indexAxis: 'y'
            }
        });
    };
    */
    </script>

    
    <TITLE><?php echo $title." 売上分析";?></TITLE>
</head>
 
<header class="header-color" style="flex-wrap:wrap;height:50px">
    <div class="title" style="width: 100%;"><a href="menu.php"><?php echo $title;?></a></div>

</header>

<body style='padding-top:55px'>
    <div class="container-fluid">
    <div class="row">
    <div class="col-md-3" style='padding:5px;background:white'>
        <form class="form" method="post" action="analysis_abc.php" style='font-size:1.3rem'>
            集計期間:
            <select name='ymfrom' class="form-control" style="padding:0;width:10rem;display:inline-block;margin:5px">
            <?php
            foreach($SLVresult as $row){
                if($ymfrom==$row["Value"]){
                    echo "<option value='".$row["Value"]."' selected>".$row["display"]."</option>\n";
                }
                echo "<option value='".$row["Value"]."'>".$row["display"]."</option>\n";
            }
            ?>
            </select>
            から
            <select name='ymto' class="form-control" style="padding:0;width:10rem;display:inline-block;margin:5px">
            <?php
            foreach($SLVresult as $row){
                if($ymto==$row["Value"]){
                    echo "<option value='".$row["Value"]."' selected>".$row["display"]."</option>\n";
                }
                echo "<option value='".$row["Value"]."'>".$row["display"]."</option>\n";
            }
            ?>
            </select>
            <select name='sum_tani' class="form-control" style="padding:0;width:auto;max-width:100%;display:inline-block;margin:5px"><!--集計単位-->
                <option value='1' <?php if($_POST["sum_tani"]==1 || empty($_POST["sum_tani"])){echo "selected";} ?> >商品別ABC分析</option>
                <option value='2' <?php if($_POST["sum_tani"]==2){echo "selected";} ?>>イベント・店舗/商品別ABC分析</option>
                <!--
                <option value='3' <?php if($_POST["sum_tani"]==3){echo "selected";} ?> >売上実績(年計)</option>
                <option value='4' <?php if($_POST["sum_tani"]==4){echo "selected";} ?> >売上ランキング(金額)</option>
                <option value='5' <?php if($_POST["sum_tani"]==5){echo "selected";} ?> >売上ランキング(個数)</option>
                <option value='6' <?php if($_POST["sum_tani"]==6){echo "selected";} ?> >客単価推移</option>
                <option value='6' <?php if($_POST["sum_tani"]==7){echo "selected";} ?> >客単価ランキング</option>
                -->
            </select>
            <select name='list' class="form-control" style="padding:0;width:auto;max-width:100%;display:inline-block;margin:5px">
            <option value='%'>場所・顧客</option>
            <option value='%'>全て</option>
            <?php
            foreach($EVresult as $row){
                if($list==$row["LIST"]){
                    echo "<option value='".$row["LIST"]."' selected>".$row["LIST"]."</option>\n";
                }
                echo "<option value='".$row["LIST"]."'>".$row["LIST"]."</option>\n";
            }
            ?>
            </select>
            <input type='submit' class='btn-view' style='padding:0;hight:55px;width:100px;margin:2px;' value='検 索'>
        </form>
    </div>
    <!--
    <div class="col-md-6">
        <canvas id="myChart" width="95%" height="100%-55px" ></canvas>
    </div>
    -->
    <div class="col-md-9" style='padding:5px'>
    <?php
        drow_table_abc($aryColumn,$result,$cols);
    ?>
    </div>
    </div><!--row-->
    </div>
</body>
<!--
<footer>
</footer>
-->

</html>
<?php
$EVresult  = null;
$TKresult = null;
$stmt = null;
$pdo_h = null;
?>


