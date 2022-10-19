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
    $analysis_type=$_POST["sum_tani"];
}else{
    $ymfrom = (int)((string)date('Y')."01");
    $ymto = (string)date('Y')."12";
    $list = "%";
    $analysis_type=$_GET["sum_tani"];
}
//get_getsumatsu($ymfrom);
//deb_echo($list);
$cols=0;
if($analysis_type==1 ){//全商品（金額）
    $sqlstr = "select tmp.* ,sum(税抜売上) over() as 総売上 from (select ShouhinNM as ShouhinNM ,sum(UriageKin) as 税抜売上 from UriageData ";
    $gp_sqlstr = "group by ShouhinNM) tmp order by 税抜売上 desc";
    $aryColumn = ["商品名","税抜売上"];
    $cols=2;
}elseif($analysis_type==2 ){//イベントごと
    $sqlstr = "select tmp.* ,sum(税抜売上) over(PARTITION BY Event) as 総売上 from (select Event,ShouhinNM as ShouhinNM ,sum(UriageKin) as 税抜売上 from UriageData ";
    $gp_sqlstr = "group by Event,ShouhinNM) tmp order by Event,税抜売上 desc";
    $aryColumn = ["商品名","税抜売上"];
    $cols=3;
}
$sqlstr = $sqlstr." where ShouhinCD<9900 and DATE_FORMAT(UriDate, '%Y%m') between :ymfrom and :ymto AND uid = :user_id ";
$sqlstr = $sqlstr." AND (Event like :event OR TokuisakiNM like :tokui )";
$sqlstr = $sqlstr." ".$gp_sqlstr;

//deb_echo($sqlstr);
$_SESSION["Event"]      =(empty($_POST["list"])?"%":$_POST["list"]);

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
/*
$EVsql = "select Event as LIST from UriageData where uid =? and Event <> '' group by Event ";
$EVsql = $EVsql."union select TokuisakiNM as LIST from UriageData where uid =? and TokuisakiNM<>'' group by TokuisakiNM ";
$stmt = $pdo_h->prepare($EVsql);
$stmt->bindValue(1, $_SESSION['user_id'], PDO::PARAM_INT);
$stmt->bindValue(2, $_SESSION['user_id'], PDO::PARAM_INT);
$stmt->execute();
$EVresult = $stmt->fetchAll();
*/

?>
<head>
    <?php 
    //共通部分、bootstrap設定、フォントCND、ファビコン等
    include "head.html" 
    ?>
    <!--ページ専用CSS-->
    <link rel="stylesheet" href="css/style_analysis.css?<?php echo $time; ?>" >
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.1/chart.min.js" integrity="sha512-QSkVNOCYLtj73J4hbmVoOV6KVZuMluZlioC+trLpewV8qMjsWqlIQvkn1KGX2StWvPMdWGBqim1xlC8krl1EKQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>    
    
    <script>

    </script>

    
    <TITLE><?php echo $title." 売上分析";?></TITLE>
</head>
<script>
    window.onload = function(){
        function getAllData(List,date_from,date_to,get_list_type){
            //検索用のイベント・顧客・商品リストを取得
            //id名[List]のリストデータを[date_from]～[date_to]に発生した[get_list_type]に更新
            $.ajax({
                // 通信先ファイル名
                type        : 'POST',
                url         : 'ajax_get_event_list.php',
                //dataType    : 'application/json',
                data        :{
                                user_id     :'<?php echo $_SESSION["user_id"];?>',
                                date_from   :$(date_from)[0].value,
                                date_to     :$(date_to)[0].value,
                                list_type   :get_list_type //イベントリスト or 商品リスト
                            }
                },
            ).done(
                // 通信が成功した時
                function(data) {
                    //selectの子要素をすべて削除
                    $(List).children().remove();
                    $(List).append("<option value='%'>イベント名選択</option>\n");
                    $(List).append("<option value='%'>全て</option>\n");
                    // 取得したレコードをeachで順次取り出す
                    $.each(data, function(key, value){
                        // appendで追記していく
                        if(get_list_type=='Event'){
                            if(value.LIST == '<?php echo $_SESSION["Event"]; ?>'){
                                $(List).append("<option value='" + value.LIST + "' selected>" + value.LIST + "</option>\n");
                            }else{
                                $(List).append("<option value='" + value.LIST + "'>" + value.LIST + "</option>\n");
                            }
                        }else if(get_list_type=='Shouhin'){
                            $(List).append("<option value='" + value.CODE + "'>" + value.CODE+ ":" + value.LIST + "</option>\n");
                        }
                    });
                    
                    //console.log("通信成功");
                    //console.log(data);
                    
                }
            ).fail(
                // 通信が失敗した時
                function(XMLHttpRequest, textStatus, errorThrown){
                    console.log("通信失敗2");
                    console.log("XMLHttpRequest : " + XMLHttpRequest.status);
                    console.log("textStatus     : " + textStatus);
                    console.log("errorThrown    : " + errorThrown.message);
                }
            )};
            
        //起動時にリストを取得
        getAllData('#Event','#uridate','#uridateto','Event');
        
    };
</script>
<header class="header-color common_header" style="flex-wrap:wrap;height:50px">
    <div class="title" style="width: 100%;"><a href="analysis_menu.php?csrf_token=<?php echo $csrf_create; ?>"><?php echo $title;?></a></div>

</header>

<body class='common_body' style='padding-top:55px'>
    <div class="container-fluid">
    <div class="row">
    <div class="col-md-3" style='padding:5px;background:white'>
        <form class="form" method="post" action="analysis_abc.php" style='font-size:1.5rem' id='form1'>
            集計期間:
            <select name='ymfrom' class="form-control" style="padding:0;width:11rem;display:inline-block;margin:5px" onchange='send()' id='uridate'>
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
            <select name='ymto' class="form-control" style="padding:0;width:11rem;display:inline-block;margin:5px" onchange='send()' id='uridateto'>
            <?php
            foreach($SLVresult as $row){
                if($ymto==$row["Value"]){
                    echo "<option value='".$row["Value"]."' selected>".$row["display"]."</option>\n";
                }
                echo "<option value='".$row["Value"]."'>".$row["display"]."</option>\n";
            }
            ?>
            </select>
            
            <select name='sum_tani' class="form-control" style="padding:0;width:auto;max-width:100%;display:inline-block;margin:5px" onchange='send()' ><!--集計単位-->
                <option value='1' <?php if($analysis_type==1){echo "selected";} ?> >商品別ABC分析</option>
                <option value='2' <?php if($analysis_type==2){echo "selected";} ?>>イベント・店舗/商品別ABC分析</option>
            </select>
            <select name='list' class="form-control" style="padding:0;width:auto;max-width:100%;display:inline-block;margin:5px" onchange='send()' id='Event' >
            <!--
            <option value='%'>場所・顧客</option>
            <option value='%'>全て</option>
            <?php
            /*
            foreach($EVresult as $row){
                if($list==$row["LIST"]){
                    echo "<option value='".$row["LIST"]."' selected>".$row["LIST"]."</option>\n";
                }
                echo "<option value='".$row["LIST"]."'>".$row["LIST"]."</option>\n";
            }
            */
            ?>
            -->
            </select>
            <!--<button type='submit' class='btn btn-primary' style='padding:0;hight:55px;width:100px;margin:2px;'>検　索</button>-->
        </form>
    </div>
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
<script>
    function send(){
        const form1 = document.getElementById('form1');
        form1.submit();
    }
    
</script>
</html>
<?php
$EVresult  = null;
$TKresult = null;
$stmt = null;
$pdo_h = null;
?>


