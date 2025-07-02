<?php
require "php_header.php";

/*売上明細を取得し、AIで売上分析するために必要な統計データを何種類か表で作成する。
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

// $shouhin_rowsをヘッダー付きのCSVに変換し$CSVに格納
$CSV = "";
if (!empty($shouhin_rows)) {
    // ヘッダー行を追加
    $CSV .= implode(",", array_keys($shouhin_rows[0])) . "<br>";
    // データ行を追加
    foreach ($shouhin_rows as $row) {
        $CSV .= implode(",", array_values($row)) . "<br>";
    }
}

// CSVデータを表示
echo $CSV;

//以下、統計データ作成
// 1. 月別売上集計
$monthly_sales = [];
foreach ($shouhin_rows as $row) {
    $month = substr($row['売上計上年月'], 0, 7);
    if (!isset($monthly_sales[$month])) {
        $monthly_sales[$month] = ['売上個数' => 0, '売上金額' => 0, '売上原価' => 0];
    }
    $monthly_sales[$month]['売上個数'] += $row['売上個数'];
    $monthly_sales[$month]['売上金額'] += $row['売上金額'];
    $monthly_sales[$month]['売上原価'] += $row['売上原価'];
}
echo "<br><br>月別売上集計:<br>";
echo "年月,売上個数,売上金額,売上原価<br>";
foreach ($monthly_sales as $month => $data) {
    echo "{$month},{$data['売上個数']},{$data['売上金額']},{$data['売上原価']}<br>";
}

// 2. 商品別売上集計
$product_sales = [];
foreach ($shouhin_rows as $row) {
    $product = $row['商品名'];
    if (!isset($product_sales[$product])) {
        $product_sales[$product] = ['売上個数' => 0, '売上金額' => 0, '売上原価' => 0];
    }
    $product_sales[$product]['売上個数'] += $row['売上個数'];
    $product_sales[$product]['売上金額'] += $row['売上金額'];
    $product_sales[$product]['売上原価'] += $row['売上原価'];
}
echo "<br><br>商品別売上集計:<br>";
echo "商品名,売上個数,売上金額,売上原価<br>";
foreach ($product_sales as $product => $data) {
    echo "{$product},{$data['売上個数']},{$data['売上金額']},{$data['売上原価']}<br>";  

}

// 3. イベント別売上集計
$event_sales = [];
foreach ($shouhin_rows as $row) {
    $event = $row['売上計上イベント名'];
    if (empty($event)) {
        $event = $row['イベント以外の売上先'];
    }
    if (!isset($event_sales[$event])) {
        $event_sales[$event] = ['売上個数' => 0, '売上金額' => 0, '売上原価' => 0];
    }
    $event_sales[$event]['売上個数'] += $row['売上個数'];
    $event_sales[$event]['売上金額'] += $row['売上金額'];
    $event_sales[$event]['売上原価'] += $row['売上原価'];
}
echo "<br><br>イベント別売上集計:<br>";
echo "イベント名,売上個数,売上金額,売上原価<br>";
foreach ($event_sales as $event => $data) {
    echo "{$event},{$data['売上個数']},{$data['売上金額']},{$data['売上原価']}<br>";
}

// 4. 商品分類別売上集計
$category_sales = [];
foreach ($shouhin_rows as $row) {
    $category = $row['商品分類'];
    if (!isset($category_sales[$category])) {
        $category_sales[$category] = ['売上個数' => 0, '売上金額' => 0, '売上原価' => 0];
    }
    $category_sales[$category]['売上個数'] += $row['売上個数'];
    $category_sales[$category]['売上金額'] += $row['売上金額'];
    $category_sales[$category]['売上原価'] += $row['売上原価'];
}
echo "<br><br>商品分類別売上集計:<br>";
echo "商品分類,売上個数,売上金額,売上原価<br>";
foreach ($category_sales as $category => $data) {
    echo "{$category},{$data['売上個数']},{$data['売上金額']},{$data['売上原価']}<br>";
}

// 5. 天気別売上集計
$weather_sales = [];
foreach ($shouhin_rows as $row) {
    $weather = $row['売上時の天気'];
    if (!isset($weather_sales[$weather])) {
        $weather_sales[$weather] = ['売上個数' => 0, '売上金額' => 0, '売上原価' => 0];
    }
    $weather_sales[$weather]['売上個数'] += $row['売上個数'];
    $weather_sales[$weather]['売上金額'] += $row['売上金額'];
    $weather_sales[$weather]['売上原価'] += $row['売上原価'];
}
echo "<br><br>天気別売上集計:<br>";
echo "天気,売上個数,売上金額,売上原価<br>";
foreach ($weather_sales as $weather => $data) {
    echo "{$weather},{$data['売上個数']},{$data['売上金額']},{$data['売上原価']}<br>";
}

// 6. 気温別売上集計 (気温を範囲で区切る)
$temperature_sales = [];
foreach ($shouhin_rows as $row) {
    $temp = (float)$row['売上時の気温'];
    $temp_range = '';
    if ($temp < 0) {
        $temp_range = '0度未満';
    } elseif ($temp >= 0 && $temp < 10) {
        $temp_range = '0-9度';
    } elseif ($temp >= 10 && $temp < 20) {
        $temp_range = '10-19度';
    } elseif ($temp >= 20 && $temp < 30) {
        $temp_range = '20-29度';
    } else {
        $temp_range = '30度以上';
    }

    if (!isset($temperature_sales[$temp_range])) {
        $temperature_sales[$temp_range] = ['売上個数' => 0, '売上金額' => 0, '売上原価' => 0];
    }
    $temperature_sales[$temp_range]['売上個数'] += $row['売上個数'];
    $temperature_sales[$temp_range]['売上金額'] += $row['売上金額'];
    $temperature_sales[$temp_range]['売上原価'] += $row['売上原価'];
}
echo "<br><br>気温別売上集計:<br>";
echo "気温範囲,売上個数,売上金額,売上原価<br>";
foreach ($temperature_sales as $range => $data) {
    echo "{$range},{$data['売上個数']},{$data['売上金額']},{$data['売上原価']}<br>";
}

?>
