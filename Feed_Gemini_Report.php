<?php
require "php_header.php";

/*売上明細を取得し、AIで売上分析するために必要な統計データを連想配列で提供します。

	売上明細を取得するSQL文は
	  "SELECT 
		DATE_FORMAT(UriDate, '%Y') as 売上計上年
		,DATE_FORMAT(UriDate, '%Y-%m') as 売上計上年月
		,UriDate as 売上計上年月日
		,Event as 売上計上イベント名
		,TokuisakiNM as イベント以外の売上先
		,UriageNO as 売上番号
		,ShouhinNM as 商品名
		,su as 売上個数
		,UriageKin as 売上金額
		,genka as 売上原価
		,CONCAT(IFNULL(bunrui1,'未設定'),IFNULL(bunrui2,'未設定') ,IFNULL(bunrui3,'未設定')) as 商品分類
		,address as イベント開催住所
		,weather as 売上時の天気
		,CAST(ROUND(temp,1) as CHAR) as 売上時の気温
		from UriageMeisai 
		where uid=:uid and UriDate between :from_d and :to_d";
	です。uidとfrom_dとto_dは$_GETから取得します。

*/
//GET変数取得
$uid = $_GET["uid"];
//$from_d = $_GET["from_d"];
//$to_d = $_GET["to_d"];
$report_type = $_GET["report_type"];

switch ($report_type) {
	case 'weekly':
		$from_d = date('Y-m-d', strtotime('last week monday'));
		$to_d = date('Y-m-d', strtotime('last week sunday'));
		$report_type = "weekly";
		break;
	case 'monthly':
		$from_d = date('Y-m-01', strtotime('first day of last month'));
		$to_d = date('Y-m-t', strtotime('last day of last month'));
		$report_type = "monthly";
		break;
	case 'monthly2':
		$from_d = date('Y-m-01', strtotime('first day of last month'));
		$to_d = date('Y-m-t');
		$report_type = "monthly2";
		break;
	case 'yearly':
		$from_d = date('Y-01-01', strtotime('-1 year'));
		$to_d = date('Y-12-31', strtotime('-1 year'));
		$report_type = "yearly";
		break;
	case 'yearly2':
		$from_d = date('Y-01-01', strtotime('-1 year'));
		$to_d = date('Y-12-31');
		$report_type = "yearly2";
		break;
	case '12month':
		$from_d = date('Y-m-01', strtotime('-11 months'));
		$to_d = date('Y-m-t');
		$report_type = "12month";
		break;
	case '5years':
		$from_d = date('Y-01-01', strtotime('-4 years'));
		$to_d = date('Y-12-31', strtotime('+1 year')); // 来年末まで
		$report_type = "5years";
		break;
	default:
		// デフォルトは直近12ヶ月
		$from_d = date('Y-m-01', strtotime('-11 months'));
		$to_d= date('Y-m-t');
		break;
}

//期間中の総売上を取得
$sql = "SELECT sum(UriageKin) as total_sales, sum(genka) as total_cost from UriageMeisai where uid=:uid and UriDate between :from_d and :to_d";
$stmt = $pdo_h->prepare($sql);
$stmt->bindValue("uid", $uid, PDO::PARAM_INT);
$stmt->bindValue("from_d", $from_d, PDO::PARAM_STR);
$stmt->bindValue("to_d", $to_d, PDO::PARAM_STR);
$stmt->execute();
$total_sales_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

//期間中の総売上と総粗利
$total_sales_summary = [
    '総売上金額' => $total_sales_data[0]['total_sales'],
    '総売上原価' => $total_sales_data[0]['total_cost'],
    '総粗利金額' => $total_sales_data[0]['total_sales'] - $total_sales_data[0]['total_cost']
];

//年毎の売上粗利を集計し年度昇順でソート
if ($report_type ==="5years") {
	//昨年末までの年間売上
	$sql = "SELECT 
		DATE_FORMAT(UriDate, '%Y') as 売上計上年
		,sum(UriageKin) as 売上金額
		,sum(UriageKin)-sum(genka) as 粗利
		from UriageMeisai 
		where uid=:uid and UriDate between :from_d and :to_d
		group by 売上計上年
		order by 売上計上年 asc";

	$stmt = $pdo_h->prepare($sql);
	$stmt->bindValue("uid", $uid, PDO::PARAM_INT);
	$stmt->bindValue("from_d", $from_d, PDO::PARAM_STR);
	$stmt->bindValue("to_d", date('Y-12-31', strtotime('-1 year'), PDO::PARAM_STR));
	$stmt->execute();
	$yearly_sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

	//今年の月別売上
	$sql = "SELECT 
		DATE_FORMAT(UriDate, '%Y-%m') as 売上計上年月
		,sum(UriageKin) as 売上金額
		,sum(UriageKin)-sum(genka) as 粗利
		from UriageMeisai 
		where uid=:uid and UriDate between :from_d and :to_d
		group by 売上計上年月
		order by 売上計上年月 asc";

	$stmt = $pdo_h->prepare($sql);
	$stmt->bindValue("uid", $uid, PDO::PARAM_INT);
	$stmt->bindValue("from_d", date('Y-01-01'), PDO::PARAM_STR);
	$stmt->bindValue("to_d", $to_d, PDO::PARAM_STR);
	$stmt->execute();
	$monthly_sales_this_year = $stmt->fetchAll(PDO::FETCH_ASSOC);

}else{
	$yearly_sales = "なし";
	$monthly_sales_this_year = "なし";
}

//月ごとの売上・粗利の集計。売上計上年月を昇順でソート
if (in_array($report_type,["yearly","yearly2","12month"])) {
	
	$sql = "SELECT 
		DATE_FORMAT(UriDate, '%Y-%m') as 売上計上年月
		,sum(UriageKin) as 売上金額
		,sum(UriageKin)-sum(genka) as 粗利
		from UriageMeisai 
		where uid=:uid and UriDate between :from_d and :to_d
		group by 売上計上年月
		order by 売上計上年月 asc";

	$stmt = $pdo_h->prepare($sql);
	$stmt->bindValue("uid", $uid, PDO::PARAM_INT);
	$stmt->bindValue("from_d", $from_d, PDO::PARAM_STR);
	$stmt->bindValue("to_d", $to_d, PDO::PARAM_STR);
	$stmt->execute();
	$monthly_sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
}else{
	$monthly_sales = "なし";
}

//日ごとの売上・粗利の集計。売上計上年月日を昇順でソート
if (in_array($report_type,["weekly","monthly","monthly2"])) {
	$sql = "SELECT 
		UriDate as 売上計上年月日
		,sum(UriageKin) as 売上金額
		,sum(UriageKin)-sum(genka) as 粗利
		from UriageMeisai 
		where uid=:uid and UriDate between :from_d and :to_d
		group by 売上計上年月日
		order by 売上計上年月日 asc";

	$stmt = $pdo_h->prepare($sql);
	$stmt->bindValue("uid", $uid, PDO::PARAM_INT);
	$stmt->bindValue("from_d", $from_d, PDO::PARAM_STR);
	$stmt->bindValue("to_d", $to_d, PDO::PARAM_STR);
	$stmt->execute();
	$daily_sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
}else{
	$daily_sales = "なし";
}

//商品分類ごとの売上・粗利の集計。商品分類を昇順でソート。未分類は最後尾に表示。
$sql = "SELECT 
	CONCAT(IFNULL(bunrui1,'未設定'),'>',IFNULL(bunrui2,'未設定')) as 大中分類
	,IFNULL(bunrui3,'未設定') as 小分類
	,sum(UriageKin) as 売上金額
	,sum(UriageKin)-sum(genka) as 粗利
	from UriageMeisai 
	where uid=:uid and UriDate between :from_d and :to_d
	group by 大中分類,小分類
	order by 
		CASE 
			WHEN bunrui1 = '' THEN 1 
			ELSE 0 
		END,
		bunrui1 ASC,
		CASE 
			WHEN bunrui2 = '' THEN 1 
			ELSE 0 
		END,
		bunrui2 ASC,
		CASE 
			WHEN bunrui3 = '' THEN 1 
			ELSE 0 
		END,
		bunrui3 ASC";

$stmt = $pdo_h->prepare($sql);
$stmt->bindValue("uid", $uid, PDO::PARAM_INT);
$stmt->bindValue("from_d", $from_d, PDO::PARAM_STR);
$stmt->bindValue("to_d", $to_d, PDO::PARAM_STR);
$stmt->execute();
$category_sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

//ABC分析
$sql = "SELECT 
	ShouhinNM as 商品名
	,sum(UriageKin) as 売上金額
	,sum(UriageKin)-sum(genka) as 粗利
	from UriageMeisai 
	where uid=:uid and UriDate between :from_d and :to_d
	group by 商品名
	order by 売上金額 desc";

$stmt = $pdo_h->prepare($sql);
$stmt->bindValue("uid", $uid, PDO::PARAM_INT);
$stmt->bindValue("from_d", $from_d, PDO::PARAM_STR);
$stmt->bindValue("to_d", $to_d, PDO::PARAM_STR);
$stmt->execute();
$abc_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_sales = array_sum(array_column($abc_data, '売上金額'));
$cumulative_sales = 0;
foreach ($abc_data as $key => $row) {
    $cumulative_sales += $row['売上金額'];
    $percentage = ($total_sales > 0) ? ($cumulative_sales / $total_sales) * 100 : 0;
    //$abc_data[$key]['累積売上金額'] = $cumulative_sales;
    $abc_data[$key]['累積構成比'] = round($percentage, 2);
    if ($percentage <= 40) {
        $abc_data[$key]['ABCランク'] = 'A+';
    } elseif ($percentage <= 60) {
        $abc_data[$key]['ABCランク'] = 'A';
    } elseif ($percentage <= 70) {
        $abc_data[$key]['ABCランク'] = 'A-';
    } elseif ($percentage <= 80) {
        $abc_data[$key]['ABCランク'] = 'B+';
    } elseif ($percentage <= 85) {
        $abc_data[$key]['ABCランク'] = 'B';
    } elseif ($percentage <= 90) {
        $abc_data[$key]['ABCランク'] = 'B-';
    } elseif ($percentage <= 95) {
        $abc_data[$key]['ABCランク'] = 'C+';
    } elseif ($percentage <= 98) {
        $abc_data[$key]['ABCランク'] = 'C';
    } else {
        $abc_data[$key]['ABCランク'] = 'C-';
    }
}
//$abc_dataの累積構成比を文字列にキャスト
foreach ($abc_data as $key => $row) {
    $abc_data[$key]['累積構成比'] = (string)$row['累積構成比'];
}


//イベント１日ごとの売上を集計し、平均売上金額トップ１０を取得。順位もつける
$sql = "SELECT 
	ROW_NUMBER() OVER (ORDER BY avg(売上金額) DESC) as 順位
	,Event as イベント名
	,CAST(ROUND(avg(売上金額), 0) as CHAR) as 平均売上
	,CAST(ROUND(avg(粗利), 0) as CHAR) as 平均粗利
	from (SELECT Event,UriDate,sum(UriageKin) as 売上金額,sum(genka) as 売上原価,sum(UriageKin)-sum(genka) as 粗利 from UriageMeisai where uid=:uid and UriDate between :from_d and :to_d group by Event,UriDate) as A 
	group by イベント名
	order by avg(売上金額) desc
	limit 10";

$stmt = $pdo_h->prepare($sql);
$stmt->bindValue("uid", $uid, PDO::PARAM_INT);
$stmt->bindValue("from_d", $from_d, PDO::PARAM_STR);
$stmt->bindValue("to_d", $to_d, PDO::PARAM_STR);
$stmt->execute();
$event_sales = $stmt->fetchAll(PDO::FETCH_ASSOC);



///イベント１日ごとの売上を集計し、平均売上金額ワースト５を取得。順位もつける
$sql = "SELECT 
	ROW_NUMBER() OVER (ORDER BY avg(売上金額) ASC) as 順位
	,Event as イベント名
	,CAST(ROUND(avg(売上金額), 0) as CHAR) as 平均売上
	,CAST(ROUND(avg(粗利), 0) as CHAR) as 平均粗利
	from (SELECT Event,UriDate,sum(UriageKin) as 売上金額,sum(genka) as 売上原価,sum(UriageKin)-sum(genka) as 粗利 from UriageMeisai where uid=:uid and UriDate between :from_d and :to_d group by Event,UriDate) as A 
	group by イベント名
	order by avg(売上金額) asc
	limit 5";

$stmt = $pdo_h->prepare($sql);
$stmt->bindValue("uid", $uid, PDO::PARAM_INT);
$stmt->bindValue("from_d", $from_d, PDO::PARAM_STR);
$stmt->bindValue("to_d", $to_d, PDO::PARAM_STR);
$stmt->execute();
$event_sales_worst = $stmt->fetchAll(PDO::FETCH_ASSOC);


//商品ごとの売上・粗利の集計。売上金額をトップ１０件降順でソート。順位もつける
$sql = "SELECT 
	ROW_NUMBER() OVER (ORDER BY sum(UriageKin) DESC) as 順位
	,ShouhinNM as 商品名
	,sum(UriageKin) as 売上金額
	,sum(su) as 売上個数
	,sum(genka) as 売上原価
	,sum(UriageKin)-sum(genka) as 粗利
	from UriageMeisai 
	where uid=:uid and UriDate between :from_d and :to_d
	group by 商品名
	order by 売上金額 desc
	limit 10";

$stmt = $pdo_h->prepare($sql);
$stmt->bindValue("uid", $uid, PDO::PARAM_INT);
$stmt->bindValue("from_d", $from_d, PDO::PARAM_STR);
$stmt->bindValue("to_d", $to_d, PDO::PARAM_STR);
$stmt->execute();
$product_sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

//商品ごとの売上・粗利の集計。売上金額をワースト１０件昇順でソート。順位もつける
$sql = "SELECT 
	ROW_NUMBER() OVER (ORDER BY sum(UriageKin) ASC) as 順位
	,ShouhinNM as 商品名
	,sum(UriageKin) as 売上金額
	,sum(su) as 売上個数
	,sum(genka) as 売上原価
	,sum(UriageKin)-sum(genka) as 粗利
	from UriageMeisai 
	where uid=:uid and UriDate between :from_d and :to_d
	group by 商品名
	order by 売上金額 asc
	limit 10";

$stmt = $pdo_h->prepare($sql);
$stmt->bindValue("uid", $uid, PDO::PARAM_INT);
$stmt->bindValue("from_d", $from_d, PDO::PARAM_STR);
$stmt->bindValue("to_d", $to_d, PDO::PARAM_STR);
$stmt->execute();
$product_sales_worst = $stmt->fetchAll(PDO::FETCH_ASSOC);


//イベント開催住所ごとの平均売上金額を降順でソート。トップ１０件昇順でソート。順位もつける
$sql = "SELECT 
	ROW_NUMBER() OVER (ORDER BY avg(売上金額) DESC) as 順位
	,address as イベント開催住所
	,CAST(ROUND(avg(売上金額), 0) as CHAR) as 平均売上
	,CAST(ROUND(avg(粗利), 0) as CHAR) as 平均粗利
	from (SELECT address ,UriDate,sum(UriageKin) as 売上金額,sum(genka) as 売上原価,sum(UriageKin)-sum(genka) as 粗利 from UriageMeisai where uid=:uid and UriDate between :from_d and :to_d group by address,UriDate) as A 
	group by イベント開催住所
	order by avg(売上金額) desc
	limit 10";


$stmt = $pdo_h->prepare($sql);
$stmt->bindValue("uid", $uid, PDO::PARAM_INT);
$stmt->bindValue("from_d", $from_d, PDO::PARAM_STR);
$stmt->bindValue("to_d", $to_d, PDO::PARAM_STR);
$stmt->execute();
$address_sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

//イベント開催住所ごとの平均売上金額ワースト５件。順位もつける。
$sql = "SELECT 
	ROW_NUMBER() OVER (ORDER BY avg(売上金額) ASC) as 順位
	,address as イベント開催住所
	,CAST(ROUND(avg(売上金額), 0) as CHAR) as 平均売上
	,CAST(ROUND(avg(粗利), 0) as CHAR) as 平均粗利
	from (SELECT address ,UriDate,sum(UriageKin) as 売上金額,sum(genka) as 売上原価,sum(UriageKin)-sum(genka) as 粗利 from UriageMeisai where uid=:uid and UriDate between :from_d and :to_d group by address,UriDate) as A 
	group by イベント開催住所
	order by avg(売上金額) asc
	limit 5";

$stmt = $pdo_h->prepare($sql);
$stmt->bindValue("uid", $uid, PDO::PARAM_INT);
$stmt->bindValue("from_d", $from_d, PDO::PARAM_STR);
$stmt->bindValue("to_d", $to_d, PDO::PARAM_STR);
$stmt->execute();
$address_sales_worst = $stmt->fetchAll(PDO::FETCH_ASSOC);


//天気ごとの平均売上金額を降順でソート。weatherが空白の場合は"未計測"と表示し最後尾に表示
$sql = "SELECT
	ROW_NUMBER() OVER (ORDER BY avg(売上金額) DESC) as 順位
	, 天気
	,CAST(ROUND(avg(売上金額), 0) as CHAR) as 平均売上
	,CAST(ROUND(avg(粗利), 0) as CHAR) as 平均粗利
	from (
		SELECT CASE WHEN weather = '' THEN '未計測' ELSE weather END as 天気 ,UriDate,sum(UriageKin) as 売上金額,sum(genka) as 売上原価 ,sum(UriageKin)-sum(genka) as 粗利
		from UriageMeisai where uid=:uid and UriDate between :from_d and :to_d group by 天気,UriDate) as A 
	group by 天気
	order by
		CASE
			WHEN 天気 = '未計測' THEN 1
			ELSE 0
		END,
		avg(売上金額) desc";

$stmt = $pdo_h->prepare($sql);
$stmt->bindValue("uid", $uid, PDO::PARAM_INT);
$stmt->bindValue("from_d", $from_d, PDO::PARAM_STR);
$stmt->bindValue("to_d", $to_d, PDO::PARAM_STR);
$stmt->execute();
$weather_sales = $stmt->fetchAll(PDO::FETCH_ASSOC);


//気温10℃単位ごとの商品別売上・粗利の集計。気温帯ごとの売上トップ５
$sql = "SELECT 
	CASE 
		WHEN IFNULL(temp,99) = 99 THEN '未計測'
		WHEN temp < 0 THEN '0度未満'
		WHEN temp >= 0 AND temp < 10 THEN '0度以上10度未満'
		WHEN temp >= 10 AND temp < 20 THEN '10度以上20度未満'
		WHEN temp >= 20 AND temp < 30 THEN '20度以上30度未満'
		ELSE '30度以上'
	END as 気温帯
	,ShouhinNM as 商品名
	,sum(UriageKin) as 売上金額
	,sum(UriageKin)-sum(genka) as 粗利
	from UriageMeisai 
	where uid=:uid and UriDate between :from_d and :to_d
	group by 気温帯, 商品名
	order by 
		CASE
			WHEN 気温帯 = '未計測' THEN 1
			ELSE 0
		END,
		気温帯, 売上金額 desc";

$stmt = $pdo_h->prepare($sql);
$stmt->bindValue("uid", $uid, PDO::PARAM_INT);
$stmt->bindValue("from_d", $from_d, PDO::PARAM_STR);
$stmt->bindValue("to_d", $to_d, PDO::PARAM_STR);
$stmt->execute();
$temp_sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
//$temp_salesを気温帯ごとのトップ５のみに
$grouped_temp_sales = [];
foreach ($temp_sales as $row) {
    $temp_zone = $row['気温帯'];
    if (!isset($grouped_temp_sales[$temp_zone])) {
        $grouped_temp_sales[$temp_zone] = [];
    }
    $grouped_temp_sales[$temp_zone][] = $row;
}

$temp_sales_top5 = [];
foreach ($grouped_temp_sales as $temp_zone => $products) {
    $temp_sales_top5[$temp_zone] = array_slice($products, 0, 5);
}


//すべての統計データをまとめる
$all_stats = [
    '期間中の総売上概要' => $total_sales_summary,
    '年次売上データ' => $yearly_sales,
    '月次売上データ' => $monthly_sales,
    '日次売上データ' => $daily_sales,
    '商品分類別売上データ' => $category_sales,
    '商品別ABC分析データ' => $abc_data,
    'イベント別平均売上トップ10' => $event_sales,
    'イベント別平均売上ワースト5' => $event_sales_worst,
    '商品別売上トップ10' => $product_sales,
    '商品別売上ワースト10' => $product_sales_worst,
    '住所別平均売上トップ10' => $address_sales,
    '住所別平均売上ワースト5' => $address_sales_worst,
    '天気別平均売上データ' => $weather_sales,
    '気温帯別商品売上トップ5' => $temp_sales_top5
];



// JSON形式で出力
header('Content-Type: application/json; charset=utf-8');
echo json_encode($all_stats, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);



?>
