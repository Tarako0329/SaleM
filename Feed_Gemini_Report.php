<?php
require "php_header.php";

/*売上明細をもとにデータを集計し、HTMLで表を作成するプログラム
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
		,tanka as 売上単価
		,UriageKin as 売上金額
		,genka_tanka as 原価単価
		,genka as 売上原価
		,IFNULL(bunrui1,'未設定') as 商品分類大
		,IFNULL(bunrui2,'未設定') as 商品分類中
		,IFNULL(bunrui3,'未設定') as 商品分類小
		,address as イベント開催住所
		,weather as 売上時の天気
		,weather_discription as 売上時の天気詳細
		,CAST(ROUND(temp,1) as CHAR) as 売上時の気温
		,CAST(ROUND(feels_like,1) as CHAR) as 売上時の体感温度
		from UriageMeisai 
		where uid=:uid and UriDate between :from_d and :to_d"
です。uidとfrom_dとto_dは$_GETから取得します。

作成する表を指示します。
１．月ごとの売上と粗利を集計した表
２．月ごとの売上金額トップ１０の商品名と個数
３．月ごとの売上数トップ１０の商品名と売上金額
４．売上計上イベント名毎の平均売上金額とイベントの開催回数
５．イベントごとの売上数トップ１０の商品と売上金額
*/
//GET変数取得
$uid = $_GET["uid"];
$from_d = $_GET["from_d"];
$to_d = $_GET["to_d"];

//DB接続
//$pdo_h = new PDO("mysql:host=localhost;dbname=SaleM;charset=utf8", "root", "");

//売上明細取得
$sql = "SELECT 
		DATE_FORMAT(UriDate, '%Y') as 売上計上年
		,DATE_FORMAT(UriDate, '%Y-%m') as 売上計上年月
		,UriDate as 売上計上年月日
		,Event as 売上計上イベント名
		,TokuisakiNM as イベント以外の売上先
		,UriageNO as 売上番号
		,ShouhinNM as 商品名
		,su as 売上個数
		,tanka as 売上単価
		,UriageKin as 売上金額
		,genka_tanka as 原価単価
		,genka as 売上原価
		,IFNULL(bunrui1,'未設定') as 商品分類大
		,IFNULL(bunrui2,'未設定') as 商品分類中
		,IFNULL(bunrui3,'未設定') as 商品分類小
		,address as イベント開催住所
		,weather as 売上時の天気
		,weather_discription as 売上時の天気詳細
		,CAST(ROUND(temp,1) as CHAR) as 売上時の気温
		,CAST(ROUND(feels_like,1) as CHAR) as 売上時の体感温度
		from UriageMeisai 
		where uid=:uid and UriDate between :from_d and :to_d";
$stmt = $pdo_h->prepare($sql);
$stmt->bindValue(":uid", $uid, PDO::PARAM_INT);
$stmt->bindValue(":from_d", $from_d, PDO::PARAM_STR);
$stmt->bindValue(":to_d", $to_d, PDO::PARAM_STR);
$stmt->execute();
$meisai_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
log_writer2("\$_GET", $_GET,"lv3");
log_writer2("\$meisai_data", $meisai_data,"lv3");

// １．月ごとの売上と粗利を集計した表。表の最終行に総合計を表示する。
$monthly_summary = [];
$total_sales_all = 0;
$total_profit_all = 0;

foreach ($meisai_data as $row) {
    $month = $row['売上計上年月'];
    $uriage_kin = $row['売上金額'];
    $genka = $row['売上原価'];
    $arari = $uriage_kin - $genka;

    if (!isset($monthly_summary[$month])) {
        $monthly_summary[$month] = ['売上金額' => 0, '粗利' => 0];
    }
    $monthly_summary[$month]['売上金額'] += $uriage_kin;
    $monthly_summary[$month]['粗利'] += $arari;

    $total_sales_all += $uriage_kin;
    $total_profit_all += $arari;
}

// Sort by month
ksort($monthly_summary);

echo "<h2>1. 月ごとの売上と粗利</h2>";
echo "<table border='1'>";
echo "<tr><th>年月</th><th>売上金額</th><th>粗利</th></tr>";
foreach ($monthly_summary as $month => $data) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($month) . "</td>";
    echo "<td>" . number_format($data['売上金額']) . "</td>";
    echo "<td>" . number_format($data['粗利']) . "</td>";
    echo "</tr>";
}
echo "<tr>";
echo "<td><strong>総合計</strong></td>";
echo "<td><strong>" . number_format($total_sales_all) . "</strong></td>";
echo "<td><strong>" . number_format($total_profit_all) . "</strong></td>";
echo "</tr>";
echo "</table>";


// ２．月ごとの売上金額トップ１０の商品名と個数。各表の最終行に総合計を表示する。
$monthly_sales_top10 = [];
foreach ($meisai_data as $row) {
    $month = $row['売上計上年月'];
    $shouhin_nm = $row['商品名'];
    $uriage_kin = $row['売上金額'];
    $su = $row['売上個数'];

    if (!isset($monthly_sales_top10[$month])) {
        $monthly_sales_top10[$month] = [];
    }
    if (!isset($monthly_sales_top10[$month][$shouhin_nm])) {
        $monthly_sales_top10[$month][$shouhin_nm] = ['売上金額' => 0, '売上個数' => 0];
    }
    $monthly_sales_top10[$month][$shouhin_nm]['売上金額'] += $uriage_kin;
    $monthly_sales_top10[$month][$shouhin_nm]['売上個数'] += $su;
}

echo "<h2>2. 月ごとの売上金額トップ10の商品</h2>";
foreach ($monthly_sales_top10 as $month => $products) {
    echo "<h3>" . htmlspecialchars($month) . "</h3>";
    // Sort products by 売上金額 in descending order
    uasort($products, function ($a, $b) {
        return $b['売上金額'] <=> $a['売上金額'];
    });
    $top10_products = array_slice($products, 0, 10, true);

    echo "<table border='1'>";
    echo "<tr><th>商品名</th><th>売上金額</th><th>売上個数</th></tr>";
    $total_sales_month = 0;
    $total_quantity_month = 0;
    foreach ($top10_products as $shouhin_nm => $data) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($shouhin_nm) . "</td>";
        echo "<td>" . number_format($data['売上金額']) . "</td>";
        echo "<td>" . number_format($data['売上個数']) . "</td>";
        echo "</tr>";
        $total_sales_month += $data['売上金額'];
        $total_quantity_month += $data['売上個数'];
    }
    echo "<tr>";
    echo "<td><strong>総合計</strong></td>";
    echo "<td><strong>" . number_format($total_sales_month) . "</strong></td>";
    echo "<td><strong>" . number_format($total_quantity_month) . "</strong></td>";
    echo "</tr>";
    echo "</table>";
}



// ３．月ごとの売上数トップ１０の商品名と売上金額。各表の最終行に総合計を表示する。
$monthly_quantity_top10 = [];
foreach ($meisai_data as $row) {
    $month = $row['売上計上年月'];
    $shouhin_nm = $row['商品名'];
    $su = $row['売上個数'];
    $uriage_kin = $row['売上金額'];

    if (!isset($monthly_quantity_top10[$month])) {
        $monthly_quantity_top10[$month] = [];
    }
    if (!isset($monthly_quantity_top10[$month][$shouhin_nm])) {
        $monthly_quantity_top10[$month][$shouhin_nm] = ['売上個数' => 0, '売上金額' => 0];
    }
    $monthly_quantity_top10[$month][$shouhin_nm]['売上個数'] += $su;
    $monthly_quantity_top10[$month][$shouhin_nm]['売上金額'] += $uriage_kin;
}

echo "<h2>3. 月ごとの売上数トップ10の商品</h2>";
foreach ($monthly_quantity_top10 as $month => $products) {
    echo "<h3>" . htmlspecialchars($month) . "</h3>";
    // Sort products by 売上個数 in descending order
    uasort($products, function ($a, $b) {
        return $b['売上個数'] <=> $a['売上個数'];
    });
    $top10_products = array_slice($products, 0, 10, true);

    echo "<table border='1'>";
    echo "<tr><th>商品名</th><th>売上個数</th><th>売上金額</th></tr>";
    $total_quantity_month = 0;
    $total_sales_month = 0;
    foreach ($top10_products as $shouhin_nm => $data) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($shouhin_nm) . "</td>";
        echo "<td>" . number_format($data['売上個数']) . "</td>";
        echo "<td>" . number_format($data['売上金額']) ."</td>";
        echo "</tr>";
        $total_quantity_month += $data['売上個数'];
        $total_sales_month += $data['売上金額'];
    }
    echo "<tr>";
    echo "<td><strong>総合計</strong></td>";
    echo "<td><strong>" . number_format($total_quantity_month) . "</strong></td>";
    echo "<td><strong>" . number_format($total_sales_month) . "</strong></td>";
    echo "</tr>";
    echo "</table>";
}


// ４．イベント名毎の平均売上金額を降順で表示。イベントの開催回数。各表の最終行に総合計を表示する。
//    イベントの開催回数はイベント名と売上計上日から判断する
$event_summary = [];
$event_dates = []; // To count unique event occurrences

foreach ($meisai_data as $row) {
    $event_name = $row['売上計上イベント名'];
    $uriage_kin = $row['売上金額'];
    $uri_date = $row['売上計上年月日'];

    // If event name is empty, consider it as "イベント以外"
    if (empty($event_name)) {
        $event_name = "イベント以外";
    }

    if (!isset($event_summary[$event_name])) {
        $event_summary[$event_name] = ['合計売上金額' => 0, '開催回数' => 0];
        $event_dates[$event_name] = [];
    }
    $event_summary[$event_name]['合計売上金額'] += $uriage_kin;

    // Count unique event occurrences by date
    if (!in_array($uri_date, $event_dates[$event_name])) {
        $event_dates[$event_name][] = $uri_date;
        $event_summary[$event_name]['開催回数']++;
    }
}

// Calculate average sales and sort
foreach ($event_summary as $event_name => &$data) {
    $data['平均売上金額'] = $data['合計売上金額'] / $data['開催回数'];
}
unset($data); // Unset reference

// Sort by 平均売上金額 in descending order
uasort($event_summary, function ($a, $b) {
    return $b['平均売上金額'] <=> $a['平均売上金額'];
});

echo "<h2>4. イベント名ごとの平均売上金額と開催回数</h2>";
echo "<table border='1'>";
echo "<tr><th>イベント名</th><th>平均売上金額</th><th>開催回数</th></tr>";
$total_avg_sales = 0;
$total_occurrences = 0;
$event_count = 0;

foreach ($event_summary as $event_name => $data) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($event_name) . "</td>";
    echo "<td>" . number_format($data['平均売上金額']) . "</td>";
    echo "<td>" . number_format($data['開催回数']) . "</td>";
    echo "</tr>";
    $total_avg_sales += $data['平均売上金額'];
    $total_occurrences += $data['開催回数'];
    $event_count++;
}
echo "<tr>";
echo "<td><strong>総合計</strong></td>";
echo "<td><strong>" . number_format($total_avg_sales / $event_count) . "</strong></td>";
echo "<td><strong>" . number_format($total_occurrences) . "</strong></td>";
echo "</tr>";
echo "</table>";


// ５．イベントごとの売上数トップ１０の商品と売上金額
$event_product_summary = [];
foreach ($meisai_data as $row) {
    $event_name = $row['売上計上イベント名'];
    $shouhin_nm = $row['商品名'];
    $su = $row['売上個数'];
    $uriage_kin = $row['売上金額'];

    if (!isset($event_product_summary[$event_name])) {
        $event_product_summary[$event_name] = [];
    }
    if (!isset($event_product_summary[$event_name][$shouhin_nm])) {
        $event_product_summary[$event_name][$shouhin_nm] = ['売上個数' => 0, '売上金額' => 0];
    }
    $event_product_summary[$event_name][$shouhin_nm]['売上個数'] += $su;
    $event_product_summary[$event_name][$shouhin_nm]['売上金額'] += $uriage_kin;
}

echo "<h2>5. イベントごとの売上数トップ10の商品</h2>";
foreach ($event_product_summary as $event_name => $products) {
    echo "<h3>" . htmlspecialchars($event_name) . "</h3>";
    // Sort products by 売上個数 in descending order
    uasort($products, function ($a, $b) {
        return $b['売上個数'] <=> $a['売上個数'];
    });
    $top10_products = array_slice($products, 0, 10, true);

    echo "<table border='1'>";
    echo "<tr><th>商品名</th><th>売上個数</th><th>売上金額</th></tr>";
    foreach ($top10_products as $shouhin_nm => $data) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($shouhin_nm) . "</td>";
        echo "<td>" . number_format($data['売上個数']) . "</td>";
        echo "<td>" . number_format($data['売上金額']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}
// ６．商品分類大、商品分類中、商品分類小を結合し、売上金額と粗利を集計。商品分類大、商品分類中、商品分類小の順でソートする。未分類はソートの最後。表の最終行に総合計を表示する。
$category_summary = [];
$total_sales = 0;
$total_cost = 0;
$total_profit = 0;

foreach ($meisai_data as $row) {
    $bunrui1 = $row['商品分類大'];
    $bunrui2 = $row['商品分類中'];
    $bunrui3 = $row['商品分類小'];
    
    // カテゴリ名を結合し、未設定の場合は「未分類」とする
    $category_parts = [];
    if ($bunrui1 !== '未設定') {
        $category_parts[] = $bunrui1;
    }
    if ($bunrui2 !== '未設定') {
        $category_parts[] = $bunrui2;
    }
    if ($bunrui3 !== '未設定') {
        $category_parts[] = $bunrui3;
    }
    $category = empty($category_parts) ? '未分類' : implode(' > ', $category_parts);

    $uriage_kin = $row['売上金額'];
    $genka = $row['売上原価'];

    if (!isset($category_summary[$category])) {
        $category_summary[$category] = ['売上金額' => 0, '売上原価' => 0, '粗利' => 0];
    }
    $category_summary[$category]['売上金額'] += $uriage_kin;
    $category_summary[$category]['売上原価'] += $genka;
    $category_summary[$category]['粗利'] += ($uriage_kin - $genka);

    $total_sales += $uriage_kin;
    $total_cost += $genka;
    $total_profit += ($uriage_kin - $genka);
}

// Sort categories: first by bunrui1, then bunrui2, then bunrui3. '未分類' should be last.
uksort($category_summary, function($a, $b) {
    if ($a === '未分類' && $b !== '未分類') {
        return 1;
    }
    if ($a !== '未分類' && $b === '未分類') {
        return -1;
    }
    
    $a_parts = explode(' > ', $a);
    $b_parts = explode(' > ', $b);

    // Compare by bunrui1
    $cmp1 = strcmp($a_parts[0], $b_parts[0]);
    if ($cmp1 !== 0) {
        return $cmp1;
    }

    // Compare by bunrui2 if exists
    if (isset($a_parts[1]) && isset($b_parts[1])) {
        $cmp2 = strcmp($a_parts[1], $b_parts[1]);
        if ($cmp2 !== 0) {
            return $cmp2;
        }
    } elseif (isset($a_parts[1])) {
        return 1; // $a has bunrui2, $b doesn't
    } elseif (isset($b_parts[1])) {
        return -1; // $b has bunrui2, $a doesn't
    }

    // Compare by bunrui3 if exists
    if (isset($a_parts[2]) && isset($b_parts[2])) {
        return strcmp($a_parts[2], $b_parts[2]);
    } elseif (isset($a_parts[2])) {
        return 1; // $a has bunrui3, $b doesn't
    } elseif (isset($b_parts[2])) {
        return -1; // $b has bunrui3, $a doesn't
    }

    return 0;
});

echo "<h2>6. 商品分類別売上金額と粗利</h2>";
echo "<table border='1'>";
echo "<tr><th>商品分類</th><th>売上金額</th><th>売上原価</th><th>粗利</th></tr>";
foreach ($category_summary as $category => $data) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($category) . "</td>";
    echo "<td>" . number_format($data['売上金額']) . "</td>";
    echo "<td>" . number_format($data['売上原価']) . "</td>";
    echo "<td>" . number_format($data['粗利']) . "</td>";
    echo "</tr>";
}
echo "<tr>";
echo "<td colspan='1'><strong>総合計</strong></td>";
echo "<td><strong>" . number_format($total_sales) . "</strong></td>";
echo "<td><strong>" . number_format($total_cost) . "</strong></td>";
echo "<td><strong>" . number_format($total_profit) . "</strong></td>";
echo "</tr>";
echo "</table>";


// ７．商品分類大、商品分類中、商品分類小を結合し、売上金額と粗利を年毎に集計
$yearly_category_summary = [];
foreach ($meisai_data as $row) {
    $year = $row['売上計上年'];
    $bunrui1 = $row['商品分類大'];
    $bunrui2 = $row['商品分類中'];
    $bunrui3 = $row['商品分類小'];
    $category = implode(' > ', array_filter([$bunrui1, $bunrui2, $bunrui3]));
    
    $uriage_kin = $row['売上金額'];
    $genka = $row['売上原価'];

    if (!isset($yearly_category_summary[$year])) {
        $yearly_category_summary[$year] = [];
    }
    if (!isset($yearly_category_summary[$year][$category])) {
        $yearly_category_summary[$year][$category] = ['売上金額' => 0, '売上原価' => 0, '粗利' => 0];
    }
    $yearly_category_summary[$year][$category]['売上金額'] += $uriage_kin;
    $yearly_category_summary[$year][$category]['売上原価'] += $genka;
    $yearly_category_summary[$year][$category]['粗利'] += ($uriage_kin - $genka);
}

echo "<h2>7. 年ごとの商品分類別売上金額と粗利</h2>";
ksort($yearly_category_summary); // Sort by year
foreach ($yearly_category_summary as $year => $categories) {
    echo "<h3>" . htmlspecialchars($year) . "年</h3>";
    echo "<table border='1'>";
    echo "<tr><th>商品分類</th><th>売上金額</th><th>売上原価</th><th>粗利</th></tr>";
    foreach ($categories as $category => $data) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($category) . "</td>";
        echo "<td>" . number_format($data['売上金額']) . "</td>";
        echo "<td>" . number_format($data['売上原価']) . "</td>";
        echo "<td>" . number_format($data['粗利']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// ８．商品分類大、商品分類中、商品分類小を結合し、売上金額と粗利を年月毎に集計
$monthly_category_summary = [];
foreach ($meisai_data as $row) {
    $month = $row['売上計上年月'];
    $bunrui1 = $row['商品分類大'];
    $bunrui2 = $row['商品分類中'];
    $bunrui3 = $row['商品分類小'];
    $category = implode(' > ', array_filter([$bunrui1, $bunrui2, $bunrui3]));
    
    $uriage_kin = $row['売上金額'];
    $genka = $row['売上原価'];

    if (!isset($monthly_category_summary[$month])) {
        $monthly_category_summary[$month] = [];
    }
    if (!isset($monthly_category_summary[$month][$category])) {
        $monthly_category_summary[$month][$category] = ['売上金額' => 0, '売上原価' => 0, '粗利' => 0];
    }
    $monthly_category_summary[$month][$category]['売上金額'] += $uriage_kin;
    $monthly_category_summary[$month][$category]['売上原価'] += $genka;
    $monthly_category_summary[$month][$category]['粗利'] += ($uriage_kin - $genka);
}

echo "<h2>8. 月ごとの商品分類別売上金額と粗利</h2>";
ksort($monthly_category_summary); // Sort by month
foreach ($monthly_category_summary as $month => $categories) {
    echo "<h3>" . htmlspecialchars($month) . "</h3>";
    echo "<table border='1'>";
    echo "<tr><th>商品分類</th><th>売上金額</th><th>売上原価</th><th>粗利</th></tr>";
    foreach ($categories as $category => $data) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($category) . "</td>";
        echo "<td>" . number_format($data['売上金額']) . "</td>";
        echo "<td>" . number_format($data['売上原価']) . "</td>";
        echo "<td>" . number_format($data['粗利']) . "</td>";
        echo "</tr>";
        }
    echo "</table>";
}


// ９．売上時の天気ごとの売上金額と粗利
$weather_summary = [];
foreach ($meisai_data as $row) {
    $weather = $row['売上時の天気'];
    $uriage_kin = $row['売上金額'];
    $genka = $row['売上原価'];

    if (!isset($weather_summary[$weather])) {
        $weather_summary[$weather] = ['売上金額' => 0, '売上原価' => 0, '粗利' => 0];
    }
    $weather_summary[$weather]['売上金額'] += $uriage_kin;
    $weather_summary[$weather]['売上原価'] += $genka;
    $weather_summary[$weather]['粗利'] += ($uriage_kin - $genka);
}

echo "<h2>9. 売上時の天気ごとの売上金額と粗利</h2>";
echo "<table border='1'>";
echo "<tr><th>天気</th><th>売上金額</th><th>売上原価</th><th>粗利</th></tr>";
foreach ($weather_summary as $weather => $data) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($weather) . "</td>";
    echo "<td>" . number_format($data['売上金額']) . "</td>";
    echo "<td>" . number_format($data['売上原価']) . "</td>";
    echo "<td>" . number_format($data['粗利']) . "</td>";
    echo "</tr>";
}
echo "</table>";

// １０．売上時の気温ごとの売上金額と粗利 (気温は範囲で集計)
$temperature_summary = [];
foreach ($meisai_data as $row) {
    $temp = (float)$row['売上時の気温'];
    $uriage_kin = $row['売上金額'];
    $genka = $row['売上原価'];

    $temp_range = '';
    if ($temp < 0) {
        $temp_range = '0℃未満';
    } elseif ($temp >= 0 && $temp < 10) {
        $temp_range = '0℃～9℃';
    } elseif ($temp >= 10 && $temp < 20) {
        $temp_range = '10℃～19℃';
    } elseif ($temp >= 20 && $temp < 30) {
        $temp_range = '20℃～29℃';
    } elseif ($temp >= 30) {
        $temp_range = '30℃以上';
    }

    if (!isset($temperature_summary[$temp_range])) {
        $temperature_summary[$temp_range] = ['売上金額' => 0, '売上原価' => 0, '粗利' => 0];
    }
    $temperature_summary[$temp_range]['売上金額'] += $uriage_kin;
    $temperature_summary[$temp_range]['売上原価'] += $genka;
    $temperature_summary[$temp_range]['粗利'] += ($uriage_kin - $genka);
}

// Sort temperature ranges for display
$temp_order = ['0℃未満', '0℃～9℃', '10℃～19℃', '20℃～29℃', '30℃以上'];
$sorted_temperature_summary = [];
foreach ($temp_order as $range) {
    if (isset($temperature_summary[$range])) {
        $sorted_temperature_summary[$range] = $temperature_summary[$range];
    }
}


echo "<h2>10. 売上時の気温ごとの売上金額と粗利</h2>";
echo "<table border='1'>";
echo "<tr><th>気温範囲</th><th>売上金額</th><th>売上原価</th><th>粗利</th></tr>";
foreach ($sorted_temperature_summary as $range => $data) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($range) . "</td>";
    echo "<td>" . number_format($data['売上金額']) . "</td>";
    echo "<td>" . number_format($data['売上原価']) . "</td>";
    echo "<td>" . number_format($data['粗利']) . "</td>";
    echo "</tr>";
}
echo "</table>";

// １１．売上時の気温ごと商品ごとの売上金額ランキングと粗利 (気温は範囲で集計)
$temperature_product_summary = [];
foreach ($meisai_data as $row) {
    $temp = (float)$row['売上時の気温'];
    $shouhin_nm = $row['商品名'];
    $uriage_kin = $row['売上金額'];
    $genka = $row['売上原価'];

    $temp_range = '';
    if ($temp < 0) {
        $temp_range = '0℃未満';
    } elseif ($temp >= 0 && $temp < 10) {
        $temp_range = '0℃～9℃';
    } elseif ($temp >= 10 && $temp < 20) {
        $temp_range = '10℃～19℃';
    } elseif ($temp >= 20 && $temp < 30) {
        $temp_range = '20℃～29℃';
    } elseif ($temp >= 30) {
        $temp_range = '30℃以上';
    }

    if (!isset($temperature_product_summary[$temp_range])) {
        $temperature_product_summary[$temp_range] = [];
    }
    if (!isset($temperature_product_summary[$temp_range][$shouhin_nm])) {
        $temperature_product_summary[$temp_range][$shouhin_nm] = ['売上金額' => 0, '売上原価' => 0, '粗利' => 0];
    }
    $temperature_product_summary[$temp_range][$shouhin_nm]['売上金額'] += $uriage_kin;
    $temperature_product_summary[$temp_range][$shouhin_nm]['売上原価'] += $genka;
    $temperature_product_summary[$temp_range][$shouhin_nm]['粗利'] += ($uriage_kin - $genka);
}

echo "<h2>11. 売上時の気温ごと商品ごとの売上金額ランキングと粗利</h2>";
foreach ($sorted_temperature_summary as $range => $data_placeholder) { // Use sorted_temperature_summary for consistent order
    if (isset($temperature_product_summary[$range])) {
        echo "<h3>" . htmlspecialchars($range) . "</h3>";
        $products_in_range = $temperature_product_summary[$range];
        // Sort products by 売上金額 in descending order
        uasort($products_in_range, function ($a, $b) {
            return $b['売上金額'] <=> $a['売上金額'];
        });
        $top10_products_in_range = array_slice($products_in_range, 0, 10, true);

        echo "<table border='1'>";
        echo "<tr><th>商品名</th><th>売上金額</th><th>粗利</th></tr>";
        foreach ($top10_products_in_range as $shouhin_nm => $data) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($shouhin_nm) . "</td>";
            echo "<td>" . number_format($data['売上金額']) . "</td>";
            echo "<td>" . number_format($data['粗利']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
}


// １２．イベント以外の売上先ごとの売上金額と粗利
$tokuisaki_summary = [];
foreach ($meisai_data as $row) {
    $tokuisaki_nm = $row['イベント以外の売上先'];
    // イベント以外の売上先が空の場合はスキップ
    if (empty($tokuisaki_nm)) {
        continue;
    }
    $uriage_kin = $row['売上金額'];
    $genka = $row['売上原価'];

    if (!isset($tokuisaki_summary[$tokuisaki_nm])) {
        $tokuisaki_summary[$tokuisaki_nm] = ['売上金額' => 0, '売上原価' => 0, '粗利' => 0];
    }
    $tokuisaki_summary[$tokuisaki_nm]['売上金額'] += $uriage_kin;
    $tokuisaki_summary[$tokuisaki_nm]['売上原価'] += $genka;
    $tokuisaki_summary[$tokuisaki_nm]['粗利'] += ($uriage_kin - $genka);
}

echo "<h2>12. イベント以外の売上先ごとの売上金額と粗利</h2>";
echo "<table border='1'>";
echo "<tr><th>イベント以外の売上先</th><th>売上金額</th><th>売上原価</th><th>粗利</th></tr>";
foreach ($tokuisaki_summary as $tokuisaki_nm => $data) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($tokuisaki_nm) . "</td>";
    echo "<td>" . number_format($data['売上金額']) . "</td>";
    echo "<td>" . number_format($data['売上原価']) . "</td>";
    echo "<td>" . number_format($data['粗利']) . "</td>";
    echo "</tr>";
}
echo "</table>";
// １３．売上計上年月日ごとの売上金額と粗利


// １４．売上計上年ごとの売上金額と粗利
$yearly_summary = [];
foreach ($meisai_data as $row) {
    $year = $row['売上計上年'];
    $uriage_kin = $row['売上金額'];
    $genka = $row['売上原価'];

    if (!isset($yearly_summary[$year])) {
        $yearly_summary[$year] = ['売上金額' => 0, '売上原価' => 0, '粗利' => 0];
    }
    $yearly_summary[$year]['売上金額'] += $uriage_kin;
    $yearly_summary[$year]['売上原価'] += $genka;
    $yearly_summary[$year]['粗利'] += ($uriage_kin - $genka);
}

// Sort by year
ksort($yearly_summary);

echo "<h2>14. 年ごとの売上金額と粗利</h2>";
echo "<table border='1'>";
echo "<tr><th>年</th><th>売上金額</th><th>売上原価</th><th>粗利</th></tr>";
foreach ($yearly_summary as $year => $data) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($year) . "</td>";
    echo "<td>" . number_format($data['売上金額']) . "</td>";
    echo "<td>" . number_format($data['売上原価']) . "</td>";
    echo "<td>" . number_format($data['粗利']) . "</td>";
    echo "</tr>";
}
echo "</table>";
// １５．商品名ごとの売上金額と粗利
$product_summary = [];
foreach ($meisai_data as $row) {
    $shouhin_nm = $row['商品名'];
    $uriage_kin = $row['売上金額'];
    $genka = $row['売上原価'];

    if (!isset($product_summary[$shouhin_nm])) {
        $product_summary[$shouhin_nm] = ['売上金額' => 0, '売上原価' => 0, '粗利' => 0];
    }
    $product_summary[$shouhin_nm]['売上金額'] += $uriage_kin;
    $product_summary[$shouhin_nm]['売上原価'] += $genka;
    $product_summary[$shouhin_nm]['粗利'] += ($uriage_kin - $genka);
}

echo "<h2>15. 商品名ごとの売上金額と粗利</h2>";
echo "<table border='1'>";
echo "<tr><th>商品名</th><th>売上金額</th><th>売上原価</th><th>粗利</th></tr>";
foreach ($product_summary as $shouhin_nm => $data) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($shouhin_nm) . "</td>";
    echo "<td>" . number_format($data['売上金額']) . "</td>";
    echo "<td>" . number_format($data['売上原価']) . "</td>";
    echo "<td>" . number_format($data['粗利']) . "</td>";
    echo "</tr>";
}
echo "</table>";
// １６．商品名ごとの売上個数
$product_quantity_summary = [];
foreach ($meisai_data as $row) {
    $shouhin_nm = $row['商品名'];
    $su = $row['売上個数'];

    if (!isset($product_quantity_summary[$shouhin_nm])) {
        $product_quantity_summary[$shouhin_nm] = 0;
    }
    $product_quantity_summary[$shouhin_nm] += $su;
}

echo "<h2>16. 商品名ごとの売上個数</h2>";
echo "<table border='1'>";
echo "<tr><th>商品名</th><th>売上個数</th></tr>";
foreach ($product_quantity_summary as $shouhin_nm => $quantity) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($shouhin_nm) . "</td>";
    echo "<td>" . number_format($quantity) . "</td>";
    echo "</tr>";
}
echo "</table>";
// １７．イベント開催住所ごとの売上金額と粗利
$address_summary = [];
foreach ($meisai_data as $row) {
    $address = $row['イベント開催住所'];
    // 住所が空の場合はスキップ
    if (empty($address)) {
        continue;
    }
    $uriage_kin = $row['売上金額'];
    $genka = $row['売上原価'];

    if (!isset($address_summary[$address])) {
        $address_summary[$address] = ['売上金額' => 0, '売上原価' => 0, '粗利' => 0];
    }
    $address_summary[$address]['売上金額'] += $uriage_kin;
    $address_summary[$address]['売上原価'] += $genka;
    $address_summary[$address]['粗利'] += ($uriage_kin - $genka);
}

echo "<h2>17. イベント開催住所ごとの売上金額と粗利</h2>";
echo "<table border='1'>";
echo "<tr><th>イベント開催住所</th><th>売上金額</th><th>売上原価</th><th>粗利</th></tr>";
foreach ($address_summary as $address => $data) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($address) . "</td>";
    echo "<td>" . number_format($data['売上金額']) . "</td>";
    echo "<td>" . number_format($data['売上原価']) . "</td>";
    echo "<td>" . number_format($data['粗利']) . "</td>";
    echo "</tr>";
}
echo "</table>";
// １８．売上時の天気詳細ごとの売上金額と粗利
$weather_description_summary = [];
foreach ($meisai_data as $row) {
    $weather_description = $row['売上時の天気詳細'];
    // 天気詳細が空の場合はスキップ
    if (empty($weather_description)) {
        continue;
    }
    $uriage_kin = $row['売上金額'];
    $genka = $row['売上原価'];

    if (!isset($weather_description_summary[$weather_description])) {
        $weather_description_summary[$weather_description] = ['売上金額' => 0, '売上原価' => 0, '粗利' => 0];
    }
    $weather_description_summary[$weather_description]['売上金額'] += $uriage_kin;
    $weather_description_summary[$weather_description]['売上原価'] += $genka;
    $weather_description_summary[$weather_description]['粗利'] += ($uriage_kin - $genka);
}

echo "<h2>18. 売上時の天気詳細ごとの売上金額と粗利</h2>";
echo "<table border='1'>";
echo "<tr><th>天気詳細</th><th>売上金額</th><th>売上原価</th><th>粗利</th></tr>";
foreach ($weather_description_summary as $weather_description => $data) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($weather_description) . "</td>";
    echo "<td>" . number_format($data['売上金額']) . "</td>";
    echo "<td>" . number_format($data['売上原価']) . "</td>";
    echo "<td>" . number_format($data['粗利']) . "</td>";
    echo "</tr>";
}
echo "</table>";
// １９．売上番号ごとの売上金額と粗利


?>