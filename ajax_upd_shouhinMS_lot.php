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
$return = "";
$status = "success";
if($rtn !== true){
	$msg = "セッションが不正です";
	$status = "failure";
}else{
	
	try{
		//トランザクション開始

		$db->begin_tran();

		$params["uid"]=$_SESSION['user_id'];
		$params["shouhinCD"]=$_POST["shouhinCD"];
		$params["H_lot"]=$_POST["H_lot"];
		$params["S_lot"]=$_POST["S_lot"];
		$params["G_Per"]=$_POST["G_per"];
		$params["genka_tanka"]=$_POST["GENKA_TANKA"];
		$params["seizou_genka_tanka"]=$_POST["seizou_genka_tanka"];

		if($params["shouhinCD"]!=0){//update
			$sqlstr="UPDATE ShouhinMS set 
				utisu = :H_lot
				,S_lot = :S_lot
				,G_Per = :G_Per
				,`genka_tanka` = :genka_tanka 
				,seizou_genka_tanka = :seizou_genka_tanka
				where `uid` = :uid and shouhinCD = :shouhinCD";
		}else{
			//$sqlst = "INSERT INTO zairyou_zaiko(`uid`,hinmei,`value`,zeikbn,volum,unit) values(:uid,:hinmei,:value,:zeikbn,:volum,:unit)";
		}

		U::log("\$params",$params);
		$return = $db->UP_DEL_EXEC($sqlstr,$params);

		$db->commit_tran();

	}catch(\Throwable $e){
		$db->Exception_rollback($e,"商品マスタの原価情報登録でエラー。至急調査してください。");
		$status = "failure";
		$msg = "登録に失敗しました。";
	}
}
$csrf_token=csrf_create();
$return_sts = array(
	"MSG" => $msg
	,"status" => $status
	,"csrf_token" => $csrf_token
	,"new_zairyouCD" => $return
);
header('Content-type: application/json');
echo json_encode($return_sts, JSON_UNESCAPED_UNICODE);

exit();

?>
