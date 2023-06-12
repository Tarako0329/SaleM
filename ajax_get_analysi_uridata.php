<?php
/*  【Params[POST]】
    analysis_type   :分析タイプ
    ymfrom          :yyyy-mm-dd
    ymto            :yyyy-mm-dd
    event
    tokui
    

    【return】
    aryColumn : []
    labels:[]
    data : []
    chart_type
*/
//log_writer2("ajax_UriageDate_update_sql.php",$sql,"lv3");
require "php_header.php";
register_shutdown_function('shutdown');

$msg = "";                          //ユーザー向け処理結果メッセージ
$alert_status = "alert-warning";    //bootstrap alert class
$reseve_status=false;               //処理結果セット済みフラグ。
$timeout=false;                     //セッション切れ。ログイン画面に飛ばすフラグ
$myname = "ajax_get_analysi_uridata.php";           //ログファイルに出力する自身のファイル名

$top15="";
$aryColumn = [];
$result=[];
$labels=[];
$data=[];
$chart_type="";

$rtn = csrf_checker(["analysis_uriagejisseki.php"],["P","C","S"]);
if($rtn !== true){
    $msg=$rtn;
    $alert_status = "alert-warning";
    $reseve_status = true;
}else{
    $tokui = $_POST["tokui"];
    $rtn=check_session_userid_for_ajax($pdo_h);
    if($rtn===false){
        $reseve_status = true;
        $msg="長時間操作されていないため、自動ﾛｸﾞｱｳﾄしました。再度ログインし、もう一度xxxxxxして下さい。";
        $_SESSION["EMSG"]="長時間操作されていないため、自動ﾛｸﾞｱｳﾄしました。再度ログインし、もう一度xxxxxxして下さい。";
        $timeout=true;
    }else{
        $logfilename="sid_".$_SESSION['user_id'].".log";

        $category=(!empty($_POST["category"])?$_POST["category"]:"")."%";
        $category_lv=(!empty($_POST["category_lv"])?$_POST["category_lv"]:"0");

        $analysis_type = $_POST["analysis_type"];

        //SQL文作成
        if($analysis_type==1){//日ごと
            $sqlstr = "select UriDate as Labels ,sum(UriageKin) as datasets,sum(zei) as 税,sum(UriageKin+zei) as datasets_withTax from UriageData ";
            $gp_sqlstr = "group by UriDate order by UriDate";
            $aryColumn = ["計上日","税抜売上","消費税","税込売上"];
            $chart_type="bar";
        }elseif($analysis_type==2){//月毎
            $sqlstr = "select DATE_FORMAT(UriDate, '%Y/%m') as Labels ,sum(UriageKin) as datasets,sum(zei) as 税,sum(UriageKin+zei) as datasets_withTax from UriageData ";
            $gp_sqlstr = "group by DATE_FORMAT(UriDate, '%Y%m') order by DATE_FORMAT(UriDate, '%Y%m')";
            $aryColumn = ["計上年月","税抜売上","消費税","税込売上"];
            $chart_type="bar";
        }elseif($analysis_type==3){//年ごと
            $sqlstr = "select DATE_FORMAT(UriDate, '%Y') as Labels ,sum(UriageKin) as datasets,sum(zei) as 税,sum(UriageKin+zei) as datasets_withTax from UriageData ";
            $gp_sqlstr = "group by DATE_FORMAT(UriDate, '%Y') order by DATE_FORMAT(UriDate, '%Y')";
            $aryColumn = ["計上年度","税抜売上","消費税","税込売上"];
            $chart_type="bar";
        }elseif($analysis_type==4){//製品名ごと売上金額ランキング
            $sqlstr = "select ShouhinNM as Labels ,sum(UriageKin) as datasets,sum(zei) as 税,sum(UriageKin+zei) as datasets_withTax from UriageData ";
            $gp_sqlstr = "group by ShouhinNM order by sum(UriageKin) desc";
            $aryColumn = ["商品名","税抜売上","消費税","税込売上"];
            $chart_type="bar";
            $top15="on";
        }elseif($analysis_type==5){//製品名ごと売上数量ランキング
            $sqlstr = "select ShouhinNM as Labels ,sum(Su) as datasets from UriageData ";
            $gp_sqlstr = "group by ShouhinNM order by sum(Su) desc";
            $aryColumn = ["商品名","売上数"];
            $chart_type="bar";
            $top15="on";
        }elseif($analysis_type==6){//客単価推移
            //客単価一覧
            $sqlstr = "select 計上日,Event as Labels,ROUND(avg(税抜売上)) as datasets from ";
            $sqlstr = $sqlstr." (select UriDate as 計上日 ,concat(Event,TokuisakiNM) as Event ,UriageNO ,sum(UriageKin) as 税抜売上 from UriageData ";
            $gp_sqlstr = "group by UriDate,UriageNO ) as UriageData group by 計上日 order by 計上日";
            $aryColumn = ["計上日","Event/店舗","客単価"];
            $chart_type="bar";
        }elseif($analysis_type==7){//イベント・店舗別客単価ランキング
            $sqlstr = "select KYAKU as Labels,ROUND(avg(客単価)) as datasets from ";
            $sqlstr = $sqlstr." (select UriDate as 計上日 ,concat(Event,TokuisakiNM) as KYAKU ,UriageNO ,sum(UriageKin) as 客単価 from UriageData ";
            $gp_sqlstr = "group by UriDate,concat(Event,TokuisakiNM),UriageNO ) as UriageData group by KYAKU order by avg(客単価) desc";
            $aryColumn = ["Event/店舗","客単価"];
            $chart_type="bar";
            $top15="on";
        }elseif($analysis_type==8){//イベント・店舗別来客数推移
            $sqlstr = "select UriDate as Labels,Event,sum(来客カウント) as datasets from ";
            $sqlstr = $sqlstr." (select uid, UriDate, Event, TokuisakiNM, UriageNO,0 as ShouhinCD, 1 as 来客カウント from UriageData where Event <>'' ";
            $sqlstr = $sqlstr." group by uid,UriDate,Event,TokuisakiNM,UriageNO) as UriageData ";
            $gp_sqlstr = "group by UriDate,Event order by UriDate";
            $aryColumn = ["計上日","Event/店舗","来客数"];
            $chart_type="bar";
            
            $tokui="xxxx";//来客数の場合は個別売りを除く
        }elseif($analysis_type==9){//イベント・店舗別来客数ランキング
            $sqlstr = "select Event as Labels,ROUND(avg(来客数)) as datasets from (select UriDate,sum(来客カウント) as 来客数,Event from ";
            $sqlstr = $sqlstr." (select uid, UriDate, Event, TokuisakiNM, UriageNO,0 as ShouhinCD, 1 as 来客カウント from UriageData where Event <>'' ";
            $sqlstr = $sqlstr." group by uid,UriDate,Event,TokuisakiNM,UriageNO) as UriageData ";
            $gp_sqlstr = "group by UriDate,Event) as Urisum2 group by Event order by ROUND(avg(来客数)) desc";
            $aryColumn = ["Event/店舗","平均来客数"];
            $chart_type="bar";
            
            $tokui="xxxx";//来客数の場合は個別売りを除く
            $top15="on";
        }elseif($analysis_type==10){//商品の売れる勢い
            $sqlstr = "select ShouhinNM as NAME,concat(time_format(insDatetime,'%H'), '時') as Hour,sum(su) as COUNT from UriageData ";
            $gp_sqlstr = "group by ShouhinNM,time_format(insDatetime,'%H') order by ShouhinNM,time_format(insDatetime,'%H')";
            $aryColumn = ["商品名","時","個数"];
            $chart_type="line";
            
            $tokui="xxxx";//時間別推移の場合は個別売りを除く
        }elseif($analysis_type==11){//来客数推移
            $sqlstr = "select tmp.Event as NAME ,tmp.Hour as Hour,count(*) as COUNT from (select Event,concat(time_format(insDatetime,'%H'), '時') as Hour,UriageNO from UriageData ";
            $gp_sqlstr = "group by Event,concat(time_format(insDatetime,'%H'), '時'),UriageNO) as tmp group by tmp.Event,tmp.Hour order by tmp.Event,tmp.Hour";
            $aryColumn = ["イベント名","時","人数"];
            $chart_type="line";
            
            $tokui="xxxx";//時間別推移の場合は個別売りを除く
        }elseif($analysis_type==12){//ジャンル別実績
            if($category_lv==="0"){
                $sql_category = "if(bunrui1<>'',bunrui1,'未分類')";
                $category="%";
            }elseif($category_lv==="1"){
                $sql_category = "concat(if(bunrui1<>'',bunrui1,'未分類'),'>',if(bunrui2<>'',bunrui2,'未分類'))";
                
            }elseif($category_lv==="2"){
                $sql_category = "concat(if(bunrui1<>'',bunrui1,'未分類'),'>',if(bunrui2<>'',bunrui2,'未分類'),'>',if(bunrui3<>'',bunrui3,'未分類'))";
                $category_lv=-1;
            }else{
                $sql_category = "";
                $sql_category_where="";
            }
            $sql_category_where = " AND ".$sql_category." LIKE '".$category."'";
            $sqlstr = "select ".$sql_category." as Labels,sum(UriageKin) as datasets from UriageData inner join ShouhinMS on UriageData.uid=ShouhinMS.uid and UriageData.shouhinCD=ShouhinMS.shouhinCD ";
            $gp_sqlstr = "group by ".$sql_category." order by sum(UriageKin) desc";
            $aryColumn = ["カテゴリー","売上"];
            $chart_type="doughnut";
        }
        
        $sqlstr = $sqlstr." where UriageData.ShouhinCD<9900 and UriDate between :ymfrom and :ymto AND UriageData.uid = :user_id ";
        $sqlstr = $sqlstr." AND ((TokuisakiNM ='' and Event like :event) OR (Event = '' and TokuisakiNM like :tokui ))";
        $sqlstr = $sqlstr.(!empty($sql_category_where)?$sql_category_where:"");
        
        $sqlstr = $sqlstr." ".$gp_sqlstr;
        
        try{
            $stmt = $pdo_h->prepare( $sqlstr );
            $stmt->bindValue("ymfrom", $_POST["date_from"], PDO::PARAM_INT);
            $stmt->bindValue("ymto", $_POST["date_to"], PDO::PARAM_INT);
            $stmt->bindValue("user_id", $_SESSION["user_id"], PDO::PARAM_INT);
            $stmt->bindValue("event", "%".$_POST["event"]."%", PDO::PARAM_STR);
            $stmt->bindValue("tokui", "%".$tokui."%", PDO::PARAM_STR);

            $status=$stmt->execute();
            $result = $stmt->fetchall(PDO::FETCH_ASSOC);
            
            //線グラフのx軸範囲
            $xEndpoint=0;
            $xStartpoint=23;

            $xlabels=[];
            if($chart_type==="bar" || $chart_type==="doughnut"){
                $i=0;
                foreach($result as $row){
                    $labels[$i] = $row["Labels"];
                    $data[$i] = $row["datasets"];
                    $i++;
                }
            }else if($chart_type==="line"){
                $i=0;
                $h=0;
                foreach($result as $row){
                    log_writer2($myname." \$row[NAME] =>",$row["NAME"],"lv3");
                    if($i===0 && empty($labels[$i])){
                        $labels[$i] = $row["NAME"];
                    }else if($labels[$i] !== $row["NAME"]){
                        for(;$h<24;$h++){//24時まで0埋めする
                            $data[$i][$h] = 0;
                        }
                        $h=0;$i++;
                        $labels[$i] = $row["NAME"];
                    }
                    
                    for(;$h<24;$h++){
                        log_writer2($myname." \$row[Hour] =>",substr($row["Hour"],0,2),"lv3");
                        if($h == (int)substr($row["Hour"],0,2)){
                            $data[$i][$h] = $row["COUNT"];
                            $xEndpoint=($h>$xEndpoint)?$h:$xEndpoint;
                            $xStartpoint=($h<$xStartpoint)?$h:$xStartpoint;
                            
                            $h++;
                            break;
                        }else{
                            $data[$i][$h] = 0;
                        }
                    }
                }
                for(;$h<24;$h++){//24時まで0埋めする
                    $data[$i][$h] = 0;
                }
                $xEndpoint=($xEndpoint + 1<=23)?$xEndpoint+1:$xEndpoint;
                $xStartpoint=($xStartpoint - 1 >=0)?$xStartpoint-1:$xStartpoint;
            }
            $msg = "取得成功。";
            $alert_status = "alert-success";
            /*
            if($status===true){
                $msg = "取得成功。";
                $alert_status = "alert-success";
            }else{
                $msg = "取得失敗。";
                $alert_status = "alert-danger";
            }
            */
            $reseve_status=true;
        }catch(Exception $e){
            $msg = "システムエラーによる取得処理失敗。管理者へ通知しました。";
            $alert_status = "alert-danger";
            log_writer2($myname." [Exc sql] =>",$sqlstr,"lv0");
            log_writer2($myname." [Exception \$e] =>",$e,"lv0");
            $reseve_status=true;
        }
    }
}

$token = csrf_create();

$return_sts = array(
    "MSG" => $msg
    ,"status" => $alert_status
    ,"csrf_create" => $token
    ,"timeout" => $timeout
    ,"aryColumn" => $aryColumn
    ,"result" => $result
    ,"labels" => $labels
    ,"data" => $data
    ,"chart_type" => $chart_type
    ,"top15" => $top15
    ,'xStart' => $xStartpoint
    ,'xEnd' => $xEndpoint
);
header('Content-type: application/json');
echo json_encode($return_sts, JSON_UNESCAPED_UNICODE);

exit();


function shutdown(){
    // シャットダウン関数
    // スクリプトの処理が完了する前に
    // ここで何らかの操作をすることができます
    // トランザクション中のエラー停止時は自動rollbackされる。
      $lastError = error_get_last();
      
      //直前でエラーあり、かつ、catch処理出来ていない場合に実行
      if($lastError!==null && $GLOBALS["reseve_status"] === false){
        log_writer2(basename(__FILE__),"shutdown","lv3");
        log_writer2(basename(__FILE__),$lastError,"lv1");
          
        $emsg = "/UriNO::".$GLOBALS["UriageNO"]."　uid::".$_SESSION['user_id']." ERROR_MESSAGE::予期せぬエラー".$lastError['message'];
        if(EXEC_MODE!=="Local"){
            send_mail(SYSTEM_NOTICE_MAIL,"【WEBREZ-WARNING】".basename(__FILE__)."でシステム停止",$emsg);
        }
        log_writer2(basename(__FILE__)." [Exception \$lastError] =>",$lastError,"lv0");
    
        $token = csrf_create();
        $return_sts = array(
            "MSG" => "システムエラーによる更新失敗。管理者へ通知しました。"
            ,"status" => "alert-danger"
            ,"csrf_create" => $token
            ,"timeout" => false
        );
        header('Content-type: application/json');
        echo json_encode($return_sts, JSON_UNESCAPED_UNICODE);
      }
  }
  

?>