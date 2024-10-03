<?php
//QRからアクセスされた場合は領収書発行済みとして処理する。
//プレビューの場合、発行済みとするチェックの有無で判断する。
{
	date_default_timezone_set('Asia/Tokyo');
	require "./vendor/autoload.php";
	require_once "functions.php";
	
	//.envの取得
	$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
	$dotenv->load();

	define("EXEC_MODE",$_ENV["EXEC_MODE"]);
	/*if(EXEC_MODE==="Local"){
			ini_set('error_log', 'C:\xampp\htdocs\SaleM\php_error.log');
	}*/
	log_writer2("\$GET",$_GET,"lv3");

	define("MAIN_DOMAIN",$_ENV["MAIN_DOMAIN"]);
	//DB接続関連
	define("DNS","mysql:host=".$_ENV["SV"].";dbname=".$_ENV["DBNAME"].";charset=utf8");
	define("USER_NAME", $_ENV["DBUSER"]);
	define("PASSWORD", $_ENV["PASS"]);

	define("TITLE", $_ENV["TITLE"]);

	//メール送信関連
	define("HOST", $_ENV["HOST"]);
	define("PORT", $_ENV["PORT"]);
	define("FROM", $_ENV["FROM"]);
	define("PROTOCOL", $_ENV["PROTOCOL"]);
	define("POP_HOST", $_ENV["POP_HOST"]);
	define("POP_USER", $_ENV["POP_USER"]);
	define("POP_PASS", $_ENV["POP_PASS"]);

	//システム通知
	define("SYSTEM_NOTICE_MAIL",$_ENV["SYSTEM_NOTICE_MAIL"]);

	$rtn=session_set_cookie_params(24*60*60*24*3,'/','.'.MAIN_DOMAIN,true);
	if($rtn==false){
			//echo "ERROR:session_set_cookie_params";
			log_writer2("php_header.php","ERROR:[session_set_cookie_params] が FALSE を返しました。","lv0");
			echo "システムエラー発生。システム管理者へ通知しました。";
			//共通ヘッダーでのエラーのため、リダイレクトTOPは実行できない。
			exit();
	}
	session_start();
	$pdo_h = new PDO(DNS, USER_NAME, PASSWORD, get_pdo_options());

	if(empty($_GET)){
		echo "想定外アクセス。";
		exit();
	}
	//$id=rot13decrypt2($_GET["i"]);
	$id=($_GET["i"]);
	//$UriNo=rot13decrypt2($_GET["u"]);
	$RNo=($_GET["r"]);
}


$sysname="WEBREZ+";	

$sql="select html from ryoushu where uid=? and R_NO=?";
$stmt = $pdo_h->prepare($sql);
$stmt->bindValue(1, $id, PDO::PARAM_INT);
$stmt->bindValue(2, $RNo, PDO::PARAM_INT);
$stmt->execute();
$data = $stmt->fetch(PDO::FETCH_ASSOC);
log_writer2("\$data",$data["html"],"lv3");

//$html = str_replace(["\r","\n","\t"],"",$html);//改行・タブの削除
try{
	// PDFの設定～出力
	output($data["html"],"Ryoushusho");
	
}catch(Exception $e){
	echo "システム不具合が発生したため、領収書が発行できませんでした。<br>";
	echo "システム管理者に不具合発生を通知いたしました。<br>";
	echo "ご迷惑をおかけいたしますが、復旧までお待ちください。<br>";
	echo "<button onclick='window.close()'>戻る</button>\n";
}

function output($html,$filename){
	$dompdf = new Dompdf();
	$dompdf->loadHtml($html);
	$options = $dompdf->getOptions();
	$options->set(array('isRemoteEnabled' => false));
	$dompdf->setOptions($options);
	$dompdf->setPaper('A4', 'portrait');
	$dompdf->render();
	$dompdf->stream($filename, array('Attachment' => 0));
}
?>