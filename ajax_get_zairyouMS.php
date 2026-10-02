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
		//トランザクション開始

		$db->begin_tran();

		$params["uid"]=$_SESSION['user_id'];

		$sqlst = "SELECT 
			*
			,'false' as used 
			,0 as use_volume
			FROM zairyou_zaiko where `uid` = :uid";	//trueがチェック
		$rows = $db->SELECT($sqlst,$params);


	}catch(\Throwable $e){
		$db->Exception_rollback($e,"材料在庫取得でエラー。至急調査してください。");
		$status = "failure";
		$msg = "登録に失敗しました。";
	}
}
$csrf_token=csrf_create();
$return_sts = array(
	"MSG" => $msg
	,"status" => $status
	,"csrf_token" => $csrf_token
	,"rows" => $rows
);
header('Content-type: application/json');
echo json_encode($return_sts, JSON_UNESCAPED_UNICODE);

exit();

?>
