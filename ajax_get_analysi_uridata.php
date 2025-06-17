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
$grafu_discription = "";

$rtn = csrf_checker(["analysis_uriagejisseki.php","analysis_abc.php"],["P","C","S"]);
log_writer2("\$_POST",$_POST,"lv3");
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

		//割引割増を含まない
		$sql_where_OUT = " WHERE 
			UriageData.ShouhinCD<9900 
			and left(UriageData.ShouhinCD,1) not in ('C','Z') 
			and UriDate between :ymfrom and :ymto 
			AND UriageData.uid = :user_id 
			AND ((TokuisakiNM ='' and Event like :event) OR (Event = '' and TokuisakiNM like :tokui ))";
		
		//割引割増を含む
		$sql_where_IN = " WHERE 
			UriageData.ShouhinCD<9900 
			and left(UriageData.ShouhinCD,1) not in ('Z') 
			and UriDate between :ymfrom and :ymto 
			AND UriageData.uid = :user_id 
			AND ((TokuisakiNM ='' and Event like :event) OR (Event = '' and TokuisakiNM like :tokui ))";

		if($analysis_type==1){//日ごと の売上実績
			$sqlstr = "SELECT ROW_NUMBER() OVER (ORDER BY (SELECT NULL)) AS UKEY, UriDate as Labels ,sum(UriageKin) as datasets ,sum(UriageKin)-sum(IFNULL(genka_tanka,0) * su) as arari from UriageData ".$sql_where_IN." group by UriDate order by UriDate";
			$aryColumn = ["計上日","売上(税抜)","粗利"];
			$chart_type="bar";
		}elseif($analysis_type==2){//月毎の売上実績
			$sqlstr = "SELECT ROW_NUMBER() OVER (ORDER BY (SELECT NULL)) AS UKEY, DATE_FORMAT(UriDate, '%Y/%m') as Labels ,sum(UriageKin) as datasets ,sum(UriageKin)-sum(IFNULL(genka_tanka,0) * su) as arari from UriageData ".$sql_where_IN." group by DATE_FORMAT(UriDate, '%Y%m') order by DATE_FORMAT(UriDate, '%Y%m')";
			$aryColumn = ["計上年月","売上(税抜)","粗利"];
			$chart_type="bar";
		}elseif($analysis_type==3){//年ごとの売上実績
			$sqlstr = "SELECT ROW_NUMBER() OVER (ORDER BY (SELECT NULL)) AS UKEY, concat(DATE_FORMAT(UriDate, '%Y'),'年') as Labels ,sum(UriageKin) as datasets ,sum(UriageKin)-sum(IFNULL(genka_tanka,0) * su) as arari from UriageData ".$sql_where_IN." group by DATE_FORMAT(UriDate, '%Y') order by DATE_FORMAT(UriDate, '%Y')";
			$aryColumn = ["計上年度","売上(税抜)","粗利"];
			$chart_type="bar";
		}elseif($analysis_type==4){//製品名ごと売上金額ランキング
			$sqlstr = "SELECT ROW_NUMBER() OVER (ORDER BY (SELECT NULL)) AS UKEY, ShouhinNM as Labels ,sum(UriageKin) as datasets ,sum(UriageKin)-sum(IFNULL(genka_tanka,0) * su) as arari from UriageData ".$sql_where_OUT."group by ShouhinNM order by sum(UriageKin) desc";
			$aryColumn = ["商品名","売上(税抜)","粗利"];
			$chart_type="bar";
			$top15="on";
		}elseif($analysis_type==5){//製品名ごと売上数量ランキング
			$sqlstr = "SELECT ROW_NUMBER() OVER (ORDER BY (SELECT NULL)) AS UKEY, ShouhinNM as Labels ,sum(Su) as datasets from UriageData ".$sql_where_OUT."group by ShouhinNM order by sum(Su) desc";
			$aryColumn = ["商品名","売上数"];
			$chart_type="bar";
			$top15="on";
		}elseif($analysis_type==6){//客単価推移
			$sqlstr = "SELECT ROW_NUMBER() OVER (ORDER BY (SELECT NULL)) AS UKEY, 計上日,Event as Labels,ROUND(avg(税抜売上)) as datasets from 
				(SELECT UriDate as 計上日 ,concat(Event,TokuisakiNM) as Event ,UriageNO ,sum(UriageKin) as 税抜売上 from UriageData ".$sql_where_IN." group by UriDate,UriageNO ) as UriageData 
				group by 計上日 order by 計上日 desc";
			$aryColumn = ["計上日","Event/店舗","客単価"];
			$chart_type="bar";
		}elseif($analysis_type==7){//イベント・店舗別客単価ランキング
			$sqlstr = "SELECT ROW_NUMBER() OVER (ORDER BY (SELECT NULL)) AS UKEY, KYAKU as Labels,ROUND(avg(客単価)) as datasets from 
				(SELECT UriDate as 計上日 ,concat(Event,TokuisakiNM) as KYAKU ,UriageNO ,sum(UriageKin) as 客単価 from UriageData ".$sql_where_IN." group by UriDate,concat(Event,TokuisakiNM),UriageNO ) as UriageData
				 group by KYAKU order by avg(客単価) desc";
			$aryColumn = ["Event/店舗","客単価"];
			$chart_type="bar";
			$top15="on";
		}elseif($analysis_type==8){//イベント・店舗別来客数推移
			$sqlstr = "SELECT ROW_NUMBER() OVER (ORDER BY (SELECT NULL)) AS UKEY, UriDate as 計上日, Event as Labels, sum(来客カウント) as datasets from 
				(SELECT uid, UriDate, Event, TokuisakiNM, UriageNO,0 as ShouhinCD, 1 as 来客カウント from UriageData where Event <>'' group by uid,UriDate,Event,TokuisakiNM,UriageNO) as UriageData".$sql_where_IN." 
				group by UriDate,Event order by UriDate desc";
			$aryColumn = ["計上日","Event/店舗","来客数"];
			$chart_type="bar";
			
			$tokui="xxxx";//来客数の場合は個別売りを除く
		}elseif($analysis_type==9){//イベント・店舗別来客数ランキング
			$sqlstr = "SELECT ROW_NUMBER() OVER (ORDER BY (SELECT NULL)) AS UKEY, Event as Labels,ROUND(avg(来客数)) as datasets from (SELECT UriDate,sum(来客カウント) as 来客数,Event from (SELECT uid, UriDate, Event, TokuisakiNM, UriageNO,0 as ShouhinCD, 1 as 来客カウント from UriageData where Event <>'' group by uid,UriDate,Event,TokuisakiNM,UriageNO) as UriageData ".$sql_where_IN." group by UriDate,Event) as Urisum2 group by Event order by ROUND(avg(来客数)) desc";
			$aryColumn = ["Event/店舗","平均来客数"];
			$chart_type="bar";
			
			$tokui="xxxx";//来客数の場合は個別売りを除く
			$top15="on";
		}elseif($analysis_type==10){//（時間帯別売上実績）
			$sqlstr = "SELECT ROW_NUMBER() OVER (ORDER BY (SELECT NULL)) AS UKEY,concat(time_format(insDatetime,'%H'), '時') as Hour,ShouhinNM as NAME,sum(su) as COUNT from UriageData ".$sql_where_OUT." 
				group by ShouhinNM,time_format(insDatetime,'%H') 
				order by time_format(insDatetime,'%H'),ShouhinNM";
			$aryColumn = ["時間帯","商品名","個数"];
			$chart_type="line";
			
			$tokui="xxxx";//時間別推移の場合は個別売りを除く
		}elseif($analysis_type==11){//平均来客数推移
			$Event = empty($_POST["event"])?"'-'":"Event";
			$sqlstr = "SELECT tmp2.Event as NAME ,tmp2.Hour as Hour,ROUND(avg(tmp2.cnt),1) as COUNT from (
				SELECT ".$Event." as Event,tmp.UriDate,Hour,sum(tmp.man) as cnt from (
						SELECT ".$Event." as Event,UriDate,concat(time_format(insDatetime,'%H'), '時') as Hour,UriageNO,1 as man 
						from UriageData ".$sql_where_OUT." 
						group by ".$Event.",UriDate,concat(time_format(insDatetime,'%H'), '時'),UriageNO
						) as tmp 
					group by tmp.Event,tmp.UriDate,tmp.Hour
					) as tmp2
				group by tmp2.Event,tmp2.Hour
				order by tmp2.Event,tmp2.Hour";
			$aryColumn = ["イベント名","時間帯","人数"];
			$chart_type="line";
			
			$tokui="xxxx";//時間別推移の場合は個別売りを除く
		}elseif($analysis_type==12){//ジャンル別実績
			if($category_lv==="0"){
				$sql_category = "if(bunrui1<>'',bunrui1,'未分類')";
				$category="%";
			}elseif($category_lv==="1"){
				$sql_category = "concat(if(bunrui1<>'',bunrui1,'未分類'),'>',if(bunrui2<>'',bunrui2,'未分類'))";
				$category = $_POST["over_category"]."%";
			}elseif($category_lv==="2"){
				$sql_category = "concat(if(bunrui1<>'',bunrui1,'未分類'),'>',if(bunrui2<>'',bunrui2,'未分類'),'>',if(bunrui3<>'',bunrui3,'未分類'))";
				$category = $_POST["over_category"]."%";
				//$category_lv=-1;
			}else{
				$sql_category = "";
				$sql_category_where="";
			}
			$sql_category_where = " AND ".$sql_category." LIKE '".$category."'";
			/*$sqlstr = "SELECT ".$sql_category." as Labels,sum(UriageKin) as datasets from UriageData 
				inner join ShouhinMS 
				on UriageData.uid=ShouhinMS.uid 
				and UriageData.shouhinCD=ShouhinMS.shouhinCD "
				.$sql_where_OUT.$sql_category_where." 
				group by ".$sql_category." 
				order by sum(UriageKin) desc";*/
				$sqlstr = "SELECT ".$sql_category." as Labels,sum(UriageKin) as datasets,sum(UriageKin)-sum(genka) as arari from UriageMeisai as UriageData"
				.$sql_where_OUT.$sql_category_where." 
				group by ".$sql_category." 
				order by sum(UriageKin) desc";
			$aryColumn = ["カテゴリー","売上","粗利"];
			$chart_type="doughnut";
			$grafu_discription = "円グラフをタップすると、タップしたジャンルの下のレベルが表示されます。";
		}elseif($analysis_type==13){//abc分析(全体)
			$sqlstr = "SELECT tmp.* ,truncate(100 * (税抜売上 / (sum(税抜売上) over())),1) as 売上占有率 from 
				(SELECT ShouhinNM as ShouhinNM ,sum(UriageKin) as 税抜売上 from UriageData ".$sql_where_OUT." group by ShouhinNM) tmp 
				order by 税抜売上 desc";
			$aryColumn = ["商品名","売上","占有率","Rank"];
			$chart_type="";
		}elseif($analysis_type==14){//abc分析(Event別)
			$sqlstr = "SELECT tmp.* ,truncate(100 * (税抜売上 / (sum(税抜売上) over(PARTITION BY Event))),1) as 売上占有率 from 
				(SELECT concat(Event,TokuisakiNM) as Event,ShouhinNM as ShouhinNM ,sum(UriageKin) as 税抜売上 from UriageData ".$sql_where_OUT." group by Event,ShouhinNM) tmp 
				order by Event,税抜売上 desc";
			$aryColumn = ["商品名","売上","占有率","Rank"];
			$chart_type="";
		}else if($analysis_type==='Ev_Avr_uri_rank'){//ｲﾍﾞﾝﾄ別平均売上ランキング
			$sqlstr = "SELECT Ev as Labels,ROUND(avg(TotalUri),0) as datasets ,ROUND(avg(TotalUri - TotalGenka),0) as datasets2
			FROM (
				SELECT UriDate, concat(Event,TokuisakiNM) as Ev,sum(UriageKin) as TotalUri ,sum(su * genka_tanka) as TotalGenka
				from `UriageData` ".$sql_where_IN." 
				group by UriDate,concat(Event,TokuisakiNM)
				) as A 
			group by Ev order by datasets desc";
			$aryColumn = ["Event/店舗","平均売上額","平均粗利"];
			$chart_type="bar";
		}else if($analysis_type==='Area_tanka_1'){//エリア別客単価
			$sqlstr = "SELECT MUNI as Labels,ROUND(AVG(Uriage)) as datasets from UriageData_GioWeather A inner join ( SELECT uid ,UriageNO ,sum(UriageKin) as Uriage from UriageData ".$sql_where_IN." group by uid ,UriageNO ) B on A.uid = B.uid and A.UriNo = B.UriageNO and MUNI > 0 group by MUNI order by AVG(Uriage) desc";
			$aryColumn = ["エリア","客単価"];
			$chart_type="bar";
			$tokui="xxxx";//エリア別客単価の場合は個別売りを除く
		}else if($analysis_type==='Area_tanka_2'){//エリア別客単価
			$sqlstr = "SELECT CONCAT(MUNI,',',address) AS Labels,ROUND(AVG(Uriage)) as datasets from UriageData_GioWeather A inner join ( SELECT uid ,UriageNO ,sum(UriageKin) as Uriage from UriageData ".$sql_where_IN." group by uid ,UriageNO ) B on A.uid = B.uid and A.UriNo = B.UriageNO and MUNI > 0 group by MUNI ,address order by AVG(Uriage) desc";
			$aryColumn = ["エリア","客単価"];
			$chart_type="bar";
			$tokui="xxxx";//エリア別客単価の場合は個別売りを除く
		}else if($analysis_type==='urikire'){//売切れ実績
			$sqlstr = "SELECT B.UriDate,B.Event,B.ShouhinNM,A.shuppin_su,B.売切日時 
				FROM `UriageDataSummary` A 
				inner join ( 
					select uid,UriDate,Event,ShouhinCD,ShouhinNM,LEFT(TIME(max(insDatetime)),5) as 売切日時 from UriageData ".$sql_where_OUT." 
					group by uid,UriDate,ShouhinCD,ShouhinNM,Event ) B 
				on A.uid = B.uid 
				and A.UriDate = B.UriDate 
				and A.ShouhinCD = B.ShouhinCD 
				and A.ShouhinNM = B.ShouhinNM 
				and A.Event = B.Event 
				WHERE zan_su = 0 and shuppin_su <> 0 
				ORDER BY `B`.`UriDate` DESC,A.Event;";
			$aryColumn = ["日付","Event","商品","出品","完売"];
			$chart_type="-";
			$tokui="xxxx";//売切れ実績の場合は個別売りを除く
		}

		log_writer2($myname." [Exc sql] =>",$sqlstr,"lv3");
		//log_writer2($myname." [\$_POST] =>",$_POST,"lv3");
		
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
				if($analysis_type==6 ||$analysis_type==8){
					foreach($result as $row){
						$labels[$i] = [$row["計上日"],(mb_strwidth($row["Labels"])<=12)?$row["Labels"]:mb_strimwidth($row["Labels"],0,12)."…"];
						$data[$i] = $row["datasets"];
						$i++;
					}
				}else{
					foreach($result as $row){
						$labels[$i] = (mb_strwidth($row["Labels"])<=12)?$row["Labels"]:mb_strimwidth($row["Labels"],0,12)."…";
						$labels_long[$i] = $row["Labels"];
						$data[$i] = $row["datasets"];
						$i++;
					}
				}
			}else if($chart_type==="line"){
				$i=0;
				$h=0;
				foreach($result as $row){
					//log_writer2($myname." \$row[NAME] =>",$row["NAME"],"lv3");
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
						//log_writer2($myname." \$row[Hour] =>",substr($row["Hour"],0,2),"lv3");
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
				//log_writer2($myname." \$xEndpoint =>",$xEndpoint,"lv3");
							

				for(;$h<24;$h++){//24時まで0埋めする
					$data[$i][$h] = 0;
				}
				$xEndpoint=($xEndpoint + 1<=23)?$xEndpoint+1:$xEndpoint;
				$xStartpoint=($xStartpoint - 1 >=0)?$xStartpoint-1:$xStartpoint;

				//log_writer2($myname." \$xEndpoint =>",$xEndpoint,"lv3");
							
			}else{
				//グラフデータ不要
			}
			
			if($analysis_type==13){//ABC分析
				$par = 0;
				$i = 0;
				foreach($result as $row){
					if($par < 70){
						$result[$i]["rank"]="A";
					}elseif($par < 90){
						$result[$i]["rank"]="B";
					}else{
						$result[$i]["rank"]="C";
					}
					$par += $row["売上占有率"];
					$i++;
				}
			}
			if($analysis_type==14){//ABC分析
				$par = 0;
				$i = 0;
				foreach($result as $row){
					if($par < 70){
						$result[$i]["rank"]="A";
					}elseif($par < 90){
						$result[$i]["rank"]="B";
					}else{
						$result[$i]["rank"]="C";
					}
					$par += $row["売上占有率"];
					$i++;
					if($result[$i]["Event"] !== $result[$i-1]["Event"]){
						$par = 0;
					}
				}
			}

			$msg = "取得成功。";
			$alert_status = "alert-success";
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
	,"labels_long" => $labels_long
	,"data" => $data
	,"chart_type" => $chart_type
	,"top15" => $top15
	,'xStart' => $xStartpoint
	,'xEnd' => $xEndpoint
	,'grafu_discription' => $grafu_discription
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