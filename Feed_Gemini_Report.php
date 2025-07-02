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
$from_d = $_GET["from_d"];
$to_d = $_GET["to_d"];

//DB接続
//$pdo_h = new PDO("mysql:host=localhost;dbname=SaleM;charset=utf8", "root", "");

//月ごとの売上・粗利の集計。売上計上年月を昇順でソート
$sql = "SELECT 
	DATE_FORMAT(UriDate, '%Y-%m') as 売上計上年月
	,sum(UriageKin) as 売上金額
	,sum(genka) as 売上原価
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

//商品分類ごとの売上・粗利の集計。売上金額を降順でソート
$sql = "SELECT 
	CONCAT(IFNULL(bunrui1,'未設定'),'>',IFNULL(bunrui2,'未設定') ,'>',IFNULL(bunrui3,'未設定')) as 商品分類
	,sum(UriageKin) as 売上金額
	,sum(genka) as 売上原価
	from UriageMeisai 
	where uid=:uid and UriDate between :from_d and :to_d
	group by 商品分類
	order by 売上金額 desc";

$stmt = $pdo_h->prepare($sql);
$stmt->bindValue("uid", $uid, PDO::PARAM_INT);
$stmt->bindValue("from_d", $from_d, PDO::PARAM_STR);
$stmt->bindValue("to_d", $to_d, PDO::PARAM_STR);
$stmt->execute();
$category_sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

//イベントごとの売上・粗利の集計。売上金額を降順でソート
$sql = "SELECT 
	Event as 売上計上イベント名
	,sum(UriageKin) as 売上金額
	,sum(genka) as 売上原価
	from UriageMeisai 
	where uid=:uid and UriDate between :from_d and :to_d
	group by 売上計上イベント名
	order by 売上金額 desc";

$stmt = $pdo_h->prepare($sql);
$stmt->bindValue("uid", $uid, PDO::PARAM_INT);
$stmt->bindValue("from_d", $from_d, PDO::PARAM_STR);
$stmt->bindValue("to_d", $to_d, PDO::PARAM_STR);
$stmt->execute();
$event_sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

//商品ごとの売上・粗利の集計。売上金額をトップ１０件降順でソート
$sql = "SELECT 
	ShouhinNM as 商品名
	,sum(UriageKin) as 売上金額
	,sum(genka) as 売上原価
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

//商品ごとの売上・粗利の集計。売上金額をワースト１０件昇順でソート
$sql = "SELECT 
	ShouhinNM as 商品名
	,sum(UriageKin) as 売上金額
	,sum(genka) as 売上原価
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

//イベント開催住所ごとの売上・粗利の集計。売上金額を降順でソート
$sql = "SELECT 
	address as イベント開催住所
	,sum(UriageKin) as 売上金額
	,sum(genka) as 売上原価
	from UriageMeisai 
	where uid=:uid and UriDate between :from_d and :to_d
	group by イベント開催住所
	order by 売上金額 desc";

$stmt = $pdo_h->prepare($sql);
$stmt->bindValue("uid", $uid, PDO::PARAM_INT);
$stmt->bindValue("from_d", $from_d, PDO::PARAM_STR);
$stmt->bindValue("to_d", $to_d, PDO::PARAM_STR);
$stmt->execute();
$address_sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

//天気ごとの売上・粗利の集計。売上金額を降順でソート
$sql = "SELECT 
	weather as 売上時の天気
	,sum(UriageKin) as 売上金額
	,sum(genka) as 売上原価
	from UriageMeisai 
	where uid=:uid and UriDate between :from_d and :to_d
	group by 売上時の天気
	order by 売上金額 desc";

$stmt = $pdo_h->prepare($sql);
$stmt->bindValue("uid", $uid, PDO::PARAM_INT);
$stmt->bindValue("from_d", $from_d, PDO::PARAM_STR);
$stmt->bindValue("to_d", $to_d, PDO::PARAM_STR);
$stmt->execute();
$weather_sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

//気温10℃単位ごとの商品別売上・粗利の集計。気温帯ごとの売上トップ１０
$sql = "SELECT 
	CASE 
		WHEN temp < 0 THEN '0度未満'
		WHEN temp >= 0 AND temp < 10 THEN '0度以上10度未満'
		WHEN temp >= 10 AND temp < 20 THEN '10度以上20度未満'
		WHEN temp >= 20 AND temp < 30 THEN '20度以上30度未満'
		ELSE '30度以上'
	END as 気温帯
	,ShouhinNM as 商品名
	,sum(UriageKin) as 売上金額
	,sum(genka) as 売上原価
	from UriageMeisai 
	where uid=:uid and UriDate between :from_d and :to_d
	group by 気温帯, 商品名
	order by 気温帯, 売上金額 desc";

$stmt = $pdo_h->prepare($sql);
$stmt->bindValue("uid", $uid, PDO::PARAM_INT);
$stmt->bindValue("from_d", $from_d, PDO::PARAM_STR);
$stmt->bindValue("to_d", $to_d, PDO::PARAM_STR);
$stmt->execute();
$temp_sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
//$temp_salesを気温帯ごとのトップ１０のみに
$temp_sales_filtered = [];
$current_temp_band = '';
$count = 0;
foreach ($temp_sales as $row) {
    if ($row['気温帯'] !== $current_temp_band) {
        $current_temp_band = $row['気温帯'];
        $count = 0;
    }
    if ($count < 10) {
        $temp_sales_filtered[] = $row;
        $count++;
    }
}
$temp_sales = $temp_sales_filtered;


//すべての統計データをまとめる
$all_stats = [
    "月ごとの売上・粗利の集計" => $monthly_sales,
    "商品分類ごとの売上・粗利の集計" => $category_sales,
    "イベントごとの売上・粗利の集計" => $event_sales,
    "商品ごとの売上・粗利の集計（トップ10）" => $product_sales,
    "商品ごとの売上・粗利の集計（ワースト10）" => $product_sales_worst,
    "イベント開催住所ごとの売上・粗利の集計" => $address_sales,
    "天気ごとの売上・粗利の集計" => $weather_sales,
    "気温帯ごとの売上トップ１０" => $temp_sales,
];


// JSON形式で出力
header('Content-Type: application/json; charset=utf-8');
echo json_encode($all_stats, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);



?>
