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
	$id=($_GET["i"]);
	//$UriNo=rot13decrypt2($_GET["u"]);
	$UriNo=($_GET["u"]);
	$Atena = (!empty($_GET["s"])?$_GET["s"] . "　　" . $_GET["k"]:"");
	$type = ($_GET["tp"]==="1"?"領　収　書":"請　求　書");
	$filename = ($_GET["tp"]==="1"?"Ryoushusho":"Seikyusho");
	$qr_GUID=(!empty($_GET["qr"])?$_GET["qr"]:null);
	$saiban=(!empty($_GET["sb"])?$_GET["sb"]:null);
}
use Dompdf\Dompdf;

if(!empty($qr_GUID)){
	$sql = "select * from ryoushu where QR_GUID = ?";
	$stmt = $pdo_h->prepare($sql);
	$stmt->bindValue(1, $qr_GUID, PDO::PARAM_STR);
	$stmt->execute();
	$ryoushu_info = $stmt->fetch(PDO::FETCH_ASSOC);
	$html = $ryoushu_info["html"];
	if(empty($html)){
		$saiban="on";
	}else{
		output($html,$filename);
		exit();
	}
}

$sysname="WEBREZ+";

//$sql="select * from Users where uid = ?";
$sql="select * from Users_webrez where uid = ?";
$stmt = $pdo_h->prepare($sql);
$stmt->bindValue(1, $id, PDO::PARAM_INT);
$stmt->execute();
$userinfo = $stmt->fetch(PDO::FETCH_ASSOC);
$from = (!empty($userinfo["yagou"])?$userinfo["yagou"]:$userinfo["name"]);
$invoice = $userinfo["invoice_no"];
$add = $userinfo["address1"].$userinfo["address2"].$userinfo["address3"];
$inquiry = (!empty($userinfo["inquiry_tel"])?$userinfo["inquiry_tel"]:"")."/".$userinfo["inquiry_mail"];

//売上明細の取得
{
	$sql="select *,ZeiMS.hyoujimei as 税率desp,ZeiMS.zeiritu as 税率 from UriageData Uri inner join ZeiMS on Uri.zeiKBN = ZeiMS.zeiKBN where uid = ? and UriageNO like ? and ShouhinCD not like 'Z%' order by Uri.zeiKBN,Uri.ShouhinCD";
	$stmt = $pdo_h->prepare($sql);
	$stmt->bindValue(1, $id, PDO::PARAM_INT);
	$stmt->bindValue(2, $UriNo, PDO::PARAM_STR);
	$stmt->execute();
	$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

	$i=0;
	$Goukei=0;
	$meisai="";
	$ZeiKei="";

	foreach($result as $row){
		if($i===0){
			$UriageDate = (string)$row["UriDate"];
			$insDT = (string)$row["insDatetime"];
			$EvName = $row["Event"];
			$TkName = $row["TokuisakiNM"];
		}
		if(substr($row["ShouhinCD"],0,1)!=="C"){
			$meisai .= "<tr><td style='text-align:left;'>".($row["zeiKBN"]=="1001"?"※": ($row["zeiKBN"]=="0"?"(非課税)":"")).$row["ShouhinNM"]."</td><td class='meisaival'>".number_format($row["su"]);
			$meisai .= "</td><td class='meisaival'>".number_format($row["tanka"])."</td><td class='meisaival'>".number_format($row["UriageKin"])."</td></tr>\n";
	
		}else{
			$meisai .= "<tr><td style='text-align:left;'>".($row["zeiKBN"]=="1001"?"※": "").$row["ShouhinNM"]."</td><td class='meisaival'>".number_format($row["su"]);
			$meisai .= "</td><td class='meisaival'>".number_format($row["tanka"])."</td><td class='meisaival'>".number_format($row["UriageKin"])."</td></tr>\n";
	
		}
		$Goukei += $row["UriageKin"];
		$i++;
	}
}

//税率ごとの合計
{
	$sql="select ZeiMS.hyoujimei as 税率,ZeiMS.zeiKBN, sum(UriageKin) as 売上金額, sum(zei) as 消費税額 from UriageData Uri inner join ZeiMS on Uri.zeiKBN = ZeiMS.zeiKBN where uid = ? and UriageNO like ? group by ZeiMS.	hyoujimei,ZeiMS.zeiKBN order by ZeiMS.zeiKBN";
	$stmt = $pdo_h->prepare($sql);
	$stmt->bindValue(1, $id, PDO::PARAM_INT);
	$stmt->bindValue(2, $UriNo, PDO::PARAM_STR);
	$stmt->execute();
	$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
	$ZeiGoukei = 0;
	foreach($result as $row){
		$zeigaku = $row["消費税額"] ;
		$ZeiKei .= "<tr><td style='width:30%;'>".$row["税率"]."対象</td><td style='text-align:right;width:30%;'>￥".number_format($row["売上金額"])."-</td><td style='width:20%;'>消費税</td><td style='text-align:right;width:20%;'>	￥".number_format($zeigaku)."-</td></tr>\n";
		$ZeiGoukei += $zeigaku;
	}
	$Goukei = $Goukei+$ZeiGoukei;
	$ZeiGoukei = number_format($ZeiGoukei);
	$Goukei = number_format($Goukei);
}
$message="";
if($saiban==="on"){//領収書Noの取得
	
	$sql = "select max(R_NO) as R_NO from ryoushu where uid = ? group by uid";
	$stmt = $pdo_h->prepare($sql);
	$stmt->bindValue(1, $id, PDO::PARAM_INT);
	$stmt->execute();
	$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
	$count = $stmt->rowCount();

	if(empty($result[0]["R_NO"])){
		$RyoushuuNO = 1;
	}else{
		$RyoushuuNO = $result[0]["R_NO"] + 1;
	}
}else{
	$RyoushuuNO = "xxxxx";
	$message = "<br><span style='font-size:12px;'>この領収書は確認表示のため、お客様に発行できません。</span>";
}
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
			<span class='title'> - $type - </span>
			$message
		</div>
		<div style='text-align:left;font-size:25px;'>
			<span style='border-bottom:solid;'>$Atena</span>
		</div>
		<div style='text-align:right;'>
			<p style='font-size:25px;'>$from</p>
			<p>$invoice</p>
			<p>$add</p>
			<p>取引日時[$insDT]</p>
			<p>伝票番号[$UriNo/$RyoushuuNO]</p>
		</div>
		<div class='Seikyu' style='display:flex;'>
			<table style='width:100%;'>
				<tr>
				<td style='width:30%;'>税込合計金額</td><td style='text-align:right;width:30%;'>￥$Goukei-</td>
				<td style='width:20%;'>内消費税</td><td style='text-align:right;width:20%;'>￥$ZeiGoukei-</td>
				</tr>
			</table>
		</div>
			<table style='width:100%;'>
				$ZeiKei
			</table>
		<div style='margin-top:15px;'>
			<span style='font-size:20px;'>【 内　訳 】</span>
			<table style='width:100%;'>
				<thead>
					<tr>
					<th>商品名</th>
					<th>数</th>
					<th>単価</th>
					<th>金額</th>
					</tr>
				</thead>
				<tbody>
				$meisai
				</tbody>
			</table>
		</div>
		<div style='text-align:left;'>
			※：軽減税率８％対象
		</div>
		
	</body>
</html>
EOM;
$html = str_replace(["\r","\n","\t"],"",$html);//改行・タブの削除
try{
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
	// PDFの設定～出力
	output($html,$filename);
	
}catch(Exception $e){
	$pdo_h->rollBack();
	$sqllog .= rtn_sqllog("rollBack",[]);
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