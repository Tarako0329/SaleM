<?php
/*
*本日の売上データを取得
*params:POST
*   user_id     ：ログインユーザID
*   orderby     ：
*   list_type   ：
*   serch_word  ：
*/
require "php_header.php";

log_writer2("ajax_get_Uriage2.php",$_POST,"lv3");

{//パラメータセット
	//POST
	if(!empty($_POST)){
		$UriFrom    =(empty($_POST["UriDateFrom"])?(string)date("Y-m-d"):$_POST["UriDateFrom"]);
		$UriTo      =(empty($_POST["UriDateTo"])?(string)date("Y-m-d"):$_POST["UriDateTo"]);
		//$_SESSION["Event"]      =(empty($_POST["Event"])?"%":$_POST["Event"]);
		$Type = (empty($_POST["Type"])?"":$_POST["Type"]);
		
	}
	//GET

	//SESSION
	//$_SESSION["UriageData_Correct_mode"]=(empty($_SESSION["UriageData_Correct_mode"])?"false":$_SESSION["UriageData_Correct_mode"]);
}

//絞り込みをjsで行うため、検索条件を減らす
//$wheresql="where uid = :user_id AND UriDate >= :UriDate AND UriDate <= :UriDateTo and concat(Event,TokuisakiNM) like :Event ";  //検索モーダル部
$wheresql="where uid = :user_id AND UriDate >= :UriDate AND UriDate <= :UriDateTo ";  //検索モーダル部
//$wheresql=$wheresql."AND UriDate like :UriDate2 AND UriageNO like :UriNO AND ShouhinCD like :shouhinCD ";    //絞り込み対応部

if($Type=="rireki"){
	//履歴明細取得
	$sql = "select U.*,IFNULL(UGW.icon,'0') as icon,UGW.temp,UGW.description, max(UGW.temp) OVER (PARTITION BY U.uid,U.UriDate,U.Event) as max_temp, min(UGW.temp) OVER (PARTITION BY U.uid,U.UriDate,U.Event) as min_temp ";
	$sql = $sql."from (select * ,su*genka_tanka as genka,UriageKin-(su*genka_tanka) as arari from UriageData ".$wheresql.") as U ";
	$sql = $sql."left join UriageData_GioWeather as UGW on U.uid = UGW.uid and U.UriageNO = UGW.UriNo order by U.UriDate desc,U.Event,U.UriageNO";
	
}elseif($Type=="sum_items"){
	//商品単位で集計
	$sql="select UriDate,UriageNO,Event,TokuisakiNM, ShouhinCD, ShouhinNM,shuppin_su,uri_su as su,zan_su, tanka,UriageKin,zei,genka,arari,icon,max_temp,min_temp from UriageDataSummary ";
	$sql = $sql.$wheresql."order by UriDate desc,Event,TokuisakiNM,ShouhinNM ";
	
}elseif($Type=="sum_events"){
	//イベント単位で集計
	$_SESSION["MSG"]="";
	$sql = "select U.UriDate,'-' as UriageNO,U.Event,U.TokuisakiNM,'-' as ShouhinCD,'-' as ShouhinNM,0 as su,0 as tanka,sum(U.UriageKin) as UriageKin,sum(U.zei) as zei,sum(U.su*U.genka_tanka) as genka,sum(U.UriageKin-(U.su*U.genka_tanka)) as arari ";
	$sql = $sql.",max(IFNULL(UGW.icon,'0')) as icon,max(UGW.temp) as max_temp,min(UGW.temp) as min_temp ";
	$sql = $sql."from (select * from UriageData ".$wheresql.") as U left join UriageData_GioWeather as UGW on U.uid = UGW.uid and U.UriageNO = UGW.UriNo ";
	$sql = $sql." group by U.UriDate,U.Event,U.TokuisakiNM order by U.UriDate desc,U.Event,U.TokuisakiNM";
}

$stmt = $pdo_h->prepare( $sql );
$stmt->bindValue("UriDate", $UriFrom, PDO::PARAM_STR);
$stmt->bindValue("UriDateTo", $UriTo, PDO::PARAM_STR);
/*
$stmt->bindValue("Event", $_SESSION["Event"], PDO::PARAM_STR);
$stmt->bindValue("UriDate2", $_SESSION["Uridate2"], PDO::PARAM_STR);
$stmt->bindValue("UriNO", $_SESSION["UriNO"], PDO::PARAM_INT);
$stmt->bindValue("shouhinCD", $_SESSION["shouhinCD"], PDO::PARAM_INT);
*/
$stmt->bindValue("user_id", $_SESSION["user_id"], PDO::PARAM_INT);
$rtn=$stmt->execute();
if($rtn==false){
	deb_echo("失敗した場合は不正値が渡されたとみなし、wheresqlを破棄<br>");
	//$_SESSION["wheresql"]="";
}
$UriageList = $stmt->fetchAll();
$rowcnt = $stmt->rowCount();
if($rowcnt!==0){
}else{
}



// ヘッダーを指定することによりjsonの動作を安定させる
header('Content-type: application/json');
// htmlへ渡す配列$productListをjsonに変換する
echo json_encode($UriageList, JSON_UNESCAPED_UNICODE);
?>


