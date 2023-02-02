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

function param_clear(){
	$_SESSION["Uridate2"]="%";
	$_SESSION["Event"]="%";
	$_SESSION["UriNO"]="%";
	$_SESSION["shouhinCD"]="%";
	$_SESSION["shouhinNM"]="%";
	$_SESSION["wheresql"]="";
}

log_writer2("ajax_get_Uriage2.php",$_POST,"lv3");

{//パラメータセット
	//POST
	if(!empty($_POST)){
		$_SESSION["UriFrom"]    =(empty($_POST["UriDateFrom"])?(string)date("Y-m-d"):$_POST["UriDateFrom"]);
		$_SESSION["UriTo"]      =(empty($_POST["UriDateTo"])?(string)date("Y-m-d"):$_POST["UriDateTo"]);
		$_SESSION["Event"]      =(empty($_POST["Event"])?"%":$_POST["Event"]);
		$Type = (empty($_POST["Type"])?"":$_POST["Type"]);
		
		//検索モーダル使用時は絞り込みを解除
		$_SESSION["Uridate2"]="%";
		$_SESSION["UriNO"]="%";
		$_SESSION["shouhinCD"]="%";
		
		//更新条件を保存
		/*
		$_SESSION["chk_uridate"]      =(empty($_POST["chk_uridate"])?"":$_POST["chk_uridate"]);
		$_SESSION["up_uridate"]      =(empty($_POST["up_uridate"])?"":$_POST["up_uridate"]);
		$upd_msg = $upd_msg.(empty($_POST["up_uridate"])?"":"売上日：".$_POST["up_uridate"]."<br>");
		$_SESSION["chk_event"]      =(empty($_POST["chk_event"])?"":$_POST["chk_event"]);
		$_SESSION["up_event"]      =(empty($_POST["up_event"])?"":$_POST["up_event"]);
		$upd_msg = $upd_msg.(empty($_POST["up_event"])?"":"イベント名：".$_POST["up_event"]."<br>");
		$_SESSION["chk_kokyaku"]      =(empty($_POST["chk_kokyaku"])?"":$_POST["chk_kokyaku"]);
		$_SESSION["up_kokyaku"]      =(empty($_POST["up_kokyaku"])?"":$_POST["up_kokyaku"]);
		$upd_msg = $upd_msg.(empty($_POST["up_kokyaku"])?"":"顧客名：".$_POST["up_kokyaku"]."<br>");
		$_SESSION["chk_urikin"]      =(empty($_POST["chk_urikin"])?"":$_POST["chk_urikin"]);
		$_SESSION["up_zeikbn"]      =(empty($_POST["up_zeikbn"])?"":$_POST["up_zeikbn"]);
		$_SESSION["up_uritanka"]      =(empty($_POST["up_uritanka"])?"":$_POST["up_uritanka"]);
		$_SESSION["up_zei"]      =(empty($_POST["up_zei"])?"":$_POST["up_zei"]);
		$upd_msg = $upd_msg.(empty($_POST["chk_urikin"])?"":"商品単価(税込)：".($_POST["up_uritanka"]+$_POST["up_zei"])."(".$_POST["up_uritanka"]." + ".$_POST["up_zei"].")<br>");
		$_SESSION["chk_genka"]      =(empty($_POST["chk_genka"])?"":$_POST["chk_genka"]);
		$_SESSION["up_urigenka"]      =(empty($_POST["up_urigenka"])?"":$_POST["up_urigenka"]);
		$upd_msg = $upd_msg.(empty($_POST["chk_genka"])?"":"原価単価：".$_POST["up_urigenka"]."<br>");
		*/
	}
	//GET
	if(!empty($_GET)){
		//初回アクセスはGETで来るので日付に今日をセット
		if(!empty($_GET["first"])){
			$_SESSION["MSG"]="本日の売上";
			$_SESSION["UriFrom"]=date("Y-m-d");
			$_SESSION["UriTo"]=date("Y-m-d");
			$_SESSION["UriageData_Correct_mode"]="false";

			param_clear();
		}
		$Type=(empty($_GET["Type"])?"rireki":$_GET["Type"]);
		
		if((empty($_GET["display"])?"":$_GET["display"])=="all"){
			$_SESSION["UriFrom"]="2000-01-01";
			$_SESSION["UriTo"]="2099-12-31";

			param_clear();
		}
		//日付＞イベント＞商品・売上No
		if(!empty($_GET["ad1"])){
			$_SESSION["Uridate2"]=rot13decrypt2($_GET["ad1"]);
			$_SESSION["Event"]="%";
			$_SESSION["UriNO"]="%";
			$_SESSION["shouhinCD"]="%";
		}
		if(!empty($_GET["ad2"])){
			$_SESSION["Event"]=rot13decrypt2($_GET["ad2"]);
			$_SESSION["UriNO"]="%";
			$_SESSION["shouhinCD"]="%";
		}
		if(!empty($_GET["ad3"])){$_SESSION["UriNO"]=rot13decrypt2($_GET["ad3"]);}
		if(!empty($_GET["ad4"])){$_SESSION["shouhinCD"]=rot13decrypt2($_GET["ad4"]);}
		if(!empty($_GET["ad5"])){$_SESSION["shouhinNM"]=rot13decrypt2($_GET["ad5"]);}

		//deleteモード
		$_SESSION["urino"]=(empty($_GET["urino"])?"":$_GET["urino"]);
		$_SESSION["cd"]=(empty($_GET["cd"])?"":$_GET["cd"]);
	}
	//SESSION
	$_SESSION["UriageData_Correct_mode"]=(empty($_SESSION["UriageData_Correct_mode"])?"false":$_SESSION["UriageData_Correct_mode"]);
}
//var_dump($_SESSION);





$wheresql="where uid = :user_id AND UriDate >= :UriDate AND UriDate <= :UriDateTo and concat(Event,TokuisakiNM) like :Event ";  //検索モーダル部
$wheresql=$wheresql."AND UriDate like :UriDate2 AND UriageNO like :UriNO AND ShouhinCD like :shouhinCD ";    //絞り込み対応部

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

//削除した後に表示する履歴のwhere文をセッションに保存
$_SESSION["wheresql"]="where uid = :user_id AND UriDate >= '".$_SESSION["UriFrom"]."' AND UriDate <= '".$_SESSION["UriTo"]."' and concat(Event,TokuisakiNM) like '".$_SESSION["Event"]."' ";
$_SESSION["wheresql"]=$_SESSION["wheresql"]."AND UriDate like '".$_SESSION["Uridate2"]."' AND UriageNO like '".$_SESSION["UriNO"]."' AND ShouhinCD like '".$_SESSION["shouhinCD"]."' ";

$stmt = $pdo_h->prepare( $sql );
$stmt->bindValue("UriDate", $_SESSION["UriFrom"], PDO::PARAM_STR);
$stmt->bindValue("UriDateTo", $_SESSION["UriTo"], PDO::PARAM_STR);
$stmt->bindValue("Event", $_SESSION["Event"], PDO::PARAM_STR);
$stmt->bindValue("UriDate2", $_SESSION["Uridate2"], PDO::PARAM_STR);
$stmt->bindValue("UriNO", $_SESSION["UriNO"], PDO::PARAM_INT);
$stmt->bindValue("shouhinCD", $_SESSION["shouhinCD"], PDO::PARAM_INT);

$stmt->bindValue("user_id", $_SESSION["user_id"], PDO::PARAM_INT);
$rtn=$stmt->execute();
if($rtn==false){
	deb_echo("失敗した場合は不正値が渡されたとみなし、wheresqlを破棄<br>");
	$_SESSION["wheresql"]="";
}
$UriageList = $stmt->fetchAll();
$rowcnt = $stmt->rowCount();
if($rowcnt!==0){
}else{
	if($Type=="rireki"){
		$Type="sum_items";
	}elseif($Type=="sum_items"){
		$Type="sum_events";
	}
}



// ヘッダーを指定することによりjsonの動作を安定させる
header('Content-type: application/json');
// htmlへ渡す配列$productListをjsonに変換する
echo json_encode($UriageList, JSON_UNESCAPED_UNICODE);
?>


