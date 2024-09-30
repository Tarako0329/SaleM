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
/*
	$rtn=session_set_cookie_params(24*60*60*24*3,'/','.'.MAIN_DOMAIN,true);
	if($rtn==false){
			log_writer2("php_header.php","ERROR:[session_set_cookie_params] が FALSE を返しました。","lv0");
			echo "システムエラー発生。システム管理者へ通知しました。";
			//共通ヘッダーでのエラーのため、リダイレクトTOPは実行できない。
			exit();
	}
	session_start();
*/	
	$pdo_h = new PDO(DNS, USER_NAME, PASSWORD, get_pdo_options());

	if(empty($_GET)){
		echo "想定外アクセス。";
		exit();
	}
	//$id=rot13decrypt2($_GET["i"]);
	$uid=($_GET["i"]);
	$from = (!empty($_GET["from"])?$_GET["from"] :"2023-01-01");
	$to = (!empty($_GET["to"])?$_GET["to"] :"2023-12-31");
	$filename = "Uriage_meisai";
}
use Dompdf\Dompdf;



$sysname="WEBREZ+";

//売上明細の取得

	$sql="select * from uriagedenpyou where uid = :uid and 計上日 between :from and :to";
	$stmt = $pdo_h->prepare($sql);
	$stmt->bindValue("uid", $uid, PDO::PARAM_INT);
	$stmt->bindValue("from", $from, PDO::PARAM_STR);
	$stmt->bindValue("to", $to, PDO::PARAM_STR);
	$stmt->execute();
	$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
	//log_writer2("\$result",$result,"lv3");
	$meisai = "";
	foreach($result as $row){
		$meisai .= "<tr><td>".$row["計上日"]."</td><td>".$row["売上NO"]."</td><td>".$row["売上先"]."</td><td>".$row["商品"]."</td><td>".number_format($row["数"])."</td><td>".number_format($row["単価"])."</td><td>".number_format($row["金額"])."</td><td>".number_format($row["税額"])."</td><td>".$row["区分"]."</td></tr>\r\n";
	}
	//log_writer2("\$meisai",$meisai,"lv3");
	/*
	<!--<tr><td>伝票合計</td><td>$row["売上NO"]</td><td>$row["売上先"]</td><td>$row["商品"]</td><td>$row["数"]</td><td>$row["単価"]</td><td>$row["金額"]</td><td>$row["区分"]</td>
	<tr><td>日計</td><td>$row["売上NO"]</td><td>$row["売上先"]</td><td>$row["商品"]</td><td>$row["数"]</td><td>$row["単価"]</td><td>$row["金額"]</td><td>$row["区分"]</td>
	<tr><td>月計</td><td>$row["売上NO"]</td><td>$row["売上先"]</td><td>$row["商品"]</td><td>$row["数"]</td><td>$row["単価"]</td><td>$row["金額"]</td><td>$row["区分"]</td>
	<tr><td>年計</td><td>$row["売上NO"]</td><td>$row["売上先"]</td><td>$row["商品"]</td><td>$row["数"]</td><td>$row["単価"]</td><td>$row["金額"]</td><td>$row["区分"]</td>-->
	*/

// PDFにする内容をHTMLで記述
$html = <<< EOM
<html>
	<head>
		<meta charset='utf-8'>
		<style>
			html{
				font-family:ipagp;
			}
			div{
				border:0;
				width:100%;
				text-align: center;
				padding:5px auto;
			}
			p{
				margin-top:5px;
				margin-bottom:0px;
			}
			table{
				margin: 0 auto;
				border:solid;
				border-collapse: collapse;
				font-size:10px;
			}
			th{
				border-bottom:solid;
				border-right:solid 0.5px;
				border-collapse: collapse;
				padding:auto 5px;
			}
			td{
				border:solid 0.5px;
				border-collapse: collapse;
				padding:auto 5px;
			}
			.meisaival{
				width:80px;
				min-width:50px;
				text-align: right;
				padding:auto 5px;
			}
			.title{
				font-size:30px;
				font-weight: bolder;
				border-top: 4px solid;
				border-bottom: 4px solid;
			}
			.Seikyu{
				font-size:25px;
				font-weight: bolder;
			}
		</style>
	</head>
	<body>
		<div style='text-align:left;'>
			powered by <span style='font-family:Kranky;font-weight: bolder;'>$sysname</span>
		</div>
		<div style='height:70px;'>
			<span class='title'> - 売上明細 - </span>
		</div>
		<div style='margin-top:15px;'>
			<span style='font-size:20px;'>【 内　訳 】</span>
			<table style='width:100%;'>
				<thead>
					<tr>
					<th>計上日</th>
					<th>売上NO</th>
					<th>売上先</th>
					<th>商品名</th>
					<th>数</th>
					<th>単価</th>
					<th>本体金額</th>
					<th>消費税</th>
					<th>税区分</th>
					</tr>
				</thead>
				<tbody>
				$meisai
				</tbody>
			</table>
		</div>
	</body>
</html>
EOM;
$html = str_replace(["\r","\n","\t"],"",$html);//改行・タブの削除
try{
	/*
	$sqllog="";
	if($saiban==="on"){
		$pdo_h->beginTransaction();
		$sqllog .= rtn_sqllog("START TRANSACTION",[]);
		$sql = "insert into ryoushu(uid,R_NO,UriNO,Atena,html,QR_GUID) values(?,?,?,?,?,?)";
		$stmt = $pdo_h->prepare($sql);
		$stmt->bindValue(1, $id, PDO::PARAM_INT);
		$stmt->bindValue(2, $RyoushuuNO, PDO::PARAM_INT);
		$stmt->bindValue(3, $UriNo, PDO::PARAM_INT);
		$stmt->bindValue(4, $Atena, PDO::PARAM_STR);
		$stmt->bindValue(5, $html, PDO::PARAM_STR);
		$stmt->bindValue(6, $qr_GUID, PDO::PARAM_STR);

		$status = $stmt->execute();
		$sqllog .= rtn_sqllog($sql,[$id,$RyoushuuNO,$UriNo,$Atena,$html,$qr_GUID]);

		$pdo_h->commit();
		$sqllog .= rtn_sqllog("commit",[]);
		sqllogger($sqllog,0);
	}
	*/
	// PDFの設定～出力
	output($html,$filename);
	
}catch(Exception $e){
	//$pdo_h->rollBack();
	//$sqllog .= rtn_sqllog("rollBack",[]);
	sqllogger($sqllog,$e);
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