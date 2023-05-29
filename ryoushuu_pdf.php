<?php
date_default_timezone_set('Asia/Tokyo');
require "./vendor/autoload.php";
require_once "functions.php";

//.envの取得
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

define("EXEC_MODE",$_ENV["EXEC_MODE"]);
if(EXEC_MODE==="Local"){
		ini_set('error_log', 'C:\xampp\htdocs\SaleM\php_error.log');
}

define("MAIN_DOMAIN",$_ENV["MAIN_DOMAIN"]);
//DB接続関連
define("DNS","mysql:host=".$_ENV["SV"].";dbname=".$_ENV["DBNAME"].";charset=utf8");
define("USER_NAME", $_ENV["DBUSER"]);
define("PASSWORD", $_ENV["PASS"]);
$pdo_h = new PDO(DNS, USER_NAME, PASSWORD, get_pdo_options());

define("TITLE", $_ENV["TITLE"]);

use Dompdf\Dompdf;

if(empty($_GET)){
	echo "想定外アクセス。";
	exit();
}
$id=rot13decrypt2($_GET["i"]);
$UriNo=rot13decrypt2($_GET["u"]);
$Atena = (!empty($_GET["s"])?$_GET["s"] . " " . $_GET["k"]:"");
$type = ($_GET["tp"]==="1"?"領　収　書":"請　求　書");

//$id=2;
//$UriNo="206";

$sysname="WEBREZ+";

$sql="select * from Users where uid = ?";
$stmt = $pdo_h->prepare($sql);
$stmt->bindValue(1, $id, PDO::PARAM_INT);
$stmt->execute();
$userinfo = $stmt->fetch(PDO::FETCH_ASSOC);
$from = (!empty($userinfo["yagou"])?$userinfo["yagou"]:$userinfo["name"]);
$invoice = $userinfo["invoice_no"];
$add = $userinfo["address1"].$userinfo["address2"].$userinfo["address3"];

//売上明細の取得
$sql="select *,ZeiMS.hyoujimei as 税率desp,ZeiMS.zeiritu as 税率 from UriageData Uri inner join ZeiMS on Uri.zeiKBN = ZeiMS.zeiKBN where uid = ? and UriageNO like ?";
$stmt = $pdo_h->prepare($sql);
$stmt->bindValue(1, $id, PDO::PARAM_INT);
$stmt->bindValue(2, $UriNo, PDO::PARAM_STR);
$stmt->execute();
$result = $stmt->fetchAll();

$i=0;
$Goukei=0;
foreach($result as $row){
	if($i===0){
		$UriageDate = (string)$row["UriDate"];
		$insDT = (string)$row["insDatetime"];
		$EvName = $row["Event"];
		$TkName = $row["TokuisakiNM"];
	}//
	$meisai .= "<tr><td style='text-align:left;'>".($row["zeiKBN"]=="1001"?"※":"").$row["ShouhinNM"]."</td><td class='meisaival'>".number_format($row["su"]);
	$meisai .= "</td><td class='meisaival'>".number_format($row["tanka"])."</td><td class='meisaival'>".number_format($row["UriageKin"])."</td></tr>\n";
	$Goukei += $row["UriageKin"];
	$i++;
}


//税率ごとの合計
$sql="select ZeiMS.hyoujimei as 税率,ZeiMS.zeiritu, sum(UriageKin) as 売上金額 from UriageData Uri inner join ZeiMS on Uri.zeiKBN = ZeiMS.zeiKBN where uid = ? and UriageNO like ? group by ZeiMS.hyoujimei,ZeiMS.zeiritu order by ZeiMS.zeiritu desc";
$stmt = $pdo_h->prepare($sql);
$stmt->bindValue(1, $id, PDO::PARAM_INT);
$stmt->bindValue(2, $UriNo, PDO::PARAM_STR);
$stmt->execute();
$result = $stmt->fetchAll();
$ZeiGoukei = 0;
foreach($result as $row){
	$zeigaku = round($row["売上金額"] * $row["zeiritu"] / 100);
	$ZeiKei .= "<tr><td style='width:30%;'>".$row["税率"]."対象</td><td style='text-align:right;width:30%;'>￥".number_format($row["売上金額"])."-</td><td style='width:20%;'>消費税</td><td style='text-align:right;width:20%;'>￥".number_format($zeigaku)."-</td></tr>\n";
	$ZeiGoukei += $zeigaku;
}
$Goukei = $Goukei+$ZeiGoukei;
$ZeiGoukei = number_format($ZeiGoukei);
$Goukei = number_format($Goukei);

// PDFにする内容をHTMLで記述
$html = <<< EOM
<html>
	<head>
		<meta charset="utf-8">
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
				width:50px;
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
		</div>
		<div style='text-align:left;font-size:25px;'>
			<span style='border-bottom:solid;'>$Atena</span>
		</div>
		<div style='text-align:right;'>
			<p style='font-size:25px;'>$from</p>
			<p>$invoice</p>
			<p>$add</p>
			<p>取引日時[$insDT]</p>
			<p>伝票番号[$UriNo]</p>
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

// PDFの設定～出力
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$options = $dompdf->getOptions();
$options->set(array('isRemoteEnabled' => false));
$dompdf->setOptions($options);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream(".pdf", array("Attachment" => 0));
?>