<?php
chdir(__DIR__);
echo "処理を開始します。\n";

date_default_timezone_set('Asia/Tokyo');
require "../vendor/autoload.php";
require "../functions.php";


//.envの取得
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();
//var_dump($_ENV);
define("DNS","mysql:host=".$_ENV["SV"].";dbname=".$_ENV["DBNAME"].";charset=utf8");
define("USER_NAME", $_ENV["DBUSER"]);
define("PASSWORD", $_ENV["PASS"]);

define("HOST", $_ENV["HOST"]);
define("PORT", $_ENV["PORT"]);
define("FROM", $_ENV["FROM"]);
define("PROTOCOL", $_ENV["PROTOCOL"]);
define("POP_HOST", $_ENV["POP_HOST"]);
define("POP_USER", $_ENV["POP_USER"]);
define("POP_PASS", $_ENV["POP_PASS"]);

define("EXEC_MODE",$_ENV["EXEC_MODE"]);

define("MAIN_DOMAIN",$_ENV["MAIN_DOMAIN"]);
if(!empty($_SERVER['SCRIPT_URI'])){
	define("ROOT_URL",substr($_SERVER['SCRIPT_URI'],0,mb_strrpos($_SERVER['SCRIPT_URI'],"/")+1));
}else{
	define("ROOT_URL","http://".MAIN_DOMAIN."/");
}

define("GEMINI",$_ENV["GOOGLE_API"]);
define("GEMINI_URL",$_ENV["GEMINI_URL"]);
define("GEMINI_URL_TOKEN",$_ENV["GEMINI_URL_TOKEN"]);

$msg["result"] = "ローカルテストは空よん";                          //ユーザー向け処理結果メッセージ
//log_writer2("\$_POST",$_POST,"lv3");


$rtn = csrf_checker(["analysis_ai_menu.php"]);
if($rtn !== true){
	$msg=$rtn;
	$alert_status = "alert-warning";
	$reseve_status = true;
}else{
	$user_input = $_POST["Article"] ?? '';
	$type = 'kaiwa';   //連続会話(kaiwa) or 一問一答(one)
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
	$report_type = $_POST['report_name'];
	
	$url = ROOT_URL."Feed_Gemini_Report.php?uid=" . $_SESSION['user_id'] . "&report_type=" . $report_type ;
	log_writer2("\$url",$url,"lv3");
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
			$params["report_name"]=$_POST['report_name'];
			$params["your_ask"]=TRIM($_POST['your_ask']);
			$params["report_type"]=TRIM($_POST['report_type']);
			
			$sqlstr="DELETE from analysis_ai_setting where uid=:uid and report_name=:report_name";
			$stmt = $pdo_h->prepare($sqlstr);
			$stmt->bindValue("uid", $params['uid'], PDO::PARAM_INT);
			$stmt->bindValue("report_name", $params['report_name'], PDO::PARAM_STR);
			$sqllog .= rtn_sqllog($sqlstr,$params);
			$stmt->execute();
			$sqllog .= rtn_sqllog("--execute():正常終了",[]);
	
			$sqlstr="INSERT into analysis_ai_setting(uid,ai_role,report_name,your_ask,report_type) values(:uid,:ai_role,:report_name,:your_ask,:report_type)";
			$stmt = $pdo_h->prepare($sqlstr);
			$stmt->bindValue("uid", $params['uid'], PDO::PARAM_INT);
			$stmt->bindValue("ai_role", $params["ai_role"], PDO::PARAM_STR);
			$stmt->bindValue("report_name", $params["report_name"], PDO::PARAM_STR);
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
		$report_file = $_SESSION["user_id"]."_gemini_report.html";

		$textParts = [
			['text' => $user_input]
		];
		$token_count = countGeminiTokensWithCurl($textParts);
		
		$msg = gemini_api_kaiwa($user_input,"html","AI_report");
		//$msgに$token_countを追加
		$msg["token_count"] = $token_count;
		//$msg["result"]の最後の改行を削除して$html_codeにセット
		//$html_code = trim($msg["result"]);
		file_put_contents($report_file,trim($msg["result"]));
		
		log_writer2("\$msg['result']",$msg["emsg"],"lv3");

		//$msg["finishReason"]!=="finished"の場合、$user_inputに"続きを出力してください"をセットし、"finished"が返ってくるまでgemini_api_kaiwa($user_input,"html","AI_report")を繰り返す
		//繰り返しの上限は3回まで
		
		$retry_count = 0;
		while ($msg["finishReason"] <> "finished" && $retry_count < 5) {
			$user_input = "続きを出力してください";
			$msg = gemini_api_kaiwa($user_input, "html", "AI_report");
			log_writer2("\$msg['result']",$msg["emsg"],"lv3");
			//$html_code .= trim($msg["result"]);
			file_put_contents($report_file, trim($msg["result"]),FILE_APPEND );
			$retry_count++;
		}
		//gemini_api_kaiwaでhtmlのチェック
		
		//log_writer2("\$_SESSION['AI_report']",$_SESSION["AI_report"],"lv3");
		//会話履歴をリセット
		$_SESSION["AI_report"] = [];
		//log_writer2("\$_SESSION['AI_report']",$_SESSION["AI_report"],"lv3");
		$msg["retry_times"] = $retry_count;
		
	}

	//$answer_type=htmlの場合、$msg["result"]をファイルに上書きで出力する。ファイル名は$_SESSION["user_id"]+_gemini_report.html
	//$report_file = $_SESSION["user_id"]."_gemini_report.html";
	
	//file_put_contents($report_file, $html_code,FILE_APPEND | LOCK_EX);
	//レポートのURLを$urlにセット
	$url = ROOT_URL.$report_file;

	//レポートを作成しました。こちらから確認してください。のHTMLメール用データを$mail_bodyにセット
	if($msg["emsg"]<>""){
		$mail_body = $msg["emsg"];
	}else{
		$mail_body = "レポートを作成しました。こちらから確認してください。<br><a href='". $url ."'>".$url."</a>";
	}
	send_htmlmail($_POST["mail"],$_POST['title'],$mail_body);
	if(EXEC_MODE==="Test"){
		//send_htmlmail($_POST["mail"],"user_input",$user_input);
	}
}
echo "処理が終了しました。\n";

?>




















