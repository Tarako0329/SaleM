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

if(isset($_GET["csrf_token"]) || empty($_POST)){
    if(csrf_chk_nonsession_get($_GET["csrf_token"])==false){
        $_SESSION["EMSG"]="セッションが正しくありませんでした。①";
        header("HTTP/1.1 301 Moved Permanently");
        header("Location: index.php");
        exit();
    }
}

$rtn=check_session_userid($pdo_h);
$csrf_create = csrf_create();

//deb_echo("UID：".$_SESSION["user_id"]);

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
if($_POST["sum_tani"]==1){//日ごと
    $sqlstr = "select UriDate as 計上年月 ,sum(UriageKin) as 税抜売上,sum(zei) as 税,sum(UriageKin+zei) as 税込売上 from UriageData ";
    $gp_sqlstr = "group by UriDate order by UriDate";
    $aryColumn = ["計上日","税抜売上","消費税","税込売上"];
}elseif($_POST["sum_tani"]==2 || empty($_POST)){//月毎
    $sqlstr = "select DATE_FORMAT(UriDate, '%Y/%m') as 計上年月 ,sum(UriageKin) as 税抜売上,sum(zei) as 税,sum(UriageKin+zei) as 税込売上 from UriageData ";
    $gp_sqlstr = "group by DATE_FORMAT(UriDate, '%Y%m') order by DATE_FORMAT(UriDate, '%Y%m')";
    $aryColumn = ["計上年月","税抜売上","消費税","税込売上"];
}elseif($_POST["sum_tani"]==3){//年ごと
    $sqlstr = "select DATE_FORMAT(UriDate, '%Y') as 計上年月 ,sum(UriageKin) as 税抜売上,sum(zei) as 税,sum(UriageKin+zei) as 税込売上 from UriageData ";
    $gp_sqlstr = "group by DATE_FORMAT(UriDate, '%Y') order by DATE_FORMAT(UriDate, '%Y')";
    $aryColumn = ["計上年度","税抜売上","消費税","税込売上"];
}elseif($_POST["sum_tani"]==4){//製品名ごと売上金額ランキング
    $sqlstr = "select ShouhinNM as ShouhinNM ,sum(UriageKin) as 税抜売上,sum(zei) as 税,sum(UriageKin+zei) as 税込売上 from UriageData ";
    $gp_sqlstr = "group by ShouhinNM order by sum(UriageKin) desc";
    $aryColumn = ["商品名","税抜売上","消費税","税込売上"];
}elseif($_POST["sum_tani"]==5){//製品名ごと売上数量ランキング
    $sqlstr = "select ShouhinNM as ShouhinNM ,sum(Su) as 売上数 from UriageData ";
    $gp_sqlstr = "group by ShouhinNM order by sum(Su) desc";
    $aryColumn = ["商品名","売上数"];
}elseif($_POST["sum_tani"]==6){//客単価推移
    //客単価一覧
    $sqlstr = "select 計上日,ROUND(avg(税抜売上)) as 客単価,Event from ";
    $sqlstr = $sqlstr." (select UriDate as 計上日 ,Event ,UriageNO ,sum(UriageKin) as 税抜売上 from UriageData ";
    $gp_sqlstr = "group by UriDate,UriageNO ) as UriSum group by 計上日 order by 計上日";
    $aryColumn = ["計上日","客単価","Event/店舗"];
}elseif($_POST["sum_tani"]==7){//イベント・店舗別客単価ランキング
    $sqlstr = "select KYAKU,ROUND(avg(客単価)) as 平均客単価 from ";
    $sqlstr = $sqlstr." (select UriDate as 計上日 ,concat(Event,TokuisakiNM) as KYAKU ,UriageNO ,sum(UriageKin) as 客単価 from UriageData ";
    $gp_sqlstr = "group by UriDate,concat(Event,TokuisakiNM),UriageNO ) as UriSum group by KYAKU order by avg(客単価) desc";
    $aryColumn = ["Event/店舗","客単価"];
}elseif($_POST["sum_tani"]==8){//イベント・店舗別来客数推移
    $sqlstr = "select UriDate,sum(来客カウント) as 来客数,Event from ";
    $sqlstr = $sqlstr." (select uid, UriDate, Event, TokuisakiNM, UriageNO,0 as ShouhinCD, 1 as 来客カウント from UriageData where Event <>'' ";
    $sqlstr = $sqlstr." group by uid,UriDate,Event,TokuisakiNM,UriageNO) as UriSum ";
    $gp_sqlstr = "group by UriDate,Event order by UriDate";
    $aryColumn = ["計上日","来客数","Event/店舗"];
}elseif($_POST["sum_tani"]==9){//イベント・店舗別来客数ランキング
    $sqlstr = "select Event,ROUND(avg(来客数)) as 平均来客数 from (select UriDate,sum(来客カウント) as 来客数,Event from ";
    $sqlstr = $sqlstr." (select uid, UriDate, Event, TokuisakiNM, UriageNO,0 as ShouhinCD, 1 as 来客カウント from UriageData where Event <>'' ";
    $sqlstr = $sqlstr." group by uid,UriDate,Event,TokuisakiNM,UriageNO) as UriSum ";
    $gp_sqlstr = "group by UriDate,Event) as Urisum2 group by Event order by ROUND(avg(来客数)) desc";
    $aryColumn = ["Event/店舗","平均来客数"];
}elseif($_POST["sum_tani"]==10){//商品の売れる勢い
    $sqlstr = "select ShouhinNM,concat(time_format(insDatetime,'%H'), '時') as Hour,sum(su) as 個数 from UriageData ";
    $gp_sqlstr = "group by ShouhinNM,time_format(insDatetime,'%H') order by ShouhinNM,time_format(insDatetime,'%H')";
    $aryColumn = ["商品名","時","個数"];
}

$sqlstr = $sqlstr." where ShouhinCD<9900 and DATE_FORMAT(UriDate, '%Y%m') between :ymfrom and :ymto AND uid = :user_id ";
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
    <!--<link rel="stylesheet" href="css/style_UriageData.css?<?php echo $time; ?>" >-->
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.1/chart.min.js" integrity="sha512-QSkVNOCYLtj73J4hbmVoOV6KVZuMluZlioC+trLpewV8qMjsWqlIQvkn1KGX2StWvPMdWGBqim1xlC8krl1EKQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>    
    
    <script>
    window.onload = function() {

    <?php
    if($_POST["sum_tani"]!=10){
    ?>
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
                    label: '<?php echo $aryColumn[1];if($row[0]===$row["ShouhinNM"]){
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
    <?php
    }else if($_POST["sum_tani"]==10){
        $label="";  //商品名を格納
        $j=0;       //0～23までのカウンタ
        $urisu=0;   //売上総数の保持
        $min_hour=24;   //時間軸の最小値
        $max_hour=0;    //時間軸の最大値
        foreach($result as $row){
            //取得したデータの中で最小時間をセット
            if($min_hour>$row["Hour"]){
                $min_hour=$row["Hour"];
            }
            //取得したデータの中で最大時間をセット
            if($max_hour<$row["Hour"]){
                $max_hour=$row["Hour"];
            }
        }
        //最初に売れた時間帯の2時間前から最後に売れた1時間後までを時間軸に使用
        $min_hour=$min_hour-2;
        $max_hour=$max_hour+1;
    ?>
        var ctx = document.getElementById('myChart');
        var data = {
            <?php
            echo "      labels: [";
            $j=$min_hour;
            while($j<=$max_hour){
                echo "'".$j."時',";
                $j++;
            }
            echo "],\n";
            echo "      datasets: [\n";
            $j=$min_hour;
            foreach($result as $row){
                if($label!=$row["ShouhinNM"]){
                    if($j!=$min_hour){
                        //echo $urisu;
                        while($j<=$max_hour){
                            echo $urisu.",";
                            $j++;
                        }
                        echo "]},\n";
                    }
                    $urisu=0;
                    $j=$min_hour;
                    echo "      {\n";
                    echo "      borderColor: 'rgba('+(~~(256 * Math.random()))+','+(~~(256 * Math.random()))+','+ (~~(256 * Math.random()))+', 0.8)',\n";
                    echo "      label: '".rot13decrypt($row["ShouhinNM"])."',\n";
                    echo "      tension: 0.2,\n";
                    echo "      data: [";
                    $label=$row["ShouhinNM"];
                }
                while($j<=$max_hour){
                    //echo $j."時:".$row["Hour"]."\n";
                    if($j."時"==$row["Hour"]){
                        $urisu = $urisu + $row["個数"];
                        echo $urisu.",";
                        $j++;
                        break;
                    }else{
                        echo $urisu.",";
                    }
                    
                    $j++;
                }
            }
            while($j<=$max_hour){
                echo $urisu.",";
                $j++;
            }
            echo "]}\n";
            echo "      ]};\n";
            /*
            var data = {
                labels: ["1月", "2月", "3月", "4月", "5月"],
                datasets: [{
                    label: 'プリンター',
                    data: [880, 740, 900, 520, 930],
                    borderColor: 'rgba(255, 100, 100, 1)',
                    lineTension: 0,
                    fill: false,
                    borderWidth: 3
                },
                {
                    label: 'パソコン',
                    data: [1200, 1350, 1220, 1220, 1420],
                    borderColor: 'rgba(100, 100, 255, 1)',
                    lineTension: 0,
                    fill: false,
                    borderWidth: 3
                }]
            };
            */
            ?>
        
        var options = {};
        
        var ex_chart = new Chart(ctx, {
            type: 'line',
            data: data,
            options: options
        });
    <?php
    }
    ?>
        
    };
    </script>

    
    <TITLE><?php echo $title." 売上分析";?></TITLE>
</head>
 
<header class="header-color" style="flex-wrap:wrap;height:50px">
    <div class="title" style="width: 100%;"><a href="analysis_menu.php?csrf_token=<?php echo $csrf_create; ?>"><?php echo $title;?></a></div>
</header>

<body style='padding-top:55px'>
    <div class="container-fluid">
    <div class="row">
    <div class="col-md-3" style='padding:5px;background:white'>
        <form class="form" method="post" action="analysis_uriagejisseki.php" style='font-size:1.5rem'>
            集計期間:
            <select name='ymfrom' class="form-control" style="padding:0;width:11rem;display:inline-block;margin:5px">
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
            <select name='ymto' class="form-control" style="padding:0;width:11rem;display:inline-block;margin:5px">
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
                <option value='1' <?php if($_POST["sum_tani"]==1){echo "selected";} ?> >売上実績(日計)</option>
                <option value='2' <?php if($_POST["sum_tani"]==2 || empty($_POST["sum_tani"])){echo "selected";} ?>>売上実績(月計)</option>
                <option value='3' <?php if($_POST["sum_tani"]==3){echo "selected";} ?> >売上実績(年計)</option>
                <option value='4' <?php if($_POST["sum_tani"]==4){echo "selected";} ?> >売上ランキング(金額)</option>
                <option value='5' <?php if($_POST["sum_tani"]==5){echo "selected";} ?> >売上ランキング(個数)</option>
                <option value='6' <?php if($_POST["sum_tani"]==6){echo "selected";} ?> >客単価推移</option>
                <option value='7' <?php if($_POST["sum_tani"]==7){echo "selected";} ?> >客単価ランキング</option>
                <option value='8' <?php if($_POST["sum_tani"]==8){echo "selected";} ?> >来客数推移</option>
                <option value='9' <?php if($_POST["sum_tani"]==9){echo "selected";} ?> >平均来客数ランキング</option>
                <option value='10' <?php if($_POST["sum_tani"]==10){echo "selected";} ?> >売れる勢い</option>
            </select>
            <select name='list' class="form-control" style="padding:0;width:auto;max-width:100%;display:inline-block;margin:5px">
            <option value='%'>イベント・顧客の選択</option>
            <option value='%'>全て</option>
            <?php
            foreach($EVresult as $row){
                if($list==$row["LIST"]){
                    echo "<option value='".$row["LIST"]."' selected>".$row["LIST"]."</option>\n";
                }else{
                    echo "<option value='".$row["LIST"]."'>".$row["LIST"]."</option>\n";
                }
            }
            ?>
            </select>
            <button type='submit' class='btn btn-primary'>検　索</button>
        </form>
    </div>
    <div class="col-md-6">
        <canvas id="myChart" width="95%" height="100%-55px" ></canvas>
    </div>
    <div class="col-md-3" style='padding:5px'>
    <?php
        //var_dump($result);
        drow_table($aryColumn,$result);
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


