<?php
require "php_header.php";
register_shutdown_function('shutdown_ajax',basename(__FILE__));

$msg["result"] = "ローカルテストは空よん";                          //ユーザー向け処理結果メッセージ
log_writer2("\$_POST",$_POST,"lv3");


$rtn = csrf_checker(["analysis_ai_menu.php"]);
if($rtn !== true){
	$msg=$rtn;
	$alert_status = "alert-warning";
	$reseve_status = true;
}else{
	$user_input = $_POST["Article"] ?? '';
	$type = $_POST["type"] ?? 'kaiwa';   //連続会話(kaiwa) or 一問一答(one)
	$answer_type = $_POST["answer_type"] ?? 'plain';   //json or html or plain(そのまま)
	$subject = $_POST["subject"] ?? ''; //会話のテーマ($_SESSION[$subject]に会話履歴を保存)
	
	//売上明細データを取得
	$sql="SELECT 
		UriageNO as 売上番号
		,UriDate as 売上計上日
		,Event as 売上計上イベント名
		,TokuisakiNM as イベント以外の売上先
		,ShouhinNM as 商品名
		,su as 売上個数
		,tanka as 売上単価
		,UriageKin as 売上金額
		,genka_tanka as 原価単価
		,genka as 売上原価
		,IFNULL(bunrui1,'未設定') as 商品分類大
		,IFNULL(bunrui2,'未設定') as 商品分類中
		,IFNULL(bunrui3,'未設定') as 商品分類小
		,address as イベント開催住所
		,weather as 売上時の天気
		,weather_discription as 売上時の天気詳細
		,temp as 売上時の気温
		,feels_like as 売上時の体感温度
		from UriageMeisai 
		where uid=? and UriDate between ? and ?
	";
	

	//UriageMeisaiから売上計上日、イベント、得意先、商品を集計キーとした売上集計データを取得するSQL文
	//気温は平均。天気は最大カウントを取る天気
	$sql_sum = "SELECT
		UriDate as 売上計上日
		,Event as 売上計上イベント名
		,TokuisakiNM as イベント以外の売上先
		,ShouhinNM as 商品名
		,sum(su) as 合計売上個数
		,avg(tanka) as 平均売上単価
		,sum(UriageKin) as 合計売上金額
		,avg(genka_tanka) as 平均原価単価
		,sum(genka) as 合計売上原価
		,IFNULL(bunrui1,'未設定') as 商品分類大
		,IFNULL(bunrui2,'未設定') as 商品分類中
		,IFNULL(bunrui3,'未設定') as 商品分類小
		,address as イベント開催住所
		, (SELECT weather FROM UriageMeisai WHERE UriDate = U.UriDate GROUP BY weather ORDER BY COUNT(*) DESC LIMIT 1) AS 売上時の天気
		, (SELECT weather_discription FROM UriageMeisai WHERE UriDate = U.UriDate GROUP BY weather_discription ORDER BY COUNT(*) DESC LIMIT 1) AS 売上時の天気詳細
		,ROUND(avg(temp),1) as 売上時の平均気温
		,ROUND(avg(feels_like),1) as 売上時の平均体感温度
		from UriageMeisai U
		where uid=? and UriDate between ? and ?
		group by UriDate,Event,TokuisakiNM,ShouhinNM,bunrui1,bunrui2,bunrui3,address
		order by UriDate desc
	";


	if($_POST["data_range"]==="過去５年の売上データをもとに"){
		$stmt = $pdo_h->prepare($sql_sum);
		$stmt->bindValue(1, $_SESSION['user_id'], PDO::PARAM_INT);
		$stmt->bindValue(2, date('Y-m-d', strtotime('-5 year')), PDO::PARAM_STR);
		$stmt->bindValue(3, date('Y-m-d'), PDO::PARAM_STR);
	}else if($_POST["data_range"]==="直近１２ヵ月の売上データをもとに"){
		$stmt = $pdo_h->prepare($sql);
		$stmt->bindValue(1, $_SESSION['user_id'], PDO::PARAM_INT);
		$stmt->bindValue(2, date('Y-m-d', strtotime('-12 month')), PDO::PARAM_STR);
		$stmt->bindValue(3, date('Y-m-d'), PDO::PARAM_STR);
	}else if($_POST["data_range"]==="今年の売上データをもとに"){
		$stmt = $pdo_h->prepare($sql);
		$stmt->bindValue(1, $_SESSION['user_id'], PDO::PARAM_INT);
		$stmt->bindValue(2, date('Y-01-01'), PDO::PARAM_STR);
		$stmt->bindValue(3, date('Y-12-31'), PDO::PARAM_STR);
	}
	
	$stmt->execute();
	$shouhin_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

	//$user_inputに売上明細をJSONで追記
	$user_input .= "\n売上明細は次の通り。\n" . json_encode($shouhin_rows, JSON_UNESCAPED_UNICODE);
	
	if($_POST["save_setting"]==="true"){
		//ajax_delins_business_info.phpに保存
		//ビジネス情報とプロンプトを保存
		$sqlstr="DELETE from analysis_ai_setting where uid=:uid";
		$stmt = $pdo_h->prepare($sqlstr);
		$stmt->bindValue("uid", $_SESSION['user_id'], PDO::PARAM_INT);
		$stmt->execute();

		$sqlstr="INSERT into analysis_ai_setting(uid,ai_role,data_range,your_ask,report_type) values(:uid,:ai_role,:data_range,:your_ask,:report_type)";
		$stmt = $pdo_h->prepare($sqlstr);
		$stmt->bindValue("uid", $_SESSION['user_id'], PDO::PARAM_INT);
		$stmt->bindValue("ai_role", $_POST["ai_role"], PDO::PARAM_STR);
		$stmt->bindValue("data_range", $_POST["data_range"], PDO::PARAM_STR);
		$stmt->bindValue("your_ask", $_POST["your_ask"], PDO::PARAM_STR);
		$stmt->bindValue("report_type", $_POST["report_type"], PDO::PARAM_STR);
		$stmt->execute();

		
	}
	
	//$msg = gemini_api($user_input,$answer_type,$response_schema);

	//$answer_type=htmlの場合、$msg["result"]をファイルに上書きで出力する。ファイル名は$_SESSION["user_id"]+_gemini_report.html
	if($answer_type==="html"){
		$report_file = $_SESSION["user_id"]."_gemini_report.html";
		file_put_contents($report_file, $msg["result"]);
		send_htmlmail("r.tamura-az@azuma.agns.co.jp","report",$msg["result"]);
	}
	
}
//log_writer2("\$msg",$msg,"lv3");
//$token = csrf_create();

header('Content-type: application/json');
echo json_encode($msg, JSON_UNESCAPED_UNICODE);
//echo $msg;

exit();

?>