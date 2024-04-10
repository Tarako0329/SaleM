<?php
/*
*params:POST
*   user_id     ：ログインユーザID
*   orderby     ：
*   list_type   ：
*   serch_word  ：
*/
require "php_header.php";

if(!empty($_SESSION["user_id"])){
	
	$sqlstr = "select SM.*
		,tanka+tanka_zei as moto_kin
		,ZM.hyoujimei,length(ShouhinNM) as nm_bite
		,tanka_zei as bk_tanka_zei
		,ZM.zeiritu as bk_zeiritu
		,SM.zeiKBN as bk_zeiKBN
		,ZM.hyoujimei as bk_hyoujimei 
		,false as cate_chk
		from vw_shouhinms SM inner join ZeiMS ZM on SM.zeiKBN = ZM.zeiKBN where uid = ? order by shouhinNM";
	//log_writer2("ajax_get_MSCategory_list.php ",$sqlstr,"lv3");

	$stmt = $pdo_h->prepare($sqlstr);
	$stmt->bindValue(1, $_SESSION["user_id"], PDO::PARAM_INT);
	$stmt->execute();
	$shouhihMS = $stmt->fetchAll();
}else{
	echo "不正アクセス";
	exit;
}

// ヘッダーを指定することによりjsonの動作を安定させる
header('Content-type: application/json');
// htmlへ渡す配列$productListをjsonに変換する
echo json_encode($shouhihMS, JSON_UNESCAPED_UNICODE);
exit();
?>