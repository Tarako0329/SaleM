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
	//$answer_type = $_POST["answer_type"] ?? 'plain';   //json or html or plain(そのまま)
	$subject = $_POST["subject"] ?? ''; //会話のテーマ($_SESSION[$subject]に会話履歴を保存)

	$params['uid'] = $_SESSION['user_id'];

	/*以下リストに合わせて$params['from_d']$params['to_d']を設定
	'ウィークリーレポート (先週)',
	'月次レポート (先月)',
	'月次レポート (先月と今月)', 
	'年次レポート (昨年)', 
	'年次レポート (昨年と今年)',
	'直近１２ヵ月レポート', 
	'過去５年と今後の見通し', 
	*/
	switch ($_POST['data_range']) {
		case 'ウィークリーレポート (先週)':
			$params['from_d'] = date('Y-m-d', strtotime('last week monday'));
			$params['to_d'] = date('Y-m-d', strtotime('last week sunday'));
			break;
		case '月次レポート (先月)':
			$params['from_d'] = date('Y-m-01', strtotime('first day of last month'));
			$params['to_d'] = date('Y-m-t', strtotime('last day of last month'));
			break;
		case '月次レポート (先月と今月)':
			$params['from_d'] = date('Y-m-01', strtotime('first day of last month'));
			$params['to_d'] = date('Y-m-t');
			break;
		case '年次レポート (昨年)':
			$params['from_d'] = date('Y-01-01', strtotime('-1 year'));
			$params['to_d'] = date('Y-12-31', strtotime('-1 year'));
			break;
		case '年次レポート (昨年と今年)':
			$params['from_d'] = date('Y-01-01', strtotime('-1 year'));
			$params['to_d'] = date('Y-12-31');
			break;
		case '直近１２ヵ月レポート':
			$params['from_d'] = date('Y-m-01', strtotime('-11 months'));
			$params['to_d'] = date('Y-m-t');
			break;
		case '過去５年と今後の見通し':
			$params['from_d'] = date('Y-01-01', strtotime('-4 years'));
			$params['to_d'] = date('Y-12-31', strtotime('+1 year')); // 来年末まで
			break;
		default:
			// デフォルトは直近12ヶ月
			$params['from_d'] = date('Y-m-01', strtotime('-11 months'));
			$params['to_d']= date('Y-m-t');
			break;
	}

	//Feed_Gemini_Report.phpからデータを取得
	$url = ROOT_URL."Feed_Gemini_Report.php?uid=" . $_SESSION['user_id'] . "&from_d=" . $params['from_d'] . "&to_d=" . $params['to_d'];
	$json = file_get_contents($url);
	$data = json_decode($json, true);


	$user_input .= "\売上分析用データをJSONで提供します。\n\n" . $json;
	

	
	if($_POST["save_setting"]==="true"){
		//ajax_delins_business_info.phpに保存
		//ビジネス情報とプロンプトを保存
		try {
			$pdo_h->beginTransaction();

			//$params["uid"]=$_SESSION['user_id'];
			$params["ai_role"]=$_POST['ai_role'];
			$params["data_range"]=$_POST['data_range'];
			$params["your_ask"]=TRIM($_POST['your_ask']);
			$params["report_type"]=TRIM($_POST['report_type']);
			
			$sqlstr="DELETE from analysis_ai_setting where uid=:uid";
			$stmt = $pdo_h->prepare($sqlstr);
			$stmt->bindValue("uid", $params['uid'], PDO::PARAM_INT);
			$sqllog .= rtn_sqllog($sqlstr,$params);
			$stmt->execute();
			$sqllog .= rtn_sqllog("--execute():正常終了",[]);
	
			$sqlstr="INSERT into analysis_ai_setting(uid,ai_role,data_range,your_ask,report_type) values(:uid,:ai_role,:data_range,:your_ask,:report_type)";
			$stmt = $pdo_h->prepare($sqlstr);
			$stmt->bindValue("uid", $params['uid'], PDO::PARAM_INT);
			$stmt->bindValue("ai_role", $params["ai_role"], PDO::PARAM_STR);
			$stmt->bindValue("data_range", $params["data_range"], PDO::PARAM_STR);
			$stmt->bindValue("your_ask", $params["your_ask"], PDO::PARAM_STR);
			$stmt->bindValue("report_type", $params["report_type"], PDO::PARAM_STR);
			$sqllog .= rtn_sqllog($sqlstr,$params);
			$stmt->execute();
			$sqllog .= rtn_sqllog("--execute():正常終了",[]);
			$pdo_h->commit();
			$sqllog .= rtn_sqllog("commit",[]);
			sqllogger($sqllog,0);
		} catch (Exception $e) {
			$pdo_h->rollBack();
			log_writer2(basename(__FILE__)." [Exception \$e] =>",$e,"lv0");
		}
	}
	
	if(EXEC_MODE<>"Local"){
		$textParts = [
			['text' => $user_input]
		];
		$token_count = countGeminiTokensWithCurl($textParts);
		$msg = gemini_api($user_input,"html",$response_schema);
		//$msgに$token_countを追加
		$msg["token_count"] = $token_count;
	}

	//$answer_type=htmlの場合、$msg["result"]をファイルに上書きで出力する。ファイル名は$_SESSION["user_id"]+_gemini_report.html
	$report_file = $_SESSION["user_id"]."_gemini_report.html";
	file_put_contents($report_file, $msg["result"]);
	//レポートのURLを$urlにセット
	$url = ROOT_URL.$report_file;

	//レポートを作成しました。こちらから確認してください。のHTMLメール用データを$mail_bodyにセット
	$mail_body = "レポートを作成しました。こちらから確認してください。<br><a href='". $url ."'>".$url."</a>";
	
	send_htmlmail($_POST["mail"],$_POST['data_range'],$mail_body);
	if(EXEC_MODE==="Test"){
		send_htmlmail($_POST["mail"],"user_input",$user_input);
	}
}
//log_writer2("\$msg",$msg,"lv3");
//$token = csrf_create();

header('Content-type: application/json');
echo json_encode($msg, JSON_UNESCAPED_UNICODE);
//echo $msg;

exit();

?>