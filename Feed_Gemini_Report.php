<?php
require "php_header.php";

/*売上明細を取得し、AIで売上分析するために必要な統計データをウェブサイトに表形式で表示する。
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
		,IFNULL(bunrui1,'未設定') as 商品分類大
		,IFNULL(bunrui2,'未設定') as 商品分類中
		,IFNULL(bunrui3,'未設定') as 商品分類小
		,address as イベント開催住所
		,weather as 売上時の天気
		,CAST(ROUND(temp,1) as CHAR) as 売上時の気温
		from UriageMeisai 
		where uid=:uid and UriDate between :from_d and :to_d"
です。uidとfrom_dとto_dは$_GETから取得します。

*/
//GET変数取得
$uid = $_GET["uid"];
$from_d = $_GET["from_d"];
$to_d = $_GET["to_d"];

//DB接続
//$pdo_h = new PDO("mysql:host=localhost;dbname=SaleM;charset=utf8", "root", "");

//売上明細データを取得
$sql="SELECT 
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

$stmt = $pdo_h->prepare($sql);
$stmt->bindValue("uid", $uid, PDO::PARAM_INT);
$stmt->bindValue("from_d", $from_d, PDO::PARAM_STR);
$stmt->bindValue("to_d", $to_d, PDO::PARAM_STR);
$stmt->execute();
$shouhin_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 統計データの生成
$total_sales = 0;
$total_cost = 0;
$sales_by_month = [];
$sales_by_event = [];
$sales_by_product = [];
$sales_by_weather = [];

foreach ($shouhin_rows as $row) {
    $total_sales += $row['売上金額'];
    $total_cost += $row['売上原価'];

    // 月ごとの売上
    $month = $row['売上計上年月'];
    $sales_by_month[$month] = ($sales_by_month[$month] ?? 0) + $row['売上金額'];

    // イベントごとの売上
    $event = !empty($row['売上計上イベント名']) ? $row['売上計上イベント名'] : $row['イベント以外の売上先'];
    $sales_by_event[$event] = ($sales_by_event[$event] ?? 0) + $row['売上金額'];

    // 商品ごとの売上
    $product = $row['商品名'];
    $sales_by_product[$product] = ($sales_by_product[$product] ?? 0) + $row['売上金額'];

    // 天気ごとの売上
    $weather = $row['売上時の天気'];
    $sales_by_weather[$weather] = ($sales_by_weather[$weather] ?? 0) + $row['売上金額'];
}

// 利益の計算
$profit = $total_sales - $total_cost;

// HTML出力
echo "<h1>売上分析レポート</h1>";
echo "<h2>期間: " . htmlspecialchars($from_d) . " から " . htmlspecialchars($to_d) . "</h2>";

echo "<h3>概要</h3>";
echo "<p><strong>総売上金額:</strong> " . number_format($total_sales) . " 円</p>";
echo "<p><strong>総売上原価:</strong> " . number_format($total_cost) . " 円</p>";
echo "<p><strong>総利益:</strong> " . number_format($profit) . " 円</p>";

echo "<h3>月ごとの売上</h3>";
echo "<table border='1'><tr><th>年月</th><th>売上金額</th></tr>";
foreach ($sales_by_month as $month => $sales) {
    echo "<tr><td>" . htmlspecialchars($month) . "</td><td>" . number_format($sales) . " 円</td></tr>";
}
echo "</table>";

echo "<h3>イベントごとの売上</h3>";
echo "<table border='1'><tr><th>イベント名/売上先</th><th>売上金額</th></tr>";
foreach ($sales_by_event as $event => $sales) {
    echo "<tr><td>" . htmlspecialchars($event) . "</td><td>" . number_format($sales) . " 円</td></tr>";
}
echo "</table>";

echo "<h3>商品ごとの売上</h3>";
echo "<table border='1'><tr><th>商品名</th><th>売上金額</th></tr>";
foreach ($sales_by_product as $product => $sales) {
    echo "<tr><td>" . htmlspecialchars($product) . "</td><td>" . number_format($sales) . " 円</td></tr>";
}
echo "</table>";

echo "<h3>天気ごとの売上</h3>";
echo "<table border='1'><tr><th>天気</th><th>売上金額</th></tr>";
foreach ($sales_by_weather as $weather => $sales) {
    echo "<tr><td>" . htmlspecialchars($weather) . "</td><td>" . number_format($sales) . " 円</td></tr>";
}
echo "</table>";

echo "<h3>詳細データ</h3>";
echo "<table border='1'><tr>";
foreach (array_keys($shouhin_rows[0]) as $header) {
    echo "<th>" . htmlspecialchars($header) . "</th>";
}
echo "</tr>";
foreach ($shouhin_rows as $row) {
    echo "<tr>";
    foreach ($row as $value) {
        echo "<td>" . htmlspecialchars($value) . "</td>";
    }
    echo "</tr>";
}
echo "</table>";
echo "<p>データは以上です。</p>";

?>
