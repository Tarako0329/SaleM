<?php
/*関数メモ
check_session_userid：セッションのユーザIDが消えた場合、自動ログインがオフならログイン画面へ、オンなら自動ログインテーブルからユーザIDを取得

【想定して無いページからの遷移チェック】
csrf_create()：SESSIONとCOOKIEに同一トークンをセットし、同内容を返す。(POSTorGETで遷移先に渡す)
　　　　　　　 headerでリダイレクトされた場合、COOKIEにセットされないので注意。

*/

require "php_header.php";
U::log("\$_POST",$_POST);

//セッションのIDがクリアされた場合の再取得処理。
$rtn=check_session_userid($pdo_h);
$rtn = csrf_checker(["genka.php"],["P","C","S"]);

$status = "success";
$rows = [];
if($rtn !== true){
	$msg = "セッションが不正です";
	$status = "failure";
}else{
	
	try{

		$params["uid"]=$_SESSION['user_id'];
		$params["shouhinCD"] = $_POST['shouhinCD'];

		$sqlst = "SELECT * FROM ShouhinMS‗Genzairyou where `uid` = :uid and shouhinCD = :shouhinCD";
		$ShouhinMS‗Genzairyou = $db->SELECT($sqlst,$params);

		$sqlst = "SELECT 
			zairyou_zaiko.*
			,IF(ShouhinMS‗Genzairyou.zairyouCD IS NULL,'false','true') as used 
			,IF(ShouhinMS‗Genzairyou.zairyouCD IS NULL,0,ShouhinMS‗Genzairyou.use_vol) as use_volume
			FROM zairyou_zaiko 
			LEFT JOIN ShouhinMS‗Genzairyou 
			on zairyou_zaiko.zairyouCD = ShouhinMS‗Genzairyou.zairyouCD
			and ShouhinMS‗Genzairyou.shouhinCD = :shouhinCD
			where zairyou_zaiko.`uid` = :uid ";	//trueがチェック
		$zairyou_zaiko = $db->SELECT($sqlst,$params);

	}catch(\Throwable $e){
		$db->Exception_rollback($e,"商品マスタの原材料取得でエラー。至急調査してください。");
		$status = "failure";
		$msg = "商品マスタの原材料取得に失敗しました。";
	}
}
$csrf_token=csrf_create();
$return_sts = array(
	"MSG" => $msg
	,"status" => $status
	,"csrf_token" => $csrf_token
	,"ShouhinMS‗Genzairyou" => $ShouhinMS‗Genzairyou
	,"zairyou_zaiko" => $zairyou_zaiko
);
header('Content-type: application/json');
echo json_encode($return_sts, JSON_UNESCAPED_UNICODE);

exit();

?>
