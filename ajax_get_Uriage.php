<?php
/*
*本日の売上データを取得
*params:POST
*   user_id     ：ログインユーザID
*   orderby     ：
*   list_type   ：
*   serch_word  ：
*/
require "php_header.php";

if(!empty($_POST)){
	$sql = "select *,(UriageKin + zei) as ZeikomiUriage,max(UriageNO) OVER() as lastNo from UriageData where uid = ? and UriDate = ? order by insDatetime desc,shouhinCD";
	//log_writer("ajax_get_Uriage.php ",$sqlstr);
	$stmt = $pdo_h->prepare($sql);
	$stmt->bindValue(1, $_POST['user_id'], PDO::PARAM_INT);
	$stmt->bindValue(2, (string)date("Y-m-d"), PDO::PARAM_STR);
	$stmt->execute();
	$UriageList = $stmt->fetchAll(PDO::FETCH_ASSOC);
	
	$i=0;
	foreach($UriageList as $row){
		$UriageList[$i]["URL"] = ROOT_URL."ryoushuu_pdf.php?u=".rot13encrypt2($row["UriageNO"])."&i=".rot13encrypt2($_POST["user_id"]);//領収書リンク
		$i++;
	}
}else{
	echo "不正アクセス";
	exit;
}

// ヘッダーを指定することによりjsonの動作を安定させる
header('Content-type: application/json');
// htmlへ渡す配列$productListをjsonに変換する
echo json_encode($UriageList, JSON_UNESCAPED_UNICODE);
?>


